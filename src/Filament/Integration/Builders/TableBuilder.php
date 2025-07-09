<?php

// ABOUTME: Builder for creating Filament table columns and filters from custom fields
// ABOUTME: Provides fluent API for generating table components with filtering support

namespace Relaticle\CustomFields\Filament\Integration\Builders;

use Illuminate\Support\Collection;
use Relaticle\CustomFields\Filament\Integration\Factories\FieldColumnFactory;
use Relaticle\CustomFields\Filament\Integration\Factories\FieldFilterFactory;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\Utils;

class TableBuilder extends BaseBuilder
{
    public function columns(): Collection
    {
        if (! Utils::isTableColumnsEnabled()) {
            return collect();
        }

        $fieldColumnFactory = app(FieldColumnFactory::class);

        return $this->getFilteredSections()
            ->flatMap(fn ($section) => $section->fields)
            ->map(fn (CustomField $field) => $fieldColumnFactory->create($field))
            ->values();
    }

    public function filters(): Collection
    {
        if (! Utils::isTableFiltersEnabled()) {
            return collect();
        }

        $fieldFilterFactory = app(FieldFilterFactory::class);

        return $this->getFilteredSections()
            ->flatMap(fn ($section) => $section->fields)
            ->filter(fn (CustomField $field): bool => $field->isFilterable())
            ->map(fn (CustomField $field) => $fieldFilterFactory->create($field))
            ->filter()
            ->values();
    }
}
