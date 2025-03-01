<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\FormSchemas;

use Filament\Facades\Filament;
use Filament\Forms;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Relaticle\CustomFields\Enums\CustomFieldSectionType;
use Relaticle\CustomFields\Models\CustomFieldSection;
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
            Forms\Components\Grid::make(12)->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('custom-fields::custom-fields.section.form.name'))
                    ->required()
                    ->live(onBlur: true)
                    ->maxLength(50)
                    ->unique(
                        table: CustomFieldSection::class,
                        column: 'name',
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, Forms\Get $get) {
                            return $rule->when(
                                Utils::isTenantEnabled(),
                                fn (Unique $rule) => $rule
                                    ->where(
                                        config('custom-fields.column_names.tenant_foreign_key'),
                                        Filament::getTenant()?->id
                                    )
                                    ->where('entity_type', self::$entityType)
                            );
                        },
                    )
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $old, ?string $state): void {
                        $old ??= '';
                        $state ??= '';

                        if (($get('code') ?? '') !== Str::of($old)->slug('_')->toString()) {
                            return;
                        }

                        $set('code', Str::of($state)->slug('_')->toString());
                    })
                    ->columnSpan(6),
                Forms\Components\TextInput::make('code')
                    ->label(__('custom-fields::custom-fields.section.form.code'))
                    ->required()
                    ->alphaDash()
                    ->maxLength(50)
                    ->unique(
                        table: CustomFieldSection::class,
                        column: 'code',
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, Forms\Get $get) {
                            return $rule->when(
                                Utils::isTenantEnabled(),
                                fn (Unique $rule) => $rule
                                    ->where(
                                        config('custom-fields.column_names.tenant_foreign_key'),
                                        Filament::getTenant()?->id
                                    )
                                    ->where('entity_type', self::$entityType)
                            );
                        },
                    )
                    ->afterStateUpdated(function (Forms\Set $set, ?string $state): void {
                        $set('code', Str::of($state)->slug('_')->toString());
                    })
                    ->columnSpan(6),
                Forms\Components\Select::make('type')
                    ->label(__('custom-fields::custom-fields.section.form.type'))
                    ->reactive()
                    ->default(CustomFieldSectionType::SECTION->value)
                    ->options(CustomFieldSectionType::class)
                    ->required()
                    ->columnSpan(12),
                Forms\Components\Textarea::make('description')
                    ->label(__('custom-fields::custom-fields.section.form.description'))
                    ->visible(fn (Forms\Get $get): bool => $get('type') === CustomFieldSectionType::SECTION->value)
                    ->maxLength(255)
                    ->nullable()
                    ->columnSpan(12),
            ]),
        ];
    }
}
