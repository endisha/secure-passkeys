<?php

namespace Secure_Passkeys\Actions;

use Secure_Passkeys\Models\Secure_Passkeys_WebAuthn;

class Secure_Passkeys_Web_Authn_Remove_Action extends Secure_Passkeys_Web_Authn_Action
{
    public function execute(int $user_id, int $id): bool
    {
        $validate = apply_filters('secure_passkeys_web_authn_validate_user_remove', true, $user_id, $id);

        if (!$validate) {
            return false;
        }

        return (new Secure_Passkeys_WebAuthn())->remove_by_user_id($id, $user_id);
    }
}
