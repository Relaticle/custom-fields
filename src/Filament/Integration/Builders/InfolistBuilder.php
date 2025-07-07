<?php

// ABOUTME: Builder for creating Filament infolist schemas from custom fields
// ABOUTME: Generates read-only views of custom field data with section support

namespace Relaticle\CustomFields\Filament\Integration\Builders;

use Filament\Infolists\Components\Section;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Filament\Integration\Components\Infolists\Sections\SectionComponent;
use Relaticle\CustomFields\Filament\Integration\Enums\ComponentContext;
use Relaticle\CustomFields\Filament\Integration\Factories\ComponentFactory;
use Relaticle\CustomFields\Models\CustomField;

class InfolistBuilder extends BaseBuilder
{
    public function build(): array
    {
        $components = [];
        $groupedFields = $this->groupFieldsBySection();

        foreach ($groupedFields as $sectionKey => $fields) {
            if ($sectionKey === 'unsectioned') {
                // Add unsectioned fields directly
                foreach ($fields as $field) {
                    $component = ComponentFactory::make($field, ComponentContext::INFOLIST);
                    if ($component) {
                        $components[] = $component;
                    }
                }
            } else {
                // Create section with fields
                $section = $fields->first()->sectionCustomFields->first()->section;
                $sectionComponent = SectionComponent::make($section);

                $sectionEntries = [];
                foreach ($fields as $field) {
                    $component = ComponentFactory::make($field, ComponentContext::INFOLIST);
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
        return $this->getFilteredFields()
            ->map(function (CustomField $field) {
                return ComponentFactory::make($field, ComponentContext::INFOLIST);
            })
            ->filter()
            ->values();
    }
}
