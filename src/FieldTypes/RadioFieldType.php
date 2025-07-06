<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\FieldTypes;

use Relaticle\CustomFields\Contracts\FieldTypeDefinitionInterface;
use Relaticle\CustomFields\Enums\CustomFieldValidationRule;
use Relaticle\CustomFields\Enums\FieldDataType;
use Relaticle\CustomFields\FieldTypes\Concerns\HasCommonFieldProperties;
use Relaticle\CustomFields\Filament\Integration\Forms\Components\RadioComponent;
use Relaticle\CustomFields\Filament\Integration\Infolists\Fields\SingleValueEntry;
use Relaticle\CustomFields\Filament\Integration\Tables\Columns\SingleValueColumn;
use Relaticle\CustomFields\Filament\Integration\Tables\Filters\SelectFilter;

/**
 * ABOUTME: Field type definition for Radio fields
 * ABOUTME: Provides Radio functionality with appropriate validation rules
 */
class RadioFieldType implements FieldTypeDefinitionInterface
{
    use HasCommonFieldProperties;

    public function getKey(): string
    {
        return 'radio';
    }

    public function getLabel(): string
    {
        return 'Radio';
    }

    public function getIcon(): string
    {
        return 'mdi-radiobox-marked';
    }

    public function getDataType(): FieldDataType
    {
        return FieldDataType::SINGLE_CHOICE;
    }

    public function getFormComponentClass(): string
    {
        return RadioComponent::class;
    }

    public function getTableColumnClass(): string
    {
        return SingleValueColumn::class;
    }

    public function getTableFilterClass(): ?string
    {
        return SelectFilter::class;
    }

    public function getInfolistEntryClass(): string
    {
        return SingleValueEntry::class;
    }

    public function getPriority(): int
    {
        return 45;
    }

    public function allowedValidationRules(): array
    {
        return [
            CustomFieldValidationRule::REQUIRED,
        ];
    }
}
