# CustomFieldType to FieldTypes Migration Plan

## 1. Mapping Table: CustomFieldType Enum to New FieldType Classes

| CustomFieldType Enum | FieldDataType Category | New FieldType Class | Status |
|---------------------|------------------------|-------------------|---------|
| TEXT | TEXT | TextFieldType | ❌ Not Created |
| NUMBER | NUMERIC | NumberFieldType | ❌ Not Created |
| LINK | TEXT | LinkFieldType | ❌ Not Created |
| SELECT | SINGLE_CHOICE | SelectFieldType | ✅ Exists |
| CHECKBOX | BOOLEAN | CheckboxFieldType | ❌ Not Created |
| CHECKBOX_LIST | MULTI_CHOICE* | CheckboxListFieldType | ❌ Not Created |
| RADIO | SINGLE_CHOICE | RadioFieldType | ❌ Not Created |
| RICH_EDITOR | TEXT | RichEditorFieldType | ❌ Not Created |
| MARKDOWN_EDITOR | TEXT | MarkdownEditorFieldType | ❌ Not Created |
| TAGS_INPUT | MULTI_CHOICE* | TagsInputFieldType | ❌ Not Created |
| COLOR_PICKER | TEXT | ColorPickerFieldType | ❌ Not Created |
| TOGGLE | BOOLEAN | ToggleFieldType | ❌ Not Created |
| TOGGLE_BUTTONS | MULTI_CHOICE* | ToggleButtonsFieldType | ❌ Not Created |
| TEXTAREA | TEXT | TextareaFieldType | ❌ Not Created |
| CURRENCY | NUMERIC | CurrencyFieldType | ❌ Not Created |
| DATE | DATE | DateFieldType | ❌ Not Created |
| DATE_TIME | DATE_TIME** | DateTimeFieldType | ✅ Exists |
| MULTI_SELECT | MULTI_CHOICE* | MultiSelectFieldType | ❌ Not Created |

## 2. Inconsistencies Found

### Naming Inconsistency
- **CustomFieldType** uses: `SINGLE_OPTION` and `MULTI_OPTION`
- **FieldDataType** uses: `SINGLE_CHOICE` and `MULTI_CHOICE`
- **Action Required**: Update FieldDataType enum to fix `MULTI_CHOICE = 'multi_option'` to `MULTI_CHOICE = 'multi_choice'` for consistency

### Missing FieldDataType Categories
- **DATE_TIME** exists in FieldDataType but is not used in CustomFieldType mapping
- **STRING** exists in FieldDataType but is not used in CustomFieldType mapping
- **Action Required**: Clarify the purpose of STRING vs TEXT categories

## 3. CustomFieldType Methods to Replace

### Instance Methods
| Method | Purpose | Replacement in New System |
|--------|---------|--------------------------|
| `getCategory()` | Returns FieldDataType | `FieldTypeDefinitionInterface::getDataType()` |
| `isBoolean()` | Check if boolean type | `$fieldType->getDataType() === FieldDataType::BOOLEAN` |
| `isNumeric()` | Check if numeric type | `$fieldType->getDataType() === FieldDataType::NUMERIC` |
| `isTextBased()` | Check if text type | `$fieldType->getDataType() === FieldDataType::TEXT` |
| `isDateBased()` | Check if date type | `$fieldType->getDataType() === FieldDataType::DATE` |
| `isOptionable()` | Check if has options | `$fieldType->getDataType()->isChoiceField()` |
| `hasMultipleValues()` | Check if multi-value | `$fieldType->getDataType()->isMultiChoiceField()` |
| `getCompatibleOperators()` | Get allowed operators | `$fieldType->getDataType()->getCompatibleOperators()` |
| `getIcon()` | Get field icon | `FieldTypeDefinitionInterface::getIcon()` |
| `getLabel()` | Get field label | `FieldTypeDefinitionInterface::getLabel()` |
| `allowedValidationRules()` | Get validation rules | Need to implement in each FieldType class |

