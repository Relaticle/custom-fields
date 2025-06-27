<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Integration\Forms\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\FieldTypeUtils;

final readonly class DateComponent implements FieldComponentInterface
{
    public function __construct(private FieldConfigurator $configurator) {}

    /**
     * @param  array<string>  $dependentFieldCodes
     */
    public function make(CustomField $customField, array $dependentFieldCodes = [], ?Collection $allFields = null): Field
    {
        $field = DatePicker::make("custom_fields.{$customField->code}")
            ->native(FieldTypeUtils::isDatePickerNative())
            ->format(FieldTypeUtils::getDateFormat())
            ->displayFormat(FieldTypeUtils::getDateFormat())
            ->placeholder(FieldTypeUtils::getDateFormat());

        return $this->configurator->configure($field, $customField, $allFields, $dependentFieldCodes);
    }
}
