<?php

declare(strict_types=1);

use Relaticle\CustomFields\Traits\HasFieldFilters;

class TestClassWithFieldFilters
{
    use HasFieldFilters;

    public function testShouldIncludeField(string $fieldCode): bool
    {
        return $this->shouldIncludeField($fieldCode);
    }

    public function testResetFieldFilters(): void
    {
        $this->resetFieldFilters();
    }
}

beforeEach(function (): void {
    $this->class = new TestClassWithFieldFilters();
});

it('includes all fields by default', function (): void {
    expect($this->class->testShouldIncludeField('field1'))->toBeTrue();
    expect($this->class->testShouldIncludeField('field2'))->toBeTrue();
    expect($this->class->testShouldIncludeField('field3'))->toBeTrue();
});

it('includes only specified fields when using only()', function (): void {
    $this->class->only(['field1', 'field2']);

    expect($this->class->testShouldIncludeField('field1'))->toBeTrue();
    expect($this->class->testShouldIncludeField('field2'))->toBeTrue();
    expect($this->class->testShouldIncludeField('field3'))->toBeFalse();
});

it('excludes specified fields when using except()', function (): void {
    $this->class->except(['field2']);

    expect($this->class->testShouldIncludeField('field1'))->toBeTrue();
    expect($this->class->testShouldIncludeField('field2'))->toBeFalse();
    expect($this->class->testShouldIncludeField('field3'))->toBeTrue();
});

it('merges multiple only() calls', function (): void {
    $this->class->only(['field1'])->only(['field2']);

    expect($this->class->testShouldIncludeField('field1'))->toBeTrue();
    expect($this->class->testShouldIncludeField('field2'))->toBeTrue();
    expect($this->class->testShouldIncludeField('field3'))->toBeFalse();
});

it('merges multiple except() calls', function (): void {
    $this->class->except(['field1'])->except(['field2']);

    expect($this->class->testShouldIncludeField('field1'))->toBeFalse();
    expect($this->class->testShouldIncludeField('field2'))->toBeFalse();
    expect($this->class->testShouldIncludeField('field3'))->toBeTrue();
});

it('removes duplicates in filters', function (): void {
    $this->class->only(['field1', 'field1', 'field2'])->only(['field2']);

    expect($this->class->testShouldIncludeField('field1'))->toBeTrue();
    expect($this->class->testShouldIncludeField('field2'))->toBeTrue();
});

it('resets filters correctly', function (): void {
    $this->class->only(['field1'])->except(['field2']);
    
    $this->class->testResetFieldFilters();

    expect($this->class->testShouldIncludeField('field1'))->toBeTrue();
    expect($this->class->testShouldIncludeField('field2'))->toBeTrue();
    expect($this->class->testShouldIncludeField('field3'))->toBeTrue();
});