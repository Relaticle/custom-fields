# Custom Fields API Refactoring Specification

## Overview

This specification outlines the refactoring of the Custom Fields package to provide a more flexible, maintainable API
that follows Laravel conventions and reduces code duplication.

## Current Architecture Issues

1. **Limited Flexibility**: Current API returns a single component, making it difficult to:
    - Mix custom fields with regular fields at specific positions
    - Apply different layouts or groupings
    - Exclude certain fields conditionally

2. **Code Duplication**: Multiple factory classes with nearly identical logic
3. **Inconsistent Patterns**: Different approaches for Forms, Tables, and Infolists

## New API Design

### Core Principles

1. **Builder Pattern**: Mutable builders following Laravel conventions
2. **Granular Control**: Return collections of components instead of single wrapped components
3. **Consistent Interface**: Unified API across Forms, Tables, and Infolists
4. **Minimal Changes**: Refactor for maximum value with minimal disruption

### API Examples

```php
// Table Columns - returns Collection of Column components
$columns = CustomFields::table()
    ->forModel(Customer::class)
    ->except(['internal_notes'])
    ->columns();

// Table Filters - returns Collection of Filter components  
$filters = CustomFields::table()
    ->forModel(Customer::class)
    ->only(['status', 'created_at'])
    ->filters();

// Forms - returns Collection of Section/Fieldset/Grid components
$components = CustomFields::form()
    ->forModel($customer) // instance for loading values
    ->except(['salary', 'ssn'])
    ->components();

// Forms - returns single Grid component (backward compatible)
$grid = CustomFields::form()
    ->forModel($customer)
    ->build();

// Infolists - returns Collection of Section/Fieldset/Grid components
$entries = CustomFields::infolist()
    ->forModel($customer) // instance for loading values  
    ->only(['name', 'email', 'phone'])
    ->components();
```

### Builder Methods

All builders support:

- `forModel($modelOrClass)`: Accept both model instance and class name
- `only(array $fields)`: Include only specified field codes
- `except(array $fields)`: Exclude specified field codes

Context-specific methods:

- Table Builder: `columns()`, `filters()`
- Form Builder: `components()`, `build()`
- Infolist Builder: `components()`, `build()`

### Component Structure

#### Forms and Infolists

The `components()` method returns a Collection of actual Filament section components:

```php
Collection([
    Section::make('General Information')
        ->description('Basic customer details')
        ->schema([
            TextInput::make('custom_fields.first_name')->required(),
            TextInput::make('custom_fields.last_name')->required(),
        ])
        ->columns(2),
    
    Fieldset::make('custom_fields.contact')
        ->label('Contact Information')
        ->schema([
            TextInput::make('custom_fields.email')->email(),
            TextInput::make('custom_fields.phone')->tel(),
        ])
        ->columns(2),
])
```

Key points:

- All fields are guaranteed to have a section
- Empty sections (after filtering) are automatically removed
- Components are ready to use or further customize

#### Tables

The `columns()` method returns a Collection of Column components:

```php
Collection([
    TextColumn::make('custom_fields.customer_code'),
    IconColumn::make('custom_fields.is_active')->boolean(),
    // ...
])
```

The `filters()` method returns a Collection of Filter components:

```php
Collection([
    SelectFilter::make('custom_fields.status'),
    DateFilter::make('custom_fields.created_at'),
    // ...
])
```

### Implementation Architecture

#### 1. Base Builder Class

```php
abstract class AbstractCustomFieldsBuilder
{
    protected ?string $entityType = null;
    protected ?Model $model = null;
    protected array $onlyFields = [];
    protected array $exceptFields = [];
    
    public function forModel(Model|string $modelOrClass): static
    {
        // Handle both instance and class name
        // Set entityType and optionally model instance
    }
    
    public function only(array $fields): static
    {
        $this->onlyFields = $fields;
        return $this;
    }
    
    public function except(array $fields): static
    {
        $this->exceptFields = $fields;
        return $this;
    }
    
    protected function shouldIncludeField(CustomField $field): bool
    {
        // Apply filtering logic
    }
}
```

#### 2. Specific Builders

```php
class TableBuilder extends AbstractCustomFieldsBuilder
{
    public function columns(): Collection
    {
        // Load fields, apply filters, create column components
    }
    
    public function filters(): Collection
    {
        // Load fields, apply filters, create filter components
    }
}

class FormBuilder extends AbstractCustomFieldsBuilder
{
    public function components(): Collection
    {
        // Load sections with fields
        // Apply filters
        // Create section components with field components
        // Load values if model instance provided
    }
    
    public function build(): Component
    {
        // Return Grid component containing all sections
        return Grid::make()->schema(
            $this->components()->toArray()
        );
    }
}

class InfolistBuilder extends AbstractCustomFieldsBuilder
{
    // Similar to FormBuilder
}
```

#### 3. Unified Factory System

Refactor existing factories to reduce duplication:

```php
abstract class AbstractComponentFactory
{
    abstract protected function getComponentProperty(): string;
    abstract protected function createComponent(CustomField $field): mixed;
    
    public function create(CustomField $field): mixed
    {
        $componentClass = $field->typeData->{$this->getComponentProperty()};
        $component = app($componentClass);
        return $component->make($field);
    }
}

class ColumnFactory extends AbstractComponentFactory
{
    protected function getComponentProperty(): string
    {
        return 'tableColumn';
    }
}

// Similar for FilterFactory, FieldComponentFactory, etc.
```

### Value Loading Behavior

- **Tables**: Only need entity type to determine which fields to show
- **Forms/Infolists**: When provided a model instance, automatically load saved values from `custom_field_values` table
- Values are loaded efficiently with eager loading to prevent N+1 queries

### Migration Path

1. Keep existing API working:
    - `CustomFields::formComponent()` continues to work
    - Returns same component structure as before

2. New API available alongside:
    - Developers can gradually migrate to new API
    - Both APIs use same underlying implementation

### Benefits

1. **Flexibility**: Developers can position custom fields anywhere in their forms
2. **Consistency**: Same patterns across Forms, Tables, and Infolists
3. **Maintainability**: Reduced code duplication through inheritance
4. **Laravel Conventions**: Familiar builder pattern matching Query Builder
5. **Performance**: Efficient eager loading and filtering at query level

### Implementation Architecture

For the detailed implementation architecture following Filament-native patterns, please refer
to [Enhanced Architecture Document](./enhanced-architecture.md).

### Next Steps

1. Create base builder classes and interfaces
2. Refactor factory classes to use inheritance
3. Implement builders for Table, Form, and Infolist
4. Update CustomFieldsManager to expose new builders
5. Write comprehensive tests
6. Update documentation with examples