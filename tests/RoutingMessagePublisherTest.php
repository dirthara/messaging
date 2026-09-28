<?php

declare(strict_types=1);

namespace Dirthara\Messaging\Tests;

use Error;
use stdClass;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Dirthara\Messaging\Contract\MessageRouter;
use Dirthara\Messaging\RoutingMessagePublisher;
use Dirthara\Messaging\Tests\Fixtures\Traceable;
use Dirthara\Messaging\Contract\MessagePublisher;
use Dirthara\Messaging\Tests\Fixtures\Maintenance;
use Dirthara\Messaging\Exception\MessagingException;
use Dirthara\Messaging\Tests\Fixtures\BillingMessage;
use Dirthara\Messaging\Tests\Fixtures\GenerateInvoice;
use Dirthara\Messaging\Tests\Fixtures\SendWelcomeEmail;
use Dirthara\Messaging\Tests\Fixtures\RecordingPublisher;
use Dirthara\Messaging\Exception\InvalidMessageTypeException;
use Dirthara\Messaging\Tests\Fixtures\Inheritance\TakeDeposit;
use Dirthara\Messaging\Tests\Fixtures\Inheritance\TakePayment;
use Dirthara\Messaging\Exception\MessageRouteNotFoundException;
use Dirthara\Messaging\Exception\DuplicateMessageRouteException;
use Dirthara\Messaging\Tests\Fixtures\Inheritance\CustomerMessage;
use Dirthara\Messaging\Tests\Fixtures\Inheritance\SynchroniseCustomer;

use function strtolower;
use function strtoupper;

final class RoutingMessagePublisherTest extends TestCase
{
    #[Test]
    public function it_is_a_message_publisher(): void
    {
        self::assertInstanceOf(MessagePublisher::class, new RoutingMessagePublisher());
    }

    #[Test]
    public function it_is_a_message_router(): void
    {
        self::assertInstanceOf(MessageRouter::class, new RoutingMessagePublisher());
    }

    #[Test]
    public function it_publishes_through_the_routes_configured_on_it_as_a_message_router(): void
    {
        $router = new RoutingMessagePublisher();
        $publisher = new RecordingPublisher();
        $message = new GenerateInvoice('INV-1');

        self::configure($router, $publisher);
        $router->publish($message);

        self::assertSame([$message], $publisher->published);
    }

    #[Test]
    public function it_hands_the_same_message_to_the_routed_publisher_exactly_once(): void
    {
        $router = new RoutingMessagePublisher();
        $publisher = new RecordingPublisher();
        $message = new GenerateInvoice('INV-1');

        $router->route(GenerateInvoice::class, $publisher);
        $router->publish($message);

        self::assertCount(1, $publisher->published);
        self::assertSame($message, $publisher->published[0]);
    }

    #[Test]
    public function it_routes_each_message_type_to_its_own_publisher(): void
    {
        $router = new RoutingMessagePublisher();
        $invoices = new RecordingPublisher();
        $emails = new RecordingPublisher();
        $invoice = new GenerateInvoice('INV-1');
        $email = new SendWelcomeEmail('user-1');
        $another = new GenerateInvoice('INV-2');

        $router->route(GenerateInvoice::class, $invoices);
        $router->route(SendWelcomeEmail::class, $emails);

        $router->publish($invoice);
        $router->publish($email);
        $router->publish($another);

        self::assertSame([$invoice, $another], $invoices->published);
        self::assertSame([$email], $emails->published);
    }

    #[Test]
    public function it_does_not_route_a_message_by_its_parent_class(): void
    {
        $router = new RoutingMessagePublisher();
        $payments = new RecordingPublisher();
        $payment = new TakePayment('PAY-1');

        $router->route(TakePayment::class, $payments);
        $router->publish($payment);

        $this->expectException(MessageRouteNotFoundException::class);

        try {
            $router->publish(new TakeDeposit('PAY-2'));
        } finally {
            self::assertSame([$payment], $payments->published);
        }
    }

