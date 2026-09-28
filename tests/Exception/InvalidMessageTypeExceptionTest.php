<?php

declare(strict_types=1);

namespace Dirthara\Messaging\Tests\Exception;

use RuntimeException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Dirthara\Messaging\Exception\MessagingException;
use Dirthara\Messaging\Exception\InvalidMessageTypeException;

final class InvalidMessageTypeExceptionTest extends TestCase
{
    #[Test]
    public function it_carries_nothing_by_default(): void
    {
        $exception = new InvalidMessageTypeException();

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
        $exception = new InvalidMessageTypeException('message', 3, $previous, ['message' => 'Missing']);

        self::assertSame('message', $exception->getMessage());
        self::assertSame(3, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(['message' => 'Missing'], $exception->context);
    }

    #[Test]
    public function it_merges_what_is_added_to_its_context(): void
    {
        $exception = new InvalidMessageTypeException(context: ['message' => 'Missing', 'kept' => true]);

        self::assertSame($exception, $exception->addContext(['message' => 'Replaced', 'publisher' => 'queue']));
        self::assertSame(['message' => 'Replaced', 'kept' => true, 'publisher' => 'queue'], $exception->context);
    }

    #[Test]
    public function it_describes_a_message_type_no_object_can_have(): void
    {
        $exception = InvalidMessageTypeException::notAnObjectType("Missing\n\0Message\x7f");

        self::assertSame(
            'Unable to route "Missing\\n\\000Message\\177": a message type has to be an existing class or enum.',
            $exception->getMessage(),
        );
        self::assertSame(['message' => 'Missing\\n\\000Message\\177'], $exception->context);
    }

    #[Test]
    public function it_describes_an_interface_given_as_a_message_type(): void
    {
        $exception = InvalidMessageTypeException::interfaceType("Billing\nMessage");

        self::assertSame(
            'Unable to route "Billing\\nMessage": it is an interface, and messages are routed by their exact class.',
            $exception->getMessage(),
        );
        self::assertSame(['message' => 'Billing\\nMessage'], $exception->context);
    }

    #[Test]
    public function it_describes_an_abstract_class_given_as_a_message_type(): void
    {
        $exception = InvalidMessageTypeException::abstractClass("Customer\nMessage");

        self::assertSame(
            'Unable to route "Customer\\nMessage": it is an abstract class, and messages are routed by their exact class.',
            $exception->getMessage(),
        );
        self::assertSame(['message' => 'Customer\\nMessage'], $exception->context);
    }
}
