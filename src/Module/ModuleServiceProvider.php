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
 * Loading is convention-first: config, migrations, views, routes, translations
 * and console commands load automatically when their folders exist. Wiring is
 * attribute-driven — #[Bind], #[Listen] and an optional #[Module] override.
 *
 * Fast by design: attribute settings *and* the resolved convention paths come
 * from the compiled cache when present (`module:cache`), so a production
 * request does zero reflection and touches the filesystem zero times — a module
 * that ships none of the optional folders costs nothing at boot. Without a
 * cache it reflects and resolves once per provider and memoises the result.
 *
 * @phpstan-import-type Settings from AttributeParser
 * @phpstan-import-type Bindings from AttributeParser
 * @phpstan-import-type Tags from AttributeParser
 * @phpstan-import-type Paths from ModulePaths
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /** @var Settings|null */
    private ?array $settings = null;

    /** @var Paths|null */
    private ?array $paths = null;

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
        $paths = $this->paths();

        if ($paths['config'] !== null) {
            $this->mergeConfigFrom($paths['config'], Str::kebab($this->name()));
        }

        if ($paths['migrations'] !== null) {
            $this->loadMigrationsFrom($paths['migrations']);
        }

        if ($paths['views'] !== null) {
            $this->loadViewsFrom($paths['views'], $this->lower());
        }

        if ($paths['lang'] !== null) {
            $this->loadTranslationsFrom($paths['lang'], $this->lower());
            $this->loadJsonTranslationsFrom($paths['lang']);
        }

        $this->loadRoutes($paths['routes']);

        foreach ($settings['listens'] as $listen) {
            Event::listen($listen['event'], $listen['listener']);
        }

        foreach ($settings['middleware'] as $middleware) {
            Route::aliasMiddleware($middleware['name'], $middleware['class']);
        }

        if ($this->app->runningInConsole()) {
            $this->registerPublishing($paths);

            $commands = $this->consoleCommands($settings['commands']);

            if ($commands !== []) {
                $this->commands($commands);
            }
        }
    }

    /**
     * The module's convention folders, from the compiled cache when present.
     *
     * Uncompiled this resolves once per provider; compiled it is a plain array
     * read, so nothing here stats the filesystem on a production request.
     *
     * @return Paths
     */
    private function paths(): array
    {
        if ($this->paths !== null) {
            return $this->paths;
        }

        $settings = $this->settings();

        return $this->paths = $settings['paths']
            ?? ModulePaths::resolve(module_path($this->name()), $this->name(), $settings);
    }

    /**
     * @param  array{web: string|null, api: string|null}  $routes
     */
    private function loadRoutes(array $routes): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        if ($routes['web'] !== null) {
            Route::middleware('web')->group($routes['web']);
        }

        if ($routes['api'] !== null) {
            Route::middleware('api')->prefix('api')->group($routes['api']);
        }
    }

    /**
     * Publishable paths only matter to `vendor:publish`, so they are declared
     * on console boots only — an HTTP request never builds these arrays.
     *
     * @param  Paths  $paths
     */
    private function registerPublishing(array $paths): void
    {
        if ($paths['views'] !== null) {
            $this->publishes([$paths['views'] => resource_path('views/modules/'.$this->lower())], 'modules-views');
        }

        if ($paths['lang'] !== null) {
            $this->publishes([$paths['lang'] => lang_path('modules/'.$this->lower())], 'modules-lang');
        }
    }

    /**
     * Commands to register: the explicit #[Module(commands:)] list plus classes
     * found by convention in the module's `Console` directories. Discovery only
     * runs uncompiled — the compiled cache already folds them into the settings.
     *
     * @param  list<class-string>  $commands
     * @return list<class-string>
     */
    private function consoleCommands(array $commands): array
    {
        if ($this->compiledSettings() === null && (bool) config('modules.scan_commands', true)) {
            $commands = array_merge($commands, $this->scanCommands());
        }

        return array_values(array_unique($commands));
    }

    /**
     * @return list<class-string>
     */
    private function scanCommands(): array
    {
        /** @var string $namespace */
        $namespace = config('modules.namespace');
        /** @var string $appFolder */
        $appFolder = config('modules.paths.app_folder', 'src/');

        return $this->app->make(CommandScanner::class)->scan(
            module_path($this->name()),
            $namespace.'\\'.$this->name(),
            $appFolder,
        );
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
