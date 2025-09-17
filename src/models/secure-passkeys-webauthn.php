<?php

namespace Secure_Passkeys\Models;

use Secure_Passkeys\Core\Secure_Passkeys_Model;
use Secure_Passkeys\Utils\Secure_Passkeys_Webauthn_Helper;

defined('ABSPATH') || exit;

class Secure_Passkeys_WebAuthn extends Secure_Passkeys_Model
{
    protected $table = 'secure_passkeys_webauthns';

    public function paginate_filters()
    {
        return [
            'user_id' => 'int',
            'is_active' => 'int',
            'aaguid' => 'string',
            'created_at' => 'date_range',
            'last_used_at' => 'date_range',
        ];
    }

    public function get_count()
    {
        return (int) $this->db->get_var("SELECT COUNT(*) as `count` FROM {$this->table}");
    }

    public function get_unique_usesr_count()
    {
        $users_table = $this->db->base_prefix . 'users';

        return (int) $this->db->get_var("
            SELECT COUNT(DISTINCT {$users_table}.ID) as `unique_user_count`
            FROM {$this->table}
            JOIN {$users_table} ON {$users_table}.ID = {$this->table}.user_id
        ");
    }

    public function remove_by_user_id(int $id, int $user_id)
    {
        return $this->delete(['id' => $id, 'user_id' => $user_id]);
    }

    public function get_count_by_user_id(int $id)
    {
        return $this->db->get_var(
            $this->db->prepare("
                SELECT COUNT(*) AS `count` FROM $this->table WHERE `user_id` = %d
            ", $id)
        );
    }

    public function get_authenticators()
    {
        $results = $this->db->get_results("
            SELECT `aaguid`, COUNT(*) as `count`
            FROM $this->table
            GROUP BY `aaguid`
            ORDER BY `count` DESC, `aaguid` DESC
        ");

        $authenticators = [];
        foreach ($results as $item) {
            $key = empty($item->aaguid) ? 'unknown' : $item->aaguid;
            $authenticators[$key] = [
                'name' => Secure_Passkeys_Webauthn_Helper::get_friendly_name($item->aaguid),
                'icon' => Secure_Passkeys_Webauthn_Helper::get_icon($item->aaguid),
                'count' => (int) $item->count,
            ];
        }

        return $authenticators;
    }

    public function get_counts_by_user_id(int $id)
    {
        return $this->db->get_row(
            $this->db->prepare("
                SELECT 
                    SUM(CASE WHEN `is_active` = 1 THEN 1 ELSE 0 END) AS activated,
                    SUM(CASE WHEN `is_active` = 0 THEN 1 ELSE 0 END) AS deactivated
                FROM $this->table WHERE `user_id` = %d
                GROUP BY `user_id`
            ", $id)
        );
    }

    public function get_by_user_id(int $id)
    {
        return $this->db->get_row(
            $this->db->prepare("
                SELECT * FROM $this->table WHERE `user_id` = %d
            ", $id)
        );
    }

    public function get_by_user_id_security_key_name(int $id, string $security_key_name)
    {
        return $this->db->get_row(
            $this->db->prepare("
                SELECT * FROM $this->table WHERE `user_id` = %d AND `security_key_name` = %s
            ", $id, $security_key_name)
        );
    }

    public function is_security_key_name_for_user_id(int $id, string $security_key_name)
    {
        $record = $this->db->get_row(
            $this->db->prepare("
                SELECT * FROM $this->table WHERE `user_id` = %d AND `security_key_name` = %s
            ", $id, $security_key_name)
        );

        return !is_null($record);
    }

    public function is_credential_id_for_user_id(int $id, string $credential_id)
    {
        $record = $this->db->get_row(
            $this->db->prepare("
                SELECT * FROM $this->table WHERE `user_id` = %d AND `credential_id` = %s
            ", $id, $credential_id)
        );

        return !is_null($record);
    }

    public function get_by_credential_id(string $credential_id)
    {
        return $this->db->get_row(
            $this->db->prepare("
                SELECT * FROM $this->table WHERE `credential_id` = %s
            ", $credential_id)
        );
    }

    public function get_by_user_id_credential_id(int $id, string $credential_id)
    {
        return $this->db->get_row(
            $this->db->prepare("
                SELECT * FROM $this->table WHERE `user_id` = %d AND `credential_id` = %s
            ", $id, $credential_id)
        );
    }

    public function get_all_by_user_id_credential_ids(int $id)
    {
        return $this->db->get_results(
            $this->db->prepare("
                SELECT `credential_id` FROM $this->table WHERE `user_id` = %d
            ", $id)
        );
    }

    public function get_all_by_user_id(int $id)
    {
        return $this->db->get_results(
            $this->db->prepare("
                SELECT `id`, `security_key_name`, `aaguid`, `is_active`, `last_used_at`, `created_at`
                FROM $this->table 
                WHERE `user_id` = %d
                ORDER BY `created_at` DESC
            ", $id)
        );
    }

    public function touch_last_used(int $id)
    {
        return $this->update(
            ['last_used_at' => current_time('mysql')],
            ['id' => $id]
        );
    }

    public function update_is_active(int $id, bool $is_active)
    {
        return $this->update(
            ['is_active' => $is_active, 'updated_at' => current_time('mysql')],
            ['id' => $id]
        );
    }
}
