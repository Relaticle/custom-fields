<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Relaticle\CustomFields\Casts\CustomFieldTypeCast;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Data\CustomFieldSettingsData;
use Relaticle\CustomFields\Data\ValidationRuleData;
use Relaticle\CustomFields\Database\Factories\CustomFieldFactory;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\CustomFieldWidth;
use Relaticle\CustomFields\Models\Concerns\Activable;
use Relaticle\CustomFields\Models\Scopes\CustomFieldsActivableScope;
use Relaticle\CustomFields\Models\Scopes\SortOrderScope;
use Relaticle\CustomFields\Models\Scopes\TenantScope;
use Relaticle\CustomFields\Observers\CustomFieldObserver;
use Relaticle\CustomFields\QueryBuilders\CustomFieldQueryBuilder;
use Spatie\LaravelData\DataCollection;

/**
 * @property string $name
 * @property string $code
 * @property CustomFieldType $type
 * @property string $entity_type
 * @property string $lookup_type
 * @property DataCollection<int, ValidationRuleData> $validation_rules
 * @property CustomFieldSettingsData $settings
 * @property int $sort_order
 * @property bool $active
 * @property bool $system_defined
 */
#[ScopedBy([TenantScope::class, SortOrderScope::class])]
#[ObservedBy(CustomFieldObserver::class)]
class CustomField extends Model
{
    use Activable;

    /** @use HasFactory<CustomFieldFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $guarded = [];

    protected $attributes = [
        'width' => CustomFieldWidth::_100,
    ];

    public function __construct(array $attributes = [])
    {
        if (! isset($this->table)) {
            $this->setTable(config('custom-fields.table_names.custom_fields'));
        }

        parent::__construct($attributes);
    }

    /**
     * Boot the soft deleting trait for a model.
     */
    public static function bootActivable(): void
    {
        static::addGlobalScope(new CustomFieldsActivableScope);
    }

    public function newEloquentBuilder($query): CustomFieldQueryBuilder
    {
        return new CustomFieldQueryBuilder($query);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CustomFieldTypeCast::class,
            'width' => CustomFieldWidth::class,
            'validation_rules' => DataCollection::class.':'.ValidationRuleData::class.',default',
            'active' => 'boolean',
            'system_defined' => 'boolean',
            'settings' => CustomFieldSettingsData::class.':default',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CustomFields::sectionModel(), 'custom_field_section_id');
    }

    /**
     * @return HasMany<CustomFieldValue>
     */
    public function values(): HasMany
    {
        return $this->hasMany(CustomFields::valueModel());
    }

    /**
     * @return HasMany<CustomFieldOption>
     */
    public function options(): HasMany
    {
        return $this->hasMany(CustomFields::optionModel());
    }

    /**
     * Determine if the model instance is user defined.
     */
    public function isSystemDefined(): bool
    {
        return $this->system_defined === true;
    }

    public function getValueColumn(): string
    {
        return CustomFields::newValueModel()::getValueColumn($this->type);
    }
}
