<?php

namespace Secure_Passkeys\Packages\Web_Authn\Attestation\Format;

use Secure_Passkeys\Packages\Web_Authn\Attestation\Authenticator_Data;
use Secure_Passkeys\Packages\Web_Authn\Binary\Byte_Buffer;
use Secure_Passkeys\Packages\Web_Authn\Web_Authn_Exception;

class Packed extends Format_Base
{
    private $_alg;
    private $_signature;
    private $_x5c;

    public function __construct($AttestionObject, Authenticator_Data $Authenticator_Data)
    {
        parent::__construct($AttestionObject, $Authenticator_Data);

        // check packed data
        $attStmt = $this->_Attestation_Object['attStmt'];

        if (!\array_key_exists('alg', $attStmt) || $this->_getCoseAlgorithm($attStmt['alg']) === null) {
            throw new Web_Authn_Exception('unsupported alg: ' . $attStmt['alg'], Web_Authn_Exception::INVALID_DATA);
        }

        if (!\array_key_exists('sig', $attStmt) || !\is_object($attStmt['sig']) || !($attStmt['sig'] instanceof Byte_Buffer)) {
            throw new Web_Authn_Exception('no signature found', Web_Authn_Exception::INVALID_DATA);
        }

        $this->_alg = $attStmt['alg'];
        $this->_signature = $attStmt['sig']->getBinaryString();

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
        }
    }


    /*
     * returns the key certificate in PEM format
     * @return string|null
     */
    public function getCertificatePem()
    {
        if (!$this->_x5c) {
            return null;
        }
        return $this->_createCertificatePem($this->_x5c);
    }

    /**
     * @param string $clientDataHash
     */
    public function validateAttestation($clientDataHash)
    {
        if ($this->_x5c) {
            return $this->_validateOverX5c($clientDataHash);
        } else {
            return $this->_validateSelfAttestation($clientDataHash);
        }
    }

    /**
     * validates the certificate against root certificates
     * @param array $rootCas
     * @return boolean
     * @throws Web_Authn_Exception
     */
    public function validateRootCertificate($rootCas)
    {
        if (!$this->_x5c) {
            return false;
        }

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

        // Verify that sig is a valid signature over the concatenation of Authenticator_Data and clientDataHash
        // using the attestation public key in attestnCert with the algorithm specified in alg.
        $dataToVerify = $this->_Authenticator_Data->getBinary();
        $dataToVerify .= $clientDataHash;

        $coseAlgorithm = $this->_getCoseAlgorithm($this->_alg);

        // check certificate
        return \openssl_verify($dataToVerify, $this->_signature, $publicKey, $coseAlgorithm->openssl) === 1;
    }

    /**
     * validate if self attestation is in use
     * @param string $clientDataHash
     * @return bool
     */
    protected function _validateSelfAttestation($clientDataHash)
    {
        // Verify that sig is a valid signature over the concatenation of Authenticator_Data and clientDataHash
        // using the credential public key with alg.
        $dataToVerify = $this->_Authenticator_Data->getBinary();
        $dataToVerify .= $clientDataHash;

        $publicKey = $this->_Authenticator_Data->getPublicKeyPem();

        // check certificate
        return \openssl_verify($dataToVerify, $this->_signature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }
}
