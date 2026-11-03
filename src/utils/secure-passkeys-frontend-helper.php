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
            if (!$load) {
                return self::render_view(realpath($file), (array) $data);
            }

            ob_start();
            self::render_view(realpath($file), (array) $data);
            return ob_get_clean();
        }

        return '';
    }

    /**
     * Render a view file with the given data exposed as variables in its scope.
     *
     * Templates reference the data keys directly (for example the shortcode atts
     * $extra_wrapper_classes and $extra_button_classes in login.shortcode). The
     * parameters are underscore-prefixed and EXTR_SKIP is used so a data key can
     * never overwrite the file path.
     *
     * @param string $__file Absolute path to the view file.
     * @param array  $__data Variables to expose to the view.
     * @return mixed The view file's return value (for include-style callers).
     */
    protected static function render_view(string $__file, array $__data)
    {
        extract($__data, EXTR_SKIP);

        return include $__file;
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
