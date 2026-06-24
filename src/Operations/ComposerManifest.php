<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

use Illuminate\Filesystem\Filesystem;

/**
 * A composer.json file as a mutable value object — the single place that knows
 * how to read, change and write Composer manifests. Console commands never
 * touch the JSON directly; they ask this object for an operation.
 *
 * @phpstan-type Json array<string, mixed>
 */
final class ComposerManifest
{
    /**
     * @param  Json  $data
     */
    private function __construct(
        private readonly Filesystem $files,
        private readonly string $path,
        private array $data,
    ) {}

    public static function load(Filesystem $files, string $path): self
    {
        $data = $files->exists($path)
            ? (json_decode($files->get($path), true) ?? [])
            : [];

        /** @var Json $data */
        return new self($files, $path, $data);
    }

    /**
     * The package name (`vendor/name`) this manifest declares, if any.
     */
    public function name(): ?string
    {
        /** @var string|null $name */
        $name = $this->data['name'] ?? null;

        return $name;
    }

    /**
     * Current version constraint required for a package, or null when absent.
     */
    public function constraintFor(string $package): ?string
    {
        /** @var array<string, mixed> $require */
        $require = $this->data['require'] ?? [];
        /** @var string|null $constraint */
        $constraint = $require[$package] ?? null;

        return $constraint;
    }

    public function requirePackage(string $package, string $constraint): self
    {
        /** @var array<string, mixed> $require */
        $require = $this->data['require'] ?? [];
        $require[$package] = $constraint;
        $this->data['require'] = $require;

        return $this;
    }

    public function removePackage(string $package): self
    {
        /** @var array<string, mixed> $require */
        $require = $this->data['require'] ?? [];
        unset($require[$package]);
        $this->data['require'] = $require;

        return $this;
    }

    public function ensurePathRepository(string $url): self
    {
        $repositories = $this->repositories();

        foreach ($repositories as $repo) {
            if (is_array($repo) && ($repo['type'] ?? null) === 'path' && ($repo['url'] ?? null) === $url) {
                return $this;
            }
        }

        $repositories[] = ['type' => 'path', 'url' => $url, 'options' => ['symlink' => true]];
        $this->data['repositories'] = $repositories;

        return $this;
    }

    public function removePathRepository(string $url): self
    {
        $kept = array_values(array_filter(
            $this->repositories(),
            static fn (mixed $repo): bool => ! (is_array($repo)
                && ($repo['type'] ?? null) === 'path'
                && ($repo['url'] ?? null) === $url),
        ));

        if ($kept === []) {
            unset($this->data['repositories']);
        } else {
            $this->data['repositories'] = $kept;
        }

        return $this;
    }

    public function save(): void
    {
        $this->files->put(
            $this->path,
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
        );
    }

    /**
     * @return Json
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @return list<mixed>
     */
    private function repositories(): array
    {
        return array_values((array) ($this->data['repositories'] ?? []));
    }
}
