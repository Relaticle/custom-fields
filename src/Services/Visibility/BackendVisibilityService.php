<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services\Visibility;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\FieldTypeHelperService;

/**
 * Backend Visibility Service
 *
 * Handles server-side visibility evaluation using the CoreVisibilityLogicService.
 * Used by infolists, exports, and other backend components that need to
 * determine field visibility.
 *
 * This service provides PHP-based evaluation of visibility conditions.
 */
final readonly class BackendVisibilityService
{
    public function __construct(
        private CoreVisibilityLogicService $coreLogic,
        private FieldTypeHelperService $fieldTypeHelper,
    ) {}

    /**
     * Extract field values from a record for visibility evaluation.
     */
    public function extractFieldValues(Model $record, Collection $fields): array
    {
        if (! $record instanceof HasCustomFields) {
            return [];
        }

        // Ensure custom field values are loaded
        if (! $record->relationLoaded('customFieldValues')) {
            $record->load('customFieldValues.customField');
        }

        $fieldValues = [];

        foreach ($fields as $field) {
            $rawValue = $record->getCustomFieldValue($field);
            $fieldValues[$field->code] = $this->normalizeValueForEvaluation($rawValue, $field);
        }

        return $fieldValues;
    }

    /**
     * Check if a field should be visible for the given record.
     */
    public function isFieldVisible(Model $record, CustomField $field, Collection $allFields): bool
    {
        $fieldValues = $this->extractFieldValues($record, $allFields);

        return $this->coreLogic->evaluateVisibilityWithCascading($field, $fieldValues, $allFields);
    }

    /**
     * Filter fields to only those that should be visible for the given record.
     */
    public function getVisibleFields(Model $record, Collection $fields): Collection
    {
        $fieldValues = $this->extractFieldValues($record, $fields);

        return $fields->filter(fn(CustomField $field): bool => $this->coreLogic->evaluateVisibilityWithCascading($field, $fieldValues, $fields));
    }

    /**
     * Get field values normalized for visibility evaluation.
     */
    public function getNormalizedFieldValues(Model $record, Collection $fields): array
    {
        $rawValues = $this->extractFieldValues($record, $fields);
        $fieldCodes = $fields->pluck('code')->toArray();

        return $this->normalizeFieldValues($fieldCodes, $rawValues);
    }

    /**
     * Normalize field values for consistent evaluation.
     * Converts option IDs to names and handles different data types.
     */
    public function normalizeFieldValues(array $fieldCodes, array $rawValues): array
    {
        if ($fieldCodes === []) {
            return $rawValues;
        }

        $fields = CustomFields::newCustomFieldModel()::whereIn('code', $fieldCodes)
            ->with('options')
            ->get()
            ->keyBy('code');

        $normalized = [];

        foreach ($rawValues as $fieldCode => $value) {
            $field = $fields->get($fieldCode);
            $normalized[$fieldCode] = $this->normalizeValueForEvaluation($value, $field);
        }

        return $normalized;
    }

    /**
     * Normalize a single field value for visibility evaluation.
     */
    private function normalizeValueForEvaluation(mixed $value, ?CustomField $field): mixed
    {
        if ($value === null || $value === '' || ! $this->fieldTypeHelper->isOptionable($field->type ?? '')) {
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

    /**
     * Validate that field visibility evaluation is working correctly.
     */
    public function validateVisibilityConsistency(Model $record, Collection $fields): array
    {
        $fieldValues = $this->extractFieldValues($record, $fields);
        $normalizedValues = $this->getNormalizedFieldValues($record, $fields);
        $visibleFields = $this->getVisibleFields($record, $fields);

        return [
            'total_fields' => $fields->count(),
            'visible_fields' => $visibleFields->count(),
            'hidden_fields' => $fields->count() - $visibleFields->count(),
            'field_values_extracted' => count($fieldValues),
            'normalized_values' => count($normalizedValues),
            'has_visibility_conditions' => $fields->filter(fn ($f): bool => $this->coreLogic->hasVisibilityConditions($f))->count(),
            'visible_field_codes' => $visibleFields->pluck('code')->toArray(),
            'dependencies' => $this->coreLogic->calculateDependencies($fields),
        ];
    }

    /**
     * Get fields that should be saved regardless of visibility.
     */
    public function getAlwaysSaveFields(Collection $fields): Collection
    {
        return $fields->filter(fn (CustomField $field): bool => $this->coreLogic->shouldAlwaysSave($field));
    }

    /**
     * Filter visible fields from a collection based on field values.
     */
    public function filterVisibleFields(Collection $fields, array $fieldValues): Collection
    {
        return $fields->filter(fn (CustomField $field): bool => $this->coreLogic->evaluateVisibility($field, $fieldValues));
    }

    /**
     * Get field dependencies for multiple fields efficiently.
     */
    public function calculateDependencies(Collection $allFields): array
    {
        return $this->coreLogic->calculateDependencies($allFields);
    }

    /**
     * Get field options for optionable fields.
     */
    public function getFieldOptions(string $fieldCode, string $entityType): array
    {
        $field = CustomFields::newCustomFieldModel()::forMorphEntity($entityType)
            ->where('code', $fieldCode)
            ->with('options')
            ->first();

        if (! $field || ! $this->fieldTypeHelper->isOptionable($field->type)) {
            return [];
        }

        return $field->options()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'name')
            ->toArray();
    }

    /**
     * Get field metadata for visibility evaluation.
     */
    public function getFieldMetadata(string $fieldCode, string $entityType): ?array
    {
        $field = CustomFields::newCustomFieldModel()::forMorphEntity($entityType)
            ->where('code', $fieldCode)
            ->first();

        if (! $field) {
            return null;
        }

        return $this->coreLogic->getFieldMetadata($field);
    }
}
