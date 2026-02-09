/*
 Navicat Premium Dump SQL

 Source Server         : nextcloud
 Source Server Type    : MySQL
 Source Server Version : 101115 (10.11.15-MariaDB-ubu2204)
 Source Host           : localhost:3307
 Source Schema         : nextcloud

 Target Server Type    : MySQL
 Target Server Version : 101115 (10.11.15-MariaDB-ubu2204)
 File Encoding         : 65001

 Date: 06/02/2026 14:02:57
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for oc_personal_vault_audit
-- ----------------------------
DROP TABLE IF EXISTS `oc_personal_vault_audit`;
CREATE TABLE `oc_personal_vault_audit`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `pv_audit_user_id_idx`(`user_id` ASC) USING BTREE,
  INDEX `pv_audit_created_at_idx`(`created_at` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 65 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_bin ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oc_personal_vault_otp
-- ----------------------------
DROP TABLE IF EXISTS `oc_personal_vault_otp`;
CREATE TABLE `oc_personal_vault_otp`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `otp_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `expires_at` datetime NOT NULL,
  `last_sent_at` datetime NULL DEFAULT NULL,
  `attempts` int NULL DEFAULT 0,
  `created_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `pv_otp_user_id`(`user_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 20 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_bin ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oc_personal_vault_settings
-- ----------------------------
DROP TABLE IF EXISTS `oc_personal_vault_settings`;
CREATE TABLE `oc_personal_vault_settings`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `pin_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL,
  `failed_attempts` int NULL DEFAULT 0,
  `lockout_until` datetime NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  `vault_folder_id` bigint NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `pv_settings_user_id`(`user_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_bin ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
