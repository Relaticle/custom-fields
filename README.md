# Custom Fields - The 'Just One More Field' Solution

[![Latest Version](https://img.shields.io/packagist/v/relaticle/custom-fields.svg?style=for-the-badge)](https://packagist.org/packages/relaticle/custom-fields)
[![License](https://img.shields.io/packagist/l/relaticle/custom-fields.svg?style=for-the-badge)](https://packagist.org/packages/relaticle/custom-fields)
[![PHP Version](https://img.shields.io/packagist/php-v/relaticle/custom-fields.svg?style=for-the-badge)](https://packagist.org/packages/relaticle/custom-fields)

A powerful Laravel/Filament plugin for adding dynamic custom fields to any Eloquent model without database migrations.

## ✨ Features

- **32+ Field Types** - Text, number, date, select, rich editor, and more
- **Conditional Visibility** - Show/hide fields based on other field values
- **Multi-tenancy** - Complete tenant isolation and context management
- **Filament Integration** - Forms, tables, infolists, and admin interface
- **Import/Export** - Built-in CSV capabilities
- **Security** - Optional field encryption and type-safe validation
- **Extensible** - Custom field types and automatic discovery (coming soon)

## 🔧 Requirements

- PHP 8.3+
- Laravel via Filament 3.0+

## 🚀 Quick Start

### Installation

```bash
composer require relaticle/custom-fields
php artisan vendor:publish --tag="custom-fields-migrations"
php artisan migrate
```

### Integrating Custom Fields Plugin into a panel

```php
use Relaticle\CustomFields\CustomFieldsPlugin;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... other panel configurations
        ->plugins([
            CustomFieldsPlugin::make(),
        ]);
}
```

### Setting Up the Model

Add the trait to your model:

```php
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;

class Post extends Model implements HasCustomFields
{
    use UsesCustomFields;
}
```

Add to your Filament form:

```php
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

public function form(Form $form): Form
{
    return $form->schema([
        // Your existing form fields...
        CustomFieldsComponent::make()->columns(1),
    ]);
}
```

## 📚 Documentation

**Full documentation and examples:** https://custom-fields.relaticle.com/

- [Installation Guide](https://custom-fields.relaticle.com/installation)
- [Quickstart](https://custom-fields.relaticle.com/quickstart)
- [Configuration](https://custom-fields.relaticle.com/essentials/configuration)
- [Authorization](https://custom-fields.relaticle.com/essentials/authorization)
- [Preset Custom Fields](https://custom-fields.relaticle.com/essentials/preset-custom-fields)

## 🤝 Contributing

Contributions welcome! Please see our [contributing guide](https://custom-fields.relaticle.com/contributing).
