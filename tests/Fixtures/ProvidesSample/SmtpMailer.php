<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Tests\Fixtures\ProvidesSample;

use Dem1Off\LaravelModular\Module\Attributes\Provides;

// No abstract given: inferred from the single implemented interface (Mailer).
#[Provides]
final class SmtpMailer implements Mailer
{
    public function send(string $to): void {}
}
