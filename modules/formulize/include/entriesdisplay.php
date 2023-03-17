<?php

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions 					     ##
##  Project: Formulize                                                       ##
###############################################################################

// THIS FILE CONTAINS FUNCTIONS FOR DISPLAYING A SUMMARY OF ENTRIES IN A FORM OR FRAMEWORK, AND DOING SEARCHES AND OTHER OPERATIONS ON THE DATA

// Basic order of operations:
// 1. determine if report is requested
// 2. if report is requested, get report data
// 3. if no report is requested, get scope data and header data for this form
// 4. check to see if search/sort/scope/column/etc changes sent from form
// 5. add/override existing settings with other settings -- add to hidden fields in the form if necessary (queue it up)
// 6. prepare list of available reports and view settings
// 7. draw top UI
// 8. draw notice about changes being made and ready to be applied if applicable?
// 9. draw results.


global $xoopsConfig;
// load the formulize language constants if they haven't been loaded already
	if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
	} else {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
	}

include_once XOOPS_ROOT_PATH . "/modules/formulize/class/usersGroupsPerms.php";
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

// main function
// $screen will be a screen object if present
function displayEntries($formframe, $mainform="", $loadview="", $loadOnlyView=0, $viewallforms=0, $screen=null) {

	formulize_benchmark("start of drawing list");

	global $xoopsDB, $xoopsUser;
	
	// Set some required variables
	$mid = getFormulizeModId();
	list($fid, $frid) = getFormFramework($formframe, $mainform);
	$gperm_handler =& xoops_gethandler('groupperm');
	$member_handler =& xoops_gethandler('member');
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
	$uid = $xoopsUser ? $xoopsUser->getVar('uid') : "0";
	
	if(!$scheck = security_check($fid, "", $uid, "", $groups, $mid, $gperm_handler)) {
		print "<p>" . _NO_PERM . "</p>";
		return;
	}
	
	// must wrap security check in only the conditions in which it is needed, so we don't interfere with saving data in a form (which independently checks the security token)
	$formulize_LOESecurityPassed = (isset($GLOBALS['formulize_securityCheckPassed']) AND $GLOBALS['formulize_securityCheckPassed']) ? true : false;
	if((($_POST['delconfirmed'] OR $_POST['cloneconfirmed'] OR $_POST['delviewid_formulize'] OR $_POST['saveid_formulize'] OR is_numeric($_POST['caid']))) AND !$formulize_LOESecurityPassed) {
		$module_handler =& xoops_gethandler('module');
		$config_handler =& xoops_gethandler('config');
	$formulizeModule =& $module_handler->getByDirname("formulize");
	$formulizeConfig =& $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
	$modulePrefUseToken = $formulizeConfig['useToken'];
		$useToken = $screen ? $screen->getVar('useToken') : $modulePrefUseToken;
		if(isset($GLOBALS['xoopsSecurity']) AND $useToken) {
			$formulize_LOESecurityPassed = $GLOBALS['xoopsSecurity']->check();
		} else { // if there is no security token system, then assume true -- necessary for old versions of XOOPS.
			$formulize_LOESecurityPassed = true;
		}
	}
	
	// check for all necessary permissions
	$add_own_entry = $gperm_handler->checkRight("add_own_entry", $fid, $groups, $mid);
	$delete_own_reports = $gperm_handler->checkRight("delete_own_reports", $fid, $groups, $mid);
	$delete_other_reports = $gperm_handler->checkRight("delete_other_reports", $fid, $groups, $mid);
	$update_other_reports = $gperm_handler->checkRight("update_other_reports", $fid, $groups, $mid);
	$update_own_reports = $gperm_handler->checkRight("update_own_reports", $fid, $groups, $mid);
	$view_globalscope = $gperm_handler->checkRight("view_globalscope", $fid, $groups, $mid);
	$view_groupscope = $gperm_handler->checkRight("view_groupscope", $fid, $groups, $mid);

	$screen_handler = xoops_getmodulehandler('screen', 'formulize');
	
	$settings = array(); // $settings will be filled up with various variables and passed to some other functions as an "easy" way of including all those values
	$currentURL = getCurrentURL();
	$displaytitle = getFormTitle($fid);
	
	// get default info and info passed to page....
	
	// clear any default search text that has been passed (because the user didn't actually search for anything)
	foreach($_POST as $k=>$v) {
		if(substr($k, 0, 7) == "search_" AND $v==_formulize_DE_SEARCH_HELP) {
			unset($_POST[$k]);
			break; // assume this is only sent once, since the help text only appears in the first column
		}
	}

	// check for deletion request (set by 'delete selected' button)
	if ($_POST['delconfirmed'] AND $formulize_LOESecurityPassed) {
		foreach($_POST as $k=>$v) {
			if(substr($k, 0, 7) == "delete_" AND $v != "") {
				$delete_entry_id = substr($k, 7);
				// confirm user has permission to delete this entry
				if (formulizePermHandler::user_can_delete_entry($fid, $uid, $delete_entry_id)) {
					$GLOBALS['formulize_deletionRequested'] = true;
					if($frid) {
						deleteEntry($delete_entry_id, $frid, $fid);
					} else {
						deleteEntry($delete_entry_id, "", $fid);
					}
				}
			}
		}
	}

	// check for cloning request and if present then clone entries
	if($_POST['cloneconfirmed'] AND $formulize_LOESecurityPassed AND $add_own_entry) {
		foreach($_POST as $k=>$v) {
			if(substr($k, 0, 7) == "delete_" AND $v != "") {
				$thisentry = substr($k, 7);
				cloneEntry($thisentry, $frid, $fid, $_POST['cloneconfirmed']); // cloneconfirmed is the number of copies required
			}
		}
	}

	// handle deletion of view...reset currentView
	if($_POST['delview'] AND $formulize_LOESecurityPassed) {
		if(substr($_POST['delviewid_formulize'], 1, 4) == "old_") {
			$delviewid_formulize = substr($_POST['delviewid_formulize'], 5);
		} else {
			$delviewid_formulize = substr($_POST['delviewid_formulize'], 1);
		}

		if($delete_other_reports OR $xoopsUser->getVar('uid') == getSavedViewOwner($delviewid_formulize)) { // "get saved view owner" only works with new saved view format in 2.0 or greater, but since that is 2.5 years old now, should be good to go!
			if(substr($_POST['delviewid_formulize'], 1, 4) == "old_") {
				$sql = "DELETE FROM " . $xoopsDB->prefix("formulize_reports") . " WHERE report_id='" . $delviewid_formulize . "'";
			} else {
				$sql = "DELETE FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_id='" . $delviewid_formulize . "'";
			}
			if(!$res = $xoopsDB->query($sql)) {
				exit("Error deleting report: " . $_POST['delviewid_formulize']);
			}
			unset($_POST['currentview']);
			$_POST['resetview'] = 1;
		}
	}
	
	// if resetview is set, then unset POST and then set currentview to resetview
	// intended for when a user switches from a locked view back to a basic view.  In that case we want all settings to be cleared and everything to work like the basic view, rather than remembering, for instance, that the previous view had a calculation or a search of something.
	// users who view reports (views) that aren't locked can switch back to a basic view and retain settings.  This is so they can make changes to a view and then save the updates.  It is also a little confusing to switch from a predefined view to a basic one but have the predefined view's settings still hanging around.
	// recommendation to users should be to lock the controls for all published views.
	// (this routine also invoked when a view has been deleted)
	$resetview = false;
	if($_POST['resetview']) {
		$resetview = $_POST['currentview'];
		foreach($_POST as $k=>$v) {
			unset($_POST[$k]);
		}
		$_POST['currentview'] = $resetview;
	}

	// handle saving of the view if that has been requested
	// only do this if there's a saveid_formulize and they passed the security check, and any one of these:  they can update other reports, or this is a "new" view, or this is not a new view, and it belongs to them and they have update own reports permission
	if($_POST['saveid_formulize'] AND $formulize_LOESecurityPassed AND ($update_other_reports OR ((is_numeric($_POST['saveid_formulize']) AND ($update_own_reports AND $xoopsUser->getVar('uid') == getSavedViewOwner($_POST['saveid_formulize']))) OR $_POST['saveid_formulize'] == "new"))) {
		// gather all values
		//$_POST['currentview'] -- from save (they might have updated/changed the scope)
		//possible situations:
		// user replaced a report, so we need to set that report as the name of the dropdown, value is currentview
		// user made a new report, so we need to set that report as the name and the value is currentview
		// so name of the report gets sent to $loadedView, which also gets assigned to settings array
		// report is either newid or newname if newid is "new"
		// newscope goes to $_POST['currentview']
		//$_POST['oldcols'] -- from page
		//$_POST['asearch'] -- from page
		//$_POST['calc_cols'] -- from page
		//$_POST['calc_calcs'] -- from page
		//$_POST['calc_blanks'] -- from page
		//$_POST['calc_grouping'] -- from page
		//$_POST['sort'] -- from page
		//$_POST['order'] -- from page
		//$_POST['hlist'] -- passed from page
		//$_POST['hcalc'] -- passed from page
		//$_POST['lockcontrols'] -- passed from save
		//and quicksearches -- passed with the page
		// pubgroups -- passed from save

		$_POST['currentview'] = $_POST['savescope'];
		$saveid_formulize = $_POST['saveid_formulize'];
		$_POST['lockcontrols'] = $_POST['savelock'];
		$savegroups = $_POST['savegroups'];

		// put name into loadview
		if($saveid_formulize != "new") {
			if(!strstr($saveid_formulize, "old_")) { // if it's not a legacy report...
				$sname = q("SELECT sv_name, sv_owner_uid FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_id = \"" . substr($saveid_formulize, 1) . "\"");
				if($sname[0]['sv_owner_uid'] == $uid) {
					$loadedView = $saveid_formulize;
				} else {
					$loadedView =  "p" . substr($saveid_formulize, 1);
				}
			}
		}
		$savename = $_POST['savename'];

		// flatten quicksearches -- one value in the array for every column in the view
		$allcols = explode(",", $_POST['oldcols']);
		foreach($allcols as $thiscol) {
			$allquicksearches[] = $_POST['search_' . $thiscol];
		}
		// need to grab all hidden quick searches and then add any hidden columns to the column list...need to reverse this process when loading views
		foreach($_POST as $k=>$v) {
			if(substr($k, 0, 7) == "search_" AND $v != "") {
				if(!in_array(substr($k, 7), $allcols)						// if this column is hidden
					AND substr($v, 0, 1) == "!" AND substr($v, -1) == "!")	// if this is a persistent search
				{
					$_POST['oldcols'] .= ",hiddencolumn_".substr($k, 7);
					$allquicksearches[] = $v;
				}
			}
		}

		$qsearches = implode("&*=%4#", $allquicksearches);

		$savename = formulize_db_escape($savename);
		$savesearches = formulize_db_escape($_POST['asearch']);
		//print $_POST['asearch'] . "<br>";
		//print "$savesearches<br>";
		$qsearches = formulize_db_escape($qsearches);

		if($frid) {
			$saveformframe = $frid;
			$savemainform = $fid;
		} else {
			$saveformframe = $fid;
			$savemainform = "";
		}

		if($saveid_formulize == "new" OR strstr($saveid_formulize, "old_")) {
			if ($saveid_formulize == "new") {
				$owneruid = $uid;
				$moduid = $uid;
			} else {
				// get existing uid
				$olduid = q("SELECT report_uid FROM " . $xoopsDB->prefix("formulize_reports") . " WHERE report_id = '" . substr($saveid_formulize, 5) . "'");
				$owneruid = $olduid[0]['report_uid'];
				$moduid = $uid;
			}
			$savesql =
				"INSERT INTO " . $xoopsDB->prefix("formulize_saved_views") . " (" .
					"sv_name, " .
					"sv_pubgroups, " .
					"sv_owner_uid, " .
					"sv_mod_uid, " .
					"sv_formframe, " .
					"sv_mainform, " .
					"sv_lockcontrols, " .
					"sv_hidelist, " .
					"sv_hidecalc, " .
					"sv_asearch, " .
					"sv_sort, " .
					"sv_order, " .
					"sv_oldcols, " .
					"sv_currentview, " .
					"sv_calc_cols, " .
					"sv_calc_calcs, " .
					"sv_calc_blanks, " .
					"sv_calc_grouping, " .
					"sv_quicksearches, " .
					"sv_global_search, " .
					"sv_pubfilters" .
				") VALUES (" .
					"\"".formulize_db_escape($savename)					."\", ".
					"\"".formulize_db_escape($savegroups)				."\", ".
					"\"".formulize_db_escape($owneruid)					."\", ".
					"\"".formulize_db_escape($moduid)					."\", ".
					"\"".formulize_db_escape($saveformframe)			."\", ".
					"\"".formulize_db_escape($savemainform)				."\", ".
					"\"".formulize_db_escape($_POST['savelock'])		."\", ".
					intval($_POST['hlist'])                             .", ".
					intval($_POST['hcalc'])			                    .", ".
					"\"".formulize_db_escape($savesearches)				."\", ".
					"\"".formulize_db_escape($_POST['sort'])			."\", ".
					"\"".formulize_db_escape($_POST['order'])			."\", ".
					"\"".formulize_db_escape($_POST['oldcols'])			."\", ".
					"\"".formulize_db_escape($_POST['savescope'])		."\", ".
					"\"".formulize_db_escape($_POST['calc_cols'])		."\", ".
					"\"".formulize_db_escape($_POST['calc_calcs'])		."\", ".
					"\"".formulize_db_escape($_POST['calc_blanks'])		."\", ".
					"\"".formulize_db_escape($_POST['calc_grouping'])	."\", ".
					"\"".formulize_db_escape($qsearches)				."\", ".
					"\"".formulize_db_escape($_POST['global_search'])	."\", ".
					"\"".formulize_db_escape($_POST['pubfilters'])      ."\"  ".
				")";
		} else {
			// print "UPDATE " . $xoopsDB->prefix("formulize_saved_views") . " SET sv_pubgroups=\"$savegroups\", sv_mod_uid=\"$uid\", sv_lockcontrols=\"{$_POST['savelock']}\", sv_hidelist=\"{$_POST['hlist']}\", sv_hidecalc=\"{$_POST['hcalc']}\", sv_asearch=\"$savesearches\", sv_sort=\"{$_POST['sort']}\", sv_order=\"{$_POST['order']}\", sv_oldcols=\"{$_POST['oldcols']}\", sv_currentview=\"{$_POST['savescope']}\", sv_calc_cols=\"{$_POST['calc_cols']}\", sv_calc_calcs=\"{$_POST['calc_calcs']}\", sv_calc_blanks=\"{$_POST['calc_blanks']}\", sv_calc_grouping=\"{$_POST['calc_grouping']}\", sv_quicksearches=\"$qsearches\" WHERE sv_id = \"" . substr($saveid_formulize, 1) . "\"";
			$savesql =
				"UPDATE " . $xoopsDB->prefix("formulize_saved_views") .
				" SET " .
					"sv_name 			= \"".formulize_db_escape($savename) 				."\", ".
					"sv_pubgroups 		= \"".formulize_db_escape($savegroups) 				."\", ".
					"sv_mod_uid 		= \"".formulize_db_escape($uid) 					."\", ".
					"sv_lockcontrols 	= \"".formulize_db_escape($_POST['savelock'])		."\", ".
					"sv_hidelist 		= ".intval($_POST['hlist'])                         .", ".
					"sv_hidecalc 		= ".intval($_POST['hcalc']) 			            .", ".
					"sv_asearch 		= \"".formulize_db_escape($savesearches) 			."\", ".
					"sv_sort 			= \"".formulize_db_escape($_POST['sort']) 			."\", ".
					"sv_order 			= \"".formulize_db_escape($_POST['order']) 			."\", ".
					"sv_oldcols 		= \"".formulize_db_escape($_POST['oldcols']) 		."\", ".
					"sv_currentview 	= \"".formulize_db_escape($_POST['savescope']) 		."\", ".
					"sv_calc_cols 		= \"".formulize_db_escape($_POST['calc_cols']) 		."\", ".
					"sv_calc_calcs 		= \"".formulize_db_escape($_POST['calc_calcs']) 	."\", ".
					"sv_calc_blanks 	= \"".formulize_db_escape($_POST['calc_blanks']) 	."\", ".
					"sv_calc_grouping 	= \"".formulize_db_escape($_POST['calc_grouping']) 	."\", ".
					"sv_quicksearches 	= \"".formulize_db_escape($qsearches) 				."\", ".
					"sv_global_search   = \"".formulize_db_escape($_POST['global_search'])	."\", ".
					"sv_pubfilters      = \"".formulize_db_escape($_POST['pubfilters'])	    ."\" ".
				" WHERE " .
					"sv_id = \"" . substr($saveid_formulize, 1) . "\"";
		}

		// save the report
		if(!$result = $xoopsDB->query($savesql)) {
			exit("Error:  unable to save the current view settings.  SQL dump: $savesql");
		}
		if($saveid_formulize == "new" OR strstr($saveid_formulize, "old_")) {
			if($owneruid == $uid) {
				$loadedView = "s" . $xoopsDB->getInsertId();
			} else {
				$loadedView = "p" . $xoopsDB->getInsertId();
			}
		}
		$settings['loadedview'] = $loadedView;

		// delete legacy report if necessary
		if(strstr($saveid_formulize, "old_")) {
			$dellegacysql = "DELETE FROM " . $xoopsDB->prefix("formulize_reports") . " WHERE report_id=\"" . substr($saveid_formulize, 5) . "\"";
			if(!$result = $xoopsDB->query($dellegacysql)) {
				exit("Error:  unable to delete legacy report: " . substr($saveid_formulize, 5));
			}
		}
	}

	$forceLoadView = false;
	if($screen) {
		$loadview = is_numeric($loadview) ? $loadview : $screen->getVar('defaultview'); // flag the screen default for loading if no specific view has been requested
		if (is_array($loadview)) {
			$loadview = getDefaultViewForActiveUser($screen->getVar('defaultview'), $groups);
		}	
		if($loadview == "mine" OR $loadview == "group" OR $loadview == "all" OR ($loadview == "blank" AND (!isset($_POST['hlist']) AND !isset($_POST['hcalc'])))) { // only pay attention to the "blank" default list if we are on an initial page load, ie: no hcalc or hlist is set yet, and one of those is set on each page load hereafter
			$currentView = $loadview; // if the default is a standard view, then use that instead and don't load anything
			unset($loadview);
		} 
	}

	// set currentView to group if they have groupscope permission (overridden below by value sent from form)
	// override with loadview if that is specified

	if($loadview AND ((!$_POST['currentview'] AND $_POST['advscope'] == "") OR $_POST['userClickedReset'])) {
		if(is_numeric($loadview)) { // new view id
			$loadview = "p" . $loadview;
		} else { // new view name -- loading view by name -- note if two reports have the same name, then the first one created will be returned
			$viewnameq = q("SELECT sv_id FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_name='$loadview' ORDER BY sv_id");
			$loadview = "p" . $viewnameq[0]['sv_id'];
		}
		$_POST['currentview'] = $loadview;
		$_POST['loadreport'] = 1;
	} elseif($view_globalscope AND !$currentView) {
		$currentView = "all";
	} elseif($view_groupscope AND !$currentView) {
		$currentView = "group";
	} elseif(!$currentView) {
		$currentView = "mine";
	}

	// no report/saved view to be loaded, and we're not on a subsequent page load that is sending back a declared currentview, or the user clicked the reset button
	// therefore, an advanceview if any could be loaded after all the other setup has been done
	if(!$_POST['loadreport'] AND (!$_POST['currentview'] OR $_POST['userClickedReset'])) {
		$couldLoadAdvanceView = true;
	} else {
		$couldLoadAdvanceView = false;
	}
	
	// debug block to show key settings being passed back to the page
	
	/*if($uid == 19511) {
	print "delview: " . $_POST['delview'] . "<br>";
	print "advscope: " . $_POST['advscope'] . "<br>";
	print "asearch: " . $_POST['asearch'] . "<br>";
	print "Hidelist: " . $_POST['hlist'] . "<br>";
	print "Hidecalc: " . $_POST['hcalc'] . "<br>";
	print "Lock Controls: " . $_POST['lockcontrols'] . "<br>";
	print "Sort: " . $_POST['sort'] . "<br>";
	print "Order: " . $_POST['order'] . "<br>";
	print	"Cols: " . $_POST['oldcols'] . "<br>";
	print "Curview: " . $_POST['currentview'] . "<br>";
	print "Calculation columns: " . $_POST['calc_cols'] . "<br>";
	print "Calculation calcs: " . $_POST['calc_calcs'] . "<br>";
	print "Calculation blanks: " . $_POST['calc_blanks'] . "<br>";
	print "Calculation grouping: " . $_POST['calc_grouping'] . "<br>";
	foreach($_POST as $k=>$v) {
		if(substr($k, 0, 7) == "search_" AND $v != "") {
			print "$k: $v<br>";
		}
	}
	}*/


	// set flag to indicate whether we let the user's scope setting expand beyond their normal permission level (happens when unlocked published views are in effect)
	$currentViewCanExpand = false;

	// handling change in view, and loading reports/saved views if necessary
	if($_POST['loadreport']) {
		
		if(is_numeric(substr($_POST['currentview'], 1))) { // saved or published view
			$loadedView = $_POST['currentview'];
			$settings['loadedview'] = $loadedView;
			// kill the quicksearches, unless we've found a special flag that will cause them to be preserved
			if(!isset($_POST['formulize_preserveQuickSearches']) AND !isset($_GET['formulize_preserveQuickSearches'])) {
				foreach($_POST as $k=>$v) {
					if(substr($k, 0, 7) == "search_" AND $v != "") {
						unset($_POST[$k]);
					}
				}
			}
			list(
				$_POST['currentview'],
				$_POST['oldcols'],
				$_POST['asearch'],
				$_POST['calc_cols'],
				$_POST['calc_calcs'],
				$_POST['calc_blanks'],
				$_POST['calc_grouping'],
				$_POST['sort'],
				$_POST['order'],
				$savedViewHList,
				$savedViewHCalc,
				$_POST['lockcontrols'],
				$quicksearches,
				$_POST['global_search'], $_POST['pubfilters']) = loadReport(substr($_POST['currentview'], 1), $fid, $frid);
			if(!isset($_POST['formulize_preserveListCalcPage']) AND !isset($_GET['formulize_preserveListCalcPage'])) {
				$_POST['hlist'] = $savedViewHList;
				$_POST['hcalc'] = $savedViewHCalc;
			}
			// explode quicksearches into the search_ values
			$allqsearches = explode("&*=%4#", $quicksearches);
			$colsforsearches = explode(",", $_POST['oldcols']);
			for($i=0;$i<count((array) $allqsearches);$i++) {
				if($allqsearches[$i] != "") {
					$_POST["search_" . str_replace("hiddencolumn_", "", $colsforsearches[$i])] = $allqsearches[$i]; // need to remove the hiddencolumn indicator if it is present
					if(strstr($colsforsearches[$i], "hiddencolumn_")) {
						unset($colsforsearches[$i]); // remove columns that were added to the column list just so we would know the name of the hidden searches
					}
				}
			}
			$_POST['oldcols'] = implode(",",$colsforsearches); // need to reconstruct this in case any columns were removed because of persistent searches on a hidden column
			
			// BIG HACK FOR DARA THAT NEEDS TO BE BASED ON NEW UI IN CHANGE COLUMNS
			if(strstr(getCurrentURL(),'dara.daniels') AND isset($_GET['sid']) AND $_GET['sid']==66) {
				$_POST['oldcols'] = "ro_module_ug_grad_shortform,ro_module_semester,ro_module_program,ro_module_full_course_title,sections_section_number,ro_module_lecture_studio,ro_module_course_weight_ui,instr_assignments_instructor,instr_assignments_split_weight_override,course_components_teaching_weighting_display,course_components_teaching_weighting_ove,ro_module_course_coordinator,ro_module_coordinatorship_weighting_display,ro_module_coord_weighting_override";
			}
		}
		
		$currentView = $_POST['currentview'];

		// need to check that the user is allowed to have this scope, unless the view is unlocked
		// only works for the default levels of views, not specific group selections that a view might have...that would be more complicated and could be built in later
		if($_POST['lockcontrols']) {
			if($currentView == "all" AND !$view_globalscope) {
				$currentView = "group";
			}
			if($currentView == "group" AND !$view_groupscope AND !$view_globalscope) {
				$currentView = "mine";
			}
		}
		// must check for this and set it here, inside this section, where we know for sure that $_POST['lockcontrols'] has been set based on the database value for the saved view, and not anything else sent from the user!!!  Otherwise the user might be injecting a greater scope for themselves than they should have!
		$currentViewCanExpand = $_POST['lockcontrols'] ? false : true; // if the controls are not locked, then we can expand the view for the user so they can see things they wouldn't normally see

		// if there is a screen with a top template in effect, then do not lock the controls even if the saved view says we should.  Assume that the screen author has compensated for any permission issues.
		// we need to do this after rachetting down the visibility controls.  Fact is, controlling UI for users is one thing that we can trust the screen author to do, so we don't need to indicate that the controls are locked.  But we don't want the visibility to override what people can normally see, so we rachet that down above.
		if($screen AND $_POST['lockcontrols'] AND $screen->getTemplate('toptemplate') != "") {
			$_POST['lockcontrols'] = 0;
		}

	} elseif($_POST['advscope'] AND strstr($_POST['advscope'], ",")) { // looking for comma sort of means that we're checking that a valid advanced scope is being sent
		$currentView = $_POST['advscope'];
	} elseif($_POST['currentview']) { // could have been unset by deletion of a view or something else, so we must check to make sure it exists before we override the default that was determined above
		if(is_numeric(substr($_POST['currentview'], 1))) {
			// a saved view was requested as the current view, but we don't want to load the entire thing....this means that we just want to use the view to generate the scope, we don't want to load all settings.  So we have to load the view, but discard everything but the view's currentview value
			// if we were supposed to load the whole thing, loadreport would have been set in post and the above code would have kicked in
			$loadedViewSettings = loadReport(substr($_POST['currentview'], 1), $fid, $frid);
			$currentview = $loadedViewSettings[0];
		} else {
			$currentView = $_POST['currentview'];
		}
	} elseif($loadview) {
		$currentView = $loadview;
	}

	$pubfilters = strlen($_POST['pubfilters']) > 0 ? explode(",", $_POST['pubfilters']) : array();

	// if we did not load a full report/saved view, then load an advanceview if any is specified and the current page load is appropriate for it (see above for couldLoadAdvanceView))
	if($screen AND count((array) $screen->getVar('advanceview')) > 0 AND $couldLoadAdvanceView) {
		// kill the quicksearches, unless we've found a special flag that will cause them to be preserved
		if(!isset($_POST['formulize_preserveQuickSearches']) AND !isset($_GET['formulize_preserveQuickSearches'])) {
			foreach($_POST as $k=>$v) {
				if(substr($k, 0, 7) == "search_" AND $v != "") {
					unset($_POST[$k]);
				}
			}
		}
		list($_POST['oldcols'],
			 $_POST['sort'],
			 $_POST['order'],
			 $quicksearches) = loadAdvanceView($fid, $screen->getVar('advanceview'));
			
		// explode quicksearches into the search_ values
		$allqsearches = explode(",", $quicksearches);
		$colsforsearches = explode(",", $_POST['oldcols']);
		for($i=0;$i<count((array) $allqsearches);$i++) {
			if($allqsearches[$i] != "") {
				$_POST["search_" . $colsforsearches[$i]] = $allqsearches[$i]; 
			}
		}
		$_POST['oldcols'] = implode(",",$colsforsearches);
	}    
	
    // get columns for this form/framework or use columns sent from interface
	// ele_handles for a form, handles for a framework, includes handles of all unified display forms
	if($_POST['oldcols']) {
		$showcols = explode(",", $_POST['oldcols']);
	} else { // or use the defaults
		$showcols = getDefaultCols($fid, $frid);
	}

	if($_POST['newcols']) {
		$temp_showcols = $_POST['newcols'];
		$showcols = explode(",", $temp_showcols);
	}

	$showcols = removeNotAllowedCols($fid, $frid, $showcols, $groups); // converts old format metadata fields to new ones too if necessary

	// Create settings array to pass to form page or to other functions

	$settings['title'] = $displaytitle;

	// get export options
	if($_POST['xport']) {
		$settings['xport'] = $_POST['xport'];
		if($_POST['xport'] == "custom") {
			$settings['xport_cust'] = $_POST['xport_cust'];
		}
	}

	list($scope, $currentView) = buildScope($currentView, $uid, $fid, $currentViewCanExpand);
	// generate the available views

	// pubstart used to indicate to the delete button where the list of published views begins in the current view drop down (since you cannot delete published views)
	list($settings['viewoptions'], $settings['pubstart'], $settings['endstandard'], $settings['pickgroups'], $settings['loadviewname'], $settings['curviewid'], $settings['publishedviewnames']) = generateViews($fid, $uid, $groups, $frid, $currentView, $loadedView, $view_groupscope, $view_globalscope, $_POST['curviewid'], $loadOnlyView, $screen, $_POST['lastloaded']);

	// this param only used in case of loading of reports via passing in the report id or name through $loadview
	if($_POST['loadviewname']) { $settings['loadviewname'] = $_POST['loadviewname']; }

	// if a view was loaded, then update the lastloaded value, otherwise preserve the previous value
	if($settings['curviewid']) {
		$settings['lastloaded'] = $settings['curviewid'];
	} else {
		$settings['lastloaded'] = $_POST['lastloaded'];
	}

	// clear quick searches for any columns not included now
	// also, convert any { } terms to literal values for users who can't update other reports, if the last loaded report doesn't belong to them (they're presumably just report consumers, so they don't need to preserve the abstract terms)
	$hiddenQuickSearches = array(); // array used to indicate quick searches that should be present even if the column is not displayed to the user
    $activeViewId = substr($settings['lastloaded'], 1); // will have a p in front of the number, to show it's a published view (or an s, but that's unlikely to ever happen in this case)
	$ownerOfLastLoadedViewData = q("SELECT sv_owner_uid FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_id=".intval($activeViewId));
	$ownerOfLastLoadedView = $ownerOfLastLoadedViewData[0]['sv_owner_uid'];
	foreach($_POST as $k=>$v) {
		
		if(substr($k, 0, 7) == "search_" AND !in_array(substr($k, 7), $showcols) AND !in_array(substr($k, 7), $pubfilters)) {
			if(is_string($v) AND substr($v, 0, 1) == "!" AND substr($v, -1) == "!") {// don't strip searches that have ! at front and back
				$hiddenQuickSearches[] = substr($k, 7);
				continue; // since the { } replacement is meant for the ease of use of non-admin users, and hiddenQuickSearches never show up to users on screen, we can skip the potentially expensive operations below in this loop
			} else {
				unset($_POST[$k]);
			}
		}
		
		// check for starting and ending ! ! and put them back at the end if necessary
		$needPreserveHiddenMarkers = false;
		if(is_string($v) AND substr($v, 0, 1) == "!" AND substr($v, -1) == "!") {
			$needPreserveHiddenMarkers = true;
			$v = substr($v, 1, -1);
		}
		
		$operatorToPutBack = "";
		if(is_string($v) AND substr($v, 0, 1) == '=') {
			$operatorToPutBack = '=';
		}
		if(is_string($v) AND substr($v, 0, 1) == '>') {
			$operatorToPutBack = '>';
		}
		if(is_string($v) AND substr($v, 0, 1) == '<') {
			$operatorToPutBack = '<';
		}
		if(is_string($v) AND substr($v, 0, 1) == '!') {
			$operatorToPutBack = '!';
		}
		if(is_string($v) AND substr($v, 0, 2) == '!=') {
			$operatorToPutBack = '!=';
		}
		if(is_string($v) AND substr($v, 0, 2) == '<=') {
			$operatorToPutBack = '<=';
		}
		if(is_string($v) AND substr($v, 0, 2) == '>=') {
			$operatorToPutBack = '>=';
		}
		
		// if this is not a report/view that was created by the user, and they don't have update permission, then convert any { } terms to literals
		// remove any { } terms that don't have a passed in value (so they appear as "" to users)
		// only deal with terms that start and end with { } and not ones where the { } terms is not the entire term
		
		$valueToCheck = str_replace($operatorToPutBack, '', $v);
		
		if(is_string($v) AND substr($valueToCheck, 0, 1) == "{" AND substr($valueToCheck, -1) == "}"
			AND substr($k, 0, 7) == "search_" AND (in_array(substr($k, 7), $showcols) OR in_array(substr($k, 7), $pubfilters)))
		{
			$requestKeyToUse = substr($valueToCheck,1,-1);
			if(!strstr($requestKeyToUse,"}") AND !strstr($requestKeyToUse, "{")) { // double check that there's no other { } in the term!
				if(!$update_other_reports AND $uid != $ownerOfLastLoadedView) {
					$filterValue = convertVariableSearchToLiteral($v, $requestKeyToUse); // returns updated value, or false to kill value, or true to do nothing
					if(!is_bool($filterValue)) {
						$_POST[$k] = $operatorToPutBack.$filterValue;
						if($needPreserveHiddenMarkers) {
						   $_POST[$k] = '!'.$_POST[$k].'!';
						}
					} elseif($filterValue === false) {
						unset($_POST[$k]); // clear terms where no match was found, because this term is not active on the current page, so don't confuse users by showing it
					}
				}
			}
		}
	}

	$settings['pubfilters'] = $pubfilters;
	$settings['currentview'] = $currentView;

	$settings['currentURL'] = $currentURL;

	// no need for both these values now, since framework handles are deprecated
	$settings['columns'] = $showcols;
	$settings['columnhandles'] = $showcols;

	$settings['hlist'] = isset($_POST['hlist']) ? $_POST['hlist'] : 0;
	$settings['hcalc'] = isset($_POST['hcalc']) ? $_POST['hcalc'] : 1;

	// determine if the controls should really be locked...
	if($_POST['lockcontrols']) { // if a view locks the controls
		// only lock the controls when the user is not a member of the currentview groups AND has no globalscope
		// OR if they are a member of the currentview groups AND has no groupscope or no globalscope
		switch($currentView) {
			case "mine":
				$settings['lockcontrols'] = "";
				break;
			case "all":
				if($view_globalscope) {
					$settings['lockcontrols'] = "";
				} else {
					$settings['lockcontrols'] = "1";
				}
				break;
			case "group":
				if($view_groupscope OR $view_globalscope) {
					$settings['lockcontrols'] = "";
				} else {
					$settings['lockcontrols'] = "1";
				}
				break;
			default:
				$viewgroups = explode(",", trim($currentView, ","));
				// get the groups that the current user has specified scope for, and if none, then look at view form
				$formulize_permHandler = new formulizePermHandler($fid);
				$groupsWithAccess = $formulize_permHandler->getGroupScopeGroupIds($groups);
				if($groupsWithAccess === false) {
					$groupsWithAccess = $gperm_handler->getGroupIds("view_form", $fid, $mid);
					$groupsWithAccess = array_intersect($groups, $groupsWithAccess); // limit to just the user's own groups that have this permission, since what we're checking of below is whether the user's groups with view form meet the condition or not
				}
				$diff = array_diff($viewgroups, $groupsWithAccess);
				if(!isset($diff[0]) AND $view_groupscope) { // if the scopegroups are completely included in the user's groups that have access to the form, and they have groupscope (ie: they would be allowed to see all these entries anyway)
					$settings['lockcontrols'] = "";
				} elseif($view_globalscope) { // if they have global scope
					$settings['lockcontrols'] = "";
				} else { // no globalscope and even if they're a member of the scope for this view, they don't have groupscope
					$settings['lockcontrols'] = "1";
				}
		}
	} else {
		$settings['lockcontrols'] = "";
	}

	$settings['asearch'] = $_POST['asearch'];
	if($_POST['asearch']) {
		$as_array = explode("/,%^&2", $_POST['asearch']);
		foreach($as_array as $k=>$one_as) {
			$settings['as_' . $k] = $one_as;
		}
	}

	$settings['oldcols'] = implode(",", $showcols);

	// if there's a bunch of go_back info, and no entry, then we should not show list, we need to display something else entirely
	if(isset($_POST['go_back_form']) AND $_POST['go_back_form'] AND isset($_POST['go_back_entry']) AND $_POST['go_back_entry'] AND (!isset($_POST['ventry']) OR !$_POST['ventry'])) {
        $_POST['ventry'] = setupParentFormValuesInPostAndReturnEntryId();
        $settings['ventry'] = $_POST['ventry'];
	} elseif(isset($_POST['formulize_originalVentry']) AND is_numeric($_POST['formulize_originalVentry'])) {
		$settings['ventry'] = $_POST['formulize_originalVentry'];
	} else {
        // if the user has requested a ve in the URL, set it now as if they clicked on a link to go into an entry
        if((!isset($_POST['ventry']) OR !$_POST['ventry']) AND
            isset($_GET['ve']) AND is_numeric($_GET['ve']) AND $_GET['ve'] > 0) {
            $_POST['ventry'] = $_GET['ve'];
        }
		$settings['ventry'] = $_POST['ventry'];
	}

	// get sort and order options
	$settings['sort'] = $_POST['sort'];
	$settings['order'] = $_POST['order'];

	//get all submitted search text
	foreach($_POST as $k=>$v) {
		if(substr($k, 0, 7) == "search_" AND $v != "") {
			$thiscol = substr($k, 7);
			$searches[$thiscol] = $v;
			$temp_key = "search_" . $thiscol;
			$settings[$temp_key] = $v;
		}
	}

	// get the submitted global search text
	$settings['global_search'] = $_POST['global_search'];

	// get all requested calculations...assign to settings array.
	$settings['calc_cols'] = $_POST['calc_cols'];
	$settings['calc_calcs'] = $_POST['calc_calcs'];
	$settings['calc_blanks'] = $_POST['calc_blanks'];
	$settings['calc_grouping'] = $_POST['calc_grouping'];

	// grab all the locked columns so we can persist them
	if(strstr($_POST['formulize_lockedColumns'], ",")) {
		$settings['lockedColumns'] = array_unique(explode(",",trim($_POST['formulize_lockedColumns'],",")));
	} elseif(strlen($_POST['formulize_lockedColumns'])>0) {
		$settings['lockedColumns'] = array(intval($_POST['formulize_lockedColumns']));
	} else {
		$settings['lockedColumns'] = array();
	}

	// set the requested procedure, if any
	$settings['advcalc_acid'] = strip_tags(htmlspecialchars($_POST['advcalc_acid']));
	formulize_addProcedureChoicesToPost($settings['advcalc_acid']);

	// gather id of the cached data, if any. Used by the procedure system (advanced calculations).
	$settings['formulize_cacheddata'] = strip_tags($_POST['formulize_cacheddata']);

	// process a clicked custom button
	// must do this before gathering the data, because it might alter the data!
	$messageText = "";
	if(isset($_POST['caid']) AND $screen AND $formulize_LOESecurityPassed) {
		$customButtonDetails = $screen->getVar('customactions');
		if(is_numeric($_POST['caid']) AND isset($customButtonDetails[$_POST['caid']])) {
			list($caCode, $caElements, $caActions, $caValues, $caMessageText, $caApplyTo, $caPHP, $caInline) = processCustomButton($_POST['caid'], $customButtonDetails[$_POST['caid']]); // just processing to get the info so we can process the click.  Actual output of this button happens lower down
			$messageText = processClickedCustomButton($caElements, $caValues, $caActions, $caMessageText, $caApplyTo, $caPHP, $caInline, $screen);
		}
	}
	
	// user clicked on a view this entry link
	// figure out which screen to show, and abort drawing a list of entries
	if($_POST['ventry']) { 
		
		include_once XOOPS_ROOT_PATH . '/modules/formulize/include/formdisplay.php';

		if($_POST['ventry'] == "addnew" OR $_POST['ventry'] == "single") {
			$this_ent = "";
		} elseif($_POST['ventry'] == "proxy") {
			$this_ent = "proxy";
		} else {
			$this_ent = $settings['ventry'];
		}

		if($screen OR $_POST['overridescreen']) {
			if($screen AND is_string($screen->getVar("viewentryscreen")) AND strstr($screen->getVar("viewentryscreen"), "p")) { // if there's a p in the specified viewentryscreen, then it's a pageworks page -- added April 16 2009 by jwe
				$page = intval(substr($screen->getVar("viewentryscreen"), 1));
				include XOOPS_ROOT_PATH . "/modules/pageworks/index.php";
				return;
			} elseif($screenToLoad = determineViewEntryScreen($screen, $fid)) {
				$viewEntryScreenObject = $screen_handler->get($screenToLoad);
				if($viewEntryScreenObject->getVar('type')=="listOfEntries") {
					exit("You're sending the user to a list of entries screen instead of some kind of form screen, when they're editing an entry.  Check what screen is defined as the screen to use for editing an entry, or what screen id you're using in the viewEntryLink or viewEntryButton functions in the template.");
				}
				$viewEntryScreen_handler = xoops_getmodulehandler($viewEntryScreenObject->getVar('type').'Screen', 'formulize');
				$displayScreen = $viewEntryScreen_handler->get($viewEntryScreenObject->getVar('sid'));
                // RELOAD BLANK DOESN'T MEAN ANYTHING YET FOR MULTIPAGE SCREENS?
                if($_POST['ventry'] != "single") {
                    $displayScreen->setVar('reloadblank', 1); // if the user clicked the add multiple button, then specifically override that screen setting so they can make multiple entries
                } else {
                    $displayScreen->setVar('reloadblank', 0); // otherwise, if they did click the single button, make sure the form reloads with their entry
                }
				// NEED TO PROPAGATE ANY ANON PASSCODE FOR THE CURRENT SCREEN, INTO THE SESSION BUT ASSIGNED TO THE NEW SCREEN, SO IT WILL BE SAVED PROPERLY WHEN THE FORM IS SUBMITTED THROUGH THAT SCREEN, IF APPLICABLE
				// THIS IS SAFE TO DO, BECAUSE IF THE PASSCODE IS NOT VALID FOR THE OTHER SCREEN, IT WILL FAIL VALIDATION WHEN THE USER GOES TO THAT SCREEN DIRECTLY. WE'RE NOT GIVING THEM A FREE PASS TO SOMETHING THEY SHOULDN'T OTHERWISE SEE.
				if(isset($_SESSION['formulize_passCode_'.$screen->getVar('sid')])) {
					$_SESSION['formulize_passCode_'.$displayScreen->getVar('sid')] = $_SESSION['formulize_passCode_'.$screen->getVar('sid')];
				}
				if(!$_POST['overridescreen'] AND $displayScreen->getVar('fid') != $fid) {
					// display screen is for another form in the active relationship, so figure out what all the entries are, and display the first entry in the set that's for the form this screen is based on
					$dataSetEntries = checkForLinks($frid, array($fid), $fid, array($fid=>array($this_ent))); // returns array of the forms and entries in the dataset
					$this_ent = $dataSetEntries['entries'][$displayScreen->getVar('fid')][0]; // first entry for the screen's form, in this dataset - see formdisplay.php for more detailed example of usage of checkforlinks
				}
				$viewEntryScreen_handler->render($displayScreen, $this_ent, $settings);
				global $renderedFormulizeScreen; // picked up at the end of initialize.php so we set the right info in the template when the whole page is rendered
				$renderedFormulizeScreen = $displayScreen;
				return;
			}
        }
        // if we're still here, then load up a plain non-screen version of the form
        if($_POST['ventry'] != "single") {
            if($frid) {
                displayForm($frid, $this_ent, $fid, $currentURL, "", $settings, "", "", "", "", $viewallforms); // "" is the done text
                return;
            } else {
                displayForm($fid, $this_ent, "", $currentURL, "", $settings, "", "", "", "", $viewallforms); // "" is the done text
                return;
            }
        } else { // if a single entry was requested for a form that can have multiple entries, then specifically override the multiple entry UI (which causes a blank form to appear on save)
            if($frid) {
                displayForm($frid, $this_ent, $fid, $currentURL, "", $settings, "", "", "1", "", $viewallforms); // "" is the done text
                return;
            } else {
                displayForm($fid, $this_ent, "", $currentURL, "", $settings, "", "", "1", "", $viewallforms); // "" is the done text
                return;
            }
        }
		
	}

	// user is still here, so go get the data and start building the page...        
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
	//formulize_benchmark("before gathering dataset");
	list($data, $regeneratePageNumbers) = formulize_gatherDataSet($settings, $searches, strip_tags($_POST['sort']), strip_tags($_POST['order']), $frid, $fid, $scope, $screen, $currentURL, intval($_POST['forcequery']));
	//formulize_benchmark("after gathering dataset/before generating calcs");
	
	// perform calculations on the data if any requested...
	if($settings['calc_cols'] AND !$settings['hcalc']) {
		//formulize_benchmark("before performing calcs");
		$ccols = explode("/", $settings['calc_cols']);
		$ccalcs = explode("/", $settings['calc_calcs']);
		$cblanks = explode("/", $settings['calc_blanks']);
		$cgrouping = explode("/", $settings['calc_grouping']);
		$cResults = performCalcs($ccols, $ccalcs, $cblanks, $cgrouping, $frid, $fid);
	}
	//formulize_benchmark("after performing calcs");
	
	//formulize_benchmark("after generating calcs/before creating pagenav");
	list($formulize_LOEPageNav, $formulize_LOEEntryCount) = formulize_LOEbuildPageNav($data, $screen, $regeneratePageNumbers);
	//formulize_benchmark("after nav/before interface");
	
	ob_start();
	// drawInterface... renders the top template, sets up searches, many template variables including all the action buttons...
	$formulize_buttonCodeArray = drawInterface($settings, $fid, $frid, $groups, $mid, $gperm_handler, $loadview, $loadOnlyView, $screen, $searches, $formulize_LOEPageNav, $formulize_LOEEntryCount, $messageText, $hiddenQuickSearches);

	// drawEntries ... renders the openlist, list and closelist templates
	formulize_benchmark("before entries");
	drawEntries($fid, $showcols, $searches, $frid, $scope, "", $currentURL, $gperm_handler, $uid, $mid, $groups, $settings, $member_handler, $screen, $data, $regeneratePageNumbers, $hiddenQuickSearches, $cResults, $formulize_buttonCodeArray); // , $loadview); // -- loadview not passed any longer since the lockcontrols indicator is used to handle whether things should appear or not.
	formulize_benchmark("after entries");

	// render the bottomtemplate
    $visibleSearches = 0;
    foreach($searches as $thisSearch) {
        if(substr($thisSearch,0,1) != "!" OR substr($thisSearch, -1) != "!") {
            $visibleSearches = 1;
            break;
        }
    }
    $formulize_buttonCodeArray['toggleSearchesOnFirst'] = $visibleSearches;
	formulize_screenLOETemplate($screen, "bottom", $formulize_buttonCodeArray, $settings);
	
	$listOfEntriesBufferContents = ob_get_clean();
	
	print $listOfEntriesBufferContents;
	
	if(isset($formulize_buttonCodeArray['submitButton'])) { // if a custom top template was in effect, this will have been sent back, so now we display it at the very bottom of the form so it doesn't take up a visible amount of space above (the submitButton is invisible, but does take up space)
		print "<p class=\"formulize_customTemplateSubmitButton\">" . $formulize_buttonCodeArray['submitButton'] . "</p>";
	}
	
	// check for any searches that were not used, and output them in a hidden div, so their values are not lost
	// this was an old way of enforcing fundamental filters which are better done on the admin side as part of screen settings
	// or done as part of scope permissions for the user's group(s).
    // Have to do this by text analysis and not looking for variable name, because variable names might not be present if dynamically generated
    // NOTE: if one is playing games in the template and doing string manipulation of the quick searches, then they will be broken because this code will not pick up their presence
    // RECOMMENDATION: users should put original search code into an html comment
    // we could extract the name attribute and do a regular expression match with the opening input tag and the name, or something like that (or select tag, etc) but way way more complex

	print "<div id='hidden_quick_searches' style='display: none;'>\n";

	foreach($formulize_buttonCodeArray['quickSearches'] as $handle=>$qsCode) {
		if( (($searches[$handle] OR is_numeric($searches[$handle])) AND !strstr($listOfEntriesBufferContents, $qsCode['search']))
            AND (!isset($qsCode['filter']) OR !strstr($listOfEntriesBufferContents, $qsCode['filter']))
            AND (!isset($qsCode['multiFilter']) OR !strstr($listOfEntriesBufferContents, $qsCode['multiFilter']))
            AND (!isset($qsCode['dateRange']) OR !strstr($listOfEntriesBufferContents, $qsCode['dateRange'])) ) {
            foreach(array('search', 'filter', 'multiFilter', 'dateRange') as $searchType) {
                if(isset($qsCode[$searchType])) {
                    print $qsCode[$searchType]."\n";
                    break;
                }
            }
		}
	}
	print "</div>\n";
	
	print "</form>\n"; // end of the form started in gatherdataset

	print "</div>\n"; // end of the listofentries div, started in gatherdataset
	
}

