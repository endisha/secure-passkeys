<?php

namespace Secure_Passkeys\Actions;

use Secure_Passkeys\Exceptions\Secure_Passkeys_Web_Authn_Already_Registered_Exception;
use Secure_Passkeys\Exceptions\Secure_Passkeys_Web_Authn_Invalid_User_Exception;
use Secure_Passkeys\Exceptions\Secure_Passkeys_Web_Authn_Reach_Maximum_Credentials_Exception;
use Secure_Passkeys\Models\Secure_Passkeys_WebAuthn;
use Secure_Passkeys\Packages\Web_Authn\Binary\Byte_Buffer;
use Secure_Passkeys\Packages\Web_Authn\Web_Authn;
use Secure_Passkeys\Utils\Secure_Passkeys_Helper;
use Secure_Passkeys\Utils\Secure_Passkeys_Webauthn_Helper;

class Secure_Passkeys_Web_Authn_Enable_Action extends Secure_Passkeys_Web_Authn_Action
{
    public function execute(int $user_id, string $challenge, array $data, ?string $security_key = null): array
    {
        $webAuthn = new Web_Authn(
            $this->get_relying_party_id(),
            $this->get_relying_party_id()
        );

        $credential_id = Secure_Passkeys_Helper::get_raw_credential_id($data['rawId']);
        $clientDataJSON = Secure_Passkeys_Helper::base64url_decode($data['response']['clientDataJSON']);
        $attestationObject = Secure_Passkeys_Helper::base64url_decode($data['response']['attestationObject']);
        $challenge = Secure_Passkeys_Helper::base64url_decode($challenge);

        $pre_validate = apply_filters('secure_passkeys_web_authn_validate_user_pre_enable', true, $user_id, $security_key);

        if (!$pre_validate) {
            throw new Secure_Passkeys_Web_Authn_Invalid_User_Exception();
        }

        $model = new Secure_Passkeys_WebAuthn();

        if (Secure_Passkeys_Helper::get_option('registration_maximum_passkeys_enabled', 'on') === 'on') {
            $credentials_count = $model->get_count_by_user_id($user_id);

            if ($credentials_count >= intval(Secure_Passkeys_Helper::get_option('registration_maximum_passkeys_per_user', 3))) {
                throw new Secure_Passkeys_Web_Authn_Reach_Maximum_Credentials_Exception();
            }
        }

        if ($model->is_credential_id_for_user_id($user_id, $credential_id)) {
            throw new Secure_Passkeys_Web_Authn_Already_Registered_Exception();
        }

        if ($security_key && $model->is_security_key_name_for_user_id($user_id, $security_key)) {
            throw new Secure_Passkeys_Web_Authn_Already_Registered_Exception();
        }

        $validate = apply_filters('secure_passkeys_web_authn_validate_user_enable', true, $user_id, $security_key);

        if (!$validate) {
            throw new Secure_Passkeys_Web_Authn_Invalid_User_Exception();
        }

        $attestation = $webAuthn->processCreate(
            $clientDataJSON,
            new Byte_Buffer($attestationObject),
            $challenge,
            $this->is_user_verification_required(),
        );

        return apply_filters('secure_passkeys_web_authn_enable_data', [
            'user_id' => $user_id,
            'credential_id' => $credential_id,
            'public_key' => $attestation->credentialPublicKey,
            'aaguid' => Secure_Passkeys_Webauthn_Helper::convert_aaguid_to_hex($attestation->AAGUID),
            'last_used_at' => null,
        ]);
    }
}
