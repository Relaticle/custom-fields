<?php

declare(strict_types=1);

use Relaticle\CustomFields\Enums\CustomFieldType;

// Comprehensive field type configurations dataset for all 18 field types
dataset('field_type_configurations', fn (): array => [
    'text_field_basic' => [
        'fieldType' => CustomFieldType::TEXT->value,
        'config' => [
            'required' => true,
            'validation_rules' => [
                ['name' => 'required', 'parameters' => []],
                ['name' => 'min', 'parameters' => [3]],
                ['name' => 'max', 'parameters' => [255]],
            ],
        ],
        'testValues' => [
            'valid' => ['hello world', 'test', 'some text'],
            'invalid' => [null, '', 'ab', str_repeat('a', 256)],
        ],
        'expectedComponent' => 'TextInput',
    ],
    'text_field_with_regex' => [
        'fieldType' => CustomFieldType::TEXT->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'regex', 'parameters' => ['/^[A-Z][a-z]+$/']],
            ],
        ],
        'testValues' => [
            'valid' => ['Hello', 'World', 'Test'],
            'invalid' => ['hello', 'HELLO', 'Hello123', '123Hello'],
        ],
        'expectedComponent' => 'TextInput',
    ],
    'text_field_email' => [
        'fieldType' => CustomFieldType::TEXT->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'email', 'parameters' => []],
            ],
        ],
        'testValues' => [
            'valid' => ['test@example.com', 'user@domain.org'],
            'invalid' => ['not-an-email', 'test@', '@domain.com'],
        ],
        'expectedComponent' => 'TextInput',
    ],
    'number_field_basic' => [
        'fieldType' => CustomFieldType::NUMBER->value,
        'config' => [
            'required' => true,
            'validation_rules' => [
                ['name' => 'required', 'parameters' => []],
                ['name' => 'numeric', 'parameters' => []],
                ['name' => 'min', 'parameters' => [0]],
                ['name' => 'max', 'parameters' => [1000]],
            ],
        ],
        'testValues' => [
            'valid' => [0, 1, 100, 999, 1000],
            'invalid' => [null, '', -1, 1001, 'not-a-number'],
        ],
        'expectedComponent' => 'TextInput',
    ],
    'number_field_integer' => [
        'fieldType' => CustomFieldType::NUMBER->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'integer', 'parameters' => []],
                ['name' => 'between', 'parameters' => [1, 100]],
            ],
        ],
        'testValues' => [
            'valid' => [1, 50, 100],
            'invalid' => [0, 101, 1.5, 'not-integer'],
        ],
        'expectedComponent' => 'TextInput',
    ],
    'currency_field' => [
        'fieldType' => CustomFieldType::CURRENCY->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'numeric', 'parameters' => []],
                ['name' => 'decimal', 'parameters' => [0, 2]],
                ['name' => 'min', 'parameters' => [0]],
            ],
        ],
        'testValues' => [
            'valid' => [0, 10, 10.50, 999.99],
            'invalid' => [-1, 10.555, 'not-currency'],
        ],
        'expectedComponent' => 'TextInput',
    ],
    'date_field' => [
        'fieldType' => CustomFieldType::DATE->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'date', 'parameters' => []],
                ['name' => 'after', 'parameters' => ['2023-01-01']],
                ['name' => 'before', 'parameters' => ['2025-12-31']],
            ],
        ],
        'testValues' => [
            'valid' => ['2023-06-15', '2024-01-01', '2025-12-30'],
            'invalid' => ['2022-12-31', '2026-01-01', 'not-a-date', '32/13/2023'],
        ],
        'expectedComponent' => 'DatePicker',
    ],
    'datetime_field' => [
        'fieldType' => CustomFieldType::DATE_TIME->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'date', 'parameters' => []],
            ],
        ],
        'testValues' => [
            'valid' => ['2023-06-15 10:30:00', '2024-01-01 00:00:00'],
            'invalid' => ['not-a-datetime', '2023-06-15 25:00:00'],
        ],
        'expectedComponent' => 'DateTimePicker',
    ],
    'textarea_field' => [
        'fieldType' => CustomFieldType::TEXTAREA->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'min', 'parameters' => [10]],
                ['name' => 'max', 'parameters' => [1000]],
            ],
        ],
        'testValues' => [
            'valid' => ['This is a long text content that exceeds 10 characters', 'Lorem ipsum dolor sit amet consectetur'],
            'invalid' => ['short', str_repeat('a', 1001)],
        ],
        'expectedComponent' => 'Textarea',
    ],
    'select_field' => [
        'fieldType' => CustomFieldType::SELECT->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'in', 'parameters' => ['red', 'green', 'blue']],
            ],
            'options' => [
                ['label' => 'Red', 'value' => 'red'],
                ['label' => 'Green', 'value' => 'green'],
                ['label' => 'Blue', 'value' => 'blue'],
            ],
        ],
        'testValues' => [
            'valid' => ['red', 'green', 'blue'],
            'invalid' => ['yellow', 'purple', null],
        ],
        'expectedComponent' => 'Select',
    ],
    'multi_select_field' => [
        'fieldType' => CustomFieldType::MULTI_SELECT->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'array', 'parameters' => []],
                ['name' => 'min', 'parameters' => [1]],
                ['name' => 'max', 'parameters' => [3]],
            ],
            'options' => [
                ['label' => 'Option 1', 'value' => 'opt1'],
                ['label' => 'Option 2', 'value' => 'opt2'],
                ['label' => 'Option 3', 'value' => 'opt3'],
                ['label' => 'Option 4', 'value' => 'opt4'],
            ],
        ],
        'testValues' => [
            'valid' => [['opt1'], ['opt1', 'opt2'], ['opt1', 'opt2', 'opt3']],
            'invalid' => [[], ['opt1', 'opt2', 'opt3', 'opt4'], ['invalid'], 'not-array'],
        ],
        'expectedComponent' => 'Select',
    ],
    'checkbox_field' => [
        'fieldType' => CustomFieldType::CHECKBOX->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'boolean', 'parameters' => []],
            ],
        ],
        'testValues' => [
            'valid' => [true, false, 1, 0, '1', '0'],
            'invalid' => ['not-boolean', 2, 'yes', 'no'],
        ],
        'expectedComponent' => 'Checkbox',
    ],
    'checkbox_list_field' => [
        'fieldType' => CustomFieldType::CHECKBOX_LIST->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'array', 'parameters' => []],
                ['name' => 'between', 'parameters' => [1, 3]],
            ],
            'options' => [
                ['label' => 'Feature 1', 'value' => 'feat1'],
                ['label' => 'Feature 2', 'value' => 'feat2'],
                ['label' => 'Feature 3', 'value' => 'feat3'],
                ['label' => 'Feature 4', 'value' => 'feat4'],
            ],
        ],
        'testValues' => [
            'valid' => [['feat1'], ['feat1', 'feat2'], ['feat1', 'feat2', 'feat3']],
            'invalid' => [[], ['feat1', 'feat2', 'feat3', 'feat4'], 'not-array'],
        ],
        'expectedComponent' => 'CheckboxList',
    ],
    'radio_field' => [
        'fieldType' => CustomFieldType::RADIO->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'in', 'parameters' => ['small', 'medium', 'large']],
            ],
            'options' => [
                ['label' => 'Small', 'value' => 'small'],
                ['label' => 'Medium', 'value' => 'medium'],
                ['label' => 'Large', 'value' => 'large'],
            ],
        ],
        'testValues' => [
            'valid' => ['small', 'medium', 'large'],
            'invalid' => ['extra-large', 'tiny', null],
        ],
        'expectedComponent' => 'Radio',
    ],
    'toggle_field' => [
        'fieldType' => CustomFieldType::TOGGLE->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'boolean', 'parameters' => []],
            ],
        ],
        'testValues' => [
            'valid' => [true, false, 1, 0],
            'invalid' => ['not-boolean', 2, 'yes'],
        ],
        'expectedComponent' => 'Toggle',
    ],
    'toggle_buttons_field' => [
        'fieldType' => CustomFieldType::TOGGLE_BUTTONS->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'array', 'parameters' => []],
            ],
            'options' => [
                ['label' => 'Bold', 'value' => 'bold'],
                ['label' => 'Italic', 'value' => 'italic'],
                ['label' => 'Underline', 'value' => 'underline'],
            ],
        ],
        'testValues' => [
            'valid' => [[], ['bold'], ['bold', 'italic'], ['bold', 'italic', 'underline']],
            'invalid' => ['not-array', ['invalid-option']],
        ],
        'expectedComponent' => 'ToggleButtons',
    ],
    'rich_editor_field' => [
        'fieldType' => CustomFieldType::RICH_EDITOR->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'string', 'parameters' => []],
                ['name' => 'min', 'parameters' => [50]],
            ],
        ],
        'testValues' => [
            'valid' => ['<p>This is a rich text content with HTML tags and sufficient length to pass validation</p>'],
            'invalid' => ['<p>Too short</p>', null, 123],
        ],
        'expectedComponent' => 'RichEditor',
    ],
    'markdown_editor_field' => [
        'fieldType' => CustomFieldType::MARKDOWN_EDITOR->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'string', 'parameters' => []],
                ['name' => 'min', 'parameters' => [20]],
            ],
        ],
        'testValues' => [
            'valid' => ['# Heading\n\nThis is **bold** text and *italic* text.', '## Another heading\n\nSome content here.'],
            'invalid' => ['# Short', null, 123],
        ],
        'expectedComponent' => 'MarkdownEditor',
    ],
    'tags_input_field' => [
        'fieldType' => CustomFieldType::TAGS_INPUT->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'array', 'parameters' => []],
                ['name' => 'min', 'parameters' => [1]],
                ['name' => 'max', 'parameters' => [5]],
            ],
        ],
        'testValues' => [
            'valid' => [['tag1'], ['tag1', 'tag2'], ['tag1', 'tag2', 'tag3', 'tag4', 'tag5']],
            'invalid' => [[], ['tag1', 'tag2', 'tag3', 'tag4', 'tag5', 'tag6'], 'not-array'],
        ],
        'expectedComponent' => 'TagsInput',
    ],
    'color_picker_field' => [
        'fieldType' => CustomFieldType::COLOR_PICKER->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'string', 'parameters' => []],
                ['name' => 'starts_with', 'parameters' => ['#']],
            ],
        ],
        'testValues' => [
            'valid' => ['#ff0000', '#00ff00', '#0000ff', '#ffffff', '#000000'],
            'invalid' => ['ff0000', 'red', 'blue', null, 123],
        ],
        'expectedComponent' => 'ColorPicker',
    ],
    'link_field' => [
        'fieldType' => CustomFieldType::LINK->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'url', 'parameters' => []],
                ['name' => 'starts_with', 'parameters' => ['http://', 'https://']],
            ],
        ],
        'testValues' => [
            'valid' => ['https://example.com', 'http://test.org', 'https://www.google.com'],
            'invalid' => ['not-a-url', 'ftp://example.com', 'example.com', null],
        ],
        'expectedComponent' => 'TextInput',
    ],
]);

