<?php

namespace Secure_Passkeys\Packages\Web_Authn\CBOR;

use Secure_Passkeys\Packages\Web_Authn\Binary\Byte_Buffer;
use Secure_Passkeys\Packages\Web_Authn\Web_Authn_Exception;

/**
 * Modified version of https://github.com/madwizard-thomas/Web_Authn-server/blob/master/src/Format/Cbor_Decoder.php
 * Copyright © 2018 Thomas Bleeker - MIT licensed
 * Modified by Lukas Buchs
 * Thanks Thomas for your work!
 */
class Cbor_Decoder
{
    public const CBOR_MAJOR_UNSIGNED_INT = 0;
    public const CBOR_MAJOR_TEXT_STRING = 3;
    public const CBOR_MAJOR_FLOAT_SIMPLE = 7;
    public const CBOR_MAJOR_NEGATIVE_INT = 1;
    public const CBOR_MAJOR_ARRAY = 4;
    public const CBOR_MAJOR_TAG = 6;
    public const CBOR_MAJOR_MAP = 5;
    public const CBOR_MAJOR_BYTE_STRING = 2;

    /**
     * @param Byte_Buffer|string $bufOrBin
     * @return mixed
     * @throws Web_Authn_Exception
     */
    public static function decode($bufOrBin)
    {
        $buf = $bufOrBin instanceof Byte_Buffer ? $bufOrBin : new Byte_Buffer($bufOrBin);

        $offset = 0;
        $result = self::_parseItem($buf, $offset);
        if ($offset !== $buf->getLength()) {
            throw new Web_Authn_Exception('Unused bytes after data item.', Web_Authn_Exception::CBOR);
        }
        return $result;
    }

    /**
     * @param Byte_Buffer|string $bufOrBin
     * @param int $startOffset
     * @param int|null $endOffset
     * @return mixed
     */
    public static function decodeInPlace($bufOrBin, $startOffset, &$endOffset = null)
    {
        $buf = $bufOrBin instanceof Byte_Buffer ? $bufOrBin : new Byte_Buffer($bufOrBin);

        $offset = $startOffset;
        $data = self::_parseItem($buf, $offset);
        $endOffset = $offset;
        return $data;
    }

    // ---------------------
    // protected
    // ---------------------

    /**
     * @param Byte_Buffer $buf
     * @param int $offset
     * @return mixed
     */
    protected static function _parseItem(Byte_Buffer $buf, &$offset)
    {
        $first = $buf->getByteVal($offset++);
        $type = $first >> 5;
        $val = $first & 0b11111;

        if ($type === self::CBOR_MAJOR_FLOAT_SIMPLE) {
            return self::_parseFloatSimple($val, $buf, $offset);
        }

        $val = self::_parseExtraLength($val, $buf, $offset);

        return self::_parseItemData($type, $val, $buf, $offset);
    }

    protected static function _parseFloatSimple($val, Byte_Buffer $buf, &$offset)
    {
        switch ($val) {
            case 24:
                $val = $buf->getByteVal($offset);
                $offset++;
                return self::_parseSimple($val);

            case 25:
                $floatValue = $buf->getHalfFloatVal($offset);
                $offset += 2;
                return $floatValue;

            case 26:
                $floatValue = $buf->getFloatVal($offset);
                $offset += 4;
                return $floatValue;

            case 27:
                $floatValue = $buf->getDoubleVal($offset);
                $offset += 8;
                return $floatValue;

            case 28:
            case 29:
            case 30:
                throw new Web_Authn_Exception('Reserved value used.', Web_Authn_Exception::CBOR);

            case 31:
                throw new Web_Authn_Exception('Indefinite length is not supported.', Web_Authn_Exception::CBOR);
        }

        return self::_parseSimple($val);
    }

    /**
     * @param int $val
     * @return mixed
     * @throws Web_Authn_Exception
     */
    protected static function _parseSimple($val)
    {
        if ($val === 20) {
            return false;
        }
        if ($val === 21) {
            return true;
        }
        if ($val === 22) {
            return null;
        }
        throw new Web_Authn_Exception(sprintf('Unsupported simple value %d.', $val), Web_Authn_Exception::CBOR);
    }

    protected static function _parseExtraLength($val, Byte_Buffer $buf, &$offset)
    {
        switch ($val) {
            case 24:
                $val = $buf->getByteVal($offset);
                $offset++;
                break;

            case 25:
                $val = $buf->getUint16Val($offset);
                $offset += 2;
                break;

            case 26:
                $val = $buf->getUint32Val($offset);
                $offset += 4;
                break;

            case 27:
                $val = $buf->getUint64Val($offset);
                $offset += 8;
                break;

            case 28:
            case 29:
            case 30:
                throw new Web_Authn_Exception('Reserved value used.', Web_Authn_Exception::CBOR);

            case 31:
                throw new Web_Authn_Exception('Indefinite length is not supported.', Web_Authn_Exception::CBOR);
        }

        return $val;
    }

    protected static function _parseItemData($type, $val, Byte_Buffer $buf, &$offset)
    {
        switch ($type) {
            case self::CBOR_MAJOR_UNSIGNED_INT: // uint
                return $val;

            case self::CBOR_MAJOR_NEGATIVE_INT:
                return -1 - $val;

            case self::CBOR_MAJOR_BYTE_STRING:
                $data = $buf->getBytes($offset, $val);
                $offset += $val;
                return new Byte_Buffer($data); // bytes

            case self::CBOR_MAJOR_TEXT_STRING:
                $data = $buf->getBytes($offset, $val);
                $offset += $val;
                return $data; // UTF-8

            case self::CBOR_MAJOR_ARRAY:
                return self::_parseArray($buf, $offset, $val);

            case self::CBOR_MAJOR_MAP:
                return self::_parseMap($buf, $offset, $val);

            case self::CBOR_MAJOR_TAG:
                return self::_parseItem($buf, $offset); // 1 embedded data item
        }

        // This should never be reached
        throw new Web_Authn_Exception(sprintf('Unknown major type %d.', $type), Web_Authn_Exception::CBOR);
    }

    protected static function _parseMap(Byte_Buffer $buf, &$offset, $count)
    {
        $map = array();

        for ($i = 0; $i < $count; $i++) {
            $mapKey = self::_parseItem($buf, $offset);
            $mapVal = self::_parseItem($buf, $offset);

            if (!\is_int($mapKey) && !\is_string($mapKey)) {
                throw new Web_Authn_Exception('Can only use strings or integers as map keys', Web_Authn_Exception::CBOR);
            }

            $map[$mapKey] = $mapVal; // todo dup
        }
        return $map;
    }

    protected static function _parseArray(Byte_Buffer $buf, &$offset, $count)
    {
        $arr = array();
        for ($i = 0; $i < $count; $i++) {
            $arr[] = self::_parseItem($buf, $offset);
        }

        return $arr;
    }
}
