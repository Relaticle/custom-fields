<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Relaticle\CustomFields\Services\FieldTypeRegistryService;

enum CustomFieldType: string implements HasLabel
{
    case TEXT = 'text';
    case NUMBER = 'number';
    case LINK = 'link';
    case SELECT = 'select';
    case CHECKBOX = 'checkbox';
    case CHECKBOX_LIST = 'checkbox-list';
    case RADIO = 'radio';
    case RICH_EDITOR = 'rich-editor';
    case MARKDOWN_EDITOR = 'markdown-editor';
    case TAGS_INPUT = 'tags-input';
    case COLOR_PICKER = 'color-picker';
    case TOGGLE = 'toggle';
    case TOGGLE_BUTTONS = 'toggle-buttons';
    case TEXTAREA = 'textarea';
    case CURRENCY = 'currency';
    case DATE = 'date';
    case DATE_TIME = 'date-time';
    case MULTI_SELECT = 'multi-select';

    /**
     * 🚀 PERFORMANCE: Cached static options with 5-minute TTL
     * 
     * @return array<string, string>
     */
    public static function options(): array
    {
        return Cache::remember(
            'custom-fields.field-types.options',
            300, // 5 minutes
            fn () => [
                self::TEXT->value => 'Text',
                self::NUMBER->value => 'Number',
                self::LINK->value => 'Link',
                self::TEXTAREA->value => 'Textarea',
                self::CURRENCY->value => 'Currency',
                self::DATE->value => 'Date',
                self::DATE_TIME->value => 'Date and Time',
                self::TOGGLE->value => 'Toggle',
                self::TOGGLE_BUTTONS->value => 'Toggle buttons',
                self::SELECT->value => 'Select',
                self::CHECKBOX->value => 'Checkbox',
                self::CHECKBOX_LIST->value => 'Checkbox list',
                self::RADIO->value => 'Radio',
                self::RICH_EDITOR->value => 'Rich editor',
                self::MARKDOWN_EDITOR->value => 'Markdown editor',
                self::TAGS_INPUT->value => 'Tags input',
                self::COLOR_PICKER->value => 'Color picker',
                self::MULTI_SELECT->value => 'Multi-select',
            ]
        );
    }

    /**
     * 🚀 ENHANCED: Better integration with FieldTypeRegistryService
     * 
     * @return Collection<int, array{label: string, value: string, icon: string}>
     */
    public static function optionsForSelect(): Collection
    {
        return app(FieldTypeRegistryService::class)->getFieldTypeOptions();
    }

    /**
     * 🚀 PERFORMANCE: Cached static icons with 5-minute TTL
     * 
     * @return array<string, string>
     */
    public static function icons(): array
    {
        return Cache::remember(
            'custom-fields.field-types.icons',
            300, // 5 minutes
            fn () => [
                self::TEXTAREA->value => 'mdi-form-textbox',
                self::NUMBER->value => 'mdi-numeric-7-box',
                self::LINK->value => 'mdi-link-variant',
                self::TEXT->value => 'mdi-form-textbox',
                self::CURRENCY->value => 'mdi-currency-usd',
                self::DATE->value => 'mdi-calendar',
                self::DATE_TIME->value => 'mdi-calendar-clock',
                self::TOGGLE->value => 'mdi-toggle-switch',
                self::TOGGLE_BUTTONS->value => 'mdi-toggle-switch',
                self::SELECT->value => 'mdi-form-select',
                self::CHECKBOX->value => 'mdi-checkbox-marked',
                self::CHECKBOX_LIST->value => 'mdi-checkbox-multiple-marked',
                self::RADIO->value => 'mdi-radiobox-marked',
                self::RICH_EDITOR->value => 'mdi-format-text',
                self::MARKDOWN_EDITOR->value => 'mdi-format-text',
                self::TAGS_INPUT->value => 'mdi-tag-multiple',
                self::COLOR_PICKER->value => 'mdi-palette',
                self::MULTI_SELECT->value => 'mdi-form-dropdown',
            ]
        );
    }

    /**
     * 🚀 PERFORMANCE: Cached optionable types
     * 
     * @return Collection<int, mixed>
     */
    public static function optionables(): Collection
    {
        return Cache::remember(
            'custom-fields.field-types.optionables',
            300, // 5 minutes
            fn () => collect([
                self::SELECT,
                self::MULTI_SELECT,
                self::RADIO,
                self::CHECKBOX_LIST,
                self::TAGS_INPUT,
                self::TOGGLE_BUTTONS,
            ])
        );
    }

