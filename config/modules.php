<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Module namespace
    |--------------------------------------------------------------------------
    |
    | Root PSR-4 namespace under which every module lives. A module keeps the
    | exact same namespace whether it lives in this app or, later, as a
    | standalone Composer package. That is what makes promotion zero-churn.
    |
    */
    'namespace' => 'Modules',

    /*
    |--------------------------------------------------------------------------
    | Layout preset
    |--------------------------------------------------------------------------
    |
    | Shape of a generated module:
    |  - 'ddd'    : src/{Domain,Application,Infrastructure}; provider in
    |               Infrastructure/Providers. Enforces layer boundaries.
    |  - 'simple' : app/{Http,Models,Providers}; a flat Laravel-style layout,
    |               lighter for straightforward CRUD modules.
    |
    | Affects make:module scaffolding only; existing modules are untouched.
    |
    */
    'layout' => 'ddd',

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    */
    'paths' => [
        // Directory that holds the in-app modules (path-repository root).
        'modules' => base_path('Modules'),

        // Folder inside a module that maps to its root namespace.
        'app_folder' => 'src/',

        // Generator sub-paths used by make:* commands (DDD layout).
        'generator' => [
            'provider' => 'src/Infrastructure/Providers',
            'config' => 'config',
            'migration' => 'database/migrations',
            'factory' => 'database/factories',
            'seeder' => 'database/seeders',
            'views' => 'resources/views',
            'tests' => 'tests',
            'domain' => 'src/Domain',
            'application' => 'src/Application',
            'infrastructure' => 'src/Infrastructure',
            'component-class' => [
                'path' => 'src/Infrastructure/View/Components',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Statuses file
    |--------------------------------------------------------------------------
    |
    | JSON map of module-name => bool that gates which modules boot. Uses the
    | conventional modules_statuses.json file, so an existing project migrates
    | without touching it.
    |
    */
    'statuses_file' => base_path('modules_statuses.json'),

    /*
    |--------------------------------------------------------------------------
    | Manifest file
    |--------------------------------------------------------------------------
    |
    | Per-module metadata file. Optional: a module that ships only a
    | composer.json (extra.laravel.providers) works too, so a promoted package
    | needs no module.json.
    |
    */
    'manifest_file' => 'module.json',

    /*
    |--------------------------------------------------------------------------
    | Composer vendor for module packages
    |--------------------------------------------------------------------------
    |
    | Used by generators when scaffolding a module's composer.json
    | (e.g. "acme" -> "acme/blog-module").
    |
    */
    'vendor' => 'modules',

    /*
    |--------------------------------------------------------------------------
    | Auto-discovery
    |--------------------------------------------------------------------------
    |
    | When true, the package scans the modules path and registers each enabled
    | module's service providers itself. Set to false when modules are wired
    | through Composer path-repositories + Laravel package auto-discovery and
    | you only want this package for the runtime facade and generators.
    |
    */
    'auto_discover' => true,

    /*
    |--------------------------------------------------------------------------
    | Runtime autoloading
    |--------------------------------------------------------------------------
    |
    | When true, each discovered module's PSR-4 namespace is registered at
    | runtime, so a module works by just existing in the modules directory — no
    | Composer package and no entry in the app's root composer.json. Set to false
    | if you autoload modules through Composer (a path repository or root PSR-4)
    | for an optimised classmap.
    |
    */
    'autoload' => true,

    /*
    |--------------------------------------------------------------------------
    | Scan #[Provides] bindings
    |--------------------------------------------------------------------------
    |
    | When true, a module's classes are scanned for the #[Provides] attribute
    | and auto-bound. Scanning runs only when the compiled cache is absent
    | (development); `module:cache` bakes the result in for production. Disable
    | if you prefer to declare every binding with #[Bind] on the provider.
    |
    */
    'scan_bindings' => true,

    /*
    |--------------------------------------------------------------------------
    | Scan for artisan commands
    |--------------------------------------------------------------------------
    |
    | When true, Illuminate\Console\Command subclasses inside a module's
    | `Console` directories (any depth under the app folder) are registered
    | automatically. Scanning runs only on console boots and when the compiled
    | cache is absent (development); `module:cache` bakes the result in for
    | production. Commands outside a Console directory are declared explicitly
    | with #[Module(commands: [...])].
    |
    */
    'scan_commands' => true,

    /*
    |--------------------------------------------------------------------------
    | Module boundaries
    |--------------------------------------------------------------------------
    |
    | Sub-namespaces of a module that other modules may reference — its public
    | surface. `module:check --boundaries` flags any cross-module reference
    | outside this list (reaching into another module's internals) and, when a
    | module declares `requires` in module.json, any undeclared dependency.
    |
    */
    'boundaries' => [
        'allowed' => ['Contracts', 'Data', 'Events', 'Enums'],
    ],
];
