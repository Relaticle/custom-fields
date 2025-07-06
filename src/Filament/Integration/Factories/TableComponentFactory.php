<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Factories;

use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\Filter;
use Relaticle\CustomFields\Contracts\Components\TableColumnInterface;
use Relaticle\CustomFields\Contracts\Components\TableFilterInterface;
use Relaticle\CustomFields\Models\CustomField;

/**
 * ABOUTME: Factory for creating table columns and filters from custom field definitions
 * ABOUTME: Maps field types to table component classes and configures display properties
 */
class TableComponentFactory extends AbstractComponentFactory
{
    /**
     * Component type constants
     */
    private const TYPE_COLUMN = 'column';
    private const TYPE_FILTER = 'filter';

    /**
     * The type of component to create
     */
    protected string $componentType = self::TYPE_COLUMN;

    /**
     * Create a table column for the given custom field
     *
     * @param  CustomField  $field
     * @return Column
     *
     * @throws UnsupportedFieldTypeException
     */
    public function createColumn(CustomField $field): Column
    {
        $this->componentType = self::TYPE_COLUMN;
        return $this->createComponent($field);
    }

    /**
     * Create a table filter for the given custom field
     *
     * @param  CustomField  $field
     * @return Filter
     *
     * @throws UnsupportedFieldTypeException
     */
    public function createFilter(CustomField $field): Filter
    {
        $this->componentType = self::TYPE_FILTER;
        return $this->createComponent($field);
    }

    /**
     * Get the component class for a specific field type
     *
     * @param  string  $fieldType
     * @return class-string|null
     */
    public function getComponentClass(string $fieldType): ?string
    {
        // Check custom components first
        $customKey = $fieldType . '.' . $this->componentType;
        if (isset($this->customComponents[$customKey])) {
            return $this->customComponents[$customKey];
        }

        // Get from registry based on component type
        return match ($this->componentType) {
            self::TYPE_COLUMN => $this->componentRegistry->getTableColumn($fieldType),
            self::TYPE_FILTER => $this->componentRegistry->getTableFilter($fieldType),
            default => null,
        };
    }

    /**
     * Register a custom column class for a field type
     *
     * @param  string  $fieldType
     * @param  class-string  $columnClass
     * @return void
     */
    public function registerColumnClass(string $fieldType, string $columnClass): void
    {
        $this->customComponents[$fieldType . '.column'] = $columnClass;
    }

    /**
     * Register a custom filter class for a field type
     *
     * @param  string  $fieldType
     * @param  class-string  $filterClass
     * @return void
     */
    public function registerFilterClass(string $fieldType, string $filterClass): void
    {
        $this->customComponents[$fieldType . '.filter'] = $filterClass;
    }

    /**
     * Get the expected interface that components must implement
     *
     * @return class-string
     */
    protected function getExpectedInterface(): string
    {
        return match ($this->componentType) {
            self::TYPE_COLUMN => TableColumnInterface::class,
            self::TYPE_FILTER => TableFilterInterface::class,
            default => throw new \LogicException("Invalid component type: {$this->componentType}"),
        };
    }

    /**
     * Configure the component with field-specific settings
     *
     * @param  mixed  $componentInstance
     * @param  CustomField  $field
     * @return Column|Filter
     */
    protected function configureComponent(mixed $componentInstance, CustomField $field): Column|Filter
    {
        return match ($this->componentType) {
            self::TYPE_COLUMN => $this->configureColumn($componentInstance, $field),
            self::TYPE_FILTER => $this->configureFilter($componentInstance, $field),
            default => throw new \LogicException("Invalid component type: {$this->componentType}"),
        };
    }

    /**
     * Configure a table column
     *
     * @param  TableColumnInterface  $componentInstance
     * @param  CustomField  $field
     * @return Column
     */
    protected function configureColumn(TableColumnInterface $componentInstance, CustomField $field): Column
    {
        $column = $componentInstance->make($field);

        // Apply common column configuration
        $this->applyCommonColumnConfiguration($column, $field);

        // Apply field-specific column configuration
        $this->applyFieldSpecificColumnConfiguration($column, $field);

        return $column;
    }

