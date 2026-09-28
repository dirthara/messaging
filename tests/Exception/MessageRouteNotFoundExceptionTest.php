<?php

declare(strict_types=1);

namespace Dirthara\Messaging\Tests\Exception;

use RuntimeException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Dirthara\Messaging\Exception\MessagingException;
use Dirthara\Messaging\Exception\MessageRouteNotFoundException;

final class MessageRouteNotFoundExceptionTest extends TestCase
{
    #[Test]
    public function it_carries_nothing_by_default(): void
    {
        $exception = new MessageRouteNotFoundException();

        self::assertInstanceOf(MessagingException::class, $exception);
        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame('', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertNull($exception->getPrevious());
        self::assertSame([], $exception->context);
    }

    #[Test]
    public function it_keeps_a_previous_exception_and_its_context(): void
    {
        $previous = new RuntimeException('cause');
        $exception = new MessageRouteNotFoundException('message', 3, $previous, ['message' => 'Missing']);

        self::assertSame('message', $exception->getMessage());
        self::assertSame(3, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(['message' => 'Missing'], $exception->context);
    }

    #[Test]
    public function it_merges_what_is_added_to_its_context(): void
    {
        $exception = new MessageRouteNotFoundException(context: ['message' => 'Missing', 'kept' => true]);

        self::assertSame($exception, $exception->addContext(['message' => 'Replaced', 'publisher' => 'queue']));
        self::assertSame(['message' => 'Replaced', 'kept' => true, 'publisher' => 'queue'], $exception->context);
    }

    #[Test]
    public function it_describes_a_message_type_without_a_route(): void
    {
        $exception = MessageRouteNotFoundException::noRouteFor("class@anonymous\0/app/src/Job.php:3$0");

        self::assertSame(
            'Unable to publish "class@anonymous\\000/app/src/Job.php:3$0": no route is registered for the message type.',
            $exception->getMessage(),
        );
        self::assertSame(['message' => 'class@anonymous\\000/app/src/Job.php:3$0'], $exception->context);
    }
}
