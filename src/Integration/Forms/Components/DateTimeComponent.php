<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\FieldTypeUtils;

final readonly class DateTimeComponent extends AbstractFieldComponent
{
    public function createField(CustomField $customField): Field
    {
        return DateTimePicker::make("custom_fields.{$customField->code}")
            ->native(FieldTypeUtils::isDateTimePickerNative())
            ->format(FieldTypeUtils::getDateTimeFormat())
            ->displayFormat(FieldTypeUtils::getDateTimeFormat())
            ->placeholder(FieldTypeUtils::getDateTimeFormat());
    }
}
