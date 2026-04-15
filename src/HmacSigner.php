<?php

declare(strict_types=1);

namespace Mrudchenko\PaymentMtlsClient;

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
        $canonicalPayload = $this->canonicalize($payload);

        return hash_hmac('sha256', $canonicalPayload, $secret);
    }

    /**
     * Returns canonical payload representation used for HMAC signing.
     *
     * Null values are omitted from canonical output.
     *
     * @param array<int|string, scalar|null> $payload
     */
    public function canonicalize(array $payload): string
    {
        return PayloadCanonicalizer::toQueryString($payload);
    }
}
