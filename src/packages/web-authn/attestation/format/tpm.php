<?php

namespace Secure_Passkeys\Packages\Web_Authn\Attestation\Format;

use Secure_Passkeys\Packages\Web_Authn\Attestation\Authenticator_Data;
use Secure_Passkeys\Packages\Web_Authn\Binary\Byte_Buffer;
use Secure_Passkeys\Packages\Web_Authn\Web_Authn_Exception;

class Tpm extends Format_Base
{
    private $_TPM_GENERATED_VALUE = "\xFF\x54\x43\x47";
    private $_TPM_ST_ATTEST_CERTIFY = "\x80\x17";
    private $_alg;
    private $_signature;
    private $_pubArea;
    private $_x5c;

    /**
     * @var Byte_Buffer
     */
    private $_certInfo;


    public function __construct($AttestionObject, Authenticator_Data $Authenticator_Data)
    {
        parent::__construct($AttestionObject, $Authenticator_Data);

        // check packed data
        $attStmt = $this->_Attestation_Object['attStmt'];

        if (!\array_key_exists('ver', $attStmt) || $attStmt['ver'] !== '2.0') {
            throw new Web_Authn_Exception('invalid tpm version: ' . $attStmt['ver'], Web_Authn_Exception::INVALID_DATA);
        }

        if (!\array_key_exists('alg', $attStmt) || $this->_getCoseAlgorithm($attStmt['alg']) === null) {
            throw new Web_Authn_Exception('unsupported alg: ' . $attStmt['alg'], Web_Authn_Exception::INVALID_DATA);
        }

        if (!\array_key_exists('sig', $attStmt) || !\is_object($attStmt['sig']) || !($attStmt['sig'] instanceof Byte_Buffer)) {
            throw new Web_Authn_Exception('signature not found', Web_Authn_Exception::INVALID_DATA);
        }

        if (!\array_key_exists('certInfo', $attStmt) || !\is_object($attStmt['certInfo']) || !($attStmt['certInfo'] instanceof Byte_Buffer)) {
            throw new Web_Authn_Exception('certInfo not found', Web_Authn_Exception::INVALID_DATA);
        }

        if (!\array_key_exists('pubArea', $attStmt) || !\is_object($attStmt['pubArea']) || !($attStmt['pubArea'] instanceof Byte_Buffer)) {
            throw new Web_Authn_Exception('pubArea not found', Web_Authn_Exception::INVALID_DATA);
        }

        $this->_alg = $attStmt['alg'];
        $this->_signature = $attStmt['sig']->getBinaryString();
        $this->_certInfo = $attStmt['certInfo'];
        $this->_pubArea = $attStmt['pubArea'];

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
            throw new Web_Authn_Exception('no x5c certificate found', Web_Authn_Exception::INVALID_DATA);
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

        // Concatenate Authenticator_Data and clientDataHash to form attToBeSigned.
        $attToBeSigned = $this->_Authenticator_Data->getBinary();
        $attToBeSigned .= $clientDataHash;

        // Validate that certInfo is valid:

        // Verify that magic is set to TPM_GENERATED_VALUE.
        if ($this->_certInfo->getBytes(0, 4) !== $this->_TPM_GENERATED_VALUE) {
            throw new Web_Authn_Exception('tpm magic not TPM_GENERATED_VALUE', Web_Authn_Exception::INVALID_DATA);
        }

        // Verify that type is set to TPM_ST_ATTEST_CERTIFY.
        if ($this->_certInfo->getBytes(4, 2) !== $this->_TPM_ST_ATTEST_CERTIFY) {
            throw new Web_Authn_Exception('tpm type not TPM_ST_ATTEST_CERTIFY', Web_Authn_Exception::INVALID_DATA);
        }

        $offset = 6;
        $qualifiedSigner = $this->_tpmReadLengthPrefixed($this->_certInfo, $offset);
        $extraData = $this->_tpmReadLengthPrefixed($this->_certInfo, $offset);
        $coseAlg = $this->_getCoseAlgorithm($this->_alg);

        // Verify that extraData is set to the hash of attToBeSigned using the hash algorithm employed in "alg".
        if ($extraData->getBinaryString() !== \hash($coseAlg->hash, $attToBeSigned, true)) {
            throw new Web_Authn_Exception('certInfo:extraData not hash of attToBeSigned', Web_Authn_Exception::INVALID_DATA);
        }

        // Verify the sig is a valid signature over certInfo using the attestation
        // public key in aikCert with the algorithm specified in alg.
        return \openssl_verify($this->_certInfo->getBinaryString(), $this->_signature, $publicKey, $coseAlg->openssl) === 1;
    }


    /**
     * returns next part of Byte_Buffer
     * @param Byte_Buffer $buffer
     * @param int $offset
     * @return Byte_Buffer
     */
    protected function _tpmReadLengthPrefixed(Byte_Buffer $buffer, &$offset)
    {
        $len = $buffer->getUint16Val($offset);
        $data = $buffer->getBytes($offset + 2, $len);
        $offset += (2 + $len);

        return new Byte_Buffer($data);
    }

}
