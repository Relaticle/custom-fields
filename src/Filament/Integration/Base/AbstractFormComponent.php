<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Base;

use Filament\Forms\Components\Field;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Contracts\FormComponentInterface;
use Relaticle\CustomFields\Filament\Integration\Concerns\Forms\ConfiguresFieldName;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\ValidationService;
use Relaticle\CustomFields\Services\Visibility\CoreVisibilityLogicService;
use Relaticle\CustomFields\Services\Visibility\FrontendVisibilityService;
use Relaticle\CustomFields\Support\FieldTypeUtils;
use Relaticle\CustomFields\Support\Utils;

/**
 * Abstract base class for form field components.
 *
 * Eliminates duplication across 18+ component classes by providing
 * common structure and delegating to FieldConfigurator for shared logic.
 *
 * Each concrete component only needs to implement createField() to specify
 * the Filament component type and its basic configuration.
 */
abstract readonly class AbstractFormComponent implements FormComponentInterface
{
    use ConfiguresFieldName;

    public function __construct(
        protected ValidationService $validationService,
        protected CoreVisibilityLogicService $coreVisibilityLogic,
        protected FrontendVisibilityService $frontendVisibilityService
    ) {}

    /**
     * Create and configure a field component.
     *
     * @param  array<string>  $dependentFieldCodes
     * @param  Collection<int, CustomField>|null  $allFields
     */
    public function make(CustomField $customField, array $dependentFieldCodes = [], ?Collection $allFields = null): Field
    {
        $field = $this->create($customField);
        $allFields ??= collect();

        return $this->configure($field, $customField, $allFields, $dependentFieldCodes);
    }

    protected function configure(
        Field $field,
        CustomField $customField,
        Collection $allFields,
        array $dependentFieldCodes
    ): Field {
        return $field
            ->name("custom_fields.{$customField->code}")
            ->label($customField->name)
            ->afterStateHydrated(
                fn (mixed $component, mixed $state, mixed $record): mixed => $component->state(
                    $this->getFieldValue($customField, $state, $record)
                )
            )
            ->dehydrated(
                fn (mixed $state): bool => Utils::isConditionalVisibilityFeatureEnabled() &&
                    ($this->coreVisibilityLogic->shouldAlwaysSave($customField) || filled($state))
            )
            ->required($this->validationService->isRequired($customField))
            ->rules($this->validationService->getValidationRules($customField))
            ->columnSpan($customField->width->getSpanValue())
            ->when(
                Utils::isConditionalVisibilityFeatureEnabled() &&
                $this->hasVisibilityConditions($customField),
                fn (Field $field): Field => $this->applyVisibility(
                    $field,
                    $customField,
                    $allFields
                )
            )
            ->when(
                Utils::isConditionalVisibilityFeatureEnabled() &&
                filled($dependentFieldCodes),
                fn (Field $field): Field => $field->live()
            );
    }

    private function getFieldValue(
        CustomField $customField,
        mixed $state,
        mixed $record
    ): mixed {
        return value(function () use ($customField, $state, $record) {
            $value = $record?->getCustomFieldValue($customField) ??
                ($state ?? ($customField->isMultiChoiceField() ? [] : null));

            return $value instanceof Carbon
                ? $value->format(
                    $customField->isDateField()
                        ? FieldTypeUtils::getDateFormat()
                        : FieldTypeUtils::getDateTimeFormat()
                )
                : $value;
        });
    }

    private function hasVisibilityConditions(CustomField $customField): bool
    {
        return $this->coreVisibilityLogic->hasVisibilityConditions($customField);
    }

    private function applyVisibility(
        Field $field,
        CustomField $customField,
        Collection $allFields
    ): Field {
        $jsExpression = $this->frontendVisibilityService->buildVisibilityExpression(
            $customField,
            $allFields
        );

        return $jsExpression !== null &&
        $jsExpression !== '' &&
        $jsExpression !== '0'
            ? $field->live()->visibleJs($jsExpression)
            : $field;
    }

    /**
     * Create the specific Filament field component.
     *
     * Concrete implementations should create the appropriate Filament component
     * (TextInput, Select, etc.) with field-specific configuration.
     *
     * Made public to allow composition patterns (like MultiSelectComponent).
     */
    abstract public function create(CustomField $customField): Field;
}
