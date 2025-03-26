<?php

namespace Secure_Passkeys\Utils;

defined('ABSPATH') || exit;

class Secure_Passkeys_I18n
{
    public static function get_login_localization(): array
    {
        return apply_filters('secure_passkeys_get_frontend_login_localization', [
            'passkeys_not_supported_in_browser' => __('Your browser does not support passkeys. Try updating your browser or using another one.', 'secure-passkeys'),
            'failed_load_options' => __('Failed to fetch passkey login options.', 'secure-passkeys'),
            'failed_login' => __('Passkey authentication failed. Please try again if you want to proceed.', 'secure-passkeys'),
            'cancelled_login' => __('Passkey authentication cancelled. Please try again if you want to proceed.', 'secure-passkeys'),
            'success_login' => __('You have successfully logged in with Passkey. Redirecting...', 'secure-passkeys'),
        ]);
    }

    public static function get_register_localization(): array
    {
        return apply_filters('secure_passkeys_get_frontend_register_localization', [
            'title' => __('Passwordless sign-in with passkeys', 'secure-passkeys'),
            'description' => __('Passkeys offer a secure and user-friendly authentication method, serving as an alternative or complement to traditional methods. They validate your identity through touch, facial recognition, device passwords, or PINs, and can effectively replace passwords.', 'secure-passkeys'),
            'out_of' => __('out of', 'secure-passkeys'),
            'add_passkey_button' => __('Add Passkey', 'secure-passkeys'),
            'add_button' => __('Add', 'secure-passkeys'),
            'cancel_button' => __('Cancel', 'secure-passkeys'),
            'your_passkeys' => __('Your Passkeys', 'secure-passkeys'),
            'add_waiting_button' => __('Waiting for input from browser interaction ...', 'secure-passkeys'),
            'added_on' => __('Added', 'secure-passkeys'),
            'last_used' => __('Last used', 'secure-passkeys'),
            'security_key_name' => __('Name your security key', 'secure-passkeys'),
            'security_key_description' => __('This passkey can work across multiple devices - pick a nickname that will help you identify it later.<br /> For example, the name of your password manager or account provider.', 'secure-passkeys'),
            'security_key_name_placeholder' => __('Enter Security Key Name', 'secure-passkeys'),
            'inactive' => __('Inactive', 'secure-passkeys'),
            'active' => __('Active', 'secure-passkeys'),
            'failed_load_passkeys' => __('An error occurred while loading passkeys.', 'secure-passkeys'),
            'reach_maximum_credentials' => __('The maximum number of registered passkeys has been reached.', 'secure-passkeys'),
            'failed_cancel_register' => __('Passkey registration failed or cancelled.', 'secure-passkeys'),
            'failed_save_passkey_name_length' => __('The security key name must be between 3 and 30 characters long.', 'secure-passkeys'),
            'passkey_already_registered' => __('You already registered this device. You don\'t have to register it again.', 'secure-passkeys'),
            'failed_register' => "An error occurred while registering passkey. Please try again if you want to proceed.",
            'cancelled_register' => __('Passkey registration cancelled. Please try again if you want to proceed.', 'secure-passkeys'),
            'confirm_delete_passkey' => __('Are you sure you want to delete your passkey?', 'secure-passkeys'),
            'failed_delete_passkey' => "An error occurred while deleting the passkey.",
            'success_delete_passkey' => __('Passkey successfully deleted.', 'secure-passkeys'),
            'passkeys_not_supported_in_browser' => __('Your browser does not support passkeys. Try updating your browser or using another one.', 'secure-passkeys'),
            'failed_load_options' => __('Failed to fetch passkey registration options.', 'secure-passkeys'),
            'failed_save_passkey' => __('An error occurred while saving the passkey.', 'secure-passkeys'),
            'failed_save_passkey_name' => __('Please use only letters, numbers, spaces, hyphens, or underscores.', 'secure-passkeys'),
            'failed_save_passkey_name_length' => __('Security key name must be at least 3 characters long.', 'secure-passkeys'),
            'success_save_passkey' => __('Passkey successfully added.', 'secure-passkeys'),
            'no_passkeys_found' =>  __('No passkeys found.', 'secure-passkeys'),
        ]);
    }

    public static function get_admin_localization(): array
    {
        return apply_filters('secure_passkeys_get_admin_localization', [
            'delete_message' => __('Are you sure you want to delete the passkey?', 'secure-passkeys'),
            'activate_message' => __('Are you sure you want to activate the passkey?', 'secure-passkeys'),
            'deactivate_message' => __('Are you sure you want to deactivate the passkey?', 'secure-passkeys'),
        ]);
    }

