# FlexFields v2.0 Component Examples

## Core Interfaces

### TypedComponentInterface
```php
<?php

namespace FlexFields\Contracts;

use FlexFields\Models\CustomField;

interface TypedComponentInterface
{
    /**
     * Create a new instance of the component
     */
    public static function make(CustomField $field): static;
}
```

### TypedFormComponentInterface
```php
<?php

namespace FlexFields\Contracts;

use Filament\Forms\Components\Component;

interface TypedFormComponentInterface extends TypedComponentInterface
{
    /**
     * Get the Filament form component
     */
    public function getFormComponent(): Component;
}
```

### TypedColumnInterface
```php
<?php

namespace FlexFields\Contracts;

use Filament\Tables\Columns\Column;

interface TypedColumnInterface extends TypedComponentInterface
{
    /**
     * Get the Filament table column
     */
    public function getTableColumn(): Column;
}
```

### TypedInfolistInterface
```php
<?php

namespace FlexFields\Contracts;

use Filament\Infolists\Components\Entry;

interface TypedInfolistInterface extends TypedComponentInterface
{
    /**
     * Get the Filament infolist entry
     */
    public function getInfolistEntry(): Entry;
}
```

## Enhanced CustomFieldType Enum

```php
<?php

namespace FlexFields\Enums;

use FlexFields\Components\Forms\{
    TextInputComponent,
    NumberComponent,
    SelectComponent,
    DateComponent,
    DateTimeComponent,
    ToggleComponent,
    CheckboxComponent,
    RadioComponent,
    MultiSelectComponent,
    CheckboxListComponent,
    TagsInputComponent,
    ColorPickerComponent,
    CurrencyComponent,
    LinkComponent,
    TextareaFieldComponent,
    RichEditorComponent,
    MarkdownEditorComponent,
    ToggleButtonsComponent
};
use FlexFields\Contracts\TypedComponentInterface;
use FlexFields\Models\CustomField;
use FlexFields\Services\FlexFieldsCacheService;
use Filament\Forms\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Infolists\Components\Entry;

enum CustomFieldType: string
{
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case NUMBER = 'number';
    case CURRENCY = 'currency';
    case DATE = 'date';
    case DATE_TIME = 'date_time';
    case TOGGLE = 'toggle';
    case CHECKBOX = 'checkbox';
    case SELECT = 'select';
    case RADIO = 'radio';
    case MULTI_SELECT = 'multi_select';
    case CHECKBOX_LIST = 'checkbox_list';
    case TAGS_INPUT = 'tags_input';
    case COLOR_PICKER = 'color_picker';
    case LINK = 'link';
    case RICH_EDITOR = 'rich_editor';
    case MARKDOWN_EDITOR = 'markdown_editor';
    case TOGGLE_BUTTONS = 'toggle_buttons';

    /**
     * Get the form component for this field type
     */
    public function getFormComponent(CustomField $field): Component
    {
        return app(FlexFieldsCacheService::class)->fieldType(
            $this,
            "form.{$field->id}",
            fn() => $this->createComponent($field)->getFormComponent()
        );
    }

    /**
     * Get the table column for this field type
     */
    public function getTableColumn(CustomField $field): Column
    {
        return app(FlexFieldsCacheService::class)->fieldType(
            $this,
            "column.{$field->id}",
            fn() => $this->createComponent($field)->getTableColumn()
        );
    }

    /**
     * Get the infolist entry for this field type
     */
    public function getInfolistEntry(CustomField $field): Entry
    {
        return app(FlexFieldsCacheService::class)->fieldType(
            $this,
            "infolist.{$field->id}",
            fn() => $this->createComponent($field)->getInfolistEntry()
        );
    }

    /**
     * Create the typed component instance
     */
    private function createComponent(CustomField $field): TypedComponentInterface
    {
        $componentClass = $this->getComponentClass();
        return $componentClass::make($field);
    }

    /**
     * Get the component class for this field type
     */
    private function getComponentClass(): string
    {
        return match($this) {
            self::TEXT => TextInputComponent::class,
            self::TEXTAREA => TextareaFieldComponent::class,
            self::NUMBER => NumberComponent::class,
            self::CURRENCY => CurrencyComponent::class,
            self::DATE => DateComponent::class,
            self::DATE_TIME => DateTimeComponent::class,
            self::TOGGLE => ToggleComponent::class,
            self::CHECKBOX => CheckboxComponent::class,
            self::SELECT => SelectComponent::class,
            self::RADIO => RadioComponent::class,
            self::MULTI_SELECT => MultiSelectComponent::class,
            self::CHECKBOX_LIST => CheckboxListComponent::class,
            self::TAGS_INPUT => TagsInputComponent::class,
            self::COLOR_PICKER => ColorPickerComponent::class,
            self::LINK => LinkComponent::class,
            self::RICH_EDITOR => RichEditorComponent::class,
            self::MARKDOWN_EDITOR => MarkdownEditorComponent::class,
            self::TOGGLE_BUTTONS => ToggleButtonsComponent::class,
        };
    }

    /**
     * Get validation rules for this field type
     */
    public function getValidationRules(bool $isRequired = false, bool $isEncrypted = false): array
    {
        return app(FlexFieldsCacheService::class)->fieldType(
            $this,
            "validation.{$isRequired}.{$isEncrypted}",
            fn() => $this->buildValidationRules($isRequired, $isEncrypted)
        );
    }

    /**
     * Build validation rules
     */
    private function buildValidationRules(bool $isRequired, bool $isEncrypted): array
    {
        $rules = [];
        
        if ($isRequired) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        $rules = match($this) {
            self::TEXT, self::LINK => [...$rules, 'string', 'max:' . ($isEncrypted ? 1000 : 65535)],
            self::TEXTAREA => [...$rules, 'string', 'max:' . ($isEncrypted ? 5000 : 16777215)],
            self::NUMBER => [...$rules, 'numeric'],
            self::CURRENCY => [...$rules, 'numeric', 'min:0'],
            self::DATE => [...$rules, 'date'],
            self::DATE_TIME => [...$rules, 'date'],
            self::TOGGLE, self::CHECKBOX => [...$rules, 'boolean'],
            self::SELECT, self::RADIO => [...$rules, 'string', 'exists:custom_field_options,id'],
            self::MULTI_SELECT, self::CHECKBOX_LIST, self::TAGS_INPUT, self::TOGGLE_BUTTONS => [...$rules, 'array'],
            self::COLOR_PICKER => [...$rules, 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            self::RICH_EDITOR, self::MARKDOWN_EDITOR => [...$rules, 'string'],
        };

        return $rules;
    }

    /**
     * Check if this field type supports options
     */
    public function isOptionable(): bool
    {
        return in_array($this, [
            self::SELECT,
            self::RADIO,
            self::MULTI_SELECT,
            self::CHECKBOX_LIST,
            self::TOGGLE_BUTTONS,
        ]);
    }

    /**
     * Check if this field type can be encrypted
     */
    public function isEncryptable(): bool
    {
        return in_array($this, [
            self::TEXT,
            self::TEXTAREA,
            self::NUMBER,
            self::CURRENCY,
            self::DATE,
            self::DATE_TIME,
            self::LINK,
        ]);
    }

    /**
     * Check if this field type is searchable
     */
    public function isSearchable(): bool
    {
        return !in_array($this, [
            self::TOGGLE,
            self::CHECKBOX,
            self::COLOR_PICKER,
        ]);
    }

    /**
     * Check if this field type is filterable
     */
    public function isFilterable(): bool
    {
        return true; // All field types can be filtered
    }

    /**
     * Get the icon for this field type
     */
    public function getIcon(): string
    {
        return match($this) {
            self::TEXT => 'heroicon-o-document-text',
            self::TEXTAREA => 'heroicon-o-bars-3-bottom-left',
            self::NUMBER => 'heroicon-o-hashtag',
            self::CURRENCY => 'heroicon-o-currency-dollar',
            self::DATE => 'heroicon-o-calendar',
            self::DATE_TIME => 'heroicon-o-clock',
            self::TOGGLE => 'heroicon-o-switch-horizontal',
            self::CHECKBOX => 'heroicon-o-check-square',
            self::SELECT => 'heroicon-o-chevron-down',
            self::RADIO => 'heroicon-o-radio',
            self::MULTI_SELECT => 'heroicon-o-list-bullet',
            self::CHECKBOX_LIST => 'heroicon-o-check-circle',
            self::TAGS_INPUT => 'heroicon-o-tag',
            self::COLOR_PICKER => 'heroicon-o-swatch',
            self::LINK => 'heroicon-o-link',
            self::RICH_EDITOR => 'heroicon-o-document-text',
            self::MARKDOWN_EDITOR => 'heroicon-o-code-bracket',
            self::TOGGLE_BUTTONS => 'heroicon-o-squares-2x2',
        };
    }

    /**
     * Get the label for this field type
     */
    public function getLabel(): string
    {
        return match($this) {
            self::TEXT => 'Text',
            self::TEXTAREA => 'Textarea',
            self::NUMBER => 'Number',
            self::CURRENCY => 'Currency',
            self::DATE => 'Date',
            self::DATE_TIME => 'Date & Time',
            self::TOGGLE => 'Toggle',
            self::CHECKBOX => 'Checkbox',
            self::SELECT => 'Select',
            self::RADIO => 'Radio',
            self::MULTI_SELECT => 'Multi Select',
            self::CHECKBOX_LIST => 'Checkbox List',
            self::TAGS_INPUT => 'Tags Input',
            self::COLOR_PICKER => 'Color Picker',
            self::LINK => 'Link',
            self::RICH_EDITOR => 'Rich Editor',
            self::MARKDOWN_EDITOR => 'Markdown Editor',
            self::TOGGLE_BUTTONS => 'Toggle Buttons',
        };
    }

    /**
     * Get the field category
     */
    public function getCategory(): FieldCategory
    {
        return match($this) {
            self::TEXT, self::TEXTAREA, self::LINK, self::RICH_EDITOR, self::MARKDOWN_EDITOR => FieldCategory::TEXT,
            self::NUMBER, self::CURRENCY => FieldCategory::NUMERIC,
            self::DATE, self::DATE_TIME => FieldCategory::DATE,
            self::TOGGLE, self::CHECKBOX => FieldCategory::BOOLEAN,
            self::SELECT, self::RADIO => FieldCategory::SINGLE_CHOICE,
            self::MULTI_SELECT, self::CHECKBOX_LIST, self::TAGS_INPUT, self::TOGGLE_BUTTONS => FieldCategory::MULTI_CHOICE,
            self::COLOR_PICKER => FieldCategory::OTHER,
        };
    }
}
```