// return the available current view settings based on the user's permissions
function generateViews($fid, $uid, $groups, $frid, $currentView, $loadedView, $view_groupscope, $view_globalscope, $prevview, $loadOnlyView, $screen, $lastLoaded) {
	global $xoopsDB;

	$limitViews = false;
	$screenLimitViews = array();
	$forceLastLoaded = false;
	if($screen) {
		$screenLimitViews = $screen->getVar('limitviews');
		if(!in_array("allviews", $screenLimitViews)) {
			$limitViews = true;

			// IF LIMIT VIEWS IS IN EFFECT, THEN CHECK FOR BASIC VIEWS BEING ENABLED, AND IF THEY ARE NOT, THEN WE NEED TO SET THE CURRENT VIEW LIST TO THE LASTLOADED
			// Excuses....This is a future todo item.  Very complex UI issues, in that user could change options, then switch to other view, then switch back and their options are missing now
			// Right now, without basic views enabled, the first view in the list comes up if an option is changed (since the basic scope cannot be reflected in the available views), so that's just confusing
			// Could have 'custom' option show up at top of list instead, just to indicate to the user that things are not the options originally loaded from that view

			if((!in_array("mine", $screenLimitViews) AND !in_array("group", $screenLimitViews) AND !in_array("all", $screenLimitViews)) AND !$_POST['loadreport'] ) { // if the basic views are not present, and the user hasn't specifically changed the current view list
				$forceLastLoaded = true;
			} else {
				$forceLastLoaded = false;
			}

		}
	}


	$options =  !$limitViews ? "<option value=\"\">" . _formulize_DE_STANDARD_VIEWS . "</option>\n" : "";
	$vcounter=0;

	if($loadOnlyView AND $loadedView AND !$limitViews) {
		$vcounter++;
		$options .= "<option value=\"\">&nbsp;&nbsp;" . _formulize_DE_NO_STANDARD_VIEWS . "</option>\n";
	}


	if($currentView == "mine" AND !$loadOnlyView AND (!$limitViews OR in_array("mine", $screenLimitViews))) {
		$options .= "<option value=mine selected>&nbsp;&nbsp;" . _formulize_DE_MINE . "</option>\n";
		$vcounter++;
	} elseif(!$loadOnlyView AND (!$limitViews OR in_array("mine", $screenLimitViews))) {
		$vcounter++;
		$options .= "<option value=mine>&nbsp;&nbsp;" . _formulize_DE_MINE . "</option>\n";
	}



	if($currentView == "group" AND $view_groupscope AND !$loadOnlyView AND (!$limitViews OR in_array("group", $screenLimitViews))) {
		$options .= "<option value=group selected>&nbsp;&nbsp;" . _formulize_DE_GROUP . "</option>\n";
		$vcounter++;
	} elseif($view_groupscope AND !$loadOnlyView AND (!$limitViews OR in_array("group", $screenLimitViews))) {
		$vcounter++;
		$options .= "<option value=group>&nbsp;&nbsp;" . _formulize_DE_GROUP . "</option>\n";
	}

	if($currentView == "all" AND $view_globalscope AND !$loadOnlyView AND (!$limitViews OR in_array("all", $screenLimitViews))) {
		$options .= "<option value=all selected>&nbsp;&nbsp;" . _formulize_DE_ALL . "</option>\n";
		$vcounter++;
	} elseif($view_globalscope AND !$loadOnlyView AND (!$limitViews OR in_array("all", $screenLimitViews))) {
		$vcounter++;
		$options .= "<option value=all>&nbsp;&nbsp;" . _formulize_DE_ALL . "</option>\n";
	}

	// check for pressence of advanced scope
	if(strstr($currentView, ",") AND !$loadedView AND !$limitViews) {
		$vcounter++;
		$groupNames = groupNameList(trim($currentView, ","));
		$options .= "<option value=$currentView selected>&nbsp;&nbsp;" . _formulize_DE_AS_ENTRIESBY . printSmart($groupNames) . "</option>\n";
	} elseif(($view_globalscope OR $view_groupscope) AND !$loadOnlyView AND !$limitViews) {
		$vcounter++;
		$pickgroups = $vcounter;
		$options .= "<option value=\"\">&nbsp;&nbsp;" . _formulize_DE_AS_PICKGROUPS . "</option>\n";
	}


	// check for available reports/views
	list($s_reports, $p_reports, $ns_reports, $np_reports) = availReports($uid, $groups, $fid, $frid);
	$lastStandardView = $vcounter;

	if(!$limitViews) { // cannot pick saved views in the screen UI so these will never be available if views are being limited
		if((count((array) $s_reports)>0 OR count((array) $ns_reports)>0) AND !$limitViews) { // we have saved reports...
			$options .= "<option value=\"\">" . _formulize_DE_SAVED_VIEWS . "</option>\n";
			$vcounter++;
		}
		for($i=0;$i<count((array) $s_reports);$i++) {
			if($loadedView == "sold_" . $s_reports[$i]['report_id'] OR $prevview == "sold_" . $s_reports[$i]['report_id']) {
				$vcounter++;
				$options .= "<option value=$currentView selected>&nbsp;&nbsp;" . stripslashes($s_reports[$i]['report_name']) . "</option>\n"; // " (id: " . $s_reports[$i]['report_id'] . ")</option>\n";
				$loadviewname = $s_reports[$i]['report_name'];
				$curviewid = "sold_" . $s_reports[$i]['report_id'];
			} else {
				$vcounter++;
				$options .= "<option value=sold_" . $s_reports[$i]['report_id'] . ">&nbsp;&nbsp;" . stripslashes($s_reports[$i]['report_name']) . "</option>\n"; // " (id: " . $s_reports[$i]['report_id'] . ")</option>\n";
			}
		}
		for($i=0;$i<count((array) $ns_reports);$i++) {
			if($loadedView == "s" . $ns_reports[$i]['sv_id'] OR $prevview == "s" . $ns_reports[$i]['sv_id']) {
				$vcounter++;
				$options .= "<option value=$currentView selected>&nbsp;&nbsp;" . stripslashes($ns_reports[$i]['sv_name']) . "</option>\n"; // " (id: " . $ns_reports[$i]['sv_id'] . ")</option>\n";
				$loadviewname = $ns_reports[$i]['sv_name'];
				$curviewid = "s" . $ns_reports[$i]['sv_id'];
			} else {
				$vcounter++;
				$options .= "<option value=s" . $ns_reports[$i]['sv_id'] . ">&nbsp;&nbsp;" . stripslashes($ns_reports[$i]['sv_name']) . "</option>\n"; // " (id: " . $ns_reports[$i]['sv_id'] . ")</option>\n";
			}
		}
	}


	if((count((array) $p_reports)>0 OR count((array) $np_reports)>0) AND !$limitViews) { // we have saved reports...
		$options .= "<option value=\"\">" . _formulize_DE_PUB_VIEWS . "</option>\n";
		$vcounter++;
	}
	$firstPublishedView = $vcounter + 1;
	if(!$limitViews) { // old reports are not selectable in the screen UI so will never be in the limit list
		for($i=0;$i<count((array) $p_reports);$i++) {
			if($loadedView == "pold_" . $p_reports[$i]['report_id'] OR $prevview == "pold_" . $p_reports[$i]['report_id']) {
				$vcounter++;
				$options .= "<option value=$currentView selected>&nbsp;&nbsp;" . stripslashes($p_reports[$i]['report_name']) . "</option>\n"; // " (id: " . $p_reports[$i]['report_id'] . ")</option>\n";
				$loadviewname = $p_reports[$i]['report_name'];
				$curviewid = "pold_" . $p_reports[$i]['report_id'];
			} else {
				$vcounter++;
				$options .= "<option value=pold_" . $p_reports[$i]['report_id'] . ">&nbsp;&nbsp;" . stripslashes($p_reports[$i]['report_name']) . "</option>\n"; // " (id: " . $p_reports[$i]['report_id'] . ")</option>\n";
			}
		}
	}
	$publishedViewNames = array();
	for($i=0;$i<count((array) $np_reports);$i++) {
		if(!$limitViews OR in_array($np_reports[$i]['sv_id'], $screenLimitViews)) {
			if($loadedView == "p" . $np_reports[$i]['sv_id'] OR $prevview == "p" . $np_reports[$i]['sv_id'] OR ($forceLastLoaded AND $lastLoaded == "p" . $np_reports[$i]['sv_id'])) {
				$vcounter++;
				$options .= "<option value=$currentView selected>&nbsp;&nbsp;" . stripslashes($np_reports[$i]['sv_name']) . "</option>\n"; // " (id: " . $np_reports[$i]['sv_id'] . ")</option>\n";
				$loadviewname = $np_reports[$i]['sv_name'];
				$curviewid = "p" . $np_reports[$i]['sv_id'];
			} else {
				$vcounter++;
				$options .= "<option value=p" . $np_reports[$i]['sv_id'] . ">&nbsp;&nbsp;" . stripslashes($np_reports[$i]['sv_name']) . "</option>\n"; // " (id: " . $np_reports[$i]['sv_id'] . ")</option>\n";
			}
			$publishedViewNames["p" . $np_reports[$i]['sv_id']] = stripslashes($np_reports[$i]['sv_name']); // used by the screen system to create a variable for each view name, and only the last loaded view is set to true.
		}
	}
	$to_return[0] = $options;
	$to_return[1] = $firstPublishedView;
	$to_return[2] = $lastStandardView;
	$to_return[3] = $pickgroups;
	$to_return[4] = $loadviewname;
	$to_return[5] = $curviewid;
	$to_return[6] = $publishedViewNames;
	return $to_return;

}

