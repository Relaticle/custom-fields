<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Forms\Components;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\FieldTypeUtils;

final readonly class DateComponent extends AbstractFormComponent
{
    public function create(CustomField $customField): Field
    {
        return DatePicker::make("custom_fields.{$customField->code}")
            ->native(FieldTypeUtils::isDatePickerNative())
            ->format(FieldTypeUtils::getDateFormat())
            ->displayFormat(FieldTypeUtils::getDateFormat())
            ->placeholder(FieldTypeUtils::getDateFormat());
    }
}
