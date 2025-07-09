# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel/Filament plugin package called "Custom Fields" that allows adding dynamic custom fields to any
Eloquent model without database migrations. It provides 32+ field types with conditional visibility, multi-tenancy
support, and full Filament integration.

## Development Commands

### Testing

- `composer test` - Run complete test suite (includes linting, static analysis, and tests)
- `composer test:unit` - Run unit tests only
- `composer test:arch` - Run architecture tests
- `composer test:types` - Run PHPStan static analysis (Level 6)
- `composer test:type-coverage` - Check type coverage (must be ≥98%)

To run a specific test:

```bash
vendor/bin/pest tests/path/to/test.php --filter="test name"
```

### Code Quality

- `composer lint` - Format code with Laravel Pint
- `composer refactor` - Apply Rector refactoring rules
- `composer refactor:dry` - Preview Rector changes without applying

### Frontend Build

- `npm run dev` - Watch and build CSS/JS for development
- `npm run build` - Build CSS/JS for production

## Architecture Overview

### Core Design Patterns

1. **Service Provider Architecture**: Field types, imports, and validation are registered via service providers
2. **Factory Pattern**: Component creation uses factories (`FieldComponentFactory`, `ColumnFactory`, etc.)
3. **Builder Pattern**: Complex UI construction via builders (`FormBuilder`, `InfolistBuilder`, `TableBuilder`)
4. **Data Transfer Objects**: Type-safe data structures using Spatie Laravel Data (`CustomFieldData`,
   `ValidationRuleData`, etc.)
5. **Repository/Service Pattern**: Business logic in services (`TenantContextService`, `ValidationService`,
   `VisibilityService`)

### Key Directories

- `src/Models/` - Eloquent models and traits (`CustomField`, `CustomFieldValue`, `UsesCustomFields`)
- `src/Forms/` - Form components and builders for Filament forms
- `src/Tables/` - Table columns and filters for Filament tables
- `src/Infolists/` - Infolist components for read-only displays
- `src/Services/` - Business logic services
- `src/FieldTypes/` - Field type definitions and registration
- `src/Data/` - DTO classes for type safety
- `src/Filament/` - Filament admin panel resources and pages

### Testing Approach

Tests use Pest PHP with custom expectations:

- `toHaveCustomFieldValue()` - Assert field values
- `toHaveValidationError()` - Check validation errors
- `toHaveFieldType()` - Verify field types
- `toHaveVisibilityCondition()` - Test conditional visibility

Test fixtures include `Post` and `User` models with pre-configured resources.

### Multi-tenancy

The package supports complete tenant isolation via `TenantContextService`. Custom fields are automatically scoped to the
current tenant when multi-tenancy is enabled.

### Field Type System

Field types are registered via `FieldTypeRegistry` and must implement `FieldTypeDefinitionInterface`. Each field type
provides:

- Form component creation
- Table column creation
- Infolist entry creation
- Validation rules
- Value transformation

### Validation System

Validation uses Laravel's validation rules with additional custom rules:

- Rules are stored as `ValidationRuleData` DTOs
- Applied dynamically based on field configuration
- Support for conditional validation based on visibility

### Best Practices 

./.claude/docs/pestphp-testing-best-practices.md