## Example Component Implementation

### TextInputComponent
```php
<?php

namespace FlexFields\Components\Forms;

use FlexFields\Contracts\TypedFormComponentInterface;
use FlexFields\Contracts\TypedColumnInterface;
use FlexFields\Contracts\TypedInfolistInterface;
use FlexFields\Models\CustomField;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\TextEntry;

class TextInputComponent implements TypedFormComponentInterface, TypedColumnInterface, TypedInfolistInterface
{
    public function __construct(
        protected CustomField $field
    ) {}

    public static function make(CustomField $field): static
    {
        return new static($field);
    }

    public function getFormComponent(): Component
    {
        $component = TextInput::make($this->field->code)
            ->label($this->field->name)
            ->helperText($this->field->description)
            ->placeholder($this->field->placeholder)
            ->required($this->field->is_required)
            ->maxLength($this->field->settings['max_length'] ?? 255);

        // Apply validation rules
        if ($this->field->validation_rules) {
            $component->rules($this->field->validation_rules);
        }

        // Apply visibility conditions
        if ($this->field->hasVisibilityConditions()) {
            $component->visible(fn ($get) => $this->field->evaluateVisibility($get));
        }

        // Apply default value
        if ($this->field->default_value) {
            $component->default($this->field->default_value);
        }

        // Apply encryption if enabled
        if ($this->field->is_encrypted) {
            $component->extraAttributes(['data-encrypted' => true]);
        }

        return $component;
    }

    public function getTableColumn(): Column
    {
        $column = TextColumn::make($this->field->code)
            ->label($this->field->name)
            ->searchable($this->field->is_searchable)
            ->sortable($this->field->is_sortable)
            ->toggleable($this->field->is_toggleable);

        // Apply formatting
        if ($format = $this->field->settings['display_format'] ?? null) {
            $column->formatStateUsing(fn ($state) => sprintf($format, $state));
        }

        // Apply character limit
        if ($limit = $this->field->settings['character_limit'] ?? null) {
            $column->limit($limit);
        }

        return $column;
    }

    public function getInfolistEntry(): Entry
    {
        $entry = TextEntry::make($this->field->code)
            ->label($this->field->name)
            ->placeholder('—');

        // Apply formatting
        if ($format = $this->field->settings['display_format'] ?? null) {
            $entry->formatStateUsing(fn ($state) => sprintf($format, $state));
        }

        // Apply visibility conditions
        if ($this->field->hasVisibilityConditions()) {
            $entry->visible(fn ($get) => $this->field->evaluateVisibility($get));
        }

        return $entry;
    }
}
```

