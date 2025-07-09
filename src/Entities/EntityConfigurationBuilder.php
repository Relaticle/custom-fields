<?php

// ABOUTME: Fluent builder for creating entity configurations with method chaining
// ABOUTME: Provides an intuitive API for configuring entities programmatically

declare(strict_types=1);

namespace Relaticle\CustomFields\Entities;

use Closure;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class EntityConfigurationBuilder
{
    private string $alias;

    private string $labelSingular;

    private string $labelPlural;

    private string $icon = 'heroicon-o-document';

    private string $primaryAttribute = 'id';

    private array $searchAttributes = [];

    private ?string $resourceClass = null;

    private array $scopes = [];

    private array $relationships = [];

    private array $features = [EntityConfiguration::FEATURE_CUSTOM_FIELDS];

    private int $priority = 999;

    private array $metadata = [];

    public function __construct(
        private readonly string $modelClass
    ) {
        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException("Invalid model class: {$modelClass}");
        }

        // Set defaults based on model
        $model = new $modelClass;
        $this->alias = $model->getMorphClass();
        $this->labelSingular = class_basename($modelClass);
        $this->labelPlural = str($this->labelSingular)->plural()->toString();
    }

    public function alias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function label(string $singular, ?string $plural = null): self
    {
        $this->labelSingular = $singular;
        $this->labelPlural = $plural ?? str($singular)->plural()->toString();

        return $this;
    }

    public function labelSingular(string $label): self
    {
        $this->labelSingular = $label;

        return $this;
    }

    public function labelPlural(string $label): self
    {
        $this->labelPlural = $label;

        return $this;
    }

    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function primaryAttribute(string $attribute): self
    {
        $this->primaryAttribute = $attribute;

        return $this;
    }

    public function searchable(array $attributes): self
    {
        $this->searchAttributes = $attributes;

        return $this;
    }

    public function addSearchAttribute(string $attribute): self
    {
        $this->searchAttributes[] = $attribute;

        return $this;
    }

    public function resource(string $resourceClass): self
    {
        if (! class_exists($resourceClass)) {
            throw new InvalidArgumentException("Resource class does not exist: {$resourceClass}");
        }

        $this->resourceClass = $resourceClass;

        return $this;
    }

    public function scope(string|Closure $scope): self
    {
        $this->scopes[] = $scope;

        return $this;
    }

    public function scopes(array $scopes): self
    {
        $this->scopes = array_merge($this->scopes, $scopes);

        return $this;
    }

    public function relationship(string $name, array $config): self
    {
        $this->relationships[$name] = $config;

        return $this;
    }

    public function relationships(array $relationships): self
    {
        $this->relationships = array_merge($this->relationships, $relationships);

        return $this;
    }

    public function feature(string $feature): self
    {
        if (! in_array($feature, $this->features, true)) {
            $this->features[] = $feature;
        }

        return $this;
    }

    public function features(array $features): self
    {
        $this->features = array_unique(array_merge($this->features, $features));

        return $this;
    }

    public function withoutFeature(string $feature): self
    {
        $this->features = array_values(array_diff($this->features, [$feature]));

        return $this;
    }

    public function priority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function metadata(array $metadata): self
    {
        $this->metadata = array_merge($this->metadata, $metadata);

        return $this;
    }

    public function meta(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    /**
     * Enable this entity as a lookup source
     */
    public function asLookupSource(): self
    {
        return $this->feature(EntityConfiguration::FEATURE_LOOKUP_SOURCE);
    }

    /**
     * Enable import/export features
     */
    public function importable(): self
    {
        return $this->feature(EntityConfiguration::FEATURE_IMPORTABLE);
    }

    public function exportable(): self
    {
        return $this->feature(EntityConfiguration::FEATURE_EXPORTABLE);
    }

    /**
     * Enable versioning
     */
    public function versionable(): self
    {
        return $this->feature(EntityConfiguration::FEATURE_VERSIONABLE);
    }

    /**
     * Enable auditing
     */
    public function auditable(): self
    {
        return $this->feature(EntityConfiguration::FEATURE_AUDITABLE);
    }

    /**
     * Apply tenant scope automatically
     */
    public function withTenantScope(): self
    {
        return $this->scope('forCurrentTenant');
    }

    /**
     * Build the configuration
     */
    public function build(): EntityConfiguration
    {
        return new EntityConfiguration(
            modelClass: $this->modelClass,
            alias: $this->alias,
            labelSingular: $this->labelSingular,
            labelPlural: $this->labelPlural,
            icon: $this->icon,
            primaryAttribute: $this->primaryAttribute,
            searchAttributes: $this->searchAttributes,
            resourceClass: $this->resourceClass,
            scopes: $this->scopes,
            relationships: $this->relationships,
            features: $this->features,
            priority: $this->priority,
            metadata: $this->metadata,
        );
    }

    /**
     * Build and return as array
     */
    public function toArray(): array
    {
        return $this->build()->toArray();
    }
}
