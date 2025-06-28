<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Models\CustomField;

final readonly class CheckboxComponent extends AbstractFieldComponent
{
    public function createField(CustomField $customField): Field
    {
        return Checkbox::make("custom_fields.{$customField->code}")->inline(false);
    }
}
