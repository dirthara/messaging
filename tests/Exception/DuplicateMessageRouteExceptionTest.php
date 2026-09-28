<?php

declare(strict_types=1);

namespace Dirthara\Messaging\Tests\Exception;

use RuntimeException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Dirthara\Messaging\Exception\MessagingException;
use Dirthara\Messaging\Exception\DuplicateMessageRouteException;

final class DuplicateMessageRouteExceptionTest extends TestCase
{
    #[Test]
    public function it_carries_nothing_by_default(): void
    {
        $exception = new DuplicateMessageRouteException();

        self::assertInstanceOf(MessagingException::class, $exception);
        self::assertInstanceOf(InvalidArgumentException::class, $exception);
        self::assertSame('', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertNull($exception->getPrevious());
        self::assertSame([], $exception->context);
    }

    #[Test]
    public function it_keeps_a_previous_exception_and_its_context(): void
    {
        $previous = new RuntimeException('cause');
        $exception = new DuplicateMessageRouteException('message', 3, $previous, ['message' => 'Missing']);

        self::assertSame('message', $exception->getMessage());
        self::assertSame(3, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(['message' => 'Missing'], $exception->context);
    }

    #[Test]
    public function it_merges_what_is_added_to_its_context(): void
    {
        $exception = new DuplicateMessageRouteException(context: ['message' => 'Missing', 'kept' => true]);

        self::assertSame($exception, $exception->addContext(['message' => 'Replaced', 'publisher' => 'queue']));
        self::assertSame(['message' => 'Replaced', 'kept' => true, 'publisher' => 'queue'], $exception->context);
    }

    #[Test]
    public function it_describes_a_message_type_that_already_has_a_route(): void
    {
        $exception = DuplicateMessageRouteException::alreadyRouted("Generate\nInvoice\0");

        self::assertSame(
            'Unable to route "Generate\\nInvoice\\000": the message type already has a route, and a route is never replaced.',
            $exception->getMessage(),
        );
        self::assertSame(['message' => 'Generate\\nInvoice\\000'], $exception->context);
    }
}
