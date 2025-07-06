<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Factories;

use RuntimeException;

/**
 * ABOUTME: Exception thrown when attempting to create a component for an unsupported field type
 * ABOUTME: Provides detailed error messages to help with debugging and development
 */
class UnsupportedFieldTypeException extends RuntimeException
{
    /**
     * Create exception for missing component registration
     *
     * @param  string  $fieldType
     * @param  string  $componentType
     * @return self
     */
    public static function missingRegistration(string $fieldType, string $componentType): self
    {
        return new self(
            "No {$componentType} component registered for field type '{$fieldType}'. " .
            "Please register a component for this field type or check if the field type is supported."
        );
    }

    /**
     * Create exception for invalid component class
     *
     * @param  string  $fieldType
     * @param  string  $componentClass
     * @param  string  $expectedInterface
     * @return self
     */
    public static function invalidComponentClass(string $fieldType, string $componentClass, string $expectedInterface): self
    {
        return new self(
            "The component class '{$componentClass}' registered for field type '{$fieldType}' " .
            "must implement {$expectedInterface}."
        );
    }

    /**
     * Create exception for non-existent component class
     *
     * @param  string  $fieldType
     * @param  string  $componentClass
     * @return self
     */
    public static function classNotFound(string $fieldType, string $componentClass): self
    {
        return new self(
            "The component class '{$componentClass}' registered for field type '{$fieldType}' does not exist. " .
            "Please check the class name and namespace."
        );
    }
}