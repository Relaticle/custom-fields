<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\CustomFieldValidationRule;

/**
 * Custom Field Validation Component
 *
 * A comprehensive validation component for managing custom field validation rules
 * with intelligent rule filtering, parameter management, and state synchronization.
 *
 * Features:
 * - Dynamic rule filtering based on field type compatibility
 * - Automatic duplicate rule prevention
 * - Smart parameter management with validation
 * - Real-time state synchronization
 * - Comprehensive error handling and user feedback
 */
final class CustomFieldValidationComponent extends Component
{
    protected string $view = 'filament-schemas::components.grid';

    public function __construct()
    {
        $this->schema([
            $this->buildValidationRulesRepeater(),
        ]);

        $this->columnSpanFull();
    }

    public static function make(): self
    {
        return app(self::class);
    }

    /**
     * Build the main validation rules repeater component
     */
    private function buildValidationRulesRepeater(): Repeater
    {
        return Repeater::make('validation_rules')
            ->label(__('custom-fields::custom-fields.field.form.validation.rules'))
            ->schema([
                Grid::make(3)
                    ->schema([
                        $this->buildRuleSelector(),
                        $this->buildRuleDescription(),
                        $this->buildRuleParametersRepeater(),
                    ]),
            ])
            ->itemLabel(fn (array $state): string => $this->generateRuleLabel($state))
            ->collapsible()
            ->collapsed(fn (Get $get): bool => count($get('validation_rules') ?? []) > 3)
            ->reorderable()
            ->reorderableWithButtons()
            ->deletable()
            ->cloneable()
            ->hintColor('danger')
            ->addable(fn (Get $get): bool => $this->canAddRule($get))
            ->hiddenLabel()
            ->defaultItems(0)
            ->addActionLabel(__('custom-fields::custom-fields.field.form.validation.add_rule'))
            ->columnSpanFull();
    }

    /**
     * Build the rule selector component with intelligent filtering
     */
    private function buildRuleSelector(): Select
    {
        return Select::make('name')
            ->label(__('custom-fields::custom-fields.field.form.validation.rule'))
            ->placeholder(__('custom-fields::custom-fields.field.form.validation.select_rule_placeholder'))
            ->options(fn (Get $get) => $this->getAvailableRuleOptions($get))
            ->searchable()
            ->required()
            ->live()
            ->in(fn (Get $get) => $this->getAllowedRuleValues($get))
            ->afterStateUpdated(fn (Get $get, Set $set, ?string $state, ?string $old) => $this->handleRuleChange($get, $set, $state, $old)
            )
            ->columnSpan(1);
    }

    /**
     * Build the rule description component
     */
    private function buildRuleDescription(): TextEntry
    {
        return TextEntry::make('description')
            ->label(__('custom-fields::custom-fields.field.form.validation.description'))
            ->state(fn (Get $get): string => $this->getRuleDescription($get))
            ->columnSpan(2);
    }

    /**
     * Build the rule parameters repeater with comprehensive validation
     */
    private function buildRuleParametersRepeater(): Repeater
    {
        return Repeater::make('parameters')
            ->label(__('custom-fields::custom-fields.field.form.validation.parameters'))
            ->simple(
                TextInput::make('value')
                    ->label(__('custom-fields::custom-fields.field.form.validation.parameters_value'))
                    ->required()
                    ->hiddenLabel()
                    ->rules(fn (Get $get, Component $component): array => $this->getParameterValidationRules($get, $component)
                    )
                    ->hint(fn (Get $get, Component $component): string => $this->getParameterHint($get, $component)
                    )
                    ->afterStateHydrated(fn (Get $get, Set $set, $state, Component $component) => $this->hydrateParameterValue($get, $set, $state, $component)
                    )
                    ->dehydrateStateUsing(fn (Get $get, $state, Component $component) => $this->dehydrateParameterValue($get, $state, $component)
                    ),
            )
            ->columnSpanFull()
            ->visible(fn (Get $get): bool => $this->shouldShowParameters($get))
            ->minItems(fn (Get $get): int => $this->getMinParameterCount($get))
            ->maxItems(fn (Get $get): int => $this->getMaxParameterCount($get))
            ->reorderable(false)
            ->deletable(fn (Get $get): bool => $this->canDeleteParameter($get))
            ->defaultItems(fn (Get $get): int => $this->getDefaultParameterCount($get))
            ->hint(fn (Get $get) => $this->getParameterHint($get))
            ->hintColor('danger')
            ->addActionLabel(__('custom-fields::custom-fields.field.form.validation.add_parameter'));
    }

