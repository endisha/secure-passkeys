<?php

namespace Secure_Passkeys\Settings;

use Secure_Passkeys\Utils\Secure_Passkeys_Frontend_Helper;
use Secure_Passkeys\Utils\Secure_Passkeys_Helper;
use WP_Error;

defined('ABSPATH') || exit;

class Secure_Passkeys_Display_Settings extends Secure_Passkeys_Settings
{
    public function defaults()
    {
        return [
            'themes' => Secure_Passkeys_Frontend_Helper::get_frontend_themes(),
        ];
    }

    public function get()
    {
        return [
            'display_passkey_theme',
            'display_passkey_login_wp_enabled',
            'display_passkey_login_woocommerce_enabled',
            'display_passkey_login_memberpress_enabled',
            'display_passkey_login_edd_enabled',
            'display_passkey_users_list_enabled',
            'display_passkey_edit_user_enabled',
        ];
    }

    public function save()
    {
        $display_passkey_theme = sanitize_key($_POST['settings']['display_passkey_theme'] ?? 'default');
        $display_passkey_login_wp_enabled = intval($_POST['settings']['display_passkey_login_wp_enabled'] ?? 1);
        $display_passkey_login_woocommerce_enabled = intval($_POST['settings']['display_passkey_login_woocommerce_enabled'] ?? 1);
        $display_passkey_login_memberpress_enabled = intval($_POST['settings']['display_passkey_login_memberpress_enabled'] ?? 1);
        $display_passkey_login_edd_enabled = intval($_POST['settings']['display_passkey_login_edd_enabled'] ?? 1);
        $display_passkey_users_list_enabled = intval($_POST['settings']['display_passkey_users_list_enabled'] ?? 1);
        $display_passkey_edit_user_enabled = intval($_POST['settings']['display_passkey_edit_user_enabled'] ?? 1);

        $themes = Secure_Passkeys_Frontend_Helper::get_frontend_themes();
        $themes_paths = Secure_Passkeys_Frontend_Helper::get_frontend_themes_paths();

        if (!array_key_exists($display_passkey_theme, $themes)) {
            return new WP_Error('error', __('The theme does not exist.', 'secure-passkeys'));
        } elseif (!array_key_exists($display_passkey_theme, $themes_paths)) {
            return new WP_Error('error', __('The path associated with the theme does not exist.', 'secure-passkeys'));
        }

        $data = [
            'display_passkey_theme' => $display_passkey_theme,
            'display_passkey_login_wp_enabled' => $display_passkey_login_wp_enabled,
            'display_passkey_login_woocommerce_enabled' => $display_passkey_login_woocommerce_enabled,
            'display_passkey_login_memberpress_enabled' => $display_passkey_login_memberpress_enabled,
            'display_passkey_login_edd_enabled' => $display_passkey_login_edd_enabled,
            'display_passkey_users_list_enabled' => $display_passkey_users_list_enabled,
            'display_passkey_edit_user_enabled' => $display_passkey_edit_user_enabled,
        ];

        Secure_Passkeys_Helper::update_option($data);

        return $data;
    }
}
