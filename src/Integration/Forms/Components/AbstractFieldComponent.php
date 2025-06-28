<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Integration\Forms\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;

/**
 * Abstract base class for form field components.
 * 
 * Eliminates duplication across 18+ component classes by providing
 * common structure and delegating to FieldConfigurator for shared logic.
 * 
 * Each concrete component only needs to implement createField() to specify
 * the Filament component type and its basic configuration.
 */
abstract readonly class AbstractFieldComponent implements FieldComponentInterface
{
    public function __construct(protected FieldConfigurator $configurator) {}

    /**
     * Create and configure a field component.
     * 
     * @param  array<string>  $dependentFieldCodes
     * @param  Collection<int, CustomField>|null  $allFields
     */
    final public function make(CustomField $customField, array $dependentFieldCodes = [], ?Collection $allFields = null): Field
    {
        $field = $this->createField($customField);
        
        /** @var Field */
        return $this->configurator->configure($field, $customField, $allFields, $dependentFieldCodes);
    }

    /**
     * Create the specific Filament field component.
     * 
     * Concrete implementations should create the appropriate Filament component
     * (TextInput, Select, etc.) with field-specific configuration.
     * 
     * Made public to allow composition patterns (like MultiSelectComponent).
     */
    abstract public function createField(CustomField $customField): Field;
}