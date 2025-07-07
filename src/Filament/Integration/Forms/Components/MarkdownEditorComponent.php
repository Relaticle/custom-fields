<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\MarkdownEditor;
use Relaticle\CustomFields\Models\CustomField;

final readonly class MarkdownEditorComponent extends AbstractFormComponent
{
    public function create(CustomField $customField): Field
    {
        return MarkdownEditor::make($this->getFieldName($customField));
    }
}
