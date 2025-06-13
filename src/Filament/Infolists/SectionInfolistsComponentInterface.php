<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Infolists;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Relaticle\CustomFields\Models\CustomFieldSection;

interface SectionInfolistsComponentInterface
{
    public function make(CustomFieldSection $customFieldSection): Section|Fieldset|Grid;
}
