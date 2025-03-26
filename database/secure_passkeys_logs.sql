CREATE TABLE `{prefix}secure_passkeys_logs` (
    `id` BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `admin_id` INT(11) DEFAULT NULL,
    `webauthn_id` INT(11) DEFAULT NULL,
    `security_key_name` VARCHAR(255) DEFAULT NULL,
    `aaguid` CHAR(36) DEFAULT NULL,
    `log_type` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `{prefix}secure_passkeys_logs_user_id_index` (`user_id`)
) {collate};