<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\FormSchemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Facades\Filament;
use Filament\Forms;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldValidationComponent;
use Relaticle\CustomFields\Filament\Forms\Components\TypeField;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\EntityTypeService;
use Relaticle\CustomFields\Services\LookupTypeService;
use Relaticle\CustomFields\Support\Utils;

class FieldForm implements FormInterface
{
    public static function schema(bool $withOptionsRelationship = true): array
    {
        $optionsRepeater = Repeater::make('options')
            ->simple(
                TextInput::make('name')
                    ->columnSpanFull()
                    ->required()
                    ->unique(
                        table: CustomFields::optionModel(),
                        column: 'name',
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, Get $get) {
                            $recordId = $get('../../id');

                            return $rule->where('custom_field_id', $recordId)
                                ->when(
                                    Utils::isTenantEnabled(),
                                    function (Unique $rule) {
                                        return $rule->where(
                                            config('custom-fields.column_names.tenant_foreign_key'),
                                            Filament::getTenant()?->getKey()
                                        );
                                    }
                                );
                        },
                    )
            )
            ->columns(2)
            ->requiredUnless('type', CustomFieldType::TAGS_INPUT->value)
            ->hiddenLabel()
            ->defaultItems(1)
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
                    Tab::make(__('custom-fields::custom-fields.field.form.general'))
                        ->schema([
                            Select::make('entity_type')
                                ->label(__('custom-fields::custom-fields.field.form.entity_type'))
                                ->options(EntityTypeService::getOptions())
                                ->disabled()
                                ->default(fn() => request('entityType', EntityTypeService::getDefaultOption()))
                                ->required(),
                            TypeField::make('type')
                                ->label(__('custom-fields::custom-fields.field.form.type'))
                                ->disabled(fn(?CustomField $record): bool => (bool)$record?->exists)
                                ->reactive()
                                ->required(),
                            TextInput::make('name')
                                ->label(__('custom-fields::custom-fields.field.form.name'))
                                ->helperText(__('custom-fields::custom-fields.field.form.name_helper_text'))
                                ->live(onBlur: true)
                                ->required()
                                ->maxLength(50)
                                ->disabled(fn(?CustomField $record): bool => (bool)$record?->system_defined)
                                ->unique(
                                    table: CustomFields::customFieldModel(),
                                    column: 'name',
                                    ignoreRecord: true,
                                    modifyRuleUsing: function (Unique $rule, Get $get) {
                                        return $rule->where('entity_type', $get('entity_type'))
                                            ->when(
                                                Utils::isTenantEnabled(),
                                                function (Unique $rule) {
                                                    return $rule->where(
                                                        config('custom-fields.column_names.tenant_foreign_key'),
                                                        Filament::getTenant()?->getKey()
                                                    );
                                                });
                                    },
                                )
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state): void {
                                    $old ??= '';
                                    $state ??= '';

                                    if (($get('code') ?? '') !== Str::of($old)->slug('_')->toString()) {
                                        return;
                                    }

                                    $set('code', Str::of($state)->slug('_')->toString());
                                }),
                            TextInput::make('code')
                                ->label(__('custom-fields::custom-fields.field.form.code'))
                                ->helperText(__('custom-fields::custom-fields.field.form.code_helper_text'))
                                ->live(onBlur: true)
                                ->required()
                                ->alphaDash()
                                ->maxLength(50)
                                ->disabled(fn(?CustomField $record): bool => (bool)$record?->system_defined)
                                ->unique(
                                    table: CustomFields::customFieldModel(),
                                    column: 'code',
                                    ignoreRecord: true,
                                    modifyRuleUsing: function (Unique $rule, Get $get) {
                                        return $rule->where('entity_type', $get('entity_type'))
                                            ->when(
                                                Utils::isTenantEnabled(),
                                                function (Unique $rule) {
                                                    return $rule->where(
                                                        config('custom-fields.column_names.tenant_foreign_key'),
                                                        Filament::getTenant()?->getKey()
                                                    );
                                                });
                                    },
                                )
                                ->afterStateUpdated(function (Set $set, ?string $state): void {
                                    $set('code', Str::of($state)->slug('_')->toString());
                                }),
                            Fieldset::make(__('custom-fields::custom-fields.field.form.settings'))
                                ->columns(3)
                                ->schema([
                                    Toggle::make('settings.encrypted')
                                        ->inline(false)
                                        ->reactive()
                                        ->disabled(fn(?CustomField $record): bool => (bool)$record?->exists)
                                        ->label(__('custom-fields::custom-fields.field.form.encrypted'))
                                        ->visible(fn(Get $get): bool => Utils::isValuesEncryptionFeatureEnabled() && CustomFieldType::encryptables()->contains('value', $get('type')))
                                        ->default(false),
                                    Toggle::make('settings.searchable')
                                        ->inline(false)
                                        ->visible(fn(Get $get): bool => CustomFieldType::searchables()->contains('value', $get('type')))
                                        ->disabled(fn(Get $get): bool => $get('settings.encrypted') === true)
                                        ->label(__('custom-fields::custom-fields.field.form.searchable'))
                                        ->afterStateHydrated(function (Toggle $component, $state) {
                                            if (is_null($state)) {
                                                $component->state(false);
                                            }
                                        }),
                                    Toggle::make('settings.visible_in_list')
                                        ->inline(false)
                                        ->reactive()
                                        ->label(__('custom-fields::custom-fields.field.form.visible_in_list'))
                                        ->afterStateHydrated(function (Toggle $component, $state) {
                                            if (is_null($state)) {
                                                $component->state(true);
                                            }
                                        }),
                                    Toggle::make('settings.list_toggleable_hidden')
                                        ->inline(false)
                                        ->label(__('custom-fields::custom-fields.field.form.list_toggleable_hidden'))
                                        ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('custom-fields::custom-fields.field.form.list_toggleable_hidden_hint'))
                                        ->visible(fn(Get $get): bool => $get('settings.visible_in_list') && Utils::isTableColumnsToggleableEnabled() && Utils::isTableColumnsToggleableUserControlEnabled())
                                        ->afterStateHydrated(function (Toggle $component, $state) {
                                            if (is_null($state)) {
                                                $component->state(Utils::isTableColumnsToggleableHiddenByDefault());
                                            }
                                        }),
                                    Toggle::make('settings.visible_in_view')
                                        ->inline(false)
                                        ->label(__('custom-fields::custom-fields.field.form.visible_in_view'))
                                        ->afterStateHydrated(function (Toggle $component, $state) {
                                            if (is_null($state)) {
                                                $component->state(true);
                                            }
                                        }),
                                ]),

                            Select::make('options_lookup_type')
                                ->label(__('custom-fields::custom-fields.field.form.options_lookup_type.label'))
                                ->visible(fn(Get $get): bool => in_array($get('type'), CustomFieldType::optionables()->pluck('value')->toArray()))
                                ->disabled(fn(?CustomField $record): bool => (bool)$record?->system_defined)
                                ->reactive()
                                ->options([
                                    'options' => __('custom-fields::custom-fields.field.form.options_lookup_type.options'),
                                    'lookup' => __('custom-fields::custom-fields.field.form.options_lookup_type.lookup'),
                                ])
                                ->afterStateHydrated(function (Select $component, $state, $record): void {
                                    if (blank($state)) {
                                        $optionsLookupType = $record?->lookup_type ? 'lookup' : 'options';
                                        $component->state($optionsLookupType);
                                    }
                                })
                                ->afterStateUpdated(function (Select $component, ?string $state, Set $set, $record): void {
                                    if ($state === 'options') {
                                        $set('lookup_type', null);
                                    }else{
                                        $set('lookup_type', $record?->lookup_type ?? LookupTypeService::getDefaultOption());
                                    }
                                })
                                ->dehydrated(false)
                                ->required(),
                            Select::make('lookup_type_options')
                                ->label(__('custom-fields::custom-fields.field.form.lookup_type.label'))
                                ->disabled(fn(?CustomField $record): bool => (bool)$record?->system_defined)
                                ->visible(fn(Get $get): bool => $get('options_lookup_type') === 'lookup')
                                ->reactive()
                                ->afterStateUpdated(function (Select $component, ?string $state, Set $set): void {
                                    $set('lookup_type', $state);
                                })
                                ->afterStateHydrated(function (Select $component, $state, $record): void {
                                    if (blank($state)) {
                                        $component->state($record?->lookup_type ?? LookupTypeService::getDefaultOption());
                                    }
                                })
                                ->dehydrated(false)
                                ->options(LookupTypeService::getOptions())
                                ->default(LookupTypeService::getDefaultOption())
                                ->required(),
                            Hidden::make('lookup_type'),
                            Fieldset::make('options')
                                ->label(__('custom-fields::custom-fields.field.form.options.label'))
                                ->visible(fn(Get $get): bool => $get('options_lookup_type') === 'options' && in_array($get('type'), CustomFieldType::optionables()->pluck('value')->toArray()))
                                ->schema([
                                    $optionsRepeater,
                                ]),
                        ]),
                    Tab::make(__('custom-fields::custom-fields.field.form.validation.label'))
                        ->visible(fn(Get $get): bool => !empty($get('type')))
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
