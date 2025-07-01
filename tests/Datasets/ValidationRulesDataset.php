<?php

declare(strict_types=1);

use Relaticle\CustomFields\Enums\CustomFieldValidationRule;

// Comprehensive validation rules dataset with all 84 rules
dataset('validation_rules_with_parameters', fn (): array => [
    // No parameters rules
    'required' => [
        'rule' => CustomFieldValidationRule::REQUIRED->value,
        'parameters' => [],
        'validValue' => 'some value',
        'invalidValue' => null,
    ],
    'accepted' => [
        'rule' => CustomFieldValidationRule::ACCEPTED->value,
        'parameters' => [],
        'validValue' => true,
        'invalidValue' => false,
    ],
    'active_url' => [
        'rule' => CustomFieldValidationRule::ACTIVE_URL->value,
        'parameters' => [],
        'validValue' => 'https://example.com',
        'invalidValue' => 'not-a-url',
    ],
    'alpha' => [
        'rule' => CustomFieldValidationRule::ALPHA->value,
        'parameters' => [],
        'validValue' => 'abcdef',
        'invalidValue' => 'abc123',
    ],
    'alpha_dash' => [
        'rule' => CustomFieldValidationRule::ALPHA_DASH->value,
        'parameters' => [],
        'validValue' => 'abc-def_123',
        'invalidValue' => 'abc def!',
    ],
    'alpha_num' => [
        'rule' => CustomFieldValidationRule::ALPHA_NUM->value,
        'parameters' => [],
        'validValue' => 'abc123',
        'invalidValue' => 'abc-123',
    ],
    'array' => [
        'rule' => CustomFieldValidationRule::ARRAY->value,
        'parameters' => [],
        'validValue' => ['item1', 'item2'],
        'invalidValue' => 'not-array',
    ],
    'ascii' => [
        'rule' => CustomFieldValidationRule::ASCII->value,
        'parameters' => [],
        'validValue' => 'Hello World',
        'invalidValue' => 'Héllo Wörld',
    ],
    'boolean' => [
        'rule' => CustomFieldValidationRule::BOOLEAN->value,
        'parameters' => [],
        'validValue' => true,
        'invalidValue' => 'not-boolean',
    ],
    'confirmed' => [
        'rule' => CustomFieldValidationRule::CONFIRMED->value,
        'parameters' => [],
        'validValue' => 'password',
        'invalidValue' => 'password', // Note: needs password_confirmation field
    ],
    'current_password' => [
        'rule' => CustomFieldValidationRule::CURRENT_PASSWORD->value,
        'parameters' => [],
        'validValue' => 'current-password',
        'invalidValue' => 'wrong-password',
    ],
    'date' => [
        'rule' => CustomFieldValidationRule::DATE->value,
        'parameters' => [],
        'validValue' => '2023-12-25',
        'invalidValue' => 'not-a-date',
    ],
    'declined' => [
        'rule' => CustomFieldValidationRule::DECLINED->value,
        'parameters' => [],
        'validValue' => false,
        'invalidValue' => true,
    ],
    'distinct' => [
        'rule' => CustomFieldValidationRule::DISTINCT->value,
        'parameters' => [],
        'validValue' => ['a', 'b', 'c'],
        'invalidValue' => ['a', 'a', 'b'],
    ],
    'email' => [
        'rule' => CustomFieldValidationRule::EMAIL->value,
        'parameters' => [],
        'validValue' => 'test@example.com',
        'invalidValue' => 'not-an-email',
    ],
    'file' => [
        'rule' => CustomFieldValidationRule::FILE->value,
        'parameters' => [],
        'validValue' => null, // UploadedFile instance needed
        'invalidValue' => 'not-a-file',
    ],
    'filled' => [
        'rule' => CustomFieldValidationRule::FILLED->value,
        'parameters' => [],
        'validValue' => 'some value',
        'invalidValue' => '',
    ],
    'image' => [
        'rule' => CustomFieldValidationRule::IMAGE->value,
        'parameters' => [],
        'validValue' => null, // UploadedFile image needed
        'invalidValue' => 'not-an-image',
    ],
    'integer' => [
        'rule' => CustomFieldValidationRule::INTEGER->value,
        'parameters' => [],
        'validValue' => 123,
        'invalidValue' => 12.3,
    ],
    'ip' => [
        'rule' => CustomFieldValidationRule::IP->value,
        'parameters' => [],
        'validValue' => '192.168.1.1',
        'invalidValue' => 'not-an-ip',
    ],
    'ipv4' => [
        'rule' => CustomFieldValidationRule::IPV4->value,
        'parameters' => [],
        'validValue' => '192.168.1.1',
        'invalidValue' => '2001:db8::1',
    ],
    'ipv6' => [
        'rule' => CustomFieldValidationRule::IPV6->value,
        'parameters' => [],
        'validValue' => '2001:db8::1',
        'invalidValue' => '192.168.1.1',
    ],
    'json' => [
        'rule' => CustomFieldValidationRule::JSON->value,
        'parameters' => [],
        'validValue' => '{"key": "value"}',
        'invalidValue' => 'not-json',
    ],
    'mac_address' => [
        'rule' => CustomFieldValidationRule::MAC_ADDRESS->value,
        'parameters' => [],
        'validValue' => '00:14:22:01:23:45',
        'invalidValue' => 'not-a-mac',
    ],
    'numeric' => [
        'rule' => CustomFieldValidationRule::NUMERIC->value,
        'parameters' => [],
        'validValue' => '123.45',
        'invalidValue' => 'not-numeric',
    ],
    'password' => [
        'rule' => CustomFieldValidationRule::PASSWORD->value,
        'parameters' => [],
        'validValue' => 'StrongPassword123!',
        'invalidValue' => 'weak',
    ],
    'present' => [
        'rule' => CustomFieldValidationRule::PRESENT->value,
        'parameters' => [],
        'validValue' => '',
        'invalidValue' => null, // Field must be present but can be empty
    ],
    'prohibited' => [
        'rule' => CustomFieldValidationRule::PROHIBITED->value,
        'parameters' => [],
        'validValue' => null,
        'invalidValue' => 'value',
    ],
    'string' => [
        'rule' => CustomFieldValidationRule::STRING->value,
        'parameters' => [],
        'validValue' => 'string value',
        'invalidValue' => 123,
    ],
    'timezone' => [
        'rule' => CustomFieldValidationRule::TIMEZONE->value,
        'parameters' => [],
        'validValue' => 'America/New_York',
        'invalidValue' => 'invalid-timezone',
    ],
    'uppercase' => [
        'rule' => CustomFieldValidationRule::UPPERCASE->value,
        'parameters' => [],
        'validValue' => 'UPPERCASE',
        'invalidValue' => 'lowercase',
    ],
    'url' => [
        'rule' => CustomFieldValidationRule::URL->value,
        'parameters' => [],
        'validValue' => 'https://example.com',
        'invalidValue' => 'not-a-url',
    ],
    'uuid' => [
        'rule' => CustomFieldValidationRule::UUID->value,
        'parameters' => [],
        'validValue' => '550e8400-e29b-41d4-a716-446655440000',
        'invalidValue' => 'not-a-uuid',
    ],

    // Single parameter rules
    'min_length' => [
        'rule' => CustomFieldValidationRule::MIN->value,
        'parameters' => [3],
        'validValue' => 'abc',
        'invalidValue' => 'ab',
    ],
    'max_length' => [
        'rule' => CustomFieldValidationRule::MAX->value,
        'parameters' => [10],
        'validValue' => 'short',
        'invalidValue' => 'this is too long for max validation',
    ],
    'size' => [
        'rule' => CustomFieldValidationRule::SIZE->value,
        'parameters' => [5],
        'validValue' => 'exact',
        'invalidValue' => 'wrong',
    ],
    'digits' => [
        'rule' => CustomFieldValidationRule::DIGITS->value,
        'parameters' => [4],
        'validValue' => '1234',
        'invalidValue' => '123',
    ],
    'max_digits' => [
        'rule' => CustomFieldValidationRule::MAX_DIGITS->value,
        'parameters' => [5],
        'validValue' => '12345',
        'invalidValue' => '123456',
    ],
    'min_digits' => [
        'rule' => CustomFieldValidationRule::MIN_DIGITS->value,
        'parameters' => [3],
        'validValue' => '123',
        'invalidValue' => '12',
    ],
    'multiple_of' => [
        'rule' => CustomFieldValidationRule::MULTIPLE_OF->value,
        'parameters' => [5],
        'validValue' => 25,
        'invalidValue' => 23,
    ],
    'after' => [
        'rule' => CustomFieldValidationRule::AFTER->value,
        'parameters' => ['2023-01-01'],
        'validValue' => '2023-06-01',
        'invalidValue' => '2022-12-31',
    ],
    'after_or_equal' => [
        'rule' => CustomFieldValidationRule::AFTER_OR_EQUAL->value,
        'parameters' => ['2023-01-01'],
        'validValue' => '2023-01-01',
        'invalidValue' => '2022-12-31',
    ],
    'before' => [
        'rule' => CustomFieldValidationRule::BEFORE->value,
        'parameters' => ['2023-12-31'],
        'validValue' => '2023-06-01',
        'invalidValue' => '2024-01-01',
    ],
    'before_or_equal' => [
        'rule' => CustomFieldValidationRule::BEFORE_OR_EQUAL->value,
        'parameters' => ['2023-12-31'],
        'validValue' => '2023-12-31',
        'invalidValue' => '2024-01-01',
    ],
    'date_equals' => [
        'rule' => CustomFieldValidationRule::DATE_EQUALS->value,
        'parameters' => ['2023-06-15'],
        'validValue' => '2023-06-15',
        'invalidValue' => '2023-06-16',
    ],
    'date_format' => [
        'rule' => CustomFieldValidationRule::DATE_FORMAT->value,
        'parameters' => ['Y-m-d'],
        'validValue' => '2023-06-15',
        'invalidValue' => '15/06/2023',
    ],
    'gt' => [
        'rule' => CustomFieldValidationRule::GT->value,
        'parameters' => ['10'],
        'validValue' => 15,
        'invalidValue' => 5,
    ],
    'gte' => [
        'rule' => CustomFieldValidationRule::GTE->value,
        'parameters' => ['10'],
        'validValue' => 10,
        'invalidValue' => 9,
    ],
    'lt' => [
        'rule' => CustomFieldValidationRule::LT->value,
        'parameters' => ['10'],
        'validValue' => 5,
        'invalidValue' => 15,
    ],
    'lte' => [
        'rule' => CustomFieldValidationRule::LTE->value,
        'parameters' => ['10'],
        'validValue' => 10,
        'invalidValue' => 15,
    ],

    // Two parameter rules
    'between_numeric' => [
        'rule' => CustomFieldValidationRule::BETWEEN->value,
        'parameters' => [5, 10],
        'validValue' => 7,
        'invalidValue' => 15,
    ],
    'between_string' => [
        'rule' => CustomFieldValidationRule::BETWEEN->value,
        'parameters' => [3, 10],
        'validValue' => 'hello',
        'invalidValue' => 'hi',
    ],
    'digits_between' => [
        'rule' => CustomFieldValidationRule::DIGITS_BETWEEN->value,
        'parameters' => [3, 5],
        'validValue' => '1234',
        'invalidValue' => '12',
    ],
    'decimal_precision' => [
        'rule' => CustomFieldValidationRule::DECIMAL->value,
        'parameters' => [2, 4],
        'validValue' => '123.45',
        'invalidValue' => '123.456789',
    ],

    // Multiple parameter rules
    'in_list' => [
        'rule' => CustomFieldValidationRule::IN->value,
        'parameters' => ['red', 'green', 'blue'],
        'validValue' => 'red',
        'invalidValue' => 'yellow',
    ],
    'not_in_list' => [
        'rule' => CustomFieldValidationRule::NOT_IN->value,
        'parameters' => ['red', 'green', 'blue'],
        'validValue' => 'yellow',
        'invalidValue' => 'red',
    ],
    'starts_with' => [
        'rule' => CustomFieldValidationRule::STARTS_WITH->value,
        'parameters' => ['hello', 'hi'],
        'validValue' => 'hello world',
        'invalidValue' => 'goodbye world',
    ],
    'ends_with' => [
        'rule' => CustomFieldValidationRule::ENDS_WITH->value,
        'parameters' => ['world', 'universe'],
        'validValue' => 'hello world',
        'invalidValue' => 'hello there',
    ],
    'doesnt_start_with' => [
        'rule' => CustomFieldValidationRule::DOESNT_START_WITH->value,
        'parameters' => ['bad', 'evil'],
        'validValue' => 'good morning',
        'invalidValue' => 'bad morning',
    ],
    'doesnt_end_with' => [
        'rule' => CustomFieldValidationRule::DOESNT_END_WITH->value,
        'parameters' => ['bad', 'evil'],
        'validValue' => 'something good',
        'invalidValue' => 'something bad',
    ],
    'mimes' => [
        'rule' => CustomFieldValidationRule::MIMES->value,
        'parameters' => ['jpg', 'png', 'gif'],
        'validValue' => null, // UploadedFile needed
        'invalidValue' => null, // Wrong mime type file needed
    ],
    'mimetypes' => [
        'rule' => CustomFieldValidationRule::MIMETYPES->value,
        'parameters' => ['image/jpeg', 'image/png'],
        'validValue' => null, // UploadedFile needed
        'invalidValue' => null, // Wrong mime type file needed
    ],

    // Complex conditional rules
    'required_if' => [
        'rule' => CustomFieldValidationRule::REQUIRED_IF->value,
        'parameters' => ['other_field', 'value'],
        'validValue' => 'required value',
        'invalidValue' => null, // When other_field = value
    ],
    'required_unless' => [
        'rule' => CustomFieldValidationRule::REQUIRED_UNLESS->value,
        'parameters' => ['other_field', 'value'],
        'validValue' => 'required value',
        'invalidValue' => null, // When other_field != value
    ],
    'required_with' => [
        'rule' => CustomFieldValidationRule::REQUIRED_WITH->value,
        'parameters' => ['other_field'],
        'validValue' => 'required value',
        'invalidValue' => null, // When other_field is present
    ],
    'required_with_all' => [
        'rule' => CustomFieldValidationRule::REQUIRED_WITH_ALL->value,
        'parameters' => ['field1', 'field2'],
        'validValue' => 'required value',
        'invalidValue' => null, // When all fields are present
    ],
    'required_without' => [
        'rule' => CustomFieldValidationRule::REQUIRED_WITHOUT->value,
        'parameters' => ['other_field'],
        'validValue' => 'required value',
        'invalidValue' => null, // When other_field is missing
    ],
    'required_without_all' => [
        'rule' => CustomFieldValidationRule::REQUIRED_WITHOUT_ALL->value,
        'parameters' => ['field1', 'field2'],
        'validValue' => 'required value',
        'invalidValue' => null, // When all fields are missing
    ],
    'accepted_if' => [
        'rule' => CustomFieldValidationRule::ACCEPTED_IF->value,
        'parameters' => ['other_field', 'value'],
        'validValue' => true,
        'invalidValue' => false, // When other_field = value
    ],
    'declined_if' => [
        'rule' => CustomFieldValidationRule::DECLINED_IF->value,
        'parameters' => ['other_field', 'value'],
        'validValue' => false,
        'invalidValue' => true, // When other_field = value
    ],
    'prohibited_if' => [
        'rule' => CustomFieldValidationRule::PROHIBITED_IF->value,
        'parameters' => ['other_field', 'value'],
        'validValue' => null,
        'invalidValue' => 'prohibited value', // When other_field = value
    ],
    'prohibited_unless' => [
        'rule' => CustomFieldValidationRule::PROHIBITED_UNLESS->value,
        'parameters' => ['other_field', 'value'],
        'validValue' => null,
        'invalidValue' => 'prohibited value', // When other_field != value
    ],
    'exclude_if' => [
        'rule' => CustomFieldValidationRule::EXCLUDE_IF->value,
        'parameters' => ['other_field', 'value'],
        'validValue' => 'any value', // Excluded from validation
        'invalidValue' => 'any value', // Excluded from validation
    ],
    'exclude_unless' => [
        'rule' => CustomFieldValidationRule::EXCLUDE_UNLESS->value,
        'parameters' => ['other_field', 'value'],
        'validValue' => 'any value', // Excluded from validation
        'invalidValue' => 'any value', // Excluded from validation
    ],
    'prohibits' => [
        'rule' => CustomFieldValidationRule::PROHIBITS->value,
        'parameters' => ['other_field'],
        'validValue' => 'some value',
        'invalidValue' => 'some value', // When other_field is also present
    ],

    // Advanced rules
    'different' => [
        'rule' => CustomFieldValidationRule::DIFFERENT->value,
        'parameters' => ['other_field'],
        'validValue' => 'different value',
        'invalidValue' => 'same value', // When other_field has same value
    ],
    'same' => [
        'rule' => CustomFieldValidationRule::SAME->value,
        'parameters' => ['other_field'],
        'validValue' => 'same value',
        'invalidValue' => 'different value', // When other_field has different value
    ],
    'regex_pattern' => [
        'rule' => CustomFieldValidationRule::REGEX->value,
        'parameters' => ['/^[A-Z][a-z]+$/'],
        'validValue' => 'Hello',
        'invalidValue' => 'hello',
    ],
    'not_regex_pattern' => [
        'rule' => CustomFieldValidationRule::NOT_REGEX->value,
        'parameters' => ['/^\d+$/'],
        'validValue' => 'abc123',
        'invalidValue' => '123',
    ],
    'exists_in_table' => [
        'rule' => CustomFieldValidationRule::EXISTS->value,
        'parameters' => ['users.id'],
        'validValue' => 1, // Existing user ID
        'invalidValue' => 999999, // Non-existing user ID
    ],
    'unique_in_table' => [
        'rule' => CustomFieldValidationRule::UNIQUE->value,
        'parameters' => ['users.email'],
        'validValue' => 'unique@example.com',
        'invalidValue' => 'existing@example.com', // Existing email
    ],
    'in_array_field' => [
        'rule' => CustomFieldValidationRule::IN_ARRAY->value,
        'parameters' => ['allowed_values'],
        'validValue' => 'allowed_value',
        'invalidValue' => 'not_allowed_value',
    ],
    'dimensions_image' => [
        'rule' => CustomFieldValidationRule::DIMENSIONS->value,
        'parameters' => ['min_width=100', 'min_height=100'],
        'validValue' => null, // Valid image file needed
        'invalidValue' => null, // Invalid dimensions image needed
    ],
    'exclude' => [
        'rule' => CustomFieldValidationRule::EXCLUDE->value,
        'parameters' => [],
        'validValue' => 'any value', // Always excluded
        'invalidValue' => 'any value', // Always excluded
    ],
    'enum_values' => [
        'rule' => CustomFieldValidationRule::ENUM->value,
        'parameters' => ['App\\Enums\\Status'],
        'validValue' => 'active', // Valid enum value
        'invalidValue' => 'invalid_status', // Invalid enum value
    ],
]);

