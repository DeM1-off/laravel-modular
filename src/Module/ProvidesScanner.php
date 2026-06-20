<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Module;

use Dem1Off\LaravelModular\Module\Attributes\Provides;
use Illuminate\Filesystem\Filesystem;
use ReflectionClass;

/**
 * Finds #[Provides] implementations in a module and turns them into bindings.
 *
 * Runs in development (lazily, over the module's own files) and at compile time
 * for `module:cache` — the same logic feeds both, so dev and production agree.
 */
final class ProvidesScanner
{
    public function __construct(private readonly Filesystem $files) {}

    /**
     * @param  string  $rootNamespace  e.g. "Modules\Blog"
     * @return list<array{abstract: class-string, concrete: class-string, singleton: bool}>
     */
    public function scan(string $modulePath, string $rootNamespace, string $appFolder): array
    {
        $base = $modulePath.'/'.trim($appFolder, '/');

        if (! $this->files->isDirectory($base)) {
            return [];
        }

        $binds = [];

        foreach ($this->files->allFiles($base) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            /** @var class-string $class */
            $class = $rootNamespace.'\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            foreach ($reflection->getAttributes(Provides::class) as $attribute) {
                $provides = $attribute->newInstance();
                $abstract = $provides->abstract ?? $this->inferInterface($reflection);

                if ($abstract === null) {
                    continue;
                }

                $binds[] = ['abstract' => $abstract, 'concrete' => $class, 'singleton' => $provides->singleton];
            }
        }

        return $binds;
    }

    /**
     * @param  ReflectionClass<object>  $reflection
     * @return class-string|null
     */
    private function inferInterface(ReflectionClass $reflection): ?string
    {
        $interfaces = $reflection->getInterfaceNames();

        return count($interfaces) === 1 ? $interfaces[0] : null;
    }
}
