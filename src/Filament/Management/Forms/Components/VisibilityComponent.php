<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Management\Forms\Components;

use Exception;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Arr;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Enums\FieldDataType;
use Relaticle\CustomFields\Enums\Logic;
use Relaticle\CustomFields\Enums\Mode;
use Relaticle\CustomFields\Enums\Operator;
use Relaticle\CustomFields\Facades\CustomFieldsType;
use Relaticle\CustomFields\Services\Visibility\BackendVisibilityService;
use ValueError;

/**
 * Clean visibility component for configuring field visibility conditions.
 */
class VisibilityComponent extends Component
{
    public $fieldTypeHelper;

    protected string $view = 'filament-schemas::components.grid';

    public function __construct(
    ) {
        $this->schema([$this->buildFieldset()]);
        $this->columnSpanFull();
    }

    public static function make(): self
    {
        return new self;
    }

    private function buildFieldset(): Fieldset
    {
        return Fieldset::make('Conditional Visibility')->schema([
            Select::make('settings.visibility.mode')
                ->label('Visibility')
                ->options(Mode::class)
                ->default(Mode::ALWAYS_VISIBLE)
                ->required()
                ->afterStateHydrated(function (
                    Select $component,
                    $state
                ): void {
                    $component->state($state ?? Mode::ALWAYS_VISIBLE);
                })
                ->live(),

            Select::make('settings.visibility.logic')
                ->label('Condition Logic')
                ->options(Logic::class)
                ->default(Logic::ALL)
                ->required()
                ->afterStateHydrated(function (
                    Select $component,
                    $state
                ): void {
                    $component->state($state ?? Logic::ALL);
                })
                ->visible(
                    fn (Get $get): bool => $this->requiresConditions($get)
                ),

            Repeater::make('settings.visibility.conditions')
                ->label('Conditions')
                ->schema($this->buildConditionSchema())
                ->visible(fn (Get $get): bool => $this->requiresConditions($get))
                ->defaultItems(1)
                ->minItems(1)
                ->maxItems(10)
                ->columnSpanFull()
                ->reorderable(false)
                ->columns(12),
        ]);
    }

    /**
     * @return array<int, Component>
     */
    private function buildConditionSchema(): array
    {
        return [
            Select::make('field_code')
                ->label('Field')
                ->options(fn (Get $get): array => $this->getFieldOptions($get))
                ->required()
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set): void {
                    $set('value', null);

                    $set(
                        'operator',
                        array_key_first($this->getOperatorOptions($get))
                    );

                    if ($this->requiresSingleValue($get)) {
                        $set('text_value', null);

                        return;
                    }

                    if ($this->requiresMultipleValues($get)) {
                        $set('multiple_values', []);

                        return;
                    }

                    $set('single_value', null);
                })
                ->columnSpan(4),

