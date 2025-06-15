# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel PHP package called "**custom-fields**" that provides user-defined custom fields functionality for Filament admin panels. The package enables dynamic custom fields with comprehensive validation, multi-tenancy support, and extensive Filament integration.

**Key Features:**
- 12+ field types (text, number, date, select, etc.)
- Multi-tenant architecture with automatic tenant scoping
- Advanced validation system with database constraint enforcement
- Import/export functionality with configurable columns
- Comprehensive Filament integration (forms, tables, infolists)
- Large number handling with safe value conversion
- Encryption support for sensitive fields

## Development Commands

### PHP Development
```bash
# Run PHPStan static analysis
composer analyse

# Run Pest PHP tests  
composer test

# Run tests with coverage
composer test-coverage

# Format code with Laravel Pint
composer format
```

### Filament V4 Migration Status
**✅ MIGRATED TO FILAMENT V4** - This package has been successfully upgraded to Filament V4:
- **Unified Schema Components**: All components now use `Filament\Schemas\Components\*`
- **Unified Actions**: Actions use `Filament\Actions` namespace
- **Immediate Filtering**: Configured `deferFilters(false)` to maintain V3 behavior
- **Beta Stability**: Composer configured for Filament V4 beta packages

### Frontend Development
```bash
# Development build with watch mode
npm run dev

# Production build
npm run build

# Build styles only
npm run build:styles

# Build scripts only  
npm run build:scripts
```

### Package Commands
```bash
# Install package with stub publishing
php artisan custom-fields:install

# Upgrade package between versions
php artisan custom-fields:upgrade

# Optimize database performance
php artisan custom-fields:optimize-database
```

## Architecture Overview

### Core Services
- **ValidationService** (`src/Services/ValidationService.php`) - Central validation with rule merging and caching
- **TenantContextService** (`src/Services/TenantContextService.php`) - Multi-tenant context management for web and queue jobs
- **ValueResolver** (`src/Services/ValueResolver/`) - Safe value conversion and type handling

### Multi-Tenancy System
The package implements sophisticated tenant awareness using Laravel Context:
- **Automatic tenant scoping** for all database queries
- **Queue job tenant preservation** using `TenantAware` trait
- **Middleware-based context setting** for web requests
- **Manual tenant context management** for complex scenarios

Configuration in `config/custom-fields.php`:
```php
'tenant_aware' => true,
'column_names' => [
    'tenant_foreign_key' => 'tenant_id',
],
```

### Database Architecture
- **custom_field_sections** - Organizational sections for fields
- **custom_fields** - Field definitions with type, validation, and settings
- **custom_field_values** - Polymorphic storage for field values
- **custom_field_options** - Select/multi-select field options

### Field Types & Validation
Field types are defined in `src/Enums/FieldType.php` with comprehensive validation:
- **Database constraint enforcement** via `DatabaseFieldConstraints`
- **User-defined rule precedence** - stricter user rules always take precedence
- **Type-specific validation** for arrays, numbers, dates, etc.
- **Encryption support** with adjusted constraints for overhead

### Filament Integration
Extensive integration across Filament components:
- **Form components** in `src/Filament/Forms/Components/`
- **Table columns** in `src/Filament/Tables/Columns/`
- **Filters** in `src/Filament/Tables/Filters/`
- **Export/Import** configurators in `src/Filament/Exports/` and `src/Filament/Imports/`

## Testing Framework

**Pest PHP** with comprehensive coverage:
- **Feature tests** - Filament integration, resource behavior
- **Unit tests** - Models, services, validation, enums
- **Database** - SQLite in-memory for clean isolation
- **Factories** - Available for all models in `database/factories/`

Test structure:
```
tests/
├── Feature/          # Integration tests with Filament
├── Unit/            # Isolated unit tests  
├── Helpers.php      # Test utilities
└── Pest.php         # Pest configuration
```

## Code Conventions

- **Strict typing** - All files use `declare(strict_types=1)`
- **Modern PHP 8.2+** features and patterns
- **PSR standards** with Laravel Pint formatting
- **Comprehensive docblocks** for complex methods
- **Consistent naming** following Laravel conventions

### Important Patterns
- Use `TenantAware` trait for queue jobs that work with custom fields
- Always use `TenantContextService` for manual tenant context management
- Leverage `ValidationService` for field validation rule generation
- Use model factories in tests for consistent data creation

## Configuration

Main configuration in `config/custom-fields.php`:
- **Feature flags** (encryption, table toggles)
- **Field type behavior** (date formats, native inputs)
- **Resource configuration** (navigation, clustering)
- **Entity/lookup resource filtering**
- **Multi-tenancy settings**
- **Custom table/column names**

## Documentation

Technical documentation in `docs/`:
- **`tenant-context.md`** - Comprehensive multi-tenancy implementation guide
- **`validation-system.md`** - Validation architecture and rule precedence
- **`large-number-handling.md`** - MySQL BIGINT handling strategies

## Package Structure

```
src/
├── Commands/           # Artisan commands for management
├── Contracts/          # Interfaces for extensibility
├── Data/              # DTOs using Spatie Laravel Data
├── Enums/             # Field types, validation rules, etc.
├── Filament/          # Comprehensive Filament integration
├── Models/            # Eloquent models with concerns
├── Services/          # Business logic and utilities
└── Support/           # Helper classes and utilities
```

## Development Notes

- Package uses **beta stability** for cutting-edge features
- **Spatie Laravel Package Tools** for package structure
- **esbuild** for JavaScript compilation with watch mode
- **Tailwind CSS 4.x** for styling with PostCSS processing
- **Orchestra Testbench** for Laravel package testing environment