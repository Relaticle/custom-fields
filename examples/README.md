# Custom Field Type Extension System

The Relaticle Custom Fields package now supports extensible custom field types, allowing developers to create and register new field types without modifying core package files.

## Overview

The extension system provides:

- **Type-safe API** for defining custom field types
- **Automatic discovery** of field types from configured directories/namespaces
- **Seamless integration** with admin panel, forms, tables, and infolists
- **Zero breaking changes** to existing functionality
- **Performance optimized** with intelligent caching

## Quick Start

### 1. Create a Field Type Definition

```php
<?php

namespace App\CustomFields\Types;

use Relaticle\CustomFields\Contracts\FieldTypeDefinitionInterface;
use Relaticle\CustomFields\Enums\FieldCategory;
use Relaticle\CustomFields\Enums\CustomFieldValidationRule;

class RatingFieldType implements FieldTypeDefinitionInterface
{
    public function getKey(): string
    {
        return 'rating';
    }

    public function getLabel(): string
    {
        return 'Rating (1-5 Stars)';
    }

    public function getIcon(): string
    {
        return 'mdi-star';
    }

    public function getCategory(): FieldCategory
    {
        return FieldCategory::NUMERIC;
    }

    public function getAllowedValidationRules(): array
    {
        return [
            CustomFieldValidationRule::REQUIRED,
            CustomFieldValidationRule::MIN,
            CustomFieldValidationRule::MAX,
        ];
    }

    public function getFormComponentClass(): string
    {
        return RatingFormComponent::class;
    }

    public function getTableColumnClass(): string
    {
        return RatingTableColumn::class;
    }

    public function getInfolistEntryClass(): string
    {
        return RatingInfolistEntry::class;
    }

    public function isSearchable(): bool
    {
        return false;
    }

    public function isFilterable(): bool
    {
        return true;
    }

    public function isEncryptable(): bool
    {
        return false;
    }

    public function getPriority(): int
    {
        return 50; // Lower numbers appear first in admin panel
    }
}
```

### 2. Create Component Classes

#### Form Component
```php
<?php

namespace App\CustomFields\Types;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Integration\Forms\Components\FieldComponentInterface;
use Relaticle\CustomFields\Integration\Forms\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;

class RatingFormComponent implements FieldComponentInterface
{
    public function __construct(
        private readonly FieldConfigurator $configurator
    ) {}

    public function make(CustomField $customField, array $dependentFieldCodes = [], ?Collection $allFields = null): Field
    {
        $field = Select::make($customField->code)
            ->label($customField->name)
            ->options([
                1 => '⭐ (1 star)',
                2 => '⭐⭐ (2 stars)',
                3 => '⭐⭐⭐ (3 stars)',
                4 => '⭐⭐⭐⭐ (4 stars)',
                5 => '⭐⭐⭐⭐⭐ (5 stars)',
            ]);

        return $this->configurator->configure($field, $customField, $dependentFieldCodes, $allFields);
    }
}
```

#### Table Column Component
```php
<?php

namespace App\CustomFields\Types;

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Relaticle\CustomFields\Integration\Tables\Columns\ColumnInterface;
use Relaticle\CustomFields\Models\CustomField;

class RatingTableColumn implements ColumnInterface
{
    public function make(CustomField $customField): Column
    {
        return TextColumn::make($customField->code)
            ->label($customField->name)
            ->formatStateUsing(fn (?string $state): string => 
                $state ? str_repeat('⭐', (int) $state) : '—'
            )
            ->sortable()
            ->alignCenter();
    }
}
```

#### Infolist Entry Component
```php
<?php

namespace App\CustomFields\Types;

use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\TextEntry;
use Relaticle\CustomFields\Integration\Infolists\FieldInfolistsComponentInterface;
use Relaticle\CustomFields\Models\CustomField;

class RatingInfolistEntry implements FieldInfolistsComponentInterface
{
    public function make(CustomField $customField): Entry
    {
        return TextEntry::make($customField->code)
            ->label($customField->name)
            ->formatStateUsing(function (?string $state): string {
                if (!$state) return 'No rating';
                $rating = (int) $state;
                return str_repeat('⭐', $rating) . str_repeat('☆', 5 - $rating) . " ({$rating}/5)";
            });
    }
}
```

### 3. Register Your Field Type

#### Option A: Automatic Discovery (Recommended)

Update your `config/custom-fields.php`:

```php
'field_type_discovery' => [
    'directories' => [
        app_path('CustomFields/Types'),
    ],
    'namespaces' => [
        'App\\CustomFields\\Types',
    ],
    'classes' => [
        App\CustomFields\Types\RatingFieldType::class,
    ],
    'enabled' => true,
    'cache' => true,
],
```

#### Option B: Manual Registration

In your service provider:

```php
use Relaticle\CustomFields\Support\CustomFieldTypes;

public function boot(): void
{
    CustomFieldTypes::register(new RatingFieldType());
    
    // Or register multiple at once
    CustomFieldTypes::registerClasses([
        RatingFieldType::class,
        ProgressFieldType::class,
        FileUploadFieldType::class,
    ]);
}
```

