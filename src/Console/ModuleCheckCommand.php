<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Manager\ModuleCache;
use Dem1Off\LaravelModular\Manager\ModuleManager;
use Dem1Off\LaravelModular\Module\AttributeParser;
use Dem1Off\LaravelModular\Module\ModuleServiceProvider;
use Dem1Off\LaravelModular\Module\ProvidesScanner;
use Illuminate\Console\Command;

final class ModuleCheckCommand extends Command
{
    protected $signature = 'module:check';

    protected $description = 'Diagnose the module setup (autoload, cache, providers, conflicts)';

    public function handle(ModuleManager $manager, ModuleCache $cache, ProvidesScanner $scanner): int
    {
        $healthy = true;

        $this->components->twoColumnDetail('Runtime autoload', $this->onOff('modules.autoload'));
        $this->components->twoColumnDetail('Auto-discover providers', $this->onOff('modules.auto_discover'));
        $this->components->twoColumnDetail('Scan #[Provides]', $this->onOff('modules.scan_bindings'));
        $this->components->twoColumnDetail('Compiled cache', $cache->exists() ? '<fg=green>present</>' : '<fg=gray>absent</>');

        $rows = [];
        foreach ($manager->all() as $module) {
            $missing = array_filter($module->providers, static fn (string $p): bool => ! class_exists($p));

            if ($missing !== []) {
                $healthy = false;
            }

            $rows[] = [
                $module->name,
                $module->enabled ? '<fg=green>enabled</>' : '<fg=gray>disabled</>',
                (string) $module->priority,
                $missing === [] ? '<fg=green>ok</>' : '<fg=red>'.count($missing).' missing</>',
            ];
        }

        $this->table(['Module', 'Status', 'Priority', 'Providers'], $rows);

        foreach ($manager->all() as $module) {
            foreach ($module->providers as $provider) {
                if (! class_exists($provider)) {
                    $this->components->error("Provider not autoloadable: {$provider} (module {$module->name})");
                }
            }
        }

        $this->reportConflicts($manager, $scanner);

        if (! $healthy) {
            $this->components->warn('Some providers are not autoloadable — check the autoload mode or run `composer dump-autoload`.');

            return self::FAILURE;
        }

        $this->components->info('Modules look healthy.');

        return self::SUCCESS;
    }

    private function reportConflicts(ModuleManager $manager, ProvidesScanner $scanner): void
    {
        $namespace = (string) config('modules.namespace');
        $appFolder = (string) config('modules.paths.app_folder', 'src/');

        /** @var array<class-string, list<string>> $owners */
        $owners = [];

        foreach ($manager->enabled() as $module) {
            $abstracts = [];

            foreach ($module->providers as $provider) {
                if (class_exists($provider) && is_subclass_of($provider, ModuleServiceProvider::class)) {
                    foreach (AttributeParser::parse($provider)['binds'] as $bind) {
                        $abstracts[] = $bind['abstract'];
                    }
                }
            }

            foreach ($scanner->scan($module->path, $namespace.'\\'.$module->name, $appFolder)['binds'] as $bind) {
                $abstracts[] = $bind['abstract'];
            }

            foreach (array_unique($abstracts) as $abstract) {
                $owners[$abstract][] = $module->name;
            }
        }

        foreach ($owners as $abstract => $modules) {
            if (count($modules) > 1) {
                $this->components->warn("Binding conflict: {$abstract} is bound by ".implode(', ', $modules).' (last wins).');
            }
        }
    }

    private function onOff(string $key): string
    {
        return (bool) config($key, true) ? '<fg=green>on</>' : '<fg=gray>off</>';
    }
}
