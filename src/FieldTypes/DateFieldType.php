<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\FieldTypes;

use Relaticle\CustomFields\Contracts\FieldTypeDefinitionInterface;
use Relaticle\CustomFields\Enums\CustomFieldValidationRule;
use Relaticle\CustomFields\Enums\FieldDataType;
use Relaticle\CustomFields\FieldTypes\Concerns\HasCommonFieldProperties;
use Relaticle\CustomFields\Filament\Integration\Forms\Components\DatePickerComponent;
use Relaticle\CustomFields\Filament\Integration\Infolists\Fields\TextEntry;
use Relaticle\CustomFields\Filament\Integration\Tables\Columns\DateTimeColumn;
use Relaticle\CustomFields\Filament\Integration\Tables\Filters\DateFilter;

/**
 * ABOUTME: Field type definition for Date fields
 * ABOUTME: Provides Date functionality with appropriate validation rules
 */
class DateFieldType implements FieldTypeDefinitionInterface
{
    use HasCommonFieldProperties;

    public function getKey(): string
    {
        return 'date';
    }

    public function getLabel(): string
    {
        return 'Date';
    }

    public function getIcon(): string
    {
        return 'mdi-calendar';
    }

    public function getDataType(): FieldDataType
    {
        return FieldDataType::DATE;
    }

    public function getFormComponentClass(): string
    {
        return DatePickerComponent::class;
    }

    public function getTableColumnClass(): string
    {
        return DateTimeColumn::class;
    }

    public function getTableFilterClass(): ?string
    {
        return DateFilter::class;
    }

    public function getInfolistEntryClass(): string
    {
        return TextEntry::class;
    }

    public function getPriority(): int
    {
        return 30;
    }

    public function allowedValidationRules(): array
    {
        return [
            CustomFieldValidationRule::REQUIRED,
            CustomFieldValidationRule::MIN_DATE,
            CustomFieldValidationRule::MAX_DATE,
        ];
    }
}
