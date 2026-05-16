<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Payment;

use App\Checkout\Domain\ValueObject\PaypalCreateOrderResult;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PaypalClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(PAYPAL_CLIENT_ID)%')]
        private readonly string $clientId,
        #[Autowire('%env(PAYPAL_CLIENT_SECRET)%')]
        private readonly string $clientSecret,
        #[Autowire('%env(PAYPAL_BASE_URL)%')]
        private readonly string $baseUrl
    ) {
    }

    public function createOrder(
        float $amount,
        string $currencyCode,
        string $returnUrl,
        string $cancelUrl,
        string $requestId
    ): PaypalCreateOrderResult {
        $accessToken = $this->getAccessToken();
        $response = $this->httpClient->request('POST', rtrim($this->baseUrl, '/') . '/v2/checkout/orders', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'PayPal-Request-Id' => $requestId,
            ],
            'json' => [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => $currencyCode,
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ]],
                'payment_source' => [
                    'paypal' => [
                        'experience_context' => [
                            'return_url' => $returnUrl,
                            'cancel_url' => $cancelUrl,
                        ],
                    ],
                ],
            ],
        ]);

        $payload = $response->toArray(false);
        $providerOrderId = (string) ($payload['id'] ?? '');
        if ('' === $providerOrderId) {
            throw new RuntimeException('PayPal create order response did not include order id.');
        }

        $approvalUrl = $this->findApproveLink($payload);
        if (null === $approvalUrl) {
            throw new RuntimeException('PayPal create order response did not include approval url.');
        }

        return new PaypalCreateOrderResult($providerOrderId, $approvalUrl);
    }

    public function captureOrder(string $providerOrderId, string $requestId): bool
    {
        $accessToken = $this->getAccessToken();
        $response = $this->httpClient->request(
            'POST',
            rtrim($this->baseUrl, '/') . '/v2/checkout/orders/' . rawurlencode($providerOrderId) . '/capture',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                    'PayPal-Request-Id' => $requestId,
                ],
                'json' => new \stdClass(),
            ]
        );

        $payload = $response->toArray(false);
        $status = (string) ($payload['status'] ?? '');

        return 'COMPLETED' === strtoupper($status);
    }

    private function getAccessToken(): string
    {
        if ('' === trim($this->clientId) || '' === trim($this->clientSecret)) {
            throw new RuntimeException('PayPal credentials are missing.');
        }

        $response = $this->httpClient->request('POST', rtrim($this->baseUrl, '/') . '/v1/oauth2/token', [
            'auth_basic' => [$this->clientId, $this->clientSecret],
            'headers' => [
                'Accept' => 'application/json',
                'Accept-Language' => 'en_US',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'grant_type' => 'client_credentials',
            ],
        ]);

        $payload = $response->toArray(false);
        $accessToken = (string) ($payload['access_token'] ?? '');
        if ('' === $accessToken) {
            throw new RuntimeException('PayPal token response did not include access token.');
        }

        return $accessToken;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function findApproveLink(array $payload): ?string
    {
        if (!isset($payload['links']) || !is_array($payload['links'])) {
            return null;
        }

        foreach ($payload['links'] as $link) {
            if (!is_array($link)) {
                continue;
            }

            $rel = (string) ($link['rel'] ?? '');
            if ('approve' !== strtolower($rel)) {
                continue;
            }

            $href = (string) ($link['href'] ?? '');
            if ('' !== $href) {
                return $href;
            }
        }

        return null;
    }
}
