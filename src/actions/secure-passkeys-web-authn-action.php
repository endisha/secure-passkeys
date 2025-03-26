<?php

namespace Secure_Passkeys\Actions;

use Secure_Passkeys\Models\Secure_Passkeys_WebAuthn;
use Secure_Passkeys\Utils\Secure_Passkeys_Helper;

class Secure_Passkeys_Web_Authn_Action
{
    protected function excluded_credentials_for_user($user_id): array
    {
        $credential_ids = (new Secure_Passkeys_WebAuthn())->get_all_by_user_id_credential_ids($user_id);

        return array_map(function ($item) {
            return [
                'type' => 'public-key',
                'id' => $item->credential_id,
            ];
        }, $credential_ids);
    }

    protected function get_relying_party_id(): string
    {
        $site_url = get_option('siteurl');
        $parsed_url = wp_parse_url($site_url);
        $domain = $parsed_url['host'] ?? '';

        $value = apply_filters('secure_passkeys_web_authn_relying_party_id', $domain);

        return strval($value);
    }

    protected function get_login_timeout(): int
    {
        $value = intval(Secure_Passkeys_Helper::get_option('login_timeout', 5)) * 60 * 1000;

        $value = apply_filters('secure_passkeys_web_authn_login_timeout', $value);

        return $value;
    }

    protected function get_registration_timeout(): int
    {
        $value = intval(Secure_Passkeys_Helper::get_option('registration_timeout', 5)) * 60 * 1000;

        $value = apply_filters('secure_passkeys_web_authn_registration_timeout', $value);

        return $value;
    }

    protected function is_exclude_credentials_enabled(): bool
    {
        $value = intval(Secure_Passkeys_Helper::get_option('registration_exclude_credentials_enabled', 1));

        $value = apply_filters('secure_passkeys_web_authn_is_exclude_credentials_enabled', $value);

        return (bool) $value;
    }

    protected function authenticator_selection(): array
    {
        return apply_filters('secure_passkeys_web_authn_authenticator_selection', [
            'residentKey' => 'required',
            'userVerification' => 'required',
        ]);
    }

    protected function is_authenticator_selection_enabled(): bool
    {
        $value = $this->authenticator_selection();

        $option = !empty($value) && is_array($value);

        $option = apply_filters('secure_passkeys_web_authn_is_authenticator_selection_enabled', $option);

        return $option;
    }

    protected function get_authenticator_selection(): array
    {
        return $this->authenticator_selection();
    }

    protected function get_login_user_verification(): string
    {
        $value = strval(Secure_Passkeys_Helper::get_option('login_user_verification', 'required'));

        $value = apply_filters('secure_passkeys_web_authn_login_user_verification', $value);

        return $value;
    }

    protected function is_user_verification_required(): bool
    {
        $value = intval(Secure_Passkeys_Helper::get_option('registration_user_verification_enabled', 1));

        $value = apply_filters('secure_passkeys_web_authn_is_user_verification_required', $value);

        return (bool) $value;
    }
}
