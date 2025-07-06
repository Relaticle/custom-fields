<?php

declare(strict_types=1);

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Mockery\MockInterface;
use Relaticle\CustomFields\Filament\Integration\Components\Base\CustomFieldInput;
use Relaticle\CustomFields\Models\CustomField;

beforeEach(function () {
    $this->customFieldInput = new class extends CustomFieldInput
    {
        protected function createField(CustomField $customField): Field
        {
            return TextInput::make($this->getStateAttributeName($customField));
        }

        protected function applyFieldSpecificConfiguration(Field $field, CustomField $customField): void
        {
            // Test implementation - no specific configuration
        }
    };
});

it('creates a field with basic configuration', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'test_field';
    $customField->label = 'Test Field';
    $customField->is_required = true;
    $customField->is_readonly = false;
    $customField->help_text = 'Help text';
    $customField->hint = 'Hint text';
    $customField->field_config = [];
    $customField->type = (object) ['value' => 'text'];

    $field = $this->customFieldInput->make($customField);

    expect($field)->toBeInstanceOf(TextInput::class);
    expect($field->getLabel())->toBe('Test Field');
    expect($field->isRequired())->toBeTrue();
    expect($field->isDisabled())->toBeFalse();
    expect($field->getHelperText())->toBe('Help text');
    expect($field->getHint())->toBe('Hint text');
});

it('applies field configuration from field_config', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'test_field';
    $customField->label = 'Test Field';
    $customField->is_required = false;
    $customField->is_readonly = false;
    $customField->help_text = null;
    $customField->hint = null;
    $customField->field_config = [
        'placeholder' => 'Enter value',
        'default' => 'Default value',
        'columnSpan' => 2,
        'inlineLabel' => true,
        'hidden' => false,
    ];
    $customField->type = (object) ['value' => 'text'];

    $field = $this->customFieldInput->make($customField);

    expect($field->getPlaceholder())->toBe('Enter value');
    expect($field->getDefault())->toBe('Default value');
    expect($field->getColumnSpan())->toBe(2);
});

it('configures validation rules correctly', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'email_field';
    $customField->label = 'Email';
    $customField->is_required = true;
    $customField->is_readonly = false;
    $customField->help_text = null;
    $customField->hint = null;
    $customField->field_config = [
        'validation_rules' => ['max:255'],
    ];
    $customField->type = (object) ['value' => 'email'];

    $field = $this->customFieldInput->make($customField);

    $rules = $field->getValidationRules();
    
    expect($rules)->toContain('required');
    expect($rules)->toContain('max:255');
    expect($rules)->toContain('email');
});

it('applies field type specific validation rules', function () {
    $testCases = [
        ['type' => 'email', 'expected' => ['email']],
        ['type' => 'url', 'expected' => ['url']],
        ['type' => 'number', 'expected' => ['numeric']],
        ['type' => 'tel', 'expected' => ['regex:/^[+]?[0-9\s\-\(\)]+$/']],
        ['type' => 'color', 'expected' => ['regex:/^#[0-9A-Fa-f]{6}$/']],
    ];

    foreach ($testCases as $testCase) {
        $customField = Mockery::mock(CustomField::class);
        $customField->code = 'test_field';
        $customField->label = 'Test Field';
        $customField->is_required = false;
        $customField->is_readonly = false;
        $customField->help_text = null;
        $customField->hint = null;
        $customField->field_config = [];
        $customField->type = (object) ['value' => $testCase['type']];

        $field = $this->customFieldInput->make($customField);
        $rules = $field->getValidationRules();

        foreach ($testCase['expected'] as $expectedRule) {
            expect($rules)->toContain($expectedRule);
        }
    }
});

it('configures numeric validation with min and max', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'number_field';
    $customField->label = 'Number';
    $customField->is_required = true;
    $customField->is_readonly = false;
    $customField->help_text = null;
    $customField->hint = null;
    $customField->field_config = [
        'min' => 10,
        'max' => 100,
    ];
    $customField->type = (object) ['value' => 'number'];

    $field = $this->customFieldInput->make($customField);
    $rules = $field->getValidationRules();

    expect($rules)->toContain('numeric');
    expect($rules)->toContain('min:10');
    expect($rules)->toContain('max:100');
});

it('creates field with visibility configuration', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'dependent_field';
    $customField->label = 'Dependent Field';
    $customField->is_required = false;
    $customField->is_readonly = false;
    $customField->help_text = null;
    $customField->hint = null;
    $customField->field_config = [];
    $customField->type = (object) ['value' => 'text'];
    $customField->visibility_rules = [
        'logic' => 'and',
        'conditions' => [
            ['field' => 'master_field', 'operator' => 'equals', 'value' => 'show'],
        ],
    ];

    $field = $this->customFieldInput->make(
        $customField,
        ['master_field'],
        collect()
    );

    expect($field)->toBeInstanceOf(TextInput::class);
    // The visibility configuration would be applied through the configureVisibility method
});

it('handles nullable fields correctly', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'optional_field';
    $customField->label = 'Optional Field';
    $customField->is_required = false;
    $customField->is_readonly = false;
    $customField->help_text = null;
    $customField->hint = null;
    $customField->field_config = [];
    $customField->type = (object) ['value' => 'text'];

    $field = $this->customFieldInput->make($customField);
    $rules = $field->getValidationRules();

    expect($rules)->toContain('nullable');
    expect($rules)->not->toContain('required');
});

it('applies hidden configuration', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'hidden_field';
    $customField->label = 'Hidden Field';
    $customField->is_required = false;
    $customField->is_readonly = false;
    $customField->help_text = null;
    $customField->hint = null;
    $customField->field_config = [
        'hidden' => true,
    ];
    $customField->type = (object) ['value' => 'text'];

    $field = $this->customFieldInput->make($customField);

    expect($field->isHidden())->toBeTrue();
});