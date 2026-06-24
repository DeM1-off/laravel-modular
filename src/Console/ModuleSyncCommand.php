<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Dem1Off\LaravelModular\Operations\SyncEntry;
use Dem1Off\LaravelModular\Operations\SyncModules;

/**
 * Thin adapter: report what version each module package is pinned to vs.
 * installed, then run `composer update` for those packages so a project can be
 * synced to the latest published modules by module name rather than package
 * name. The planning lives in the SyncModules use-case.
 */
final class ModuleSyncCommand extends ModuleLinkingCommand
{
    protected $signature = 'module:sync
        {modules?* : Modules to sync to their latest resolvable version (omit and pass --all for every module)}
        {--all : Sync every module}
        {--check : Only report installed vs required versions; do not run composer}
        {--dry-run : Pass --dry-run to composer update (show, do not write)}';

    protected $description = 'Sync module package(s) to the version Composer resolves (composer update wrapper)';

    public function handle(ModuleManager $manager): int
    {
        if ($this->missingSelection()) {
            $this->components->error('Specify module name(s) or pass --all.');

            return self::FAILURE;
        }

        $sync = new SyncModules($this->files, $this->manifest(), $this->laravel->basePath('composer.lock'));
        $entries = $sync->plan($this->resolveTargets($manager));
        $managed = array_values(array_filter($entries, static fn (SyncEntry $e): bool => $e->isManaged()));

        if ($managed === []) {
            $this->components->error('Nothing to sync — no target module is required in the app composer.json. Promote or require it first.');

            return self::FAILURE;
        }

        $this->table(
            ['Module', 'Package', 'Constraint', 'Installed'],
            array_map(static fn (SyncEntry $e): array => [$e->module, $e->package, $e->constraint, $e->installed ?? '—'], $managed),
        );

        $packages = array_map(static fn (SyncEntry $e): string => $e->package, $managed);

        if ((bool) $this->option('check')) {
            $this->line('  Run: <fg=yellow>composer update '.implode(' ', $packages).'</>');

            return self::SUCCESS;
        }

        return $this->runComposerUpdate($packages, (bool) $this->option('dry-run'));
    }

    /**
     * @param  list<string>  $packages
     */
    private function runComposerUpdate(array $packages, bool $dryRun): int
    {
        $command = ['composer', 'update', ...$packages, '--with-dependencies'];

        if ($dryRun) {
            $command[] = '--dry-run';
        }

        $this->components->info('Running: '.implode(' ', $command));

        // Inherit the parent stdio so Composer streams its progress live.
        $process = @proc_open($command, [], $pipes, $this->laravel->basePath());

        if (! is_resource($process)) {
            $this->components->error('Could not run composer — is it installed and on your PATH?');

            return self::FAILURE;
        }

        return proc_close($process) === 0 ? self::SUCCESS : self::FAILURE;
    }
}
