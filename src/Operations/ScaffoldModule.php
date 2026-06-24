<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Use-case: write a module's directory structure and stub files to disk.
 *
 * Console-free, so scaffolding can be exercised without artisan. Published
 * stubs (stubs/modular) win over the package's own, so projects can customise
 * the generated structure.
 */
final readonly class ScaffoldModule
{
    public function __construct(
        private Filesystem $files,
        private string $packageStubs,
        private string $publishedStubs,
    ) {}

    public function exists(string $path): bool
    {
        return $this->files->isDirectory($path);
    }

    public function execute(string $module, ModuleLayout $layout, string $path, string $namespace, string $vendor): void
    {
        foreach ($layout->dirs as $dir) {
            $this->files->ensureDirectoryExists($path.DIRECTORY_SEPARATOR.$dir);
        }

        $this->writeStubs($path, $module, $layout, $this->replacements($module, $layout, $namespace, $vendor));
    }

    /**
     * @return array<string, string>
     */
    private function replacements(string $module, ModuleLayout $layout, string $namespace, string $vendor): array
    {
        return [
            '{{ module }}' => $module,
            '{{ module_lower }}' => strtolower($module),
            '{{ module_kebab }}' => Str::kebab($module),
            '{{ namespace }}' => $namespace,
            '{{ vendor }}' => $vendor,
            '{{ src_path }}' => $layout->srcPath,
            '{{ provider_namespace }}' => $layout->providerNamespace,
            // JSON manifests need backslashes doubled.
            '{{ provider_fqcn }}' => str_replace('\\', '\\\\', $layout->providerFqcn($module)),
        ];
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function writeStubs(string $path, string $module, ModuleLayout $layout, array $replacements): void
    {
        $map = [
            'composer.json.stub' => 'composer.json',
            'module.json.stub' => 'module.json',
            $layout->providerStub => $layout->providerRelpath,
        ];

        if ($layout->config) {
            $map['config.stub'] = 'config/'.strtolower($module).'.php';
        }

        foreach ($map as $stub => $target) {
            $contents = strtr($this->files->get($this->stubPath($stub)), $replacements);
            $this->files->put($path.DIRECTORY_SEPARATOR.$target, $contents);
        }
    }

    private function stubPath(string $stub): string
    {
        $published = $this->publishedStubs.DIRECTORY_SEPARATOR.$stub;

        return $this->files->isFile($published) ? $published : $this->packageStubs.DIRECTORY_SEPARATOR.$stub;
    }
}
