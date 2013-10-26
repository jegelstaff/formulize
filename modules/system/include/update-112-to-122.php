<?php
/**
 * DataBase Update Functions - 1.1.2 to 1.2.2 release
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.3
 * @author		malanciault <marcan@impresscms.org)
 * @version		$Id: update-112-to-122.php 20426 2010-11-20 22:28:44Z phoenyx $
 */

	if ($dbVersion < 12) include 'update-to-112.php';

	/*
	 * These are updates to the database that occured between 1.1.2 and 1.2.2
	 */
	if (!$abortUpdate) $newDbVersion = 12;

	if ($dbVersion < $newDbVersion) {
		$from_112 = true;
		if (getDbValue(icms::$xoopsDB, 'configcategory', 'confcat_name', 'confcat_name="_MD_AM_CAPTCHA"') == FALSE) {
			icms::$xoopsDB->queryF(" INSERT INTO " . icms::$xoopsDB->prefix("configcategory") . " (confcat_id, confcat_name) VALUES ('11', '_MD_AM_CAPTCHA')");
		}
		icms::$xoopsDB->queryF("DELETE FROM `" . icms::$xoopsDB->prefix('config') . "` WHERE (conf_modid='1' AND conf_catid='11')");
		// Adding new function of Captcha
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_CAPTCHA, 'captcha_mode', '_MD_AM_CAPTCHA_MODE', 'image', '_MD_AM_CAPTCHA_MODEDSC', 'select', 'text', 1);
		$config_id = icms::$xoopsDB->getInsertId();

		$sql = "INSERT INTO " . icms::$xoopsDB->prefix('configoption') . " (confop_id, confop_name, confop_value, conf_id)" . " VALUES" . " (NULL, '_MD_AM_CAPTCHA_OFF', 'none', {$config_id}), " . " (NULL, '_MD_AM_CAPTCHA_IMG', 'image', {$config_id}), " . " (NULL, '_MD_AM_CAPTCHA_TXT', 'text', {$config_id})";
		if (! icms::$xoopsDB->queryF($sql)) {
			return false;
		}
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_CAPTCHA, 'captcha_skipmember', '_MD_AM_CAPTCHA_SKIPMEMBER', serialize(array('2')), '_MD_AM_CAPTCHA_SKIPMEMBERDSC', 'group_multi', 'array', 2);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_CAPTCHA, 'captcha_casesensitive', '_MD_AM_CAPTCHA_CASESENS', '0', '_MD_AM_CAPTCHA_CASESENSDSC', 'yesno', 'int', 3);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_CAPTCHA, 'captcha_skip_characters', '_MD_AM_CAPTCHA_SKIPCHAR', serialize(array('o', '0', 'i', 'l', '1')), '_MD_AM_CAPTCHA_SKIPCHARDSC', 'textarea', 'array', 4);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_CAPTCHA, 'captcha_maxattempt', '_MD_AM_CAPTCHA_MAXATTEMP', '8', '_MD_AM_CAPTCHA_MAXATTEMPDSC', 'textbox', 'int', 5);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_CAPTCHA, 'captcha_num_chars', '_MD_AM_CAPTCHA_NUMCHARS', '4', '_MD_AM_CAPTCHA_NUMCHARSDSC', 'textbox', 'int', 6);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_CAPTCHA, 'captcha_fontsize_min', '_MD_AM_CAPTCHA_FONTMIN', '10', '_MD_AM_CAPTCHA_FONTMINDSC', 'textbox', 'int', 7);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_CAPTCHA, 'captcha_fontsize_max', '_MD_AM_CAPTCHA_FONTMAX', '12', '_MD_AM_CAPTCHA_FONTMAXDSC', 'textbox', 'int', 8);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_CAPTCHA, 'captcha_background_type', '_MD_AM_CAPTCHA_BGTYPE', '100', '_MD_AM_CAPTCHA_BGTYPEDSC', 'select', 'text', 9);
		$config_id = icms::$xoopsDB->getInsertId();

		$sql2 = "INSERT INTO " . icms::$xoopsDB->prefix('configoption') . " (confop_id, confop_name, confop_value, conf_id)" . " VALUES" . " (NULL, '_MD_AM_BAR', '0', {$config_id}), " . " (NULL, '_MD_AM_CIRCLE', '1', {$config_id}), " . " (NULL, '_MD_AM_LINE', '2', {$config_id}), " . " (NULL, '_MD_AM_RECTANGLE', '3', {$config_id}), " . " (NULL, '_MD_AM_ELLIPSE', '4', {$config_id}), " . " (NULL, '_MD_AM_POLYGON', '5', {$config_id}), " . " (NULL, '_MD_AM_RANDOM', '100', {$config_id})";
		if (! icms::$xoopsDB->queryF($sql2)) {
			return false;
		}
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_CAPTCHA, 'captcha_background_num', '_MD_AM_CAPTCHA_BGNUM', '50', '_MD_AM_CAPTCHA_BGNUMDSC', 'textbox', 'int', 10);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_CAPTCHA, 'captcha_polygon_point', '_MD_AM_CAPTCHA_POLPNT', '3', '_MD_AM_CAPTCHA_POLPNTDSC', 'textbox', 'int', 11);

		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 13;

	if ($dbVersion < $newDbVersion) {
		//echo sprintf(_CO_ICMS_UPDATE_DBVERSION, icms_conv_nr2local($newDbVersion));

		icms::$xoopsDB->queryF("UPDATE `" . icms::$xoopsDB->prefix('config') . "` SET conf_formtype = 'textsarea', conf_valuetype = 'text' WHERE conf_name = 'reg_disclaimer'");

		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 14;

	if ($dbVersion < $newDbVersion) {
		if (is_writable(ICMS_PLUGINS_PATH) || (is_dir(ICMS_ROOT_PATH . '/plugins/preloads') && is_writable(ICMS_ROOT_PATH . '/plugins/preloads'))) {
			if (is_dir(ICMS_ROOT_PATH . '/preload')) {
				/* Remove these 2 files so they don't overwrite the updated versions provided in 1.3 */
				icms_core_Filesystem::deleteFile(ICMS_ROOT_PATH . '/preload/customtag.php');
				icms_core_Filesystem::deleteFile(ICMS_ROOT_PATH . '/preload/userinfo.php');
				if (icms_core_Filesystem::copyRecursive(ICMS_ROOT_PATH . '/preload', ICMS_ROOT_PATH . '/plugins/preloads')) {
					icms_core_Filesystem::deleteRecursive(ICMS_ROOT_PATH . '/preload');
				} else {
					$newDbVersion = 13;
					echo '<br />'.sprintf(_MD_AM_PLUGINSFOLDER_UPDATE_TEXT, ICMS_PLUGINS_PATH, ICMS_ROOT_PATH . '/plugins/preloads');
					$abortUpdate = true;
				}
			}
		} else {
			$newDbVersion = 13;
			echo '<br />'.sprintf(_MD_AM_PLUGINSFOLDER_UPDATE_TEXT, ICMS_PLUGINS_PATH, ICMS_ROOT_PATH . '/plugins/preloads');
			$abortUpdate = true;
		}
		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 15;

	if ($dbVersion < $newDbVersion) {
		$table = new icms_db_legacy_updater_Table('users');
		if (! $table->fieldExists('login_name')) {
			$table->addNewField('login_name', "varchar(255) NOT NULL default ''");
			$icmsDatabaseUpdater->updateTable($table);
			icms::$xoopsDB->queryF("UPDATE `" . icms::$xoopsDB->prefix("users") . "` SET login_name=uname");
			$icmsDatabaseUpdater->runQuery("ALTER TABLE `" . $table->name() . "` ADD INDEX login_name(login_name)", 'Successfully altered the index login_name on table users', '');
		}
		unset($table);
		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 16;

	if ($dbVersion < $newDbVersion) {
		$sql = "SELECT conf_id FROM `" . icms::$xoopsDB->prefix('config') . "` WHERE conf_name = 'email_protect'";
		$result = icms::$xoopsDB->query($sql);
		list($conf_id) = icms::$xoopsDB->FetchRow($result);
		icms::$xoopsDB->queryF(" INSERT INTO " . icms::$xoopsDB->prefix("configoption") . " VALUES(NULL, '_MD_AM_NOMAILPROTECT', '0', " . $conf_id . ");");
		icms::$xoopsDB->queryF(" INSERT INTO " . icms::$xoopsDB->prefix("configoption") . " VALUES(NULL, '_MD_AM_GDMAILPROTECT', '1', " . $conf_id . ");");
		icms::$xoopsDB->queryF(" INSERT INTO " . icms::$xoopsDB->prefix("configoption") . " VALUES(NULL, '_MD_AM_REMAILPROTECT', '2', " . $conf_id . ");");
		icms::$xoopsDB->queryF("UPDATE `" . icms::$xoopsDB->prefix('config') . "` SET conf_formtype = 'select', conf_valuetype = 'text' WHERE conf_name = 'email_protect'");
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PERSONA, 'recprvkey', '_MD_AM_RECPRVKEY', '', '_MD_AM_RECPRVKEY_DESC', 'textbox', 'text', 17);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PERSONA, 'recpubkey', '_MD_AM_RECPUBKEY', '', '_MD_AM_RECPUBKEY_DESC', 'textbox', 'text', 17);

		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 17;

	if ($dbVersion < $newDbVersion) {
		//$icmsDatabaseUpdater->insertConfig(ICMS_CONF_USER, 'delusers', '_MD_AM_DELUSRES', '90', '_MD_AM_DELUSRESDSC', 'textbox', 'int', 3);
		if (getDbValue(icms::$xoopsDB, 'configcategory', 'confcat_name', 'confcat_name="_MD_AM_PLUGINS"') == FALSE) {
			icms::$xoopsDB->queryF(" INSERT INTO " . icms::$xoopsDB->prefix("configcategory") . " (confcat_id, confcat_name) VALUES ('12', '_MD_AM_PLUGINS')");
		}
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PLUGINS, 'sanitizer_plugins', '_MD_AM_SELECTSPLUGINS',  serialize(array('')), '_MD_AM_SELECTSPLUGINS_DESC', 'select_plugin', 'array', 1);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PLUGINS, 'code_sanitizer', '_MD_AM_SELECTSHIGHLIGHT', 'none', '_MD_AM_SELECTSHIGHLIGHT_DESC', 'select', 'text', 2);
		$config_id = icms::$xoopsDB->getInsertId();
		$sql = "INSERT INTO " . icms::$xoopsDB->prefix('configoption') . " (confop_id, confop_name, confop_value, conf_id)" . " VALUES" . " (NULL, '_MD_AM_HIGHLIGHTER_OFF', 'none', {$config_id}), " . " (NULL, '_MD_AM_HIGHLIGHTER_PHP', 'php', {$config_id}), " . " (NULL, '_MD_AM_HIGHLIGHTER_GESHI', 'geshi', {$config_id})";
		if (! icms::$xoopsDB->queryF($sql)) {
			return false;
		}
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PLUGINS, 'geshi_default', '_MD_AM_GESHI_DEFAULT', 'php', '_MD_AM_GESHI_DEFAULT_DESC', 'select_geshi', 'text', 3);
		icms::$xoopsDB->queryF("UPDATE `" . icms::$xoopsDB->prefix('config') . "` SET conf_valuetype = 'array' WHERE conf_name = 'startpage'");
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_USER, 'delusers', '_MD_AM_DELUSRES', '30', '_MD_AM_DELUSRESDSC', 'textbox', 'int', 6);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_USER, 'allow_chguname', '_MD_AM_ALLWCHGUNAME', '0', '_MD_AM_ALLWCHGUNAMEDSC', 'yesno', 'int', 11);
		$icmsDatabaseUpdater->insertConfig(IM_CONF_CONTENT, 'num_pages', '_MD_AM_CONT_NUMPAGES', '10', '_MD_AM_CONT_NUMPAGESDSC', 'textbox', 'int', 5);
		$icmsDatabaseUpdater->insertConfig(IM_CONF_CONTENT, 'teaser_length', '_MD_AM_CONT_TEASERLENGTH', '500', '_MD_AM_CONT_TEASERLENGTHDSC', 'textbox', 'int', 6);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PERSONA, 'pagstyle', '_MD_AM_PAGISTYLE', 'default', '_MD_AM_PAGISTYLE_DESC', 'select_paginati', 'text', 24);

		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 18;
	/* errors discovered after 1.2 beta release (dbversion 31) moved to dbversion 32 */
	if ($dbVersion < $newDbVersion) {

		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
	    echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 19;

	if ($dbVersion < $newDbVersion) {
		$module_handler = icms::handler('icms_module');
		$smartprofile_module = $module_handler->getByDirname('smartprofile');
		$table = new icms_db_legacy_updater_Table('profile_category');
		if ($smartprofile_module && $smartprofile_module->getVar('isactive') && ! $table->exists()) {
			icms::$xoopsDB->queryF("RENAME TABLE `" . icms::$xoopsDB->prefix("smartprofile_category") . "` TO `" . icms::$xoopsDB->prefix("profile_category") . "`");
			icms::$xoopsDB->queryF("RENAME TABLE `" . icms::$xoopsDB->prefix("smartprofile_field") . "` TO `" . icms::$xoopsDB->prefix("profile_field") . "`");
			icms::$xoopsDB->queryF("RENAME TABLE `" . icms::$xoopsDB->prefix("smartprofile_visibility") . "` TO `" . icms::$xoopsDB->prefix("profile_visibility") . "`");
			icms::$xoopsDB->queryF("RENAME TABLE `" . icms::$xoopsDB->prefix("smartprofile_profile") . "` TO `" . icms::$xoopsDB->prefix("profile_profile") . "`");
			icms::$xoopsDB->queryF("RENAME TABLE `" . icms::$xoopsDB->prefix("smartprofile_regstep") . "` TO `" . icms::$xoopsDB->prefix("profile_regstep") . "`");
			$command = array("ALTER TABLE `" . icms::$xoopsDB->prefix("profile_profile") . "` ADD `newemail` varchar(255) NOT NULL default '' AFTER `profile_id`", "ALTER TABLE `" . icms::$xoopsDB->prefix("profile_field") . "` ADD `exportable` int unsigned NOT NULL default 0 AFTER `step_id`", "UPDATE `" . icms::$xoopsDB->prefix('modules') . "` SET dirname='profile' WHERE dirname='smartprofile'");

			foreach($command as $sql) {
				if (! $result = icms::$xoopsDB->queryF($sql)) {
					icms_core_Debug::message('An error occurred while executing "' . $sql . '" - ' . icms::$xoopsDB->error());
					return false;
				}
			}
		}
		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 20;

	if ($dbVersion < $newDbVersion) {
		// Adding configurations of search preferences
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_SEARCH, 'enable_deep_search', '_MD_AM_DODEEPSEARCH', '1', '_MD_AM_DODEEPSEARCHDSC', 'yesno', 'int', 2);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_SEARCH, 'num_shallow_search', '_MD_AM_NUMINITSRCHRSLTS', '5', '_MD_AM_NUMINITSRCHRSLTSDSC', 'textbox', 'int', 4);
		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 21;

	if ($dbVersion < $newDbVersion) {
		// create extended date function's config option
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF, 'theme_admin_set', '_MD_AM_ADMIN_DTHEME', 'iTheme', '_MD_AM_ADMIN_DTHEME_DESC', 'theme_admin', 'other', 12);

		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 22;

	if ($dbVersion < $newDbVersion) {
		icms::$xoopsDB->queryF("DELETE FROM `" . icms::$xoopsDB->prefix('modules') . "` WHERE dirname='waiting'");
		icms::$xoopsDB->queryF("DELETE FROM `" . icms::$xoopsDB->prefix('newblocks') . "` WHERE dirname='waiting'");
		icms::$xoopsDB->queryF("DELETE FROM `" . icms::$xoopsDB->prefix('tplfile') . "` WHERE tpl_module='waiting'");

		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	/*	if (!$abortUpdate) $newDbVersion =  23;

	if ($dbVersion < $newDbVersion) {
		echo $action;
		icms::$xoopsDB->queryF("DELETE FROM `" . icms::$xoopsDB->prefix('config') . "` WHERE conf_name='pass_level'");
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_USER, 'pass_level', '_MD_AM_PASSLEVEL', '1', '_MD_AM_PASSLEVEL_DESC', 'yesno', 'int', 2);
	}
*/

	if (!$abortUpdate) $newDbVersion = 24;

	if ($dbVersion < $newDbVersion) {
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PERSONA, 'use_custom_redirection', '_MD_AM_CUSTOMRED', '0', '_MD_AM_CUSTOMREDDSC', 'yesno', 'int', 9);

		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 25;

	if ($dbVersion < $newDbVersion) {
		$table = new icms_db_legacy_updater_Table('icmscontent');
		if (! $table->fieldExists('content_seo_description')) {
			$table->addNewField('content_seo_description', "text");
			$icmsDatabaseUpdater->updateTable($table);
		}
		unset($table);

		$table = new icms_db_legacy_updater_Table('icmscontent');
		if (! $table->fieldExists('content_seo_keywords')) {
			$table->addNewField('content_seo_keywords', "text");
			$icmsDatabaseUpdater->updateTable($table);
		}
		unset($table);
		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 26;

	if ($dbVersion < $newDbVersion) {
		$table = new icms_db_legacy_updater_Table('system_mimetype');
		if (! $table->exists()) {
			$table->setStructure("mimetypeid int(11) NOT NULL auto_increment,
					extension varchar(60) NOT NULL default '',
					types text NOT NULL,
					name varchar(255) NOT NULL default '',
					dirname VARCHAR(255) NOT NULL,
					KEY mimetypeid (mimetypeid)
					");
			$table->createTable();
		}
		icms::$xoopsDB->queryFromFile(ICMS_ROOT_PATH . "/modules/" . $module->getVar('dirname', 'n') . "/include/upgrade.sql");
		unset($table);

		$table = new icms_db_legacy_updater_Table('system_adsense');
		if (! $table->exists()) {
			$table->setStructure("adsenseid int(11) NOT NULL auto_increment,
					format VARCHAR(100) NOT NULL,
					description TEXT NOT NULL,
					style TEXT NOT NULL,
					border_color varchar(6) NOT NULL default '',
					background_color varchar(6) NOT NULL default '',
					link_color varchar(6) NOT NULL default '',
					url_color varchar(6) NOT NULL default '',
					text_color varchar(6) NOT NULL default '',
					client_id varchar(100) NOT NULL default '',
					tag varchar(50) NOT NULL default '',
					PRIMARY KEY  (`adsenseid`)
					");
			$table->createTable();
		}
		unset($table);

		$table = new icms_db_legacy_updater_Table('system_rating');
		if (! $table->exists()) {
			$table->setStructure("ratingid int(11) NOT NULL auto_increment,
					dirname VARCHAR(255) NOT NULL,
					item VARCHAR(255) NOT NULL,
					itemid int(11) NOT NULL,
					uid int(11) NOT NULL,
					rate int(1) NOT NULL,
					date int(11) NOT NULL,
					PRIMARY KEY  (`ratingid`)
					");
			$table->createTable();
		}
		unset($table);
		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 27;

	if ($dbVersion < $newDbVersion) {
		$handler = icms_getModulehandler('userrank', 'system');
		$handler->MoveAllRanksImagesToProperPath();
		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 28;

	if ($dbVersion < $newDbVersion) {
		$table = new icms_db_legacy_updater_Table('system_autotasks');
		if (! $table->exists()) {
			$table->setStructure("sat_id int(10) unsigned NOT NULL AUTO_INCREMENT,
					sat_name varchar(255) NOT NULL,
					sat_code text NOT NULL,
					sat_repeat int(11) NOT NULL,
					sat_interval int(11) NOT NULL,
					sat_onfinish smallint(2) NOT NULL,
					sat_enabled INT(1) NOT NULL,
					sat_lastruntime int(15) unsigned NOT NULL,
					sat_type varchar(100) NOT NULL DEFAULT 'custom',
					sat_addon_id int(2) unsigned zerofill DEFAULT NULL,
					PRIMARY KEY (sat_id),
					KEY sat_interval (sat_interval),
					KEY sat_lastruntime (sat_lastruntime),
					KEY sat_type (sat_type)
					");
			$table->createTable();
		}
		unset($table);

		if (getDbValue(icms::$xoopsDB, 'configcategory', 'confcat_name', 'confcat_name="_MD_AM_AUTOTASKS"') == FALSE) {
			icms::$xoopsDB->queryF(" INSERT INTO " . icms::$xoopsDB->prefix("configcategory") . " (confcat_id, confcat_name) VALUES (13, '_MD_AM_AUTOTASKS')");
		}

		$icmsDatabaseUpdater->insertConfig(ICMS_CONF, 'sourceeditor_default', '_MD_AM_SRCEDITOR_DEFAULT', 'editarea', '_MD_AM_SRCEDITOR_DEFAULT_DESC', 'editor_source', 'text', 16);
		$icmsDatabaseUpdater->insertConfig(IM_CONF_AUTOTASKS, 'autotasks_system', '_MD_AM_AUTOTASKS_SYSTEM', 'internal', '_MD_AM_AUTOTASKS_SYSTEMDSC', 'autotasksystem', 'text', 1);
		$icmsDatabaseUpdater->insertConfig(IM_CONF_AUTOTASKS, 'autotasks_helper', '_MD_AM_AUTOTASKS_HELPER', 'wget %url%', '_MD_AM_AUTOTASKS_HELPERDSC', 'select', 'text', 2);
		$config_id = icms::$xoopsDB->getInsertId();
		$sql = "INSERT INTO " . icms::$xoopsDB->prefix('configoption') . " (confop_id, confop_name, confop_value, conf_id)" . " VALUES" . " (NULL, 'PHP-CGI', 'php -f %path%', {$config_id}), " . " (NULL, 'wget', 'wget %url%', {$config_id}), " . " (NULL, 'Lynx', 'lynx --dump %url%', {$config_id})";
		if (! icms::$xoopsDB->queryF($sql)) {
			return false;
		}
		$icmsDatabaseUpdater->insertConfig(IM_CONF_AUTOTASKS, 'autotasks_helper_path', '_MD_AM_AUTOTASKS_HELPER_PATH', '/usr/bin/', '_MD_AM_AUTOTASKS_HELPER_PATHDSC', 'text', 'text', 3);
		$icmsDatabaseUpdater->insertConfig(IM_CONF_AUTOTASKS, 'autotasks_user', '_MD_AM_AUTOTASKS_USER', '', '_MD_AM_AUTOTASKS_USERDSC', 'text', 'text', 4);
		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 29;

	if ($dbVersion < $newDbVersion) {
		if (getDbValue(icms::$xoopsDB, 'configcategory', 'confcat_name', 'confcat_name="_MD_AM_PURIFIER"') == FALSE) {
			icms::$xoopsDB->queryF("INSERT INTO " . icms::$xoopsDB->prefix('configcategory') . " (confcat_id, confcat_name) VALUES ('14', '_MD_AM_PURIFIER')");
		}

		$table = new icms_db_legacy_updater_Table('config');
		$icmsDatabaseUpdater->runQuery("ALTER TABLE `" . $table->name() . "` MODIFY conf_name VARCHAR(75) NOT NULL default ''", 'Successfully altered field conf_name in config', '');
		unset($table);

		include_once ICMS_ROOT_PATH . '/include/functions.php';
		$host_domain = icms_get_base_domain(ICMS_URL);
		$host_base = icms_get_url_domain(ICMS_URL);

		// Allowed Elements in HTML
		$HTML_Allowed_Elms = array('a', 'abbr', 'acronym', 'b', 'blockquote', 'br', 'caption', 'cite', 'code', 'dd', 'del', 'dfn', 'div', 'dl', 'dt', 'em', 'font', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'i', 'img', 'ins', 'kbd', 'li', 'ol', 'p', 'pre', 's', 'span', 'strike', 'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'tt', 'u', 'ul', 'var');

		// Allowed Element Attributes in HTML', 'element must also be allowed in Allowed Elements for these attributes to work.
		$HTML_Allowed_Attr = array('a.class', 'a.href', 'a.id', 'a.name', 'a.rev', 'a.style', 'a.title', 'a.target', 'a.rel', 'abbr.title', 'acronym.title', 'blockquote.cite', 'div.align', 'div.style', 'div.class', 'div.id', 'font.size', 'font.color', 'h1.style', 'h2.style', 'h3.style', 'h4.style', 'h5.style', 'h6.style', 'img.src', 'img.alt', 'img.title', 'img.class', 'img.align', 'img.style', 'img.height', 'img.width', 'li.style', 'ol.style', 'p.style', 'span.style', 'span.class', 'span.id', 'table.class', 'table.id', 'table.border', 'table.cellpadding', 'table.cellspacing', 'table.style', 'table.width', 'td.abbr', 'td.align', 'td.class', 'td.id', 'td.colspan', 'td.rowspan', 'td.style', 'td.valign', 'tr.align', 'tr.class', 'tr.id', 'tr.style', 'tr.valign', 'th.abbr', 'th.align', 'th.class', 'th.id', 'th.colspan', 'th.rowspan', 'th.style', 'th.valign', 'ul.style');

		$p = 0;
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'enable_purifier', '_MD_AM_PURIFIER_ENABLE', '1', '_MD_AM_PURIFIER_ENABLEDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_URI_DefinitionID', '_MD_AM_PURIFIER_URI_DEFID', 'system', '_MD_AM_PURIFIER_URI_DEFIDDSC', 'textbox', 'text', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_URI_DefinitionRev', '_MD_AM_PURIFIER_URI_DEFREV', '1', '_MD_AM_PURIFIER_URI_DEFREVDSC', 'textbox', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_URI_Host', '_MD_AM_PURIFIER_URI_HOST', addslashes($host_domain), '_MD_AM_PURIFIER_URI_HOSTDSC', 'textbox', 'text', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_URI_Base', '_MD_AM_PURIFIER_URI_BASE', addslashes($host_base), '_MD_AM_PURIFIER_URI_BASEDSC', 'textbox', 'text', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_URI_Disable', '_MD_AM_PURIFIER_URI_DISABLE', '0', '_MD_AM_PURIFIER_URI_DISABLEDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_URI_DisableExternal', '_MD_AM_PURIFIER_URI_DISABLEEXT', '0', '_MD_AM_PURIFIER_URI_DISABLEEXTDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_URI_DisableExternalResources', '_MD_AM_PURIFIER_URI_DISABLEEXTRES', '0', '_MD_AM_PURIFIER_URI_DISABLEEXTRESDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_URI_DisableResources', '_MD_AM_PURIFIER_URI_DISABLERES', '0', '_MD_AM_PURIFIER_URI_DISABLERESDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_URI_MakeAbsolute', '_MD_AM_PURIFIER_URI_MAKEABS', '0', '_MD_AM_PURIFIER_URI_MAKEABSDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_URI_HostBlacklist', '_MD_AM_PURIFIER_URI_BLACKLIST', '', '_MD_AM_PURIFIER_URI_BLACKLISTDSC', 'textsarea', 'array', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_URI_AllowedSchemes', '_MD_AM_PURIFIER_URI_ALLOWSCHEME',  serialize(array('http', 'https', 'mailto', 'ftp', 'nntp', 'news')), '_MD_AM_PURIFIER_URI_ALLOWSCHEMEDSC', 'textsarea', 'array', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_HTML_DefinitionID', '_MD_AM_PURIFIER_HTML_DEFID', 'system', '_MD_AM_PURIFIER_HTML_DEFIDDSC', 'textbox', 'text', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_HTML_DefinitionRev', '_MD_AM_PURIFIER_HTML_DEFREV', '1', '_MD_AM_PURIFIER_HTML_DEFREVDSC', 'textbox', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_HTML_Doctype', '_MD_AM_PURIFIER_HTML_DOCTYPE', 'XHTML 1.0 Transitional', '_MD_AM_PURIFIER_HTML_DOCTYPEDSC', 'select', 'text', $p++);

		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_HTML_TidyLevel', '_MD_AM_PURIFIER_HTML_TIDYLEVEL', 'medium', '_MD_AM_PURIFIER_HTML_TIDYLEVELDSC', 'select', 'text', $p++);
		$config_id = icms::$xoopsDB->getInsertId();
		$sql = "INSERT INTO " . icms::$xoopsDB->prefix('configoption') . " (confop_id, confop_name, confop_value, conf_id)" . " VALUES" . " (NULL, '_MD_AM_PURIFIER_NONE', 'none', {$config_id}), " . " (NULL, '_MD_AM_PURIFIER_LIGHT', 'light', {$config_id}), " . " (NULL, '_MD_AM_PURIFIER_MEDIUM', 'medium', {$config_id}), " . " (NULL, '_MD_AM_PURIFIER_HEAVY', 'heavy', {$config_id})";
		if (! icms::$xoopsDB->queryF($sql)) {
			return false;
		}

		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_HTML_AllowedElements', '_MD_AM_PURIFIER_HTML_ALLOWELE',  serialize($HTML_Allowed_Elms), '_MD_AM_PURIFIER_HTML_ALLOWELEDSC', 'textsarea', 'array', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_HTML_AllowedAttributes', '_MD_AM_PURIFIER_HTML_ALLOWATTR',  serialize($HTML_Allowed_Attr), '_MD_AM_PURIFIER_HTML_ALLOWATTRDSC', 'textsarea', 'array', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_HTML_ForbiddenElements', '_MD_AM_PURIFIER_HTML_FORBIDELE', '', '_MD_AM_PURIFIER_HTML_FORBIDELEDSC', 'textsarea', 'array', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_HTML_ForbiddenAttributes', '_MD_AM_PURIFIER_HTML_FORBIDATTR', '', '_MD_AM_PURIFIER_HTML_FORBIDATTRDSC', 'textsarea', 'array', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_HTML_MaxImgLength', '_MD_AM_PURIFIER_HTML_MAXIMGLENGTH', '1200', '_MD_AM_PURIFIER_HTML_MAXIMGLENGTHDSC', 'textbox', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_HTML_SafeEmbed', '_MD_AM_PURIFIER_HTML_SAFEEMBED', '0', '_MD_AM_PURIFIER_HTML_SAFEEMBEDDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_HTML_SafeObject', '_MD_AM_PURIFIER_HTML_SAFEOBJECT', '0', '_MD_AM_PURIFIER_HTML_SAFEOBJECTDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_HTML_AttrNameUseCDATA', '_MD_AM_PURIFIER_HTML_ATTRNAMEUSECDATA', '0', '_MD_AM_PURIFIER_HTML_ATTRNAMEUSECDATADSC', 'yesno', 'int', $p++);

		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Filter_ExtractStyleBlocks', '_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLK', '1', '_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Filter_ExtractStyleBlocks_Escaping', '_MD_AM_PURIFIER_FILTERPARAM_EXTRACTSTYLEESC', '1', '_MD_AM_PURIFIER_FILTERPARAM_EXTRACTSTYLEESCDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Filter_ExtractStyleBlocks_Scope', '_MD_AM_PURIFIER_FILTERPARAM_EXTRACTSTYLEBLKSCOPE', '', '_MD_AM_PURIFIER_FILTERPARAM_EXTRACTSTYLEBLKSCOPEDSC', 'textsarea', 'text', $p++);

		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Filter_YouTube', '_MD_AM_PURIFIER_FILTER_ENABLEYOUTUBE', '1', '_MD_AM_PURIFIER_FILTER_ENABLEYOUTUBEDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Core_EscapeNonASCIICharacters', '_MD_AM_PURIFIER_CORE_ESCNONASCIICHARS', '1', '_MD_AM_PURIFIER_CORE_ESCNONASCIICHARSDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Core_HiddenElements', '_MD_AM_PURIFIER_CORE_HIDDENELE',  serialize(array('script', 'style')), '_MD_AM_PURIFIER_CORE_HIDDENELEDSC', 'textsarea', 'array', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Core_RemoveInvalidImg', '_MD_AM_PURIFIER_CORE_REMINVIMG', '1', '_MD_AM_PURIFIER_CORE_REMINVIMGDSC', 'yesno', 'int', $p++);

		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_AutoFormat_AutoParagraph', '_MD_AM_PURIFIER_AUTO_AUTOPARA', '0', '_MD_AM_PURIFIER_AUTO_AUTOPARADSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_AutoFormat_DisplayLinkURI', '_MD_AM_PURIFIER_AUTO_DISPLINKURI', '0', '_MD_AM_PURIFIER_AUTO_DISPLINKURIDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_AutoFormat_Linkify', '_MD_AM_PURIFIER_AUTO_LINKIFY', '1', '_MD_AM_PURIFIER_AUTO_LINKIFYDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_AutoFormat_PurifierLinkify', '_MD_AM_PURIFIER_AUTO_PURILINKIFY', '0', '_MD_AM_PURIFIER_AUTO_PURILINKIFYDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_AutoFormat_Custom', '_MD_AM_PURIFIER_AUTO_CUSTOM', '', '_MD_AM_PURIFIER_AUTO_CUSTOMDSC', 'textsarea', 'array', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_AutoFormat_RemoveEmpty', '_MD_AM_PURIFIER_AUTO_REMOVEEMPTY', '0', '_MD_AM_PURIFIER_AUTO_REMOVEEMPTYDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_AutoFormat_RemoveEmptyNbsp', '_MD_AM_PURIFIER_AUTO_REMOVEEMPTYNBSP', '0', '_MD_AM_PURIFIER_AUTO_REMOVEEMPTYNBSPDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_AutoFormat_RemoveEmptyNbspExceptions', '_MD_AM_PURIFIER_AUTO_REMOVEEMPTYNBSPEXCEPT',  serialize(array('td', 'th')), '_MD_AM_PURIFIER_AUTO_REMOVEEMPTYNBSPEXCEPTDSC', 'textsarea', 'array', $p++);

		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Attr_AllowedFrameTargets', '_MD_AM_PURIFIER_ATTR_ALLOWFRAMETARGET',  serialize(array('_blank', '_parent', '_self', '_top')), '_MD_AM_PURIFIER_ATTR_ALLOWFRAMETARGETDSC', 'textsarea', 'array', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Attr_AllowedRel', '_MD_AM_PURIFIER_ATTR_ALLOWREL', serialize(array('external', 'nofollow', 'external nofollow', 'lightbox')), '_MD_AM_PURIFIER_ATTR_ALLOWRELDSC', 'textsarea', 'array', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Attr_AllowedClasses', '_MD_AM_PURIFIER_ATTR_ALLOWCLASSES', '', '_MD_AM_PURIFIER_ATTR_ALLOWCLASSESDSC', 'textsarea', 'array', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Attr_ForbiddenClasses', '_MD_AM_PURIFIER_ATTR_FORBIDDENCLASSES', '', '_MD_AM_PURIFIER_ATTR_FORBIDDENCLASSESDSC', 'textsarea', 'array', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Attr_DefaultInvalidImage', '_MD_AM_PURIFIER_ATTR_DEFINVIMG', '', '_MD_AM_PURIFIER_ATTR_DEFINVIMGDSC', 'textbox', 'text', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Attr_DefaultInvalidImageAlt', '_MD_AM_PURIFIER_ATTR_DEFINVIMGALT', '', '_MD_AM_PURIFIER_ATTR_DEFINVIMGALTDSC', 'textbox', 'text', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Attr_DefaultImageAlt', '_MD_AM_PURIFIER_ATTR_DEFIMGALT', '', '_MD_AM_PURIFIER_ATTR_DEFIMGALTDSC', 'textbox', 'text', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Attr_ClassUseCDATA', '_MD_AM_PURIFIER_ATTR_CLASSUSECDATA', '1', '_MD_AM_PURIFIER_ATTR_CLASSUSECDATADSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Attr_EnableID', '_MD_AM_PURIFIER_ATTR_ENABLEID', '1', '_MD_AM_PURIFIER_ATTR_ENABLEIDDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Attr_IDPrefix', '_MD_AM_PURIFIER_ATTR_IDPREFIX', '', '_MD_AM_PURIFIER_ATTR_IDPREFIXDSC', 'textbox', 'text', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Attr_IDPrefixLocal', '_MD_AM_PURIFIER_ATTR_IDPREFIXLOCAL', '', '_MD_AM_PURIFIER_ATTR_IDPREFIXLOCALDSC', 'textbox', 'text', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_Attr_IDBlacklist', '_MD_AM_PURIFIER_ATTR_IDBLACKLIST', '', '_MD_AM_PURIFIER_ATTR_IDBLACKLISTDSC', 'textsarea', 'array', $p++);

		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_CSS_DefinitionRev', '_MD_AM_PURIFIER_CSS_DEFREV', '1', '_MD_AM_PURIFIER_CSS_DEFREVDSC', 'textbox', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_CSS_AllowImportant', '_MD_AM_PURIFIER_CSS_ALLOWIMPORTANT', '1', '_MD_AM_PURIFIER_CSS_ALLOWIMPORTANTDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_CSS_AllowTricky', '_MD_AM_PURIFIER_CSS_ALLOWTRICKY', '1', '_MD_AM_PURIFIER_CSS_ALLOWTRICKYDSC', 'yesno', 'int', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_CSS_AllowedProperties', '_MD_AM_PURIFIER_CSS_ALLOWPROP', '', '_MD_AM_PURIFIER_CSS_ALLOWPROPDSC', 'textsarea', 'array', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_CSS_MaxImgLength', '_MD_AM_PURIFIER_CSS_MAXIMGLEN', '1200px', '_MD_AM_PURIFIER_CSS_MAXIMGLENDSC', 'textbox', 'text', $p++);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PURIFIER, 'purifier_CSS_Proprietary', '_MD_AM_PURIFIER_CSS_PROPRIETARY', '1', '_MD_AM_PURIFIER_CSS_PROPRIETARYDSC', 'yesno', 'int', $p++);

		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 30;

	if ($dbVersion < $newDbVersion) {

		$table = new icms_db_legacy_updater_Table('users');
		if ($table->fieldExists('level')) {
			$icmsDatabaseUpdater->runQuery("ALTER TABLE `" . $table->name() . "` MODIFY level varchar(3) NOT NULL default '1'", 'Successfully modified field level in table users', '');
		}
		unset($table);
		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	/* 1.2 beta release */

	if (!$abortUpdate) $newDbVersion = 32;
	/* this was in dbversion 18, but there were errors discovered after 1.2 beta relase */
	if ($dbVersion < $newDbVersion) {
		/*
		$table = new icms_db_legacy_updater_Table('icmscontent');
		if (!$table->fieldExists('content_tags')) {
		$table->addNewField('content_tags', "text");
		$icmsDatabaseUpdater->updateTable($table);
		}
		unset($table);
		*/
		$table = new icms_db_legacy_updater_Table('imagecategory');
		if (! $table->fieldExists('imgcat_foldername')) {
			$table->addNewField('imgcat_foldername', "varchar(100) default ''");
		}
		if (! $table->fieldExists('imgcat_pid')) {
			$table->addNewField('imgcat_pid', "smallint(5) unsigned NOT NULL default '0'");
		}
		$icmsDatabaseUpdater->updateTable($table);
		unset($table);

		/**
		 * DEVELOPER, PLEASE NOTE !!!
		 *
		 * Everytime we add a new modules to system, the cache folders must get cleaned up so,
		 * set a value for '$CleanWritingFolders' in each upgrade block here, if there is a cache
		 * cleaning required please add $CleanWritingFolders = 1 and otherwise $CleanWritingFolders = 0
		 * Like this below:
		 */
		$CleanWritingFolders = true;
		/* end of dbversion 18 update */
		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';

	}
	if (!$abortUpdate) $newDbVersion = 33;
	/*
	 * New symlinks need to be added to the db
	 */
	if ($dbVersion < $newDbVersion) {
		$table = new icms_db_legacy_updater_Table('icmspage');
		$new_pages = array(
			"NULL, 1, '" . _CPHOME . "', 'admin.php', 1",
			"NULL, 1, '" . _MI_SYSTEM_ADMENU14 . "', 'modules/system/admin.php?fct=avatars*', 1",
			"NULL, 1, '" . _MI_SYSTEM_ADMENU1 . "', 'modules/system/admin.php?fct=banners*', 1",
			"NULL, 1, '" . _MI_SYSTEM_ADMENU2 . "', 'modules/system/admin.php?fct=blocksadmin*', 1",
			"NULL, 1, '" . _MI_SYSTEM_ADMENU19 . "', 'modules/system/admin.php?fct=blockspadmin*', 1",
			"NULL, 1, '" . _MI_SYSTEM_ADMENU16 . "', 'modules/system/admin.php?fct=comments*', 1",
			"NULL, 1, '" . _MI_SYSTEM_ADMENU12 . "', 'modules/system/admin.php?fct=findusers*', 1",
			"NULL, 1, '" . _MI_SYSTEM_ADMENU21 . "', 'modules/system/admin.php?fct=customtag*', 1",
			"NULL, 1, '" . _MI_SYSTEM_ADMENU3 . "', 'modules/system/admin.php?fct=groups*', 1",
			"NULL, 1, '" . _MI_SYSTEM_ADMENU13 . "', 'modules/system/admin.php?fct=images*', 1",
			"NULL, 1, '" . _MI_SYSTEM_ADMENU11 . "', 'modules/system/admin.php?fct=mailusers*', 1",
			"NULL, 1, '" . _MD_AM_MDAD . "', 'modules/system/admin.php?fct=modulesadmin*', 1",
			"NULL, 1, '" . _MI_SYSTEM_ADMENU20 . "', 'modules/system/admin.php?fct=pages*', 1",
			"NULL, 1, '" . _MI_SYSTEM_ADMENU6 . "', 'modules/system/admin.php?fct=preferences*', 1",
			"NULL, 1, '" . _MI_SYSTEM_ADMENU7 . "', 'modules/system/admin.php?fct=smilies*', 1",
			"NULL, 1, '" . _MI_SYSTEM_ADMENU15 . "', 'modules/system/admin.php?fct=tplsets*', 1",
			"NULL, 1, '" . _MI_SYSTEM_ADMENU9 . "', 'modules/system/admin.php?fct=userrank*', 1",
			"NULL, 1, '" . _MI_SYSTEM_ADMENU10 . "', 'modules/system/admin.php?fct=users*', 1",
			"NULL, 1, '" . _MD_AM_VRSN . "', 'modules/system/admin.php?fct=version*', 1"
			);
		foreach($new_pages as $new_page) {
			$table->setData($new_page);
		}
		$table->addData();
		unset($table);
		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 34;
	/* The admin control panel now consists of blocks - these need to be set as visible
		 * Control Panel, System Warnings, Modules Installed
		 */
	if ($dbVersion < $newDbVersion) {
		$admin_blocks = array(array('b_system_admin_cp_show', 'page_topleft_admin'), array('b_system_admin_modules_show', 'page_topright_admin'), array('b_system_admin_warnings_show', 'page_topcenter_admin'));
		/* Get block positions */
		$sql = 'SELECT id, pname FROM ' . icms::$xoopsDB->prefix('block_positions') . ' WHERE pname = "page_topleft_admin"' . ' OR pname = "page_topright_admin"' . ' OR pname = "page_topcenter_admin"';
		$result = icms::$xoopsDB->query($sql);
		while ($row = icms::$xoopsDB->fetchArray($result)) {
			$block_positions [$row ['pname']] = $row ['id'];
		}
		/* Get symlink id for Admin Control Panel */
		$page_id = getDbValue(icms::$xoopsDB, 'icmspage', 'page_id', 'page_url="admin.php"');

		foreach($admin_blocks as $admin_block) {
			/* Get block ids for Control Panel, System Warnings, Installed Modules */
			$sql_find = 'SELECT bid FROM `' . icms::$xoopsDB->prefix('newblocks') . '` WHERE show_func="' . $admin_block [0] . '"';
			$goodmsg = $admin_block [0] . ' updated';
			$badmsg = $admin_block [0] . ' failed';
			$result = icms::$xoopsDB->query($sql_find);
			list($block_id) = icms::$xoopsDB->fetchRow($result);
			/* Modify the visible, side and visiblein properties of the blocks */
			$sql_update = 'UPDATE `' . icms::$xoopsDB->prefix('newblocks') . '` SET `visible`=1, `side`=' . $block_positions [$admin_block [1]] . ' WHERE `bid`=' . $block_id;
			$icmsDatabaseUpdater->runQuery($sql_update, $goodmsg, $badmsg, true);
			$sql_page_update = 'UPDATE `' . icms::$xoopsDB->prefix('block_module_link') . '` SET `module_id`=1, `page_id`=' . $page_id . ' WHERE `block_id`=' . $block_id;
			$icmsDatabaseUpdater->runQuery($sql_page_update, $goodmsg, $badmsg, true);
		}
		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}
	if (!$abortUpdate) $newDbVersion = 35;
	/* copy settings for the old waiting contents block to the new block
	 * and set the block_type correctly for new system blocks -
	 * blocks added during a module update default to block_type 'M', which
	 * is not correct for the system module, adding todo in modulesadmin
	 */
	if ($dbVersion < $newDbVersion) {
		$result = icms::$xoopsDB->query('SELECT title, side, weight, visible, bcachetime, bid' . ' FROM `' . icms::$xoopsDB->prefix('newblocks') . '` WHERE `show_func`="b_system_waiting_show" AND `func_file`="system_blocks.php"');
		list($title, $side, $weight, $visible, $bcachetime, $bid) = icms::$xoopsDB->fetchRow($result);
		icms::$xoopsDB->queryF('UPDATE `' . icms::$xoopsDB->prefix('newblocks') . '` SET `title`="' . $title . '", `side`=' . $side . ', `weight`=' . $weight . ', `visible`=' . $visible . ', `bcachetime`=' . $bcachetime . ' WHERE `show_func`="b_system_waiting_show" AND `func_file`="system_waiting.php"');
		icms::$xoopsDB->queryF('DELETE FROM `' . icms::$xoopsDB->prefix('newblocks') . '` WHERE `bid`=' . $bid);
		icms::$xoopsDB->queryF('DELETE FROM `' . icms::$xoopsDB->prefix('block_module_link') . '` WHERE `block_id`=' . $bid);
		icms::$xoopsDB->queryF('UPDATE `' . icms::$xoopsDB->prefix('newblocks') . '` SET `block_type`="S"' . ' WHERE `dirname`="system" AND `block_type`="M"');

		/* Change the field type of welcome_msg_content to textsarea */
		$sql_welcome_msg_content = 'UPDATE ' . icms::$xoopsDB->prefix('config') . ' SET `conf_formtype` = "textsarea"' . ' WHERE `conf_name`="welcome_msg_content"';
		$icmsDatabaseUpdater->runQuery($sql_welcome_msg_content, 'Welcome message form type successfully updated', 'Unable to update the welcome message form type', true);

		/* Set the start page for each group, so they don't default to Admin Control Panel */
		$groups = icms::handler('icms_member_group')->getObjects(NULL, true);
		$start_page = getDbValue(icms::$xoopsDB, 'config', 'conf_value', 'conf_name="startpage"');
		foreach($groups as $groupid => $group) {
			$start_pages [$groupid] = $start_page;
		}
		icms::$xoopsDB->queryF('UPDATE `' . icms::$xoopsDB->prefix('config') . '`' . ' SET `conf_value`="' . addslashes(serialize($start_pages)) . '"' . ' WHERE `conf_name`="startpage"');

		/* Check for HTMLPurifier cache path and create, if needed */
		$purifier_path = icms_core_Filesystem::mkdir(ICMS_TRUST_PATH . '/cache/htmlpurifier');
		/* Removing the option for multilogin text, as we're using a constant for it */
		icms::$xoopsDB->queryF("DELETE FROM `" . icms::$xoopsDB->prefix('config') . "` WHERE conf_name='multi_login_msg'");
		icms::$xoopsDB->queryF("DELETE FROM `" . icms::$xoopsDB->prefix('config') . "` WHERE conf_name='use_hidden'");

		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	/* 1.2 RC1 released */

	if (!$abortUpdate) $newDbVersion = 36;
	if ($dbVersion < $newDbVersion) {
		/* Change the the constant name for extractsyleblock_escape & styleblocks */
		$sql_extract_esc = 'UPDATE ' . icms::$xoopsDB->prefix('config') . ' SET `conf_title` = "_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEESC"' . ' WHERE `conf_name`="purifier_Filter_ExtractStyleBlocks_Escaping"';
		$icmsDatabaseUpdater->runQuery($sql_extract_esc, 'Constant _MD_AM_PURIFIER_FILTER_EXTRACTSTYLEESC Updated', 'Unable to update Constant _MD_AM_PURIFIER_FILTER_EXTRACTSTYLEESC', true);

		/* Change the the constant name for extractsyleblock_escape & styleblocks Descriptions*/
		$sql_extract_escdsc = 'UPDATE ' . icms::$xoopsDB->prefix('config') . ' SET `conf_desc` = "_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEESCDSC"' . ' WHERE `conf_name`="purifier_Filter_ExtractStyleBlocks_Escaping"';
		$icmsDatabaseUpdater->runQuery($sql_extract_escdsc, 'Constant _MD_AM_PURIFIER_FILTER_EXTRACTSTYLEESCDSC Updated', 'Unable to update Constant _MD_AM_PURIFIER_FILTER_EXTRACTSTYLEESCDSC', true);

		/* Change the the constant name for extractsyleblock_scope */
		$sql_extract_scope = 'UPDATE ' . icms::$xoopsDB->prefix('config') . ' SET `conf_title` = "_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKSCOPE"' . ' WHERE `conf_name`="purifier_Filter_ExtractStyleBlocks_Scope"';
		$icmsDatabaseUpdater->runQuery($sql_extract_scope, 'Constant _MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKSCOPE Updated', 'Unable to update Constant _MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKSCOPE', true);

		/* Change the the constant name for extractsyleblock_scope Descriptions*/
		$sql_extract_scopedsc = 'UPDATE ' . icms::$xoopsDB->prefix('config') . ' SET `conf_desc` = "_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKSCOPEDSC"' . ' WHERE `conf_name`="purifier_Filter_ExtractStyleBlocks_Scope"';
		$icmsDatabaseUpdater->runQuery($sql_extract_scopedsc, 'Constant _MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKSCOPEDSC Updated', 'Unable to update Constant _MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKSCOPEDSC', true);
		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 37;
	/* moving the images of the image manager from uploads to the new folder */
	if ($dbVersion < $newDbVersion) {
		if (is_writable(ICMS_IMANAGER_FOLDER_PATH)) {

			$result = icms::$xoopsDB->query('SELECT * FROM `' . icms::$xoopsDB->prefix('imagecategory') . '`');
			while ($row = icms::$xoopsDB->fetchArray($result)) {
				if (empty($row ['imgcat_foldername']) && $row[ 'imgcat_storetype' ] = 'file') {
					$new_folder =  preg_replace( '/[:?".<>\/\\\|\s]/', '_', strtolower($row[ 'imgcat_name' ]));
				} else {
					$new_folder = $row ['imgcat_foldername '];
				}
				/* attempt to create the new folder for the image category */
				if (FALSE === icms_core_Filesystem::mkdir(ICMS_IMANAGER_FOLDER_PATH . '/' . $new_folder)) {
					$newDbVersion = 36;
					echo '<br />' . sprintf('Unable to create category folder - %s', $new_folder);
					$abortUpdate = true;

				} else {
					$moved = array();
					/* Get all the images in the category */
					$result1 = icms::$xoopsDB->query(
						'SELECT * FROM `' . icms::$xoopsDB->prefix('image')
						. '` WHERE imgcat_id=' . $row['imgcat_id']
					);
					/* copy all the images in the category to the new folder */
					while (($row1 = icms::$xoopsDB->fetchArray($result1)) && ! $abortUpdate) {
						if (! file_exists(ICMS_IMANAGER_FOLDER_PATH . '/' . $new_folder . '/' . $row1 ['image_name'])
							&& file_exists(ICMS_UPLOAD_PATH . '/' . $row1['image_name'])
						) {
							if (icms_core_Filesystem::copyRecursive(
								ICMS_UPLOAD_PATH . '/' . $row1['image_name'],
								ICMS_IMANAGER_FOLDER_PATH . '/' . $new_folder . '/' . $row1['image_name'])
							) {
								$moved[] = $row1['image_name'];
 							} else {
								$newDbVersion = 36;
								echo '<br />' . sprintf('Unable to copy image - %s', $row1['image_name']);
								$abortUpdate = true;
							}
						}
					}
					/* if all the images were successfully copied for this category - update the category folder and
					 * Do NOT delete the old images - it affects existing content
					 */
					if (FALSE === $abortUpdate) {
						icms::$xoopsDB->queryF(
							'UPDATE `' . icms::$xoopsDB->prefix('imagecategory')
							. '` SET imgcat_foldername="' . $new_folder
							. '" WHERE imgcat_id=' . $row['imgcat_id']
						);
						/* This would delete the images from the uploads folder after they are moved
						 * do not do this - it will affect any existing content
						foreach ($moved as $image) {
							@unlink(ICMS_UPLOAD_PATH . '/' . $image);
						}
						*/
					}
				}
			}
			/**
			 *Changing the path of the left and right admin logo, defined in the personalization preferences area.
			 */
			$result = icms::$xoopsDB->query('SELECT conf_id, conf_value FROM `' . icms::$xoopsDB->prefix('config') . '` WHERE conf_name = "adm_left_logo" or conf_name = "adm_right_logo"');
			while (list($conf_id, $conf_value) = icms::$xoopsDB->fetchRow($result)) {
				$img = explode('/', $conf_value);
				$img = $img [count($img) - 1];
				$result1 = icms::$xoopsDB->query('SELECT imgcat_id FROM `' . icms::$xoopsDB->prefix('image') . '` WHERE image_name="' . $img . '"');
				list($imgcat_id) = icms::$xoopsDB->fetchRow($result1);
				$result2 = icms::$xoopsDB->query('SELECT imgcat_foldername FROM `' . icms::$xoopsDB->prefix('imagecategory') . '` WHERE imgcat_id="' . $imgcat_id . '"');
				list($imgcat_foldername) = icms::$xoopsDB->fetchRow($result2);
				$new_conf_value = str_replace(ICMS_ROOT_PATH, '', ICMS_IMANAGER_FOLDER_PATH) . '/' . $imgcat_foldername . '/' . $img;
				icms::$xoopsDB->queryF('UPDATE `' . icms::$xoopsDB->prefix('config') . '` SET conf_value="' . $new_conf_value . '" WHERE conf_id=' . $conf_id);
			}
		} else {
			$newDbVersion = 36;
			echo '<br />' . sprintf(_MD_AM_IMAGESFOLDER_UPDATE_TEXT, ICMS_IMANAGER_FOLDER_PATH);
			$abortUpdate = true;
		}
		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	/* 1.2 RC2 released */

	if (!$abortUpdate) $newDbVersion = 38;
	if ($dbVersion < $newDbVersion) {
		/* Change the system preference with textarea control to textsarea */
		$sql_extract_esc = 'UPDATE ' . icms::$xoopsDB->prefix('config') . ' SET `conf_formtype` = "textsarea"' . ' WHERE  `conf_modid` =0 AND `conf_formtype` = "textarea"';
		$icmsDatabaseUpdater->runQuery($sql_extract_esc, 'System Preferences textarea controls set to textsarea', true);

		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}

	/* 1.2.1 release */

	if (!$abortUpdate) $newDbVersion = 39;
	if ($dbVersion < $newDbVersion) {
		// retrieve config_id for purifier_HTML_Doctype
		$sql = "SELECT conf_id FROM " . icms::$xoopsDB->prefix('config') . " WHERE conf_name='purifier_HTML_Doctype'";
		$result = icms::$xoopsDB->query($sql);
		if (!$result) $abortUpdate = true;
		$myrow = icms::$xoopsDB->fetchArray($result);
		if (!isset($myrow['conf_id'])) $abortUpdate = true;
		$config_id = $myrow['conf_id'];

		$sql = "INSERT INTO " . icms::$xoopsDB->prefix('configoption') . " (confop_id, confop_name, confop_value, conf_id)" . " VALUES" . " (NULL, '_MD_AM_PURIFIER_401T', 'HTML 4.01 Transitional', {$config_id}), "
		. " (NULL, '_MD_AM_PURIFIER_401S', 'HTML 4.01 Strict', {$config_id}), "
		. " (NULL, '_MD_AM_PURIFIER_X10T', 'XHTML 1.0 Transitional', {$config_id}), "
		. " (NULL, '_MD_AM_PURIFIER_X10S', 'XHTML 1.0 Strict', {$config_id}), "
		. " (NULL, '_MD_AM_PURIFIER_X11', 'XHTML 1.1', {$config_id})";
		if (!icms::$xoopsDB->queryF($sql)) $abortUpdate = true;

	/* New config options and values for mail settings */
		$sql = 'UPDATE `' . icms::$xoopsDB->prefix( 'config') . '` SET `conf_order`=9 WHERE `conf_name`="sendmailpath"';
		$result = icms::$xoopsDB->query( $sql);
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_MAILER, 'smtpsecure', '_MD_AM_SMTPSECURE', 'ssl', '_MD_AM_SMTPSECUREDESC', 'select', 'text', 7);
		$config_id = icms::$xoopsDB->getInsertId();
		$sql = "INSERT INTO " . icms::$xoopsDB->prefix('configoption') . " (confop_id, confop_name, confop_value, conf_id)"
		. " VALUES" . " (NULL, 'None', 'none', {$config_id}), "
		. " (NULL, 'SSL', 'ssl', {$config_id}), "
		. " (NULL, 'TLS', 'tls', {$config_id})";
		if (!icms::$xoopsDB->queryF($sql)) $abortUpdate = true;
		$icmsDatabaseUpdater->insertConfig(ICMS_CONF_MAILER, 'smtpauthport', '_MD_AM_SMTPAUTHPORT', '465', '_MD_AM_SMTPAUTHPORTDESC', 'textbox', 'int', 8);

		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}
	/* 1.2.2 release */

	if (!$abortUpdate) $newDbVersion = 40;
	if ($dbVersion < $newDbVersion) {
		$file = ICMS_PLUGINS_PATH . '/csstidy/css_optimiser.php';
		if (file_exists($file)) {
			if(unlink($file)) {
				echo sprintf(_FILE_DELETED, $file) . '<br />';
			} else {
				icms_core_Message::error(sprintf(_CSSTIDY_VULN, $file));
			}
		}

		$icmsDatabaseUpdater->updateModuleDBVersion($newDbVersion, 'system');
		echo sprintf(_DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local($newDbVersion)) . '<br />';
	}
