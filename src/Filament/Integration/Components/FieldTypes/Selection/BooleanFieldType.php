<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\FieldTypes\Selection;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Field;
use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\IconEntry;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\TernaryFilter;
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
 * ABOUTME: Boolean field type implementation for true/false values
 * ABOUTME: Provides checkbox input, icon column, ternary filter, and icon entry components
 */
class BooleanFieldType implements 
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
                return Checkbox::make($this->getStateAttributeName($customField));
            }
            
            protected function applyFieldSpecificConfiguration(Field $field, CustomField $customField): void
            {
                $config = $customField->field_config ?? [];
                
                if ($field instanceof Checkbox) {
                    // Inline label
                    if (isset($config['inline']) && $config['inline']) {
                        $field->inline();
                    }
                    
                    // Accepted/Rejected rules (for validation)
                    if (isset($config['accepted']) && $config['accepted']) {
                        $field->accepted();
                    }
                    
                    if (isset($config['declined']) && $config['declined']) {
                        $field->declined();
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
                return IconColumn::make($this->getCustomFieldStateKey($customField))
                    ->boolean();
            }
            
            protected function applyColumnSpecificConfiguration(Column $column, CustomField $customField): void
            {
                $config = $customField->field_config ?? [];
                
                if ($column instanceof IconColumn) {
                    // Custom icons
                    if (isset($config['trueIcon'])) {
                        $column->trueIcon($config['trueIcon']);
                    }
                    
                    if (isset($config['falseIcon'])) {
                        $column->falseIcon($config['falseIcon']);
                    }
                    
                    // Custom colors
                    if (isset($config['trueColor'])) {
                        $column->trueColor($config['trueColor']);
                    }
                    
                    if (isset($config['falseColor'])) {
                        $column->falseColor($config['falseColor']);
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
        $filter = TernaryFilter::make("custom_fields_{$customField->code}")
            ->label($customField->label)
            ->queries(
                true: fn (Builder $query): Builder => $this->buildBooleanQuery($query, $customField, true),
                false: fn (Builder $query): Builder => $this->buildBooleanQuery($query, $customField, false),
                blank: fn (Builder $query): Builder => $query->whereDoesntHave('customFieldValues', 
                    fn (Builder $query) => $query->where('custom_field_id', $customField->id)
                ),
            );
            
        $config = $customField->field_config ?? [];
        
        // Custom labels
        if (isset($config['trueLabel'])) {
            $filter->trueLabel($config['trueLabel']);
        }
        
        if (isset($config['falseLabel'])) {
            $filter->falseLabel($config['falseLabel']);
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
            protected function createEntry(CustomField $customField): Entry
            {
                return IconEntry::make($this->getCustomFieldStateKey($customField))
                    ->boolean();
            }
            
            protected function applyEntrySpecificConfiguration(Entry $entry, CustomField $customField): void
            {
                $config = $customField->field_config ?? [];
                
                if ($entry instanceof IconEntry) {
                    // Custom icons
                    if (isset($config['infolistTrueIcon'])) {
                        $entry->trueIcon($config['infolistTrueIcon']);
                    }
                    
                    if (isset($config['infolistFalseIcon'])) {
                        $entry->falseIcon($config['infolistFalseIcon']);
                    }
                    
                    // Custom colors
                    if (isset($config['infolistTrueColor'])) {
                        $entry->trueColor($config['infolistTrueColor']);
                    }
                    
                    if (isset($config['infolistFalseColor'])) {
                        $entry->falseColor($config['infolistFalseColor']);
                    }
                    
                    // Size
                    if (isset($config['infolistIconSize'])) {
                        $entry->size($config['infolistIconSize']);
                    }
                }
            }
        };
        
        return $entryHelper->makeInfolistEntry($customField);
    }
    
    /**
     * Build boolean query helper
     *
     * @param  Builder  $query
     * @param  CustomField  $customField
     * @param  bool  $value
     * @return Builder
     */
    private function buildBooleanQuery(Builder $query, CustomField $customField, bool $value): Builder
    {
        return $query->whereHas('customFieldValues', function (Builder $query) use ($customField, $value) {
            $query->where('custom_field_id', $customField->id)
                ->where('value', $value ? '1' : '0');
        });
    }
}