// this function draws in the interface parts of a display entries widget

function drawInterface($settings, $fid, $frid, $groups, $mid, $gperm_handler, $loadview, $loadOnlyView, $screen, $searches, $pageNav, $entryTotals, $messageText, $hiddenQuickSearches) {

	global $xoopsDB;
	global $xoopsUser;

	// unpack the $settings
	foreach($settings as $k=>$v) {
		${$k} = $v;
	}

	// run any Procedures the user might have requested
    global $procedureResults;
    $procedureResults = "";
	if( @$_POST['advcalc_acid'] AND $_POST['acid'] > 0 ) {
	  $procedureResults = formulize_runAdvancedCalculation( intval($_POST['acid'] )); // result will be an array with two or three keys: 'text' and 'output', and possibly 'groupingMap'.  Text is for display on screen "raw" and Output is a variable that can be used by a dev.  The output variable will be an array if groupings are in effect.  The keys of the array will be the various grouping values in effect.  The groupingMap will be present if there's a set of groupings in effect.  It is an array that contains all the grouping choices, their text equivalents and their data values (which are the keys in the output array) -- groupingMap is still to be developed/added to the mix....will be necessary when we are integrating with Drupal or other API uses.
      $procedureResults = $procedureResults['text'];
	}

	// get single/multi entry status of this form...
	$singleMulti = q("SELECT singleentry FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form = $fid");

	// flatten columns array and convert handles to ids so that we can send them to the change columns popup
	// Since 4.0 columns and columnhandles are identical...this is a cleanup job for later
	$colhandles = implode(",", $columnhandles); // part of $settings
	$flatcols = implode(",", $columns); // part of $settings (will be IDs if no framework in effect)

	$useWorking = ($screen AND !$screen->getVar('useworkingmsg')) ? false : true;
	$title = $screen ? $screen->getVar('title') : $settings['title']; 
	
	$submitButton =  "<input type=submit name=submitx style=\"position: absolute; left: -10000px;\" value='' ></input>\n";

	// need to establish these here because they are used in conditions lower down
	$add_own_entry = $gperm_handler->checkRight("add_own_entry", $fid, $groups, $mid);
	$proxy = $gperm_handler->checkRight("add_proxy_entries", $fid, $groups, $mid);
	$uid = $xoopsUser ? $xoopsUser->getVar('uid') : "0";
	$user_can_delete    = formulizePermHandler::user_can_delete_from_form($fid, $uid);
	$edit_form = $gperm_handler->checkRight("edit_form", $fid, $groups, $mid);
	$module_admin_rights = $gperm_handler->checkRight("module_admin", $mid, $groups, 1);

	// establish text and code for buttons, whether a screen is in effect or not
	$screenButtonText = array();
	$screenButtonText['changeColsButton'] = !$lockcontrols ? _formulize_DE_CHANGECOLS : "";
	$screenButtonText['calcButton'] = !$lockcontrols ? _formulize_DE_CALCS : "";
	$screenButtonText['proceduresButton'] = !$lockcontrols ? _formulize_DE_ADVCALCS : "";
	$screenButtonText['exportButton'] = !$lockcontrols ? _formulize_DE_EXPORT : "";
	$screenButtonText['exportCalcsButton'] = !$lockcontrols ? _formulize_DE_EXPORT_CALCS : "";
	$screenButtonText['importButton'] = !$lockcontrols ? _formulize_DE_IMPORTDATA : "";
	$screenButtonText['notifButton'] = !$lockcontrols ? _formulize_DE_NOTBUTTON : "";
	$screenButtonText['cloneButton'] = !$lockcontrols ? _formulize_DE_CLONESEL : "";
	$screenButtonText['deleteButton'] = !$lockcontrols ? _formulize_DE_DELETESEL : "";
	$screenButtonText['selectAllButton'] = !$lockcontrols ? _formulize_DE_SELALL : "";
	$screenButtonText['clearSelectButton'] = !$lockcontrols ? _formulize_DE_CLEARALL : "";
	$screenButtonText['resetViewButton'] = !$lockcontrols ? _formulize_DE_RESETVIEW : "";
	$screenButtonText['saveViewButton'] = !$lockcontrols ? _formulize_DE_SAVE : "";
	$screenButtonText['deleteViewButton'] = !$lockcontrols ? _formulize_DE_DELETE : "";

	// first 15 items must be the action buttons!

    $screenButtonText['procedureResults'] = $procedureResults;
	$screenButtonText['modifyScreenLink'] = ($edit_form AND $screen AND $module_admin_rights) ? _formulize_DE_MODIFYSCREEN : "";
	$screenButtonText['currentViewList'] = _formulize_DE_CURRENT_VIEW;
	$screenButtonText['saveButton'] = _formulize_SAVE;
	$screenButtonText['globalQuickSearch'] = _formulize_GLOBAL_SEARCH;
    if(!$lockcontrols) {
        $screenButtonText['addButton'] = $singleMulti[0]['singleentry'] == "" ? _formulize_DE_ADDENTRY : _formulize_DE_UPDATEENTRY;
        $screenButtonText['addMultiButton'] = _formulize_DE_ADD_MULTIPLE_ENTRY;
        $screenButtonText['addProxyButton'] = _formulize_DE_PROXYENTRY;
    }
	if($screen) {
		if($add_own_entry) {
			$screenButtonText['addButton'] = !$lockcontrols ? $screen->getVar('useaddupdate') : "";
			$screenButtonText['addMultiButton'] = !$lockcontrols ? $screen->getVar('useaddmultiple') : "";
		} else {
			$screenButtonText['addButton'] = "";
			$screenButtonText['addMultiButton'] = "";
		}
		if($proxy) {
			$screenButtonText['addProxyButton'] = !$lockcontrols ? $screen->getVar('useaddproxy') : "";
		} else {
			$screenButtonText['addProxyButton'] = "";
		}
		$screenButtonText['exportButton'] = !$lockcontrols ? $screen->getVar('useexport') : "";
		$screenButtonText['importButton'] = ($import_data = $gperm_handler->checkRight("import_data", $fid, $groups, $mid) AND !$frid) ? $screen->getVar('useimport') : "";
		$screenButtonText['notifButton'] = $screen->getVar('usenotifications');
		$screenButtonText['currentViewList'] = $screen->getVar('usecurrentviewlist');
		$screenButtonText['saveButton'] = !$lockcontrols ? $screen->getVar('desavetext') : "";
		$screenButtonText['changeColsButton'] = !$lockcontrols ? $screen->getVar('usechangecols') : "";
		$screenButtonText['calcButton'] = $screen->getVar('usecalcs');
		$screenButtonText['proceduresButton'] = $screen->getVar('useadvcalcs');
		$screenButtonText['exportCalcsButton'] = $screen->getVar('useexportcalcs');
		// only include clone and delete if the checkboxes are in effect (2 means do not use checkboxes)
		if($screen->getVar('usecheckboxes') != 2) {
			$screenButtonText['cloneButton'] = $add_own_entry ? $screen->getVar('useclone') : "";
			if ($user_can_delete and !$settings['lockcontrols']) {
				$screenButtonText['deleteButton'] = $screen->getVar('usedelete');
			} else {
				$screenButtonText['deleteButton'] = "";
			}
			$screenButtonText['selectAllButton'] = $screen->getVar('useselectall');
			$screenButtonText['clearSelectButton'] = $screen->getVar('useclearall');
		} else {
			$screenButtonText['cloneButton'] = "";
			$screenButtonText['deleteButton'] = "";
			$screenButtonText['selectAllButton'] = "";
			$screenButtonText['clearSelectButton'] = "";
		}
		// only include the reset, save, deleteview buttons if the current view list is in effect
		if($screen->getVar('usecurrentviewlist')) {
			$screenButtonText['resetViewButton'] = $screen->getVar('usereset');
			$screenButtonText['saveViewButton'] = $screen->getVar('usesave');
			$screenButtonText['deleteViewButton'] = $screen->getVar('usedeleteview');
		} else {
			$screenButtonText['resetViewButton'] = "";
			$screenButtonText['saveViewButton'] = "";
			$screenButtonText['deleteViewButton'] = "";
		}
	}
	if($delete_other_reports = $gperm_handler->checkRight("delete_other_reports", $fid, $groups, $mid)) { $pubstart = 10000; }
	if($screenButtonText['saveButton']) { $screenButtonText['goButton'] = $screenButtonText['saveButton']; } // want this button accessible by two names, essentially, since it serves two purposes semantically/logically
	$onActionButtonCounter = 0;
	$atLeastOneActionButton = false;
    $atLeastOneActionButtonNotChangeCols = false;
	foreach($screenButtonText as $scrButton=>$scrText) {
		$buttonCodeArray[$scrButton] = formulize_screenLOEButton($scrButton, $scrText, $settings, $fid, $frid, $colhandles, $flatcols, $pubstart, $loadOnlyView, $calc_cols, $calc_calcs, $calc_blanks, $calc_grouping, $singleMulti[0]['singleentry'], $lastloaded, $currentview, $endstandard, $pickgroups, $viewoptions, $loadviewname, $advcalc_acid, $screen);
		if($buttonCodeArray[$scrButton] AND $onActionButtonCounter < 14) { // first 0-14 items in the array should be the action buttons only
			$atLeastOneActionButton = true;
            if($onActionButtonCounter > 0) {
                $atLeastOneActionButtonNotChangeCols = true;
            }
		}
		$onActionButtonCounter++;
	}
    
    // make a ... button available for opening up extra actions beyond change cols
    if($atLeastOneActionButtonNotChangeCols) {
        $buttonCodeArray['moreActionsButton'] = formulize_screenLOEButton('moreActions', '...', $settings, $fid, $frid, $colhandles, $flatcols, $pubstart, $loadOnlyView, $calc_cols, $calc_calcs, $calc_blanks, $calc_grouping, $singleMulti[0]['singleentry'], $lastloaded, $currentview, $endstandard, $pickgroups, $viewoptions, $loadviewname, $advcalc_acid, $screen);
    }
    
	if($hlist) { // if we're on the calc side, then the export button should be the export calcs one
		$buttonCodeArray['exportButton'] = $buttonCodeArray['exportCalcsButton'];
	}
	$buttonCodeArray['pageNavControls'] = $pageNav; // put this unique UI element into the buttonCodeArray for use elsewhere if necessary
    $buttonCodeArray['numberOfEntries'] = $entryTotals; 

	$currentViewName = $settings['loadviewname'];

	// variables used in the default template...
	$buttonCodeArray['title'] = trans($title);
	$buttonCodeArray['modifyScreenLink'] = $buttonCodeArray['modifyScreenLink'];
	$buttonCodeArray['autoLoadedView'] = $loadview ? $loadviewname : '';
	$buttonCodeArray['lockControls'] = $lockcontrols;
	$buttonCodeArray['actionButtonHeading'] = $atLeastOneActionButton ? _formulize_DE_ACTIONS : "";
	if($add_own_entry AND $singleMulti[0]['singleentry'] == "" AND ($buttonCodeArray['addButton'] OR $buttonCodeArray['addMultiButton'])) {
		$buttonCodeArray['addProxyButton'] = '';
	} elseif($add_own_entry AND $proxy AND ($buttonCodeArray['addButton'] OR $buttonCodeArray['addProxyButton'])) { // this is a single entry form, so add in the update and proxy buttons if they have proxy, otherwise, just add in update button
		$buttonCodeArray['addMultiButton'] = '';
	} elseif($add_own_entry AND $buttonCodeArray['addButton']) {
		$buttonCodeArray['addMultiButton'] = '';
		$buttonCodeArray['addProxyButton'] = '';
	} elseif($proxy AND $buttonCodeArray['addProxyButton']) {
		$buttonCodeArray['addMultiButton'] = '';
		$buttonCodeArray['addButton'] = '';
	}	
	$buttonCodeArray['addButtonHeading'] = ($buttonCodeArray['addButton'] OR $buttonCodeArray['addMultiButton'] OR $buttonCodeArray['addProxyButton']) ? _formulize_DE_FILLINFORM : "";
	$buttonCodeArray['submitButton'] = $submitButton;
	$buttonCodeArray['lockControlsWarning'] = "<input type=hidden name=curviewid id=curviewid value=$curviewid>"._formulize_DE_WARNLOCK;
	
	
	// print current view list even if the text is blank, it will be a hidden value in this case
    $screenOrScreenType = $screen ? $screen : 'listOfEntries';
	if(!strstr(getTemplateToRender('toptemplate', $screenOrScreenType), 'currentViewList') AND
		 !strstr(getTemplateToRender('bottomtemplate', $screenOrScreenType), 'currentViewList') AND
		 !strstr(getTemplateToRender('openlisttemplate', $screenOrScreenType), 'currentViewList') AND
		 !strstr(getTemplateToRender('closelisttemplate', $screenOrScreenType), 'currentViewList') 
		 ) {
		print "<input type=hidden name=currentview id=currentview value=\"$currentview\"></input>\n";
	} 

	$filterTypes = array('\$quickDateRange', '\$quickFilter', '\$quickMultiFilter');
	$screenOrScreenType = $screen ? $screen : 'listOfEntries';
	$filterHandles = extractHandles($filterTypes, getTemplateToRender('toptemplate', $screenOrScreenType));
	$filterHandles = array_merge($filterHandles, extractHandles($filterTypes, getTemplateToRender('listtemplate', $screenOrScreenType)));
	$filterHandles = array_merge($filterHandles, extractHandles($filterTypes, getTemplateToRender('bottomtemplate', $screenOrScreenType)));
	$filterHandles = array_merge($filterHandles, extractHandles($filterTypes, getTemplateToRender('openlisttemplate', $screenOrScreenType)));
	$filterHandles = array_merge($filterHandles, extractHandles($filterTypes, getTemplateToRender('closelisttemplate', $screenOrScreenType)));
    // add any columns for which the advanceview settings call for not just a box
    if($screen) {
        foreach($screen->getVar('advanceview') as $avData) {
            if(isset($avData[3]) AND $avData[3] != 'Box') {
                if(!in_array($avData[0], $filterHandles)) {
                    $filterHandles[] = $avData[0];
                }
            }
        }
    }

	$quickSearches = createQuickSearches($searches, $settings, $hiddenQuickSearches, $filterHandles); 

	foreach($quickSearches as $handle=>$qsCode) {
		$handle = str_replace("-","_",$handle);
		if(screenUsesSearchStringWithHandle(
            $screenOrScreenType, array('quickSearch', 'quickSearchBox_'), $handle)
            OR in_array($handle, $settings['pubfilters'])) {
				$buttonCodeArray['quickSearch' . $handle] = $qsCode['search']; // set variables for use in the template
				$buttonCodeArray['quickSearchBox_'.$handle] = $qsCode['search'];
		}
		if(screenUsesSearchStringWithHandle(
            $screenOrScreenType, array('quickFilter', 'quickSearchFilter_'), $handle)
            OR in_array($handle, $settings['pubfilters'])) {
				$buttonCodeArray['quickFilter' . $handle] = $qsCode['filter']; // set variables for use in the template
				$buttonCodeArray['quickSearchFilter_'.$handle] = $qsCode['filter'];
		}
		if(screenUsesSearchStringWithHandle(
            $screenOrScreenType, array('quickMultiFilter', 'quickSearchMultiFilter_'), $handle)
            OR in_array($handle, $settings['pubfilters'])) {
				$buttonCodeArray['quickMultiFilter' . $handle] = $qsCode['multiFilter']; // set variables for use in the template
				$buttonCodeArray['quickSearchMultiFilter_'.$handle] = $qsCode['multiFilter'];
		}
		if(screenUsesSearchStringWithHandle(
            $screenOrScreenType, array('quickDateRange', 'quickSearchDateRange_'), $handle)
            OR in_array($handle, $settings['pubfilters'])) {
				$buttonCodeArray['quickDateRange' . $handle] = $qsCode['dateRange']; // set variables for use in the template
				$buttonCodeArray['quickSearchDateRange_'.$handle] = $qsCode['dateRange'];
		}
	}

	formulize_benchmark("before rendering top template");
	$buttonCodeArray['submitButton'] = $submitButton;
	$buttonCodeArray['messageText'] = str_replace("'", "\'", $messageText); // message text will be output inside single quotes;
	formulize_screenLOETemplate($screen, "top", $buttonCodeArray, $settings);
	formulize_benchmark("after rendering top template");
	if(strstr(getTemplateToRender('toptemplate', $screenOrScreenType), "\$submitButton")) {
		unset($buttonCodeArray['submitButton']); // do not send this back if it has been used. Otherwise, send it back and we can put it at the bottom of the page if necessary
	}

	print "<input type=hidden name=newcols id=newcols value=\"\"></input>\n";
	print "<input type=hidden name=pubfilters id=pubfilters value=\"".implode(",",$settings['pubfilters'])."\"></input>\n";
	print "<input type=hidden name=oldcols id=oldcols value='$flatcols'></input>\n";
	print "<input type=hidden name=ventry id=ventry value=\"\"></input>\n";
	print "<input type=\"hidden\" name=\"overridescreen\" id=\"overridescreen\" value=\"\"></input>\n";
	print "<input type=hidden name=delconfirmed id=delconfirmed value=\"\"></input>\n";
	print "<input type=hidden name=cloneconfirmed id=cloneconfirmed value=\"\"></input>\n";
	print "<input type=hidden name=xport id=xport value=\"\"></input>\n";
	print "<input type=hidden name=xport_cust id=xport_cust value=\"\"></input>\n";
	print "<input type=hidden name=loadreport id=loadreport value=\"\"></input>\n";
	print "<input type=hidden name=lastloaded id=lastloaded value=\"$lastloaded\"></input>\n";
	print "<input type=hidden name=saveviewname id=saveviewname value=\"\"></input>\n";
	print "<input type=hidden name=saveviewoptions id=saveviewoptions value=\"\"></input>\n";

	// setup HTML to receive custom button values -- javascript function sets these based on which button is clicked
	print "<input type=hidden name=caid id=caid value=\"\"></input>\n";
	print "<input type=hidden name=caentries id=caentries value=\"\"></input>\n";

	// hidden fields used by UI in the Entries section
	print "<input type=hidden name=sort id=sort value=\"$sort\"></input>\n";
	print "<input type=hidden name=order id=order value=\"$order\"></input>\n";

	print "<input type=hidden name=hlist id=hlist value=\"$hlist\"></input>\n";
	print "<input type=hidden name=hcalc id=hcalc value=\"$hcalc\"></input>\n";
	print "<input type=hidden name=lockcontrols id=lockcontrols value=\"$lockcontrols\"></input>\n";
	print "<input type=hidden name=resetview id=resetview value=\"\"></input>\n";

	// hidden fields used by calculations
	print "<input type=hidden name=calc_cols id=calc_cols value=\"$calc_cols\"></input>\n";
	print "<input type=hidden name=calc_calcs id=calc_calcs value=\"$calc_calcs\"></input>\n";
	print "<input type=hidden name=calc_blanks id=calc_blanks value=\"$calc_blanks\"></input>\n";
	print "<input type=hidden name=calc_grouping id=calc_grouping value=\"$calc_grouping\"></input>\n";

	// advanced calculations
	print "<input type=hidden name=advcalc_acid id=advcalc_acid value=\"$advcalc_acid\"></input>\n";

	// advanced scope
	print "<input type=hidden name=advscope id=advscope value=\"\"></input>\n";

	// delete view
	print "<input type=hidden name=delview id=delview value=\"\"></input>\n";
	print "<input type=hidden name=delviewid_formulize id=delviewid_formulize value=\"$loadedview\"></input>\n";

	// related to saving a new view
	print "<input type=hidden name=saveid_formulize id=saveid_formulize value=\"\"></input>\n";
	print "<input type=hidden name=savename id=savename value=\"\"></input>\n";
	print "<input type=hidden name=savegroups id=savegroups value=\"\"></input>\n";
	print "<input type=hidden name=savelock id=savelock value=\"\"></input>\n";
	print "<input type=hidden name=savescope id=savescope value=\"\"></input>\n";

	// forcequery value, perpetuates from pageload to pageload
	print "<input type=hidden name=forcequery id=forcequery value=\"" .intval($_POST['forcequery']) . "\"></input>\n";

	// lockedColumns is the list of columns that the user has locked in place...however it is relative to the currently active columns...changing columns while columns are locked may have unexpected results!
	print "<input type=hidden name=formulize_lockedColumns id=formulize_lockedColumns value=\"".implode(",",$lockedColumns)."\"></input>\n";

	// scroll x and y are used to retain the scroll position after the page reloads
	print "<input type=hidden name=formulize_scrollx id=formulize_scrollx value=\"\"></input>\n";
	print "<input type=hidden name=formulize_scrolly id=formulize_scrolly value=\"\"></input>\n";

	$useXhr = false;
	if($screen) {
		if($screen->getVar('dedisplay')) {
			$useXhr = true;
		}
	}

	interfaceJavascript($fid, $frid, $currentview, $useWorking, $useXhr, $settings['lockedColumns']); // must be called after form is drawn, so that the javascript which clears ventry can operate correctly (clearing is necessary to avoid displaying the form after clicking the Back button on the form and then clicking a button or doing an operation that causes a posting of the controls form).

	$buttonCodeArray['quickSearches'] = $quickSearches;
	
	$GLOBALS['formulize_buttonCodeArray'] = $buttonCodeArray; // made a global just because? Maybe it is used in some external code somewhere?
	
	return $buttonCodeArray;
}

// check for a handle being used in a quickSearch variable anywhere in relevant templates
function screenUsesSearchStringWithHandle($screenOrScreenType, $searchString, $handle) {
    $searchString = !is_array($searchString) ? array($searchString) : $searchString;
    foreach($searchString as $thisSearchString) {
        if(
            strstr(getTemplateToRender('toptemplate', $screenOrScreenType), $thisSearchString . $handle) OR
    		strstr(getTemplateToRender('bottomtemplate', $screenOrScreenType), $thisSearchString . $handle) OR
    		strstr(getTemplateToRender('openlisttemplate', $screenOrScreenType), $thisSearchString . $handle) OR
    		strstr(getTemplateToRender('closelisttemplate', $screenOrScreenType), $thisSearchString . $handle)    
        ) {
            return true;
        }
    }
    return false;
}