// Field type categories dataset
dataset('field_type_categories', fn (): array => [
    'text_category' => [
        'category' => 'text',
        'fieldTypes' => [
            CustomFieldType::TEXT->value,
            CustomFieldType::TEXTAREA->value,
            CustomFieldType::LINK->value,
            CustomFieldType::RICH_EDITOR->value,
            CustomFieldType::MARKDOWN_EDITOR->value,
            CustomFieldType::COLOR_PICKER->value,
        ],
        'characteristics' => [
            'encryptable' => true,
            'searchable' => true,
            'filterable' => false,
            'optionable' => false,
        ],
    ],
    'numeric_category' => [
        'category' => 'numeric',
        'fieldTypes' => [
            CustomFieldType::NUMBER->value,
            CustomFieldType::CURRENCY->value,
        ],
        'characteristics' => [
            'encryptable' => false,
            'searchable' => false,
            'filterable' => false,
            'optionable' => false,
        ],
    ],
    'date_category' => [
        'category' => 'date',
        'fieldTypes' => [
            CustomFieldType::DATE->value,
            CustomFieldType::DATE_TIME->value,
        ],
        'characteristics' => [
            'encryptable' => false,
            'searchable' => true,
            'filterable' => false,
            'optionable' => false,
        ],
    ],
    'boolean_category' => [
        'category' => 'boolean',
        'fieldTypes' => [
            CustomFieldType::TOGGLE->value,
            CustomFieldType::CHECKBOX->value,
        ],
        'characteristics' => [
            'encryptable' => false,
            'searchable' => false,
            'filterable' => true,
            'optionable' => false,
        ],
    ],
    'single_option_category' => [
        'category' => 'single_option',
        'fieldTypes' => [
            CustomFieldType::SELECT->value,
            CustomFieldType::RADIO->value,
        ],
        'characteristics' => [
            'encryptable' => false,
            'searchable' => false,
            'filterable' => true,
            'optionable' => true,
        ],
    ],
    'multi_option_category' => [
        'category' => 'multi_option',
        'fieldTypes' => [
            CustomFieldType::MULTI_SELECT->value,
            CustomFieldType::CHECKBOX_LIST->value,
            CustomFieldType::TAGS_INPUT->value,
            CustomFieldType::TOGGLE_BUTTONS->value,
        ],
        'characteristics' => [
            'encryptable' => false,
            'searchable' => true, // tags_input is searchable
            'filterable' => true,
            'optionable' => true,
        ],
    ],
]);

