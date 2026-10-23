<?php

namespace Secure_Passkeys\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Secure_Passkeys\Utils\Secure_Passkeys_Helper;

/**
 * Tests for the base64url helpers used to normalise credential identifiers
 * passed between the browser and the server. get_raw_credential_id() must map
 * a base64url rawId to the exact standard-base64 string stored in, and looked
 * up from, the database — a mismatch would silently break every login.
 */
final class HelperEncodingTest extends TestCase
{
    #[DataProvider('base64UrlVectors')]
    public function test_base64url_decode_handles_alphabet_and_padding(string $input, string $expected): void
    {
        $this->assertSame($expected, Secure_Passkeys_Helper::base64url_decode($input));
    }

    public static function base64UrlVectors(): array
    {
        return [
            'one pad char'  => ['aGVsbG8', 'hello'],   // 7 chars -> 1 '=' added
            'no pad needed' => ['aGVsbG9v', 'helloo'], // 8 chars -> already a multiple of 4
        ];
    }

    public function test_base64url_decode_uses_url_safe_alphabet(): void
    {
        // Bytes 0xFB 0xFF 0xBF encode to "+/+/" in standard base64 and to
        // "-_-_" in the URL-safe alphabet; the decoder must accept the latter.
        $expected = base64_decode('+/+/');
        $this->assertSame($expected, Secure_Passkeys_Helper::base64url_decode('-_-_'));
    }

    public function test_get_raw_credential_id_normalises_to_standard_base64(): void
    {
        $bytes = random_bytes(32);
        $rawId = rtrim(strtr(base64_encode($bytes), '+/', '-_'), '='); // what the browser sends

        $this->assertSame(
            base64_encode($bytes),
            Secure_Passkeys_Helper::get_raw_credential_id($rawId)
        );
    }

    public function test_get_raw_credential_id_is_stable_across_encodings(): void
    {
        // The same credential expressed with and without '=' padding, and with
        // URL-safe characters, must resolve to one canonical stored value.
        $bytes = "\xfb\xff\xbf\x00\x10";
        $canonical = base64_encode($bytes);

        $urlSafeNoPad = rtrim(strtr($canonical, '+/', '-_'), '=');

        $this->assertSame($canonical, Secure_Passkeys_Helper::get_raw_credential_id($urlSafeNoPad));
    }
}
