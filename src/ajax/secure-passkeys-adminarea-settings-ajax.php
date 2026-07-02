<?php

namespace Secure_Passkeys\Ajax;

use Secure_Passkeys\Settings\Secure_Passkeys_Factory;
use Secure_Passkeys\Utils\Secure_Passkeys_Ajax_Helper;
use Secure_Passkeys\Utils\Secure_Passkeys_Settings_Helper;

defined('ABSPATH') || exit;

class Secure_Passkeys_Adminarea_Settings_Ajax
{
    public function __construct()
    {
        $allowed_actions = Secure_Passkeys_Settings_Helper::get_allowed_actions();
        foreach ($allowed_actions as $action => $callback) {
            add_action('wp_ajax_' . $action, [$this, $callback]);
        }
    }

    public function get()
    {
        Secure_Passkeys_Ajax_Helper::validate_adminarea_settings_ajax_request();

        Secure_Passkeys_Ajax_Helper::ensure_has_admin_access();

        $settings_action = sanitize_key($_POST['action'] ?? '');
        $settings_key = Secure_Passkeys_Settings_Helper::get_settings_key($settings_action);
        $settings_component = Secure_Passkeys_Factory::create($settings_key);

        if (is_wp_error($settings_component)) {
            wp_send_json_error($settings_component);
        }

        wp_send_json_success([
            'data' => Secure_Passkeys_Settings_Helper::return_settings_data($settings_component->get()),
            'defaults' => $settings_component->defaults()
        ]);
    }

    public function save()
    {
        Secure_Passkeys_Ajax_Helper::validate_adminarea_settings_ajax_request();

        Secure_Passkeys_Ajax_Helper::ensure_has_admin_access();

        $settings_action = sanitize_key($_POST['action'] ?? '');
        $settings_key = Secure_Passkeys_Settings_Helper::get_settings_key($settings_action);
        $settings_component = Secure_Passkeys_Factory::create($settings_key);

        if (is_wp_error($settings_component)) {
            wp_send_json_error(['message' => $settings_component->get_error_message()]);
        }

        $settings = $settings_component->save();

        if (is_wp_error($settings)) {
            wp_send_json_error(['message' => $settings->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Settings updated successfully.', 'secure-passkeys'),
            'data' => Secure_Passkeys_Settings_Helper::return_settings_data(array_keys($settings)),
            'defaults' => $settings_component->defaults()
        ]);
    }
}
