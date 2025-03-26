<?php

namespace Secure_Passkeys\Settings;

use Secure_Passkeys\Utils\Secure_Passkeys_Helper;
use WP_Error;

defined('ABSPATH') || exit;

class Secure_Passkeys_Advanced_Settings extends Secure_Passkeys_Settings
{
    public function defaults()
    {
        $challenge_cleanup_days_periods = apply_filters('secure_passkeys_challenge_cleanup_allowed_days_periods', [0, 30, 60, 90, 180, 365]);
        $log_cleanup_days_periods = apply_filters('secure_passkeys_log_cleanup_allowed_days_periods', [0, 30, 60, 90, 180, 365]);

        return [
            'challenge_cleanup_days_periods' => $challenge_cleanup_days_periods,
            'log_cleanup_days_periods' => $log_cleanup_days_periods
        ];
    }

    public function get()
    {
        return [
            'challenge_cleanup_days',
            'log_cleanup_days',
        ];
    }

    public function save()
    {
        $challenge_cleanup_days = intval($_POST['settings']['challenge_cleanup_days'] ?? 0);
        $log_cleanup_days = intval($_POST['settings']['log_cleanup_days'] ?? 0);

        $challenge_cleanup_days_periods = apply_filters('secure_passkeys_challenge_cleanup_allowed_days_periods', [0, 30, 60, 90]);
        $log_cleanup_days_periods = apply_filters('secure_passkeys_log_cleanup_allowed_days_periods', [0, 30, 60, 90]);

        if (!in_array($challenge_cleanup_days, $challenge_cleanup_days_periods)) {
            return new WP_Error('error', __('The challenge cleanup period is not correct.', 'secure-passkeys'));
        }

        if (!in_array($log_cleanup_days, $log_cleanup_days_periods)) {
            return new WP_Error('error', __('The log cleanup period is not correct.', 'secure-passkeys'));
        }

        $data = [
            'challenge_cleanup_days' => $challenge_cleanup_days,
            'log_cleanup_days' => $log_cleanup_days
        ];

        Secure_Passkeys_Helper::update_option($data);

        return $data;
    }
}
