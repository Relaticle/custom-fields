<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Factories;

use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use InvalidArgumentException;
use Relaticle\CustomFields\Enums\CustomFieldSectionType;
use Relaticle\CustomFields\Models\CustomFieldSection;

final class SectionInfolistsFactory
{
    public function create(CustomFieldSection $customFieldSection): Section|Fieldset|Grid
    {
        return match ($customFieldSection->type) {
            CustomFieldSectionType::SECTION => Section::make($customFieldSection->label)
                ->description($customFieldSection->description),

            CustomFieldSectionType::FIELDSET => Fieldset::make($customFieldSection->label),

            CustomFieldSectionType::HEADLESS => Grid::make($customFieldSection->column_span ?? 1),

            default => throw new InvalidArgumentException("No section infolists component registered for custom field type: {$customFieldSection->type->value}")
        };
    }
}
