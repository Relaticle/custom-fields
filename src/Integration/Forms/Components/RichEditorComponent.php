<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\RichEditor;
use Relaticle\CustomFields\Models\CustomField;

final readonly class RichEditorComponent extends AbstractFieldComponent
{
    public function createField(CustomField $customField): Field
    {
        return RichEditor::make("custom_fields.{$customField->code}");
    }
}
