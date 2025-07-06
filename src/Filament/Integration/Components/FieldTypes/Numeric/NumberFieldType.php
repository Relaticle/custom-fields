<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\FieldTypes\Numeric;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
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
 * ABOUTME: Number field type implementation for numeric input fields
 * ABOUTME: Provides form input, table column, filter, and infolist entry components with numeric validation
 */
class NumberFieldType implements 
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
                return TextInput::make($this->getStateAttributeName($customField))
                    ->numeric();
            }
            
            protected function applyFieldSpecificConfiguration(Field $field, CustomField $customField): void
            {
        $config = $customField->field_config ?? [];
        
        if ($field instanceof TextInput) {
            // Min/Max values
            if (isset($config['minValue'])) {
                $field->minValue($config['minValue']);
            }
            
            if (isset($config['maxValue'])) {
                $field->maxValue($config['maxValue']);
            }
            
            // Step
            if (isset($config['step'])) {
                $field->step($config['step']);
            }
            
            // Placeholder
            if (isset($config['placeholder'])) {
                $field->placeholder($config['placeholder']);
            }
            
            // Prefix/Suffix
            if (isset($config['prefix'])) {
                $field->prefix($config['prefix']);
            }
            
            if (isset($config['suffix'])) {
                $field->suffix($config['suffix']);
            }
            
            // Integer only
            if (isset($config['integer']) && $config['integer']) {
                $field->integer();
            }
            
            // Input mode for mobile
            $field->inputMode('numeric');
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
                    ->numeric();
            }
            
            protected function applyColumnSpecificConfiguration(Column $column, CustomField $customField): void
            {
                $config = $customField->field_config ?? [];
                
                if ($column instanceof TextColumn) {
                    // Decimal places
                    if (isset($config['decimalPlaces'])) {
                        $column->numeric($config['decimalPlaces']);
                    }
                    
                    // Prefix/Suffix
                    if (isset($config['columnPrefix'])) {
                        $column->prefix($config['columnPrefix']);
                    }
                    
                    if (isset($config['columnSuffix'])) {
                        $column->suffix($config['columnSuffix']);
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
                
                // Create a field group for range filtering
                return \Filament\Forms\Components\Group::make([
                    TextInput::make('min')
                        ->label('Min')
                        ->numeric()
                        ->placeholder($config['filterMinPlaceholder'] ?? 'Minimum'),
                    TextInput::make('max')
                        ->label('Max')
                        ->numeric()
                        ->placeholder($config['filterMaxPlaceholder'] ?? 'Maximum'),
                ])->columns(2);
            }
            
            protected function applyFilter(Builder $query, CustomField $customField): Builder
            {
                $range = [
                    'min' => data_get($this->getState(), 'min'),
                    'max' => data_get($this->getState(), 'max'),
                ];
                
                if (!empty(array_filter($range))) {
                    return $this->buildNumericRangeQuery($query, $customField, $range);
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
                    ->numeric();
            }
            
            protected function applyEntrySpecificConfiguration(Entry $entry, CustomField $customField): void
            {
                $config = $customField->field_config ?? [];
                
                if ($entry instanceof TextEntry) {
                    // Decimal places
                    if (isset($config['decimalPlaces'])) {
                        $entry->numeric($config['decimalPlaces']);
                    }
                    
                    // Prefix/Suffix
                    if (isset($config['infolistPrefix'])) {
                        $entry->prefix($config['infolistPrefix']);
                    }
                    
                    if (isset($config['infolistSuffix'])) {
                        $entry->suffix($config['infolistSuffix']);
                    }
                }
            }
        };
        
        return $entryHelper->makeInfolistEntry($customField);
    }
}