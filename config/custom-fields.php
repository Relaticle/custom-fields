<?php

declare(strict_types=1);

use Relaticle\CustomFields\EntitySystem\EntityConfigurator;
use Relaticle\CustomFields\Enums\CustomFieldsFeature;
use Relaticle\CustomFields\FeatureSystem\FeatureConfigurator;
use Relaticle\CustomFields\FieldTypeSystem\FieldTypeConfigurator;

return [
    /*
    |--------------------------------------------------------------------------
    | Entity Configuration
    |--------------------------------------------------------------------------
    |
    | Configure entities (models that can have custom fields) using the
    | clean, type-safe fluent builder interface.
    |
    */
    'entity_configuration' => EntityConfigurator::configure()
        ->discover(app_path('Models'))
        ->cache(false),

    /*
    |--------------------------------------------------------------------------
    | Advanced Field Type Configuration
    |--------------------------------------------------------------------------
    |
    | Configure field types using the powerful fluent builder API.
    | This provides advanced control over validation, security, and behavior.
    |
    */
    'field_type_configuration' => FieldTypeConfigurator::configure()
        // Control which field types are available globally
        ->enabled([]) // Empty = all enabled, or specify: ['text', 'email', 'select']
        ->disabled(['file-upload']) // Disable specific field types
        ->discover(true)
        ->cache(enabled: false, ttl: 3400),

    /*
    |--------------------------------------------------------------------------
    | Features Configuration
    |--------------------------------------------------------------------------
    |
    | Configure package features using the type-safe enum-based configurator.
    | This consolidates all feature settings into a single, organized system.
    |
    | Every feature is listed below, on or off, with the reason for its default.
    | A feature is on when it only adds a control to the field editor and is a
    | no-op for fields that do not use it; it is off when turning it on would
    | change how existing values are stored, validated, or displayed.
    |
    */
    'features' => FeatureConfigurator::configure()
        ->enable(
            // Field-level show/hide rules, the base the other visibility features build on.
            CustomFieldsFeature::FIELD_CONDITIONAL_VISIBILITY,
            // The same rules one level up; a section without conditions renders as before.
            CustomFieldsFeature::SECTION_CONDITIONAL_VISIBILITY,
            // Per-field encryption at rest, opt in per field.
            CustomFieldsFeature::FIELD_ENCRYPTION,
            // Colored option badges, ignored by fields that set no colors.
            CustomFieldsFeature::FIELD_OPTION_COLORS,
            // Per-field validation rules; fields with none configured validate as before.
            CustomFieldsFeature::FIELD_VALIDATION_RULES,
            // Help text under a field.
            CustomFieldsFeature::FIELD_DESCRIPTION,
            // Whether that help text sits above or below the input; unset still means below.
            CustomFieldsFeature::FIELD_DESCRIPTION_POSITION,
            // Custom fields as table columns, the entry point for the table integration.
            CustomFieldsFeature::UI_TABLE_COLUMNS,
            // Lets users pick which of those columns they see.
            CustomFieldsFeature::UI_TOGGLEABLE_COLUMNS,
            // Table filters for filterable field types.
            CustomFieldsFeature::UI_TABLE_FILTERS,
            // Field width within the form grid; every field defaults to full width.
            CustomFieldsFeature::UI_FIELD_WIDTH_CONTROL,
            // The same for sections; every existing section is already 100%.
            CustomFieldsFeature::UI_SECTION_WIDTH_CONTROL,
            // The Filament page that manages fields and sections.
            CustomFieldsFeature::SYSTEM_MANAGEMENT_INTERFACE,
            // Grouping fields into sections.
            CustomFieldsFeature::SYSTEM_SECTIONS,
        )
        ->disable(
            // Would take the code away from whoever creates the field, and codes are the
            // identifier host code and imports address a field by.
            CustomFieldsFeature::FIELD_CODE_AUTO_GENERATE,
            // Turns a field's storage into a list of values; that is a data-shape decision.
            CustomFieldsFeature::FIELD_MULTI_VALUE,
            // Adds a uniqueness rule over values that already exist.
            CustomFieldsFeature::FIELD_UNIQUE_VALUE,
            // Offers the host's own model columns as condition sources; only the host knows
            // which of its columns are safe to expose in the field editor.
            CustomFieldsFeature::MODEL_ATTRIBUTE_CONDITIONS,
            // Would hide existing custom-field columns from tables that show them today.
            CustomFieldsFeature::UI_TOGGLEABLE_COLUMNS_HIDDEN_DEFAULT,
            // Tenant isolation depends on the host's tenancy; see the multi-tenancy docs.
            CustomFieldsFeature::SYSTEM_MULTI_TENANCY,
        ),

    /*
    |--------------------------------------------------------------------------
    | Management Interface Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the Custom Fields management interface in Filament.
    | Only applies when SYSTEM_MANAGEMENT_INTERFACE feature is enabled.
    |
    */
    'management' => [
        'slug' => 'custom-fields',
        'navigation_sort' => -1,

        // Nest the management page under its own navigation group instead of top-level.
        'navigation_group_enabled' => true,

        'cluster' => null,

        // Width of the add/edit section modal. Accepts a Filament\Support\Enums\Width case or its
        // string value (e.g. 'screen-lg'). Null falls back to a width based on conditional visibility.
        // Overridable per panel via CustomFieldsPlugin::make()->sectionModalWidth(...).
        'section_modal_width' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Settings
    |--------------------------------------------------------------------------
    |
    | Configure default settings for custom fields.
    |
    */
    'fields' => [
        'description_max_length' => 255,
    ],

    /*
    |--------------------------------------------------------------------------
    | Section Settings
    |--------------------------------------------------------------------------
    |
    | Configure default settings for custom field sections.
    |
    */
    'sections' => [
        'description_max_length' => 255,
    ],

    /*
    |--------------------------------------------------------------------------
    | Select & Record Lookup Behavior
    |--------------------------------------------------------------------------
    |
    | searchable_threshold controls when option-backed selects render a search
    | box. Set it to 0 to always show one, which is the pre-3.8 behavior.
    |
    | record_lookup governs the record-select field's initial page and search.
    | order_column null means the model's key, which is backed by the primary
    | key index and so costs no more than an unordered query. Naming a column
    | instead (for example 'updated_at' for most-recently-touched-first) is
    | supported, but on a large lookup table an unindexed column makes every
    | render sort the whole tenant, so index it before you switch.
    |
    */
    'selects' => [
        'searchable_threshold' => 10,

        'record_lookup' => [
            'order_column' => null,
            'order_direction' => 'desc',
            'limit' => 50,
            'min_search_length' => 2,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Configuration
    |--------------------------------------------------------------------------
    |
    | Default currency settings for currency field types.
    |
    */
    'currency' => [
        'default_code' => env('CUSTOM_FIELDS_DEFAULT_CURRENCY', 'USD'),

        // Override the currency list (null = auto-detect from PHP intl/ICU).
        // Format: ['USD' => 'US Dollar', 'EUR' => 'Euro', ...]
        'currencies' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Imports
    |--------------------------------------------------------------------------
    |
    | How CSV cells are read during import. Dates and numbers are parsed against a
    | declared convention rather than guessed, so an ambiguous cell like 3/4/2024 has
    | exactly one meaning. The defaults match how this package has always behaved.
    |
    */
    'imports' => [
        // 'iso' (Y-m-d only), 'european' (day first), or 'american' (month first).
        // Every convention also accepts ISO, so picking one only widens what is read.
        'date_format' => env('CUSTOM_FIELDS_IMPORT_DATE_FORMAT', 'iso'),

        // 'point' (1,234.56) or 'comma' (1.234,56).
        'number_format' => env('CUSTOM_FIELDS_IMPORT_NUMBER_FORMAT', 'point'),
    ],

    'database' => [
        'migrations_path' => database_path('custom-fields'),
        'table_names' => [
            'custom_field_sections' => 'custom_field_sections',
            'custom_fields' => 'custom_fields',
            'custom_field_values' => 'custom_field_values',
            'custom_field_options' => 'custom_field_options',
        ],
        'column_names' => [
            'tenant_foreign_key' => 'tenant_id',
        ],
    ],
];
