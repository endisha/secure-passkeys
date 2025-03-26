<?php

namespace Secure_Passkeys\Utils;

use stdClass;

defined('ABSPATH') || exit;

class Secure_Passkeys_Helper
{
    public static function verify_nonce(string $nonce)
    {
        return wp_verify_nonce(sanitize_text_field($nonce), SECURE_PASSKEYS_NONCE);
    }

    public static function check_is_admin_request(): bool
    {
        return strpos(sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER'] ?? '')), admin_url()) !== false;
    }

    public static function check_is_admin_login_request(): bool
    {
        return strpos(sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER'] ?? '')), 'wp-login.php') !== false;
    }

    public static function login_redirect_to(): string
    {
        return self::check_is_admin_request() || self::check_is_admin_login_request() ? admin_url() : home_url();
    }

    public static function get_user_full_name($user_id)
    {
        $first_name = get_user_meta($user_id, 'first_name', true);
        $last_name = get_user_meta($user_id, 'last_name', true);

        $full_name = trim($first_name.' '.$last_name);

        if (empty($full_name)) {
            $full_name = get_the_author_meta('user_email', $user_id);
        }

        return trim($full_name);
    }

    public static function get_user_object_by_id(int $id)
    {
        if (!$id) {
            return null;
        }

        $user = new stdClass();
        $user->id = $id;
        $user->name = self::get_user_full_name($id);
        $user->page_url = get_edit_user_link($id);

        return $user;
    }

    public static function get_users_roles()
    {
        global $wp_roles;

        $ignored_excluded_roles = apply_filters('secure_passkeys_ignored_excluded_roles', ['administrator']);

        $roles = [];
        foreach ($wp_roles->roles as $role_key => $role) {
            if (in_array($role_key, $ignored_excluded_roles)) {
                continue;
            }
            $translated_role = translate_user_role($role['name']);
            $roles[$role_key] = $translated_role;
        }
        return $roles;
    }


    public static function are_allowed_empty_roles(?array $roles = [])
    {
        global $wp_roles;

        $ignored_excluded_roles = apply_filters('secure_passkeys_ignored_excluded_roles', ['administrator']);

        if (!$roles) {
            return true;
        }

        foreach ($roles as $role) {
            if (in_array($role, $ignored_excluded_roles)) {
                continue;
            }
            if (!array_key_exists($role, $wp_roles->roles)) {
                return false;
            }
        }

        return $roles;
    }

    public static function is_user_in_excluded_roles(int $user_id)
    {
        if (!$user_id) {
            return false;
        }

        $ignored_excluded_roles = apply_filters('secure_passkeys_ignored_excluded_roles', ['administrator']);

        $excluded_roles = self::get_option('excluded_roles_registration_login', []);

        if (empty($excluded_roles)) {
            return false;
        }

        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        $user_roles = $user->roles;

        foreach ($user_roles as $user_role) {
            if (in_array($user_role, $ignored_excluded_roles)) {
                continue;
            }
            if (in_array($user_role, $excluded_roles)) {
                return true;
            }
        }

        return false;
    }

    public static function get_datetime_from_now(?string $datetime, int $hours = 12)
    {
        if (!$datetime) {
            return null;
        }

        $time_diff_in_seconds = current_time('timestamp') - strtotime($datetime);
        $hours_diff = $time_diff_in_seconds / 3600;

        if ($hours_diff > $hours) {
            return null;
        }

        $human_time_diff = human_time_diff(strtotime($datetime), current_time('timestamp'));

        // translators: %s represents a human-readable time difference (e.g., "5 minutes").
        return sprintf(__('%s ago', 'secure-passkeys'), $human_time_diff);
    }

    public static function get_ip_address()
    {
        if (! empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
        } elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = rest_is_ip_address(trim(current(preg_split('/,/', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']))))));
        } else {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
        }
        return (string) $ip;
    }

    public static function generate_fingerprint(): string
    {
        $userAgent = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
        $ipAddress = self::get_ip_address() ?? 'unknown';
        $acceptLanguage = sanitize_text_field(wp_unslash($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'unknown'));
        $acceptEncoding = sanitize_text_field(wp_unslash($_SERVER['HTTP_ACCEPT_ENCODING'] ?? 'unknown'));

        $fingerprintData = $userAgent . '|' . $ipAddress . '|' . $acceptLanguage . '|' . $acceptEncoding;

        $fingerprintHash = hash('sha256', $fingerprintData);

        return $fingerprintHash;
    }

    public static function get_option(?string $key = null, $default = '')
    {
        if (is_multisite()) {
            $settings = get_site_option('secure_passkeys_settings', []);
        } else {
            $settings = get_option('secure_passkeys_settings', []);
        }

        if (!is_null($key)) {
            return $settings[$key] ?? $default;
        }

        return $settings ?? [];
    }

    public static function update_option(array $data = [])
    {
        if (is_multisite()) {
            $settings = get_site_option('secure_passkeys_settings', self::get_default_settings());
        } else {
            $settings = get_option('secure_passkeys_settings', self::get_default_settings());
        }

        if (!is_array($settings)) {
            $settings = [];
        }

        $settings = array_merge($settings, $data);

        if (is_multisite()) {
            update_site_option('secure_passkeys_settings', $settings);
        } else {
            update_option('secure_passkeys_settings', $settings);
        }
    }

    public static function get_default_settings(): array
    {
        return [
            'registration_maximum_passkeys_enabled' => 1,
            'registration_maximum_passkeys_per_user' => 3,
            'excluded_roles_registration_login' => [],
            'registration_timeout' => 5,
            'registration_exclude_credentials_enabled' => 1,
            'registration_user_verification_enabled' => 1,
            'login_timeout' => 5,
            'login_user_verification' => 'required',
            'display_passkey_theme' => 'default',
            'display_passkey_login_wp_enabled' => 1,
            'display_passkey_login_woocommerce_enabled' => 1,
            'display_passkey_login_memberpress_enabled' => 1,
            'display_passkey_login_edd_enabled' => 1,
            'display_passkey_users_list_enabled' => 1,
            'display_passkey_edit_user_enabled' => 1,
            'challenge_cleanup_days' => 0,
            'log_cleanup_days' => 0,
        ];
    }

    public static function get_raw_credential_id(string $credential_id): string
    {
        return base64_encode(self::base64url_decode($credential_id));
    }

    public static function base64url_decode(string $string): string
    {
        return base64_decode(strtr($string, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($string)) % 4));
    }

    public static function filter_user_by(?string $keyword, ?string $model = null)
    {
        global $wpdb;

        $type = 'name';
        if (is_numeric($keyword)) {
            $type = 'id';
        } elseif (strpos($keyword, '@')) {
            $type = 'email';
        }
        $sql_query = "
        SELECT DISTINCT `u`.`ID` AS `ID`, `u`.`user_email` AS `user_email`
        FROM `{$wpdb->base_prefix}users` u";

        if ($model == 'logs') {
            $sql_query .= " INNER JOIN `{$wpdb->base_prefix}secure_passkeys_logs` sp ON u.ID = sp.user_id";
        } elseif ($model == 'webauthn') {
            $sql_query .= " INNER JOIN `{$wpdb->base_prefix}secure_passkeys_webauthns` sp ON u.ID = sp.user_id";
        }

        if ($type == 'id') {
            $sql_query .= " WHERE `u`.`ID` LIKE %s";
        } elseif ($type == 'email') {
            $sql_query .= " WHERE `u`.`user_email` LIKE %s";
        } elseif ($type == 'name') {
            $sql_query .= "
            INNER JOIN `{$wpdb->base_prefix}usermeta` um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
            INNER JOIN `{$wpdb->base_prefix}usermeta` um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
            WHERE CONCAT(um1.meta_value, ' ', um2.meta_value) LIKE %s";
        }

        $sql_query .= " ORDER BY `ID` ASC LIMIT 10";

        $search_term = '%' . $wpdb->esc_like($keyword) . '%';

        $records = $wpdb->get_results(
            $wpdb->prepare($sql_query, $search_term)
        );

        $results = [];
        if (!empty($records)) {
            foreach ($records as $record) {
                $lines = [];
                $id = $record->ID;
                $fullname = get_user_meta($id, 'first_name', true);
                $fullname .= ' ' . get_user_meta($id, 'last_name', true);

                $lines[] = trim($fullname . ' (#' . $id . ')');
                $lines[] = $record->user_email;

                $results[] = [
                    'id' => $id,
                    'text' => implode('||', $lines) ,
                    'name' => $fullname,
                ];
            }
        }

        return $results;
    }
}
