# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Relaticle Custom Fields** is a Laravel package that provides user-defined custom fields functionality for Laravel Filament applications. The package enables dynamic field creation, management, and integration with Filament forms, tables, and infolists.

## Essential Commands

### Composer (PHP)
```bash
composer test              # Run Pest tests
composer test-coverage     # Run tests with coverage  
composer analyse           # Run PHPStan static analysis
composer format           # Format code with Laravel Pint
```

### NPM (Frontend Assets)
```bash
npm run dev               # Development build with watch mode
npm run build            # Production build
npm run dev:styles       # Watch CSS only
npm run dev:scripts      # Watch JS only
```

### Laravel Artisan
```bash
php artisan custom-fields:install    # Install package
php artisan custom-fields:upgrade    # Upgrade existing installation
php artisan custom-fields:optimize   # Optimize database
```

## Core Architecture

### Technology Stack
- **PHP 8.3+** with strict typing
- **Laravel Filament 4.0+** for UI components
- **Spatie Laravel Data 4.12+** for data objects
- **Pest 3.0** for testing
- **TailwindCSS 4.1+** and ESBuild for frontend

### Key Directories
- `src/Models/` - Core models (CustomField, CustomFieldValue, CustomFieldSection, CustomFieldOption)
- `src/Services/` - Business logic services including the recently refactored VisibilityService
- `src/Integration/` - Filament integrations (Forms, Tables, Infolists, Actions)
- `src/Data/` - Spatie Data objects for type-safe data structures
- `src/Enums/` - Clean enum-based configurations (recently simplified)

### Recently Refactored Visibility System ⭐

**Major Achievement**: The conditional visibility system was completely rewritten, removing 1,230+ lines of complex legacy code and simplifying from 8 files to 5 files with 8 essential operators.

**Key Components**:
- `src/Enums/Mode.php` - 3 visibility modes (always_visible, show_when, hide_when)
- `src/Enums/Logic.php` - AND/OR logic for conditions
- `src/Enums/Operator.php` - 8 streamlined operators
- `src/Data/VisibilityData.php` - Clean data structure
- `src/Services/VisibilityService.php` - Single service handling all visibility logic

## Configuration

Main configuration files:
- `config/custom-fields.php` - Primary package configuration
- `config/data.php` - Additional data configuration

Key configuration options include entity resources, lookup resources, tenant awareness, field types, and database settings.

## Testing Strategy

- **Framework**: Pest 3.0 with PHPStan Level 3 static analysis
- **Test Structure**: Feature tests in `tests/Feature/`, Unit tests in `tests/Unit/`
- **Key Tests**: Visibility system tests, Filament integration tests, basic functionality tests
- **Coverage**: Run `composer test-coverage` for coverage reports

## Development Workflow

### Code Quality
- **Strict typing** throughout (PHP 8.3+ features)
- **Laravel Pint** for consistent formatting
- **PHPStan Level 3** for static analysis
- **Enum-based configurations** for type safety

### Field Types Supported
Text, Textarea, Number, Date, DateTime, Select, Multi-select, Radio, Checkboxes, Toggle, Tags, Color Picker, Currency, Rich Editor, Markdown Editor, File uploads, Links

### Filament Integration Points
- **Forms**: Dynamic form building with reactive fields (`src/Integration/Forms/`)
- **Tables**: Custom columns with search/filter support (`src/Integration/Tables/`)
- **Infolists**: Display components for viewing data (`src/Integration/Infolists/`)
- **Import/Export**: Excel/CSV support (`src/Integration/Actions/`)

## Multi-Tenancy Support

The package includes optional tenant awareness with:
- Configurable foreign keys
- Automatic tenant scoping for all models
- Middleware for tenant context management (`src/Http/Middleware/SetTenantContextMiddleware.php`)

## Performance Considerations

- Smart dependency management for visibility calculations
- Optimized database queries with proper scoping
- JavaScript-based reactivity where appropriate
- Field caching optimization

## Important Notes

- **PHP 8.3+ Required** - Uses strict typing throughout
- **Laravel Filament 4.0+ Required** - Core dependency
- **Database JSON Support Required** - For field settings storage
- **Clean Architecture** - Follow the established service/data/enum structure
- **Type Safety First** - Maintain strict typing and enum-based configurations
- **Test-Driven Development** - Maintain high test coverage standards

## Documentation

Comprehensive documentation available in `docs/`:
- `clean-visibility-system.md` - Major refactoring documentation
- `conditional-visibility-usage.md` - Usage guide
- `validation-system.md` - Validation documentation
- `tenant-context.md` - Multi-tenancy guide

## Linting and Code Style

Always run linting and formatting before commits:
```bash
composer format    # Laravel Pint formatting
composer analyse   # PHPStan static analysis
```

The codebase follows strict PSR standards with additional Laravel-specific conventions.