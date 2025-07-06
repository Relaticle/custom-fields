<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Builders;

use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Relaticle\CustomFields\Filament\Integration\Factories\TableComponentFactory;
use Relaticle\CustomFields\Filament\Integration\Services\FieldRepository;
use Relaticle\CustomFields\Filament\Integration\Services\VisibilityResolver;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;

/**
 * ABOUTME: Builder for creating Filament table columns and filters from custom fields
 * ABOUTME: Provides separate methods for columns() and filters() with filtering support
 */
class TableBuilder extends CustomFieldsBuilder
{
    /**
     * Create a new table builder instance
     */
    public function __construct(
        FieldRepository $fieldRepository,
        TableComponentFactory $factory,
        VisibilityResolver $visibilityResolver
    ) {
        parent::__construct($fieldRepository, $factory, $visibilityResolver);
    }
    /**
     * Whether to include only filterable fields when getting filters
     *
     * @var bool
     */
    protected bool $onlyFilterable = true;

    /**
     * Whether to include only fields marked as visible in table
     *
     * @var bool
     */
    protected bool $onlyVisibleInTable = true;

    /**
     * Get table columns
     *
     * @return Collection<int, Column>
     */
    public function columns(): Collection
    {
        $fields = $this->getTableFields();

        return $fields->map(function (CustomField $field) {
            return $this->createColumnForField($field);
        })->filter()->values();
    }

    /**
     * Get table filters
     *
     * @return Collection<int, BaseFilter>
     */
    public function filters(): Collection
    {
        $fields = $this->getFilterableFields();

        return $fields->map(function (CustomField $field) {
            return $this->createFilterForField($field);
        })->filter()->values();
    }

    /**
     * Get all components (columns and filters)
     *
     * @return Collection
     */
    public function components(): Collection
    {
        return collect([
            'columns' => $this->columns(),
            'filters' => $this->filters(),
        ]);
    }

    /**
     * Build and return array suitable for table schema
     *
     * @return array{columns: array, filters: array}
     */
    public function build(): array
    {
        return [
            'columns' => $this->columns()->toArray(),
            'filters' => $this->filters()->toArray(),
        ];
    }

    /**
     * Include all fields, not just those marked as visible in table
     *
     * @return $this
     */
    public function includeHiddenColumns(): static
    {
        $this->onlyVisibleInTable = false;

        return $this;
    }

    /**
     * Include non-filterable fields in filters
     *
     * @return $this
     */
    public function includeNonFilterableFields(): static
    {
        $this->onlyFilterable = false;

        return $this;
    }

    /**
     * Get fields for table columns
     *
     * @return Collection<int, CustomField>
     */
    protected function getTableFields(): Collection
    {
        if ($this->onlyVisibleInTable) {
            $fields = $this->fieldRepository->getTableFields($this->getEntityType());
        } else {
            $fields = $this->fieldRepository->getFields(
                $this->getEntityType(),
                $this->onlyFields,
                $this->exceptFields
            );
        }

        return $this->filterFields($fields);
    }

    /**
     * Get fields for filters
     *
     * @return Collection<int, CustomField>
     */
    protected function getFilterableFields(): Collection
    {
        if ($this->onlyFilterable) {
            $fields = $this->fieldRepository->getFilterableFields($this->getEntityType());
        } else {
            $fields = $this->fieldRepository->getFields(
                $this->getEntityType(),
                $this->onlyFields,
                $this->exceptFields
            );
        }

        return $this->filterFields($fields);
    }

    /**
     * Create a table column for a custom field
     *
     * @param  CustomField  $field
     * @return Column|null
     */
    protected function createColumnForField(CustomField $field): ?Column
    {
        try {
            $component = $this->factory->createComponent($field);

            if (! $component instanceof Column) {
                return null;
            }

            // Apply additional column configuration
            $this->configureColumn($component, $field);

            return $component;
        } catch (\Exception $e) {
            logger()->error('Failed to create table column for field', [
                'field' => $field->code,
                'type' => $field->type,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create a table filter for a custom field
     *
     * @param  CustomField  $field
     * @return BaseFilter|null
     */
    protected function createFilterForField(CustomField $field): ?BaseFilter
    {
        try {
            // Use a different factory method for filters
            if (method_exists($this->factory, 'createFilter')) {
                $component = $this->factory->createFilter($field);
            } else {
                // Fallback to generic component creation
                $component = $this->factory->createComponent($field);
            }

            if (! $component instanceof BaseFilter) {
                return null;
            }

            return $component;
        } catch (\Exception $e) {
            logger()->error('Failed to create table filter for field', [
                'field' => $field->code,
                'type' => $field->type,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Configure additional column properties
     *
     * @param  Column  $column
     * @param  CustomField  $field
     * @return void
     */
    protected function configureColumn(Column $column, CustomField $field): void
    {
        $config = $field->field_config ?? [];

        // Apply column-specific configuration
        if (isset($config['columnWidth'])) {
            $column->width($config['columnWidth']);
        }

        if (isset($config['columnAlignment'])) {
            $column->alignment($config['columnAlignment']);
        }

        if (isset($config['columnWrap']) && $config['columnWrap']) {
            $column->wrap();
        }

        if (isset($config['columnTooltip'])) {
            $column->tooltip($config['columnTooltip']);
        }

        if (isset($config['columnLimit'])) {
            $column->limit($config['columnLimit']);
        }

        // Apply default visibility
        if (isset($config['columnHiddenByDefault']) && $config['columnHiddenByDefault']) {
            $column->toggleable(isToggledHiddenByDefault: true);
        }
    }

    /**
     * Create component for field (required by abstract parent)
     *
     * @param  CustomField  $field
     * @return mixed
     */
    protected function createComponentForField(CustomField $field): mixed
    {
        // This method is not used directly in TableBuilder
        // We use createColumnForField and createFilterForField instead
        return $this->createColumnForField($field);
    }
}