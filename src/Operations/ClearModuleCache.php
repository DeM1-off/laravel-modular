<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

use Dem1Off\LaravelModular\Manager\ModuleCache;
use Dem1Off\LaravelModular\Module\ProvidesScanner;

/**
 * Use-case: remove every compiled module artifact (the discovery cache and the
 * #[Provides] scan cache). The mirror of {@see CompileModuleCache}.
 */
final readonly class ClearModuleCache
{
    public function __construct(
        private ModuleCache $cache,
        private ProvidesScanner $scanner,
    ) {}

    public function execute(): void
    {
        $this->cache->clear();
        $this->scanner->clearCache();
    }
}
