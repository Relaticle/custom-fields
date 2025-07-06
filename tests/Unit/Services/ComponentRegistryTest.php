<?php

declare(strict_types=1);

use Relaticle\CustomFields\Services\ComponentRegistry;

beforeEach(function (): void {
    $this->registry = new ComponentRegistry();
    $this->registry->initialize();
});

it('is initialized after calling initialize', function (): void {
    expect($this->registry->isInitialized())->toBeTrue();
});

it('registers and retrieves form components', function (): void {
    $this->registry->registerFormComponent('custom_type', 'CustomComponent');
    
    expect($this->registry->getFormComponent('custom_type'))->toBe('CustomComponent');
    expect($this->registry->hasFormComponent('custom_type'))->toBeTrue();
});

it('registers and retrieves table columns', function (): void {
    $this->registry->registerTableColumn('custom_type', 'CustomColumn');
    
    expect($this->registry->getTableColumn('custom_type'))->toBe('CustomColumn');
    expect($this->registry->hasTableColumn('custom_type'))->toBeTrue();
});

it('registers and retrieves table filters', function (): void {
    $this->registry->registerTableFilter('custom_type', 'CustomFilter');
    
    expect($this->registry->getTableFilter('custom_type'))->toBe('CustomFilter');
    expect($this->registry->hasTableFilter('custom_type'))->toBeTrue();
});

it('registers and retrieves infolist entries', function (): void {
    $this->registry->registerInfolistEntry('custom_type', 'CustomEntry');
    
    expect($this->registry->getInfolistEntry('custom_type'))->toBe('CustomEntry');
    expect($this->registry->hasInfolistEntry('custom_type'))->toBeTrue();
});

it('returns null for unregistered components', function (): void {
    expect($this->registry->getFormComponent('unknown'))->toBeNull();
    expect($this->registry->getTableColumn('unknown'))->toBeNull();
    expect($this->registry->getTableFilter('unknown'))->toBeNull();
    expect($this->registry->getInfolistEntry('unknown'))->toBeNull();
});

it('has default components registered', function (): void {
    // Form components
    expect($this->registry->hasFormComponent('text'))->toBeTrue();
    expect($this->registry->hasFormComponent('select'))->toBeTrue();
    expect($this->registry->hasFormComponent('date'))->toBeTrue();
    expect($this->registry->hasFormComponent('number'))->toBeTrue();
    
    // Table columns
    expect($this->registry->hasTableColumn('text'))->toBeTrue();
    expect($this->registry->hasTableColumn('boolean'))->toBeTrue();
    expect($this->registry->hasTableColumn('multiselect'))->toBeTrue();
    
    // Table filters
    expect($this->registry->hasTableFilter('text'))->toBeTrue();
    expect($this->registry->hasTableFilter('select'))->toBeTrue();
    expect($this->registry->hasTableFilter('boolean'))->toBeTrue();
    
    // Infolist entries
    expect($this->registry->hasInfolistEntry('text'))->toBeTrue();
    expect($this->registry->hasInfolistEntry('color'))->toBeTrue();
    expect($this->registry->hasInfolistEntry('tags'))->toBeTrue();
});

it('returns all registered components', function (): void {
    $formComponents = $this->registry->getAllFormComponents();
    $tableColumns = $this->registry->getAllTableColumns();
    $tableFilters = $this->registry->getAllTableFilters();
    $infolistEntries = $this->registry->getAllInfolistEntries();
    
    expect($formComponents)->toBeArray()->not->toBeEmpty();
    expect($tableColumns)->toBeArray()->not->toBeEmpty();
    expect($tableFilters)->toBeArray()->not->toBeEmpty();
    expect($infolistEntries)->toBeArray()->not->toBeEmpty();
});

it('overwrites existing registrations', function (): void {
    expect($this->registry->getFormComponent('text'))->toBe('TextInputComponent');
    
    $this->registry->registerFormComponent('text', 'CustomTextComponent');
    
    expect($this->registry->getFormComponent('text'))->toBe('CustomTextComponent');
});