<?php

namespace Secure_Passkeys\Ajax;

use Secure_Passkeys\Settings\Secure_Passkeys_Factory;
use Secure_Passkeys\Utils\Secure_Passkeys_Helper;

defined('ABSPATH') || exit;

class Secure_Passkeys_Adminarea_Settings_Ajax
{
    public function __construct()
    {
        $callback_action = $this->get_callback_action();

        if (!empty($callback_action)) {
            add_action($callback_action['hook_name'], $callback_action['callback']);
        }
    }

    public function get()
    {
        $this->throw_error_if_invalid_request();

        $this->throw_error_if_invalid_access_to_action();

        $settings_key = $this->get_settings_key();

        $settings_component = Secure_Passkeys_Factory::create($settings_key);

        if (is_wp_error($settings_component)) {
            wp_send_json_error($settings_component);
        }

        wp_send_json_success([
            'data' => $this->return_settings_data($settings_component->get()),
            'defaults' => $settings_component->defaults()
        ]);
    }

    public function save()
    {
        $this->throw_error_if_invalid_request();

        $this->throw_error_if_invalid_access_to_action();

        $settings_key = $this->get_settings_key();

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
            'data' => $this->return_settings_data(array_keys($settings)),
            'defaults' => $settings_component->defaults()
        ]);
    }

    private function get_settings_key()
    {
        $settings_key = '';

        $settings_action = sanitize_key($_POST['action'] ?? '');

        if (!empty($settings_action) && preg_match('/^secure_passkeys_(get|update)_([a-zA-Z0-9_]+)_settings$/', $settings_action, $matches)) {
            $settings_key = $matches[2];
        }

        return sanitize_key($settings_key);
    }

    private function get_callback_action()
    {
        $action = [];

        if (!empty(sanitize_key($_POST['action'] ?? ''))) {
            $settings_key = sanitize_key($_POST['action'] ?? '');
            if (strpos($settings_key, 'secure_passkeys_get') !== false) {
                $action['hook_name'] = 'wp_ajax_'.$settings_key;
                $action['callback'] = [$this, 'get'];
            } elseif (strpos($settings_key, 'secure_passkeys_update') !== false) {
                $action['hook_name'] = 'wp_ajax_'.$settings_key;
                $action['callback'] = [$this, 'save'];
            }
        }

        return $action;
    }

    private function return_settings_data(array $options = [])
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

    private function throw_error_if_invalid_request()
    {
        $is_admin_requst = strpos(sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER'] ?? '')), admin_url()) !== false;

        $message = '';

        if (!$is_admin_requst || !wp_doing_ajax()) {
            $message = __('You do not have permission to make this request.', 'secure-passkeys');
        } elseif ('POST' !== strtoupper(sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'] ?? '')))) {
            $message = __('The request method must be POST.', 'secure-passkeys');
        } elseif (!Secure_Passkeys_Helper::verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')))) {
            $message = __('Token mismatch, please refresh the page.', 'secure-passkeys');
        }

        if (!empty($message)) {
            wp_send_json_error(['missing_nonce' => true, 'message' => $message]);
        }
    }

    private function throw_error_if_invalid_access_to_action()
    {
        $user_id = get_current_user_id();

        $should_check = apply_filters('secure_passkeys_adminarea_should_check_access', true);

        $capability = apply_filters('secure_passkeys_admin_menu_capability', 'manage_options');

        $has_permission = apply_filters('secure_passkeys_adminarea_check_settings_permission', current_user_can($capability, $user_id));

        if ($should_check && !$has_permission) {
            wp_send_json_error([
                'message' => __('You do not have permission to make this request.', 'secure-passkeys')
            ]);
        }
    }
}
