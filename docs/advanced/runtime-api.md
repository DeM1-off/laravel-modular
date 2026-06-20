11# Runtime API

Query modules at runtime through the `Modules` facade or the `module_path()`
helper.

```php
use Dem1Off\LaravelModular\Facades\Modules;

Modules::all();           // every module, keyed by name
Modules::enabled();       // only enabled ones
Modules::find('Blog');    // ModuleDescriptor|null
Modules::has('Blog');     // bool
Modules::isEnabled('Blog');
Modules::path('Blog');    // absolute path (throws if unknown)

module_path('Blog', 'resources/views'); // path helper
```

## ModuleDescriptor

`Modules::find()` / `Modules::all()` return immutable descriptors:

```php
$module = Modules::find('Blog');

$module->name;         // 'Blog'
$module->path;         // '/app/Modules/Blog'
$module->enabled;      // bool
$module->providers;    // list<class-string>
$module->alias;        // ?string
$module->description;  // ?string
$module->path('config/blog.php'); // path inside the module
```