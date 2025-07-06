<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Traits\HasModelContext;

class TestModel extends Model implements HasCustomFields
{
    use \Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
}

class RegularModel extends Model
{
}

class TestClassWithModelContext
{
    use HasModelContext;

    public function testGetModelClass(): ?string
    {
        return $this->getModelClass();
    }

    public function testGetRecord(): ?Model
    {
        return $this->getRecord();
    }

    public function testHasModelClass(): bool
    {
        return $this->hasModelClass();
    }

    public function testHasRecord(): bool
    {
        return $this->hasRecord();
    }

    public function testResetModelContext(): void
    {
        $this->resetModelContext();
    }
}

beforeEach(function (): void {
    $this->class = new TestClassWithModelContext();
});

it('sets model class correctly', function (): void {
    $this->class->forModel(TestModel::class);

    expect($this->class->testGetModelClass())->toBe(TestModel::class);
    expect($this->class->testHasModelClass())->toBeTrue();
});

it('throws exception for non-existent class', function (): void {
    expect(fn () => $this->class->forModel('NonExistentClass'))
        ->toThrow(InvalidArgumentException::class, 'Model class [NonExistentClass] does not exist.');
});

it('throws exception for non-model class', function (): void {
    expect(fn () => $this->class->forModel(stdClass::class))
        ->toThrow(InvalidArgumentException::class, 'Class [stdClass] must extend Eloquent Model.');
});

it('throws exception for model without HasCustomFields interface', function (): void {
    expect(fn () => $this->class->forModel(RegularModel::class))
        ->toThrow(InvalidArgumentException::class, 'Model [' . RegularModel::class . '] must implement HasCustomFields interface.');
});

it('sets record correctly', function (): void {
    $model = new TestModel();
    
    $this->class->forRecord($model);

    expect($this->class->testGetRecord())->toBe($model);
    expect($this->class->testGetModelClass())->toBe(TestModel::class);
    expect($this->class->testHasRecord())->toBeTrue();
});

it('throws exception for record without HasCustomFields interface', function (): void {
    $model = new RegularModel();
    
    expect(fn () => $this->class->forRecord($model))
        ->toThrow(InvalidArgumentException::class, 'Model must implement HasCustomFields interface.');
});

it('resets model context correctly', function (): void {
    $model = new TestModel();
    $this->class->forModel(TestModel::class)->forRecord($model);
    
    $this->class->testResetModelContext();

    expect($this->class->testGetModelClass())->toBeNull();
    expect($this->class->testGetRecord())->toBeNull();
    expect($this->class->testHasModelClass())->toBeFalse();
    expect($this->class->testHasRecord())->toBeFalse();
});