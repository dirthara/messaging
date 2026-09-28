---
id: intro
title: Dirthara Messaging
sidebar_position: 1
description: Transport-neutral, one-way message publishing with plain PHP objects.
---

Dirthara Messaging publishes messages. A message hands responsibility for something to whoever receives it: once it is
published, the code that published it no longer owns it, and no longer controls what happens to it.

The package defines that hand-over and nothing more. It does not decide where a message goes or how it is handled.

## Messages and events

A message is not an event, as Dirthara Events publishes them. The two say different things:

| | Says | The sender |
| --- | --- | --- |
| Event | "This happened." | Announces a fact, and does not care who reacts to it. |
| Message | "Take responsibility for this." | Hands over work, and relies on someone taking it. |

A message is not necessarily a local job either. It may end up on a queue in the same application, in a worker, in
another service, or in a program written in another language entirely.

## Messages are plain PHP objects

A message is any object. Dirthara has no message interface, base class, trait, or attribute, and a message needs no
`handle()` method, name, or version:

```php
final readonly class GenerateInvoice
{
    public function __construct(
        public InvoiceId $invoiceId,
    ) {}
}
```

Messages should be immutable, and carry identifiers and values rather than services; see
[what to put in a message](publishing.md#what-to-put-in-a-message). The package does not enforce either.

## A first message

Code that hands over work depends on `MessagePublisher`, and publishes the message:

```php
use Dirthara\Messaging\Contract\MessagePublisher;

final readonly class CompleteOrder
{
    public function __construct(
        private MessagePublisher $messages,
    ) {}

    public function __invoke(Order $order): void
    {
        // ...

        $this->messages->publish(
            new GenerateInvoice($order->invoiceId),
        );
    }
}
```

`publish()` returns nothing. There is no `dispatch()` for messages, no handler that runs before `publish()` returns, and
no answer to wait for: publishing is always one-way.

Which publisher the application passes in decides where the message goes. A
[`RoutingMessagePublisher`](routing.md) sends each message type to its own publisher, and a
[`FakeMessagePublisher`](testing.md) records messages in tests.

## What this package does not do

Dirthara Messaging has no:

- message handlers, and no synchronous dispatch of a message to one;
- command bus, message bus, or request and response messaging;
- queues, workers, retries, delays, or failed-message storage;
- serialisation of messages;
- transports, such as AMQP, Kafka, SQS, Redis, HTTP, or a database;
- external messaging protocol, message names, versions, or envelopes.

Those belong in packages built on top of it, such as a future `dirthara/queue`, which implement `MessagePublisher`.
Application code that publishes through `MessagePublisher` does not change when they are added.

| Page | Covers |
| --- | --- |
| [Installation](installation.md) | Requirements and installation. |
| [Publishing messages](publishing.md) | What a successful `publish()` means, what to put in a message, and publishing to other systems. |
| [Routing messages](routing.md) | Sending each message type to its own publisher, and the exceptions routing throws. |
| [Testing](testing.md) | Recording published messages in tests with `FakeMessagePublisher`. |
