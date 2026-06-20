<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Module\Attributes;

use Attribute;

/**
 * Register a route middleware alias from a module's service provider.
 *
 * ```php
 * #[Middleware('blog.subscriber', EnsureSubscriber::class)]
 * final class BlogServiceProvider extends ModuleServiceProvider {}
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Middleware
{
    /**
     * @param  class-string  $class
     */
    public function __construct(
        public string $name,
        public string $class,
    ) {}
}
