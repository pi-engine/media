CREATE TABLE IF NOT EXISTS `media_storage`
(
    `id`             INT(10) UNSIGNED                   NOT NULL AUTO_INCREMENT,
    `slug`           VARCHAR(255)                                DEFAULT NULL,
    `title`          VARCHAR(255)                       NOT NULL DEFAULT '',
    `user_id`        INT(10) UNSIGNED                   NOT NULL DEFAULT '0',
    `company_id`     INT(10) UNSIGNED                   NOT NULL DEFAULT '0',
    `access`         ENUM ('public', 'company', 'user') NOT NULL DEFAULT 'public',
    `storage`        VARCHAR(32)                        NOT NULL DEFAULT '',
    `type`           VARCHAR(32)                        NOT NULL DEFAULT '',
    `extension`      VARCHAR(32)                        NOT NULL DEFAULT '',
    `size`           INT(10) UNSIGNED                   NOT NULL DEFAULT '0',
    `download_count` INT(10) UNSIGNED                   NOT NULL DEFAULT '0',
    `status`         TINYINT(1) UNSIGNED                NOT NULL DEFAULT '1',
    `time_create`    INT(10) UNSIGNED                   NOT NULL DEFAULT '0',
    `time_update`    INT(10) UNSIGNED                   NOT NULL DEFAULT '0',
    `information`    JSON,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
);

CREATE TABLE IF NOT EXISTS `media_relation`
(
    `id`               INT(10) UNSIGNED                   NOT NULL AUTO_INCREMENT,
    `storage_id`       INT(10) UNSIGNED                   NOT NULL DEFAULT '0',
    `user_id`          INT(10) UNSIGNED                   NOT NULL DEFAULT '0',
    `company_id`       INT(10) UNSIGNED                   NOT NULL DEFAULT '0',
    `access`           ENUM ('public', 'company', 'user') NOT NULL DEFAULT 'public',
    `relation_module`  VARCHAR(32)                        NOT NULL DEFAULT '',
    `relation_section` VARCHAR(32)                        NOT NULL DEFAULT '',
    `relation_item`    INT(10) UNSIGNED                   NOT NULL DEFAULT '0',
    `status`           TINYINT(1) UNSIGNED                NOT NULL DEFAULT '1',
    `time_create`      INT(10) UNSIGNED                   NOT NULL DEFAULT '0',
    `time_update`      INT(10) UNSIGNED                   NOT NULL DEFAULT '0',
    `information`      JSON,
    PRIMARY KEY (`id`)
);