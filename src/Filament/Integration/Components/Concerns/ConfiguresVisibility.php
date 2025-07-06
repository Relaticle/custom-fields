<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Concerns;

use Closure;
use Filament\Forms\Components\Component;
use Filament\Forms\Get;
use Relaticle\CustomFields\Models\CustomField;

/**
 * ABOUTME: Trait for configuring conditional visibility of components
 * ABOUTME: Handles field dependencies and visibility rules based on other field values
 */
trait ConfiguresVisibility
{
    /**
     * Configure visibility rules for a component based on field dependencies
     *
     * @param  Component  $component
     * @param  CustomField  $field
     * @param  array<string>  $dependentFieldCodes
     * @return Component
     */
    protected function configureVisibility(Component $component, CustomField $field, array $dependentFieldCodes = []): Component
    {
        // Get visibility configuration
        $visibilityRules = $field->visibility_rules ?? [];

        if (empty($visibilityRules) || empty($dependentFieldCodes)) {
            return $component;
        }

        // Apply visibility rules
        $component->visible(function (Get $get) use ($visibilityRules, $dependentFieldCodes): bool {
            return $this->evaluateVisibilityRules($get, $visibilityRules, $dependentFieldCodes);
        });

        // Apply reactive behavior to dependent fields
        foreach ($dependentFieldCodes as $fieldCode) {
            $component->reactive();
        }

        return $component;
    }

    /**
     * Evaluate visibility rules to determine if component should be visible
     *
     * @param  Get  $get
     * @param  array  $visibilityRules
     * @param  array<string>  $dependentFieldCodes
     * @return bool
     */
    protected function evaluateVisibilityRules(Get $get, array $visibilityRules, array $dependentFieldCodes): bool
    {
        // Default to AND logic between rules
        $logic = $visibilityRules['logic'] ?? 'and';
        $conditions = $visibilityRules['conditions'] ?? [];

        if (empty($conditions)) {
            return true;
        }

        $results = [];

        foreach ($conditions as $condition) {
            $fieldCode = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;

            if (! $fieldCode || ! in_array($fieldCode, $dependentFieldCodes)) {
                continue;
            }

            // Get the current value of the dependent field
            $currentValue = $get("custom_fields.{$fieldCode}");

            // Evaluate the condition
            $results[] = $this->evaluateCondition($currentValue, $operator, $value);
        }

        // If no valid conditions, show by default
        if (empty($results)) {
            return true;
        }

        // Apply logic operator
        return $logic === 'and' 
            ? ! in_array(false, $results, true)
            : in_array(true, $results, true);
    }

    /**
     * Evaluate a single visibility condition
     *
     * @param  mixed  $currentValue
     * @param  string  $operator
     * @param  mixed  $expectedValue
     * @return bool
     */
    protected function evaluateCondition(mixed $currentValue, string $operator, mixed $expectedValue): bool
    {
        return match ($operator) {
            'equals' => $this->compareEquals($currentValue, $expectedValue),
            'not_equals' => ! $this->compareEquals($currentValue, $expectedValue),
            'contains' => $this->compareContains($currentValue, $expectedValue),
            'not_contains' => ! $this->compareContains($currentValue, $expectedValue),
            'greater_than' => $this->compareGreaterThan($currentValue, $expectedValue),
            'less_than' => $this->compareLessThan($currentValue, $expectedValue),
            'greater_than_or_equal' => $this->compareGreaterThanOrEqual($currentValue, $expectedValue),
            'less_than_or_equal' => $this->compareLessThanOrEqual($currentValue, $expectedValue),
            'empty' => $this->isEmpty($currentValue),
            'not_empty' => ! $this->isEmpty($currentValue),
            'in' => $this->compareIn($currentValue, $expectedValue),
            'not_in' => ! $this->compareIn($currentValue, $expectedValue),
            default => true,
        };
    }

