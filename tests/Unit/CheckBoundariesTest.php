<?php

declare(strict_types=1);

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Dem1Off\LaravelModular\Operations\CheckBoundaries;
use Illuminate\Filesystem\Filesystem;

// No Orchestra TestCase, no artisan — the Operations layer is plain PHP.

const BOUNDARY_ALLOWED = ['Contracts', 'Data', 'Events', 'Enums'];

function checkBoundaries(string $base, Filesystem $files): array
{
    $manager = new ModuleManager($files, [
        'paths' => ['modules' => $base],
        'statuses_file' => $base.'/modules_statuses.json',
        'manifest_file' => 'module.json',
    ]);

    return (new CheckBoundaries($manager, $files))->execute('Modules', 'src/', BOUNDARY_ALLOWED);
}

beforeEach(function () {
    $this->base = sys_get_temp_dir().'/lm-boundaries-'.uniqid();
    $this->files = new Filesystem;

    foreach (['Blog', 'Shop'] as $module) {
        $this->files->ensureDirectoryExists($this->base.'/'.$module.'/src');
        $this->files->put($this->base.'/'.$module.'/module.json', json_encode([
            'name' => $module,
            'providers' => [],
        ]));
    }
});

afterEach(function () {
    $this->files->deleteDirectory($this->base);
});

it('flags a reference into another module internals', function () {
    $this->files->put(
        $this->base.'/Blog/src/ImportOrders.php',
        "<?php\n\nuse Modules\\Shop\\Infrastructure\\Persistence\\Models\\Order;\n",
    );

    $violations = checkBoundaries($this->base, $this->files);

    expect($violations)->toHaveCount(1)
        ->and($violations[0]['module'])->toBe('Blog')
        ->and($violations[0]['target'])->toBe('Shop')
        ->and($violations[0]['type'])->toBe('internal')
        ->and($violations[0]['file'])->toBe('src/ImportOrders.php');
});

it('allows references through the public surface', function () {
    $this->files->put(
        $this->base.'/Blog/src/ImportOrders.php',
        "<?php\n\nuse Modules\\Shop\\Contracts\\OrdersApi;\nuse Modules\\Shop\\Events\\OrderPlaced;\n",
    );

    expect(checkBoundaries($this->base, $this->files))->toBe([]);
});

it('flags an undeclared dependency when requires is declared', function () {
    $this->files->put($this->base.'/Blog/module.json', json_encode([
        'name' => 'Blog',
        'requires' => ['Billing'],
        'providers' => [],
    ]));
    $this->files->put(
        $this->base.'/Blog/src/ImportOrders.php',
        "<?php\n\nuse Modules\\Shop\\Contracts\\OrdersApi;\n",
    );

    $violations = checkBoundaries($this->base, $this->files);

    expect($violations)->toHaveCount(1)
        ->and($violations[0]['type'])->toBe('undeclared')
        ->and($violations[0]['target'])->toBe('Shop');
});

it('ignores self-references and unknown namespaces', function () {
    $this->files->put(
        $this->base.'/Blog/src/PostService.php',
        "<?php\n\nnamespace Modules\\Blog\\Application;\n\nuse Modules\\Blog\\Domain\\Post;\nuse Modules\\Vendor\\Support\\Helper;\n",
    );

    expect(checkBoundaries($this->base, $this->files))->toBe([]);
});

it('skips disabled modules', function () {
    $this->files->put(
        $this->base.'/Blog/src/ImportOrders.php',
        "<?php\n\nuse Modules\\Shop\\Infrastructure\\Persistence\\Models\\Order;\n",
    );
    $this->files->put($this->base.'/modules_statuses.json', json_encode(['Blog' => false]));

    expect(checkBoundaries($this->base, $this->files))->toBe([]);
});
