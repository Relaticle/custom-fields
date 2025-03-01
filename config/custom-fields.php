<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | This section controls the features of the Custom Fields package.
    | You can enable or disable features as needed.
    |
    */
    'features' => [
        'encryption' => [
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Entity Resources Customization
    |--------------------------------------------------------------------------
    |
    | This section allows you to customize the behavior of entity resources,
    | such as enabling table column toggling and setting default visibility.
    |
    */
    'resource' => [
        'table' => [
            'columns' => [
                'enabled' => true,
            ],
            'columns_toggleable' => [
                'enabled' => true,
                'user_control' => true,
                'hidden_by_default' => true,
            ],
            'filters' => [
                'enabled' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Field Types Configuration
    |--------------------------------------------------------------------------
    |
    | This section controls the Custom Field Types.
    | This allows you to customize the behavior of the field types.
    |
    */
    'field_types_configuration' => [
        'date' => [
            'native' => false,
            'format' => 'Y-m-d',
            'display_format' => null,
        ],
        'date_time' => [
            'native' => false,
            'format' => 'Y-m-d H:i:s',
            'display_format' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Fields Resource Configuration
    |--------------------------------------------------------------------------
    |
    | This section controls the Custom Fields resource.
    | This allows you to customize the behavior of the resource.
    |
    */
    'custom_fields_resource' => [
        'should_register_navigation' => true,
        'slug' => 'custom-fields',
        'navigation_sort' => -1,
        'navigation_group' => true,
        'cluster' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Entity Resources Configuration
    |--------------------------------------------------------------------------
    |
    | This section controls which Filament resources are allowed or disallowed
    | to have custom fields. You can specify allowed resources, disallowed
    | resources, or leave them empty to use default behavior.
    |
    */
    'allowed_entity_resources' => [
        // App\Filament\Resources\UserResource::class,
    ],

    'disallowed_entity_resources' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Lookup Resources Configuration
    |--------------------------------------------------------------------------
    |
    | Define which Filament resources can be used as lookups. You can specify
    | allowed resources, disallowed resources, or leave them empty to use
    | default behavior.
    |
    */
    'allowed_lookup_resources' => [
        //
    ],

    'disallowed_lookup_resources' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Awareness Configuration
    |--------------------------------------------------------------------------
    |
    | When enabled, this feature implements multi-tenancy using the specified
    | tenant foreign key. Enable this before running migrations to automatically
    | register the tenant foreign key.
    |
    */
    'tenant_aware' => false,

    /*
    |--------------------------------------------------------------------------
    | Database Migrations Paths
    |--------------------------------------------------------------------------
    |
    | In these directories custom fields migrations will be stored and ran when migrating. A custom fields
    | migration created via the make:custom-fields-migration command will be stored in the first path or
    | a custom defined path when running the command.
    |
    */
    'migrations_paths' => [
        database_path('custom-fields'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Table Names
    |--------------------------------------------------------------------------
    |
    | You can specify custom table names for the package's database tables here.
    | These tables will be used to store custom fields, their values, and options.
    |
    */
    'table_names' => [
        'custom_field_sections' => 'custom_field_sections',
        'custom_fields' => 'custom_fields',
        'custom_field_values' => 'custom_field_values',
        'custom_field_options' => 'custom_field_options',
    ],

    /*
    |--------------------------------------------------------------------------
    | Column Names
    |--------------------------------------------------------------------------
    |
    | Here you can customize the names of specific columns used by the package.
    | For example, you can change the name of the tenant foreign key if needed.
    |
    */
    'column_names' => [
        'tenant_foreign_key' => 'tenant_id',
    ],
];
