<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Base;

use Filament\Tables\Columns\Column;
use Relaticle\CustomFields\Contracts\Components\TableColumnInterface;
use Relaticle\CustomFields\Filament\Integration\Components\Concerns\ConfiguresVisibility;
use Relaticle\CustomFields\Filament\Integration\Components\Concerns\HasCustomFieldState;
use Relaticle\CustomFields\Models\CustomField;

/**
 * ABOUTME: Abstract base class for all custom field table column components
 * ABOUTME: Provides common functionality for table column configuration and state display
 */
abstract class CustomFieldColumn implements TableColumnInterface
{
    use ConfiguresVisibility;
    use HasCustomFieldState;

    /**
     * Create and configure a table column component
     */
    public function make(CustomField $customField): Column
    {
        // Create the specific column component
        $column = $this->createColumn($customField);

        // Configure common properties
        $this->configureColumn($column, $customField);

        // Apply column-specific configuration
        $this->applyColumnSpecificConfiguration($column, $customField);

        return $column;
    }

    /**
     * Create the specific Filament column component
     */
    abstract protected function createColumn(CustomField $customField): Column;

    /**
     * Apply column-specific configuration
     */
    abstract protected function applyColumnSpecificConfiguration(Column $column, CustomField $customField): void;

    /**
     * Configure common column properties
     */
    protected function configureColumn(Column $column, CustomField $customField): void
    {
        // Basic configuration
        $column->label($customField->label);

        // Configure state retrieval
        $column->getStateUsing(fn ($record) => $this->resolveState($record, $customField));

        // Get field configuration
        $config = $customField->field_config ?? [];

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
        if (isset($config['hidden']) && $config['hidden']) {
            $column->hidden();
        }

        // Configure tooltip
        if ($customField->help_text) {
            $column->tooltip($customField->help_text);
        }

        // Configure alignment
        if (isset($config['alignment'])) {
            $column->alignment($config['alignment']);
        }

        // Configure wrapping
        if (isset($config['wrap'])) {
            $column->wrap($config['wrap']);
        }

        // Configure width
        if (isset($config['width'])) {
            $column->width($config['width']);
        }

        // Configure extra attributes
        if (isset($config['extraAttributes']) && is_array($config['extraAttributes'])) {
            $column->extraAttributes($config['extraAttributes']);
        }

        // Apply empty state
        $this->configureEmptyState($column, $customField);

        // Apply state formatting
        $this->configureStateFormatting($column, $customField);
    }

    /**
     * Configure empty state display
     */
    protected function configureEmptyState(Column $column, CustomField $customField): void
    {
        $config = $customField->field_config ?? [];

        if (isset($config['placeholder'])) {
            $column->placeholder($config['placeholder']);
        } elseif (isset($config['emptyStateLabel'])) {
            $column->placeholder($config['emptyStateLabel']);
        }
    }

    /**
     * Configure state formatting
     */
    protected function configureStateFormatting(Column $column, CustomField $customField): void
    {
        $config = $customField->field_config ?? [];

        // Apply custom state formatting if provided
        if (isset($config['formatStateUsing']) && is_callable($config['formatStateUsing'])) {
            $column->formatStateUsing($config['formatStateUsing']);
        }

        // Apply description
        if (isset($config['description'])) {
            $column->description($config['description']);
        }

        // Apply description position
        if (isset($config['descriptionPosition'])) {
            $column->description(
                $config['description'] ?? '',
                position: $config['descriptionPosition']
            );
        }
    }

    /**
     * Configure sortability with custom query
     */
    protected function configureSortability(Column $column, CustomField $customField): void
    {
        $config = $customField->field_config ?? [];

        if (! isset($config['sortable']) || ! $config['sortable']) {
            return;
        }

        // Configure custom sort query for custom field values
        $column->sortable(query: function ($query, string $direction) use ($customField) {
            return $query->orderBy(
                function ($query) use ($customField) {
                    $query->select('value')
                        ->from('custom_field_values')
                        ->whereColumn('customizable_id', $query->getModel()->getTable() . '.id')
                        ->where('customizable_type', $query->getModel()->getMorphClass())
                        ->where('custom_field_id', $customField->id)
                        ->limit(1);
                },
                $direction
            );
        });
    }

    /**
     * Configure searchability with custom query
     */
    protected function configureSearchability(Column $column, CustomField $customField): void
    {
        $config = $customField->field_config ?? [];

        if (! isset($config['searchable']) || ! $config['searchable']) {
            return;
        }

        // Configure custom search query for custom field values
        $column->searchable(query: function ($query, string $search) use ($customField) {
            return $query->whereHas('customFieldValues', function ($query) use ($customField, $search) {
                $query->where('custom_field_id', $customField->id)
                    ->where('value', 'like', "%{$search}%");
            });
        });
    }

    /**
     * Create and configure column with visibility rules
     *
     * @param  array<string>  $dependentFieldCodes
     */
    public function makeWithVisibility(CustomField $customField, array $dependentFieldCodes = []): Column
    {
        $column = $this->make($customField);

        // Apply visibility configuration if dependencies exist
        if (! empty($dependentFieldCodes) && ! empty($customField->visibility_rules)) {
            $visibilityClosure = $this->createVisibilityClosure(
                $customField->visibility_rules,
                $dependentFieldCodes
            );
            $column->visible($visibilityClosure);
        }

        return $column;
    }
}
