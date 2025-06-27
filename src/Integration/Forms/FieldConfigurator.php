<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms;

use Filament\Forms\Components\Field;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\ValidationService;
use Relaticle\CustomFields\Services\Visibility\CoreVisibilityLogicService;
use Relaticle\CustomFields\Services\Visibility\FrontendVisibilityService;
use Relaticle\CustomFields\Support\FieldTypeUtils;
use Relaticle\CustomFields\Support\Utils;

final readonly class FieldConfigurator
{
    public function __construct(
        private ValidationService          $validationService,
        private CoreVisibilityLogicService $coreVisibilityLogic,
        private FrontendVisibilityService  $frontendVisibilityService
    )
    {
    }

    /**
     * @param Collection<int, CustomField> $allFields
     * @param array<string> $dependentFieldCodes
     */
    public function configure(
        Field       $field,
        CustomField $customField,
        Collection  $allFields,
        array       $dependentFieldCodes
    ): Field
    {
        return $field
            ->name("custom_fields.{$customField->code}")
            ->label($customField->name)
            ->afterStateHydrated(
                fn($component, $state, $record) => $component->state(
                    $this->getFieldValue($customField, $state, $record)
                )
            )
            ->dehydrated(
                fn(
                    $state
                ): bool => Utils::isConditionalVisibilityFeatureEnabled() &&
                    ($this->coreVisibilityLogic->shouldAlwaysSave(
                            $customField
                        ) ||
                        filled($state))
            )
            ->required($this->validationService->isRequired($customField))
            ->rules($this->validationService->getValidationRules($customField))
            ->columnSpan($customField->width->getSpanValue())
            ->when(
                Utils::isConditionalVisibilityFeatureEnabled() &&
                $this->hasVisibilityConditions($customField),
                fn(Field $field): Field => $this->applyVisibility(
                    $field,
                    $customField,
                    $allFields
                )
            )
            ->when(
                Utils::isConditionalVisibilityFeatureEnabled() &&
                filled($dependentFieldCodes),
                fn(Field $field): Field => $field->live()
            );
    }

    private function getFieldValue(
        CustomField $customField,
        mixed       $state,
        mixed       $record
    ): mixed
    {
        return value(function () use ($customField, $state, $record) {
            $value =
                $record?->getCustomFieldValue($customField) ??
                ($state ??
                    ($customField->hasFieldTypeMultipleValues() ? [] : null));

            return $value instanceof Carbon
                ? $value->format(
                    $customField->getFieldTypeValue() ===
                    CustomFieldType::DATE->value
                        ? FieldTypeUtils::getDateFormat()
                        : FieldTypeUtils::getDateTimeFormat()
                )
                : $value;
        });
    }

    private function hasVisibilityConditions(CustomField $customField): bool
    {
        return $this->coreVisibilityLogic->hasVisibilityConditions(
            $customField
        );
    }

    /**
     * @param Collection<int, CustomField> $allFields
     */
    private function applyVisibility(
        Field       $field,
        CustomField $customField,
        Collection  $allFields
    ): Field
    {
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
}
