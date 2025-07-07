<?php

// ABOUTME: Builder for creating Filament form schemas from custom fields
// ABOUTME: Handles form generation with sections, validation, and field dependencies

namespace Relaticle\CustomFields\Filament\Integration\Builders;

use Filament\Forms\Components\Section;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Filament\Integration\Components\Forms\SectionComponent;
use Relaticle\CustomFields\Filament\Integration\Enums\ComponentContext;
use Relaticle\CustomFields\Filament\Integration\Factories\ComponentFactory;
use Relaticle\CustomFields\Models\CustomField;

class FormBuilder extends BaseBuilder
{
    public function build(): array
    {
        $components = [];
        $groupedFields = $this->groupFieldsBySection();

        foreach ($groupedFields as $sectionKey => $fields) {
            if ($sectionKey === 'unsectioned') {
                // Add unsectioned fields directly
                foreach ($fields as $field) {
                    $component = ComponentFactory::make($field, ComponentContext::FORM);
                    if ($component) {
                        $components[] = $component;
                    }
                }
            } else {
                // Create section with fields
                $section = $fields->first()->sectionCustomFields->first()->section;
                $sectionComponent = SectionComponent::make($section);

                $sectionFields = [];
                foreach ($fields as $field) {
                    $component = ComponentFactory::make($field, ComponentContext::FORM);
                    if ($component) {
                        $sectionFields[] = $component;
                    }
                }

                if (! empty($sectionFields)) {
                    $sectionComponent->schema($sectionFields);
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
                return ComponentFactory::make($field, ComponentContext::FORM);
            })
            ->filter()
            ->values();
    }
}
