<?php

namespace Secure_Passkeys\Utils;

defined('ABSPATH') || exit;

class Secure_Passkeys_Loader
{
    public static function folder_loader(string $folder, string $plugin_dir)
    {
        $classes = [];

        foreach (glob($folder . '/*.php') as $file) {
            $path = str_replace([dirname($plugin_dir), '/src'], '', $file);
            $path = str_replace(DIRECTORY_SEPARATOR, '\\', $path);
            $class = str_replace(' ', '_', ucwords(str_replace('-', ' ', $path)));
            $parts = explode('\\', $class);
            $class = implode('\\', array_map('ucfirst', $parts));
            $class = rtrim($class, '.php');
            $class = ltrim($class, '\\');
            if (class_exists($class)) {
                $classes[] = new $class();
            }
        }

        return $classes;
    }
}
