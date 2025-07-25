<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Imports\ValueConverters;

use Illuminate\Database\Eloquent\Model;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\EntityTypeService;

/**
 * Default implementation of the value converter interface.
 */
final class ValueConverter implements ValueConverterInterface
{
    /**
     * Convert custom field values from import format to storage format.
     *
     * @param  Model  $record  The model record
     * @param  array<string, mixed>  $customFieldsData  The custom fields data
     * @param  Model|null  $tenant  Optional tenant for multi-tenancy support
     * @return array<string, mixed> The converted custom fields data
     */
    public function convertValues(Model $record, array $customFieldsData, ?Model $tenant = null): array
    {
        // Get the entity type for the model
        $entityType = EntityTypeService::getEntityFromModel(get_class($record));

        // Get all relevant custom fields
        $customFields = CustomField::forMorphEntity($entityType)
            ->with('options')
            ->whereIn('code', array_keys($customFieldsData))
            ->get();

        // Process each field
        foreach ($customFields as $field) {
            // Skip if no value exists for this field
            if (! array_key_exists($field->code, $customFieldsData)) {
                continue;
            }

            $value = $customFieldsData[$field->code];

            // Skip null values
            if ($value === null) {
                continue;
            }

            // Handle select/radio fields (single-value select)
            if ($this->isSingleValueSelectField($field) && ! is_numeric($value)) {
                $this->convertSingleValueField($field, $value, $customFieldsData);
            }

            // Handle multi-select fields (multi-value select)
            elseif ($this->isMultiValueSelectField($field)) {
                $this->convertMultiValueField($field, $value, $customFieldsData);
            }
        }

        return $customFieldsData;
    }

    /**
     * Check if the field is a single-value select field.
     *
     * @param  CustomField  $field  The custom field
     * @return bool True if the field is a single-value select field
     */
    private function isSingleValueSelectField(CustomField $field): bool
    {
        return in_array($field->type, [CustomFieldType::SELECT, CustomFieldType::RADIO]);
    }

    /**
     * Check if the field is a multi-value select field.
     *
     * @param  CustomField  $field  The custom field
     * @return bool True if the field is a multi-value select field
     */
    private function isMultiValueSelectField(CustomField $field): bool
    {
        return in_array($field->type, [
            CustomFieldType::MULTI_SELECT,
            CustomFieldType::CHECKBOX_LIST,
            CustomFieldType::TAGS_INPUT,
            CustomFieldType::TOGGLE_BUTTONS,
        ]);
    }

    /**
     * Convert a single-value select field value from import format to storage format.
     *
     * @param  CustomField  $field  The custom field
     * @param  mixed  $value  The value to convert
     * @param  array<string, mixed>  $customFieldsData  The custom fields data (passed by reference)
     */
    private function convertSingleValueField(CustomField $field, mixed $value, array &$customFieldsData): void
    {
        // If we have a string value instead of an ID, try to find the matching option
        if (is_string($value) && $field->options->count() > 0) {
            // Try exact match first
            $option = $field->options->where('name', $value)->first();

            // If no match, try case-insensitive match
            if (! $option) {
                $option = $field->options->first(fn ($opt) => strtolower($opt->name) === strtolower($value)
                );
            }

            // Update the value to the option ID if found
            if ($option) {
                $customFieldsData[$field->code] = $option->getKey();
            }
        }
    }

    /**
     * Convert a multi-value select field value from import format to storage format.
     *
     * @param  CustomField  $field  The custom field
     * @param  mixed  $value  The value to convert
     * @param  array<string, mixed>  $customFieldsData  The custom fields data (passed by reference)
     */
    private function convertMultiValueField(CustomField $field, mixed $value, array &$customFieldsData): void
    {
        // Ensure value is array
        $values = is_array($value) ? $value : [$value];
        $newValues = [];

        foreach ($values as $singleValue) {
            // Skip if already numeric
            if (is_numeric($singleValue)) {
                $newValues[] = (int) $singleValue;

                continue;
            }

            // Try to match string value to option
            if (is_string($singleValue) && $field->options->count() > 0) {
                // Try exact match first
                $option = $field->options->where('name', $singleValue)->first();

                // If no match, try case-insensitive match
                if (! $option) {
                    $option = $field->options->first(fn ($opt) => strtolower($opt->name) === strtolower($singleValue)
                    );
                }

                // Add option ID if found
                if ($option) {
                    $newValues[] = $option->getKey();
                }
            }
        }

        // Update the value if we have matches
        if (! empty($newValues)) {
            $customFieldsData[$field->code] = $newValues;
        }
    }
}
