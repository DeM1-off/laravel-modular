<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console\Generators;

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Shared base for in-module class generators (controllers, models, actions, …).
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

    /** Stub file name in the package's stubs/ directory. */
    abstract protected function stub(): string;

    /** Directory inside the module's app folder, e.g. 'Infrastructure/Http/Controllers'. */
    abstract protected function layerPath(): string;

    /** Namespace appended after the module root, e.g. 'Infrastructure\Http\Controllers'. */
    abstract protected function layerNamespace(): string;

    /** Suffix appended to the class name when missing (e.g. 'Controller'). */
    protected function classSuffix(): string
    {
        return '';
    }

    public function handle(ModuleManager $manager): int
    {
        /** @var string $moduleArg */
        $moduleArg = $this->argument('module');
        $module = Str::studly($moduleArg);

        if (! $manager->has($module)) {
            $this->components->error("Module [{$module}] not found.");

            return self::FAILURE;
        }

        /** @var string $nameArg */
        $nameArg = $this->argument('name');
        $class = $this->qualifyClass($nameArg);
        $appFolder = trim((string) config('modules.paths.app_folder', 'src/'), '/');
        $dir = $manager->path($module).'/'.$appFolder.'/'.$this->layerPath();
        $file = $dir.'/'.$class.'.php';

        if ($this->files->exists($file) && ! $this->option('force')) {
            $this->components->error("{$class} already exists.");

            return self::FAILURE;
        }

        $this->files->ensureDirectoryExists($dir);

        $namespace = config('modules.namespace').'\\'.$module.'\\'.$this->layerNamespace();

        $this->files->put($file, strtr($this->files->get($this->stubPath()), [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $class,
            '{{ module }}' => $module,
        ]));

        $this->components->info("{$class} created in module {$module}.");

        return self::SUCCESS;
    }

    private function qualifyClass(string $name): string
    {
        $class = Str::studly($name);
        $suffix = $this->classSuffix();

        return $suffix !== '' && ! str_ends_with($class, $suffix) ? $class.$suffix : $class;
    }

    private function stubPath(): string
    {
        $published = base_path('stubs/modular/'.$this->stub());

        return is_file($published) ? $published : __DIR__.'/../../../stubs/'.$this->stub();
    }
}