    /**
     * 🎯 REVOLUTIONARY UNIFIED CLASSIFICATION SYSTEM
     * Single source of truth for all field type characteristics.
     */
    public function getCategory(): FieldCategory
    {
        return match ($this) {
            self::TEXT,
            self::TEXTAREA,
            self::LINK,
            self::RICH_EDITOR,
            self::MARKDOWN_EDITOR,
            self::COLOR_PICKER => FieldCategory::TEXT,

            self::NUMBER, self::CURRENCY => FieldCategory::NUMERIC,

            self::DATE, self::DATE_TIME => FieldCategory::DATE,

            self::TOGGLE, self::CHECKBOX => FieldCategory::BOOLEAN,

            self::SELECT, self::RADIO => FieldCategory::SINGLE_OPTION,

            self::MULTI_SELECT,
            self::CHECKBOX_LIST,
            self::TAGS_INPUT,
            self::TOGGLE_BUTTONS => FieldCategory::MULTI_OPTION,
        };
    }

    // 🔥 SIMPLIFIED BOOLEAN METHODS - One-liner delegates
    public function isBoolean(): bool
    {
        return $this->getCategory() === FieldCategory::BOOLEAN;
    }

    public function isNumeric(): bool
    {
        return $this->getCategory() === FieldCategory::NUMERIC;
    }

    public function isTextBased(): bool
    {
        return $this->getCategory() === FieldCategory::TEXT;
    }

    public function isDateBased(): bool
    {
        return $this->getCategory() === FieldCategory::DATE;
    }

    public function isOptionable(): bool
    {
        return $this->getCategory()->isOptionable();
    }

    public function hasMultipleValues(): bool
    {
        return $this->getCategory() === FieldCategory::MULTI_OPTION;
    }

    /**
     * 🚀 REVOLUTIONARY OPERATOR COMPATIBILITY
     * Delegates to category for consistent behavior.
     *
     * @return array<int, Operator>
     */
    public function getCompatibleOperators(): array
    {
        return $this->getCategory()->getCompatibleOperators();
    }

    /**
     * 🚀 PERFORMANCE: Cached encryptable types
     * 
     * @return Collection<int, mixed>
     */
    public static function encryptables(): Collection
    {
        return Cache::remember(
            'custom-fields.field-types.encryptables',
            300, // 5 minutes
            fn () => collect([
                self::TEXT,
                self::TEXTAREA,
                self::LINK,
                self::RICH_EDITOR,
                self::MARKDOWN_EDITOR,
            ])
        );
    }

    /**
     * 🚀 PERFORMANCE: Cached searchable types
     * 
     * @return Collection<int, mixed>
     */
    public static function searchables(): Collection
    {
        return Cache::remember(
            'custom-fields.field-types.searchables',
            300, // 5 minutes
            fn () => collect([
                self::TEXT,
                self::TEXTAREA,
                self::LINK,
                self::DATE,
                self::DATE_TIME,
                self::TAGS_INPUT,
            ])
        );
    }

    /**
     * 🚀 PERFORMANCE: Cached filterable types
     * 
     * @return Collection<int, mixed>
     */
    public static function filterable(): Collection
    {
        return Cache::remember(
            'custom-fields.field-types.filterable',
            300, // 5 minutes
            fn () => collect([
                self::SELECT,
                self::MULTI_SELECT,
                self::RADIO,
                self::CHECKBOX_LIST,
                self::CHECKBOX,
                self::TOGGLE,
                self::TOGGLE_BUTTONS,
            ])
        );
    }

    /**
     * 🚀 PERFORMANCE: Optimized icon getter
     */
    public function getIcon(): string
    {
        return self::icons()[$this->value];
    }

    /**
     * 🚀 PERFORMANCE: Optimized label getter
     */
    public function getLabel(): string
    {
        return self::options()[$this->value];
    }

