<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

use Dem1Off\LaravelModular\Manager\ModuleDescriptor;
use Illuminate\Filesystem\Filesystem;

/**
 * Use-case: build a sync report for module packages — what the app pins each
 * module to and what version is actually installed — so a `composer update` of
 * those packages can be driven by module name rather than package name.
 *
 * Console-free and read-only: it never touches Composer itself, it only reads
 * the app manifest and lock file, so the planning logic stays unit-testable.
 */
final readonly class SyncModules
{
    public function __construct(
        private Filesystem $files,
        private ComposerManifest $app,
        private string $lockPath,
    ) {}

    /**
     * @param  list<ModuleDescriptor>  $modules
     * @return list<SyncEntry>
     */
    public function plan(array $modules): array
    {
        $installed = $this->installedVersions();
        $entries = [];

        foreach ($modules as $module) {
            $package = ComposerManifest::load($this->files, $module->path('composer.json'))->name();

            if ($package === null) {
                continue;
            }

            $entries[] = new SyncEntry(
                module: $module->name,
                package: $package,
                constraint: $this->app->constraintFor($package),
                installed: $installed[$package] ?? null,
            );
        }

        return $entries;
    }

    /**
     * Package => installed version, read from composer.lock.
     *
     * @return array<string, string>
     */
    private function installedVersions(): array
    {
        if (! $this->files->exists($this->lockPath)) {
            return [];
        }

        /** @var array{packages?: list<array<string, mixed>>, packages-dev?: list<array<string, mixed>>} $lock */
        $lock = json_decode($this->files->get($this->lockPath), true) ?: [];
        $versions = [];

        foreach ([...($lock['packages'] ?? []), ...($lock['packages-dev'] ?? [])] as $package) {
            if (isset($package['name'], $package['version']) && is_string($package['name']) && is_string($package['version'])) {
                $versions[$package['name']] = $package['version'];
            }
        }

        return $versions;
    }
}
