<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

/**
 * One row of a sync report: a module, its Composer package, the constraint the
 * app pins it to, and the version currently installed (from composer.lock).
 */
final readonly class SyncEntry
{
    public function __construct(
        public string $module,
        public string $package,
        public ?string $constraint,
        public ?string $installed,
    ) {}

    /** A module is syncable only when the app actually requires its package. */
    public function isManaged(): bool
    {
        return $this->constraint !== null;
    }
}