    /**
     * Get available rule options filtered by field type and existing rules
     */
    private function getAvailableRuleOptions(Get $get): array
    {
        $fieldType = $this->getFieldType($get);
        if (! $fieldType) {
            return [];
        }

        $allowedRules = $fieldType->allowedValidationRules();
        $existingRules = $this->getExistingRules($get);

        return collect($allowedRules)
            ->reject(fn (CustomFieldValidationRule $rule): bool => $this->isRuleDuplicate($existingRules, $rule->value)
            )
            ->mapWithKeys(fn (CustomFieldValidationRule $rule) => [
                $rule->value => $rule->getLabel(),
            ])
            ->toArray();
    }

    /**
     * Get all allowed rule values for validation
     */
    private function getAllowedRuleValues(Get $get): array
    {
        $fieldType = $this->getFieldType($get);
        if (! $fieldType) {
            return [];
        }

        return collect($fieldType->allowedValidationRules())
            ->pluck('value')
            ->toArray();
    }

    /**
     * Handle rule selection change
     */
    private function handleRuleChange(Get $get, Set $set, ?string $state, ?string $old): void
    {
        if ($old === $state) {
            return;
        }

        // Clear parameters when rule changes
        $set('parameters', []);

        if (empty($state)) {
            return;
        }

        // Auto-create required parameters
        $rule = CustomFieldValidationRule::tryFrom($state);
        if ($rule && $rule->allowedParameterCount() > 0) {
            $paramCount = $rule->allowedParameterCount();
            $parameters = array_fill(0, $paramCount, [
                'value' => '',
                'key' => Str::uuid()->toString(),
            ]);
            $set('parameters', $parameters);
        }
    }

    /**
     * Get rule description
     */
    private function getRuleDescription(Get $get): string
    {
        $ruleName = $get('name');

        return CustomFieldValidationRule::getDescriptionForRule($ruleName);
    }

    /**
     * Get parameter validation rules
     */
    private function getParameterValidationRules(Get $get, Component $component): array
    {
        $ruleName = $get('../../name');
        $parameterIndex = $this->getParameterIndex($component);

        return CustomFieldValidationRule::getParameterValidationRuleFor($ruleName, $parameterIndex);
    }

    /**
     * Get parameter hint text
     */
    private function getParameterHint(Get $get, ?Component $component = null): string
    {
        $ruleName = $get('name') ?? $get('../../name');

        if (empty($ruleName)) {
            return '';
        }

        if ($component) {
            $parameterIndex = $this->getParameterIndex($component);

            return CustomFieldValidationRule::getParameterHelpTextFor($ruleName, $parameterIndex);
        }

        // For repeater-level hints
        $rule = CustomFieldValidationRule::tryFrom($ruleName);
        $parameters = $get('parameters') ?? [];

        if ($rule && $rule->allowedParameterCount() > 0 && count($parameters) < $rule->allowedParameterCount()) {
            $requiredCount = $rule->allowedParameterCount();

            return match ($requiredCount) {
                2 => match ($rule) {
                    CustomFieldValidationRule::BETWEEN => __('custom-fields::custom-fields.validation.between_validation_error'),
                    CustomFieldValidationRule::DIGITS_BETWEEN => __('custom-fields::custom-fields.validation.digits_between_validation_error'),
                    CustomFieldValidationRule::DECIMAL => __('custom-fields::custom-fields.validation.decimal_validation_error'),
                    default => __('custom-fields::custom-fields.validation.parameter_missing', ['count' => $requiredCount]),
                },
                default => __('custom-fields::custom-fields.validation.parameter_missing', ['count' => $requiredCount]),
            };
        }

        return '';
    }

    /**
     * Hydrate parameter value
     */
    private function hydrateParameterValue(Get $get, Set $set, $state, Component $component): void
    {
        if ($state === null) {
            return;
        }

        $ruleName = $get('../../name');
        if (empty($ruleName)) {
            return;
        }

        $parameterIndex = $this->getParameterIndex($component);
        $normalizedValue = $this->normalizeParameterValue($ruleName, (string) $state, $parameterIndex);

        $set('value', $normalizedValue);
    }

    /**
     * Dehydrate parameter value
     */
    private function dehydrateParameterValue(Get $get, $state, Component $component): ?string
    {
        if ($state === null) {
            return null;
        }

        $ruleName = $get('../../name');
        if (empty($ruleName)) {
            return $state;
        }

        $parameterIndex = $this->getParameterIndex($component);

        return $this->normalizeParameterValue($ruleName, (string) $state, $parameterIndex);
    }

