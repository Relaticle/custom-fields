<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Models;

use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Database\Factories\CustomFieldValueFactory;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\Scopes\TenantScope;
use Relaticle\CustomFields\Services\FieldTypeRegistryService;
use Relaticle\CustomFields\Support\SafeValueConverter;

/**
 * @property int $id
 * @property string $entity_type
 * @property int $entity_id
 * @property int $custom_field_id
 * @property ?string $string_value
 * @property ?string $text_value
 * @property ?int $integer_value
 * @property ?float $float_value
 * @property ?Collection<int, mixed> $json_value
 * @property ?bool $boolean_value
 * @property ?Carbon $date_value
 * @property ?Carbon $datetime_value
 * @property CustomField $customField
 * @property Model $entity
 */
#[ScopedBy([TenantScope::class])]
class CustomFieldValue extends Model
{
    /** @use HasFactory<CustomFieldValueFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [];

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        if ($this->table === null) {
            $this->setTable(
                config('custom-fields.table_names.custom_field_values')
            );
        }

        parent::__construct($attributes);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'string_value' => 'string',
            'text_value' => 'string',
            'integer_value' => 'integer',
            'float_value' => 'float',
            'json_value' => 'collection',
            'boolean_value' => 'boolean',
            'date_value' => 'date',
            'datetime_value' => 'datetime',
        ];
    }

    public static function getValueColumn(CustomFieldType|string $type): string
    {
        // Handle enum types
        if ($type instanceof CustomFieldType) {
            return match ($type) {
                CustomFieldType::TEXT,
                CustomFieldType::TEXTAREA,
                CustomFieldType::RICH_EDITOR,
                CustomFieldType::MARKDOWN_EDITOR => 'text_value',
                CustomFieldType::LINK,
                CustomFieldType::COLOR_PICKER => 'string_value',
                CustomFieldType::NUMBER,
                CustomFieldType::RADIO,
                CustomFieldType::SELECT => 'integer_value',
                CustomFieldType::CHECKBOX,
                CustomFieldType::TOGGLE => 'boolean_value',
                CustomFieldType::CHECKBOX_LIST,
                CustomFieldType::TOGGLE_BUTTONS,
                CustomFieldType::TAGS_INPUT,
                CustomFieldType::MULTI_SELECT => 'json_value',
                CustomFieldType::CURRENCY => 'float_value',
                CustomFieldType::DATE => 'date_value',
                CustomFieldType::DATE_TIME => 'datetime_value',
            };
        }

        // Handle custom field types (strings) - determine column based on category
        if (app()->bound(FieldTypeRegistryService::class)) {
            $registry = app(FieldTypeRegistryService::class);
            $fieldTypeConfig = $registry->getFieldType($type);

            if ($fieldTypeConfig !== null) {
                return match ($fieldTypeConfig['category']) {
                    'text' => 'text_value',
                    'numeric' => 'integer_value',
                    'date' => 'date_value',
                    'boolean' => 'boolean_value',
                    'single_option' => 'integer_value',
                    'multi_option' => 'json_value',
                    default => 'text_value',
                };
            }
        }

        // Fallback for unknown types
        return 'text_value';
    }

    /**
     * @return BelongsTo<CustomField, self>
     */
    public function customField(): BelongsTo
    {
        /** @var BelongsTo<CustomField, self> */
        return $this->belongsTo(CustomFields::customFieldModel());
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function getValue(): mixed
    {
        $column = static::getValueColumn($this->customField->type);

        /** @phpstan-ignore-next-line */
        return $this->$column;
    }

    public function setValue(mixed $value): void
    {
        $column = static::getValueColumn($this->customField->type);

        // Convert the value to a database-safe format based on the field type
        $safeValue = SafeValueConverter::toDbSafe(
            $value,
            $this->customField->type
        );

        /** @phpstan-ignore-next-line */
        $this->$column = $safeValue;
    }
}