### Static Methods
| Method | Purpose | Replacement in New System |
|--------|---------|--------------------------|
| `options()` | Get all type options | `FieldTypeManager::toCollection()` |
| `optionsForSelect()` | Get select options | `FieldTypeRegistryService::getFieldTypeOptions()` |
| `icons()` | Get all icons | Iterate through `FieldTypeManager::toCollection()` |
| `optionables()` | Get optionable types | Filter by `isChoiceField()` |
| `encryptables()` | Get encryptable types | Filter by `isEncryptable()` |
| `searchables()` | Get searchable types | Filter by `isSearchable()` |
| `filterable()` | Get filterable types | Filter by `isFilterable()` |
| `values()` | Get all enum values | `FieldTypeManager::toCollection()->keys()` |
| `byCategory()` | Get by category | Filter collection by `getDataType()` |
| `isBuiltInType()` | Check if built-in | `FieldTypeRegistryService::isBuiltInFieldType()` |

## 4. Places Where CustomFieldType is Used

### Critical Files to Update
1. **Models/CustomField.php**
   - Property: `@property CustomFieldType|string $type`
   - Method: `getValueColumn()` uses `$this->type`
   - Cast needed: Change from enum to string

2. **Services/Visibility/FrontendVisibilityService.php**
   - Uses: `$targetField->type->hasMultipleValues()`
   - Change to: `$targetField->typeData->dataType->isMultiChoiceField()`

3. **Support/DatabaseFieldConstraints.php**
   - Uses CustomFieldType extensively for validation rules
   - Need to refactor to use FieldTypeManager

4. **Filament/Management/Forms/Components/VisibilityComponent.php**
   - Uses `$targetField->type->isOptionable()`
   - Change to: `$targetField->typeData->dataType->isChoiceField()`

5. **Models/Concerns/HasFieldType.php**
   - Already updated to use `typeData->dataType`

## 5. New FieldType Classes to Create

Each new FieldType class needs to:
1. Implement `FieldTypeDefinitionInterface`
2. Use `HasCommonFieldProperties` trait
3. Define:
   - `getKey()`: string identifier
   - `getLabel()`: human-readable name
   - `getIcon()`: MDI icon name
   - `getDataType()`: FieldDataType enum
   - `getFormComponentClass()`: Form component class
   - `getTableColumnClass()`: Table column class
   - `getInfolistEntryClass()`: Infolist entry class
   - `getTableFilterClass()`: Optional filter class
   - `getAllowedValidationRules()`: Array of validation rules (NEW METHOD NEEDED)

## 6. Migration Steps

### Phase 1: Fix Inconsistencies
1. Update FieldDataType enum to use consistent naming
2. Add `isOptionable()` method to FieldDataType (alias for `isChoiceField()`)

### Phase 2: Create Missing FieldType Classes
1. Create all 16 missing FieldType classes
2. Register them in FieldTypeManager::DEFAULT_FIELD_TYPES

### Phase 3: Update FieldTypeDefinitionInterface
1. Add `getAllowedValidationRules()` method
2. Add `isEncryptable()` method (already in HasCommonFieldProperties)
3. Add `isSearchable()` method (already in HasCommonFieldProperties)
4. Add `isFilterable()` method (already in HasCommonFieldProperties)

### Phase 4: Update CustomField Model
1. Change type property from CustomFieldType enum to string
2. Update all references to use typeData instead of type enum

### Phase 5: Update Services
1. Update DatabaseFieldConstraints to use FieldTypeManager
2. Update FrontendVisibilityService to use typeData
3. Update FieldTypeRegistryService to handle built-in types

### Phase 6: Remove CustomFieldType Enum
1. Search and replace all remaining usages
2. Remove the enum file
3. Update tests

## 7. Code That Needs Special Attention

### Validation Rules Mapping
The `allowedValidationRules()` method in CustomFieldType is complex and needs to be properly migrated to each FieldType class.

### Database Constraints
The DatabaseFieldConstraints class has hardcoded mappings between CustomFieldType and database columns that need to be refactored.

### Caching
CustomFieldType has extensive caching that needs to be replicated in the new system.

### Backward Compatibility
Consider keeping CustomFieldType enum temporarily with deprecation notices to ease migration.