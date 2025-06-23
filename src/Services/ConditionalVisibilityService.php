<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services;

use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;

final readonly class ConditionalVisibilityService
{
    /**
     * Evaluate if a field should be visible based on its conditional visibility settings.
     *
     * @param array<string, mixed> $fieldValues Current values of all fields
     */
    public function shouldShowField(CustomField $field, array $fieldValues): bool
    {
        $conditionalVisibility = $field->settings?->conditionalVisibility;

        if (!$conditionalVisibility) {
            return true;
        }

        return $conditionalVisibility->evaluate($fieldValues);
    }

    /**
     * Get all field dependencies for a custom field.
     * Returns the codes of fields that this field depends on for conditional visibility.
     *
     * @return array<string>
     */
    public function getFieldDependencies(CustomField $field): array
    {
        $conditionalVisibility = $field->settings?->conditionalVisibility;

        if (!$conditionalVisibility || !$conditionalVisibility->requiresConditions()) {
            return [];
        }

        $dependencies = [];
        foreach ($conditionalVisibility->conditions ?? [] as $condition) {
            if (isset($condition['field'])) {
                $dependencies[] = $condition['field'];
            }
        }

        return array_unique($dependencies);
    }

    /**
     * Check if a field should always save its value (regardless of visibility).
     */
    public function shouldAlwaysSave(CustomField $field): bool
    {
        return $field->settings?->conditionalVisibility?->always_save ?? false;
    }

    /**
     * Normalize field values for condition evaluation across all contexts.
     * Handles optionable fields by converting IDs to names for consistent comparisons.
     */
    public function normalizeFieldValue(string $fieldCode, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $field = $this->getFieldByCode($fieldCode);
        if (!$field?->type->isOptionable()) {
            return $value;
        }

        // Single value optionable fields
        if (!$field->type->hasMultipleValues()) {
            return is_numeric($value)
                ? $field->options()->where('id', $value)->first()?->name ?? $value
                : $value;
        }

        // Multi-value optionable fields
        if (is_array($value)) {
            return collect($value)->map(fn($id) => is_numeric($id)
                ? $field->options()->where('id', $id)->first()?->name ?? $id
                : $id
            )->all();
        }

        return $value;
    }

    /**
     * Get field by code with static caching for performance.
     */
    public function getFieldByCode(string $fieldCode): ?CustomField
    {
        static $cache = [];

        return $cache[$fieldCode] ??= CustomField::query()->where('code', $fieldCode)->first();
    }

    /**
     * Calculate field dependencies for a collection of custom fields.
     * Returns mapping of field_code => [dependent_field_codes]
     * Used across forms, tables, and infolists for consistent dependency handling.
     *
     * @param Collection<CustomField> $customFields
     * @return array<string, array<string>>
     */
    public function calculateFieldDependencies(Collection $customFields): array
    {
        $dependencies = [];

        foreach ($customFields as $field) {
            $conditionalVisibility = $field->settings?->conditionalVisibility;
            if (!$conditionalVisibility || !$conditionalVisibility->requiresConditions()) {
                continue;
            }

            foreach ($conditionalVisibility->conditions ?? [] as $condition) {
                $dependencyFieldCode = $condition['field'] ?? null;
                if (empty($dependencyFieldCode)) {
                    continue;
                }

                if (!isset($dependencies[$dependencyFieldCode])) {
                    $dependencies[$dependencyFieldCode] = [];
                }

                $dependencies[$dependencyFieldCode][] = $field->code;
            }
        }

        return $dependencies;
    }
}
