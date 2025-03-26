<?php
/**
 * Login WooCommerce template
 *
 * @package SecurePasskeys
 */

defined('ABSPATH') || exit;
?>
<div class="secure-passkey-login-woocommerce-wrapper" id="secure-passkey-login-woocommerce-wrapper">
    <div id="errorMessage" class="wc-block-components-notice-banner is-error" style="display: none;"></div>
    <div id="successMessage" class="wc-block-components-notice-banner is-success" style="display: none;"></div>
    <button id="login-via-passkey" class="woocommerce-button button woocommerce-form-login__submit wp-element-button login-via-passkey">
        <span id="spinnerText" style="display: none;"><?php esc_html_e('Login via Passkey..', 'secure-passkeys') ;?></span>
        <span id="buttonText"><?php esc_html_e('Login via Passkey', 'secure-passkeys') ;?></span>
    </button>
</div>