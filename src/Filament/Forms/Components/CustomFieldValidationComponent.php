<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components;

use Filament\Schemas\Components\Component;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\CustomFieldValidationRule;
use Illuminate\Support\Str;

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

    private function buildValidationRulesRepeater(): Repeater
    {
        return Repeater::make('validation_rules')
            ->label(__('custom-fields::custom-fields.field.form.validation.rules'))
            ->schema([
                Grid::make(3)
                    ->schema([
                        Select::make('name')
                            ->label(__('custom-fields::custom-fields.field.form.validation.rule'))
                            ->placeholder('Select Rule')
                            ->options(function (Get $get) {
                                $existingRules = $get('../../validation_rules') ?? [];
                                $fieldType = $get('../../type');
                                if (empty($fieldType)) {
                                    return [];
                                }
                                $customFieldType = CustomFieldType::tryFrom($fieldType);
                                $allowedRules = $customFieldType instanceof CustomFieldType ? $customFieldType->allowedValidationRules() : [];

                                return collect($allowedRules)
                                    ->reject(fn ($enum): bool => $this->hasDuplicateRule($existingRules, $enum->value))
                                    ->mapWithKeys(fn ($enum) => [$enum->value => $enum->getLabel()])
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?string $old): void {
                                if ($old !== $state) {
                                    $set('parameters', []);
                                    
                                    if (empty($state)) {
                                        return;
                                    }
                            
                                    // Create appropriate number of parameters based on rule requirements
                                    $rule = CustomFieldValidationRule::tryFrom($state);
                                    if ($rule && $rule->allowedParameterCount() > 0) {
                                        $paramCount = $rule->allowedParameterCount();
                                        $parameters = array_fill(0, $paramCount, ['value' => '', 'key' => Str::uuid()->toString()]);
                                        $set('parameters', $parameters);
                                    }
                                }
                            })
                            ->columnSpan(1),
                        Placeholder::make('description')
                            ->label(__('custom-fields::custom-fields.field.form.validation.description'))
                            ->content(fn (Get $get): string => CustomFieldValidationRule::getDescriptionForRule($get('name')))
                            ->columnSpan(2),
                        $this->buildRuleParametersRepeater(),
                    ]),
            ])
            ->itemLabel(fn (array $state): string => CustomFieldValidationRule::getLabelForRule((string) ($state['name'] ?? ''), $state['parameters'] ?? []))
            ->collapsible()
            ->collapsed(fn (Get $get): bool => count($get('validation_rules') ?? []) > 3)
            ->reorderable()
            ->reorderableWithButtons()
            ->deletable()
            ->cloneable()
            ->hintColor('danger')
            ->addable(fn (Get $get): bool => $get('type') && CustomFieldType::tryFrom($get('type')))
            ->hint(function (Get $get): string {
                $isTypeSelected = $get('type') && CustomFieldType::tryFrom($get('type'));

                return $isTypeSelected ? '' : __('custom-fields::custom-fields.field.form.validation.rules_hint');
            })
            ->hiddenLabel()
            ->defaultItems(0)
            ->addActionLabel(__('custom-fields::custom-fields.field.form.validation.add_rule'))
            ->columnSpanFull();
    }

    private function buildRuleParametersRepeater(): Repeater
    {
        return Repeater::make('parameters')
            ->label(__('custom-fields::custom-fields.field.form.validation.parameters'))
            ->simple(
                TextInput::make('value')
                    ->label(__('custom-fields::custom-fields.field.form.validation.parameters_value'))
                    ->required()
                    ->hiddenLabel()
                    ->rules(function (Get $get, $record, $state, Component $component): array {
                        $ruleName = $get('../../name');
                        $parameterIndex = $this->getParameterIndex($component);
                        return CustomFieldValidationRule::getParameterValidationRuleFor($ruleName, $parameterIndex);
                    })
                    ->hint(function (Get $get, Component $component): string {
                        $ruleName = $get('../../name');
                        if (empty($ruleName)) {
                            return '';
                        }
                        $parameterIndex = $this->getParameterIndex($component);

                        return CustomFieldValidationRule::getParameterHelpTextFor($ruleName, $parameterIndex);
                    })
                    ->afterStateHydrated(function (Get $get, Set $set, $state, Component $component): void {
                        if ($state === null) {
                            return;
                        }
                        
                        $ruleName = $get('../../name');
                        if (empty($ruleName)) {
                            return;
                        }
                        $parameterIndex = $this->getParameterIndex($component);
                        
                        $set('value', $this->normalizeParameterValue($ruleName, (string) $state, $parameterIndex));
                    })
                    ->dehydrateStateUsing(function (Get $get, $state, Component $component) {
                        if ($state === null) {
                            return null;
                        }
                        
                        $ruleName = $get('../../name');
                        if (empty($ruleName)) {
                            return $state;
                        }
                        $parameterIndex = $this->getParameterIndex($component);
                        
                        return $this->normalizeParameterValue($ruleName, (string) $state, $parameterIndex);
                    }),
            )
            ->columnSpanFull()
            ->visible(fn (Get $get): bool => CustomFieldValidationRule::hasParameterForRule($get('name')))
            ->minItems(function (Get $get): int {
                $ruleName = $get('name');
                if (empty($ruleName)) {
                    return 1;
                }
                $rule = CustomFieldValidationRule::tryFrom($ruleName);
                
                // For rules with specific parameter counts, ensure we have the right minimum
                if ($rule && $rule->allowedParameterCount() > 0) {
                    return $rule->allowedParameterCount();
                }
                
                return 1;
            })
            ->maxItems(fn (Get $get): int => CustomFieldValidationRule::getAllowedParametersCountForRule($get('name')))
            ->reorderable(false)
            ->deletable(function (Get $get): bool {
                $ruleName = $get('name');
                if (empty($ruleName)) {
                    return true;
                }
                $rule = CustomFieldValidationRule::tryFrom($ruleName);
            
                // For rules with specific parameter counts, don't allow deleting if it would go below required count
                return !($rule && $rule->allowedParameterCount() > 0 && count($get('parameters') ?? []) <= $rule->allowedParameterCount());
            })
            ->defaultItems(function (Get $get): int {
                $ruleName = $get('name');
                if (empty($ruleName)) {
                    return 1;
                }
                $rule = CustomFieldValidationRule::tryFrom($ruleName);
                
                // For rules with specific parameter counts, create the right number by default
                if ($rule && $rule->allowedParameterCount() > 0) {
                    return $rule->allowedParameterCount();
                }
                
                return 1;
            })
            ->hint(function (Get $get) {
                $ruleName = $get('name');
                if (empty($ruleName)) {
                    return null;
                }
                $rule = CustomFieldValidationRule::tryFrom($ruleName);
                $parameters = $get('parameters') ?? [];
                
                // Validate that rules have the correct number of parameters
                if ($rule && $rule->allowedParameterCount() > 0 && count($parameters) < $rule->allowedParameterCount()) {
                    $requiredCount = $rule->allowedParameterCount();
                    
                    // Special case handling for known rules
                    if ($requiredCount === 2) {
                        return match($rule) {
                            CustomFieldValidationRule::BETWEEN => 
                                __('custom-fields::custom-fields.validation.between_validation_error'),
                            CustomFieldValidationRule::DIGITS_BETWEEN => 
                                __('custom-fields::custom-fields.validation.digits_between_validation_error'),
                            CustomFieldValidationRule::DECIMAL => 
                                __('custom-fields::custom-fields.validation.decimal_validation_error'),
                            default => 
                                __('custom-fields::custom-fields.validation.parameter_missing', ['count' => $requiredCount]),
                        };
                    }
                    
                    // Generic message for other parameter counts
                    return __('custom-fields::custom-fields.validation.parameter_missing', ['count' => $requiredCount]);
                }
                
                return null;
            })
            ->hintColor('danger')
            ->addActionLabel(__('custom-fields::custom-fields.field.form.validation.add_parameter'));
    }

    /**
     * Checks if a validation rule already exists in the array of rules.
     *
     * @param  array<string, array<string, string>>  $rules
     */
    private function hasDuplicateRule(array $rules, string $newRule): bool
    {
        return collect($rules)->contains(fn (array $rule): bool => $rule['name'] === $newRule);
    }
    
    /**
     * Normalize a parameter value based on the validation rule type.
     * 
     * @param string|null $ruleName The validation rule name
     * @param string $value The parameter value to normalize
     * @param int $parameterIndex The index of the parameter (0-based)
     * @return string The normalized parameter value
     */
    private function normalizeParameterValue(?string $ruleName, string $value, int $parameterIndex = 0): string
    {
        return CustomFieldValidationRule::normalizeParameterValue($ruleName, $value, $parameterIndex);
    }
    
    /**
     * Get the parameter index from a component within a repeater.
     *
     * @param \Filament\Schemas\Components\Component $component The component to get the index for
     * @return int The zero-based index of the parameter
     */
    private function getParameterIndex(Component $component): int
    {
        $statePath = $component->getStatePath();

        // Extract the key from the state path
        if (preg_match('/parameters\.([^\.]+)/', $statePath, $matches)) {
            $key = $matches[1];

            // Try to directly find the index in the container state
            $container = $component->getContainer();
            if (method_exists($container, 'getParentComponent')) {
                $repeater = $container->getParentComponent();
                $parameters = $repeater->getState();

                // If parameters is just a flat array (simple repeater), use the keys directly
                if (is_array($parameters)) {
                    $keys = array_keys($parameters);
                    $index = array_search($key, $keys);
                    if ($index !== false) {
                        return (int) $index;
                    }

                    // If it's a numeric key, just return that
                    if (is_numeric($key)) {
                        return (int) $key;
                    }
                }
            }

            // For UUIDs or other keys, try to extract the ordinal position from the DOM structure
            $idParts = explode('-', $component->getId());
            if (count($idParts) > 1) {
                $lastPart = end($idParts);
                if (is_numeric($lastPart)) {
                    return (int) $lastPart;
                }
            }
        }
        
        return 0;
    }
}

