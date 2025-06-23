<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Enums\ConditionalVisibilityLogic;
use Relaticle\CustomFields\Enums\ConditionalVisibilityMode;
use Relaticle\CustomFields\Enums\ConditionOperator;
use Relaticle\CustomFields\Enums\CustomFieldType;

class ConditionalVisibilityComponent extends Component
{
    protected string $view = 'filament-schemas::components.grid';

    public function __construct()
    {
        $this->schema([$this->buildConditionalVisibilityFieldset()]);
        $this->columnSpanFull();
    }

    public static function make(): self
    {
        return app(self::class);
    }

    private function buildConditionalVisibilityFieldset(): Fieldset
    {
        return Fieldset::make('Conditional Visibility')
            ->schema([
                Select::make('settings.conditional_visibility.enabled')
                    ->label('When to show or hide this field')
                    ->enum(ConditionalVisibilityMode::class)
                    ->default(ConditionalVisibilityMode::ALWAYS)
                    ->required()
                    ->live(),

                Select::make('settings.conditional_visibility.logic')
                    ->label('Logic')
                    ->options(ConditionalVisibilityLogic::options())
                    ->default(ConditionalVisibilityLogic::ALL->value)
                    ->visible(fn(Get $get): bool => $this->shouldShowConditionFields($get)),

                Repeater::make('settings.conditional_visibility.conditions')
                    ->label('Conditions')
                    ->schema([
                        Select::make('field')
                            ->label('Field')
                            ->options(function (Get $get) {
                                return $this->getCustomFieldsForEntity($get);
                            })
                            ->required()
                            ->columnSpan(3),

                        Select::make('operator')
                            ->label('Operator')
                            ->live()
                            ->options(function (Get $get) {
                                return $this->getOperatorOptionsForField($get);
                            })
                            ->required()
                            ->columnSpan(3),

                        TextInput::make('value')
                            ->label('Value')
                            ->columnSpan(6)
                            ->visible(fn(Get $get): bool => $this->shouldShowValueField($get)),
                    ])
                    ->columns(12)
                    ->visible(fn(Get $get): bool => $this->shouldShowConditionFields($get))
                    ->defaultItems(1)
                    ->minItems(1)
                    ->maxItems(10)
                    ->reorderable(false),

                Toggle::make('settings.conditional_visibility.always_save')
                    ->label('Always save')
                    ->helperText('Save the field value even if it is hidden by conditional visibility')
                    ->default(false)
                    ->visible(fn(Get $get): bool => $this->shouldShowConditionFields($get)),
            ])
            ->columns(1);
    }

    private function getCustomFieldsForEntity(Get $get): array
    {
        try {
            $entityType = $get('../../../../entity_type');
            $fieldCode = $get('../../../../code');

            // Fallback to URL if isn't found in form
            if (!$entityType) {
                $entityType = request('entityType') ?? request()->route('entityType');
            }

            if (!$entityType) {
                return [];
            }

            // Get all custom fields for this entity type
            return CustomFields::customFieldModel()::query()
                ->forMorphEntity($entityType)
                ->where('code', '!=', $fieldCode)
                ->orderBy('name')
                ->get()
                ->mapWithKeys(function ($field) {
                    return [$field->code => $field->name];
                })
                ->toArray();
        } catch (\Exception $e) {
            report($e);

            return [];
        }
    }

    /**
     * Check if condition-related fields should be shown.
     */
    private function shouldShowConditionFields(Get $get): bool
    {
        $visibilityMode = $get('settings.conditional_visibility.enabled');

        if (!$visibilityMode) {
            return false;
        }

        return $visibilityMode->requiresConditions();
    }

    /**
     * Check if the value field should be shown for the selected operator.
     */
    private function shouldShowValueField(Get $get): bool
    {
        $operator = $get('operator');

        if (!$operator) {
            return true;
        }

        $conditionOperator = ConditionOperator::from($operator);

        return $conditionOperator->requiresValue();
    }

    /**
     * Get operator options based on the selected field type.
     *
     * @return array<string, string>
     */
    private function getOperatorOptionsForField(Get $get): array
    {
        $fieldCode = $get('field');

        if (!$fieldCode) {
            return ConditionOperator::commonOptions();
        }

        try {
            // Get the field type for the selected field
            $entityType = $get('../../../../entity_type') ?? request('entityType') ?? request()->route('entityType');

            if (!$entityType) {
                return ConditionOperator::commonOptions();
            }

            $field = CustomFields::customFieldModel()::query()
                ->forMorphEntity($entityType)
                ->where('code', $fieldCode)
                ->first();

            if (!$field) {
                return ConditionOperator::commonOptions();
            }

            $fieldType = CustomFieldType::from($field->type->value);
            $operators = ConditionOperator::forFieldType($fieldType);

            $options = [];
            foreach ($operators as $operator) {
                $options[$operator->value] = $operator->getLabel();
            }

            return $options;
        } catch (\Exception $e) {
            report($e);

            return ConditionOperator::commonOptions();
        }
    }
}
