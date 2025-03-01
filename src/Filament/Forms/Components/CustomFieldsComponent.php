<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\FieldComponentFactory;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\SectionComponentFactory;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;

final class CustomFieldsComponent extends Component
{
    protected string $view = 'filament-forms::components.group';

    /**
     * @var array<int, Field>|null
     */
    protected ?array $cachedSchema = null;

    public function __construct(
        private readonly SectionComponentFactory $sectionComponentFactory,
        private readonly FieldComponentFactory $fieldComponentFactory
    ) {
        // Defer schema generation until we can safely access the record
        $this->schema(fn () => $this->getSchema());
    }

    /**
     * @return array<int, Field>
     */
    protected function getSchema(): array
    {
        if ($this->cachedSchema === null) {
            $this->cachedSchema = $this->generateSchema();
        }

        return $this->cachedSchema;
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
            ->with(['fields' => fn ($query) => $query->with('options', 'values')])
            ->forEntityType($this->getModel())
            ->orderBy('sort_order')
            ->get()
            ->map(function (CustomFieldSection $section) {
                return $this->sectionComponentFactory->create($section)->schema(
                    function () use ($section) {
                        return $section->fields
                            ->map(function (CustomField $customField) {
                                return $this->fieldComponentFactory->create($customField);
                            })
                            ->toArray();
                    }
                );
            })
            ->toArray();
    }
}
