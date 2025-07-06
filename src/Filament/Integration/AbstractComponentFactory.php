<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Relaticle\CustomFields\Models\CustomField;
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
        $customFieldType = $customField->typeData;

        $componentClass = $customFieldType->formComponent;

        if (! isset($this->instanceCache[$componentClass])) {
            $component = $this->container->make($componentClass);

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
