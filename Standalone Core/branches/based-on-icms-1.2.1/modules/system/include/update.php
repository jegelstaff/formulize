<?php
/**
 * DataBase Update Functons
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.0
 * @author		malanciault <marcan@impresscms.org)
 * @version		$Id: update.php 9836 2010-02-07 13:59:22Z skenow $
 */

icms_loadLanguageFile ( 'core', 'databaseupdater' );

/**
 * Automatic update of the system module
 *
 * @param object $module reference to the module object
 * @param int $oldversion The old version of the database
 * @param int $dbVersion The database version
 * @return mixed
 */
function xoops_module_update_system(&$module, $oldversion = null, $dbVersion = null) {

	global $icmsConfig, $xoTheme;
	$icmsDB = $GLOBALS ['xoopsDB'];

	$from_112 = $abortUpdate = false;

	$oldversion = $module->getVar ( 'version' );
	if ($oldversion < 120) {
		$result = $icmsDB->query ( "SELECT t1.tpl_id FROM " . $icmsDB->prefix ( 'tplfile' ) . " t1, " . $icmsDB->prefix ( 'tplfile' ) . " t2 WHERE t1.tpl_module = t2.tpl_module AND t1.tpl_tplset=t2.tpl_tplset AND t1.tpl_file = t2.tpl_file AND t1.tpl_id > t2.tpl_id" );

		$tplids = array ( );
		while ( list ( $tplid ) = $icmsDB->fetchRow ( $result ) ) {
			$tplids [] = $tplid;
		}

		if (count ( $tplids ) > 0) {
			$tplfile_handler = & xoops_gethandler ( 'tplfile' );
			$duplicate_files = $tplfile_handler->getObjects ( new Criteria ( 'tpl_id', "(" . implode ( ',', $tplids ) . ")", "IN" ) );

			if (count ( $duplicate_files ) > 0) {
				foreach ( array_keys ( $duplicate_files ) as $i ) {
					$tplfile_handler->delete ( $duplicate_files [$i] );
				}
			}
		}
	}

	$icmsDatabaseUpdater = XoopsDatabaseFactory::getDatabaseUpdater ();
	//$dbVersion  = $module->getDBVersion();
	//$oldversion  = $module->getVar('version');

	ob_start ();

	$dbVersion = $module->getDBVersion ();
	echo sprintf ( _DATABASEUPDATER_CURRENTVER, icms_conv_nr2local ( $dbVersion ) ) . '<br />';
	echo "<code>" . sprintf ( _DATABASEUPDATER_UPDATE_TO, icms_conv_nr2local( ICMS_SYSTEM_DBVERSION ) ). "<br />";

	/**
	 * Migrate the db with new changes from 1.1 since 1.0
	 * Note: many of these changes have been implemented in the upgrade script, which is essential in 1.1 because
	 * of the new dbversion field we have added in the modules table. However, starting with release after 1.1, all
	 * upgrade scripts will be added here. Doing so, only the System module will need to be updated by webmaster.
	 */

	/**
	 * DEVELOPER, PLEASE NOTE !!!
	 *
	 * Everytime we add a new upgrade block here, the dbversion of the System Module will get
	 * incremented. It is very important to modify the ICMS_SYSTEM_DBVERSION accordingly
	 * in htdocs/include/version.php
	 */

	$CleanWritingFolders = false;

	$newDbVersion = 1;

	if ( $dbVersion <= $newDbVersion) {

		// Now, first, let's increment the conf_order of user option starting at new_user_notify
		$table = new IcmsDatabasetable ( 'config' );
		$criteria = new CriteriaCompo ( );
		$criteria->add ( new Criteria ( 'conf_order', 3, '>' ) );
		$table->addUpdateAll ( 'conf_order', 'conf_order + 2', $criteria, true );
		$icmsDatabaseUpdater->updateTable ( $table );
		unset ( $table );

		// create extended date function's config option
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF, 'use_ext_date', '_MD_AM_EXT_DATE', 0, '_MD_AM_EXT_DATEDSC', 'yesno', 'int', 12 );
		// create editors config option
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF, 'editor_default', '_MD_AM_EDITOR_DEFAULT', 'default', '_MD_AM_EDITOR_DEFAULT_DESC', 'editor', 'text', 16 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF, 'editor_enabled_list', '_MD_AM_EDITOR_ENABLED_LIST', ".serialize(array('default')).", '_MD_AM_EDITOR_ENABLED_LIST_DESC', 'editor_multi', 'array', 16 );
		// create captcha options
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF, 'use_captchaf', '_MD_AM_USECAPTCHAFORM', 1, '_MD_AM_USECAPTCHAFORMDSC', 'yesno', 'int', 37 );

		// create 4 new user config options
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_USER, 'use_captcha', '_MD_AM_USECAPTCHA', 1, '_MD_AM_USECAPTCHADSC', 'yesno', 'int', 3 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_USER, 'welcome_msg', '_MD_AM_WELCOMEMSG', 0, '_MD_AM_WELCOMEMSGDSC', 'yesno', 'int', 3 );

		// get the default content of the mail
		$default_msg_content_file = XOOPS_ROOT_PATH . '/language/' . $icmsConfig ['language'] . '/mail_template/' . 'welcome.tpl';
		if (! file_exists ( $default_msg_content_file )) {
			$default_msg_content_file = XOOPS_ROOT_PATH . '/language/english/mail_template/' . 'welcome.tpl';
		}
		$fp = fopen ( $default_msg_content_file, 'r' );
		if ($fp) {
			$default_msg_content = fread ( $fp, filesize ( $default_msg_content_file ) );
		}
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_USER, 'welcome_msg_content', '_MD_AM_WELCOMEMSG_CONTENT', $default_msg_content, '_MD_AM_WELCOMEMSG_CONTENTDSC', 'textarea', 'text', 3 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_USER, 'allwshow_sig', '_MD_AM_ALLWSHOWSIG', 1, '_MD_AM_ALLWSHOWSIGDSC', 'yesno', 'int', 4 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_USER, 'allow_htsig', '_MD_AM_ALLWHTSIG', 1, '_MD_AM_ALLWHTSIGDSC', 'yesno', 'int', 4 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_USER, 'sig_max_length', '_MD_AM_SIGMAXLENGTH', '255', '_MD_AM_SIGMAXLENGTHDSC', 'textbox', 'int', 4 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_USER, 'avatar_allow_gravatar', '_MD_AM_GRAVATARALLOW', '1', '_MD_AM_GRAVATARALWDSC', 'yesno', 'int', 15 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_USER, 'allow_annon_view_prof', '_MD_AM_ALLOW_ANONYMOUS_VIEW_PROFILE', '1', '_MD_AM_ALLOW_ANONYMOUS_VIEW_PROFILE_DESC', 'yesno', 'int', 36 );

		// Adding configurations of meta tag&footer
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_METAFOOTER, 'google_meta', '_MD_AM_METAGOOGLE', '', '_MD_AM_METAGOOGLE_DESC', 'textbox', 'text', 9 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_METAFOOTER, 'use_google_analytics', '_MD_AM_USE_GOOGLE_ANA', 0, '_MD_AM_USE_GOOGLE_ANA_DESC', 'yesno', 'int', 21 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_METAFOOTER, 'google_analytics', '_MD_AM_GOOGLE_ANA', '', '_MD_AM_GOOGLE_ANA_DESC', 'textbox', 'text', 21 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_METAFOOTER, 'footadm', '_MD_AM_FOOTADM', 'Powered by ImpressCMS &copy; 2007-' . date ( "Y", time () ) . ' <a href=\"http://www.impresscms.org/\" rel=\"external\">The ImpressCMS Project</a>', '_MD_AM_FOOTADM_DESC', 'textarea', 'text', 22 );

		// Adding configurations of search preferences
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_SEARCH, 'search_user_date', '_MD_AM_SEARCH_USERDATE', '1', '_MD_AM_SEARCH_USERDATE', 'yesno', 'int', 2 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_SEARCH, 'search_no_res_mod', '_MD_AM_SEARCH_NO_RES_MOD', '1', '_MD_AM_SEARCH_NO_RES_MODDSC', 'yesno', 'int', 3 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_SEARCH, 'search_per_page', '_MD_AM_SEARCH_PER_PAGE', '20', '_MD_AM_SEARCH_PER_PAGEDSC', 'textbox', 'int', 4 );

		// Adding new cofigurations added for multi language
		$icmsDatabaseUpdater->insertConfig ( IM_CONF_MULILANGUAGE, 'ml_autoselect_enabled', '_MD_AM_ML_AUTOSELECT_ENABLED', '0', '_MD_AM_ML_AUTOSELECT_ENABLED_DESC', 'yesno', 'int', 1 );

		// Adding new function of content manager
		$icmsDatabaseUpdater->insertConfig ( IM_CONF_CONTENT, 'default_page', '_MD_AM_DEFAULT_CONTPAGE', '0', '_MD_AM_DEFAULT_CONTPAGEDSC', 'select_pages', 'int', 1 );
		$icmsDatabaseUpdater->insertConfig ( IM_CONF_CONTENT, 'show_nav', '_MD_AM_CONT_SHOWNAV', '1', '_MD_AM_CONT_SHOWNAVDSC', 'yesno', 'int', 2 );
		$icmsDatabaseUpdater->insertConfig ( IM_CONF_CONTENT, 'show_subs', '_MD_AM_CONT_SHOWSUBS', '1', '_MD_AM_CONT_SHOWSUBSDSC', 'yesno', 'int', 3 );
		$icmsDatabaseUpdater->insertConfig ( IM_CONF_CONTENT, 'show_pinfo', '_MD_AM_CONT_SHOWPINFO', '1', '_MD_AM_CONT_SHOWPINFODSC', 'yesno', 'int', 4 );
		/*
		$default_login_content_file = XOOPS_ROOT_PATH . '/upgrade/language/' . $icmsConfig['language'] . '/' . 'login.tpl';
		if (!file_exists($default_login_content_file)) {
			$default_login_content_file = XOOPS_ROOT_PATH . '/upgrade/language/english/' . 'login.tpl';
		}
		$fp = fopen($default_login_content_file, 'r');
		if ($fp) {
			$default_login_content = fread($fp, filesize($default_login_content_file));
		}
*/
		// Adding new function of Personalization
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'adm_left_logo', '_MD_AM_LLOGOADM', '/uploads/img482278e29e81c.png', '_MD_AM_LLOGOADM_DESC', 'select_image', 'text', 1 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'adm_left_logo_url', '_MD_AM_LLOGOADM_URL', '' . XOOPS_URL . '/index.php', '_MD_AM_LLOGOADM_URL_DESC', 'textbox', 'text', 2 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'adm_left_logo_alt', '_MD_AM_LLOGOADM_ALT', 'ImpressCMS', '_MD_AM_LLOGOADM_ALT_DESC', 'textbox', 'text', 3 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'adm_right_logo', '_MD_AM_RLOGOADM', '', '_MD_AM_RLOGOADM_DESC', 'select_image', 'text', 4 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'adm_right_logo_url', '_MD_AM_RLOGOADM_URL', '', '_MD_AM_RLOGOADM_URL_DESC', 'textbox', 'text', 5 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'adm_right_logo_alt', '_MD_AM_RLOGOADM_ALT', '', '_MD_AM_RLOGOADM_ALT_DESC', 'textbox', 'text', 6 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'rss_local', '_MD_AM_RSSLOCAL', 'http://www.impresscms.org/modules/smartsection/backend.php', '_MD_AM_RSSLOCAL_DESC', 'textbox', 'text', 7 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'editre_block', '_MD_AM_EDITREMOVEBLOCK', '1', '_MD_AM_EDITREMOVEBLOCKDSC', 'yesno', 'int', 8 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'multi_login', '_MD_AM_MULTLOGINPREVENT', '0', '_MD_AM_MULTLOGINPREVENTDSC', 'yesno', 'int', 9 );
		//$icmsDatabaseUpdater->insertConfig(XOOPS_CONF_PERSONA, 'multi_login_msg', '_MD_AM_MULTLOGINMSG', $default_login_content, '_MD_AM_MULTLOGINMSG_DESC', 'textarea', 'text', 10);
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'email_protect', '_MD_AM_EMAILPROTECT', '0', '_MD_AM_EMAILPROTECTDSC', 'yesno', 'int', 11 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'email_font', '_MD_AM_EMAILTTF', 'arial.ttf', '_MD_AM_EMAILTTF_DESC', 'select_font', 'text', 12 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'email_font_len', '_MD_AM_EMAILLEN', '12', '_MD_AM_EMAILLEN_DESC', 'textbox', 'int', 13 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'email_cor', '_MD_AM_EMAILCOLOR', '#000000', '_MD_AM_EMAILCOLOR_DESC', 'color', 'text', 14 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'email_shadow', '_MD_AM_EMAILSHADOW', '#cccccc', '_MD_AM_EMAILSHADOW_DESC', 'color', 'text', 15 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'shadow_x', '_MD_AM_SHADOWX', '2', '_MD_AM_SHADOWX_DESC', 'textbox', 'int', 16 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'shadow_y', '_MD_AM_SHADOWY', '2', '_MD_AM_SHADOWY_DESC', 'textbox', 'int', 17 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'shorten_url', '_MD_AM_SHORTURL', '0', '_MD_AM_SHORTURLDSC', 'yesno', 'int', 18 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'max_url_long', '_MD_AM_URLLEN', '50', '_MD_AM_URLLEN_DESC', 'textbox', 'int', 19 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'pre_chars_left', '_MD_AM_PRECHARS', '35', '_MD_AM_PRECHARS_DESC', 'textbox', 'int', 20 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'last_chars_left', '_MD_AM_LASTCHARS', '10', '_MD_AM_LASTCHARS_DESC', 'textbox', 'int', 21 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'show_impresscms_menu', '_MD_AM_SHOW_ICMSMENU', '1', '_MD_AM_SHOW_ICMSMENU_DESC', 'yesno', 'int', 22 );
		// Adding new function of authentication
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_AUTH, 'auth_openid', '_MD_AM_AUTHOPENID', '0', '_MD_AM_AUTHOPENIDDSC', 'yesno', 'int', 1 );

		$table = new IcmsDatabasetable ( 'imagecategory' );
		$icmsDatabaseUpdater->runQuery ( 'INSERT INTO ' . $table->name () . ' (imgcat_id, imgcat_name, imgcat_maxsize, imgcat_maxwidth, imgcat_maxheight, imgcat_display, imgcat_weight, imgcat_type, imgcat_storetype) VALUES (NULL, "Logos", 350000, 350, 80, 1, 0, "C", "file")', 'Successfully created Logos imagecategory', 'Problems when try to create Logos imagecategory' );
		unset ( $table );
		$result = $icmsDB->query ( "SELECT imgcat_id FROM " . $icmsDB->prefix ( 'imagecategory' ) . " WHERE imgcat_name = 'Logos'" );
		list ( $categ_id ) = $icmsDB->fetchRow ( $result );
		$table = new IcmsDatabasetable ( 'image' );
		$icmsDatabaseUpdater->runQuery ( 'INSERT INTO ' . $table->name () . ' (image_id, image_name, image_nicename, image_mimetype, image_created, image_display, image_weight, imgcat_id) VALUES (NULL, "img482278e29e81c.png", "ImpressCMS", "image/png", ' . time () . ', 1, 0, ' . $categ_id . ')', 'Successfully added default ImpressCMS admin logo', 'Problems when try to add ImpressCMS admin logo' );
		unset ( $table );
		$table = new IcmsDatabasetable ( 'group_permission' );
		$icmsDatabaseUpdater->runQuery ( 'INSERT INTO ' . $table->name () . ' VALUES(0,1,' . $categ_id . ',1,"imgcat_write")', '', '' );
		$icmsDatabaseUpdater->runQuery ( 'INSERT INTO ' . $table->name () . ' VALUES(0,1,' . $categ_id . ',1,"imgcat_read")', '', '' );
		unset ( $table );
		$table = new IcmsDatabasetable ( 'block_module_link' );
		if (! $table->fieldExists ( 'page_id' )) {
			$table->addNewField ( 'page_id', "smallint(5) NOT NULL default '0'" );
		}
		if (! $icmsDatabaseUpdater->updateTable ( $table )) {
		/**
		 * @todo trap the errors
		 */
		}

		$icmsDatabaseUpdater->runQuery ( 'UPDATE ' . $table->name () . ' SET module_id=0, page_id=1 WHERE module_id=-1', 'Block Visibility Restructured Successfully', 'Failed in Restructure the Block Visibility' );

		unset ( $table );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	/**
	 * Changing $icmsConfigPersona['rss_local'] from www.impresscms.org to community.impresscms.org
	 */
	if( !$abortUpdate) $newDbVersion = 2;

	if ($dbVersion < $newDbVersion) {
		$configitem_handler = xoops_getHandler ( 'configitem' );
		// fetch the rss_local configitem
		$criteria = new CriteriaCompo ( );
		$criteria->add ( new Criteria ( 'conf_name', 'rss_local' ) );
		$criteria->add ( new Criteria ( 'conf_catid', XOOPS_CONF_PERSONA ) );
		$configitemsObj = $configitem_handler->getObjects ( $criteria );
		if (isset ( $configitemsObj [0] ) && $configitemsObj [0]->getVar ( 'conf_value', 'n' ) == 'http://www.impresscms.org/modules/smartsection/backend.php') {
			$configitemsObj [0]->setVar ( 'conf_value', 'http://community.impresscms.org/modules/smartsection/backend.php' );
			$configitem_handler->insert ( $configitemsObj [0] );
			echo "&nbsp;&nbsp;Updating rss_local config with correct info (if value was not previously changed by user)<br />";
		}
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	/**
	 * A few fields were added in the DB after 1.1 Beta 1. Those fields were added to the upgrade script from 1.0 to 1.1,
	 * but it may be a problem for people following each of our release
	 * Bug item #2098379 is about this
	 */
	if( !$abortUpdate) $newDbVersion = 3;

	if ($dbVersion < $newDbVersion) {
		$table = new IcmsDatabasetable ( 'users' );
		if (! $table->fieldExists ( 'openid' )) {
			$table->addNewField ( 'openid', "varchar(255) NOT NULL default ''" );
			$icmsDatabaseUpdater->updateTable ( $table );
		}
		unset ( $table );

		$table = new IcmsDatabasetable ( 'users' );
		if (! $table->fieldExists ( 'user_viewoid' )) {
			$table->addNewField ( 'user_viewoid', "tinyint(1) UNSIGNED NOT NULL default 0" );
			$icmsDatabaseUpdater->updateTable ( $table );
		}
		unset ( $table );

		$table = new IcmsDatabasetable ( 'users' );
		if (! $table->fieldExists ( 'pass_expired' )) {
			$table->addNewField ( 'pass_expired', "tinyint(1) UNSIGNED NOT NULL default 0" );
			$icmsDatabaseUpdater->updateTable ( $table );
		}
		unset ( $table );

		$table = new IcmsDatabasetable ( 'users' );
		if (! $table->fieldExists ( 'enc_type' )) {
			$table->addNewField ( 'enc_type', "tinyint(2) UNSIGNED NOT NULL default 0" );
			$icmsDatabaseUpdater->updateTable ( $table );
		}
		unset ( $table );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 4;

	if ($dbVersion < $newDbVersion) {

		$table = new IcmsDatabasetable ( 'users' );
		if ($table->fieldExists ( 'pass' )) {
			$table->alterTable ( 'pass', 'pass', "varchar(255) NOT NULL default ''" );
			$icmsDatabaseUpdater->updateTable ( $table );
		}
		unset ( $table );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 5;

	if ($dbVersion < $newDbVersion) {
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'use_jsjalali', '_MD_AM_JALALICAL', '0', '_MD_AM_JALALICALDSC', 'yesno', 'int', 23 );
		unset ( $table );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	//Some users had used a copy of working branch and they got multiple option, this is to remove all those re-created options and make a single option
	if( !$abortUpdate) $newDbVersion = 6;

	if ($dbVersion < $newDbVersion) {
		$icmsDB->queryF ( "DELETE FROM `" . $icmsDB->prefix ( 'config' ) . "` WHERE conf_name='use_jsjalali'" );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'use_jsjalali', '_MD_AM_JALALICAL', '0', '_MD_AM_JALALICALDSC', 'yesno', 'int', 23 );
		unset ( $table );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 7;

	if ($dbVersion < $newDbVersion) {
		$configitem_handler = xoops_getHandler ( 'configitem' );
		// fetch the rss_local configitem
		$criteria = new CriteriaCompo ( );
		$criteria->add ( new Criteria ( 'conf_name', 'google_analytics' ) );
		$criteria->add ( new Criteria ( 'conf_catid', XOOPS_CONF_METAFOOTER ) );
		$configitemsObj = $configitem_handler->getObjects ( $criteria );
		if (isset ( $configitemsObj [0] ) && $configitemsObj [0]->getVar ( 'conf_formtype', 'n' ) == 'textarea') {
			$configitemsObj [0]->setVar ( 'conf_formtype', 'textbox' );
			$configitem_handler->insert ( $configitemsObj [0] );
			echo "&nbsp;&nbsp;Updating google_analytics field type<br />";
		}
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 8;

	if ($dbVersion < $newDbVersion) {

		$table = new IcmsDatabasetable ( 'modules' );
		if ($table->fieldExists ( 'dbversion' )) {
			$icmsDatabaseUpdater->runQuery ( "ALTER TABLE `" . $table->name () . "` MODIFY dbversion INT(11) unsigned NOT NULL DEFAULT 1", 'Successfully modified field dbversion in table modules', '' );
		}
		$icmsDatabaseUpdater->runQuery ( "ALTER TABLE `" . $table->name () . "` MODIFY version smallint(5) unsigned NOT NULL default '102'", 'Successfully modified field version in table modules', '' );
		unset ( $table );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 9;

	if ($dbVersion < $newDbVersion) {
		$table = new IcmsDatabasetable ( 'users' );
		$icmsDatabaseUpdater->runQuery ( "ALTER TABLE `" . $table->name () . "` DROP INDEX unamepass, ADD INDEX unamepass (uname (10), pass (10))", 'Successfully altered the index unamepass on table users', '' );
		$icmsDatabaseUpdater->runQuery ( "ALTER TABLE `" . $table->name () . "` MODIFY pass_expired tinyint(1) unsigned NOT NULL default 0", 'Successfully altered field pass_expired in table users', '' );
		unset ( $table );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 10;
	if ($dbVersion < $newDbVersion) {

		if (getDbValue ( $icmsDB, 'newblocks', 'show_func', 'show_func="b_social_bookmarks"' ) == FALSE ) {
			$sql = "SELECT bid FROM `" . $icmsDB->prefix ( 'newblocks' ) . "` WHERE show_func='b_social_bookmarks'";
			$result = $icmsDB->query ( $sql );
			list ( $new_block_id ) = $icmsDB->FetchRow ( $result );
			$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "block_module_link" ) . " VALUES (" . $new_block_id . ", 0, 1);" );
			$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "group_permission" ) . " VALUES ('', 3, " . $new_block_id . ", 1, 'block_read');" );
		}

		if (getDbValue ( $icmsDB, 'newblocks', 'show_func', 'show_func="b_content_show"' ) == FALSE ) {
			$sql = "SELECT bid FROM `" . $icmsDB->prefix ( 'newblocks' ) . "` WHERE show_func='b_content_show'";
			$result = $icmsDB->query ( $sql );
			list ( $new_block_id ) = $icmsDB->FetchRow ( $result );
			$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "block_module_link" ) . " VALUES (" . $new_block_id . ", 0, 0);" );
			$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "group_permission" ) . " VALUES ('', 3, " . $new_block_id . ", 1, 'block_read');" );
		}

		if (getDbValue ( $icmsDB, 'newblocks', 'show_func', 'show_func="b_content_menu_show"' ) == FALSE ) {
			$sql = "SELECT bid FROM `" . $icmsDB->prefix ( 'newblocks' ) . "` WHERE show_func='b_content_menu_show'";
			$result = $icmsDB->query ( $sql );
			list ( $new_block_id ) = $icmsDB->FetchRow ( $result );
			$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "block_module_link" ) . " VALUES (" . $new_block_id . ", 0, 0);" );
			$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "group_permission" ) . " VALUES ('', 3, " . $new_block_id . ", 1, 'block_read');" );
		}

		if (getDbValue ( $icmsDB, 'newblocks', 'show_func', 'show_func="b_content_relmenu_show"' ) == FALSE ) {
			$sql = "SELECT bid FROM `" . $icmsDB->prefix ( 'newblocks' ) . "` WHERE show_func='b_content_relmenu_show'";
			$result = $icmsDB->query ( $sql );
			list ( $new_block_id ) = $icmsDB->FetchRow ( $result );
			$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "block_module_link" ) . " VALUES (" . $new_block_id . ", 0, 0);" );
			$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "group_permission" ) . " VALUES ('', 3, " . $new_block_id . ", 1, 'block_read');" );
		}
		$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "group_permission" ) . " VALUES ('', 1, 16, 1, 'system_admin');" );
		$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "group_permission" ) . " VALUES ('', 1, 17, 1, 'system_admin');" );
		$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "group_permission" ) . " VALUES ('', 1, 18, 1, 'system_admin');" );
		$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "group_permission" ) . " VALUES ('', 1, 19, 1, 'system_admin');" );
		$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "group_permission" ) . " VALUES ('', 1, 20, 1, 'system_admin');" );
		$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "group_permission" ) . " VALUES ('', 1, 1, 1, 'content_admin');" );
		$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "group_permission" ) . " VALUES ('', 1, 2, 1, 'group_manager');" );
		$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "group_permission" ) . " VALUES ('', 1, 3, 1, 'group_manager');" );

		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';

	}

	if( !$abortUpdate) $newDbVersion = 11;

	if ($dbVersion < $newDbVersion) {
		$icmsDatabaseUpdater->db->queryF ( "UPDATE `" . $icmsDatabaseUpdater->db->prefix ( 'config' ) . "` SET conf_formtype = 'textsarea', conf_valuetype = 'array' WHERE conf_name = 'bad_unames'" );
		$icmsDatabaseUpdater->db->queryF ( "UPDATE `" . $icmsDatabaseUpdater->db->prefix ( 'config' ) . "` SET conf_formtype = 'textsarea', conf_valuetype = 'array' WHERE conf_name = 'bad_emails'" );
		$icmsDatabaseUpdater->db->queryF ( "UPDATE `" . $icmsDatabaseUpdater->db->prefix ( 'config' ) . "` SET conf_formtype = 'textsarea', conf_valuetype = 'text' WHERE conf_name = 'meta_keywords'" );
		$icmsDatabaseUpdater->db->queryF ( "UPDATE `" . $icmsDatabaseUpdater->db->prefix ( 'config' ) . "` SET conf_formtype = 'textsarea', conf_valuetype = 'text' WHERE conf_name = 'meta_description'" );
		$icmsDatabaseUpdater->db->queryF ( "UPDATE `" . $icmsDatabaseUpdater->db->prefix ( 'config' ) . "` SET conf_formtype = 'textsarea', conf_valuetype = 'array' WHERE conf_name = 'censor_words'" );
		$icmsDatabaseUpdater->db->queryF ( "UPDATE `" . $icmsDatabaseUpdater->db->prefix ( 'config' ) . "` SET conf_formtype = 'textsarea', conf_valuetype = 'array' WHERE conf_name = 'ldap_users_bypass'" );
		$icmsDatabaseUpdater->db->queryF ( "UPDATE `" . $icmsDatabaseUpdater->db->prefix ( 'config' ) . "` SET conf_formtype = 'textsarea', conf_valuetype = 'text' WHERE conf_name = 'ldap_field_mapping'" );
		$icmsDatabaseUpdater->db->queryF ( "UPDATE `" . $icmsDatabaseUpdater->db->prefix ( 'config' ) . "` SET conf_formtype = 'textsarea', conf_valuetype = 'text' WHERE conf_name = 'reg_disclaimer'" );
		$icmsDatabaseUpdater->db->queryF ( "UPDATE `" . $icmsDatabaseUpdater->db->prefix ( 'config' ) . "` SET conf_formtype = 'textsarea', conf_valuetype = 'array' WHERE conf_name = 'bad_ips'" );
		$icmsDatabaseUpdater->db->queryF ( "UPDATE `" . $icmsDatabaseUpdater->db->prefix ( 'config' ) . "` SET conf_formtype = 'textsarea', conf_valuetype = 'array' WHERE conf_name = 'smtphost'" );
		$icmsDatabaseUpdater->db->queryF ( "UPDATE `" . $icmsDatabaseUpdater->db->prefix ( 'config' ) . "` SET conf_formtype = 'textsarea', conf_valuetype = 'text' WHERE conf_name = 'multi_login_msg'" );

		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}
	/* 1.1.2 released */

	if( !$abortUpdate) $newDbVersion = 12;

	if ($dbVersion < $newDbVersion) {
		$from_112 = true;
		if (getDbValue ( $icmsDB, 'configcategory', 'confcat_name', 'confcat_name="_MD_AM_CAPTCHA"' ) == FALSE ) {
			$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "configcategory" ) . " (confcat_id,confcat_name) VALUES ('11','_MD_AM_CAPTCHA')" );
		}
		$icmsDB->queryF ( "DELETE FROM `" . $icmsDB->prefix ( 'config' ) . "` WHERE (conf_modid='1' AND conf_catid='11')" );
		// Adding new function of Captcha
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_CAPTCHA, 'captcha_mode', '_MD_AM_CAPTCHA_MODE', 'image', '_MD_AM_CAPTCHA_MODEDSC', 'select', 'text', 1 );
		$config_id = $icmsDB->getInsertId ();

		$sql = "INSERT INTO " . $icmsDB->prefix ( 'configoption' ) . " (confop_id, confop_name, confop_value, conf_id)" . " VALUES" . " (NULL, '_MD_AM_CAPTCHA_OFF', 'none', {$config_id})," . " (NULL, '_MD_AM_CAPTCHA_IMG', 'image', {$config_id})," . " (NULL, '_MD_AM_CAPTCHA_TXT', 'text', {$config_id})";
		if (! $icmsDB->queryF ( $sql )) {
			return false;
		}
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_CAPTCHA, 'captcha_skipmember', '_MD_AM_CAPTCHA_SKIPMEMBER', serialize(array('2')), '_MD_AM_CAPTCHA_SKIPMEMBERDSC', 'group_multi', 'array', 2 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_CAPTCHA, 'captcha_casesensitive', '_MD_AM_CAPTCHA_CASESENS', '0', '_MD_AM_CAPTCHA_CASESENSDSC', 'yesno', 'int', 3 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_CAPTCHA, 'captcha_skip_characters', '_MD_AM_CAPTCHA_SKIPCHAR', serialize(array('o', '0', 'i', 'l', '1')), '_MD_AM_CAPTCHA_SKIPCHARDSC', 'textarea', 'array', 4 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_CAPTCHA, 'captcha_maxattempt', '_MD_AM_CAPTCHA_MAXATTEMP', '8', '_MD_AM_CAPTCHA_MAXATTEMPDSC', 'textbox', 'int', 5 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_CAPTCHA, 'captcha_num_chars', '_MD_AM_CAPTCHA_NUMCHARS', '4', '_MD_AM_CAPTCHA_NUMCHARSDSC', 'textbox', 'int', 6 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_CAPTCHA, 'captcha_fontsize_min', '_MD_AM_CAPTCHA_FONTMIN', '10', '_MD_AM_CAPTCHA_FONTMINDSC', 'textbox', 'int', 7 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_CAPTCHA, 'captcha_fontsize_max', '_MD_AM_CAPTCHA_FONTMAX', '12', '_MD_AM_CAPTCHA_FONTMAXDSC', 'textbox', 'int', 8 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_CAPTCHA, 'captcha_background_type', '_MD_AM_CAPTCHA_BGTYPE', '100', '_MD_AM_CAPTCHA_BGTYPEDSC', 'select', 'text', 9 );
		$config_id = $icmsDB->getInsertId ();

		$sql2 = "INSERT INTO " . $icmsDB->prefix ( 'configoption' ) . " (confop_id, confop_name, confop_value, conf_id)" . " VALUES" . " (NULL, '_MD_AM_BAR', '0', {$config_id})," . " (NULL, '_MD_AM_CIRCLE', '1', {$config_id})," . " (NULL, '_MD_AM_LINE', '2', {$config_id})," . " (NULL, '_MD_AM_RECTANGLE', '3', {$config_id})," . " (NULL, '_MD_AM_ELLIPSE', '4', {$config_id})," . " (NULL, '_MD_AM_POLYGON', '5', {$config_id})," . " (NULL, '_MD_AM_RANDOM', '100', {$config_id})";
		if (! $icmsDB->queryF ( $sql2 )) {
			return false;
		}
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_CAPTCHA, 'captcha_background_num', '_MD_AM_CAPTCHA_BGNUM', '50', '_MD_AM_CAPTCHA_BGNUMDSC', 'textbox', 'int', 10 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_CAPTCHA, 'captcha_polygon_point', '_MD_AM_CAPTCHA_POLPNT', '3', '_MD_AM_CAPTCHA_POLPNTDSC', 'textbox', 'int', 11 );

		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 13;

	if ($dbVersion < $newDbVersion) {
		//echo sprintf ( _CO_ICMS_UPDATE_DBVERSION, icms_conv_nr2local ( $newDbVersion ) );

		$icmsDB->queryF ( "UPDATE `" . $icmsDB->prefix ( 'config' ) . "` SET conf_formtype = 'textsarea', conf_valuetype = 'text' WHERE conf_name = 'reg_disclaimer'" );

		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 14;

	if ($dbVersion < $newDbVersion) {
		if (is_writable ( ICMS_PLUGINS_PATH ) || (is_dir(ICMS_ROOT_PATH . '/plugins/preloads') && is_writable ( ICMS_ROOT_PATH . '/plugins/preloads' ))) {
			if (is_dir ( ICMS_ROOT_PATH . '/preload' )) {
				if( icms_copyr ( ICMS_ROOT_PATH . '/preload', ICMS_ROOT_PATH . '/plugins/preloads' ) ) {
					icms_unlinkRecursive ( ICMS_ROOT_PATH . '/preload' );
				} else {
					$newDbVersion = 13;
					echo '<br />'.sprintf(_MD_AM_PLUGINSFOLDER_UPDATE_TEXT, ICMS_PLUGINS_PATH,ICMS_ROOT_PATH . '/plugins/preloads');
					$abortUpdate = true;
				}
			}
		} else {
			$newDbVersion = 13;
			echo '<br />'.sprintf(_MD_AM_PLUGINSFOLDER_UPDATE_TEXT, ICMS_PLUGINS_PATH,ICMS_ROOT_PATH . '/plugins/preloads');
			$abortUpdate = true;
		}
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 15;

	if ($dbVersion < $newDbVersion) {
		$table = new IcmsDatabasetable ( 'users' );
		if (! $table->fieldExists ( 'login_name' )) {
			$table->addNewField ( 'login_name', "varchar(255) NOT NULL default ''" );
			$icmsDatabaseUpdater->updateTable ( $table );
			$icmsDB->queryF ( "UPDATE `" . $icmsDB->prefix ( "users" ) . "` SET login_name=uname" );
			$icmsDatabaseUpdater->runQuery ( "ALTER TABLE `" . $table->name () . "` ADD INDEX login_name (login_name)", 'Successfully altered the index login_name on table users', '' );
		}
		unset ( $table );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 16;

	if ($dbVersion < $newDbVersion) {
		$sql = "SELECT conf_id FROM `" . $icmsDB->prefix ( 'config' ) . "` WHERE conf_name = 'email_protect'";
		$result = $icmsDB->query ( $sql );
		list ( $conf_id ) = $icmsDB->FetchRow ( $result );
		$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "configoption" ) . " VALUES ( NULL, '_MD_AM_NOMAILPROTECT', '0', " . $conf_id . ");" );
		$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "configoption" ) . " VALUES ( NULL, '_MD_AM_GDMAILPROTECT', '1', " . $conf_id . ");" );
		$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "configoption" ) . " VALUES ( NULL, '_MD_AM_REMAILPROTECT', '2', " . $conf_id . ");" );
		$icmsDB->queryF ( "UPDATE `" . $icmsDB->prefix ( 'config' ) . "` SET conf_formtype = 'select', conf_valuetype = 'text' WHERE conf_name = 'email_protect'" );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'recprvkey', '_MD_AM_RECPRVKEY', '', '_MD_AM_RECPRVKEY_DESC', 'textbox', 'text', 17 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'recpubkey', '_MD_AM_RECPUBKEY', '', '_MD_AM_RECPUBKEY_DESC', 'textbox', 'text', 17 );

		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 17;

	if ($dbVersion < $newDbVersion) {
		//$icmsDatabaseUpdater->insertConfig(XOOPS_CONF_USER, 'delusers', '_MD_AM_DELUSRES', '90', '_MD_AM_DELUSRESDSC', 'textbox', 'int', 3);
		if (getDbValue ( $icmsDB, 'configcategory', 'confcat_name', 'confcat_name="_MD_AM_PLUGINS"' ) == FALSE ) {
			$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "configcategory" ) . " (confcat_id,confcat_name) VALUES ('12','_MD_AM_PLUGINS')" );
		}
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PLUGINS, 'sanitizer_plugins', '_MD_AM_SELECTSPLUGINS',  serialize ( array ('' ) ), '_MD_AM_SELECTSPLUGINS_DESC', 'select_plugin', 'array', 1 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PLUGINS, 'code_sanitizer', '_MD_AM_SELECTSHIGHLIGHT', 'none', '_MD_AM_SELECTSHIGHLIGHT_DESC', 'select', 'text', 2 );
		$config_id = $icmsDB->getInsertId ();
		$sql = "INSERT INTO " . $icmsDB->prefix ( 'configoption' ) . " (confop_id, confop_name, confop_value, conf_id)" . " VALUES" . " (NULL, '_MD_AM_HIGHLIGHTER_OFF', 'none', {$config_id})," . " (NULL, '_MD_AM_HIGHLIGHTER_PHP', 'php', {$config_id})," . " (NULL, '_MD_AM_HIGHLIGHTER_GESHI', 'geshi', {$config_id})";
		if (! $icmsDB->queryF ( $sql )) {
			return false;
		}
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PLUGINS, 'geshi_default', '_MD_AM_GESHI_DEFAULT', 'php', '_MD_AM_GESHI_DEFAULT_DESC', 'select_geshi', 'text', 3 );
		$icmsDB->queryF ( "UPDATE `" . $icmsDB->prefix ( 'config' ) . "` SET conf_valuetype = 'array' WHERE conf_name = 'startpage'" );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_USER, 'delusers', '_MD_AM_DELUSRES', '30', '_MD_AM_DELUSRESDSC', 'textbox', 'int', 6 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_USER, 'allow_chguname', '_MD_AM_ALLWCHGUNAME', '0', '_MD_AM_ALLWCHGUNAMEDSC', 'yesno', 'int', 11 );
		$icmsDatabaseUpdater->insertConfig ( IM_CONF_CONTENT, 'num_pages', '_MD_AM_CONT_NUMPAGES', '10', '_MD_AM_CONT_NUMPAGESDSC', 'textbox', 'int', 5 );
		$icmsDatabaseUpdater->insertConfig ( IM_CONF_CONTENT, 'teaser_length', '_MD_AM_CONT_TEASERLENGTH', '500', '_MD_AM_CONT_TEASERLENGTHDSC', 'textbox', 'int', 6 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'pagstyle', '_MD_AM_PAGISTYLE', 'default', '_MD_AM_PAGISTYLE_DESC', 'select_paginati', 'text', 24 );

		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 18;
	/* errors discovered after 1.2 beta release (dbversion 31) moved to dbversion 32 */
	if ($dbVersion < $newDbVersion) {

		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
	    echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 19;

	if ($dbVersion < $newDbVersion) {
		$module_handler = xoops_gethandler ( 'module' );
		$smartprofile_module = $module_handler->getByDirname ( 'smartprofile' );
		$table = new IcmsDatabasetable ( 'profile_category' );
		if ($smartprofile_module && $smartprofile_module->getVar ( 'isactive' ) && ! $table->exists ()) {
			$icmsDB->queryF ( "RENAME TABLE `" . $icmsDB->prefix ( "smartprofile_category" ) . "` TO `" . $icmsDB->prefix ( "profile_category" ) . "`" );
			$icmsDB->queryF ( "RENAME TABLE `" . $icmsDB->prefix ( "smartprofile_field" ) . "` TO `" . $icmsDB->prefix ( "profile_field" ) . "`" );
			$icmsDB->queryF ( "RENAME TABLE `" . $icmsDB->prefix ( "smartprofile_visibility" ) . "` TO `" . $icmsDB->prefix ( "profile_visibility" ) . "`" );
			$icmsDB->queryF ( "RENAME TABLE `" . $icmsDB->prefix ( "smartprofile_profile" ) . "` TO `" . $icmsDB->prefix ( "profile_profile" ) . "`" );
			$icmsDB->queryF ( "RENAME TABLE `" . $icmsDB->prefix ( "smartprofile_regstep" ) . "` TO `" . $icmsDB->prefix ( "profile_regstep" ) . "`" );
			$command = array ("ALTER TABLE `" . $icmsDB->prefix ( "profile_profile" ) . "` ADD `newemail` varchar(255) NOT NULL default '' AFTER `profile_id`", "ALTER TABLE `" . $icmsDB->prefix ( "profile_field" ) . "` ADD `exportable` int unsigned NOT NULL default 0 AFTER `step_id`", "UPDATE `" . $icmsDB->prefix ( 'modules' ) . "` SET dirname='profile' WHERE dirname='smartprofile'" );

			foreach ( $command as $sql ) {
				if (! $result = $icmsDB->queryF ( $sql )) {
					icms_debug ( 'An error occurred while executing "' . $sql . '" - ' . $icmsDB->error () );
					return false;
				}
			}
		}
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 20;

	if ($dbVersion < $newDbVersion) {
		// Adding configurations of search preferences
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_SEARCH, 'enable_deep_search', '_MD_AM_DODEEPSEARCH', '1', '_MD_AM_DODEEPSEARCHDSC', 'yesno', 'int', 2 );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_SEARCH, 'num_shallow_search', '_MD_AM_NUMINITSRCHRSLTS', '5', '_MD_AM_NUMINITSRCHRSLTSDSC', 'textbox', 'int', 4 );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 21;

	if ($dbVersion < $newDbVersion) {
		// create extended date function's config option
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF, 'theme_admin_set', '_MD_AM_ADMIN_DTHEME', 'iTheme', '_MD_AM_ADMIN_DTHEME_DESC', 'theme_admin', 'other', 12 );

		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 22;

	if ($dbVersion < $newDbVersion) {
		$icmsDB->queryF ( "DELETE FROM `" . $icmsDB->prefix ( 'modules' ) . "` WHERE dirname='waiting'" );
		$icmsDB->queryF ( "DELETE FROM `" . $icmsDB->prefix ( 'newblocks' ) . "` WHERE dirname='waiting'" );
		$icmsDB->queryF ( "DELETE FROM `" . $icmsDB->prefix ( 'tplfile' ) . "` WHERE tpl_module='waiting'" );

		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	/*	if( !$abortUpdate) $newDbVersion =  23;

	if($dbVersion < $newDbVersion) {
		echo $action;
		$icmsDB->queryF("DELETE FROM `" . $icmsDB->prefix('config') . "` WHERE conf_name='pass_level'");
		$icmsDatabaseUpdater->insertConfig(XOOPS_CONF_USER, 'pass_level', '_MD_AM_PASSLEVEL', '1', '_MD_AM_PASSLEVEL_DESC', 'yesno', 'int', 2);
	}
*/

	if( !$abortUpdate) $newDbVersion = 24;

	if ($dbVersion < $newDbVersion) {
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_PERSONA, 'use_custom_redirection', '_MD_AM_CUSTOMRED', '0', '_MD_AM_CUSTOMREDDSC', 'yesno', 'int', 9 );

		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 25;

	if ($dbVersion < $newDbVersion) {
		$table = new IcmsDatabasetable ( 'icmscontent' );
		if (! $table->fieldExists ( 'content_seo_description' )) {
			$table->addNewField ( 'content_seo_description', "text" );
			$icmsDatabaseUpdater->updateTable ( $table );
		}
		unset ( $table );

		$table = new IcmsDatabasetable ( 'icmscontent' );
		if (! $table->fieldExists ( 'content_seo_keywords' )) {
			$table->addNewField ( 'content_seo_keywords', "text" );
			$icmsDatabaseUpdater->updateTable ( $table );
		}
		unset ( $table );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 26;

	if ($dbVersion < $newDbVersion) {
		$table = new IcmsDatabasetable ( 'system_mimetype' );
		if (! $table->exists ()) {
			$table->setStructure ( "mimetypeid int(11) NOT NULL auto_increment,
					extension varchar(60) NOT NULL default '',
					types text NOT NULL,
					name varchar(255) NOT NULL default '',
					dirname VARCHAR(255) NOT NULL,
					KEY mimetypeid (mimetypeid)
					" );
			$table->createTable ();
		}
		$icmsDB->queryFromFile ( ICMS_ROOT_PATH . "/modules/" . $module->getVar ( 'dirname', 'n' ) . "/include/upgrade.sql" );
		unset ( $table );

		$table = new IcmsDatabasetable ( 'system_adsense' );
		if (! $table->exists ()) {
			$table->setStructure ( "adsenseid int(11) NOT NULL auto_increment,
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
					" );
			$table->createTable ();
		}
		unset ( $table );

		$table = new IcmsDatabasetable ( 'system_rating' );
		if (! $table->exists ()) {
			$table->setStructure ( "ratingid int(11) NOT NULL auto_increment,
					dirname VARCHAR(255) NOT NULL,
					item VARCHAR(255) NOT NULL,
					itemid int(11) NOT NULL,
					uid int(11) NOT NULL,
					rate int(1) NOT NULL,
					date int(11) NOT NULL,
					PRIMARY KEY  (`ratingid`)
					" );
			$table->createTable ();
		}
		unset ( $table );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 27;

	if ($dbVersion < $newDbVersion) {
		$handler = icms_getModulehandler ( 'userrank', 'system' );
		$handler->MoveAllRanksImagesToProperPath ();
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 28;

	if ($dbVersion < $newDbVersion) {
		$table = new IcmsDatabasetable ( 'system_autotasks' );
		if (! $table->exists ()) {
			$table->setStructure ( "sat_id int(10) unsigned NOT NULL AUTO_INCREMENT,
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
					" );
			$table->createTable ();
		}
		unset ( $table );

		if (getDbValue ( $icmsDB, 'configcategory', 'confcat_name', 'confcat_name="_MD_AM_AUTOTASKS"' ) == FALSE ) {
			$icmsDB->queryF ( " INSERT INTO " . $icmsDB->prefix ( "configcategory" ) . " (confcat_id,confcat_name) VALUES (13, '_MD_AM_AUTOTASKS')" );
		}

		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF, 'sourceeditor_default', '_MD_AM_SRCEDITOR_DEFAULT', 'editarea', '_MD_AM_SRCEDITOR_DEFAULT_DESC', 'editor_source', 'text', 16 );
		$icmsDatabaseUpdater->insertConfig ( IM_CONF_AUTOTASKS, 'autotasks_system', '_MD_AM_AUTOTASKS_SYSTEM', 'internal', '_MD_AM_AUTOTASKS_SYSTEMDSC', 'autotasksystem', 'text', 1 );
		$icmsDatabaseUpdater->insertConfig ( IM_CONF_AUTOTASKS, 'autotasks_helper', '_MD_AM_AUTOTASKS_HELPER', 'wget %url%', '_MD_AM_AUTOTASKS_HELPERDSC', 'select', 'text', 2 );
		$config_id = $icmsDB->getInsertId ();
		$sql = "INSERT INTO " . $icmsDB->prefix ( 'configoption' ) . " (confop_id, confop_name, confop_value, conf_id)" . " VALUES" . " (NULL, 'PHP-CGI', 'php -f %path%', {$config_id})," . " (NULL, 'wget', 'wget %url%', {$config_id})," . " (NULL, 'Lynx', 'lynx --dump %url%', {$config_id})";
		if (! $icmsDB->queryF ( $sql )) {
			return false;
		}
		$icmsDatabaseUpdater->insertConfig ( IM_CONF_AUTOTASKS, 'autotasks_helper_path', '_MD_AM_AUTOTASKS_HELPER_PATH', '/usr/bin/', '_MD_AM_AUTOTASKS_HELPER_PATHDSC', 'text', 'text', 3 );
		$icmsDatabaseUpdater->insertConfig ( IM_CONF_AUTOTASKS, 'autotasks_user', '_MD_AM_AUTOTASKS_USER', '', '_MD_AM_AUTOTASKS_USERDSC', 'text', 'text', 4 );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 29;

	if ($dbVersion < $newDbVersion) {
		if (getDbValue ( $icmsDB, 'configcategory', 'confcat_name', 'confcat_name="_MD_AM_PURIFIER"' ) == FALSE ) {
			$icmsDB->queryF ( "INSERT INTO " . $icmsDB->prefix ( 'configcategory' ) . " (confcat_id,confcat_name) VALUES ('14', '_MD_AM_PURIFIER')" );
		}

		$table = new IcmsDatabasetable ( 'config' );
		$icmsDatabaseUpdater->runQuery ( "ALTER TABLE `" . $table->name () . "` MODIFY conf_name VARCHAR(75) NOT NULL default ''", 'Successfully altered field conf_name in config', '' );
		unset ( $table );

		include_once ICMS_ROOT_PATH . '/include/functions.php';
		$host_domain = icms_get_base_domain ( ICMS_URL );
		$host_base = icms_get_url_domain ( ICMS_URL );

		// Allowed Elements in HTML
		$HTML_Allowed_Elms = array ('a', 'abbr', 'acronym', 'b', 'blockquote', 'br', 'caption', 'cite', 'code', 'dd', 'del', 'dfn', 'div', 'dl', 'dt', 'em', 'font', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'i', 'img', 'ins', 'kbd', 'li', 'ol', 'p', 'pre', 's', 'span', 'strike', 'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'tt', 'u', 'ul', 'var' );

		// Allowed Element Attributes in HTML', 'element must also be allowed in Allowed Elements for these attributes to work.
		$HTML_Allowed_Attr = array ('a.class', 'a.href', 'a.id', 'a.name', 'a.rev', 'a.style', 'a.title', 'a.target', 'a.rel', 'abbr.title', 'acronym.title', 'blockquote.cite', 'div.align', 'div.style', 'div.class', 'div.id', 'font.size', 'font.color', 'h1.style', 'h2.style', 'h3.style', 'h4.style', 'h5.style', 'h6.style', 'img.src', 'img.alt', 'img.title', 'img.class', 'img.align', 'img.style', 'img.height', 'img.width', 'li.style', 'ol.style', 'p.style', 'span.style', 'span.class', 'span.id', 'table.class', 'table.id', 'table.border', 'table.cellpadding', 'table.cellspacing', 'table.style', 'table.width', 'td.abbr', 'td.align', 'td.class', 'td.id', 'td.colspan', 'td.rowspan', 'td.style', 'td.valign', 'tr.align', 'tr.class', 'tr.id', 'tr.style', 'tr.valign', 'th.abbr', 'th.align', 'th.class', 'th.id', 'th.colspan', 'th.rowspan', 'th.style', 'th.valign', 'ul.style' );

		$p = 0;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'enable_purifier', '_MD_AM_PURIFIER_ENABLE', '1', '_MD_AM_PURIFIER_ENABLEDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_URI_DefinitionID', '_MD_AM_PURIFIER_URI_DEFID', 'system', '_MD_AM_PURIFIER_URI_DEFIDDSC', 'textbox', 'text', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_URI_DefinitionRev', '_MD_AM_PURIFIER_URI_DEFREV', '1', '_MD_AM_PURIFIER_URI_DEFREVDSC', 'textbox', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_URI_Host', '_MD_AM_PURIFIER_URI_HOST', addslashes ( $host_domain ), '_MD_AM_PURIFIER_URI_HOSTDSC', 'textbox', 'text', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_URI_Base', '_MD_AM_PURIFIER_URI_BASE', addslashes ( $host_base ), '_MD_AM_PURIFIER_URI_BASEDSC', 'textbox', 'text', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_URI_Disable', '_MD_AM_PURIFIER_URI_DISABLE', '0', '_MD_AM_PURIFIER_URI_DISABLEDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_URI_DisableExternal', '_MD_AM_PURIFIER_URI_DISABLEEXT', '0', '_MD_AM_PURIFIER_URI_DISABLEEXTDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_URI_DisableExternalResources', '_MD_AM_PURIFIER_URI_DISABLEEXTRES', '0', '_MD_AM_PURIFIER_URI_DISABLEEXTRESDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_URI_DisableResources', '_MD_AM_PURIFIER_URI_DISABLERES', '0', '_MD_AM_PURIFIER_URI_DISABLERESDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_URI_MakeAbsolute', '_MD_AM_PURIFIER_URI_MAKEABS', '0', '_MD_AM_PURIFIER_URI_MAKEABSDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_URI_HostBlacklist', '_MD_AM_PURIFIER_URI_BLACKLIST', '', '_MD_AM_PURIFIER_URI_BLACKLISTDSC', 'textsarea', 'array', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_URI_AllowedSchemes', '_MD_AM_PURIFIER_URI_ALLOWSCHEME',  serialize ( array ('http', 'https', 'mailto', 'ftp', 'nntp', 'news' ) ), '_MD_AM_PURIFIER_URI_ALLOWSCHEMEDSC', 'textsarea', 'array', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_HTML_DefinitionID', '_MD_AM_PURIFIER_HTML_DEFID', 'system', '_MD_AM_PURIFIER_HTML_DEFIDDSC', 'textbox', 'text', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_HTML_DefinitionRev', '_MD_AM_PURIFIER_HTML_DEFREV', '1', '_MD_AM_PURIFIER_HTML_DEFREVDSC', 'textbox', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_HTML_Doctype', '_MD_AM_PURIFIER_HTML_DOCTYPE', 'XHTML 1.0 Transitional', '_MD_AM_PURIFIER_HTML_DOCTYPEDSC', 'select', 'text', $p );
		$p ++;

		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_HTML_TidyLevel', '_MD_AM_PURIFIER_HTML_TIDYLEVEL', 'medium', '_MD_AM_PURIFIER_HTML_TIDYLEVELDSC', 'select', 'text', $p );
		$p ++;
		$config_id = $icmsDB->getInsertId ();
		$sql = "INSERT INTO " . $icmsDB->prefix ( 'configoption' ) . " (confop_id, confop_name, confop_value, conf_id)" . " VALUES" . " (NULL, '_MD_AM_PURIFIER_NONE', 'none', {$config_id})," . " (NULL, '_MD_AM_PURIFIER_LIGHT', 'light', {$config_id})," . " (NULL, '_MD_AM_PURIFIER_MEDIUM', 'medium', {$config_id})," . " (NULL, '_MD_AM_PURIFIER_HEAVY', 'heavy', {$config_id})";
		if (! $icmsDB->queryF ( $sql )) {
			return false;
		}

		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_HTML_AllowedElements', '_MD_AM_PURIFIER_HTML_ALLOWELE',  serialize ( $HTML_Allowed_Elms ), '_MD_AM_PURIFIER_HTML_ALLOWELEDSC', 'textsarea', 'array', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_HTML_AllowedAttributes', '_MD_AM_PURIFIER_HTML_ALLOWATTR',  serialize ( $HTML_Allowed_Attr ), '_MD_AM_PURIFIER_HTML_ALLOWATTRDSC', 'textsarea', 'array', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_HTML_ForbiddenElements', '_MD_AM_PURIFIER_HTML_FORBIDELE', '', '_MD_AM_PURIFIER_HTML_FORBIDELEDSC', 'textsarea', 'array', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_HTML_ForbiddenAttributes', '_MD_AM_PURIFIER_HTML_FORBIDATTR', '', '_MD_AM_PURIFIER_HTML_FORBIDATTRDSC', 'textsarea', 'array', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_HTML_MaxImgLength', '_MD_AM_PURIFIER_HTML_MAXIMGLENGTH', '1200', '_MD_AM_PURIFIER_HTML_MAXIMGLENGTHDSC', 'textbox', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_HTML_SafeEmbed', '_MD_AM_PURIFIER_HTML_SAFEEMBED', '0', '_MD_AM_PURIFIER_HTML_SAFEEMBEDDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_HTML_SafeObject', '_MD_AM_PURIFIER_HTML_SAFEOBJECT', '0', '_MD_AM_PURIFIER_HTML_SAFEOBJECTDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_HTML_AttrNameUseCDATA', '_MD_AM_PURIFIER_HTML_ATTRNAMEUSECDATA', '0', '_MD_AM_PURIFIER_HTML_ATTRNAMEUSECDATADSC', 'yesno', 'int', $p );
		$p ++;

		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_Filter_ExtractStyleBlocks', '_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLK', '1', '_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_Filter_ExtractStyleBlocks_Escaping', '_MD_AM_PURIFIER_FILTERPARAM_EXTRACTSTYLEESC', '1', '_MD_AM_PURIFIER_FILTERPARAM_EXTRACTSTYLEESCDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_Filter_ExtractStyleBlocks_Scope', '_MD_AM_PURIFIER_FILTERPARAM_EXTRACTSTYLEBLKSCOPE', '', '_MD_AM_PURIFIER_FILTERPARAM_EXTRACTSTYLEBLKSCOPEDSC', 'textsarea', 'text', $p );
		$p ++;

		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_Filter_YouTube', '_MD_AM_PURIFIER_FILTER_ENABLEYOUTUBE', '1', '_MD_AM_PURIFIER_FILTER_ENABLEYOUTUBEDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_Core_EscapeNonASCIICharacters', '_MD_AM_PURIFIER_CORE_ESCNONASCIICHARS', '1', '_MD_AM_PURIFIER_CORE_ESCNONASCIICHARSDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_Core_HiddenElements', '_MD_AM_PURIFIER_CORE_HIDDENELE',  serialize ( array ('script', 'style' ) ), '_MD_AM_PURIFIER_CORE_HIDDENELEDSC', 'textsarea', 'array', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_Core_RemoveInvalidImg', '_MD_AM_PURIFIER_CORE_REMINVIMG', '1', '_MD_AM_PURIFIER_CORE_REMINVIMGDSC', 'yesno', 'int', $p );
		$p ++;

		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_AutoFormat_AutoParagraph', '_MD_AM_PURIFIER_AUTO_AUTOPARA', '0', '_MD_AM_PURIFIER_AUTO_AUTOPARADSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_AutoFormat_DisplayLinkURI', '_MD_AM_PURIFIER_AUTO_DISPLINKURI', '0', '_MD_AM_PURIFIER_AUTO_DISPLINKURIDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_AutoFormat_Linkify', '_MD_AM_PURIFIER_AUTO_LINKIFY', '1', '_MD_AM_PURIFIER_AUTO_LINKIFYDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_AutoFormat_PurifierLinkify', '_MD_AM_PURIFIER_AUTO_PURILINKIFY', '0', '_MD_AM_PURIFIER_AUTO_PURILINKIFYDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_AutoFormat_Custom', '_MD_AM_PURIFIER_AUTO_CUSTOM', '', '_MD_AM_PURIFIER_AUTO_CUSTOMDSC', 'textsarea', 'array', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_AutoFormat_RemoveEmpty', '_MD_AM_PURIFIER_AUTO_REMOVEEMPTY', '0', '_MD_AM_PURIFIER_AUTO_REMOVEEMPTYDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_AutoFormat_RemoveEmptyNbsp', '_MD_AM_PURIFIER_AUTO_REMOVEEMPTYNBSP', '0', '_MD_AM_PURIFIER_AUTO_REMOVEEMPTYNBSPDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_AutoFormat_RemoveEmptyNbspExceptions', '_MD_AM_PURIFIER_AUTO_REMOVEEMPTYNBSPEXCEPT',  serialize ( array ('td', 'th' ) ), '_MD_AM_PURIFIER_AUTO_REMOVEEMPTYNBSPEXCEPTDSC', 'textsarea', 'array', $p );
		$p ++;

		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_Attr_AllowedFrameTargets', '_MD_AM_PURIFIER_ATTR_ALLOWFRAMETARGET',  serialize ( array ('_blank', '_parent', '_self', '_top' ) ), '_MD_AM_PURIFIER_ATTR_ALLOWFRAMETARGETDSC', 'textsarea', 'array', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_Attr_AllowedRel', '_MD_AM_PURIFIER_ATTR_ALLOWREL', serialize ( array ('external', 'nofollow', 'external nofollow', 'lightbox' ) ), '_MD_AM_PURIFIER_ATTR_ALLOWRELDSC', 'textsarea', 'array', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_Attr_AllowedClasses', '_MD_AM_PURIFIER_ATTR_ALLOWCLASSES', '', '_MD_AM_PURIFIER_ATTR_ALLOWCLASSESDSC', 'textsarea', 'array', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_Attr_ForbiddenClasses', '_MD_AM_PURIFIER_ATTR_FORBIDDENCLASSES', '', '_MD_AM_PURIFIER_ATTR_FORBIDDENCLASSESDSC', 'textsarea', 'array', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_Attr_DefaultInvalidImage', '_MD_AM_PURIFIER_ATTR_DEFINVIMG', '', '_MD_AM_PURIFIER_ATTR_DEFINVIMGDSC', 'textbox', 'text', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_Attr_DefaultInvalidImageAlt', '_MD_AM_PURIFIER_ATTR_DEFINVIMGALT', '', '_MD_AM_PURIFIER_ATTR_DEFINVIMGALTDSC', 'textbox', 'text', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_Attr_DefaultImageAlt', '_MD_AM_PURIFIER_ATTR_DEFIMGALT', '', '_MD_AM_PURIFIER_ATTR_DEFIMGALTDSC', 'textbox', 'text', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_Attr_ClassUseCDATA', '_MD_AM_PURIFIER_ATTR_CLASSUSECDATA', '1', '_MD_AM_PURIFIER_ATTR_CLASSUSECDATADSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_Attr_EnableID', '_MD_AM_PURIFIER_ATTR_ENABLEID', '1', '_MD_AM_PURIFIER_ATTR_ENABLEIDDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_Attr_IDPrefix', '_MD_AM_PURIFIER_ATTR_IDPREFIX', '', '_MD_AM_PURIFIER_ATTR_IDPREFIXDSC', 'textbox', 'text', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_Attr_IDPrefixLocal', '_MD_AM_PURIFIER_ATTR_IDPREFIXLOCAL', '', '_MD_AM_PURIFIER_ATTR_IDPREFIXLOCALDSC', 'textbox', 'text', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_Attr_IDBlacklist', '_MD_AM_PURIFIER_ATTR_IDBLACKLIST', '', '_MD_AM_PURIFIER_ATTR_IDBLACKLISTDSC', 'textsarea', 'array', $p );
		$p ++;

		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_CSS_DefinitionRev', '_MD_AM_PURIFIER_CSS_DEFREV', '1', '_MD_AM_PURIFIER_CSS_DEFREVDSC', 'textbox', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_CSS_AllowImportant', '_MD_AM_PURIFIER_CSS_ALLOWIMPORTANT', '1', '_MD_AM_PURIFIER_CSS_ALLOWIMPORTANTDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_CSS_AllowTricky', '_MD_AM_PURIFIER_CSS_ALLOWTRICKY', '1', '_MD_AM_PURIFIER_CSS_ALLOWTRICKYDSC', 'yesno', 'int', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_CSS_AllowedProperties', '_MD_AM_PURIFIER_CSS_ALLOWPROP', '', '_MD_AM_PURIFIER_CSS_ALLOWPROPDSC', 'textsarea', 'array', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_CSS_MaxImgLength', '_MD_AM_PURIFIER_CSS_MAXIMGLEN', '1200px', '_MD_AM_PURIFIER_CSS_MAXIMGLENDSC', 'textbox', 'text', $p );
		$p ++;
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PURIFIER, 'purifier_CSS_Proprietary', '_MD_AM_PURIFIER_CSS_PROPRIETARY', '1', '_MD_AM_PURIFIER_CSS_PROPRIETARYDSC', 'yesno', 'int', $p );

		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 30;

	if ($dbVersion < $newDbVersion) {

		$table = new IcmsDatabasetable ( 'users' );
		if ($table->fieldExists ( 'level' )) {
			$icmsDatabaseUpdater->runQuery ( "ALTER TABLE `" . $table->name () . "` MODIFY level varchar(3) NOT NULL default '1'", 'Successfully modified field level in table users', '' );
		}
		unset ( $table );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	/* 1.2 beta release */

	if( !$abortUpdate) $newDbVersion = 32;
	/* this was in dbversion 18, but there were errors discovered after 1.2 beta relase */
	if ($dbVersion < $newDbVersion) {
		/*
		$table = new IcmsDatabasetable('icmscontent');
		if (!$table->fieldExists('content_tags')) {
		$table->addNewField('content_tags', "text");
		$icmsDatabaseUpdater->updateTable($table);
		}
		unset($table);
		*/
		$table = new IcmsDatabasetable ( 'imagecategory' );
		if (! $table->fieldExists ( 'imgcat_foldername' )) {
			$table->addNewField ( 'imgcat_foldername', "varchar(100) default ''" );
		}
		if (! $table->fieldExists ( 'imgcat_pid' )) {
			$table->addNewField ( 'imgcat_pid', "smallint(5) unsigned NOT NULL default '0'" );
		}
		$icmsDatabaseUpdater->updateTable ( $table );
		unset ( $table );

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
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';

	}
	if( !$abortUpdate) $newDbVersion = 33;
	/*
	 * New symlinks need to be added to the db
	 */
	if ($dbVersion < $newDbVersion) {
		$table = new IcmsDatabasetable ( 'icmspage' );
		$new_pages = array (
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
		foreach ( $new_pages as $new_page ) {
			$table->setData ( $new_page );
		}
		$table->addData ();
		unset ( $table );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if( !$abortUpdate) $newDbVersion = 34;
	/* The admin control panel now consists of blocks - these need to be set as visible
		 * Control Panel, System Warnings, Modules Installed
		 */
	if ($dbVersion < $newDbVersion) {
		$admin_blocks = array (array ('b_system_admin_cp_show', 'page_topleft_admin' ), array ('b_system_admin_modules_show', 'page_topright_admin' ), array ('b_system_admin_warnings_show', 'page_topcenter_admin' ) );
		/* Get block positions */
		$sql = 'SELECT id, pname FROM ' . $icmsDB->prefix ( 'block_positions' ) . ' WHERE pname = "page_topleft_admin"' . ' OR pname = "page_topright_admin"' . ' OR pname = "page_topcenter_admin"';
		$result = $icmsDB->query ( $sql );
		while ( $row = $icmsDB->fetchArray ( $result ) ) {
			$block_positions [$row ['pname']] = $row ['id'];
		}
		/* Get symlink id for Admin Control Panel */
		$page_id = getDbValue ( $icmsDB, 'icmspage', 'page_id', 'page_url="admin.php"' );

		foreach ( $admin_blocks as $admin_block ) {
			/* Get block ids for Control Panel, System Warnings, Installed Modules */
			$sql_find = 'SELECT bid FROM `' . $icmsDB->prefix ( 'newblocks' ) . '` WHERE show_func="' . $admin_block [0] . '"';
			$goodmsg = $admin_block [0] . ' updated';
			$badmsg = $admin_block [0] . ' failed';
			$result = $icmsDB->query ( $sql_find );
			list ( $block_id ) = $icmsDB->fetchRow ( $result );
			/* Modify the visible, side and visiblein properties of the blocks */
			$sql_update = 'UPDATE `' . $icmsDB->prefix ( 'newblocks' ) . '` SET `visible`=1, `side`=' . $block_positions [$admin_block [1]] . ' WHERE `bid`=' . $block_id;
			$icmsDatabaseUpdater->runQuery ( $sql_update, $goodmsg, $badmsg, true );
			$sql_page_update = 'UPDATE `' . $icmsDB->prefix ( 'block_module_link' ) . '` SET `module_id`=1, `page_id`=' . $page_id . ' WHERE `block_id`=' . $block_id;
			$icmsDatabaseUpdater->runQuery ( $sql_page_update, $goodmsg, $badmsg, true );
		}
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}
	if( !$abortUpdate) $newDbVersion = 35;
	/* copy settings for the old waiting contents block to the new block
	 * and set the block_type correctly for new system blocks -
	 * blocks added during a module update default to block_type 'M', which
	 * is not correct for the system module, adding todo in modulesadmin
	 */
	if ($dbVersion < $newDbVersion) {
		$result = $icmsDB->query ( 'SELECT title, side, weight, visible, bcachetime, bid' . ' FROM `' . $icmsDB->prefix ( 'newblocks' ) . '` WHERE `show_func`="b_system_waiting_show" AND `func_file`="system_blocks.php"' );
		list ( $title, $side, $weight, $visible, $bcachetime, $bid ) = $icmsDB->fetchRow ( $result );
		$icmsDB->queryF ( 'UPDATE `' . $icmsDB->prefix ( 'newblocks' ) . '` SET `title`="' . $title . '", `side`=' . $side . ', `weight`=' . $weight . ', `visible`=' . $visible . ', `bcachetime`=' . $bcachetime . ' WHERE `show_func`="b_system_waiting_show" AND `func_file`="system_waiting.php"' );
		$icmsDB->queryF ( 'DELETE FROM `' . $icmsDB->prefix ( 'newblocks' ) . '` WHERE `bid`=' . $bid );
		$icmsDB->queryF ( 'DELETE FROM `' . $icmsDB->prefix ( 'block_module_link' ) . '` WHERE `block_id`=' . $bid );
		$icmsDB->queryF ( 'UPDATE `' . $icmsDB->prefix ( 'newblocks' ) . '` SET `block_type`="S"' . ' WHERE `dirname`="system" AND `block_type`="M"' );

		/* Change the field type of welcome_msg_content to textsarea */
		$sql_welcome_msg_content = 'UPDATE ' . $icmsDB->prefix ( 'config' ) . ' SET `conf_formtype` = "textsarea"' . ' WHERE `conf_name`="welcome_msg_content"';
		$icmsDatabaseUpdater->runQuery ( $sql_welcome_msg_content, 'Welcome message form type successfully updated', 'Unable to update the welcome message form type', true );

		/* Set the start page for each group, so they don't default to Admin Control Panel */
		$groups = xoops_gethandler ( 'group' )->getObjects ( NULL, true );
		$start_page = getDbValue ( $icmsDB, 'config', 'conf_value', 'conf_name="startpage"' );
		foreach ( $groups as $groupid => $group ) {
			$start_pages [$groupid] = $start_page;
		}
		$icmsDB->queryF ( 'UPDATE `' . $icmsDB->prefix ( 'config' ) . '`' . ' SET `conf_value`="' . addslashes ( serialize ( $start_pages ) ) . '"' . ' WHERE `conf_name`="startpage"' );

		/* Check for HTMLPurifier cache path and create, if needed */
		$purifier_path = icms_mkdir ( ICMS_TRUST_PATH . '/cache/htmlpurifier' );
		/* Removing the option for multilogin text, as we're using a constant for it */
		$icmsDB->queryF ( "DELETE FROM `" . $icmsDB->prefix ( 'config' ) . "` WHERE conf_name='multi_login_msg'" );
		$icmsDB->queryF ( "DELETE FROM `" . $icmsDB->prefix ( 'config' ) . "` WHERE conf_name='use_hidden'" );

		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	/* 1.2 RC1 released */

	if( !$abortUpdate) $newDbVersion = 36;
	if ($dbVersion < $newDbVersion) {
		/* Change the the constant name for extractsyleblock_escape & styleblocks */
		$sql_extract_esc = 'UPDATE ' . $icmsDB->prefix ( 'config' ) . ' SET `conf_title` = "_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEESC"' . ' WHERE `conf_name`="purifier_Filter_ExtractStyleBlocks_Escaping"';
		$icmsDatabaseUpdater->runQuery ( $sql_extract_esc, 'Constant _MD_AM_PURIFIER_FILTER_EXTRACTSTYLEESC Updated', 'Unable to update Constant _MD_AM_PURIFIER_FILTER_EXTRACTSTYLEESC', true );

		/* Change the the constant name for extractsyleblock_escape & styleblocks Descriptions*/
		$sql_extract_escdsc = 'UPDATE ' . $icmsDB->prefix ( 'config' ) . ' SET `conf_desc` = "_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEESCDSC"' . ' WHERE `conf_name`="purifier_Filter_ExtractStyleBlocks_Escaping"';
		$icmsDatabaseUpdater->runQuery ( $sql_extract_escdsc, 'Constant _MD_AM_PURIFIER_FILTER_EXTRACTSTYLEESCDSC Updated', 'Unable to update Constant _MD_AM_PURIFIER_FILTER_EXTRACTSTYLEESCDSC', true );

		/* Change the the constant name for extractsyleblock_scope */
		$sql_extract_scope = 'UPDATE ' . $icmsDB->prefix ( 'config' ) . ' SET `conf_title` = "_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKSCOPE"' . ' WHERE `conf_name`="purifier_Filter_ExtractStyleBlocks_Scope"';
		$icmsDatabaseUpdater->runQuery ( $sql_extract_scope, 'Constant _MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKSCOPE Updated', 'Unable to update Constant _MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKSCOPE', true );

		/* Change the the constant name for extractsyleblock_scope Descriptions*/
		$sql_extract_scopedsc = 'UPDATE ' . $icmsDB->prefix ( 'config' ) . ' SET `conf_desc` = "_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKSCOPEDSC"' . ' WHERE `conf_name`="purifier_Filter_ExtractStyleBlocks_Scope"';
		$icmsDatabaseUpdater->runQuery ( $sql_extract_scopedsc, 'Constant _MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKSCOPEDSC Updated', 'Unable to update Constant _MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKSCOPEDSC', true );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 37;
	/* moving the images of the image manager from uploads to the new folder
	 */
	if ($dbVersion < $newDbVersion) {
		if (is_writable ( ICMS_IMANAGER_FOLDER_PATH )) {

			$result = $icmsDB->query ( 'SELECT * FROM `' . $icmsDB->prefix ( 'imagecategory' ) . '`' );
			while ( $row = $icmsDB->fetchArray ( $result ) ) {
				if (empty ( $row ['imgcat_foldername'] ) && $row[ 'imgcat_storetype' ] = 'file' ) {
					$new_folder =  preg_replace( '/[:?".<>\/\\\|\s]/', '_', strtolower ( $row[ 'imgcat_name' ] ));
				} else {
					$new_folder = $row ['imgcat_foldername '];
				}
				if( icms_mkdir ( ICMS_IMANAGER_FOLDER_PATH . '/' . $new_folder ) ) {

					$result1 = $icmsDB->query ( 'SELECT * FROM `' . $icmsDB->prefix ( 'image' ) . '` WHERE imgcat_id=' . $row ['imgcat_id'] );
					while( ( $row1 = $icmsDB->fetchArray ( $result1 ) ) && ! $abortUpdate ) {
						if( ! file_exists ( ICMS_IMANAGER_FOLDER_PATH . '/' . $new_folder . '/' . $row1 ['image_name'] ) && file_exists ( ICMS_UPLOAD_PATH . '/' . $row1 ['image_name'] )) {
							if( icms_copyr ( ICMS_UPLOAD_PATH . '/' . $row1 ['image_name'], ICMS_IMANAGER_FOLDER_PATH . '/' . $new_folder . '/' . $row1 ['image_name'] ) ) {
								@unlink ( ICMS_UPLOAD_PATH . '/' . $row1 ['image_name'] );
                                $icmsDB->queryF ( 'UPDATE `' . $icmsDB->prefix ( 'imagecategory' ) . '` SET imgcat_foldername="' . $new_folder . '" WHERE imgcat_id=' . $row ['imgcat_id'] );
							} else {
								$newDbVersion = 36;
								echo '<br />'.sprintf('Unable to copy image - %s', $row1['image_name']);
								$abortUpdate = true;
							}
						}
					}
				} else {
					$newDbVersion = 36;
					echo '<br />'.sprintf('Unable to create category folder - %s', $new_folder);
					$abortUpdate = true;
				}
			}
			/**
			 *Changing the path of the left and right admin logo, defined in the personalization preferences area.
			 */
			$result = $icmsDB->query ( 'SELECT conf_id,conf_value FROM `' . $icmsDB->prefix ( 'config' ) . '` WHERE conf_name = "adm_left_logo" or conf_name = "adm_right_logo"' );
			while ( list ( $conf_id, $conf_value ) = $icmsDB->fetchRow ( $result ) ) {
				$img = explode ( '/', $conf_value );
				$img = $img [count ( $img ) - 1];
				$result1 = $icmsDB->query ( 'SELECT imgcat_id FROM `' . $icmsDB->prefix ( 'image' ) . '` WHERE image_name="' . $img . '"' );
				list ( $imgcat_id ) = $icmsDB->fetchRow ( $result1 );
				$result2 = $icmsDB->query ( 'SELECT imgcat_foldername FROM `' . $icmsDB->prefix ( 'imagecategory' ) . '` WHERE imgcat_id="' . $imgcat_id . '"' );
				list ( $imgcat_foldername ) = $icmsDB->fetchRow ( $result2 );
				$new_conf_value = str_replace ( ICMS_ROOT_PATH, '', ICMS_IMANAGER_FOLDER_PATH ) . '/' . $imgcat_foldername . '/' . $img;
				$icmsDB->queryF ( 'UPDATE `' . $icmsDB->prefix ( 'config' ) . '` SET conf_value="' . $new_conf_value . '" WHERE conf_id=' . $conf_id );
			}
		} else {
			$newDbVersion = 36;
			echo '<br />'.sprintf(_MD_AM_IMAGESFOLDER_UPDATE_TEXT, ICMS_IMANAGER_FOLDER_PATH);
			$abortUpdate = true;
		}
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	/* 1.2 RC2 released */

	if (!$abortUpdate) $newDbVersion = 38;
	if ($dbVersion < $newDbVersion) {
		/* Change the system preference with textarea control to textsarea */
		$sql_extract_esc = 'UPDATE ' . $icmsDB->prefix ( 'config' ) . ' SET `conf_formtype` = "textsarea"' . ' WHERE  `conf_modid` =0 AND `conf_formtype` = "textarea"';
		$icmsDatabaseUpdater->runQuery ( $sql_extract_esc, 'System Preferences textarea controls set to textsarea', true );

		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	/* 1.2.1 release */

	if (!$abortUpdate) $newDbVersion = 39;
	if ($dbVersion < $newDbVersion) {
		// retrieve config_id for purifier_HTML_Doctype
		$sql = "SELECT conf_id FROM " . $icmsDB->prefix ( 'config' ) . " WHERE conf_name='purifier_HTML_Doctype'";
		$result = $icmsDB->query ($sql);
		if (!$result) $abortUpdate = true;
		$myrow = $icmsDB->fetchArray($result);
		if (!isset($myrow['conf_id'])) $abortUpdate = true;
		$config_id = $myrow['conf_id'];

		$sql = "INSERT INTO " . $icmsDB->prefix ( 'configoption' ) . " (confop_id, confop_name, confop_value, conf_id)" . " VALUES" . " (NULL, '_MD_AM_PURIFIER_401T', 'HTML 4.01 Transitional', {$config_id}), "
		. " (NULL, '_MD_AM_PURIFIER_401S', 'HTML 4.01 Strict', {$config_id}), "
		. " (NULL, '_MD_AM_PURIFIER_X10T', 'XHTML 1.0 Transitional', {$config_id}), "
		. " (NULL, '_MD_AM_PURIFIER_X10S', 'XHTML 1.0 Strict', {$config_id}), "
		. " (NULL, '_MD_AM_PURIFIER_X11', 'XHTML 1.1', {$config_id})";
		if (!$icmsDB->queryF($sql)) $abortUpdate = true;

	/* New config options and values for mail settings */
		$sql = 'UPDATE `' . $icmsDB->prefix( 'config' ) . '` SET `conf_order`=9 WHERE `conf_name`="sendmailpath"';
		$result = $icmsDB->query( $sql );
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_MAILER, 'smtpsecure', '_MD_AM_SMTPSECURE', 'ssl', '_MD_AM_SMTPSECUREDESC', 'select', 'text', 7 );
		$config_id = $icmsDB->getInsertId();
		$sql = "INSERT INTO " . $icmsDB->prefix ( 'configoption' ) . " (confop_id, confop_name, confop_value, conf_id)"
		. " VALUES" . " (NULL, 'None', 'none', {$config_id}), "
		. " (NULL, 'SSL', 'ssl', {$config_id}), "
		. " (NULL, 'TLS', 'tls', {$config_id})";
		if (!$icmsDB->queryF($sql)) $abortUpdate = true;
		$icmsDatabaseUpdater->insertConfig ( XOOPS_CONF_MAILER, 'smtpauthport', '_MD_AM_SMTPAUTHPORT', '465', '_MD_AM_SMTPAUTHPORTDESC', 'textbox', 'int', 8 );

		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	echo "</code>";
    if( $abortUpdate ) {
        icms_error_msg( sprintf( _DATABASEUPDATER_UPDATE_ERR, icms_conv_nr2local( $newDbVersion ) ), _DATABASEUPDATER_UPDATE_DB, TRUE);
    }
	if ($from_112 && ! $abortUpdate ) {
		/**
		 * @todo create a language constant for this text
		 */
		echo "<code><h3>You have updated your site from ImpressCMS 1.1.x to ImpressCMS 1.2 so you <strong>must install the new Content module</strong> to update the core content manager. You will be redirected to the installation process in 20 seconds. If this does not happen click <a href='" . ICMS_URL . "/modules/system/admin.php?fct=modulesadmin&op=install&module=content&from_112=1'>here</a>.</h3></code>";
		echo '<script>setTimeout("window.location.href=\'' . ICMS_URL . '/modules/system/admin.php?fct=modulesadmin&op=install&module=content&from_112=1\'",20000);</script>';
	}

	/* DEVELOPER, PLEASE NOTE !!!
  *
  * Everytime we add a new upgrade block here, the dbversion of the System Module will get
  * incremented. It is very important to modify the ICMS_SYSTEM_DBVERSION accordingly
  * in htdocs/include/version.php
  */

	$feedback = ob_get_clean ();
	if (method_exists ( $module, "setMessage" )) {
		$module->messages = $module->setMessage ( $feedback );
	} else {
		echo $feedback;
	}

	$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
	return icms_clean_folders ( array ('templates_c' => ICMS_ROOT_PATH . "/templates_c/", 'cache' => ICMS_ROOT_PATH . "/cache/" ), $CleanWritingFolders );
}
?>