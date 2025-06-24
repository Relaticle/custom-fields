<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\ValidationService;
use Relaticle\CustomFields\Services\VisibilityService;
use Relaticle\CustomFields\Support\FieldTypeUtils;

final readonly class FieldConfigurator
{
    /**
     * Create a new field configurator instance.
     */
    public function __construct(
        /**
         * The validation service instance.
         */
        private ValidationService $validationService,
        /**
         * The visibility service instance.
         */
        private VisibilityService $visibilityService,
    ) {}

    /**
     * Configure a Filament form field based on a custom field definition.
     * Applies appropriate validation rules, state management, and UI settings.
     *
     * @template T of Field
     *
     * @param  T  $field  The Filament form field to configure
     * @param  CustomField  $customField  The custom field definition
     * @param  array<string>  $dependentFieldCodes  Field codes that depend on this field (makes it live)
     * @return T The configured field
     */
    public function configure(Field $field, CustomField $customField, array $dependentFieldCodes = []): Field
    {
        $field = $field
            ->name('custom_fields.'.$customField->code)
            ->label($customField->name)
            ->afterStateHydrated(function ($component, $state, $record) use ($customField): void {
                // Get existing value from record or use default
                $value = $record?->getCustomFieldValue($customField);

                // If no value exists, use custom field default state or empty value based on field type
                if ($value === null) {
                    $value = $state ?? ($customField->type->hasMultipleValues() ? [] : null);
                }

                // If the field type is a date or datetime, format the value accordingly
                if ($value instanceof Carbon) {
                    $value = $value->format(
                        $customField->type === CustomFieldType::DATE
                            ? FieldTypeUtils::getDateFormat()
                            : FieldTypeUtils::getDateTimeFormat()
                    );
                }

                // Set the component state
                $component->state($value);
            })
            ->dehydrated(function ($state) use ($customField): bool {
                // Always save if configured to do so, otherwise check if state is not empty
                if ($this->visibilityService->shouldAlwaysSave($customField)) {
                    return true;
                }

                return $state !== null && $state !== '';
            })
            ->required($this->validationService->isRequired($customField))
            ->rules($this->validationService->getValidationRules($customField))
            ->columnSpan($customField->width->getSpanValue())
            ->inlineLabel(false);

        // Add conditional visibility if configured
        if ($this->hasVisibilityConditions($customField)) {
            $field = $this->addConditionalVisibility($field, $customField);
        }

        // Make field live if other fields depend on it (this ensures dependency fields trigger updates)
        if (! empty($dependentFieldCodes)) {
            $field = $field->live();
        }

        return $field;
    }

    /**
     * Check if field has visibility conditions configured.
     */
    private function hasVisibilityConditions(CustomField $customField): bool
    {
        $visibility = $customField->settings?->visibility;

        return $visibility && $visibility->requiresConditions();
    }

    /**
     * Add conditional visibility using the visibility service.
     * Leverages Filament's reactive system for natural dependency resolution.
     * Uses cascading visibility logic to properly handle parent-child dependencies.
     */
    private function addConditionalVisibility(Field $field, CustomField $customField): Field
    {
        return $field
            ->live()
            ->visible(function (Get $get) use ($customField): bool {
                return $this->evaluateFieldVisibilityWithCascading($customField, $get);
            });
    }

    /**
     * Evaluate field visibility with cascading logic using the Get closure.
     * This recursively checks parent field visibility to ensure proper hierarchical hiding.
     */
    private function evaluateFieldVisibilityWithCascading(CustomField $field, Get $get): bool
    {
        // First check if the field itself should be visible based on its conditions
        $dependentFields = $this->visibilityService->getDependentFields($field);

        // Build field values for evaluation
        $fieldValues = [];
        foreach ($dependentFields as $fieldCode) {
            $rawValue = $get('custom_fields.'.$fieldCode);
            $fieldValues[$fieldCode] = $rawValue;
        }

        // Normalize values for consistent evaluation
        $normalizedValues = $this->visibilityService->normalizeFieldValues($dependentFields, $fieldValues);

        // Check if the field itself should be visible based on its conditions
        if (! $this->visibilityService->shouldShowField($field, $normalizedValues)) {
            return false;
        }

        // If the field has no visibility conditions, it's always visible
        $visibility = $field->settings?->visibility;
        if (! $visibility || ! $visibility->requiresConditions()) {
            return true;
        }

        // Check if all parent fields that this field depends on are visible (cascading logic)
        foreach ($dependentFields as $dependentFieldCode) {
            // Dynamically fetch the parent field definition
            $parentField = CustomField::withoutGlobalScopes()
                ->where('code', $dependentFieldCode)
                ->where('entity_type', $field->entity_type)
                ->first();

            if (! $parentField) {
                continue; // Skip if the parent field doesn't exist
            }

            // Recursively check if the parent field is visible
            if (! $this->evaluateFieldVisibilityWithCascading($parentField, $get)) {
                return false; // Hide this field if any parent is hidden
            }
        }

        return true;
    }
}
