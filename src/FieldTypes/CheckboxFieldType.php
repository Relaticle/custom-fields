<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\FieldTypes;

use Relaticle\CustomFields\Contracts\FieldTypeDefinitionInterface;
use Relaticle\CustomFields\Enums\CustomFieldValidationRule;
use Relaticle\CustomFields\Enums\FieldDataType;
use Relaticle\CustomFields\FieldTypes\Concerns\HasCommonFieldProperties;
use Relaticle\CustomFields\Filament\Integration\Forms\Components\CheckboxComponent;
use Relaticle\CustomFields\Filament\Integration\Infolists\Fields\BooleanEntry;
use Relaticle\CustomFields\Filament\Integration\Tables\Columns\BooleanColumn;
use Relaticle\CustomFields\Filament\Integration\Tables\Filters\BooleanFilter;

/**
 * ABOUTME: Field type definition for Checkbox fields
 * ABOUTME: Provides Checkbox functionality with appropriate validation rules
 */
class CheckboxFieldType implements FieldTypeDefinitionInterface
{
    use HasCommonFieldProperties;

    public function getKey(): string
    {
        return 'checkbox';
    }

    public function getLabel(): string
    {
        return 'Checkbox';
    }

    public function getIcon(): string
    {
        return 'mdi-checkbox-marked';
    }

    public function getDataType(): FieldDataType
    {
        return FieldDataType::BOOLEAN;
    }

    public function getFormComponentClass(): string
    {
        return CheckboxComponent::class;
    }

    public function getTableColumnClass(): string
    {
        return BooleanColumn::class;
    }

    public function getTableFilterClass(): ?string
    {
        return BooleanFilter::class;
    }

    public function getInfolistEntryClass(): string
    {
        return BooleanEntry::class;
    }

    public function getPriority(): int
    {
        return 50;
    }

    public function allowedValidationRules(): array
    {
        return [
            CustomFieldValidationRule::REQUIRED,
        ];
    }
}
