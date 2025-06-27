# Fixed Conditional Visibility Implementation Guide

This guide shows how to properly implement reactive conditional visibility with optionable fields support.

## 🎯 **Fixed Issues**

1. ✅ **Reactivity**: Fields with dependencies are automatically made `live()`
2. ✅ **Optionable Fields**: Option IDs are converted to option names for comparison
3. ✅ **Consistent Paths**: All fields use `custom_fields.{code}` path structure

## 🔧 **Proper Implementation**

### 1. **Calculate Dependencies First**

```php
use Relaticle\CustomFields\Integration\Forms\FieldConfigurator;

// Get your custom fields
$customFields = CustomField::forEntity($entityType)->get();

// Calculate which fields depend on others
$fieldDependencies = FieldConfigurator::calculateFieldDependencies($customFields);

// Result: ['status' => ['description', 'priority'], 'category' => ['notes']]
```

### 2. **Configure Fields with Dependencies**

```php
$configurator = app(FieldConfigurator::class);
$formFields = [];

foreach ($customFields as $customField) {
    // Create appropriate field component
    $fieldComponent = match ($customField->type) {
        CustomFieldType::TEXT => TextInput::make("custom_fields.{$customField->code}"),
        CustomFieldType::SELECT => Select::make("custom_fields.{$customField->code}")
            ->options($customField->options()->pluck('name', 'id')),
        CustomFieldType::MULTI_SELECT => CheckboxList::make("custom_fields.{$customField->code}")
            ->options($customField->options()->pluck('name', 'id')),
        // ... other field types
    };

    // Get fields that depend on this field (makes it live)
    $dependentFields = $fieldDependencies[$customField->code] ?? [];

    // Configure the field with proper reactivity
    $formFields[] = $configurator->configure(
        $fieldComponent,
        $customField,
        $dependentFields  // This makes the field live if others depend on it
    );
}

return $formFields;
```

### 3. **How It Works Now**

#### **Dependency Field (Made Live)**
```php
// Status field - other fields depend on it, so it's made live
Select::make('custom_fields.status')
    ->live()  // Added automatically because others depend on it
    ->options([1 => 'Active', 2 => 'Inactive'])
```

#### **Dependent Field (Reactive)**
```php
// Description field - visible when status = 'Active'
TextInput::make('custom_fields.description')
    ->visible(function (Get $get) use ($conditionalVisibility): bool {
        // Gets raw value: 1 (option ID)
        $rawValue = $get('custom_fields.status');
        
        // Converts to: 'Active' (option name) for comparison
        $fieldValues = ['status' => 'Active'];
        
        // Evaluates condition: status equals 'Active'
        return $conditionalVisibility->evaluate($fieldValues);
    });
```

## 🎭 **Optionable Field Value Handling**

### **Before (Broken)**
```php
// Condition: status equals "Active"
// Form value: 1 (option ID)
// Comparison: 1 === "Active" → false ❌
```

### **After (Fixed)**
```php
// Condition: status equals "Active"  
// Form value: 1 (option ID)
// Normalized: "Active" (option name)
// Comparison: "Active" === "Active" → true ✅
```

### **Multi-Select Fields**
```php
// Condition: features contains "Premium"
// Form value: [2, 5, 8] (option IDs)
// Normalized: ["Basic", "Premium", "Advanced"] (option names)
// Comparison: in_array("Premium", ["Basic", "Premium", "Advanced"]) → true ✅
```

## 🔥 **Complete Example**

```php
class UserResource extends Resource
{
    public static function form(Form $form): Form
    {
        $customFields = CustomField::forEntity(User::class)->get();
        $fieldDependencies = FieldConfigurator::calculateFieldDependencies($customFields);
        $configurator = app(FieldConfigurator::class);

        $formFields = [
            TextInput::make('name')->required(),
            TextInput::make('email')->email()->required(),
        ];

        foreach ($customFields as $customField) {
            $fieldComponent = match ($customField->type) {
                CustomFieldType::TEXT => TextInput::make("custom_fields.{$customField->code}"),
                CustomFieldType::SELECT => Select::make("custom_fields.{$customField->code}")
                    ->options($customField->options()->pluck('name', 'id')),
                CustomFieldType::MULTI_SELECT => CheckboxList::make("custom_fields.{$customField->code}")
                    ->options($customField->options()->pluck('name', 'id')),
                CustomFieldType::TOGGLE => Toggle::make("custom_fields.{$customField->code}"),
                // ... other types
            };

            $dependentFields = $fieldDependencies[$customField->code] ?? [];
            
            $formFields[] = $configurator->configure(
                $fieldComponent,
                $customField,
                $dependentFields
            );
        }

        return $form->schema($formFields);
    }
}
```

## 📊 **Field Dependency Flow**

```
User Form with Custom Fields:
├── status (SELECT) → live() [others depend on it]
├── description (TEXT) → visible when status='Active'
├── priority (SELECT) → live() [others depend on it]  
├── notes (TEXTAREA) → visible when priority='High'
└── category (SELECT) → no dependencies
```

**Result:**
- `status` and `priority` are automatically made `live()`
- `description` shows/hides based on `status` selection
- `notes` shows/hides based on `priority` selection
- `category` is static (no reactivity needed)

## ⚡ **Performance Benefits**

1. **Minimal Live Fields**: Only fields with dependents are made live
2. **Efficient Updates**: Form only re-renders when dependency values change
3. **Smart Caching**: Field lookups are cached for better performance
4. **Proper Comparisons**: Option name comparisons work correctly

## 🐛 **Common Issues Fixed**

### ❌ **Before: Not Reactive**
```php
// Fields weren't live, so conditions never updated
$field->visible(fn($get) => $get('status') === 'active'); // Never updates
```

### ✅ **After: Properly Reactive**
```php
// Dependency field is live, triggers updates
$statusField->live(); // Added automatically
$field->visible(fn($get) => $get('custom_fields.status') === 'active'); // Updates on change
```

### ❌ **Before: Option ID vs Name Mismatch**
```php
// Comparing option ID (1) with option name ('Active')
$get('status') === 'Active' // 1 === 'Active' → false
```

### ✅ **After: Proper Option Comparison**
```php
// Option ID (1) converted to option name ('Active') automatically
$normalizedValue === 'Active' // 'Active' === 'Active' → true
```

This implementation ensures both reactivity and proper optionable field handling work seamlessly together!