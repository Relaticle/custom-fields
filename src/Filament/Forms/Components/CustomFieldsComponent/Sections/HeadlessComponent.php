<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\Sections;

use Filament\Schemas\Components\Grid;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\SectionComponentInterface;
use Relaticle\CustomFields\Models\CustomFieldSection;

final readonly class HeadlessComponent implements SectionComponentInterface
{
    public function make(CustomFieldSection $customFieldSection): Grid
    {
        return Grid::make($customFieldSection->name)->columns(12);
    }
}
