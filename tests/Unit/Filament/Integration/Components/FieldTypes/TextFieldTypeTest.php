<?php

declare(strict_types=1);

use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Relaticle\CustomFields\Filament\Integration\Components\FieldTypes\Text\TextFieldType;
use Relaticle\CustomFields\Models\CustomField;

beforeEach(function () {
    $this->textFieldType = new TextFieldType();
    
    $this->customField = new CustomField();
    $this->customField->id = 1;
    $this->customField->code = 'test_text';
    $this->customField->label = 'Test Text Field';
    $this->customField->type = 'text';
    $this->customField->is_required = false;
    $this->customField->field_config = [
        'maxLength' => 100,
        'minLength' => 5,
        'placeholder' => 'Enter text here',
        'prefix' => '$',
        'suffix' => '.00',
    ];
});

describe('TextFieldType Form Component', function () {
    it('creates a text input component', function () {
        $component = $this->textFieldType->make($this->customField);
        
        expect($component)->toBeInstanceOf(TextInput::class);
        expect($component->getName())->toBe('custom_fields.test_text');
        expect($component->getLabel())->toBe('Test Text Field');
    });
    
    it('applies text-specific configuration', function () {
        $component = $this->textFieldType->make($this->customField);
        
        expect($component->getMaxLength())->toBe(100);
        expect($component->getPlaceholder())->toBe('Enter text here');
    });
    
    it('handles required fields', function () {
        $this->customField->is_required = true;
        $component = $this->textFieldType->make($this->customField);
        
        expect($component->isRequired())->toBeTrue();
    });
    
    it('handles default values', function () {
        $this->customField->field_config = array_merge($this->customField->field_config, [
            'default' => 'Default Text',
        ]);
        $component = $this->textFieldType->make($this->customField);
        
        expect($component->getDefaultState())->toBe('Default Text');
    });
});

describe('TextFieldType Table Column', function () {
    it('creates a text column component', function () {
        $column = $this->textFieldType->makeTableColumn($this->customField);
        
        expect($column)->toBeInstanceOf(TextColumn::class);
        expect($column->getName())->toContain('customFieldValues');
        expect($column->getLabel())->toBe('Test Text Field');
    });
    
    it('applies column-specific configuration', function () {
        $this->customField->field_config = array_merge($this->customField->field_config, [
            'columnCharacterLimit' => 50,
            'columnWrap' => true,
        ]);
        
        $column = $this->textFieldType->makeTableColumn($this->customField);
        
        expect($column)->toBeInstanceOf(TextColumn::class);
    });
});

describe('TextFieldType Table Filter', function () {
    it('creates a filter component', function () {
        $filter = $this->textFieldType->makeTableFilter($this->customField);
        
        expect($filter)->toBeInstanceOf(BaseFilter::class);
        expect($filter->getName())->toBe('custom_fields_test_text');
        expect($filter->getLabel())->toBe('Test Text Field');
    });
    
    it('has a text input in filter form', function () {
        $filter = $this->textFieldType->makeTableFilter($this->customField);
        $form = $filter->getFormSchema();
        
        expect($form)->toBeArray();
        expect($form)->toHaveCount(1);
        expect($form[0])->toBeInstanceOf(TextInput::class);
        expect($form[0]->getName())->toBe('value');
    });
});

describe('TextFieldType Infolist Entry', function () {
    it('creates a text entry component', function () {
        $entry = $this->textFieldType->makeInfolistEntry($this->customField);
        
        expect($entry)->toBeInstanceOf(TextEntry::class);
        expect($entry->getName())->toContain('customFieldValues');
        expect($entry->getLabel())->toBe('Test Text Field');
    });
    
    it('applies entry-specific configuration', function () {
        $this->customField->field_config = array_merge($this->customField->field_config, [
            'infolistCharacterLimit' => 100,
            'copyable' => true,
        ]);
        
        $entry = $this->textFieldType->makeInfolistEntry($this->customField);
        
        expect($entry)->toBeInstanceOf(TextEntry::class);
    });
});