<?php

namespace Secure_Passkeys\Actions;

use Secure_Passkeys\Utils\Secure_Passkeys_Helper;

defined('ABSPATH') || exit;

class Secure_Passkeys_Web_Authn_Sign_In_Action extends Secure_Passkeys_Web_Authn_Action
{
    public function execute(int $user_id): bool
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        // Block spammed or deleted accounts. These properties only exist on
        // multisite; on single-site installs they are absent, so this is a no-op.
        if (!empty($user->spam) || !empty($user->deleted)) {
            return false;
        }

        $validate = apply_filters('secure_passkeys_web_authn_validate_user_sign_in', true, $user);

        if (!$validate) {
            return false;
        }

        wp_set_current_user($user_id, $user->user_login);

        $remember = (bool) apply_filters(
            'secure_passkeys_login_remember_me',
            Secure_Passkeys_Helper::get_option('login_remember_me', 1)
        );

        wp_set_auth_cookie($user_id, $remember);

        $enabled = apply_filters('secure_passkeys_web_authn_validate_user_sign_in_enable_wp_login', true, $user);

        if ($enabled) {
            do_action('wp_login', $user->user_login, $user);
        }

        do_action('secure_passkeys_web_authn_sign_in', $user);

        return true;
    }
}
