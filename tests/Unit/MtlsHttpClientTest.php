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

    public function testGetUsesSeparateSslKeyWhenDifferentKeyPathIsProvided(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $httpClient
            ->expects(self::once())
            ->method('request')
            ->with(
                'GET',
                'https://example.com',
                self::callback(static function (array $options): bool {
                    return $options['cert'] === '/path/to/client-cert.pem'
                        && $options['ssl_key'] === ['/path/to/client-key.pem', 'passphrase']
                        && $options['verify'] === true
                        && $options['timeout'] === 10.0
                        && $options['http_errors'] === false;
                })
            )
            ->willReturn($response);

        $client = new MtlsHttpClient(
            '/path/to/client-cert.pem',
            '/path/to/client-key.pem',
            'passphrase',
            true,
            10.0,
            $httpClient
        );

        $client->get('https://example.com', ['foo' => 'bar'], 'dummy-signature');
    }

    public function testGetUsesCombinedPemWithoutSslKeyWhenKeyPathIsEmpty(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $httpClient
            ->expects(self::once())
            ->method('request')
            ->with(
                'GET',
                'https://example.com',
                self::callback(static function (array $options): bool {
                    return $options['cert'] === ['/path/to/client.pem', 'passphrase']
                        && !isset($options['ssl_key'])
                        && $options['verify'] === true
                        && $options['timeout'] === 10.0
                        && $options['http_errors'] === false;
                })
            )
            ->willReturn($response);

        $client = new MtlsHttpClient(
            '/path/to/client.pem',
            '',
            'passphrase',
            true,
            10.0,
            $httpClient
        );

        $client->get('https://example.com', ['foo' => 'bar'], 'dummy-signature');
    }
}
