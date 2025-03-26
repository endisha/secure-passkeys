<?php

namespace Secure_Passkeys\Settings;

use WP_Error;

defined('ABSPATH') || exit;

class Secure_Passkeys_Factory
{
    public static function create($setting_name)
    {
        $settings_class = __NAMESPACE__.'\\Secure_Passkeys_'.ucfirst($setting_name).'_Settings';

        if (!class_exists($settings_class)) {
            return new WP_Error('invalid_settings', __('Invalid settings.', 'secure-passkeys'));
        }

        return new $settings_class();
    }
}
