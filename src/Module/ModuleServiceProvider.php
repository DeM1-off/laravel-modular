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
 * @phpstan-import-type Bindings from AttributeParser
 * @phpstan-import-type Tags from AttributeParser
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

        $wiring = $this->wiring();

        foreach ($wiring['binds'] as $bind) {
            if ($bind['lifetime'] === 'singleton') {
                $this->app->singleton($bind['abstract'], $bind['concrete']);
            } elseif ($bind['lifetime'] === 'scoped') {
                $this->app->scoped($bind['abstract'], $bind['concrete']);
            } else {
                $this->app->bind($bind['abstract'], $bind['concrete']);
            }
        }

        $grouped = [];
        foreach ($wiring['tags'] as $tag) {
            $grouped[$tag['tag']][] = $tag['concrete'];
        }

        foreach ($grouped as $name => $concretes) {
            $this->app->tag($concretes, $name);
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

        foreach ($settings['middleware'] as $middleware) {
            Route::aliasMiddleware($middleware['name'], $middleware['class']);
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
     * Bindings and tags to apply: provider attributes plus #[Provides]/#[Singleton]
     * /#[Scoped] implementations found by scanning. Scanning only runs uncompiled
     * — the compiled cache already folds them into the settings.
     *
     * @return array{binds: Bindings, tags: Tags}
     */
    private function wiring(): array
    {
        $settings = $this->settings();
        $binds = $settings['binds'];
        $tags = $settings['tags'];

        if ($this->compiledSettings() === null && (bool) config('modules.scan_bindings', true)) {
            $scanned = $this->scanProvides();
            $binds = array_merge($binds, $scanned['binds']);
            $tags = array_merge($tags, $scanned['tags']);
        }

        return ['binds' => $binds, 'tags' => $tags];
    }

    /**
     * @return array{binds: Bindings, tags: Tags}
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
