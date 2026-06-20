<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Module\Attributes;

use Attribute;

/**
 * Auto-binding shorthand: bind the implementation as a shared (singleton)
 * instance. Equivalent to #[Provides(singleton: true)].
 *
 * ```php
 * #[Singleton]
 * final class RedisFeedCache implements FeedCache {}
 * ```
 *
 * Omit the abstract to infer it from a single implemented interface.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Singleton
{
    /**
     * @param  class-string|null  $abstract
     */
    public function __construct(
        public ?string $abstract = null,
        public ?string $tag = null,
    ) {}
}
