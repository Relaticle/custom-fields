<?php

// ABOUTME: Custom collection class for querying and filtering entity configurations
// ABOUTME: Provides specialized methods for finding entities by various criteria

declare(strict_types=1);

namespace Relaticle\CustomFields\Entities;

use Illuminate\Support\Collection;
use Relaticle\CustomFields\Contracts\EntityConfigurationInterface;

final class EntityCollection extends Collection
{
    /**
     * Find entity by model class or alias
     */
    public function findByClassOrAlias(string $classOrAlias): ?EntityConfigurationInterface
    {
        return $this->first(fn (EntityConfigurationInterface $entity): bool => $entity->getModelClass() === $classOrAlias
            || $entity->getAlias() === $classOrAlias);
    }

    /**
     * Find entity by model class
     */
    public function findByModelClass(string $modelClass): ?EntityConfigurationInterface
    {
        return $this->first(fn (EntityConfigurationInterface $entity): bool => $entity->getModelClass() === $modelClass
        );
    }

    /**
     * Find entity by alias
     */
    public function findByAlias(string $alias): ?EntityConfigurationInterface
    {
        return $this->first(fn (EntityConfigurationInterface $entity): bool => $entity->getAlias() === $alias
        );
    }

    /**
     * Get entities that support custom fields
     */
    public function withCustomFields(): static
    {
        return $this->filter(fn (EntityConfigurationInterface $entity): bool => $entity->hasFeature(EntityConfiguration::FEATURE_CUSTOM_FIELDS)
        );
    }

    /**
     * Get entities that can be used as lookup sources
     */
    public function asLookupSources(): static
    {
        return $this->filter(fn (EntityConfigurationInterface $entity): bool => $entity->hasFeature(EntityConfiguration::FEATURE_LOOKUP_SOURCE)
        );
    }

    /**
     * Get entities with a specific feature
     */
    public function withFeature(string $feature): static
    {
        return $this->filter(fn (EntityConfigurationInterface $entity): bool => $entity->hasFeature($feature)
        );
    }

    /**
     * Get entities without a specific feature
     */
    public function withoutFeature(string $feature): static
    {
        return $this->reject(fn (EntityConfigurationInterface $entity): bool => $entity->hasFeature($feature)
        );
    }

    /**
     * Get entities with any of the specified features
     */
    public function withAnyFeature(array $features): static
    {
        return $this->filter(function (EntityConfigurationInterface $entity) use ($features): bool {
            foreach ($features as $feature) {
                if ($entity->hasFeature($feature)) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Get entities with all of the specified features
     */
    public function withAllFeatures(array $features): static
    {
        return $this->filter(function (EntityConfigurationInterface $entity) use ($features): bool {
            foreach ($features as $feature) {
                if (! $entity->hasFeature($feature)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Get entities that have a Filament Resource
     */
    public function withResource(): static
    {
        return $this->filter(fn (EntityConfigurationInterface $entity): bool => $entity->getResourceClass() !== null
        );
    }

    /**
     * Get entities without a Filament Resource
     */
    public function withoutResource(): static
    {
        return $this->filter(fn (EntityConfigurationInterface $entity): bool => $entity->getResourceClass() === null
        );
    }

    /**
     * Sort by priority (ascending)
     */
    public function sortedByPriority(): static
    {
        return $this->sortBy(fn (EntityConfigurationInterface $entity): int => $entity->getPriority()
        )->values();
    }

    /**
     * Sort by label (alphabetically)
     */
    public function sortedByLabel(): static
    {
        return $this->sortBy(fn (EntityConfigurationInterface $entity): string => $entity->getLabelSingular()
        )->values();
    }

    /**
     * Get as options array for selects (alias => label)
     */
    public function toOptions(bool $usePlural = true): array
    {
        return $this->mapWithKeys(fn (EntityConfigurationInterface $entity) => [
            $entity->getAlias() => $usePlural
                ? $entity->getLabelPlural()
                : $entity->getLabelSingular(),
        ])->toArray();
    }

    /**
     * Get as detailed options array with icons
     */
    public function toDetailedOptions(): array
    {
        return $this->mapWithKeys(fn (EntityConfigurationInterface $entity) => [
            $entity->getAlias() => [
                'label' => $entity->getLabelPlural(),
                'icon' => $entity->getIcon(),
                'modelClass' => $entity->getModelClass(),
            ],
        ])->toArray();
    }

    /**
     * Group by feature
     */
    public function groupByFeature(string $feature): static
    {
        return $this->groupBy(fn (EntityConfigurationInterface $entity): string => $entity->hasFeature($feature) ? 'with_'.$feature : 'without_'.$feature
        );
    }

    /**
     * Filter by metadata value
     */
    public function whereMetadata(string $key, mixed $value): static
    {
        return $this->filter(fn (EntityConfigurationInterface $entity): bool => $entity->getMetadataValue($key) === $value
        );
    }

    /**
     * Get model classes
     */
    public function getModelClasses(): array
    {
        return $this->map(fn (EntityConfigurationInterface $entity): string => $entity->getModelClass()
        )->unique()->values()->toArray();
    }

    /**
     * Get aliases
     */
    public function getAliases(): array
    {
        return $this->map(fn (EntityConfigurationInterface $entity): string => $entity->getAlias()
        )->unique()->values()->toArray();
    }
}
