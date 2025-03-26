<?php

namespace Secure_Passkeys\Packages\Web_Authn\Binary;

use Secure_Passkeys\Packages\Web_Authn\Web_Authn_Exception;

/**
 * Modified version of https://github.com/madwizard-thomas/Web_Authn-server/blob/master/src/Format/Byte_Buffer.php
 * Copyright © 2018 Thomas Bleeker - MIT licensed
 * Modified by Lukas Buchs
 * Thanks Thomas for your work!
 */
class Byte_Buffer implements \JsonSerializable, \Serializable
{
    /**
     * @var bool
     */
    public static $useBase64UrlEncoding = false;

    /**
     * @var string
     */
    private $_data;

    /**
     * @var int
     */
    private $_length;

    public function __construct($binaryData)
    {
        $this->_data = (string)$binaryData;
        $this->_length = \strlen($binaryData);
    }


    // -----------------------
    // PUBLIC STATIC
    // -----------------------

    /**
     * create a Byte_Buffer from a base64 url encoded string
     * @param string $base64url
     * @return Byte_Buffer
     */
    public static function fromBase64Url($base64url): Byte_Buffer
    {
        $bin = self::_base64url_decode($base64url);
        if ($bin === false) {
            throw new Web_Authn_Exception('Byte_Buffer: Invalid base64 url string', Web_Authn_Exception::Byte_Buffer);
        }
        return new Byte_Buffer($bin);
    }

    /**
     * create a Byte_Buffer from a base64 url encoded string
     * @param string $hex
     * @return Byte_Buffer
     */
    public static function fromHex($hex): Byte_Buffer
    {
        $bin = \hex2bin($hex);
        if ($bin === false) {
            throw new Web_Authn_Exception('Byte_Buffer: Invalid hex string', Web_Authn_Exception::Byte_Buffer);
        }
        return new Byte_Buffer($bin);
    }

    /**
     * create a random Byte_Buffer
     * @param string $length
     * @return Byte_Buffer
     */
    public static function randomBuffer($length): Byte_Buffer
    {
        if (\function_exists('random_bytes')) { // >PHP 7.0
            return new Byte_Buffer(\random_bytes($length));

        } elseif (\function_exists('openssl_random_pseudo_bytes')) {
            return new Byte_Buffer(\openssl_random_pseudo_bytes($length));

        } else {
            throw new Web_Authn_Exception('Byte_Buffer: cannot generate random bytes', Web_Authn_Exception::Byte_Buffer);
        }
    }

    // -----------------------
    // PUBLIC
    // -----------------------

    public function getBytes($offset, $length): string
    {
        if ($offset < 0 || $length < 0 || ($offset + $length > $this->_length)) {
            throw new Web_Authn_Exception('Byte_Buffer: Invalid offset or length', Web_Authn_Exception::Byte_Buffer);
        }
        return \substr($this->_data, $offset, $length);
    }

    public function getByteVal($offset): int
    {
        if ($offset < 0 || $offset >= $this->_length) {
            throw new Web_Authn_Exception('Byte_Buffer: Invalid offset', Web_Authn_Exception::Byte_Buffer);
        }
        return \ord(\substr($this->_data, $offset, 1));
    }

    public function getJson($jsonFlags = 0)
    {
        $data = \json_decode($this->getBinaryString(), null, 512, $jsonFlags);
        if (\json_last_error() !== JSON_ERROR_NONE) {
            throw new Web_Authn_Exception(\json_last_error_msg(), Web_Authn_Exception::Byte_Buffer);
        }
        return $data;
    }

    public function getLength(): int
    {
        return $this->_length;
    }

    public function getUint16Val($offset)
    {
        if ($offset < 0 || ($offset + 2) > $this->_length) {
            throw new Web_Authn_Exception('Byte_Buffer: Invalid offset', Web_Authn_Exception::Byte_Buffer);
        }
        return unpack('n', $this->_data, $offset)[1];
    }

    public function getUint32Val($offset)
    {
        if ($offset < 0 || ($offset + 4) > $this->_length) {
            throw new Web_Authn_Exception('Byte_Buffer: Invalid offset', Web_Authn_Exception::Byte_Buffer);
        }
        $val = unpack('N', $this->_data, $offset)[1];

        // Signed integer overflow causes signed negative numbers
        if ($val < 0) {
            throw new Web_Authn_Exception('Byte_Buffer: Value out of integer range.', Web_Authn_Exception::Byte_Buffer);
        }
        return $val;
    }

