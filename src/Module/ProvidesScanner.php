<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Module;

use Dem1Off\LaravelModular\Module\Attributes\Provides;
use Dem1Off\LaravelModular\Module\Attributes\Scoped;
use Dem1Off\LaravelModular\Module\Attributes\Singleton;
use Illuminate\Filesystem\Filesystem;
use ReflectionClass;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Finds auto-binding attributes (#[Provides], #[Singleton], #[Scoped]) in a
 * module and turns them into bindings and tags.
 *
 * Runs in development (lazily, over the module's own files) and at compile time
 * for `module:cache` — the same logic feeds both, so dev and production agree.
 * In development the result is memoised against the module's change signature,
 * so unchanged modules aren't re-reflected each request.
 *
 * Reflection is the expensive part, because reaching a class autoloads and
 * compiles it. Files are therefore filtered on their source text first: an
 * attribute cannot be named dynamically, so a file that binds something must
 * mention the attribute's short name — via a `use` import or inline. Reading a
 * file is far cheaper than compiling a class, so on a cold scan only the
 * handful of files that actually declare bindings are loaded.
 *
 * @phpstan-import-type Bindings from AttributeParser
 * @phpstan-import-type Tags from AttributeParser
 *
 * @phpstan-type ScanResult array{binds: Bindings, tags: Tags}
 */
final class ProvidesScanner
{
    private readonly ModuleFiles $listing;

    private readonly ScanCache $cache;

    public function __construct(
        private readonly Filesystem $files,
        ?ScanCache $cache = null,
        ?ModuleFiles $listing = null,
    ) {
        $this->cache = $cache ?? new ScanCache($files);
        $this->listing = $listing ?? new ModuleFiles($files);
    }

    /**
     * @param  string  $rootNamespace  e.g. "Modules\Blog"
     * @return ScanResult
     */
    public function scan(string $modulePath, string $rootNamespace, string $appFolder): array
    {
        $base = $modulePath.'/'.trim($appFolder, '/');

        if (! $this->files->isDirectory($base)) {
            return ['binds' => [], 'tags' => []];
        }

        ['files' => $files, 'signature' => $signature] = $this->listing->php($base);

        $key = 'provides:'.$base;

        /** @var ScanResult|null $cached */
        $cached = $this->cache->get($key, $signature);

        if ($cached !== null) {
            return $cached;
        }

        $result = $this->reflect($files, $rootNamespace, $base);

        $this->cache->put($key, $signature, $result);

        return $result;
    }

    /**
     * @param  array<int, SplFileInfo>  $files
     * @return ScanResult
     */
    private function reflect(array $files, string $rootNamespace, string $base): array
    {
        $binds = [];
        $tags = [];

        foreach ($files as $file) {
            if (! $this->mayBind($file)) {
                continue;
            }

            $relative = ltrim(substr($file->getPathname(), strlen($base)), '/\\');

            /** @var class-string $class */
            $class = $rootNamespace.'\\'.str_replace(['/', '.php'], ['\\', ''], $relative);

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
     * Whether a file's source even mentions one of the binding attributes.
     *
     * Short names, not FQCNs: an aliased import (`use Provides as P`) still
     * carries the short name in its `use` line, so this cannot produce a false
     * negative — only skip files that plainly declare nothing.
     */
    private function mayBind(SplFileInfo $file): bool
    {
        $source = $file->getContents();

        foreach (self::markers() as $marker) {
            if (str_contains($source, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function markers(): array
    {
        static $markers = null;

        return $markers ??= array_map(
            static fn (string $attribute): string => substr((string) strrchr($attribute, '\\'), 1),
            [Provides::class, Singleton::class, Scoped::class],
        );
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
