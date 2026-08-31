<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Module;

/**
 * Resolves which of a module's convention folders actually exist on disk.
 *
 * One resolution feeds both worlds: an uncompiled boot resolves once per
 * provider and memoises, while `module:cache` resolves at compile time and
 * bakes the answer into the compiled array. With a cache present a request
 * touches the filesystem zero times here — so a module that ships none of the
 * optional folders costs nothing at boot instead of a stat per folder.
 *
 * Because the answer is baked, adding a `routes/` or `lang/` folder to a module
 * needs a `module:cache` rebuild to take effect — the same contract as
 * `config:cache` and `route:cache`.
 *
 * @phpstan-type Paths array{config: string|null, migrations: string|null, views: string|null, routes: array{web: string|null, api: string|null}, lang: string|null}
 */
final class ModulePaths
{
    /**
     * @param  array{config: bool, migrations: bool, views: bool, routes: bool, lang: bool}  $enabled  the #[Module] toggles
     * @return Paths
     */
    public static function resolve(string $modulePath, string $name, array $enabled): array
    {
        return [
            'config' => $enabled['config'] ? self::config($modulePath, $name) : null,
            'migrations' => $enabled['migrations'] ? self::dir($modulePath.'/database/migrations') : null,
            'views' => $enabled['views'] ? self::dir($modulePath.'/resources/views') : null,
            'routes' => [
                'web' => $enabled['routes'] ? self::file($modulePath.'/routes/web.php') : null,
                'api' => $enabled['routes'] ? self::file($modulePath.'/routes/api.php') : null,
            ],
            'lang' => $enabled['lang'] ? self::lang($modulePath) : null,
        ];
    }

    /**
     * A module's own name wins (config/blog.php); config/config.php is the
     * fallback for modules that use the generic name.
     */
    private static function config(string $modulePath, string $name): ?string
    {
        return self::file($modulePath.'/config/'.strtolower($name).'.php')
            ?? self::file($modulePath.'/config/config.php');
    }

    private static function lang(string $modulePath): ?string
    {
        return self::dir($modulePath.'/lang') ?? self::dir($modulePath.'/resources/lang');
    }

    private static function file(string $path): ?string
    {
        return is_file($path) ? $path : null;
    }

    private static function dir(string $path): ?string
    {
        return is_dir($path) ? $path : null;
    }
}
