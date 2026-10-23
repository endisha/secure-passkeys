<?php

namespace Secure_Passkeys\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Secure_Passkeys\Packages\Web_Authn\Binary\Byte_Buffer;
use Secure_Passkeys\Packages\Web_Authn\Web_Authn_Exception;

/**
 * Tests for the binary Byte_Buffer helper, which backs all CBOR and
 * authenticator-data parsing. Its bounds checking is what stops malformed,
 * attacker-supplied input from reading past the end of the buffer.
 */
final class ByteBufferTest extends TestCase
{
    public function test_from_hex_round_trips(): void
    {
        $buf = Byte_Buffer::fromHex('deadbeef');
        $this->assertSame('deadbeef', $buf->getHex());
        $this->assertSame(4, $buf->getLength());
    }

    public function test_from_base64url_decodes_without_padding(): void
    {
        $buf = Byte_Buffer::fromBase64Url('aGVsbG8'); // "hello", no '=' padding
        $this->assertSame('hello', $buf->getBinaryString());
    }

    public function test_get_bytes_returns_slice(): void
    {
        $buf = Byte_Buffer::fromHex('00112233');
        $this->assertSame('1122', bin2hex($buf->getBytes(1, 2)));
    }

    public function test_get_byte_val_reads_single_byte(): void
    {
        $buf = Byte_Buffer::fromHex('ff01');
        $this->assertSame(255, $buf->getByteVal(0));
        $this->assertSame(1, $buf->getByteVal(1));
    }

    public function test_get_uint16_and_uint32(): void
    {
        $buf = Byte_Buffer::fromHex('0100000100');
        $this->assertSame(256, $buf->getUint16Val(0));
        $this->assertSame(256, $buf->getUint32Val(1));
    }

    public function test_get_bytes_past_end_throws(): void
    {
        $this->expectException(Web_Authn_Exception::class);
        $this->expectExceptionCode(Web_Authn_Exception::Byte_Buffer);
        Byte_Buffer::fromHex('0011')->getBytes(1, 5);
    }

    public function test_get_byte_val_out_of_range_throws(): void
    {
        $this->expectException(Web_Authn_Exception::class);
        $this->expectExceptionCode(Web_Authn_Exception::Byte_Buffer);
        Byte_Buffer::fromHex('00')->getByteVal(5);
    }

    public function test_negative_offset_throws(): void
    {
        $this->expectException(Web_Authn_Exception::class);
        Byte_Buffer::fromHex('0011')->getBytes(-1, 1);
    }

    public function test_equals_and_is_empty(): void
    {
        $this->assertTrue(Byte_Buffer::fromHex('abcd')->equals(Byte_Buffer::fromHex('abcd')));
        $this->assertFalse(Byte_Buffer::fromHex('abcd')->equals(Byte_Buffer::fromHex('abce')));
        $this->assertTrue((new Byte_Buffer(''))->isEmpty());
        $this->assertFalse(Byte_Buffer::fromHex('00')->isEmpty());
    }
}
