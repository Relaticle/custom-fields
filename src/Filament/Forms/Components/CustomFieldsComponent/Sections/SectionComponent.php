<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\Sections;

use Filament\Schemas\Components\Section;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\SectionComponentInterface;
use Relaticle\CustomFields\Models\CustomFieldSection;

final readonly class SectionComponent implements SectionComponentInterface
{
    public function make(CustomFieldSection $customFieldSection): Section
    {
        return Section::make($customFieldSection->name)
            ->description($customFieldSection->description)
            ->columns(12);
    }
}
