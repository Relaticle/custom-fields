<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TagsInput;
use Relaticle\CustomFields\Integration\Forms\Components\Traits\ConfiguresLookups;
use Relaticle\CustomFields\Models\CustomField;

final readonly class TagsInputComponent extends AbstractFieldComponent
{
    use ConfiguresLookups;

    public function createField(CustomField $customField): Field
    {
        $field = TagsInput::make("custom_fields.{$customField->code}");

        // Get suggestions from lookup or field options
        $suggestions = $this->getFieldOptions($customField);
        $field->suggestions($suggestions);

        return $field;
    }
}
