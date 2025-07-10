# Entity Management System - Usage Examples

This guide demonstrates how to use the new Entity Management System in your Laravel/Filament application with the Custom Fields package.

## Table of Contents
1. [Basic Setup](#basic-setup)
2. [Registering Entities](#registering-entities)
3. [Querying Entities](#querying-entities)
4. [Using Entities in Your Application](#using-entities-in-your-application)
5. [Advanced Usage](#advanced-usage)

## Basic Setup

### 1. Configuration

The entity management system works out of the box with sensible defaults. To customize, publish the configuration:

```bash
php artisan vendor:publish --tag=custom-fields-config
```

Then configure in `config/custom-fields.php`:

```php
'entity_management' => [
    'auto_discover_entities' => true,
    'entity_discovery_paths' => [
        app_path('Models'),
        app_path('Domain/*/Models'), // For DDD structure
    ],
    'cache_entities' => true,
    'excluded_models' => [
        App\Models\SystemLog::class, // Exclude specific models
    ],
],
```

### 2. Model Setup

Ensure your models implement the `HasCustomFields` interface and use the trait:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Relaticle\CustomFields\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;

class Product extends Model implements HasCustomFields
{
    use UsesCustomFields;
    
    // Optional: Customize entity configuration
    public static function getCustomFieldsIcon(): string
    {
        return 'heroicon-o-shopping-bag';
    }
    
    public static function getCustomFieldsPrimaryAttribute(): string
    {
        return 'name';
    }
    
    public static function getCustomFieldsSearchAttributes(): array
    {
        return ['name', 'sku', 'description'];
    }
}
```

## Registering Entities

### Automatic Registration

If you have a Filament Resource, entities are automatically registered:

```php
namespace App\Filament\Resources;

use App\Models\Product;
use Filament\Resources\Resource;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    
    // Entity configuration is automatically extracted from the resource
}
```

### Manual Registration

Register entities in a service provider:

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Relaticle\CustomFields\Facades\Entities;
use Relaticle\CustomFields\Entities\EntityConfiguration;
use App\Models\Customer;
use App\Models\Invoice;

class CustomFieldsEntityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register from array
        Entities::registerFromArray([
            'modelClass' => Customer::class,
            'labelSingular' => 'Customer',
            'labelPlural' => 'Customers',
            'icon' => 'heroicon-o-user',
            'primaryAttribute' => 'name',
            'searchAttributes' => ['name', 'email', 'phone'],
            'features' => ['custom_fields', 'lookup_source'],
            'priority' => 10,
        ]);
        
        // Register using builder
        Entities::registerEntity(
            EntityConfiguration::make(Invoice::class)
                ->label('Invoice', 'Invoices')
                ->icon('heroicon-o-document-text')
                ->primaryAttribute('invoice_number')
                ->searchable(['invoice_number', 'customer_name'])
                ->asLookupSource()
                ->withTenantScope()
                ->feature('exportable')
                ->feature('importable')
                ->priority(20)
                ->build()
        );
        
        // Register multiple entities
        Entities::register([
            'orders' => [
                'modelClass' => Order::class,
                'labelSingular' => 'Order',
                'labelPlural' => 'Orders',
                'icon' => 'heroicon-o-shopping-cart',
                'primaryAttribute' => 'order_number',
                'searchAttributes' => ['order_number', 'customer_name'],
                'features' => ['custom_fields', 'lookup_source', 'exportable'],
            ],
            'tickets' => [
                'modelClass' => Ticket::class,
                'labelSingular' => 'Support Ticket',
                'labelPlural' => 'Support Tickets',
                'icon' => 'heroicon-o-ticket',
                'primaryAttribute' => 'title',
                'searchAttributes' => ['title', 'description'],
                'features' => ['custom_fields'],
            ],
        ]);
    }
}
```

## Querying Entities

### Basic Queries

```php
use Relaticle\CustomFields\Facades\Entities;

// Get all entities
$allEntities = Entities::getEntities();

// Get entities that support custom fields
$customFieldEntities = Entities::withCustomFields();

// Get entities that can be used as lookup sources
$lookupEntities = Entities::asLookupSources();

// Find a specific entity
$productEntity = Entities::getEntity('products');
$productEntity = Entities::getEntity(Product::class);

// Check if entity exists
if (Entities::hasEntity('products')) {
    // Entity exists
}
```

### Advanced Queries

```php
// Get entities with specific features
$exportableEntities = Entities::getEntitiesWithFeature('exportable');

// Use collection methods
$entities = Entities::getEntities()
    ->withFeature('custom_fields')
    ->withoutFeature('versionable')
    ->sortedByPriority()
    ->filter(fn ($entity) => str_contains($entity->getLabelPlural(), 'Product'));

// Get entities as options for select fields
$options = Entities::getOptions(); // ['products' => 'Products', ...]

// Get detailed options with icons
$detailedOptions = Entities::getEntities()
    ->withCustomFields()
    ->toDetailedOptions();
// Returns: ['products' => ['label' => 'Products', 'icon' => '...', 'modelClass' => '...']]

// Group by feature
$grouped = Entities::getEntities()->groupByFeature('exportable');
```

## Using Entities in Your Application

### In Filament Forms

```php
use Filament\Forms\Components\Select;
use Relaticle\CustomFields\Facades\Entities;

Select::make('entity_type')
    ->label('Entity Type')
    ->options(Entities::getOptions())
    ->searchable()
    ->required();
```

### In Custom Field Management

The entity system is automatically integrated with the custom fields management UI:

```php
// In your custom implementation
$entity = Entities::getEntity($entityType);

if ($entity) {
    $icon = $entity->getIcon();
    $label = $entity->getLabelPlural();
    $model = $entity->createModelInstance();
    $query = $entity->newQuery(); // With scopes applied
}
```

### In Lookups and Relations

```php
use Filament\Forms\Components\Select;
use Relaticle\CustomFields\Facades\Entities;

// Dynamic lookup based on entity configuration
Select::make('customer_id')
    ->label('Customer')
    ->searchable()
    ->getSearchResultsUsing(function (string $search) {
        $entity = Entities::getEntity('customers');
        
        if (!$entity) {
            return [];
        }
        
        $query = $entity->newQuery();
        $searchAttributes = $entity->getSearchAttributes();
        
        foreach ($searchAttributes as $attribute) {
            $query->orWhere($attribute, 'like', "%{$search}%");
        }
        
        return $query
            ->limit(50)
            ->pluck($entity->getPrimaryAttribute(), 'id');
    });
```

### In Import/Export

```php
use Relaticle\CustomFields\Facades\Entities;

class ImportController
{
    public function getImportableEntities()
    {
        return Entities::getEntities()
            ->withFeature('importable')
            ->toOptions();
    }
    
    public function import(string $entityAlias, array $data)
    {
        $entity = Entities::getEntity($entityAlias);
        
        if (!$entity || !$entity->hasFeature('importable')) {
            throw new \Exception('Entity not importable');
        }
        
        $model = $entity->createModelInstance();
        // Process import...
    }
}
```

## Advanced Usage

### Custom Entity Features

Define custom features for your entities:

```php
// In registration
EntityConfiguration::make(Document::class)
    ->feature('versionable')
    ->feature('approvable')
    ->feature('archivable')
    ->metadata([
        'max_versions' => 10,
        'approval_levels' => 2,
        'archive_after_days' => 365,
    ])
    ->build();

// Query by custom features
$versionableEntities = Entities::getEntitiesWithFeature('versionable');

// Access metadata
$entity = Entities::getEntity('documents');
$maxVersions = $entity->getMetadataValue('max_versions', 5); // Default: 5
```

### Entity Resolution Callbacks

Add custom logic when entities are resolved:

```php
// In a service provider
Entities::resolving(function (array $entities) {
    // Modify entities based on user permissions
    if (!auth()->user()->isAdmin()) {
        unset($entities['admin_logs']);
    }
    
    // Add dynamic entities
    foreach (config('dynamic_entities', []) as $alias => $config) {
        $entities[$alias] = EntityConfiguration::fromArray($config);
    }
    
    return $entities;
});
```

### Performance Optimization

```php
use Relaticle\CustomFields\Facades\Entities;

// Disable cache temporarily
Entities::withoutCache(function ($manager) {
    // Operations without cache
    $entities = $manager->getEntities();
});

// Clear cache manually
Entities::clearCache();

// Pre-warm cache
app()->booted(function () {
    Entities::getEntities(); // Triggers cache build
});
```

### Custom Entity Discovery

Create a custom discovery mechanism:

```php
namespace App\Services;

use Relaticle\CustomFields\Entities\EntityConfiguration;

class PluginEntityDiscovery
{
    public function discover(): array
    {
        $entities = [];
        
        foreach (app('plugins')->getEnabled() as $plugin) {
            $models = $plugin->getModels();
            
            foreach ($models as $model) {
                if (in_array(HasCustomFields::class, class_implements($model))) {
                    $entities[] = EntityConfiguration::fromArray([
                        'modelClass' => $model,
                        'labelSingular' => $plugin->getModelLabel($model),
                        'labelPlural' => $plugin->getModelPluralLabel($model),
                        'icon' => $plugin->getModelIcon($model),
                        'features' => ['custom_fields', 'lookup_source'],
                    ]);
                }
            }
        }
        
        return $entities;
    }
}

// Register in service provider
Entities::register(fn () => app(PluginEntityDiscovery::class)->discover());
```

### Testing with Entities

```php
use Relaticle\CustomFields\Facades\Entities;

class EntityTest extends TestCase
{
    public function test_custom_entity_registration()
    {
        // Register test entity
        Entities::registerFromArray([
            'modelClass' => TestModel::class,
            'labelSingular' => 'Test Model',
            'labelPlural' => 'Test Models',
            'features' => ['custom_fields', 'testable'],
        ]);
        
        // Assert entity exists
        $this->assertTrue(Entities::hasEntity('test_models'));
        
        // Assert entity configuration
        $entity = Entities::getEntity('test_models');
        $this->assertEquals('Test Model', $entity->getLabelSingular());
        $this->assertTrue($entity->hasFeature('testable'));
    }
    
    protected function tearDown(): void
    {
        // Clear cache after tests
        Entities::clearCache();
        parent::tearDown();
    }
}
```

## Best Practices

1. **Use Auto-Discovery**: Let the system discover entities automatically when possible
2. **Cache in Production**: Keep entity caching enabled for better performance
3. **Consistent Naming**: Use consistent naming for aliases and labels
4. **Feature Flags**: Use features to enable/disable functionality per entity
5. **Metadata for Configuration**: Store entity-specific configuration in metadata
6. **Lazy Registration**: Use closures for expensive registration operations
7. **Type Hints**: Always type hint when working with entity configurations

## Troubleshooting

### Entity Not Found

```php
// Debug entity registration
dd(Entities::getEntities()->getAliases());

// Check if model implements interface
$implements = class_implements(YourModel::class);
dd(in_array(HasCustomFields::class, $implements));
```

### Cache Issues

```bash
# Clear application cache
php artisan cache:clear

# Or programmatically
Entities::clearCache();
```

### Discovery Not Working

Check your configuration:

```php
// Verify discovery is enabled
dd(config('custom-fields.entity_management.auto_discover_entities'));

// Check discovery paths
dd(config('custom-fields.entity_management.entity_discovery_paths'));
```