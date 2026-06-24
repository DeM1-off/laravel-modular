<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Dem1Off\LaravelModular\Operations\UnlinkModules;

/**
 * Thin adapter: parse input, delegate to the UnlinkModules use-case, render.
 *
 * Restores module(s) from local path development back to a versioned package.
 */
final class ModuleUnlinkCommand extends ModuleLinkingCommand
{
    protected $signature = 'module:unlink
        {modules?* : Modules to restore to a versioned package (omit and pass --all for every module)}
        {--all : Unlink every module}
        {--constraint= : Version to pin instead of the recorded one (e.g. ^1.3)}
        {--hide-git : Restore git tracking of composer.json/lock (reverse of link --hide-git)}
        {--dry-run : Show the composer.json changes without writing them}';

    protected $description = 'Restore module(s) from local path development to a versioned package';

    public function handle(ModuleManager $manager): int
    {
        if ($this->missingSelection()) {
            $this->components->error('Specify module name(s) or pass --all.');

            return self::FAILURE;
        }

        /** @var string|null $constraint */
        $constraint = $this->option('constraint');

        $operation = new UnlinkModules($this->files, $this->manifest(), $this->linkState(), $this->modulesUrl());
        $result = $operation->execute($this->resolveTargets($manager), $constraint);

        if ($result->isEmpty()) {
            $this->components->error('Nothing to unlink.');

            return self::FAILURE;
        }

        if ((bool) $this->option('dry-run')) {
            $this->components->info('Dry run — composer.json would change to:');
            $this->components->bulletList(array_map(
                static fn (string $p): string => "require: {$p} → restored",
                $result->packages,
            ));

            return self::SUCCESS;
        }

        $operation->commit();

        if ((bool) $this->option('hide-git')) {
            $this->toggleGitVisibility(false)
                ? $this->components->info('Git tracking restored: composer.json, composer.lock')
                : $this->components->warn('Could not restore git tracking (not a repo, or git unavailable).');
        }

        $this->components->info('Unlinked: '.implode(', ', $result->packages));
        $this->newLine();
        $this->line('  Next: <fg=yellow>composer update '.implode(' ', $result->packages).'</>');

        return self::SUCCESS;
    }
}
