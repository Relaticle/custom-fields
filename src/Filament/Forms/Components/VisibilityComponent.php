<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\FieldCategory;
use Relaticle\CustomFields\Enums\Logic;
use Relaticle\CustomFields\Enums\Mode;
use Relaticle\CustomFields\Enums\Operator;
use Relaticle\CustomFields\Services\VisibilityService;

/**
 * Clean visibility component for configuring field visibility conditions.
 */
class VisibilityComponent extends Component
{
    protected string $view = 'filament-schemas::components.grid';

    public function __construct()
    {
        $this->schema([$this->buildFieldset()]);
        $this->columnSpanFull();
    }

    public static function make(): self
    {
        return new self;
    }

    private function buildFieldset(): Fieldset
    {
        return Fieldset::make('Conditional Visibility')
            ->schema([
                Select::make('settings.visibility.mode')
                    ->label('Visibility')
                    ->options(Mode::options())
                    ->default(Mode::ALWAYS_VISIBLE->value)
                    ->required()
                    ->live(),

                Select::make('settings.visibility.logic')
                    ->label('Condition Logic')
                    ->options(Logic::options())
                    ->default(Logic::ALL->value)
                    ->visible(fn (Get $get) => $this->requiresConditions($get)),

                Repeater::make('settings.visibility.conditions')
                    ->label('Conditions')
                    ->schema($this->buildConditionSchema())
                    ->visible(fn (Get $get) => $this->requiresConditions($get))
                    ->defaultItems(1)
                    ->minItems(1)
                    ->maxItems(10)
                    ->columnSpanFull()
                    ->reorderable(false)
                    ->columns(12),

                Toggle::make('settings.visibility.always_save')
                    ->label('Always save field value')
                    ->helperText('Save the field value even when hidden')
                    ->default(false)
                    ->visible(fn (Get $get) => $this->requiresConditions($get)),
            ]);
    }

