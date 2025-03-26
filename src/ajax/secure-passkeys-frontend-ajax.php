<?php

namespace Secure_Passkeys\Ajax;

use Exception;
use Secure_Passkeys\Actions\Secure_Passkeys_Web_Authn_Enable_Action;
use Secure_Passkeys\Actions\Secure_Passkeys_Web_Authn_Enable_Options_Action;
use Secure_Passkeys\Actions\Secure_Passkeys_Web_Authn_Login_Action;
use Secure_Passkeys\Actions\Secure_Passkeys_Web_Authn_Login_Options_Action;
use Secure_Passkeys\Actions\Secure_Passkeys_Web_Authn_Remove_Action;
use Secure_Passkeys\Actions\Secure_Passkeys_Web_Authn_Sign_In_Action;
use Secure_Passkeys\Exceptions\Secure_Passkeys_Web_Authn_Already_Registered_Exception;
use Secure_Passkeys\Exceptions\Secure_Passkeys_Web_Authn_Reach_Maximum_Credentials_Exception;
use Secure_Passkeys\Models\Secure_Passkeys_Challenge;
use Secure_Passkeys\Models\Secure_Passkeys_Log;
use Secure_Passkeys\Models\Secure_Passkeys_WebAuthn;
use Secure_Passkeys\Utils\Secure_Passkeys_Helper;
use Secure_Passkeys\Utils\Secure_Passkeys_Webauthn_Helper;

defined('ABSPATH') || exit;

class Secure_Passkeys_Frontend_Ajax
{
    public function __construct()
    {
        add_action('wp_ajax_nopriv_secure_passkeys_frontend_get_login_options', [$this, 'get_login_options'], 100);
        add_action('wp_ajax_nopriv_secure_passkeys_frontend_login', [$this, 'login'], 100);
        add_action('wp_ajax_secure_passkeys_frontend_get_registered_passkeys_list', [$this, 'get_registered_passkeys_list'], 100);
        add_action('wp_ajax_secure_passkeys_frontend_get_register_options', [$this, 'get_register_options'], 100);
        add_action('wp_ajax_secure_passkeys_frontend_register_passkey', [$this, 'register_passkey'], 100);
        add_action('wp_ajax_secure_passkeys_frontend_remove_passkey', [$this, 'remove_passkey'], 100);
    }

    /**
     * Get login options
     */
    public function get_login_options()
    {
        $this->throw_error_if_invalid_request();

        $challenge = (new Secure_Passkeys_Challenge())->generate_challenge('authentication');

        $options = (new Secure_Passkeys_Web_Authn_Login_Options_Action())->execute($challenge);

        wp_send_json_success($options);
    }

    /**
     * Login
     */
    public function login()
    {
        $this->throw_error_if_invalid_request();

        $params = [
            'rawId' => sanitize_text_field(wp_unslash($_POST['rawId'] ?? '')),
            'response' => map_deep(wp_unslash($_POST['response'] ?? []), 'sanitize_text_field')
        ];

        $challenge = sanitize_text_field(wp_unslash($_POST['challenge'] ?? ''));

        try {
            (new Secure_Passkeys_Challenge())->verify_challenge_or_throw_exception($challenge, 'authentication');

            $data = (new Secure_Passkeys_Web_Authn_Login_Action())->execute($challenge, $params);

            (new Secure_Passkeys_WebAuthn())->touch_last_used($data->id);

            (new Secure_Passkeys_Challenge())->mark_as_used_challenge($challenge);

            (new Secure_Passkeys_Web_Authn_Sign_In_Action())->execute($data->user_id);

        } catch (Exception $e) {
            wp_send_json_error(__('Passkey authentication failed. Please try again.', 'secure-passkeys'));
        }

        (new Secure_Passkeys_Log())->add_record('login', $data->user_id, $data->security_key_name, null, $data->aaguid, $data->id);

        $redirect_to = Secure_Passkeys_Helper::login_redirect_to();

        wp_send_json_success([
            'redirect_url' => apply_filters('secure_passkeys_login_redirect_url', $redirect_to),
        ]);
    }

    /**
     * Get registered passkeys list
     */
    public function get_registered_passkeys_list()
    {
        $this->throw_error_if_invalid_request();

        $this->throw_error_if_has_not_permission();

        $user_id = get_current_user_id();

        $records = (new Secure_Passkeys_WebAuthn())->get_all_by_user_id($user_id);

        array_map(function ($record) {
            $record->aaguid = Secure_Passkeys_Webauthn_Helper::get_authenticator($record->aaguid);
            $record->is_active = intval($record->is_active);
            $record->last_used_on = Secure_Passkeys_Helper::get_datetime_from_now($record->last_used_at);
        }, $records);

        $records = apply_filters('secure_passkeys_frontend_response_registered_passkeys_list', $records, $user_id);

        wp_send_json_success($records);
    }

