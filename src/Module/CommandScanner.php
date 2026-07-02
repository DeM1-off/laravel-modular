<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Module;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use ReflectionClass;

/**
 * Finds a module's artisan commands by convention: any instantiable
 * Illuminate\Console\Command subclass inside a `Console` directory (at any
 * depth) under the module's app folder.
 *
 * Runs in development (per console boot, over the Console directories only)
 * and at compile time for `module:cache` — the same logic feeds both, so dev
 * and production agree. Commands outside a Console directory are declared
 * explicitly with #[Module(commands: [...])].
 */
final readonly class CommandScanner
{
    public function __construct(private Filesystem $files) {}

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

        $commands = [];

        foreach ($this->files->allFiles($base) as $file) {
            $relative = ltrim(substr($file->getPathname(), strlen($base)), '/\\');
            $segments = explode('/', str_replace('\\', '/', $relative));

            if ($file->getExtension() !== 'php' || ! in_array('Console', array_slice($segments, 0, -1), true)) {
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
