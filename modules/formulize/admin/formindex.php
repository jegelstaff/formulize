<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Freeform Solutions and NS Tai (aka tuff) and others ##
##  URL: http://www.brandycoke.com/                                          ##
##  Project: Formulize                                                       ##
###############################################################################

include("admin_header.php");

if ( file_exists("../language/".$xoopsConfig['language']."/main.php") ) {
    include_once "../language/".$xoopsConfig['language']."/main.php";
} else {
    include_once "../language/english/main.php";
}

if (!isset($_GET['op']) AND !defined('_FORMULIZE_UI_PHP_INCLUDED')){
    header('Location: '.XOOPS_URL.'/modules/formulize/admin/ui.php');
    exit();
}

include_once XOOPS_ROOT_PATH."/modules/formulize/class/forms.php"; // form class
include_once XOOPS_ROOT_PATH."/modules/formulize/include/extract.php";
include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";
include_once XOOPS_ROOT_PATH.'/class/xoopsform/grouppermform.php'; // Classe permissions
$module_id = $xoopsModule->getVar('mid'); // recupere le numero id du module

$n = 0;
$m = 0;
include_once XOOPS_ROOT_PATH."/class/xoopstree.php";
include_once XOOPS_ROOT_PATH."/class/xoopslists.php";
include_once XOOPS_ROOT_PATH."/include/xoopscodes.php";
include_once XOOPS_ROOT_PATH."/class/module.errorhandler.php";
$myts =& MyTextSanitizer::getInstance();
$eh = new ErrorHandler;

// this functions runs the SQL and returns false if it failed, also outputs error message to screen
// returns the result object of the query if it was successful
function formulize_DBPatchCheckSQL($sql, &$needsPatch) {
    global $xoopsDB;
    //print $sql."<br>";
    if (!$needsPatchRes = $xoopsDB->queryF($sql)) {
        print "Error: ".$xoopsDB->error()."<br>We could not determine if your Formulize database structure is up to date.  Please contact <a href=\"mailto:info@formulize.org\">info@formulize.org</a> for assistance.<br>\n";
        return false;
    }
    $needsPatchRows = $xoopsDB->getRowsNum($needsPatchRes);
    if ($needsPatchRows == 0) { // no rows returned
        $needsPatch = true;
    }
    return $needsPatchRes;
}

