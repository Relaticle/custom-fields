<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Forms\Components;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Models\CustomField;

final readonly class CheckboxComponent extends AbstractFormComponent
{
    public function create(CustomField $customField): Field
    {
        return Checkbox::make("custom_fields.{$customField->code}")->inline(false);
    }
}
