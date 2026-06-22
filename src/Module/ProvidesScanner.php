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
 * In development the result is memoised to a small file keyed by the module's
 * file count and newest mtime, so unchanged modules aren't re-reflected each
 * request.
 *
 * @phpstan-import-type Bindings from AttributeParser
 * @phpstan-import-type Tags from AttributeParser
 *
 * @phpstan-type ScanResult array{binds: Bindings, tags: Tags}
 */
final class ProvidesScanner
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly ?string $cachePath = null,
    ) {}

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

        $phpFiles = array_filter(
            $this->files->allFiles($base),
            static fn ($file): bool => $file->getExtension() === 'php',
        );

        $signature = $this->signature($phpFiles);
        $cache = $this->readCache();

        if (isset($cache[$base]) && $cache[$base]['signature'] === $signature) {
            return $cache[$base]['result'];
        }

        $result = $this->reflect($phpFiles, $rootNamespace, $base);

        $this->writeCache($base, $signature, $result, $cache);

        return $result;
    }

    public function clearCache(): void
    {
        if ($this->cachePath !== null && $this->files->exists($this->cachePath)) {
            $this->files->delete($this->cachePath);
        }
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

    /**
     * Newest mtime plus file count: changes on edit, add or delete.
     *
     * @param  array<int, SplFileInfo>  $files
     */
    private function signature(array $files): string
    {
        $newest = 0;

        foreach ($files as $file) {
            $newest = max($newest, (int) $file->getMTime());
        }

        return $newest.':'.count($files);
    }

    /**
     * @return array<string, array{signature: string, result: ScanResult}>
     */
    private function readCache(): array
    {
        if ($this->cachePath === null || ! $this->files->exists($this->cachePath)) {
            return [];
        }

        /** @var array<string, array{signature: string, result: ScanResult}> $cache */
        $cache = require $this->cachePath;

        return $cache;
    }

    /**
     * @param  ScanResult  $result
     * @param  array<string, array{signature: string, result: ScanResult}>  $cache
     */
    private function writeCache(string $base, string $signature, array $result, array $cache): void
    {
        if ($this->cachePath === null) {
            return;
        }

        $cache[$base] = ['signature' => $signature, 'result' => $result];

        $this->files->ensureDirectoryExists(dirname($this->cachePath));
        $this->files->put($this->cachePath, '<?php return '.var_export($cache, true).';'.PHP_EOL);
    }
}