// Field type validation rules compatibility dataset
dataset('field_type_validation_compatibility', fn (): array => [
    'text_field_rules' => [
        'fieldType' => 'text',
        'allowedRules' => ['required', 'min', 'max', 'between', 'regex', 'alpha', 'alpha_num', 'alpha_dash', 'string', 'email', 'starts_with'],
        'disallowedRules' => ['numeric', 'integer', 'boolean', 'array', 'date'],
    ],
    'number_field_rules' => [
        'fieldType' => 'number',
        'allowedRules' => ['required', 'numeric', 'min', 'max', 'between', 'integer', 'starts_with'],
        'disallowedRules' => ['alpha', 'alpha_dash', 'email', 'boolean', 'array'],
    ],
    'currency_field_rules' => [
        'fieldType' => 'currency',
        'allowedRules' => ['required', 'numeric', 'min', 'max', 'between', 'decimal', 'starts_with'],
        'disallowedRules' => ['alpha', 'integer', 'boolean', 'array', 'date'],
    ],
    'date_field_rules' => [
        'fieldType' => 'date',
        'allowedRules' => ['required', 'date', 'after', 'after_or_equal', 'before', 'before_or_equal', 'date_format'],
        'disallowedRules' => ['numeric', 'alpha', 'boolean', 'array', 'email'],
    ],
    'boolean_field_rules' => [
        'fieldType' => 'toggle',
        'allowedRules' => ['required', 'boolean'],
        'disallowedRules' => ['numeric', 'alpha', 'string', 'array', 'date', 'email'],
    ],
    'select_field_rules' => [
        'fieldType' => 'select',
        'allowedRules' => ['required', 'in'],
        'disallowedRules' => ['numeric', 'alpha', 'boolean', 'array', 'date', 'email'],
    ],
    'multi_select_field_rules' => [
        'fieldType' => 'multi-select',
        'allowedRules' => ['required', 'array', 'min', 'max', 'between', 'in'],
        'disallowedRules' => ['numeric', 'alpha', 'boolean', 'string', 'date', 'email'],
    ],
    'checkbox_list_field_rules' => [
        'fieldType' => 'checkbox-list',
        'allowedRules' => ['required', 'array', 'min', 'max', 'between'],
        'disallowedRules' => ['numeric', 'alpha', 'boolean', 'string', 'date', 'email'],
    ],
    'rich_editor_field_rules' => [
        'fieldType' => 'rich-editor',
        'allowedRules' => ['required', 'string', 'min', 'max', 'between', 'starts_with'],
        'disallowedRules' => ['numeric', 'alpha', 'boolean', 'array', 'date', 'integer'],
    ],
    'url_field_rules' => [
        'fieldType' => 'link',
        'allowedRules' => ['required', 'url', 'starts_with'],
        'disallowedRules' => ['numeric', 'alpha', 'boolean', 'array', 'date', 'integer'],
    ],
]);