    /**
     * Check if parameters should be shown
     */
    private function shouldShowParameters(Get $get): bool
    {
        return CustomFieldValidationRule::hasParameterForRule($get('name'));
    }

    /**
     * Get minimum parameter count
     */
    private function getMinParameterCount(Get $get): int
    {
        $ruleName = $get('name');
        if (empty($ruleName)) {
            return 1;
        }

        $rule = CustomFieldValidationRule::tryFrom($ruleName);
        if ($rule && $rule->allowedParameterCount() > 0) {
            return $rule->allowedParameterCount();
        }

        return 1;
    }

    /**
     * Get maximum parameter count
     */
    private function getMaxParameterCount(Get $get): int
    {
        return CustomFieldValidationRule::getAllowedParametersCountForRule($get('name'));
    }

    /**
     * Check if parameter can be deleted
     */
    private function canDeleteParameter(Get $get): bool
    {
        $ruleName = $get('name');
        if (empty($ruleName)) {
            return true;
        }

        $rule = CustomFieldValidationRule::tryFrom($ruleName);
        $parameterCount = count($get('parameters') ?? []);

        // Don't allow deletion if it would go below required count
        return ! ($rule && $rule->allowedParameterCount() > 0 && $parameterCount <= $rule->allowedParameterCount());
    }

    /**
     * Get default parameter count
     */
    private function getDefaultParameterCount(Get $get): int
    {
        $ruleName = $get('name');
        if (empty($ruleName)) {
            return 1;
        }

        $rule = CustomFieldValidationRule::tryFrom($ruleName);
        if ($rule && $rule->allowedParameterCount() > 0) {
            return $rule->allowedParameterCount();
        }

        return 1;
    }

    /**
     * Generate rule label for display
     */
    private function generateRuleLabel(array $state): string
    {
        $ruleName = $state['name'] ?? '';
        $parameters = $state['parameters'] ?? [];

        return CustomFieldValidationRule::getLabelForRule($ruleName, $parameters);
    }

    /**
     * Check if rules can be added
     */
    private function canAddRule(Get $get): bool
    {
        return ! empty($get('type'));
    }

    /**
     * Get field type from context
     */
    private function getFieldType(Get $get): ?CustomFieldType
    {
        $fieldType = $get('../../type');
        if (empty($fieldType)) {
            return null;
        }

        return CustomFieldType::tryFrom($fieldType);
    }

    /**
     * Get existing rules from context
     */
    private function getExistingRules(Get $get): array
    {
        return $get('../../validation_rules') ?? [];
    }

    /**
     * Check if a validation rule is already in use
     */
    private function isRuleDuplicate(array $existingRules, string $newRule): bool
    {
        return collect($existingRules)->contains(
            fn (array $rule): bool => ($rule['name'] ?? '') === $newRule
        );
    }

    /**
     * Normalize a parameter value based on the validation rule type
     */
    private function normalizeParameterValue(?string $ruleName, string $value, int $parameterIndex = 0): string
    {
        return CustomFieldValidationRule::normalizeParameterValue($ruleName, $value, $parameterIndex);
    }

    /**
     * Get the parameter index from a component within a repeater
     *
     * This method intelligently determines the parameter index by examining
     * the component's state path and container hierarchy.
     */
    private function getParameterIndex(Component $component): int
    {
        $statePath = $component->getStatePath();

        // Extract the parameter key from state path
        if (! preg_match('/parameters\.([^.]+)/', $statePath, $matches)) {
            return 0;
        }

        $key = $matches[1];

        // Attempt to get index from container state
        $container = $component->getContainer();
        if (method_exists($container, 'getParentComponent')) {
            $repeater = $container->getParentComponent();
            $parameters = $repeater->getState();

            if (is_array($parameters)) {
                $keys = array_keys($parameters);
                $index = array_search($key, $keys, true);

                if ($index !== false) {
                    return (int) $index;
                }

                // Handle numeric keys directly
                if (is_numeric($key)) {
                    return (int) $key;
                }
            }
        }

        // Fallback: extract from component ID
        $idParts = explode('-', $component->getId());
        if (count($idParts) > 1) {
            $lastPart = end($idParts);
            if (is_numeric($lastPart)) {
                return (int) $lastPart;
            }
        }

        return 0;
    }
}
