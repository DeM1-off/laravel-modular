<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Module\Attributes;

use Attribute;

/**
 * Optional overrides for a module's service provider.
 *
 * Loading is convention-first: config, migrations, views and routes load
 * automatically when their folders exist. Add this attribute only to override a
 * default — rename the module, turn a loader off, or register commands.
 *
 * ```php
 * #[Module(views: false, commands: [PublishScheduledPosts::class])]
 * final class BlogServiceProvider extends ModuleServiceProvider {}
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Module
{
    /**
     * @param  list<class-string>  $commands
     */
    public function __construct(
        public ?string $name = null,
        public bool $config = true,
        public bool $migrations = true,
        public bool $views = true,
        public bool $routes = true,
        public array $commands = [],
    ) {}
}
