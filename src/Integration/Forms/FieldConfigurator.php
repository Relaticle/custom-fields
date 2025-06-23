<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;
use Relaticle\CustomFields\Data\CustomFieldConditionsData;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\ConditionalVisibilityService;
use Relaticle\CustomFields\Services\ValidationService;
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
         * The conditional visibility service instance.
         */
        private ConditionalVisibilityService $conditionalVisibilityService,
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
                if ($this->conditionalVisibilityService->shouldAlwaysSave($customField)) {
                    return true;
                }

                return $state !== null && $state !== '';
            })
            ->required($this->validationService->isRequired($customField))
            ->rules($this->validationService->getValidationRules($customField))
            ->columnSpan($customField->width->getSpanValue())
            ->inlineLabel(false);

        // Add conditional visibility if configured
        $conditionalVisibility = $customField->settings?->conditional_visibility;
        if ($conditionalVisibility && $conditionalVisibility->requiresConditions()) {
            $field = $this->addConditionalVisibility($field, $conditionalVisibility);
        }

        // Make field live if other fields depend on it (this ensures dependency fields trigger updates)
        if (! empty($dependentFieldCodes)) {
            $field = $field->live();
        }

        return $field;
    }

    /**
     * Add conditional visibility using the centralized service.
     * Leverages Filament's reactive system for natural dependency resolution.
     */
    private function addConditionalVisibility(Field $field, CustomFieldConditionsData $conditionalVisibility): Field
    {
        return $field
            ->live()
            ->visible(function (Get $get) use ($conditionalVisibility): bool {
                // Build field values for evaluation
                $fieldValues = [];

                foreach ($conditionalVisibility->conditions ?? [] as $condition) {
                    $fieldCode = $condition['field'] ?? null;

                    if (empty($fieldCode)) {
                        continue;
                    }

                    $rawValue = $get('custom_fields.'.$fieldCode);
                    $fieldValues[$fieldCode] = $this->conditionalVisibilityService->normalizeFieldValue($fieldCode, $rawValue);
                }

                return $conditionalVisibility->evaluate($fieldValues);
            });
    }
}
