<?php

namespace Secure_Passkeys\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Secure_Passkeys\Packages\Web_Authn\Binary\Byte_Buffer;
use Secure_Passkeys\Packages\Web_Authn\CBOR\Cbor_Decoder;
use Secure_Passkeys\Packages\Web_Authn\Web_Authn_Exception;

/**
 * Tests for the CBOR decoder. Attestation objects arrive as attacker-supplied
 * CBOR on the (authenticated) registration endpoint and, via extension data,
 * on the unauthenticated login endpoint, so robust decoding matters.
 *
 * Vectors are taken from RFC 8949, Appendix A.
 */
final class CborDecoderTest extends TestCase
{
    private function decodeHex(string $hex)
    {
        return Cbor_Decoder::decode(Byte_Buffer::fromHex($hex));
    }

    #[DataProvider('unsignedIntVectors')]
    public function test_decodes_unsigned_integers(string $hex, int $expected): void
    {
        $this->assertSame($expected, $this->decodeHex($hex));
    }

    public static function unsignedIntVectors(): array
    {
        return [
            '0'        => ['00', 0],
            '1'        => ['01', 1],
            '23'       => ['17', 23],
            '24'       => ['1818', 24],
            '255'      => ['18ff', 255],
            '256'      => ['190100', 256],
            '1000000'  => ['1a000f4240', 1000000],
        ];
    }

    #[DataProvider('negativeIntVectors')]
    public function test_decodes_negative_integers(string $hex, int $expected): void
    {
        $this->assertSame($expected, $this->decodeHex($hex));
    }

    public static function negativeIntVectors(): array
    {
        return [
            '-1'   => ['20', -1],
            '-10'  => ['29', -10],
            '-100' => ['3863', -100],
        ];
    }

    public function test_decodes_text_string(): void
    {
        // "hello"
        $this->assertSame('hello', $this->decodeHex('6568656c6c6f'));
    }

    public function test_decodes_byte_string_to_byte_buffer(): void
    {
        $result = $this->decodeHex('43010203'); // bytes 01 02 03
        $this->assertInstanceOf(Byte_Buffer::class, $result);
        $this->assertSame('010203', $result->getHex());
    }

    public function test_decodes_array(): void
    {
        $this->assertSame([1, 2, 3], $this->decodeHex('83010203'));
    }

    public function test_decodes_map_with_integer_keys(): void
    {
        // {1: 2, 3: 4}
        $this->assertSame([1 => 2, 3 => 4], $this->decodeHex('a201020304'));
    }

    public function test_decodes_simple_values(): void
    {
        $this->assertFalse($this->decodeHex('f4'));
        $this->assertTrue($this->decodeHex('f5'));
        $this->assertNull($this->decodeHex('f6'));
    }

    public function test_rejects_trailing_bytes(): void
    {
        $this->expectException(Web_Authn_Exception::class);
        $this->expectExceptionCode(Web_Authn_Exception::CBOR);
        $this->decodeHex('0001'); // one extra byte after the first item
    }

    public function test_rejects_indefinite_length(): void
    {
        $this->expectException(Web_Authn_Exception::class);
        $this->expectExceptionCode(Web_Authn_Exception::CBOR);
        $this->decodeHex('5f'); // indefinite-length byte string marker
    }

    public function test_rejects_truncated_input(): void
    {
        // Byte string claims 3 bytes but none follow.
        $this->expectException(Web_Authn_Exception::class);
        $this->decodeHex('43');
    }
}
