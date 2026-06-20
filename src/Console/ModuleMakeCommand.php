<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

final class ModuleMakeCommand extends Command
{
    protected $signature = 'make:module {name : The studly-cased module name}
        {--layout= : Layout preset: ddd|simple|contracts (defaults to config modules.layout)}
        {--force : Overwrite the module if it already exists}';

    protected $description = 'Scaffold a new module (promotion-ready Composer package)';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(ModuleManager $manager): int
    {
        $module = Str::studly($this->argument('name'));
        $path = config('modules.paths.modules').DIRECTORY_SEPARATOR.$module;

        if ($this->files->isDirectory($path) && ! $this->option('force')) {
            $this->components->error("Module [{$module}] already exists.");

            return self::FAILURE;
        }

        $layout = $this->layout($module);

        $this->createDirectories($path, $layout['dirs']);
        $this->writeStubs($path, $layout, $this->replacements($module, $layout));

        $manager->flush();
        $manager->setStatus($module, true);

        $this->components->info("Module [{$module}] created at {$path}");
        $this->components->bulletList([
            'layout: '.$layout['name'],
            $layout['provider_relpath'],
            'composer.json (type: laravel-module) — ready to promote to a package',
        ]);

        return self::SUCCESS;
    }

    /**
     * @return array{name: string, src_path: string, provider_namespace: string, provider_relpath: string, provider_stub: string, config: bool, dirs: list<string>}
     */
    private function layout(string $module): array
    {
        $namespace = config('modules.namespace');
        $preset = $this->option('layout') ?: config('modules.layout', 'ddd');

        $common = ['config', 'database/migrations', 'database/factories', 'database/seeders', 'resources/views', 'tests'];

        return match ($preset) {
            'simple' => [
                'name' => 'simple',
                'src_path' => 'app/',
                'provider_namespace' => "{$namespace}\\{$module}\\Providers",
                'provider_relpath' => "app/Providers/{$module}ServiceProvider.php",
                'provider_stub' => 'provider.stub',
                'config' => true,
                'dirs' => ['app/Http/Controllers', 'app/Models', 'app/Providers', 'routes', ...$common],
            ],
            // Thin contracts/shared-kernel module: only interfaces, DTOs, events
            // and enums shared between modules. No bindings, config or database.
            'contracts' => [
                'name' => 'contracts',
                'src_path' => 'src/',
                'provider_namespace' => "{$namespace}\\{$module}\\Providers",
                'provider_relpath' => "src/Providers/{$module}ServiceProvider.php",
                'provider_stub' => 'provider-contracts.stub',
                'config' => false,
                'dirs' => ['src/Contracts', 'src/Data', 'src/Events', 'src/Enums', 'src/Providers'],
            ],
            default => [
                'name' => 'ddd',
                'src_path' => 'src/',
                'provider_namespace' => "{$namespace}\\{$module}\\Infrastructure\\Providers",
                'provider_relpath' => "src/Infrastructure/Providers/{$module}ServiceProvider.php",
                'provider_stub' => 'provider.stub',
                'config' => true,
                'dirs' => ['src/Domain', 'src/Application', 'src/Infrastructure/Providers', ...$common],
            ],
        };
    }

    /**
     * @param  array{src_path: string, provider_namespace: string}  $layout
     * @return array<string, string>
     */
    private function replacements(string $module, array $layout): array
    {
        $providerFqcn = $layout['provider_namespace'].'\\'.$module.'ServiceProvider';

        return [
            '{{ module }}' => $module,
            '{{ module_lower }}' => strtolower($module),
            '{{ module_kebab }}' => Str::kebab($module),
            '{{ namespace }}' => config('modules.namespace'),
            '{{ vendor }}' => config('modules.vendor', 'modules'),
            '{{ src_path }}' => $layout['src_path'],
            '{{ provider_namespace }}' => $layout['provider_namespace'],
            // JSON manifests need backslashes doubled.
            '{{ provider_fqcn }}' => str_replace('\\', '\\\\', $providerFqcn),
        ];
    }

    /**
     * @param  list<string>  $dirs
     */
    private function createDirectories(string $path, array $dirs): void
    {
        foreach ($dirs as $dir) {
            $this->files->ensureDirectoryExists($path.DIRECTORY_SEPARATOR.$dir);
        }
    }

    /**
     * @param  array{provider_relpath: string, provider_stub: string, config: bool}  $layout
     * @param  array<string, string>  $replacements
     */
    private function writeStubs(string $path, array $layout, array $replacements): void
    {
        $module = $replacements['{{ module }}'];

        $map = [
            'composer.json.stub' => 'composer.json',
            'module.json.stub' => 'module.json',
            $layout['provider_stub'] => $layout['provider_relpath'],
        ];

        if ($layout['config']) {
            $map['config.stub'] = 'config/'.strtolower($module).'.php';
        }

        foreach ($map as $stub => $target) {
            $contents = strtr($this->files->get($this->stubPath($stub)), $replacements);
            $this->files->put($path.DIRECTORY_SEPARATOR.$target, $contents);
        }
    }

    /**
     * Published stubs (stubs/modular) win over the package's, so the structure
     * can be customised per project.
     */
    private function stubPath(string $stub): string
    {
        $published = base_path('stubs/modular/'.$stub);

        return is_file($published) ? $published : __DIR__.'/../../stubs/'.$stub;
    }
}