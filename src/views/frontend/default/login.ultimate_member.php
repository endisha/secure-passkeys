<?php
/**
 * Login Ultimate Member template
 *
 * @package SecurePasskeys
 */

defined('ABSPATH') || exit;
?>
<div class="secure-passkey-login-ultimate-member-wrapper" id="secure-passkey-login-ultimate-member-wrapper">
    <div id="errorMessage" class="um-field-error is-error" style="display: none;"></div>
    <p id="successMessage" class="um-notice success is-success" style="display: none;"></p>
    <button id="login-via-passkey" class="um-button um-alt login-via-passkey">
        <span id="spinnerText" style="display: none;"><?php esc_html_e('Login via Passkey..', 'secure-passkeys') ;?></span>
        <span id="buttonText"><?php esc_html_e('Login via Passkey', 'secure-passkeys') ;?></span>
    </button>
</div>