// THIS FUNCTION DRAWS IN THE RESULTS OF THE QUERY
function drawEntries($fid, $cols, $searches, $frid, $scope, $standalone, $currentURL, $gperm_handler, $uid, $mid, $groups, $settings, $member_handler, $screen, $data, $regeneratePageNumbers, $hiddenQuickSearches, $cResults, $buttonCodeArray) { // , $loadview="") { // -- loadview removed from this function sept 24 2005
	// determine if the query reached a limit in the number of entries to return
	$LOE_limit = 0;
	if(!is_array($data)) {
		$LOE_limit = is_numeric($data) ? $data : 0;
		$data = array();
	}

	global $xoopsDB;

	$useScrollBox = true;
	$useHeadings = true;
	$repeatHeaders = 5;
	$columnWidth = 0;
	$textWidth = 35;
	$useCheckboxes = $settings['lockcontrols'] ? 2 : 0;
	$useViewEntryLinks = $settings['lockcontrols'] ? 0 : 1;
	$useSearch = $settings['lockcontrols'] ? 0 : 1;
	$deColumns = array();
	$useSearchCalcMsgs = 1;
	$inlineButtons = array();
	$hiddenColumns = array();
	$formulize_LOEPageSize = 10;
    $searchTypes = array_fill_keys($cols, 'Box');
	if($screen) {
		$useScrollBox = $screen->getVar('usescrollbox');
		$useHeadings = $screen->getVar('useheadings');
		$repeatHeaders = $screen->getVar('repeatheaders');
		$columnWidth = $screen->getVar('columnwidth');
		$textWidth = $screen->getVar('textwidth');
		if($textWidth == 0) { $textWidth = 10000; }
		$useCheckboxes = $screen->getVar('usecheckboxes');
		$useViewEntryLinks = $screen->getVar('useviewentrylinks');
		$useSearch = $screen->getVar('usesearch') ? $screen->getVar('usesearch') : 0;
		$hiddenColumns = $screen->getVar('hiddencolumns');
		$deColumns = $screen->getVar('decolumns');
		$deDisplay = $screen->getVar('dedisplay');
		$useSearchCalcMsgs = $screen->getVar('usesearchcalcmsgs');
		foreach($screen->getVar('customactions') as $caid=>$thisCustomAction) {
			if($thisCustomAction['appearinline'] == 1) {
				list($caCode) = processCustomButton($caid, $thisCustomAction);
				if($caCode) {
					$inlineButtons[$caid] = $thisCustomAction;
				}
			}
		}
		$formulize_LOEPageSize = $screen->getVar('entriesperpage');
        $formulize_LOEPageSize = isset($_POST['formulize_entriesPerPage']) ? intval($_POST['formulize_entriesPerPage']) : $formulize_LOEPageSize;
        foreach($screen->getVar('advanceview') as $avData) {
            $searchTypes[$avData[0]] = isset($avData[3]) ? $avData[3] : 'Box'; // default to quickSearch boxes, otherwise use type specified in screen settings
        }
	}

	// Prepare link for downloading calculations
	$downloadCalculationsURL = "";
	$downloadCalculationsText = "";
	if(!$settings['hcalc']) {
		// garbage collection. delete files older than 6 hours
		formulize_scandirAndClean(XOOPS_ROOT_PATH . SPREADSHEET_EXPORT_FOLDER, _formulize_EXPORT_FILENAME_TEXT."_");
		$exportTitle = $screen ? $screen->getVar('title') : $settings['title'];
		$exportTitle = "&#039;".str_replace(array(" ", "-", "/", "'", "`", "\\", ".", "?", ",", ")", "(", "[", "]"), "_", trans($exportTitle))."&#039;";
		$export_filename = _formulize_EXPORT_FILENAME_TEXT."_".$exportTitle."_".date("M_j_Y_Hi").".html";
        $downloadCalculationsURL = XOOPS_URL . SPREADSHEET_EXPORT_FOLDER . $export_filename;
		$downloadCalculationsText = _formulize_DE_CLICKSAVE; 
	}

	// determine if we need scrollbox class for the outer most container
	$scrollBoxClassOnOff = '';
	if($useScrollBox AND count((array) $data) > 0) {
		$scrollBoxClassOnOff =  " scrollbox ";
	}

	// determine if the heading help and lock icons are shown
	// they are never shown on the calculation view
	$headingHelpAndLockShown = false;
	$module_handler = xoops_gethandler('module');
	$config_handler = xoops_gethandler('config');
	$formulizeModule = $module_handler->getByDirname("formulize");
	$formulizeConfig = $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
	$headingHelpAndLockShown = $formulizeConfig['heading_help_link'];
	
	// work out how many columns are possible
	$colspan = count((array) $cols) + count((array) $inlineButtons) + 1; // columns, plus inline buttons, plus hidden floating column
	if($useViewEntryLinks OR $useCheckboxes != 2) {
		$colspan++; // plus 1 for the checkbox/view entry link column
	}
	
	// prepare the UI controls for calculations
	$modCalcsButton = "";
	$cancelCalcsButton = "";
	$toggleCalcsButton = "";
	if($settings['calc_cols'] AND !$settings['lockcontrols'] AND ($useSearchCalcMsgs == 1 OR $useSearchCalcMsgs == 3)) { 
		$calc_cols = $settings['calc_cols'];
		$calc_calcs = $settings['calc_calcs'];
		$calc_blanks = $settings['calc_blanks'];
		$calc_grouping = $settings['calc_grouping'];
		
		$modCalcsButton = "<input type='button' style='width: 140px;' name='mod_calculations'
			value='"._formulize_DE_MODCALCS."' onclick=\"javascript:showPop('".XOOPS_URL.
			"/modules/formulize/include/pickcalcs.php?fid=$fid&frid=$frid&calc_cols=$calc_cols&calc_calcs=$calc_calcs&calc_blanks=$calc_blanks&calc_grouping=".
			urlencode($calc_grouping)."&cols=".urlencode(implode(",",$cols))."');\"></input>";
		
		$cancelCalcsButton = "<input type='button' style='width: 140px;' name='cancelcalcs'
			value='"._formulize_DE_CANCELCALCS."' onclick=\"javascript:cancelCalcs();\"></input>";
		
		if($settings['hcalc']) {
			$toggleCalcsButton = "<input type='button' style='width: 140px;' name='hidelist'
				value='"._formulize_DE_HIDELIST."' onclick=\"javascript:hideList();\"></input>";
		} else {
			$toggleCalcsButton = "<input type='button' style='width: 140px;' name='showlist'
				value='"._formulize_DE_SHOWLIST."' onclick=\"javascript:showList();\"></input>";
		}
	}
	
	// figure out the applicable search help file
	if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/docs/search_help_"._LANGCODE.".html")) {
		$search_help_filepath = XOOPS_URL."/modules/formulize/docs/search_help_"._LANGCODE.".html";
	} elseif(file_exists(XOOPS_ROOT_PATH."/modules/formulize/docs/search_help_"._LANGCODE.".xhtml")) {
		$search_help_filepath = XOOPS_URL."/modules/formulize/docs/search_help_"._LANGCODE.".xhtml";
	} else {
		$search_help_filepath = XOOPS_URL."/modules/formulize/docs/search_help.xhtml";
	}
	$searchHelp = "<a href='' class='header-info-link' onclick=\"javascript:showPop('".$search_help_filepath."'); return false;\" title='"._formulize_DE_SEARCH_POP_HELP."'></a>";
    $toggleSearches = "<a href='' class='search-toggle-link' onclick=\"javascript:toggleSearches(); return false;\" title='"._formulize_DE_TOGGLE_SEARCHES."'>
        &#9013;
        </a>";
    
    global $procedureResults; // set in drawInterface
	
	$templateVariables = array(
		'procedureResults' => $procedureResults,
		'scrollBoxClassOnOff' => $scrollBoxClassOnOff,
		'headingHelpAndLockShown' => $headingHelpAndLockShown,
		'headersShown' => $useHeadings,
		'headers' => ($useHeadings ? getHeaders($cols) : array()),
		'checkBoxesShown' => ($useCheckboxes != 2 ? true : false),
		'viewEntryLinksShown' => $useViewEntryLinks,
		'lockedColumns' => $settings['lockedColumns'],
		'numberOfInlineCustomButtons' => count((array) $inlineButtons),
		'spacerNeeded' => ($columnWidth ? true : false),
		'columnWidthStyle' => ($columnWidth ? "style='width: $columnWidth"."px'" : ""),
		'colspan' => $colspan,
		'downloadCalculationsURL' => $downloadCalculationsURL,
		'downloadCalculationsText' => $downloadCalculationsText,
		'modCalcsButton' => $modCalcsButton,
		'cancelCalcsButton' => $cancelCalcsButton,
		'toggleCalcsButton' => $toggleCalcsButton,
		'searchesShown' => $useSearch,
        'toggleSearches' => ($useSearch == 2 ? $toggleSearches : ''),
		'searchHelp' => (($useCheckboxes != 2 OR $useViewEntryLinks) ? $searchHelp : ''),
        'searchTypes' => $searchTypes,
		'columns' => $cols
	);
	
	// add all search boxes to the available set of variables
    // and the specified filter type if there's a declared type in the advanceview
	foreach($buttonCodeArray['quickSearches'] as $handle=>$qsCode) {
		if(!isset($buttonCodeArray['quickSearch'.$handle])) {
			$buttonCodeArray['quickSearch'.$handle] = $qsCode['search'];
			$buttonCodeArray['quickSearchBox_'.$handle] = $qsCode['search']; // new naming convention that applies to other types also, see above
		}
        if(isset($searchTypes[$handle]) AND $searchTypes[$handle] != 'Box') {
            $buttonCodeArray['quick'.$searchTypes[$handle].$handle] = $qsCode[lcfirst($searchTypes[$handle])]; 
            $buttonCodeArray['quickSearch'.$searchTypes[$handle].'_'.$handle] = $qsCode[lcfirst($searchTypes[$handle])]; 
        }
	}
	
	// setup the view name variables, with true only set for the last loaded view
	// this way webmasters can write templates that check for if a view is currently active
	$viewNumber = 1;
	foreach($settings['publishedviewnames'] as $id=>$thisViewName) {
		$pattern = '/[^a-zA-Z0-9]/';
		$thisViewName =preg_replace($pattern, '', (string) $thisViewName);
		if($id == $settings['lastloaded']) {
			$templateVariables[$thisViewName] = true;
			$templateVariables['view'.$viewNumber] = true;
		} else {
			$templateVariables[$thisViewName] = false;
			$templateVariables['view'.$viewNumber] = false;
		}
		$viewNumber++;
	}

	$templateVariables = array_merge($buttonCodeArray, $templateVariables); // buttonCodeArray returned from drawInterface, and then passed to drawEntries

	formulize_screenLOETemplate($screen, 'openlist', $templateVariables, $settings);
	
	// MASTER HIDELIST CONDITIONAL...
	if(!$settings['hlist']) {
		
		if (count((array) $data) == 0) {
			// kill an empty dataset so there's no rows drawn
			unset($data);
		} else {
			// get form handles in use
			$mainFormHandle = key($data[key($data)]);
		}
		
        $element_handler = xoops_getmodulehandler('elements', 'formulize');
        
		// setup counter for cells, because we need to id each deDiv uniquely
		$deInstanceCounter = 1;
		$headcounter = 0;
		$blankentries = 0;
		$GLOBALS['formulize_displayElement_LOE_Used'] = false;
		$formulize_LOEPageStart = (isset($_POST['formulize_LOEPageStart']) AND !$regeneratePageNumbers) ? intval($_POST['formulize_LOEPageStart']) : 0;
		// adjust formulize_LOEPageSize if the actual count of entries is less than the page size
		$formulize_LOEPageSize = $GLOBALS['formulize_countMasterResultsForPageNumbers'] < $formulize_LOEPageSize ? $GLOBALS['formulize_countMasterResultsForPageNumbers'] : $formulize_LOEPageSize;
		$actualPageSize = $formulize_LOEPageSize ? $formulize_LOEPageStart + $formulize_LOEPageSize : $GLOBALS['formulize_countMasterResultsForPageNumbers'];
		if(isset($data)) {
			
			$templateVariables['class'] = 'even'; // seed the table row class... will flip to odd on first row
			
			foreach($data as $id=>$entry) {
				formulize_benchmark("starting to draw one row of results");

				// check to make sure this isn't an unset entry (ie: one that was blanked by the extraction layer just prior to sending back results
				// Since the extraction layer is unsetting entries to blank them, this condition should never be met?
				// If this condition is ever met, it may very well screw up the paging of results!
				// NOTE: this condition is met on the last page of a paged set of results, unless the last page as exactly the same number of entries on it as the limit of entries per page
				if($entry != "") {

					// draw in the heading row if necessary and keep track of how many rows it's been
					if($headcounter == $repeatHeaders AND $repeatHeaders > 0) {
						if($useHeadings AND function_exists('drawHeaderRow')) {
							print drawHeaderRow($templateVariables['headers'], $templateVariables['checkBoxesShown'], $templateVariables['viewEntryLinksShown'], $templateVariables['columnWidthStyle'], $templateVariables['headingHelpAndLockShown'], $templateVariables['lockedColumns'], $templateVariables['numberOfInlineCustomButtons'], $templateVariables['spacerNeeded']);
						}
						$headcounter = 0;
					}
					$headcounter++;

					// get the entry_id of the mainform entry in this record from the dataset
					unset($ids);
					$ids = internalRecordIds($entry, $mainFormHandle);
					$entry_id = $ids[0];
					$GLOBALS['formulize_viewEntryId'] = $entry_id;

					// if the user can interact with the data...
					$selectionCheckbox = "";
					$viewEntryLinkCode = "";
					if(!$settings['lockcontrols']) { //  AND !$loadview) { // -- loadview removed from this function sept 24 2005
						
						// check to see if we should draw in the delete checkbox
						// 2 is none, 1 is all
						if ($useCheckboxes != 2 and ($useCheckboxes == 1 or formulizePermHandler::user_can_delete_entry($fid, $uid, $entry_id))) {
							$selectionCheckbox = "<input type=checkbox title='" . _formulize_DE_DELBOXDESC . "' class='formulize_selection_checkbox' name='delete_" . $entry_id . "' id='delete_" . $entry_id . "' value='delete_" . $entry_id . "'>";
						}
												
						$viewEntryLinkCode = "<a href='" . $currentURL;
						if(strstr($currentURL, "?")) { // if params are already part of the URL...
							$viewEntryLinkCode .= "&";
						} else {
							$viewEntryLinkCode .= "?";
						}
						$viewEntryLinkCode .= "ve=" . $entry_id . "' onclick=\"javascript:goDetails('" . $entry_id . "');return false;\"";
						// put into global scope so the function 'viewEntryLink' can pick it up
						$GLOBALS['formulize_viewEntryLinkCode'] = $viewEntryLinkCode;
						
					} 
			
					$templateVariables['selectionCheckbox'] = $selectionCheckbox;
					$templateVariables['viewEntryLink'] = $useViewEntryLinks ? viewEntryLink() : "";
					$templateVariables['class'] = ($templateVariables['class'] == 'even' ? 'odd' : 'even');
					$templateVariables['rowNumber'] = $id+2;
					$templateVariables['columnContents'] = array();
                    $templateVariables['columnHandles'] = array();
                    $templateVariables['columnFormIds'] = array();
                    $templateVariables['entry'] = $entry;
                    $templateVariables['entry_id'] = $entry_id;
					
					for($i=0;$i<count((array) $cols);$i++) {
			
						$col = $cols[$i];
						$colhandle = $settings['columnhandles'][$i];
						
                        $templateVariables['columnHandles'][] = $colhandle;
                        $colElementObject = $element_handler->get($colhandle);
                        $templateVariables['columnFormIds'][] = $colElementObject ? $colElementObject->getVar('id_form') : $fid;
                        
						if($col == "creation_uid" OR $col == "mod_uid") {
							$userObject = $member_handler->getUser(display($entry, $col));
							if($userObject) {
								$nameToDisplay = $userObject->getVar('name') ? $userObject->getVar('name') : $userObject->getVar('uname');
							} else {
								$nameToDisplay = _FORM_ANON_USER;
							}
							$value = "<a href=\"" . XOOPS_URL . "/userinfo.php?uid=" . display($entry, $col) . "\" target=_blank>" . $nameToDisplay . "</a>";
						} else {
							$value = display($entry, $col);
						}

						ob_start();
						// set in the display function, corresponds to the entry id of the record in the form where the current value was retrieved from.  If there is more than one local entry id, because of a one to many framework, then this will be an array that corresponds to the order of the values returned by display.
						$currentColumnLocalId = $GLOBALS['formulize_mostRecentLocalId'];
                        
                        $elementDisplayed = false;
						// if we're supposed to display this column as an element... (only show it if they have permission to update this entry)
						if (in_array($colhandle, $deColumns)) {
							include_once XOOPS_ROOT_PATH . "/modules/formulize/include/elementdisplay.php";
							if($frid) { // need to work out which form this column belongs to, and use that form's entry ID.  Need to loop through the entry to find all possible internal IDs, since a subform situation would lead to multiple values appearing in a single cell, so multiple displayElement calls would be made each with their own internal ID.
								foreach($entry as $entryFormHandle=>$entryFormData) {
									$multiValueBRNeeded = false;
									foreach($entryFormData as $internalID=>$entryElements) {
                                        $deThisIntId = false;
                                        foreach($entryElements as $entryHandle=>$values) {
                                            if($entryHandle == $col AND $internalID) { // we found the element that we're trying to display
                                                $displayElementObject = $element_handler->get($entryHandle);
                                                if(formulizePermHandler::user_can_edit_entry($displayElementObject->getVar('id_form'), $uid, $internalID)) {
                                                    if($deThisIntId) { print "\n<br />\n"; } // could be a subform so we'd display multiple values
                                                    if($deDisplay) {
                                                        if($multiValueBRNeeded) { print "\n<br />\n"; } // in the case of multiple values, split them based on this
                                                        print '<div id="deDiv_'.$colhandle.'_'.$internalID.'_'.$deInstanceCounter.'">';
                                                        print getHTMLForList($values, $colhandle, $internalID, $deDisplay, $textWidth, $internalID, $fid, $cellRowAddress, $i, $deInstanceCounter); // $internalID passed in in place of $currentColumnLocalId because we are manually looping through the data to get to the lowest level, so we can be sure of the local id that is in use, and it won't be an array, etc (unless we're showing a checkbox element??? or something else with multiple values??? - probably doesn't matter because the entry id is the same for all values of a single element that allows multiple selection)
                                                        print "</div>";
                                                        $deInstanceCounter++;
                                                    } else {
                                                        if($deThisIntId) { print "\n<br />\n"; } // extra break to separate multiple form elements in the same cell, for readability/usability
                                                        // NEEDS DEBUG - ELEMENTS NOT DISPLAYING
                                                        displayElement("", $colhandle, $internalID);
                                                    }
                                                    $deThisIntId = true;
                                                    $multiValueBRNeeded = true;
                                                    $elementDisplayed = true;
                                                }
                                            }
                                        }
									}
								}
							} elseif(formulizePermHandler::user_can_edit_entry($fid, $uid, $entry_id)) { // display based on the mainform entry id
								if($deDisplay) {
									print '<div id="deDiv_'.$colhandle.'_'.$entry_id.'_'.$deInstanceCounter.'">';
									print getHTMLForList($value,$colhandle,$entry_id, $deDisplay, $textWidth, $currentColumnLocalId, $fid, $cellRowAddress, $i, $deInstanceCounter);
									print "</div>";
									$deInstanceCounter++;
								} else {
									// NEEDS DEBUG - ELEMENTS NOT DISPLAYING
									displayElement("", $colhandle, $entry_id); // works for mainform only!  To work on elements from a framework, we need to figure out the form the element is from, and the entry ID in that form, which is done above
								}
                                $elementDisplayed = true;
							}
							$GLOBALS['formulize_displayElement_LOE_Used'] = true;
						}
                        if(!$elementDisplayed AND ($col != "creation_uid" AND $col!= "mod_uid" AND $col != "entry_id")) {
							print getHTMLForList($value, $col, $entry_id, 0, $textWidth, $currentColumnLocalId, $fid, $cellRowAddress, $i);
						} elseif(!$elementDisplayed) { // no special formatting on the uid columns:
							print $value;
						}
						$templateVariables['columnContents'][] = ob_get_clean();
					}

					// handle inline custom buttons
					$templateVariables['customButtons'] = array();
					foreach($inlineButtons as $caid=>$thisCustomAction) {
						list($caCode) = processCustomButton($caid, $thisCustomAction, $entry_id, $entry); // only bother with the code, since we already processed any clicked button above
						if($caCode) {
							$templateVariables[$thisCustomAction['handle']] = $caCode;
							$templateVariables['customButtons'][$thisCustomAction['handle']] = $caCode; // assign the button code that was returned
						}
					}
					
					formulize_screenLOETemplate($screen, 'list', $templateVariables, $settings);
					
					// handle hidden elements for passing back to custom buttons
					foreach($hiddenColumns as $thisHiddenCol) {
						print "\n<input type=\"hidden\" name=\"hiddencolumn_".$entry_id."_$thisHiddenCol\" value=\"" . htmlspecialchars(displayTogether($entry, $thisHiddenCol, '=]-!')) . "\"></input>\n";
					}
					
				} else { // this is a blank entry
					$blankentries++;
				} // end of not "" check
			} // end of foreach data as entry
		} // end of if there is any data to draw
	}// END OF MASTER HIDELIST CONDITIONAL

	$calculationResults = "";
	if($settings['calc_cols'] AND !$settings['hcalc'] AND !$settings['lockcontrols']) { 
		ob_start();
		// 0 is the masterresults, 1 is the blanksettings, 2 is grouping settings
		printResults($cResults[0], $cResults[1], $cResults[2], $cResults[3], $cResults[4], $export_filename, $settings['title']);
		$calculationResults = ob_get_clean();
	}	
		
	$noDataFound = "";
	if((!isset($data) OR count((array) $data) == $blankentries) AND !$LOE_limit) { // if no data was returned, or the dataset was empty...
		$noDataFound = "<b>"._formulize_DE_NODATAFOUND."</b>";
	} elseif($LOE_limit) {
		$noDataFound = _formulize_DE_LOE_LIMIT_REACHED1 . " <b>" . $LOE_limit . "</b> " . _formulize_DE_LOE_LIMIT_REACHED2 . " <a href=\"\" onclick=\"javascript:forceQ();return false;\">" . _formulize_DE_LOE_LIMIT_REACHED3 . "</a>";
	}
	
	$templateVariables['calculationResults'] = $calculationResults;
	$templateVariables['noDataFound'] = $noDataFound;
		
	formulize_screenLOETemplate($screen, 'closelist', $templateVariables, $settings);
	
	global $entriesThatHaveBeenLockedThisPageLoad;
	if(is_array($entriesThatHaveBeenLockedThisPageLoad) AND count((array) $entriesThatHaveBeenLockedThisPageLoad) > 0) {
		?>
		<script type='text/javascript'>
			jQuery(window).on('unload', function() {
				<?php print formulize_javascriptForRemovingEntryLocks('unload'); ?>
			});
		</script>
		<?php
	}
	
	formulize_benchmark("We're done rendering list of entries!");
}

// this function outputs the html to view an entry, based on the value the user wants clickable, and the pre-determined view link code for the entry
// $linkContents is the clickable text/item. If empty, then the default link will be created, with the standard loe-edit-entry class
// overrideId is an alternative ID that we should construct the link to display instead of the active entry
// overrideScreen is the ID of the screen that the overrideID should be displayed in.  If not specified, then the current screen would be used.
// Note: for the $overrideId, pass in 'proxy' to start a new proxy entry, 'single' to start a new entry without the "add multiple entries" behaviour, 'addnew' to start adding multiple new entries
function viewEntryLink($linkContents="", $overrideId="", $overrideScreen="") {
	$anchorMarkup = $GLOBALS['formulize_viewEntryLinkCode'];
	if(!$anchorMarkup) { return ""; }
	if($overrideId) {
		// swap out the goDetails instruction for the new one based on overrideId and overrideScreen
		$screenParam = $overrideScreen ? intval($overrideScreen) : "";
		$onClickPos = strpos($anchorMarkup, 'onclick');
		$semicolonPos = strpos($anchorMarkup, ';', $onClickPos);
		$anchorMarkup = substr_replace($anchorMarkup, "onclick=\"javascript:goDetails('".$overrideId ."', '". $screenParam ."')", $onClickPos, ($semicolonPos-$onClickPos));
	}
	if(!$linkContents) {
		$anchorMarkup .= " class='loe-edit-entry' alt='"._formulize_DE_VIEWDETAILS."' title='"._formulize_DE_VIEWDETAILS."'>&nbsp;</a>";
	} else {
		$anchorMarkup .= ">".$linkContents."</a>";
	}
	return $anchorMarkup;
}

// this function outputs a clickable button that will lead to the entry when clicked, just the same as viewEntryLink above
// overrideId is an alternative ID that we should construct the link to display instead of the active entry
// overrideScreen is the ID of the screen that the overrideID should be displayed in.  If not specified, then the current screen would be used.
// Note: for the $overrideId, pass in 'proxy' to start a new proxy entry, 'single' to start a new entry without the "add multiple entries" behaviour, 'addnew' to start adding multiple new entries
function viewEntryButton($linkContents, $overrideId="", $overrideScreen="") {
	if($overrideId) {
		$screenParam = $overrideScreen ? "', '".intval($overrideScreen) : "";
	} else {
		$screenParam = "";
		$overrideId = $GLOBALS['formulize_viewEntryId'];
	}
	return "<input type=\"button\" name=\"formulize_veb\" value=\"$linkContents\" onclick=\"javascript:goDetails('" . $overrideId . $screenParam ."');return false;\"></input>";
}



// this function creates the search boxes, filters, date ranges, etc
// $searches is the searches the user has typed in (or defaults)
// $settings is the standard settings array that has lots of metadata about the current situation
// $hiddenQuickSearches is an array of all the elements besides the columns that should have searches created
// $filtersRequired is a an array of all the elements that should have more than just search boxes created, which can be an expensive operation. Passing TRUE will cause all columns to have all search/filter types of UI created
function createQuickSearches($searches, $settings, $hiddenQuickSearches=array(), $filtersRequired=array()) {
	$quickSearches = array();

	$pubfilters = is_array($settings['pubfilters']) ? $settings['pubfilters'] : array();
	$cols = $settings['columns'];

	for($i=0;$i<count((array) $cols);$i++) {
		$search_text = isset($searches[$cols[$i]]) ? strip_tags(htmlspecialchars($searches[$cols[$i]]), ENT_QUOTES) : "";
		$boxid = "";
		$clear_help_javascript = "";
		if($i==0) {
			$boxid = "id=firstbox";
            $clear_help_javascript = "placeholder='"._formulize_DE_SEARCH_HELP."'";
		}
		$quickSearches[$cols[$i]] = packageSearches($cols[$i], $search_text, $filtersRequired, $boxid, $clear_help_javascript);
	}
	
	$hiddenQuickSearchesToMake = array_merge($hiddenQuickSearches, $pubfilters); // include the published filters/searches that the user may have assigned to this screen
	foreach($hiddenQuickSearchesToMake as $thisHQS) {
		$search_text = isset($searches[$thisHQS]) ? strip_tags(htmlspecialchars($searches[$thisHQS], ENT_QUOTES)) : ""; // striping tags after htmlspecialchars is probably unnecessary, but can't hurt(?)
		$quickSearches[$thisHQS] = packageSearches($thisHQS, $search_text, $filtersRequired);
	}
		
	return $quickSearches;
}

// go make all the necessary searches/filters for a given element
function packageSearches($handle, $search_text, $filtersRequired=true, $boxid="", $clear_help_javascript="") {
	$quickSearches = array();
	$quickSearches['search'] = "<input type=text $boxid name='search_$handle' value=\"$search_text\" $clear_help_javascript onchange=\"javascript:window.document.controls.ventry.value = '';\"></input>\n";
	if($filtersRequired === true OR (is_array($filtersRequired) AND in_array($handle, $filtersRequired))) {
		$quickSearches['filter'] = formulize_buildQSFilter($handle, $search_text);
		$quickSearches['dateRange'] = formulize_buildDateRangeFilter($handle, $search_text);
		$quickSearches['multiFilter'] = formulize_buildQSFilterMulti($handle, $search_text);
	}
	return $quickSearches;
}

// create a multi filter
function formulize_buildQSFilterMulti($handle, $search_text) {
	return formulize_buildQSFilter($handle, $search_text, true);
}

// THIS FUNCTION CREATES THE QUICKFILTER BOXES
// multi returns a checkbox set, not dropdown
function formulize_buildQSFilter($handle, $search_text, $multi=false) {

    if(substr($search_text, 0, 1) == "{" AND substr($search_text, -1) == "}") {
        $requestKeyToUse = substr($search_text,1,-1);
        $filterValue = convertVariableSearchToLiteral($search_text, $requestKeyToUse); // returns updated value, or false to kill value, or true to do nothing
        if(!is_bool($filterValue)) {
            $search_text = $filterValue;
        } elseif($filterValue === false) {
            $search_text = '';
        }
    }
    formulize_benchmark("start of building filter");
    $elementMetaData = formulize_getElementMetaData($handle, true); // true means this is a handle
    $id = $elementMetaData['ele_id'];
    if($elementMetaData['ele_type']=="select" OR $elementMetaData['ele_type']=="radio" OR $elementMetaData['ele_type']=="checkbox") {
      $qsfparts = explode("_", $search_text);
      $search_term = strstr($search_text, "_") ? $qsfparts[1] : $search_text;
      if(substr($search_term, 0, 1)=="!" AND substr($search_term, -1) == "!") {
        $search_term = substr($search_term, 1, -1); // cut off any hidden filter values that might be present
      }
      $filterHTML = buildFilter("search_".$handle, $id, _formulize_QSF_DefaultText, $name="{listofentries}", $search_term, false, 0, 0, false, $multi);
      return $filterHTML;
    }
    return "";
}

// THIS FUNCTION CREATES THE HTML FOR A DATE RANGE FILTER
function formulize_buildDateRangeFilter($handle, $search_text) {
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $elementObject = false;
    if($handle == 'creation_datetime' OR $handle == 'mod_datetime' OR $elementObject = $element_handler->get($handle)) {
        $typeInfo = $elementObject ? $elementObject->getDataTypeInformation() : true;
        if($typeInfo == true OR $typeInfo['dataType'] == 'date') {
            $startText = "";
            $endText = "";
            // split any search_text into start and end values
            if(strstr($search_text, "//")) {
                $startEnd = explode("//",$search_text);
                $startText = isset($startEnd[0]) ? strtotime(parseUserAndToday(substr(htmlspecialchars_decode($startEnd[0]), 2))) : "";
                $endText = isset($startEnd[1]) ? strtotime(parseUserAndToday(substr(htmlspecialchars_decode($startEnd[1]), 2))) : "";
            }
            include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";
            $startDateElement = new XoopsFormTextDateSelect ('', 'formulize_daterange_sta_'.$handle, 15, $startText);
            $endDateElement = new XoopsFormTextDateSelect ('', 'formulize_daterange_end_'.$handle, 15, $endText);
            
            static $js;
            if($js) { // only need to include this code once!
                $js = "";
            } else {
                $js = "<script type='text/javascript'>
                if (typeof jQuery == 'undefined') {
                        var head = document.getElementsByTagName('head')[0];
                        script = document.createElement('script');
                        script.id = 'jQuery';
                        script.type = 'text/javascript';
                        script.src = '".XOOPS_URL."/modules/formulize/libraries/jquery/jquery-1.4.2.min.js';
                        head.appendChild(script);
                }
                $().click(function() {
                    $('.formulize_daterange').change();
                });
                $(\"[id^='formulize_daterange_sta_'],[id^='formulize_daterange_end_']\").change(function() {
                    var id = new String($(this).attr('id'));
                    var handle = id.substr(24);
                    var start = $('#formulize_daterange_sta_'+handle).val();
                    var end = $('#formulize_daterange_end_'+handle).val();
                    $('#formulize_hidden_daterange_'+handle).val('>='+start+'//'+'<='+end);
                    $('#formulize_daterange_button_'+handle).show(200);
                });
                </script>";
            }
            return '<div>'._formulize_FROM.' <div style="display: flex;">'.$startDateElement->render(). "</div><br>"._formulize_TO . " <div style='display: flex;'>" . $endDateElement->render() . "</div><br>\n<input type=button style='display: none;' id='formulize_daterange_button_".$handle."' class='formulize-small-button' name=qdrGoButton value='" . _formulize_SUBMITTEXT . "' onclick=\"javascript:showLoading();\"></input>\n<input type='hidden' id='formulize_hidden_daterange_".$handle."' name='search_".$handle."' value='".$search_text."' ></input></div>\n$js";
        } 
    }
    return "";
}