            Select::make('operator')
                ->label('Operator')
                ->options(
                    fn (Get $get): array => $this->getOperatorOptions($get)
                )
                ->required()
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set): void {
                    $set('value', null);

                    if ($this->requiresSingleValue($get)) {
                        $set('text_value', null);

                        return;
                    }

                    if ($this->requiresMultipleValues($get)) {
                        $set('multiple_values', []);

                        return;
                    }

                    $set('single_value', null);
                })
                ->columnSpan(3),

            // Smart value input for optionable fields
            Select::make('single_value')
                ->label('Value')
                ->live()
                ->searchable()
                ->options(fn (Get $get): array => $this->getValueOptions($get))
                ->visible(
                    fn (Get $get): bool => $this->requiresSingleValue($get) &&
                        $this->isOptionableField($get)
                )
                ->placeholder(
                    fn (Get $get): string => $this->getValuePlaceholder($get)
                )
                ->afterStateHydrated(function (
                    Select $component,
                    Get $get
                ): void {
                    $component->state($get('value') ?? null);
                })
                ->afterStateUpdated(
                    fn (?string $state, Set $set): mixed => $set('value', $state)
                )
                ->columnSpan(5),

            Select::make('multiple_values')
                ->label('Value')
                ->live()
                ->searchable()
                ->multiple()
                ->options(fn (Get $get): array => $this->getValueOptions($get))
                ->visible(
                    fn (Get $get): bool => $this->requiresMultipleValues($get) &&
                        $this->isOptionableField($get)
                )
                ->placeholder(
                    fn (Get $get): string => $this->getValuePlaceholder($get)
                )
                ->afterStateHydrated(function (
                    Select $component,
                    Get $get
                ): void {
                    $component->state(Arr::wrap($get('value')));
                })
                ->afterStateUpdated(
                    fn (array $state, Set $set): mixed => $set(
                        'value',
                        Arr::wrap($state)
                    )
                )
                ->columnSpan(5),

            // Text input for non-optionable fields
            TextInput::make('text_value')
                ->label('Value')
                ->placeholder(
                    fn (Get $get): string => $this->getValuePlaceholder($get)
                )
                ->visible(
                    fn (Get $get): bool => $this->requiresValue($get) &&
                        ! $this->isOptionableField($get)
                )
                ->afterStateHydrated(function (
                    TextInput $component,
                    Get $get
                ): void {
                    $component->state($get('value') ?? '');
                })
                ->afterStateUpdated(
                    fn (string $state, Set $set): mixed => $set('value', $state)
                )
                ->columnSpan(5),

            Hidden::make('value')->default(null),
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

            if ($fieldType === null || $fieldType === '' || $fieldType === '0') {
                return false;
            }

            // For string types, check the field type data
            $fieldTypeData = CustomFieldsType::getFieldType($fieldType);

            return $fieldTypeData->dataType->isChoiceField();
        } catch (Exception) {
            return false;
        }
    }

    private function requiresSingleValue(Get $get): bool
    {
        $fieldCode = $get('field_code');
        $operator = $get('operator');

        if (! $fieldCode || ! $operator) {
            return true;
        }

        try {
            $fieldType = $this->getFieldType($fieldCode, $get);

            if ($fieldType === null || $fieldType === '' || $fieldType === '0') {
                return true;
            }

            // Check if field has multiple values
            $fieldTypeData = CustomFieldsType::getFieldType($fieldType);
            $hasMultipleValues = $fieldTypeData->dataType->isMultiChoiceField();

            // Multi-value fields require multiple selection for CONTAINS/NOT_CONTAINS
            if ($hasMultipleValues) {
                return ! in_array($operator, [
                    Operator::CONTAINS->value,
                    Operator::NOT_CONTAINS->value,
                ]);
            }

            return true;
        } catch (Exception) {
            return true;
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

            if ($fieldType === null || $fieldType === '' || $fieldType === '0') {
                return false;
            }

            // Check if field has multiple values
            $fieldTypeData = CustomFieldsType::getFieldType($fieldType);
            $hasMultipleValues = $fieldTypeData->dataType->isMultiChoiceField();

            // Multi-value fields support multiple selection for CONTAINS/NOT_CONTAINS
            if ($hasMultipleValues) {
                return in_array($operator, [
                    Operator::CONTAINS->value,
                    Operator::NOT_CONTAINS->value,
                ]);
            }

            return false;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, string>
     */
    private function getValueOptions(Get $get): array
    {
        $fieldCode = $get('field_code');

        if (! $fieldCode) {
            return [];
        }

        try {
            $entityType = $this->getEntityType($get);

            if (
                $entityType === null ||
                $entityType === '' ||
                $entityType === '0'
            ) {
                return [];
            }

            $visibilityService = app(BackendVisibilityService::class);

            return $visibilityService->getFieldOptions($fieldCode, $entityType);
        } catch (Exception) {
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

            if ($fieldType === null || $fieldType === '' || $fieldType === '0') {
                return 'Enter comparison value';
            }

            // Get field data type
            $fieldTypeData = CustomFieldsType::getFieldType($fieldType);
            $dataType = $fieldTypeData->dataType;
            $isOptionable = $fieldTypeData->dataType->isChoiceField();

            if ($isOptionable) {
                return $this->requiresMultipleValues($get)
                    ? 'Select one or more options'
                    : 'Select an option';
            }

            return match ($dataType) {
                FieldDataType::NUMERIC => 'Enter a number',
                FieldDataType::DATE, FieldDataType::DATE_TIME => 'Enter a date (YYYY-MM-DD)',
                FieldDataType::BOOLEAN => 'true or false',
                default => 'Enter comparison value',
            };
        } catch (Exception) {
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
            return $mode->requiresConditions();
        } catch (ValueError) {
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
        } catch (ValueError) {
            return true;
        }
    }

    /**
     * @return array<string, string>
     */
    private function getFieldOptions(Get $get): array
    {
        try {
            $entityType = $this->getEntityType($get);
            $currentFieldCode = $get('../../../../code');

            if (
                $entityType === null ||
                $entityType === '' ||
                $entityType === '0'
            ) {
                return [];
            }

            return CustomFields::customFieldModel()::query()
                ->forMorphEntity($entityType)
                ->where('code', '!=', $currentFieldCode)
                ->orderBy('name')
                ->pluck('name', 'code')
                ->toArray();
        } catch (Exception) {
            return [];
        }
    }

    /**
     * @return array<string, string>
     */
    private function getOperatorOptions(Get $get): array
    {
        $fieldCode = $get('field_code');

        if (! $fieldCode) {
            return Operator::options();
        }

        try {
            $fieldType = $this->getFieldType($fieldCode, $get);

            if ($fieldType === null || $fieldType === '' || $fieldType === '0') {
                return Operator::options();
            }

            // Get field type data to get the data type
            $fieldTypeData = CustomFieldsType::getFieldType($fieldType);

            return $fieldTypeData->dataType->getCompatibleOperatorOptions();
        } catch (Exception) {
            return Operator::options();
        }
    }

    private function getFieldType(string $fieldCode, Get $get): ?string
    {
        try {
            $entityType = $this->getEntityType($get);

            if (
                $entityType === null ||
                $entityType === '' ||
                $entityType === '0'
            ) {
                return null;
            }

            $field = CustomFields::customFieldModel()::query()
                ->forMorphEntity($entityType)
                ->where('code', $fieldCode)
                ->first();

            return $field?->type;
        } catch (Exception) {
            return null;
        }
    }

    private function getEntityType(Get $get): ?string
    {
        return $get('../../../../entity_type') ??
            (request('entityType') ?? request()->route('entityType'));
    }
}
