---
id: routing
title: Routing messages
sidebar_position: 4
description: Send each message type to its own publisher with RoutingMessagePublisher, and what routing throws.
---

## Route messages to publishers

`Dirthara\Messaging\RoutingMessagePublisher` is a `MessagePublisher` that passes each message on to another publisher,
chosen by the message's class:

```php
use Dirthara\Messaging\RoutingMessagePublisher;

$publisher = new RoutingMessagePublisher();

$publisher->route(
    GenerateInvoice::class,
    $queuePublisher,
);

$publisher->route(
    SynchroniseCustomer::class,
    $crmPublisher,
);

$publisher->publish(
    new GenerateInvoice($invoiceId),
);
```

`GenerateInvoice` goes to `$queuePublisher`, and `SynchroniseCustomer` to `$crmPublisher`. The code that publishes
depends only on `MessagePublisher`, and does not know which destination each message has.

`publish()` passes the message it is given, the same instance, to the routed publisher exactly once. It does not copy,
change, serialise, or wrap it.

## Routes match the exact class

A route matches messages whose class is exactly the routed type. It does not match a subclass of that type, or a class
that implements it:

```php
$publisher->route(TakePayment::class, $payments);

$publisher->publish(new TakePayment($paymentId)); // routed to $payments
$publisher->publish(new TakeDeposit($paymentId)); // TakeDeposit extends TakePayment: no route
```

This is deliberately unlike listening for events in Dirthara Events, where a listener for a parent
class or interface applies to every event of that type. Many listeners can react to one event, but exactly one
publisher takes responsibility for a message. If routes matched parent classes and interfaces, a message implementing
two routed interfaces would have two owners, and the router would need rules to pick one.

Routes can be registered for:

| Type | Accepted |
| --- | --- |
| A concrete class | Yes |
| An enum | Yes; its cases are routed |
| An anonymous class | Yes, by its generated class name |
| An interface | No: no object's exact class is an interface |
| An abstract class | No: no object's exact class is an abstract class |
| A trait, or a name that does not exist | No |

A rejected type throws `InvalidMessageTypeException` from `route()`, so a mistake in the routes shows up when the
application configures them, not when it first publishes. Class names are case-insensitive in PHP; a type written in
other letter case or with a leading backslash is routed as the class it names.

There are no wildcard routes, fallback routes, priorities, or routes to several publishers at once.

## A type is routed once

A second route for the same type throws `DuplicateMessageRouteException`:

```php
$publisher->route(GenerateInvoice::class, $first);
$publisher->route(GenerateInvoice::class, $second); // throws
```

The first route stays in place. A route cannot be replaced or removed, because two routes for one message would leave
it unclear which publisher is responsible for it.

## An unrouted message is refused

Publishing a message whose class has no route throws `MessageRouteNotFoundException`. The message is not discarded
silently, and it is not sent anywhere else.

## Failures of the routed publisher

An exception or error thrown by the routed publisher leaves `publish()` unchanged; the router does not wrap it. It does
not retry the message or try another publisher either. Retrying is up to the publisher that owns the message.

## Exceptions

Every exception implements `Dirthara\Messaging\Exception\MessagingException`, and carries its context in the `context`
property.

| Exception | Extends | Thrown when |
| --- | --- | --- |
| `InvalidMessageTypeException` | `InvalidArgumentException` | `route()` is given a type that cannot be routed. |
| `DuplicateMessageRouteException` | `InvalidArgumentException` | `route()` is given a type that already has a route. |
| `MessageRouteNotFoundException` | `RuntimeException` | `publish()` is given a message whose class has no route. |

Each exception's context holds the message type under `message`, and never the message itself, whose contents could
be sensitive. Control characters in the type, which an anonymous class name contains, are escaped in the context and
in the exception message, so the type cannot forge a line in a log.
