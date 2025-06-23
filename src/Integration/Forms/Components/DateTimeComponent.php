<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Integration\Forms\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\FieldTypeUtils;

final readonly class DateTimeComponent implements FieldComponentInterface
{
    public function __construct(private FieldConfigurator $configurator) {}

    /**
     * @param  array<string>  $dependentFieldCodes
     */
    public function make(CustomField $customField, array $dependentFieldCodes = []): Field
    {
        $field = DateTimePicker::make("custom_fields.{$customField->code}")
            ->native(FieldTypeUtils::isDateTimePickerNative())
            ->format(FieldTypeUtils::getDateTimeFormat())
            ->displayFormat(FieldTypeUtils::getDateTimeFormat())
            ->placeholder(FieldTypeUtils::getDateTimeFormat());

        return $this->configurator->configure($field, $customField, $dependentFieldCodes);
    }
}
