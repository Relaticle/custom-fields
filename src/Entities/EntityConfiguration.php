<?php

// ABOUTME: Concrete implementation of entity configuration that holds all metadata
// ABOUTME: Provides factory methods for creating from Resources and arrays

declare(strict_types=1);

namespace Relaticle\CustomFields\Entities;

use BackedEnum;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Relaticle\CustomFields\Contracts\EntityConfigurationInterface;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;

final readonly class EntityConfiguration implements EntityConfigurationInterface
{
    /**
     * Common entity features
     */
    public const string FEATURE_CUSTOM_FIELDS = 'custom_fields';

    public const string FEATURE_LOOKUP_SOURCE = 'lookup_source';

    public const string FEATURE_IMPORTABLE = 'importable';

    public const string FEATURE_EXPORTABLE = 'exportable';

    public const string FEATURE_VERSIONABLE = 'versionable';

    public const string FEATURE_AUDITABLE = 'auditable';

    public function __construct(
        private string  $modelClass,
        private string  $alias,
        private string  $labelSingular,
        private string  $labelPlural,
        private mixed   $icon,
        private string  $primaryAttribute,
        private array   $searchAttributes,
        private ?string $resourceClass = null,
        private array   $scopes = [],
        private array   $relationships = [],
        private array   $features = [],
        private int     $priority = 0,
        private array   $metadata = [],
    )
    {
        $this->validateConfiguration();
    }

    /**
     * Create from a Filament Resource
     */
    public static function fromResource(string $resourceClass): self
    {
        if (!class_exists($resourceClass) || !is_subclass_of($resourceClass, Resource::class)) {
            throw new InvalidArgumentException("Class {$resourceClass} must be a valid Filament Resource");
        }

        /** @var resource $resource */
        $resource = app($resourceClass);
        $modelClass = $resource::getModel();

        if (!class_exists($modelClass)) {
            throw new InvalidArgumentException("Model class {$modelClass} does not exist");
        }

        /** @var Model $model */
        $model = new $modelClass;

        $features = [self::FEATURE_LOOKUP_SOURCE];

        if (in_array(HasCustomFields::class, class_implements($modelClass), true)) {
            $features[] = self::FEATURE_CUSTOM_FIELDS;
        }

        $globalSearchAttributes = method_exists($resource, 'getGloballySearchableAttributes')
            ? $resource::getGloballySearchableAttributes()
            : [];

        return new self(
            modelClass: $modelClass,
            alias: $model->getMorphClass(),
            labelSingular: $resource::getModelLabel(),
            labelPlural: $resource::getBreadcrumb() ?? $resource::getPluralModelLabel() ?? $resource::getModelLabel() . 's',
            icon: $resource::getNavigationIcon() ?? 'heroicon-o-document',
            primaryAttribute: method_exists($resource, 'getRecordTitleAttribute')
                ? ($resource::getRecordTitleAttribute() ?? $model->getKeyName())
                : $model->getKeyName(),
            searchAttributes: $globalSearchAttributes,
            resourceClass: $resourceClass,
            scopes: [],
            relationships: [],
            features: $features,
            priority: $resource::getNavigationSort() ?? 999,
            metadata: [
                'navigation_group' => method_exists($resource, 'getNavigationGroup')
                    ? $resource::getNavigationGroup()
                    : null,
            ],
        );
    }

    /**
     * Create from array configuration
     */
    public static function fromArray(array $config): self
    {
        return new self(
            modelClass: $config['modelClass'] ?? throw new InvalidArgumentException('modelClass is required'),
            alias: $config['alias'] ?? (new $config['modelClass'])->getMorphClass(),
            labelSingular: $config['labelSingular'] ?? class_basename($config['modelClass']),
            labelPlural: $config['labelPlural'] ?? str(class_basename($config['modelClass']))->plural()->toString(),
            icon: $config['icon'] ?? 'heroicon-o-document',
            primaryAttribute: $config['primaryAttribute'] ?? 'id',
            searchAttributes: $config['searchAttributes'] ?? [],
            resourceClass: $config['resourceClass'] ?? null,
            scopes: $config['scopes'] ?? [],
            relationships: $config['relationships'] ?? [],
            features: $config['features'] ?? [self::FEATURE_CUSTOM_FIELDS],
            priority: $config['priority'] ?? 999,
            metadata: $config['metadata'] ?? [],
        );
    }

    /**
     * Create a builder for fluent configuration
     */
    public static function make(string $modelClass): EntityConfigurationBuilder
    {
        return new EntityConfigurationBuilder($modelClass);
    }

    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getLabelSingular(): string
    {
        return $this->labelSingular;
    }

    public function getLabelPlural(): string
    {
        return $this->labelPlural;
    }

    public function getIcon(): string
    {
        // Handle different icon types
        if (is_string($this->icon)) {
            return $this->icon;
        }

        // Handle Filament Heroicon enums
        if ($this->icon instanceof BackedEnum) {
            return $this->icon->value;
        }

        // For any objects with a name property
        if (is_object($this->icon) && property_exists($this->icon, 'name')) {
            return $this->icon->name;
        }

        // For any objects with a value property
        if (is_object($this->icon) && property_exists($this->icon, 'value')) {
            return $this->icon->value;
        }

        return 'heroicon-o-document';
    }

    public function getPrimaryAttribute(): string
    {
        return $this->primaryAttribute;
    }

    public function getSearchAttributes(): array
    {
        return $this->searchAttributes;
    }

    public function getResourceClass(): ?string
    {
        return $this->resourceClass;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function getRelationships(): array
    {
        return $this->relationships;
    }

    public function getFeatures(): array
    {
        return $this->features;
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features, true);
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get a specific metadata value
     */
    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Create a new model instance
     */
    public function createModelInstance(): Model
    {
        $modelClass = $this->modelClass;

        return new $modelClass;
    }

    /**
     * Get a query builder for this entity
     */
    public function newQuery(): Builder
    {
        $query = $this->createModelInstance()->newQuery();

        foreach ($this->scopes as $scope) {
            if (is_string($scope) && method_exists($this->modelClass, 'scope' . ucfirst($scope))) {
                $query->{$scope}();
            } elseif (is_callable($scope)) {
                $scope($query);
            }
        }

        return $query;
    }

    public function toArray(): array
    {
        return [
            'modelClass' => $this->modelClass,
            'alias' => $this->alias,
            'labelSingular' => $this->labelSingular,
            'labelPlural' => $this->labelPlural,
            'icon' => $this->icon,
            'primaryAttribute' => $this->primaryAttribute,
            'searchAttributes' => $this->searchAttributes,
            'resourceClass' => $this->resourceClass,
            'scopes' => array_map(fn($scope): string => is_string($scope) ? $scope : 'closure', $this->scopes),
            'relationships' => $this->relationships,
            'features' => $this->features,
            'priority' => $this->priority,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Validate the configuration
     */
    private function validateConfiguration(): void
    {
        if (!class_exists($this->modelClass)) {
            throw new InvalidArgumentException("Model class {$this->modelClass} does not exist");
        }

        if (!is_subclass_of($this->modelClass, Model::class)) {
            throw new InvalidArgumentException("Model class {$this->modelClass} must extend " . Model::class);
        }

        if ($this->resourceClass && !class_exists($this->resourceClass)) {
            throw new InvalidArgumentException("Resource class {$this->resourceClass} does not exist");
        }

        if ($this->alias === '' || $this->alias === '0') {
            throw new InvalidArgumentException('Entity alias cannot be empty');
        }
    }
}
