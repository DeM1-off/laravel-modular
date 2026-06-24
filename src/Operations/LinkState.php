<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

use Illuminate\Filesystem\Filesystem;

/**
 * Records the version constraint each package had before it was linked for
 * local development, so unlink can restore it exactly. Persisted as JSON in
 * bootstrap/cache so the round-trip survives across processes.
 *
 * @phpstan-type Entries array<string, array{previous: string|null}>
 */
final class LinkState
{
    /**
     * @param  Entries  $entries
     */
    private function __construct(
        private readonly Filesystem $files,
        private readonly string $path,
        private array $entries,
    ) {}

    public static function load(Filesystem $files, string $path): self
    {
        $entries = $files->exists($path)
            ? (json_decode($files->get($path), true) ?? [])
            : [];

        /** @var Entries $entries */
        return new self($files, $path, $entries);
    }

    /**
     * Record a package's prior constraint — only the first time, so re-linking
     * never overwrites the genuine original with the linked "*".
     */
    public function remember(string $package, ?string $previous): void
    {
        if (! array_key_exists($package, $this->entries)) {
            $this->entries[$package] = ['previous' => $previous];
        }
    }

    public function has(string $package): bool
    {
        return array_key_exists($package, $this->entries);
    }

    public function previousFor(string $package): ?string
    {
        return $this->entries[$package]['previous'] ?? null;
    }

    public function forget(string $package): void
    {
        unset($this->entries[$package]);
    }

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    public function save(): void
    {
        if ($this->entries === []) {
            if ($this->files->exists($this->path)) {
                $this->files->delete($this->path);
            }

            return;
        }

        $this->files->ensureDirectoryExists(dirname($this->path));
        $this->files->put(
            $this->path,
            json_encode($this->entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );
    }
}
