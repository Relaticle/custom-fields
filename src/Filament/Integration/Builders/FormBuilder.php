<?php

// ABOUTME: Builder for creating Filament form schemas from custom fields
// ABOUTME: Handles form generation with sections, validation, and field dependencies

namespace Relaticle\CustomFields\Filament\Integration\Builders;

use Filament\Schemas\Components\Grid;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Filament\Integration\Factories\FieldComponentFactory;
use Relaticle\CustomFields\Filament\Integration\Factories\SectionComponentFactory;
use Relaticle\CustomFields\Models\CustomField;

class FormBuilder extends BaseBuilder
{
    public function build(): Grid
    {
        $fieldComponentFactory = app(FieldComponentFactory::class);
        $sectionComponentFactory = app(SectionComponentFactory::class);
        $components = [];
        $groupedFields = $this->groupFieldsBySection();
        $allFields = $this->getFilteredFields();

        // Get all dependent field codes for live updates
        $dependentFieldCodes = $this->getDependentFieldCodes($allFields);

        foreach ($groupedFields as $sectionId => $fields) {
            // Create section with fields
            $section = $fields->first()->section;
            $sectionComponent = $sectionComponentFactory->create($section);

            $sectionFields = [];
            foreach ($fields as $field) {
                $component = $fieldComponentFactory->create($field, $dependentFieldCodes, $allFields);
                if ($component) {
                    $sectionFields[] = $component;
                }
            }

            if (! empty($sectionFields)) {
                $sectionComponent->schema($sectionFields);
                $components[] = $sectionComponent;
            }
        }

        return Grid::make(1)->schema($components);
    }

    private function getDependentFieldCodes(Collection $fields): array
    {
        $dependentCodes = [];

        foreach ($fields as $field) {
            if ($field->visibility_conditions && is_array($field->visibility_conditions)) {
                foreach ($field->visibility_conditions as $condition) {
                    if (isset($condition['field'])) {
                        $dependentCodes[] = $condition['field'];
                    }
                }
            }
        }

        return array_unique($dependentCodes);
    }

    public function values(): Collection
    {
        $fieldComponentFactory = app(FieldComponentFactory::class);
        $allFields = $this->getFilteredFields();
        $dependentFieldCodes = $this->getDependentFieldCodes($allFields);

        return $allFields
            ->map(fn (CustomField $field) => $fieldComponentFactory->create($field, $dependentFieldCodes, $allFields))
            ->filter()
            ->values();
    }
}
