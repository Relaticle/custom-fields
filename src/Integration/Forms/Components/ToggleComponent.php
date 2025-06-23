<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Toggle;
use Relaticle\CustomFields\Integration\Forms\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;

final readonly class ToggleComponent implements FieldComponentInterface
{
    public function __construct(private FieldConfigurator $configurator) {}

    /**
     * @param  array<string>  $dependentFieldCodes
     */
    public function make(CustomField $customField, array $dependentFieldCodes = []): Field
    {
        $field = Toggle::make("custom_fields.{$customField->code}")
            ->onColor('success')
            ->offColor('danger')
            ->inline(false);

        return $this->configurator->configure($field, $customField, $dependentFieldCodes);
    }
}
