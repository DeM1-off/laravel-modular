<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Module;

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Base provider every module extends.
 *
 * Loading is convention-first: config, migrations, views and routes load
 * automatically when their folders exist. Wiring is attribute-driven —
 * #[Bind], #[Listen] and an optional #[Module] override.
 *
 * Fast by design: attribute settings come from the compiled cache when present
 * (`module:cache`), so a production request does zero reflection. Without a
 * cache it reflects once per provider and memoises the result.
 *
 * @phpstan-import-type Settings from AttributeParser
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /** @var Settings|null */
    private ?array $settings = null;

    private ?string $name = null;

    private ?bool $disabled = null;

    public function register(): void
    {
        if ($this->isDisabled()) {
            return;
        }

        foreach ($this->bindings() as $bind) {
            $bind['singleton']
                ? $this->app->singleton($bind['abstract'], $bind['concrete'])
                : $this->app->bind($bind['abstract'], $bind['concrete']);
        }
    }

    public function boot(): void
    {
        if ($this->isDisabled()) {
            return;
        }

        $settings = $this->settings();

        if ($settings['config']) {
            $this->loadConfig();
        }

        if ($settings['migrations']) {
            $this->loadMigrations();
        }

        if ($settings['views']) {
            $this->loadViews();
        }

        if ($settings['routes']) {
            $this->loadRoutes();
        }

        foreach ($settings['listens'] as $listen) {
            Event::listen($listen['event'], $listen['listener']);
        }

        if ($settings['commands'] !== [] && $this->app->runningInConsole()) {
            $this->commands($settings['commands']);
        }
    }

    private function loadConfig(): void
    {
        $dir = module_path($this->name(), 'config');
        $file = $dir.'/'.$this->lower().'.php';

        if (! is_file($file)) {
            $file = $dir.'/config.php';
        }

        if (is_file($file)) {
            $this->mergeConfigFrom($file, Str::kebab($this->name()));
        }
    }

    private function loadMigrations(): void
    {
        $path = module_path($this->name(), 'database/migrations');

        if (is_dir($path)) {
            $this->loadMigrationsFrom($path);
        }
    }

    private function loadViews(): void
    {
        $path = module_path($this->name(), 'resources/views');

        if (is_dir($path)) {
            $this->loadViewsFrom($path, $this->lower());
            $this->publishes([$path => resource_path('views/modules/'.$this->lower())], 'modules-views');
        }
    }

    private function loadRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $web = module_path($this->name(), 'routes/web.php');

        if (is_file($web)) {
            Route::middleware('web')->group($web);
        }

        $api = module_path($this->name(), 'routes/api.php');

        if (is_file($api)) {
            Route::middleware('api')->prefix('api')->group($api);
        }
    }

    /**
     * Container bindings to apply: provider #[Bind] attributes, plus #[Provides]
     * implementations discovered by scanning. Scanning only runs uncompiled —
     * the compiled cache already folds #[Provides] into the settings.
     *
     * @return list<array{abstract: class-string, concrete: class-string, singleton: bool}>
     */
    private function bindings(): array
    {
        $binds = $this->settings()['binds'];

        if ($this->compiledSettings() === null && (bool) config('modules.scan_bindings', true)) {
            $binds = array_merge($binds, $this->scanProvides());
        }

        return $binds;
    }

    /**
     * @return list<array{abstract: class-string, concrete: class-string, singleton: bool}>
     */
    private function scanProvides(): array
    {
        /** @var string $namespace */
        $namespace = config('modules.namespace');
        /** @var string $appFolder */
        $appFolder = config('modules.paths.app_folder', 'src/');

        return $this->app->make(ProvidesScanner::class)->scan(
            module_path($this->name()),
            $namespace.'\\'.$this->name(),
            $appFolder,
        );
    }

    /**
     * @return Settings
     */
    private function settings(): array
    {
        return $this->settings ??= $this->compiledSettings() ?? AttributeParser::parse(static::class);
    }

    /**
     * @return Settings|null
     */
    private function compiledSettings(): ?array
    {
        if (! $this->app->bound('modular.compiled')) {
            return null;
        }

        return $this->app->make('modular.compiled')['settings'][static::class] ?? null;
    }

    private function name(): string
    {
        return $this->name ??= $this->settings()['name']
            ?? str_replace('ServiceProvider', '', class_basename(static::class));
    }

    private function lower(): string
    {
        return strtolower($this->name());
    }

    private function isDisabled(): bool
    {
        return $this->disabled ??= $this->app->bound(ModuleManager::class)
            && ! $this->app->make(ModuleManager::class)->isEnabled($this->name());
    }
}
