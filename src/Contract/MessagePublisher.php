<?php

declare(strict_types=1);

namespace Dirthara\Messaging\Contract;

interface MessagePublisher
{
    public function publish(object $message): void;
}
