<?php

declare(strict_types=1);

namespace Mrudchenko\PaymentMtlsClient\Tests\Unit;

use InvalidArgumentException;
use Mrudchenko\PaymentMtlsClient\HmacSigner;
use PHPUnit\Framework\TestCase;
use stdClass;

final class HmacSignerTest extends TestCase
{
    public function testSignReturnsExpectedHmacForFixedPayloadAndSecret(): void
    {
        $payload = [
            'amount' => '100.50',
            'currency' => 'USD',
            'order_id' => '123',
        ];
        $secret = 'test-secret';
        // Canonical string: amount=100.50&currency=USD&order_id=123

        $expectedSignature = '643a19d5fc49bb3c1391137ebcbf062f59c0a6c0fd1b2e3946b84544e50feab3';

        $signer = new HmacSigner();
        $actualSignature = $signer->sign($payload, $secret);

        self::assertSame($expectedSignature, $actualSignature);
    }

    public function testSignIsDeterministicRegardlessOfPayloadKeyOrder(): void
    {
        $payloadA = [
            'amount' => '100.50',
            'currency' => 'USD',
            'order_id' => '123',
        ];
        $payloadB = [
            'order_id' => '123',
            'amount' => '100.50',
            'currency' => 'USD',
        ];
        $secret = 'test-secret';

        $signer = new HmacSigner();
        $signatureA = $signer->sign($payloadA, $secret);
        $signatureB = $signer->sign($payloadB, $secret);

        self::assertSame($signatureA, $signatureB);
    }

    public function testSignThrowsExceptionForNonScalarPayloadValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $secret = 'test-secret';

        $signer = new HmacSigner();
        $signer->sign(['foo' => new stdClass()], $secret);
    }
}
