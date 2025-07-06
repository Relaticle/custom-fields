<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\FieldTypes\Selection;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Contracts\Components\FormComponentInterface;
use Relaticle\CustomFields\Contracts\Components\InfolistEntryInterface;
use Relaticle\CustomFields\Contracts\Components\TableColumnInterface;
use Relaticle\CustomFields\Contracts\Components\TableFilterInterface;
use Relaticle\CustomFields\Filament\Integration\Components\Base\CustomFieldColumn;
use Relaticle\CustomFields\Filament\Integration\Components\Base\CustomFieldEntry;
use Relaticle\CustomFields\Filament\Integration\Components\Base\CustomFieldInput;
use Relaticle\CustomFields\Models\CustomField;

/**
 * ABOUTME: Select field type implementation for single-choice dropdown fields
 * ABOUTME: Provides select input, text column, select filter, and text entry components
 */
class SelectFieldType implements 
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
                return Select::make($this->getStateAttributeName($customField))
                    ->options($this->getFieldOptions($customField));
            }
            
            protected function applyFieldSpecificConfiguration(Field $field, CustomField $customField): void
            {
                $config = $customField->field_config ?? [];
                
                if ($field instanceof Select) {
                    // Searchable
                    if (isset($config['searchable']) && $config['searchable']) {
                        $field->searchable();
                    }
                    
                    // Native HTML5
                    if (isset($config['native']) && $config['native']) {
                        $field->native();
                    }
                    
                    // Placeholder
                    if (isset($config['placeholder'])) {
                        $field->placeholder($config['placeholder']);
                    }
                    
                    // Disable placeholder selection
                    if (isset($config['disablePlaceholderSelection']) && $config['disablePlaceholderSelection']) {
                        $field->disablePlaceholderSelection();
                    }
                    
                    // Preload options
                    if (isset($config['preload']) && $config['preload']) {
                        $field->preload();
                    }
                    
                    // Live search
                    if (isset($config['searchDebounce'])) {
                        $field->searchDebounce($config['searchDebounce']);
                    }
                    
                    // Max items (for search results)
                    if (isset($config['maxItems'])) {
                        $field->optionsLimit($config['maxItems']);
                    }
                }
            }
            
            private function getFieldOptions(CustomField $customField): array
            {
                $options = [];
                
                foreach ($customField->options ?? [] as $option) {
                    $options[$option->value] = $option->label;
                }
                
                return $options;
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
            private array $options;
            
            public function __construct(CustomField $customField)
            {
                parent::__construct($customField);
                $this->options = $this->getFieldOptions($customField);
            }
            
            protected function createColumn(CustomField $customField): Column
            {
                return TextColumn::make($this->getCustomFieldStateKey($customField))
                    ->formatStateUsing(fn ($state) => $this->options[$state] ?? $state);
            }
            
            protected function applyColumnSpecificConfiguration(Column $column, CustomField $customField): void
            {
                $config = $customField->field_config ?? [];
                
                if ($column instanceof TextColumn) {
                    // Badge
                    if (isset($config['displayAsBadge']) && $config['displayAsBadge']) {
                        $column->badge();
                        
                        // Badge colors
                        if (isset($config['badgeColors'])) {
                            $column->color(fn ($state) => $config['badgeColors'][$state] ?? null);
                        }
                    }
                }
            }
            
            private function getFieldOptions(CustomField $customField): array
            {
                $options = [];
                
                foreach ($customField->options ?? [] as $option) {
                    $options[$option->value] = $option->label;
                }
                
                return $options;
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
        $filter = SelectFilter::make("custom_fields_{$customField->code}")
            ->label($customField->label)
            ->options($this->getOptions($customField))
            ->query(fn (Builder $query, array $data): Builder => 
                !empty($data['value']) 
                    ? $this->buildSelectQuery($query, $customField, $data['value'])
                    : $query
            );
            
        $config = $customField->field_config ?? [];
        
        // Multiple selection
        if (isset($config['filterMultiple']) && $config['filterMultiple']) {
            $filter->multiple();
        }
        
        // Searchable
        if (isset($config['filterSearchable']) && $config['filterSearchable']) {
            $filter->searchable();
        }
        
        return $filter;
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
            private array $options;
            
            public function __construct(CustomField $customField)
            {
                parent::__construct($customField);
                $this->options = $this->getFieldOptions($customField);
            }
            
            protected function createEntry(CustomField $customField): Entry
            {
                return TextEntry::make($this->getCustomFieldStateKey($customField))
                    ->formatStateUsing(fn ($state) => $this->options[$state] ?? $state);
            }
            
            protected function applyEntrySpecificConfiguration(Entry $entry, CustomField $customField): void
            {
                $config = $customField->field_config ?? [];
                
                if ($entry instanceof TextEntry) {
                    // Badge
                    if (isset($config['infolistBadge']) && $config['infolistBadge']) {
                        $entry->badge();
                        
                        // Badge colors
                        if (isset($config['infolistBadgeColors'])) {
                            $entry->color(fn ($state) => $config['infolistBadgeColors'][$state] ?? null);
                        }
                    }
                }
            }
            
            private function getFieldOptions(CustomField $customField): array
            {
                $options = [];
                
                foreach ($customField->options ?? [] as $option) {
                    $options[$option->value] = $option->label;
                }
                
                return $options;
            }
        };
        
        return $entryHelper->makeInfolistEntry($customField);
    }
    
    /**
     * Get options for the select field
     *
     * @param  CustomField  $customField
     * @return array
     */
    private function getOptions(CustomField $customField): array
    {
        $options = [];
        
        // Load options from custom field options
        foreach ($customField->options ?? [] as $option) {
            $options[$option->value] = $option->label;
        }
        
        return $options;
    }
    
    /**
     * Build select query
     *
     * @param  Builder  $query
     * @param  CustomField  $customField
     * @param  string|array  $value
     * @return Builder
     */
    private function buildSelectQuery(Builder $query, CustomField $customField, $value): Builder
    {
        return $query->whereHas('customFieldValues', function (Builder $query) use ($customField, $value) {
            $query->where('custom_field_id', $customField->id);
            
            if (is_array($value)) {
                $query->whereIn('value', $value);
            } else {
                $query->where('value', $value);
            }
        });
    }
}