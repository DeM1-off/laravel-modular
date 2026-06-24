<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Manager\ModuleDescriptor;
use Dem1Off\LaravelModular\Manager\ModuleManager;
use Dem1Off\LaravelModular\Operations\ComposerManifest;
use Dem1Off\LaravelModular\Operations\HideFromGit;
use Dem1Off\LaravelModular\Operations\LinkState;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Thin CLI plumbing shared by module:link / module:unlink. It only parses
 * arguments and assembles the collaborators an operation needs — all the actual
 * work lives in the Operations layer (LinkModules / UnlinkModules).
 */
abstract class ModuleLinkingCommand extends Command
{
    public function __construct(protected readonly Filesystem $files)
    {
        parent::__construct();
    }

    /**
     * Resolve target modules from the three selection variants:
     *  - --all                 → every module
     *  - Blog Billing Checkout → several (variadic argument)
     *  - Blog                  → one
     *
     * @return list<ModuleDescriptor>
     */
    protected function resolveTargets(ModuleManager $manager): array
    {
        if ((bool) $this->option('all')) {
            return array_values($manager->all());
        }

        /** @var list<string> $names */
        $names = (array) $this->argument('modules');
        $targets = [];

        foreach ($names as $name) {
            $studly = Str::studly($name);
            $descriptor = $manager->find($studly);

            if ($descriptor === null) {
                $this->components->warn("Module [{$studly}] not found — skipped.");

                continue;
            }

            $targets[] = $descriptor;
        }

        return $targets;
    }

    /**
     * True when no target was given and --all was not passed (a usage error).
     */
    protected function missingSelection(): bool
    {
        return ! (bool) $this->option('all') && (array) $this->argument('modules') === [];
    }

    protected function manifest(): ComposerManifest
    {
        return ComposerManifest::load($this->files, $this->laravel->basePath('composer.json'));
    }

    protected function linkState(): LinkState
    {
        return LinkState::load($this->files, $this->laravel->bootstrapPath('cache/module-links.json'));
    }

    /**
     * Relative path-repository URL for the modules directory (e.g. "Modules/*").
     */
    protected function modulesUrl(): string
    {
        /** @var string $modulesPath */
        $modulesPath = config('modules.paths.modules', $this->laravel->basePath('Modules'));
        $base = $this->laravel->basePath();

        $relative = Str::startsWith($modulesPath, $base)
            ? trim(Str::after($modulesPath, $base), '/\\')
            : $modulesPath;

        return ($relative === '' ? '.' : str_replace('\\', '/', $relative)).'/*';
    }

    /**
     * Hide (or reveal) the manifest files that linking mutates from git, so a
     * project's diff shows only real code changes while modules are linked.
     * Returns false when git is unavailable or the files are not tracked.
     */
    protected function toggleGitVisibility(bool $hide): bool
    {
        $paths = $this->gitNoisePaths();
        $git = new HideFromGit($this->gitRunner());

        return $hide ? $git->hide($paths) : $git->reveal($paths);
    }

    /**
     * Tracked files that linking rewrites — composer.lock only when it exists.
     *
     * @return list<string>
     */
    private function gitNoisePaths(): array
    {
        $paths = ['composer.json'];

        if ($this->files->exists($this->laravel->basePath('composer.lock'))) {
            $paths[] = 'composer.lock';
        }

        return $paths;
    }

    /**
     * A dependency-free `git` runner rooted at the project base path.
     *
     * @return \Closure(list<string>): bool
     */
    private function gitRunner(): \Closure
    {
        $base = $this->laravel->basePath();

        return static function (array $args) use ($base): bool {
            $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            /** @var list<string> $command */
            $command = ['git', ...$args];
            $process = @proc_open($command, $descriptors, $pipes, $base);

            if (! is_resource($process)) {
                return false;
            }

            foreach ($pipes as $pipe) {
                fclose($pipe);
            }

            return proc_close($process) === 0;
        };
    }
}
