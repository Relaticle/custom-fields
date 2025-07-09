<?php

// ABOUTME: Central registry for managing all entities in the custom fields system
// ABOUTME: Handles registration, discovery, caching, and retrieval of entity configurations

declare(strict_types=1);

namespace Relaticle\CustomFields\Entities;

use Closure;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Traits\Macroable;
use Relaticle\CustomFields\Contracts\EntityConfigurationInterface;
use Relaticle\CustomFields\Contracts\EntityManagerInterface;

final class EntityManager implements EntityManagerInterface
{
    use Macroable;

    private const string CACHE_KEY = 'custom_fields_entities';

    private const int CACHE_TTL = 3600; // 1 hour

    private array $entities = [];

    private ?array $cachedEntities = null;

    private array $cachedInstances = [];

    private ?EntityDiscovery $discovery = null;

    private bool $discoveryEnabled = false;

    private array $resolvingCallbacks = [];

    private bool $useCache = true;

    public function __construct(
        private readonly bool $cacheEnabled = true
    ) {
        $this->useCache = $cacheEnabled && config('custom-fields.entity_management.cache_entities', true);
    }

    /**
     * Register entities
     */
    public function register(array|Closure|string $entities): static
    {
        if ($entities === 'discover') {
            $this->discoveryEnabled = true;
        } else {
            $this->entities[] = $entities;
        }

        $this->clearCache();

        return $this;
    }

    /**
     * Get all registered entities
     */
    public function getEntities(): EntityCollection
    {
        if ($this->cachedEntities === null) {
            $this->cachedEntities = $this->useCache
                ? Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn (): array => $this->buildEntityCache())
                : $this->buildEntityCache();
        }

        return new EntityCollection($this->cachedEntities);
    }

    /**
     * Get a specific entity by class or alias
     */
    public function getEntity(string $classOrAlias): ?EntityConfigurationInterface
    {
        return $this->getEntities()->findByClassOrAlias($classOrAlias);
    }

    /**
     * Check if an entity exists
     */
    public function hasEntity(string $classOrAlias): bool
    {
        return $this->getEntity($classOrAlias) instanceof EntityConfigurationInterface;
    }

    /**
     * Enable automatic discovery of entities
     */
    public function enableDiscovery(array $paths = []): static
    {
        $this->discoveryEnabled = true;
        $this->discovery = new EntityDiscovery($paths);
        $this->clearCache();

        return $this;
    }

    /**
     * Disable automatic discovery
     */
    public function disableDiscovery(): static
    {
        $this->discoveryEnabled = false;
        $this->discovery = null;
        $this->clearCache();

        return $this;
    }

    /**
     * Clear the entity cache
     */
    public function clearCache(): static
    {
        $this->cachedEntities = null;
        $this->cachedInstances = [];

        if ($this->useCache) {
            Cache::forget(self::CACHE_KEY);
        }

        return $this;
    }

    /**
     * Get entities for a specific feature
     */
    public function getEntitiesWithFeature(string $feature): EntityCollection
    {
        return $this->getEntities()->withFeature($feature);
    }

    /**
     * Register a callback to be called when entities are resolved
     */
    public function resolving(Closure $callback): static
    {
        $this->resolvingCallbacks[] = $callback;

        return $this;
    }

    /**
     * Disable caching temporarily
     */
    public function withoutCache(Closure $callback): mixed
    {
        $originalCacheState = $this->useCache;
        $this->useCache = false;

        try {
            return $callback($this);
        } finally {
            $this->useCache = $originalCacheState;
        }
    }

    /**
     * Build the entity cache
     */
    private function buildEntityCache(): array
    {
        $entities = [];

        // Add manually registered entities
        foreach ($this->entities as $entityGroup) {
            $resolvedEntities = $this->resolveEntities($entityGroup);
            foreach ($resolvedEntities as $entity) {
                $entities[$entity->getAlias()] = $entity;
            }
        }

        // Add discovered entities if enabled
        if ($this->discoveryEnabled && $this->discovery instanceof EntityDiscovery) {
            $discoveredEntities = $this->discovery->discover();
            foreach ($discoveredEntities as $entity) {
                // Manual registrations take precedence
                if (! isset($entities[$entity->getAlias()])) {
                    $entities[$entity->getAlias()] = $entity;
                }
            }
        }

        // Call resolving callbacks
        foreach ($this->resolvingCallbacks as $callback) {
            $entities = $callback($entities) ?? $entities;
        }

        return $entities;
    }

    /**
     * Resolve entities from various input types
     */
    private function resolveEntities(array|Closure $entities): array
    {
        if ($entities instanceof Closure) {
            $entities = $entities();
        }

        $resolved = [];

        foreach ($entities as $value) {
            if ($value instanceof EntityConfigurationInterface) {
                $resolved[] = $value;
            } elseif (is_array($value)) {
                // Array configuration
                if (isset($value['modelClass'])) {
                    // Single entity configuration
                    $resolved[] = EntityConfiguration::fromArray($value);
                } else {
                    // Nested array of entities
                    $resolved = array_merge($resolved, $this->resolveEntities($value));
                }
            } elseif (is_string($value) && class_exists($value)) {
                // Resource class
                if (is_subclass_of($value, Resource::class)) {
                    $resolved[] = EntityConfiguration::fromResource($value);
                }
            }
        }

        return $resolved;
    }

    /**
     * Get or create an entity instance
     */
    private function getOrCreateInstance(string $class, Closure $factory): EntityConfigurationInterface
    {
        if (! isset($this->cachedInstances[$class])) {
            $this->cachedInstances[$class] = $factory();
        }

        return $this->cachedInstances[$class];
    }
}
