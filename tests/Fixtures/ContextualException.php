<?php

declare(strict_types=1);

namespace Dirthara\Messaging\Tests\Fixtures;

use RuntimeException;
use Dirthara\Messaging\Exception\MessagingException;
use Dirthara\Messaging\Exception\HasExceptionContext;

final class ContextualException extends RuntimeException implements MessagingException
{
    use HasExceptionContext;

    public static function describe(string $value): string
    {
        return self::printable($value);
    }
}
