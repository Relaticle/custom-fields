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
use Relaticle\CustomFields\Models\Concerns\HasFieldTypeHelpers;
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
 * @property CustomFieldWidth $width
 *
 * @method static CustomFieldQueryBuilder<CustomField> query()
 * @method static CustomFieldQueryBuilder<CustomField> where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static CustomFieldQueryBuilder<CustomField> whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static CustomFieldQueryBuilder<CustomField> active()
 * @method static CustomFieldQueryBuilder<CustomField> visibleInList()
 * @method static CustomFieldQueryBuilder<CustomField> nonEncrypted()
 * @method static CustomFieldQueryBuilder<CustomField> forEntity(string $model)
 * @method static CustomFieldQueryBuilder<CustomField> forMorphEntity(string $entity)
 * @method static CustomFieldQueryBuilder<CustomField> forType(CustomFieldType $type)
 */
#[ScopedBy([TenantScope::class, SortOrderScope::class])]
#[ObservedBy(CustomFieldObserver::class)]
class CustomField extends Model
{
    use Activable;

    /** @use HasFactory<CustomFieldFactory> */
    use HasFactory;

    use HasFieldTypeHelpers;

    /**
     * @var array<string>|bool
     */
    protected $guarded = [];

    protected $attributes = [
        'width' => CustomFieldWidth::_100,
    ];

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        if ($this->table === null) {
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

    /**
     * @return CustomFieldQueryBuilder<self>
     */
    public function newEloquentBuilder($query): CustomFieldQueryBuilder
    {
        /** @var CustomFieldQueryBuilder<CustomField> */
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

    /**
     * @return BelongsTo<CustomFieldSection, self>
     */
    public function section(): BelongsTo
    {
        /** @var BelongsTo<CustomFieldSection, self> */
        return $this->belongsTo(CustomFields::sectionModel(), 'custom_field_section_id');
    }

    /**
     * @return HasMany<CustomFieldValue, self>
     */
    public function values(): HasMany
    {
        /** @var HasMany<CustomFieldValue, self> */
        return $this->hasMany(CustomFields::valueModel());
    }

    /**
     * @return HasMany<CustomFieldOption, self>
     */
    public function options(): HasMany
    {
        /** @var HasMany<CustomFieldOption, self> */
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
