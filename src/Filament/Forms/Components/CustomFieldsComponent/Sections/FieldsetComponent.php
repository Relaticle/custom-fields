<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\Sections;

use Filament\Schemas\Components\Fieldset;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\SectionComponentInterface;
use Relaticle\CustomFields\Models\CustomFieldSection;

final readonly class FieldsetComponent implements SectionComponentInterface
{
    public function make(CustomFieldSection $customFieldSection): Fieldset
    {
        return Fieldset::make("custom_fields.{$customFieldSection->code}")
            ->label($customFieldSection->name)
            ->columns(12);
    }
}
