<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Manager;

/**
 * Immutable view of a single discovered module.
 */
final readonly class ModuleDescriptor
{
    /**
     * @param  list<class-string>  $providers
     */
    public function __construct(
        public string $name,
        public string $path,
        public bool $enabled,
        public array $providers,
        public ?string $alias = null,
        public ?string $description = null,
    ) {}

    public function path(string $sub = ''): string
    {
        return $sub === '' ? $this->path : $this->path.DIRECTORY_SEPARATOR.ltrim($sub, '/\\');
    }
}