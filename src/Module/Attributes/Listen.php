<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Module\Attributes;

use Attribute;

/**
 * Declaratively register an event listener from a module's service provider.
 *
 * ```php
 * #[Listen(ChapterPublished::class, SendDigest::class)]
 * final class BlogServiceProvider extends ModuleServiceProvider {}
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Listen
{
    /**
     * @param  class-string  $event
     * @param  class-string  $listener
     */
    public function __construct(
        public string $event,
        public string $listener,
    ) {}
}
