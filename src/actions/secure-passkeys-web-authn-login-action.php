<?php

namespace Secure_Passkeys\Actions;

use Secure_Passkeys\Exceptions\Secure_Passkeys_Web_Authn_Invalid_Credentials_Exception;
use Secure_Passkeys\Models\Secure_Passkeys_WebAuthn;
use Secure_Passkeys\Packages\Web_Authn\Web_Authn;
use Secure_Passkeys\Utils\Secure_Passkeys_Helper;

defined('ABSPATH') || exit;
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
            throw new Secure_Passkeys_Web_Authn_Invalid_Credentials_Exception('NOT_FOUND');
        }

        if (!$data->is_active) {
            throw new Secure_Passkeys_Web_Authn_Invalid_Credentials_Exception('INACTIVE_PASSKEY');
        }

        if (Secure_Passkeys_Helper::is_user_in_excluded_roles(intval($data->user_id))) {
            throw new Secure_Passkeys_Web_Authn_Invalid_Credentials_Exception('EXCLUDED_ROLES');
        }

        $webauthn->processGet(
            Secure_Passkeys_Helper::base64url_decode($credentials['response']['clientDataJSON']),
            Secure_Passkeys_Helper::base64url_decode($credentials['response']['authenticatorData']),
            Secure_Passkeys_Helper::base64url_decode($credentials['response']['signature']),
            $data->public_key,
            Secure_Passkeys_Helper::base64url_decode($challenge),
            intval($data->sign_count ?? 0),
            $this->is_login_user_verification_required()
        );

        $new_signature_counter = $webauthn->getSignatureCounter();

        if ($new_signature_counter !== null) {
            (new Secure_Passkeys_WebAuthn())->update_signature_counter($data->id, $new_signature_counter);
        }

        return $data;
    }
}
