<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\FieldTypes;

use Relaticle\CustomFields\Contracts\FieldTypeDefinitionInterface;
use Relaticle\CustomFields\Enums\FieldDataType;
use Relaticle\CustomFields\FieldTypes\Concerns\DelegatesValidationToDataType;
use Relaticle\CustomFields\FieldTypes\Concerns\HasCommonFieldProperties;
use Relaticle\CustomFields\Filament\Fields\Forms\SelectInput;
use Relaticle\CustomFields\Filament\Fields\Infolists\Entries\SelectEntry;
use Relaticle\CustomFields\Filament\Fields\Tables\Columns\SelectColumn;

class DateTimeFieldType implements FieldTypeDefinitionInterface
{
    use DelegatesValidationToDataType;
    use HasCommonFieldProperties;

    public function getKey(): string
    {
        return 'datetime';
    }

    public function getLabel(): string
    {
        return 'Date and Time';
    }

    public function getIcon(): string
    {
        return 'mdi-calendar-clock';
    }

    public function getDataType(): FieldDataType
    {
        return FieldDataType::SINGLE_CHOICE;
    }

    public function getFormComponentClass(): string
    {
        return SelectInput::class;
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
