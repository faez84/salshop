<?php

declare(strict_types=1);

namespace App\Checkout\Domain\ValueObject;
final class PaypalCreateOrderResult
{
    public function __construct(
        private readonly string $providerOrderId = '',
        private readonly string $approvalUrl = ''
    ) {
    }

    public function getProviderOrderId(): string
    {
        return $this->providerOrderId;
    }

    public function getApprovalUrl(): string
    {
        return $this->approvalUrl;
    }
}
