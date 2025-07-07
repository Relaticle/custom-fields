<?php

// ABOUTME: Builder for creating Filament table columns and filters from custom fields
// ABOUTME: Provides fluent API for generating table components with filtering support

namespace Relaticle\CustomFields\Filament\Integration\Builders;

use Filament\Tables\Filters\Filter;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Filament\Integration\Enums\ComponentContext;
use Relaticle\CustomFields\Filament\Integration\Factories\ComponentFactory;
use Relaticle\CustomFields\Models\CustomField;

class TableBuilder extends BaseBuilder
{
    public function columns(): Collection
    {
        return $this->getFilteredFields()
            ->map(function (CustomField $field) {
                return ComponentFactory::make($field, ComponentContext::TABLE);
            })
            ->filter()
            ->values();
    }

    public function filters(): Collection
    {
        return $this->getFilteredFields()
            ->filter(fn (CustomField $field) => $this->isFilterable($field))
            ->map(function (CustomField $field) {
                return $this->createFilter($field);
            })
            ->filter()
            ->values();
    }

    protected function isFilterable(CustomField $field): bool
    {
        // Only certain field types support filtering
        $filterableTypes = ['select', 'multi_select', 'ternary', 'checkbox', 'toggle'];

        return in_array($field->fieldType->type, $filterableTypes, true);
    }

    protected function createFilter(CustomField $field): ?Filter
    {
        // This will be implemented based on the existing filter logic
        // For now, returning null to be replaced with actual filter creation
        return null;
    }
}
