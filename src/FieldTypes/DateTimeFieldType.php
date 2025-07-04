<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\FieldTypes;

use Relaticle\CustomFields\Contracts\FieldTypeDefinitionInterface;
use Relaticle\CustomFields\Enums\FieldDataType;
use Relaticle\CustomFields\FieldTypes\Concerns\HasCommonFieldProperties;
use Relaticle\CustomFields\Filament\Integration\Forms\Components\DateTimeComponent;
use Relaticle\CustomFields\Filament\Integration\Infolists\Fields\DateTimeEntry;
use Relaticle\CustomFields\Filament\Integration\Tables\Columns\DateTimeColumn;

class DateTimeFieldType implements FieldTypeDefinitionInterface
{
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
        return FieldDataType::DATE_TIME;
    }

    public function getFormComponentClass(): string
    {
        return DateTimeComponent::class;
    }

    public function getTableColumnClass(): string
    {
        return DateTimeColumn::class;
    }

    public function getInfolistEntryClass(): string
    {
        return DateTimeEntry::class;
    }

    /**
     * Select fields have medium priority.
     */
    public function getPriority(): int
    {
        return 50;
    }
}
