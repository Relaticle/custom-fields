<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Toggle;
use Relaticle\CustomFields\Models\CustomField;

final readonly class ToggleComponent extends AbstractFormComponent
{
    public function create(CustomField $customField): Field
    {
        return Toggle::make("custom_fields.{$customField->code}")
            ->onColor('success')
            ->offColor('danger')
            ->inline(false);
    }
}
