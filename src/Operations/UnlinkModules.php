<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

use Dem1Off\LaravelModular\Manager\ModuleDescriptor;
use Illuminate\Filesystem\Filesystem;

/**
 * Use-case: restore modules from local path development to a versioned package.
 *
 * Reverses {@see LinkModules}: each module's constraint is restored from the
 * recorded state (or an explicit override), and the modules path repository is
 * dropped once nothing is linked through it anymore.
 */
final readonly class UnlinkModules
{
    public function __construct(
        private Filesystem $files,
        private ComposerManifest $app,
        private LinkState $state,
        private string $modulesUrl,
    ) {}

    /**
     * @param  list<ModuleDescriptor>  $modules
     * @param  string|null  $constraint  Pin this instead of the recorded constraint.
     */
    public function execute(array $modules, ?string $constraint = null): LinkResult
    {
        $unlinked = [];
        $skipped = [];

        foreach ($modules as $module) {
            $package = $this->packageName($module);

            if ($package === null) {
                $skipped[] = $module->name;

                continue;
            }

            // Only touch packages we actually linked. Without a recorded link
            // (and no explicit override) leave a normally-required package alone,
            // so unlink can never delete a dependency it never created.
            if (! $this->state->has($package) && $constraint === null) {
                $skipped[] = $module->name;

                continue;
            }

            $previous = $constraint ?? $this->state->previousFor($package);

            if ($previous === null) {
                $this->app->removePackage($package);
            } else {
                $this->app->requirePackage($package, $previous);
            }

            $this->state->forget($package);
            $unlinked[] = $package;
        }

        if ($this->state->isEmpty()) {
            $this->app->removePathRepository($this->modulesUrl);
        }

        return new LinkResult($unlinked, $skipped);
    }

    public function commit(): void
    {
        $this->app->save();
        $this->state->save();
    }

    private function packageName(ModuleDescriptor $module): ?string
    {
        return ComposerManifest::load($this->files, $module->path('composer.json'))->name();
    }
}
