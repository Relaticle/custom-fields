<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Infolists;

use Filament\Forms\Components\Field;
use Filament\Infolists\Components\Component;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;

final class CustomFieldsInfolists extends Component
{
    protected string $view = 'filament-forms::components.group';

    public function __construct(
        private readonly SectionInfolistsFactory $sectionInfolistsFactory,
        private readonly FieldInfolistsFactory $fieldInfolistsFactory
    ) {
        // Defer schema generation until we can safely access the record
        $this->schema(fn () => $this->generateSchema());
    }

    public static function make(): static
    {
        return app(self::class);
    }

    /**
     * @return array<int, Field>
     */
    protected function generateSchema(): array
    {
        $this->getRecord()?->load('customFieldValues.customField');

        return CustomFieldSection::query()
            ->with(['fields' => fn ($query) => $query->visibleInView()])
            ->forEntityType($this->getRecord()::class)
            ->orderBy('sort_order')
            ->get()
            ->map(function (CustomFieldSection $section) {
                return $this->sectionInfolistsFactory->create($section)->schema(
                    function () use ($section) {
                        return $section->fields->map(function (CustomField $customField) {
                            return $this->fieldInfolistsFactory->create($customField);
                        })->toArray();
                    }
                );
            })
            ->toArray();
    }
}
