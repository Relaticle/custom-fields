<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Infolists\Sections;

use Filament\Schemas\Components\Fieldset;
use Relaticle\CustomFields\Integration\Infolists\SectionInfolistsComponentInterface;
use Relaticle\CustomFields\Models\CustomFieldSection;

final readonly class FieldsetInfolistsComponent implements SectionInfolistsComponentInterface
{
    public function make(CustomFieldSection $customFieldSection): Fieldset
    {
        return Fieldset::make($customFieldSection->name)->columns(12);
    }
}
