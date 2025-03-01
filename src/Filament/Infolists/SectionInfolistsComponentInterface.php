<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Infolists;

use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Relaticle\CustomFields\Models\CustomFieldSection;

interface SectionInfolistsComponentInterface
{
    public function make(CustomFieldSection $customFieldSection): Section | Fieldset | Grid;
}
