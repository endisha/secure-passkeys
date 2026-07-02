<?php

namespace Secure_Passkeys\Utils;

defined('ABSPATH') || exit;

class Secure_Passkeys_Settings_Helper
{
    public static function get_allowed_actions(): array
    {
        return apply_filters('secure_passkeys_adminarea_settings_allowed_actions', [
            'secure_passkeys_get_general_settings' => 'get',
            'secure_passkeys_get_display_settings' => 'get',
            'secure_passkeys_get_advanced_settings' => 'get',
            'secure_passkeys_update_general_settings' => 'save',
            'secure_passkeys_update_display_settings' => 'save',
            'secure_passkeys_update_advanced_settings' => 'save',
        ]);
    }

    public static function get_settings_key($settings_action)
    {
        $settings_key = '';

        if (!empty($settings_action) && preg_match('/^secure_passkeys_(get|update)_([a-zA-Z0-9_]+)_settings$/', $settings_action, $matches)) {
            $settings_key = $matches[2];
        }

        return sanitize_key($settings_key);
    }

    public static function return_settings_data(array $options = [])
    {
        $settings = Secure_Passkeys_Helper::get_option(null, []);

        if (!is_array($settings)) {
            $settings = [];
        }

        if (!empty($options)) {
            $settings = array_intersect_key($settings, array_flip($options));
        }

        return $settings;
    }
}
