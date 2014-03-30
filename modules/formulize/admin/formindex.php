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
	include "../language/".$xoopsConfig['language']."/main.php";
} else {
	include "../language/english/main.php";
}

if(!isset($_GET['op']) AND !defined('_FORMULIZE_UI_PHP_INCLUDED')){
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
	if(!$needsPatchRes = $xoopsDB->queryF($sql)) {
		print "Error: ".$xoopsDB->error()."<br>We could not determine if your Formulize database structure is up to date.  Please contact <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a> for assistance.<br>\n"; 
		return false;
	}
	$needsPatchRows = $xoopsDB->getRowsNum($needsPatchRes);
	if($needsPatchRows == 0) { // no rows returned
		$needsPatch = true;
	}
	return $needsPatchRes;
}

// database patch logic for 4.0 and higher
function patch40() {
	// CHECK THAT THEY ARE AT 3.1 LEVEL, IF NOT, LINK TO PATCH31
	// Check for ele_handle being 255 in formulize table
	global $xoopsDB;
	$fieldStateSQL = "SHOW COLUMNS FROM " . $xoopsDB->prefix("formulize") ." LIKE 'ele_handle'"; // note very odd use of LIKE as a clause of its own in SHOW statements, very strange, but that's what MySQL does
	if(!$fieldStateRes = $xoopsDB->queryF($fieldStateSQL)) {
		print "Error: could not determine if your Formulize database structure is up to date.  Please contact <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a> for assistance.<br>\n"; 
		return false;
	}
	$fieldStateData = $xoopsDB->fetchArray($fieldStateRes);
	$dataType = $fieldStateData['Type'];
	if($dataType != "varchar(255)") {
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
	
	$checkThisTable = 'formulize';
	$checkThisField = 'ele_list_order';
	$checkThisProperty = false;
	$checkPropertyForValue = false;
	
	$needsPatch = false;
	
	$tableCheckSql = "SELECT 1 FROM information_schema.tables WHERE table_name = '".$xoopsDB->prefix(formulize_escape($checkThisTable)) ."'";
	$tableCheckRes = formulize_DBPatchCheckSQL($tableCheckSql, $needsPatch); // may modify needsPatch!
	if($tableCheckRes AND !$needsPatch AND $checkThisField) { // table was found, and we're looking for a field in it
		$fieldCheckSql = "SHOW COLUMNS FROM " . $xoopsDB->prefix(formulize_escape($checkThisTable)) ." LIKE '".formulize_escape($checkThisField)."'"; // note very odd use of LIKE as a clause of its own in SHOW statements, very strange, but that's what MySQL does
		$fieldCheckRes = formulize_DBPatchCheckSQL($fieldCheckSql, $needsPatch); // may modify needsPatch!	
	} 
	if($fieldCheckRes AND !$needsPatch AND $checkPropertyForValue) {
		$fieldCheckArray = $xoopsDB->fetchArray($fieldCheckRes);
		if($fieldCheckArray[$checkThisProperty] != $checkPropertyForValue) {
			$needsPatch = true;
		}
	} 
	
	if(!$needsPatch AND (!isset($_GET['op']) OR ($_GET['op'] != 'patch40' AND $_GET['op'] != 'patchDB'))) {
		return;
	}
	
	if(!isset($_POST['patch40'])) {
		print "<form action=\"ui.php?op=patchDB\" method=post>";
		print "<h1>Your database structure is not up to date!  Click the button below to apply the necesssary patch to the database.</h1>";
		print "<h2>Warning: this patch makes several changes to the database.  Backup your database prior to applying this patch!</h2>";
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

		if(!in_array($xoopsDB->prefix("formulize_groupscope_settings"), $existingTables)) {
			$sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_groupscope_settings")."` (
  `groupscope_id` int(11) NOT NULL auto_increment,
  `groupid` int(11) NOT NULL default 0,
	`fid` int(11) NOT NULL default 0,
  `view_groupid` int(11) NOT NULL default 0,
  PRIMARY KEY (`groupscope_id`),
  INDEX i_groupid (`groupid`),
	INDEX i_fid (`fid`),
  INDEX i_view_groupid (`view_groupid`)
) ENGINE=MyISAM;";
		}
		
		if(!in_array($xoopsDB->prefix("formulize_group_filters"), $existingTables)) {
			$sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_group_filters")."` (
  `filterid` int(11) NOT NULL auto_increment,
  `fid` int(11) NOT NULL default 0,
  `groupid` int(11) NOT NULL default 0,
  `filter` text NOT NULL,
  PRIMARY KEY (`filterid`),
  INDEX i_fid (`fid`),
  INDEX i_groupid (`groupid`)
) ENGINE=MyISAM;";
		}
	
		if(!in_array($xoopsDB->prefix("formulize_applications"), $existingTables)) {
			$sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_applications")."` (
  `appid` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  PRIMARY KEY (`appid`)
) ENGINE=MyISAM;";
		}

		if(!in_array($xoopsDB->prefix("formulize_application_form_link"), $existingTables)) {
			$sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_application_form_link")."` (
  `linkid` int(11) NOT NULL auto_increment,
  `appid` int(11) NOT NULL default 0,
  `fid` int(11) NOT NULL default 0,
  PRIMARY KEY (`linkid`),
  INDEX i_fid (`fid`),
  INDEX i_appid (`appid`)
) ENGINE=MyISAM;";
		}
		
		if(!in_array($xoopsDB->prefix("formulize_screen_form"), $existingTables)) {
			$sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_screen_form")."` (
  `formid` int(11) NOT NULL auto_increment,
  `sid` int(11) NOT NULL default 0,
  `donedest` varchar(255) NOT NULL default '',
  `savebuttontext` varchar(255) NOT NULL default '',
  `alldonebuttontext` varchar(255) NOT NULL default '',
  `displayheading` tinyint(1) NOT NULL default 0,
  `reloadblank` tinyint(1) NOT NULL default 0,
  `formelements` text,
  PRIMARY KEY (`formid`),
  INDEX i_sid (`sid`)
) ENGINE=MyISAM;";
		}
		
		if(!in_array($xoopsDB->prefix("formulize_advanced_calculations"), $existingTables)) {
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
) ENGINE=MyISAM;";
		}

		if(!in_array($xoopsDB->prefix("formulize_procedure_logs"), $existingTables)) {
			$sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_procedure_logs")."` (
  `proc_log_id` int(11) unsigned NOT NULL auto_increment,
  `proc_id` int(11) NOT NULL,
  `proc_datetime` datetime NOT NULL,
  `proc_uid` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`proc_log_id`),
  INDEX i_proc_id (proc_id),
  INDEX i_proc_uid (proc_uid)
) ENGINE=MyISAM;";
		}

		if(!in_array($xoopsDB->prefix("formulize_procedure_logs_params"), $existingTables)) {
			$sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_procedure_logs_params")."` (
  `proc_log_param_id` int(11) unsigned NOT NULL auto_increment,
  `proc_log_id` int(11) unsigned NOT NULL,
  `proc_log_param` varchar(255),
  `proc_log_value` varchar(255),
  PRIMARY KEY (`proc_log_param_id`),
  INDEX i_proc_log_id (proc_log_id)
) ENGINE=MyISAM;";	
			}
			
			
if(!in_array($xoopsDB->prefix("formulize_resource_mapping"), $existingTables)) {			
	$sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_resource_mapping")."` (
	mapping_id int(11) NOT NULL auto_increment,
	internal_id int(11) NOT NULL,
	external_id int(11) NOT NULL,
	resource_type int(4) NOT NULL,
	mapping_active tinyint(1) NOT NULL,
	PRIMARY KEY (mapping_id),
	INDEX i_internal_id (internal_id),
	INDEX i_external_id (external_id),
	INDEX i_resource_type (resource_type)
) ENGINE=MyISAM;";

		}
		
	if(!in_array($xoopsDB->prefix("formulize_deletion_logs"), $existingTables)) {
		$sql[] = "CREATE TABLE ".$xoopsDB->prefix("formulize_deletion_logs")." (
				  del_log_id int(11) unsigned NOT NULL auto_increment,
				  form_id int(11) NOT NULL,
				  entry_id int(7) NOT NULL,
				  user_id mediumint(8) NOT NULL,
				  context text,
				  deletion_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				  PRIMARY KEY (del_log_id),
				  INDEX i_del_id (del_log_id)
		) ENGINE=MyISAM;";
	}

		// if this is a standalone installation, then we want to make sure the session id field in the DB is large enough to store whatever session id we might be working with
		if(file_exists(XOOPS_ROOT_PATH."/integration_api.php")) {
	$sql['increase_session_id_size'] = "ALTER TABLE ".$xoopsDB->prefix("session")." CHANGE `sess_id` `sess_id` varchar(60) NOT NULL";
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
		$sql['add_toptext'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_multipage") . " ADD `toptemplate` text NOT NULL";    
		$sql['add_elementtext'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_multipage") . " ADD `elementtemplate` text NOT NULL"; 
		$sql['add_bottomtext'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_multipage") . " ADD `bottomtemplate` text NOT NULL"; 
		$sql['add_formelements'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_form") . " ADD `formelements` text";
        $sql['add_on_before_save'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD `on_before_save` text";
		$sql['add_form_note'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD `note` text";
		foreach($sql as $key=>$thissql) {
			if(!$result = $xoopsDB->query($thissql)) {
				if($key === "add_encrypt") {
					print "ele_encrypt field already added.  result: OK<br>";
				} elseif($key === "add_lockedform") {
					print "lockedform field already added.  result: OK<br>";
				} elseif($key === "add_filtersettings") {
					print "element filtersettings field already added.  result: OK<br>";
				} elseif($key === "add_defaultform") {
					print "defaultform field already added.  result: OK<br>";
				} elseif($key === "add_defaultlist") {
					print "defaultlist field already added.  result: OK<br>";
				} elseif($key === "add_menutext") {
					print "menutext field already added.  result: OK<br>";
				} elseif($key === "add_useadvcalcs") {
					print "useadvcalcs field already added.  result: OK<br>";
				} elseif($key === "add_not_elementemail") {
					print "elementemail notification option already added.  result: OK<br>";
				} elseif($key === "add_form_handle") {
					print "form handles already added.  result: OK<br>";
				} elseif($key === "add_dedisplay") {
					print "dedisplay already added.  result: OK<br>";
				} elseif($key === "add_store_revisions") {
					print "store_revisions already added.  result: OK<br>";
				} elseif($key === "add_finishisdone") {
					print "finishisdone for multipage forms already added.  result: OK<br>";
				} elseif($key === "add_toptext") {                            
					print "toptemplate already added for multipage screens.  result: OK<br>";
				} elseif($key === "add_elementtext") {
					print "elementtemplate already added for multipage screens.  result: OK<br>";
				} elseif($key === "add_bottomtext") {
					print "bottomtemplate already added for multipage screens.  result: OK<br>";
                } elseif($key === "add_formelements") {
                    print "formelements field already added for single page screens.  result: OK<br>";
                } elseif($key === "add_on_before_save") {
                    print "on_before_save field already added.  result: OK<br>";
                } elseif($key === "add_form_note") {
                    print "form note field already added.  result: OK<br>";
				} elseif(strstr($key, 'drop_from_formulize_id_')) {
					continue;
				} else {
					exit("Error patching DB for Formulize 4.0. SQL dump:<br>" . $thissql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
				}
			} 
		}
		
		// if there is a framework handles table present, then we need to check for a few things to ensure the integrity of code and our ability to disambiguate inputs to the API
		if(in_array($xoopsDB->prefix("formulize_framework_elements"), $existingTables)) {
			
			// need to change rules...framework handles must now be globally unique, so we can disambiguate them from each other when we are passed just a framework handle
			$uniqueSQL = "SELECT elements.ele_caption, elements.ele_id, elements.ele_handle, handles.fe_handle, handles.fe_frame_id FROM ".$xoopsDB->prefix("formulize")." as elements, ".$xoopsDB->prefix("formulize_framework_elements")." as handles WHERE EXISTS (SELECT 1 FROM ".$xoopsDB->prefix("formulize_framework_elements")." as checkhandles WHERE handles.fe_handle = checkhandles.fe_handle AND handles.fe_element_id != checkhandles.fe_element_id) AND handles.fe_element_id = elements.ele_id AND handles.fe_handle != \"\" ORDER BY handles.fe_handle";
			$uniqueRes = $xoopsDB->query($uniqueSQL);
			$haveWarning = false;
			$warningIdentifier = array();
			$warningContents = array();
			if($xoopsDB->getRowsNum($uniqueRes)) {
				$haveWarning = true;
				$warningIdentifier[] = "<li>You have some \"framework handles\" which are the same between different frameworks.</li>";
				ob_start();
				print "<ul>";
				$prevHandle = "";
				while($uniqueArray = $xoopsDB->fetchArray($uniqueRes)) {
					if($uniqueArray['fe_handle'] != $prevHandle) {
						if($prevHandle != "") {
							// need to finish previous set and print out what's missing
							print "</li>";
						}
						print "<li>Framework handle: <b>".$uniqueArray['fe_handle']."</b> is used in more than one place:<br>";
					}
					$prevHandle = $uniqueArray['fe_handle'];
					print "&nbsp;&nbsp;&nbsp;&nbsp;In framework ".$uniqueArray['fe_frame_id'].", it is used for element ".$uniqueArray['ele_id']." (".$uniqueArray['ele_caption'].")<br>";
					if($uniqueArray['fe_handle'] != $uniqueArray['ele_handle']) {
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
			if($xoopsDB->getRowsNum($handleRes) > 0) {
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
		
		if($haveWarning) {
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
			print "<p>If you have any questions about this upgrade issue, please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.</p>";
		}

        //create new menus table
        if(!in_array($xoopsDB->prefix("formulize_menu_links"), $existingTables)) {
            $menusql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_menu_links")."` (
            `menu_id` int(11) unsigned NOT NULL auto_increment,
            `appid` int(11) unsigned NOT NULL,
            `screen` varchar(11),
            `rank` int(11),
            `url` varchar(255),
            `link_text` varchar(255),
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
                
                if(!$result = $xoopsDB->query($thissql)) {
                    exit("Error patching DB for Formulize 4.0. SQL dump:<br>" . $thissql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
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
                    if($menuText = $thisFormObject->getVar('menutext')) {
				saveMenuEntryAndPermissionsSQL($thisFormObject->getVar('id_form'),$thisApplication->getVar("appid"),$i,$menuText);
                    }
                    $i++;		
                }
                $i=0;
            }		
            
            $formsWithNoApplication = $form_handler->getFormsByApplication(0,true); // true forces ids not objects to be returned
            foreach($formsWithNoApplication as $thisForm) {
                $thisFormObject = $form_handler->get($thisForm);
                if($menuText = $thisFormObject->getVar('menutext')) {
			    saveMenuEntryAndPermissionsSQL($thisFormObject->getVar('id_form'),0,$i,$menuText);
                }
                $i++;		
            }
        }
		
		// need to update multiple select boxes for new data structure
                // $xoopsDB->prefix("formulize")
                // 1. get a list of all elements that are linked selectboxes that support only single values
                $selectBoxesSQL = "SELECT id_form, ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_type = 'select'";
                $selectBoxRes = $xoopsDB->query($selectBoxesSQL);
                if ($xoopsDB->getRowsNum($selectBoxRes) > 0) {
                    while ($handleArray = $xoopsDB->fetchArray($selectBoxRes)) {
                        $metaData = formulize_getElementMetaData($handleArray['ele_id']);
                        $ele_value = unserialize($metaData['ele_value']);
                        
                        // select only single option, linked select boxes
                        if (!$ele_value[1] AND strstr($ele_value[2], "#*=:*")) {
                            $successSelectBox = convertSelectBoxToSingle($xoopsDB->prefix('formulize_' . $handleArray['id_form']), $handleArray['ele_id']);
                            if (!$successSelectBox) {
                                print "could not convert column " . $handleArray['ele_id'] . " in table " . $xoopsDB->prefix('formulize_' . $handleArray['id_form']) . "<br>";
                            }
                        }
                    }
                }

        // if the relationship link option, unified_delete, does not exist, create the field and default to the unified_display setting value
        $sql = $xoopsDB->query("show columns from ".$xoopsDB->prefix("formulize_framework_links")." where Field = 'fl_unified_delete'");
        if (0 == $xoopsDB->getRowsNum($sql)) {
		    $sql = "ALTER TABLE " . $xoopsDB->prefix("formulize_framework_links") . " ADD `fl_unified_delete` smallint(5)";
		    if($udres1 = $xoopsDB->query($sql)) {
			$sql = "update " . $xoopsDB->prefix("formulize_framework_links") . " set `fl_unified_delete` = `fl_unified_display`";
			$udres2 = $xoopsDB->query($sql);
		    }
		    if(!$udres1 OR !$udres2) {
			print "Error updating relationships with unified delete option.  SQL dump:<br>" . $thissql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.";
		    } else {
			print "Updating relationships with unified delete option.  result: OK<br>";
		    }
        }

        // if ele_list_order doesn't exist, create it. The initial values can be the same as ele_order
		$myCol = q("SELECT * FROM ". $xoopsDB->prefix("formulize"). " LIMIT 1");
		if(!isset($myCol[0]["ele_list_order"])){
		  $statement="Alter table `".$xoopsDB->prefix("formulize")."` ADD `ele_list_order` smallint(5)";
		  $result=$xoopsDB->queryF($statement);
		  $statement = "update " . $xoopsDB->prefix("formulize") . " set `ele_list_order` = `ele_order`";
		  $result=$xoopsDB->queryF($sql);
		}
      //if ele_list_display doesn't exist, create it
		$myCol = q("SELECT * FROM ". $xoopsDB->prefix("formulize"). " LIMIT 1");
		if(!isset($myCol[0]["ele_list_display"])){
		  $statement="Alter table `".$xoopsDB->prefix("formulize")."` ADD `ele_list_display` text";
		  $result=$xoopsDB->queryF($statement);
		  $statement = "update " . $xoopsDB->prefix("formulize") . " set `ele_list_display` = `ele_display`";
		  $result=$xoopsDB->queryF($sql);
		}

		// CONVERTING EXISTING TEMPLATES IN DB TO TEMPLATE FILES
		$screenpathname = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/default/";
                
                $templateSQL = "SELECT sid, toptemplate, listtemplate, bottomtemplate FROM ".$xoopsDB->prefix("formulize")."_screen_listofentries";
                
                $templateRes = $xoopsDB->query($templateSQL);
                if($xoopsDB->getRowsNum($templateRes) > 0) {
                
                    while($handleArray = $xoopsDB->fetchArray($templateRes)) {
                        if (!file_exists($screenpathname.$handleArray['sid'])) {
                            $pathname = $screenpathname.$handleArray['sid']."/";
                            mkdir($pathname, 0777, true);
            
                            if (!is_writable($pathname)) {
                                chmod($pathname, 0777);
                            }
            
                            saveTemplate($handleArray['toptemplate'], $handleArray['sid'], "toptemplate");
                            saveTemplate($handleArray['bottomtemplate'], $handleArray['sid'], "bottomtemplate");
                            saveTemplate($handleArray['listtemplate'], $handleArray['sid'], "listtemplate");
                            
                        } else {
                            print "screen templates for screen ".$handleArray['sid']." already exist. result: OK<br>";
                        }
                        
                    }
                }
                
                $multitemplateSQL = "SELECT sid, toptemplate, elementtemplate, bottomtemplate FROM ".$xoopsDB->prefix("formulize")."_screen_multipage";
                
                $multitemplateRes = $xoopsDB->query($multitemplateSQL);
                if($xoopsDB->getRowsNum($multitemplateRes) > 0) {
                
                    while($handleArray = $xoopsDB->fetchArray($multitemplateRes)) {
                        if (!file_exists($screenpathname.$handleArray['sid'])) {
                            $pathname = $screenpathname.$handleArray['sid']."/";
                            mkdir($pathname, 0777, true);
            
                            if (!is_writable($pathname)) {
                                chmod($pathname, 0777);
                            }
            
                            saveTemplate($handleArray['toptemplate'], $handleArray['sid'], "toptemplate");
                            saveTemplate($handleArray['bottomtemplate'], $handleArray['sid'], "bottomtemplate");
                            saveTemplate($handleArray['elementtemplate'], $handleArray['sid'], "elementtemplate");
                            
                        } else {
                            print "screen templates for screen ".$handleArray['sid']." already exist. result: OK<br>";
                        }
                        
                    }
                }

		
		print "DB updates completed.  result: OK";
	}
}

// Saves the given template to a template file on the disk
function saveTemplate($template, $sid, $name) {
    $pathname = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/default/". $sid . "/";
    
    $text = html_entity_decode($template);
    if (!empty($text)) {
        $fileHandle = fopen($pathname . $name. ".php", "w+");
        $success = fwrite($fileHandle, "<?php\n" . $text);
        fclose($fileHandle);

        if ($success) {
            print "created templates/screens/default/" . $sid . "/". $name . ".php. result: OK<br>";
        } else {
            print "Warning: could not save " . $name . ".php for screen " . $sid . ".<br>";
        }
    }
}

    function saveMenuEntryAndPermissionsSQL($formid,$appid,$i,$menuText){
        global $xoopsDB;
        $gperm_handler = xoops_gethandler('groupperm');
        $permissionsql = "";
        $groupsThatCanView = $gperm_handler->getGroupIds("view_form", $formid, getFormulizeModId());
        
        $menuText = html_entity_decode($menuText, ENT_QUOTES) == "Use the form's title" ? '' : $menuText;
        $thissql = "INSERT INTO `".$xoopsDB->prefix("formulize_menu_links")."` VALUES (null,". $appid.",'fid=".$formid."',".$i.",null,'".$menuText."');";//.$permissionsql.";";
        if(!$result = $xoopsDB->query($thissql)) {
            exit("Error inserting Menus. SQL dump:<br>" . $thissql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
        }else{
            foreach($groupsThatCanView as $groupid) {
                if($permissionsql != ""){
                    $permissionsql .= ",(null,". $xoopsDB->getInsertId().",". $groupid.",0)";
                }else{
                    $permissionsql = "INSERT INTO `".$xoopsDB->prefix("formulize_menu_permissions")."` VALUES (null,". $xoopsDB->getInsertId().",". $groupid.",0)";
                }
            }
            if ($permissionsql){
                if(!$result = $xoopsDB->query($permissionsql)) {
                    exit("Error inserting Menu permissions. SQL dump:<br>" . $permissionsql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
                }
            }
        }
    }
        
// THE 4.0 SERIES WILL NEED A SEPARATE PATCH ROUTINE.  THERE IS TOO MUCH BAGGAGE IN HERE TO KEEP CARRYING IT AROUND.  ESPECIALLY THE DATATYPE CONVERSION STUFF.
function patch31() {

  global $xoopsDB;
  // check if the new table structure is in place, and don't run this patch if so!
  $patchCheckSql = "SHOW TABLES";
	$resultPatchCheck = $xoopsDB->queryF($patchCheckSql);
  $entryOwnerGroupFound = false;
	while($table = $xoopsDB->fetchRow($resultPatchCheck)) {
    if($table[0] == $xoopsDB->prefix("formulize_entry_owner_groups")) {
      $entryOwnerGroupFound = true;
    } 
  }

	if(!isset($_POST['patch31'])) {
		print "<form action=\"formindex.php?op=patch31\" method=post>";
		print "<h1>Warning: this patch makes several changes to the database.  Backup your database prior to applying this patch!</h1>";
		print "<p>This patch may take a few minutes to apply.  Your page may take that long to reload, please be patient.</p>";
		print "<input type = submit name=patch31 value=\"Apply Database Patch for Formulize 3.1\">";
		print "</form>";
	} else {
		print "<h2>Patch Results:</h2>";
    // if entryownergroupfound, then only do the 3.1 upgrade, otherwise, do the entire upgrade
    if($entryOwnerGroupFound) {
      if($derivedResult = formulize_createDerivedValueFieldsInDB()) {
        print "Created derived value fields in database.  result: OK<br>\n";
        $sql = array();
        $sql['ves_to_varchar'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_listofentries") . " CHANGE `viewentryscreen` `viewentryscreen` varchar(10) NOT NULL DEFAULT ''";
        $sql['handlelength'] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " CHANGE `ele_handle` `ele_handle` varchar(255) NOT NULL default ''";
        /*$sql['copyTableForClean'] = "CREATE TABLE temp_entry_owner_groups like ".$xoopsDB->prefix("formulize_entry_owner_groups");
        $sql['lockForClean'] = "LOCK TABLES ".$xoopsDB->prefix("formulize_entry_owner_groups") . " WRITE, temp_entry_owner_groups WRITE";
        $sql['copyDataForClean'] = "INSERT INTO temp_entry_owner_groups SELECT * FROM ".$xoopsDB->prefix("formulize_entry_owner_groups");
        $sql['cleanEntryOwnerGroups'] = "DELETE FROM ".$xoopsDB->prefix("formulize_entry_owner_groups") . " WHERE owner_id NOT IN (SELECT MIN(owner_id) FROM temp_entry_owner_groups GROUP BY fid, entry_id, groupid)";
        $sql['dropTempTable'] = "DROP TABLE temp_entry_owner_groups";
        $sql['unlockForClean'] = "UNLOCK TABLES";*/ // this cleaning routine may be too intensive to unleash on unsuspecting servers
        foreach($sql as $key=>$thissql) {
          if(!$result = $xoopsDB->query($thissql)) {
          	if($key === "ves_to_carchar") {
          		print "viewentryscreen param already converted to varchar.  result: OK<br>";
            } elseif($key === "handlelength") {
              print "Length of element handles already extended.  result OK<br>";
            } elseif($key === "cleanEntryOwnerGroups" OR $key === "lockForClean" OR $key === "copyTableForClean" OR $key === "copyDataForClean" OR $key === "dropTempTable" OR $key === "unlockForClean") {
              print "Warning: could not delete duplicate entries from the entry_owner_groups table.  Please contact <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a> for assistance.<br>\n";
            } else {
							exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $thissql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
						}
          }
        }
				// need to handle datetype conversions for date fields, and making all fields accept NULL
				// 1. get a list of all forms
				// 2. loop through each element, getting it's metadata and datatable field data
				// 3. switch each one to accept NULL and default to NULL
				// 4. if it's a date field, switch its type to date
				// 5. update 0000-00-00 to NULL for dates
				$form_handler = xoops_getmodulehandler('forms', 'formulize');
				$allFids = $form_handler->getAllForms(true); // true means get all elements, even ones that are not displayed to any groups
				foreach($allFids as $thisFid) {
					if($thisFid->getVar('tableform') != "") { continue; } // don't do table forms obviously!
					$dataTableInfoSQL = "SHOW COLUMNS FROM ".$xoopsDB->prefix("formulize_".$thisFid->getVar('id_form'));
					if($dataTableInfoResult = $xoopsDB->query($dataTableInfoSQL)) {
						$foundDateFields = array();
						$thisFidElementHandles = $thisFid->getVar('elementHandles');
						$thisFidElementTypes = $thisFid->getVar('elementTypes');
						$alterTableSQL = "ALTER TABLE ".$xoopsDB->prefix("formulize_".$thisFid->getVar('id_form'));
						$start = true;
						while($thisFidDataTableInfo = $xoopsDB->fetchArray($dataTableInfoResult)) {
							if($thisFidDataTableInfo['Field'] == "entry_id" OR $thisFidDataTableInfo['Field'] == "creation_datetime" OR $thisFidDataTableInfo['Field'] == "mod_datetime" OR $thisFidDataTableInfo['Field'] == "creaiton_uid" OR $thisFidDataTableInfo['Field'] == "mod_uid" OR $thisFidDataTableInfo['Type'] != "text") {
								continue; // skip the metadata fields of course, and anything that has been manually changed from 'text'
							}
							if($thisFidElementTypes[array_search($thisFidDataTableInfo['Field'],$thisFidElementHandles)] == "date") { // if it's a date...
								$foundDateFields[] = $thisFidDataTableInfo['Field'];
								$alterTableSQL .= !$start ? "," : ""; // add comma if we're on a subsequent run through
								$alterTableSQL .= " CHANGE `".$thisFidDataTableInfo['Field']."` `".$thisFidDataTableInfo['Field']."` date NULL default NULL";
								$start = false;
							} elseif(strtoupper($thisFidDataTableInfo['Null']) != "YES") {
								$alterTableSQL .= !$start ? "," : ""; // add comma if we're on a subsequent run through
								$alterTableSQL .= " CHANGE `".$thisFidDataTableInfo['Field']."` `".$thisFidDataTableInfo['Field']."` text NULL default NULL";
								$start = false;
							}
						}
						if($start) {
							$alterTableSQL = ""; // blank it, so we don't actually do a query
						}
						if($alterTableSQL) {
							if(!$alterTableResult = $xoopsDB->query($alterTableSQL)) {
								exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $dataTableInfoSQL . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
							}
						}
						foreach($foundDateFields as $thisDateField) {
							$dateCorrectionSQL = "UPDATE ".$xoopsDB->prefix("formulize_".$thisFid->getVar('id_form'))." SET `".$thisDateField."` = NULL WHERE `".$thisDateField."` = '0000-00-00'";
							if(!$dateCorrectionResult = $xoopsDB->query($dateCorrectionSQL)) {
								exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $dataTableInfoSQL . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
							}
						}
					} else {
						exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $dataTableInfoSQL . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
					}
				}
				
				print "<br><br><b>NOTE:</b> Although the 3.x data structure is highly optimized compared to previous versions of Formulize, there are some situations which we cannot account for automatically in the upgrade process:  if you have elements in a form and users only enter numerical data there, you should edit those elements now and give them a truly numeric data type (use the new option on the element editing page to do this).  In Formulize 3 and higher, elements that have only numbers, but which are not stored as numbers in the database, will not sort properly and some calculations will not work correctly on them either.  Unfortunately, we cannot reliably determine which numeric data type should be used for all elements, therefore you will need to make this adjustment manually.  We apologize for any inconvenience.  Please contact <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a> if you have any questions about this process.<br><br>\n";
				
        print "DB updates completed.  result: OK";
      } else {
        print "Unable to create derived value fields in database.  result: failed.  contact <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a> for assistance.<br>\n";
      }
      return;
    }
		// put logic here

		// check to see if form table exists
		// need to put in check to make sure we're not finding the valid 'form' table from the Formulaire module
		$checkFormulaire = "SELECT * FROM " . $xoopsDB->prefix("modules") . " WHERE dirname='formulaire'";
		$cfresult = $xoopsDB->query($checkFormulaire);
		$sql = "SELECT * FROM " . $xoopsDB->prefix("form") . " LIMIT 0,1";
		$result = $xoopsDB->query($sql);
		if($xoopsDB->getRowsNum($result) AND $xoopsDB->getRowsNum($cfresult) == 0) {
                        
			// check to see if ele_forcehidden is in the table or not
			$sql = "SELECT * FROM " . $xoopsDB->prefix("form") . " LIMIT 0,1";
			$result = $xoopsDB->query($sql);
			if(!$result) {
				exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $sql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
			}
			$array = $xoopsDB->fetchArray($result);
			unset($result);
			if(!isset($array['ele_forcehidden'])) {
				$sql = "ALTER TABLE " . $xoopsDB->prefix("form") . " ADD `ele_forcehidden` tinyint(1) NOT NULL default '0'";
				if(!$result = $xoopsDB->query($sql)) {
					exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $sql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
				}
			} 
			unset($sql);

			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("form") . " RENAME " . $xoopsDB->prefix("formulize");
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("form_id") . " RENAME " . $xoopsDB->prefix("formulize_id");
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("form_menu") . " RENAME " . $xoopsDB->prefix("formulize_menu");
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("form_form") . " RENAME " . $xoopsDB->prefix("formulize_form");
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("form_reports") . " RENAME " . $xoopsDB->prefix("formulize_reports");
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " CHANGE `ele_display` `ele_display` varchar(255) NOT NULL default '1'";
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize_menu") . " CHANGE `itemname` `itemname` VARCHAR( 255 ) NOT NULL ";
			$sql[] = "DROP TABLE " . $xoopsDB->prefix("form_max_entries");
			$sql[] = "DROP TABLE " . $xoopsDB->prefix("form_chains");
			$sql[] = "DROP TABLE " . $xoopsDB->prefix("form_chains_entries");
			foreach($sql as $thissql) {
				if(!$result = $xoopsDB->query($thissql)) {
					exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $thissql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
				}
			}

		} // end of if there's still a form table...

		unset($sql);


		$testsql = "SHOW TABLES";
		$resultst = $xoopsDB->query($testsql);
		while($table = $xoopsDB->fetchRow($resultst)) {
			$existingTables[] = $table[0];
		}
                $need22DataChecks = false;
		if(!in_array($xoopsDB->prefix("formulize_other"), $existingTables)) {
                        $need22DataChecks = true; // assume that if the formulize_other table is not present, then we have not patched up to 2.2 level yet
			$sql[] = "CREATE TABLE " . $xoopsDB->prefix("formulize_other") . " (
  other_id smallint(5) NOT NULL auto_increment,
  id_req smallint(5),
  ele_id int(5),
  other_text varchar(255) default NULL,
  PRIMARY KEY (`other_id`),
  INDEX i_ele_id (ele_id),
  INDEX i_id_req (id_req)
) ENGINE=MyISAM;";
		}

		if(!in_array($xoopsDB->prefix("formulize_notification_conditions"), $existingTables)) {
			$sql[] = "CREATE TABLE " . $xoopsDB->prefix("formulize_notification_conditions") . " (
  not_cons_id smallint(5) NOT NULL auto_increment,
  not_cons_fid smallint(5) NOT NULL default 0,
  not_cons_event varchar(25) default '',
  not_cons_uid mediumint(8) NOT NULL default 0,
  not_cons_curuser tinyint(1),
  not_cons_groupid smallint(5) NOT NULL default 0,
  not_cons_con text NOT NULL,
  not_cons_template varchar(255) default '',
  not_cons_subject varchar(255) default '',
  PRIMARY KEY (`not_cons_id`),
  INDEX i_not_cons_fid (not_cons_fid),
  INDEX i_not_cons_uid (not_cons_uid),
  INDEX i_not_cons_groupid (not_cons_groupid),
  INDEX i_not_cons_fidevent (not_cons_fid, not_cons_event(1))
) ENGINE=MyISAM;";
		}

		if(!in_array($xoopsDB->prefix("formulize_valid_imports"), $existingTables)) {
			$sql[] = "CREATE TABLE " . $xoopsDB->prefix("formulize_valid_imports") . " (
  import_id smallint(5) NOT NULL auto_increment,
  file varchar(255) NOT NULL default '',
  id_reqs text NOT NULL,
  PRIMARY KEY (`import_id`)
) ENGINE=MyISAM;";
		}
                
                if(!in_array($xoopsDB->prefix("formulize_screen_listofentries"), $existingTables)) {
                        $sql[] = "CREATE TABLE " . $xoopsDB->prefix("formulize_screen_listofentries") . " (
  listofentriesid int(11) NOT NULL auto_increment,
  sid int(11) NOT NULL default 0,
  useworkingmsg tinyint(1) NOT NULL,
  repeatheaders tinyint(1) NOT NULL,
  useaddupdate varchar(255) NOT NULL default '',
  useaddmultiple varchar(255) NOT NULL default '',
  useaddproxy varchar(255) NOT NULL default '',
  usecurrentviewlist varchar(255) NOT NULL default '',
  limitviews text NOT NULL, 
  defaultview varchar(20) NOT NULL default '',
  usechangecols varchar(255) NOT NULL default '',
  usecalcs varchar(255) NOT NULL default '',
  useadvcalcs varchar(255) NOT NULL default '',
  useadvsearch varchar(255) NOT NULL default '',
  useexport varchar(255) NOT NULL default '',
  useexportcalcs varchar(255) NOT NULL default '',
  useimport varchar(255) NOT NULL default '',
  useclone varchar(255) NOT NULL default '',
  usedelete varchar(255) NOT NULL default '',
  useselectall varchar(255) NOT NULL default '',
  useclearall varchar(255) NOT NULL default '',
  usenotifications varchar(255) NOT NULL default '',
  usereset varchar(255) NOT NULL default '',
  usesave varchar(255) NOT NULL default '',
  usedeleteview varchar(255) NOT NULL default '',
  useheadings tinyint(1) NOT NULL,
  usesearch tinyint(1) NOT NULL, 
  usecheckboxes tinyint(1) NOT NULL, 
  useviewentrylinks tinyint(1) NOT NULL,
  usescrollbox tinyint(1) NOT NULL,
  usesearchcalcmsgs tinyint(1) NOT NULL,
  hiddencolumns text NOT NULL,
  decolumns text NOT NULL,
  desavetext varchar(255) NOT NULL default '',
  columnwidth int(1) NOT NULL,
  textwidth int(1) NOT NULL,
  customactions text NOT NULL, 
  toptemplate text NOT NULL,
  listtemplate text NOT NULL,
  bottomtemplate text NOT NULL,
  PRIMARY KEY (`listofentriesid`),
  INDEX i_sid (`sid`)
) ENGINE=MyISAM;";
                }
                
                if(!in_array($xoopsDB->prefix("formulize_screen_multipage"), $existingTables)) {
                        $sql[] = "CREATE TABLE " . $xoopsDB->prefix("formulize_screen_multipage") . " (
  multipageid int(11) NOT NULL auto_increment,
  sid int(11) NOT NULL default 0,
  introtext text NOT NULL,
  thankstext text NOT NULL,
  donedest varchar(255) NOT NULL default '',
  buttontext varchar(255) NOT NULL default '',
  pages text NOT NULL,
  pagetitles text NOT NULL,
  conditions text NOT NULL,
  printall tinyint(1) NOT NULL,
  PRIMARY KEY (`multipageid`),
  INDEX i_sid (`sid`)
) ENGINE=MyISAM;";
                }
                
                if(!in_array($xoopsDB->prefix("formulize_screen"), $existingTables)) {
                        $sql[] = "CREATE TABLE " . $xoopsDB->prefix("formulize_screen") . " (
  sid int(11) NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  fid int(11) NOT NULL default 0,
  frid int(11) NOT NULL default 0,
  type varchar(100) NOT NULL default '',
  PRIMARY KEY  (`sid`)
) ENGINE=MyISAM;";
                }

if(!in_array($xoopsDB->prefix("formulize_entry_owner_groups"), $existingTables)) {
	$sql[] = "CREATE TABLE ".$xoopsDB->prefix("formulize_entry_owner_groups")." (
  owner_id int(5) unsigned NOT NULL auto_increment,
  fid int(5) NOT NULL default '0',
  entry_id int(7) NOT NULL default '0',
  groupid int(5) NOT NULL default '0',
  PRIMARY KEY (`owner_id`),
  INDEX i_fid (fid),
  INDEX i_entry_id (entry_id),
  INDEX i_groupid (groupid)
) ENGINE=MyISAM;";
}

		// check about altered fields
		$testsql = "SELECT * FROM " .  $xoopsDB->prefix("formulize") . " LIMIT 0,1";
		$result1 = $xoopsDB->query($testsql);
                if($xoopsDB->getRowsNum($result1) == 0) {
                        exit("Error patching DB for Formulize 3.1.<br>No forms exist in the database.<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
                }
		$array1 = $xoopsDB->fetchArray($result1); // for 2.1 we were checking explicitly whether we needed to add these fields.  But for 2.2 we just ran the SQL and caught the error appropriately in the condition below (ie: looked for failure for 'commonvalue' and ignored it) -- although ele_disabled was added this way...clearly we're not consistent about the patch approach!
		
		if(!array_key_exists('ele_desc',$array1)) {
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_desc` text NULL";
		}
		if(!array_key_exists('ele_delim',$array1)) {
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_delim` varchar(255) NOT NULL default ''";
		}
		if(!array_key_exists('ele_colhead',$array1)) {
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_colhead` varchar(255) NULL default ''";
		}
		if(!array_key_exists('ele_private',$array1)) {
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_private` tinyint(1) NOT NULL default '0'";
		}
                if(!array_key_exists('ele_disabled',$array1)) {
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_disabled` varchar(255) NOT NULL default '0'";
		}
		if(!array_key_exists('ele_uitext',$array1)) {
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_uitext` text NOT NULL";
		}
    
                // these commands can be run more than once, so no need to check them
                $sql['entriesperpage'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_listofentries") . " ADD `entriesperpage` int(1) NOT NULL"; // part of 2.3, but dev sites will not have it, so they must be patched up to include this
                $sql['hiddencolumns'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_listofentries") . " ADD `hiddencolumns` text NOT NULL"; // part of 2.3, but dev sites will not have it, so they must be patched up to include this
								$sql['tableforms'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD `tableform` varchar(255) default NULL"; // part of 2.3, but dev sites will not have it, so they must be patched up to include this
		$sql['commonvalue'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_framework_links") . " ADD `fl_common_value` tinyint(1) NOT NULL default '0'";
		$sql['dropindex'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_form") . " DROP INDEX `ele_id`";
		$sql['deleteyyyy'] = "DELETE FROM " . $xoopsDB->prefix("formulize_form") . " WHERE ele_value =\"YYYY-mm-dd\" AND ele_type=\"date\"";
                // change alterations not checked for success below, since they can be repeated
		$sql['headerlist'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") .  " CHANGE `headerlist` `headerlist` text";
		$sql['grouplist'] = "ALTER TABLE " . $xoopsDB->prefix("group_lists") .  " CHANGE `gl_groups` `gl_groups` text NOT NULL";
                $sql['importidreqs'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_valid_imports") . " CHANGE `id_reqs` `id_reqs` text NOT NULL";
                $sql['sv_asearch'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " CHANGE `sv_asearch` `sv_asearch` text";
                $sql['sv_oldcols'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " CHANGE `sv_oldcols` `sv_oldcols` text";
                $sql['sv_currentview'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " CHANGE `sv_currentview` `sv_currentview` text";
                $sql['sv_calc_cols'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " CHANGE `sv_calc_cols` `sv_calc_cols` text";
                $sql['sv_calc_calcs'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " CHANGE `sv_calc_calcs` `sv_calc_calcs` text";
                $sql['sv_calc_blanks'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " CHANGE `sv_calc_blanks` `sv_calc_blanks` text";
                $sql['sv_calc_grouping'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " CHANGE `sv_calc_grouping` `sv_calc_grouping` text";
                $sql['sv_quicksearches'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " CHANGE `sv_quicksearches` `sv_quicksearches` text";
								$sql['fixlsbapos'] = "UPDATE " . $xoopsDB->prefix("formulize_form") . " SET `ele_value` = REPLACE(`ele_value`, '&#039;', '\'') WHERE `ele_type` = 'select' AND `ele_value` LIKE '%#*=:*%'"; // during the 2.2 patch, some apostrophes in the ele_value field would have been converted to html chars incorrectly
		$sql['sv_pubgroups'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " CHANGE `sv_pubgroups` `sv_pubgroups` text";
                $sql['id_req_int'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_form") . " CHANGE `id_req` `id_req` int(7)";
								$sql['import_fid'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_valid_imports") . " ADD `fid` int(5)";
                $sql['useToken'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen") . " ADD `useToken` tinyint(1) NOT NULL";
                $sql['notCreator'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_notification_conditions") . " ADD `not_cons_creator` tinyint(1)";
                $sql['notElementUids'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_notification_conditions") . " ADD `not_cons_elementuids` smallint(5) NOT NULL default 0";
                $sql['notLinkCreator'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_notification_conditions") . " ADD `not_cons_linkcreator` smallint(5) NOT NULL default 0";
                $sql['printAll'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_multipage") . " ADD `printall` TINYINT( 1 ) NOT NULL";
                $sql['pageTitles'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_multipage") . " ADD `pagetitles` TEXT NOT NULL AFTER `pages`";
								$sql['paraentryform'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_multipage") . " ADD `paraentryform` int(11) NOT NULL default 0";
								$sql['paraentryrel'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_multipage") . " ADD `paraentryrelationship` tinyint(1) NOT NULL default 0";
                $sql['ele_handle'] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_handle` varchar(255) NOT NULL default ''";
                $sql['viewentryscreen'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_listofentries") . " ADD `viewentryscreen` varchar(10) NOT NULL DEFAULT ''"; 
                $sql['otherint1'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_other") . " CHANGE `other_id` `other_id` INT(5) NOT NULL AUTO_INCREMENT";
                $sql['otherint2'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_other") . " CHANGE `id_req` `id_req` INT(5)";
                $sql['ele_caption_text'] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " CHANGE `ele_caption` `ele_caption` text NOT NULL";
								
		foreach($sql as $key=>$thissql) {
			if(!$result = $xoopsDB->query($thissql)) {
				if($key === "dropindex") {
					print "Ele_id Index already dropped.  result: OK<br>";
				} elseif($key === "tableforms") {
					print "Tableform option already present.  result: OK<br>";
				} elseif($key === "deleteyyyy") {
					print "No redundant date values found.  result: OK<br>";
				} elseif($key === "commonvalue") {
					print "Common framework value already added.  result: OK<br>";
        } elseif($key === "entriesperpage") {
          print "Entries per page option already added.  result: OK<br>";
        } elseif($key === "hiddencolumns") {
          print "Hidden columns option already added.  result: OK<br>";
        } elseif($key === "useToken") {
          print "Security token awareness already added to screens.  result: OK<br>";
        } elseif($key === "notCreator" OR $key === "notElementUids" OR $key === "notLinkCreator" ) {
          print "Additional notification options already added.  result: OK<br>";
        } elseif($key === "printAll") {
          print "Multipage form \"print all\" option already added.  result: OK<br>";
        } elseif($key === "pageTitles") {
          print "Multipage form \"page titles\" options already added.  result: OK<br>";
				} elseif($key === "paraentryform") {
          print "Multipage form \"parallel entry form\" option already added.  result: OK<br>";
				} elseif($key === "paraentryrel") {
          print "Multipage form \"parallel entry form relationship\" option already added.  result: OK<br>";
				} elseif($key ==="onetoone_main") {
					print "Onetoone \"main_fid\" field already added.  result: OK<br>";
				} elseif($key === "onetoone_link") {
					print "Onetoone \"link_fid\" field already added.  result: OK<br>";
				} elseif($key === "import_fid") {
					print "Form id field already added to the import table.  result: OK<br>";
				} elseif($key === "ele_handle") {
					// assume it has already been added
					if($secondaryResult = $xoopsDB->query("ALTER TABLE " . $xoopsDB->prefix("formulize") . " CHANGE `ele_handle` `ele_handle` varchar(255) NOT NULL default ''")) {
						print "Element handle field already added.  result: OK<br>";	
					} else {
						exit("Error patching DB for Formulize 3.1. SQL dump:<br>ALTER TABLE " . $xoopsDB->prefix("formulize") . " CHANGE `ele_handle` `ele_handle` varchar(255) NOT NULL default ''<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
					}
				} elseif($key === "viewentryscreen") {
					// assume it has already been added
					if($secondaryResult = $xoopsDB->query("ALTER TABLE " . $xoopsDB->prefix("formulize_screen_listofentries") . " CHANGE `viewentryscreen` `viewentryscreen` varchar(10) NOT NULL DEFAULT ''")) {
						print "viewentryscreen field already added.  result: OK<br>";	
					} else {
						exit("Error patching DB for Formulize 3.1. SQL dump:<br>ALTER TABLE " . $xoopsDB->prefix("formulize_screen_listofentries") . " CHANGE `viewentryscreen` `viewentryscreen` varchar(10) NOT NULL DEFAULT ''<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
					}
				}else {
					exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $thissql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
				}
			} elseif($key === "ele_handle") { // we just added the ele_handle field
				
				// use element id number for the initial element handles
				$eh_eleid_sql = "UPDATE " . $xoopsDB->prefix("formulize") . " SET ele_handle = ele_id WHERE ele_handle = ''";
				if(!$eh_eleid_res = $xoopsDB->query($eh_eleid_sql)) {
					exit("Error patching DB for Formulize 3.1.  SQL dump:<br>" . $eh_check_sql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
				}
				
			}
		}

                if($need22DataChecks) {
                        // lock formulize_form
                        $xoopsDB->query("LOCK TABLES " . $xoopsDB->prefix("formulize_form") . " WRITE, " . $xoopsDB->prefix("formulize_form") . " AS t1 READ, " . $xoopsDB->prefix("formulize_form") . " AS t2 READ");
        
                        // check for ambiguous id_reqs
                        print "Searching for ambiguous id_reqs.  Please be patient.  This may take a few minutes on a large database.<br>";
                        $findSql = "SELECT distinct(t1.id_req) FROM " . $xoopsDB->prefix("formulize_form") . " AS t1, " . $xoopsDB->prefix("formulize_form") . " AS t2 WHERE t1.uid != t2.uid AND t1.id_req = t2.id_req";
                        if(!$findRes = $xoopsDB->query($findSql)) { print "None found.<br>"; }
                        // loop through all ambiguous id_reqs and fix them
        
                        while($find = $xoopsDB->fetchArray($findRes)) {
                                print "Found ambiguous id_req: " . $find['id_req'] . "<br>";
                                include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
                                $maxIdReq = getMaxIdReq();		
                                $uidSql = "SELECT distinct(uid) FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req='" . $find['id_req'] . "'";
                                $uidRes = $xoopsDB->query($uidSql);
                                $start = 1;
                                while($uid = $xoopsDB->fetchArray($uidRes)) {
                                        // ignore the first one, since one of the entries can keep the current id_req
                                        if($start) {
                                                print "Uid " . $uid['uid'] . " unchanged<br>";
                                                $start = 0;
                                                continue;
                                        }
                                        $fixSql = "UPDATE " . $xoopsDB->prefix("formulize_form") . " SET id_req='$maxIdReq' WHERE id_req='" . $find['id_req'] . "' AND uid='" . $uid['uid'] . "'";
                                        if(!$fixRes = $xoopsDB->query($fixSql)) {
                                                exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $fixsql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
                                        }
                                        print "Uid " . $uid['uid'] . " now using id_req $maxIdReq<br>";
                                        $maxIdReq++;
                                }
                        }
        
                        // repeat check, but base on id_form instead...
                        correctAmbiguousIdReqsBasedOnFormIds(true); // true causes the uidFocus flag to be set which causes a special error message to appear to the user when duplicates are found.  This message only matters during this particular ambiguous ID pass
                        print "Finished checking for ambiguous id_reqs.<br>";
        
                        // unlock tables
                        $xoopsDB->query("UNLOCK TABLES");
        
                        // check for old data
                        print "Checking for old data left over from deleted form elements.  This may take a few minutes on a large database<br>";
                        $formsSql = "SELECT ele_caption, id_form FROM " . $xoopsDB->prefix("formulize");
                        $formsRes = $xoopsDB->query($formsSql);
                        while($formArray = $xoopsDB->fetchArray($formsRes)) {
                                $newcap = str_replace("'", "`", $formArray['ele_caption']);
                                $newcap = str_replace("&quot;", "`", $newcap);
                                $newcap = str_replace("&#039;", "`", $newcap);
                                $formCaptions[$formArray['id_form']][$newcap] = 1;
                        }
                        $dataSql = "SELECT id_form, ele_caption FROM " . $xoopsDB->prefix("formulize_form");
                        $dataRes = $xoopsDB->query($dataSql);
                        while($dataArray = $xoopsDB->fetchArray($dataRes)) {
                                if(!isset($formCaptions[$dataArray['id_form']][$dataArray['ele_caption']])) {
                                        $deleteSql = "DELETE FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_form=".$dataArray['id_form']." AND ele_caption=\"".formulize_escape($dataArray['ele_caption'])."\"";
                                        if(!$result = $xoopsDB->query($deleteSql)) {
                                                exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $deletesql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
                                        }
                                }
                        }
                        print "Finished checking for old data.  result: OK<br>";
                }

    // added Feb 3 2008 by jwe
    // check for duplicate id_reqs...this is based not based on the same criteria that is in the 22 data check above.
    // in this case we are simply looking for the same id_req being applied to different forms
    // check for ambiguous id_reqs
    // only necessary if 22 checks were not done, since 22 check now includes this function call too
    if(!$need22DataChecks) {
      $xoopsDB->query("LOCK TABLES " . $xoopsDB->prefix("formulize_form") . " WRITE, " . $xoopsDB->prefix("formulize_form") . " AS t1 READ, " . $xoopsDB->prefix("formulize_form") . " AS t2 READ");
      print "Searching for duplicate entry ids in use on two or more forms.  Please be patient.  This may take a few minutes on a large database.<br>";
      correctAmbiguousIdReqsBasedOnFormIds();
      print "Finished checking for duplicate entry ids.<br>";
      // unlock tables
      $xoopsDB->query("UNLOCK TABLES");  
    }
    

		print "DB updates completed.  result: OK";
        } 
}

// this function reads all the derived value fields in the elements list and makes sure there is a field in the data tables for each one
function formulize_createDerivedValueFieldsInDB() {
  
  // 1. gather a list of all the derived elements, including their form ids (so we can reference the tables), and handles (so we know what the field should be called)
  // 2. foreach table, get a list of columns, and if the field name is not represented, then create it
  global $xoopsDB;
  $form_handler =& xoops_getmodulehandler('forms', 'formulize');
  $element_handler =& xoops_getmodulehandler('elements', 'formulize');
  $sql = "SELECT id_form, ele_handle, ele_id FROM ".$xoopsDB->prefix("formulize")." WHERE ele_type = 'derived' ORDER BY id_form";
  if($result = $xoopsDB->query($sql)) {
    $fieldMap = array();
    while($array = $xoopsDB->fetchArray($result)) {
      $fieldMap[$array['id_form']][] = array(0=>$array['ele_handle'], 1=>$array['ele_id']);
    }
    foreach($fieldMap as $fid=>$fields) {
      $sql2 = "SHOW COLUMNS FROM ".$xoopsDB->prefix("formulize_".$fid);
      if($result2 = $xoopsDB->query($sql2)) {
        $existingFields = array();
        while($array2 = $xoopsDB->fetchArray($result2)) {
          $existingFields[] = $array2['Field']; // show columns returns Field Type Null Key Default Extra
        }
      } else {
        return false;
      }
      foreach($fields as $thisFieldInfo) {
        if(!in_array($thisFieldInfo[0], $existingFields)) { // 0 is the handle
          if(!$insertResult = $form_handler->insertElementField($element_handler->get($thisFieldInfo[1]))) { // 1 is the id
            exit("Error: could not add the derived value field, $thisFieldInfo[1], to the data table.");
          }
        }
      }
    }
    
    // now that we know we have fields for all the derived value elements, tell the user to populate them with the correct data
    print "Before you can do calculations or sort lists based on derived values, you will have to go to every page in each list of entries that has derived values.  Going to each page will cause the derived values to be cached in the database, so that calculations and sorts based on the derived values will work.<br>\n";
    
    return true;
  } else {
    return false;
  }
  
}

// this function handles the checking for ambiguous id_reqs based on the form id
function correctAmbiguousIdReqsBasedOnFormIds($uidFocus = false) {

  global $xoopsDB;
  $findSql = "SELECT distinct(t1.id_req) FROM " . $xoopsDB->prefix("formulize_form") . " AS t1, " . $xoopsDB->prefix("formulize_form") . " AS t2 WHERE t1.id_form != t2.id_form AND t1.id_req = t2.id_req";
  if(!$findRes = $xoopsDB->query($findSql)) { print "None found.<br>"; }
  // loop through all ambiguous id_reqs and fix them

  while($find = $xoopsDB->fetchArray($findRes)) {
          print "Found ambiguous id_req: " . $find['id_req'] . "<br>";
          include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
          $maxIdReq = getMaxIdReq();		
          $fidSql = "SELECT distinct(id_form) FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req='" . $find['id_req'] . "' ORDER BY id_form";
          $fidRes = $xoopsDB->query($fidSql);
          $start = 1;
          while($id_form = $xoopsDB->fetchArray($fidRes)) {
            // ignore the first one, since one of the entries can keep the current id_req
            if($start) {
              print "Form id " . $id_form['id_form'] . " unchanged<br>";
              $start = 0;
              continue;
            }
            $fixSql = "UPDATE " . $xoopsDB->prefix("formulize_form") . " SET id_req='$maxIdReq' WHERE id_req='" . $find['id_req'] . "' AND id_form='" . $id_form['id_form'] . "'";
            if(!$fixRes = $xoopsDB->query($fixSql)) {
              exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $fixsql . "<br>".$xoopsDB->error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
            }
            print "Form id " . $id_form['id_form'] . " now using id_req $maxIdReq<br>";
            if($uidFocus) { print "EITHER ENTRY " . $find['id_req'] . " OR ENTRY $maxIdReq HAS THE WRONG OWNER (uid).  THERE IS NO WAY FOR THE SYSTEM TO TELL WHICH IS INCORRECT. YOU SHOULD CHECK THE ENTRIES AND MODIFY THE UID COLUMN IN THE DATABASE FOR THE ONE ENTRY THAT IS INCORRECT.  THE PROPER SQL SHOULD BE LIKE THIS: \"UPDATE xoops_formulize_form SET uid=123 WHERE id_req=456\" WHERE 123 IS THE CORRECT UID AND 456 IS THE ENTRY THAT CURRENTLY HAS AN INCORRECT UID.  PLEASE CONTACT FREEFORM SOLUTIONS FOR ASSISTANCE IF YOU ARE AT ALL UNSURE ABOUT THIS PROCEDURE!<br>"; }
            $maxIdReq++;
          }
  }

}

// convert data to the new no slashes, HTML special chars format
// previously, Formulize erroneously added slashes to the data that was stored in the database, meaning that an apostrophe was stored as \'
// now, slashes are removed from values entered in a form if magic quotes is on, and then htmlspecialchars is run on the values
// this results in apostrophes, for instance, stored as &#039;
// the search logic in the extract.php file has been modified as well to run htmlspecialchars on search terms so that matches are found properly
function patch22convertdata() {

  global $xoopsDB;
  $patchCheckSql = "SHOW TABLES";
	$resultPatchCheck = $xoopsDB->queryF($patchCheckSql);
  $formulizeFormFound = false;
  $newStructureFound = false;
  $entryOwnerGroupFound = false;
	while($table = $xoopsDB->fetchRow($resultPatchCheck)) {
    $secondPart = substr($table[0], strlen($xoopsDB->prefix("formulize_")));
    if(is_numeric($secondPart) AND strstr($table[0], "formulize_")) {
      $newStructureFound = true;
    }
    if($table[0] == $xoopsDB->prefix("formulize_form")) {
      $formulizeFormFound = true;
    }
    if($table[0] == $xoopsDB->prefix("formulize_entry_owner_groups")) {
      $entryOwnerGroupFound = true;
    }
	}
  if(!$formulizeFormFound AND $entryOwnerGroupFound) {
          print "<h1>It appears you have not upgraded from a previous version of Formulize.  You do not need to apply this patch unless you are upgrading from a version prior to 2.2</h1>\n";
          print "<p>If you did upgrade from a previous version, please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.</p>\n";
          return;
  }
  if($newStructureFound) {
      print "<h1>You cannot run this patch after upgrading to the 3.0 data structure.</h1>";
      print "<p>If you upgraded from Formulize 2.1 or earlier, and you did not run the \"patch22convertdata\" already, please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.</p>";
      return;
  }

	

	if(!isset($_POST['patch22convertdata'])) {

		// detect name of Formulize table
		$sql = "SELECT * FROM " . $xoopsDB->prefix("formulize_form") . " LIMIT 0,1";
		if($res = $xoopsDB->query($sql)) {

			print "<form action=\"formindex.php?op=patch22convertdata\" method=post>";
			print "<h1>Warning: this patch changes the formatting of data in your database, primarily to address security issues in how data is being stored.  Backup your database prior to applying this patch!</h1>";
			print "<h1>DO NOT APPLY THIS PATCH TWICE.  If you apply this patch again after applying it once already, then some data in your database may be damaged.  So, please backup your database prior to applying this patch!  If there is an error when the patch runs, returning to a backup is the only way to ensure the integrity of your data.</h1>";
			print "<p>This patch may take a few minutes to apply.  Your page may take that long to reload, please be patient.</p>";
                        print "<p>If you applied this patch previously when upgrading to Formulize 2.2, DO NOT apply it again when upgrading to a higher version!</p>";
                        print "<p>If the first version of Formulize that you installed was 2.2 or higher, you DO NOT need to apply this patch!</p>";
			print "<input type = submit name=patch22convertdata value=\"Apply Data Conversion Patch for upgrading to Formulize 2.2 and higher\">";
			print "</form>";
		} else {
			print "<h1>You do not appear to have applied 'patch31'.</h1>\n";
			print "<p>You must apply patch31 before applying this patch.  <a href=\"" . XOOPS_URL . "/modules/formulize/admin/formindex.php?op=patch31\">Click here to run \"patch31\".</a></p>";
		}
	} else {
		print "<h2>Patch Results:</h2>";
		print "Sanitizing form entries.  On a large database, this may take a long time.<br>";

		global $myts;
		if(!$myts) { $myts =& MyTextSanitizer::getInstance(); }

		$sansql = "SELECT ele_id, ele_value FROM " . $xoopsDB->prefix("formulize_form") . " WHERE ele_type != \"date\" AND  ele_type != \"yn\" AND ele_type != \"areamodif\"";
		if(!$sanres = $xoopsDB->query($sansql)) { exit("Error patching DB for Formulize 2.2. SQL dump:<br>" . $sansql . "<br>".$xoopsDB->error()."<br>Could not collect all data for sanitizing.  Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance."); }
		while($sanArray = $xoopsDB->fetchArray($sanres)) {
			$origvalue = $sanArray['ele_value'];
			if(get_magic_quotes_gpc()) { $sanArray['ele_value'] = stripslashes($sanArray['ele_value']); }
			$newvalue = $myts->htmlSpecialChars($sanArray['ele_value']);
			if($newvalue != $origvalue) {
				$newsql = "UPDATE " . $xoopsDB->prefix("formulize_form") . " SET ele_value = \"" . formulize_escape($newvalue) . "\" WHERE ele_id = " . $sanArray['ele_id'];
				if(!$newres = $xoopsDB->query($newsql)) {
					exit("Error patching DB for Formulize 2.2. SQL dump:<br>" . $sansql . "<br>".$xoopsDB->error()."<br>Could not write data for sanitizing.  Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
				}
			}
		}

		print "Sanitizing form entries completed.  result: OK<br>";
	}
}

// this patch copies the formulize_form table to separate datatables based on each form in the system
// this should have been done three years ago!
function patch30DataStructure($auto = false) {
        
        global $xoopsDB;
        // check for new data structure and don't run this patch if it already has been!
        // check that patch30 has been run and don't run this patch unless it already has been!
        // check that formulize_form table exists, or else don't run the patch
        $patchCheckSql = "SHOW TABLES";
        $resultPatchCheck = $xoopsDB->queryF($patchCheckSql);
        $entryOwnerGroupFound = false;
        $formulizeFormFound = false;
        $newStructureFound = false;
        while($table = $xoopsDB->fetchRow($resultPatchCheck)) {
          $secondPart = substr($table[0], strlen($xoopsDB->prefix("formulize_")));
          if(is_numeric($secondPart) AND strstr($table[0], $xoopsDB->prefix("formulize_"))) { // there will be a part after "formulize_" that is numeric in the new data structure
            $newStructureFound = true;
          }
          if($table[0] == $xoopsDB->prefix("formulize_entry_owner_groups")) {
            $entryOwnerGroupFound = true;
          }
          if($table[0] == $xoopsDB->prefix("formulize_form")) {
            $formulizeFormFound = true;
          }
        }
        if(!$formulizeFormFound AND $entryOwnerGroupFound) {
          print "<h1>It appears you have not upgraded from a previous version of Formulize.  You do not need to apply this patch unless you are upgrading from a version prior to 3.0</h1>\n";
          print "<p>If you did upgrade from a previous version, please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.</p>\n";
          return;
        }
        if(!$entryOwnerGroupFound) {
          print "<h1>You must run \"patch31\" before upgrading to the 3.0 data structure.</h1>\n";
          print "<p><a href=\"" . XOOPS_URL . "/modules/formulize/admin/formindex.php?op=patch31\">Click here to run \"patch31\".</a></p>\n";
          return;
        }
        if($newStructureFound) {
            print "<h1>You cannot run this patch after upgrading to the 3.0 data structure.</h1>";
            return;
        }

        $carryon = true;
        if(!$auto) { // put UI control in if not called from another function....not actually used; this patch must be invoked manually on its own.
                if(!isset($_POST['patch30datastructure'])) {
                        $carryon = false;
												print "<form action=\"formindex.php?op=patch30datastructure\" method=post>";
												print "<h1>Warning: this patch completely changes the structure of the formulize data in your database.  Backup your database prior to applying this patch!</h1>";
												print "<p>This patch may take a few minutes to apply.  Your page may take that long to reload, please be patient.</p>";
                        print "<p>You may need to increase the memory limit and/or max execution time in PHP, if you have a large database (100,000 records or more, depending on the size of your forms).</p>";
                        print "<p>If the first version of Formulize that you installed was 3.0 or higher, you DO NOT need to apply this patch!</p>";
												print "<input type = submit name=patch30datastructure value=\"Apply Data Structure Patch for upgrading to Formulize 3.0 and higher\">";
												print "</form>";
								} 
				}
        
        if($carryon) {
                        print "<h2>Patch Results:</h2>";
                // 1. figure out all the forms in existence
                // 2. for each one, devise the field names in its table
                // 3. create its table
                // 4. import its data from formulize_form
                
                
                include_once XOOPS_ROOT_PATH . "/modules/formulize/class/forms.php";
                include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
                $formHandler =& xoops_getmodulehandler('forms', 'formulize');
                $allFormObjects = $formHandler->getAllForms(true); // true flag causes all elements to be included in objects, not just elements that are being displayed, which are ignored in every other situation
                foreach($allFormObjects as $formObjectId=>$thisFormObject) {
												if($thisFormObject->getVar('tableform')) { continue; } // only process actual Formulize forms
                        if(!$tableCreationResult = $formHandler->createDataTable($thisFormObject)) {
                                exit("Error: could not make the necessary new datatable for form " . $thisFormObject->getVar('id_form') . ".<br>".$xoopsDB->error()."<br>Please report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.");
                        }
                        
                        print "Created data table formulize_" . $thisFormObject->getVar('id_form') . ".  result: OK<br>\n";
                        
                        // map data in formulize_form into new table
                        // 1. get an index of the captions to element ids
                        // 2. get all the data organized by id_req
                        // 3. insert the data
                        
                        $captionPlusHandlesSQL = "SELECT ele_caption, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form = " . $thisFormObject->getVar('id_form');
                        $captionPlusHandlesRes = $xoopsDB->query($captionPlusHandlesSQL);
                        $captionHandleIndex = array();
                        while($captionPlusHandlesArray = $xoopsDB->fetchArray($captionPlusHandlesRes)) {
                                $captionHandleIndex[str_replace("'", "`", $captionPlusHandlesArray['ele_caption'])] = $captionPlusHandlesArray['ele_handle'];
                        }
                                                
                        $dataSQL = "SELECT id_req, ele_caption, ele_value, ele_type FROM " .$xoopsDB->prefix("formulize_form") . " WHERE id_form = " . $thisFormObject->getVar('id_form') . " AND ele_type != \"areamodif\" AND ele_type != \"sep\" ORDER BY id_req"; // for some reason areamodif and sep are stored in some really old data
                        $dataRes = $xoopsDB->query($dataSQL);
                        $prevIdReq = "";
                        $insertSQL = "";
                        unset($foundCaptions);
                        $foundCaptions = array();
                        while($dataArray = $xoopsDB->fetchArray($dataRes)) {
                                if(!isset($captionHandleIndex[$dataArray['ele_caption']])) {
																	if($dataArray['ele_caption'] === '') {
																		print "Warning: you have data saved, with no caption specified, for entry number ". $dataArray['id_req'] . " (id_req) in form ".$thisFormObject->getVar('id_form'). ".  This data will be ignored.  Please contact <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a> if you would like assistance cleaning this up.  This will NOT affect the upgrade to version 3.<br>";
																		continue;
																	} else {
																		print "Warning: the form ". $thisFormObject->getVar('id_form') . " does not have an element with the caption '". $dataArray['ele_caption'] . "', but you have saved data associated with that caption.  This data will be ignored.  Please contact <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a> if you would like assistance cleaning this up.  This will NOT affect the upgrade to version 3.<br>";
                                    continue;
																	}
                                }
                                if($dataArray['id_req'] != $prevIdReq) { // we're on a new entry
                                        unset($foundCaptions);
                                        $foundCaptions = array(); // reset the list of captions we've found in this entry so far, since we're moving onto a different entry
                                        $prevIdReq = $dataArray['id_req'];
                                        // write whatever we just finished working on
                                        if($insertSQL) {
                                                if(!$insertRes = $xoopsDB->query($insertSQL)) {
                                                        exit("Error: could not write data to the new table structure with this SQL: $insertSQL.<br>".$xoopsDB->error()."<br>Please report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.");
                                                }
                                                $insertSQL = "";
                                        }
                                        // build the SQL for inserting this entry
                                        $insertSQL = "INSERT INTO " . $xoopsDB->prefix("formulize_" . $thisFormObject->getVar('id_form')) . " SET entry_id = \"" . $dataArray['id_req'] . "\"";
                                        $metaData = getMetaData($dataArray['id_req'], "", "", true); // special last param necessary because we need to use the old meta process when doing this patch!
                                        $creation_uid = $metaData['created_by_uid'];
                                        $mod_uid = $metaData['last_update_by_uid'];
                                        $creation_datetime = $metaData['created'] == "???" ? "" : $metaData['created'];
                                        $mod_datetime = $metaData['last_update'];
                                        $insertSQL .= ", creation_datetime = \"$creation_datetime\", mod_datetime = \"$mod_datetime\", creation_uid = \"$creation_uid\", mod_uid = \"$mod_uid\"";
																				
																				// derive the owner groups and write them to the owner groups table
																				$ownerGroups = array();
																				if($creation_uid) {
																					$member_handler =& xoops_gethandler('member');
																					$creationUser = $member_handler->getUser($creation_uid);
																					if(is_object($creationUser)) {
																						$ownerGroups = $creationUser->getGroups();
																					} else {
																						$ownerGroups[] = XOOPS_GROUP_ANONYMOUS;
																					}
																				} else {
																					$ownerGroups[] = XOOPS_GROUP_ANONYMOUS;
																				}
																				foreach($ownerGroups as $thisGroup) {
																					$ownerInsertSQL = "INSERT INTO " . $xoopsDB->prefix("formulize_entry_owner_groups") . " (`fid`, `entry_id`, `groupid`) VALUES ('". intval($thisFormObject->getVar('id_form')) . "', '". intval($dataArray['id_req']) . "', '". intval($thisGroup) . "')";
																					if(!$ownerInsertRes = $xoopsDB->query($ownerInsertSQL)) {
																						print "Error: could not write owner information to new data structure, using this SQL:<br>$ownerInsertSQL<br>".$xoopsDB->error()."<br>Please report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.";
																					}
																				}
                                }
                                
                                // record the caption and go through to the next one if this one already exists in this form
                                if(isset($foundCaptions[$dataArray['ele_caption']])) {
                                  print "Warning: you have duplicate captions, '".$dataArray['ele_caption']."', in your data, at entry number " .$dataArray['id_req'] . " (id_req) in form ".$thisFormObject->getVar('id_form').".  Only the first value found will be copied to the new data structure.  Please contact <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a> if you would like assistance cleaning this up.  This will NOT affect the upgrade to version 3.<br>";
                                  continue;
                                } else {
                                  $foundCaptions[$dataArray['ele_caption']] = true;
                                }                                
                                
                                // need to handle linked selectboxes, and convert them to a different format, and store the entry_id of the sources
                                // We are going to store a comma separated list of entry_ids, with leading and trailing commas so a LIKE operator can be used to do a join in the database
                                if(strstr($dataArray['ele_value'], "#*=:*")) {
                                        $boxproperties = explode("#*=:*", $dataArray['ele_value']);
                                        $source_ele_ids = explode("[=*9*:", $boxproperties[2]);
                                        // get the id_reqs of the source ele_ids
                                        $sourceIdReqSQL = "SELECT id_req FROM " . $xoopsDB->prefix("formulize_form") . " WHERE ele_id = " . implode(" OR ele_id = ", $source_ele_ids) . " ORDER BY id_req";
                                        $sourceIdReqRes = $xoopsDB->query($sourceIdReqSQL);
                                        $dataArray['ele_value'] = "";
                                        while($sourceIdReqArray = $xoopsDB->fetchArray($sourceIdReqRes)) {
                                                $dataArray['ele_value'] .=  "," . $sourceIdReqArray['id_req'];
                                        }
                                        if($dataArray['ele_value']) {
                                          $dataArray['ele_value'] .= ",";  
                                        }
                                }
                                if($dataArray['ele_type'] == "date" AND $dataArray['ele_value'] == "") {
																	continue; // don't write in blank date values, let them get the default NULL value for the field
																} 
																
                                $insertSQL .= ", `" . $captionHandleIndex[$dataArray['ele_caption']] . "`=\"" . formulize_escape($dataArray['ele_value']) . "\"";
                        }
                        if($insertSQL) {
                                if(!$insertRes = $xoopsDB->query($insertSQL)) {
                                        exit("Error: could not write data to the new table structure with this SQL: $insertSQL.<br>".$xoopsDB->error()."<br>Please report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.");
                                }
                        }
                        print "Migrated data to new data structure for form " . $thisFormObject->getVar('id_form') . ".  result: OK<br>\n";
                        unset($allFormObjects[$formObjectId]); // attempt to free up some memory
                }
      
      if($derivedResult = formulize_createDerivedValueFieldsInDB()) {
        print "Created derived value fields in database.  result: OK<br>\n";
      } else {
        print "Unable to create derived value fields in database.  result: failed.  contact <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a> for assistance.<br>\n";
      }
      
      // convert the captions in the linked selectbox defintions to the handles for those elements
      // 1. lookup all elements that are linked selectboxes in the formulize table (element table) -- db query for element ids
      // 2. for each one, get the caption that is stored there -- PHP level work with element handler
      // 3. get the handle corresponding to that caption
      // 4. rewrite the ele_value[2] with the handle instead of caption
      // 5. reinsert that value into the DB
      $sql = "SELECT ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_value LIKE '%#*=:*%'";
      if(!$res = $xoopsDB->query($sql)) {
        exit("Error: cound not get the element ids of the linked selectboxes.  SQL: $sql<br>".$xoopsDB->error()."<br>Please report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.");
      }
      $element_handler =& xoops_getmodulehandler('elements', 'formulize');
      while($array = $xoopsDB->fetchArray($res)) {
        $elementObject = $element_handler->get($array['ele_id']);
        $ele_value = $elementObject->getVar('ele_value');
        $parts = explode("#*=:*", $ele_value[2]);
        $sql2 = "SELECT ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_caption = '". formulize_escape($parts[1]) . "' AND id_form=". $parts[0];
				//print "$sql2<br>";
        if(!$res2 = $xoopsDB->query($sql2)) {
          exit("Error: could not get the handle for a linked selectbox source.  SQL: $sql2<br>".$xoopsDB->error()."<br>Please report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.");
        }
        $array2 = $xoopsDB->fetchArray($res2);
        if($array2['ele_handle'] == "") {
          print "Warning: a handle could not be identified for this caption: '".$parts[1]."', in form ".$parts[0]."  This breaks linked selectboxes for element number ".$array['ele_id'].".  This is most likely caused by an old caption that was changed for the element, in an old version of Formulize.<br>Please report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.<br>";
        }
        $ele_value[2] = $parts[0]."#*=:*".$array2['ele_handle'];
        $elementObject->setVar('ele_value', $ele_value);
        if(!$res3 = $element_handler->insert($elementObject)) {
          exit("Error: could not update the linked selectbox metadata. <br>".$xoopsDB->error()."<br>Please report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.");
        }
        unset($parts);
        unset($elementObject);
      }
      print "Updated the linked selectbox definitions new metadata.  result: OK<br><br><b>NOTE:</b> Although the 3.0 data structure is highly optimized compared to previous versions of Formulize, there are some situations which we cannot account for automatically in the upgrade process:  if you have elements in a form and users only enter numerical data there, you should edit those elements now and give them a truly numeric data type (use the new option on the element editing page to do this).  In Formulize 3 and higher, elements that have only numbers, but which are not stored as numbers in the database, will not sort properly and some calculations will not work correctly on them either.  Unfortunately, we cannot reliably determine which numeric data type should be used for all elements, therefore you will need to make this adjustment manually.  We apologize for any inconvenience.  Please contact <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a> if you have any questions about this process.<br><br>\n";
      
      print "Data migration complete.  result: OK\n";
		
		}
		
}

if(!defined('_FORMULIZE_UI_PHP_INCLUDED')) {
	include "ui.php"; 
}


