<?php

namespace Secure_Passkeys\Ajax;

use Secure_Passkeys\Models\Secure_Passkeys_Challenge;
use Secure_Passkeys\Models\Secure_Passkeys_Log;
use Secure_Passkeys\Models\Secure_Passkeys_WebAuthn;
use Secure_Passkeys\Utils\Secure_Passkeys_Helper;
use Secure_Passkeys\Utils\Secure_Passkeys_Webauthn_Helper;

defined('ABSPATH') || exit;

class Secure_Passkeys_Adminarea_Ajax
{
    public function __construct()
    {
        add_action('wp_ajax_secure_passkeys_adminarea_overview', [$this, 'overview']);
        add_action('wp_ajax_secure_passkeys_adminarea_filter_users', [$this, 'filter_users']);
        add_action('wp_ajax_secure_passkeys_adminarea_passkeys_list', [$this, 'passkeys_list']);
        add_action('wp_ajax_secure_passkeys_adminarea_delete_passkey', [$this, 'delete_passkey']);
        add_action('wp_ajax_secure_passkeys_adminarea_activate_deactivate_passkey', [$this, 'activate_deactivate_passkey']);
        add_action('wp_ajax_secure_passkeys_adminarea_get_profile_registered_passkeys_list', [$this, 'get_profile_registered_passkeys_list']);
        add_action('wp_ajax_secure_passkeys_adminarea_activity_list', [$this, 'activity_list']);
    }

    public function overview()
    {
        $this->throw_error_if_invalid_request();

        $webauthn = new Secure_Passkeys_WebAuthn();
        $challenge = new Secure_Passkeys_Challenge();
        $log = new Secure_Passkeys_Log();

        wp_send_json_success([
            'users_count' => $webauthn->get_unique_usesr_count(),
            'passkeys_count' => $webauthn->get_count(),
            'challenges_count' => $challenge->get_count(),
            'logs_count' => $log->get_count(),
            'authenticators' => $webauthn->get_authenticators(),
            'last_login_activity' => $log->get_last_login_activity(),
        ]);
    }

    public function filter_users()
    {
        $this->throw_error_if_invalid_request();

        $keyword = trim(sanitize_text_field(wp_unslash($_POST['keyword'] ?? '')));
        $model = trim(sanitize_text_field(wp_unslash($_POST['model'] ?? null)));

        if (empty($keyword)) {
            return wp_send_json_success(['results' => []]);
        }

        $results = Secure_Passkeys_Helper::filter_user_by($keyword, $model);

        return wp_send_json_success(['results' =>  $results]);
    }

    /**
     * Passkeys list.
     */
    public function passkeys_list()
    {
        $this->throw_error_if_invalid_request();

        $filters = map_deep(wp_unslash($_POST['filters'] ?? []), 'sanitize_text_field');

        $records = (new Secure_Passkeys_WebAuthn())->get_all_paginate([
            'id', 'user_id', 'is_active', 'security_key_name', 'aaguid', 'last_used_at', 'created_at'
        ], 20, $filters);

        array_map(function ($record) {
            $record->is_active = intval($record->is_active);
            $record->aaguid = Secure_Passkeys_Webauthn_Helper::get_authenticator($record->aaguid);
            $record->user = Secure_Passkeys_Helper::get_user_object_by_id(intval($record->user_id));
            $record->last_used_on = Secure_Passkeys_Helper::get_datetime_from_now($record->last_used_at);
        }, $records['records'] ?? []);

        $records['authenticators'] = (new Secure_Passkeys_WebAuthn())->get_authenticators();

        wp_send_json_success($records);
    }

    /**
     * Delete passkey.
     */
    public function delete_passkey()
    {
        $this->throw_error_if_invalid_request();

        $user_id = intval($_POST['user_id'] ?? 0);

        $this->throw_error_if_invalid_access_to_action($user_id);

        $id = intval($_POST['id'] ?? 0);
        $admin_id = get_current_user_id();

        $model = new Secure_Passkeys_WebAuthn();
        $record = $model->first($id);
        if (is_null($record) || ($user_id && $user_id !== intval($record->user_id))) {
            wp_send_json_error([
                'message' => __('The passkey cannot be deleted', 'secure-passkeys')
            ]);
        }

        $deleted = $model->delete(['id' => $id]);
        if (!$deleted) {
            wp_send_json_error([
                'message' => __('The passkey cannot be deleted', 'secure-passkeys')
            ]);
        }

        $is_owner = $user_id === $admin_id;
        $log_type = 'delete';

        if ($is_owner) {
            $admin_id = null;
            $log_type = 'remove';
        }

        (new Secure_Passkeys_Log())->add_record($log_type, $record->user_id, $record->security_key_name, $admin_id, $record->aaguid);

        wp_send_json_success([
            'message' => __('The passkey deleted successfully', 'secure-passkeys'),
        ]);
    }

