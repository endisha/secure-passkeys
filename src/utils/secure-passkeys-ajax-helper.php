<?php

namespace Secure_Passkeys\Utils;

defined('ABSPATH') || exit;

class Secure_Passkeys_Ajax_Helper
{
    public static function validate_adminarea_ajax_request()
    {
        $message = '';
        $missing_nonce = false;

        if (!wp_doing_ajax()) {
            $message = __('You do not have permission to make this request.', 'secure-passkeys');
        } elseif ('POST' !== strtoupper(sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'] ?? '')))) {
            $message = __('The request method must be POST.', 'secure-passkeys');
        } elseif (!Secure_Passkeys_Helper::verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')))) {
            $message = __('Token mismatch, please refresh the page.', 'secure-passkeys');
            $missing_nonce = true;
        }

        $message = apply_filters('secure_passkeys_adminarea_invalid_request_error_message', $message);

        if (!empty($message)) {
            wp_send_json_error(['missing_nonce' => $missing_nonce, 'message' => $message]);
        }
    }

    public static function validate_adminarea_user_permission(int $user_id)
    {
        if (Secure_Passkeys_Helper::is_user_in_excluded_roles($user_id)) {
            $message = __('You are not allowed to make this request.', 'secure-passkeys');
            $message = apply_filters('secure_passkeys_adminarea_invalid_permission_error_message', $message);

            wp_send_json_error($message);
        }
    }

    public static function ensure_user_can_perform_action(int $user_id)
    {
        $should_check = apply_filters('secure_passkeys_adminarea_should_check_access', true);

        $has_permission = apply_filters('secure_passkeys_adminarea_check_permission', current_user_can('edit_user', $user_id));

        if ($should_check && !$has_permission) {
            $message = __('You do not have permission to make this request.', 'secure-passkeys');
            $message = apply_filters('secure_passkeys_adminarea_invalid_access_error_message', $message);

            wp_send_json_error(['message' => $message]);
        }
    }

    public static function ensure_has_admin_access()
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

    public static function validate_adminarea_settings_ajax_request()
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

    public static function validate_frontend_ajax_request()
    {
        $is_admin_requst = Secure_Passkeys_Helper::check_is_admin_request();

        switch_to_locale($is_admin_requst ? get_user_locale() : get_locale());

        $message = '';

        if (!wp_doing_ajax()) {
            $message = __('You are not allowed to make this request.', 'secure-passkeys');
        } elseif ('POST' !== strtoupper(sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'] ?? '')))) {
            $message = __('The request method must be POST.', 'secure-passkeys');
        } elseif (!Secure_Passkeys_Helper::verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')))) {
            $message = __('Token mismatch, please refresh the page.', 'secure-passkeys');
        }

        $message = apply_filters('secure_passkeys_frontend_invalid_request_error_message', $message);

        if (!empty($message)) {
            wp_send_json_error($message);
        }
    }

    public static function validate_frontend_user_permission()
    {
        if (Secure_Passkeys_Helper::is_user_in_excluded_roles(get_current_user_id())) {
            $message = __('You are not allowed to make this request.', 'secure-passkeys');
            $message = apply_filters('secure_passkeys_frontend_invalid_permission_error_message', $message);

            wp_send_json_error($message);
        }
    }
}
