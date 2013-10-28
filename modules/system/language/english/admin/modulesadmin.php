<?php
// $Id: modulesadmin.php 21361 2011-03-29 00:57:50Z skenow $
//%%%%%%	File Name  modulesadmin.php 	%%%%%
define("_MD_AM_MODADMIN","Modules Administration");
define("_MD_AM_MODULE","Module");
define("_MD_AM_VERSION","Version");
define("_MD_AM_LASTUP","Last Update");
define("_MD_AM_DEACTIVATED","Deactivated");
define("_MD_AM_ACTION","Action");
define("_MD_AM_DEACTIVATE","Deactivate");
define("_MD_AM_ACTIVATE","Activate");
define("_MD_AM_UPDATE","Update");
define("_MD_AM_DUPEN","Duplicate entry in modules table!");
define("_MD_AM_DEACTED","The selected module has been deactivated. You can now safely uninstall the module.");
define("_MD_AM_ACTED","The selected module has been activated!");
define("_MD_AM_UPDTED","The selected module has been updated!");
define("_MD_AM_SYSNO","System module cannot be deactivated.");
define("_MD_AM_STRTNO","This module is set as your default start page. Please change the start module to whatever suits your preferences.");

// added in RC2
define("_MD_AM_PCMFM","Please confirm:");

// added in RC3
define("_MD_AM_ORDER","Order");
define("_MD_AM_ORDER0","(0 = hide)");
define("_MD_AM_ACTIVE","Active");
define("_MD_AM_INACTIVE","Inactive");
define("_MD_AM_NOTINSTALLED","Not Installed");
define("_MD_AM_NOCHANGE","No Change");
define("_MD_AM_INSTALL","Install");
define("_MD_AM_UNINSTALL","Uninstall");
define("_MD_AM_SUBMIT","Submit");
define("_MD_AM_CANCEL","Cancel");
define("_MD_AM_DBUPDATE","Database updated successfully!");
define("_MD_AM_BTOMADMIN","Back to Module Administration page");

// %s represents module name
define("_MD_AM_FAILINS","Unable to install %s.");
define("_MD_AM_FAILACT","Unable to activate %s.");
define("_MD_AM_FAILDEACT","Unable to deactivate %s.");

define("_MD_AM_FAILUNINS","Unable to uninstall %s.");
define("_MD_AM_FAILORDER","Unable to reorder %s.");
define("_MD_AM_FAILWRITE","Unable to write to main menu.");
define("_MD_AM_ALEXISTS","Module %s already exists.");
define("_MD_AM_ERRORSC", "Error(s):");
define("_MD_AM_OKINS","Module %s installed successfully.");
define("_MD_AM_OKACT","Module %s activated successfully.");
define("_MD_AM_OKDEACT","Module %s deactivated successfully.");
define("_MD_AM_OKUPD","Module %s updated successfully.");
define("_MD_AM_OKUNINS","Module %s uninstalled successfully.");
define("_MD_AM_OKORDER","Module %s changed successfully.");

define('_MD_AM_RUSUREINS', 'Press the button below to install this module');
define('_MD_AM_RUSUREUPD', 'Press the button below to update this module');
define('_MD_AM_RUSUREUNINS', 'Are you sure you would like to uninstall this module?');
define('_MD_AM_LISTUPBLKS', 'The following blocks will be updated.<br />Select the blocks of which contents (template and options) may be overwritten.<br />');
define('_MD_AM_NEWBLKS', 'New Blocks');
define('_MD_AM_DEPREBLKS', 'Deprecated Blocks');

