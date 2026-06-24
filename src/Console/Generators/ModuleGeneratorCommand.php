<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console\Generators;

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Dem1Off\LaravelModular\Operations\ClassLayer;
use Dem1Off\LaravelModular\Operations\GenerateModuleClass;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Thin adapter base for in-module class generators (controllers, models,
 * actions, …): resolve the module, delegate to the GenerateModuleClass
 * use-case. Subclasses only describe their target via a ClassLayer.
 *
 * Targets the DDD layout: classes land under the module's app folder
 * (config `modules.paths.app_folder`, default `src/`) plus a layer sub-path.
 */
abstract class ModuleGeneratorCommand extends Command
{
    public function __construct(protected readonly Filesystem $files)
    {
        parent::__construct();
    }

    /** Where the generated class lives and how it is named. */
    abstract protected function layer(): ClassLayer;

    public function handle(ModuleManager $manager): int
    {
        /** @var string $moduleArg */
        $moduleArg = $this->argument('module');
        $module = Str::studly($moduleArg);

        if (! $manager->has($module)) {
            $this->components->error("Module [{$module}] not found.");

            return self::FAILURE;
        }

        $layer = $this->layer();
        /** @var string $nameArg */
        $nameArg = $this->argument('name');
        $class = $layer->className($nameArg);

        $generate = new GenerateModuleClass(
            $this->files,
            packageStubs: __DIR__.'/../../../stubs',
            publishedStubs: base_path('stubs/modular'),
        );

        $moduleRoot = $manager->path($module);
        $appFolder = (string) config('modules.paths.app_folder', 'src/');

        if ($generate->exists($moduleRoot, $appFolder, $layer, $class) && ! $this->option('force')) {
            $this->components->error("{$class} already exists.");

            return self::FAILURE;
        }

        $namespace = (string) config('modules.namespace');
        $generate->execute($moduleRoot, $appFolder, $namespace, $module, $layer, $class);

        $this->components->info("{$class} created in module {$module}.");

        return self::SUCCESS;
    }
}
