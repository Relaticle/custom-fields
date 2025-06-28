<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Relaticle\CustomFields\Models\CustomField;

final readonly class TextInputComponent extends AbstractFieldComponent
{
    public function createField(CustomField $customField): Field
    {
        return TextInput::make("custom_fields.{$customField->code}")
            ->maxLength(255)
            ->placeholder(null);
    }
}
