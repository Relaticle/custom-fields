<?php

// ABOUTME: Defines the contract for entity configuration in the custom fields system
// ABOUTME: Each entity must provide metadata and settings through this interface

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts;

interface EntityConfigurationInterface
{
    /**
     * Get the model class this entity represents
     */
    public function getModelClass(): string;

    /**
     * Get the unique alias for this entity (typically the morph class)
     */
    public function getAlias(): string;

    /**
     * Get the singular label for this entity
     */
    public function getLabelSingular(): string;

    /**
     * Get the plural label for this entity
     */
    public function getLabelPlural(): string;

    /**
     * Get the icon for this entity
     */
    public function getIcon(): string;

    /**
     * Get the primary display attribute (e.g., 'name', 'title')
     */
    public function getPrimaryAttribute(): string;

    /**
     * Get attributes that can be searched
     */
    public function getSearchAttributes(): array;

    /**
     * Get the associated Filament Resource class, if any
     */
    public function getResourceClass(): ?string;

    /**
     * Get query scopes to apply when fetching this entity
     */
    public function getScopes(): array;

    /**
     * Get relationship definitions for this entity
     */
    public function getRelationships(): array;

    /**
     * Get enabled features for this entity
     */
    public function getFeatures(): array;

    /**
     * Check if entity has a specific feature
     */
    public function hasFeature(string $feature): bool;

    /**
     * Get the priority for sorting
     */
    public function getPriority(): int;

    /**
     * Get additional metadata
     */
    public function getMetadata(): array;

    /**
     * Convert to array representation
     */
    public function toArray(): array;
}
