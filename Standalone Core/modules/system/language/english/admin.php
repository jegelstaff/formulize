<?php
// $Id: admin.php 9551 2009-11-14 14:43:53Z pesianstranger $
//%%%%%%	File Name  admin.php 	%%%%%
//define('_MD_AM_CONFIG','System Configuration');
define('_INVALID_ADMIN_FUNCTION', 'Invalid Admin Function');

// Admin Module Names
define('_MD_AM_ADGS','Groups');
define('_MD_AM_BANS','Banners');
define('_MD_AM_BKAD','Blocks');
define('_MD_AM_MDAD','Modules Admin');
define('_MD_AM_SMLS','Smilies');
define('_MD_AM_RANK','User Ranks');
define('_MD_AM_USER','Edit Users');
define('_MD_AM_FINDUSER', 'Find Users');
define('_MD_AM_PREF','Preferences');
define('_MD_AM_VRSN','Version Checker');
define('_MD_AM_MLUS', 'Mail Users');
define('_MD_AM_IMAGES', 'Image Manager');
define('_MD_AM_AVATARS', 'Avatars');
define('_MD_AM_TPLSETS', 'Templates');
define('_MD_AM_COMMENTS', 'Comments');
define('_MD_AM_BKPOSAD','Block Positions');
define('_MD_AM_PAGES','Symlink Manager');
define('_MD_AM_CUSTOMTAGS', 'Custom Tags');

// Group permission phrases
define('_MD_AM_PERMADDNG', 'Could not add %s permission to %s for group %s');
define('_MD_AM_PERMADDOK','Added %s permission to %s for group %s');
define('_MD_AM_PERMRESETNG','Could not reset group permission for module %s');
define('_MD_AM_PERMADDNGP', 'All parent items must be selected.');
######################## Added in 1.2 ###################################
if(!defined('_MD_AM_AUTOTASKS')){define('_MD_AM_AUTOTASKS', 'Auto Tasks');}
define('_MD_AM_ADSENSES', 'Adsenses');
define('_MD_AM_RATINGS', 'Ratings');
define('_MD_AM_MIMETYPES', 'Mime Types');
?>