<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Infolists\Sections;

use Filament\Schemas\Components\Grid;
use Relaticle\CustomFields\Filament\Infolists\SectionInfolistsComponentInterface;
use Relaticle\CustomFields\Models\CustomFieldSection;

final readonly class HeadlessInfolistsComponent implements SectionInfolistsComponentInterface
{
    public function make(CustomFieldSection $customFieldSection): Grid
    {
        return Grid::make()->columns(12);
    }
}
