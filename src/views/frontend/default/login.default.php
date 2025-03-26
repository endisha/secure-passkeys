<?php
/**
 * Login Default template
 *
 * @package SecurePasskeys
 */

defined('ABSPATH') || exit;
?>
<div class="secure-passkey-login-wrapper" id="secure-passkey-login-wrapper" style="display: none;">
    <div id="errorMessage" class="notice notice-error" style="display: none;"></div>
    <div id="successMessage" class="notice notice-success" style="display: none;"></div>
    <button id="login-via-passkey" class="button button-large login-via-passkey">
        <span id="spinnerText" style="display: none;"><?php esc_html_e('Login via Passkey..', 'secure-passkeys') ;?></span>
        <span id="buttonText"><?php esc_html_e('Login via Passkey', 'secure-passkeys') ;?></span>
    </button>
</div>