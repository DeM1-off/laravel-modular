<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Module;

use Dem1Off\LaravelModular\Module\Attributes\Bind;
use Dem1Off\LaravelModular\Module\Attributes\Listen;
use Dem1Off\LaravelModular\Module\Attributes\Middleware;
use Dem1Off\LaravelModular\Module\Attributes\Module;
use ReflectionClass;

/**
 * Reads a provider's attributes into a plain array.
 *
 * Used live (dev) by ModuleServiceProvider and at compile time by the cache
 * command. The output is pure scalars/arrays, so it serialises straight into
 * the compiled cache — at runtime nothing reflects, it just reads this shape.
 *
 * @phpstan-type Bindings list<array{abstract: class-string, concrete: class-string, lifetime: 'bind'|'singleton'|'scoped'}>
 * @phpstan-type Tags list<array{tag: string, concrete: class-string}>
 * @phpstan-type Settings array{name: string|null, config: bool, migrations: bool, views: bool, routes: bool, lang: bool, commands: list<class-string>, binds: Bindings, listens: list<array{event: class-string, listener: class-string}>, middleware: list<array{name: string, class: class-string}>, tags: Tags}
 */
final class AttributeParser
{
    /**
     * @param  class-string  $providerClass
     * @return Settings
     */
    public static function parse(string $providerClass): array
    {
        $reflection = new ReflectionClass($providerClass);

        $moduleAttributes = $reflection->getAttributes(Module::class);
        $module = $moduleAttributes !== [] ? $moduleAttributes[0]->newInstance() : null;

        $binds = [];
        foreach ($reflection->getAttributes(Bind::class) as $attribute) {
            $bind = $attribute->newInstance();
            $binds[] = [
                'abstract' => $bind->abstract,
                'concrete' => $bind->concrete,
                'lifetime' => $bind->singleton ? 'singleton' : 'bind',
            ];
        }

        $listens = [];
        foreach ($reflection->getAttributes(Listen::class) as $attribute) {
            $listen = $attribute->newInstance();
            $listens[] = ['event' => $listen->event, 'listener' => $listen->listener];
        }

        $middleware = [];
        foreach ($reflection->getAttributes(Middleware::class) as $attribute) {
            $entry = $attribute->newInstance();
            $middleware[] = ['name' => $entry->name, 'class' => $entry->class];
        }

        return [
            'name' => $module?->name,
            'config' => $module === null ? true : $module->config,
            'migrations' => $module === null ? true : $module->migrations,
            'views' => $module === null ? true : $module->views,
            'routes' => $module === null ? true : $module->routes,
            'lang' => $module === null ? true : $module->lang,
            'commands' => $module === null ? [] : $module->commands,
            'binds' => $binds,
            'listens' => $listens,
            'middleware' => $middleware,
            'tags' => [],
        ];
    }
}
