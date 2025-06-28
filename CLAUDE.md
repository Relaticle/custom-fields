# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

FlexFields (formerly custom-fields) is a Laravel/Filament package that provides a dynamic custom fields system, allowing developers to add custom fields to Eloquent models without database migrations. It supports 32+ field types with features like conditional visibility, multi-tenancy, import/export, and field encryption.

## Development Commands

### Testing
```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run specific test files or directories
vendor/bin/pest tests/Feature/Admin
vendor/bin/pest --filter="test name pattern"
```

### Code Quality
```bash
# Format code (Laravel Pint)
composer format

# Check formatting without fixing
composer format-check

# Static analysis (PHPStan level 6)
composer analyse

# Code insights
composer insights

# Check for refactoring opportunities
composer refactor

# Run all code quality checks
composer code-audit
```

### Frontend Build
```bash
# Watch and build for development
npm run dev

# Build for production
npm run build
```

### Package Development
```bash
# Install dependencies
composer install
npm install

# Publish migrations for testing
php artisan vendor:publish --tag="custom-fields-migrations"
php artisan migrate
```

## Architecture & Key Concepts

### Package Structure
The package follows Laravel package development best practices with clear separation of concerns:

- **Service Layer** (`src/Services/`): Business logic for validation, visibility rules, field types, etc.
- **Data Transfer Objects** (`src/Data/`): Type-safe data structures using spatie/laravel-data
- **Filament Integration** (`src/Filament/`): Components, forms, tables, and admin pages
- **Models & Contracts** (`src/Models/`, `src/Contracts/`): Core domain models and interfaces
- **Field Type System** (`src/Enums/FieldType.php`, `src/Services/FieldTypes/`): Extensible field type architecture

### Key Architectural Patterns

1. **Field Type Registry**: Field types are registered through a discovery system that scans for classes implementing `FieldTypeDefinitionInterface`. Custom field types can be added via configuration.

2. **Tenant Isolation**: When multi-tenancy is enabled, all queries are automatically scoped to the current tenant using global scopes and the `tenant_id` column.

3. **Value Storage**: Field values are stored in a polymorphic `custom_field_values` table with JSON data column, allowing flexible storage for any field type.

4. **Form Component Integration**: The package provides a single `CustomFieldsComponent` that dynamically renders all custom fields for a model in Filament forms.

### Database Schema
```
custom_field_sections -> custom_fields -> custom_field_values
                                      `-> custom_field_options
```

### Testing Architecture
- Uses Pest PHP with feature and unit test organization
- Test fixtures include complete Filament panel setup with test models (Post, User)
- In-memory SQLite database for fast, isolated tests
- Comprehensive feature tests for all Filament pages and resources

## Working with the Codebase

### Adding New Field Types
1. Create a class implementing `FieldTypeDefinitionInterface`
2. Add to config `field_type_discovery.classes` or place in a scanned directory
3. Implement required methods: `component()`, `tableColumn()`, `rules()`, etc.

### Testing Patterns
```php
// Feature test for Filament pages
it('can access custom fields page', function () {
    Livewire::test(CustomFieldsPage::class)
        ->assertSuccessful()
        ->assertSee('Custom Fields');
});

// Test with database interactions
it('creates custom field', function () {
    $field = CustomField::factory()->create();
    expect($field)->toBeInstanceOf(CustomField::class);
});
```

### Common Development Tasks

#### Running Tests for a Specific Feature
```bash
# Test a specific feature area
composer test -- --filter="CustomFieldsPage"

# Test with verbose output
composer test -- --verbose
```

#### Debugging Field Type Issues
1. Check field type registration in `config/custom-fields.php`
2. Verify field type class implements all required interfaces
3. Use `php artisan tinker` to inspect field type registry
4. Check `storage/logs/laravel.log` for registration errors

#### Working with Migrations
```bash
# Create a custom fields migration
php artisan make:custom-fields-migration add_new_field_type

# Run migrations with specific path
php artisan migrate --path=database/custom-fields
```

## Task Master AI Integration

This project uses Task Master AI for task management. Key commands:

```bash
# View current tasks
task-master list
task-master next

# Update task status
task-master set-status --id=<id> --status=done

# Add implementation notes
task-master update-subtask --id=<id> --prompt="implementation details"
```

See the full Task Master guide in the existing CLAUDE.md context above.

## Important Configuration

### Multi-tenancy
Enable in `config/custom-fields.php` before running migrations:
```php
'tenant_aware' => true,
'column_names' => [
    'tenant_foreign_key' => 'tenant_id',
],
```

### Field Type Configuration
Configure specific field types in `config/custom-fields.php`:
```php
'field_types_configuration' => [
    'date' => [
        'native' => false,
        'format' => 'Y-m-d',
    ],
],
```

### Resource Permissions
Control which resources can have custom fields:
```php
'allowed_entity_resources' => [
    App\Filament\Resources\UserResource::class,
],
```