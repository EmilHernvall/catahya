-- MySQL dump 10.11
--
-- Host: localhost    Database: catahya2
-- ------------------------------------------------------
-- Server version	5.0.44-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES latin1 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `access`
--

DROP TABLE IF EXISTS `access`;
CREATE TABLE `access` (
  `access_id` int(10) unsigned NOT NULL auto_increment,
  `object_id` int(10) unsigned NOT NULL,
  `access_name` varchar(45) collate utf8_swedish_ci NOT NULL,
  `access_title` varchar(45) collate utf8_swedish_ci NOT NULL,
  `access_defaultpermission` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`access_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `access_group`
--

DROP TABLE IF EXISTS `access_group`;
CREATE TABLE `access_group` (
  `group_id` int(10) unsigned NOT NULL,
  `access_id` int(10) unsigned NOT NULL,
  `access_permission` int(10) unsigned NOT NULL,
  UNIQUE KEY `Index_1` USING BTREE (`group_id`,`access_id`),
  UNIQUE KEY `Index_2` USING BTREE (`access_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `access_object`
--

DROP TABLE IF EXISTS `access_object`;
CREATE TABLE `access_object` (
  `object_id` int(10) unsigned NOT NULL auto_increment,
  `object_name` varchar(45) collate utf8_swedish_ci NOT NULL,
  `object_title` varchar(45) collate utf8_swedish_ci NOT NULL,
  PRIMARY KEY  (`object_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `artwork`
--

DROP TABLE IF EXISTS `artwork`;
CREATE TABLE `artwork` (
  `artwork_id` mediumint(8) unsigned NOT NULL auto_increment,
  `member_id` mediumint(8) unsigned NOT NULL,
  `type_id` smallint(6) NOT NULL,
  `subtype_id` smallint(5) unsigned NOT NULL,
  `language_id` smallint(5) unsigned NOT NULL,
  `artwork_timestamp` int(11) NOT NULL,
  `artwork_title` varchar(255) character set latin1 NOT NULL,
  `artwork_text` text character set latin1 NOT NULL,
  `artwork_published` int(10) unsigned NOT NULL,
  `artwork_publishedby` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (`artwork_id`),
  KEY `member_id` (`member_id`),
  KEY `subtype_id` (`subtype_id`,`artwork_timestamp`),
  KEY `type_id` (`type_id`,`artwork_timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=4017 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `artwork_comment`
--

DROP TABLE IF EXISTS `artwork_comment`;
CREATE TABLE `artwork_comment` (
  `comment_id` mediumint(7) unsigned NOT NULL auto_increment,
  `artwork_id` mediumint(7) unsigned NOT NULL,
  `member_id` mediumint(7) unsigned NOT NULL,
  `comment_timestamp` int(10) unsigned NOT NULL,
  `comment_deleted` enum('0','1') collate utf8_swedish_ci NOT NULL,
  `comment_title` varchar(100) collate utf8_swedish_ci NOT NULL,
  `comment_text` mediumtext collate utf8_swedish_ci NOT NULL,
  PRIMARY KEY  (`comment_id`),
  KEY `memberid` (`member_id`),
  KEY `artwork_id` (`artwork_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13146 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `artwork_language`
--

DROP TABLE IF EXISTS `artwork_language`;
CREATE TABLE `artwork_language` (
  `language_id` smallint(5) unsigned NOT NULL auto_increment,
  `language_title` varchar(255) character set latin1 NOT NULL,
  PRIMARY KEY  (`language_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `artwork_subtype`
--

DROP TABLE IF EXISTS `artwork_subtype`;
CREATE TABLE `artwork_subtype` (
  `subtype_id` smallint(5) unsigned NOT NULL auto_increment,
  `type_id` smallint(6) NOT NULL,
  `subtype_title` varchar(255) character set latin1 NOT NULL,
  PRIMARY KEY  (`subtype_id`),
  KEY `type_id` (`type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `artwork_type`
--

DROP TABLE IF EXISTS `artwork_type`;
CREATE TABLE `artwork_type` (
  `type_id` smallint(6) unsigned NOT NULL auto_increment,
  `type_name` varchar(100) character set latin1 NOT NULL,
  `type_title` varchar(255) character set latin1 NOT NULL,
  PRIMARY KEY  (`type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `forum`
--

DROP TABLE IF EXISTS `forum`;
CREATE TABLE `forum` (
  `forum_id` smallint(5) unsigned NOT NULL auto_increment,
  `category_id` tinyint(3) unsigned NOT NULL default '0',
  `guild_id` smallint(5) unsigned NOT NULL default '0',
  `access_id` int(10) unsigned NOT NULL default '0',
  `forum_name` varchar(45) collate utf8_swedish_ci NOT NULL,
  `forum_description` text collate utf8_swedish_ci NOT NULL,
  `forum_threadcount` smallint(5) unsigned NOT NULL default '0',
  `forum_replycount` mediumint(8) unsigned NOT NULL default '0',
  `forum_lastthreadid` mediumint(8) unsigned NOT NULL default '0',
  `forum_lastmemberid` mediumint(8) unsigned NOT NULL default '0',
  `forum_lasttimestamp` int(10) unsigned NOT NULL default '0',
  `forum_guildlevel` enum('open','member','moderator','admin') collate utf8_swedish_ci NOT NULL default 'member',
  PRIMARY KEY  (`forum_id`),
  KEY `idx_categoryid` (`category_id`),
  KEY `idx_guildid` (`guild_id`)
) ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `forum_category`
--

DROP TABLE IF EXISTS `forum_category`;
CREATE TABLE `forum_category` (
  `category_id` tinyint(3) unsigned NOT NULL auto_increment,
  `category_name` varchar(45) collate utf8_swedish_ci NOT NULL,
  `category_description` varchar(255) collate utf8_swedish_ci NOT NULL,
  PRIMARY KEY  (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `forum_read`
--

DROP TABLE IF EXISTS `forum_read`;
CREATE TABLE `forum_read` (
  `thread_id` mediumint(8) unsigned NOT NULL,
  `member_id` mediumint(8) unsigned NOT NULL,
  `read_timestamp` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`thread_id`,`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `forum_reply`
--

DROP TABLE IF EXISTS `forum_reply`;
CREATE TABLE `forum_reply` (
  `reply_id` mediumint(8) unsigned NOT NULL auto_increment,
  `thread_id` smallint(5) unsigned NOT NULL,
  `member_id` mediumint(8) unsigned NOT NULL,
  `reply_timestamp` int(10) unsigned NOT NULL,
  `reply_text` text collate utf8_swedish_ci NOT NULL,
  `reply_deleted` enum('0','1') collate utf8_swedish_ci NOT NULL default '0',
  PRIMARY KEY  (`reply_id`),
  KEY `idx_memberid` (`member_id`),
  KEY `idx_threadid` (`thread_id`,`reply_timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=382813 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `forum_thread`
--

DROP TABLE IF EXISTS `forum_thread`;
CREATE TABLE `forum_thread` (
  `thread_id` smallint(5) unsigned NOT NULL auto_increment,
  `forum_id` smallint(5) unsigned NOT NULL,
  `member_id` mediumint(8) unsigned NOT NULL,
  `thread_timestamp` int(10) unsigned NOT NULL,
  `thread_title` varchar(100) collate utf8_swedish_ci NOT NULL,
  `thread_text` text collate utf8_swedish_ci NOT NULL,
  `thread_deleted` enum('0','1') collate utf8_swedish_ci NOT NULL default '0',
  `thread_sticky` enum('0','1') collate utf8_swedish_ci NOT NULL default '0',
  `thread_locked` enum('0','1') collate utf8_swedish_ci NOT NULL default '0',
  `thread_lastmemberid` mediumint(8) unsigned NOT NULL default '0',
  `thread_lasttimestamp` int(10) unsigned NOT NULL default '0',
  `thread_replycount` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`thread_id`),
  KEY `idx_memberid` (`member_id`),
  KEY `forumthread_lasttimestamp` (`thread_lasttimestamp`),
  KEY `forum_id` (`forum_id`,`thread_timestamp`),
  KEY `idx_forumid` (`forum_id`,`thread_sticky`,`thread_lasttimestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=13466 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `group`
--

DROP TABLE IF EXISTS `group`;
CREATE TABLE `group` (
  `group_id` int(10) unsigned NOT NULL auto_increment,
  `group_title` varchar(45) collate utf8_swedish_ci NOT NULL,
  `group_description` varchar(45) collate utf8_swedish_ci NOT NULL,
  PRIMARY KEY  (`group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `group_member`
--

DROP TABLE IF EXISTS `group_member`;
CREATE TABLE `group_member` (
  `group_id` int(10) unsigned NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  KEY `Index_1` USING BTREE (`group_id`,`member_id`),
  KEY `Index_2` USING BTREE (`member_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `guild`
--

DROP TABLE IF EXISTS `guild`;
CREATE TABLE `guild` (
  `guild_id` smallint(5) unsigned NOT NULL auto_increment,
  `member_id` mediumint(7) unsigned NOT NULL,
  `guild_name` varchar(100) character set latin1 NOT NULL,
  `guild_description` varchar(1024) character set latin1 NOT NULL,
  `guild_text` text collate utf8_swedish_ci NOT NULL,
  `guild_requirements` text character set latin1 NOT NULL,
  `guild_confirmed` int(10) unsigned NOT NULL,
  `guild_confirmedby` mediumint(8) unsigned NOT NULL,
  `guild_type` enum('general','catahya','hidden','official') collate utf8_swedish_ci NOT NULL default 'general',
  `guild_haslogo` enum('0','1') collate utf8_swedish_ci NOT NULL default '0',
  PRIMARY KEY  (`guild_id`)
) ENGINE=InnoDB AUTO_INCREMENT=417 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `guild_history`
--

DROP TABLE IF EXISTS `guild_history`;
CREATE TABLE `guild_history` (
  `history_id` mediumint(8) unsigned NOT NULL auto_increment,
  `guild_id` smallint(5) unsigned NOT NULL,
  `history_timestamp` int(10) unsigned NOT NULL,
  `history_description` varchar(250) character set latin1 NOT NULL,
  PRIMARY KEY  (`history_id`)
) ENGINE=InnoDB AUTO_INCREMENT=180 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `guild_level`
--

DROP TABLE IF EXISTS `guild_level`;
CREATE TABLE `guild_level` (
  `level_id` smallint(5) unsigned NOT NULL auto_increment,
  `guild_id` smallint(5) NOT NULL,
  `group_id` int(10) unsigned NOT NULL,
  `level_name` varchar(100) character set latin1 NOT NULL,
  `level_access` enum('member','moderator','admin') character set latin1 NOT NULL,
  PRIMARY KEY  (`level_id`)
) ENGINE=InnoDB AUTO_INCREMENT=474 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `guild_member`
--

DROP TABLE IF EXISTS `guild_member`;
CREATE TABLE `guild_member` (
  `guild_id` smallint(5) unsigned NOT NULL auto_increment,
  `member_id` mediumint(7) NOT NULL,
  `level_id` smallint(6) NOT NULL,
  `member_guildtimestamp` int(10) unsigned NOT NULL,
  `member_guildstatement` text character set latin1 NOT NULL,
  PRIMARY KEY  (`guild_id`,`member_id`),
  UNIQUE KEY `member_id` (`member_id`,`guild_id`)
) ENGINE=InnoDB AUTO_INCREMENT=416 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `member`
--

DROP TABLE IF EXISTS `member`;
CREATE TABLE `member` (
  `member_id` mediumint(7) unsigned NOT NULL auto_increment,
  `member_alias` varchar(40) collate utf8_swedish_ci NOT NULL,
  `member_flatalias` varchar(40) collate utf8_swedish_ci NOT NULL,
  `member_password` char(32) collate utf8_swedish_ci NOT NULL,
  `member_gender` enum('male','female') collate utf8_swedish_ci NOT NULL default 'male',
  `member_age` tinyint(3) NOT NULL default '0',
  `member_status` enum('unverified','active','discontinued','inactivated') collate utf8_swedish_ci NOT NULL default 'unverified',
  `member_online` enum('0','1') collate utf8_swedish_ci NOT NULL default '0',
  `member_photo` char(30) collate utf8_swedish_ci NOT NULL default 'alternatives/0.png',
  `member_photostatus` enum('1','2') collate utf8_swedish_ci NOT NULL default '1',
  `member_quickdesc` char(15) collate utf8_swedish_ci NOT NULL,
  `member_city` varchar(20) collate utf8_swedish_ci NOT NULL,
  PRIMARY KEY  (`member_id`),
  UNIQUE KEY `member_flatalias` (`member_flatalias`)
) ENGINE=InnoDB AUTO_INCREMENT=661926 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `member_account`
--

DROP TABLE IF EXISTS `member_account`;
CREATE TABLE `member_account` (
  `member_id` mediumint(8) unsigned NOT NULL,
  `auditlog_id` int(11) unsigned NOT NULL default '0',
  `member_accountstatus` enum('pending','valid','invalid') collate utf8_swedish_ci NOT NULL,
  `member_timestamp` int(10) unsigned NOT NULL,
  `member_firstname` varchar(50) collate utf8_swedish_ci NOT NULL,
  `member_surname` varchar(50) collate utf8_swedish_ci NOT NULL,
  `member_address` varchar(200) collate utf8_swedish_ci NOT NULL,
  `member_zipcode` varchar(10) collate utf8_swedish_ci NOT NULL,
  `member_city` varchar(20) collate utf8_swedish_ci NOT NULL,
  `member_country` varchar(40) collate utf8_swedish_ci NOT NULL,
  `member_phonenr` varchar(20) collate utf8_swedish_ci NOT NULL,
  `member_ssn` varchar(15) collate utf8_swedish_ci NOT NULL,
  `member_email` varchar(250) collate utf8_swedish_ci NOT NULL,
  PRIMARY KEY  (`member_id`),
  KEY `audit` (`member_accountstatus`,`member_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `member_auditlog`
--

DROP TABLE IF EXISTS `member_auditlog`;
CREATE TABLE `member_auditlog` (
  `auditlog_id` int(10) unsigned NOT NULL auto_increment,
  `member_id` mediumint(8) unsigned NOT NULL,
  `auditlog_timestamp` int(10) unsigned NOT NULL,
  `auditlog_auditor` mediumint(8) unsigned NOT NULL,
  `auditlog_confirmed` enum('confirmed','rejected') collate utf8_swedish_ci NOT NULL,
  `auditlog_message` text collate utf8_swedish_ci NOT NULL,
  PRIMARY KEY  (`auditlog_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `member_avatar`
--

DROP TABLE IF EXISTS `member_avatar`;
CREATE TABLE `member_avatar` (
  `avatar_id` mediumint(6) unsigned NOT NULL auto_increment,
  `member_id` mediumint(7) unsigned NOT NULL,
  `avatar_name` varchar(255) collate utf8_swedish_ci NOT NULL,
  `avatar_size` smallint(5) unsigned NOT NULL default '0',
  `avatar_width` smallint(3) unsigned NOT NULL default '0',
  `avatar_height` smallint(3) unsigned NOT NULL default '0',
  `avatar_timestamp` int(10) unsigned NOT NULL default '0',
  `avatar_current` enum('0','1') collate utf8_swedish_ci NOT NULL default '0',
  PRIMARY KEY  (`avatar_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `member_character`
--

DROP TABLE IF EXISTS `member_character`;
CREATE TABLE `member_character` (
  `member_id` int(10) unsigned NOT NULL,
  `character_race` varchar(50) collate utf8_swedish_ci NOT NULL default 'Människa',
  `character_class` varchar(50) collate utf8_swedish_ci NOT NULL default 'Ingen',
  `character_alignment` varchar(50) collate utf8_swedish_ci NOT NULL default 'Neutral',
  `character_description` text collate utf8_swedish_ci NOT NULL,
  PRIMARY KEY  (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `member_guestbook`
--

DROP TABLE IF EXISTS `member_guestbook`;
CREATE TABLE `member_guestbook` (
  `guestbook_id` mediumint(8) unsigned NOT NULL auto_increment,
  `guestbook_from` mediumint(7) unsigned NOT NULL default '0',
  `guestbook_to` mediumint(7) unsigned NOT NULL default '0',
  `guestbook_timestamp` int(10) NOT NULL default '0',
  `guestbook_msg` mediumtext collate utf8_swedish_ci NOT NULL,
  `guestbook_secret` enum('0','1') collate utf8_swedish_ci NOT NULL default '0',
  `guestbook_read` enum('0','1') collate utf8_swedish_ci NOT NULL default '0',
  `guestbook_answered` enum('0','1') collate utf8_swedish_ci NOT NULL default '0',
  PRIMARY KEY  (`guestbook_id`),
  KEY `idx_guestbook_to` (`guestbook_to`,`guestbook_read`),
  KEY `guestbook_timestamp` (`guestbook_timestamp`),
  KEY `guestbook_to` (`guestbook_to`,`guestbook_timestamp`),
  KEY `guestbook_to_2` (`guestbook_to`,`guestbook_from`,`guestbook_timestamp`),
  KEY `guestbook_from` (`guestbook_from`)
) ENGINE=InnoDB AUTO_INCREMENT=354298 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `member_login`
--

DROP TABLE IF EXISTS `member_login`;
CREATE TABLE `member_login` (
  `login_id` mediumint(8) unsigned NOT NULL auto_increment,
  `member_id` mediumint(7) unsigned NOT NULL default '0',
  `login_ip` char(15) collate utf8_swedish_ci NOT NULL default '',
  `login_proxyip` char(15) collate utf8_swedish_ci default NULL,
  `login_useragent` char(150) collate utf8_swedish_ci NOT NULL default '',
  `login_resolution` char(75) collate utf8_swedish_ci NOT NULL default '',
  `login_timestamp` int(10) unsigned zerofill default NULL,
  PRIMARY KEY  (`login_id`),
  KEY `login_uid` (`member_id`),
  KEY `login_timestamp` (`login_timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=189 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `member_online`
--

DROP TABLE IF EXISTS `member_online`;
CREATE TABLE `member_online` (
  `member_id` mediumint(7) unsigned NOT NULL default '0',
  `member_sessionid` char(32) collate utf8_swedish_ci NOT NULL,
  `member_active` int(10) unsigned NOT NULL default '0',
  `member_realactivity` int(10) unsigned NOT NULL default '0',
  `member_away` int(10) unsigned NOT NULL default '0',
  `member_awayreason` char(40) collate utf8_swedish_ci NOT NULL,
  `member_idle` enum('0','1') collate utf8_swedish_ci NOT NULL default '0',
  PRIMARY KEY  (`member_id`),
  UNIQUE KEY `online_uid` (`member_id`),
  KEY `online_uid_2` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci COMMENT='Category:Member tables|';

--
-- Table structure for table `member_profile`
--

DROP TABLE IF EXISTS `member_profile`;
CREATE TABLE `member_profile` (
  `member_id` mediumint(7) unsigned NOT NULL default '0',
  `member_name` varchar(100) collate utf8_swedish_ci NOT NULL,
  `member_email` varchar(250) collate utf8_swedish_ci NOT NULL,
  `member_birthdate` date NOT NULL,
  `member_jabber` varchar(100) collate utf8_swedish_ci NOT NULL,
  `member_msn` varchar(100) collate utf8_swedish_ci NOT NULL,
  `member_homepage` varchar(200) collate utf8_swedish_ci NOT NULL,
  `member_note` varchar(255) collate utf8_swedish_ci NOT NULL,
  `member_presentation` mediumtext collate utf8_swedish_ci NOT NULL,
  PRIMARY KEY  (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `member_profilevisit`
--

DROP TABLE IF EXISTS `member_profilevisit`;
CREATE TABLE `member_profilevisit` (
  `profilevisit_id` mediumint(7) unsigned NOT NULL auto_increment,
  `member_id` mediumint(7) unsigned default '0',
  `profilevisit_visitorid` mediumint(7) unsigned default '0',
  `profilevisit_timestamp` int(10) unsigned default '0',
  PRIMARY KEY  (`profilevisit_id`),
  KEY `idx_profilevisit_profileid` (`member_id`)
) ENGINE=InnoDB AUTO_INCREMENT=973 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `member_relation`
--

DROP TABLE IF EXISTS `member_relation`;
CREATE TABLE `member_relation` (
  `relation_id` mediumint(8) unsigned NOT NULL auto_increment,
  `relation_memberid1` mediumint(8) unsigned NOT NULL,
  `relation_memberid2` mediumint(8) unsigned NOT NULL,
  `relation_action` varchar(30) collate utf8_swedish_ci NOT NULL,
  `relation_irl` enum('0','1') collate utf8_swedish_ci NOT NULL default '0',
  `relation_timestamp` int(11) NOT NULL,
  `relation_approved` enum('0','1') collate utf8_swedish_ci NOT NULL default '0',
  PRIMARY KEY  (`relation_id`),
  UNIQUE KEY `Index_1_2` (`relation_memberid1`,`relation_memberid2`),
  UNIQUE KEY `Index_2_1` (`relation_memberid2`,`relation_memberid1`)
) ENGINE=InnoDB AUTO_INCREMENT=3006 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `member_renewal`
--

DROP TABLE IF EXISTS `member_renewal`;
CREATE TABLE `member_renewal` (
  `renewal_id` tinyint(3) unsigned NOT NULL auto_increment,
  `renewal_timestamp` int(10) unsigned NOT NULL,
  `renewal_message` text collate utf8_swedish_ci NOT NULL,
  PRIMARY KEY  (`renewal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `member_userdata`
--

DROP TABLE IF EXISTS `member_userdata`;
CREATE TABLE `member_userdata` (
  `member_id` mediumint(7) unsigned NOT NULL default '0',
  `theme_id` tinyint(2) unsigned NOT NULL default '1',
  `member_lastlogin` int(10) unsigned NOT NULL default '0',
  `member_memberdate` int(10) unsigned NOT NULL default '0',
  `member_minonline` mediumint(9) unsigned NOT NULL default '0',
  `member_logintotal` smallint(5) unsigned NOT NULL default '0',
  `member_visitstotal` mediumint(6) unsigned NOT NULL default '0',
  `member_gbrecv` mediumint(6) unsigned NOT NULL default '0',
  `member_gbsent` mediumint(6) unsigned NOT NULL default '0',
  PRIMARY KEY  (`member_id`),
  UNIQUE KEY `userdata_uid` (`member_id`),
  KEY `userdata_uid_2` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `message_folder`
--

DROP TABLE IF EXISTS `message_folder`;
CREATE TABLE `message_folder` (
  `folder_id` smallint(5) unsigned NOT NULL auto_increment,
  `member_id` mediumint(7) unsigned NOT NULL default '0',
  `folder_name` varchar(50) collate utf8_swedish_ci NOT NULL,
  `folder_type` enum('system','user') collate utf8_swedish_ci NOT NULL,
  PRIMARY KEY  (`folder_id`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4826 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `message_reply`
--

DROP TABLE IF EXISTS `message_reply`;
CREATE TABLE `message_reply` (
  `reply_id` int(10) unsigned NOT NULL auto_increment,
  `thread_id` int(10) unsigned NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `reply_timestamp` int(10) unsigned NOT NULL,
  `reply_text` text collate utf8_swedish_ci NOT NULL,
  PRIMARY KEY  (`reply_id`),
  KEY `thread_id` (`thread_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `message_thread`
--

DROP TABLE IF EXISTS `message_thread`;
CREATE TABLE `message_thread` (
  `thread_id` int(10) unsigned NOT NULL auto_increment,
  `thread_title` varchar(100) collate utf8_swedish_ci NOT NULL,
  `thread_text` text collate utf8_swedish_ci NOT NULL,
  `thread_timestamp` int(10) unsigned NOT NULL,
  `thread_rcount` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`thread_id`)
) ENGINE=InnoDB AUTO_INCREMENT=188377 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `message_thread_member`
--

DROP TABLE IF EXISTS `message_thread_member`;
CREATE TABLE `message_thread_member` (
  `thread_id` int(10) unsigned NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `folder_id` int(10) unsigned NOT NULL,
  `thread_role` enum('s','r') collate utf8_swedish_ci NOT NULL default 'r',
  `thread_read` enum('0','1') collate utf8_swedish_ci NOT NULL default '0',
  `thread_deleted` enum('0','1') collate utf8_swedish_ci NOT NULL default '0',
  `thread_lasttimestamp` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`thread_id`,`folder_id`),
  KEY `index_getmembers` (`thread_id`,`thread_role`),
  KEY `index_listthreads` (`folder_id`,`thread_lasttimestamp`),
  KEY `index_unreadcount` (`member_id`,`thread_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `store_category`
--

DROP TABLE IF EXISTS `store_category`;
CREATE TABLE `store_category` (
  `category_id` int(10) unsigned NOT NULL auto_increment,
  `category_name` varchar(45) character set utf8 NOT NULL,
  PRIMARY KEY  (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `store_image`
--

DROP TABLE IF EXISTS `store_image`;
CREATE TABLE `store_image` (
  `image_id` int(10) unsigned NOT NULL auto_increment,
  `product_id` int(10) unsigned NOT NULL,
  `image_path` varchar(50) character set utf8 NOT NULL,
  PRIMARY KEY  (`image_id`),
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `store_product`
--

DROP TABLE IF EXISTS `store_product`;
CREATE TABLE `store_product` (
  `product_id` int(10) unsigned NOT NULL auto_increment,
  `product_category` int(10) unsigned NOT NULL default '1',
  `product_name` varchar(45) character set utf8 NOT NULL,
  `product_short_description` text character set utf8 NOT NULL,
  `product_long_description` text character set utf8 NOT NULL,
  `product_price` int(10) unsigned NOT NULL default '0',
  `product_weight` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`product_id`),
  KEY `idx_product_name` (`product_name`),
  KEY `idx_product_price` (`product_price`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `store_stock`
--

DROP TABLE IF EXISTS `store_stock`;
CREATE TABLE `store_stock` (
  `stock_id` int(11) NOT NULL auto_increment,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY  (`stock_id`),
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `store_thumbnail`
--

DROP TABLE IF EXISTS `store_thumbnail`;
CREATE TABLE `store_thumbnail` (
  `thumbnail_id` int(10) unsigned NOT NULL auto_increment,
  `access_id` int(10) unsigned NOT NULL,
  `thumbnail_filename` varchar(50) character set utf8 NOT NULL,
  PRIMARY KEY  (`thumbnail_id`),
  KEY `idx_product_id` (`access_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `text`
--

DROP TABLE IF EXISTS `text`;
CREATE TABLE `text` (
  `text_id` int(10) unsigned NOT NULL auto_increment,
  `type_id` int(10) unsigned NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `image_id` mediumint(8) unsigned NOT NULL default '0',
  `text_timestamp` int(10) unsigned NOT NULL,
  `text_title` varchar(45) collate utf8_swedish_ci NOT NULL,
  `text_pretext` text collate utf8_swedish_ci NOT NULL,
  `text_text` text collate utf8_swedish_ci NOT NULL,
  `text_showpretext` enum('0','1') collate utf8_swedish_ci NOT NULL default '0',
  `text_published` enum('0','1') collate utf8_swedish_ci NOT NULL default '0',
  `text_publishedby` mediumint(8) unsigned NOT NULL default '0',
  `text_gallery` enum('0','1') collate utf8_swedish_ci NOT NULL default '0',
  PRIMARY KEY  (`text_id`),
  KEY `Index_3` (`member_id`),
  KEY `Index_2` USING BTREE (`type_id`,`text_timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=1087 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `text_comment`
--

DROP TABLE IF EXISTS `text_comment`;
CREATE TABLE `text_comment` (
  `comment_id` mediumint(7) unsigned NOT NULL auto_increment,
  `text_id` mediumint(7) unsigned NOT NULL,
  `member_id` mediumint(7) unsigned NOT NULL,
  `comment_timestamp` int(10) unsigned NOT NULL,
  `comment_deleted` enum('0','1') collate utf8_swedish_ci NOT NULL,
  `comment_title` varchar(100) collate utf8_swedish_ci NOT NULL,
  `comment_text` mediumtext collate utf8_swedish_ci NOT NULL,
  PRIMARY KEY  (`comment_id`),
  KEY `memberid` (`member_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6629 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `text_image`
--

DROP TABLE IF EXISTS `text_image`;
CREATE TABLE `text_image` (
  `image_id` int(10) unsigned NOT NULL auto_increment,
  `text_id` mediumint(8) unsigned NOT NULL,
  `member_id` mediumint(8) unsigned NOT NULL,
  `image_timestamp` int(10) unsigned NOT NULL,
  `image_size` int(10) unsigned NOT NULL,
  `image_name` varchar(100) character set latin1 NOT NULL,
  `image_title` varchar(100) collate utf8_swedish_ci NOT NULL,
  `image_description` varchar(1024) collate utf8_swedish_ci NOT NULL,
  `image_gallery` enum('0','1') collate utf8_swedish_ci NOT NULL,
  `image_width` smallint(6) NOT NULL,
  `image_height` smallint(6) NOT NULL,
  PRIMARY KEY  (`image_id`),
  KEY `text_id` (`text_id`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1142 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `text_meta_book`
--

DROP TABLE IF EXISTS `text_meta_book`;
CREATE TABLE `text_meta_book` (
  `text_id` int(10) unsigned NOT NULL,
  `book_grade` tinyint(3) unsigned NOT NULL,
  `book_author` varchar(100) collate utf8_swedish_ci NOT NULL,
  `book_series` varchar(100) collate utf8_swedish_ci NOT NULL,
  `book_volume` varchar(100) collate utf8_swedish_ci NOT NULL,
  PRIMARY KEY  (`text_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `text_meta_game`
--

DROP TABLE IF EXISTS `text_meta_game`;
CREATE TABLE `text_meta_game` (
  `text_id` int(10) unsigned NOT NULL,
  `game_grade` tinyint(3) unsigned NOT NULL,
  `game_type` varchar(100) collate utf8_swedish_ci NOT NULL,
  `game_distributor` varchar(100) collate utf8_swedish_ci NOT NULL,
  PRIMARY KEY  (`text_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `text_meta_movie`
--

DROP TABLE IF EXISTS `text_meta_movie`;
CREATE TABLE `text_meta_movie` (
  `text_id` int(10) unsigned NOT NULL,
  `movie_grade` tinyint(3) unsigned NOT NULL,
  `movie_director` varchar(100) collate utf8_swedish_ci NOT NULL,
  `movie_year` year(4) NOT NULL,
  `movie_length` int(11) NOT NULL,
  `movie_actors` varchar(1000) collate utf8_swedish_ci NOT NULL,
  PRIMARY KEY  (`text_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `text_meta_music`
--

DROP TABLE IF EXISTS `text_meta_music`;
CREATE TABLE `text_meta_music` (
  `text_id` int(10) unsigned NOT NULL,
  `music_grade` tinyint(3) unsigned NOT NULL,
  `music_artist` varchar(100) collate utf8_swedish_ci NOT NULL,
  `music_year` year(4) NOT NULL,
  `music_length` smallint(6) NOT NULL,
  `music_tracks` tinyint(4) NOT NULL,
  PRIMARY KEY  (`text_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `text_type`
--

DROP TABLE IF EXISTS `text_type`;
CREATE TABLE `text_type` (
  `type_id` int(10) unsigned NOT NULL auto_increment,
  `access_id` int(10) unsigned NOT NULL,
  `type_name` varchar(30) collate utf8_swedish_ci NOT NULL,
  `type_title` varchar(45) collate utf8_swedish_ci NOT NULL,
  `type_class` varchar(45) collate utf8_swedish_ci NOT NULL,
  `type_metatable` varchar(100) collate utf8_swedish_ci NOT NULL,
  PRIMARY KEY  USING BTREE (`type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `theme`
--

DROP TABLE IF EXISTS `theme`;
CREATE TABLE `theme` (
  `theme_id` tinyint(2) unsigned NOT NULL auto_increment,
  `theme_name` varchar(20) collate utf8_swedish_ci NOT NULL default '',
  `theme_adminonly` enum('0','1') collate utf8_swedish_ci NOT NULL default '0',
  `theme_description` mediumtext collate utf8_swedish_ci NOT NULL,
  PRIMARY KEY  (`theme_id`),
  UNIQUE KEY `theme_id` (`theme_id`),
  KEY `theme_id_2` (`theme_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci COMMENT='Category:Semi-static tables|';

--
-- Table structure for table `wiki`
--

DROP TABLE IF EXISTS `wiki`;
CREATE TABLE `wiki` (
  `wiki_id` smallint(6) NOT NULL auto_increment,
  `access_id` int(11) NOT NULL,
  `wiki_name` varchar(40) character set latin1 NOT NULL,
  `wiki_title` varchar(100) character set latin1 NOT NULL,
  PRIMARY KEY  (`wiki_id`),
  UNIQUE KEY `wiki_name` (`wiki_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Table structure for table `wiki_page`
--

DROP TABLE IF EXISTS `wiki_page`;
CREATE TABLE `wiki_page` (
  `page_id` mediumint(9) NOT NULL auto_increment,
  `wiki_id` smallint(6) NOT NULL,
  `page_parentid` mediumint(9) NOT NULL,
  `page_name` varchar(255) character set latin1 NOT NULL,
  `page_title` varchar(100) character set latin1 NOT NULL,
  `page_text` text character set latin1 NOT NULL,
  PRIMARY KEY  (`page_id`),
  UNIQUE KEY `wiki_id` (`wiki_id`,`page_title`),
  KEY `parent_id` (`page_parentid`),
  KEY `wiki_id_2` (`wiki_id`,`page_name`)
) ENGINE=InnoDB AUTO_INCREMENT=200 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2009-02-18 19:27:27
