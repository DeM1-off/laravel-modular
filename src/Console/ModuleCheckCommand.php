<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Manager\ModuleCache;
use Dem1Off\LaravelModular\Operations\DiagnoseModules;
use Illuminate\Console\Command;

/**
 * Thin adapter: render the Diagnosis produced by the DiagnoseModules use-case.
 */
final class ModuleCheckCommand extends Command
{
    protected $signature = 'module:check';

    protected $description = 'Diagnose the module setup (autoload, cache, providers, conflicts)';

    public function handle(DiagnoseModules $diagnose, ModuleCache $cache): int
    {
        $this->components->twoColumnDetail('Runtime autoload', $this->onOff('modules.autoload'));
        $this->components->twoColumnDetail('Auto-discover providers', $this->onOff('modules.auto_discover'));
        $this->components->twoColumnDetail('Scan #[Provides]', $this->onOff('modules.scan_bindings'));
        $this->components->twoColumnDetail('Compiled cache', $cache->exists() ? '<fg=green>present</>' : '<fg=gray>absent</>');

        $diagnosis = $diagnose->execute(
            (string) config('modules.namespace'),
            (string) config('modules.paths.app_folder', 'src/'),
        );

        $rows = [];
        foreach ($diagnosis->modules as $module) {
            $rows[] = [
                $module['name'],
                $module['enabled'] ? '<fg=green>enabled</>' : '<fg=gray>disabled</>',
                (string) $module['priority'],
                $module['missing'] === [] ? '<fg=green>ok</>' : '<fg=red>'.count($module['missing']).' missing</>',
            ];
        }

        $this->table(['Module', 'Status', 'Priority', 'Providers'], $rows);

        foreach ($diagnosis->modules as $module) {
            foreach ($module['missing'] as $provider) {
                $this->components->error("Provider not autoloadable: {$provider} (module {$module['name']})");
            }
        }

        foreach ($diagnosis->conflicts as $conflict) {
            $this->components->warn("Binding conflict: {$conflict['abstract']} is bound by ".implode(', ', $conflict['modules']).' (last wins).');
        }

        if (! $diagnosis->isHealthy()) {
            $this->components->warn('Some providers are not autoloadable — check the autoload mode or run `composer dump-autoload`.');

            return self::FAILURE;
        }

        $this->components->info('Modules look healthy.');

        return self::SUCCESS;
    }

    private function onOff(string $key): string
    {
        return (bool) config($key, true) ? '<fg=green>on</>' : '<fg=gray>off</>';
    }
}
