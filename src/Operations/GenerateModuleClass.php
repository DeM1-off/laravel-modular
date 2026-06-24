<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

use Illuminate\Filesystem\Filesystem;

/**
 * Use-case: write a single class (action, controller, model, …) into a module's
 * app folder from a stub.
 *
 * Console-free, so generation can be exercised without artisan. Published stubs
 * (stubs/modular) win over the package's own, matching ScaffoldModule.
 */
final readonly class GenerateModuleClass
{
    public function __construct(
        private Filesystem $files,
        private string $packageStubs,
        private string $publishedStubs,
    ) {}

    public function targetFile(string $moduleRoot, string $appFolder, ClassLayer $layer, string $class): string
    {
        return $moduleRoot.'/'.trim($appFolder, '/').'/'.$layer->path.'/'.$class.'.php';
    }

    public function exists(string $moduleRoot, string $appFolder, ClassLayer $layer, string $class): bool
    {
        return $this->files->exists($this->targetFile($moduleRoot, $appFolder, $layer, $class));
    }

    /**
     * Render the stub and write the class file. Returns the file path written.
     */
    public function execute(string $moduleRoot, string $appFolder, string $baseNamespace, string $module, ClassLayer $layer, string $class): string
    {
        $file = $this->targetFile($moduleRoot, $appFolder, $layer, $class);
        $this->files->ensureDirectoryExists(dirname($file));

        $namespace = $baseNamespace.'\\'.$module.'\\'.$layer->namespace;

        $this->files->put($file, strtr($this->files->get($this->stubPath($layer->stub)), [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $class,
            '{{ module }}' => $module,
        ]));

        return $file;
    }

    private function stubPath(string $stub): string
    {
        $published = $this->publishedStubs.DIRECTORY_SEPARATOR.$stub;

        return $this->files->isFile($published) ? $published : $this->packageStubs.DIRECTORY_SEPARATOR.$stub;
    }
}
