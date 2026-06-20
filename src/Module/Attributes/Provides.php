<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Module\Attributes;

use Attribute;

/**
 * Auto-binding on the implementation itself. The class declares what it
 * provides; the module binds it — no entry on the service provider needed.
 *
 * ```php
 * #[Provides(PostRepositoryInterface::class)]
 * final class EloquentPostRepository implements PostRepositoryInterface {}
 * ```
 *
 * Omit the abstract to infer it when the class implements exactly one interface:
 *
 * ```php
 * #[Provides]
 * final class EloquentPostRepository implements PostRepositoryInterface {}
 * ```
 *
 * Discovered by scanning in development and baked into the compiled cache by
 * `module:cache`, so production pays nothing.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Provides
{
    /**
     * @param  class-string|null  $abstract
     */
    public function __construct(
        public ?string $abstract = null,
        public bool $singleton = false,
    ) {}
}
