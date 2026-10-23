<?php

namespace Secure_Passkeys\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Secure_Passkeys\Packages\Web_Authn\Binary\Byte_Buffer;
use Secure_Passkeys\Packages\Web_Authn\Web_Authn;
use Secure_Passkeys\Packages\Web_Authn\Web_Authn_Exception;

/**
 * Verification of the attestation (registration) path with the "none"
 * attestation format, driven by a deterministic ES256 fixture.
 */
final class WebAuthnAttestationTest extends TestCase
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

    private function newRp(): Web_Authn
    {
        return new Web_Authn(self::$f['rp_id'], self::$f['rp_id']);
    }

    public function test_valid_attestation_is_accepted(): void
    {
        $result = $this->newRp()->processCreate(
            $this->b('create_client_data_json'),
            new Byte_Buffer($this->b('create_attestation_object')),
            $this->b('create_challenge'),
            true,
            true
        );

        $this->assertSame('none', $result->attestationFormat);
        $this->assertSame(self::$f['create_credential_id'], base64_encode($result->credentialId));
        $this->assertNotFalse(
            openssl_pkey_get_public($result->credentialPublicKey),
            'Extracted credential public key should be a valid PEM.'
        );
        $this->assertTrue($result->userPresent);
        $this->assertTrue($result->userVerified);
    }

    public function test_wrong_challenge_is_rejected(): void
    {
        $this->expectException(Web_Authn_Exception::class);
        $this->expectExceptionCode(Web_Authn_Exception::INVALID_CHALLENGE);

        $this->newRp()->processCreate(
            $this->b('create_client_data_json'),
            new Byte_Buffer($this->b('create_attestation_object')),
            random_bytes(32),
            true,
            true
        );
    }

    public function test_client_data_type_must_be_webauthn_create(): void
    {
        // Feed an assertion's client data (type webauthn.get) to processCreate.
        $this->expectException(Web_Authn_Exception::class);
        $this->expectExceptionCode(Web_Authn_Exception::INVALID_TYPE);

        $this->newRp()->processCreate(
            $this->b('get_client_data_json'),
            new Byte_Buffer($this->b('create_attestation_object')),
            $this->b('create_challenge'),
            true,
            true
        );
    }
}