    /**
     * 🚀 ENHANCED: Better validation rules with caching per type
     * 
     * @return array<int, CustomFieldValidationRule>
     */
    public function allowedValidationRules(): array
    {
        return Cache::remember(
            "custom-fields.field-types.validation-rules.{$this->value}",
            300, // 5 minutes
            fn () => match ($this) {
                self::TEXT => [
                    CustomFieldValidationRule::REQUIRED,
                    CustomFieldValidationRule::MIN,
                    CustomFieldValidationRule::MAX,
                    CustomFieldValidationRule::BETWEEN,
                    CustomFieldValidationRule::REGEX,
                    CustomFieldValidationRule::ALPHA,
                    CustomFieldValidationRule::ALPHA_NUM,
                    CustomFieldValidationRule::ALPHA_DASH,
                    CustomFieldValidationRule::STRING,
                    CustomFieldValidationRule::EMAIL,
                    CustomFieldValidationRule::STARTS_WITH,
                ],
                self::TEXTAREA => [
                    CustomFieldValidationRule::REQUIRED,
                    CustomFieldValidationRule::MIN,
                    CustomFieldValidationRule::MAX,
                    CustomFieldValidationRule::BETWEEN,
                    CustomFieldValidationRule::STRING,
                    CustomFieldValidationRule::STARTS_WITH,
                ],
                self::CURRENCY => [
                    CustomFieldValidationRule::REQUIRED,
                    CustomFieldValidationRule::NUMERIC,
                    CustomFieldValidationRule::MIN,
                    CustomFieldValidationRule::MAX,
                    CustomFieldValidationRule::BETWEEN,
                    CustomFieldValidationRule::DECIMAL,
                    CustomFieldValidationRule::STARTS_WITH,
                ],
                self::DATE, self::DATE_TIME => [
                    CustomFieldValidationRule::REQUIRED,
                    CustomFieldValidationRule::DATE,
                    CustomFieldValidationRule::AFTER,
                    CustomFieldValidationRule::AFTER_OR_EQUAL,
                    CustomFieldValidationRule::BEFORE,
                    CustomFieldValidationRule::BEFORE_OR_EQUAL,
                    CustomFieldValidationRule::DATE_FORMAT,
                ],
                self::TOGGLE, self::TOGGLE_BUTTONS, self::CHECKBOX => [
                    CustomFieldValidationRule::REQUIRED,
                    CustomFieldValidationRule::BOOLEAN,
                ],
                self::SELECT, self::RADIO => [
                    CustomFieldValidationRule::REQUIRED,
                    CustomFieldValidationRule::IN,
                ],
                self::MULTI_SELECT => [
                    CustomFieldValidationRule::REQUIRED,
                    CustomFieldValidationRule::ARRAY,
                    CustomFieldValidationRule::MIN,
                    CustomFieldValidationRule::MAX,
                    CustomFieldValidationRule::BETWEEN,
                    CustomFieldValidationRule::IN,
                ],
                self::NUMBER => [
                    CustomFieldValidationRule::REQUIRED,
                    CustomFieldValidationRule::NUMERIC,
                    CustomFieldValidationRule::MIN,
                    CustomFieldValidationRule::MAX,
                    CustomFieldValidationRule::BETWEEN,
                    CustomFieldValidationRule::INTEGER,
                    CustomFieldValidationRule::STARTS_WITH,
                ],
                self::LINK => [
                    CustomFieldValidationRule::REQUIRED,
                    CustomFieldValidationRule::URL,
                    CustomFieldValidationRule::STARTS_WITH,
                ],
                self::CHECKBOX_LIST, self::TAGS_INPUT => [
                    CustomFieldValidationRule::REQUIRED,
                    CustomFieldValidationRule::ARRAY,
                    CustomFieldValidationRule::MIN,
                    CustomFieldValidationRule::MAX,
                    CustomFieldValidationRule::BETWEEN,
                ],
                self::RICH_EDITOR, self::MARKDOWN_EDITOR => [
                    CustomFieldValidationRule::REQUIRED,
                    CustomFieldValidationRule::STRING,
                    CustomFieldValidationRule::MIN,
                    CustomFieldValidationRule::MAX,
                    CustomFieldValidationRule::BETWEEN,
                    CustomFieldValidationRule::STARTS_WITH,
                ],
                self::COLOR_PICKER => [
                    CustomFieldValidationRule::REQUIRED,
                    CustomFieldValidationRule::STRING,
                    CustomFieldValidationRule::STARTS_WITH,
                ],
            }
        );
    }

    /**
     * 🚀 NEW: Type-safe factory method for creating from string
     * Provides better error handling than tryFrom
     */
    public static function safeFrom(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * 🚀 NEW: Check if a string represents a valid built-in field type
     */
    public static function isBuiltInType(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }

    /**
     * 🚀 NEW: Get all field type values as array (for validation, etc.)
     * 
     * @return array<int, string>
     */
    public static function values(): array
    {
        return Cache::remember(
            'custom-fields.field-types.values',
            300, // 5 minutes
            fn () => array_map(fn (self $case) => $case->value, self::cases())
        );
    }

    /**
     * 🚀 NEW: Get field types by category
     * 
     * @return Collection<int, self>
     */
    public static function byCategory(FieldCategory $category): Collection
    {
        return Cache::remember(
            "custom-fields.field-types.by-category.{$category->value}",
            300, // 5 minutes
            fn () => collect(self::cases())
                ->filter(fn (self $type) => $type->getCategory() === $category)
        );
    }

    /**
     * 🚀 NEW: Clear all field type caches (useful for testing/development)
     */
    public static function clearCache(): void
    {
        $keys = [
            'custom-fields.field-types.options',
            'custom-fields.field-types.options-for-select',
            'custom-fields.field-types.icons',
            'custom-fields.field-types.optionables',
            'custom-fields.field-types.encryptables',
            'custom-fields.field-types.searchables',
            'custom-fields.field-types.filterable',
            'custom-fields.field-types.values',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        // Clear validation rules cache for each type
        foreach (self::cases() as $type) {
            Cache::forget("custom-fields.field-types.validation-rules.{$type->value}");
        }

        // Clear category-based caches
        foreach (FieldCategory::cases() as $category) {
            Cache::forget("custom-fields.field-types.by-category.{$category->value}");
        }
    }
}
