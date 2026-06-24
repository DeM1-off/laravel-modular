<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Use-case: write a timestamped migration into a module's database/migrations
 * folder from the migration stub.
 *
 * Console-free, so generation can be exercised without artisan. Published stubs
 * (stubs/modular) win over the package's own, matching ScaffoldModule.
 */
final readonly class GenerateModuleMigration
{
    public function __construct(
        private Filesystem $files,
        private string $packageStubs,
        private string $publishedStubs,
    ) {}

    /**
     * Render the migration stub and write the file. Returns the file path written.
     */
    public function execute(string $moduleRoot, string $name, ?string $table = null): string
    {
        $name = Str::snake($name);
        $table = $table ?: $name;

        $dir = $moduleRoot.'/database/migrations';
        $file = $dir.'/'.date('Y_m_d_His').'_'.$name.'.php';

        $this->files->ensureDirectoryExists($dir);
        $this->files->put($file, strtr($this->files->get($this->stubPath('migration.stub')), [
            '{{ table }}' => $table,
        ]));

        return $file;
    }

    private function stubPath(string $stub): string
    {
        $published = $this->publishedStubs.DIRECTORY_SEPARATOR.$stub;

        return $this->files->isFile($published) ? $published : $this->packageStubs.DIRECTORY_SEPARATOR.$stub;
    }
}
