<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms;

use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use InvalidArgumentException;
use Relaticle\CustomFields\Enums\CustomFieldSectionType;
use Relaticle\CustomFields\Models\CustomFieldSection;

final class SectionComponentFactory
{
    public function create(CustomFieldSection $customFieldSection): Section|Fieldset|Grid
    {
        return match ($customFieldSection->type) {
            CustomFieldSectionType::SECTION => Section::make($customFieldSection->name)
                ->description($customFieldSection->description)
                ->columns(12),
            CustomFieldSectionType::FIELDSET => Fieldset::make("custom_fields.{$customFieldSection->code}")
                ->label($customFieldSection->name)
                ->columns(12),
            CustomFieldSectionType::HEADLESS => Grid::make(12),
        };
    }
}
