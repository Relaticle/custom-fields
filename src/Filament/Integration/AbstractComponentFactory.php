<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\FieldTypeRegistryService;
use RuntimeException;

/**
 * Abstract base factory for component creation.
 *
 * Eliminates duplication across 7+ factory classes by providing
 * common pattern for:
 * - Component type resolution via FieldTypeRegistryService
 * - Instance caching for performance
 * - Validation and error handling
 *
 * Each concrete factory only needs to specify:
 * - Component configuration key (form_component, table_column, etc.)
 * - Component interface for validation
 * - Post-creation configuration
 *
 * @template TComponent
 * @template TResult
 */
abstract class AbstractComponentFactory
{
    /**
     * @var array<class-string, object>
     */
    protected array $instanceCache = [];

    public function __construct(
        protected readonly Container $container,
        protected readonly FieldTypeRegistryService $fieldTypeRegistry
    ) {}

    /**
     * Create component instance for given field.
     *
     * @throws BindingResolutionException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function createComponent(CustomField $customField, string $componentKey, string $expectedInterface): object
    {
        $customFieldType = $customField->getFieldTypeValue();

        $fieldTypeConfig = $this->fieldTypeRegistry->getFieldType($customFieldType);

        if ($fieldTypeConfig === null) {
            throw new InvalidArgumentException("No {$componentKey} registered for custom field type: {$customFieldType}");
        }

        $componentClass = $fieldTypeConfig[$componentKey];

        if (! isset($this->instanceCache[$componentClass])) {
            $component = $this->container->make($componentClass);

            if (! $component instanceof $expectedInterface) {
                throw new RuntimeException("Component class {$componentClass} must implement {$expectedInterface}");
            }

            $this->instanceCache[$componentClass] = $component;
        }

        return $this->instanceCache[$componentClass];
    }

    /**
     * Clear the instance cache (useful for testing).
     */
    public function clearCache(): void
    {
        $this->instanceCache = [];
    }

    /**
     * Get cached instance count (useful for debugging).
     */
    public function getCacheSize(): int
    {
        return count($this->instanceCache);
    }
}
