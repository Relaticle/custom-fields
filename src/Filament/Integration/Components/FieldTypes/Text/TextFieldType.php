<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\FieldTypes\Text;

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
 * ABOUTME: Text field type implementation for basic text input fields
 * ABOUTME: Provides form input, table column, filter, and infolist entry components
 */
class TextFieldType implements 
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
                return TextInput::make($this->getStateAttributeName($customField));
            }
            
            protected function applyFieldSpecificConfiguration(Field $field, CustomField $customField): void
            {
                $config = $customField->field_config ?? [];
                
                if ($field instanceof TextInput) {
                    // Max length
                    if (isset($config['maxLength'])) {
                        $field->maxLength($config['maxLength']);
                    }
                    
                    // Min length
                    if (isset($config['minLength'])) {
                        $field->minLength($config['minLength']);
                    }
                    
                    // Placeholder
                    if (isset($config['placeholder'])) {
                        $field->placeholder($config['placeholder']);
                    }
                    
                    // Autocomplete
                    if (isset($config['autocomplete'])) {
                        $field->autocomplete($config['autocomplete']);
                    }
                    
                    // Input mode
                    if (isset($config['inputMode'])) {
                        $field->inputMode($config['inputMode']);
                    }
                    
                    // Prefix/Suffix
                    if (isset($config['prefix'])) {
                        $field->prefix($config['prefix']);
                    }
                    
                    if (isset($config['suffix'])) {
                        $field->suffix($config['suffix']);
                    }
                    
                    // Mask
                    if (isset($config['mask'])) {
                        $field->mask($config['mask']);
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
                return TextColumn::make($this->getCustomFieldStateKey($customField));
            }
            
            protected function applyColumnSpecificConfiguration(Column $column, CustomField $customField): void
            {
                $config = $customField->field_config ?? [];
                
                if ($column instanceof TextColumn) {
                    // Character limit
                    if (isset($config['columnCharacterLimit'])) {
                        $column->limit($config['columnCharacterLimit']);
                    }
                    
                    // Word limit
                    if (isset($config['columnWordLimit'])) {
                        $column->words($config['columnWordLimit']);
                    }
                    
                    // Wrap
                    if (isset($config['columnWrap']) && $config['columnWrap']) {
                        $column->wrap();
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
                return TextInput::make('value')
                    ->label($customField->label)
                    ->placeholder($customField->field_config['filterPlaceholder'] ?? null);
            }
            
            protected function applyFilter(Builder $query, CustomField $customField): Builder
            {
                return $query->when(
                    filled($filterValue = $this->getFilterValue()),
                    fn (Builder $query) => $this->buildCustomFieldQuery($query, $customField, $filterValue, 'like')
                );
            }
            
            private function getFilterValue(): ?string
            {
                return data_get($this->getState(), 'value');
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
                return TextEntry::make($this->getCustomFieldStateKey($customField));
            }
            
            protected function applyEntrySpecificConfiguration(Entry $entry, CustomField $customField): void
            {
                $config = $customField->field_config ?? [];
                
                if ($entry instanceof TextEntry) {
                    // Character limit
                    if (isset($config['infolistCharacterLimit'])) {
                        $entry->limit($config['infolistCharacterLimit']);
                    }
                    
                    // Word limit
                    if (isset($config['infolistWordLimit'])) {
                        $entry->words($config['infolistWordLimit']);
                    }
                    
                    // Copy to clipboard
                    if (isset($config['copyable']) && $config['copyable']) {
                        $entry->copyable();
                    }
                }
            }
        };
        
        return $entryHelper->makeInfolistEntry($customField);
    }
}