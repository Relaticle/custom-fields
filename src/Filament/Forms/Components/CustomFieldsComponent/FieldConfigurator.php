<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

use Filament\Forms\Components\Field;
use Illuminate\Support\Carbon;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\CustomField;
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
    ) {}

    /**
     * Configure a Filament form field based on a custom field definition.
     * Applies appropriate validation rules, state management, and UI settings.
     *
     * @template T of Field
     *
     * @param  Field  $field  The Filament form field to configure
     * @param  CustomField  $customField  The custom field definition
     * @return Field The configured field
     */
    public function configure(Field $field, CustomField $customField): Field
    {
        return $field
            ->label($customField->name)
            ->reactive()
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
            ->dehydrated(fn ($state): bool => $state !== null && $state !== '')
            ->required($this->validationService->isRequired($customField))
            ->rules($this->validationService->getValidationRules($customField));
    }
}
