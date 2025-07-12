# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Custom Fields** is a powerful Laravel/Filament plugin package that enables adding dynamic custom fields to any Eloquent model without database migrations.

### Key Features
- **32+ Field Types** - Text, number, date, select, rich editor, and more
- **Conditional Visibility** - Show/hide fields based on other field values
- **Multi-tenancy** - Complete tenant isolation and context management
- **Filament Integration** - Forms, tables, infolists, and admin interface
- **Import/Export** - Built-in CSV capabilities
- **Security** - Optional field encryption and type-safe validation
- **Extensible** - Custom field types and automatic discovery (coming soon)

### Requirements
- PHP 8.3+
- Laravel 11.0+
- Filament 4.0+

### Package Details
- **Package Name**: `relaticle/custom-fields`
- **License**: AGPL-3.0
- **Documentation**: https://custom-fields.relaticle.com/

## Development Commands

### Testing

**Complete Test Suite**
- `composer test` - Run complete test suite (includes linting, static analysis, and tests)
  - Runs in sequence: lint check → refactor check → PHPStan → type coverage → tests

**Individual Test Commands**
- `composer test:pest` - Run all tests in parallel
- `composer test:arch` - Run architecture tests only
- `composer test:types` - Run PHPStan static analysis (Level 5)
- `composer test:type-coverage` - Check type coverage (must be ≥98%)
- `composer test:lint` - Check code style (dry run)
- `composer test:refactor` - Check Rector rules (dry run)
- `composer test-coverage` - Run tests with code coverage report

**Running Specific Tests**
```bash
# Run a specific test file
vendor/bin/pest tests/path/to/test.php

# Run tests matching a pattern
vendor/bin/pest --filter="test name"

# Run tests in parallel (faster)
vendor/bin/pest --parallel

# Run only changed tests
vendor/bin/pest --dirty

# Re-run failed tests
vendor/bin/pest --retry

# Profile slow tests
vendor/bin/pest --profile
```

### Code Quality

**Linting & Formatting**
- `composer lint` - Auto-fix code style with Laravel Pint and apply Rector rules
  - Runs both Rector and Pint in parallel for better performance
- `rector` - Apply automated refactoring rules
- `rector --dry-run` - Preview Rector changes without applying
- `pint` - Format code according to Laravel standards
- `pint --test` - Check code style without making changes

**Static Analysis**
- `phpstan analyse` - Run PHPStan analysis (configured at Level 5)
- PHPStan is configured for parallel processing for improved performance

### Frontend Build

**Development**
- `npm run dev` - Watch and build CSS/JS for development
  - Runs CSS and JS builds concurrently with live reload
  - `npm run dev:styles` - Watch CSS only
  - `npm run dev:scripts` - Watch JS only

**Production**
- `npm run build` - Build CSS/JS for production
  - `npm run build:styles` - Build CSS with PostCSS optimizations
  - `npm run build:scripts` - Build JS with esbuild minification

**Frontend Stack**
- CSS: Tailwind CSS 4.x with PostCSS
- JS: esbuild for fast bundling
- Uses PostCSS nesting and prefix selectors for component isolation

## Architecture Overview

### Core Design Patterns

1. **Service Provider Architecture**: Field types, imports, and validation are registered via service providers
2. **Factory Pattern**: Component creation uses factories (`FieldComponentFactory`, `ColumnFactory`, etc.)
3. **Builder Pattern**: Complex UI construction via builders (`FormBuilder`, `InfolistBuilder`, `TableBuilder`)
4. **Data Transfer Objects**: Type-safe data structures using Spatie Laravel Data (`CustomFieldData`,
   `ValidationRuleData`, etc.)
5. **Repository/Service Pattern**: Business logic in services (`TenantContextService`, `ValidationService`,
   `VisibilityService`)

### Directory Structure

**Source Code (`src/`)**
- `Models/` - Eloquent models and traits
  - `CustomField` - Main field definition model
  - `CustomFieldValue` - Stores field values
  - `CustomFieldSection` - Groups fields into sections
  - `CustomFieldOption` - Options for select/radio fields
  - `Concerns/UsesCustomFields` - Trait for models using custom fields
  - `Contracts/HasCustomFields` - Interface for custom field models
