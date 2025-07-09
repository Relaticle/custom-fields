# Entity Management System Integration Plan

## Overview

This document outlines how the new Entity Management System will integrate with existing features in the custom fields package. The integration will be done in phases to ensure backward compatibility while providing a smooth migration path.

## Integration Points

### 1. EntityTypeService → Entities Facade

**Current Implementation:**
```php
// EntityTypeService::getOptions()
$entityTypes = EntityTypeService::getOptions();
```

**New Implementation:**
```php
// Using Entities facade
$entityTypes = Entities::getOptions();
```

**Migration Strategy:**
1. Update `EntityTypeService` to use `Entities` facade internally
2. Mark old methods as deprecated
3. Update all internal usages
4. Remove in next major version

**Updated EntityTypeService (Phase 1):**
```php
class EntityTypeService extends AbstractOptionsService
{
    /**
     * @deprecated Use Entities::getOptions() instead
     */
    public static function getOptions(): Collection
    {
        return collect(Entities::getOptions(onlyCustomFields: true));
    }
    
    /**
     * @deprecated Use Entities::getEntity() instead
     */
    public static function getEntityFromModel(string $model): ?string
    {
        $entity = Entities::getEntity($model);
        return $entity?->getAlias();
    }
}
```

### 2. LookupTypeService → Entities Facade

**Current Implementation:**
```php
// LookupTypeService::getOptions()
$lookupTypes = LookupTypeService::getOptions();
```

**New Implementation:**
```php
// Using Entities facade
$lookupTypes = Entities::getLookupOptions();
```

**Updated LookupTypeService (Phase 1):**
```php
class LookupTypeService extends AbstractOptionsService
{
    /**
     * @deprecated Use Entities::getLookupOptions() instead
     */
    public static function getOptions(): Collection
    {
        return collect(Entities::getLookupOptions());
    }
}
```

### 3. FilamentResourceService Updates

**Update Methods to Use Entity Manager:**

```php
class FilamentResourceService
{
    public static function getResourceInstance(string $model): ?Resource
    {
        $entity = Entities::getEntity($model);
        
        if ($entity?->getResourceClass()) {
            return app($entity->getResourceClass());
        }
        
        return null;
    }
    
    public static function getRecordTitleAttribute(string $model): string
    {
        $entity = Entities::getEntity($model);
        return $entity?->getPrimaryAttribute() ?? 'id';
    }
    
    public static function getGlobalSearchableAttributes(string $model): array
    {
        $entity = Entities::getEntity($model);
        return $entity?->getSearchAttributes() ?? [];
    }
    
    public static function getModelInstance(string $model): Model
    {
        $entity = Entities::getEntity($model);
        
        if (!$entity) {
            throw new \InvalidArgumentException("Entity not found: {$model}");
        }
        
        return $entity->createModelInstance();
    }
    
    public static function getModelInstanceQuery(string $model): Builder
    {
        $entity = Entities::getEntity($model);
        
        if (!$entity) {
            throw new \InvalidArgumentException("Entity not found: {$model}");
        }
        
        return $entity->newQuery();
    }
}
```

### 4. CustomFieldsManagementPage Updates

**Update to Use Entity Manager:**

```php
class CustomFieldsManagementPage extends Page
{
    #[Computed]
    public function entityTypes(): Collection
    {
        return collect(Entities::getOptions(onlyCustomFields: true));
    }
    
    #[Computed]
    public function currentEntityLabel(): string
    {
        if (!$this->currentEntityType) {
            return '';
        }
        
        $entity = Entities::getEntity($this->currentEntityType);
        return $entity?->getLabelPlural() ?? $this->currentEntityType;
    }
    
    #[Computed]
    public function currentEntityIcon(): string
    {
        if (!$this->currentEntityType) {
            return 'heroicon-o-document';
        }
        
        $entity = Entities::getEntity($this->currentEntityType);
        return $entity?->getIcon() ?? 'heroicon-o-document';
    }
}
```

### 5. Lookup Field Configuration

**Update ConfiguresLookups Trait:**

