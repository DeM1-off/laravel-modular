<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Prints a tailored, copy-pasteable plan to promote a module into a standalone
 * Composer package. Non-destructive by design: it never edits the app's
 * composer.json or runs git. With --export it copies the module out (leaving the
 * original untouched) so you can initialise a repo there.
 */
final class ModulePromoteCommand extends Command
{
    protected $signature = 'module:promote {module} {--export= : Copy the module to this directory (non-destructive)}';

    protected $description = 'Show the promotion plan for moving a module into its own package';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(ModuleManager $manager): int
    {
        /** @var string $moduleArg */
        $moduleArg = $this->argument('module');
        $module = Str::studly($moduleArg);
        $descriptor = $manager->find($module);

        if ($descriptor === null) {
            $this->components->error("Module [{$module}] not found.");

            return self::FAILURE;
        }

        $composerFile = $descriptor->path('composer.json');

        if (! $this->files->exists($composerFile)) {
            $this->components->error("Module [{$module}] has no composer.json — it cannot be promoted.");

            return self::FAILURE;
        }

        /** @var array{name?: string} $composer */
        $composer = json_decode($this->files->get($composerFile), true) ?? [];
        $package = $composer['name'] ?? Str::kebab($module).'-module';

        /** @var string|null $export */
        $export = $this->option('export');

        if ($export !== null && $export !== '') {
            $this->files->copyDirectory($descriptor->path(), $export);
            $this->components->info("Copied {$module} to {$export} (original left in place).");
        }

        $this->line('');
        $this->components->info("Promotion plan for {$module} ({$package})");
        $this->components->bulletList([
            "1. Move the module to its own repo (e.g. git subtree split --prefix=Modules/{$module} -b {$module}-module).",
            '2. In the app composer.json, replace the path repository with a vcs/registry entry:',
            "       \"require\": { \"{$package}\": \"^1.0\" }",
            "3. composer update {$package}",
        ]);
        $this->line('');
        $this->line('Namespace stays the same, so no code changes are needed. See the docs: Promotion.');

        return self::SUCCESS;
    }
}
