<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

use Dem1Off\LaravelModular\Manager\ModuleDescriptor;
use Illuminate\Filesystem\Filesystem;

/**
 * Use-case: switch modules to local path development (reverse of promotion).
 *
 * Pins each module's package to "*" in the app manifest, ensures the modules
 * path repository exists, and records the prior constraint so it can be
 * restored. Pure application logic — no console, no I/O beyond the injected
 * manifest/state, so it is unit-testable on its own.
 */
final readonly class LinkModules
{
    public function __construct(
        private Filesystem $files,
        private ComposerManifest $app,
        private LinkState $state,
        private string $modulesUrl,
    ) {}

    /**
     * @param  list<ModuleDescriptor>  $modules
     */
    public function execute(array $modules): LinkResult
    {
        $linked = [];
        $skipped = [];

        foreach ($modules as $module) {
            $package = $this->packageName($module);

            if ($package === null) {
                $skipped[] = $module->name;

                continue;
            }

            $this->state->remember($package, $this->app->constraintFor($package));
            $this->app->requirePackage($package, '*');
            $linked[] = $package;
        }

        if ($linked !== []) {
            $this->app->ensurePathRepository($this->modulesUrl);
        }

        return new LinkResult($linked, $skipped, $this->modulesUrl);
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
