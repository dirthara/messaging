<?php

declare(strict_types=1);

namespace Dirthara\Messaging\Tests\Fixtures;

final readonly class GenerateInvoice implements BillingMessage
{
    public function __construct(
        public string $invoiceId,
    ) {}
}
