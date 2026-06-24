<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

/**
 * Outcome of a link/unlink operation — what changed, for the console to render.
 */
final readonly class LinkResult
{
    /**
     * @param  list<string>  $packages  Package names that were (un)linked.
     * @param  list<string>  $skipped  Module names skipped (no composer.json).
     */
    public function __construct(
        public array $packages,
        public array $skipped = [],
        public ?string $repositoryUrl = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->packages === [];
    }
}
