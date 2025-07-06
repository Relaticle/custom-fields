<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Builders\Concerns;

use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;

/**
 * ABOUTME: Trait providing common component building functionality
 * ABOUTME: Shared logic for filtering fields and building component collections
 */
trait BuildsComponents
{
    /**
     * Filter fields based on the configured filters
     *
     * @param  Collection<int, CustomField>  $fields
     * @return Collection<int, CustomField>
     */
    protected function filterFields(Collection $fields): Collection
    {
        return $fields->filter(function (CustomField $field) {
            return $this->shouldIncludeField($field->code);
        });
    }

    /**
     * Check if a section has any visible fields after filtering
     *
     * @param  CustomFieldSection  $section
     * @return bool
     */
    protected function hasVisibleFields(CustomFieldSection $section): bool
    {
        if (! $section->relationLoaded('fields')) {
            return true; // Assume it has fields if not loaded
        }

        $visibleFields = $this->filterFields($section->fields);

        return $visibleFields->isNotEmpty();
    }

    /**
     * Build fields for a specific section
     *
     * @param  CustomFieldSection  $section
     * @return array
     */
    protected function buildFieldsForSection(CustomFieldSection $section): array
    {
        if (! $section->relationLoaded('fields')) {
            return [];
        }

        $fields = $this->filterFields($section->fields);

        return $fields->map(function (CustomField $field) {
            return $this->createComponentForField($field);
        })->filter()->values()->toArray();
    }

    /**
     * Create a component for a custom field
     * This method should be implemented by the concrete builder
     *
     * @param  CustomField  $field
     * @return mixed
     */
    abstract protected function createComponentForField(CustomField $field): mixed;

    /**
     * Get dependent field codes for visibility configuration
     *
     * @param  CustomField  $field
     * @param  Collection<int, CustomField>  $allFields
     * @return array<string>
     */
    protected function getDependentFieldCodes(CustomField $field, Collection $allFields): array
    {
        $visibilityRules = $field->visibility_rules ?? [];
        $dependentCodes = [];

        foreach ($visibilityRules as $rule) {
            if (isset($rule['field_code']) && is_string($rule['field_code'])) {
                $dependentCodes[] = $rule['field_code'];
            }
        }

        // Only return codes that exist in the current field set
        return array_values(array_intersect(
            $dependentCodes,
            $allFields->pluck('code')->toArray()
        ));
    }

    /**
     * Sort components based on their order
     *
     * @param  Collection  $components
     * @return Collection
     */
    protected function sortComponents(Collection $components): Collection
    {
        return $components->sortBy('sort_order')->values();
    }
}