    public function getUint64Val($offset)
    {
        if (PHP_INT_SIZE < 8) {
            throw new Web_Authn_Exception('Byte_Buffer: 64-bit values not supported by this system', Web_Authn_Exception::Byte_Buffer);
        }
        if ($offset < 0 || ($offset + 8) > $this->_length) {
            throw new Web_Authn_Exception('Byte_Buffer: Invalid offset', Web_Authn_Exception::Byte_Buffer);
        }
        $val = unpack('J', $this->_data, $offset)[1];

        // Signed integer overflow causes signed negative numbers
        if ($val < 0) {
            throw new Web_Authn_Exception('Byte_Buffer: Value out of integer range.', Web_Authn_Exception::Byte_Buffer);
        }

        return $val;
    }

    public function getHalfFloatVal($offset)
    {
        //FROM spec pseudo decode_half(unsigned char *halfp)
        $half = $this->getUint16Val($offset);

        $exp = ($half >> 10) & 0x1f;
        $mant = $half & 0x3ff;

        if ($exp === 0) {
            $val = $mant * (2 ** -24);
        } elseif ($exp !== 31) {
            $val = ($mant + 1024) * (2 ** ($exp - 25));
        } else {
            $val = ($mant === 0) ? INF : NAN;
        }

        return ($half & 0x8000) ? -$val : $val;
    }

    public function getFloatVal($offset)
    {
        if ($offset < 0 || ($offset + 4) > $this->_length) {
            throw new Web_Authn_Exception('Byte_Buffer: Invalid offset', Web_Authn_Exception::Byte_Buffer);
        }
        return unpack('G', $this->_data, $offset)[1];
    }

    public function getDoubleVal($offset)
    {
        if ($offset < 0 || ($offset + 8) > $this->_length) {
            throw new Web_Authn_Exception('Byte_Buffer: Invalid offset', Web_Authn_Exception::Byte_Buffer);
        }
        return unpack('E', $this->_data, $offset)[1];
    }

    /**
     * @return string
     */
    public function getBinaryString(): string
    {
        return $this->_data;
    }

    /**
     * @param string|Byte_Buffer $buffer
     * @return bool
     */
    public function equals($buffer): bool
    {
        if (is_object($buffer) && $buffer instanceof Byte_Buffer) {
            return $buffer->getBinaryString() === $this->getBinaryString();

        } elseif (is_string($buffer)) {
            return $buffer === $this->getBinaryString();
        }

        return false;
    }

    /**
     * @return string
     */
    public function getHex(): string
    {
        return \bin2hex($this->_data);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->_length === 0;
    }


    /**
     * jsonSerialize interface
     * return binary data in RFC 1342-Like serialized string
     * @return string
     */
    public function jsonSerialize(): string
    {
        if (Byte_Buffer::$useBase64UrlEncoding) {
            return self::_base64url_encode($this->_data);

        } else {
            return '=?BINARY?B?' . \base64_encode($this->_data) . '?=';
        }
    }

    /**
     * Serializable-Interface
     * @return string
     */
    public function serialize(): string
    {
        return \serialize($this->_data);
    }

    /**
     * Serializable-Interface
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $this->_data = \unserialize($serialized);
        $this->_length = \strlen($this->_data);
    }

    /**
     * (PHP 8 deprecates Serializable-Interface)
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'data' => \serialize($this->_data)
        ];
    }

    /**
     * object to string
     * @return string
     */
    public function __toString(): string
    {
        return $this->getHex();
    }

    /**
     * (PHP 8 deprecates Serializable-Interface)
     * @param array $data
     * @return void
     */
    public function __unserialize($data)
    {
        if ($data && isset($data['data'])) {
            $this->_data = \unserialize($data['data']);
            $this->_length = \strlen($this->_data);
        }
    }

    // -----------------------
    // PROTECTED STATIC
    // -----------------------

    /**
     * base64 url decoding
     * @param string $data
     * @return string
     */
    protected static function _base64url_decode($data): string
    {
        return \base64_decode(\strtr($data, '-_', '+/') . \str_repeat('=', 3 - (3 + \strlen($data)) % 4));
    }

    /**
     * base64 url encoding
     * @param string $data
     * @return string
     */
    protected static function _base64url_encode($data): string
    {
        return \rtrim(\strtr(\base64_encode($data), '+/', '-_'), '=');
    }
}
