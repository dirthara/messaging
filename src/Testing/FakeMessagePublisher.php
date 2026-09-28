<?php

declare(strict_types=1);

namespace Dirthara\Messaging\Testing;

use Dirthara\Messaging\Contract\MessagePublisher;

use function is_subclass_of;

final class FakeMessagePublisher implements MessagePublisher
{
    /**
     * @var list<object>
     */
    public private(set) array $published = [];

    public function publish(object $message): void
    {
        $this->published[] = $message;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $message
     *
     * @return list<T>
     */
    public function published(string $message): array
    {
        $published = [];

        foreach ($this->published as $candidate) {
            if (!$candidate instanceof $message) {
                continue;
            }

            if (is_subclass_of($candidate, $message)) {
                continue;
            }

            $published[] = $candidate;
        }

        return $published;
    }

    /**
     * @param class-string $message
     */
    public function hasPublished(string $message): bool
    {
        return $this->published($message) !== [];
    }
}
