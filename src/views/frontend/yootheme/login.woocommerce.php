<?php
/**
 * Login WooCommerce template
 *
 * @package SecurePasskeys
 */

defined('ABSPATH') || exit;
?>
<div class="uk-container uk-text-center" id="secure-passkey-login-woocommerce-wrapper">
    <div id="errorMessage" class="uk-alert-danger" uk-alert style="display: none;"></div>
    <div id="successMessage" class="uk-alert-success" uk-alert style="display: none;"></div>
    <button id="login-via-passkey" class="uk-button uk-button-default">
        <span id="spinnerText" style="display: none;"><?php esc_html_e('Login via Passkey..', 'secure-passkeys'); ?></span>
        <span id="buttonText"><?php esc_html_e('Login via Passkey', 'secure-passkeys'); ?></span>
    </button>
</div>
