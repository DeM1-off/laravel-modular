<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Module\Attributes;

use Attribute;

/**
 * Auto-binding shorthand: bind the implementation as scoped (one instance per
 * request/job lifecycle). Equivalent to a container scoped() binding.
 *
 * ```php
 * #[Scoped]
 * final class RequestContext implements Context {}
 * ```
 *
 * Omit the abstract to infer it from a single implemented interface.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Scoped
{
    /**
     * @param  class-string|null  $abstract
     */
    public function __construct(
        public ?string $abstract = null,
        public ?string $tag = null,
    ) {}
}
