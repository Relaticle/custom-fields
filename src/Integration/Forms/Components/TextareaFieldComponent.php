<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Relaticle\CustomFields\Integration\Forms\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;

final readonly class TextareaFieldComponent implements FieldComponentInterface
{
    public function __construct(private FieldConfigurator $configurator) {}

    /**
     * @param  array<string>  $dependentFieldCodes
     */
    public function make(CustomField $customField, array $dependentFieldCodes = []): Field
    {
        $field = Textarea::make("custom_fields.{$customField->code}")
            ->rows(3)
            ->maxLength(50000)
            ->placeholder(null);

        return $this->configurator->configure($field, $customField, $dependentFieldCodes);
    }
}
