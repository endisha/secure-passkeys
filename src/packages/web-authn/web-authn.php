<?php

namespace Secure_Passkeys\Packages\Web_Authn;

use Secure_Passkeys\Packages\Web_Authn\Binary\Byte_Buffer;

class Web_Authn
{
    private $_rpName;
    private $_rpId;
    private $_rpIdHash;
    private $_challenge;
    private $_signatureCounter;
    private $_caFiles;
    private $_formats;
    private $_Android_KeyHashes;

    public function __construct($rpName, $rpId, $allowedFormats = null, $useBase64UrlEncoding = false)
    {
        $this->_rpName = $rpName;
        $this->_rpId = $rpId;
        $this->_rpIdHash = \hash('sha256', $rpId, true);
        Byte_Buffer::$useBase64UrlEncoding = !!$useBase64UrlEncoding;
        $supportedFormats = array('android-key', 'android-safetynet', 'apple', 'fido-u2f', 'none', 'packed', 'tpm');

        if (!\function_exists('\openssl_open')) {
            throw new Web_Authn_Exception('OpenSSL-Module not installed');
        }

        if (!\in_array('SHA256', \array_map('\strtoupper', \openssl_get_md_methods()))) {
            throw new Web_Authn_Exception('SHA256 not supported by this openssl installation.');
        }

        // default: all format
        if (!is_array($allowedFormats)) {
            $allowedFormats = $supportedFormats;
        }
        $this->_formats = $allowedFormats;

        // validate formats
        $invalidFormats = \array_diff($this->_formats, $supportedFormats);
        if (!$this->_formats || $invalidFormats) {
            throw new Web_Authn_Exception('invalid formats on construct: ' . implode(', ', $invalidFormats));
        }
    }

    public function addRootCertificates($path, $certFileExtensions = null)
    {
        if (!\is_array($this->_caFiles)) {
            $this->_caFiles = [];
        }
        if ($certFileExtensions === null) {
            $certFileExtensions = array('pem', 'crt', 'cer', 'der');
        }
        $path = \rtrim(\trim($path), '\\/');
        if (\is_dir($path)) {
            foreach (\scandir($path) as $ca) {
                if (\is_file($path . DIRECTORY_SEPARATOR . $ca) && \in_array(\strtolower(\pathinfo($ca, PATHINFO_EXTENSION)), $certFileExtensions)) {
                    $this->addRootCertificates($path . DIRECTORY_SEPARATOR . $ca);
                }
            }
        } elseif (\is_file($path) && !\in_array(\realpath($path), $this->_caFiles)) {
            $this->_caFiles[] = \realpath($path);
        }
    }

    public function addAndroid_KeyHashes($hashes)
    {
        if (!\is_array($this->_Android_KeyHashes)) {
            $this->_Android_KeyHashes = [];
        }

        foreach ($hashes as $hash) {
            if (is_string($hash)) {
                $this->_Android_KeyHashes[] = $hash;
            }
        }
    }

    public function getChallenge()
    {
        return $this->_challenge;
    }

