<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\FormSchemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Enums\CustomFieldSectionType;
use Relaticle\CustomFields\Support\Utils;

class SectionForm implements FormInterface, SectionFormInterface
{
    private static string $entityType;

    public static function entityType(string $entityType): self
    {
        self::$entityType = $entityType;

        return new self;
    }

    public static function schema(): array
    {
        return [
            Grid::make(12)->schema([
                TextInput::make('name')
                    ->label(__('custom-fields::custom-fields.section.form.name'))
                    ->required()
                    ->live(onBlur: true)
                    ->maxLength(50)
                    ->unique(
                        table: CustomFields::sectionModel(),
                        column: 'name',
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, Get $get) {
                            return $rule
                                ->when(
                                    Utils::isTenantEnabled(),
                                    fn(Unique $rule) => $rule->where(
                                        config('custom-fields.column_names.tenant_foreign_key'),
                                        Filament::getTenant()?->id
                                    )
                                )
                                ->where('entity_type', self::$entityType);
                        },
                    )
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state): void {
                        $old ??= '';
                        $state ??= '';

                        if (($get('code') ?? '') !== Str::of($old)->slug('_')->toString()) {
                            return;
                        }

                        $set('code', Str::of($state)->slug('_')->toString());
                    })
                    ->columnSpan(6),
                TextInput::make('code')
                    ->label(__('custom-fields::custom-fields.section.form.code'))
                    ->required()
                    ->alphaDash()
                    ->maxLength(50)
                    ->unique(
                        table: CustomFields::sectionModel(),
                        column: 'code',
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, Get $get) {
                            return $rule
                                ->when(
                                    Utils::isTenantEnabled(),
                                    fn(Unique $rule) => $rule
                                        ->where(
                                            config('custom-fields.column_names.tenant_foreign_key'),
                                            Filament::getTenant()?->id
                                        )
                                )->where('entity_type', self::$entityType);
                        },
                    )
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        $set('code', Str::of($state)->slug('_')->toString());
                    })
                    ->columnSpan(6),
                Select::make('type')
                    ->label(__('custom-fields::custom-fields.section.form.type'))
                    ->live()
                    ->default(CustomFieldSectionType::SECTION->value)
                    ->options(CustomFieldSectionType::class)
                    ->required()
                    ->columnSpan(12),
                Textarea::make('description')
                    ->label(__('custom-fields::custom-fields.section.form.description'))
                    ->reactive()
                    ->visible(fn(Get $get): bool => $get('type') === CustomFieldSectionType::SECTION)
                    ->maxLength(255)
                    ->nullable()
                    ->columnSpan(12),
            ]),
        ];
    }
}
