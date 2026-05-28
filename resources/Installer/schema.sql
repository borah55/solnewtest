-- Solnew - database schema
--
-- Improvements vs. the original:
--   * utf8mb4 charset on every table (was latin1)
--   * UNIQUE constraint on users.user_email (prevents duplicate signups)
--   * activation_hash / reset_hash / rememberme_token are all indexed
--   * dead `failed_history` table removed (was created but never used)
--   * `password` column on admin_details widened to fit bcrypt hashes

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

-- ---------- admin_details ----------
DROP TABLE IF EXISTS `admin_details`;
CREATE TABLE `admin_details` (
    `id`        BIGINT NOT NULL AUTO_INCREMENT,
    `user_name` VARCHAR(32) NOT NULL,
    `password`  VARCHAR(255) NOT NULL,
    `token`     VARCHAR(128) NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_name` (`user_name`),
    KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- ads ----------
DROP TABLE IF EXISTS `ads`;
CREATE TABLE `ads` (
    `id`   INT NOT NULL AUTO_INCREMENT,
    `type` INT NOT NULL,
    `code` TEXT NOT NULL,
    PRIMARY KEY (`id`),
    KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- claims_hashes (one-shot redemption tokens) ----------
DROP TABLE IF EXISTS `claims_hashes`;
CREATE TABLE `claims_hashes` (
    `id`         BIGINT NOT NULL AUTO_INCREMENT,
    `user_id`    BIGINT NOT NULL,
    `hash`       VARCHAR(64) NOT NULL,
    `win_amount` DECIMAL(20,8) NOT NULL,
    `time`       BIGINT NOT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- claims_registered (audit log of paid-out claims) ----------
DROP TABLE IF EXISTS `claims_registered`;
CREATE TABLE `claims_registered` (
    `id`              BIGINT NOT NULL AUTO_INCREMENT,
    `user_id`         BIGINT NOT NULL,
    `user_name`       VARCHAR(32) NOT NULL,
    `time`            BIGINT NOT NULL,
    `amount_credited` DECIMAL(20,8) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- config (key/value store for site settings) ----------
DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
    `id`        INT NOT NULL AUTO_INCREMENT,
    `parameter` VARCHAR(64) NOT NULL,
    `value`     TEXT NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `parameter` (`parameter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `config` (`parameter`, `value`) VALUES
    ('website_name',            'Solnew'),
    ('website_homepage_title',  'High-paying crypto faucet'),
    ('coin_name',               'Bitcoin'),
    ('coin_abbreviation',       'BTC'),
    ('contact_email_address',   ''),
    ('no_reply_email_address',  ''),
    ('faucet_reward',           '0.00000050'),
    ('faucet_time_limit',       '30'),
    ('referral_percentage',     '25'),
    ('captcha_used',            '0'),
    ('site_key',                ''),
    ('secret_key',              ''),
    ('shortlink_preference',    '0'),
    ('ouo_api_key',             ''),
    ('shortest_api_token',      ''),
    ('automated_withdrawals',   'true'),
    ('faucetpay_api_key',       ''),
    ('use_smtp',                'false'),
    ('smtp_auth',               'true'),
    ('email_smtp_encryption',   'ssl'),
    ('email_smtp_host',         ''),
    ('email_smtp_port',         '465'),
    ('email_smtp_username',     ''),
    ('email_smtp_password',     ''),
    ('email_confirmation',      'false'),
    ('stats_Total_Users',       '0'),
    ('stats_Claims_Made',       '0'),
    ('stats_Amount_Claimed',    '0.00000000'),
    ('anti_ad_blocker',         '0');

-- ---------- email_updates (pending email-change confirmations) ----------
DROP TABLE IF EXISTS `email_updates`;
CREATE TABLE `email_updates` (
    `id`           BIGINT NOT NULL AUTO_INCREMENT,
    `user_id`      BIGINT NOT NULL,
    `email`        VARCHAR(255) NOT NULL,
    `confirm_code` VARCHAR(64) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id` (`user_id`),
    KEY `confirm_code` (`confirm_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- error_log ----------
DROP TABLE IF EXISTS `error_log`;
CREATE TABLE `error_log` (
    `id`      BIGINT NOT NULL AUTO_INCREMENT,
    `message` TEXT NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- referral_returns ----------
DROP TABLE IF EXISTS `referral_returns`;
CREATE TABLE `referral_returns` (
    `id`          BIGINT NOT NULL AUTO_INCREMENT,
    `user_name`   VARCHAR(32) NOT NULL,
    `referred_by` BIGINT NOT NULL,
    `amount`      DECIMAL(20,8) NOT NULL,
    `time`        BIGINT NOT NULL,
    PRIMARY KEY (`id`),
    KEY `referred_by` (`referred_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- users ----------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `user_id`               BIGINT NOT NULL AUTO_INCREMENT,
    `user_name`             VARCHAR(32) NOT NULL,
    `user_email`            VARCHAR(255) NOT NULL,
    `password_hash`         VARCHAR(255) NOT NULL,
    `user_verified`         TINYINT NOT NULL DEFAULT 0,
    `account_status`        TINYINT NOT NULL DEFAULT 1,
    `admin_powers`          TINYINT NOT NULL DEFAULT 0,
    `activation_hash`       VARCHAR(64) DEFAULT NULL,
    `reset_hash`            VARCHAR(64) DEFAULT NULL,
    `rememberme_token`      VARCHAR(64) DEFAULT NULL,
    `failed_logins`         MEDIUMINT NOT NULL DEFAULT 0,
    `last_failed_login`     BIGINT NOT NULL DEFAULT 0,
    `last_logged_in`        DATETIME DEFAULT NULL,
    `registration_datetime` DATETIME NOT NULL,
    `registration_ip`       VARCHAR(45) NOT NULL DEFAULT '',
    `session_ip`            VARCHAR(45) NOT NULL DEFAULT '',
    `last_claimed`          BIGINT NOT NULL DEFAULT 0,
    `claims_made`           INT NOT NULL DEFAULT 0,
    `referred_income`       DECIMAL(20,8) NOT NULL DEFAULT 0,
    `referral_income`       DECIMAL(20,8) NOT NULL DEFAULT 0,
    `referral`              INT NOT NULL DEFAULT 0,
    `address`               VARCHAR(128) NOT NULL DEFAULT '',
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `user_name` (`user_name`),
    UNIQUE KEY `user_email` (`user_email`),
    KEY `referral` (`referral`),
    KEY `activation_hash` (`activation_hash`),
    KEY `reset_hash` (`reset_hash`),
    KEY `rememberme_token` (`rememberme_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- withdrawals ----------
DROP TABLE IF EXISTS `withdrawals`;
CREATE TABLE `withdrawals` (
    `id`      INT NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT NOT NULL,
    `address` VARCHAR(128) NOT NULL,
    `amount`  DECIMAL(20,8) NOT NULL DEFAULT 0,
    `status`  INT NOT NULL DEFAULT 0,
    `time`    BIGINT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
