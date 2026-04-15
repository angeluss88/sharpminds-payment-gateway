<?php

declare(strict_types=1);

namespace Mrudchenko\PaymentMtlsClient;

use Dotenv\Dotenv;
use Mrudchenko\PaymentMtlsClient\Exception\ConfigurationException;

final class EnvConfig
{
    private string $apiUrl;
    private string $hmacSecret;
    private string $clientCertificatePath;
    private string $clientPrivateKeyPath;
    private ?string $clientPrivateKeyPassphrase;
    private bool $verifyTls;
    private float $timeout;

    /**
     * @param array<string, string> $values
     */
    public function __construct(array $values)
    {
        $this->apiUrl = $this->requireUrl($values, 'PAYMENT_API_URL');
        $this->hmacSecret = $this->requireNonEmptyString($values, 'PAYMENT_HMAC_SECRET');
        $this->clientCertificatePath = $this->requireNonEmptyString($values, 'PAYMENT_CLIENT_CERT');
        $this->clientPrivateKeyPath = $this->optionalString($values, 'PAYMENT_CLIENT_KEY') ?? '';
        $this->clientPrivateKeyPassphrase = $this->optionalString($values, 'PAYMENT_CLIENT_KEY_PASSPHRASE');
        $this->verifyTls = $this->requireBool($values, 'PAYMENT_VERIFY_TLS');
        $this->timeout = $this->optionalPositiveFloat($values, 'PAYMENT_TIMEOUT', 10.0);
    }

    public static function fromProjectRoot(string $projectRoot, string $fileName = '.env'): self
    {
        if ($projectRoot === '') {
            throw new ConfigurationException('Project root path must not be empty.');
        }

        $environmentFilePath = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
        if (!is_file($environmentFilePath)) {
            throw new ConfigurationException(
                sprintf(
                    "Environment file '%s' was not found in '%s'. Please create it (for example, by copying .env.example).",
                    $fileName,
                    $projectRoot
                )
            );
        }

        try {
            $dotenv = Dotenv::createArrayBacked($projectRoot, $fileName);
            /** @var array<string, string> $loaded */
            $loaded = $dotenv->safeLoad();
        } catch (\Throwable $exception) {
            throw new ConfigurationException(
                sprintf('Failed to load environment file "%s" from "%s".', $fileName, $projectRoot),
                0,
                $exception
            );
        }

        return new self($loaded);
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function getHmacSecret(): string
    {
        return $this->hmacSecret;
    }

    public function getClientCertificatePath(): string
    {
        return $this->clientCertificatePath;
    }

    public function getClientPrivateKeyPath(): string
    {
        return $this->clientPrivateKeyPath;
    }

    public function getClientPrivateKeyPassphrase(): ?string
    {
        return $this->clientPrivateKeyPassphrase;
    }

    public function shouldVerifyTls(): bool
    {
        return $this->verifyTls;
    }

    public function getTimeout(): float
    {
        return $this->timeout;
    }

    /**
     * @param array<string, string> $values
     */
    private function requireNonEmptyString(array $values, string $key): string
    {
        $value = $values[$key] ?? null;
        if ($value === null || trim($value) === '') {
            throw new ConfigurationException(sprintf('Missing required configuration value: %s', $key));
        }

        return trim($value);
    }

    /**
     * @param array<string, string> $values
     */
    private function optionalString(array $values, string $key): ?string
    {
        $value = $values[$key] ?? null;
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        return $trimmed;
    }

    /**
     * @param array<string, string> $values
     */
    private function requireUrl(array $values, string $key): string
    {
        $url = $this->requireNonEmptyString($values, $key);
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new ConfigurationException(sprintf('Invalid URL in configuration: %s', $key));
        }

        return $url;
    }

    /**
     * @param array<string, string> $values
     */
    private function requireBool(array $values, string $key): bool
    {
        $rawValue = $this->requireNonEmptyString($values, $key);
        $parsed = filter_var($rawValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($parsed === null) {
            throw new ConfigurationException(
                sprintf('Invalid boolean in configuration: %s (expected true/false)', $key)
            );
        }

        return $parsed;
    }

    /**
     * @param array<string, string> $values
     */
    private function optionalPositiveFloat(array $values, string $key, float $default): float
    {
        $rawValue = $values[$key] ?? null;
        if ($rawValue === null || trim($rawValue) === '') {
            return $default;
        }

        if (!is_numeric($rawValue)) {
            throw new ConfigurationException(sprintf('Invalid numeric value in configuration: %s', $key));
        }

        $parsed = (float) $rawValue;
        if ($parsed <= 0.0) {
            throw new ConfigurationException(sprintf('Configuration value must be > 0: %s', $key));
        }

        return $parsed;
    }
}
