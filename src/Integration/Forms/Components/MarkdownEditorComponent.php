<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\MarkdownEditor;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Integration\Forms\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;

final readonly class MarkdownEditorComponent implements FieldComponentInterface
{
    public function __construct(private FieldConfigurator $configurator) {}

    /**
     * @param  array<string>  $dependentFieldCodes
     *
     * @throws \Exception
     */
    public function make(CustomField $customField, array $dependentFieldCodes = [], ?Collection $allFields = null): Field
    {
        $field = MarkdownEditor::make("custom_fields.{$customField->code}");

        return $this->configurator->configure($field, $customField, $dependentFieldCodes, $allFields);
    }
}
