<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\FieldTypes;

use Relaticle\CustomFields\Contracts\FieldTypeDefinitionInterface;
use Relaticle\CustomFields\Enums\CustomFieldValidationRule;
use Relaticle\CustomFields\Enums\FieldDataType;
use Relaticle\CustomFields\FieldTypes\Concerns\HasCommonFieldProperties;
use Relaticle\CustomFields\Filament\Integration\Forms\Components\MultiSelectComponent;
use Relaticle\CustomFields\Filament\Integration\Infolists\Fields\MultipleValuesEntry;
use Relaticle\CustomFields\Filament\Integration\Tables\Columns\MultipleValuesColumn;
use Relaticle\CustomFields\Filament\Integration\Tables\Filters\MultiSelectFilter;

/**
 * ABOUTME: Field type definition for Multi Select fields
 * ABOUTME: Provides Multi Select functionality with appropriate validation rules
 */
class MultiSelectFieldType implements FieldTypeDefinitionInterface
{
    use HasCommonFieldProperties;

    public function getKey(): string
    {
        return 'multi_select';
    }

    public function getLabel(): string
    {
        return 'Multi Select';
    }

    public function getIcon(): string
    {
        return 'mdi-form-dropdown';
    }

    public function getDataType(): FieldDataType
    {
        return FieldDataType::MULTI_CHOICE;
    }

    public function getFormComponentClass(): string
    {
        return MultiSelectComponent::class;
    }

    public function getTableColumnClass(): string
    {
        return MultipleValuesColumn::class;
    }

    public function getTableFilterClass(): ?string
    {
        return MultiSelectFilter::class;
    }

    public function getInfolistEntryClass(): string
    {
        return MultipleValuesEntry::class;
    }

    public function getPriority(): int
    {
        return 42;
    }

    public function allowedValidationRules(): array
    {
        return [
            CustomFieldValidationRule::REQUIRED,
            CustomFieldValidationRule::MIN_OPTIONS,
            CustomFieldValidationRule::MAX_OPTIONS,
        ];
    }
}
