<?php

use Secure_Passkeys\Core\Secure_Passkeys_Autoloader;

defined('ABSPATH') || exit;

require_once SECURE_PASSKEYS_AUTOLOADER;

$autoloader = new Secure_Passkeys_Autoloader();
$autoloader->register();