    /**
     * Configure a table filter
     *
     * @param  TableFilterInterface  $componentInstance
     * @param  CustomField  $field
     * @return Filter
     */
    protected function configureFilter(TableFilterInterface $componentInstance, CustomField $field): Filter
    {
        $filter = $componentInstance->make($field);

        // Apply common filter configuration
        $this->applyCommonFilterConfiguration($filter, $field);

        return $filter;
    }

    /**
     * Apply common configuration to all table columns
     *
     * @param  Column  $column
     * @param  CustomField  $field
     * @return void
     */
    protected function applyCommonColumnConfiguration(Column $column, CustomField $field): void
    {
        $column->label($field->label);

        // Get field configuration
        $config = $field->field_config ?? [];

        // Configure sortability
        if (isset($config['sortable'])) {
            $column->sortable($config['sortable']);
        }

        // Configure searchability
        if (isset($config['searchable'])) {
            $column->searchable($config['searchable']);
        }

        // Configure toggleability
        if (isset($config['toggleable'])) {
            $column->toggleable($config['toggleable']);
        }

        // Configure default hidden state
        if (isset($config['hidden'])) {
            $column->hidden($config['hidden']);
        }

        // Configure tooltip
        if ($field->help_text) {
            $column->tooltip($field->help_text);
        }

        // Configure alignment
        if (isset($config['alignment'])) {
            $column->alignment($config['alignment']);
        }

        // Configure wrapping
        if (isset($config['wrap'])) {
            $column->wrap($config['wrap']);
        }
    }

    /**
     * Apply field type specific configuration to columns
     *
     * @param  Column  $column
     * @param  CustomField  $field
     * @return void
     */
    protected function applyFieldSpecificColumnConfiguration(Column $column, CustomField $field): void
    {
        $config = $field->field_config ?? [];

        // Configure date/time formatting
        if (in_array($field->type->value, ['date', 'datetime', 'time'])) {
            if (isset($config['dateFormat'])) {
                $column->dateTime($config['dateFormat']);
            }
            if (isset($config['timezone'])) {
                $column->timezone($config['timezone']);
            }
        }

        // Configure numeric formatting
        if (in_array($field->type->value, ['number', 'currency'])) {
            if (isset($config['decimalPlaces'])) {
                $column->numeric($config['decimalPlaces']);
            }
            if ($field->type->value === 'currency' && isset($config['currency'])) {
                $column->money($config['currency']);
            }
        }

        // Configure boolean display
        if (in_array($field->type->value, ['boolean', 'toggle'])) {
            if (isset($config['trueIcon'])) {
                $column->trueIcon($config['trueIcon']);
            }
            if (isset($config['falseIcon'])) {
                $column->falseIcon($config['falseIcon']);
            }
            if (isset($config['trueColor'])) {
                $column->trueColor($config['trueColor']);
            }
            if (isset($config['falseColor'])) {
                $column->falseColor($config['falseColor']);
            }
        }

        // Configure badge display for select fields
        if (in_array($field->type->value, ['select', 'multiselect', 'tags'])) {
            if (isset($config['badge']) && $config['badge']) {
                $column->badge();
            }
            if (isset($config['colors'])) {
                $column->colors($config['colors']);
            }
        }

        // Configure color display
        if ($field->type->value === 'color') {
            if (isset($config['copyable'])) {
                $column->copyable($config['copyable']);
            }
        }

        // Configure character limit for text fields
        if (in_array($field->type->value, ['text', 'textarea', 'richtext', 'markdown'])) {
            if (isset($config['characterLimit'])) {
                $column->limit($config['characterLimit']);
            }
            if (isset($config['words'])) {
                $column->words($config['words']);
            }
        }

        // Configure HTML rendering
        if (in_array($field->type->value, ['richtext', 'markdown']) && isset($config['html']) && $config['html']) {
            $column->html();
        }

        // Configure URL opening
        if ($field->type->value === 'url' && isset($config['openExternally']) && $config['openExternally']) {
            $column->openUrlInNewTab();
        }
    }

    /**
     * Apply common configuration to all table filters
     *
     * @param  Filter  $filter
     * @param  CustomField  $field
     * @return void
     */
    protected function applyCommonFilterConfiguration(Filter $filter, CustomField $field): void
    {
        $filter->label($field->label);

        // Get field configuration
        $config = $field->field_config ?? [];

        // Configure default state
        if (isset($config['defaultFilterValue'])) {
            $filter->default($config['defaultFilterValue']);
        }
    }
}