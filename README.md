# payment-mtls-client

Small PHP client for sending signed GET requests over mTLS.

## TLS certificate setup

This package supports both mTLS certificate layouts:

- Separate files:
  - `PAYMENT_CLIENT_CERT` points to the certificate PEM
  - `PAYMENT_CLIENT_KEY` points to the private key PEM
- Combined PEM file:
  - `PAYMENT_CLIENT_CERT` points to a PEM that contains both certificate and private key
  - `PAYMENT_CLIENT_KEY` is left empty (or points to the same file as `PAYMENT_CLIENT_CERT`)

`PAYMENT_CLIENT_KEY_PASSPHRASE` is supported for encrypted private key material in either setup.
