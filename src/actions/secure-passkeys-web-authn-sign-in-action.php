<?php

namespace Secure_Passkeys\Actions;

class Secure_Passkeys_Web_Authn_Sign_In_Action extends Secure_Passkeys_Web_Authn_Action
{
    public function execute(int $user_id): bool
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        $validate = apply_filters('secure_passkeys_web_authn_validate_user_sign_in', true, $user);

        if (!$validate) {
            return false;
        }

        wp_set_current_user($user_id, $user->user_login);

        wp_set_auth_cookie($user_id, true);

        do_action('wp_login', $user->user_login, $user);

        do_action('secure_passkeys_web_authn_sign_in', $user);

        return true;
    }
}
