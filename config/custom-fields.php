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
        'conditional_visibility' => [
            'enabled' => true,
        ],
        'encryption' => [
            'enabled' => true,
        ],
        'select_option_colors' => [
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
    | Entity Management Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how entities (models that can have custom fields) are
    | discovered, registered, and managed throughout the system.
    |
    */
    'entity_management' => [
        /*
        | Enable automatic discovery of entities from configured paths
        | and Filament Resources. When disabled, only manually registered
        | entities will be available.
        */
        'auto_discover_entities' => env('CUSTOM_FIELDS_AUTO_DISCOVER_ENTITIES', true),

        /*
        | Directories to scan for models implementing HasCustomFields.
        | All models in these directories will be automatically discovered.
        */
        'entity_discovery_paths' => [
            app_path('Models'),
        ],

        /*
        | Namespaces to scan for entity models.
        | Used when discovery paths are not sufficient.
        */
        'entity_discovery_namespaces' => [
            'App\\Models',
        ],

        /*
        | Enable caching of discovered entities for better performance.
        | Disable during development for immediate updates.
        */
        'cache_entities' => env('CUSTOM_FIELDS_CACHE_ENTITIES', true),

        /*
        | Models to exclude from automatic discovery.
        | These models will not be available as entities even if they
        | implement HasCustomFields.
        */
        'excluded_models' => [
            // App\Models\SystemModel::class,
        ],

        /*
        | Manually registered entities.
        | Use this to register entities without Resources or to override
        | auto-discovered configuration.
        |
        | Example:
        | 'posts' => [
        |     'modelClass' => \App\Models\Post::class,
        |     'labelSingular' => 'Post',
        |     'labelPlural' => 'Posts',
        |     'icon' => 'heroicon-o-document-text',
        |     'primaryAttribute' => 'title',
        |     'searchAttributes' => ['title', 'content'],
        |     'features' => ['custom_fields', 'lookup_source'],
        |     'priority' => 10,
        | ],
        */
        'entities' => [
            //
        ],
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

    /*
    |--------------------------------------------------------------------------
    | Custom Field Type Discovery
    |--------------------------------------------------------------------------
    |
    | Configure how custom field types are discovered and registered.
    | This allows extending the package with custom field types without
    | modifying core files.
    |
    */
    'field_type_discovery' => [
        /*
        | Directories to scan for custom field type definitions.
        | All PHP files in these directories will be scanned for classes
        | implementing FieldTypeDefinitionInterface.
        */
        'directories' => [
            // app_path('CustomFields/Types'),
        ],

        /*
        | Namespaces to scan for custom field type definitions.
        | Uses composer's PSR-4 autoloader to locate directories.
        */
        'namespaces' => [
            // 'App\\CustomFields\\Types',
        ],

        /*
        | Explicitly registered field type classes.
        | These classes will be loaded directly without scanning.
        */
        'classes' => [
            //            App\CustomFields\Types\RatingFieldType::class,
        ],

        /*
        | Enable/disable automatic discovery.
        | When disabled, only explicitly registered classes are loaded.
        */
        'enabled' => true,

        /*
        | Cache discovery results for better performance.
        | Set to false during development for immediate updates.
        */
        'cache' => false,

        /*
        | Cache duration in minutes.
        */
        'cache_duration' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Field Type Settings
    |--------------------------------------------------------------------------
    |
    | Global settings that affect custom field type behavior.
    |
    */
    'custom_field_types' => [
        /*
        | Default priority for custom field types.
        | Lower numbers appear first in the admin panel.
        */
        'default_priority' => 200,

        /*
        | Validation settings for custom field types.
        */
        'validation' => [
            'strict_mode' => true,
            'validate_component_interfaces' => true,
        ],
    ],
];
