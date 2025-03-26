<?php

namespace Secure_Passkeys\Includes;

use Secure_Passkeys\Utils\Secure_Passkeys_Adminarea_Helper;
use Secure_Passkeys\Utils\Secure_Passkeys_Helper;
use Secure_Passkeys\Utils\Secure_Passkeys_I18n;

defined('ABSPATH') || exit;

class Secure_Passkeys_Adminarea
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'menus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts'], 10);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_profile_passkey_vue_script'], 10);
        add_action('show_user_profile', [$this, 'passkey_section_to_profile'], 1);
        add_action('edit_user_profile', [$this, 'passkey_section_to_profile'], 1);
    }

    public function enqueue_scripts($hook)
    {
        $resources = [
            'secure-passkeys-overview' => [
                'vue-secure-passkeys-overview' => Secure_Passkeys_Adminarea_Helper::get_js_assets_url('overview.js')
            ],
            'secure-passkeys-list' => [
                'vue-secure-passkeys-list' => Secure_Passkeys_Adminarea_Helper::get_js_assets_url('passkeys.js')
            ],
            'secure-passkeys-activity' => [
                'vue-secure-passkeys-activity' => Secure_Passkeys_Adminarea_Helper::get_js_assets_url('activity.js')
            ],
            'secure-passkeys-settings' => [
                'vue-secure-passkeys-settings' => Secure_Passkeys_Adminarea_Helper::get_js_assets_url('settings.js')
            ],
        ];

        $key = Secure_Passkeys_Adminarea_Helper::get_page_resource_key($resources, $hook);

        if (!empty($key)) {
            $scripts = [
                'vuejs' => Secure_Passkeys_Adminarea_Helper::get_js_assets_url('vue.js'),
                'vue-router' => Secure_Passkeys_Adminarea_Helper::get_js_assets_url('vue-router.js'),
                'vue-pagination' => Secure_Passkeys_Adminarea_Helper::get_js_assets_url('pagination.js'),
                'jquery-ui' => Secure_Passkeys_Adminarea_Helper::get_css_assets_url('jquery-ui.css'),
                'secure-passkeys-css' => Secure_Passkeys_Adminarea_Helper::get_css_assets_url('style.css'),
            ];
            if (!empty($resources[$key])) {
                $scripts = array_merge($scripts, $resources[$key]);
            }

            $scripts = apply_filters('secure_passkeys_adminarea_scripts', $scripts);

            Secure_Passkeys_Adminarea_Helper::register_assets($scripts);

            wp_localize_script('vuejs', 'secure_passkeys_params', [
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce(SECURE_PASSKEYS_NONCE),
                'i18n' => Secure_Passkeys_I18n::get_admin_localization(),
            ]);
        }
    }

    public function menus()
    {
        $title = __('Secure Passkeys', 'secure-passkeys');

        $items = apply_filters('secure_passkeys_adminarea_menus', [
            'secure-passkeys-overview' => [
                'title' => __('Overview', 'secure-passkeys'),
                'callback' => function () {
                    include Secure_Passkeys_Adminarea_Helper::get_vue_component('overview.php');
                }
            ],
            'secure-passkeys-list' => [
                'title' => __('Passkeys', 'secure-passkeys'),
                'callback' => function () {
                    include Secure_Passkeys_Adminarea_Helper::get_vue_component('passkeys.php');
                },
            ],
            'secure-passkeys-activity' => [
                'title' => __('Activity Log', 'secure-passkeys'),
                'callback' => function () {
                    include Secure_Passkeys_Adminarea_Helper::get_vue_component('activity.php');
                },
            ],
            'secure-passkeys-settings' => [
                'title' => __('Settings', 'secure-passkeys'),
                'callback' => function () {
                    include Secure_Passkeys_Adminarea_Helper::get_vue_component('settings.php');
                },
            ],
        ], $title);

        Secure_Passkeys_Adminarea_Helper::render_menus($title, 'secure-passkeys', $items, Secure_Passkeys_Adminarea_Helper::get_image_assets_url('icon.svg'));
    }

    public function enqueue_profile_passkey_vue_script($hook)
    {
        if (!Secure_Passkeys_Helper::get_option('display_passkey_edit_user_enabled', 1)) {
            return;
        }

        if (!Secure_Passkeys_Adminarea_Helper::is_profile_or_edit_user_page()) {
            return;
        }

        $user_id = Secure_Passkeys_Adminarea_Helper::get_user_id_from_profile_or_user_page();

        if (Secure_Passkeys_Helper::is_user_in_excluded_roles($user_id)) {
            return;
        }

        $scripts = apply_filters('secure_passkeys_adminarea_profile_scripts', [
            'vue-js' => Secure_Passkeys_Adminarea_Helper::get_js_assets_url('vue3.js'),
            'vue-profile' => Secure_Passkeys_Adminarea_Helper::get_js_assets_url('profile.js'),
            'profile-css' => Secure_Passkeys_Adminarea_Helper::get_css_assets_url('profile.css'),
        ]);

        Secure_Passkeys_Adminarea_Helper::enqueue_assets($scripts);

        $credentials_allowed_count = null;
        if (Secure_Passkeys_Helper::get_option('registration_maximum_passkeys_enabled', 1)) {
            $credentials_allowed_count = intval(Secure_Passkeys_Helper::get_option('registration_maximum_passkeys_per_user', 3));
        }

        $has_access = apply_filters('secure_passkeys_adminarea_profile_has_access', current_user_can('edit_user', $user_id));

        wp_localize_script('vue-js', 'secure_passkeys_params', [
            'url' => admin_url('admin-ajax.php'),
            'user_id' => $user_id,
            'credentials_allowed_count' => $credentials_allowed_count,
            'nonce' => wp_create_nonce(SECURE_PASSKEYS_NONCE),
            'is_owner' => $user_id === get_current_user_id(),
            'has_access' => $has_access,
            'content' => Secure_Passkeys_Adminarea_Helper::include_vue_adminarea_file('profile', [], true),
            'i18n' => Secure_Passkeys_I18n::get_admin_profile_localization(),
        ]);
    }

    public function passkey_section_to_profile($user)
    {
        if (!Secure_Passkeys_Helper::get_option('display_passkey_edit_user_enabled', 0)) {
            return;
        }

        if (Secure_Passkeys_Helper::is_user_in_excluded_roles($user->ID)) {
            return;
        }

        ?>
        <div id="passkey-app">
            <passkey-list></passkey-list>
        </div>
        <?php
    }
}
