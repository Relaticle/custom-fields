<?php

declare(strict_types=1);

use Relaticle\CustomFields\Filament\Integration\Components\FieldTypes\Advanced\TagsFieldType;
use Relaticle\CustomFields\Filament\Integration\Components\FieldTypes\DateTime\DateFieldType;
use Relaticle\CustomFields\Filament\Integration\Components\FieldTypes\Numeric\NumberFieldType;
use Relaticle\CustomFields\Filament\Integration\Components\FieldTypes\Selection\BooleanFieldType;
use Relaticle\CustomFields\Filament\Integration\Components\FieldTypes\Selection\SelectFieldType;
use Relaticle\CustomFields\Filament\Integration\Components\FieldTypes\Text\TextareaFieldType;
use Relaticle\CustomFields\Filament\Integration\Components\FieldTypes\Text\TextFieldType;

/**
 * Field type registry mapping field type values to their component implementations
 */
return [
    /**
     * Text-based field types
     */
    'text' => TextFieldType::class,
    'textarea' => TextareaFieldType::class,
    // 'rich_text' => RichTextFieldType::class,
    // 'markdown' => MarkdownFieldType::class,
    // 'email' => EmailFieldType::class,
    // 'url' => UrlFieldType::class,
    // 'tel' => TelFieldType::class,
    // 'slug' => SlugFieldType::class,
    
    /**
     * Numeric field types
     */
    'number' => NumberFieldType::class,
    // 'currency' => CurrencyFieldType::class,
    // 'percentage' => PercentageFieldType::class,
    
    /**
     * Date/Time field types
     */
    'date' => DateFieldType::class,
    // 'datetime' => DateTimeFieldType::class,
    // 'time' => TimeFieldType::class,
    
    /**
     * Selection field types
     */
    'select' => SelectFieldType::class,
    // 'multiselect' => MultiselectFieldType::class,
    // 'radio' => RadioFieldType::class,
    // 'checkbox' => CheckboxFieldType::class,
    // 'checkbox_list' => CheckboxListFieldType::class,
    // 'toggle' => ToggleFieldType::class,
    'boolean' => BooleanFieldType::class,
    
    /**
     * Media field types
     */
    // 'file' => FileFieldType::class,
    // 'image' => ImageFieldType::class,
    
    /**
     * Advanced field types
     */
    // 'color' => ColorFieldType::class,
    'tags' => TagsFieldType::class,
    // 'key_value' => KeyValueFieldType::class,
    // 'json' => JsonFieldType::class,
    // 'code' => CodeFieldType::class,
    // 'rating' => RatingFieldType::class,
    // 'range' => RangeFieldType::class,
    // 'repeater' => RepeaterFieldType::class,
    // 'relationship' => RelationshipFieldType::class,
];