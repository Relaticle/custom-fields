<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Relaticle\CustomFields\Models\CustomField;

final readonly class TextareaFieldComponent extends AbstractFieldComponent
{
    public function createField(CustomField $customField): Field
    {
        return Textarea::make("custom_fields.{$customField->code}")
            ->rows(3)
            ->maxLength(50000)
            ->placeholder(null);
    }
}
