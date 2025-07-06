<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Contracts\Services\ServiceInterface;
use Relaticle\CustomFields\Contracts\Services\StateManagerInterface;
use Relaticle\CustomFields\Data\VisibilityConditionData;
use Relaticle\CustomFields\Data\VisibilityData;
use Relaticle\CustomFields\Enums\Logic;
use Relaticle\CustomFields\Enums\Operator;
use Relaticle\CustomFields\Models\CustomField;

/**
 * ABOUTME: Service for resolving field visibility based on conditions and dependencies
 * ABOUTME: Evaluates complex visibility rules with support for AND/OR logic
 */
class VisibilityResolver implements ServiceInterface
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
     * Cache of evaluated visibility results
     *
     * @var array<string, bool>
     */
    protected array $visibilityCache = [];

    /**
     * Create a new visibility resolver instance
     *
     * @param  StateManagerInterface  $stateManager
     */
    public function __construct(
        protected StateManagerInterface $stateManager
    ) {
    }

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
     * Determine if a field should be visible based on its conditions
     *
     * @param  CustomField  $field
     * @param  Model|null  $model
     * @param  Collection<int, CustomField>  $allFields
     * @return bool
     */
    public function isFieldVisible(CustomField $field, ?Model $model, Collection $allFields): bool
    {
        // Field without visibility conditions is always visible
        if (! $field->visibility || empty($field->visibility)) {
            return true;
        }

        // No model means we can't evaluate conditions
        if (! $model) {
            return false;
        }

        $cacheKey = $this->getCacheKey($field, $model);
        
        if (isset($this->visibilityCache[$cacheKey])) {
            return $this->visibilityCache[$cacheKey];
        }

        $visibilityData = VisibilityData::from($field->visibility);
        $result = $this->evaluateVisibility($visibilityData, $model, $allFields);
        
        $this->visibilityCache[$cacheKey] = $result;
        
        return $result;
    }

    /**
     * Get dependent field codes for a field
     *
     * @param  CustomField  $field
     * @return array<string>
     */
    public function getDependentFieldCodes(CustomField $field): array
    {
        if (! $field->visibility || empty($field->visibility)) {
            return [];
        }

        $visibilityData = VisibilityData::from($field->visibility);
        $codes = [];

        foreach ($visibilityData->conditions as $condition) {
            $codes[] = $condition->field_code;
        }

        return array_unique($codes);
    }

    /**
     * Clear visibility cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->visibilityCache = [];
    }

    /**
     * Evaluate visibility data
     *
     * @param  VisibilityData  $visibility
     * @param  Model  $model
     * @param  Collection<int, CustomField>  $allFields
     * @return bool
     */
    protected function evaluateVisibility(VisibilityData $visibility, Model $model, Collection $allFields): bool
    {
        if ($visibility->conditions->isEmpty()) {
            return true;
        }

        $results = [];

        foreach ($visibility->conditions as $condition) {
            $results[] = $this->evaluateCondition($condition, $model, $allFields);
        }

        return $visibility->logic === Logic::AND
            ? ! in_array(false, $results, true)
            : in_array(true, $results, true);
    }

    /**
     * Evaluate a single visibility condition
     *
     * @param  VisibilityConditionData  $condition
     * @param  Model  $model
     * @param  Collection<int, CustomField>  $allFields
     * @return bool
     */
    protected function evaluateCondition(VisibilityConditionData $condition, Model $model, Collection $allFields): bool
    {
        $field = $allFields->firstWhere('code', $condition->field_code);
        
        if (! $field) {
            return false;
        }

        $fieldValue = $this->stateManager->getValue($model, $field);
        $conditionValue = $condition->value;

        return match ($condition->operator) {
            Operator::EQUALS => $this->compareEquals($fieldValue, $conditionValue),
            Operator::NOT_EQUALS => ! $this->compareEquals($fieldValue, $conditionValue),
            Operator::CONTAINS => $this->compareContains($fieldValue, $conditionValue),
            Operator::NOT_CONTAINS => ! $this->compareContains($fieldValue, $conditionValue),
            Operator::GREATER_THAN => $this->compareGreaterThan($fieldValue, $conditionValue),
            Operator::LESS_THAN => $this->compareLessThan($fieldValue, $conditionValue),
            Operator::GREATER_THAN_OR_EQUAL => $this->compareGreaterThanOrEqual($fieldValue, $conditionValue),
            Operator::LESS_THAN_OR_EQUAL => $this->compareLessThanOrEqual($fieldValue, $conditionValue),
            Operator::IS_EMPTY => $this->isEmpty($fieldValue),
            Operator::IS_NOT_EMPTY => ! $this->isEmpty($fieldValue),
            Operator::IN => $this->compareIn($fieldValue, $conditionValue),
            Operator::NOT_IN => ! $this->compareIn($fieldValue, $conditionValue),
        };
    }

    /**
     * Compare equality
     *
     * @param  mixed  $fieldValue
     * @param  mixed  $conditionValue
     * @return bool
     */
    protected function compareEquals(mixed $fieldValue, mixed $conditionValue): bool
    {
        return $fieldValue == $conditionValue;
    }

    /**
     * Check if field value contains condition value
     *
     * @param  mixed  $fieldValue
     * @param  mixed  $conditionValue
     * @return bool
     */
    protected function compareContains(mixed $fieldValue, mixed $conditionValue): bool
    {
        if (is_array($fieldValue)) {
            return in_array($conditionValue, $fieldValue, true);
        }

        if (is_string($fieldValue) && is_string($conditionValue)) {
            return str_contains($fieldValue, $conditionValue);
        }

        return false;
    }

    /**
     * Compare greater than
     *
     * @param  mixed  $fieldValue
     * @param  mixed  $conditionValue
     * @return bool
     */
    protected function compareGreaterThan(mixed $fieldValue, mixed $conditionValue): bool
    {
        return is_numeric($fieldValue) && is_numeric($conditionValue) && $fieldValue > $conditionValue;
    }

    /**
     * Compare less than
     *
     * @param  mixed  $fieldValue
     * @param  mixed  $conditionValue
     * @return bool
     */
    protected function compareLessThan(mixed $fieldValue, mixed $conditionValue): bool
    {
        return is_numeric($fieldValue) && is_numeric($conditionValue) && $fieldValue < $conditionValue;
    }

    /**
     * Compare greater than or equal
     *
     * @param  mixed  $fieldValue
     * @param  mixed  $conditionValue
     * @return bool
     */
    protected function compareGreaterThanOrEqual(mixed $fieldValue, mixed $conditionValue): bool
    {
        return is_numeric($fieldValue) && is_numeric($conditionValue) && $fieldValue >= $conditionValue;
    }

    /**
     * Compare less than or equal
     *
     * @param  mixed  $fieldValue
     * @param  mixed  $conditionValue
     * @return bool
     */
    protected function compareLessThanOrEqual(mixed $fieldValue, mixed $conditionValue): bool
    {
        return is_numeric($fieldValue) && is_numeric($conditionValue) && $fieldValue <= $conditionValue;
    }

    /**
     * Check if value is empty
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function isEmpty(mixed $value): bool
    {
        return empty($value);
    }

    /**
     * Check if field value is in condition values
     *
     * @param  mixed  $fieldValue
     * @param  mixed  $conditionValue
     * @return bool
     */
    protected function compareIn(mixed $fieldValue, mixed $conditionValue): bool
    {
        if (! is_array($conditionValue)) {
            return false;
        }

        return in_array($fieldValue, $conditionValue, true);
    }

    /**
     * Get cache key for field and model
     *
     * @param  CustomField  $field
     * @param  Model  $model
     * @return string
     */
    protected function getCacheKey(CustomField $field, Model $model): string
    {
        return sprintf(
            '%s:%s:%s:%s',
            get_class($model),
            $model->getKey(),
            $field->id,
            md5(serialize($field->visibility))
        );
    }
}