define('_MD_AM_MODULESADMIN_SUPPORT', 'Module Support Site');
define('_MD_AM_MODULESADMIN_STATUS', 'Status');
define('_MD_AM_MODULESADMIN_MODULENAME', 'Module Name');
define('_MD_AM_MODULESADMIN_MODULETITLE', 'Module Title');
######################## Added in 1.2 ###################################
define('_MD_AM_MOD_DATA_UPDATED',' Module data updated.');
define('_MD_AM_MOD_REBUILD_BLOCKS','Rebuilding blocks...');
define('_MD_AM_INSTALLED', 'Installed Modules');
define('_MD_AM_NONINSTALL', 'UnInstalled Modules');
define('_MD_AM_IMAGESFOLDER_UPDATE_TITLE', 'Image Manager folder needs to be writable');
define('_MD_AM_IMAGESFOLDER_UPDATE_TEXT', 'The new version of the Image Manager changed the storage location of your images. This update will try to move your images to the right place, but this requires that the storage folder has write permission. Please set the correct permission in the folder <strong>and refresh this page</strong> before clicking on the Update button below.<br /><strong>Image Manager folder</strong>: %s');
define('_MD_AM_PLUGINSFOLDER_UPDATE_TITLE', 'Plugins/Preloads folder needs to be writable');
define('_MD_AM_PLUGINSFOLDER_UPDATE_TEXT', 'The new version of ImpressCMS changed the storage location of the preloads. This update will try to move your preloads to the right place, but this requires that the storage folder for plugins and preloads has write permission. Please set the correct permission in the folder <strong>and refresh this page</strong> before clicking on the Update button below.<br /><strong>Plugins folder</strong>: %s<br /><strong>Preloads folder</strong>: %s');

// Added and Changed in 1.3
define("_MD_AM_UPDATE_FAIL","Unable to update %s.");
define('_MD_AM_FUNCT_EXEC','Function %s is successfully executed.');
define('_MD_AM_FAIL_EXEC','Failed to execute %s.');
define('_MD_AM_INSTALLING','Installing ');
define('_MD_AM_SQL_NOT_FOUND', 'SQL file not found at %s');
define('_MD_AM_SQL_FOUND', "SQL file found at %s . <br  /> Creating tables...");
define('_MD_SQL_NOT_VALID', ' is not valid SQL!');
define('_MD_AM_TABLE_CREATED', 	'Table %s created.');
define('_MD_AM_DATA_INSERT_SUCCESS', 'Data inserted to table %s.');
define('_MD_AM_RESERVED_TABLE', '%s is a reserved table!');
define('_MD_AM_DATA_INSERT_FAIL', 'Could not insert %s to database.');
define('_MD_AM_CREATE_FAIL', 'ERROR: Could not create %s');

define('_MD_AM_MOD_DATA_INSERT_SUCCESS', 'Module data inserted successfully. Module ID: %s');

define('_MD_AM_BLOCK_UPDATED', 'Block %s updated. Block ID: %s.');
define('_MD_AM_BLOCK_CREATED', 'Block %s created. Block ID: %s.');

define('_MD_AM_BLOCKS_ADDING', 'Adding blocks...');
define('_MD_AM_BLOCKS_ADD_FAIL', 'ERROR: Could not add block %1$s to the database! Database error: %2$s');
define('_MD_AM_BLOCK_ADDED', 'Block %1$s added. Block ID: %2$s');
define('_MD_AM_BLOCKS_DELETE', 'Deleting block...');
define('_MD_AM_BLOCK_DELETE_FAIL', 'ERROR: Could not delete block %1$s. Block ID: %2$s');
define('_MD_AM_BLOCK_DELETED', 'Block %1$s deleted. Block ID: %2$s');
define('_MD_AM_BLOCK_TMPLT_DELETE_FAILED', 'ERROR: Could not delete block template %1$s  from the database. Template ID: %2$s');
define('_MD_AM_BLOCK_TMPLT_DELETED', 'Block template %1$s  deleted from the database. Template ID: %2$s');
define('_MD_AM_BLOCK_ACCESS_FAIL', 'ERROR: Could not add block access right. Block ID: %1$s  Group ID: %2$s');
define('_MD_AM_BLOCK_ACCESS_ADDED', 'Added block access right. Block ID: %1$s, Group ID: %2$s');

define('_MD_AM_CONFIG_ADDING', 'Adding module config data...');
define('_MD_AM_CONFIGOPTION_ADDED', 'Config option added. Name: %1$s Value: %2$s');
define('_MD_AM_CONFIG_ADDED', 'Config %s  added to the database.');
define('_MD_AM_CONFIG_ADD_FAIL', 'ERROR: Could not insert config %s to the database.');

