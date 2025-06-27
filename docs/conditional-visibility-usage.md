# Conditional Visibility Usage Guide

This guide shows how to use the refactored conditional visibility system with proper Filament 4 reactive patterns.

## Key Components

### 1. ENUMs for Type Safety

```php
use Relaticle\CustomFields\Enums\ConditionalVisibilityMode;
use Relaticle\CustomFields\Enums\ConditionalVisibilityLogic;
use Relaticle\CustomFields\Enums\ConditionOperator;

// Visibility modes
ConditionalVisibilityMode::ALWAYS       // Always show
ConditionalVisibilityMode::SHOW_WHEN    // Show when conditions are met
ConditionalVisibilityMode::HIDE_WHEN    // Hide when conditions are met

// Logic for combining conditions
ConditionalVisibilityLogic::ALL          // All conditions must be met (AND)
ConditionalVisibilityLogic::ANY          // Any condition can be met (OR)

// Available operators
ConditionOperator::EQUALS                // =
ConditionOperator::NOT_EQUALS            // !=
ConditionOperator::GREATER_THAN          // >
ConditionOperator::CONTAINS              // Contains
ConditionOperator::IS_EMPTY              // Is empty
// ... and many more
```

### 2. Data Structure

```php
use Relaticle\CustomFields\Data\CustomFieldConditionsData;

$conditionalVisibility = new CustomFieldConditionsData(
    enabled: ConditionalVisibilityMode::SHOW_WHEN,
    logic: ConditionalVisibilityLogic::ALL,
    conditions: [
        ['field' => 'status', 'operator' => ConditionOperator::EQUALS->value, 'value' => 'active'],
        ['field' => 'priority', 'operator' => ConditionOperator::GREATER_THAN->value, 'value' => '5'],
    ],
    always_save: false
);
```

## Usage in Forms

### Basic Usage

```php
use Relaticle\CustomFields\Integration\Forms\FieldConfigurator;

$configurator = app(FieldConfigurator::class);

// Simple field configuration
$field = $configurator->configure(
    TextInput::make('description'),
    $customField
);
```

### With Dependency Management

```php
// Build dependency map for optimal live field setup
$customFields = CustomField::forEntity(User::class)->get();
$dependencyMap = $configurator->buildFieldDependencyMap($customFields);

$formFields = [];
foreach ($customFields as $customField) {
    $fieldComponent = match ($customField->type) {
        CustomFieldType::TEXT => TextInput::make($customField->code),
        CustomFieldType::SELECT => Select::make($customField->code),
        // ... other field types
    };

    // Configure with dependencies
    $dependentFields = $dependencyMap[$customField->code] ?? [];
    $formFields[] = $configurator->configure(
        $fieldComponent, 
        $customField,
        $dependentFields
    );
}
```

## How It Works

### Reactive System

1. **Dependency Fields**: Fields that other fields depend on are automatically made `live()`
2. **Conditional Fields**: Fields with conditions use `visible()` with `Get $get` callback
3. **Real-time Updates**: Filament's live system triggers visibility updates when dependency values change

### Example Flow

```php
// Field A (status) - made live because field B depends on it
Select::make('status')
    ->live()  // Added automatically
    ->options(['active', 'inactive']);

// Field B (description) - visible when status = 'active'
TextInput::make('description')
    ->visible(function (Get $get): bool {
        return $get('status') === 'active';
    });
```

### Performance Considerations

- **JavaScript-first**: For simple conditions, consider using `visibleJs()` for better performance
- **Live fields**: Only fields with dependents are made live, reducing unnecessary requests
- **Debouncing**: Use `debounce()` for fields that change frequently

## Advanced Usage

### Custom Operators for Specific Field Types

```php
// The system automatically filters operators based on field type
$operators = ConditionOperator::forFieldType(CustomFieldType::NUMBER);
// Returns: [EQUALS, NOT_EQUALS, GREATER_THAN, LESS_THAN, ...]

$operators = ConditionOperator::forFieldType(CustomFieldType::MULTI_SELECT);
// Returns: [CONTAINS, NOT_CONTAINS, IS_EMPTY, IS_NOT_EMPTY]
```

### Validation

```php
$errors = $configurator->validateConditions($customField, $availableFields);
// Returns array of validation error messages
```

### Dependencies

```php
$dependencies = $configurator->getFieldDependencies($customField);
// Returns array of field codes this field depends on
```

## Migration from Legacy Code

### Before (Legacy)
```php
// Old hardcoded approach
->visible(fn ($get) => $get('type') === 'detailed')
```

### After (New ENUM-based)
```php
// New structured approach
$conditionalVisibility = new CustomFieldConditionsData(
    enabled: ConditionalVisibilityMode::SHOW_WHEN,
    logic: ConditionalVisibilityLogic::ALL,
    conditions: [
        ['field' => 'type', 'operator' => ConditionOperator::EQUALS->value, 'value' => 'detailed']
    ]
);

// Automatic handling via FieldConfigurator
$field = $configurator->configure($field, $customField);
```

## Benefits

1. **Type Safety**: ENUMs prevent typos and provide IDE autocompletion
2. **Reusability**: Conditions are data structures, not code
3. **Performance**: Smart live field management
4. **Maintainability**: Clean separation of concerns
5. **Extensibility**: Easy to add new operators and modes
6. **Testing**: Structured data makes testing easier