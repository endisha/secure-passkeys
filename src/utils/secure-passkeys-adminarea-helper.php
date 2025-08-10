<?php

namespace Secure_Passkeys\Utils;

defined('ABSPATH') || exit;

class Secure_Passkeys_Adminarea_Helper
{
    public static function is_profile_or_edit_user_page()
    {
        global $current_screen;

        return $current_screen && in_array($current_screen->id, ['profile', 'user-edit']);
    }

    public static function get_user_id_from_profile_or_user_page()
    {
        global $current_screen;

        $user_id = $current_screen->id === 'profile' ? get_current_user_id() : intval($_GET['user_id'] ?? 0);

        return intval($user_id);
    }

    public static function get_page_resource_key(array $resources, string $hook)
    {
        $found = false;

        foreach (array_keys($resources) as $key) {
            $found = strpos($hook, $key) !== false;
            if ($found) {
                break;
            }
        }

        return $found ? $key : '';
    }

    public static function render_menus($title, $slug, $items, $icon)
    {
        $capability = apply_filters('secure_passkeys_admin_menu_capability', 'manage_options');

        add_menu_page($title, $title, $capability, $slug, '', $icon, 53);
        foreach ($items as $key => $item) {
            add_submenu_page($slug, $item['title'], $item['title'], $capability, $key, $item['callback']);
        }
        remove_submenu_page($slug, $slug);
    }

    public static function register_assets(array $scripts)
    {
        foreach ($scripts as $handle => $source) {
            $extension = substr(strrchr($source, "."), 1);

            if ($extension == 'js') {
                wp_register_script($handle, $source, [], SECURE_PASSKEYS_VERSION, true);
            } elseif ($extension == 'css') {
                wp_register_style($handle, $source, [], SECURE_PASSKEYS_VERSION);
            }
        }
    }

    public static function enqueue_assets(array $scripts)
    {
        foreach ($scripts as $handle => $source) {
            $extension = substr(strrchr($source, "."), 1);

            if ($extension == 'js') {
                wp_enqueue_script($handle, $source, [], SECURE_PASSKEYS_VERSION, true);
            } elseif ($extension == 'css') {
                wp_enqueue_style($handle, $source, [], SECURE_PASSKEYS_VERSION);
            }
        }
    }

    public static function get_js_assets_url(string $file)
    {
        return SECURE_PASSKEYS_JS_ASSETS_ADMIN_URL . '/' . $file;
    }

    public static function get_css_assets_url(string $file)
    {
        return SECURE_PASSKEYS_CSS_ASSETS_ADMIN_URL . '/' . $file;
    }

    public static function get_image_assets_url(string $file)
    {
        return SECURE_PASSKEYS_IMG_ASSETS_ADMIN_URL . '/' . $file;
    }

    public static function get_vue_component(string $file)
    {
        return SECURE_PASSKEYS_VIEWS_ADMIN_VUE_DIR . '/' . $file;
    }

    public static function get_view_file(string $file)
    {
        return SECURE_PASSKEYS_VIEWS_ADMIN_DIR . '/' . $file;
    }

    public static function display_passkeys_in_users_list(?object $passkeys)
    {
        if (empty($passkeys)) {
            return '-';
        }

        $lines = [];

        if ($passkeys->activated > 0) {
            $lines[] = sprintf(
                '%s: <strong>%d</strong>',
                __('Activated', 'secure-passkeys'),
                intval($passkeys->activated)
            );
        }
        if ($passkeys->deactivated > 0) {
            $lines[] = sprintf(
                '%s: <strong>%d</strong>',
                __('Deactivated', 'secure-passkeys'),
                intval($passkeys->deactivated)
            );
        }

        return implode('<br />', $lines);
    }

    public static function include_vue_adminarea_file(string $template, ?array $data = [], bool $load = false)
    {
        $file = realpath(SECURE_PASSKEYS_VIEWS_ADMIN_VUE_DIR . '/' . $template . '.php');

        $file = apply_filters('secure_passkeys_include_view_adminarea_file', $file, $template, $data);

        if (file_exists(realpath($file))) {
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

    public static function show_enable_passkeys_notice()
    {
        $title = __('Simplify your sign-in.', 'secure-passkeys');
        $title = '<strong>' . $title . '</strong><br>';
        $title = apply_filters('secure_passkeys_enable_passkeys_notice_title', $title);

        $profile_url = esc_url(admin_url('profile.php').'#passkey-app');
        $profile_url = apply_filters('secure_passkeys_enable_passkeys_notice_profile_url', $profile_url);

        $link_text = __('Enable passkeys', 'secure-passkeys');
        $link = '<br><a href="' . $profile_url . '">' . $link_text . '</a>';

        $message = sprintf(
            /* translators: %1$s is the title, %2$s is the clickable enable passkeys link */
            __('%1$s Use your fingerprint or other biometric methods with passkeys to securely and conveniently verify your login. %2$s in your profile settings.', 'secure-passkeys'),
            $title,
            $link
        );

        $allowed_tags = [
            'a' => [
                'href' => [],
                'title' => [],
                'target' => [],
            ],
            'strong' => [],
            'br' => [],
        ];

        $escaped_message = wp_kses($message, $allowed_tags);
        $escaped_message = apply_filters('secure_passkeys_enable_passkeys_notice_message', $escaped_message);

        $alert = '<div class="notice notice-warning is-dismissible"><p>' . $escaped_message . '</p></div>';
        $alert = apply_filters('secure_passkeys_enable_passkeys_notice_alert', $alert);

        return $alert;
    }
}
