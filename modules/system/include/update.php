<?php
/**
 * DataBase Update Functions
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.0
 * @author		malanciault <marcan@impresscms.org)
 * @version		$Id: update.php 22551 2011-09-04 02:45:17Z skenow $
 */

icms_loadLanguageFile('core', 'databaseupdater');

/**
 * Automatic update of the system module
 *
 * @param object $module reference to the module object
 * @param int $oldversion The old version of the database
 * @param int $dbVersion The database version
 * @return mixed
 */
function xoops_module_update_system(&$module, $oldversion = NULL, $dbVersion = NULL) {

	global $icmsConfig, $xoTheme;

	$from_112 = $abortUpdate = FALSE;

	$oldversion = $module->getVar('version');
	if ($oldversion < 120) {
		$result = icms::$xoopsDB->query("SELECT t1.tpl_id FROM " 
				. icms::$xoopsDB->prefix('tplfile') . " t1, " 
				. icms::$xoopsDB->prefix('tplfile') . " t2 WHERE t1.tpl_module = t2.tpl_module AND t1.tpl_tplset=t2.tpl_tplset AND t1.tpl_file = t2.tpl_file AND t1.tpl_id > t2.tpl_id");

		$tplids = array();
		while (list($tplid) = icms::$xoopsDB->fetchRow($result)) {
			$tplids[] = $tplid;
		}

		if (count($tplids) > 0) {
			$tplfile_handler = icms::handler('icms_view_template_file');
			$duplicate_files = $tplfile_handler->getObjects(new icms_db_criteria_Item('tpl_id', "(" . implode(',', $tplids) . ")", "IN"));

			if (count($duplicate_files) > 0) {
				foreach (array_keys($duplicate_files) as $i) {
					$tplfile_handler->delete($duplicate_files[$i]);
				}
			}
		}
	}

	$icmsDatabaseUpdater = icms_db_legacy_Factory::getDatabaseUpdater();
	//$dbVersion  = $module->getDBVersion();
	//$oldversion  = $module->getVar('version');

	ob_start();

	$dbVersion = $module->getDBVersion();
	echo sprintf(_DATABASEUPDATER_CURRENTVER, icms_conv_nr2local($dbVersion)) . '<br />';
	echo "<code>" . sprintf(_DATABASEUPDATER_UPDATE_TO, icms_conv_nr2local(ICMS_SYSTEM_DBVERSION)) . "<br />";

	/*
	 * DEVELOPER, PLEASE NOTE !!!
	 *
	 * Everytime we add a new upgrade block here, the dbversion of the System Module will get
	 * incremented. It is very important to modify the ICMS_SYSTEM_DBVERSION accordingly
	 * in htdocs/include/version.php
	 *
	 * When we start a new major release, move all the previous version's upgrade scripts to
	 * a separate file, to minimize file size and memory usage
	 */

	$CleanWritingFolders = FALSE;

	if ($dbVersion < 40) include 'update-112-to-122.php';

/*  Begin upgrade to version 1.3 */
	if (!$abortUpdate) $newDbVersion = 41;

	if ($dbVersion < $newDbVersion) {
	/* Add new tables and data for the help suggestions and quick search */
		$table = new icms_db_legacy_updater_Table('autosearch_cat');
		if (!$table->exists()) {
			$table->setStructure(
				"`cid` int(11) NOT NULL auto_increment,
				 `cat_name` varchar(255) NOT NULL,
				 `cat_url` text NOT NULL,
				 PRIMARY KEY (`cid`)"
			);
			if (!$table->createTable()) {
				$abortUpdate = TRUE;
				$newDbVersion = 40;
			}
			if (!$abortUpdate) {
				icms_loadLanguageFile('system', 'admin');
				$search_cats = array(
					"NULL, '" . _MD_AM_ADSENSES . "', '/modules/system/admin.php?fct=adsense'",
					"NULL, '" . _MD_AM_AUTOTASKS . "', '/modules/system/admin.php?fct=autotasks'",
					"NULL, '" . _MD_AM_AVATARS . "', '/modules/system/admin.php?fct=avatars'",
					"NULL, '" . _MD_AM_BANS . "', '/modules/system/admin.php?fct=banners'",
					"NULL, '" . _MD_AM_BKPOSAD . "', '/modules/system/admin.php?fct=blockspadmin'",
					"NULL, '" . _MD_AM_BKAD . "', '/modules/system/admin.php?fct=blocksadmin'",
					"NULL, '" . _MD_AM_COMMENTS . "', '/modules/system/admin.php?fct=comments'",
					"NULL, '" . _MD_AM_CUSTOMTAGS . "', '/modules/system/admin.php?fct=customtag'",
					"NULL, '" . _MD_AM_USER . "', '/modules/system/admin.php?fct=users'",
					"NULL, '" . _MD_AM_FINDUSER . "', '/modules/system/admin.php?fct=finduser'",
					"NULL, '" . _MD_AM_ADGS . "', '/modules/system/admin.php?fct=groups'",
					"NULL, '" . _MD_AM_IMAGES . "', '/modules/system/admin.php?fct=images'",
					"NULL, '" . _MD_AM_MLUS . "', '/modules/system/admin.php?fct=mailusers'",
					"NULL, '" . _MD_AM_MIMETYPES . "', '/modules/system/admin.php?fct=mimetype'",
					"NULL, '" . _MD_AM_MDAD . "', '/modules/system/admin.php?fct=modulesadmin'",
					"NULL, '" . _MD_AM_PREF . "', '/modules/system/admin.php?fct=preferences'",
					"NULL, '" . _MD_AM_RATINGS . "', '/modules/system/admin.php?fct=rating'",
					"NULL, '" . _MD_AM_SMLS . "', '/modules/system/admin.php?fct=smilies'",
					"NULL, '" . _MD_AM_PAGES . "', '/modules/system/admin.php?fct=pages'",
					"NULL, '" . _MD_AM_TPLSETS . "', '/modules/system/admin.php?fct=tplsets'",
					"NULL, '" . _MD_AM_RANK . "', '/modules/system/admin.php?fct=userrank'",
					"NULL, '" . _MD_AM_VERSION . "', '/modules/system/admin.php?fct=version'");
				foreach ($search_cats as $cat) {
					$table->setData($cat);
				}
				$table->addData();
			}
			unset($table);
		}

		$table = new icms_db_legacy_updater_Table('autosearch_list');
		if (!$table->exists() && !$abortUpdate) {
			$table->setStructure(
				"`id` int(11) NOT NULL auto_increment,
				 `cat_id` int(11) NOT NULL,
				 `name` varchar(255) NOT NULL,
				 `img` varchar(255) NOT NULL,
				 `desc` text NOT NULL,
				 `url` text NOT NULL,
				 PRIMARY KEY (`id`)"
			);
			if (!$table->createTable()) {
				$abortUpdate = TRUE;
				$newDbVersion = 40;
			}
			if (!$abortUpdate) {
				icms_loadLanguageFile('system', 'admin');
				icms_loadLanguageFile('system', 'preferences', TRUE);
				$search_items = array(
					"NULL, 1, '" . _MD_AM_ADSENSES . "', '/modules/system/admin/adsense/images/adsense_small.png', '" . _MD_AM_ADSENSES_DSC . "', '/modules/system/admin.php?fct=adsense'",
					"NULL, 2, '" . _MD_AM_AUTOTASKS . "', '/modules/system/admin/autotasks/images/autotasks_small.png', '" . _MD_AM_AUTOTASKS_DSC . "', '/modules/system/admin.php?fct=autotasks'",
					"NULL, 3, '" . _MD_AM_AVATARS . "', '/modules/system/admin/avatars/images/avatars_small.png', '" . _MD_AM_AVATARS_DSC . "', '/modules/system/admin.php?fct=avatars'",
					"NULL, 4, '" . _MD_AM_BANS . "', '/modules/system/admin/banners/images/banners_small.png', '" . _MD_AM_BANS_DSC . "', '/modules/system/admin.php?fct=banners'",
					"NULL, 5, '" . _MD_AM_BKPOSAD . "', '/modules/system/admin/blockspadmin/images/blockspadmin_small.png', '" . _MD_AM_BKPOSAD_DSC . "', '/modules/system/admin.php?fct=blockspadmin'",
					"NULL, 6, '" . _MD_AM_BKAD . "', '/modules/system/admin/blocksadmin/images/blocksadmin_small.png', '" . _MD_AM_BKAD_DSC . "', '/modules/system/admin.php?fct=blocksadmin'",
					"NULL, 7, '" . _MD_AM_COMMENTS . "', '/modules/system/admin/comments/images/comments_small.png', '" . _MD_AM_COMMENTS_DSC . "', '/modules/system/admin.php?fct=comments'",
					"NULL, 8, '" . _MD_AM_CUSTOMTAGS . "', '/modules/system/admin/customtag/images/customtag_small.png', '" . _MD_AM_CUSTOMTAGS_DSC . "', '/modules/system/admin.php?fct=customtag'",
					"NULL, 9, '" . _MD_AM_USER . "', '/modules/system/admin/users/images/users_small.png', '" . _MD_AM_USER_DSC . "', '/modules/system/admin.php?fct=users'",
					"NULL, 10, '" . _MD_AM_FINDUSER . "', '/modules/system/admin/findusers/images/findusers_small.png', '" . _MD_AM_FINDUSER_DSC . "', '/modules/system/admin.php?fct=findusers'",
					"NULL, 11, '" . _MD_AM_ADGS . "', '/modules/system/admin/groups/images/groups_small.png', '" . _MD_AM_ADGS_DSC . "', '/modules/system/admin.php?fct=groups'",
					"NULL, 12, '" . _MD_AM_IMAGES . "', '/modules/system/admin/images/images/images_small.png', '" . _MD_AM_IMAGES_DSC . "', '/modules/system/admin.php?fct=images'",
					"NULL, 13, '" . _MD_AM_MLUS . "', '/modules/system/admin/mailusers/images/mailusers_small.png', '" . _MD_AM_MLUS_DSC . "', '/modules/system/admin.php?fct=mailusers'",
					"NULL, 14, '" . _MD_AM_MIMETYPES . "', '/modules/system/admin/mimetype/images/mimetype_small.png', '" . _MD_AM_MIMETYPES_DSC . "', '/modules/system/admin.php?fct=mimetype'",
					"NULL, 15, '" . _MD_AM_MDAD . "', '/modules/system/admin/modulesadmin/images/modulesadmin_small.png', '" . _MD_AM_MDAD_DSC . "', '/modules/system/admin.php?fct=modulesadmin'",
					"NULL, 16, '" . _MD_AM_PREF . " - " . _MD_AM_AUTHENTICATION . "', '/modules/system/admin/preferences/images/preferences_small.png', '" . _MD_AM_AUTHENTICATION_DSC . "', '/modules/system/admin.php?fct=preferences&op=show&confcat_id=7'",
					"NULL, 16, '" . _MD_AM_PREF . " - " . _MD_AM_AUTOTASKS . "', '/modules/system/admin/preferences/images/preferences_small.png', '" . _MD_AM_AUTOTASKS_PREF_DSC . "', '/modules/system/admin.php?fct=preferences&op=show&confcat_id=13'",
					"NULL, 16, '" . _MD_AM_PREF . " - " . _MD_AM_CAPTCHA . "', '/modules/system/admin/preferences/images/preferences_small.png', '" . _MD_AM_CAPTCHA_DSC . "', '/modules/system/admin.php?fct=preferences&op=show&confcat_id=11'",
					"NULL, 16, '" . _MD_AM_PREF . " - " . _MD_AM_GENERAL . "', '/modules/system/admin/preferences/images/preferences_small.png', '" . _MD_AM_GENERAL_DSC . "', '/modules/system/admin.php?fct=preferences&op=show&confcat_id=1'",
					"NULL, 16, '" . _MD_AM_PREF . " - " . _MD_AM_PURIFIER . "', '/modules/system/admin/preferences/images/preferences_small.png', '" . _MD_AM_PURIFIER_DSC . "', '/modules/system/admin.php?fct=preferences&op=show&confcat_id=14'",
					"NULL, 16, '" . _MD_AM_PREF . " - " . _MD_AM_MAILER . "', '/modules/system/admin/preferences/images/preferences_small.png', '" . _MD_AM_MAILER_DSC . "', '/modules/system/admin.php?fct=preferences&op=show&confcat_id=6'",
					"NULL, 16, '" . _MD_AM_PREF . " - " . _MD_AM_METAFOOTER . "', '/modules/system/admin/preferences/images/preferences_small.png', '" . _MD_AM_METAFOOTER_DSC . "', '/modules/system/admin/preferences/images/preferences_small.png'",
					"NULL, 16, '" . _MD_AM_PREF . " - " . _MD_AM_MULTILANGUAGE . "', '/modules/system/admin/preferences/images/preferences_small.png', '" . _MD_AM_MULTILANGUAGE_DSC . "', '/modules/system/admin.php?fct=preferences&op=show&confcat_id=8'",
					"NULL, 16, '" . _MD_AM_PREF . " - " . _MD_AM_PERSON . "', '/modules/system/admin/preferences/images/preferences_small.png', '" . _MD_AM_PERSON_DSC . "', '/modules/system/admin.php?fct=preferences&op=show&confcat_id=10'",
					"NULL, 16, '" . _MD_AM_PREF . " - " . _MD_AM_PLUGINS . "', '/modules/system/admin/preferences/images/preferences_small.png', '" . _MD_AM_PLUGINS_DSC . "', '/modules/system/admin.php?fct=preferences&op=show&confcat_id=12'",
					"NULL, 16, '" . _MD_AM_PREF . " - " . _MD_AM_SEARCH . "', '/modules/system/admin/preferences/images/preferences_small.png', '" . _MD_AM_SEARCH_DSC . "', '/modules/system/admin.php?fct=preferences&op=show&confcat_id=5'",
					"NULL, 16, '" . _MD_AM_PREF . " - " . _MD_AM_USERSETTINGS . "', '/modules/system/admin/preferences/images/preferences_small.png', '" . _MD_AM_USERSETTINGS_DSC . "', '/modules/system/admin.php?fct=preferences&op=show&confcat_id=2'",
					"NULL, 16, '" . _MD_AM_PREF . " - " . _MD_AM_CENSOR . "', '/modules/system/admin/preferences/images/preferences_small.png', '" . _MD_AM_CENSOR_DSC . "', '/modules/system/admin.php?fct=preferences&op=show&confcat_id=4'",
					"NULL, 17, '" . _MD_AM_RATINGS . "', '/modules/system/admin/rating/images/rating_small.png', '" . _MD_AM_RATINGS_DSC . "', '/modules/system/admin.php?fct=rating'",
					"NULL, 18, '" . _MD_AM_SMLS . "', '/modules/system/admin/smilies/images/smilies_small.png', '" . _MD_AM_SMLS_DSC . "', '/modules/system/admin.php?fct=smilies'",
					"NULL, 19, '" . _MD_AM_PAGES . "', '/modules/system/admin/pages/images/pages_small.png', '" . _MD_AM_PAGES_DSC . "', '/modules/system/admin.php?fct=pages'",
					"NULL, 20, '" . _MD_AM_TPLSETS . "', '/modules/system/admin/tplsets/images/tplsets_small.png', '" . _MD_AM_TPLSETS_DSC . "', '/modules/system/admin.php?fct=tplsets'",
					"NULL, 21, '" . _MD_AM_RANK . "', '/modules/system/admin/userrank/images/userrank_small.png', '" . _MD_AM_RANK_DSC . "', '/modules/system/admin.php?fct=userrank'",
					"NULL, 22, '" . _MD_AM_VRSN . "', '/modules/system/admin/version/images/version_small.png', '" . _MD_AM_VRSN_DSC . "', '/modules/system/admin.php?fct=version'"
				);
				foreach ($search_items as $item) {
					$table->setData($item);
				}
				$table->addData();
			}
			unset($table);
		}

		/* Optimize old tables and fix data structures */
		$table = new icms_db_legacy_updater_Table('config');
		$icmsDatabaseUpdater->runQuery("ALTER TABLE `" . $table->name() . "` DROP INDEX conf_mod_cat_id, ADD INDEX mod_cat_order(conf_modid, conf_catid, conf_order)", 'Successfully altered the indexes on table config', '');
		unset($table);

		$table = new icms_db_legacy_updater_Table('group_permission');
		$table->addAlteredField('gperm_modid', "SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0'", 'gperm_modid');
		$table->alterTable();
		$icmsDatabaseUpdater->runQuery("ALTER TABLE `" . $table->name() . "` DROP INDEX itemid, DROP INDEX groupid, DROP INDEX gperm_modid", 'Successfully dropped the indexes on table group_permission', '');
		$icmsDatabaseUpdater->runQuery("ALTER TABLE `" . $table->name() . "` ADD INDEX name_mod_group (gperm_name(10), gperm_modid, gperm_groupid)", 'Successfully added the indexes on table group_permission', '');
		unset($table);

		$table = new icms_db_legacy_updater_Table('modules');
		$icmsDatabaseUpdater->runQuery("ALTER TABLE `" . $table->name() . "` DROP INDEX hasmain, DROP INDEX hasadmin, DROP INDEX hassearch, DROP INDEX hasnotification, DROP INDEX name, DROP INDEX dirname", 'Successfully dropped the indexes on table modules', '');
		$icmsDatabaseUpdater->runQuery("ALTER TABLE `" . $table->name() . "` ADD INDEX dirname (dirname(5)), ADD INDEX active_main_weight (isactive, hasmain, weight)", 'Successfully added the indexes on table modules', '');
		unset($table);

		$table = new icms_db_legacy_updater_Table('users');
		$icmsDatabaseUpdater->runQuery("ALTER TABLE `" . $table->name() . "` DROP INDEX email, DROP INDEX uiduname, DROP INDEX unamepass", 'Successfully dropped the indexes on table users', '');
		$icmsDatabaseUpdater->runQuery("ALTER TABLE `" . $table->name() . "` DROP INDEX login_name, ADD UNIQUE INDEX login_name (login_name)", 'Successfully added the indexes on table users', '');
		unset($table);

		$table = new icms_db_legacy_updater_Table('priv_msgs');
		$icmsDatabaseUpdater->runQuery("ALTER TABLE `" . $table->name() . "` DROP INDEX to_userid", 'Successfully dropped the indexes on table priv_msgs', '');
		unset($table);

		$table = new icms_db_legacy_updater_Table('ranks');
		$icmsDatabaseUpdater->runQuery("ALTER TABLE `" . $table->name() . "` DROP INDEX rank_min", 'Successfully dropped the indexes on table ranks', '');
		unset($table);

		/* Corrects an error from db version 4 */
		$table = new icms_db_legacy_updater_Table('users');
		if ($table->fieldExists('pass')) {
			$table->addAlteredField('pass', "varchar(255) NOT NULL default ''", 'pass');
			$table->alterTable();
		}
		unset($table);

		/* change IP address to varchar(64) in session to accomodate IPv6 addresses */
		$table = new icms_db_legacy_updater_Table('session');
		if ($table->fieldExists('sess_ip')) {
			$table->addAlteredField('sess_ip', "varchar(64) NOT NULL default ''", 'sess_ip');
			$table->alterTable();
		}
		unset($table);

		/* add modname and ipf to modules table */
		$table = new icms_db_legacy_updater_Table("modules");
		$alter = FALSE;
		if (!$table->fieldExists("modname")) {
			$table->addNewField("modname", "varchar(25) NOT NULL default ''");
			$alter = TRUE;
		}
		if (!$table->fieldExists("ipf")) {
			$table->addNewField("ipf", "tinyint(1) unsigned NOT NULL default '0'");
			$alter = TRUE;
		}
		if ($alter) $table->addNewFields();
		unset($table, $alter);

		$module_handler = icms::handler('icms_module');
		$modules = $module_handler->getObjects();
		foreach ($modules as $module) {
			if ($module->getInfo("modname") !== FALSE) {
				$module->setVar("modname", $module->getInfo("modname"));
			}
			if ($module->getInfo("object_items") !== FALSE) {
				$module->setVar("ipf", 1);
			}
			$module_handler->insert($module);
		}
		unset($module_handler, $modules);

		/* add slot for adsense and rename fields */
		$table = new icms_db_legacy_updater_Table('system_adsense');
		if (!$table->fieldExists("slot")) {
			$table->addNewField("slot", "varchar(12) NOT NULL default ''");
			$table->addNewFields();
		}
		if ($table->fieldExists("border_color")) $icmsDatabaseUpdater->runQuery("ALTER TABLE `" . $table->name() . "` CHANGE border_color color_border varchar(6) NOT NULL default ''");
		if ($table->fieldExists("background_color")) $icmsDatabaseUpdater->runQuery("ALTER TABLE `" . $table->name() . "` CHANGE background_color color_background varchar(6) NOT NULL default ''");
		if ($table->fieldExists("link_color")) $icmsDatabaseUpdater->runQuery("ALTER TABLE `" . $table->name() . "` CHANGE link_color color_link varchar(6) NOT NULL default ''");
		if ($table->fieldExists("url_color")) $icmsDatabaseUpdater->runQuery("ALTER TABLE `" . $table->name() . "` CHANGE url_color color_url varchar(6) NOT NULL default ''");
		if ($table->fieldExists("text_color")) $icmsDatabaseUpdater->runQuery("ALTER TABLE `" . $table->name() . "` CHANGE text_color color_text varchar(6) NOT NULL default ''");
		unset($table);

		/* rename content field for customtag */
		$table = new icms_db_legacy_updater_Table("system_customtag");
		if ($table->fieldExists("content")) $icmsDatabaseUpdater->runQuery("ALTER TABLE `" . $table->name() . "` CHANGE content customtag_content text NOT NULL");
		unset($table);

		/* rename gperm_name from view to view_customtag for system module */
		$table = new icms_db_legacy_updater_Table("group_permission");
		$icmsDatabaseUpdater->runQuery("UPDATE `" . $table->name() . "` SET gperm_name = 'view_customtag' WHERE gperm_name = 'view' AND gperm_modid = 1", "", "");
		unset($table);

		/* reset default source editor if jsvi is used */
		$configs = icms::$config->getConfigs(icms_buildCriteria(array("conf_name" => "sourceeditor_default")));
		if (count($configs) == 1 && $configs[0]->getVar("conf_value") == "jsvi") {
			$configs[0]->setVar("conf_value", "editarea");
			icms::$config->insertConfig($configs[0]);
		}
		unset($configs);
		
		/* New HTML Purifier options -
		 * purifier_HTML_FlashAllowFullScreen, after purifier_HTML_AttrNameUseCDATA
		 * purifier_Output_FlashCompat, after purifier_HTML_FlashAllowFullScreen
		 * purifier_Filter_AllowCustom, after purifier_Filter_YouTube
		 * purifier_Core_NormalizeNewlines, after purifier_Core_RemoveInvalidImg
		 */
		
		$table = new icms_db_legacy_updater_Table("config");

		// retrieve the value of the position before the config to be inserted. 
		$configs = icms::$config->getConfigs(icms_buildCriteria(array("conf_name" => "purifier_HTML_AttrNameUseCDATA")));
		$p = $configs[0]->getVar('conf_order') + 1;
		//move all the other options down
		$icmsDatabaseUpdater->runQuery("UPDATE `" . $table->name() . "` SET conf_order = conf_order + 2 WHERE conf_order >= " . $p . " AND conf_catid = " . ICMS_CONF_PURIFIER, "", "");
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_HTML_FlashAllowFullScreen', '_MD_AM_PURIFIER_HTML_FLASHFULLSCRN', '0', '_MD_AM_PURIFIER_HTML_FLASHFULLSCRNDSC', 'yesno', 'int', $p);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Output_FlashCompat', '_MD_AM_PURIFIER_OUTPUT_FLASHCOMPAT', '0', '_MD_AM_PURIFIER_OUTPUT_FLASHCOMPATDSC', 'yesno', 'int', $p++);
				
		// retrieve the value of the position before the config to be inserted. 
		$configs = icms::$config->getConfigs(icms_buildCriteria(array("conf_name" => "purifier_Filter_YouTube")));
		$p = $configs[0]->getVar('conf_order') + 1;
		//move all the other options down
		$icmsDatabaseUpdater->runQuery("UPDATE `" . $table->name() . "` SET conf_order = conf_order + 1 WHERE conf_order >= " . $p . " AND conf_catid = " . ICMS_CONF_PURIFIER, "", "");
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Filter_AllowCustom', '_MD_AM_PURIFIER_FILTER_ALLOWCUSTOM', '0', '_MD_AM_PURIFIER_FILTER_ALLOWCUSTOMDSC', 'yesno', 'int', $p);

		// retrieve the value of the position before the config to be inserted. 
		$configs = icms::$config->getConfigs(icms_buildCriteria(array("conf_name" => "purifier_Core_RemoveInvalidImg")));
		$p = $configs[0]->getVar('conf_order') + 1;
		//move all the other options down
		$icmsDatabaseUpdater->runQuery("UPDATE `" . $table->name() . "` SET conf_order = conf_order + 1 WHERE conf_order >= " . $p . " AND conf_catid = " . ICMS_CONF_PURIFIER, "", "");
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Core_NormalizeNewlines', '_MD_AM_PURIFIER_CORE_NORMALNEWLINES', '1', '_MD_AM_PURIFIER_CORE_NORMALNEWLINESDSC', 'yesno', 'int', $p);
		
		unset($table);
		
		/* Finish up this portion of the db update */
		if (!$abortUpdate) {
			$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
			echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
		}
	}
/*  1.3 beta|rc|final release  */

/*
 * This portion of the upgrade must remain as the last section of code to execute
 * Place all release upgrade steps above this point
 */
	echo "</code>";
    if ($abortUpdate) {
        icms_core_Message::error(sprintf(_DATABASEUPDATER_UPDATE_ERR, icms_conv_nr2local($newDbVersion)), _DATABASEUPDATER_UPDATE_DB, TRUE);
    }
	if ($from_112 && ! $abortUpdate) {
		/**
		 * @todo create a language constant for this text
		 */
		echo _DATABASEUPDATER_MSG_FROM_112;
		echo '<script>setTimeout("window.location.href=\'' . ICMS_MODULES_URL . '/system/admin.php?fct=modulesadmin&op=install&module=content&from_112=1\'",20000);</script>';
	}

	$feedback = ob_get_clean();
	if (method_exists($module, "setMessage")) {
		$module->messages = $module->setMessage($feedback);
	} else {
		echo $feedback;
	}

	$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
	return icms_core_Filesystem::cleanFolders(array('templates_c' => ICMS_COMPILE_PATH . "/", 'cache' => ICMS_CACHE_PATH . "/"), $CleanWritingFolders);
}
