<?php

/**
 * $Id: databaseupdater.php 20490 2010-12-05 19:58:20Z skenow $
 * Author: The SmartFactory <www.smartfactory.ca>
 * Licence: GNU
 */
defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

define("_DATABASEUPDATER_IMPORT", "Import");
define("_DATABASEUPDATER_CURRENTVER", "Current version: <span class='currentVer'>%s</span>");
define("_DATABASEUPDATER_DBVER", "Database Version %s");
define("_DATABASEUPDATER_MSG_ADD_DATA", "Data added in table %s");
define("_DATABASEUPDATER_MSG_ADD_DATA_ERR", "Error adding data in table %s");
define("_DATABASEUPDATER_MSG_CHGFIELD", "Changing field %s in table %s");
define("_DATABASEUPDATER_MSG_CHGFIELD_ERR", "Error changing field %s in table %s");
define("_DATABASEUPDATER_MSG_CREATE_TABLE", "Table %s created");
define("_DATABASEUPDATER_MSG_CREATE_TABLE_ERR", "Error creating table %s");
define("_DATABASEUPDATER_MSG_NEWFIELD", "Successfully added field %s");
define("_DATABASEUPDATER_MSG_NEWFIELD_ERR", "Error adding field %s");
define("_DATABASEUPDATER_NEEDUPDATE", "Your database is out-of-date. Please upgrade your database tables!<br /><b>Note : The ImpressCMS strongly recommends you to backup all your database tables before running this upgrade script.</b>");
define("_DATABASEUPDATER_NOUPDATE", "Your database is up-to-date. No updates are necessary.");
define("_DATABASEUPDATER_UPDATE_DB", "Updating Database");
define("_DATABASEUPDATER_UPDATE_ERR", "Errors updating to version %s");
define("_DATABASEUPDATER_UPDATE_NOW", "Update Now!");
define("_DATABASEUPDATER_UPDATE_OK", "Successfully updated to version %s");
define("_DATABASEUPDATER_UPDATE_TO", "Updating to version %s");
define("_DATABASEUPDATER_UPDATE_UPDATING_DATABASE", "Updating database...");

define("_DATABASEUPDATER_MSG_UPDATE_TABLE", "Records of table %s were successfully updated");
define("_DATABASEUPDATER_MSG_UPDATE_TABLE_ERR", "An error occured while updating records in table %s");
define("_DATABASEUPDATER_MSG_DELETE_TABLE", "Specified records of table %s were successfully deleted");
define("_DATABASEUPDATER_MSG_DELETE_TABLE_ERR", "An error occured while deleting specified records in table %s");
############# added since 1.2 #############
define("_DATABASEUPDATER_MSG_DB_VERSION_ERR", "Unable to update module dbversion");
define("_DATABASEUPDATER_LATESTVER", "Latest database version : <span class='currentVer'>%s</span>");
define("_DATABASEUPDATER_MSG_CONFIG_ERR", "Unable to insert config %s");
define("_DATABASEUPDATER_MSG_CONFIG_SCC", "Successfully inserted %s config");

/* added in 1.3 */
define( '_DATABASEUPDATER_MSG_FROM_112', "<code><h3>You have updated your site from ImpressCMS 1.1.x to ImpressCMS 1.2 so you <strong>must install the new Content module</strong> to update the core content manager. You will be redirected to the installation process in 20 seconds. If this does not happen click <a href='" . ICMS_URL . "/modules/system/admin.php?fct=modulesadmin&op=install&module=content&from_112=1'>here</a>.</h3></code>" );
define('_DATABASEUPDATER_MSG_DROPFIELD_ERR', 'An error occured while deleting specified fields %1$s from table %2$s');
define("_DATABASEUPDATER_MSG_DROPFIELD", 'Successfully dropped field %1$s from table %2$s');
