<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\FieldTypes;

use Relaticle\CustomFields\Contracts\FieldTypeDefinitionInterface;
use Relaticle\CustomFields\Enums\FieldDataType;
use Relaticle\CustomFields\FieldTypes\Concerns\DelegatesValidationToDataType;
use Relaticle\CustomFields\FieldTypes\Concerns\HasCommonFieldProperties;
use Relaticle\CustomFields\Filament\Integration\Forms\Components\SelectComponent;
use Relaticle\CustomFields\Filament\Fields\Infolists\Entries\SelectEntry;
use Relaticle\CustomFields\Filament\Fields\Tables\Columns\SelectColumn;

class SelectFieldType implements FieldTypeDefinitionInterface
{
    use DelegatesValidationToDataType;
    use HasCommonFieldProperties;

    public function getKey(): string
    {
        return 'select';
    }

    public function getLabel(): string
    {
        return 'Select';
    }

    public function getIcon(): string
    {
        return 'mdi-form-select';
    }

    public function getDataType(): FieldDataType
    {
        return FieldDataType::SINGLE_CHOICE;
    }

    public function getFormComponentClass(): string
    {
        return SelectComponent::class;
    }

    public function getTableColumnClass(): string
    {
        return SelectColumn::class;
    }

    public function getInfolistEntryClass(): string
    {
        return SelectEntry::class;
    }

    /**
     * Select fields have medium priority.
     */
    public function getPriority(): int
    {
        return 50;
    }
}
