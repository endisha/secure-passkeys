<?php

namespace Secure_Passkeys\Actions;

use Secure_Passkeys\Utils\Secure_Passkeys_Helper;

class Secure_Passkeys_Web_Authn_Enable_Options_Action extends Secure_Passkeys_Web_Authn_Action
{
    public function execute(int $user_id, string $challenge): array
    {
        $username = Secure_Passkeys_Helper::get_user_full_name($user_id);

        $options = [
            'challenge' => $challenge,
            'rp' => [
                'id' => $this->get_relying_party_id(),
                'name' => $this->get_relying_party_id(),
            ],
            'user' => [
                'id' => base64_encode($user_id),
                'name' => $username,
                'displayName' => $username,
            ],
            'pubKeyCredParams' => [
                [
                    'type' => 'public-key',
                    'alg' => -7,
                ],
                [
                    'type' => 'public-key',
                    'alg' => -257,
                ],
            ],
            'attestation' => 'none',
            'extensions' => [
                'credProps' => true,
            ],
            'timeout' => $this->get_registration_timeout(),
        ];

        if ($this->is_authenticator_selection_enabled()) {
            $options['authenticatorSelection'] = $this->get_authenticator_selection();
        }

        if ($this->is_exclude_credentials_enabled()) {
            $excludeCredentials = $this->excluded_credentials_for_user($user_id);
            if (!empty($excludeCredentials)) {
                $options['excludeCredentials'] = $excludeCredentials;
            }
        }

        return apply_filters('secure_passkeys_web_authn_register_options', $options);
    }
}
