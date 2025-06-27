<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services\Visibility;

use Illuminate\Support\Collection;
use Relaticle\CustomFields\Data\VisibilityConditionData;
use Relaticle\CustomFields\Data\VisibilityData;
use Relaticle\CustomFields\Enums\Logic;
use Relaticle\CustomFields\Enums\Mode;
use Relaticle\CustomFields\Enums\Operator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\FieldTypeHelperService;

/**
 * Core Visibility Logic Service - Single Source of Truth
 *
 * This service contains the pure logic for visibility evaluation that is used
 * by both backend (PHP) and frontend (JavaScript) implementations.
 *
 * CRITICAL: This is the single source of truth for ALL visibility logic.
 * Any changes to visibility rules MUST be made here to ensure consistency
 * between frontend and backend implementations.
 */
final readonly class CoreVisibilityLogicService
{
    public function __construct(
        private FieldTypeHelperService $fieldTypeHelper,
    ) {}
    /**
     * Extract visibility data from a custom field.
     * This is the authoritative method for getting visibility configuration.
     */
    public function getVisibilityData(CustomField $field): ?VisibilityData
    {
        $settings = $field->settings;

        if (! $settings) {
            return null;
        }

        // Handle array settings (from tests or JSON)
        if (is_array($settings) && isset($settings['visibility'])) {
            return VisibilityData::from($settings['visibility']);
        }

        // Handle object settings
        if (is_object($settings) && isset($settings->visibility)) {
            return $settings->visibility instanceof VisibilityData
                ? $settings->visibility
                : null;
        }

        return null;
    }

    /**
     * Determine if a field has visibility conditions.
     * Single source of truth for visibility requirement checking.
     */
    public function hasVisibilityConditions(CustomField $field): bool
    {
        $visibility = $this->getVisibilityData($field);

        return $visibility?->requiresConditions() ?? false;
    }

    /**
     * Get dependent field codes for a given field.
     * This determines which fields this field depends on for visibility.
     */
    public function getDependentFields(CustomField $field): array
    {
        $visibility = $this->getVisibilityData($field);

        return $visibility?->getDependentFields() ?? [];
    }

    /**
     * Evaluate whether a field should be visible based on field values.
     * This is the core evaluation logic used by backend implementations.
     */
    public function evaluateVisibility(CustomField $field, array $fieldValues): bool
    {
        $visibility = $this->getVisibilityData($field);

        return $visibility?->evaluate($fieldValues) ?? true;
    }

    /**
     * Evaluate visibility with cascading logic.
     * Considers parent field visibility for hierarchical dependencies.
     */
    public function evaluateVisibilityWithCascading(CustomField $field, array $fieldValues, Collection $allFields): bool
    {
        // First check if the field itself should be visible
        if (! $this->evaluateVisibility($field, $fieldValues)) {
            return false;
        }

        // If field has no visibility conditions, it's always visible
        if (! $this->hasVisibilityConditions($field)) {
            return true;
        }

        // Check if all parent fields are visible (cascading)
        $dependentFields = $this->getDependentFields($field);

        foreach ($dependentFields as $dependentFieldCode) {
            $parentField = $allFields->firstWhere('code', $dependentFieldCode);

            if (! $parentField) {
                continue; // Skip if parent field doesn't exist
            }

            // Recursively check parent visibility
            if (! $this->evaluateVisibilityWithCascading($parentField, $fieldValues, $allFields)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get visibility mode for a field.
     * Returns the mode (always_visible, show_when, hide_when).
     */
    public function getVisibilityMode(CustomField $field): Mode
    {
        $visibility = $this->getVisibilityData($field);

        return $visibility?->mode ?? Mode::ALWAYS_VISIBLE;
    }

    /**
     * Get visibility logic for a field.
     * Returns the logic (all, any) for multiple conditions.
     */
    public function getVisibilityLogic(CustomField $field): Logic
    {
        $visibility = $this->getVisibilityData($field);

        return $visibility?->logic ?? Logic::ALL;
    }

    /**
     * Get visibility conditions for a field.
     * Returns the array of conditions that control visibility.
     */
    public function getVisibilityConditions(CustomField $field): array
    {
        $visibility = $this->getVisibilityData($field);

        if (! $visibility || ! $visibility->conditions) {
            return [];
        }

        return $visibility->conditions->toArray();
    }

    /**
     * Check if field should always save regardless of visibility.
     */
    public function shouldAlwaysSave(CustomField $field): bool
    {
        $visibility = $this->getVisibilityData($field);

        return $visibility?->alwaysSave ?? false;
    }

    /**
     * Calculate field dependencies for all fields.
     * Returns mapping of source field codes to their dependent field codes.
     */
    public function calculateDependencies(Collection $allFields): array
    {
        $dependencies = [];

        foreach ($allFields as $field) {
            $dependentFieldCodes = $this->getDependentFields($field);

            foreach ($dependentFieldCodes as $dependentCode) {
                // Check if the dependent field exists in our collection
                if ($allFields->firstWhere('code', $dependentCode)) {
                    // Map: source field code -> array of fields that depend on it
                    if (! isset($dependencies[$dependentCode])) {
                        $dependencies[$dependentCode] = [];
                    }
                    $dependencies[$dependentCode][] = $field->code;
                }
            }
        }

        return $dependencies;
    }

    /**
     * Validate that operator is compatible with field type.
     * Ensures operators are used appropriately for different field types.
     */
    public function isOperatorCompatible(Operator $operator, CustomField $field): bool
    {
        $compatibleOperators = $field->getFieldTypeCompatibleOperators();

        return in_array($operator, $compatibleOperators, true);
    }

    /**
     * Get metadata for a field that's needed for visibility evaluation.
     * This is used by frontend services to build JavaScript expressions.
     */
    public function getFieldMetadata(CustomField $field): array
    {
        return [
            'code' => $field->code,
            'type' => $field->getFieldTypeValue(),
            'category' => $field->getFieldTypeCategory(),
            'is_optionable' => $field->isFieldTypeOptionable(),
            'has_multiple_values' => $field->hasFieldTypeMultipleValues(),
            'compatible_operators' => array_map(fn ($op) => $op->value, $field->getFieldTypeCompatibleOperators()),
            'has_visibility_conditions' => $this->hasVisibilityConditions($field),
            'visibility_mode' => $this->getVisibilityMode($field)->value,
            'visibility_logic' => $this->getVisibilityLogic($field)->value,
            'visibility_conditions' => $this->getVisibilityConditions($field),
            'dependent_fields' => $this->getDependentFields($field),
            'always_save' => $this->shouldAlwaysSave($field),
        ];
    }

    /**
     * Normalize a single condition for consistent evaluation.
     * Ensures conditions are in the expected format across contexts.
     */
    public function normalizeCondition(VisibilityConditionData $condition): array
    {
        return [
            'field_code' => $condition->field_code,
            'operator' => $condition->operator->value,
            'value' => $condition->value,
        ];
    }

    /**
     * Check if a condition requires the target field to be optionable.
     * Used to validate condition setup and provide appropriate error messages.
     */
    public function conditionRequiresOptionableField(Operator $operator): bool
    {
        return in_array($operator, [
            Operator::EQUALS,
            Operator::NOT_EQUALS,
            Operator::CONTAINS,
            Operator::NOT_CONTAINS,
        ], true);
    }

    /**
     * Get the appropriate error message for invalid operator/field combinations.
     */
    public function getOperatorValidationError(Operator $operator, CustomField $field): ?string
    {
        if (! $this->isOperatorCompatible($operator, $field)) {
            return "Operator '{$operator->value}' is not compatible with field type '{$field->type->value}'";
        }

        if ($this->conditionRequiresOptionableField($operator) && ! $this->fieldTypeHelper->isOptionable($field->type)) {
            return "Operator '{$operator->value}' can only be used with optionable fields (select, radio, etc.)";
        }

        return null;
    }

    /**
     * Filter visible fields from a collection based on field values.
     * Uses core evaluation logic without cascading.
     */
    public function filterVisibleFields(Collection $fields, array $fieldValues): Collection
    {
        return $fields->filter(fn (CustomField $field) => $this->evaluateVisibility($field, $fieldValues));
    }

    /**
     * Get fields that should be saved regardless of visibility.
     */
    public function getAlwaysSaveFields(Collection $fields): Collection
    {
        return $fields->filter(fn (CustomField $field) => $this->shouldAlwaysSave($field));
    }

    /**
     * Legacy method aliases for backward compatibility.
     * These delegates to the main methods with consistent naming.
     */
    public function shouldShowField(CustomField $field, array $fieldValues): bool
    {
        return $this->evaluateVisibility($field, $fieldValues);
    }

    public function shouldShowFieldWithCascading(CustomField $field, array $fieldValues, Collection $allFields): bool
    {
        return $this->evaluateVisibilityWithCascading($field, $fieldValues, $allFields);
    }
}
