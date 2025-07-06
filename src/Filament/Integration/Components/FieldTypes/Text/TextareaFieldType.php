<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\FieldTypes\Text;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Contracts\Components\FormComponentInterface;
use Relaticle\CustomFields\Contracts\Components\InfolistEntryInterface;
use Relaticle\CustomFields\Contracts\Components\TableColumnInterface;
use Relaticle\CustomFields\Contracts\Components\TableFilterInterface;
use Relaticle\CustomFields\Filament\Integration\Components\Base\CustomFieldInput;
use Relaticle\CustomFields\Models\CustomField;

/**
 * ABOUTME: Textarea field type implementation for multi-line text input
 * ABOUTME: Provides form textarea, table column, filter, and infolist entry components
 */
class TextareaFieldType implements 
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
                return Textarea::make($this->getStateAttributeName($customField));
            }
            
            protected function applyFieldSpecificConfiguration(Field $field, CustomField $customField): void
            {
                $config = $customField->field_config ?? [];
                
                if ($field instanceof Textarea) {
                    // Rows
                    if (isset($config['rows'])) {
                        $field->rows($config['rows']);
                    }
                    
                    // Cols
                    if (isset($config['cols'])) {
                        $field->cols($config['cols']);
                    }
                    
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
                    
                    // Auto resize
                    if (isset($config['autosize']) && $config['autosize']) {
                        $field->autosize();
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
        // Use the same implementation as TextFieldType
        $textFieldType = new TextFieldType();
        return $textFieldType->makeTableColumn($customField);
    }

    /**
     * Create table filter
     *
     * @param  CustomField  $customField
     * @return BaseFilter
     */
    public function makeTableFilter(CustomField $customField): BaseFilter
    {
        // Use the same filter implementation as TextFieldType
        $textFieldType = new TextFieldType();
        return $textFieldType->makeTableFilter($customField);
    }

    /**
     * Create infolist entry
     *
     * @param  CustomField  $customField
     * @return Entry
     */
    public function makeInfolistEntry(CustomField $customField): Entry
    {
        // Use the same implementation as TextFieldType but with line breaks support
        $textFieldType = new TextFieldType();
        $entry = $textFieldType->makeInfolistEntry($customField);
        
        if ($entry instanceof TextEntry) {
            $entry->lineBreaksToBr();
        }
        
        return $entry;
    }
}