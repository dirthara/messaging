<?php

declare(strict_types=1);

namespace Dirthara\Messaging\Contract;

use Dirthara\Messaging\Exception\MessagingException;

interface MessageRouter
{
    /**
     * @param class-string $message
     *
     * @throws MessagingException
     */
    public function route(string $message, MessagePublisher $publisher): void;
}
