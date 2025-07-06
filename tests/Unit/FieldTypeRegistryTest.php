<?php

declare(strict_types=1);

use Relaticle\CustomFields\Contracts\Components\FormComponentInterface;
use Relaticle\CustomFields\Contracts\Components\InfolistEntryInterface;
use Relaticle\CustomFields\Contracts\Components\TableColumnInterface;
use Relaticle\CustomFields\Contracts\Components\TableFilterInterface;

it('can load field type registry configuration', function () {
    $registry = require __DIR__ . '/../../config/field-type-registry.php';
    
    expect($registry)->toBeArray();
    expect($registry)->toHaveKeys(['text', 'textarea', 'number', 'boolean', 'select', 'date', 'tags']);
});

it('all registered field types implement required interfaces', function () {
    $registry = require __DIR__ . '/../../config/field-type-registry.php';
    
    foreach ($registry as $type => $class) {
        expect(class_exists($class))->toBeTrue("Class {$class} for type {$type} does not exist");
        
        $instance = new $class();
        
        expect($instance)->toBeInstanceOf(FormComponentInterface::class);
        expect($instance)->toBeInstanceOf(TableColumnInterface::class);
        expect($instance)->toBeInstanceOf(TableFilterInterface::class);
        expect($instance)->toBeInstanceOf(InfolistEntryInterface::class);
    }
});

it('can instantiate all field types', function () {
    $registry = require __DIR__ . '/../../config/field-type-registry.php';
    
    foreach ($registry as $type => $class) {
        $instance = new $class();
        expect($instance)->not->toBeNull();
    }
});

it('field types have unique type identifiers', function () {
    $registry = require __DIR__ . '/../../config/field-type-registry.php';
    
    $types = array_keys($registry);
    $uniqueTypes = array_unique($types);
    
    expect(count($types))->toBe(count($uniqueTypes));
});