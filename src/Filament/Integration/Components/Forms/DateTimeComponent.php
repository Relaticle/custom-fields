<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Forms;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\FieldTypeUtils;

final readonly class DateTimeComponent extends AbstractFormComponent
{

    public function create(CustomField $customField): Field
    {
        return DateTimePicker::make($this->getFieldName($customField))
            ->native(FieldTypeUtils::isDateTimePickerNative())
            ->format(FieldTypeUtils::getDateTimeFormat())
            ->displayFormat(FieldTypeUtils::getDateTimeFormat())
            ->placeholder(FieldTypeUtils::getDateTimeFormat());
    }
}
