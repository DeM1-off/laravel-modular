<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Module;

use Illuminate\Filesystem\Filesystem;

/**
 * Signature-keyed memo file for the development scanners.
 *
 * Without the compiled cache the scanners run per boot, so their results are
 * memoised to one small PHP file keyed by the scanner and the module's change
 * signature. Unchanged modules are never re-reflected. Both scanners share the
 * one file, so `module:clear` has a single thing to remove.
 *
 * A null path disables persistence entirely — that is the shape the unit tests
 * and one-shot compile runs use.
 */
final class ScanCache
{
    /** @var array<string, array{signature: string, result: array<mixed>}>|null */
    private ?array $entries = null;

    public function __construct(
        private readonly Filesystem $files,
        private readonly ?string $path = null,
    ) {}

    /**
     * The memoised result for $key, or null when absent or stale.
     *
     * @return array<mixed>|null
     */
    public function get(string $key, string $signature): ?array
    {
        $entry = $this->load()[$key] ?? null;

        return $entry !== null && $entry['signature'] === $signature ? $entry['result'] : null;
    }

    /**
     * @param  array<mixed>  $result
     */
    public function put(string $key, string $signature, array $result): void
    {
        if ($this->path === null) {
            return;
        }

        $entries = $this->load();
        $entries[$key] = ['signature' => $signature, 'result' => $result];
        $this->entries = $entries;

        $this->files->ensureDirectoryExists(dirname($this->path));
        $this->files->put($this->path, '<?php return '.var_export($entries, true).';'.PHP_EOL);
    }

    public function clear(): void
    {
        $this->entries = null;

        if ($this->path !== null && $this->files->exists($this->path)) {
            $this->files->delete($this->path);
        }
    }

    /**
     * @return array<string, array{signature: string, result: array<mixed>}>
     */
    private function load(): array
    {
        if ($this->entries !== null) {
            return $this->entries;
        }

        if ($this->path === null || ! $this->files->exists($this->path)) {
            return $this->entries = [];
        }

        /** @var array<string, array{signature: string, result: array<mixed>}> $entries */
        $entries = require $this->path;

        return $this->entries = $entries;
    }
}
