<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Facades;

use Dem1Off\LaravelModular\Manager\ModuleDescriptor;
use Dem1Off\LaravelModular\Manager\ModuleManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array<string, ModuleDescriptor> all()
 * @method static array<string, ModuleDescriptor> enabled()
 * @method static ModuleDescriptor|null find(string $name)
 * @method static bool has(string $name)
 * @method static bool isEnabled(string $name)
 * @method static string path(string $name)
 * @method static void flush()
 *
 * @see ModuleManager
 */
final class Modules extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ModuleManager::class;
    }
}
