<?php

namespace Secure_Passkeys\Actions;

class Secure_Passkeys_Web_Authn_Login_Options_Action extends Secure_Passkeys_Web_Authn_Action
{
    public function execute(string $challenge): array
    {
        $options = [
            'challenge' => $challenge,
            'rpId' => $this->get_relying_party_id(),
            'allowCredentials' => [],
            'userVerification' => $this->get_login_user_verification(),
            'timeout' => $this->get_login_timeout(),
        ];

        return apply_filters('secure_passkeys_web_authn_login_options', $options);
    }
}
