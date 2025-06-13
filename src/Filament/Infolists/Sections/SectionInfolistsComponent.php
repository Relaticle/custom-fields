<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Infolists\Sections;

use Filament\Schemas\Components\Section;
use Relaticle\CustomFields\Filament\Infolists\SectionInfolistsComponentInterface;
use Relaticle\CustomFields\Models\CustomFieldSection;

final readonly class SectionInfolistsComponent implements SectionInfolistsComponentInterface
{
    public function make(CustomFieldSection $customFieldSection): Section
    {
        return Section::make($customFieldSection->name)
            ->description($customFieldSection->description)
            ->columns(12);
    }
}
