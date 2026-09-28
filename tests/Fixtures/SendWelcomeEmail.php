<?php

declare(strict_types=1);

namespace Dirthara\Messaging\Tests\Fixtures;

final readonly class SendWelcomeEmail
{
    public function __construct(
        public string $userId,
    ) {}
}
