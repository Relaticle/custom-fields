<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Relaticle\CustomFields\Models\CustomField;

final readonly class LinkComponent extends AbstractFormComponent
{
    public function create(CustomField $customField): Field
    {
        return TextInput::make("custom_fields.{$customField->code}")
            ->url();
    }
}
