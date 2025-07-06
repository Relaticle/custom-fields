<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\FieldTypes\DateTime;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Contracts\Components\FormComponentInterface;
use Relaticle\CustomFields\Contracts\Components\InfolistEntryInterface;
use Relaticle\CustomFields\Contracts\Components\TableColumnInterface;
use Relaticle\CustomFields\Contracts\Components\TableFilterInterface;
use Relaticle\CustomFields\Filament\Integration\Components\Base\CustomFieldColumn;
use Relaticle\CustomFields\Filament\Integration\Components\Base\CustomFieldEntry;
use Relaticle\CustomFields\Filament\Integration\Components\Base\CustomFieldFilter;
use Relaticle\CustomFields\Filament\Integration\Components\Base\CustomFieldInput;
use Relaticle\CustomFields\Models\CustomField;

/**
 * ABOUTME: Date field type implementation for date picker fields
 * ABOUTME: Provides date picker input, date column, date range filter, and date entry components
 */
class DateFieldType implements 
    FormComponentInterface,
    TableColumnInterface,
    TableFilterInterface,
    InfolistEntryInterface
{
    /**
     * Create form component
     *
     * @param  CustomField  $customField
     * @param  array  $dependentFieldCodes
     * @param  Collection|null  $allFields
     * @return Field
     */
    public function make(CustomField $customField, array $dependentFieldCodes = [], ?Collection $allFields = null): Field
    {
        $inputHelper = new class($customField, $dependentFieldCodes, $allFields) extends CustomFieldInput {
            protected function createField(CustomField $customField): Field
            {
                return DatePicker::make($this->getStateAttributeName($customField));
            }
            
            protected function applyFieldSpecificConfiguration(Field $field, CustomField $customField): void
            {
                $config = $customField->field_config ?? [];
                
                if ($field instanceof DatePicker) {
                    // Format
                    if (isset($config['format'])) {
                        $field->format($config['format']);
                    }
                    
                    // Display format
                    if (isset($config['displayFormat'])) {
                        $field->displayFormat($config['displayFormat']);
                    }
                    
                    // Min/Max dates
                    if (isset($config['minDate'])) {
                        $field->minDate($config['minDate']);
                    }
                    
                    if (isset($config['maxDate'])) {
                        $field->maxDate($config['maxDate']);
                    }
                    
                    // Disabled dates
                    if (isset($config['disabledDates']) && is_array($config['disabledDates'])) {
                        $field->disabledDates($config['disabledDates']);
                    }
                    
                    // First day of week
                    if (isset($config['firstDayOfWeek'])) {
                        $field->firstDayOfWeek($config['firstDayOfWeek']);
                    }
                    
                    // Native HTML5
                    if (isset($config['native']) && $config['native']) {
                        $field->native();
                    }
                    
                    // Close on date selection
                    if (isset($config['closeOnDateSelection']) && $config['closeOnDateSelection']) {
                        $field->closeOnDateSelection();
                    }
                    
                    // Placeholder
                    if (isset($config['placeholder'])) {
                        $field->placeholder($config['placeholder']);
                    }
                }
            }
        };
        
        return $inputHelper->make($customField, $dependentFieldCodes, $allFields);
    }

    /**
     * Create table column
     *
     * @param  CustomField  $customField
     * @return Column
     */
    public function makeTableColumn(CustomField $customField): Column
    {
        $columnHelper = new class($customField) extends CustomFieldColumn {
            protected function createColumn(CustomField $customField): Column
            {
                return TextColumn::make($this->getCustomFieldStateKey($customField))
                    ->date();
            }
            
            protected function applyColumnSpecificConfiguration(Column $column, CustomField $customField): void
            {
                $config = $customField->field_config ?? [];
                
                if ($column instanceof TextColumn) {
                    // Date format
                    if (isset($config['columnDateFormat'])) {
                        $column->date($config['columnDateFormat']);
                    }
                    
                    // Since (relative time)
                    if (isset($config['displayAsSince']) && $config['displayAsSince']) {
                        $column->since();
                    }
                }
            }
        };
        
        return $columnHelper->makeTableColumn($customField);
    }

    /**
     * Create table filter
     *
     * @param  CustomField  $customField
     * @return BaseFilter
     */
    public function makeTableFilter(CustomField $customField): BaseFilter
    {
        $filterHelper = new class($customField) extends CustomFieldFilter {
            protected function createFilterFormComponent(CustomField $customField): Component
            {
                $config = $customField->field_config ?? [];
                
                // Create date range filter
                return \Filament\Forms\Components\Grid::make([
                    DatePicker::make('from')
                        ->label('From')
                        ->displayFormat($config['filterDisplayFormat'] ?? null),
                    DatePicker::make('to')
                        ->label('To')
                        ->displayFormat($config['filterDisplayFormat'] ?? null),
                ])->columns(2);
            }
            
            protected function applyFilter(Builder $query, CustomField $customField): Builder
            {
                $dateRange = [
                    'from' => data_get($this->getState(), 'from'),
                    'to' => data_get($this->getState(), 'to'),
                ];
                
                if (!empty(array_filter($dateRange))) {
                    return $this->buildDateRangeQuery($query, $customField, $dateRange);
                }
                
                return $query;
            }
            
            public function getState(): ?array
            {
                // This would be handled by Filament's filter state management
                return [];
            }
        };
        
        return $filterHelper->makeTableFilter($customField);
    }

    /**
     * Create infolist entry
     *
     * @param  CustomField  $customField
     * @return Entry
     */
    public function makeInfolistEntry(CustomField $customField): Entry
    {
        $entryHelper = new class($customField) extends CustomFieldEntry {
            protected function createEntry(CustomField $customField): Entry
            {
                return TextEntry::make($this->getCustomFieldStateKey($customField))
                    ->date();
            }
            
            protected function applyEntrySpecificConfiguration(Entry $entry, CustomField $customField): void
            {
                $config = $customField->field_config ?? [];
                
                if ($entry instanceof TextEntry) {
                    // Date format
                    if (isset($config['infolistDateFormat'])) {
                        $entry->date($config['infolistDateFormat']);
                    }
                    
                    // Since (relative time)
                    if (isset($config['infolistDisplayAsSince']) && $config['infolistDisplayAsSince']) {
                        $entry->since();
                    }
                    
                    // Date time if needed
                    if (isset($config['infolistIncludeTime']) && $config['infolistIncludeTime']) {
                        $entry->dateTime($config['infolistDateTimeFormat'] ?? null);
                    }
                }
            }
        };
        
        return $entryHelper->makeInfolistEntry($customField);
    }
}