<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\Fields;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Toggle;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\FieldComponentInterface;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;

final readonly class ToggleComponent implements FieldComponentInterface
{
    public function __construct(private FieldConfigurator $configurator) {}

    public function make(CustomField $customField): Field
    {
        $field = Toggle::make("custom_fields.{$customField->code}")
            ->onColor('success')
            ->offColor('danger')
            ->inline(false);

        return $this->configurator->configure($field, $customField);
    }
}
