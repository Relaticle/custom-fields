<?php

declare(strict_types=1);

use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Mockery\MockInterface;
use Relaticle\CustomFields\Filament\Integration\Components\Concerns\ConfiguresVisibility;
use Relaticle\CustomFields\Models\CustomField;

beforeEach(function () {
    $this->trait = new class
    {
        use ConfiguresVisibility {
            configureVisibility as public;
            evaluateVisibilityRules as public;
            evaluateCondition as public;
            compareEquals as public;
            compareContains as public;
            compareGreaterThan as public;
            compareLessThan as public;
            compareGreaterThanOrEqual as public;
            compareLessThanOrEqual as public;
            isEmpty as public;
            compareIn as public;
            createVisibilityClosure as public;
        }
    };
});

it('evaluates equals condition correctly', function () {
    expect($this->trait->compareEquals('test', 'test'))->toBeTrue();
    expect($this->trait->compareEquals('test', 'other'))->toBeFalse();
    expect($this->trait->compareEquals(123, '123'))->toBeTrue();
    expect($this->trait->compareEquals(['a', 'b'], ['a', 'b']))->toBeTrue();
    expect($this->trait->compareEquals(['a', 'b'], ['b', 'a']))->toBeTrue();
    expect($this->trait->compareEquals(true, 'true'))->toBeTrue();
    expect($this->trait->compareEquals(false, 'false'))->toBeTrue();
});

it('evaluates contains condition correctly', function () {
    expect($this->trait->compareContains(['a', 'b', 'c'], 'b'))->toBeTrue();
    expect($this->trait->compareContains(['a', 'b', 'c'], 'd'))->toBeFalse();
    expect($this->trait->compareContains('hello world', 'world'))->toBeTrue();
    expect($this->trait->compareContains('hello world', 'foo'))->toBeFalse();
    expect($this->trait->compareContains(123, 'test'))->toBeFalse();
});

it('evaluates numeric comparisons correctly', function () {
    expect($this->trait->compareGreaterThan(10, 5))->toBeTrue();
    expect($this->trait->compareGreaterThan(5, 10))->toBeFalse();
    expect($this->trait->compareGreaterThan('10', '5'))->toBeTrue();
    expect($this->trait->compareGreaterThan('abc', 5))->toBeFalse();

    expect($this->trait->compareLessThan(5, 10))->toBeTrue();
    expect($this->trait->compareLessThan(10, 5))->toBeFalse();

    expect($this->trait->compareGreaterThanOrEqual(10, 10))->toBeTrue();
    expect($this->trait->compareGreaterThanOrEqual(10, 5))->toBeTrue();
    expect($this->trait->compareGreaterThanOrEqual(5, 10))->toBeFalse();

    expect($this->trait->compareLessThanOrEqual(10, 10))->toBeTrue();
    expect($this->trait->compareLessThanOrEqual(5, 10))->toBeTrue();
    expect($this->trait->compareLessThanOrEqual(10, 5))->toBeFalse();
});

it('evaluates empty condition correctly', function () {
    expect($this->trait->isEmpty(''))->toBeTrue();
    expect($this->trait->isEmpty(null))->toBeTrue();
    expect($this->trait->isEmpty([]))->toBeTrue();
    expect($this->trait->isEmpty(0))->toBeTrue();
    expect($this->trait->isEmpty('0'))->toBeTrue();
    expect($this->trait->isEmpty('test'))->toBeFalse();
    expect($this->trait->isEmpty(['a']))->toBeFalse();
});

it('evaluates in condition correctly', function () {
    expect($this->trait->compareIn('b', ['a', 'b', 'c']))->toBeTrue();
    expect($this->trait->compareIn('d', ['a', 'b', 'c']))->toBeFalse();
    expect($this->trait->compareIn(2, [1, 2, 3]))->toBeTrue();
    expect($this->trait->compareIn('test', 'not an array'))->toBeFalse();
});

