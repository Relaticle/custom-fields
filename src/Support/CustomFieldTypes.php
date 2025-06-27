<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Support;

use Relaticle\CustomFields\Contracts\FieldTypeDefinitionInterface;
use Relaticle\CustomFields\Services\FieldTypeRegistryService;

/**
 * Helper class for registering custom field types.
 */
class CustomFieldTypes
{
    /**
     * Register a custom field type.
     */
    public static function register(FieldTypeDefinitionInterface $fieldType): void
    {
        if (app()->bound(FieldTypeRegistryService::class)) {
            app(FieldTypeRegistryService::class)->register($fieldType);
        }
    }

    /**
     * Register multiple custom field types.
     *
     * @param  array<FieldTypeDefinitionInterface>  $fieldTypes
     */
    public static function registerMany(array $fieldTypes): void
    {
        foreach ($fieldTypes as $fieldType) {
            self::register($fieldType);
        }
    }

    /**
     * Register a custom field type from a class name.
     *
     * @param  class-string<FieldTypeDefinitionInterface>  $className
     */
    public static function registerClass(string $className): void
    {
        if (! class_exists($className)) {
            throw new \InvalidArgumentException("Class {$className} does not exist.");
        }

        $fieldType = new $className;

        if (! $fieldType instanceof FieldTypeDefinitionInterface) {
            throw new \InvalidArgumentException("Class {$className} must implement FieldTypeDefinitionInterface.");
        }

        self::register($fieldType);
    }

    /**
     * Register multiple custom field types from class names.
     *
     * @param  array<class-string<FieldTypeDefinitionInterface>>  $classNames
     */
    public static function registerClasses(array $classNames): void
    {
        foreach ($classNames as $className) {
            self::registerClass($className);
        }
    }

    /**
     * Clear the field type cache to force re-discovery.
     */
    public static function clearCache(): void
    {
        if (app()->bound(FieldTypeRegistryService::class)) {
            \Illuminate\Support\Facades\Cache::forget('custom-fields.discovered-field-types');
            \Illuminate\Support\Facades\Cache::forget('custom-fields.field-types.options-for-select');
        }
    }

    /**
     * Get all registered field types.
     */
    public static function getAllFieldTypes(): \Illuminate\Support\Collection
    {
        if (app()->bound(FieldTypeRegistryService::class)) {
            return app(FieldTypeRegistryService::class)->getAllFieldTypes();
        }

        return collect();
    }

    /**
     * Check if a field type is registered.
     */
    public static function hasFieldType(string $key): bool
    {
        if (app()->bound(FieldTypeRegistryService::class)) {
            return app(FieldTypeRegistryService::class)->hasFieldType($key);
        }

        return false;
    }
}