    #[Test]
    public function it_does_not_route_a_subclass_route_to_its_parent(): void
    {
        $router = new RoutingMessagePublisher();
        $deposits = new RecordingPublisher();

        $router->route(TakeDeposit::class, $deposits);

        $this->expectException(MessageRouteNotFoundException::class);

        try {
            $router->publish(new TakePayment('PAY-1'));
        } finally {
            self::assertSame([], $deposits->published);
        }
    }

    #[Test]
    public function it_routes_an_enum_case_by_its_enum(): void
    {
        $router = new RoutingMessagePublisher();
        $publisher = new RecordingPublisher();

        $router->route(Maintenance::class, $publisher);
        $router->publish(Maintenance::Start);
        $router->publish(Maintenance::Stop);

        self::assertSame([Maintenance::Start, Maintenance::Stop], $publisher->published);
    }

    #[Test]
    public function it_routes_a_plain_object(): void
    {
        $router = new RoutingMessagePublisher();
        $publisher = new RecordingPublisher();
        $message = new stdClass();

        $router->route(stdClass::class, $publisher);
        $router->publish($message);

        self::assertSame([$message], $publisher->published);
    }

    #[Test]
    public function it_routes_a_type_registered_in_other_letter_case_or_with_a_leading_backslash(): void
    {
        $router = new RoutingMessagePublisher();
        $invoices = new RecordingPublisher();
        $emails = new RecordingPublisher();
        $invoice = new GenerateInvoice('INV-1');
        $email = new SendWelcomeEmail('user-1');

        $lowercase = strtolower(GenerateInvoice::class);

        $router->route($lowercase, $invoices);
        $router->route('\\' . SendWelcomeEmail::class, $emails);
        $router->publish($invoice);
        $router->publish($email);

        self::assertSame([$invoice], $invoices->published);
        self::assertSame([$email], $emails->published);
    }

    #[Test]
    public function it_leaves_the_message_unchanged(): void
    {
        $router = new RoutingMessagePublisher();
        $message = new stdClass();
        $message->invoiceId = 'INV-1';
        $expected = clone $message;

        $router->route(stdClass::class, new RecordingPublisher());
        $router->publish($message);

        self::assertEquals($expected, $message);
    }

    #[Test]
    public function it_rejects_a_second_route_for_the_same_message_type(): void
    {
        $router = new RoutingMessagePublisher();
        $first = new RecordingPublisher();
        $message = new GenerateInvoice('INV-1');

        $router->route(GenerateInvoice::class, $first);

        try {
            $router->route(GenerateInvoice::class, new RecordingPublisher());
            self::fail('The duplicate route was accepted.');
        } catch (DuplicateMessageRouteException $exception) {
            self::assertInstanceOf(MessagingException::class, $exception);
            self::assertSame(['message' => GenerateInvoice::class], $exception->context);
        }

        $router->publish($message);

        self::assertSame([$message], $first->published);
    }

    #[Test]
    public function it_rejects_a_second_route_for_the_same_message_type_spelled_differently(): void
    {
        $router = new RoutingMessagePublisher();

        $uppercase = '\\' . strtoupper(GenerateInvoice::class);

        $router->route(GenerateInvoice::class, new RecordingPublisher());

        $this->expectExceptionObject(DuplicateMessageRouteException::alreadyRouted(GenerateInvoice::class));

        $router->route($uppercase, new RecordingPublisher());
    }

    #[Test]
    public function it_refuses_a_message_without_a_route(): void
    {
        $router = new RoutingMessagePublisher();
        $router->route(SendWelcomeEmail::class, new RecordingPublisher());

        try {
            $router->publish(new GenerateInvoice('INV-1'));
            self::fail('The unrouted message was accepted.');
        } catch (MessageRouteNotFoundException $exception) {
            self::assertInstanceOf(MessagingException::class, $exception);
            self::assertSame(['message' => GenerateInvoice::class], $exception->context);
        }
    }

