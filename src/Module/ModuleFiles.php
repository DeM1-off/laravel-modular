<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Module;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Walks a module's app folder once and hands the same listing to every scanner.
 *
 * Both {@see ProvidesScanner} and {@see CommandScanner} need the module's PHP
 * files, and both need a change signature to decide whether their memo is
 * still valid. Resolved as a shared singleton, the tree is walked once per base
 * path per process rather than once per scanner.
 *
 * @phpstan-type Listing array{files: list<SplFileInfo>, signature: string}
 */
final class ModuleFiles
{
    /** @var array<string, Listing> */
    private array $memo = [];

    public function __construct(private readonly Filesystem $files) {}

    /**
     * The PHP files under $base, plus a signature that changes on any edit,
     * addition or deletion (newest mtime paired with the file count).
     *
     * @return Listing
     */
    public function php(string $base): array
    {
        if (isset($this->memo[$base])) {
            return $this->memo[$base];
        }

        if (! $this->files->isDirectory($base)) {
            return $this->memo[$base] = ['files' => [], 'signature' => '0:0'];
        }

        $files = [];
        $newest = 0;

        foreach ($this->files->allFiles($base) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $files[] = $file;
            $newest = max($newest, (int) $file->getMTime());
        }

        return $this->memo[$base] = [
            'files' => $files,
            'signature' => $newest.':'.count($files),
        ];
    }

    /**
     * Drop the in-process listing (used after generating files into a module).
     */
    public function flush(): void
    {
        $this->memo = [];
    }
}
