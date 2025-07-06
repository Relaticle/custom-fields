# Enhanced Architecture: Filament-Native Pattern

## Overview

This document outlines the enhanced architecture for the Custom Fields package's Integration folder, following
Filament's native patterns and conventions while implementing the new builder API from spec.md.

## Architecture Goals

1. **Filament-Native Feel**: Use patterns that make the package feel like a natural extension of Filament
2. **Separation of Concerns**: Clear boundaries between component creation, configuration, and business logic
3. **Reduced Duplication**: Share common logic through traits and base classes
4. **Maintainability**: Easy to understand, test, and extend
5. **Backward Compatibility**: Existing APIs continue to work

## Folder Structure

```
src/Filament/Integration/
├── Builders/                        # New API from spec.md
│   ├── Concerns/                    # Filament-style traits
│   │   ├── HasFieldFilters.php     # only(), except() methods
│   │   ├── HasModelContext.php     # forModel() method
│   │   └── BuildsComponents.php    # Common builder logic
│   ├── CustomFieldsBuilder.php     # Abstract base
│   ├── FormBuilder.php             # Returns Collection<Section|Fieldset>
│   ├── TableBuilder.php            # Returns Collection<Column|Filter>
│   └── InfolistBuilder.php         # Returns Collection<Section|Entry>
│
├── Components/                      # Organized by Filament package
│   ├── Concerns/                    # Shared component traits
│   │   ├── HasCustomFieldState.php # State management
│   │   ├── InteractsWithCustomFields.php
│   │   └── ConfiguresVisibility.php
│   │
│   ├── Forms/
│   │   ├── Fields/                 # All form field implementations
│   │   │   ├── CustomFieldInput.php # Base class
│   │   │   ├── TextInput.php
│   │   │   ├── Select.php
│   │   │   └── ... (other fields)
│   │   └── Sections/
│   │       └── CustomFieldSection.php
│   │
│   ├── Tables/
│   │   ├── Columns/
│   │   │   ├── CustomFieldColumn.php # Base class
│   │   │   ├── TextColumn.php
│   │   │   └── ... (other columns)
│   │   └── Filters/
│   │       ├── CustomFieldFilter.php # Base class
│   │       └── ... (filters)
│   │
│   └── Infolists/
│       ├── Entries/
│       │   ├── CustomFieldEntry.php # Base class
│       │   └── ... (entries)
│       └── Sections/
│           └── CustomFieldSection.php
│
├── Services/                        # Core business logic
│   ├── ComponentResolver.php       # Maps field types to components
│   ├── FieldRepository.php         # Fetches and filters fields
│   ├── StateManager.php            # Handles custom field values
│   └── VisibilityResolver.php      # Field dependency logic
│
├── Factories/                       # Simple, focused factories
│   ├── Contracts/
│   │   └── ComponentFactoryInterface.php
│   ├── FormComponentFactory.php
│   ├── TableComponentFactory.php
│   └── InfolistComponentFactory.php
│
├── Support/                         # Utilities and helpers
│   ├── ComponentRegistry.php       # Registers custom components
│   ├── FieldTypeMapper.php         # Maps field types to classes
│   └── ValueCaster.php             # Type casting for values
│
├── Actions/                         # Keep as-is
│   ├── Exports/
│   └── Imports/
│
└── CustomFieldsManager.php         # Main entry point
```

## Key Components

### 1. Builders (New API)

#### Base Builder

```php
namespace CustomFields\Filament\Integration\Builders;

abstract class CustomFieldsBuilder
{
    use Concerns\HasFieldFilters;
    use Concerns\HasModelContext;
    use Concerns\BuildsComponents;
    
    public function __construct(
        protected FieldRepository $fieldRepository,
        protected ComponentFactoryInterface $factory,
        protected VisibilityResolver $visibilityResolver
    ) {}
    
    abstract public function components(): Collection;
}
```

#### Form Builder Example

```php
class FormBuilder extends CustomFieldsBuilder
{
    public function components(): Collection
    {
        $sections = $this->fieldRepository
            ->getSectionsWithFields($this->entityType)
            ->filter(fn ($section) => $this->hasVisibleFields($section));
        
        return $sections->map(function ($section) {
            return Section::make($section->label)
                ->schema($this->buildFieldsForSection($section))
                ->collapsed($section->is_collapsed)
                ->description($section->description);
        });
    }
    
    public function build(): Component
    {
        return Grid::make()->schema(
            $this->components()->toArray()
        );
    }
}
```

### 2. Component Base Classes

#### Form Field Base

```php
namespace CustomFields\Filament\Integration\Components\Forms\Fields;

abstract class CustomFieldInput implements FieldComponentInterface
{
    use Components\Concerns\HasCustomFieldState;
    use Components\Concerns\ConfiguresVisibility;
    
    abstract protected function getFieldClass(): string;
    
    public function make(CustomField $field): Field
    {
        $component = $this->getFieldClass()::make("custom_fields.{$field->code}")
            ->label($field->label)
            ->helperText($field->help_text)
            ->required($field->is_required)
            ->disabled($field->is_readonly);
        
        return $this->configureVisibility($component, $field);
    }
}
```

