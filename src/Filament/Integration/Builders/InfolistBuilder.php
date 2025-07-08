<?php

// ABOUTME: Builder for creating Filament infolist schemas from custom fields
// ABOUTME: Generates read-only views of custom field data with section support

namespace Relaticle\CustomFields\Filament\Integration\Builders;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Filament\Integration\Factories\FieldInfolistsFactory;
use Relaticle\CustomFields\Filament\Integration\Factories\SectionInfolistsFactory;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;

class InfolistBuilder extends BaseBuilder
{
    public function build(): Component
    {
        $fieldInfolistsFactory = app(FieldInfolistsFactory::class);
        $sectionInfolistsFactory = app(SectionInfolistsFactory::class);
        $components = [];
        $groupedFields = $this->groupFieldsBySection();

        foreach ($groupedFields as $fields) {
            // Create section with fields
            $section = $fields->first()->section;
            $sectionComponent = $sectionInfolistsFactory->create($section);

            $sectionEntries = [];
            foreach ($fields as $field) {
                $sectionEntries[] = $fieldInfolistsFactory->create($field);
            }

            if (! empty($sectionEntries)) {
                $sectionComponent->schema($sectionEntries);
                $components[] = $sectionComponent;
            }
        }

        return Grid::make(1)->schema($components);
    }

    /**
     * @return Collection<int, array{section: CustomFieldSection, fields: Collection}>
     */
    public function values(): Collection
    {
        $fieldInfolistsFactory = app(FieldInfolistsFactory::class);
        $groupedFields = $this->groupFieldsBySection();
        $sections = collect();

        foreach ($groupedFields as $fields) {
            $section = $fields->first()->section;
            $sectionData = [
                'section' => $section,
                'fields' => $fields->map(fn (CustomField $field) => $fieldInfolistsFactory->create($field))
                    ->filter()
                    ->values(),
            ];

            if ($sectionData['fields']->isNotEmpty()) {
                $sections->push($sectionData);
            }
        }

        return $sections;
    }
}
