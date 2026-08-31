<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

/**
 * The shape of a generated module for a given preset — directories, provider
 * location and which stub to use. A value object built from the preset name,
 * so the scaffolder and the command share one source of truth.
 *
 * Presets range from the full DDD scaffold down to `clean`, which writes only a
 * namespace and a provider: every convention folder is opt-in, so a module
 * carries no directory it does not use.
 */
final readonly class ModuleLayout
{
    /**
     * @param  list<string>  $dirs
     */
    public function __construct(
        public string $name,
        public string $srcPath,
        public string $providerNamespace,
        public string $providerRelpath,
        public string $providerStub,
        public bool $config,
        public array $dirs,
    ) {}

    public static function for(string $preset, string $namespace, string $module): self
    {
        $common = ['config', 'database/migrations', 'database/factories', 'database/seeders', 'lang', 'resources/views', 'tests'];

        return match ($preset) {
            'simple' => new self(
                name: 'simple',
                srcPath: 'app/',
                providerNamespace: "{$namespace}\\{$module}\\Providers",
                providerRelpath: "app/Providers/{$module}ServiceProvider.php",
                providerStub: 'provider.stub',
                config: true,
                dirs: ['app/Http/Controllers', 'app/Models', 'app/Providers', 'routes', ...$common],
            ),
            // Nothing but a namespace and a provider. Every convention folder is
            // opt-in: add `routes/` or `lang/` when the module actually needs it
            // (and rebuild `module:cache`, which resolves folders once). Costs
            // nothing at boot and leaves no empty directories in the repo.
            'clean' => new self(
                name: 'clean',
                srcPath: 'src/',
                providerNamespace: "{$namespace}\\{$module}\\Providers",
                providerRelpath: "src/Providers/{$module}ServiceProvider.php",
                providerStub: 'provider-clean.stub',
                config: false,
                dirs: ['src/Providers'],
            ),
            // Thin contracts/shared-kernel module: only interfaces, DTOs, events
            // and enums shared between modules. No bindings, config or database.
            'contracts' => new self(
                name: 'contracts',
                srcPath: 'src/',
                providerNamespace: "{$namespace}\\{$module}\\Providers",
                providerRelpath: "src/Providers/{$module}ServiceProvider.php",
                providerStub: 'provider-contracts.stub',
                config: false,
                dirs: ['src/Contracts', 'src/Data', 'src/Events', 'src/Enums', 'src/Providers'],
            ),
            default => new self(
                name: 'ddd',
                srcPath: 'src/',
                providerNamespace: "{$namespace}\\{$module}\\Infrastructure\\Providers",
                providerRelpath: "src/Infrastructure/Providers/{$module}ServiceProvider.php",
                providerStub: 'provider.stub',
                config: true,
                dirs: ['src/Domain', 'src/Application', 'src/Infrastructure/Providers', ...$common],
            ),
        };
    }

    public function providerFqcn(string $module): string
    {
        return $this->providerNamespace.'\\'.$module.'ServiceProvider';
    }
}
