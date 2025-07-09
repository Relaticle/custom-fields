<?php

// ABOUTME: Contract for the entity manager that handles registration and retrieval
// ABOUTME: Defines the public API for entity management operations

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts;

use Closure;
use Relaticle\CustomFields\Data\EntityConfigurationData;
use Relaticle\CustomFields\Entities\EntityCollection;

interface EntityManagerInterface
{
    /**
     * Register entities
     *
     * @param  array|Closure  $entities  Array of configs or closure returning configs
     */
    public function register(array|Closure $entities): static;

    /**
     * Get all registered entities
     */
    public function getEntities(): EntityCollection;

    /**
     * Get a specific entity by class or alias
     */
    public function getEntity(string $classOrAlias): ?EntityConfigurationData;

    /**
     * Check if an entity exists
     */
    public function hasEntity(string $classOrAlias): bool;

    /**
     * Enable automatic discovery of entities
     */
    public function enableDiscovery(array $paths = []): static;

    /**
     * Disable automatic discovery
     */
    public function disableDiscovery(): static;

    /**
     * Clear the entity cache
     */
    public function clearCache(): static;

    /**
     * Get entities for a specific feature
     */
    public function getEntitiesWithFeature(string $feature): EntityCollection;

    /**
     * Register a callback to be called when entities are resolved
     */
    public function resolving(Closure $callback): static;
}
