<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Concerns;

use Illuminate\Database\Eloquent\Model;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldValue;

/**
 * ABOUTME: Trait for handling custom field value loading and saving
 * ABOUTME: Provides common state management functionality for all component types
 */
trait HasCustomFieldState
{
    /**
     * Resolve the state value for a custom field from a model
     *
     * @param  Model  $record
     * @param  CustomField  $field
     * @return mixed
     */
    protected function resolveState(Model $record, CustomField $field): mixed
    {
        if (! $record->exists) {
            return $this->getDefaultValue($field);
        }

        // Load the custom field value
        $customFieldValue = $record->customFieldValues()
            ->where('custom_field_id', $field->id)
            ->first();

        if (! $customFieldValue) {
            return $this->getDefaultValue($field);
        }

        // Process the value based on field type
        return $this->processValue($customFieldValue->value, $field);
    }

    /**
     * Get the default value for a field
     *
     * @param  CustomField  $field
     * @return mixed
     */
    protected function getDefaultValue(CustomField $field): mixed
    {
        $config = $field->field_config ?? [];

        // Check for explicit default value in config
        if (array_key_exists('default', $config)) {
            return $config['default'];
        }

        // Return type-specific defaults
        return match ($field->type->value) {
            'boolean', 'toggle' => false,
            'number', 'currency' => 0,
            'multiselect', 'checkbox_list', 'tags' => [],
            default => null,
        };
    }

    /**
     * Process a stored value for display
     *
     * @param  mixed  $value
     * @param  CustomField  $field
     * @return mixed
     */
    protected function processValue(mixed $value, CustomField $field): mixed
    {
        // Handle null values
        if ($value === null) {
            return $this->getDefaultValue($field);
        }

        // Process based on field type
        return match ($field->type->value) {
            'number', 'currency' => $this->processNumericValue($value),
            'boolean', 'toggle' => $this->processBooleanValue($value),
            'date' => $this->processDateValue($value),
            'datetime' => $this->processDateTimeValue($value),
            'multiselect', 'checkbox_list', 'tags' => $this->processArrayValue($value),
            default => $value,
        };
    }

    /**
     * Process numeric values
     *
     * @param  mixed  $value
     * @return float|int|null
     */
    protected function processNumericValue(mixed $value): float|int|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? +$value : null;
    }

    /**
     * Process boolean values
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function processBooleanValue(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Process date values
     *
     * @param  mixed  $value
     * @return string|null
     */
    protected function processDateValue(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Process datetime values
     *
     * @param  mixed  $value
     * @return string|null
     */
    protected function processDateTimeValue(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toIso8601String();
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Process array values
     *
     * @param  mixed  $value
     * @return array
     */
    protected function processArrayValue(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Get the state attribute name for a custom field
     *
     * @param  CustomField  $field
     * @return string
     */
    protected function getStateAttributeName(CustomField $field): string
    {
        return "custom_fields.{$field->code}";
    }

    /**
     * Save custom field value to the database
     *
     * @param  Model  $record
     * @param  CustomField  $field
     * @param  mixed  $value
     * @return void
     */
    protected function saveCustomFieldValue(Model $record, CustomField $field, mixed $value): void
    {
        if (! $record->exists) {
            return;
        }

        // Prepare value for storage
        $preparedValue = $this->prepareValueForStorage($value, $field);

        // Update or create the custom field value
        CustomFieldValue::updateOrCreate(
            [
                'custom_field_id' => $field->id,
                'customizable_type' => $record->getMorphClass(),
                'customizable_id' => $record->getKey(),
            ],
            [
                'value' => $preparedValue,
            ]
        );
    }

    /**
     * Prepare a value for database storage
     *
     * @param  mixed  $value
     * @param  CustomField  $field
     * @return mixed
     */
    protected function prepareValueForStorage(mixed $value, CustomField $field): mixed
    {
        // Handle null or empty values
        if ($value === null || $value === '') {
            return null;
        }

        // Prepare based on field type
        return match ($field->type->value) {
            'multiselect', 'checkbox_list', 'tags' => json_encode($value),
            'boolean', 'toggle' => (bool) $value,
            'number' => is_numeric($value) ? (float) $value : null,
            'currency' => is_numeric($value) ? round((float) $value, 2) : null,
            default => $value,
        };
    }
}