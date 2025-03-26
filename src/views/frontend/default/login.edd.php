<?php
/**
 * Login Easy Digital Downloads template
 *
 * @package SecurePasskeys
 */

defined('ABSPATH') || exit;
?>
<div class="secure-passkey-login-edd-wrapper" id="secure-passkey-login-edd-wrapper">
    <div id="errorMessage" class="edd_errors edd-alert edd-alert-error is-error" style="display: none;"></div>
    <div id="successMessage" class="edd_success edd-alert edd-alert-success" style="display: none;"></div>
    <button id="login-via-passkey" class="edd-button edd-submit login-via-passkey">
        <span id="spinnerText" style="display: none;"><?php esc_html_e('Login via Passkey..', 'secure-passkeys') ;?></span>
        <span id="buttonText"><?php esc_html_e('Login via Passkey', 'secure-passkeys') ;?></span>
    </button>
</div>