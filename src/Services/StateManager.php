<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Contracts\Services\StateManagerInterface;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldValue;

/**
 * ABOUTME: Service for managing custom field values and state for model instances
 * ABOUTME: Handles CRUD operations on field values with type conversion and validation
 */
class StateManager implements StateManagerInterface
{
    /**
     * Whether the service is initialized
     */
    protected bool $initialized = false;

    /**
     * Configuration array
     *
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Value cache to prevent multiple queries
     *
     * @var array<string, Collection>
     */
    protected array $valueCache = [];

    /**
     * Check if the service is properly initialized
     *
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Initialize the service with configuration
     *
     * @param  array<string, mixed>  $config
     * @return void
     */
    public function initialize(array $config = []): void
    {
        $this->config = $config;
        $this->initialized = true;
    }

    /**
     * Get the value of a custom field for a model instance
     *
     * @param  Model  $model
     * @param  CustomField  $field
     * @return mixed
     */
    public function getValue(Model $model, CustomField $field): mixed
    {
        $value = $this->getFieldValue($model, $field);

        if ($value === null) {
            return $this->getDefaultValue($field);
        }

        return $this->deserializeValue($value->value, $field);
    }

    /**
     * Set the value of a custom field for a model instance
     *
     * @param  Model  $model
     * @param  CustomField  $field
     * @param  mixed  $value
     * @return void
     */
    public function setValue(Model $model, CustomField $field, mixed $value): void
    {
        $serializedValue = $this->serializeValue($value, $field);

        CustomFieldValue::updateOrCreate(
            [
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'custom_field_id' => $field->id,
            ],
            [
                'value' => $serializedValue,
            ]
        );

        // Clear cache for this model
        $this->clearModelCache($model);
    }

    /**
     * Get all custom field values for a model instance
     *
     * @param  Model  $model
     * @return Collection<string, mixed>
     */
    public function getValues(Model $model): Collection
    {
        $cacheKey = $this->getModelCacheKey($model);

        if (! isset($this->valueCache[$cacheKey])) {
            $this->valueCache[$cacheKey] = $this->loadValues($model);
        }

        return $this->valueCache[$cacheKey];
    }

    /**
     * Set multiple custom field values for a model instance
     *
     * @param  Model  $model
     * @param  array<string, mixed>  $values
     * @return void
     */
    public function setValues(Model $model, array $values): void
    {
        foreach ($values as $fieldCode => $value) {
            $field = CustomField::where('code', $fieldCode)
                ->where('model_type', get_class($model))
                ->first();

            if ($field) {
                $this->setValue($model, $field, $value);
            }
        }
    }

    /**
     * Check if a model has a value for a specific field
     *
     * @param  Model  $model
     * @param  CustomField  $field
     * @return bool
     */
    public function hasValue(Model $model, CustomField $field): bool
    {
        return $this->getFieldValue($model, $field) !== null;
    }

    /**
     * Delete the value of a custom field for a model instance
     *
     * @param  Model  $model
     * @param  CustomField  $field
     * @return void
     */
    public function deleteValue(Model $model, CustomField $field): void
    {
        CustomFieldValue::where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->where('custom_field_id', $field->id)
            ->delete();

        $this->clearModelCache($model);
    }

    /**
     * Delete all custom field values for a model instance
     *
     * @param  Model  $model
     * @return void
     */
    public function deleteAllValues(Model $model): void
    {
        CustomFieldValue::where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->delete();

        $this->clearModelCache($model);
    }

    /**
     * Get field value from database or cache
     *
     * @param  Model  $model
     * @param  CustomField  $field
     * @return CustomFieldValue|null
     */
    protected function getFieldValue(Model $model, CustomField $field): ?CustomFieldValue
    {
        return CustomFieldValue::where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->where('custom_field_id', $field->id)
            ->first();
    }

    /**
     * Load all values for a model
     *
     * @param  Model  $model
     * @return Collection<string, mixed>
     */
    protected function loadValues(Model $model): Collection
    {
        $values = CustomFieldValue::where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->with('customField')
            ->get();

        $result = new Collection();

        foreach ($values as $value) {
            if ($value->customField) {
                $deserializedValue = $this->deserializeValue($value->value, $value->customField);
                $result->put($value->customField->code, $deserializedValue);
            }
        }

        return $result;
    }

    /**
     * Serialize value for storage
     *
     * @param  mixed  $value
     * @param  CustomField  $field
     * @return array<string, mixed>
     */
    protected function serializeValue(mixed $value, CustomField $field): array
    {
        // Handle null values
        if ($value === null) {
            return ['type' => 'null', 'value' => null];
        }

        // Handle different field types
        return match ($field->type) {
            'date', 'datetime' => ['type' => 'datetime', 'value' => $value instanceof \DateTime ? $value->format('Y-m-d H:i:s') : $value],
            'number', 'currency' => ['type' => 'number', 'value' => (float) $value],
            'boolean', 'toggle' => ['type' => 'boolean', 'value' => (bool) $value],
            'array', 'multiselect', 'tags' => ['type' => 'array', 'value' => (array) $value],
            'json' => ['type' => 'json', 'value' => is_string($value) ? json_decode($value, true) : $value],
            default => ['type' => 'string', 'value' => (string) $value],
        };
    }

    /**
     * Deserialize value from storage
     *
     * @param  array<string, mixed>  $storedValue
     * @param  CustomField  $field
     * @return mixed
     */
    protected function deserializeValue(array $storedValue, CustomField $field): mixed
    {
        if ($storedValue['type'] === 'null') {
            return null;
        }

        return match ($storedValue['type']) {
            'datetime' => new \DateTime($storedValue['value']),
            'number' => (float) $storedValue['value'],
            'boolean' => (bool) $storedValue['value'],
            'array' => (array) $storedValue['value'],
            'json' => $storedValue['value'],
            default => $storedValue['value'],
        };
    }

    /**
     * Get default value for a field
     *
     * @param  CustomField  $field
     * @return mixed
     */
    protected function getDefaultValue(CustomField $field): mixed
    {
        return $field->settings['default_value'] ?? null;
    }

    /**
     * Get cache key for a model
     *
     * @param  Model  $model
     * @return string
     */
    protected function getModelCacheKey(Model $model): string
    {
        return get_class($model) . ':' . $model->getKey();
    }

    /**
     * Clear cache for a model
     *
     * @param  Model  $model
     * @return void
     */
    protected function clearModelCache(Model $model): void
    {
        $cacheKey = $this->getModelCacheKey($model);
        unset($this->valueCache[$cacheKey]);
    }
}