// THIS FUNCTION RETURNS THE ELEMENT HANDLE AND FORM ALIAS IN THE CURRENT GETDATA QUERY, WHEN GIVEN THE ELEMENT ID NUMBER
function getCalcHandleAndFidAlias($id, $fid) {
  if($id == "uid") { $id = "creation_uid"; }
  if($id == "proxyid") { $id = "mod_uid"; }
  if($id == "creation_date") { $id = "mod_datetime"; }
  if($id == "mod_date") { $id = "mod_datetime"; }
  if($id == "creation_uid" OR $id == "mod_uid" OR $id == "mod_datetime" OR $id == "creation_datetime") {
	return array(0=>$id, 1=>"main", 2=>$fid);
  }
  if($id == "creator_email") {
	return array(0=>"email", 1=>"usertable", 2=>"xoopsusertable"); // special handlefid so we can pickup e-mail field correctly, this flag is used to construct the allowed/excluded statements correctly.
  }
  $elementMetaData = formulize_getElementMetaData($id, false);
  $handle = $elementMetaData['ele_handle'];
  $handleFid = $elementMetaData['id_form'];
  if($handleFid == $fid) {
	   $handleFidAlias = "main";
  } else {
	   $handleFidAlias = array_keys($GLOBALS['formulize_linkformidsForCalcs'], $handleFid); // position of this form in the linking relationships is important for identifying which form alias to use
	   $handleFidAlias = "f".$handleFidAlias[0];
  }
  return array(0=>$handle, 1=>$handleFidAlias, 2=>$handleFid);
}


//THIS FUNCTION PERFORMS THE REQUESTED CALCULATIONS, AND RETURNS AN html FORMATTED CHUNK FOR DISPLAY ON THE SCREEN
// note: cols is elementids!!
function performCalcs($cols, $calcs, $blanks, $grouping, $frid, $fid)  {

  // determine which fields have which calculations and exculsion options
  // calculations that are simple, with the same exclusion options, can be done in the same query
  // percentage distribution is not simple, nor is percentile calculation (part of averages), nor is mode (part of averages), but all others are simple and can be done in one query

  global $xoopsDB;
  $masterResults = array();
  $masterResultsRaw = array(); // not all avg values, and no per values are stored here, because the formatting of them is very complex and we haven't needed them raw yet
  $blankSettings = array();
  $groupingSettings = array();
  $groupingValues = array();
  $baseQuery = $GLOBALS['formulize_queryForCalcs'];
  $oneSideBaseQuery = $GLOBALS['formulize_queryForOneSideCalcs'];

  if($frid) {
	$framework_handler =& xoops_getmodulehandler('frameworks', 'formulize');
	$frameworkObject = $framework_handler->get($frid);
  }

  $form_handler = xoops_getmodulehandler('forms', 'formulize');
  $element_handler = xoops_getmodulehandler('elements', 'formulize');

  for($i=0;$i<count((array) $cols);$i++) {
	// convert to element handle from element id
	list($handle, $fidAlias, $handleFid) = getCalcHandleAndFidAlias($cols[$i], $fid); // returns ELEMENT handles for use in query
	$handleFormObject = $form_handler->get($handleFid);

	// get the exclude and grouping values for this column
	$excludes = explode(",", $blanks[$i]);
	$groupings = explode(",", $grouping[$i]);

	// need to properly handle "other" values

	// build the select statement
	foreach(explode(",", $calcs[$i]) as $cid=>$calc) {

	  // set the base query to use:
	  // if this calculation is being done on a field that is on the other none main form in a relationship, then we need to use a special version of the baseQuery that includes the join
	  if($frid) {
        list($side, $onMainForm) = $frameworkObject->whatSideIsHandleOn($cols[$i], $fid);
		if($side == "one" AND $onMainForm) {
		  $thisBaseQuery = $oneSideBaseQuery; // isolates the one side form, which we need to do in this case, see comment in extract.php, definite issues if calcs are done on "many" side of a one-to-many relationship?
		} else {
		  $thisBaseQuery = $baseQuery;
		}
	  } else {
		$thisBaseQuery = $baseQuery;
	  }

	// figure out if the field is encrypted, and setup the calcElement accordingly
    $calcElement = "$fidAlias.`$handle`";
	if($calcElementObject = $element_handler->get($handle)) {
        $calcElementMetaData = formulize_getElementMetaData($handle, true);
        if(isset($calcElementMetaData['ele_encrypt']) AND $calcElementMetaData['ele_encrypt']) {
        	$calcElement = "AES_DECRYPT($fidAlias.`$handle`, '".getAESPassword()."')";
        } 
	}

	// figure out the group by clause (grouping is expressed as element ids right now)
	//$groupings[$cid] .= "!@^%*17461!@^%*9402";
	$theseGroupings = explode("!@^%*", $groupings[$cid]);
	$groupByClause = "";
	$outerGroupingSelect = "";
	$innerGroupingSelect = "";
	$outerGroupingSelectAvgCount = "";
	$innerGroupingSelectAvgCount = "";
	$start = true;
	$allGroupings = array();
	foreach($theseGroupings as $thisGrouping) {
	  if($thisGrouping == "none" OR $thisGrouping == "") { continue; }
	  list($ghandle, $galias) = getCalcHandleAndFidAlias($thisGrouping, $fid);
	  // need to add awareness of encryption in here
	  if($start) {
		$start = false;
	  } else {
		$groupByClause .= ", ";
	  }
	  $allGroupings[] = "$galias$ghandle";
	  $groupByClause .= "$galias$ghandle";
	  if($ghandle == "creation_uid" OR $ghandle == "mod_uid") {
		  $innerGroupingSelect .= ", (SELECT CASE usertable.name WHEN '' THEN usertable.uname ELSE usertable.name END FROM ". DBPRE."users as usertable WHERE usertable.uid = ".$galias.".".$ghandle.") as inner$galias$ghandle";
		  $innerGroupingSelectAvgCount .= ", (SELECT CASE usertable.name WHEN '' THEN usertable.uname ELSE usertable.name END FROM ". DBPRE."users as usertable WHERE usertable.uid = ".$galias.".".$ghandle.") as inner$galias$ghandle";
	  } else {
		  $innerGroupingSelect .= ", $galias.`$ghandle` as inner$galias$ghandle";
		  $innerGroupingSelectAvgCount .= ", $galias.`$ghandle` as inner$galias$ghandle";
	  }
	  $outerGroupingSelect .= ", inner$galias$ghandle as $galias$ghandle";
	  $outerGroupingSelectAvgCount .= ", inner$galias$ghandle as $galias$ghandle";
	}

	// figure out what to ask for for this calculation
	switch($calc) {
	  case "sum":
		$select = "SELECT sum(tempElement) as $fidAlias$handle $outerGroupingSelect FROM (SELECT distinct($fidAlias.`entry_id`), $calcElement as tempElement $innerGroupingSelect";
		break;
	  case "min":
		$select = "SELECT min(tempElement) as $fidAlias$handle $outerGroupingSelect FROM (SELECT distinct($fidAlias.`entry_id`), $calcElement as tempElement $innerGroupingSelect";
		break;
	  case "max":
		$select = "SELECT max(tempElement) as $fidAlias$handle $outerGroupingSelect FROM (SELECT distinct($fidAlias.`entry_id`), $calcElement as tempElement $innerGroupingSelect";
		break;
	  case "count":
		$select = "SELECT count(tempElement) as count$fidAlias$handle, count(distinct(tempElement)) as distinct$fidAlias$handle $outerGroupingSelect FROM (SELECT distinct($fidAlias.`entry_id`), $calcElement as tempElement $innerGroupingSelect";
		break;
	  case "avg":
		$select = "SELECT avg(tempElement) as avg$fidAlias$handle, std(tempElement) as std$fidAlias$handle $outerGroupingSelect FROM (SELECT distinct($fidAlias.`entry_id`), $calcElement as tempElement $innerGroupingSelect";
		$selectAvgCount = "SELECT tempElement as $fidAlias$handle, count(tempElement) as avgcount$fidAlias$handle $outerGroupingSelectAvgCount FROM (SELECT distinct($fidAlias.`entry_id`), $calcElement as tempElement $innerGroupingSelectAvgCount";
		break;
	  case "per":
		$select = "SELECT tempElement as $fidAlias$handle, count(tempElement) as percount$fidAlias$handle $outerGroupingSelect, entry_id FROM (SELECT distinct($fidAlias.`entry_id`) as entry_id, $calcElement as tempElement $innerGroupingSelect";
		include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php"; // need a function here later on
		break;
	  default:

		break;
	}

	// figure out the special where clause conditions that need to be added for this calculation
	list($allowedValues, $excludedValues) = calcParseBlanksSetting($excludes[$cid]);

	$numericDataTypes = array('decimal'=>0, 'float'=>0, 'numeric'=>0, 'double'=>0, 'int'=>0, 'mediumint'=>0, 'tinyint'=>0, 'bigint'=>0, 'smallint'=>0, 'integer'=>0);
    if($calcElementObject) {
        $dataTypeInfo = $calcElementObject->getDataTypeInformation();    
    } else {
        $dataHandler = new formulizeDataHandler($fid);
        if(isset($dataHandler->metadataFieldTypes[$handle])) {
            $dataTypeInfo = array('dataType'=>$dataHandler->metadataFieldTypes[$handle]);
        } else {
            print "Error: could not determine datatype for element '$handle'<br>";
        }
    }

	$allowedWhere = "";
	if(count((array) $allowedValues)>0) {
	  $start = true;
	  foreach($allowedValues as $value) {
		if($start) {
		  $allowedWhereConjunction = " AND (";
		  $start = false;
		} else {
		  $allowedWhereConjunction = " OR ";
		}
		if($value === "{BLANK}") {
		  $allowedWhere .= " $allowedWhereConjunction ($calcElement='' OR $calcElement IS NULL)";
		} else {
		  $value = parseUserAndToday($value); // translate {USER} and {TODAY} into literals
		  if(is_numeric($value) AND isset($numericDataTypes[$dataTypeInfo['dataType']])) {
			$allowedWhere .= " $allowedWhereConjunction $calcElement=".formulize_db_escape($value);
		  } else {
			$allowedWhere .= " $allowedWhereConjunction $calcElement='".formulize_db_escape($value)."'";
		  }
		}
	  }
	  if($allowedWhere) {
		$allowedWhere .= ")";
		// replace any LEFT JOIN on this form in the query with an INNER JOIN, since there are now search criteria for this form
		if($handleFid =="xoopsusertable") {
		  $replacementTable = DBPRE . "users";
		} else {
		  $replacementTable = DBPRE . "formulize_".$handleFormObject->getVar('form_handle');
		}
		$thisBaseQuery = str_replace("LEFT JOIN " . $replacementTable. " AS", "INNER JOIN " . $replacementTable. " AS", $thisBaseQuery);
	  }
	}

	$excludedWhere = "";
	if(count((array) $excludedValues)>0) {
	  $start = true;
	  foreach($excludedValues as $value) {
		if($start) {
		  $excludedWhereConjunction = " AND (";
		  $start = false;
		} else {
		  $excludedWhereConjunction = " AND ";
		}
		if($value === "{BLANK}") {
		  $excludedWhere .= " $excludedWhereConjunction ($calcElement!='' AND $calcElement IS NOT NULL)";
		} else {
		  $value = parseUserAndToday($value); // translate {USER} and {TODAY} into literals
		  if(is_numeric($value) AND isset($numericDataTypes[$dataTypeInfo['dataType']])) {
			$excludedWhere .= " $excludedWhereConjunction $calcElement!=".formulize_db_escape($value);
		  } else {
			$excludedWhere .= " $excludedWhereConjunction $calcElement!='".formulize_db_escape($value)."'";
		  }
		}
	  }
	  if($excludedWhere) {
		$excludedWhere .= ")";
		if($handleFid =="xoopsusertable") {
		  $replacementTable = DBPRE . "users";
		} else {
		  $replacementTable = DBPRE . "formulize_".$handleFormObject->getVar('form_handle');
		}
		// replace any LEFT JOIN on this form in the query with an INNER JOIN, since there are now search criteria for this form
		$thisBaseQuery = str_replace("LEFT JOIN " . $replacementTable. " AS", "INNER JOIN " . $replacementTable. " AS", $thisBaseQuery);
	  }
	}

	// setup group by clause and order by clause
	$orderByClause = "";
	$groupByClauseMode = "";
	if($groupByClause) {
	  if($calc == "avg") {
		$groupByClauseMode = " GROUP BY $fidAlias$handle, ".$groupByClause;
		$groupByClause = " GROUP BY ".$groupByClause;
	  } elseif($calc == "per") {
		$orderByClause = " ORDER BY $groupByClause, percount$fidAlias$handle DESC";
		$groupByClause = " GROUP BY $fidAlias$handle, ".$groupByClause;
	  } else {
		$groupByClause = " GROUP BY ".$groupByClause;
	  }
	} elseif($calc == "avg") {
	  $groupByClauseMode = " GROUP BY $fidAlias$handle";
	} elseif($calc == "per") {
	  $groupByClause = " GROUP BY $fidAlias$handle, entry_id";
	  $orderByClause = " ORDER BY percount$fidAlias$handle DESC";
	}

	// do the query
	$calcResult = array();
	$calcResultSQL = "$select $thisBaseQuery $allowedWhere $excludedWhere) as tempQuery $groupByClause $orderByClause ";
	global $xoopsUser;
	/*if($xoopsUser->getVar('uid') == 1) {
	  print "$calcResultSQL<br><br>";
	}*/
	$calcResultRes = $xoopsDB->query($calcResultSQL);
	while($calcResultArray = $xoopsDB->fetchArray($calcResultRes)) {
	  $calcResult[] = $calcResultArray;
	}

	// package up the result into the results array that gets passed to the output function that dumps data to screen (suitable for templating at a later date)
	$blankSettings[$cols[$i]][$calc] = $excludes[$cid];
	$groupingSettings[$cols[$i]][$calc] = $groupings[$cid];
	$groupingValues[$cols[$i]][$calc] = array(); // this is an array to store

	if($calc == "per") {
	  $groupCounts = array();
	  $indivCounts = array();
	  $perindexer = -1;
	}

	foreach($calcResult as $calcId=>$thisResult) { // this needs to be moved inside or lower down in order to support two level grouping?

		switch($calc) {
		  case "sum":
		foreach($theseGroupings as $gid=>$thisGrouping) {
		  if($thisGrouping != "none" AND $thisGrouping != "") {
			list($ghandle, $galias) = getCalcHandleAndFidAlias($thisGrouping, $fid);
			$groupingValues[$cols[$i]][$calc][$calcId][] = convertRawValuesToRealValues($thisResult["$galias$ghandle"], $ghandle, true);
		  }
		}
		$masterResultsRaw[$cols[$i]][$calc][$calcId]['sum'] = $thisResult["$fidAlias$handle"];
		$masterResults[$cols[$i]][$calc][$calcId] = _formulize_DE_CALC_SUM . ": ".formulize_numberFormat($thisResult["$fidAlias$handle"], $handle);
		break;
		  case "min":
		foreach($theseGroupings as $gid=>$thisGrouping) {
		  if($thisGrouping != "none" AND $thisGrouping != "") {
			list($ghandle, $galias) = getCalcHandleAndFidAlias($thisGrouping, $fid);

			$groupingValues[$cols[$i]][$calc][$calcId][] = convertRawValuesToRealValues($thisResult["$galias$ghandle"], $handle, true);
		  }
		}
		$masterResults[$cols[$i]][$calc][$calcId] = _formulize_DE_CALC_MIN . ": ".formulize_numberFormat($thisResult["$fidAlias$handle"], $handle);
        $masterResultsRaw[$cols[$i]][$calc][$calcId]['min'] = $thisResult["$fidAlias$handle"];
		break;
		  case "max":
		foreach($theseGroupings as $gid=>$thisGrouping) {
		  if($thisGrouping != "none" AND $thisGrouping != "") {
			list($ghandle, $galias) = getCalcHandleAndFidAlias($thisGrouping, $fid);
			$groupingValues[$cols[$i]][$calc][$calcId][] = convertRawValuesToRealValues($thisResult["$galias$ghandle"], $handle, true);
		  }
		}
		$masterResults[$cols[$i]][$calc][$calcId] = _formulize_DE_CALC_MAX . ": ".formulize_numberFormat($thisResult["$fidAlias$handle"], $handle);
        $masterResultsRaw[$cols[$i]][$calc][$calcId]['max'] = $thisResult["$fidAlias$handle"];
		break;
		  case "count":
		foreach($theseGroupings as $gid=>$thisGrouping) {
		  if($thisGrouping != "none" AND $thisGrouping != "") {
			list($ghandle, $galias) = getCalcHandleAndFidAlias($thisGrouping, $fid);
			$groupingValues[$cols[$i]][$calc][$calcId][] = convertRawValuesToRealValues($thisResult["$galias$ghandle"], $ghandle, true);
		  }
		}
        $masterResultsRaw[$cols[$i]][$calc][$calcId]['count'] = $thisResult["count$fidAlias$handle"];
        $masterResultsRaw[$cols[$i]][$calc][$calcId]['countunique'] = $thisResult["distinct$fidAlias$handle"];
		$masterResults[$cols[$i]][$calc][$calcId] = _formulize_DE_CALC_NUMENTRIES . ": ".$thisResult["count$fidAlias$handle"]."<br>"._formulize_DE_CALC_NUMUNIQUE . ": " .$thisResult["distinct$fidAlias$handle"];
		break;
		  case "avg":
		foreach($theseGroupings as $gid=>$thisGrouping) {
		  if($thisGrouping != "none" AND $thisGrouping != "") {
			list($ghandle, $galias) = getCalcHandleAndFidAlias($thisGrouping, $fid);
			$groupingValues[$cols[$i]][$calc][$calcId][] = convertRawValuesToRealValues($thisResult["$galias$ghandle"], $ghandle, true);
		  }
		}
		$masterResults[$cols[$i]][$calc][$calcId] =  _formulize_DE_CALC_MEAN . ": ".formulize_numberFormat($thisResult["avg$fidAlias$handle"], $handle)."<br>" . _formulize_DE_CALC_STD . ": ".formulize_numberFormat($thisResult["std$fidAlias$handle"], $handle)."<br><br>";
        $masterResultsRaw[$cols[$i]][$calc][$calcId]['avg'] = $thisResult["avg$fidAlias$handle"];
        $masterResultsRaw[$cols[$i]][$calc][$calcId]['avgstd'] = $thisResult["std$fidAlias$handle"];
		break;
		  case "per":
		$groupingWhere = array();
		$groupingValuesFound = array();
		foreach($theseGroupings as $gid=>$thisGrouping) {
		  if($thisGrouping != "none" AND $thisGrouping != "") {
			list($ghandle, $galias) = getCalcHandleAndFidAlias($thisGrouping, $fid);
			//print $thisResult["$galias$ghandle"] . "<br>";
			if($thisResult["$galias$ghandle"] == "") {
			  $groupingWhere[] = "($galias.`$ghandle` = '".$thisResult["$galias$ghandle"]."' OR $galias.`$ghandle` IS NULL)";
			  $groupingValuesFound[] = _formulize_BLANK_KEYWORD;
			} else {
			  $groupingWhere[] = "$galias.`$ghandle` = '".$thisResult["$galias$ghandle"]."'";
			  $groupingValuesFound[] = $thisResult["$galias$ghandle"];
			}
		  }
		}
		if(count((array) $groupingWhere)>0) {
		  $groupingWhere = "AND (".implode(" AND ", $groupingWhere).")";
		} else {
		  $groupingWhere = "";
		}
		if(!isset($groupCounts[$groupingWhere])) { // need to figure out the total count for this grouping setting
		  $perindexer++;
		  $groupingValues[$cols[$i]][$calc][$perindexer] = convertRawValuesToRealValues($groupingValuesFound, $ghandle, true);
		  $countSQL = "SELECT count(tempElement) as count$fidAlias$handle FROM (SELECT distinct($fidAlias.`entry_id`), $fidAlias.`$handle` as tempElement $thisBaseQuery $allowedWhere $excludedWhere $groupingWhere) as tempQuery";
		  //print "$countSQL<br>";
		  $countRes = $xoopsDB->query($countSQL);
		  $countArray = $xoopsDB->fetchArray($countRes);
		  $countValue = $countArray["count$fidAlias$handle"];
		  $indexerToUse = $perindexer;
		  $groupCounts[$groupingWhere]['countValue'] = $countValue;
		  $groupCounts[$groupingWhere]['indexerToUse'] = $indexerToUse;
		  $start = true;
		} else {
		  $indexerToUse = $groupCounts[$groupingWhere]['indexerToUse'];
		  $countValue = $groupCounts[$groupingWhere]['countValue'];
		}
		// need to figure out the individual counts of the constituent parts of this result
		if(strstr($thisResult["$fidAlias$handle"], "*=+*:")) {
		  $rawIndivValues = explode("*=+*:", $thisResult["$fidAlias$handle"]);
		  array_shift($rawIndivValues); // current convention is to have the separator at the beginning of the string, so the exploded array will have a blank value at the beginning
		} elseif($linkedMetaData = formulize_isLinkedSelectBox($cols[$i])) {
            $rawIndivValues = prepValues($thisResult["$fidAlias$handle"], $handle, $thisResult['entry_id']);
		} else {
		  $rawIndivValues = array(0=>$thisResult["$fidAlias$handle"]);
		}
		foreach($rawIndivValues as $thisIndivValue) {
		  $indivCounts[$cols[$i]][$calc][$indexerToUse][trans(calcValuePlusText($thisIndivValue, $handle, $cols[$i], $calc, $indexerToUse))] += $thisResult["percount$fidAlias$handle"]; // add this count to the total count for this particular item
		  $groupCounts[$groupingWhere]['responseCountValue'] += $thisResult["percount$fidAlias$handle"]; // add this count to the total count for all items
		}
		break;
		}

	}

	if($calc=="avg") { // then do some extra stuff for the more complicated calculations
	  // work out the mode...
	  $modeCounts = array();
	  $modeQuery = "$selectAvgCount $thisBaseQuery $allowedWhere $excludedWhere ) as tempQuery $groupByClauseMode ORDER BY ";
	  if(count((array) $allGroupings)>0) {
		$modeQuery .= implode(", ",$allGroupings) . ", ";
	  }
	  $modeQuery .= "avgcount$fidAlias$handle DESC";
	  //print "$modeQuery<br>";
	  $modeRes = $xoopsDB->query($modeQuery);
	  $foundModeValue = array();
	  $modeIndexer = 0;
	  while($modeData = $xoopsDB->fetchArray($modeRes)) {
		$foundValues = "";
		$modeCountsTemp = array();
		foreach($theseGroupings as $gid=>$thisGrouping) {
		  if($thisGrouping != "none" AND $thisGrouping != "") {
		list($ghandle, $galias) = getCalcHandleAndFidAlias($thisGrouping, $fid);
		$foundValues .= $modeData["$galias$ghandle"]."xyz";
		$modeCountsTemp[$modeData["$galias$ghandle"]] = "$galias.`$ghandle`";
		  }
		}
		if(!isset($foundModeValue[$foundValues])) { // this is a new combination
		  $foundModeValue[$foundValues] = true;
		  if($foundValues) {
		$modeCounts[$modeIndexer] = $modeCountsTemp;
		  } else {
		$modeCounts[$modeIndexer]['none'] = 'none';
		  }
		  $masterResults[$cols[$i]][$calc][$modeIndexer] .= "REPLACE WITH MEDIAN"._formulize_DE_CALC_MODE . ": ".formulize_numberFormat($modeData["$fidAlias$handle"], $handle);
		  $modeIndexer++;
		}
	  }
	  // work out the percentiles including median
	  // calculating percentiles logic based on formula described here: http://onlinestatbook.com/chapter1/percentiles.html
	  // modeGrouping is the value that we are grouping by, modeHandle is the field to look for that value in
	  foreach($modeCounts as $thisGid=>$thisModeGrouping) {
		$groupingWhere = "";
		foreach($thisModeGrouping as $modeGrouping=>$modeHandle) {
		  // first we need to get the full count for this group of results
		  // need to convert grouping values into the where clause for the percentile calculations
		  $groupingWhere .= $modeHandle === 'none' ? "" : " AND ($modeHandle = '$modeGrouping')";
		}
		$countSQL = "SELECT count(tempElement) as count$fidAlias$handle FROM (SELECT distinct($fidAlias.`entry_id`), $fidAlias.`$handle` as tempElement $thisBaseQuery $allowedWhere $excludedWhere $groupingWhere) as tempQuery";
		//print "<br>$countSQL<br>";
		$countRes = $xoopsDB->query($countSQL);
		$countArray = $xoopsDB->fetchArray($countRes);
		$countValue = $countArray["count$fidAlias$handle"];
		$per25Limit = floor(($countValue+1)/4);
		$per25Fraction = (($countValue+1)/4)-$per25Limit;
		$per25Limit = $per25Limit-1; // since Limit statements interpret rank orders as starting from 0, must subtract 1
		$per25Size = ($countValue+1) % 4 == 0 ? 1 : 2;
		$per75Limit = floor((($countValue+1)*3)/4);
		$per75Fraction = ((($countValue+1)*3)/4)-$per75Limit;
		$per75Limit = $per75Limit-1; // since Limit statements interpret rank orders as starting from 0, must subtract 1
		$per75Size = $per25Size;
		$per50Limit = floor(($countValue+1)/2);
		$per50Fraction = (($countValue+1)/2)-$per50Limit;
		$per50Limit = $per50Limit-1; // since Limit statements interpret rank orders as starting from 0, must subtract 1
		$per50Size = ($countValue+1) % 2 == 0 ? 1 : 2;
		$per25SQL = "SELECT distinct($fidAlias.`entry_id`), $fidAlias.`$handle` as $fidAlias$handle $thisBaseQuery $allowedWhere $excludedWhere $groupingWhere ORDER BY $fidAlias$handle LIMIT $per25Limit,$per25Size";
		//print "$per25SQL<Br><Br>";
		$per75SQL = "SELECT distinct($fidAlias.`entry_id`), $fidAlias.`$handle` as $fidAlias$handle $thisBaseQuery $allowedWhere $excludedWhere $groupingWhere ORDER BY $fidAlias$handle LIMIT $per75Limit,$per75Size";
		//print "$per75SQL<Br><Br>";
		$per50SQL = "SELECT distinct($fidAlias.`entry_id`), $fidAlias.`$handle` as $fidAlias$handle $thisBaseQuery $allowedWhere $excludedWhere $groupingWhere ORDER BY $fidAlias$handle LIMIT $per50Limit,$per50Size";
		//print "$per50SQL<Br><Br>";
		$per25Res = $xoopsDB->query($per25SQL);
		$per75Res = $xoopsDB->query($per75SQL);
		$per50Res = $xoopsDB->query($per50SQL);
		$allPerResults = _formulize_DE_CALC_MEDIAN25.": ";
		$per25Results = "";
		$per75Results = "";
		$per50Results = "";
		$start = true;
		$perPair = array();
		while($per25Array = $xoopsDB->fetchArray($per25Res)) {
		$perPair[] = $per25Array["$fidAlias$handle"];
		if(!$start) { $per25Results .= ", "; }
		$start = false;
		$per25Results .= formulize_numberFormat($per25Array["$fidAlias$handle"], $handle);
		}
		if(count((array) $perPair) < 2) {
		  $allPerResults .= $per25Results;
		} elseif($perPair[0] != $perPair[1]) { // we have multiple values at the median/percentile point, so figure out the weighted average
		  $allPerResults .= formulize_numberFormat(($per25Fraction * ($perPair[1]-$perPair[0])) + $perPair[0], $handle, "", 2) . " ($per25Results)";
		} else { // multiple, equal values at median/percentile point
		  $allPerResults .= formulize_numberFormat($perPair[0], $handle);
		}
		$allPerResults .= "<br>";
		$allPerResults .= _formulize_DE_CALC_MEDIAN.": ";
		$start = true;
		$perPair = array();
		while($per50Array = $xoopsDB->fetchArray($per50Res)) {
		$perPair[] = $per50Array["$fidAlias$handle"];
		if(!$start) { $per50Results .= ", "; }
		$start = false;
		$per50Results .= formulize_numberFormat($per50Array["$fidAlias$handle"], $handle);
		}
		if(count((array) $perPair) < 2) {
		  $allPerResults .= $per50Results;
		} elseif($perPair[0] != $perPair[1]) { // we have multiple values at the median/percentile point, so figure out the average
		  $allPerResults .= formulize_numberFormat(($per50Fraction * ($perPair[1]-$perPair[0])) + $perPair[0], $handle, "", 2) . " ($per50Results)";
		} else { // multiple, equal values at median/percentile point
		  $allPerResults .= formulize_numberFormat($perPair[0], $handle);
		}
		$allPerResults .= "<br>";
		$allPerResults .= _formulize_DE_CALC_MEDIAN75.": ";
		$start = true;
		$perPair = array();
		while($per75Array = $xoopsDB->fetchArray($per75Res)) {
		$perPair[] = $per75Array["$fidAlias$handle"];
		if(!$start) { $per75Results .= ", "; }
		$start = false;
		$per75Results .= formulize_numberFormat($per75Array["$fidAlias$handle"], $handle);
		}
		if(count((array) $perPair) < 2) {
		  $allPerResults .= $per75Results;
		} elseif($perPair[0] != $perPair[1]) { // we have multiple values at the median/percential point, so figure out the average
		  $allPerResults .= formulize_numberFormat(($per75Fraction * ($perPair[1]-$perPair[0])) + $perPair[0], $handle, "", 2) . " ($per75Results)";
		} else { // multiple, equal values at median/percentile point
		  $allPerResults .= formulize_numberFormat($perPair[0], $handle);
		}
		$allPerResults .= "<br><br>";
		//print $medianResults."<br><br>";
		$masterResults[$cols[$i]][$calc][$thisGid] = str_replace("REPLACE WITH MEDIAN", $allPerResults, $masterResults[$cols[$i]][$calc][$thisGid]);

	  }
	} elseif($calc=="per") { // output the percentage breakdowns, since we'll be done counting everything we need now
	  foreach($groupCounts as $groupCountData) {
		$start = true;
		if($groupCountData['countValue'] == $groupCountData['responseCountValue'] AND $start) {
		  $typeout = "<table cellpadding=3>\n<tr><td style=\"vertical-align: top; padding-right: 1em;\"><u>" . _formulize_DE_PER_ITEM . "</u></td><td style=\"vertical-align: top; padding-right: 1em;\"><u>" . _formulize_DE_PER_COUNT . "</u></td><td style=\"vertical-align: top; padding-right: 1em; padding-right: 1em;\"><u>" . _formulize_DE_PER_PERCENT . "</u></td></tr>\n";
		} else {
		  $typeout = "<table cellpadding=3>\n<tr><td style=\"vertical-align: top; padding-right: 1em;\"><u>" . _formulize_DE_PER_ITEM . "</u></td><td style=\"vertical-align: top; padding-right: 1em;\"><u>" . _formulize_DE_PER_COUNT . "</u></td><td style=\"vertical-align: top; padding-right: 1em; padding-right: 1em;\"><u>" . _formulize_DE_PER_PERCENTRESPONSES . "</u></td><td style=\"vertical-align: top; padding-right: 1em;\"><u>" . _formulize_DE_PER_PERCENTENTRIES . "</u></td></tr>\n";
		}
		// replace the indivText with a corresponding name, if we have any on file
		$nameReplacementMap = array();
		if(isset($GLOBALS['formulize_fullNameUserNameCalculationReplacementList'][$cols[$i]][$calc][$groupCountData['indexerToUse']]) AND $start) {
		  global $xoopsDB;
		  $nameType = $GLOBALS['formulize_fullNameUserNameCalculationReplacementList'][$cols[$i]][$calc][$groupCountData['indexerToUse']]['nametype'];
		  $userIDs = $GLOBALS['formulize_fullNameUserNameCalculationReplacementList'][$cols[$i]][$calc][$groupCountData['indexerToUse']]['values'];
		  // get a list of all the names and uids that we're dealing with
		  $nameReplacementSQL = "SELECT $nameType, uid FROM ".$xoopsDB->prefix("users") . " WHERE uid IN (". implode(", ", $userIDs). ")";
		  $nameReplacementRes = $xoopsDB->query($nameReplacementSQL);
		  while($nameReplacementArray = $xoopsDB->fetchArray($nameReplacementRes)) {
		// map the uid and name values we found, so we can sub them in lower down when needed
		$nameReplacementMap[$nameReplacementArray['uid']] = $nameReplacementArray[$nameType];
		  }
		}
		$start = false;
		arsort($indivCounts[$cols[$i]][$calc][$groupCountData['indexerToUse']]);
		foreach($indivCounts[$cols[$i]][$calc][$groupCountData['indexerToUse']] as $indivText=>$indivTotal) {
		  if(count((array) $nameReplacementMap)>0) { $indivText = $nameReplacementMap[$indivText]; } // swap in a name for this user, if applicable
		  if($groupCountData['countValue'] == $groupCountData['responseCountValue']) {
		$typeout .= "<tr><td style=\"vertical-align: top;\">$indivText</td><td style=\"vertical-align: top;\">$indivTotal</td><td style=\"vertical-align: top;\">".round(($indivTotal/$groupCountData['countValue'])*100,2)."%</td></tr>\n";
		  } else {
		$typeout .= "<tr><td style=\"vertical-align: top;\">$indivText</td><td style=\"vertical-align: top;\">$indivTotal</td><td style=\"vertical-align: top;\">".round(($indivTotal/$groupCountData['responseCountValue'])*100,2)."%</td><td style=\"vertical-align: top;\">".round(($indivTotal/$groupCountData['countValue'])*100,2)."%</td></tr>\n";
		  }
		}
		if($groupCountData['countValue'] == $groupCountData['responseCountValue']) {
		  $typeout .= "<tr><td style=\"vertical-align: top;\"><hr>" . _formulize_DE_PER_TOTAL . "</td><td style=\"vertical-align: top;\"><hr>".$groupCountData['countValue']."</td><td style=\"vertical-align: top;\"><hr>100%</td></tr>\n</table>\n";
		} else {
		  $typeout .= "<tr><td style=\"vertical-align: top;\"><hr>" . _formulize_DE_PER_TOTAL . "</td><td style=\"vertical-align: top;\"><hr>".$groupCountData['responseCountValue']. " " ._formulize_DE_PER_TOTALRESPONSES."<br>".$groupCountData['countValue']. " " ._formulize_DE_PER_TOTALENTRIES."</td><td style=\"vertical-align: top;\"><hr>100%</td><td style=\"vertical-align: top;\"><hr>" . round($groupCountData['responseCountValue']/$groupCountData['countValue'], 2) . " " . _formulize_DE_PER_RESPONSESPERENTRY . "</td></tr>\n</table>";
		}
		$masterResults[$cols[$i]][$calc][$groupCountData['indexerToUse']] = $typeout;
	  }
	}
	}
  }
  /*print "<br><br>";
  print_r($masterResults);
  print "<br><br>";
  print_r($groupingValues);*/
	$to_return[0] = $masterResults;
	$to_return[1] = $blankSettings;
	$to_return[2] = $groupingSettings;
	$to_return[3] = $groupingValues;
	$to_return[4] = $masterResultsRaw;
	return $to_return;
}

