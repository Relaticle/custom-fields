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
use Relaticle\CustomFields\Services\Visibility\BackendVisibilityService;

class InfolistBuilder extends BaseBuilder
{
    public function build(): Component
    {
        return Grid::make(1)->schema($this->values()->toArray());
    }

    /**
     * @return Collection<int, array{section: CustomFieldSection, fields: Collection}>
     */
    public function values(): Collection
    {
        $fieldInfolistsFactory = app(FieldInfolistsFactory::class);
        $sectionInfolistsFactory = app(SectionInfolistsFactory::class);

        $backendVisibilityService = app(BackendVisibilityService::class);

        return $this->getFilteredSections()
            ->map(function (CustomFieldSection $section) use ($fieldInfolistsFactory, $sectionInfolistsFactory, $backendVisibilityService) {
                // Filter fields to only those that should be visible based on conditional visibility
                $visibleFields = $backendVisibilityService->getVisibleFields($this->model, $section->fields);

                // Only create a section if it has visible fields
                if ($visibleFields->isEmpty()) {
                    return null;
                }

                return $sectionInfolistsFactory->create($section)->schema(
                    fn () => $visibleFields->map(fn (CustomField $customField) => $fieldInfolistsFactory->create($customField))->toArray()
                );
            })
            ->filter();
    }
}
