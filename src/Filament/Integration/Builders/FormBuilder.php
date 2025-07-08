<?php

// ABOUTME: Builder for creating Filament form schemas from custom fields
// ABOUTME: Handles form generation with sections, validation, and field dependencies

namespace Relaticle\CustomFields\Filament\Integration\Builders;

use Filament\Schemas\Components\Grid;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Filament\Integration\Factories\FieldComponentFactory;
use Relaticle\CustomFields\Filament\Integration\Factories\SectionComponentFactory;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;

class FormBuilder extends BaseBuilder
{
    public function build(): Grid
    {
        return Grid::make(1)->schema($this->values()->toArray());
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
        $sectionComponentFactory = app(SectionComponentFactory::class);

        $allFields = $this->getFilteredSections()->flatMap(fn($section) => $section->fields);
        $dependentFieldCodes = $this->getDependentFieldCodes($allFields);

        return $this->getFilteredSections()
            ->map(function (CustomFieldSection $section) use ($sectionComponentFactory, $fieldComponentFactory, $dependentFieldCodes, $allFields) {
                return $sectionComponentFactory->create($section)->schema(
                    function () use ($section, $fieldComponentFactory, $dependentFieldCodes, $allFields) {
                        return $section->fields->map(function (CustomField $customField) use ($fieldComponentFactory, $dependentFieldCodes, $allFields) {
                            return $fieldComponentFactory->create($customField, $dependentFieldCodes, $allFields);
                        })->toArray();
                    }
                );
            });
    }
}
