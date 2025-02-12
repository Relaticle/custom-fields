<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\FormSchemas;

use Filament\Facades\Filament;
use Filament\Forms;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
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
        $optionsRepeater = Forms\Components\Repeater::make('options')
            ->simple(
                Forms\Components\TextInput::make('name')
                    ->columnSpanFull()
                    ->required(),
            )
            ->columns(2)
            ->requiredUnless('type', CustomFieldType::TAGS_INPUT->value)
            ->hiddenLabel()
            ->defaultItems(1)
            ->addActionLabel(__('custom-fields::custom-fields.field.form.options.add'))
            ->reorderable()
            ->orderColumn('sort_order')
            ->columnSpanFull()
            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                if (Utils::isTenantEnabled()) {
                    $data[config('custom-fields.column_names.tenant_foreign_key')] = Filament::getTenant()?->id;
                }

                return $data;
            });

        if ($withOptionsRelationship) {
            $optionsRepeater = $optionsRepeater->relationship();
        }

        return [
            Forms\Components\Tabs::make()
                ->tabs([
                    Forms\Components\Tabs\Tab::make(__('custom-fields::custom-fields.field.form.general'))
                        ->schema([
                            Forms\Components\Select::make('entity_type')
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
                            Forms\Components\TextInput::make('name')
                                ->label(__('custom-fields::custom-fields.field.form.name'))
                                ->helperText(__('custom-fields::custom-fields.field.form.name_helper_text'))
                                ->live(onBlur: true)
                                ->required()
                                ->maxLength(50)
                                ->unique(
                                    table: CustomField::class,
                                    column: 'name',
                                    ignoreRecord: true,
                                    modifyRuleUsing: function (Unique $rule, Forms\Get $get) {
                                        return $rule->where('entity_type', $get('entity_type'))
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
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $old, ?string $state): void {
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
                                ->unique(
                                    table: CustomField::class,
                                    column: 'code',
                                    ignoreRecord: true,
                                    modifyRuleUsing: function (Unique $rule, Forms\Get $get) {
                                        return $rule->where('entity_type', $get('entity_type'))
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
                                ->afterStateUpdated(function (Forms\Set $set, ?string $state): void {
                                    $set('code', Str::of($state)->slug('_')->toString());
                                }),
                            Forms\Components\Fieldset::make(__('custom-fields::custom-fields.field.form.settings'))
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Toggle::make('settings.visible_in_list')
                                        ->inline(false)
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
                                    Forms\Components\Toggle::make('settings.encrypted')
                                        ->inline(false)
                                        ->disabled(fn(?CustomField $record): bool => (bool)$record?->exists)
                                        ->label(__('custom-fields::custom-fields.field.form.encrypted'))
                                        ->visible(fn(Forms\Get $get): bool => Utils::isValuesEncryptionFeatureEnabled() && CustomFieldType::encryptables()->contains('value', $get('type')))
                                        ->default(false),
                                ]),

                            Forms\Components\Select::make('options_lookup_type')
                                ->label(__('custom-fields::custom-fields.field.form.options_lookup_type.label'))
                                ->visible(fn(Forms\Get $get): bool => in_array($get('type'), CustomFieldType::optionables()->pluck('value')->toArray()))
                                ->reactive()
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
                                ->dehydrated(false)
                                ->required(),
                            Forms\Components\Select::make('lookup_type')
                                ->label(__('custom-fields::custom-fields.field.form.lookup_type.label'))
                                ->visible(fn(Forms\Get $get): bool => $get('options_lookup_type') === 'lookup')
                                ->reactive()
                                ->options(LookupTypeService::getOptions())
                                ->default(LookupTypeService::getDefaultOption())
                                ->required(),
                            Forms\Components\Fieldset::make('options')
                                ->label(__('custom-fields::custom-fields.field.form.options.label'))
                                ->visible(fn(Forms\Get $get): bool => $get('options_lookup_type') === 'options' && in_array($get('type'), CustomFieldType::optionables()->pluck('value')->toArray()))
                                ->schema([
                                    $optionsRepeater
                                ])
                        ]),
                    Forms\Components\Tabs\Tab::make(__('custom-fields::custom-fields.field.form.validation.label'))
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
