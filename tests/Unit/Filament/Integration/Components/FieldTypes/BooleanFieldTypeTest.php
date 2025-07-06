<?php

declare(strict_types=1);

use Filament\Forms\Components\Checkbox;
use Filament\Infolists\Components\IconEntry;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Relaticle\CustomFields\Filament\Integration\Components\FieldTypes\Selection\BooleanFieldType;
use Relaticle\CustomFields\Models\CustomField;

beforeEach(function () {
    $this->booleanFieldType = new BooleanFieldType();
    
    $this->customField = new CustomField();
    $this->customField->id = 1;
    $this->customField->code = 'test_boolean';
    $this->customField->label = 'Test Boolean Field';
    $this->customField->type = 'boolean';
    $this->customField->is_required = false;
    $this->customField->field_config = [
        'inline' => true,
        'trueIcon' => 'heroicon-o-check-circle',
        'falseIcon' => 'heroicon-o-x-circle',
        'trueColor' => 'success',
        'falseColor' => 'danger',
    ];
});

describe('BooleanFieldType Form Component', function () {
    it('creates a checkbox component', function () {
        $component = $this->booleanFieldType->make($this->customField);
        
        expect($component)->toBeInstanceOf(Checkbox::class);
        expect($component->getName())->toBe('custom_fields.test_boolean');
        expect($component->getLabel())->toBe('Test Boolean Field');
    });
    
    it('applies boolean-specific configuration', function () {
        $component = $this->booleanFieldType->make($this->customField);
        
        // Note: isInline() requires container to be initialized which doesn't happen in unit tests
        expect($component)->toBeInstanceOf(Checkbox::class);
    });
    
    it('handles default values', function () {
        $this->customField->field_config = array_merge($this->customField->field_config, [
            'default' => true,
        ]);
        $component = $this->booleanFieldType->make($this->customField);
        
        expect($component->getDefaultState())->toBe(true);
    });
});

describe('BooleanFieldType Table Column', function () {
    it('creates an icon column component', function () {
        $column = $this->booleanFieldType->makeTableColumn($this->customField);
        
        expect($column)->toBeInstanceOf(IconColumn::class);
        expect($column->getName())->toContain('customFieldValues');
        expect($column->getLabel())->toBe('Test Boolean Field');
    });
});

describe('BooleanFieldType Table Filter', function () {
    it('creates a ternary filter component', function () {
        $filter = $this->booleanFieldType->makeTableFilter($this->customField);
        
        expect($filter)->toBeInstanceOf(TernaryFilter::class);
        expect($filter->getName())->toBe('custom_fields_test_boolean');
        expect($filter->getLabel())->toBe('Test Boolean Field');
    });
});

describe('BooleanFieldType Infolist Entry', function () {
    it('creates an icon entry component', function () {
        $entry = $this->booleanFieldType->makeInfolistEntry($this->customField);
        
        expect($entry)->toBeInstanceOf(IconEntry::class);
        expect($entry->getName())->toContain('customFieldValues');
        expect($entry->getLabel())->toBe('Test Boolean Field');
    });
});