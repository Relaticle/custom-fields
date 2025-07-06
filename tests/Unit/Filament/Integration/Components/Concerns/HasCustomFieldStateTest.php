<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Mockery\MockInterface;
use Relaticle\CustomFields\Enums\FieldType;
use Relaticle\CustomFields\Filament\Integration\Components\Concerns\HasCustomFieldState;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldValue;

// Helper function to create a custom field for testing
function createCustomField($attributes = []) {
    $field = Mockery::mock(CustomField::class)->makePartial();
    
    // Set up property access for all attributes
    foreach ($attributes as $key => $value) {
        $field->{$key} = $value;
    }
    
    // Allow getting any property
    $field->shouldAllowMockingMethod('__get');
    $field->shouldReceive('__get')->andReturnUsing(function ($key) use ($field) {
        return $field->{$key} ?? null;
    });
    
    return $field;
}

beforeEach(function () {
    $this->trait = new class
    {
        use HasCustomFieldState {
            resolveState as public;
            getDefaultValue as public;
            processValue as public;
            processNumericValue as public;
            processBooleanValue as public;
            processDateValue as public;
            processDateTimeValue as public;
            processArrayValue as public;
            getStateAttributeName as public;
            saveCustomFieldValue as public;
            prepareValueForStorage as public;
        }
    };
});

it('resolves state from existing model with custom field value')->skip('Complex mocking issue - needs integration test');

it('returns default value when model does not exist', function () {
    $field = createCustomField([
        'type' => (object) ['value' => 'text'],
        'field_config' => ['default' => 'default text']
    ]);

    $record = Mockery::mock(Model::class);
    $record->shouldReceive('exists')->andReturn(false);

    $result = $this->trait->resolveState($record, $field);

    expect($result)->toBe('default text');
});

it('returns default value when no custom field value exists', function () {
    $field = createCustomField([
        'id' => 1,
        'type' => (object) ['value' => 'number'],
        'field_config' => []
    ]);

    $customFieldValuesRelation = Mockery::mock();
    $customFieldValuesRelation->shouldReceive('where')
        ->with('custom_field_id', 1)
        ->andReturnSelf();
    $customFieldValuesRelation->shouldReceive('first')
        ->andReturn(null);

    $record = Mockery::mock(Model::class);
    $record->shouldReceive('exists')->andReturn(true);
    $record->shouldReceive('customFieldValues')->andReturn($customFieldValuesRelation);

    $result = $this->trait->resolveState($record, $field);

    expect($result)->toBe(0);
});

it('returns type-specific default values', function () {
    $testCases = [
        ['type' => 'boolean', 'expected' => false],
        ['type' => 'toggle', 'expected' => false],
        ['type' => 'number', 'expected' => 0],
        ['type' => 'currency', 'expected' => 0],
        ['type' => 'multiselect', 'expected' => []],
        ['type' => 'checkbox_list', 'expected' => []],
        ['type' => 'tags', 'expected' => []],
        ['type' => 'text', 'expected' => null],
    ];

    foreach ($testCases as $testCase) {
        $field = createCustomField([
            'type' => (object) ['value' => $testCase['type']],
            'field_config' => []
        ]);

        $result = $this->trait->getDefaultValue($field);

        expect($result)->toBe($testCase['expected']);
    }
});

it('processes numeric values correctly', function () {
    expect($this->trait->processNumericValue('123'))->toBe(123);
    expect($this->trait->processNumericValue('123.45'))->toBe(123.45);
    expect($this->trait->processNumericValue(456))->toBe(456);
    expect($this->trait->processNumericValue(null))->toBeNull();
    expect($this->trait->processNumericValue(''))->toBeNull();
    expect($this->trait->processNumericValue('abc'))->toBeNull();
});

it('processes boolean values correctly', function () {
    expect($this->trait->processBooleanValue(true))->toBeTrue();
    expect($this->trait->processBooleanValue('true'))->toBeTrue();
    expect($this->trait->processBooleanValue('1'))->toBeTrue();
    expect($this->trait->processBooleanValue(1))->toBeTrue();
    expect($this->trait->processBooleanValue(false))->toBeFalse();
    expect($this->trait->processBooleanValue('false'))->toBeFalse();
    expect($this->trait->processBooleanValue('0'))->toBeFalse();
    expect($this->trait->processBooleanValue(0))->toBeFalse();
});

it('processes date values correctly', function () {
    expect($this->trait->processDateValue('2024-01-15'))->toBe('2024-01-15');
    expect($this->trait->processDateValue('2024-01-15 10:30:00'))->toBe('2024-01-15');
    expect($this->trait->processDateValue(''))->toBeNull();
    expect($this->trait->processDateValue(null))->toBeNull();
    expect($this->trait->processDateValue('invalid date'))->toBeNull();
});

it('processes array values correctly', function () {
    expect($this->trait->processArrayValue(['a', 'b', 'c']))->toBe(['a', 'b', 'c']);
    expect($this->trait->processArrayValue('["x", "y", "z"]'))->toBe(['x', 'y', 'z']);
    expect($this->trait->processArrayValue('invalid json'))->toBe([]);
    expect($this->trait->processArrayValue(null))->toBe([]);
});

it('saves custom field value to database')->skip('Requires database integration');

it('prepares values for storage based on field type', function () {
    $testCases = [
        ['type' => 'multiselect', 'value' => ['a', 'b'], 'expected' => '["a","b"]'],
        ['type' => 'boolean', 'value' => 'true', 'expected' => true],
        ['type' => 'number', 'value' => '123.45', 'expected' => 123.45],
        ['type' => 'currency', 'value' => '123.456', 'expected' => 123.46],
        ['type' => 'text', 'value' => 'hello', 'expected' => 'hello'],
    ];

    foreach ($testCases as $testCase) {
        $field = createCustomField([
            'type' => (object) ['value' => $testCase['type']]
        ]);

        $result = $this->trait->prepareValueForStorage($testCase['value'], $field);

        expect($result)->toBe($testCase['expected']);
    }
});

it('generates correct state attribute name', function () {
    $field = createCustomField([
        'code' => 'company_name'
    ]);

    $result = $this->trait->getStateAttributeName($field);

    expect($result)->toBe('custom_fields.company_name');
});