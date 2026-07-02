<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

use Dem1Off\LaravelModular\Manager\ModuleDescriptor;
use Dem1Off\LaravelModular\Manager\ModuleManager;
use Illuminate\Filesystem\Filesystem;

/**
 * Use-case: find cross-module boundary violations. Pure analysis — returns a
 * list of violations, prints nothing.
 *
 * A reference from module A to module B is a violation when it points outside
 * B's public surface (the allowed sub-namespaces, e.g. Contracts/Data/Events/
 * Enums) — type `internal` — or, when A declares `requires` in module.json and
 * B is not on the list — type `undeclared`. Text-based (a namespace regex over
 * the app folder), so it sees use statements and inline FQCNs without loading
 * any module code.
 *
 * @phpstan-type Violation array{module: string, file: string, target: string, symbol: string, type: 'internal'|'undeclared'}
 */
final readonly class CheckBoundaries
{
    public function __construct(
        private ModuleManager $manager,
        private Filesystem $files,
    ) {}

    /**
     * @param  list<string>  $allowed  sub-namespaces other modules may reference
     * @return list<Violation>
     */
    public function execute(string $namespace, string $appFolder, array $allowed): array
    {
        $modules = $this->manager->all();
        $pattern = '/\b'.preg_quote($namespace, '/').'\\\\([A-Z][A-Za-z0-9_]*)\\\\([A-Za-z0-9_\\\\]+)/';

        $violations = [];

        foreach ($this->manager->enabled() as $module) {
            $base = $module->path.'/'.trim($appFolder, '/');

            if (! $this->files->isDirectory($base)) {
                continue;
            }

            foreach ($this->files->allFiles($base) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                preg_match_all($pattern, $this->files->get($file->getPathname()), $matches, PREG_SET_ORDER);

                foreach ($matches as $match) {
                    $violation = $this->judge($module->name, $module->requires, $match[1], $match[2], $modules, $allowed);

                    if ($violation !== null) {
                        $relative = ltrim(substr($file->getPathname(), strlen($module->path)), '/\\');
                        $violations[$module->name.'|'.$relative.'|'.$violation['type'].'|'.$violation['symbol']] = [
                            'module' => $module->name,
                            'file' => $relative,
                            ...$violation,
                        ];
                    }
                }
            }
        }

        return array_values($violations);
    }

    /**
     * @param  list<string>  $requires
     * @param  array<string, ModuleDescriptor>  $modules
     * @param  list<string>  $allowed
     * @return array{target: string, symbol: string, type: 'internal'|'undeclared'}|null
     */
    private function judge(string $module, array $requires, string $target, string $rest, array $modules, array $allowed): ?array
    {
        // Own namespace, or not a known module (nothing to judge).
        if ($target === $module || ! isset($modules[$target])) {
            return null;
        }

        $symbol = $target.'\\'.$rest;
        $segment = explode('\\', $rest)[0];

        if (! in_array($segment, $allowed, true)) {
            return ['target' => $target, 'symbol' => $symbol, 'type' => 'internal'];
        }

        if ($requires !== [] && ! in_array($target, $requires, true)) {
            return ['target' => $target, 'symbol' => $symbol, 'type' => 'undeclared'];
        }

        return null;
    }
}
