<?php

// ABOUTME: Unified factory for creating Filament components across different contexts
// ABOUTME: Replaces separate factories for forms, tables, and infolists

namespace Relaticle\CustomFields\Filament\Integration\Factories;

use Filament\Forms\Components\Field;
use Filament\Infolists\Components\Entry;
use Filament\Tables\Columns\Column;
use Relaticle\CustomFields\Filament\Integration\Enums\ComponentContext;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\FieldTypeManager;

class ComponentFactory
{
    public static function make(CustomField $customField, ComponentContext $context): Field|Column|Entry|null
    {
        $fieldType = $customField->fieldType;
        $definition = FieldTypeManager::getDefinition($fieldType->type);

        if (! $definition) {
            return null;
        }

        $componentClass = match ($context) {
            ComponentContext::FORM => $definition->formComponent(),
            ComponentContext::TABLE => $definition->tableComponent(),
            ComponentContext::INFOLIST => $definition->infolistComponent(),
        };

        if (! $componentClass || ! class_exists($componentClass)) {
            return null;
        }

        // Create the component based on context
        $component = match ($context) {
            ComponentContext::FORM => self::createFormComponent($componentClass, $customField),
            ComponentContext::TABLE => self::createTableComponent($componentClass, $customField),
            ComponentContext::INFOLIST => self::createInfolistComponent($componentClass, $customField),
        };

        return $component;
    }

    protected static function createFormComponent(string $componentClass, CustomField $customField): ?Field
    {
        $component = $componentClass::make($customField);

        // Apply field configuration using existing FieldConfigurator logic
        if ($component instanceof Field) {
            self::configureFormField($component, $customField);
        }

        return $component;
    }

    protected static function createTableComponent(string $componentClass, CustomField $customField): ?Column
    {
        return $componentClass::make($customField);
    }

    protected static function createInfolistComponent(string $componentClass, CustomField $customField): ?Entry
    {
        $component = $componentClass::make($customField);

        // Apply infolist configuration if needed
        if ($component instanceof Entry) {
            self::configureInfolistEntry($component, $customField);
        }

        return $component;
    }

    protected static function configureFormField(Field $field, CustomField $customField): void
    {
        $validationService = app(\Relaticle\CustomFields\Services\ValidationService::class);
        $coreVisibilityLogic = app(\Relaticle\CustomFields\Services\Visibility\CoreVisibilityLogicService::class);
        $frontendVisibilityService = app(\Relaticle\CustomFields\Services\Visibility\FrontendVisibilityService::class);

        $field
            ->name("custom_fields.{$customField->code}")
            ->label($customField->name)
            ->afterStateHydrated(
                fn (mixed $component, mixed $state, mixed $record): mixed => $component->state(
                    self::getFieldValue($customField, $state, $record)
                )
            )
            ->dehydrated(
                fn (mixed $state): bool => \Relaticle\CustomFields\Support\Utils::isConditionalVisibilityFeatureEnabled() &&
                    ($coreVisibilityLogic->shouldAlwaysSave($customField) || filled($state))
            )
            ->required($validationService->isRequired($customField))
            ->rules($validationService->getValidationRules($customField))
            ->columnSpan($customField->width->getSpanValue());
    }

    private static function getFieldValue(CustomField $customField, mixed $state, mixed $record): mixed
    {
        return value(function () use ($customField, $state, $record) {
            $value = $record?->getCustomFieldValue($customField) ??
                ($state ?? ($customField->isMultiChoiceField() ? [] : null));

            return $value instanceof \Illuminate\Support\Carbon
                ? $value->format(
                    $customField->isDateField()
                        ? \Relaticle\CustomFields\Support\FieldTypeUtils::getDateFormat()
                        : \Relaticle\CustomFields\Support\FieldTypeUtils::getDateTimeFormat()
                )
                : $value;
        });
    }

    protected static function configureInfolistEntry(Entry $entry, CustomField $customField): void
    {
        $entry
            ->name('custom_fields.'.$customField->code)
            ->label($customField->name)
            ->state(fn ($record) => $record->getCustomFieldValue($customField));
    }
}
