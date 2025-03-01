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
use Relaticle\CustomFields\Database\Factories\CustomFieldValueFactory;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\Scopes\TenantScope;

/**
 * @property CustomField $customField
 * @property ?string $text_value
 * @property ?int $integer_value
 * @property ?float $float_value
 * @property ?Collection $json_value
 * @property ?bool $boolean_value
 * @property ?Carbon $date_value
 * @property ?Carbon $datetime_value
 */
#[ScopedBy([TenantScope::class])]
final class CustomFieldValue extends Model
{
    /** @use HasFactory<CustomFieldValueFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        if (! isset($this->table)) {
            $this->setTable(config('custom-fields.table_names.custom_field_values'));
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

    public static function getValueColumn(CustomFieldType $type): string
    {
        return match ($type) {
            CustomFieldType::TEXT, CustomFieldType::TEXTAREA, CustomFieldType::RICH_EDITOR, CustomFieldType::MARKDOWN_EDITOR => 'text_value',
            CustomFieldType::LINK, CustomFieldType::COLOR_PICKER => 'string_value',
            CustomFieldType::NUMBER, CustomFieldType::RADIO, CustomFieldType::SELECT => 'integer_value',
            CustomFieldType::CHECKBOX, CustomFieldType::TOGGLE => 'boolean_value',
            CustomFieldType::CHECKBOX_LIST, CustomFieldType::TOGGLE_BUTTONS, CustomFieldType::TAGS_INPUT, CustomFieldType::MULTI_SELECT => 'json_value',
            CustomFieldType::CURRENCY => 'float_value',
            CustomFieldType::DATE => 'date_value',
            CustomFieldType::DATE_TIME => 'datetime_value',
        };
    }

    /**
     * @return BelongsTo<CustomField, CustomFieldValue>
     */
    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }

    /**
     * @return MorphTo<Model, CustomFieldValue>
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function getValue(): mixed
    {
        $column = $this->getValueColumn($this->customField->type);

        return $this->$column;
    }

    public function setValue(mixed $value): void
    {
        $column = $this->getValueColumn($this->customField->type);
        $this->$column = $value;
    }
}
