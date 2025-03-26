<?php

namespace Secure_Passkeys\Packages\Web_Authn;

class Web_Authn_Exception extends \Exception
{
    public const INVALID_DATA = 1;
    public const INVALID_TYPE = 2;
    public const INVALID_CHALLENGE = 3;
    public const INVALID_ORIGIN = 4;
    public const INVALID_RELYING_PARTY = 5;
    public const INVALID_SIGNATURE = 6;
    public const INVALID_PUBLIC_KEY = 7;
    public const CERTIFICATE_NOT_TRUSTED = 8;
    public const USER_PRESENT = 9;
    public const USER_VERIFICATED = 10;
    public const SIGNATURE_COUNTER = 11;
    public const CRYPTO_STRONG = 13;
    public const Byte_Buffer = 14;
    public const CBOR = 15;
    public const ANDROID_NOT_TRUSTED = 16;

    public function __construct($message = "", $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
