<?php

namespace Secure_Passkeys\Core;

defined('ABSPATH') || exit;

class Secure_Passkeys_Migrations
{
    public function booting(string $path): void
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
            $filename = esc_sql(basename($file, ".sql"));
            $table_name = $table_prefix . $filename;
            $table_name = esc_sql($table_name);
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
                $sql .= file_get_contents($file);
            }
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
        $file = null;
        $backtrace = debug_backtrace();
        foreach ($backtrace as $entry) {
            if ($entry['function'] == 'activate_plugin') {
                if (isset($entry['args'][0]) && SECURE_PASSKEYS_PLUGIN_FILE_BASENAME === $entry['args'][0]) {
                    $plugin = SECURE_PASSKEYS_PLUGIN_FILE;
                    if (file_exists($plugin)) {
                        $file = $plugin;
                    }
                    break;
                }
            }
        }
        return $file;
    }

    private function replacement(string $query): string
    {
        global $table_prefix, $wpdb;

        $collate = $wpdb->has_cap('collation') ? $wpdb->get_charset_collate() : '';

        return str_replace(['{prefix}', '{collate}'], [$table_prefix, $collate], $query);
    }
}
