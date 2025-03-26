<?php

namespace Secure_Passkeys\Actions;

use Secure_Passkeys\Exceptions\Secure_Passkeys_Web_Authn_Invalid_Credentials_Exception;
use Secure_Passkeys\Models\Secure_Passkeys_WebAuthn;
use Secure_Passkeys\Packages\Web_Authn\Web_Authn;
use Secure_Passkeys\Utils\Secure_Passkeys_Helper;

class Secure_Passkeys_Web_Authn_Login_Action extends Secure_Passkeys_Web_Authn_Action
{
    public function execute(string $challenge, array $credentials)
    {
        $webauthn = new Web_Authn(
            $this->get_relying_party_id(),
            $this->get_relying_party_id()
        );

        $credential_id = Secure_Passkeys_Helper::get_raw_credential_id($credentials['rawId']);

        $data = (new Secure_Passkeys_WebAuthn())->get_by_credential_id($credential_id);

        if (is_null($data)) {
            throw new Secure_Passkeys_Web_Authn_Invalid_Credentials_Exception('Invalid credentials');
        }

        if (!$data->is_active) {
            throw new Secure_Passkeys_Web_Authn_Invalid_Credentials_Exception('Invalid credentials');
        }

        if (Secure_Passkeys_Helper::is_user_in_excluded_roles(intval($data->user_id))) {
            throw new Secure_Passkeys_Web_Authn_Invalid_Credentials_Exception('Invalid credentials');
        }

        $webauthn->processGet(
            Secure_Passkeys_Helper::base64url_decode($credentials['response']['clientDataJSON']),
            Secure_Passkeys_Helper::base64url_decode($credentials['response']['authenticatorData']),
            Secure_Passkeys_Helper::base64url_decode($credentials['response']['signature']),
            $data->public_key,
            Secure_Passkeys_Helper::base64url_decode($challenge)
        );

        return $data;
    }
}
