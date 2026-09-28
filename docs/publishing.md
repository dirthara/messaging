---
id: publishing
title: Publishing messages
sidebar_position: 3
description: What a successful publish means, what to put in a message, and publishing to other systems.
---

## Publish a message

`Dirthara\Messaging\Contract\MessagePublisher` has one method:

```php
namespace Dirthara\Messaging\Contract;

interface MessagePublisher
{
    public function publish(object $message): void;
}
```

```php
$publisher->publish(
    new GenerateInvoice($invoiceId),
);
```

`publish()` takes any object and returns nothing. It is the only way to send a message: there is no `dispatch()`,
`send()`, `request()`, or asynchronous variant, because publishing a message is always a one-way hand-over.

## What a successful publish means

When `publish()` returns normally, the publisher has accepted responsibility for the message, according to the delivery
guarantees that publisher documents.

It does not mean that the message has been handled, and it does not mean the message has been stored durably. What
accepting responsibility involves depends on the publisher:

| Publisher | `publish()` returning normally may mean |
| --- | --- |
| A queue-backed publisher | The queue accepted the message. |
| An AMQP publisher | The broker accepted it, according to the configured acknowledgements. |
| An HTTP publisher | The remote endpoint accepted the request. |
| [`RoutingMessagePublisher`](routing.md) | The publisher routed to returned normally. |

A publisher that cannot accept responsibility for a message throws, rather than discarding the message.

:::caution
Once `publish()` returns, the calling code must not rely on a handler having run, on a result, on the message being
processed now, or on it staying in this process, or being handled by PHP, or by this application at all.
:::

That is also why this package has no publisher that silently discards messages. A message may stand for a payment, an
invoice, or a contract, and losing one because an application was configured with a no-op publisher is worse than
failing loudly because it was not configured at all.

## What to put in a message

A published message may be handled after `publish()` returns, in another process, or by another application, so
nothing the caller does to it afterwards can be relied on. Messages should be immutable data:

```php
final readonly class GenerateInvoice
{
    public function __construct(
        public InvoiceId $invoiceId,
    ) {}
}
```

Carry identifiers and values, which can be serialised, rather than services, repositories, open connections, or
resources, which cannot. The handler of `GenerateInvoice` loads the invoice and uses its own PDF generator; neither
belongs in the message.

The package enforces none of this, and `publish()` accepts a mutable object or one holding a service like any other.
Whether such a message works depends on the publisher: one that keeps it in the same process may manage, one that sends
it elsewhere cannot.

## Publishing to other systems

A `MessagePublisher` may hand a message to:

- a queue in the same application;
- a worker in another process;
- another application or service;
- a program written in another language or running on another runtime;
- an external broker, such as RabbitMQ or Kafka, or an HTTP endpoint.

This package implements none of those. Other packages can, by implementing `MessagePublisher`:

```php
use Dirthara\Messaging\Contract\MessagePublisher;

final readonly class QueuedMessagePublisher implements MessagePublisher
{
    public function publish(object $message): void
    {
        // hand the message to a queue, or throw if the queue does not accept it
    }
}
```

Code that publishes through `MessagePublisher` does not change when the application moves a message from one such
publisher to another.

## Class names are not message identities

A PHP class name is not a stable identity for a message outside the application. `App\Message\GenerateInvoice` can be
renamed or moved, and means nothing to a consumer written in another language. A transport that sends messages to other
systems will map classes to identities of its own, such as `billing.invoice.generate` at version 1.

This package does not decide that mapping. Messages carry no name or version, and none is required of them. Nor is
there an envelope for metadata such as a message ID, a correlation ID, a timestamp, or headers. Those belong to the
serialisation and transport packages that need them, and can be added there without changing the message classes.
