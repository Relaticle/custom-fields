<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Services\ConditionalVisibilityService;

final class CustomFieldsForm extends Component
{
    protected string $view = 'filament-schemas::components.grid';

    /**
     * @var array<int, Field>|null
     */
    protected ?array $cachedSchema = null;

    public function __construct(
        private readonly SectionComponentFactory $sectionComponentFactory,
        private readonly FieldComponentFactory $fieldComponentFactory,
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

        $sections = CustomFields::newSectionModel()->query()
            ->with(['fields' => fn ($query) => $query->with('options', 'values')])
            ->forEntityType($this->getModel())
            ->orderBy('sort_order')
            ->get();

        // Calculate field dependencies for all fields across all sections
        $allFields = $sections->flatMap(fn ($section) => $section->fields);
        $fieldDependencies = app(ConditionalVisibilityService::class)->calculateFieldDependencies($allFields);

        return $sections->map(function (CustomFieldSection $section) use ($fieldDependencies) {
            return $this->sectionComponentFactory->create($section)->schema(
                function () use ($section, $fieldDependencies) {
                    return $section->fields
                        ->map(function (CustomField $customField) use ($fieldDependencies) {
                            // Get fields that depend on this field (makes it live)
                            $dependentFields = $fieldDependencies[$customField->code] ?? [];

                            return $this->fieldComponentFactory->create($customField, $dependentFields);
                        })
                        ->toArray();
                }
            );
        })->toArray();
    }
}
