<?php

declare(strict_types=1);

namespace Dirthara\Messaging\Tests\Testing;

use stdClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Dirthara\Messaging\Contract\MessagePublisher;
use Dirthara\Messaging\Tests\Fixtures\Maintenance;
use Dirthara\Messaging\Testing\FakeMessagePublisher;
use Dirthara\Messaging\Tests\Fixtures\BillingMessage;
use Dirthara\Messaging\Tests\Fixtures\GenerateInvoice;
use Dirthara\Messaging\Tests\Fixtures\SendWelcomeEmail;
use Dirthara\Messaging\Tests\Fixtures\Inheritance\TakeDeposit;
use Dirthara\Messaging\Tests\Fixtures\Inheritance\TakePayment;

use function strtolower;

final class FakeMessagePublisherTest extends TestCase
{
    #[Test]
    public function it_is_a_message_publisher(): void
    {
        self::assertInstanceOf(MessagePublisher::class, new FakeMessagePublisher());
    }

    #[Test]
    public function it_starts_without_published_messages(): void
    {
        $messages = new FakeMessagePublisher();

        self::assertSame([], $messages->published);
        self::assertSame([], $messages->published(GenerateInvoice::class));
        self::assertFalse($messages->hasPublished(GenerateInvoice::class));
    }

    #[Test]
    public function it_records_the_same_instances_in_publication_order(): void
    {
        $messages = new FakeMessagePublisher();
        $first = new GenerateInvoice('INV-1');
        $second = new SendWelcomeEmail('user-1');
        $third = new stdClass();

        $messages->publish($first);
        $messages->publish($second);
        $messages->publish($third);

        self::assertSame([$first, $second, $third], $messages->published);
    }

    #[Test]
    public function it_records_a_message_published_twice_twice(): void
    {
        $messages = new FakeMessagePublisher();
        $message = new GenerateInvoice('INV-1');

        $messages->publish($message);
        $messages->publish($message);

        self::assertSame([$message, $message], $messages->published);
        self::assertSame([$message, $message], $messages->published(GenerateInvoice::class));
    }

    #[Test]
    public function it_accepts_enum_cases_and_anonymous_objects(): void
    {
        $messages = new FakeMessagePublisher();
        $anonymous = new class {};

        $messages->publish(Maintenance::Start);
        $messages->publish($anonymous);

        self::assertSame([Maintenance::Start, $anonymous], $messages->published);
        self::assertSame([Maintenance::Start], $messages->published(Maintenance::class));
        self::assertTrue($messages->hasPublished($anonymous::class));
    }

    #[Test]
    public function it_returns_the_messages_of_one_exact_class_in_publication_order(): void
    {
        $messages = new FakeMessagePublisher();
        $first = new GenerateInvoice('INV-1');
        $email = new SendWelcomeEmail('user-1');
        $second = new GenerateInvoice('INV-2');

        $messages->publish($first);
        $messages->publish($email);
        $messages->publish($second);

        self::assertSame([$first, $second], $messages->published(GenerateInvoice::class));
        self::assertSame([$email], $messages->published(SendWelcomeEmail::class));
        self::assertTrue($messages->hasPublished(GenerateInvoice::class));
        self::assertTrue($messages->hasPublished(SendWelcomeEmail::class));
        self::assertFalse($messages->hasPublished(TakePayment::class));
    }

    #[Test]
    public function it_matches_only_the_exact_class_like_routing_does(): void
    {
        $messages = new FakeMessagePublisher();
        $deposit = new TakeDeposit('PAY-1');

        $messages->publish($deposit);

        self::assertSame([], $messages->published(TakePayment::class));
        self::assertFalse($messages->hasPublished(TakePayment::class));
        self::assertSame([], $messages->published(BillingMessage::class));
        self::assertFalse($messages->hasPublished(BillingMessage::class));
        self::assertSame([$deposit], $messages->published(TakeDeposit::class));
        self::assertTrue($messages->hasPublished(TakeDeposit::class));
    }

    #[Test]
    public function it_matches_a_type_written_in_other_letter_case_or_with_a_leading_backslash(): void
    {
        $messages = new FakeMessagePublisher();
        $message = new GenerateInvoice('INV-1');

        /** @var class-string<GenerateInvoice> $lowercase */
        $lowercase = strtolower(GenerateInvoice::class);

        $messages->publish($message);

        self::assertSame([$message], $messages->published($lowercase));
        self::assertTrue($messages->hasPublished('\\' . GenerateInvoice::class));
    }

    #[Test]
    public function it_matches_nothing_for_a_type_that_does_not_exist(): void
    {
        $messages = new FakeMessagePublisher();
        $messages->publish(new GenerateInvoice('INV-1'));

        /** @var class-string $missing */
        $missing = 'Dirthara\Messaging\Tests\Fixtures\MissingMessage';

        self::assertSame([], $messages->published($missing));
        self::assertFalse($messages->hasPublished($missing));
    }

    #[Test]
    public function it_leaves_the_published_message_unchanged(): void
    {
        $messages = new FakeMessagePublisher();
        $message = new stdClass();
        $message->invoiceId = 'INV-1';
        $expected = clone $message;

        $messages->publish($message);

        self::assertEquals($expected, $message);
        self::assertSame($message, $messages->published[0]);
    }
}
