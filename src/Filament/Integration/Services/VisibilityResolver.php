<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Services;

use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;

/**
 * ABOUTME: Service for resolving field visibility dependencies
 * ABOUTME: Handles conditional visibility logic based on field rules
 */
class VisibilityResolver
{
    /**
     * Get dependent field codes for a custom field
     *
     * @param  CustomField  $field
     * @return array<string>
     */
    public function getDependentFieldCodes(CustomField $field): array
    {
        $visibilityRules = $field->visibility_rules ?? [];
        $dependentCodes = [];

        foreach ($visibilityRules as $rule) {
            if (isset($rule['field_code']) && is_string($rule['field_code'])) {
                $dependentCodes[] = $rule['field_code'];
            }
        }

        return array_unique($dependentCodes);
    }

    /**
     * Check if a field has visibility dependencies
     *
     * @param  CustomField  $field
     * @return bool
     */
    public function hasVisibilityDependencies(CustomField $field): bool
    {
        return ! empty($field->visibility_rules);
    }

    /**
     * Get all fields that depend on a specific field
     *
     * @param  string  $fieldCode
     * @param  Collection<int, CustomField>  $allFields
     * @return Collection<int, CustomField>
     */
    public function getDependentFields(string $fieldCode, Collection $allFields): Collection
    {
        return $allFields->filter(function (CustomField $field) use ($fieldCode) {
            $dependentCodes = $this->getDependentFieldCodes($field);
            
            return in_array($fieldCode, $dependentCodes, true);
        });
    }

    /**
     * Sort fields based on their dependencies
     * Fields with no dependencies come first
     *
     * @param  Collection<int, CustomField>  $fields
     * @return Collection<int, CustomField>
     */
    public function sortByDependencies(Collection $fields): Collection
    {
        // Create a map of field codes to fields
        $fieldMap = $fields->keyBy('code');
        $sorted = collect();
        $visited = [];

        // Topological sort using depth-first search
        foreach ($fields as $field) {
            if (! isset($visited[$field->code])) {
                $this->visitField($field, $fieldMap, $sorted, $visited);
            }
        }

        return $sorted;
    }

    /**
     * Visit a field for topological sorting
     *
     * @param  CustomField  $field
     * @param  Collection  $fieldMap
     * @param  Collection  $sorted
     * @param  array  $visited
     * @return void
     */
    private function visitField(CustomField $field, Collection $fieldMap, Collection $sorted, array &$visited): void
    {
        $visited[$field->code] = true;

        // Visit dependencies first
        $dependentCodes = $this->getDependentFieldCodes($field);
        foreach ($dependentCodes as $code) {
            if ($fieldMap->has($code) && ! isset($visited[$code])) {
                $this->visitField($fieldMap->get($code), $fieldMap, $sorted, $visited);
            }
        }

        // Add the field after its dependencies
        $sorted->push($field);
    }

    /**
     * Build visibility configuration for Filament components
     *
     * @param  CustomField  $field
     * @return array
     */
    public function buildVisibilityConfiguration(CustomField $field): array
    {
        $rules = $field->visibility_rules ?? [];
        $config = [];

        foreach ($rules as $rule) {
            if (! isset($rule['field_code']) || ! isset($rule['operator']) || ! isset($rule['value'])) {
                continue;
            }

            $fieldCode = $rule['field_code'];
            $operator = $rule['operator'];
            $value = $rule['value'];

            // Build Filament visibility condition
            $config[] = [
                'field' => "custom_fields.{$fieldCode}",
                'operator' => $this->mapOperatorToFilament($operator),
                'value' => $value,
            ];
        }

        return $config;
    }

    /**
     * Map custom field operators to Filament operators
     *
     * @param  string  $operator
     * @return string
     */
    private function mapOperatorToFilament(string $operator): string
    {
        return match ($operator) {
            'equals', '=' => 'is',
            'not_equals', '!=' => 'isNot',
            'greater_than', '>' => 'gt',
            'less_than', '<' => 'lt',
            'greater_equal', '>=' => 'gte',
            'less_equal', '<=' => 'lte',
            'contains' => 'contains',
            'not_contains' => 'doesNotContain',
            'starts_with' => 'startsWith',
            'ends_with' => 'endsWith',
            'is_empty' => 'isEmpty',
            'is_not_empty' => 'isNotEmpty',
            default => 'is',
        };
    }
}