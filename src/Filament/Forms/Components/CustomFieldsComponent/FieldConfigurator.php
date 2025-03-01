<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Data\ValidationRuleData;
use Relaticle\CustomFields\Enums\CustomFieldValidationRule;
use Relaticle\CustomFields\Models\CustomField;
use Spatie\LaravelData\DataCollection;

final readonly class FieldConfigurator
{
    /**
     * @template T of Field
     *
     * @param  T  $field
     * @return T
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

                // Set the component state
                $component->state($value);
            })
            ->dehydrated(fn ($state): bool => $state !== null && $state !== '')
            ->required($this->isRequired($customField))
            ->rules($this->convertRulesToFilamentFormat($customField->validation_rules));
    }

    /**
     * Converts validation rules from a collection to an array in the format expected by Filament.
     *
     * @param  DataCollection<int, ValidationRuleData>|null  $rules  The validation rules to convert.
     * @return array<string, string> The converted rules.
     */
    private function convertRulesToFilamentFormat(?DataCollection $rules): array
    {
        if (! $rules instanceof DataCollection || $rules->toCollection()->isEmpty()) {
            return [];
        }

        return $rules->toCollection()->map(function ($ruleData): string {
            if ($ruleData->parameters === []) {
                return $ruleData->name;
            }

            return $ruleData->name . ':' . implode(',', $ruleData->parameters);
        })->toArray();
    }

    public function isRequired(CustomField $customField): bool
    {
        return $customField->validation_rules->toCollection()->contains('name', CustomFieldValidationRule::REQUIRED->value);
    }
}
