<?php

namespace Secure_Passkeys\Utils;

defined('ABSPATH') || exit;

class Secure_Passkeys_Frontend_Helper
{
    public static function include_view_frontend_file(string $template, ?array $data = [], bool $load = false)
    {
        $default_theme = apply_filters('secure_passkeys_default_theme', 'default');

        $current_theme = Secure_Passkeys_Helper::get_option('display_passkey_theme', $default_theme);

        $theme = apply_filters('secure_passkeys_theme', $current_theme);

        $themes = self::get_frontend_themes();

        if (!array_key_exists($theme, $themes)) {
            $theme = $default_theme;
        }

        $theme_paths = self::get_frontend_themes_paths();

        if (!array_key_exists($theme, $theme_paths)) {
            $theme = $default_theme;
        }

        $theme_path = $theme_paths[$theme];
        $default_path = $theme_paths['default'] ?? '';

        $file_path = realpath($theme_path . '/' . $template . '.php');

        if (empty($file_path)) {
            $file_path = realpath($default_path . '/' . $template . '.php');
        }

        $file = apply_filters('secure_passkeys_include_view_frontend_file', $file_path, $theme, $template, $data);

        if (!empty($file) && file_exists(realpath($file))) {
            extract($data);
            if (!$load) {
                return include realpath($file);
            }

            ob_start();
            require realpath($file);
            return ob_get_clean();
        }

        return '';
    }

    public static function get_frontend_themes()
    {
        return apply_filters('secure_passkeys_themes', [
            'default' => __('Default', 'secure-passkeys'),
            'yootheme' => __('YOOtheme (UiKit)', 'secure-passkeys'),
        ]);
    }

    public static function get_frontend_themes_paths()
    {
        return apply_filters('secure_passkeys_themes_paths', [
            'default' => SECURE_PASSKEYS_VIEWS_FRONTEND_DIR . '/default',
            'yootheme' => SECURE_PASSKEYS_VIEWS_FRONTEND_DIR . '/yootheme',
        ]);
    }

    public static function get_js_assets_url(string $file_name)
    {
        $file = SECURE_PASSKEYS_JS_ASSETS_FRONTEND_URL . '/' . $file_name;

        return apply_filters('secure_passkeys_get_frontend_js_assets_url', $file, $file_name);
    }

    public static function get_css_assets_url(string $file_name)
    {
        $file = SECURE_PASSKEYS_CSS_ASSETS_FRONTEND_URL . '/' . $file_name;

        return apply_filters('secure_passkeys_get_frontend_css_assets_url', $file, $file_name);
    }
}
