<?php
// $Id: admin.php 11023 2011-02-15 01:30:36Z skenow $
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

// added in 1.2
if (!defined('_MD_AM_AUTOTASKS')) {define('_MD_AM_AUTOTASKS', 'Auto Tasks');}
define('_MD_AM_ADSENSES', 'Adsenses');
define('_MD_AM_RATINGS', 'Ratings');
define('_MD_AM_MIMETYPES', 'Mime Types');

// added in 1.3
define("_MD_AM_GROUPS_ADVERTISING", "Advertising");
define("_MD_AM_GROUPS_CONTENT", "Content");
define("_MD_AM_GROUPS_LAYOUT", "Layout");
define("_MD_AM_GROUPS_MEDIA", "Media");
define("_MD_AM_GROUPS_SITECONFIGURATION", "Site Configuration");
define("_MD_AM_GROUPS_SYSTEMTOOLS", "System Tools");
define("_MD_AM_GROUPS_USERSANDGROUPS", "Users and Groups");
define('_MD_AM_ADSENSES_DSC', 'Adsenses are tags that you can define and use anywhere on your website.');
define('_MD_AM_AUTOTASKS_DSC', 'Auto Tasks allow you to create a schedule of actions that the system will perform automatically.');
define('_MD_AM_AVATARS_DSC', 'Manage the avatars available to the users of your website.');
define('_MD_AM_BANS_DSC', 'Manage ad campaigns and advertiser accounts.');
define('_MD_AM_BKPOSAD_DSC', 'Manage and create blocks positions that are used within the themes on your website.');
define('_MD_AM_BKAD_DSC', 'Manage and create blocks used throughout your website.');
define('_MD_AM_COMMENTS_DSC', 'Manage the comments made by users on your website.');
define('_MD_AM_CUSTOMTAGS_DSC', 'Custom Tags are tags that you can define and use anywhere on your website.');
define('_MD_AM_USER_DSC', 'Create, Modify or Delete registered users.');
define('_MD_AM_FINDUSER_DSC', 'Search through registered users with filters.');
define('_MD_AM_ADGS_DSC', 'Manage permissions, members, visibility and access rights of groups of users.');
define('_MD_AM_IMAGES_DSC', 'Create groups of images and manage the permissions for each group. Crop and resize uploaded photos.');
define('_MD_AM_MLUS_DSC', 'Send mail to users of whole groups - or filter recipients based on matching criteria.');
define('_MD_AM_MIMETYPES_DSC', 'Manage the allowed extensions for files uploaded to your website.');
define('_MD_AM_MDAD_DSC', 'Manage modules menu weight, status, name or update modules as needed.');
define('_MD_AM_RATINGS_DSC', 'With using this tool, you can add a new rating method to your modules, and control the results through this section!');
define('_MD_AM_SMLS_DSC', 'Manage the available smilies and define the code associatted with each.');
define('_MD_AM_PAGES_DSC', 'Symlink allows you to create a unique link based on any page of your website, which can be used for blocks specific to a page URL, or to link directly within the content of a module.');
define('_MD_AM_TPLSETS_DSC', 'Templates are sets of html/css files that render the screen layout of modules.');
define('_MD_AM_RANK_DSC', 'User ranks are picture, used to make difference between users in different levels of your website!');
define('_MD_AM_VRSN_DSC', 'Use this tool to check your system for updates.');
define('_MD_AM_PREF_DSC',"ImpressCMS Site Preferences");
