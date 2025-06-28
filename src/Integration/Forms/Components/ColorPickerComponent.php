<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Models\CustomField;

final readonly class ColorPickerComponent extends AbstractFieldComponent
{
    public function createField(CustomField $customField): Field
    {
        return ColorPicker::make("custom_fields.{$customField->code}");
    }
}
