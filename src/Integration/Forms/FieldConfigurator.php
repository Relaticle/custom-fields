<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;
use Relaticle\CustomFields\CustomFields;
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
     *
     * @param  Field  $field  The Filament form field to configure
     * @param  CustomField  $customField  The custom field definition
     * @param  array<string>  $dependentFieldCodes  Field codes that depend on this field (makes it live)
     */
    public function configure(Field $field, CustomField $customField, array $dependentFieldCodes = []): Field
    {
        $field = $field
            ->name('custom_fields.'.$customField->code)
            ->label($customField->name)
            ->afterStateHydrated(function ($component, $state, $record) use ($customField): void {
                $value = $record?->getCustomFieldValue($customField)
                    ?? $state
                    ?? ($customField->type->hasMultipleValues() ? [] : null);

                if ($value instanceof Carbon) {
                    $value = $value->format(
                        $customField->type === CustomFieldType::DATE
                            ? FieldTypeUtils::getDateFormat()
                            : FieldTypeUtils::getDateTimeFormat()
                    );
                }

                $component->state($value);
            })
            ->dehydrated(fn ($state) => $this->visibilityService->shouldAlwaysSave($customField)
                || ($state !== null && $state !== ''))
            ->required($this->validationService->isRequired($customField))
            ->rules($this->validationService->getValidationRules($customField))
            ->columnSpan($customField->width->getSpanValue())
            ->inlineLabel(false);

        if ($this->hasVisibilityConditions($customField)) {
            $field = $this->addConditionalVisibility($field, $customField);
        }

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
        return $customField->settings?->visibility?->requiresConditions() ?? false;
    }

    /**
     * Add conditional visibility using the visibility service.
     */
    private function addConditionalVisibility(Field $field, CustomField $customField): Field
    {
        return $field
            ->live()
            ->visible(fn (Get $get) => $this->evaluateFieldVisibility($customField, $get));
    }

    /**
     * Evaluate field visibility with cascading logic using the visibility service.
     */
    private function evaluateFieldVisibility(CustomField $field, Get $get): bool
    {
        // Get all fields for the same entity to support cascading visibility
        $allFields = CustomFields::customFieldModel()::query()
            ->where('entity_type', $field->entity_type)
            ->get()
            ->keyBy('code');

        // Get all field codes that might be needed for visibility evaluation
        $allFieldCodes = $allFields->keys()->all();

        // Build field values for all fields
        $fieldValues = collect($allFieldCodes)
            ->mapWithKeys(fn ($fieldCode) => [$fieldCode => $get('custom_fields.'.$fieldCode)])
            ->all();

        // Normalize values for consistent evaluation
        $normalizedValues = $this->visibilityService->normalizeFieldValues($allFieldCodes, $fieldValues);

        // Use the cascading visibility method from the service
        return $this->visibilityService->shouldShowFieldWithCascading($field, $normalizedValues, $allFields);
    }
}
