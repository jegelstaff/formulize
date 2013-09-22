<?php
// $Id: modinfo.php 10326 2010-07-11 18:54:25Z malanciault $
// Module Info

// The name of this module
define("_MI_SYSTEM_NAME","System");

// A brief description of this module
define("_MI_SYSTEM_DESC","For administration of core settings of the site.");

// Names of blocks for this module (Not all module has blocks)
define("_MI_SYSTEM_BNAME2","User Menu");
define("_MI_SYSTEM_BNAME3","Login");
define("_MI_SYSTEM_BNAME4","Search");
define("_MI_SYSTEM_BNAME5","Waiting Contents");
define("_MI_SYSTEM_BNAME6","Main Menu");
define("_MI_SYSTEM_BNAME7","Site Info");
define('_MI_SYSTEM_BNAME8', "Who's Online");
define('_MI_SYSTEM_BNAME9', "Top Posters");
define('_MI_SYSTEM_BNAME10', "New Members");
define('_MI_SYSTEM_BNAME11', "Recent Comments");
// RMV-NOTIFY
define('_MI_SYSTEM_BNAME12', "Notification Options");
define('_MI_SYSTEM_BNAME13', "Themes");
define('_MI_SYSTEM_BNAME14', "Language Selection");

/**
 * @todo Remove this blocks on future versions. When 1.1.2 isn't supported anymore.
 */
define('_MI_SYSTEM_BNAME15', "Content");
define('_MI_SYSTEM_BNAME16', "Content Menu");
define('_MI_SYSTEM_BNAME17', "Related Content");
/**/

define('_MI_SYSTEM_BNAME18', "Share this page!");

// Names of admin menu items
define("_MI_SYSTEM_ADMENU1","Banners");
define("_MI_SYSTEM_ADMENU2","Blocks");
define("_MI_SYSTEM_ADMENU3","Groups");
define("_MI_SYSTEM_ADMENU5","Modules");
define("_MI_SYSTEM_ADMENU6","Preferences");
define("_MI_SYSTEM_ADMENU7","Smilies");
define("_MI_SYSTEM_ADMENU9","User Ranks");
define("_MI_SYSTEM_ADMENU10","Edit User");
define("_MI_SYSTEM_ADMENU11","Mail Users");
define("_MI_SYSTEM_ADMENU12", "Find Users");
define("_MI_SYSTEM_ADMENU13", "Images Manager");
define("_MI_SYSTEM_ADMENU14", "Avatars");
define("_MI_SYSTEM_ADMENU15", "Templates");
define("_MI_SYSTEM_ADMENU16", "Comments");
// Version Added
define("_MI_SYSTEM_ADMENU17", "Version");
define("_MI_SYSTEM_ADMENU19", "Block Positions");
define("_MI_SYSTEM_ADMENU20", "Symlink Manager");
define("_MI_SYSTEM_ADMENU21", "Custom Tags");

######################## Added in 1.2 ###################################
define("_MI_SYSTEM_ADMENU22", "Adsense");
define("_MI_SYSTEM_ADMENU23", "Ratings");
define("_MI_SYSTEM_ADMENU24", "MimeTypes");

define('_MI_SYSTEM_BNAME101', "System Warnings");
define('_MI_SYSTEM_BNAME102', "Control Panel");
define('_MI_SYSTEM_BNAME103', "Installed Modules");
define('_MI_SYSTEM_BLOCK_BOOKMARKS','My Bookmarks');
define('_MI_SYSTEM_BLOCK_BOOKMARKS_DESC','Things I have bookmarked');

define("_MI_SYSTEM_ADMENU25", "Autotasks");
define('_MI_SYSTEM_REMOVEUSERS','Inactivating users');

// Added in 1.3
define('_MI_SYSTEM_BLOCK_CP_NEW', 'New Control Panel');