### SelectComponent
```php
<?php

namespace FlexFields\Components\Forms;

use FlexFields\Contracts\TypedFormComponentInterface;
use FlexFields\Contracts\TypedColumnInterface;
use FlexFields\Contracts\TypedInfolistInterface;
use FlexFields\Models\CustomField;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\TextEntry;

class SelectComponent implements TypedFormComponentInterface, TypedColumnInterface, TypedInfolistInterface
{
    public function __construct(
        protected CustomField $field
    ) {}

    public static function make(CustomField $field): static
    {
        return new static($field);
    }

    public function getFormComponent(): Component
    {
        $component = Select::make($this->field->code)
            ->label($this->field->name)
            ->helperText($this->field->description)
            ->placeholder($this->field->placeholder ?? 'Select an option')
            ->required($this->field->is_required)
            ->searchable($this->field->settings['searchable'] ?? false)
            ->options($this->getOptions());

        // Apply validation rules
        if ($this->field->validation_rules) {
            $component->rules($this->field->validation_rules);
        }

        // Apply visibility conditions
        if ($this->field->hasVisibilityConditions()) {
            $component->visible(fn ($get) => $this->field->evaluateVisibility($get));
        }

        // Apply default value
        if ($this->field->default_value) {
            $component->default($this->field->default_value);
        }

        // Enable create option if allowed
        if ($this->field->settings['allow_create'] ?? false) {
            $component->createOptionForm([
                TextInput::make('label')
                    ->label('Option Label')
                    ->required(),
            ])
            ->createOptionUsing(function (array $data) {
                return $this->field->options()->create([
                    'label' => $data['label'],
                    'value' => str($data['label'])->slug()->toString(),
                ])->id;
            });
        }

        return $component;
    }

    public function getTableColumn(): Column
    {
        $column = TextColumn::make($this->field->code)
            ->label($this->field->name)
            ->searchable($this->field->is_searchable)
            ->sortable($this->field->is_sortable)
            ->toggleable($this->field->is_toggleable);

        // Format to show option label instead of value
        $column->formatStateUsing(function ($state) {
            $option = $this->field->options()->find($state);
            return $option ? $option->label : $state;
        });

        // Add badge styling if enabled
        if ($this->field->settings['show_as_badge'] ?? false) {
            $column->badge()
                ->color(fn ($state) => $this->getOptionColor($state));
        }

        return $column;
    }

    public function getInfolistEntry(): Entry
    {
        $entry = TextEntry::make($this->field->code)
            ->label($this->field->name)
            ->placeholder('—');

        // Format to show option label
        $entry->formatStateUsing(function ($state) {
            $option = $this->field->options()->find($state);
            return $option ? $option->label : $state;
        });

        // Add badge styling if enabled
        if ($this->field->settings['show_as_badge'] ?? false) {
            $entry->badge()
                ->color(fn ($state) => $this->getOptionColor($state));
        }

        // Apply visibility conditions
        if ($this->field->hasVisibilityConditions()) {
            $entry->visible(fn ($get) => $this->field->evaluateVisibility($get));
        }

        return $entry;
    }

    protected function getOptions(): array
    {
        return $this->field->options()
            ->orderBy('sort_order')
            ->pluck('label', 'id')
            ->toArray();
    }

    protected function getOptionColor($optionId): string
    {
        $option = $this->field->options()->find($optionId);
        return $option?->settings['color'] ?? 'gray';
    }
}
```

