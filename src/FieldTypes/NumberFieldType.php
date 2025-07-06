<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\FieldTypes;

use Relaticle\CustomFields\Contracts\FieldTypeDefinitionInterface;
use Relaticle\CustomFields\Enums\CustomFieldValidationRule;
use Relaticle\CustomFields\Enums\FieldDataType;
use Relaticle\CustomFields\FieldTypes\Concerns\HasCommonFieldProperties;
use Relaticle\CustomFields\Filament\Integration\Forms\Components\NumberComponent;
use Relaticle\CustomFields\Filament\Integration\Infolists\Fields\TextEntry;
use Relaticle\CustomFields\Filament\Integration\Tables\Columns\TextColumn;

/**
 * ABOUTME: Field type definition for numeric input fields
 * ABOUTME: Provides number input functionality with validation for min/max values
 */
class NumberFieldType implements FieldTypeDefinitionInterface
{
    use HasCommonFieldProperties;

    public function getKey(): string
    {
        return 'number';
    }

    public function getLabel(): string
    {
        return 'Number';
    }

    public function getIcon(): string
    {
        return 'mdi-numeric';
    }

    public function getDataType(): FieldDataType
    {
        return FieldDataType::NUMERIC;
    }

    public function getFormComponentClass(): string
    {
        return NumberComponent::class;
    }

    public function getTableColumnClass(): string
    {
        return TextColumn::class;
    }

    public function getInfolistEntryClass(): string
    {
        return TextEntry::class;
    }

    public function getPriority(): int
    {
        return 20;
    }

    public function allowedValidationRules(): array
    {
        return [
            CustomFieldValidationRule::REQUIRED,
            CustomFieldValidationRule::MIN,
            CustomFieldValidationRule::MAX,
            CustomFieldValidationRule::UNIQUE,
        ];
    }
}
