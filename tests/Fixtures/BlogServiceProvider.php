<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Tests\Fixtures;

use Dem1Off\LaravelModular\Module\Attributes\Bind;
use Dem1Off\LaravelModular\Module\ModuleServiceProvider;

#[Bind(Greeter::class, EnglishGreeter::class)]
#[Bind(Counter::class, Counter::class, singleton: true)]
final class BlogServiceProvider extends ModuleServiceProvider {}
