<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Tests;

use Dem1Off\LaravelModular\LaravelModularServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /** Temp modules directory, unique per test. */
    protected string $modulesPath;

    protected string $statusesFile;

    protected function setUp(): void
    {
        // Created before parent::setUp() so defineEnvironment() can use them.
        $this->modulesPath = sys_get_temp_dir().'/laravel-modular-'.uniqid();
        $this->statusesFile = $this->modulesPath.'/modules_statuses.json';

        (new Filesystem)->ensureDirectoryExists($this->modulesPath);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->modulesPath);

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [LaravelModularServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('modules.paths.modules', $this->modulesPath);
        $app['config']->set('modules.statuses_file', $this->statusesFile);
        // Providers are registered explicitly in tests, not auto-discovered.
        $app['config']->set('modules.auto_discover', false);
    }
}