// Field type component mapping dataset
dataset('field_type_component_mappings', fn (): array => [
    'text_input_types' => [
        'fieldTypes' => [
            CustomFieldType::TEXT->value,
            CustomFieldType::NUMBER->value,
            CustomFieldType::CURRENCY->value,
            CustomFieldType::LINK->value,
        ],
        'expectedComponent' => 'TextInput',
    ],
    'textarea_types' => [
        'fieldTypes' => [CustomFieldType::TEXTAREA->value],
        'expectedComponent' => 'Textarea',
    ],
    'select_types' => [
        'fieldTypes' => [
            CustomFieldType::SELECT->value,
            CustomFieldType::MULTI_SELECT->value,
        ],
        'expectedComponent' => 'Select',
    ],
    'checkbox_types' => [
        'fieldTypes' => [CustomFieldType::CHECKBOX->value],
        'expectedComponent' => 'Checkbox',
    ],
    'checkbox_list_types' => [
        'fieldTypes' => [CustomFieldType::CHECKBOX_LIST->value],
        'expectedComponent' => 'CheckboxList',
    ],
    'radio_types' => [
        'fieldTypes' => [CustomFieldType::RADIO->value],
        'expectedComponent' => 'Radio',
    ],
    'toggle_types' => [
        'fieldTypes' => [CustomFieldType::TOGGLE->value],
        'expectedComponent' => 'Toggle',
    ],
    'toggle_buttons_types' => [
        'fieldTypes' => [CustomFieldType::TOGGLE_BUTTONS->value],
        'expectedComponent' => 'ToggleButtons',
    ],
    'date_picker_types' => [
        'fieldTypes' => [CustomFieldType::DATE->value],
        'expectedComponent' => 'DatePicker',
    ],
    'datetime_picker_types' => [
        'fieldTypes' => [CustomFieldType::DATE_TIME->value],
        'expectedComponent' => 'DateTimePicker',
    ],
    'rich_editor_types' => [
        'fieldTypes' => [CustomFieldType::RICH_EDITOR->value],
        'expectedComponent' => 'RichEditor',
    ],
    'markdown_editor_types' => [
        'fieldTypes' => [CustomFieldType::MARKDOWN_EDITOR->value],
        'expectedComponent' => 'MarkdownEditor',
    ],
    'tags_input_types' => [
        'fieldTypes' => [CustomFieldType::TAGS_INPUT->value],
        'expectedComponent' => 'TagsInput',
    ],
    'color_picker_types' => [
        'fieldTypes' => [CustomFieldType::COLOR_PICKER->value],
        'expectedComponent' => 'ColorPicker',
    ],
]);

