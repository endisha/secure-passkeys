<?php

namespace Secure_Passkeys\Packages\Web_Authn\Attestation;

use Secure_Passkeys\Packages\Web_Authn\Web_Authn_Exception;
use Secure_Passkeys\Packages\Web_Authn\Cbor\Cbor_Decoder;
use Secure_Passkeys\Packages\Web_Authn\Binary\Byte_Buffer;

/**
 * @author Lukas Buchs
 * @license https://github.com/lbuchs/Web_Authn/blob/master/LICENSE MIT
 */
class Attestation_Object
{
    private $_Authenticator_Data;
    private $_attestationFormat;
    private $_attestationFormatName;

    public function __construct($binary, $allowedFormats)
    {
        $enc = Cbor_Decoder::decode($binary);
        // validation
        if (!\is_array($enc) || !\array_key_exists('fmt', $enc) || !is_string($enc['fmt'])) {
            throw new Web_Authn_Exception('invalid attestation format', Web_Authn_Exception::INVALID_DATA);
        }

        if (!\array_key_exists('attStmt', $enc) || !\is_array($enc['attStmt'])) {
            throw new Web_Authn_Exception('invalid attestation format (attStmt not available)', Web_Authn_Exception::INVALID_DATA);
        }

        if (!\array_key_exists('authData', $enc) || !\is_object($enc['authData']) || !($enc['authData'] instanceof Byte_Buffer)) {
            throw new Web_Authn_Exception('invalid attestation format (authData not available)', Web_Authn_Exception::INVALID_DATA);
        }

        $this->_Authenticator_Data = new Authenticator_Data($enc['authData']->getBinaryString());
        $this->_attestationFormatName = $enc['fmt'];

        // Format ok?
        if (!in_array($this->_attestationFormatName, $allowedFormats)) {
            throw new Web_Authn_Exception('invalid atttestation format: ' . $this->_attestationFormatName, Web_Authn_Exception::INVALID_DATA);
        }


        switch ($this->_attestationFormatName) {
            case 'android-key': $this->_attestationFormat = new Format\Android_Key($enc, $this->_Authenticator_Data);
                break;
            case 'android-safetynet': $this->_attestationFormat = new Format\Android_Safety_Net($enc, $this->_Authenticator_Data);
                break;
            case 'apple': $this->_attestationFormat = new Format\Apple($enc, $this->_Authenticator_Data);
                break;
            case 'fido-u2f': $this->_attestationFormat = new Format\U2f($enc, $this->_Authenticator_Data);
                break;
            case 'none': $this->_attestationFormat = new Format\None($enc, $this->_Authenticator_Data);
                break;
            case 'packed': $this->_attestationFormat = new Format\Packed($enc, $this->_Authenticator_Data);
                break;
            case 'tpm': $this->_attestationFormat = new Format\Tpm($enc, $this->_Authenticator_Data);
                break;
            default: throw new Web_Authn_Exception('invalid attestation format: ' . $enc['fmt'], Web_Authn_Exception::INVALID_DATA);
        }
    }

    /**
     * returns the attestation format name
     * @return string
     */
    public function getAttestationFormatName()
    {
        return $this->_attestationFormatName;
    }

    /**
     * returns the attestation format class
     * @return Format\Format_Base
     */
    public function getAttestationFormat()
    {
        return $this->_attestationFormat;
    }

    /**
     * returns the attestation public key in PEM format
     * @return Authenticator_Data
     */
    public function getAuthenticator_Data()
    {
        return $this->_Authenticator_Data;
    }

    /**
     * returns the certificate chain as PEM
     * @return string|null
     */
    public function getCertificateChain()
    {
        return $this->_attestationFormat->getCertificateChain();
    }

    /**
     * return the certificate issuer as string
     * @return string
     */
    public function getCertificateIssuer()
    {
        $pem = $this->getCertificatePem();
        $issuer = '';
        if ($pem) {
            $certInfo = \openssl_x509_parse($pem);
            if (\is_array($certInfo) && \array_key_exists('issuer', $certInfo) && \is_array($certInfo['issuer'])) {

                $cn = $certInfo['issuer']['CN'] ?? '';
                $o = $certInfo['issuer']['O'] ?? '';
                $ou = $certInfo['issuer']['OU'] ?? '';

                if ($cn) {
                    $issuer .= $cn;
                }
                if ($issuer && ($o || $ou)) {
                    $issuer .= ' (' . trim($o . ' ' . $ou) . ')';
                } else {
                    $issuer .= trim($o . ' ' . $ou);
                }
            }
        }

        return $issuer;
    }

    /**
     * return the certificate subject as string
     * @return string
     */
    public function getCertificateSubject()
    {
        $pem = $this->getCertificatePem();
        $subject = '';
        if ($pem) {
            $certInfo = \openssl_x509_parse($pem);
            if (\is_array($certInfo) && \array_key_exists('subject', $certInfo) && \is_array($certInfo['subject'])) {

                $cn = $certInfo['subject']['CN'] ?? '';
                $o = $certInfo['subject']['O'] ?? '';
                $ou = $certInfo['subject']['OU'] ?? '';

                if ($cn) {
                    $subject .= $cn;
                }
                if ($subject && ($o || $ou)) {
                    $subject .= ' (' . trim($o . ' ' . $ou) . ')';
                } else {
                    $subject .= trim($o . ' ' . $ou);
                }
            }
        }

        return $subject;
    }

    /**
     * returns the key certificate in PEM format
     * @return string
     */
    public function getCertificatePem()
    {
        return $this->_attestationFormat->getCertificatePem();
    }

    /**
     * checks validity of the signature
     * @param string $clientDataHash
     * @return bool
     * @throws Web_Authn_Exception
     */
    public function validateAttestation($clientDataHash)
    {
        return $this->_attestationFormat->validateAttestation($clientDataHash);
    }

    /**
     * validates the certificate against root certificates
     * @param array $rootCas
     * @return boolean
     * @throws Web_Authn_Exception
     */
    public function validateRootCertificate($rootCas)
    {
        return $this->_attestationFormat->validateRootCertificate($rootCas);
    }

    /**
     * checks if the RpId-Hash is valid
     * @param string$rpIdHash
     * @return bool
     */
    public function validateRpIdHash($rpIdHash)
    {
        return $rpIdHash === $this->_Authenticator_Data->getRpIdHash();
    }
}
