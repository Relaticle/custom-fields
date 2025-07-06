# Detailed Migration Analysis

## Key Findings and Recommendations

### 1. Current Architecture

The system is transitioning from:
- **Old System**: `CustomFieldType` enum with all logic embedded
- **New System**: Individual `FieldType` classes implementing `FieldTypeDefinitionInterface`

Currently, only 2 out of 18 field types have been migrated:
- ✅ SelectFieldType
- ✅ DateTimeFieldType

### 2. Critical Inconsistency

**FieldDataType enum has a typo:**
```php
// Current (WRONG)
case MULTI_CHOICE = 'multi_option';

// Should be
case MULTI_CHOICE = 'multi_choice';
```

This inconsistency will cause issues when storing/retrieving data.

### 3. Method Migration Examples

#### Example 1: Checking if a field has multiple values
```php
// Old way
if ($field->type->hasMultipleValues()) {
    // ...
}

// New way
if ($field->typeData->dataType->isMultiChoiceField()) {
    // ...
}
```

#### Example 2: Getting compatible operators
```php
// Old way
$operators = $field->type->getCompatibleOperators();

// New way
$operators = $field->typeData->dataType->getCompatibleOperators();
```

#### Example 3: Checking if field is optionable
```php
// Old way
if ($field->type->isOptionable()) {
    // ...
}

// New way
if ($field->typeData->dataType->isChoiceField()) {
    // ...
}
```

### 4. Missing Methods in New System

The new `FieldTypeDefinitionInterface` is missing:
- `getAllowedValidationRules()`: Currently hardcoded in CustomFieldType enum
- These methods exist in `HasCommonFieldProperties` but with different defaults than the enum

### 5. Example New FieldType Class

Here's how a new FieldType class should look:

```php
<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\FieldTypes;

use Relaticle\CustomFields\Contracts\FieldTypeDefinitionInterface;
use Relaticle\CustomFields\Enums\FieldDataType;
use Relaticle\CustomFields\FieldTypes\Concerns\HasCommonFieldProperties;
use Relaticle\CustomFields\Filament\Integration\Forms\Components\TextComponent;
use Relaticle\CustomFields\Filament\Integration\Infolists\Fields\TextEntry;
use Relaticle\CustomFields\Filament\Integration\Tables\Columns\TextColumn;

class TextFieldType implements FieldTypeDefinitionInterface
{
    use HasCommonFieldProperties;

    public function getKey(): string
    {
        return 'text';
    }

    public function getLabel(): string
    {
        return 'Text';
    }

    public function getIcon(): string
    {
        return 'mdi-form-textbox';
    }

    public function getDataType(): FieldDataType
    {
        return FieldDataType::TEXT;
    }

    public function getFormComponentClass(): string
    {
        return TextComponent::class;
    }

    public function getTableColumnClass(): string
    {
        return TextColumn::class;
    }

    public function getInfolistEntryClass(): string
    {
        return TextEntry::class;
    }

    /**
     * Text fields are always searchable.
     */
    public function isSearchable(): bool
    {
        return true;
    }

    /**
     * Text fields are always encryptable.
     */
    public function isEncryptable(): bool
    {
        return true;
    }
}
```

### 6. Database Value Storage

The system uses different database columns based on field type:
- `text_value`: TEXT, TEXTAREA, RICH_EDITOR, MARKDOWN_EDITOR
- `string_value`: LINK, COLOR_PICKER
- `integer_value`: NUMBER, RADIO, SELECT
- `float_value`: CURRENCY
- `json_value`: CHECKBOX_LIST, TOGGLE_BUTTONS, TAGS_INPUT, MULTI_SELECT
- `boolean_value`: TOGGLE, CHECKBOX
- `date_value`: DATE
- `datetime_value`: DATE_TIME

This mapping needs to be maintained in the new system.

### 7. Validation Rules Migration

The validation rules are currently defined in `CustomFieldType::allowedValidationRules()`. Each new FieldType class needs to define its own allowed validation rules. This could be added to the interface or kept as a separate concern.

### 8. Registry Service Issues

The `FieldTypeRegistryService` has commented-out code in `buildCache()` method that needs to be reimplemented to support both built-in enum types (temporarily) and new FieldType classes.

### 9. Recommended Migration Order

1. **Fix FieldDataType enum** (critical bug)
2. **Create TextFieldType** (most common type)
3. **Update FieldTypeDefinitionInterface** (add missing methods)
4. **Create remaining FieldType classes** (in order of usage frequency)
5. **Update CustomField model** (change type to string)
6. **Update all services** (use new type system)
7. **Add deprecation to CustomFieldType** (backward compatibility)
8. **Remove CustomFieldType** (final cleanup)

### 10. Testing Considerations

- Each new FieldType class needs unit tests
- Integration tests for type conversion
- Migration tests to ensure data integrity
- Performance tests for the new registry system