<?php

namespace Secure_Passkeys\Settings;

use Secure_Passkeys\Utils\Secure_Passkeys_Helper;
use WP_Error;

defined('ABSPATH') || exit;

class Secure_Passkeys_General_Settings extends Secure_Passkeys_Settings
{
    public function defaults()
    {
        return [
            'roles' => Secure_Passkeys_Helper::get_users_roles()
        ];
    }

    public function get()
    {
        return [
            'registration_maximum_passkeys_enabled',
            'registration_maximum_passkeys_per_user',
            'excluded_roles_registration_login',
            'registration_timeout',
            'registration_exclude_credentials_enabled',
            'registration_user_verification_enabled',
            'login_timeout',
            'login_user_verification'
        ];
    }

    public function save()
    {
        $registration_maximum_passkeys_enabled = intval($_POST['settings']['registration_maximum_passkeys_enabled'] ?? 1);
        $registration_maximum_passkeys_per_user = intval($_POST['settings']['registration_maximum_passkeys_per_user'] ?? 3);
        $excluded_roles_registration_login = map_deep(wp_unslash($_POST['settings']['excluded_roles_registration_login'] ?? []), 'sanitize_text_field');
        $registration_timeout = intval($_POST['settings']['registration_timeout'] ?? 0);
        $registration_exclude_credentials_enabled = intval($_POST['settings']['registration_exclude_credentials_enabled'] ?? 1);
        $registration_user_verification_enabled = intval($_POST['settings']['registration_user_verification_enabled'] ?? 1);
        $login_timeout = intval($_POST['settings']['login_timeout'] ?? 0);
        $login_user_verification = sanitize_text_field(wp_unslash($_POST['settings']['login_user_verification'] ?? ''));

        if ($registration_maximum_passkeys_enabled && $registration_maximum_passkeys_per_user <= 0) {
            return new WP_Error('error', __('The maximum number of passkeys per user must be greater than 0.', 'secure-passkeys'));
        } elseif ($registration_maximum_passkeys_enabled && $registration_maximum_passkeys_per_user > 10) {
            return new WP_Error('error', __('The maximum number of passkeys must be less than 10.', 'secure-passkeys'));
        }

        if (!Secure_Passkeys_Helper::are_allowed_empty_roles($excluded_roles_registration_login)) {
            return new WP_Error('error', __('The excluded roles must be a valid role.', 'secure-passkeys'));
        }

        if (!in_array($login_user_verification, ['required', 'preferred', 'discouraged'])) {
            return new WP_Error('error', __('The login user verification must be required, preferred, or discouraged.', 'secure-passkeys'));
        }

        if (trim($registration_timeout) <= 0) {
            return new WP_Error('error', __('The registration timeout must be greater than 0.', 'secure-passkeys'));
        }

        if (trim($login_timeout) <= 0) {
            return new WP_Error('error', __('The login timeout must be greater than 0.', 'secure-passkeys'));
        }

        $data = [
            'registration_maximum_passkeys_enabled' => $registration_maximum_passkeys_enabled,
            'registration_maximum_passkeys_per_user' => $registration_maximum_passkeys_per_user,
            'excluded_roles_registration_login' => $excluded_roles_registration_login,
            'registration_timeout' => $registration_timeout,
            'registration_exclude_credentials_enabled' => $registration_exclude_credentials_enabled,
            'registration_user_verification_enabled' => $registration_user_verification_enabled,
            'login_timeout' => $login_timeout,
            'login_user_verification' => $login_user_verification,
        ];

        Secure_Passkeys_Helper::update_option($data);

        return $data;
    }
}
