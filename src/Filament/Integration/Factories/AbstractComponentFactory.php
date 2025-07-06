<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Factories;

use Relaticle\CustomFields\Contracts\Factories\ComponentFactoryInterface;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\ComponentRegistry;

/**
 * ABOUTME: Abstract base class for component factories providing common functionality
 * ABOUTME: Extended by specific factories for forms, tables, and infolists
 */
abstract class AbstractComponentFactory implements ComponentFactoryInterface
{
    /**
     * @var array<string, class-string>
     */
    protected array $customComponents = [];

    /**
     * Constructor
     *
     * @param  ComponentRegistry  $componentRegistry
     */
    public function __construct(
        protected ComponentRegistry $componentRegistry
    ) {
        if (! $this->componentRegistry->isInitialized()) {
            $this->componentRegistry->initialize();
        }
    }

    /**
     * Create a component for the given custom field
     *
     * @param  CustomField  $field
     * @return mixed The created component
     *
     * @throws UnsupportedFieldTypeException
     */
    public function createComponent(CustomField $field): mixed
    {
        $componentClass = $this->getComponentClass($field->type->value);

        if (! $componentClass) {
            throw new UnsupportedFieldTypeException(
                "No component registered for field type: {$field->type->value}"
            );
        }

        // Check if the component class exists
        if (! class_exists($componentClass)) {
            throw new UnsupportedFieldTypeException(
                "Component class does not exist: {$componentClass} for field type: {$field->type->value}"
            );
        }

        // Get the component instance from the container
        $componentInstance = app($componentClass);

        // Check if the component implements the expected interface
        $expectedInterface = $this->getExpectedInterface();
        if (! $componentInstance instanceof $expectedInterface) {
            throw new UnsupportedFieldTypeException(
                "Component class {$componentClass} must implement {$expectedInterface}"
            );
        }

        // Create and configure the component
        return $this->configureComponent($componentInstance, $field);
    }

    /**
     * Check if the factory can create a component for the given type
     *
     * @param  mixed  $type
     * @return bool
     */
    public function supports(mixed $type): bool
    {
        if (! is_string($type)) {
            return false;
        }

        return $this->getComponentClass($type) !== null;
    }

    /**
     * Register a custom component class for a field type
     *
     * @param  string  $fieldType
     * @param  class-string  $componentClass
     * @return void
     */
    public function registerComponentClass(string $fieldType, string $componentClass): void
    {
        $this->customComponents[$fieldType] = $componentClass;
    }

    /**
     * Get the expected interface that components must implement
     *
     * @return class-string
     */
    abstract protected function getExpectedInterface(): string;

    /**
     * Configure the component with field-specific settings
     *
     * @param  mixed  $componentInstance
     * @param  CustomField  $field
     * @return mixed
     */
    abstract protected function configureComponent(mixed $componentInstance, CustomField $field): mixed;
}