    public function getCreateArgs($userId, $userName, $userDisplayName, $timeout = 20, $requireResidentKey = false, $requireUserVerification = false, $crossPlatformAttachment = null, $excludeCredentialIds = [])
    {
        $args = new \stdClass();
        $args->publicKey = new \stdClass();

        // relying party
        $args->publicKey->rp = new \stdClass();
        $args->publicKey->rp->name = $this->_rpName;
        $args->publicKey->rp->id = $this->_rpId;

        $args->publicKey->authenticatorSelection = new \stdClass();
        $args->publicKey->authenticatorSelection->userVerification = 'preferred';

        // validate User Verification Requirement
        if (\is_bool($requireUserVerification)) {
            $args->publicKey->authenticatorSelection->userVerification = $requireUserVerification ? 'required' : 'preferred';

        } elseif (\is_string($requireUserVerification) && \in_array(\strtolower($requireUserVerification), ['required', 'preferred', 'discouraged'])) {
            $args->publicKey->authenticatorSelection->userVerification = \strtolower($requireUserVerification);
        }

        // validate Resident Key Requirement
        if (\is_bool($requireResidentKey) && $requireResidentKey) {
            $args->publicKey->authenticatorSelection->requireResidentKey = true;
            $args->publicKey->authenticatorSelection->residentKey = 'required';

        } elseif (\is_string($requireResidentKey) && \in_array(\strtolower($requireResidentKey), ['required', 'preferred', 'discouraged'])) {
            $requireResidentKey = \strtolower($requireResidentKey);
            $args->publicKey->authenticatorSelection->residentKey = $requireResidentKey;
            $args->publicKey->authenticatorSelection->requireResidentKey = $requireResidentKey === 'required';
        }

        // filte authenticators attached with the specified authenticator attachment modality
        if (\is_bool($crossPlatformAttachment)) {
            $args->publicKey->authenticatorSelection->authenticatorAttachment = $crossPlatformAttachment ? 'cross-platform' : 'platform';
        }

        // user
        $args->publicKey->user = new \stdClass();
        $args->publicKey->user->id = new Byte_Buffer($userId); // binary
        $args->publicKey->user->name = $userName;
        $args->publicKey->user->displayName = $userDisplayName;

        // supported algorithms
        $args->publicKey->pubKeyCredParams = [];

        if (function_exists('sodium_crypto_sign_verify_detached') || \in_array('ed25519', \openssl_get_curve_names(), true)) {
            $tmp = new \stdClass();
            $tmp->type = 'public-key';
            $tmp->alg = -8; // EdDSA
            $args->publicKey->pubKeyCredParams[] = $tmp;
            unset($tmp);
        }

        if (\in_array('prime256v1', \openssl_get_curve_names(), true)) {
            $tmp = new \stdClass();
            $tmp->type = 'public-key';
            $tmp->alg = -7; // ES256
            $args->publicKey->pubKeyCredParams[] = $tmp;
            unset($tmp);
        }

        $tmp = new \stdClass();
        $tmp->type = 'public-key';
        $tmp->alg = -257; // RS256
        $args->publicKey->pubKeyCredParams[] = $tmp;
        unset($tmp);

        // if there are root certificates added, we need direct attestation to validate
        // against the root certificate. If there are no root-certificates added,
        // anonymization ca are also accepted, because we can't validate the root anyway.
        $attestation = 'indirect';
        if (\is_array($this->_caFiles)) {
            $attestation = 'direct';
        }

        $args->publicKey->attestation = \count($this->_formats) === 1 && \in_array('none', $this->_formats) ? 'none' : $attestation;
        $args->publicKey->extensions = new \stdClass();
        $args->publicKey->extensions->exts = true;
        $args->publicKey->timeout = $timeout * 1000; // microseconds
        $args->publicKey->challenge = $this->_createChallenge(); // binary

        //prevent re-registration by specifying existing credentials
        $args->publicKey->excludeCredentials = [];

        if (is_array($excludeCredentialIds)) {
            foreach ($excludeCredentialIds as $id) {
                $tmp = new \stdClass();
                $tmp->id = $id instanceof Byte_Buffer ? $id : new Byte_Buffer($id);  // binary
                $tmp->type = 'public-key';
                $tmp->transports = array('usb', 'nfc', 'ble', 'hybrid', 'internal');
                $args->publicKey->excludeCredentials[] = $tmp;
                unset($tmp);
            }
        }

        return $args;
    }

    public function getGetArgs($credentialIds = [], $timeout = 20, $allowUsb = true, $allowNfc = true, $allowBle = true, $allowHybrid = true, $allowInternal = true, $requireUserVerification = false)
    {

        // validate User Verification Requirement
        if (\is_bool($requireUserVerification)) {
            $requireUserVerification = $requireUserVerification ? 'required' : 'preferred';
        } elseif (\is_string($requireUserVerification) && \in_array(\strtolower($requireUserVerification), ['required', 'preferred', 'discouraged'])) {
            $requireUserVerification = \strtolower($requireUserVerification);
        } else {
            $requireUserVerification = 'preferred';
        }

        $args = new \stdClass();
        $args->publicKey = new \stdClass();
        $args->publicKey->timeout = $timeout * 1000; // microseconds
        $args->publicKey->challenge = $this->_createChallenge();  // binary
        $args->publicKey->userVerification = $requireUserVerification;
        $args->publicKey->rpId = $this->_rpId;

        if (\is_array($credentialIds) && \count($credentialIds) > 0) {
            $args->publicKey->allowCredentials = [];

            foreach ($credentialIds as $id) {
                $tmp = new \stdClass();
                $tmp->id = $id instanceof Byte_Buffer ? $id : new Byte_Buffer($id);  // binary
                $tmp->transports = [];

                if ($allowUsb) {
                    $tmp->transports[] = 'usb';
                }
                if ($allowNfc) {
                    $tmp->transports[] = 'nfc';
                }
                if ($allowBle) {
                    $tmp->transports[] = 'ble';
                }
                if ($allowHybrid) {
                    $tmp->transports[] = 'hybrid';
                }
                if ($allowInternal) {
                    $tmp->transports[] = 'internal';
                }

                $tmp->type = 'public-key';
                $args->publicKey->allowCredentials[] = $tmp;
                unset($tmp);
            }
        }

        return $args;
    }

