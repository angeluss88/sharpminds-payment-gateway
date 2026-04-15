<?php

declare(strict_types=1);

namespace Mrudchenko\PaymentMtlsClient;

use Psr\Http\Message\ResponseInterface;

/**
 * Orchestrates request signing and mTLS HTTP sending.
 *
 * The API URL is intentionally not stored in this client. Callers provide the
 * target URL for each request via send().
 */
final class PaymentGatewayClient
{
    /**
     * @param string $hmacSecret Shared secret used for payload signatures.
     */
    public function __construct(
        private MtlsHttpClient $httpClient,
        private HmacSigner $hmacSigner,
        private string $hmacSecret
    ) {
    }

    /**
     * Creates a ready-to-use client from .env values in the project root.
     *
     * Loads mTLS transport settings and HMAC secret only; request URLs are
     * still provided per call via send().
     */
    public static function fromEnv(string $projectRoot): self
    {
        $config = EnvConfig::fromProjectRoot($projectRoot);

        $httpClient = new MtlsHttpClient(
            $config->getClientCertificatePath(),
            $config->getClientPrivateKeyPath(),
            $config->getClientPrivateKeyPassphrase(),
            $config->shouldVerifyTls(),
            $config->getTimeout()
        );

        return new self($httpClient, new HmacSigner(), $config->getHmacSecret());
    }

    /**
     * Sends a signed GET request to the provided URL.
     *
     * URL is passed explicitly to keep endpoint selection at call site.
     *
     * @param array<int|string, scalar|null> $payload
     */
    public function send(string $url, array $payload): ResponseInterface
    {
        $signature = $this->hmacSigner->sign($payload, $this->hmacSecret);

        return $this->httpClient->get($url, $payload, $signature);
    }
}