    /**
     * Get register options
     */
    public function get_register_options()
    {
        $this->throw_error_if_invalid_request();

        $this->throw_error_if_has_not_permission();

        $user_id = get_current_user_id();

        $challenge = (new Secure_Passkeys_Challenge())->generate_challenge('registration', $user_id);

        $options = (new Secure_Passkeys_Web_Authn_Enable_Options_Action())->execute($user_id, $challenge);

        wp_send_json_success($options);
    }

    /**
     * Register passkey
     */
    public function register_passkey()
    {
        $this->throw_error_if_invalid_request();

        $this->throw_error_if_has_not_permission();

        $params = [
            'rawId' => sanitize_text_field(wp_unslash($_POST['rawId'] ?? '')),
            'response' => map_deep(wp_unslash($_POST['response'] ?? []), 'sanitize_text_field')
        ];
        $user_id = get_current_user_id();
        $challenge = sanitize_text_field(wp_unslash($_POST['challenge'] ?? ''));
        $security_key_name = sanitize_text_field(wp_unslash($_POST['security_key_name'] ?? ''));

        try {
            (new Secure_Passkeys_Challenge())->verify_challenge_or_throw_exception($challenge, 'registration');

            $data = (new Secure_Passkeys_Web_Authn_Enable_Action())->execute($user_id, $challenge, $params, $security_key_name);
        } catch (Secure_Passkeys_Web_Authn_Reach_Maximum_Credentials_Exception $e) {
            wp_send_json_error(__('The maximum number of registered passkeys has been reached.', 'secure-passkeys'));
        } catch (Secure_Passkeys_Web_Authn_Already_Registered_Exception  $e) {
            wp_send_json_error(__('This passkey is already registered. Please use a different one.', 'secure-passkeys'));
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to register the passkey. Please try again later.', 'secure-passkeys'));
        }

        if (empty($security_key_name)) {
            wp_send_json_error('EMPTY_SECURITY_KEY_NAME');
        } elseif (mb_strlen($security_key_name) < 3) {
            wp_send_json_error(__('Security key name must be at least 3 characters long.', 'secure-passkeys'));
        } elseif (mb_strlen($security_key_name) > 30) {
            wp_send_json_error(__('Security key name must be at most 30 characters long.', 'secure-passkeys'));
        } elseif (!preg_match('/^[A-Za-z0-9\s\-_]+$/', $security_key_name)) {
            wp_send_json_error(__('Please use only letters, numbers, spaces, hyphens, or underscores.', 'secure-passkeys'));
        }

        try {
            $wenauthn = new Secure_Passkeys_WebAuthn();
            $wenauthn->insert(array_merge($data, [
                'security_key_name' => $security_key_name,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]));
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to register the passkey. Please try again later.', 'secure-passkeys'));
        }

        (new Secure_Passkeys_Challenge())->mark_as_used_challenge($challenge);

        $webauthn_id = $wenauthn->get_last_inserted_id();
        $aaguid = $data['aaguid'];

        (new Secure_Passkeys_Log())->add_record('register', $user_id, $security_key_name, null, $aaguid, $webauthn_id);

        wp_send_json_success([]);
    }

    /**
     * Remove passkey
     */
    public function remove_passkey(): void
    {
        $this->throw_error_if_invalid_request();

        $this->throw_error_if_has_not_permission();

        $id = intval($_POST['id'] ?? 0);
        $user_id = get_current_user_id();

        $passkey = (new Secure_Passkeys_WebAuthn())->first($id);

        try {
            $remove = (new Secure_Passkeys_Web_Authn_Remove_Action())->execute($user_id, $id);
        } catch (Exception $e) {
            $remove = false;
        }

        if (!$remove) {
            wp_send_json_error(__('You do not have permission to remove this passkey.', 'secure-passkeys'));
        }

        (new Secure_Passkeys_Log())->add_record('remove', $user_id, $passkey->security_key_name, null, $passkey->aaguid);

        wp_send_json_success([]);
    }

    private function throw_error_if_invalid_request()
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

        if (!empty($message)) {
            wp_send_json_error($message);
        }
    }

    private function throw_error_if_has_not_permission()
    {
        if (Secure_Passkeys_Helper::is_user_in_excluded_roles(get_current_user_id())) {
            wp_send_json_error(__('You are not allowed to make this request.', 'secure-passkeys'));
        }
    }
}
