<?php
/**
* File containing onUpdate and onInstall functions for the module
*
* This file is included by the core in order to trigger onInstall or onUpdate functions when needed.
* Of course, onUpdate function will be triggered when the module is updated, and onInstall when
* the module is originally installed.
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: onupdate.inc.php 23947 2012-03-24 12:39:46Z qm-b $
*/
defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

// this needs to be the latest db version
define("BANNERS_DB_VERSION", 1);

/**
 * it is possible to define custom functions which will be call when the module is updating at the
 * correct time in update incrementation. Simpy define a function named <direname_db_upgrade_db_version>
 */
function deleteSmartyPlugin() {
	$file = 'function.banners.php';
	$plugin = ICMS_LIBRARIES_PATH . '/smarty/icms_plugins/function.banners.php';
	if(is_file($plugin)) {
		icms_core_Filesystem::chmod($plugin);
		icms_core_Filesystem::deleteFile($plugin);
		echo "<code><b>Smarty plugin successfully deleted.</b></code>";
	}
}

function copySmartyPlugin() {
	$dir = ICMS_ROOT_PATH . '/modules/banners/plugins/';
	$file = 'function.banners.php';
	$plugin_folder = ICMS_LIBRARIES_PATH . '/smarty/icms_plugins/';
	if(!is_file($plugin_folder . $file)) {
		icms_core_Filesystem::copyRecursive($dir . $file, $plugin_folder . $file);
	}
}
 
//function banners_db_upgrade_1() {}
function icms_module_update_banners($module) {
	// optimize tables
	/*$banners_banner_handler = icms_getModuleHandler('banner', basename(dirname(dirname(__FILE__))), 'banners');
	$banner_table = new icms_db_legacy_updater_Table($module->getVar("dirname") . "_banner");
	$banner_table->addAlteredField('type', 'tinyint(1) not null default ' . BANNERS_BANNER_TYPE_IMAGE);
	$banner_table->addAlteredField('contract', 'tinyint(1) not null default ' . BANNERS_BANNER_CONTRACT_TIME);
	$banner_table->addAlteredField('active', 'tinyint(1) not null default 0');
	$banner_table->alterTable();*/
    return TRUE;
}

/**
 * 
 * The procedures to run upon installation of the module
 * @todo	Handle blocks that may be assigned to symlinks for the legacy banner admin 
 * @todo	Remove legacy files and folders, if present : /banners.php, /modules/system/admin/banners/
 * @todo	Handle error conditions
 * @param unknown_type $module
 */
function icms_module_install_banners($module) {
	// copy smarty plugin, if file does not exist in smarty plugins
	copySmartyPlugin();
	/* Remove legacy banner tables, settings and symlinks 
		$table = new icms_db_legacy_updater_Table('banner');
		if ($table->exists()) $table->dropTable();
		unset($table);
		$table = new icms_db_legacy_updater_Table('bannerclient');
		if ($table->exists()) $table->dropTable();
		unset($table);
		$table = new icms_db_legacy_updater_Table('bannerfinish');
		if ($table->exists()) $table->dropTable();
		unset($table);
		$icmsDatabaseUpdater = icms_db_legacy_Factory::getDatabaseUpdater();
		$table = new icms_db_legacy_updater_Table('config');
		$icmsDatabaseUpdater->runQuery("DELETE FROM `" . $table->name() . "` WHERE conf_modid = 0 AND conf_name IN ('banners', 'my_ip')");
		unset($table);
		
		$table = new icms_db_legacy_updater_Table('config');
		$icmsDatabaseUpdater->runQuery("DELETE FROM `" . $table->name() . "` WHERE page_url = 'modules/system/admin.php?fct=banners*'");
		unset($table);*/
	
	return TRUE;
}

function icms_module_uninstall_banners($module) {
	deleteSmartyPlugin();
	return TRUE;
}