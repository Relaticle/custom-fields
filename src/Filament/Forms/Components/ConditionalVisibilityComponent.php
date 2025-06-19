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
                    ->live()
                    ->options([
                        'always' => 'Always Show',
                        'if' => 'Show When',
                        'unless' => 'Hide When',
                    ])
                    ->default('always'),

                Select::make('settings.conditional_visibility.logic')
                    ->label('Logic')
                    ->options([
                        'all' => 'All conditions must be met (AND)',
                        'any' => 'Any condition can be met (OR)',
                    ])
                    ->default('all')
                    ->visible(fn (Get $get): bool => $get('settings.conditional_visibility.enabled') === 'if' || $get('settings.conditional_visibility.enabled') === 'unless'),

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
                            ->options([
                                '=' => 'Equals',
                                '!=' => 'Not equals',
                                '>' => 'Greater than',
                                '<' => 'Less than',
                                '>=' => 'Greater or equal',
                                '<=' => 'Less or equal',
                                'contains' => 'Contains',
                                'not_contains' => 'Not contains',
                                'empty' => 'Is empty',
                                'not_empty' => 'Is not empty',
                            ])
                            ->required()
                            ->columnSpan(3),

                        TextInput::make('value')
                            ->label('Value')
                            ->columnSpan(6)
                            ->visible(fn (Get $get): bool => ! in_array($get('operator'), ['empty', 'not_empty'])),
                    ])
                    ->columns(12)
                    ->visible(fn (Get $get): bool => $get('settings.conditional_visibility.enabled') === 'if' || $get('settings.conditional_visibility.enabled') === 'unless')
                    ->defaultItems(1)
                    ->reorderable(false),

                Toggle::make('settings.conditional_visibility.always_save')
                    ->label('Always save')
                    ->helperText('Save the field value even if it is hidden by conditional visibility')
                    ->default(false)
                    ->visible(fn (Get $get): bool => $get('settings.conditional_visibility.enabled') === 'if' || $get('settings.conditional_visibility.enabled') === 'unless'),
            ])
            ->columns(1);
    }

    private function getCustomFieldsForEntity(Get $get): array
    {
        try {
            $entityType = $get('../../../../entity_type');

            // Fallback to URL if not found in form
            if (! $entityType) {
                $entityType = request('entityType') ?? request()->route('entityType');
            }

            if (! $entityType) {
                return [];
            }

            // Get all custom fields for this entity type
            $fields = CustomFields::customFieldModel()::query()
                ->forMorphEntity($entityType)
                ->orderBy('name')
                ->get();

            $options = [];
            foreach ($fields as $field) {
                $options[$field->code] = $field->name;
            }

            return $options;
        } catch (\Exception $e) {
            report($e);
            return [];
        }
    }
}
