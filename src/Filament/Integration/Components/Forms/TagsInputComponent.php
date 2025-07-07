<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Forms;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TagsInput;
use Relaticle\CustomFields\Filament\Integration\Concerns\Forms\ConfiguresLookups;
use Relaticle\CustomFields\Models\CustomField;

final readonly class TagsInputComponent extends AbstractFormComponent
{
    use ConfiguresLookups;

    public function create(CustomField $customField): Field
    {
        $field = TagsInput::make($this->getFieldName($customField));

        // Get suggestions from lookup or field options
        $suggestions = $this->getFieldOptions($customField);
        $field->suggestions($suggestions);

        return $field;
    }
}