    /**
     * Get profile registered passkeys list.
     */
    public function get_profile_registered_passkeys_list()
    {
        $this->throw_error_if_invalid_request();

        $user_id = intval($_POST['user_id'] ?? 0);

        $this->throw_error_if_invalid_access_to_action($user_id);

        $this->throw_error_if_has_not_permission($user_id);

        $records = (new Secure_Passkeys_WebAuthn())->get_all_by_user_id($user_id);

        array_map(function ($record) {
            $record->aaguid = Secure_Passkeys_Webauthn_Helper::get_authenticator($record->aaguid);
            $record->is_active = intval($record->is_active);
            $record->last_used_on = Secure_Passkeys_Helper::get_datetime_from_now($record->last_used_at);
        }, $records);

        wp_send_json_success($records);
    }

    /**
     * Activate or deactivate passkey.
     */
    public function activate_deactivate_passkey()
    {
        $this->throw_error_if_invalid_request();

        $admin_id = get_current_user_id();
        $id = intval($_POST['id'] ?? 0);
        $procedure = sanitize_text_field(wp_unslash($_POST['procedure'] ?? ''));

        if (!in_array($procedure, ['activate', 'deactivate'])) {
            wp_send_json_error([
                'message' => __('Invalid procedure', 'secure-passkeys')
            ]);
        }

        $is_active_value = $procedure === 'activate';

        $model = new Secure_Passkeys_WebAuthn();
        $record = $model->first($id);
        if (is_null($record)) {
            wp_send_json_error([
                'message' => __('A passkey cannot be updated', 'secure-passkeys')
            ]);
        }

        $user_id = intval($record->user_id);

        $this->throw_error_if_invalid_access_to_action($user_id);

        $update = $model->update_is_active($id, $is_active_value);
        if (!$update) {
            wp_send_json_error([
                'message' => __('A passkey cannot be updated', 'secure-passkeys')
            ]);
        }

        $log_type = $is_active_value ? 'activate' : 'deactivate';

        (new Secure_Passkeys_Log())->add_record($log_type, $user_id, $record->security_key_name, $admin_id, $record->aaguid, $record->id);

        $message = $is_active_value ? __('The passkey activated successfully', 'secure-passkeys')
        : __('The passkey deactivated successfully', 'secure-passkeys');

        wp_send_json_success([
            'message' => $message
        ]);
    }

    /**
     * Activity list.
     */
    public function activity_list()
    {
        $this->throw_error_if_invalid_request();

        $filters = map_deep(wp_unslash($_POST['filters'] ?? []), 'sanitize_text_field');

        $records = (new Secure_Passkeys_Log())->get_all_paginate([
            'id', 'user_id', 'security_key_name', 'aaguid', 'admin_id', 'log_type', 'ip_address', 'created_at'
        ], 20, $filters);

        array_map(function ($record) {
            $aaguid_friendly_name = Secure_Passkeys_Webauthn_Helper::get_friendly_name($record->aaguid);
            $record->localized_log_type = Secure_Passkeys_Log::get_localized_log_type($record->log_type);
            $record->description = Secure_Passkeys_Log::get_log_line($record->log_type, $record->security_key_name, $aaguid_friendly_name);
            $record->user = Secure_Passkeys_Helper::get_user_object_by_id(intval($record->user_id));
            $record->admin = Secure_Passkeys_Helper::get_user_object_by_id(intval($record->admin_id));
        }, $records['records'] ?? []);

        wp_send_json_success($records);
    }

    private function throw_error_if_invalid_request()
    {
        $is_admin_requst = Secure_Passkeys_Helper::check_is_admin_request();

        $message = '';
        $missing_nonce = false;

        if (!$is_admin_requst || !wp_doing_ajax()) {
            $message = __('You do not have permission to make this request.', 'secure-passkeys');
        } elseif ('POST' !== strtoupper(sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'] ?? '')))) {
            $message = __('The request method must be POST.', 'secure-passkeys');
        } elseif (!Secure_Passkeys_Helper::verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')))) {
            $message = __('Token mismatch, please refresh the page.', 'secure-passkeys');
            $missing_nonce = true;
        }

        if (!empty($message)) {
            wp_send_json_error(['missing_nonce' => $missing_nonce, 'message' => $message]);
        }
    }

    private function throw_error_if_invalid_access_to_action(int $user_id)
    {
        if ($user_id === 0) {
            return;
        }

        $should_check = apply_filters('secure_passkeys_adminarea_should_check_access', true);

        $has_permission = apply_filters('secure_passkeys_adminarea_check_permission', current_user_can('edit_user', $user_id));

        if ($should_check && !$has_permission) {
            wp_send_json_error([
                'message' => __('You do not have permission to make this request.', 'secure-passkeys')
            ]);
        }
    }

    private function throw_error_if_has_not_permission($user_id)
    {
        if (Secure_Passkeys_Helper::is_user_in_excluded_roles($user_id)) {
            wp_send_json_error(__('You are not allowed to make this request.', 'secure-passkeys'));
        }
    }
}
