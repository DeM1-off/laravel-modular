<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Module;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use ReflectionClass;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Finds a module's artisan commands by convention: any instantiable
 * Illuminate\Console\Command subclass inside a `Console` directory (at any
 * depth) under the module's app folder.
 *
 * Runs in development (per console boot) and at compile time for
 * `module:cache` — the same logic feeds both, so dev and production agree.
 * The module listing is shared with {@see ProvidesScanner}, so a console boot
 * walks the tree once for both scanners, and the result is memoised against the
 * module's change signature so an unchanged module is never re-reflected.
 * Commands outside a Console directory are declared explicitly with
 * #[Module(commands: [...])].
 */
final class CommandScanner
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
     * @return list<class-string<Command>>
     */
    public function scan(string $modulePath, string $rootNamespace, string $appFolder): array
    {
        $base = $modulePath.'/'.trim($appFolder, '/');

        if (! $this->files->isDirectory($base)) {
            return [];
        }

        ['files' => $files, 'signature' => $signature] = $this->listing->php($base);

        $key = 'commands:'.$base;

        /** @var list<class-string<Command>>|null $cached */
        $cached = $this->cache->get($key, $signature);

        if ($cached !== null) {
            return $cached;
        }

        $commands = $this->reflect($files, $rootNamespace, $base);

        $this->cache->put($key, $signature, $commands);

        return $commands;
    }

    /**
     * @param  array<int, SplFileInfo>  $files
     * @return list<class-string<Command>>
     */
    private function reflect(array $files, string $rootNamespace, string $base): array
    {
        $commands = [];

        foreach ($files as $file) {
            $relative = ltrim(substr($file->getPathname(), strlen($base)), '/\\');
            $segments = explode('/', str_replace('\\', '/', $relative));

            if (! in_array('Console', array_slice($segments, 0, -1), true)) {
                continue;
            }

            /** @var class-string $class */
            $class = $rootNamespace.'\\'.str_replace(['/', '.php'], ['\\', ''], $relative);

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isInstantiable() && $reflection->isSubclassOf(Command::class)) {
                $commands[] = $class;
            }
        }

        sort($commands);

        /** @var list<class-string<Command>> $commands */
        return $commands;
    }
}
