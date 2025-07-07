<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Forms;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\FieldTypeUtils;
final readonly class DateComponent extends AbstractFormComponent
{

    public function create(CustomField $customField): Field
    {
        return DatePicker::make($this->getFieldName($customField))
            ->native(FieldTypeUtils::isDatePickerNative())
            ->format(FieldTypeUtils::getDateFormat())
            ->displayFormat(FieldTypeUtils::getDateFormat())
            ->placeholder(FieldTypeUtils::getDateFormat());
    }
}
