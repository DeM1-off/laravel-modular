<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Manager\ModuleCache;
use Dem1Off\LaravelModular\Operations\CheckBoundaries;
use Dem1Off\LaravelModular\Operations\DiagnoseModules;
use Illuminate\Console\Command;

/**
 * Thin adapter: render the Diagnosis produced by the DiagnoseModules use-case
 * and, with --boundaries, the violations found by CheckBoundaries.
 */
final class ModuleCheckCommand extends Command
{
    protected $signature = 'module:check {--boundaries : Also check cross-module boundaries}';

    protected $description = 'Diagnose the module setup (autoload, cache, providers, dependencies, conflicts)';

    public function handle(DiagnoseModules $diagnose, ModuleCache $cache, CheckBoundaries $boundaries): int
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

            foreach ($module['requires_missing'] as $requirement) {
                $this->components->error("Module {$module['name']} requires {$requirement}, which is not installed.");
            }

            foreach ($module['requires_disabled'] as $requirement) {
                $this->components->error("Module {$module['name']} requires {$requirement}, which is disabled.");
            }
        }

        foreach ($diagnosis->conflicts as $conflict) {
            $this->components->warn("Binding conflict: {$conflict['abstract']} is bound by ".implode(', ', $conflict['modules']).' (last wins).');
        }

        $violations = $this->option('boundaries') ? $this->checkBoundaries($boundaries) : [];

        if (! $diagnosis->isHealthy() || $violations !== []) {
            if (! $diagnosis->isHealthy()) {
                $this->components->warn('Some modules are unhealthy — check the autoload mode, `composer dump-autoload`, or the module `requires` lists.');
            }

            return self::FAILURE;
        }

        $this->components->info('Modules look healthy.');

        return self::SUCCESS;
    }

    /**
     * @return list<array{module: string, file: string, target: string, symbol: string, type: 'internal'|'undeclared'}>
     */
    private function checkBoundaries(CheckBoundaries $boundaries): array
    {
        /** @var list<string> $allowed */
        $allowed = config('modules.boundaries.allowed', ['Contracts', 'Data', 'Events', 'Enums']);

        $violations = $boundaries->execute(
            (string) config('modules.namespace'),
            (string) config('modules.paths.app_folder', 'src/'),
            $allowed,
        );

        foreach ($violations as $violation) {
            $message = $violation['type'] === 'internal'
                ? "Boundary violation: {$violation['module']} reaches into {$violation['symbol']} ({$violation['file']}) — expose it via ".implode('/', $allowed).' instead.'
                : "Undeclared dependency: {$violation['module']} uses {$violation['symbol']} ({$violation['file']}) but does not list {$violation['target']} in module.json `requires`.";

            $this->components->error($message);
        }

        if ($violations === []) {
            $this->components->info('No boundary violations.');
        }

        return $violations;
    }

    private function onOff(string $key): string
    {
        return (bool) config($key, true) ? '<fg=green>on</>' : '<fg=gray>off</>';
    }
}