// this function converts raw values from the database to their actual values users should see
// currently handles linked selectboxes and multiple values fields (listboxes and checkboxes)
// this could be made into a replacement for the prepvalues function in the extract.php file that does the same kind of thing when preparing a dataset
// returnFlat is a flag to cause multiple values to be returned as comma separated strings
// value can be an array, and if so, an array will be passed
function convertRawValuesToRealValues($value, $handle, $returnFlat=false) {
    
  if(!is_array($value)) {
		$value = array(0=>$value);
		$arrayWasPassedIn = false;
	} else {
		$arrayWasPassedIn = true;
	}
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
	$thisElement = $element_handler->get($handle);
	if(!is_object($thisElement)) {
		if($arrayWasPassedIn) {
			return $value;
		} else {
			return $value[0];
		}
	}
	$ele_value = $thisElement->getVar('ele_value');
	$isLinkedSelectBox = false;
	$isNamesList = false;
	if(is_array($ele_value)) {
		if(isset($ele_value[2])) {
			if(is_string($ele_value[2]) AND strstr($ele_value[2], "#*=:*")) {
				$isLinkedSelectBox = true;
				$linkedMetaData = formulize_isLinkedSelectBox($thisElement->getVar('ele_id'));
			} elseif(isset($ele_value[2]["{FULLNAMES}"]) OR isset($ele_value[2]["{USERNAMES}"]))  {
				$isNamesList = isset($ele_value[2]["{FULLNAMES}"]) ? 'name' : 'uname';
			}
		}
	}
	$allRealValuesNames = array();
	foreach($value as $thisValue) {
		if($isLinkedSelectBox) {
			// convert the pointers for the linked selectbox values, to their source values
			$sourceMeta = explode("#*=:*", $linkedMetaData[2]);
			$data_handler = new formulizeDataHandler($sourceMeta[0]);
			$realValues = $data_handler->findAllValuesForEntries($sourceMeta[1], explode(",",trim($thisValue, ",")), true); // trim opening and closing commas and split by comma into an array, final true causes values to be prepped for display to users
			// findAllValuesForEntries method returns an array, so convert to a single value
			if(is_array($realValues) AND $returnFlat) {
				$realValues = implode(", ", $realValues);
			}
			$allRealValues[] = $realValues;
		} elseif(strstr($thisValue, "*=+*:")) {
			$allRealValues[] = str_replace("*=+*:", ", ", ltrim($thisValue, "*=+*:")); // replace the separator with commas between values
		} elseif($isNamesList) {
			$allRealValuesNames[] = $thisValue;
		} else {
			$allRealValues[] = $thisValue;
		}
	}
	if(count((array) $allRealValuesNames) > 0) {
		// convert all uids found into names with only one query
		$user_handler = xoops_gethandler('user');
		$criteria = new CriteriaCompo();
		foreach($allRealValuesNames as $thisUid) {
			if(is_numeric($thisUid)) {
				$criteria->add(new Criteria('uid', $thisUid), 'OR');
			}
		}
		$users = $user_handler->getObjects($criteria, true); // true causes key of returned array to be uids
		foreach($allRealValuesNames as $thisUid) {
			if(is_numeric($thisUid) AND isset($users[$thisUid])) {
                if(!$nameToUse = $users[$thisUid]->getVar($isNamesList)) {
                    $nameToUse = $users[$thisUid]->getVar('uname');
                }
                $allRealValues[] = $nameToUse;
			} else {
				$allRealValues[] = _formulize_BLANK_KEYWORD;
			}
		}
	}
	if($arrayWasPassedIn) {
		return $allRealValues;
	} else {
		return $allRealValues[0];
	}


}

// THIS FUNCTION READS THE BLANKS SETTING AND RETURNS A LIST OF VALUES THAT ARE ALLOWED AND A LIST OF VALUES THAT ARE NOT ALLOWED
function calcParseBlanksSetting($setting) {
	$allowed = array();
	$excluded = array();
	switch($setting) {
		case "onlyblanks";
			$allowed[] = "{BLANK}";
			$allowed[] = 0;
			break;
		case "noblanks";
			$excluded[] = "{BLANK}";
			$excluded[] = 0;
			break;
		case "justnoblanks";
			$excluded[] = "{BLANK}";
			break;
		case "justnozeros";
			$excluded[] = 0;
			break;
		case "all";
			break;
		default: // only thing left is custom
			$setting = explode(",",substr(str_replace("!@^%*", ",", $setting),6)); // replace back the commas and remove the word custom from the front, and explode it into an array
			foreach($setting as $thisSetting) {
				// does it have ! at the front, which is the "not" indicator
				if(substr($thisSetting,0,1)=="!") {
				$allowed[] = formulize_db_escape(substr($thisSetting,1));
				} else {
					$excluded[] = formulize_db_escape($thisSetting);
				}
			}
			break;
	}
	$to_return = array(0=>$allowed,1=>$excluded);
	return $to_return;
}


// THIS FUNCTION TAKES THE VALUE AND THE HANDLE AND FIGURES OUT WHAT THE VALUE PLUS UITEXT WOULD BE
// This is only used when determining the item values for percentage breakdown calculations
function calcValuePlusText($value, $handle, $col, $calc, $groupingValue) {

  if($handle == "entry_id" OR $handle=="creation_date" OR $handle == "mod_date" OR $handle == "creation_datetime" OR $handle == "mod_datetime" OR $handle == "creator_email") {
	return $value;
  }
  if($handle == "uid" OR $handle=="proxyid" OR $handle == "creation_uid" OR $handle == "mod_uid") {
	$member_handler = xoops_gethandler('member');
    $nameToDisplay = _FORM_ANON_USER;
    if($userObject = $member_handler->getUser($value)) {
        $nameToDisplay = $userObject->getVar('name') ? $userObject->getVar('name') : $userObject->getVar('uname');
    }
	return $nameToDisplay;
  }
  if($handle == "email" AND strstr($value, "@")) { // creator e-mail metadata field will be treated as having handle "email" in the calculation SQL, so we need this special condition
	return $value;
  }
  $id = formulize_getIdFromElementHandle($handle);
  $element_handler =& xoops_getmodulehandler('elements', 'formulize');
  $element = $element_handler->get($id);
  // check for fullnames/usernames and handle those
  $ele_type = $element->getVar('ele_type');
  if($ele_type == "select") {
	$ele_value = $element->getVar('ele_value');
	if(is_array($ele_value[2]) AND (key($ele_value[2]) === "{USERNAMES}" OR key($ele_value[2]) === "{FULLNAMES}")) {
	  if(!isset($GLOBALS['formulize_fullNameUserNameCalculationReplacementList'][$col][$calc][$groupingValue])) {
		$GLOBALS['formulize_fullNameUserNameCalculationReplacementList'][$col][$calc][$groupingValue]['nametype'] = key($ele_value[2]) === "{USERNAMES}" ? "uname" : "name";
	  }
	  $GLOBALS['formulize_fullNameUserNameCalculationReplacementList'][$col][$calc][$groupingValue]['values'][] = $value; // flag the value for replacement later
	  return $value;
	}
  }
  $uitexts = $element->getVar('ele_uitext');
  $value = isset($uitexts[$value]) ? $uitexts[$value] : $value;
  if(substr($value, 0, 6)=="{OTHER") { $value = _formulize_OPT_OTHER; }
  if($element->getVar('ele_type')=='yn') {
	if($value == "1") {
			$value = _formulize_TEMP_QYES;
		} elseif($value == "2") {
			$value = _formulize_TEMP_QNO;
		} else {
			$value = "";
		}
  }
  return $value;
}


//THIS FUNCTION TAKES A MASTER RESULT SET AND DRAWS IT ON THE SCREEN
// old notes:
// calc_cols is the columns requested (separated by / -- ele_id for each, also metadata is indicated with uid, proxyid, creation_date, mod_date)
// calc_calcs is the calcs for each column, columns separated by / and calcs for a column separated by ,. possible calcs are sum, avg, min, max, count, per
// calc_blanks is the blank setting for each calculation, setup the same way as the calcs, possible settings are all,  noblanks, onlyblanks
// calc_grouping is the grouping option.  same format as calcs.  possible values are ele_ids or the uid, proxyid, creation_date and mod_date metadata terms
function printResults($masterResults, $blankSettings, $groupingSettings, $groupingValues, $masterResultsRaw, $filename="", $title="") {

	$output = "";
	foreach($masterResults as $elementId=>$calcs) {
		$output .= "<tr><td class=head colspan=2>\n";
		$output .= printSmart(trans(getCalcHandleText($elementId)), 100);
		$output .= "\n</td></tr>\n";
		foreach($calcs as $calc=>$groups) {
			$countGroups = count((array) $groups);
			$rowspan = ($countGroups > 1 AND $calc != "count" AND $calc != "sum") ? $countGroups : 1;
		$output .= "<tr><td class=even rowspan=$rowspan>\n"; // start of row with calculation results (possibly first row among many)
			switch($calc) {
				case "sum":
					$calc_name = _formulize_DE_CALC_SUM;
					break;
				case "avg":
					$calc_name = _formulize_DE_CALC_AVG;
					break;
				case "min":
					$calc_name = _formulize_DE_CALC_MIN;
					break;
				case "max":
					$calc_name = _formulize_DE_CALC_MAX;
					break;
				case "count":
					$calc_name = _formulize_DE_CALC_COUNT;
					break;
				case "per":
					$calc_name = _formulize_DE_CALC_PER;
					break;
			}
			$output .= "<p><b>$calc_name</b></p>\n";
			switch($blankSettings[$elementId][$calc]) {
				case "all":
					$bsetting = _formulize_DE_INCLBLANKS;
					break;
				case "noblanks":
					$bsetting = _formulize_DE_EXCLBLANKS;
					break;
				case "onlyblanks":
					$bsetting = _formulize_DE_INCLONLYBLANKS;
					break;
				case "justnoblanks":
					$bsetting = _formulize_DE_EXCLONLYBLANKS;
					break;
				case "justnozeros":
					$bsetting =_formulize_DE_EXCLONLYZEROS;
					break;
				default: // must be custom
					$bsetting = _formulize_DE_EXCLCUSTOM;
					$setting = explode(",",substr(str_replace("!@^%*", ",", $blankSettings[$elementId][$calc]),6)); // replace back the commas and remove the word custom from the front, and explode it into an array
					$start = 1;
					foreach($setting as $thissetting) {
						if(!$start) {
							$bsetting .= ", ";
						}
						$start = 0;
						if(substr($thissetting,0,1)=="!") {
							$notText = strtolower(_formulize_NOT) ." ";
							$thissetting = substr($thissetting,1);
						} else {
							$notText = "";
						}
						$bsetting .= $notText.$thissetting;
					}
					break;
			}
			$output .= "<p class='formulize_blank_setting'>$bsetting</p>\n</td>\n";

			// start of right hand column for calculation results
			if($calc == "count") {
				$output .= "<td class=odd>\n"; // start of cell with calculations results

				if($countGroups > 1) {
						$theseGroupSettings = explode("!@^%*", $groupingSettings[$elementId][$calc]);
						$firstGroupSettingText = printSmart(trans(getCalcHandleText($theseGroupSettings[0], true)));

						$output .= "<table style='width: auto;'><tr><th>$firstGroupSettingText</th><td class='count-total' style='padding-left: 2em;'><center><b>"._formulize_DE_CALC_NUMENTRIES."</b><center></td><td class='count-unique' style='padding-left: 2em;'><center><b>"._formulize_DE_CALC_NUMUNIQUE."</b><center></td></tr>\n";

						$totalCount = 0;
						$totalUnique = 0;
						foreach($masterResultsRaw[$elementId][$calc] as $group=>$rawResult) {
								foreach($theseGroupSettings as $id=>$thisGroupSetting) {
										if($thisGroupSetting === "none") { continue; }
										$elementMetaData = formulize_getElementMetaData($thisGroupSetting, false);
										$groupText = formulize_swapUIText($groupingValues[$elementId][$calc][$group][$id], unserialize($elementMetaData['ele_uitext']));
										$output .= "<tr><td>".printSmart(trans($groupText))."</td><td class='count-total' style='text-align: right;'>".$rawResult['count']."</td><td class='count-unique' style='text-align: right;'>".$rawResult['countunique']."</td></tr>";
										$totalCount += $rawResult['count'];
										$totalUnique += $rawResult['countunique'];
								}
						}

						$output .= "<tr><td style='border-top: 1px solid black;'><b>"._formulize_DE_CALC_GRANDTOTAL."</b></td><td style='border-top: 1px solid black; text-align: right;' class='count-total'><b>$totalCount</b></td><td style='border-top: 1px solid black; text-align: right;' class='count-unique'><b>$totalUnique</b></td></tr>\n";
						$output .= "</table>";
				} else {
						$rawResult = $masterResultsRaw[$elementId][$calc][0];
						$output .= "<div class='count-total'><p><b>"._formulize_DE_CALC_NUMENTRIES." . . . ".$rawResult['count']."</b></p></div><div class='count-unique'><p><b>"._formulize_DE_CALC_NUMUNIQUE." . . . ".$rawResult['countunique']."</b></p></div>\n";
				}

				$output .= "</td></tr>"; // end of the main row, and the specific cell with the calculations results

			} elseif($calc == "sum") {
				$output .= "<td class=odd>\n"; // start of cell with calculations results
				$handle = convertElementIdsToElementHandles($elementId); // returns an array, since it might be passed multiple values
				$handle = $handle[0];
				if($countGroups > 1) {

					$theseGroupSettings = explode("!@^%*", $groupingSettings[$elementId][$calc]);
					$firstGroupSettingText = printSmart(trans(getCalcHandleText($theseGroupSettings[0], true)));

					$output .= "<table style='width: auto;'><tr><th>$firstGroupSettingText</th><td class='sum-total' style='padding-left: 2em;'><center><b>"._formulize_DE_CALC_SUM."</b><center></td></tr>\n";
					$totalSum = 0;
					foreach($masterResultsRaw[$elementId][$calc] as $group=>$rawResult) {
							foreach($theseGroupSettings as $id=>$thisGroupSetting) {
									if($thisGroupSetting === "none") { continue; }
									$elementMetaData = formulize_getElementMetaData($thisGroupSetting, false);
									$groupText = formulize_swapUIText($groupingValues[$elementId][$calc][$group][$id], unserialize($elementMetaData['ele_uitext']));
									$output .= "<tr><td>".printSmart(trans($groupText))."</td><td class='sum-total' style='text-align: right;'>".formulize_numberFormat($rawResult['sum'],$handle)."</td></tr>";
									$totalSum += $rawResult['sum'];
							}
					}

					$output .= "<tr><td style='border-top: 1px solid black;'><b>"._formulize_DE_CALC_GRANDTOTAL."</b></td><td style='border-top: 1px solid black; text-align: right;' class='sum-total'><b>".formulize_numberFormat($totalSum,$handle)."</b></td></tr>\n";
					$output .= "</table>";

				} else {
					$rawResult = $masterResultsRaw[$elementId][$calc][0];
					$output .= "<div class='sum-total'><p><b>"._formulize_DE_CALC_SUM." . . . ".formulize_numberFormat($rawResult['sum'],$handle)."</b></p></div>\n";
				}
				$output .= "</td></tr>"; // end of the main row, and the specific cell with the calculations results

			} else {
	  $start = 1;
		foreach($groups as $group=>$result) {
		//foreach($result as $resultID=>$thisResult) {
		  if(!$start) { $output .= "<tr>\n"; }
		  $start=0;
		  $output .= "<td class=odd>\n";
		  //if(count((array) $groups)>1) { // OR count((array) $groups)>1) { // output the heading section for this group of results
			$output .= "<p><b>";
			$start2 = true;
			foreach(explode("!@^%*", $groupingSettings[$elementId][$calc]) as $id=>$thisGroupSetting) {
			  if($thisGroupSetting === "none") { continue; }
			  if(!$start2) {
				$output .= "<br>\n";
			  }
			  $start2 = false;
			  $elementMetaData = formulize_getElementMetaData($thisGroupSetting, false);
			  $groupText = formulize_swapUIText($groupingValues[$elementId][$calc][$group][$id], unserialize($elementMetaData['ele_uitext']));
			  $output .= printSmart(trans(getCalcHandleText($thisGroupSetting, true))) . ": " . printSmart(trans($groupText)) . "\n";
			}
			$output .= "</b></p>\n";
		  //}
		  $output .= "<p>$result</p>\n";
		  $output .= "</td></tr>\n";
		//}
		}
	}
  }
  }
	print $output;
	// addition of calculation download, August 22 2006
	if($filename) {
		// get the current CSS values for head, even and odd
		global $xoopsConfig;
		$head = "";
		$odd = "";
		$even = "";
	formulize_benchmark("before reading stylesheet");
		if(file_exists(XOOPS_ROOT_PATH . "/themes/" . $xoopsConfig['theme_set'] . "/style.css")) {
			if( !class_exists('csstidy') ) {
				// use supplied csstidy in parent if one exists...
				if(file_exists(XOOPS_ROOT_PATH . "/plugins/csstidy/class.csstidy.php")) {
					include_once XOOPS_ROOT_PATH . "/plugins/csstidy/class.csstidy.php";
				} else {
					include_once XOOPS_ROOT_PATH . "/modules/formulize/class/class.csstidy.php";
				}
			}
			$css = new csstidy();
			$css->set_cfg('merge_selectors',0);
			$css->parse_from_url(XOOPS_ROOT_PATH . "/themes/" . $xoopsConfig['theme_set'] . "/style.css");
			$parsed_css = $css->css;
			// parsed_css seems to have only one key when looking at the default template...key is the number of styles?
			foreach($parsed_css as $thiscss) {
				$head = isset($thiscss['.head']['background-color']) ? $thiscss['.head']['background-color'] : (isset($thiscss['.head']['background']) ? $thiscss['.head']['background'] : "");
				$even = isset($thiscss['.even']['background-color']) ? $thiscss['.even']['background-color'] : (isset($thiscss['.even']['background']) ? $thiscss['.even']['background'] : "");
				$odd = isset($thiscss['.odd']['background-color']) ? $thiscss['.odd']['background-color'] : (isset($thiscss['.odd']['background']) ? $thiscss['.odd']['background'] : "");
			}
		}
	formulize_benchmark("after reading stylesheet");
		unset($css);
		// if we couldn't find any values, use these:
		$head = $head ? $head : "#c2cdd6";
		$even = $even ? $even : "#dee3e7";
		$odd = $odd ? $odd : "#E9E9E9";

		// create the file
	formulize_benchmark("before creating file");
		$outputfile = "<HTML>
<head>
<meta charset='UTF-8'>
<meta name=\"generator\" content=\"Formulize -- form creation and data management\" />
<title>" . _formulize_DE_EXPORTCALC_TITLE . " '".trans($title)."'</title>
<style type=\"text/css\">
.outer {border: 1px solid silver;}
.head { background-color: $head; padding: 5px; font-weight: bold; }
.even { background-color: $even; padding: 5px; }
.odd { background-color: $odd; padding: 5px; }
body {color: black; background: white; margin-top: 30px; margin-bottom: 30px; margin-left: 30px; margin-right: 30px; padding: 0; font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10pt;}
td { vertical-align: top; }
</style>
</head>
<body>
<h1>" . _formulize_DE_EXPORTCALC_TITLE . " '".trans($title)."'</h1>
<table class=outer>
$output
</table>
</body>
</html>";
		// output the file
		$wpath = XOOPS_ROOT_PATH . SPREADSHEET_EXPORT_FOLDER . undoAllHTMLChars($filename);
		$exportfile = fopen($wpath, "w");
		fwrite ($exportfile, $outputfile);
		fclose ($exportfile);
	}
	formulize_benchmark("after creating file");
}

