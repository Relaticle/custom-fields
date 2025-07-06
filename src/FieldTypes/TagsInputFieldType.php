<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\FieldTypes;

use Relaticle\CustomFields\Contracts\FieldTypeDefinitionInterface;
use Relaticle\CustomFields\Enums\CustomFieldValidationRule;
use Relaticle\CustomFields\Enums\FieldDataType;
use Relaticle\CustomFields\FieldTypes\Concerns\HasCommonFieldProperties;
use Relaticle\CustomFields\Filament\Integration\Forms\Components\TagsInputComponent;
use Relaticle\CustomFields\Filament\Integration\Infolists\Fields\MultipleValuesEntry;
use Relaticle\CustomFields\Filament\Integration\Tables\Columns\MultipleValuesColumn;
use Relaticle\CustomFields\Filament\Integration\Tables\Filters\MultiSelectFilter;

/**
 * ABOUTME: Field type definition for Tags Input fields
 * ABOUTME: Provides Tags Input functionality with appropriate validation rules
 */
class TagsInputFieldType implements FieldTypeDefinitionInterface
{
    use HasCommonFieldProperties;

    public function getKey(): string
    {
        return 'tags_input';
    }

    public function getLabel(): string
    {
        return 'Tags Input';
    }

    public function getIcon(): string
    {
        return 'mdi-tag-multiple';
    }

    public function getDataType(): FieldDataType
    {
        return FieldDataType::MULTI_CHOICE;
    }

    public function getFormComponentClass(): string
    {
        return TagsInputComponent::class;
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
        return 70;
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
