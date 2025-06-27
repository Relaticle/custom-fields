<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components;

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
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\FieldCategory;
use Relaticle\CustomFields\Enums\Logic;
use Relaticle\CustomFields\Enums\Mode;
use Relaticle\CustomFields\Enums\Operator;
use Relaticle\CustomFields\Services\FieldTypeHelperService;
use Relaticle\CustomFields\Services\Visibility\BackendVisibilityService;
use ValueError;

/**
 * Clean visibility component for configuring field visibility conditions.
 */
class VisibilityComponent extends Component
{
    protected string $view = 'filament-schemas::components.grid';

    public function __construct(
        private ?FieldTypeHelperService $fieldTypeHelper = null,
    ) {
        $this->fieldTypeHelper ??= app(FieldTypeHelperService::class);
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
                    ->options(Mode::class)
                    ->default(Mode::ALWAYS_VISIBLE)
                    ->required()
                    ->afterStateHydrated(function (Select $component, $state): void {
                        $component->state($state ?? Mode::ALWAYS_VISIBLE);
                    })
                    ->live(),

                Select::make('settings.visibility.logic')
                    ->label('Condition Logic')
                    ->options(Logic::class)
                    ->default(Logic::ALL)
                    ->required()
                    ->afterStateHydrated(function (Select $component, $state): void {
                        $component->state($state ?? Logic::ALL);
                    })
                    ->visible(fn (Get $get): bool => $this->requiresConditions($get)),

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

                    $set('operator', array_key_first($this->getOperatorOptions($get)));

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
                ->options(fn (Get $get): array => $this->getOperatorOptions($get))
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
                ->visible(fn (Get $get): bool => $this->requiresSingleValue($get) && $this->isOptionableField($get))
                ->placeholder(fn (Get $get): string => $this->getValuePlaceholder($get))
                ->afterStateHydrated(function (Select $component, Get $get): void {
                    $component->state($get('value') ?? null);
                })
                ->afterStateUpdated(fn (?string $state, Set $set): mixed => $set('value', $state))
                ->columnSpan(5),

            Select::make('multiple_values')
                ->label('Value')
                ->live()
                ->searchable()
                ->multiple()
                ->options(fn (Get $get): array => $this->getValueOptions($get))
                ->visible(fn (Get $get): bool => $this->requiresMultipleValues($get) && $this->isOptionableField($get))
                ->placeholder(fn (Get $get): string => $this->getValuePlaceholder($get))
                ->afterStateHydrated(function (Select $component, Get $get): void {
                    $component->state(Arr::wrap($get('value')));
                })
                ->afterStateUpdated(fn (array $state, Set $set): mixed => $set('value', Arr::wrap($state)))
                ->columnSpan(5),

            // Text input for non-optionable fields
            TextInput::make('text_value')
                ->label('Value')
                ->placeholder(fn (Get $get): string => $this->getValuePlaceholder($get))
                ->visible(fn (Get $get): bool => $this->requiresValue($get) && ! $this->isOptionableField($get))
                ->afterStateHydrated(function (TextInput $component, Get $get): void {
                    $component->state($get('value') ?? '');
                })
                ->afterStateUpdated(fn (string $state, Set $set): mixed => $set('value', $state))
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

            return $fieldType instanceof CustomFieldType && $this->fieldTypeHelper->isOptionable($fieldType);
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

            if (! $fieldType instanceof CustomFieldType) {
                return true;
            }

            // Multi-value fields require multiple selection for CONTAINS/NOT_CONTAINS
            if ($fieldType->hasMultipleValues()) {
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

            if (! $fieldType instanceof CustomFieldType) {
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
        } catch (Exception) {
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

            if ($entityType === null || $entityType === '' || $entityType === '0') {
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

            if (! $fieldType instanceof CustomFieldType) {
                return 'Enter comparison value';
            }

            if ($this->fieldTypeHelper->isOptionable($fieldType)) {
                return $this->requiresMultipleValues($get)
                    ? 'Select one or more options'
                    : 'Select an option';
            }

            return match ($fieldType->getCategory()) {
                FieldCategory::NUMERIC => 'Enter a number',
                FieldCategory::DATE => 'Enter a date (YYYY-MM-DD)',
                FieldCategory::BOOLEAN => 'true or false',
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

    private function getFieldOptions(Get $get): array
    {
        try {
            $entityType = $this->getEntityType($get);
            $currentFieldCode = $get('../../../../code');

            if ($entityType === null || $entityType === '' || $entityType === '0') {
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

    private function getOperatorOptions(Get $get): array
    {
        $fieldCode = $get('field_code');

        if (! $fieldCode) {
            return Operator::options();
        }

        try {
            $fieldType = $this->getFieldType($fieldCode, $get);

            return $fieldType instanceof CustomFieldType
                ? Operator::forFieldType($fieldType)
                : Operator::options();
        } catch (Exception) {
            return Operator::options();
        }
    }

    private function getFieldType(string $fieldCode, Get $get): ?CustomFieldType
    {
        try {
            $entityType = $this->getEntityType($get);

            if ($entityType === null || $entityType === '' || $entityType === '0') {
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
            request('entityType') ??
            request()->route('entityType');
    }
}
