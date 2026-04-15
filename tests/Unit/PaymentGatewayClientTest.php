<?php

declare(strict_types=1);

namespace Mrudchenko\PaymentMtlsClient\Tests\Unit;

use GuzzleHttp\ClientInterface;
use Mrudchenko\PaymentMtlsClient\HmacSigner;
use Mrudchenko\PaymentMtlsClient\MtlsHttpClient;
use Mrudchenko\PaymentMtlsClient\PaymentGatewayClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class PaymentGatewayClientTest extends TestCase
{
    public function testSendUsesSameCanonicalPayloadForSignatureAndOutgoingQuery(): void
    {
        $secret = 'test-secret';
        $payload = [
            'z' => '9',
            'a' => '1',
            'n' => null,
        ];

        $hmacSigner = new HmacSigner();
        $expectedCanonicalQuery = $hmacSigner->canonicalize($payload);
        $expectedSignature = $hmacSigner->sign($payload, $secret);

        $httpClient = $this->createMock(ClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $httpClient
            ->expects(self::once())
            ->method('request')
            ->with(
                'GET',
                'https://example.com/payments',
                self::callback(static function (array $options) use ($expectedCanonicalQuery, $expectedSignature): bool {
                    return $options['query'] === $expectedCanonicalQuery
                        && ($options['headers']['X-Signature'] ?? null) === $expectedSignature;
                })
            )
            ->willReturn($response);

        $mtlsHttpClient = new MtlsHttpClient(
            '/path/to/client.pem',
            '',
            null,
            true,
            10.0,
            $httpClient
        );

        $client = new PaymentGatewayClient($mtlsHttpClient, $hmacSigner, $secret);
        $client->send('https://example.com/payments', $payload);
    }
}