    private function buildConditionSchema(): array
    {
        return [
            Select::make('field_code')
                ->label('Field')
                ->options(fn (Get $get) => $this->getFieldOptions($get))
                ->required()
                ->live()
                ->afterStateUpdated(function (Set $set) {
                    $set('operator', null);
                    $set('value', null);
                })
                ->columnSpan(4),

            Select::make('operator')
                ->label('Operator')
                ->options(fn (Get $get) => $this->getOperatorOptions($get))
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('value', null))
                ->columnSpan(3),

//            // Smart value input for optionable fields
            Select::make('value')
                ->label('Value')
                ->options(fn (Get $get) => $this->getValueOptions($get))
                ->searchable()
                ->visible(fn (Get $get) => $this->requiresValue($get) && $this->isOptionableField($get) && ! $this->requiresMultipleValues($get))
                ->placeholder(fn (Get $get) => $this->getValuePlaceholder($get))
                ->columnSpan(5),

            Select::make('multiple_values')
                ->label('Multiple Values')
                ->multiple()
                ->options(fn (Get $get) => $this->getValueOptions($get))
                ->visible(fn (Get $get) => $this->requiresMultipleValues($get) && $this->isOptionableField($get))
                ->placeholder(fn (Get $get) => $this->getValuePlaceholder($get))
                ->dehydrateStateUsing(fn ($state) => is_array($state) ? array_values($state) : $state)
                ->columnSpan(5),

            // Text input for non-optionable fields  
            TextInput::make('value')
                ->label('Value')
                ->placeholder(fn (Get $get) => $this->getValuePlaceholder($get))
                ->visible(fn (Get $get) => $this->requiresValue($get) && ! $this->isOptionableField($get))
                ->columnSpan(5),
        ];
    }

    /**
     * Check if the selected field is optionable.
     */
    private function isOptionableField(Get $get): bool
    {
        $fieldCode = $get('field_code');
        
        if (! $fieldCode) {
            return false;
        }

        try {
            $fieldType = $this->getFieldType($fieldCode, $get);
            return $fieldType?->isOptionable() ?? false;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Check if the field requires multiple values for the selected operator.
     */
    private function requiresMultipleValues(Get $get): bool
    {
        $fieldCode = $get('field_code');
        $operator = $get('operator');
        
        if (! $fieldCode || ! $operator) {
            return false;
        }

        try {
            $fieldType = $this->getFieldType($fieldCode, $get);
            
            if (! $fieldType) {
                return false;
            }

            // Multi-value fields support multiple selection for CONTAINS/NOT_CONTAINS
            if ($fieldType->hasMultipleValues()) {
                return in_array($operator, [
                    Operator::CONTAINS->value,
                    Operator::NOT_CONTAINS->value,
                ]);
            }

            return false;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get value options for optionable fields.
     */
    private function getValueOptions(Get $get): array
    {
        $fieldCode = $get('field_code');
        
        if (! $fieldCode) {
            return [];
        }

        try {
            $entityType = $this->getEntityType($get);
            
            if (! $entityType) {
                return [];
            }

            $visibilityService = app(VisibilityService::class);
            return $visibilityService->getFieldOptions($fieldCode, $entityType);
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Get appropriate placeholder for the value input.
     */
    private function getValuePlaceholder(Get $get): string
    {
        $fieldCode = $get('field_code');
        $operator = $get('operator');
        
        if (! $fieldCode) {
            return 'Select a field first';
        }

        if (! $operator) {
            return 'Select an operator first';
        }

        try {
            $fieldType = $this->getFieldType($fieldCode, $get);
            
            if (! $fieldType) {
                return 'Enter comparison value';
            }

            if ($fieldType->isOptionable()) {
                return $fieldType->hasMultipleValues() 
                    ? 'Select one or more options'
                    : 'Select an option';
            }

            return match ($fieldType->getCategory()) {
                FieldCategory::NUMERIC => 'Enter a number',
                FieldCategory::DATE => 'Enter a date (YYYY-MM-DD)',
                FieldCategory::BOOLEAN => 'true or false',
                default => 'Enter comparison value',
            };
        } catch (\Exception) {
            return 'Enter comparison value';
        }
    }

    private function requiresConditions(Get $get): bool
    {
        $mode = $get('settings.visibility.mode');

        if (! $mode) {
            return false;
        }

        try {
            return Mode::from($mode)->requiresConditions();
        } catch (\ValueError) {
            return false;
        }
    }

    private function requiresValue(Get $get): bool
    {
        $operator = $get('operator');

        if (! $operator) {
            return true;
        }

        try {
            return Operator::from($operator)->requiresValue();
        } catch (\ValueError) {
            return true;
        }
    }

    private function getFieldOptions(Get $get): array
    {
        try {
            $entityType = $this->getEntityType($get);
            $currentFieldCode = $get('../../../../code');

            if (! $entityType) {
                return [];
            }

            return CustomFields::customFieldModel()::query()
                ->forMorphEntity($entityType)
                ->where('code', '!=', $currentFieldCode)
                ->orderBy('name')
                ->pluck('name', 'code')
                ->toArray();
        } catch (\Exception) {
            return [];
        }
    }

    private function getOperatorOptions(Get $get): array
    {
        $fieldCode = $get('field_code');

        if (! $fieldCode) {
            return Operator::options();
        }

        try {
            $fieldType = $this->getFieldType($fieldCode, $get);

            return $fieldType
                ? Operator::forFieldType($fieldType)
                : Operator::options();
        } catch (\Exception) {
            return Operator::options();
        }
    }

    private function getFieldType(string $fieldCode, Get $get): ?CustomFieldType
    {
        try {
            $entityType = $this->getEntityType($get);

            if (! $entityType) {
                return null;
            }

            $field = CustomFields::customFieldModel()::query()
                ->forMorphEntity($entityType)
                ->where('code', $fieldCode)
                ->first();

            return $field?->type;
        } catch (\Exception) {
            return null;
        }
    }

    private function getEntityType(Get $get): ?string
    {
        return $get('../../../../entity_type') ??
               request('entityType') ??
               request()->route('entityType');
    }
}
