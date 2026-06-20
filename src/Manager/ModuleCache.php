<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Manager;

use Illuminate\Filesystem\Filesystem;

/**
 * Reads and writes the compiled modules cache — one PHP file of discovered
 * modules plus parsed provider settings. When it exists, a request does no
 * filesystem scanning and no attribute reflection.
 */
final class ModuleCache
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $path,
    ) {}

    public function exists(): bool
    {
        return $this->files->exists($this->path);
    }

    /**
     * @return array{modules: array<string, mixed>, settings: array<string, mixed>}
     */
    public function load(): array
    {
        /** @var array{modules: array<string, mixed>, settings: array<string, mixed>} $data */
        $data = require $this->path;

        return $data;
    }

    /**
     * @param  array{modules: array<string, mixed>, settings: array<string, mixed>}  $data
     */
    public function write(array $data): void
    {
        $this->files->ensureDirectoryExists(dirname($this->path));
        $this->files->put($this->path, '<?php return '.var_export($data, true).';'.PHP_EOL);
    }

    public function clear(): void
    {
        if ($this->exists()) {
            $this->files->delete($this->path);
        }
    }

    public function path(): string
    {
        return $this->path;
    }
}
