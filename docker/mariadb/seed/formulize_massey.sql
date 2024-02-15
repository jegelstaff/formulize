-- MariaDB dump 10.19-11.1.2-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: formulize
-- ------------------------------------------------------
-- Server version	11.1.2-MariaDB-1:11.1.2+maria~ubu2204

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `formulize`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `formulize` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `formulize`;

--
-- Table structure for table `ai8k7Bba_autosearch_cat`
--

DROP TABLE IF EXISTS `ai8k7Bba_autosearch_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_autosearch_cat` (
  `cid` int(11) NOT NULL AUTO_INCREMENT,
  `cat_name` varchar(255) NOT NULL,
  `cat_url` text NOT NULL,
  PRIMARY KEY (`cid`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_autosearch_cat`
--

LOCK TABLES `ai8k7Bba_autosearch_cat` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_autosearch_cat` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_autosearch_cat` VALUES
(1,'Adsenses','/modules/system/admin.php?fct=adsense'),
(2,'Auto Tasks','/modules/system/admin.php?fct=autotasks'),
(3,'Avatars','/modules/system/admin.php?fct=avatars'),
(4,'Banners','/modules/system/admin.php?fct=banners'),
(5,'Block Positions','/modules/system/admin.php?fct=blockspadmin'),
(6,'Blocks','/modules/system/admin.php?fct=blocksadmin'),
(7,'Comments','/modules/system/admin.php?fct=comments'),
(8,'Custom Tags','/modules/system/admin.php?fct=customtag'),
(9,'Edit Users','/modules/system/admin.php?fct=users'),
(10,'Find Users','/modules/system/admin.php?fct=finduser'),
(11,'Groups','/modules/system/admin.php?fct=groups'),
(12,'Image Manager','/modules/system/admin.php?fct=images'),
(13,'Mail Users','/modules/system/admin.php?fct=mailusers'),
(14,'Mime Types','/modules/system/admin.php?fct=mimetype'),
(15,'Modules Admin','/modules/system/admin.php?fct=modulesadmin'),
(16,'Preferences','/modules/system/admin.php?fct=preferences'),
(17,'Ratings','/modules/system/admin.php?fct=rating'),
(18,'Smilies','/modules/system/admin.php?fct=smilies'),
(19,'Symlink Manager','/modules/system/admin.php?fct=pages'),
(20,'Templates','/modules/system/admin.php?fct=tplsets'),
(21,'User Ranks','/modules/system/admin.php?fct=userrank'),
(22,'Version','/modules/system/admin.php?fct=version');
/*!40000 ALTER TABLE `ai8k7Bba_autosearch_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_autosearch_list`
--

DROP TABLE IF EXISTS `ai8k7Bba_autosearch_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_autosearch_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cat_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `img` varchar(255) NOT NULL,
  `desc` text NOT NULL,
  `url` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_autosearch_list`
--

LOCK TABLES `ai8k7Bba_autosearch_list` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_autosearch_list` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_autosearch_list` VALUES
(1,1,'Adsenses','/modules/system/admin/adsense/images/adsense_small.png','Adsenses are tags that you can define and use anywhere on your website.','/modules/system/admin.php?fct=adsense'),
(2,2,'Auto Tasks','/modules/system/admin/autotasks/images/autotasks_small.png','Auto Tasks allow you to create a schedule of actions that the system will perform automatically.','/modules/system/admin.php?fct=autotasks'),
(3,3,'Avatars','/modules/system/admin/avatars/images/avatars_small.png','Manage the avatars available to the users of your website.','/modules/system/admin.php?fct=avatars'),
(4,4,'Banners','/modules/system/admin/banners/images/banners_small.png','Manage ad campaigns and advertiser accounts.','/modules/system/admin.php?fct=banners'),
(5,5,'Block Positions','/modules/system/admin/blockspadmin/images/blockspadmin_small.png','Manage and create blocks positions that are used within the themes on your website.','/modules/system/admin.php?fct=blockspadmin'),
(6,6,'Blocks','/modules/system/admin/blocksadmin/images/blocksadmin_small.png','Manage and create blocks used throughout your website.','/modules/system/admin.php?fct=blocksadmin'),
(7,7,'Comments','/modules/system/admin/comments/images/comments_small.png','Manage the comments made by users on your website.','/modules/system/admin.php?fct=comments'),
(8,8,'Custom Tags','/modules/system/admin/customtag/images/customtag_small.png','Custom Tags are tags that you can define and use anywhere on your website.','/modules/system/admin.php?fct=customtag'),
(9,9,'Edit Users','/modules/system/admin/users/images/users_small.png','Create, Modify or Delete registered users.','/modules/system/admin.php?fct=users'),
(10,10,'Find Users','/modules/system/admin/findusers/images/findusers_small.png','Search through registered users with filters.','/modules/system/admin.php?fct=findusers'),
(11,11,'Groups','/modules/system/admin/groups/images/groups_small.png','Manage permissions, members, visibility and access rights of groups of users.','/modules/system/admin.php?fct=groups'),
(12,12,'Image Manager','/modules/system/admin/images/images/images_small.png','Create groups of images and manage the permissions for each group. Crop and resize uploaded photos.','/modules/system/admin.php?fct=images'),
(13,13,'Mail Users','/modules/system/admin/mailusers/images/mailusers_small.png','Send mail to users of whole groups - or filter recipients based on matching criteria.','/modules/system/admin.php?fct=mailusers'),
(14,14,'Mime Types','/modules/system/admin/mimetype/images/mimetype_small.png','Manage the allowed extensions for files uploaded to your website.','/modules/system/admin.php?fct=mimetype'),
(15,15,'Modules Admin','/modules/system/admin/modulesadmin/images/modulesadmin_small.png','Manage modules menu weight, status, name or update modules as needed.','/modules/system/admin.php?fct=modulesadmin'),
(16,16,'Preferences - Authentication','/modules/system/admin/preferences/images/preferences_small.png','Manage security settings related to accessibility. Settings that will effect how users accounts are handled.','/modules/system/admin.php?fct=preferences&op=show&confcat_id=7'),
(17,16,'Preferences - Auto Tasks','/modules/system/admin/preferences/images/preferences_small.png','Preferences for the Auto Tasks system.','/modules/system/admin.php?fct=preferences&op=show&confcat_id=13'),
(18,16,'Preferences - Captcha Settings','/modules/system/admin/preferences/images/preferences_small.png','Manage the settings used by captcha throughout your site.','/modules/system/admin.php?fct=preferences&op=show&confcat_id=11'),
(19,16,'Preferences - General Settings','/modules/system/admin/preferences/images/preferences_small.png','The primary settings page for basic information needed by the system.','/modules/system/admin.php?fct=preferences&op=show&confcat_id=1'),
(20,16,'Preferences - HTMLPurifier Settings','/modules/system/admin/preferences/images/preferences_small.png','HTMLPurifier is used to protect your site against common attack methods.','/modules/system/admin.php?fct=preferences&op=show&confcat_id=14'),
(21,16,'Preferences - Mail Setup','/modules/system/admin/preferences/images/preferences_small.png','Configure how your site will handle mail.','/modules/system/admin.php?fct=preferences&op=show&confcat_id=6'),
(22,16,'Preferences - Meta + Footer','/modules/system/admin/preferences/images/preferences_small.png','Manage your meta information and site footer as well as your crawler options.','/modules/system/admin/preferences/images/preferences_small.png'),
(23,16,'Preferences - Multilanguage','/modules/system/admin/preferences/images/preferences_small.png','Manage your sites Multi-language settings. Enable, and configure what languages are available and how they are triggered.','/modules/system/admin.php?fct=preferences&op=show&confcat_id=8'),
(24,16,'Preferences - Personalization','/modules/system/admin/preferences/images/preferences_small.png','Personalize the system with custom logos and other settings.','/modules/system/admin.php?fct=preferences&op=show&confcat_id=10'),
(25,16,'Preferences - Plugins Manager','/modules/system/admin/preferences/images/preferences_small.png','Select which plugins are used and available to be used throughout your site.','/modules/system/admin.php?fct=preferences&op=show&confcat_id=12'),
(26,16,'Preferences - Search Options','/modules/system/admin/preferences/images/preferences_small.png','Manage how the search function operates for your users.','/modules/system/admin.php?fct=preferences&op=show&confcat_id=5'),
(27,16,'Preferences - User Settings','/modules/system/admin/preferences/images/preferences_small.png','Manage how users register for your site. ser names length, formatting and password options.','/modules/system/admin.php?fct=preferences&op=show&confcat_id=2'),
(28,16,'Preferences - Word Censoring','/modules/system/admin/preferences/images/preferences_small.png','Manage the language that is not permitted on your site.','/modules/system/admin.php?fct=preferences&op=show&confcat_id=4'),
(29,17,'Ratings','/modules/system/admin/rating/images/rating_small.png','With using this tool, you can add a new rating method to your modules, and control the results through this section!','/modules/system/admin.php?fct=rating'),
(30,18,'Smilies','/modules/system/admin/smilies/images/smilies_small.png','Manage the available smilies and define the code associatted with each.','/modules/system/admin.php?fct=smilies'),
(31,19,'Symlink Manager','/modules/system/admin/pages/images/pages_small.png','Symlink allows you to create a unique link based on any page of your website, which can be used for blocks specific to a page URL, or to link directly within the content of a module.','/modules/system/admin.php?fct=pages'),
(32,20,'Templates','/modules/system/admin/tplsets/images/tplsets_small.png','Templates are sets of html/css files that render the screen layout of modules.','/modules/system/admin.php?fct=tplsets'),
(33,21,'User Ranks','/modules/system/admin/userrank/images/userrank_small.png','User ranks are picture, used to make difference between users in different levels of your website!','/modules/system/admin.php?fct=userrank'),
(34,22,'Version Checker','/modules/system/admin/version/images/version_small.png','Use this tool to check your system for updates.','/modules/system/admin.php?fct=version');
/*!40000 ALTER TABLE `ai8k7Bba_autosearch_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_avatar`
--

DROP TABLE IF EXISTS `ai8k7Bba_avatar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_avatar` (
  `avatar_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `avatar_file` varchar(30) NOT NULL DEFAULT '',
  `avatar_name` varchar(100) NOT NULL DEFAULT '',
  `avatar_mimetype` varchar(30) NOT NULL DEFAULT '',
  `avatar_created` int(10) NOT NULL DEFAULT 0,
  `avatar_display` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `avatar_weight` smallint(5) unsigned NOT NULL DEFAULT 0,
  `avatar_type` char(1) NOT NULL DEFAULT '',
  PRIMARY KEY (`avatar_id`),
  KEY `avatar_type` (`avatar_type`,`avatar_display`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_avatar`
--

LOCK TABLES `ai8k7Bba_avatar` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_avatar` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_avatar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_avatar_user_link`
--

DROP TABLE IF EXISTS `ai8k7Bba_avatar_user_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_avatar_user_link` (
  `avatar_id` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT 0,
  KEY `avatar_user_id` (`avatar_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_avatar_user_link`
--

LOCK TABLES `ai8k7Bba_avatar_user_link` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_avatar_user_link` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_avatar_user_link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_banner`
--

DROP TABLE IF EXISTS `ai8k7Bba_banner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_banner` (
  `bid` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `cid` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `imptotal` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `impmade` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `clicks` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `imageurl` varchar(255) NOT NULL DEFAULT '',
  `clickurl` varchar(255) NOT NULL DEFAULT '',
  `date` int(10) NOT NULL DEFAULT 0,
  `htmlbanner` tinyint(1) NOT NULL DEFAULT 0,
  `htmlcode` text NOT NULL,
  PRIMARY KEY (`bid`),
  KEY `idxbannercid` (`cid`),
  KEY `idxbannerbidcid` (`bid`,`cid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_banner`
--

LOCK TABLES `ai8k7Bba_banner` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_banner` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_banner` VALUES
(1,1,0,1,0,'https://uoc.formulize.net/images/banners/impresscms_banner.gif','http://www.impresscms.org/',1008813250,0,''),
(2,1,0,1,0,'https://uoc.formulize.net/images/banners/impresscms_banner_2.gif','http://www.impresscms.org/',1008813250,0,''),
(3,1,0,1,0,'https://uoc.formulize.net/images/banners/banner.swf','http://www.impresscms.org/',1008813250,0,''),
(4,1,0,1,0,'https://uoc.formulize.net/images/banners/impresscms_banner_3.gif','http://www.impresscms.org/',1008813250,0,'');
/*!40000 ALTER TABLE `ai8k7Bba_banner` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_bannerclient`
--

DROP TABLE IF EXISTS `ai8k7Bba_bannerclient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_bannerclient` (
  `cid` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL DEFAULT '',
  `contact` varchar(60) NOT NULL DEFAULT '',
  `email` varchar(60) NOT NULL DEFAULT '',
  `login` varchar(10) NOT NULL DEFAULT '',
  `passwd` varchar(10) NOT NULL DEFAULT '',
  `extrainfo` text NOT NULL,
  PRIMARY KEY (`cid`),
  KEY `login` (`login`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_bannerclient`
--

LOCK TABLES `ai8k7Bba_bannerclient` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_bannerclient` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_bannerclient` VALUES
(1,'ImpressCMS','ImpressCMS Dev Team','info@impresscms.org','','','');
/*!40000 ALTER TABLE `ai8k7Bba_bannerclient` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_bannerfinish`
--

DROP TABLE IF EXISTS `ai8k7Bba_bannerfinish`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_bannerfinish` (
  `bid` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `cid` smallint(5) unsigned NOT NULL DEFAULT 0,
  `impressions` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `clicks` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `datestart` int(10) unsigned NOT NULL DEFAULT 0,
  `dateend` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`bid`),
  KEY `cid` (`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_bannerfinish`
--

LOCK TABLES `ai8k7Bba_bannerfinish` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_bannerfinish` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_bannerfinish` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_block_module_link`
--

DROP TABLE IF EXISTS `ai8k7Bba_block_module_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_block_module_link` (
  `block_id` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `module_id` smallint(5) NOT NULL DEFAULT 0,
  `page_id` smallint(5) NOT NULL DEFAULT 0,
  KEY `module_id` (`module_id`),
  KEY `block_id` (`block_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_block_module_link`
--

LOCK TABLES `ai8k7Bba_block_module_link` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_block_module_link` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_block_module_link` VALUES
(1,0,0),
(3,0,0),
(4,1,2),
(5,0,0),
(6,0,0),
(7,1,2),
(8,1,2),
(9,1,2),
(10,0,0),
(11,0,0),
(12,0,0),
(13,0,0),
(14,0,0),
(15,1,2),
(16,1,2),
(17,1,2),
(18,0,0),
(19,0,0),
(20,0,1),
(21,0,1),
(22,0,1),
(23,0,1),
(25,0,1),
(26,0,1),
(2,0,1),
(24,0,0);
/*!40000 ALTER TABLE `ai8k7Bba_block_module_link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_block_positions`
--

DROP TABLE IF EXISTS `ai8k7Bba_block_positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_block_positions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pname` varchar(30) DEFAULT '',
  `title` varchar(90) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `block_default` int(1) NOT NULL DEFAULT 0,
  `block_type` varchar(1) NOT NULL DEFAULT 'L',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_block_positions`
--

LOCK TABLES `ai8k7Bba_block_positions` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_block_positions` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_block_positions` VALUES
(1,'canvas_left','_AM_SBLEFT',NULL,1,'L'),
(2,'canvas_right','_AM_SBRIGHT',NULL,1,'L'),
(3,'page_topleft','_AM_CBLEFT',NULL,1,'C'),
(4,'page_topcenter','_AM_CBCENTER',NULL,1,'C'),
(5,'page_topright','_AM_CBRIGHT',NULL,1,'C'),
(6,'page_bottomleft','_AM_CBBOTTOMLEFT',NULL,1,'C'),
(7,'page_bottomcenter','_AM_CBBOTTOM',NULL,1,'C'),
(8,'page_bottomright','_AM_CBBOTTOMRIGHT',NULL,1,'C'),
(9,'canvas_left_admin','_AM_SBLEFT_ADMIN',NULL,1,'L'),
(10,'canvas_right_admin','_AM_SBRIGHT_ADMIN',NULL,1,'L'),
(11,'page_topleft_admin','_AM_CBLEFT_ADMIN',NULL,1,'C'),
(12,'page_topcenter_admin','_AM_CBCENTER_ADMIN',NULL,1,'C'),
(13,'page_topright_admin','_AM_CBRIGHT_ADMIN',NULL,1,'C'),
(14,'page_bottomleft_admin','_AM_CBBOTTOMLEFT_ADMIN',NULL,1,'C'),
(15,'page_bottomcenter_admin','_AM_CBBOTTOM_ADMIN',NULL,1,'C'),
(16,'page_bottomright_admin','_AM_CBBOTTOMRIGHT_ADMIN',NULL,1,'C');
/*!40000 ALTER TABLE `ai8k7Bba_block_positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_config`
--

DROP TABLE IF EXISTS `ai8k7Bba_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_config` (
  `conf_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `conf_modid` smallint(5) unsigned NOT NULL DEFAULT 0,
  `conf_catid` smallint(5) unsigned NOT NULL DEFAULT 0,
  `conf_name` varchar(75) NOT NULL DEFAULT '',
  `conf_title` varchar(255) NOT NULL DEFAULT '',
  `conf_value` text NOT NULL,
  `conf_desc` varchar(255) NOT NULL DEFAULT '',
  `conf_formtype` varchar(15) NOT NULL DEFAULT '',
  `conf_valuetype` varchar(10) NOT NULL DEFAULT '',
  `conf_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`conf_id`),
  KEY `mod_cat_order` (`conf_modid`,`conf_catid`,`conf_order`)
) ENGINE=InnoDB AUTO_INCREMENT=368 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_config`
--

LOCK TABLES `ai8k7Bba_config` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_config` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_config` VALUES
(1,0,1,'sitename','_MD_AM_SITENAME','massey','_MD_AM_SITENAMEDSC','textbox','text',0),
(2,0,1,'slogan','_MD_AM_SLOGAN','','_MD_AM_SLOGANDSC','textbox','text',1),
(3,0,1,'adminmail','_MD_AM_ADMINML','julian@polygon.red','_MD_AM_ADMINMLDSC','textbox','text',2),
(4,0,1,'language','_MD_AM_LANGUAGE','english','_MD_AM_LANGUAGEDSC','language','other',3),
(5,0,1,'startpage','_MD_AM_STARTPAGE','a:3:{i:1;s:9:\"formulize\";i:2;s:9:\"formulize\";i:3;s:2:\"--\";}','_MD_AM_STARTPAGEDSC','startpage','array',4),
(6,0,1,'server_TZ','_MD_AM_SERVERTZ','-8','_MD_AM_SERVERTZDSC','timezone','float',5),
(7,0,1,'default_TZ','_MD_AM_DEFAULTTZ','-5','_MD_AM_DEFAULTTZDSC','timezone','float',6),
(8,0,1,'use_ext_date','_MD_AM_EXT_DATE','1','_MD_AM_EXT_DATEDSC','yesno','int',7),
(9,0,1,'theme_set','_MD_AM_DTHEME','Anari','_MD_AM_DTHEMEDSC','theme','other',8),
(10,0,1,'theme_admin_set','_MD_AM_ADMIN_DTHEME','Anari','_MD_AM_ADMIN_DTHEME_DESC','theme_admin','other',9),
(11,0,1,'theme_fromfile','_MD_AM_THEMEFILE','1','_MD_AM_THEMEFILEDSC','yesno','int',10),
(12,0,1,'theme_set_allowed','_MD_AM_THEMEOK','a:2:{i:0;s:5:\"Anari\";i:1;s:20:\"formulize_standalone\";}','_MD_AM_THEMEOKDSC','theme_multi','array',11),
(13,0,1,'template_set','_MD_AM_DTPLSET','default','_MD_AM_DTPLSETDSC','tplset','other',12),
(14,0,1,'editor_default','_MD_AM_EDITOR_DEFAULT','dhtmltextarea','_MD_AM_EDITOR_DEFAULT_DESC','editor','text',13),
(15,0,1,'editor_enabled_list','_MD_AM_EDITOR_ENABLED_LIST','a:3:{i:0;s:13:\"dhtmltextarea\";i:1;s:9:\"FCKeditor\";i:2;s:7:\"tinymce\";}','_MD_AM_EDITOR_ENABLED_LIST_DESC','editor_multi','array',14),
(16,0,1,'sourceeditor_default','_MD_AM_SRCEDITOR_DEFAULT','editarea','_MD_AM_SRCEDITOR_DEFAULT_DESC','editor_source','text',15),
(17,0,1,'anonymous','_MD_AM_ANONNAME','Anonymous','_MD_AM_ANONNAMEDSC','textbox','text',16),
(18,0,1,'gzip_compression','_MD_AM_USEGZIP','0','_MD_AM_USEGZIPDSC','yesno','int',17),
(19,0,1,'usercookie','_MD_AM_USERCOOKIE','icms_user','_MD_AM_USERCOOKIEDSC','textbox','text',18),
(20,0,1,'use_mysession','_MD_AM_USEMYSESS','1','_MD_AM_USEMYSESSDSC','yesno','int',19),
(21,0,1,'session_name','_MD_AM_SESSNAME','icms_session','_MD_AM_SESSNAMEDSC','textbox','text',20),
(22,0,1,'session_expire','_MD_AM_SESSEXPIRE','60','_MD_AM_SESSEXPIREDSC','textbox','int',21),
(23,0,1,'debug_mode','_MD_AM_DEBUGMODE','1','_MD_AM_DEBUGMODEDSC','select','int',22),
(24,0,1,'banners','_MD_AM_BANNERS','0','_MD_AM_BANNERSDSC','yesno','int',23),
(25,0,1,'closesite','_MD_AM_CLOSESITE','0','_MD_AM_CLOSESITEDSC','yesno','int',24),
(26,0,1,'closesite_okgrp','_MD_AM_CLOSESITEOK','a:1:{i:0;s:1:\"1\";}','_MD_AM_CLOSESITEOKDSC','group_multi','array',25),
(27,0,1,'closesite_text','_MD_AM_CLOSESITETXT','The site is currently closed for maintenance. Please come back later.','_MD_AM_CLOSESITETXTDSC','textsarea','text',26),
(28,0,1,'my_ip','_MD_AM_MYIP','127.0.0.1','_MD_AM_MYIPDSC','textbox','text',27),
(29,0,1,'use_ssl','_MD_AM_USESSL','0','_MD_AM_USESSLDSC','yesno','int',28),
(30,0,1,'sslpost_name','_MD_AM_SSLPOST','icms_ssl','_MD_AM_SSLPOSTDSC','textbox','text',29),
(31,0,1,'sslloginlink','_MD_AM_SSLLINK','https://','_MD_AM_SSLLINKDSC','textbox','text',30),
(32,0,1,'com_mode','_MD_AM_COMMODE','nest','_MD_AM_COMMODEDSC','select','text',31),
(33,0,1,'com_order','_MD_AM_COMORDER','0','_MD_AM_COMORDERDSC','select','int',32),
(34,0,1,'use_captchaf','_MD_AM_USECAPTCHAFORM','1','_MD_AM_USECAPTCHAFORMDSC','yesno','int',33),
(35,0,1,'enable_badips','_MD_AM_DOBADIPS','0','_MD_AM_DOBADIPSDSC','yesno','int',34),
(36,0,1,'bad_ips','_MD_AM_BADIPS','a:1:{i:0;s:9:\"127.0.0.1\";}','_MD_AM_BADIPSDSC','textsarea','array',35),
(37,0,1,'module_cache','_MD_AM_MODCACHE','a:3:{i:2;s:1:\"0\";i:3;s:1:\"0\";i:4;s:1:\"0\";}','_MD_AM_MODCACHEDSC','module_cache','array',36),
(38,0,2,'allow_register','_MD_AM_ALLOWREG','0','_MD_AM_ALLOWREGDSC','yesno','int',0),
(39,0,2,'minpass','_MD_AM_MINPASS','5','_MD_AM_MINPASSDSC','textbox','int',1),
(40,0,2,'pass_level','_MD_AM_PASSLEVEL','40','_MD_AM_PASSLEVEL_DESC','select','int',2),
(41,0,2,'minuname','_MD_AM_MINUNAME','3','_MD_AM_MINUNAMEDSC','textbox','int',3),
(42,0,2,'maxuname','_MD_AM_MAXUNAME','20','_MD_AM_MAXUNAMEDSC','textbox','int',4),
(43,0,2,'delusers','_MD_AM_DELUSRES','30','_MD_AM_DELUSRESDSC','textbox','int',5),
(44,0,2,'use_captcha','_MD_AM_USECAPTCHA','1','_MD_AM_USECAPTCHADSC','yesno','int',6),
(45,0,2,'welcome_msg','_MD_AM_WELCOMEMSG','0','_MD_AM_WELCOMEMSGDSC','yesno','int',7),
(46,0,2,'welcome_msg_content','_MD_AM_WELCOMEMSG_CONTENT','Welcome {UNAME},\r\n\r\nYour account has been successfully activated on {X_SITENAME}. As a member of our site, you will benefit from all the features reserved to registered members !\r\n\r\nOnce again, welcome to our site. Visit us often !\r\n\r\nIf you did not registered to our site, please contact us at the following address {X_ADMINMAIL}, and we will fix the situation.\r\n\r\n-----------\r\nYours truly,\r\n{X_SITENAME}\r\n{X_SITEURL}','_MD_AM_WELCOMEMSG_CONTENTDSC','textsarea','text',8),
(47,0,2,'allow_chgmail','_MD_AM_ALLWCHGMAIL','0','_MD_AM_ALLWCHGMAILDSC','yesno','int',9),
(48,0,2,'allow_chguname','_MD_AM_ALLWCHGUNAME','0','_MD_AM_ALLWCHGUNAMEDSC','yesno','int',10),
(49,0,2,'allwshow_sig','_MD_AM_ALLWSHOWSIG','1','_MD_AM_ALLWSHOWSIGDSC','yesno','int',11),
(50,0,2,'allow_htsig','_MD_AM_ALLWHTSIG','1','_MD_AM_ALLWHTSIGDSC','yesno','int',12),
(51,0,2,'sig_max_length','_MD_AM_SIGMAXLENGTH','255','_MD_AM_SIGMAXLENGTHDSC','textbox','int',13),
(52,0,2,'new_user_notify','_MD_AM_NEWUNOTIFY','1','_MD_AM_NEWUNOTIFYDSC','yesno','int',14),
(53,0,2,'new_user_notify_group','_MD_AM_NOTIFYTO','1','_MD_AM_NOTIFYTODSC','group','int',15),
(54,0,2,'activation_type','_MD_AM_ACTVTYPE','0','_MD_AM_ACTVTYPEDSC','select','int',16),
(55,0,2,'activation_group','_MD_AM_ACTVGROUP','1','_MD_AM_ACTVGROUPDSC','group','int',17),
(56,0,2,'uname_test_level','_MD_AM_UNAMELVL','2','_MD_AM_UNAMELVLDSC','select','int',18),
(57,0,2,'avatar_allow_upload','_MD_AM_AVATARALLOW','0','_MD_AM_AVATARALWDSC','yesno','int',19),
(58,0,2,'avatar_allow_gravatar','_MD_AM_GRAVATARALLOW','1','_MD_AM_GRAVATARALWDSC','yesno','int',20),
(59,0,2,'avatar_minposts','_MD_AM_AVATARMP','0','_MD_AM_AVATARMPDSC','textbox','int',21),
(60,0,2,'avatar_width','_MD_AM_AVATARW','80','_MD_AM_AVATARWDSC','textbox','int',22),
(61,0,2,'avatar_height','_MD_AM_AVATARH','80','_MD_AM_AVATARHDSC','textbox','int',23),
(62,0,2,'avatar_maxsize','_MD_AM_AVATARMAX','35000','_MD_AM_AVATARMAXDSC','textbox','int',24),
(63,0,2,'self_delete','_MD_AM_SELFDELETE','0','_MD_AM_SELFDELETEDSC','yesno','int',25),
(64,0,2,'rank_width','_MD_AM_RANKW','120','_MD_AM_RANKWDSC','textbox','int',26),
(65,0,2,'rank_height','_MD_AM_RANKH','120','_MD_AM_RANKHDSC','textbox','int',27),
(66,0,2,'rank_maxsize','_MD_AM_RANKMAX','35000','_MD_AM_RANKMAXDSC','textbox','int',28),
(67,0,2,'bad_unames','_MD_AM_BADUNAMES','a:3:{i:0;s:9:\"webmaster\";i:1;s:11:\"^impresscms\";i:2;s:6:\"^admin\";}','_MD_AM_BADUNAMESDSC','textsarea','array',29),
(68,0,2,'bad_emails','_MD_AM_BADEMAILS','a:1:{i:0;s:15:\"impresscms.org$\";}','_MD_AM_BADEMAILSDSC','textsarea','array',30),
(69,0,2,'remember_me','_MD_AM_REMEMBERME','0','_MD_AM_REMEMBERMEDSC','yesno','int',31),
(70,0,2,'reg_dispdsclmr','_MD_AM_DSPDSCLMR','0','_MD_AM_DSPDSCLMRDSC','yesno','int',32),
(71,0,2,'reg_disclaimer','_MD_AM_REGDSCLMR','While the administrators and moderators of this site will attempt to remove or edit any generally objectionable material as quickly as possible, it is impossible to review every message. Therefore you acknowledge that all posts made to this site express the views and opinions of the author and not the administrators, moderators or webmaster (except for posts by these people) and hence will not be held liable.\r\n\r\nYou agree not to post any abusive, obscene, vulgar, slanderous, hateful, threatening, sexually-orientated or any other material that may violate any applicable laws. Doing so may lead to you being immediately and permanently banned (and your service provider being informed). The IP address of all posts is recorded to aid in enforcing these conditions. Creating multiple accounts for a single user is not allowed. You agree that the webmaster, administrator and moderators of this site have the right to remove, edit, move or close any topic at any time should they see fit. As a user you agree to any information you have entered above being stored in a database. While this information will not be disclosed to any third party without your consent the webmaster, administrator and moderators cannot be held responsible for any hacking attempt that may lead to the data being compromised.\r\n\r\nThis site system uses cookies to store information on your local computer. These cookies do not contain any of the information you have entered above, they serve only to improve your viewing pleasure. The email address is used only for confirming your registration details and password (and for sending new passwords should you forget your current one).\r\n\r\nBy clicking Register below you agree to be bound by these conditions.','_MD_AM_REGDSCLMRDSC','textsarea','text',33),
(72,0,2,'priv_dpolicy','_MD_AM_PRIVDPOLICY','0','_MD_AM_PRIVDPOLICYDSC','yesno','int',34),
(73,0,2,'priv_policy','_MD_AM_PRIVPOLICY','&lt;p&gt;This privacy policy sets out how {X_SITENAME} uses and protects any information that you provide when you use this website. {X_SITENAME} is committed to ensuring that your privacy is protected. Should we ask you to provide certain information by which you can be identified when using this website, then you can be assured that it will only be used in accordance with this privacy statement. {X_SITENAME} may change this policy from time to time by updating this page. You should check this page from time to time to ensure that you are happy with any changes.\r\n&lt;/p&gt;&lt;p&gt;\r\nThis policy is effective from [date].\r\n&lt;/p&gt;\r\n&lt;h2&gt;What we collect&lt;/h2&gt;\r\n&lt;p&gt;\r\nWe may collect the following information:\r\n&lt;ul&gt;\r\n&lt;li&gt;name and job title&lt;/li&gt;\r\n&lt;li&gt;contact information including email address&lt;/li&gt;\r\n&lt;li&gt;demographic information such as postcode, preferences and interests&lt;/li&gt;\r\n&lt;li&gt;other information relevant to customer surveys and/or offers&lt;/li&gt;&lt;/ul&gt;\r\n&lt;/p&gt;\r\n&lt;h2&gt;What we do with the information we gather&lt;/h2&gt;\r\n&lt;p&gt;\r\nWe require this information to understand your needs and provide you with a better service, and in particular for the following reasons:\r\n&lt;ul&gt;\r\n&lt;li&gt;Internal record keeping.&lt;/li&gt;\r\n&lt;li&gt;We may use the information to improve our products and services.&lt;/li&gt;\r\n&lt;li&gt;We may periodically send promotional email about new products, special offers or other information which we think you may find interesting using the email address which you have provided.&lt;/li&gt;\r\n&lt;li&gt;From time to time, we may also use your information to contact you for market research purposes. We may contact you by email.&lt;/li&gt;\r\n&lt;li&gt;We may use the information to customise the website according to your interests.&lt;/li&gt;&lt;/ul&gt;\r\n&lt;/p&gt;\r\n&lt;h2&gt;Security&lt;/h2&gt;\r\n&lt;p&gt;\r\nWe are committed to ensuring that your information is secure. In order to prevent unauthorised access or disclosure we have put in place suitable physical, electronic and managerial procedures to safeguard and secure the information we collect online.\r\n&lt;/p&gt;\r\n&lt;h2&gt;How we use cookies&lt;/h2&gt;\r\n&lt;p&gt;\r\nA cookie is a small file which asks permission to be placed on your computer&#039;s hard drive. Once you agree, the file is added and the cookie helps analyse web traffic or lets you know when you visit a particular site. Cookies allow web applications to respond to you as an individual. The web application can tailor its operations to your needs, likes and dislikes by gathering and remembering information about your preferences.\r\n&lt;/p&gt;&lt;p&gt;\r\nWe use traffic log cookies to identify which pages are being used & for authenticating you as a registered member. This helps us analyse data about web page traffic and improve our website in order to tailor it to customer needs. We only use this information for statistical analysis purposes and then the data is removed from the system. Overall, cookies help us provide you with a better website, by enabling us to monitor which pages you find useful and which you do not. A cookie in no way gives us access to your computer or any information about you, other than the data you choose to share with us.\r\n&lt;/p&gt;&lt;p&gt;\r\nYou can choose to accept or decline cookies. Most web browsers automatically accept cookies, but you can usually modify your browser setting to decline cookies if you prefer. This may prevent you from taking full advantage of the website including registration and logging in.\r\n&lt;/p&gt;\r\n&lt;h2&gt;Links to other websites&lt;/h2&gt;\r\n&lt;p&gt;\r\nOur website may contain links to enable you to visit other websites of interest easily. However, once you have used these links to leave our site, you should note that we do not have any control over that other website. Therefore, we cannot be responsible for the protection and privacy of any information which you provide whilst visiting such sites and such sites are not governed by this privacy statement. You should exercise caution and look at the privacy statement applicable to the website in question.\r\n&lt;/p&gt;\r\n&lt;h2&gt;Controlling your personal information&lt;/h2&gt;\r\n&lt;p&gt;\r\nYou may choose to restrict the collection or use of your personal information in the following ways:\r\n&lt;ul&gt;\r\n&lt;li&gt;whenever you are asked to fill in a form on the website, look for the box that you can click to indicate that you do not want the information to be used by anybody for direct marketing purposes&lt;/li&gt;\r\n&lt;li&gt;if you have previously agreed to us using your personal information for direct marketing purposes, you may change your mind at any time by writing to or emailing us at [email address]&lt;/li&gt;&lt;/ul&gt;\r\n&lt;/p&gt;&lt;p&gt;\r\nWe will not sell, distribute or lease your personal information to third parties unless we have your permission or are required by law to do so. We may use your personal information to send you promotional information about third parties which we think you may find interesting if you tell us that you wish this to happen. You may request details of personal information which we hold about you under the Data Protection Act 1998. A small fee will be payable. If you would like a copy of the information held on you please write to [address].\r\n&lt;/p&gt;&lt;p&gt;\r\nIf you believe that any information we are holding on you is incorrect or incomplete, please write to or email us as soon as possible, at the above address. We will promptly correct any information found to be incorrect.\r\n&lt;/p&gt;','_MD_AM_PRIVPOLICYDSC','textsarea','text',35),
(74,0,2,'allow_annon_view_prof','_MD_AM_ALLOW_ANONYMOUS_VIEW_PROFILE','0','_MD_AM_ALLOW_ANONYMOUS_VIEW_PROFILE_DESC','yesno','int',36),
(75,0,2,'enc_type','_MD_AM_ENC_TYPE','1','_MD_AM_ENC_TYPEDSC','select','int',37),
(76,0,3,'meta_keywords','_MD_AM_METAKEY','','_MD_AM_METAKEYDSC','textsarea','text',0),
(77,0,3,'meta_description','_MD_AM_METADESC','Formulize is a data management and reporting system that lets you easily create forms on the web, and interact with the data people have submitted.','_MD_AM_METADESCDSC','textsarea','text',1),
(78,0,3,'meta_robots','_MD_AM_METAROBOTS','noindex,nofollow','_MD_AM_METAROBOTSDSC','select','text',2),
(79,0,3,'meta_rating','_MD_AM_METARATING','general','_MD_AM_METARATINGDSC','select','text',3),
(80,0,3,'meta_author','_MD_AM_METAAUTHOR','Formulize','_MD_AM_METAAUTHORDSC','textbox','text',4),
(81,0,3,'meta_copyright','_MD_AM_METACOPYR','Copyright © 2004-2022','_MD_AM_METACOPYRDSC','textbox','text',5),
(82,0,3,'google_meta','_MD_AM_METAGOOGLE','','_MD_AM_METAGOOGLE_DESC','textbox','text',6),
(83,0,3,'footer','_MD_AM_FOOTER','<a href=\"http://www.formulize.org/\" rel=\"external\" target=\"_blank\">Formulize Standalone Version</a> &copy; 2004-2022 &mdash; <a href=\"http://www.impresscms.org/\" rel=\"external\" target=\"_blank\">The ImpressCMS Project</a> &copy; 2007-2011 ','_MD_AM_FOOTERDSC','textsarea','text',7),
(84,0,3,'use_google_analytics','_MD_AM_USE_GOOGLE_ANA','0','_MD_AM_USE_GOOGLE_ANA_DESC','yesno','int',8),
(85,0,3,'google_analytics','_MD_AM_GOOGLE_ANA','','_MD_AM_GOOGLE_ANA_DESC','textbox','text',9),
(86,0,3,'footadm','_MD_AM_FOOTADM','<a href=\"http://www.formulize.org/\" rel=\"external\" target=\"_blank\">Formulize Standalone Version</a> &copy; 2004-2022 &mdash; <a href=\"http://www.impresscms.org/\" rel=\"external\" target=\"_blank\">The ImpressCMS Project</a> &copy; 2007-2011 ','_MD_AM_FOOTADM_DESC','textsarea','text',10),
(87,0,4,'censor_enable','_MD_AM_DOCENSOR','0','_MD_AM_DOCENSORDSC','yesno','int',0),
(88,0,4,'censor_words','_MD_AM_CENSORWRD','a:5:{i:0;s:4:\"fuck\";i:1;s:4:\"shit\";i:2;s:4:\"cunt\";i:3;s:6:\"wanker\";i:4;s:7:\"bastard\";}','_MD_AM_CENSORWRDDSC','textsarea','array',1),
(89,0,4,'censor_replace','_MD_AM_CENSORRPLC','#OOPS#','_MD_AM_CENSORRPLCDSC','textbox','text',2),
(90,0,5,'enable_search','_MD_AM_DOSEARCH','1','_MD_AM_DOSEARCHDSC','yesno','int',0),
(91,0,5,'enable_deep_search','_MD_AM_DODEEPSEARCH','1','_MD_AM_DODEEPSEARCHDSC','yesno','int',1),
(92,0,5,'num_shallow_search','_MD_AM_NUMINITSRCHRSLTS','5','_MD_AM_NUMINITSRCHRSLTSDSC','textbox','int',2),
(93,0,5,'keyword_min','_MD_AM_MINSEARCH','3','_MD_AM_MINSEARCHDSC','textbox','int',3),
(94,0,5,'search_user_date','_MD_AM_SEARCH_USERDATE','1','_MD_AM_SEARCH_USERDATE','yesno','int',4),
(95,0,5,'search_no_res_mod','_MD_AM_SEARCH_NO_RES_MOD','1','_MD_AM_SEARCH_NO_RES_MODDSC','yesno','int',5),
(96,0,5,'search_per_page','_MD_AM_SEARCH_PER_PAGE','20','_MD_AM_SEARCH_PER_PAGEDSC','textbox','int',6),
(97,0,6,'from','_MD_AM_MAILFROM','info@formulize.net','_MD_AM_MAILFROMDESC','textbox','text',0),
(98,0,6,'fromname','_MD_AM_MAILFROMNAME','Formulize.net','_MD_AM_MAILFROMNAMEDESC','textbox','text',1),
(99,0,6,'fromuid','_MD_AM_MAILFROMUID','1','_MD_AM_MAILFROMUIDDESC','user','int',2),
(100,0,6,'mailmethod','_MD_AM_MAILERMETHOD','mail','_MD_AM_MAILERMETHODDESC','select','text',3),
(101,0,6,'smtphost','_MD_AM_SMTPHOST','a:1:{i:0;s:0:\"\";}','_MD_AM_SMTPHOSTDESC','textsarea','array',4),
(102,0,6,'smtpuser','_MD_AM_SMTPUSER','','_MD_AM_SMTPUSERDESC','textbox','text',5),
(103,0,6,'smtppass','_MD_AM_SMTPPASS','','_MD_AM_SMTPPASSDESC','password','text',6),
(104,0,6,'smtpsecure','_MD_AM_SMTPSECURE','ssl','_MD_AM_SMTPSECUREDESC','select','text',7),
(105,0,6,'smtpauthport','_MD_AM_SMTPAUTHPORT','465','_MD_AM_SMTPAUTHPORTDESC','textbox','int',8),
(106,0,6,'sendmailpath','_MD_AM_SENDMAILPATH','/usr/sbin/sendmail','_MD_AM_SENDMAILPATHDESC','textbox','text',9),
(107,0,7,'auth_method','_MD_AM_AUTHMETHOD','xoops','_MD_AM_AUTHMETHODDESC','select','text',0),
(108,0,7,'auth_openid','_MD_AM_AUTHOPENID','0','_MD_AM_AUTHOPENIDDSC','yesno','int',1),
(109,0,7,'ldap_port','_MD_AM_LDAP_PORT','389','_MD_AM_LDAP_PORT','textbox','int',2),
(110,0,7,'ldap_server','_MD_AM_LDAP_SERVER','your directory server','_MD_AM_LDAP_SERVER_DESC','textbox','text',3),
(111,0,7,'ldap_base_dn','_MD_AM_LDAP_BASE_DN','dc=icms,dc=org','_MD_AM_LDAP_BASE_DN_DESC','textbox','text',4),
(112,0,7,'ldap_manager_dn','_MD_AM_LDAP_MANAGER_DN','manager_dn','_MD_AM_LDAP_MANAGER_DN_DESC','textbox','text',5),
(113,0,7,'ldap_manager_pass','_MD_AM_LDAP_MANAGER_PASS','manager_pass','_MD_AM_LDAP_MANAGER_PASS_DESC','password','text',6),
(114,0,7,'ldap_version','_MD_AM_LDAP_VERSION','3','_MD_AM_LDAP_VERSION_DESC','textbox','text',7),
(115,0,7,'ldap_users_bypass','_MD_AM_LDAP_USERS_BYPASS','a:1:{i:0;s:5:\"admin\";}','_MD_AM_LDAP_USERS_BYPASS_DESC','textsarea','array',8),
(116,0,7,'ldap_loginname_asdn','_MD_AM_LDAP_LOGINNAME_ASDN','uid_asdn','_MD_AM_LDAP_LOGINNAME_ASDN_D','yesno','int',9),
(117,0,7,'ldap_loginldap_attr','_MD_AM_LDAP_LOGINLDAP_ATTR','uid','_MD_AM_LDAP_LOGINLDAP_ATTR_D','textbox','text',10),
(118,0,7,'ldap_filter_person','_MD_AM_LDAP_FILTER_PERSON','','_MD_AM_LDAP_FILTER_PERSON_DESC','textbox','text',11),
(119,0,7,'ldap_domain_name','_MD_AM_LDAP_DOMAIN_NAME','mydomain','_MD_AM_LDAP_DOMAIN_NAME_DESC','textbox','text',12),
(120,0,7,'ldap_provisionning','_MD_AM_LDAP_PROVIS','0','_MD_AM_LDAP_PROVIS_DESC','yesno','int',13),
(121,0,7,'ldap_provisionning_group','_MD_AM_LDAP_PROVIS_GROUP','a:1:{i:0;s:1:\"2\";}','_MD_AM_LDAP_PROVIS_GROUP_DSC','group_multi','array',14),
(122,0,7,'ldap_mail_attr','_MD_AM_LDAP_MAIL_ATTR','mail','_MD_AM_LDAP_MAIL_ATTR_DESC','textbox','text',15),
(123,0,7,'ldap_givenname_attr','_MD_AM_LDAP_GIVENNAME_ATTR','givenname','_MD_AM_LDAP_GIVENNAME_ATTR_DSC','textbox','text',16),
(124,0,7,'ldap_surname_attr','_MD_AM_LDAP_SURNAME_ATTR','sn','_MD_AM_LDAP_SURNAME_ATTR_DESC','textbox','text',17),
(125,0,7,'ldap_field_mapping','_MD_AM_LDAP_FIELD_MAPPING_ATTR','email=mail|name=displayname','_MD_AM_LDAP_FIELD_MAPPING_DESC','textsarea','text',18),
(126,0,7,'ldap_provisionning_upd','_MD_AM_LDAP_PROVIS_UPD','1','_MD_AM_LDAP_PROVIS_UPD_DESC','yesno','int',19),
(127,0,7,'ldap_use_TLS','_MD_AM_LDAP_USETLS','0','_MD_AM_LDAP_USETLS_DESC','yesno','int',20),
(128,0,8,'ml_enable','_MD_AM_ML_ENABLE','1','_MD_AM_ML_ENABLEDEC','yesno','int',0),
(129,0,8,'ml_autoselect_enabled','_MD_AM_ML_AUTOSELECT_ENABLED','1','_MD_AM_ML_AUTOSELECT_ENABLED_DESC','yesno','int',1),
(130,0,8,'ml_tags','_MD_AM_ML_TAGS','en,fr','_MD_AM_ML_TAGSDSC','textbox','text',2),
(131,0,8,'ml_names','_MD_AM_ML_NAMES','english,french','_MD_AM_ML_NAMESDSC','textbox','text',3),
(132,0,8,'ml_captions','_MD_AM_ML_CAPTIONS','English,French','_MD_AM_ML_CAPTIONSDSC','textbox','text',4),
(133,0,8,'ml_charset','_MD_AM_ML_CHARSET','UTF-8,UTF-8','_MD_AM_ML_CHARSETDSC','textbox','text',5),
(134,0,10,'adm_left_logo','_MD_AM_LLOGOADM','/uploads/imagemanager/logos/img482278e29e81c.png','_MD_AM_LLOGOADM_DESC','select_image','text',0),
(135,0,10,'adm_left_logo_url','_MD_AM_LLOGOADM_URL','','_MD_AM_LLOGOADM_URL_DESC','textbox','text',1),
(136,0,10,'adm_left_logo_alt','_MD_AM_LLOGOADM_ALT','ImpressCMS','_MD_AM_LLOGOADM_ALT_DESC','textbox','text',2),
(137,0,10,'adm_right_logo','_MD_AM_RLOGOADM','','_MD_AM_RLOGOADM_DESC','select_image','text',3),
(138,0,10,'adm_right_logo_url','_MD_AM_RLOGOADM_URL','','_MD_AM_RLOGOADM_URL_DESC','textbox','text',4),
(139,0,10,'adm_right_logo_alt','_MD_AM_RLOGOADM_ALT','','_MD_AM_RLOGOADM_ALT_DESC','textbox','text',5),
(140,0,10,'rss_local','_MD_AM_RSSLOCAL','http://community.impresscms.org/modules/smartsection/backend.php','_MD_AM_RSSLOCAL_DESC','textbox','text',6),
(141,0,10,'editre_block','_MD_AM_EDITREMOVEBLOCK','1','_MD_AM_EDITREMOVEBLOCKDSC','yesno','int',7),
(142,0,10,'use_custom_redirection','_MD_AM_CUSTOMRED','1','_MD_AM_CUSTOMREDDSC','yesno','int',8),
(143,0,10,'multi_login','_MD_AM_MULTLOGINPREVENT','0','_MD_AM_MULTLOGINPREVENTDSC','yesno','int',9),
(144,0,10,'email_protect','_MD_AM_EMAILPROTECT','0','_MD_AM_EMAILPROTECTDSC','select','text',10),
(145,0,10,'email_font','_MD_AM_EMAILTTF','arial.ttf','_MD_AM_EMAILTTF_DESC','select_font','text',11),
(146,0,10,'email_font_len','_MD_AM_EMAILLEN','12','_MD_AM_EMAILLEN_DESC','textbox','int',12),
(147,0,10,'email_cor','_MD_AM_EMAILCOLOR','#000000','_MD_AM_EMAILCOLOR_DESC','color','text',13),
(148,0,10,'email_shadow','_MD_AM_EMAILSHADOW','#cccccc','_MD_AM_EMAILSHADOW_DESC','color','text',14),
(149,0,10,'shadow_x','_MD_AM_SHADOWX','2','_MD_AM_SHADOWX_DESC','textbox','int',15),
(150,0,10,'shadow_y','_MD_AM_SHADOWY','2','_MD_AM_SHADOWY_DESC','textbox','int',16),
(151,0,10,'recprvkey','_MD_AM_RECPRVKEY','','_MD_AM_RECPRVKEY_DESC','textbox','text',17),
(152,0,10,'recpubkey','_MD_AM_RECPUBKEY','','_MD_AM_RECPUBKEY_DESC','textbox','text',18),
(153,0,10,'shorten_url','_MD_AM_SHORTURL','0','_MD_AM_SHORTURLDSC','yesno','int',19),
(154,0,10,'max_url_long','_MD_AM_URLLEN','50','_MD_AM_URLLEN_DESC','textbox','int',20),
(155,0,10,'pre_chars_left','_MD_AM_PRECHARS','35','_MD_AM_PRECHARS_DESC','textbox','int',21),
(156,0,10,'last_chars_left','_MD_AM_LASTCHARS','10','_MD_AM_LASTCHARS_DESC','textbox','int',22),
(157,0,10,'show_impresscms_menu','_MD_AM_SHOW_ICMSMENU','1','_MD_AM_SHOW_ICMSMENU_DESC','yesno','int',23),
(158,0,10,'use_jsjalali','_MD_AM_JALALICAL','1','_MD_AM_JALALICALDSC','yesno','int',24),
(159,0,10,'pagstyle','_MD_AM_PAGISTYLE','default','_MD_AM_PAGISTYLE_DESC','select_paginati','text',25),
(160,0,11,'captcha_mode','_MD_AM_CAPTCHA_MODE','image','_MD_AM_CAPTCHA_MODEDSC','select','text',0),
(161,0,11,'captcha_skipmember','_MD_AM_CAPTCHA_SKIPMEMBER','a:1:{i:0;s:1:\"2\";}','_MD_AM_CAPTCHA_SKIPMEMBERDSC','group_multi','array',1),
(162,0,11,'captcha_casesensitive','_MD_AM_CAPTCHA_CASESENS','0','_MD_AM_CAPTCHA_CASESENSDSC','yesno','int',2),
(163,0,11,'captcha_skip_characters','_MD_AM_CAPTCHA_SKIPCHAR','a:5:{i:0;s:1:\"o\";i:1;s:1:\"0\";i:2;s:1:\"i\";i:3;s:1:\"l\";i:4;s:1:\"1\";}','_MD_AM_CAPTCHA_SKIPCHARDSC','textsarea','array',3),
(164,0,11,'captcha_maxattempt','_MD_AM_CAPTCHA_MAXATTEMP','8','_MD_AM_CAPTCHA_MAXATTEMPDSC','textbox','int',4),
(165,0,11,'captcha_num_chars','_MD_AM_CAPTCHA_NUMCHARS','4','_MD_AM_CAPTCHA_NUMCHARSDSC','textbox','int',5),
(166,0,11,'captcha_fontsize_min','_MD_AM_CAPTCHA_FONTMIN','10','_MD_AM_CAPTCHA_FONTMINDSC','textbox','int',6),
(167,0,11,'captcha_fontsize_max','_MD_AM_CAPTCHA_FONTMAX','12','_MD_AM_CAPTCHA_FONTMAXDSC','textbox','int',7),
(168,0,11,'captcha_background_type','_MD_AM_CAPTCHA_BGTYPE','100','_MD_AM_CAPTCHA_BGTYPEDSC','select','text',8),
(169,0,11,'captcha_background_num','_MD_AM_CAPTCHA_BGNUM','50','_MD_AM_CAPTCHA_BGNUMDSC','textbox','int',9),
(170,0,11,'captcha_polygon_point','_MD_AM_CAPTCHA_POLPNT','3','_MD_AM_CAPTCHA_POLPNTDSC','textbox','int',10),
(171,0,12,'sanitizer_plugins','_MD_AM_SELECTSPLUGINS','a:2:{i:0;s:18:\"syntaxhighlightphp\";i:1;s:13:\"hiddencontent\";}','_MD_AM_SELECTSPLUGINS_DESC','select_plugin','array',0),
(172,0,12,'code_sanitizer','_MD_AM_SELECTSHIGHLIGHT','none','_MD_AM_SELECTSHIGHLIGHT_DESC','select','text',1),
(173,0,12,'geshi_default','_MD_AM_GESHI_DEFAULT','php','_MD_AM_GESHI_DEFAULT_DESC','select_geshi','text',2),
(174,0,13,'autotasks_system','_MD_AM_AUTOTASKS_SYSTEM','internal','_MD_AM_AUTOTASKS_SYSTEMDSC','autotasksystem','text',0),
(175,0,13,'autotasks_helper','_MD_AM_AUTOTASKS_HELPER','wget %url%','_MD_AM_AUTOTASKS_HELPERDSC','select','text',1),
(176,0,13,'autotasks_helper_path','_MD_AM_AUTOTASKS_HELPER_PATH','/usr/bin/','_MD_AM_AUTOTASKS_HELPER_PATHDSC','text','text',2),
(177,0,13,'autotasks_user','_MD_AM_AUTOTASKS_USER','','_MD_AM_AUTOTASKS_USERDSC','text','text',3),
(178,0,14,'enable_purifier','_MD_AM_PURIFIER_ENABLE','1','_MD_AM_PURIFIER_ENABLEDSC','yesno','int',0),
(179,0,14,'purifier_URI_DefinitionID','_MD_AM_PURIFIER_URI_DEFID','system','_MD_AM_PURIFIER_URI_DEFIDDSC','textbox','text',1),
(180,0,14,'purifier_URI_DefinitionRev','_MD_AM_PURIFIER_URI_DEFREV','1','_MD_AM_PURIFIER_URI_DEFREVDSC','textbox','int',2),
(181,0,14,'purifier_URI_Host','_MD_AM_PURIFIER_URI_HOST','127.0.0.1','_MD_AM_PURIFIER_URI_HOSTDSC','textbox','text',3),
(182,0,14,'purifier_URI_Base','_MD_AM_PURIFIER_URI_BASE','127.0.0.1','_MD_AM_PURIFIER_URI_BASEDSC','textbox','text',4),
(183,0,14,'purifier_URI_Disable','_MD_AM_PURIFIER_URI_DISABLE','0','_MD_AM_PURIFIER_URI_DISABLEDSC','yesno','int',5),
(184,0,14,'purifier_URI_DisableExternal','_MD_AM_PURIFIER_URI_DISABLEEXT','0','_MD_AM_PURIFIER_URI_DISABLEEXTDSC','yesno','int',6),
(185,0,14,'purifier_URI_DisableExternalResources','_MD_AM_PURIFIER_URI_DISABLEEXTRES','0','_MD_AM_PURIFIER_URI_DISABLEEXTRESDSC','yesno','int',7),
(186,0,14,'purifier_URI_DisableResources','_MD_AM_PURIFIER_URI_DISABLERES','0','_MD_AM_PURIFIER_URI_DISABLERESDSC','yesno','int',8),
(187,0,14,'purifier_URI_MakeAbsolute','_MD_AM_PURIFIER_URI_MAKEABS','0','_MD_AM_PURIFIER_URI_MAKEABSDSC','yesno','int',9),
(188,0,14,'purifier_URI_HostBlacklist','_MD_AM_PURIFIER_URI_BLACKLIST','','_MD_AM_PURIFIER_URI_BLACKLISTDSC','textsarea','array',10),
(189,0,14,'purifier_URI_AllowedSchemes','_MD_AM_PURIFIER_URI_ALLOWSCHEME','a:6:{i:0;s:4:\"http\";i:1;s:5:\"https\";i:2;s:6:\"mailto\";i:3;s:3:\"ftp\";i:4;s:4:\"nntp\";i:5;s:4:\"news\";}','_MD_AM_PURIFIER_URI_ALLOWSCHEMEDSC','textsarea','array',11),
(190,0,14,'purifier_HTML_DefinitionID','_MD_AM_PURIFIER_HTML_DEFID','system','_MD_AM_PURIFIER_HTML_DEFIDDSC','textbox','text',12),
(191,0,14,'purifier_HTML_DefinitionRev','_MD_AM_PURIFIER_HTML_DEFREV','1','_MD_AM_PURIFIER_HTML_DEFREVDSC','textbox','int',13),
(192,0,14,'purifier_HTML_Doctype','_MD_AM_PURIFIER_HTML_DOCTYPE','XHTML 1.0 Transitional','_MD_AM_PURIFIER_HTML_DOCTYPEDSC','select','text',14),
(193,0,14,'purifier_HTML_TidyLevel','_MD_AM_PURIFIER_HTML_TIDYLEVEL','medium','_MD_AM_PURIFIER_HTML_TIDYLEVELDSC','select','text',15),
(194,0,14,'purifier_HTML_AllowedElements','_MD_AM_PURIFIER_HTML_ALLOWELE','a:48:{i:0;s:1:\"a\";i:1;s:4:\"abbr\";i:2;s:7:\"acronym\";i:3;s:1:\"b\";i:4;s:10:\"blockquote\";i:5;s:2:\"br\";i:6;s:7:\"caption\";i:7;s:4:\"cite\";i:8;s:4:\"code\";i:9;s:2:\"dd\";i:10;s:3:\"del\";i:11;s:3:\"dfn\";i:12;s:3:\"div\";i:13;s:2:\"dl\";i:14;s:2:\"dt\";i:15;s:2:\"em\";i:16;s:4:\"font\";i:17;s:2:\"h1\";i:18;s:2:\"h2\";i:19;s:2:\"h3\";i:20;s:2:\"h4\";i:21;s:2:\"h5\";i:22;s:2:\"h6\";i:23;s:1:\"i\";i:24;s:3:\"img\";i:25;s:3:\"ins\";i:26;s:3:\"kbd\";i:27;s:2:\"li\";i:28;s:2:\"ol\";i:29;s:1:\"p\";i:30;s:3:\"pre\";i:31;s:1:\"s\";i:32;s:4:\"span\";i:33;s:6:\"strike\";i:34;s:6:\"strong\";i:35;s:3:\"sub\";i:36;s:3:\"sup\";i:37;s:5:\"table\";i:38;s:5:\"tbody\";i:39;s:2:\"td\";i:40;s:5:\"tfoot\";i:41;s:2:\"th\";i:42;s:5:\"thead\";i:43;s:2:\"tr\";i:44;s:2:\"tt\";i:45;s:1:\"u\";i:46;s:2:\"ul\";i:47;s:3:\"var\";}','_MD_AM_PURIFIER_HTML_ALLOWELEDSC','textsarea','array',16),
(195,0,14,'purifier_HTML_AllowedAttributes','_MD_AM_PURIFIER_HTML_ALLOWATTR','a:67:{i:0;s:7:\"a.class\";i:1;s:6:\"a.href\";i:2;s:4:\"a.id\";i:3;s:6:\"a.name\";i:4;s:5:\"a.rev\";i:5;s:7:\"a.style\";i:6;s:7:\"a.title\";i:7;s:8:\"a.target\";i:8;s:5:\"a.rel\";i:9;s:10:\"abbr.title\";i:10;s:13:\"acronym.title\";i:11;s:15:\"blockquote.cite\";i:12;s:9:\"div.align\";i:13;s:9:\"div.style\";i:14;s:9:\"div.class\";i:15;s:6:\"div.id\";i:16;s:9:\"font.size\";i:17;s:10:\"font.color\";i:18;s:8:\"h1.style\";i:19;s:8:\"h2.style\";i:20;s:8:\"h3.style\";i:21;s:8:\"h4.style\";i:22;s:8:\"h5.style\";i:23;s:8:\"h6.style\";i:24;s:7:\"img.src\";i:25;s:7:\"img.alt\";i:26;s:9:\"img.title\";i:27;s:9:\"img.class\";i:28;s:9:\"img.align\";i:29;s:9:\"img.style\";i:30;s:10:\"img.height\";i:31;s:9:\"img.width\";i:32;s:8:\"li.style\";i:33;s:8:\"ol.style\";i:34;s:7:\"p.style\";i:35;s:10:\"span.style\";i:36;s:10:\"span.class\";i:37;s:7:\"span.id\";i:38;s:11:\"table.class\";i:39;s:8:\"table.id\";i:40;s:12:\"table.border\";i:41;s:17:\"table.cellpadding\";i:42;s:17:\"table.cellspacing\";i:43;s:11:\"table.style\";i:44;s:11:\"table.width\";i:45;s:7:\"td.abbr\";i:46;s:8:\"td.align\";i:47;s:8:\"td.class\";i:48;s:5:\"td.id\";i:49;s:10:\"td.colspan\";i:50;s:10:\"td.rowspan\";i:51;s:8:\"td.style\";i:52;s:9:\"td.valign\";i:53;s:8:\"tr.align\";i:54;s:8:\"tr.class\";i:55;s:5:\"tr.id\";i:56;s:8:\"tr.style\";i:57;s:9:\"tr.valign\";i:58;s:7:\"th.abbr\";i:59;s:8:\"th.align\";i:60;s:8:\"th.class\";i:61;s:5:\"th.id\";i:62;s:10:\"th.colspan\";i:63;s:10:\"th.rowspan\";i:64;s:8:\"th.style\";i:65;s:9:\"th.valign\";i:66;s:8:\"ul.style\";}','_MD_AM_PURIFIER_HTML_ALLOWATTRDSC','textsarea','array',17),
(196,0,14,'purifier_HTML_ForbiddenElements','_MD_AM_PURIFIER_HTML_FORBIDELE','','_MD_AM_PURIFIER_HTML_FORBIDELEDSC','textsarea','array',18),
(197,0,14,'purifier_HTML_ForbiddenAttributes','_MD_AM_PURIFIER_HTML_FORBIDATTR','','_MD_AM_PURIFIER_HTML_FORBIDATTRDSC','textsarea','array',19),
(198,0,14,'purifier_HTML_MaxImgLength','_MD_AM_PURIFIER_HTML_MAXIMGLENGTH','1200','_MD_AM_PURIFIER_HTML_MAXIMGLENGTHDSC','textbox','int',20),
(199,0,14,'purifier_HTML_SafeEmbed','_MD_AM_PURIFIER_HTML_SAFEEMBED','0','_MD_AM_PURIFIER_HTML_SAFEEMBEDDSC','yesno','int',21),
(200,0,14,'purifier_HTML_SafeObject','_MD_AM_PURIFIER_HTML_SAFEOBJECT','0','_MD_AM_PURIFIER_HTML_SAFEOBJECTDSC','yesno','int',22),
(201,0,14,'purifier_HTML_AttrNameUseCDATA','_MD_AM_PURIFIER_HTML_ATTRNAMEUSECDATA','0','_MD_AM_PURIFIER_HTML_ATTRNAMEUSECDATADSC','yesno','int',23),
(202,0,14,'purifier_Filter_ExtractStyleBlocks','_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLK','1','_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKDSC','yesno','int',26),
(203,0,14,'purifier_Filter_ExtractStyleBlocks_Escaping','_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEESC','1','_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEESCDSC','yesno','int',27),
(204,0,14,'purifier_Filter_ExtractStyleBlocks_Scope','_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKSCOPE','','_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKSCOPEDSC','textsarea','text',28),
(205,0,14,'purifier_Filter_YouTube','_MD_AM_PURIFIER_FILTER_ENABLEYOUTUBE','1','_MD_AM_PURIFIER_FILTER_ENABLEYOUTUBEDSC','yesno','int',29),
(206,0,14,'purifier_Core_EscapeNonASCIICharacters','_MD_AM_PURIFIER_CORE_ESCNONASCIICHARS','1','_MD_AM_PURIFIER_CORE_ESCNONASCIICHARSDSC','yesno','int',31),
(207,0,14,'purifier_Core_HiddenElements','_MD_AM_PURIFIER_CORE_HIDDENELE','a:2:{i:0;s:6:\"script\";i:1;s:5:\"style\";}','_MD_AM_PURIFIER_CORE_HIDDENELEDSC','textsarea','array',32),
(208,0,14,'purifier_Core_RemoveInvalidImg','_MD_AM_PURIFIER_CORE_REMINVIMG','1','_MD_AM_PURIFIER_CORE_REMINVIMGDSC','yesno','int',33),
(209,0,14,'purifier_AutoFormat_AutoParagraph','_MD_AM_PURIFIER_AUTO_AUTOPARA','0','_MD_AM_PURIFIER_AUTO_AUTOPARADSC','yesno','int',35),
(210,0,14,'purifier_AutoFormat_DisplayLinkURI','_MD_AM_PURIFIER_AUTO_DISPLINKURI','0','_MD_AM_PURIFIER_AUTO_DISPLINKURIDSC','yesno','int',36),
(211,0,14,'purifier_AutoFormat_Linkify','_MD_AM_PURIFIER_AUTO_LINKIFY','1','_MD_AM_PURIFIER_AUTO_LINKIFYDSC','yesno','int',37),
(212,0,14,'purifier_AutoFormat_PurifierLinkify','_MD_AM_PURIFIER_AUTO_PURILINKIFY','0','_MD_AM_PURIFIER_AUTO_PURILINKIFYDSC','yesno','int',38),
(213,0,14,'purifier_AutoFormat_Custom','_MD_AM_PURIFIER_AUTO_CUSTOM','','_MD_AM_PURIFIER_AUTO_CUSTOMDSC','textsarea','array',39),
(214,0,14,'purifier_AutoFormat_RemoveEmpty','_MD_AM_PURIFIER_AUTO_REMOVEEMPTY','0','_MD_AM_PURIFIER_AUTO_REMOVEEMPTYDSC','yesno','int',40),
(215,0,14,'purifier_AutoFormat_RemoveEmptyNbsp','_MD_AM_PURIFIER_AUTO_REMOVEEMPTYNBSP','0','_MD_AM_PURIFIER_AUTO_REMOVEEMPTYNBSPDSC','yesno','int',41),
(216,0,14,'purifier_AutoFormat_RemoveEmptyNbspExceptions','_MD_AM_PURIFIER_AUTO_REMOVEEMPTYNBSPEXCEPT','a:2:{i:0;s:2:\"td\";i:1;s:2:\"th\";}','_MD_AM_PURIFIER_AUTO_REMOVEEMPTYNBSPEXCEPTDSC','textsarea','array',42),
(217,0,14,'purifier_Attr_AllowedFrameTargets','_MD_AM_PURIFIER_ATTR_ALLOWFRAMETARGET','a:4:{i:0;s:6:\"_blank\";i:1;s:7:\"_parent\";i:2;s:5:\"_self\";i:3;s:4:\"_top\";}','_MD_AM_PURIFIER_ATTR_ALLOWFRAMETARGETDSC','textsarea','array',43),
(218,0,14,'purifier_Attr_AllowedRel','_MD_AM_PURIFIER_ATTR_ALLOWREL','a:4:{i:0;s:8:\"external\";i:1;s:8:\"nofollow\";i:2;s:17:\"external nofollow\";i:3;s:8:\"lightbox\";}','_MD_AM_PURIFIER_ATTR_ALLOWRELDSC','textsarea','array',44),
(219,0,14,'purifier_Attr_AllowedClasses','_MD_AM_PURIFIER_ATTR_ALLOWCLASSES','','_MD_AM_PURIFIER_ATTR_ALLOWCLASSESDSC','textsarea','array',45),
(220,0,14,'purifier_Attr_ForbiddenClasses','_MD_AM_PURIFIER_ATTR_FORBIDDENCLASSES','','_MD_AM_PURIFIER_ATTR_FORBIDDENCLASSESDSC','textsarea','array',46),
(221,0,14,'purifier_Attr_DefaultInvalidImage','_MD_AM_PURIFIER_ATTR_DEFINVIMG','','_MD_AM_PURIFIER_ATTR_DEFINVIMGDSC','textbox','text',47),
(222,0,14,'purifier_Attr_DefaultInvalidImageAlt','_MD_AM_PURIFIER_ATTR_DEFINVIMGALT','','_MD_AM_PURIFIER_ATTR_DEFINVIMGALTDSC','textbox','text',48),
(223,0,14,'purifier_Attr_DefaultImageAlt','_MD_AM_PURIFIER_ATTR_DEFIMGALT','','_MD_AM_PURIFIER_ATTR_DEFIMGALTDSC','textbox','text',49),
(224,0,14,'purifier_Attr_ClassUseCDATA','_MD_AM_PURIFIER_ATTR_CLASSUSECDATA','1','_MD_AM_PURIFIER_ATTR_CLASSUSECDATADSC','yesno','int',50),
(225,0,14,'purifier_Attr_EnableID','_MD_AM_PURIFIER_ATTR_ENABLEID','1','_MD_AM_PURIFIER_ATTR_ENABLEIDDSC','yesno','int',51),
(226,0,14,'purifier_Attr_IDPrefix','_MD_AM_PURIFIER_ATTR_IDPREFIX','','_MD_AM_PURIFIER_ATTR_IDPREFIXDSC','textbox','text',52),
(227,0,14,'purifier_Attr_IDPrefixLocal','_MD_AM_PURIFIER_ATTR_IDPREFIXLOCAL','','_MD_AM_PURIFIER_ATTR_IDPREFIXLOCALDSC','textbox','text',53),
(228,0,14,'purifier_Attr_IDBlacklist','_MD_AM_PURIFIER_ATTR_IDBLACKLIST','','_MD_AM_PURIFIER_ATTR_IDBLACKLISTDSC','textsarea','array',54),
(229,0,14,'purifier_CSS_DefinitionRev','_MD_AM_PURIFIER_CSS_DEFREV','1','_MD_AM_PURIFIER_CSS_DEFREVDSC','textbox','int',55),
(230,0,14,'purifier_CSS_AllowImportant','_MD_AM_PURIFIER_CSS_ALLOWIMPORTANT','1','_MD_AM_PURIFIER_CSS_ALLOWIMPORTANTDSC','yesno','int',56),
(231,0,14,'purifier_CSS_AllowTricky','_MD_AM_PURIFIER_CSS_ALLOWTRICKY','1','_MD_AM_PURIFIER_CSS_ALLOWTRICKYDSC','yesno','int',57),
(232,0,14,'purifier_CSS_AllowedProperties','_MD_AM_PURIFIER_CSS_ALLOWPROP','','_MD_AM_PURIFIER_CSS_ALLOWPROPDSC','textsarea','array',58),
(233,0,14,'purifier_CSS_MaxImgLength','_MD_AM_PURIFIER_CSS_MAXIMGLEN','1200px','_MD_AM_PURIFIER_CSS_MAXIMGLENDSC','textbox','text',59),
(234,0,14,'purifier_CSS_Proprietary','_MD_AM_PURIFIER_CSS_PROPRIETARY','1','_MD_AM_PURIFIER_CSS_PROPRIETARYDSC','yesno','int',60),
(235,0,14,'purifier_HTML_FlashAllowFullScreen','_MD_AM_PURIFIER_HTML_FLASHFULLSCRN','0','_MD_AM_PURIFIER_HTML_FLASHFULLSCRNDSC','yesno','int',24),
(236,0,14,'purifier_Output_FlashCompat','_MD_AM_PURIFIER_OUTPUT_FLASHCOMPAT','0','_MD_AM_PURIFIER_OUTPUT_FLASHCOMPATDSC','yesno','int',24),
(237,0,14,'purifier_Filter_AllowCustom','_MD_AM_PURIFIER_FILTER_ALLOWCUSTOM','0','_MD_AM_PURIFIER_FILTER_ALLOWCUSTOMDSC','yesno','int',30),
(238,0,14,'purifier_Core_NormalizeNewlines','_MD_AM_PURIFIER_CORE_NORMALNEWLINES','1','_MD_AM_PURIFIER_CORE_NORMALNEWLINESDSC','yesno','int',34),
(239,3,0,'default_page','_MI_CONTENT_CONTPAGE','0','_MI_CONTENT_CONTPAGEDSC','select_pages','int',0),
(240,3,0,'poster_groups','_MI_CONTENT_AUTHORGR','a:1:{i:0;s:1:\"1\";}','_MI_CONTENT_AUTHORGRDSC','group_multi','array',1),
(241,3,0,'contents_limit','_MI_CONTENT_LIMIT','5','_MI_CONTENT_LIMITDSC','textbox','text',2),
(242,3,0,'show_breadcrumb','_MI_CONTENT_SHOWBREADCRUMB','1','_MI_CONTENT_SHOWBREADCRUMBDSC','yesno','int',3),
(243,3,0,'show_relateds','_MI_CONTENT_SHOWRELATEDS','1','_MI_CONTENT_SHOWRELATEDSDSC','yesno','int',4),
(244,3,0,'show_contentinfo','_MI_CONTENT_SHOWINFO','1','_MI_CONTENT_SHOWINFODSC','yesno','int',5),
(245,3,0,'com_rule','_CM_COMRULES','1','','select','int',6),
(246,3,0,'com_anonpost','_CM_COMANONPOST','0','','yesno','int',7),
(247,3,0,'notification_enabled','_NOT_CONFIG_ENABLE','3','_NOT_CONFIG_ENABLEDSC','select','int',8),
(248,3,0,'notification_events','_NOT_CONFIG_EVENTS','a:1:{i:0;s:24:\"global-content_published\";}','_NOT_CONFIG_EVENTSDSC','select_multi','array',9),
(267,2,0,'profile_social','_MI_PROFILE_PROFILE_SOCIAL','0','_MI_PROFILE_PROFILE_SOCIAL_DESC','yesno','int',0),
(268,2,0,'profile_search','_MI_PROFILE_PROFILE_SEARCH','0','_MI_PROFILE_PROFILE_SEARCH_DSC','yesno','int',1),
(269,2,0,'show_empty','_MI_PROFILE_SHOWEMPTY','0','_MI_PROFILE_SHOWEMPTY_DESC','yesno','int',2),
(270,2,0,'index_real_name','_MI_PROFILE_DISPNAME','nick','_MI_PROFILE_DISPNAME_DESC','select','text',3),
(271,2,0,'view_group_2','_MI_PROFILE_GROUP_VIEW_2','a:1:{i:0;s:1:\"2\";}','_MI_PROFILE_GROUP_VIEW_DSC','group_multi','array',4),
(272,2,0,'view_group_3','_MI_PROFILE_GROUP_VIEW_3','a:1:{i:0;s:1:\"2\";}','_MI_PROFILE_GROUP_VIEW_DSC','group_multi','array',5),
(273,2,0,'rowitems','_MI_PROFILE_ROWITEMS_TITLE','5','_MI_PROFILE_ROWITEMS_DESC','textbox','int',6),
(274,4,0,'notification_enabled','_NOT_CONFIG_ENABLE','3','_NOT_CONFIG_ENABLEDSC','select','int',25),
(275,4,0,'notification_events','_NOT_CONFIG_EVENTS','a:3:{i:0;s:14:\"form-new_entry\";i:1;s:17:\"form-update_entry\";i:2;s:17:\"form-delete_entry\";}','_NOT_CONFIG_EVENTSDSC','select_multi','array',26),
(276,2,0,'thumb_width','_MI_PROFILE_THUMW_TITLE','125','_MI_PROFILE_THUMBW_DESC','textbox','int',9),
(277,2,0,'thumb_height','_MI_PROFILE_THUMBH_TITLE','175','_MI_PROFILE_THUMBH_DESC','textbox','int',10),
(278,2,0,'resized_width','_MI_PROFILE_RESIZEDW_TITLE','650','_MI_PROFILE_RESIZEDW_DESC','textbox','int',11),
(279,2,0,'resized_height','_MI_PROFILE_RESIZEDH_TITLE','450','_MI_PROFILE_RESIZEDH_DESC','textbox','int',12),
(280,2,0,'max_original_width','_MI_PROFILE_ORIGINALW_TITLE','2048','_MI_PROFILE_ORIGINALW_DESC','textbox','int',13),
(281,2,0,'max_original_height','_MI_PROFILE_ORIGINALH_TITLE','1600','_MI_PROFILE_ORIGINALH_DESC','textbox','int',14),
(282,2,0,'maxfilesize_picture','_MI_PROFILE_MAXFILEBYTES_PICTURE_TITLE','512000','_MI_PROFILE_MAXFILEBYTES_PICTURE_DESC','textbox','int',15),
(283,2,0,'picturesperpage','_MI_PROFILE_PICTURESPERPAGE_TITLE','6','_MI_PROFILE_PICTURESPERPAGE_DESC','textbox','int',16),
(284,2,0,'physical_delete','_MI_PROFILE_DELETEPHYSICAL_TITLE','1','_MI_PROFILE_DELETEPHYSICAL_DESC','yesno','int',17),
(285,2,0,'images_order','_MI_PROFILE_IMGORDER_TITLE','1','_MI_PROFILE_IMGORDER_DESC','yesno','int',18),
(286,2,0,'enable_friendship','_MI_PROFILE_ENABLEFRIENDS_TITLE','0','_MI_PROFILE_ENABLEFRIENDS_DESC','yesno','int',19),
(287,2,0,'enable_audio','_MI_PROFILE_ENABLEAUDIO_TITLE','0','_MI_PROFILE_ENABLEAUDIO_DESC','yesno','int',20),
(288,2,0,'nb_audio','_MI_PROFILE_NUMBAUDIO_TITLE','12','_MI_PROFILE_NUMBAUDIO_DESC','textbox','int',21),
(289,2,0,'audiosperpage','_MI_PROFILE_AUDIOSPERPAGE_TITLE','20','_MI_PROFILE_AUDIOSPERPAGE_DESC','textbox','int',22),
(290,2,0,'maxfilesize_audio','_MI_PROFILE_MAXFILEBYTES_AUDIO_TITLE','5242880','_MI_PROFILE_MAXFILEBYTES_AUDIO_DESC','textbox','int',23),
(291,2,0,'enable_videos','_MI_PROFILE_ENABLEVIDEOS_TITLE','0','_MI_PROFILE_ENABLEVIDEOS_DESC','yesno','int',24),
(292,2,0,'videosperpage','_MI_PROFILE_VIDEOSPERPAGE_TITLE','6','_MI_PROFILE_VIDEOSPERPAGE_DESC','textbox','int',25),
(293,2,0,'width_tube','_MI_PROFILE_TUBEW_TITLE','450','_MI_PROFILE_TUBEW_DESC','textbox','int',26),
(294,2,0,'height_tube','_MI_PROFILE_TUBEH_TITLE','350','_MI_PROFILE_TUBEH_DESC','textbox','int',27),
(295,2,0,'width_maintube','_MI_PROFILE_MAINTUBEW_TITLE','250','_MI_PROFILE_MAINTUBEW_DESC','textbox','int',28),
(296,2,0,'height_maintube','_MI_PROFILE_MAINTUBEH_TITLE','210','_MI_PROFILE_MAINTUBEH_DESC','textbox','int',29),
(297,2,0,'enable_tribes','_MI_PROFILE_ENABLETRIBES_TITLE','0','_MI_PROFILE_ENABLETRIBES_DESC','yesno','int',30),
(298,2,0,'tribetopicsperpage','_MI_PROFILE_TRIBETOPICSPERPAGE_TITLE','10','_MI_PROFILE_TRIBETOPICSPERPAGE_DESC','textbox','int',31),
(299,2,0,'tribepostsperpage','_MI_PROFILE_TRIBEPOSTSPERPAGE_TITLE','10','_MI_PROFILE_TRIBEPOSTSPERPAGE_DESC','textbox','int',32),
(300,2,0,'com_rule','_CM_COMRULES','1','','select','int',33),
(301,2,0,'com_anonpost','_CM_COMANONPOST','0','','yesno','int',34),
(302,2,0,'enable_pictures','_MI_PROFILE_ENABLEPICT_TITLE','0','_MI_PROFILE_ENABLEPICT_DESC','yesno','int',7),
(303,2,0,'notification_events','_NOT_CONFIG_EVENTS','a:11:{i:0;s:20:\"pictures-new_picture\";i:1;s:16:\"pictures-comment\";i:2;s:23:\"pictures-comment_submit\";i:3;s:16:\"videos-new_video\";i:4;s:14:\"videos-comment\";i:5;s:21:\"videos-comment_submit\";i:6;s:15:\"audio-new_audio\";i:7;s:13:\"audio-comment\";i:8;s:20:\"audio-comment_submit\";i:9;s:25:\"tribetopic-new_tribetopic\";i:10;s:23:\"tribepost-new_tribepost\";}','_NOT_CONFIG_EVENTSDSC','select_multi','array',36),
(304,2,0,'global_disabled','_MI_PROTECTOR_GLOBAL_DISBL','0','_MI_PROTECTOR_GLOBAL_DISBLDSC','yesno','int',0),
(305,2,0,'default_lang','_MI_PROTECTOR_DEFAULT_LANG','english','_MI_PROTECTOR_DEFAULT_LANGDSC','text','text',1),
(306,2,0,'log_level','_MI_PROTECTOR_LOG_LEVEL','255','','select','int',2),
(307,2,0,'banip_time0','_MI_PROTECTOR_BANIP_TIME0','86400','','text','int',3),
(308,2,0,'reliable_ips','_MI_PROTECTOR_RELIABLE_IPS','a:2:{i:0;s:9:\"^192.168.\";i:1;s:9:\"127.0.0.1\";}','_MI_PROTECTOR_RELIABLE_IPSDSC','textarea','array',4),
(309,2,0,'session_fixed_topbit','_MI_PROTECTOR_HIJACK_TOPBIT','24','_MI_PROTECTOR_HIJACK_TOPBITDSC','text','int',5),
(310,2,0,'groups_denyipmove','_MI_PROTECTOR_HIJACK_DENYGP','a:1:{i:0;s:1:\"1\";}','_MI_PROTECTOR_HIJACK_DENYGPDSC','group_multi','array',6),
(311,2,0,'notification_enabled','_NOT_CONFIG_ENABLE','3','_NOT_CONFIG_ENABLEDSC','select','int',35),
(312,2,0,'die_badext','_MI_PROTECTOR_DIE_BADEXT','1','_MI_PROTECTOR_DIE_BADEXTDSC','yesno','int',8),
(313,2,0,'contami_action','_MI_PROTECTOR_CONTAMI_ACTION','3','_MI_PROTECTOR_CONTAMI_ACTIONDS','select','int',9),
(314,2,0,'isocom_action','_MI_PROTECTOR_ISOCOM_ACTION','0','_MI_PROTECTOR_ISOCOM_ACTIONDSC','select','int',10),
(315,2,0,'union_action','_MI_PROTECTOR_UNION_ACTION','0','_MI_PROTECTOR_UNION_ACTIONDSC','select','int',11),
(316,2,0,'id_forceintval','_MI_PROTECTOR_ID_INTVAL','0','_MI_PROTECTOR_ID_INTVALDSC','yesno','int',12),
(317,2,0,'file_dotdot','_MI_PROTECTOR_FILE_DOTDOT','1','_MI_PROTECTOR_FILE_DOTDOTDSC','yesno','int',13),
(318,2,0,'bf_count','_MI_PROTECTOR_BF_COUNT','10','_MI_PROTECTOR_BF_COUNTDSC','text','int',14),
(319,2,0,'bwlimit_count','_MI_PROTECTOR_BWLIMIT_COUNT','0','_MI_PROTECTOR_BWLIMIT_COUNTDSC','text','int',15),
(320,2,0,'dos_skipmodules','_MI_PROTECTOR_DOS_SKIPMODS','','_MI_PROTECTOR_DOS_SKIPMODSDSC','text','text',16),
(321,2,0,'dos_expire','_MI_PROTECTOR_DOS_EXPIRE','60','_MI_PROTECTOR_DOS_EXPIREDSC','text','int',17),
(322,2,0,'dos_f5count','_MI_PROTECTOR_DOS_F5COUNT','20','_MI_PROTECTOR_DOS_F5COUNTDSC','text','int',18),
(323,2,0,'dos_f5action','_MI_PROTECTOR_DOS_F5ACTION','exit','','select','text',19),
(324,2,0,'dos_crcount','_MI_PROTECTOR_DOS_CRCOUNT','40','_MI_PROTECTOR_DOS_CRCOUNTDSC','text','int',20),
(325,2,0,'dos_craction','_MI_PROTECTOR_DOS_CRACTION','exit','','select','text',21),
(326,2,0,'dos_crsafe','_MI_PROTECTOR_DOS_CRSAFE','/(msnbot|Googlebot|Yahoo! Slurp)/i','_MI_PROTECTOR_DOS_CRSAFEDSC','text','text',22),
(327,2,0,'bip_except','_MI_PROTECTOR_BIP_EXCEPT','a:1:{i:0;s:1:\"1\";}','_MI_PROTECTOR_BIP_EXCEPTDSC','group_multi','array',23),
(328,2,0,'disable_features','_MI_PROTECTOR_DISABLES','1','','select','int',24),
(329,2,0,'enable_dblayertrap','_MI_PROTECTOR_DBLAYERTRAP','1','_MI_PROTECTOR_DBLAYERTRAPDSC','yesno','int',25),
(330,2,0,'dblayertrap_wo_server','_MI_PROTECTOR_DBTRAPWOSRV','0','_MI_PROTECTOR_DBTRAPWOSRVDSC','yesno','int',26),
(331,2,0,'enable_bigumbrella','_MI_PROTECTOR_BIGUMBRELLA','1','_MI_PROTECTOR_BIGUMBRELLADSC','yesno','int',27),
(332,2,0,'spamcount_uri4user','_MI_PROTECTOR_SPAMURI4U','0','_MI_PROTECTOR_SPAMURI4UDSC','textbox','int',28),
(333,2,0,'spamcount_uri4guest','_MI_PROTECTOR_SPAMURI4G','5','_MI_PROTECTOR_SPAMURI4GDSC','textbox','int',29),
(334,2,0,'filters','_MI_PROTECTOR_FILTERS','','_MI_PROTECTOR_FILTERSDSC','textarea','text',30),
(335,2,0,'enable_manip_check','_MI_PROTECTOR_MANIPUCHECK','1','_MI_PROTECTOR_MANIPUCHECKDSC','yesno','int',31),
(336,2,0,'manip_value','_MI_PROTECTOR_MANIPUVALUE','','_MI_PROTECTOR_MANIPUVALUEDSC','textbox','text',32),
(337,0,7,'auth_2fa','_MD_AM_AUTH2FA','1','_MD_AM_AUTH2FADESC','yesno','int',1),
(338,0,7,'auth_2fa_groups','_MD_AM_AUTH2FAGROUPS','','_MD_AM_AUTH2FAGROUPSDESC','group_multi','array',1),
(339,0,7,'auth_okta','_MD_AM_AUTHOKTA','','_MD_AM_AUTHOKTADESC','textbox','text',1),
(340,4,0,'t_width','_MI_formulize_TEXT_WIDTH','30','','textbox','int',0),
(341,4,0,'t_max','_MI_formulize_TEXT_MAX','255','','textbox','int',1),
(342,4,0,'ta_rows','_MI_formulize_TAREA_ROWS','5','','textbox','int',2),
(343,4,0,'ta_cols','_MI_formulize_TAREA_COLS','35','','textbox','int',3),
(344,4,0,'delimeter','_MI_formulize_DELIMETER','br','','select','text',4),
(345,4,0,'profileForm','_MI_formulize_PROFILEFORM','0','','select','int',5),
(346,4,0,'all_done_singles','_MI_formulize_ALL_DONE_SINGLES','1','_MI_formulize_SINGLESDESC','yesno','int',6),
(347,4,0,'LOE_limit','_MI_formulize_LOE_limit','5000','_MI_formulize_LOE_limit_DESC','textbox','int',7),
(348,4,0,'useToken','_MI_formulize_USETOKEN','1','_MI_formulize_USETOKENDESC','yesno','int',8),
(349,4,0,'isSaveLocked','_MI_formulize_ISSAVELOCKED','0','_MI_formulize_ISSAVELOCKEDDESC','yesno','int',9),
(350,4,0,'number_decimals','_MI_formulize_NUMBER_DECIMALS','0','_MI_formulize_NUMBER_DECIMALS_DESC','textbox','int',10),
(351,4,0,'number_prefix','_MI_formulize_NUMBER_PREFIX','','_MI_formulize_NUMBER_PREFIX_DESC','textbox','text',11),
(352,4,0,'number_suffix','_MI_formulize_NUMBER_SUFFIX','','_MI_formulize_NUMBER_SUFFIX_DESC','textbox','text',12),
(353,4,0,'number_decimalsep','_MI_formulize_NUMBER_DECIMALSEP','.','','textbox','text',13),
(354,4,0,'number_sep','_MI_formulize_NUMBER_SEP',',','','textbox','text',14),
(355,4,0,'heading_help_link','_MI_formulize_HEADING_HELP_LINK','1','_MI_formulize_HEADING_HELP_LINK_DESC','yesno','int',15),
(356,4,0,'useCache','_MI_formulize_USECACHE','1','_MI_formulize_USECACHEDESC','yesno','int',16),
(357,4,0,'downloadDefaultToExcel','_MI_formulize_DOWNLOADDEFAULT','0','_MI_formulize_DOWNLOADDEFAULT_DESC','yesno','int',17),
(358,4,0,'logProcedure','_MI_formulize_LOGPROCEDURE','0','_MI_formulize_LOGPROCEDUREDESC','yesno','int',18),
(359,4,0,'printviewStylesheets','_MI_formulize_PRINTVIEWSTYLESHEETS','','_MI_formulize_PRINTVIEWSTYLESHEETSDESC','textbox','text',19),
(360,4,0,'debugDerivedValues','_MI_formulize_DEBUGDERIVEDVALUES','0','_MI_formulize_DEBUGDERIVEDVALUESDESC','yesno','int',20),
(361,4,0,'customScope','_MI_formulize_CUSTOMSCOPE','','_MI_formulize_CUSTOMSCOPEDESC','textarea','text',21),
(362,4,0,'exportIntroChar','_MI_formulize_EXPORTINTROCHAR','1','_MI_formulize_EXPORTINTROCHARDESC','select','int',22),
(363,4,0,'notifyByCron','_MI_formulize_NOTIFYBYCRON','0','_MI_formulize_NOTIFYBYCRONDESC','yesno','int',23),
(364,4,0,'f7MenuTemplate','_MI_formulize_F7MENUTEMPLATE','1','_MI_formulize_F7MENUTEMPLATEDESC','yesno','int',24),
(365,2,0,'san_nullbyte','_MI_PROTECTOR_SAN_NULLBYTE','1','_MI_PROTECTOR_SAN_NULLBYTEDSC','yesno','int',7),
(366,2,0,'nb_pict','_MI_PROFILE_NUMBPICT_TITLE','12','_MI_PROFILE_NUMBPICT_DESC','textbox','int',8),
(367,0,7,'auth_googleonly','_MD_AM_GOOGLEONLY','0','_MD_AM_GOOGLEONLYDSC','yesno','int',1);
/*!40000 ALTER TABLE `ai8k7Bba_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_configcategory`
--

DROP TABLE IF EXISTS `ai8k7Bba_configcategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_configcategory` (
  `confcat_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `confcat_name` varchar(255) NOT NULL DEFAULT '',
  `confcat_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`confcat_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_configcategory`
--

LOCK TABLES `ai8k7Bba_configcategory` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_configcategory` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_configcategory` VALUES
(1,'_MD_AM_GENERAL',0),
(2,'_MD_AM_USERSETTINGS',0),
(3,'_MD_AM_METAFOOTER',0),
(4,'_MD_AM_CENSOR',0),
(5,'_MD_AM_SEARCH',0),
(6,'_MD_AM_MAILER',0),
(7,'_MD_AM_AUTHENTICATION',0),
(8,'_MD_AM_MULTILANGUAGE',0),
(10,'_MD_AM_PERSON',0),
(11,'_MD_AM_CAPTCHA',0),
(12,'_MD_AM_PLUGINS',0),
(13,'_MD_AM_AUTOTASKS',0),
(14,'_MD_AM_PURIFIER',0);
/*!40000 ALTER TABLE `ai8k7Bba_configcategory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_configoption`
--

DROP TABLE IF EXISTS `ai8k7Bba_configoption`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_configoption` (
  `confop_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `confop_name` varchar(255) NOT NULL DEFAULT '',
  `confop_value` varchar(255) NOT NULL DEFAULT '',
  `conf_id` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`confop_id`),
  KEY `conf_id` (`conf_id`)
) ENGINE=InnoDB AUTO_INCREMENT=163 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_configoption`
--

LOCK TABLES `ai8k7Bba_configoption` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_configoption` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_configoption` VALUES
(1,'_MD_AM_DEBUGMODE0','0',23),
(2,'_MD_AM_DEBUGMODE1','1',23),
(3,'_MD_AM_DEBUGMODE2','2',23),
(4,'_MD_AM_DEBUGMODE3','3',23),
(5,'_NESTED','nest',32),
(6,'_FLAT','flat',32),
(7,'_THREADED','thread',32),
(8,'_OLDESTFIRST','0',33),
(9,'_NEWESTFIRST','1',33),
(10,'_MD_AM_PASSLEVEL1','20',40),
(11,'_MD_AM_PASSLEVEL2','40',40),
(12,'_MD_AM_PASSLEVEL3','60',40),
(13,'_MD_AM_PASSLEVEL4','80',40),
(14,'_MD_AM_PASSLEVEL5','95',40),
(15,'_MD_AM_USERACTV','0',54),
(16,'_MD_AM_AUTOACTV','1',54),
(17,'_MD_AM_ADMINACTV','2',54),
(18,'_MD_AM_REGINVITE','3',54),
(19,'_MD_AM_STRICT','0',56),
(20,'_MD_AM_MEDIUM','1',56),
(21,'_MD_AM_LIGHT','2',56),
(22,'_MD_AM_ENC_MD5','0',75),
(23,'_MD_AM_ENC_SHA256','1',75),
(24,'_MD_AM_ENC_SHA384','2',75),
(25,'_MD_AM_ENC_SHA512','3',75),
(26,'_MD_AM_ENC_RIPEMD128','4',75),
(27,'_MD_AM_ENC_RIPEMD160','5',75),
(28,'_MD_AM_ENC_WHIRLPOOL','6',75),
(29,'_MD_AM_ENC_HAVAL1284','7',75),
(30,'_MD_AM_ENC_HAVAL1604','8',75),
(31,'_MD_AM_ENC_HAVAL1924','9',75),
(32,'_MD_AM_ENC_HAVAL2244','10',75),
(33,'_MD_AM_ENC_HAVAL2564','11',75),
(34,'_MD_AM_ENC_HAVAL1285','12',75),
(35,'_MD_AM_ENC_HAVAL1605','13',75),
(36,'_MD_AM_ENC_HAVAL1925','14',75),
(37,'_MD_AM_ENC_HAVAL2245','15',75),
(38,'_MD_AM_ENC_HAVAL2565','16',75),
(39,'_MD_AM_INDEXFOLLOW','index,follow',78),
(40,'_MD_AM_NOINDEXFOLLOW','noindex,follow',78),
(41,'_MD_AM_INDEXNOFOLLOW','index,nofollow',78),
(42,'_MD_AM_NOINDEXNOFOLLOW','noindex,nofollow',78),
(43,'_MD_AM_METAOGEN','general',79),
(44,'_MD_AM_METAO14YRS','14 years',79),
(45,'_MD_AM_METAOREST','restricted',79),
(46,'_MD_AM_METAOMAT','mature',79),
(47,'PHP mail()','mail',100),
(48,'sendmail','sendmail',100),
(49,'SMTP','smtp',100),
(50,'SMTPAuth','smtpauth',100),
(51,'None','',104),
(52,'SSL','ssl',104),
(53,'TLS','tls',104),
(54,'_MD_AM_AUTH_CONFOPTION_XOOPS','xoops',107),
(55,'_MD_AM_AUTH_CONFOPTION_LDAP','ldap',107),
(56,'_MD_AM_AUTH_CONFOPTION_AD','ads',107),
(57,'_MD_AM_NOMAILPROTECT','0',144),
(58,'_MD_AM_GDMAILPROTECT','1',144),
(59,'_MD_AM_REMAILPROTECT','2',144),
(60,'_MD_AM_CAPTCHA_OFF','none',160),
(61,'_MD_AM_CAPTCHA_IMG','image',160),
(62,'_MD_AM_CAPTCHA_TXT','text',160),
(63,'_MD_AM_BAR','0',168),
(64,'_MD_AM_CIRCLE','1',168),
(65,'_MD_AM_LINE','2',168),
(66,'_MD_AM_RECTANGLE','3',168),
(67,'_MD_AM_ELLIPSE','4',168),
(68,'_MD_AM_POLYGON','5',168),
(69,'_MD_AM_RANDOM','100',168),
(70,'_MD_AM_HIGHLIGHTER_OFF','none',172),
(71,'_MD_AM_HIGHLIGHTER_PHP','php',172),
(72,'_MD_AM_HIGHLIGHTER_GESHI','geshi',172),
(73,'PHP-CGI','php -f %path%',175),
(74,'wget','wget %url%',175),
(75,'Lynx','lynx --dump %url%',175),
(76,'_MD_AM_PURIFIER_401T','HTML 4.01 Transitional',192),
(77,'_MD_AM_PURIFIER_401S','HTML 4.01 Strict',192),
(78,'_MD_AM_PURIFIER_X10T','XHTML 1.0 Transitional',192),
(79,'_MD_AM_PURIFIER_X10S','XHTML 1.0 Strict',192),
(80,'_MD_AM_PURIFIER_X11','XHTML 1.1',192),
(81,'_MD_AM_PURIFIER_NONE','none',193),
(82,'_MD_AM_PURIFIER_LIGHT','light',193),
(83,'_MD_AM_PURIFIER_MEDIUM','medium',193),
(84,'_MD_AM_PURIFIER_HEAVY','heavy',193),
(85,'Display Name','nick',242),
(86,'Real Name','real',242),
(87,'Both','both',242),
(88,'_CM_COMNOCOM','0',272),
(89,'_CM_COMAPPROVEALL','1',272),
(90,'_CM_COMAPPROVEUSER','2',272),
(91,'_CM_COMAPPROVEADMIN','3',272),
(92,'_NOT_CONFIG_DISABLE','0',274),
(93,'_NOT_CONFIG_ENABLEBLOCK','1',274),
(94,'_NOT_CONFIG_ENABLEINLINE','2',274),
(95,'_NOT_CONFIG_ENABLEBOTH','3',274),
(96,'Pictures : New picture','pictures-new_picture',275),
(97,'Pictures : Comment Added','pictures-comment',275),
(98,'Pictures : Comment Submitted','pictures-comment_submit',275),
(99,'Videos : New video','videos-new_video',275),
(100,'Videos : Comment Added','videos-comment',275),
(101,'Videos : Comment Submitted','videos-comment_submit',275),
(102,'Audio : New audio','audio-new_audio',275),
(103,'Audio : Comment Added','audio-comment',275),
(104,'Audio : Comment Submitted','audio-comment_submit',275),
(105,'Groups : New topic','tribetopic-new_tribetopic',275),
(106,'Groups : New post','tribepost-new_tribepost',275),
(107,'_CM_COMNOCOM','0',282),
(108,'_CM_COMAPPROVEALL','1',282),
(109,'_CM_COMAPPROVEUSER','2',282),
(110,'_CM_COMAPPROVEADMIN','3',282),
(111,'_NOT_CONFIG_DISABLE','0',284),
(112,'_NOT_CONFIG_ENABLEBLOCK','1',284),
(113,'_NOT_CONFIG_ENABLEINLINE','2',284),
(114,'_NOT_CONFIG_ENABLEBOTH','3',284),
(115,'All contents : New content published','global-content_published',285),
(116,'Line break','br',290),
(117,'White space','space',290),
(118,'-------------','0',291),
(119,'Prefix strings with a TAB character (for Excel), unless makecsv.php is generating the file, then use an apostrophe (for Google Sheets)','1',308),
(120,'Always prefix strings with an apostrophe (for Google Sheets)','2',308),
(121,'Always prefix strings with a TAB (for Excel)','3',308),
(122,'Never prefix strings (for programs that need clean, raw data)','4',308),
(123,'_NOT_CONFIG_DISABLE','0',311),
(124,'_NOT_CONFIG_ENABLEBLOCK','1',311),
(125,'_NOT_CONFIG_ENABLEINLINE','2',311),
(126,'_NOT_CONFIG_ENABLEBOTH','3',311),
(127,'Form Notifications : New Entry in a Form','form-new_entry',312),
(128,'Form Notifications : Updated Entry in a Form','form-update_entry',312),
(129,'Form Notifications : Entry deleted from a Form','form-delete_entry',312),
(130,'_MI_PROTECTOR_LOGLEVEL0','0',315),
(131,'_MI_PROTECTOR_LOGLEVEL15','15',315),
(132,'_MI_PROTECTOR_LOGLEVEL63','63',315),
(133,'_MI_PROTECTOR_LOGLEVEL255','255',315),
(134,'_MI_PROTECTOR_OPT_NONE','0',322),
(135,'_MI_PROTECTOR_OPT_EXIT','3',322),
(136,'_MI_PROTECTOR_OPT_BIPTIME0','7',322),
(137,'_MI_PROTECTOR_OPT_BIP','15',322),
(138,'_MI_PROTECTOR_OPT_NONE','0',323),
(139,'_MI_PROTECTOR_OPT_SAN','1',323),
(140,'_MI_PROTECTOR_OPT_EXIT','3',323),
(141,'_MI_PROTECTOR_OPT_BIPTIME0','7',323),
(142,'_MI_PROTECTOR_OPT_BIP','15',323),
(143,'_MI_PROTECTOR_OPT_NONE','0',324),
(144,'_MI_PROTECTOR_OPT_SAN','1',324),
(145,'_MI_PROTECTOR_OPT_EXIT','3',324),
(146,'_MI_PROTECTOR_OPT_BIPTIME0','7',324),
(147,'_MI_PROTECTOR_OPT_BIP','15',324),
(148,'_MI_PROTECTOR_DOSOPT_NONE','none',332),
(149,'_MI_PROTECTOR_DOSOPT_SLEEP','sleep',332),
(150,'_MI_PROTECTOR_DOSOPT_EXIT','exit',332),
(151,'_MI_PROTECTOR_DOSOPT_BIPTIME0','biptime0',332),
(152,'_MI_PROTECTOR_DOSOPT_BIP','bip',332),
(153,'_MI_PROTECTOR_DOSOPT_HTA','hta',332),
(154,'_MI_PROTECTOR_DOSOPT_NONE','none',334),
(155,'_MI_PROTECTOR_DOSOPT_SLEEP','sleep',334),
(156,'_MI_PROTECTOR_DOSOPT_EXIT','exit',334),
(157,'_MI_PROTECTOR_DOSOPT_BIPTIME0','biptime0',334),
(158,'_MI_PROTECTOR_DOSOPT_BIP','bip',334),
(159,'_MI_PROTECTOR_DOSOPT_HTA','hta',334),
(160,'xmlrpc','1',337),
(161,'xmlrpc + 2.0.9.2 bugs','1025',337),
(162,'_NONE','0',337);
/*!40000 ALTER TABLE `ai8k7Bba_configoption` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_content_content`
--

DROP TABLE IF EXISTS `ai8k7Bba_content_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_content_content` (
  `content_id` int(11) NOT NULL AUTO_INCREMENT,
  `content_pid` int(11) NOT NULL DEFAULT 0,
  `content_uid` int(11) NOT NULL DEFAULT 1,
  `content_title` varchar(255) NOT NULL DEFAULT '',
  `content_body` text NOT NULL,
  `content_css` text NOT NULL,
  `content_tags` text NOT NULL,
  `content_visibility` int(11) NOT NULL DEFAULT 3,
  `content_published_date` int(11) NOT NULL DEFAULT 0,
  `content_updated_date` int(11) NOT NULL DEFAULT 0,
  `content_weight` int(11) NOT NULL DEFAULT 0,
  `content_status` int(11) NOT NULL DEFAULT 1,
  `content_makesymlink` int(11) NOT NULL DEFAULT 0,
  `content_showsubs` int(11) NOT NULL DEFAULT 0,
  `content_cancomment` int(11) NOT NULL,
  `content_comments` int(11) NOT NULL DEFAULT 0,
  `content_notification_sent` int(11) NOT NULL DEFAULT 0,
  `counter` int(11) NOT NULL DEFAULT 0,
  `dohtml` int(11) NOT NULL,
  `dobr` int(11) NOT NULL,
  `doimage` int(11) NOT NULL,
  `dosmiley` int(11) NOT NULL,
  `doxcode` int(11) NOT NULL,
  `meta_keywords` text NOT NULL,
  `meta_description` text NOT NULL,
  `short_url` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_content_content`
--

LOCK TABLES `ai8k7Bba_content_content` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_content_content` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_content_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize` (
  `id_form` int(5) NOT NULL DEFAULT 0,
  `ele_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `ele_type` varchar(100) NOT NULL DEFAULT '',
  `ele_caption` text NOT NULL,
  `ele_desc` text DEFAULT NULL,
  `ele_colhead` varchar(255) DEFAULT '',
  `ele_handle` varchar(255) NOT NULL DEFAULT '',
  `ele_order` smallint(2) NOT NULL DEFAULT 0,
  `ele_req` tinyint(1) NOT NULL DEFAULT 1,
  `ele_encrypt` tinyint(1) NOT NULL DEFAULT 0,
  `ele_value` text NOT NULL,
  `ele_uitext` text NOT NULL,
  `ele_uitextshow` tinyint(1) NOT NULL DEFAULT 0,
  `ele_delim` varchar(255) NOT NULL DEFAULT '',
  `ele_display` text NOT NULL,
  `ele_disabled` text NOT NULL,
  `ele_filtersettings` text NOT NULL,
  `ele_forcehidden` tinyint(1) NOT NULL DEFAULT 0,
  `ele_private` tinyint(1) NOT NULL DEFAULT 0,
  `ele_use_default_when_blank` tinyint(1) NOT NULL DEFAULT 0,
  `ele_exportoptions` text NOT NULL,
  `ele_sort` smallint(2) DEFAULT NULL,
  PRIMARY KEY (`ele_id`),
  KEY `ele_order` (`ele_order`),
  KEY `ele_display` (`ele_display`(255))
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize`
--

LOCK TABLES `ai8k7Bba_formulize` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_formulize` VALUES
(1,1,'text','Topic Name','','','topics_topic_name',1,0,0,'a:10:{i:0;s:2:\"30\";i:1;s:3:\"255\";i:2;s:0:\"\";i:11;s:1:\"0\";i:3;s:1:\"0\";i:5;s:1:\"0\";i:6;s:0:\"\";i:10;s:0:\"\";i:7;s:1:\".\";i:8;s:1:\",\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(1,2,'textarea','Outline','','','topics_outline',2,0,0,'a:3:{i:1;s:1:\"5\";i:2;s:2:\"35\";i:0;s:0:\"\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(1,3,'textarea','Details','','','topics_details',3,0,0,'a:3:{i:1;s:1:\"5\";i:2;s:2:\"35\";i:0;s:0:\"\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(2,4,'select','Topic','Optional','','publications_topic',4,0,0,'a:18:{i:0;i:1;i:16;s:1:\"0\";s:8:\"snapshot\";s:1:\"0\";i:4;s:1:\"0\";i:6;s:1:\"0\";s:21:\"optionsLimitByElement\";s:4:\"none\";i:12;s:4:\"none\";i:15;s:1:\"1\";i:7;s:1:\"0\";i:9;s:1:\"0\";i:14;s:1:\"0\";i:2;s:23:\"1#*=:*topics_topic_name\";s:20:\"linkedSourceMappings\";N;i:8;i:0;i:1;i:0;i:3;s:3:\"all\";i:5;s:0:\"\";s:27:\"optionsLimitByElementFilter\";s:0:\"\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(2,5,'text','Title','','','publications_title',1,1,0,'a:10:{i:0;s:2:\"30\";i:1;s:3:\"255\";i:2;s:0:\"\";i:11;s:1:\"0\";i:3;s:1:\"0\";i:5;s:1:\"0\";i:6;s:0:\"\";i:10;s:0:\"\";i:7;s:1:\".\";i:8;s:1:\",\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(2,6,'fileUpload','File','','','publications_file',6,0,0,'a:3:{i:1;s:67:\"doc,docx,xls,xlsx,ppt,pptx,csv,txt,pdf,jpg,jpeg,gif,png,odt,ods,odp\";i:2;s:1:\"0\";i:0;s:2:\"10\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(2,7,'text','URL','','','publications_url',7,0,0,'a:10:{i:0;s:2:\"30\";i:1;s:3:\"255\";i:2;s:0:\"\";i:11;s:1:\"0\";i:3;s:1:\"0\";i:5;s:1:\"0\";i:6;s:0:\"\";i:10;s:0:\"\";i:7;s:1:\".\";i:8;s:1:\",\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(1,8,'subform','Publications','','','topics_publications',4,0,0,'a:21:{i:0;s:1:\"2\";i:8;s:3:\"row\";i:6;s:7:\"subform\";s:14:\"addButtonLimit\";s:0:\"\";s:21:\"simple_add_one_button\";s:1:\"1\";s:26:\"simple_add_one_button_text\";s:15:\"Add Publication\";i:9;s:7:\"entries\";s:18:\"show_delete_button\";s:1:\"1\";i:4;s:1:\"0\";i:3;s:1:\"2\";s:14:\"display_screen\";s:2:\"11\";s:14:\"SortingElement\";s:1:\"5\";s:19:\"UserFilterByElement\";s:1:\"0\";i:5;s:1:\"0\";s:17:\"show_clone_button\";i:0;s:20:\"enforceFilterChanges\";i:0;i:2;i:0;s:22:\"subform_prepop_element\";i:0;i:1;s:11:\"5,25,26,6,7\";s:16:\"disabledelements\";s:11:\"5,25,26,6,7\";i:7;s:0:\"\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'',0),
(3,9,'select','Topic','','','activities_topic',1,0,0,'a:18:{i:0;i:1;i:16;s:1:\"0\";s:8:\"snapshot\";s:1:\"0\";i:4;s:1:\"0\";i:6;s:1:\"0\";s:21:\"optionsLimitByElement\";s:4:\"none\";i:12;s:4:\"none\";i:15;s:1:\"1\";i:7;s:1:\"0\";i:9;s:1:\"0\";i:14;s:1:\"0\";i:2;s:23:\"1#*=:*topics_topic_name\";s:20:\"linkedSourceMappings\";N;i:8;i:0;i:1;i:0;i:3;s:3:\"all\";i:5;s:0:\"\";s:27:\"optionsLimitByElementFilter\";s:0:\"\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(3,10,'radio','Activity Type','','','activities_type',3,0,0,'a:7:{s:10:\"Conference\";i:0;s:8:\"Workshop\";i:0;s:7:\"Lecture\";i:0;s:11:\"Book Launch\";i:0;s:10:\"Roundtable\";i:0;s:5:\"Panel\";i:0;s:10:\"{OTHER|30}\";i:0;}','a:0:{}',0,'br','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(3,11,'text','Activity Name','','','activities_activity_name',2,0,0,'a:10:{i:0;s:2:\"30\";i:1;s:3:\"255\";i:2;s:0:\"\";i:11;s:1:\"0\";i:3;s:1:\"0\";i:5;s:1:\"0\";i:6;s:0:\"\";i:10;s:0:\"\";i:7;s:1:\".\";i:8;s:1:\",\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(3,12,'textarea','Activity Details','Agenda, Sessions, etc','','activities_details',8,0,0,'a:3:{i:1;s:1:\"5\";i:2;s:2:\"35\";i:0;s:0:\"\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(2,13,'select','Activity','Optional','','publications_activity',5,0,0,'a:18:{i:0;i:1;i:16;s:1:\"0\";s:8:\"snapshot\";s:1:\"0\";i:4;s:1:\"0\";i:6;s:1:\"0\";s:21:\"optionsLimitByElement\";s:4:\"none\";i:12;s:4:\"none\";i:15;s:1:\"1\";i:7;s:1:\"0\";i:9;s:1:\"0\";i:14;s:1:\"0\";i:2;s:30:\"3#*=:*activities_activity_name\";s:20:\"linkedSourceMappings\";N;i:8;i:0;i:1;i:0;i:3;s:3:\"all\";i:5;s:0:\"\";s:27:\"optionsLimitByElementFilter\";s:0:\"\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(3,14,'subform','Publications','','','activities_publications',9,0,0,'a:21:{i:0;s:1:\"2\";i:8;s:3:\"row\";i:6;s:7:\"subform\";s:14:\"addButtonLimit\";s:0:\"\";s:21:\"simple_add_one_button\";s:1:\"1\";s:26:\"simple_add_one_button_text\";s:15:\"Add Publication\";i:9;s:7:\"entries\";s:18:\"show_delete_button\";s:1:\"1\";i:4;s:1:\"0\";i:3;s:1:\"1\";s:14:\"display_screen\";s:2:\"13\";s:14:\"SortingElement\";s:1:\"5\";s:19:\"UserFilterByElement\";s:1:\"0\";i:5;s:1:\"0\";s:17:\"show_clone_button\";i:0;s:20:\"enforceFilterChanges\";i:0;i:2;i:0;s:22:\"subform_prepop_element\";i:0;i:1;s:11:\"5,25,26,6,7\";s:16:\"disabledelements\";s:11:\"5,25,26,6,7\";i:7;s:0:\"\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'',0),
(4,15,'text','First Name','','','people_first_name',1,1,0,'a:10:{i:0;s:2:\"30\";i:1;s:3:\"255\";i:2;s:0:\"\";i:11;s:1:\"0\";i:3;s:1:\"0\";i:5;s:1:\"0\";i:6;s:0:\"\";i:10;s:0:\"\";i:7;s:1:\".\";i:8;s:1:\",\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(4,17,'textarea','Person Notes','','','people_person_notes',6,0,0,'a:3:{i:1;s:1:\"5\";i:2;s:2:\"35\";i:0;s:0:\"\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(3,19,'subform','People','','','activities_people',10,0,0,'a:21:{i:0;s:1:\"4\";i:8;s:3:\"row\";i:6;s:7:\"subform\";s:14:\"addButtonLimit\";s:0:\"\";s:21:\"simple_add_one_button\";s:1:\"1\";s:26:\"simple_add_one_button_text\";s:12:\"Add a Person\";i:9;s:7:\"entries\";s:18:\"show_delete_button\";s:1:\"1\";i:4;s:1:\"0\";i:3;s:1:\"2\";s:14:\"display_screen\";s:2:\"14\";s:14:\"SortingElement\";s:1:\"0\";s:19:\"UserFilterByElement\";s:1:\"0\";i:5;s:1:\"0\";s:17:\"show_clone_button\";i:0;s:20:\"enforceFilterChanges\";i:0;i:2;i:0;s:22:\"subform_prepop_element\";i:0;i:1;s:11:\"32,28,29,30\";s:16:\"disabledelements\";s:11:\"32,28,29,30\";i:7;s:0:\"\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'',0),
(5,20,'select','Activity','','','people_in_activities_activity',1,0,0,'a:17:{i:0;i:1;i:16;s:1:\"0\";s:8:\"snapshot\";s:1:\"0\";i:4;s:1:\"0\";i:6;s:1:\"0\";s:21:\"optionsLimitByElement\";s:4:\"none\";i:15;s:1:\"1\";i:7;s:1:\"0\";i:9;s:1:\"0\";i:14;s:1:\"0\";i:2;s:30:\"3#*=:*activities_activity_name\";s:20:\"linkedSourceMappings\";N;i:8;i:0;i:1;i:0;i:3;s:3:\"all\";i:5;s:0:\"\";s:27:\"optionsLimitByElementFilter\";s:0:\"\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(5,21,'select','Person','','','people_in_activities_person',2,0,0,'a:18:{i:0;i:1;i:16;s:1:\"1\";s:8:\"snapshot\";s:1:\"0\";i:4;s:1:\"0\";i:6;s:1:\"0\";s:21:\"optionsLimitByElement\";s:4:\"none\";i:12;s:4:\"none\";i:15;s:1:\"1\";i:7;s:1:\"0\";i:9;s:1:\"0\";i:14;s:1:\"0\";i:2;s:24:\"4#*=:*people_person_name\";s:20:\"linkedSourceMappings\";a:1:{i:0;a:2:{s:8:\"thisForm\";i:21;s:10:\"sourceForm\";i:32;}}i:8;i:1;i:1;s:1:\"0\";i:3;s:3:\"all\";i:5;s:0:\"\";s:27:\"optionsLimitByElementFilter\";s:0:\"\";}','a:0:{}',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(5,22,'select','Role','','','people_in_activities_role',3,0,0,'a:17:{i:0;i:1;i:16;s:1:\"0\";s:8:\"snapshot\";s:1:\"0\";i:4;s:1:\"0\";i:6;s:1:\"0\";s:21:\"optionsLimitByElement\";s:4:\"none\";i:15;s:1:\"1\";i:7;s:1:\"0\";i:9;s:1:\"0\";i:14;s:1:\"0\";i:2;a:7:{s:5:\"Staff\";i:0;s:9:\"Volunteer\";i:0;s:7:\"Host/MC\";i:0;s:9:\"Moderator\";i:0;s:18:\"Publication Author\";i:0;s:7:\"Speaker\";i:0;s:8:\"Attendee\";i:0;}s:20:\"linkedSourceMappings\";N;i:8;i:0;i:1;i:0;i:3;s:3:\"all\";i:5;s:0:\"\";s:27:\"optionsLimitByElementFilter\";s:0:\"\";}','a:0:{}',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(1,24,'subform','Activites','','','topics_activites',5,0,0,'a:21:{i:0;s:1:\"3\";i:8;s:3:\"row\";i:6;s:7:\"subform\";s:14:\"addButtonLimit\";s:0:\"\";s:21:\"simple_add_one_button\";s:1:\"1\";s:26:\"simple_add_one_button_text\";s:12:\"Add Activity\";i:9;s:7:\"entries\";s:18:\"show_delete_button\";s:1:\"1\";i:4;s:1:\"0\";i:3;s:1:\"1\";s:14:\"display_screen\";s:2:\"12\";s:14:\"SortingElement\";s:2:\"11\";s:19:\"UserFilterByElement\";s:1:\"0\";i:5;s:1:\"0\";s:17:\"show_clone_button\";i:0;s:20:\"enforceFilterChanges\";i:0;i:2;i:0;s:22:\"subform_prepop_element\";i:0;i:1;s:5:\"11,10\";s:16:\"disabledelements\";a:0:{}i:7;s:0:\"\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'',0),
(2,25,'radio','Type','','','publications_type',2,1,0,'a:4:{i:1;i:0;i:2;i:0;i:3;i:0;i:4;i:0;}','a:4:{i:1;s:4:\"Book\";i:2;s:7:\"Article\";i:3;s:14:\"Academic Paper\";i:4;s:3:\"URL\";}',1,'br','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(2,26,'select','Source','','','publications_source',3,0,0,'a:17:{i:0;i:1;i:16;s:1:\"1\";s:8:\"snapshot\";s:1:\"0\";i:4;s:1:\"0\";i:6;s:1:\"0\";s:21:\"optionsLimitByElement\";s:4:\"none\";i:15;s:1:\"1\";i:7;s:1:\"0\";i:9;s:1:\"0\";i:14;s:1:\"0\";i:2;a:1:{s:16:\"The Toronto Star\";i:0;}s:20:\"linkedSourceMappings\";N;i:8;i:1;i:1;s:1:\"0\";i:3;s:3:\"all\";i:5;s:0:\"\";s:27:\"optionsLimitByElementFilter\";s:0:\"\";}','a:0:{}',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(4,27,'text','Last Name','','','people_last_name',2,1,0,'a:10:{i:0;s:2:\"30\";i:1;s:3:\"255\";i:2;s:0:\"\";i:11;s:1:\"0\";i:3;s:1:\"0\";i:5;s:1:\"0\";i:6;s:0:\"\";i:10;s:0:\"\";i:7;s:1:\".\";i:8;s:1:\",\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(4,29,'email','Person Email','','','people_person_email',4,0,0,'','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(4,30,'phone','Person Phone','','','people_person_phone',5,0,0,'a:1:{s:6:\"format\";s:12:\"XXX-XXX-XXXX\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(4,31,'select','Activities','','','people_activities',7,0,0,'a:17:{i:0;i:1;i:16;s:1:\"0\";s:8:\"snapshot\";s:1:\"0\";i:4;s:1:\"0\";i:6;s:1:\"0\";s:21:\"optionsLimitByElement\";s:4:\"none\";i:15;s:1:\"1\";i:7;s:1:\"0\";i:9;s:1:\"0\";i:14;s:1:\"0\";i:2;s:30:\"3#*=:*activities_activity_name\";s:20:\"linkedSourceMappings\";N;i:8;i:1;i:1;s:1:\"1\";i:3;s:3:\"all\";i:5;s:0:\"\";s:27:\"optionsLimitByElementFilter\";s:0:\"\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(4,32,'derived','Person name','','','people_person_name',3,0,0,'a:6:{i:0;s:56:\"$value = \"people_first_name\" . \' \' . \"people_last_name\";\";i:1;s:1:\"0\";i:2;s:0:\"\";i:5;s:0:\"\";i:3;s:1:\".\";i:4;s:1:\",\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',27),
(6,33,'text','Title','','','pages_title',1,1,0,'a:10:{i:0;s:2:\"30\";i:1;s:3:\"255\";i:2;s:0:\"\";i:11;s:1:\"0\";i:3;s:1:\"0\";i:5;s:1:\"0\";i:6;s:0:\"\";i:10;s:0:\"\";i:7;s:1:\".\";i:8;s:1:\",\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(6,34,'textarea','Content','','','pages_content',2,0,0,'a:4:{i:1;s:1:\"5\";i:2;s:2:\"35\";i:0;s:0:\"\";s:13:\"use_rich_text\";s:1:\"1\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(3,37,'date','Activity Date','','','activities_activity_date',4,0,0,'a:3:{i:0;s:0:\"\";s:14:\"date_past_days\";s:0:\"\";s:16:\"date_future_days\";s:0:\"\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(3,38,'time','Activity Time','','','activities_activity_time',5,0,0,'','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0),
(3,40,'text','Activity Location','','','activities_activity_location',6,0,0,'a:10:{i:0;s:2:\"30\";i:1;s:3:\"255\";i:2;s:0:\"\";i:11;s:1:\"0\";i:3;s:1:\"0\";i:5;s:1:\"0\";i:6;s:0:\"\";i:10;s:0:\"\";i:7;s:1:\".\";i:8;s:1:\",\";}','',0,'','1','0','a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}',0,0,0,'a:0:{}',0);
/*!40000 ALTER TABLE `ai8k7Bba_formulize` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_activities`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_activities` (
  `entry_id` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `creation_datetime` datetime DEFAULT NULL,
  `mod_datetime` datetime DEFAULT NULL,
  `creation_uid` int(7) DEFAULT 0,
  `mod_uid` int(7) DEFAULT 0,
  `activities_topic` bigint(20) DEFAULT NULL,
  `activities_type` text DEFAULT NULL,
  `activities_activity_name` text DEFAULT NULL,
  `activities_details` text DEFAULT NULL,
  `activities_activity_date` date DEFAULT NULL,
  `activities_activity_time` time DEFAULT NULL,
  `activities_activity_location` text DEFAULT NULL,
  PRIMARY KEY (`entry_id`),
  KEY `i_creation_uid` (`creation_uid`),
  KEY `activities_topic` (`activities_topic`),
  FULLTEXT KEY `activities_activity_name` (`activities_activity_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_activities`
--

LOCK TABLES `ai8k7Bba_formulize_activities` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_activities` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_formulize_activities` VALUES
(1,'2024-01-25 08:21:45','2024-01-25 08:21:45',2,2,1,'Conference','Notwithstanding Clause at 40','January 25 2024 at the Sheraton....',NULL,NULL,NULL),
(2,'2024-01-25 08:25:24','2024-01-25 08:25:24',2,2,1,'Conference','The unwritten constitution','Etc',NULL,NULL,NULL),
(3,'2024-02-14 20:28:02','2024-02-14 20:28:02',2,2,1,'Panel','Elected Senate Finally',NULL,'2024-02-14','15:00:00','Toronto');
/*!40000 ALTER TABLE `ai8k7Bba_formulize_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_advanced_calculations`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_advanced_calculations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_advanced_calculations` (
  `acid` int(11) NOT NULL AUTO_INCREMENT,
  `fid` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `input` text NOT NULL,
  `output` text NOT NULL,
  `steps` text NOT NULL,
  `steptitles` text NOT NULL,
  `fltr_grps` text NOT NULL,
  `fltr_grptitles` text NOT NULL,
  PRIMARY KEY (`acid`),
  KEY `i_fid` (`fid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_advanced_calculations`
--

LOCK TABLES `ai8k7Bba_formulize_advanced_calculations` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_advanced_calculations` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_advanced_calculations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_apikeys`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_apikeys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_apikeys` (
  `key_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT 0,
  `apikey` varchar(255) NOT NULL DEFAULT '',
  `expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`key_id`),
  KEY `i_uid` (`uid`),
  KEY `i_apikey` (`apikey`),
  KEY `i_expiry` (`expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_apikeys`
--

LOCK TABLES `ai8k7Bba_formulize_apikeys` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_apikeys` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_apikeys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_application_form_link`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_application_form_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_application_form_link` (
  `linkid` int(11) NOT NULL AUTO_INCREMENT,
  `appid` int(11) NOT NULL DEFAULT 0,
  `fid` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`linkid`),
  KEY `i_fid` (`fid`),
  KEY `i_appid` (`appid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_application_form_link`
--

LOCK TABLES `ai8k7Bba_formulize_application_form_link` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_application_form_link` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_formulize_application_form_link` VALUES
(1,1,1),
(2,1,2),
(3,1,3),
(4,1,4),
(5,1,5),
(6,1,6);
/*!40000 ALTER TABLE `ai8k7Bba_formulize_application_form_link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_applications`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_applications` (
  `appid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `custom_code` mediumtext DEFAULT NULL,
  PRIMARY KEY (`appid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_applications`
--

LOCK TABLES `ai8k7Bba_formulize_applications` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_applications` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_formulize_applications` VALUES
(1,'Public Policy','','');
/*!40000 ALTER TABLE `ai8k7Bba_formulize_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_deletion_logs`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_deletion_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_deletion_logs` (
  `del_log_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `entry_id` int(7) NOT NULL,
  `user_id` mediumint(8) NOT NULL,
  `context` text DEFAULT NULL,
  `deletion_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`del_log_id`),
  KEY `i_del_id` (`del_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_deletion_logs`
--

LOCK TABLES `ai8k7Bba_formulize_deletion_logs` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_deletion_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_deletion_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_digest_data`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_digest_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_digest_data` (
  `digest_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `fid` int(11) DEFAULT NULL,
  `event` varchar(50) DEFAULT NULL,
  `extra_tags` text DEFAULT NULL,
  `mailSubject` text DEFAULT NULL,
  `mailTemplate` text DEFAULT NULL,
  PRIMARY KEY (`digest_id`),
  KEY `i_email` (`email`),
  KEY `i_fid` (`fid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_digest_data`
--

LOCK TABLES `ai8k7Bba_formulize_digest_data` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_digest_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_digest_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_entry_owner_groups`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_entry_owner_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_entry_owner_groups` (
  `owner_id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `fid` int(5) NOT NULL DEFAULT 0,
  `entry_id` int(7) NOT NULL DEFAULT 0,
  `groupid` int(5) NOT NULL DEFAULT 0,
  PRIMARY KEY (`owner_id`),
  KEY `i_fid` (`fid`),
  KEY `i_entry_id` (`entry_id`),
  KEY `i_groupid` (`groupid`)
) ENGINE=InnoDB AUTO_INCREMENT=119 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_entry_owner_groups`
--

LOCK TABLES `ai8k7Bba_formulize_entry_owner_groups` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_entry_owner_groups` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_formulize_entry_owner_groups` VALUES
(1,1,1,1),
(2,1,1,2),
(3,3,1,1),
(4,3,1,2),
(5,2,1,1),
(6,2,1,2),
(7,4,1,1),
(8,4,1,2),
(9,5,1,1),
(10,5,1,2),
(11,3,2,1),
(12,3,2,2),
(13,5,2,1),
(14,5,2,2),
(21,6,1,1),
(22,6,1,2),
(23,6,2,1),
(24,6,2,2),
(53,2,3,1),
(54,2,3,2),
(55,4,18,1),
(56,4,18,2),
(59,4,20,1),
(60,4,20,2),
(99,4,40,1),
(100,4,40,2),
(101,4,41,1),
(102,4,41,2),
(103,5,3,1),
(104,5,3,2),
(109,4,44,1),
(110,4,44,2),
(111,5,4,1),
(112,5,4,2),
(113,4,45,1),
(114,4,45,2),
(115,5,5,1),
(116,5,5,2),
(117,3,3,1),
(118,3,3,2);
/*!40000 ALTER TABLE `ai8k7Bba_formulize_entry_owner_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_framework_links`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_framework_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_framework_links` (
  `fl_id` smallint(5) NOT NULL AUTO_INCREMENT,
  `fl_frame_id` smallint(5) DEFAULT NULL,
  `fl_form1_id` smallint(5) DEFAULT NULL,
  `fl_form2_id` smallint(5) DEFAULT NULL,
  `fl_key1` smallint(5) DEFAULT NULL,
  `fl_key2` smallint(5) DEFAULT NULL,
  `fl_relationship` smallint(5) DEFAULT NULL,
  `fl_unified_display` smallint(5) DEFAULT NULL,
  `fl_unified_delete` smallint(5) DEFAULT NULL,
  `fl_common_value` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`fl_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_framework_links`
--

LOCK TABLES `ai8k7Bba_formulize_framework_links` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_framework_links` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_formulize_framework_links` VALUES
(1,1,1,2,1,4,2,1,0,0),
(2,1,3,2,11,13,2,1,0,0),
(6,1,3,1,9,1,3,1,0,0),
(7,1,3,4,11,31,2,1,0,0),
(8,1,3,5,11,20,2,1,0,0),
(9,1,5,4,21,32,1,1,0,0);
/*!40000 ALTER TABLE `ai8k7Bba_formulize_framework_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_frameworks`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_frameworks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_frameworks` (
  `frame_id` smallint(5) NOT NULL AUTO_INCREMENT,
  `frame_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`frame_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_frameworks`
--

LOCK TABLES `ai8k7Bba_formulize_frameworks` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_frameworks` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_formulize_frameworks` VALUES
(1,'Topics + Publications');
/*!40000 ALTER TABLE `ai8k7Bba_formulize_frameworks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_group_filters`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_group_filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_group_filters` (
  `filterid` int(11) NOT NULL AUTO_INCREMENT,
  `fid` int(11) NOT NULL DEFAULT 0,
  `groupid` int(11) NOT NULL DEFAULT 0,
  `filter` text NOT NULL,
  PRIMARY KEY (`filterid`),
  KEY `i_fid` (`fid`),
  KEY `i_groupid` (`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_group_filters`
--

LOCK TABLES `ai8k7Bba_formulize_group_filters` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_group_filters` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_group_filters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_groupscope_settings`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_groupscope_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_groupscope_settings` (
  `groupscope_id` int(11) NOT NULL AUTO_INCREMENT,
  `groupid` int(11) NOT NULL DEFAULT 0,
  `fid` int(11) NOT NULL DEFAULT 0,
  `view_groupid` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`groupscope_id`),
  KEY `i_groupid` (`groupid`),
  KEY `i_fid` (`fid`),
  KEY `i_view_groupid` (`view_groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_groupscope_settings`
--

LOCK TABLES `ai8k7Bba_formulize_groupscope_settings` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_groupscope_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_groupscope_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_id`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_id`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_id` (
  `id_form` smallint(5) NOT NULL AUTO_INCREMENT,
  `desc_form` varchar(255) NOT NULL DEFAULT '',
  `singleentry` varchar(5) DEFAULT NULL,
  `headerlist` text DEFAULT NULL,
  `tableform` varchar(255) DEFAULT NULL,
  `lockedform` tinyint(1) DEFAULT NULL,
  `defaultform` int(11) NOT NULL DEFAULT 0,
  `defaultlist` int(11) NOT NULL DEFAULT 0,
  `menutext` varchar(255) DEFAULT NULL,
  `form_handle` varchar(255) NOT NULL DEFAULT '',
  `store_revisions` tinyint(1) NOT NULL DEFAULT 0,
  `on_before_save` text DEFAULT NULL,
  `on_after_save` text DEFAULT NULL,
  `custom_edit_check` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `send_digests` tinyint(1) NOT NULL DEFAULT 0,
  `on_delete` text DEFAULT NULL,
  PRIMARY KEY (`id_form`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_id`
--

LOCK TABLES `ai8k7Bba_formulize_id` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_id` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_formulize_id` VALUES
(1,'Topics','','','',NULL,1,2,'','topics',0,'','','','',0,''),
(2,'Publications','','','',NULL,3,4,'','publications',0,'','','','',0,''),
(3,'Activities','','','',NULL,5,6,'','activities',0,'','','','',0,''),
(4,'People','','','',NULL,7,8,'','people',0,'','','','',0,''),
(5,'People in Activities','','','',NULL,9,10,'','people_in_activities',0,'','','','',0,''),
(6,'Pages','','','',NULL,15,16,'','pages',0,'','','','',0,'');
/*!40000 ALTER TABLE `ai8k7Bba_formulize_id` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_menu_links`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_menu_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_menu_links` (
  `menu_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `appid` int(11) unsigned NOT NULL,
  `screen` varchar(11) DEFAULT NULL,
  `rank` int(11) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `link_text` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  PRIMARY KEY (`menu_id`),
  KEY `i_menus_appid` (`appid`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_menu_links`
--

LOCK TABLES `ai8k7Bba_formulize_menu_links` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_menu_links` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_formulize_menu_links` VALUES
(1,1,'fid=1',1,'','Topics',''),
(2,1,'fid=2',2,'','Publications',''),
(3,1,'fid=3',3,'','Activities',''),
(4,1,'fid=4',4,'','People',''),
(5,1,'fid=5',5,'','People in Activities',''),
(6,1,'fid=6',6,'','Pages',''),
(7,1,'sid=17',7,'','Activities','');
/*!40000 ALTER TABLE `ai8k7Bba_formulize_menu_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_menu_permissions`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_menu_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_menu_permissions` (
  `permission_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` int(11) unsigned NOT NULL,
  `group_id` int(11) unsigned NOT NULL,
  `default_screen` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`permission_id`),
  KEY `i_menu_permissions` (`menu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_menu_permissions`
--

LOCK TABLES `ai8k7Bba_formulize_menu_permissions` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_menu_permissions` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_formulize_menu_permissions` VALUES
(7,7,3,1),
(8,1,1,0),
(9,2,1,0),
(10,3,1,0),
(11,4,1,0),
(12,5,1,0),
(13,6,1,0);
/*!40000 ALTER TABLE `ai8k7Bba_formulize_menu_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_notification_conditions`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_notification_conditions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_notification_conditions` (
  `not_cons_id` smallint(5) NOT NULL AUTO_INCREMENT,
  `not_cons_fid` smallint(5) NOT NULL DEFAULT 0,
  `not_cons_event` varchar(25) DEFAULT '',
  `not_cons_uid` mediumint(8) NOT NULL DEFAULT 0,
  `not_cons_curuser` tinyint(1) DEFAULT NULL,
  `not_cons_groupid` smallint(5) NOT NULL DEFAULT 0,
  `not_cons_creator` tinyint(1) DEFAULT NULL,
  `not_cons_elementuids` smallint(5) NOT NULL DEFAULT 0,
  `not_cons_linkcreator` smallint(5) NOT NULL DEFAULT 0,
  `not_cons_elementemail` smallint(5) NOT NULL DEFAULT 0,
  `not_cons_arbitrary` text DEFAULT NULL,
  `not_cons_con` text NOT NULL,
  `not_cons_template` varchar(255) DEFAULT '',
  `not_cons_subject` varchar(255) DEFAULT '',
  PRIMARY KEY (`not_cons_id`),
  KEY `i_not_cons_fid` (`not_cons_fid`),
  KEY `i_not_cons_uid` (`not_cons_uid`),
  KEY `i_not_cons_groupid` (`not_cons_groupid`),
  KEY `i_not_cons_fidevent` (`not_cons_fid`,`not_cons_event`(1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_notification_conditions`
--

LOCK TABLES `ai8k7Bba_formulize_notification_conditions` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_notification_conditions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_notification_conditions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_other`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_other`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_other` (
  `other_id` int(5) NOT NULL AUTO_INCREMENT,
  `id_req` int(5) DEFAULT NULL,
  `ele_id` int(5) DEFAULT NULL,
  `other_text` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`other_id`),
  KEY `i_ele_id` (`ele_id`),
  KEY `i_id_req` (`id_req`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_other`
--

LOCK TABLES `ai8k7Bba_formulize_other` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_other` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_other` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_pages`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_pages` (
  `entry_id` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `creation_datetime` datetime DEFAULT NULL,
  `mod_datetime` datetime DEFAULT NULL,
  `creation_uid` int(7) DEFAULT 0,
  `mod_uid` int(7) DEFAULT 0,
  `pages_title` text DEFAULT NULL,
  `pages_content` text DEFAULT NULL,
  PRIMARY KEY (`entry_id`),
  KEY `i_creation_uid` (`creation_uid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_pages`
--

LOCK TABLES `ai8k7Bba_formulize_pages` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_pages` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_formulize_pages` VALUES
(1,'2024-02-05 13:23:18','2024-02-05 13:23:18',2,2,'About Us','&lt;p&gt;&lt;i&gt;&lt;strong&gt;Here you can read about us&lt;/strong&gt;&lt;/i&gt;&lt;/p&gt;'),
(2,'2024-02-05 13:25:13','2024-02-05 13:25:13',2,2,'Contact Us','&lt;p&gt;&lt;i&gt;&lt;strong&gt;Please contact us&lt;/strong&gt;&lt;/i&gt;&lt;/p&gt;');
/*!40000 ALTER TABLE `ai8k7Bba_formulize_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_passcodes`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_passcodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_passcodes` (
  `passcode_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `passcode` text DEFAULT NULL,
  `screen` int(11) NOT NULL DEFAULT 0,
  `expiry` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`passcode_id`),
  KEY `i_passcode` (`passcode`(50)),
  KEY `i_screen` (`screen`),
  KEY `i_expiry` (`expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_passcodes`
--

LOCK TABLES `ai8k7Bba_formulize_passcodes` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_passcodes` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_passcodes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_people`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_people`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_people` (
  `entry_id` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `creation_datetime` datetime DEFAULT NULL,
  `mod_datetime` datetime DEFAULT NULL,
  `creation_uid` int(7) DEFAULT 0,
  `mod_uid` int(7) DEFAULT 0,
  `people_first_name` text DEFAULT NULL,
  `people_person_notes` text DEFAULT NULL,
  `people_last_name` text DEFAULT NULL,
  `people_person_email` varchar(255) DEFAULT NULL,
  `people_person_phone` varchar(255) DEFAULT NULL,
  `people_activities` text DEFAULT NULL,
  `people_person_name` text DEFAULT NULL,
  PRIMARY KEY (`entry_id`),
  KEY `i_creation_uid` (`creation_uid`),
  FULLTEXT KEY `people_activities` (`people_activities`),
  FULLTEXT KEY `people_person_name` (`people_person_name`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_people`
--

LOCK TABLES `ai8k7Bba_formulize_people` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_people` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_formulize_people` VALUES
(1,'2024-01-25 08:23:21','2024-02-05 12:50:49',2,2,'David','Head guru of everything','Sloly','something@gmail.com','--',',2,','David Sloly'),
(18,'2024-02-13 20:32:31','2024-02-13 20:54:11',2,2,'John','xxx','Smith','Test@my.com','--','1','John Smith'),
(20,'2024-02-14 03:27:42','2024-02-14 07:05:52',2,2,'Frank',NULL,'Lynn','','--',NULL,'Frank Lynn'),
(40,'2024-02-14 06:23:24','2024-02-14 06:24:11',2,2,'qqq',NULL,'www','','--',NULL,'qqq www'),
(41,'2024-02-14 06:26:13','2024-02-14 06:26:14',2,2,'Unwritten',NULL,'Esq','','--',NULL,'Unwritten Esq'),
(44,'2024-02-14 07:13:01','2024-02-14 07:13:01',2,2,'New',NULL,'2','','--',NULL,'New 2'),
(45,'2024-02-14 07:13:57','2024-02-14 07:13:58',2,2,'new',NULL,'3','','--',NULL,'new 3');
/*!40000 ALTER TABLE `ai8k7Bba_formulize_people` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_people_in_activities`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_people_in_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_people_in_activities` (
  `entry_id` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `creation_datetime` datetime DEFAULT NULL,
  `mod_datetime` datetime DEFAULT NULL,
  `creation_uid` int(7) DEFAULT 0,
  `mod_uid` int(7) DEFAULT 0,
  `people_in_activities_activity` bigint(20) DEFAULT NULL,
  `people_in_activities_person` bigint(20) DEFAULT NULL,
  `people_in_activities_role` text DEFAULT NULL,
  PRIMARY KEY (`entry_id`),
  KEY `i_creation_uid` (`creation_uid`),
  KEY `people_in_activities_activity` (`people_in_activities_activity`),
  KEY `people_in_activities_person` (`people_in_activities_person`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_people_in_activities`
--

LOCK TABLES `ai8k7Bba_formulize_people_in_activities` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_people_in_activities` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_formulize_people_in_activities` VALUES
(1,'2024-01-25 08:23:52','2024-02-14 07:08:29',2,2,1,43,'Host/MC'),
(2,'2024-01-25 08:25:40','2024-01-25 08:25:40',2,2,2,1,'Speaker'),
(3,'2024-02-14 06:26:14','2024-02-14 06:27:14',2,2,2,1,'Host/MC'),
(4,'2024-02-14 07:13:01','2024-02-14 07:13:58',2,2,1,45,NULL),
(5,'2024-02-14 07:14:53','2024-02-14 07:15:24',2,2,1,20,'Speaker');
/*!40000 ALTER TABLE `ai8k7Bba_formulize_people_in_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_procedure_logs`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_procedure_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_procedure_logs` (
  `proc_log_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `proc_id` int(11) NOT NULL,
  `proc_datetime` datetime NOT NULL,
  `proc_uid` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`proc_log_id`),
  KEY `i_proc_id` (`proc_id`),
  KEY `i_proc_uid` (`proc_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_procedure_logs`
--

LOCK TABLES `ai8k7Bba_formulize_procedure_logs` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_procedure_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_procedure_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_procedure_logs_params`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_procedure_logs_params`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_procedure_logs_params` (
  `proc_log_param_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `proc_log_id` int(11) unsigned NOT NULL,
  `proc_log_param` varchar(255) DEFAULT NULL,
  `proc_log_value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`proc_log_param_id`),
  KEY `i_proc_log_id` (`proc_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_procedure_logs_params`
--

LOCK TABLES `ai8k7Bba_formulize_procedure_logs_params` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_procedure_logs_params` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_procedure_logs_params` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_publications`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_publications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_publications` (
  `entry_id` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `creation_datetime` datetime DEFAULT NULL,
  `mod_datetime` datetime DEFAULT NULL,
  `creation_uid` int(7) DEFAULT 0,
  `mod_uid` int(7) DEFAULT 0,
  `publications_topic` bigint(20) DEFAULT NULL,
  `publications_title` text DEFAULT NULL,
  `publications_file` text DEFAULT NULL,
  `publications_url` text DEFAULT NULL,
  `publications_activity` bigint(20) DEFAULT NULL,
  `publications_type` text DEFAULT NULL,
  `publications_source` text DEFAULT NULL,
  PRIMARY KEY (`entry_id`),
  KEY `i_creation_uid` (`creation_uid`),
  KEY `publications_topic` (`publications_topic`),
  KEY `publications_activity` (`publications_activity`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_publications`
--

LOCK TABLES `ai8k7Bba_formulize_publications` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_publications` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_formulize_publications` VALUES
(1,'2024-01-25 08:22:15','2024-01-30 13:29:22',2,2,1,'Publication XYZ','a:4:{s:4:\"name\";s:38:\"1706646814.5684+---+MasseyWebForms.txt\";s:6:\"isfile\";b:1;s:4:\"type\";s:10:\"text/plain\";s:4:\"size\";i:809;}',NULL,NULL,'1','The Toronto Star'),
(3,'2024-02-06 07:25:25','2024-02-06 07:25:48',2,2,1,'Publication 123',NULL,NULL,1,'2','The Toronto Star');
/*!40000 ALTER TABLE `ai8k7Bba_formulize_publications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_resource_mapping`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_resource_mapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_resource_mapping` (
  `mapping_id` int(11) NOT NULL AUTO_INCREMENT,
  `internal_id` int(11) NOT NULL,
  `external_id` int(11) DEFAULT NULL,
  `resource_type` int(4) NOT NULL,
  `mapping_active` tinyint(1) NOT NULL,
  `external_id_string` text DEFAULT NULL,
  PRIMARY KEY (`mapping_id`),
  KEY `i_internal_id` (`internal_id`),
  KEY `i_external_id` (`external_id`),
  KEY `i_resource_type` (`resource_type`),
  KEY `i_external_id_string` (`external_id_string`(10))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_resource_mapping`
--

LOCK TABLES `ai8k7Bba_formulize_resource_mapping` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_resource_mapping` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_resource_mapping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_saved_views`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_saved_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_saved_views` (
  `sv_id` smallint(5) NOT NULL AUTO_INCREMENT,
  `sv_name` varchar(255) DEFAULT NULL,
  `sv_pubgroups` text DEFAULT NULL,
  `sv_owner_uid` int(5) DEFAULT NULL,
  `sv_mod_uid` int(5) DEFAULT NULL,
  `sv_formframe` varchar(255) DEFAULT NULL,
  `sv_mainform` varchar(255) DEFAULT NULL,
  `sv_lockcontrols` tinyint(1) DEFAULT NULL,
  `sv_hidelist` tinyint(1) DEFAULT NULL,
  `sv_hidecalc` tinyint(1) DEFAULT NULL,
  `sv_asearch` text DEFAULT NULL,
  `sv_sort` varchar(255) DEFAULT NULL,
  `sv_order` varchar(30) DEFAULT NULL,
  `sv_oldcols` text DEFAULT NULL,
  `sv_currentview` text DEFAULT NULL,
  `sv_calc_cols` text DEFAULT NULL,
  `sv_calc_calcs` text DEFAULT NULL,
  `sv_calc_blanks` text DEFAULT NULL,
  `sv_calc_grouping` text DEFAULT NULL,
  `sv_quicksearches` text DEFAULT NULL,
  `sv_global_search` text DEFAULT NULL,
  `sv_pubfilters` text DEFAULT NULL,
  `sv_entriesperpage` tinyint(3) DEFAULT NULL,
  PRIMARY KEY (`sv_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_saved_views`
--

LOCK TABLES `ai8k7Bba_formulize_saved_views` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_saved_views` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_saved_views` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_screen`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_screen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_screen` (
  `sid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `fid` int(11) NOT NULL DEFAULT 0,
  `frid` int(11) NOT NULL DEFAULT 0,
  `type` varchar(100) NOT NULL DEFAULT '',
  `useToken` tinyint(1) NOT NULL,
  `anonNeedsPasscode` tinyint(1) NOT NULL,
  `theme` varchar(101) NOT NULL DEFAULT '',
  PRIMARY KEY (`sid`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_screen`
--

LOCK TABLES `ai8k7Bba_formulize_screen` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_screen` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_formulize_screen` VALUES
(1,'Topics Form',1,1,'multiPage',1,0,'Anari'),
(2,'Topics List',1,1,'listOfEntries',1,0,'Anari'),
(3,'Publications Form',2,1,'multiPage',1,0,'Anari'),
(4,'Publications List',2,1,'listOfEntries',1,0,'Anari'),
(5,'Activities Form',3,1,'multiPage',1,0,'Anari'),
(6,'Activities List',3,1,'listOfEntries',1,0,'Anari'),
(7,'People Form',4,0,'multiPage',1,0,'Anari'),
(8,'People List',4,0,'listOfEntries',1,0,'Anari'),
(9,'People in Activities Form',5,1,'multiPage',1,0,'Anari'),
(10,'People in Activities List',5,1,'listOfEntries',1,0,'Anari'),
(11,'Publications Form - by Topic',2,1,'multiPage',1,0,'Anari'),
(12,'Activities Form - by Topic',3,1,'multiPage',1,0,'Anari'),
(13,'Publications Form - by Activity',2,1,'multiPage',1,0,'Anari'),
(14,'People Form - by Activity',4,0,'multiPage',1,0,'Anari'),
(15,'Pages Form',6,0,'multiPage',1,0,'Anari'),
(16,'Pages List',6,0,'listOfEntries',1,0,'Anari'),
(17,'Activities',3,1,'listOfEntries',1,0,'Anari'),
(18,'Activity Template',3,0,'template',1,1,'Anari');
/*!40000 ALTER TABLE `ai8k7Bba_formulize_screen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_screen_calendar`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_screen_calendar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_screen_calendar` (
  `calendar_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(11) DEFAULT NULL,
  `caltype` varchar(50) DEFAULT NULL,
  `datasets` text DEFAULT NULL,
  PRIMARY KEY (`calendar_id`),
  KEY `i_sid` (`sid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_screen_calendar`
--

LOCK TABLES `ai8k7Bba_formulize_screen_calendar` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_screen_calendar` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_screen_calendar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_screen_form`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_screen_form`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_screen_form` (
  `formid` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL DEFAULT 0,
  `donedest` varchar(255) NOT NULL DEFAULT '',
  `savebuttontext` varchar(255) NOT NULL DEFAULT '',
  `saveandleavebuttontext` varchar(255) NOT NULL DEFAULT '',
  `printableviewbuttontext` varchar(255) NOT NULL DEFAULT '',
  `alldonebuttontext` varchar(255) NOT NULL DEFAULT '',
  `displayheading` tinyint(1) NOT NULL DEFAULT 0,
  `reloadblank` tinyint(1) NOT NULL DEFAULT 0,
  `formelements` text DEFAULT NULL,
  `elementdefaults` text NOT NULL,
  `displaycolumns` tinyint(1) NOT NULL DEFAULT 2,
  `column1width` varchar(255) DEFAULT NULL,
  `column2width` varchar(255) DEFAULT NULL,
  `displayType` varchar(255) NOT NULL DEFAULT 'block',
  PRIMARY KEY (`formid`),
  KEY `i_sid` (`sid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_screen_form`
--

LOCK TABLES `ai8k7Bba_formulize_screen_form` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_screen_form` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_screen_form` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_screen_listofentries`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_screen_listofentries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_screen_listofentries` (
  `listofentriesid` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL DEFAULT 0,
  `useworkingmsg` tinyint(1) NOT NULL,
  `repeatheaders` tinyint(1) NOT NULL,
  `useaddupdate` varchar(255) NOT NULL DEFAULT '',
  `useaddmultiple` varchar(255) NOT NULL DEFAULT '',
  `useaddproxy` varchar(255) NOT NULL DEFAULT '',
  `usecurrentviewlist` varchar(255) NOT NULL DEFAULT '',
  `limitviews` text NOT NULL,
  `defaultview` text NOT NULL,
  `advanceview` text NOT NULL,
  `usechangecols` varchar(255) NOT NULL DEFAULT '',
  `usecalcs` varchar(255) NOT NULL DEFAULT '',
  `useadvcalcs` varchar(255) NOT NULL DEFAULT '',
  `useadvsearch` varchar(255) NOT NULL DEFAULT '',
  `useexport` varchar(255) NOT NULL DEFAULT '',
  `useexportcalcs` varchar(255) NOT NULL DEFAULT '',
  `useimport` varchar(255) NOT NULL DEFAULT '',
  `useclone` varchar(255) NOT NULL DEFAULT '',
  `usedelete` varchar(255) NOT NULL DEFAULT '',
  `useselectall` varchar(255) NOT NULL DEFAULT '',
  `useclearall` varchar(255) NOT NULL DEFAULT '',
  `usenotifications` varchar(255) NOT NULL DEFAULT '',
  `usereset` varchar(255) NOT NULL DEFAULT '',
  `usesave` varchar(255) NOT NULL DEFAULT '',
  `usedeleteview` varchar(255) NOT NULL DEFAULT '',
  `useheadings` tinyint(1) NOT NULL,
  `usesearch` tinyint(1) NOT NULL,
  `usecheckboxes` tinyint(1) NOT NULL,
  `useviewentrylinks` tinyint(1) NOT NULL,
  `usescrollbox` tinyint(1) NOT NULL,
  `usesearchcalcmsgs` tinyint(1) NOT NULL,
  `hiddencolumns` text NOT NULL,
  `decolumns` text NOT NULL,
  `dedisplay` int(1) NOT NULL,
  `desavetext` varchar(255) NOT NULL DEFAULT '',
  `columnwidth` int(1) NOT NULL,
  `textwidth` int(1) NOT NULL,
  `customactions` text NOT NULL,
  `entriesperpage` int(1) NOT NULL,
  `viewentryscreen` varchar(10) NOT NULL DEFAULT '',
  `fundamental_filters` text NOT NULL,
  PRIMARY KEY (`listofentriesid`),
  KEY `i_sid` (`sid`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_screen_listofentries`
--

LOCK TABLES `ai8k7Bba_formulize_screen_listofentries` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_screen_listofentries` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_formulize_screen_listofentries` VALUES
(1,2,1,0,'Add one topic','Add multiple topics','Make a proxy entry','','a:1:{i:0;s:8:\"allviews\";}','a:1:{i:2;s:3:\"all\";}','a:2:{i:0;a:4:{i:0;s:18:\"publications_topic\";i:1;s:0:\"\";i:2;i:0;i:3;s:3:\"Box\";}i:1;a:4:{i:0;s:14:\"topics_outline\";i:1;s:0:\"\";i:2;i:0;i:3;s:3:\"Box\";}}','Change columns','Calculations','Procedures','','Export Entries','Export Calcs','Import Data','Clone selected','Delete selected','Select all','Clear selection','Notifications','Reset view','Save view','Delete view',1,1,0,1,1,1,'a:0:{}','a:0:{}',0,'Save',0,35,'a:0:{}',10,'1','s:0:\"\";'),
(2,4,1,0,'Add one publication','Add multiple pubs','','','a:1:{i:0;s:8:\"allviews\";}','a:1:{i:2;s:3:\"all\";}','a:3:{i:0;a:4:{i:0;s:18:\"publications_title\";i:1;s:0:\"\";i:2;i:1;i:3;s:3:\"Box\";}i:1;a:4:{i:0;s:17:\"publications_type\";i:1;s:0:\"\";i:2;i:0;i:3;s:3:\"Box\";}i:2;a:4:{i:0;s:19:\"publications_source\";i:1;s:0:\"\";i:2;i:0;i:3;s:3:\"Box\";}}','Change columns','Calculations','Procedures','','Export Entries','Export Calcs','Import Data','Clone selected','Delete selected','Select all','Clear selection','Notifications','Reset view','Save view','Delete view',1,1,0,1,1,1,'a:0:{}','a:0:{}',0,'Save',0,35,'a:0:{}',10,'3','s:0:\"\";'),
(3,6,1,0,'Add Activity','Add multiple Activities','','','a:1:{i:0;s:8:\"allviews\";}','a:1:{i:2;s:3:\"all\";}','a:3:{i:0;a:4:{i:0;s:24:\"activities_activity_name\";i:1;s:0:\"\";i:2;i:1;i:3;s:3:\"Box\";}i:1;a:4:{i:0;s:15:\"activities_type\";i:1;s:0:\"\";i:2;i:0;i:3;s:3:\"Box\";}i:2;a:4:{i:0;s:18:\"activities_details\";i:1;s:0:\"\";i:2;i:0;i:3;s:3:\"Box\";}}','','','','','','','','','','','','','','','',1,0,2,1,1,1,'a:0:{}','a:0:{}',0,'Save',0,35,'a:0:{}',10,'5','s:0:\"\";'),
(4,8,1,0,'Add one person','Add multiple people','Make a proxy entry','Showing: ','a:1:{i:0;s:8:\"allviews\";}','a:1:{i:2;s:3:\"all\";}','a:3:{i:0;a:4:{i:0;s:16:\"people_last_name\";i:1;s:0:\"\";i:2;i:1;i:3;s:3:\"Box\";}i:1;a:4:{i:0;s:17:\"people_first_name\";i:1;s:0:\"\";i:2;i:0;i:3;s:3:\"Box\";}i:2;a:4:{i:0;s:18:\"people_person_type\";i:1;s:0:\"\";i:2;i:0;i:3;s:3:\"Box\";}}','Change columns','Calculations','Procedures','','Export Entries','Export Calcs','Import Data','Clone selected','Delete selected','Select all','Clear selection','Notifications','Reset view','Save view','Delete view',1,1,0,1,1,1,'a:0:{}','a:0:{}',0,'Save',0,35,'a:0:{}',10,'7','s:0:\"\";'),
(5,10,1,0,'Add one entry','Add multiple entries','Make a proxy entry','Showing: ','a:1:{i:0;s:8:\"allviews\";}','a:1:{i:2;s:4:\"b:0;\";}','b:0;','Change columns','Calculations','Procedures','','Export Entries','Export Calcs','Import Data','Clone selected','Delete selected','Select all','Clear selection','Notifications','Reset view','Save view','Delete view',1,1,0,1,1,1,'b:0;','b:0;',0,'Save',0,35,'b:0;',10,'9','b:0;'),
(6,16,1,0,'Add one entry','Add multiple entries','Make a proxy entry','Showing: ','a:1:{i:0;s:8:\"allviews\";}','a:1:{i:2;s:3:\"all\";}','b:0;','Change columns','Calculations','Procedures','','Export Entries','Export Calcs','Import Data','Clone selected','Delete selected','Select all','Clear selection','Notifications','Reset view','Save view','Delete view',1,1,0,1,1,1,'b:0;','b:0;',0,'Save',0,35,'b:0;',10,'15','b:0;'),
(7,17,1,0,'Add Activity','Add multiple Activities','','','a:1:{i:0;s:8:\"allviews\";}','a:1:{i:2;s:3:\"all\";}','a:3:{i:0;a:4:{i:0;s:24:\"activities_activity_name\";i:1;s:0:\"\";i:2;i:1;i:3;s:3:\"Box\";}i:1;a:4:{i:0;s:15:\"activities_type\";i:1;s:0:\"\";i:2;i:0;i:3;s:3:\"Box\";}i:2;a:4:{i:0;s:18:\"activities_details\";i:1;s:0:\"\";i:2;i:0;i:3;s:3:\"Box\";}}','','','','','','','','','','','','','','','',1,0,2,1,1,1,'a:0:{}','a:0:{}',0,'Save',0,35,'a:0:{}',10,'18','s:0:\"\";');
/*!40000 ALTER TABLE `ai8k7Bba_formulize_screen_listofentries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_screen_multipage`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_screen_multipage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_screen_multipage` (
  `multipageid` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL DEFAULT 0,
  `introtext` text NOT NULL,
  `thankstext` text NOT NULL,
  `donedest` varchar(255) NOT NULL DEFAULT '',
  `buttontext` text DEFAULT NULL,
  `finishisdone` tinyint(1) NOT NULL DEFAULT 0,
  `navstyle` tinyint(1) NOT NULL DEFAULT 0,
  `pages` text NOT NULL,
  `pagetitles` text NOT NULL,
  `conditions` text NOT NULL,
  `printall` tinyint(1) NOT NULL,
  `paraentryform` int(11) NOT NULL DEFAULT 0,
  `paraentryrelationship` tinyint(1) NOT NULL DEFAULT 0,
  `displaycolumns` tinyint(1) NOT NULL DEFAULT 2,
  `column1width` varchar(255) DEFAULT NULL,
  `column2width` varchar(255) DEFAULT NULL,
  `showpagetitles` tinyint(1) NOT NULL,
  `showpageselector` tinyint(1) NOT NULL,
  `showpageindicator` tinyint(1) NOT NULL,
  `displayheading` tinyint(1) NOT NULL DEFAULT 0,
  `reloadblank` tinyint(1) NOT NULL DEFAULT 0,
  `elementdefaults` text NOT NULL,
  PRIMARY KEY (`multipageid`),
  KEY `i_sid` (`sid`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_screen_multipage`
--

LOCK TABLES `ai8k7Bba_formulize_screen_multipage` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_screen_multipage` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_formulize_screen_multipage` VALUES
(1,1,'','','','a:7:{s:16:\"thankyoulinktext\";s:46:\"Leave this form and continue browsing the site\";s:15:\"leaveButtonText\";s:14:\"Save and Leave\";s:14:\"prevButtonText\";s:16:\"Save and Go Back\";s:14:\"saveButtonText\";s:4:\"Save\";s:14:\"nextButtonText\";s:17:\"Save and Continue\";s:16:\"finishButtonText\";s:15:\"Save and Finish\";s:23:\"printableViewButtonText\";s:14:\"Printable View\";}',1,1,'a:3:{i:0;a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}i:1;a:1:{i:0;s:1:\"8\";}i:2;a:1:{i:0;s:2:\"24\";}}','a:3:{i:0;s:6:\"Topics\";i:1;s:12:\"Publications\";i:2;s:10:\"Activities\";}','a:3:{i:0;a:0:{}i:1;a:0:{}i:2;a:0:{}}',0,0,1,2,'20%','auto',2,2,2,0,0,'b:0;'),
(2,3,'','','','a:7:{s:16:\"thankyoulinktext\";s:0:\"\";s:15:\"leaveButtonText\";s:14:\"Save and Leave\";s:14:\"prevButtonText\";s:16:\"Save and Go Back\";s:14:\"saveButtonText\";s:4:\"Save\";s:14:\"nextButtonText\";s:17:\"Save and Continue\";s:16:\"finishButtonText\";s:15:\"Save and Finish\";s:23:\"printableViewButtonText\";s:14:\"Printable View\";}',1,1,'a:1:{i:0;a:7:{i:0;i:4;i:1;i:5;i:2;i:6;i:3;i:7;i:4;i:13;i:5;i:25;i:6;i:26;}}','a:1:{i:0;s:12:\"Publications\";}','b:0;',0,0,0,2,'20%','auto',0,0,0,0,0,'b:0;'),
(3,5,'','','','a:7:{s:16:\"thankyoulinktext\";s:46:\"Leave this form and continue browsing the site\";s:15:\"leaveButtonText\";s:14:\"Save and Leave\";s:14:\"prevButtonText\";s:16:\"Save and Go Back\";s:14:\"saveButtonText\";s:4:\"Save\";s:14:\"nextButtonText\";s:17:\"Save and Continue\";s:16:\"finishButtonText\";s:15:\"Save and Finish\";s:23:\"printableViewButtonText\";s:14:\"Printable View\";}',1,1,'a:3:{i:0;a:7:{i:0;s:1:\"9\";i:1;s:2:\"11\";i:2;s:2:\"10\";i:3;s:2:\"37\";i:4;s:2:\"38\";i:5;s:2:\"40\";i:6;s:2:\"12\";}i:1;a:1:{i:0;s:2:\"14\";}i:2;a:1:{i:0;s:2:\"19\";}}','a:3:{i:0;s:10:\"Activities\";i:1;s:12:\"Publications\";i:2;s:6:\"People\";}','a:3:{i:0;a:0:{}i:1;a:0:{}i:2;a:0:{}}',0,0,1,2,'20%','auto',2,2,2,0,0,'b:0;'),
(4,7,'','','','a:7:{s:16:\"thankyoulinktext\";s:46:\"Leave this form and continue browsing the site\";s:15:\"leaveButtonText\";s:14:\"Save and Leave\";s:14:\"prevButtonText\";s:16:\"Save and Go Back\";s:14:\"saveButtonText\";s:4:\"Save\";s:14:\"nextButtonText\";s:17:\"Save and Continue\";s:16:\"finishButtonText\";s:15:\"Save and Finish\";s:23:\"printableViewButtonText\";s:14:\"Printable View\";}',1,1,'a:1:{i:0;a:9:{i:0;i:15;i:1;i:16;i:2;i:17;i:3;i:18;i:4;i:27;i:5;i:28;i:6;i:29;i:7;i:30;i:8;i:31;}}','a:1:{i:0;s:6:\"People\";}','b:0;',0,0,1,2,'20%','auto',2,2,2,0,0,'b:0;'),
(5,9,'','','','a:7:{s:16:\"thankyoulinktext\";s:0:\"\";s:15:\"leaveButtonText\";s:14:\"Save and Leave\";s:14:\"prevButtonText\";s:16:\"Save and Go Back\";s:14:\"saveButtonText\";s:4:\"Save\";s:14:\"nextButtonText\";s:17:\"Save and Continue\";s:16:\"finishButtonText\";s:15:\"Save and Finish\";s:23:\"printableViewButtonText\";s:14:\"Printable View\";}',1,1,'a:1:{i:0;a:11:{i:0;s:2:\"20\";i:1;s:2:\"35\";i:2;s:2:\"21\";i:3;s:2:\"22\";i:4;s:2:\"15\";i:5;s:2:\"27\";i:6;s:2:\"28\";i:7;s:2:\"29\";i:8;s:2:\"30\";i:9;s:2:\"17\";i:10;i:36;}}','a:1:{i:0;s:20:\"People in Activities\";}','a:1:{i:0;a:0:{}}',0,0,0,2,'20%','auto',0,0,0,0,0,'b:0;'),
(6,11,'','','','a:7:{s:16:\"thankyoulinktext\";s:46:\"Leave this form and continue browsing the site\";s:15:\"leaveButtonText\";s:14:\"Save and Leave\";s:14:\"prevButtonText\";s:16:\"Save and Go Back\";s:14:\"saveButtonText\";s:4:\"Save\";s:14:\"nextButtonText\";s:17:\"Save and Continue\";s:16:\"finishButtonText\";s:15:\"Save and Finish\";s:23:\"printableViewButtonText\";s:14:\"Printable View\";}',1,1,'a:1:{i:0;a:6:{i:0;s:1:\"5\";i:1;s:2:\"25\";i:2;s:2:\"26\";i:3;s:2:\"13\";i:4;s:1:\"6\";i:5;s:1:\"7\";}}','a:1:{i:0;s:12:\"Publications\";}','a:1:{i:0;a:0:{}}',0,0,1,2,'30%','auto',2,2,2,0,0,'b:0;'),
(7,12,'','','','a:7:{s:16:\"thankyoulinktext\";s:46:\"Leave this form and continue browsing the site\";s:15:\"leaveButtonText\";s:14:\"Save and Leave\";s:14:\"prevButtonText\";s:16:\"Save and Go Back\";s:14:\"saveButtonText\";s:4:\"Save\";s:14:\"nextButtonText\";s:17:\"Save and Continue\";s:16:\"finishButtonText\";s:15:\"Save and Finish\";s:23:\"printableViewButtonText\";s:14:\"Printable View\";}',1,1,'a:3:{i:0;a:6:{i:0;s:2:\"11\";i:1;s:2:\"10\";i:2;s:2:\"37\";i:3;s:2:\"38\";i:4;s:2:\"40\";i:5;s:2:\"12\";}i:1;a:1:{i:0;s:2:\"14\";}i:2;a:1:{i:0;s:2:\"19\";}}','a:3:{i:0;s:10:\"Activities\";i:1;s:12:\"Publications\";i:2;s:6:\"People\";}','a:3:{i:0;a:0:{}i:1;a:0:{}i:2;a:0:{}}',0,0,1,2,'30%','auto',2,2,2,0,0,'b:0;'),
(8,13,'','','','a:7:{s:16:\"thankyoulinktext\";s:46:\"Leave this form and continue browsing the site\";s:15:\"leaveButtonText\";s:14:\"Save and Leave\";s:14:\"prevButtonText\";s:16:\"Save and Go Back\";s:14:\"saveButtonText\";s:4:\"Save\";s:14:\"nextButtonText\";s:17:\"Save and Continue\";s:16:\"finishButtonText\";s:15:\"Save and Finish\";s:23:\"printableViewButtonText\";s:14:\"Printable View\";}',1,1,'a:1:{i:0;a:6:{i:0;s:1:\"5\";i:1;s:2:\"25\";i:2;s:2:\"26\";i:3;s:1:\"4\";i:4;s:1:\"6\";i:5;s:1:\"7\";}}','a:1:{i:0;s:12:\"Publications\";}','a:1:{i:0;a:0:{}}',0,0,1,2,'30%','auto',2,2,2,0,0,'b:0;'),
(9,14,'','','','a:7:{s:16:\"thankyoulinktext\";s:46:\"Leave this form and continue browsing the site\";s:15:\"leaveButtonText\";s:14:\"Save and Leave\";s:14:\"prevButtonText\";s:16:\"Save and Go Back\";s:14:\"saveButtonText\";s:4:\"Save\";s:14:\"nextButtonText\";s:17:\"Save and Continue\";s:16:\"finishButtonText\";s:15:\"Save and Finish\";s:23:\"printableViewButtonText\";s:14:\"Printable View\";}',1,1,'a:1:{i:0;a:6:{i:0;s:2:\"15\";i:1;s:2:\"27\";i:2;s:2:\"28\";i:3;s:2:\"29\";i:4;s:2:\"30\";i:5;s:2:\"17\";}}','a:1:{i:0;s:6:\"People\";}','a:1:{i:0;a:0:{}}',0,0,1,2,'20%','auto',2,2,2,0,0,'b:0;'),
(10,15,'','','','a:7:{s:16:\"thankyoulinktext\";s:0:\"\";s:15:\"leaveButtonText\";s:14:\"Save and Leave\";s:14:\"prevButtonText\";s:16:\"Save and Go Back\";s:14:\"saveButtonText\";s:4:\"Save\";s:14:\"nextButtonText\";s:17:\"Save and Continue\";s:16:\"finishButtonText\";s:15:\"Save and Finish\";s:23:\"printableViewButtonText\";s:14:\"Printable View\";}',1,1,'a:1:{i:0;a:2:{i:0;i:33;i:1;i:34;}}','a:1:{i:0;s:5:\"Pages\";}','b:0;',0,0,0,2,'20%','auto',0,0,0,0,0,'b:0;');
/*!40000 ALTER TABLE `ai8k7Bba_formulize_screen_multipage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_screen_template`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_screen_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_screen_template` (
  `templateid` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL DEFAULT 0,
  `custom_code` text NOT NULL,
  `donedest` varchar(255) NOT NULL DEFAULT '',
  `savebuttontext` varchar(255) NOT NULL DEFAULT '',
  `donebuttontext` varchar(255) NOT NULL DEFAULT '',
  `template` text NOT NULL,
  PRIMARY KEY (`templateid`),
  KEY `i_sid` (`sid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_screen_template`
--

LOCK TABLES `ai8k7Bba_formulize_screen_template` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_screen_template` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_formulize_screen_template` VALUES
(1,18,'','','Save','Save and Leave','&lt;h1&gt;&lt;{$activities_activity_name}&gt;&lt;/h1&gt;');
/*!40000 ALTER TABLE `ai8k7Bba_formulize_screen_template` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_tokens`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_tokens` (
  `key_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `groups` varchar(255) NOT NULL DEFAULT '',
  `tokenkey` varchar(255) NOT NULL DEFAULT '',
  `expiry` datetime DEFAULT NULL,
  `maxuses` int(11) NOT NULL DEFAULT 0,
  `currentuses` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`key_id`),
  KEY `i_groups` (`groups`),
  KEY `i_tokenkey` (`tokenkey`),
  KEY `i_expiry` (`expiry`),
  KEY `i_maxuses` (`maxuses`),
  KEY `i_currentuses` (`currentuses`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_tokens`
--

LOCK TABLES `ai8k7Bba_formulize_tokens` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_topics`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_topics` (
  `entry_id` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `creation_datetime` datetime DEFAULT NULL,
  `mod_datetime` datetime DEFAULT NULL,
  `creation_uid` int(7) DEFAULT 0,
  `mod_uid` int(7) DEFAULT 0,
  `topics_topic_name` text DEFAULT NULL,
  `topics_outline` text DEFAULT NULL,
  `topics_details` text DEFAULT NULL,
  PRIMARY KEY (`entry_id`),
  KEY `i_creation_uid` (`creation_uid`),
  FULLTEXT KEY `topics_topic_name` (`topics_topic_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_topics`
--

LOCK TABLES `ai8k7Bba_formulize_topics` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_topics` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_formulize_topics` VALUES
(1,'2024-01-25 08:17:40','2024-01-25 08:17:40',2,2,'Constitutional Issues','We love the constitution','We really really love the constitution');
/*!40000 ALTER TABLE `ai8k7Bba_formulize_topics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_formulize_valid_imports`
--

DROP TABLE IF EXISTS `ai8k7Bba_formulize_valid_imports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_formulize_valid_imports` (
  `import_id` smallint(5) NOT NULL AUTO_INCREMENT,
  `file` varchar(255) NOT NULL DEFAULT '',
  `id_reqs` text NOT NULL,
  `fid` int(5) DEFAULT NULL,
  PRIMARY KEY (`import_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_formulize_valid_imports`
--

LOCK TABLES `ai8k7Bba_formulize_valid_imports` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_valid_imports` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_formulize_valid_imports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_group_lists`
--

DROP TABLE IF EXISTS `ai8k7Bba_group_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_group_lists` (
  `gl_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `gl_name` varchar(250) NOT NULL DEFAULT '',
  `gl_groups` text NOT NULL,
  PRIMARY KEY (`gl_id`),
  UNIQUE KEY `gl_name_id` (`gl_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_group_lists`
--

LOCK TABLES `ai8k7Bba_group_lists` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_group_lists` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_group_lists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_group_permission`
--

DROP TABLE IF EXISTS `ai8k7Bba_group_permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_group_permission` (
  `gperm_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `gperm_groupid` smallint(5) unsigned NOT NULL DEFAULT 0,
  `gperm_itemid` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `gperm_modid` smallint(5) unsigned NOT NULL DEFAULT 0,
  `gperm_name` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`gperm_id`),
  KEY `name_mod_group` (`gperm_name`(10),`gperm_modid`,`gperm_groupid`)
) ENGINE=InnoDB AUTO_INCREMENT=204 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_group_permission`
--

LOCK TABLES `ai8k7Bba_group_permission` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_group_permission` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_group_permission` VALUES
(1,2,20,1,'use_extension'),
(2,1,20,1,'use_extension'),
(3,2,19,1,'use_extension'),
(4,1,19,1,'use_extension'),
(5,2,76,1,'use_extension'),
(6,1,76,1,'use_extension'),
(7,2,77,1,'use_extension'),
(8,1,77,1,'use_extension'),
(9,2,82,1,'use_extension'),
(10,1,82,1,'use_extension'),
(11,2,79,1,'use_extension'),
(12,1,79,1,'use_extension'),
(13,2,80,1,'use_extension'),
(14,1,80,1,'use_extension'),
(15,2,81,1,'use_extension'),
(16,1,81,1,'use_extension'),
(17,2,83,1,'use_extension'),
(18,1,83,1,'use_extension'),
(19,2,84,1,'use_extension'),
(20,1,84,1,'use_extension'),
(21,2,100,1,'use_extension'),
(22,1,100,1,'use_extension'),
(23,2,101,1,'use_extension'),
(24,1,101,1,'use_extension'),
(25,1,1,1,'module_admin'),
(26,1,1,1,'module_read'),
(28,3,1,1,'module_read'),
(29,1,1,1,'system_admin'),
(30,1,2,1,'system_admin'),
(31,1,3,1,'system_admin'),
(32,1,4,1,'system_admin'),
(33,1,5,1,'system_admin'),
(34,1,6,1,'system_admin'),
(35,1,7,1,'system_admin'),
(36,1,8,1,'system_admin'),
(37,1,9,1,'system_admin'),
(38,1,10,1,'system_admin'),
(39,1,11,1,'system_admin'),
(40,1,12,1,'system_admin'),
(41,1,13,1,'system_admin'),
(42,1,14,1,'system_admin'),
(43,1,15,1,'system_admin'),
(44,1,16,1,'system_admin'),
(45,1,17,1,'system_admin'),
(46,1,18,1,'system_admin'),
(47,1,19,1,'system_admin'),
(48,1,20,1,'system_admin'),
(49,1,1,1,'group_manager'),
(50,1,2,1,'group_manager'),
(51,1,3,1,'group_manager'),
(52,1,1,1,'content_read'),
(53,2,1,1,'content_read'),
(54,3,1,1,'content_read'),
(55,1,1,1,'content_admin'),
(56,1,1,1,'use_wysiwygeditor'),
(57,1,1,1,'imgcat_write'),
(58,1,1,1,'imgcat_read'),
(59,1,1,1,'block_read'),
(61,3,1,1,'block_read'),
(62,1,2,1,'block_read'),
(64,3,2,1,'block_read'),
(65,1,3,1,'block_read'),
(67,3,3,1,'block_read'),
(68,1,4,1,'block_read'),
(70,3,4,1,'block_read'),
(71,1,5,1,'block_read'),
(73,3,5,1,'block_read'),
(74,1,6,1,'block_read'),
(76,3,6,1,'block_read'),
(77,1,7,1,'block_read'),
(79,3,7,1,'block_read'),
(80,1,8,1,'block_read'),
(82,3,8,1,'block_read'),
(83,1,9,1,'block_read'),
(85,3,9,1,'block_read'),
(86,1,10,1,'block_read'),
(88,3,10,1,'block_read'),
(89,1,11,1,'block_read'),
(91,3,11,1,'block_read'),
(92,1,12,1,'block_read'),
(94,3,12,1,'block_read'),
(95,1,13,1,'block_read'),
(97,3,13,1,'block_read'),
(98,1,14,1,'block_read'),
(100,3,14,1,'block_read'),
(101,1,15,1,'block_read'),
(103,3,15,1,'block_read'),
(104,1,16,1,'block_read'),
(106,3,16,1,'block_read'),
(107,1,17,1,'block_read'),
(109,3,17,1,'block_read'),
(110,1,18,1,'block_read'),
(112,3,18,1,'block_read'),
(113,1,19,1,'block_read'),
(115,3,19,1,'block_read'),
(118,1,2,1,'module_admin'),
(119,1,2,1,'module_read'),
(120,1,1,1,'block_read'),
(121,1,1,1,'block_read'),
(125,3,2,1,'module_read'),
(126,3,1,1,'block_read'),
(127,3,1,1,'block_read'),
(128,1,3,1,'module_admin'),
(129,1,3,1,'module_read'),
(130,1,1,1,'block_read'),
(133,3,3,1,'module_read'),
(134,3,1,1,'block_read'),
(135,1,4,1,'module_admin'),
(136,1,4,1,'module_read'),
(137,1,1,1,'block_read'),
(138,1,1,1,'block_read'),
(142,3,4,1,'module_read'),
(143,3,1,1,'block_read'),
(144,3,1,1,'block_read'),
(145,1,5,1,'module_admin'),
(146,1,5,1,'module_read'),
(147,3,21,1,'block_read'),
(148,1,20,1,'block_read'),
(149,2,2,1,'module_read'),
(150,2,3,1,'module_read'),
(151,2,4,1,'module_read'),
(152,2,1,1,'module_read'),
(153,2,1,1,'block_read'),
(154,2,2,1,'block_read'),
(155,2,5,1,'block_read'),
(156,2,6,1,'block_read'),
(157,2,10,1,'block_read'),
(158,2,11,1,'block_read'),
(159,2,12,1,'block_read'),
(160,2,13,1,'block_read'),
(161,2,18,1,'block_read'),
(162,2,19,1,'block_read'),
(164,2,3,1,'block_read'),
(165,2,14,1,'block_read'),
(166,2,4,1,'block_read'),
(167,2,7,1,'block_read'),
(168,2,8,1,'block_read'),
(169,2,9,1,'block_read'),
(170,2,16,1,'block_read'),
(171,2,15,1,'block_read'),
(172,2,17,1,'block_read'),
(179,2,18,2,'profile_edit'),
(180,2,19,2,'profile_edit'),
(181,2,29,2,'profile_edit'),
(182,2,30,2,'profile_edit'),
(183,2,32,2,'profile_edit'),
(184,1,1,4,'edit_form'),
(185,1,2,4,'edit_form'),
(186,1,3,4,'edit_form'),
(187,1,4,4,'edit_form'),
(188,1,5,4,'edit_form'),
(189,1,6,4,'edit_form'),
(190,3,3,4,'view_form'),
(191,3,3,4,'view_globalscope'),
(192,3,4,4,'view_form'),
(193,3,4,4,'view_globalscope'),
(194,3,5,4,'view_form'),
(195,3,5,4,'view_globalscope'),
(196,3,2,4,'view_form'),
(197,3,2,4,'view_globalscope'),
(198,3,1,4,'view_form'),
(199,3,1,4,'view_globalscope'),
(200,3,6,4,'view_form'),
(201,3,6,4,'view_globalscope'),
(202,3,24,1,'block_read'),
(203,2,24,1,'block_read');
/*!40000 ALTER TABLE `ai8k7Bba_group_permission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_groups`
--

DROP TABLE IF EXISTS `ai8k7Bba_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_groups` (
  `groupid` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `group_type` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`groupid`),
  KEY `group_type` (`group_type`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_groups`
--

LOCK TABLES `ai8k7Bba_groups` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_groups` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_groups` VALUES
(1,'Webmasters','Webmasters of this site','Admin'),
(2,'Registered Users','Registered Users Group','User'),
(3,'Anonymous Users','Anonymous Users Group','Anonymous');
/*!40000 ALTER TABLE `ai8k7Bba_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_groups_users_link`
--

DROP TABLE IF EXISTS `ai8k7Bba_groups_users_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_groups_users_link` (
  `linkid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `groupid` smallint(5) unsigned NOT NULL DEFAULT 0,
  `uid` mediumint(8) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`linkid`),
  KEY `groupid_uid` (`groupid`,`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_groups_users_link`
--

LOCK TABLES `ai8k7Bba_groups_users_link` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_groups_users_link` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_groups_users_link` VALUES
(1,1,1),
(3,1,2),
(2,2,1),
(4,2,2);
/*!40000 ALTER TABLE `ai8k7Bba_groups_users_link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_icms_data_file`
--

DROP TABLE IF EXISTS `ai8k7Bba_icms_data_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_icms_data_file` (
  `fileid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mid` smallint(5) unsigned NOT NULL,
  `caption` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  PRIMARY KEY (`fileid`),
  KEY `mid` (`mid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_icms_data_file`
--

LOCK TABLES `ai8k7Bba_icms_data_file` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_icms_data_file` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_icms_data_file` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_icms_data_urllink`
--

DROP TABLE IF EXISTS `ai8k7Bba_icms_data_urllink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_icms_data_urllink` (
  `urllinkid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mid` smallint(5) unsigned NOT NULL,
  `caption` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `target` varchar(6) NOT NULL,
  PRIMARY KEY (`urllinkid`),
  KEY `mid` (`mid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_icms_data_urllink`
--

LOCK TABLES `ai8k7Bba_icms_data_urllink` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_icms_data_urllink` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_icms_data_urllink` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_icmspage`
--

DROP TABLE IF EXISTS `ai8k7Bba_icmspage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_icmspage` (
  `page_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `page_moduleid` mediumint(8) unsigned NOT NULL DEFAULT 1,
  `page_title` varchar(255) NOT NULL DEFAULT '',
  `page_url` varchar(255) NOT NULL DEFAULT '',
  `page_status` tinyint(1) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`page_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_icmspage`
--

LOCK TABLES `ai8k7Bba_icmspage` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_icmspage` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_icmspage` VALUES
(2,1,'Admin Control Panel','admin.php',1),
(3,1,'Avatars','modules/system/admin.php?fct=avatars*',1),
(4,1,'Banners','modules/system/admin.php?fct=banners*',1),
(5,1,'Blocks Admin','modules/system/admin.php?fct=blocksadmin*',1),
(6,1,'Block Positions','modules/system/admin.php?fct=blockspadmin*',1),
(7,1,'Comments','modules/system/admin.php?fct=comments*',1),
(9,1,'Find Users','modules/system/admin.php?fct=findusers*',1),
(10,1,'Custom Tag','modules/system/admin.php?fct=customtag*',1),
(11,1,'Groups','modules/system/admin.php?fct=groups*',1),
(12,1,'Image Manager','modules/system/admin.php?fct=images*',1),
(13,1,'Mail Users','modules/system/admin.php?fct=mailusers*',1),
(14,1,'Modules Admin','modules/system/admin.php?fct=modulesadmin*',1),
(15,1,'Symlink Manager','modules/system/admin.php?fct=pages*',1),
(16,1,'Preferences','modules/system/admin.php?fct=preferences*',1),
(17,1,'Smilies','modules/system/admin.php?fct=smilies*',1),
(18,1,'Templates','modules/system/admin.php?fct=tplsets*',1),
(19,1,'User Ranks','modules/system/admin.php?fct=userrank*',1),
(20,1,'User Edit','modules/system/admin.php?fct=users*',1),
(21,1,'Version Checker','modules/system/admin.php?fct=version*',1);
/*!40000 ALTER TABLE `ai8k7Bba_icmspage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_image`
--

DROP TABLE IF EXISTS `ai8k7Bba_image`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_image` (
  `image_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `image_name` varchar(30) NOT NULL DEFAULT '',
  `image_nicename` varchar(255) NOT NULL DEFAULT '',
  `image_mimetype` varchar(30) NOT NULL DEFAULT '',
  `image_created` int(10) unsigned NOT NULL DEFAULT 0,
  `image_display` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `image_weight` smallint(5) unsigned NOT NULL DEFAULT 0,
  `imgcat_id` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`image_id`),
  KEY `imgcat_id` (`imgcat_id`),
  KEY `image_display` (`image_display`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_image`
--

LOCK TABLES `ai8k7Bba_image` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_image` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_image` VALUES
(1,'img482278e29e81c.png','ImpressCMS','image/png',1671220912,1,0,1);
/*!40000 ALTER TABLE `ai8k7Bba_image` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_imagebody`
--

DROP TABLE IF EXISTS `ai8k7Bba_imagebody`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_imagebody` (
  `image_id` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `image_body` mediumblob DEFAULT NULL,
  KEY `image_id` (`image_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_imagebody`
--

LOCK TABLES `ai8k7Bba_imagebody` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_imagebody` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_imagebody` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_imagecategory`
--

DROP TABLE IF EXISTS `ai8k7Bba_imagecategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_imagecategory` (
  `imgcat_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `imgcat_pid` smallint(5) unsigned NOT NULL DEFAULT 0,
  `imgcat_name` varchar(100) NOT NULL DEFAULT '',
  `imgcat_maxsize` int(8) unsigned NOT NULL DEFAULT 0,
  `imgcat_maxwidth` smallint(3) unsigned NOT NULL DEFAULT 0,
  `imgcat_maxheight` smallint(3) unsigned NOT NULL DEFAULT 0,
  `imgcat_display` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `imgcat_weight` smallint(3) unsigned NOT NULL DEFAULT 0,
  `imgcat_type` char(1) NOT NULL DEFAULT '',
  `imgcat_storetype` varchar(5) NOT NULL DEFAULT '',
  `imgcat_foldername` varchar(100) DEFAULT '',
  PRIMARY KEY (`imgcat_id`),
  KEY `imgcat_display` (`imgcat_display`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_imagecategory`
--

LOCK TABLES `ai8k7Bba_imagecategory` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_imagecategory` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_imagecategory` VALUES
(1,0,'Logos',358400,350,80,1,0,'C','file','logos');
/*!40000 ALTER TABLE `ai8k7Bba_imagecategory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_imgset`
--

DROP TABLE IF EXISTS `ai8k7Bba_imgset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_imgset` (
  `imgset_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `imgset_name` varchar(50) NOT NULL DEFAULT '',
  `imgset_refid` mediumint(8) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`imgset_id`),
  KEY `imgset_refid` (`imgset_refid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_imgset`
--

LOCK TABLES `ai8k7Bba_imgset` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_imgset` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_imgset` VALUES
(1,'default',0);
/*!40000 ALTER TABLE `ai8k7Bba_imgset` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_imgset_tplset_link`
--

DROP TABLE IF EXISTS `ai8k7Bba_imgset_tplset_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_imgset_tplset_link` (
  `imgset_id` smallint(5) unsigned NOT NULL DEFAULT 0,
  `tplset_name` varchar(50) NOT NULL DEFAULT '',
  KEY `tplset_name` (`tplset_name`(10))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_imgset_tplset_link`
--

LOCK TABLES `ai8k7Bba_imgset_tplset_link` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_imgset_tplset_link` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_imgset_tplset_link` VALUES
(1,'default');
/*!40000 ALTER TABLE `ai8k7Bba_imgset_tplset_link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_imgsetimg`
--

DROP TABLE IF EXISTS `ai8k7Bba_imgsetimg`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_imgsetimg` (
  `imgsetimg_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `imgsetimg_file` varchar(50) NOT NULL DEFAULT '',
  `imgsetimg_body` blob NOT NULL,
  `imgsetimg_imgset` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`imgsetimg_id`),
  KEY `imgsetimg_imgset` (`imgsetimg_imgset`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_imgsetimg`
--

LOCK TABLES `ai8k7Bba_imgsetimg` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_imgsetimg` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_imgsetimg` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_invites`
--

DROP TABLE IF EXISTS `ai8k7Bba_invites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_invites` (
  `invite_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `from_id` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `invite_to` varchar(255) NOT NULL DEFAULT '',
  `invite_code` varchar(8) NOT NULL DEFAULT '',
  `invite_date` int(10) unsigned NOT NULL DEFAULT 0,
  `view_date` int(10) unsigned NOT NULL DEFAULT 0,
  `register_id` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `extra_info` text NOT NULL,
  PRIMARY KEY (`invite_id`),
  KEY `invite_code` (`invite_code`),
  KEY `register_id` (`register_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_invites`
--

LOCK TABLES `ai8k7Bba_invites` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_invites` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_invites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_modules`
--

DROP TABLE IF EXISTS `ai8k7Bba_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_modules` (
  `mid` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL DEFAULT '',
  `version` smallint(5) unsigned NOT NULL DEFAULT 102,
  `last_update` int(10) unsigned NOT NULL DEFAULT 0,
  `weight` smallint(3) unsigned NOT NULL DEFAULT 0,
  `isactive` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `dirname` varchar(25) NOT NULL DEFAULT '',
  `hasmain` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `hasadmin` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `hassearch` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `hasconfig` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `hascomments` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `hasnotification` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `dbversion` int(11) unsigned NOT NULL DEFAULT 1,
  `modname` varchar(25) NOT NULL DEFAULT '',
  `ipf` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`mid`),
  KEY `dirname` (`dirname`(5)),
  KEY `active_main_weight` (`isactive`,`hasmain`,`weight`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_modules`
--

LOCK TABLES `ai8k7Bba_modules` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_modules` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_modules` VALUES
(1,'System',130,1671220918,0,1,'system',0,1,0,0,0,0,41,'system',0),
(2,'Profile',200,1671220918,1,1,'profile',1,1,1,1,1,1,2,'profile',1),
(3,'Content',110,1671220918,1,1,'content',1,1,1,1,1,1,1,'content',1),
(4,'Forms',710,1671220918,1,1,'formulize',1,1,0,1,0,1,0,'',0),
(5,'Protector',350,1671220919,1,1,'protector',0,1,0,1,0,0,0,'',0);
/*!40000 ALTER TABLE `ai8k7Bba_modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_newblocks`
--

DROP TABLE IF EXISTS `ai8k7Bba_newblocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_newblocks` (
  `bid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `mid` smallint(5) unsigned NOT NULL DEFAULT 0,
  `func_num` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `options` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(150) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `side` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `weight` smallint(5) unsigned NOT NULL DEFAULT 0,
  `visible` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `block_type` char(1) NOT NULL DEFAULT '',
  `c_type` char(1) NOT NULL DEFAULT '',
  `isactive` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `dirname` varchar(50) NOT NULL DEFAULT '',
  `func_file` varchar(50) NOT NULL DEFAULT '',
  `show_func` varchar(50) NOT NULL DEFAULT '',
  `edit_func` varchar(50) NOT NULL DEFAULT '',
  `template` varchar(50) NOT NULL DEFAULT '',
  `bcachetime` int(10) unsigned NOT NULL DEFAULT 0,
  `last_modified` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`bid`),
  KEY `mid` (`mid`),
  KEY `visible` (`visible`),
  KEY `isactive_visible_mid` (`isactive`,`visible`,`mid`),
  KEY `mid_funcnum` (`mid`,`func_num`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_newblocks`
--

LOCK TABLES `ai8k7Bba_newblocks` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_newblocks` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_newblocks` VALUES
(1,1,1,'','User Menu','User Menu','',1,0,0,'S','H',1,'system','system_blocks.php','b_system_user_show','','system_block_user.html',0,1324237597),
(2,1,2,'','Login','','',1,0,0,'S','H',1,'system','system_blocks.php','b_system_login_show','','system_block_login.html',0,1707503916),
(3,1,3,'','Search','Search','',2,0,0,'S','H',1,'system','system_blocks.php','b_system_search_show','','system_block_search.html',0,1324230443),
(4,1,4,'1|5','Waiting Contents','Waiting Contents','',9,0,0,'S','H',1,'system','system_waiting.php','b_system_waiting_show','b_system_waiting_edit','system_block_waiting.html',0,1324230522),
(5,1,5,'','Main Menu','Main Menu','',1,0,0,'S','H',1,'system','system_blocks.php','b_system_main_show','','system_block_mainmenu.html',0,1324230437),
(6,1,6,'320|190|s_poweredby.gif|1','Site Info','Site Info','',1,0,0,'S','H',1,'system','system_blocks.php','b_system_info_show','b_system_info_edit','system_block_siteinfo.html',0,1324009012),
(7,1,7,'','Who\'s Online','Who\'s Online','',9,0,1,'S','H',1,'system','system_blocks.php','b_system_online_show','','system_block_online.html',0,1324230491),
(8,1,8,'10|1','Top Posters','Top Posters','',10,0,0,'S','H',1,'system','system_blocks.php','b_system_topposters_show','b_system_topposters_edit','system_block_topusers.html',0,1324230527),
(9,1,9,'10|1|1','New Members','New Members','',10,0,0,'S','H',1,'system','system_blocks.php','b_system_newmembers_show','b_system_newmembers_edit','system_block_newusers.html',0,1324230441),
(10,1,10,'10','Recent Comments','Recent Comments','',1,0,0,'S','H',1,'system','system_blocks.php','b_system_comments_show','b_system_comments_edit','system_block_comments.html',0,1324009012),
(11,1,11,'','Notification Options','Notification Options','',1,0,0,'S','H',1,'system','system_blocks.php','b_system_notification_show','','system_block_notification.html',0,1324009012),
(12,1,12,'0|80','Themes','Themes','',1,0,0,'S','H',1,'system','system_blocks.php','b_system_themes_show','b_system_themes_edit','system_block_themes.html',0,1324230465),
(13,1,13,'','Language Selection','Language Selection','',1,0,0,'S','H',1,'system','system_blocks.php','b_system_multilanguage_show','','system_block_multilanguage.html',0,1324009012),
(14,1,14,'1|1|1|1|0|0|0|0|0|0|0|0|0|1|0|0|0|0|1|0|1|0|0|1|0|0|0|0|0|0|0|0','Share this page!','Share this page!','',7,0,0,'S','H',1,'system','system_blocks.php','b_system_social_show','b_system_social_edit','system_block_socialbookmark.html',0,1324230450),
(15,1,15,'','System Warnings','System Warnings','',12,0,1,'S','H',1,'system','system_admin_blocks.php','b_system_admin_warnings_show','','system_admin_block_warnings.html',0,1324009012),
(16,1,16,'','Control Panel','Control Panel','',11,0,1,'S','H',1,'system','system_admin_blocks.php','b_system_admin_cp_show','','system_admin_block_cp.html',0,1324009012),
(17,1,17,'','Installed Modules','Installed Modules','',13,0,1,'S','H',1,'system','system_admin_blocks.php','b_system_admin_modules_show','','system_admin_block_modules.html',0,1324009012),
(18,1,18,'','My Bookmarks','My Bookmarks','',1,0,0,'S','H',1,'system','system_blocks.php','b_system_bookmarks_show','','system_block_bookmarks.html',0,1324009012),
(19,1,19,'','New Control Panel','New Control Panel','',1,0,0,'S','H',1,'system','system_admin_blocks.php','b_system_admin_cp_new_show','','system_admin_block_cp_new.html',0,1324009012),
(20,0,0,'','Custom Block (Auto Format + smilies)','Welcome!','',4,0,1,'C','S',1,'','','','','',0,1324230663),
(21,0,0,'','Custom Block (Auto Format + smilies)','Welcome!','Please login on the left.',4,0,1,'C','S',1,'','','','','',0,1324230576),
(22,2,0,'0|1|1|1','Content','Content','',1,0,0,'M','H',1,'content','content_display.php','content_content_display_show','content_content_display_edit','content_content_display.html',0,1324009074),
(23,2,1,'content_title|ASC|1|#59ADDB|0','Content Menu','Content Menu','',1,0,0,'M','H',1,'content','content_menu.php','content_content_menu_show','content_content_menu_edit','content_content_menu.html',0,1324009074),
(24,3,1,'','Form Menu','','',1,0,1,'M','H',1,'formulize','mymenu.php','block_formulizeMENU_show','','menu_controller.html',0,1707503992),
(25,4,1,'5','My friends','My friends','',1,0,0,'M','H',1,'profile','blocks.php','b_profile_friends_show','b_profile_friends_edit','profile_block_friends.html',0,1324009084),
(26,4,2,'','User Menu','User Menu','',1,0,0,'M','H',1,'profile','blocks.php','b_profile_usermenu_show','','profile_block_usermenu.html',0,1324009084);
/*!40000 ALTER TABLE `ai8k7Bba_newblocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_online`
--

DROP TABLE IF EXISTS `ai8k7Bba_online`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_online` (
  `online_uid` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `online_uname` varchar(25) NOT NULL DEFAULT '',
  `online_updated` int(10) unsigned NOT NULL DEFAULT 0,
  `online_module` smallint(5) unsigned NOT NULL DEFAULT 0,
  `online_ip` varchar(15) NOT NULL DEFAULT '',
  KEY `online_module` (`online_module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_online`
--

LOCK TABLES `ai8k7Bba_online` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_online` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_online` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_priv_msgs`
--

DROP TABLE IF EXISTS `ai8k7Bba_priv_msgs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_priv_msgs` (
  `msg_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `msg_image` varchar(100) DEFAULT NULL,
  `subject` varchar(255) NOT NULL DEFAULT '',
  `from_userid` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `to_userid` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `msg_time` int(10) unsigned NOT NULL DEFAULT 0,
  `msg_text` text NOT NULL,
  `read_msg` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`msg_id`),
  KEY `touseridreadmsg` (`to_userid`,`read_msg`),
  KEY `msgidfromuserid` (`msg_id`,`from_userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_priv_msgs`
--

LOCK TABLES `ai8k7Bba_priv_msgs` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_priv_msgs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_priv_msgs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_profile_audio`
--

DROP TABLE IF EXISTS `ai8k7Bba_profile_audio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_profile_audio` (
  `audio_id` int(11) NOT NULL AUTO_INCREMENT,
  `author` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `uid_owner` int(11) NOT NULL DEFAULT 0,
  `creation_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`audio_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_profile_audio`
--

LOCK TABLES `ai8k7Bba_profile_audio` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_profile_audio` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_profile_audio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_profile_category`
--

DROP TABLE IF EXISTS `ai8k7Bba_profile_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_profile_category` (
  `catid` int(11) NOT NULL AUTO_INCREMENT,
  `cat_title` varchar(255) NOT NULL DEFAULT '',
  `cat_description` text NOT NULL,
  `cat_weight` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`catid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_profile_category`
--

LOCK TABLES `ai8k7Bba_profile_category` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_profile_category` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_profile_category` VALUES
(1,'Personal','','1'),
(2,'Messaging','','3'),
(3,'Settings','','4'),
(4,'Community','','2');
/*!40000 ALTER TABLE `ai8k7Bba_profile_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_profile_configs`
--

DROP TABLE IF EXISTS `ai8k7Bba_profile_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_profile_configs` (
  `configs_id` int(11) NOT NULL AUTO_INCREMENT,
  `config_uid` int(11) NOT NULL DEFAULT 0,
  `pictures` int(11) NOT NULL DEFAULT 2,
  `audio` int(11) NOT NULL DEFAULT 2,
  `videos` int(11) NOT NULL DEFAULT 2,
  `friendship` int(11) NOT NULL DEFAULT 2,
  `tribes` int(11) NOT NULL DEFAULT 2,
  `profile_usercontributions` int(11) NOT NULL DEFAULT 2,
  `suspension` int(11) NOT NULL DEFAULT 0,
  `backup_password` text NOT NULL,
  `backup_email` varchar(255) NOT NULL DEFAULT '',
  `backup_sig` text NOT NULL,
  `end_suspension` int(11) NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`configs_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_profile_configs`
--

LOCK TABLES `ai8k7Bba_profile_configs` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_profile_configs` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_profile_configs` VALUES
(1,1,2,2,2,2,2,2,0,'','','',0,'');
/*!40000 ALTER TABLE `ai8k7Bba_profile_configs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_profile_field`
--

DROP TABLE IF EXISTS `ai8k7Bba_profile_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_profile_field` (
  `fieldid` int(11) NOT NULL AUTO_INCREMENT,
  `catid` int(11) NOT NULL DEFAULT 0,
  `field_type` varchar(255) NOT NULL DEFAULT '',
  `field_valuetype` varchar(255) NOT NULL DEFAULT '',
  `field_name` varchar(255) NOT NULL DEFAULT '',
  `field_title` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `field_description` text NOT NULL,
  `field_required` int(11) NOT NULL DEFAULT 0,
  `field_maxlength` varchar(255) NOT NULL DEFAULT '',
  `field_weight` int(11) NOT NULL DEFAULT 0,
  `field_default` text NOT NULL,
  `field_notnull` int(11) NOT NULL DEFAULT 0,
  `field_edit` int(11) NOT NULL DEFAULT 0,
  `field_show` int(11) NOT NULL DEFAULT 0,
  `field_options` varchar(255) NOT NULL DEFAULT '',
  `exportable` int(11) NOT NULL DEFAULT 0,
  `step_id` int(11) NOT NULL DEFAULT 0,
  `system` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`fieldid`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_profile_field`
--

LOCK TABLES `ai8k7Bba_profile_field` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_profile_field` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_profile_field` VALUES
(1,1,'textbox','1','name','Real Name','username.gif','',0,'255',1,'',1,1,0,'a:0:{}',1,0,1),
(2,1,'location','1','user_from','Location','house.gif','',0,'255',2,'',1,1,0,'a:0:{}',1,2,1),
(3,1,'textbox','1','user_occ','Occupation','occ.gif','',0,'255',3,'',1,1,0,'a:0:{}',1,2,1),
(4,1,'textbox','1','user_intrest','Interest','interests.gif','',0,'255',4,'',1,1,0,'a:0:{}',1,2,1),
(5,1,'textarea','2','bio','Extra Info','bio.gif','',0,'0',5,'',1,1,0,'a:0:{}',1,2,1),
(6,2,'textbox','1','user_aim','AIM','aim.gif','',0,'255',1,'',1,1,0,'a:0:{}',1,2,1),
(7,2,'textbox','1','user_icq','ICQ','icq.gif','',0,'255',2,'',1,1,0,'a:0:{}',1,2,1),
(8,2,'textbox','1','user_msnm','MSNM','msnm.gif','',0,'255',3,'',1,1,0,'a:0:{}',1,2,1),
(9,2,'textbox','1','user_yim','YIM','ym.gif','',0,'255',4,'',1,1,0,'a:0:{}',1,2,1),
(10,3,'yesno','3','user_viewemail','Allow other users to view my email address','','',0,'1',1,'',1,1,0,'a:0:{}',1,0,1),
(11,3,'yesno','3','attachsig','Always attach my signature','','',0,'1',2,'',1,1,0,'a:0:{}',1,0,1),
(12,3,'yesno','3','user_mailok','Receive occasional email notices from administrators and moderators?','','',0,'1',3,'',1,1,0,'a:0:{}',1,0,1),
(13,3,'theme','1','theme','Default Theme','','',0,'0',4,'',1,1,0,'a:0:{}',1,0,1),
(14,3,'language','1','language','Default Language','','',0,'0',5,'',1,1,0,'a:0:{}',1,0,1),
(15,3,'select','3','umode','Comments Display Mode','','',0,'0',6,'',1,1,0,'a:3:{s:4:\"nest\";s:6:\"Nested\";s:4:\"flat\";s:4:\"Flat\";s:6:\"thread\";s:8:\"Threaded\";}',1,0,1),
(16,3,'select','3','uorder','Comments Sort Order','','',0,'0',7,'',1,1,0,'a:2:{i:0;s:12:\"Oldest First\";i:1;s:12:\"Newest First\";}',1,0,1),
(17,3,'select','3','notify_mode','Default Notification Mode','','',0,'0',8,'',1,1,0,'a:3:{i:0;s:33:\"Notify me of all selected updates\";i:1;s:19:\"Notify me only once\";i:2;s:48:\"Notify me once then disable until I log in again\";}',1,0,1),
(18,3,'select','3','notify_method','Notification Method: When you monitor e.g. a forum, how would you like to receive notifications of updates?','','',0,'0',9,'',1,1,1,'a:3:{i:0;s:19:\"Temporarily Disable\";i:1;s:15:\"Private Message\";i:2;s:33:\"Email (use address in my profile)\";}',1,1,1),
(19,3,'timezone','1','timezone_offset','Time Zone','','',0,'0',10,'',1,1,1,'a:0:{}',1,1,1),
(20,3,'yesno','3','user_viewoid','Allow other users to view my OpenID','','',0,'1',11,'',1,0,0,'a:0:{}',1,0,1),
(21,4,'url','1','url','Website','url.gif','',0,'255',1,'',1,1,0,'a:0:{}',1,0,1),
(22,4,'textbox','3','posts','Comments/Posts','comments.gif','',0,'255',2,'',1,0,0,'a:0:{}',1,0,1),
(23,4,'rank','3','rank','Rank','rank.gif','',0,'0',3,'',1,1,0,'a:0:{}',1,0,1),
(24,4,'datetime','3','user_regdate','Member Since','birthday.gif','',0,'10',4,'0',1,0,1,'a:0:{}',1,0,1),
(25,4,'datetime','3','last_login','Last Login','clock.gif','',0,'10',5,'0',1,0,1,'a:0:{}',1,0,1),
(26,4,'openid','1','openid','OpenID','openid.gif','',0,'255',6,'',1,0,0,'a:0:{}',1,0,1),
(27,4,'dhtml','2','user_sig','Signature','signature.gif','',0,'0',7,'',1,1,0,'a:0:{}',1,0,1),
(28,2,'email','1','email','Email','email.gif','',1,'255',5,'',1,0,1,'a:0:{}',1,1,1),
(29,0,'select','3','2famethod','2-factor authentication method','','',0,'0',7,'',1,1,1,'a:4:{i:0;s:8:\"--None--\";i:1;s:14:\"Text me a code\";i:2;s:15:\"Email me a code\";i:3;s:24:\"Use an authenticator app\";}',1,1,1),
(30,0,'textbox','1','2faphone','Phone Number','','',0,'255',8,'',1,1,1,'a:0:{}',1,2,1),
(31,0,'textarea','2','2fadevices','Devices','','',0,'0',9,'',1,1,0,'a:0:{}',1,2,1),
(32,0,'textbox','1','fontsize','Font Size','','',0,'255',7,'',1,1,1,'',1,1,1);
/*!40000 ALTER TABLE `ai8k7Bba_profile_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_profile_friendship`
--

DROP TABLE IF EXISTS `ai8k7Bba_profile_friendship`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_profile_friendship` (
  `friendship_id` int(11) NOT NULL AUTO_INCREMENT,
  `friend1_uid` int(11) NOT NULL DEFAULT 0,
  `friend2_uid` int(11) NOT NULL DEFAULT 0,
  `creation_time` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`friendship_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_profile_friendship`
--

LOCK TABLES `ai8k7Bba_profile_friendship` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_profile_friendship` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_profile_friendship` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_profile_pictures`
--

DROP TABLE IF EXISTS `ai8k7Bba_profile_pictures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_profile_pictures` (
  `pictures_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `creation_time` int(11) NOT NULL DEFAULT 0,
  `uid_owner` int(11) NOT NULL DEFAULT 0,
  `url` varchar(255) NOT NULL DEFAULT '',
  `private` varchar(255) NOT NULL DEFAULT '',
  `counter` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`pictures_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_profile_pictures`
--

LOCK TABLES `ai8k7Bba_profile_pictures` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_profile_pictures` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_profile_pictures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_profile_profile`
--

DROP TABLE IF EXISTS `ai8k7Bba_profile_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_profile_profile` (
  `profileid` int(11) NOT NULL AUTO_INCREMENT,
  `newemail` varchar(255) NOT NULL DEFAULT '',
  `2famethod` int(11) DEFAULT NULL,
  `2faphone` varchar(15) DEFAULT NULL,
  `2fadevices` text DEFAULT NULL,
  `fontsize` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`profileid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_profile_profile`
--

LOCK TABLES `ai8k7Bba_profile_profile` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_profile_profile` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_profile_profile` VALUES
(1,'',0,'','',''),
(2,'',0,'','','');
/*!40000 ALTER TABLE `ai8k7Bba_profile_profile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_profile_regstep`
--

DROP TABLE IF EXISTS `ai8k7Bba_profile_regstep`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_profile_regstep` (
  `step_id` int(11) NOT NULL AUTO_INCREMENT,
  `step_name` varchar(255) NOT NULL DEFAULT '',
  `step_intro` text NOT NULL,
  `step_order` varchar(255) NOT NULL DEFAULT '',
  `step_save` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`step_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_profile_regstep`
--

LOCK TABLES `ai8k7Bba_profile_regstep` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_profile_regstep` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_profile_regstep` VALUES
(1,'Basic information','','1','0'),
(2,'Complementary information','','2','1');
/*!40000 ALTER TABLE `ai8k7Bba_profile_regstep` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_profile_tribepost`
--

DROP TABLE IF EXISTS `ai8k7Bba_profile_tribepost`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_profile_tribepost` (
  `post_id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL DEFAULT 0,
  `tribes_id` int(11) NOT NULL DEFAULT 0,
  `poster_uid` int(11) NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `attachsig` int(11) NOT NULL DEFAULT 1,
  `post_time` int(11) NOT NULL DEFAULT 0,
  `dohtml` int(11) NOT NULL,
  `dobr` int(11) NOT NULL,
  `doimage` int(11) NOT NULL,
  `dosmiley` int(11) NOT NULL,
  `doxcode` int(11) NOT NULL,
  `meta_keywords` text NOT NULL,
  `meta_description` text NOT NULL,
  `short_url` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_profile_tribepost`
--

LOCK TABLES `ai8k7Bba_profile_tribepost` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_profile_tribepost` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_profile_tribepost` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_profile_tribes`
--

DROP TABLE IF EXISTS `ai8k7Bba_profile_tribes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_profile_tribes` (
  `tribes_id` int(11) NOT NULL AUTO_INCREMENT,
  `uid_owner` int(11) NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `tribe_desc` text NOT NULL,
  `tribe_img` varchar(255) NOT NULL DEFAULT '',
  `creation_time` int(11) NOT NULL DEFAULT 0,
  `security` int(11) NOT NULL DEFAULT 1,
  `counter` int(11) NOT NULL DEFAULT 0,
  `dohtml` int(11) NOT NULL,
  `dobr` int(11) NOT NULL,
  `doimage` int(11) NOT NULL,
  `dosmiley` int(11) NOT NULL,
  `doxcode` int(11) NOT NULL,
  `meta_keywords` text NOT NULL,
  `meta_description` text NOT NULL,
  `short_url` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`tribes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_profile_tribes`
--

LOCK TABLES `ai8k7Bba_profile_tribes` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_profile_tribes` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_profile_tribes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_profile_tribetopic`
--

DROP TABLE IF EXISTS `ai8k7Bba_profile_tribetopic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_profile_tribetopic` (
  `topic_id` int(11) NOT NULL AUTO_INCREMENT,
  `tribes_id` int(11) NOT NULL DEFAULT 0,
  `poster_uid` int(11) NOT NULL DEFAULT 0,
  `post_id` int(11) NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `closed` int(11) NOT NULL DEFAULT 0,
  `replies` int(11) NOT NULL DEFAULT 0,
  `views` int(11) NOT NULL DEFAULT 0,
  `last_post_id` int(11) NOT NULL DEFAULT 0,
  `last_post_time` int(11) NOT NULL DEFAULT 0,
  `meta_keywords` text NOT NULL,
  `meta_description` text NOT NULL,
  `short_url` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_profile_tribetopic`
--

LOCK TABLES `ai8k7Bba_profile_tribetopic` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_profile_tribetopic` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_profile_tribetopic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_profile_tribeuser`
--

DROP TABLE IF EXISTS `ai8k7Bba_profile_tribeuser`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_profile_tribeuser` (
  `tribeuser_id` int(11) NOT NULL AUTO_INCREMENT,
  `tribe_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `approved` int(11) NOT NULL DEFAULT 1,
  `accepted` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`tribeuser_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_profile_tribeuser`
--

LOCK TABLES `ai8k7Bba_profile_tribeuser` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_profile_tribeuser` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_profile_tribeuser` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_profile_videos`
--

DROP TABLE IF EXISTS `ai8k7Bba_profile_videos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_profile_videos` (
  `videos_id` int(11) NOT NULL AUTO_INCREMENT,
  `uid_owner` int(11) NOT NULL DEFAULT 0,
  `video_title` varchar(255) NOT NULL DEFAULT '',
  `youtube_code` varchar(255) NOT NULL DEFAULT '',
  `video_desc` text NOT NULL,
  `creation_time` int(11) NOT NULL DEFAULT 0,
  `dohtml` int(11) NOT NULL,
  `dobr` int(11) NOT NULL,
  `doimage` int(11) NOT NULL,
  `dosmiley` int(11) NOT NULL,
  `doxcode` int(11) NOT NULL,
  PRIMARY KEY (`videos_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_profile_videos`
--

LOCK TABLES `ai8k7Bba_profile_videos` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_profile_videos` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_profile_videos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_profile_visibility`
--

DROP TABLE IF EXISTS `ai8k7Bba_profile_visibility`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_profile_visibility` (
  `fieldid` int(11) NOT NULL DEFAULT 0,
  `user_group` int(11) NOT NULL DEFAULT 0,
  `profile_group` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`fieldid`,`user_group`,`profile_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_profile_visibility`
--

LOCK TABLES `ai8k7Bba_profile_visibility` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_profile_visibility` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_profile_visibility` VALUES
(1,1,0),
(1,2,0),
(18,0,0),
(19,2,0),
(25,1,0),
(25,2,0),
(26,1,0),
(28,1,0),
(28,2,0),
(29,1,0),
(30,1,0),
(32,2,0);
/*!40000 ALTER TABLE `ai8k7Bba_profile_visibility` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_profile_visitors`
--

DROP TABLE IF EXISTS `ai8k7Bba_profile_visitors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_profile_visitors` (
  `visitors_id` int(11) NOT NULL AUTO_INCREMENT,
  `uid_owner` int(11) NOT NULL DEFAULT 0,
  `uid_visitor` int(11) NOT NULL DEFAULT 0,
  `visit_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`visitors_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_profile_visitors`
--

LOCK TABLES `ai8k7Bba_profile_visitors` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_profile_visitors` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_profile_visitors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_protector_access`
--

DROP TABLE IF EXISTS `ai8k7Bba_protector_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_protector_access` (
  `ip` varchar(255) NOT NULL DEFAULT '0.0.0.0',
  `request_uri` varchar(255) NOT NULL DEFAULT '',
  `malicious_actions` varchar(255) NOT NULL DEFAULT '',
  `expire` int(11) NOT NULL DEFAULT 0,
  KEY `ip` (`ip`),
  KEY `request_uri` (`request_uri`),
  KEY `malicious_actions` (`malicious_actions`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_protector_access`
--

LOCK TABLES `ai8k7Bba_protector_access` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_protector_access` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_protector_access` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_protector_log`
--

DROP TABLE IF EXISTS `ai8k7Bba_protector_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_protector_log` (
  `lid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `ip` varchar(255) NOT NULL DEFAULT '0.0.0.0',
  `type` varchar(255) NOT NULL DEFAULT '',
  `agent` varchar(255) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `extra` text DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (`lid`),
  KEY `uid` (`uid`),
  KEY `ip` (`ip`),
  KEY `type` (`type`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_protector_log`
--

LOCK TABLES `ai8k7Bba_protector_log` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_protector_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_protector_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_ranks`
--

DROP TABLE IF EXISTS `ai8k7Bba_ranks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_ranks` (
  `rank_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `rank_title` varchar(50) NOT NULL DEFAULT '',
  `rank_min` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `rank_max` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `rank_special` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `rank_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rank_id`),
  KEY `rank_max` (`rank_max`),
  KEY `rankminrankmaxranspecial` (`rank_min`,`rank_max`,`rank_special`),
  KEY `rankspecial` (`rank_special`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_ranks`
--

LOCK TABLES `ai8k7Bba_ranks` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_ranks` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_ranks` VALUES
(1,'Just popping in',0,20,0,'rank3e632f95e81ca.gif'),
(2,'Not too shy to talk',21,40,0,'rank3dbf8e94a6f72.gif'),
(3,'Quite a regular',41,70,0,'rank3dbf8e9e7d88d.gif'),
(4,'Just can not stay away',71,150,0,'rank3dbf8ea81e642.gif'),
(5,'Home away from home',151,10000,0,'rank3dbf8eb1a72e7.gif'),
(6,'Moderator',0,0,1,'rank3dbf8edf15093.gif'),
(7,'Webmaster',0,0,1,'rank3dbf8ee8681cd.gif');
/*!40000 ALTER TABLE `ai8k7Bba_ranks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_session`
--

DROP TABLE IF EXISTS `ai8k7Bba_session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_session` (
  `sess_id` varchar(60) NOT NULL,
  `sess_updated` int(10) unsigned NOT NULL DEFAULT 0,
  `sess_ip` varchar(64) NOT NULL DEFAULT '',
  `sess_data` text NOT NULL,
  PRIMARY KEY (`sess_id`),
  KEY `updated` (`sess_updated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_session`
--

LOCK TABLES `ai8k7Bba_session` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_session` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_session` VALUES
('ekj79najnrsl39hj11kpemgd7b',1707939205,'172.19.0.1','xoopsUserId|i:2;xoopsUserGroups|a:2:{i:0;i:1;i:1;i:2;}xoopsUserLastLogin|i:1707856315;xoopsUserTheme|s:5:\"Anari\";UserLanguage|s:7:\"english\";ad_sess_regen|b:0;icms_fprint|s:64:\"5522da2d7c381d91f94c7c3e1cd49278d7e9ea3021ddac18e697a6f21fb041d7\";XOOPS_TOKEN_SESSION|a:3:{i:0;a:2:{s:2:\"id\";s:64:\"106725c004bcc31b16edb023e54be57bca5b754e63ee14827031241383fdcf77\";s:6:\"expire\";i:1707942716;}i:1;a:2:{s:2:\"id\";s:64:\"82c04c58beed9d389ed7cd66b49d5bd36a29479ee06dffff13bf207007a5a6ce\";s:6:\"expire\";i:1707942760;}i:2;a:2:{s:2:\"id\";s:64:\"d024378b1e708a3d21e9baab0fd8543cb6926616f115d681e0c7b678dffc76bb\";s:6:\"expire\";i:1707942763;}}formulize_entry_lock_token_SESSION|a:47:{i:11;a:2:{s:2:\"id\";s:64:\"4e1a02e24533f78cbbcc723a7a1f095962824e99dfaf0f0e2ced378a344d2e2e\";s:6:\"expire\";i:1707895210;}i:12;a:2:{s:2:\"id\";s:64:\"e7f5a81650d1c62c7a0d35d7eb5282f029d4071f6276d8877815742621be479c\";s:6:\"expire\";i:1707895219;}i:13;a:2:{s:2:\"id\";s:64:\"b9abc9b44316c85851fe022b1df7a3ae4991eca0b9fe31df5b2ed6cf0fc6c7e6\";s:6:\"expire\";i:1707895233;}i:14;a:2:{s:2:\"id\";s:64:\"f25708aa49940d9abd8da487b2a563a3f5872ebf1b697b6092e5d1da7f92f15c\";s:6:\"expire\";i:1707895237;}i:15;a:2:{s:2:\"id\";s:64:\"a33ef45d4a935fd51d088c4ac33dff8dfdfae3f3b980444530d54da4e0fb1eec\";s:6:\"expire\";i:1707895368;}i:16;a:2:{s:2:\"id\";s:64:\"92db88b73d566df68cd0b4ab92175753a38d11233743f1c25c0b317a472fd8f4\";s:6:\"expire\";i:1707895375;}i:17;a:2:{s:2:\"id\";s:64:\"fef73f5854670c61b4fbc722ff5dc6d43e2fcc9f3c7d257044eb370280492ff3\";s:6:\"expire\";i:1707895383;}i:18;a:2:{s:2:\"id\";s:64:\"78ed03dc813b7ec33bfed006364d050305b6f262d115fb08e2774dc789dea68c\";s:6:\"expire\";i:1707895458;}i:19;a:2:{s:2:\"id\";s:64:\"f54d43bff4cd84fa3dfc69719e0c695b54052dbd35aac0c062dcb4a970c43793\";s:6:\"expire\";i:1707895481;}i:20;a:2:{s:2:\"id\";s:64:\"13d66935e799718196f40522f6fa08fbc85712c07d44f4c869614af0144e3365\";s:6:\"expire\";i:1707895502;}i:21;a:2:{s:2:\"id\";s:64:\"133d7fcbe953bf67ee776cfe8deaac6dbd0411a411e92f17555fab2b8878ab1b\";s:6:\"expire\";i:1707895516;}i:22;a:2:{s:2:\"id\";s:64:\"f8dfdf9103ca20e03cc17d05d14b645ba5bbd2c45519f7bc730faa89180511f6\";s:6:\"expire\";i:1707895558;}i:23;a:2:{s:2:\"id\";s:64:\"a40880bcb73d041808a2cbcdd034a8cb80a51a89e5063456f0057c5d2a719218\";s:6:\"expire\";i:1707895562;}i:24;a:2:{s:2:\"id\";s:64:\"bdb18696c3ab01cfbb07d0ddfa47173ca7ad6c2133910a9f231ee3fa0b2f3b84\";s:6:\"expire\";i:1707895575;}i:25;a:2:{s:2:\"id\";s:64:\"15f637186c19767b5a406b881166a03a8d4a44f06b41d0144421e8b9bc39c660\";s:6:\"expire\";i:1707895587;}i:26;a:2:{s:2:\"id\";s:64:\"23bf3a06c0e3d8928c0ef5d741e4192002fb01785013dcbfe788f7c61d3936d4\";s:6:\"expire\";i:1707895597;}i:27;a:2:{s:2:\"id\";s:64:\"b2a3b65d2858ceece9ff39198cc6ba8c839c05d62b2fe2918f36e03fc63f33b4\";s:6:\"expire\";i:1707895636;}i:28;a:2:{s:2:\"id\";s:64:\"98ad03dccc53224ecb6ba94df0c4f0bcb84e760b79fcacb1e7907f3569993c1b\";s:6:\"expire\";i:1707895646;}i:29;a:2:{s:2:\"id\";s:64:\"a552ab03b17446696b3b9976e3b12b2a812313a38478c2d0aed2597e263339fc\";s:6:\"expire\";i:1707896592;}i:30;a:2:{s:2:\"id\";s:64:\"554953a5c9ab32980cc14f072078a0ccd3194779b3e3366356f412e0469b2903\";s:6:\"expire\";i:1707896597;}i:31;a:2:{s:2:\"id\";s:64:\"20ff176342e255a7e3b741ea65defcf16a279fc4ceb9450aaf170de47194d906\";s:6:\"expire\";i:1707896606;}i:32;a:2:{s:2:\"id\";s:64:\"e1cedb1fae16ca13a4be9cb53a1311ac7ea16df344d5ef8d929148a05498259e\";s:6:\"expire\";i:1707897614;}i:33;a:2:{s:2:\"id\";s:64:\"2dfb92f361ff3add2cfd40f2ebcc448cbd86f6181626a1091933882f723a923d\";s:6:\"expire\";i:1707897623;}i:34;a:2:{s:2:\"id\";s:64:\"52ec6059961a7764c34ab58d2d4dd7aed188daeb4682ea85b0ab1592bafe2043\";s:6:\"expire\";i:1707897634;}i:35;a:2:{s:2:\"id\";s:64:\"71cb105cf39f78a476fb40cd04323b3f75b657def6d71f990b5afa46f5386c0a\";s:6:\"expire\";i:1707897879;}i:36;a:2:{s:2:\"id\";s:64:\"ff6dea88efb356c7c18ff20e9cd48b06501a4bf0c0fe5572d0ad31e76cd34546\";s:6:\"expire\";i:1707897885;}i:37;a:2:{s:2:\"id\";s:64:\"713cf20cff9b26f895734367e13c82b451662a5d73ffb658cd477133f81cc6eb\";s:6:\"expire\";i:1707897953;}i:38;a:2:{s:2:\"id\";s:64:\"f47b44bbd79dfced2aa2c2bfe293a93c991bbbdcab324a275508e7c9371188f9\";s:6:\"expire\";i:1707898019;}i:39;a:2:{s:2:\"id\";s:64:\"99ea96c0252ae2b70a35a378e222c73441602736bd226c6d47e9baa901b16eb6\";s:6:\"expire\";i:1707898039;}i:40;a:2:{s:2:\"id\";s:64:\"2906f4b95d0c79558e51f9b4635a1cf1dbedd78c13870f3204f20db6bf7f425a\";s:6:\"expire\";i:1707898060;}i:41;a:2:{s:2:\"id\";s:64:\"8267e0056c5d8388b3075abad444c7e0f07ed051cf724bfacb960cd066684961\";s:6:\"expire\";i:1707898072;}i:42;a:2:{s:2:\"id\";s:64:\"ac6489932cad535b3d8bb78200e88de42178bceb397e583d2b27847a4244e317\";s:6:\"expire\";i:1707898084;}i:43;a:2:{s:2:\"id\";s:64:\"0d9a91acf0275763f7f84b70bb73cc5454c4678d34aa4a7c4da94140bb8b75d3\";s:6:\"expire\";i:1707898096;}i:44;a:2:{s:2:\"id\";s:64:\"9b6adb5340bc1aeba73034f7f8a8b321c7e2613fe6d954d3780c05d6eb28ec12\";s:6:\"expire\";i:1707898111;}i:45;a:2:{s:2:\"id\";s:64:\"c9cb3eda66cd79ace5847504b8e168f7888df2a259fccbb22eacb6aaf0807b26\";s:6:\"expire\";i:1707898213;}i:46;a:2:{s:2:\"id\";s:64:\"50a66775a73af83d175ba71380208dcdc526b6dd561a048fbacd11db5cd4ef52\";s:6:\"expire\";i:1707898344;}i:47;a:2:{s:2:\"id\";s:64:\"7913fd9a49818d75d442c6ab82f2e388eea2e67d77a458217cc52ab8af9c2e1a\";s:6:\"expire\";i:1707898357;}i:48;a:2:{s:2:\"id\";s:64:\"374d60d6c1d6bb6698f10302987aff3503634fef8ab615ec59a7b244239a09e0\";s:6:\"expire\";i:1707898364;}i:49;a:2:{s:2:\"id\";s:64:\"2c6acb842382be52ee85163f2d7714435448ade5df1798dc7269bc926cf4cfd9\";s:6:\"expire\";i:1707898383;}i:50;a:2:{s:2:\"id\";s:64:\"ed705d6f7dccb8263a7d2c1c3981a9625d652745ee004aaf87edac730e780813\";s:6:\"expire\";i:1707898416;}i:51;a:2:{s:2:\"id\";s:64:\"e206d0a4d863d64e09486dd30185e645343833363b283937d10cc3bc23bf7225\";s:6:\"expire\";i:1707898439;}i:52;a:2:{s:2:\"id\";s:64:\"05e94dc822ae1f93e19c09ab08d55eb303ca067ff6731c37944d699de2362533\";s:6:\"expire\";i:1707898451;}i:53;a:2:{s:2:\"id\";s:64:\"735f14a646521fc2654a6e4586f70d00a700e9f7ac8df1c066cfb0cd10dd4b8c\";s:6:\"expire\";i:1707898456;}i:54;a:2:{s:2:\"id\";s:64:\"2d51083515c585627b3ea843ec82ae9017fd1f8d123492b20340e7968a285620\";s:6:\"expire\";i:1707898480;}i:55;a:2:{s:2:\"id\";s:64:\"78139ccae13c9d2ee582415207ee34b26dfdb414c17d7b9b317977106a5a0a66\";s:6:\"expire\";i:1707898495;}i:56;a:2:{s:2:\"id\";s:64:\"4f951746a59a176276d31067739a1f06055b1ed6685abe892bfd339f9e32868f\";s:6:\"expire\";i:1707898527;}i:57;a:2:{s:2:\"id\";s:64:\"5405b643145787eee31dc9edd58fc0075880438eb0e5e7ab8e0e6819fb758913\";s:6:\"expire\";i:1707898538;}}formulizeScreenId|a:1:{i:2;i:9;}columns|a:1:{i:2;a:312:{s:32:\"4f198013194e0ec0f41e9977bfd8cf89\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"cef003c7f8c968a9d2f990884b605855\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}i:9;a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}i:11;a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}i:10;a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}i:12;a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"788df3f733a14282be020d424bba1b70\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"b4152a711e8e8cabf12931e7e4021342\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"ec8ec984cf78a374be13fd44a8855151\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"807e1254a65b0e3d07089a8754e71f98\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"03b89718a01a9a6b20279dfdbf69d23a\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"4450b79d014842ae9f6aeedbecb53ebc\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"a670e9bad69ed8617b2924315a39203f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"366d44a73576d21db2afcd61558fb7d9\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"25380d4013988afd4888a21e9c8964a1\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"10476847a48b0e82193b67409354ec91\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"c55f46ac12953748ee0dedd6aee8dc63\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"3b02f27576423da53c26bdc11ec89028\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"c292b7de71d4509a10fe2f3d3827a75c\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"40209ac7b24f15b6f721c6898c8ccec9\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"3b35a2323958eb636feab4108cc08cf4\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"c864752f2bc4de7865af11ad19050f75\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"96d2403eced966e2c9968134b7dd5026\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"df388d3b634331d99e2de8ba5563f8ac\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"351f9640de46c1d78372b9d6219f22dc\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"f5b03bd24f854b26df1a35cc294a697b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"a9ea097756b728a5cebbac01428daf10\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"2d7e6552755324a181e332bec50b1dbc\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"4499a5d20c7cf701c1f5428be025b06e\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"e67d8423b8478aeb1a0d4a7ad0a52e0f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"550bcce477eb2096871e57aa988f2584\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"699b0729e9875bc578d7f13e12822e35\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"99b44ac392c9c8e5049d2c132d18ad1b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"a3f126af811f806843bb9900351226ce\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"a77bd30cb6420854c1b5b6630906ee3b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"bfde7ec9da038e16aa14aa651bc8c74b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"37c7b9463f0d0f10bc27423abccd5595\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"cd66bac417c68260cfecdfe88425466b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"57c922c4390581ce41feb8bc78506eae\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"6c79bb237797f9339def051ffc2aa612\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"2de690397ade07c661188cbd72416733\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"94897afdd163fc49ef9a8bd550feddaf\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"95b4e8ff67f5a05548747b6290b5f981\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"b5fb3158d6b368fb37ecbd9026c5f2c3\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"0357b431a244543aede12829b5d9e5b7\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"35afc72d0241b545e744ce8415c521fe\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"f04bb565758e395cf51240a002d5d4e1\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"bdb46ab6a263933fd43b4b1edb1e8029\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"1cfd2cce612cfa56c781da3a065fbf08\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"79dc7161a6bd88204a9350d4ce2dcfee\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"2cb6b62ac94db704f998acfc9bf2fcb4\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"ae35ede75207c0a0a0a76ec743677fdd\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"e5956093ff3a9194615ddffaa9ed79de\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"635f76b08624a6983b663d5c0ccae2c2\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"9adcbdf46f7c43b37300615b71267932\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"52ba599e1695b30d56f7d57893d61851\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"b178e6ddd37544c2562c0c4c8c964b13\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"ba6aa7add009697836c834de0c3ddbff\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"058d655a5ac760d008c997aba36608b9\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"00f99dd277027d4f5b248beaa2b7c196\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"5bce4dedafc7734c79d09b8b0fe920bc\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"e6449bbacc7c49c2606306683037e58f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"63a453b5c15dc7be76e5b560a800fdbb\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:0:\"\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"8b261b5b48df2d9d973b9773106a6dca\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"0f146c00556214336dc217e053a3ee9b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"0282f1ba7fec9ca144b0c553d89f0433\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"fbc5247d07230b45d31e04478b5ee1c2\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"9e2abedea241e2a9e6e7ecffa635c59f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"a54d2a9223c1699d8ba6f4d81b2096f4\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"18f9439bd41bef7b595411686fe46a0f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}i:15;a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}i:27;a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}i:29;a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}i:30;a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}i:17;a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"884a84a42ee8cdf11f59728bd80c8345\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"4804c590c12b1d5ffa8c54a1ad4ffd7d\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"ff0c7b5afeaa2aecbd0e8d65ddbb0a59\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}i:20;a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}i:21;a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}i:22;a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"bf0b84b84ffb8340535dd748cb08be1b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"706ee479e2309fb1a81362aee52776c7\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"ac32a3bc2d7c386ff5a1a880d6750d4d\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"7cea661219a9098218bbc6afbb26b618\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"c478146cfbd69d40997e65f462b8101c\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"aad2e9eea0bb3d94886f86eff8800674\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"59c0e4175b3e82199200bab689253816\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"3802fdea343d66d7fa722dc8252f2904\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"19d627b32e3e54324dbdc4f9e4c4cb27\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"39260c7d63ee5e4c0259800dc6e46af1\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"f40c89a0ed4e46bf3fe7e4ff9de7fc72\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"6e13ad314ca66c3bde21c78cab3fda44\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"3800779c96b090fd5e1d8b2e544c0cf1\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"6fedc5f02862ddeeb9ba3ad6073776f9\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"6330a5a558579f37718fa5d014c549c2\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"3af12b2bf6b18b9b7e29fb343349b4e4\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"e6c045049c2d6925cb91efba97dde343\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"aeb7c74bf58a76de330bde14fe851c38\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"ca346461552eb476358c856c71183d01\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"b16051c65762b7d767787687bb9552e5\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"5023febb845b40214c27e065ce3a3180\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"4580b9989cb3c3208195c6990ed74b16\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"2d535c9b80ccba678ea987422ad86f1b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"505fdbee634215cc0e3dae30f28f09be\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"61eb46dec21204c641dd27f2aefba692\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"3f2934c47a57228a2f47dfcfb9ff38a8\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"45a33c3d019576ebbf95b8e9137cd00b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"46ae8512d25d201231331d4480ebbc04\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"c8d4c75463f02a40853e0912ba094421\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"83049aed664d6e855e79981ec82f2ce8\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"70b38c2af9539a03bf5b2917b20a6b3f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"e5686b8bfec08e350d6b7d9e5247f959\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"64768ef19c4e2518d34de77b8c358db9\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"a03457af93205d01a096f9cbdac4955e\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"afcd992adc69e170296a41ff7b3d4c49\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"60ced59b2bb95d3d34f81757da79e292\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"7f31825dda9ca3e19112c87ffb50abfc\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"adbc328e4d9b8d629b355924ed9fe373\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"aa567588871eff62f78948b2cbb895f5\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"9169c31ffa10f561a77bcf3affadc3c2\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"fe25f116ecbac329df1b8d509e3ceb00\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"74718d1f9c5e3e4d51354a89a0e20cd3\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"548c64bce2db82e9db7fedb1cf3b6d7e\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"3f7f0783a778d20aecbf8d616f52db3b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"0ef529ca327bc7758f4b4341601184c3\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"e61b89ec28a560cb59c1a324db2f03cc\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"f12810b0dedfbc470348fa37d46e56d7\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"d290850317466109feeab8af393a4125\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"814e7bbdc8c1f597a2e0a98f6ea55796\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"80efd155445e2af6036b2bc2edbb9c4b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"11500d7dc1553bcebff04b5e043a079f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"1d41f691e5362c5f3d91c2be8d15bf91\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"94a565cd736ff7db377a940aad6eb251\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"df00ac397e9dffcdd51f110bdd436cdc\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"18afbfee52f0c9b333436cc3f0164201\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"6695f944d45815b346abbef2d2018e04\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"cae05d335ef28ab0223457602a7d30ee\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"6c988e35fc5f4e1336d50ee560cd4986\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"b1d04e7735c1e55305c45ad9b15904c8\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"676064561908d4e50430fb05ac586c23\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"026e9dd5c2f34c638e3d8b2caa21b9d0\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"f5228e8d5a72acf16329eea33daa51db\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"9f336c08a18b454dfdf49822b23d5f31\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"4691c4a350177e37289cbf8f7fad9798\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"a05b2c2d782050f174a354ea21c27b2f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"ec46ada6a7e695649c85b01fde6f1d05\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"167606d16cc335b2b924c134e7ae820f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"e58e73fdda7d24cb5d212b5b6ceb94d8\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"058acc349608bfec4b9bccc0a62c2700\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"4449b37f5ee2e44684dfea95f912d460\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"5de8688b614f742888aff374bc586474\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"2b0a24a78af76f7b8738953d878747be\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"7b6ab32d332b5075188a0cecd32a82e4\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"b359f7727ed0e57e0fc8eb73f8fadc6f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"8a83173ccf1dc92e2894955f2337dc77\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"3c639680cd93763edbf6506d90a4145b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"d735010f795857e532d67fdbd6ea606b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"dba491689fd9608990296e797a83939f\";a:3:{i:0;i:1;i:1;s:4:\"auto\";i:2;s:4:\"auto\";}s:32:\"8236098d9cd256be6176aa5bbcccebb9\";a:3:{i:0;i:1;i:1;s:4:\"auto\";i:2;s:4:\"auto\";}s:32:\"9a82e8accd3e7f43055bafd48dae404e\";a:3:{i:0;i:1;i:1;s:4:\"auto\";i:2;s:4:\"auto\";}i:32;a:3:{i:0;i:1;i:1;s:4:\"auto\";i:2;s:4:\"auto\";}i:31;a:3:{i:0;i:1;i:1;s:4:\"auto\";i:2;s:4:\"auto\";}s:32:\"7cc8a211bdbcd20085f24ed1b00e08ef\";a:3:{i:0;i:1;i:1;s:4:\"auto\";i:2;s:4:\"auto\";}s:32:\"e4739e3900a9768f39d95a8c78ffccf1\";a:3:{i:0;i:1;i:1;s:4:\"auto\";i:2;s:4:\"auto\";}s:32:\"7766a51376677745d66d28d16caf1aa7\";a:3:{i:0;i:1;i:1;s:4:\"auto\";i:2;s:4:\"auto\";}s:32:\"a1cfe11dbd846a37bf4a2d41aa654a5a\";a:3:{i:0;i:1;i:1;s:4:\"auto\";i:2;s:4:\"auto\";}s:32:\"760415eb840351a457a4e6d1b3f34f22\";a:3:{i:0;i:1;i:1;s:4:\"auto\";i:2;s:4:\"auto\";}s:32:\"00dc2cf934966c0386d3ed1f2b4bd43a\";a:3:{i:0;i:1;i:1;s:4:\"auto\";i:2;s:4:\"auto\";}s:32:\"c0ca98b94a6279613880158829bb342b\";a:3:{i:0;i:1;i:1;s:4:\"auto\";i:2;s:4:\"auto\";}s:32:\"1cd7dbd8d7ecc5a739af940435750aa8\";a:3:{i:0;i:1;i:1;s:4:\"auto\";i:2;s:4:\"auto\";}s:32:\"b63dc45a4614edadc0bd82d2cc3e5e3f\";a:3:{i:0;i:1;i:1;s:4:\"auto\";i:2;s:4:\"auto\";}s:32:\"a09e4cf2642ce3df2297d99e246a5d18\";a:3:{i:0;i:1;i:1;s:4:\"auto\";i:2;s:4:\"auto\";}s:32:\"c9922fe328d4a20dc94b668a2028ac73\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"206b53403301a958e006ae82e293072c\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"6d6dca68f74c8202713415f5df699379\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"0e7e43feee459a379e15efd24ef61d61\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"3cbda939ca37515122c510dc584180ba\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"a0e193591ef2495c91c5bb3ed0213044\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"aceaa811cbbade63dacce1dd86c7637e\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"339cff7228aeef3a382059c1135ef84c\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"b868b0fc9684efb3878b430f5ef1b4ed\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"cc96146ab10108416f5e7989791aeae0\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"79094e906975501acc47655e1d5c1f28\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"6fb613d1687219561f8726a11fd078ca\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"b05bdfe56e21485e3f094997de6a97a5\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"e2ac92f0a88b095fe96d7d7a0060c305\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"3b80808d358e2e982d04c0d9beb8c003\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"d02f4d165734f8621f2a20cc53320548\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"b21f46e21fbb5e03acb6a68a187b631c\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"5a9e92539a909542e3961308876b86dd\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"1434723dc8b353f60fcaafcdcb104e1e\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"21b134863dbbd7f6954b42faf8622ac6\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"d353153605453d8bdb51ed90ad8f737f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"c71efc8834640cfd842b1f2732d4db57\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"d25ec76c3774ca1ed36788cb5e36d118\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"e3be87f23d68e48a588626149eac0f7f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"2f3b2020bc8835a84ade550742cee0ae\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"ae008fcea0bf455bd9a3cb6274b94d14\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"8a6d3c86fa0ccb787a780656544e2701\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"0194684ef393cbdc32468b047511febc\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"3d2d888e8411c7a9d684d3ae56f6c718\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"00771273f18ad21c032d4f0d0fa0428a\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"50dac60efbabf03457d2cdc9b6c5002c\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"5c1735a59b5788fe53435381291e605f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"6a6c690ad3f019577c2fa6878cb3dd90\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"f3e34bd95f20ef7d0f23921054029011\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"c0fa423e6d27e2476cd98d69b870ff16\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"c366d6b080665f9f137a5b4d53051ea9\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"5e8276c7e05f11893aebd3f17320ab5a\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"b62f1cebb505b60762e53a5da5ae19b7\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"ac253a4b77c37561baaf6d389b065310\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"94bffa843672b44bcfd8515d0b286712\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"0c04fd7a6a0bbf458753396a3af5873b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"15b3a50636a1797123c7a91d06cf52da\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"a2dc1bc2e19d890f434cc73efe35db65\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"28d72b5b51de21d1a43adec4d1e1eda5\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"a747853c7b5c78eabf2f1a9b4aa680d4\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"366707949941e43358dfdf55f1ce82e4\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"3a5b02e97f5a20e47966b79aa45e8406\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"8fdc12d2a786863f707231e9deac0103\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"ac7713ab14af4417916a6e31389a6624\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"1df29deb2ffaeff5f42fed0d120523af\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"51fbcdc945a18e2ff3eb507ac7446dd2\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"02a84888ed99a555f206368015be2d31\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"1ebcd76296bc5508f6f6cf0c4273f95f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"b159756f3a68180c99b5cb1a168c0824\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"03030e4c31f3c89c93bca8b041bb6939\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"45f3b6398c1e54f90d9ed3f9254accd8\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"45fc508f965b31637781cebd21f20eb5\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"66fcc9d3e0f2eba0dc7accd99827446b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"5f902a326b2f87fe7d41f76ecdb58169\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"86a99c23ade5d2735ce93c9c5927d918\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"7ba473d9d2f877439fb90a5d6cd3b570\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"e1515d66bae94fa8fe376f472959bf4d\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"813f680df9acf58f6ca9d0f80d325660\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"5880355cbe249917d1cafe43bf3c2b44\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"2558189f9e2bcec361770d2cb636c11c\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"a0be56e5d349def3c863e7157c308c55\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"1b44eff0524edd6d97cee13645a5b767\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"0fa82e0439a0cca8ec23573384356b3e\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"4a9e426a582686c8a3f0ccc496746e93\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"faf3516b659a093c544e239f2943d7c2\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"efccfc1e6c332520f23fddccf731b0a8\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"9626b07e73ce895cf3d868f022193c98\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"6fca5501ae88843a7b3e629400f262bd\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"23a0bf742d1ea56b0d4eb1111409a865\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"e44447184cf7d65284aa0411ee861745\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"709896f942664061eef8a899d44ce31b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"9a1074b10740fabbe5e74eae3d274534\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"d1a723fd16f7dd2972ec4fd4f2607236\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"cabc9017b2542d6c946eea684de0dab2\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"24814c3d7d5d5377e15435be950131e5\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"e431db7d99e203257c9f7b4c43f905bd\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"0817caa7e337cc5d3fa5c9d861b049b6\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"dd0ab569650b8d068592b2b986b9c999\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"79b04fdc01bc26a0935b6f14bd83bc64\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"2acae7d7bace626169be42db3ba49fdf\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"c63b9297c1a1dbdca5ddd32aeb343ae0\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"731096eaf77bacd8d0559c9960918f0e\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"805b9cf66d914ee8bbe11e040cc56616\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"677c580066334616c984238e069cb362\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"e88e0d0ebdcc1332474e87f09971df6b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"014f1f915154667fe32b8bd90c2bd0d3\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"58d7c9b109d162c0c34a069b555cfdae\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"113bb0b059ee3edbd6676bef6ad48901\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"0724501455f1b0a707ee96841a150436\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"fa0864e81ecd75b235c0b6349ccae1f4\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"2ae52d770a2a93137605550eb8bdf879\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"d9442c7c73fb048774305ccfc3d02d82\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"904af8682d6d50c2cde994b79996061b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"42fc7abaf4f9c93c9f37e6913639f1d0\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"4f1437f7f29304667c791d0c0d227a2e\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"6f8f29280412905871100465029a01d9\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"07cc391274de416588febb6c62280265\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"3c669bd49cb59dd8b01093ec308421ef\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"53c02e91f6aa4e358f300e9b43f2a5e0\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"d0f6c15e3e697602c937948e35a64b5c\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"dff949a8f5883e2a07fb7261d1b9077f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"73316aa0697e3a9fa2463303cf85ba91\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"7825ae0f00927ed6e458df4f64ab611d\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"dda8d9eed6e282d749d03875a09d62fa\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"307091542e21d0867e18e15d82b904ee\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"504cb203c3a1dfbf3086407a16c34990\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"cfe5a9d565b48a29aa3afd30af32e6a4\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"d6eab62d74dfb575f9a2301516bbb3cc\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"e80fa25ca5fcf1d50288669c1f6e13e9\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"5bc2e25f3b82f8cf307579368265c01b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"a086d850cde3c0e6833b9a091664aed5\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"d0f732c4ef00284b5a35d04ba4f03b0c\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"5e9ca0d00cdc70b7662967cb93d0287d\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"37ed8bae798e928ac9612237bc8fdf80\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"4deb58a48dc2c4cab2dc586fa8fef3d8\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"980706fcbde3e5592673b0155910e464\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"8c4983a07c4b604d2e973b99625f4c62\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"90dfa510c49b0f51a20c25c6eecd72f3\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"501c8bad14a039d36419ab16b2b5e4a0\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"d97c4a9b8c1126f79d55b86f7af2b8d4\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"bce0501f2fbba0b867f0ea8ac6a325c6\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"af37448cf1a6cf103aae5c5b5edc20a1\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"32b2685e19031835c44b341d1f8404ae\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"7394e901e5499b684f3a1024744c3e0b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"23e1a4fea7a347c95b457009db6bb688\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"59eed9e86e91da68e3224356566a3712\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"977e95ae66efbe4714e3b6edeb7ab373\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"b453058fd34536220db5d43cc37e2b57\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"a6570b29c643461e9c1134509d23c27a\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"0de0e987ba0d35692dbeb93d8ae5bcb2\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"f16230fadb84f58fee21f9681cccaf1f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"39d4cd725170602a15c5e3b3b4b4150a\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"e8e579ae1efbab0b5b21707feb1f629f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}}}'),
('f4vdb2183trsa4ca75k4g8hvjq',1707942486,'172.19.0.1','xoopsUserId|i:2;xoopsUserGroups|a:2:{i:0;i:1;i:1;i:2;}xoopsUserLastLogin|i:1707942110;xoopsUserTheme|s:5:\"Anari\";UserLanguage|s:7:\"english\";ad_sess_regen|b:0;icms_fprint|s:64:\"5522da2d7c381d91f94c7c3e1cd49278d7e9ea3021ddac18e697a6f21fb041d7\";XOOPS_TOKEN_SESSION|a:15:{i:0;a:2:{s:2:\"id\";s:64:\"08a4c14887599301a79cb3b0ab15c56eeed1d1c9e882a3ab10a0a935666812f5\";s:6:\"expire\";i:1707945718;}i:1;a:2:{s:2:\"id\";s:64:\"c77b7d13f1ee8cb2b7393d519499789891ffbb69ff6a0551cf082d60a2254c70\";s:6:\"expire\";i:1707945722;}i:2;a:2:{s:2:\"id\";s:64:\"9c59821360f5c7a594a9de0c38a3e91cfd2e12a69fc0bdd4e25a69677b62df8c\";s:6:\"expire\";i:1707945722;}i:3;a:2:{s:2:\"id\";s:64:\"3dfc23249c2b52cc6c7d9855cdc69e389c0a202b36c49bc6ef78ebb988a5bdf9\";s:6:\"expire\";i:1707945765;}i:4;a:2:{s:2:\"id\";s:64:\"4d51156a29590110b7ee79d369173fb4f9303e8b32280bcdae5fd440bbb7806a\";s:6:\"expire\";i:1707945765;}i:5;a:2:{s:2:\"id\";s:64:\"016a8af4b3db07c4139f42ff45802eddae02034e418b3004f538c3f53f3c790d\";s:6:\"expire\";i:1707945840;}i:6;a:2:{s:2:\"id\";s:64:\"36faf5fd06e54795eef31720c0c000fcb9615385664b1485895d405ccbbe8694\";s:6:\"expire\";i:1707945840;}i:7;a:2:{s:2:\"id\";s:64:\"df0ab25e0b47bd9b005b1cebda1df3b0d3020ca70e37cd4455a1ae6aed8f7965\";s:6:\"expire\";i:1707945861;}i:8;a:2:{s:2:\"id\";s:64:\"45a5deda0c2985786d3ca91ffb3caeb61776d1dec3e88cdb840226aa6ef88bab\";s:6:\"expire\";i:1707945861;}i:9;a:2:{s:2:\"id\";s:64:\"85fca1d9c4bcd0e514f9752dd8248fc93d2129fadda85f587674074e9930ba3f\";s:6:\"expire\";i:1707945890;}i:10;a:2:{s:2:\"id\";s:64:\"de740a7bc9ad1a84dd9b18960a02dda03faf8e4832441cbcc1fccbbb9930f9b2\";s:6:\"expire\";i:1707945890;}i:11;a:2:{s:2:\"id\";s:64:\"7d81d326523c917b4ab11b01559828307977e4957e47ca38ff1e6aae86f61778\";s:6:\"expire\";i:1707946049;}i:13;a:2:{s:2:\"id\";s:64:\"1d4a9246209b377fd99dbd665b05937cf80f9bbfa5260c33afdcf16dfd7be642\";s:6:\"expire\";i:1707946054;}i:14;a:2:{s:2:\"id\";s:64:\"4835d9b4333ee4df714119a92d239c1872bfd005bc02b4486b796d8bf6b8b95d\";s:6:\"expire\";i:1707946083;}i:15;a:2:{s:2:\"id\";s:64:\"565099951f381759d2be82e8ad7a55f5382685e5325a3f122157944692e86a79\";s:6:\"expire\";i:1707946084;}}formulize_entry_lock_token_SESSION|a:3:{i:0;a:2:{s:2:\"id\";s:64:\"86af862b3b3b4fddbb7e08839e6a0cac43a962dcb19add4aa80393d540064743\";s:6:\"expire\";i:1707946049;}i:1;a:2:{s:2:\"id\";s:64:\"d556b03293468cade5381dc1b388999ee07b03d7cf308d48f76fb01fed660f6f\";s:6:\"expire\";i:1707946054;}i:2;a:2:{s:2:\"id\";s:64:\"d30a1c6b29680c1791fe523ac7cfdc8ee642c13001ef4df038a7bfb6e868d1b0\";s:6:\"expire\";i:1707946084;}}formulizeScreenId|a:1:{i:2;i:5;}columns|a:1:{i:2;a:73:{s:32:\"f63f1e2102245c048029f70e393c8947\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"ff0c7b5afeaa2aecbd0e8d65ddbb0a59\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}i:9;a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}i:11;a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}i:10;a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}i:37;a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}i:38;a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}i:40;a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}i:12;a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"3080a1d1a476a3dc943447888c2bc357\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"ec8ec984cf78a374be13fd44a8855151\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"807e1254a65b0e3d07089a8754e71f98\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"03b89718a01a9a6b20279dfdbf69d23a\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"4450b79d014842ae9f6aeedbecb53ebc\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"a670e9bad69ed8617b2924315a39203f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"366d44a73576d21db2afcd61558fb7d9\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"25380d4013988afd4888a21e9c8964a1\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"10476847a48b0e82193b67409354ec91\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"c55f46ac12953748ee0dedd6aee8dc63\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"3b02f27576423da53c26bdc11ec89028\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"c292b7de71d4509a10fe2f3d3827a75c\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"40209ac7b24f15b6f721c6898c8ccec9\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"3b35a2323958eb636feab4108cc08cf4\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"c864752f2bc4de7865af11ad19050f75\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"96d2403eced966e2c9968134b7dd5026\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"df388d3b634331d99e2de8ba5563f8ac\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"351f9640de46c1d78372b9d6219f22dc\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"f5b03bd24f854b26df1a35cc294a697b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"a9ea097756b728a5cebbac01428daf10\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"2d7e6552755324a181e332bec50b1dbc\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"4499a5d20c7cf701c1f5428be025b06e\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"e67d8423b8478aeb1a0d4a7ad0a52e0f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"550bcce477eb2096871e57aa988f2584\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"3af12b2bf6b18b9b7e29fb343349b4e4\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"d4b0ec60ce59846b4c85e322d3f2be5f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"37c7b9463f0d0f10bc27423abccd5595\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"ca346461552eb476358c856c71183d01\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"6807211f555be4b7e3ec0af9ac116bf0\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"6c79bb237797f9339def051ffc2aa612\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"2de690397ade07c661188cbd72416733\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"94897afdd163fc49ef9a8bd550feddaf\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"95b4e8ff67f5a05548747b6290b5f981\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"b5fb3158d6b368fb37ecbd9026c5f2c3\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"0357b431a244543aede12829b5d9e5b7\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"35afc72d0241b545e744ce8415c521fe\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"f04bb565758e395cf51240a002d5d4e1\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"bdb46ab6a263933fd43b4b1edb1e8029\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"1cfd2cce612cfa56c781da3a065fbf08\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"79dc7161a6bd88204a9350d4ce2dcfee\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"2cb6b62ac94db704f998acfc9bf2fcb4\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"5023febb845b40214c27e065ce3a3180\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"ec589b7d671f90575e1dd938473a13ed\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"635f76b08624a6983b663d5c0ccae2c2\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"9adcbdf46f7c43b37300615b71267932\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"52ba599e1695b30d56f7d57893d61851\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"b178e6ddd37544c2562c0c4c8c964b13\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"ba6aa7add009697836c834de0c3ddbff\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"058d655a5ac760d008c997aba36608b9\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"00f99dd277027d4f5b248beaa2b7c196\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"5bce4dedafc7734c79d09b8b0fe920bc\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"e6449bbacc7c49c2606306683037e58f\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"4a263a9bb01c7d8ea7fac9f34146e693\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"4a9e426a582686c8a3f0ccc496746e93\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"df8a10406ec71e3d98c7b1c375e186ab\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"efccfc1e6c332520f23fddccf731b0a8\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"8b261b5b48df2d9d973b9773106a6dca\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"0282f1ba7fec9ca144b0c553d89f0433\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"23a0bf742d1ea56b0d4eb1111409a865\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"a3749c76bb7c20a4798237f9176b7c39\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"a3f126af811f806843bb9900351226ce\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"cd66bac417c68260cfecdfe88425466b\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"89897fd085142ee79125401e12fc7454\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}s:32:\"60d378599671f61793e1feda7445acad\";a:3:{i:0;i:2;i:1;s:3:\"20%\";i:2;s:4:\"auto\";}}}');
/*!40000 ALTER TABLE `ai8k7Bba_session` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_smiles`
--

DROP TABLE IF EXISTS `ai8k7Bba_smiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_smiles` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL DEFAULT '',
  `smile_url` varchar(100) NOT NULL DEFAULT '',
  `emotion` varchar(75) NOT NULL DEFAULT '',
  `display` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_smiles`
--

LOCK TABLES `ai8k7Bba_smiles` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_smiles` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_smiles` VALUES
(1,':-D','smil3dbd4d4e4c4f2.gif','Very Happy',1),
(2,':-)','smil3dbd4d6422f04.gif','Smile',1),
(3,':-(','smil3dbd4d75edb5e.gif','Sad',1),
(4,':-o','smil3dbd4d8676346.gif','Surprised',1),
(5,':-?','smil3dbd4d99c6eaa.gif','Confused',1),
(6,'8-)','smil3dbd4daabd491.gif','Cool',1),
(7,':lol:','smil3dbd4dbc14f3f.gif','Laughing',1),
(8,':-x','smil3dbd4dcd7b9f4.gif','Mad',1),
(9,':-P','smil3dbd4ddd6835f.gif','Razz',1),
(10,':oops:','smil3dbd4df1944ee.gif','Embarrassed',0),
(11,':cry:','smil3dbd4e02c5440.gif','Crying (very sad)',0),
(12,':evil:','smil3dbd4e1748cc9.gif','Evil or Very Mad',0),
(13,':roll:','smil3dbd4e29bbcc7.gif','Rolling Eyes',0),
(14,';-)','smil3dbd4e398ff7b.gif','Wink',0),
(15,':pint:','smil3dbd4e4c2e742.gif','Another pint of beer',0),
(16,':hammer:','smil3dbd4e5e7563a.gif','ToolTimes at work',0),
(17,':idea:','smil3dbd4e7853679.gif','I have an idea',0);
/*!40000 ALTER TABLE `ai8k7Bba_smiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_system_adsense`
--

DROP TABLE IF EXISTS `ai8k7Bba_system_adsense`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_system_adsense` (
  `adsenseid` int(11) NOT NULL AUTO_INCREMENT,
  `format` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `style` text NOT NULL,
  `color_border` varchar(6) NOT NULL DEFAULT '',
  `color_background` varchar(6) NOT NULL DEFAULT '',
  `color_link` varchar(6) NOT NULL DEFAULT '',
  `color_url` varchar(6) NOT NULL DEFAULT '',
  `color_text` varchar(6) NOT NULL DEFAULT '',
  `client_id` varchar(100) NOT NULL DEFAULT '',
  `tag` varchar(50) NOT NULL DEFAULT '',
  `slot` varchar(12) NOT NULL DEFAULT '',
  PRIMARY KEY (`adsenseid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_system_adsense`
--

LOCK TABLES `ai8k7Bba_system_adsense` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_system_adsense` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_system_adsense` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_system_autotasks`
--

DROP TABLE IF EXISTS `ai8k7Bba_system_autotasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_system_autotasks` (
  `sat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sat_name` varchar(255) NOT NULL,
  `sat_code` text NOT NULL,
  `sat_repeat` int(11) NOT NULL,
  `sat_interval` int(11) NOT NULL,
  `sat_onfinish` smallint(2) NOT NULL,
  `sat_enabled` int(1) NOT NULL,
  `sat_lastruntime` int(15) unsigned NOT NULL,
  `sat_type` varchar(100) NOT NULL DEFAULT 'custom',
  `sat_addon_id` int(2) unsigned zerofill DEFAULT NULL,
  PRIMARY KEY (`sat_id`),
  KEY `sat_interval` (`sat_interval`),
  KEY `sat_lastruntime` (`sat_lastruntime`),
  KEY `sat_type` (`sat_type`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_system_autotasks`
--

LOCK TABLES `ai8k7Bba_system_autotasks` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_system_autotasks` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_system_autotasks` VALUES
(1,'Inactivating users','autotask.php',0,1440,0,1,1707856295,'addon/system',00),
(2,'Reactivate suspended users','include/autotasks/reactivate_suspended.php',0,360,0,1,1707939093,'addon/profile',00);
/*!40000 ALTER TABLE `ai8k7Bba_system_autotasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_system_customtag`
--

DROP TABLE IF EXISTS `ai8k7Bba_system_customtag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_system_customtag` (
  `customtagid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `customtag_content` text NOT NULL,
  `language` varchar(100) NOT NULL DEFAULT '',
  `customtag_type` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`customtagid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_system_customtag`
--

LOCK TABLES `ai8k7Bba_system_customtag` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_system_customtag` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_system_customtag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_system_mimetype`
--

DROP TABLE IF EXISTS `ai8k7Bba_system_mimetype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_system_mimetype` (
  `mimetypeid` int(11) NOT NULL AUTO_INCREMENT,
  `extension` varchar(60) NOT NULL DEFAULT '',
  `types` text NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `dirname` varchar(255) NOT NULL,
  KEY `mimetypeid` (`mimetypeid`)
) ENGINE=InnoDB AUTO_INCREMENT=124 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_system_mimetype`
--

LOCK TABLES `ai8k7Bba_system_mimetype` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_system_mimetype` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_system_mimetype` VALUES
(1,'bin','application/octet-stream','Binary File/Linux Executable',''),
(2,'dms','application/octet-stream','Amiga DISKMASHER Compressed Archive',''),
(3,'class','application/octet-stream','Java Bytecode',''),
(4,'so','application/octet-stream','UNIX Shared Library Function',''),
(5,'dll','application/octet-stream','Dynamic Link Library',''),
(6,'hqx','application/binhex application/mac-binhex application/mac-binhex40','Macintosh BinHex 4 Compressed Archive',''),
(7,'cpt','application/mac-compactpro application/compact_pro','Compact Pro Archive',''),
(8,'lha','application/lha application/x-lha application/octet-stream application/x-compress application/x-compressed application/maclha','Compressed Archive File',''),
(9,'lzh','application/lzh application/x-lzh application/x-lha application/x-compress application/x-compressed application/x-lzh-archive zz-application/zz-winassoc-lzh application/maclha application/octet-stream','Compressed Archive File',''),
(10,'sh','application/x-shar','UNIX shar Archive File',''),
(11,'shar','application/x-shar','UNIX shar Archive File',''),
(12,'tar','application/tar application/x-tar applicaton/x-gtar multipart/x-tar application/x-compress application/x-compressed','Tape Archive File',''),
(13,'gtar','application/x-gtar','GNU tar Compressed File Archive',''),
(14,'ustar','application/x-ustar multipart/x-ustar','POSIX tar Compressed Archive',''),
(15,'zip','application/zip application/x-zip application/x-zip-compressed application/octet-stream application/x-compress application/x-compressed multipart/x-zip','Compressed Archive File',''),
(16,'exe','application/exe application/x-exe application/dos-exe application/x-winexe application/msdos-windows application/x-msdos-program','Executable File',''),
(17,'wmz','application/x-ms-wmz','Windows Media Compressed Skin File',''),
(18,'wmd','application/x-ms-wmd','Windows Media Download File',''),
(19,'doc','application/msword application/doc appl/text application/vnd.msword application/vnd.ms-word application/winword application/word application/x-msw6 application/x-msword','Word Document','system'),
(20,'pdf','application/pdf application/acrobat application/x-pdf applications/vnd.pdf text/pdf','Acrobat Portable Document Format','system'),
(21,'eps','application/eps application/postscript application/x-eps image/eps image/x-eps','Encapsulated PostScript',''),
(22,'ps','application/postscript application/ps application/x-postscript application/x-ps text/postscript','PostScript',''),
(23,'smi','application/smil','SMIL Multimedia',''),
(24,'smil','application/smil','Synchronized Multimedia Integration Language',''),
(25,'wmlc','application/vnd.wap.wmlc ','Compiled WML Document',''),
(26,'wmlsc','application/vnd.wap.wmlscriptc','Compiled WML Script',''),
(27,'vcd','application/x-cdlink','Virtual CD-ROM CD Image File',''),
(28,'pgn','application/formstore','Picatinny Arsenal Electronic Formstore Form in TIFF Format',''),
(29,'cpio','application/x-cpio','UNIX CPIO Archive',''),
(30,'csh','application/x-csh','Csh Script',''),
(31,'dcr','application/x-director','Shockwave Movie',''),
(32,'dir','application/x-director','Macromedia Director Movie',''),
(33,'dxr','application/x-director application/vnd.dxr','Macromedia Director Protected Movie File',''),
(34,'dvi','application/x-dvi','TeX Device Independent Document',''),
(35,'spl','application/x-futuresplash','Macromedia FutureSplash File',''),
(36,'hdf','application/x-hdf','Hierarchical Data Format File',''),
(37,'js','application/x-javascript text/javascript','JavaScript Source Code',''),
(38,'skp','application/x-koan application/vnd-koan koan/x-skm application/vnd.koan','SSEYO Koan Play File',''),
(39,'skd','application/x-koan application/vnd-koan koan/x-skm application/vnd.koan','SSEYO Koan Design File',''),
(40,'skt','application/x-koan application/vnd-koan koan/x-skm application/vnd.koan','SSEYO Koan Template File',''),
(41,'skm','application/x-koan application/vnd-koan koan/x-skm application/vnd.koan','SSEYO Koan Mix File',''),
(42,'latex','application/x-latex text/x-latex','LaTeX Source Document',''),
(43,'nc','application/x-netcdf text/x-cdf','Unidata netCDF Graphics',''),
(44,'cdf','application/cdf application/x-cdf application/netcdf application/x-netcdf text/cdf text/x-cdf','Channel Definition Format',''),
(45,'swf','application/x-shockwave-flash application/x-shockwave-flash2-preview application/futuresplash image/vnd.rn-realflash','Macromedia Flash Format File',''),
(46,'sit','application/stuffit application/x-stuffit application/x-sit','StuffIt Compressed Archive File',''),
(47,'tcl','application/x-tcl','TCL/TK Language Script',''),
(48,'tex','application/x-tex','LaTeX Source',''),
(49,'texinfo','application/x-texinfo','TeX',''),
(50,'texi','application/x-texinfo','TeX',''),
(51,'t','application/x-troff','TAR Tape Archive Without Compression',''),
(52,'tr','application/x-troff','Unix Tape Archive = TAR without compression (tar)',''),
(53,'src','application/x-wais-source','Sourcecode',''),
(54,'xhtml','application/xhtml+xml','Extensible HyperText Markup Language File',''),
(55,'xht','application/xhtml+xml','Extensible HyperText Markup Language File',''),
(56,'au','audio/basic audio/x-basic audio/au audio/x-au audio/x-pn-au audio/rmf audio/x-rmf audio/x-ulaw audio/vnd.qcelp audio/x-gsm audio/snd','ULaw/AU Audio File',''),
(57,'XM','audio/xm audio/x-xm audio/module-xm audio/mod audio/x-mod','Fast Tracker 2 Extended Module',''),
(58,'snd','audio/basic','Macintosh Sound Resource',''),
(59,'mid','audio/mid audio/m audio/midi audio/x-midi application/x-midi audio/soundtrack','Musical Instrument Digital Interface MIDI-sequention Sound',''),
(60,'midi','audio/mid audio/m audio/midi audio/x-midi application/x-midi','Musical Instrument Digital Interface MIDI-sequention Sound',''),
(61,'kar','audio/midi audio/x-midi audio/mid x-music/x-midi','Karaoke MIDI File',''),
(62,'mpga','audio/mpeg audio/mp3 audio/mgp audio/m-mpeg audio/x-mp3 audio/x-mpeg audio/x-mpg video/mpeg','Mpeg-1 Layer3 Audio Stream',''),
(63,'mp2','video/mpeg audio/mpeg','MPEG Audio Stream, Layer II',''),
(64,'mp3','audio/mpeg audio/x-mpeg audio/mp3 audio/x-mp3 audio/mpeg3 audio/x-mpeg3 audio/mpg audio/x-mpg audio/x-mpegaudio','MPEG Audio Stream, Layer III',''),
(65,'aif','audio/aiff audio/x-aiff sound/aiff audio/rmf audio/x-rmf audio/x-pn-aiff audio/x-gsm audio/x-midi audio/vnd.qcelp','Audio Interchange File',''),
(66,'aiff','audio/aiff audio/x-aiff sound/aiff audio/rmf audio/x-rmf audio/x-pn-aiff audio/x-gsm audio/mid audio/x-midi audio/vnd.qcelp','Audio Interchange File',''),
(67,'aifc','audio/aiff audio/x-aiff audio/x-aifc sound/aiff audio/rmf audio/x-rmf audio/x-pn-aiff audio/x-gsm audio/x-midi audio/mid audio/vnd.qcelp','Audio Interchange File',''),
(68,'m3u','audio/x-mpegurl audio/mpeg-url application/x-winamp-playlist audio/scpls audio/x-scpls','MP3 Playlist File',''),
(69,'ram','audio/x-pn-realaudio audio/vnd.rn-realaudio audio/x-pm-realaudio-plugin audio/x-pn-realvideo audio/x-realaudio video/x-pn-realvideo text/plain','RealMedia Metafile',''),
(70,'rm','application/vnd.rn-realmedia audio/vnd.rn-realaudio audio/x-pn-realaudio audio/x-realaudio audio/x-pm-realaudio-plugin','RealMedia Streaming Media',''),
(71,'rpm','audio/x-pn-realaudio audio/x-pn-realaudio-plugin audio/x-pnrealaudio-plugin video/x-pn-realvideo-plugin audio/x-mpegurl application/octet-stream','RealMedia Player Plug-in',''),
(72,'ra','audio/vnd.rn-realaudio audio/x-pn-realaudio audio/x-realaudio audio/x-pm-realaudio-plugin video/x-pn-realvideo','RealMedia Streaming Media',''),
(73,'wav','audio/wav audio/x-wav audio/wave audio/x-pn-wav','Waveform Audio',''),
(74,'wax',' audio/x-ms-wax','Windows Media Audio Redirector',''),
(75,'wma','audio/x-ms-wma video/x-ms-asf','Windows Media Audio File',''),
(76,'bmp','image/bmp image/x-bmp image/x-bitmap image/x-xbitmap image/x-win-bitmap image/x-windows-bmp image/ms-bmp image/x-ms-bmp application/bmp application/x-bmp application/x-win-bitmap application/preview','Windows OS/2 Bitmap Graphics','system'),
(77,'gif','image/gif image/x-xbitmap image/gi_','Graphic Interchange Format','system'),
(78,'ief','image/ief','Image File - Bitmap graphics',''),
(79,'jpeg','image/jpeg image/jpg image/jpe_ image/pjpeg image/vnd.swiftview-jpeg','JPEG/JIFF Image','system'),
(80,'jpg','image/jpeg image/jpg image/jp_ application/jpg application/x-jpg image/pjpeg image/pipeg image/vnd.swiftview-jpeg image/x-xbitmap','JPEG/JIFF Image','system'),
(81,'jpe','image/jpeg','JPEG/JIFF Image','system'),
(82,'png','image/png application/png application/x-png','Portable (Public) Network Graphic','system'),
(83,'tiff','image/tiff','Tagged Image Format File','system'),
(84,'tif','image/tif image/x-tif image/tiff image/x-tiff application/tif application/x-tif application/tiff application/x-tiff','Tagged Image Format File','system'),
(85,'ico','image/ico image/x-icon application/ico application/x-ico application/x-win-bitmap image/x-win-bitmap application/octet-stream','Windows Icon',''),
(86,'wbmp','image/vnd.wap.wbmp','Wireless Bitmap File Format',''),
(87,'ras','application/ras application/x-ras image/ras','Sun Raster Graphic',''),
(88,'pnm','image/x-portable-anymap','PBM Portable Any Map Graphic Bitmap',''),
(89,'pbm','image/portable bitmap image/x-portable-bitmap image/pbm image/x-pbm','UNIX Portable Bitmap Graphic',''),
(90,'pgm','image/x-portable-graymap image/x-pgm','Portable Graymap Graphic',''),
(91,'ppm','image/x-portable-pixmap application/ppm application/x-ppm image/x-p image/x-ppm','PBM Portable Pixelmap Graphic',''),
(92,'rgb','image/rgb image/x-rgb','Silicon Graphics RGB Bitmap',''),
(93,'xbm','image/x-xpixmap image/x-xbitmap image/xpm image/x-xpm','X Bitmap Graphic',''),
(94,'xpm','image/x-xpixmap','BMC Software Patrol UNIX Icon File',''),
(95,'xwd','image/x-xwindowdump image/xwd image/x-xwd application/xwd application/x-xwd','X Windows Dump',''),
(96,'igs','model/iges application/iges application/x-iges application/igs application/x-igs drawing/x-igs image/x-igs','Initial Graphics Exchange Specification Format',''),
(97,'css','application/css-stylesheet text/css','Hypertext Cascading Style Sheet',''),
(98,'html','text/html text/plain','Hypertext Markup Language',''),
(99,'htm','text/html','Hypertext Markup Language',''),
(100,'txt','text/plain application/txt browser/internal','Text File','system'),
(101,'rtf','application/rtf application/x-rtf text/rtf text/richtext application/msword application/doc application/x-soffice','Rich Text Format File','system'),
(102,'wml','text/vnd.wap.wml text/wml','Website META Language File',''),
(103,'wmls','text/vnd.wap.wmlscript','WML Script',''),
(104,'etx','text/x-setext','SetText Structure Enhanced Text',''),
(105,'xml','text/xml application/xml application/x-xml','Extensible Markup Language File',''),
(106,'xsl','text/xml','XML Stylesheet',''),
(107,'php','text/php application/x-httpd-php application/php magnus-internal/shellcgi application/x-php','PHP Script',''),
(108,'php3','text/php3 application/x-httpd-php','PHP Script',''),
(109,'mpeg','video/mpeg','MPEG Movie',''),
(110,'mpg','video/mpeg video/mpg video/x-mpg video/mpeg2 application/x-pn-mpg video/x-mpeg video/x-mpeg2a audio/mpeg audio/x-mpeg image/mpg','MPEG 1 System Stream',''),
(111,'mpe','video/mpeg','MPEG Movie Clip',''),
(112,'qt','video/quicktime audio/aiff audio/x-wav video/flc','QuickTime Movie',''),
(113,'mov','video/quicktime video/x-quicktime image/mov audio/aiff audio/x-midi audio/x-wav video/avi','QuickTime Video Clip',''),
(114,'avi','video/avi video/msvideo video/x-msvideo image/avi video/xmpg2 application/x-troff-msvideo audio/aiff audio/avi','Audio Video Interleave File',''),
(115,'movie','video/sgi-movie video/x-sgi-movie','QuickTime Movie',''),
(116,'asf','audio/asf application/asx video/x-ms-asf-plugin application/x-mplayer2 video/x-ms-asf application/vnd.ms-asf video/x-ms-asf-plugin video/x-ms-wm video/x-ms-wmx','Advanced Streaming Format',''),
(117,'asx','video/asx application/asx video/x-ms-asf-plugin application/x-mplayer2 video/x-ms-asf application/vnd.ms-asf video/x-ms-asf-plugin video/x-ms-wm video/x-ms-wmx video/x-la-asf','Advanced Stream Redirector File',''),
(118,'wmv','video/x-ms-wmv','Windows Media File',''),
(119,'wvx','video/x-ms-wvx','Windows Media Redirector',''),
(120,'wm','video/x-ms-wm','Windows Media A/V File',''),
(121,'wmx','video/x-ms-wmx','Windows Media Player A/V Shortcut',''),
(122,'ice','x-conference-xcooltalk','Cooltalk Audio',''),
(123,'rar','application/octet-stream','WinRAR Compressed Archive','');
/*!40000 ALTER TABLE `ai8k7Bba_system_mimetype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_system_rating`
--

DROP TABLE IF EXISTS `ai8k7Bba_system_rating`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_system_rating` (
  `ratingid` int(11) NOT NULL AUTO_INCREMENT,
  `dirname` varchar(255) NOT NULL,
  `item` varchar(255) NOT NULL,
  `itemid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `rate` int(1) NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`ratingid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_system_rating`
--

LOCK TABLES `ai8k7Bba_system_rating` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_system_rating` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_system_rating` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_tfa_codes`
--

DROP TABLE IF EXISTS `ai8k7Bba_tfa_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_tfa_codes` (
  `code_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `method` tinyint(1) unsigned DEFAULT NULL,
  PRIMARY KEY (`code_id`),
  KEY `i_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_tfa_codes`
--

LOCK TABLES `ai8k7Bba_tfa_codes` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_tfa_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_tfa_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_tplfile`
--

DROP TABLE IF EXISTS `ai8k7Bba_tplfile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_tplfile` (
  `tpl_id` mediumint(7) unsigned NOT NULL AUTO_INCREMENT,
  `tpl_refid` smallint(5) unsigned NOT NULL DEFAULT 0,
  `tpl_module` varchar(25) NOT NULL DEFAULT '',
  `tpl_tplset` varchar(50) NOT NULL DEFAULT '',
  `tpl_file` varchar(50) NOT NULL DEFAULT '',
  `tpl_desc` varchar(255) NOT NULL DEFAULT '',
  `tpl_lastmodified` int(10) unsigned NOT NULL DEFAULT 0,
  `tpl_lastimported` int(10) unsigned NOT NULL DEFAULT 0,
  `tpl_type` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`tpl_id`),
  KEY `tpl_refid` (`tpl_refid`,`tpl_type`),
  KEY `tpl_tplset` (`tpl_tplset`,`tpl_file`(10))
) ENGINE=InnoDB AUTO_INCREMENT=204 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_tplfile`
--

LOCK TABLES `ai8k7Bba_tplfile` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_tplfile` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_tplfile` VALUES
(1,1,'system','default','system_userinfo.html','',1671220912,1671220912,'module'),
(2,1,'system','default','system_userform.html','',1671220912,1671220912,'module'),
(3,1,'system','default','system_rss.html','',1671220912,1671220912,'module'),
(4,1,'system','default','system_comment.html','',1671220912,1671220912,'module'),
(5,1,'system','default','system_comments_flat.html','',1671220912,1671220912,'module'),
(6,1,'system','default','system_comments_thread.html','',1671220912,1671220912,'module'),
(7,1,'system','default','system_comments_nest.html','',1671220912,1671220912,'module'),
(8,1,'system','default','system_siteclosed.html','',1671220912,1671220912,'module'),
(9,1,'system','default','system_redirect.html','',1671220912,1671220912,'module'),
(10,1,'system','default','system_dummy.html','',1671220912,1671220912,'module'),
(11,1,'system','default','system_notification_list.html','',1671220912,1671220912,'module'),
(12,1,'system','default','system_notification_select.html','',1671220912,1671220912,'module'),
(13,1,'system','default','system_block_dummy.html','',1671220912,1671220912,'module'),
(14,1,'system','default','system_privpolicy.html','',1671220912,1671220912,'module'),
(15,1,'system','default','system_error.html','',1671220912,1671220912,'module'),
(16,1,'system','default','system_openid.html','',1671220912,1671220912,'module'),
(17,1,'system','default','admin/blockspadmin/system_adm_blockspadmin.html','',1671220912,1671220912,'module'),
(18,1,'system','default','admin/pages/system_adm_pagemanager_index.html','',1671220912,1671220912,'module'),
(19,1,'system','default','admin/blocksadmin/system_adm_blocksadmin.html','',1671220912,1671220912,'module'),
(20,1,'system','default','admin/modulesadmin/system_adm_modulesadmin.html','',1671220912,1671220912,'module'),
(21,1,'system','default','system_common_form.html','',1671220912,1671220912,'module'),
(22,1,'system','default','system_persistabletable_display.html','',1671220912,1671220912,'module'),
(23,1,'system','default','admin/customtag/system_adm_customtag.html','',1671220912,1671220912,'module'),
(24,1,'system','default','system_default_form.html','',1671220912,1671220912,'module'),
(25,1,'system','default','admin/images/system_adm_imagemanager.html','',1671220912,1671220912,'module'),
(26,1,'system','default','admin/images/system_adm_imagemanager_imglist.html','',1671220912,1671220912,'module'),
(27,1,'system','default','admin/images/system_adm_imagemanager_img.html','',1671220912,1671220912,'module'),
(28,1,'system','default','admin/images/system_adm_imagemanager_editimg.html','',1671220912,1671220912,'module'),
(29,1,'system','default','admin/images/system_adm_imagemanager_cloneimg.html','',1671220912,1671220912,'module'),
(30,1,'system','default','admin/system_adm_rss.html','',1671220912,1671220912,'module'),
(31,1,'system','default','system_search.html','',1671220912,1671220912,'module'),
(32,1,'system','default','system_persistable_singleview.html','',1671220912,1671220912,'module'),
(33,1,'system','default','system_breadcrumb.html','',1671220912,1671220912,'module'),
(34,1,'system','default','admin/adsense/system_adm_adsense.html','',1671220912,1671220912,'module'),
(35,1,'system','default','system_print.html','',1671220912,1671220912,'module'),
(36,1,'system','default','admin/rating/system_adm_rating.html','',1671220912,1671220912,'module'),
(37,1,'system','default','system_rating_form.html','',1671220912,1671220912,'module'),
(38,1,'system','default','admin/mimetype/system_adm_mimetype.html','',1671220912,1671220912,'module'),
(39,1,'system','default','admin/userrank/system_adm_userrank.html','',1671220912,1671220912,'module'),
(40,1,'system','default','admin/autotasks/system_adm_autotasks.html','',1671220912,1671220912,'module'),
(41,1,'system','default','system_block_user.html','',1671220912,1671220912,'block'),
(42,2,'system','default','system_block_login.html','',1671220912,1671220912,'block'),
(43,3,'system','default','system_block_search.html','',1671220912,1671220912,'block'),
(44,4,'system','default','system_block_waiting.html','',1671220912,1671220912,'block'),
(45,5,'system','default','system_block_mainmenu.html','',1671220912,1671220912,'block'),
(46,6,'system','default','system_block_siteinfo.html','',1671220912,1671220912,'block'),
(47,7,'system','default','system_block_online.html','',1671220912,1671220912,'block'),
(48,8,'system','default','system_block_topusers.html','',1671220912,1671220912,'block'),
(49,9,'system','default','system_block_newusers.html','',1671220912,1671220912,'block'),
(50,10,'system','default','system_block_comments.html','',1671220912,1671220912,'block'),
(51,11,'system','default','system_block_notification.html','',1671220912,1671220912,'block'),
(52,12,'system','default','system_block_themes.html','',1671220912,1671220912,'block'),
(53,13,'system','default','system_block_multilanguage.html','',1671220912,1671220912,'block'),
(54,14,'system','default','system_block_socialbookmark.html','',1671220912,1671220912,'block'),
(55,15,'system','default','system_admin_block_warnings.html','',1671220912,1671220912,'block'),
(56,16,'system','default','system_admin_block_cp.html','',1671220912,1671220912,'block'),
(57,17,'system','default','system_admin_block_modules.html','',1671220912,1671220912,'block'),
(58,18,'system','default','system_block_bookmarks.html','Things I have bookmarked',1671220912,1671220912,'block'),
(59,19,'system','default','system_admin_block_cp_new.html','',1671220912,1671220912,'block'),
(60,2,'profile','default','profile_admin_audio.html','',1671220918,0,'module'),
(61,2,'profile','default','profile_admin_category.html','',1671220918,0,'module'),
(62,2,'profile','default','profile_admin_field.html','',1671220918,0,'module'),
(63,2,'profile','default','profile_admin_pictures.html','',1671220918,0,'module'),
(64,2,'profile','default','profile_admin_regstep.html','',1671220918,0,'module'),
(65,2,'profile','default','profile_admin_tribes.html','',1671220918,0,'module'),
(66,2,'profile','default','profile_admin_tribeuser.html','',1671220918,0,'module'),
(67,2,'profile','default','profile_admin_videos.html','',1671220918,0,'module'),
(68,2,'profile','default','profile_admin_visibility.html','',1671220918,0,'module'),
(69,2,'profile','default','profile_audio.html','',1671220918,0,'module'),
(70,2,'profile','default','profile_changemail.html','',1671220918,0,'module'),
(71,2,'profile','default','profile_changepass.html','',1671220918,0,'module'),
(72,2,'profile','default','profile_configs.html','',1671220918,0,'module'),
(73,2,'profile','default','profile_footer.html','',1671220918,0,'module'),
(74,2,'profile','default','profile_friendship.html','',1671220918,0,'module'),
(75,2,'profile','default','profile_header.html','',1671220918,0,'module'),
(76,2,'profile','default','profile_index.html','',1671220918,0,'module'),
(77,2,'profile','default','profile_pictures.html','',1671220918,0,'module'),
(78,2,'profile','default','profile_register.html','',1671220918,0,'module'),
(79,2,'profile','default','profile_requirements.html','',1671220918,0,'module'),
(80,2,'profile','default','profile_results.html','',1671220918,0,'module'),
(81,2,'profile','default','profile_search.html','',1671220918,0,'module'),
(82,2,'profile','default','profile_tribes.html','',1671220918,0,'module'),
(83,2,'profile','default','profile_userinfo.html','',1671220918,0,'module'),
(84,2,'profile','default','profile_videos.html','',1671220918,0,'module'),
(85,22,'profile','default','profile_block_friends.html','',1671220918,0,'block'),
(86,23,'profile','default','profile_block_usermenu.html','',1671220918,0,'block'),
(87,3,'content','default','content_header.html','Module Header',1671220918,0,'module'),
(88,3,'content','default','content_footer.html','Module Footer',1671220918,0,'module'),
(89,3,'content','default','content_admin_content.html','Content Index',1671220918,0,'module'),
(90,3,'content','default','content_index.html','Content Index',1671220918,0,'module'),
(91,3,'content','default','content_single_content.html','Single content template',1671220918,0,'module'),
(92,3,'content','default','content_content.html','Content page',1671220918,0,'module'),
(93,3,'content','default','content_requirements.html','Content page',1671220918,0,'module'),
(94,3,'content','default','content_content_menu_structure.html','Structure used to create recursive menu.',1671220918,0,'module'),
(95,24,'content','default','content_content_display.html','Display the desired content page with some defined configurations.',1671220918,0,'block'),
(96,25,'content','default','content_content_menu.html','Show a block with a menu of content pages.',1671220918,0,'block'),
(97,4,'formulize','default','admin/element_type_anonPasscode.html','',1671220918,0,'module'),
(98,4,'formulize','default','admin/element_type_checkbox.html','',1671220918,0,'module'),
(99,4,'formulize','default','admin/element_type_email.html','',1671220918,0,'module'),
(100,4,'formulize','default','admin/element_type_fileUpload.html','',1671220918,0,'module'),
(101,4,'formulize','default','admin/element_type_googleAddress.html','',1671220918,0,'module'),
(102,4,'formulize','default','admin/element_type_googleFilePicker.html','',1671220918,0,'module'),
(103,4,'formulize','default','admin/element_type_newslider.html','',1671220918,0,'module'),
(104,4,'formulize','default','admin/element_type_phone.html','',1671220918,0,'module'),
(105,4,'formulize','default','admin/element_type_provinceList.html','',1671220918,0,'module'),
(106,4,'formulize','default','admin/element_type_slider.html','',1671220918,0,'module'),
(107,4,'formulize','default','admin/element_type_time.html','',1671220918,0,'module'),
(108,4,'formulize','default','formulize_cat.html','',1671220918,0,'module'),
(109,4,'formulize','default','formulize_application.html','',1671220918,0,'module'),
(110,4,'formulize','default','calendar_month.html','',1671220918,0,'module'),
(111,4,'formulize','default','calendar_mini_month.html','',1671220918,0,'module'),
(112,4,'formulize','default','calendar_micro_month.html','',1671220918,0,'module'),
(113,4,'formulize','default','admin/ui.html','',1671220918,0,'module'),
(114,4,'formulize','default','admin/ui-tabs.html','',1671220918,0,'module'),
(115,4,'formulize','default','admin/ui-accordion.html','',1671220918,0,'module'),
(116,4,'formulize','default','admin/application_settings.html','',1671220918,0,'module'),
(117,4,'formulize','default','admin/application_forms.html','',1671220918,0,'module'),
(118,4,'formulize','default','admin/application_menu_entries.html','',1671220918,0,'module'),
(119,4,'formulize','default','admin/application_code.html','',1671220918,0,'module'),
(120,4,'formulize','default','admin/application_menu_entries_sections.html','',1671220918,0,'module'),
(121,4,'formulize','default','admin/application_screens.html','',1671220918,0,'module'),
(122,4,'formulize','default','admin/form_listing.html','',1671220918,0,'module'),
(123,4,'formulize','default','admin/form_settings.html','',1671220918,0,'module'),
(124,4,'formulize','default','admin/form_permissions.html','',1671220918,0,'module'),
(125,4,'formulize','default','admin/form_screens.html','',1671220918,0,'module'),
(126,4,'formulize','default','admin/form_elements.html','',1671220918,0,'module'),
(127,4,'formulize','default','admin/form_elements_sections.html','',1671220918,0,'module'),
(128,4,'formulize','default','admin/form_advanced_calculations.html','',1671220918,0,'module'),
(129,4,'formulize','default','admin/application_relationships.html','',1671220918,0,'module'),
(130,4,'formulize','default','admin/application_relationships_sections.html','',1671220918,0,'module'),
(131,4,'formulize','default','admin/relationship_settings.html','',1671220918,0,'module'),
(132,4,'formulize','default','admin/relationship_common_values.html','',1671220918,0,'module'),
(133,4,'formulize','default','admin/screen_settings.html','',1671220918,0,'module'),
(134,4,'formulize','default','admin/screen_relationships.html','',1671220918,0,'module'),
(135,4,'formulize','default','admin/element_names.html','',1671220918,0,'module'),
(136,4,'formulize','default','admin/element_options.html','',1671220918,0,'module'),
(137,4,'formulize','default','admin/element_display.html','',1671220918,0,'module'),
(138,4,'formulize','default','admin/element_advanced.html','',1671220918,0,'module'),
(139,4,'formulize','default','admin/element_type_checkbox.html','',1671220918,0,'module'),
(140,4,'formulize','default','admin/element_type_date.html','',1671220918,0,'module'),
(141,4,'formulize','default','admin/element_type_derived.html','',1671220918,0,'module'),
(142,4,'formulize','default','admin/element_type_grid.html','',1671220918,0,'module'),
(143,4,'formulize','default','admin/element_type_areamodif.html','',1671220918,0,'module'),
(144,4,'formulize','default','admin/element_type_ib.html','',1671220918,0,'module'),
(145,4,'formulize','default','admin/element_type_radio.html','',1671220918,0,'module'),
(146,4,'formulize','default','admin/element_type_select.html','',1671220918,0,'module'),
(147,4,'formulize','default','admin/element_type_sep.html','',1671220918,0,'module'),
(148,4,'formulize','default','admin/element_type_subform.html','',1671220918,0,'module'),
(149,4,'formulize','default','admin/element_type_textarea.html','',1671220919,0,'module'),
(150,4,'formulize','default','admin/element_type_text.html','',1671220919,0,'module'),
(151,4,'formulize','default','admin/element_type_yn.html','',1671220919,0,'module'),
(152,4,'formulize','default','admin/home.html','',1671220919,0,'module'),
(153,4,'formulize','default','admin/home_sections.html','',1671220919,0,'module'),
(154,4,'formulize','default','admin/screen_list_entries.html','',1671220919,0,'module'),
(155,4,'formulize','default','admin/screen_list_custom.html','',1671220919,0,'module'),
(156,4,'formulize','default','admin/screen_list_custom_sections.html','',1671220919,0,'module'),
(157,4,'formulize','default','admin/screen_form_options.html','',1671220919,0,'module'),
(158,4,'formulize','default','admin/screen_list_buttons.html','',1671220919,0,'module'),
(159,4,'formulize','default','admin/screen_list_templates.html','',1671220919,0,'module'),
(160,4,'formulize','default','admin/screen_list_headings.html','',1671220919,0,'module'),
(161,4,'formulize','default','admin/screen_multipage_options.html','',1671220919,0,'module'),
(162,4,'formulize','default','admin/screen_multipage_text.html','',1671220919,0,'module'),
(163,4,'formulize','default','admin/screen_multipage_pages.html','',1671220919,0,'module'),
(164,4,'formulize','default','admin/screen_multipage_pages_sections.html','',1671220919,0,'module'),
(165,4,'formulize','default','admin/screen_multipage_pages_settings.html','',1671220919,0,'module'),
(166,4,'formulize','default','admin/screen_multipage_templates.html','',1671220919,0,'module'),
(167,4,'formulize','default','admin/screen_template_options.html','',1671220919,0,'module'),
(168,4,'formulize','default','admin/screen_template_templates.html','',1671220919,0,'module'),
(169,4,'formulize','default','admin/element_optionlist.html','',1671220919,0,'module'),
(170,4,'formulize','default','admin/element_linkedoptionlist.html','',1671220919,0,'module'),
(171,4,'formulize','default','admin/element_linkedfilter.html','',1671220919,0,'module'),
(172,4,'formulize','default','admin/element_linkedsortoptions.html','',1671220919,0,'module'),
(173,4,'formulize','default','admin/advanced_calculation_settings.html','',1671220919,0,'module'),
(174,4,'formulize','default','admin/advanced_calculation_input_output.html','',1671220919,0,'module'),
(175,4,'formulize','default','admin/advanced_calculation_steps.html','',1671220919,0,'module'),
(176,4,'formulize','default','admin/advanced_calculation_steps_sections.html','',1671220919,0,'module'),
(177,4,'formulize','default','admin/advanced_calculation_fltr_grp.html','',1671220919,0,'module'),
(178,4,'formulize','default','admin/advanced_calculation_fltr_grp_sections.html','',1671220919,0,'module'),
(179,4,'formulize','default','admin/import_template.html','',1671220919,0,'module'),
(180,4,'formulize','default','admin/export_template.html','',1671220919,0,'module'),
(181,4,'formulize','default','admin/synchronize.html','',1671220919,0,'module'),
(182,4,'formulize','default','admin/synchronize_sections.html','',1671220919,0,'module'),
(183,4,'formulize','default','admin/sync_import.html','',1671220919,0,'module'),
(184,4,'formulize','default','admin/sync_import_sections.html','',1671220919,0,'module'),
(185,4,'formulize','default','admin/managekeys.html','',1671220919,0,'module'),
(186,4,'formulize','default','admin/managetokens.html','',1671220919,0,'module'),
(187,4,'formulize','default','admin/element_options_delimiter_choice.html','',1671220919,0,'module'),
(188,4,'formulize','default','admin/screen_calendar_data_sections.html','',1671220919,0,'module'),
(189,4,'formulize','default','admin/screen_calendar_data.html','',1671220919,0,'module'),
(190,4,'formulize','default','admin/screen_calendar_templates.html','',1671220919,0,'module'),
(191,4,'formulize','default','passcode.html','',1671220919,0,'module'),
(192,4,'formulize','default','admin/multipage_navigation2-above.html','',1671220919,0,'module'),
(193,4,'formulize','default','admin/multipage_navigation2-below.html','',1671220919,0,'module'),
(194,4,'formulize','default','admin/multipage_navigation3-above.html','',1671220919,0,'module'),
(195,4,'formulize','default','admin/multipage_navigation3-below.html','',1671220919,0,'module'),
(196,4,'formulize','default','admin/alternate_fields_for_linked_elements.html','',1671220919,0,'module'),
(197,4,'formulize','default','admin/screen_form_templates.html','',1671220919,0,'module'),
(198,4,'formulize','default','admin/screen_form_template_boxes.html','',1671220919,0,'module'),
(199,4,'formulize','default','blocks/menu.html','',1671220919,0,'module'),
(200,4,'formulize','default','blocks/menu_controller.html','',1671220919,0,'module'),
(201,4,'formulize','default','admin/mailusers.html','',1671220919,0,'module'),
(202,4,'formulize','default','admin/managepermissions.html','',1671220919,0,'module'),
(203,26,'formulize','default','menu_controller.html','',1671220919,0,'block');
/*!40000 ALTER TABLE `ai8k7Bba_tplfile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_tplset`
--

DROP TABLE IF EXISTS `ai8k7Bba_tplset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_tplset` (
  `tplset_id` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `tplset_name` varchar(50) NOT NULL DEFAULT '',
  `tplset_desc` varchar(255) NOT NULL DEFAULT '',
  `tplset_credits` text NOT NULL,
  `tplset_created` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`tplset_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_tplset`
--

LOCK TABLES `ai8k7Bba_tplset` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_tplset` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_tplset` VALUES
(1,'default','ImpressCMS Default Template Set','',1671220912);
/*!40000 ALTER TABLE `ai8k7Bba_tplset` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_tplsource`
--

DROP TABLE IF EXISTS `ai8k7Bba_tplsource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_tplsource` (
  `tpl_id` mediumint(7) unsigned NOT NULL DEFAULT 0,
  `tpl_source` mediumtext NOT NULL,
  KEY `tpl_id` (`tpl_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_tplsource`
--

LOCK TABLES `ai8k7Bba_tplsource` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_tplsource` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_tplsource` VALUES
(1,'<{if $user_ownpage == true}>\n\n<form id=\"usernav\" action=\"user.php\" method=\"post\">\n\n<table width=\"70%\" style=\"margin: 0 auto;\" border=\"0\">\n  <tr align=\"center\">\n    <td><input type=\"button\" value=\"<{$lang_editprofile}>\" onclick=\"location=\'edituser.php\'\" />\n    <input type=\"button\" value=\"<{$lang_avatar}>\" onclick=\"location=\'edituser.php?op=avatarform\'\" />\n    <input type=\"button\" value=\"<{$lang_inbox}>\" onclick=\"location=\'viewpmsg.php\'\" />\n\n    <{if $user_candelete == true}>\n    <input type=\"button\" value=\"<{$lang_deleteaccount}>\" onclick=\"location=\'user.php?op=delete\'\" />\n    <{/if}>\n\n    <input type=\"button\" value=\"<{$lang_logout}>\" onclick=\"location=\'user.php?op=logout\'\" /></td>\n  </tr>\n</table>\n</form>\n\n<{elseif $xoops_isadmin != false}>\n\n\n<table width=\"70%\" style=\"margin: 0 auto;\" border=\"0\">\n  <tr align=\"center\">\n    <td><input type=\"button\" value=\"<{$lang_editprofile}>\" onclick=\"location=\'<{$xoops_url}>/modules/system/admin.php?fct=users&uid=<{$user_uid}>&op=modifyUser\'\" />\n    <input type=\"button\" value=\"<{$lang_deleteaccount}>\" onclick=\"location=\'<{$xoops_url}>/modules/system/admin.php?fct=users&op=delUser&uid=<{$user_uid}>\'\" />\n    </td>\n  </tr>\n</table>\n\n<{/if}>\n\n<table width=\"100%\" border=\"0\" cellspacing=\"5px\">\n  <tr valign=\"top\">\n    <td style=\"width: 50%;\">\n      <table class=\"outer\" cellpadding=\"4px\" cellspacing=\"1px\" width=\"100%\">\n        <tr>\n          <th colspan=\"2\" align=\"center\"><{$lang_allaboutuser}></th>\n        </tr>\n        <tr valign=\"top\">\n          <td class=\"head\"><{$lang_avatar}></td>\n          <td align=\"center\" class=\"even\"><img src=\"<{$user_avatarurl}>\" alt=\"Avatar\" /></td>\n        </tr>\n        <tr>\n          <td class=\"head\"><{$lang_realname}></td>\n          <td align=\"center\" class=\"odd\"><{$user_realname}></td>\n        </tr>\n        <tr>\n          <td class=\"head\"><{$lang_website}></td>\n          <td class=\"even\"><{$user_websiteurl}></td>\n        </tr>\n        <tr valign=\"top\">\n          <td class=\"head\"><{$lang_email}></td>\n          <td class=\"odd\"><{$user_email}></td>\n        </tr>\n    <{if $user_alwopenid == true}>\n        <tr valign=\"top\">\n          <td class=\"head\"><{$lang_openid}></td>\n          <td class=\"odd\"><{$user_openid}></td>\n        </tr>\n    <{/if}>\n	<tr valign=\"top\">\n          <td class=\"head\"><{$lang_privmsg}></td>\n          <td class=\"even\"><{$user_pmlink}></td>\n        </tr>\n        <tr valign=\"top\">\n          <td class=\"head\"><{$lang_icq}></td>\n          <td class=\"odd\"><{$user_icq}></td>\n        </tr>\n        <tr valign=\"top\">\n          <td class=\"head\"><{$lang_aim}></td>\n          <td class=\"even\"><{$user_aim}></td>\n        </tr>\n        <tr valign=\"top\">\n          <td class=\"head\"><{$lang_yim}></td>\n          <td class=\"odd\"><{$user_yim}></td>\n        </tr>\n        <tr valign=\"top\">\n          <td class=\"head\"><{$lang_msnm}></td>\n          <td class=\"even\"><{$user_msnm}></td>\n        </tr>\n        <tr valign=\"top\">\n          <td class=\"head\"><{$lang_location}></td>\n          <td class=\"odd\"><{$user_location}></td>\n        </tr>\n        <tr valign=\"top\">\n          <td class=\"head\"><{$lang_occupation}></td>\n          <td class=\"even\"><{$user_occupation}></td>\n        </tr>\n        <tr valign=\"top\">\n          <td class=\"head\"><{$lang_interest}></td>\n          <td class=\"odd\"><{$user_interest}></td>\n        </tr>\n        <tr valign=\"top\">\n          <td class=\"head\"><{$lang_extrainfo}></td>\n          <td class=\"even\"><{$user_extrainfo}></td>\n        </tr>\n      </table>\n    </td>\n    <td style=\"width: 50%;\">\n      <table class=\"outer\" cellpadding=\"4px\" cellspacing=\"1px\" width=\"100%\">\n        <tr valign=\"top\">\n          <th colspan=\"2\" align=\"center\"><{$lang_statistics}></th>\n        </tr>\n        <tr valign=\"top\">\n          <td class=\"head\"><{$lang_membersince}></td>\n          <td align=\"center\" class=\"even\"><{$user_joindate}></td>\n        </tr>\n        <tr valign=\"top\">\n          <td class=\"head\"><{$lang_rank}></td>\n          <td align=\"center\" class=\"odd\"><{if $user_rankimage}><{$user_rankimage}><br /><{/if}><{$user_ranktitle}></td>\n        </tr>\n        <tr valign=\"top\">\n          <td class=\"head\"><{$lang_posts}></td>\n          <td align=\"center\" class=\"even\"><{$user_posts}></td>\n        </tr>\n	<tr valign=\"top\">\n          <td class=\"head\"><{$lang_lastlogin}></td>\n          <td align=\"center\" class=\"odd\"><{$user_lastlogin}></td>\n        </tr>\n      </table>\n      <br />\n    <{if $user_showsignature == true}>\n      <table class=\"outer\" cellpadding=\"4px\" cellspacing=\"1px\" width=\"100%\">\n        <tr valign=\"top\">\n          <th colspan=\"2\" align=\"center\"><{$lang_signature}></th>\n        </tr>\n        <tr valign=\"top\">\n          <td class=\"even\"><{$user_signature}></td>\n        </tr>\n      </table>\n    <{/if}>\n    </td>\n  </tr>\n</table>\n\n<!-- start module search results loop -->\n<{foreach item=module from=$modules}>\n\n<h4><{$module.name}></h4>\n<p>\n  <!-- start results item loop -->\n  <{foreach item=result from=$module.results}>\n\n  <img src=\"<{$result.image}>\" alt=\"<{$module.name}>\" /><b><a href=\"<{$result.link}>\" title=\"<{$result.title}>\"><{$result.title}></a></b><br /><small>(<{$result.time}>)</small><br />\n\n  <{/foreach}>\n  <!-- end results item loop -->\n\n<{$module.showall_link}>\n</p>\n\n<{/foreach}>\n<!-- end module search results loop -->\n'),
(2,'<div style=\"position: relative;\">\n	<div>\n		<fieldset style=\"padding: 10px;\">\n			<legend style=\"font-weight: bold; font-size: 150%;\"><{$lang_login}></legend>\n<div id=\"icms_block_login_form\">\n			<form action=\"user.php\" method=\"post\">\n		    	<div><{$lang_username}><input type=\"text\" class=\"uname\" name=\"uname\" size=\"21\" maxlength=\"255\" value=\"<{$usercookie}>\" />\n		    	<br />\n			    <br /></div>\n		    	<div><{$lang_password}><input type=\"password\" name=\"pass\" size=\"21\" maxlength=\"255\" /><br /></div>\n			    <{if $rememberme }>\n			    	<div><input type=\"checkbox\" name=\"rememberme\" value=\"On\" /><{$lang_rememberme}><br /></div>\n			    <{/if}>\n			    <div><input type=\"hidden\" name=\"op\" value=\"login\" /></div>\n				<input type=\"hidden\" id=\"tfacode\" name=\"tfacode\" value=\"\" />\n				<input type=\"hidden\" id=\"tfaremember\" name=\"tfaremember\" value=\"\" />\n		    	<div><input type=\"hidden\" name=\"xoops_redirect\" value=\"<{$redirect_page}>\"/>\n				\n			    <br />\n		    	<input type=\"submit\" value=\"<{$lang_login}>\" /></div>\n			</form>\n			<{php}>include_once XOOPS_ROOT_PATH.\'/include/2fa/manage.php\';print tfaLoginJS(\'icms_block_login_form\');<{/php}>\n  <{if $auth_openid}>\n<br />\n	  <div style=\"text-align: <{$smarty.const._GLOBAL_LEFT}>;\"><a href=\"<{$auth_url}>\"><{$lang_login_oid}></a></div>\n  <{/if}>\n<{if $auth_okta}>\n<br />\n	  <div style=\"text-align: <{$smarty.const._GLOBAL_LEFT}>;\"><a href=\"<{$auth_okta}>\"><{$lang_login_okta}></a></div>\n  <{/if}>\n</div>\n			<a name=\"lost\"></a>\n			<{if $allow_registration }>\n				<div style=\"text-align: <{$smarty.const._GLOBAL_RIGHT}>;\"><{$lang_notregister}><br /></div>\n			<{/if}>\n		</fieldset>\n	</div>\n</div>\n\n'),
(3,'<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<rss version=\"2.0\">\n  <channel>\n    <title><{$channel_title}></title>\n    <link><{$channel_link}></link>\n    <description><{$channel_desc}></description>\n	<copyright><{$channel_copyright}></copyright>\n    <lastBuildDate><{$channel_lastbuild}></lastBuildDate>\n    <docs>http://backend.userland.com/rss/</docs>\n    <generator><{$channel_generator}></generator>\n    <category><{$channel_category}></category>\n    <managingEditor><{$channel_editor}></managingEditor>\n    <webMaster><{$channel_webmaster}></webMaster>\n	<ttl><{$channel_ttl}></ttl>\n    <language><{$channel_language}></language>\n    <{if $image_url != \"\"}>\n    <image>\n      <title><{$channel_title}></title>\n      <url><{$image_url}></url>\n      <link><{$channel_link}></link>\n      <width><{$channel_width}></width>\n      <height><{$channel_height}></height>\n    </image>\n    <{/if}>\n    <{foreach item=item from=$items}>\n    <item>\n      <title><{$item.title}></title>\n      <link><{$item.link}></link>\n      <description><{$item.description}></description>\n      <pubDate><{$item.pubdate}></pubDate>\n      <guid><{$item.guid}></guid>\n	  <category><{$item.category}></category>\n	  <author><{$item.author}></author>\n    </item>\n    <{/foreach}>\n  </channel>\n</rss>'),
(4,'<!-- start comment post -->\n        <tr>\n          <td class=\"head\"><a id=\"comment<{$comment.id}>\"></a> <{$comment.poster.uname}></td>\n          <td class=\"head\"><div class=\"comDate\"><span class=\"comDateCaption\"><{$lang_posted}>:</span> <{$comment.date_posted}>&nbsp;&nbsp;<span class=\"comDateCaption\"><{$lang_updated}>:</span> <{$comment.date_modified}></div></td>\n        </tr>\n        <tr>\n\n          <{if $comment.poster.id != 0}>\n\n          <td class=\"odd\"><div class=\"comUserRank\"><div class=\"comUserRankText\"><{$comment.poster.rank_title}></div><img class=\"comUserRankImg\" src=\"<{$comment.poster.rank_image}>\" alt=\"\" /></div><img class=\"comUserImg\" src=\"<{$comment.poster.avatar}>\" alt=\"\" /><div class=\"comUserStat\"><span class=\"comUserStatCaption\"><{$lang_joined}>:</span> <{$comment.poster.regdate}></div><div class=\"comUserStat\"><span class=\"comUserStatCaption\"><{$lang_from}>:</span> <{$comment.poster.from}></div><div class=\"comUserStat\"><span class=\"comUserStatCaption\"><{$lang_posts}>:</span> <{$comment.poster.postnum}></div><div class=\"comUserStatus\"><{$comment.poster.status}></div></td>\n\n          <{else}>\n\n          <td class=\"odd\"> </td>\n\n          <{/if}>\n\n          <td class=\"odd\">\n            <div class=\"comTitle\"><{$comment.image}><{$comment.title}></div><div class=\"comText\"><{$comment.text}></div>\n          </td>\n        </tr>\n        <tr>\n          <td class=\"even\"></td>\n\n          <{if $xoops_iscommentadmin == true}>\n\n          <td class=\"even\" align=\"<{$smarty.const._GLOBAL_RIGHT}>\">\n            <a href=\"<{$editcomment_link}>&amp;com_id=<{$comment.id}>\" title=\"<{$lang_edit}>\"><img src=\"<{$xoops_url}>/images/icons/<{$icms_langname}>/edit.gif\" alt=\"<{$lang_edit}>\" /></a><a href=\"<{$deletecomment_link}>&amp;com_id=<{$comment.id}>\" title=\"<{$lang_delete}>\"><img src=\"<{$xoops_url}>/images/icons/<{$icms_langname}>/delete.gif\" alt=\"<{$lang_delete}>\" /></a><a href=\"<{$replycomment_link}>&amp;com_id=<{$comment.id}>\" title=\"<{$lang_reply}>\"><img src=\"<{$xoops_url}>/images/icons/<{$icms_langname}>/reply.gif\" alt=\"<{$lang_reply}>\" /></a>\n          </td>\n\n          <{elseif $xoops_isuser == true && $xoops_userid == $comment.poster.id}>\n\n          <td class=\"even\" align=\"<{$smarty.const._GLOBAL_RIGHT}>\">\n            <a href=\"<{$editcomment_link}>&amp;com_id=<{$comment.id}>\" title=\"<{$lang_edit}>\"><img src=\"<{$xoops_url}>/images/icons/<{$icms_langname}>/edit.gif\" alt=\"<{$lang_edit}>\" /></a><a href=\"<{$replycomment_link}>&amp;com_id=<{$comment.id}>\" title=\"<{$lang_reply}>\"><img src=\"<{$xoops_url}>/images/icons/<{$icms_langname}>/reply.gif\" alt=\"<{$lang_reply}>\" /></a>\n          </td>\n\n          <{elseif $xoops_isuser == true || $anon_canpost == true}>\n\n          <td class=\"even\" align=\"<{$smarty.const._GLOBAL_RIGHT}>\">\n            <a href=\"<{$replycomment_link}>&amp;com_id=<{$comment.id}>\" title=\"<{$lang_reply}>\"><img src=\"<{$xoops_url}>/images/icons/<{$icms_langname}>/reply.gif\" alt=\"<{$lang_reply}>\" /></a>\n          </td>\n\n          <{else}>\n\n          <td class=\"even\"> </td>\n\n          <{/if}>\n\n        </tr>\n<!-- end comment post -->'),
(5,'<table class=\"outer\" cellpadding=\"5px\" cellspacing=\"1px\">\n  <tr>\n    <th width=\"20%\"><{$lang_poster}></th>\n    <th><{$lang_thread}></th>\n  </tr>\n  <{foreach item=comment from=$comments}>\n    <{include file=\"db:system_comment.html\" comment=$comment}>\n  <{/foreach}>\n</table>'),
(6,'<{section name=i loop=$comments}>\n<br />\n<table cellspacing=\"1px\" class=\"outer\">\n  <tr>\n    <th width=\"20%\"><{$lang_poster}></th>\n    <th><{$lang_thread}></th>\n  </tr>\n  <{include file=\"db:system_comment.html\" comment=$comments[i]}>\n</table>\n\n<{if $show_threadnav == true}>\n<div style=\"text-align:<{$smarty.const._GLOBAL_LEFT}>; margin:3px; padding: 5px;\">\n<a href=\"<{$comment_url}>\" title=\"<{$lang_top}>\"><{$lang_top}></a> | <a href=\"<{$comment_url}>&amp;com_id=<{$comments[i].pid}>&amp;com_rootid=<{$comments[i].rootid}>#newscomment<{$comments[i].pid}>\" title=\"<{$lang_parent}>\"><{$lang_parent}></a>\n</div>\n<{/if}>\n\n<{if $comments[i].show_replies == true}>\n<!-- start comment tree -->\n<br />\n<table cellspacing=\"1px\" class=\"outer\">\n  <tr>\n    <th width=\"50%\"><{$lang_subject}></th>\n    <th width=\"20%\" align=\"center\"><{$lang_poster}></th>\n    <th align=\"<{$smarty.const._GLOBAL_RIGHT}>\"><{$lang_posted}></th>\n  </tr>\n  <{foreach item=reply from=$comments[i].replies}>\n  <tr>\n    <td class=\"even\"><{$reply.prefix}> <a href=\"<{$comment_url}>&amp;com_id=<{$reply.id}>&amp;com_rootid=<{$reply.root_id}>\" title=\"<{$reply.simple_title}>\"><{$reply.title}></a></td>\n    <td class=\"odd\" align=\"center\"><{$reply.poster.uname}></td>\n    <td class=\"even\" align=\"<{$smarty.const._GLOBAL_RIGHT}>\"><{$reply.date_posted}></td>\n  </tr>\n  <{/foreach}>\n</table>\n<!-- end comment tree -->\n<{/if}>\n\n<{/section}>'),
(7,'<{section name=i loop=$comments}>\n<br />\n<table cellspacing=\"1px\" class=\"outer\">\n  <tr>\n    <th width=\"20%\"><{$lang_poster}></th>\n    <th><{$lang_thread}></th>\n  </tr>\n  <{include file=\"db:system_comment.html\" comment=$comments[i]}>\n</table>\n\n<!-- start comment replies -->\n<{foreach item=reply from=$comments[i].replies}>\n<br />\n<table cellspacing=\"0\" border=\"0\">\n  <tr>\n    <td width=\"<{$reply.prefix}>\"></td>\n    <td>\n      <table class=\"outer\" cellspacing=\"1px\">\n        <tr>\n          <th width=\"20%\"><{$lang_poster}></th>\n          <th><{$lang_thread}></th>\n        </tr>\n        <{include file=\"db:system_comment.html\" comment=$reply}>\n      </table>\n    </td>\n  </tr>\n</table>\n<{/foreach}>\n<!-- end comment tree -->\n<{/section}>'),
(8,'<!DOCTYPE html PUBLIC \'-//W3C//DTD XHTML 1.0 Transitional//EN\' \'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\'>\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"<{$xoops_langcode}>\" lang=\"<{$xoops_langcode}>\">\n<head>\n<meta http-equiv=\"content-type\" content=\"text/html; charset=<{$xoops_charset}>\" />\n<meta http-equiv=\"content-language\" content=\"<{$xoops_langcode}>\" />\n<title><{$xoops_sitename}></title>\n<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"<{$icms_style}>\" />\n<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"<{$icms_themecss}>\" />\n\n</head>\n<body>\n<div id=\"xo-canvas\"<{if $columns_layout}> class=\"<{$columns_layout}>\"<{/if}>>\n  <!-- Start header -->\n    <div id=\"xo-header\">\n	    <div id=\"xo-headerlogo\"></div>\n    </div>  \n  <!-- End header -->\n  \n<!-- Start Main Content Area -->\n<div id=\"xo-canvas-content\">\n<center>\n<br />\n<div style=\"width: 85%;background-color: #f7e6bd; color: #222222; text-align: center; border-top: 1px solid #DDDDFF; border-left: 1px solid #DDDDFF; border-right: 1px solid #DDDDFF; border-bottom: 1px solid #DDDDFF; font-weight: bold; padding: 10px;\"><{$lang_siteclosemsg}>\n</div>\n<br />\n  <br />\n  <br />\n    <div style=\"width: 270px;border:1px solid #DDDDFF\">\n <div style=\"background-color: #f3ac03;font-weight: bold;font-size: 1.2em; color: white;height: 24px\"><{$lang_login}></div>\n    <br />\n   <form action=\"<{$xoops_url}>/user.php\" method=\"post\">\n   <{$lang_username}><input type=\"text\" name=\"uname\" size=\"21\" maxlength=\"25\" value=\"\" /><br />\n    <div>\n    <{$lang_password}><input type=\"password\" name=\"pass\" size=\"21\" maxlength=\"32\" /><br />\n        	<input type=\"hidden\" name=\"xoops_redirect\" value=\"<{$xoops_requesturi}>\" />\n        	<input type=\"hidden\" name=\"xoops_login\" value=\"1\" />\n        <br />\n        	<input type=\"submit\" value=\"<{$lang_login}>\" />\n</div> \n </form>\n<br />\n</div>\n</center>\n</div><!-- Start footer -->\n<br class=\"clear\" />\n<div id=\"xo-footer-close\">\n</div>\n<!-- end Footer -->\n</div>\n  </body>\n</html>'),
(9,'<!DOCTYPE html PUBLIC \'-//W3C//DTD XHTML 1.0 Transitional//EN\' \'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\'>\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"<{$xoops_langcode}>\" lang=\"<{$xoops_langcode}>\">\n<head>\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=<{$xoops_charset}>\" />\n<meta http-equiv=\"Refresh\" content=\"<{$time}>; url=<{$url}>\" />\n<title><{$xoops_sitename}></title>\n<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"<{$icms_themecss}>\" />\n</head>\n<body>\n<div style=\"text-align:center; background-color: #EBEBEB; border-top: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #AAAAAA; border-bottom: 1px solid #AAAAAA; font-weight: bold;\">\n  <h4><{$message}></h4>\n  <p><{$lang_ifnotreload}></p>\n</div>\n<div id=\"icms-footer\">\n		<{$xoops_footer}>\n</div>\n<{if $xoops_logdump != \'\'}><div><{$xoops_logdump}></div><{/if}>\n</body>\n</html>\n'),
(10,'<{$dummy_content}>'),
(11,'<h4><{$lang_activenotifications}></h4>\n<form action=\"notifications.php\" method=\"post\">\n<table class=\"outer\">\n  <tr>\n	<th><input name=\"allbox\" id=\"allbox\" onclick=\"xoopsCheckAll(\'notificationlist\', \'allbox\');\" type=\"checkbox\" value=\"<{$lang_checkall}>\" /></th>\n    <th><{$lang_event}></th>\n    <th><{$lang_category}></th>\n    <th><{$lang_itemid}></th>\n    <th><{$lang_itemname}></th>\n  </tr>\n  <{foreach item=module from=$modules}>\n  <tr>\n    <td class=\"head\"><input name=\"del_mod[<{$module.id}>]\" id=\"del_mod[]\" onclick=\"xoopsCheckGroup(\'notificationlist\', \'del_mod[<{$module.id}>]\', \'del_not[<{$module.id}>][]\');\" type=\"checkbox\" value=\"<{$module.id}>\" /></td>\n    <td class=\"head\" colspan=\"4\"><{$lang_module}>: <{$module.name}></td>\n  </tr>\n  <{foreach item=category from=$module.categories}>\n  <{foreach item=item from=$category.items}>\n  <{foreach item=notification from=$item.notifications}>\n  <tr>\n    <{cycle values=odd,even assign=class}>\n    <td class=\"<{$class}>\"><input type=\"checkbox\" name=\"del_not[<{$module.id}>][]\" id=\"del_not[<{$module.id}>][]\" value=\"<{$notification.id}>\" /></td>\n    <td class=\"<{$class}>\"><{$notification.event_title}></td>\n    <td class=\"<{$class}>\"><{$notification.category_title}></td>\n    <td class=\"<{$class}>\"><{if $item.id != 0}><{$item.id}><{/if}></td>\n    <td class=\"<{$class}>\"><{if $item.id != 0}><{if $item.url != \'\'}><a href=\"<{$item.url}>\" title=\"<{$item.name}>\"><{/if}><{$item.name}><{if $item.url != \'\'}></a><{/if}><{/if}></td>\n  </tr>\n  <{/foreach}>\n  <{/foreach}>\n  <{/foreach}>\n  <{/foreach}>\n  <tr>\n    <td class=\"foot\" colspan=\"5\">\n      <input type=\"submit\" name=\"delete_cancel\" value=\"<{$lang_cancel}>\" />\n      <input type=\"reset\" name=\"delete_reset\" value=\"<{$lang_clear}>\" />\n      <input type=\"submit\" name=\"delete\" value=\"<{$lang_delete}>\" />\n      <input type=\"hidden\" name=\"XOOPS_TOKEN_REQUEST\" value=\"<{$notification_token}>\" />\n    </td>\n  </tr>\n</table>\n</form>\n'),
(12,'<{if $xoops_notification.show}>\n<form action=\"<{$xoops_notification.target_page}>\" method=\"post\">\n<div>\n<h4 style=\"text-align:center;\"><{$lang_activenotifications}></h4>\n<input type=\"hidden\" name=\"not_redirect\" value=\"<{$xoops_notification.redirect_script}>\" />\n<input type=\"hidden\" name=\"XOOPS_TOKEN_REQUEST\" value=\"<{php}>echo icms::$security->createToken();<{/php}>\" />\n<table class=\"outer\">\n  <tr><th colspan=\"3\"><{$lang_notificationoptions}></th></tr>\n  <tr>\n    <td class=\"head\"><{$lang_category}></td>\n    <td class=\"head\"><input name=\"allbox\" id=\"allbox\" onclick=\"xoopsCheckAll(\'notification_select\',\'allbox\');\" type=\"checkbox\" value=\"<{$lang_checkall}>\" /></td>\n    <td class=\"head\"><{$lang_events}></td>\n  </tr>\n  <{foreach name=outer item=category from=$xoops_notification.categories}>\n  <{foreach name=inner item=event from=$category.events}>\n  <tr>\n    <{if $smarty.foreach.inner.first}>\n    <td class=\"even\" rowspan=\"<{$smarty.foreach.inner.total}>\"><{$category.title}></td>\n    <{/if}>\n    <td class=\"odd\">\n    <{counter assign=index}>\n    <input type=\"hidden\" name=\"not_list[<{$index}>][params]\" value=\"<{$category.name}>,<{$category.itemid}>,<{$event.name}>\" />\n    <input type=\"checkbox\" id=\"not_list[]\" name=\"not_list[<{$index}>][status]\" value=\"1\" <{if $event.subscribed}>checked=\"checked\"<{/if}> />\n    </td>\n    <td class=\"odd\"><{$event.caption}></td>\n  </tr>\n  <{/foreach}>\n  <{/foreach}>\n  <tr>\n    <td class=\"foot\" colspan=\"3\" align=\"center\"><input type=\"submit\" name=\"not_submit\" value=\"<{$lang_updatenow}>\" /></td>\n  </tr>\n</table>\n<div style=\"text-align: center;\">\n<{$lang_notificationmethodis}>:&nbsp;<{$user_method}>&nbsp;&nbsp;[<a href=\"<{$editprofile_url}>\" title=\"<{$lang_change}>\"><{$lang_change}></a>]\n</div>\n</div>\n</form>\n<{/if}>'),
(13,'<{$block.content}>'),
(14,'<{if $priv_poltype == \'page\'}>\n\n	<div class=\"privacy_policy\">\n		<div style=\"text-align: center;\"><h1><{$xoops_sitename}>: <{$lang_privacy_policy}></h1></div>\n		<div><{$priv_policy}></div>\n	</div>\n<{/if}>\n'),
(15,'<div id=\"notfound\">\n	<h1><{$lang_error_title}></h1>\n	<{if $lang_error_desc && $lang_error_desc != \'\'}>\n		<div id=\"http_error_text\"><{$lang_error_desc}></div>\n	<{/if}>\n	<br />\n	<ul>\n		<li><{$lang_search_our_site}><br />\n			<form id=\"http_error_searchform\" style=\"vertical-align: middle;\" action=\"<{$xoops_url}>/search.php\" method=\"get\">\n				<input name=\"query\" size=\"14\" style=\"vertical-align: middle;\" type=\"text\" />\n				<input name=\"action\" value=\"results\" type=\"hidden\" />\n				<input src=\"<{$xoops_url}>/images/search2.gif\" style=\"vertical-align: middle;\" alt=\"<{$lang_search}>\" onclick=\"this.form.submit()\" type=\"image\" />\n				&nbsp;&nbsp;<a href=\"<{$xoops_url}>/search.php\"><{$lang_advanced_search}></a>\n			</form>\n		</li>\n		<li><{$lang_start_again}></li>\n		<li><{$lang_found_contact}></li>\n	</ul>\n</div>'),
(16,'<h1><{$smarty.const._US_OPENID_YOUR}>: <{$displayId}></h1>\n\n<div>\n<div>\n<fieldset style=\"padding: 10px;\">\n  <legend style=\"font-weight: bold;\"><{$smarty.const._US_OPENID_EXISTING_USER}></legend>\n  <{$smarty.const._US_OPENID_EXISTING_USER_LOGIN_BELOW}><br />\n  <br />\n  <form action=\"finish_auth.php\" method=\"post\">\n    <{$smarty.const._US_NICKNAME}>: <input type=\"text\" name=\"uname\" size=\"21\" maxlength=\"\" value=\"<{$usercookie}>\" /><br />\n    <br />\n    <{$smarty.const._US_PASSWORD}>: <input type=\"password\" name=\"pass\" size=\"21\" maxlength=\"32\" /><br />\n    <input type=\"hidden\" name=\"op\" value=\"login\" />\n    <br />\n	<input type=\"hidden\" name=\"openid_link\" value=\"1\" />    \n    <input type=\"submit\" value=\"<{$smarty.const._LOGIN}>\" />\n  </form>\n</div>\n<div>\n</fieldset>\n<fieldset style=\"padding: 10px;\">\n  <legend style=\"font-weight: bold;\"><{$smarty.const._US_OPENID_NOM_MEMBER}></legend>\n  <div>\n	<{$smarty.const._US_OPENID_NON_MEMBER_DSC}></div>\n	<br />\n  <form action=\"finish_auth.php\" method=\"post\">\n  \n    <{$smarty.const._US_NICKNAME}>: <input type=\"text\" name=\"uname\" size=\"26\" maxlength=\"60\" value=\"<{$uname}>\"/><br />\n    <br />\n    <{$smarty.const._US_EMAIL}>: <input type=\"text\" name=\"email\" size=\"26\" maxlength=\"255\" value=\"<{$email}>\"/>\n    <br />\n    <br />\n     <input type=\"hidden\" name=\"openid_register\" value=\"1\" />\n     <input type=\"submit\" value=\"<{$smarty.const._SUBMIT}>\" />\n  </form>\n</fieldset>\n\n</div>\n\n</div>\n<div style=\"clear: both;\"></div>\n'),
(17,'<div class=\"CPbigTitle\" style=\"background-image: url(<{$xoops_url}>/modules/system/admin/blockspadmin/images/blockspadmin_big.png)\"><{$lang_badmin}></div><br />\n<{if $icms_blockposition_title}>\n	<h1><{$icms_blockposition_title}></h1>\n<{/if}>\n<{if $icms_blockposition_info}>\n	<p><{$icms_blockposition_info}></p>\n<{/if}>\n\n<{if $icms_blockposition_table}>\n	<{$icms_blockposition_table}>\n<{/if}>\n\n<{if $addblockposition}>\n	<{includeq file=\'db:system_common_form.html\' form=$addblockposition}>\n<{/if}>'),
(18,'<{if $icms_page_title}>\n	<div class=\"CPbigTitle\" style=\"background-image: url(<{$xoops_url}>/modules/system/admin/pages/images/pages_big.png)\"><{$icms_page_title}></div><br />\n<{/if}>\n<{if $icms_page_info}>\n	<p><{$icms_page_info}></p>\n<{/if}>\n\n<{if $icms_page_table}>\n	<{$icms_page_table}>\n<{/if}>\n\n<{if $addpage}>\n	<{includeq file=\'db:system_common_form.html\' form=$addpage}>\n<{/if}>'),
(19,'<div class=\"CPbigTitle\" style=\"background-image: url(<{$xoops_url}>/modules/system/admin/blocksadmin/images/blocksadmin_big.png)\"><{$smarty.const._AM_BADMIN}></div><br />\n<{if $icms_block_title}>\n	<h1><{$icms_block_title}></h1>\n<{/if}>\n<{if $icms_block_info}>\n	<p><{$icms_block_info}></p>\n<{/if}>\n\n<{if $icms_block_table}>\n	<{$icms_block_table}>\n<{/if}>\n\n<{if $addblock}>\n	<{includeq file=\'db:system_common_form.html\' form=$addblock}>\n<{/if}>'),
(20,'<div class=\"CPbigTitle\" style=\"background-image: url(<{$xoops_url}>/modules/system/admin/modulesadmin/images/modulesadmin_big.png)\"><{$lang_madmin}></div><br />\n<h2><{$lang_installed}></h2>\n<form action=\'admin.php\' method=\'post\' name=\'moduleadmin\' id=\'moduleadmin\'>\n<table width=\"100%\" cellpadding=\"4\" cellspacing=\"1\" border=\"0\" class=\"outer\">\n  <tr align=\'center\' valign=\'middle\'>\n    <th width=\"40%\"><{$lang_module}></th>\n    <th><{$lang_version}></th>\n    <th><{$lang_modstatus}></th>\n    <th><{$lang_lastup}></th>\n    <th><{$lang_active}></th>\n    <th><{$lang_order}><br /><small><{$lang_order0}></small></th>\n    <th width=\'130px\'><{$lang_action}></th>\n  </tr>\n  <{foreach item=module from=$modules}>\n    <tr valign=\'middle\'class=\"<{cycle values=\"even,odd\"}>\">\n      <td align=\"<{$smarty.const._GLOBAL_LEFT}>\" valign=\"middle\">\n        <div id=\"modlogo\" style=\"float: <{$smarty.const._GLOBAL_LEFT}>; padding: 2px;\">\n          <{if $module.hasadmin == 1 && $module.isactive == \'1\'}>\n            <a href=\"<{$xoops_url}>/modules/<{$module.dirname}>/<{$module.adminindex}>\">\n              <img src=\"<{$xoops_url}>/modules/<{$module.dirname}>/<{$module.image}>\" alt=\"<{$module.name}>\" title=\"<{$module.name}>\" border=\"0\" />\n            </a>\n          <{else}>\n            <img src=\"<{$xoops_url}>/modules/<{$module.dirname}>/<{$module.image}>\" alt=\"<{$module.name}>\" title=\"<{$module.name}>\" border=\"0\" />\n          <{/if}>&nbsp;\n        </div>\n        <div id=\"modlogo\" style=\"float: <{$smarty.const._GLOBAL_LEFT}>; padding-top: 2px;\">\n          <b><{$lang_modulename}>: </b><{$module.name}><br />\n          <b><{$lang_moduletitle}>: </b><input type=\"text\" name=\"newname[<{$module.mid}>]\" value=\"<{$module.title}>\" maxlength=\"150\" size=\"30\" />\n        </div>\n        <input type=\"hidden\" name=\"oldname[<{$module.mid}>]\" value=\"<{$module.title}>\" />\n      </td>\n      <td align=\'center\' valign=\"middle\"><{$module.version}></td>\n      <td align=\'center\' valign=\"middle\"><{$module.status}></td>\n      <td align=\'center\' valign=\"middle\"><{$module.last_update}></td>\n      <td align=\'center\' valign=\"middle\">\n        <{if $module.dirname == \'system\'}>\n          <input type=\"hidden\" name=\"newstatus[<{$module.mid}>]\" value=\"1\" />\n          <input type=\"hidden\" name=\"oldstatus[<{$module.mid}>]\" value=\"1\" />\n        <{else}>\n          <{if $module.isactive == \'1\'}>\n            <input type=\"checkbox\" name=\"newstatus[<{$module.mid}>]\" value=\"1\" checked=\"checked\" />\n            <input type=\"hidden\" name=\"oldstatus[<{$module.mid}>]\" value=\"1\" />\n          <{else}>\n            <input type=\"checkbox\" name=\"newstatus[<{$module.mid}>]\" value=\"1\" />\n            <input type=\"hidden\" name=\"oldstatus[<{$module.mid}>]\" value=\"0\" />\n          <{/if}>\n        <{/if}>\n      </td>\n      <td align=\'center\' valign=\"middle\">\n        <{if $module.hasmain == \'1\'}>\n          <input type=\"hidden\" name=\"oldweight[<{$module.mid}>]\" value=\"<{$module.weight}>\" />\n          <input type=\"text\" name=\"weight[<{$module.mid}>]\" size=\"3\" maxlength=\"5\" value=\"<{$module.weight}>\" />\n        <{else}>\n          <input type=\"hidden\" name=\"oldweight[<{$module.mid}>]\" value=\"0\" />\n          <input type=\"hidden\" name=\"weight[<{$module.mid}>]\" value=\"0\" />\n        <{/if}>\n      </td>\n      <td align=\'center\' valign=\"middle\">\n        <{if $module.support_site_url != \'\' &&  $module.isactive == \'1\'}>\n          <a href=\"<{$module.support_site_url}>\" rel=\"external\"><img src=\"<{$xoops_url}>/modules/system/images/support.png\" alt=\"<{$lang_support}>\" title=\"<{$lang_support}>\"/></a>\n        <{/if}>\n        <a href=\"<{$xoops_url}>/modules/system/admin.php?fct=modulesadmin&amp;op=update&amp;module=<{$module.dirname}>\"><img src=\"<{$xoops_url}>/modules/system/images/update.png\" alt=\"<{$lang_update}>\" title=\"<{$lang_update}>\"/></a>\n        <{if $module.isactive != \'1\'}>\n          <a href=\"<{$xoops_url}>/modules/system/admin.php?fct=modulesadmin&amp;op=uninstall&amp;module=<{$module.dirname}>\"><img src=\"<{$xoops_url}>/modules/system/images/uninstall.png\" alt=\"<{$lang_unistall}>\" title=\"<{$lang_unistall}>\" /></a>\n        <{/if}>  \n        <a href=\'javascript:openWithSelfMain(\"<{$xoops_url}>/modules/system/admin.php?fct=version&amp;mid=<{$module.mid}>\",\"Info\",300,230);\'><img src=\"<{$xoops_url}>/modules/system/images/info.png\" alt=\"<{$lang_info}>\" title=\"<{$lang_info}>\" /></a>\n        <input type=\"hidden\" name=\"module[]\" value=\"<{$module.mid}>\" />\n      </td>\n    </tr>\n  <{/foreach}>\n  <tr class=\'foot\'>\n    <td colspan=\'7\' align=\'center\'>\n      <input type=\'hidden\' name=\'fct\' value=\'modulesadmin\' />\n      <input type=\'hidden\' name=\'op\' value=\'confirm\' />\n      <input type=\'submit\' name=\'submit\' value=\'<{$lang_submit}>\' />\n    </td>\n  </tr>\n</table>\n</form>\n<br />\n<h2><{$lang_noninstall}></h2>\n<table width=\'100%\' border=\'0\' class=\'outer\' cellpadding=\'4\' cellspacing=\'1\'>\n  <tr align=\'center\'>\n    <th><{$lang_module}></th>\n    <th><{$lang_version}></th>\n    <th><{$lang_modstatus}></th>\n    <th width=\'130px\'><{$lang_action}></th>\n  </tr>\n  <{foreach item=module from=$avmodules}>\n    <tr valign=\'middle\'class=\"<{cycle values=\"even,odd\"}>\">\n      <td>\n        <div id=\"modlogo\" style=\"padding: 2px;\"><img src=\"<{$xoops_url}>/modules/<{$module.dirname}>/<{$module.image}>\" alt=\"<{$module.name}>\" alt=\"<{$module.name}>\" border=\"0\" />&nbsp;</div>\n	    <div id=\"modlogo\" style=\"padding-top: 10px;\"> <b><{$lang_modulename}>: </b><{$module.name}><br /> </div>\n      </td>\n      <td align=\'center\'><{$module.version}></td>\n      <td align=\'center\'><{$module.status}></td>\n      <td width=\'130px\' align=\'center\'>\n        <a href=\"<{$xoops_url}>/modules/system/admin.php?fct=modulesadmin&op=install&module=<{$module.dirname}>\"><img src=\"<{$xoops_url}>/modules/system/images/install.png\" alt=\"<{$lang_install}>\" title=\"<{$lang_install}>\" /></a>\n        <a href=\'javascript:openWithSelfMain(\"<{$xoops_url}>/modules/system/admin.php?fct=version&mid=<{$module.dirname}>\",\"Info\",300,230);\'><img src=\"<{$xoops_url}>/modules/system/images/info.png\" alt=\"<{$lang_info}>\" title=\"<{$lang_info}>\" /></a>\n      </td>\n    </tr>\n  <{/foreach}>\n</table>'),
(21,'<{$form.javascript}>\n<form id=\"<{$form.name}>\" action=\"<{$form.action}>\" method=\"<{$form.method}>\" <{$form.extra}>>\n  <table style=\"width: 100%\" class=\"outer\" cellspacing=\"1\">\n    <{if $form.title}><tr><th colspan=\"2\"><{$form.title}></th></tr><{/if}>\n    <!-- start of form elements loop -->\n    <{foreach item=element from=$form.elements}>\n      <{if $element.section == true}>\n      <tr><th colspan=\"2\"><{$element.body}></th></tr>\n      <{elseif $element.section_close == true}>\n      <tr><td class=\"even\" colspan=\"2\">&nbsp;</td></tr>\n      <{elseif $element.hidden != true}>\n      <tr id=\"<{$element.name}>_row\">\n        <td class=\"head\"><label for=\'<{$element.name}>\'><{$element.caption}> <{if $element.required}> <span style=\'color:#f00\'>*</span><{/if}></label>\n        <{if $element.description}>\n        	<div style=\"font-weight: normal\"><{$element.description}></div>\n        <{/if}>\n        </td>\n        <td class=\"<{cycle values=\"even,odd\"}>\"><{$element.body}></td>\n      </tr>\n      <{else}>\n      <{$element.body}>\n      <{/if}>\n    <{/foreach}>\n    <!-- end of form elements loop -->\n  </table>\n</form>'),
(22,'<style type=\"text/css\">\n	img {vertical-align: middle;}\n</style>\n<!--\n<style type=\"text/css\">\n.bg3 a{color: #fff;}\n</style>\n//-->\n<div id=\"<{$icms_id}>\">\n\n<{if $icms_table_header}>\n	<{$icms_table_header}>\n<{/if}>\n\n<div style=\"margin-bottom: 12px;\">\n	<{if $icms_introButtons}>\n		<div style=\"float: <{$smarty.const._GLOBAL_LEFT}>;\">\n			<form action =\'\'>\n				<{foreach from=$icms_introButtons item=introButton}>\n					<input type=\"button\" name=\"<{$introButton.name}>\" onclick=\"location=\'<{$introButton.location}>\'\" value=\"<{$introButton.value}>\" />\n				<{/foreach}>\n			</form>\n		</div>\n	<{/if}>\n\n<{if $icms_showFilterAndLimit || $icms_quicksearch}>\n<form id=\"pick\" action=\"<{$icms_optionssel_action}>\" method=\"post\" style=\"margin: 0;\">\n	<{if $icms_quicksearch}>\n		<div style=\"vertical-align: middle; float: <{$smarty.const._GLOBAL_RIGHT}>; border: 2px solid #C2CDD6; padding: 5px; background-color: #E6E6E6;\">\n			<strong><{$icms_quicksearch}> :</strong> <input style=\"min-width: 10px; vertical-align: middle;\" type=\"text\" id=\"quicksearch_<{$icms_id}>\" name=\"quicksearch_<{$icms_id}>\" size=\"15\" maxlength=\"255\"/>\n			<input style=\"vertical-align: middle;\" type=\"submit\" name=\"button_quicksearch_<{$icms_id}>\" value=\"<{$smarty.const._SEARCH}>\" />\n		</div>\n	<{/if}>\n</div>\n\n<!-- Why this If Else... ?? Let\'s comment it for now //-->\n<div style=\"clear:both; padding-top:10px;\"></div>\n<!--\n<{if !$icms_user_side}>\n	<div style=\"clear:both; padding-top:10px;\"></div>\n<{else}>\n	<div style=\"padding-top:45px;\"> </div>\n<{/if}>\n//-->\n<!-- Why this If Else... ?? //-->\n\n\n<{if $icms_showFilterAndLimit}>\n<{if $icms_pagenav}>\n	<div style=\"text-align:<{$smarty.const._GLOBAL_RIGHT}>; padding-bottom: 3px;\"><{$icms_pagenav}></div>\n<{/if}>\n\n	<table width=\"100%\" cellspacing=\"1\" cellpadding=\"2\" border=\"0\" style=\"border-left: 1px solid silver; border-top: 1px solid silver; border-right: 1px solid silver;\">\n		<tr>\n			<td>\n				<{if $icms_optionssel_filtersArray}>\n					<span style=\"font-weight: bold; font-size: 12px;\"><{$smarty.const._CO_ICMS_FILTER}> : </span>\n					<select name=\'filtersel\' onchange=\'submit()\'>\n						<{foreach from=$icms_optionssel_filtersArray key=key item=field}>\n							<option value=\'<{$key}>\' <{$field.selected}> > <{$field.caption}></option>\n						<{/foreach}>\n					</select>\n					<{if $icms_optionssel_filters2Array}>\n						<select name=\'filtersel2\' onchange=\'submit()\'>\n							<{foreach from=$icms_optionssel_filters2Array key=key item=field}>\n								<option value=\'<{$key}>\' <{$field.selected}> > <{$field.caption}></option>\n							<{/foreach}>\n						</select>\n					<{/if}>\n				<{/if}>\n			</td>\n			<td align=\'<{$smarty.const._GLOBAL_RIGHT}>\'>\n<!--				<span style=\"font-weight: bold; font-size: 12px;\"><{$smarty.const._CO_ICMS_SORT_BY}> : </span>\n				<select name=\'sortsel\' onchange=\'submit()\'>\n					<{foreach from=$icms_optionssel_fieldsForSorting key=key item=field}>\n						<option value=\'<{$key}>\' <{$field.selected}> > <{$field.caption}></option>\n					<{/foreach}>\n				</select>\n				<select name=\'ordersel\' onchange=\'submit()\'>\n					<{foreach from=$icms_optionssel_ordersArray key=key item=field}>\n						<option value=\'<{$key}>\' <{$field.selected}> > <{$field.caption}></option>\n					<{/foreach}>\n				</select>\n//-->\n				<{if !$icms_isTree}>\n					<{$smarty.const._CO_ICMS_SHOW_ONLY}> <select name=\'limitsel\' onchange=\'submit()\'>\n						<{foreach from=$icms_optionssel_limitsArray key=key item=field}>\n							<option value= \'<{$key}>\' <{$field.selected}> > <{$field.caption}></option>\n						<{/foreach}>\n					</select>\n				<{/if}>\n			</td>\n		</tr>\n	</table>\n\n<{/if}>\n</form>\n<{/if}>\n\n<{if $icms_actionButtons || $icms_withSelectedActions}>\n	<form id=\"form_<{$icms_id}>\" method=\"post\">\n<{/if}>\n<table width=\'100%\' cellspacing=\'1\' cellpadding=\'3\' border=\'0\' class=\'outer\'>\n	<tr>\n	 <{foreach from=$icms_columns item=column}>\n	 	<th width=\"<{$column.width}>\" align=\'<{$column.align}>\'><strong><{$column.caption}></strong></th>\n	 <{/foreach}>\n	 <{if $icms_has_actions}>\n	 	<th width=\'<{$icms_actions_column_width}>\' align=\'center\'>\n	 		<{if $icms_show_action_column_title}>\n	 			<strong><{$smarty.const._CO_ICMS_ACTIONS}></strong>\n	 		<{/if}>\n	 	</th>\n	 <{/if}>\n	</tr>\n\n	<{if $icms_persistable_objects}>\n		<{foreach from=$icms_persistable_objects item=icms_object}>\n			<{if $icms_actionButtons}>\n				<input type=\'hidden\' name=\'<{$icms_id}>_objects[]\' id=\'listed_objects\' value=\'<{$icms_object.id}>\' />\n			<{/if}>\n			<tr>\n				<{foreach from=$icms_object.columns item=column}>\n					<td class=\"<{$column.keyname}> <{$icms_object.class}>\" width=\"<{$column.width}>\" align=\"<{$column.align}>\"><{$column.value}></td>\n				<{/foreach}>\n				<{if $icms_object.actions}>\n					<td class=\"<{$icms_object.class}>\" align=\'center\'>\n						<{foreach from=$icms_object.actions item=action}>\n							<{$action}>\n						<{/foreach}>\n					</td>\n				<{/if}>\n			</tr>\n		<{/foreach}>\n	<{else}>\n		<tr>\n			<td class=\'head\' style=\'text-align: center; font-weight: bold;\' colspan=\"<{$icms_colspan}>\"><{$smarty.const._CO_ICMS_NO_OBJECT}></td>\n		</tr>\n	<{/if}>\n</table>\n<{if  $icms_actionButtons || $icms_withSelectedActions}>\n	<input type=\'hidden\' name=\'op\' id=\'op\' value=\'\' />\n	<{if $icms_withSelectedActions}>\n		<div style=\"padding: 5px;text-align: <{$smarty.const._GLOBAL_LEFT}>; border-left: 1px solid silver; border-bottom: 1px solid silver; border-right: 1px solid silver;\">\n		<{$smarty.const._CO_ICMS_WITH_SELECTED}>\n		<select name=\'selected_action\'>\n			<option value = \'\'>---</option>\n			<{foreach from=$icms_withSelectedActions key=key item=action}>\n				<option value = \'<{$key}>\'><{$action}></option>\n			<{/foreach}>\n		</select>\n		<input type=\"submit\" name=\"<{$actionButton.op}>\" onclick=\"this.form.elements.op.value=\'with_selected_actions\'\" value=\"<{$smarty.const._CO_ICMS_SUBMIT}>\" />\n		</div>\n	<{/if}>\n	<{if $icms_actionButtons}>\n		<div style=\"padding: 5px;text-align: <{$smarty.const._GLOBAL_RIGHT}>; border-left: 1px solid silver; border-bottom: 1px solid silver; border-right: 1px solid silver;\">\n			<{foreach from=$icms_actionButtons item=actionButton}>\n				<input type=\"submit\" name=\"<{$actionButton.op}>\" onclick=\"this.form.elements.op.value=\'<{$actionButton.op}>\'\" value=\"<{$actionButton.text}>\" />\n			<{/foreach}>\n		</div>\n	<{/if}>\n</form>\n<{/if}>\n<{if $icms_pagenav}>\n	<div style=\"text-align:<{$smarty.const._GLOBAL_RIGHT}>; padding-top: 3px;\"><{$icms_pagenav}></div>\n<{/if}>\n\n<{if $icms_introButtons}>\n	<div style=\"padding-top:15px; padding-bottom: 5px;\">\n		<form action=\'\'>\n			<{foreach from=$icms_introButtons item=introButton}>\n				<input type=\"button\" name=\"<{$introButton.name}>\" onclick=\"location=\'<{$introButton.location}>\'\" value=\"<{$introButton.value}>\" />\n			<{/foreach}>\n		</form>\n	</div>\n<{/if}>\n\n<{if $icms_table_footer}>\n	<{$icms_table_footer}>\n<{/if}>\n\n</div>\n\n<br />\n\n<{if $icms_printer_friendly_page}>\n	<a href=\"javascript:openWithSelfMain(\'<{$icms_printer_friendly_page}>\', \'smartpopup\', 700, 519);\"><img  src=\"<{$xoops_url}>/modules/icms/images/actions/fileprint.png\" alt=\"\" /></a>\n<{/if}>\n\n\n\n<!--\n<script language=\"javascript\">\nfunction Clickheretoprint()\n{\n  var disp_setting=\"toolbar=yes,location=no,directories=yes,menubar=yes,\";\n      disp_setting+=\"scrollbars=yes,width=650, height=600, <{$smarty.const._GLOBAL_LEFT}>=100, top=25\";\n  var content_value = document.getElementById(\"<{$icms_id}>\").innerHTML;\n\n  var docprint=window.open(\"\",\"\",disp_setting);\n   docprint.document.open();\n   docprint.document.write(\'<html><head><title>Inel Power System</title>\');\n   docprint.document.write(\'<link rel=\"stylesheet\" media=\"print\" href=\"<{$xoops_url}>/modules/icms/print.css\" type=\"text/css\">\');\n   docprint.document.write(\'<link rel=\"stylesheet\" media=\"all\" href=\"<{$xoops_url}>/modules/system/style.css\" type=\"text/css\">\');\n   docprint.document.write(\'</head><body onLoad=\"self.print()\">\');\n   docprint.document.write(\'<h2>Title</h2>\');\n   docprint.document.write(\'<h3>SubTitle</h3>\');\n   docprint.document.write(content_value);\n   docprint.document.write(\'<div style=\"text-align: center;\"><a href=\"javascript:window.close();\">Close this window</a></div>\');\n   docprint.document.write(\'</body></html>\');\n   docprint.document.close();\n   docprint.focus();\n}\n</script>\n<br />\n<a href=\"javascript:Clickheretoprint();\"><img  src=\"<{$xoops_url}>/modules/icms/images/actions/fileprint.png\" alt=\"\" /></a>\n//-->'),
(23,'<div class=\"CPbigTitle\" style=\"background-image: url(<{$xoops_url}>/modules/system/admin/customtag/images/customtag_big.png)\"><{$smarty.const._CO_ICMS_CUSTOMTAGS}></div><br />\n\n<{if $icms_custom_tag_explain}>\n	<h1><{$smarty.const._CO_ICMS_CUSTOMTAGS_EXPLAIN_TITLE}></h1>\n	<p><{$smarty.const._CO_ICMS_CUSTOMTAGS_EXPLAIN}></p>\n<{/if}>\n\n<p><{$icms_custom_tag_title}></p>\n\n<{if $icms_customtag_table}>\n	<{$icms_customtag_table}>\n<{/if}>\n\n<{if $addcustomtag}>\n	<{includeq file=\'db:system_common_form.html\' form=$addcustomtag}>\n<{/if}>'),
(24,'<{$form.javascript}>\n<form name=\"<{$form.name}>\" action=\"<{$form.action}>\" method=\"<{$form.method}>\" <{$form.extra}>>\n  <table style=\"width: 100%\" class=\"outer\" cellspacing=\"1\">\n    <tr>\n    <th colspan=\"2\"><{$form.title}></th>\n    </tr>\n    <!-- start of form elements loop -->\n    <{foreach item=element from=$form.elements}>\n      <{if $element.hidden != true}>\n      <tr id=\"<{$element.name}>_row\">\n        <td class=\"head\"><{$element.caption}>\n        <{if $element.description}>\n        	<div style=\"font-weight: normal\"><{$element.description}></div>\n        <{/if}>\n        </td>\n        <td class=\"<{cycle values=\"even,odd\"}>\"><{$element.body}></td>\n      </tr>\n      <{else}>\n      <{$element.body}>\n      <{/if}>\n    <{/foreach}>\n    <!-- end of form elements loop -->\n  </table>\n</form>'),
(25,'<script type=\"text/javascript\" src=\"<{$xoops_url}>/modules/system/admin/images/js/imanager.js\"></script>\n<div class=\"CPbigTitle\" style=\"background-image: url(<{$xoops_url}>/modules/system/admin/images/images/images_big.png)\"><{$lang_imanager_title}></div><br />\n<p style=\"margin-top:0;\"><{$admnav}></p>\n<{if $catcount > 0}>\n<form action=\"admin.php\" method=\"post\">\n  <table width=\"100%\" celpadding=\"0\" cellspacing=\"1\" class=\"outer\">\n    <tr>\n      <th align=\"center\" width=\"3%\"><{$lang_imanager_catid}></th>\n      <th align=\"center\" width=\"20%\"><{$lang_imanager_catname}></th>\n      <th align=\"center\" width=\"10%\"><{$lang_imanager_catmsize}></th>\n      <th align=\"center\" width=\"10%\"><{$lang_imanager_catmwidth}></th>\n      <th align=\"center\" width=\"10%\"><{$lang_imanager_catmheight}></th>\n      <th align=\"center\" width=\"8%\"><{$lang_imanager_catstype}></th>\n      <th align=\"center\" width=\"7%\"><{$lang_imanager_catdisp}></th>\n      <th align=\"center\" width=\"5%\"><{$lang_imanager_catweight}></th>\n      <th align=\"center\" width=\"5%\"><{$lang_imanager_catsubs}></th>\n      <th align=\"center\" width=\"10%\"><{$lang_imanager_catqtde}></th>\n      <th align=\"center\" width=\"10%\"><{$lang_imanager_catoptions}></th>\n    </tr>\n    <{section name=i loop=$imagecategorys}>\n      <tr class=\"<{cycle values=\"odd,even\"}><{if !$nwrite[i]}> blocked<{/if}>\" align=\"center\">\n        <td><{$imagecategorys[i]->getVar(\'imgcat_id\')}></td>\n        <td align=\"left\"><{$imagecategorys[i]->getVar(\'imgcat_name\')}></td>\n        <td><{$msize[i]}></td>\n        <td><{$imagecategorys[i]->getVar(\'imgcat_maxwidth\')}>px</td>\n        <td><{$imagecategorys[i]->getVar(\'imgcat_maxheight\')}>px</td>\n        <td><img src=\"<{$smarty.const.ICMS_IMAGES_SET_URL}>/actions/<{$imagecategorys[i]->getVar(\'imgcat_storetype\')}>.png\" title=\"<{$imagecategorys[i]->getVar(\'imgcat_storetype\')}>\" /></td>\n        <td><img src=\"<{$smarty.const.ICMS_IMAGES_SET_URL}>/actions/<{$imagecategorys[i]->getVar(\'imgcat_display\')}>.png\" /></td>\n        <td><input style=\"text-align:center;\" type=\"text\" name=\"imgcat_weight[<{$imagecategorys[i]->getVar(\'imgcat_id\')}>]\" value=\"<{$imagecategorys[i]->getVar(\'imgcat_weight\')}>\" size=\"3\" maxlength=\"4\" /></td>\n        <td>\n          <{if $subs[i] > 0}>\n            <{$subs[i]}> <a href=\"admin.php?fct=images&imgcat_id=<{$imagecategorys[i]->getVar(\'imgcat_id\')}>\" title=\"<{$smarty.const._MD_IMAGE_VIEWSUBS}>\"><img src=\"<{$smarty.const.ICMS_IMAGES_SET_URL}>/actions/filefind.png\" align=\"absmiddle\" alt=\"<{$smarty.const._MD_IMAGE_VIEWSUBS}>\" /></a>\n          <{else}>\n            <{$subs[i]}>\n          <{/if}>\n        </td>\n        <td><{$count[i]}><{if $scount[i] > 0}> (+<{$scount[i]}>)<{/if}></td>\n        <td>\n          <{if $count[i] > 0}>\n            <a href=\"admin.php?fct=images&amp;op=listimg&amp;imgcat_id=<{$imagecategorys[i]->getVar(\'imgcat_id\')}>\" title=\"<{$lang_imanager_cat_listimg}>\"><img src=\"<{$smarty.const.ICMS_IMAGES_SET_URL}>/actions/viewmag.png\" alt=\"<{$lang_imanager_cat_listimg}>\" /></a>\n          <{else}>\n            <img src=\"<{$smarty.const.ICMS_IMAGES_SET_URL}>/actions/encrypted.png\" alt=\"\" />\n          <{/if}>\n          <a href=\"admin.php?fct=images&amp;op=editcat&amp;imgcat_id=<{$imagecategorys[i]->getVar(\'imgcat_id\')}>\" title=\"<{$lang_imanager_cat_edit}>\"><img src=\"<{$smarty.const.ICMS_IMAGES_SET_URL}>/actions/edit.png\" alt=\"<{$lang_imanager_cat_edit}>\" /></a>\n          <{if ($imagecategorys[i]->getVar(\'imgcat_type\') == \'C\')}>\n            <a href=\"admin.php?fct=images&amp;op=delcat&amp;imgcat_id=<{$imagecategorys[i]->getVar(\'imgcat_id\')}>\" title=\"<{$lang_imanager_cat_del}>\"><img src=\"<{$smarty.const.ICMS_IMAGES_SET_URL}>/actions/editdelete.png\" alt=\"<{$lang_imanager_cat_del}>\" /></a>\n          <{else}>\n            <img src=\"<{$smarty.const.ICMS_IMAGES_SET_URL}>/actions/encrypted.png\" alt=\"\" />\n          <{/if}>\n        </td>\n      </tr>\n      <tr id=\"line<{$imagecategorys[i]->getVar(\'imgcat_id\')}>\" style=\"display:none;\">\n        <td class=\"head\" width=\"2%\"></td>\n        <td colspan=\"11\" style=\"padding:0; margin:0;\">\n          <div id=\"cat<{$imagecategorys[i]->getVar(\'imgcat_id\')}>\"></div>\n        </td>  \n      </tr>\n    <{/section}>\n    <tr>\n      <td class=\"head\" colspan=\"12\" align=\"right\">\n        <input type=\"submit\" name=\"submit\" value=\"<{$lang_imanager_cat_submit}>\" />\n        <{if $isAdmin}>\n          <input type=\"button\" onclick=\"showDiv(\'addcatform\'); document.anchors.item(\'addcatform\').scrollIntoView(); return false;\" value=\"<{$lang_imanager_cat_addnewcat}>\" />\n        <{/if}>\n        <{if $writecatcount > 0}>\n          <input type=\"button\" onclick=\"showDiv(\'addimgform\'); document.anchors.item(\'addimgform\').scrollIntoView(); return false;\" value=\"<{$lang_imanager_cat_addnewimg}>\" />\n        <{/if}>\n      </td>\n    </tr>\n  </table>\n  <input type=\"hidden\" name=\"op\" value=\"reordercateg\" />\n  <input type=\"hidden\" name=\"fct\" value=\"images\" />\n  <{$token}>\n</form>\n<{/if}>\n<{if $hasnwrite}>\n<div id=\"legend\">\n  <div class=\"imgcat_notwrite\"><span><{$lang_imanager_folder_not_writable}>: <{$hasnwrite}></span></div>\n</div>\n<{/if}>\n<div id=\"addimgform\" class=\"opt_divs\" style=\"display:none; margin:5px; padding:5px;\"><{$addimgform}></div>\n<a name=\"addimgform\"></a>\n<div id=\"addcatform\" class=\"opt_divs\" style=\"<{if $catcount > 0}>display:none;<{else}>display:block;<{/if}> margin:5px; padding:5px;\"><{$addcatform}></div>\n<a name=\"addcatform\"></a>'),
(26,'<script type=\"text/javascript\" src=\"<{$xoops_url}>/modules/system/admin/images/js/imanager.js\"></script>\n<div class=\"CPbigTitle\" style=\"background-image: url(<{$xoops_url}>/modules/system/admin/images/images/images_big.png)\"><{$lang_imanager_title}></div><br />\n<p style=\"margin-top:0;\"><{$admnav}></p>\n<table width=\"100%\" celpadding=\"0\" cellspacing=\"1\" class=\"outer\">\n  <tr>\n    <th align=\"center\"><{$lang_imanager_catmsize}></th>\n    <th align=\"center\"><{$lang_imanager_catmwidth}></th>\n    <th align=\"center\"><{$lang_imanager_catmheight}></th>\n    <th align=\"center\"><{$lang_imanager_catstype}></th>\n    <th align=\"center\"><{$lang_imanager_catdisp}></th>\n    <th align=\"center\" width=\"5%\"><{$lang_imanager_catsubs}></th>\n    <th align=\"center\"><{$lang_imanager_catqtde}></th>\n    <th align=\"center\"><{$lang_imanager_catoptions}></th>\n  </tr>\n  <tr class=\"odd\">\n    <td align=\"center\"><{$cat_maxsize}></td>\n    <td align=\"center\"><{$cat_maxwidth}>px</td>\n    <td align=\"center\"><{$cat_maxheight}>px</td>\n    <td align=\"center\"><img src=\"<{$smarty.const.ICMS_IMAGES_SET_URL}>/actions/<{$cat_storetype}>.png\" title=\"<{$cat_storetype}>\" alt=\"<{$cat_storetype}>\" /></td>\n    <td align=\"center\"><img src=\"<{$smarty.const.ICMS_IMAGES_SET_URL}>/actions/<{$cat_display}>.png\" title=\"<{$cat_display}>\" alt=\"<{$cat_display}>\" /></td>\n    <td align=\"center\">\n    <{if $cat_subs > 0}>\n      <{$cat_subs}> <a href=\"admin.php?fct=images&imgcat_id=<{$cat_id}>\" title=\"<{$smarty.const._MD_IMAGE_VIEWSUBS}>\"><img src=\"<{$smarty.const.ICMS_IMAGES_SET_URL}>/actions/filefind.png\" align=\"absmiddle\" alt=\"<{$smarty.const._MD_IMAGE_VIEWSUBS}>\" /></a>\n    <{else}>\n      <{$cat_subs}>\n    <{/if}>\n    </td>\n    <td align=\"center\"><{$imgcount}><{if $simgcount > 0}> (+<{$simgcount}>)<{/if}></td>\n    <td align=\"center\">\n      <a href=\"#\" onclick=\"showDiv(\'addimgform\',\'\'); document.anchors.item(\'addimgform\').scrollIntoView(); return false;\" title=\"<{$lang_imanager_cat_addimg}>\"><img src=\"<{$smarty.const.ICMS_IMAGES_SET_URL}>/actions/filenew2.png\" alt=\"<{$lang_imanager_cat_addimg}>\" /></a>\n      <a href=\"admin.php?fct=images&amp;op=editcat&amp;imgcat_id=<{$cat_id}>\" title=\"<{$lang_imanager_cat_edit}>\"><img src=\"<{$smarty.const.ICMS_IMAGES_SET_URL}>/actions/edit.png\" alt=\"<{$lang_imanager_cat_edit}>\" /></a>\n      <a href=\"admin.php?fct=images&amp;op=delcat&amp;imgcat_id=<{$cat_id}>\" title=\"<{$lang_imanager_cat_del}>\"><img src=\"<{$smarty.const.ICMS_IMAGES_SET_URL}>/actions/editdelete.png\" alt=\"<{$lang_imanager_cat_del}>\" /></a>\n    </td>\n  </tr>\n</table>\n<div style=\"border: 2px solid #C2CDD6; padding: 5px; vertical-align: middle; background-color: #E6E6E6; width:45%; float:right; margin-top:5px;\" align=\"right\">\n  <form action=\"admin.php?fct=images&op=listimg&imgcat_id=<{$cat_id}>\" method=\"POST\">\n    <b><{$lang_search_title}>:</b> \n    <input type=\"text\" name=\"query\" id=\"query\" size=\"20\" value=\"<{$query}>\" style=\"min-width:300px;\" />\n    <input type=\"submit\" name=\"btn\" value=\"<{$lang_search}>\" />\n    <input type=\"submit\" name=\"btn1\" value=\"<{$lang_cancel}>\" onclick=\"document.getElementById(\'query\').value=\'\';\" />\n  </form>\n</div>\n<br style=\"clear:right;\" />\n<{foreach from=$images item=image key=key}>\n  <{include file=\"db:admin/images/system_adm_imagemanager_img.html\" image=$image i=$key}>\n<{/foreach}>\n<br style=\"clear:both;\" />\n<{foreach from=$images item=image key=key}>\n  <{include file=\"db:admin/images/system_adm_imagemanager_editimg.html\" image=$image i=$key}>\n  <{include file=\"db:admin/images/system_adm_imagemanager_cloneimg.html\" image=$image i=$key}>\n<{/foreach}>\n<div id=\"addimgform\" class=\"opt_divs\" style=\"display:none; padding:5px; margin:5px;\"><{$addimgform}></div>\n<a name=\"addimgform\"></a>\n<{$pag}>'),
(27,'<div id=\"img<{$i}>\" class=\"imanager_image_box\">\n  <span class=\"imanager_image_img\"><img src=\"<{$image.src}>\" title=\"<{$image.nicename}>\" /></span>\n  <span class=\"imanager_image_label\"><{$image.display_nicename}></span>\n  <span class=\"imanager_image_info\">\n    <b><{$smarty.const.IMANAGER_FILE}>:</b> <{$image.name}><br />\n    <b><{$smarty.const.IMANAGER_SIZE}>:</b> <{$image.size}><br />\n    <b><{$smarty.const.IMANAGER_WIDTH}>:</b> <{$image.width}>px<br />\n    <b><{$smarty.const.IMANAGER_HEIGHT}>:</b> <{$image.height}>px\n  </span>\n  <span class=\"imanager_image_btns\">\n    <{$image.preview_link}>\n    <a href=\"#\" onclick=\"showDiv(\'edit_image\',<{$i}>); document.anchors.item(\'edit_image<{$i}>\').scrollIntoView(); return false;\" title=\"<{$lang_imanager_cat_edit}>\"><img src=\"<{$smarty.const.ICMS_IMAGES_SET_URL}>/actions/edit.png\" alt=\"<{$lang_imanager_cat_edit}>\" /></a>\n    <a href=\"#\" onclick=\"showDiv(\'clone_image\',<{$i}>); document.anchors.item(\'clone_image<{$i}>\').scrollIntoView(); return false;\" title=\"<{$lang_imanager_cat_clone}>\"><img src=\"<{$smarty.const.ICMS_IMAGES_SET_URL}>/actions/editcopy.png\" alt=\"<{$lang_imanager_cat_clone}>\" /></a>\n    <{if $image.hasextra_link}>\n      <a href=\"#\" onclick=\"<{$image.editor_link}>\" title=\"<{$lang_imanager_img_editor}>\"><img src=\"<{$smarty.const.ICMS_IMAGES_SET_URL}>/actions/edit_picture.png\" alt=\"<{$lang_imanager_img_editor}>\" /></a>\n    <{/if}>\n    <a href=\"admin.php?fct=images&amp;op=delfile&amp;image_id=<{$i}>&imgcat_id=<{$image.categ_id}>\" title=\"<{$lang_imanager_cat_del}>\"><img src=\"<{$smarty.const.ICMS_IMAGES_SET_URL}>/actions/editdelete.png\" alt=\"<{$lang_imanager_cat_del}>\" /></a>\n  </span>\n</div>'),
(28,'<div id=\"edit_image<{$i}>\" class=\"opt_divs\" style=\"display:none; padding:5px; margin:5px;\">\n  <form action=\"admin.php\" method=\"post\">\n    <table width=\"100%\" cellspacing=\"1\" class=\"outer\">\n      <tr><th colspan=\"2\"><{$lang_imanager_cat_edit}></th></tr>\n	  <tr>\n	    <td class=\"head\"><{$lang_image_name}></td>\n	    <td class=\"even\"><input type=\"text\" name=\"image_nicename[]\" id=\"name<{$i}>\" value=\"<{$image.nicename}>\" size=\"20\" maxlength=\"255\" /></td>\n	  </tr>\n	  <tr>\n	    <td class=\"head\"><{$lang_image_mimetype}></td>\n	    <td class=\"odd\"><{$image.mimetype}></td>\n	  </tr>\n	  <tr>\n	    <td class=\"head\"><{$lang_image_cat}></td>\n	    <td class=\"even\"><select name=\"imgcat_id[]\" size=\"1\"><{$image.ed_selcat_options}></select></td>\n	  </tr>\n	  <tr>\n	    <td class=\"head\"><{$lang_image_weight}></td>\n	    <td class=\"odd\"><input type=\"text\" name=\"image_weight[]\" value=\"<{$image.weight}>\" size=\"3\" maxlength=\"4\" /></td>\n	  </tr>\n	  <tr>\n	    <td class=\"head\"><{$lang_image_disp}></td>\n	    <td class=\"even\"><input type=\"checkbox\" name=\"image_display[]\" value=\"1\"<{if $image.display == 1}> checked=\"checked\"<{/if}> /></td>\n	  </tr>\n	  <tr>\n	    <td class=\"head\" colspan=\"2\" align=\"center\">\n	     <input type=\"submit\" name=\"submit\" value=\"<{$lang_submit}>\" /> \n	     <input type=\"button\" name=\"btn\" value=\"<{$lang_cancel}>\" onclick=\"document.getElementById(\'edit_image<{$i}>\').style.display=\'none\'; return false;\" />\n	    </td>\n	  </tr>\n    </table>\n	<input type=\"hidden\" name=\"image_id[]\" value=\"<{$i}>\" />\n	<input type=\"hidden\" name=\"op\" value=\"save\" />\n	<input type=\"hidden\" name=\"redir\" value=\"<{$cat_id}>\" />\n	<input type=\"hidden\" name=\"fct\" value=\"images\" />\n	<{$image.ed_token}>\n  </form>\n</div>\n<a name=\"edit_image<{$i}>\"></a>'),
(29,'<div id=\"clone_image<{$i}>\" class=\"opt_divs\" style=\"display:none; padding:5px; margin:5px;\">\n  <form id=\"clone_form<{$i}>\" action=\"admin.php\" method=\"post\">\n    <table width=\"100%\" cellspacing=\"1\" class=\"outer\">\n      <tr><th colspan=\"2\"><{$lang_imanager_cat_clone}></th></tr>\n      <tr>\n	    <td class=\"head\"><{$lang_image_name}></td>\n	    <td class=\"odd\"><input type=\"text\" name=\"image_nicename\" id=\"name<{$i}>\" size=20 value=\"<{$lang_imanager_copyof}><{$image.nicename}>\"></td>\n	  </tr>\n	  <tr>\n	    <td class=\"head\"><{$lang_image_weight}></td>\n	    <td class=\"odd\"><input type=\"text\" name=\"image_weight\" size=\"5\" value=\"0\"></td>\n	  </tr>\n	  <tr>\n	    <td class=\"head\"><{$lang_image_disp}></td>\n	    <td class=\"odd\">\n	      <{$lang_yes}> <input type=\"radio\" name=\"image_display\" value=\"1\" checked /> \n	  	  <{$lang_no}> <input type=\"radio\" name=\"image_display\" value=\"0\" />\n  	    </td>\n	  </tr>\n	  <tr>\n	    <td class=\"head\" colspan=\"2\" align=\"center\">\n	      <input type=\"submit\" name=\"submit\" value=\"<{$lang_submit}>\" /> \n	      <input type=\"button\" name=\"btn\" value=\"<{$lang_cancel}>\" onclick=\"document.getElementById(\'clone_image<{$i}>\').style.display = \'none\'; return false;\" />\n	    </td>\n      </tr>\n    </table>\n    <input type=\"hidden\" name=\"image_id\" value=\"<{$image.id}>\" />\n    <input type=\"hidden\" name=\"imgcat_id\" value=\"<{$image.categ_id}>\" />\n    <input type=\"hidden\" name=\"op\" value=\"cloneimg\" />\n    <input type=\"hidden\" name=\"fct\" value=\"images\" />\n    <{$image.clone_token}>\n  </form>\n</div>\n<a name=\"clone_image<{$i}>\"></a>'),
(30,'<table class=\"outer\" width=\"100%\">\n	<{foreach item=feeditem from=$admin_rss_feeditems}>\n		<tr class=\"head\">\n			<td><a href=\"<{$feeditem.link}>\" rel=\"external\"><{$feeditem.title}></a> (<{$feeditem.date}>)</td>\n		</tr>\n		<{if $feeditem.description}>\n			<tr>\n				<td class=\"odd\">\n					<{$feeditem.description}>\n					<{if $feeditem.guid}>\n						<br />\n						<a href=\"<{$feeditem.guid}>\"><{$smarty.const._MORE}></a>\n					<{/if}>\n				</td>\n			</tr>\n		<{elseif $feeditem.guid}>\n			<tr>\n				<td class=\"even\" valign=\"top\">\n					<a href=\"<{$feeditem.guid}>\"><{$smarty.const._MORE}></a>\n				</td>\n			</tr>\n		<{/if}>\n	<{/foreach}>\n</table>'),
(31,'<{if $basic_search == false && $search_results }>\n<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"height: 33px;\" border=\"0\">\n <tr style=\"height: 33px;\">\n  <td style=\"width:3px; background-image: url(images/search/header.<{$smarty.const._GLOBAL_LEFT}>.gif);\"></td>\n  <td style=\"background-image: url(images/search/header.background.gif); vertical-align: middle\">\n   <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">\n    <tr>\n     <td colspan=\"2\" style=\"font-family: sans-serif; font-weight: bold; padding-<{$smarty.const._GLOBAL_LEFT}>: 3px;\"><{$label_search_results}>: <{$showing}></td>\n    </tr>\n   </table>\n  </td>\n  <td style=\"width:3px; background-image: url(images/search/header.<{$smarty.const._GLOBAL_RIGHT}>.gif);\"></td>\n </tr>\n</table>\n\n<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" border=\"0\">\n<{foreach from=$module_sort_order key=sort_key item=sort_value}>\n<tr>\n <td style=\"background-image: url(images/search/bodyHeader.<{$smarty.const._GLOBAL_LEFT}>.gif);\"></td>\n <td style=\"background-image: url(images/search/bodyHeader.background.gif);\">\n 	<div style=\"padding-top: 5px; padding-bottom: 5px;\"><b><{$sort_key}></b>: (<{$sort_value}> <{$smarty.const._SR_HITSRETURNED}>)</div>\n<{if $search_results[$sort_key].search_more_url != \'\'}>\n	  <span style=\"margin: 10px; padding: 2px 0.5em 3px 0.5em; height:30px;\">\n	  <a href=\"<{$search_results[$sort_key].search_more_url}>\">\n	  <img style=\"vertical-align: middle;\" src=\"images/search/blue_view.png\" alt=\"<{$search_results[$sort_key].search_more_title}>\" /> <{$smarty.const._SR_SHOWALLR}>\n	  </a>\n	  </span>\n	<{/if}>\n\n	<{if $search_results[$sort_key].page_nav != \'\'}>\n		<div style=\"padding-bottom: 5px; text-align: <{$smarty.const._GLOBAL_RIGHT}>\"><{$search_results[$sort_key].page_nav}></div>\n	<{/if}>\n </td>\n <td style=\"background-image: url(images/search/bodyHeader.<{$smarty.const._GLOBAL_RIGHT}>.gif);\"></td>\n</tr>\n<tr style=\"background-color: #FDFDFD;\">\n <td style=\"width: 16px; background-image: url(images/search/body.<{$smarty.const._GLOBAL_LEFT}>.gif)\"></td>\n <td style=\"padding: 15px\">\n	<table class=\"outer\" cellpadding=\"4\" cellspacing=\"1\" width=\"100%\">\n		<{section name=cur_result loop=$search_results[$sort_key].results}>\n		<tr>\n			<td class=\"head\"><{math equation=\"x + y\" x=$smarty.section.cur_result.index y=$start}></td>\n			<td style=\"width: 100%;\" class=\"<{cycle values=\"even,odd\"}>\">\n				<img alt=\"<{$search_results[$sort_key].results[cur_result].processed_image_alt_text}>\" src=\"<{$search_results[$sort_key].results[cur_result].processed_image_url}>\" />\n				<{$search_results[$sort_key].results[cur_result].processed_image_tag}>&nbsp;\n				<b><a href=\"<{$search_results[$sort_key].results[cur_result].link}>\"><{$search_results[$sort_key].results[cur_result].processed_title}></a>						</b>\n				<br /><small>&nbsp;&nbsp;<a href=\"<{$search_results[$sort_key].results[cur_result].processed_user_url}>\"><{$search_results[$sort_key].results[cur_result].processed_user_name}></a> <{$search_results[$sort_key].results[cur_result].processed_time}></small>\n     			</td>\n    	</tr>\n		<{/section}>\n	</table>\n	<{if $search_results[$sort_key].page_nav != \'\'}>\n		<div style=\"padding-bottom: 5px; text-align: <{$smarty.const._GLOBAL_RIGHT}>\"><{$search_results[$sort_key].page_nav}></div>\n	<{/if}>\n </td>\n <td style=\"width: 19px; background-image: url(images/search/body.<{$smarty.const._GLOBAL_RIGHT}>.gif)\"></td>\n</tr>\n<{/foreach}>\n\n    <tr>\n     <td style=\"height:9px; background-image: url(images/search/footer.<{$smarty.const._GLOBAL_LEFT}>.gif);\"></td>\n     <td style=\"height:9px; background-image: url(images/search/footer.background.gif);\"></td>\n     <td style=\"height:9px; background-image: url(images/search/footer.<{$smarty.const._GLOBAL_RIGHT}>.gif);\"></td>\n    </tr>\n</table><br />\n<strong><{$label_search_type}> </strong><{$search_type}><br />\n<strong><{$label_keywords}> </strong>\n	<{* This section generates a space separated list of keywords that were searched. *}>\n	<{section name=cur_kw_searched loop=$searched_keywords}>\n	  	<{$searched_keywords[cur_kw_searched]}><{if $smarty.section.cur_kw_searched.index <> $smarty.section.cur_kw_searched.total}>&nbsp;<{/if}>\n	<{/section}><br />\n<{if $ignored_keywords}><{$label_ignored_keywords}>\n	<strong>\n	<{* This section generates a space separated list of keywords that were NOT searched. *}>\n	<{section name=cur_kw_not_searched loop=$ignored_keywords}>\n	  	<{$ignored_keywords[cur_kw_not_searched]}><{if $smarty.section.cur_kw_not_searched.index <> $smarty.section.cur_kw_not_searched.total}>&nbsp;<{/if}>\n	<{/section}>\n	</strong><br />\n<{/if}><br />\n<{/if}>\n<{$search_form}>'),
(32,'<{if $icms_single_view_header_value && !$icms_header_as_row}>\n	<h1><{$icms_single_view_header_value}></h1>\n<{/if}>\n\n<table class=\"outer\" cellspacing=\"1\" width=\"100%\">\n	<{if $icms_single_view_header_value && $icms_header_as_row}>\n		<tr>\n			<th width=\"200\" style=\"text-align: <{$smarty.const._GLOBAL_LEFT}>;\"><{$icms_single_view_header_caption}></th>\n			<th style=\"text-align: <{$smarty.const._GLOBAL_LEFT}>;\"><{$icms_single_view_header_value}></th>\n		</tr>\n	<{/if}>\n	<{foreach from=$icms_object_array key=key item=field name=singleviewloop}>\n		<tr>\n			<td class=\"head\" width=\"200\"><{$field.caption}></td>\n			<td class=\"<{cycle values=\"even,odd\"}>\"><{$field.value}></td>\n		</tr>\n	<{/foreach}>\n</table>\n'),
(33,'<div class=\"icms_breadcrumb\">\n	<{foreach item=breadcrumb_item from=$icms_breadcrumb_items name=loop}>\n		<{if $breadcrumb_item.link}>\n			<a href=\"<{$breadcrumb_item.link}>\"><{$breadcrumb_item.caption}></a>\n		<{else}>\n			<{$breadcrumb_item.caption}>\n		<{/if}>\n		<{if !$smarty.foreach.loop.last}>&nbsp;>&nbsp;<{/if}>\n	<{/foreach}>\n</div>'),
(34,'<div class=\"CPbigTitle\" style=\"background-image: url(<{$xoops_url}>/modules/system/admin/adsense/images/adsense_big.png)\"><{$smarty.const._CO_ICMS_ADSENSE}></div><br />\n\n<{if $icms_adsense_explain}>\n	<h1><{$smarty.const._CO_ICMS_ADSENSE_EXPLAIN_TITLE}></h1>\n	<p><{$smarty.const._CO_ICMS_ADSENSE_EXPLAIN}></p>\n<{/if}>\n\n<p><{$icms_adsense_title}></p>\n\n<{if $icms_adsense_table}>\n	<{$icms_adsense_table}>\n<{/if}>\n\n<{if $addadsense}>\n	<{includeq file=\'db:system_common_form.html\' form=$addadsense}>\n<{/if}>'),
(35,'<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\">\n<head>\n<meta http-equiv=\"content-type\" content=\"text/html; charset=<{$icms_charset}>\" />\n<meta http-equiv=\"content-language\" content=\"<{$icms_langcode}>\" />\n<meta name=\"robots\" content=\"<{$icms_meta_robots}>\" />\n<meta name=\"keywords\" content=\"<{$icms_meta_keywords}>\" />\n<meta name=\"description\" content=\"<{$icms_meta_description}>\" />\n<meta name=\"rating\" content=\"<{$icms_meta_rating}>\" />\n<meta name=\"author\" content=\"<{$icms_meta_author}>\" />\n<meta name=\"copyright\" content=\"<{$icms_meta_copyright}>\" />\n<meta name=\"generator\" content=\"IMPRESSCMS\" />\n<title><{$icms_print_pageTitle}></title>\n\n<link rel=\"stylesheet\" media=\"all\" href=\"<{$icms_url}>/modules/system/style<{if $icms_rtl}>_rtl<{/if}>.css\" type=\"text/css\">\n<link rel=\"stylesheet\" media=\"all\" href=\"<{$icms_url}>/modules/system/print<{if $icms_rtl}>_rtl<{/if}>.css\" type=\"text/css\">\n\n<style>\n	#container {width: <{$icms_print_width}>px; margin-left: auto; margin-right: auto;}\n</style>\n\n</head>\n\n<body onload=\"self.print();\">\n<div id=\"container\">\n	<{if $icms_print_title}>\n		<h2><{$icms_print_title}></h2>\n	<{/if}>\n	<{if $icms_print_dsc}>\n		<h3><{$icms_print_dsc}></h3>\n	<{/if}>\n	\n	<div id=\"icms_printer_friendly_content\"><{$icms_print_content}></div>\n	\n	<div id=\"print_close\"><a href=\"javascript:window.close();\">Close this window</a></div>\n</div>\n</body>\n</html>'),
(36,'<div class=\"CPbigTitle\" style=\"background-image: url(<{$xoops_url}>/modules/system/admin/rating/images/rating_big.png)\"><{$smarty.const._CO_ICMS_RATING}></div><br />\n\n<{if $icms_rating_explain}>\n	<h1><{$smarty.const._CO_ICMS_RATING_EXPLAIN_TITLE}></h1>\n	<p><{$smarty.const._CO_ICMS_RATING_EXPLAIN}></p>\n<{/if}>\n\n<p><{$icms_rating_title}></p>\n\n<{if $icms_rating_table}>\n	<{$icms_rating_table}>\n<{/if}>\n\n<{if $addrating}>\n	<{includeq file=\'db:system_common_form.html\' form=$addrating}>\n<{/if}>'),
(37,'<div id=\"icms_rating_container\">\n	<div class=\"item\">\n		<{if $icms_rated}>\n			<div class=\"itemHead\"><b><{$smarty.const._CO_ICMS_RATING_ALREADY_RATED}><{$icms_user_rate}></b></div>\n		<{elseif $icms_user_can_rate}>\n			<div class=\"itemHead\"><{$smarty.const._CO_ICMS_RATING_RATE_THIS}></div>\n			<div class=\"odd\">\n				<form name=\'icms_rating_form\' id=\'icms_rating_form\' method=\'post\'>\n					<div style=\"float: <{$smarty.const._GLOBAL_LEFT}>;\">\n						<input name=\"icms_rating_value\" value=\"1\" type=\"radio\">1\n						<input name=\"icms_rating_value\" value=\"2\" type=\"radio\">2\n						<input name=\"icms_rating_value\" value=\"3\" type=\"radio\">3\n						<input name=\"icms_rating_value\" value=\"4\" type=\"radio\">4\n						<input name=\"icms_rating_value\" value=\"5\" type=\"radio\">5\n					</div>\n					<div style=\"float: <{$smarty.const._GLOBAL_RIGHT}>;\">\n						<input name=\"icms_rating_submit\" value=\"<{$smarty.const._CO_ICMS_RATING_RATE_IT}>\" type=\"submit\">\n					</div>\n				</form>\n				<br />\n			</div>\n\n		<{/if}>\n		<div class=\"odd\" style=\"display: block;\">\n			<div><b><{$smarty.const._CO_ICMS_RATING_VOTERS_TOTAL}></b><{$icms_rating_stats_total}></div>\n			<div><b><{$smarty.const._CO_ICMS_RATING_AVERAGE}></b><{$icms_rating_stats_average}></div>\n		</div>\n\n	</div>\n</div>'),
(38,'<div class=\"CPbigTitle\" style=\"background-image: url(<{$xoops_url}>/modules/system/admin/mimetype/images/mimetype_big.png)\"><{$smarty.const._CO_ICMS_MIMETYPES}></div><br />\n\n<{if $icms_mimetype_explain}>\n	<h1><{$smarty.const._CO_ICMS_MIMETYPES_EXPLAIN_TITLE}></h1>\n	<p><{$smarty.const._CO_ICMS_MIMETYPES_EXPLAIN}></p>\n<{/if}>\n\n<p><{$icms_mimetype_title}></p>\n\n<{if $icms_mimetype_table}>\n	<{$icms_mimetype_table}>\n<{/if}>\n\n<{if $addmimetype}>\n	<{includeq file=\'db:system_common_form.html\' form=$addmimetype}>\n<{/if}>'),
(39,'<div class=\"CPbigTitle\" style=\"background-image: url(<{$xoops_url}>/modules/system/admin/userrank/images/userrank_big.png)\"><{$smarty.const._CO_ICMS_USERRANK}></div><br />\n\n<{if $icms_userrank_explain}>\n	<h1><{$smarty.const._CO_ICMS_USERRANK_EXPLAIN_TITLE}></h1>\n	<p><{$smarty.const._CO_ICMS_USERRANK_EXPLAIN}></p>\n<{/if}>\n\n<p><{$icms_userrank_title}></p>\n\n<{if $icms_userrank_table}>\n	<{$icms_userrank_table}>\n<{/if}>\n\n<{if $adduserrank}>\n	<{includeq file=\'db:system_common_form.html\' form=$adduserrank}>\n<{/if}>'),
(40,'<div class=\"CPbigTitle\" style=\"background-image: url(<{$xoops_url}>/modules/system/admin/autotasks/images/autotasks_big.png)\"><{$smarty.const._MD_AM_AUTOTASKS}></div><br />\n\n<{if $icms_autotasks_table}>\n	<{$icms_autotasks_table}>\n<{else}>\n	<{includeq file=\'db:system_common_form.html\' form=$addautotasks}>\n<{/if}>'),
(41,'<div id=\"usermenu\">\n      <{if $xoops_isadmin}>\n        <a class=\"menuTop\" href=\"<{$xoops_url}>/admin.php\" title=\"<{$block.lang_adminmenu}>\"><{$block.lang_adminmenu}></a>\n	    <a href=\"<{$xoops_url}>/user.php\" title=\"<{$block.lang_youraccount}>\"><{$block.lang_youraccount}></a>\n      <{else}>\n		<a class=\"menuTop\" href=\"<{$xoops_url}>/user.php\"title=\"<{$block.lang_youraccount}>\"><{$block.lang_youraccount}></a>\n      <{/if}>\n      <a href=\"<{$xoops_url}>/notifications.php\" title=\"<{$block.lang_notifications}>\"><{$block.lang_notifications}></a>\n      <{if $block.new_messages > 0}>\n        <a class=\"highlight\" href=\"<{$xoops_url}>/viewpmsg.php\" title=\"<{$block.lang_inbox}>\"><{$block.lang_inbox}> (<span style=\"color:#ff0000; font-weight: bold;\"><{$block.new_messages}></span>)</a>\n      <{else}>\n        <a href=\"<{$xoops_url}>/viewpmsg.php\" title=\"<{$block.lang_inbox}>\"><{$block.lang_inbox}></a>\n      <{/if}>\n      <a href=\"<{$xoops_url}>/user.php?op=logout\" title=\"<{$block.lang_logout}>\"><{$block.lang_logout}></a>\n</div>\n'),
(42,'<div id=\"block_login_form\">\n    <{if $block.auth_googleonly == FALSE}>\n  <br />\n  <form style=\"margin-top: 0px;\" action=\"<{$xoops_url}>/user.php\" method=\"post\">\n    <p><{$block.lang_username}></p>\n    <div><input type=\"text\" class=\"uname\" name=\"uname\" size=\"12\" value=\"<{$block.unamevalue}>\" maxlength=\"200\" /><br /></div>\n    <br /><p><{$block.lang_password}></p>\n    <div><input type=\"password\" name=\"pass\" size=\"12\" maxlength=\"32\" /><br /></div>\n    <{if $block.rememberme }>\n    <div><input type=\"checkbox\" name=\"rememberme\" value=\"On\" /><{$block.lang_rememberme}><br /></div>\n    <{/if}>\n    <div><input type=\"hidden\" name=\"xoops_redirect\" value=\"<{$xoops_requesturi}>\" /></div>\n    <div><input type=\"hidden\" name=\"op\" value=\"login\" /></div>\n	<input type=\"hidden\" id=\"tfacode\" name=\"tfacode\" value=\"\" />\n  <input type=\"hidden\" id=\"tfaremember\" name=\"tfaremember\" value=\"\" />\n    <div><input type=\"submit\" value=\"<{$block.lang_login}>\" /><br /></div>\n    <{$block.sslloginlink}>\n  </form>\n  <{php}>include_once XOOPS_ROOT_PATH.\'/include/2fa/manage.php\';print tfaLoginJS(\'block_login_form\');<{/php}>\n<br />\n  <{if $block.auth_openid}>\n	  <div style=\"text-align: <{$smarty.const._GLOBAL_LEFT}>;\"><a href=\"<{$block.auth_url}>\"><{$smarty.const._MB_SYSTEM_OPENID_LOGIN}></a></div>\n  <{/if}>\n  <{if $block.auth_okta}>\n	  <div style=\"text-align: <{$smarty.const._GLOBAL_LEFT}>;\"><a href=\"<{$block.auth_okta}>\"><{$smarty.const._MB_SYSTEM_OKTA_LOGIN}></a></div>\n  <{/if}>\n</div>\n<br />\n<a id=\'lostpass\' href=\'#\' title=\"<{$block.lang_lostpass}>\"><{$block.lang_lostpass}></a>\n<{if $block.registration }>\n<br />\n<a href=\"<{$xoops_url}>/register.php\" title=\"<{$block.lang_registernow}>\"><{$block.lang_registernow}></a>\n<{/if}>\n    <{else}>\n        <div class=\'google-only-login-div\' style=\"text-align: <{$smarty.const._GLOBAL_LEFT}>;\"><a class=\'google-only-login-link\' href=\"<{$block.auth_url}>\">\n          <div class=\"google-btn\" style=\"max-width: 192px;\">\n          <div class=\"google-icon-wrapper\">\n            <img class=\"google-icon-svg\" src=\"https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg\"/>\n            <span class=\"btn-text btn\"><b>Login with Google</b></span>\n          </div>\n</a></div>\n        </div>\n    <{/if}>\n'),
(43,'<form style=\"margin-top: 0px;\" action=\"<{$xoops_url}>/search.php\" method=\"get\">\n  <div><input type=\"text\" name=\"query\" size=\"14\" /><input type=\"hidden\" name=\"action\" value=\"results\" /><br /><input type=\"submit\" value=\"<{$block.lang_search}>\" /></div>\n</form>\n<a href=\"<{$xoops_url}>/search.php\" title=\"<{$block.lang_advsearch}>\"><{$block.lang_advsearch}></a>'),
(44,'<{foreach item=module from=$block.modules}>\n<strong><{$module.name}></strong>\n<ul>\n  <{foreach item=pending from=$module.pending}>\n  <li>\n    <a href=\"<{$pending.adminlink}>\"><{$pending.lang_linkname}></a>:\n\n    <{if $pending.pendingnum}>\n      <span style=\'font-weight:bold;color:#ff0000;\'><{$pending.pendingnum}></span>\n    <{else}>\n      <{$pending.pendingnum}>\n    <{/if}>\n  </li>\n  <{/foreach}>\n</ul>\n<{/foreach}>\n'),
(45,'<div id=\"mainmenu\">\n      <a class=\"menuTop<{if $xoops_dirname==\'system\'}> actlink<{/if}>\" href=\"<{$xoops_url}>/index.php\" title=\"<{$block.lang_home}>\"><{$block.lang_home}></a>\n      <!-- start module menu loop -->\n      <{foreach item=module from=$block.modules}>\n      <a class=\"menuMain<{if $xoops_dirname==$module.directory}> actlink<{/if}>\" href=\"<{$xoops_url}>/modules/<{$module.directory}>/\" title=\"<{$module.name}>\"><{$module.name}></a>\n        <{foreach item=sublink from=$module.sublinks}>\n          <a class=\"menuSub\" href=\"<{$sublink.url}>\" title=\"<{$sublink.name}>\"><{$sublink.name}></a>\n        <{/foreach}>\n      <{/foreach}>\n      <!-- end module menu loop -->\n	  <{if $block.priv_enabled == true}>\n		  <a class=\"menuMain<{if $xoops_dirname==\'system\'}> actlink<{/if}>\" href=\"<{$xoops_url}>/privpolicy.php\" title=\"<{$block.lang_privpolicy}>\"><{$block.lang_privpolicy}></a>\n	  <{/if}>\n</div>'),
(46,'  <{if $block.showgroups == true}>\n    <div class=\"outer\">\n  <!-- start group loop -->\n  <{foreach item=group from=$block.groups}>\n  <div style=\"text-align:center;\"><strong><{$group.name}></strong></div>\n  <div class=\"clear\"></div>\n\n  <!-- start group member loop -->\n  <{foreach item=user from=$group.users}>\n  <div class=\"<{cycle values=\"even,odd\"}>\" style=\"margin: 0 auto; text-align:center;\"><img src=\"<{$user.avatar}>\" alt=\"<{$user.name}>\'s avatar\" width=\"32px\" /><br /><a href=\"<{$xoops_url}>/userinfo.php?uid=<{$user.id}>\" title=\"<{$user.name}>\"><{$user.name}></a>&nbsp;<{$user.msglink}></div>\n   <div class=\"clear\"></div>\n  <{/foreach}>\n  <!-- end group member loop -->\n\n  <{/foreach}>\n  <!-- end group loop -->\n</div>\n<{/if}>\n  <div class=\"clear\"></div>\n\n<div style=\"margin: 3px; text-align:center;\">\n  <img src=\"<{$block.logourl}>\" alt=\"<{$xoops_sitename}>\" /><br /><{$block.recommendlink}>\n</div>\n'),
(47,'<{$block.online_total}><br /><br /><{$block.lang_members}>: <{$block.online_members}><br /><{$block.lang_guests}>: <{$block.online_guests}><br /><br /><{$block.online_names}> <a href=\"javascript:openWithSelfMain(\'<{$xoops_url}>/misc.php?action=showpopups&amp;type=online\',\'Online\',420,350);\" title=\"<{$block.lang_more}>\"><{$block.lang_more}></a>'),
(48,'<table cellspacing=\"1px\" class=\"outer\">\n  <{foreach item=user from=$block.users}>\n  <tr class=\"<{cycle values=\"even,odd\"}>\" valign=\"middle\">\n    <td align=\"center\">\n      <{if $user.avatar != \"\"}>\n      <img src=\"<{$user.avatar}>\" alt=\"<{$user.name}>\'s avatar\" width=\"32px\" /><br />\n      <{/if}>\n      <a href=\"<{$xoops_url}>/userinfo.php?uid=<{$user.id}>\" title=\"<{$user.name}>\"><{$user.name}></a>\n    </td>\n    <td align=\"center\"><{$user.posts}></td>\n  </tr>\n  <{/foreach}>\n</table>\n'),
(49,'<table cellspacing=\"1px\" class=\"outer\">\n  <{foreach item=user from=$block.users}>\n  <tr class=\"<{cycle values=\"even,odd\"}>\" valign=\"middle\">\n    <td align=\"center\">\n      <{if $user.avatar != \"\"}>\n      <img src=\"<{$user.avatar}>\" alt=\"<{$user.name}>\'s avatar\" width=\"32px\" /><br />\n      <{/if}>\n      <a href=\"<{$xoops_url}>/userinfo.php?uid=<{$user.id}>\" title=\"<{$user.name}>\"><{$user.name}></a>\n      <{if $xoops_isadmin}><br />(<{$user.login_name}>)<{/if}>\n      <br /><{$user.joindate}>\n    </td>\n  </tr>\n  <{/foreach}>\n</table>\n<{if $block.index_enabled == true}>\n<{$block.lang_activeusers}>: <{$block.active}><br />\n<{$block.lang_inactiveusers}>: <{$block.inactive}><br />\n<{$block.lang_totalusers}>: <{$block.registered}><br />\n<{/if}>'),
(50,'<table width=\"100%\" cellspacing=\"1px\" class=\"outer\">\n  <{foreach item=comment from=$block.comments}>\n  <tr class=\"<{cycle values=\"even,odd\"}>\">\n    <td align=\"center\"><img src=\"<{$xoops_url}>/images/subject/<{$comment.icon}>\" alt=\"\" /></td>\n    <td><{$comment.title}></td>\n    <td align=\"center\"><{$comment.module}></td>\n    <td align=\"center\"><{$comment.poster}></td>\n    <td align=\"<{$smarty.const._GLOBAL_RIGHT}>\"><{$comment.time}></td>\n  </tr>\n  <{/foreach}>\n</table>'),
(51,'<form action=\"<{$block.target_page}>\" method=\"post\">\n<table class=\"outer\">\n  <{foreach item=category from=$block.categories}>\n  <{foreach name=inner item=event from=$category.events}>\n  <{if $smarty.foreach.inner.first}>\n  <tr>\n    <td class=\"head\" colspan=\"2\"><{$category.title}></td>\n  </tr>\n  <{/if}>\n  <tr>\n    <td class=\"odd\"><{counter assign=index}><input type=\"hidden\" name=\"not_list[<{$index}>][params]\" value=\"<{$category.name}>,<{$category.itemid}>,<{$event.name}>\" /><input type=\"checkbox\" name=\"not_list[<{$index}>][status]\" value=\"1\" <{if $event.subscribed}>checked=\"checked\"<{/if}> /></td>\n    <td class=\"odd\"><{$event.caption}></td>\n  </tr>\n  <{/foreach}>\n  <{/foreach}>\n  <tr>\n    <td class=\"foot\" colspan=\"2\"><input type=\"hidden\" name=\"not_redirect\" value=\"<{$block.redirect_script}>\" /><input type=\"hidden\" value=\"<{$block.notification_token}>\" name=\"XOOPS_TOKEN_REQUEST\" /><input type=\"submit\" name=\"not_submit\" value=\"<{$block.submit_button}>\" /></td>\n  </tr>\n</table>\n</form>'),
(52,'<div style=\"text-align: center;\">\n<form action=\"index.php\" method=\"post\">\n<div>\n<{$block.theme_select}>\n</div>\n</form>\n</div>'),
(53,'<div style=\"margin-left: auto; margin-right: auto; text-align: center;\">\n	<{$block.ml_tag}>\n</div>'),
(54,'<{foreach item=provider from=$block.provider}>\n<a href=\"#\" onclick=\"window.open(<{$provider.link}>);return false;\" rel=\"nofollow\" title=\"<{$provider.title}>\"><img src=\"<{$block.imagepath}><{$provider.image}>\" alt=\"<{$provider.title}>\"/></a>\n<{/foreach}>'),
(55,'<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n	<{foreach item=item from=$block.msg}>\n		<{$item}>\n	<{/foreach}>\n</table>'),
(56,'<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n  	<tr>\n  		<div class=\"CPindexOptions\" align=\"center\">\n	  		<div class=\"cpicon\" align=\"center\">\n	  			<{foreach item=mod from=$block.sysmod}>\n	  				<a href=\"<{$mod.link}>\" title=\"<{$mod.title}>\"><img src=\"<{if $mod.image != \'\'}><{$mod.image}><{else}><{$xoops_url}>/modules/system/images/modules.png<{/if}>\" alt=\"<{$mod.title}>\" />\n	  					<span><{$mod.title}></span></a>\n	  			<{/foreach}>\n	  		</div>\n	  	</div>\n	</tr>\n</table>'),
(57,'<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n  	<tr>\n		<td width=\"100%\" class=\"CPindexOptions\">\n			<div class=\"cpicon\">\n				<{foreach item=mod from=$block.mods}>\n			 			<a href=\"<{$mod.link}>\" title=\"<{$mod.title}>\"><img src=\"<{if $mod.iconbig != \'\'}><{$mod.iconbig}><{else}><{$xoops_url}>/modules/system/images/modules.png<{/if}>\" alt=\"<{$mod.title}>\" />\n			 			<span><{$mod.title}></span></a>\n				<{/foreach}>\n			</div>\n		</td>\n	</tr>\n</table>'),
(58,'<div class=\"bookmarks\">\n<{foreach item=module key=key from=$block}>\n<strong><{$key}></strong>\n	<ul>\n	<{foreach item=bookmark from=$module}>\n		<li>\n	        <a href=\"<{$bookmark.url}>\" title=\"<{$key}> &raquo; <{$bookmark.name}>\"><{$bookmark.name}></a>\n		</li>\n	<{/foreach}>\n	</ul>\n<{/foreach}>\n</div>'),
(59,'<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n  	<tr>\n  		<div class=\"CPindexOptions\" align=\"center\">\n	  		<div class=\"cpicon\" align=\"center\">\n		  		<{foreach item=group key=key from=$block}>\n		  			<span><{$key}></span>\n		  			<{foreach item=mod from=$group}>\n		  				<a href=\"<{$mod.link}>\" title=\"<{$mod.title}>\"><img src=\"<{if $mod.image != \'\'}><{$mod.image}><{else}><{$xoops_url}>/modules/system/images/modules.png<{/if}>\" alt=\"<{$mod.title}>\" />\n		  				<span><{$mod.title}></span></a>\n		  			<{/foreach}>\n		  			<br />\n				<{/foreach}>\n	  		</div>\n	  	</div>\n	</tr>\n</table>'),
(60,'<{if $profile_audio_title}>\r\n	<h1><{$profile_audio_title}></h1>\r\n<{/if}>\r\n<{if $profile_audio_info}>\r\n	<p><{$profile_audio_info}></p>\r\n<{/if}>\r\n\r\n<{if $profile_audio_table}>\r\n	<{$profile_audio_table}>\r\n<{/if}>\r\n\r\n<{if $addaudio}>\r\n	<{includeq file=\'db:system_common_form.html\' form=$addaudio}>\r\n<{/if}>'),
(61,'<{if $profile_category_title}>\r\n	<h1><{$profile_category_title}></h1>\r\n<{/if}>\r\n<{if $profile_category_info}>\r\n	<p><{$profile_category_info}></p>\r\n<{/if}>\r\n\r\n<{if $profile_category_table}>\r\n	<{$profile_category_table}>\r\n<{/if}>\r\n\r\n<{if $addcategory}>\r\n	<{includeq file=\'db:system_common_form.html\' form=$addcategory}>\r\n<{/if}>'),
(62,'<{if $profile_field_title}>\r\n	<h1><{$profile_field_title}></h1>\r\n<{/if}>\r\n<{if $profile_field_info}>\r\n	<p><{$profile_field_info}></p>\r\n<{/if}>\r\n\r\n<{if $profile_field_table}>\r\n	<{$profile_field_table}>\r\n<{/if}>\r\n\r\n<{if $addfield}>\r\n	<{includeq file=\'db:system_common_form.html\' form=$addfield}>\r\n<{/if}>'),
(63,'<{if $profile_pictures_title}>\r\n	<h1><{$profile_pictures_title}></h1>\r\n<{/if}>\r\n<{if $profile_pictures_info}>\r\n	<p><{$profile_pictures_info}></p>\r\n<{/if}>\r\n\r\n<{if $profile_pictures_table}>\r\n	<{$profile_pictures_table}>\r\n<{/if}>\r\n\r\n<{if $addpictures}>\r\n	<{includeq file=\'db:system_common_form.html\' form=$addpictures}>\r\n<{/if}>'),
(64,'<{if $profile_regstep_title}>\r\n	<h1><{$profile_regstep_title}></h1>\r\n<{/if}>\r\n<{if $profile_regstep_info}>\r\n	<p><{$profile_regstep_info}></p>\r\n<{/if}>\r\n\r\n<{if $profile_regstep_table}>\r\n	<{$profile_regstep_table}>\r\n<{/if}>\r\n\r\n<{if $addregstep}>\r\n	<{includeq file=\'db:system_common_form.html\' form=$addregstep}>\r\n<{/if}>'),
(65,'<{if $profile_tribes_title}><h1><{$profile_tribes_title}></h1><{/if}>\r\n<{if $profile_tribes_info}><p><{$profile_tribes_info}></p><{/if}>\r\n<{if $profile_tribes_table}><{$profile_tribes_table}><{/if}>\r\n<{if $addtribes}><{includeq file=\'db:system_common_form.html\' form=$addtribes}><{/if}>\r\n<{if $mergetribes}><{includeq file=\'db:system_common_form.html\' form=$mergetribes}><{/if}>'),
(66,'<{if $profile_tribeuser_title}>\r\n	<h1><{$profile_tribeuser_title}></h1>\r\n<{/if}>\r\n<{if $profile_tribeuser_info}>\r\n	<p><{$profile_tribeuser_info}></p>\r\n<{/if}>\r\n\r\n<{if $profile_tribeuser_table}>\r\n	<{$profile_tribeuser_table}>\r\n<{/if}>\r\n\r\n<{if $addtribeuser}>\r\n	<{includeq file=\'db:system_common_form.html\' form=$addtribeuser}>\r\n<{/if}>'),
(67,'<{if $profile_videos_title}>\r\n	<h1><{$profile_videos_title}></h1>\r\n<{/if}>\r\n<{if $profile_videos_info}>\r\n	<p><{$profile_videos_info}></p>\r\n<{/if}>\r\n\r\n<{if $profile_videos_table}>\r\n	<{$profile_videos_table}>\r\n<{/if}>\r\n\r\n<{if $addvideos}>\r\n	<{includeq file=\'db:system_common_form.html\' form=$addvideos}>\r\n<{/if}>'),
(68,'<table>\r\n  <tr class=\"<{cycle values=\'odd,even\'}>\">\r\n    <td colspan=\"2\">\r\n      <form id=\"<{$addform.name}>\" method=\"<{$addform.method}>\" action=\"<{$addform.action}>\">\r\n        <{foreach item=element from=$addform.elements}>\r\n        <{$element.caption}> <{$element.body}>\r\n        <{/foreach}>\r\n      </form>\r\n    </td>\r\n  </tr>\r\n  <{foreach item=field from=$fields key=fieldid}>\r\n  <tr class=\"<{cycle values=\'odd,even\'}>\">\r\n    <td><{$field}></td>\r\n    <td>\r\n      <{if isset($visibilities[$fieldid])}>\r\n      <{foreach item=visibility from=$visibilities[$fieldid]}>\r\n      <a href=\"visibility.php?op=del&amp;fieldid=<{$fieldid}>&amp;ug=<{$visibility.user_group}>&amp;pg=<{$visibility.profile_group}>\" title=\"<{$smarty.const._DELETE}>\"><img src=\"<{$icms_url}>/images/delete.gif\" alt=\"<{$smarty.const._DELETE}>\" width=\"14\" height=\"14\" /></a>\r\n      <{$smarty.const._AM_PROFILE_FIELDVISIBLEFOR}> <{$groups[$visibility.user_group]}>\r\n      <{$smarty.const._AM_PROFILE_FIELDVISIBLEON}> <{$groups[$visibility.profile_group]}><br />\r\n      <{/foreach}>\r\n      <{else}>\r\n      <{$smarty.const._AM_PROFILE_FIELDNOTVISIBLE}>\r\n      <{/if}>\r\n    </td>\r\n  </tr>\r\n  <{/foreach}>\r\n</table>'),
(69,'<{include file=\"db:profile_header.html\"}>\r\n<{if $profile_audioform}>\r\n<{if $hideForm}>\r\n<div class=\"profile-form outer\">\r\n  <h2 class=\"head\">\r\n    <a href=\"#add-audio\" onclick =\"jQuery(\'div.profile-album-form\').toggle(400);\" name=\"add-audio\"><img src=\"images/toggle.png\" />&nbsp;<{$lang_audioform_title}></a>\r\n  </h2>\r\n  <div class=\"profile-album-form\" style=\"display: none\">\r\n    <{includeq file=\'db:system_common_form.html\' form=$profile_audioform}>\r\n  </div>\r\n</div>\r\n<{else}>\r\n<{includeq file=\'db:system_common_form.html\' form=$profile_audioform}>\r\n<{/if}>\r\n<{/if}>\r\n<{if $profile_audios}>\r\n<div id=\"profile-audio-container\" class=\"outer\">\r\n  <table width=\"100%\" cellspacing=\"1\" cellpadding=\"4\">\r\n    <tbody>\r\n      <tr align=\"center\">\r\n        <th width=\"205px\"><{$lang_player}></th>\r\n        <th><{$lang_author}></th>\r\n        <th><{$lang_title}></th>\r\n        <th><{$lang_lastupdated}></th>\r\n        <{if $actions}><th width=\"130px\"><{$lang_actions}></th><{/if}>\r\n      </tr>\r\n      <{foreach item=audio from=$profile_audios}>\r\n      <{cycle values=\'even,odd\' assign=class}>\r\n      <tr class=\"<{$class}>\">\r\n        <td width=\"205px\"><{$audio.audio_content}></td>\r\n        <td><{$audio.author}></td>\r\n        <td><{$audio.title}></td>\r\n        <td><{$audio.creation_time}></td>\r\n        <{if $audio.userCanEditAndDelete}>\r\n        <td width=\"130px\" align=\"center\"><{$audio.editItemLink}><{$audio.deleteItemLink}></td>\r\n        <{/if}>\r\n      </tr>\r\n      <{/foreach}>\r\n    </tbody>\r\n  </table>\r\n</div>\r\n<{if $profile_audios_pagenav}>\r\n<div class=\"pagination\"><{$profile_audios_pagenav}></div>\r\n<{/if}>\r\n<{/if}>\r\n<{if $icmspersistable_delete_confirm}><{$icmspersistable_delete_confirm}><{/if}>\r\n<{if $lang_nocontent}><div id=\"profile-nocontent\" class=\"outer\"><p class=\"odd\"><{$lang_nocontent}></p></div><{/if}>\r\n<{if $profile_audios || $lang_nocontent}><{includeq file=\'db:system_notification_select.html\'}><{/if}>'),
(70,'<{if $form}>\r\n<{include file=\"db:profile_header.html\"}>\r\n<{includeq file=\'db:system_common_form.html\' form=$form}>\r\n<{/if}>'),
(71,'<{if $form}>\r\n<{include file=\"db:profile_header.html\"}>\r\n<{includeq file=\'db:system_common_form.html\' form=$form}>\r\n<{/if}>'),
(72,'<{include file=\"db:profile_header.html\"}>\r\n<{if $profile_configsform}><{includeq file=\'db:system_common_form.html\' form=$profile_configsform}><{/if}>'),
(73,'<div style=\"clear: both\"></div>\r\n<{if $section_name != $lang_profile}></div><{/if}>'),
(74,'<{include file=\"db:profile_header.html\"}>\r\n<{if $profile_friendships[2]}>\r\n<div class=\"profile-friendships-container outer\">\r\n  <h2 class=\"head\">\r\n    <a href=\"#\" onclick =\"jQuery(\'div#profile-friendships-accepted\').toggle(400);\"><img src=\"images/toggle.png\" />&nbsp;<{$lang_friendships_accepted}></a>\r\n  </h2>\r\n  <div id=\"profile-friendships-accepted\">\r\n    <{foreach name=friends item=friend from=$profile_friendships[2]}>\r\n    <div class=\"profile-friendships-friend\" style=\"width:<{$itemwidth}>%\">\r\n      <{if $friend.friendship_avatar}><{$friend.friendship_avatar}><br /><{/if}>\r\n      <{$friend.friendship_linkedUname}><br />\r\n      <{if $friend.userCanEditAndDelete}>\r\n      <{$friend.deleteItemLink}>\r\n      <{/if}>\r\n    </div>\r\n    <{if $smarty.foreach.friends.iteration is div by $rowitems || $smarty.foreach.friends.last == TRUE}>\r\n    <div class=\"clear\"></div>\r\n    <{/if}>\r\n    <{/foreach}>\r\n  </div>\r\n</div>\r\n<{/if}>\r\n<{if $profile_friendships[1]}>\r\n<div class=\"profile-friendships-container outer\">\r\n  <h2 class=\"head\">\r\n    <a href=\"#\" onclick =\"jQuery(\'div#profile-friendships-pending\').toggle(400);\"><img src=\"images/toggle.png\" />&nbsp;<{$lang_friendships_pending}></a>\r\n  </h2>\r\n  <div id=\"profile-friendships-pending\" style=\"display: none\">\r\n    <{foreach name=friends item=friend from=$profile_friendships[1]}>\r\n    <div class=\"profile-friendships-friend\" style=\"width:<{$itemwidth}>%\">\r\n      <{if $friend.friendship_avatar}><{$friend.friendship_avatar}><br /><{/if}>\r\n      <{$friend.friendship_linkedUname}><br />\r\n      <{if $friend.userCanEditAndDelete}>\r\n      <form action=\"<{$icms_url}>/modules/<{$icms_dirname}>/index.php?uid=<{$uid}>\" method=\"post\" class=\"profile-quickform\">\r\n        <input type=\"hidden\" value=\"<{$friend.friendship_id}>\" name=\"friendship_id\" />\r\n        <input type=\"hidden\" value=\"2\" name=\"status\" />\r\n        <input type=\"hidden\" value=\"editfriendship\" name=\"op\" />\r\n        <{$token}>\r\n        <input class=\"image\" name=\"submit\" type=\"image\" alt=\"<{$lang_friendship_accept}>\" title=\"<{$lang_friendship_accept}>\" src=\"<{$image_ok}>\" style=\"vertical-align:middle\" />\r\n      </form>\r\n      <form action=\"<{$icms_url}>/modules/<{$icms_dirname}>/index.php?uid=<{$uid}>\" method=\"post\" class=\"profile-quickform\">\r\n        <input type=\"hidden\" value=\"<{$friend.friendship_id}>\" name=\"friendship_id\" />\r\n        <input type=\"hidden\" value=\"3\" name=\"status\" />\r\n        <input type=\"hidden\" value=\"editfriendship\" name=\"op\" />\r\n        <{$token}>\r\n        <input class=\"image\" name=\"submit\" type=\"image\" alt=\"<{$lang_friendship_reject}>\" title=\"<{$lang_friendship_reject}>\" src=\"<{$image_cancel}>\" style=\"vertical-align:middle\" />\r\n      </form>\r\n      <{/if}>\r\n    </div>\r\n    <{if $smarty.foreach.friends.iteration is div by $rowitems || $smarty.foreach.friends.last == TRUE}>\r\n    <div class=\"clear\"></div>\r\n    <{/if}>\r\n    <{/foreach}>\r\n  </div>\r\n</div>\r\n<{/if}>\r\n<{if $profile_friendships[3]}>\r\n<div class=\"profile-friendships-container outer\">\r\n  <h2 class=\"head\">\r\n    <a href=\"#\" onclick =\"jQuery(\'div#profile-friendships-rejected\').toggle(400);\"><img src=\"images/toggle.png\" />&nbsp;<{$lang_friendships_rejected}></a>\r\n  </h2>\r\n  <div id=\"profile-friendships-rejected\" style=\"display: none\">\r\n    <{foreach name=friends item=friend from=$profile_friendships[3]}>\r\n    <div class=\"profile-friendships-friend\" style=\"width:<{$itemwidth}>%\">\r\n      <{if $friend.friendship_avatar}><{$friend.friendship_avatar}><br /><{/if}>\r\n      <{$friend.friendship_linkedUname}><br />\r\n      <{if $friend.userCanEditAndDelete}>\r\n      <{$friend.deleteItemLink}>\r\n      <{/if}>\r\n    </div>\r\n    <{if $smarty.foreach.friends.iteration is div by $rowitems || $smarty.foreach.friends.last == TRUE}>\r\n    <div class=\"clear\"></div>\r\n    <{/if}>\r\n    <{/foreach}>\r\n  </div>\r\n</div>\r\n<{/if}>\r\n<{if $icmspersistable_delete_confirm}>\r\n<{$icmspersistable_delete_confirm}>\r\n<{/if}>\r\n<{if $lang_nocontent}><div id=\"profile-nocontent\" class=\"outer\"><p class=\"odd\"><{$lang_nocontent}></p></div><{/if}>'),
(75,'<{if $profile_module_home || $profile_category_path}>\r\n<div id=\"profile_header\">\r\n  <{if $profile_module_home}><{$profile_module_home}><{/if}>\r\n  <{if $profile_category_path}>\r\n  <{if $profile_module_home}>&raquo;&raquo;<{/if}>\r\n  <{$profile_category_path}>\r\n  <{/if}>\r\n</div>\r\n<{/if}>\r\n<{if $deleted || $suspended}><div class=\"errorMsg\"><{$deleted}><{$suspended}></div><br /><{/if}>'),
(76,'<{include file=\"db:profile_header.html\"}>\r\n<{if $profile_friendshipform}>\r\n<{if $hideForm}>\r\n<div class=\"profile-form outer\">\r\n  <h2 class=\"head\">\r\n    <a href=\"#add-friend\" onclick =\"jQuery(\'div.profile-friendship-form\').toggle(400);\" name=\"add-friend\"><img src=\"images/toggle.png\" alt=\"\" />&nbsp;<{$lang_friendshipform_title}></a>\r\n  </h2>\r\n  <div class=\"profile-friendship-form\" style=\"display: none\">\r\n    <{includeq file=\'db:system_common_form.html\' form=$profile_friendshipform}>\r\n  </div>\r\n</div>\r\n<{else}>\r\n<{includeq file=\'db:system_common_form.html\' form=$profile_friendshipform}>\r\n<{/if}>\r\n<{/if}>\r\n<{if $user_name_header || $isOwner || $allow_profile_general || $allow_profile_contact || $allow_profile_stats || ($allow_profile_usercontributions && $modules|count > 0)}>\r\n<div class=\"profile-profile-group1\">\r\n  <{if $user_name_header}>\r\n  <div id=\"profile-profile-visual\" class=\"outer\">\r\n    <h3 class=\"profile-profile-title head\">\r\n      <{if $isOwner || $isAdmin}><a href=\"configs.php?uid=<{$uid_owner}>\"><img class=\"profile-nav-bar-icon\" src=\"images/configs.gif\" alt=\"\" /></a><{/if}>\r\n      <{if !$isAnonym && !$isOwner}><a href=\"<{$icms_url}>/pmlite.php?send2=1&amp;to_userid=<{$uid_owner}>\" class=\"profile-pm\"></a><{/if}>\r\n      <{$user_name_header}>\r\n    </h3>\r\n    <{if $allow_pictures}>\r\n    <div id=\"profile-profile-avatar\">\r\n      <{if $user_avatar}>\r\n      <img src=\"<{$user_avatar}>\" alt=\"\" />\r\n      <{if $isOwner && !$gravatar}>\r\n      <br />\r\n      <form action=\"<{$icms_url}>/modules/<{$icms_dirname}>/pictures.php\" method=\"post\" id=\"avatarform\" class=\"profile-quickform\">\r\n        <input type=\"hidden\" value=\"delavatar\" name=\"op\" />\r\n        <{$token}>\r\n        <input name=\"submit\" type=\"image\" alt=\"<{$lang_delete}>\" title=\"<{$lang_delete}>\" src=\"<{$icms_url}>/images/crystal/actions/editdelete.png\" style=\"vertical-align:middle\" />\r\n      </form>\r\n      <{elseif $gravatar && $allow_avatar_upload}>\r\n      <p><{$lang_selectavatar}></p>\r\n      <{/if}>\r\n      <{else}>\r\n      <{if $isOwner && $allow_avatar_upload}><a href=\"<{$icms_url}>/modules/<{$icms_dirname}>/pictures.php\"><img src=\"images/noavatar.gif\" alt=\"\" /></a><p><{$lang_selectavatar}></p>\r\n      <{else}>\r\n      <img src=\"images/noavatar.gif\" alt=\"\" />\r\n      <{/if}>\r\n      <{/if}>\r\n    </div>\r\n    <{/if}>\r\n  </div>\r\n  <{/if}>\r\n  <{if $isOwner}>\r\n  <div id=\"profile-profile-visitors\" class=\"outer\">\r\n    <h3 class=\"head\"><{$lang_visitors}></h3>\r\n    <{if $visitors}>\r\n    <{cycle values=\'even,odd\' print=false reset=true advance=false}>\r\n    <{section name=i loop=$visitors}>\r\n    <p class=\"<{cycle values=\'even,odd\'}>\">&raquo; <a href=index.php?uid=<{$visitors[i].uid}>><{$visitors[i].uname}></a> <small>(<{$visitors[i].time}>)</small></p>\r\n    <{/section}>\r\n    <{/if}>\r\n  </div>\r\n  <{/if}>\r\n  <{foreach item=category from=$fields}>\r\n  <div class=\"profile-profile-details outer\">\r\n    <{cycle values=\'even,odd\' print=false reset=true advance=false}>\r\n    <h3 class=\"profile-profiletitle head\"><{$category.title}></h3>\r\n    <{foreach item=field from=$category.fields}>\r\n    <p class=\"<{cycle values=\'even,odd\'}>\"><{$field.image}><span class=\"profile-profileinfo-label\"><{$field.title}>:</span><span class=\"profile-profileinfo-value\"><{$field.value}></span></p>\r\n    <{/foreach}>\r\n  </div>\r\n  <{/foreach}>\r\n  <{if $allow_profile_usercontributions && $modules|count > 0}>\r\n  <div id=\"profile-profile-search-results\" class=\"outer\">\r\n    <h3 class=\"profile-profiletitle head\"><{$lang_usercontributions}></h3>\r\n    <{foreach item=module from=$modules name=\"search_results\"}>\r\n    <div class=\"profile-profile-search-module\" id=\"profile-profile-search-module-<{$smarty.foreach.search_results.iteration}>\" >\r\n      <h4 class=\"profile-profiletitle head\">\r\n        <a class=\"profile-profile-search-module-title\" id=\"profile-profile-search-module-title-<{$smarty.foreach.search_results.iteration}>\" onclick =\"$(\'#profile-profile-search-module-results-<{$smarty.foreach.search_results.iteration}>\').toggle(400);\"><img src=\"images/toggle.png\" alt=\"\" /></a>\r\n        <{$module.name}>\r\n      </h4>\r\n      <div class=\"profile-profile-search-module-results\" id=\"profile-profile-search-module-results-<{$smarty.foreach.search_results.iteration}>\" style=\"display: none\">\r\n        <{cycle values=\'even,odd\' print=false reset=true advance=false}>\r\n        <{foreach item=result from=$module.results}>\r\n        <p class=\"<{cycle values=\'even,odd\'}>\">\r\n          <img src=\"<{$icms_url}>/<{$result.image}>\" alt=\"<{$module.name}>\" />\r\n          <strong><a href=\"<{$result.link}>\"><{$result.title}></a></strong>\r\n          <small>(<{$result.time}>)</small>\r\n        </p>\r\n        <{/foreach}>\r\n        <p><{$module.showall_link}></p>\r\n      </div>\r\n    </div>\r\n    <{/foreach}>\r\n  </div>\r\n  <{/if}>\r\n</div>\r\n<{/if}>\r\n<{if $allow_pictures || $allow_audio || $allow_videos || $allow_friendship || $allow_tribes}>\r\n<div class=\"profile-profile-group2\">\r\n  <{if $allow_pictures}>\r\n  <div id=\"profile-profile-pictures\" class=\"outer\">\r\n    <h3 class=\"profile-profiletitle head\">\r\n      <a href=\"pictures.php?uid=<{$uid_owner}>\"><img src=\"images/pictures.gif\" alt=\"<{$lang_pictures_goto}>\" /><{$lang_photos}></a>\r\n    </h3>\r\n    <{if $pictures|@count > 0}>\r\n    <{cycle values=\'even,odd\' print=false reset=true advance=false}>\r\n    <table cellspacing=\"1\" cellpadding=\"0\"><tr>\r\n    <{section name=i loop=$pictures}>\r\n    <td class=\"profile-profile-picture <{cycle values=\"even,odd\"}>\">\r\n      <{$pictures[i].content}>\r\n    </td>\r\n    <{/section}>\r\n    </tr></table>\r\n    <div style=\"clear:both;\"></div>\r\n    <{/if}>\r\n  </div>\r\n  <{/if}>\r\n  <{if $allow_tribes}>\r\n  <div id=\"profile-profile-tribes\" class=\"outer\">\r\n    <h3 class=\"head\">\r\n      <a href=\"tribes.php?uid=<{$uid_owner}>\"><img src=\"images/tribes.gif\" alt=\"<{$lang_tribes_goto}>\" /><{$lang_tribes}></a>\r\n    </h3>\r\n    <{if $tribes|@count > 0}>\r\n    <ul>\r\n      <{section name=i loop=$tribes}>\r\n      <li><{$tribes[i].itemLink}></li>\r\n      <{/section}>\r\n    </ul>\r\n    <{/if}>\r\n    <{if $tribes_approvals|@count > 0}>\r\n    <h3 class=\"head\"><{$lang_approvals}></h3>\r\n    <ul>\r\n      <{section name=i loop=$tribes_approvals}>\r\n      <li>\r\n        <form action=\"<{$icms_url}>/modules/<{$icms_dirname}>/tribes.php?tribes_id=<{$tribes_approvals[i].tribes_id}>\" method=\"post\" class=\"profile-quickform\">\r\n          <input type=\"hidden\" value=\"<{$tribes_approvals[i].tribeuser_id}>\" name=\"tribeuser_id\" />\r\n          <input type=\"hidden\" value=\"approved\" name=\"action\" />\r\n          <input type=\"hidden\" value=\"1\" name=\"store\" />\r\n          <input type=\"hidden\" value=\"edittribeuser\" name=\"op\" />\r\n          <{$token}>\r\n          <input class=\"image\" name=\"submit\" type=\"image\" alt=\"<{$lang_approve}>\" title=\"<{$lang_approve}>\" src=\"<{$image_ok}>\" style=\"vertical-align:middle\" />\r\n        </form>\r\n        <form action=\"<{$icms_url}>/modules/<{$icms_dirname}>/tribes.php?tribes_id=<{$tribes_approvals[i].tribes_id}>\" method=\"post\" class=\"profile-quickform\">\r\n          <input type=\"hidden\" value=\"<{$tribes_approvals[i].tribeuser_id}>\" name=\"tribeuser_id\" />\r\n          <input type=\"hidden\" value=\"approved\" name=\"action\" />\r\n          <input type=\"hidden\" value=\"0\" name=\"store\" />\r\n          <input type=\"hidden\" value=\"edittribeuser\" name=\"op\" />\r\n          <{$token}>\r\n          <input class=\"image\" name=\"submit\" type=\"image\" alt=\"<{$lang_delete}>\" title=\"<{$lang_delete}>\" src=\"<{$image_cancel}>\" style=\"vertical-align:middle\" />\r\n        </form>\r\n        <{$tribes_approvals[i].uname}> (<{$tribes_approvals[i].tribe_itemLink}>)\r\n      </li>\r\n      <{/section}>\r\n    </ul>\r\n    <{/if}>\r\n    <{if $tribes_invitations|@count > 0}>\r\n    <h3 class=\"head\"><{$lang_invitations}></h3>\r\n    <ul>\r\n      <{section name=i loop=$tribes_invitations}>\r\n      <li>\r\n        <form action=\"<{$icms_url}>/modules/<{$icms_dirname}>/tribes.php?tribes_id=<{$tribes_invitations[i].tribes_id}>\" method=\"post\" class=\"profile-quickform\">\r\n          <input type=\"hidden\" value=\"<{$tribes_invitations[i].tribeuser_id}>\" name=\"tribeuser_id\" />\r\n          <input type=\"hidden\" value=\"accepted\" name=\"action\" />\r\n          <input type=\"hidden\" value=\"1\" name=\"store\" />\r\n          <input type=\"hidden\" value=\"edittribeuser\" name=\"op\" />\r\n          <{$token}>\r\n          <input class=\"image\" name=\"submit\" type=\"image\" alt=\"<{$lang_accept}>\" title=\"<{$lang_accept}>\" src=\"<{$image_ok}>\" style=\"vertical-align:middle\" />\r\n        </form>\r\n        <form action=\"<{$icms_url}>/modules/<{$icms_dirname}>/tribes.php?tribes_id=<{$tribes_invitations[i].tribes_id}>\" method=\"post\" class=\"profile-quickform\">\r\n          <input type=\"hidden\" value=\"<{$tribes_invitations[i].tribeuser_id}>\" name=\"tribeuser_id\" />\r\n          <input type=\"hidden\" value=\"accepted\" name=\"action\" />\r\n          <input type=\"hidden\" value=\"0\" name=\"store\" />\r\n          <input type=\"hidden\" value=\"edittribeuser\" name=\"op\" />\r\n          <{$token}>\r\n          <input class=\"image\" name=\"submit\" type=\"image\" alt=\"<{$lang_delete}>\" title=\"<{$lang_delete}>\" src=\"<{$image_cancel}>\" style=\"vertical-align:middle\" />\r\n        </form>\r\n        <{$tribes_invitations[i].itemLink}>\r\n      </li>\r\n      <{/section}>\r\n    </ul>\r\n    <{/if}>\r\n  </div>\r\n  <{/if}>\r\n  <{if $allow_friendship}>\r\n  <div id=\"profile-profile-friends\" class=\"outer\">\r\n    <h3 class=\"head\">\r\n      <a href=\"friendship.php?uid=<{$uid_owner}>\"><img src=\"images/friends.gif\" alt=\"<{$lang_friends_goto}>\" /><{$lang_friends}></a>\r\n    </h3>\r\n    <{if $friends|@count > 0}>\r\n    <{cycle values=\'even,odd\' print=false reset=true advance=false}>\r\n    <table cellspacing=\"1\" cellpadding=\"0\"><tr>\r\n    <{section name=i loop=$friends}>\r\n    <td class=\"profile-profile-friend <{cycle values=\"even,odd\"}>\">\r\n      <{if $friends[i].user_avatar}><{$friends[i].user_avatar}><br /><{/if}><{$friends[i].uname}>\r\n    <{/section}>\r\n    </tr></table>\r\n    <{/if}>\r\n    <{if $friends_pending|@count > 0}>\r\n    <h3 class=\"head\"><{$lang_friends_pending}></h3>\r\n    <ul>\r\n      <{section name=i loop=$friends_pending}>\r\n      <li>\r\n        <form action=\"<{$icms_url}>/modules/<{$icms_dirname}>/index.php?uid=<{$uid}>\" method=\"post\" class=\"profile-quickform\">\r\n          <input type=\"hidden\" value=\"<{$friends_pending[i].friendship_id}>\" name=\"friendship_id\" />\r\n          <input type=\"hidden\" value=\"2\" name=\"status\" />\r\n          <input type=\"hidden\" value=\"editfriendship\" name=\"op\" />\r\n          <{$token}>\r\n          <input class=\"image\" name=\"submit\" type=\"image\" alt=\"<{$lang_friendship_accept}>\" title=\"<{$lang_friendship_accept}>\" src=\"<{$image_ok}>\" style=\"vertical-align:middle\" />\r\n        </form>\r\n        <form action=\"<{$icms_url}>/modules/<{$icms_dirname}>/index.php?uid=<{$uid}>\" method=\"post\" class=\"profile-quickform\">\r\n          <input type=\"hidden\" value=\"<{$friends_pending[i].friendship_id}>\" name=\"friendship_id\" />\r\n          <input type=\"hidden\" value=\"3\" name=\"status\" />\r\n          <input type=\"hidden\" value=\"editfriendship\" name=\"op\" />\r\n          <{$token}>\r\n          <input class=\"image\" name=\"submit\" type=\"image\" alt=\"<{$lang_friendship_reject}>\" title=\"<{$lang_friendship_reject}>\" src=\"<{$image_cancel}>\" style=\"vertical-align:middle\" />\r\n        </form>\r\n        <{$friends_pending[i].uname}>\r\n      </li>\r\n      <{/section}>\r\n    </ul>\r\n    <{/if}>\r\n  </div>\r\n  <{/if}>\r\n  <{if $allow_audio}>\r\n  <div id=\"profile-profile-audio\" class=\"outer\">\r\n    <h3 class=\"head\">\r\n      <a href=\"audio.php?uid=<{$uid_owner}>\"><img src=\"images/audio.gif\" alt=\"<{$lang_audio_goto}>\" /><{$lang_audio}></a>\r\n    </h3>\r\n    <{if $audio}>\r\n    <div class=\"profile-profile-audio even\">\r\n      <{$audio.content}>\r\n    </div>\r\n    <{/if}>\r\n  </div>\r\n  <{/if}>\r\n  <{if $allow_videos}>\r\n  <div id=\"profile-profile-videos\" class=\"outer\">\r\n    <h3 class=\"head\">\r\n      <a href=\"videos.php?uid=<{$uid_owner}>\"><img src=\"images/video.gif\" alt=\"<{$lang_video_goto}>\" /><{$lang_videos}></a>\r\n    </h3>\r\n    <div id=\"profile-profile-video\">\r\n    <{if $video}>\r\n    <div class=\"profile-profile-video even\">\r\n      <{$video.content}>\r\n    </div>\r\n    <{/if}>\r\n    </div>\r\n  </div>\r\n  <{/if}>\r\n</div>\r\n<{/if}>\r\n<{if $user_name_header || $isOwner || $allow_profile_general || $allow_profile_contact || $allow_profile_stats || ($allow_profile_usercontributions && $modules|count > 0) || $allow_pictures || $allow_audio || $allow_videos || $allow_friendship || $allow_tribes}>\r\n<br style=\"clear:both;\" />\r\n<{/if}>\r\n<{if $module_is_socialmode}>\r\n<div style=\"text-align:center;\"><{$commentsnav}><{$lang_notice}></div>\r\n<{if $comment_mode == \"flat\"}>\r\n<{include file=\"db:system_comments_flat.html\"}>\r\n<{elseif $comment_mode == \"thread\"}>\r\n<{include file=\"db:system_comments_thread.html\"}>\r\n<{elseif $comment_mode == \"nest\"}>\r\n<{include file=\"db:system_comments_nest.html\"}>\r\n<{/if}>\r\n<{/if}>'),
(77,'<{include file=\"db:profile_header.html\"}>\r\n<{if $profile_picturesform}>\r\n<{if $hideForm}>\r\n<div class=\"profile-form outer\">\r\n  <h2 class=\"head\">\r\n    <a href=\"#add-picture\" onclick =\"jQuery(\'div.profile-pictures-form\').toggle(400);\" name=\"add-picture\"><img src=\"images/toggle.png\" alt=\"\" />&nbsp;<{$lang_picturesform_title}></a>\r\n  </h2>\r\n  <div class=\"profile-pictures-form\" style=\"display: none\">\r\n    <{includeq file=\'db:system_common_form.html\' form=$profile_picturesform}>\r\n  </div>\r\n</div>\r\n<{else}>\r\n<{includeq file=\'db:system_common_form.html\' form=$profile_picturesform}>\r\n<{/if}>\r\n<{/if}>\r\n<{if $profile_pictures}>\r\n<div id=\"profile-pictures-container\">\r\n  <{foreach name=pictures item=picture from=$profile_pictures}>\r\n  <div class=\"profile-pictures-picture\" style=\"width:<{$itemwidth}>%\">\r\n    <{$picture.picture_content}>\r\n    <br /><{$picture.title}><br />\r\n    <{if $picture.userCanEditAndDelete}>\r\n    <{$picture.editItemLink}><{$picture.deleteItemLink}>\r\n    <{/if}>\r\n    <{if $allow_avatar_upload}>\r\n    <form action=\"<{$icms_url}>/modules/<{$icms_dirname}>/pictures.php\" method=\"post\" id=\"avatarform\" class=\"profile-quickform\">\r\n      <input type=\"hidden\" value=\"<{$picture.pictures_id}>\" name=\"pictures_id\" />\r\n      <input type=\"hidden\" value=\"setavatar\" name=\"op\" />\r\n      <{$token}>\r\n      <input name=\"submit\" type=\"image\" alt=\"<{$lang_avatar}>\" title=\"<{$lang_avatar}>\" src=\"<{$icms_url}>/modules/<{$icms_dirname}>/images/avatar.gif\" style=\"vertical-align:middle\" />\r\n    </form>\r\n    <{/if}>\r\n    <{if $picture.private}><img src=\"<{$icms_url}>/modules/<{$icms_dirname}>/images/lock.gif\" style=\"vertical-align:middle\" alt=\"\" /><{/if}>\r\n  </div>\r\n  <{if $smarty.foreach.pictures.iteration is div by $rowitems || $smarty.foreach.pictures.last == TRUE}>\r\n  <div class=\"clear\"></div>\r\n  <{/if}>\r\n  <{/foreach}>\r\n  <{if $profile_pictures_pagenav}>\r\n  <div class=\"pagination\"><{$profile_pictures_pagenav}></div>\r\n  <{/if}>\r\n</div>\r\n<{/if}>\r\n<{if $icmspersistable_delete_confirm}>\r\n<{$icmspersistable_delete_confirm}>\r\n<{/if}>\r\n<{if $lang_nocontent}><div id=\"profile-nocontent\" class=\"outer\"><p class=\"odd\"><{$lang_nocontent}></p></div><{/if}>\r\n<{if $profile_pictures || $lang_nocontent}><{includeq file=\'db:system_notification_select.html\'}><{/if}>'),
(78,'<{include file=\"db:profile_header.html\"}>\r\n<{if $stop}><div class=\'errorMsg\'><{$stop}></div><br clear=\'both\'><{/if}>\r\n<{if $confirm}><div class=\'confirmMsg\'><{foreach item=msg from=$confirm name=loop}><{$msg}><{if !$smarty.foreach.loop.last}><br /><{/if}><{/foreach}></div><br clear=\'both\'><{/if}>\r\n<{if $regform}><{includeq file=\'db:system_common_form.html\' form=$regform}><{/if}>\r\n<{include file=\"db:profile_footer.html\"}>'),
(79,'<h1><{$smarty.const._AM_PROFILE_REQUIREMENTS}></h1>\r\n<{$smarty.const._AM_PROFILE_REQUIREMENTS_INFO}>\r\n\r\n<ul>\r\n<{foreach item=failed_requirement from=$failed_requirements}>\r\n  <li><{$failed_requirement}></li>\r\n<{/foreach}>\r\n</ul>\r\n\r\n<br /><{$smarty.const._AM_PROFILE_REQUIREMENTS_SUPPORT}>'),
(80,'<div>\r\n    <a href=\"<{$icms_url}>/modules/<{$icms_dirname}>/search.php\" title=\"<{$smarty.const._BACK}>\"><{$smarty.const._SEARCH}></a> >> <{$smarty.const._MD_PROFILE_RESULTS}>\r\n</div>\r\n<br />\r\n<{if $users}>\r\n    <table>\r\n        <tr>\r\n            <{foreach item=caption from=$captions}>\r\n                <th><{$caption}></th>\r\n            <{/foreach}>\r\n        </tr>\r\n        <{foreach item=user from=$users}>\r\n            <tr class=\"<{cycle values=\'odd, even\'}>\">\r\n                <{foreach item=fieldvalue from=$user.output}>\r\n                    <td><{$fieldvalue}></td>\r\n                <{/foreach}>\r\n            </tr>\r\n        <{/foreach}>\r\n    </table>\r\n    \r\n    <{$nav}>\r\n<{else}>\r\n    <div class=\"errorMsg\">\r\n        <{$smarty.const._MD_PROFILE_NOUSERSFOUND}>\r\n    </div>\r\n<{/if}>'),
(81,'<form id=\"<{$searchform.name}>\" action=\"<{$searchform.action}>\" method=\"<{$searchform.method}>\" <{$searchform.extra}> >\r\n    <table>\r\n    <!-- start of visible form elements loop -->\r\n    <{foreach item=element from=$searchform.elements}>\r\n        <{if $element.hidden != true}>\r\n            <tr valign=\"top\">\r\n                <td class=\"head\"><{$element.caption}></td>\r\n                <td class=\"odd\" style=\"white-space: nowrap;\"><{$element.body}></td>\r\n            </tr>\r\n        <{/if}>\r\n    <{/foreach}>\r\n    <!-- end of visible form elements loop -->\r\n    </table>\r\n    <div>\r\n    <{foreach item=element from=$searchform.elements}>\r\n        <{if $element.hidden == true}>\r\n            <{$element.body}>\r\n        <{/if}>\r\n    <{/foreach}>\r\n    </div>\r\n</form>'),
(82,'<{include file=\"db:profile_header.html\"}>\r\n<{if $profile_tribesform}>\r\n<{if $hideForm}>\r\n<div class=\"profile-form outer\">\r\n  <h2 class=\"head\">\r\n    <a href=\"#add-group\" onclick =\"$(\'#profile-tribes-form\').toggle(400);\" name=\"add-group\"><img src=\"images/toggle.png\" />&nbsp;<{$lang_tribesform_title}></a>\r\n  </h2>\r\n  <div id=\"profile-tribes-form\" class=\"profile-album-form\" style=\"display: none\">\r\n    <{includeq file=\'db:system_common_form.html\' form=$profile_tribesform}>\r\n  </div>\r\n</div>\r\n<{else}>\r\n<{includeq file=\'db:system_common_form.html\' form=$profile_tribesform}>\r\n<{/if}>\r\n<{/if}>\r\n<{if $profile_tribeuserform}>\r\n<{if $hideForm}>\r\n<div class=\"profile-form outer\">\r\n  <h2 class=\"head\">\r\n    <a href=\"#add-user\" onclick =\"$(\'#profile-tribeuser-form\').toggle(400);\" name=\"add-user\"><img src=\"images/toggle.png\" />&nbsp;<{$lang_tribeuserform_title}></a>\r\n  </h2>\r\n  <div id=\"profile-tribeuser-form\" class=\"profile-album-form\" style=\"display: none\">\r\n    <{includeq file=\'db:system_common_form.html\' form=$profile_tribeuserform}>\r\n  </div>\r\n</div>\r\n<{else}>\r\n<{includeq file=\'db:system_common_form.html\' form=$profile_tribeuserform}>\r\n<{/if}>\r\n<{/if}>\r\n<{if $profile_editpostform}>\r\n<{if $hideForm}>\r\n<div class=\"profile-form outer\">\r\n  <h2 class=\"head\">\r\n    <a href=\"#edit-post\" onclick =\"$(\'#profile-post-form\').toggle(400);\" name=\"edit-post\"><img src=\"images/toggle.png\" />&nbsp;<{$lang_editpostform_title}></a>\r\n  </h2>\r\n  <div id=\"profile-post-form\" class=\"profile-album-form\" style=\"display: none\">\r\n    <{includeq file=\'db:system_common_form.html\' form=$profile_editpostform}>\r\n  </div>\r\n</div>\r\n<{else}>\r\n<{includeq file=\'db:system_common_form.html\' form=$profile_editpostform}>\r\n<{/if}>\r\n<{/if}>\r\n<{if $profile_tribe}>\r\n<{if !$profile_tribe_posts}>\r\n<div id=\"profile-tribes-description\" class=\"outer\">\r\n  <h2 class=\"head\"><{$profile_tribe.title}></h2>\r\n  <div class=\"even\">\r\n	<{if $profile_tribe.tribe_img}>\r\n	<div id=\"profile-tribes-picture\"><{$profile_tribe.tribe_content}></div>\r\n	<{/if}>\r\n	<{$profile_tribe.tribe_desc}>\r\n    <p id=\"profile-tribes-statistics\">\r\n      <strong><{$lang_creation_time}>:</strong> <{$profile_tribe.creation_time_short}>,\r\n      <strong><{$lang_topics}>:</strong> <{$profile_tribe_topics_count}>,\r\n      <strong><{$lang_views}>:</strong> <{$profile_tribe.counter}>,\r\n      <strong><{$lang_members}>:</strong> <{$profile_tribe_members|@count}>\r\n    </p>\r\n    <div class=\"clear\"></div>\r\n  </div>\r\n</div>\r\n<{/if}>\r\n<{if $showContent && $profile_tribe_topics|@count > 0}>\r\n<div id=\"profile-tribes-discussions\" class=\"outer\">\r\n  <table width=\"100%\" cellspacing=\"1\" cellpadding=\"4\">\r\n	<tbody>\r\n	  <tr align=\"center\">\r\n		<th><{$lang_topic_title}></th>\r\n		<th><{$lang_topic_author}></th>\r\n		<th><{$lang_topic_replies}></th>\r\n		<th><{$lang_topic_views}></th>\r\n		<th><{$lang_topic_last_post_time}></th>\r\n	  </tr>\r\n	  <{foreach item=topic from=$profile_tribe_topics}>\r\n	  <{cycle values=\'even,odd\' assign=class}>\r\n	  <tr class=\"<{$class}>\">\r\n		<td><{if $topic.closedIcon}><{$topic.closedIcon}> <{/if}><{$topic.itemLink}></td>\r\n		<td align=\"center\"><{$topic.poster_uname}></td>\r\n		<td align=\"center\"><{$topic.replies}></td>\r\n		<td align=\"center\"><{$topic.views}></td>\r\n		<td align=\"right\"><{$topic.last_post_time}> <{$topic.lastItemLink}></td>\r\n	  </tr>\r\n	  <{/foreach}>\r\n	  <{if $profile_tribe_topics_pagenav}>\r\n	  <tr>\r\n		<td colspan=\"5\" align=\"right\"><{$profile_tribe_topics_pagenav}></td>\r\n	  </tr>\r\n	  <{/if}>\r\n	</tbody>\r\n  </table>\r\n</div>\r\n<{/if}>\r\n<{if $showContent && $profile_tribe_posts|@count > 0}>\r\n<div id=\"profile-tribes-discussions\" class=\"outer\">\r\n  <h2 class=\"head\"><{$lang_discussions}> - <{$profile_tribe_topic.title}><{if $profile_tribe_topic.closed}> (<{$lang_closed}>)<{/if}></h2>\r\n  <table width=\"100%\" cellspacing=\"1\" cellpadding=\"4\">\r\n	<tbody>\r\n	  <{foreach item=post from=$profile_tribe_posts}>\r\n	  <{cycle values=\'even,odd\' assign=class}>\r\n	  <tr class=\"<{$class}>\">\r\n		<td width=\"120px\">\r\n		  <{if $post.poster_avatar}><{$post.poster_avatar}><br /><{/if}>\r\n		  <{$post.poster_uname}><br />\r\n		  <{$post.post_time}>\r\n		  <{if $isOwner || $post.userCanEditAndDelete}>\r\n		  <br /><{$post.editItemLink}><{$post.deleteItemLink}>\r\n		  <{/if}>\r\n		  <{if $post.post_id == $profile_tribe_topic.post_id && ($isOwner || $profile_tribe_topic.userCanEditAndDelete)}>\r\n		  <{$profile_tribe_topic.toggleCloseLink}>\r\n		  <{/if}>\r\n		</td>\r\n		<td>\r\n		  <a name=\"post<{$post.post_id}>\"></a>\r\n		  <{if $post.title}><strong><{$post.title}></strong><br /><br /><{/if}>\r\n		  <{$post.body}>\r\n		  <{if $post.poster_signature}><br /><br />--------------------<br /><{$post.poster_signature}><{/if}>\r\n		</td>\r\n	  </tr>\r\n	  <{/foreach}>\r\n	  <{if $profile_tribe_posts_pagenav}>\r\n	  <tr>\r\n		<td colspan=\"5\" align=\"right\"><{$profile_tribe_posts_pagenav}></td>\r\n	  </tr>\r\n  <{/if}>\r\n	</tbody>\r\n  </table>\r\n</div>\r\n<{/if}>\r\n<{if $profile_addpostform}>\r\n<{if $hideForm}>\r\n<div class=\"profile-form outer\">\r\n  <h2 class=\"head\">\r\n	<a href=\"#add-post\" onclick =\"$(\'#profile-post-form\').toggle(400);\" name=\"add-post\"><img src=\"images/toggle.png\" />&nbsp;<{$lang_addpostform_title}></a>\r\n  </h2>\r\n  <div id=\"profile-post-form\" style=\"display:none\">\r\n	<{includeq file=\'db:system_common_form.html\' form=$profile_addpostform}>\r\n  </div>\r\n</div>\r\n<{else}>\r\n<{includeq file=\'db:system_common_form.html\' form=$profile_addpostform}>\r\n<{/if}>\r\n<{/if}>\r\n<{if $showContent && $profile_tribe_members|@count > 0 && !$profile_tribe_posts}>\r\n<div id=\"profile-tribes-members\" class=\"outer\">\r\n  <h2 class=\"head\"><{$lang_members}></h2>\r\n  <{foreach name=members item=member from=$profile_tribe_members}>\r\n  <div class=\"profile-tribes-members-member\" style=\"width:<{$itemwidth}>%\">\r\n	<{if $member.tribeuser_avatar}><{$member.tribeuser_avatar}><br /><{/if}><{if $member.owner}><{$lang_owner}><br /><{/if}><{$member.tribeuser_sender_link}>\r\n	<{if $member.userCanEditAndDelete || ($userCanEditAndDelete && !$member.owner)}>\r\n	<form action=\"<{$icms_url}>/modules/<{$icms_dirname}>/tribes.php\" method=\"post\">\r\n	  <input type=\"hidden\" value=\"<{$profile_tribe.tribes_id}>\" name=\"tribes_id\" />\r\n	  <input type=\"hidden\" value=\"<{xoAppUrl /modules/<{$icms_dirname}>/tribes.php}>?tribes_id=<{$profile_tribe.tribes_id}>\" name=\"redirect_page\" />\r\n	  <input type=\"hidden\" value=\"<{$member.tribeuser_id}>\" name=\"tribeuser_id\" />\r\n	  <input type=\"hidden\" value=\"1\" name=\"confirm\" />\r\n	  <input type=\"hidden\" value=\"deltribeuser\" name=\"op\" />\r\n	  <{$token}>\r\n	  <input name=\"submit\" type=\"image\" alt=\"<{$lang_delete}>\" title=\"<{$lang_delete}>\" src=\"<{$delete_image}>\" style=\"vertical-align:middle\" />\r\n	</form>\r\n	<{/if}>\r\n  </div>\r\n  <{if $smarty.foreach.members.iteration is div by $rowitems || $smarty.foreach.members.last == TRUE}>\r\n  <div class=\"clear\"></div>\r\n  <{/if}>\r\n  <{/foreach}>\r\n</div>\r\n<{/if}>\r\n<{if !$showContent}><div id=\"profile-nocontent\" class=\"outer\"><p class=\"odd\"><{$lang_joinfirst}></p></div><{/if}>\r\n<div class=\"clear\"></div>\r\n<{if $showContent}><{includeq file=\'db:system_notification_select.html\'}><{/if}>\r\n<{/if}>\r\n<{if $profile_tribes_search}>\r\n<div id=\"profile-tribes-search\" class=\"odd outer\">\r\n  <form method=\"post\" action=\"tribes.php\" id=\"searchtribes\" name=\"searchttribes\">\r\n    <strong><{$lang_tribes_search}>:</strong>\r\n    <input type=\"text\" maxlength=\"255\" size=\"20\" name=\"search_title\" id=\"search_title\" />\r\n    <input type=\"submit\" value=\"<{$lang_tribes_search_submit}>\" name=\"search_submit\" />\r\n  </form>\r\n</div>\r\n<div class=\"clear\"></div>\r\n<{/if}>\r\n<{if $profile_tribes.search}>\r\n<div class=\"profile-tribes-container outer\">\r\n<h2 class=\"head\"><{$lang_tribes_search_title}></h2>\r\n<{foreach name=tribes item=tribe from=$profile_tribes.search}>\r\n<div class=\"profile-tribes-tribe\" style=\"width:<{$itemwidth}>%\">\r\n  <{$tribe.picture_link}>\r\n  <br /><{$tribe.title}><br />\r\n</div>\r\n<{if $smarty.foreach.tribes.iteration is div by $rowitems || $smarty.foreach.tribes.last == TRUE}>\r\n<div class=\"clear\"></div>\r\n<{/if}>\r\n<{/foreach}>\r\n</div>\r\n<{elseif $lang_search_noresults}>\r\n<div id=\"profile-nocontent\" class=\"outer\"><p class=\"odd\"><{$lang_search_noresults}></p></div>\r\n<{/if}>\r\n<{if $profile_tribes.own}>\r\n<div class=\"profile-tribes-container outer\">\r\n<h2 class=\"head\"><{$lang_tribes_own}></h2>\r\n<{foreach name=tribes item=tribe from=$profile_tribes.own}>\r\n<div class=\"profile-tribes-tribe\" style=\"width:<{$itemwidth}>%\">\r\n  <{$tribe.picture_link}>\r\n  <br /><{$tribe.title}><br />\r\n  <{if $tribe.userCanEditAndDelete}>\r\n  <{$tribe.editItemLink}><{$tribe.deleteItemLink}>\r\n  <{/if}>\r\n</div>\r\n<{if $smarty.foreach.tribes.iteration is div by $rowitems || $smarty.foreach.tribes.last == TRUE}>\r\n<div class=\"clear\"></div>\r\n<{/if}>\r\n<{/foreach}>\r\n</div>\r\n<{/if}>\r\n<{if $profile_tribes.member}>\r\n<div class=\"profile-tribes-container outer\">\r\n<h2 class=\"head\"><{$lang_tribes_membership}></h2>\r\n<{foreach name=tribes item=tribe from=$profile_tribes.member}>\r\n<div class=\"profile-tribes-tribe\" style=\"width:<{$itemwidth}>%\">\r\n  <{$tribe.picture_link}>\r\n  <br /><{$tribe.title}><br />\r\n  <{if $tribe.userCanEditAndDelete}>\r\n  <{$tribe.editItemLink}><{$tribe.deleteItemLink}>\r\n  <{/if}>\r\n</div>\r\n<{if $smarty.foreach.tribes.iteration is div by $rowitems || $smarty.foreach.tribes.last == TRUE}>\r\n<div class=\"clear\"></div>\r\n<{/if}>\r\n<{/foreach}>\r\n</div>\r\n<{/if}>\r\n<{if $icmspersistable_delete_confirm}>\r\n<{$icmspersistable_delete_confirm}>\r\n<{/if}>\r\n<{if $lang_nocontent}><div id=\"profile-nocontent\" class=\"outer\"><p class=\"odd\"><{$lang_nocontent}></p></div><{/if}>'),
(83,'<div id=\"profile_header\"><{$profile_module_home}></div><br />\r\n<{if $deleted}><div class=\"errorMsg\"><{$deleted}></div><br /><{/if}>\r\n<{counter assign=catcount print=false}>\r\n<div class=\"profile-profile-group1\">\r\n  <{foreach item=category from=$categories}>\r\n  <{if isset($category.fields)}>\r\n  <{if $catcount gt $break}>\r\n</div>\r\n<div class=\"profile-profile-group2\">\r\n  <{/if}>\r\n  <div style=\"padding-bottom:10px;\">\r\n  <table class=\"outer\" cellpadding=\"4\" cellspacing=\"1\">\r\n    <tr>\r\n      <th colspan=\"2\" align=\"center\"><{$category.title}></th>\r\n    </tr>\r\n    <{foreach item=field from=$category.fields}>\r\n    <tr>\r\n      <td class=\"head\"><{$field.title}></td>\r\n      <td class=\"even\"><{$field.value}></td>\r\n    </tr>\r\n    <{/foreach}>\r\n  </table>\r\n  </div>\r\n  <{/if}>\r\n  <{counter}>\r\n  <{/foreach}>\r\n</div>\r\n\r\n<{if $modules}>\r\n<{counter assign=modcount print=false}>\r\n<{foreach item=module from=$modules}>\r\n<{counter}>\r\n<div style=\"width: 48%; float: <{$smarty.const._GLOBAL_LEFT}>; padding: 0px 5px 10px 0px;<{if $modcount % 2 == 0}> clear: <{$smarty.const._GLOBAL_LEFT}>;<{/if}>\">\r\n  <h4><img src=\"<{$module.results.0.image}>\" alt=\"<{$module.name}>\" />&nbsp;<{$module.name}></h4>\r\n  <{foreach item=result from=$module.results}>\r\n  <strong><a href=\"<{$result.link}>\"><{$result.title}></strong></b><br />\r\n  <small>(<{$result.time}>)</small><br />\r\n  <{/foreach}>\r\n  <{$module.showall_link}>\r\n</div>\r\n<{/foreach}>\r\n<{/if}>\r\n\r\n<{include file=\"db:profile_footer.html\"}>'),
(84,'<{include file=\"db:profile_header.html\"}>\r\n<{if $profile_videosform}>\r\n<{if $hideForm}>\r\n<div class=\"profile-form outer\">\r\n  <h2 class=\"head\">\r\n    <a href=\"#add-video\" onclick =\"jQuery(\'div.profile-album-form\').toggle(400);\" name=\"add-video\"><img src=\"images/toggle.png\" />&nbsp;<{$lang_videosform_title}></a>\r\n  </h2>\r\n  <div class=\"profile-album-form\" style=\"display: none\">\r\n    <{includeq file=\'db:system_common_form.html\' form=$profile_videosform}>\r\n  </div>\r\n</div>\r\n<{else}>\r\n<{includeq file=\'db:system_common_form.html\' form=$profile_videosform}>\r\n<{/if}>\r\n<{/if}>\r\n<{if $profile_videos}>\r\n<div id=\"profile-video-container\" class=\"outer\">\r\n  <table width=\"100%\" cellspacing=\"1\" cellpadding=\"4\">\r\n    <tbody>\r\n      <tr align=\"center\">\r\n        <th width=\"350px\"><{$lang_video}></th>\r\n        <th><{$lang_description}></th>\r\n        <{if $actions}><th width=\"130px\"><{$lang_actions}></th><{/if}>\r\n      </tr>\r\n      <{foreach item=video from=$profile_videos}>\r\n      <{cycle values=\'even,odd\' assign=class}>\r\n      <tr class=\"<{$class}>\">\r\n        <td width=\"350px\" align=\"center\"><{$video.video_content}></td>\r\n        <td style=\"vertical-align:top;\"><strong><{$video.video_title}></strong><br /><br /><{$video.video_desc}></td>\r\n        <{if $video.userCanEditAndDelete}>\r\n        <td width=\"130px\" align=\"center\"><{$video.editItemLink}><{$video.deleteItemLink}></td>\r\n        <{/if}>\r\n      </tr>\r\n      <{/foreach}>\r\n    </tbody>\r\n  </table>\r\n</div>\r\n<{if $profile_videos_pagenav}>\r\n<div class=\"pagination\"><{$profile_videos_pagenav}></div>\r\n<{/if}>\r\n<{/if}>\r\n<{if $icmspersistable_delete_confirm}>\r\n<{$icmspersistable_delete_confirm}>\r\n<{/if}>\r\n<{if $lang_nocontent}><div id=\"profile-nocontent\" class=\"outer\"><p class=\"odd\"><{$lang_nocontent}></p></div><{/if}>\r\n<{if $profile_videos || $lang_nocontent}><{includeq file=\'db:system_notification_select.html\'}><{/if}>'),
(85,'<{section name=i loop=$block.friends}>\r\n<a href=\"<{$icms_url}>/pmlite.php?send2=1&amp;to_userid=<{$block.friends[i].friend_uid}>\" class=\"block-profile-pm\"><img src=\"<{$icms_url}>/images/icons/pm.gif\" alt=\"\" /></a>&nbsp;<{$block.friends[i].uname}>\r\n<{/section}>\r\n<{if $block.jQuery}><script type=\"text/javascript\"><{$block.jQuery}></script><{/if}>'),
(86,'<div id=\"usermenu\">\r\n  <{foreach item=link from=$block}>\r\n  <a <{if $link.extra}>class=\"highlight\"<{/if}> href=\"<{$link.url}>\" title=\"<{$link.name}>\"><{$link.name}><{if $link.extra}> (<{$link.extra}>)<{/if}></a>\r\n  <{/foreach}>\r\n</div>'),
(87,'<div class=\"content_headertable\">\r\n	<{if $content_module_home}><span class=\"content_modulename\"><{$content_module_home}></span><{/if}>\r\n	<{if $content_category_path}>\r\n	<span class=\"content_breadcrumb\">\r\n		<{if $content_module_home}>&gt;&nbsp;<{/if}><{$content_category_path}>\r\n	</span>\r\n	<{/if}>\r\n</div>\r\n<div style=\"clear: both;\"></div>'),
(88,'<{if $content_content_comment}>\r\n<div style=\"text-align:center;\"><{$commentsnav}><{$lang_notice}></div>\r\n<a name=\"comments_container\">&nbsp;</a>\r\n<{if $comment_mode == \"flat\"}>\r\n<{include file=\"db:system_comments_flat.html\"}>\r\n<{elseif $comment_mode == \"thread\"}>\r\n<{include file=\"db:system_comments_thread.html\"}>\r\n<{elseif $comment_mode == \"nest\"}>\r\n<{include file=\"db:system_comments_nest.html\"}>\r\n<{/if}>\r\n<{/if}>\r\n<{if $content_module_home != \'\'}><{includeq file=\'db:system_notification_select.html\'}><{/if}>'),
(89,'<{if $content_content_title}><h1><{$content_content_title}></h1><{/if}>\r\n<{if $content_content_info}><p><{$content_content_info}></p><{/if}>\r\n<{if $content_content_singleview}><{$content_content_singleview}><{/if}>\r\n<{if $content_content_table}><{$content_content_table}><{/if}>\r\n<{if $addcontent}><{includeq file=\'db:system_common_form.html\' form=$addcontent}><{/if}>'),
(90,'<{includeq file=\'db:content_header.html\'}>\r\n<{foreach item=content_content from=$content_contents}>\r\n<div class=\"content_content_container\">\r\n	<h1 class=\"content_content_title\"><{$content_content.itemLink}> <{if $content_content.userCanEditAndDelete}><{$content_content.editItemLink}><{$content_content.deleteItemLink}><{/if}></h1>\r\n	<{if $showInfo}><div class=\"content_content_info\"><{$content_content.content_info}></div><{/if}>\r\n	<div class=\"content_content_body\"><{$content_content.content_lead}></div>\r\n	<div class=\"content_admin_links\">\r\n		<{if $content_content.content_tags}>\r\n		<div class=\"content_content_tags\">\r\n			<strong><{$smarty.const._CO_CONTENT_CONTENT_CONTENT_TAGS}>:</strong> <{$content_content.content_tags}>\r\n		</div>\r\n		<{/if}>\r\n		<div class=\"content_content_comments\"><{$content_content.content_comment_info}></div>\r\n		<div style=\"clear: left\"></div>\r\n	</div>\r\n</div>\r\n<{/foreach}>\r\n<{if $navbar}><div id=\"content_navbar\">Pages: <{$navbar}></div><{/if}>\r\n<{includeq file=\'db:content_footer.html\'}>'),
(91,'<{if $content_content}>\r\n<style type=\"text/css\"><{$content_content.content_css}></style>\r\n<div class=\"content_content_container\">\r\n	<h1 class=\"content_content_title\">\r\n		<{$content_content.content_title}>\r\n		<{if $content_content.userCanEditAndDelete}><{$content_content.editItemLink}><{$content_content.deleteItemLink}><{/if}>\r\n	</h1>\r\n	<{if $showInfo}><div class=\"content_content_info\"><{$content_content.content_info}></div><{/if}>\r\n	<div class=\"content_content_body\"><{$content_content.content_body}></div>\r\n	<{if $showSubs && $content_content.content_hassubs}>\r\n	<hr style=\"margin:20px;\" />\r\n	<div id=\"content_content_subs\">\r\n		<div class=\"content_content_subs_header\"><{$smarty.const._MD_CONTENT_SUBS}></div>\r\n		<{foreach item=sub from=$content_content.content_subs}>\r\n		<div style=\"margin:5px;\" class=\"content_content_subs_item <{cycle values=\"even,odd\"}>\">\r\n			<h3 class=\"content_content_subs_item_title\" style=\"margin:0;\"><{$sub.itemLink}></h3>\r\n			<div class=\"content_content_subs_item_teaser\"><{$sub.content_body}></div>\r\n		</div>\r\n		<{/foreach}>\r\n	</div>\r\n	<{/if}>\r\n</div>\r\n<{/if}>'),
(92,'<{includeq file=\'db:content_header.html\'}>\r\n<{if $content_content}>\r\n<{includeq file=\'db:content_single_content.html\'}>\r\n<{else}>\r\n<{foreach item=content_content from=$content_contents}>\r\n<{includeq file=\'db:content_single_content.html\'}>\r\n<{/foreach}>\r\n<{/if}>\r\n<{if $content_contentform}><{includeq file=\'db:system_common_form.html\' form=$content_contentform}><{/if}>\r\n<{if $icmspersistable_delete_confirm}><{$icmspersistable_delete_confirm}><{/if}>\r\n<{includeq file=\'db:content_footer.html\'}>'),
(93,'<h1><{$smarty.const._AM_CONTENT_REQUIREMENTS}></h1>\r\n<p><{$smarty.const._AM_CONTENT_REQUIREMENTS_INFO}></p>\r\n<{foreach item=failed_requirement from=$failed_requirements}>\r\n<li><{$failed_requirement}></li>\r\n<{/foreach}>\r\n<p><{$smarty.const._AM_CONTENT_REQUIREMENTS_SUPPORT}></p>'),
(94,'<ul>\r\n	<{foreach from=$menus key=key item=menu}>\r\n	<li<{if $block.showsubs && $menu.hassubs}> class=\"menuparent\"<{/if}>>\r\n		<a class=\"menuMain\" href=\"<{$icms_url}>/modules/content/content.php?page=<{$menu.menu}>\"><{$menu.title}></a>\r\n		<{if $block.showsubs && $menu.hassubs}><{includeq file=\'db:content_content_menu_structure.html\' menus=$menu.subs}><{/if}>\r\n	</li>\r\n	<{/foreach}>\r\n</ul>'),
(95,'<{if $block.content_content}>\r\n<style type=\"text/css\"><{$block.content_content.content_css}></style>\r\n<div class=\"content_content_container\">\r\n	<h1 class=\"content_content_title\"><{$block.content_content.content_title}></h1>\r\n	<{if $block.showInfo}>	<div class=\"content_content_info\"><{$block.content_content.content_info}></div><{/if}>\r\n	<div class=\"content_content_body\"><{$block.content_content.content_body}></div>\r\n	<{if $block.content_content.userCanEditAndDelete}>\r\n	<div class=\"content_admin_links\"><{$block.content_content.editItemLink}><{$block.content_content.deleteItemLink}></div>\r\n	<{/if}>\r\n	<{if $block.showSubs && $block.content_content.content_hassubs}>\r\n	<hr style=\"margin:20px;\" />\r\n	<div id=\"content_content_subs\">\r\n		<div class=\"content_content_subs_header\"><{$smarty.const._MD_CONTENT_SUBS}></div>\r\n		<{foreach item=sub from=$block.content_content.content_subs}>\r\n		<div style=\"margin:5px;\" class=\"content_content_subs_item <{cycle values=\"even,odd\"}>\">\r\n			<h3 class=\"content_content_subs_item_title\" style=\"margin:0;\"><{$sub.content_url}></h3>\r\n			<div class=\"content_content_subs_item_teaser\"><{$sub.content_body}></div>\r\n		</div>\r\n		<{/foreach}>\r\n	</div>\r\n	<{/if}>\r\n</div>\r\n<{/if}>'),
(96,'<div class=\"contentmenu\">\r\n	<ul class=\"primary-nav blue\">\r\n		<{foreach from=$block.menu key=key item=menu}>\r\n		<li>\r\n			<a class=\"menuMain\" href=\"<{$icms_url}>/modules/content/content.php?page=<{$menu.menu}>\"><{$menu.title}></a>\r\n			<{if $block.showsubs && $menu.hassubs}><{includeq file=\'db:content_content_menu_structure.html\' menus=$menu.subs}><{/if}>\r\n		</li>\r\n		<{/foreach}>\r\n	</ul>\r\n</div>'),
(98,'<div class=\"panel-content content\">\r\n\r\n	<{include file=\"db:admin/element_linkedoptionlist.html\"}>\r\n\r\n    <{include file=\"db:admin/element_options_delimiter_choice.html\"}>\r\n    \r\n       <div class=\"form-item\">\r\n    <fieldset>\r\n		<legend>If the options are linked -- or are {FULLNAMES} or {USERNAMES}</legend>\r\n		<div class=\"form-item\">\r\n		<fieldset>\r\n			<legend>Limit them to values from the groups selected here</legend>\r\n			<select id=\"element-formlink_scope\" name=\"element_formlink_scope[]\" size=\"10\" multiple>\r\n			<{html_options options=$content.formlink_scope_options selected=$content.formlink_scope}>\r\n			</select>\r\n            <br/><br/>\r\n            <fieldset>\r\n                <div class=\"form-radios\">\r\n                    <label for=\"elements-ele_value[checkbox_scopelimit]-0\"><input type=\"radio\" id=\"elements-ele_value[checkbox_scopelimit]-0\" name=\"elements-ele_value[checkbox_scopelimit]\" value=\"0\"<{if $content.ele_value.checkbox_scopelimit eq 0}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_FORMLINK_SCOPELIMIT_NO}></label>\r\n                </div>\r\n                <div class=\"form-radios\"><label for=\"elements-ele_value[checkbox_scopelimit]-1\"><input type=\"radio\" id=\"elements-ele_value[checkbox_scopelimit]-1\" name=\"elements-ele_value[checkbox_scopelimit]\" value=\"1\"<{if $content.ele_value.checkbox_scopelimit eq 1}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_FORMLINK_SCOPELIMIT_YES}></label></div>\r\n            </fieldset>\r\n            <fieldset>\r\n                <div class=\"form-radios\">\r\n                    <label for=\"elements-ele_value[checkbox_formlink_anyorall]-0\"><input type=\"radio\" id=\"elements-ele_value[checkbox_formlink_anyorall]-0\" name=\"elements-ele_value[checkbox_formlink_anyorall]\" value=\"0\"<{if $content.ele_value.checkbox_formlink_anyorall eq 0}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_FORMLINK_ANYALL_ANY}></label>\r\n                </div>\r\n                <div class=\"form-radios\">\r\n                    <label for=\"elements-ele_value[checkbox_formlink_anyorall]-1\"><input type=\"radio\" id=\"elements-ele_value[checkbox_formlink_anyorall]-1\" name=\"elements-ele_value[checkbox_formlink_anyorall]\" value=\"1\"<{if $content.ele_value.checkbox_formlink_anyorall eq 1}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_FORMLINK_ANYALL_ALL}></label>\r\n                </div>\r\n            </fieldset>\r\n            <div class=\"description\">\r\n                <{$smarty.const._AM_ELE_FORMLINK_SCOPE_DESC}>\r\n            </div>\r\n        </fieldset>\r\n	</fieldset>\r\n</div>  \r\n    \r\n  <{include file=\"db:admin/element_linkedfilter.html\"}>\r\n  \r\n  <{include file=\"db:admin/element_linkedsortoptions.html\"}>\r\n    \r\n  <{include file=\"db:admin/alternate_fields_for_linked_elements.html\"}>\r\n  \r\n</div>\r\n\r\n<script>\r\n$(\"#snapshot-<{$content.ele_value.snapshot}>\").attr(\'checked\',1);\r\n    \r\n    $(\'input[name=\"linked_yesno\"]\').change(function() {\r\n        if($(\'input[name=\"linked_yesno\"]:checked\').val() == 1) {\r\n            $(\'#snapshot-values\').show(200);\r\n        } else {\r\n            $(\'#snapshot-values\').hide(200);\r\n        }\r\n    });\r\n</script>'),
(100,'<div class=\"panel-content content\">\r\n  <fieldset>\r\n    <legend>Options for File Upload</legend>\r\n\r\n    <div class=\"form-item\">\r\n      <label for=\"elements-ele_value[1]\">What file extensions are allowed for uploading?</label>\r\n      <input id=\"elements-ele_value[1]\" name=\"elements-ele_value[1]\" type=text size=75 maxlength=255 value=\"<{$content.extensions}>\"></input>\r\n      <div class=\"description\">\r\n	<p>Separate file extensions with commas, ie: doc,docx,pdf,jpg</p>\r\n      </div>\r\n    </div>\r\n\r\n    <div class=\"form-item\">\r\n      <label for=\"elements-ele_value[2]\">Should users be able to link directly to the files that are uploaded?</label>\r\n      <input id=\"elements-ele_value[2]\" name=\"elements-ele_value[2]\" type=radio value=1 <{$content.directlinkyes}>>Yes</input>\r\n      <input id=\"elements-ele_value[2]\" name=\"elements-ele_value[2]\" type=radio value=0 <{$content.directlinkno}>>No</input>\r\n      <div class=\"description\">\r\n	<p>If users can link directly to files, then someone who knows the name of the file and where it\'s located, could potentially download it from your website, regardless of whether they are logged in, or have permission to view the form.  If you set this to \'No\' then files will only be accessible through special links in Formulize.</p>\r\n      </div>\r\n    </div>\r\n\r\n    <div class=\"form-item\">\r\n	    <label for=\"elements-ele_value[0]\">Maximum file size?</label>\r\n	    <input id=\"elements-ele_value[0]\" name=\"elements-ele_value[0]\" type=\"text\" value=\"<{$content.maxfilesize}>\"></input>\r\n	    <div class=\"description\">\r\n            <p>In megabytes.</p>\r\n            </div>\r\n    </div>\r\n	\r\n  </fieldset>\r\n</div>'),
(101,'<div class=\"panel-content content\">\r\n  <fieldset>\r\n    <legend>API Key</legend>\r\n    <div class=\"form-item\">\r\n	    <input id=\"elements-ele_value[apikey]\" name=\"elements-ele_value[apikey]\" type=\"text\" value=\"<{$content.apikey}>\"></input>\r\n    </div>\r\n  </fieldset>\r\n    <div class=\"description\">If you use Google to login to this website, then use the API Key from the same Google project you use to handle the authentication. IMPORTANT: make sure to enable the Google Places API for that project too!</div>\r\n</div>'),
(102,'<div class=\"panel-content content\">\r\n  <fieldset>\r\n    <legend>API Key</legend>\r\n    <div class=\"form-item\">\r\n	    <input id=\"elements-ele_value[apikey]\" name=\"elements-ele_value[apikey]\" type=\"text\" value=\"<{$content.apikey}>\"></input>\r\n    </div>\r\n  </fieldset>\r\n    <fieldset>\r\n    <legend>OAuth 2.0 Client ID</legend>\r\n    <div class=\"form-item\">\r\n	    <input id=\"elements-ele_value[clientid]\" name=\"elements-ele_value[clientid]\" type=\"text\" value=\"<{$content.clientid}>\"></input>\r\n    </div>\r\n  </fieldset>\r\n  <fieldset>\r\n    <legend>Project Number (under IAM & Admin > Settings)</legend>\r\n    <div class=\"form-item\">\r\n	    <input id=\"elements-ele_value[projectnumber]\" name=\"elements-ele_value[projectnumber]\" type=\"text\" value=\"<{$content.projectnumber}>\"></input>\r\n    </div>\r\n  </fieldset>\r\n  <fieldset>\r\n    <legend>Folders</legend>\r\n    <div class=\"form-item\">\r\n        <label for=\"elements-ele_value[includeGoogleDrive]\"><input class=\'includeGoogleDrive\' id=\"elements-ele_value[includeGoogleDrive]\" name=\"elements-ele_value[includeGoogleDrive]\" type=\"checkbox\" value=\"1\" <{if $content.includeGoogleDrive == 1}>checked<{/if}> /> Allow access to \'Google Drive\'</label> &mdash; Default folder: <input id=\"elements-ele_value[googleDriveDefaultFolder]\" name=\"elements-ele_value[googleDriveDefaultFolder]\" type=\"text\" size=50 maxlength=255 value=\"<{$content.googleDriveDefaultFolder}>\" /><br>\r\n        <label for=\"elements-ele_value[includeSharedDrives]\"><input class=\'includeSharedDrives\' id=\"elements-ele_value[includeSharedDrives]\" name=\"elements-ele_value[includeSharedDrives]\" type=\"checkbox\" value=\"1\" <{if $content.includeSharedDrives == 1}>checked<{/if}> /> Allow access to \'Shared Drives\'</label> &mdash; Default folder: <input id=\"elements-ele_value[sharedDrivesDefaultFolder]\" name=\"elements-ele_value[sharedDrivesDefaultFolder]\" type=\"text\" size=50 maxlength=255 value=\"<{$content.sharedDrivesDefaultFolder}>\" onfocus=\"jQuery(\'.includeSharedDrives\').attr(\'checked\', \'checked\');\" />\r\n    </div>\r\n  </fieldset>\r\n  <fieldset>\r\n    <legend>Mime Types (comma separated list, leave blank to include all types)</legend>\r\n    <div class=\"form-item\">\r\n	    <input id=\"elements-ele_value[mimetypes]\" name=\"elements-ele_value[mimetypes]\" type=\"text\" value=\"<{$content.mimetypes}>\"></input>\r\n    </div>\r\n  </fieldset>\r\n  <fieldset>\r\n    <legend>Options</legend>\r\n    <div class=\"form-item\">\r\n        <label for=\"elements-ele_value[multiselect]-0\"><input id=\"elements-ele_value[multiselect]-0\" name=\"elements-ele_value[multiselect]\" type=\"radio\" value=\"0\" <{if $content.multiselect != 1}>checked<{/if}> /> Allow only one file to be selected</label><br>\r\n        <label for=\"elements-ele_value[multiselect]-1\"><input id=\"elements-ele_value[multiselect]-1\" name=\"elements-ele_value[multiselect]\" type=\"radio\" value=\"1\" <{if $content.multiselect == 1}>checked<{/if}> /> Allow multiple files to be selected</label><br><br>\r\n	    <label for=\"elements-ele_value[upload]\"><input id=\"elements-ele_value[upload]\" name=\"elements-ele_value[upload]\" type=\"checkbox\" value=\"1\" <{if $content.upload == 1}>checked<{/if}> /> Allow files to be uploaded</label>        \r\n    </div>\r\n  </fieldset>\r\n    <div class=\"description\">If you use Google to login to this website, then use the API Key and Client ID and Project Number from the same Google project you use to handle the authentication. IMPORTANT: make sure to enable to Google Picker API for that project too!</div>\r\n</div>'),
(103,'<div class=\"panel-content content\">\r\n    <fieldset>\r\n        <div class=\"form-item required\">\r\n            <label for=\"elements-ele_value[0]\"><{$smarty.const._AM_ELE_MIN_VALUE}><em>*</em></label>\r\n	          <input type=\"text\" id=\"elements-ele_value[0]\" name=\"elements-ele_value[0]\" value=\"<{$content.ele_value[0]}>\" size=\"5\" maxlength=\"5\"/>\r\n        </div>\r\n\r\n        <div class=\"form-item required\">\r\n            <label for=\"elements-ele_value[1]\"><{$smarty.const._AM_ELE_MAX_VALUE}><em>*</em></label>\r\n            <input type=\"text\" id=\"elements-ele_value[1]\" name=\"elements-ele_value[1]\" value=\"<{$content.ele_value[1]}>\" size=\"5\" maxlength=\"5\"/>\r\n        </div>\r\n\r\n        <div class=\"form-item required\">\r\n            <label for=\"elements-ele_value[2]\"><{$smarty.const._AM_ELE_STEPSIZE}><em>*</em></label>\r\n            <input type=\"text\" id=\"elements-ele_value[2]\" name=\"elements-ele_value[2]\" value=\"<{$content.ele_value[2]}>\" size=\"5\" maxlength=\"5\"/>\r\n        </div>\r\n\r\n        <div class=\"form-item required\">\r\n            <label for=\"elements-ele_value[3]\"><{$smarty.const._AM_ELE_DEFAULT}><em>*</em></label>\r\n            <input type=\"text\" id=\"elements-ele_value[3]\" name=\"elements-ele_value[3]\" value=\"<{$content.ele_value[3]}>\" size=\"5\" maxlength=\"5\"/>\r\n        </div>\r\n    </fieldset>\r\n</div>'),
(104,'<div class=\"panel-content content\">\r\n  <fieldset>\r\n    <legend>Number Format</legend>\r\n    <div class=\"form-item\">\r\n	    <input id=\"elements-ele_value[format]\" name=\"elements-ele_value[format]\" type=\"text\" value=\"<{$content.format}>\"></input>\r\n    </div>\r\n  </fieldset>\r\n    <div class=\"description\">Specify the number format using X\'s for the digits, ie: 416 686 3766 would be: XXX XXX XXXX and (416) 686-3766 would be: (XXX) XXX-XXXX</div>\r\n</div>'),
(105,'<div class=\"panel-content content\">  <fieldset>    <legend>Options for Province List</legend>  		<div class=\"form-item\">      <label for=\"elements-ele_value[0]\">What province should be set as the default?</label>	  	    <select id=\"elements-ele_value[0]\" name=\"elements-ele_value[0]\">		  <{html_options options=$content.provinceOptions selected=$content.provinceSelected}>	    </select>    </div>		<div class=\"form-item\">      <label for=\"elements-ele_value[1]\">What type of form element should be used to select the province?</label>	  	    <select id=\"elements-ele_value[1]\" name=\"elements-ele_value[1]\">		  <{html_options options=$content.elementOptions selected=$content.elementSelected}>	    </select>    </div>		<div class=\"form-item\">      <label for=\"elements-ele_value[2]\">How should the provinces be ordered?</label>	  	    <select id=\"elements-ele_value[2]\" name=\"elements-ele_value[2]\">		  <{html_options options=$content.sortOptions selected=$content.sortSelected}>	    </select>    </div>	  </fieldset></div>'),
(106,'<div class=\"panel-content content\">\r\n    <fieldset>\r\n        <div class=\"form-item required\">\r\n            <label for=\"elements-ele_value[0]\"><{$smarty.const._AM_ELE_MIN_VALUE}><em>*</em></label>\r\n	          <input type=\"text\" id=\"elements-ele_value[0]\" name=\"elements-ele_value[0]\" value=\"<{$content.ele_value[0]}>\" size=\"5\" maxlength=\"5\"/>\r\n        </div>\r\n\r\n        <div class=\"form-item required\">\r\n            <label for=\"elements-ele_value[1]\"><{$smarty.const._AM_ELE_MAX_VALUE}><em>*</em></label>\r\n            <input type=\"text\" id=\"elements-ele_value[1]\" name=\"elements-ele_value[1]\" value=\"<{$content.ele_value[1]}>\" size=\"5\" maxlength=\"5\"/>\r\n        </div>\r\n\r\n        <div class=\"form-item required\">\r\n            <label for=\"elements-ele_value[2]\"><{$smarty.const._AM_ELE_STEPSIZE}><em>*</em></label>\r\n            <input type=\"text\" id=\"elements-ele_value[2]\" name=\"elements-ele_value[2]\" value=\"<{$content.ele_value[2]}>\" size=\"5\" maxlength=\"5\"/>\r\n        </div>\r\n\r\n        <div class=\"form-item required\">\r\n            <label for=\"elements-ele_value[3]\"><{$smarty.const._AM_ELE_DEFAULT}><em>*</em></label>\r\n            <input type=\"text\" id=\"elements-ele_value[3]\" name=\"elements-ele_value[3]\" value=\"<{$content.ele_value[3]}>\" size=\"5\" maxlength=\"5\"/>\r\n        </div>\r\n    </fieldset>\r\n</div>'),
(108,'<table><tr><td class=\'appcatspacer\'></td><td>\r\n<h1><{$cat_name}></h1>\r\n\r\n<{if $noforms}>\r\n\r\n	<p><{$noforms}></p>\r\n\r\n<{else}>\r\n\r\n<{foreach item=thisform from=$formData}>\r\n\r\n	<p><a href=\"<{$xoops_url}>/modules/formulize/index.php?fid=<{$thisform.fid}>\"><{$thisform.title}></a></p>\r\n\r\n<{/foreach}>\r\n\r\n<{/if}>\r\n\r\n</td><td class=\'appcatspacer\'></td></tr></table>'),
(109,'<{foreach item=appData from=$allAppData}>\r\n\r\n<table><tr><td class=\'appcatspacer\'></td><td>\r\n<div class=\'applinks\'>\r\n<h1><{$appData.app_name}></h1>\r\n\r\n<{if $appData.noforms}>\r\n\r\n	<p><{$appData.noforms}></p>\r\n\r\n<{else}>\r\n\r\n<{foreach item=thisform from=$appData.formData}>\r\n\r\n	<p><a href=\"<{$thisform.url}>\"><{$thisform.title}></a></p>\r\n\r\n<{/foreach}>\r\n\r\n<{/if}>\r\n</div>\r\n</td><td class=\'appcatspacer\'></td></tr></table>\r\n\r\n<{/foreach}>'),
(110,'<form name=controls id=controls action=<{$currentURL}> method=post>\r\n\r\n<{$toptemplate}>\r\n\r\n<{foreach key=hidename item=hidevalue from=$hidden}>\r\n	<input type=hidden name=<{$hidename}> value=<{$hidevalue}>>\r\n<{/foreach}>\r\n\r\n<input type=hidden name=calview id=calview value=\"<{$calview}>\">\r\n\r\n\r\n<!-- calendar start -->\r\n<table class=outer width=\"98%\"><tr>\r\n	<!-- Display calendar header - month and year. -->\r\n    <th><a href=\"\" onclick=\"javascript: changeMonth(\'<{ $previousMonth }>\'); return false;\">&lt;</a></th>\r\n    <th colspan=\"5\">\r\n        <table><tr>\r\n            <th><{ $MonthNames[$dateMonthZeroIndex] }> <{ $dateYear }></th>\r\n            <th align=right>\r\n            	<select id=monthSelector onchange=\"changeSelector()\">\r\n	                <{foreach key=monthKey item=monthName from=$monthSelector}>\r\n	                    <option value=\"<{ $monthKey }>\" <{ if $monthKey == $dateMonth }>selected<{/if}>><{ $monthName }></option>\r\n	                <{/foreach}>\r\n	        	</select>\r\n	        </th>\r\n            <th align=right>\r\n	            <select id=yearSelector onchange=\"changeSelector()\">\r\n	                <{foreach item=yearValue from=$yearSelector}>\r\n	                    <option value=\"<{ $yearValue }>\" <{ if $yearValue == $dateYear }>selected<{/if}>><{ $yearValue }></option>\r\n	                <{/foreach}>\r\n	            </select>\r\n            </th>\r\n       </tr></table>\r\n	</th>\r\n	<th><a href=\"\" onclick=\"javascript: changeMonth(\'<{ $nextMonth }>\'); return false;\">&gt;</a></th>\r\n</tr>\r\n            \r\n           \r\n<!-- Display calendar week day names. -->\r\n<tr>\r\n	<{foreach item=weekName from=$WeekNames}>\r\n	    <td class=head width=\"14%\"><{$weekName}></td>\r\n	<{/foreach}>\r\n</tr>\r\n\r\n\r\n<!-- Display calendar body (days and weeks). -->\r\n<{foreach item=week from=$calendarData}>\r\n	<tr>\r\n	    <{foreach item=day from=$week}>\r\n	        <td class=\"<{ if $rowStyleEven == true }>even<{elseif $rowStyleEven == false }>odd<{/if}>\">\r\n                \r\n				<{if $rights == true && $day[0][0] != \'\'}>\r\n	                <img src=\"<{$xoops_url}>/modules/formulize/images/plus.PNG\" onclick=\"javascript:addNew(\'\', \'<{ $frids }>\', \'<{ $fids }>\',\'<{ $day[0][1] }>\');return false;\" style=\"float: right; cursor: pointer; cursor: hand;\" alt=\"<{ $addItem }>\" title=\"<{ $addItem }>\">\r\n				<{/if}>\r\n			<span style=\\\"horizontal-align: left\\\"><{ $day[0][0] }></span>\r\n\r\n			    <{foreach item=dayItem from=$day[1]}><br><br>&bull;&nbsp;<a href=\"\" onclick=\"javascript:goDetails(\'<{ $dayItem[0] }>\',\'<{ $dayItem[1] }>\',\'<{ $dayItem[2] }>\');return false;\"><{ $dayItem[3] }></a><{if $dayItem[4] == true}><nobr>&nbsp;<a href=\"\" onclick=\"javascript:goDel(\'<{$dayItem[0]}>\', \'<{ $dayItem[1] }>\',\'<{ $dayItem[2] }>\');return false;\"><img src=\"<{$xoops_url}>/modules/formulize/images/x.gif\" border=0 style=\"vertical-align: bottom;\" alt=\"<{$delete}>\" title=\"<{$delete}>\"></a></nobr><{/if}><{/foreach}>\r\n            </td>\r\n	    <{/foreach}>\r\n        <{ if $rowStyleEven == true }><{ assign var=\"rowStyleEven\" value=false }><{elseif $rowStyleEven == false }><{ assign var=\"rowStyleEven\" value=true }><{/if}>\r\n    </tr>\r\n<{/foreach}>\r\n\r\n\r\n\r\n<!-- Display calendar footer. -->\r\n</table>\r\n\r\n<{$bottomtemplate}>\r\n\r\n<input type=hidden name=ventry id=ventry value=\"\">\r\n<input type=hidden name=calfid id=calfid value=\"\">\r\n<input type=hidden name=calfrid id=calfrid value=\"\">\r\n<input type=hidden name=adddate id=adddate value=\"\">\r\n\r\n<input type=hidden name=delentry id=delentry value=\"\">\r\n<input type=hidden name=delfid id=delfid value=\"\">\r\n<input type=hidden name=delfrid id=delfrid value=\"\">\r\n\r\n<input type=submit style=\"width: 140px; visibility: hidden;\" name=submitx value=\'\'></input> \r\n\r\n</form>\r\n\r\n\r\n	\r\n<script type=\'text/javascript\'>\r\n\r\nwindow.document.controls.ventry.value = \'\';\r\nwindow.document.controls.calfid.value = \'\';\r\nwindow.document.controls.calfrid.value = \'\';\r\n\r\nfunction changeSelector()\r\n{\r\n	newDate = window.document.controls.yearSelector.options[\r\n    	window.document.controls.yearSelector.selectedIndex].value \r\n        + \'-\' + window.document.controls.monthSelector.options[\r\n        window.document.controls.monthSelector.selectedIndex].value;\r\n\r\n	changeMonth(newDate);\r\n}\r\n\r\nfunction changeMonth(newDate)\r\n{\r\n	window.document.controls.calview.value = newDate;\r\n	window.document.controls.submit();\r\n}\r\n\r\nfunction addNew(proxy,frid,fid,date) {\r\n	if(proxy) {\r\n		window.document.controls.ventry.value = \'proxy\';\r\n	} else {\r\n		window.document.controls.ventry.value = \'addnew\';\r\n	}\r\n	window.document.controls.adddate.value = date;\r\n	window.document.controls.calfrid.value = frid;\r\n	window.document.controls.calfid.value = fid;\r\n	window.document.controls.submit();\r\n}\r\n\r\nfunction goDetails(viewentry,frid,fid) {\r\n	window.document.controls.ventry.value = viewentry;\r\n	window.document.controls.calfrid.value = frid;\r\n	window.document.controls.calfid.value = fid;\r\n	window.document.controls.submit();\r\n}\r\n\r\nfunction goDel(viewentry,frid,fid) {\r\n	var answer = confirm (\'<{$delconf}>\');\r\n	if (answer) {\r\n		window.document.controls.delentry.value = viewentry;\r\n		window.document.controls.delfrid.value = frid;\r\n		window.document.controls.delfid.value = fid;\r\n		window.document.controls.submit();\r\n	} else {\r\n		return false;\r\n	}\r\n}\r\n\r\n\r\n</script>'),
(111,'<style>\r\na.info\r\n{\r\n    position: relative;\r\n    z-index: 24; \r\n    text-decoration: none\r\n}\r\n\r\na.info:hover\r\n{\r\n	z-index:25; \r\n    background-color: #ffffe1;\r\n    font-weight: bold;\r\n}\r\n\r\na.info div {\r\n    font-weight: normal;\r\n}\r\n\r\na.info div\r\n{\r\n	display: none\r\n}\r\n\r\na.info:hover div\r\n{\r\n    display:block;\r\n    position:absolute;\r\n    top:2em; left:2em; width:15em;\r\n    background-color: #ffffe1 \r\n}\r\n</style>\r\n\r\n\r\n\r\n<form name=formulizecalcontrols id=formulizecalcontrols action=<{$currentURL}> method=post>\r\n\r\n<{foreach key=hidename item=hidevalue from=$hidden}>\r\n	<input type=hidden name=<{$hidename}> value=<{$hidevalue}>>\r\n<{/foreach}>\r\n\r\n<input type=hidden name=calview id=calview value=\"<{$calview}>\">\r\n\r\n\r\n<!-- calendar start -->\r\n<table class=outer><tr>\r\n	<!-- Display calendar header - month and year. -->\r\n    <th colspan=\"7\">\r\n	    <a href=\"\" onclick=\"javascript: changeMonth(\'<{ $previousMonth }>\'); return false;\">&lt;</a>&nbsp;&nbsp;&nbsp;&nbsp;    \r\n	    <{ $MonthNames[$dateMonthZeroIndex] }> <{ $dateYear }>\r\n		&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"\" onclick=\"javascript: changeMonth(\'<{ $nextMonth }>\'); return false;\">&gt;</a>\r\n    </th>\r\n</tr>\r\n            \r\n           \r\n<!-- Display calendar week day names. -->\r\n<tr>\r\n	<{foreach item=weekName from=$WeekNames}>\r\n	    <td class=head><{$weekName}></td>\r\n	<{/foreach}>\r\n</tr>\r\n\r\n\r\n<!-- Display calendar body (days and weeks). -->\r\n<{foreach item=week from=$calendarData}>\r\n	<tr>\r\n	    <{foreach item=day from=$week}>\r\n	        <td class=\"<{ if $rowStyleEven == true }>even<{elseif $rowStyleEven == false }>odd<{/if}>\">\r\n		        <{ if count((array) $day[1]) > 0 }>\r\n	                <a class=info href=\"\" onclick=\"return false;\"><{ $day[0][0] }><div class=\"event-day-listing\"><ul><{foreach item=dayItem from=$day[1]}><li><{ $dayItem[3] }></li><{/foreach}></ul></div></a>\r\n				<{else}>\r\n	                <{ $day[0][0] }>\r\n				<{/if}>\r\n            </td>\r\n	    <{/foreach}>\r\n        <{ if $rowStyleEven == true }><{ assign var=\"rowStyleEven\" value=false }><{elseif $rowStyleEven == false }><{ assign var=\"rowStyleEven\" value=true }><{/if}>\r\n    </tr>\r\n<{/foreach}>\r\n\r\n\r\n\r\n<!-- Display calendar footer. -->\r\n</table>\r\n\r\n\r\n\r\n<input type=hidden name=ventry id=ventry value=\"\">\r\n<input type=hidden name=calfid id=calfid value=\"\">\r\n<input type=hidden name=calfrid id=calfrid value=\"\">\r\n<input type=hidden name=adddate id=adddate value=\"\">\r\n\r\n<input type=submit style=\"width: 140px; visibility: hidden;\" name=submitx value=\'\'></input> \r\n\r\n</form>\r\n\r\n\r\n	\r\n<script type=\'text/javascript\'>\r\n\r\nwindow.document.formulizecalcontrols.ventry.value = \'\';\r\nwindow.document.formulizecalcontrols.calfid.value = \'\';\r\nwindow.document.formulizecalcontrols.calfrid.value = \'\';\r\n\r\nfunction changeSelector()\r\n{\r\n	newDate = window.document.formulizecalcontrols.yearSelector.options[\r\n    	window.document.formulizecalcontrols.yearSelector.selectedIndex].value \r\n        + \'-\' + window.document.formulizecalcontrols.monthSelector.options[\r\n        window.document.formulizecalcontrols.monthSelector.selectedIndex].value;\r\n\r\n	changeMonth(newDate);\r\n}\r\n\r\nfunction changeMonth(newDate)\r\n{\r\n	window.document.formulizecalcontrols.calview.value = newDate;\r\n	window.document.formulizecalcontrols.submit();\r\n}\r\n\r\nfunction addNew(proxy,frid,fid,date) {\r\n	if(proxy) {\r\n		window.document.formulizecalcontrols.ventry.value = \'proxy\';\r\n	} else {\r\n		window.document.formulizecalcontrols.ventry.value = \'addnew\';\r\n	}\r\n	window.document.formulizecalcontrols.adddate.value = date;\r\n	window.document.formulizecalcontrols.calfrid.value = frid;\r\n	window.document.formulizecalcontrols.calfid.value = fid;\r\n	window.document.formulizecalcontrols.submit();\r\n}\r\n\r\nfunction goDetails(viewentry,frid,fid) {\r\n	window.document.formulizecalcontrols.ventry.value = viewentry;\r\n	window.document.formulizecalcontrols.calfrid.value = frid;\r\n	window.document.formulizecalcontrols.calfid.value = fid;\r\n	window.document.formulizecalcontrols.submit();\r\n}\r\n\r\n</script>'),
(112,'<style>\r\na.info\r\n{\r\n    position: relative;\r\n    z-index: 24; \r\n    text-decoration: none\r\n}\r\n\r\na.info:hover\r\n{\r\n	z-index:25; \r\n    background-color: #ffffe1; \r\n}\r\n\r\na.info span\r\n{\r\n	display: none\r\n}\r\n\r\na.info:hover span\r\n{\r\n    display:block;\r\n    position:absolute;\r\n    top:2em; left:2em; width:15em;\r\n    background-color: #ffffe1 \r\n}\r\n</style>\r\n\r\n\r\n\r\n<form name=controls id=controls action=<{$currentURL}> method=post>\r\n\r\n<{foreach key=hidename item=hidevalue from=$hidden}>\r\n	<input type=hidden name=<{$hidename}> value=<{$hidevalue}>>\r\n<{/foreach}>\r\n\r\n<input type=hidden name=calview id=calview value=\"<{$calview}>\">\r\n\r\n\r\n<!-- calendar start -->\r\n<table class=outer><tr>\r\n	<!-- Display calendar header - month and year. -->\r\n    <th colspan=\"7\">\r\n	    <a href=\"\" onclick=\"javascript: changeMonth(\'<{ $previousMonth }>\'); return false;\">&lt;</a>&nbsp;&nbsp;&nbsp;&nbsp;    \r\n	    <{ $MonthNames[$dateMonthZeroIndex] }> <{ $dateYear }>\r\n		&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"\" onclick=\"javascript: changeMonth(\'<{ $nextMonth }>\'); return false;\">&gt;</a>\r\n    </th>\r\n</tr>\r\n            \r\n           \r\n<!-- Display calendar body (days and weeks). -->\r\n<{foreach item=week from=$calendarData}>\r\n	<tr>\r\n	    <{foreach item=day from=$week}>\r\n	        <td class=\"<{ if $rowStyleEven == true }>even<{elseif $rowStyleEven == false }>odd<{/if}>\">\r\n		        <{ if count((array) $day[1]) > 0 }>\r\n	                <a class=info href=\"\" onclick=\"return false;\"><{ $day[0][0] }><span><{foreach item=dayItem from=$day[1]}><li><{ $dayItem[3] }></li><{/foreach}></span></a>\r\n				<{else}>\r\n	                <{ $day[0][0] }>\r\n				<{/if}>\r\n            </td>\r\n	    <{/foreach}>\r\n        <{ if $rowStyleEven == true }><{ assign var=\"rowStyleEven\" value=false }><{elseif $rowStyleEven == false }><{ assign var=\"rowStyleEven\" value=true }><{/if}>\r\n    </tr>\r\n<{/foreach}>\r\n\r\n\r\n\r\n<!-- Display calendar footer. -->\r\n</table>\r\n\r\n\r\n\r\n<input type=hidden name=ventry id=ventry value=\"\">\r\n<input type=hidden name=calfid id=calfid value=\"\">\r\n<input type=hidden name=calfrid id=calfrid value=\"\">\r\n<input type=hidden name=adddate id=adddate value=\"\">\r\n\r\n<input type=submit style=\"width: 140px; visibility: hidden;\" name=submitx value=\'\'></input> \r\n\r\n</form>\r\n\r\n\r\n	\r\n<script type=\'text/javascript\'>\r\n\r\nwindow.document.controls.ventry.value = \'\';\r\nwindow.document.controls.calfid.value = \'\';\r\nwindow.document.controls.calfrid.value = \'\';\r\n\r\nfunction changeSelector()\r\n{\r\n	newDate = window.document.controls.yearSelector.options[\r\n    	window.document.controls.yearSelector.selectedIndex].value \r\n        + \'-\' + window.document.controls.monthSelector.options[\r\n        window.document.controls.monthSelector.selectedIndex].value;\r\n\r\n	changeMonth(newDate);\r\n}\r\n\r\nfunction changeMonth(newDate)\r\n{\r\n	window.document.controls.calview.value = newDate;\r\n	window.document.controls.submit();\r\n}\r\n\r\nfunction addNew(proxy,frid,fid,date) {\r\n	if(proxy) {\r\n		window.document.controls.ventry.value = \'proxy\';\r\n	} else {\r\n		window.document.controls.ventry.value = \'addnew\';\r\n	}\r\n	window.document.controls.adddate.value = date;\r\n	window.document.controls.calfrid.value = frid;\r\n	window.document.controls.calfid.value = fid;\r\n	window.document.controls.submit();\r\n}\r\n\r\nfunction goDetails(viewentry,frid,fid) {\r\n	window.document.controls.ventry.value = viewentry;\r\n	window.document.controls.calfrid.value = frid;\r\n	window.document.controls.calfid.value = fid;\r\n	window.document.controls.submit();\r\n}\r\n\r\n</script>'),
(113,'<script type=\"text/javascript\" src=\"<{$xoops_url}>/modules/formulize/libraries/jquery/jquery-1.4.2.min.js\"></script>\r\n<script type=\"text/javascript\" src=\"<{$xoops_url}>/modules/formulize/libraries/jquery/jquery-ui-1.8.2.custom.min.js\"></script>\r\n<script type=\"text/javascript\" src=\"<{$xoops_url}>/modules/formulize/libraries/codemirror/codemirror-compressed.js\"></script>\r\n<script type=\"text/javascript\" src=\"<{$xoops_url}>/modules/formulize/libraries/formulize-admin.js\"></script>\r\n<script type=\"text/javascript\" src=\"<{$xoops_url}>/modules/formulize/selenium_debug.js\"></script>\r\n\r\n<link rel=\"stylesheet\" type=\"text/css\" href=\"<{$xoops_url}>/modules/formulize/libraries/jquery/css/start/jquery-ui-1.8.2.custom.css\">\r\n<link rel=\"stylesheet\" type=\"text/css\" href=\"<{$xoops_url}>/modules/formulize/templates/css/formulize-admin.css\">\r\n\r\n<script type=\"text/javascript\">\r\n var pagehasaccordion = new Array();\r\n</script>\r\n\r\n<!-- jquery for tooltips -->\r\n<script type=\"text/javascript\">\r\n$(document).ready(function() {\r\n	//Select all anchor tag with rel set to tooltip\r\n	$(\'a[rel=tooltip]\').mouseover(function(e) {\r\n		//Grab the title attribute\'s value and assign it to a variable\r\n		var tip = $(this).attr(\'title\');		\r\n		//Remove the title attribute\'s to avoid the native tooltip from the browser\r\n		$(this).attr(\'title\',\'\');\r\n		//Append the tooltip template and its value\r\n		$(\'body\').append(\'<div id=\"tooltip\"><div class=\"tipHeader\"></div><div class=\"tipBody\">\' + tip + \'</div><div class=\"tipFooter\"></div></div>\');\r\n	}).mousemove(function(e) {\r\n		//Keep changing the X and Y axis for the tooltip, thus, the tooltip move along with the mouse\r\n		//$(\'#tooltip\').children(\'.tipBody\').html(\'width:\' + $(window).width() + \', height:\' + $(window).height() + \':: top:\' + ( e.pageY + 10 ) + \', left:\' + ( e.pageX + 20 ) + \', width: \' + $(\'#tooltip\').width() + \', height: \' + $(\'#tooltip\').height() + \':: top:\' + e.clientY + \', left:\' + e.clientX );\r\n    if( e.clientY + 10 + $(\'#tooltip\').height() > $(window).height() ) {\r\n  		$(\'#tooltip\').css(\'top\', e.pageY - 10 - $(\'#tooltip\').height() );\r\n    } else {\r\n  		$(\'#tooltip\').css(\'top\', e.pageY + 10 );\r\n    }\r\n    if( e.clientX + 20 + $(\'#tooltip\').width() > $(window).width() ) {\r\n  		$(\'#tooltip\').css(\'left\', e.pageX - 20 - $(\'#tooltip\').width() );\r\n    } else {\r\n  		$(\'#tooltip\').css(\'left\', e.pageX + 20 );\r\n    }\r\n	}).mouseout(function() {\r\n		//Put back the title attribute\'s value\r\n		$(this).attr(\'title\',$(\'.tipBody\').html());\r\n		//Remove the appended tooltip template\r\n		$(\'body\').children(\'div#tooltip\').remove();\r\n	});\r\n});\r\n</script>\r\n\r\n<!-- jquery for floating admin bar, added by S.Gray, April 11, 2011 -->\r\n<script type=\"text/javascript\">\r\n$(document).ready(function() {\r\n	var offset = $(\'#admin_toolbar\').offset();\r\n	\r\n	  $(window).scroll(function () {\r\n		var scrollTop = $(window).scrollTop();\r\n		if (offset && offset.top<scrollTop <{$allowFloatingSave}>) {\r\n		  $(\'#admin_toolbar\').addClass(\'toolbar_fixed\');\r\n		  $(\'#admin_toolbar\').addClass(\'ui-corner-all\');\r\n		} else {\r\n		  $(\'#admin_toolbar\').removeClass(\'toolbar_fixed\');\r\n		  $(\'#admin_toolbar\').removeClass(\'ui-corner-all\');\r\n		};\r\n	  });\r\n	  \r\n	});\r\n</script>\r\n<!-- end jquery -->\r\n\r\n<!--[if IE 6]> \r\n	<link rel=\"stylesheet\" type=\"text/css\" href=\"<{$xoops_url}>/modules/formulize/templates/css/ie6.css\" />\r\n<![endif]-->\r\n\r\n<div class=\"admin-ui\">\r\n\r\n<{if $opResults}>\r\n<div id=\"formulize-patch-panel\"><{$opResults}><p><a href=\'<{$xoops_url}>/modules/formulize/admin/\'><{$smarty.const._CLOSE}></a></p></div>\r\n<{/if}>\r\n<div id=\"formulize-logo\"><img src=\"<{$xoops_url}><{$adminPage.logo}>\" align=\"<{$adminPage.pagetitle}>\" title=\"<{$adminPage.pagetitle}>\" /></div>\r\n\r\n<form name=\"scrollposition\" method=\"post\" action=\"\">\r\n  <input type=\"hidden\" name=\"scrollx\" value=\"\">\r\n  <input type=\"hidden\" name=\"tabs_selected\" value=\"\">\r\n  <input type=\"hidden\" name=\"accordion_active\" value=\"\">\r\n  <input type=\"hidden\" name=\"themeswitch\" value=\"\">\r\n  <input type=\"hidden\" name=\"seedtemplates\" value=\"\">\r\n</form>\r\n\r\n<{if $adminPage.show_user_view}>\r\n<{* link to the front-end view of this screen *}>\r\n<div id=\"fz-admin-toolbar\">\r\n    <a class=\"show-user-view\" href=\"<{$adminPage.show_user_view.1}>\"><{$adminPage.show_user_view.0}></a>\r\n</div>\r\n<{/if}>\r\n\r\n<{if $adminPage.pagetitle}>\r\n  <h1><{$adminPage.pagetitle}> <span class=\"smallhead\"><{$adminPage.pagesubtitle}></span></h1>\r\n  <{if $lasturl}>\r\n    <p><a href=\"ui.php?<{$lasturl}>\"><{$smarty.const._AM_HOME_GOBACKTO}><i><{$lasttext}></i></a></p>\r\n  <{/if}>\r\n<{/if}>\r\n\r\n<{if $breadcrumbtrail}>\r\n<p id=\"admin-breadcrumbs\">\r\n    <{counter start=0 assign=crumbcount print=false}>\r\n    <{foreach from=$breadcrumbtrail item=crumb}>\r\n        <{if $crumbcount}> &raquo; <{/if}>\r\n        <{if $crumb.url}>\r\n            <a href=\"ui.php?<{$crumb.url}>\" class=\"breadcrumb-<{$crumbcount}>\"><{$crumb.text}></a>\r\n            <{assign var=\'lasturl\' value=$crumb.url}>\r\n            <{assign var=\'lasttext\' value=$crumb.text}>\r\n        <{else}>\r\n            <{$crumb.text}>\r\n        <{/if}>\r\n    <{counter}>\r\n    <{/foreach}>\r\n</p>\r\n<{/if}>\r\n    \r\n<!-- modified by Freeform Solutions, S.Gray, April 11, 2011 -->\r\n<{if $adminPage.needsave}>\r\n<div id=\"admin_toolbar\">\r\n<div id=savebutton>\r\n<{if $adminPage.isSaveLocked}>\r\n	READ ONLY\r\n<{else}>\r\n    <input type=\"button\" class=\"savebutton\" id=\"save\" value=\"<{$smarty.const._AM_HOME_SAVECHANGES}>\"/>    \r\n<{/if}>\r\n</div>\r\n<div id=\"savewarning\" class=\"ui-corner-all\"><{$smarty.const._AM_HOME_WARNING_UNSAVED}></div>\r\n<div id=\"derivedfinished\" class=\"ui-corner-all\" style=\"display:none\"><{$smarty.const._AM_ELE_DERIVED_DONE}></div>\r\n</div><!-- /admin_toolbar -->\r\n<{/if}>\r\n\r\n<{if $adminPage.template}>\r\n<{include file=$adminPage.template}>  \r\n<{/if}>\r\n\r\n<{if $adminPage.tabs}>  \r\n<{include file=\"db:admin/ui-tabs.html\" tabs=$adminPage.tabs}>\r\n<{/if}>\r\n \r\n<p class=\"versionnumber\">Version <{$version}></p>\r\n\r\n</div><!-- End admin-ui -->\r\n\r\n<script type=\"text/javascript\">\r\n\r\n  var saveCounter = 0;\r\n  var saveTarget = 0;\r\n  var redirect = \"\";\r\n  var newhandle = \"\";\r\n  var formdata = new Array();\r\n  \r\n  $(\"input\").change(function() {\r\n    setDisplay(\'savewarning\',\'block\');\r\n    });\r\n  $(\"input[type=text]\").keydown(function() {\r\n    setDisplay(\'savewarning\',\'block\');\r\n    });\r\n  $(\"select\").change(function() {\r\n        if ($(this).attr(\'name\') != \'screens-theme\') { // switching themes is a special event, and can only be done when there\'s been no changes to the settings yet\r\n            setDisplay(\'savewarning\',\'block\');\r\n        }\r\n    });\r\n  $(\"textarea\").keydown(function() {\r\n    setDisplay(\'savewarning\',\'block\');\r\n    });\r\n\r\n  $(\".savebutton\").click(function() {\r\n    if(validateRequired()) {\r\n      runSaveEvent();\r\n    }\r\n  });\r\n\r\n  function runSaveEvent() {\r\n    $(\".admin-ui\").fadeTo(1,0.5);\r\n    var formulize_formlist = $(\".formulize-admin-form\");\r\n    saveCounter = 0;\r\n    saveTarget = 0;\r\n    redirect = \"\";\r\n    formdata = new Array();\r\n    for(i=0;i<formulize_formlist.length;i++) {\r\n      if(typeof(formulize_formlist[i]) == \'object\') { // for some crazy reason, non-form stuff can be pulled in by getElementsByName with that param...I hate javascript\r\n        formdata[saveTarget] = formulize_formlist[i];\r\n        saveTarget = saveTarget + 1;\r\n      }\r\n    }\r\n    if(saveTarget > 0) {\r\n      sendFormData(formdata[0]); // send the first form\'s data \r\n    }\r\n  }\r\n  \r\n  function sendFormData(thisformdata, ele_id) {\r\n    if(!ele_id) { ele_id = 0 }\r\n    $.post(\"save.php?ele_id=\"+ele_id, $(thisformdata).serialize(), function(data) {\r\n      saveCounter = saveCounter + 1;\r\n      if(data) {\r\n        if(data.substr(0,10)==\"/* eval */\") {\r\n          redirect = data;\r\n        } else if(data.substr(0,13)==\"/* evalnow */\") {\r\n          eval(data);\r\n        } else {\r\n          alert(data);\r\n        }\r\n      }\r\n      if(saveCounter >= saveTarget) { // if we\'ve received a response for all the forms...\r\n        setDisplay(\'savewarning\',\'none\');\r\n        $(\".savebutton\").blur();\r\n        if(newhandle) {\r\n          $(\"[name=original_handle]\").val(newhandle);\r\n        }\r\n        if(redirect) {\r\n          eval(redirect);\r\n        } else {\r\n          $(\".admin-ui\").fadeTo(1,1);\r\n        }\r\n      } else { // if there\'s still forms to do, then send the next one...must do sequentially to avoid race conditions\r\n        sendFormData(formdata[saveCounter], ele_id);\r\n      }\r\n    });\r\n  }\r\n  \r\n  function reloadWithScrollPosition(url) {\r\n    if(url) {\r\n      $(\"[name=scrollposition]\").attr(\'action\', url);\r\n    }\r\n    window.document.scrollposition.scrollx.value = $(window).scrollTop();\r\n    var tabs_selected = \"\";\r\n    <{if $adminPage.tabs}> \r\n    tabs_selected = $(\"#tabs\").tabs(\"option\",\"selected\");\r\n    window.document.scrollposition.tabs_selected.value = tabs_selected;\r\n    tabs_selected = tabs_selected+1;\r\n    <{/if}>\r\n    var accordion_active = \"\";\r\n    if(pagehasaccordion[\"accordion-\"+tabs_selected]) {\r\n      <{* // not really the active accordion we want, it\'s the current position of the active accordion, since accordion sections are sortable! - this is a semi-rare bug that needs fixing, cross reference the active position with the results of a toArray call on the sortable element *}>\r\n      accordion_active = $(\"#accordion-\"+tabs_selected).accordion( \"option\", \"active\" );\r\n    }\r\n    window.document.scrollposition.accordion_active.value = accordion_active;\r\n    window.document.scrollposition.submit();\r\n  }\r\n\r\n  function validateRequired() {\r\n    var requiredok = true;\r\n    $(\".required_formulize_element\").each(function () {\r\n      if(($(this).val().length) == 0) {\r\n        requiredok = false;\r\n      }\r\n    });\r\n    return requiredok;\r\n  }\r\n\r\n  $().ajaxError(function () {\r\n    alert(\"There was an error when saving your data.  Please try again.\");\r\n  });\r\n  \r\n  $(window).load(function () {\r\n    $(window).scrollTop(<{$scrollx}>);\r\n  });\r\n\r\n  function setDisplay( elementId, styleDisplay ) {\r\n    var element = window.document.getElementById( elementId );\r\n    if( element ) {\r\n      element.style.display = styleDisplay;\r\n    }\r\n    if (elementId == \'savewarning\') {\r\n        if (styleDisplay == \'block\') {\r\n            // disable theme switching\r\n            $(\"[name=\'screens-theme\']\").attr(\'disabled\', true);\r\n        } else {\r\n            // enable theme switching\r\n            $(\"[name=\'screens-theme\']\").attr(\'disabled\', false);\r\n        }\r\n    }\r\n  }\r\n\r\n  $(document).ready(\r\n    $(\'.code-textarea\').each(function() {\r\n        if (this.type !== \'textarea\' || SELENIUM_DEBUG == \'ON\') {\r\n            return true; // continue\r\n        }\r\n        CodeMirror.fromTextArea(this, {\r\n            lineNumbers: true,\r\n            matchBrackets: true,\r\n            mode: \"application/x-httpd-php\",\r\n            indentUnit: 4,\r\n            indentWithTabs: true,\r\n            enterMode: \"keep\",\r\n            tabMode: \"shift\",\r\n            lineWrapping: true,\r\n            onChange: function(instance) { \r\n                setDisplay(\'savewarning\',\'block\');\r\n                instance.save(); // Call this to update the textarea value for the ajax post\r\n            }\r\n        });\r\n    })\r\n  );\r\n  \r\n    // change the themes, only possible when no changes to screen settings yet. Change theme property of screen and reload page.\r\n    $(\"[name=\'screens-theme\']\").change(function() {\r\n        window.document.scrollposition.themeswitch.value  = $(this).val();\r\n        reloadWithScrollPosition();\r\n    });\r\n    \r\n    // seed the templates, only possible when no custom templates yet exist for a screen.\r\n    $(\"#seedtemplates\").click(function() {\r\n        window.document.scrollposition.seedtemplates.value = 1;\r\n        reloadWithScrollPosition();\r\n    });\r\n  \r\n</script>'),
(114,'<script type=\"text/javascript\">\r\n	\r\n	$(function() {\r\n		$(\"#tabs\").tabs({\r\n			<{if $adminPage.tabselected > 0}>\r\n			selected: <{$adminPage.tabselected}>\r\n			<{/if}>\r\n		});\r\n	});\r\n	\r\n</script>\r\n\r\n<div id=\"tabs\">\r\n	<ul>\r\n		<{foreach from=$tabs key=number item=tab}>\r\n		<li><a href=\"#tabs-<{$number}>\"><{$tab.name}></a></li>\r\n		<{/foreach}>\r\n	</ul>\r\n	<{foreach from=$tabs key=number item=tab}>\r\n	<div id=\"tabs-<{$number}>\" class=\"tab-content\">\r\n		<{include file=$tab.template content=$tab.content}>\r\n	</div>\r\n	<{/foreach}>\r\n</div><!-- end tabs -->'),
(115,'<script type=\"text/javascript\">\r\n\r\n	pagehasaccordion[\"accordion-<{$number}>\"] = true;\r\n\r\n	$(function() {\r\n		var stop = false;\r\n		$(\"#accordion-<{$number}> h3\").click(function(event) {\r\n			if (stop) {\r\n				event.stopImmediatePropagation();\r\n				event.preventDefault();\r\n				stop = false;\r\n			}\r\n		});\r\n		$(\"#accordion-<{$number}>\").accordion({\r\n			autoHeight: false, // no fixed height for sections\r\n			collapsible: true, // sections can be collapsed \r\n			active: <{$accordion_active}>,\r\n			header: \"> div > h3\"\r\n		}).sortable({\r\n			axis: \"y\",\r\n			handle: \"h3\",\r\n			stop: function(event, ui) {\r\n				stop = true;\r\n			}\r\n		});\r\n		$(\"#accordion-<{$number}>\").accordion(\"activate\" , 0);\r\n	});\r\n\r\n</script>\r\n\r\n<div id=\"accordion-<{$number}>\">\r\n	<{foreach from=$sections key=sectionNumber item=section}>\r\n	<div id=\"drawer-<{$number}>-<{$sectionNumber}>\">\r\n        <h3><a href=\"#\"><span class=\"accordion-name\"><{$section.name}></span></a></h3>\r\n		<div class=\"accordion-content content\">\r\n			<{if $section.header}>\r\n			<div style=\"position: absolute; top: 10px; right: 10px;\">\r\n				<{$section.header}>\r\n			</div>\r\n			<{/if}>\r\n			<{include file=$sectionTemplate sectionContent=$section.content}>\r\n		</div>\r\n	</div>\r\n	<{/foreach}>\r\n</div><!-- end accordion -->'),
(116,'<div class=\"panel-content content\">\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<{php}>print $GLOBALS[\'xoopsSecurity\']->getTokenHTML()<{/php}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"application_settings\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.aid}>\">\r\n<input type=\"hidden\" id=\"reload_settings\" name=\"reload_settings\" value=\"\">\r\n\r\n	<div class=\"accordion-box\">\r\n		<div class=\"form-item\">\r\n        	<fieldset>\r\n                <legend><label for=\"applications-name\" class=\"question\"><{$smarty.const._AM_APP_NAMEQUESTION}></label></legend>\r\n                <input type=\"text\" id=\"applications-name\" name=\"applications-name\" value=\"<{$content.name}>\" class=\"input-text\" />\r\n            </fieldset>\r\n		</div>\r\n\r\n		<div class=\"form-item\">\r\n        	<fieldset>\r\n                <legend><label for=\"applications-description\" class=\"question\"><{$smarty.const._AM_APP_DESCQUESTION}></label></legend>\r\n                <textarea rows=\"4\" id=\"applications-description\" name=\"applications-description\"><{$content.description}></textarea>\r\n            </fieldset>\r\n		</div>\r\n	\r\n	</div>\r\n	\r\n	<div class=\"accordion-box\">\r\n		<div class=\"form-item\">\r\n			<fieldset>\r\n				<legend><label for=\"applications-forms\" class=\"question\"><{$smarty.const._AM_APP_FORMSIN}></label></legend>\r\n				<select id=\"applications-forms\" name=\"applications-forms[]\" size=6 multiple>\r\n					<{foreach from=$content.forms item=thisform}>\r\n						<option value=<{$thisform.id}><{$thisform.selected}>><{$thisform.name}></option>\r\n					<{/foreach}>\r\n				</select>\r\n			</fieldset>\r\n		</div>\r\n	</div>\r\n	\r\n\r\n</form>\r\n\r\n<div style=\"clear: both\"></div>\r\n</div> <!--// end content -->\r\n\r\n<script type=\"text/javascript\">\r\n\r\n	$(\"#applications-name\").keydown(function () {\r\n		window.document.getElementById(\'reload_settings\').value = 1;\r\n	});\r\n	\r\n	$(\"#applications-forms\").change(function () {\r\n		window.document.getElementById(\'reload_settings\').value = 1;\r\n	});\r\n	\r\n</script>'),
(117,'<{if $content.aid == 0}>\r\n<div class=\"description\"><{$smarty.const._AM_FORM_CREATE_EXPLAIN}></div><br>\r\n<{/if}>\r\n\r\n<{php}>print $GLOBALS[\'xoopsSecurity\']->getTokenHTML()<{/php}>\r\n<form class=\"formulize-admin-form\">\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"application_forms\">\r\n<input type=\"hidden\" name=\"aid\" value=\"<{$content.aid}>\">\r\n<input type=\"hidden\" name=\"deleteform\" value=\"\">\r\n<input type=\"hidden\" name=\"cloneform\" value=\"\">\r\n<input type=\"hidden\" name=\"cloneformdata\" value=\"\">\r\n<input type=\"hidden\" name=\"lockdown\" value=\"\">\r\n<div id=\"formlisting\">\r\n<p><a href=\"ui.php?page=form&tab=settings&fid=new&aid=<{$content.aid}>\"><img src=\"../images/filenew2.png\"><{$smarty.const._AM_FORM_CREATE}></a></p>\r\n<blockquote>\r\n	<{include file=\"db:admin/form_listing.html\" sectionContent=$content}>\r\n</blockquote>\r\n</div>\r\n</form>\r\n\r\n<script type=\"text/javascript\">\r\n\r\n$(\".deleteformlink\").click(function () {\r\n	answer = confirm(\"<{$smarty.const._AM_HOME_CONFIRMDELETEFORM}>\");\r\n	if(answer) {\r\n		$(\"[name=deleteform]\").val($(this).attr(\'target\'));\r\n		runSaveEvent();\r\n	}\r\n	return false;\r\n});\r\n\r\n$(\".cloneform\").click(function () {\r\n	$(\"[name=cloneform]\").val($(this).attr(\'target\'));\r\n	runSaveEvent();\r\n	return false;\r\n});\r\n\r\n$(\".cloneformdata\").click(function () {\r\n	$(\"[name=cloneformdata]\").val($(this).attr(\'target\'));\r\n	runSaveEvent();\r\n	return false;\r\n});\r\n\r\n$(\".lockdown\").click(function () {\r\n	answer = confirm(\"<{$smarty.const._AM_CONFIRM_LOCK}>\");\r\n	if(answer) {\r\n		$(\"[name=lockdown]\").val($(this).attr(\'target\'));\r\n		runSaveEvent();\r\n	}\r\n	return false;\r\n});\r\n\r\n</script>'),
(118,'<div class=\"panel-content content\">\r\n	<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n        <{php}>print $GLOBALS[\'xoopsSecurity\']->getTokenHTML()<{/php}>\r\n        <input type=\"hidden\" name=\"formulize_admin_handler\" value=\"application_menu_entries\">\r\n        <input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.aid}>\">\r\n        <input type=\"hidden\" id=\"reload_settings\" name=\"reload_settings\" value=1>\r\n        <input type=\"hidden\" id=\"deletemenuitem\" name=\"deletemenuitem\" value=\"\">\r\n        <input type=\"hidden\" name=\"menuorder\" value=\"\">\r\n	<input type=\"hidden\" name=\"tabnumber\" value=<{$number}>>\r\n		\r\n                            \r\n                            \r\n        <div class=\"accordion-box\">\r\n            <div class=\"form-item\">\r\n            \r\n                <h2> Add links to the menu</h2> \r\n                \r\n                <fieldset>		\r\n                    <div>\r\n			<h3>Text for this link:</h3>\r\n			<input type=\"text\" id=\"addmenutext\" name=\"addmenutext\">\r\n		    </div>\r\n		    <div id=\"listofscreenoptions\">\r\n			<h3>This link goes to:</h3>\r\n			<{html_options name=\'addnewscreenoptions\' options=$content.listsofscreenoptions}>\r\n		    </div>\r\n		    <div>\r\n			<h3>A note for this link:</h3>\r\n			<input type=\"text\" name=\"addnote\" id=\"addnote\">\r\n		    </div>\r\n		    <div>\r\n                    <input type=\"text\" name=\"addurl\" id=\"addurl\" value=\"http://\">\r\n		    </div>\r\n		    <div>\r\n                    <h3>Show this link to these groups:</h3>\r\n                    <select name=\"addgroups\" id=\"addgroups\" size=10 multiple style=\"overflow-y: scroll;\">\r\n                        <{foreach from=$content.groups item=group}>\r\n                            <option value=<{$group.id}><{$group.selected}>><{$group.name}></option>                 \r\n                        <{/foreach}>\r\n                    </select>\r\n		    </div>\r\n                    <div id=\"defaultScreenSection\"> \r\n			<h3>Send these groups to this link right after they login:</h3>                        \r\n			<select name=\"defaultScreenGroups\" id=\"addDefaultScreenGroups\" size=10 multiple style=\"overflow-y: scroll;\">\r\n			    <{foreach from=$content.groups item=group}>\r\n				<option value=<{$group.id}><{$group.selected}>><{$group.name}></option>                 \r\n			    <{/foreach}>\r\n			</select>\r\n                    </div>\r\n			<div class=\"description\">\r\n			<p>This only takes effect if Formulize is also set as the default start page for a group, under <a href=\'<{$xoops_url}>/modules/system/admin.php?fct=preferences&op=show&confcat_id=1\' target=\'_blank\'>General Settings</a></p>\r\n			</div>\r\n                   \r\n                </fieldset>\r\n                \r\n                <button class=\"menuButton\" id=\"addMenuItem\" type=\"button\" >Add Menu Item</button>				\r\n                \r\n            </div>		\r\n        </div>\r\n                \r\n        <div class=\"accordion-box\">			\r\n            <h2> Manage the links in the menu</h2>\r\n            <p>Click and drag the links to re-order them</p><br/>\r\n            <div id=\"sortable-list\">        \r\n                <{include file=\"db:admin/ui-accordion.html\" sectionTemplate=\"db:admin/application_menu_entries_sections.html\" sections=$content.links}>\r\n            </div>                                                    \r\n        </div>\r\n    </form>\r\n    \r\n    <div style=\"clear: both\"></div>\r\n    \r\n</div> <!--// end content -->\r\n<script type=\"text/javascript\">\r\n    var formID = \"\";\r\n    var formRelationID = \"1\";\r\n    \r\n    // added Oct 2013 \r\n    $(\".deletemenulink\").click(function () {\r\n        var answer = confirm(\"Do you want to delete the menu link \'\"+$(this).attr(\'menuname\')+\"\'?\");\r\n        if(answer) {\r\n            $(\"[name=deletemenuitem]\").val($(this).attr(\'target\'));\r\n            $(\".savebutton\").click();\r\n        }\r\n        return false;\r\n    });\r\n    \r\n	jQuery(\"#addurl\").hide();\r\n        jQuery(\"#listofscreenoptions select\").change(function(){\r\n            if(jQuery(this).val() == \'url\') {\r\n                jQuery(\"#addurl\").fadeIn();\r\n            } else {\r\n                jQuery(\"#addurl\").fadeOut();\r\n            }\r\n        });\r\n    \r\n    // modified Oct 2013\r\n    $(\"#addMenuItem\").click(function (){\r\n        $(\".savebutton\").click();\r\n    });\r\n  \r\n    $(\".savebutton\").click(function () {\r\n        $(\"[name=menuorder]\").val($(\"#accordion-<{$number}>\").sortable(\'serialize\'));\r\n	\r\n\r\n	var menuText = $(\"#addmenutext\").val().trim();\r\n	if(menuText) {\r\n		var url = $(\"#addurl\").val();\r\n	        var screen = $(\"#listofscreenoptions select\").val();\r\n	        var groupids = $(\"#addgroups\").val();\r\n	        var default_screen = $(\"#addDefaultScreenGroups\").val();\r\n		var note=$(\"#addnote\").val()\r\n	        var value = \"null\";\r\n		value += \"::\"+menuText+ \"::\" + screen + \"::\" + url + \"::\"+groupids+\"::\"+default_screen+\"::\"+note;\r\n	        addHiddenMenuItem(value);\r\n	}\r\n	\r\n	$(\".menuEntriesSection\").each(function() {\r\n		var sectionNumber = $(this).attr(\'menuentry\');\r\n		var menuID = $(this).attr(\'menuid\');\r\n		var menuText = $(\"#menutext\"+sectionNumber).val().trim().replace(/\\\'/g, \" \") ;\r\n		var url = $(\"#url\"+sectionNumber).val();\r\n		var screen = $(\"#sectionScreen\"+sectionNumber+\" select\").val();\r\n		var groupids = $(\"#groups\"+sectionNumber).val();\r\n		var default_screen= $(\"#defaultScreenGroups\"+sectionNumber).val();\r\n		var note=$(\"#addnote\"+sectionNumber).val()\r\n		var value = menuID;\r\n		\r\n		value += \"::\"+menuText+ \"::\" + screen + \"::\" + url + \"::\"+groupids+\"::\"+default_screen+\"::\"+note;                                                                               \r\n		addHiddenMenuItem(value);\r\n	});\r\n    });\r\n \r\n    $(\"#accordion-<{$number}>\").bind( \"sortupdate\", function(event, ui) {\r\n        setDisplay(\'savewarning\',\'block\');\r\n    });\r\n                            \r\n    function addHiddenMenuItem(value) {\r\n	$(\'<input>\').attr({\r\n		type: \'hidden\',\r\n		name: \'menu_items[]\',\r\n		value: value\r\n	}).appendTo(\'#form-<{$number}>\');\r\n    }\r\n    \r\n    \r\n    \r\n    </script>'),
(119,'<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n    <{$securitytoken}>\r\n    <input type=\"hidden\" name=\"formulize_admin_handler\" value=\"application_code\">\r\n    <input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.aid}>\">\r\n\r\n    <div class=\"panel-content content\">\r\n\r\n        <div class=\"form-item\">\r\n            <fieldset>\r\n                <legend>Code Template</legend>\r\n                <textarea id=\"applications-custom_code\"  name=\"applications-custom_code\" class=\"code-textarea\" rows=\"20\"/>\r\n<{$content.custom_code}></textarea>\r\n                <div class=\"description\">\r\n                    <p>PHP code you enter here is included in every Formulize page.</p>\r\n                </div>\r\n            </fieldset>\r\n        </div>\r\n\r\n    </div>\r\n</form>\r\n\r\n<script>\r\njQuery(document).ready(function() {\r\n    jQuery(\".savebutton\").click(function() {\r\n        fz_check_php_code(jQuery(\"#applications-custom_code\").val(), \"Code template\", \"<{$icms_url}>\", <{$icms_userid}>);\r\n    });\r\n});\r\n</script>'),
(120,'<div class=\"panel-content content menuEntriesSection\" menuentry=\'<{$sectionNumber}>\' menuid=\'<{$content.links[$sectionNumber].menu_id}>\'>\r\n    \r\n    <p><a href=\"\" class=\"deletemenulink\" target=\"<{$content.links[$sectionNumber].menu_id}>\" menuname=\"<{$content.links[$sectionNumber].link_text}>\"><img src=\"../images/editdelete.gif\"> Delete</a></p>\r\n\r\n    <form>\r\n        <div>\r\n        <h3>Text for this link:</h3>\r\n        <input type=\"text\" id=\"menutext<{$sectionNumber}>\" name=\"menutext\" value=\"<{$content.links[$sectionNumber].link_text}>\">\r\n        </div>\r\n        <div>\r\n	    \r\n        <h3>Note for this link:</h3>\r\n        <input type=\"text\" id=\"addnote<{$sectionNumber}>\" name=\"addnote\" value=\"<{$content.links[$sectionNumber].note}>\">\r\n        </div>\r\n	\r\n        <div id=\"sectionScreen<{$sectionNumber}>\" class=\"sectionScreen\">\r\n            <h3>This link goes to:</h3>\r\n            <{html_options name=\'sectionScreenOptions\'|cat:$sectionNumber options=$content.listsofscreenoptions}>\r\n        </div>  \r\n        <div id=\"externalUrl<{$sectionNumber}>\">\r\n        <input type=\"text\" name=\"url\" id=\"url<{$sectionNumber}>\" value=\"<{$content.links[$sectionNumber].url}>\">\r\n        </div>\r\n        <div>    \r\n        <h3>Show this link to these groups:</h3>                        \r\n        <select name=\"groups\" id=\"groups<{$sectionNumber}>\" size=10 multiple style=\"overflow-y: scroll;\">\r\n            <{foreach from=$content.groups item=group}>\r\n                    <option value=\"<{$group.id}>\"> <{$group.name}> </option>\r\n            <{/foreach}>\r\n        </select>\r\n        </div>\r\n        <div id=\"defaultScreenSection<{$sectionNumber}>\"> \r\n            <h3>Send these groups to this link right after they login:</h3>                        \r\n            <select name=\"defaultScreenGroups\" id=\"defaultScreenGroups<{$sectionNumber}>\" size=10 multiple style=\"overflow-y: scroll;\">\r\n            <{foreach from=$content.groups item=group}>\r\n                    <option value=\"<{$group.id}>\"> <{$group.name}> </option>\r\n            <{/foreach}>\r\n            </select>\r\n       </div>\r\n       <div class=\"description\">\r\n        <p>This only takes effect if Formulize is also set as the default start page for a group, under <a href=\'<{$xoops_url}>/modules/system/admin.php?fct=preferences&op=show&confcat_id=1\' target=\'_blank\'>General Settings</a></p>\r\n	</div>\r\n            \r\n    </form> \r\n</div>\r\n\r\n\r\n<script type=\"text/javascript\">\r\n    \r\n    jQuery( document ).ready(function() {\r\n                             \r\n        //show screen for each link (if there is any)		\r\n        jQuery(\"#sectionScreen<{$sectionNumber}> select\").val(\"<{$content.links[$sectionNumber].screen}>\"); \r\n\r\n        if(jQuery(\"#sectionScreen<{$sectionNumber}> select\").val() != \'url\') {\r\n            jQuery(\"#externalUrl<{$sectionNumber}>\").hide();\r\n        }\r\n        jQuery(\"#sectionScreen<{$sectionNumber}> select\").change(function(){\r\n            if(jQuery(this).val() == \'url\') {\r\n                jQuery(\"#externalUrl<{$sectionNumber}>\").fadeIn();\r\n            } else {\r\n                jQuery(\"#externalUrl<{$sectionNumber}>\").fadeOut();\r\n            }\r\n        });\r\n                             \r\n        //show group permissions for each link (if there are any)\r\n        var permissions= \"\"+\'<{$content.links[$sectionNumber].permissions}>\';		\r\n        if ( permissions != \'\'){				\r\n            permissions= permissions.split(\",\");\r\n            for (var i=0; i<permissions.length; i++){				\r\n                jQuery(\"#groups<{$sectionNumber}> option\").filter( function(){\r\n                    return jQuery(this).val() == permissions[i];	   			\r\n                }).attr(\"selected\", true);\r\n            }\r\n        }\r\n        var default_screen= \"\"+\'<{$content.links[$sectionNumber].default_screen}>\';		\r\n        if ( default_screen != \'\'){				\r\n            default_screen= default_screen.split(\",\");\r\n            for (var i=0; i<default_screen.length; i++){				\r\n                jQuery(\"#defaultScreenGroups<{$sectionNumber}> option\").filter( function(){\r\n                    return jQuery(this).val() == default_screen[i];	   			\r\n                }).attr(\"selected\", true);\r\n            }\r\n        }\r\n\r\n    });\r\n    \r\n    \r\n     \r\n\r\n</script>'),
(121,'<div class=\"form-item\">\r\n	<table>\r\n		<tr>\r\n			<th><a href=\"?page=application&aid=<{$content.aid}>&tab=screens&sort=sid<{if $content.screenSort == \'sid\'}>&order=<{$content.nextOrder}><{/if}>\">ID</a></th>\r\n			<th><a href=\"?page=application&aid=<{$content.aid}>&tab=screens&sort=title<{if $content.screenSort == \'title\'}>&order=<{$content.nextOrder}><{/if}>\">Title</a></th>\r\n			<th></th>\r\n			<th><a href=\"?page=application&aid=<{$content.aid}>&tab=screens&sort=fid<{if $content.screenSort == \'fid\'}>&order=<{$content.nextOrder}><{/if}>\">Form</a></th>\r\n			<th><a href=\"?page=application&aid=<{$content.aid}>&tab=screens&sort=type<{if $content.screenSort == \'type\'}>&order=<{$content.nextOrder}><{/if}>\">Type</a></th>\r\n		</tr>\r\n	<{foreach from=$content.screens item=screen}>\r\n		<tr>\r\n			<td>Screen <{$screen.sid}></td>\r\n			<td><a class=\"configscreen\" target-sid=\"<{$screen.sid}>\" href=\"?page=screen&aid=<{$content.aid}>&fid=<{$screen.fid}>&sid=<{$screen.sid}>\"><{$screen.title}></a></td>\r\n			<td><a href=\"<{$xoops_url}>/modules/formulize/index.php?sid=<{$screen.sid}>\" target=\"_blank\">View</a></td>\r\n			<td><a href=\"?page=form&fid=<{$screen.fid}>\"><{$screen.formname}> (<{$screen.fid}>)</a></td>\r\n			<td><{$screen.type}></td>\r\n		</tr>\r\n	<{/foreach}>\r\n	</table>\r\n</div>\r\n<div class=\"form-item paging\">\r\n	<div class=\"floatright page-list\">\r\n	   <{$content.pageNav}>\r\n	</div>\r\n</div>'),
(122,'<{foreach from=$sectionContent.forms item=form}>\r\n  \r\n<{if $form.istableform}>\r\n<{assign var=\"defaulttab\" value=\"settings\"}>\r\n<{else}>\r\n<{assign var=\"defaulttab\" value=\"elements\"}>    \r\n<{/if}>\r\n  \r\n<div class=\"accordion-box\">\r\n  <p class=\"form-name ui-corner-top\"><{if $form.lockedform != 1}><a href=\"ui.php?page=form&aid=<{$sectionContent.aid}>&fid=<{$form.fid}>&tab=<{$defaulttab}>\" class=\"form-name-icon\"><{/if}>Form: <{$form.name}> (id: <{$form.fid}>)<{if $form.lockedform != 1}></a><{/if}></p>\r\n  <div class=\"form-screen-list ui-corner-bottom\">\r\n		<p class=\"formbox-title\">Actions:</p>\r\n		<div class=\"formactions-list\">\r\n		<{if $form.lockedform != 1}>\r\n		<a rel=\"tooltip\" title=\"Change the form\'s behaviour, add elements, set permissions, create new screens, etc.\" href=\"ui.php?page=form&aid=<{$sectionContent.aid}>&fid=<{$form.fid}>&tab=<{$defaulttab}>\"><div class=\"imageicon\"><img src=\"../images/kedit.png\"></div><div class=\"formaction\"><{$smarty.const._AM_APP_CONFIGURE}></div></a><br />\r\n		<{/if}>\r\n		<a rel=\"tooltip\" title=\"View the form as users will see it.<br>This will show the default list screen, and from there you can add an entry to see the form itself.\" href=\"../index.php?fid=<{$form.fid}>\" target=\"_blank\"><div class=\"imageicon\"><img src=\"../images/kfind.png\"></div><div class=\"formaction\"><{$smarty.const._AM_APP_VIEW_DEFAULT_SCREEN}></div></a><br />\r\n		<{if $form.lockedform != 1}>\r\n		<a rel=\"tooltip\" title=\"View the form with all controls available.<br>This will show the form with all the default options in place, the same way it would appear if there were no modifications to the default screens (or if it had no screens at all).<br>This is useful for interacting with the form when your default screen has been stripped down to remove features that regular users don\'t need, but administrators do need.\" href=\"../master.php?fid=<{$form.fid}>\" target=\"_blank\"><div class=\"imageicon\"><img src=\"../images/kfind.png\"></div><div class=\"formaction\"><{$smarty.const._AM_APP_VIEW_OPTIONS_SCREEN}></div></a><br />\r\n		<{/if}>\r\n		<a rel=\"tooltip\" title=\"Copy the structure of an existing form into a new form with a different name.<br>None of the information collected is copied.\" class=\"cloneform\" target=\"<{$form.fid}>\" href=\"\"><div class=\"imageicon\"><img src=\"../images/clone.gif\"></div><div class=\"formaction\"><{$smarty.const._AM_APP_CLONE_SIMPLY}></div></a><br />\r\n		<a rel=\"tooltip\" title=\"Copy the structure of an existing form into a new form with a different name.<br>The new form will have a copy of all the entries collected in the original form.\" class=\"cloneformdata\" target=\"<{$form.fid}>\" href=\"\"><div class=\"imageicon\"><img src=\"../images/clonedata.gif\"></div><div class=\"formaction\"><{$smarty.const._AM_APP_CLONE_WITHDATA}></div></a><br />\r\n		<{if $form.lockedform != 1 && $form.hasdelete}>\r\n		<a rel=\"tooltip\" title=\"Use this to prevent any changes to the form or its settings, even by the webmaster.<br>This action cannot be undone!\" class=\"lockdown\" target=\"<{$form.fid}>\" href=\"\"><div class=\"imageicon\"><img src=\"../images/perm.png\"></div><div class=\"formaction\"><{$smarty.const._AM_APP_LOCKDOWN}></div></a><br />\r\n		<a rel=\"tooltip\" title=\"Delete a form plus all the entries people have made in it.<br>This action cannot be undone!\" class=\"deleteformlink\" target=\"<{$form.fid}>\" href=\"\"><div class=\"imageicon\"><img src=\"../images/editdelete.gif\"></div><div class=\"formaction\"><{$smarty.const._AM_APP_DELETE_FORM}></div></a><br />\r\n		<{/if}>\r\n		<{if !$form.defaultformscreenid && !$form.defaultlistscreenid}>\r\n		<p><a href=\"ui.php?page=screen&aid=<{$sectionContent.aid}>&fid=<{$form.fid}>&sid=new\"><div class=\"imageicon\"><img src=\"../images/filenew2.png\"></div><div class=\"formaction\"><{$smarty.const._AM_APP_CREATE_NEW_SCREEN}></div></a></p>\r\n		<{/if}>\r\n		</div>\r\n\r\n		<{if $form.defaultformscreenid || $form.defaultlistscreenid}>\r\n		<p class=\"formbox-title\"><{$smarty.const._AM_APP_DEFAULTSCREENS}></p>\r\n		<div class=\"formactions-list\">\r\n		<{if $form.defaultlistscreenid}>\r\n		<a href=\"ui.php?page=screen&aid=<{$sectionContent.aid}>&fid=<{$form.fid}>&sid=<{$form.defaultlistscreenid}>\"><div class=\"imageicon\"><img src=\"../images/kedit.png\"></div><div class=\"formaction\"><{$form.defaultlistscreenname}></div></a><br />\r\n		<{/if}>\r\n		<{if $form.defaultformscreenid}>  \r\n		<a href=\"ui.php?page=screen&aid=<{$sectionContent.aid}>&fid=<{$form.fid}>&sid=<{$form.defaultformscreenid}>\"><div class=\"imageicon\"><img src=\"../images/kedit.png\"></div><div class=\"formaction\"><{$form.defaultformscreenname}></div></a><br />\r\n		<{/if}>\r\n		<a href=\"ui.php?page=form&aid=<{$sectionContent.aid}>&fid=<{$form.fid}>&tab=screens\"><div class=\"formaction\"><i><{$smarty.const._AM_APP_MORESCREENS}></i></div></a>\r\n		</div>	\r\n    <{/if}>\r\n	</div>\r\n</div>\r\n<{/foreach}>'),
(123,'<div class=\"panel-content content\">\r\n\r\n\r\n<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<{php}>print $GLOBALS[\'xoopsSecurity\']->getTokenHTML()<{/php}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"form_settings\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.fid}>\">\r\n<input type=\"hidden\" name=\"application_url_id\" value=\"<{$content.aid}>\">\r\n<input type=\"hidden\" id=\"reload_settings\" name=\"reload_settings\" value=\"<{if $content.fid == \'new\'}>1<{/if}>\">\r\n\r\n	<div class=\"accordion-box\">\r\n		<div class=\"form-item required\">\r\n        	<fieldset>\r\n                <legend><label for=\"forms-title\" class=\"question\"><{$smarty.const._AM_SETTINGS_FORM_TITLE_QUESTION}></label></legend>\r\n                <p><{$smarty.const._AM_SETTINGS_FORM_TITLE}><input type=\"text\" id=\"forms-title\" name=\"forms-title\" class=\"required_formulize_element\" value=\"<{$content.name}>\" onkeyup=\"fillHandle()\" /></p>\r\n            \r\n            </fieldset>\r\n		</div>\r\n	</div>\r\n\r\n  <div class=\"accordion-box\">\r\n	  <div class=\"form-item\">\r\n     	<fieldset>\r\n        <legend><label for=\"forms-form_handle\" class=\"question\"><{$smarty.const._AM_SETTINGS_FORM_HANDLE}></label></legend>\r\n			  <input type=\"text\" name=\"forms-form_handle\" value=\"<{$content.form_handle}>\" >\r\n              <a class=\"tooltip\"><span><{$smarty.const._AM_SETTINGS_FORM_HANDLE_EXPLAIN}></span></a>\r\n		  </fieldset>\r\n	  </div>\r\n  </div>\r\n\r\n	<div class=\"accordion-box\">\r\n		<div class=\"form-item\">\r\n			<fieldset>\r\n				<legend><label class=\"question\">Note</label></legend>\r\n				<textarea id=\"forms-note\" name=\"forms-note\"><{$content.note}></textarea>\r\n			</fieldset>\r\n		</div>\r\n	</div>\r\n\r\n	<div class=\"accordion-box\">\r\n		<div class=\"form-item required\">\r\n        	<fieldset>\r\n                <legend><label for=\"forms-title\" class=\"question\"><{$smarty.const._AM_EOG_Repair}>\r\n                </label>\r\n                </legend><input type=\"button\" id=\"fix_entries\" value=\"Repair Ownership table\">\r\n             </fieldset>\r\n		</div>\r\n	</div>\r\n		<{if $content.istableform == true && $content.fid == \"new\"}>\r\n		<div class=\"accordion-box\">\r\n			<div class=\"form-item\">\r\n				<fieldset>\r\n                    <legend><label for=\"database_table\" class=\"question\"><{$smarty.const._AM_SETTINGS_FORM_DATABASE}></label></legend>\r\n                    <input type=\"text\" id=\"forms-tableform\" name=\"forms-tableform\" value=\"\" />\r\n                    <div class=\"description\">\r\n                        <p><{$smarty.const._AM_SETTINGS_FORM_DATABASE_EXPLAIN}></p>\r\n                    </div>\r\n                </fieldset>\r\n			</div>\r\n		</div>\r\n		<div style=\"clear: both;\"></div>\r\n		<{/if}>\r\n	\r\n		<{if $content.istableform == false}>\r\n		<div class=\"accordion-box\">\r\n			<div class=\"form-item\">\r\n        	<fieldset>\r\n				<legend><label class=\"question\"><{$smarty.const._AM_SETTINGS_FORM_ENTRIES_ALLOWED}></label></legend>\r\n				\r\n				<div class=\"form-radios\">\r\n					<label for=\"group\"><input type=\"radio\" id=\"group\" name=\"forms-single\" value=\"group\" /><{$smarty.const._AM_SETTINGS_FORM_ENTRIES_ONEPERGROUP}></label>\r\n				</div>\r\n				<div class=\"form-radios\">\r\n					<label for=\"on\"><input type=\"radio\" id=\"user\" name=\"forms-single\" value=\"user\" /><{$smarty.const._AM_SETTINGS_FORM_ENTRIES_ONEPERUSER}></label>\r\n				</div>\r\n				<div class=\"form-radios\">\r\n					<label for=\"empty\"><input type=\"radio\" id=\"off\" name=\"forms-single\" value=\"off\" /><{$smarty.const._AM_SETTINGS_FORM_ENTRIES_MORETHANONE}></label>\r\n				</div>\r\n             </fieldset>\r\n		</div>\r\n	</div>\r\n	<div style=\"clear: both;\"></div>\r\n	\r\n		<{if $content.fid == \"new\"}>\r\n			<div class=\"accordion-box\">\r\n			<div class=\"form-item\">\r\n			<fieldset>\r\n				<legend><label class=\"question\"><{$smarty.const._AM_SETTINGS_FORM_DEFAULT_GROUP_PERM}></label></legend>\r\n				<select name=\"groups_can_edit[]\" multiple size=8>\r\n				<{html_options options=$content.groupsCanEditOptions selected=$content.groupsCanEditDefaults}>\r\n				</select>\r\n			</fieldset>\r\n			</div>\r\n			</div>\r\n			<div style=\"clear: both;\"></div>\r\n		<{/if}>\r\n	\r\n		<{if $content.fid != \"new\" AND $content.elementheadings|is_array AND $content.elementheadings|@count}>\r\n		<div class=\"accordion-box\">\r\n			<div class=\"form-item\">\r\n				<fieldset>\r\n									<legend><label class=\"question\"><{$smarty.const._AM_SETTINGS_FORM_SHOWING_LIST}></label></legend>\r\n									<select name=headerlist[] size=10 multiple class=\"form-multiple-select\">\r\n											<{foreach from=$content.elementheadings item=element}>\r\n											<option value=<{$element.ele_id}><{$element.selected}>><{$element.text}></option>\r\n											<{/foreach}>\r\n									</select>\r\n							</fieldset>\r\n			</div>\r\n		</div>\r\n		<{/if}>\r\n\r\n		<div class=\"accordion-box\">\r\n			<div class=\"form-item\">\r\n				<fieldset>\r\n					<legend><label class=\"question\">Do you want to keep a revision history of all the changes people make to entries in this form?</label></legend>\r\n					<div class=\"form-radios\">\r\n						<label for=\"store_revisions-0\"><input type=\"radio\" id=\"store_revisions-0\" name=\"forms-store_revisions\" value=\"0\" />No</label>\r\n					</div>\r\n					<div class=\"form-radios\">\r\n						<label for=\"store_revisions-1\"><input type=\"radio\" id=\"store_revisions-1\" name=\"forms-store_revisions\" value=\"1\" />Yes, store revision history for this form</label>\r\n					</div>\r\n					<div class=\"description\">\r\n						<p>This can increase the size of your database <b>a lot</b> if you turn on revisions for a form where entries are updated very often!</p>\r\n					</div>\r\n				</fieldset>\r\n			</div>\r\n		</div>\r\n					\r\n        <div class=\"accordion-box\">\r\n			<div class=\"form-item\">\r\n				<fieldset>\r\n					<legend><label class=\"question\">Do you want to send notification e-mails for activity in this form once a day as a digest?</label></legend>\r\n					<div class=\"form-radios\">\r\n						<label for=\"send_digests-0\"><input type=\"radio\" id=\"send_digests-0\" name=\"forms-send_digests\" value=\"0\" />No, send notifications right away</label>\r\n					</div>\r\n					<div class=\"form-radios\">\r\n						<label for=\"send_digests-1\"><input type=\"radio\" id=\"send_digests-1\" name=\"forms-send_digests\" value=\"1\" />Yes</label>\r\n					</div>\r\n					<div class=\"description\">\r\n						<p>This feature requires that you turn on the <a href=\"<{$xoops_url}>/modules/system/admin.php?fct=preferences&op=showmod&mod=<{$adminPage.formulizeModId}>\">Formulize module Preference</a> for sending nofitications via cron job. In addition to creating a cron job for triggering \'notify.php\' to process notification events, you will need to set a cron job for triggering \'digest.php\' which will actually send the digest e-mails.</p>\r\n					</div>\r\n				</fieldset>\r\n			</div>\r\n		</div>\r\n    \r\n    \r\n	<{/if}>\r\n	\r\n	<{if $content.applications|is_array AND $content.applications|@count > 0}>\r\n	<div class=\"accordion-box\">\r\n		<div class=\"form-item\">\r\n			<fieldset>\r\n                <legend><label class=\"question\"><{$smarty.const._AM_SETTINGS_FORM_APP_PART}></label></legend>\r\n                <select name=\"apps[]\" id=\"apps\" size=10 multiple>\r\n                    <{foreach from=$content.applications item=application}>\r\n                        <option value=<{$application.appid}><{$application.selected}>><{$application.text}></option>\r\n                    <{/foreach}>\r\n                </select>\r\n            </fieldset>\r\n		</div>\r\n	</div>\r\n	<{/if}>\r\n	<div class=\"accordion-box\">\r\n		<div class=\"form-item\">\r\n			<fieldset>\r\n                <legend><label class=\"question\"><{$smarty.const._AM_SETTINGS_FORM_APPNEW}></label></legend>\r\n                <div class=\"form-radios radio-inline\">\r\n                    <label for=\"yes\"><input type=\"radio\" id=\"new-app-yes\" name=\"new_app_yes_no\" value=\"yes\"/><{$smarty.const._AM_YES}></label>\r\n                </div>\r\n                <div class=\"form-radios radio-inline\">\r\n                    <label for=\"yes\"><input type=\"radio\" id=\"new-app-no\" name=\"new_app_yes_no\" value=\"no\" checked/><{$smarty.const._AM_NO}></label>\r\n                </div>\r\n								<br /><br />\r\n								<div class=\"form-item\" id=\"new-application-box\" style=\"display: none;\">\r\n			          <label class=\"question\">What is the name of the new application?</label>\r\n                <input type=\"text\" id=\"applications-name\" name=\"applications-name\" value=\"\" />\r\n							</div>\r\n            </fieldset>\r\n		</div>\r\n	</div> \r\n\r\n</form>\r\n<script type=\"text/javascript\">\r\n  $(\"#<{$content.singleentry}>\").attr(\'checked\', true);\r\n  $(\"#store_revisions-<{$content.store_revisions}>\").attr(\'checked\', true);\r\n  $(\"#send_digests-<{$content.send_digests}>\").attr(\'checked\', true);\r\n	\r\n	$(\"#forms-title\").keydown(function () {\r\n		window.document.getElementById(\'reload_settings\').value = 1;\r\n	});\r\n	\r\n	$(\'input:radio[name=new_app_yes_no]\').change(function(){\r\n		if($(\'input:radio[name=new_app_yes_no]:checked\').val() == \'yes\') {\r\n			window.document.getElementById(\"new-application-box\").style.display = \'block\';\r\n		} else {\r\n			window.document.getElementById(\"new-application-box\").style.display = \'none\';\r\n		}\r\n		});\r\n	\r\n	$(\".savebutton\").click(function() {\r\n		if($(\"[name=forms-title]\").val() == \"\") {\r\n			alert(\"Forms must have a name!\");\r\n			$(\"[name=forms-title]\").focus();\r\n		}\r\n		if ($(\"[name=forms-form_handle]\").val() == \"\") {\r\n			fillHandle();\r\n		}\r\n		\r\n	});\r\n	function fillHandle(){\r\n		//this function will be called when they are typing title to update handle\r\n		if (\"<{$content.fid}>\" == \"new\") {\r\n			var str=$(\"[name=forms-title]\").val();\r\n			str=str.toLowerCase().replace(new RegExp(\"[^a-z0-9]\",\"gm\"),\"_\");\r\n			str=str.replace(new RegExp(\"_{2,}\",\"gm\"),\"_\").substring(0,20);\r\n			$(\"[name=forms-form_handle]\").val(str);\r\n		}\r\n	}\r\n	$(\'#fix_entries\').click(function(){\r\n		$.ajax({\r\n			type:\"POST\",\r\n			url:\"<{$xoops_url}>/modules/formulize/admin/repair_eog_table.php\",\r\n			data:{\"form_id\":\"<{$content.fid}>\",\"form_handle\":\"<{$content.form_handle}>\"},\r\n			success:function(response){\r\n				alert(response);\r\n			}\r\n		});\r\n	});	\r\n</script>\r\n<div style=\"clear: both\"></div>\r\n</div> <!--// end content -->'),
(124,'<div class=\"panel-content content\" xmlns=\"http://www.w3.org/1999/html\">\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\" action=\"<{$xoops_url}>/modules/formulize/admin/ui.php?page=form&aid=<{$content.aid}>&fid=<{$content.fid}>&tab=permissions\" method=\"post\">\r\n<{php}>print $GLOBALS[\'xoopsSecurity\']->getTokenHTML()<{/php}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"form_permissions\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.fid}>\">\r\n<{* form number is used to trigger the actual submission of this form after data has been saved, so we can pass this state info to the server *}>\r\n<input type=\"hidden\" name=\"form_number\" value=<{$number}>>\r\n<input type=\"hidden\" name=\"tabs_selected\" value=\"<{$number-1}>\">\r\n<input type=\"hidden\" name=\"reload\" value=\"\">\r\n<input type=\"hidden\" name=\"conditionsdelete\" value=\"\">\r\n<input type=\"hidden\" id=\"permscrollx\" name=\"scrollx\" value=\"\">\r\n\r\n<div class=\"accordion-box\">\r\n		<div class=\"form-item\">\r\n			<fieldset>\r\n                <legend><label class=\"question\"><{$smarty.const._AM_PERMISSIONS_CHOOSE_GROUPS}></label></legend>\r\n                <select name=\"groups[]\" id=\"groups\" size=10 multiple>\r\n                    <{foreach from=$content.groups item=group}>\r\n                        <option value=<{$group.id}><{$group.selected}>><{$group.name}></option>\r\n                    <{/foreach}>\r\n                </select>\r\n								<br /><br />\r\n								<input type=\"button\" name=\"showperms\" value=\"<{$smarty.const._AM_PERMISSIONS_SHOW_PERMS_FOR_GROUPS}>\" />\r\n								<input type=\"hidden\" name=\"useselection\" value=\"\" />\r\n								<br /><br />\r\n								<label class=\"question\"><{$smarty.const._AM_PERMISSIONS_LIST_GROUPS}></label>\r\n								<div class=\"form-radios radio-inline\">\r\n                    <label for=\"alpha\"><input type=\"radio\" id=\"alpha\" name=\"order\" value=\"alpha\"/><{$smarty.const._AM_PERMISSIONS_LIST_ALPHA}></label>\r\n                </div>\r\n                <div class=\"form-radios radio-inline\">\r\n                    <label for=\"creation\"><input type=\"radio\" id=\"creation\" name=\"order\" value=\"creation\" checked/><{$smarty.const._AM_PERMISSIONS_LIST_CREATION}></label>\r\n                </div>\r\n								<br /><br />\r\n								<input type=\"button\" name=\"savegrouplist\" value=\"<{$smarty.const._AM_PERMISSIONS_LIST_SAVE}>\">\r\n								<input type=\"hidden\" name=\"grouplistname\" value=\"\">\r\n								<input type=\"hidden\" name=\"grouplistid\" value=\"\">\r\n            </fieldset>\r\n		</div>\r\n\r\n		<div class=\"form-item\">\r\n			<fieldset>\r\n                <legend><label class=\"question\"><{$smarty.const._AM_PERMISSIONS_LIST_ONCE}></label></legend>\r\n                <select name=\"grouplists\" id=\"grouplists\" size=1>\r\n                    <{foreach from=$content.grouplists item=grouplist}>\r\n                        <option value=<{$grouplist.id}><{$grouplist.selected}>><{$grouplist.name}></option>\r\n                    <{/foreach}>\r\n                </select>\r\n								<input type=\"hidden\" name=\"loadthislist\" value=\"\">\r\n								<input type=\"button\" name=\"removegrouplist\" value=\"<{$smarty.const._AM_PERMISSIONS_LIST_REMOVE}>\">\r\n								<input type=\"hidden\" name=\"removelistid\" value=\"\">\r\n            </fieldset>\r\n		</div>\r\n\r\n		<div class=\"form-item\">\r\n			<fieldset>\r\n			<legend><label class=\"question\"><{$smarty.const._AM_PERMISSIONS_SAME_CHECKBOX}></label></legend>\r\n							<div class=\"form-radios radio-inline\">\r\n                    <label for=\"same\"><input type=\"radio\" id=\"same\" name=\"same_diff\" value=\"same\" /><{$smarty.const._AM_PERMISSIONS_SAME_CHECKBOX_YES}></label>\r\n                </div>\r\n                <div class=\"form-radios radio-inline\">\r\n                    <label for=\"different\"><input type=\"radio\" id=\"different\" name=\"same_diff\" value=\"different\" /><{$smarty.const._AM_PERMISSIONS_SAME_CHECKBOX_NO}></label>\r\n                </div>\r\n								<div class=\"description\">\r\n									<p><{$smarty.const._AM_PERMISSIONS_SAME_CHECKBOX_EXPLAIN}></p>\r\n								</div>\r\n\r\n	        </fieldset>\r\n		</div>\r\n\r\n        <div class=\"form-item\">\r\n            <fieldset>\r\n                <legend><label class=\"question\"><{$smarty.const._AM_PERMISSIONS_REVIEW_PERMISSIONS}></label></legend>\r\n\r\n                    <input type=\"text\" name=\"submitted_user\">\r\n                    <input name=\"show_user_perms\" value=\"Show permissions for the user\" type=\"button\">\r\n                    <input type=\"hidden\" name=\"search_by_user\" value=\"\" />\r\n            </fieldset>\r\n        </div>\r\n\r\n</div>\r\n\r\n\r\n<div class=\"accordion-box\">\r\n\r\n	<{if $content.groupperms|is_array AND $content.groupperms|@count == 0}>\r\n		<div class=\"form-item\">\r\n		<fieldset>\r\n			<legend><label class=\"question\"><{$smarty.const._AM_PERMISSIONS_SELECT_GROUP}></label></legend>\r\n		</fieldset>\r\n		</div>\r\n	<{/if}>\r\n\r\n    <!--Show the submitted user\'s permission-->\r\n    <{if $content.submitted_user}>\r\n         <div class=\"accordion-box\" id=\"user_perms_accordion_box\">\r\n             <div class=\"form-item\">\r\n                 <fieldset>\r\n                     <legend><label class=\"question\">User Permissions for <{$content.submitted_user}></label></legend>\r\n\r\n                     <p><b>Groups</b></p>\r\n                     <div class=\"permissiongroup\">\r\n                         <{foreach from=$content.groupperms item=groupperm}>\r\n                            <p><{$groupperm.name}></p>\r\n                         <{/foreach}>\r\n                     </div>\r\n\r\n                     <!--The basics-->\r\n                     <p><b><{$smarty.const._AM_PERMISSIONS_DEFINE_BASIC}></b></p>\r\n                     <div class=\"permissiongroup\">\r\n                        <{if $content.userperms.view_form}>\r\n                             <p><{$smarty.const._AM_PERMISSIONS_DEFINE_VIEWFORM}> <span class=\"user_groups\"> --\r\n                                <{foreach from=$content.userperms.view_form item=group}>\r\n                                    <{$group}>;\r\n                                <{/foreach}> </span>\r\n                             </p>\r\n                         <{/if}>\r\n                         <{if $content.userperms.add_own_entry}>\r\n                             <p><{$smarty.const._AM_PERMISSIONS_DEFINE_CREATEOWNENTRIES}> <span class=\"user_groups\">--\r\n                                 <{foreach from=$content.userperms.add_own_entry item=group}>\r\n                                 <{$group}>;\r\n                                 <{/foreach}> </span>\r\n                             </p>\r\n                         <{/if}>\r\n                         <{if $content.userperms.update_own_entry}>\r\n                             <p><{$smarty.const._AM_PERMISSIONS_DEFINE_UPDATEOWNENTRIES}> <span class=\"user_groups\">--\r\n                                 <{foreach from=$content.userperms.update_own_entry item=group}>\r\n                                 <{$group}>;\r\n                                 <{/foreach}> </span>\r\n                             </p>\r\n                         <{/if}>\r\n                         <{if $content.userperms.update_group_entries}>\r\n                             <p><{$smarty.const._AM_PERMISSIONS_DEFINE_UPDATE_GROUP_ENTRIES}> <span class=\"user_groups\">--\r\n                                 <{foreach from=$content.userperms.update_group_entries item=group}>\r\n                                 <{$group}>;\r\n                                 <{/foreach}> </span>\r\n                             </p>\r\n                         <{/if}>\r\n                         <{if $content.userperms.update_other_entries}>\r\n                             <p><{$smarty.const._AM_PERMISSIONS_DEFINE_UPDATEOTHERENTRIES}> <span class=\"user_groups\">--\r\n                                 <{foreach from=$content.userperms.update_other_entries item=group}>\r\n                                 <{$group}>;\r\n                                 <{/foreach}> </span>\r\n                             </p>\r\n                         <{/if}>\r\n                         <{if $content.userperms.delete_own_entry}>\r\n                             <p><{$smarty.const._AM_PERMISSIONS_DEFINE_DELETEOWNENTRIES}> <span class=\"user_groups\">--\r\n                                 <{foreach from=$content.userperms.delete_own_entry item=group}>\r\n                                 <{$group}>;\r\n                                 <{/foreach}> </span>\r\n                             </p>\r\n                         <{/if}>\r\n                         <{if $content.userperms.delete_group_entries}>\r\n                             <p><{$smarty.const._AM_PERMISSIONS_DEFINE_DELETE_GROUP_ENTRIES}> <span class=\"user_groups\">--\r\n                                 <{foreach from=$content.userperms.delete_group_entries item=group}>\r\n                                 <{$group}>;\r\n                                 <{/foreach}> </span>\r\n                             </p>\r\n                         <{/if}>\r\n                         <{if $content.userperms.delete_other_entries}>\r\n                             <p><{$smarty.const._AM_PERMISSIONS_DEFINE_DELETEOTHERENTRIES}> <span class=\"user_groups\">--\r\n                                 <{foreach from=$content.userperms.delete_other_entries item=group}>\r\n                                 <{$group}>;\r\n                                 <{/foreach}> </span>\r\n                             </p>\r\n                         <{/if}>\r\n\r\n                     </div>\r\n\r\n                     <!--Visibility-->\r\n                     <p><b><{$smarty.const._AM_PERMISSIONS_DEFINE_VISIBILITY}></b></p>\r\n                     <div class=\"permissiongroup\">\r\n                         <{if $content.userperms.view_private_elements}>\r\n                             <p><{$smarty.const._AM_PERMISSIONS_DEFINE_VISIBILITY_PRIVATE}> <span class=\"user_groups\">--\r\n                                 <{foreach from=$content.userperms.view_private_elements item=group}>\r\n                                 <{$group}>;\r\n                                 <{/foreach}> </span>\r\n                             </p>\r\n                         <{/if}>\r\n\r\n                         <{if $content.userperms.view_their_own_entries}>\r\n                             <p><{$smarty.const._AM_PERMISSIONS_DEFINE_VISIBILITY_THEIROWN}> <span class=\"user_groups\">--\r\n                                 <{foreach from=$content.userperms.view_their_own_entries item=group}>\r\n                                 <{$group}>;\r\n                                 <{/foreach}> </span>\r\n                             </p>\r\n                         <{/if}>\r\n                         <{if $content.userperms.view_globalscope}>\r\n                             <p><{$smarty.const._AM_PERMISSIONS_DEFINE_VISIBILITY_VIEWALL}> <span class=\"user_groups\">--\r\n                                 <{foreach from=$content.userperms.view_globalscope item=group}>\r\n                                 <{$group}>;\r\n                                 <{/foreach}> </span>\r\n                             </p>\r\n                         <{/if}>\r\n\r\n                         <{if $content.userperms.view_groupscope.checked}>\r\n                             <p><{$smarty.const._AM_PERMISSIONS_DEFINE_VISIBILITY_VIEWOTHERGROUPONLY}> </p>\r\n                             <ul>\r\n                                 <{foreach from=$content.groups item=group}>\r\n                                     <{if $content.userperms.view_groupscope[$group.id]}>\r\n                                         <li class=\"user_perms_li\"><{$group.name}> <span class=\"user_groups\">--\r\n                                             <{foreach from=$content.userperms.view_groupscope[$group.id] item=group}>\r\n                                                 <{$group}>;\r\n                                             <{/foreach}> </span>\r\n                                         </li>\r\n                                     <{/if}>\r\n                                 <{/foreach}>\r\n                             </ul>\r\n                         <{/if}>\r\n\r\n                         <{if $content.userperms.view_groupfilter.all || $content.userperms.view_groupfilter.oom }>\r\n                             <p><{$smarty.const._AM_PERMISSIONS_DEFINE_VISIBILITY_CONDITIONS}> </p>\r\n                             <ul>\r\n                                 <{if $content.userperms.view_groupfilter.all}>\r\n                                     <li class=\"user_perms_li\">Match all of these:</li>\r\n                                     <ul>\r\n                                         <{foreach from=$content.userperms.view_groupfilter.all key=\"filter\" item=\"groups\"}>\r\n                                            <li class=\"user_perms_li_li\"><{$filter}> <span class=\"user_groups\">-- <{foreach from=$groups item=\"group\"}><{$group}>;<{/foreach}> </span></li>\r\n                                         <{/foreach}>\r\n                                     </ul>\r\n                                 <{/if}>\r\n\r\n                                 <{if $content.userperms.view_groupfilter.oom}>\r\n                                     <li class=\"user_perms_li\">Match one or more of these:</li>\r\n                                     <ul>\r\n                                         <{foreach from=$content.userperms.view_groupfilter.oom key=\"filter\" item=\"groups\"}>\r\n                                             <li class=\"user_perms_li_li\"><{$filter}> <span class=\"user_groups\">-- <{foreach from=$groups item=\"group\"}><{$group}>;<{/foreach}></span></li>\r\n                                         <{/foreach}>\r\n                                     </ul>\r\n                                 <{/if}>\r\n                            </ul>\r\n                         <{/if}>\r\n                     </div>\r\n\r\n                    <!--Publishing \'Saved Views\' of form entries-->\r\n                    <p><b><{$smarty.const._AM_PERMISSIONS_DEFINE_VIEW_CONDITIONS}></b></p>\r\n                    <div class=\"permissiongroup\">\r\n                        <{if $content.userperms.manage_own}>\r\n                            <p><{$smarty.const._AM_PERMISSIONS_DEFINE_VIEW_THEIROWN}> <span class=\"user_groups\">--\r\n                                <{foreach from=$content.userperms.manage_own item=group}>\r\n                                    <{$group}>;\r\n                                <{/foreach}> </span>\r\n                            </p>\r\n                        <{/if}>\r\n                        <{if $content.userperms.publish_reports}>\r\n                            <p><{$smarty.const._AM_PERMISSIONS_DEFINE_VIEW_INTHEIR}> <span class=\"user_groups\">--\r\n                                <{foreach from=$content.userperms.publish_reports item=group}>\r\n                                    <{$group}>;\r\n                                <{/foreach}> </span>\r\n                            </p>\r\n                        <{/if}>\r\n                        <{if $content.userperms.publish_globalscope}>\r\n                            <p><{$smarty.const._AM_PERMISSIONS_DEFINE_VIEW_FOROTHER}> <span class=\"user_groups\">--\r\n                                <{foreach from=$content.userperms.publish_globalscope item=group}>\r\n                                    <{$group}>;\r\n                                <{/foreach}> </span>\r\n                            </p>\r\n                        <{/if}>\r\n                        <{if $content.userperms.update_other_reports}>\r\n                            <p><{$smarty.const._AM_PERMISSIONS_DEFINE_VIEW_UPDATE}> <span class=\"user_groups\">--\r\n                                <{foreach from=$content.userperms.update_other_reports item=group}>\r\n                                    <{$group}>;\r\n                                <{/foreach}> </span>\r\n                            </p>\r\n                        <{/if}>\r\n                        <{if $content.userperms.delete_other_reports}>\r\n                            <p><{$smarty.const._AM_PERMISSIONS_DEFINE_VIEW_DELETE}> <span class=\"user_groups\">--\r\n                                <{foreach from=$content.userperms.delete_other_reports item=group}>\r\n                                    <{$group}>;\r\n                                <{/foreach}> </span>\r\n                            </p>\r\n                        <{/if}>\r\n                    </div>\r\n\r\n                    <!--Advanced options-->\r\n                    <p><b><{$smarty.const._AM_PERMISSIONS_ADVANCED}></b></p>\r\n                    <div class=\"permissiongroup\">\r\n                        <{if $content.userperms.import_data}>\r\n                            <p><{$smarty.const._AM_PERMISSIONS_ADVANCED_IMPORT}> <span class=\"user_groups\">--\r\n                                <{foreach from=$content.userperms.import_data item=group}>\r\n                                    <{$group}>;\r\n                                <{/foreach}> </span>\r\n                            </p>\r\n                        <{/if}>\r\n                        <{if $content.userperms.set_notifications_for_others}>\r\n                            <p><{$smarty.const._AM_PERMISSIONS_ADVANCED_NOTIFICATIONS}> <span class=\"user_groups\">--\r\n                                <{foreach from=$content.userperms.set_notifications_for_others item=group}>\r\n                                    <{$group}>;\r\n                                <{/foreach}> </span>\r\n                            </p>\r\n                        <{/if}>\r\n                        <{if $content.userperms.add_proxy_entries}>\r\n                            <p><{$smarty.const._AM_PERMISSIONS_ADVANCED_CREATEFOROTHER}> <span class=\"user_groups\">--\r\n                                <{foreach from=$content.userperms.add_proxy_entries item=group}>\r\n                                    <{$group}>;\r\n                                <{/foreach}> </span>\r\n                            </p>\r\n                        <{/if}>\r\n                        <{if $content.userperms.update_entry_ownership}>\r\n                            <p><{$smarty.const._AM_PERMISSIONS_ADVANCED_CHANGEOWNER}> <span class=\"user_groups\">--\r\n                                <{foreach from=$content.userperms.update_entry_ownership item=group}>\r\n                                    <{$group}>;\r\n                                <{/foreach}> </span>\r\n                            </p>\r\n                        <{/if}>\r\n                        <{if $content.userperms.ignore_editing_lock}>\r\n                            <p>Save entries even when they are locked while being edited elsewhere (saving cancels existing locks) <span class=\"user_groups\">--\r\n                                <{foreach from=$content.userperms.ignore_editing_lock item=group}>\r\n                                    <{$group}>;\r\n                                <{/foreach}> </span>\r\n                            </p>\r\n                        <{/if}>\r\n                        <{if $content.userperms.edit_form}>\r\n                            <p><{$smarty.const._AM_PERMISSIONS_ADVANCED_ALTER}> <span class=\"user_groups\">--\r\n                                <{foreach from=$content.userperms.edit_form item=group}>\r\n                                    <{$group}>;\r\n                                <{/foreach}> </span>\r\n                            </p>\r\n                        <{/if}>\r\n                        <{if $content.userperms.delete_form}>\r\n                            <p><{$smarty.const._AM_PERMISSIONS_ADVANCED_DELETEFORM}> <span class=\"user_groups\">--\r\n                                <{foreach from=$content.userperms.delete_form item=group}>\r\n                                    <{$group}>;\r\n                                <{/foreach}> </span>\r\n                            </p>\r\n                        <{/if}>\r\n                    </div>\r\n                </fieldset>\r\n            </div>\r\n        </div>\r\n    <{else}>\r\n        <{foreach from=$content.groupperms item=groupperm}>\r\n            <input type=\"hidden\" name=\"group_list[]\" value=<{$groupperm.id}>>\r\n            <div class=\"accordion-box\">\r\n                <div class=\"form-item\">\r\n                    <fieldset>\r\n                        <legend><label class=\"question\"><{$groupperm.name}></label></legend>\r\n                        <p><b><{$smarty.const._AM_PERMISSIONS_DEFINE_BASIC}></b></p>\r\n                        <div class=\"permissiongroup\">\r\n                            <input type=\"checkbox\" class=\"view_form\" name=\"<{$content.fid}>_<{$groupperm.id}>_view_form\" value=1 <{$groupperm.view_form}> id=\"<{$content.fid}>_<{$groupperm.id}>_view_form\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_view_form\"><{$smarty.const._AM_PERMISSIONS_DEFINE_VIEWFORM}></label><br />\r\n                            <input type=\"checkbox\" class=\"add_own_entry\" name=\"<{$content.fid}>_<{$groupperm.id}>_add_own_entry\" value=1 <{$groupperm.add_own_entry}> id=\"<{$content.fid}>_<{$groupperm.id}>_add_own_entry\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_add_own_entry\"><{$smarty.const._AM_PERMISSIONS_DEFINE_CREATEOWNENTRIES}></label><br />\r\n                            <input type=\"checkbox\" class=\"update_own_entry\" name=\"<{$content.fid}>_<{$groupperm.id}>_update_own_entry\" value=1 <{$groupperm.update_own_entry}> id=\"<{$content.fid}>_<{$groupperm.id}>_update_own_entry\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_update_own_entry\"><{$smarty.const._AM_PERMISSIONS_DEFINE_UPDATEOWNENTRIES}></label><br />\r\n                            <input type=\"checkbox\" class=\"update_group_entries\" name=\"<{$content.fid}>_<{$groupperm.id}>_update_group_entries\" value=1 <{$groupperm.update_group_entries}> id=\"<{$content.fid}>_<{$groupperm.id}>_update_group_entries\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_update_group_entries\"><{$smarty.const._AM_PERMISSIONS_DEFINE_UPDATE_GROUP_ENTRIES}></label><br />\r\n                            <input type=\"checkbox\" class=\"update_other_entries\" name=\"<{$content.fid}>_<{$groupperm.id}>_update_other_entries\" value=1 <{$groupperm.update_other_entries}> id=\"<{$content.fid}>_<{$groupperm.id}>_update_other_entries\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_update_other_entries\"><{$smarty.const._AM_PERMISSIONS_DEFINE_UPDATEOTHERENTRIES}></label><br />\r\n                            <input type=\"checkbox\" class=\"delete_own_entry\" name=\"<{$content.fid}>_<{$groupperm.id}>_delete_own_entry\" value=1 <{$groupperm.delete_own_entry}> id=\"<{$content.fid}>_<{$groupperm.id}>_delete_own_entry\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_delete_own_entry\"><{$smarty.const._AM_PERMISSIONS_DEFINE_DELETEOWNENTRIES}></label><br />\r\n                            <input type=\"checkbox\" class=\"delete_group_entries\" name=\"<{$content.fid}>_<{$groupperm.id}>_delete_group_entries\" value=1 <{$groupperm.delete_group_entries}> id=\"<{$content.fid}>_<{$groupperm.id}>_delete_group_entries\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_delete_group_entries\"><{$smarty.const._AM_PERMISSIONS_DEFINE_DELETE_GROUP_ENTRIES}></label><br />\r\n                            <input type=\"checkbox\" class=\"delete_other_entries\" name=\"<{$content.fid}>_<{$groupperm.id}>_delete_other_entries\" value=1 <{$groupperm.delete_other_entries}> id=\"<{$content.fid}>_<{$groupperm.id}>_delete_other_entries\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_delete_other_entries\"><{$smarty.const._AM_PERMISSIONS_DEFINE_DELETEOTHERENTRIES}></label><br />\r\n                        </div>\r\n                        <p><b><{$smarty.const._AM_PERMISSIONS_DEFINE_VISIBILITY}></b></p>\r\n                        <div class=\"permissiongroup\">\r\n                            <input type=\"checkbox\" class=\"view_private_elements\" name=\"<{$content.fid}>_<{$groupperm.id}>_view_private_elements\" value=1 <{$groupperm.view_private_elements}> id=\"<{$content.fid}>_<{$groupperm.id}>_view_private_elements\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_view_private_elements\"><{$smarty.const._AM_PERMISSIONS_DEFINE_VISIBILITY_PRIVATE}></label><br />\r\n                            <input type=\"checkbox\" name=\"<{$content.fid}>_<{$groupperm.id}>_dummy1\" value=1 checked disabled id=\"<{$content.fid}>_<{$groupperm.id}>_dummy1\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_dummy1\"><{$smarty.const._AM_PERMISSIONS_DEFINE_VISIBILITY_THEIROWN}></label><br />\r\n                            <input type=\"checkbox\" class=\"view_globalscope\" name=\"<{$content.fid}>_<{$groupperm.id}>_view_globalscope\" value=1 <{$groupperm.view_globalscope}> id=\"<{$content.fid}>_<{$groupperm.id}>_view_globalscope\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_view_globalscope\"><{$smarty.const._AM_PERMISSIONS_DEFINE_VISIBILITY_VIEWALL}></label><br />\r\n                            <input type=\"checkbox\" class=\"view_groupscope\" name=\"<{$content.fid}>_<{$groupperm.id}>_view_groupscope\" value=1 <{$groupperm.view_groupscope}> id=\"<{$content.fid}>_<{$groupperm.id}>_view_groupscope\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_view_groupscope\"><{$smarty.const._AM_PERMISSIONS_DEFINE_VISIBILITY_VIEWOTHERGROUPONLY}></label>\r\n                            <div class=\"groupselectionbox\">\r\n                                <select name=\"groupsscope_choice_<{$content.fid}>_<{$groupperm.id}>[]\" size=6 multiple>\r\n                                    <option value=0<{$groupperm.groupscope_choice.0}>><{$smarty.const._AM_PERMISSIONS_DEFINE_VISIBILITY_VIEWOTHERGROUPISAMEMEBER}></option>\r\n                                    <{foreach from=$content.groups item=group}>\r\n                                    <{assign var=groupid value=$group.id}>\r\n                                    <option value=<{$groupid}><{$groupperm.groupscope_choice.$groupid}>><{$group.name}></option>\r\n                                    <{/foreach}>\r\n                                </select>\r\n                            </div>\r\n                            <input type=\"checkbox\" name=\"<{$content.fid}>_<{$groupperm.id}>_filterentries\" value=1 <{$groupperm.hasgroupfilter}> <{$smarty.const._AM_PERMISSIONS_DEFINE_VISIBILITY_DISABLED}> id=\"<{$content.fid}>_<{$groupperm.id}>_filterentries\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_filterentries\"><{$smarty.const._AM_PERMISSIONS_DEFINE_VISIBILITY_CONDITIONS}></label>\r\n                            <div class=\"groupselectionbox\">\r\n                                <{$groupperm.groupfilter}>\r\n                            </div>\r\n                        </div>\r\n                        <p><b><{$smarty.const._AM_PERMISSIONS_DEFINE_VIEW_CONDITIONS}></b></p>\r\n                        <div class=\"permissiongroup\">\r\n                            <input type=\"checkbox\" name=\"<{$content.fid}>_<{$groupperm.id}>_dummy2\" value=1 checked disabled id=\"<{$content.fid}>_<{$groupperm.id}>_dummy2\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_dummy2\"><{$smarty.const._AM_PERMISSIONS_DEFINE_VIEW_THEIROWN}></label><br />\r\n                            <input type=\"checkbox\" class=\"publish_reports\" name=\"<{$content.fid}>_<{$groupperm.id}>_publish_reports\" value=1 <{$groupperm.publish_reports}> id=\"<{$content.fid}>_<{$groupperm.id}>_publish_reports\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_publish_reports\"><{$smarty.const._AM_PERMISSIONS_DEFINE_VIEW_INTHEIR}></label><br />\r\n                            <input type=\"checkbox\" class=\"publish_globalscope\" name=\"<{$content.fid}>_<{$groupperm.id}>_publish_globalscope\" value=1 <{$groupperm.publish_globalscope}> id=\"<{$content.fid}>_<{$groupperm.id}>_publish_globalscope\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_publish_globalscope\"><{$smarty.const._AM_PERMISSIONS_DEFINE_VIEW_FOROTHER}></label><br />\r\n                            <input type=\"checkbox\" class=\"update_other_reports\" name=\"<{$content.fid}>_<{$groupperm.id}>_update_other_reports\" value=1 <{$groupperm.update_other_reports}> id=\"<{$content.fid}>_<{$groupperm.id}>_update_other_reports\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_update_other_reports\"><{$smarty.const._AM_PERMISSIONS_DEFINE_VIEW_UPDATE}></label><br />\r\n                            <input type=\"checkbox\" class=\"delete_other_reports\" name=\"<{$content.fid}>_<{$groupperm.id}>_delete_other_reports\" value=1 <{$groupperm.delete_other_reports}> id=\"<{$content.fid}>_<{$groupperm.id}>_delete_other_reports\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_delete_other_reports\"><{$smarty.const._AM_PERMISSIONS_DEFINE_VIEW_DELETE}></label><br />\r\n                        </div>\r\n                        <p><b><{$smarty.const._AM_PERMISSIONS_ADVANCED}></b></p>\r\n                        <div class=\"permissiongroup\">\r\n                            <input type=\"checkbox\" class=\"import_data\" name=\"<{$content.fid}>_<{$groupperm.id}>_import_data\" value=1 <{$groupperm.import_data}> id=\"<{$content.fid}>_<{$groupperm.id}>_import_data\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_import_data\"><{$smarty.const._AM_PERMISSIONS_ADVANCED_IMPORT}></label><br />\r\n                            <input type=\"checkbox\" class=\"set_notifications_for_others\" name=\"<{$content.fid}>_<{$groupperm.id}>_set_notifications_for_others\" value=1 <{$groupperm.set_notifications_for_others}> id=\"<{$content.fid}>_<{$groupperm.id}>_set_notifications_for_others\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_set_notifications_for_others\"><{$smarty.const._AM_PERMISSIONS_ADVANCED_NOTIFICATIONS}></label><br />\r\n                            <input type=\"checkbox\" class=\"add_proxy_entries\" name=\"<{$content.fid}>_<{$groupperm.id}>_add_proxy_entries\" value=1 <{$groupperm.add_proxy_entries}> id=\"<{$content.fid}>_<{$groupperm.id}>_add_proxy_entries\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_add_proxy_entries\"><{$smarty.const._AM_PERMISSIONS_ADVANCED_CREATEFOROTHER}></label><br />\r\n                            <input type=\"checkbox\" class=\"update_entry_ownership\" name=\"<{$content.fid}>_<{$groupperm.id}>_update_entry_ownership\" value=1 <{$groupperm.update_entry_ownership}> id=\"<{$content.fid}>_<{$groupperm.id}>_update_entry_ownership\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_update_entry_ownership\"><{$smarty.const._AM_PERMISSIONS_ADVANCED_CHANGEOWNER}></label><br />\r\n                            <input type=\"checkbox\" class=\"ignore_editing_lock\" name=\"<{$content.fid}>_<{$groupperm.id}>_ignore_editing_lock\" value=1 <{$groupperm.ignore_editing_lock}> id=\"<{$content.fid}>_<{$groupperm.id}>_ignore_editing_lock\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_ignore_editing_lock\">Save entries even when they are locked while being edited elsewhere (saving cancels existing locks)</label><br />\r\n                            <input type=\"checkbox\" class=\"edit_form\" name=\"<{$content.fid}>_<{$groupperm.id}>_edit_form\" value=1 <{$groupperm.edit_form}> id=\"<{$content.fid}>_<{$groupperm.id}>_edit_form\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_edit_form\"><{$smarty.const._AM_PERMISSIONS_ADVANCED_ALTER}></label><br />\r\n                            <input type=\"checkbox\" class=\"delete_form\" name=\"<{$content.fid}>_<{$groupperm.id}>_delete_form\" value=1 <{$groupperm.delete_form}> id=\"<{$content.fid}>_<{$groupperm.id}>_delete_form\">&nbsp;&nbsp;<label for=\"<{$content.fid}>_<{$groupperm.id}>_delete_form\"><{$smarty.const._AM_PERMISSIONS_ADVANCED_DELETEFORM}></label>\r\n                        </div>\r\n                    </fieldset>\r\n                </div>\r\n            </div>\r\n        <{/foreach}>\r\n    <{/if}>\r\n</div>\r\n\r\n</form>\r\n\r\n<div style=\"clear: both\"></div>\r\n</div> <!--// end content -->\r\n\r\n<script type=\'text/javascript\'>\r\n\r\n$(\"#<{$content.order}>\").attr(\'checked\', true);\r\n$(\"#<{$content.samediff}>\").attr(\'checked\', true);\r\n\r\n$(\"[name=savegrouplist]\").click(function () {\r\n	var grouplistname = prompt(\"Name of this group list\", $(\"#grouplists option:selected\").text());\r\n	if(grouplistname) {\r\n		$(\"[name=grouplistname]\").val(grouplistname);\r\n		$(\"[name=grouplistid]\").val($(\"#grouplists\").val());\r\n		formulize_reload();\r\n	}\r\n  return false;\r\n});\r\n\r\n$(\"[name=removegrouplist]\").click(function () {\r\n	var answer = confirm(\"Are you sure you want to delete the group list \'\"+$(\"#grouplists option:selected\").text()+\"\'?\");\r\n	if(answer) {\r\n		$(\"[name=removelistid]\").val($(\"#grouplists\").val());\r\n		formulize_reload();\r\n	}\r\n  return false;\r\n});\r\n\r\n$(\"[name=grouplists]\").change(function () {\r\n	$(\"[name=loadthislist]\").val($(\"#grouplists\").val());\r\n	formulize_reload();\r\n});\r\n\r\n$(\"[name=showperms]\").click(function () {\r\n	$(\"[name=useselection]\").val(1);\r\n	formulize_reload();\r\n});\r\n\r\n$(\"[name=order]\").change(function () {\r\n	formulize_reload();\r\n});\r\n\r\n$(\"[name=addcon]\").click(function () {\r\n	formulize_reload();\r\n});\r\n\r\n$(\"[name=show_user_perms]\").click(function () {\r\n    $(\"[name=search_by_user]\").val(1);\r\n    formulize_reload();\r\n});\r\n\r\n$(\"[name=submitted_user]\").keypress(function(e) {\r\n    if(e.keyCode == 13) {\r\n        $(\"[name=search_by_user]\").val(1);\r\n        formulize_reload();\r\n    }\r\n});\r\n\r\n$(\".conditionsdelete\").click(function () {\r\n	$(\"[name=conditionsdelete]\").val($(this).attr(\'target\'));\r\n	formulize_reload();\r\n	return false;\r\n});\r\n\r\n$(\"div.permissiongroup > input[type=checkbox]\").click(function () {\r\n	if($(\'input:radio[name=same_diff]:checked\').val() == \"same\") {\r\n		var checked = $(this).attr(\'checked\');\r\n		var checkedclass = \".\"+$(this).attr(\'class\');\r\n		$(checkedclass).attr(\'checked\', checked);\r\n	}\r\n});\r\n\r\nfunction formulize_reload() {\r\n	$(\"[name=reload]\").val(1);\r\n	$(\"#permscrollx\").val($(window).scrollTop());\r\n	$(\".savebutton\").click();\r\n}\r\n\r\n</script>'),
(125,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<form class=\"formulize-admin-form\">\r\n<{$securitytoken}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"form_screens\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.fid}>\">\r\n<input type=\"hidden\" name=\"formulize_admin_aid\" value=\"<{$content.aid}>\">\r\n<input type=\"hidden\" name=\"gotoscreen\" value=\"\">\r\n    <input type=\"hidden\" name=\"cloneformscreen\" value=\"\">\r\n    <input type=\"hidden\" name=\"clonemultiscreen\" value=\"\">\r\n    <input type=\"hidden\" name=\"clonelistscreen\" value=\"\">\r\n    <input type=\"hidden\" name=\"clonetemplatescreen\" value=\"\">\r\n    <input type=\"hidden\" name=\"deletescreen\" value=\"\">\r\n\r\n<div class=\"panel-content content\">\r\n\r\n	<{$smarty.const._AM_SCREEN_EXPLAIN}>\r\n\r\n  <h2><a href=\"ui.php?page=screen&tab=settings&aid=<{$content.aid}>&fid=<{$content.fid}>&sid=new\"><img src=\"../images/filenew2.png\"><{$smarty.const._AM_SCREEN_CREATE}></a></h2>\r\n\r\n  <{* include form if exist screens exist *}>\r\n\r\n  <h2><{$smarty.const._AM_SCREEN_FORMSCREENS}></h2>\r\n  <div class=\"form-item\">\r\n    <table>\r\n	    <tr>\r\n		    <th>Name</th>\r\n			<th>id</th>\r\n		    <th>Default</th>\r\n		    <th>Action</th>\r\n            <th>Relationships</th>\r\n	    </tr>\r\n      <{foreach from=$content.screens item=screen}>\r\n	      <tr>\r\n		      <td><label for=\"screens-defaultform-<{$screen.sid}>\"><a class=\"configscreen\" target-sid=\"<{$screen.sid}>\" href=\"?page=screen&aid=<{$content.aid}>&fid=<{$content.fid}>&sid=<{$screen.sid}>\"><img src=\"../images/kedit.png\"> <{$screen.title}></a></label></td>\r\n					<td><{$screen.sid}></td>\r\n		      <td><div class=\"form-radios\"><input type=\"radio\" id=\"screens-defaultform-<{$screen.sid}>\" name=\"screens-defaultform\" value=\"<{$screen.sid}>\"<{if $screen.sid eq $content.defaultform}> checked=\"checked\"<{/if}>/></div></td>\r\n  		    <td><a href=\"<{$xoops_url}>/modules/formulize/index.php?sid=<{$screen.sid}>\" target=\"_blank\"><img src=\"../images/kfind.png\"> View</a>&nbsp;&nbsp;&nbsp;&nbsp;\r\n                <a target=\"<{$screen.sid}>\" href=\"\" class=\"clonemultiscreen\" />\r\n                <img src=\"../images/clone.gif\">Clone</a>&nbsp;&nbsp;&nbsp;&nbsp;\r\n                <a class=\"deletescreen\" target=\"<{$screen.sid}>\" href=\"\"><img src=\"../images/editdelete.gif\"> Remove</a></td>\r\n            <td>\r\n                <{foreach from=$content.listOfEntries item=loe}>\r\n                    <{if $loe.frid ne $screen.frid}><{assign var=\"flag\" value=true}><{/if}>\r\n                <{/foreach}>\r\n                <{if $flag}><{$smarty.const._AM_SCREEN_RELATIONWARNING}><{/if}>\r\n            </td>\r\n         </tr>\r\n      <{/foreach}>\r\n    </table>\r\n  </div>\r\n\r\n\r\n  <h2><{$smarty.const._AM_SCREEN_LISTSCREENS}></h2>\r\n  <div class=\"form-item\">\r\n    <table>\r\n	    <tr>\r\n		    <th>Name</th>\r\n				<th>id</th>\r\n		    <th>Default</th>\r\n		    <th>Action</th>\r\n	    </tr>\r\n      <{foreach from=$content.listOfEntries item=screen}>\r\n	      <tr>\r\n		      <td><label for=\"screens-defaultlist-<{$screen.sid}>\"><a class=\"configscreen\" target-sid=\"<{$screen.sid}>\" href=\"?page=screen&aid=<{$content.aid}>&fid=<{$content.fid}>&sid=<{$screen.sid}>\"><img src=\"../images/kedit.png\"> <{$screen.title}></a></label></td>\r\n		      <td><{$screen.sid}></td>\r\n		      <td><div class=\"form-radios\"><input type=\"radio\" id=\"screens-defaultlist-<{$screen.sid}>\" name=\"screens-defaultlist\" value=\"<{$screen.sid}>\"<{if $screen.sid eq $content.defaultlist}> checked=\"checked\"<{/if}>/></div></td>\r\n  		    <td><a href=\"<{$xoops_url}>/modules/formulize/index.php?sid=<{$screen.sid}>\" target=\"_blank\"><img src=\"../images/kfind.png\"> View</a>&nbsp;&nbsp;&nbsp;&nbsp;\r\n				<a class=\"clonelistscreen\" target=\"<{$screen.sid}>\" href=\"\"><img src=\"../images/clone.gif\">Clone</a>&nbsp;&nbsp;&nbsp;&nbsp;\r\n				<a class=\"deletescreen\" target=\"<{$screen.sid}>\" href=\"\"><img src=\"../images/editdelete.gif\"> Remove</a></td>\r\n		  </tr>\r\n      <{/foreach}>\r\n    </table>\r\n  </div>\r\n\r\n\r\n    <{if $content.template|is_array AND $content.template|@count != 0}>\r\n    <h2><{$smarty.const._AM_SCREEN_TEMPLATESCREENS}></h2>\r\n    <div class=\"form-item\">\r\n        <table>\r\n            <tr>\r\n                <th>Name</th>\r\n                <th>id</th>\r\n                <th>Action</th>\r\n            </tr>\r\n            <{foreach from=$content.template item=screen}>\r\n            <tr>\r\n                <td><a class=\"configscreen\" target-sid=\"<{$screen.sid}>\" href=\"?page=screen&aid=<{$content.aid}>&fid=<{$content.fid}>&sid=<{$screen.sid}>\"><img src=\"../images/kedit.png\"> <{$screen.title}></a></label></td>\r\n                <td><{$screen.sid}></td>\r\n                <td><a href=\"<{$xoops_url}>/modules/formulize/index.php?sid=<{$screen.sid}>\" target=\"_blank\"><img src=\"../images/kfind.png\"> View</a>&nbsp;&nbsp;&nbsp;&nbsp;\r\n                    <a class=\"clonetemplatescreen\" target=\"<{$screen.sid}>\" href=\"\"><img src=\"../images/clone.gif\">Clone</a>&nbsp;&nbsp;&nbsp;&nbsp;\r\n                    <a class=\"deletescreen\" target=\"<{$screen.sid}>\" href=\"\"><img src=\"../images/editdelete.gif\"> Remove</a></td>\r\n            </tr>\r\n            <{/foreach}>\r\n                \r\n            \r\n                \r\n        </table>\r\n    </div>\r\n    <{/if}>\r\n\r\n    <{if $content.calendar|is_array AND $content.calendar|@count != 0}>\r\n\r\n    <h2><{$smarty.const._AM_SCREEN_CALENDARSCREENS}></h2>\r\n    <div class=\"form-item\">\r\n        <table>\r\n            <tr>\r\n                <th>Name</th>\r\n                <th>id</th>\r\n                <th>Action</th>\r\n            </tr>\r\n            <{foreach from=$content.calendar item=screen}>\r\n            <tr>\r\n                <td><a class=\"configscreen\" target-sid=\"<{$screen.sid}>\" href=\"?page=screen&aid=<{$content.aid}>&fid=<{$content.fid}>&sid=<{$screen.sid}>\"><img src=\"../images/kedit.png\"> <{$screen.title}></a></label></td>\r\n                <td><{$screen.sid}></td>\r\n                <td><a href=\"<{$xoops_url}>/modules/formulize/index.php?sid=<{$screen.sid}>\" target=\"_blank\"><img src=\"../images/kfind.png\"> View</a>&nbsp;&nbsp;&nbsp;&nbsp;\r\n                    <a class=\"clonecalendarscreen\" target=\"<{$screen.sid}>\" href=\"\"><img src=\"../images/clone.gif\">Clone</a>&nbsp;&nbsp;&nbsp;&nbsp;\r\n                    <a class=\"deletescreen\" target=\"<{$screen.sid}>\" href=\"\"><img src=\"../images/editdelete.gif\"> Remove</a></td>\r\n            </tr>\r\n            <{/foreach}>\r\n        </table>\r\n    </div>\r\n    <{/if}>\r\n        \r\n    <{if $content.legacy|is_array AND $content.legacy|@count != 0}>\r\n\r\n    <h2>Legacy Form Screens</h2>\r\n    <p>These screens are the old \"Regular\" single page form screens.<br>\r\n    There is a new version of each of these screens, under \"Form Screens\" above.<br>\r\n    The new versions can have one or more pages instead of being limited to just one.<br><br></p>\r\n    <div class=\"form-item\">\r\n        <table>\r\n            <tr>\r\n                <th>Name</th>\r\n                <th>id</th>\r\n                <th>Default</th>\r\n                <th>Action</th>\r\n            </tr>\r\n            <{foreach from=$content.legacy item=screen}>\r\n            <tr>\r\n                <td><a class=\"configscreen\" target-sid=\"<{$screen.sid}>\" href=\"?page=screen&aid=<{$content.aid}>&fid=<{$content.fid}>&sid=<{$screen.sid}>\"><img src=\"../images/kedit.png\"> <{$screen.title}></a></label></td>\r\n                <td><{$screen.sid}></td>\r\n                <td><div class=\"form-radios\"><input type=\"radio\" name=\"legacydefaultform\" <{if $screen.sid eq $content.defaultform}> checked=\"checked\"<{/if}> disabled /></div></td>\r\n                <td><a href=\"<{$xoops_url}>/modules/formulize/index.php?sid=<{$screen.sid}>\" target=\"_blank\"><img src=\"../images/kfind.png\"> View</a>&nbsp;&nbsp;&nbsp;&nbsp;\r\n                <a class=\"deletescreen\" target=\"<{$screen.sid}>\" href=\"\"><img src=\"../images/editdelete.gif\"> Remove</a></td>\r\n            </tr>\r\n            <{/foreach}>\r\n        </table>\r\n    </div>\r\n    <{/if}>\r\n\r\n</div>\r\n\r\n</form>\r\n\r\n<script type=\"text/javascript\">\r\n<{* Use javascript to save any changes to the settings before following the link *}>\r\n\r\n$(\".cloneformscreen\").click(function () {\r\n    $(\"[name=cloneformscreen]\").val($(this).attr(\'target\'));\r\n    $(\".savebutton\").click();\r\n    return false;\r\n});\r\n\r\n$(\".clonemultiscreen\").click(function () {\r\n    $(\"[name=clonemultiscreen]\").val($(this).attr(\'target\'));\r\n    $(\".savebutton\").click();\r\n    return false;\r\n});\r\n\r\n$(\".clonelistscreen\").click(function () {\r\n	$(\"[name=clonelistscreen]\").val($(this).attr(\'target\'));\r\n	$(\".savebutton\").click();\r\n	return false;\r\n});\r\n\r\n$(\".clonetemplatescreen\").click(function () {\r\n    $(\"[name=clonetemplatescreen]\").val($(this).attr(\'target\'));\r\n    $(\".savebutton\").click();\r\n    return false;\r\n});\r\n\r\n$(\".clonecalendarscreen\").click(function () {\r\n    $(\"[name=clonecalendarscreen]\").val($(this).attr(\'target\'));\r\n    $(\".savebutton\").click();\r\n    return false;\r\n});\r\n\r\n$(\".deletescreen\").click(function() {\r\n	var answer = confirm(\"<{$smarty.const._AM_SCREEN_DELETESCREENS}>\");\r\n	if(answer) {\r\n		$(\"[name=deletescreen]\").val($(this).attr(\'target\'));\r\n		$(\".savebutton\").click();\r\n	}\r\n	return false;\r\n});\r\n</script>'),
(126,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<form class=\"formulize-admin-form\">\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"form_elements\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.fid}>\">\r\n<input type=\"hidden\" name=\"cloneelement\" value=\"\">\r\n<input type=\"hidden\" name=\"deleteelement\" value=\"\">\r\n<input type=\"hidden\" name=\"convertelement\" value=\"\">\r\n<input type=\"hidden\" name=\"reload_elements\" value=\"\">\r\n<input type=\"hidden\" name=\"aid\" value=\"<{$content.aid}>\">	\r\n<input type=\"hidden\" name=\"elementorder\" value=\"\">	\r\n	\r\n<div class=\"accordion-box\">\r\n	<h2><{$smarty.const._AM_ELE_ADDINGTOFORM}></h2>\r\n	<p><{$smarty.const._AM_ELE_CLICKTOADD}></p>\r\n			<p><a href=\"ui.php?page=element&ele_id=new&fid=<{$content.fid}>&aid=<{$content.aid}>&type=text\"><img src=\"../images/filenew2.png\"> <{$smarty.const._AM_ELE_TEXT}></a></p>\r\n			<p><a href=\"ui.php?page=element&ele_id=new&fid=<{$content.fid}>&aid=<{$content.aid}>&type=textarea\"><img src=\"../images/filenew2.png\"> <{$smarty.const._AM_ELE_MLTEXT}></a></p>\r\n			<p><a href=\"ui.php?page=element&ele_id=new&fid=<{$content.fid}>&aid=<{$content.aid}>&type=areamodif\"><img src=\"../images/filenew2.png\"> <{$smarty.const._AM_ELE_MODIF}></a></p>\r\n			<p><a href=\"ui.php?page=element&ele_id=new&fid=<{$content.fid}>&aid=<{$content.aid}>&type=ib\"><img src=\"../images/filenew2.png\"> <{$smarty.const._AM_ELE_MODIF_ONE}></a></p>\r\n			<p><a href=\"ui.php?page=element&ele_id=new&fid=<{$content.fid}>&aid=<{$content.aid}>&type=select\"><img src=\"../images/filenew2.png\"> <{$smarty.const._AM_ELE_SELECTEXPLAIN}></a></p>\r\n			<p><a href=\"ui.php?page=element&ele_id=new&fid=<{$content.fid}>&aid=<{$content.aid}>&type=radio\"><img src=\"../images/filenew2.png\"> <{$smarty.const._AM_ELE_RADIO}></a></p>\r\n			<p><a href=\"ui.php?page=element&ele_id=new&fid=<{$content.fid}>&aid=<{$content.aid}>&type=yn\"><img src=\"../images/filenew2.png\"> <{$smarty.const._AM_ELE_YN}></a></p>\r\n			<p><a href=\"ui.php?page=element&ele_id=new&fid=<{$content.fid}>&aid=<{$content.aid}>&type=date\"><img src=\"../images/filenew2.png\"> <{$smarty.const._AM_ELE_DATEBOX}></a></p>\r\n			<p><a href=\"ui.php?page=element&ele_id=new&fid=<{$content.fid}>&aid=<{$content.aid}>&type=subform\"><img src=\"../images/filenew2.png\"> <{$smarty.const._AM_ELE_SUBFORMEXPLAIN}></a></p>\r\n			<p><a href=\"ui.php?page=element&ele_id=new&fid=<{$content.fid}>&aid=<{$content.aid}>&type=grid\"><img src=\"../images/filenew2.png\"> <{$smarty.const._AM_ELE_GRID}></a></p>\r\n			<p><a href=\"ui.php?page=element&ele_id=new&fid=<{$content.fid}>&aid=<{$content.aid}>&type=derived\"><img src=\"../images/filenew2.png\"> <{$smarty.const._AM_ELE_DERIVED}></a></p>\r\n			<p><a href=\"ui.php?page=element&ele_id=new&fid=<{$content.fid}>&aid=<{$content.aid}>&type=colorpick\"><img src=\"../images/filenew2.png\"> <{$smarty.const._AM_ELE_COLORPICK}></a></p>\r\n			<{foreach item=customElement from=$content.customElements}>\r\n			<p><a href=\"ui.php?page=element&ele_id=new&fid=<{$content.fid}>&aid=<{$content.aid}>&type=<{$customElement.type}>\"><img src=\"../images/filenew2.png\"> <{$customElement.name}></a></p>	\r\n			<{/foreach}>\r\n</div>\r\n\r\n<div class=\"accordion-box-wide\">\r\n<h2><{$smarty.const._AM_ELE_MANAGINGELEFORM}></h2>\r\n<p><{$smarty.const._AM_ELE_CLICKDRAGANDDROP}></p>\r\n		<div id=\"sortable-list\">\r\n<{include file=\"db:admin/ui-accordion.html\" sectionTemplate=\"db:admin/form_elements_sections.html\" sections=$content.elements}>\r\n		</div>\r\n</div>\r\n</form>\r\n<div style=\"clear: both;\"></div>\r\n<script type=\"text/javascript\">\r\n\r\n$(\".clonelink\").click(function () {\r\n	$(\"[name=cloneelement]\").val($(this).attr(\'target\'));\r\n	$(\".savebutton\").click();\r\n	return false;\r\n});\r\n\r\n$(\".deleteelementlink\").click(function () {\r\n	var answer = confirm(\"<{$smarty.const._AM_ELE_CONFIRM_DELETE}>\");\r\n	if(answer) {\r\n		$(\"[name=deleteelement]\").val($(this).attr(\'target\'));\r\n		$(\"[name=reload_elements]\").val(1);\r\n		$(\".savebutton\").click();\r\n	}\r\n	return false;\r\n});\r\n\r\n$(\".converttotextarea\").click(function () {\r\n	var answer = confirm(\"<{$smarty.const._AM_CONVERTTEXT_HELP}>\");\r\n	if(answer) {\r\n		$(\"[name=convertelement]\").val($(this).attr(\'target\'));\r\n		$(\"[name=reload_elements]\").val(1);\r\n		$(\".savebutton\").click();\r\n	}\r\n	return false;\r\n});\r\n\r\n$(\".converttotext\").click(function () {\r\n	var answer = confirm(\"<{$smarty.const._AM_CONVERTTEXTAREA_HELP}>\");\r\n	if(answer) {\r\n		$(\"[name=convertelement]\").val($(this).attr(\'target\'));\r\n		$(\"[name=reload_elements]\").val(1);\r\n		$(\".savebutton\").click();\r\n	}\r\n	return false;\r\n});\r\n$(\".converttocheckbox\").click(function () {\r\n	var answer = confirm(\"<{$smarty.const._AM_CONVERT_RB_CB}>\");\r\n	if(answer) {\r\n		$(\"[name=convertelement]\").val($(this).attr(\'target\'));\r\n		$(\"[name=reload_elements]\").val(1);\r\n		$(\".savebutton\").click();\r\n	}\r\n	return false;\r\n});\r\n\r\n$(\".converttocheckboxfromsb\").click(function () {\r\n	var answer = confirm(\"<{$smarty.const._AM_CONVERT_SB_CB}> WARNING: existing submissions from users will not be preserved!!\");\r\n	if(answer) {\r\n		$(\"[name=convertelement]\").val($(this).attr(\'target\'));\r\n		$(\"[name=reload_elements]\").val(1);\r\n		$(\".savebutton\").click();\r\n	}\r\n	return false;\r\n});\r\n\r\n$(\".converttoradio\").click(function () {\r\n	var answer = confirm(\"<{$smarty.const._AM_CONVERT_CB_RB}>\");\r\n	if(answer) {\r\n		$(\"[name=convertelement]\").val($(this).attr(\'target\'));\r\n		$(\"[name=reload_elements]\").val(1);\r\n		$(\".savebutton\").click();\r\n	}\r\n	return false;\r\n});\r\n\r\n$(\".savebutton\").click(function () {\r\n	$(\"[name=elementorder]\").val($(\"#accordion-2\").sortable(\'serialize\')); \r\n});\r\n\r\n$(\"#accordion-2\").bind( \"sortupdate\", function(event, ui) {\r\n  setDisplay(\'savewarning\',\'block\');\r\n});\r\n\r\n</script>'),
(127,'<div class=\"panel-content content\">\r\n	<p><a href=\"ui.php?page=element&aid=<{$content.aid}>&ele_id=<{$sectionContent.ele_id}>\"><img src=\"../images/kedit.png\"> Configure</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"\" class=\"clonelink\" target=\"<{$sectionContent.ele_id}>\"><img src=\"../images/clone.gif\"> Clone</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"\" class=\"deleteelementlink\" target=\"<{$sectionContent.ele_id}>\"><img src=\"../images/editdelete.gif\"> Delete</a></p>\r\n	\r\n<table>\r\n	<tr>\r\n		<th>Data handle</th>\r\n		<th>Type</th>\r\n		<th>Required</th>\r\n		<th>Display</th>\r\n		<th>Private</th>\r\n	</tr>\r\n	<tr>\r\n		<td><{$sectionContent.ele_handle}></td>\r\n		<td><{$sectionContent.ele_type}></td>\r\n		<td align=\"center\"><input type=\"checkbox\" <{if $sectionContent.ele_req}>checked=\"checked\"<{/if}><{if $sectionContent.ele_req === false}>disabled<{/if}> value=\"1\" name=\"elements-ele_req[<{$sectionContent.ele_id}>]\"></td>\r\n		<td align=\"center\"><{if is_numeric($sectionContent.ele_display)}><input type=\"checkbox\" <{if $sectionContent.ele_display}>checked=\"checked\"<{/if}> value=\"1\" name=\"elements-ele_display[<{$sectionContent.ele_id}>]\"><{else}><{$sectionContent.ele_display}><input type=\"hidden\" name=\"customDisplayFlag[<{$sectionContent.ele_id}>]\" value=1></input><{/if}></td>\r\n		<td align=\"center\"><input type=\"checkbox\" <{if $sectionContent.ele_private}>checked=\"checked\"<{/if}>  value=\"1\" name=\"elements-ele_private[<{$sectionContent.ele_id}>]\"></td>\r\n	</tr>\r\n</table>\r\n<{if $sectionContent.converttext}>\r\n	<p><a href=\"\" class=\"convertto<{$sectionContent.linktype}>\" target=\"<{$sectionContent.ele_id}>\"><{$sectionContent.converttext}></a></p>\r\n<{/if}>\r\n\r\n</div>'),
(128,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<form class=\"formulize-admin-form\">\r\n<{$securitytoken}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"form_advanced_calculations\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.fid}>\">\r\n<input type=\"hidden\" name=\"formulize_admin_aid\" value=\"<{$content.aid}>\">\r\n<input type=\"hidden\" name=\"gotoadvanced_calculations\" value=\"\">\r\n<input type=\"hidden\" name=\"deleteadvanced_calculations\" value=\"\">\r\n<input type=\"hidden\" name=\"cloneadvanced_calculations\" value=\"\">\r\n\r\n<div class=\"panel-content content\">\r\n\r\n    <h2>Before Saving</h2>\r\n    <p>This special procedure runs before an entry is saved. Values are available as PHP variables using the element handle name. The ID of the current entry (or \'new\') is available as $entry_id. The current value in the database (prior to this save) is available as $currentValues[\'handle_name\'], so you can check if something has actually changed.</p><br />\r\n    <textarea id=\"forms-on_before_save\" name=\"forms-on_before_save\" class=\"code-textarea\"><{$content.form_object->on_before_save}>\r\n</textarea><{* closing tag on a new line so the textarea has a blank line at the bottom *}>\r\n\r\n    <h2>After Saving</h2>\r\n    <p>This special procedure runs after an entry is saved. The ID of the saved entry is available as $entry_id. The value that was in the database, prior to this save, is available as $currentValues[\'handle_name\'], so you can check if something has actually changed.</p><br />\r\n    <textarea id=\"forms-on_after_save\" name=\"forms-on_after_save\" class=\"code-textarea\"><{$content.form_object->on_after_save}>\r\n</textarea><{* closing tag on a new line so the textarea has a blank line at the bottom *}>\r\n\r\n    <h2>Enable / Disable Entry Editing</h2>\r\n    <p>This special procedure allows for a custom condition to be set to decide whether a user can edit an entry or not. Variables will be available in the code: $entry_id can be used to identify an entry, $user_id for identifying the logged in user, $form_id to indicate the form, and $allow_editing (true or false) to determine if the user can make changes or not. Do not include a return value after the code: the value of $allow_editing will be returned. </p><br />\r\n    <textarea id=\"forms-custom_edit_check\" name=\"forms-custom_edit_check\" class=\"code-textarea\"><{$content.form_object->custom_edit_check}>\r\n</textarea><{* closing tag on a new line so the textarea has a blank line at the bottom *}>\r\n\r\n    <p><i>Procedures</i> <{$smarty.const._AM_CALC_EXPLAIN}></p>\r\n\r\n    <h2><a name=\"newprocedure\" href=\"ui.php?page=advanced-calculation&tab=settings&aid=<{$content.aid}>&fid=<{$content.fid}>&acid=new\"><img src=\"../images/filenew2.png\"> <{$smarty.const._AM_CALC_CREATE_NEW}></a></h2>\r\n\r\n  <{* include form if exist advanced_calculations exist *}>\r\n\r\n  <h2>Procedures</h2>\r\n  <div class=\"form-item\">\r\n    <table>\r\n	    <tr>\r\n		    <th><{$smarty.const._AM_ITEMNAME}></th>\r\n		    <th>Action</th>\r\n	    </tr>\r\n      <{foreach from=$content.advanced_calculations item=advanced_calculation}>\r\n	      <tr>\r\n		      <td><label for=\"advanced-calculation-defaultform-<{$advanced_calculation.acid}>\"><a class=\"configadvanced_calculation\" target=\"<{$advanced_calculation.acid}>\" href=\"\"><img src=\"../images/kedit.png\"> <{$advanced_calculation.name}></a></label></td>\r\n  		    <td><a class=\"cloneadvanced_calculation\" target=\"<{$advanced_calculation.acid}>\" href=\"\"><img src=\"../images/clone.gif\"><{$smarty.const._AM_CALC_CLONE}></a>&nbsp;&nbsp;&nbsp;&nbsp;<a class=\"deleteadvanced_calculation\" target=\"<{$advanced_calculation.acid}>\" href=\"\"><img src=\"../images/editdelete.gif\"><{$smarty.const._AM_CALC_REMOVE}></a></td>\r\n	      </tr>\r\n      <{/foreach}>\r\n    </table>\r\n  </div>\r\n</div>\r\n\r\n</form>\r\n\r\n<script type=\"text/javascript\">\r\n$(\".configadvanced_calculation\").click(function() {\r\n	$(\"[name=gotoadvanced_calculations]\").val($(this).attr(\'target\'));\r\n	$(\".savebutton\").click();\r\n	return false;\r\n})\r\n\r\n$(\"[name=newprocedure]\").click(function() {\r\n	$(\"[name=gotoadvanced_calculations]\").val(\'new\');\r\n	$(\".savebutton\").click();\r\n	return false;\r\n})\r\n\r\n$(\".deleteadvanced_calculation\").click(function() {\r\n	var answer = confirm(\"<{$smarty.const._AM_CALC_CONFIRM_DELETE}>\");\r\n	if(answer) {\r\n		$(\"[name=deleteadvanced_calculations]\").val($(this).attr(\'target\'));\r\n		$(\".savebutton\").click();\r\n	}\r\n	return false;\r\n})\r\n\r\n$(\".cloneadvanced_calculation\").click(function() {\r\n	$(\"[name=cloneadvanced_calculations]\").val($(this).attr(\'target\'));\r\n	$(\".savebutton\").click();\r\n	return false;\r\n})\r\n\r\njQuery(document).ready(function() {\r\n    jQuery(\".savebutton\").click(function() {\r\n        fz_check_php_code(jQuery(\"#forms-on_before_save\").val(), \"Before Save\", \"<{$icms_url}>\", <{$icms_userid}>);\r\n        fz_check_php_code(jQuery(\"#forms-on_after_save\").val(), \"After Save\", \"<{$icms_url}>\", <{$icms_userid}>);\r\n        fz_check_php_code(jQuery(\"#forms-custom_edit_check\").val(), \"Disable Entry Editing\", \"<{$icms_url}>\", <{$icms_userid}>);\r\n    });\r\n});\r\n</script>'),
(129,'<{if $content.aid == 0}>\r\n<div class=\"description\"><{$smarty.const._AM_FORM_CREATE_EXPLAIN}></div><br>\r\n<{/if}>\r\n\r\n<div class=\"panel-content content\">\r\n<h2><a href=\"ui.php?page=relationship&tab=settings&aid=<{$content.aid}>&fid=<{$content.fid}>&sid=<{$content.sid}>&frid=new\"><img src=\"../images/filenew2.png\"><{$smarty.const._AM_APP_RELATIONSHIPS_CREATE}></a></h2>\r\n</div>\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"application_relationships\">\r\n<input type=\"hidden\" name=\"deleteframework\" value=\"\">\r\n<{include file=\"db:admin/ui-accordion.html\" sectionTemplate=\"db:admin/application_relationships_sections.html\" sections=$content.relationships}>\r\n</form>\r\n\r\n<script type=\"text/javascript\">\r\n  $(\".deleterelationship\").click( function() {\r\n    var answer = confirm(\"<{$smarty.const._AM_APP_RELATIONSHIPS_DELETE_CONFIRM}>\");\r\n    if(answer) {\r\n      $(\"[name=deleteframework]\").val($(this).attr(\'target\'));\r\n      $(\".savebutton\").click();\r\n    }\r\n    return false;\r\n  });\r\n  \r\n</script>'),
(130,'<div>\r\n<p><a href=\"ui.php?page=relationship&frid=<{$sectionContent.frid}>&aid=<{$content.aid}>&fid=<{$content.fid}>&sid=<{$content.sid}>\"><img src=\"../images/kedit.png\"> Configure this relationship</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a class=\"deleterelationship\" target=\"<{$sectionContent.frid}>\" href=\"\"><img src=\"../images/editdelete.gif\">&nbsp;&nbsp;Delete this relationship</a></p>\r\n</div>\r\n<div class=\"panel-content content\">\r\n	<p><b>Links between forms in this relationship:</b></p>\r\n  <ul>\r\n	  <{foreach from=$sectionContent.links key=linkNumber item=link}>\r\n      <li><{$link.form1}> + <{$link.form2}> - <{$link.relationship}></li>\r\n  	<{/foreach}>\r\n  </ul>\r\n</div>'),
(131,'<script type=\'text/javascript\'>\r\njQuery(document).ready(function() {\r\n    jQuery(\"#dialog-common-values\").dialog({ autoOpen: false, modal: true, width: 700, height: 200 });\r\n});\r\n\r\n$.ajaxSetup({\r\n    cache: false  \r\n});\r\n\r\nfunction checkForCommon(Obj, form1, form2, lid) {\r\n    for (var i=0; i < Obj.options.length; i++) {\r\n        if(Obj.options[i].selected && Obj.options[i].value == \'common\') {\r\n            jQuery(\"#dialog-common-values\").dialog(\'open\');\r\n            jQuery(\"#dialog-common-values-content\").load(\'<{$smarty.const.XOOPS_URL}>/modules/formulize/admin/relationship_common_values.php?form1=\' + form1 + \'&form2=\' + form2 + \'&lid=\' + lid);\r\n        }\r\n    }\r\n}\r\n</script>\r\n\r\n<div id=\"dialog-common-values\" title=\"<{$smarty.const._AM_FRAME_WHICH_ELEMENTS}>\" style=\'display:none\'>\r\n    <div id=\"dialog-common-values-content\"></div>\r\n</div>\r\n\r\n<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<form id=\"form-admin-form\" class=\"formulize-admin-form\">\r\n<{php}>print $GLOBALS[\'xoopsSecurity\']->getTokenHTML()<{/php}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"relationship_settings\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.frid}>\">\r\n<input type=\"hidden\" name=\"formulize_admin_op\" value=\"\">\r\n<input type=\"hidden\" name=\"formulize_admin_lid\" value=\"\">\r\n<input type=\"hidden\" name=\"aid\" value=\"<{$content.aid}>\">  \r\n<input type=\"hidden\" name=\"fid\" value=\"<{$content.fid}>\">\r\n<input type=\"hidden\" name=\"sid\" value=\"<{$content.sid}>\">\r\n\r\n<div class=\"pane-content content\">\r\n    <{if $content.relationship}>\r\n        <h2><{$smarty.const._AM_FRAME_NAME}></h2>\r\n        <input type=\"text\" name=\"relationships-name\" size=\"50\" maxlength=\"255\" value=\"<{$content.relationship->name}>\" />\r\n    <{/if}>\r\n\r\n    <h2><{$smarty.const._AM_FRAME_ADDFORM}></h2>\r\n    <{if $content.required_form}>\r\n        <input type=\"hidden\" name=relationships-fid1 value=\"<{$content.required_form.value}>\">\r\n        <text><{$content.required_form.name}></text>\r\n        </select>\r\n    <{else}>\r\n        <select name=relationships-fid1 size=1>\r\n            <{foreach from=$content.formoptions key=linkNumber item=formoption}>\r\n                <option value=\"<{$formoption.value}>\"><{$formoption.name}></option>\r\n            <{/foreach}>\r\n        </select>\r\n    <{/if}>\r\n    <select name=relationships-fid2 size=1>\r\n        <{foreach from=$content.formoptions key=formoptionNumber item=formoption}>\r\n            <option value=\"<{$formoption.value}>\"><{$formoption.name}></option>\r\n        <{/foreach}>\r\n    </select>\r\n    <input type=submit class=formbutton name=addlink value=\'<{$smarty.const._AM_FRAME_NEWFORMBUTTON}>\'>\r\n\r\n\r\n    <{if count((array) $content.relationship->links) lt 1}>\r\n        <h2><{$smarty.const._AM_FRAME_NOFORMS}></h2>\r\n    <{else}>\r\n        <input type=hidden name=relationships-common1choice value=\"\">\r\n        <input type=hidden name=relationships-common2choice value=\"\">\r\n        <input type=hidden name=relationships-common_fl_id value=\"\">\r\n        <h2><{$smarty.const._AM_FRAME_FORMSIN}></h2>\r\n        <table>\r\n            <tr>\r\n                <th></th>\r\n                <th><{$smarty.const._AM_FRAME_AVAILFORMS1}></th>\r\n                <th><{$smarty.const._AM_FRAME_AVAILFORMS2}></th>\r\n                <th><{$smarty.const._AM_FRAME_RELATIONSHIP}></th>\r\n                <th><{$smarty.const._AM_FRAME_LINKAGE}></th>\r\n                <th>Options</th>\r\n            </tr>\r\n            <{foreach from=$content.relationship->links key=linkNumber item=link}>\r\n                <tr class=\"<{cycle values=\'even,odd\'}>\">\r\n                    <td><a href=\"\" class=\"deletethislink\" target=\"<{$link->lid}>\"><img src=\"../images/editdelete.gif\"></a></td>\r\n                    <td><{$link->main_form->title}></td>\r\n                    <td><{$link->linked_form->title}></td>\r\n                    <td>\r\n                        <select name=relationships-rel<{$link->lid}> size=1>\r\n                            <option value=\"1\"<{if $link->relationship eq 1}> selected=\"selected\"<{/if}>><{$smarty.const._AM_FRAME_ONETOONE}></option>\r\n                            <option value=\"2\"<{if $link->relationship eq 2}> selected=\"selected\"<{/if}>><{$smarty.const._AM_FRAME_ONETOMANY}></option>\r\n                            <option value=\"3\"<{if $link->relationship eq 3}> selected=\"selected\"<{/if}>><{$smarty.const._AM_FRAME_MANYTOONE}></option>\r\n                        </select>\r\n                    </td>\r\n                    <td>\r\n                        <select name=relationships-linkages<{$link->lid}> id=linkages<{$link->lid}> size=1 onchange=\"javascript:checkForCommon(this.form.linkages<{$link->lid}>, \'<{$link->main_form->id_form}>\', \'<{$link->linked_form->id_form}>\', \'<{$link->lid}>\');\">\r\n                            <option value=\'0+0\'<{if $link->key1 eq 0 and $link->key2 eq 0}> selected=\"selected\"<{/if}>><{$smarty.const._AM_FRAME_UIDLINK}></option>\r\n                            <{if $link->common neq 1}>\r\n                                <option value=\'common\'><{$smarty.const._AM_FRAME_COMMONLINK}></option>\r\n                            <{/if}>\r\n                            <{foreach from=$link->link_options key=linkoptionNumber item=linkoption}>\r\n                                <option value=\"<{$linkoption.value}>\"<{if $linkoption.value == $link->link_selected}> selected=\"selected\"<{/if}>><{$linkoption.name}></option>\r\n                            <{/foreach}>\r\n                        </select>\r\n                        <{if $link->common eq 1}>\r\n                            <input type=\"hidden\" name=\"relationships-preservecommon<{$link->lid}>\" value=\"<{$link->key1}>+<{$link->key2}>\"></input>\r\n                        <{/if}>\r\n                    </td>\r\n                    <td style=\"white-space:nowrap;\">\r\n                        <input type=\"checkbox\"<{if $link->unifiedDisplay}> checked=\"checked\"<{/if}> value=\"1\" name=\"relationships-display<{$link->lid}>\" id=\"relationships-display<{$link->lid}>\"><label for=\"relationships-display<{$link->lid}>\">Display as a single form</label>\r\n                        <input type=\"checkbox\"<{if $link->unified_delete}> checked=\"checked\"<{/if}> value=\"1\" name=\"relationships-delete<{$link->lid}>\" id=\"relationships-delete<{$link->lid}>\"><label for=\"relationships-delete<{$link->lid}>\">Delete linked entries</label>\r\n                    </td>\r\n                </tr>\r\n            <{/foreach}>\r\n        </table>\r\n    <{/if}>\r\n</div>\r\n\r\n</form>\r\n\r\n\r\n<script type=\'text/javascript\'>\r\njQuery(\"[name=addlink]\").click(function () {\r\n    jQuery(\"[name=formulize_admin_op]\").val(\'addlink\');\r\n    jQuery(\".savebutton\").click();\r\n    return false;\r\n});\r\n\r\njQuery(\".deletethislink\").click(function () {\r\n    if (confirm(\'<{$smarty.const._AM_CONFIRM_DEL_FF_FORM}>\')) {\r\n        jQuery(\"[name=formulize_admin_op]\").val(\'dellink\');\r\n        jQuery(\"[name=formulize_admin_lid]\").val(jQuery(this).attr(\'target\'));\r\n        jQuery(\".savebutton\").click();\r\n    }\r\n    return false;\r\n});\r\n</script>'),
(132,'<script type=\'text/javascript\'>\r\nvar lid=<{$content.lid}>;\r\n\r\n$(\"[name=submitx]\").click(function () {\r\n  var form1choice = $(\"#form1choice\").find(\':selected\');\r\n  var form2choice = $(\"#form2choice\").find(\':selected\');\r\n\r\n  var sourceLink = $(\"#linkages\"+lid).find(\':selected\');\r\n  \r\n  sourceLink.val( form1choice.val() + \'+\' + form2choice.val() + \'+common\' );\r\n  sourceLink.text( \'<{$smarty.const._AM_FRAME_COMMON_VALUES}>\' + form1choice.text() + \' & \' + form2choice.text() );\r\n\r\n  $(\"#dialog-common-values\").dialog(\'close\');\r\n\r\n  return false;\r\n});\r\n\r\n// If saveLock is turned on, do not display save button to user, instead display \"READ ONLY\"\r\n$( document ).ready(function() {\r\n    <{if $content.isSaveLocked}>\r\n        document.getElementById(\'submit\').style.visibility = \'hidden\';\r\n        document.getElementById(\'submittd\').innerHTML = \"READ ONLY\";\r\n    <{/if}>\r\n    \r\n});\r\n\r\n</script>\r\n\r\n<div class=\"panel-content content\">\r\n  <table align=\"center\" width=\"80%\">\r\n    <tr>\r\n      <th>\r\n        <{$content.form1.name}>\r\n      </th>\r\n      <th>\r\n        <{$content.form2.name}>\r\n      </th>\r\n      <th>\r\n        Action\r\n      </th>\r\n    </tr>\r\n    <tr>\r\n      <td class=even>\r\n        <select id=form1choice name=form1choice size=1>\r\n          <{foreach from=$content.form1.elements key=elementNumber item=element}>\r\n            <option value=\"<{$elementNumber}>\"><{$element}></option>\r\n          <{/foreach}>\r\n        </select>\r\n      </td>\r\n      <td class=even>\r\n        <select id=form2choice name=form2choice size=1>\r\n          <{foreach from=$content.form2.elements key=elementNumber item=element}>\r\n            <option value=\"<{$elementNumber}>\"><{$element}></option>\r\n          <{/foreach}>\r\n        </select>\r\n      </td>\r\n      <td class=head id=submittd>\r\n        <input type=submit class=formbutton id=submit name=submitx value=\'<{$smarty.const._SUBMIT}>\'>\r\n      </td>\r\n    </tr>\r\n  </table>\r\n</div>'),
(133,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<form id=\"form-admin-form\" class=\"formulize-admin-form\">\r\n<{$securitytoken}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"screen_settings\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.sid}>\">\r\n<input type=\"hidden\" name=\"formulize_admin_fid\" value=\"<{$content.fid}>\">\r\n<input type=\"hidden\" name=\"aid\" value=\"<{$content.aid}>\">\r\n<input type=\"hidden\" id=\"delete_passcode\" name=\"delete_passcode\" value=\"\">\r\n<input type=\"hidden\" id=\"make_new_passcode\" name=\"make_new_passcode\" value=\"\">\r\n<input type=\"hidden\" id=\"add_existing_passcode\" name=\"add_existing_passcode\" value=\"\">\r\n    \r\n\r\n\r\n<div class=\"panel-content content\">\r\n  <fieldset>\r\n    <legend>Settings for the Screen: <em><{$content.title}></em></legend>\r\n\r\n	  <div class=\"form-item\">\r\n		  <label for=\"screens-title\">Name of the screen:</label>\r\n		  <input type=\"text\" id=\"screens-title\" name=\"screens-title\" value=\"<{$content.title}>\"/>\r\n	  </div>\r\n	  <div class=\"form-item\">\r\n		  <label for=\"screens-type\">What kind of screen is this:</label>\r\n			\r\n	    <select id=\"screens-type\" size=\"1\" name=\"screens-type\"<{if $content.sid neq \'new\'}> disabled<{/if}>>\r\n		    <option value=\"listOfEntries\"<{if $content.type eq \'listOfEntries\'}> selected=\"selected\"<{/if}>>List</option>\r\n		    <option value=\"multiPage\"<{if $content.type eq \'multiPage\'}> selected=\"selected\"<{/if}>>Form</option>\r\n          <option value=\"template\"<{if $content.type eq \'template\'}> selected=\"selected\"<{/if}>>Template</option>\r\n          <option value=\"calendar\"<{if $content.type eq \'calendar\'}> selected=\"selected\"<{/if}>>Calendar</option>\r\n	    </select>\r\n			<{if $content.sid neq \'new\'}>\r\n				<input type=\"hidden\" name=\"screens-type\" value=\"<{$content.type}>\">\r\n			<{/if}>\r\n	  </div>\r\n  </fieldset>\r\n\r\n	<fieldset>\r\n		<legend>URL for this screen</legend>\r\n		<p>To give people access to this screen, send them to this URL:</p>\r\n		<blockquote><{$xoops_url}>/modules/formulize/index.php?sid=<{$content.sid}></blockquote>\r\n	</fieldset>\r\n	\r\n	<fieldset>\r\n		<legend>PHP code for including this screen anywhere</legend>\r\n		<p>You can embed this screen in any PHP application or web page that is running on the same web server.<br />Use this snippet of PHP code to include it:</p>\r\n		<blockquote class=\"code\">include_once \"<{$smarty.const.XOOPS_ROOT_PATH}>/mainfile.php\";<br/>\r\n$formulize_screen_id = <{$content.sid}>;<br/>\r\ninclude \"<{$smarty.const.XOOPS_ROOT_PATH}>/modules/formulize/index.php\";</blockquote>\r\n	</fieldset>\r\n\r\n  <!--<fieldset>\r\n	  <legend>Menu</legend>\r\n\r\n    <div class=\"form-item\">\r\n	    <label for=\"imenu\"><input type=\"radio\" id=\"imenu\" name=\"menu\" value=\"imenu\"/>Use iMenu</label>\r\n	    <label for=\"internal\"><input type=\"radio\" id=\"internal\" name=\"menu\" value=\"internal\"/>Use internal menu</label>\r\n	    <div class=\"description\">Choose whether you will use XOOPS iMenu for assigning menu items or the internal menu.</div>\r\n	  </div>\r\n  </fieldset>-->\r\n\r\n  <fieldset>\r\n	  <legend>Should this screen use an anti-CSRF security token</legend>\r\n\r\n	  <div class=\"form-item\">\r\n	    <label for=\"screens-useToken-yes\"><input type=\"radio\" id=\"screens-useToken-yes\" name=\"screens-useToken\" value=\"1\"<{if $content.useToken eq 1}> checked<{/if}>/>Yes</label>\r\n	    <label for=\"screens-useToken-no\"><input type=\"radio\" id=\"screens-useToken-no\" name=\"screens-useToken\" value=\"0\"<{if $content.useToken eq 0}> checked<{/if}>/>No</label>\r\n	    <div class=\"description\">The security token is a defense against cross-site request forgery attacks. However, it can cause problems if you are using an advanced Ajax-based UI in a List of Entries screen, and possibly other screen types.</div>\r\n	  </div>\r\n  </fieldset>\r\n\r\n  <fieldset>\r\n	  <legend>Do Anonymous Users need a passcode to access this screen?</legend>\r\n\r\n	  <div class=\"form-item\">\r\n        <label for=\"screens-anonNeedsPasscode-0\"><input type=\"radio\" id=\"screens-anonNeedsPasscode-0\" name=\"screens-anonNeedsPasscode\" value=\"0\"<{if $content.anonNeedsPasscode eq 0}> checked<{/if}>/>No, only permission to view the form</label><br />\r\n	    <label for=\"screens-anonNeedsPasscode-1\"><input type=\"radio\" id=\"screens-anonNeedsPasscode-1\" name=\"screens-anonNeedsPasscode\" value=\"1\"<{if $content.anonNeedsPasscode eq 1}> checked<{/if}>/>Yes, plus permission to view the form</label>\r\n	    <div class=\"description\">Passcodes are saved with any data a user enters into a form, and are used as a filter when viewing data. This allows anonymous users to interact with only certain data, and if passcodes are given out per-user, then having a passcode is like having a \'throwaway\' account.</div>\r\n        <br />\r\n        <fieldset id=\'codelist\' <{if $content.anonNeedsPasscode eq 0}>style=\'display: none;\'<{/if}>>\r\n        <legend>Valid Codes</legend>\r\n        <table id=\'validcodes\'>\r\n        <{foreach from=$content.passcodes item=passcode}>\r\n            <tr><td><{$passcode.passcode}></td><td>&mdash;</td>\r\n                <td><{$passcode.notes}></td><td>&mdash;</td>\r\n                <td><input type=\"radio\" name=\"passcode_status_<{$passcode.id}>\" value=1>Active<br />\r\n                <input type=\"radio\" name=\"passcode_status_<{$passcode.id}>\" value=0>Expired</td><td>&mdash;</td>\r\n                <td>Auto-expire on: <input type=\"text\" value=\"<{$passcode.expiry}>\" name=\"passcode_expiry_<{$passcode.id}>\" size=10 class=\"passcode_expiry\" /></td><td>&mdash;</td>\r\n                <td><a href=\'\' class=\'delete_passcode\' passcodeId=\'<{$passcode.id}>\'>delete</a></td></tr>    \r\n        <{/foreach}>\r\n        </table>\r\n        <p>Add a passcode from another screen: <select name=\"existing_passcode\" size=1>\r\n            <{foreach from=$content.existingPasscodes item=pc}>\r\n                <option value=<{$pc.id}>><{$pc.passcode}> &mdash; <{$pc.notes}></option>\r\n            <{/foreach}>\r\n        </select> <input type=\"button\" name=\"add_existing_passcode_button\" id=\"add_existing_passcode_button\" value=\"Add\" /></p>\r\n        <p>Add a new passcode: <input type=\"text\" name=\"new_passcode\" size=25 value=\"<{$content.newPasscode}>\" /> <input type=\"text\" name=\"new_notes\" size=40 value=\"\" placeholder=\"Optional Note\" /> <input type=\"button\" name=\"make_new_passcode_button\" id=\"make_new_passcode_button\" value=\"Add\" /></p>\r\n        </fieldset>\r\n	  </div>\r\n  </fieldset>\r\n\r\n\r\n  <!--<fieldset>\r\n	  <legend>Sample PHP Code</legend>\r\n\r\n	  <div class=\"form-item\">\r\n		  <textarea name=\"code\"/></textarea>\r\n		  <input type=\"submit\" class=\"copybutton\" name=\"copy-code\" value=\"Copy code\"/>\r\n		  <div class=\"description\">Copy this sample PHP code somewhere.</div>\r\n	  </div>\r\n  </fieldset>-->\r\n</div>\r\n\r\n</form>\r\n\r\n<script>\r\n    $(\'.delete_passcode\').click(function() {\r\n       $(\'#delete_passcode\').val($(this).attr(\'passcodeId\'));\r\n       $(\".savebutton\").click();\r\n       return false;\r\n    });\r\n    $(\'#add_existing_passcode_button\').click(function() {\r\n        $(\'#add_existing_passcode\').val(1); \r\n        $(\".savebutton\").click();\r\n    });\r\n    $(\'#make_new_passcode_button\').click(function() {\r\n        $(\'#make_new_passcode\').val(1); \r\n        $(\".savebutton\").click();\r\n    });\r\n    $(\'input[name=\"screens-anonNeedsPasscode\"]\').click(function() {\r\n       if($(this).val() == 1 && $(\'#codelist\').css(\'display\') == \'none\') {\r\n            $(\'#codelist\').show();\r\n       } else if($(this).val() == 0 && $(\'#codelist\').css(\'display\') != \'none\') {\r\n            $(\'#codelist\').hide();\r\n       }\r\n    });\r\n    \r\n    \r\n</script>\r\n\r\n<style>\r\n    #validcodes {\r\n        border: none;\r\n        width: auto;\r\n        margin-left: 2em;\r\n    }\r\n    #validcodes td {\r\n        vertical-align: middle;\r\n    }\r\n</style>'),
(134,'<{if $content.aid == 0}>\r\n<div class=\"description\"><{$smarty.const._AM_FORM_CREATE_EXPLAIN}></div><br>\r\n<{/if}>\r\n\r\n<div class=\"panel-content content\">\r\n  <h2><a href=\"ui.php?page=relationship&tab=settings&aid=<{$content.aid}>&fid=<{$content.fid}>&sid=<{$content.sid}>&frid=new\"><img src=\"../images/filenew2.png\"><{$smarty.const._AM_APP_RELATIONSHIPS_CREATE}></a></h2>\r\n\r\n</div>\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n  <input type=\"hidden\" name=\"formulize_admin_handler\" value=\"screen_relationships\">\r\n  <input type=\"hidden\" name=\"deleteframework\" value=\"\">\r\n  <input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.sid}>\">\r\n  <input type=\"hidden\" name=\"formulize_admin_fid\" value=\"<{$content.fid}>\">\r\n  <input type=\"hidden\" name=\"aid\" value=\"<{$content.aid}>\">\r\n  <input type=\"hidden\" name=\"screens-type\" value=\"<{$content.type}>\">\r\n  <fieldset>\r\n    <legend>Screen Relationship Settings</legend>\r\n    <label for=\"screens-frid\">Is this screen based on the form alone, or on a relationship with another form?</label>\r\n    <select id=\"screens-frid\" name=\"screens-frid\">\r\n      <option value=\"0\"<{if $content.frid eq 0}> selected=\"selected\"<{/if}>>The form alone, no relationship</option>\r\n      <{foreach from=$content.relationships key=linkNumber item=relationship}>\r\n        <option value=\"<{$relationship.content.frid}>\" \r\n                <{if $content.frid eq $relationship.content.frid}>\r\n                  selected=\"selected\"\r\n                <{/if}>>\r\n          <{$relationship.name}>\r\n        </option>\r\n      <{/foreach}>\r\n    </select>\r\n  </fieldset>\r\n  <fieldset>\r\n    <legend>Relationships Based on this Form</legend>\r\n    <{include file=\"db:admin/ui-accordion.html\" \r\n              sectionTemplate=\"db:admin/application_relationships_sections.html\" \r\n              sections=$content.relationships}>\r\n  </fieldset>\r\n</form>\r\n<script type=\"text/javascript\">\r\n  $(\".deleterelationship\").click( function() {\r\n    var answer = confirm(\"<{$smarty.const._AM_APP_RELATIONSHIPS_DELETE_CONFIRM}>\");\r\n    if(answer) {\r\n      $(\"[name=deleteframework]\").val($(this).attr(\'target\'));\r\n      $(\".savebutton\").click();\r\n    }\r\n    return false;\r\n  });\r\n  \r\n</script>'),
(135,'<div class=\"panel-content content\">\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"element_names\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.ele_id}>\">\r\n<input type=\"hidden\" name=\"formulize_form_id\" value=\"<{$content.fid}>\">\r\n<input type=\"hidden\" name=\"aid\" value=\"<{$content.aid}>\">\r\n<input type=\"hidden\" name=\"element_type\" value=\"<{$content.type}>\">\r\n<input type=\"hidden\" name=\"reload_names_page\" value=\"\">\r\n\r\n\r\n	<div class=\"form-item required\">\r\n   	<fieldset>\r\n      <legend><label for=\"elements-ele_caption\" class=\"question\">Caption</label></legend>\r\n			<input type=\"text\" name=\"elements-ele_caption\" class=\"required_formulize_element\" value=\"<{$content.ele_caption}>\" size=70 onkeyup=\"fillHandle()\">\r\n			<div class=\"description\">\r\n				<p>The text that users see when this element appears on a form.</p>\r\n			</div>\r\n		</fieldset>\r\n	</div>\r\n\r\n	<div class=\"form-item\">\r\n   	<fieldset>\r\n      <legend><label for=\"elements-ele_colhead\" class=\"question\">Column Heading</label></legend>\r\n			<input type=\"text\" name=\"elements-ele_colhead\" value=\"<{$content.ele_colhead}>\" size=70>\r\n			<div class=\"description\">\r\n				<p>Optional. The text that users see when this element appears at the top of a column in a list.</p>\r\n			</div>\r\n		</fieldset>\r\n	</div>\r\n\r\n	<div class=\"form-item\">\r\n   	<fieldset>\r\n      <legend><label for=\"elements-ele_desc\" class=\"question\">Help text</label></legend>\r\n			<textarea name=\"elements-ele_desc\" rows=4 cols=70><{$content.ele_desc}></textarea>\r\n			<div class=\"description\">\r\n				<p>Optional. Descriptive or explanatory text that accompanies the element.</p>\r\n			</div>\r\n		</fieldset>\r\n	</div>\r\n\r\n	<div class=\"form-item\">\r\n   	<fieldset>\r\n      <legend><label for=\"elements-ele_handle\" class=\"question\">Data handle</label></legend>\r\n			<input type=\"text\" name=\"elements-ele_handle\" value=\"<{$content.ele_handle}>\" size=70>\r\n			<div class=\"description\">\r\n				<p>Optional. The name used to refer to this element in programming code and in the database.</p>\r\n			</div>\r\n		</fieldset>\r\n	</div>\r\n\r\n\r\n<div class=\"accordion-box\">\r\n	<div class=\"form-item\">\r\n   	<fieldset>\r\n      <legend><label for=\"orderpref\" class=\"question\">Position of this element</label></legend>\r\n			<select name=\"orderpref\" size=1>\r\n				<option value=\"bottom\">At the end of the form</option>\r\n				<option value=\"top\"<{$content.firstelementorder}>>At the beginning of the form</option>\r\n				<{html_options options=$content.orderoptions selected=$content.defaultorder}>\r\n			</select>\r\n		</fieldset>\r\n	</div>\r\n</div>\r\n<div style=\"clear:both;\"></div>\r\n<{if $content.ele_req_on}>\r\n<div class=\"accordion-box\" >\r\n	<div class=\"form-item\">\r\n   	<fieldset>\r\n      <legend><label for=\"elements-ele_req\" class=\"question\">Make this element \"required\" so users must give a response:</label></legend>\r\n			<p><input type=\"radio\" name=\"elements-ele_req\" value=1<{$content.ele_req_yes_on}>> Yes</p>\r\n			<p><input type=\"radio\" name=\"elements-ele_req\" value=0<{$content.ele_req_no_on}>> No</p>\r\n		</fieldset>\r\n	</div>\r\n</div>\r\n<{/if}>\r\n\r\n</form>\r\n<div style=\"clear: both;\"></div>\r\n</div> <!--// end content -->\r\n\r\n<script type=\"text/javascript\">\r\n$(\".savebutton\").click(function() {\r\n	if($(\"[name=elements-ele_caption]\").val() == \"\") {\r\n		alert(\"Elements must have a caption!\");\r\n		$(\"[name=elements-ele_caption]\").focus();\r\n	}\r\n	<{* reload after ele_handle is blank, since it will be reset to the ele_id and that\'s a state change we\'ll need to reload to show the user *}>\r\n	if($(\"[name=elements-ele_handle]\").val() == \"\") {\r\n		fillHandle();\r\n		$(\"[name=reload_names_page]\").val(1);\r\n	}\r\n});\r\n\r\n$(\"[name=elements-ele_handle]\").keydown(function () {\r\n	$(\"[name=check_handle]\").val(1);\r\n});\r\n\r\nfunction fillHandle(){\r\n	//this function will be called when they are typing title to update handle\r\n	if (\"<{$content.ele_id}>\" == \"new\") {\r\n		var str=\"<{$content.formhandle}>\"+\"_\"+$(\"[name=elements-ele_caption]\").val();\r\n		str=str.toLowerCase().replace(new RegExp(\"[^a-z0-9]\",\"gm\"),\"_\");\r\n		str=str.replace(new RegExp(\"_{2,}\",\"gm\"),\"_\").substring(0,40);\r\n		$(\"[name=elements-ele_handle]\").val(str);\r\n	}\r\n}\r\n\r\n\r\n$(\"[name=elements-ele_caption]\").keydown(function () {\r\n	$(\"[name=reload_names_page]\").val(1);\r\n});\r\n\r\n\r\n\r\n\r\n</script>'),
(136,'<div class=\"panel-content content\">\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"element_options\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.ele_id}>\">\r\n<input type=\"hidden\" name=\"reload_option_page\" value=\"\">\r\n<input type=\"hidden\" class=\"optionsconditionsdelete\" name=\"optionsconditionsdelete\" value=\"\">\r\n<input type=\"hidden\" class=\"optionsLimitByElementFilterDelete\" name=\"optionsLimitByElementFilterDelete\" value=\"\">\r\n\r\n\r\n<{include file=$content.typetemplate}>\r\n\r\n</form>\r\n\r\n</div> <!--// end content -->'),
(137,'<!-- start checkboxTree configuration -->\r\n<script type=\"text/javascript\" src=\"<{$xoops_url}>/modules/formulize/libraries/jquery/checkboxtree/jquery.checkboxtree.js\"></script>\r\n<link rel=\"stylesheet\" type=\"text/css\" href=\"<{$xoops_url}>/modules/formulize/libraries/jquery/checkboxtree/jquery.checkboxtree.css\"/>\r\n<!-- end checkboxTree configuration -->\r\n\r\n<div class=\"panel-content content\">\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"element_display\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.ele_id}>\">\r\n<input type=\"hidden\" name=\"reload_element_pages\" value=\"\">\r\n<input type=\"hidden\" class=\"conditionsdelete\" name=\"conditionsdelete\" value=\"\">\r\n\r\n<!-- initialize checkboxTree plugin -->\r\n<script type=\"text/javascript\">\r\n$(document).ready(function() {\r\n    $(\'#multi-screen-tree\').checkboxTree({\r\n        onCheck: {\r\n            ancestors: \'checkIfFull\',\r\n            descendants: \'check\'\r\n        },\r\n        onUncheck: {\r\n            ancestors: \'uncheck\'\r\n        },\r\n        initializeChecked: \'expanded\',\r\n        initializeUnchecked: \'collapsed\'\r\n    });\r\n});\r\n</script>       \r\n\r\n<div class=\"accordion-box\">\r\n	<div class=\"form-item\">\r\n   	<fieldset>\r\n      <legend><label for=\"elements_ele_display\" class=\"question\">Which groups should see this element?</label></legend>\r\n			<select name=\"elements_ele_display[]\" size=8 multiple>\r\n				<option value=\"all\"<{$content.ele_display.all}>>All groups with permission to view the form</option>\r\n				<option value=\"none\"<{$content.ele_display.none}>>No groups</option>\r\n				<{foreach from=$content.groups item=group}>\r\n				<{assign var=groupid value=$group.id}>\r\n        <option value=<{$groupid}><{$content.ele_display.$groupid}>><{$group.name}></option>\r\n        <{/foreach}>\r\n			</select>\r\n		</fieldset>\r\n	</div>\r\n</div>\r\n\r\n<div class=\"accordion-box\">\r\n	<div class=\"form-item\">\r\n   	<fieldset>\r\n      <legend><label for=\"elements_ele_disabled\" class=\"question\">Disable this element for any groups?</label></legend>\r\n			<select name=\"elements_ele_disabled[]\" size=8 multiple>\r\n				<option value=\"none\"<{$content.ele_disabled.none}>>Disable for no groups</option>\r\n				<option value=\"all\"<{$content.ele_disabled.all}>>Disable for all groups</option>\r\n				<{foreach from=$content.groups item=group}>\r\n				<{assign var=groupid value=$group.id}>\r\n        <option value=<{$groupid}><{$content.ele_disabled.$groupid}>><{$group.name}></option>\r\n        <{/foreach}>\r\n			</select>\r\n			<div class=\"description\">\r\n				<p>Users will see this element if they are a member of any group for which the element is not disabled.  Only users who have all their groups selected here, will see the element as disabled.</p>\r\n			</div>\r\n		</fieldset>\r\n	</div>\r\n</div>\r\n\r\n<div class=\"accordion-box\">\r\n    <div class=\"form-item\">\r\n    <fieldset>\r\n	<legend>Form Screens to display this element on</legend>\r\n    <ul id=\"multi-screen-tree\">\r\n		<label for=\"multi_page_screens\">Select the pages where the element will appear</label>\r\n		<{foreach from=$content.multi_form_screens item=form_screen}>\r\n			<{assign var=formscreenid value=$form_screen.sid}>\r\n			<{assign var=formscreenpages value=$form_screen.pages}>\r\n			<li><input type=\"checkbox\" name=\"multi_page_screens[]\" value=all><label><{$form_screen.title}></label>\r\n			<{foreach from=$formscreenpages key=k item=screen_page}>\r\n				<{if in_array($content.ele_id, $screen_page)}>\r\n				 <ul>\r\n	                <li><input type=\"checkbox\" name=\"multi_page_screens[]\" value=<{$formscreenid|cat:\'-\'|cat:$k}> checked><label><{$form_screen.pagetitles[$k]}></label>\r\n	            </ul>\r\n	            <{else}>\r\n	            <ul>\r\n	                <li><input type=\"checkbox\" name=\"multi_page_screens[]\" value=<{$formscreenid|cat:\'-\'|cat:$k}>><label><{$form_screen.pagetitles[$k]}></label>\r\n	            </ul>\r\n	            <{/if}>\r\n			<{/foreach}>\r\n        <{/foreach}>         \r\n	</ul>\r\n    </fieldset>\r\n    </div>\r\n</div>\r\n\r\n<{if $content.form_screens|is_array AND $content.form_screens|@count AND $content.ele_form_screens|is_array AND $content.ele_form_screens|@count}>\r\n<div class=\"accordion-box\">\r\n    <div class=\"form-item\">\r\n    <fieldset>\r\n	<legend>Legacy Form Screens to display on</legend>\r\n	<div class=\"form-item\">\r\n		<label for=\"elements_form_screens\">On which legacy form screens should this element appear?</label>\r\n		<select id=\"elements_form_screens\" name=\"elements_form_screens[]\" size=\"8\" multiple>\r\n		<{foreach from=$content.form_screens item=form_screen}>\r\n		<{assign var=formscreenid value=$form_screen.sid}>\r\n        <option value=<{$formscreenid}><{$content.ele_form_screens.$formscreenid}>><{$form_screen.title}></option>\r\n        <{/foreach}>\r\n		</select>\r\n    </div>\r\n    </fieldset>\r\n    </div>\r\n</div>\r\n<{/if}>\r\n\r\n<div class=\"accordion-box-wide\">\r\n	<div class=\"form-item\">\r\n   	<fieldset>\r\n      <legend><label for=\"elements_ele_filtersettings\" class=\"question\">Only display the element in the form if the entry being edited meets these conditions:</label></legend>\r\n			<div id=\"displayfilter\">\r\n			<{$content.filtersettings}>\r\n			</div>\r\n			<div class=\"description\">\r\n				<p>When an entry is saved, these conditions will be checked and this element will be displayed or not displayed on the next page load.  This feature is most useful on multipage form screens, which save the entry between pages.</p>\r\n			</div>\r\n		</fieldset>\r\n	</div>\r\n</div>\r\n\r\n<div class=\"accordion-box\">\r\n	<div class=\"form-item\">\r\n   	<fieldset>\r\n      <legend><label class=\"question\">Additional display options:</label></legend>\r\n			<input type=\"checkbox\" name=\"elements-ele_private\" value=\"1\"<{$content.ele_private}>> Only display this element to users who have permission to \"view private elements\".\r\n			<div class=\"description\">\r\n				This can be useful for hiding information like phone numbers and other personal details from most other users.\r\n			</div><br />\r\n            <input type=\"checkbox\" name=\"elements-ele_forcehidden\" value=\"1\"<{$content.ele_forcehidden}>> DEPRECATED - use default values for the element instead - If this element is not displayed to a user, still save its value when the form is submitted.\r\n			<div class=\"description\">\r\n				This can be useful for setting default values in a form, for elements the user should not interact with.\r\n			</div>\r\n		</fieldset>\r\n	</div>\r\n</div>\r\n\r\n</form>\r\n\r\n<div style=\"clear: both;\"></div>\r\n</div> <!--// end content -->\r\n\r\n<script type=\'text/javascript\'>\r\n\r\n$(\"div#displayfilter > input#addcon\").click(function () {\r\n	$(\"[name=reload_element_pages]\").val(1);\r\n  $(\".savebutton\").click();\r\n	return false;\r\n});\r\n\r\n$(\"div#displayfilter > a.conditionsdelete\").click(function () {\r\n	$(\".conditionsdelete\").val($(this).attr(\'target\'));\r\n	$(\"[name=reload_element_pages]\").val(1);\r\n  $(\".savebutton\").click();\r\n	return false;\r\n});\r\n\r\n</script>'),
(138,'<div class=\"panel-content content\">\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"element_advanced\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.ele_id}>\">\r\n<input type=\"hidden\" name=\"original_handle\" value=\"<{$content.original_handle}>\">\r\n<input type=\"hidden\" name=\"original_index_name\" value=\"<{$content.original_index_name}>\">\r\n<input type=\"hidden\" name=\"original_ele_index\" value=\"<{$content.original_ele_index}>\">\r\n\r\n<{if $content.datatypeui}>\r\n<div class=\"accordion-box-wide\">\r\n<div class=\"form-item\">\r\n		<fieldset>\r\n			<legend><label class=\"question\">How should the data for this element be stored in the database?</label></legend>\r\n			<{$content.datatypeui}>\r\n			<div class=\"description\">\r\n				<p>Elements that will contain only numbers should use a numeric type, so that sorting and calculations work correctly.  When in doubt, leave this value on its default setting.</p>\r\n			</div>\r\n		</fieldset>\r\n</div>\r\n</div>\r\n<{/if}>\r\n\r\n<{if $content.ele_encrypt_show}>  \r\n<div class=\"accordion-box\">\r\n<div class=\"form-item\">\r\n		<fieldset>\r\n			<legend><label for=\"elements-ele_encrypt\" class=\"question\">Encrypt this information in the database?</label></legend>\r\n      <p><input type=\"radio\" name=\"elements-ele_encrypt\" value=1<{$content.ele_encrypt_yes_on}>> Yes</p>\r\n			<p><input type=\"radio\" name=\"elements-ele_encrypt\" value=0<{$content.ele_encrypt_no_on}>> No</p>\r\n      <div class=\"description\">\r\n				<p>To prevent anyone who has access to the database from reading the information users submit, you can encrypt the information before it is stored in the database.  Most features of Formulize work with encrypted information, but some unusual operations, like grouping calculation results by an encrypted field, don\'t work.</p>\r\n        <p><b>Encryption is dependent on the current database password; if you change the database password, all your encrypted information will be inaccessible!</b></p>\r\n			</div>\r\n  </fieldset>\r\n</div>\r\n</div>\r\n<{/if}>\r\n<{if $content.ele_index_show}>  \r\n<div class=\"accordion-box\">\r\n<div class=\"form-item\">\r\n		<fieldset>\r\n			<legend><label for=\"elements-ele_index\" class=\"question\">Index this information in the database?</label></legend>\r\n      <p><input type=\"radio\" name=\"elements-ele_index\" value=1<{$content.ele_index_yes_on}>> Yes</p>\r\n			<p><input type=\"radio\" name=\"elements-ele_index\" value=0<{$content.ele_index_no_on}>> No</p>\r\n      <div class=\"description\">\r\n				<p>An index can help speed up searchs but can impact performance in other areas.</p>\r\n        <p><b>Do not add to all elements as it will create unnecessary overhead!</b></p>\r\n			</div>\r\n  </fieldset>\r\n</div>\r\n</div>\r\n<{/if}>\r\n \r\n<{if $content.ele_req_on}>		\r\n<div class=\"accordion-box\">\r\n	<div class=\"form-item\">\r\n   	<fieldset>\r\n      <legend><label for=\"elements-ele_req\" class=\"question\"><{$smarty.const._AM_FORMULIZE_USE_DEFAULT_WHEN_BLANK}></label></legend>\r\n			<p><input type=\"radio\" id=\"elements-ele_use_default_when_blank0\" name=\"elements-ele_use_default_when_blank\" value=0> <{$smarty.const._AM_FORMULIZE_USE_DEFAULT_WHEN_BLANK_ONLY_NEW}></p>\r\n			<p><input type=\"radio\" id=\"elements-ele_use_default_when_blank1\" name=\"elements-ele_use_default_when_blank\" value=1> <{$smarty.const._AM_FORMULIZE_USE_DEFAULT_WHEN_BLANK_ALL_WHEN_BLANK}></p>\r\n			<div class=description>\r\n				<p><{$smarty.const._AM_FORMULIZE_USE_DEFAULT_WHEN_BLANK_DESC}></p>\r\n			</div>\r\n		</fieldset>\r\n	</div>\r\n</div>\r\n<{/if}>\r\n \r\n<{if $content.hasMultipleOptions}>\r\n<div class=\"accordion-box\">\r\n	<div class=\"form-item\">\r\n   	<fieldset>\r\n      <legend><label for=\"elements-ele_req\" class=\"question\"><{$smarty.const._AM_FORMULIZE_EXPLODE_COLUMNS_ON_EXPORT}></label></legend>\r\n			<p><nobr><input type=\"radio\" id=\"exportoptions_onoff0\" name=\"exportoptions_onoff\" value=0> <{$smarty.const._AM_FORMULIZE_EXPLODE_COLUMNS_ON_EXPORT_OFF}></nobr></p>\r\n            <hr>\r\n			<p><input type=\"radio\" id=\"exportoptions_onoff1\" name=\"exportoptions_onoff\" value=1> <{$smarty.const._AM_FORMULIZE_EXPLODE_COLUMNS_ON_EXPORT_ON}></p>\r\n            <p><{$smarty.const._AM_FORMULIZE_EXPORTOPTIONS_HASVALUE}><input type=\'text\' id=\'exportoptions_hasvalue\' name=\'exportoptions_hasvalue\' value=\'<{$content.exportoptions_hasvalue}>\' size=10><br>\r\n            <{$smarty.const._AM_FORMULIZE_EXPORTOPTIONS_DOESNOTEHAVEVALUE}><input type=\'text\' id=\'exportoptions_doesnothavevalue\' name=\'exportoptions_doesnothavevalue\' value=\'<{$content.exportoptions_doesnothavevalue}>\' size=10></p>\r\n		</fieldset>\r\n	</div>\r\n</div>    \r\n<{/if}>\r\n \r\n</form>\r\n\r\n<div style=\"clear: both;\"></div>\r\n</div> <!--// end content -->\r\n\r\n<script type=\"text/javascript\">\r\n		$(\"#elements-ele_use_default_when_blank<{$content.ele_use_default_when_blank}>\").attr(\'checked\', \'checked\');\r\n        $(\"#exportoptions_onoff<{$content.exportoptions_onoff}>\").attr(\'checked\', \'checked\');\r\n</script>'),
(139,'<div class=\"panel-content content\">\r\n\r\n	<{include file=\"db:admin/element_linkedoptionlist.html\"}>\r\n\r\n    <{include file=\"db:admin/element_options_delimiter_choice.html\"}>\r\n    \r\n       <div class=\"form-item\">\r\n    <fieldset>\r\n		<legend>If the options are linked -- or are {FULLNAMES} or {USERNAMES}</legend>\r\n		<div class=\"form-item\">\r\n		<fieldset>\r\n			<legend>Limit them to values from the groups selected here</legend>\r\n			<select id=\"element-formlink_scope\" name=\"element_formlink_scope[]\" size=\"10\" multiple>\r\n			<{html_options options=$content.formlink_scope_options selected=$content.formlink_scope}>\r\n			</select>\r\n            <br/><br/>\r\n            <fieldset>\r\n                <div class=\"form-radios\">\r\n                    <label for=\"elements-ele_value[checkbox_scopelimit]-0\"><input type=\"radio\" id=\"elements-ele_value[checkbox_scopelimit]-0\" name=\"elements-ele_value[checkbox_scopelimit]\" value=\"0\"<{if $content.ele_value.checkbox_scopelimit eq 0}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_FORMLINK_SCOPELIMIT_NO}></label>\r\n                </div>\r\n                <div class=\"form-radios\"><label for=\"elements-ele_value[checkbox_scopelimit]-1\"><input type=\"radio\" id=\"elements-ele_value[checkbox_scopelimit]-1\" name=\"elements-ele_value[checkbox_scopelimit]\" value=\"1\"<{if $content.ele_value.checkbox_scopelimit eq 1}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_FORMLINK_SCOPELIMIT_YES}></label></div>\r\n            </fieldset>\r\n            <fieldset>\r\n                <div class=\"form-radios\">\r\n                    <label for=\"elements-ele_value[checkbox_formlink_anyorall]-0\"><input type=\"radio\" id=\"elements-ele_value[checkbox_formlink_anyorall]-0\" name=\"elements-ele_value[checkbox_formlink_anyorall]\" value=\"0\"<{if $content.ele_value.checkbox_formlink_anyorall eq 0}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_FORMLINK_ANYALL_ANY}></label>\r\n                </div>\r\n                <div class=\"form-radios\">\r\n                    <label for=\"elements-ele_value[checkbox_formlink_anyorall]-1\"><input type=\"radio\" id=\"elements-ele_value[checkbox_formlink_anyorall]-1\" name=\"elements-ele_value[checkbox_formlink_anyorall]\" value=\"1\"<{if $content.ele_value.checkbox_formlink_anyorall eq 1}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_FORMLINK_ANYALL_ALL}></label>\r\n                </div>\r\n            </fieldset>\r\n            <div class=\"description\">\r\n                <{$smarty.const._AM_ELE_FORMLINK_SCOPE_DESC}>\r\n            </div>\r\n        </fieldset>\r\n	</fieldset>\r\n</div>  \r\n    \r\n  <{include file=\"db:admin/element_linkedfilter.html\"}>\r\n  \r\n  <{include file=\"db:admin/element_linkedsortoptions.html\"}>\r\n    \r\n  <{include file=\"db:admin/alternate_fields_for_linked_elements.html\"}>\r\n  \r\n</div>\r\n\r\n<script>\r\n$(\"#snapshot-<{$content.ele_value.snapshot}>\").attr(\'checked\',1);\r\n    \r\n    $(\'input[name=\"linked_yesno\"]\').change(function() {\r\n        if($(\'input[name=\"linked_yesno\"]:checked\').val() == 1) {\r\n            $(\'#snapshot-values\').show(200);\r\n        } else {\r\n            $(\'#snapshot-values\').hide(200);\r\n        }\r\n    });\r\n</script>'),
(140,'<div class=\"panel-content content\">\r\n    <fieldset>\r\n        <legend>Default Date</legend>\r\n        <div class=\"description\">\r\n            <p>Leave blank for no default date. Use {TODAY} for the current date, or {TODAY+7} for one week in the future, {TODAY-14} for two weeks in the past, etc.</p>\r\n        </div>\r\n        <div class=\"form-item\">\r\n            <input type=\"text\" id=\"element-date\" name=\"elements-ele_value[0]\" value=\"<{$content.ele_value[0]}>\" size=\"15\" />\r\n        </div>\r\n    </fieldset>\r\n    <fieldset>\r\n        <legend>Date Validation (optional)</legend>\r\n        <div class=\"form-item\">\r\n            <p><label>The minimum/earliest date the user can choose is: </label> <input type=\"text\" id=\"date_past_days\" name=\"elements-ele_value[date_past_days]\" value=\"<{$content.ele_value.date_past_days}>\" size=\"15\" /> </p>\r\n            <p><label>The maximum/latest date the user can choose is: </label> <input type=\"text\" id=\"date_future_days\" name=\"elements-ele_value[date_future_days]\" value=\"<{$content.ele_value.date_future_days}>\" size=\"15\" /> </p>\r\n        </div>\r\n  </fieldset>\r\n	<div class=\"description\">\r\n		<p>You can use literal dates: 2021-05-09 or dates relative to the current date: {TODAY+7} or references to another element in this form: {element_handle} <br>If you use a reference to another element, that element must contain a valid literal date.</p>\r\n	</div>\r\n</div>\r\n\r\n<script type=\'text/javascript\'>\r\njQuery(function() {\r\n    jQuery(\'#element-date\').datepicker({dateFormat: \'yy-mm-dd\', constrainInput: false});\r\n});\r\n\r\n$(\'#validate-past-dates\').click(function() {\r\n    if (this.checked && $(\'#date_past_days\').val() == \'\') {\r\n        //we have checked the box, and the value is empty.\r\n        $(\'#date_past_days\').val(\'0\'); // focus on the input box.\r\n        $(\'#date_past_days\').focus();\r\n        $(\'#date_past_days\').select();\r\n\r\n    }\r\n});\r\n\r\n$(\'#validate-future-dates\').click(function() {\r\n    if (this.checked && $(\'#date_future_days\').val() == \'\') {\r\n        //we have checked the box, and the value is empty.\r\n        $(\'#date_future_days\').val(\'0\'); // focus on the input box.\r\n        $(\'#date_future_days\').focus();\r\n        $(\'#date_future_days\').select();\r\n    }\r\n});\r\n</script>'),
(141,'<div class=\"panel-content content\">\r\n\r\n    <div class=\"form-item\">\r\n	    <fieldset>\r\n		    <legend><{$smarty.const._AM_ELE_DERIVED_CAP}></legend>\r\n	      <textarea id=\"elements-ele_value\" name=\"elements-ele_value[0]\" class=\"code-textarea\" rows=\"5\" cols=\"35\"><{$content.ele_value[0]}></textarea>\r\n	      <div class=\"description\">\r\n          <p><{$content.listofelementsoptions}> <input type=button name=addele value=\"<{$smarty.const._AM_ELE_DERIVED_ADD}>\"></p>\r\n          <p><{$smarty.const._AM_ELE_DERIVED_DESC}></p>\r\n        </div>\r\n	    </fieldset>\r\n    </div>\r\n\r\n    <div class=\"form-item\">\r\n        <fieldset>\r\n            <legend><{$smarty.const._AM_ELE_DERIVED_UPDATE_CAP}></legend>\r\n            <p>\r\n                <span name=\"updateder_controls\">\r\n                    <input type=button name=updateder value=\"<{$smarty.const._AM_ELE_DERIVED_UPDATE}>\"> <{$content.listofrelationshipoptions}>\r\n                </span>\r\n                <span name=\"updateder_Info\" style=\"display:none\">\r\n                    Updating Values... <img src=\"../../../images/await.gif\" width=\"20\" height=\"20\"/>\r\n                </span>\r\n            </p>\r\n            <div class=\"description\">\r\n                <p><{$smarty.const._AM_ELE_DERIVED_UPDATE_DESC}></p>\r\n            </div>\r\n        </fieldset>\r\n    </div>\r\n\r\n    <div class=\"form-item\">\r\n	    <fieldset>\r\n		    <legend><{$smarty.const._AM_ELE_DERIVED_NUMBER_OPTS}></legend>\r\n		    <div class=\"form-item\">\r\n			    <label for=\"elements-ele_value[1]\"><{$smarty.const._AM_ELE_NUMBER_OPTS_DEC}></label><input type=\"text\" id=\"elements-ele_value[1]\" name=\"elements-ele_value[1]\" value=\"<{$content.ele_value.1}>\" size=\"2\" maxlength=\"2\"/>\r\n		    </div>\r\n		    <div class=\"form-item\">\r\n			    <label for=\"elements-ele_value[2]\"><{$smarty.const._AM_ELE_NUMBER_OPTS_PREFIX}></label><input type=\"text\" id=\"elements-ele_value[2]\" name=\"elements-ele_value[2]\" value=\"<{$content.ele_value.2}>\" size=\"5\" maxlength=\"255\"/>\r\n		    </div>\r\n		    <div class=\"form-item\">\r\n			    <label for=\"elements-ele_value[5]\"><{$smarty.const._AM_ELE_NUMBER_OPTS_SUFFIX}></label><input type=\"text\" id=\"elements-ele_value[5]\" name=\"elements-ele_value[5]\" value=\"<{$content.ele_value.5}>\" size=\"5\" maxlength=\"255\"/>\r\n		    </div>\r\n		    <div class=\"form-item\">\r\n			    <label for=\"elements-ele_value[3]\"><{$smarty.const._AM_ELE_NUMBER_OPTS_DECSEP}></label><input type=\"text\" id=\"elements-ele_value[3]\" name=\"elements-ele_value[3]\" value=\"<{$content.ele_value.3}>\" size=\"5\" maxlength=\"255\"/>\r\n		    </div>\r\n		    <div class=\"form-item\">\r\n			    <label for=\"elements-ele_value[4]\"><{$smarty.const._AM_ELE_NUMBER_OPTS_SEP}></label><input type=\"text\" id=\"elements-ele_value[4]\" name=\"elements-ele_value[4]\" value=\"<{$content.ele_value.4}>\" size=\"5\" maxlength=\"255\"/>\r\n		    </div>\r\n		    \r\n		    <div class=\"description\">\r\n			    <{$smarty.const._AM_ELE_NUMBER_OPTS_DESC}>\r\n		    </div>\r\n	    </fieldset>\r\n    </div>\r\n  \r\n</div>\r\n\r\n\r\n<script type=\"text/javascript\">\r\n// http://plugins.jquery.com/project/caret-range\r\n/*\r\n * jQuery Caret Range plugin\r\n * Copyright (c) 2009 Matt Zabriskie\r\n * Released under the MIT and GPL licenses.\r\n */\r\n(function($) {\r\n	$.extend($.fn, {\r\n		caret: function (start, end) {\r\n			var elem = this[0];\r\n\r\n			if (elem) {							\r\n				// get caret range\r\n				if (typeof start == \"undefined\") {\r\n					if (elem.selectionStart) {\r\n						start = elem.selectionStart;\r\n						end = elem.selectionEnd;\r\n					}\r\n					else if (document.selection) {\r\n						var val = this.val();\r\n						var range = document.selection.createRange().duplicate();\r\n						range.moveEnd(\"character\", val.length)\r\n						start = (range.text == \"\" ? val.length : val.lastIndexOf(range.text));\r\n\r\n						range = document.selection.createRange().duplicate();\r\n						range.moveStart(\"character\", -val.length);\r\n						end = range.text.length;\r\n					}\r\n				}\r\n				// set caret range\r\n				else {\r\n					var val = this.val();\r\n\r\n					if (typeof start != \"number\") start = -1;\r\n					if (typeof end != \"number\") end = -1;\r\n					if (start < 0) start = 0;\r\n					if (end > val.length) end = val.length;\r\n					if (end < start) end = start;\r\n					if (start > end) start = end;\r\n\r\n					elem.focus();\r\n\r\n					if (elem.selectionStart) {\r\n						elem.selectionStart = start;\r\n						elem.selectionEnd = end;\r\n					}\r\n					else if (document.selection) {\r\n						var range = elem.createTextRange();\r\n						range.collapse(true);\r\n						range.moveStart(\"character\", start);\r\n						range.moveEnd(\"character\", end - start);\r\n						range.select();\r\n					}\r\n				}\r\n\r\n				return {start:start, end:end};\r\n			}\r\n		}\r\n	});\r\n})(jQuery);\r\n\r\n\r\n$(\"[name=addele]\").click(function () {\r\n  //$(\"#elements-ele_value[0]\").val( $(\"#elements-ele_value[0]\").val() + \'\"\' + $(\"#listofelementsoptions\").find(\':selected\').val() + \'\"\' );\r\n\r\n  var input = $(\"#elements-ele_value\");\r\n  var range = input.caret();\r\n  var value = input.val();\r\n  var text = \'\"\' + $(\"#listofelementsoptions\").find(\':selected\').val() + \'\"\';\r\n  input.val(value.substr(0, range.start) + text + value.substr(range.end, value.length));\r\n  input.caret(range.start + text.length);\r\n  return false;\r\n});\r\n\r\n  var formID = \"\";\r\n  var formRelationID = \"1\";\r\n\r\n$(\"[name=updateder]\").click(function () {\r\n    formID = $(\"[name=formulize_form_id]\").val();\r\n    formRelationID = $(\"#listofrelationshipoptions\").find(\':selected\').val()\r\n    var limitStart = 0;\r\n    $(\"[name=updateder_controls]\").hide();\r\n    $(\"[name=updateder_Info]\").show();\r\n    updateDerivedValues(formID,formRelationID,limitStart);\r\n    return false;\r\n});\r\n\r\nfunction updateDerivedValues(fid,frid,limitStart){\r\n    $.get(\"<{$xoops_url}>/modules/formulize/formulize_xhr_responder.php?uid=<{$content.uid}>&op=update_derived_value&fid=\"+fid+\"&frid=\"+frid+\"&limitstart=\"+limitStart, function(data){\r\n            if(!isNaN(data) && data > 0){\r\n                limitStart += parseInt(data);\r\n                updateDerivedValues(fid,frid,limitStart);\r\n            }else{\r\n                $(\"[name=updateder_Info]\").hide();\r\n                $(\"[name=updateder_controls]\").show();\r\n                if (isNaN(data)) {\r\n                    alert(data);   \r\n                } else {\r\n                $(\"#derivedfinished\").show();\r\n                }\r\n            }\r\n        });\r\n    }\r\n\r\n    function removeOnChange(){\r\n        $(\"#listofrelationshipoptions\").unbind(\"change\");\r\n        $(\"#listofelementsoptions\").unbind(\"change\");\r\n    }\r\n\r\n    window.setTimeout(\"removeOnChange();\",500);\r\n</script>'),
(142,'<div class=\"panel-content content\">\r\n\r\n    <div class=\"form-item\">\r\n	    <fieldset>\r\n	    <legend><{$smarty.const._AM_ELE_GRID_HEADING}></legend>\r\n		    <div class=\"form-radios\">\r\n			    <label for=\"caption\"><input type=\"radio\" id=\"caption\" name=\"elements-ele_value[0]\" value=\"caption\"/><{$smarty.const._AM_ELE_GRID_HEADING_USE_CAPTION}></label>\r\n		    </div>\r\n		    <div class=\"form-radios\">\r\n			    <label for=\"form\"><input type=\"radio\" id=\"form\" name=\"elements-ele_value[0]\" value=\"form\"/><{$smarty.const._AM_ELE_GRID_HEADING_USE_FORM}></label>\r\n		    </div>\r\n		    <div class=\"form-radios\">\r\n			    <label for=\"none\"><input type=\"radio\" id=\"none\" name=\"elements-ele_value[0]\" value=\"none\"/><{$smarty.const._AM_ELE_GRID_HEADING_NONE}></label>\r\n		    </div>\r\n	    </fieldset>\r\n    </div>\r\n    <div class=\"form-item\">\r\n	    <fieldset>\r\n	    <legend><{$smarty.const._AM_ELE_GRID_HEADING_SIDEORTOP}></legend>\r\n		    <div class=\"form-radios\">\r\n			    <label for=\"side\"><input type=\"radio\" id=\"side\" name=\"elements-ele_value[5]\" value=\"1\"/><{$smarty.const._AM_ELE_GRID_HEADING_SIDE}></label>\r\n		    </div>\r\n		    <div class=\"form-radios\">\r\n			    <label for=\"above\"><input type=\"radio\" id=\"above\" name=\"elements-ele_value[5]\" value=\"\"/><{$smarty.const._AM_ELE_GRID_HEADING_TOP}></label>\r\n		    </div>\r\n	    </fieldset>\r\n    </div>\r\n    <div class=\"form-item required\">\r\n	    <label for=\"element-row\"><{$smarty.const._AM_ELE_GRID_ROW_CAPTIONS}><em>*</em></label>\r\n	    <textarea id=\"element-row\" name=\"elements-ele_value[1]\" rows=\"5\" cols=\"50\"><{$content.ele_value[1]}></textarea>\r\n	    <div class=\"description\">\r\n		    <{$smarty.const._AM_ELE_GRID_ROW_CAPTIONS_DESC}>\r\n	    </div>\r\n    </div>\r\n    <div class=\"form-item required\">\r\n	    <label for=\"element-col\"><{$smarty.const._AM_ELE_GRID_COL_CAPTIONS}><em>*</em></label>\r\n	    <textarea id=\"element-col\" name=\"elements-ele_value[2]\" rows=\"5\" cols=\"50\"><{$content.ele_value[2]}></textarea>\r\n	    <div class=\"description\">\r\n		    <{$smarty.const._AM_ELE_GRID_COL_CAPTIONS_DESC}>\r\n	    </div>\r\n    </div>\r\n    <div class=\"form-item\">\r\n	    <fieldset>\r\n	    <legend><{$smarty.const._AM_ELE_GRID_BACKGROUND}></legend>\r\n		    <div class=\"form-radios\">\r\n			    <label for=\"horizontal\"><input type=\"radio\" id=\"horizontal\" name=\"elements-ele_value[3]\" value=\"horizontal\"/><{$smarty.const._AM_ELE_GRID_BACKGROUND_HOR}></label>\r\n		    </div>\r\n		    <div class=\"form-radios\">\r\n			    <label for=\"vertical\"><input type=\"radio\" id=\"vertical\" name=\"elements-ele_value[3]\" value=\"vertical\"/><{$smarty.const._AM_ELE_GRID_BACKGROUND_VER}></label>\r\n		    </div>\r\n	    </fieldset>\r\n    </div>\r\n    <div class=\"form-item\">\r\n	    <label for=\"element-grid_start_option\"><{$smarty.const._AM_ELE_GRID_START}></label>\r\n	    <select id=\"element-grid_start_option\" name=\"elements-ele_value[4]\" size=\"1\">\r\n        <{html_options options=$content.grid_start_options selected=$content.ele_value[4]}>\r\n	    </select>\r\n	    <div class=\"description\">\r\n		    <{$smarty.const._AM_ELE_GRID_START_DESC}>\r\n	    </div>\r\n    </div>\r\n  \r\n</div>\r\n\r\n<script type=\"text/javascript\">\r\n\r\n$(\"#<{$content.background}>\").attr(\'checked\',1);\r\n$(\"#<{$content.sideortop}>\").attr(\'checked\',1);\r\n$(\"#<{$content.heading}>\").attr(\'checked\',1);\r\n\r\n</script>'),
(143,'<div class=\"panel-content content\">\r\n    <div class=\"form-item\">\r\n	    <label for=\"elements-ele_value[0]\"><{$smarty.const._AM_ELE_LEFTRIGHT_TEXT}></label>\r\n	    <textarea id=\"elements-ele_value[0]\" name=\"elements-ele_value[0]\" rows=\"5\" cols=\"35\"><{$content.ele_value[0]}></textarea>\r\n	    <div class=\"description\">\r\n          <p><{$smarty.const._AM_ELE_LEFTRIGHT_DESC}></p>\r\n      </div>\r\n    </div>\r\n</div>'),
(144,'<div class=\"panel-content content\">\r\n  <fieldset>\r\n    <legend><{$smarty.const._AM_ELE_MODIF_ONE}></legend>\r\n\r\n    <div class=\"form-item\">\r\n	    <label for=\"elements-ele_value[0]\"><{$smarty.const._AM_ELE_INSERTBREAK}></label>\r\n	    <textarea id=\"elements-ele_value[0]\" name=\"elements-ele_value[0]\" rows=\"5\" cols=\"35\"><{$content.ele_value[0]}></textarea>\r\n	    <div class=\"description\">\r\n          <p><{$smarty.const._AM_ELE_IB_DESC}></p>\r\n      </div>\r\n    </div>\r\n    <div class=\"form-item\">\r\n	    <label for=\"elements-ele_value[1]\"><{$smarty.const._AM_ELE_INSERTBREAK}></label>\r\n      <select name=\"elements-ele_value[1]\" id=\"css\" size=1>\r\n	<{html_options options=$content.ib_style_options selected=$content.ele_value[1]}>\r\n      </select>\r\n    </div>\r\n  </fieldset>\r\n</div>'),
(145,'<div class=\"panel-content content\">\r\n    <div class=\"form-item\">\r\n    <fieldset>\r\n		<legend><{$smarty.const._AM_ELE_OPT}></legend>\r\n		<{include file=\"db:admin/element_optionlist.html\"}>\r\n    </div>\r\n    <{include file=\"db:admin/element_options_delimiter_choice.html\"}>\r\n    \r\n  \r\n</div>'),
(146,'<div class=\"panel-content content\">\r\n	<div class=\"form-item\">\r\n		<fieldset>\r\n			<legend>What kind of select box is this?</legend>\r\n			<div class=\"form-radios\">\r\n				<label for=\"dropdown\"><input type=\"radio\" id=\"dropdown\" name=\"elements_listordd\" value=\"0\"<{if $content.listordd eq 0}> checked=\"checked\"<{/if}>/>This is a dropdown box</label>\r\n			</div>\r\n			<div class=\"form-radios\">\r\n				<label for=\"list\" style=\"vertical-align: bottom;\"><input type=\"radio\" style=\"vertical-align: bottom;\" id=\"list\" name=\"elements_listordd\" value=\"1\"<{if $content.listordd eq 1}> checked=\"checked\"<{/if}>/>This is a list box, with <input type=\"text\" id=\"elements-ele_value_0\" name=\"elements-ele_value[0]\" value=\"<{$content.ele_value[0]}>\" maxlength=2 size=2> rows.</label>\r\n				<blockquote style=\'margin-top: 3px\'>\r\n					Multiple selections are:\r\n					<label for=\"elements_multiple_allowed\"><input type=\"radio\" id=\"elements_multiple_allowed\" name=\"elements_multiple\" value=\"1\" <{if $content.multiple}>checked<{/if}>/>Allowed</label>\r\n					<label for=\"elements_multiple_notallowed\"><input type=\"radio\" id=\"elements_multiple_notallowed\" name=\"elements_multiple\" value=\"0\" <{if $content.multiple eq 0}>checked<{/if}>/>Not Allowed</label>\r\n				</blockquote>\r\n			</div>\r\n			<div class=\"form-radios\">\r\n				<label for=\"autocomplete\"><input type=\"radio\" id=\"autocomplete\" name=\"elements_listordd\" value=\"2\"<{if $content.listordd eq 2}> checked=\"checked\"<{/if}>/>This is a \"autocompletion\" text box, which will give users a list of choices based on what they type in the box</label>\r\n				<blockquote style=\'margin-top: 3px\'>\r\n                    Multiple selections are:\r\n					<label for=\"elements_multiple_allowed_auto\"><input type=\"radio\" id=\"elements_multiple_allowed_auto\" name=\"elements_multiple_auto\" value=\"1\" <{if $content.multiple_auto}>checked<{/if}>/>Allowed</label>\r\n					<label for=\"elements_multiple_notallowed_auto\"><input type=\"radio\" id=\"elements_multiple_notallowed_auto\" name=\"elements_multiple_auto\" value=\"0\" <{if $content.multiple_auto eq 0}>checked<{/if}>/>Not Allowed</label><br />\r\n					When users type something that doesn\'t match any of the choices:\r\n					<label for=\"elements-ele_value[16]-0\"><input type=\"radio\" id=\"elements-ele_value[16]-0\" name=\"elements-ele_value[16]\" value=\"0\"<{if $content.ele_value[16] eq 0}> checked=\"checked\"<{/if}>/>Say \"No Match Found\"</label>\r\n					<label for=\"elements-ele_value[16]-1\"><input type=\"radio\" id=\"elements-ele_value[16]-1\" name=\"elements-ele_value[16]\" value=\"1\"<{if $content.ele_value[16] eq 1}> checked=\"checked\"<{/if}>/>Allow new values to be saved</label>\r\n				</blockquote>\r\n			</div>\r\n		</fieldset>\r\n	</div>\r\n\r\n	<{include file=\"db:admin/element_linkedoptionlist.html\"}>\r\n        \r\n    <div class=\"form-item\" id=\'linkedsourcemapping\' style=\'display: none;\'>\r\n        <fieldset>\r\n            <legend>When creating a new entry in the linked source form, map other values to the source form too:</legend>\r\n            <input type=\'button\' name=\'new-mapping\' value=\'Add 1\' /><br /><br />\r\n            <div id=\'mappingcontainer\'>\r\n              <{if $content.linkedSourceMappings|is_array AND $content.linkedSourceMappings|@count > 0}>\r\n                <{foreach from=$content.linkedSourceMappings key=index item=value name=mappings}>\r\n                  <div class=\"elementmappings\" name=\"<{$index}>\">\r\n                  <{if is_numeric($value.thisForm)}>\r\n                  <select id=\"mappingthisform-<{$index}>\" name=\"mappingthisform[<{$index}>]\" size=\"1\">\r\n                    <{html_options options=$content.mappingthisformoptions selected=$value.thisForm}>\r\n                  </select>\r\n                  <{elseif $value.thisForm}>\r\n                  <select id=\"mappingthisform-<{$index}>\" name=\"turnedoff\" size=\"1\" style=\'display: none;\'>\r\n                    <{html_options options=$content.mappingthisformoptions}>\r\n                  </select>\r\n                  <input name=\"mappingthisform[<{$index}>]\" value=\"<{$value.thisForm}>\">\r\n                  <{/if}>\r\n                    >>\r\n                  <select id=\"mappingsourceform-<{$index}>\" name=\"mappingsourceform[<{$index}>]\" size=\"1\">\r\n                    <{html_options options=$content.mappingsourceformoptions selected=$value.sourceForm}>\r\n                  </select>\r\n                  <{if $smarty.foreach.mappings.index > 0}>\r\n                  <img class=\"removeMapping\" style=\"cursor: pointer;\" onclick=\"removeMapping(<{$index}>)\" src=\"../images/editdelete.gif\"></img>\r\n                  <{/if}>\r\n                  </div>\r\n                <{/foreach}>\r\n              <{else}>\r\n                  <div class=\"elementmappings\" name=\"0\">\r\n                  <select id=\"mappingthisform-0\" name=\"mappingthisform[0]\" size=\"1\">\r\n                    <{html_options options=$content.mappingthisformoptions}>\r\n                  </select>\r\n                    >>\r\n                  <select id=\"mappingsourceform-0\" name=\"mappingsourceform[0]\" size=\"1\">\r\n                    <{html_options options=$content.mappingsourceformoptions}>\r\n                  </select>\r\n                  </div>\r\n              <{/if}>\r\n            </div>\r\n        </fieldset>\r\n    </div>\r\n\r\n    <div class=\"form-item\">\r\n    <fieldset>\r\n		<legend>If the options are linked -- or are {FULLNAMES} or {USERNAMES}</legend>\r\n		<div class=\"form-item\">\r\n		<fieldset>\r\n			<legend>Limit them to values from the groups selected here</legend>\r\n			<select id=\"element-formlink_scope\" name=\"element_formlink_scope[]\" size=\"10\" multiple>\r\n			<{html_options options=$content.formlink_scope_options selected=$content.formlink_scope}>\r\n			</select>\r\n			<br/><br/>\r\n		<fieldset>\r\n			<div class=\"form-radios\">\r\n				<label for=\"elements-ele_value[4]-0\"><input type=\"radio\" id=\"elements-ele_value[4]-0\" name=\"elements-ele_value[4]\" value=\"0\"<{if $content.ele_value[4] eq 0}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_FORMLINK_SCOPELIMIT_NO}></label>\r\n			</div>\r\n			<div class=\"form-radios\"><label for=\"elements-ele_value[4]-1\"><input type=\"radio\" id=\"elements-ele_value[4]-1\" name=\"elements-ele_value[4]\" value=\"1\"<{if $content.ele_value[4] eq 1}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_FORMLINK_SCOPELIMIT_YES}></label></div>\r\n		</fieldset>\r\n		<fieldset>\r\n			<div class=\"form-radios\">\r\n				<label for=\"elements-ele_value[6]-0\"><input type=\"radio\" id=\"elements-ele_value[6]-0\" name=\"elements-ele_value[6]\" value=\"0\"<{if $content.ele_value[6] eq 0}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_FORMLINK_ANYALL_ANY}></label>\r\n			</div>\r\n			<div class=\"form-radios\">\r\n				<label for=\"elements-ele_value[6]-1\"><input type=\"radio\" id=\"elements-ele_value[6]-1\" name=\"elements-ele_value[6]\" value=\"1\"<{if $content.ele_value[6] eq 1}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_FORMLINK_ANYALL_ALL}></label>\r\n			</div>\r\n		</fieldset>\r\n            <fieldset>\r\n                <div class=\"form-select\" id=\"optionsLimitByElementFilterDiv\">\r\n                    Further limit the options to values that are selected in this other element: <{$content.optionsLimitByElement}><br /><br />\r\n                    <{if $content.optionsLimitByElementFilter}>\r\n                    <{$content.optionsLimitByElementFilter}><br />\r\n                    <div class=\"description\">Use the filter options to isolate a specific entry in the other form. Dynamic { } references are possible for referring to the values of elements in this form.</div>\r\n                    <{else}>\r\n                    <div class=\"description\">Select an element and then save, to get filter options for isolating a single entry in the selected form.</div>    \r\n                    <{/if}>\r\n                </div>\r\n            </fieldset>\r\n		<div class=\"description\">\r\n			<{$smarty.const._AM_ELE_FORMLINK_SCOPE_DESC}>\r\n		</div>\r\n		</fieldset>\r\n	</div>\r\n    </fieldset>\r\n</div>\r\n        \r\n        <{include file=\"db:admin/element_linkedfilter.html\"}>\r\n        \r\n        <{include file=\"db:admin/element_linkedsortoptions.html\"}>\r\n        \r\n		  <div class=\"form-item\">\r\n		    <fieldset>\r\n			    <legend>Show as links in lists of entries?</legend>\r\n	          <div class=\"form-radios\">\r\n		          <label for=\"elements-ele_value[7]-1\"><input type=\"radio\" id=\"elements-ele_value[7]-1\" name=\"elements-ele_value[7]\" value=\"1\" <{if $content.ele_value[7] eq 1}>checked<{/if}>/>Yes, make these values clickable in lists of entries so people can go to the source form easily</label>\r\n	          </div>\r\n	          <div class=\"form-radios\">\r\n              <label for=\"elements-ele_value[7]-0\"><input type=\"radio\" id=\"elements-ele_value[7]-0\" name=\"elements-ele_value[7]\" value=\"0\" <{if $content.ele_value[7] eq 0}>checked<{/if}>/>No, just show the selected value as text</label>\r\n	          </div>\r\n			    <div class=\"description\">\r\n			    </div>\r\n		    </fieldset>\r\n	    </div>\r\n\r\n	<div class=\"form-item\">\r\n	  <fieldset>\r\n		  <legend>Are there restrictions on how many times each option can be picked?</legend>\r\n	        <div class=\"form-radios\">\r\n            <label for=\"elements-ele_value[9]-0\"><input type=\"radio\" id=\"elements-ele_value[9]-0\" name=\"elements-ele_value[9]\" value=\"0\" <{if $content.ele_value[9] eq 0}>checked<{/if}>/>No</label>\r\n	        </div>\r\n	        <div class=\"form-radios\">\r\n		        <label for=\"elements-ele_value[9]-1\"><input type=\"radio\" id=\"elements-ele_value[9]-1\" name=\"elements-ele_value[9]\" value=\"1\" <{if $content.ele_value[9] eq 1}>checked<{/if}>/>Each option can only be chosen once</label>\r\n	        </div>\r\n	        <div class=\"form-radios\">\r\n            <label for=\"elements-ele_value[9]-2\"><input type=\"radio\" id=\"elements-ele_value[9]-2\" name=\"elements-ele_value[9]\" value=\"2\" <{if $content.ele_value[9] eq 2}>checked<{/if}>/>Each option can only be chosen once, per user</label>\r\n	        </div>\r\n	        <div class=\"form-radios\">\r\n            <label for=\"elements-ele_value[9]-3\"><input type=\"radio\" id=\"elements-ele_value[9]-3\" name=\"elements-ele_value[9]\" value=\"3\" <{if $content.ele_value[9] eq 3}>checked<{/if}>/>Each option can only be chosen once, per group</label>\r\n	        </div>\r\n			  <div class=\"description\">\r\n			  </div>\r\n	  </fieldset>\r\n	</div>\r\n\r\n	    <div class=\"form-item\">\r\n		    <fieldset>\r\n			    <legend>Which option(s) should be selected by default?</legend>\r\n				<select name=elements-ele_value[13][] size=10 multiple class=\"form-multiple-select\">\r\n					<{foreach from=$content.optionDefaultSelection key=\"default_entry_id\" item=\"default_value\"}>\r\n						<option value=<{$default_entry_id}><{if in_array($default_entry_id, $content.optionDefaultSelectionDefaults)}> selected<{/if}>><{$default_value}></option>\r\n					<{/foreach}>\r\n				</select>\r\n				<div class=\"form-radios\">\r\n			          <label for=\"elements-ele_value[14]-0\"><input type=\"radio\" id=\"elements-ele_value[14]-0\" name=\"elements-ele_value[14]\" value=\"0\" <{if $content.ele_value[14] eq 0}>checked<{/if}>/>Use these defaults the first time the form is shown (when creating a new entry).</label>\r\n				</div>\r\n				<div class=\"form-radios\">\r\n				  <label for=\"elements-ele_value[14]-1\"><input type=\"radio\" id=\"elements-ele_value[14]-1\" name=\"elements-ele_value[14]\" value=\"1\" <{if $content.ele_value[14] eq 1}>checked<{/if}>/>Use these defaults the first time the form is shown, and also when editing an entry that has no value for this linked selectbox.</label>\r\n				</div>\r\n				<div class=\"description\">\r\n					If you use these defaults every time an entry has no value for this linked selectbox, then if a user purposely saves an entry with no values selected, this selectbox will have the defaults showing on screen when the user edits their entry.  So they will always have to un-select the defaults in order to preserve their \"no value\" choice.\r\n				</div>\r\n		    </fieldset>\r\n	    </div>\r\n\r\n		<{include file=\"db:admin/alternate_fields_for_linked_elements.html\"}>\r\n\r\n</div>\r\n\r\n<script type=\"text/javascript\">\r\n    \r\n  $(\"[name=\'new-mapping\']\").click(function (){\r\n    number = $(\".elementmappings:last\").attr(\'name\');\r\n    number = parseInt(number) + 1;\r\n    $(\'#mappingcontainer\').append(\'<div class=\"elementmappings\" name=\"\'+number+\'\"><select id=\"mappingthisform-\'+number+\'\" name=\"mappingthisform[\'+number+\']\" size=\"1\"></select> >> <select id=\"mappingsourceform-\'+number+\'\" name=\"mappingsourceform[\'+number+\']\" size=\"1\"></select> <img class=\"removeMapping\" style=\"cursor: pointer;\" onclick=\"removeMapping(\'+number+\')\" src=\"../images/editdelete.gif\"></img></div>\');\r\n    var thisformoptions = $(\'#mappingthisform-0 > option\').clone();\r\n    $(\'#mappingthisform-\'+number).append(thisformoptions);\r\n    $(\'#mappingthisform-\'+number+\' option:selected\').removeAttr(\'selected\');\r\n    $(\'#mappingthisform-\'+number).append(\'<option value=\"mapaliteralvalue\">Map a literal value</option>\');\r\n    var sourceformoptions = $(\'#mappingsourceform-0 > option\').clone();\r\n    $(\'#mappingsourceform-\'+number).append(sourceformoptions);\r\n    $(\'#mappingsourceform-\'+number+\' option:selected\').removeAttr(\'selected\');\r\n    $(\"[name=\'new-mapping\']\").blur();\r\n    setDisplay(\'savewarning\',\'block\');\r\n  });\r\n    \r\n    $(\"[name^=mappingthisform]\").live(\'change\', function() {\r\n        if ($(this).val() == \'mapaliteralvalue\') {\r\n            $(this).attr(\'name\', \'turnedoff\');\r\n            $(this).parent().prepend(\'<input name=\"mappingthisform[\'+$(this).parent().attr(\'name\')+\']\">\');\r\n            $(this).hide();\r\n        }\r\n    });\r\n    \r\n    function removeMapping(number) {\r\n        $(\'.elementmappings\').remove(\'[name=\"\'+number+\'\"]\');\r\n        setDisplay(\'savewarning\',\'block\');\r\n    }\r\n    \r\n $(\"#elements-ele_value_0\").focus(function() {\r\n	  $(\"#list\").attr(\'checked\',1);\r\n });\r\n\r\n $(\"[name=elements_multiple]\").click(function() {\r\n	$(\"#list\").attr(\'checked\',1);\r\n });\r\n\r\n $(\"#elements_multiple_notallowed\").click( function(){\r\n 	alert(\"WARNING: You may lose data when changing the multiple selection of your select box!\");\r\n });\r\n\r\n $(\"#formlink\").change(function() {\r\n		$(\"#yes\").attr(\'checked\',1);\r\n		$(\"[name=reload_option_page]\").val(1);\r\n		$(\"#filterdiv\").empty();\r\n		$(\"#filterdiv\").append(\'<p><input type=\"button\" class=\"formButton\" name=\"refreshfilter\" onclick=\"refreshfilterjq()\" value=\"Save changes to update filter options\"></p>\');\r\n });\r\n\r\n function refreshfilterjq() {\r\n	 $(\"[name=reload_option_page]\").val(1);\r\n	 $(\".savebutton\").click();\r\n\r\n }\r\n\r\n $(\"[name=addoption]\").click(function () {\r\n		$(\"#no\").attr(\'checked\',1);\r\n })\r\n\r\n $(\"[name=ele_value[0]]\").keydown(function () {\r\n	$(\"#no\").attr(\'checked\',1);\r\n	$(\"#formlink\").val(\'none\');\r\n	$(\"#filterdiv\").empty();\r\n	$(\"#filterdiv\").append(\'<p>The options are not linked.</p>\');\r\n\r\n });\r\n\r\n    $(\"div#optionsLimitByElementFilterDiv > a.conditionsdelete\").click(function () {\r\n		$(\".optionsLimitByElementFilterDelete\").val($(this).attr(\'target\'));\r\n		$(\"[name=reload_option_page]\").val(1);\r\n	  $(\".savebutton\").click();\r\n		return false;\r\n	});\r\n \r\n 	$(\"div#optionsLimitByElementFilterDiv > input#addcon\").click(function () {\r\n		$(\"[name=reload_option_page]\").val(1);\r\n	  $(\".savebutton\").click();\r\n		return false;\r\n	});\r\n \r\n    $(\"#snapshot-<{$content.ele_value.snapshot}>\").attr(\'checked\',1);\r\n    \r\n    <{if $content.ele_id != \'new\'}>\r\n    var snapshotWarning = true;\r\n    $(\'input[name*=\"snapshot\"]\').change(function() {\r\n        if (snapshotWarning) {\r\n            alert(\'*** WARNING: If you change the \"snapshot\" setting of an element that users have saved data in already, then all the existing data will be lost. Change the setting back BEFORE SAVING if you need to preserve the data. ***\');\r\n            snapshotWarning = false;\r\n        }\r\n    })\r\n    <{/if}>\r\n    \r\n    $(\'input[name=\"linked_yesno\"]\').change(function() {\r\n        if($(\'input[name=\"linked_yesno\"]:checked\').val() == 1) {\r\n            $(\'#snapshot-values\').show(200);\r\n        } else {\r\n            $(\'#snapshot-values\').hide(200);\r\n        }\r\n    });\r\n \r\n \r\n    function showHideLinkedSourceMapping() {\r\n        if ($(\'[name=\"elements_listordd\"]:checked\').val()==2 && $(\'[name=\"elements-ele_value\\\\[snapshot\\\\]\"]:checked\').val() != 1 && $(\'[name=\"elements-ele_value\\\\[16\\\\]\"]:checked\').val() == 1 && $(\'#formlink\').val() != \'none\') {\r\n            if($(\'#linkedsourcemapping\').css(\'display\') == \'none\') {\r\n                $(\'#linkedsourcemapping\').css(\'display\', \'block\');\r\n            }\r\n            return false;\r\n        } else {\r\n            if($(\'#linkedsourcemapping\').css(\'display\') == \'none\') {\r\n		return false;\r\n            }\r\n            $(\'#linkedsourcemapping\').css(\'display\', \'none\');\r\n        }\r\n    }\r\n    \r\n    $(document).ready(function() {\r\n        showHideLinkedSourceMapping();\r\n        \r\n        $(\'[name=\"elements_listordd\"]\').change(function() {\r\n            showHideLinkedSourceMapping();\r\n        });\r\n        \r\n        $(\'[name=\"elements-ele_value\\\\[16\\\\]\"]\').change(function() {\r\n            showHideLinkedSourceMapping();\r\n        });\r\n        \r\n        $(\'#formlink\').change(function() {\r\n            showHideLinkedSourceMapping();\r\n	});\r\n\r\n        $(\'[name=\"elements-ele_value\\\\[snapshot\\\\]\"]\').change(function() {\r\n            showHideLinkedSourceMapping();\r\n        })\r\n       \r\n\r\n	});\r\n\r\n</script>'),
(147,'<div class=\"panel-content content\">\r\n  <fieldset>\r\n    <legend><{$smarty.const._AM_ELE_SEP}></legend>\r\n\r\n    <div class=\"form-item required\">\r\n	    <label for=\"element-rows\"><{$smarty.const._AM_ELE_ROWS}><em>*</em></label>\r\n	    <input type=\"text\" id=\"element-rows\" name=\"element-rows\" value=\"\" size=\"3\" maxlength=\"3\"/>\r\n    </div>\r\n    <div class=\"form-item required\">\r\n	    <label for=\"element-cols\"><{$smarty.const._AM_ELE_COLS}><em>*</em></label>\r\n	    <input type=\"text\" id=\"element-cols\" name=\"element-cols\" value=\"\" size=\"3\" maxlength=\"3\"/>\r\n    </div>\r\n	  <div class=\"form-item\">\r\n      <fieldset>\r\n        <legend><{$smarty.const._AM_ELE_TYPE}></legend>\r\n        <{html_checkboxes name=\'screens-option\' options=$content.options selected=$content.option separator=\'<br />\'}>\r\n      </fieldset>\r\n	  </div>\r\n    <div class=\"form-item\">\r\n	    <label for=\"element-default\"><{$smarty.const._AM_ELE_DEFAULT}></label>\r\n	    <textarea id=\"element-default\" name=\"element-default\" rows=\"5\" cols=\"35\"></textarea>\r\n	    <div class=\"description\">\r\n        <p><{$smarty.const._AM_ELE_TEXT_DESC}><{$smarty.const._AM_ELE_TEXT_DESC2}></p>\r\n      </div>\r\n    </div>\r\n    <div class=\"form-item\">\r\n	    <label for=\"element-default\"><{$smarty.const._AM_ELE_CLR}></label>\r\n	    <select id=\"element-couleur\" name=\"element-couleur\" size=\"5\">\r\n        <{html_options options=$content.colors selected=$content.couleur}>\r\n	    </select>\r\n    </div>\r\n  </fieldset>\r\n</div>'),
(148,'<div class=\"form-item\">\r\n    <fieldset>\r\n        <legend><{$smarty.const._AM_ELE_SUBFORM_FORM}></legend>\r\n        <select id=\"element-subform\" name=\"elements-ele_value[0]\" size=\"1\">\r\n        <{html_options options=$content.subforms selected=$content.ele_value[0]}>\r\n        </select>\r\n        <div class=\"description\">\r\n            <{$smarty.const._AM_ELE_SUBFORM_DESC}>\r\n        </div>\r\n    </fieldset>\r\n    </div>\r\n\r\n    <div class=\"form-item\">\r\n	<fieldset>\r\n		<legend>User interface options</legend>\r\n\r\n		<div class=\"form-item\">\r\n		 <div class=\"form-radios\">\r\n		     <label for=\"elements-ele_value[8]-row\"><input type=\"radio\" id=\"elements-ele_value[8]-row\" name=\"elements-ele_value[8]\" value=\"row\"<{if $content.ele_value[8] eq \'row\' OR $content.ele_value[8] eq \'\'}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_SUBFORM_UITYPE_ROW}></label>\r\n		 </div>\r\n		 <div class=\"form-radios\">\r\n		     <label for=\"elements-ele_value[8]-form\"><input type=\"radio\" id=\"elements-ele_value[8]-form\" name=\"elements-ele_value[8]\" value=\"form\"<{if $content.ele_value[8] eq \'form\'}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_SUBFORM_UITYPE_FORM}></label>\r\n		 </div>\r\n		</div>\r\n\r\n        <div class=\"form-item\">\r\n            <p>Show the button to add entries?</p>\r\n            <div class=\"description\">The \"Add x Entries\" button lets users create additional entries in the subform.</div>\r\n            <div class=\"form-radios\">\r\n                <label for=\"elements-ele_value[6]-subform\"><input type=\"radio\" id=\"elements-ele_value[6]-subform\" name=\"elements-ele_value[6]\" value=\"subform\"<{if $content.ele_value[6] eq \'subform\' OR $content.ele_value[6] eq \'1\'}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_SUBFORM_ADD_SUBFORM}></label>\r\n            </div>\r\n            <div class=\"form-radios\">\r\n                <label for=\"elements-ele_value[6]-parent\"><input type=\"radio\" id=\"elements-ele_value[6]-parent\" name=\"elements-ele_value[6]\" value=\"parent\"<{if $content.ele_value[6] eq \'parent\'}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_SUBFORM_ADD_PARENT}></label>\r\n            </div>\r\n	    <div class=\"form-radios\">\r\n                <label for=\"elements-ele_value[6]-no\"><input type=\"radio\" id=\"elements-ele_value[6]-no\" name=\"elements-ele_value[6]\" value=\"hideaddentries\"<{if $content.ele_value[6] eq \'hideaddentries\' OR $content.ele_value[6] eq \'\'}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_SUBFORM_ADD_NONE}></label>\r\n            </div>\r\n        </div>\r\n        \r\n        <div class=\"form-item\">\r\n            <p>Do not show the button if there are <input type=\'text\' size=2 id=\'elements-ele_value[addButtonLimit]\' name=\'elements-ele_value[addButtonLimit]\' value=\'<{$content.ele_value.addButtonLimit}>\' /> or more entries already.</p>\r\n            <div class=\"description\">Leave blank, or 0, to always show the button</div>\r\n        </div>\r\n\r\n        <div class=\"form-item\" id=\"add-button-appearance\">\r\n            <p>If the button for adding entries is shown, how should it look?</p><br />\r\n            <div class=\"form-radios\">\r\n                <label for=\"elements-ele_value[simple_add_one_button]\"><input type=\"radio\" id=\"elements-ele_value[simple_add_one_button]\" name=\"elements-ele_value[simple_add_one_button]\" value=\"1\"<{if $content.ele_value.simple_add_one_button eq 1}> checked=\"checked\"<{/if}>/><{$smarty.const._formulize_SUBFORM_SIMPLE_BUTTON}></label>\r\n                <p style=\"margin-left:2em;\"><{$smarty.const._formulize_SUBFORM_SIMPLE_LABEL}><br />\r\n                &nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"elements-ele_value[simple_add_one_button_text]\" value=\"<{if $content.ele_value.simple_add_one_button_text}><{$content.ele_value.simple_add_one_button_text}><{else}>Add One<{/if}>\" /></p>\r\n            </div>\r\n            <br />\r\n            <div class=\"form-radios\">\r\n                <label for=\"elements-ele_value[simple_add_one_button]-no\"><input type=\"radio\" id=\"elements-ele_value[simple_add_one_button]-no\" name=\"elements-ele_value[simple_add_one_button]\" value=\"0\"<{if $content.ele_value.simple_add_one_button eq 0}> checked=\"checked\"<{/if}>/><{$smarty.const._formulize_SUBFORM_MULTIPLE_BUTTON}></label>\r\n                <p style=\"margin-left:2em;\"><{$smarty.const._formulize_SUBFORM_MULTIPLE_LABEL}><br />\r\n                &nbsp;&nbsp;&nbsp;Add x <input type=\"text\" name=\"elements-ele_value[9]\" value=\"<{if $content.ele_value[9]}><{$content.ele_value[9]}><{else}><{$smarty.const._formulize_ADD_ENTRIES}><{/if}>\" /></p>\r\n            </div>\r\n        <div class=\"form-item\" id=\"delete-clone-buttons\">\r\n            <div class=\"form-radios\">\r\n                <p><label for=\"elements-ele_value[show_delete_button]\"><input type=\"checkbox\" id=\"elements-ele_value[show_delete_button]\" name=\"elements-ele_value[show_delete_button]\" value=\"1\"<{if $content.ele_value.show_delete_button eq 1}> checked=\"checked\"<{/if}>/> Show the Delete button</label></p>\r\n                <p><label for=\"elements-ele_value[show_clone_button]\"><input type=\"checkbox\" id=\"elements-ele_value[show_clone_button]\" name=\"elements-ele_value[show_clone_button]\" value=\"1\"<{if $content.ele_value.show_clone_button eq 1}> checked=\"checked\"<{/if}>/> Show the Clone button</label></p>\r\n            </div>\r\n        </div>\r\n        </div>\r\n	</fieldset>\r\n    </div>\r\n\r\n		<div class=\"form-item\">\r\n			<fieldset>\r\n				<legend><{$smarty.const._AM_ELE_SUBFORM_ELEMENT_LIST}></legend>\r\n				\r\n			<select name=\"elements_ele_value_1[]\" size=\"10\" multiple>\r\n				<{html_options options=$content.subformelements selected=$content.ele_value[1]}>\r\n			</select>\r\n			<div class=\"description\"><{$smarty.const._AM_ELE_SUBFORM_ELEMENTS_DESC}></div>\r\n			\r\n			</fieldset>\r\n		</div>\r\n\r\n<div class=\"form-item\">\r\n			    <fieldset>\r\n				    <legend><{$smarty.const._AM_ELE_SUBFORM_IFROW}></legend>\r\n				    <p><{$smarty.const._AM_ELE_SUBFORM_HEADINGSORCAPTIONS}></p>\r\n			    <div class=\"form-radios\">\r\n				    <label for=\"elements-ele_value[4]-0\"><input type=\"radio\" id=\"elements-ele_value[4]-0\" name=\"elements-ele_value[4]\" value=\"0\"<{if $content.ele_value[4] eq 0}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_SUBFORM_HEADINGSORCAPTIONS_HEADINGS}></label>\r\n			    </div>\r\n			    <div class=\"form-radios\">\r\n				    <label for=\"elements-ele_value[4]-1\"><input type=\"radio\" id=\"elements-ele_value[4]-1\" name=\"elements-ele_value[4]\" value=\"1\"<{if $content.ele_value[4] eq 1}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_SUBFORM_HEADINGSORCAPTIONS_CAPTIONS}></label>\r\n			    </div>\r\n<br />\r\n                <p><{$smarty.const._AM_ELE_SUBFORM_START}></p>\r\n                <div class=\"form-radios\">\r\n				    <label for=\"elements-ele_value[2]-0\"><input type=\"radio\" id=\"elements-ele_value[2]-0\" name=\"subform_start\" value=\"empty\"<{if $content.ele_value.subform_prepop_element == 0 AND $content.ele_value[2] == 0 }> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_SUBFORM_START_EMPTY}></label>\r\n			    </div>\r\n			    <div class=\"form-radios\">\r\n				    <input type=\"radio\" id=\"subform_start_blanks\" name=\"subform_start\" value=\"blanks\"<{if $content.ele_value.subform_prepop_element == 0 AND $content.ele_value[2] > 0}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_SUBFORM_START_BLANKS1}> <input type=\"text\" id=\"number_of_subform_blanks\" name=\"number_of_subform_blanks\" value=\"<{ $content.ele_value[2] }>\" size=\"2\" maxlength=\"2\"/> <{$smarty.const._AM_ELE_SUBFORM_START_BLANKS2}> \r\n			    </div>\r\n                <div class=\"form-radios\">\r\n				    <input type=\"radio\" id=\"subform_start_prepop\" name=\"subform_start\" value=\"prepop\"<{if $content.ele_value.subform_prepop_element > 0 AND $content.ele_value[2] == 0 }> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_SUBFORM_START_PREPOPULATE}> <select id=\"subform_start_prepop_element\" name=\"subform_start_prepop_element\" size=1><{html_options options=$content.subformelements selected=$content.ele_value.subform_prepop_element}></select>\r\n			    </div>\r\n                    <div class=\"description\"><{$smarty.const._AM_ELE_SUBFORM_START_HELP}></div>\r\n<br />\r\n                <div class=\"form-item\">\r\n                    <p><{$smarty.const._AM_ELE_SUBFORM_VIEW}></p>\r\n                     <div class=\"form-radios\">\r\n				    <label for=\"elements-ele_value[3]-0\"><input type=\"radio\" id=\"elements-ele_value[3]-0\" name=\"elements-ele_value[3]\" value=\"0\"<{if !$content.ele_value[3] }> checked=\"checked\"<{/if}>/>Do not show the <i>View</i> buttons</label>\r\n			    </div>\r\n			    <div class=\"form-radios\">\r\n				    <label for=\"elements-ele_value[3]-2\"><input type=\"radio\" id=\"elements-ele_value[3]-2\" name=\"elements-ele_value[3]\" value=\"2\"<{if $content.ele_value[3] == 2 }> checked=\"checked\"<{/if}>/>Show the <i>View</i> buttons, open entries in a dialog box. New entries show up in the dialog.</label>\r\n                </div>\r\n                    <label for=\"elements-ele_value[3]-3\"><input type=\"radio\" id=\"elements-ele_value[3]-3\" name=\"elements-ele_value[3]\" value=\"3\"<{if $content.ele_value[3] == 3 }> checked=\"checked\"<{/if}>/>Show the <i>View</i> buttons, open entries in a dialog box. New entries show up as new rows.</label>\r\n			    <div class=\"form-radios\">\r\n			    </div>\r\n                <div class=\"form-radios\">\r\n				    <label for=\"elements-ele_value[3]-1\"><input type=\"radio\" id=\"elements-ele_value[3]-1\" name=\"elements-ele_value[3]\" value=\"1\"<{if $content.ele_value[3] == 1 }> checked=\"checked\"<{/if}>/>Show the <i>View</i> buttons, open entries full screen (reloads the page, clicking <i>Save and Leave</i> returns to the main form)</label>\r\n			    </div>\r\n                <div class=\"form-radios\">\r\n                <br>\r\n                <p>Disable these elements in each row:</p>\r\n                <select name=\"elements_ele_value_disabledelements[]\" size=\"10\" multiple>\r\n				<{html_options options=$content.subformelements selected=$content.ele_value.disabledelements}>\r\n                </select>\r\n                <div class=\"description\">Elements selected will be rendered as \"read only\" values, instead of as form elements.</div>\r\n                </div>\r\n                \r\n                \r\n                </div>\r\n			    \r\n			    </fieldset>\r\n			    </div>\r\n\r\n    <div class=\"form-item\">\r\n        <fieldset>\r\n            <legend><{$smarty.const._AM_ELE_SUBFORM_IFFORM}></legend>\r\n	    <p><{$smarty.const._AM_ELE_SUBFORM_SCREEN}></p>\r\n            <select id=\"element-subform-screen\" name=\"elements-ele_value[display_screen]\" size=\"1\">\r\n                <{html_options options=$content.subform_screens selected=$content.ele_value.display_screen}>\r\n            </select>\r\n            <div class=\"description\"><{$smarty.const._AM_ELE_SUBFORM_SCREEN_HELP}></div>\r\n        </fieldset>\r\n    </div>\r\n\r\n<div class=\"form-item\">\r\n<fieldset>\r\n<legend>Sorting order for the subform entries</legend>\r\n<p>Sort the subform entries by this element (\'None\' means use creation order):<br />\r\n    <select name=\"elements-ele_value[SortingElement]\" size=\"1\">\r\n		<{html_options options=$content.subformUserFilterElements selected=$content.ele_value.SortingElement}>\r\n	</select>\r\n    &nbsp;&nbsp;&nbsp;\r\n    <input type=\'radio\' name=\'elements-ele_value[SortingDirection]\' value=\'ASC\'>A...Z</input> &nbsp;&nbsp;&nbsp; <input type=\'radio\' name=\'elements-ele_value[SortingDirection]\' value=\'DESC\'>Z...A</input></p>\r\n</fieldset>\r\n</div>\r\n    \r\n\r\n<div class=\"form-item\">\r\n<fieldset>\r\n<legend>Which entries in the subform should be accessible through this subform element?</legend>\r\n    <div id=\"filterdiv\">\r\n	<{if $content.subformfilter}>\r\n        <{$content.subformfilter}>\r\n    </div>\r\n    <p><label for=\"enforceFilterChanges\"><input type=\'checkbox\' id=\'enforceFilterChanges\' name=\'elements-ele_value[enforceFilterChanges]\' value=1 <{if $content.ele_value.enforceFilterChanges}>checked<{/if}> /> Keep elements in sync with the parent entry if they are filtered by a \"match all\" <i>equals</i> condition (=).</label></p>\r\n    <div class=\"description\">\r\n        By default, all entries in a subform that are tied to the main form will be accessible.  You can set conditions here that will limit which entries will be accessible.  This is useful if you have multiple types of entries in a subform, and want the user to interact with each type in a separate place.  For example, a Housing subform might have entries for \"apartments,\" \"townhouses,\" and \"houses\".  You could set this subform element to just show apartments, and another subform element to just show houses.\r\n    </div>\r\n    <div class=\"description\">\r\n        If you have any \"match all\" conditions that use the = operator, then any new entries you add through the subform interface, will have those values assigned automatically.  For example, if you limit this subform interface to only entries where \"type\" = \"apartment\" then all new entries you create would have the value of \"type\" set to \"apartment\". This behaviour can be turned off. If you have the same subform being used in different places, with competing filter conditions, you might not want the synchronization behaviour.\r\n    </div>\r\n    <div>\r\n        <p>Allow the user to filter entries manually, based on this element:</p>\r\n        <select name=\"elements-ele_value[UserFilterByElement]\" size=\"1\">\r\n            <{html_options options=$content.subformUserFilterElements selected=$content.ele_value.UserFilterByElement}>\r\n        </select>\r\n    </div>            \r\n    <div class=\"description\">\r\n        If you allow the user to filter based on a selected element, then no subform entries will appear until the user has typed something in the filter box. Then, only the matching entries will be shown. Filters you specify above will be applied in addition to the filter the user types.\r\n    </div>\r\n	<{else}>\r\n		<p>You need to select a subform and save this element before you can choose conditions.</p>\r\n    </div>\r\n    <{/if}>					\r\n</fieldset>\r\n</div>\r\n\r\n\r\n\r\n    \r\n\r\n	<div class=\"form-item\">\r\n		<fieldset>\r\n    <legend>When new entries are created in the subform, who should be the owner of those entries?</legend>\r\n		<div class=\"form-radios\">\r\n      <label for=\"elements-ele_value[5]-0\"><input type=\"radio\" id=\"elements-ele_value[5]-0\" name=\"elements-ele_value[5]\" value=\"0\"<{if $content.ele_value[5] eq 0}> checked=\"checked\"<{/if}>/>The currently logged in user should be the owner of subform entries</label>\r\n      <label for=\"elements-ele_value[5]-1\"><input type=\"radio\" id=\"elements-ele_value[5]-1\" name=\"elements-ele_value[5]\" value=\"1\"<{if $content.ele_value[5] eq 1}> checked=\"checked\"<{/if}>/>The owner of the entry in the main form should be the owner of subform entries</label>\r\n    </div>\r\n		<div class=\"description\">This feature lets you override the ownership of new entries created in the subform.  By default, new entries are owned by the user who created them.  But in some situtations you might want to have all subform entries owned by the same person who created the main form entry, even if the subform entries are being created by many different people.</div>\r\n		</fieldset>\r\n	</div>\r\n\r\n<script type=\"text/javascript\">\r\n\r\n	$(\"#element-subform\").change(function() {\r\n		subfid = $(\"#element-subform\").val();\r\n		$.get(\"<{$xoops_url}>/modules/formulize/formulize_xhr_responder.php?uid=<{$content.uid}>&op=get_element_option_list&fid=\"+subfid, function(data) {\r\n			results = eval(\'(\'+data+\')\');\r\n			if(results) {\r\n				$(\"[name=elements_ele_value_1[]]\").empty();\r\n				for( var i in results.options) {\r\n					if(typeof(results.options[i]) == \"object\") {\r\n						$(\"[name=elements_ele_value_1[]]\").append(\"<option value=\'\"+results.options[i].id+\"\'>\"+results.options[i].value+\"</value>\");\r\n					}\r\n				}\r\n			}\r\n		});\r\n	});\r\n	\r\n    $(\"#subform_start_prepop_element\").change(function() {\r\n       $(\"#subform_start_prepop\").attr(\"checked\", \"checked\");\r\n    });\r\n    \r\n    $(\"#number_of_subform_blanks\").focus(function() {\r\n       $(\"#subform_start_blanks\").attr(\"checked\", \"checked\");\r\n    });\r\n    \r\n	$(\"div#filterdiv > a.conditionsdelete\").click(function () {\r\n		$(\".optionsconditionsdelete\").val($(this).attr(\'target\'));\r\n		$(\"[name=reload_option_page]\").val(1);\r\n	  $(\".savebutton\").click();\r\n		return false;\r\n	});\r\n\r\n	$(\"div#filterdiv > input#addcon\").click(function () {\r\n		$(\"[name=reload_option_page]\").val(1);\r\n	  $(\".savebutton\").click();\r\n		return false;\r\n	});\r\n\r\n    var prevVal;\r\n    function removeNonDisplayedElementsFromDisabledList() {\r\n        // if some elements are selected...\r\n        if ($(\"[name^=elements_ele_value_1]\").children(\":not(option:selected)\").length < $(\"[name^=elements_ele_value_1]\").children().length) {\r\n            $(\"[name^=elements_ele_value_1]\").children(\":not(option:selected)\").each(function() {\r\n                $(\"[name^=elements_ele_value_disabledelements] option[value=\"+$(this).val()+\"]\").remove();\r\n            });\r\n        // no elements are selected, so replicate the entire list...\r\n        } else {\r\n            $(\"[name^=elements_ele_value_1]\").children().each(function() {\r\n                addElementToDisabledList($(this).val(), prevVal, $(this).index());\r\n                prevVal = $(this).val();\r\n            });\r\n        }\r\n    }\r\n    \r\n    function addElementToDisabledList(optionValue, prevOptionValue, i) {\r\n        if(optionValue != \'null\' && $(\"[name^=elements_ele_value_disabledelements] option[value=\"+optionValue+\"]\").length == 0) {\r\n            if (i == 0) {\r\n                $(\"[name^=elements_ele_value_disabledelements]\").prepend(\'<option value=\"\'+optionValue+\'\">\'+$(\"[name^=elements_ele_value_1] option[value=\"+optionValue+\"]\").text()+\'</option>\');\r\n            } else {\r\n                $(\'<option value=\"\'+optionValue+\'\">\'+$(\"[name^=elements_ele_value_1] option[value=\"+optionValue+\"]\").text()+\'</option>\').insertAfter($(\"[name^=elements_ele_value_disabledelements] option[value=\"+prevOptionValue+\"]\"));\r\n            }\r\n        }\r\n    }\r\n    \r\n    $(\"[name^=elements_ele_value_1]\").change(function() {\r\n        var eles = String($(this).val()).split(\',\');\r\n        for (i = 0; i < eles.length; i++) {\r\n            addElementToDisabledList(eles[i], eles[i-1], i);\r\n        }\r\n        removeNonDisplayedElementsFromDisabledList();\r\n    });\r\n    \r\n    removeNonDisplayedElementsFromDisabledList()\r\n    \r\n</script>'),
(149,'<div class=\"panel-content content\">\r\n\r\n    <div class=\"form-item required\">\r\n	    <label for=\"elements-ele_value[1]\"><{$smarty.const._AM_ELE_ROWS}><em>*</em></label>\r\n	    <input type=\"text\" id=\"elements-ele_value[1]\" name=\"elements-ele_value[1]\" value=\"<{$content.ele_value[1]}>\" size=\"3\" maxlength=\"3\"/>\r\n    </div>\r\n    <div class=\"form-item required\">\r\n	    <label for=\"elements-ele_value[2]\"><{$smarty.const._AM_ELE_COLS}><em>*</em></label>\r\n	    <input type=\"text\" id=\"elements-ele_value[2]\" name=\"elements-ele_value[2]\" value=\"<{$content.ele_value[2]}>\" size=\"3\" maxlength=\"3\"/>\r\n    </div>\r\n    <div class=\"form-item\">\r\n	    <label for=\"elements-ele_value[0]\"><{$smarty.const._AM_ELE_DEFAULT}></label>\r\n	    <textarea id=\"elements-ele_value[0]\" name=\"elements-ele_value[0]\" rows=\"5\" cols=\"35\"><{$content.ele_value[0]}></textarea>\r\n	    <div class=\"description\">\r\n          <p><{$smarty.const._AM_ELE_TEXT_DESC}></p>\r\n      </div>\r\n    </div>\r\n	    <div class=\"form-item\">\r\n	    <input type=\'checkbox\' id=\"elements-ele_value-use-rich-text\" name=\"elements-ele_value[use_rich_text]\" value=1 <{if $content.ele_value.use_rich_text}>checked=\'checked\'<{/if}>> <label for=\"elements-ele_value-use-rich-text\"><{$smarty.const._AM_ELE_USERICHTEXT}></label>\r\n	    <div class=\"description\">\r\n          <p><{$smarty.const._AM_ELE_RICHTEXT_DESC}></p>\r\n      </div>\r\n    </div>\r\n	\r\n    <div class=\"form-item\">\r\n	    <label for=\"formlink\"><{$smarty.const._AM_ELE_FORMLINK_TEXTBOX}></label>\r\n      <{$content.formlink}>\r\n	    <div class=\"description\">\r\n		    <{$smarty.const._AM_ELE_FORMLINK_DESC_TEXTBOX}>\r\n	    </div>\r\n    </div>\r\n\r\n</div>'),
(150,'<div class=\"panel-content content\">\r\n    <div class=\"form-item required\">\r\n	    <label for=\"elements-ele_value[0]\"><{$smarty.const._AM_ELE_SIZE}><em>*</em></label>\r\n	    <input type=\"text\" id=\"elements-ele_value[0]\" name=\"elements-ele_value[0]\" value=\"<{$content.ele_value[0]}>\" size=\"3\" maxlength=\"3\"/>\r\n    </div>\r\n    <div class=\"form-item required\">\r\n	    <label for=\"elements-ele_value[1]\"><{$smarty.const._AM_ELE_MAX_LENGTH}><em>*</em></label>\r\n	    <input type=\"text\" id=\"elements-ele_value[1]\" name=\"elements-ele_value[1]\" value=\"<{$content.ele_value[1]}>\" size=\"3\" maxlength=\"3\"/>\r\n    </div>\r\n    <div class=\"form-item\">\r\n	    <label for=\"elements-ele_value[2]\"><{$smarty.const._AM_ELE_DEFAULT}></label>\r\n	    <textarea id=\"elements-ele_value[2]\" name=\"elements-ele_value[2]\" rows=\"5\" cols=\"35\"><{$content.ele_value[2]}></textarea>\r\n	    <div class=\"description\">\r\n          <p><{$smarty.const._AM_ELE_TEXT_DESC}><{$smarty.const._AM_ELE_TEXT_DESC2}></p>\r\n      	</div>\r\n    </div>\r\n    <div class=\"form-item\">\r\n      	<fieldset>\r\n      	<legend><{$smarty.const._AM_ELE_PLACEHOLDER_DESC}></legend>\r\n      		<div class=\"form-radios\">\r\n      			<label for=\"no-placeholder\"><input type=\"radio\" id=\"no-placeholder\" name=\"elements-ele_value[11]\" value=\"0\"<{if $content.ele_value[11] eq 0}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_NO_PLACEHOLDER}></label>\r\n      		</div>\r\n      		<div class=\"form-radios\">\r\n      			<label for=\"placeholder\"><input type=\"radio\" id=\"placeholder\" name=\"elements-ele_value[11]\" value=\"1\"<{if $content.ele_value[11] eq 1}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_PLACEHOLDER_OPTION}></label>\r\n      		</div>\r\n      	</fieldset>\r\n    </div>\r\n    <div class=\"form-item\">\r\n	    <fieldset>\r\n	    <legend><{$smarty.const._AM_ELE_TYPE}></legend>\r\n		    <div class=\"form-radios\">\r\n			    <label for=\"anything\"><input type=\"radio\" id=\"anything\" name=\"elements-ele_value[3]\" value=\"0\"<{if $content.ele_value[3] eq 0}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_TYPE_STRING}></label>\r\n		    </div>\r\n		    <div class=\"form-radios\">\r\n			    <label for=\"numbers\"><input type=\"radio\" id=\"numbers\" name=\"elements-ele_value[3]\" value=\"1\"<{if $content.ele_value[3] eq 1}> checked=\"checked\"<{/if}>/><{$smarty.const._AM_ELE_TYPE_NUMBER}></label>\r\n		    </div>\r\n		    <div class=\"description\">\r\n			    <p><{$smarty.const._AM_ELE_TYPE_DESC}></p>\r\n		    </div>\r\n	    </fieldset>\r\n    </div>\r\n    <div class=\"form-item\">\r\n	    <fieldset>\r\n		    <legend><{$smarty.const._AM_ELE_NUMBER_OPTS}></legend>\r\n		    <div class=\"form-item\">\r\n			    <label for=\"elements-ele_value[5]\"><{$smarty.const._AM_ELE_NUMBER_OPTS_DEC}></label><input type=\"text\" id=\"elements-ele_value[5]\" name=\"elements-ele_value[5]\" value=\"<{$content.ele_value[5]}>\" size=\"2\" maxlength=\"2\"/>\r\n		    </div>\r\n		    <div class=\"form-item\">\r\n			    <label for=\"elements-ele_value[6]\"><{$smarty.const._AM_ELE_NUMBER_OPTS_PREFIX}></label><input type=\"text\" id=\"elements-ele_value[6]\" name=\"elements-ele_value[6]\" value=\"<{$content.ele_value[6]}>\" size=\"5\" maxlength=\"255\"/>\r\n		    </div>\r\n		    <div class=\"form-item\">\r\n			    <label for=\"elements-ele_value[10]\"><{$smarty.const._AM_ELE_NUMBER_OPTS_SUFFIX}></label><input type=\"text\" id=\"elements-ele_value[10]\" name=\"elements-ele_value[10]\" value=\"<{$content.ele_value[10]}>\" size=\"5\" maxlength=\"255\"/>\r\n		    </div>\r\n		    <div class=\"form-item\">\r\n			    <label for=\"elements-ele_value[7]\"><{$smarty.const._AM_ELE_NUMBER_OPTS_DECSEP}></label><input type=\"text\" id=\"elements-ele_value[7]\" name=\"elements-ele_value[7]\" value=\"<{$content.ele_value[7]}>\" size=\"5\" maxlength=\"255\"/>\r\n		    </div>\r\n		    <div class=\"form-item\">\r\n			    <label for=\"elements-ele_value[8]\"><{$smarty.const._AM_ELE_NUMBER_OPTS_SEP}></label><input type=\"text\" id=\"elements-ele_value[8]\" name=\"elements-ele_value[8]\" value=\"<{$content.ele_value[8]}>\" size=\"5\" maxlength=\"255\"/>\r\n		    </div>\r\n		    <div class=\"description\">\r\n			    <{$smarty.const._AM_ELE_NUMBER_OPTS_DESC}>\r\n		    </div>\r\n	    </fieldset>\r\n    </div>\r\n    <div class=\"form-item\">\r\n	    <label for=\"formlink\"><{$smarty.const._AM_ELE_FORMLINK_TEXTBOX}></label>\r\n      <{$content.formlink}>\r\n	    <div class=\"description\">\r\n		    <{$smarty.const._AM_ELE_FORMLINK_DESC_TEXTBOX}>\r\n	    </div>\r\n    </div>\r\n		  <input type=\"checkbox\" id=\"elements-ele_value[9]\" name=\"elements-ele_value[9]\"<{if $content.ele_value[9] eq 1}> checked=\"checked\"<{/if}> value=\"1\"/> <{$smarty.const._AM_ELE_REQUIREUNIQUE}>\r\n</div>'),
(151,'<div class=\"panel-content content\">\r\n  <div class=\"form-item\">\r\n    <fieldset>\r\n      <legend><{$smarty.const._AM_ELE_YN}></legend>\r\n\r\n  	  <div class=\"form-item\">\r\n  		<label for=\"max\"><{$smarty.const._AM_ELE_DEFAULT}></label>\r\n\r\n	    <label for=\"elements-ele_value-yes\"><input type=\"radio\" id=\"elements-ele_value-yes\" name=\"elements_ele_value\" value=\"_YES\"<{if $content.ele_value_yes}> checked<{/if}>/><{$smarty.const._YES}></label>\r\n	    <label for=\"elements-ele_value-no\"><input type=\"radio\" id=\"elements-ele_value-no\" name=\"elements_ele_value\" value=\"_NO\"<{if $content.ele_value_no}> checked<{/if}>/><{$smarty.const._NO}></label>\r\n  		<input type=\"submit\" class=\"formButton\" name=\"cleardef\" value=\"<{$smarty.const._AM_CLEAR_DEFAULT}>\"/>\r\n	    <div class=\"description\"></div>\r\n	  </div>\r\n    </fieldset>\r\n  </div>\r\n\r\n<{include file=\"db:admin/element_options_delimiter_choice.html\"}>\r\n\r\n</div>\r\n\r\n<script type=\'text/javascript\'>\r\n$(\"[name=cleardef]\").click(function () {\r\n  var el_collection = document.getElementsByName(\"elements_ele_value\");\r\n  for(c=0;c<el_collection.length;c++)\r\n    el_collection[c].checked=false;\r\n\r\n	$(\"[name=cleardef]\").blur();\r\n  return false;\r\n});\r\n\r\n</script>'),
(152,'<div class=\"panel-content content\">\r\n<div style=\"float: right;\">\r\n	<p style=\"text-align: right;\"><a href=\"../../system/admin.php?fct=preferences&op=showmod&mod=<{$adminPage.formulizeModId}>\"><img src=\"../images/kedit.png\"> <{$smarty.const._AM_HOME_PREF}></a></p>\r\n    <p style=\"text-align: right;\"><a  href=\"ui.php?page=managepermissions\">Copy Group Permissions</a></p>\r\n	<p style=\"text-align: right;\"><a  href=\"ui.php?page=synchronize\">Synchronize With Another System</a></p>\r\n    <p style=\"text-align: right;\"><a href=\"ui.php?page=managekeys\">Manage API keys</a></p>\r\n    <!--<p style=\"text-align: right;\"><a href=\"ui.php?page=manageaccess\">Manage Access to Forms</a></p>-->\r\n    <p style=\"text-align: right;\"><a href=\"ui.php?page=managetokens\">Manage Account Creation Tokens</a></p>\r\n    <p style=\"text-align: right;\"><a href=\"ui.php?page=mailusers\">Email Users</a></p>\r\n</div>\r\n<p>&nbsp;</p><p><a href=\"ui.php?page=form&tab=settings&fid=new\"><img src=\"../images/filenew2.png\"> <{$smarty.const._AM_HOME_NEWFORM}></a>&nbsp&nbsp&nbsp;&nbsp;&nbsp&nbsp&nbsp;&nbsp;<a href=\"ui.php?page=form&tab=settings&fid=new&tableform=true\"><img src=\"../images/filenew3.gif\"> Create a new reference to a datatable</a></p>\r\n<h2><{$smarty.const._AM_HOME_MANAGEAPP}></h2>\r\n<form class=\"formulize-admin-form\">\r\n<{php}>print $GLOBALS[\'xoopsSecurity\']->getTokenHTML()<{/php}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"home\">\r\n<input type=\"hidden\" name=\"deleteform\" value=\"\">\r\n<input type=\"hidden\" name=\"deleteapp\" value=\"\">\r\n<input type=\"hidden\" name=\"cloneform\" value=\"\">\r\n<input type=\"hidden\" name=\"cloneformdata\" value=\"\">\r\n<input type=\"hidden\" name=\"lockdown\" value=\"\">\r\n<{include file=\"db:admin/ui-accordion.html\" sectionTemplate=\"db:admin/home_sections.html\" sections=$adminPage.apps}>\r\n</form>\r\n</div>\r\n\r\n<script type=\"text/javascript\">\r\n\r\n$(\".deleteformlink\").click(function () {\r\n	answer = confirm(\"<{$smarty.const._AM_HOME_CONFIRMDELETEFORM}>\");\r\n	if(answer) {\r\n		$(\"[name=deleteform]\").val($(this).attr(\'target\'));\r\n		runSaveEvent();\r\n	}\r\n	return false;\r\n});\r\n\r\n$(\".deleteapplink\").click(function () {\r\n	answer = confirm(\"<{$smarty.const._AM_HOME_CONFIRMDELETEAPP}>\");\r\n	if(answer) {\r\n		$(\"[name=deleteapp]\").val($(this).attr(\'target\'));\r\n		runSaveEvent();\r\n	}\r\n	return false;\r\n});\r\n\r\n$(\".cloneform\").click(function () {\r\n	$(\"[name=cloneform]\").val($(this).attr(\'target\'));\r\n	runSaveEvent();\r\n	return false;\r\n});\r\n\r\n$(\".cloneformdata\").click(function () {\r\n	$(\"[name=cloneformdata]\").val($(this).attr(\'target\'));\r\n	runSaveEvent();\r\n	return false;\r\n});\r\n\r\n$(\".lockdown\").click(function () {\r\n	answer = confirm(\"<{$smarty.const._AM_HOME_CONFIRMLOCKDOWN}>\");\r\n	if(answer) {\r\n		$(\"[name=lockdown]\").val($(this).attr(\'target\'));\r\n		runSaveEvent();\r\n	}\r\n	return false;\r\n});\r\n\r\n</script>'),
(153,'<{if $sectionContent.aid == 0}>\r\n<div class=\"description homepage-description\"><p><{$smarty.const._AM_HOME_APP_DESC}></p></div>\r\n<{else}>\r\n<div class=\"description homepage-description\"><p><{$sectionContent.description}></p></div>\r\n<{/if}>\r\n\r\n\r\n<{if count((array) $sectionContent.forms) == 0}>\r\n<p>No forms.</p>\r\n<{else}>\r\n<h2>Forms</h2>\r\n<table>\r\n  <tr>\r\n      <th>ID</th>\r\n      <th>Name</th>\r\n      <th>Elements</th>\r\n      <th>Entries</th>\r\n      <th>Data-entry Screen</th>\r\n      <th>List Screen</th>\r\n  </tr>\r\n  <{foreach from=$sectionContent.forms item=form}>\r\n  <tr>\r\n      <td style=\"text-align:right;padding-right:1em;\"><{$form.fid}></td>\r\n      <td><a href=\"<{$xoops_url}>/modules/formulize/admin/ui.php?page=form&aid=<{$sectionContent.aid}>&fid=<{$form.fid}>&tab=elements\"><i class=\"icon-form\"></i> <{$form.form->title}></a></td>\r\n      <td style=\"text-align:right;padding-right:1em;\"><{if $form.form->elementHandles|is_array}><{$form.form->elementHandles|@count}><{/if}></td>\r\n      <td style=\"text-align:right;padding-right:1em;\"><{$form.form->entry_count}></td>\r\n      <td><{if $form.form->defaultform > 0}><a href=\"<{$xoops_url}>/modules/formulize/index.php?sid=<{$form.form->default_form_screen->sid}>\" target=\"_blank\"><i class=\"icon-screen\"></i> <{$form.form->default_form_screen->title}></a><{/if}></td>\r\n      <td><{if $form.form->defaultlist > 0}><a href=\"<{$xoops_url}>/modules/formulize/index.php?sid=<{$form.form->default_list_screen->sid}>\" target=\"_blank\"><i class=\"icon-screen\"></i> <{$form.form->default_list_screen->title}></a><{/if}></td>\r\n  </tr>\r\n  <{/foreach}>\r\n</table>\r\n<{/if}>'),
(154,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<{$securitytoken}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"screen_list_entries\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.sid}>\">\r\n<input type=\"hidden\" class=\"ffdelete\" name=\"ffdelete\" value=\"\">\r\n<input type=\"hidden\" name=\"reload_list_screen_page\" value=\"\">\r\n\r\n\r\n<div class=\"panel-content content\">\r\n  \r\n  <fieldset>\r\n    <legend><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DISPLAY_ONLY_COLUMNS}></legend>\r\n    <p><input type=\"button\" class=\"formButton\" name=\"addColumn\" value=\"<{$smarty.const._AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_ADVANCE_VIEW_ADD_COLUMN}>\"></p>\r\n    <br/>\r\n    <table class=\"advanceview\" style=\"border:none\">\r\n      <tr>\r\n	<td><label><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_ADVANCE_VIEW_COLUMNS}></label></td>\r\n	<td><label><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_ADVANCE_VIEW_SEARCH_BY}></label></td>\r\n    <td><label><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_ADVANCE_VIEW_SEARCH_TYPE}></label></td>\r\n	<td><label><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_ADVANCE_VIEW_SORT_BY}></label></td>\r\n	<td></td>\r\n      </tr>\r\n	<{if $content.advanceview|is_array AND $content.advanceview|@count > 0}>\r\n	  <{foreach from=$content.advanceview key=index item=value}>\r\n	    <tr class=\"advanceviewcol\" name=\"<{$index}>\">\r\n	      <td>\r\n		<select id=\"cols-<{$index}>\" name=\"col-value[<{$index}>]\" size=\"1\">\r\n		  <{html_options options=$content.advanceviewoptions selected=$value.column}>\r\n		</select>\r\n	      </td>\r\n	      <td>\r\n		<input type=\"text\" name=\"search-value[<{$index}>]\" value=\"<{$value.text}>\"></input>\r\n	      </td>\r\n          <td>\r\n        <select id=\"search-type-<{$index}>\" name=\"search-type[<{$index}>]\" size=\"1\">\r\n		  <{html_options options=$content.advanceviewsearchtypeoptions selected=$value.searchtype}>\r\n		</select>\r\n          </td>\r\n	      <td>\r\n		<{if $value.sort == 1}>\r\n		  <input type=\"radio\" name=\"sort-by\" value=<{$index}> checked></input>\r\n		<{else}>\r\n		  <input type=\"radio\" name=\"sort-by\" value=<{$index}>></input>\r\n		<{/if}>\r\n	      </td>\r\n	      <td class=\'removeImage\'>\r\n		<img class=\"removeCol\" style=\"cursor: pointer;\" onclick=\"removeColumn(<{$index}>)\" src=\"../images/editdelete.gif\"></img>\r\n	      </td>\r\n	    </tr>\r\n	  <{/foreach}>\r\n	  <input type=\"hidden\" id=\"numberOfRows\" name=\"rows\" value=<{if $content.advanceview|is_array}><{$content.advanceview|@count}><{/if}>></input>\r\n	<{else}>\r\n	    <tr class=\"advanceviewcol\" name=\"0\">\r\n	      <td>\r\n		<select id=\"cols-0\" name=\"col-value[0]\" size=\"1\">\r\n		  <{html_options options=$content.advanceviewoptions}>\r\n		</select>\r\n	      </td>\r\n	      <td>\r\n		<input type=\"text\" name=\"search-value[0]\"></input>\r\n	      </td>\r\n          <td>\r\n            <select id=\"search-type-0\" name=\"search-type[0]\"><option label=\"Search Box\" value=\"Box\">Search Box</option><option label=\"Dropdown List\" value=\"Filter\">Dropdown List</option><option label=\"Checkboxes\" value=\"MultiFilter\">Checkboxes</option><option label=\"Date Range\" value=\"DateRange\">Date Range</option></select>\r\n          </td>\r\n	      <td>\r\n		<input type=\"radio\" name=\"sort-by\" value=0></input>\r\n	      </td>\r\n	      <td></td>\r\n	    </tr>\r\n	    <input type=\"hidden\" id=\"numberOfRows\" name=\"rows\" value=<{if $content.advanceview|is_array}><{$content.advanceview|@count}><{/if}>></input>\r\n	<{/if}>\r\n    </table>\r\n    <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_ADVANCE_VIEW_DESCRIPTION}></div>\r\n    \r\n  </fieldset>\r\n  <fieldset>\r\n    <legend><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_VIEW_DATA_TO_DISPLAY_HEADER}></legend>	\r\n	<div class=\"form-item float-left half-width\">\r\n	  \r\n	  <label for=\"screens-defaultview\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DEFAULTVIEW}></label>\r\n\r\n	  <p><input type=\"button\" name=\"add_view\" class=\"formButton\" value=\"<{$smarty.const._AM_ELE_ADD_OPT_SUBMIT}> Default View\"</p><br /><br />\r\n\r\n	  <div class=\"view-list\">\r\n		<{foreach from=$content.defaultview key=groupid item=viewid name=viewlist}>\r\n          <div id=\"view_remove_<{$smarty.foreach.viewlist.iteration}>\">\r\n          <{if $smarty.foreach.viewlist.first}><{else}><br><{/if}>\r\n          <img style=\"cursor: pointer;\" onclick=\"removeDefView(<{$smarty.foreach.viewlist.iteration}>)\" src=\"../images/editdelete.gif\">&nbsp;\r\n		  <select id=\"view_group_<{$smarty.foreach.viewlist.iteration}>\" class=\'default_view_group\' name=\"defaultview_group[]\">\r\n			<{html_options options=$content.grouplist selected=$groupid}>\r\n		  </select>\r\n		  <select id=\"view_view_<{$smarty.foreach.viewlist.iteration}>\" class=\'default_view_list\' name=\"defaultview_view[]\">\r\n			<{html_options options=$content.viewoptions selected=$viewid}>\r\n		  </select></div>\r\n		<{/foreach}>\r\n	  </div>\r\n\r\n	  <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_DEFAULTVIEW}></div>\r\n	</div>     \r\n	<div class=\"save-view-access\">\r\n	<p><a href=\"<{$xoops_url}>/modules/formulize/master.php?fid=<{$content.fid}>&frid=<{$content.frid}>\" target=\"_blank\"><img src=\"../images/kedit.png\"> <{$smarty.const._AM_FORMULIZE_SCREEN_LOE_EDIT_VIEW}></a></p>\r\n	<p><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_EDIT_VIEW_DETAILS}></p>\r\n	</div>\r\n	\r\n	<div class=\"form-item clear-both\">\r\n        Fundamental Filters<br>\r\n        <div id=\'fundamentalfilters\'>\r\n            <br>\r\n            <{$content.fundamentalfilters}>\r\n        </div>\r\n        <div class=\'description\'>These filters will be applied in addition to any other filter settings and searches on the page. They cannot be altered by the end user and they are never shown to the end user. You can have the same saved view as the default view for multiple screens, and then modify what the user sees through these filters. New entries created through this list will have their values set based on any \'Match all\' filters that use the \'equals\' operator. Note: if you create multiple \'one or more\' filters, they will only work if they are on elements from the same form. Note also: dynamic references with { } are not supported.</div>\r\n    </div>\r\n    \r\n	<div class=\"form-item clear-both\">\r\n	  <label for=\"screens-usecurrentviewlist\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_CURRENTVIEWLIST}></label>\r\n	  <input type=\"text\" id=\"screens-usecurrentviewlist\" name=\"screens-usecurrentviewlist\" value=\"<{$content.usecurrentviewlist}>\" size=\"20\" maxlength=\"255\"/>\r\n	  <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK_LIST}></div>\r\n	</div>\r\n	<div class=\"form-item\">\r\n	  <label for=\"screens-limitviews\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_LIMITVIEWS}></label>\r\n	  <select id=\"screens-limitviews[]\" name=\"screens-limitviews[]\" size=\"8\" multiple>\r\n		<{html_options options=$content.limitviewoptions selected=$content.limitviews}>\r\n	  </select>\r\n	  <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_LIMITVIEWS}></div>\r\n	</div>\r\n    \r\n    \r\n    \r\n    <div class=\"form-item\">\r\n      <label for=\"screens-useworkingmsg\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_USEWORKING}></label>\r\n			<div class=\"form-radios\">\r\n			  <label for=\"1\"><input type=\"radio\" id=\"screens-useworkingmsg\" name=\"screens-useworkingmsg\"<{if $content.useworkingmsg eq 1}> checked=\"checked\"<{/if}> value=\"1\"/><{$smarty.const._YES}></label>\r\n		  </div>\r\n		  <div class=\"form-radios\">\r\n			  <label for=\"0\"><input type=\"radio\" id=\"screens-useworkingmsg\" name=\"screens-useworkingmsg\"<{if $content.useworkingmsg eq 0}> checked=\"checked\"<{/if}> value=\"0\"/><{$smarty.const._NO}></label>\r\n		  </div>\r\n      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_USEWORKING}></div>\r\n    </div>\r\n    <div class=\"form-item\">\r\n      <label for=\"screens-usescrollbox\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_USESCROLLBOX}></label>\r\n						<div class=\"form-radios\">\r\n			  <label for=\"1\"><input type=\"radio\" id=\"screens-usescrollbox\" name=\"screens-usescrollbox\"<{if $content.usescrollbox eq 1}> checked=\"checked\"<{/if}> value=\"1\"/><{$smarty.const._YES}></label>\r\n		  </div>\r\n		  <div class=\"form-radios\">\r\n			  <label for=\"0\"><input type=\"radio\" id=\"screens-usescrollbox\" name=\"screens-usescrollbox\"<{if $content.usescrollbox eq 0}> checked=\"checked\"<{/if}> value=\"0\"/><{$smarty.const._NO}></label>\r\n		  </div>\r\n      <div class=\"description\"></div>\r\n    </div>\r\n    <div class=\"form-item\">\r\n      <label for=\"screens-entriesperpage\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_ENTRIESPERPAGE}></label>\r\n      <input type=\"text\" id=\"screens-entriesperpage\" name=\"screens-entriesperpage\" value=\"<{$content.entriesperpage}>\" size=\"4\" maxlength=\"4\"/>\r\n      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_ENTRIESPERPAGE}></div>\r\n    </div>\r\n	<div class=\"form-item\">\r\n      <label for=\"screens-viewentryscreen\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_VIEWENTRYSCREEN}></label>\r\n	    <select id=\"screens-viewentryscreen\" name=\"screens-viewentryscreen\" size=\"1\">\r\n        <{html_options options=$content.viewentryscreenoptions selected=$content.viewentryscreen}>\r\n	    </select>\r\n      <div class=\"description\"></div>\r\n    </div>\r\n  </fieldset>\r\n</div>\r\n\r\n</form>\r\n\r\n<script type=\"text/javascript\">\r\n  \r\n  setInterval(function () {\r\n    $.ajax({\r\n      type:\"POST\",\r\n      url:\"<{$xoops_url}>/modules/formulize/formulize_xhr_responder.php?op=get_views_for_form&uid=<{$content.uid}>\",\r\n      data:{\"form_id\":\"<{$content.fid}>\"},\r\n      dataType: \"json\",\r\n      success:function(response){\r\n	$.each(response, function( index, value ) {\r\n            $(\".default_view_list\").each(function() {\r\n                if ($(this).find(\"option[value=\'\"+value[0]+\"\']\").length == 0) {\r\n                    $(this).append($(\'<option>\', { label : value[1], value : value[0] }).text(value[1].replace(\'\"\',\'\\\"\')));\r\n	  }\r\n	});\r\n        });\r\n      }\r\n    });\r\n  }, 8000);\r\n  \r\n  $(\"[name=add_view]\").click(function(){\r\n    oldNumber = $(\".default_view_group\").length;\r\n    newNumber = parseInt(oldNumber)+1;\r\n    group = $(\"#view_group_\"+oldNumber);\r\n    view = $(\"#view_view_\"+oldNumber);\r\n    appendContentsGroup =\'<select id=\"view_group_\'+newNumber+\'\" class=\"default_view_group\" name=\"defaultview_group[]\">\' + group.html().replace(\'selected=\"selected\"\', \'\') + \'</select>\';\r\n    appendContentsView = \'<select id=\"view_view_\'+newNumber+\'\" class=\"default_view_list\" name=\"defaultview_view[]\">\' + view.html().replace(\'selected=\"selected\"\', \'\') +\'</select>\';\r\n\r\n    $(\'div.view-list\').append(\'<div id=\"view_remove_\'+newNumber+\'\"><br /><img style=\"cursor: pointer;\" onclick=\"removeDefView(\'+newNumber+\')\" src=\"../images/editdelete.gif\">&nbsp;\');\r\n    $(\'div.view-list\').append(appendContentsGroup);\r\n    $(\'div.view-list\').append(\'&nbsp;\');\r\n    $(\'div.view-list\').append(appendContentsView);\r\n    $(\'div.view-list\').append(\'</div>\');\r\n    $(\"[name=add_view]\").blur();\r\n    setDisplay(\'savewarning\',\'block\');\r\n    });\r\n  \r\n  function removeDefView(number) {\r\n    $(\'#view_remove_\'+number).remove();\r\n  }\r\n  \r\n  function getFormColumns(number) {\r\n    <{foreach from=$content.advanceviewoptions key=index item=value}>\r\n	  $(\"#cols-\"+number).append($(\'<option>\', { \r\n	    value: \"<{$index}>\" }).text(\"<{$value|replace:\'\"\':\'\\\"\'}>\"));\r\n    <{/foreach}>\r\n  }\r\n  \r\n  function addAdvanceViewRow(number) {\r\n    appendContents1 = \'<td><select id=\"cols-\'+number+\'\" name=\"col-value[\'+number+\']\"></select></td>\';\r\n    appendContents2 = \'<td><input type=\"text\" name=\"search-value[\'+number+\']\"></input></td>\';\r\n    appendContents25 = \'<td><select id=\"search-type-\'+number+\'\" name=\"search-type[\'+number+\']\"><option label=\"Search Box\" value=\"Box\">Search Box</option><option label=\"Dropdown List\" value=\"Filter\">Dropdown List</option><option label=\"Checkboxes\" value=\"MultiFilter\">Checkboxes</option><option label=\"Date Range\" value=\"DateRange\">Date Range</option></select></td>\';\r\n    appendContents3 = \'<td><input type=\"radio\" name=\"sort-by\" value=\'+number+\'></input></td>\';\r\n    $(\".advanceview:last\").append(\'<tr class=\"advanceviewcol\" name=\"\'+number+\'\"></tr>\');\r\n    $(\".advanceviewcol:last\").append(appendContents1);\r\n    $(\".advanceviewcol:last\").append(appendContents2);\r\n    $(\".advanceviewcol:last\").append(appendContents25);\r\n    $(\".advanceviewcol:last\").append(appendContents3);\r\n    $(\".advanceviewcol:last\").append(\"<td class=\'removeImage\'></td>\");\r\n  }\r\n  \r\n  $(\"[name=addColumn]\").click(function (){\r\n    number = $(\".advanceviewcol:last\").attr(\'name\');\r\n    number = parseInt(number) + 1;\r\n    addAdvanceViewRow(number);\r\n    \r\n    //Append the remove column\r\n    appendContents4 = \'<img class=\"removeCol\" style=\"cursor: pointer;\" onclick=\"removeColumn(\'+number+\')\" src=\"../images/editdelete.gif\"></img>\';\r\n    $(\".removeImage:last\").append(appendContents4);\r\n    \r\n    //Populate the columns\r\n    getFormColumns(number);\r\n    \r\n    //Update the number of rows\r\n    rows = $(\"#numberOfRows\").val();\r\n    rows = parseInt(rows) + 1;\r\n    $(\"#numberOfRows\").val(rows);\r\n    \r\n    //$(\"#numberOfRows\").text($(\"#numberOfRows\").val() + 1);\r\n    $(\"[name=addColumn]\").blur();\r\n    setDisplay(\'savewarning\',\'block\');\r\n  });\r\n  \r\n  function removeColumn(id){\r\n    firstNumber = $(\".advanceviewcol:first\").attr(\'name\');\r\n    lastNumber = $(\".advanceviewcol:last\").attr(\'name\');\r\n    if(firstNumber == lastNumber) {\r\n      //Remove the row with past values but add another row with the default values\r\n      $(\"tr\").remove(\"[name=\"+id+\"]\");\r\n      addAdvanceViewRow(id);\r\n      getFormColumns(id);\r\n      $(\"#numberOfRows\").html(1);\r\n    }\r\n    else {\r\n      $(\"tr\").remove(\"[name=\"+id+\"]\");\r\n      \r\n      //Update the number of rows\r\n      rows = $(\"#numberOfRows\").val();\r\n      rows = parseInt(rows) - 1;\r\n      $(\"#numberOfRows\").val(rows);\r\n    }\r\n    \r\n    setDisplay(\'savewarning\',\'block\');\r\n  }\r\n  $(\"div#fundamentalfilters > input#addcon\").click(function () {\r\n	$(\"[name=reload_list_screen_page]\").val(1);\r\n  $(\".savebutton\").click();\r\n	return false;\r\n});\r\n  $(\"div#fundamentalfilters > a.conditionsdelete\").click(function () {\r\n	$(\".ffdelete\").val($(this).attr(\'target\'));\r\n	$(\"[name=reload_list_screen_page]\").val(1);\r\n  $(\".savebutton\").click();\r\n	return false;\r\n});\r\n  \r\n</script>'),
(155,'<p><a href=\"\" class=\"newcustombutton\"><img src=\"../images/filenew2.png\"> Add a custom button</a></p>\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"screen_list_custom\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.sid}>\">\r\n<input type=\"hidden\" name=\"reload_list_pages\" value=\"\">\r\n<input type=\"hidden\" name=\"newbutton\" value=>	\r\n<input type=\"hidden\" name=\"deletebutton\" value=>\r\n<input type=\"hidden\" name=\"neweffect\" value=\"\">\r\n<input type=\"hidden\" name=\"removeeffect\" value=>\r\n\r\n\r\n<{if $content.custombuttons|is_array AND $content.custombuttons|@count == 0}>\r\n		<h2>No custom buttons have been created for this screen</h2>\r\n<{/if}>\r\n\r\n<div>\r\n<{include file=\"db:admin/ui-accordion.html\" sectionTemplate=\"db:admin/screen_list_custom_sections.html\" sections=$content.custombuttons}>\r\n</div>\r\n\r\n</form>\r\n\r\n<script type=\'text/javascript\'>\r\n\r\n$(\".savebutton\").click(function() {\r\n  $(\".required_formulize_element\").each(function() {\r\n    if($(this).val() == \"\") {\r\n      alert(\"Buttons must have a handle, and text to appear on the button!\");\r\n      $(this).focus();\r\n    }\r\n	});\r\n});\r\n\r\n$(\"div.handle > input\").keydown(function () {\r\n	$(\"[name=reload_list_pages]\").val(1);\r\n})\r\n\r\n$(\".newcustombutton\").click(function () {\r\n	$(\"[name=newbutton]\").val(1);\r\n	$(\"[name=reload_list_pages]\").val(1);\r\n  $(\".savebutton\").click();\r\n	return false;\r\n});\r\n\r\n$(\".addeffectbutton\").click(function () {\r\n	$(\"[name=neweffect]\").val($(this).attr(\'target\'));\r\n	$(\"[name=reload_list_pages]\").val(1);\r\n  $(\".savebutton\").click();\r\n	return false;\r\n});\r\n\r\n$(\".removeeffectbutton\").click(function () {\r\n	$(\"[name=removeeffect]\").val($(this).attr(\'target\'));\r\n	$(\"[name=reload_list_pages]\").val(1);\r\n  $(\".savebutton\").click();\r\n	return false;\r\n});\r\n\r\n$(\".deletebutton\").click(function () {\r\n	$(\"[name=deletebutton]\").val($(this).attr(\'target\'));\r\n	$(\"[name=reload_list_pages]\").val(1);\r\n  $(\".savebutton\").click();\r\n	return false;\r\n});\r\n\r\n</script>'),
(156,'<p><a class=\"deletebutton\" href=\"\" target=\"<{$sectionContent.id}>\"><img src=\"../images/editdelete.gif\"> Delete this button</a></p>\r\n\r\n	<div class=\"form-item handle required\">\r\n		<label for=\"handle_<{$sectionContent.id}>\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_HANDLE}></label>\r\n		<input type=\"text\" id=\"handle_<{$sectionContent.id}>\" class=\"required_formulize_element\" name=\"handle_<{$sectionContent.id}>\" value=\"<{$sectionContent.handle}>\"/>\r\n	</div>\r\n	<div class=\"form-item required\">\r\n		<label for=\"text_<{$sectionContent.id}>\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_BUTTONTEXT}></label>\r\n		<input type=\"text\" id=\"text_<{$sectionContent.id}>\" class=\"required_formulize_element\" name=\"buttontext_<{$sectionContent.id}>\" value=\"<{$sectionContent.buttontext}>\"/>\r\n	</div>\r\n    \r\n    <div class=\"form-item\">\r\n		<label for=\"popup_<{$sectionContent.id}>\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_POPUPTEXT}></label>\r\n		<input type=\"text\" id=\"popup_<{$sectionContent.id}>\" name=\"popuptext_<{$sectionContent.id}>\" value=\"<{$sectionContent.popuptext}>\"/>\r\n	</div>\r\n    \r\n	<div class=\"form-item\">\r\n		<label for=\"message_<{$sectionContent.id}>\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_MESSAGETEXT}></label>\r\n		<input type=\"text\" id=\"message_<{$sectionContent.id}>\" name=\"messagetext_<{$sectionContent.id}>\" value=\"<{$sectionContent.messagetext}>\"/>\r\n	</div>\r\n	\r\n	<div class=\"form-item\">\r\n		<label for=\"groups_<{$sectionContent.id}>\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_GROUPS}></label>\r\n		<select name=\"groups_<{$sectionContent.id}>[]\" id=\"groups_<{$sectionContent.id}>\" size=8 multiple=\"multiple\">\r\n		<{html_options options=$content.grouplist selected=$sectionContent.groups}>\r\n		</select>\r\n	</div>\r\n	\r\n	<div class=\"form-item\">\r\n		<label><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_INLINE}></label>\r\n			<label for=\"inline_yes\"><input type=\"radio\" id=\"inline_yes\" name=\"appearinline_<{$sectionContent.id}>\" value=1 <{if $sectionContent.appearinline}>checked<{/if}> />Yes</label>\r\n			<label for=\"inline_no\"><input type=\"radio\" id=\"inline_no\" name=\"appearinline_<{$sectionContent.id}>\" value=0 <{if !$sectionContent.appearinline}>checked<{/if}> />No</label>\r\n			<div class=\"description\">\r\n				<p>If no, then the button will be available in the Top and Bottom Templates. If yes, the button will appear in the list, or will be available in the List Item Template if you use one.</p>\r\n			</div>\r\n	</div>\r\n	<div class=\"form-item\">\r\n		<label><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO}></label>\r\n		<select name=\"applyto_<{$sectionContent.id}>\" size=1>\r\n			<{html_options options=$content.applytoOptions selected=$sectionContent.applyto}>\r\n		</select>\r\n	</div>\r\n	<input type=\"button\" class=\"addeffectbutton\" target=\"<{$sectionContent.id}>\" value=\"<{$smarty.const._AM_FORMULIZE_SCREEN_LOE_ADDCUSTOMBUTTON_EFFECT}>\"/>\r\n\r\n	<{counter name=\"effects\" start=0 print=false}>\r\n	<{foreach from=$sectionContent key=id item=effect}>\r\n		<{if is_numeric($id)}>\r\n		<fieldset>\r\n			<legend><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT}> <{counter name=\"effects\"}></legend>\r\n			<div class=\"customeffect\">\r\n				<p><a class=\"removeeffectbutton\" href=\"\" target=\"<{$sectionContent.id}>_<{$id}>\"><img src=\"../images/editdelete.gif\"> Remove this effect</a></p>\r\n				<{if $sectionContent.applyto == \'custom_code\'}>\r\n				<textarea name=\"code_<{$sectionContent.id}>[<{$id}>]\" class=\"code-textarea\" rows=8><{$effect.code}></textarea>			\r\n				<{elseif $sectionContent.applyto == \'custom_html\'}>\r\n				<textarea name=\"html_<{$sectionContent.id}>[<{$id}>]\" class=\"code-textarea\" rows=8><{$effect.html}></textarea>				\r\n				<{else}>\r\n				<label><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ELEMENT}></label>\r\n				<select name=\"element_<{$sectionContent.id}>[<{$id}>]\" size=1>\r\n					<{html_options options=$effect.elementOptions selected=$effect.element}>\r\n				</select>\r\n				<label><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ACTION}></label>\r\n				<select name=\"action_<{$sectionContent.id}>[<{$id}>]\" size=1>\r\n					<{html_options options=$effect.actionOptions selected=$effect.action}>\r\n				</select>\r\n				<label><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_VALUE}></label>\r\n				<textarea name=\"value_<{$sectionContent.id}>[<{$id}>]\" class=\"code-textarea\" rows=4><{$effect.value}></textarea>		\r\n				<{/if}>\r\n				<div class=\"description\">\r\n					<p><{$effect.description}></p>\r\n				</div>\r\n			</div>\r\n		</fieldset>\r\n		\r\n		<{/if}>\r\n	<{/foreach}>'),
(157,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<{$securitytoken}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"screen_form_options\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.sid}>\">\r\n\r\n\r\n<div class=\"panel-content content\">\r\n  <fieldset>\r\n		<legend>Form Elements</legend>\r\n		<div class=\"form-item\">\r\n			<label for=\"screens-formelements\">Which form elements should be displayed?</label><br>\r\n			<select id=\"screens-formelements\" name=\"screens-formelements[]\" size=\"15\" multiple>\r\n			<{html_options options=$content.element_list selected=$content.formelements}>\r\n			</select>\r\n			<div class=\"description\">Leave this blank to display all form elements.</div>\r\n		</div>\r\n	</fieldset>\r\n    <fieldset>\r\n		<legend>Element Defaults</legend>\r\n		<div class=\"form-item\">\r\n			<p><label for=\"elementchoice\">Do you want to force certain elements to have certain default values when displayed in this screen?</label>\r\n            <br>\r\n			<select id=\"elementchoice\" name=\"elementchoice\" size=\"1\">\r\n			<{html_options options=$content.element_list}>\r\n			</select>\r\n            <input type=\"text\" id=\"elementdefault\" name=\"elementdefault\">\r\n            <input type=\"button\" id=\"edadd\" value=\"Add This Default\"></p>\r\n            <p id=\"addeddefaults\"></p>\r\n			<div class=\"description\">Defaults that you set for elements here, will take effect only on this screen. The elements will behave normally on other screens (if those screens do not have any defaults set of their own).</div>\r\n		</div>\r\n	</fieldset>\r\n  <fieldset>\r\n    <legend>Form Display</legend>\r\n		<div class=\"form-item\">\r\n		  <p><nobr><input type=\"checkbox\" id=\"screens-displayheading\" name=\"screens-displayheading\"<{if $content.displayheading}> checked=\"checked\"<{/if}> value=\"1\"/>&nbsp;&nbsp;Show all the headings at the top of the form (\"Entry created by so-and-so, on this date, etc\")</nobr></p>\r\n	  </div>\r\n        <div class=\"form-item\">\r\n            <p>Display the form elements as:<br>\r\n            <label for=\"onecolumn\"><input type=\'radio\' id=\"onecolumn\" name=\"screens-displaycolumns\" value=\"1\"/> a single column</label><br>\r\n            <label for=\"twocolumns\"><input type=\'radio\' id=\"twocolumns\" name=\"screens-displaycolumns\" value=\"2\"/> two columns</label>\r\n            </p>\r\n            <p>Width for column one: <input type=\'text\' name=\'screens-column1width\' value=\"<{$content.column1width}>\"><br>\r\n            Width for column two: <input type=\'text\' name=\'screens-column2width\' value=\"<{$content.column2width}>\"></p>\r\n            <div class=\"description\">The widths can be specified using any valid CSS value.</div>\r\n        </div>\r\n	</fieldset>\r\n	<fieldset>\r\n		<legend>Buttons, text and behaviour</legend>\r\n	  <div class=\"form-item\">\r\n		  <label for=\"screens-savebuttontext\">What text should be used on the <b>Save</b> button?</label>\r\n		  <input type=\"text\" id=\"screens-savebuttontext\" name=\"screens-savebuttontext\" value=\"<{$content.savebuttontext}>\"/>\r\n          <div class=\"description\">You can leave this blank, that will remove this button from the form.</div>\r\n	  </div>\r\n      \r\n		<div class=\"form-item\">\r\n		  <label for=\"screens-saveandleavebuttontext\">What text should be used on the <b>Save and Leave</b> button?</label>\r\n		  <input type=\"text\" id=\"screens-saveandleavebuttontext\" name=\"screens-saveandleavebuttontext\" value=\"<{$content.saveandleavebuttontext}>\"/>\r\n          <div class=\"description\">You can leave this blank, that will remove this button from the form.</div> \r\n	  </div>\r\n      \r\n      <div class=\"form-item\">\r\n		  <label for=\"screens-alldonebuttontext\">What text should be used on the <b>Leave Page</b> button?</label>\r\n		  <input type=\"text\" id=\"screens-alldonebuttontext\" name=\"screens-alldonebuttontext\" value=\"<{$content.alldonebuttontext}>\"/>\r\n          <div class=\"description\">You can leave this blank, that will remove this button from the form.</div> \r\n	  </div>\r\n      \r\n      <div class=\"form-item\">\r\n		  <p><label for=\"screens-donedest\">When users leave the form, where should they be sent?</label></p>\r\n				<div class=\"form-radios\">\r\n          <label for=\"default\"><input type=\"radio\" id=\"default\" name=\"leavebehaviour0\" value=0 />Back to the where they came from</label>\r\n        </div>\r\n        <div class=\"form-radios\">\r\n          <label for=\"url\"><input type=\"radio\" id=\"url\" name=\"leavebehaviour0\" value=1 />To this URL: </label><input type=\"text\" id=\"screens-donedest\" name=\"screens-donedest\" value=\"<{$content.donedest}>\"/>\r\n          <div class=\"description\">If you are sending users to a location on this site, then you don\'t have to type the root part of the site\'s URL.  Just start the destination with a slash, ie: /modules/formulize/index.php?sid=12</div>\r\n        </div>\r\n	  </div>\r\n        \r\n	  <div class=\"form-item\">\r\n		  <p><label for=\"screens-printableviewbuttontext\">What text should be used on the <b>Printable View</b> button?</label></p>\r\n		  <input type=\"text\" id=\"screens-printableviewbuttontext\" name=\"screens-printableviewbuttontext\" value=\"<{$content.printableviewbuttontext}>\"/>\r\n          <div class=\"description\">You can leave this blank, that will remove this button from the form.</div> \r\n	  </div>\r\n        \r\n	  <div class=\"form-item\">\r\n		  <p><label for=\"screens-reloadblank\">How should the form reload, after the user has saved a <b>new</b> entry?</label></p>\r\n				<div class=\"form-radios\">\r\n          <label for=\"entry\"><input type=\"radio\" id=\"entry\" name=\"screens-reloadblank\" value=0 />Reload showing the entry the user has just created</label>\r\n        </div>\r\n        <div class=\"form-radios\">\r\n          <label for=\"blank\"><input type=\"radio\" id=\"blank\" name=\"screens-reloadblank\" value=1 />Reload blank, so another new entry can be created</label>\r\n          <div class=\"description\">When a user lands on a form screen from a list of entries, this setting will be ignored and the button the user clicked will take precedence (either the <b>Add one entry</b> button or the <b>Add multiple entries</b> button).</div>\r\n	  </div>\r\n		</div>\r\n	</fieldset>\r\n	\r\n</div>\r\n\r\n</form>\r\n\r\n<style>\r\n    .adefault:hover {\r\n        color: red;\r\n        text-decoration: line-through;\r\n        cursor: pointer;\r\n    }\r\n</style>\r\n\r\n<script type=\"text/javascript\">\r\n    \r\n    $(\"#edadd\").click(function() {\r\n        appendDefault($(\'#elementchoice\').val(), $(\'#elementchoice\').children(\'option:selected\').text(), $(\'#elementdefault\').val().replace(/\"/g, \'&quot;\'));\r\n        $(\'#elementdefault\').val(\'\');       \r\n    });\r\n    $(\'.adefault\').live(\'click\', function() {\r\n        $(\'#\'+$(this).attr(\'id\').replace(\'def\',\'hidden\')).remove();\r\n        $(this).remove();\r\n        setDisplay(\'savewarning\',\'block\');\r\n    });\r\n\r\n    <{foreach from=$content.elementdefaults key=id item=defaultText}>\r\n    <{if $id}>\r\n    appendDefault(<{$id}>, \'<{$content.element_list.$id}>\', \"<{$defaultText|replace:\'\"\':\'&quot;\'}>\");\r\n    <{/if}>\r\n    <{/foreach}>\r\n    \r\n    \r\n    function appendDefault(id, elementText, defaultText) {\r\n        $(\'#addeddefaults\').append(\'<input type=\"hidden\" name=\"screens-elementdefaults[\'+id+\']\" id=\"hidden_\'+id+\'\" value=\"\'+defaultText+\'\"><span class=\"adefault\" id=\"def_\'+id+\'\">\'+elementText+\' >> \'+defaultText+\'<br></span>\');\r\n        \r\n    }\r\n    \r\n    \r\n	$(\"#<{$content.reloadblank}>\").attr(\'checked\', true);\r\n    $(\"#<{$content.leavebehaviour}>\").attr(\'checked\', true);\r\n    $(\"#<{$content.displaycolumns}>\").attr(\'checked\', true);\r\n    $(\"#url\").click(function() {\r\n        $(\"#screens-donedest\").focus();\r\n    });\r\n    $(\"#screens-donedest\").click(function() {\r\n       $(\"#url\").attr(\'checked\', true);\r\n    });\r\n</script>'),
(158,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<{$securitytoken}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"screen_list_buttons\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.sid}>\">\r\n\r\n\r\n<div class=\"panel-content content\">\r\n  <fieldset>\r\n    <legend>Text for the action buttons</legend>\r\n	      <div class=\"form-item\">\r\n		      <label for=\"screens-useaddupdate\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON1}><{$smarty.const._formulize_DE_ADDENTRY}>/<{$smarty.const._formulize_DE_UPDATEENTRY}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON2}></label>\r\n		      <input type=\"text\" id=\"screens-useaddupdate\" name=\"screens-useaddupdate\" value=\"<{$content.useaddupdate}>\" size=\"20\" maxlength=\"255\"/>\r\n		      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK}></div>\r\n	      </div>\r\n	      <div class=\"form-item\">\r\n		      <label for=\"screens-useaddmultiple\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON1}><{$smarty.const._formulize_DE_ADD_MULTIPLE_ENTRY}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON2}></label>\r\n		      <input type=\"text\" id=\"screens-useaddmultiple\" name=\"screens-useaddmultiple\" value=\"<{$content.useaddmultiple}>\" size=\"20\" maxlength=\"255\"/>\r\n		      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK}></div>\r\n	      </div>\r\n	      <div class=\"form-item\">\r\n		      <label for=\"screens-useaddproxy\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON1}><{$smarty.const._formulize_DE_PROXYENTRY}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON2}></label>\r\n		      <input type=\"text\" id=\"screens-useaddproxy\" name=\"screens-useaddproxy\" value=\"<{$content.useaddproxy}>\" size=\"20\" maxlength=\"255\"/>\r\n		      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK}></div>\r\n	      </div>\r\n	      <div class=\"form-item\">\r\n		      <label for=\"screens-useexport\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON1}><{$smarty.const._formulize_DE_EXPORT}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON2}></label>\r\n		      <input type=\"text\" id=\"screens-useexport\" name=\"screens-useexport\" value=\"<{$content.useexport}>\" size=\"20\" maxlength=\"255\"/>\r\n		      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK}></div>\r\n	      </div>\r\n	      <div class=\"form-item\">\r\n		      <label for=\"screens-useimport\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON1}><{$smarty.const._formulize_DE_IMPORT}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON2}></label>\r\n		      <input type=\"text\" id=\"screens-useimport\" name=\"screens-useimport\" value=\"<{$content.useimport}>\" size=\"20\" maxlength=\"255\"/>\r\n		      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK}></div>\r\n	      </div>\r\n	      <div class=\"form-item\">\r\n		      <label for=\"screens-usenotifications\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON1}><{$smarty.const._formulize_DE_NOTBUTTON}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON2}></label>\r\n		      <input type=\"text\" id=\"screens-usenotifications\" name=\"screens-usenotifications\" value=\"<{$content.usenotifications}>\" size=\"20\" maxlength=\"255\"/>\r\n		      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK}></div>\r\n	      </div>\r\n	      <div class=\"form-item\">\r\n		      <label for=\"screens-usechangecols\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON1}><{$smarty.const._formulize_DE_CHANGECOLS}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON2}></label>\r\n		      <input type=\"text\" id=\"screens-usechangecols\" name=\"screens-usechangecols\" value=\"<{$content.usechangecols}>\" size=\"20\" maxlength=\"255\"/>\r\n		      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK}></div>\r\n	      </div>\r\n	      <div class=\"form-item\">\r\n		      <label for=\"screens-usecalcs\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON1}><{$smarty.const._formulize_DE_CALCS}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON2}></label>\r\n		      <input type=\"text\" id=\"screens-usecalcs\" name=\"screens-usecalcs\" value=\"<{$content.usecalcs}>\" size=\"20\" maxlength=\"255\"/>\r\n		      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK}></div>\r\n	      </div>\r\n	      <div class=\"form-item\">\r\n		      <label for=\"screens-useadvcalcs\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON1}><{$smarty.const._formulize_DE_ADVCALCS}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON2}></label>\r\n		      <input type=\"text\" id=\"screens-useadvcalcs\" name=\"screens-useadvcalcs\" value=\"<{$content.useadvcalcs}>\" size=\"20\" maxlength=\"255\"/>\r\n		      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK}></div>\r\n	      </div>\r\n	      <div class=\"form-item\">\r\n		      <label for=\"screens-useexportcalcs\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON1}><{$smarty.const._formulize_DE_EXPORT_CALCS}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON2}></label>\r\n		      <input type=\"text\" id=\"screens-useexportcalcs\" name=\"screens-useexportcalcs\" value=\"<{$content.useexportcalcs}>\" size=\"20\" maxlength=\"255\"/>\r\n		      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK}></div>\r\n	      </div>\r\n	      <div class=\"form-item\">\r\n		      <label for=\"screens-useclone\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON1}><{$smarty.const._formulize_DE_CLONESEL}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON2}></label>\r\n		      <input type=\"text\" id=\"screens-useclone\" name=\"screens-useclone\" value=\"<{$content.useclone}>\" size=\"20\" maxlength=\"255\"/>\r\n		      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK}></div>\r\n	      </div>\r\n	      <div class=\"form-item\">\r\n		      <label for=\"screens-usedelete\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON1}><{$smarty.const._formulize_DE_DELETESEL}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON2}></label>\r\n		      <input type=\"text\" id=\"screens-usedelete\" name=\"screens-usedelete\" value=\"<{$content.usedelete}>\" size=\"20\" maxlength=\"255\"/>\r\n		      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK}></div>\r\n	      </div>\r\n	      <div class=\"form-item\">\r\n		      <label for=\"screens-useselectall\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON1}><{$smarty.const._formulize_DE_SELALL}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON2}></label>\r\n		      <input type=\"text\" id=\"screens-useselectall\" name=\"screens-useselectall\" value=\"<{$content.useselectall}>\" size=\"20\" maxlength=\"255\"/>\r\n		      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK}></div>\r\n	      </div>\r\n	      <div class=\"form-item\">\r\n		      <label for=\"screens-useclearall\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON1}><{$smarty.const._formulize_DE_CLEARALL}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON2}></label>\r\n		      <input type=\"text\" id=\"screens-useclearall\" name=\"screens-useclearall\" value=\"<{$content.useclearall}>\" size=\"20\" maxlength=\"255\"/>\r\n		      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK}></div>\r\n	      </div>\r\n	      <div class=\"form-item\">\r\n		      <label for=\"screens-usereset\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON1}><{$smarty.const._formulize_DE_RESETVIEW}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON2}></label>\r\n		      <input type=\"text\" id=\"screens-usereset\" name=\"screens-usereset\" value=\"<{$content.usereset}>\" size=\"20\" maxlength=\"255\"/>\r\n		      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK}></div>\r\n	      </div>\r\n	      <div class=\"form-item\">\r\n		      <label for=\"screens-usesave\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON1}><{$smarty.const._formulize_DE_SAVE}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON2}></label>\r\n		      <input type=\"text\" id=\"screens-usesave\" name=\"screens-usesave\" value=\"<{$content.usesave}>\" size=\"20\" maxlength=\"255\"/>\r\n		      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK}></div>\r\n	      </div>\r\n	      <div class=\"form-item\">\r\n		      <label for=\"screens-usedeleteview\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON1}><{$smarty.const._formulize_DE_DELETE}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_BUTTON2}></label>\r\n		      <input type=\"text\" id=\"screens-usedeleteview\" name=\"screens-usedeleteview\" value=\"<{$content.usedeleteview}>\" size=\"20\" maxlength=\"255\"/>\r\n		      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK}></div>\r\n	      </div>\r\n      \r\n    </fieldset>\r\n</div>\r\n\r\n</form>'),
(159,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we are inserting/updating on *}>\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n    <{$securitytoken}>\r\n    <input type=\"hidden\" name=\"formulize_admin_handler\" value=\"screen_list_templates\">\r\n    <input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.sid}>\">\r\n\r\n    <div class=\"panel-content content\">\r\n        \r\n        <p><b>Showing templates for use with this theme:</b> <{html_options name=screens-theme options=$content.themes selected=$content.selectedTheme}></p>\r\n        <p><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_TOPTEMPLATE3}></p><br />\r\n        \r\n        <{if !$content.usingTemplates}>\r\n            <p><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_SEEDTEMPLATES1}><{$content.seedtemplates}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_SEEDTEMPLATES2}></p><br /><p><input type=\"button\" value=\"<{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_SEEDTEMPLATES3}>\" id=\"seedtemplates\"></p><br />        \r\n        <{/if}>\r\n        \r\n        <{if $content.usingTemplates}>       \r\n        \r\n        <p><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_TOPTEMPLATE}></p>\r\n        <p><a class=\"formulize-open-close-link\" open-text=\"View template documentation\" close-text=\"Hide template documentation\" linked-block-id=\"#formulize-template-documentation\">View template documentation</a> about how to include buttons and other interface elements.</p><br />\r\n        <div id=\"formulize-template-documentation\" style=\"display:none;\">\r\n            <fieldset>\r\n                <legend>Template Documentation</legend>\r\n                <{$smarty.const._AM_FORMULIZE_SCREEN_LOE_TEMPLATEINTRO2}>\r\n            </fieldset>\r\n            <br />\r\n        </div>\r\n\r\n        <div class=\"form-item\">\r\n            <fieldset>\r\n                <legend>Top Template</legend>\r\n                <textarea id=\"screens-toptemplate\"  name=\"screens-toptemplate\" class=\"code-textarea\" rows=\"20\"/>\r\n<{$content.toptemplate}></textarea>\r\n            </fieldset>\r\n        </div>\r\n\r\n        <div class=\"form-item\">\r\n            <fieldset>\r\n                <legend>Open List Template</legend>\r\n                <textarea id=\"screens-openlisttemplate\"  name=\"screens-openlisttemplate\" class=\"code-textarea\" rows=\"20\"/>\r\n<{$content.openlisttemplate}></textarea>\r\n            </fieldset>\r\n        </div>\r\n\r\n        <div class=\"form-item\">\r\n            <fieldset>\r\n                <legend>List Item Template</legend>\r\n                <textarea id=\"screens-listtemplate\" name=\"screens-listtemplate\" class=\"code-textarea\" rows=\"20\"/>\r\n<{$content.listtemplate}></textarea>\r\n                <br />\r\n                <a name=\"elementhandles\"></a>\r\n                <p><a class=\"formulize-open-close-link\" open-text=\"View Available Variables\" close-text=\"Hide Available Variables\" linked-block-id=\"#formulize-open-close-section\">View Available Variables</a></p>\r\n                <div id=\"formulize-open-close-section\" style=\"display:none;\">\r\n                    <br />\r\n                    <table>\r\n                        <tr><th>Form element</th><th>handle</th></tr>\r\n                        <{foreach from=$content.listtemplatehelp item=row}>\r\n                            <{$row}>\r\n                        <{/foreach}>\r\n                    </table>\r\n                </div>\r\n            </fieldset>\r\n        </div>\r\n\r\n        <div class=\"form-item\">\r\n            <fieldset>\r\n                <legend>Close List Template</legend>\r\n                <textarea id=\"screens-closelisttemplate\"  name=\"screens-closelisttemplate\" class=\"code-textarea\" rows=\"20\"/>\r\n<{$content.closelisttemplate}></textarea>\r\n            </fieldset>\r\n        </div>\r\n\r\n        <div class=\"form-item\">\r\n            <fieldset>\r\n            <legend>Bottom Template</legend>\r\n                <textarea id=\"screens-bottomtemplate\" name=\"screens-bottomtemplate\" class=\"code-textarea\" rows=\"20\"/>\r\n<{$content.bottomtemplate}></textarea>\r\n            </fieldset>\r\n        </div>\r\n\r\n        <{/if}>\r\n\r\n    </div>\r\n</form>\r\n<script>\r\njQuery(document).ready(function() {\r\n    jQuery(\".savebutton\").click(function() {\r\n        fz_check_php_code(jQuery(\"#screens-toptemplate\").val(), \"Top template\", \"<{$icms_url}>\", <{$icms_userid}>);\r\n        fz_check_php_code(jQuery(\"#screens-listtemplate\").val(), \"List Item template\", \"<{$icms_url}>\", <{$icms_userid}>);\r\n        fz_check_php_code(jQuery(\"#screens-bottomtemplate\").val(), \"Bottom template\", \"<{$icms_url}>\", <{$icms_userid}>);\r\n        fz_check_php_code(jQuery(\"#screens-openlisttemplate\").val(), \"Open List template\", \"<{$icms_url}>\", <{$icms_userid}>);\r\n        fz_check_php_code(jQuery(\"#screens-closelisttemplate\").val(), \"Close List template\", \"<{$icms_url}>\", <{$icms_userid}>);\r\n    });\r\n});\r\n</script>'),
(160,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<{$securitytoken}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"screen_list_headings\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.sid}>\">\r\n\r\n\r\n<div class=\"panel-content content\">\r\n	<p><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_CONFIG_SECTION2}></p>\r\n  <fieldset>\r\n    <legend>Options for the headings and interface elements of the list</legend>\r\n\r\n    <div class=\"form-item\">\r\n      <label for=\"screens-useheadings\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_USEHEADINGS}></label>\r\n			<div class=\"form-radios\">\r\n			  <label for=\"1\"><input type=\"radio\" id=\"screens-useheadings\" name=\"screens-useheadings\"<{if $content.useheadings eq 1}> checked=\"checked\"<{/if}> value=\"1\"/><{$smarty.const._YES}></label>\r\n		  </div>\r\n		  <div class=\"form-radios\">\r\n			  <label for=\"0\"><input type=\"radio\" id=\"screens-useheadings\" name=\"screens-useheadings\"<{if $content.useheadings eq 0}> checked=\"checked\"<{/if}> value=\"0\"/><{$smarty.const._NO}></label>\r\n		  </div>\r\n      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_USEHEADINGS}></div>\r\n    </div>\r\n    <div class=\"form-item\">\r\n      <label for=\"screens-repeatheaders\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_REPEATHEADERS}></label>\r\n      <input type=\"text\" id=\"screens-repeatheaders\" name=\"screens-repeatheaders\" value=\"<{$content.repeatheaders}>\" size=\"2\" maxlength=\"2\"/>\r\n      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_REPEATHEADERS}></div>\r\n    </div>\r\n    <div class=\"form-item\">\r\n      <label for=\"screens-usesearchcalcmsgs\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_USESEARCHCALCMSGS}></label>\r\n		  <div class=\"form-radios\">\r\n              <label for=\"1\"><input type=\"radio\" id=\"screens-usesearchcalcmsgs\" name=\"screens-usesearchcalcmsgs\"<{if $content.usesearchcalcmsgs eq 1 or $content.usesearchcalcmsgs eq 3}> checked=\"checked\"<{/if}> value=\"1\"/><{$smarty.const._YES}></label>\r\n		  </div>\r\n		  <div class=\"form-radios\">\r\n              <label for=\"0\"><input type=\"radio\" id=\"screens-usesearchcalcmsgs\" name=\"screens-usesearchcalcmsgs\"<{if $content.usesearchcalcmsgs eq 0 or $content.usesearchcalcmsgs eq 2}> checked=\"checked\"<{/if}> value=\"0\"/><{$smarty.const._NO}></label>\r\n		  </div>\r\n      <div class=\"description\"></div>\r\n    </div>                \r\n    <div class=\"form-item\">\r\n      <label for=\"screens-usesearch\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_USESEARCH}></label>\r\n      <div class=\"form-radios\">\r\n			  <label for=\"1\"><input type=\"radio\" id=\"screens-usesearch\" name=\"screens-usesearch\"<{if $content.usesearch eq 1}> checked=\"checked\"<{/if}> value=\"1\"/><{$smarty.const._YES}></label>\r\n		  </div>\r\n    <{php}>\r\n        global $xoopsConfig;\r\n    if($xoopsConfig[\'theme_set\']!=\'formulize_standalone\') {\r\n    <{/php}>\r\n          <div class=\"form-radios\">\r\n			  <label for=\"2\"><input type=\"radio\" id=\"screens-usesearch\" name=\"screens-usesearch\"<{if $content.usesearch eq 2}> checked=\"checked\"<{/if}> value=\"2\"/>Yes, but hide the row, user can open with a click</label>\r\n		  </div>\r\n    <{php}>\r\n        }\r\n    <{/php}>\r\n		  <div class=\"form-radios\">\r\n			  <label for=\"0\"><input type=\"radio\" id=\"screens-usesearch\" name=\"screens-usesearch\"<{if $content.usesearch eq 0}> checked=\"checked\"<{/if}> value=\"0\"/><{$smarty.const._NO}></label>\r\n		  </div>\r\n          \r\n      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_USESEARCH}></div>\r\n    </div>\r\n    <div class=\"form-item\">\r\n      <label for=\"screens-columnwidth\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_COLUMNWIDTH}></label>\r\n      <input type=\"text\" id=\"screens-columnwidth\" name=\"screens-columnwidth\" value=\"<{$content.columnwidth}>\" size=\"3\" maxlength=\"3\"/>\r\n      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_COLUMNWIDTH}></div>\r\n    </div>\r\n    <div class=\"form-item\">\r\n      <label for=\"screens-textwidth\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_TEXTWIDTH}></label>\r\n      <input type=\"text\" id=\"screens-textwidth\" name=\"screens-textwidth\" value=\"<{$content.textwidth}>\" size=\"3\" maxlength=\"3\"/>\r\n      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_TEXTWIDTH}></div>\r\n    </div>\r\n    <div class=\"form-item\">\r\n      <label for=\"screens-usecheckboxes\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_USECHECKBOXES}></label>\r\n		  <div class=\"form-radios\">\r\n			  <label for=\"0\"><input type=\"radio\" id=\"screens-usecheckboxes\" name=\"screens-usecheckboxes\"<{if $content.usecheckboxes eq 0}> checked=\"checked\"<{/if}> value=\"0\"/><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_UCHDEFAULT}></label>\r\n		  </div>\r\n		  <div class=\"form-radios\">\r\n			  <label for=\"1\"><input type=\"radio\" id=\"screens-usecheckboxes\" name=\"screens-usecheckboxes\"<{if $content.usecheckboxes eq 1}> checked=\"checked\"<{/if}> value=\"1\"/><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_UCHALL}></label>\r\n		  </div>\r\n		  <div class=\"form-radios\">\r\n			  <label for=\"2\"><input type=\"radio\" id=\"screens-usecheckboxes\" name=\"screens-usecheckboxes\"<{if $content.usecheckboxes eq 2}> checked=\"checked\"<{/if}> value=\"2\"/><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_UCHNONE}></label>\r\n		  </div>\r\n      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_USECHECKBOXES}></div>\r\n    </div>\r\n    <div class=\"form-item\">\r\n      <label for=\"screens-useviewentrylinks\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_USEVIEWENTRYLINKS}></label>\r\n      <div class=\"form-radios\">\r\n			  <label for=\"1\"><input type=\"radio\" id=\"screens-useviewentrylinks\" name=\"screens-useviewentrylinks\"<{if $content.useviewentrylinks eq 1}> checked=\"checked\"<{/if}> value=\"1\"/><{$smarty.const._YES}></label>\r\n		  </div>\r\n		  <div class=\"form-radios\">\r\n			  <label for=\"0\"><input type=\"radio\" id=\"screens-useviewentrylinks\" name=\"screens-useviewentrylinks\"<{if $content.useviewentrylinks eq 0}> checked=\"checked\"<{/if}> value=\"0\"/><{$smarty.const._NO}></label>\r\n		  </div>\r\n      <div class=\"description\"></div>\r\n    </div>\r\n    <div class=\"form-item\">\r\n      <label for=\"screens-hiddencolumns\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_HIDDENCOLUMNS}></label>\r\n	    <select id=\"screens-hiddencolumns[]\" name=\"screens-hiddencolumns[]\" size=\"10\" multiple>\r\n        <{html_options options=$content.elementoptions selected=$content.hiddencolumns}>\r\n	    </select>\r\n      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_HIDDENCOLUMNS}></div>\r\n    </div>\r\n  </fieldset>\r\n</div>\r\n\r\n<div class=\"panel-content content\">\r\n  <fieldset>\r\n    <legend>Options for columns where you would like the data displayed as a form element</legend>\r\n\r\n   <div class=\"form-item\">\r\n      <label for=\"screens-decolumns\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DECOLUMNS}></label>\r\n      <select id=\"screens-decolumns[]\" name=\"screens-decolumns[]\" size=\"10\" multiple>\r\n        <{html_options options=$content.elementoptions selected=$content.decolumns}>\r\n      </select>\r\n      <div class=\"description\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_DECOLUMNS}></div>\r\n    </div>\r\n\r\n    <div class=\"form-item\">\r\n      <label for=\"screens-dedisplay\">How should the form elements be displayed?</label>\r\n      <div class=\"form-radios\">\r\n	      <label for=\"0\"><input type=\"radio\" id=\"screens-dedisplay\" name=\"screens-dedisplay\"<{if $content.dedisplay eq 0}> checked=\"checked\"<{/if}> value=\"0\"/>Display them all at once when the list loads</label>\r\n      </div>\r\n      <div class=\"form-radios\">\r\n	      <label for=\"1\"><input type=\"radio\" id=\"screens-dedisplay\" name=\"screens-dedisplay\"<{if $content.dedisplay eq 1}> checked=\"checked\"<{/if}> value=\"1\"/>Display them when the user clicks an icon to activate them &mdash; if users click to activate, then you should turn OFF the security token in the <a href=\"../../system/admin.php?fct=preferences&op=showmod&mod=<{$adminPage.formulizeModId}>\" target=\"_blank\">Formulize Preferences</a></label>\r\n      </div>\r\n      <div class=\"description\"></div>\r\n    </div>\r\n\r\n    <div class=\"form-item\">\r\n      <label for=\"screens-desavetext\"><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESAVETEXT}></label>\r\n      <input type=\"text\" id=\"screens-desavetext\" name=\"screens-desavetext\" value=\"<{$content.desavetext}>\" size=\"20\" maxlength=\"255\"/>\r\n      <div class=\"description\"></div>\r\n    </div>\r\n  </fieldset>\r\n</div>\r\n\r\n</form>'),
(161,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<{$securitytoken}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"screen_multipage_options\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.sid}>\">\r\n\r\n\r\n<div class=\"panel-content content\">\r\n	<fieldset>\r\n        <legend>Appearance</legend>\r\n        \r\n        <div class=\"form-item\">\r\n            <input id=\"navstyletabs\" type=\"checkbox\" name=\"navstyletabs\" value=1 <{$content.navstyletabs}>> <label for=\"navstyletabs\">Show navigation tabs at the top of the form</label><br>\r\n            <input id=\"navstylebuttons\" type=\"checkbox\" name=\"navstylebuttons\" value=1 <{$content.navstylebuttons}>> <label for=\"navstylebuttons\">Show navigation buttons at the bottom of the form</label><br>\r\n            <input id=\"showpageindicator\" type=\"checkbox\" name=\"screens-showpageindicator\" value=1 <{$content.showpageindicator}>> <label for=\"showpageindicator\">Show \'page 1 of X\'</label><br>\r\n            <input id=\"showpageselector\" type=\"checkbox\" name=\"screens-showpageselector\" value=1 <{$content.showpageselector}>> <label for=\"showpageselector\">Show the page selection dropdown list</label><br>\r\n            <input id=\"showpagetitles\" type=\"checkbox\" name=\"screens-showpagetitles\" value=1 <{$content.showpagetitles}>> <label for=\"showpagetitles\">Show the page title at the top of the form</label>\r\n	    </div>\r\n        \r\n        <div class=\"form-item\">\r\n            <p>Layout Options:<br>\r\n            <table style=\'border: none; width: auto; margin-top: 0.5em;\'>\r\n                <tr>\r\n                    <td><label for=\"onecolumn\"><input type=\'radio\' id=\"onecolumn\" name=\"screens-displaycolumns\" value=\"1\" /> One Column</label></td>\r\n                    <td style=\'border-left: 1px solid;\'><label for=\"twocolumns\"><input type=\'radio\' id=\"twocolumns\" name=\"screens-displaycolumns\" value=\"2\"/> Two Columns</label></td>\r\n                    <td></td>\r\n                </tr>\r\n                <tr>\r\n                    <td><center>Width:<br>\r\n                        <input type=\'text\' name=\'singlecolumn1width\' size=5 value=\"<{$content.column1width}>\"></center></td>\r\n                    <td style=\'border-left: 1px solid;\'><center>Col. 1 Width:<br>\r\n                        <input type=\'text\' name=\'doublecolumn1width\' size=5 value=\"<{$content.column1width}>\"></center></td>\r\n                    <td><center>Col. 2 Width:<br>\r\n                        <input type=\'text\' name=\'screens-column2width\' size=5 value=\"<{$content.column2width}>\"></center></td>\r\n                </tr>\r\n            </table>\r\n            <div class=\"description\">Two column layout will compress to one column on phones. The widths can be specified using any valid CSS value, \'auto\' is recommended for column two.</div>\r\n        </div>\r\n    </fieldset>\r\n	<fieldset>\r\n		<legend>Buttons, text and behaviour</legend>\r\n	  <div class=\"form-item\">\r\n		<label for=\"screens-finishisdone\"><{$smarty.const._AM_FORMULIZE_SCREEN_FINISHISDONE}></label><br>\r\n		<input type=\"radio\" id=screens-finishisdone-0 name=\"screens-finishisdone\" value=0> <{$smarty.const._AM_FORMULIZE_SCREEN_FINISHISDONE_THANKSPAGE}><br />\r\n		<input type=\"radio\" id=screens-finishisdone-1 name=\"screens-finishisdone\" value=1> <{$smarty.const._AM_FORMULIZE_SCREEN_FINISHISDONE_FINISHBUTTON}>\r\n	  </div><br><br>\r\n      \r\n	  <div class=\"form-item\">\r\n		  <label for=\"screens-donedest\"><{$smarty.const._AM_FORMULIZE_SCREEN_DONEDEST}></label><br>\r\n      <input type=\"text\" id=\"screens-donedest\" name=\"screens-donedest\" value=\"<{$content.donedest}>\" size=\"50\" maxlength=\"255\"/><br><br>\r\n	  </div>\r\n      \r\n      <div class=\"form-item\">\r\n            <p>How should the form reload, after the user has saved a <b>new</b> entry?</p>\r\n            <div class=\"form-radios\">\r\n              <label for=\"entry\"><input type=\"radio\" id=\"entry\" name=\"screens-reloadblank\" value=0 />Reload showing the entry the user has just created</label>\r\n            </div>\r\n            <div class=\"form-radios\">\r\n              <label for=\"blank\"><input type=\"radio\" id=\"blank\" name=\"screens-reloadblank\" value=1 />Reload blank, so another new entry can be created</label>\r\n            <div class=\"description\">Only applies to forms with one page. When a user arrives from a list of entries, this setting will be ignored and the button the user clicked will take precedence (either the <b>Add one entry</b> button or the <b>Add multiple entries</b> button).</div>\r\n            </div>\r\n      </div>\r\n      <br><hr><br>\r\n	  <div class=\"form-item\">\r\n		  <label for=\"thankyoulinktext\"><{$smarty.const._AM_FORMULIZE_SCREEN_THANKYOULINKTEXT}></label><br>\r\n      <input type=\"text\" id=\"thankyoulinktext\" name=\"thankyoulinktext\" value=\"<{$content.thankyoulinktext}>\" size=\"50\" maxlength=\"255\"/><br><br>\r\n      	  <div class=\"form-item\">\r\n		  <label for=\"leaveButtonText\"><{$smarty.const._AM_FORMULIZE_SCREEN_LEAVEBUTTONTEXT}></label><br>\r\n      <input type=\"text\" id=\"leaveButtonText\" name=\"leaveButtonText\" value=\"<{$content.leaveButtonText}>\" size=\"50\" maxlength=\"255\"/><br><br>\r\n	  </div>\r\n	  <div class=\"form-item\">\r\n		  <label for=\"prevButtonText\"><{$smarty.const._AM_FORMULIZE_SCREEN_PREVBUTTONTEXT}></label><br>\r\n      <input type=\"text\" id=\"prevButtonText\" name=\"prevButtonText\" value=\"<{$content.prevButtonText}>\" size=\"50\" maxlength=\"255\"/><br><br>\r\n	  </div>\r\n	  <div class=\"form-item\">\r\n		  <label for=\"saveButtonText\"><{$smarty.const._AM_FORMULIZE_SCREEN_SAVEBUTTONTEXT}></label><br>\r\n      <input type=\"text\" id=\"saveButtonText\" name=\"saveButtonText\" value=\"<{$content.saveButtonText}>\" size=\"50\" maxlength=\"255\"/><br><br>\r\n	  </div>\r\n	  <div class=\"form-item\">\r\n		  <label for=\"nextButtonText\"><{$smarty.const._AM_FORMULIZE_SCREEN_NEXTBUTTONTEXT}></label><br>\r\n      <input type=\"text\" id=\"nextButtonText\" name=\"nextButtonText\" value=\"<{$content.nextButtonText}>\" size=\"50\" maxlength=\"255\"/><br><br>\r\n	  </div>\r\n	  <div class=\"form-item\">\r\n		  <label for=\"finishButtonText\"><{$smarty.const._AM_FORMULIZE_SCREEN_FINISHBUTTONTEXT}></label><br>\r\n      <input type=\"text\" id=\"finishButtonText\" name=\"finishButtonText\" value=\"<{$content.finishButtonText}>\" size=\"50\" maxlength=\"255\"/><br><br>\r\n      </div>\r\n      <div class=\"form-item\">\r\n        <label for=\"printableViewButtonText\">Text for the Printable View button?</label><br>\r\n		<input type=\"text\" id=\"printableViewButtonText\" name=\"printableViewButtonText\" value=\"<{$content.printableViewButtonText}>\"/><br><br>\r\n	  </div>\r\n      <div class=\"form-item\">\r\n        <label for=\"screens-printall\"><{$smarty.const._AM_FORMULIZE_SCREEN_PRINTALL}></label>\r\n        <div class=\"form-radios\">\r\n            <label for=\"1\"><input type=\"radio\" id=\"screens-printall\" name=\"screens-printall\"<{if $content.printall eq 1}> checked=\"checked\"<{/if}> value=\"1\"/><{$smarty.const._AM_FORMULIZE_SCREEN_PRINTALL_Y}></label>\r\n        </div>\r\n        <div class=\"form-radios\">\r\n            <label for=\"0\"><input type=\"radio\" id=\"screens-printall\" name=\"screens-printall\"<{if $content.printall eq 0}> checked=\"checked\"<{/if}> value=\"0\"/><{$smarty.const._AM_FORMULIZE_SCREEN_PRINTALL_N}></label>\r\n        </div>\r\n        <div class=\"form-radios\">\r\n            <label for=\"2\"><input type=\"radio\" id=\"screens-printall\" name=\"screens-printall\"<{if $content.printall eq 2}> checked=\"checked\"<{/if}> value=\"2\"/><{$smarty.const._AM_FORMULIZE_SCREEN_PRINTALL_NONE}></label>\r\n        </div>\r\n      </div>\r\n	</fieldset>\r\n    \r\n    <fieldset>\r\n		<legend>Element Defaults</legend>\r\n		<div class=\"form-item\">\r\n			<p><label for=\"elementchoice\">Do you want to force certain elements to have certain default values when displayed in this screen?</label>\r\n            <br>\r\n			<select id=\"elementchoice\" name=\"elementchoice\" size=\"1\">\r\n			<{html_options options=$content.element_list}>\r\n			</select>\r\n            <input type=\"text\" id=\"elementdefault\" name=\"elementdefault\">\r\n            <input type=\"button\" id=\"edadd\" value=\"Add This Default\"></p>\r\n            <p id=\"addeddefaults\"></p>\r\n			<div class=\"description\">Defaults that you set for elements here, will take effect only on this screen. The elements will behave normally on other screens (if those screens do not have any defaults set of their own).</div>\r\n		</div>\r\n	</fieldset>\r\n    \r\n    <fieldset>\r\n      <legend>Answers from a previous entry</legend>\r\n	  <div class=\"form-item\">\r\n		  <label for=\"screens-paraentryform\"><{$smarty.const._AM_FORMULIZE_SCREEN_PARAENTRYFORM}></label>\r\n	    <select id=\"screens-paraentryform\" name=\"screens-paraentryform\" size=\"1\">\r\n				<option value=0>No, don\'t show previous answers</option>\r\n        <{html_options options=$content.allformoptions selected=$content.paraentryform}>\r\n	    </select>\r\n			<div class=\"description\">Previous answers in another form will be matched to this form, based on equivalence of the element captions; the two forms need to have the same captions for corresponding elements.  It is often useful to clone a form to create a copy with identical captions.</div>\r\n	  </div>\r\n	  <div class=\"form-item\">\r\n		  <label for=\"screens-paraentryrelationship\"><{$smarty.const._AM_FORMULIZE_SCREEN_PARAENTRYRELATIONSHIP}></label>\r\n	    <select id=\"screens-paraentryrelationship\" name=\"screens-paraentryrelationship\" size=\"1\">\r\n        <option value=\"1\"<{if $content.paraentryrelationship eq 1}> selected=\"selected\"<{/if}>><{$smarty.const._AM_FORMULIZE_SCREEN_PARAENTRYREL_BYGROUP}></option>\r\n	    </select>\r\n	  </div>\r\n	</fieldset>\r\n	\r\n</div>\r\n\r\n</form>\r\n\r\n<script type=\'text/javascript\'>\r\n$(\"#screens-finishisdone-<{$content.finishisdone}>\").attr(\'checked\',1);\r\n$(\"#<{$content.reloadblank}>\").attr(\'checked\', true);\r\n$(\"#<{$content.displaycolumns}>\").attr(\'checked\', true);\r\n\r\n$(\"#edadd\").click(function() {\r\n    appendDefault($(\'#elementchoice\').val(), $(\'#elementchoice\').children(\'option:selected\').text(), $(\'#elementdefault\').val().replace(/\"/g, \'&quot;\'));\r\n    $(\'#elementdefault\').val(\'\');       \r\n});\r\n$(\'.adefault\').live(\'click\', function() {\r\n    $(\'#\'+$(this).attr(\'id\').replace(\'def\',\'hidden\')).remove();\r\n    $(this).remove();\r\n    setDisplay(\'savewarning\',\'block\');\r\n});\r\n\r\n<{foreach from=$content.elementdefaults key=id item=defaultText}>\r\n<{if $id}>\r\nappendDefault(<{$id}>, \'<{$content.element_list.$id}>\', \"<{$defaultText|replace:\'\"\':\'&quot;\'}>\");\r\n<{/if}>\r\n<{/foreach}>\r\n\r\nfunction appendDefault(id, elementText, defaultText) {\r\n    $(\'#addeddefaults\').append(\'<input type=\"hidden\" name=\"screens-elementdefaults[\'+id+\']\" id=\"hidden_\'+id+\'\" value=\"\'+defaultText+\'\"><span class=\"adefault\" id=\"def_\'+id+\'\">\'+elementText+\' >> \'+defaultText+\'<br></span>\');    \r\n}\r\n\r\n</script>\r\n\r\n<style>\r\n    .adefault:hover {\r\n        color: red;\r\n        text-decoration: line-through;\r\n        cursor: pointer;\r\n    }\r\n</style>'),
(162,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<{$securitytoken}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"screen_multipage_text\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.sid}>\">\r\n\r\n\r\n<div class=\"panel-content content\">\r\n  <fieldset>\r\n    <legend>Text for Multi page form: <em><{$content.title}></em></legend>\r\n\r\n	  <div class=\"form-item\">\r\n		  <label for=\"screens-introtext\"><{$smarty.const._AM_FORMULIZE_SCREEN_INTRO}></label>\r\n		  <textarea name=\"screens-introtext\" class=\"code-textarea\" rows=\"20\"/><{$content.introtext}></textarea>\r\n		  <div class=\"description\"></div>\r\n	  </div>\r\n	  <div class=\"form-item\">\r\n		  <label for=\"screens-thankstext\"><{$smarty.const._AM_FORMULIZE_SCREEN_THANKS}></label>\r\n		  <textarea name=\"screens-thankstext\" class=\"code-textarea\" rows=\"20\"/><{$content.thankstext}></textarea>\r\n		  <div class=\"description\">By default, this is a block of HTML that gets rendered onto the page. You can use {thankYouNav} in the HTML to include the standard navigation for the user to click on for the done destination. You can use PHP instead of HTML, just start with &lt;?php as the first line. In PHP, you can use $entry_id to refer to the entry id number. You can use $thankYouNav to include the standard navigation link to the \"done destination.\"</div>\r\n	  </div>\r\n  </fieldset>\r\n</div>\r\n\r\n</form>'),
(163,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<script type=\'text/javascript\'>\r\n  $(document).ready(function() {\r\n    $(\"#dialog-page-settings\").dialog({ autoOpen: false, modal: true, width: 950, height: 450, close: function(event, ui) {\r\n				var dialogEditingPage = $(\"[name=piforjquery]\").val();\r\n				var newTitle = $(\"#screens-pagetitle_\"+dialogEditingPage).val();\r\n				var saveWarningDisplay = $(\"#popupsavewarning\").css(\"display\");\r\n				if(saveWarningDisplay == \"none\") {\r\n					$(\"#drawer-4-\"+dialogEditingPage+\" .accordion-name\").empty();\r\n					$(\"#drawer-4-\"+dialogEditingPage+\" .accordion-name\").append(newTitle);\r\n				}\r\n			}\r\n		});\r\n  });\r\n\r\n  $.ajaxSetup({  \r\n    cache: false  \r\n  });  \r\n\r\n\r\n  function editPageSettings(pageNumber) {\r\n		$(\"#dialog-page-settings-content\").empty();\r\n		$(\"#dialog-page-settings-content\").append(\"<h1>Loading...</h1>\");\r\n		$(\"#dialog-page-settings\").dialog(\'open\');\r\n    $(\"#dialog-page-settings-content\").load(\'<{$smarty.const.XOOPS_URL}>/modules/formulize/admin/screen_multipage_pages_settings.php?page=\' + pageNumber + \'&sid=<{$content.sid}>\');\r\n  }\r\n</script>\r\n\r\n<div id=\"dialog-page-settings\" title=\"Edit Page Settings\" style=\"display:none\"><div id=\"dialog-page-settings-content\"></div></div>\r\n\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<{$securitytoken}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"screen_multipage_pages\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.sid}>\">\r\n<input type=\"hidden\" name=\"formulize_admin_op\" value=\"\">\r\n<input type=\"hidden\" name=\"formulize_admin_index\" value=\"\">\r\n<input type=\"hidden\" name=\"reload_multipage_pages\" value=\"\">\r\n<input type=\"hidden\" name=\"pageorder\" value=\"\">\r\n<input type=\"hidden\" name=\"conditionsdelete\" value=\"\">\r\n\r\n\r\n\r\n    <p><a name=\"addpage\" href=\"\"><img src=\"../images/filenew2.png\"> Create a new page</a></p>\r\n    <h2>Manage the pages in this screen:</h2>\r\n    <p>Click and drag the pages to re-order them</p>\r\n    <!--<p><a href=\"ui.php?page=screen&tab=pages&aid=<{$content.aid}>&fid=<{$content.fid}>&sid=<{$content.sid}>&op=new\">Create a new page</a></p>-->\r\n  \r\n<div id=\"sortable-list\">\r\n<{include file=\"db:admin/ui-accordion.html\" sectionTemplate=\"db:admin/screen_multipage_pages_sections.html\" sections=$content.pages}>\r\n</div>\r\n\r\n\r\n</form>\r\n\r\n\r\n<script type=\'text/javascript\'>\r\n\r\n  $(\"[name=addpage]\").click(function () {\r\n    $(\"[name=formulize_admin_op]\").val(\'addpage\');\r\n    $(\".savebutton\").click();\r\n    return false;\r\n  });\r\n  \r\n  \r\n	$(\"[name=editpage]\").click(function () {\r\n		editPageSettings($(this).attr(\'target\'));\r\n		return false;\r\n	});\r\n	\r\n	$(\"[name=delpage]\").click(function () {\r\n			var answer = confirm(\'Are you sure you want to delete this page?\');\r\n			if (answer)	{\r\n		    $(\"[name=formulize_admin_op]\").val(\'delpage\');\r\n		    $(\"[name=formulize_admin_index]\").val($(this).attr(\'target\'));\r\n		    $(\".savebutton\").click();\r\n			}\r\n		  return false;\r\n	});\r\n\r\n    $(\".savebutton\").click(function () {\r\n        $(\"[name=pageorder]\").val($(\"#accordion-5\").sortable(\'serialize\'));\r\n    });\r\n\r\n    $(\"#accordion-5\").bind( \"sortupdate\", function(event, ui) {\r\n        setDisplay(\'savewarning\',\'block\');\r\n    });\r\n</script>'),
(164,'<p><a name=\"delpage\" href=\"\" target=\"<{$sectionContent.index}>\"><img src=\"../images/editdelete.gif\"> Delete this page</a></p>\r\n		<p><a name=\"editpage\" href=\"\" target=\"<{$sectionContent.index}>\"><img src=\"../images/kedit.png\"> Edit this page</a></p>\r\n		<p>Form Elements on this page:</p>\r\n		<ul>\r\n			<{foreach from=$sectionContent.elements item=thiselement}>\r\n			<li><{$thiselement}></li>	\r\n			<{/foreach}>\r\n		</ul>'),
(165,'<div>\r\n	<form name=\"popupform\">\r\n	<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"screen_multipage_pages\">\r\n  <input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$sid}>\">\r\n	<input type=\"hidden\" name=\"reloadpopup\" value=\"\">\r\n	<input type=\"hidden\" name=\"conditionsdelete\" value=\"\">\r\n	<input type=\"hidden\" name=\"piforjquery\" value=<{$pageIndex}>>\r\n	<div id=popupsavebutton><input type=\"button\" class=\"savebuttonpopup\" id=\"savebuttonpopup\" value=\"Save your changes\"/></div>\r\n	<div id=\"popupsavewarning\">You have unsaved changes!</div>\r\n	<div style=\"clear: both\"></div>\r\n	<div class=\"accordion-box\">\r\n	  <div class=\"form-item required\">\r\n		  <label for=\"screens-pagetitle_<{$pageIndex}>\">Title for page number <{$pageNumber}></label>\r\n      <input type=\"text\" class=\"required_formulize_element\" id=\"screens-pagetitle_<{$pageIndex}>\" name=\"screens-pagetitle_<{$pageIndex}>\" value=\"<{$pageTitle}>\" size=\"30\" maxlength=\"255\"/>\r\n		  <div class=\"description\"></div>\r\n	  </div>\r\n	  <div class=\"form-item\">\r\n		  <label for=\"screens-page<{$pageIndex}>\">Form elements to display on page <{$pageNumber}></label>\r\n	    <select id=\"screens-page<{$pageIndex}>\" name=\"screens-page<{$pageIndex}>[]\" size=\"10\" multiple>\r\n        <{html_options options=$options selected=$pageElements}>\r\n	    </select>\r\n		  <div class=\"description\"></div>\r\n	  </div>\r\n	</div>\r\n		\r\n	<div class=\"accordion-box\">\r\n				<p>What conditions are there on the display of this page?</p>\r\n				<{$pageConditions}>\r\n				<div class=\"description\"><p>If you don\'t specify any conditions, the page will always be included in the form.</p></div>\r\n	</div>\r\n\r\n\r\n</form>\r\n</div>\r\n\r\n<script type=\"text/javascript\">\r\n	\r\n	// If saveLock is turned on, do not display save button to user, instead display \"READ ONLY\"\r\n	$( document ).ready(function() {\r\n		<{if $content.isSaveLocked}>\r\n			document.getElementById(\'savebuttonpopup\').style.visibility = \'hidden\';\r\n			document.getElementById(\'popupsavebutton\').innerHTML = \"READ ONLY\";\r\n		<{/if}>\r\n		\r\n	});\r\n	\r\n  $(\"input\").change(function() {\r\n    window.document.getElementById(\'popupsavewarning\').style.display = \'block\';\r\n    });\r\n  $(\"input[type=text]\").keydown(function() {\r\n    window.document.getElementById(\'popupsavewarning\').style.display = \'block\';\r\n    });\r\n  $(\"select\").change(function() {\r\n    window.document.getElementById(\'popupsavewarning\').style.display = \'block\';\r\n    });\r\n  $(\"textarea\").keydown(function() {\r\n    window.document.getElementById(\'popupsavewarning\').style.display = \'block\';\r\n    });\r\n\r\n\r\n$(\".savebuttonpopup\").click(function() {\r\n  $(\".required_formulize_element\").each(function() {\r\n    if($(this).val() == \"\") {\r\n      alert(\"Pages must have titles!\");\r\n      $(this).focus();\r\n    }\r\n	});\r\n});\r\n\r\n$(\"[name=addcon]\").click(function () {\r\n	$(\".savebuttonpopup\").click();\r\n	return false;\r\n});\r\n\r\n$(\".conditionsdelete\").click(function () {\r\n	$(\"[name=conditionsdelete]\").val($(this).attr(\'target\'));\r\n  $(\".savebuttonpopup\").click();\r\n	return false;\r\n});\r\n\r\n	$(\".savebuttonpopup\").click(function() {\r\n    if(validateRequired()) {\r\n			var pagedata = window.document.getElementsByName(\"popupform\");\r\n			$.post(\"save.php?popupsave=1\", $(pagedata).serialize(), function(data) {\r\n				if(data) {\r\n					if(data.substr(0,10)==\"/* eval */\") {\r\n						eval(data);\r\n					} else {\r\n						alert(data);\r\n					}\r\n				}\r\n				window.document.getElementById(\'popupsavewarning\').style.display = \'none\';\r\n				\r\n			});\r\n    }\r\n    $(\".savebuttonpopup\").blur();\r\n  });\r\n		\r\n	function reloadPopup() {\r\n		$(\"#dialog-page-settings-content\").load(\'<{$smarty.const.XOOPS_URL}>/modules/formulize/admin/screen_multipage_pages_settings.php?page=<{$pageIndex}>&sid=<{$sid}>\');\r\n	}\r\n\r\n</script>'),
(166,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<{$securitytoken}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"screen_multipage_templates\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.sid}>\">\r\n\r\n<div class=\"panel-content content\">\r\n    <p><b>Showing templates for use with this theme:</b> <{html_options name=\'screens-theme\' options=$content.themes selected=$content.selectedTheme}></p>\r\n        <p><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_TOPTEMPLATE3}></p>\r\n		\r\n		\r\n		<{if !$content.usingTemplates}>\r\n            <p><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_SEEDTEMPLATES1}><{$content.seedtemplates}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_SEEDTEMPLATES2}></p><br /><p><input type=\"button\" value=\"<{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_SEEDTEMPLATES3}>\" id=\"seedtemplates\"></p><br />        \r\n        <{/if}>\r\n        \r\n        <{if $content.usingTemplates}>\r\n		\r\n			<{include file=\"db:admin/screen_form_template_boxes.html\"}>\r\n		\r\n		<{/if}>\r\n	\r\n</div>\r\n\r\n</form>'),
(167,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<{$securitytoken}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"screen_template_options\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.sid}>\">\r\n\r\n\r\n<div class=\"panel-content content\">\r\n	<fieldset>\r\n		<legend>Buttons</legend>\r\n      <div class=\"form-item\">\r\n		  <label for=\"screens-savebuttontext\"><{$smarty.const._AM_FORMULIZE_SCREEN_TEMPLATE_SAVEBUTTONTEXT}></label>\r\n      <input type=\"text\" id=\"screens-savebuttontext\" name=\"screens-savebuttontext\" value=\"<{$content.savebuttontext}>\" size=\"50\" maxlength=\"255\"/>\r\n	  </div>\r\n      <div class=\"form-item\">\r\n		  <label for=\"screens-donebuttontext\"><{$smarty.const._AM_FORMULIZE_SCREEN_TEMPLATE_DONEBUTTONTEXT}></label>\r\n      <input type=\"text\" id=\"screens-donebuttontext\" name=\"screens-donebuttontext\" value=\"<{$content.donebuttontext}>\" size=\"50\" maxlength=\"255\"/>\r\n	  </div>\r\n	  <div class=\"form-item\">\r\n		  <label for=\"screens-donedest\"><{$smarty.const._AM_FORMULIZE_SCREEN_TEMPLATE_DONEDEST}></label>\r\n      <input type=\"text\" id=\"screens-donedest\" name=\"screens-donedest\" value=\"<{$content.donedest}>\" size=\"50\" maxlength=\"255\"/>\r\n	  </div>\r\n	</fieldset>\r\n</div>\r\n\r\n</form>'),
(168,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we are inserting/updating on *}>\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n    <{$securitytoken}>\r\n    <input type=\"hidden\" name=\"formulize_admin_handler\" value=\"screen_template_templates\">\r\n    <input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.sid}>\">\r\n\r\n    <div class=\"panel-content content\">\r\n        <p><b>Showing templates for use with this theme:</b> <{html_options name=screens-theme options=$content.themes selected=$content.selectedTheme}></p>\r\n        <p><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_TOPTEMPLATE3}></p>\r\n        <p><{$smarty.const._AM_FORMULIZE_SCREEN_TEMPLATE_DESC_TEMPLATE}></p><br />\r\n        <div class=\"form-item\">\r\n            <fieldset>\r\n                <legend>Template</legend>\r\n                <textarea id=\"screens-template\"  name=\"screens-template\" class=\"code-textarea\" rows=\"20\"/>\r\n<{$content.template}></textarea>\r\n            </fieldset>\r\n        </div>\r\n        <div class=\'description\'><{$smarty.const._AM_FORMULIZE_SCREEN_TEMPLATE_HELP}></div>\r\n        <br />\r\n\r\n        <p><{$smarty.const._AM_FORMULIZE_SCREEN_TEMPLATE_DESC_CUSTOM_CODE}></p><br />\r\n        <div class=\"form-item\">\r\n            <fieldset>\r\n            <legend>Custom code</legend>\r\n                <textarea id=\"screens-custom_code\" name=\"screens-custom_code\" class=\"code-textarea\" rows=\"20\"/>\r\n<{$content.custom_code}></textarea>\r\n            </fieldset>\r\n        </div>\r\n\r\n    </div>\r\n</form>\r\n<script>\r\njQuery(document).ready(function() {\r\n    jQuery(\".savebutton\").click(function() {\r\n        fz_check_php_code(jQuery(\"#screens-custom_code\").val(), \"Template Code\", \"<{$icms_url}>\", <{$icms_userid}>);\r\n    });\r\n});\r\n</script>'),
(169,'<div class=\"optionlist\">\r\n<p><input type=\"button\" class=\"formButton\" name=\"addoption\" value=\"<{$smarty.const._AM_ELE_ADD_OPT_SUBMIT}> 1\"></p>\r\n\r\n<{if $content.useroptions|is_array AND $content.useroptions|@count > 0}>\r\n  <{foreach from=$content.useroptions key=text item=checked name=optionsloop}>\r\n    <p class=\"useroptions\" name=\"<{$smarty.foreach.optionsloop.index}>\">\r\n    <{if $content.type == \"radio\"}><input type=\"radio\" name=\"defaultoption\" value=<{$smarty.foreach.optionsloop.index}> <{if $checked}>checked<{/if}>></input>\r\n    <{else}><input type=\"checkbox\" name=\"defaultoption[<{$smarty.foreach.optionsloop.index}>]\" value=<{$smarty.foreach.optionsloop.index}> <{if $checked}>checked<{/if}>></input>\r\n    <{/if}>&nbsp;&nbsp;<input type=\"text\" name=\"ele_value[<{$smarty.foreach.optionsloop.index}>]\" value=\"<{$text}>\" onchange=\"checkForNames()\"></input></p>\r\n  <{/foreach}>\r\n<{else}>\r\n  <p class=\"useroptions\" name=\"0\">\r\n  <{if $content.type == \"radio\"}><input type=\"radio\" name=\"defaultoption\" value=0></input>\r\n  <{else}><input type=\"checkbox\" name=\"defaultoption[0]\" value=0></input>\r\n  <{/if}>&nbsp;&nbsp;<input type=\"text\" name=\"ele_value[0]\" onchange=\"checkForNames()\"></input></p>\r\n<{/if}>\r\n</div>\r\n\r\n<{if $content.type == \"radio\"}>\r\n<p><input type=\"button\" class=\"formButton\" name=\"cleardef\" value=\"<{$smarty.const._AM_CLEAR_DEFAULT}>\"/></p>\r\n<{/if}>\r\n\r\n\r\n<div class=\"description\">\r\n  <{if $content.type == \"radio\"}><p><{$smarty.const._AM_ELE_OPT_DESC2}></p><p><{$smarty.const._AM_ELE_OTHER}></p><{/if}>\r\n  <{if $content.type == \"checkbox\"}><p><{$smarty.const._AM_ELE_OPT_DESC_CHECKBOXES}></p><p><{$smarty.const._AM_ELE_OTHER}></p><{/if}>\r\n  <{if $content.type == \"select\"}><p><{$smarty.const._AM_ELE_OPT_DESC}><{$smarty.const._AM_ELE_OPT_DESC1}></p><{/if}>\r\n  <p><{$smarty.const._AM_ELE_OPT_UITEXT}></p>\r\n</div>\r\n<hr style=\'margin-top: 1em; border: 0px; border-top: 1px dashed black; height: 1px;\'>\r\n<div>\r\n    <p>If you are using the pipe character to store alternative values in the database:<br />\r\n    <label for=\"showalt\"><input type=\"radio\" id=\"showalt\" name=\"elements-ele_uitextshow\" value=0>Show the alternative values in lists of entries (and use them in the API)</label><br />\r\n    <label for=\"showuitext\"><input type=\"radio\" id=\"showuitext\" name=\"elements-ele_uitextshow\" value=1>Show the same values the user sees in the form, in the list of entries (and use them in the API)</label>\r\n    </p>\r\n</div>\r\n\r\n<div class=\"description\">\r\n  <p>The second option is for use only in cases where you need an alternative value in the database for some third party integration, but a different value shown throughout Formulize. Regardless of your choice, if you use alternative values in the database then searches will only work when you search for those alternative values (because they are what is actually stored in the database).</p>\r\n</div>\r\n\r\n</fieldset>\r\n</div>\r\n\r\n<div class=\"form-item\">\r\n<fieldset>\r\n  <legend>Resynch existing entries with any option changes?</legend>\r\n  <p><label for=\"element-changeuservalues\">\r\n<input type=\"checkbox\" id=\"element-changeuservalues\" name=\"changeuservalues\" value=\"1\"/>\r\n<{$smarty.const._AM_ELE_OPT_CHANGEUSERVALUES}></label></p>\r\n</fieldset>\r\n\r\n<script type=\"text/javascript\">\r\n  \r\n  $(\"[name=addoption]\").click(function (){\r\n    number = $(\".useroptions:last\").attr(\'name\');\r\n    number = parseInt(number) + 1;\r\n    appendContents1 = \'<{if $content.type == \"radio\"}><input type=\"radio\" name=\"defaultoption\" value=\'+number+\'></input><{else}><input type=\"checkbox\" name=\"defaultoption[\'+number+\']\" value=\'+number+\'></input><{/if}>\';\r\n    appendContents2 = \'<input type=\"text\" name=\"ele_value[\'+number+\']\"></input>\';\r\n    $(\".optionlist\").append(\'<p class=\"useroptions\" name=\"\'+number+\'\"></p>\');\r\n    $(\".useroptions:last\").append(appendContents1);\r\n    $(\".useroptions:last\").append(\'&nbsp;&nbsp;\');\r\n    $(\".useroptions:last\").append(appendContents2);\r\n    $(\"[name=addoption]\").blur();\r\n    setDisplay(\'savewarning\',\'block\');\r\n    $(\"#no\").attr(\'checked\',1);\r\n  });\r\n\r\n  <{if $content.type == \"radio\"}>\r\n  $(\"[name=cleardef]\").click(function () {\r\n    $(\"[name=defaultoption]\").attr(\'checked\',0);\r\n    $(\"[name=cleardef]\").blur();\r\n  });\r\n  <{/if}>\r\n  \r\n  <{if $content.type eq \"select\"}>\r\n  checkForNames();\r\n  \r\n  function checkForNames() {\r\n    if ($(\"[name=ele_value[0]]\").val() == \"{USERNAMES}\" || $(\"[name=ele_value[0]]\").val() == \"{FULLNAMES}\"){\r\n      disableForAutocomplete();\r\n    }else{\r\n      enableForAutocomplete();\r\n    }\r\n  }\r\n\r\n  function disableForAutocomplete(){\r\n    $(\"input[name=elements-ele_value[16]]:eq(1)\").attr(\"disabled\",\"disabled\");\r\n    $(\"input[name=elements-ele_value[16]]:eq(0)\").attr(\"checked\",\"checked\");\r\n    $(\"[name=addoption]\").attr(\"disabled\",\"disabled\");\r\n  }\r\n  \r\n  function enableForAutocomplete(){\r\n    $(\"input[name=elements-ele_value[16]]:eq(1)\").attr(\"disabled\",0);\r\n    $(\"[name=addoption]\").attr(\"disabled\",0);\r\n  }\r\n  <{/if}>\r\n\r\n  $(document).ready(function() {\r\n    if (<{$content.ele_uitextshow}> == 1) {\r\n      $(\"#showuitext\").attr(\'checked\',1);\r\n    } else {\r\n      $(\"#showalt\").attr(\'checked\',1);\r\n    }\r\n  });\r\n  \r\n</script>'),
(170,'<div class=\"form-item\">\r\n	<fieldset>\r\n		<legend>What are the options the user can choose from?</legend>\r\n		<div class=\"form-radios\">\r\n			<input type=\"radio\" id=\"yes\" name=\"linked_yesno\" value=\"1\"<{if $content.islinked eq 1}> checked=\"checked\"<{/if}>/>Use the values that people have entered in this form element: <{$content.linkedoptions}>\r\n		</div>\r\n        \r\n        <div class=\"form-radios\" id=\"snapshot-values\" style=\"<{if $content.islinked neq 1}>display: none; <{/if}>padding: 1em;\">\r\n            <input type=\"radio\" id=\"snapshot-0\" name=\"elements-ele_value[snapshot]\" value=0 checked> When people select a value, save a reference to the source (so when the source changes, the values people have entered here will change too).<br>\r\n            <input type=\"radio\" id=\"snapshot-1\" name=\"elements-ele_value[snapshot]\" value=1> When people select a value, save a snapshot of the value (so it will stay as-is regardless of changes to the source).\r\n        </div>\r\n        \r\n		<div class=\"form-radios\">\r\n			<input type=\"radio\" id=\"no\" name=\"linked_yesno\" value=\"0\"<{if $content.islinked eq 0}> checked=\"checked\"<{/if}>/>Use the options specified below:\r\n		</div>\r\n		<{include file=\"db:admin/element_optionlist.html\"}>	\r\n		\r\n	</div>'),
(171,'<div class=\"form-item\">\r\n			  <fieldset>\r\n				  <legend>Filter options based on these properties of their entry in the source form</legend>\r\n				  <div id=\"filterdiv\">\r\n				  <{$content.formlinkfilter}>\r\n				  </div>\r\n				  <div class=\"description\">\r\n					  <{$smarty.const._AM_ELE_FORMLINK_SCOPEFILTER_DESC}>\r\n				  </div>\r\n			  </fieldset>\r\n		  </div>\r\n\r\n\r\n<script type=\'text/javascript\'>\r\n\r\n    $(\"#formlink\").change(function() {\r\n		$(\"#yes\").attr(\'checked\',1);\r\n		$(\"[name=reload_option_page]\").val(1);\r\n		$(\"#filterdiv\").empty();\r\n		$(\"#filterdiv\").append(\'<p><input type=\"button\" class=\"formButton\" name=\"refreshfilter\" onclick=\"refreshfilterjq()\" value=\"Save changes to update filter options\"></p>\');\r\n    });\r\n \r\n    function refreshfilterjq() {\r\n        $(\"[name=reload_option_page]\").val(1);\r\n        $(\".savebutton\").click();\r\n	}\r\n\r\n    $(\"[name=ele_value[0]]\").keydown(function () {\r\n        $(\"#no\").attr(\'checked\',1);\r\n        $(\"#formlink\").val(\'none\');\r\n        $(\"#filterdiv\").empty();\r\n        $(\"#filterdiv\").append(\'<p>The options are not linked.</p>\');\r\n	});\r\n    \r\n    $(\"div#filterdiv > a.conditionsdelete\").click(function () {\r\n		$(\".optionsconditionsdelete\").val($(this).attr(\'target\'));\r\n		$(\"[name=reload_option_page]\").val(1);\r\n	  $(\".savebutton\").click();\r\n		return false;\r\n	});\r\n\r\n\r\n	$(\"div#filterdiv > input#addcon\").click(function () {\r\n		$(\"[name=reload_option_page]\").val(1);\r\n	  $(\".savebutton\").click();\r\n		return false;\r\n	});\r\n    \r\n\r\n</script>'),
(172,'<div class=\"form-item\">\r\n		    <fieldset>\r\n			    <legend>Which column in the source form should be used to sort the options?</legend>\r\n                <{if strstr($content.formlinkfilter, \"<select\") == false}>\r\n                    <{$content.formlinkfilter}>\r\n                <{/if}>\r\n			    <p><{$content.optionSortOrder}><br /><label for=\"elements-ele_value[15]-1\"><input type=radio id=\"elements-ele_value[15]-1\" name=\"elements-ele_value[15]\" value=1 <{if $content.ele_value[15] != 2}>checked<{/if}>/> Ascending (a..z)&nbsp;&nbsp;&nbsp;</label><label for=\"elements-ele_value[15]-2\"><input type=radio id=\"elements-ele_value[15]-2\" name=\"elements-ele_value[15]\" value=2 <{if $content.ele_value[15] eq 2}>checked<{/if}>/> Descending (z..a)</label></p>\r\n			    <div class=\"description\">\r\n				By default, the linked column/element will be used, so the options will simply appear in alphabetical order.  If you select another column, then the options will be sorted according to those values in the source form instead.  For example, you might link to a list of classroom names, but rather than having them show up alphabetical by name, you want to show them according to which building they\'re in.  If the source form has an element to record the building, then you could sort the options by that column and they will show up in a different order.\r\n			    </div>\r\n		    </fieldset>\r\n	    </div>'),
(173,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<{$securitytoken}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"advanced_calculation_settings\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.acid}>\">\r\n<input type=\"hidden\" name=\"formulize_admin_fid\" value=\"<{$content.fid}>\">\r\n<input type=\"hidden\" name=\"aid\" value=\"<{$content.aid}>\">\r\n\r\n\r\n<div class=\"panel-content content\">\r\n  <fieldset>\r\n    <legend><{$smarty.const._AM_CALC_PROCEDURE_SETTINGS}><em><{$content.name}></em></legend>\r\n\r\n	  <div class=\"form-item\">\r\n		  <label for=\"name\"><{$smarty.const._AM_CALC_PROCEDURE_NAME}>:</label>\r\n		  <input type=\"text\" id=\"advcalc-name\" name=\"advcalc-name\" value=\"<{$content.name}>\"/>\r\n	  </div>\r\n\r\n	  <div class=\"form-item\">\r\n		  <label for=\"description\"><{$smarty.const._AM_CALC_PROCEDURE_DESCR}></label>\r\n	    <textarea id=\"advcalc-description\" name=\"advcalc-description\" rows=\"5\" cols=\"35\"><{$content.description}></textarea>\r\n	  </div>\r\n  </fieldset>\r\n</div>\r\n\r\n</form>'),
(174,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<form id=\"form-admin-form\" class=\"formulize-admin-form\">\r\n<{$securitytoken}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"advanced_calculation_input_output\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.acid}>\">\r\n<input type=\"hidden\" name=\"formulize_admin_fid\" value=\"<{$content.fid}>\">\r\n<input type=\"hidden\" name=\"aid\" value=\"<{$content.aid}>\">\r\n\r\n\r\n<div class=\"panel-content content\">\r\n  <fieldset>\r\n    <legend>Input/Output for the Procedure: <em><{$content.name}></em></legend>\r\n\r\n	  <div class=\"form-item\">\r\n		  <label for=\"input\">PHP code for defining any initial values (before the steps are evaluated)</label><br />\r\n	    <textarea id=\"advcalc-input\" name=\"advcalc-input\" class=\"code-textarea\" rows=\"5\" cols=\"35\"><{$content.input}></textarea>\r\n	  </div>\r\n\r\n	  <div class=\"form-item\">\r\n		  <label for=\"output\">PHP code for outputing results to the screen</label><br />\r\n	    <textarea id=\"advcalc-output\" name=\"advcalc-output\" class=\"code-textarea\" rows=\"10\" cols=\"35\"><{$content.output}></textarea>\r\n	  </div>\r\n  </fieldset>\r\n</div>\r\n\r\n</form>'),
(175,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<{$securitytoken}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"advanced_calculation_steps\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.acid}>\">\r\n<input type=\"hidden\" name=\"formulize_admin_op\" value=\"\">\r\n<input type=\"hidden\" name=\"formulize_admin_index\" value=\"\">\r\n<input type=\"hidden\" name=\"reload_advanced_calculation_steps\" value=\"\">\r\n<input type=\"hidden\" name=\"steporder\" value=\"\">\r\n<input type=\"hidden\" name=\"conditionsdelete\" value=\"\">\r\n\r\n\r\n\r\n    <p><a name=\"addstep\" href=\"\"><img src=\"../images/filenew2.png\"> Create a new step</a></p>\r\n    <h2>Manage the steps in this Procedure:</h2>\r\n    <p>Click and drag the steps to re-order them</p>\r\n    <!--<p><a href=\"ui.php?page=screen&tab=steps&aid=<{$content.aid}>&fid=<{$content.fid}>&acid=<{$content.acid}>&op=new\">Create a new step</a></p>-->\r\n  \r\n<div id=\"sortable-list\">\r\n<{include file=\"db:admin/ui-accordion.html\" sectionTemplate=\"db:admin/advanced_calculation_steps_sections.html\" sections=$content.steps}>\r\n</div>\r\n\r\n\r\n</form>\r\n\r\n\r\n<script type=\'text/javascript\'>\r\n\r\n$(\".savebutton\").click(function() {\r\n  $(\".required_formulize_element\").each(function() {\r\n    if($(this).val() == \"\" && $(this).hasClass(\'steptitle\')) {\r\n      alert(\"Steps must have names!\");\r\n      $(this).focus();\r\n    }\r\n	});\r\n});\r\n\r\n$(\"[name=addcon]\").click(function () {\r\n	$(\"[name=reload_advanced_calculation_steps]\").val(1);\r\n  $(\".savebutton\").click();\r\n	return false;\r\n});\r\n\r\n$(\".conditionsdelete\").click(function () {\r\n	$(\"[name=conditionsdelete]\").val($(this).attr(\'target\'));\r\n	$(\"[name=reload_advanced_calculation_steps]\").val(1);\r\n  $(\".savebutton\").click();\r\n	return false;\r\n});\r\n\r\n\r\n  $(\".steptitle\").keydown(function () {\r\n    $(\"[name=reload_advanced_calculation_steps]\").val(1);\r\n  });\r\n  $(\".condition_term\").keydown(function () {\r\n    $(\"[name=reload_advanced_calculation_steps]\").val(1);\r\n  });\r\n  \r\n\r\n  $(\"[name=addstep]\").click(function () {\r\n    $(\"[name=formulize_admin_op]\").val(\'addstep\');\r\n    $(\".savebutton\").click();\r\n    return false;\r\n  });\r\n  \r\n  \r\n	$(\"[name=delstep]\").click(function () {\r\n			var answer = confirm(\'Are you sure you want to delete this step?\');\r\n			if (answer)	{\r\n		    $(\"[name=formulize_admin_op]\").val(\'delstep\');\r\n		    $(\"[name=formulize_admin_index]\").val($(this).attr(\'target\'));\r\n		    $(\".savebutton\").click();\r\n			}\r\n		  return false;\r\n	});\r\n  \r\n	$(\"[name=clonestep]\").click(function () {\r\n    $(\"[name=formulize_admin_op]\").val(\'clonestep\');\r\n    $(\"[name=formulize_admin_index]\").val($(this).attr(\'target\'));\r\n    $(\".savebutton\").click();\r\n    return false;\r\n	});\r\n\r\n  $(\".savebutton\").click(function () {\r\n  	$(\"[name=steporder]\").val($(\"#accordion-3\").sortable(\'serialize\')); \r\n  });\r\n\r\n  $(\"#accordion-3\").bind( \"sortupdate\", function(event, ui) {\r\n    setDisplay(\'savewarning\',\'block\');\r\n  });\r\n\r\n</script>'),
(176,'<p>\r\n      <a name=\"clonestep\" href=\"\" target=\"<{$sectionContent.index}>\"><img src=\"../images/clone.gif\"> Clone this step</a>\r\n      <a name=\"delstep\" href=\"\" target=\"<{$sectionContent.index}>\"><img src=\"../images/editdelete.gif\"> Delete this step</a>\r\n    </p>\r\n\r\n	  <div class=\"form-item required\">\r\n		  <label for=\"advcalc-steptitle_<{$sectionContent.index}>\">Title for step number <{$sectionContent.number}></label>\r\n      <input type=\"text\" class=\"required_formulize_element\" id=\"advcalc-steptitle_<{$sectionContent.index}>\" name=\"advcalc-steptitle_<{$sectionContent.index}>\" value=\"<{$sectionContent.title}>\" size=\"30\" maxlength=\"255\"/>\r\n		  <div class=\"description\"></div>\r\n	  </div>\r\n\r\n	  <div class=\"form-item\">\r\n		  <label for=\"input\">Description of this step</label><br />\r\n	    <textarea id=\"advcalc-description_<{$sectionContent.index}>\" name=\"advcalc-description_<{$sectionContent.index}>\" rows=\"5\" cols=\"35\"><{$sectionContent.steps.description}></textarea>\r\n		  <div class=\"description\"></div>\r\n	  </div>\r\n\r\n	  <div class=\"form-item\">\r\n		  <label for=\"input\">Use this SQL to get data from the database</label><br />\r\n	    <textarea id=\"advcalc-sql_<{$sectionContent.index}>\" name=\"advcalc-sql_<{$sectionContent.index}>\" class=\"code-textarea\" rows=\"15\" cols=\"80\"><{$sectionContent.steps.sql}></textarea>\r\n		  <div class=\"description\">You can use any previously defined PHP variable in the SQL, including ones defined in previous steps.  Use $fromBaseQuery instead of \"FROM tablename WHERE\" to have your query use the current search and filtering settings that the user has specified in Formulize -- if you do this you MUST NOT use WHERE in the SQL ($fromBaseQuery already includes it) and you MUST start your own where clause statement(s) with AND (since they will be appended to the end of the Formulize base query\'s where clause).  Use \"{foreach $array as $key=>$value; BOOLEAN }SQL INCLUDING $key AND/OR $value{/foreach}\" to add a certain piece of SQL once for every value in a previously defined array.  The BOOLEAN can be AND or OR, depending on which you want used between each instance of the SQL.</div>\r\n	  </div>\r\n\r\n	  <div class=\"form-item\">\r\n		  <label for=\"input\">Run this PHP code before the SQL</label><br />\r\n	    <textarea id=\"advcalc-preCalculate_<{$sectionContent.index}>\" name=\"advcalc-preCalculate_<{$sectionContent.index}>\" class=\"code-textarea\" rows=\"15\" cols=\"80\"><{$sectionContent.steps.preCalculate}></textarea>\r\n		  <div class=\"description\"></div>\r\n	  </div>\r\n\r\n	  <div class=\"form-item\">\r\n			<label>Run this PHP code to process each record returned by the SQL</label><br />\r\n		  <textarea id=\"advcalc-calculate_<{$sectionContent.index}>\" name=\"advcalc-calculate_<{$sectionContent.index}>\" class=\"code-textarea\" rows=\"15\" cols=\"80\"><{$sectionContent.steps.calculate}></textarea>\r\n		  <div class=\"description\">use $row[X] or $array[fieldname] to refer to the values in the record.  X would be the order of the fields starting at 0, ie: use 2 to refer to the third field selected in the SQL.  fieldname would be the actual name of the field as selected in the SQL.</div>\r\n	  </div>\r\n\r\n	  <div class=\"form-item\">\r\n		  <label for=\"input\">Run this PHP code after processing all the records</label><br />\r\n	    <textarea id=\"advcalc-postCalculate_<{$sectionContent.index}>\" name=\"advcalc-postCalculate_<{$sectionContent.index}>\" class=\"code-textarea\" rows=\"15\" cols=\"80\"><{$sectionContent.steps.postCalculate}></textarea>\r\n		  <div class=\"description\"></div>\r\n	  </div>'),
(177,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<{$securitytoken}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"advanced_calculation_fltr_grp\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.acid}>\">\r\n<input type=\"hidden\" name=\"formulize_admin_op\" value=\"\">\r\n<input type=\"hidden\" name=\"formulize_admin_index\" value=\"\">\r\n<input type=\"hidden\" name=\"reload_advanced_calculation_fltr_grp\" value=\"\">\r\n<input type=\"hidden\" name=\"fltr_grporder\" value=\"\">\r\n<input type=\"hidden\" name=\"conditionsdelete\" value=\"\">\r\n\r\n    <p><a name=\"addfltr_grp\" href=\"\"><img src=\"../images/filenew2.png\"> Add new filter / grouping options</a></p>\r\n    <h2>Manage the filter and grouping options in this Procedure:</h2>\r\n    <p>Click and drag the filter and grouping options to re-order them</p>\r\n    <!--<p><a href=\"ui.php?page=screen&tab=fltr_grps&aid=<{$content.aid}>&fid=<{$content.fid}>&acid=<{$content.acid}>&op=new\">Create a new filters and grouping</a></p>-->\r\n\r\n<div id=\"sortable-list\">\r\n<{include file=\"db:admin/ui-accordion.html\" sectionTemplate=\"db:admin/advanced_calculation_fltr_grp_sections.html\" sections=$content.fltr_grps}>\r\n</div>\r\n\r\n\r\n</form>\r\n\r\n\r\n<script type=\'text/javascript\'>\r\n\r\n$(\".savebutton\").click(function() {\r\n  $(\".required_formulize_element\").each(function() {\r\n    if($(this).val() == \"\" && $(this).hasClass(\'fltr_grp\')) {\r\n      alert(\"Filters and groupings must have names!\");\r\n      $(this).focus();\r\n    }\r\n	});\r\n});\r\n\r\n$(\"[name=addcon]\").click(function () {\r\n	$(\"[name=reload_advanced_calculation_fltr_grp]\").val(1);\r\n  $(\".savebutton\").click();\r\n	return false;\r\n});\r\n\r\n$(\".conditionsdelete\").click(function () {\r\n	$(\"[name=conditionsdelete]\").val($(this).attr(\'target\'));\r\n	$(\"[name=reload_advanced_calculation_fltr_grp]\").val(1);\r\n  $(\".savebutton\").click();\r\n	return false;\r\n});\r\n\r\n\r\n  $(\".fltr_grptitle\").keydown(function () {\r\n    $(\"[name=reload_advanced_calculation_fltr_grp]\").val(1);\r\n  });\r\n  $(\".condition_term\").keydown(function () {\r\n    $(\"[name=reload_advanced_calculation_fltr_grp]\").val(1);\r\n  });\r\n  \r\n\r\n  $(\"[name=addfltr_grp]\").click(function () {\r\n    $(\"[name=formulize_admin_op]\").val(\'addfltr_grp\');\r\n    $(\".savebutton\").click();\r\n    return false;\r\n  });\r\n  \r\n	$(\"[name=delfltr_grp]\").click(function () {\r\n			var answer = confirm(\'Are you sure you want to delete this filters and grouping?\');\r\n			if (answer)	{\r\n		    $(\"[name=formulize_admin_op]\").val(\'delfltr_grp\');\r\n		    $(\"[name=formulize_admin_index]\").val($(this).attr(\'target\'));\r\n		    $(\".savebutton\").click();\r\n			}\r\n		  return false;\r\n	});\r\n  \r\n	$(\"[name=clonefltr_grp]\").click(function () {\r\n    $(\"[name=formulize_admin_op]\").val(\'clonefltr_grp\');\r\n    $(\"[name=formulize_admin_index]\").val($(this).attr(\'target\'));\r\n    $(\".savebutton\").click();\r\n    return false;\r\n	});\r\n\r\n  $(\".savebutton\").click(function () {\r\n  	$(\"[name=fltr_grporder]\").val($(\"#accordion-4\").sortable(\'serialize\')); \r\n  });\r\n\r\n  $(\"#accordion-4\").bind( \"sortupdate\", function(event, ui) {\r\n    setDisplay(\'savewarning\',\'block\');\r\n  });\r\n\r\n</script>'),
(178,'<p>\r\n      <a name=\"clonefltr_grp\" href=\"\" target=\"<{$sectionContent.index}>\"><img src=\"../images/clone.gif\"><{$smarty.const._AM_CALC_PROCEDURE_FILTER_CLONE}></a>\r\n      <a name=\"delfltr_grp\" href=\"\" target=\"<{$sectionContent.index}>\"><img src=\"../images/editdelete.gif\"><{$smarty.const._AM_CALC_PROCEDURE_FILTER_DELETE}></a>\r\n    </p>\r\n\r\n	  <div class=\"form-item required\">\r\n		  <label for=\"advcalc-fltr_grptitle_<{$sectionContent.index}>\">Name</label>\r\n      <input type=\"text\" class=\"required_formulize_element fltr_grp\" id=\"advcalc-fltr_grptitle_<{$sectionContent.index}>\" name=\"advcalc-fltr_grptitle_<{$sectionContent.index}>\" value=\"<{$sectionContent.title}>\" size=\"30\" maxlength=\"255\"/>\r\n		  <div class=\"description\"></div>\r\n	  </div>\r\n\r\n	  <div class=\"form-item\">\r\n		  <label for=\"input\">Description</label><br />\r\n	    <textarea id=\"advcalc-description_<{$sectionContent.index}>\" name=\"advcalc-description_<{$sectionContent.index}>\" rows=\"5\" cols=\"35\"><{$sectionContent.fltr_grps.description}></textarea>\r\n		  <div class=\"description\"></div>\r\n	  </div>\r\n\r\n\r\n	  <div class=\"form-item\">\r\n		  <label for=\"advcalc-handle_<{$sectionContent.index}>\">Handle for this option</label><br />\r\n	    <input type=\"text\" id=\"advcalc-handle_<{$sectionContent.index}>\" name=\"advcalc-handle_<{$sectionContent.index}>\" value=\"<{$sectionContent.fltr_grps.handle}>\" size=\"30\" maxlength=\"255\"/>\r\n		  <div class=\"description\">ie: \'startDate\' will allow you to use $startDate in the procedure to refer to the value the user chose</div>\r\n	  </div>\r\n\r\n	  <div class=\"form-item\">\r\n		  <label for=\"advcalc-type_<{$sectionContent.index}>\">What type of filter element should be used</label><br />\r\n	    <label for=\"advcalc-type_<{$sectionContent.index}>_1\"><input type=\"radio\" id=\"advcalc-type_<{$sectionContent.index}>_1\" name=\"advcalc-type_<{$sectionContent.index}>\" value=\"1\"<{if $sectionContent.fltr_grps.type.kind eq 1}> checked<{/if}> onClick=\"fltr_grp_type_select(this)\"/>Datebox</label><br />\r\n	    <label for=\"advcalc-type_<{$sectionContent.index}>_2\"><input type=\"radio\" id=\"advcalc-type_<{$sectionContent.index}>_2\" name=\"advcalc-type_<{$sectionContent.index}>\" value=\"2\"<{if $sectionContent.fltr_grps.type.kind eq 2}> checked<{/if}> onClick=\"fltr_grp_type_select(this)\"/>Selectbox</label><br />\r\n	    <label for=\"advcalc-type_<{$sectionContent.index}>_3\"><input type=\"radio\" id=\"advcalc-type_<{$sectionContent.index}>_3\" name=\"advcalc-type_<{$sectionContent.index}>\" value=\"3\"<{if $sectionContent.fltr_grps.type.kind eq 3}> checked<{/if}> onClick=\"fltr_grp_type_select(this)\"/>Checkboxes</label><br />\r\n	    <label for=\"advcalc-type_<{$sectionContent.index}>_4\"><input type=\"radio\" id=\"advcalc-type_<{$sectionContent.index}>_4\" name=\"advcalc-type_<{$sectionContent.index}>\" value=\"4\"<{if $sectionContent.fltr_grps.type.kind eq 4}> checked<{/if}> onClick=\"fltr_grp_type_select(this)\"/>Textbox</label>\r\n      <div id=\"advcalc-type_<{$sectionContent.index}>_1_options\" style=\"<{if $sectionContent.fltr_grps.type.kind neq 1}>visibility: hidden; position: absolute<{/if}>\">\r\n      </div>\r\n      <div id=\"advcalc-type_<{$sectionContent.index}>_2_options\" style=\"<{if $sectionContent.fltr_grps.type.kind neq 2}>visibility: hidden; position: absolute<{/if}>\">\r\n        <div class=\"optionlist\" id=\"optionlist_<{$sectionContent.index}>_2\">\r\n	<p><input onclick=\"fltr_grp_addoption(this)\" type=\"button\" class=\"formButton\" name=\"addoption\" section=\"<{$sectionContent.index}>\" kind=\"2\" value=\"<{$smarty.const._AM_ELE_ADD_OPT_SUBMIT}> 1 option\"></p>\r\n        <{if $sectionContent.fltr_grps.type.kind eq 2 AND $sectionContent.fltr_grps.type.options|is_array AND $sectionContent.fltr_grps.type.options|@count > 0}>\r\n          <{foreach from=$sectionContent.fltr_grps.type.options key=text item=value name=optionsloop}>\r\n          <p class=\"useroptions\" id=\"useroptions_<{$sectionContent.index}>_2\" section=\"<{$sectionContent.index}>\" kind=\"2\" name=\"<{$smarty.foreach.optionsloop.index}>\"><input type=\"text\" name=\"advcalc-type_options_<{$sectionContent.index}>_2[<{$smarty.foreach.optionsloop.index}>]\" value=\"<{$value}>\"></input></p>\r\n          <{/foreach}>\r\n        <{else}>\r\n          <p class=\"useroptions\" id=\"useroptions_<{$sectionContent.index}>_2\" section=\"<{$sectionContent.index}>\" kind=\"2\" name=\"0\"><input type=\"text\" name=\"advcalc-type_options_<{$sectionContent.index}>_2[0]\"></input></p>\r\n        <{/if}>\r\n        </div>\r\n        <!--<p><input type=\"button\" class=\"formButton\" name=\"cleardef\" value=\"<{$smarty.const._AM_CLEAR_DEFAULT}>\"/></p>-->\r\n      </div>\r\n      <div id=\"advcalc-type_<{$sectionContent.index}>_3_options\" style=\"<{if $sectionContent.fltr_grps.type.kind neq 3}>visibility: hidden; position: absolute<{/if}>\">\r\n        <div class=\"optionlist\" id=\"optionlist_<{$sectionContent.index}>_3\">\r\n	<p><input onclick=\"fltr_grp_addoption(this)\" type=\"button\" class=\"formButton\" name=\"addoption\" section=\"<{$sectionContent.index}>\" kind=\"3\" value=\"<{$smarty.const._AM_ELE_ADD_OPT_SUBMIT}> 1 option\"></p>\r\n        <{if $sectionContent.fltr_grps.type.kind eq 3 AND $sectionContent.fltr_grps.type.options|is_array AND $sectionContent.fltr_grps.type.options|@count > 0}>\r\n          <{foreach from=$sectionContent.fltr_grps.type.options key=text item=value name=optionsloop}>\r\n          <p class=\"useroptions\" id=\"useroptions_<{$sectionContent.index}>_3\" section=\"<{$sectionContent.index}>\" kind=\"3\" name=\"<{$smarty.foreach.optionsloop.index}>\"><input type=\"checkbox\" name=\"advcalc-defaults_<{$sectionContent.index}>[]\" value=<{$smarty.foreach.optionsloop.index}> <{if in_array($smarty.foreach.optionsloop.index, $sectionContent.fltr_grps.type.defaults)}>checked <{/if}>/>&nbsp&nbsp;<input type=\"text\" name=\"advcalc-type_options_<{$sectionContent.index}>_3[<{$smarty.foreach.optionsloop.index}>]\" value=\"<{$value}>\"></input></p>\r\n          <{/foreach}>\r\n        <{else}>\r\n          <p class=\"useroptions\" id=\"useroptions_<{$sectionContent.index}>_3\" section=\"<{$sectionContent.index}>\" kind=\"3\" name=\"0\"><input type=\"text\" name=\"advcalc-type_options_<{$sectionContent.index}>_3[0]\"></input></p>\r\n        <{/if}>\r\n        </div>\r\n        <!--<p><input type=\"button\" class=\"formButton\" name=\"cleardef\" value=\"<{$smarty.const._AM_CLEAR_DEFAULT}>\"/></p>-->\r\n      </div>\r\n      <div id=\"advcalc-type_<{$sectionContent.index}>_4_options\" style=\"<{if $sectionContent.fltr_grps.type.kind neq 4}>visibility: hidden; position: absolute<{/if}>\">\r\n      </div>\r\n\r\n		  <div class=\"description\"></div>\r\n	  </div>\r\n\r\n	  <div class=\"form-item\">\r\n		  <label for=\"advcalc-label_<{$sectionContent.index}>\">Filter label text for end users</label><br />\r\n	    <input type=\"text\" id=\"advcalc-fltr_label_<{$sectionContent.index}>\" name=\"advcalc-fltr_label_<{$sectionContent.index}>\" value=\"<{$sectionContent.fltr_grps.fltr_label}>\" size=\"30\" maxlength=\"255\"/>\r\n		  <div class=\"description\">This is the text that should accompany this filter when it\'s presented to the user, ie: \"Filter by age:\"</div>\r\n	  </div>\r\n	  <div class=\"form-item\">\r\n		  <label for=\"advcalc-label_<{$sectionContent.index}>\">Grouping label text for end users</label><br />\r\n	    <input type=\"text\" id=\"advcalc-grp_label_<{$sectionContent.index}>\" name=\"advcalc-grp_label_<{$sectionContent.index}>\" value=\"<{$sectionContent.fltr_grps.grp_label}>\" size=\"30\" maxlength=\"255\"/>\r\n		  <div class=\"description\">This is the text that should accompany this grouping when it\'s presented to the user, ie: \"Group by age:\"</div>\r\n	  </div>\r\n\r\n	  <div class=\"form-item\">\r\n		  <label for=\"advcalc-form_<{$sectionContent.index}>\">Form element this option should be associated with, if any</label><br />\r\n      <{$sectionContent.form_html}>\r\n		  <div class=\"description\">If this option applies to a specific field, select it here.  For example, if you are making a list where the user can select male or female, then select the form element where the sex is stored and the filter will be applied to that field automatically.</div>\r\n	  </div>\r\n\r\n	  <div class=\"form-item\">\r\n		  <label for=\"advcalc-form_alias_<{$sectionContent.index}>\">Form alias used in the SQL to refer to this form</label><br />\r\n	    <input type=\"text\" id=\"advcalc-form_alias_<{$sectionContent.index}>\" name=\"advcalc-form_alias_<{$sectionContent.index}>\" value=\"<{$sectionContent.fltr_grps.form_alias}>\" size=\"30\" maxlength=\"255\"/>\r\n		  <div class=\"description\">If you are using an alias for this form in the SQL in this procedure, then enter the alias here.</div>\r\n	  </div>\r\n\r\n	  <div class=\"form-item\">\r\n	    <label for=\"advcalc-is_filter_<{$sectionContent.index}>\"><input type=\"checkbox\" id=\"advcalc-is_filter_<{$sectionContent.index}>\" name=\"advcalc-is_filter_<{$sectionContent.index}>\" value=\"1\"<{if $sectionContent.fltr_grps.is_filter eq 1}> checked<{/if}>/>Use as a filter option</label>\r\n	    <label for=\"advcalc-is_group_<{$sectionContent.index}>\"><input type=\"checkbox\" id=\"advcalc-is_group_<{$sectionContent.index}>\" name=\"advcalc-is_group_<{$sectionContent.index}>\" value=\"1\"<{if $sectionContent.fltr_grps.is_group eq 1}> checked<{/if}>/>Use as a grouping option</label>\r\n		  <div class=\"description\"></div>\r\n	  </div>\r\n\r\n\r\n<script type=\"text/javascript\">\r\n  function fltr_grp_type_select(selected) {\r\n    var id = selected.id;\r\n    var base = id.substr(0,id.lastIndexOf(\"_\")+1);\r\n    var name = id + \"_options\";\r\n\r\n    for( var type = 1; type < 4; type++ ) {\r\n      var current = base + type + \"_options\";\r\n      _fltr_grp_type_select = document.getElementById( current );\r\n      if( name == current ) {\r\n        _fltr_grp_type_select.style.visibility = \"visible\";\r\n        _fltr_grp_type_select.style.position = \"static\";\r\n      } else {\r\n        _fltr_grp_type_select.style.visibility = \"hidden\";\r\n        _fltr_grp_type_select.style.position = \"absolute\";\r\n      }\r\n    }\r\n  }\r\n\r\n  function fltr_grp_addoption( button ) {\r\n    section = button.getAttribute(\'section\');\r\n    kind = button.getAttribute(\'kind\');\r\n\r\n    //alert( button.name + \"_\" + section + \"_\" + kind );\r\n\r\n    element = document.getElementById( \"optionlist_\" + section + \"_\" + kind );\r\n    items = element.getElementsByTagName( \"p\" );\r\n    number = items.length;\r\n\r\n    if(kind == 3) { // checkboxes\r\n      appendContents2 = \'<input type=\"checkbox\" name=\"advcalc-defaults_\'+section+\'[]\" value=\'+number+\' />&nbsp&nbsp;\'; \r\n    } else {\r\n      appendContents2 = \'\';\r\n    }\r\n    appendContents2 = appendContents2 + \'<input type=\"text\" name=\"advcalc-type_options_\' + section + \'_\' + kind + \'[\'+number+\']\"></input>\';\r\n    $(\"#optionlist_\" + section + \"_\" + kind ).append(\'<p class=\"useroptions\" id=\"useroptions_\' + section + \'_\' + kind + \'\" name=\"\' + number + \'\" section=\"\' + section + \'\" kind=\"\' + kind + \'\">\' + appendContents2 + \'</p>\');\r\n    setDisplay(\'savewarning\',\'block\');\r\n  }\r\n</script>'),
(179,'<div class=\"panel-content content\">\r\n<{$adminPage.htmlContents}>\r\n</div>'),
(180,'<div class=\"panel-content content\">\r\n<{$adminPage.htmlContents}>\r\n</div>'),
(181,'<div class=\"panel-content content\">\r\n\r\n    <h2>Synchronize With Another System</h2>\r\n\r\n    <p align=\"center\">\r\n        <{include file=\"db:admin/ui-accordion.html\" sectionTemplate=\"db:admin/synchronize_sections.html\" sections=$adminPage.sync}>\r\n    </p>\r\n\r\n</div>'),
(182,'<{if $sectionContent.type == \"import\"}>\r\n    <h3>Select Archive to Synchronize with Current System</h3>\r\n    <form name=\"import\" method=\"POST\" enctype=\"multipart/form-data\">\r\n        <p>Import settings from another system. You will see a list of changes for approval before synchronizing.<br/>\r\n        Please select the zip folder to be imported:</p>\r\n        <p><input type=\"file\" name=\"fileToUpload\" id=\"fileToUpload\"></p>\r\n        <p><input type=\"radio\" name=\"groupsMatch\" value=1 id=\"gm1\" checked=checked><label for=\"gm1\"> The groups in this system should match the other system exactly</label> <br>\r\n        <input type=\"radio\" name=\"groupsMatch\" id=\"gm2\" value=2><label for=\"gm2\"> The groups in this system are different, or partially different, from the other system</label></p>\r\n        <p><input type=\"submit\" name=\"import\" value=\"Synchronize!\"></p>\r\n    </form>\r\n    <div style=\"padding:5px;\">\r\n        <{if $sectionContent.error == \"import_err\"}>\r\n        <span style=\"color: red;\"> an error occurred while importing </span>\r\n        <{/if}>\r\n        <{if $sectionContent.error == \"upload_err\"}>\r\n        <span style=\"color: red;\"> an error occurred while uploading </span>\r\n        <{/if}>\r\n        <{if $sectionContent.error == \"file_err\"}>\r\n        <span style=\"color: red;\"> an error occurred while getting the file name </span>\r\n        <{/if}>\r\n    </div>\r\n<{/if}>\r\n\r\n<{if $sectionContent.type == \"export\"}>\r\n    <h3>Export Current System</h3>\r\n    <form name=\"export\" method=\"POST\">\r\n        <p>Select the user data you would like to include in the export<br/>\r\n            <{html_checkboxes name=\'forms\' options=$sectionContent.checkboxes separator=\'<br />\'}>\r\n        </p>\r\n\r\n        <p>Enter a name for the zip folder: <br/>\r\n        <input type =\"text\" name =\"filename\" value =\"\">\r\n        <input type =\"Submit\" name =\"export\" value =\"Export!\">\r\n        </p>\r\n    </form>\r\n    <{if $sectionContent.error == 1}>\r\n        <span style=\"color: red; \">an error occurred while exporting</span>\r\n    <{/if}>\r\n    <br/>\r\n<{/if}>'),
(183,'<div class=\"panel panel-content\">\r\n\r\n    <h2><{$name}></h2>\r\n\r\n    <div class=\"ui-widget ui-widget-content ui-corner-all\" style=\"padding:10px\">\r\n\r\n        <{if !$content.catalog_error}>\r\n        <div>\r\n            <{if !$content.result}>\r\n            <p>Review the synchronize import details before commiting the changes.</p>\r\n\r\n            <h4>Table Changes:</h4>\r\n            <{include file=\"db:admin/ui-accordion.html\" sectionTemplate=\"db:admin/sync_import_sections.html\" sections=$adminPage.syncimport.content.elements}>\r\n\r\n            <p align=\"center\">\r\n            <form name=\"syncimport\" method=\"POST\">\r\n                <label>Import the above changes to the system: </label>\r\n                <input type=\"submit\" name=\"syncimport\" value=\"Synchronize Import\"/>\r\n            </form>\r\n            </p>\r\n            <{/if}>\r\n\r\n            <{if $content.result}>\r\n            <div style=\"padding:15px;\">\r\n                <{if $content.result.error}>\r\n                <span style=\"color: red;\">{$content.result.error}</span>\r\n                <{/if}>\r\n\r\n                <{if $content.result.success}>\r\n                <span style=\"color:green;\">Successfully completed</span>\r\n                <br><br>\r\n                <a href=\"ui.php?page=home\">Return to Admin</a>\r\n                <{/if}>\r\n            </div>\r\n            <{/if}>\r\n        </div>\r\n        <{/if}>\r\n\r\n\r\n        <{if $content.catalog_error}>\r\n        <div>\r\n            <p>Error: There is no current synchronization import data on the server.</p>\r\n            <br>\r\n            <a href=\"ui.php?page=synchronize\">Return to Synchronize</a>\r\n        </div>\r\n        <{/if}>\r\n\r\n    </div>\r\n\r\n</div>'),
(184,'<div>\r\n\r\n    <{if $sectionContent.createTable}>\r\n    <span style=\"color:green;\">New table!</span>\r\n    <{/if}>\r\n\r\n    <{if count((array) $sectionContent.inserts) > 0}>\r\n    <fieldset>\r\n        <legend>Inserts (<{if $sectionContent.inserts|is_array}><{$sectionContent.inserts|@count}><{/if}>)</legend>\r\n        <ul>\r\n            <{foreach from=$sectionContent.inserts item=rec}>\r\n            <li><{$rec}></li>\r\n            <{/foreach}>\r\n        </ul>\r\n    </fieldset>\r\n    <{/if}>\r\n\r\n    <{if count((array) $sectionContent.updates) > 0}>\r\n    <fieldset>\r\n        <legend>Updates (<{if $sectionContent.updates|is_array}><{$sectionContent.updates|@count}><{/if}>)</legend>\r\n        <ul>\r\n            <{foreach from=$sectionContent.updates key=i item=rec}>\r\n            <li>\r\n                <{$rec}> &mdash; Changed: \r\n                <{foreach from=$sectionContent.fields.$i name=details item=field}>\r\n                    <{if $smarty.foreach.details.iteration > 1}>, <{/if}>\r\n                    <a href=\'\' onclick=\'alert(\"<{$sectionContent.changes.$i.$field.db|replace:\'\"\':\'\\\"\'}>\\n&#9013;&#9013;&#9013;\\n<{$sectionContent.changes.$i.$field.sourceValue|replace:\'\"\':\'\\\"\'}>\");return false;\'><{$field}></a>\r\n                <{/foreach}>\r\n            </li>\r\n            <{/foreach}>\r\n        </ul>\r\n    </fieldset>\r\n    <{/if}>\r\n\r\n    <{if count((array) $sectionContent.deletes) > 0}>\r\n    <fieldset>\r\n        <legend>Deletions (<{if $sectionContent.deletes|is_array}><{$sectionContent.deletes|@count}><{/if}>)</legend>\r\n        <ul>\r\n            <{foreach from=$sectionContent.deletes item=rec}>\r\n            <li><{$rec}></li>\r\n            <{/foreach}>\r\n        </ul>\r\n    </fieldset>\r\n    <{/if}>\r\n\r\n</div>'),
(185,'<div class=\"panel-content content\">\r\n\r\n    <h2>Manage Keys</h2>\r\n\r\n    <p>You can create API keys that will allow Google Sheets to pull in read-only data from Formulize using the IMPORTDATA function. To get started, try putting this formula into a sheet:</p>\r\n    <p></p>=IMPORTDATA(\"<{$xoops_url}>/makecsv.php?key=abc123\")</p>\r\n    <p>Replace abc123 with a key you generate below, and data will be gathered based on the group memberships and permissions of the user associated with the key.</p>\r\n    <br>\r\n    <hr>\r\n    <br>\r\n    <form id=\'managekeys\' name=\'managekeys\' method=\'post\'>\r\n        \r\n    <{if $adminPage.uids}>\r\n\r\n    <p>Generate key for user:<br />\r\n    <{html_radios name=\'uid\' options=$adminPage.uids separator=\'<br />\'}>\r\n    </p><p>Key expires\r\n    <select name=\'expiry\'>\r\n        <option value=\'\'>Never</option>\r\n        <option value=\'1\'>in 1 hour</option>\r\n        <option value=\'2\'>in 2 hours</option>\r\n        <option value=\'5\'>in 5 hours</option>\r\n        <option value=\'8\'>in 8 hours</option>\r\n        <option value=\'12\'>in 12 hours</option>\r\n        <option value=\'24\'>in 1 day</option>\r\n        <option value=\'48\'>in 2 days</option>\r\n        <option value=\'72\'>in 3 days</option>\r\n        <option value=\'96\'>in 4 days</option>\r\n        <option value=\'168\'>in 1 week</option>\r\n        <option value=\'336\'>in 2 weeks</option>\r\n        <option value=\'672\'>in 4 weeks</option>\r\n        <option value=\'1344\'>in 8 weeks</option>\r\n        <option value=\'4368\'>in 6 months</option>\r\n        <option value=\'8760\'>in 1 year</option>\r\n    </select> </p><p> <input type=\'submit\' name=\'save\' value=\'Create\'>\r\n    </p>\r\n    \r\n    <{else}>\r\n        \r\n    <p>To make keys, search for users: <input type=\'text\' name=\'usersearch\' /> <input type=\'submit\' name=\'search\' value=\'Search\'></p>\r\n    \r\n    <{/if}>\r\n     \r\n    <input type=\'hidden\' value=\'\' id=\'deletekey\' name=\'deletekey\' />\r\n    </form>\r\n    <br/><hr><br/>\r\n    \r\n    <table style=\'max-width: 700px;\'>\r\n    <{foreach from=$adminPage.keys item=key}>\r\n        <tr><td><a href=\'\' onclick=\"setDelete(\'<{$key.key}>\');return false;\"><img src=\'../images/x.gif\'/></a>&nbsp;<{$key.user}>&nbsp;&nbsp;&nbsp;<br/>&nbsp;</td><td><{$key.key}></td><td>&nbsp;&nbsp;&nbsp;<{$key.expiry}></td></tr>\r\n    <{/foreach}>\r\n    </table>\r\n    \r\n    \r\n    <p><b>WARNING!</b> These keys are visible in the formulas in the sheeets where you use them. Any person who has access to the spreadsheet can see the key too, and could use it to access data in Formulize. Be very careful where and with whom you share these spreadsheets!</p>\r\n    \r\n</div>\r\n\r\n<script>\r\nfunction setDelete(key) {\r\n    window.managekeys.deletekey.value = key;\r\n    window.managekeys.submit();\r\n}\r\n</script>'),
(186,'<div class=\"panel-content content\">\r\n\r\n    <h2>Manage Account Creation Tokens</h2>\r\n    <p>You can create tokens that can be used to allow new users to create a Formulize account on the first time that they login to the site with Google.</p>\r\n    <br>\r\n    <hr>\r\n    <br>\r\n    <form id=\'managetokens\' name=\'managetokens\' method=\'post\'>\r\n    <p>Allow the user to join which groups?\r\n    <br>\r\n    <br>\r\n    <{foreach from=$adminPage.groups item=key}>\r\n   <input id=\'<{$key.groupid}>\' name=\'<{$key.groupid}>\' type=\"checkbox\" value=\"<{$key.name}>\">\r\n   <label for=\'<{$key.groupid}>\'><{$key.name}></label>\r\n   <br>\r\n    <{/foreach}>\r\n    <br>\r\n    What is the maximum number of uses that this token should have?\r\n <select name=\'maxuses\'>\r\n        <option value=\'1\'>1</option>\r\n        <option value=\'2\'>2</option>\r\n        <option value=\'5\'>5</option>\r\n        <option value=\'10\'>10</option>\r\n        <option value=\'20\'>20</option>\r\n        <option value=\'30\'>30</option>\r\n        <option value=\'50\'>50</option>\r\n        <option value=\'75\'>75</option>\r\n        <option value=\'100\'>100</option>\r\n        <option value=\'0\'>Unlimited</option>\r\n    </select>\r\n    <br>\r\n    <br>\r\n    Select token length:\r\n     <select name=\'tokenlength\'>\r\n        <option value=\'32\'>32 bytes</option>\r\n        <option value=\'16\'>16 bytes</option>\r\n        <option value=\'64\'>64 bytes</option>\r\n    </select>\r\n    <br>\r\n    <br>\r\n    When should the token expire?\r\n    <select name=\'expiry\'>\r\n        <option value=\'\'>Never</option>\r\n        <option value=\'1\'>in 1 hour</option>\r\n        <option value=\'2\'>in 2 hours</option>\r\n        <option value=\'5\'>in 5 hours</option>\r\n        <option value=\'8\'>in 8 hours</option>\r\n        <option value=\'12\'>in 12 hours</option>\r\n        <option value=\'24\'>in 1 day</option>\r\n        <option value=\'48\'>in 2 days</option>\r\n        <option value=\'72\'>in 3 days</option>\r\n        <option value=\'96\'>in 4 days</option>\r\n        <option value=\'168\'>in 1 week</option>\r\n        <option value=\'336\'>in 2 weeks</option>\r\n        <option value=\'672\'>in 4 weeks</option>\r\n        <option value=\'1344\'>in 8 weeks</option>\r\n        <option value=\'4368\'>in 6 months</option>\r\n        <option value=\'8760\'>in 1 year</option>\r\n    </select> &nbsp;&nbsp;&nbsp;<input type=\'submit\' name=\'save\' value=\'Create\'>\r\n    </p>\r\n    <input type=\'hidden\' value=\'\' id=\'deletekey\' name=\'deletekey\' />\r\n    </form>\r\n    <br/><hr><br/>\r\n    \r\n    <table style=\'max-width: 700px;\'>\r\n    <{foreach from=$adminPage.keys item=key}>\r\n        <tr><td><a href=\'\' onclick=\"setDelete(\'<{$key.key}>\');return false;\"><img src=\'../images/x.gif\'/></a>&nbsp;<{$key.user}>&nbsp;&nbsp;&nbsp;<br/>&nbsp;</td><td><{$key.key}></td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td><{$key.expiry}></td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td><{$key.group}></td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td><{$key.usesleft}></td></tr>\r\n    <{/foreach}>\r\n    </table>\r\n    \r\n</div>\r\n\r\n<script>\r\nfunction setDelete(key) {\r\n    window.managetokens.deletekey.value = key;\r\n    window.managetokens.submit();\r\n}\r\n</script>'),
(187,'<div class=\"form-item\">\r\n    <fieldset>\r\n      <legend><{$smarty.const._AM_ELE_DELIM_CHOICE}></legend>\r\n      <div class=\"form-radios\">\r\n        <label for=\"br\"><input type=\"radio\" id=\"br\" name=\"element_delimit\" value=\"br\"/><{$smarty.const._MI_formulize_DELIMETER_BR}></label>\r\n      </div>\r\n      <div class=\"form-radios\">\r\n        <label for=\"space\"><input type=\"radio\" id=\"space\" name=\"element_delimit\" value=\"space\"/><{$smarty.const._MI_formulize_DELIMETER_SPACE}></label>\r\n      </div>\r\n      <div class=\"form-radios\">\r\n	      <label for=\"custom\" style=\"display:inline\">\r\n        <input type=\"radio\" id=\"custom\" name=\"element_delimit\" value=\"custom\"/><{$smarty.const._MI_formulize_DELIMETER_CUSTOM}></label>\r\n  	    <input type=\"text\" id=\"element-delim_custom\" name=\"element_delim_custom\" value=\"<{$content.ele_delim_custom_value}>\" size=\"25\" maxlength=\"255\"/>\r\n      </div>\r\n    </fieldset>\r\n  </div>\r\n  \r\n  <script type=\'text/javascript\'>\r\n$(\"#element-delimit-custom\").click(function () {\r\n  $(\"#element-delim_custom\").focus();\r\n});\r\n\r\n$(\"#element-delim_custom\").click(function () {\r\n  $(\"#custom\").attr(\"checked\", \"checked\"); \r\n});\r\n\r\n\r\n$(\"#<{$content.ele_delim}>\").attr(\'checked\',1);\r\n</script>'),
(188,'<p><a name=\"deldata\" href=\"\" target=\"<{$sectionContent.index}>\"><img src=\"../images/editdelete.gif\"> Delete this dataset</a></p>\r\n		<p>Properties for this dataset:</p>\r\n        \r\n        <p>Scope <select name=\'scopes[]\'>\r\n        <option value=\'mine\' <{if $sectionContent.scope == \'mine\'}>selected<{/if}>>The user\'s own entries</option>\r\n        <option value=\'group\' <{if $sectionContent.scope == \'group\'}>selected<{/if}>>The user\'s groups\' entries</option>\r\n        <option value=\'all\' <{if $sectionContent.scope == \'all\'}>selected<{/if}>>All entries</option>\r\n        </select></p>\r\n        \r\n        <p>The element handle with the date <input type=\'text\' name=\'datehandles[]\' value=\'<{$sectionContent.datehandle}>\' /></p>\r\n        \r\n        <p>The viewentryscreen <input type=\'text\' name=\'viewentryscreens[]\' value=\'<{$sectionContent.viewentryscreen}>\' /></p>\r\n        \r\n        <p>The text color (not implemented yet) <input type=\'text\' name=\'textcolors[]\' value=\'<{$sectionContent.textcolor}>\' /></p>\r\n        \r\n        <p>Use add icons? <input type=\'radio\' name=\'useaddicons[]\' value=1 <{if $sectionContent.useaddicons == 1}>checked<{/if}>>Yes <input type=\'radio\' name=\'useaddicons[]\' value=0 <{if $sectionContent.useaddicons == 0}>checked<{/if}>>No\'</p>\r\n        <p>Use delete icons? <input type=\'radio\' name=\'usedeleteicons[]\' value=1 <{if $sectionContent.usedeleteicons == 1}>checked<{/if}>>Yes <input type=\'radio\' name=\'usedeleteicons[]\' value=0 <{if $sectionContent.usedeleteicons == 0}>checked<{/if}>>No\'</p>\r\n        \r\n        <p>Template for how to summarize each entry in the calendar\r\n        <textarea name=\'clicktemplates[]\'><{$sectionContent.clicktemplate}></textarea></p>\r\n        \r\n        <p>Optional - alternate form Id <input type=\'text\' name=\'fids[]\' value=\'<{$sectionContent.fid}>\' /></p>\r\n        <p>Optional - alternate relationship Id <input type=\'text\' name=\'frids[]\' value=\'<{$sectionContent.frid}>\' /></p>'),
(189,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we\'re inserting/updating on *}>\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n<{$securitytoken}>\r\n<input type=\"hidden\" name=\"formulize_admin_handler\" value=\"screen_calendar_data\">\r\n<input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.sid}>\">\r\n<input type=\"hidden\" name=\"formulize_admin_op\" value=\"\">\r\n<input type=\"hidden\" name=\"formulize_admin_index\" value=\"\">\r\n<input type=\"hidden\" name=\"reload_calendar_data\" value=\"\">\r\n\r\n    <p><a name=\"adddata\" href=\"\"><img src=\"../images/filenew2.png\"> Add new dataset</a></p>\r\n    <h2>Manage the datasets in this screen:</h2>\r\n    \r\n<div>\r\n<{include file=\"db:admin/ui-accordion.html\" sectionTemplate=\"db:admin/screen_calendar_data_sections.html\" sections=$content.data}>\r\n</div>\r\n\r\n\r\n</form>\r\n\r\n\r\n<script type=\'text/javascript\'>\r\n\r\n  $(\"[name=adddata]\").click(function () {\r\n    $(\"[name=formulize_admin_op]\").val(\'adddata\');\r\n    $(\".savebutton\").click();\r\n    return false;\r\n  });\r\n  \r\n	\r\n	$(\"[name=deldata]\").click(function () {\r\n			var answer = confirm(\'Are you sure you want to remove this dataset?\');\r\n			if (answer)	{\r\n		    $(\"[name=formulize_admin_op]\").val(\'deldata\');\r\n		    $(\"[name=formulize_admin_index]\").val($(this).attr(\'target\'));\r\n		    $(\".savebutton\").click();\r\n			}\r\n		  return false;\r\n	});\r\n\r\n    \r\n</script>'),
(190,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we are inserting/updating on *}>\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n    <{$securitytoken}>\r\n    <input type=\"hidden\" name=\"formulize_admin_handler\" value=\"screen_calendar_templates\">\r\n    <input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.sid}>\">\r\n\r\n    <div class=\"panel-content content\">\r\n        <p><b>Showing templates for use with this theme:</b> <{html_options name=screens-theme options=$content.themes selected=$content.selectedTheme}></p>\r\n        <p><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_TOPTEMPLATE3}></p>\r\n        <p><{$smarty.const._AM_FORMULIZE_SCREEN_CAL_DESC_TOPTEMPLATE}></p>\r\n        <p><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_TOPTEMPLATE2}></p>\r\n        <br />\r\n        <div class=\"form-item\">\r\n            <fieldset>\r\n                <legend>Top Template</legend>\r\n                <textarea id=\"screens-toptemplate\"  name=\"screens-toptemplate\" class=\"code-textarea\" rows=\"20\"/>\r\n<{$content.toptemplate}></textarea>\r\n                <br />\r\n                <p><a class=\"formulize-open-close-link\" open-text=\"View element handles\" close-text=\"Hide element handles\" linked-block-id=\"#formulize-open-close-section\">View element handles</a></p>\r\n                <div id=\"formulize-open-close-section\" style=\"display:none;\">\r\n                    <br />\r\n                    <table>\r\n                        <tr><th>Form element</th><th>handle</th></tr>\r\n                        <{foreach from=$content.caltemplatehelp item=row}>\r\n                            <{$row}>\r\n                        <{/foreach}>\r\n                    </table>\r\n                </div>\r\n            </fieldset>\r\n        </div>\r\n\r\n        <div class=\"form-item\">\r\n            <fieldset>\r\n            <legend>Bottom Template</legend>\r\n                <textarea id=\"screens-bottomtemplate\" name=\"screens-bottomtemplate\" class=\"code-textarea\" rows=\"20\"/>\r\n<{$content.bottomtemplate}></textarea>\r\n            </fieldset>\r\n        </div>\r\n\r\n    </div>\r\n</form>\r\n<script>\r\njQuery(document).ready(function() {\r\n    jQuery(\".savebutton\").click(function() {\r\n        fz_check_php_code(jQuery(\"#screens-toptemplate\").val(), \"Top template\", \"<{$icms_url}>\", <{$icms_userid}>);\r\n        fz_check_php_code(jQuery(\"#screens-bottomtemplate\").val(), \"Bottom template\", \"<{$icms_url}>\", <{$icms_userid}>);\r\n    });\r\n});\r\n</script>'),
(191,'<form method=\"post\" id=\"passcode_form\">\r\n    <h1><{$smarty.const._PASSWORD}></h1>\r\n    <p><input type=\"text\" name=\"passcode\" id=\"passcode\" value=\"\" width=50/><br /><br /></p>\r\n    <p><input type=\"submit\" class=\"formulize_button\" id=\"formulize_addButton\" value=\"<{$smarty.const._SUBMIT}>\" /></p>\r\n </form>\r\n <script>\r\n    $(document).ready(function() {\r\n        $(\'#passcode\').focus();  \r\n    });\r\n </script>'),
(196,'<div class=\"form-item\">\r\n    <fieldset>\r\n        <legend>When displying this element as a list box, display these values:</legend>\r\n        <{$content.displayElements}>\r\n        <div class=\"description\">\r\n        This feature is useful to display multiple elements from the linked form.\r\n        </div>\r\n    </fieldset>\r\n</div>\r\n\r\n<div class=\"form-item\">\r\n    <fieldset>\r\n        <legend>When displaying this element in a list of entries, or including it in a dataset with the API, use values from a different element in the source form:</legend>\r\n        <{$content.listValue}>\r\n        <div class=\"description\">\r\n        This feature is useful if you want the value used in the list of entries to be different from the value that users choose when filling in the form.  For example, you might link to a form called \"Master List of Workshops\" and in that form there are two fields, the name of the workshop, and a code number that is used to refer to them.  You might link the element to the name of the workshop, to get a list of workshops for users to choose from.  But in a list of entries, you want the code number for the workshop to be displayed.  In that case, you would pick the code number element here.\r\n        </div>\r\n    </fieldset>\r\n</div>\r\n\r\n<div class=\"form-item\" style=\"margin-bottom: 0px\">\r\n    <fieldset>\r\n        <legend>When exporting this element to a spreadsheet, use values from a different element in the source form:</legend>\r\n        <{$content.exportValue}>\r\n        <div class=\"description\">\r\n        This feature is essentially the same as the previous one, except it applies only when data is being exported to a spreadsheet, not when lists of entries are displayed on screen.\r\n        </div>\r\n    </fieldset>\r\n</div>'),
(197,'<{* form elements must be named with their object name hyphen field name *}>\r\n<{* no other elements should have hyphens, since that tells the saving system that this is a property of an object to update *}>\r\n<{* securitytoken should be part of the form *}>\r\n<{* formulize_admin_handler and formulize_admin_key are required, to tell what the name of the save handling file is, and what the key is that we are inserting/updating on *}>\r\n\r\n<form id=\"form-<{$number}>\" class=\"formulize-admin-form\">\r\n    <{$securitytoken}>\r\n    <input type=\"hidden\" name=\"formulize_admin_handler\" value=\"screen_form_templates\">\r\n    <input type=\"hidden\" name=\"formulize_admin_key\" value=\"<{$content.sid}>\">\r\n\r\n    <div class=\"panel-content content\">\r\n        <p><b>Showing templates for use with this theme:</b> <{html_options name=screens-theme options=$content.themes selected=$content.selectedTheme}></p>\r\n        <p><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_TOPTEMPLATE3}></p><br />\r\n        \r\n        <{if !$content.usingTemplates}>\r\n            <p><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_SEEDTEMPLATES1}><{$content.seedtemplates}><{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_SEEDTEMPLATES2}></p><br /><p><input type=\"button\" value=\"<{$smarty.const._AM_FORMULIZE_SCREEN_LOE_DESC_SEEDTEMPLATES3}>\" id=\"seedtemplates\"></p><br />        \r\n        <{/if}>\r\n        \r\n        <{if $content.usingTemplates}>\r\n        \r\n            <{include file=\"db:admin/screen_form_template_boxes.html\"}>\r\n        \r\n        <{/if}>\r\n\r\n    </div>\r\n</form>\r\n<script>\r\njQuery(document).ready(function() {\r\n    jQuery(\".savebutton\").click(function() {\r\n        fz_check_php_code(jQuery(\"#bottomtemplate\").val(), \"Bottom Template\", \"<{$icms_url}>\", <{$icms_userid}>);\r\n        fz_check_php_code(jQuery(\"#toptemplate\").val(), \"Top Template\", \"<{$icms_url}>\", <{$icms_userid}>);\r\n        fz_check_php_code(jQuery(\"#elementtemplate1\").val(), \"Element Template (one column)\", \"<{$icms_url}>\", <{$icms_userid}>);\r\n        fz_check_php_code(jQuery(\"#elementtemplate2\").val(), \"Element Template (two columns)\", \"<{$icms_url}>\", <{$icms_userid}>);\r\n    });\r\n});\r\n</script>'),
(198,'<div class=\"form-item\">\r\n            <fieldset>\r\n                <legend>Top Template</legend>\r\n                <textarea id=\"toptemplate\"  name=\"toptemplate\" class=\"code-textarea\" rows=\"20\"/>\r\n<{$content.toptemplate}></textarea>\r\n            </fieldset>\r\n        </div>\r\n        <div class=\'description\'>The top template is rendered before the elements in the form. If you need to contain the form elements inside a table, or div, this is where you would open that tag. You might also want to include custom navigation, instructions, etc, above the form. Use the variable $formTitle for the title of the form.</div>\r\n        <br />\r\n\r\n        <div class=\"form-item\">\r\n            <fieldset>\r\n            <legend>Element Container Template (opening)</legend>\r\n                <textarea id=\"elementcontainero\" name=\"elementcontainero\" class=\"code-textarea\" rows=\"5\"/>\r\n<{$content.elementcontainero}></textarea>\r\n            </fieldset>\r\n        </div>\r\n        <div class=\'description\'>All form elements are contained inside other markup with a consistent id. Use the variable $elementContainerId for the id, ie: \"&lt;div id=\'$elementContainerId\' ...\"<br>This id is used by the conditional element logic to show and hide elements. Conditional elements won\'t work unless you use this id.<br>You can use a table row, a div, even a series of divs or whatever you want. You just have to make sure the HTML works seamlessly between the top, container, element, and bottom templates, and the $elementContainerId is used on the parent item in the DOM that contains the form elements.</div>\r\n        <br />\r\n        \r\n        <div class=\"form-item\">\r\n            <fieldset>\r\n            <legend>Element Template (two columns)</legend>\r\n                <textarea id=\"elementtemplate2\" name=\"elementtemplate2\" class=\"code-textarea\" rows=\"20\"/>\r\n<{$content.elementtemplate2}></textarea>\r\n            </fieldset>\r\n        </div>\r\n        <div class=\'description\'>The element template is used to render each element in the form.<br>The two column version is used when the two column option is selected on the Options tab, and is intended for situations where you want captions on the left and form elements on the right.<br><br>You can use the following variables in the element templates:<ul>\r\n        <li>$elementName - the markup name that is used to uniquely identify the element</li>\r\n        <li>$elementCaption - the caption for the element</li>\r\n        <li>$elementHelpText - any help text for the element</li>\r\n        <li>$elementIsRequired - a flag to indicate if the user must provide a response to the element. The enforcement of required elements is done automatically, but you may wish to highlight them differently than others when displaying on screen</li>\r\n        <li>$renderedElement - use this variable to drop in the actual HTML for the element itself, ie: the textbox, the radio buttons, etc</li>\r\n        <li>$elementObject - the Formulize element object, containing all the configuration settings of the element</li>\r\n        <li>$column1Width - the CSS width setting for the first (and possibly only) column. Note it does <b>not</b> have a ; on the end.</li>\r\n        <li>$column2Width - the CSS width setting for the second column, if relevant. Note it does <b>not</b> have a ; on the end.</li>\r\n        <li>$spacerNeeded - a flag indicating if there should be some kind of spacer added to the end of the element display, to maintain the appropriate widths. For example, if the form is rendered in a 100% width table, and the two columns are handled as table cells, then a third, empty table cell, with \'auto\' width could use up the rest of the space on screen so that the specified widths of the first two columns are respected.</li>\r\n        <li>$colSpan - a special value that will be passed in for use in the \"Element Template (one column)\" and should be included in any table cell if you are using one, so that it will span across two columns when appropriate, ie: for the \"Text for display spanning both columns\" element type.</li></ul></div>\r\n        <br />\r\n\r\n        <div class=\"form-item\">\r\n            <fieldset>\r\n            <legend>Element Template (one column)</legend>\r\n                <textarea id=\"elementtemplate1\" name=\"elementtemplate1\" class=\"code-textarea\" rows=\"20\"/>\r\n<{$content.elementtemplate1}></textarea>\r\n            </fieldset>\r\n        </div>\r\n        <div class=\'description\'>The element template is used to render each element in the form.<br>The one column version is used when the one column option is selected on the Options tab, and is intended for situations where you want captions and the form elements in a single column vertically. This is usually a good layout for mobile devices.</div>\r\n        <br />\r\n        \r\n        <div class=\"form-item\">\r\n            <fieldset>\r\n            <legend>Element Container Template (closing)</legend>\r\n                <textarea id=\"elementcontainerc\" name=\"elementcontainerc\" class=\"code-textarea\" rows=\"5\"/>\r\n<{$content.elementcontainerc}></textarea>\r\n            </fieldset>\r\n        </div>\r\n        <div class=\'description\'>The element container (closing) ends the element container started above.<br>If you opened a table row above and show each element in a table cell, you would need to use a &lt;/tr> here, for example.</div>\r\n        \r\n        <!--\r\n            displayType not actually used yet. Might be relevant in more complex themes? The js for displaying conditional elements simply sets the display property in css to null, and the browser figures out what is appropriate, but that\'s sort of a hack and might not work in all cases.\r\n            <div class=\"form-item\">\r\n            <fieldset>\r\n                <legend>Element Container Display Type</legend>\r\n                <{html_options name=\"screens-displayType\" options=$content.displayOptions selected=$content.displayType}>\r\n            </fieldset>\r\n        </div>\r\n        <div class=\'description\'>When conditional elements are shown, the CSS display setting is set to this value.</div>-->\r\n        <br />\r\n        \r\n        <div class=\"form-item\">\r\n            <fieldset>\r\n                <legend>Bottom Template</legend>\r\n                <textarea id=\"bottomtemplate\"  name=\"bottomtemplate\" class=\"code-textarea\" rows=\"20\"/>\r\n<{$content.bottomtemplate}></textarea>\r\n            </fieldset>\r\n        </div>\r\n        <div class=\'description\'>The bottom template is rendered after the elements in the form. You can use it to close any tags you opened in the top template. You can also add other navigation or information below the form.</div>\r\n        <br />'),
(199,'<ul id=\"mainmenu\" class=\"main-menu\"><{foreach item=links from=$block.content}>  <li class=\"main-menu__item\">    <a href=\"<{$links.url}>\" target=\"<{$links.target}>\" class=\"main-menu__item-link<{if $links.active}> main-menu__item-link--active<{/if}>\">      <{if $links.icon}><span class=\"main-menu__item-link-icon\">        <{$links.icon}>      </span><{/if}>      <{$links.title}>    </a>  </li>  <{if is_array($links.subs)}>    <{foreach item=subs from=$links.subs}>    <li class=\"main-menu__item main-menu__subitem\">      <a href=\"<{$subs.url}>\" target=\"<{$subs.target}>\" class=\"main-menu__item-link<{if $subs.active}> main-menu__item-link--active<{/if}>\">        <{if $links.icon}><span class=\"main-menu__item-link-icon\">          <{$subs.icon}>        </span><{/if}>        <{$subs.title}>      </a>    </li>    <{/foreach}>  <{/if}><{/foreach}></ul>'),
(200,'<{if is_array($block.content)==false}>    <{$block.content}><{else}>    <{include file=\"db:blocks/menu.html\"}><{/if}>'),
(201,'<div class=\"panel-content content\">\r\n\r\n    <{if $adminPage.mailStatus}>\r\n    <h2>Mail Status:</h2>\r\n    <{$adminPage.mailStatus}>\r\n    <{/if}>\r\n\r\n    <h2>Email Users</h2>\r\n\r\n    <form method=\'post\' enctype=\'multipart/form-data\'>\r\n    \r\n    <p><b>Send To:</b></p>\r\n    <select name=\'groups[]\' size=<{$adminPage.groupListSize}> multiple>\r\n        <{html_options values=$adminPage.groupIds output=$adminPage.groupNames}>\r\n    </select>\r\n    \r\n    <p><b>Subject:</b></p>\r\n    <input type=\'text\' name=\'subject\'>\r\n        \r\n    <p><b>Body:</b></p>\r\n    <textarea name=\'body\' rows=10></textarea>\r\n    \r\n    <p><b>Attachment:</b> (optional)</p>\r\n    <input type=\'file\' name=\'attachment\'>\r\n    \r\n    <p><input type=\'submit\' name=\'submit\' value=\'Send\'></p>\r\n    \r\n    </form>\r\n    \r\n    </div>'),
(202,'<div class=\"panel-content content\">\r\n\r\n    <h2>Copy Group Permissions</h2>\r\n    <p>You can copy permissions from one group to another group or groups.</p>\r\n    <br>\r\n    <hr>\r\n    <br>\r\n    <form id=\'managepermissions\' name=\'managepermissions\' method=\'post\'>\r\n    <p>Copy permissions from which group?\r\n    <select id=\"managepermissions-source\" name=\"managepermissions-source\" size=\"1\">\r\n        <{foreach from=$adminPage.groups key=id item=name}>\r\n            <option label=\"<{$name}>\" value=\"<{$id}>\"><{$name}></option>           \r\n        <{/foreach}>\r\n    </select>\r\n    </p>\r\n    <p>Copy permissions to which group(s)?</p>\r\n    <{foreach from=$adminPage.groups key=id item=name}>\r\n        <input id=\'<{$id}>\' name=\'<{$id}>\' type=\"checkbox\" value=\"<{$name}>\">\r\n        <label for=\'<{$id}>\'><{$name}></label>\r\n        <br>\r\n    <{/foreach}>\r\n    <br>\r\n    <input type=\'radio\' name=\'formulize-or-all\' value=\'all-perms\' id=\'all-perms\' checked=\'checked\'> <label for=\'all-perms\'>Copy all permissions system wide</label><br>\r\n    <input type=\'radio\' name=\'formulize-or-all\' value=\'formulize-perms\' id=\'formulize-perms\'> <label for=\'formulize-perms\'>Copy only Formulize permissions</label>\r\n    <br><br>\r\n    <input type=\'submit\' name=\'save\' value=\'Copy\' onclick=\'return confirmCopy();\'>\r\n    </form>\r\n    <p>Note: if you have multiple sets of groups, they should follow this type of naming convention: Toronto Volunteers, Toronto Managers; Ottawa Volunteers, Ottawa Managers. ie: the set name first, and then the group type last. If you don\'t follow this convention, then group level visibility settings (where one group see entries created by another group) won\'t necessarily copy over correctly. Check your permissions after copying to make sure they\'re right!</p>\r\n</div>\r\n\r\n<script>\r\nfunction confirmCopy() {\r\n    var sourceGroupName = jQuery(\'#managepermissions-source option:selected\').text();\r\n    var targetGroupNames = \"\";\r\n    jQuery(\'input[type=\"checkbox\"]:checked\').each(function() {\r\n        targetGroupNames += \"\\n\"+this.value;\r\n    });\r\n    return confirm(\"Are you sure you want to copy the permissions from \"+sourceGroupName+\" to the group(s):\\n\"+targetGroupNames+\"\\n\\nThis will REMOVE all permissions for the target group(s) and REPLACE them with the permissions from the source group.\");    \r\n}\r\n</script>'),
(203,'<{if is_array($block.content)==false}>    <{$block.content}><{else}>    <{include file=\"db:blocks/menu.html\"}><{/if}>');
/*!40000 ALTER TABLE `ai8k7Bba_tplsource` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_users`
--

DROP TABLE IF EXISTS `ai8k7Bba_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_users` (
  `uid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL DEFAULT '',
  `uname` varchar(175) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `user_avatar` varchar(30) NOT NULL DEFAULT 'blank.gif',
  `user_regdate` int(10) unsigned NOT NULL DEFAULT 0,
  `user_icq` varchar(15) NOT NULL DEFAULT '',
  `user_from` varchar(100) NOT NULL DEFAULT '',
  `user_sig` text NOT NULL,
  `user_viewemail` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `actkey` varchar(8) NOT NULL DEFAULT '',
  `user_aim` varchar(18) NOT NULL DEFAULT '',
  `user_yim` varchar(25) NOT NULL DEFAULT '',
  `user_msnm` varchar(100) NOT NULL DEFAULT '',
  `pass` varchar(255) NOT NULL DEFAULT '',
  `posts` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `attachsig` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `rank` smallint(5) unsigned NOT NULL DEFAULT 0,
  `level` varchar(3) NOT NULL DEFAULT '1',
  `theme` varchar(100) NOT NULL DEFAULT '',
  `timezone_offset` float(3,1) NOT NULL DEFAULT 0.0,
  `last_login` int(10) unsigned NOT NULL DEFAULT 0,
  `umode` varchar(10) NOT NULL DEFAULT '',
  `uorder` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `notify_method` tinyint(1) NOT NULL DEFAULT 1,
  `notify_mode` tinyint(1) NOT NULL DEFAULT 0,
  `user_occ` varchar(100) NOT NULL DEFAULT '',
  `bio` tinytext NOT NULL,
  `user_intrest` varchar(150) NOT NULL DEFAULT '',
  `user_mailok` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `language` varchar(100) NOT NULL DEFAULT '',
  `openid` varchar(255) NOT NULL DEFAULT '',
  `salt` varchar(255) NOT NULL DEFAULT '',
  `user_viewoid` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `pass_expired` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `enc_type` tinyint(2) unsigned NOT NULL DEFAULT 1,
  `login_name` varchar(175) NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `login_name` (`login_name`),
  KEY `uname` (`uname`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_users`
--

LOCK TABLES `ai8k7Bba_users` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_users` DISABLE KEYS */;
INSERT INTO `ai8k7Bba_users` VALUES
(1,'','Polygon','julian@polygon.red','https://massey.formulize.net/','blank.gif',1671220912,'','','',0,'','','','','2ef3baef547b7b5d2ef772c6fd643ba956bfcba1ca07ef20a32ef3af61abd29c',0,0,7,'1','Anari',-5.0,1706645872,'thread',0,2,0,'','','',0,'english','','4vvVnhMh073al6NoPQW1Tp0Scjc90Pp5EgNzVUH7rrTNTC6C5mgnkvymLgi07aYjR',0,0,1,'polygon'),
(2,'','Roumen Andreev','andreev.rom.ca@gmail.com','https://massey.formulize.net/','blank.gif',1706197172,'','','',0,'','','','','9534e94099baf2a424024e20b96071917c50cc204d055944a2be31f17d24e282',0,0,7,'5','Anari',-5.0,1707942110,'thread',0,2,0,'','','',0,'english','','TxsyKdOerDxfnaWu3RHNWdYd4sonxFSpJ9S4yUjqFHdWye5SOuAVkYcCzS7gNKkW3',0,0,1,'admin');
/*!40000 ALTER TABLE `ai8k7Bba_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_xoopscomments`
--

DROP TABLE IF EXISTS `ai8k7Bba_xoopscomments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_xoopscomments` (
  `com_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `com_pid` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `com_rootid` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `com_modid` smallint(5) unsigned NOT NULL DEFAULT 0,
  `com_itemid` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `com_icon` varchar(25) NOT NULL DEFAULT '',
  `com_created` int(10) unsigned NOT NULL DEFAULT 0,
  `com_modified` int(10) unsigned NOT NULL DEFAULT 0,
  `com_uid` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `com_ip` varchar(15) NOT NULL DEFAULT '',
  `com_title` varchar(255) NOT NULL DEFAULT '',
  `com_text` text NOT NULL,
  `com_sig` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `com_status` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `com_exparams` varchar(255) NOT NULL DEFAULT '',
  `dohtml` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `dosmiley` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `doxcode` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `doimage` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `dobr` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`com_id`),
  KEY `com_pid` (`com_pid`),
  KEY `com_itemid` (`com_itemid`),
  KEY `com_uid` (`com_uid`),
  KEY `com_title` (`com_title`(40))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_xoopscomments`
--

LOCK TABLES `ai8k7Bba_xoopscomments` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_xoopscomments` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_xoopscomments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai8k7Bba_xoopsnotifications`
--

DROP TABLE IF EXISTS `ai8k7Bba_xoopsnotifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai8k7Bba_xoopsnotifications` (
  `not_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `not_modid` smallint(5) unsigned NOT NULL DEFAULT 0,
  `not_itemid` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `not_category` varchar(30) NOT NULL DEFAULT '',
  `not_event` varchar(30) NOT NULL DEFAULT '',
  `not_uid` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `not_mode` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`not_id`),
  KEY `not_modid` (`not_modid`),
  KEY `not_itemid` (`not_itemid`),
  KEY `not_class` (`not_category`),
  KEY `not_uid` (`not_uid`),
  KEY `not_event` (`not_event`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai8k7Bba_xoopsnotifications`
--

LOCK TABLES `ai8k7Bba_xoopsnotifications` WRITE;
/*!40000 ALTER TABLE `ai8k7Bba_xoopsnotifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai8k7Bba_xoopsnotifications` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-02-14 20:30:00
