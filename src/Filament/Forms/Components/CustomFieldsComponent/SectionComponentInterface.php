<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Relaticle\CustomFields\Models\CustomFieldSection;

interface SectionComponentInterface
{
    public function make(CustomFieldSection $customFieldSection): Section | Fieldset | Grid;
}
