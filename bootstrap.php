<?php

use Secure_Passkeys\Core\Secure_Passkeys_Application;

defined('ABSPATH') || exit;

require_once __DIR__ . '/autoloader.php';

$app = new Secure_Passkeys_Application();
$app->boot();
