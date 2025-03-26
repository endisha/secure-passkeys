CREATE TABLE `{prefix}secure_passkeys_challenges` (
    `id` BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) DEFAULT NULL,
    `challenge_type` ENUM('authentication', 'registration') NOT NULL,
    `challenge` VARCHAR(255) NOT NULL,
    `fingerprint` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `expired_at` TIMESTAMP NULL DEFAULT NULL,
    `used_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `{prefix}secure_passkeys_challenges_challenge_unique` (`challenge`),
    KEY `{prefix}secure_passkeys_challenges_user_id_index` (`user_id`)
) {collate};