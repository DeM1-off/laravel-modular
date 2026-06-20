<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Module\Attributes;

use Attribute;

/**
 * Declarative container binding on a module's service provider.
 *
 * ```php
 * #[Bind(PostRepositoryInterface::class, EloquentPostRepository::class)]
 * #[Bind(FeedCache::class, RedisFeedCache::class, singleton: true)]
 * final class BlogServiceProvider extends ModuleServiceProvider {}
 * ```
 *
 * Swap the concrete to extract the module into a service later — callers depend
 * only on the abstract, so nothing else changes.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Bind
{
    /**
     * @param  class-string  $abstract
     * @param  class-string  $concrete
     */
    public function __construct(
        public string $abstract,
        public string $concrete,
        public bool $singleton = false,
    ) {}
}