define('_MD_AM_PERMS_ADDING', 'Setting group rights...');
define('_MD_AM_ADMIN_PERM_ADD_FAIL', 'ERROR: Could not add admin access right for Group ID %s');
define('_MD_AM_ADMIN_PERM_ADDED', 'Added admin access right for Group ID %s');
define('_MD_AM_USER_PERM_ADD_FAIL', 'ERROR: Could not add user access right for Group ID: %s');
define('_MD_AM_USER_PERM_ADDED', 'Added user access right for Group ID: %s');

define('_MD_AM_AUTOTASK_FAIL', 'ERROR: Could not insert autotask to db. Name: %s');
define('_MD_AM_AUTOTASK_ADDED', 'Added task to autotasks list. Task Name: %s');
define('_MD_AM_AUTOTASK_UPDATE', 'Updating autotasks...');
define('_MD_AM_AUTOTASKS_DELETE', 'Deleting Autotasks...');

define('_MD_AM_SYMLINKS_DELETE', 'Deleting links from Symlink Manager...');
define('_MD_AM_SYMLINK_DELETE_FAIL', 'ERROR: Could not delete link %1$s from the database. Link ID: %2$s');
define('_MD_AM_SYMLINK_DELETED', 'Link %1$s deleted from the database. Link ID: %2$s');

define('_MD_AM_DELETE_FAIL', 'ERROR: Could not delete %s');

define('_MD_AM_MOD_UP_TEM','Updating templates...');
define('_MD_AM_TEMPLATE_INSERT_FAIL','ERROR: Could not insert template %s to the database.');
define('_MD_AM_TEMPLATE_UPDATE_FAIL','ERROR: Could not update template %s.');
define('_MD_AM_TEMPLATE_INSERTED','Template %s added to the database. (ID: %s)');
define('_MD_AM_TEMPLATE_COMPILE_FAIL','ERROR: Failed compiling template %s.');
define('_MD_AM_TEMPLATE_COMPILED','Template %s compiled.');
define('_MD_AM_TEMPLATE_RECOMPILED','Template %s recompiled.');
define('_MD_AM_TEMPLATE_RECOMPILE_FAIL','ERROR: Could not recompile template %s.');

define('_MD_AM_TEMPLATES_ADDING', 'Adding templates...');
define('_MD_AM_TEMPLATES_DELETE', 'Deleting templates...');
define('_MD_AM_TEMPLATE_DELETE_FAIL', 'ERROR: Could not delete template %1$s from the database. Template ID: %2$s');
define('_MD_AM_TEMPLATE_DELETED', 'Template %1$s  deleted from the database. Template ID: %2$s');
define('_MD_AM_TEMPLATE_UPDATED', 'Template %s updated.');

define('_MD_AM_MOD_TABLES_DELETE', 'Deleting module tables...');
define('_MD_AM_MOD_TABLE_DELETE_FAIL', 'ERROR: Could not drop table %s');
define('_MD_AM_MOD_TABLE_DELETED', 'Table %s dropped.');
define('_MD_AM_MOD_TABLE_DELETE_NOTALLOWED', 'ERROR: Not allowed to drop table %s!');

define('_MD_AM_COMMENTS_DELETE', 'Deleting comments...');
define('_MD_AM_COMMENT_DELETE_FAIL', 'ERROR: Could not delete comments');
define('_MD_AM_COMMENT_DELETED', 'Comments deleted');

define('_MD_AM_NOTIFICATIONS_DELETE', 'Deleting notifications...');
define('_MD_AM_NOTIFICATION_DELETE_FAIL', 'ERROR: Could not delete notifications');
define('_MD_AM_NOTIFICATION_DELETED', 'Notifications deleted');

define('_MD_AM_GROUPPERM_DELETE', 'Deleting group permissions...');
define('_MD_AM_GROUPPERM_DELETE_FAIL', 'ERROR: Could not delete group permissions');
define('_MD_AM_GROUPPERM_DELETED', 'Group permissions deleted');

define('_MD_AM_CONFIGOPTIONS_DELETE', 'Deleting module config options...');
define('_MD_AM_CONFIGOPTION_DELETE_FAIL', 'ERROR: Could not delete config data from the database. Config ID: %s');
define('_MD_AM_CONFIGOPTION_DELETED', 'Config data deleted from the database. Config ID: %s');

