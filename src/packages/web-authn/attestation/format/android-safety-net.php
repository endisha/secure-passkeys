<?php

namespace Secure_Passkeys\Packages\Web_Authn\Attestation\Format;

use Secure_Passkeys\Packages\Web_Authn\Attestation\Authenticator_Data;
use Secure_Passkeys\Packages\Web_Authn\Binary\Byte_Buffer;
use Secure_Passkeys\Packages\Web_Authn\Web_Authn_Exception;

class Android_Safety_Net extends Format_Base
{
    private $_signature;
    private $_signedValue;
    private $_x5c;
    private $_payload;

    public function __construct($AttestionObject, Authenticator_Data $Authenticator_Data)
    {
        parent::__construct($AttestionObject, $Authenticator_Data);

        // check data
        $attStmt = $this->_Attestation_Object['attStmt'];

        if (!\array_key_exists('ver', $attStmt) || !$attStmt['ver']) {
            throw new Web_Authn_Exception('invalid Android Safety Net Format', Web_Authn_Exception::INVALID_DATA);
        }

        if (!\array_key_exists('response', $attStmt) || !($attStmt['response'] instanceof Byte_Buffer)) {
            throw new Web_Authn_Exception('invalid Android Safety Net Format', Web_Authn_Exception::INVALID_DATA);
        }

        $response = $attStmt['response']->getBinaryString();

        // Response is a JWS [RFC7515] object in Compact Serialization.
        // JWSs have three segments separated by two period ('.') characters
        $parts = \explode('.', $response);
        unset($response);
        if (\count($parts) !== 3) {
            throw new Web_Authn_Exception('invalid JWS data', Web_Authn_Exception::INVALID_DATA);
        }

        $header = $this->_base64url_decode($parts[0]);
        $payload = $this->_base64url_decode($parts[1]);
        $this->_signature = $this->_base64url_decode($parts[2]);
        $this->_signedValue = $parts[0] . '.' . $parts[1];
        unset($parts);

        $header = \json_decode($header);
        $payload = \json_decode($payload);

        if (!($header instanceof \stdClass)) {
            throw new Web_Authn_Exception('invalid JWS header', Web_Authn_Exception::INVALID_DATA);
        }
        if (!($payload instanceof \stdClass)) {
            throw new Web_Authn_Exception('invalid JWS payload', Web_Authn_Exception::INVALID_DATA);
        }

        if (!isset($header->x5c) || !is_array($header->x5c) || count($header->x5c) === 0) {
            throw new Web_Authn_Exception('No X.509 signature in JWS Header', Web_Authn_Exception::INVALID_DATA);
        }

        // algorithm
        if (!\in_array($header->alg, array('RS256', 'ES256'))) {
            throw new Web_Authn_Exception('invalid JWS algorithm ' . $header->alg, Web_Authn_Exception::INVALID_DATA);
        }

        $this->_x5c = \base64_decode($header->x5c[0]);
        $this->_payload = $payload;

        if (count($header->x5c) > 1) {
            for ($i = 1; $i < count($header->x5c); $i++) {
                $this->_x5c_chain[] = \base64_decode($header->x5c[$i]);
            }
            unset($i);
        }
    }

    /**
     * ctsProfileMatch: A stricter verdict of device integrity.
     * If the value of ctsProfileMatch is true, then the profile of the device running your app matches
     * the profile of a device that has passed Android compatibility testing and
     * has been approved as a Google-certified Android device.
     * @return bool
     */
    public function ctsProfileMatch()
    {
        return isset($this->_payload->ctsProfileMatch) ? !!$this->_payload->ctsProfileMatch : false;
    }


    /*
     * returns the key certificate in PEM format
     * @return string
     */
    public function getCertificatePem()
    {
        return $this->_createCertificatePem($this->_x5c);
    }

    /**
     * @param string $clientDataHash
     */
    public function validateAttestation($clientDataHash)
    {
        $publicKey = \openssl_pkey_get_public($this->getCertificatePem());

        // Verify that the nonce in the response is identical to the Base64 encoding
        // of the SHA-256 hash of the concatenation of Authenticator_Data and clientDataHash.
        if (empty($this->_payload->nonce) || $this->_payload->nonce !== \base64_encode(\hash('SHA256', $this->_Authenticator_Data->getBinary() . $clientDataHash, true))) {
            throw new Web_Authn_Exception('invalid nonce in JWS payload', Web_Authn_Exception::INVALID_DATA);
        }

        // Verify that attestationCert is issued to the hostname "attest.android.com"
        $certInfo = \openssl_x509_parse($this->getCertificatePem());
        if (!\is_array($certInfo) || ($certInfo['subject']['CN'] ?? '') !== 'attest.android.com') {
            throw new Web_Authn_Exception('invalid certificate CN in JWS (' . ($certInfo['subject']['CN'] ?? '-'). ')', Web_Authn_Exception::INVALID_DATA);
        }

        // Verify that the basicIntegrity attribute in the payload of response is true.
        if (empty($this->_payload->basicIntegrity)) {
            throw new Web_Authn_Exception('invalid basicIntegrity in payload', Web_Authn_Exception::INVALID_DATA);
        }

        // check certificate
        return \openssl_verify($this->_signedValue, $this->_signature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }


    /**
     * validates the certificate against root certificates
     * @param array $rootCas
     * @return boolean
     * @throws Web_Authn_Exception
     */
    public function validateRootCertificate($rootCas)
    {
        $chainC = $this->_createX5cChainFile();
        if ($chainC) {
            $rootCas[] = $chainC;
        }

        $v = \openssl_x509_checkpurpose($this->getCertificatePem(), -1, $rootCas);
        if ($v === -1) {
            throw new Web_Authn_Exception('error on validating root certificate: ' . \openssl_error_string(), Web_Authn_Exception::CERTIFICATE_NOT_TRUSTED);
        }
        return $v;
    }


    /**
     * decode base64 url
     * @param string $data
     * @return string
     */
    private function _base64url_decode($data)
    {
        return \base64_decode(\strtr($data, '-_', '+/') . \str_repeat('=', 3 - (3 + \strlen($data)) % 4));
    }
}
