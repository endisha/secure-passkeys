<?php

namespace Secure_Passkeys\Packages\Web_Authn\Attestation\Format;

use Secure_Passkeys\Packages\Web_Authn\Attestation\Authenticator_Data;
use Secure_Passkeys\Packages\Web_Authn\Binary\Byte_Buffer;
use Secure_Passkeys\Packages\Web_Authn\Web_Authn_Exception;

class Apple extends Format_Base
{
    private $_x5c;

    public function __construct($AttestionObject, Authenticator_Data $Authenticator_Data)
    {
        parent::__construct($AttestionObject, $Authenticator_Data);

        // check packed data
        $attStmt = $this->_Attestation_Object['attStmt'];


        // certificate for validation
        if (\array_key_exists('x5c', $attStmt) && \is_array($attStmt['x5c']) && \count($attStmt['x5c']) > 0) {

            // The attestation certificate attestnCert MUST be the first element in the array
            $attestnCert = array_shift($attStmt['x5c']);

            if (!($attestnCert instanceof Byte_Buffer)) {
                throw new Web_Authn_Exception('invalid x5c certificate', Web_Authn_Exception::INVALID_DATA);
            }

            $this->_x5c = $attestnCert->getBinaryString();

            // certificate chain
            foreach ($attStmt['x5c'] as $chain) {
                if ($chain instanceof Byte_Buffer) {
                    $this->_x5c_chain[] = $chain->getBinaryString();
                }
            }
        } else {
            throw new Web_Authn_Exception('invalid Apple attestation statement: missing x5c', Web_Authn_Exception::INVALID_DATA);
        }
    }


    /*
     * returns the key certificate in PEM format
     * @return string|null
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
        return $this->_validateOverX5c($clientDataHash);
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
     * validate if x5c is present
     * @param string $clientDataHash
     * @return bool
     * @throws Web_Authn_Exception
     */
    protected function _validateOverX5c($clientDataHash)
    {
        $publicKey = \openssl_pkey_get_public($this->getCertificatePem());

        if ($publicKey === false) {
            throw new Web_Authn_Exception('invalid public key: ' . \openssl_error_string(), Web_Authn_Exception::INVALID_PUBLIC_KEY);
        }

        // Concatenate Authenticator_Data and clientDataHash to form nonceToHash.
        $nonceToHash = $this->_Authenticator_Data->getBinary();
        $nonceToHash .= $clientDataHash;

        // Perform SHA-256 hash of nonceToHash to produce nonce
        $nonce = hash('SHA256', $nonceToHash, true);

        $credCert = openssl_x509_read($this->getCertificatePem());
        if ($credCert === false) {
            throw new Web_Authn_Exception('invalid x5c certificate: ' . \openssl_error_string(), Web_Authn_Exception::INVALID_DATA);
        }

        $keyData = openssl_pkey_get_details(openssl_pkey_get_public($credCert));
        $key = is_array($keyData) && array_key_exists('key', $keyData) ? $keyData['key'] : null;


        // Verify that nonce equals the value of the extension with OID ( 1.2.840.113635.100.8.2 ) in credCert.
        $parsedCredCert = openssl_x509_parse($credCert);
        $nonceExtension = $parsedCredCert['extensions']['1.2.840.113635.100.8.2'] ?? '';

        // nonce padded by ASN.1 string: 30 24 A1 22 04 20
        // 30     — type tag indicating sequence
        // 24     — 36 byte following
        //   A1   — Enumerated [1]
        //   22   — 34 byte following
        //     04 — type tag indicating octet string
        //     20 — 32 byte following

        $asn1Padding = "\x30\x24\xA1\x22\x04\x20";
        if (substr($nonceExtension, 0, strlen($asn1Padding)) === $asn1Padding) {
            $nonceExtension = substr($nonceExtension, strlen($asn1Padding));
        }

        if ($nonceExtension !== $nonce) {
            throw new Web_Authn_Exception('nonce doesn\'t equal the value of the extension with OID 1.2.840.113635.100.8.2', Web_Authn_Exception::INVALID_DATA);
        }

        // Verify that the credential public key equals the Subject Public Key of credCert.
        $authKeyData = openssl_pkey_get_details(openssl_pkey_get_public($this->_Authenticator_Data->getPublicKeyPem()));
        $authKey = is_array($authKeyData) && array_key_exists('key', $authKeyData) ? $authKeyData['key'] : null;

        if ($key === null || $key !== $authKey) {
            throw new Web_Authn_Exception('credential public key doesn\'t equal the Subject Public Key of credCert', Web_Authn_Exception::INVALID_DATA);
        }

        return true;
    }

}
