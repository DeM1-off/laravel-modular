<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular;

use Composer\Autoload\ClassLoader;
use Dem1Off\LaravelModular\Console\Generators\MakeActionCommand;
use Dem1Off\LaravelModular\Console\Generators\MakeControllerCommand;
use Dem1Off\LaravelModular\Console\Generators\MakeMigrationCommand;
use Dem1Off\LaravelModular\Console\Generators\MakeModelCommand;
use Dem1Off\LaravelModular\Console\ModuleCacheCommand;
use Dem1Off\LaravelModular\Console\ModuleClearCommand;
use Dem1Off\LaravelModular\Console\ModuleDisableCommand;
use Dem1Off\LaravelModular\Console\ModuleEnableCommand;
use Dem1Off\LaravelModular\Console\ModuleListCommand;
use Dem1Off\LaravelModular\Console\ModuleMakeCommand;
use Dem1Off\LaravelModular\Console\ModulePromoteCommand;
use Dem1Off\LaravelModular\Manager\ModuleCache;
use Dem1Off\LaravelModular\Manager\ModuleManager;
use Dem1Off\LaravelModular\Module\ProvidesScanner;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

final class LaravelModularServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/modules.php', 'modules');

        $this->app->singleton(ModuleManager::class, static function ($app): ModuleManager {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('modules');

            return new ModuleManager(new Filesystem, $config);
        });

        $this->app->singleton(ModuleCache::class, fn ($app): ModuleCache => new ModuleCache(
            new Filesystem,
            $app->bootstrapPath('cache/modular.php'),
        ));

        $this->app->singleton(ProvidesScanner::class, fn (): ProvidesScanner => new ProvidesScanner(new Filesystem));

        $this->loadCompiled();

        if ((bool) config('modules.autoload', true)) {
            $this->registerModuleAutoloading();
        }

        if ($this->shouldAutoDiscover()) {
            $this->registerModuleProviders();
        }
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/modules.php' => config_path('modules.php'),
        ], 'modules-config');

        // Publish stubs so the generated module structure can be customised.
        $this->publishes([
            __DIR__.'/../stubs' => base_path('stubs/modular'),
        ], 'modules-stubs');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ModuleMakeCommand::class,
                ModuleListCommand::class,
                ModuleEnableCommand::class,
                ModuleDisableCommand::class,
                ModulePromoteCommand::class,
                ModuleCacheCommand::class,
                ModuleClearCommand::class,
                MakeControllerCommand::class,
                MakeModelCommand::class,
                MakeActionCommand::class,
                MakeMigrationCommand::class,
            ]);
        }

        // Run module:cache/clear alongside `php artisan optimize`/`optimize:clear`.
        // @phpstan-ignore function.alreadyNarrowedType (optimizes() is absent on older Laravel 11)
        if (method_exists($this, 'optimizes')) {
            $this->optimizes('module:cache', 'module:clear', 'modules');
        }
    }

    /**
     * Load the compiled cache, if present, so a production request does no
     * filesystem scanning (modules) and no attribute reflection (settings).
     */
    private function loadCompiled(): void
    {
        $cache = $this->app->make(ModuleCache::class);

        if (! $cache->exists()) {
            return;
        }

        $data = $cache->load();

        $this->app->instance('modular.compiled', $data);
        $this->app->make(ModuleManager::class)->useCompiled($data['modules']);
    }

    /**
     * Register each discovered module's PSR-4 namespace on the Composer loader,
     * so a module works by just existing — no Composer package, no root PSR-4.
     */
    private function registerModuleAutoloading(): void
    {
        $autoload = $this->app->basePath('vendor/autoload.php');

        if (! is_file($autoload)) {
            return;
        }

        /** @var ClassLoader $loader */
        $loader = require $autoload;

        /** @var string $namespace */
        $namespace = config('modules.namespace');
        /** @var string $appFolderConfig */
        $appFolderConfig = config('modules.paths.app_folder', 'src/');
        $appFolder = trim($appFolderConfig, '/');

        foreach ($this->app->make(ModuleManager::class)->all() as $module) {
            $root = $namespace.'\\'.$module->name.'\\';

            $loader->addPsr4($root, $module->path.'/'.$appFolder.'/');
            $loader->addPsr4($root.'Database\\Factories\\', $module->path.'/database/factories/');
            $loader->addPsr4($root.'Database\\Seeders\\', $module->path.'/database/seeders/');
        }
    }

    /**
     * Register the service providers of every enabled module.
     *
     * Only needed when modules are not wired through Composer path-repositories
     * (Laravel package auto-discovery already registers those). Providers that
     * Laravel has already discovered are skipped, so the two mechanisms can
     * coexist without registering anything twice. Enable/disable is enforced
     * authoritatively by ModuleServiceProvider::isDisabled(), which also covers
     * a disabled module that Composer auto-discovered.
     */
    private function registerModuleProviders(): void
    {
        $manager = $this->app->make(ModuleManager::class);

        foreach ($manager->enabled() as $module) {
            foreach ($module->providers as $provider) {
                if ($this->app->getProvider($provider) === null) {
                    $this->app->register($provider);
                }
            }
        }
    }

    private function shouldAutoDiscover(): bool
    {
        return (bool) config('modules.auto_discover', true);
    }
}
