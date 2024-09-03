/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

CREATE TABLE IF NOT EXISTS `#__ars_categories` (
    `id`                bigint(20)                     NOT NULL AUTO_INCREMENT,
    `asset_id`          int(10) UNSIGNED               NOT NULL DEFAULT 0,
    `title`             varchar(255)                   NOT NULL,
    `alias`             varchar(255)                   NOT NULL,
    `description`       mediumtext,
    `type`              enum ('normal','bleedingedge') NOT NULL DEFAULT 'normal',
    `directory`         varchar(255)                   NOT NULL DEFAULT 'arsrepo',
    `created`           datetime                       NULL     DEFAULT NULL,
    `created_by`        int(11)                        NOT NULL DEFAULT '0',
    `modified`          datetime                       NULL     DEFAULT NULL,
    `modified_by`       int(11)                        NOT NULL DEFAULT '0',
    `checked_out`       int(11)                        NOT NULL DEFAULT '0',
    `checked_out_time`  datetime                       NULL     DEFAULT NULL,
    `ordering`          bigint(20)                     NOT NULL DEFAULT '0',
    `access`            int(11)                        NOT NULL DEFAULT '0',
    `show_unauth_links` TINYINT                        NOT NULL DEFAULT '0',
    `redirect_unauth`   VARCHAR(255)                   NOT NULL DEFAULT '',
    `published`         int(11)                        NOT NULL DEFAULT '1',
    `is_supported`      TINYINT                        NOT NULL DEFAULT '1',
    `language`          char(7)                        NOT NULL DEFAULT '*',
    PRIMARY KEY (`id`),
    KEY `#__ars_categories_published` (`published`)
) ENGINE InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ars_releases` (
    `id`                bigint(20)                          NOT NULL AUTO_INCREMENT,
    `category_id`       BIGINT(20) UNSIGNED                 NOT NULL,
    `version`           VARCHAR(255)                        NOT NULL,
    `alias`             VARCHAR(255)                        NOT NULL,
    `maturity`          ENUM ('alpha','beta','rc','stable') NOT NULL DEFAULT 'beta',
    `notes`             TEXT                                NULL,
    `hits`              BIGINT(20) UNSIGNED                 NOT NULL DEFAULT 0,
    `created`           datetime                            NULL     DEFAULT NULL,
    `created_by`        int(11)                             NOT NULL DEFAULT '0',
    `modified`          datetime                            NULL     DEFAULT NULL,
    `modified_by`       int(11)                             NOT NULL DEFAULT '0',
    `checked_out`       int(11)                             NOT NULL DEFAULT '0',
    `checked_out_time`  datetime                            NULL     DEFAULT NULL,
    `ordering`          bigint(20) unsigned                 NOT NULL,
    `access`            int(11)                             NOT NULL DEFAULT '0',
    `show_unauth_links` TINYINT                             NOT NULL DEFAULT '0',
    `redirect_unauth`   VARCHAR(255)                        NOT NULL DEFAULT '',
    `published`         tinyint(1)                          NOT NULL DEFAULT '1',
    `language`          char(7)                             NOT NULL DEFAULT '*',
    PRIMARY KEY `id` (`id`),
    KEY `#__ars_releases_category_id` (`category_id`),
    KEY `#__ars_releases_published` (`published`)
) ENGINE InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ars_items` (
    `id`                bigint(20)          NOT NULL AUTO_INCREMENT,
    `release_id`        BIGINT(20)          NOT NULL,
    `title`             VARCHAR(255)        NOT NULL,
    `alias`             VARCHAR(255)        NOT NULL,
    `description`       MEDIUMTEXT          NOT NULL,
    `type`              ENUM ('link','file'),
    `filename`          VARCHAR(255)        NULL     DEFAULT '',
    `url`               VARCHAR(255)        NULL     DEFAULT '',
    `updatestream`      BIGINT(20) UNSIGNED          DEFAULT NULL,
    `md5`               varchar(32)                  DEFAULT NULL,
    `sha1`              varchar(64)                  DEFAULT NULL,
    `sha256`            varchar(64)                  DEFAULT NULL,
    `sha384`            varchar(96)                  DEFAULT NULL,
    `sha512`            varchar(128)                 DEFAULT NULL,
    `filesize`          int(10) unsigned             DEFAULT NULL,
    `hits`              BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    `created`           datetime            NULL     DEFAULT NULL,
    `created_by`        int(11)             NOT NULL DEFAULT '0',
    `modified`          datetime            NULL     DEFAULT NULL,
    `modified_by`       int(11)             NOT NULL DEFAULT '0',
    `checked_out`       int(11)             NOT NULL DEFAULT '0',
    `checked_out_time`  datetime            NULL     DEFAULT NULL,
    `ordering`          bigint(20) unsigned NOT NULL,
    `access`            int(11)             NOT NULL DEFAULT '0',
    `show_unauth_links` TINYINT             NOT NULL DEFAULT '0',
    `redirect_unauth`   VARCHAR(255)        NOT NULL DEFAULT '',
    `published`         tinyint(1)          NOT NULL DEFAULT '1',
    `language`          char(7)             NOT NULL DEFAULT '*',
    `environments`      varchar(255)                 DEFAULT NULL,
    PRIMARY KEY `id` (`id`),
    KEY `#__ars_items_release_id` (`release_id`),
    KEY `#__ars_items_updatestream` (`updatestream`),
    KEY `#__ars_items_published` (`published`)
) ENGINE InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ars_log` (
    `id`          bigint(20)          NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT(20) UNSIGNED NOT NULL,
    `item_id`     BIGINT(20) UNSIGNED NOT NULL,
    `accessed_on` datetime            NULL     DEFAULT NULL,
    `referer`     VARCHAR(255)        NOT NULL,
    `ip`          VARCHAR(255)        NOT NULL,
    `authorized`  TINYINT(1)          NOT NULL DEFAULT '1',
    PRIMARY KEY `id` (`id`),
    KEY `#__ars_log_accessed` (`accessed_on`),
    KEY `#__ars_log_authorized` (`authorized`),
    KEY `#__ars_log_itemid` (`item_id`),
    KEY `#__ars_log_userid` (`user_id`)
) ENGINE InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ars_updatestreams` (
    `id`               bigint(20)          NOT NULL AUTO_INCREMENT,
    `name`             VARCHAR(255)        NOT NULL,
    `alias`            VARCHAR(255)        NOT NULL,
    `type`             ENUM ('components','libraries','modules','packages',
        'plugins','files','templates')     NOT NULL DEFAULT 'components',
    `element`          VARCHAR(255)        NOT NULL,
    `category`         BIGINT(20) UNSIGNED NOT NULL,
    `packname`         VARCHAR(255),
    `client_id`        int(1)              NOT NULL DEFAULT '1',
    `folder`           varchar(255)                 DEFAULT '',
    `created`          datetime            NULL     DEFAULT NULL,
    `created_by`       int(11)             NOT NULL DEFAULT '0',
    `modified`         datetime            NULL     DEFAULT NULL,
    `modified_by`      int(11)             NOT NULL DEFAULT '0',
    `checked_out`      int(11)             NOT NULL DEFAULT '0',
    `checked_out_time` datetime            NULL     DEFAULT NULL,
    `published`        int(11)             NOT NULL DEFAULT '1',
    PRIMARY KEY `id` (`id`),
    KEY `#__ars_updatestreams_published` (`published`)
) ENGINE InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ars_autoitemdesc` (
    `id`                bigint(20)          NOT NULL AUTO_INCREMENT,
    `category`          bigint(20) unsigned NOT NULL,
    `packname`          varchar(255)                 DEFAULT NULL,
    `title`             varchar(255)        NOT NULL,
    `access`            int(11)             NOT NULL DEFAULT '0',
    `show_unauth_links` tinyint             NOT NULL DEFAULT '0',
    `redirect_unauth`   varchar(255)        NOT NULL DEFAULT '',
    `description`       MEDIUMTEXT          NOT NULL,
    `environments`      varchar(100)                 DEFAULT NULL,
    `created`           datetime            NULL     DEFAULT NULL,
    `created_by`        int(11)             NOT NULL DEFAULT '0',
    `modified`          datetime            NULL     DEFAULT NULL,
    `modified_by`       int(11)             NOT NULL DEFAULT '0',
    `checked_out`       int(11)             NOT NULL DEFAULT '0',
    `checked_out_time`  datetime            NULL     DEFAULT NULL,
    `published`         int(11)             NOT NULL DEFAULT '1',
    PRIMARY KEY `id` (`id`)
) ENGINE InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ars_environments` (
    `id`               bigint(20)   NOT NULL AUTO_INCREMENT,
    `title`            varchar(100) NOT NULL DEFAULT '',
    `xmltitle`         varchar(20)  NOT NULL DEFAULT '1.0',
    `created`          datetime     NULL     DEFAULT NULL,
    `created_by`       int(11)      NOT NULL DEFAULT '0',
    `modified`         datetime     NULL     DEFAULT NULL,
    `modified_by`      int(11)      NOT NULL DEFAULT '0',
    `checked_out`      int(11)      NOT NULL DEFAULT '0',
    `checked_out_time` datetime     NULL     DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ars_dlidlabels` (
    `id`               bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id`          bigint(20) unsigned NOT NULL,
    `primary`          TINYINT(1)          NOT NULL DEFAULT 0,
    `title`            varchar(255)        NOT NULL DEFAULT '',
    `dlid`             CHAR(32)            NOT NULL,
    `published`        tinyint(3)          NOT NULL DEFAULT '1',
    `created_by`       bigint(20)          NOT NULL DEFAULT '0',
    `created`          datetime            NULL     DEFAULT NULL,
    `modified_by`      bigint(20)          NOT NULL DEFAULT '0',
    `modified`         datetime            NULL     DEFAULT NULL,
    `checked_out`      int(11)             NOT NULL DEFAULT '0',
    `checked_out_time` datetime            NULL     DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `#__ars_environments` (`id`, `title`, `xmltitle`)
VALUES (1, 'Joomla! 1.5', 'joomla/1.5'),
       (2, 'Joomla! 1.6', 'joomla/1.6'),
       (3, 'Joomla! 1.7', 'joomla/1.7'),
       (4, 'Joomla! 2.5', 'joomla/2.5'),
       (5, 'Joomla! 3.x', 'joomla/3'),
       (6, 'Joomla! 3.0', 'joomla/3.0'),
       (7, 'Joomla! 3.1', 'joomla/3.1'),
       (8, 'Joomla! 3.2', 'joomla/3.2'),
       (9, 'Joomla! 3.3', 'joomla/3.3'),
       (10, 'Joomla! 3.4', 'joomla/3.4'),
       (11, 'Joomla! 3.5', 'joomla/3.5'),
       (28, 'Joomla! 3.6', 'joomla/3.6'),
       (29, 'Joomla! 3.7', 'joomla/3.7'),
       (30, 'Joomla! 3.8', 'joomla/3.8'),
       (31, 'Joomla! 3.9', 'joomla/3.9'),
       (42, 'Joomla! 3.10', 'joomla/3.10'),
       (32, 'Joomla! 4.0', 'joomla/4.0'),
       (33, 'Joomla! 4.1', 'joomla/4.1'),
       (12, 'Linux (32-bit)', 'linux/x86'),
       (13, 'Linux (64-bit)', 'linux/x86-64'),
       (14, 'macOS', 'macosx/10'),
       (15, 'WHMCS 4.5.2', 'whmcs/4.5.2'),
       (16, 'Windows 7', 'win/7'),
       (17, 'Windows XP', 'win/xp'),
       (34, 'Windows 8', 'win/8'),
       (35, 'Windows 10', 'win/10'),
       (18, 'WordPress 3.2+', 'wordpress/3'),
       (19, 'WordPress 4.x', 'wordpress/4'),
       (36, 'WordPress 5.x', 'wordpress/5'),
       (37, 'ClassicPress 1.x', 'classicpress/1'),
       (20, 'ePub', 'epub/3.0'),
       (21, 'PDF', 'pdf/1.5'),
       (22, 'PHP 5.2', 'php/5.2'),
       (23, 'PHP 5.3', 'php/5.3'),
       (24, 'PHP 5.4', 'php/5.4'),
       (25, 'PHP 5.5', 'php/5.5'),
       (26, 'PHP 5.6', 'php/5.6'),
       (27, 'PHP 7.0', 'php/7.0'),
       (38, 'PHP 7.1', 'php/7.1'),
       (39, 'PHP 7.2', 'php/7.2'),
       (40, 'PHP 7.3', 'php/7.3'),
       (41, 'PHP 8.0', 'php/8.0'),
       (43, 'PHP 8.1', 'php/8.1')
;

-- UCM Content types (for tags)
DELETE FROM `#__content_types` WHERE `type_alias` = 'com_ars.category';
DELETE FROM `#__content_types` WHERE `type_alias` = 'com_ars.release';

INSERT INTO `#__content_types` (`type_id`, `type_title`, `type_alias`, `table`, `rules`, `field_mappings`, `router`, `content_history_options`) VALUES (19, 'ARS Category', 'com_ars.category', '{   		"special": {   			"dbtable": "#__ars_categories",   			"key": "id",   			"type": "CategoryTable",   			"prefix": "Akeeba\\\\Component\\\\ARS\\\\Administrator\\\\Table\\\\",   			"config": "array()"   		},   		"common": {   			"dbtable": "#__ucm_content",   			"key": "ucm_id",   			"type": "Corecontent",   			"prefix": "Joomla\\\\CMS\\\\Table\\\\",   			"config": "array()"   		}   	}', '', '{   		"common": {   			"core_content_item_id": "id",   			"core_title": "title",   			"core_state": "published",   			"core_alias": "alias",   			"core_created_time": "created",   			"core_modified_time": "modified",   			"core_body": "description",   			"core_hits": "null",   			"core_publish_up": "null",   			"core_publish_down": "null",   			"access": "access",   			"core_params": "null",   			"core_featured": "null",   			"core_metadata": "null",   			"core_language": "language",   			"core_images": "null",   			"core_urls": "null",   			"core_version": "null",   			"core_ordering": "ordering",   			"core_metakey": "null",   			"core_metadesc": "null",   			"core_catid": "null",   			"asset_id": "asset_id"   		},   		"special": {   			"type": "type",   			"directory": "directory",   			"show_unauth_links": "show_unauth_links",   			"redirect_unauth": "redirect_unauth",   			"is_supported": "is_supported"   		}   	}   ', 'Akeeba\\Component\\ATS\\Site\\Helper\\Route::getCategoryRoute', '');
INSERT INTO `#__content_types` (`type_id`, `type_title`, `type_alias`, `table`, `rules`, `field_mappings`, `router`, `content_history_options`) VALUES (20, 'ARS Release', 'com_ars.release', '{   		"special": {   			"dbtable": "#__ars_releases",   			"key": "id",   			"type": "ReleaseTable",   			"prefix": "Akeeba\\\\Component\\\\ARS\\\\Administrator\\\\Table\\\\",   			"config": "array()"   		},   		"common": {   			"dbtable": "#__ucm_content",   			"key": "ucm_id",   			"type": "Corecontent",   			"prefix": "Joomla\\\\CMS\\\\Table\\\\",   			"config": "array()"   		}   	}', '', '{   		"common": {   			"core_content_item_id": "id",   			"core_title": "version",   			"core_state": "published",   			"core_alias": "alias",   			"core_created_time": "created",   			"core_modified_time": "modified",   			"core_body": "notes",   			"core_hits": "hits",   			"core_publish_up": "null",   			"core_publish_down": "null",   			"access": "access",   			"core_params": "null",   			"core_featured": "null",   			"core_metadata": "null",   			"core_language": "language",   			"core_images": "null",   			"core_urls": "null",   			"core_version": "null",   			"core_ordering": "ordering",   			"core_metakey": "null",   			"core_metadesc": "null",   			"core_catid": "null",   			"asset_id": "null"   		},   		"special": {   			"category_id": "category_id",   			"maturity": "maturity",   			"show_unauth_links": "show_unauth_links",   			"redirect_unauth": "redirect_unauth"   		}   	}','Akeeba\\Component\\ATS\\Site\\Helper\\Route::getReleaseRoute', '');
