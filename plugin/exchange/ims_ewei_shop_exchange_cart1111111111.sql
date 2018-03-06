/*
Navicat MySQL Data Transfer

Source Server         : user
Source Server Version : 50553
Source Host           : localhost:3306
Source Database       : test1

Target Server Type    : MYSQL
Target Server Version : 50553
File Encoding         : 65001

Date: 2018-01-23 15:13:41
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `ims_ewei_shop_exchange_cart`
-- ----------------------------
DROP TABLE IF EXISTS `ims_ewei_shop_exchange_cart`;
CREATE TABLE `ims_ewei_shop_exchange_cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) DEFAULT NULL,
  `openid` varchar(100) DEFAULT NULL,
  `goodsid` int(11) DEFAULT NULL,
  `total` int(10) DEFAULT '1',
  `marketprice` decimal(10,2) DEFAULT NULL,
  `optionid` int(11) DEFAULT NULL,
  `selected` tinyint(1) DEFAULT '1',
  `deleted` tinyint(1) DEFAULT '0',
  `merchid` int(11) DEFAULT '0',
  `title` varchar(255) DEFAULT NULL,
  `groupid` int(11) DEFAULT NULL,
  `serial` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Records of ims_ewei_shop_exchange_cart
-- ----------------------------

-- ----------------------------
-- Table structure for `ims_ewei_shop_exchange_code`
-- ----------------------------
DROP TABLE IF EXISTS `ims_ewei_shop_exchange_code`;
CREATE TABLE `ims_ewei_shop_exchange_code` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupid` int(11) NOT NULL DEFAULT '0',
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `endtime` datetime NOT NULL DEFAULT '2016-10-01 00:00:00',
  `status` int(2) NOT NULL DEFAULT '1',
  `openid` varchar(255) NOT NULL DEFAULT '',
  `count` int(11) NOT NULL DEFAULT '0',
  `key` varchar(255) NOT NULL DEFAULT '',
  `type` int(11) NOT NULL DEFAULT '0',
  `scene` int(11) NOT NULL DEFAULT '0',
  `qrcode_url` varchar(255) NOT NULL DEFAULT '',
  `serial` varchar(255) NOT NULL DEFAULT '',
  `balancestatus` int(11) DEFAULT '1',
  `redstatus` int(11) DEFAULT '1',
  `scorestatus` int(11) DEFAULT '1',
  `couponstatus` int(11) DEFAULT '1',
  `goodsstatus` int(11) DEFAULT NULL,
  `repeatcount` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`,`key`)
) ENGINE=MyISAM AUTO_INCREMENT=133 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Records of ims_ewei_shop_exchange_code
-- ----------------------------
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('2', '1', '3', '2017-09-19 20:56:20', '1', '', '0', '68411872', '3', '36466', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=68411872', 'DH201709182', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('3', '4', '3', '2017-10-21 14:25:07', '1', '', '0', '02191229', '3', '83179', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=02191229', 'DH201709213', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('4', '4', '3', '2017-10-21 14:25:07', '1', '', '0', '14538774', '3', '82294', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=14538774', 'DH201709214', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('5', '4', '3', '2017-10-21 14:25:07', '1', '', '0', '24711096', '3', '37928', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=24711096', 'DH201709215', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('6', '4', '3', '2017-10-21 14:25:07', '1', '', '0', '38324402', '3', '77707', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=38324402', 'DH201709216', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('7', '4', '3', '2017-10-21 14:25:07', '1', '', '0', '88941333', '3', '83701', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=88941333', 'DH201709217', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('8', '4', '3', '2017-10-21 14:25:07', '1', '', '0', '81832051', '3', '79151', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=81832051', 'DH201709218', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('9', '4', '3', '2017-10-21 14:25:08', '1', '', '0', '40336685', '3', '36631', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=40336685', 'DH201709219', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('10', '4', '3', '2017-10-21 14:25:08', '1', '', '0', '22362775', '3', '28199', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=22362775', 'DH2017092110', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('11', '4', '3', '2017-10-21 14:25:08', '1', '', '0', '08669883', '3', '89777', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=08669883', 'DH2017092111', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('12', '4', '3', '2017-10-21 14:25:08', '1', '', '0', '71869912', '3', '21595', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=71869912', 'DH2017092112', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('13', '4', '3', '2017-10-21 14:25:08', '1', '', '0', '78678989', '3', '66364', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=78678989', 'DH2017092113', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('14', '4', '3', '2017-10-21 14:25:09', '1', '', '0', '10068527', '3', '19474', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=10068527', 'DH2017092114', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('15', '4', '3', '2017-10-21 14:25:09', '1', '', '0', '88166031', '3', '60605', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=88166031', 'DH2017092115', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('16', '4', '3', '2017-10-21 14:25:09', '1', '', '0', '58239822', '3', '31418', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=58239822', 'DH2017092116', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('17', '4', '3', '2017-10-21 14:25:09', '1', '', '0', '02904646', '3', '980', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=02904646', 'DH2017092117', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('18', '4', '3', '2017-10-21 14:25:09', '1', '', '0', '57271333', '3', '33030', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=57271333', 'DH2017092118', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('19', '4', '3', '2017-10-21 14:25:09', '1', '', '0', '55782410', '3', '31843', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=55782410', 'DH2017092119', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('20', '4', '3', '2017-10-21 14:25:09', '1', '', '0', '28851035', '3', '88523', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=28851035', 'DH2017092120', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('21', '4', '3', '2017-10-21 14:25:10', '1', '', '0', '42511526', '3', '21961', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=42511526', 'DH2017092121', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('22', '4', '3', '2017-10-21 14:25:10', '1', '', '0', '11867613', '3', '73011', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=11867613', 'DH2017092122', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('23', '4', '3', '2017-10-21 14:25:10', '1', '', '0', '59233298', '3', '85416', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=59233298', 'DH2017092123', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('24', '4', '3', '2017-10-21 14:25:10', '1', '', '0', '92707886', '3', '64145', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=92707886', 'DH2017092124', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('25', '4', '3', '2017-10-21 14:25:10', '1', '', '0', '81562962', '3', '53531', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=81562962', 'DH2017092125', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('26', '4', '3', '2017-10-21 14:25:10', '1', '', '0', '14849112', '3', '69047', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=14849112', 'DH2017092126', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('27', '4', '3', '2017-10-21 14:25:10', '1', '', '0', '69186996', '3', '78870', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=69186996', 'DH2017092127', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('28', '4', '3', '2017-10-21 14:25:10', '1', '', '0', '23080838', '3', '31589', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=23080838', 'DH2017092128', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('29', '4', '3', '2017-10-21 14:25:11', '1', '', '0', '30912667', '3', '96421', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=30912667', 'DH2017092129', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('30', '4', '3', '2017-10-21 14:25:11', '1', '', '0', '17527167', '3', '89444', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=17527167', 'DH2017092130', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('31', '4', '3', '2017-10-21 14:25:11', '1', '', '0', '92607093', '3', '24909', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=92607093', 'DH2017092131', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('32', '4', '3', '2017-10-21 14:25:11', '1', '', '0', '77231911', '3', '82657', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=77231911', 'DH2017092132', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('33', '5', '5', '2020-01-01 23:08:20', '1', '', '0', '35350365', '3', '52482', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=35350365', 'DH2017102333', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('34', '5', '5', '2020-01-01 23:08:20', '1', '', '0', '74243033', '3', '12558', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=74243033', 'DH2017102334', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('35', '5', '5', '2020-01-01 23:08:20', '1', '', '0', '66889136', '3', '852', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=66889136', 'DH2017102335', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('36', '5', '5', '2020-01-01 23:08:21', '1', '', '0', '14467770', '3', '73572', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=14467770', 'DH2017102336', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('37', '5', '5', '2020-01-01 23:08:21', '1', '', '0', '23126675', '3', '12763', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=23126675', 'DH2017102337', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('38', '5', '5', '2020-01-01 23:08:21', '1', '', '0', '48511362', '3', '39600', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=48511362', 'DH2017102338', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('39', '5', '5', '2020-01-01 23:08:21', '1', '', '0', '57856661', '3', '10169', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=57856661', 'DH2017102339', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('40', '5', '5', '2020-01-01 23:08:21', '1', '', '0', '39837368', '3', '21451', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=39837368', 'DH2017102340', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('41', '5', '5', '2020-01-01 23:08:21', '1', '', '0', '81427443', '3', '41520', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=81427443', 'DH2017102341', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('42', '5', '5', '2020-01-01 23:08:21', '1', '', '0', '95763827', '3', '39991', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=95763827', 'DH2017102342', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('43', '5', '5', '2020-01-01 23:08:22', '1', '', '0', '81940577', '3', '75535', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=81940577', 'DH2017102343', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('44', '5', '5', '2020-01-01 23:08:22', '1', '', '0', '19127317', '3', '92298', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=19127317', 'DH2017102344', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('45', '5', '5', '2020-01-01 23:08:22', '1', '', '0', '36261153', '3', '90226', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=36261153', 'DH2017102345', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('46', '5', '5', '2020-01-01 23:08:22', '1', '', '0', '53379142', '3', '94352', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=53379142', 'DH2017102346', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('47', '5', '5', '2020-01-01 23:08:22', '1', '', '0', '46071417', '3', '11368', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=46071417', 'DH2017102347', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('48', '5', '5', '2020-01-01 23:08:22', '1', '', '0', '43636616', '3', '41459', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=43636616', 'DH2017102348', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('49', '5', '5', '2020-01-01 23:08:22', '1', '', '0', '20973856', '3', '89997', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=20973856', 'DH2017102349', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('50', '5', '5', '2020-01-01 23:08:23', '1', '', '0', '73005049', '3', '2884', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=73005049', 'DH2017102350', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('51', '5', '5', '2020-01-01 23:08:23', '1', '', '0', '07627354', '3', '39170', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=07627354', 'DH2017102351', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('52', '5', '5', '2020-01-01 23:08:23', '1', '', '0', '39481928', '3', '6327', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=39481928', 'DH2017102352', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('53', '5', '5', '2020-01-01 23:08:23', '1', '', '0', '23821773', '3', '38718', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=23821773', 'DH2017102353', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('54', '5', '5', '2020-01-01 23:08:23', '1', '', '0', '17879089', '3', '83793', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=17879089', 'DH2017102354', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('55', '5', '5', '2020-01-01 23:08:23', '1', '', '0', '54473909', '3', '25489', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=54473909', 'DH2017102355', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('56', '5', '5', '2020-01-01 23:08:23', '1', '', '0', '35070286', '3', '55237', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=35070286', 'DH2017102356', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('57', '5', '5', '2020-01-01 23:08:23', '1', '', '0', '85151402', '3', '78187', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=85151402', 'DH2017102357', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('58', '5', '5', '2020-01-01 23:08:24', '1', '', '0', '54713649', '3', '32807', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=54713649', 'DH2017102358', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('59', '5', '5', '2020-01-01 23:08:24', '1', '', '0', '09788062', '3', '42808', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=09788062', 'DH2017102359', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('60', '5', '5', '2020-01-01 23:08:24', '1', '', '0', '37590305', '3', '49048', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=37590305', 'DH2017102360', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('61', '5', '5', '2020-01-01 23:08:24', '1', '', '0', '74526377', '3', '89463', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=74526377', 'DH2017102361', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('62', '5', '5', '2020-01-01 23:08:24', '1', '', '0', '84905952', '3', '59473', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=84905952', 'DH2017102362', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('63', '5', '5', '2020-01-01 23:08:24', '1', '', '0', '64712273', '3', '6342', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=64712273', 'DH2017102363', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('64', '5', '5', '2020-01-01 23:08:24', '1', '', '0', '72849308', '3', '56049', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=72849308', 'DH2017102364', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('65', '5', '5', '2020-01-01 23:08:25', '1', '', '0', '15916480', '3', '84461', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=15916480', 'DH2017102365', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('66', '5', '5', '2020-01-01 23:08:25', '1', '', '0', '26957587', '3', '65836', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=26957587', 'DH2017102366', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('67', '5', '5', '2020-01-01 23:08:25', '1', '', '0', '91149721', '3', '89994', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=91149721', 'DH2017102367', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('68', '5', '5', '2020-01-01 23:08:25', '1', '', '0', '47203065', '3', '10807', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=47203065', 'DH2017102368', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('69', '5', '5', '2020-01-01 23:08:25', '1', '', '0', '42806830', '3', '21988', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=42806830', 'DH2017102369', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('70', '5', '5', '2020-01-01 23:08:25', '1', '', '0', '64251582', '3', '83115', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=64251582', 'DH2017102370', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('71', '5', '5', '2020-01-01 23:08:25', '1', '', '0', '46161219', '3', '74497', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=46161219', 'DH2017102371', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('72', '5', '5', '2020-01-01 23:08:26', '1', '', '0', '04823023', '3', '57584', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=04823023', 'DH2017102372', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('73', '5', '5', '2020-01-01 23:08:26', '1', '', '0', '80444806', '3', '26316', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=80444806', 'DH2017102373', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('74', '5', '5', '2020-01-01 23:08:26', '1', '', '0', '46016590', '3', '32010', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=46016590', 'DH2017102374', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('75', '5', '5', '2020-01-01 23:08:26', '1', '', '0', '83829358', '3', '30924', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=83829358', 'DH2017102375', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('76', '5', '5', '2020-01-01 23:08:26', '1', '', '0', '67614150', '3', '68964', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=67614150', 'DH2017102376', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('77', '5', '5', '2020-01-01 23:08:26', '1', '', '0', '29021193', '3', '36399', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=29021193', 'DH2017102377', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('78', '5', '5', '2020-01-01 23:08:26', '1', '', '0', '29900265', '3', '22300', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=29900265', 'DH2017102378', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('79', '5', '5', '2020-01-01 23:08:27', '1', '', '0', '71582357', '3', '92679', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=71582357', 'DH2017102379', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('80', '5', '5', '2020-01-01 23:08:27', '1', '', '0', '92239049', '3', '80277', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=92239049', 'DH2017102380', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('81', '5', '5', '2020-01-01 23:08:27', '1', '', '0', '70014526', '3', '63898', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=70014526', 'DH2017102381', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('82', '5', '5', '2020-01-01 23:08:27', '1', '', '0', '98858171', '3', '57273', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=98858171', 'DH2017102382', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('83', '5', '5', '2020-01-01 23:08:27', '1', '', '0', '25262305', '3', '19077', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=25262305', 'DH2017102383', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('84', '5', '5', '2020-01-01 23:08:27', '1', '', '0', '11911484', '3', '30485', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=11911484', 'DH2017102384', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('85', '5', '5', '2020-01-01 23:08:27', '1', '', '0', '29991861', '3', '88120', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=29991861', 'DH2017102385', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('86', '5', '5', '2020-01-01 23:08:28', '1', '', '0', '31793530', '3', '58698', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=31793530', 'DH2017102386', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('87', '5', '5', '2020-01-01 23:08:28', '1', '', '0', '75656241', '3', '3657', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=75656241', 'DH2017102387', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('88', '5', '5', '2020-01-01 23:08:28', '1', '', '0', '31438577', '3', '50141', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=31438577', 'DH2017102388', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('89', '5', '5', '2020-01-01 23:08:28', '1', '', '0', '39983858', '3', '65992', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=39983858', 'DH2017102389', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('90', '5', '5', '2020-01-01 23:08:28', '1', '', '0', '81409743', '3', '77927', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=81409743', 'DH2017102390', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('91', '5', '5', '2020-01-01 23:08:28', '1', '', '0', '37369054', '3', '30347', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=37369054', 'DH2017102391', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('92', '5', '5', '2020-01-01 23:08:28', '1', '', '0', '35175024', '3', '87018', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=35175024', 'DH2017102392', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('93', '5', '5', '2020-01-01 23:08:29', '1', '', '0', '24913513', '3', '85187', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=24913513', 'DH2017102393', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('94', '5', '5', '2020-01-01 23:08:29', '1', '', '0', '95433201', '3', '8747', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=95433201', 'DH2017102394', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('95', '5', '5', '2020-01-01 23:08:29', '1', '', '0', '97992235', '3', '30619', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=97992235', 'DH2017102395', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('96', '5', '5', '2020-01-01 23:08:29', '1', '', '0', '40040630', '3', '10099', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=40040630', 'DH2017102396', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('97', '5', '5', '2020-01-01 23:08:29', '1', '', '0', '12347949', '3', '38566', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=12347949', 'DH2017102397', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('98', '5', '5', '2020-01-01 23:08:29', '1', '', '0', '96172274', '3', '67606', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=96172274', 'DH2017102398', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('99', '5', '5', '2020-01-01 23:08:29', '1', '', '0', '57416961', '3', '44706', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=57416961', 'DH2017102399', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('100', '5', '5', '2020-01-01 23:08:29', '1', '', '0', '71933246', '3', '82468', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=71933246', 'DH20171023100', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('101', '5', '5', '2020-01-01 23:08:30', '1', '', '0', '62490640', '3', '48002', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=62490640', 'DH20171023101', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('102', '5', '5', '2020-01-01 23:08:30', '1', '', '0', '96136882', '3', '56873', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=96136882', 'DH20171023102', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('103', '5', '5', '2020-01-01 23:08:30', '1', '', '0', '80014413', '3', '40088', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=80014413', 'DH20171023103', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('104', '5', '5', '2020-01-01 23:08:30', '1', '', '0', '72952247', '3', '43812', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=72952247', 'DH20171023104', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('105', '5', '5', '2020-01-01 23:08:30', '1', '', '0', '22656825', '3', '15769', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=22656825', 'DH20171023105', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('106', '5', '5', '2020-01-01 23:08:30', '1', '', '0', '75260806', '3', '76703', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=75260806', 'DH20171023106', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('107', '5', '5', '2020-01-01 23:08:30', '1', '', '0', '82594552', '3', '90320', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=82594552', 'DH20171023107', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('108', '5', '5', '2020-01-01 23:08:31', '1', '', '0', '58819880', '3', '93076', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=58819880', 'DH20171023108', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('109', '5', '5', '2020-01-01 23:08:31', '1', '', '0', '98786096', '3', '31589', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=98786096', 'DH20171023109', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('110', '5', '5', '2020-01-01 23:08:31', '1', '', '0', '16416380', '3', '1178', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=16416380', 'DH20171023110', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('111', '5', '5', '2020-01-01 23:08:31', '1', '', '0', '62130029', '3', '67301', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=62130029', 'DH20171023111', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('112', '5', '5', '2020-01-01 23:08:31', '1', '', '0', '12255887', '3', '11991', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=12255887', 'DH20171023112', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('113', '5', '5', '2020-01-01 23:08:31', '1', '', '0', '63123291', '3', '13453', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=63123291', 'DH20171023113', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('114', '5', '5', '2020-01-01 23:08:31', '1', '', '0', '72999366', '3', '84333', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=72999366', 'DH20171023114', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('115', '5', '5', '2020-01-01 23:08:32', '1', '', '0', '69119066', '3', '50208', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=69119066', 'DH20171023115', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('116', '5', '5', '2020-01-01 23:08:32', '1', '', '0', '55608085', '3', '19996', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=55608085', 'DH20171023116', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('117', '5', '5', '2020-01-01 23:08:32', '1', '', '0', '89516721', '3', '10376', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=89516721', 'DH20171023117', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('118', '5', '5', '2020-01-01 23:08:32', '1', '', '0', '15836637', '3', '11661', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=15836637', 'DH20171023118', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('119', '5', '5', '2020-01-01 23:08:32', '1', '', '0', '67407580', '3', '4093', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=67407580', 'DH20171023119', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('120', '5', '5', '2020-01-01 23:08:32', '1', '', '0', '70584279', '3', '1255', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=70584279', 'DH20171023120', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('121', '5', '5', '2020-01-01 23:08:32', '1', '', '0', '65142111', '3', '45182', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=65142111', 'DH20171023121', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('122', '5', '5', '2020-01-01 23:08:33', '1', '', '0', '57093394', '3', '62797', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=57093394', 'DH20171023122', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('123', '5', '5', '2020-01-01 23:08:33', '1', '', '0', '53021897', '3', '31376', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=53021897', 'DH20171023123', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('124', '5', '5', '2020-01-01 23:08:33', '1', '', '0', '57282484', '3', '94376', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=57282484', 'DH20171023124', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('125', '5', '5', '2020-01-01 23:08:33', '1', '', '0', '51112474', '3', '25452', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=51112474', 'DH20171023125', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('126', '5', '5', '2020-01-01 23:08:33', '1', '', '0', '46423524', '3', '907', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=46423524', 'DH20171023126', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('127', '5', '5', '2020-01-01 23:08:33', '1', '', '0', '82125030', '3', '49292', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=82125030', 'DH20171023127', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('128', '5', '5', '2020-01-01 23:08:33', '1', '', '0', '93794081', '3', '85431', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=93794081', 'DH20171023128', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('129', '5', '5', '2020-01-01 23:08:34', '1', '', '0', '20809073', '3', '763', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=20809073', 'DH20171023129', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('130', '5', '5', '2020-01-01 23:08:34', '1', '', '0', '56354163', '3', '54514', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=56354163', 'DH20171023130', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('131', '5', '5', '2020-01-01 23:08:34', '1', '', '0', '42573289', '3', '96454', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=42573289', 'DH20171023131', '1', '1', '1', '1', null, '1');
INSERT INTO `ims_ewei_shop_exchange_code` VALUES ('132', '5', '5', '2020-01-01 23:08:34', '1', '', '0', '31793269', '3', '94935', 'http://test.xehsy.com/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=exchange.goods.qr&key=31793269', 'DH20171023132', '1', '1', '1', '1', null, '1');

-- ----------------------------
-- Table structure for `ims_ewei_shop_exchange_group`
-- ----------------------------
DROP TABLE IF EXISTS `ims_ewei_shop_exchange_group`;
CREATE TABLE `ims_ewei_shop_exchange_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `type` int(2) NOT NULL DEFAULT '0',
  `endtime` datetime NOT NULL DEFAULT '2016-10-01 00:00:00',
  `mode` int(2) NOT NULL DEFAULT '0',
  `status` int(2) NOT NULL DEFAULT '0',
  `max` int(2) NOT NULL DEFAULT '0',
  `value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `starttime` datetime NOT NULL DEFAULT '2016-10-01 00:00:00',
  `goods` text,
  `score` int(11) NOT NULL DEFAULT '0',
  `coupon` text,
  `use` int(11) NOT NULL DEFAULT '0',
  `total` int(11) NOT NULL DEFAULT '0',
  `red` decimal(10,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `balance_left` decimal(10,2) NOT NULL DEFAULT '0.00',
  `balance_right` decimal(10,2) NOT NULL DEFAULT '0.00',
  `red_left` decimal(10,2) NOT NULL DEFAULT '0.00',
  `red_right` decimal(10,2) NOT NULL DEFAULT '0.00',
  `score_left` int(11) NOT NULL DEFAULT '0',
  `score_right` int(11) NOT NULL DEFAULT '0',
  `balance_type` int(11) NOT NULL,
  `red_type` int(11) NOT NULL,
  `score_type` int(11) NOT NULL,
  `title_reply` varchar(255) NOT NULL DEFAULT '',
  `img` varchar(255) NOT NULL DEFAULT '',
  `content` varchar(255) NOT NULL DEFAULT '',
  `rule` text NOT NULL,
  `coupon_type` varchar(255) DEFAULT NULL,
  `basic_content` varchar(500) NOT NULL DEFAULT '',
  `reply_type` int(11) NOT NULL DEFAULT '0',
  `code_type` int(11) NOT NULL DEFAULT '0',
  `binding` int(11) NOT NULL DEFAULT '0',
  `showcount` int(11) DEFAULT '0',
  `postage` decimal(10,2) DEFAULT '0.00',
  `postage_type` int(11) DEFAULT '0',
  `banner` varchar(800) DEFAULT '',
  `keyword_reply` int(11) DEFAULT '0',
  `reply_status` int(11) DEFAULT '1',
  `reply_keyword` varchar(255) DEFAULT '',
  `input_banner` varchar(255) DEFAULT '',
  `diypage` int(11) NOT NULL DEFAULT '0',
  `sendname` varchar(255) DEFAULT '',
  `wishing` varchar(255) DEFAULT '',
  `actname` varchar(255) DEFAULT '',
  `remark` varchar(255) DEFAULT '',
  `repeat` tinyint(1) NOT NULL DEFAULT '0',
  `koulingstart` varchar(255) NOT NULL DEFAULT '',
  `koulingend` varchar(255) NOT NULL DEFAULT '',
  `kouling` tinyint(1) NOT NULL DEFAULT '0',
  `chufa` varchar(255) NOT NULL DEFAULT '',
  `chufaend` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Records of ims_ewei_shop_exchange_group
-- ----------------------------
INSERT INTO `ims_ewei_shop_exchange_group` VALUES ('1', '测', '1', '2017-09-19 18:01:00', '2', '1', '0', '0.00', '3', '2017-09-17 18:00:58', null, '0', null, '0', '1', '0.00', '10.00', '0.00', '0.00', '0.00', '0.00', '0', '0', '0', '0', '0', '余额兑换', '../addons/ewei_shopv2/plugin/exchange/static/img/exchange.jpg', '欢迎来到兑换中心,点击进入兑换', '', null, '', '0', '3', '0', '0', '0.00', '0', '', '0', '1', '', '', '0', '', '', '', '', '1', '', '', '0', '', '');
INSERT INTO `ims_ewei_shop_exchange_group` VALUES ('2', '测试1', '1', '2017-09-18 20:55:19', '2', '1', '0', '0.00', '3', '2017-09-18 20:55:19', null, '0', null, '0', '0', '0.00', '10.00', '0.00', '0.00', '0.00', '0.00', '0', '0', '0', '0', '0', '余额兑换', '../addons/ewei_shopv2/plugin/exchange/static/img/exchange.jpg', '欢迎来到兑换中心,点击进入兑换', '', null, '', '0', '3', '0', '0', '0.00', '0', '', '0', '1', '', '', '0', '', '', '', '', '1', '', '', '0', '', '');
INSERT INTO `ims_ewei_shop_exchange_group` VALUES ('3', 'test', '1', '2017-11-25 21:02:00', '3', '1', '0', '0.00', '3', '2017-09-18 21:02:36', null, '0', null, '0', '0', '10.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0', '0', '0', '0', '0', '红包兑换', '../addons/ewei_shopv2/plugin/exchange/static/img/exchange.jpg', '欢迎来到兑换中心,点击进入兑换', '', null, '', '0', '0', '1', '0', '0.00', '0', '', '0', '1', '', '', '0', '', '111', '', '111', '1', '', '', '0', '', '');
INSERT INTO `ims_ewei_shop_exchange_group` VALUES ('4', '充值卡', '1', '2017-09-30 14:24:00', '2', '1', '0', '0.00', '3', '2017-09-21 14:24:10', null, '0', null, '0', '30', '0.00', '50.00', '0.00', '0.00', '0.00', '0.00', '0', '0', '0', '0', '0', '余额兑换', '../addons/ewei_shopv2/plugin/exchange/static/img/exchange.jpg', '欢迎来到兑换中心,点击进入兑换', '', null, '', '0', '3', '0', '0', '0.00', '0', '', '0', '1', '', '', '0', '', '', '', '', '1', '', '', '0', '', '');
INSERT INTO `ims_ewei_shop_exchange_group` VALUES ('5', '5255', '1', '2017-10-27 23:07:00', '2', '1', '0', '0.00', '5', '2017-10-23 23:07:49', null, '0', null, '0', '100', '0.00', '1.00', '0.00', '0.00', '0.00', '0.00', '0', '0', '0', '0', '0', '余额兑换', '../addons/ewei_shopv2/plugin/exchange/static/img/exchange.jpg', '欢迎来到兑换中心,点击进入兑换', '', null, '', '0', '3', '0', '0', '0.00', '0', '', '0', '1', '', '', '0', '', '', '', '', '1', '', '', '0', '', '');

-- ----------------------------
-- Table structure for `ims_ewei_shop_exchange_query`
-- ----------------------------
DROP TABLE IF EXISTS `ims_ewei_shop_exchange_query`;
CREATE TABLE `ims_ewei_shop_exchange_query` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `openid` varchar(255) NOT NULL DEFAULT '',
  `querykey` varchar(255) NOT NULL DEFAULT '',
  `querytime` int(11) NOT NULL DEFAULT '0',
  `unfreeze` int(11) NOT NULL DEFAULT '0',
  `errorcount` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`openid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Records of ims_ewei_shop_exchange_query
-- ----------------------------

-- ----------------------------
-- Table structure for `ims_ewei_shop_exchange_record`
-- ----------------------------
DROP TABLE IF EXISTS `ims_ewei_shop_exchange_record`;
CREATE TABLE `ims_ewei_shop_exchange_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL DEFAULT '',
  `uniacid` int(11) DEFAULT NULL,
  `goods` text,
  `orderid` varchar(255) NOT NULL DEFAULT '',
  `time` int(11) NOT NULL,
  `openid` varchar(255) NOT NULL DEFAULT '',
  `mode` int(11) NOT NULL DEFAULT '0',
  `balance` decimal(10,2) DEFAULT '0.00',
  `red` decimal(10,2) NOT NULL DEFAULT '0.00',
  `coupon` text,
  `score` int(11) NOT NULL DEFAULT '0',
  `nickname` varchar(255) NOT NULL DEFAULT '',
  `groupid` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `serial` varchar(255) NOT NULL DEFAULT '',
  `ordersn` varchar(255) NOT NULL DEFAULT '',
  `goods_title` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`,`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Records of ims_ewei_shop_exchange_record
-- ----------------------------

-- ----------------------------
-- Table structure for `ims_ewei_shop_exchange_setting`
-- ----------------------------
DROP TABLE IF EXISTS `ims_ewei_shop_exchange_setting`;
CREATE TABLE `ims_ewei_shop_exchange_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `freeze` int(11) NOT NULL DEFAULT '0',
  `mistake` int(11) NOT NULL DEFAULT '0',
  `grouplimit` int(11) NOT NULL DEFAULT '0',
  `alllimit` int(11) NOT NULL DEFAULT '0',
  `no_qrimg` tinyint(3) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`,`uniacid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- ----------------------------
-- Records of ims_ewei_shop_exchange_setting
-- ----------------------------
