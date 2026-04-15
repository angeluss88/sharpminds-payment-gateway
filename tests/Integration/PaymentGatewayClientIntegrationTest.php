<?php

declare(strict_types=1);

namespace Mrudchenko\PaymentMtlsClient\Tests\Integration;

use Mrudchenko\PaymentMtlsClient\EnvConfig;
use Mrudchenko\PaymentMtlsClient\Exception\ConfigurationException;
use Mrudchenko\PaymentMtlsClient\PaymentGatewayClient;
use PHPUnit\Framework\TestCase;

final class PaymentGatewayClientIntegrationTest extends TestCase
{
    public function testSendReturns2xxResponseWithMtls(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $config = $this->loadConfigOrSkip($projectRoot);
        $certificatePath = $this->resolvePath($projectRoot, $config->getClientCertificatePath());

        if (!is_file($certificatePath)) {
            self::markTestSkipped(
                sprintf(
                    'mTLS client certificate was not found at "%s". Update your .env before running integration tests.',
                    $certificatePath
                )
            );
        }

        $privateKeyPath = $config->getClientPrivateKeyPath();
        $resolvedPrivateKeyPath = $privateKeyPath !== '' ? $this->resolvePath($projectRoot, $privateKeyPath) : '';
        if ($resolvedPrivateKeyPath !== '' && !is_file($resolvedPrivateKeyPath)) {
            self::markTestSkipped(
                sprintf(
                    'mTLS client private key was not found at "%s". Update your .env before running integration tests.',
                    $resolvedPrivateKeyPath
                )
            );
        }

        $client = PaymentGatewayClient::fromEnv($projectRoot);

        $payload = [
            'transaction_id' => 'txn-integration-001',
            'amount' => '100.00',
            'currency' => 'USD',
        ];

        $response = $client->send($config->getApiUrl(), $payload);
        $statusCode = $response->getStatusCode();

        self::assertGreaterThanOrEqual(200, $statusCode);
        self::assertLessThan(300, $statusCode);
    }

    private function loadConfigOrSkip(string $projectRoot): EnvConfig
    {
        try {
            return EnvConfig::fromProjectRoot($projectRoot);
        } catch (ConfigurationException $exception) {
            self::markTestSkipped(
                sprintf(
                    'Integration test requires a valid .env file with mTLS settings: %s',
                    $exception->getMessage()
                )
            );
        }
    }

    private function resolvePath(string $projectRoot, string $path): string
    {
        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }
}