// Edge cases and complex scenarios dataset
dataset('edge_case_scenarios', fn (): array => [
    'empty_validation_rules' => [
        'scenario' => 'field_with_no_validation',
        'fieldType' => CustomFieldType::TEXT->value,
        'config' => ['validation_rules' => []],
        'testValues' => [
            'valid' => ['any value', null, '', 123, []],
            'invalid' => [], // Nothing is invalid when no validation
        ],
        'expectedBehavior' => 'accepts_any_value',
    ],
    'conflicting_validation_rules' => [
        'scenario' => 'conflicting_min_max',
        'fieldType' => CustomFieldType::TEXT->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'min', 'parameters' => [10]],
                ['name' => 'max', 'parameters' => [5]], // Impossible condition
            ],
        ],
        'testValues' => [
            'valid' => [],
            'invalid' => ['short', 'medium length', 'very long text'],
        ],
        'expectedBehavior' => 'always_fails_validation',
    ],
    'unicode_content' => [
        'scenario' => 'unicode_text_field',
        'fieldType' => CustomFieldType::TEXT->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'string', 'parameters' => []],
                ['name' => 'min', 'parameters' => [3]],
            ],
        ],
        'testValues' => [
            'valid' => ['🎉🎊🎈', 'héllo wörld', '中文字符', 'Ελληνικά'],
            'invalid' => ['🎉🎊', 'ab'],
        ],
        'expectedBehavior' => 'handles_unicode_correctly',
    ],
    'large_array_field' => [
        'scenario' => 'large_multi_select',
        'fieldType' => CustomFieldType::MULTI_SELECT->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'array', 'parameters' => []],
                ['name' => 'max', 'parameters' => [1000]],
            ],
            'options' => array_map(fn ($i): array => ['label' => "Option {$i}", 'value' => "opt{$i}"], range(1, 1000)),
        ],
        'testValues' => [
            'valid' => [array_map(fn ($i): string => "opt{$i}", range(1, 100))],
            'invalid' => [array_map(fn ($i): string => "opt{$i}", range(1, 1001))],
        ],
        'expectedBehavior' => 'handles_large_datasets',
    ],
    'deeply_nested_conditions' => [
        'scenario' => 'complex_visibility_chain',
        'fieldType' => CustomFieldType::TEXT->value,
        'config' => [
            'frontend_visibility_conditions' => [
                ['field_code' => 'field_a', 'operator' => 'equals', 'value' => 'show'],
                ['field_code' => 'field_b', 'operator' => 'not_equals', 'value' => 'hide'],
                ['field_code' => 'field_c', 'operator' => 'contains', 'value' => 'test'],
            ],
        ],
        'testValues' => [
            'valid' => ['any value when conditions met'],
            'invalid' => ['any value when conditions not met'],
        ],
        'expectedBehavior' => 'complex_conditional_logic',
    ],
    'special_characters_in_options' => [
        'scenario' => 'special_chars_in_select_options',
        'fieldType' => CustomFieldType::SELECT->value,
        'config' => [
            'options' => [
                ['label' => 'Option with "quotes"', 'value' => 'quotes'],
                ['label' => 'Option with <script>', 'value' => 'script'],
                ['label' => 'Option with & ampersand', 'value' => 'ampersand'],
                ['label' => 'Option with \' apostrophe', 'value' => 'apostrophe'],
            ],
        ],
        'testValues' => [
            'valid' => ['quotes', 'script', 'ampersand', 'apostrophe'],
            'invalid' => ['invalid', '<script>alert("xss")</script>'],
        ],
        'expectedBehavior' => 'sanitizes_special_characters',
    ],
    'performance_stress_test' => [
        'scenario' => 'many_validation_rules',
        'fieldType' => CustomFieldType::TEXT->value,
        'config' => [
            'validation_rules' => [
                ['name' => 'required', 'parameters' => []],
                ['name' => 'string', 'parameters' => []],
                ['name' => 'min', 'parameters' => [5]],
                ['name' => 'max', 'parameters' => [100]],
                ['name' => 'alpha_dash', 'parameters' => []],
                ['name' => 'starts_with', 'parameters' => ['prefix_']],
                ['name' => 'regex', 'parameters' => ['/^prefix_[a-z0-9_-]+$/i']],
            ],
        ],
        'testValues' => [
            'valid' => ['prefix_valid_value'],
            'invalid' => [null, '', 'pre', 'prefix_'.str_repeat('a', 95), 'invalid_prefix', 'prefix_invalid!'],
        ],
        'expectedBehavior' => 'handles_multiple_rules_efficiently',
    ],
]);