// this function includes the javascript necessary make the interface operate properly
// note the mandatory clearing of the ventry value upon loading of the page.  Necessary to make the back button work right (otherwise ventry setting is retained from the previous loading of the page and the form is displayed after the next submission of the controls form)
function interfaceJavascript($fid, $frid, $currentview, $useWorking, $useXhr, $lockedColumns) {
?>
<script type='text/javascript'>

if (typeof jQuery == 'undefined') {
	var head = document.getElementsByTagName('head')[0];
	script = document.createElement('script');
	script.id = 'jQuery';
	script.type = 'text/javascript';
	script.src = '<?php print XOOPS_URL; ?>/modules/formulize/libraries/jquery/jquery-1.4.2.min.js';
	head.appendChild(script);
}

var formulize_javascriptFileIncluded = new Array();

function includeResource(filename, type) {
   if(filename in formulize_javascriptFileIncluded == false) {
	 var head = document.getElementsByTagName('head')[0];
	 if(type == 'link') {
	   var resource = document.createElement("link");
	   resource.type = "text/css";
	   resource.rel = "stylesheet";
	   resource.href = filename;
	 } else if(type == 'script') {
	   var resource = document.createElement('script');
	   resource.type = 'text/javascript';
	   resource.src = filename;
	 }
	 head.appendChild(resource);
	 formulize_javascriptFileIncluded[filename] = true;
   }
} 

<?php
if($useXhr) {
	print " initialize_formulize_xhr();\n";
	drawXhrJavascript();
	print "</script>";
	print "<script type='text/javascript'>";
	print "var elementStates = new Array();";
	print "var savingNow = \"\";";
	print "var elementActive = \"\";";
?>
function renderElement(handle,element_id,entryId,fid,check,deInstanceCounter) {
	if(elementStates[handle] == undefined) {
		elementStates[handle] = new Array();
	}
	if(elementStates[handle][entryId] == undefined) {
		if(elementActive) {
			alert("<?php print _formulize_CLOSE_FORM_ELEMENT; // this is a bit cheap...we should be able to track multiple elements open at once.  But there seem to be race condition issues in the asynchronous requests that we have to track down.  This UI restriction isn't too bad though. ?>");
			return false;
		}
		elementActive = true;
		elementStates[handle][entryId] = jQuery("#deDiv_"+handle+"_"+entryId+"_"+deInstanceCounter).html();
		var formulize_xhr_params = [];
		formulize_xhr_params[0] = handle;
		formulize_xhr_params[1] = element_id;
		formulize_xhr_params[2] = entryId;
		formulize_xhr_params[3] = fid;
		formulize_xhr_params[5] = deInstanceCounter;
		formulize_xhr_send('get_element_html',formulize_xhr_params);
	} else {
		if(check && savingNow == "") {
			savingNow = true;
			jQuery("#deDiv_"+handle+"_"+entryId+"_"+deInstanceCounter).fadeTo("fast",0.33);
			if(jQuery("[name='de_"+fid+"_"+entryId+"_"+element_id+"[]']").length > 0) {
			  nameToUse = "[name='de_"+fid+"_"+entryId+"_"+element_id+"[]']";
			} else {
			  nameToUse = "[name='de_"+fid+"_"+entryId+"_"+element_id+"']";
			}
			jQuery.post("<?php print XOOPS_URL; ?>/modules/formulize/include/readelements.php", jQuery(nameToUse+",[name='decue_"+fid+"_"+entryId+"_"+element_id+"']").serialize(), function(data) {
				if(data) {
				   alert(data);
				} else {
					// need to get the current value, and then prep it, and then format it
					var formulize_xhr_params = [];
					formulize_xhr_params[0] = handle;
					formulize_xhr_params[1] = element_id;
					formulize_xhr_params[2] = entryId;
					formulize_xhr_params[3] = fid;
					formulize_xhr_params[5] = deInstanceCounter;
					formulize_xhr_send('get_element_value',formulize_xhr_params);
				}
			});
		} else if(check) {
			// do nothing...only allow one saving operation at a time
		} else {
			jQuery("#deDiv_"+handle+"_"+entryId+"_"+deInstanceCounter).html(elementStates[handle][entryId]);
			elementStates[handle].splice(entryId, 1);
			elementActive = "";
		}
	}
	return false;
}

function renderElementHtml(elementHtml,params) {
	handle = params[0];
	element_id = params[1];
	entryId = params[2];
	fid = params[3];
	deInstanceCounter = params[5];
	jQuery("#deDiv_"+handle+"_"+entryId+"_"+deInstanceCounter).html(elementHtml+"<br /><a style=\"display: inline-block;\" href=\"\" onclick=\"renderElement('"+handle+"', "+element_id+", "+entryId+", "+fid+",1,"+deInstanceCounter+");return false;\"><img src=\"<?php print XOOPS_URL; ?>/modules/formulize/images/check.gif\" /></a>&nbsp;&nbsp;&nbsp;<a style=\"display: inline-block;\" href=\"\" onclick=\"javascript:renderElement('"+handle+"', "+element_id+", "+entryId+", "+fid+",0,"+deInstanceCounter+");return false;\"><img src=\"<?php print XOOPS_URL; ?>/modules/formulize/images/x-wide.gif\" /></a>");
}

function renderElementNewValue(elementValue,params) {
	handle = params[0];
	element_id = params[1];
	entryId = params[2];
	fid = params[3];
	deInstanceCounter = params[5];
	jQuery("#deDiv_"+handle+"_"+entryId+"_"+deInstanceCounter).fadeTo("fast",1);
	jQuery("#deDiv_"+handle+"_"+entryId+"_"+deInstanceCounter).html(elementValue);
	elementStates[handle].splice(entryId, 1);
	savingNow = "";
	elementActive = "";
}

<?php
} // end of if use XHR -- only invoked for handling inline display elements
?>

window.document.controls.ventry.value = '';
window.document.controls.loadreport.value = '';

function warnLock() {
	alert('<?php print _formulize_DE_WARNLOCK; ?>');
	return false;
}

function clearSearchHelp(formObj, defaultHelp) {
	if(formObj.firstbox.value == defaultHelp) {
		formObj.firstbox.value = "";
	}
}

function showPop(url) {

	window.document.controls.ventry.value = '';
	if (window.formulize_popup == null) {
		formulize_popup = window.open(url,'formulize_popup','toolbar=no,scrollbars=yes,resizable=yes,width=800,height=550,screenX=0,screenY=0,top=0,left=0');
	  } else {
		if (window.formulize_popup.closed) {
			formulize_popup = window.open(url,'formulize_popup','toolbar=no,scrollbars=yes,resizable=yes,width=800,height=550,screenX=0,screenY=0,top=0,left=0');
			} else {
			window.formulize_popup.location = url;
		}
	}
	window.formulize_popup.focus();

}

function confirmDel() {
	var answer = confirm ('<?php print _formulize_DE_CONFIRMDEL; ?>');
	if (answer) {
		window.document.controls.delconfirmed.value = 1;
		window.document.controls.ventry.value = '';
		showLoading();
		return true;
	} else {
		return false;
	}
}

function confirmClone() {
	var clonenumber = prompt("<?php print _formulize_DE_CLONE_PROMPT; ?>", "1");
	if(eval(clonenumber) > 0) {
		window.document.controls.cloneconfirmed.value = clonenumber;
		window.document.controls.ventry.value = '';
		window.document.controls.forcequery.value = 1;
		showLoading();
		return true;
	} else {
		return false;
	}
}


function sort_data(col) {
	if(window.document.controls.sort.value == col) {
		var ord = window.document.controls.order.value;
		if(ord == 'SORT_DESC') {
			window.document.controls.order.value = 'SORT_ASC';
		} else {
			window.document.controls.order.value = 'SORT_DESC';
		}
	} else {
		window.document.controls.order.value = 'SORT_ASC';
	}
	window.document.controls.sort.value = col;
	window.document.controls.ventry.value = '';
	showLoading();
}


function runExport(type) {
	window.document.controls.xport.value = type;
	window.document.controls.ventry.value = '';
	showLoading();

}

function showExport() {
	window.document.getElementById('exportlink').style.display = 'block';
}

//Select All and Clear All new JQuery Function Instead the Javascript
function selectAll(check) {
   $('.formulize_selection_checkbox').each(function(){
	  $('.formulize_selection_checkbox').attr('checked', true);
   });
}

function unselectAll(uncheck) {
   $('.formulize_selection_checkbox').each(function(){
	  $('.formulize_selection_checkbox').attr('checked', false);
   });
}

function delete_view(pubstart, endstandard) {

	for (var i=0; i < window.document.controls.currentview.options.length; i++) {
		if (window.document.controls.currentview.options[i].selected) {
			if( i > endstandard && i < pubstart && window.document.controls.currentview.options[i].value != "") {
				var answer = confirm ('<?php print _formulize_DE_CONF_DELVIEW; ?>');
				if (answer) {
					window.document.controls.delview.value = 1;
					window.document.controls.ventry.value = '';
					showLoading();
					return true;
				} else {
					return false;
				}
			} else {
				if(window.document.controls.currentview.options[i].value != "") {
					alert('<?php print _formulize_DE_DELETE_ALERT; ?>');
				}
				return false;
			}
		}
	}
	return false;

}

function change_view(formObj, pickgroups, endstandard) {
	for (var i=0; i < formObj.currentview.options.length; i++) {
		if (formObj.currentview.options[i].selected) {
			if(i == pickgroups && pickgroups != 0) {
				<?php print "showPop('" . XOOPS_URL . "/modules/formulize/include/changescope.php?fid=$fid&frid=$frid&scope=$currentview');"; ?>
				return false;
			} else {
				if ( formObj.currentview.options[i].value == "" ) {
					return false;
				} else {
					window.document.controls.loadreport.value = 1;
					if(i <= endstandard && window.document.controls.lockcontrols.value == 1) {
						window.document.controls.resetview.value = 1;
						window.document.controls.curviewid.value = "";
					}
					window.document.controls.lockcontrols.value = 0;
					window.document.controls.ventry.value = '';
					showLoading();
					return true;
				}
			}
		}
	}
	return false;
}

function addNew(flag) {
	if(flag=='proxy') {
		window.document.controls.ventry.value = 'proxy';
	} else if(flag=='single') {
		window.document.controls.ventry.value = 'single';
	} else {
		window.document.controls.ventry.value = 'addnew';
	}
	window.document.controls.submit();
}

function goDetails(viewentry, screen) {
	window.document.controls.ventry.value = viewentry;
	if(screen>0) {
		window.document.controls.overridescreen.value = screen;
	}
	window.document.controls.submit();
}

function cancelCalcs() {
	window.document.controls.calc_cols.value = '';
	window.document.controls.calc_calcs.value = '';
	window.document.controls.calc_blanks.value = '';
	window.document.controls.calc_grouping.value = '';
	window.document.controls.hlist.value = 0;
	window.document.controls.hcalc.value = 1;
	window.document.controls.ventry.value = '';
	showLoading();
}

function customButtonProcess(caid, entries, popup) {
	if (popup) {
		var answer = confirm(popup);
		if (!answer) {
			return false;
		}
	}
	window.document.controls.caid.value = caid;
	window.document.controls.caentries.value = entries;
	showLoading();
	return true;
}


function hideList() {
	window.document.controls.hlist.value = 1;
	window.document.controls.hcalc.value = 0;
	window.document.controls.ventry.value = '';
	showLoading();
}

function showList() {
	window.document.controls.hlist.value = 0;
	window.document.controls.hcalc.value = 1;
	window.document.controls.ventry.value = '';
	showLoading();
}

function killSearch() {
	window.document.controls.asearch.value = '';
	window.document.controls.ventry.value = '';
	showLoading();
}

function forceQ() {
	window.document.controls.forcequery.value = 1;
	showLoading();
}

function showLoading() {
	window.document.controls.formulize_scrollx.value = jQuery(window).scrollTop();
	window.document.controls.formulize_scrolly.value = jQuery(window).scrollLeft();
	<?php
		if($useWorking) {
			print "window.document.getElementById('listofentries').style.opacity = 0.5;\n";
			print "window.document.getElementById('workingmessage').style.display = 'block';\n";
			print "window.scrollTo(0,0);\n";
		}
	?>
	window.document.controls.ventry.value = '';
	window.document.controls.submit();
}

function showLoadingReset() {
	<?php
		if($useWorking) {
			print "window.document.getElementById('listofentries').style.opacity = 0.5;\n";
			print "window.document.getElementById('workingmessage').style.display = 'block';\n";
			print "window.scrollTo(0,0);\n";
		}
	?>
	window.document.resetviewform.submit();
}

function pageJump(page) {
	window.document.controls.formulize_LOEPageStart.value = page;
	showLoading();
}

jQuery(window).load(function() {
	
	<?php
	// set the scroll position when first loading
	if(isset($_POST['formulize_scrollx']) OR isset($_POST['formulize_scrolly'])) {
		print "jQuery(window).scrollTop(".intval($_POST['formulize_scrollx']).");
		jQuery(window).scrollLeft(".intval($_POST['formulize_scrolly']).");";
	}
	
	?>
	
	var saveButtonOffset = jQuery('#floating-list-of-entries-save-button').offset();
	if (saveButtonOffset) {
		saveButtonOffset.left = 15;
		floatSaveButton(saveButtonOffset);
		jQuery(window).scroll(function () {
			floatSaveButton(saveButtonOffset);
		});
	}
    
});
    
jQuery(document).ready(function() {
    jQuery('.formulize_selection_checkbox').click(function() {
        if(this.checked && jQuery('#formulize_moreActions').length && jQuery('#more-action-buttons').is(':hidden'))  {
            jQuery('#more-action-buttons').show(300);
        }
        if(this.checked == false && jQuery('#more-action-buttons').is(':visible') && jQuery('.formulize_selection_checkbox:checked').length == 0) {
            jQuery('#more-action-buttons').hide(300);
        }
    });    
});

function floatSaveButton(saveButtonOffset) {
	  var scrollBottom = jQuery(window).height() - jQuery(window).scrollTop();
	  if (saveButtonOffset && (saveButtonOffset.top > scrollBottom || jQuery(window).width() < jQuery(document).width())) {
	jQuery('#floating-list-of-entries-save-button').addClass('save_button_fixed');
	jQuery('#floating-list-of-entries-save-button').addClass('ui-corner-all');
	if(saveButtonOffset.top <= scrollBottom) {
		jQuery('#floating-list-of-entries-save-button').css('bottom', jQuery(window).height() - saveButtonOffset.top - jQuery('#floating-list-of-entries-save-button').height());
	}
	if(jQuery(window).scrollLeft() < saveButtonOffset.left) {
		newSaveButtonOffset = saveButtonOffset.left - jQuery(window).scrollLeft();
	} else if(jQuery(window).scrollLeft() > 0){
		newSaveButtonOffset = 0;
	} else {
		newSaveButtonOffset = saveButtonOffset.left;
	}
	jQuery('#floating-list-of-entries-save-button').css('left', newSaveButtonOffset);
	  } else if(saveButtonOffset) {
	jQuery('#floating-list-of-entries-save-button').removeClass('save_button_fixed');
	jQuery('#floating-list-of-entries-save-button').removeClass('ui-corner-all');
	  };
}

<?php

print checkForChrome();
print "</script>";

}


// THIS FUNCTION LOADS A SAVED VIEW
// fid and frid are only used if a report is being asked for by name
function loadReport($id, $fid, $frid) {
	global $xoopsDB;
  if(is_numeric($id)) {
	$thisview = q("SELECT * FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_id='$id'");
  } else {
	if($frid) {
	  $formframe = intval($frid);
	  $mainform = intval($fid);
	} else {
	  $formframe = intval($fid);
	  $mainform = "''";
	}
	$thisview = q("SELECT * FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_name='".formulize_db_escape($id)."' AND sv_formframe = $formframe AND sv_mainform = $mainform");
  }
  if(!isset($thisview[0]['sv_currentview'])) {
	print "Error: could not load the specified saved view: '".strip_tags(htmlspecialchars($id))."'";
	return false;
  }
	$to_return[0] = $thisview[0]['sv_currentview'];
	$to_return[1] = $thisview[0]['sv_oldcols'];
	$to_return[2] = $thisview[0]['sv_asearch'];
	$to_return[3] = $thisview[0]['sv_calc_cols'];
	$to_return[4] = $thisview[0]['sv_calc_calcs'];
	$to_return[5] = $thisview[0]['sv_calc_blanks'];
	$to_return[6] = $thisview[0]['sv_calc_grouping'];
	$to_return[7] = $thisview[0]['sv_sort'];
	$to_return[8] = $thisview[0]['sv_order'];
	$to_return[9] = $thisview[0]['sv_hidelist'];
	$to_return[10] = $thisview[0]['sv_hidecalc'];
	$to_return[11] = $thisview[0]['sv_lockcontrols'];
	$to_return[12] = $thisview[0]['sv_quicksearches'];
	$to_return[13] = $thisview[0]['sv_global_search'];
	$to_return[14] = $thisview[0]['sv_pubfilters'];
	return $to_return;
}

//This function loads the Advance view that was set up at the "Data to be displayed" tab
function loadAdvanceView($fid, $advance_view) {
	$sort = null;
	$sortby = null;
	if($advance_view){
			foreach($advance_view as $id=>$arr) {
		   $columns .= $arr[0].',';
		   $search .= $arr[1].',';
		   if($arr[2] == "1"){
		$sort = $arr[0];
			$sortby = "SORT_ASC";
		   }
		}
		//Remove the trailing ','
		$columns = rtrim($columns, ",");
		$search = rtrim($search, ",");
		$to_return[0] = $columns;
		$to_return[1] = $sort;
		$to_return[2] = $sortby;
		$to_return[3] = $search;
		return $to_return;
	} else{
		return null;
	}
}

// remove columns that the user does not have permission to view -- added June 29, 2006 -- jwe
// this function takes a column list (handles or ids) and returns it with all columns removed that the user cannot view according to the display options on the elements
// this function also removes columns that are private if the user does not have view_private_elements permission
function removeNotAllowedCols($fid, $frid, $cols, $groups) {


	// convert old metadata handles to new ones if present
	if($uidKey = array_search("uid", $cols)) {
		$cols[$uidKey] = "creation_uid";
	}
	if($proxyidKey = array_search("proxyid", $cols)) {
		$cols[$proxyidKey] = "mod_uid";
	}
	if($mod_dateKey = array_search("mod_date", $cols)) {
		$cols[$mod_dateKey] = "mod_datetime";
	}
	if($creation_dateKey = array_search("creation_date", $cols)) {
		$cols[$creation_dateKey] = "creation_datetime";
	}

	$all_allowed_cols = array();
	$allowed_cols_in_view = array();

	// metadata columns always allowed!
	$dataHandler = new formulizeDataHandler(false);
	$metadataFields = $dataHandler->metadataFields;

	foreach ($metadataFields as $field)
	{
		$all_allowed_cols[] = $field;
	}

	$all_allowed_cols_raw = getAllColList($fid, $frid, $groups);
	foreach($all_allowed_cols_raw as $form_id=>$values) {
		foreach($values as $id=>$value) {
			if(!in_array($value['ele_handle'], $all_allowed_cols)) {	$all_allowed_cols[] = $value['ele_handle']; }
		}
	}
	$all_cols_from_view = $cols;

	$allowed_cols_in_view = array_intersect($all_cols_from_view, $all_allowed_cols);
	$allowed_cols_in_view = array_values($allowed_cols_in_view);

	return $allowed_cols_in_view;
}

// THIS FUNCTION HANDLES INTERPRETTING A LOE SCREEN TEMPLATE
// $type is the top/bottom setting
// $buttonCodeArray is the available buttons that have been pre-compiled by the drawInterface function
function formulize_screenLOETemplate($screen, $type, $buttonCodeArray, $settings) {
	// include necessary files
	$screenOrScreenType = $screen ? $screen : 'listOfEntries';
	if(strstr(getTemplateToRender($type.'template', $screenOrScreenType), 'buildFilter(')) {
		include_once XOOPS_ROOT_PATH . "/modules/formulize/include/calendardisplay.php";
	}

	// setup the button variables
	foreach($buttonCodeArray as $buttonName=>$buttonCode) {
		${$buttonName} = $buttonCode;
	}
	
	// setup the view name variables, with true only set for the last loaded view
	$viewNumber = 1;
	foreach($settings['publishedviewnames'] as $id=>$thisViewName) {
		$thisViewName = str_replace(" ", "_", $thisViewName);
		if($id == $settings['lastloaded']) {
			${$thisViewName} = true;
			${'view'.$viewNumber} = true;
		} else {
			${$thisViewName} = false;
			${'view'.$viewNumber} = false;
		}
		$viewNumber++;
	}

	// setup any custom buttons
	$atLeastOneCustomButton = false;

	$caCode = array();
	if($screen) {
		foreach($screen->getVar('customactions') as $caid=>$thisCustomAction) {
			if($thisCustomAction['appearinline']) { continue; } // ignore buttons that are meant to appear inline
			$atLeastOneCustomButton = true;
			list($caCode) = processCustomButton($caid, $thisCustomAction);
			if($caCode) {
				${$thisCustomAction['handle']} = $caCode; // assign the button code that was returned
			}
		}
	}

	// if there is no save button specified in either of the templates, but one is available, then put it in below the list
	if($screen AND $type == "bottom" AND
		count((array) $screen->getVar('decolumns')) > 0 AND
		!$screen->getVar('dedisplay') AND
		$GLOBALS['formulize_displayElement_LOE_Used'] AND
		!strstr(getTemplateToRender('toptemplate', $screenOrScreenType), 'saveButton') AND
		!strstr(getTemplateToRender('bottomtemplate', $screenOrScreenType), 'saveButton') AND
		!strstr(getTemplateToRender('openlisttemplate', $screenOrScreenType), 'saveButton') AND
		!strstr(getTemplateToRender('closelisttemplate', $screenOrScreenType), 'saveButton')
		) {
		print "<div id=\"floating-list-of-entries-save-button\" class=\"\"><p>$saveButton</p></div>\n";
	}

	$publishedFilters = is_array($settings['pubfilters']) ? $settings['pubfilters'] : array();

	$thisTemplate = getTemplateToRender($type.'template', $screenOrScreenType);
	if($thisTemplate != "") {

		// process the template and output results
		include getTemplatePath($screenOrScreenType, $type."template");

		// if there are no page nav controls in any template, then print them out
		if($type == "top" AND
			!strstr(getTemplateToRender('toptemplate', $screenOrScreenType), 'pageNavControls') AND
			!strstr(getTemplateToRender('bottomtemplate', $screenOrScreenType), 'pageNavControls') AND
			!strstr(getTemplateToRender('openlisttemplate', $screenOrScreenType), 'pageNavControls') AND
			!strstr(getTemplateToRender('closelisttemplate', $screenOrScreenType), 'pageNavControls') 
			) {
			print $pageNavControls;
		}
	}

	// output the message text to the screen if it's not used in the custom templates somewhere
	if($type == "top" AND $messageText AND
		!strstr(getTemplateToRender('toptemplate', $screenOrScreenType), 'messageText') AND
		!strstr(getTemplateToRender('bottomtemplate', $screenOrScreenType), 'messageText') AND
		!strstr(getTemplateToRender('openlisttemplate', $screenOrScreenType), 'messageText') AND
		!strstr(getTemplateToRender('closelisttemplate', $screenOrScreenType), 'messageText')
		) {
		print "<p><center><b>$messageText</b></center></p>\n";
	}

}

// THIS FUNCTION PROCESSES THE REQUESTED BUTTONS AND GENERATES HTML PLUS SENDS BACK INFO ABOUT THAT BUTTON
// $caid is the id of this button,
// $thisCustomAction is all the settings for this button, 
// $entry_id is the entry ID that should be altered when this button is clicked.  Only sent for inline buttons.  Looks like it is only ever a single ID of the main entry of the line where the button was clicked?
// $entry is the getData result package for this entry. Only sent from inline buttons, so that any PHP/HTML to be rendered inline has access to all the values of the current entry
function processCustomButton($caid, $thisCustomAction, $entry_id="", $entry="") {

	global $xoopsUser;
	$userGroups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
	if(!is_array($thisCustomAction['groups'])) {
		$thisCustomAction['groups'] = unserialize($thisCustomAction['groups']);	// under some circumstances, this might be serialized?  I think it's being unserialized by the getVar method before it gets this far, but anyway....
	}
	if(is_array($thisCustomAction['groups'])) {
		$groupOverlap = array_intersect($thisCustomAction['groups'], $userGroups);
		if(count((array) $groupOverlap) == 0) {
			return array();
		}
	}

	static $nameIdAddOn = 0; // used to give inline buttons unique names and ids

	$caElements = array();
	$caActions = array();
	$caValues = array();
	$caCode = array();
	$caHTML = array();
	$isHTML = false;
	foreach($thisCustomAction as $effectid=>$effectProperties) {
		if(!is_numeric($effectid)) { continue; } // effectid, as second key, could be buttontext, messagetext, etc, so ignore those and focus on actual effects which will have numeric keys
		$caElements[] = $effectProperties['element'];
		$caActions[] = $effectProperties['action'];
		$caValues[] = $effectProperties['value'];
		$caPHP[] = isset($effectProperties['code']) ? $effectProperties['code'] : "";
		$caHTML[$caid.'...'.$effectid.'...'.$entry_id] = isset($effectProperties['html']) ? $effectProperties['html'] : "";
		$isHTML = isset($effectProperties['html']) ? true : $isHTML;
        
        // experimental... need all types of element values and actions, etc, to be worked out
        $useClickedText = false;
        if($entry_id AND $thisCustomAction['appearinline'] == 1 AND $effectProperties['element'] AND $effectProperties['action'] AND $effectProperties['value']) {
            $element_handler = xoops_getmodulehandler('elements', 'formulize');
            if($elementObject = $element_handler->get($effectProperties['element'])) {
                $dataHandler = new formulizeDataHandler($elementObject->getVar('id_form'));
                $elementValueInEntry = $dataHandler->getElementValueInEntry($entry_id, $elementObject);
                $valueToCheck = processButtonValue($effectProperties['value'], $entry_id);
                switch($effectProperties['action']) {
                    case 'replace':
                        $useClickedText = $elementValueInEntry == $valueToCheck ? true : false;
                        break;
                    case 'append':
                        $strFound = strstr((string)$elementValueInEntry, (string)$valueToCheck);
                        $useClickedText = $strFound===false ? false : true;
                        break;
                    case 'remove':
                        $strFound = strstr((string)$elementValueInEntry, (string)$valueToCheck);
                        $useClickedText = $strFound===false ? true : false;
                        break;
                    
                }
            }
        }
        
	}
    
    // run HTML when there's no $entry, only if the code is not looking for $entry (not foolproof! but better than simply not running at all when there's no $entry)
    // need to run through all the code in advance of loop below because in the loop below we're caching the processing of the results and we don't want to cached results that are empty only because there's no $entry on this run through. There might be a valid $entry on a subsequent attempt
    $dollarEntryPresentInCode = false;
    foreach($caHTML as $key=>$thisHTML) {
        if(strstr($thisHTML,"\$entry") !== false) {
            $dollarEntryPresentInCode = true;
            break;
        }
    }
	if($isHTML AND ($entry OR !$dollarEntryPresentInCode)) { // code to be rendered in place
		static $cachedCAHTML = array(); // this function is called a few times...we want to generate the HTML only once
		$allHTML = "";
		foreach($caHTML as $key=>$thisHTML) {
			if(!isset($cachedCAHTML[$key])) {
                ob_start();
                eval($thisHTML);
 				$cachedCAHTML[$key] = ob_get_clean();
			}
			$allHTML .= $cachedCAHTML[$key];
		}
		$caCode = $allHTML;
	} else {
		$nameIdAddOn = $thisCustomAction['appearinline'] ? $entry_id : "";
        
        // figure out if the action(s) of the button have already been applied, and if so, use the clickedtext, otherwise use the buttontext
        // experimental... roughing this in with 'Shared' as the clickedtext
        $thisCustomAction['clickedtext'] = $thisCustomAction['buttontext'];
        if($caElements[0] == 192 AND strstr($thisCustomAction['buttontext'],'Share with Counsellor')) {
            $thisCustomAction['clickedtext'] = '[en]Shared[/en][fr]Partagé[/fr]';
        }
        
        if($useClickedText AND $caElements[0] == 192 AND strstr($thisCustomAction['buttontext'],'Share with Counsellor')) {
            $caCode = "<p>".$thisCustomAction['clickedtext']."</p>";
        } else {
            $caCode = "<input type=button style=\"cursor: pointer;\" name=\"" . $thisCustomAction['handle'] . "$nameIdAddOn\" id=\"" . $thisCustomAction['handle'] . "$nameIdAddOn\" value=\"" . trans($thisCustomAction['buttontext']) . "\" onclick=\"javascript:customButtonProcess('$caid', '$entry_id', '".trans(str_replace("'","\'",$thisCustomAction['popuptext']))."');\">\n";            
        }
        
        $buttonTextToUse = $useClickedText ? $thisCustomAction['clickedtext'] : $thisCustomAction['buttontext'];
        
	}

	return array(0=>$caCode, 1=>$caElements, 2=>$caActions, 3=>$caValues, 4=>$thisCustomAction['messagetext'], 5=>$thisCustomAction['applyto'], 6=>$caPHP, 7=>$thisCustomAction['appearinline']);
}

