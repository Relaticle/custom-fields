<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Schemas;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldValidationComponent;
use Relaticle\CustomFields\Filament\Forms\Components\TypeField;
use Relaticle\CustomFields\Filament\Forms\Components\VisibilityComponent;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\EntityTypeService;
use Relaticle\CustomFields\Services\LookupTypeService;
use Relaticle\CustomFields\Support\Utils;

class FieldForm implements FormInterface
{
    /**
     * @throws \Exception
     */
    public static function schema(bool $withOptionsRelationship = true): array
    {
        $optionsRepeater = Forms\Components\Repeater::make('options')
            ->table([
                TableColumn::make('Color')
                    ->width('150px')
                    ->hiddenHeaderLabel(),
                TableColumn::make('Name')
                    ->hiddenHeaderLabel(),
            ])
            ->hiddenLabel()
            ->schema([
                Forms\Components\ColorPicker::make('settings.color')
                    ->columnSpan(3)
                    ->hexColor()
                    ->visible(fn (Utilities\Get $get): bool => Utils::isSelectOptionColorsFeatureEnabled() &&
                        $get('../../settings.enable_option_colors')
                    ),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->columnSpan(9)
                    ->distinct(),
            ])
            ->columns(12)
            ->columnSpanFull()
            ->requiredUnless('type', CustomFieldType::TAGS_INPUT->value)
            ->hiddenLabel()
            ->defaultItems(1)
            ->minItems(1)
            ->addActionLabel(__('custom-fields::custom-fields.field.form.options.add'))
            ->columnSpanFull()
            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                if (Utils::isTenantEnabled()) {
                    $data[config('custom-fields.column_names.tenant_foreign_key')] = Filament::getTenant()?->getKey();
                }

                return $data;
            });

        if ($withOptionsRelationship) {
            $optionsRepeater = $optionsRepeater->relationship();
        }

        $optionsRepeater->reorderable()->orderColumn('sort_order');

        return [
            Tabs::make()
                ->tabs([
                    Tabs\Tab::make(__('custom-fields::custom-fields.field.form.general'))
                        ->schema([
                            Forms\Components\Select::make('entity_type')
                                ->label(__('custom-fields::custom-fields.field.form.entity_type'))
                                ->options(EntityTypeService::getOptions())
                                ->disabled()
                                ->default(fn () => request('entityType', EntityTypeService::getDefaultOption()))
                                ->required(),
                            TypeField::make('type')
                                ->label(__('custom-fields::custom-fields.field.form.type'))
                                ->disabled(fn (?CustomField $record): bool => (bool) $record?->exists)
                                ->live()
                                ->afterStateHydrated(function (Forms\Components\Select $component, $state, $record): void {
                                    if (blank($state)) {
                                        $component->state($record?->type ?? CustomFieldType::TEXT->value);
                                    }
                                })
                                ->required(),
                            Forms\Components\TextInput::make('name')
                                ->label(__('custom-fields::custom-fields.field.form.name'))
                                ->helperText(__('custom-fields::custom-fields.field.form.name_helper_text'))
                                ->live(onBlur: true)
                                ->required()
                                ->maxLength(50)
                                ->disabled(fn (?CustomField $record): bool => (bool) $record?->system_defined)
                                ->unique(
                                    table: CustomFields::customFieldModel(),
                                    column: 'name',
                                    ignoreRecord: true,
                                    modifyRuleUsing: function (Unique $rule, Utilities\Get $get) {
                                        return $rule
                                            ->where('entity_type', $get('entity_type'))
                                            ->when(
                                                Utils::isTenantEnabled(),
                                                function (Unique $rule) {
                                                    return $rule->where(
                                                        config('custom-fields.column_names.tenant_foreign_key'),
                                                        Filament::getTenant()?->id
                                                    );
                                                });
                                    },
                                )
                                ->afterStateUpdated(function (Utilities\Get $get, Utilities\Set $set, ?string $old, ?string $state): void {
                                    $old ??= '';
                                    $state ??= '';

                                    if (($get('code') ?? '') !== Str::of($old)->slug('_')->toString()) {
                                        return;
                                    }

                                    $set('code', Str::of($state)->slug('_')->toString());
                                }),
                            Forms\Components\TextInput::make('code')
                                ->label(__('custom-fields::custom-fields.field.form.code'))
                                ->helperText(__('custom-fields::custom-fields.field.form.code_helper_text'))
                                ->live(onBlur: true)
                                ->required()
                                ->alphaDash()
                                ->maxLength(50)
                                ->disabled(fn (?CustomField $record): bool => (bool) $record?->system_defined)
                                ->unique(
                                    table: CustomFields::customFieldModel(),
                                    column: 'code',
                                    ignoreRecord: true,
                                    modifyRuleUsing: function (Unique $rule, Utilities\Get $get) {
                                        return $rule
                                            ->where('entity_type', $get('entity_type'))
                                            ->when(
                                                Utils::isTenantEnabled(),
                                                function (Unique $rule) {
                                                    return $rule->where(
                                                        config('custom-fields.column_names.tenant_foreign_key'),
                                                        Filament::getTenant()?->id
                                                    );
                                                });
                                    },
                                )
                                ->afterStateUpdated(function (Utilities\Set $set, ?string $state): void {
                                    $set('code', Str::of($state)->slug('_')->toString());
                                }),
                            Fieldset::make(__('custom-fields::custom-fields.field.form.settings'))
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema([
                                    // Visibility settings
                                    Forms\Components\Toggle::make('settings.visible_in_list')
                                        ->inline(false)
                                        ->live()
                                        ->label(__('custom-fields::custom-fields.field.form.visible_in_list'))
                                        ->afterStateHydrated(function (Forms\Components\Toggle $component, $state) {
                                            if (is_null($state)) {
                                                $component->state(true);
                                            }
                                        }),
                                    Forms\Components\Toggle::make('settings.visible_in_view')
                                        ->inline(false)
                                        ->label(__('custom-fields::custom-fields.field.form.visible_in_view'))
                                        ->afterStateHydrated(function (Forms\Components\Toggle $component, $state) {
                                            if (is_null($state)) {
                                                $component->state(true);
                                            }
                                        }),
                                    Forms\Components\Toggle::make('settings.list_toggleable_hidden')
                                        ->inline(false)
                                        ->label(__('custom-fields::custom-fields.field.form.list_toggleable_hidden'))
                                        ->helperText(__('custom-fields::custom-fields.field.form.list_toggleable_hidden_hint'))
                                        ->visible(fn (Utilities\Get $get): bool => $get('settings.visible_in_list') && Utils::isTableColumnsToggleableEnabled() && Utils::isTableColumnsToggleableUserControlEnabled())
                                        ->afterStateHydrated(function (Forms\Components\Toggle $component, $state) {
                                            if (is_null($state)) {
                                                $component->state(Utils::isTableColumnsToggleableHiddenByDefault());
                                            }
                                        }),
                                    // Data settings
                                    Forms\Components\Toggle::make('settings.searchable')
                                        ->inline(false)
                                        ->visible(fn (Utilities\Get $get): bool => CustomFieldType::searchables()->contains('value', $get('type')))
                                        ->disabled(fn (Utilities\Get $get): bool => $get('settings.encrypted') === true)
                                        ->label(__('custom-fields::custom-fields.field.form.searchable'))
                                        ->afterStateHydrated(function (Forms\Components\Toggle $component, $state) {
                                            if (is_null($state)) {
                                                $component->state(false);
                                            }
                                        }),
                                    Forms\Components\Toggle::make('settings.encrypted')
                                        ->inline(false)
                                        ->live()
                                        ->disabled(fn (?CustomField $record): bool => (bool) $record?->exists)
                                        ->label(__('custom-fields::custom-fields.field.form.encrypted'))
                                        ->visible(fn (Utilities\Get $get): bool => Utils::isValuesEncryptionFeatureEnabled() && CustomFieldType::encryptables()->contains('value', $get('type')))
                                        ->default(false),
                                    // Appearance settings
                                    Forms\Components\Toggle::make('settings.enable_option_colors')
                                        ->inline(false)
                                        ->live()
                                        ->label(__('custom-fields::custom-fields.field.form.enable_option_colors'))
                                        ->helperText(__('custom-fields::custom-fields.field.form.enable_option_colors_help'))
                                        ->visible(fn (Utilities\Get $get): bool => Utils::isSelectOptionColorsFeatureEnabled() &&
                                            in_array($get('type'), [CustomFieldType::SELECT->value, CustomFieldType::MULTI_SELECT->value])
                                        ),
                                ]),

                            Forms\Components\Select::make('options_lookup_type')
                                ->label(__('custom-fields::custom-fields.field.form.options_lookup_type.label'))
                                ->visible(fn (Utilities\Get $get): bool => in_array($get('type'), CustomFieldType::optionables()->pluck('value')->toArray()))
                                ->disabled(fn (?CustomField $record): bool => (bool) $record?->system_defined)
                                ->live()
                                ->options([
                                    'options' => __('custom-fields::custom-fields.field.form.options_lookup_type.options'),
                                    'lookup' => __('custom-fields::custom-fields.field.form.options_lookup_type.lookup'),
                                ])
                                ->afterStateHydrated(function (Forms\Components\Select $component, $state, $record): void {
                                    if (blank($state)) {
                                        $optionsLookupType = $record?->lookup_type ? 'lookup' : 'options';
                                        $component->state($optionsLookupType);
                                    }
                                })
                                ->afterStateUpdated(function (Forms\Components\Select $component, ?string $state, Utilities\Set $set, $record): void {
                                    if ($state === 'options') {
                                        $set('lookup_type', null, true, true);
                                    } else {
                                        $set('lookup_type', $record?->lookup_type ?? LookupTypeService::getDefaultOption());
                                    }
                                })
                                ->dehydrated(false)
                                ->required(),
                            Forms\Components\Select::make('lookup_type')
                                ->label(__('custom-fields::custom-fields.field.form.lookup_type.label'))
                                ->visible(fn (Utilities\Get $get): bool => $get('options_lookup_type') === 'lookup')
                                ->live()
                                ->options(LookupTypeService::getOptions())
                                ->default(LookupTypeService::getDefaultOption())
                                ->required(),
                            Forms\Components\Hidden::make('lookup_type'),
                            $optionsRepeater
                                ->label(__('custom-fields::custom-fields.field.form.options.label'))
                                ->visible(fn (Utilities\Get $get): bool => $get('options_lookup_type') === 'options' && in_array($get('type'), CustomFieldType::optionables()->pluck('value')->toArray())),
                        ]),
                    Tabs\Tab::make('Visibility')
                        ->visible(fn (): bool => Utils::isConditionalVisibilityFeatureEnabled())
                        ->schema([
                            VisibilityComponent::make(),
                        ]),
                    Tabs\Tab::make(__('custom-fields::custom-fields.field.form.validation.label'))
                        ->schema([
                            CustomFieldValidationComponent::make(),
                        ]),
                ])
                ->columns(2)
                ->columnSpanFull()
                ->contained(false),
        ];
    }
}
