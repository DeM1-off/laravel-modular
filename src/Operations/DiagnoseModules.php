<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Dem1Off\LaravelModular\Module\AttributeParser;
use Dem1Off\LaravelModular\Module\ModuleServiceProvider;
use Dem1Off\LaravelModular\Module\ProvidesScanner;

/**
 * Use-case: inspect the module setup and report module health and binding
 * conflicts. Pure analysis — returns a {@see Diagnosis}, prints nothing.
 */
final readonly class DiagnoseModules
{
    public function __construct(
        private ModuleManager $manager,
        private ProvidesScanner $scanner,
    ) {}

    public function execute(string $namespace, string $appFolder): Diagnosis
    {
        $modules = [];

        foreach ($this->manager->all() as $module) {
            $missing = array_values(array_filter(
                $module->providers,
                static fn (string $provider): bool => ! class_exists($provider),
            ));

            $modules[] = [
                'name' => $module->name,
                'enabled' => $module->enabled,
                'priority' => $module->priority,
                'missing' => $missing,
            ];
        }

        return new Diagnosis($modules, $this->conflicts($namespace, $appFolder));
    }

    /**
     * @return list<array{abstract: string, modules: list<string>}>
     */
    private function conflicts(string $namespace, string $appFolder): array
    {
        /** @var array<class-string, list<string>> $owners */
        $owners = [];

        foreach ($this->manager->enabled() as $module) {
            $abstracts = [];

            foreach ($module->providers as $provider) {
                if (class_exists($provider) && is_subclass_of($provider, ModuleServiceProvider::class)) {
                    foreach (AttributeParser::parse($provider)['binds'] as $bind) {
                        $abstracts[] = $bind['abstract'];
                    }
                }
            }

            foreach ($this->scanner->scan($module->path, $namespace.'\\'.$module->name, $appFolder)['binds'] as $bind) {
                $abstracts[] = $bind['abstract'];
            }

            foreach (array_unique($abstracts) as $abstract) {
                $owners[$abstract][] = $module->name;
            }
        }

        $conflicts = [];

        foreach ($owners as $abstract => $modules) {
            if (count($modules) > 1) {
                $conflicts[] = ['abstract' => $abstract, 'modules' => $modules];
            }
        }

        return $conflicts;
    }
}