    /**
     * Compare values for equality
     *
     * @param  mixed  $current
     * @param  mixed  $expected
     * @return bool
     */
    protected function compareEquals(mixed $current, mixed $expected): bool
    {
        // Handle array comparison
        if (is_array($current) && is_array($expected)) {
            return count($current) === count($expected) && 
                   array_diff($current, $expected) === [] && 
                   array_diff($expected, $current) === [];
        }

        // Handle boolean comparison
        if (is_bool($expected)) {
            return filter_var($current, FILTER_VALIDATE_BOOLEAN) === $expected;
        }

        // Standard comparison
        return $current == $expected;
    }

    /**
     * Check if current value contains expected value
     *
     * @param  mixed  $current
     * @param  mixed  $expected
     * @return bool
     */
    protected function compareContains(mixed $current, mixed $expected): bool
    {
        if (is_array($current)) {
            return in_array($expected, $current);
        }

        if (is_string($current) && is_string($expected)) {
            return str_contains($current, $expected);
        }

        return false;
    }

    /**
     * Compare numeric values for greater than
     *
     * @param  mixed  $current
     * @param  mixed  $expected
     * @return bool
     */
    protected function compareGreaterThan(mixed $current, mixed $expected): bool
    {
        if (! is_numeric($current) || ! is_numeric($expected)) {
            return false;
        }

        return (float) $current > (float) $expected;
    }

    /**
     * Compare numeric values for less than
     *
     * @param  mixed  $current
     * @param  mixed  $expected
     * @return bool
     */
    protected function compareLessThan(mixed $current, mixed $expected): bool
    {
        if (! is_numeric($current) || ! is_numeric($expected)) {
            return false;
        }

        return (float) $current < (float) $expected;
    }

    /**
     * Compare numeric values for greater than or equal
     *
     * @param  mixed  $current
     * @param  mixed  $expected
     * @return bool
     */
    protected function compareGreaterThanOrEqual(mixed $current, mixed $expected): bool
    {
        if (! is_numeric($current) || ! is_numeric($expected)) {
            return false;
        }

        return (float) $current >= (float) $expected;
    }

    /**
     * Compare numeric values for less than or equal
     *
     * @param  mixed  $current
     * @param  mixed  $expected
     * @return bool
     */
    protected function compareLessThanOrEqual(mixed $current, mixed $expected): bool
    {
        if (! is_numeric($current) || ! is_numeric($expected)) {
            return false;
        }

        return (float) $current <= (float) $expected;
    }

    /**
     * Check if value is empty
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function isEmpty(mixed $value): bool
    {
        if (is_array($value)) {
            return count($value) === 0;
        }

        return empty($value);
    }

    /**
     * Check if current value is in expected array
     *
     * @param  mixed  $current
     * @param  mixed  $expected
     * @return bool
     */
    protected function compareIn(mixed $current, mixed $expected): bool
    {
        if (! is_array($expected)) {
            return false;
        }

        return in_array($current, $expected);
    }

    /**
     * Create a visibility closure for table columns and infolist entries
     *
     * @param  array  $visibilityRules
     * @param  array<string>  $dependentFieldCodes
     * @return Closure
     */
    protected function createVisibilityClosure(array $visibilityRules, array $dependentFieldCodes): Closure
    {
        return function ($record) use ($visibilityRules, $dependentFieldCodes): bool {
            if (empty($visibilityRules) || empty($dependentFieldCodes)) {
                return true;
            }

            // Create a mock Get function that retrieves values from the record
            $get = function (string $path) use ($record) {
                // Remove 'custom_fields.' prefix if present
                $fieldCode = str_replace('custom_fields.', '', $path);
                
                // Get custom field value from record
                if (method_exists($record, 'getCustomFieldValue')) {
                    return $record->getCustomFieldValue($fieldCode);
                }

                return null;
            };

            return $this->evaluateVisibilityRules($get, $visibilityRules, $dependentFieldCodes);
        };
    }
}