// database patch logic for 4.0 and higher
function patch40() {
    
    $module_handler = xoops_gethandler('module');
    $formulizeModule = $module_handler->getByDirname("formulize");
    $metadata = $formulizeModule->getInfo();
    $versionNumber = $metadata['version'];
    
    // CHECK THAT THEY ARE AT 3.1 LEVEL, IF NOT, LINK TO PATCH31
    // Check for ele_handle being 255 in formulize table
    global $xoopsDB;
    // note very odd use of LIKE as a clause of its own in SHOW statements, very strange, but that's what MySQL does
    $fieldStateSQL = "SHOW COLUMNS FROM " . $xoopsDB->prefix("formulize") ." LIKE 'ele_handle'";
    if (!$fieldStateRes = $xoopsDB->queryF($fieldStateSQL)) {
        print "Error: could not determine if your Formulize database structure is up to date.  Please contact <a href=\"mailto:info@formulize.org\">info@formulize.org</a> for assistance.<br>\n";
        return false;
    }
    $fieldStateData = $xoopsDB->fetchArray($fieldStateRes);
    $dataType = $fieldStateData['Type'];
    if ($dataType != "varchar(255)") {
        print "<h1>Your database schema is out of date.  You must run \"patch31\" before running the current patch.</h1>\n";
        print "<p><a href=\"" . XOOPS_URL . "/modules/formulize/admin/formindex.php?op=patch31\">Click here to run \"patch31\".</a></p>\n";
        return;
    }

    /* ======================================
     * We must check here for the latest change, so we can tell the user whether they need to update or not!!
     * We set needsPatch = false, and the alter to true if a patch is necessary
     * When false, we return nothing from this function, as a cue that no patch is required
     *
     * To specify what to check, simply update the four variables declared below this comment.
     * Set to false to ignore that level of checking
     *
     * THIS NEEDS TO BE UPDATED WHENEVER THERE IS A PATCH TO THE DATA STRUCTURE!!!
     *
     * IN ADDITION TO THE UPDATE HERE, THE mysql.sql FILE MUST BE UPDATED WITH THE REQUIRED CHANGES SO NEW INSTALLATIONS ARE UP TO DATE
     *
     * IT IS ALSO CRITICAL THAT THE PATCH PROCESS CAN BE RUN OVER AND OVER AGAIN NON-DESTRUCTIVELY
     *
     * ====================================== */

    $checkThisTable = 'formulize_id';
	$checkThisField = 'on_delete';
	$checkThisProperty = '';
	$checkPropertyForValue = '';

    $needsPatch = false;

    $tableCheckSql = "SELECT 1 FROM information_schema.tables WHERE table_schema = '".SDATA_DB_NAME."' AND table_name = '".$xoopsDB->prefix(formulize_db_escape($checkThisTable)) ."'";
    $tableCheckRes = formulize_DBPatchCheckSQL($tableCheckSql, $needsPatch); // may modify needsPatch!
    if ($tableCheckRes AND !$needsPatch AND $checkThisField) { // table was found, and we're looking for a field in it
        $fieldCheckSql = "SHOW COLUMNS FROM " . $xoopsDB->prefix(formulize_db_escape($checkThisTable)) ." LIKE '".formulize_db_escape($checkThisField)."'"; // note very odd use of LIKE as a clause of its own in SHOW statements, very strange, but that's what MySQL does
        $fieldCheckRes = formulize_DBPatchCheckSQL($fieldCheckSql, $needsPatch); // may modify needsPatch!
    }
    if ($fieldCheckRes AND !$needsPatch AND $checkPropertyForValue) {
        $fieldCheckArray = $xoopsDB->fetchArray($fieldCheckRes);
        if ($fieldCheckArray[$checkThisProperty] != $checkPropertyForValue) {
            $needsPatch = true;
        }
    }

    if (!$needsPatch AND (!isset($_GET['op']) OR ($_GET['op'] != 'patch40' AND $_GET['op'] != 'patchDB'))) {
        return;
    }

    if (!isset($_POST['patch40'])) {
        $additional_themes = isset($_GET['additional_themes']) ? '&additional_themes='.preg_replace("/[^,a-zA-Z0-9_-]+/", "", $_GET['additional_themes']) : '';
        $skipRefUpdates = isset($_GET['skip_ref_updates']) ? '&skip_ref_updates=1' : '';
        print "<h1>Your database structure is not up to date!  Click the button below to apply the necesssary patch to the database.</h1>";
        print "<h2>Warning: this patch makes several changes to the database.  Backup your database prior to applying this patch!</h2>";
        print "<form action=\"ui.php?op=patchDB$additional_themes$skipRefUpdates\" method=post>";
        print "<input type = submit name=patch40 value=\"Apply Database Patch for Formulize\">";
        print "</form>";
    } else {
        // PATCH LOGIC GOES HERE
        print "<h2>Patch Results:</h2>";

        $testsql = "SHOW TABLES";
        $resultst = $xoopsDB->query($testsql);
        while($table = $xoopsDB->fetchRow($resultst)) {
            $existingTables[] = $table[0];
        }

        $sql = array();

        if (!in_array($xoopsDB->prefix("tfa_codes"), $existingTables)) {
                $sql[] = "CREATE TABLE `".$xoopsDB->prefix("tfa_codes")."` (
          `code_id` int(11) unsigned NOT NULL auto_increment,
          `uid` int(11) unsigned DEFAULT NULL,
          `code` varchar(255) DEFAULT NULL,
          `method` tinyint(1) unsigned DEFAULT NULL,
          PRIMARY KEY (`code_id`),
          INDEX i_uid (`uid`)
        ) ENGINE=InnoDB;";
        }
        
        if (!in_array($xoopsDB->prefix("formulize_digest_data"), $existingTables)) {
            $sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_digest_data")."` (
                `digest_id` int(11) unsigned NOT NULL auto_increment,
                `email` varchar(255) DEFAULT NULL,
                `fid` int(11) DEFAULT NULL,
                `event` varchar(50) DEFAULT NULL,
                `extra_tags` text DEFAULT NULL,
                `mailSubject` text DEFAULT NULL,
                `mailTemplate` text DEFAULT NULL,
                PRIMARY KEY (`digest_id`),
                INDEX i_email (`email`),
                INDEX i_fid (`fid`)
              ) ENGINE=InnoDB;";
        }
        
        if (!in_array($xoopsDB->prefix("formulize_groupscope_settings"), $existingTables)) {
            $sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_groupscope_settings")."` (
                `groupscope_id` int(11) NOT NULL auto_increment,
                `groupid` int(11) NOT NULL default 0,
                `fid` int(11) NOT NULL default 0,
                `view_groupid` int(11) NOT NULL default 0,
                PRIMARY KEY (`groupscope_id`),
                INDEX i_groupid (`groupid`),
                INDEX i_fid (`fid`),
                INDEX i_view_groupid (`view_groupid`)
              ) ENGINE=InnoDB;";
        }

        if (!in_array($xoopsDB->prefix("formulize_tokens"), $existingTables)) {
            $sql[] = "CREATE TABLE " . $xoopsDB->prefix("formulize_tokens") . " (
                `key_id` int(11) unsigned NOT NULL auto_increment,
                `groups` varchar(255) NOT NULL default '',
                `tokenkey` varchar(255) NOT NULL default '',
                `expiry` datetime default NULL,
                `maxuses` int(11) NOT NULL default '0',
                `currentuses` int(11) NOT NULL default '0',
                PRIMARY KEY (`key_id`),
                INDEX i_groups (`groups`),
                INDEX i_tokenkey (tokenkey),
                INDEX i_expiry (expiry),
                INDEX i_maxuses (maxuses),
                INDEX i_currentuses (currentuses)
            ) ENGINE=InnoDB;";
        } 
        
        
        
        if (!in_array($xoopsDB->prefix("formulize_group_filters"), $existingTables)) {
            $sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_group_filters")."` (
  `filterid` int(11) NOT NULL auto_increment,
  `fid` int(11) NOT NULL default 0,
  `groupid` int(11) NOT NULL default 0,
  `filter` text NOT NULL,
  PRIMARY KEY (`filterid`),
  INDEX i_fid (`fid`),
  INDEX i_groupid (`groupid`)
) ENGINE=InnoDB;";
        }

        if (!in_array($xoopsDB->prefix("formulize_applications"), $existingTables)) {
            $sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_applications")."` (
  `appid` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  PRIMARY KEY (`appid`)
) ENGINE=InnoDB;";
        }

        if (!in_array($xoopsDB->prefix("formulize_application_form_link"), $existingTables)) {
            $sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_application_form_link")."` (
  `linkid` int(11) NOT NULL auto_increment,
  `appid` int(11) NOT NULL default 0,
  `fid` int(11) NOT NULL default 0,
  PRIMARY KEY (`linkid`),
  INDEX i_fid (`fid`),
  INDEX i_appid (`appid`)
) ENGINE=InnoDB;";
        }

        if (!in_array($xoopsDB->prefix("formulize_screen_form"), $existingTables)) {
            $sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_screen_form")."` (
  `formid` int(11) NOT NULL auto_increment,
  `sid` int(11) NOT NULL default 0,
  `donedest` varchar(255) NOT NULL default '',
  `savebuttontext` varchar(255) NOT NULL default '',
  `saveandleavebuttontext` varchar(255) NOT NULL default '',
  `printableviewbuttontext` varchar(255) NOT NULL default '',
  `alldonebuttontext` varchar(255) NOT NULL default '',
  `displayheading` tinyint(1) NOT NULL default 0,
  `reloadblank` tinyint(1) NOT NULL default 0,
  `formelements` text,
  `elementdefaults` text NOT NULL,
  `displaycolumns` tinyint(1) NOT NULL default 2,
  `column1width` varchar(255) NULL default NULL,
  `column2width` varchar(255) NULL default NULL,
  `displayType` varchar(255) NOT NULL default 'block',
  PRIMARY KEY (`formid`),
  INDEX i_sid (`sid`)
) ENGINE=InnoDB;";
        }

        if (!in_array($xoopsDB->prefix("formulize_advanced_calculations"), $existingTables)) {
            $sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_advanced_calculations")."` (
  `acid` int(11) NOT NULL auto_increment,
  `fid` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  `input` text NOT NULL,
  `output` text NOT NULL,
  `steps` text NOT NULL,
  `steptitles` text NOT NULL,
  `fltr_grps` text NOT NULL,
  `fltr_grptitles` text NOT NULL,
  PRIMARY KEY  (`acid`),
  KEY `i_fid` (`fid`)
) ENGINE=InnoDB;";
        }

        if (!in_array($xoopsDB->prefix("formulize_procedure_logs"), $existingTables)) {
            $sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_procedure_logs")."` (
  `proc_log_id` int(11) unsigned NOT NULL auto_increment,
  `proc_id` int(11) NOT NULL,
  `proc_datetime` datetime NOT NULL,
  `proc_uid` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`proc_log_id`),
  INDEX i_proc_id (proc_id),
  INDEX i_proc_uid (proc_uid)
) ENGINE=InnoDB;";
        }

        if (!in_array($xoopsDB->prefix("formulize_procedure_logs_params"), $existingTables)) {
            $sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_procedure_logs_params")."` (
  `proc_log_param_id` int(11) unsigned NOT NULL auto_increment,
  `proc_log_id` int(11) unsigned NOT NULL,
  `proc_log_param` varchar(255),
  `proc_log_value` varchar(255),
  PRIMARY KEY (`proc_log_param_id`),
  INDEX i_proc_log_id (proc_log_id)
) ENGINE=InnoDB;";
        }


        if (!in_array($xoopsDB->prefix("formulize_resource_mapping"), $existingTables)) {
            $sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_resource_mapping")."` (
    mapping_id int(11) NOT NULL auto_increment,
    internal_id int(11) NOT NULL,
    external_id int(11) NULL default NULL,
    resource_type int(4) NOT NULL,
    mapping_active tinyint(1) NOT NULL,
    external_id_string text NULL default NULL,
    PRIMARY KEY (mapping_id),
    INDEX i_internal_id (internal_id),
    INDEX i_external_id (external_id),
    INDEX i_resource_type (resource_type),
    INDEX i_external_id_string (external_id_string(10))
) ENGINE=InnoDB;";
        }

        if (!in_array($xoopsDB->prefix("formulize_deletion_logs"), $existingTables)) {
            $sql[] = "CREATE TABLE ".$xoopsDB->prefix("formulize_deletion_logs")." (
                  del_log_id int(11) unsigned NOT NULL auto_increment,
                  form_id int(11) NOT NULL,
                  entry_id int(7) NOT NULL,
                  user_id mediumint(8) NOT NULL,
                  context text,
                  deletion_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (del_log_id),
                  INDEX i_del_id (del_log_id)
        ) ENGINE=InnoDB;";
        }

        if (!in_array($xoopsDB->prefix("formulize_screen_template"), $existingTables)) {
            $sql[] = "CREATE TABLE " . $xoopsDB->prefix("formulize_screen_template") . " (
            templateid int(11) NOT NULL auto_increment,
            sid int(11) NOT NULL default 0,
            custom_code text NOT NULL,
            donedest varchar(255) NOT NULL default '',
            savebuttontext varchar(255) NOT NULL default '',
            donebuttontext varchar(255) NOT NULL default '',
            template text NOT NULL,
            PRIMARY KEY (`templateid`),
            INDEX i_sid (`sid`)
        ) ENGINE=InnoDB;";
        }
        
        if (!in_array($xoopsDB->prefix("formulize_apikeys"), $existingTables)) {
            $sql[] = "CREATE TABLE " . $xoopsDB->prefix("formulize_apikeys") . " (
                `key_id` int(11) unsigned NOT NULL auto_increment,
                `uid` int(11) NOT NULL default '0',
                `apikey` varchar(255) NOT NULL default '',
                `expiry` datetime default NULL,
                PRIMARY KEY (`key_id`),
                INDEX i_uid (uid),
                INDEX i_apikey (apikey),
                INDEX i_expiry (expiry)
            ) ENGINE=InnoDB;";
        }      


        if (!in_array($xoopsDB->prefix("formulize_passcodes"), $existingTables)) {
            $sql[] = "CREATE TABLE " . $xoopsDB->prefix("formulize_passcodes") . " (
                `passcode_id` int(11) unsigned NOT NULL auto_increment,
                `passcode` text default null,
                `screen` int(11) NOT NULL default '0',
                `expiry` date default NULL,
                `notes` text default NULL,
                PRIMARY KEY (`passcode_id`),
                INDEX i_passcode (passcode(50)),
                INDEX i_screen (screen),
                INDEX i_expiry (expiry)
            ) ENGINE=InnoDB;";
        }

        if (!in_array($xoopsDB->prefix("formulize_screen_calendar"), $existingTables)) {
            $sql[] = "CREATE TABLE " . $xoopsDB->prefix("formulize_screen_calendar"). " (
                `calendar_id` int(11) unsigned NOT NULL auto_increment,
                `sid` int(11) DEFAULT NULL,
                `caltype` varchar(50) DEFAULT NULL,
                `datasets` text DEFAULT NULL,
                PRIMARY KEY (`calendar_id`),
                INDEX i_sid (`sid`)
              ) ENGINE=InnoDB;";
        }
        
        // if this is a standalone installation, then we want to make sure the session id field in the DB is large enough to store whatever session id we might be working with
        if (file_exists(XOOPS_ROOT_PATH."/integration_api.php")) {
            $sql['increase_session_id_size'] = "ALTER TABLE ".$xoopsDB->prefix("session")." CHANGE `sess_id` `sess_id` varchar(60) NOT NULL";
        }

        $googleOnlySql = 'SELECT * FROM '.$xoopsDB->prefix('config').' WHERE conf_name = "auth_googleonly"';
        if($googleOnlyRes = $xoopsDB->query($googleOnlySql) AND $xoopsDB->getRowsNum($googleOnlyRes)===0) {
            $googleOnlySql = 'INSERT INTO '.$xoopsDB->prefix('config').' (conf_modid, conf_catid, conf_name, conf_title, conf_value, conf_desc, conf_formtype, conf_valuetype, conf_order) VALUES (0, 7, "auth_googleonly", "_MD_AM_GOOGLEONLY", 0, "_MD_AM_GOOGLEONLYDSC", "yesno", "int", 1)';
            if(!$googleOnlyRes = $xoopsDB->query($googleOnlySql)) {
                print 'ERROR: could not add Google Only authentication option, using this SQL:<br>'.$googleOnlySql.'<BR>'.$xoopsDB->error().'<BR>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.';
            }
        }

        $sql['add_encrypt'] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_encrypt` tinyint(1) NOT NULL default '0'";
        $sql['add_lockedform'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD `lockedform` tinyint(1) NULL default NULL";
        $sql['drop_from_formulize_id_admin'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " DROP `admin`";
        $sql['drop_from_formulize_id_groupe'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " DROP `groupe`";
        $sql['drop_from_formulize_id_email'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " DROP `email`";
        $sql['drop_from_formulize_id_expe'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " DROP `expe`";
        $sql['drop_from_formulize_id_maxentries'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " DROP `maxentries`";
        $sql['drop_from_formulize_id_even'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " DROP `even`";
        $sql['drop_from_formulize_id_odd'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " DROP `odd`";
        $sql['drop_from_formulize_id_groupscope'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " DROP `groupscope`";
        $sql['drop_from_formulize_id_showviewentries'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " DROP `showviewentries`";
        $sql['add_filtersettings'] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_filtersettings` text NOT NULL";
        $sql['ele_type_100'] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " CHANGE `ele_type` `ele_type` varchar(100) NOT NULL default ''";
        $sql['ele_disabled_text'] = "ALTER TABLE ". $xoopsDB->prefix("formulize") ." CHANGE `ele_disabled` `ele_disabled` text NOT NULL ";
        $sql['ele_display_dropindex'] = "ALTER TABLE ". $xoopsDB->prefix("formulize") ." DROP INDEX `ele_display`";
        $sql['ele_display_text'] = "ALTER TABLE ". $xoopsDB->prefix("formulize") ." CHANGE `ele_display` `ele_display` text NOT NULL ";
        $sql['ele_display_addindex'] = "ALTER TABLE ". $xoopsDB->prefix("formulize") ." ADD INDEX `ele_display` ( `ele_display` ( 255 ) )";
        $sql['sep_to_areamodif'] = "UPDATE ". $xoopsDB->prefix("formulize") ." SET ele_type='areamodif' WHERE ele_type='sep'";
        $sql['add_defaultform'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD `defaultform` int(11) NOT NULL default 0";
        $sql['add_defaultlist'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD `defaultlist` int(11) NOT NULL default 0";
        $sql['add_menutext'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD `menutext` varchar(255) default 'Use the form\'s title'";
        $sql['add_useadvcalcs'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_listofentries") . " ADD `useadvcalcs` varchar(255) NOT NULL default ''";
        $sql['add_not_elementemail'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_notification_conditions") . " ADD `not_cons_elementemail` smallint(5) NOT NULL default 0";
        $sql['add_form_handle'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD `form_handle` varchar(255) NOT NULL";
        $sql['id_form_to_form_handle'] = "UPDATE " . $xoopsDB->prefix("formulize_id") . " SET form_handle = id_form WHERE form_handle IS NULL OR form_handle = ''";
        $sql['add_dedisplay'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_listofentries") . " ADD `dedisplay` int(1) NOT NULL";
        $sql['add_store_revisions'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD `store_revisions` tinyint(1) NOT NULL default '0'";
        $sql['add_finishisdone'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_multipage") . " ADD `finishisdone` tinyint(1) NOT NULL default 0";
        $sql['add_formelements'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_form") . " ADD `formelements` text";
        $sql['add_on_before_save'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD `on_before_save` text";
        $sql['add_on_after_save'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD `on_after_save` text";
        $sql['add_custom_edit_check'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD `custom_edit_check` text";
        $sql['add_form_note'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD `note` text";
        $sql['add_use_default_when_blank'] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_use_default_when_blank` tinyint(1) NOT NULL default '0'";
        $sql['add_global_search_to_saved_view'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " ADD `sv_global_search` text";
        $sql['add_application_code'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_applications") . " ADD `custom_code` mediumtext";
        $sql['add_note_to_menu_links']="ALTER TABLE ".$xoopsDB->prefix("formulize_menu_links")." ADD `note` text";
        $sql['add_pubfilters'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " ADD `sv_pubfilters` text";
        $sql['add_backdrop_group'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_resource_mapping") . " ADD external_id_string text NULL default NULL";
        $sql['add_backdrop_group_index'] = "ALTER TABLE ". $xoopsDB->prefix("formulize_resource_mapping") ." ADD INDEX i_external_id_string (external_id_string(10))";
        $sql['add_advance_view_field'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_listofentries") . " ADD `advanceview` text NOT NULL"; 
		$sql['defaultview_ele_type_text'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_listofentries") . " CHANGE `defaultview` `defaultview` TEXT NOT NULL ";
        $sql['add_ele_uitextshow'] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_uitextshow` tinyint(1) NOT NULL default 0";
        $sql['add_send_digests'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD send_digests tinyint(1) NOT NULL default 0";
        $sql['add_template_donedest'] = "ALTER TABLE ". $xoopsDB->prefix("formulize_screen_template") . " ADD `donedest` varchar(255) NOT NULL default ''";
        $sql['add_template_savebuttontext'] = "ALTER TABLE ". $xoopsDB->prefix("formulize_screen_template") . " ADD `savebuttontext` varchar(255) NOT NULL default ''";
        $sql['add_template_donebuttontext'] = "ALTER TABLE ". $xoopsDB->prefix("formulize_screen_template") . " ADD `donebuttontext` varchar(255) NOT NULL default ''";
        $sql['add_ele_exportoptions'] = "ALTER TABLE ". $xoopsDB->prefix("formulize")." ADD `ele_exportoptions` text NOT NULL";
        $sql['add_fundamental_filters'] = "ALTER TABLE ".$xoopsDB->prefix('formulize_screen_listofentries')." ADD `fundamental_filters` text NOT NULL";
        $sql['add_screen_anonNeedsPasscode'] = "ALTER TABLE ".$xoopsDB->prefix('formulize_screen')." ADD `anonNeedsPasscode` tinyint(1) NOT NULL";
        $sql['add_navstyle'] = "ALTER TABLE ".$xoopsDB->prefix('formulize_screen_multipage')." ADD `navstyle` tinyint(1) NOT NULL default 0";
        $sql['form_screen_elementdefaults'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_screen_form") . " ADD `elementdefaults` text NOT NULL";
        $sql['form_screen_displaycolumns'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_screen_form") . " ADD `displaycolumns` tinyint(1) NOT NULL default 2";
        $sql['form_screen_column1width'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_screen_form") . " ADD `column1width` varchar(255) NULL default NULL";
        $sql['form_screen_column2width'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_screen_form") . " ADD `column2width` varchar(255) NULL default NULL";
        $sql['form_screen_multipage_displaycolumns'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_screen_multipage") . " ADD `displaycolumns` tinyint(1) NOT NULL default 2";
        $sql['form_screen_multipage_column1width'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_screen_multipage") . " ADD `column1width` varchar(255) NULL default NULL";
        $sql['form_screen_multipage_column2width'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_screen_multipage") . " ADD `column2width` varchar(255) NULL default NULL";
        $sql['form_screen_saveandleave'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_screen_form"). " ADD `saveandleavebuttontext` varchar(255) NOT NULL default ''";
        $sql['form_screen_printableview'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_screen_form"). " ADD `printableviewbuttontext` varchar(255) NOT NULL default ''";
        $sql['rm_ext_id_null'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_resource_mapping") . " CHANGE `external_id` `external_id` INT(11) NULL DEFAULT NULL";
        $sql['sliderfix'] = "UPDATE " . $xoopsDB->prefix("formulize") . " SET ele_type = 'slider' WHERE ele_type = 'newslider'";
        $sql['buttontexttext'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_multipage") . " CHANGE `buttontext` `buttontext` TEXT NULL DEFAULT NULL";
        $sql['form_screen_multipage_showpagetitles'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_screen_multipage") . " ADD `showpagetitles` tinyint(1) NOT NULL";
        $sql['form_screen_multipage_showpageselector'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_screen_multipage") . " ADD `showpageselector` tinyint(1) NOT NULL";
        $sql['form_screen_multipage_showpageindicator'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_screen_multipage") . " ADD `showpageindicator` tinyint(1) NOT NULL";
        $sql['screen_theme'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_screen"). " ADD `theme` varchar(101) NOT NULL default ''";
        $sql['form_screen_displaytype'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_screen_form") . " ADD `displayType` varchar(255) NOT NULL default 'block'";
        $sql['form_screen_multipage_displayheading'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_screen_multipage") . " ADD `displayheading` tinyint(1) NOT NULL default 0";
        $sql['form_screen_multipage_reloadblank'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_screen_multipage") . " ADD `reloadblank` tinyint(1) NOT NULL default 0";
        $sql['form_screen_multipage_elementdefaults'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_screen_multipage") . " ADD `elementdefaults` text NOT NULL";
        $sql['not_cons_arbitrary'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_notification_conditions") . " ADD `not_cons_arbitrary` text NULL default NULL";
        $sql['screen_theme_change'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_screen"). " CHANGE `theme` `theme` varchar(101) NOT NULL default ''";
        $sql['element_sort'] = "ALTER TABLE ".$xoopsDB->prefix("formulize") . " ADD `ele_sort` smallint(2) NULL default NULL";
        $sql['on_delete'] = "ALTER TABLE ".$xoopsDB->prefix("formulize_id") . " ADD `on_delete` text";

        $needToSetSaveAndLeave = true;
        $needToSetPrintableView = true;
        $needToMigrateFormScreensToMultipage = true;
        foreach($sql as $key=>$thissql) {
            if (!$result = $xoopsDB->query($thissql)) {
                if ($key === "add_encrypt") {
                    print "ele_encrypt field already added.  result: OK<br>";
                } elseif ($key === "add_lockedform") {
                    print "lockedform field already added.  result: OK<br>";
                } elseif ($key === "add_filtersettings") {
                    print "element filtersettings field already added.  result: OK<br>";
                } elseif ($key == 'ele_display_dropindex' OR $key == 'ele_display_addindex') {
                    print "ele_display index already handled. result: OK<br>";
                } elseif ($key === "add_defaultform") {
                    print "defaultform field already added.  result: OK<br>";
                } elseif ($key === "add_defaultlist") {
                    print "defaultlist field already added.  result: OK<br>";
                } elseif ($key === "add_menutext") {
                    print "menutext field already added.  result: OK<br>";
                } elseif ($key === "add_useadvcalcs") {
                    print "useadvcalcs field already added.  result: OK<br>";
                } elseif ($key === "add_not_elementemail") {
                    print "elementemail notification option already added.  result: OK<br>";
                } elseif ($key === "add_form_handle") {
                    print "form handles already added.  result: OK<br>";
                } elseif ($key === "add_dedisplay") {
                    print "dedisplay already added.  result: OK<br>";
                } elseif ($key === "add_store_revisions") {
                    print "store_revisions already added.  result: OK<br>";
                } elseif ($key === "add_finishisdone") {
                    print "finishisdone for multipage forms already added.  result: OK<br>";
                } elseif ($key === "add_formelements") {
                    print "formelements field already added for single page screens.  result: OK<br>";
                } elseif ($key === "add_on_before_save") {
                    print "on_before_save field already added.  result: OK<br>";
                } elseif ($key === "add_on_after_save") {
                    print "on_after_save field already added.  result: OK<br>";
                } elseif ($key === "add_custom_edit_check") {
                    print "custom_edit_check field already added.  result: OK<br>";
                } elseif ($key === "add_form_note") {
                    print "form note field already added.  result: OK<br>";
                } elseif ($key === "add_use_default_when_blank") {
                    print "use default when blank already added.  result: OK<br>";
                } elseif ($key === "add_global_search_to_saved_view") {
                    print "global search saved view already added.  result: OK<br>";
                } elseif ($key === "add_application_code") {
                    print "application custom_code field added.  result: OK<br>";
                } elseif ($key === "add_note_to_menu_links") {
                    print "note already added for menu links.  result: OK<br>";
                } elseif (strstr($key, 'drop_from_formulize_id_')) {
                    continue;
                } elseif(strstr($key, 'add_pubfilters')) {
                    print "Pubfilters field already added.  result: OK<br>";
                } elseif(strstr($key, 'add_backdrop_group')) {
                    print "External_id_string already added for resource mapping.  result: OK<br>";
                } elseif(strstr($key, 'add_backdrop_group_index')) {
                    print "External_id_string INDEX already added for resource mapping.  result: OK<br>";
                } elseif($key === "defaultview_ele_type_text") {
					print "default view field change to text type already. result: OK<br>";
				} elseif($key === "add_advance_view_field") {
					print "advance view field already added.  result: OK<br>";
                } elseif($key === "add_ele_uitextshow") {
                    print "Option for showing UI Text already added. result: OK<br>";
                } elseif($key === "add_send_digests") {
                    print "Option for sending digest notifications already added. result: OK<br>";
                } elseif(strstr($key, 'add_template_')) {
					print "Button options already added to Template screens.  result: OK<br>";
                } elseif($key === 'add_ele_exportoptions') {
                    print "Element export options already added. result: OK<br>";
                } elseif($key === "add_fundamental_filters") {
                    print "Fundamental filters for list screens already added. result: OK<br>";
                } elseif($key === "add_screen_anonNeedsPasscode") {
                    print "Anon pass codes for screens already added. result: OK<br>";
                } elseif($key === "add_navstyle") {
                    print "Navigation Style already added for multipage screens. result: OK<br>";
                } elseif($key === "form_screen_elementdefaults") {
                    print "Form Screen element defaults already added. result: OK<br>";
                } elseif($key === "form_screen_displaycolumns") {
                    print "Form Screen element display columns option already added. result: OK<br>";
                } elseif($key === "form_screen_column1width" OR $key === "form_screen_column2width") {
                    print "Form screen column widths already added. result: OK<br>";
                } elseif($key === "form_screen_saveandleave") {
                    print "Form screen save and leave text option already added. result: OK<br>";
                    $needToSetSaveAndLeave = false;
                } elseif($key === "form_screen_printableview") {
                    print "Form screen printable view text option already added. result: OK<br>";
                    $needToSetPrintableView = false;
                } elseif($key === "form_screen_multipage_column1width" OR $key === "form_screen_multipage_column2width" OR $key === "form_screen_multipage_displaycolumns") {
                    print "Multipage form screen display columns and column widths already added. result: OK<br>";
                } elseif($key === "form_screen_multipage_showpagetitles" OR $key === "form_screen_multipage_showpageselector" OR $key === "form_screen_multipage_showpageindicator") {
                    print "Multipage form screen UI controls already added. result OK<br>";
                } elseif($key === "screen_theme") {
                    print "Theme setting for screens already added. result: OK<br>";
                } elseif($key === "form_screen_displaytype") {    
                    print "Form screen element container display type already added. result: OK<br>";
                } elseif($key === "form_screen_multipage_displayheading") {
                    print "Multipage screens displayheading already added. result: OK<br>";
                    $needToMigrateFormScreensToMultipage = false;
                } elseif($key === "form_screen_multipage_reloadblank") {
                    print "Multipage screens reloadblank already added. result: OK<br>";
                    $needToMigrateFormScreensToMultipage = false;
                } elseif($key === "form_screen_multipage_elementdefaults") {
                    print "Multipage screens elementdefaults already added. result: OK<br>";
                    $needToMigrateFormScreensToMultipage = false;
                } elseif($key === "not_cons_arbitrary") {
                    print "Arbitrary email already added to notification options. result: OK<br>";
                } elseif($key === "element_sort") {
                    print "Element sorting order already added. result: OK<br>";
                } elseif($key === "on_delete") {
                    print "On Delete already added. result: OK<br>";
                } else {
                    exit("Error patching DB for Formulize $versionNumber. SQL dump:<br>" . $thissql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
                }
            }
        }
        
        global $xoopsConfig;
        $themeSql = 'UPDATE '.$xoopsDB->prefix('formulize_screen').' SET theme = "'.$xoopsConfig['theme_set'].'" WHERE theme = ""';
        if(!$res = $xoopsDB->query($themeSql)) {
            exit("Error patching DB for Formulize $versionNumber. Could not update screens with default theme. SQL dump:<br>".$themeSql."<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
        }
        
        $newConfigSQL = array();
        $sql = "SELECT * FROM ".$xoopsDB->prefix("config")." WHERE conf_name = 'auth_2fa'";
        if($res = $xoopsDB->query($sql)) {
            if($xoopsDB->getRowsNum($res)==0) {
                $newConfigSQL[] = "INSERT INTO ".$xoopsDB->prefix("config")." (`conf_modid`, `conf_catid`, `conf_name`, `conf_title`, `conf_value`, `conf_desc`, `conf_formtype`, `conf_valuetype`, `conf_order`) VALUES (0, 7, 'auth_2fa', '_MD_AM_AUTH2FA', '0', '_MD_AM_AUTH2FADESC', 'yesno', 'int', 1)";
                $newConfigSQL[] = "INSERT INTO ".$xoopsDB->prefix("config")." (`conf_modid`, `conf_catid`, `conf_name`, `conf_title`, `conf_value`, `conf_desc`, `conf_formtype`, `conf_valuetype`, `conf_order`) VALUES (0, 7, 'auth_2fa_groups', '_MD_AM_AUTH2FAGROUPS', '', '_MD_AM_AUTH2FAGROUPSDESC', 'group_multi', 'array', 1)";
            }
        }
        $sql = "SELECT * FROM ".$xoopsDB->prefix("config")." WHERE conf_name = 'auth_okta'";
        if($res = $xoopsDB->query($sql)) {
            if($xoopsDB->getRowsNum($res)==0) {
                $newConfigSQL[] = "INSERT INTO ".$xoopsDB->prefix("config")." (`conf_modid`, `conf_catid`, `conf_name`, `conf_title`, `conf_value`, `conf_desc`, `conf_formtype`, `conf_valuetype`, `conf_order`) VALUES (0, 7, 'auth_okta', '_MD_AM_AUTHOKTA', '', '_MD_AM_AUTHOKTADESC', 'textbox', 'text', 1)";
            }
        }
        foreach($newConfigSQL as $sql) {
            if(!$xoopsDB->query($sql)) {
                exit("Error patching DB for Formulize $versionNumber. SQL dump:<br>" . $sql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
            }
        }
        // get profile module ID
        $profileModIdSQL = "SELECT mid FROM ".$xoopsDB->prefix("modules")." WHERE dirname='profile'";
        $profileModIdRes = $xoopsDB->query($profileModIdSQL);
        $profileModIdRow = $xoopsDB->fetchRow($profileModIdRes);
        $profileModId = $profileModIdRow[0];
        $sql = "SELECT * FROM ".$xoopsDB->prefix("profile_field")." WHERE field_name = '2famethod'";
        if($res = $xoopsDB->query($sql)) {
            if($xoopsDB->getRowsNum($res)==0) {
                $sql = "INSERT INTO ".$xoopsDB->prefix("profile_field")." (`catid`, `field_type`, `field_valuetype`, `field_name`, `field_title`, `url`, `field_description`, `field_required`, `field_maxlength`, `field_weight`, `field_default`, `field_notnull`, `field_edit`, `field_show`, `field_options`, `exportable`, `step_id`, `system`) VALUES (0, 'select', '3', '2famethod', '2-factor authentication method', '', '', 0, '0', 7, '', 1, 1, 1, 'a:4:{i:0;s:8:\"--None--\";i:1;s:14:\"Text me a code\";i:2;s:15:\"Email me a code\";i:3;s:24:\"Use an authenticator app\";}', 1, 1, 1)";
                if($res = $xoopsDB->query($sql)) {
                    $profileId = $xoopsDB->getInsertId();
                    $sql = "INSERT INTO ".$xoopsDB->prefix("profile_visibility")." (`fieldid`, `user_group`, `profile_group`) VALUES ($profileId, 1, 0)";
                    if(!$res = $xoopsDB->query($sql)) {
                        exit("Error patching DB for Formulize $versionNumber. SQL dump:<br>" . $sql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
                    }
                    $sql = "INSERT INTO ".$xoopsDB->prefix("group_permission")." (`gperm_groupid`, `gperm_itemid`, `gperm_modid`, `gperm_name`) VALUES (2, $profileId, $profileModId, 'profile_edit')";
                    $sql = "ALTER TABLE ".$xoopsDB->prefix("profile_profile")." ADD `2famethod` INT NULL DEFAULT NULL";
                    if(!$res = $xoopsDB->query($sql)) {
                        exit("Error patching DB for Formulize $versionNumber. SQL dump:<br>" . $sql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
                    }
                } else {
                    exit("Error patching DB for Formulize $versionNumber. SQL dump:<br>" . $sql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
                }
            }
        }
        $sql = "SELECT * FROM ".$xoopsDB->prefix("profile_field")." WHERE field_name = '2faphone'";
        if($res = $xoopsDB->query($sql)) {
            if($xoopsDB->getRowsNum($res)==0) {
                $sql = "INSERT INTO ".$xoopsDB->prefix("profile_field")." (`catid`, `field_type`, `field_valuetype`, `field_name`, `field_title`, `url`, `field_description`, `field_required`, `field_maxlength`, `field_weight`, `field_default`, `field_notnull`, `field_edit`, `field_show`, `field_options`, `exportable`, `step_id`, `system`) VALUES (0, 'textbox', '1', '2faphone', 'Phone Number', '', '', 0, '255', 8, '', 1, 1, 1, 'a:0:{}', 1, 2, 1)";
                if($res = $xoopsDB->query($sql)) {
                    $profileId = $xoopsDB->getInsertId();
                    $sql = "INSERT INTO ".$xoopsDB->prefix("profile_visibility")." (`fieldid`, `user_group`, `profile_group`) VALUES ($profileId, 1, 0)";
                    if(!$res = $xoopsDB->query($sql)) {
                        exit("Error patching DB for Formulize $versionNumber. SQL dump:<br>" . $sql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
                    }
                    $sql = "ALTER TABLE ".$xoopsDB->prefix("profile_profile")." ADD `2faphone` VARCHAR(15) NULL DEFAULT NULL";
                    if(!$res = $xoopsDB->query($sql)) {
                        exit("Error patching DB for Formulize $versionNumber. SQL dump:<br>" . $sql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
                    }
                } else {
                    exit("Error patching DB for Formulize $versionNumber. SQL dump:<br>" . $sql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
                }
            }
        }
        $sql = "SELECT * FROM ".$xoopsDB->prefix("profile_field")." WHERE field_name = '2fadevices'";
        if($res = $xoopsDB->query($sql)) {
            if($xoopsDB->getRowsNum($res)==0) {
                $sql = "INSERT INTO ".$xoopsDB->prefix("profile_field")." (`catid`, `field_type`, `field_valuetype`, `field_name`, `field_title`, `url`, `field_description`, `field_required`, `field_maxlength`, `field_weight`, `field_default`, `field_notnull`, `field_edit`, `field_show`, `field_options`, `exportable`, `step_id`, `system`) VALUES (0, 'textarea', '2', '2fadevices', 'Devices', '', '', 0, '0', 9, '', 1, 1, 0, 'a:0:{}', 1, 2, 1)";
                if($res = $xoopsDB->query($sql)) {
                    $sql = "ALTER TABLE ".$xoopsDB->prefix("profile_profile")." ADD `2fadevices` TEXT NULL DEFAULT NULL";
                    if(!$res = $xoopsDB->query($sql)) {
                        exit("Error patching DB for Formulize $versionNumber. SQL dump:<br>" . $sql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
                    }
                } else {
                    exit("Error patching DB for Formulize $versionNumber. SQL dump:<br>" . $sql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
                }
            }
        }
        
        // ADD FONT SIZE OPTION TO PROFILE
        $sql = "SELECT * FROM ".$xoopsDB->prefix("profile_field")." WHERE field_name = 'fontsize'";
        if($res = $xoopsDB->query($sql)) {
            if($xoopsDB->getRowsNum($res)==0) {
                $sql = "INSERT INTO ".$xoopsDB->prefix("profile_field")." (`catid`, `field_type`, `field_valuetype`, `field_name`, `field_title`, `url`, `field_description`, `field_required`, `field_maxlength`, `field_weight`, `field_default`, `field_notnull`, `field_edit`, `field_show`, `field_options`, `exportable`, `step_id`, `system`) VALUES (0, 'textbox', '1', 'fontsize', 'Font Size', '', '', 0, '255', 7, '', 1, 1, 1, '', 1, 1, 1)";
                if($res = $xoopsDB->query($sql)) {
                    $profileId = $xoopsDB->getInsertId();
                    $sql = "INSERT INTO ".$xoopsDB->prefix("profile_visibility")." (`fieldid`, `user_group`, `profile_group`) VALUES ($profileId, 2, 0)";
                    if(!$res = $xoopsDB->query($sql)) {
                        exit("Error patching DB for Formulize $versionNumber. SQL dump:<br>" . $sql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
                    }
                    $sql = "INSERT INTO ".$xoopsDB->prefix("group_permission")." (`gperm_groupid`, `gperm_itemid`, `gperm_modid`, `gperm_name`) VALUES (2, $profileId, $profileModId, 'profile_edit')";
                    if(!$res = $xoopsDB->query($sql)) {
                        exit("Error patching DB for Formulize $versionNumber. SQL dump:<br>" . $sql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
                    }
                    $sql = "ALTER TABLE ".$xoopsDB->prefix("profile_profile")." ADD `fontsize` varchar(255) NULL DEFAULT NULL";
                    if(!$res = $xoopsDB->query($sql)) {
                        exit("Error patching DB for Formulize $versionNumber. SQL dump:<br>" . $sql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
                    }
                } else {
                    exit("Error patching DB for Formulize $versionNumber. SQL dump:<br>" . $sql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
                }
            }
        }
        
        // if this is the first time we're adding the saveandleave and printable view options... set the values to the language constants
        if($needToSetSaveAndLeave) {
            $sql = "UPDATE ".$xoopsDB->prefix("formulize_screen_form"). " SET saveandleavebuttontext = '"._formulize_SAVE_AND_LEAVE."'";
            $xoopsDB->query($sql);
        }
        if($needToSetPrintableView) {
            $sql = "UPDATE ".$xoopsDB->prefix("formulize_screen_form"). " SET printableviewbuttontext = '"._formulize_PRINTVIEW."'";
            $xoopsDB->query($sql);
        }
        
        
        // change any non-serialized array defaultview settings for list of entries screens, into serialized arrays indicating the view for Registered Users (group 2)
        $sql1 = "UPDATE ".$xoopsDB->prefix("formulize_screen_listofentries")." SET defaultview = CONCAT('a:1:{i:2;i:',defaultview,';}') WHERE defaultview NOT LIKE '%{%' AND defaultview != 'b:0;' AND concat('',defaultview * 1) = defaultview"; //concat in where isolates numbers
        if(!$res = $xoopsDB->query($sql1)) {
            exit("Error patching DB for Formulize $versionNumber. SQL dump:<br>" . $sql1 . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
        }
        $sql2 = "UPDATE ".$xoopsDB->prefix("formulize_screen_listofentries")." SET defaultview = CONCAT('a:1:{i:2;s:',CHAR_LENGTH(defaultview),':\"',defaultview,'\";}') WHERE defaultview NOT LIKE '%{%'"; // all remaining values will not be numbers
        if(!$res = $xoopsDB->query($sql2)) {
            exit("Error patching DB for Formulize $versionNumber. SQL dump:<br>" . $sql2 . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
        }

        // if there is a framework handles table present, then we need to check for a few things to ensure the integrity of code and our ability to disambiguate inputs to the API
        if (in_array($xoopsDB->prefix("formulize_framework_elements"), $existingTables)) {
            // need to change rules...framework handles must now be globally unique, so we can disambiguate them from each other when we are passed just a framework handle
            $uniqueSQL = "SELECT elements.ele_caption, elements.ele_id, elements.ele_handle, handles.fe_handle, handles.fe_frame_id FROM ".$xoopsDB->prefix("formulize")." as elements, ".$xoopsDB->prefix("formulize_framework_elements")." as handles WHERE EXISTS (SELECT 1 FROM ".$xoopsDB->prefix("formulize_framework_elements")." as checkhandles WHERE handles.fe_handle = checkhandles.fe_handle AND handles.fe_element_id != checkhandles.fe_element_id) AND handles.fe_element_id = elements.ele_id AND handles.fe_handle != \"\" ORDER BY handles.fe_handle";
            $uniqueRes = $xoopsDB->query($uniqueSQL);
            $haveWarning = false;
            $warningIdentifier = array();
            $warningContents = array();
            if ($xoopsDB->getRowsNum($uniqueRes)) {
                $haveWarning = true;
                $warningIdentifier[] = "<li>You have some \"framework handles\" which are the same between different frameworks.</li>";
                ob_start();
                print "<ul>";
                $prevHandle = "";
                while($uniqueArray = $xoopsDB->fetchArray($uniqueRes)) {
                    if ($uniqueArray['fe_handle'] != $prevHandle) {
                        if ($prevHandle != "") {
                            // need to finish previous set and print out what's missing
                            print "</li>";
                        }
                        print "<li>Framework handle: <b>".$uniqueArray['fe_handle']."</b> is used in more than one place:<br>";
                    }
                    $prevHandle = $uniqueArray['fe_handle'];
                    print "&nbsp;&nbsp;&nbsp;&nbsp;In framework ".$uniqueArray['fe_frame_id'].", it is used for element ".$uniqueArray['ele_id']." (".$uniqueArray['ele_caption'].")<br>";
                    if ($uniqueArray['fe_handle'] != $uniqueArray['ele_handle']) {
                        print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;For element ".$uniqueArray['ele_id'].", use the element's data handle instead: <b>".$uniqueArray['ele_handle']."</b><br>";
                    }
                }
                // dump the last stuff we had found in the loop
                print "</li>";
                print "</ul>";
                $warningContents[] = ob_get_clean();
            }

            // need to disambiguate framework handles and elements' data handles.
            // no framework handle can be identical to the text of any data handle, unless they refer to the same element
            // So look up all the elements that have a data handle that matches a framework handle, which is not referring to the same element

            $handleSQL = "SELECT elements.ele_id, elements.ele_caption, elements.ele_handle, handles.fe_frame_id, handles.fe_handle, handles.fe_element_id, e2.ele_caption as handlecap, e2.ele_handle as newhandle FROM ".$xoopsDB->prefix("formulize")." AS elements, ".$xoopsDB->prefix("formulize_framework_elements")." AS handles, ".$xoopsDB->prefix("formulize")." AS e2 WHERE elements.ele_handle = handles.fe_handle AND handles.fe_element_id != elements.ele_id AND handles.fe_element_id = e2.ele_id ORDER BY elements.id_form, elements.ele_order";
            $handleRes = $xoopsDB->query($handleSQL);
            if ($xoopsDB->getRowsNum($handleRes) > 0) {
                $haveWarning = true;
                $warningIdentifier[] = "<li>You have some \"data handles\" which are identical to the \"framework handles\" of other elements.</li>";
                ob_start();
                print "<ul>";
                while($handleArray = $xoopsDB->fetchArray($handleRes)) {
                    print "<li>".$handleArray['handlecap']." (element ".$handleArray['fe_element_id'].") &mdash framework handle: <b>".$handleArray['fe_handle']."</b> in framework ".$handleArray['fe_frame_id']."<br>";
                    print "&nbsp;&nbsp;&nbsp;&nbsp;Use the element's data handle instead: <b>".$handleArray['newhandle']."</b></li>";
                }
                print "</ul>";
                $warningContents[] = ob_get_clean();
            }
        }

        if ($haveWarning) {
            print "<hr><p>MAJOR WARNING:</p>";
            print "<ol>";
            print implode("\n", $warningIdentifier);
            print "</ol>";
            print "<p>Framework handles are deprecated in Formulize 4, and having framework handles that are not entirely unique can now lead to serious errors in some situations.  However, we cannot automatically fix this situation for you, because you may have used the framework handles in programming code in your website.  If we try an automatic fix, we could break some other parts of your website.</p><p>Recommended actions:</p><p>1. Note down the following framework handles for the following elements (copy this information to a file, print this page, etc):<br>";
            print implode("\n", $warningContents);
            print "</p><p>2. Determine if you have any saved views based on a framework, that include the elements (columns) mentioned above.</p>";
            print "<p>3. For any elements mentioned above that are included in framework-based saved views, change their framework handles as suggested above.</p>";
            print "<p>4. Open up the affected saved views, and re-add the changed elements (columns) to them.  Search terms on changed columns will need to be respecified too, as well as sorting options.  Then re-save the view.  <b>Your users will need to do this too, if they have personal saved views which you cannot access.</b></p>";
            print "<p>5. Determine where you may be using the <b>framework handles</b> mentioned above in any programming code in your website.</p>";
            print "<p>6. For any elements where you are using the framework handles in programming code, change those framework handles as suggested above. You will need to make these changes in the framework configuration settings, as well as in the actual programming code where you are referring to the handle.</p>";
            print "<p> --- </p>";
            print "<p><b>You probably don't need to make all of the changes suggested above!</b>  You only need to make changes in places where there are saved views and/or programming code that uses the elements mentioned above.  For some websites, that will mean you don't have to make any changes (ie: if there are no saved views based on a framework, and you have no programming code referring to frameworks).</p>";
            print "<p> --- </p>";
            print "<p><b>You can re-run this patch after making changes.  If you do not get this warning, then your site should be OK.</b></p>";
            print "<p>If you have any questions about this upgrade issue, please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.</p>";
        }

        //create new menus table
        if (!in_array($xoopsDB->prefix("formulize_menu_links"), $existingTables)) {
            $menusql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_menu_links")."` (
            `menu_id` int(11) unsigned NOT NULL auto_increment,
            `appid` int(11) unsigned NOT NULL,
            `screen` varchar(11),
            `rank` int(11),
            `url` varchar(255),
            `link_text` varchar(255),
            `note` text,
            PRIMARY KEY (`menu_id`),
            INDEX i_menus_appid (appid)
            );";

            $menusql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_menu_permissions")."` (
            `permission_id` int(11) unsigned NOT NULL auto_increment,
            `menu_id` int(11) unsigned NOT NULL,
            `group_id` int(11) unsigned NOT NULL,
            `default_screen` tinyint(1) NOT NULL default '0',
            PRIMARY KEY (`permission_id`),
            INDEX i_menu_permissions (menu_id)
            );";
            print("Creating new menu_links and menu_permissions tables...<br>");
            foreach($menusql as $key=>$thissql) {

                if (!$result = $xoopsDB->query($thissql)) {
                    exit("Error patching DB for Formulize $versionNumber. SQL dump:<br>" . $thissql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
                }
            }
            // populate new menus tables with existing menu entries
            $application_handler = xoops_getmodulehandler('applications', 'formulize');

            $form_handler = xoops_getmodulehandler('forms', 'formulize');
            $allApplications = $application_handler->getAllApplications();
            $menuTexts = array();
            $i = 0;
            foreach($allApplications as $thisApplication) {
                $forms = $thisApplication->getVar('forms');
                foreach($forms as $thisForm) {
                    $thisFormObject = $form_handler->get($thisForm);
                    if ($menuText = $thisFormObject->getVar('menutext')) {
                saveMenuEntryAndPermissionsSQL($thisFormObject->getVar('id_form'),$thisApplication->getVar("appid"),$i,$menuText);
                    }
                    $i++;
                }
                $i=0;
            }

            $formsWithNoApplication = $form_handler->getFormsByApplication(0,true); // true forces ids not objects to be returned
            foreach($formsWithNoApplication as $thisForm) {
                $thisFormObject = $form_handler->get($thisForm);
                if ($menuText = $thisFormObject->getVar('menutext')) {
                saveMenuEntryAndPermissionsSQL($thisFormObject->getVar('id_form'),0,$i,$menuText);
                }
                $i++;
            }
        }

        // need to update multiple select boxes for new data structure
        // $xoopsDB->prefix("formulize")
        // 1. get a list of all elements that are linked selectboxes that support only single values
        $selectBoxesSQL = "SELECT e.id_form as id_form, e.ele_id as ele_id, e.ele_handle as ele_handle, f.form_handle as form_handle FROM " . $xoopsDB->prefix("formulize") . " AS e LEFT JOIN ". $xoopsDB->prefix("formulize_id") . " AS f ON e.id_form = f.id_form WHERE ele_type = 'select'";
        $selectBoxRes = $xoopsDB->query($selectBoxesSQL);
        if ($xoopsDB->getRowsNum($selectBoxRes) > 0) {
            while ($handleArray = $xoopsDB->fetchArray($selectBoxRes)) {
                $metaData = formulize_getElementMetaData($handleArray['ele_id']);
                $ele_value = unserialize($metaData['ele_value']);

                // select only single option, linked select boxes, and not snapshot boxes!
                if (!$ele_value['snapshot'] AND !$ele_value[1] AND is_string($ele_value[2]) AND strstr($ele_value[2], "#*=:*")) {
                    $successSelectBox = convertSelectBoxToSingle($xoopsDB->prefix('formulize_' . $handleArray['form_handle']), $handleArray['ele_handle']);
                    if (!$successSelectBox) {
                        print "could not convert column " . $handleArray['ele_handle'] . " in table " . $xoopsDB->prefix('formulize_' . $handleArray['form_handle']) . "<br>";
                    }
                }
            }
        }

        // if the relationship link option, unified_delete, does not exist, create the field and default to the unified_display setting value
        $sql = $xoopsDB->query("show columns from ".$xoopsDB->prefix("formulize_framework_links")." where Field = 'fl_unified_delete'");
        if (0 == $xoopsDB->getRowsNum($sql)) {
            $sql = "ALTER TABLE " . $xoopsDB->prefix("formulize_framework_links") . " ADD `fl_unified_delete` smallint(5)";
            if ($udres1 = $xoopsDB->query($sql)) {
            $sql = "update " . $xoopsDB->prefix("formulize_framework_links") . " set `fl_unified_delete` = `fl_unified_display`";
            $udres2 = $xoopsDB->query($sql);
            }
            if (!$udres1 OR !$udres2) {
            print "Error updating relationships with unified delete option.  SQL dump:<br>" . $thissql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.";
            } else {
            print "Updating relationships with unified delete option.  result: OK<br>";
            }
        }
        
        $screenpathname = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/".$xoopsConfig['theme_set']."/";
        
        // If current theme folder does not exists for templates, then create it and copy the default folder contents over to it.
        if(!file_exists($screenpathname)) {
            recurse_copy(XOOPS_ROOT_PATH."/modules/formulize/templates/screens/default/",$screenpathname);
        }

        // CONVERTING EXISTING TEMPLATES IN DB TO TEMPLATE FILES
        // Only kicks in if the template fields are still present in the relevant tables, otherwise queries fail
        $templateSQL = "SELECT l.sid as sid, l.toptemplate as toptemplate, l.listtemplate as listtemplate, l.bottomtemplate as bottomtemplate, s.theme as theme
            FROM ".$xoopsDB->prefix("formulize")."_screen_listofentries AS l
            LEFT JOIN ".$xoopsDB->prefix("formulize")."_screen AS s ON l.sid = s.sid";
        if($templateRes = $xoopsDB->query($templateSQL)) {
            if ($xoopsDB->getRowsNum($templateRes) > 0) {
                while($handleArray = $xoopsDB->fetchArray($templateRes)) {
                    if (!file_exists($screenpathname.$handleArray['sid'])) {
                        $pathname = $screenpathname.$handleArray['sid']."/";
                        mkdir($pathname, 0777, true);
                    }
                    if (!is_writable($pathname)) {
                        chmod($pathname, 0777);
                    }
                    saveTemplate($handleArray['toptemplate'], $handleArray['sid'], "toptemplate", $handleArray['theme']);
                    saveTemplate($handleArray['bottomtemplate'], $handleArray['sid'], "bottomtemplate", $handleArray['theme']);
                    saveTemplate($handleArray['listtemplate'], $handleArray['sid'], "listtemplate", $handleArray['theme']);
                    // if the screen is complex enough to have a complete set of templates, then add placeholder templates so that the defaults are not used for open and close
                    // repeat for additional themes as necessary, on the assumption that the screen would be complex enough in all themes
                    if($handleArray['toptemplate'] AND $handleArray['bottomtemplate'] AND $handleArray['listtemplate']) {
                        saveTemplate("// Placeholder because this older screen predates the use of the Open List Template", $handleArray['sid'], "openlisttemplate", $handleArray['theme']);
                        saveTemplate("// Placeholder because this older screen predates the use of the Close List Template", $handleArray['sid'], "closelisttemplate", $handleArray['theme']);
                        if(isset($_GET['additional_themes'])) {
                            foreach(explode(',',$_GET['additional_themes']) as $additional_theme) {
                                $additional_theme = preg_replace("/[^a-zA-Z0-9_-]+/", "", $additional_theme);
                                if($additional_theme != $handleArray['theme']) {
                                    saveTemplate("// Placeholder because this older screen predates the use of the Open List Template", $handleArray['sid'], "openlisttemplate", $additional_theme);
                                    saveTemplate("// Placeholder because this older screen predates the use of the Close List Template", $handleArray['sid'], "closelisttemplate", $additional_theme);
                                }    
                            }
                        }
                    }
                }
            }
        }
        $multitemplateSQL = "SELECT m.sid as sid, m.toptemplate as toptemplate, m.elementtemplate as elementtemplate, m.bottomtemplate as bottomtemplate, s.theme as theme
            FROM ".$xoopsDB->prefix("formulize")."_screen_multipage AS m
            LEFT JOIN ".$xoopsDB->prefix("formulize")."_screen AS s ON m.sid = s.sid";
        if($multitemplateRes = $xoopsDB->query($multitemplateSQL)) {
            if ($xoopsDB->getRowsNum($multitemplateRes) > 0) {
                while($handleArray = $xoopsDB->fetchArray($multitemplateRes)) {
                    if (!file_exists($screenpathname.$handleArray['sid'])) {
                        $pathname = $screenpathname.$handleArray['sid']."/";
                        mkdir($pathname, 0777, true);
                    }
                    if (!is_writable($pathname)) {
                        chmod($pathname, 0777);
                    }
                    saveTemplate($handleArray['toptemplate'], $handleArray['sid'], "toptemplate", $handleArray['theme']);
                    saveTemplate($handleArray['bottomtemplate'], $handleArray['sid'], "bottomtemplate", $handleArray['theme']);
                    saveTemplate($handleArray['elementtemplate'], $handleArray['sid'], "elementtemplate", $handleArray['theme']);
                    // if the screen is complex enough to have a complete set of templates, then add placeholder templates so that the defaults are not used for open and close
                    // repeat for additional themes as necessary, on the assumption that the screen would be complex enough in all themes
                    if($handleArray['toptemplate'] AND $handleArray['bottomtemplate'] AND $handleArray['elementtemplate']) {
                        saveTemplate("// Placeholder because this older screen predates the use of the Element Container Template (opening)", $handleArray['sid'], "elementcontainero", $handleArray['theme']);
                        saveTemplate("// Placeholder because this older screen predates the use of the Element Container Template (closing)", $handleArray['sid'], "elementcontainerc", $handleArray['theme']);
                        if(isset($_GET['additional_themes'])) {
                            foreach(explode(',',$_GET['additional_themes']) as $additional_theme) {
                                $additional_theme = preg_replace("/[^a-zA-Z0-9_-]+/", "", $additional_theme);
                                if($additional_theme != $handleArray['theme']) {
                                    saveTemplate("// Placeholder because this older screen predates the use of the Element Container Template (opening)", $handleArray['sid'], "elementcontainero", $additional_theme);
                                    saveTemplate("// Placeholder because this older screen predates the use of the Element Container Template (closing)", $handleArray['sid'], "elementcontainerc", $additional_theme);
                                }
                            }
                        }
                    }
                }
            }
        }

        // Goes through all templates in screenpathname
        emptyTemplateFixer($screenpathname);
        
        $formToMultipageMap = array();        
        if($needToMigrateFormScreensToMultipage) {
            // copy form screens to multipage screens
            // rename form screens to add (Legacy) to the end of the names/titles
            $criteria = new Criteria('type', 'form');
            $screen_handler = xoops_getmodulehandler('screen', 'formulize');
            $form_screen_handler = xoops_getmodulehandler('formScreen', 'formulize');
            $multipage_screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
            $formScreens = $screen_handler->getObjects($criteria);
            foreach($formScreens as $fs) {
                $formScreenObject = $form_screen_handler->get($fs->getVar('sid'));
                $multipageScreenObject = $multipage_screen_handler->create();
                $sameProperties = array(
                    'title',
                    'fid',
                    'frid',
                    'useToken',
                    'anonNeedsPasscode',
                    'theme',
                    'donedest',
                    'displaycolumns',
                    'reloadblank'
                );
                foreach($sameProperties as $property) {
                    $multipageScreenObject->setVar($property, $formScreenObject->getVar($property));    
                }
                // for compatibility with the Anari theme, single column layout should be auto-auto, two column layout should be percentage width, and auto. Take the existing column 1 width in case of 2 column layout, unless it's auto and then set to 20%.
                if($formScreenObject->getVar('displaycolumns')==1) {
                    $multipageScreenObject->setVar('column1width', 'auto');
                } else {
                    $newCol1Width = $formScreenObject->getVar('column1width') == 'auto' ? '20%' : $formScreenObject->getVar('column1width');
                    $multipageScreenObject->setVar('column1width', $newCol1Width);
                }
                $multipageScreenObject->setVar('column2width', 'auto');
                
                $multipageScreenObject->setVar('displayheading', $formScreenObject->getVar('displayheading'));
                $multipageScreenObject->setVar('elementdefaults', serialize($formScreenObject->getVar('elementdefaults')));    
                $multipageScreenObject->setVar('type', 'multiPage');
                $multipageScreenObject->setVar('buttontext', serialize(array(
                    'thankyoulinktext'=>'',
                    'leaveButtonText'=>($formScreenObject->getVar('saveandleavebuttontext') ? $formScreenObject->getVar('saveandleavebuttontext') : trans(_formulize_SAVE_AND_LEAVE)),
                    'prevButtonText'=>trans(_formulize_DMULTI_PREV),
                    'saveButtonText'=>($formScreenObject->getVar('savebuttontext') ? $formScreenObject->getVar('savebuttontext') : trans(_formulize_SAVE)),
                    'nextButtonText'=>trans(_formulize_DMULTI_NEXT),
                    'finishButtonText'=>trans(_formulize_DMULTI_SAVE),
                    'printableViewButtonText'=>($formScreenObject->getVar('printableviewbuttontext') ? $formScreenObject->getVar('printableviewbuttontext') : trans(_formulize_PRINTVIEW))
                )));
                $multipageScreenObject->setVar('finishisdone', 1);
                // use the declared elements for the page, or if none that means use all so go look up all the ids
                $elementsForPage = $formScreenObject->getVar('formelements');
                if(!is_array($elementsForPage) OR count((array) $elementsForPage)==0) {
                    $sql = "SELECT ele_id FROM ".$xoopsDB->prefix('formulize')." WHERE id_form = ".$formScreenObject->getVar('fid')." ORDER BY ele_order";
                    $res = $xoopsDB->query($sql);
                    $elementsForPage = array();
                    while($row = $xoopsDB->fetchRow($res)) {
                        $elementsForPage[] = $row[0];
                    }
                }
                $multipageScreenObject->setVar('pages', serialize(array(0=>$elementsForPage)));
                $multipageScreenObject->setVar('pagetitles', serialize(array(0=>$formScreenObject->getVar('title'))));
                $multipageScreenObject->setVar('conditions', serialize(array(0=>array())));
                $multipageScreenObject->setVar('printall', 0);
                $multipageScreenObject->setVar('paraentryform', 0); 
                $multipageScreenObject->setVar('paraentryrelationship', 0);
                $multipageScreenObject->setVar('navstyle', 1);	                
                $multipageScreenObject->setVar('showpagetitles', 0);
                $multipageScreenObject->setVar('showpageindicator', 0);
                $multipageScreenObject->setVar('showpageselector', 0);

                $_POST['toptemplate'] = $formScreenObject->getTemplate('toptemplate', $formScreenObject->getVar('theme'));
                $_POST['elementtemplate1'] = $formScreenObject->getTemplate('elementtemplate1', $formScreenObject->getVar('theme'));
                $_POST['elementtemplate2'] = $formScreenObject->getTemplate('elementtemplate2', $formScreenObject->getVar('theme'));
                $_POST['bottomtemplate'] = $formScreenObject->getTemplate('bottomtemplate', $formScreenObject->getVar('theme'));
                $_POST['elementcontainerc'] = $formScreenObject->getTemplate('elementcontainerc', $formScreenObject->getVar('theme'));
                $_POST['elementcontainero'] = $formScreenObject->getTemplate('elementcontainero', $formScreenObject->getVar('theme'));
                if($multiSid = $multipage_screen_handler->insert($multipageScreenObject)) {
                    $formToMultipageMap[$fs->getVar('sid')] = $multiSid;
                    print "New version of form screen ".$fs->getVar('sid')." created. Result: OK<br>";
                } else {
                    print "ERROR: could not create new version of form screen ".$fs->getVar('sid')."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.<br>";
                }
                
                // copy to an additional theme(s) that may have been specified
                if(isset($_GET['additional_themes'])) {
                    $skipTheme = $formScreenObject->getVar('theme');
                    foreach(explode(',',$_GET['additional_themes']) as $additional_theme) {
                        $additional_theme = preg_replace("/[^a-zA-Z0-9_-]+/", "", $additional_theme);
                        if($skipTheme != $additional_theme) {
                            $_POST['toptemplate'] = $formScreenObject->getTemplate('toptemplate', $additional_theme);
                            $_POST['elementtemplate1'] = $formScreenObject->getTemplate('elementtemplate1', $additional_theme);
                            $_POST['elementtemplate2'] = $formScreenObject->getTemplate('elementtemplate2', $additional_theme);
                            $_POST['bottomtemplate'] = $formScreenObject->getTemplate('bottomtemplate', $additional_theme);
                            $_POST['elementcontainerc'] = $formScreenObject->getTemplate('elementcontainerc', $additional_theme);
                            $_POST['elementcontainero'] = $formScreenObject->getTemplate('elementcontainero', $additional_theme);
                            $multipageScreenObject->setVar('sid', $multiSid);
                            $multipageScreenObject->setVar('theme', $additional_theme);
                            if($multipage_screen_handler->insert($multipageScreenObject) == false) {
                                print "ERROR: could not create new version of form screen ".$fs->getVar('sid')." for additional theme $additional_theme<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.<br>";        
                            }
                        }
                    }
                }
                    
                unset($_POST['toptemplate']);
                unset($_POST['elementtemplate1']);
                unset($_POST['elementtemplate2']);
                unset($_POST['bottomtemplate']);
                unset($_POST['elementcontainerc']);
                unset($_POST['elementcontainero']);
                $formScreenObject->setVar('title', $formScreenObject->getVar('title').' (Legacy)');
                if($form_screen_handler->insert($formScreenObject) == false) {
                    print "ERROR: could not update form screen ".$fs->getVar('sid')." with (Legacy) flag<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.<br>";
                }
                                
            }
            
            if(!isset($_GET['skip_ref_updates'])) {
            
                // swap all subform element screen declarations
                $criteria = new Criteria('ele_type', 'subform');
                $element_handler = xoops_getmodulehandler('elements','formulize');
                $subformElements = $element_handler->getObjects($criteria);
                foreach($subformElements as $element) {
                    $ele_value = $element->getVar('ele_value');
                    if(isset($formToMultipageMap[$ele_value['display_screen']])) {
                        $ele_value['display_screen'] = $formToMultipageMap[$ele_value['display_screen']];
                        $element->setVar('ele_value', $ele_value);
                        if($element_handler->insert($element) == false) {
                            print "ERROR: could not update display screen for subform element ".$element->getVar('ele_id')." in form ".$element->getVar('id_form').". Legacy form screen will still be in use.<br>";
                        }
                    }
                }
                
                // swap all list of entries display screen declarations, including "default form" to actual sid
                $criteria = new Criteria('type', 'listOfEntries');
                $list_screen_handler = xoops_getmodulehandler('listOfEntriesScreen','formulize');
                $listScreens = $screen_handler->getObjects($criteria);
                foreach($listScreens as $screen) {
                    $screen = $list_screen_handler->get($screen->getVar('sid'));
                    $ves = $screen->getVar('viewentryscreen');
                    $tryInsert = false;
                    if(!$ves OR $ves === 'none') {
                        $form_handler = xoops_getmodulehandler('forms', 'formulize');
                        $formObject = $form_handler->get($screen->getVar('fid'));
                        if($formObject->defaultform AND isset($formToMultipageMap[$formObject->defaultform])) {
                            $screen->setVar('viewentryscreen', $formToMultipageMap[$formObject->defaultform]);
                            $tryInsert = true;
                        }
                    } elseif(isset($formToMultipageMap[$ves])) {
                        $screen->setVar('viewentryscreen', $formToMultipageMap[$ves]);
                        $tryInsert = true;
                    }
                    if($tryInsert) {
                        if($list_screen_handler->insert($screen) == false) {
                            print "ERROR: could not update display screen for list of entries ".$screen->getVar('sid')." in form ".$screen->getVar('fid').". Legacy form screen will still be in use.<br>";
                        }
                    }
                }
            
            }
            
            print "<script>alert(\"Formulize 7 introduces an all new, modern and mobile friendly theme called 'Anari'. To use the new theme, go to System -> Site Configuration -> Preferences -> General Settings and change the Default Theme to 'Anari'.\");</script>";
            
        }

        $sql = array();
        $sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize")."_screen_listofentries DROP `toptemplate`";
        $sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize")."_screen_listofentries DROP `listtemplate`";
        $sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize")."_screen_listofentries DROP `bottomtemplate`";
        $sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize")."_screen_multipage DROP `toptemplate`";
        $sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize")."_screen_multipage DROP `elementtemplate`";
        $sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize")."_screen_multipage DROP `bottomtemplate`";
        foreach($sql as $thisSql) {
            $xoopsDB->query($thisSql);
        }
        
        print "DB updates completed.  result: OK";
    }
}

// Fixes the format if the template is empty
function emptyTemplateFixer($dir) {
    if(is_dir($dir)) {
        if($dhome = opendir($dir)){ // Ensures its a valid directory (just in case)
            while($file = readdir($dhome)){
                if($file != '.' && $file != '..'){
                    if(is_dir($dir . $file)){
                        // Recurse the directory
                        emptyTemplateFixer($dir . $file . '/');
                    }else{
                        $fcontents = file_get_contents($dir . $file);

                        // Overwrites files with only a header with empty contents
                        if(trim($fcontents) == "<?php"){
                            file_put_contents($dir . $file, "");
                        }
                    }
                }
            }
        }
        closedir($dhome);
    }
}

// Saves the given template to a template file on the disk
function saveTemplate($template, $sid, $name, $theme="") {
    global $xoopsConfig;
    $theme = $theme ? preg_replace("/[^a-zA-Z0-9_-]+/", "", $theme) : $xoopsConfig['theme_set'];
    $filename = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/".$theme."/{$sid}/{$name}.php";

    $text = trim(html_entity_decode($template));
    if($text) {
        if (!strstr($text, "<?php")) {
            // if there's no php open-tag in the text already, add one
            $text = "<?php\n" . $text;
        }
        if(file_exists($filename)) {
            print "$name file for screen $sid already exists. result: OK<br>";
        } elseif (false === file_put_contents($filename, $text)) {
            print "Warning: could not save " . $name . ".php for screen " . $sid . ".<br>";
        } else {
            print "created templates/screens/".$theme."/" . $sid . "/". $name . ".php. result: OK<br>";
        }
    }
}

function saveMenuEntryAndPermissionsSQL($formid, $appid, $i, $menuText) {
    global $xoopsDB;
    $gperm_handler = xoops_gethandler('groupperm');
    $permissionsql = "";
    $groupsThatCanView = $gperm_handler->getGroupIds("view_form", $formid, getFormulizeModId());

    $menuText = html_entity_decode($menuText, ENT_QUOTES) == "Use the form's title" ? '' : $menuText;
    $thissql = "INSERT INTO `".$xoopsDB->prefix("formulize_menu_links")."` VALUES (null,". $appid.",'fid=".$formid."',".$i.",null,'".$menuText."','');";//.$permissionsql.";";
    if (!$result = $xoopsDB->query($thissql)) {
        exit("Error inserting Menus. SQL dump:<br>" . $thissql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
    } else {
        foreach($groupsThatCanView as $groupid) {
            if ($permissionsql != ""){
                $permissionsql .= ",(null,". $xoopsDB->getInsertId().",". $groupid.",0)";
            } else {
                $permissionsql = "INSERT INTO `".$xoopsDB->prefix("formulize_menu_permissions")."` VALUES (null,". $xoopsDB->getInsertId().",". $groupid.",0)";
            }
        }
        if ($permissionsql){
            if (!$result = $xoopsDB->query($permissionsql)) {
                exit("Error inserting Menu permissions. SQL dump:<br>" . $permissionsql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
            }
        }
    }
}

if (!defined('_FORMULIZE_UI_PHP_INCLUDED')) {
    include "ui.php";
}


