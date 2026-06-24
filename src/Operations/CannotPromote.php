<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

use RuntimeException;

/**
 * Raised when a module cannot be promoted (e.g. it ships no composer.json).
 */
final class CannotPromote extends RuntimeException
{
    public static function missingComposer(string $module): self
    {
        return new self("Module [{$module}] has no composer.json — it cannot be promoted.");
    }
}
