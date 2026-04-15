<?php

declare(strict_types=1);

namespace Mrudchenko\PaymentMtlsClient;

use InvalidArgumentException;

final class HmacSigner
{
    /**
     * Computes an HMAC SHA-256 signature for a one-dimensional payload.
     *
     * Normalization rules used before signing:
     * - payload keys are sorted in ascending order (ksort) for stable ordering;
     * - nested arrays are rejected (one-dimensional payload only);
     * - canonical representation is built with http_build_query using RFC3986 encoding.
     *
     * @param array<int|string, scalar|null> $payload
     */
    public function sign(array $payload, string $secret): string
    {
        $this->assertOneDimensional($payload);

        $normalized = $payload;
        ksort($normalized);

        $canonicalPayload = http_build_query($normalized, '', '&', PHP_QUERY_RFC3986);

        return hash_hmac('sha256', $canonicalPayload, $secret);
    }

    /**
     * @param array<int|string, mixed> $payload
     */
    private function assertOneDimensional(array $payload): void
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Payload must be one-dimensional. Nested array found at key "%s".',
                        (string) $key
                    )
                );
            }

            if (!is_scalar($value) && $value !== null) {
                throw new InvalidArgumentException(
                    sprintf(
                        "Payload values must be scalar or null. Invalid value found at key '%s'.",
                        (string) $key
                    )
                );
            }
        }
    }
}
