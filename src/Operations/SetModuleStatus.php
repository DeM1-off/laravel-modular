<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

use Dem1Off\LaravelModular\Manager\ModuleManager;

/**
 * Use-case: enable or disable a module. Centralises the existence guard shared
 * by module:enable and module:disable. Returns false when the module is unknown.
 */
final readonly class SetModuleStatus
{
    public function __construct(private ModuleManager $manager) {}

    public function execute(string $module, bool $enabled): bool
    {
        if (! $this->manager->has($module)) {
            return false;
        }

        $this->manager->setStatus($module, $enabled);

        return true;
    }
}
