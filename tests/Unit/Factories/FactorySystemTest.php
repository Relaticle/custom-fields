<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Relaticle\CustomFields\Contracts\Components\FormComponentInterface;
use Relaticle\CustomFields\Contracts\Components\InfolistEntryInterface;
use Relaticle\CustomFields\Contracts\Components\TableColumnInterface;
use Relaticle\CustomFields\Contracts\Components\TableFilterInterface;
use Relaticle\CustomFields\Filament\Integration\Factories\FormComponentFactory;
use Relaticle\CustomFields\Filament\Integration\Factories\InfolistComponentFactory;
use Relaticle\CustomFields\Filament\Integration\Factories\TableComponentFactory;
use Relaticle\CustomFields\Filament\Integration\Factories\UnsupportedFieldTypeException;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\ComponentRegistry;

afterEach(function () {
    Mockery::close();
});

describe('FormComponentFactory', function () {
    it('creates form components successfully', function () {
        $registry = Mockery::mock(ComponentRegistry::class);
        $registry->shouldReceive('isInitialized')->andReturn(true);
        $registry->shouldReceive('getFormComponent')
            ->with('text')
            ->andReturn('TextInputComponent');

        $factory = new FormComponentFactory($registry);

        expect($factory->getComponentClass('text'))->toBe('TextInputComponent');
    });

    it('uses custom components over registry components', function () {
        $registry = Mockery::mock(ComponentRegistry::class);
        $registry->shouldReceive('isInitialized')->andReturn(true);

        $factory = new FormComponentFactory($registry);
        $factory->registerComponentClass('custom', 'CustomComponent');

        expect($factory->getComponentClass('custom'))->toBe('CustomComponent');
    });

    it('returns correct expected interface', function () {
        $registry = Mockery::mock(ComponentRegistry::class);
        $registry->shouldReceive('isInitialized')->andReturn(true);

        $factory = new FormComponentFactory($registry);
        
        $reflection = new \ReflectionClass($factory);
        $method = $reflection->getMethod('getExpectedInterface');
        $method->setAccessible(true);

        expect($method->invoke($factory))->toBe(FormComponentInterface::class);
    });
});

describe('TableComponentFactory', function () {
    it('creates table columns successfully', function () {
        $registry = Mockery::mock(ComponentRegistry::class);
        $registry->shouldReceive('isInitialized')->andReturn(true);
        $registry->shouldReceive('getTableColumn')
            ->with('text')
            ->andReturn('TextColumn');

        $factory = new TableComponentFactory($registry);

        expect($factory->getComponentClass('text'))->toBe('TextColumn');
    });

    it('creates table filters successfully', function () {
        $registry = Mockery::mock(ComponentRegistry::class);
        $registry->shouldReceive('isInitialized')->andReturn(true);
        $registry->shouldReceive('getTableFilter')
            ->with('select')
            ->andReturn('SelectFilter');

        $factory = new TableComponentFactory($registry);
        
        // Set component type to filter
        $reflection = new \ReflectionClass($factory);
        $property = $reflection->getProperty('componentType');
        $property->setAccessible(true);
        $property->setValue($factory, 'filter');

        expect($factory->getComponentClass('select'))->toBe('SelectFilter');
    });

    it('registers custom column classes', function () {
        $registry = Mockery::mock(ComponentRegistry::class);
        $registry->shouldReceive('isInitialized')->andReturn(true);

        $factory = new TableComponentFactory($registry);
        $factory->registerColumnClass('custom', 'CustomColumn');

        expect($factory->getComponentClass('custom'))->toBe('CustomColumn');
    });

    it('registers custom filter classes', function () {
        $registry = Mockery::mock(ComponentRegistry::class);
        $registry->shouldReceive('isInitialized')->andReturn(true);

        $factory = new TableComponentFactory($registry);
        $factory->registerFilterClass('custom', 'CustomFilter');

        // Set component type to filter
        $reflection = new \ReflectionClass($factory);
        $property = $reflection->getProperty('componentType');
        $property->setAccessible(true);
        $property->setValue($factory, 'filter');

        expect($factory->getComponentClass('custom'))->toBe('CustomFilter');
    });
});

describe('InfolistComponentFactory', function () {
    it('creates infolist entries successfully', function () {
        $registry = Mockery::mock(ComponentRegistry::class);
        $registry->shouldReceive('isInitialized')->andReturn(true);
        $registry->shouldReceive('getInfolistEntry')
            ->with('text')
            ->andReturn('TextEntry');

        $factory = new InfolistComponentFactory($registry);

        expect($factory->getComponentClass('text'))->toBe('TextEntry');
    });

    it('uses custom components over registry components', function () {
        $registry = Mockery::mock(ComponentRegistry::class);
        $registry->shouldReceive('isInitialized')->andReturn(true);

        $factory = new InfolistComponentFactory($registry);
        $factory->registerComponentClass('custom', 'CustomEntry');

        expect($factory->getComponentClass('custom'))->toBe('CustomEntry');
    });

    it('returns correct expected interface', function () {
        $registry = Mockery::mock(ComponentRegistry::class);
        $registry->shouldReceive('isInitialized')->andReturn(true);

        $factory = new InfolistComponentFactory($registry);
        
        $reflection = new \ReflectionClass($factory);
        $method = $reflection->getMethod('getExpectedInterface');
        $method->setAccessible(true);

        expect($method->invoke($factory))->toBe(InfolistEntryInterface::class);
    });
});

describe('UnsupportedFieldTypeException', function () {
    it('creates missing registration exception with correct message', function () {
        $exception = UnsupportedFieldTypeException::missingRegistration('custom', 'form');

        expect($exception)->toBeInstanceOf(UnsupportedFieldTypeException::class);
        expect($exception->getMessage())->toContain("No form component registered for field type 'custom'");
    });

    it('creates invalid component class exception with correct message', function () {
        $exception = UnsupportedFieldTypeException::invalidComponentClass(
            'text',
            'CustomComponent',
            'ExpectedInterface'
        );

        expect($exception)->toBeInstanceOf(UnsupportedFieldTypeException::class);
        expect($exception->getMessage())->toContain("must implement ExpectedInterface");
    });

    it('creates class not found exception with correct message', function () {
        $exception = UnsupportedFieldTypeException::classNotFound('text', 'NonExistent');

        expect($exception)->toBeInstanceOf(UnsupportedFieldTypeException::class);
        expect($exception->getMessage())->toContain("does not exist");
    });
});