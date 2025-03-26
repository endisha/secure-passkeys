<?php
/**
 * Login MemberPress template
 *
 * @package SecurePasskeys
 */

defined('ABSPATH') || exit;
?>
<div class="secure-passkey-login-memberpress-wrapper" id="secure-passkey-login-memberpress-wrapper">
    <div id="errorMessage" class="wc-block-components-notice-banner is-error" style="display: none;"></div>
    <div id="successMessage" class="wc-block-components-notice-banner is-success" style="display: none;"></div>
    <button id="login-via-passkey" class="memberpress-button button-primary login-via-passkey">
        <span id="spinnerText" style="display: none;"><?php esc_html_e('Login via Passkey..', 'secure-passkeys') ;?></span>
        <span id="buttonText"><?php esc_html_e('Login via Passkey', 'secure-passkeys') ;?></span>
    </button>
</div>