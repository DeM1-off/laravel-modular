<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Dem1Off\LaravelModular\Operations\LinkModules;

/**
 * Thin adapter: parse input, delegate to the LinkModules use-case, render.
 *
 * Switches module(s) to local path development — the reverse of module:promote.
 * The module's code is untouched; only the app's composer.json changes.
 */
final class ModuleLinkCommand extends ModuleLinkingCommand
{
    protected $signature = 'module:link
        {modules?* : Modules to link for local dev (omit and pass --all for every module)}
        {--all : Link every module}
        {--dry-run : Show the composer.json changes without writing them}';

    protected $description = 'Switch module(s) to local path development (reverse of promotion)';

    public function handle(ModuleManager $manager): int
    {
        if ($this->missingSelection()) {
            $this->components->error('Specify module name(s) or pass --all.');

            return self::FAILURE;
        }

        $operation = new LinkModules($this->files, $this->manifest(), $this->linkState(), $this->modulesUrl());
        $result = $operation->execute($this->resolveTargets($manager));

        foreach ($result->skipped as $name) {
            $this->components->warn("Module [{$name}] has no composer.json — skipped.");
        }

        if ($result->isEmpty()) {
            $this->components->error('Nothing linkable — no target module ships a composer.json.');

            return self::FAILURE;
        }

        if ((bool) $this->option('dry-run')) {
            $this->components->info('Dry run — composer.json would change to:');
            $this->components->bulletList([
                "repositories: + { type: path, url: {$result->repositoryUrl}, options: { symlink: true } }",
                'require: '.implode(', ', array_map(static fn (string $p): string => "{$p}: \"*\"", $result->packages)),
            ]);

            return self::SUCCESS;
        }

        $operation->commit();

        $this->components->info('Linked for local development: '.implode(', ', $result->packages));
        $this->newLine();
        $this->line('  Next: <fg=yellow>composer update '.implode(' ', $result->packages).'</>');

        return self::SUCCESS;
    }
}
