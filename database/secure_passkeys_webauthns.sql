CREATE TABLE `{prefix}secure_passkeys_webauthns` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `credential_id` VARCHAR(255) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT '1',
    `security_key_name` VARCHAR(255) NOT NULL,
    `public_key` TEXT NOT NULL,
    `aaguid` CHAR(36) NOT NULL,
    `last_used_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `{prefix}secure_passkeys_webauthns_credential_id_unique` (`credential_id`),
    KEY `{prefix}secure_passkeys_webauthns_user_id_index` (`user_id`)
) {collate};