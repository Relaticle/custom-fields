<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Relaticle\CustomFields\Models\CustomFieldSection;

interface SectionComponentInterface
{
    public function make(CustomFieldSection $customFieldSection): Section|Fieldset|Grid;
}
