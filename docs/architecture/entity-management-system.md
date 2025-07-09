# Entity Management System Architecture

## Overview

The Entity Management System provides a unified way to register, discover, and configure entities (models) that can have custom fields attached to them. This system serves as the single source of truth for all entity-related metadata and configuration across the custom fields package.

## Core Components

### 1. Entity Registry Manager

The `EntityManager` serves as the central registry for all entities in the system.

```php
namespace Relaticle\CustomFields\Entities;

final class EntityManager
{
    private array $entities = [];
    private array $cachedEntities;
    private array $cachedInstances = [];
    private ?EntityDiscovery $discovery = null;
    
    /**
     * Register entities via array, closure, or auto-discovery
     */
    public function register(array|Closure|string $entities): static
    {
        $this->entities[] = $entities;
        return $this;
    }
    
    /**
     * Get all registered entities
     */
    public function getEntities(): EntityCollection
    {
        if (!isset($this->cachedEntities)) {
            $this->buildEntityCache();
        }
        
        return new EntityCollection($this->cachedEntities);
    }
    
    /**
     * Get entity configuration by class or alias
     */
    public function getEntity(string $classOrAlias): ?EntityConfiguration
    {
        $entities = $this->getEntities();
        return $entities->findByClassOrAlias($classOrAlias);
    }
    
    /**
     * Enable auto-discovery of entities
     */
    public function enableDiscovery(array $paths = []): static
    {
        $this->discovery = new EntityDiscovery($paths);
        return $this;
    }
}
```

### 2. Entity Configuration

Each entity is represented by an `EntityConfiguration` object that holds all metadata.

```php
namespace Relaticle\CustomFields\Entities;

final class EntityConfiguration
{
    public function __construct(
        public readonly string $modelClass,
        public readonly string $alias,
        public readonly string $labelSingular,
        public readonly string $labelPlural,
        public readonly string $icon,
        public readonly string $primaryAttribute,
        public readonly array $searchAttributes,
        public readonly ?string $resourceClass = null,
        public readonly array $scopes = [],
        public readonly array $relationships = [],
        public readonly array $features = [],
        public readonly int $priority = 0,
    ) {}
    
    /**
     * Create from a Filament Resource
     */
    public static function fromResource(string $resourceClass): self
    {
        $resource = app($resourceClass);
        $model = $resource->getModel();
        
        return new self(
            modelClass: $model,
            alias: (new $model)->getMorphClass(),
            labelSingular: $resource::getModelLabel(),
            labelPlural: $resource::getPluralModelLabel(),
            icon: $resource::getNavigationIcon() ?? 'heroicon-o-document',
            primaryAttribute: $resource::getRecordTitleAttribute() ?? 'name',
            searchAttributes: $resource::getGloballySearchableAttributes() ?? [],
            resourceClass: $resourceClass,
            scopes: [],
            relationships: [],
            features: ['custom_fields'],
            priority: $resource::getNavigationSort() ?? 0,
        );
    }
    
    /**
     * Create from array configuration
     */
    public static function fromArray(array $config): self
    {
        return new self(...$config);
    }
}
```

### 3. Entity Collection

Custom collection class for querying and filtering entities.

```php
namespace Relaticle\CustomFields\Entities;

use Illuminate\Support\Collection;

final class EntityCollection extends Collection
{
    /**
     * Find entity by model class or alias
     */
    public function findByClassOrAlias(string $classOrAlias): ?EntityConfiguration
    {
        return $this->first(function (EntityConfiguration $entity) use ($classOrAlias) {
            return $entity->modelClass === $classOrAlias 
                || $entity->alias === $classOrAlias;
        });
    }
    
    /**
     * Get entities that support custom fields
     */
    public function withCustomFields(): static
    {
        return $this->filter(fn (EntityConfiguration $entity) => 
            in_array('custom_fields', $entity->features, true)
        );
    }
    
    /**
     * Get entities that can be used as lookups
     */
    public function asLookupSources(): static
    {
        return $this->filter(fn (EntityConfiguration $entity) => 
            in_array('lookup_source', $entity->features, true)
        );
    }
    
    /**
     * Get entities with a specific feature
     */
    public function withFeature(string $feature): static
    {
        return $this->filter(fn (EntityConfiguration $entity) => 
            in_array($feature, $entity->features, true)
        );
    }
    
    /**
     * Sort by priority
     */
    public function sortedByPriority(): static
    {
        return $this->sortBy('priority');
    }
}
```

