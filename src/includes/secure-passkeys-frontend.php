<?php

namespace Secure_Passkeys\Includes;

use Secure_Passkeys\Utils\Secure_Passkeys_Frontend_Helper;
use Secure_Passkeys\Utils\Secure_Passkeys_Helper;
use Secure_Passkeys\Utils\Secure_Passkeys_I18n;

defined('ABSPATH') || exit;

class Secure_Passkeys_Frontend
{
    protected $login_handle = 'secure-passkeys-login-script';

    protected $register_handle = 'secure-passkeys-register-script';

    public function __construct()
    {
        add_action('login_enqueue_scripts', [$this, 'enqueue_login_script']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_login_script']);

        add_shortcode('secure_passkeys_login_form', [$this, 'render_shortcode_login_form']);

        add_action('login_form', [$this, 'add_to_admin_login_page']);
        add_action('woocommerce_login_form_end', [$this, 'add_to_woocommerce_login_page']);
        add_action('edd_login_fields_after', [$this, 'add_to_edd_login_page']);
        add_action('mepr-login-form-after-submit', [$this, 'add_to_memberpress_login_page']);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_register_script']);
        add_shortcode('secure_passkeys_register_form', [$this, 'render_shortcode_register_form']);
    }

    public function enqueue_login_script()
    {
        if (is_user_logged_in()) {
            return;
        }

        wp_register_script($this->login_handle, Secure_Passkeys_Frontend_Helper::get_js_assets_url('webauthn.login.js'), [], SECURE_PASSKEYS_VERSION, true);

        wp_register_style($this->login_handle, Secure_Passkeys_Frontend_Helper::get_css_assets_url('login.css'), [], SECURE_PASSKEYS_VERSION);

        wp_localize_script($this->login_handle, 'secure_passkeys_object', [
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(SECURE_PASSKEYS_NONCE),
            'i18n' => Secure_Passkeys_I18n::get_login_localization()
        ]);
    }

    public function add_to_admin_login_page()
    {
        if (!Secure_Passkeys_Helper::get_option('display_passkey_login_wp_enabled', 1)) {
            return;
        }

        if (is_user_logged_in()) {
            return;
        }

        wp_enqueue_script($this->login_handle);

        wp_enqueue_style($this->login_handle);

        return Secure_Passkeys_Frontend_Helper::include_view_frontend_file('login.default');
    }

    public function add_to_woocommerce_login_page()
    {
        if (!Secure_Passkeys_Helper::get_option('display_passkey_login_woocommerce_enabled', 1)) {
            return;
        }

        if (is_user_logged_in()) {
            return;
        }

        wp_enqueue_script($this->login_handle);

        wp_enqueue_style($this->login_handle);

        return Secure_Passkeys_Frontend_Helper::include_view_frontend_file('login.woocommerce');
    }

    public function add_to_memberpress_login_page()
    {
        if (!Secure_Passkeys_Helper::get_option('display_passkey_login_memberpress_enabled', 1)) {
            return;
        }

        if (is_user_logged_in()) {
            return;
        }

        wp_enqueue_script($this->login_handle);

        wp_enqueue_style($this->login_handle);

        return Secure_Passkeys_Frontend_Helper::include_view_frontend_file('login.memberpress');
    }

    public function add_to_edd_login_page()
    {
        if (!Secure_Passkeys_Helper::get_option('display_passkey_login_edd_enabled', 1)) {
            return;
        }

        if (is_user_logged_in()) {
            return;
        }

        wp_enqueue_script($this->login_handle);

        wp_enqueue_style($this->login_handle);

        return Secure_Passkeys_Frontend_Helper::include_view_frontend_file('login.edd');
    }

    public function render_shortcode_login_form()
    {
        if (is_user_logged_in()) {
            return;
        }

        wp_enqueue_script($this->login_handle);

        wp_enqueue_style($this->login_handle);

        return Secure_Passkeys_Frontend_Helper::include_view_frontend_file('login.shortcode', [], true);
    }

    public function enqueue_register_script()
    {
        if (!is_user_logged_in()) {
            return;
        }

        if (Secure_Passkeys_Helper::is_user_in_excluded_roles(get_current_user_id())) {
            return;
        }

        wp_register_script($this->register_handle.'-vue', Secure_Passkeys_Frontend_Helper::get_js_assets_url('vue.js'), [], SECURE_PASSKEYS_VERSION, true);

        wp_register_script($this->register_handle, Secure_Passkeys_Frontend_Helper::get_js_assets_url('webauthn.register.js'), [], SECURE_PASSKEYS_VERSION, true);

        wp_register_style($this->register_handle, Secure_Passkeys_Frontend_Helper::get_css_assets_url('register.css'), [], SECURE_PASSKEYS_VERSION);

        $credentials_allowed_count = null;
        if (Secure_Passkeys_Helper::get_option('registration_maximum_passkeys_enabled', 1)) {
            $credentials_allowed_count = intval(Secure_Passkeys_Helper::get_option('registration_maximum_passkeys_per_user', 3));
        }

        wp_localize_script($this->register_handle, 'secure_passkeys_object', [
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(SECURE_PASSKEYS_NONCE),
            'credentials_allowed_count' => $credentials_allowed_count,
            'content' => Secure_Passkeys_Frontend_Helper::include_view_frontend_file('register', [], true),
            'is_rtl' => is_rtl(),
            'i18n' => Secure_Passkeys_I18n::get_register_localization()
        ]);
    }

    public function render_shortcode_register_form()
    {
        if (!is_user_logged_in()) {
            return;
        }

        if (Secure_Passkeys_Helper::is_user_in_excluded_roles(get_current_user_id())) {
            return;
        }

        wp_enqueue_script($this->register_handle.'-vue');

        wp_enqueue_script($this->register_handle);

        wp_enqueue_style($this->register_handle);

        return apply_filters('secure_passkeys_register_form', '<div id="passkey_app"></div>');
    }
}
