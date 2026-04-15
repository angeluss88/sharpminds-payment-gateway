<?php

declare(strict_types=1);

namespace Mrudchenko\PaymentMtlsClient;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Mrudchenko\PaymentMtlsClient\Exception\HttpRequestException;
use Psr\Http\Message\ResponseInterface;

final class MtlsHttpClient
{
    private const SUCCESS_STATUS_MIN = 200;
    private const SUCCESS_STATUS_MAX = 299;

    private ClientInterface $httpClient;

    /** @var array<string, mixed> */
    private array $baseOptions;

    public function __construct(
        string $clientCertificatePath,
        string $clientPrivateKeyPath,
        ?string $clientPrivateKeyPassphrase,
        bool $verifyTls,
        float $timeout,
        ?ClientInterface $httpClient = null
    ) {
        $this->httpClient = $httpClient ?? new Client();

        $hasPassphrase = $clientPrivateKeyPassphrase !== null && $clientPrivateKeyPassphrase !== '';

        $certOption = $clientCertificatePath;
        if ($hasPassphrase) {
            $certOption = [$clientCertificatePath, $clientPrivateKeyPassphrase];
        }

        $this->baseOptions = [
            'cert' => $certOption,
            'verify' => $verifyTls,
            'timeout' => $timeout,
            'http_errors' => false,
        ];

        if ($this->shouldUseSeparateSslKey($clientCertificatePath, $clientPrivateKeyPath)) {
            $this->baseOptions['ssl_key'] = $hasPassphrase
                ? [$clientPrivateKeyPath, $clientPrivateKeyPassphrase]
                : $clientPrivateKeyPath;
        }
    }

    /**
     * @param array<int|string, scalar|null> $payload
     */
    public function get(string $url, array $payload, string $signature): ResponseInterface
    {
        $options = $this->baseOptions;
        $options['query'] = $payload;
        $options['headers'] = [
            'X-Signature' => $signature,
        ];

        try {
            $response = $this->httpClient->request('GET', $url, $options);
        } catch (GuzzleException $exception) {
            throw new HttpRequestException(
                sprintf('HTTP transport error while requesting "%s": %s', $url, $exception->getMessage()),
                0,
                $exception
            );
        }

        $statusCode = $response->getStatusCode();
        if (!$this->isSuccessfulStatusCode($statusCode)) {
            $reasonPhrase = $response->getReasonPhrase();
            $reasonDetails = $reasonPhrase !== '' ? sprintf(' (%s)', $reasonPhrase) : '';

            throw new HttpRequestException(
                sprintf('Request to "%s" failed with HTTP status %d%s.', $url, $statusCode, $reasonDetails)
            );
        }

        return $response;
    }

    private function isSuccessfulStatusCode(int $statusCode): bool
    {
        return $statusCode >= self::SUCCESS_STATUS_MIN && $statusCode <= self::SUCCESS_STATUS_MAX;
    }

    private function shouldUseSeparateSslKey(string $clientCertificatePath, string $clientPrivateKeyPath): bool
    {
        $trimmedKeyPath = trim($clientPrivateKeyPath);
        if ($trimmedKeyPath === '') {
            return false;
        }

        $trimmedCertificatePath = trim($clientCertificatePath);
        if ($trimmedCertificatePath === $trimmedKeyPath) {
            return false;
        }

        $resolvedCertificatePath = realpath($trimmedCertificatePath);
        $resolvedKeyPath = realpath($trimmedKeyPath);
        if ($resolvedCertificatePath !== false && $resolvedKeyPath !== false && $resolvedCertificatePath === $resolvedKeyPath) {
            return false;
        }

        return true;
    }
}
