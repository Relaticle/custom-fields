<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Base;

use Filament\Schemas\Components\Component;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\CustomFields\Contracts\Components\TableFilterInterface;
use Relaticle\CustomFields\Models\CustomField;

/**
 * ABOUTME: Abstract base class for all custom field table filter components
 * ABOUTME: Provides common functionality for table filter configuration and query building
 */
abstract class CustomFieldFilter implements TableFilterInterface
{
    /**
     * Create and configure a table filter component
     *
     * @param  CustomField  $customField
     * @return BaseFilter
     */
    public function make(CustomField $customField): BaseFilter
    {
        // Create the base filter
        $filter = Filter::make("custom_fields_{$customField->code}")
            ->label($customField->label);

        // Configure the filter form
        $filter->form([
            $this->createFilterFormComponent($customField),
        ]);

        // Configure the query
        $filter->query(fn (Builder $query): Builder => $this->applyFilter($query, $customField));

        // Apply common configuration
        $this->configureFilter($filter, $customField);

        // Apply filter-specific configuration
        $this->applyFilterSpecificConfiguration($filter, $customField);

        return $filter;
    }

    /**
     * Create the form component for the filter
     *
     * @param  CustomField  $customField
     * @return Component
     */
    abstract protected function createFilterFormComponent(CustomField $customField): Component;

    /**
     * Apply the filter to the query
     *
     * @param  Builder  $query
     * @param  CustomField  $customField
     * @return Builder
     */
    abstract protected function applyFilter(Builder $query, CustomField $customField): Builder;

    /**
     * Apply filter-specific configuration
     *
     * @param  BaseFilter  $filter
     * @param  CustomField  $customField
     * @return void
     */
    protected function applyFilterSpecificConfiguration(BaseFilter $filter, CustomField $customField): void
    {
        // Override in subclasses for specific configuration
    }

    /**
     * Configure common filter properties
     *
     * @param  BaseFilter  $filter
     * @param  CustomField  $customField
     * @return void
     */
    protected function configureFilter(BaseFilter $filter, CustomField $customField): void
    {
        // Get field configuration
        $config = $customField->field_config ?? [];

        // Configure default value
        if (isset($config['defaultFilterValue'])) {
            $filter->default($config['defaultFilterValue']);
        }

        // Configure column span
        if (isset($config['filterColumnSpan'])) {
            $filter->columnSpan($config['filterColumnSpan']);
        }
    }

    /**
     * Build base query for custom field values
     *
     * @param  Builder  $query
     * @param  CustomField  $customField
     * @param  mixed  $value
     * @param  string  $operator
     * @return Builder
     */
    protected function buildCustomFieldQuery(
        Builder $query,
        CustomField $customField,
        mixed $value,
        string $operator = '='
    ): Builder {
        if ($value === null || $value === '') {
            return $query;
        }

        return $query->whereHas('customFieldValues', function (Builder $query) use ($customField, $value, $operator) {
            $query->where('custom_field_id', $customField->id);

            switch ($operator) {
                case 'like':
                case 'not like':
                    $query->where('value', $operator, "%{$value}%");
                    break;

                case 'in':
                    $query->whereIn('value', (array) $value);
                    break;

                case 'not in':
                    $query->whereNotIn('value', (array) $value);
                    break;

                case 'between':
                    if (is_array($value) && count($value) === 2) {
                        $query->whereBetween('value', $value);
                    }
                    break;

                case 'not between':
                    if (is_array($value) && count($value) === 2) {
                        $query->whereNotBetween('value', $value);
                    }
                    break;

                case 'null':
                    $query->whereNull('value');
                    break;

                case 'not null':
                    $query->whereNotNull('value');
                    break;

                default:
                    $query->where('value', $operator, $value);
                    break;
            }
        });
    }

    /**
     * Build query for boolean custom field values
     *
     * @param  Builder  $query
     * @param  CustomField  $customField
     * @param  mixed  $value
     * @return Builder
     */
    protected function buildBooleanQuery(Builder $query, CustomField $customField, mixed $value): Builder
    {
        if ($value === null) {
            return $query;
        }

        $booleanValue = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        return $query->whereHas('customFieldValues', function (Builder $query) use ($customField, $booleanValue) {
            $query->where('custom_field_id', $customField->id)
                ->where('value', $booleanValue ? '1' : '0');
        });
    }

    /**
     * Build query for date range custom field values
     *
     * @param  Builder  $query
     * @param  CustomField  $customField
     * @param  array|null  $dateRange
     * @return Builder
     */
    protected function buildDateRangeQuery(Builder $query, CustomField $customField, ?array $dateRange): Builder
    {
        if (empty($dateRange)) {
            return $query;
        }

        return $query->whereHas('customFieldValues', function (Builder $query) use ($customField, $dateRange) {
            $query->where('custom_field_id', $customField->id);

            if (isset($dateRange['from'])) {
                $query->where('value', '>=', $dateRange['from']);
            }

            if (isset($dateRange['to'])) {
                $query->where('value', '<=', $dateRange['to']);
            }
        });
    }

    /**
     * Build query for numeric range custom field values
     *
     * @param  Builder  $query
     * @param  CustomField  $customField
     * @param  array|null  $range
     * @return Builder
     */
    protected function buildNumericRangeQuery(Builder $query, CustomField $customField, ?array $range): Builder
    {
        if (empty($range)) {
            return $query;
        }

        return $query->whereHas('customFieldValues', function (Builder $query) use ($customField, $range) {
            $query->where('custom_field_id', $customField->id);

            if (isset($range['min'])) {
                $query->where('value', '>=', $range['min']);
            }

            if (isset($range['max'])) {
                $query->where('value', '<=', $range['max']);
            }
        });
    }

    /**
     * Get the filter state key
     *
     * @param  CustomField  $customField
     * @return string
     */
    protected function getFilterStateKey(CustomField $customField): string
    {
        return "custom_fields_{$customField->code}";
    }
}