## Unified Cache Service

```php
<?php

namespace FlexFields\Services;

use FlexFields\Enums\CustomFieldType;
use Illuminate\Support\Facades\Cache;

class FlexFieldsCacheService
{
    protected string $prefix = 'flexfields';
    protected int $ttl = 300; // 5 minutes

    /**
     * Cache a value for a specific field type
     */
    public function fieldType(CustomFieldType $type, string $key, callable $callback): mixed
    {
        $cacheKey = $this->getCacheKey($type->value, $key);
        
        return Cache::remember($cacheKey, $this->ttl, $callback);
    }

    /**
     * Clear cache for a specific field type
     */
    public function clearFieldType(CustomFieldType $type, ?string $key = null): void
    {
        if ($key) {
            Cache::forget($this->getCacheKey($type->value, $key));
        } else {
            // Clear all cache for this field type
            Cache::flush("{$this->prefix}.{$type->value}.*");
        }
    }

    /**
     * Clear all FlexFields cache
     */
    public function clearAll(): void
    {
        Cache::flush("{$this->prefix}.*");
    }

    /**
     * Get a cache key
     */
    protected function getCacheKey(string ...$parts): string
    {
        return implode('.', [$this->prefix, ...$parts]);
    }

    /**
     * Cache a value with custom TTL
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember(
            $this->getCacheKey($key),
            $ttl,
            $callback
        );
    }

    /**
     * Get a cached value
     */
    public function get(string $key): mixed
    {
        return Cache::get($this->getCacheKey($key));
    }

    /**
     * Set a cached value
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        return Cache::put(
            $this->getCacheKey($key),
            $value,
            $ttl ?? $this->ttl
        );
    }

    /**
     * Forget a cached value
     */
    public function forget(string $key): bool
    {
        return Cache::forget($this->getCacheKey($key));
    }
}
```

