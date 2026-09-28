<?php

declare(strict_types=1);

namespace Dirthara\Messaging\Tests\Fixtures\Inheritance;

use Dirthara\Messaging\Tests\Fixtures\BillingMessage;

readonly class TakePayment implements BillingMessage
{
    public function __construct(
        public string $paymentId,
    ) {}
}
