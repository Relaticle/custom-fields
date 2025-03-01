<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Models\Concerns;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldValue;
use Relaticle\CustomFields\Support\Utils;

/**
 * @see HasCustomFields
 */
trait UsesCustomFields
{
    public function __construct($attributes = [])
    {
        // Ensure custom fields are included in a fillable array
        $this->fillable = array_merge(['custom_fields'], $this->fillable);
        parent::__construct($attributes);
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
     * @return Builder<CustomField>
     */
    public function customFields(): Builder
    {
        return CustomField::query()->forEntity($this::class);
    }

    /**
     * @return MorphMany<CustomFieldValue>
     */
    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'entity');
    }

    public function getCustomFieldValue(CustomField $customField): mixed
    {
        $customFieldValue = $this->customFieldValues->where('custom_field_id', $customField->id);

        if ($customField->settings->encrypted) {
            $customFieldValue = $customFieldValue->withCasts([$customField->getValueColumn() => 'encrypted']);
        }

        $customFieldValue = $customFieldValue->first();
        $customFieldValue = $customFieldValue ? $customFieldValue->getValue() : null;

        return $customFieldValue instanceof Collection ? $customFieldValue->toArray() : $customFieldValue;
    }

    public function saveCustomFieldValue(CustomField $customField, mixed $value): void
    {
        $data = ['custom_field_id' => $customField->id];

        if (Utils::isTenantEnabled()) {
            $data[config('custom-fields.column_names.tenant_foreign_key')] = Filament::getTenant()?->id;
        }

        $customFieldValue = $this->customFieldValues();

        if ($customField->settings->encrypted) {
            $customFieldValue->withCasts([$customField->getValueColumn() => 'encrypted']);
        }

        $customFieldValue = $customFieldValue->firstOrNew($data);
        $customFieldValue->setValue($value);
        $customFieldValue->save();
    }

    /**
     * @param  array<string, mixed>  $customFields
     */
    public function saveCustomFields(array $customFields): void
    {
        $this->customFields()->each(function (CustomField $customField) use ($customFields): void {
            $value = $customFields[$customField->code] ?? null;
            $this->saveCustomFieldValue($customField, $value);
        });
    }
}
