<?php

namespace Secure_Passkeys\Core;

defined('ABSPATH') || exit;

class Secure_Passkeys_Migrations
{
    public function booting(string $path = ''): void
    {
        $file = $this->file();
        if (!is_null($file)) {
            $plugin = dirname($file);
            $this->execute($this->prepare($plugin));
        }
    }

    public function prepare(string $plugin): string
    {
        global $table_prefix, $wpdb;

        $sql = '';
        $dir = glob("{$plugin}/database/*.sql");
        foreach ($dir as $file) {
            $sql .= file_get_contents($file);
        }
        return $sql;
    }

    public function execute(string $query): void
    {
        if (!empty($query)) {
            $query = $this->replacement($query);
            require_once(ABSPATH . '/wp-admin/includes/upgrade.php');
            dbDelta($query);
        }
    }

    public function file(): ?string
    {
        return defined('SECURE_PASSKEYS_PLUGIN_FILE') ? SECURE_PASSKEYS_PLUGIN_FILE : null;
    }

    private function replacement(string $query): string
    {
        global $table_prefix, $wpdb;

        $collate = $wpdb->has_cap('collation') ? $wpdb->get_charset_collate() : '';

        return str_replace(['{prefix}', '{collate}'], [$table_prefix, $collate], $query);
    }
}
