<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\FieldTypes\Advanced;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TagsInput;
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
 * ABOUTME: Tags field type implementation for multi-value tag input
 * ABOUTME: Provides tags input, badge column, multi-select filter, and badge list entry components
 */
class TagsFieldType implements 
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
                return TagsInput::make($this->getStateAttributeName($customField));
            }
            
            protected function applyFieldSpecificConfiguration(Field $field, CustomField $customField): void
            {
                $config = $customField->field_config ?? [];
                
                if ($field instanceof TagsInput) {
                    // Separator
                    if (isset($config['separator'])) {
                        $field->separator($config['separator']);
                    }
                    
                    // Suggestions
                    if (isset($config['suggestions']) && is_array($config['suggestions'])) {
                        $field->suggestions($config['suggestions']);
                    }
                    
                    // Placeholder
                    if (isset($config['placeholder'])) {
                        $field->placeholder($config['placeholder']);
                    }
                    
                    // Duplicate check
                    if (isset($config['reorderable']) && $config['reorderable']) {
                        $field->reorderable();
                    }
                    
                    // Split keys
                    if (isset($config['splitKeys']) && is_array($config['splitKeys'])) {
                        $field->splitKeys($config['splitKeys']);
                    }
                    
                    // Prefix/Suffix for each tag
                    if (isset($config['tagPrefix'])) {
                        $field->nestedRecursiveRules([
                            'min:' . ($config['minTagLength'] ?? 1),
                        ]);
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
                    ->badge()
                    ->separator(',');
            }
            
            protected function applyColumnSpecificConfiguration(Column $column, CustomField $customField): void
            {
                $config = $customField->field_config ?? [];
                
                if ($column instanceof TextColumn) {
                    // Limit visible tags
                    if (isset($config['columnTagLimit'])) {
                        $column->limitList($config['columnTagLimit']);
                    }
                    
                    // Badge color
                    if (isset($config['badgeColor'])) {
                        $column->color($config['badgeColor']);
                    }
                    
                    // Custom separator
                    if (isset($config['columnSeparator'])) {
                        $column->separator($config['columnSeparator']);
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
                
                return TagsInput::make('tags')
                    ->label($customField->label)
                    ->placeholder($config['filterPlaceholder'] ?? 'Filter by tags...')
                    ->suggestions($config['filterSuggestions'] ?? []);
            }
            
            protected function applyFilter(Builder $query, CustomField $customField): Builder
            {
                $tags = data_get($this->getState(), 'tags', []);
                
                if (empty($tags)) {
                    return $query;
                }
                
                return $query->whereHas('customFieldValues', function (Builder $query) use ($customField, $tags) {
                    $query->where('custom_field_id', $customField->id);
                    
                    // Tags are stored as JSON array, so we need to check if any of the filter tags exist
                    $query->where(function (Builder $query) use ($tags) {
                        foreach ($tags as $tag) {
                            $query->orWhereJsonContains('value', $tag);
                        }
                    });
                });
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
                    ->badge()
                    ->separator(',');
            }
            
            protected function applyEntrySpecificConfiguration(Entry $entry, CustomField $customField): void
            {
                $config = $customField->field_config ?? [];
                
                if ($entry instanceof TextEntry) {
                    // Badge color
                    if (isset($config['infolistBadgeColor'])) {
                        $entry->color($config['infolistBadgeColor']);
                    }
                    
                    // Custom separator
                    if (isset($config['infolistSeparator'])) {
                        $entry->separator($config['infolistSeparator']);
                    }
                    
                    // Limit visible tags
                    if (isset($config['infolistTagLimit'])) {
                        $entry->limitList($config['infolistTagLimit']);
                    }
                }
            }
        };
        
        return $entryHelper->makeInfolistEntry($customField);
    }
}