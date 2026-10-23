<?php

/**
 * PHPUnit bootstrap for the Secure Passkeys test suite.
 *
 * The WebAuthn/CBOR layer under src/packages is almost entirely self-contained
 * PHP; the only WordPress functions it touches are wp_parse_url() and
 * wp_delete_file(). We stub those here and reuse the plugin's own autoloader so
 * the tests exercise the real class-loading path. Tests that need the full
 * WordPress runtime (database models, AJAX handlers) belong in a separate
 * integration suite built on wordpress-tests-lib.
 */

error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

if (!defined('ABSPATH')) {
    // The plugin files guard on this constant; tests are not a web request.
    define('ABSPATH', sys_get_temp_dir() . '/');
}

if (!function_exists('wp_parse_url')) {
    function wp_parse_url($url, $component = -1)
    {
        return parse_url($url, $component);
    }
}

if (!function_exists('wp_delete_file')) {
    function wp_delete_file($file)
    {
        if (is_string($file) && is_file($file)) {
            @unlink($file);
        }
    }
}

// Reuse the plugin's real autoloader for the Secure_Passkeys\* namespace.
if (!defined('SECURE_PASSKEYS_PLUGIN_DIR')) {
    define('SECURE_PASSKEYS_PLUGIN_DIR', dirname(__DIR__));
}
if (!defined('SECURE_PASSKEYS_PLUGIN_BASENAME')) {
    define('SECURE_PASSKEYS_PLUGIN_BASENAME', 'secure-passkeys');
}

require_once SECURE_PASSKEYS_PLUGIN_DIR . '/src/core/secure-passkeys-autoloader.php';
(new Secure_Passkeys\Core\Secure_Passkeys_Autoloader())->register();