### 4. Entity Discovery

Automatic discovery of entities from various sources.

```php
namespace Relaticle\CustomFields\Entities;

final class EntityDiscovery
{
    public function __construct(
        private array $paths = [],
        private array $namespaces = [],
    ) {}
    
    /**
     * Discover entities from multiple sources
     */
    public function discover(): array
    {
        $entities = [];
        
        // Discover from Filament Resources
        $entities = array_merge($entities, $this->discoverFromFilamentResources());
        
        // Discover from models implementing HasCustomFields
        $entities = array_merge($entities, $this->discoverFromModels());
        
        // Discover from configured paths
        $entities = array_merge($entities, $this->discoverFromPaths());
        
        return $entities;
    }
    
    /**
     * Discover entities from Filament Resources
     */
    private function discoverFromFilamentResources(): array
    {
        $entities = [];
        
        foreach (Filament::getResources() as $resourceClass) {
            $resource = app($resourceClass);
            $model = $resource->getModel();
            
            if ($this->modelSupportsCustomFields($model)) {
                $entities[] = EntityConfiguration::fromResource($resourceClass);
            }
        }
        
        return $entities;
    }
    
    /**
     * Check if model supports custom fields
     */
    private function modelSupportsCustomFields(string $modelClass): bool
    {
        return class_exists($modelClass) 
            && in_array(HasCustomFields::class, class_implements($modelClass), true);
    }
}
```

### 5. Entity Service Provider

Registers the entity management system with Laravel.

```php
namespace Relaticle\CustomFields\Providers;

use Illuminate\Support\ServiceProvider;
use Relaticle\CustomFields\Entities\EntityManager;

class EntityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register EntityManager as singleton
        $this->app->singleton(EntityManager::class);
        
        // Register entity discovery paths from config
        $this->app->resolving(EntityManager::class, function (EntityManager $manager) {
            $discoveryPaths = config('custom-fields.entity_discovery_paths', []);
            
            if (!empty($discoveryPaths)) {
                $manager->enableDiscovery($discoveryPaths);
            }
        });
    }
    
    public function boot(): void
    {
        $manager = $this->app->make(EntityManager::class);
        
        // Register default entities from config
        $defaultEntities = config('custom-fields.entities', []);
        if (!empty($defaultEntities)) {
            $manager->register($defaultEntities);
        }
        
        // Auto-discover entities if enabled
        if (config('custom-fields.auto_discover_entities', true)) {
            $manager->register(fn () => $manager->discovery?->discover() ?? []);
        }
    }
}
```

### 6. Entity Facade

Provides clean API access to entity management.

```php
namespace Relaticle\CustomFields\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Relaticle\CustomFields\Entities\EntityCollection getEntities()
 * @method static \Relaticle\CustomFields\Entities\EntityConfiguration|null getEntity(string $classOrAlias)
 * @method static \Relaticle\CustomFields\Entities\EntityManager register(array|Closure|string $entities)
 * @method static \Relaticle\CustomFields\Entities\EntityManager enableDiscovery(array $paths = [])
 */
class Entities extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EntityManager::class;
    }
    
    /**
     * Register entities with deferred execution
     */
    public static function register(array|Closure|string $entities): void
    {
        static::resolved(function (EntityManager $manager) use ($entities): void {
            $manager->register($entities);
        });
    }
}
```

## Integration Points

### 1. Replace EntityTypeService

The current `EntityTypeService` will be replaced with calls to the Entity Manager:

```php
// Before
$entityTypes = EntityTypeService::getOptions();

// After
$entityTypes = Entities::getEntities()
    ->withCustomFields()
    ->mapWithKeys(fn ($entity) => [$entity->alias => $entity->labelPlural]);
```

### 2. Replace LookupTypeService

Lookup sources will come from the Entity Manager:

```php
// Before
$lookupTypes = LookupTypeService::getOptions();

// After
$lookupTypes = Entities::getEntities()
    ->asLookupSources()
    ->mapWithKeys(fn ($entity) => [$entity->alias => $entity->labelPlural]);
```

