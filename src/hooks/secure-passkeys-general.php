<?php

namespace Secure_Passkeys\Hooks;

use Secure_Passkeys\Models\Secure_Passkeys_WebAuthn;
use Secure_Passkeys\Utils\Secure_Passkeys_Adminarea_Helper;
use Secure_Passkeys\Utils\Secure_Passkeys_Helper;

defined('ABSPATH') || exit;

class Secure_Passkeys_General
{
    public function __construct()
    {
        add_action('deleted_user', [$this, 'delete_passkey']);
        add_filter('manage_users_columns', [$this, 'users_passkey_column']);
        add_action('manage_users_custom_column', [$this, 'users_passkey_value'], 10, 3);
        add_action('admin_notices', [$this, 'show_enable_passkeys_notice']);
    }

    public function delete_passkey($user_id)
    {
        (new Secure_Passkeys_WebAuthn())->delete(['user_id' => $user_id]);
    }

    public function users_passkey_column($columns)
    {
        if (Secure_Passkeys_Helper::get_option('display_passkey_users_list_enabled', 1)) {
            $columns['secure_passkeys'] = __('Passkeys', 'secure-passkeys');
        }

        return $columns;
    }

    public function users_passkey_value($value, $column_name, $user_id)
    {
        if (Secure_Passkeys_Helper::get_option('display_passkey_users_list_enabled', 1)) {
            if ('secure_passkeys' === $column_name) {
                $passkeys = (new Secure_Passkeys_WebAuthn())->get_counts_by_user_id($user_id);

                return Secure_Passkeys_Adminarea_Helper::display_passkeys_in_users_list($passkeys);
            }
        }

        return $value;
    }

    public function show_enable_passkeys_notice()
    {
        if (!is_user_logged_in()) {
            return;
        }

        if (!Secure_Passkeys_Helper::is_show_enable_passkeys_notice_enabled()) {
            return;
        }

        $user_id = get_current_user_id();

        if (Secure_Passkeys_Helper::is_user_in_excluded_roles($user_id)) {
            return;
        }

        $passkeys_count = (new Secure_Passkeys_WebAuthn())->get_count_by_user_id($user_id);

        if ($passkeys_count > 0) {
            return;
        }

        echo Secure_Passkeys_Adminarea_Helper::show_enable_passkeys_notice();
    }
}
