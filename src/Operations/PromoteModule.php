<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

use Dem1Off\LaravelModular\Manager\ModuleDescriptor;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Use-case: produce the promotion plan for a module, and optionally export a
 * copy of it. Non-destructive — it never edits the app's composer.json or runs
 * git; that stays a deliberate human step.
 */
final readonly class PromoteModule
{
    public function __construct(private Filesystem $files) {}

    public function plan(ModuleDescriptor $module): PromotionPlan
    {
        $composerFile = $module->path('composer.json');

        if (! $this->files->exists($composerFile)) {
            throw CannotPromote::missingComposer($module->name);
        }

        $package = ComposerManifest::load($this->files, $composerFile)->name()
            ?? Str::kebab($module->name).'-module';

        return new PromotionPlan($module->name, $package);
    }

    /**
     * Copy the module out to a destination, leaving the original in place.
     */
    public function export(ModuleDescriptor $module, string $destination): void
    {
        $this->files->copyDirectory($module->path(), $destination);
    }
}
