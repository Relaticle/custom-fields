# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Custom Fields is a Laravel/Filament plugin that enables adding dynamic custom fields to Eloquent models without database
migrations. It provides 32+ field types with conditional visibility, multi-tenancy support, and full Filament
integration.

## Development Commands

### Setup

```bash
composer install
npm install
```

### Build and Development

```bash
# Watch files during development
npm run dev

# Build for production
npm run build

# Individual development tasks
npm run dev:styles   # Watch CSS changes
npm run dev:scripts  # Watch JS changes
```

### Testing

```bash
# Run full test suite (recommended before commits)
composer test

# Individual test commands
composer test:lint          # Laravel Pint code style check
composer test:refactor      # Rector dry-run refactoring check
composer test:types         # PHPStan static analysis (level 6)
composer test:type-coverage # Type coverage check (min 98%)
composer test:unit          # Pest unit tests (parallel execution)
composer test:arch          # Architecture tests

# Run tests with coverage report
composer test-coverage

# Fix code style issues
composer lint
```

### Code Quality

```bash
# Apply code style fixes
composer lint

# Apply automated refactoring
composer refactor
```

## Architecture Overview

### Core Structure

- **Service Provider**: `CustomFieldsServiceProvider` - Main package entry point
- **Plugin**: `CustomFieldsPlugin` - Filament integration layer
- **Models**: Located in `src/Models/` with repository pattern implementation
- **Field Types**: Located in `src/FieldTypes/` implementing common interfaces
- **Components**: Factory pattern for forms, tables, and infolists in `src/Filament/`

### Key Patterns

1. **Factory Pattern**: Component factories create form/table/infolist components dynamically
2. **Strategy Pattern**: Each field type implements common interfaces for consistent behavior
3. **Repository Pattern**: Models use scopes and query builders for data access
4. **Observer Pattern**: Model observers handle lifecycle events
5. **DTO Pattern**: Using Spatie Laravel Data for type-safe data transfer

### Testing Approach

- Uses Pest PHP with SQLite in-memory database
- Tests located in `tests/` directory
- Custom test traits and expectations available
- Parallel test execution enabled for speed
- Best Practices for testing located in `./docs/pestphp-testing-best-practices.md`

### Configuration

- Main config file: `config/custom-fields.php`
- Supports extensive customization of features, resources, and field types
- Multi-tenancy configuration options available

## Important Development Notes

1. **Type Safety**: Project maintains 98% type coverage minimum - ensure all new code is properly typed
2. **Code Style**: Follows PSR-12 via Laravel Pint - run `composer lint` before committing
3. **Static Analysis**: PHPStan level 6 - fix all reported issues before committing
4. **Field Type Development**: New field types should extend `BaseFieldType` and implement required interfaces
5. **Filament Components**: Use existing component factories when adding UI elements
6. **Database**: Custom fields are stored in JSON columns with proper casting and validation