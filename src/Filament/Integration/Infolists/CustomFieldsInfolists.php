<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Infolists;

use Filament\Infolists\Components\Entry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Filament\Integration\Factories\FieldInfolistsFactory;
use Relaticle\CustomFields\Filament\Integration\Factories\SectionInfolistsFactory;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Services\Visibility\BackendVisibilityService;

final class CustomFieldsInfolists extends Component
{
    protected string $view = 'filament-schemas::components.grid';

    public function __construct(
        private readonly SectionInfolistsFactory $sectionInfolistsFactory,
        private readonly FieldInfolistsFactory $fieldInfolistsFactory,
        private readonly BackendVisibilityService $visibilityService
    ) {
        // Defer schema generation until we can safely access the record
        $this->schema(fn (): array => $this->generateSchema());
    }

    public static function make(): static
    {
        return app(self::class);
    }

    /**
     * @return array<int, Component>
     */
    private function generateSchema(): array
    {
        $record = $this->getRecord();

        if (! $record || ! is_object($record)) {
            return [];
        }

        // Ensure custom field values are properly loaded
        $record->load('customFieldValues.customField');

        $sections = CustomFields::newSectionModel()->query()
            ->with(['fields' => fn ($query) => $query->visibleInView()])
            ->forEntityType($record::class)
            ->orderBy('sort_order')
            ->get();

        return $sections
            ->map(function (CustomFieldSection $section) use ($record): null|Section|Fieldset|Grid {
                // Filter fields to only those that should be visible based on conditional visibility
                $visibleFields = $this->visibilityService->getVisibleFields($record, $section->fields);

                // Only create section if it has visible fields
                if ($visibleFields->isEmpty()) {
                    return null;
                }

                return $this->sectionInfolistsFactory->create($section)->schema(
                    fn () => $visibleFields->map(fn (CustomField $customField): Entry => $this->fieldInfolistsFactory->create($customField)->name('custom_fields_'.$customField->code))->toArray()
                );
            })
            ->filter() // Remove null entries (sections with no visible fields)
            ->toArray();
    }
}