    public function getSignatureCounter()
    {
        return \is_int($this->_signatureCounter) ? $this->_signatureCounter : null;
    }

    public function processCreate($clientDataJSON, $Attestation_Object, $challenge, $requireUserVerification = false, $requireUserPresent = true, $failIfRootMismatch = true, $requireCtsProfileMatch = true)
    {
        $clientDataHash = \hash('sha256', $clientDataJSON, true);
        $clientData = \json_decode($clientDataJSON);
        $challenge = $challenge instanceof Byte_Buffer ? $challenge : new Byte_Buffer($challenge);

        // security: https://www.w3.org/TR/Web_Authn/#registering-a-new-credential

        // 2. Let C, the client data claimed as collected during the credential creation,
        //    be the result of running an implementation-specific JSON parser on JSONtext.
        if (!\is_object($clientData)) {
            throw new Web_Authn_Exception('invalid client data', Web_Authn_Exception::INVALID_DATA);
        }

        // 3. Verify that the value of C.type is Web_Authn.create.
        if (!\property_exists($clientData, 'type') || $clientData->type !== 'webauthn.create') {
            throw new Web_Authn_Exception('invalid type', Web_Authn_Exception::INVALID_TYPE);
        }

        // 4. Verify that the value of C.challenge matches the challenge that was sent to the authenticator in the create() call.
        if (!\property_exists($clientData, 'challenge') || Byte_Buffer::fromBase64Url($clientData->challenge)->getBinaryString() !== $challenge->getBinaryString()) {
            throw new Web_Authn_Exception('invalid challenge', Web_Authn_Exception::INVALID_CHALLENGE);
        }

        // 5. Verify that the value of C.origin matches the Relying Party's origin.
        if (!\property_exists($clientData, 'origin') || !$this->_checkOrigin($clientData->origin)) {
            throw new Web_Authn_Exception('invalid origin', Web_Authn_Exception::INVALID_ORIGIN);
        }

        // Attestation
        $Attestation_Object = new Attestation\Attestation_Object($Attestation_Object, $this->_formats);

        // 9. Verify that the RP ID hash in authData is indeed the SHA-256 hash of the RP ID expected by the RP.
        if (!$Attestation_Object->validateRpIdHash($this->_rpIdHash)) {
            throw new Web_Authn_Exception('invalid rpId hash', Web_Authn_Exception::INVALID_RELYING_PARTY);
        }

        // 14. Verify that attStmt is a correct attestation statement, conveying a valid attestation signature
        if (!$Attestation_Object->validateAttestation($clientDataHash)) {
            throw new Web_Authn_Exception('invalid certificate signature', Web_Authn_Exception::INVALID_SIGNATURE);
        }

        // Android-SafetyNet: if required, check for Compatibility Testing Suite (CTS).
        if ($requireCtsProfileMatch && $Attestation_Object->getAttestationFormat() instanceof Attestation\Format\Android_Safety_Net) {
            if (!$Attestation_Object->getAttestationFormat()->ctsProfileMatch()) {
                throw new Web_Authn_Exception('invalid ctsProfileMatch: device is not approved as a Google-certified Android device.', Web_Authn_Exception::ANDROID_NOT_TRUSTED);
            }
        }

        // 15. If validation is successful, obtain a list of acceptable trust anchors
        $rootValid = is_array($this->_caFiles) ? $Attestation_Object->validateRootCertificate($this->_caFiles) : null;
        if ($failIfRootMismatch && is_array($this->_caFiles) && !$rootValid) {
            throw new Web_Authn_Exception('invalid root certificate', Web_Authn_Exception::CERTIFICATE_NOT_TRUSTED);
        }

        // 10. Verify that the User Present bit of the flags in authData is set.
        $userPresent = $Attestation_Object->getAuthenticator_Data()->getUserPresent();
        if ($requireUserPresent && !$userPresent) {
            throw new Web_Authn_Exception('user not present during authentication', Web_Authn_Exception::USER_PRESENT);
        }

        // 11. If user verification is required for this registration, verify that the User Verified bit of the flags in authData is set.
        $userVerified = $Attestation_Object->getAuthenticator_Data()->getUserVerified();
        if ($requireUserVerification && !$userVerified) {
            throw new Web_Authn_Exception('user not verified during authentication', Web_Authn_Exception::USER_VERIFICATED);
        }

        $signCount = $Attestation_Object->getAuthenticator_Data()->getSignCount();
        if ($signCount > 0) {
            $this->_signatureCounter = $signCount;
        }

        // prepare data to store for future logins
        $data = new \stdClass();
        $data->rpId = $this->_rpId;
        $data->attestationFormat = $Attestation_Object->getAttestationFormatName();
        $data->credentialId = $Attestation_Object->getAuthenticator_Data()->getCredentialId();
        $data->credentialPublicKey = $Attestation_Object->getAuthenticator_Data()->getPublicKeyPem();
        $data->certificateChain = $Attestation_Object->getCertificateChain();
        $data->certificate = $Attestation_Object->getCertificatePem();
        $data->certificateIssuer = $Attestation_Object->getCertificateIssuer();
        $data->certificateSubject = $Attestation_Object->getCertificateSubject();
        $data->signatureCounter = $this->_signatureCounter;
        $data->AAGUID = $Attestation_Object->getAuthenticator_Data()->getAAGUID();
        $data->rootValid = $rootValid;
        $data->userPresent = $userPresent;
        $data->userVerified = $userVerified;
        $data->isBackupEligible = $Attestation_Object->getAuthenticator_Data()->getIsBackupEligible();
        $data->isBackedUp = $Attestation_Object->getAuthenticator_Data()->getIsBackup();
        return $data;
    }

