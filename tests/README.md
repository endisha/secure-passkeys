# Tests

PHPUnit test suite for Secure Passkeys.

## Running

```bash
composer install
composer test          # or: vendor/bin/phpunit
```

Requires PHP 8.2+ with the `openssl` and `mbstring` extensions (the committed
`composer.lock` pins PHPUnit 11, which needs PHP 8.2). The plugin itself runs on
PHP 7.4+; the test suite simply targets a modern PHPUnit, so it needs a newer
PHP than the plugin's runtime floor. CI lints the shipped code on 7.4–8.4 and
runs this suite on 8.2–8.4.

## What is covered

The current suite is a **unit** suite focused on the security-critical,
self-contained WebAuthn layer under `src/packages/`:

| Test | Covers |
| --- | --- |
| `WebAuthnAssertionTest` | The login path (`processGet`): challenge, origin and RP-ID checks, UP/UV flags, ECDSA signature verification, and clone-detection counter. |
| `WebAuthnAttestationTest` | The registration path (`processCreate`) with the `none` attestation format. |
| `CborDecoderTest` | CBOR decoding of attacker-supplied attestation data (RFC 8949 vectors) and rejection of malformed input. |
| `ByteBufferTest` | Bounds checking in the binary buffer that backs all parsing. |
| `HelperEncodingTest` | base64url / credential-id normalisation used for credential lookup. |

### Fixtures

`tests/fixtures/webauthn-vectors.php` holds a deterministic ES256 (P-256) key
pair and a self-consistent assertion and attestation that the library must
accept. It is committed as the regression anchor and can be regenerated with
`tests/fixtures/gen_fixtures.php` (see that file's header).

## Not yet covered

The database models (challenge binding, rate limiting), AJAX handlers and
WordPress hooks need the WordPress runtime and are best exercised with an
integration suite built on
[`wordpress-tests-lib`](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
against a throwaway test database. That harness is not set up here yet.
