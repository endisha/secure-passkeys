<?php

namespace Secure_Passkeys\Packages\Web_Authn\Attestation\Format;

use Secure_Passkeys\Packages\Web_Authn\Attestation\Authenticator_Data;
use Secure_Passkeys\Packages\Web_Authn\Web_Authn_Exception;

class None extends Format_Base
{
    public function __construct($AttestionObject, Authenticator_Data $Authenticator_Data)
    {
        parent::__construct($AttestionObject, $Authenticator_Data);
    }

    /*
     * returns the key certificate in PEM format
     * @return string
     */
    public function getCertificatePem()
    {
        return null;
    }

    /**
     * @param string $clientDataHash
     */
    public function validateAttestation($clientDataHash)
    {
        return true;
    }

    /**
     * validates the certificate against root certificates.
     * Format 'none' does not contain any ca, so always false.
     * @param array $rootCas
     * @return boolean
     * @throws Web_Authn_Exception
     */
    public function validateRootCertificate($rootCas)
    {
        return false;
    }
}
