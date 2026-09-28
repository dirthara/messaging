<?php

declare(strict_types=1);

namespace Dirthara\Messaging;

use ReflectionClass;
use Dirthara\Messaging\Contract\MessagePublisher;
use Dirthara\Messaging\Exception\InvalidMessageTypeException;
use Dirthara\Messaging\Exception\MessageRouteNotFoundException;
use Dirthara\Messaging\Exception\DuplicateMessageRouteException;

use function class_exists;
use function array_key_exists;
use function interface_exists;

final class RoutingMessagePublisher implements MessagePublisher
{
    /**
     * @var array<class-string, MessagePublisher>
     */
    private array $routes = [];

    /**
     * @param class-string $message
     *
     * @throws InvalidMessageTypeException
     * @throws DuplicateMessageRouteException
     */
    public function route(string $message, MessagePublisher $publisher): void
    {
        $type = self::exactType($message);

        if (array_key_exists($type, $this->routes)) {
            throw DuplicateMessageRouteException::alreadyRouted($type);
        }

        $this->routes[$type] = $publisher;
    }

    /**
     * @throws MessageRouteNotFoundException
     */
    public function publish(object $message): void
    {
        $publisher = $this->routes[$message::class] ?? throw MessageRouteNotFoundException::noRouteFor($message::class);

        $publisher->publish($message);
    }

    /**
     * @return class-string
     *
     * @throws InvalidMessageTypeException
     */
    private static function exactType(string $message): string
    {
        if (interface_exists($message)) {
            throw InvalidMessageTypeException::interfaceType($message);
        }

        if (!class_exists($message)) {
            throw InvalidMessageTypeException::notAnObjectType($message);
        }

        $class = new ReflectionClass($message);

        if ($class->isAbstract()) {
            throw InvalidMessageTypeException::abstractClass($message);
        }

        return $class->getName();
    }
}
