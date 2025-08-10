<?php

/**
 * Plugin Name: Secure Passkeys
 * Plugin URI: https://endisha.ly/
 * Description: Secure Passkeys is a powerful WordPress plugin that enables passwordless authentication using WebAuthn technology.
 * Author: Mohamed Endisha
 * Author URI: https://endisha.ly
 * Version: 1.2.0
 * Text Domain: secure-passkeys
 * Domain Path: /src/languages/
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

define('SECURE_PASSKEYS_VERSION', '1.2.0');
define('SECURE_PASSKEYS_PLUGIN_FILE', __FILE__);
define('SECURE_PASSKEYS_PLUGIN_DIR', __DIR__);
define('SECURE_PASSKEYS_AUTOLOADER', __DIR__ . '/src/core/secure-passkeys-autoloader.php');
define('SECURE_PASSKEYS_BOOTSTRAP_FILE', __DIR__ . '/bootstrap.php');
define('SECURE_PASSKEYS_LANGUAGES_DIR', basename(__DIR__) . '/src/languages/');
define('SECURE_PASSKEYS_PLUGIN_BASENAME', basename(__FILE__, '.php'));
define('SECURE_PASSKEYS_PLUGIN_FILE_BASENAME', basename(__DIR__) . '/' . basename(__FILE__));
define('SECURE_PASSKEYS_VIEWS_ADMIN_VUE_DIR', __DIR__ . '/src/views/admin/vue');
define('SECURE_PASSKEYS_INCLUDES_DIR', __DIR__ . '/src/includes');
define('SECURE_PASSKEYS_JOBS_DIR', __DIR__ . '/src/jobs');
define('SECURE_PASSKEYS_AJAX_DIR', __DIR__ . '/src/ajax');
define('SECURE_PASSKEYS_HOOKS_DIR', __DIR__ . '/src/hooks');
define('SECURE_PASSKEYS_VIEWS_DIR', __DIR__ . '/src/views');
define('SECURE_PASSKEYS_VIEWS_ADMIN_DIR', __DIR__ . '/src/views/admin');
define('SECURE_PASSKEYS_VIEWS_FRONTEND_DIR', __DIR__ . '/src/views/frontend');
define('SECURE_PASSKEYS_ASSETS_FRONTEND_URL', plugin_dir_url(__FILE__) . 'assets/frontend');
define('SECURE_PASSKEYS_ASSETS_ADMIN_URL', plugin_dir_url(__FILE__) . 'assets/admin');
define('SECURE_PASSKEYS_CSS_ASSETS_FRONTEND_URL', SECURE_PASSKEYS_ASSETS_FRONTEND_URL . '/css');
define('SECURE_PASSKEYS_JS_ASSETS_FRONTEND_URL', SECURE_PASSKEYS_ASSETS_FRONTEND_URL . '/js');
define('SECURE_PASSKEYS_CSS_ASSETS_ADMIN_URL', SECURE_PASSKEYS_ASSETS_ADMIN_URL . '/css');
define('SECURE_PASSKEYS_JS_ASSETS_ADMIN_URL', SECURE_PASSKEYS_ASSETS_ADMIN_URL . '/js');
define('SECURE_PASSKEYS_IMG_ASSETS_ADMIN_URL', SECURE_PASSKEYS_ASSETS_ADMIN_URL . '/img');
define('SECURE_PASSKEYS_NONCE', 'secure-passkeys-nonce');

require SECURE_PASSKEYS_BOOTSTRAP_FILE;
