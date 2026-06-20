<?php

declare(strict_types=1);

use Dem1Off\LaravelModular\Manager\ModuleManager;

if (! function_exists('modules')) {
    /**
     * Resolve the module manager, or a single module descriptor when a name
     * is given.
     */
    function modules(?string $name = null): mixed
    {
        $manager = app(ModuleManager::class);

        return $name === null ? $manager : $manager->find($name);
    }
}

if (! function_exists('module_path')) {
    /**
     * Absolute path to a module, optionally to a file/dir inside it.
     */
    function module_path(string $name, string $path = ''): string
    {
        $base = app(ModuleManager::class)->path($name);

        return $path === '' ? $base : $base.DIRECTORY_SEPARATOR.ltrim($path, '/\\');
    }
}

if (! function_exists('isTestingEnvironment')) {
    /**
     * Whether the app is running in any testing environment.
     *
     * Recognises the common testing environment names so a module can gate
     * test-only wiring (e.g. hasTestMigrations()).
     */
    function isTestingEnvironment(): bool
    {
        return app()->environment(['testing', 'testing-local', 'testing-ci']);
    }
}
