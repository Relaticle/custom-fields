<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts\Factories;

use Relaticle\CustomFields\Models\CustomField;

/**
 * ABOUTME: Interface for factories that create Filament components from custom field definitions
 * ABOUTME: Extended by specific factories for forms, tables, and infolists
 */
interface ComponentFactoryInterface extends FactoryInterface
{
    /**
     * Create a component for the given custom field
     *
     * @param  CustomField  $field
     * @return mixed The created component (Field, Column, Filter, Entry, etc.)
     */
    public function createComponent(CustomField $field): mixed;

    /**
     * Get the component class for a specific field type
     *
     * @param  string  $fieldType
     * @return class-string|null
     */
    public function getComponentClass(string $fieldType): ?string;

    /**
     * Register a custom component class for a field type
     *
     * @param  string  $fieldType
     * @param  class-string  $componentClass
     * @return void
     */
    public function registerComponentClass(string $fieldType, string $componentClass): void;
}