    public function processGet($clientDataJSON, $Authenticator_Data, $signature, $credentialPublicKey, $challenge, $prevSignatureCnt = null, $requireUserVerification = false, $requireUserPresent = true)
    {
        $authenticatorObj = new Attestation\Authenticator_Data($Authenticator_Data);
        $clientDataHash = \hash('sha256', $clientDataJSON, true);
        $clientData = \json_decode($clientDataJSON);
        $challenge = $challenge instanceof Byte_Buffer ? $challenge : new Byte_Buffer($challenge);

        // https://www.w3.org/TR/Web_Authn/#verifying-assertion

        // 1. If the allowCredentials option was given when this authentication ceremony was initiated,
        //    verify that credential.id identifies one of the public key credentials that were listed in allowCredentials.
        //    -> TO BE VERIFIED BY IMPLEMENTATION

        // 2. If credential.response.userHandle is present, verify that the user identified
        //    by this value is the owner of the public key credential identified by credential.id.
        //    -> TO BE VERIFIED BY IMPLEMENTATION

        // 3. Using credential’s id attribute (or the corresponding rawId, if base64url encoding is
        //    inappropriate for your use case), look up the corresponding credential public key.
        //    -> TO BE LOOKED UP BY IMPLEMENTATION

        // 5. Let JSONtext be the result of running UTF-8 decode on the value of cData.
        if (!\is_object($clientData)) {
            throw new Web_Authn_Exception('invalid client data', Web_Authn_Exception::INVALID_DATA);
        }

        // 7. Verify that the value of C.type is the string Web_Authn.get.
        if (!\property_exists($clientData, 'type') || $clientData->type !== 'webauthn.get') {
            throw new Web_Authn_Exception('invalid type', Web_Authn_Exception::INVALID_TYPE);
        }

        // 8. Verify that the value of C.challenge matches the challenge that was sent to the
        //    authenticator in the PublicKeyCredentialRequestOptions passed to the get() call.
        if (!\property_exists($clientData, 'challenge') || Byte_Buffer::fromBase64Url($clientData->challenge)->getBinaryString() !== $challenge->getBinaryString()) {
            throw new Web_Authn_Exception('invalid challenge', Web_Authn_Exception::INVALID_CHALLENGE);
        }

        // 9. Verify that the value of C.origin matches the Relying Party's origin.
        if (!\property_exists($clientData, 'origin') || !$this->_checkOrigin($clientData->origin)) {
            throw new Web_Authn_Exception('invalid origin', Web_Authn_Exception::INVALID_ORIGIN);
        }

        // 11. Verify that the rpIdHash in authData is the SHA-256 hash of the RP ID expected by the Relying Party.
        if ($authenticatorObj->getRpIdHash() !== $this->_rpIdHash) {
            throw new Web_Authn_Exception('invalid rpId hash', Web_Authn_Exception::INVALID_RELYING_PARTY);
        }

        // 12. Verify that the User Present bit of the flags in authData is set
        if ($requireUserPresent && !$authenticatorObj->getUserPresent()) {
            throw new Web_Authn_Exception('user not present during authentication', Web_Authn_Exception::USER_PRESENT);
        }

        // 13. If user verification is required for this assertion, verify that the User Verified bit of the flags in authData is set.
        if ($requireUserVerification && !$authenticatorObj->getUserVerified()) {
            throw new Web_Authn_Exception('user not verificated during authentication', Web_Authn_Exception::USER_VERIFICATED);
        }

        // 14. Verify the values of the client extension outputs
        //     (extensions not implemented)

        // 16. Using the credential public key looked up in step 3, verify that sig is a valid signature
        //     over the binary concatenation of authData and hash.
        $dataToVerify = '';
        $dataToVerify .= $Authenticator_Data;
        $dataToVerify .= $clientDataHash;

        if (!$this->_verifySignature($dataToVerify, $signature, $credentialPublicKey)) {
            throw new Web_Authn_Exception('invalid signature', Web_Authn_Exception::INVALID_SIGNATURE);
        }

        $signatureCounter = $authenticatorObj->getSignCount();
        if ($signatureCounter !== 0) {
            $this->_signatureCounter = $signatureCounter;
        }

        // 17. If either of the signature counter value authData.signCount or
        //     previous signature count is nonzero, and if authData.signCount
        //     less than or equal to previous signature count, it's a signal
        //     that the authenticator may be cloned
        if ($prevSignatureCnt !== null) {
            if ($signatureCounter !== 0 || $prevSignatureCnt !== 0) {
                if ($prevSignatureCnt >= $signatureCounter) {
                    throw new Web_Authn_Exception('signature counter not valid', Web_Authn_Exception::SIGNATURE_COUNTER);
                }
            }
        }

        return true;
    }

