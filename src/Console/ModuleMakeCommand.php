<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Dem1Off\LaravelModular\Operations\ModuleLayout;
use Dem1Off\LaravelModular\Operations\ScaffoldModule;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Thin adapter: resolve the layout, delegate to the ScaffoldModule use-case,
 * register the new module. The scaffolding itself lives in the Operations layer.
 */
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
        /** @var string $name */
        $name = $this->argument('name');
        $module = Str::studly($name);
        $path = config('modules.paths.modules').DIRECTORY_SEPARATOR.$module;

        $scaffold = new ScaffoldModule(
            $this->files,
            packageStubs: __DIR__.'/../../stubs',
            publishedStubs: base_path('stubs/modular'),
        );

        if ($scaffold->exists($path) && ! $this->option('force')) {
            $this->components->error("Module [{$module}] already exists.");

            return self::FAILURE;
        }

        /** @var string $preset */
        $preset = $this->option('layout') ?: config('modules.layout', 'ddd');
        $namespace = (string) config('modules.namespace');
        $vendor = (string) config('modules.vendor', 'modules');

        $layout = ModuleLayout::for($preset, $namespace, $module);
        $scaffold->execute($module, $layout, $path, $namespace, $vendor);

        $manager->flush();
        $manager->setStatus($module, true);

        $this->components->info("Module [{$module}] created at {$path}");
        $this->components->bulletList([
            'layout: '.$layout->name,
            $layout->providerRelpath,
            'composer.json (type: laravel-module) — ready to promote to a package',
        ]);

        return self::SUCCESS;
    }
}
