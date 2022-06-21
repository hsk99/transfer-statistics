/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 50726
 Source Host           : localhost:3306
 Source Schema         : transfer

 Target Server Type    : MySQL
 Target Server Version : 50726
 File Encoding         : 65001

 Date: 21/06/2022 17:02:50
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for project
-- ----------------------------
DROP TABLE IF EXISTS `project`;
CREATE TABLE `project`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `project` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '应用',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '应用' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for statistic
-- ----------------------------
DROP TABLE IF EXISTS `statistic`;
CREATE TABLE `statistic`  (
  `day` int(11) UNSIGNED NOT NULL COMMENT '产生日期',
  `count` int(11) UNSIGNED NOT NULL COMMENT '次数',
  `cost` double UNSIGNED NOT NULL COMMENT '耗时',
  `success_count` int(11) UNSIGNED NOT NULL COMMENT '成功次数',
  `error_count` int(11) UNSIGNED NOT NULL COMMENT '失败次数',
  PRIMARY KEY (`day`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '总统计' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for statistic_project
-- ----------------------------
DROP TABLE IF EXISTS `statistic_project`;
CREATE TABLE `statistic_project`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `day` int(11) UNSIGNED NOT NULL COMMENT '产生日期',
  `project` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '应用',
  `count` int(11) UNSIGNED NOT NULL COMMENT '次数',
  `cost` double UNSIGNED NOT NULL COMMENT '耗时',
  `success_count` int(11) UNSIGNED NOT NULL COMMENT '成功次数',
  `error_count` int(11) UNSIGNED NOT NULL COMMENT '失败次数',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '应用总统计' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for statistic_project_code
-- ----------------------------
DROP TABLE IF EXISTS `statistic_project_code`;
CREATE TABLE `statistic_project_code`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `day` int(11) UNSIGNED NOT NULL COMMENT '产生日期',
  `project` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '应用',
  `code` int(11) NOT NULL COMMENT '状态码',
  `count` int(11) UNSIGNED NOT NULL COMMENT '次数',
  `cost` double UNSIGNED NOT NULL COMMENT '耗时',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '应用状态码统计' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for statistic_project_code_interval
-- ----------------------------
DROP TABLE IF EXISTS `statistic_project_code_interval`;
CREATE TABLE `statistic_project_code_interval`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `day` int(11) UNSIGNED NOT NULL COMMENT '产生日期',
  `time` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '统计时间',
  `project` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '应用',
  `code` int(11) NOT NULL COMMENT '状态码',
  `count` int(11) UNSIGNED NOT NULL COMMENT '次数',
  `cost` double UNSIGNED NOT NULL COMMENT '耗时',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '应用状态码每分钟统计' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for statistic_project_interval
-- ----------------------------
DROP TABLE IF EXISTS `statistic_project_interval`;
CREATE TABLE `statistic_project_interval`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `day` int(8) UNSIGNED NOT NULL COMMENT '产生日期',
  `time` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '统计时间',
  `project` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '应用',
  `count` int(11) UNSIGNED NOT NULL COMMENT '次数',
  `cost` double UNSIGNED NOT NULL COMMENT '耗时',
  `success_count` int(11) UNSIGNED NOT NULL COMMENT '成功次数',
  `error_count` int(11) UNSIGNED NOT NULL COMMENT '失败次数',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '应用每分钟统计' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for statistic_project_ip
-- ----------------------------
DROP TABLE IF EXISTS `statistic_project_ip`;
CREATE TABLE `statistic_project_ip`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `day` int(11) UNSIGNED NOT NULL COMMENT '产生日期',
  `project` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '应用',
  `ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'IP',
  `count` int(11) UNSIGNED NOT NULL COMMENT '次数',
  `cost` double UNSIGNED NOT NULL COMMENT '耗时',
  `success_count` int(11) UNSIGNED NOT NULL COMMENT '成功次数',
  `error_count` int(11) UNSIGNED NOT NULL COMMENT '失败次数',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '应用IP统计' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for statistic_project_ip_code
-- ----------------------------
DROP TABLE IF EXISTS `statistic_project_ip_code`;
CREATE TABLE `statistic_project_ip_code`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `day` int(11) UNSIGNED NOT NULL COMMENT '产生日期',
  `project` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '应用',
  `ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'IP',
  `code` int(11) NOT NULL COMMENT '状态码',
  `count` int(11) UNSIGNED NOT NULL COMMENT '次数',
  `cost` double UNSIGNED NOT NULL COMMENT '耗时',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '应用IP状态码统计' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for statistic_project_ip_code_interval
-- ----------------------------
DROP TABLE IF EXISTS `statistic_project_ip_code_interval`;
CREATE TABLE `statistic_project_ip_code_interval`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `day` int(11) UNSIGNED NOT NULL COMMENT '产生日期',
  `time` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '统计时间',
  `project` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '应用',
  `ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'IP',
  `code` int(11) NOT NULL COMMENT '状态码',
  `count` int(11) UNSIGNED NOT NULL COMMENT '次数',
  `cost` double UNSIGNED NOT NULL COMMENT '耗时',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '应用IP状态码每分钟统计' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for statistic_project_ip_interval
-- ----------------------------
DROP TABLE IF EXISTS `statistic_project_ip_interval`;
CREATE TABLE `statistic_project_ip_interval`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `day` int(11) UNSIGNED NOT NULL COMMENT '产生日期',
  `time` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '统计时间',
  `project` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '应用',
  `ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'IP',
  `count` int(11) UNSIGNED NOT NULL COMMENT '次数',
  `cost` double UNSIGNED NOT NULL COMMENT '耗时',
  `success_count` int(11) UNSIGNED NOT NULL COMMENT '成功次数',
  `error_count` int(11) UNSIGNED NOT NULL COMMENT '失败次数',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '应用IP每分钟统计' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for statistic_project_ip_transfer
-- ----------------------------
DROP TABLE IF EXISTS `statistic_project_ip_transfer`;
CREATE TABLE `statistic_project_ip_transfer`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `day` int(11) UNSIGNED NOT NULL COMMENT '产生日期',
  `project` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '应用',
  `ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'IP',
  `transfer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '调用',
  `count` int(11) UNSIGNED NOT NULL COMMENT '次数',
  `cost` double UNSIGNED NOT NULL COMMENT '耗时',
  `success_count` int(11) UNSIGNED NOT NULL COMMENT '成功次数',
  `error_count` int(11) UNSIGNED NOT NULL COMMENT '失败次数',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '应用IP调用统计' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for statistic_project_ip_transfer_interval
-- ----------------------------
DROP TABLE IF EXISTS `statistic_project_ip_transfer_interval`;
CREATE TABLE `statistic_project_ip_transfer_interval`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `day` int(11) UNSIGNED NOT NULL COMMENT '产生日期',
  `time` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '统计时间',
  `project` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '应用',
  `ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'IP',
  `transfer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '调用',
  `count` int(11) UNSIGNED NOT NULL COMMENT '次数',
  `cost` double UNSIGNED NOT NULL COMMENT '耗时',
  `success_count` int(11) UNSIGNED NOT NULL COMMENT '成功次数',
  `error_count` int(11) UNSIGNED NOT NULL COMMENT '失败次数',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '应用IP调用每分钟统计' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for statistic_project_transfer
-- ----------------------------
DROP TABLE IF EXISTS `statistic_project_transfer`;
CREATE TABLE `statistic_project_transfer`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `day` int(11) UNSIGNED NOT NULL COMMENT '产生日期',
  `project` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '应用',
  `transfer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '调用',
  `count` int(11) UNSIGNED NOT NULL COMMENT '次数',
  `cost` double UNSIGNED NOT NULL COMMENT '耗时',
  `success_count` int(11) UNSIGNED NOT NULL COMMENT '成功次数',
  `error_count` int(11) UNSIGNED NOT NULL COMMENT '失败次数',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '应用调用统计' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for statistic_project_transfer_interval
-- ----------------------------
DROP TABLE IF EXISTS `statistic_project_transfer_interval`;
CREATE TABLE `statistic_project_transfer_interval`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `day` int(8) UNSIGNED NOT NULL COMMENT '产生日期',
  `time` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '统计时间',
  `project` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '应用',
  `transfer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '调用',
  `count` int(11) UNSIGNED NOT NULL COMMENT '次数',
  `cost` double UNSIGNED NOT NULL COMMENT '耗时',
  `success_count` int(11) UNSIGNED NOT NULL COMMENT '成功次数',
  `error_count` int(11) UNSIGNED NOT NULL COMMENT '失败次数',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '应用调用每分钟统计' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for tracing
-- ----------------------------
DROP TABLE IF EXISTS `tracing`;
CREATE TABLE `tracing`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `day` int(11) NOT NULL COMMENT '产生日期',
  `time` datetime(4) NOT NULL COMMENT '调用时间',
  `trace` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '追踪标识',
  `project` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '应用',
  `ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'IP',
  `transfer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '调用',
  `cost_time` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '消耗时长',
  `success` smallint(1) NOT NULL COMMENT '状态',
  `code` int(11) NOT NULL COMMENT '状态码',
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '详情',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '调用记录' ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
