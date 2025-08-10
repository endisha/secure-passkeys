<?php

namespace Secure_Passkeys\Utils;

defined('ABSPATH') || exit;

class Secure_Passkeys_Settings_Helper
{
    public static function get_callback_action($settings_key)
    {
        $action = [];

        if (!empty($settings_key)) {
            if (strpos($settings_key, 'secure_passkeys_get') !== false) {
                $action['hook_name'] = 'wp_ajax_'.$settings_key;
                $action['callback'] = 'get';
            } elseif (strpos($settings_key, 'secure_passkeys_update') !== false) {
                $action['hook_name'] = 'wp_ajax_'.$settings_key;
                $action['callback'] = 'save';
            }
        }

        return $action;
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
