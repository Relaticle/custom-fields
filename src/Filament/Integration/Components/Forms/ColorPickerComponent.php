<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Forms;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Models\CustomField;

final readonly class ColorPickerComponent extends AbstractFormComponent
{

    public function create(CustomField $customField): Field
    {
        return ColorPicker::make($this->getFieldName($customField));
    }
}
