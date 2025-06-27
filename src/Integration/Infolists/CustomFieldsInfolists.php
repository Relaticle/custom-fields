<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Infolists;

use Filament\Schemas\Components\Component;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Services\Visibility\BackendVisibilityService;

final class CustomFieldsInfolists extends Component
{
    protected string $view = 'filament-schemas::components.grid';

    public function __construct(
        private readonly SectionInfolistsFactory  $sectionInfolistsFactory,
        private readonly FieldInfolistsFactory    $fieldInfolistsFactory,
        private readonly BackendVisibilityService $visibilityService
    )
    {
        // Defer schema generation until we can safely access the record
        $this->schema(fn() => $this->generateSchema());
    }

    public static function make(): static
    {
        return app(self::class);
    }

    /**
     * @return array<int, Component>
     */
    protected function generateSchema(): array
    {
        $record = $this->getRecord();

        if (!$record) {
            return [];
        }

        // Ensure custom field values are properly loaded
        $record->load('customFieldValues.customField');

        $sections = CustomFields::newSectionModel()->query()
            ->with(['fields' => fn($query) => $query->visibleInView()])
            ->forEntityType($record::class)
            ->orderBy('sort_order')
            ->get();

        return $sections
            ->map(function (CustomFieldSection $section) use ($record) {
                // Filter fields to only those that should be visible based on conditional visibility
                $visibleFields = $this->visibilityService->getVisibleFields($record, $section->fields);

                // Only create section if it has visible fields
                if ($visibleFields->isEmpty()) {
                    return null;
                }

                return $this->sectionInfolistsFactory->create($section)->schema(
                    function () use ($visibleFields) {
                        return $visibleFields->map(function (CustomField $customField) {
                            return $this->fieldInfolistsFactory->create($customField)->name('custom_fields_'.$customField->code);
                        })->toArray();
                    }
                );
            })
            ->filter() // Remove null entries (sections with no visible fields)
            ->toArray();
    }
}
