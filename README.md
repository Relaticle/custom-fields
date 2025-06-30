# Custom Fields

[![Latest Version](https://img.shields.io/packagist/v/relaticle/custom-fields.svg?style=flat-square)](https://packagist.org/packages/relaticle/custom-fields)
[![License](https://img.shields.io/packagist/l/relaticle/custom-fields.svg?style=flat-square)](https://packagist.org/packages/relaticle/custom-fields)
[![PHP Version](https://img.shields.io/packagist/php-v/relaticle/custom-fields.svg?style=flat-square)](https://packagist.org/packages/relaticle/custom-fields)

A powerful Laravel/Filament plugin for adding dynamic custom fields to any Eloquent model without database migrations.

## ✨ Features

- **32+ Field Types** - Text, number, date, select, rich editor, and more
- **Conditional Visibility** - Show/hide fields based on other field values
- **Multi-tenancy** - Complete tenant isolation and context management
- **Filament Integration** - Forms, tables, infolists, and admin interface
- **Import/Export** - Built-in CSV capabilities
- **Security** - Optional field encryption and type-safe validation
- **Extensible** - Custom field types and automatic discovery (coming soon)

## 🚀 Quick Start

### Installation

```bash
composer require relaticle/custom-fields
php artisan vendor:publish --tag="custom-fields-migrations"
php artisan migrate
```

### Basic Usage

Add the trait to your model:

```php
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;

class Post extends Model
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
        // Your existing fields...
        CustomFieldsComponent::make('custom_fields')
            ->model(Post::class),
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

## 🔧 Requirements

- PHP 8.3+
- Laravel via Filament 3.0+

## 📝 License

Apache 2.0. See [LICENSE](LICENSE) for details.

## 🤝 Contributing

Contributions welcome! Please see our [contributing guide](https://custom-fields.relaticle.com/contributing).