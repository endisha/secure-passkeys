<?php

namespace Secure_Passkeys\Core;

defined('ABSPATH') || exit;

class Secure_Passkeys_Autoloader
{
    /**
     * Registers autoloader
     */
    public function register()
    {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    public function autoload(string $class)
    {
        $file = $this->get_class_file($class);

        if (file_exists($file)) {
            require_once $file;
        }
    }

    private function get_class_file(string $class): string
    {
        $class_file = strtolower(str_replace('_', '-', $class));
        $class_file = str_replace('\\', DIRECTORY_SEPARATOR, $class_file);
        $class_file = str_replace(SECURE_PASSKEYS_PLUGIN_BASENAME . '/', '', $class_file);

        return SECURE_PASSKEYS_PLUGIN_DIR . '/src/' . $class_file . '.php';
    }
}