```php
trait ConfiguresLookups
{
    protected function getLookupOptions(string $lookupType, int $limit = 50): array
    {
        $entity = Entities::getEntity($lookupType);
        
        if (!$entity) {
            return [];
        }
        
        $query = $entity->newQuery();
        $primaryAttribute = $entity->getPrimaryAttribute();
        
        return $query->limit($limit)
            ->pluck($primaryAttribute, 'id')
            ->toArray();
    }
    
    protected function configureAdvancedLookup(Select $select, string $lookupType): Select
    {
        $entity = Entities::getEntity($lookupType);
        
        if (!$entity) {
            return $select;
        }
        
        return $select
            ->searchable()
            ->getSearchResultsUsing(function (string $search) use ($entity) {
                $query = $entity->newQuery();
                $searchAttributes = $entity->getSearchAttributes();
                
                if (empty($searchAttributes)) {
                    $searchAttributes = [$entity->getPrimaryAttribute()];
                }
                
                foreach ($searchAttributes as $attribute) {
                    $query->orWhere($attribute, 'like', "%{$search}%");
                }
                
                return $query->limit(50)
                    ->pluck($entity->getPrimaryAttribute(), 'id');
            })
            ->getOptionLabelUsing(function ($value) use ($entity): ?string {
                $model = $entity->newQuery()->find($value);
                return $model?->{$entity->getPrimaryAttribute()};
            });
    }
}
```

### 6. Import/Export Integration

**Update Import Configuration:**

```php
class CustomFieldsImporter
{
    public function getAvailableEntities(): array
    {
        return Entities::getEntities()
            ->withFeature(EntityConfiguration::FEATURE_IMPORTABLE)
            ->toOptions();
    }
    
    public function createImportForEntity(string $entityAlias): Import
    {
        $entity = Entities::getEntity($entityAlias);
        
        if (!$entity) {
            throw new \InvalidArgumentException("Entity not found: {$entityAlias}");
        }
        
        // Create import configuration based on entity
        return new Import($entity);
    }
}
```

### 7. Validation Integration

**Update Validation Service:**

```php
class ValidationService
{
    public function getEntitySpecificRules(string $entityAlias): array
    {
        $entity = Entities::getEntity($entityAlias);
        
        if (!$entity) {
            return [];
        }
        
        // Get validation rules from entity metadata
        return $entity->getMetadataValue('validation_rules', []);
    }
}
```

## Configuration Updates

### Updated config/custom-fields.php

```php
return [
    // ... existing config ...
    
    /*
    |--------------------------------------------------------------------------
    | Entity Management
    |--------------------------------------------------------------------------
    */
    
    'auto_discover_entities' => env('CUSTOM_FIELDS_AUTO_DISCOVER_ENTITIES', true),
    
    'entity_discovery_paths' => [
        app_path('Models'),
    ],
    
    'entity_discovery_namespaces' => [
        'App\\Models',
    ],
    
    'cache_entities' => env('CUSTOM_FIELDS_CACHE_ENTITIES', true),
    
    'excluded_models' => [
        // Models to exclude from discovery
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
    
    /*
    |--------------------------------------------------------------------------
    | Legacy Configuration (Deprecated)
    |--------------------------------------------------------------------------
    */
    
    'allowed_entity_resources' => [],
    'disallowed_entity_resources' => [],
    'allowed_lookup_resources' => [],
    'disallowed_lookup_resources' => [],
];
```

## Service Provider Updates

### CustomFieldsServiceProvider

```php
class CustomFieldsServiceProvider extends PackageServiceProvider
{
    public function bootingPackage(): void
    {
        // Register entity service provider
        $this->app->register(EntityServiceProvider::class);
        
        // ... existing registrations ...
    }
}
```

## Migration Timeline

### Phase 1: Parallel Implementation (v2.x)
- Implement Entity Management System
- Update internal services to use new system
- Keep old services functional with deprecation notices
- Add configuration migration helper

### Phase 2: Documentation & Migration Tools (v2.x)
- Update all documentation
- Provide migration guide
- Create artisan command for config migration
- Add compatibility layer for custom implementations

### Phase 3: Deprecation (v2.x)
- Mark old services as deprecated
- Show deprecation warnings in development
- Update all examples to use new system

### Phase 4: Removal (v3.0)
- Remove deprecated services
- Remove legacy configuration options
- Simplify codebase

## Benefits

1. **Unified System**: Single source of truth for all entity configuration
2. **Better Performance**: Efficient caching and lazy loading
3. **Improved DX**: Cleaner API with intuitive methods
4. **Extensibility**: Easy to add new features and metadata
5. **Type Safety**: Strong typing throughout the system
6. **Backward Compatible**: Smooth migration path for existing users

## Testing Strategy

1. **Unit Tests**: Test all new components individually
2. **Integration Tests**: Test integration with existing features
3. **Migration Tests**: Test that old APIs still work during transition
4. **Performance Tests**: Ensure no performance regression
5. **End-to-End Tests**: Test complete workflows with new system