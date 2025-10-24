<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Documentation Title
    |--------------------------------------------------------------------------
    |
    | The title that will be displayed in the API documentation.
    |
    */
    'title' => env('API_DOCS_TITLE', config('app.name') . ' API Documentation'),

    /*
    |--------------------------------------------------------------------------
    | API Documentation Version
    |--------------------------------------------------------------------------
    |
    | The version of your API.
    |
    */
    'version' => env('API_DOCS_VERSION', '1.0.0'),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for your API endpoints.
    |
    */
    'base_url' => env('API_DOCS_BASE_URL', config('app.url') . '/api'),

    /*
    |--------------------------------------------------------------------------
    | API Documentation Routes
    |--------------------------------------------------------------------------
    |
    | Enable or disable the documentation routes.
    |
    */
    'enabled' => env('API_DOCS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix for all API documentation routes.
    |
    */
    'route_prefix' => env('API_DOCS_ROUTE_PREFIX', 'api-docs'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to the documentation routes.
    |
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Route Filters
    |--------------------------------------------------------------------------
    |
    | Filter which routes to include in the documentation.
    |
    */
    'route_filters' => [
        // Only include routes starting with these prefixes
        'include_prefixes' => ['api/'],

        // Exclude routes matching these patterns
        'exclude_patterns' => [
            'sanctum/*',
            'telescope/*',
            'horizon/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Detection
    |--------------------------------------------------------------------------
    |
    | Configure how resources are detected and used.
    |
    */
    'resources' => [
        // Automatically detect and use Laravel Resources for response examples
        'auto_detect' => true,

        // Namespace where resources are located
        'namespace' => 'App\\Http\\Resources',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Schema
    |--------------------------------------------------------------------------
    |
    | Configure database schema reading.
    |
    */
    'database' => [
        // Schema name for PostgreSQL (use 'public' for default)
        'schema' => 'public',

        // Show database schema in documentation
        // Set to false to hide database schema tab (useful in production)
        'show_in_docs' => env('API_DOCS_SHOW_DATABASE_SCHEMA', true),

        // Use actual data from database for response examples
        // Set to false to use generated dummy data instead
        'use_actual_data' => env('API_DOCS_USE_ACTUAL_DATA', true),
    ],
];