    public static function get_admin_profile_localization(): array
    {
        return apply_filters('secure_passkeys_get_admin_profile_localization', [
            'passkey_label' => __('Passwordless Secure Passkeys', 'secure-passkeys'),
            'description' => __('Passkeys offer a secure and user-friendly authentication method, serving as an alternative or complement to traditional methods. They validate your identity through touch, facial recognition, device passwords, or PINs, and can effectively replace passwords.', 'secure-passkeys'),
            'security_key_name_column' => __('Security Key Name', 'secure-passkeys'),
            'authenticator_column' => __('Authenticator', 'secure-passkeys'),
            'active_column' => __('Active', 'secure-passkeys'),
            'last_used_column' => __('Last Used', 'secure-passkeys'),
            'created_at_column' => __('Created Date', 'secure-passkeys'),
            'actions_column' => __('Actions', 'secure-passkeys'),
            'activate' => __('Activate', 'secure-passkeys'),
            'deactivate' => __('Deactivate', 'secure-passkeys'),
            'delete' => __('Delete', 'secure-passkeys'),
            'processing' => __('Processing...', 'secure-passkeys'),
            'deleting' => __('Deleting...', 'secure-passkeys'),
            'no_records_found' => __('No records found.', 'secure-passkeys'),
            'loading' => __('Loading passkeys...', 'secure-passkeys'),
            'unexpected_error' => __('An unexpected error occurred.', 'secure-passkeys'),
            'delete_message' => __('Are you sure you want to delete the passkey?', 'secure-passkeys'),
            'activate_message' => __('Are you sure you want to activate the passkey?', 'secure-passkeys'),
            'deactivate_message' => __('Are you sure you want to deactivate the passkey?', 'secure-passkeys'),
            'failed_fetch_passkeys' => __('Failed to fetch passkeys.', 'secure-passkeys'),
            'failed_delete_passkey' => "An error occurred while deleting the passkey.",
            'out_of' => __('out of', 'secure-passkeys'),
            'add_passkey_button' => __('Add Passkey', 'secure-passkeys'),
            'add_button' => __('Add', 'secure-passkeys'),
            'cancel_button' => __('Cancel', 'secure-passkeys'),
            'your_passkeys' => __('Your Passkeys', 'secure-passkeys'),
            'user_passkeys' => __('User Passkeys', 'secure-passkeys'),
            'add_waiting_button' => __('Waiting for input from browser interaction ...', 'secure-passkeys'),
            'added_on' => __('Added', 'secure-passkeys'),
            'last_used' => __('Last used', 'secure-passkeys'),
            'security_key_name' => __('Name your security key', 'secure-passkeys'),
            'security_key_description' => __('This passkey can work across multiple devices - pick a nickname that will help you identify it later.<br /> For example, the name of your password manager or account provider.', 'secure-passkeys'),
            'security_key_name_placeholder' => __('Enter Security Key Name', 'secure-passkeys'),
            'inactive' => __('Inactive', 'secure-passkeys'),
            'active' => __('Active', 'secure-passkeys'),
            'passkeys_not_supported_in_browser' => __('Your browser does not support passkeys. Try updating your browser or using another one.', 'secure-passkeys'),
            'failed_load_passkeys' => __('An error occurred while loading passkeys.', 'secure-passkeys'),
            'reach_maximum_credentials' => __('The maximum number of registered passkeys has been reached.', 'secure-passkeys'),
            'failed_cancel_register' => __('Passkey registration failed or cancelled.', 'secure-passkeys'),
            'failed_save_passkey_name_length' => __('The security key name must be between 3 and 30 characters long.', 'secure-passkeys'),
            'passkey_already_registered' => __('You already registered this device. You don\'t have to register it again.', 'secure-passkeys'),
            'failed_register' => "An error occurred while registering passkey. Please try again if you want to proceed.",
            'cancelled_register' => __('Passkey registration cancelled. Please try again if you want to proceed.', 'secure-passkeys'),
            'confirm_delete_passkey' => __('Are you sure you want to delete your passkey?', 'secure-passkeys'),
            'success_delete_passkey' => __('Passkey successfully deleted.', 'secure-passkeys'),
            'failed_load_options' => __('Failed to fetch passkey registration options.', 'secure-passkeys'),
            'failed_save_passkey' => __('An error occurred while saving the passkey.', 'secure-passkeys'),
            'failed_save_passkey_name' => __('Please use only letters, numbers, spaces, hyphens, or underscores.', 'secure-passkeys'),
            'success_save_passkey' => __('Passkey successfully added.', 'secure-passkeys'),
            'no_passkeys_found' =>  __('No passkeys found.', 'secure-passkeys'),
        ]);
    }
}
