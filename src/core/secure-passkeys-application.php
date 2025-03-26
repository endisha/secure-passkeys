<?php

namespace Secure_Passkeys\Core;

use Secure_Passkeys\Utils\Secure_Passkeys_Helper;
use Secure_Passkeys\Utils\Secure_Passkeys_Loader;

defined('ABSPATH') || exit;

class Secure_Passkeys_Application
{
    /**
     * Boot the plugin.
     */
    public function boot(): void
    {
        register_activation_hook(SECURE_PASSKEYS_PLUGIN_FILE, [new Secure_Passkeys_Migrations(), 'booting']);

        register_activation_hook(SECURE_PASSKEYS_PLUGIN_FILE, [$this, 'register_default_settings']);

        register_deactivation_hook(SECURE_PASSKEYS_PLUGIN_FILE, [$this, 'unregister']);

        add_action('activated_plugin', [$this, 'activation']);

        add_filter('plugin_action_links', [$this, 'plugin_action_settings'], 10, 2);

        $this->load_includes();

        $this->load_hooks();

        $this->register_jobs();

        add_action('init', [$this, 'load_i18n']);

        add_action('plugins_loaded', [$this, 'load_ajax']);
    }

    /**
     * Load internationalization (i18n) languages for the plugin.
     */
    public function load_i18n(): void
    {
        load_plugin_textdomain('secure-passkeys', false, SECURE_PASSKEYS_LANGUAGES_DIR);
    }

    /**
     * Load plugin includes.
     */
    public function load_includes()
    {
        Secure_Passkeys_Loader::folder_loader(SECURE_PASSKEYS_INCLUDES_DIR, SECURE_PASSKEYS_PLUGIN_DIR);
    }

    /**
     * Load plugin ajax.
     */
    public function load_ajax()
    {
        Secure_Passkeys_Loader::folder_loader(SECURE_PASSKEYS_AJAX_DIR, SECURE_PASSKEYS_PLUGIN_DIR);
    }

    /**
     * Load plugin ajax.
     */
    public function load_hooks()
    {
        Secure_Passkeys_Loader::folder_loader(SECURE_PASSKEYS_HOOKS_DIR, SECURE_PASSKEYS_PLUGIN_DIR);
    }

    /**
     * Plugin action settings
     */
    public function plugin_action_settings(array $links, string $plugin): array
    {
        if ($plugin == SECURE_PASSKEYS_PLUGIN_FILE_BASENAME) {
            $links[] = sprintf('<a href="%s">%s</a>', $this->get_plugin_settings_url(), esc_html__('Settings', 'secure-passkeys'));
        }
        return $links;
    }

    /**
     * Activation
     */
    public function activation(string $plugin): void
    {
        if ($plugin == SECURE_PASSKEYS_PLUGIN_FILE_BASENAME) {
            wp_safe_redirect($this->get_plugin_settings_url());
            exit;
        }
    }

    /**
     * Register default settings
     */
    public function register_default_settings()
    {
        $defaults = Secure_Passkeys_Helper::get_default_settings();

        $options = Secure_Passkeys_Helper::get_option();

        if (!empty($options)) {
            foreach ($defaults as $key => $value) {
                if (!isset($options[$key])) {
                    $defaults[$key] = $value;
                }
            }
        }

        Secure_Passkeys_Helper::update_option($defaults);
    }

    /**
     * Get the plugin settings URL
     */
    protected function get_plugin_settings_url(): string
    {
        return admin_url('admin.php?' . http_build_query(['page' => 'secure-passkeys-settings']));
    }

    /**
     * Register jobs
     */
    public function register_jobs()
    {
        $jobs = Secure_Passkeys_Loader::folder_loader(SECURE_PASSKEYS_JOBS_DIR, SECURE_PASSKEYS_PLUGIN_DIR);

        if (!empty($jobs)) {
            foreach ($jobs as $instance) {
                if ($instance instanceof Secure_Passkeys_Scheduler) {
                    if (method_exists($instance, 'boot')) {
                        $instance->boot();
                    }
                }
            }
        }
    }

    /**
     * Unregister jobs
     */
    public function unregister()
    {
        $jobs = Secure_Passkeys_Loader::folder_loader(SECURE_PASSKEYS_JOBS_DIR, SECURE_PASSKEYS_PLUGIN_DIR);

        if (!empty($jobs)) {
            foreach ($jobs as $instance) {
                if ($instance instanceof Secure_Passkeys_Scheduler) {
                    $instance->unregister();
                }
            }
        }
    }
}
