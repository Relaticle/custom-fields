<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Integration\Forms\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;

final readonly class CheckboxComponent implements FieldComponentInterface
{
    public function __construct(private FieldConfigurator $configurator) {}

    public function make(CustomField $customField): Field
    {
        $field = Checkbox::make("custom_fields.{$customField->code}")->inline(false);

        return $this->configurator->configure($field, $customField);
    }
}