## Field Categories

Field types must belong to one of these categories:

- `FieldCategory::TEXT` - Text-based fields (text, textarea, etc.)
- `FieldCategory::NUMERIC` - Number fields (number, currency, rating)
- `FieldCategory::DATE` - Date/time fields
- `FieldCategory::BOOLEAN` - Boolean fields (toggle, checkbox)
- `FieldCategory::SINGLE_OPTION` - Single selection (select, radio)
- `FieldCategory::MULTI_OPTION` - Multiple selection (multi-select, checkboxes)

## Validation Rules

Available validation rules for custom field types:

- `CustomFieldValidationRule::REQUIRED`
- `CustomFieldValidationRule::MIN` / `MAX` / `BETWEEN`
- `CustomFieldValidationRule::NUMERIC` / `INTEGER` / `DECIMAL`
- `CustomFieldValidationRule::STRING` / `EMAIL` / `URL`
- `CustomFieldValidationRule::DATE` / `AFTER` / `BEFORE`
- `CustomFieldValidationRule::ARRAY` / `IN`
- `CustomFieldValidationRule::ALPHA` / `ALPHA_NUM` / `ALPHA_DASH`
- `CustomFieldValidationRule::REGEX` / `STARTS_WITH`
- `CustomFieldValidationRule::BOOLEAN`

## Configuration Options

### Discovery Configuration

```php
'field_type_discovery' => [
    'directories' => [
        // Scan these directories for field type classes
        app_path('CustomFields/Types'),
        base_path('packages/my-package/src/FieldTypes'),
    ],
    
    'namespaces' => [
        // Scan these namespaces using PSR-4 autoloader
        'App\\CustomFields\\Types',
        'MyPackage\\FieldTypes',
    ],
    
    'classes' => [
        // Explicitly register these classes
        App\CustomFields\Types\RatingFieldType::class,
    ],
    
    'enabled' => true,        // Enable/disable discovery
    'cache' => true,          // Cache discovery results
    'cache_duration' => 60,   // Cache duration in minutes
],

'custom_field_types' => [
    'default_priority' => 200,    // Default priority for custom types
    'validation' => [
        'strict_mode' => true,                      // Strict validation
        'validate_component_interfaces' => true,   // Validate interfaces
    ],
],
```

## Helper Functions

The `CustomFieldTypes` helper provides convenient methods:

```php
use Relaticle\CustomFields\Support\CustomFieldTypes;

// Register field types
CustomFieldTypes::register(new RatingFieldType());
CustomFieldTypes::registerClass(RatingFieldType::class);
CustomFieldTypes::registerMany([new RatingFieldType(), new ProgressFieldType()]);

// Get information
$allTypes = CustomFieldTypes::getAllFieldTypes();
$hasRating = CustomFieldTypes::hasFieldType('rating');

// Cache management
CustomFieldTypes::clearCache(); // Force re-discovery
```

## Best Practices

### 1. Naming Conventions
- Use descriptive, unique keys: `'rating'`, `'file-upload'`, `'progress-bar'`
- Avoid conflicts with built-in types
- Use kebab-case for multi-word keys

### 2. Component Implementation
- Always implement all three required interfaces
- Use dependency injection for configurators and services
- Handle null/empty values gracefully
- Follow Filament component patterns

### 3. Validation
- Only return validation rules that make sense for your field type
- Consider the field category when choosing rules
- Test validation thoroughly

### 4. Performance
- Implement efficient state formatting
- Use appropriate caching strategies
- Minimize database queries in components

### 5. User Experience
- Provide clear labels and icons
- Use appropriate priority values
- Consider accessibility in component design

## Troubleshooting

### Field Type Not Appearing
1. Check that discovery is enabled in config
2. Verify class implements `FieldTypeDefinitionInterface`
3. Ensure component classes exist and implement required interfaces
4. Clear cache: `CustomFieldTypes::clearCache()`

### Component Errors
1. Verify component classes implement correct interfaces
2. Check constructor dependencies are resolvable
3. Test component classes independently

### Validation Issues
1. Ensure validation rules are appropriate for field category
2. Test validation rule combinations
3. Check for conflicts with conditional visibility

## Advanced Usage

### Custom Categories
While you must use existing `FieldCategory` values, you can create specialized behavior within categories by customizing component implementations.

### Integration with Packages
Custom field types work seamlessly with all package features:
- Conditional visibility
- Multi-tenancy
- Encryption (if enabled)
- Import/Export
- Search and filtering

### Example Use Cases
- **Rating fields** - Star ratings, numeric scores
- **File uploads** - Image galleries, document attachments  
- **Progress bars** - Completion percentages, status indicators
- **Geographic fields** - Address pickers, map coordinates
- **Rich media** - Video embeds, audio players
- **Custom selects** - API-driven options, hierarchical data

This extension system provides unlimited flexibility while maintaining the package's core stability and performance characteristics.