- `Forms/` - Form components and builders for Filament forms
- `Tables/` - Table columns and filters for Filament tables
- `Infolists/` - Infolist components for read-only displays
- `Services/` - Business logic services
  - `TenantContextService` - Multi-tenancy handling
  - `ValidationService` - Dynamic validation rules
  - `VisibilityService` - Conditional field visibility
- `FieldTypes/` - Field type definitions and registration
- `Data/` - DTO classes for type safety using Spatie Laravel Data
- `Filament/` - Filament admin panel resources and pages
- `Facades/` - Laravel facades for simplified API access
- `Enums/` - Type-safe enumerations

**Database (`database/`)**
- `factories/` - Model factories for testing
- `migrations/` - Database migration files

**Resources (`resources/`)**
- `css/` - Tailwind CSS styles
- `js/` - JavaScript components
- `dist/` - Compiled assets (git ignored)
- `lang/` - Translation files
- `views/` - Blade templates for custom components

### Testing Approach

Tests use Pest PHP with custom expectations:

- `toHaveCustomFieldValue()` - Assert field values
- `toHaveValidationError()` - Check validation errors
- `toHaveFieldType()` - Verify field types
- `toHaveVisibilityCondition()` - Test conditional visibility

Test fixtures include `Post` and `User` models with pre-configured resources.

**Environment Setup**
- Tests run with SQLite in-memory database
- Automatic migration of test database
- Parallel execution enabled by default

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

## Installation

```bash
# Install the package
composer require relaticle/custom-fields

# Publish and run migrations
php artisan vendor:publish --tag="custom-fields-migrations"
php artisan migrate
```

## Quick Start

### 1. Add Plugin to Filament Panel

```php
use Relaticle\CustomFields\CustomFieldsPlugin;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            CustomFieldsPlugin::make(),
        ]);
}
```

### 2. Configure Your Model

```php
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;

class Post extends Model implements HasCustomFields
{
    use UsesCustomFields;
}
```

### 3. Add to Filament Resource

```php
use Filament\Schemas\Schema;
use Relaticle\CustomFields\Facades\CustomFields;

public function form(Schema $schema): Form
{
    return $schema->components([
        // Your existing form fields...
        
        CustomFields::form()->forModel($schema->getRecord())->build()
    ]);
}
```

## Testing Best Practices

The project follows comprehensive testing practices documented in `.claude/docs/pestphp-testing-best-practices.md`. Key principles:

### Test Philosophy
- **Feature Tests First** (80-90% of tests) - Test behavior, not implementation
- **Avoid Over-Mocking** - Use real implementations when possible
- **Follow AAA Pattern** - Arrange, Act, Assert
- **Use Descriptive Names** - Tests should read like specifications

### Test Structure
```php
it('creates a custom field with validation', function () {
    // Arrange
    $user = User::factory()->create();
    
    // Act
    $response = $this->actingAs($user)
        ->post('/custom-fields', [
            'name' => 'Company Size',
            'type' => 'number',
            'validation_rules' => ['required', 'min:1']
        ]);
    
    // Assert
    $response->assertCreated();
    $this->assertDatabaseHas('custom_fields', [
        'name' => 'Company Size',
        'type' => 'number'
    ]);
});
```

### Architecture Tests
The project includes architecture tests to enforce coding standards:
- Controllers don't use Models directly
- Services have proper suffixes
- No debugging functions in production code
- DTOs are immutable

## Contributing

See the full contributing guide at https://custom-fields.relaticle.com/contributing

## Common Development Tasks

### Debugging Custom Fields
- Use `dd()` or `ray()` to inspect field values and configurations
- Check `storage/logs/laravel.log` for validation and visibility condition errors
- Enable query logging to debug performance issues with field loading
- Test field behavior in isolation using Pest tests

### Creating New Field Types
1. Create a new class in `src/FieldTypes/` implementing `FieldTypeDefinitionInterface`
2. Register the field type in a service provider
3. Add corresponding form component, table column, and infolist entry methods
4. Create tests for the new field type behavior

## Resources

- **Documentation**: https://custom-fields.relaticle.com/
- **Installation Guide**: https://custom-fields.relaticle.com/installation
- **Quickstart**: https://custom-fields.relaticle.com/quickstart
- **Configuration**: https://custom-fields.relaticle.com/essentials/configuration
- **Authorization**: https://custom-fields.relaticle.com/essentials/authorization
- **Testing Guide**: `.claude/docs/pestphp-testing-best-practices.md`