### 3. Update FilamentResourceService

The service will use the Entity Manager for resource resolution:

```php
public static function getResourceInstance(string $model): ?Resource
{
    $entity = Entities::getEntity($model);
    
    if ($entity?->resourceClass) {
        return app($entity->resourceClass);
    }
    
    return null;
}
```

### 4. Update Management UI

The CustomFieldsManagementPage will use the Entity Manager:

```php
#[Computed]
public function entityTypes(): Collection
{
    return Entities::getEntities()
        ->withCustomFields()
        ->sortedByPriority()
        ->mapWithKeys(fn ($entity) => [$entity->alias => $entity->labelPlural]);
}
```

## Configuration

### config/custom-fields.php

```php
return [
    // Entity Management
    'auto_discover_entities' => true,
    
    'entity_discovery_paths' => [
        app_path('Models'),
    ],
    
    'entities' => [
        // Manual entity registration
        // 'posts' => [
        //     'modelClass' => \App\Models\Post::class,
        //     'labelSingular' => 'Post',
        //     'labelPlural' => 'Posts',
        //     'icon' => 'heroicon-o-document-text',
        //     'primaryAttribute' => 'title',
        //     'searchAttributes' => ['title', 'content'],
        //     'features' => ['custom_fields', 'lookup_source'],
        // ],
    ],
    
    // Legacy settings (for backward compatibility)
    'allowed_entity_resources' => [],
    'disallowed_entity_resources' => [],
];
```

## Usage Examples

### Registering Entities

```php
// In a service provider
use Relaticle\CustomFields\Facades\Entities;

// Register from array
Entities::register([
    'products' => [
        'modelClass' => Product::class,
        'labelSingular' => 'Product',
        'labelPlural' => 'Products',
        'icon' => 'heroicon-o-shopping-bag',
        'primaryAttribute' => 'name',
        'searchAttributes' => ['name', 'sku', 'description'],
        'features' => ['custom_fields', 'lookup_source'],
    ],
]);

// Register with closure for deferred loading
Entities::register(function () {
    return collect(config('shop.entities'))
        ->map(fn ($config) => EntityConfiguration::fromArray($config))
        ->toArray();
});

// Register single entity
Entities::register([
    EntityConfiguration::fromArray([
        'modelClass' => Customer::class,
        'alias' => 'customers',
        'labelSingular' => 'Customer',
        'labelPlural' => 'Customers',
        'icon' => 'heroicon-o-user',
        'primaryAttribute' => 'name',
        'searchAttributes' => ['name', 'email'],
    ]),
]);
```

### Querying Entities

```php
use Relaticle\CustomFields\Facades\Entities;

// Get all entities
$entities = Entities::getEntities();

// Get entities with custom fields
$customFieldEntities = Entities::getEntities()->withCustomFields();

// Get specific entity
$postEntity = Entities::getEntity('posts');
$postEntity = Entities::getEntity(Post::class);

// Get entities for lookups
$lookupSources = Entities::getEntities()->asLookupSources();

// Check if entity exists
if ($entity = Entities::getEntity($modelClass)) {
    $icon = $entity->icon;
    $label = $entity->labelPlural;
}
```

### Custom Entity Features

```php
// Register entity with custom features
Entities::register([
    'documents' => [
        'modelClass' => Document::class,
        // ... other config ...
        'features' => [
            'custom_fields',
            'lookup_source',
            'importable',
            'exportable',
            'versionable',
        ],
    ],
]);

// Query by feature
$importableEntities = Entities::getEntities()->withFeature('importable');
```

## Migration Strategy

1. **Phase 1**: Implement EntityManager alongside existing services
2. **Phase 2**: Update integration points to use EntityManager
3. **Phase 3**: Deprecate old services (EntityTypeService, LookupTypeService)
4. **Phase 4**: Remove deprecated services in next major version

## Benefits

1. **Unified System**: Single source of truth for all entity configuration
2. **Extensibility**: Easy to add new entity features and metadata
3. **Performance**: Efficient caching and lazy loading
4. **Developer Experience**: Clean API with intuitive methods
5. **Backward Compatibility**: Can coexist with current implementation
6. **Type Safety**: Strong typing with configuration objects
7. **Discoverability**: Automatic discovery reduces configuration burden