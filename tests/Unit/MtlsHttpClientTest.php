<?php

declare(strict_types=1);

namespace Mrudchenko\PaymentMtlsClient\Tests\Unit;

use GuzzleHttp\ClientInterface;
use Mrudchenko\PaymentMtlsClient\Exception\HttpRequestException;
use Mrudchenko\PaymentMtlsClient\MtlsHttpClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class MtlsHttpClientTest extends TestCase
{
    public function testGetThrowsExceptionForNon2xxResponse(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $response->method('getStatusCode')->willReturn(500);

        $httpClient
            ->method('request')
            ->with(
                'GET',
                'https://example.com',
                $this->arrayHasKey('query')
            )
            ->willReturn($response);

        $client = new MtlsHttpClient(
            '/path/to/client-cert.pem',
            '/path/to/client-key.pem',
            null,
            true,
            10.0,
            $httpClient
        );

        $this->expectException(HttpRequestException::class);
        $this->expectExceptionMessage('500');

        $client->get('https://example.com', ['foo' => 'bar'], 'dummy-signature');
    }
}
