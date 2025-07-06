<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Relaticle\CustomFields\Contracts\Components\FormComponentInterface;
use Relaticle\CustomFields\Filament\Integration\Factories\AbstractComponentFactory;
use Relaticle\CustomFields\Filament\Integration\Factories\UnsupportedFieldTypeException;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\ComponentRegistry;

afterEach(function () {
    Mockery::close();
});

it('initializes component registry if not initialized', function () {
    $registry = Mockery::mock(ComponentRegistry::class);
    $registry->shouldReceive('isInitialized')->once()->andReturn(false);
    $registry->shouldReceive('initialize')->once();

    new TestComponentFactory($registry);
});

it('does not reinitialize component registry if already initialized', function () {
    $registry = Mockery::mock(ComponentRegistry::class);
    $registry->shouldReceive('isInitialized')->once()->andReturn(true);
    $registry->shouldNotReceive('initialize');

    new TestComponentFactory($registry);
});

it('creates component successfully', function () {
    $registry = Mockery::mock(ComponentRegistry::class);
    $registry->shouldReceive('isInitialized')->andReturn(true);

    $field = Mockery::mock(CustomField::class)->makePartial();
    $field->shouldReceive('getAttribute')->with('type')->andReturn((object) ['value' => 'text']);

    $componentInstance = Mockery::mock(FormComponentInterface::class);
    $expectedComponent = new \stdClass();

    $factory = Mockery::mock(TestComponentFactory::class, [$registry])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $factory->shouldReceive('getComponentClass')
        ->with('text')
        ->once()
        ->andReturn('TestFormComponent');

    $factory->shouldReceive('getExpectedInterface')
        ->once()
        ->andReturn(FormComponentInterface::class);

    $factory->shouldReceive('configureComponent')
        ->with($componentInstance, $field)
        ->once()
        ->andReturn($expectedComponent);

    // Mock the app() helper
    app()->instance('TestFormComponent', $componentInstance);

    $result = $factory->createComponent($field);

    expect($result)->toBe($expectedComponent);
});

it('throws exception when no component registered', function () {
    $registry = Mockery::mock(ComponentRegistry::class);
    $registry->shouldReceive('isInitialized')->andReturn(true);

    $field = Mockery::mock(CustomField::class)->makePartial();
    $field->shouldReceive('getAttribute')->with('type')->andReturn((object) ['value' => 'text']);

    $factory = Mockery::mock(TestComponentFactory::class, [$registry])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $factory->shouldReceive('getComponentClass')
        ->with('text')
        ->once()
        ->andReturn(null);

    $factory->createComponent($field);
})->throws(UnsupportedFieldTypeException::class, 'No component registered for field type: text');

it('throws exception when component class does not exist', function () {
    $registry = Mockery::mock(ComponentRegistry::class);
    $registry->shouldReceive('isInitialized')->andReturn(true);

    $field = Mockery::mock(CustomField::class)->makePartial();
    $field->shouldReceive('getAttribute')->with('type')->andReturn((object) ['value' => 'text']);

    $factory = Mockery::mock(TestComponentFactory::class, [$registry])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $factory->shouldReceive('getComponentClass')
        ->with('text')
        ->once()
        ->andReturn('NonExistentClass');

    $factory->createComponent($field);
})->throws(UnsupportedFieldTypeException::class, 'Component class does not exist: NonExistentClass for field type: text');

it('throws exception when component does not implement expected interface', function () {
    $registry = Mockery::mock(ComponentRegistry::class);
    $registry->shouldReceive('isInitialized')->andReturn(true);

    $field = Mockery::mock(CustomField::class)->makePartial();
    $field->shouldReceive('getAttribute')->with('type')->andReturn((object) ['value' => 'text']);

    $componentInstance = new \stdClass();

    $factory = Mockery::mock(TestComponentFactory::class, [$registry])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $factory->shouldReceive('getComponentClass')
        ->with('text')
        ->once()
        ->andReturn(\stdClass::class);

    $factory->shouldReceive('getExpectedInterface')
        ->once()
        ->andReturn(FormComponentInterface::class);

    // Mock the app() helper
    app()->instance(\stdClass::class, $componentInstance);

    $factory->createComponent($field);
})->throws(UnsupportedFieldTypeException::class, 'Component class stdClass must implement ' . FormComponentInterface::class);

it('returns true for supports with registered type', function () {
    $registry = Mockery::mock(ComponentRegistry::class);
    $registry->shouldReceive('isInitialized')->andReturn(true);

    $factory = Mockery::mock(TestComponentFactory::class, [$registry])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $factory->shouldReceive('getComponentClass')
        ->with('text')
        ->once()
        ->andReturn('SomeComponentClass');

    expect($factory->supports('text'))->toBeTrue();
});

it('returns false for supports with unregistered type', function () {
    $registry = Mockery::mock(ComponentRegistry::class);
    $registry->shouldReceive('isInitialized')->andReturn(true);

    $factory = Mockery::mock(TestComponentFactory::class, [$registry])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $factory->shouldReceive('getComponentClass')
        ->with('unknown')
        ->once()
        ->andReturn(null);

    expect($factory->supports('unknown'))->toBeFalse();
});

it('returns false for supports with non-string type', function () {
    $registry = Mockery::mock(ComponentRegistry::class);
    $registry->shouldReceive('isInitialized')->andReturn(true);

    $factory = new TestComponentFactory($registry);

    expect($factory->supports(123))->toBeFalse();
    expect($factory->supports([]))->toBeFalse();
    expect($factory->supports(null))->toBeFalse();
});

it('registers component class and stores custom component', function () {
    $registry = Mockery::mock(ComponentRegistry::class);
    $registry->shouldReceive('isInitialized')->andReturn(true);

    $factory = new TestComponentFactory($registry);
    $factory->registerComponentClass('custom_type', 'CustomComponentClass');

    // Access protected property via reflection
    $reflection = new \ReflectionClass($factory);
    $property = $reflection->getProperty('customComponents');
    $property->setAccessible(true);
    $customComponents = $property->getValue($factory);

    expect($customComponents)->toHaveKey('custom_type');
    expect($customComponents['custom_type'])->toBe('CustomComponentClass');
});

/**
 * Mock form component class for testing
 */
class TestFormComponent implements FormComponentInterface
{
    public function make(CustomField $customField, array $dependentFieldCodes = [], ?\Illuminate\Support\Collection $allFields = null): \Filament\Forms\Components\Field
    {
        return \Filament\Forms\Components\TextInput::make('test');
    }
}

/**
 * Test implementation of AbstractComponentFactory
 */
class TestComponentFactory extends AbstractComponentFactory
{
    public function getComponentClass(string $fieldType): ?string
    {
        return $this->customComponents[$fieldType] ?? null;
    }

    protected function getExpectedInterface(): string
    {
        return FormComponentInterface::class;
    }

    protected function configureComponent(mixed $componentInstance, CustomField $field): mixed
    {
        return new \stdClass();
    }
}