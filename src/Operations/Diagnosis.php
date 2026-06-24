<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

/**
 * The result of diagnosing the module setup — plain data for the console to
 * render. Knows whether the setup is healthy; does no rendering itself.
 *
 * @phpstan-type ModuleRow array{name: string, enabled: bool, priority: int, missing: list<class-string>}
 * @phpstan-type Conflict array{abstract: string, modules: list<string>}
 */
final readonly class Diagnosis
{
    /**
     * @param  list<ModuleRow>  $modules
     * @param  list<Conflict>  $conflicts
     */
    public function __construct(
        public array $modules,
        public array $conflicts,
    ) {}

    public function isHealthy(): bool
    {
        foreach ($this->modules as $module) {
            if ($module['missing'] !== []) {
                return false;
            }
        }

        return true;
    }
}