#### Table Column Base

```php
namespace CustomFields\Filament\Integration\Components\Tables\Columns;

abstract class CustomFieldColumn implements ColumnInterface
{
    use Components\Concerns\HasCustomFieldState;
    
    abstract protected function getColumnClass(): string;
    
    public function make(CustomField $field): Column
    {
        return $this->getColumnClass()::make("custom_fields.{$field->code}")
            ->label($field->label)
            ->getStateUsing(fn ($record) => $this->resolveState($record, $field))
            ->sortable($field->getConfigValue('sortable', false))
            ->searchable($field->getConfigValue('searchable', false));
    }
}
```

### 3. Services Layer

#### Field Repository

```php
namespace CustomFields\Filament\Integration\Services;

class FieldRepository
{
    public function getSectionsWithFields(string $entityType): Collection
    {
        return CustomFieldSection::query()
            ->with(['customFields' => fn ($query) => 
                $query->where('entity_type', $entityType)
                      ->where('is_active', true)
                      ->orderBy('order')
            ])
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }
    
    public function getFields(string $entityType, array $only = [], array $except = []): Collection
    {
        $query = CustomField::query()
            ->where('entity_type', $entityType)
            ->where('is_active', true);
        
        if (!empty($only)) {
            $query->whereIn('code', $only);
        }
        
        if (!empty($except)) {
            $query->whereNotIn('code', $except);
        }
        
        return $query->orderBy('order')->get();
    }
}
```

#### State Manager

```php
namespace CustomFields\Filament\Integration\Services;

class StateManager
{
    public function loadValues(Model $model, Collection $fields): array
    {
        if (!$model->exists) {
            return [];
        }
        
        $values = $model->customFieldValues()
            ->whereIn('custom_field_id', $fields->pluck('id'))
            ->get()
            ->keyBy('custom_field_id');
        
        return $fields->mapWithKeys(function ($field) use ($values) {
            $value = $values->get($field->id)?->value;
            return [$field->code => $this->castValue($value, $field)];
        })->toArray();
    }
    
    protected function castValue($value, CustomField $field)
    {
        return match($field->type) {
            'boolean' => (bool) $value,
            'number' => (float) $value,
            'date' => $value ? Carbon::parse($value) : null,
            'array' => json_decode($value, true) ?? [],
            default => $value,
        };
    }
}
```

### 4. Factory Pattern

```php
namespace CustomFields\Filament\Integration\Factories;

interface ComponentFactoryInterface
{
    public function make(CustomField $field): mixed;
}

class FormComponentFactory implements ComponentFactoryInterface
{
    public function __construct(
        protected ComponentRegistry $registry
    ) {}
    
    public function make(CustomField $field): Field
    {
        $componentClass = $this->registry->getFormComponent($field->type);
        
        if (!$componentClass) {
            throw new UnsupportedFieldTypeException(
                "No form component registered for type: {$field->type}"
            );
        }
        
        return app($componentClass)->make($field);
    }
}
```

## Implementation Benefits

1. **Separation of Concerns**
    - Builders handle API and component composition
    - Services handle business logic
    - Components handle Filament integration
    - Factories handle instantiation

2. **Testability**
    - Each service can be tested independently
    - Builders can be tested with mocked services
    - Components can be tested in isolation

3. **Extensibility**
    - New field types added by registering components
    - Custom builders can be created
    - Services can be overridden via DI

4. **Maintainability**
    - Clear folder structure
    - Single responsibility for each class
    - Consistent patterns throughout

5. **Performance**
    - Efficient queries with eager loading
    - Caching where appropriate
    - Minimal database queries

## Migration Strategy

### Phase 1: Foundation (Week 1)

1. Create new folder structure
2. Implement base classes and traits
3. Set up service layer

### Phase 2: Component Migration (Week 2-3)

1. Migrate form components to new base class
2. Migrate table columns and filters
3. Migrate infolist entries

### Phase 3: Builder Implementation (Week 4)

1. Implement builders
2. Update CustomFieldsManager
3. Add tests for new API

### Phase 4: Cleanup (Week 5)

1. Remove deprecated code
2. Update documentation
3. Performance optimization

## Backward Compatibility

The existing API will be maintained during migration:

```php
// Old API (still works)
CustomFields::formComponent($entity)

// New API (additional option)
CustomFields::form()
    ->forModel($entity)
    ->except(['internal_notes'])
    ->components()
```

## Code Style Guidelines

1. Follow Filament naming conventions
2. Use static `make()` methods for instantiation
3. Return `$this` for fluent interface methods
4. Use traits for shared behaviors
5. Type hint all parameters and return types

## Testing Strategy

1. Unit tests for each service
2. Integration tests for builders
3. Component tests with Filament
4. End-to-end tests for full workflows

## Performance Considerations

1. Use eager loading for relationships
2. Cache field definitions per request
3. Minimize database queries in loops
4. Use database indexes appropriately

## Security Considerations

1. Validate all field configurations
2. Sanitize user inputs
3. Check permissions before loading values
4. Audit trail for value changes