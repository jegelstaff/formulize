<?php
/**
 * DataBase Update Functions - 1.1.2 release
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.3
 * @author		malanciault <marcan@impresscms.org)
 * @version		$Id: update-to-112.php 11963 2012-08-26 02:57:04Z skenow $
 */

	/*
	 * Migrate the db with new changes from 1.1 since 1.0
	 * Note: many of these changes have been implemented in the upgrade script, which is essential in 1.1 because
	 * of the new dbversion field we have added in the modules table. However, starting with release after 1.1, all
	 * upgrade scripts will be added here. Doing so, only the System module will need to be updated by webmaster.
	 */

	$newDbVersion = 1;

	if ($dbVersion <= $newDbVersion) {

		// Now, first, let's increment the conf_order of user option starting at new_user_notify
		$table = new icms_db_legacy_updater_Table ( 'config' );
		$criteria = new icms_db_criteria_Compo ( );
		$criteria->add ( new icms_db_criteria_Item ( 'conf_order', 3, '>' ) );
		$table->addUpdateAll ( 'conf_order', 'conf_order + 2', $criteria, true );
		$icmsDatabaseUpdater->updateTable ( $table );
		unset ( $table );

		// create extended date function's config option
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF, 'use_ext_date', '_MD_AM_EXT_DATE', 0, '_MD_AM_EXT_DATEDSC', 'yesno', 'int', 12 );
		// create editors config option
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF, 'editor_default', '_MD_AM_EDITOR_DEFAULT', 'default', '_MD_AM_EDITOR_DEFAULT_DESC', 'editor', 'text', 16 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF, 'editor_enabled_list', '_MD_AM_EDITOR_ENABLED_LIST', ".serialize(array('default')).", '_MD_AM_EDITOR_ENABLED_LIST_DESC', 'editor_multi', 'array', 16 );
		// create captcha options
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF, 'use_captchaf', '_MD_AM_USECAPTCHAFORM', 1, '_MD_AM_USECAPTCHAFORMDSC', 'yesno', 'int', 37 );

		// create 4 new user config options
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_USER, 'use_captcha', '_MD_AM_USECAPTCHA', 1, '_MD_AM_USECAPTCHADSC', 'yesno', 'int', 3 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_USER, 'welcome_msg', '_MD_AM_WELCOMEMSG', 0, '_MD_AM_WELCOMEMSGDSC', 'yesno', 'int', 3 );

		// get the default content of the mail
		$default_msg_content_file = ICMS_ROOT_PATH . '/language/' . $icmsConfig ['language'] . '/mail_template/' . 'welcome.tpl';
		if (! file_exists ( $default_msg_content_file )) {
			$default_msg_content_file = ICMS_ROOT_PATH . '/language/english/mail_template/' . 'welcome.tpl';
		}
		$fp = fopen ( $default_msg_content_file, 'r' );
		if ($fp) {
			$default_msg_content = fread ( $fp, filesize ( $default_msg_content_file ) );
		}
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_USER, 'welcome_msg_content', '_MD_AM_WELCOMEMSG_CONTENT', $default_msg_content, '_MD_AM_WELCOMEMSG_CONTENTDSC', 'textarea', 'text', 3 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_USER, 'allwshow_sig', '_MD_AM_ALLWSHOWSIG', 1, '_MD_AM_ALLWSHOWSIGDSC', 'yesno', 'int', 4 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_USER, 'allow_htsig', '_MD_AM_ALLWHTSIG', 1, '_MD_AM_ALLWHTSIGDSC', 'yesno', 'int', 4 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_USER, 'sig_max_length', '_MD_AM_SIGMAXLENGTH', '255', '_MD_AM_SIGMAXLENGTHDSC', 'textbox', 'int', 4 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_USER, 'avatar_allow_gravatar', '_MD_AM_GRAVATARALLOW', '1', '_MD_AM_GRAVATARALWDSC', 'yesno', 'int', 15 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_USER, 'allow_annon_view_prof', '_MD_AM_ALLOW_ANONYMOUS_VIEW_PROFILE', '1', '_MD_AM_ALLOW_ANONYMOUS_VIEW_PROFILE_DESC', 'yesno', 'int', 36 );

		// Adding configurations of meta tag&footer
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_METAFOOTER, 'google_meta', '_MD_AM_METAGOOGLE', '', '_MD_AM_METAGOOGLE_DESC', 'textbox', 'text', 9 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_METAFOOTER, 'use_google_analytics', '_MD_AM_USE_GOOGLE_ANA', 0, '_MD_AM_USE_GOOGLE_ANA_DESC', 'yesno', 'int', 21 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_METAFOOTER, 'google_analytics', '_MD_AM_GOOGLE_ANA', '', '_MD_AM_GOOGLE_ANA_DESC', 'textbox', 'text', 21 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_METAFOOTER, 'footadm', '_MD_AM_FOOTADM', 'Powered by ImpressCMS &copy; 2007-' . date ( "Y", time () ) . ' <a href=\"http://www.impresscms.org/\" rel=\"external\">The ImpressCMS Project</a>', '_MD_AM_FOOTADM_DESC', 'textarea', 'text', 22 );

		// Adding configurations of search preferences
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_SEARCH, 'search_user_date', '_MD_AM_SEARCH_USERDATE', '1', '_MD_AM_SEARCH_USERDATE', 'yesno', 'int', 2 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_SEARCH, 'search_no_res_mod', '_MD_AM_SEARCH_NO_RES_MOD', '1', '_MD_AM_SEARCH_NO_RES_MODDSC', 'yesno', 'int', 3 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_SEARCH, 'search_per_page', '_MD_AM_SEARCH_PER_PAGE', '20', '_MD_AM_SEARCH_PER_PAGEDSC', 'textbox', 'int', 4 );

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
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'adm_left_logo', '_MD_AM_LLOGOADM', '/uploads/img482278e29e81c.png', '_MD_AM_LLOGOADM_DESC', 'select_image', 'text', 1 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'adm_left_logo_url', '_MD_AM_LLOGOADM_URL', '' . XOOPS_URL . '/', '_MD_AM_LLOGOADM_URL_DESC', 'textbox', 'text', 2 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'adm_left_logo_alt', '_MD_AM_LLOGOADM_ALT', 'ImpressCMS', '_MD_AM_LLOGOADM_ALT_DESC', 'textbox', 'text', 3 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'adm_right_logo', '_MD_AM_RLOGOADM', '', '_MD_AM_RLOGOADM_DESC', 'select_image', 'text', 4 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'adm_right_logo_url', '_MD_AM_RLOGOADM_URL', '', '_MD_AM_RLOGOADM_URL_DESC', 'textbox', 'text', 5 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'adm_right_logo_alt', '_MD_AM_RLOGOADM_ALT', '', '_MD_AM_RLOGOADM_ALT_DESC', 'textbox', 'text', 6 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'rss_local', '_MD_AM_RSSLOCAL', 'http://www.impresscms.org/modules/smartsection/backend.php', '_MD_AM_RSSLOCAL_DESC', 'textbox', 'text', 7 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'editre_block', '_MD_AM_EDITREMOVEBLOCK', '1', '_MD_AM_EDITREMOVEBLOCKDSC', 'yesno', 'int', 8 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'multi_login', '_MD_AM_MULTLOGINPREVENT', '0', '_MD_AM_MULTLOGINPREVENTDSC', 'yesno', 'int', 9 );
		//$icmsDatabaseUpdater->insertConfig(ICMS_CONF_PERSONA, 'multi_login_msg', '_MD_AM_MULTLOGINMSG', $default_login_content, '_MD_AM_MULTLOGINMSG_DESC', 'textarea', 'text', 10);
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'email_protect', '_MD_AM_EMAILPROTECT', '0', '_MD_AM_EMAILPROTECTDSC', 'yesno', 'int', 11 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'email_font', '_MD_AM_EMAILTTF', 'arial.ttf', '_MD_AM_EMAILTTF_DESC', 'select_font', 'text', 12 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'email_font_len', '_MD_AM_EMAILLEN', '12', '_MD_AM_EMAILLEN_DESC', 'textbox', 'int', 13 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'email_cor', '_MD_AM_EMAILCOLOR', '#000000', '_MD_AM_EMAILCOLOR_DESC', 'color', 'text', 14 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'email_shadow', '_MD_AM_EMAILSHADOW', '#cccccc', '_MD_AM_EMAILSHADOW_DESC', 'color', 'text', 15 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'shadow_x', '_MD_AM_SHADOWX', '2', '_MD_AM_SHADOWX_DESC', 'textbox', 'int', 16 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'shadow_y', '_MD_AM_SHADOWY', '2', '_MD_AM_SHADOWY_DESC', 'textbox', 'int', 17 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'shorten_url', '_MD_AM_SHORTURL', '0', '_MD_AM_SHORTURLDSC', 'yesno', 'int', 18 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'max_url_long', '_MD_AM_URLLEN', '50', '_MD_AM_URLLEN_DESC', 'textbox', 'int', 19 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'pre_chars_left', '_MD_AM_PRECHARS', '35', '_MD_AM_PRECHARS_DESC', 'textbox', 'int', 20 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'last_chars_left', '_MD_AM_LASTCHARS', '10', '_MD_AM_LASTCHARS_DESC', 'textbox', 'int', 21 );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'show_impresscms_menu', '_MD_AM_SHOW_ICMSMENU', '1', '_MD_AM_SHOW_ICMSMENU_DESC', 'yesno', 'int', 22 );
		// Adding new function of authentication
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_AUTH, 'auth_openid', '_MD_AM_AUTHOPENID', '0', '_MD_AM_AUTHOPENIDDSC', 'yesno', 'int', 1 );

		$table = new icms_db_legacy_updater_Table ( 'imagecategory' );
		$icmsDatabaseUpdater->runQuery ( 'INSERT INTO ' . $table->name () . ' (imgcat_id, imgcat_name, imgcat_maxsize, imgcat_maxwidth, imgcat_maxheight, imgcat_display, imgcat_weight, imgcat_type, imgcat_storetype) VALUES (NULL, "Logos", 350000, 350, 80, 1, 0, "C", "file")', 'Successfully created Logos imagecategory', 'Problems when try to create Logos imagecategory' );
		unset ( $table );
		$result = icms::$xoopsDB->query ( "SELECT imgcat_id FROM " . icms::$xoopsDB->prefix ( 'imagecategory' ) . " WHERE imgcat_name = 'Logos'" );
		list ( $categ_id ) = icms::$xoopsDB->fetchRow ( $result );
		$table = new icms_db_legacy_updater_Table ( 'image' );
		$icmsDatabaseUpdater->runQuery ( 'INSERT INTO ' . $table->name () . ' (image_id, image_name, image_nicename, image_mimetype, image_created, image_display, image_weight, imgcat_id) VALUES (NULL, "img482278e29e81c.png", "ImpressCMS", "image/png", ' . time () . ', 1, 0, ' . $categ_id . ')', 'Successfully added default ImpressCMS admin logo', 'Problems when try to add ImpressCMS admin logo' );
		unset ( $table );
		$table = new icms_db_legacy_updater_Table ( 'group_permission' );
		$icmsDatabaseUpdater->runQuery ( 'INSERT INTO ' . $table->name () . ' VALUES(0,1,' . $categ_id . ',1,"imgcat_write")', '', '' );
		$icmsDatabaseUpdater->runQuery ( 'INSERT INTO ' . $table->name () . ' VALUES(0,1,' . $categ_id . ',1,"imgcat_read")', '', '' );
		unset ( $table );
		$table = new icms_db_legacy_updater_Table ( 'block_module_link' );
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
	if (!$abortUpdate) $newDbVersion = 2;

	if ($dbVersion < $newDbVersion) {
		$configitem_handler = icms::handler('icms_config_item');
		// fetch the rss_local configitem
		$criteria = new icms_db_criteria_Compo ( );
		$criteria->add ( new icms_db_criteria_Item ( 'conf_name', 'rss_local' ) );
		$criteria->add ( new icms_db_criteria_Item ( 'conf_catid', ICMS_CONF_PERSONA ) );
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
	if (!$abortUpdate) $newDbVersion = 3;

	if ($dbVersion < $newDbVersion) {
		$table = new icms_db_legacy_updater_Table ( 'users' );
		if (! $table->fieldExists ( 'openid' )) {
			$table->addNewField ( 'openid', "varchar(255) NOT NULL default ''" );
			$icmsDatabaseUpdater->updateTable ( $table );
		}
		unset ( $table );

		$table = new icms_db_legacy_updater_Table ( 'users' );
		if (! $table->fieldExists ( 'user_viewoid' )) {
			$table->addNewField ( 'user_viewoid', "tinyint(1) UNSIGNED NOT NULL default 0" );
			$icmsDatabaseUpdater->updateTable ( $table );
		}
		unset ( $table );

		$table = new icms_db_legacy_updater_Table ( 'users' );
		if (! $table->fieldExists ( 'pass_expired' )) {
			$table->addNewField ( 'pass_expired', "tinyint(1) UNSIGNED NOT NULL default 0" );
			$icmsDatabaseUpdater->updateTable ( $table );
		}
		unset ( $table );

		$table = new icms_db_legacy_updater_Table ( 'users' );
		if (! $table->fieldExists ( 'enc_type' )) {
			$table->addNewField ( 'enc_type', "tinyint(2) UNSIGNED NOT NULL default 0" );
			$icmsDatabaseUpdater->updateTable ( $table );
		}
		unset ( $table );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 4;

	if ($dbVersion < $newDbVersion) {
		/* this syntax is incorrect and does not alter the table, as desired
		 * commenting out this and correcting in db version 40 - skenow

		$table = new icms_db_legacy_updater_Table ( 'users' );
		if ($table->fieldExists ( 'pass' )) {
			$table->alterTable ( 'pass', 'pass', "varchar(255) NOT NULL default ''" );
			$icmsDatabaseUpdater->updateTable ( $table );
		}
		unset ( $table );
		*/
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 5;

	if ($dbVersion < $newDbVersion) {
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'use_jsjalali', '_MD_AM_JALALICAL', '0', '_MD_AM_JALALICALDSC', 'yesno', 'int', 23 );
		unset ( $table );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	//Some users had used a copy of working branch and they got multiple option, this is to remove all those re-created options and make a single option
	if (!$abortUpdate) $newDbVersion = 6;

	if ($dbVersion < $newDbVersion) {
		icms::$xoopsDB->queryF ( "DELETE FROM `" . icms::$xoopsDB->prefix ( 'config' ) . "` WHERE conf_name='use_jsjalali'" );
		$icmsDatabaseUpdater->insertConfig ( ICMS_CONF_PERSONA, 'use_jsjalali', '_MD_AM_JALALICAL', '0', '_MD_AM_JALALICALDSC', 'yesno', 'int', 23 );
		unset ( $table );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 7;

	if ($dbVersion < $newDbVersion) {
		$configitem_handler = icms::handler('icms_config_item');
		// fetch the rss_local configitem
		$criteria = new icms_db_criteria_Compo ( );
		$criteria->add ( new icms_db_criteria_Item ( 'conf_name', 'google_analytics' ) );
		$criteria->add ( new icms_db_criteria_Item ( 'conf_catid', ICMS_CONF_METAFOOTER ) );
		$configitemsObj = $configitem_handler->getObjects ( $criteria );
		if (isset ( $configitemsObj [0] ) && $configitemsObj [0]->getVar ( 'conf_formtype', 'n' ) == 'textarea') {
			$configitemsObj [0]->setVar ( 'conf_formtype', 'textbox' );
			$configitem_handler->insert ( $configitemsObj [0] );
			echo "&nbsp;&nbsp;Updating google_analytics field type<br />";
		}
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 8;

	if ($dbVersion < $newDbVersion) {

		$table = new icms_db_legacy_updater_Table ( 'modules' );
		if ($table->fieldExists ( 'dbversion' )) {
			$icmsDatabaseUpdater->runQuery ( "ALTER TABLE `" . $table->name () . "` MODIFY dbversion INT(11) unsigned NOT NULL DEFAULT 1", 'Successfully modified field dbversion in table modules', '' );
		}
		$icmsDatabaseUpdater->runQuery ( "ALTER TABLE `" . $table->name () . "` MODIFY version smallint(5) unsigned NOT NULL default '102'", 'Successfully modified field version in table modules', '' );
		unset ( $table );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 9;

	if ($dbVersion < $newDbVersion) {
		$table = new icms_db_legacy_updater_Table ( 'users' );
		$icmsDatabaseUpdater->runQuery ( "ALTER TABLE `" . $table->name () . "` DROP INDEX unamepass, ADD INDEX unamepass (uname (10), pass (10))", 'Successfully altered the index unamepass on table users', '' );
		$icmsDatabaseUpdater->runQuery ( "ALTER TABLE `" . $table->name () . "` MODIFY pass_expired tinyint(1) unsigned NOT NULL default 0", 'Successfully altered field pass_expired in table users', '' );
		unset ( $table );
		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';
	}

	if (!$abortUpdate) $newDbVersion = 10;
	if ($dbVersion < $newDbVersion) {

		if (getDbValue ( icms::$xoopsDB, 'newblocks', 'show_func', 'show_func="b_social_bookmarks"' ) == FALSE) {
			$sql = "SELECT bid FROM `" . icms::$xoopsDB->prefix ( 'newblocks' ) . "` WHERE show_func='b_social_bookmarks'";
			$result = icms::$xoopsDB->query ( $sql );
			list ( $new_block_id ) = icms::$xoopsDB->FetchRow ( $result );
			icms::$xoopsDB->queryF ( " INSERT INTO " . icms::$xoopsDB->prefix ( "block_module_link" ) . " VALUES (" . $new_block_id . ", 0, 1);" );
			icms::$xoopsDB->queryF ( " INSERT INTO " . icms::$xoopsDB->prefix ( "group_permission" ) . " VALUES ('', 3, " . $new_block_id . ", 1, 'block_read');" );
		}

		if (getDbValue ( icms::$xoopsDB, 'newblocks', 'show_func', 'show_func="b_content_show"' ) == FALSE) {
			$sql = "SELECT bid FROM `" . icms::$xoopsDB->prefix ( 'newblocks' ) . "` WHERE show_func='b_content_show'";
			$result = icms::$xoopsDB->query ( $sql );
			list ( $new_block_id ) = icms::$xoopsDB->FetchRow ( $result );
			icms::$xoopsDB->queryF ( " INSERT INTO " . icms::$xoopsDB->prefix ( "block_module_link" ) . " VALUES (" . $new_block_id . ", 0, 0);" );
			icms::$xoopsDB->queryF ( " INSERT INTO " . icms::$xoopsDB->prefix ( "group_permission" ) . " VALUES ('', 3, " . $new_block_id . ", 1, 'block_read');" );
		}

		if (getDbValue ( icms::$xoopsDB, 'newblocks', 'show_func', 'show_func="b_content_menu_show"' ) == FALSE) {
			$sql = "SELECT bid FROM `" . icms::$xoopsDB->prefix ( 'newblocks' ) . "` WHERE show_func='b_content_menu_show'";
			$result = icms::$xoopsDB->query ( $sql );
			list ( $new_block_id ) = icms::$xoopsDB->FetchRow ( $result );
			icms::$xoopsDB->queryF ( " INSERT INTO " . icms::$xoopsDB->prefix ( "block_module_link" ) . " VALUES (" . $new_block_id . ", 0, 0);" );
			icms::$xoopsDB->queryF ( " INSERT INTO " . icms::$xoopsDB->prefix ( "group_permission" ) . " VALUES ('', 3, " . $new_block_id . ", 1, 'block_read');" );
		}

		if (getDbValue ( icms::$xoopsDB, 'newblocks', 'show_func', 'show_func="b_content_relmenu_show"' ) == FALSE) {
			$sql = "SELECT bid FROM `" . icms::$xoopsDB->prefix ( 'newblocks' ) . "` WHERE show_func='b_content_relmenu_show'";
			$result = icms::$xoopsDB->query ( $sql );
			list ( $new_block_id ) = icms::$xoopsDB->FetchRow ( $result );
			icms::$xoopsDB->queryF ( " INSERT INTO " . icms::$xoopsDB->prefix ( "block_module_link" ) . " VALUES (" . $new_block_id . ", 0, 0);" );
			icms::$xoopsDB->queryF ( " INSERT INTO " . icms::$xoopsDB->prefix ( "group_permission" ) . " VALUES ('', 3, " . $new_block_id . ", 1, 'block_read');" );
		}
		icms::$xoopsDB->queryF ( " INSERT INTO " . icms::$xoopsDB->prefix ( "group_permission" ) . " VALUES ('', 1, 16, 1, 'system_admin');" );
		icms::$xoopsDB->queryF ( " INSERT INTO " . icms::$xoopsDB->prefix ( "group_permission" ) . " VALUES ('', 1, 17, 1, 'system_admin');" );
		icms::$xoopsDB->queryF ( " INSERT INTO " . icms::$xoopsDB->prefix ( "group_permission" ) . " VALUES ('', 1, 18, 1, 'system_admin');" );
		icms::$xoopsDB->queryF ( " INSERT INTO " . icms::$xoopsDB->prefix ( "group_permission" ) . " VALUES ('', 1, 19, 1, 'system_admin');" );
		icms::$xoopsDB->queryF ( " INSERT INTO " . icms::$xoopsDB->prefix ( "group_permission" ) . " VALUES ('', 1, 20, 1, 'system_admin');" );
		icms::$xoopsDB->queryF ( " INSERT INTO " . icms::$xoopsDB->prefix ( "group_permission" ) . " VALUES ('', 1, 1, 1, 'content_admin');" );
		icms::$xoopsDB->queryF ( " INSERT INTO " . icms::$xoopsDB->prefix ( "group_permission" ) . " VALUES ('', 1, 2, 1, 'group_manager');" );
		icms::$xoopsDB->queryF ( " INSERT INTO " . icms::$xoopsDB->prefix ( "group_permission" ) . " VALUES ('', 1, 3, 1, 'group_manager');" );

		$icmsDatabaseUpdater->updateModuleDBVersion ( $newDbVersion, 'system' );
		echo sprintf ( _DATABASEUPDATER_UPDATE_OK, icms_conv_nr2local ( $newDbVersion ) ) . '<br />';

	}

	if (!$abortUpdate) $newDbVersion = 11;

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
