<?php

namespace Secure_Passkeys\Ajax;

use Secure_Passkeys\Settings\Secure_Passkeys_Factory;
use Secure_Passkeys\Utils\Secure_Passkeys_Helper;
use Secure_Passkeys\Utils\Secure_Passkeys_Settings_Helper;

defined('ABSPATH') || exit;

class Secure_Passkeys_Adminarea_Settings_Ajax
{
    protected $settings_factory;

    public function __construct()
    {
        $settings_action = sanitize_key($_POST['action'] ?? '');
        $callback_action = Secure_Passkeys_Settings_Helper::get_callback_action($settings_action);

        if (!empty($callback_action)) {
            add_action($callback_action['hook_name'], [$this, $callback_action['callback']]);
        }

        $settings_key = Secure_Passkeys_Settings_Helper::get_settings_key($settings_action);
        $this->settings_factory = Secure_Passkeys_Factory::create($settings_key);
    }

    public function get()
    {
        $this->throw_error_if_invalid_request();

        $this->throw_error_if_invalid_access_to_action();

        $settings_component = $this->settings_factory;

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
        $this->throw_error_if_invalid_request();

        $this->throw_error_if_invalid_access_to_action();

        $settings_component = $this->settings_factory;

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

    private function throw_error_if_invalid_request()
    {
        $message = '';

        if (!wp_doing_ajax()) {
            $message = __('You do not have permission to make this request.', 'secure-passkeys');
        } elseif ('POST' !== strtoupper(sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'] ?? '')))) {
            $message = __('The request method must be POST.', 'secure-passkeys');
        } elseif (!Secure_Passkeys_Helper::verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')))) {
            $message = __('Token mismatch, please refresh the page.', 'secure-passkeys');
        }

        $message = apply_filters('secure_passkeys_adminarea_invalid_request_error_message', $message);

        if (!empty($message)) {
            wp_send_json_error(['missing_nonce' => true, 'message' => $message]);
        }
    }

    private function throw_error_if_invalid_access_to_action()
    {
        $user_id = get_current_user_id();

        $should_check = apply_filters('secure_passkeys_adminarea_should_check_access', true);

        $capability = apply_filters('secure_passkeys_adminarea_menu_capability', 'manage_options');

        $has_permission = apply_filters('secure_passkeys_adminarea_check_settings_permission', current_user_can($capability, $user_id));

        if ($should_check && !$has_permission) {
            $message = __('You do not have permission to make this request.', 'secure-passkeys');
            $message = apply_filters('secure_passkeys_adminarea_invalid_access_error_message', $message);

            wp_send_json_error(['message' => $message]);
        }
    }
}