## Usage Examples

### Before (Factory Pattern)
```php
// In CustomFieldsForm::getFormFields()
$factory = app(FieldComponentFactory::class);
$components = [];

foreach ($customFields as $field) {
    $component = $factory->create($field);
    $components[] = $component;
}

return $components;
```

### After (Enum-Driven)
```php
// In CustomFieldsForm::getFormFields()
$components = [];

foreach ($customFields as $field) {
    $components[] = $field->type->getFormComponent($field);
}

return $components;
```

### Performance Comparison
```php
// Before: Multiple lookups and indirection
$factory = app(FieldComponentFactory::class); // DI lookup
$component = $factory->create($field); // Factory method
// Inside factory: array lookup + instance cache check + component creation

// After: Direct enum method with caching
$component = $field->type->getFormComponent($field); // Direct method call with internal caching
```

### Adding a New Field Type

#### Before (7+ files to modify)
```php
// 1. FieldTypeRegistryService::getBuiltInFormComponent()
case 'new_type':
    return NewTypeComponent::class;

// 2. FieldTypeRegistryService::getBuiltInTableColumn()
case 'new_type':
    return NewTypeColumn::class;

// 3. FieldTypeRegistryService::getBuiltInInfolistEntry()
case 'new_type':
    return NewTypeEntry::class;

// 4-7. Update all factory classes...
```

#### After (1 enum case)
```php
// Only in CustomFieldType enum:
case NEW_TYPE = 'new_type';

// In getComponentClass():
self::NEW_TYPE => NewTypeComponent::class,
```

## Migration Examples

### Service Provider Updates
```php
// Before
$this->app->singleton(FieldComponentFactory::class);
$this->app->singleton(FieldColumnFactory::class);
$this->app->singleton(FieldInfolistsFactory::class);
// ... more factory registrations

// After
$this->app->singleton(FlexFieldsCacheService::class);
$this->app->singleton(FieldTypeService::class);
```

### Configuration Updates
```php
// Before
'factories' => [
    'form_component' => FieldComponentFactory::class,
    'table_column' => FieldColumnFactory::class,
    'infolist_entry' => FieldInfolistsFactory::class,
],

// After
'services' => [
    'cache' => FlexFieldsCacheService::class,
    'field_type' => FieldTypeService::class,
],
``` 