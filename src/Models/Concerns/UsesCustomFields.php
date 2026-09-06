<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Models\Concerns;

use Filament\Facades\Filament;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Enums\CustomFieldsFeature;
use Relaticle\CustomFields\FeatureSystem\FeatureManager;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldLink;
use Relaticle\CustomFields\Models\CustomFieldRelationship;
use Relaticle\CustomFields\Models\CustomFieldValue;
use Relaticle\CustomFields\QueryBuilders\CustomFieldQueryBuilder;
use Relaticle\CustomFields\Services\Relationships\LinkReader;
use Relaticle\CustomFields\Services\Relationships\LinkWriter;
use Relaticle\CustomFields\Services\ValueResolver\LookupPreloader;

/**
 * @see HasCustomFields
 */
trait UsesCustomFields
{
    public function __construct($attributes = [])
    {
        if (count($this->getFillable()) !== 0) {
            $this->mergeFillable(['custom_fields']);
        }

        parent::__construct($attributes);

        $this->handleCustomFields();
    }

    public function isFillable($key): bool
    {
        if ($key === 'custom_fields') {
            return true;
        }

        return parent::isFillable($key);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function fillableFromArray(array $attributes): array
    {
        $fillable = parent::fillableFromArray($attributes);

        if (array_key_exists('custom_fields', $attributes) && ! array_key_exists('custom_fields', $fillable)) {
            $fillable['custom_fields'] = $attributes['custom_fields'];
        }

        return $fillable;
    }

    /**
     * @var array<int, array<string, mixed>>
     */
    protected static array $tempCustomFields = [];

    protected static function bootUsesCustomFields(): void
    {
        static::saving(function (Model $model): void {
            $model->handleCustomFields();
        });

        static::saved(function (Model $model): void {
            $model->saveCustomFieldsFromTemp();
        });

        static::deleting(function (Model $model): void {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->customFieldValues()->delete();
        });
    }

    /**
     * Handle the custom fields before saving the model.
     */
    protected function handleCustomFields(): void
    {
        if (isset($this->custom_fields) && is_array($this->custom_fields)) {
            self::$tempCustomFields[spl_object_id($this)] = $this->custom_fields;
            unset($this->custom_fields);
        }
    }

    /**
     * Save custom fields from temporary storage after the model is created/updated.
     */
    protected function saveCustomFieldsFromTemp(): void
    {
        $objectId = spl_object_id($this);

        if (isset(self::$tempCustomFields[$objectId]) && method_exists($this, 'saveCustomFields')) {
            $this->saveCustomFields(self::$tempCustomFields[$objectId]);
            unset(self::$tempCustomFields[$objectId]);
        }
    }

    /**
     * @return CustomFieldQueryBuilder<CustomField>
     */
    public function customFields(): CustomFieldQueryBuilder
    {
        return CustomFields::newCustomFieldModel()->query()->forEntity($this::class);
    }

    /**
     * @return MorphMany<CustomFieldValue>
     */
    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFields::valueModel(), 'entity');
    }

    /**
     * @return MorphMany<CustomFieldLink>
     */
    public function outgoingLinks(): MorphMany
    {
        return $this->morphMany(CustomFields::linkModel(), 'from_entity');
    }

    /**
     * @return MorphMany<CustomFieldLink>
     */
    public function incomingLinks(): MorphMany
    {
        return $this->morphMany(CustomFields::linkModel(), 'to_entity');
    }

    /**
     * The ledger keeps closed edges forever, so only the active ones are worth carrying
     * into a page render.
     */
    public function scopeWithActiveCustomFieldLinks(Builder $query): Builder
    {
        if (! FeatureManager::isEnabled(CustomFieldsFeature::SYSTEM_RELATIONSHIPS)) {
            return $query;
        }

        return $query->with([
            'outgoingLinks' => fn (MorphMany $links): MorphMany => $links->whereNull('active_until'),
            'incomingLinks' => fn (MorphMany $links): MorphMany => $links->whereNull('active_until'),
        ]);
    }

    public function scopeWithCustomFieldValues(Builder $query): Builder
    {
        return $query
            ->withActiveCustomFieldLinks()
            ->with('customFieldValues.customField.options')
            ->afterQuery(function ($records): void {
                if ($records instanceof EloquentCollection) {
                    app(LookupPreloader::class)->preload($records);
                }
            });
    }

    public function getCustomFieldValue(CustomField $customField): mixed
    {
        $definition = $customField->relationshipDefinition();

        if ($definition instanceof CustomFieldRelationship) {
            return app(LinkReader::class)->orderedIdsFor($this, $definition, $definition->readDirectionFor($customField));
        }

        $fieldValue = $this->customFieldValues
            ->firstWhere('custom_field_id', $customField->getKey())
            ?->getValue();

        if (empty($fieldValue)) {
            return $fieldValue;
        }

        if ($customField->settings?->encrypted) {
            $fieldValue = Crypt::decryptString($fieldValue);
        }

        return $fieldValue instanceof Collection
            ? $fieldValue->toArray()
            : $fieldValue;
    }

    public function saveCustomFieldValue(CustomField $customField, mixed $value, ?Model $tenant = null): void
    {
        if ($customField->relationshipDefinition() instanceof CustomFieldRelationship) {
            app(LinkWriter::class)->apply($this, $customField, $this->linkTargetIds($value));

            return;
        }

        $data = ['custom_field_id' => $customField->getKey()];

        if (FeatureManager::isEnabled(CustomFieldsFeature::SYSTEM_MULTI_TENANCY)) {
            $data[config('custom-fields.database.column_names.tenant_foreign_key')] = $this->resolveTenantId($tenant, $customField);
        }

        $customFieldValue = $this->customFieldValues();

        if ($customField->settings?->encrypted) {
            $customFieldValue->withCasts([$customField->getValueColumn() => 'encrypted']);
        }

        $customFieldValue = $customFieldValue->firstOrNew($data);
        $customFieldValue->setValue($value);
        $customFieldValue->save();
    }

    /**
     * An empty payload is a real value that closes every edge, so only null and blank ids
     * fall away here.
     *
     * @return array<int, int|string>
     */
    private function linkTargetIds(mixed $value): array
    {
        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        $ids = is_array($value) ? $value : [$value];

        return array_values(array_filter(
            $ids,
            static fn (mixed $id): bool => is_int($id) || (is_string($id) && $id !== ''),
        ));
    }

    /**
     * Resolve the tenant ID from available sources
     */
    protected function resolveTenantId(?Model $tenant, CustomField $customField): mixed
    {
        // First priority: Explicitly provided tenant
        if ($tenant instanceof Model) {
            return $tenant->getKey();
        }

        // Second priority: Current Filament tenant
        $filamentTenant = Filament::getTenant();
        if ($filamentTenant !== null) {
            return $filamentTenant->getKey();
        }

        // Fallback: Use the tenant from the custom field
        $tenantColumn = config('custom-fields.database.column_names.tenant_foreign_key');

        return $customField->{$tenantColumn};
    }

    /**
     * @param  array<string, mixed>  $customFields
     */
    public function saveCustomFields(array $customFields, ?Model $tenant = null): void
    {
        $this->customFields()->each(function (CustomField $customField) use ($customFields, $tenant): void {
            // A relationship has no row to overwrite with null: an absent key means the
            // payload said nothing about those edges, so they stay as they are.
            if (! array_key_exists($customField->code, $customFields)
                && $customField->relationshipDefinition() instanceof CustomFieldRelationship) {
                return;
            }

            $value = $customFields[$customField->code] ?? null;
            $this->saveCustomFieldValue($customField, $value, $tenant);
        });
    }
}