// THIS FUNCTION PROCESSES THE VALUE FOR A BUTTON, HANDLING PHP CODE IF NECESSARY
// buttonValue is the declared value the button is supposed to apply to the element
// entry_id is the ID number of the entry that is being affected by the button
function processButtonValue($buttonValue, $entry_id) {
    $valueToWrite = $buttonValue;
    $GLOBALS['formulize_thisEntryId'] = $entry_id; // sent up to global scope so it can be accessed by the gatherHiddenValues function without the user having to type ", $id" in the function call
    $formulize_thisEntryId = $entry_id;
    $formulize_lvoverride = false;
    if(strstr($buttonValue, "\$value")) {
        eval($buttonValue);
        $valueToWrite = $value;
    }
    $GLOBALS['formulize_lvoverride'] = $formulize_lvoverride; // kludgy way to pass it back when we might need to listen for it in writeElementValue!
    return $valueToWrite;
}

// THIS FUNCTION PROCESSES CLICKED CUSTOM BUTTONS
function processClickedCustomButton($clickedElements, $clickedValues, $clickedActions, $clickedMessageText, $clickedApplyTo, $caPHP, $caInline, $screen) {

	if(!is_numeric($_POST['caid'])) { return; } // 'caid' might be set in post, but we're not processing anything unless there actually is a value there

	static $gatheredSelectedEntries = false;
	if(!$gatheredSelectedEntries) {
		$GLOBALS['formulize_selectedEntries'] = array();
		foreach($_POST as $k=>$v) { // gather entries list from the selected entries
			if(substr($k, 0, 7) == "delete_" AND $v != "") {
				$GLOBALS['formulize_selectedEntries'][substr($k, 7)] = substr($k, 7); // make sure key and value are the same, so the special function below works inside the custom button's own logic
			}
		}
		$gatheredSelectedEntries = true;
	}

	if($clickedApplyTo == "custom_code") {
		$clickedEntries = array();
		if(isset($_POST['caentries'])) { // if this button was an inline button
			if($_POST['caentries'] != "") {
				$caEntriesTemp = explode(",", htmlspecialchars(strip_tags($_POST['caentries'])));
				foreach($caEntriesTemp as $id=>$val) {
					$clickedEntries[] = $val;
				}
			}
		}
		if(count((array) $clickedEntries) == 0 AND count((array) $GLOBALS['formulize_selectedEntries']) == 0) {
			$clickedEntries[] = "";
		} elseif(count((array) $clickedEntries) == 0) { // if this is not an inline button and there are selected entries, use them (inline buttons override selected checkboxes in this case for now)
			$clickedEntries = $GLOBALS['formulize_selectedEntries'];
		}
		foreach($caPHP as $thisCustomCode) {
			foreach($clickedEntries as $formulize_thisEntryId) {
				$GLOBALS['formulize_thisEntryId'] = $formulize_thisEntryId;
				eval($thisCustomCode);
			}
		}
	} else {

		$caEntries = array(); // click applied entries, ie: which entry does the button affect
		$csEntries = array(); // click source entries, ie: which entry do we gather hidden values from
		// need to handle "all" case by getting list of all entries in form
		if($clickedApplyTo == "selected") {
			$caEntries = $GLOBALS['formulize_selectedEntries'];
			$csEntries = $caEntries;
		} elseif($clickedApplyTo == "inline") {
			$caEntriesTemp = explode(",", htmlspecialchars(strip_tags($_POST['caentries'])));
			foreach($caEntriesTemp as $id=>$val) {
				$caEntries[$id] = $val; // make sure key and value are the same, so the special function below works inside the custom button's own logic (we need the $entry id to be the key).
			}
			$csEntries = $caEntries;
		} elseif(strstr($clickedApplyTo, "new_per_selected")) {
			foreach($GLOBALS['formulize_selectedEntries'] as $id=>$val) {
				$caEntries[$id] = "new"; // add one new entry for each box that is checked
				$csEntries[$id] = $val;
			}
		} else {
			// Default for 'new' and 'new_x' results in the same 'new' value being sent to writeElementValue -- this may have to change if the possible apply to values change as new options are added to the ui
			$caEntries[0] = 'new';
			if($caInline) {
				$csEntriesTemp = explode(",", htmlspecialchars(strip_tags($_POST['caentries'])));
				$csEntries[0] = $csEntriesTemp[0];
			} else {
				$csEntries = $GLOBALS['formulize_selectedEntries'];
			}
		}

		// process changes to each entry
		foreach($caEntries as $id=>$thisEntry) { // loop through all the entries this button click applies to
			$maxIdReq = 0;
			// don't use "i" in this loop, since it's a common variable name and would potentially conflict with names in the eval'd scope
			// same is true of "thisentry" and other variables here!
			for($ixz=0;$ixz<count((array) $clickedElements);$ixz++) { // loop through all actions for this button
				if($thisEntry == "new" AND $maxIdReq > 0) { $thisEntry = $maxIdReq; } // for multiple effects on the same button, when the button applies to a new entry, reuse the initial id_req that was created during the first effect
                $valueToWrite = processButtonValue($clickedValues[$ixz], $csEntries[$id]);
                $formulize_lvoverride = $GLOBALS['formulize_lvoverride'];
				$maxIdReq = writeElementValue("", $clickedElements[$ixz], $thisEntry, $valueToWrite, $clickedActions[$ixz], "", $formulize_lvoverride, $csEntries[$id]);
			}
			if($maxIdReq) {
				$element_handler = xoops_getmodulehandler('elements', 'formulize');
				$elementObject = $element_handler->get($clickedElements[0]);
                // NOTE: if there are derived values involving something other than the fid of the updated form, and the frid of the screen, then they won't be updated when this custom button is clicked!!
                $frid = $screen ? $screen->getVar('frid') : 0;
                formulize_updateDerivedValues($maxIdReq, $elementObject->getVar('id_form'), $frid);
            }
		}
	}
	return $clickedMessageText;
}

// THIS FUNCTION IS USED ONLY IN LIST OF ENTRIES SCREENS, IN THE VALUES OF CUSTOM BUTTONS
// Use this to gather a specified hidden value for the current entry being processed
// The key of $caEntries above MUST be set to the entry that was selected, or else this will not work
// This function is meant to be called from inside the eval call above where the custom buttons are evaluated
// This function is only meant to work with situations where someone has actually selected an entry (or clicked inline)
// THIS FUNCTION DOES NOT WORK PROPERLY IF THE VALUE BEING SELECTED IS AN ARRAY... THE INCLUSION OF THE ARRAYS AS HIDDEN VALUES FAILS
function gatherHiddenValue($handle) {
	global $formulize_thisEntryId;
	if(isset($_POST["hiddencolumn_" . $formulize_thisEntryId . "_" . $handle])) {
		$returnValue = explode('=]-!', $_POST["hiddencolumn_" . $formulize_thisEntryId . "_" . $handle]);
		if($returnValue === false) {
			return false;
		} elseif(count((array) $returnValue)==1) {
			return htmlspecialchars(strip_tags($returnValue[0]));
		} else {
			$cleanValues = array();
			foreach($returnValue as $thisValue) {
				$cleanValues[] = htmlspecialchars(strip_tags($thisValue));
			}
			return $cleanValues;
		}
	} else {
		return false;
	}
}

// THIS FUNCTION GENERATES HTML FOR ANY BUTTONS THAT ARE REQUESTED
function formulize_screenLOEButton($button, $buttonText, $settings, $fid, $frid, $colhandles, $flatcols, $pubstart, $loadOnlyView, $calc_cols, $calc_calcs, $calc_blanks, $calc_grouping, $doNotForceSingle, $lastloaded, $currentview, $endstandard, $pickgroups, $viewoptions, $loadviewname, $advcalc_acid, $screen) {
  static $importExportCleanupDone = false;
	if($buttonText) {
		$buttonText = trans($buttonText);
		switch ($button) {
            case "moreActions":
                return "<input type='button' class='formulize_button' id='formulize_$button' name='moreActions' value='$buttonText' onclick='showMoreActionButtons();'></input>";
                break;
			case "modifyScreenLink":
				$applications_handler = xoops_getmodulehandler('applications', 'formulize');
				$apps = $applications_handler->getApplicationsByForm($screen->getVar('fid'));
				if(is_array($apps) AND count((array) $apps)>0) {
					$firstAppId = $apps[key($apps)]->getVar('appid');
				} else {
					$firstAppId = 0;
				}
                $url = XOOPS_URL . "/modules/formulize/admin/ui.php?page=screen&sid=".$screen->getVar('sid')."&fid=".$screen->getVar('fid')."&aid=".$firstAppId;
				$link = "<a href='".$url."'>" . $buttonText . "</a>";
                global $xoopsTpl;
                $xoopsTpl->assign('modifyScreenUrl', $url);
                return $link;
				break;
			case "changeColsButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=changecols value='" . $buttonText . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/changecols.php?fid=$fid&frid=$frid&cols=$colhandles');\"></input>";
				break;
			case "calcButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=calculations value='" . $buttonText . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/pickcalcs.php?fid=$fid&frid=$frid&calc_cols=".urlencode($calc_cols)."&calc_calcs=".urlencode($calc_calcs)."&calc_blanks=".urlencode($calc_blanks)."&calc_grouping=".urlencode($calc_grouping)."&cols=".urlencode($colhandles)."&cols=".urlencode($colhandles)."');\"></input>";
				break;
			case "proceduresButton":
				// only if any procedures (advanced calculations) are defined for this form
				$procedureHandler = xoops_getmodulehandler('advancedCalculation','formulize');
				$procList = $procedureHandler->getList($fid);
				if(is_array($procList) AND count((array) $procList) > 0) {
				  return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=advcalculations value='" . $buttonText . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/pickadvcalcs.php?fid=$fid&frid=$frid&$advcalc_acid');\"></input>";
				} else {
					return false;
				}
				break;
			case "exportCalcsButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=export value='" . $buttonText . "' onclick=\"javascript:showExport();\"></input>";
				break;
			case "exportButton":
			case "importButton":
				// need to write the query to the cache folder so it can be picked up when needed
				$exportTime = formulize_catchAndWriteExportQuery($fid);
				if($button == "exportButton") {
					return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=export value='" . $buttonText . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/export.php?fid=$fid&frid=$frid&cols=$colhandles&eq=$exportTime');\"></input>";
				} else {
					return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=impdata value='" . $buttonText . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/import.php?fid=$fid&eq=$exportTime');\"></input>";
				}
				break;
			case "addButton":
				$addNewParam = $doNotForceSingle ? "" : "'single'"; // force the addNew behaviour to single entry unless this button is being used on a single entry form, in which case we don't need to force anything
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=addentry value='" . $buttonText . "' onclick=\"javascript:addNew($addNewParam);\"></input>";
				break;
			case "addMultiButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=addentry value='" . $buttonText . "' onclick=\"javascript:addNew();\"></input>";
				break;
			case "addProxyButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=addentry value='" . $buttonText . "' onclick=\"javascript:addNew('proxy');\"></input>";
				break;
			case "notifButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=notbutton value='". $buttonText . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/setnot.php?fid=$fid');\"></input>";
				break;
			case "cloneButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=clonesel value='" . $buttonText . "' onclick=\"javascript:confirmClone();\"></input>";
				break;
			case "deleteButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=deletesel value='" . $buttonText . "' onclick=\"javascript:confirmDel();\"></input>";
				break;
			case "selectAllButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=sellall value='" . $buttonText . "' onclick=selectAll(this.check); ></input>";
				break;
			case "clearSelectButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=clearall value='" . $buttonText . "' onclick=unselectAll(this.uncheck); ></input>";
				break;
			case "resetViewButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=resetviewbutton value='" . $buttonText . "' onclick=\"javascript:showLoadingReset();\"></input>";
				break;
			case "saveViewButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=save value='" . $buttonText . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/save.php?fid=$fid&frid=$frid&lastloaded=$lastloaded&cols=$flatcols&currentview=$currentview&loadonlyview=$loadOnlyView');\"></input>";
				break;
			case "deleteViewButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=delete value='" . $buttonText . "' onclick=\"javascript:delete_view('$pubstart', '$endstandard');\"></input>";
				break;
			case "currentViewList":
				$currentViewList = "<div class='currentViewList'><div class='currentViewList-caption'><b>" . $buttonText . "</b></div><div class='currentViewList-list'><SELECT name=currentview id=currentview size=1 onchange=\"javascript:change_view(this.form, '$pickgroups', '$endstandard');\">\n";
				$currentViewList .= $viewoptions;
				$currentViewList .= "\n</SELECT></div>\n";
				if(!$loadviewname AND strstr($currentview, ",") AND !$loadOnlyView) { // if we're on a genuine pick-groups view (not a loaded view)...and the load-only-view override is not in place (which eliminates other viewing options besides the loaded view)
					$currentViewList .= "<div class='currentViewList-button'><input type=button name=pickdiffgroup value='" . _formulize_DE_PICKDIFFGROUP . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/changescope.php?fid=$fid&frid=$frid&scope=$currentview');\"></input></div>";
				}
                $currentViewList .= '</div>';
				return $currentViewList;
				break;
			case "saveButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=deSaveButton value='" . $buttonText . "' onclick=\"javascript:showLoading();\"></input>";
			case "goButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=deSubmitButton value='" . $buttonText . "' onclick=\"javascript:showLoading();\"></input>";
				break;
			case "globalQuickSearch":
				return "<input type=text id=\"formulize_$button\" name=\"global_search\" value='" . $settings['global_search'] . "' onchange=\"javascript:window.document.controls.ventry.value = '';\"></input>";
				break;
		}
	} elseif($button == "currentViewList") { // must always set a currentview value in POST even if the list is not visible
		return "<input type=hidden name=currentview value='$currentview'></input>\n";
	} else {
		return false;
	}
}

// THIS FUNCTION HANDLES GATHERING A DATASET FOR DISPLAY IN THE LIST
function formulize_gatherDataSet($settings, $searches, $sort, $order, $frid, $fid, $scope, $screen="", $currentURL="", $forcequery = 0) {
    
	if (!is_array($searches))
		$searches = array();

	// setup "flatscope" so we can compare arrays of groups that make up the scope, from page load to pageload
	if(is_array($scope)) {
		$flatscope = serialize($scope);
	} else {
		$flatscope = $scope;
	}

	$showcols = explode(",", $settings['oldcols']);
	if ($settings['global_search']) {
		foreach($showcols as $column) {
			if ($searches[$column]) {
				$searches[$column] .= "//OR" . $settings['global_search'];
			} else {
				$searches[$column] = "OR" . $settings['global_search'];
			}
		}
	}

	$filter = formulize_parseSearchesIntoFilter($searches);
	$filterToCompare = is_array($filter) ? serialize($filter) : $filter;

	$regeneratePageNumbers = false;

  /*
	global $xoopsUser;
	if($xoopsUser) {
		if($xoopsUser->getVar('uid') == 1) {
			print "<br>formulize cacheddata: ". $settings['formulize_cacheddata'];
			print "<br>forcequery: $forcequery";
			print "<br>lastentry: ".$_POST['lastentry'];
			print "<br>deletion requests: ".$GLOBALS['formulize_deletionRequested'];
			print "<br>writeElementValue: ".$GLOBALS['formulize_writeElementValueWasRun'];
			print "<br>filter to compare: ".$filterToCompare;
			print "<br>(different from) previous filter: ".$_POST['formulize_previous_filter'];
			print "<br>flatscope: $flatscope";
			print "<br>(different from: ". $_POST['formulize_previous_scope'];
		}
	}*/



	// if something changed, then we need to redo the page numbers
	if(!isset($_POST['lastentry']) AND (($query_string != $_POST['formulize_previous_querystring'] AND $query_string != "") OR $filterToCompare != $_POST['formulize_previous_filter'] OR $flatscope != $_POST['formulize_previous_scope'])) {
			$regeneratePageNumbers = true;
		}
	$formulize_LOEPageSize = is_object($screen) ? $screen->getVar('entriesperpage') : 10;
    $formulize_LOEPageSize = isset($_POST['formulize_entriesPerPage']) ? intval($_POST['formulize_entriesPerPage']) : $formulize_LOEPageSize;
	if($formulize_LOEPageSize) {
	  $limitStart = (isset($_POST['formulize_LOEPageStart']) AND !$regeneratePageNumbers) ? intval($_POST['formulize_LOEPageStart']) : 0;
	  $limitSize = $formulize_LOEPageSize;
	} else {
	  $limitStart = 0;
	  $limitSize = 0;
	}
	//print "limitStart: $limitStart<br>limitSize: $limitSize<br>";

		$GLOBALS['formulize_getCountForPageNumbers'] = true; // flag used to trigger setting of the count of entries in the dataset
        $GLOBALS['formulize_setBaseQueryForCalcs'] = true; // flag used to trigger setting of the basequery for calculations
        $GLOBALS['formulize_setQueryForExport'] = true;
        if($screen) {
            $fundamental_filters = $screen->getVar('fundamental_filters');
            if(is_array($fundamental_filters) AND count($fundamental_filters)>0) {
                $filter = array('fundamental_filters'=>$fundamental_filters, 'active_filters'=>$filter);
            }
        }
		$data = getData($frid, $fid, $filter, "AND", $scope, $limitStart, $limitSize, $sort, $order, $forcequery);

		// if we deleted entries and the current page is now empty, then shunt back 1 page
		if(count((array) $data)==0 AND $_POST['delconfirmed'] AND $limitStart > 0) {
			$_POST['formulize_LOEPageStart'] = $_POST['formulize_LOEPageStart']-$formulize_LOEPageSize;
			$data = getData($frid, $fid, $filter, "AND", $scope, ($limitStart-$formulize_LOEPageSize), $limitSize, $sort, $order, $forcequery);    
		}
		
	if($currentURL=="") { return array(0=>"", 1=>"", 2=>""); } //current URL should only be "" if this is called directly by the special formulize_getCalcs function

	// must start drawing interface here, since we need to include those hidden form elements below...
	$drawResetForm = true;
	$useWorking = true;
	if($screen) {
		$drawResetForm = $screen->getVar('usereset') == "" ? false : true;
		$useWorking = !$screen->getVar('useworkingmsg') ? false : true;
	}

	if($drawResetForm) {
		$currentviewResetForm = $settings['currentview'];
		print "<form name=resetviewform id=resetviewform action=$currentURL method=post onsubmit=\"javascript:showLoading();\">\n";
		if($screen) { $currentviewResetForm = getDefaultViewForActiveUser($screen->getVar('defaultview')); } // override the default set by $settings...must do this here and not above, since this should only apply to the resetview form
		print "<input type=hidden name=currentview value='$currentviewResetForm'>\n";
		print "<input type=hidden name=userClickedReset value=1>\n";
		print "</form>\n";
	}

	if($useWorking) {
		// working message
		global $xoopsConfig;
		print "<div id=workingmessage style=\"display: none; position: fixed; right: 45%; top: 45%; text-align: center; padding-top: 50px; z-index: 100;\">\n";
		if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/images/working-".$xoopsConfig['language'].".gif") ) {
			print "<img src=\"" . XOOPS_URL . "/modules/formulize/images/working-" . $xoopsConfig['language'] . ".gif\">\n";
		} else {
			print "<img src=\"" . XOOPS_URL . "/modules/formulize/images/working-english.gif\">\n";
		}
		print "</div>\n";
	}

	print "<div id=listofentries>\n";

	print "<form name=controls id=controls autocomplete='off' action=$currentURL method=post onsubmit=\"javascript:showLoading();\">\n";
	if(isset($GLOBALS['xoopsSecurity'])) {
		print $GLOBALS['xoopsSecurity']->getTokenHTML();
	}

	print "<input type=hidden name=formulize_cacheddata id=formulize_cacheddata value=\"$formulize_cachedDataId\">\n"; // set the cached data id that we might want to read on next page load
	print "<input type=hidden name=formulize_previous_filter id=formulize_previous_filter value=\"" . htmlSpecialChars($filterToCompare) . "\">\n"; // save the filter to check for a change on next page load
	print "<input type=hidden name=formulize_previous_scope id=formulize_previous_scope value=\"" . htmlSpecialChars($flatscope) . "\">\n"; // save the scope to check for a change on next page load
	print "<input type=hidden name=formulize_previous_sort id=formulize_previous_sort value=\"$sort\">\n";
	print "<input type=hidden name=formulize_previous_order id=formulize_previous_order value=\"$order\">\n";
	print "<input type=hidden name=formulize_previous_querystring id=formulize_previous_querystring value=\"" . htmlSpecialChars($query_string). "\">\n";

	$to_return[0] = $data;
	$to_return[1] = $regeneratePageNumbers;
	return $to_return;
}

// THIS FUNCTION CALCULATES THE NUMBER OF PAGES AND DRAWS HTML FOR NAVIGATING THEM
function formulize_LOEbuildPageNav($data, $screen, $regeneratePageNumbers) {
	if(!is_array($data)) {
		// $data can now be a flag that says "Limit Reached"
		$data = array();
	}

    // setup default navigation - and in Anari theme, put in a hack to extend the height of the scrollbox if necessary -- need to rebuild markup for list so this kind of thing is not necessary!
    $pageNav = "";
    
    if($_POST['hlist']) {
		// return no navigation controls if the list is hidden.
		return $pageNav;
	}
    
    global $xoopsConfig;
    if($xoopsConfig['theme_set']=='Anari') {
        $pageNav = "<script type='text/javascript'>
    jQuery(document).ready(function() {
        jQuery('.scrollbox').css('height','100%');
    });
</script>";
    }   
    
	$numberPerPage = is_object($screen) ? $screen->getVar('entriesperpage') : 10;
    $numberPerPage = isset($_POST['formulize_entriesPerPage']) ? intval($_POST['formulize_entriesPerPage']) : $numberPerPage; 
	
	// regenerate essentially causes the user to jump back to page 0 because something about the dataset has fundamentally changed (like a new search term or something)
	$currentPage = (isset($_POST['formulize_LOEPageStart']) AND !$regeneratePageNumbers) ? intval($_POST['formulize_LOEPageStart']) : 0;
    $userPageNumber = $currentPage > 0 ? ($currentPage / $numberPerPage) + 1 : 1;
    
    $lastEntryNumber = $numberPerPage > 0 ? $numberPerPage*($userPageNumber) : $GLOBALS['formulize_countMasterResultsForPageNumbers'];
    $lastEntryNumber = $lastEntryNumber > $GLOBALS['formulize_countMasterResultsForPageNumbers'] ? $GLOBALS['formulize_countMasterResultsForPageNumbers'] : $lastEntryNumber;
    $firstEntryNumber = $GLOBALS['formulize_countMasterResultsForPageNumbers'] > 0 ? ((($userPageNumber-1)*$numberPerPage)+1) : 0;
	$entryTotals = "<span class=\"page-navigation-total\">".
        sprintf(_AM_FORMULIZE_LOE_TOTAL, $firstEntryNumber, $lastEntryNumber, $GLOBALS['formulize_countMasterResultsForPageNumbers'])."</span></p>\n";

    if($numberPerPage > 0) {
        // will receive via javascript the page number that was clicked, or will cause the current page to reload if anything else happens
        print "\n<input type=hidden name=formulize_LOEPageStart id=formulize_LOEPageStart value=\"$currentPage\">\n";
        $allPageStarts = array();
        $pageNumbers = 0;
        for($i = 0; $i < $GLOBALS['formulize_countMasterResultsForPageNumbers']; $i = $i + $numberPerPage) {
            $pageNumbers++;
            $allPageStarts[$pageNumbers] = $i;
        }
        
        if($pageNumbers > 1) {
            if($pageNumbers > 9) {
                if($userPageNumber < 6) {
                    $firstDisplayPage = 1;
                    $lastDisplayPage = 9;
                } elseif($userPageNumber + 4 > $pageNumbers) { // too close to the end
                    $firstDisplayPage = $userPageNumber - 4 - ($userPageNumber+4-$pageNumbers); // the previous four, plus the difference by which we're over the end when we add 4
                    $lastDisplayPage = $pageNumbers;
                } else { // somewhere in the middle
                    $firstDisplayPage = $userPageNumber - 4;
                    $lastDisplayPage = $userPageNumber + 4;
                }
            } else {
                $firstDisplayPage = 1;
                $lastDisplayPage = $pageNumbers;
            }
    
            $pageNav = "<p></p><div class=\"formulize-page-navigation\"><span class=\"page-navigation-label\">". _AM_FORMULIZE_LOE_ONPAGE."</span>";
            if ($currentPage > 1) {
                $pageNav .= "<a href=\"\" class=\"page-navigation-prev\" onclick=\"javascript:pageJump('".($currentPage - $numberPerPage)."');return false;\">"._AM_FORMULIZE_LOE_PREVIOUS."</a>";
            }
            if($firstDisplayPage > 1) {
                $pageNav .= "<a href=\"\" onclick=\"javascript:pageJump('0');return false;\">1</a><span class=\"page-navigation-skip\">—</span>";
            }
            for($i = $firstDisplayPage; $i <= $lastDisplayPage; $i++) {
                $thisPageStart = ($i * $numberPerPage) - $numberPerPage;
                if($thisPageStart == $currentPage) {
                    $pageNav .= "<a href=\"\" class=\"page-navigation-active\" onclick=\"javascript:pageJump('$thisPageStart');return false;\">$i</a>";
                } else {
                    $pageNav .= "<a href=\"\" onclick=\"javascript:pageJump('$thisPageStart');return false;\">$i</a>";
                }
            }
            if($lastDisplayPage < $pageNumbers) {
                $lastPageStart = ($pageNumbers * $numberPerPage) - $numberPerPage;
                $pageNav .= "<span class=\"page-navigation-skip\">—</span><a href=\"\" onclick=\"javascript:pageJump('$lastPageStart');return false;\">" . $pageNumbers . "</a>";
            }
            if ($currentPage < ($GLOBALS['formulize_countMasterResultsForPageNumbers'] - $numberPerPage)) {
                $pageNav .= "<a href=\"\" class=\"page-navigation-next\" onclick=\"javascript:pageJump('".($currentPage + $numberPerPage)."');return false;\">"._AM_FORMULIZE_LOE_NEXT."</a>";
            }
            $pageNav .= "</div>";
        }
    }
    
	return array($pageNav,$entryTotals);
}


// this function extracts the handles from a string (template)
function extractHandles($filterTypes, $templateString) {
	// generate a string containing search boxes to match for
	$searchString = implode("|", $filterTypes);

	// match all the search box prefix and their handlers
	// example: preg_match_all('/(\$quickDateRange|\$quickFilter)[a-zA-Z0-9_]+/', $screen->getTemplate('toptemplate'), $handlerOutArray);
	preg_match_all('/('.$searchString.')[a-zA-Z0-9_]+/', $templateString, $result);

	// remove all the searchbox prefix
	$handles = $result[0];
	if(is_array($handles)) {
	$handles = preg_replace('/('.$searchString.')/', '', $handles);
		return $handles;
	} else {
		return array();
	}

}

// this function unpacks the defaultview property of the screen object, and determines which one applies to the current user, if any
// first param is the defaultview property from the screen object
function getDefaultViewForActiveUser($loadview) {
	global $xoopsUser;
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
	$foundAView = false;
	// Search for group user belongs to in list of default views
	foreach(array_keys((array)$loadview) as $checkGroup) {
		// First group/default view found that user belongs to will be set
		if(in_array($checkGroup, $groups)) {
		  $loadview = $loadview[$checkGroup];
		  $foundAView = true;
		  break;
		}
	  }
	if(!$foundAView) {
	  $loadview = null;
	}
	return $loadview;
	
} 

