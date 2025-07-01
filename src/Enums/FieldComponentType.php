<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Enums;

use Relaticle\CustomFields\Integration\Forms\Components\CheckboxComponent;
use Relaticle\CustomFields\Integration\Forms\Components\CheckboxListComponent;
use Relaticle\CustomFields\Integration\Forms\Components\ColorPickerComponent;
use Relaticle\CustomFields\Integration\Forms\Components\CurrencyComponent;
use Relaticle\CustomFields\Integration\Forms\Components\DateComponent;
use Relaticle\CustomFields\Integration\Forms\Components\DateTimeComponent;
use Relaticle\CustomFields\Integration\Forms\Components\LinkComponent;
use Relaticle\CustomFields\Integration\Forms\Components\MarkdownEditorComponent;
use Relaticle\CustomFields\Integration\Forms\Components\MultiSelectComponent;
use Relaticle\CustomFields\Integration\Forms\Components\NumberComponent;
use Relaticle\CustomFields\Integration\Forms\Components\RadioComponent;
use Relaticle\CustomFields\Integration\Forms\Components\RichEditorComponent;
use Relaticle\CustomFields\Integration\Forms\Components\SelectComponent;
use Relaticle\CustomFields\Integration\Forms\Components\TagsInputComponent;
use Relaticle\CustomFields\Integration\Forms\Components\TextareaFormComponent;
use Relaticle\CustomFields\Integration\Forms\Components\TextInputComponent;
use Relaticle\CustomFields\Integration\Forms\Components\ToggleButtonsComponent;
use Relaticle\CustomFields\Integration\Forms\Components\ToggleComponent;

/**
 * Type-safe enum mapping field types to their corresponding component classes.
 *
 * Replaces string-based component resolution with compile-time type safety,
 * enabling better IDE support and refactoring capabilities.
 */
enum FieldComponentType: string
{
    case TEXT = TextInputComponent::class;
    case NUMBER = NumberComponent::class;
    case SELECT = SelectComponent::class;
    case MULTI_SELECT = MultiSelectComponent::class;
    case RADIO = RadioComponent::class;
    case CHECKBOX = CheckboxComponent::class;
    case CHECKBOX_LIST = CheckboxListComponent::class;
    case TOGGLE = ToggleComponent::class;
    case TOGGLE_BUTTONS = ToggleButtonsComponent::class;
    case DATE = DateComponent::class;
    case DATE_TIME = DateTimeComponent::class;
    case TEXTAREA = TextareaFormComponent::class;
    case RICH_EDITOR = RichEditorComponent::class;
    case MARKDOWN_EDITOR = MarkdownEditorComponent::class;
    case TAGS_INPUT = TagsInputComponent::class;
    case LINK = LinkComponent::class;
    case COLOR_PICKER = ColorPickerComponent::class;
    case CURRENCY = CurrencyComponent::class;

    /**
     * Get the component class name from a field type string.
     *
     * @param  string  $fieldType  The custom field type
     * @return string|null The component class name, or null if not found
     */
    public static function getComponentClass(string $fieldType): ?string
    {
        return match ($fieldType) {
            'text' => self::TEXT->value,
            'number' => self::NUMBER->value,
            'select' => self::SELECT->value,
            'multi-select' => self::MULTI_SELECT->value,
            'radio' => self::RADIO->value,
            'checkbox' => self::CHECKBOX->value,
            'checkbox-list' => self::CHECKBOX_LIST->value,
            'toggle' => self::TOGGLE->value,
            'toggle-buttons' => self::TOGGLE_BUTTONS->value,
            'date' => self::DATE->value,
            'date-time' => self::DATE_TIME->value,
            'textarea' => self::TEXTAREA->value,
            'rich-editor' => self::RICH_EDITOR->value,
            'markdown-editor' => self::MARKDOWN_EDITOR->value,
            'tags-input' => self::TAGS_INPUT->value,
            'link' => self::LINK->value,
            'color-picker' => self::COLOR_PICKER->value,
            'currency' => self::CURRENCY->value,
            default => null,
        };
    }

    /**
     * Get all supported field types.
     *
     * @return array<string> Array of field type strings
     */
    public static function getSupportedTypes(): array
    {
        return [
            'text',
            'number',
            'select',
            'multi-select',
            'radio',
            'checkbox',
            'checkbox-list',
            'toggle',
            'toggle-buttons',
            'date',
            'date-time',
            'textarea',
            'rich-editor',
            'markdown-editor',
            'tags-input',
            'link',
            'color-picker',
            'currency',
        ];
    }
}