it('evaluates single condition with all operators', function () {
    $testCases = [
        ['operator' => 'equals', 'current' => 'test', 'expected' => 'test', 'result' => true],
        ['operator' => 'not_equals', 'current' => 'test', 'expected' => 'other', 'result' => true],
        ['operator' => 'contains', 'current' => ['a', 'b'], 'expected' => 'a', 'result' => true],
        ['operator' => 'not_contains', 'current' => ['a', 'b'], 'expected' => 'c', 'result' => true],
        ['operator' => 'greater_than', 'current' => 10, 'expected' => 5, 'result' => true],
        ['operator' => 'less_than', 'current' => 5, 'expected' => 10, 'result' => true],
        ['operator' => 'empty', 'current' => '', 'expected' => null, 'result' => true],
        ['operator' => 'not_empty', 'current' => 'value', 'expected' => null, 'result' => true],
        ['operator' => 'in', 'current' => 'b', 'expected' => ['a', 'b', 'c'], 'result' => true],
        ['operator' => 'not_in', 'current' => 'd', 'expected' => ['a', 'b', 'c'], 'result' => true],
        ['operator' => 'unknown', 'current' => 'any', 'expected' => 'any', 'result' => true],
    ];

    foreach ($testCases as $testCase) {
        $result = $this->trait->evaluateCondition(
            $testCase['current'],
            $testCase['operator'],
            $testCase['expected']
        );

        expect($result)->toBe($testCase['result']);
    }
});

it('evaluates visibility rules with AND logic', function () {
    $get = function ($path) {
        $values = [
            'custom_fields.field1' => 'value1',
            'custom_fields.field2' => 'value2',
        ];
        return $values[$path] ?? null;
    };

    $visibilityRules = [
        'logic' => 'and',
        'conditions' => [
            ['field' => 'field1', 'operator' => 'equals', 'value' => 'value1'],
            ['field' => 'field2', 'operator' => 'equals', 'value' => 'value2'],
        ],
    ];

    $result = $this->trait->evaluateVisibilityRules($get, $visibilityRules, ['field1', 'field2']);
    expect($result)->toBeTrue();

    // Change one condition to fail
    $visibilityRules['conditions'][1]['value'] = 'wrong_value';
    $result = $this->trait->evaluateVisibilityRules($get, $visibilityRules, ['field1', 'field2']);
    expect($result)->toBeFalse();
});

it('evaluates visibility rules with OR logic', function () {
    $get = function ($path) {
        $values = [
            'custom_fields.field1' => 'value1',
            'custom_fields.field2' => 'wrong_value',
        ];
        return $values[$path] ?? null;
    };

    $visibilityRules = [
        'logic' => 'or',
        'conditions' => [
            ['field' => 'field1', 'operator' => 'equals', 'value' => 'value1'],
            ['field' => 'field2', 'operator' => 'equals', 'value' => 'value2'],
        ],
    ];

    $result = $this->trait->evaluateVisibilityRules($get, $visibilityRules, ['field1', 'field2']);
    expect($result)->toBeTrue();

    // Make both conditions fail
    $visibilityRules['conditions'][0]['value'] = 'wrong_value';
    $result = $this->trait->evaluateVisibilityRules($get, $visibilityRules, ['field1', 'field2']);
    expect($result)->toBeFalse();
});

it('returns true when no visibility rules exist', function () {
    $get = function ($path) {
        return null;
    };

    $result = $this->trait->evaluateVisibilityRules($get, [], ['field1']);
    expect($result)->toBeTrue();

    $result = $this->trait->evaluateVisibilityRules($get, ['conditions' => []], ['field1']);
    expect($result)->toBeTrue();
});

it('ignores conditions for fields not in dependent list', function () {
    $get = function ($path) {
        return 'any_value';
    };

    $visibilityRules = [
        'logic' => 'and',
        'conditions' => [
            ['field' => 'field1', 'operator' => 'equals', 'value' => 'value1'],
            ['field' => 'field2', 'operator' => 'equals', 'value' => 'value2'],
        ],
    ];

    // Only field1 is in dependent list
    $result = $this->trait->evaluateVisibilityRules($get, $visibilityRules, ['field1']);
    expect($result)->toBeTrue(); // Returns true because field2 condition is ignored
});

it('creates visibility closure for non-form components', function () {
    $record = new class
    {
        public function getCustomFieldValue($code)
        {
            $values = [
                'field1' => 'value1',
                'field2' => 'value2',
            ];
            return $values[$code] ?? null;
        }
    };

    $visibilityRules = [
        'logic' => 'and',
        'conditions' => [
            ['field' => 'field1', 'operator' => 'equals', 'value' => 'value1'],
            ['field' => 'field2', 'operator' => 'equals', 'value' => 'value2'],
        ],
    ];

    $closure = $this->trait->createVisibilityClosure($visibilityRules, ['field1', 'field2']);
    
    expect($closure($record))->toBeTrue();
});