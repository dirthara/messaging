<?php

declare(strict_types=1);

namespace Dirthara\Messaging\Exception;

use Throwable;
use RuntimeException;

use function sprintf;

final class MessageRouteNotFoundException extends RuntimeException implements MessagingException
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

    public static function noRouteFor(string $message): self
    {
        return new self(
            message: sprintf(
                'Unable to publish "%s": no route is registered for the message type.',
                self::printable($message),
            ),
            context: ['message' => self::printable($message)],
        );
    }
}
