<?php
// $Id: modulesadmin.php 9802 2010-01-30 18:11:08Z skenow $
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
define("_MD_AM_FAILUPD","Unable to update %s.");
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
define('_MD_AM_FAILINSTEMP','ERROR: Could not insert template <b>%s</b> to the database.');
define('_MD_AM_FAILUPDTEMP','ERROR: Could not update template <b>%s</b>.');
define('_MD_AM_INSTEMP','Template <b>%s</b> added to the database. (ID: <b>%s</b>)');
define('_MD_AM_FAILCOMPTEMP','ERROR: Failed compiling template <b>%s</b>.');
define('_MD_AM_COMPTEMP','Template <b>%s</b> compiled.');
define('_MD_AM_FAILINSTEMPFILE','ERROR: Could not insert template <b>%s</b> to the database.');
define('_MD_AM_INSTEMPFILE','Template <b>%s</b> added to the database. (ID: <b>%s</b>)');
define('_MD_AM_FAILCOMPTEMPFILE','ERROR: Failed compiling template <b>%s</b>.');
define('_MD_AM_COMPTEMPFILE','Template <b>%s</b> compiled.');
define('_MD_AM_RECOMPTEMPFILE','Template <b>%s</b> recompiled.');
define('_MD_AM_NOTRECOMPTEMPFILE','ERROR: Could not recompile template <b>%s</b>.');
define('_MD_AM_TEMPINS','Template <b>%s</b> inserted to the database.');
define('_MD_AM_MOD_DATA_UPDATED',' Module data updated.');
define('_MD_AM_MOD_UP_TEM','Updating templates...');
define('_MD_AM_MOD_REBUILD_BLOCKS','Rebuilding blocks...');
define('_MD_AM_FUNCT_EXEC','Function <b>%s</b> is successfully executed.');
define('_MD_AM_FAIL_EXEC','Failed to execute <b>%s</b>.');
define('_MD_AM_INSTALLED', 'Installed Modules');
define('_MD_AM_NONINSTALL', 'UnInstalled Modules');
define('_MD_AM_NOTDELTEMPFILE', 'ERROR: Could not delete old template <b>%s</b>. Aborting update of this file.');
define('_MD_AM_COULDNOTUPDATE', 'ERROR: Could not update <b>%s</b>.');
define('_MD_AM_BLOCKUPDATED', 'Block <b>%s</b> updated. Block ID: <b>%s</b>.');
define('_MD_AM_BLOCKCREATED', 'Block <b>%s</b> created. Block ID: <b>%s</b>.');
define('_MD_AM_IMAGESFOLDER_UPDATE_TITLE', 'Image Manager folder needs to be writable');
define('_MD_AM_IMAGESFOLDER_UPDATE_TEXT', 'The new version of the Image Manager changed the storage location of your images. This update will try to move your images to the right place, but this requires that the storage folder has write permission. Please set the correct permission in the folder <strong>and refresh this page</strong> before clicking on the Update button below.<br /><b>Image Manager folder</b>: %s');
define('_MD_AM_PLUGINSFOLDER_UPDATE_TITLE', 'Plugins/Preloads folder needs to be writable');
define('_MD_AM_PLUGINSFOLDER_UPDATE_TEXT', 'The new version of ImpressCMS changed the storage location of the preloads. This update will try to move your preloads to the right place, but this requires that the storage folder for plugins and preloads has write permission. Please set the correct permission in the folder <strong>and refresh this page</strong> before clicking on the Update button below.<br /><b>Plugins folder</b>: %s<br /><b>Preloads folder</b>: %s');
?>