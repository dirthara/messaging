<?php

declare(strict_types=1);

namespace Dirthara\Messaging\Tests\Fixtures;

use Throwable;
use Dirthara\Messaging\Contract\MessagePublisher;

final class RecordingPublisher implements MessagePublisher
{
    /**
     * @var list<object>
     */
    public private(set) array $published = [];

    public function __construct(
        private readonly ?Throwable $failure = null,
    ) {}

    /**
     * @throws Throwable
     */
    public function publish(object $message): void
    {
        $this->published[] = $message;

        if ($this->failure !== null) {
            throw $this->failure;
        }
    }
}
