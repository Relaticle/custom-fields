<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services;

use Illuminate\Support\Collection;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Data\VisibilityData;
use Relaticle\CustomFields\Models\CustomField;

/**
 * Clean service for handling field visibility.
 *
 * Single responsibility: Determine if fields should be visible based on conditions.
 */
final readonly class VisibilityService
{
    /**
     * Check if a field should be visible based on its conditions.
     */
    public function shouldShowField(CustomField $field, array $fieldValues): bool
    {
        $visibility = $this->getVisibilityData($field);

        return $visibility?->evaluate($fieldValues) ?? true;
    }

    /**
     * Check if a field should be visible with cascading visibility logic.
     * This method considers parent field visibility to properly handle hierarchical field dependencies.
     */
    public function shouldShowFieldWithCascading(CustomField $field, array $fieldValues, Collection $allFields): bool
    {
        // First check if the field itself should be visible based on its conditions
        if (! $this->shouldShowField($field, $fieldValues)) {
            return false;
        }

        // If the field has no visibility conditions, it's always visible
        $visibility = $this->getVisibilityData($field);
        if (! $visibility || ! $visibility->requiresConditions()) {
            return true;
        }

        // Check if all parent fields that this field depends on are visible
        $dependentFields = $this->getDependentFields($field);

        foreach ($dependentFields as $dependentFieldCode) {
            $parentField = $allFields->firstWhere('code', $dependentFieldCode);

            if (! $parentField) {
                continue; // Skip if parent field doesn't exist
            }

            // Recursively check if the parent field is visible
            if (! $this->shouldShowFieldWithCascading($parentField, $fieldValues, $allFields)) {
                return false; // Hide this field if any parent is hidden
            }
        }

        return true;
    }

    /**
     * Check if field should always save its value regardless of visibility.
     */
    public function shouldAlwaysSave(CustomField $field): bool
    {
        $visibility = $this->getVisibilityData($field);

        return $visibility?->alwaysSave ?? false;
    }

    /**
     * Get all fields that this field depends on for visibility conditions.
     */
    public function getDependentFields(CustomField $field): array
    {
        $visibility = $this->getVisibilityData($field);

        return $visibility?->getDependentFields() ?? [];
    }

    /**
     * Calculate field dependencies for multiple fields efficiently.
     */
    public function calculateDependencies(Collection $allFields): array
    {
        return $allFields
            // For each field, find what other fields depend on it
            ->flatMap(function ($field) use ($allFields) {
                $dependentFieldCodes = $this->getDependentFields($field);

                return collect($dependentFieldCodes)
                    // Only include dependent fields that actually exist
                    ->filter(fn($dependentCode) => $allFields->firstWhere('code', $dependentCode))
                    // Map: dependent field => source field that it depends on
                    ->mapWithKeys(fn($dependentCode) => [$dependentCode => $field->code]);
            })
            // Group by dependent field code
            ->groupBy(fn($sourceCode, $dependentCode) => $dependentCode)
            // Convert grouped collections to arrays
            ->map(fn($sourceCodes) => $sourceCodes->values()->toArray())
            ->toArray();
    }

    /**
     * Filter visible fields from a collection based on field values.
     */
    public function filterVisibleFields(Collection $fields, array $fieldValues): Collection
    {
        return $fields->filter(fn (CustomField $field) => $this->shouldShowField($field, $fieldValues));
    }

    /**
     * Get fields that should be saved regardless of visibility.
     */
    public function getAlwaysSaveFields(Collection $fields): Collection
    {
        return $fields->filter(fn (CustomField $field) => $this->shouldAlwaysSave($field));
    }

    /**
     * Normalize field values for consistent evaluation.
     * Converts option IDs to names for proper comparison.
     */
    public function normalizeFieldValues(array $fieldCodes, array $rawValues): array
    {
        if (empty($fieldCodes)) {
            return $rawValues;
        }

        $fields = CustomFields::newCustomFieldModel()::whereIn('code', $fieldCodes)
            ->with('options')
            ->get()
            ->keyBy('code');

        $normalized = [];

        foreach ($rawValues as $fieldCode => $value) {
            $field = $fields->get($fieldCode);
            $normalized[$fieldCode] = $this->normalizeValue($field, $value);
        }

        return $normalized;
    }

    /**
     * Extract visibility data from field settings.
     */
    private function getVisibilityData(CustomField $field): ?VisibilityData
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
     * Get field options for optionable fields.
     * Efficiently loads and caches field options for visibility conditions.
     */
    public function getFieldOptions(string $fieldCode, string $entityType): array
    {
        $field = CustomFields::newCustomFieldModel()::forMorphEntity($entityType)
            ->where('code', $fieldCode)
            ->with('options')
            ->first();

        if (! $field || ! $field->type->isOptionable()) {
            return [];
        }

        /** @var CustomField $field */
        return $field->options()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'name')
            ->toArray();
    }

    /**
     * Get field metadata for visibility form enhancement.
     */
    public function getFieldMetadata(string $fieldCode, string $entityType): ?array
    {
        $field = CustomFields::newCustomFieldModel()::forMorphEntity($entityType)
            ->where('code', $fieldCode)
            ->first();

        if (! $field) {
            return null;
        }

        return [
            'type' => $field->type,
            'category' => $field->type->getCategory(),
            'is_optionable' => $field->type->isOptionable(),
            'has_multiple_values' => $field->type->hasMultipleValues(),
            'compatible_operators' => $field->type->getCompatibleOperators(),
        ];
    }

    /**
     * Normalize a single field value.
     */
    private function normalizeValue(?CustomField $field, mixed $value): mixed
    {
        if ($value === null || $value === '' || ! $field?->type->isOptionable()) {
            return $value;
        }

        // Get options for the field
        $options = $field->options()->get()->keyBy('id');

        // Single value optionable fields
        if (! $field->type->hasMultipleValues()) {
            return is_numeric($value)
                ? $options->get($value)?->name ?? $value
                : $value;
        }

        // Multi-value optionable fields
        if (is_array($value)) {
            return collect($value)->map(fn ($id) => is_numeric($id)
                ? $options->get($id)?->name ?? $id
                : $id
            )->all();
        }

        return $value;
    }
}
