<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

use Dem1Off\LaravelModular\Manager\ModuleCache;
use Dem1Off\LaravelModular\Manager\ModuleManager;
use Dem1Off\LaravelModular\Module\AttributeParser;
use Dem1Off\LaravelModular\Module\CommandScanner;
use Dem1Off\LaravelModular\Module\ModuleServiceProvider;
use Dem1Off\LaravelModular\Module\ProvidesScanner;

/**
 * Use-case: compile module discovery and provider attributes into the fast
 * cache, so production requests do zero reflection and zero filesystem scanning.
 * Returns the number of enabled modules compiled.
 */
final readonly class CompileModuleCache
{
    public function __construct(
        private ModuleManager $manager,
        private ModuleCache $cache,
        private ProvidesScanner $scanner,
        private CommandScanner $commands,
    ) {}

    public function execute(string $namespace, string $appFolder): int
    {
        $settings = [];

        foreach ($this->manager->enabled() as $module) {
            $scanned = $this->scanner->scan($module->path, $namespace.'\\'.$module->name, $appFolder);
            $commands = $this->commands->scan($module->path, $namespace.'\\'.$module->name, $appFolder);

            foreach ($module->providers as $provider) {
                if (class_exists($provider) && is_subclass_of($provider, ModuleServiceProvider::class)) {
                    $parsed = AttributeParser::parse($provider);
                    $parsed['binds'] = array_merge($parsed['binds'], $scanned['binds']);
                    $parsed['tags'] = array_merge($parsed['tags'], $scanned['tags']);
                    $parsed['commands'] = array_values(array_unique(array_merge($parsed['commands'], $commands)));
                    $settings[$provider] = $parsed;
                }
            }
        }

        $this->cache->write([
            'modules' => $this->manager->toArray(),
            'settings' => $settings,
        ]);

        return count($this->manager->enabled());
    }
}
