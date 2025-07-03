<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Infolists;

use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Relaticle\CustomFields\Models\CustomFieldSection;

interface SectionInfolistsComponentInterface
{
    public function make(CustomFieldSection $customFieldSection): Section|Fieldset|Grid;
}
