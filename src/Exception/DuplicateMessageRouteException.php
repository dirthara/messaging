<?php

declare(strict_types=1);

namespace Dirthara\Messaging\Exception;

use Throwable;
use InvalidArgumentException;

use function sprintf;

final class DuplicateMessageRouteException extends InvalidArgumentException implements MessagingException
{
    use HasExceptionContext;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);

        $this->context = $context;
    }

    public static function alreadyRouted(string $message): self
    {
        return new self(
            message: sprintf(
                'Unable to route "%s": the message type already has a route, and a route is never replaced.',
                self::printable($message),
            ),
            context: ['message' => self::printable($message)],
        );
    }
}
