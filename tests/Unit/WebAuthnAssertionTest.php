<?php

namespace Secure_Passkeys\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Secure_Passkeys\Packages\Web_Authn\Web_Authn;
use Secure_Passkeys\Packages\Web_Authn\Web_Authn_Exception;

/**
 * End-to-end verification of the assertion (login) path through the vendored
 * Web_Authn library, driven by a deterministic ES256 fixture.
 *
 * These tests are the regression anchor for the login flow: they exercise
 * challenge matching, origin and RP-ID-hash checks, user-presence / user-
 * verification flags, ECDSA signature verification and the clone-detection
 * signature counter — the exact guarantees login relies on.
 */
final class WebAuthnAssertionTest extends TestCase
{
    /** @var array<string,mixed> */
    private static array $f;

    public static function setUpBeforeClass(): void
    {
        self::$f = require dirname(__DIR__) . '/fixtures/webauthn-vectors.php';
    }

    private function b(string $key): string
    {
        return base64_decode(self::$f[$key]);
    }

    private function newRp(?string $rpId = null): Web_Authn
    {
        $rpId = $rpId ?? self::$f['rp_id'];
        return new Web_Authn($rpId, $rpId);
    }

    /**
     * Re-sign an authenticator-data blob with the fixture's private key so we
     * can produce valid-signature variants that differ only in their flags.
     */
    private function signedAuthData(string $authData): string
    {
        $signedData = $authData . hash('sha256', $this->b('get_client_data_json'), true);
        openssl_sign($signedData, $sig, self::$f['private_key_pem'], OPENSSL_ALGO_SHA256);
        return $sig;
    }

    private function authDataWithFlags(int $flags, int $signCount): string
    {
        $rpIdHash = hash('sha256', self::$f['rp_id'], true);
        return $rpIdHash . chr($flags) . pack('N', $signCount);
    }

    public function test_valid_assertion_is_accepted_and_returns_counter(): void
    {
        $rp = $this->newRp();

        $result = $rp->processGet(
            $this->b('get_client_data_json'),
            $this->b('get_authenticator_data'),
            $this->b('get_signature'),
            self::$f['public_key_pem'],
            $this->b('get_challenge'),
            self::$f['get_prev_sign_count'],
            true,
            true
        );

        $this->assertTrue($result);
        $this->assertSame(self::$f['get_sign_count'], $rp->getSignatureCounter());
    }

    public function test_tampered_signature_is_rejected(): void
    {
        $sig = $this->b('get_signature');
        $sig[10] = $sig[10] === "\x00" ? "\x01" : "\x00"; // flip a byte

        $this->expectException(Web_Authn_Exception::class);
        $this->expectExceptionCode(Web_Authn_Exception::INVALID_SIGNATURE);

        $this->newRp()->processGet(
            $this->b('get_client_data_json'),
            $this->b('get_authenticator_data'),
            $sig,
            self::$f['public_key_pem'],
            $this->b('get_challenge'),
            null,
            true,
            true
        );
    }

    public function test_wrong_challenge_is_rejected(): void
    {
        $this->expectException(Web_Authn_Exception::class);
        $this->expectExceptionCode(Web_Authn_Exception::INVALID_CHALLENGE);

        $this->newRp()->processGet(
            $this->b('get_client_data_json'),
            $this->b('get_authenticator_data'),
            $this->b('get_signature'),
            self::$f['public_key_pem'],
            random_bytes(32), // a challenge the client data was not signed over
            null,
            true,
            true
        );
    }

    public function test_foreign_relying_party_is_rejected(): void
    {
        // The client data pins origin https://example.com; a server that
        // believes it is attacker.test must reject both origin and rpIdHash.
        $this->expectException(Web_Authn_Exception::class);

        $this->newRp('attacker.test')->processGet(
            $this->b('get_client_data_json'),
            $this->b('get_authenticator_data'),
            $this->b('get_signature'),
            self::$f['public_key_pem'],
            $this->b('get_challenge'),
            null,
            true,
            true
        );
    }

    public function test_cloned_authenticator_counter_regression_is_rejected(): void
    {
        // Stored counter (12) is ahead of the asserted counter (10): a signal
        // the authenticator may have been cloned.
        $this->expectException(Web_Authn_Exception::class);
        $this->expectExceptionCode(Web_Authn_Exception::SIGNATURE_COUNTER);

        $this->newRp()->processGet(
            $this->b('get_client_data_json'),
            $this->b('get_authenticator_data'),
            $this->b('get_signature'),
            self::$f['public_key_pem'],
            $this->b('get_challenge'),
            12,
            true,
            true
        );
    }

    public function test_user_verification_required_but_absent_is_rejected(): void
    {
        $authData = $this->authDataWithFlags(0x01, self::$f['get_sign_count']); // UP only, no UV
        $sig = $this->signedAuthData($authData);

        $this->expectException(Web_Authn_Exception::class);
        $this->expectExceptionCode(Web_Authn_Exception::USER_VERIFICATED);

        $this->newRp()->processGet(
            $this->b('get_client_data_json'),
            $authData,
            $sig,
            self::$f['public_key_pem'],
            $this->b('get_challenge'),
            null,
            true, // requireUserVerification
            true
        );
    }

    public function test_user_presence_required_but_absent_is_rejected(): void
    {
        $authData = $this->authDataWithFlags(0x04, self::$f['get_sign_count']); // UV only, no UP
        $sig = $this->signedAuthData($authData);

        $this->expectException(Web_Authn_Exception::class);
        $this->expectExceptionCode(Web_Authn_Exception::USER_PRESENT);

        $this->newRp()->processGet(
            $this->b('get_client_data_json'),
            $authData,
            $sig,
            self::$f['public_key_pem'],
            $this->b('get_challenge'),
            null,
            false,
            true // requireUserPresent
        );
    }
}
