<?php

// ABOUTME: Service provider that registers the entity management system with Laravel
// ABOUTME: Handles singleton registration, configuration loading, and auto-discovery setup

declare(strict_types=1);

namespace Relaticle\CustomFields\Providers;

use Filament\Resources\Resource;
use Illuminate\Support\ServiceProvider;
use Relaticle\CustomFields\Contracts\EntityManagerInterface;
use Relaticle\CustomFields\Data\EntityConfigurationData;
use Relaticle\CustomFields\Entities\EntityManager;

class EntityServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register EntityManager as singleton
        $this->app->singleton(EntityManagerInterface::class, EntityManager::class);
        $this->app->singleton(EntityManager::class, fn ($app): EntityManager => new EntityManager(
            cacheEnabled: config('custom-fields.entity_management.cache_entities', true)
        ));

        // Set up discovery paths when manager is resolved
        $this->app->resolving(EntityManager::class, function (EntityManager $manager): void {
            $this->configureDiscovery($manager);
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        $manager = $this->app->make(EntityManager::class);

        // Register default entities from config
        $this->registerConfiguredEntities($manager);

        // Set up resolving callbacks
        $this->registerResolvingCallbacks($manager);
    }

    /**
     * Configure entity discovery
     */
    private function configureDiscovery(EntityManager $manager): void
    {
        if (config('custom-fields.entity_management.auto_discover_entities', true)) {
            $paths = config('custom-fields.entity_management.entity_discovery_paths', [app_path('Models')]);
            $manager->enableDiscovery($paths);
        }
    }

    /**
     * Register entities from configuration
     */
    private function registerConfiguredEntities(EntityManager $manager): void
    {
        $entities = config('custom-fields.entity_management.entities', []);

        if (! empty($entities)) {
            $manager->register(function () use ($entities) {
                $configurations = [];

                foreach ($entities as $alias => $config) {
                    if (is_array($config)) {
                        if (! isset($config['alias']) && is_string($alias)) {
                            $config['alias'] = $alias;
                        }

                        $configurations[] = EntityConfigurationData::from($config);
                    } elseif (is_string($config) && class_exists($config) && is_subclass_of($config, Resource::class)) {
                        $configurations[] = EntityConfigurationData::fromResource($config);
                    }
                }

                return $configurations;
            });
        }
    }

    /**
     * Register resolving callbacks
     */
    private function registerResolvingCallbacks(EntityManager $manager): void
    {
        // Apply feature filters based on configuration
        $manager->resolving(function (array $entities): array {
            $filtered = [];

            foreach ($entities as $alias => $entity) {
                if ($this->shouldIncludeEntity($entity)) {
                    $filtered[$alias] = $entity;
                }
            }

            return $filtered;
        });
    }

    /**
     * Check if an entity should be included based on configuration
     */
    private function shouldIncludeEntity(EntityConfigurationData $entity): bool
    {
        // Check excluded models
        $excludedModels = config('custom-fields.entity_management.excluded_models', []);

        // Add more filtering logic as needed
        return ! in_array($entity->getModelClass(), $excludedModels, true);
    }
}
