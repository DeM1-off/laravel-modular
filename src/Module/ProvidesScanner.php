<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Module;

use Dem1Off\LaravelModular\Module\Attributes\Provides;
use Dem1Off\LaravelModular\Module\Attributes\Scoped;
use Dem1Off\LaravelModular\Module\Attributes\Singleton;
use Illuminate\Filesystem\Filesystem;
use ReflectionClass;

/**
 * Finds auto-binding attributes (#[Provides], #[Singleton], #[Scoped]) in a
 * module and turns them into bindings and tags.
 *
 * Runs in development (lazily, over the module's own files) and at compile time
 * for `module:cache` — the same logic feeds both, so dev and production agree.
 *
 * @phpstan-import-type Bindings from AttributeParser
 * @phpstan-import-type Tags from AttributeParser
 */
final class ProvidesScanner
{
    public function __construct(private readonly Filesystem $files) {}

    /**
     * @param  string  $rootNamespace  e.g. "Modules\Blog"
     * @return array{binds: Bindings, tags: Tags}
     */
    public function scan(string $modulePath, string $rootNamespace, string $appFolder): array
    {
        $base = $modulePath.'/'.trim($appFolder, '/');

        if (! $this->files->isDirectory($base)) {
            return ['binds' => [], 'tags' => []];
        }

        $binds = [];
        $tags = [];

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

            foreach ($this->bindingAttributes($reflection) as [$abstract, $tag, $lifetime]) {
                if ($abstract !== null) {
                    $binds[] = ['abstract' => $abstract, 'concrete' => $class, 'lifetime' => $lifetime];
                }

                if ($tag !== null) {
                    $tags[] = ['tag' => $tag, 'concrete' => $class];
                }
            }
        }

        return ['binds' => $binds, 'tags' => $tags];
    }

    /**
     * @param  ReflectionClass<object>  $reflection
     * @return list<array{0: class-string|null, 1: string|null, 2: 'bind'|'singleton'|'scoped'}>
     */
    private function bindingAttributes(ReflectionClass $reflection): array
    {
        $found = [];

        foreach ($reflection->getAttributes(Provides::class) as $attribute) {
            $provides = $attribute->newInstance();
            $found[] = [
                $provides->abstract ?? $this->inferInterface($reflection),
                $provides->tag,
                $provides->singleton ? 'singleton' : 'bind',
            ];
        }

        foreach ($reflection->getAttributes(Singleton::class) as $attribute) {
            $singleton = $attribute->newInstance();
            $found[] = [$singleton->abstract ?? $this->inferInterface($reflection), $singleton->tag, 'singleton'];
        }

        foreach ($reflection->getAttributes(Scoped::class) as $attribute) {
            $scoped = $attribute->newInstance();
            $found[] = [$scoped->abstract ?? $this->inferInterface($reflection), $scoped->tag, 'scoped'];
        }

        return $found;
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
