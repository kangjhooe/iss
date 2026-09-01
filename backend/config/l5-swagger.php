<?php

$swaggerEnabled = filter_var(
    env('L5_SWAGGER_ENABLED', env('APP_ENV', 'production') === 'local'),
    FILTER_VALIDATE_BOOL
);

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Swagger UI & docs routes (nonaktifkan di production)
    |--------------------------------------------------------------------------
    */
    'enabled' => $swaggerEnabled,

    'api' => [
        /*
        |--------------------------------------------------------------------------
        | Edit to set the api's title
        |--------------------------------------------------------------------------
        */
        'title' => env('APP_NAME', 'servr.in') . ' API',
    ],

    'routes' => [
        /*
        |--------------------------------------------------------------------------
        | Route for accessing api documentation interface
        |--------------------------------------------------------------------------
        */
        'api' => 'api/documentation',

        /*
        |--------------------------------------------------------------------------
        | Route for accessing parsed swagger annotations.
        |--------------------------------------------------------------------------
        */
        'docs' => 'docs',

        /*
        |--------------------------------------------------------------------------
        | Middleware allows to prevent unexpected access to API documentation
        |--------------------------------------------------------------------------
        */
        'middleware' => [
            'api' => ['swagger.enabled'],
            'asset' => ['swagger.enabled'],
            'docs' => ['swagger.enabled'],
            'oauth2_callback' => ['swagger.enabled'],
        ],
    ],

    'paths' => [
        /*
        |--------------------------------------------------------------------------
        | Absolute path to location where parsed swagger annotations will be stored
        |--------------------------------------------------------------------------
        */
        'docs' => storage_path('api-docs'),

        /*
        |--------------------------------------------------------------------------
        | File name of the generated json documentation file
        |--------------------------------------------------------------------------
        */
        'docs_json' => 'api-docs.json',

        /*
        |--------------------------------------------------------------------------
        | File name of the generated yaml documentation file
        |--------------------------------------------------------------------------
        */
        'docs_yaml' => 'api-docs.yaml',

        /*
        |--------------------------------------------------------------------------
        | Absolute paths to directory containing the swagger annotations are stored.
        |--------------------------------------------------------------------------
        */
        'annotations' => [
            base_path('app'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API security definitions. Will be generated into Swagger security.
    |--------------------------------------------------------------------------
    */
    'security' => [
        /*
        |--------------------------------------------------------------------------
        | Examples of Security definitions
        |--------------------------------------------------------------------------
        */
        [
            'sanctum' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Set this to `true` in development mode so that docs would be regenerated on each request
    | otherwise caching will be used
    |--------------------------------------------------------------------------
    */
    'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', $swaggerEnabled),

    /*
    |--------------------------------------------------------------------------
    | Set this to `true` to generate a copy of documentation in yaml format
    |--------------------------------------------------------------------------
    */
    'generate_yaml' => false,

    /*
    |--------------------------------------------------------------------------
    | Edit to set the swagger version number
    |--------------------------------------------------------------------------
    */
    'swagger_version' => env('SWAGGER_VERSION', '3.0'),

    /*
    |--------------------------------------------------------------------------
    | Edit to set the swagger version number
    |--------------------------------------------------------------------------
    */
    'swagger_ui_version' => env('SWAGGER_UI_VERSION', '5.17.14'),

    /*
    |--------------------------------------------------------------------------
    | Edit to set the swagger version number
    |--------------------------------------------------------------------------
    */
    'swagger_ui_standalone' => env('SWAGGER_UI_STANDALONE', false),

    /*
    |--------------------------------------------------------------------------
    | Edit to set the swagger version number
    |--------------------------------------------------------------------------
    */
    'swagger_ui_css' => env('SWAGGER_UI_CSS', null),

    /*
    |--------------------------------------------------------------------------
    | Edit to set the swagger version number
    |--------------------------------------------------------------------------
    */
    'swagger_ui_js' => env('SWAGGER_UI_JS', null),
];
