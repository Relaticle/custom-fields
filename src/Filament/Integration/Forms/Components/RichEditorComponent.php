<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\RichEditor;
use Relaticle\CustomFields\Models\CustomField;

final readonly class RichEditorComponent extends AbstractFormComponent
{
    public function create(CustomField $customField): Field
    {
        return RichEditor::make($this->getFieldName($customField));
    }
}