    #[Test]
    public function it_names_an_anonymous_message_class_without_control_characters(): void
    {
        $message = new class {};

        try {
            new RoutingMessagePublisher()->publish($message);
            self::fail('The unrouted message was accepted.');
        } catch (MessageRouteNotFoundException $exception) {
            self::assertStringNotContainsString("\0", $exception->getMessage());
            self::assertIsString($exception->context['message']);
            self::assertStringNotContainsString("\0", $exception->context['message']);
            self::assertStringStartsWith('class@anonymous\\000', $exception->context['message']);
        }
    }

    #[Test]
    public function it_routes_an_anonymous_message_class_by_its_exact_class(): void
    {
        $router = new RoutingMessagePublisher();
        $publisher = new RecordingPublisher();
        $message = new class {};

        $router->route($message::class, $publisher);
        $router->publish($message);

        self::assertSame([$message], $publisher->published);
    }

    #[Test]
    #[TestWith(['Dirthara\Messaging\Tests\Fixtures\MissingMessage'])]
    #[TestWith([''])]
    #[TestWith([Traceable::class])]
    public function it_rejects_a_type_no_object_can_have(string $message): void
    {
        $this->expectExceptionObject(InvalidMessageTypeException::notAnObjectType($message));

        new RoutingMessagePublisher()->route($message, new RecordingPublisher());
    }

    #[Test]
    public function it_rejects_an_interface_because_no_message_has_one_as_its_exact_class(): void
    {
        $this->expectExceptionObject(InvalidMessageTypeException::interfaceType(BillingMessage::class));

        new RoutingMessagePublisher()->route(BillingMessage::class, new RecordingPublisher());
    }

    #[Test]
    public function it_rejects_an_abstract_class_because_no_message_has_one_as_its_exact_class(): void
    {
        $this->expectExceptionObject(InvalidMessageTypeException::abstractClass(CustomerMessage::class));

        new RoutingMessagePublisher()->route(CustomerMessage::class, new RecordingPublisher());
    }

    #[Test]
    public function it_still_routes_the_concrete_class_of_an_abstract_parent(): void
    {
        $router = new RoutingMessagePublisher();
        $publisher = new RecordingPublisher();
        $message = new SynchroniseCustomer();

        $router->route(SynchroniseCustomer::class, $publisher);
        $router->publish($message);

        self::assertSame([$message], $publisher->published);
    }

    #[Test]
    public function it_lets_a_publisher_exception_through_unchanged(): void
    {
        $thrown = new RuntimeException('Publication failed.');
        $publisher = new RecordingPublisher($thrown);
        $message = new GenerateInvoice('INV-1');
        $router = new RoutingMessagePublisher();
        $router->route(GenerateInvoice::class, $publisher);

        try {
            $router->publish($message);
            self::fail('The publisher exception was not thrown.');
        } catch (RuntimeException $exception) {
            self::assertSame($thrown, $exception);
        }

        self::assertSame([$message], $publisher->published);
    }

    #[Test]
    public function it_lets_a_publisher_error_through_unchanged(): void
    {
        $thrown = new Error('Publisher error.');
        $router = new RoutingMessagePublisher();
        $router->route(GenerateInvoice::class, new RecordingPublisher($thrown));

        $this->expectExceptionObject($thrown);

        $router->publish(new GenerateInvoice('INV-1'));
    }

    #[Test]
    public function it_does_not_retry_or_fall_back_when_the_publisher_fails(): void
    {
        $failing = new RecordingPublisher(new RuntimeException('Publication failed.'));
        $other = new RecordingPublisher();
        $router = new RoutingMessagePublisher();
        $router->route(GenerateInvoice::class, $failing);
        $router->route(SendWelcomeEmail::class, $other);

        $this->expectException(RuntimeException::class);

        try {
            $router->publish(new GenerateInvoice('INV-1'));
        } finally {
            self::assertCount(1, $failing->published);
            self::assertSame([], $other->published);
        }
    }

    private static function configure(MessageRouter $router, MessagePublisher $publisher): void
    {
        $router->route(GenerateInvoice::class, $publisher);
    }
}
