<?php

namespace Secure_Passkeys\Models;

use Secure_Passkeys\Core\Secure_Passkeys_Model;
use Secure_Passkeys\Utils\Secure_Passkeys_Helper;
use Secure_Passkeys\Utils\Secure_Passkeys_Webauthn_Helper;

defined('ABSPATH') || exit;

class Secure_Passkeys_Log extends Secure_Passkeys_Model
{
    protected $table = 'secure_passkeys_logs';

    public function paginate_filters()
    {
        return[
            'user_id' => 'int',
            'log_type' => 'string',
            'action_by' => 'action_by',
            'ip_address' => 'string',
            'created_at' => 'date_range',
        ];
    }

    public function paginate_filters_custom_action_by($value)
    {
        if (!in_array($value, ['admin', 'user'])) {
            return [];
        }

        if ($value === 'admin') {
            $query = '`admin_id` != 0';
        } elseif ($value === 'user') {
            $query = '`admin_id` IS NULL';
        }

        return [$query, []];
    }

    public function add_record(
        string $log_type,
        int $user_id,
        ?string $security_key_name = null,
        ?int $admin_id = null,
        ?string $aaguid = null,
        ?int $webauthn_id = null,
        ?string $ip_address = null
    ) {
        if (is_null($ip_address)) {
            $ip_address = Secure_Passkeys_Helper::get_ip_address();
        }

        $data = [
            'log_type' => $log_type,
            'security_key_name' => $security_key_name,
            'aaguid' => $aaguid,
            'user_id' => $user_id,
            'admin_id' => $admin_id,
            'webauthn_id' => $webauthn_id,
            'ip_address' => $ip_address,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        return $this->insert($data);
    }

    public static function get_localized_log_type(string $log_type)
    {
        $log_types = apply_filters('secure_passkeys_localized_log_types', [
            'login' => __('Login', 'secure-passkeys'),
            'register' => __('Register', 'secure-passkeys'),
            'remove' => __('Remove', 'secure-passkeys'),
            'delete' => __('Delete', 'secure-passkeys'),
            'activate' => __('Activate', 'secure-passkeys'),
            'deactivate' => __('Deactivate', 'secure-passkeys'),
        ], $log_type);


        if (! array_key_exists($log_type, $log_types)) {
            return $log_type;
        }

        return $log_types[$log_type];
    }

    public static function get_log_line(string $log_type, string $security_key_name, ?string $aaguid_friendly_name)
    {
        $lines = apply_filters('secure_passkeys_log_lines', [
            /* translators: %1$s: Passkey name, %2$s: Authenticator name */
            'login' => __('User logged in using the passkey (%1$s) with the authenticator (%2$s).', 'secure-passkeys'),
            /* translators: %1$s: Passkey name, %2$s: Authenticator name */
            'register' => __('Passkey registered successfully with the name (%1$s) using the authenticator (%2$s).', 'secure-passkeys'),
            /* translators: %1$s: Passkey name, %2$s: Authenticator name */
            'remove' => __('Passkey (%1$s) associated with the authenticator (%2$s) was removed.', 'secure-passkeys'),
            /* translators: %1$s: Passkey name, %2$s: Authenticator name */
            'delete' => __('Passkey (%1$s) associated with the authenticator (%2$s) was deleted by an administrator.', 'secure-passkeys'),
            /* translators: %1$s: Passkey name, %2$s: Authenticator name */
            'activate' => __('Passkey (%1$s) associated with the authenticator (%2$s) was activated by an administrator.', 'secure-passkeys'),
            /* translators: %1$s: Passkey name, %2$s: Authenticator name */
            'deactivate' => __('Passkey (%1$s) associated with the authenticator (%2$s) was deactivated by an administrator.', 'secure-passkeys'),
        ], $log_type, $security_key_name, $aaguid_friendly_name);

        if (! array_key_exists($log_type, $lines)) {
            return '';
        }

        return sprintf($lines[$log_type], $security_key_name, $aaguid_friendly_name);
    }

    public function get_last_login_activity(int $limit = 15)
    {
        $results = $this->db->get_results(
            $this->db->prepare("
                SELECT `user_id`, `aaguid`, `ip_address`, `created_at`
                FROM $this->table
                WHERE `log_type` = %s
                ORDER BY `created_at` DESC
                LIMIT %d
            ", 'login', $limit)
        );

        array_map(function ($record) {
            $record->aaguid = Secure_Passkeys_Webauthn_Helper::get_authenticator($record->aaguid);
            $record->user = Secure_Passkeys_Helper::get_user_object_by_id(intval($record->user_id));
            $record->login_on = Secure_Passkeys_Helper::get_datetime_from_now($record->created_at);
        }, $results ?? []);

        return $results;
    }

    public function get_count()
    {
        return (int) $this->db->get_var(
            $this->db->prepare("
                SELECT COUNT(*) as `count`
                FROM {$this->table}
            ")
        );
    }
}
