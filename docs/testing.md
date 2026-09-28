---
id: testing
title: Testing
sidebar_position: 5
description: Record published messages in tests with FakeMessagePublisher.
---

## Record published messages

`Dirthara\Messaging\Testing\FakeMessagePublisher` is a `MessagePublisher` for tests. It records every message it is
given, in order, and does nothing else with them:

```php
use Dirthara\Messaging\Testing\FakeMessagePublisher;

$messages = new FakeMessagePublisher();

new CompleteOrder($messages)($order);

$messages->published; // every published message, in publication order
```

`published` holds the exact instances that were published, as a `list<object>`. A message published twice appears twice.
The property is read-only from outside the class.

The fake works with any test framework, and asserts nothing itself:

```php
self::assertEquals(
    [new GenerateInvoice($order->invoiceId)],
    $messages->published,
);
```

## Find messages of one type

`published()` returns the recorded messages of one type, in publication order, and `hasPublished()` reports whether
there are any:

```php
$invoices = $messages->published(GenerateInvoice::class); // list<GenerateInvoice>

$messages->hasPublished(SendWelcomeEmail::class); // bool
```

Both match the exact class, as [routes](routing.md#routes-match-the-exact-class) do: a subclass of the type, or a class
implementing it, does not count. A type that does not exist matches nothing.

:::note
The fake always accepts a message. To test how code behaves when publishing fails, give it a publisher of its own that
throws.
:::
