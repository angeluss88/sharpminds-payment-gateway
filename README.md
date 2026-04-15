# payment-mtls-client

## Overview

`payment-mtls-client` is a small Composer package for sending signed GET requests over mTLS.  
It signs payloads with HMAC SHA-256 and sends them as query parameters in a GET request using a client certificate/private key while validating the server certificate.

## Requirements

- PHP `^8.2`
- [Composer](https://getcomposer.org/)

## Installation

```bash
composer install
```

## Configuration (.env)

Create a `.env` file in the project root and define:

- `PAYMENT_API_URL` - target API endpoint URL
- `PAYMENT_HMAC_SECRET` - shared secret used for HMAC signature
- `PAYMENT_CLIENT_CERT` - path to client certificate PEM
- `PAYMENT_CLIENT_KEY` - path to private key PEM (optional in combined-PEM mode)
- `PAYMENT_CLIENT_KEY_PASSPHRASE` - passphrase for encrypted key material (optional)
- `PAYMENT_VERIFY_TLS` - server certificate verification flag (`true`/`false`)
- `PAYMENT_TIMEOUT` - request timeout in seconds (optional)

Supported TLS setups:

### A) Separate certificate and key

```dotenv
PAYMENT_CLIENT_CERT=cert.pem
PAYMENT_CLIENT_KEY=key.pem
```

### B) Combined PEM file (certificate + private key)

```dotenv
PAYMENT_CLIENT_CERT=client.pem
PAYMENT_CLIENT_KEY=
PAYMENT_CLIENT_KEY_PASSPHRASE=badssl.com
```

Behavior:

- if `PAYMENT_CLIENT_KEY` is empty or points to the same file as `PAYMENT_CLIENT_CERT`, the client treats `PAYMENT_CLIENT_CERT` as a combined PEM
- otherwise, separate certificate + private key files are used

## Example usage

```php
<?php

declare(strict_types=1);

use Mrudchenko\PaymentMtlsClient\PaymentGatewayClient;

$projectRoot = __DIR__;
$client = PaymentGatewayClient::fromEnv($projectRoot);
$url = 'https://api.example.com/payments';

// Payload values are sent as GET query parameters.
$response = $client->send($url, [
    'transaction_id' => '12345',
    'amount' => '99.99',
    'currency' => 'USD',
]);

echo $response->getStatusCode();
```

## Running tests

Run all tests:

```bash
composer test
```

Run unit tests only:

```bash
./vendor/bin/phpunit tests/Unit
```

Run integration tests only:

```bash
./vendor/bin/phpunit tests/Integration
```

## mTLS testing with BadSSL

You can validate mTLS behavior against BadSSL (`https://client.badssl.com/`).

- passphrase for the provided test client certificate is `badssl.com`
- reviewer flow:
  - download BadSSL test certificate/key files
  - place files locally in the repository (for example under `certs/`)
  - configure `.env` paths and credentials

Example curl verification:

```bash
curl -v --cert certs/badssl-client.pem:badssl.com \
  "https://client.badssl.com/?transaction_id=12345&amount=99.99&currency=USD"
```