    private function _checkOrigin($origin)
    {
        if (str_starts_with($origin, 'android:apk-key-hash:')) {
            return $this->_checkAndroid_KeyHashes($origin);
        }

        // https://www.w3.org/TR/Web_Authn/#rp-id

        // The origin's scheme must be https
        if ($this->_rpId !== 'localhost' && \wp_parse_url($origin, PHP_URL_SCHEME) !== 'https') {
            return false;
        }

        // extract host from origin
        $host = \wp_parse_url($origin, PHP_URL_HOST);
        $host = \trim($host, '.');

        // The RP ID must be equal to the origin's effective domain, or a registrable
        // domain suffix of the origin's effective domain.
        return \preg_match('/' . \preg_quote($this->_rpId) . '$/i', $host) === 1;
    }

    private function _checkAndroid_KeyHashes($origin)
    {
        $parts = explode('android:apk-key-hash:', $origin);
        if (count($parts) !== 2) {
            return false;
        }
        return in_array($parts[1], $this->_Android_KeyHashes, true);
    }
    
    private function _createChallenge($length = 32)
    {
        if (!$this->_challenge) {
            $this->_challenge = Byte_Buffer::randomBuffer($length);
        }
        return $this->_challenge;
    }

    private function _verifySignature($dataToVerify, $signature, $credentialPublicKey)
    {

        // Use Sodium to verify EdDSA 25519 as its not yet supported by openssl
        if (\function_exists('sodium_crypto_sign_verify_detached') && !\in_array('ed25519', \openssl_get_curve_names(), true)) {
            $pkParts = [];
            if (\preg_match('/BEGIN PUBLIC KEY\-+(?:\s|\n|\r)+([^\-]+)(?:\s|\n|\r)*\-+END PUBLIC KEY/i', $credentialPublicKey, $pkParts)) {
                $rawPk = \base64_decode($pkParts[1]);

                // 30        = der sequence
                // 2a        = length 42 byte
                // 30        = der sequence
                // 05        = lenght 5 byte
                // 06        = der OID
                // 03        = OID length 3 byte
                // 2b 65 70  = OID 1.3.101.112 curveEd25519 (EdDSA 25519 signature algorithm)
                // 03        = der bit string
                // 21        = length 33 byte
                // 00        = null padding
                // [...]     = 32 byte x-curve
                $okpPrefix = "\x30\x2a\x30\x05\x06\x03\x2b\x65\x70\x03\x21\x00";

                if ($rawPk && \strlen($rawPk) === 44 && \substr($rawPk, 0, \strlen($okpPrefix)) === $okpPrefix) {
                    $publicKeyXCurve = \substr($rawPk, \strlen($okpPrefix));

                    return \sodium_crypto_sign_verify_detached($signature, $dataToVerify, $publicKeyXCurve);
                }
            }
        }

        // verify with openSSL
        $publicKey = \openssl_pkey_get_public($credentialPublicKey);
        if ($publicKey === false) {
            throw new Web_Authn_Exception('public key invalid', Web_Authn_Exception::INVALID_PUBLIC_KEY);
        }

        return \openssl_verify($dataToVerify, $signature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }
}
