<?php

declare(strict_types=1);

namespace Mrudchenko\PaymentMtlsClient;

use InvalidArgumentException;

final class PayloadCanonicalizer
{
    /**
     * Builds a canonical RFC3986 query string for a one-dimensional payload.
     *
     * Rules:
     * - keys are sorted with ksort for deterministic ordering;
     * - nested arrays are rejected;
     * - only scalar|null values are accepted;
     * - null values are omitted from the canonical output.
     *
     * @param array<int|string, scalar|null> $payload
     */
    public static function toQueryString(array $payload): string
    {
        $normalized = self::normalize($payload);

        return http_build_query($normalized, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @param array<int|string, scalar|null> $payload
     * @return array<int|string, scalar>
     */
    private static function normalize(array $payload): array
    {
        self::assertOneDimensional($payload);

        $normalized = $payload;
        ksort($normalized);

        $filtered = [];
        foreach ($normalized as $key => $value) {
            if ($value !== null) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * @param array<int|string, mixed> $payload
     */
    private static function assertOneDimensional(array $payload): void
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
