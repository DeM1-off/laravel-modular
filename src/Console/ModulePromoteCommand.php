<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Dem1Off\LaravelModular\Operations\CannotPromote;
use Dem1Off\LaravelModular\Operations\PromoteModule;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Thin adapter: parse input, delegate to the PromoteModule use-case, render the
 * plan. Non-destructive by design (the operation never edits the app or git).
 */
final class ModulePromoteCommand extends Command
{
    protected $signature = 'module:promote {module} {--export= : Copy the module to this directory (non-destructive)}';

    protected $description = 'Show the promotion plan for moving a module into its own package';

    public function handle(ModuleManager $manager, PromoteModule $promote): int
    {
        /** @var string $moduleArg */
        $moduleArg = $this->argument('module');
        $module = $manager->find(Str::studly($moduleArg));

        if ($module === null) {
            $this->components->error("Module [{$moduleArg}] not found.");

            return self::FAILURE;
        }

        try {
            $plan = $promote->plan($module);
        } catch (CannotPromote $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        /** @var string|null $export */
        $export = $this->option('export');

        if ($export !== null && $export !== '') {
            $promote->export($module, $export);
            $this->components->info("Copied {$module->name} to {$export} (original left in place).");
        }

        $this->newLine();
        $this->components->info("Promotion plan for {$plan->module} ({$plan->package})");
        $this->components->bulletList($plan->steps());
        $this->newLine();
        $this->line('Namespace stays the same, so no code changes are needed. See the docs: Promotion.');

        return self::SUCCESS;
    }
}
