<?php

// ABOUTME: Builder for creating Filament infolist schemas from custom fields
// ABOUTME: Generates read-only views of custom field data with section support

namespace Relaticle\CustomFields\Filament\Integration\Builders;

use Filament\Infolists\Components\Section;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Filament\Integration\Factories\FieldInfolistsFactory;
use Relaticle\CustomFields\Filament\Integration\Factories\SectionInfolistsFactory;
use Relaticle\CustomFields\Models\CustomField;

class InfolistBuilder extends BaseBuilder
{
    public function build(): array
    {
        $fieldInfolistsFactory = app(FieldInfolistsFactory::class);
        $sectionInfolistsFactory = app(SectionInfolistsFactory::class);
        $components = [];
        $groupedFields = $this->groupFieldsBySection();

        foreach ($groupedFields as $sectionKey => $fields) {
            if ($sectionKey === 'unsectioned') {
                // Add unsectioned fields directly
                foreach ($fields as $field) {
                    $component = $fieldInfolistsFactory->create($field);
                    if ($component) {
                        $components[] = $component;
                    }
                }
            } else {
                // Create section with fields
                $section = $fields->first()->section;
                $sectionComponent = $sectionInfolistsFactory->create($section);

                $sectionEntries = [];
                foreach ($fields as $field) {
                    $component = $fieldInfolistsFactory->create($field);
                    if ($component) {
                        $sectionEntries[] = $component;
                    }
                }

                if (! empty($sectionEntries)) {
                    $sectionComponent->schema($sectionEntries);
                    $components[] = $sectionComponent;
                }
            }
        }

        return $components;
    }

    public function values(): Collection
    {
        $fieldInfolistsFactory = app(FieldInfolistsFactory::class);

        return $this->getFilteredFields()
            ->map(fn (CustomField $field) => $fieldInfolistsFactory->create($field))
            ->filter()
            ->values();
    }
}
