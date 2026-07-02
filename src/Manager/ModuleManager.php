<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Manager;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

/**
 * Discovers modules on disk and answers questions about them.
 *
 * Hybrid model: a module is a real Composer package (path-repository), so its
 * PSR-4 autoload and provider auto-discovery come from its own composer.json.
 * On top of that, modules_statuses.json gates which modules are *enabled*.
 * Both a module.json manifest and a plain composer.json are understood, so an
 * in-app module and a promoted standalone package both work.
 *
 * @phpstan-type Config array{namespace:string,paths:array{modules:string,app_folder:string},statuses_file:string,manifest_file:string}
 */
final class ModuleManager
{
    /** @var array<string, ModuleDescriptor>|null */
    private ?array $cache = null;

    /** @var array<string, bool>|null */
    private ?array $statuses = null;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly Filesystem $files,
        private readonly array $config,
    ) {}

    /**
     * All modules, enabled or not, keyed by name.
     *
     * @return array<string, ModuleDescriptor>
     */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $path = $this->modulesPath();

        if (! $this->files->isDirectory($path)) {
            return $this->cache = [];
        }

        $modules = [];

        foreach ($this->files->directories($path) as $directory) {
            $descriptor = $this->describe($directory);
            $modules[$descriptor->name] = $descriptor;
        }

        // Higher priority first, then alphabetical — controls provider load order.
        uasort($modules, static fn (ModuleDescriptor $a, ModuleDescriptor $b): int => [$b->priority, $a->name] <=> [$a->priority, $b->name]);

        // A `requires` declaration wins over priority: dependencies load first.
        return $this->cache = $this->resolveOrder($modules);
    }

    /**
     * Modules that declare the given module in their `requires`.
     *
     * @return list<string>
     */
    public function dependents(string $name): array
    {
        $dependents = [];

        foreach ($this->all() as $module) {
            if (in_array($name, $module->requires, true)) {
                $dependents[] = $module->name;
            }
        }

        return $dependents;
    }

    /**
     * Only enabled modules, keyed by name.
     *
     * @return array<string, ModuleDescriptor>
     */
    public function enabled(): array
    {
        return array_filter($this->all(), static fn (ModuleDescriptor $m): bool => $m->enabled);
    }

    public function find(string $name): ?ModuleDescriptor
    {
        return $this->all()[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return $this->find($name) !== null;
    }

    public function isEnabled(string $name): bool
    {
        $module = $this->find($name);

        return $module !== null && $module->enabled;
    }

    /**
     * Absolute path to a module (throws if unknown — fail loud, not silent).
     */
    public function path(string $name): string
    {
        $module = $this->find($name);

        if ($module === null) {
            throw new RuntimeException("Module [{$name}] is not registered.");
        }

        return $module->path;
    }

    /**
     * Enable or disable a module by writing the statuses file.
     */
    public function setStatus(string $name, bool $enabled): void
    {
        $file = $this->config['statuses_file'];

        $statuses = $this->files->exists($file) ? $this->readJson($file) : [];
        $statuses[$name] = $enabled;

        $this->files->put(
            $file,
            json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );

        $this->flush();
    }

    /**
     * Drop and rebuild the discovery cache (used after generating a module).
     */
    public function flush(): void
    {
        $this->cache = null;
        $this->statuses = null;
    }

    /**
     * Hydrate from the compiled cache so all()/enabled() skip the filesystem.
     *
     * @param  array<string, array{name: string, path: string, enabled: bool, providers: list<class-string>, alias?: string|null, description?: string|null, priority?: int, requires?: list<string>}>  $modules
     */
    public function useCompiled(array $modules): void
    {
        $descriptors = [];

        foreach ($modules as $name => $module) {
            $descriptors[$name] = new ModuleDescriptor(
                name: $module['name'],
                path: $module['path'],
                enabled: $module['enabled'],
                providers: $module['providers'],
                alias: $module['alias'] ?? null,
                description: $module['description'] ?? null,
                priority: $module['priority'] ?? 0,
                requires: $module['requires'] ?? [],
            );
        }

        $this->cache = $descriptors;
    }

    /**
     * Serialisable array form of every module, for the compiled cache.
     *
     * @return array<string, array{name: string, path: string, enabled: bool, providers: list<class-string>, alias: string|null, description: string|null, priority: int, requires: list<string>}>
     */
    public function toArray(): array
    {
        $modules = [];

        foreach ($this->all() as $module) {
            $modules[$module->name] = [
                'name' => $module->name,
                'path' => $module->path,
                'enabled' => $module->enabled,
                'providers' => $module->providers,
                'alias' => $module->alias,
                'description' => $module->description,
                'priority' => $module->priority,
                'requires' => $module->requires,
            ];
        }

        return $modules;
    }

    private function describe(string $directory): ModuleDescriptor
    {
        $name = basename($directory);
        $manifest = $this->readManifest($directory);
        $composer = $this->readComposer($directory);

        $providers = $manifest['providers']
            ?? ($composer['extra']['laravel']['providers'] ?? []);

        return new ModuleDescriptor(
            name: $manifest['name'] ?? $name,
            path: $directory,
            enabled: $this->statusOf($manifest['name'] ?? $name),
            providers: array_values($providers),
            alias: $manifest['alias'] ?? null,
            description: $manifest['description'] ?? ($composer['description'] ?? null),
            priority: (int) ($manifest['priority'] ?? 0),
            requires: array_values((array) ($manifest['requires'] ?? [])),
        );
    }

    /**
     * Stable topological sort: each module's requirements come before it,
     * otherwise the incoming (priority, name) order is preserved. A cycle or an
     * unknown requirement is ignored here — module:check reports those.
     *
     * @param  array<string, ModuleDescriptor>  $modules
     * @return array<string, ModuleDescriptor>
     */
    private function resolveOrder(array $modules): array
    {
        $ordered = [];
        $visiting = [];

        foreach (array_keys($modules) as $name) {
            $this->visit($name, $modules, $ordered, $visiting);
        }

        return $ordered;
    }

    /**
     * @param  array<string, ModuleDescriptor>  $modules
     * @param  array<string, ModuleDescriptor>  $ordered
     * @param  array<string, true>  $visiting
     */
    private function visit(string $name, array $modules, array &$ordered, array &$visiting): void
    {
        if (isset($ordered[$name]) || isset($visiting[$name]) || ! isset($modules[$name])) {
            return;
        }

        $visiting[$name] = true;

        foreach ($modules[$name]->requires as $requirement) {
            $this->visit($requirement, $modules, $ordered, $visiting);
        }

        unset($visiting[$name]);

        $ordered[$name] = $modules[$name];
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifest(string $directory): array
    {
        $file = $directory.DIRECTORY_SEPARATOR.$this->config['manifest_file'];

        return $this->files->exists($file) ? $this->readJson($file) : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function readComposer(string $directory): array
    {
        $file = $directory.DIRECTORY_SEPARATOR.'composer.json';

        return $this->files->exists($file) ? $this->readJson($file) : [];
    }

    /**
     * A module with no status entry defaults to enabled, so freshly generated
     * modules are live without editing the statuses file.
     */
    private function statusOf(string $name): bool
    {
        return $this->loadStatuses()[$name] ?? true;
    }

    /**
     * @return array<string, bool>
     */
    private function loadStatuses(): array
    {
        if ($this->statuses !== null) {
            return $this->statuses;
        }

        $file = $this->config['statuses_file'];

        /** @var array<string, bool> $statuses */
        $statuses = $this->files->exists($file) ? $this->readJson($file) : [];

        return $this->statuses = $statuses;
    }

    /**
     * @return array<string, mixed>
     */
    private function readJson(string $file): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($this->files->get($file), true) ?? [];

        return $decoded;
    }

    private function modulesPath(): string
    {
        return $this->config['paths']['modules'];
    }
}
