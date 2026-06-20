<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Manager\ModuleCache;
use Dem1Off\LaravelModular\Manager\ModuleManager;
use Dem1Off\LaravelModular\Module\AttributeParser;
use Dem1Off\LaravelModular\Module\ModuleServiceProvider;
use Dem1Off\LaravelModular\Module\ProvidesScanner;
use Illuminate\Console\Command;

final class ModuleCacheCommand extends Command
{
    protected $signature = 'module:cache';

    protected $description = 'Compile module discovery and attributes into a fast cache';

    public function handle(ModuleManager $manager, ModuleCache $cache, ProvidesScanner $scanner): int
    {
        $settings = [];
        $namespace = (string) config('modules.namespace');
        $appFolder = (string) config('modules.paths.app_folder', 'src/');

        foreach ($manager->enabled() as $module) {
            $scanned = $scanner->scan($module->path, $namespace.'\\'.$module->name, $appFolder);

            foreach ($module->providers as $provider) {
                if (class_exists($provider) && is_subclass_of($provider, ModuleServiceProvider::class)) {
                    $parsed = AttributeParser::parse($provider);
                    $parsed['binds'] = array_merge($parsed['binds'], $scanned['binds']);
                    $parsed['tags'] = array_merge($parsed['tags'], $scanned['tags']);
                    $settings[$provider] = $parsed;
                }
            }
        }

        $cache->write([
            'modules' => $manager->toArray(),
            'settings' => $settings,
        ]);

        $this->components->info('Modules cached ('.count($manager->enabled()).' enabled).');

        return self::SUCCESS;
    }
}
