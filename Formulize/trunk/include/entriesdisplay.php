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
		} else { // if there is no security token, then assume true -- necessary for old versions of XOOPS.
			$formulize_LOESecurityPassed = true;
		}
	}

	// check for all necessary permissions
	$add_own_entry = $gperm_handler->checkRight("add_own_entry", $fid, $groups, $mid);
	$delete_own_reports = $gperm_handler->checkRight("delete_own_reports", $fid, $groups, $mid);
	$delete_other_reports = $gperm_handler->checkRight("delete_other_reports", $fid, $groups, $mid);
	$delete_own_entry = $gperm_handler->checkRight("delete_own_entry", $fid, $groups, $mid);
	$delete_other_entries = $gperm_handler->checkRight("delete_other_entries", $fid, $groups, $mid);
	$update_other_reports = $gperm_handler->checkRight("update_other_reports", $fid, $groups, $mid);
	$update_own_reports = $gperm_handler->checkRight("update_own_reports", $fid, $groups, $mid);
	$view_globalscope = $gperm_handler->checkRight("view_globalscope", $fid, $groups, $mid);
	$view_groupscope = $gperm_handler->checkRight("view_groupscope", $fid, $groups, $mid);

	// Question:  do we need to add check here to make sure that $loadview is an available report (move function call from the generateViews function) and if it is not, then nullify
	// we may want to be able to pass in any old report, it's kind of like a way to override the publishing process.  Problem is unpublished reports or reports that aren't actually published to the user won't show up in the list of views.
	// [update: loaded views do not include the list of views, they have no interface at all except quick searches and quick sorts.  Since the intention is clearly for them to be accessed through pageworks, we will leave the permission control up to the application designer for now]

	$currentURL = getCurrentURL();

	// get title
	$displaytitle = getFormTitle($fid);

	// get default info and info passed to page....

	// clear any default search text that has been passed (because the user didn't actually search for anything)
	foreach($_POST as $k=>$v) {
		if(substr($k, 0, 7) == "search_" AND $v==_formulize_DE_SEARCH_HELP) {
			unset($_POST[$k]);
			break; // assume this is only sent once, since the help text only appears in the first column
		}
	}

	// check for deletion request and then delete entries...must check security first!
	if($_POST['delconfirmed'] AND $formulize_LOESecurityPassed AND ($delete_own_entry OR $delete_other_entries)) { // only gets set by clicking on the delete selected button
		foreach($_POST as $k=>$v) {
			if(substr($k, 0, 7) == "delete_" AND $v != "") {
				$thisentry = substr($k, 7);
				if($delete_other_entries OR $xoopsUser->getVar('uid') == getEntryOwner($thisentry, $fid)) { // if they only have "delete own" then only proceed if this is their entry
					$GLOBALS['formulize_deletionRequested'] = true;
					// new syntax for deleteEntry, Sept 18 2005 -- used to handle deleting all unified display entries that are linked to this entry.  
					if($frid) {
						deleteEntry($thisentry, $frid, $fid, $gperm_handler, $member_handler, $mid);
					} else {
						deleteEntry($thisentry, "", $fid);
					}
				}
			}
		}
	}

	// check for cloning request and if present then clone entries
	if($_POST['cloneconfirmed'] AND $formulize_LOESecurityPassed AND $add_own_entry) {
//		print $_POST['cloneconfirmed'];
		foreach($_POST as $k=>$v) {
			if(substr($k, 0, 7) == "delete_" AND $v != "") {
				$thisentry = substr($k, 7);
				cloneEntry($thisentry, $frid, $fid, $_POST['cloneconfirmed']); // cloneconfirmed is the number of copies required  
			}
		}
	}


	// handle deletion of view...reset currentView
	if($_POST['delview'] AND $formulize_LOESecurityPassed AND ($delete_other_reports OR $delete_own_reports)) {
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
		if(get_magic_quotes_gpc()) {
			$savename = stripslashes($savename);
		}
		

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

		$savename = mysql_real_escape_string($savename);
		$savesearches = mysql_real_escape_string($_POST['asearch']);
		//print $_POST['asearch'] . "<br>";
		//print "$savesearches<br>";
		$qsearches = mysql_real_escape_string($qsearches);

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
			$savesql = "INSERT INTO " . $xoopsDB->prefix("formulize_saved_views") . " (sv_name, sv_pubgroups, sv_owner_uid, sv_mod_uid, sv_formframe, sv_mainform, sv_lockcontrols, sv_hidelist, sv_hidecalc, sv_asearch, sv_sort, sv_order, sv_oldcols, sv_currentview, sv_calc_cols, sv_calc_calcs, sv_calc_blanks, sv_calc_grouping, sv_quicksearches) VALUES (\"$savename\", \"$savegroups\", \"$owneruid\", \"$moduid\", \"$saveformframe\", \"$savemainform\", \"{$_POST['savelock']}\", \"{$_POST['hlist']}\", \"{$_POST['hcalc']}\", \"$savesearches\", \"{$_POST['sort']}\", \"{$_POST['order']}\", \"{$_POST['oldcols']}\", \"{$_POST['savescope']}\", \"{$_POST['calc_cols']}\", \"{$_POST['calc_calcs']}\", \"{$_POST['calc_blanks']}\", \"{$_POST['calc_grouping']}\", \"$qsearches\")";
		} else {
			// print "UPDATE " . $xoopsDB->prefix("formulize_saved_views") . " SET sv_pubgroups=\"$savegroups\", sv_mod_uid=\"$uid\", sv_lockcontrols=\"{$_POST['savelock']}\", sv_hidelist=\"{$_POST['hlist']}\", sv_hidecalc=\"{$_POST['hcalc']}\", sv_asearch=\"$savesearches\", sv_sort=\"{$_POST['sort']}\", sv_order=\"{$_POST['order']}\", sv_oldcols=\"{$_POST['oldcols']}\", sv_currentview=\"{$_POST['savescope']}\", sv_calc_cols=\"{$_POST['calc_cols']}\", sv_calc_calcs=\"{$_POST['calc_calcs']}\", sv_calc_blanks=\"{$_POST['calc_blanks']}\", sv_calc_grouping=\"{$_POST['calc_grouping']}\", sv_quicksearches=\"$qsearches\" WHERE sv_id = \"" . substr($saveid_formulize, 1) . "\"";
			$savesql = "UPDATE " . $xoopsDB->prefix("formulize_saved_views") . " SET sv_name=\"$savename\", sv_pubgroups=\"$savegroups\", sv_mod_uid=\"$uid\", sv_lockcontrols=\"{$_POST['savelock']}\", sv_hidelist=\"{$_POST['hlist']}\", sv_hidecalc=\"{$_POST['hcalc']}\", sv_asearch=\"$savesearches\", sv_sort=\"{$_POST['sort']}\", sv_order=\"{$_POST['order']}\", sv_oldcols=\"{$_POST['oldcols']}\", sv_currentview=\"{$_POST['savescope']}\", sv_calc_cols=\"{$_POST['calc_cols']}\", sv_calc_calcs=\"{$_POST['calc_calcs']}\", sv_calc_blanks=\"{$_POST['calc_blanks']}\", sv_calc_grouping=\"{$_POST['calc_grouping']}\", sv_quicksearches=\"$qsearches\" WHERE sv_id = \"" . substr($saveid_formulize, 1) . "\"";
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
		if($loadview == "mine" OR $loadview == "group" OR $loadview == "all" OR ($loadview == "blank" AND (!isset($_POST['hlist']) AND !isset($_POST['hcalc'])))) { // only pay attention to the "blank" default list if we are on an initial page load, ie: no hcalc or hlist is set yet, and one of those is set on each page load hereafter
			$currentView = $loadview; // if the default is a standard view, then use that instead and don't load anything
			unset($loadview);
		} elseif($_POST['userClickedReset']) { // only set if the user actually clicked that button, and in that case, we want to be sure we load the default as specified for the screen
			$forceLoadView = true; 
		}
	}
		
	// set currentView to group if they have groupscope permission (overridden below by value sent from form)
	// override with loadview if that is specified
  
	if($loadview AND ((!$_POST['currentview'] AND $_POST['advscope'] == "") OR $forceLoadView)) {
		if(substr($loadview, 0, 4) == "old_") { // this is a legacy view
			$loadview = "p" . $loadview;
		} elseif(is_numeric($loadview)) { // new view id
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
		
	// debug block to show key settings being passed back to the page
/*
	if($uid == 1) {
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



	// get control settings passed from form 

	// handling change in view, and loading reports/saved views if necessary
	if($_POST['loadreport']) {
		if(substr($_POST['currentview'], 1, 4) == "old_") { // legacy report
			// load old report values and then assign them to the correct $_POST keys in order to present the view
			$loadedView = $_POST['currentview'];
			$settings['loadedview'] = $loadedView;
			// kill the quicksearches
			foreach($_POST as $k=>$v) {
				if(substr($k, 0, 7) == "search_" AND $v != "") {
					unset($_POST[$k]);
				}
			}

			list($_POST['currentview'], $_POST['oldcols'], $_POST['asearch'], $_POST['calc_cols'], $_POST['calc_calcs'], $_POST['calc_blanks'], $_POST['calc_grouping'], $_POST['sort'], $_POST['order'], $_POST['hlist'], $_POST['hcalc'], $_POST['lockcontrols']) = loadOldReport(substr($_POST['currentview'], 5), $fid, $view_groupscope);

		} elseif(is_numeric(substr($_POST['currentview'], 1))) { // saved or published view
			$loadedView = $_POST['currentview'];
			$settings['loadedview'] = $loadedView;
			// kill the quicksearches
			foreach($_POST as $k=>$v) {
				if(substr($k, 0, 7) == "search_" AND $v != "") {
					unset($_POST[$k]);
				}
			}
			list($_POST['currentview'], $_POST['oldcols'], $_POST['asearch'], $_POST['calc_cols'], $_POST['calc_calcs'], $_POST['calc_blanks'], $_POST['calc_grouping'], $_POST['sort'], $_POST['order'], $_POST['hlist'], $_POST['hcalc'], $_POST['lockcontrols'], $quicksearches) = loadReport(substr($_POST['currentview'], 1), $fid, $frid);
			// explode quicksearches into the search_ values
			$allqsearches = explode("&*=%4#", $quicksearches);
			$colsforsearches = explode(",", $_POST['oldcols']);
			for($i=0;$i<count($allqsearches);$i++) {
				if($allqsearches[$i] != "") {
					$_POST["search_" . str_replace("hiddencolumn_", "", dealWithDeprecatedFrameworkHandles($colsforsearches[$i], $frid))] = $allqsearches[$i]; // need to remove the hiddencolumn indicator if it is present
					if(strstr($colsforsearches[$i], "hiddencolumn_")) {
						unset($colsforsearches[$i]); // remove columns that were added to the column list just so we would know the name of the hidden searches
					}
				}
			}
			$_POST['oldcols'] = implode(",",$colsforsearches); // need to reconstruct this in case any columns were removed because of persistent searches on a hidden column
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
		
		// if there is a screen with a top template in effect, then do not lock the controls even if the saved view says we should.  Assume that the screen author has compensated for any permission issues.
		// we need to do this after rachetting down the visibility controls.  Fact is, controlling UI for users is one thing that we can trust the screen author to do, so we don't need to indicate that the controls are locked.  But we don't want the visibility to override what people can normally see, so we rachet that down above.
    if($screen AND $_POST['lockcontrols']) {
      if($screen->getVar('toptemplate') != "") {
        $_POST['lockcontrols'] = 0;
      }
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
	
	// convert framework handles to element handles if necessary
	$showcols = dealWithDeprecatedFrameworkHandles($showcols, $frid);	

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


	list($scope, $currentView) = buildScope($currentView, $member_handler, $gperm_handler, $uid, $groups, $fid, $mid);
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
	foreach($_POST as $k=>$v) {
		if(substr($k, 0, 7) == "search_" AND !in_array(substr($k, 7), $showcols)) {
			if(substr($v, 0, 1) == "!" AND substr($v, -1) == "!") {// don't strip searches that have ! at front and back
				$hiddenQuickSearches[] = substr($k, 7);
				continue; // since the { } replacement is meant for the ease of use of non-admin users, and hiddenQuickSearches never show up to users on screen, we can skip the potentially expensive operations below in this loop
			} else {
				unset($_POST[$k]);
			}
		}
		// if this is not a report/view that was created by the user, and they don't have update permission, then convert any { } terms to literals
		if(strstr($v, "{") AND strstr($v, "}")) {
			$activeViewId = substr($settings['lastloaded'], 1); // will have a p in front of the number, to show it's a published view (or an s, but that's unlikely to ever happen in this case)
			$ownerOfLastLoadedViewData = q("SELECT sv_owner_uid FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_id=".intval($activeViewId));
			$ownerOfLastLoadedView = $ownerOfLastLoadedViewData[0]['sv_owner_uid'];
			if(!$update_other_reports AND $uid != $ownerOfLastLoadedView) {
				$requestKeyToUse = substr($v,1,-1);
				if(isset($_POST[$requestKeyToUse])) {
					$_POST[$k] = $_POST[$requestKeyToUse];
				} elseif(isset($_GET[$requestKeyToUse])) {
					$_POST[$k] = $_GET[$requestKeyToUse];
				} elseif($v == "{USER}" AND $xoopsUser) {
					$_POST[$k] = $xoopsUser->getVar('name') ? $xoopsUser->getVar('name') : $xoopsUser->getVar('uname');
				}
			}
		}
	}

	$settings['currentview'] = $currentView;

	$settings['currentURL'] = $currentURL; 

	// no need for both these values now, since framework handles are deprecated
	$settings['columns'] = $showcols;
	$settings['columnhandles'] = $showcols;
		
	$settings['hlist'] = $_POST['hlist'];
	$settings['hcalc'] = $_POST['hcalc'];

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

	$settings['ventry'] = $_POST['ventry'];

	// get sort and order options

	$_POST['sort'] = dealWithDeprecatedFrameworkHandles($_POST['sort'], $frid);
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

	// gather id of the cached data, if any
	$settings['formulize_cacheddata'] = strip_tags($_POST['formulize_cacheddata']);

	if($_POST['ventry']) { // user clicked on a view this entry link
		include_once XOOPS_ROOT_PATH . '/modules/formulize/include/formdisplay.php';

		if($_POST['ventry'] == "addnew" OR $_POST['ventry'] == "single") {
			$this_ent = "";
		} elseif($_POST['ventry'] == "proxy") {
			$this_ent = "proxy";
		} else {
			$this_ent = $_POST['ventry'];
		}

		if(($screen AND $screen->getVar("viewentryscreen") != "none" AND $screen->getVar("viewentryscreen")) OR $_POST['overridescreen']) {
      if(strstr($screen->getVar("viewentryscreen"), "p")) { // if there's a p in the specified viewentryscreen, then it's a pageworks page -- added April 16 2009 by jwe
        $page = intval(substr($screen->getVar("viewentryscreen"), 1));
        include XOOPS_ROOT_PATH . "/modules/pageworks/index.php";
        return;
      } else {
				$screen_handler = xoops_getmodulehandler('screen', 'formulize');
				if($_POST['overridescreen']) {
					$screenToLoad = intval($_POST['overridescreen']);
				} else {
					$screenToLoad = intval($screen->getVar('viewentryscreen'));
				}

				$viewEntryScreenObject = $screen_handler->get($screenToLoad);
				if($viewEntryScreenObject->getVar('type')=="listOfEntries") {
					exit("You're sending the user to a list of entries screen instead of some kind of form screen, when they're editing an entry.  Check what screen is defined as the screen to use for editing an entry, or what screen id you're using in the viewEntryLink or viewEntryButton functions in the template.");
				}
				$viewEntryScreen_handler = xoops_getmodulehandler($viewEntryScreenObject->getVar('type').'Screen', 'formulize');
  			$displayScreen = $viewEntryScreen_handler->get($viewEntryScreenObject->getVar('sid'));
				if($displayScreen->getVar('type')=="form") {
					if($_POST['ventry'] != "single") {
						$displayScreen->setVar('reloadblank', 1); // if the user clicked the add multiple button, then specifically override that screen setting so they can make multiple entries
					} else {
						$displayScreen->setVar('reloadblank', 0); // otherwise, if they did click the single button, make sure the form reloads with their entry
					}
				}
  			$viewEntryScreen_handler->render($displayScreen, $this_ent, $settings);
			global $renderedFormulizeScreen; // picked up at the end of initialize.php so we set the right info in the template when the whole page is rendered
			$renderedFormulizeScreen = $displayScreen;
			return;
      }
		} else {

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
			
		} // end of "if there's a viewentryscreen, then show that"
	
	} 

	// check if we're coming back from a page where a form entry was saved, and if so, synch any subform blanks that might have been written on this page load, synch them with the mainform entry that was written
	$formToSynch = isset($_POST['primaryfid']) ? intval($_POST['primaryfid']) : 0;
	if($formToSynch) {
		if(isset($_POST['entry'.$formToSynch]) AND $enryToSynch = $_POST['entry'.$formToSynch]) {
			synchSubformBlankDefaults($formToSynch, $entryToSynch);
		}
	}	
	
	// process a clicked custom button
	// must do this before gathering the data!
	$messageText = "";
	if(isset($_POST['caid']) AND $screen AND $formulize_LOESecurityPassed) {
		$customButtonDetails = $screen->getVar('customactions');
		if(is_numeric($_POST['caid']) AND isset($customButtonDetails[$_POST['caid']])) {
			list($caCode, $caElements, $caActions, $caValues, $caMessageText, $caApplyTo, $caPHP, $caInline) = processCustomButton($_POST['caid'], $customButtonDetails[$_POST['caid']]); // just processing to get the info so we can process the click.  Actual output of this button happens lower down
			$messageText = processClickedCustomButton($caElements, $caValues, $caActions, $caMessageText, $caApplyTo, $caPHP, $caInline);
		}
	}

	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
	// create $data and $wq (writable query)
  formulize_benchmark("before gathering dataset");
	list($data, $wq, $regeneratePageNumbers) = formulize_gatherDataSet($settings, $searches, strip_tags($_POST['sort']), strip_tags($_POST['order']), $frid, $fid, $scope, $screen, $currentURL, intval($_POST['forcequery']));
  formulize_benchmark("after gathering dataset/before generating nav");
	$formulize_LOEPageNav = formulize_LOEbuildPageNav($data, $screen, $regeneratePageNumbers);
  formulize_benchmark("after nav/before interface");
	$formulize_buttonCodeArray = array();
	list($formulize_buttonCodeArray) = drawInterface($settings, $fid, $frid, $groups, $mid, $gperm_handler, $loadview, $loadOnlyView, $screen, $searches, $formulize_LOEPageNav, $messageText, $hiddenQuickSearches);

	// if there is messageText and no custom top template, and no messageText variable in the bottom template, then we have to output the message text here
	if($screen AND $messageText) {
		if(trim($screen->getVar('toptemplate')) == "" AND !strstr($screen->getVar('bottomtemplate'), 'messageText')) {
			print "<p><center><b>$messageText</b></center></p>\n";
		}
	}

  formulize_benchmark("before entries");
	drawEntries($fid, $showcols, $searches, $frid, $scope, "", $currentURL, $gperm_handler, $uid, $mid, $groups, $settings, $member_handler, $screen, $data, $wq, $regeneratePageNumbers, $hiddenQuickSearches); // , $loadview); // -- loadview not passed any longer since the lockcontrols indicator is used to handle whether things should appear or not.
  formulize_benchmark("after entries");

	if($screen) {
		formulize_screenLOETemplate($screen, "bottom", $formulize_buttonCodeArray, $settings);
	} else {
		print $formulize_LOEPageNav; // redraw page numbers if there is no screen in effect
	}
	if(isset($formulize_buttonCodeArray['submitButton'])) { // if a custom top template was in effect, this will have been sent back, so now we display it at the very bottom of the form so it doesn't take up a visible amount of space above (the submitButton is invisible, but does take up space)
		print "<p class=\"formulize_customTemplateSubmitButton\">" . $formulize_buttonCodeArray['submitButton'] . "</p>";
	}
	print "</form>\n"; // end of the form started in drawInterface

	print "</div>\n"; // end of the listofentries div, used to call up the working message when the page is reloading, started in drawInterface


}

// return the available current view settings based on the user's permissions
function generateViews($fid, $uid, $groups, $frid="0", $currentView, $loadedView="", $view_groupscope, $view_globalscope, $prevview="", $loadOnlyView=0, $screen, $lastLoaded) {
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
		if((count($s_reports)>0 OR count($ns_reports)>0) AND !$limitViews) { // we have saved reports...
			$options .= "<option value=\"\">" . _formulize_DE_SAVED_VIEWS . "</option>\n";
			$vcounter++;
		}
		for($i=0;$i<count($s_reports);$i++) {
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
		for($i=0;$i<count($ns_reports);$i++) {
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
	

	if((count($p_reports)>0 OR count($np_reports)>0) AND !$limitViews) { // we have saved reports...
		$options .= "<option value=\"\">" . _formulize_DE_PUB_VIEWS . "</option>\n";
		$vcounter++;
	}
	$firstPublishedView = $vcounter + 1;
	if(!$limitViews) { // old reports are not selectable in the screen UI so will never be in the limit list
		for($i=0;$i<count($p_reports);$i++) {
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
	for($i=0;$i<count($np_reports);$i++) {
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
function drawInterface($settings, $fid, $frid, $groups, $mid, $gperm_handler, $loadview="", $loadOnlyView=0, $screen, $searches, $pageNav, $messageText, $hiddenQuickSearches) {

	global $xoopsDB;
	// unpack the $settings
	foreach($settings as $k=>$v) {
		${$k} = $v;
	}
	
	
	
	// get single/multi entry status of this form...
	$singleMulti = q("SELECT singleentry FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form = $fid");
		
	// flatten columns array and convert handles to ids so that we can send them to the change columns popup
	// Since 4.0 columns and columnhandles are identical...this is a cleanup job for later
	$colhandles = implode(",", $columnhandles); // part of $settings 
	$flatcols = implode(",", $columns); // part of $settings (will be IDs if no framework in effect)

	$useWorking = true;
	$useDefaultInterface = true;
	$useSearch = 1;
	if($screen) {
		$useWorking = !$screen->getVar('useworkingmsg') ? false : true;
		$useDefaultInterface = $screen->getVar('toptemplate') != "" ? false : true;
		$title = $screen->getVar('title'); // otherwise, title of the form is in the settings array for when no screen is in use
		$useSearch = ($screen->getVar('usesearch') AND !$screen->getVar('listtemplate')) ? 1 : 0;
	}
	
	if(strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
		$submitButton = "<input type=submit name=submitx style=\"width:0px; height:0px; border-width: 0px; padding: 0px;\" value='' ></input>\n";
	} else {
		$submitButton =  "<input type=submit name=submitx style=\"visibility: hidden;\" value='' ></input>\n";
	}

	// need to establish these here because they are used in conditions lower down
	$add_own_entry = $gperm_handler->checkRight("add_own_entry", $fid, $groups, $mid);
	$proxy = $gperm_handler->checkRight("add_proxy_entries", $fid, $groups, $mid);
	$del_own = $gperm_handler->checkRight("delete_own_entry", $fid, $groups, $mid);
	$del_others = $gperm_handler->checkRight("delete_other_entries", $fid, $groups, $mid);

	// establish text and code for buttons, whether a screen is in effect or not
	$screenButtonText = array();
	$screenButtonText['changeColsButton'] = _formulize_DE_CHANGECOLS;
	$screenButtonText['calcButton'] = _formulize_DE_CALCS;
	$screenButtonText['advCalcButton'] = _formulize_DE_ADVCALCS;
	$screenButtonText['advSearchButton'] = _formulize_DE_ADVSEARCH;
	$screenButtonText['exportButton'] = _formulize_DE_EXPORT;
	$screenButtonText['exportCalcsButton'] = _formulize_DE_EXPORT_CALCS;
	$screenButtonText['importButton'] = _formulize_DE_IMPORTDATA;
	$screenButtonText['notifButton'] = _formulize_DE_NOTBUTTON;
	$screenButtonText['cloneButton'] = _formulize_DE_CLONESEL;
	$screenButtonText['deleteButton'] = _formulize_DE_DELETESEL;
	$screenButtonText['selectAllButton'] = _formulize_DE_SELALL;
	$screenButtonText['clearSelectButton'] = _formulize_DE_CLEARALL;
	$screenButtonText['resetViewButton'] = _formulize_DE_RESETVIEW;
	$screenButtonText['saveViewButton'] = _formulize_DE_SAVE;
	$screenButtonText['deleteViewButton'] = _formulize_DE_DELETE;
	$screenButtonText['currentViewList'] = _formulize_DE_CURRENT_VIEW;
	$screenButtonText['saveButton'] = _formulize_SAVE;
	$screenButtonText['addButton'] = $singleMulti[0]['singleentry'] == "" ? _formulize_DE_ADDENTRY : _formulize_DE_UPDATEENTRY;
	$screenButtonText['addMultiButton'] = _formulize_DE_ADD_MULTIPLE_ENTRY;
	$screenButtonText['addProxyButton'] = _formulize_DE_PROXYENTRY;
	if($screen) {
		if($add_own_entry) {
			$screenButtonText['addButton'] = $screen->getVar('useaddupdate');
			$screenButtonText['addMultiButton'] = $screen->getVar('useaddmultiple');
		} else {
			$screenButtonText['addButton'] = "";
			$screenButtonText['addMultiButton'] = "";
		}
		if($proxy) {
			$screenButtonText['addProxyButton'] = $screen->getVar('useaddproxy');
		} else {
			$screenButtonText['addProxyButton'] = "";
		}
		$screenButtonText['exportButton'] = $screen->getVar('useexport');
		$screenButtonText['importButton'] = $screen->getVar('useimport');
		$screenButtonText['notifButton'] = $screen->getVar('usenotifications');
		$screenButtonText['currentViewList'] = $screen->getVar('usecurrentviewlist');
		$screenButtonText['saveButton'] = $screen->getVar('desavetext');
		$screenButtonText['changeColsButton'] = $screen->getVar('usechangecols');
		$screenButtonText['calcButton'] = $screen->getVar('usecalcs');
		$screenButtonText['advCalcButton'] = $screen->getVar('useadvcalcs');
		$screenButtonText['advSearchButton'] = $screen->getVar('useadvsearch');
		$screenButtonText['exportCalcsButton'] = $screen->getVar('useexportcalcs');
		// only include clone and delete if the checkboxes are in effect (2 means do not use checkboxes)
		if($screen->getVar('usecheckboxes') != 2) {
			$screenButtonText['cloneButton'] = $screen->getVar('useclone');
			if(($del_own OR $del_others) AND !$settings['lockcontrols']) {
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
	foreach($screenButtonText as $scrButton=>$scrText) {
    formulize_benchmark("before creating button: ".$scrButton);
		$buttonCodeArray[$scrButton] = formulize_screenLOEButton($scrButton, $scrText, $settings, $fid, $frid, $colhandles, $flatcols, $pubstart, $loadOnlyView, $calc_cols, $calc_calcs, $calc_blanks, $calc_grouping, $singleMulti[0]['singleentry'], $lastloaded, $currentview, $endstandard, $pickgroups, $viewoptions, $loadviewname, $advcalc_acid);
    formulize_benchmark("button done");
		if($buttonCodeArray[$scrButton] AND $onActionButtonCounter < 14) { // first 14 items in the array should be the action buttons only
			$atLeastOneActionButton = true;
		}
		$onActionButtonCounter++;
	}
	if($hlist) { // if we're on the calc side, then the export button should be the export calcs one
		$buttonCodeArray['exportButton'] = $buttonCodeArray['exportCalcsButton'];
	}
	$buttonCodeArray['pageNavControls'] = $pageNav; // put this unique UI element into the buttonCodeArray for use elsewhere if necessary

	if($useDefaultInterface) {

		// if search is not used, generate the search boxes 
		if(!$useSearch OR ($calc_cols AND !$hcalc)) {
			print "<div style=\"display: none;\"><table>"; // enclose in a table, since drawSearches puts in <tr><td> tags
			drawSearches($searches, $settings['columns'], $useCheckboxes, $useViewEntryLinks, 0, false, $hiddenQuickSearches);
			print "</table></div>";
		}	
	
		print "<table cellpadding=10><tr><td id='titleTable' style=\"vertical-align: top;\" width=100%>";
		
		print "<h1>" . trans($title) . "</h1>";
	
	
		if($loadview AND $lockcontrols) {
			print "<h3>" . $loadviewname . "</h3></td><td>";
			print "<input type=hidden name=currentview id=currentview value=\"$currentview\"></input>\n<input type=hidden name=loadviewname id=loadviewname value=\"$loadviewname\"></input>$submitButton";
		} else {
			print "</td>";
			if(!$settings['lockcontrols']) {
	
				print "<td id='buttonsTable' class='outerTable' rowspan=3 style=\"vertical-align: bottom;\">";	      
		
				print "<table><tr><td id='leftButtonColumn' class='innerTable' style=\"vertical-align: bottom;\">";
		
				print "<p>$submitButton<br>";
				if($atLeastOneActionButton) {
					print "<b>" . _formulize_DE_ACTIONS . "</b>";
				}
				print "\n";
					
				if( $thisButtonCode = $buttonCodeArray['changeColsButton']) { print "<br>$thisButtonCode"; }
				if( $thisButtonCode = $buttonCodeArray['resetViewButton']) { print "<br>$thisButtonCode"; }
				// there is a create reports permission, but we are currently allowing everyone to save their own views regardless of that permission.  The publishing permissions do kick in on the save popup.
				if( $thisButtonCode = $buttonCodeArray['saveViewButton']) { print "<br>$thisButtonCode"; }
				// you can always create and delete your own reports right now (delete_own_reports perm has no effect).  If can delete other reports, then set $pubstart to 10000 -- which is done above -- (ie: can delete published as well as your own, because the javascript will consider everything beyond the start of 'your saved views' to be saved instead of published (published be thought to never begin))
				if( $thisButtonCode = $buttonCodeArray['deleteViewButton']) { print "<br>$thisButtonCode"; }

				print "</p></td><td id='middleButtonColumn' class='innerTable' style=\"vertical-align: bottom;\"><p style=\"text-align: center;\">";

				if(($add_own_entry AND $singleMulti[0]['singleentry'] == "") OR (($del_own OR $del_others) AND !$settings['lockcontrols'])) {
					if( $thisButtonCode = $buttonCodeArray['selectAllButton']) { print "$thisButtonCode"; }
					if( $thisButtonCode = $buttonCodeArray['clearSelectButton']) { print "<br>$thisButtonCode<br>"; }
				}
				if($add_own_entry AND $singleMulti[0]['singleentry'] == "") {
					if( $thisButtonCode = $buttonCodeArray['cloneButton']) { print "$thisButtonCode<br>"; }
				}
				if(($del_own OR $del_others) AND !$settings['lockcontrols']) {
					if( $thisButtonCode = $buttonCodeArray['deleteButton']) { print "$thisButtonCode<br>"; }
				}

				print "</p></td><td id='rightButtonColumn' class='innerTable' style=\"vertical-align: bottom;\"><p style=\"text-align: center;\">";

				if( $thisButtonCode = $buttonCodeArray['calcButton']) { print "<br>$thisButtonCode"; }
				if( $thisButtonCode = $buttonCodeArray['advCalcButton']) { print "<br>$thisButtonCode"; }
				if( $thisButtonCode = $buttonCodeArray['advSearchButton']) { print "<br>$thisButtonCode"; }
				if( $thisButtonCode = $buttonCodeArray['exportButton']) { print "<br>$thisButtonCode"; }
				if($import_data = $gperm_handler->checkRight("import_data", $fid, $groups, $mid) AND !$frid AND $thisButtonCode = $buttonCodeArray['importButton']) { // cannot import into a framework currently
					print "<br>$thisButtonCode";
				}
				if( $thisButtonCode = $buttonCodeArray['notifButton']) { print "$thisButtonCode"; } 
				print "</p>";
				print "</td></tr></table></td></tr>\n";
			} else { // if lockcontrols set, then write in explanation...
				print "<td></td></tr></table>";
				print "<table><tr><td style=\"vertical-align: bottom;\">";
				print "<input type=hidden name=curviewid id=curviewid value=$curviewid></input>";
				print "<p>$submitButton<br>" . _formulize_DE_WARNLOCK . "</p>";
				print "</td></tr>";
			} // end of if controls are locked

			// cell for add entry buttons
			print "<tr><td id='outerAddEntryPanel' style=\"vertical-align: top;\">\n";

			if(!$settings['lockcontrols']) {
				// added October 18 2006 -- moved add entry buttons to left side to emphasize them more
				print "<table><tr><td id='innerAddEntryPanel' style=\"vertical-align: bottom;\"><p>\n";
	
				$addButton = $buttonCodeArray['addButton'];
				$addMultiButton = $buttonCodeArray['addMultiButton'];
				$addProxyButton = $buttonCodeArray['addProxyButton'];
			
				if($add_own_entry AND $singleMulti[0]['singleentry'] == "" AND ($addButton OR $addMultiButton)) {
					print "<b>" . _formulize_DE_FILLINFORM . "</b>\n";
					if( $addButton) { print "<br>$addButton"; } // this will include proxy box if necessary
					if( $addMultiButton) { print "<br>$addMultiButton"; }
				} elseif($add_own_entry AND $proxy AND ($addButton OR $addProxyButton)) { // this is a single entry form, so add in the update and proxy buttons if they have proxy, otherwise, just add in update button
					print "<b>" . _formulize_DE_FILLINFORM . "</b>\n";
					if( $addButton) { print "<br>$addButton"; }
					if( $addProxyButton) { print "<br>$addProxyButton"; }
				} elseif($add_own_entry AND $addButton) {
					print "<b>" . _formulize_DE_FILLINFORM . "</b>\n";
					if( $addButton) { print "<br>$addButton"; }
				} elseif($proxy AND $addProxyButton) {
					print "<b>" . _formulize_DE_FILLINFORM . "</b>\n";
					if( $addProxyButton) { print "<br>$addProxyButton"; }
				}
				print "<br><br></p></td></tr></table>\n";
			}
	
			print "</td></tr><tr><td id=currentViewSelectTable style=\"vertical-align: bottom;\">";
	
			if ($currentViewList = $buttonCodeArray['currentViewList']) { print $currentViewList; }
	
		} // end of if there's a loadview or not
		
		// regardless of if a view is loaded and/or controls are locked, always print the page navigation controls
		if ($pageNavControls = $buttonCodeArray['pageNavControls']) { print $pageNavControls; }
		
		print "</td></tr></table>";
	} else {
		// IF THERE IS A CUSTOM TOP TEMPLATE IN EFFECT, DO SOMETHING COMPLETELY DIFFERENT
	
		if(!$screen->getVar('usecurrentviewlist') OR (!strstr($screen->getVar('toptemplate'), 'currentViewList') AND !strstr($screen->getVar('toptemplate'), 'currentViewList'))) { print "<input type=hidden name=currentview id=currentview value=\"$currentview\"></input>\n"; } // print it even if the text is blank, it will be a hidden value in this case
				
		// if search is not used, generate the search boxes and make them available in the template
		// also setup searches when calculations are in effect, or there's a custom list template
		// (essentially, whenever the search boxes would not be drawn in for whatever reason)
		if(!$useSearch OR ($calc_cols AND !$hcalc) OR $screen->getVar('listtemplate')) {
      formulize_benchmark("before calling draw searches");
			$quickSearchBoxes = drawSearches($searches, $settings['columns'], $useCheckboxes, $useViewEntryLinks, 0, true, $hiddenQuickSearches, true); // first true means we will receive back the code instead of having it output to the screen, second (last) true means that all allowed filters should be generated
      formulize_benchmark("after calling draw searches");
			$quickSearchesNotInTemplate = array();
			foreach($quickSearchBoxes as $handle=>$qscode) {
				$handle = str_replace("-","_",$handle);
				$foundQS = false;
				if(strstr($screen->getVar('toptemplate'), 'quickSearch' . $handle) OR strstr($screen->getVar('bottomtemplate'), 'quickSearch' . $handle)) {
					$buttonCodeArray['quickSearch' . $handle] = $qscode['search']; // set variables for use in the template
          $foundQS = true;
        }
        if(strstr($screen->getVar('toptemplate'), 'quickFilter' . $handle) OR strstr($screen->getVar('bottomtemplate'), 'quickFilter' . $handle)) {
          $buttonCodeArray['quickFilter' . $handle] = $qscode['filter']; // set variables for use in the template
          $foundQS = true;
        }
	if(strstr($screen->getVar('toptemplate'), 'quickDateRange' . $handle) OR strstr($screen->getVar('bottomtemplate'), 'quickDateRange' . $handle)) {
          $buttonCodeArray['quickDateRange' . $handle] = $qscode['dateRange']; // set variables for use in the template
          $foundQS = true;
        }
        if($foundQS) { continue; } // skip next line
				$quickSearchesNotInTemplate[] = $qscode['search']; // if it's not used in the template, then save the box version for hidden output to screen below, so searches still work
			}
      
			if(count($quickSearchesNotInTemplate) > 0) {			
				print "<div style=\"display: none;\">"; 
				foreach($quickSearchesNotInTemplate as $qscode) {
					print $qscode. "\n";
				}
				print "</div>";
			}
		}	
	
    formulize_benchmark("before rendering top template");
		formulize_screenLOETemplate($screen, "top", $buttonCodeArray, $settings, $messageText);
    formulize_benchmark("after rendering top template");
		$buttonCodeArray['submitButton'] = $submitButton; // send this back so that we can put it at the bottom of the page if necessary
		
	}
	

	print "<input type=hidden name=newcols id=newcols value=\"\"></input>\n";
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

	// advanced search
	$asearch = str_replace("\"", "&quot;", $asearch);
	print "<input type=hidden name=asearch id=asearch value=\"" . stripslashes($asearch) . "\"></input>\n";

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

	$returnArray = array();
	$returnArray[0] = $buttonCodeArray; // send this back so it's available in the bottom template if necessary.  MUST USE NUMERICAL KEYS FOR list TO WORK ON RECEIVING END.
	return $returnArray;

}

// THIS FUNCTION DRAWS IN THE RESULTS OF THE QUERY
function drawEntries($fid, $cols, $searches="", $frid="", $scope, $standalone="", $currentURL, $gperm_handler, $uid, $mid, $groups, $settings, $member_handler, $screen, $data, $wq, $regeneratePageNumbers, $hiddenQuickSearches) { // , $loadview="") { // -- loadview removed from this function sept 24 2005

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
	$useCheckboxes = 0;
	$useViewEntryLinks = 1;
	$useSearch = 1;
	$deColumns = array();
	$useSearchCalcMsgs = 1;
	$listTemplate = false;
	$inlineButtons = array();
	$hiddenColumns = array();
	$formulize_LOEPageSize = 10;
	if($screen) {
		$useScrollBox = $screen->getVar('usescrollbox');
		$useHeadings = $screen->getVar('useheadings');
		$repeatHeaders = $screen->getVar('repeatheaders');
		$columnWidth = $screen->getVar('columnwidth');
		$textWidth = $screen->getVar('textwidth');
		if($textWidth == 0) { $textWidth = 10000; }
		$useCheckboxes = $screen->getVar('usecheckboxes');
		$useViewEntryLinks = $screen->getVar('useviewentrylinks');
		$useSearch = $screen->getVar('usesearch');
		$hiddenColumns = $screen->getVar('hiddencolumns');
		$deColumns = $screen->getVar('decolumns');
		$deDisplay = $screen->getVar('dedisplay');
		$useSearchCalcMsgs = $screen->getVar('usesearchcalcmsgs');
		$listTemplate = $screen->getVar('listtemplate');
		foreach($screen->getVar('customactions') as $caid=>$thisCustomAction) {
			if($thisCustomAction['appearinline'] == 1) {
				list($caCode) = processCustomButton($caid, $thisCustomAction);
				if($caCode) {
					$inlineButtons[$caid] = $thisCustomAction;
				}
			}
		}
		$formulize_LOEPageSize = $screen->getVar('entriesperpage');
	}		
		
	$filename = "";
	// $settings['xport'] no longer set by a page load, except if called as part of the import process to create a template for updating
	if(!$settings['xport']) {
		$settings['xport'] = $settings['hlist'] ? "calcs" : "comma";
		$xportDivText1 = "<div id=exportlink style=\"display: none;\">"; // export button turns this link on and off now
		$xportDivText2 = "</div>";
	} else {
		$xportDivText1 = "";
		$xportDivText2 = "";
	}
  
	if( @$_POST['advcalc_acid'] ) {
    
    if( $_POST['acid'] > 0 ) {
      $result = formulize_runAdvancedCalculation( intval($_POST['acid'] )); // result will be an array with two or three keys: 'text' and 'output', and possibly 'groupingMap'.  Text is for display on screen "raw" and Output is a variable that can be used by a dev.  The output variable will be an array if groupings are in effect.  The keys of the array will be the various grouping values in effect.  The groupingMap will be present if there's a set of groupings in effect.  It is an array that contains all the grouping choices, their text equivalents and their data values (which are the keys in the output array) -- groupingMap is still to be developed/added to the mix....will be necessary when we are integrating with Drupal or other API uses.
      print "<br/>" . $result['text'] . "<br/><br/>";
    }
  }
	
	// export of Data is moved out to a popup
	// Calculations still handled in the old way for now
	if($settings['xport'] == "calcs") {
		$filename = prepExport($headers, $cols, $data, $settings['xport'], $settings['xport_cust'], $settings['title'], false, $fid, $groups);
		$linktext = $_POST['xport'] == "update" ? _formulize_DE_CLICKSAVE_TEMPLATE : _formulize_DE_CLICKSAVE;
		print "$xportDivText1<center><p><a href='$filename' target=\"_blank\">$linktext</a></p></center>";
		print "<br>$xportDivText2";
	}

	$scrollBoxWasSet = false;
	if($useScrollBox AND count($data) > 0) {
		print "<div class=scrollbox id=resbox>\n";
		$scrollBoxWasSet = true;
	}

	// perform calculations...
	// calc_cols is the columns requested (separated by / -- ele_id for each, also metadata is indicated with uid, proxyid, creation_date, mod_date)
	// calc_calcs is the calcs for each column, columns separated by / and calcs for a column separated by ,. possible calcs are sum, avg, min, max, count, per
	// calc_blanks is the blank setting for each calculation, setup the same way as the calcs, possible settings are all,  noblanks, onlyblanks
	// calc_grouping is the grouping option.  same format as calcs.  possible values are ele_ids or the uid, proxyid, creation_date and mod_date metadata terms

	// 1. extract data from four settings into arrays
	// 2. loop through the array and perform all the requested calculations
	
	if($settings['calc_cols'] AND !$settings['hcalc']) {
		$ccols = explode("/", $settings['calc_cols']);
		$ccalcs = explode("/", $settings['calc_calcs']);
		// need to add in proper handling of long calculation results, like grouping percent breakdowns that result in many, many rows.
		foreach($ccalcs as $onecalc) {
			$thesecalcs = explode(",", $onecalc);
			if(!is_array($thesecalcs)) { $thesecalcs[0] = ""; }
			$totalalcs = $totalcalcs + count($thesecalcs);
		}
		$cblanks = explode("/", $settings['calc_blanks']);
		$cgrouping = explode("/", $settings['calc_grouping']);
    //formulize_benchmark("before performing calcs");
		$cResults = performCalcs($ccols, $ccalcs, $cblanks, $cgrouping, $frid, $fid);
    //formulize_benchmark("after performing calcs");
//		print "<p><input type=button style=\"width: 140px;\" name=cancelcalcs1 value='" . _formulize_DE_CANCELCALCS . "' onclick=\"javascript:cancelCalcs();\"></input></p>\n";
//		print "<div";
//		if($totalcalcs>4) { print " class=scrollbox"; }
//		print " id=calculations>
		$calc_cols = $settings['calc_cols'];
		$calc_calcs = $settings['calc_calcs'];
		$calc_blanks = $settings['calc_blanks'];
		$calc_grouping = $settings['calc_grouping'];

 		print "<table class=outer><tr><th colspan=2>" . _formulize_DE_CALCHEAD . "</th></tr>\n";
 		if(!$settings['lockcontrols'] AND ($useSearchCalcMsgs == 1 OR $useSearchCalcMsgs == 3)) { // AND !$loadview) { // -- loadview removed from this function sept 24 2005
 			print "<tr><td class=head colspan=2><input type=button style=\"width: 140px;\" name=mod_calculations value='" . _formulize_DE_MODCALCS . "' onclick=\"javascript:showPop('" . XOOPS_URL ."/modules/formulize/include/pickcalcs.php?fid=$fid&frid=$frid&calc_cols=".urlencode($calc_cols)."&calc_calcs=".urlencode($calc_calcs)."&calc_blanks=".urlencode($calc_blanks)."&calc_grouping=".urlencode($calc_grouping)."');\"></input>&nbsp;&nbsp;<input type=button style=\"width: 140px;\" name=cancelcalcs value='" . _formulize_DE_CANCELCALCS . "' onclick=\"javascript:cancelCalcs();\"></input>&nbsp;&nbsp<input type=button style=\"width: 140px;\" name=showlist value='" . _formulize_DE_SHOWLIST . "' onclick=\"javascript:showList();\"></input></td></tr>";
 		}

		$exportFilename = $settings['xport'] == "calcs" ? $filename : "";
    //formulize_benchmark("before printing results");
    printResults($cResults[0], $cResults[1], $cResults[2], $cResults[3], $exportFilename, $settings['title']); // 0 is the masterresults, 1 is the blanksettings, 2 is grouping settings -- exportFilename is the name of the file that we need to create and into which we need to dump a copy of the calcs
    //formulize_benchmark("after printing results");
		print "</table>\n";

	} 
	// MASTER HIDELIST CONDITIONAL...
	if(!$settings['hlist'] AND !$listTemplate) {

		print "<table class=outer>";
	
		$count_colspan = count($cols)+1;
		if($useViewEntryLinks OR $useCheckboxes != 2) {
			$count_colspan_calcs = $count_colspan;
		} else {
			$count_colspan_calcs = $count_colspan - 1;
		}
		$count_colspan_calcs = $count_colspan_calcs + count($inlineButtons); // add to the column count for each inline custom button
		$count_colspan_calcs++; // add one more for the hidden floating column
		if(!$screen) { print "<tr><th colspan=$count_colspan_calcs>" . _formulize_DE_DATAHEADING . "</th></tr>\n"; }
	
		if($settings['calc_cols'] AND !$settings['lockcontrols'] AND ($useSearchCalcMsgs == 1 OR $useSearchCalcMsgs == 3)) { // AND !$loadview) { // -- loadview removed from this function sept 24 2005
			$calc_cols = $settings['calc_cols'];
			$calc_calcs = $settings['calc_calcs'];
			$calc_blanks = $settings['calc_blanks'];
			$calc_grouping = $settings['calc_grouping'];
			print "<tr><td class=head colspan=$count_colspan_calcs><input type=button style=\"width: 140px;\" name=mod_calculations value='" . _formulize_DE_MODCALCS . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/pickcalcs.php?fid=$fid&frid=$frid&calc_cols=$calc_cols&calc_calcs=$calc_calcs&calc_blanks=$calc_blanks&calc_grouping=$calc_grouping');\"></input>&nbsp;&nbsp;<input type=button style=\"width: 140px;\" name=cancelcalcs value='" . _formulize_DE_CANCELCALCS . "' onclick=\"javascript:cancelCalcs();\"></input>&nbsp;&nbsp;<input type=button style=\"width: 140px;\" name=hidelist value='" . _formulize_DE_HIDELIST . "' onclick=\"javascript:hideList();\"></input></td></tr>";
		}
	
		// draw advanced search notification
		if($settings['as_0'] AND ($useSearchCalcMsgs == 1 OR $useSearchCalcMsgs == 2)) {
			$writable_q = writableQuery($wq);
			$minus1colspan = $count_colspan-1+count($inlineButtons);
			if(!$asearch_parse_error) {
				print "<tr>";
				if($useViewEntryLinks OR $useCheckboxes != 2) { // only include this column if necessary
					print "<td class=head></td>";
				}
				print "<td colspan=$minus1colspan class=head>" . _formulize_DE_ADVSEARCH . ": $writable_q";
			} else {
				print "<tr>";
				if($useViewEntryLinks OR $useCheckboxes != 2) {
					print "<td class=head></td>";
				}
				print "<td colspan=$minus1colspan class=head><span style=\"font-weight: normal;\">" . _formulize_DE_ADVSEARCH_ERROR . "</span>";
			}
			if(!$settings['lockcontrols']) { // AND !$loadview) { // -- loadview removed from this function sept 24 2005
				print "<br><input type=button style=\"width: 140px;\" name=advsearch value='" . _formulize_DE_MOD_ADVSEARCH . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/advsearch.php?fid=$fid&frid=$frid";
				foreach($settings as $k=>$v) {
					if(substr($k, 0, 3) == "as_") {
						$v = str_replace("'", "&#39;", $v);
						$v = stripslashes($v);
						print "&$k=" . urlencode($v);
					}
				}
			print "');\"></input>&nbsp;&nbsp;<input type=button style=\"width: 140px;\" name=cancelasearch value='" . _formulize_DE_CANCELASEARCH . "' onclick=\"javascript:killSearch();\"></input>";
			}
			print "</td></tr>\n";
		}
	
		if($useHeadings) {
      $headers = getHeaders($cols, true); // second param indicates we're using element headers and not ids
      drawHeaders($headers, $cols, $useCheckboxes, $useViewEntryLinks, count($inlineButtons), $settings['lockedColumns']); 
    }
		if($useSearch) {
			drawSearches($searches, $cols, $useCheckboxes, $useViewEntryLinks, count($inlineButtons), false, $hiddenQuickSearches);
		}
		// get form handles in use
		$mainFormHandle = key($data[key($data)]);
	
		if(count($data) == 0) { // kill an empty dataset so there's no rows drawn
			unset($data);
		} 
	
  
    
  
		$headcounter = 0;
		$blankentries = 0;
		$update_own_entry = $gperm_handler->checkRight("update_own_entry", $fid, $groups, $mid);
		$update_other_entries = $gperm_handler->checkRight("update_other_entries", $fid, $groups, $mid);
		$GLOBALS['formulize_displayElement_LOE_Used'] = false;
		$formulize_LOEPageStart = (isset($_POST['formulize_LOEPageStart']) AND !$regeneratePageNumbers) ? intval($_POST['formulize_LOEPageStart']) : 0;
		// adjust formulize_LOEPageSize if the actual count of entries is less than the page size
		$formulize_LOEPageSize = $GLOBALS['formulize_countMasterResultsForPageNumbers'] < $formulize_LOEPageSize ? $GLOBALS['formulize_countMasterResultsForPageNumbers'] : $formulize_LOEPageSize;
		$actualPageSize = $formulize_LOEPageSize ? $formulize_LOEPageStart + $formulize_LOEPageSize : $GLOBALS['formulize_countMasterResultsForPageNumbers'];
    /*print "start: $formulize_LOEPageStart<br>";
    print "size: $formulize_LOEPageSize<br>";
    print "actualsize: $actualPageSize<br>";*/
		if(isset($data)) {
			//for($entryCounter=$formulize_LOEPageStart;$entryCounter<$actualPageSize;$entryCounter++) {
      foreach($data as $id=>$entry) {
        formulize_benchmark("starting to draw one row of results");
				//$entry = $data[$entryCounter];
				//$id=$entryCounter;
						
				// check to make sure this isn't an unset entry (ie: one that was blanked by the extraction layer just prior to sending back results
				// Since the extraction layer is unsetting entries to blank them, this condition should never be met?
				// If this condition is ever met, it may very well screw up the paging of results!
				// NOTE: this condition is met on the last page of a paged set of results, unless the last page as exactly the same number of entries on it as the limit of entries per page
				if($entry != "") { 
		
					if($headcounter == $repeatHeaders AND $repeatHeaders > 0) { 
						if($useHeadings) { drawHeaders($headers, $cols, $useCheckboxes, $useViewEntryLinks, count($inlineButtons)); }
						$headcounter = 0;
					}
					$headcounter++;		
			
					print "<tr>\n";
					if($class=="even") {
						$class="odd";
					} else {
						$class="even";
					}
					unset($linkids);
          
					$linkids = internalRecordIds($entry, $mainFormHandle);
					
					// draw in the margin column where the links and metadata goes
					if($useViewEntryLinks OR $useCheckboxes != 2) {
						print "<td class=\"head formulize-controls\">\n";
					}
			
					if(!$settings['lockcontrols']) { //  AND !$loadview) { // -- loadview removed from this function sept 24 2005
						if($useViewEntryLinks) {
							print "<p><center><a href='" . $currentURL;
							if(strstr($currentURL, "?")) { // if params are already part of the URL...
								print "&";
							} else {
								print "?";
							}
							print "ve=" . $linkids[0] . "' onclick=\"javascript:goDetails('" . $linkids[0] . "');return false;\"><img src='" . XOOPS_URL . "/modules/formulize/images/detail.gif' border=0 alt=\"" . _formulize_DE_VIEWDETAILS . "\" title=\"" . _formulize_DE_VIEWDETAILS . "\"></a>";
						}
						if($useCheckboxes != 2) { // two means none
							// put in the delete checkboxes -- check for perms delete_own_entry, delete_other_entries
              $owner = getEntryOwner($linkids[0], $fid);
              // check to see if we should draw in the delete checkbox or not
							if(($owner == $uid AND $gperm_handler->checkRight("delete_own_entry", $fid, $groups, $mid)) OR ($owner != $uid AND $gperm_handler->checkRight("delete_other_entries", $fid, $groups, $mid)) OR $useCheckboxes == 1) { // 1 means all
								if($useViewEntryLinks) {
									print "<br>";
								} else {
									print "<p><center>";
								}
								print "<input type=checkbox title='" . _formulize_DE_DELBOXDESC . "' class='Check' name='delete_" . $linkids[0] . "' id='delete_" . $linkids[0] . "' value='delete_" . $linkids[0] . "'>";
							}
						}
						if($useViewEntryLinks OR $useCheckboxes != 2) { // at least one of the above was used
							print "</center></p>\n";
						}
					} // end of IF NO LOCKCONTROLS
					if($useViewEntryLinks OR $useCheckboxes != 2) {
						print "</td>\n";
					}	
			
					$column_counter = 0;
					
					if($columnWidth) {
						$columnWidthParam = "style=\"width: $columnWidth" . "px\"";
					} else {
						$columnWidthParam = "";
					}
					
          for($i=0;$i<count($cols);$i++) {
            //formulize_benchmark("drawing one column");
						$col = $cols[$i];
						$colhandle = $settings['columnhandles'][$i];
						$classToUse = $class . " column column".$i;
						$cellRowAddress = $id+2;
						if($i==0) {
							print "<td $columnWidthParam class=\"$class floating-column\" id='floatingcelladdress_$cellRowAddress'>\n";
						}
						print "<td $columnWidthParam class=\"$classToUse\" id=\"celladdress_".$cellRowAddress."_".$i."\">\n";
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
						$currentColumnLocalId = $GLOBALS['formulize_mostRecentLocalId']; // set in the display function, corresponds to the entry id of the record in the form where the current value was retrieved from.  If there is more than one local entry id, because of a one to many framework, then this will be an array that corresponds to the order of the values returned by display.
						if(in_array($colhandle, $deColumns) AND ($update_other_entries OR ($update_own_entry AND $uid == getEntryOwner($entry, $fid)))) { // if we're supposed to display this column as an element... (only show it if they have permission to update this entry)
							include_once XOOPS_ROOT_PATH . "/modules/formulize/include/elementdisplay.php";
							if($frid) { // need to work out which form this column belongs to, and use that form's entry ID.  Need to loop through the entry to find all possible internal IDs, since a subform situation would lead to multiple values appearing in a single cell, so multiple displayElement calls would be made each with their own internal ID.
								foreach($entry as $entryFormHandle=>$entryFormData) {
									foreach($entryFormData as $internalID=>$entryElements) {
										$deThisIntId = false;
										foreach($entryElements as $entryHandle=>$values) {
											if($entryHandle == $col) { // we found the element that we're trying to display
												if($deThisIntId) { print "\n<br />\n"; } // could be a subform so we'd display multiple values
												if($deDisplay) {
													print '<div id="deDiv_'.$colhandle.'_'.$internalID.'">';
													print getHTMLForList($values, $colhandle, $internalID, $deDisplay, $textWidth, $currentColumnLocalId, $fid, $cellRowAddress, $i);
													print "</div>";
												} else {
													displayElement("", $colhandle, $internalID);
												}
												$deThisIntId = true;
											}
										}
									}
								}
							} else { // display based on the mainform entry id
								if($deDisplay) {
									print '<div id="deDiv_'.$colhandle.'_'.$linkids[0].'">';
									print getHTMLForList($value,$colhandle,$linkids[0], $deDisplay, $textWidth, $currentColumnLocalId, $fid, $cellRowAddress, $i);
									print "</div>";
								} else {
									displayElement("", $colhandle, $linkids[0]); // works for mainform only!  To work on elements from a framework, we need to figure out the form the element is from, and the entry ID in that form, which is done above
								}
							}
							$GLOBALS['formulize_displayElement_LOE_Used'] = true;
						} elseif($col != "creation_uid" AND $col!= "mod_uid") {
							print getHTMLForList($value, $col, $linkids[0], 0, $textWidth, $currentColumnLocalId, $fid, $cellRowAddress, $i);
						} else { // no special formatting on the uid columns:
							print $value;
						}
						
						print "</td>\n";
						$column_counter++;
					}
					
					// handle inline custom buttons
					foreach($inlineButtons as $caid=>$thisCustomAction) {
						list($caCode) = processCustomButton($caid, $thisCustomAction, $linkids[0], $entry); // only bother with the code, since we already processed any clicked button above
						if($caCode) {
							print "<td $columnWidthParam class=$class>\n";
							print "<center>$caCode</center>\n";
							print "</td>\n";
						}
					}
					
					// handle hidden elements for passing back to custom buttons
					foreach($hiddenColumns as $thisHiddenCol) {
						print "\n<input type=\"hidden\" name=\"hiddencolumn_".$linkids[0]."_$thisHiddenCol\" value=\"" . htmlspecialchars(display($entry, $thisHiddenCol)) . "\"></input>\n";
					}				
					
					print "</tr>\n";
				
				} else { // this is a blank entry
					$blankentries++;
				} // end of not "" check
			
			} // end of foreach data as entry
		} // end of if there is any data to draw
	
		print "</table>";

	} elseif($listTemplate AND !$settings['hlist']) {

		// USING A CUSTOM LIST TEMPLATE SO DO EVERYTHING DIFFERENTLY
		// print str_replace("\n", "<br />", $listTemplate); // debug code
		$mainFormHandle = key($data[key($data)]);
		$formulize_LOEPageStart = (isset($_POST['formulize_LOEPageStart']) AND !$regeneratePageNumbers) ? intval($_POST['formulize_LOEPageStart']) : 0;
		$actualPageSize = $formulize_LOEPageSize ? $formulize_LOEPageStart + $formulize_LOEPageSize : $GLOBALS['formulize_countMasterResultsForPageNumbers'];
		if(strstr($listTemplate, "displayElement")) {
			include_once XOOPS_ROOT_PATH . "/modules/formulize/include/elementdisplay.php";
		}
		if(isset($data)) {
			//for($entryCounter=$formulize_LOEPageStart;$entryCounter<$actualPageSize;$entryCounter++) {
			
			// setup the view name variables, with true only set for the last loaded view
			$viewNumber = 1;
			foreach($settings['publishedviewnames'] as $id=>$thisViewName) {
				$thisViewName = str_replace(" ", "_", $thisViewName);
				if($id == $settings['lastloaded']) {
					${$thisViewName} = true;
					${'view'.$viewNumber} = true;
				} else {
					${$thisViewName} = false;
					$view{'view'.$viewNumber} = false;
				}
				$viewNumber++;
			}
			
      foreach($data as $id=>$entry) {
				//$entry = $data[$entryCounter];
				//$id=$entryCounter;
						
				// check to make sure this isn't an unset entry (ie: one that was blanked by the extraction layer just prior to sending back results
				// Since the extraction layer is unsetting entries to blank them, this condition should never be met?
				// If this condition is ever met, it may very well screw up the paging of results!
				// NOTE: this condition is met on the last page of a paged set of results, unless the last page as exactly the same number of entries on it as the limit of entries per page
				if($entry != "") { 

					// Set up the variables for the link to the current entry, and the checkbox that can be used to select the current entry
					$linkids = internalRecordIds($entry, $mainFormHandle);
					$entry_id = $linkids[0]; // make a nice way of referring to this for in the eval'd code
					$form_id = $fid; // make a nice way of referring to this for in the eval'd code
					if(!$settings['lockcontrols']) { //  AND !$loadview) { // -- loadview removed from this function sept 24 2005
						$viewEntryLinkCode = "<a href='" . $currentURL;
						if(strstr($currentURL, "?")) { // if params are already part of the URL...
							$viewEntryLinkCode .= "&";
						} else {
							$viewEntryLinkCode .= "?";
						}
						$viewEntryLinkCode .= "ve=" . $linkids[0] . "' onclick=\"javascript:goDetails('" . $linkids[0] . "');return false;\">";
						$GLOBALS['formulize_viewEntryId'] = $linkids[0];
	    				        $GLOBALS['formulize_viewEntryLinkCode'] = $viewEntryLinkCode; // put into global scope so the function 'viewEntryLink' can pick it up if necessary
						// put in the delete checkboxes -- check for perms delete_own_entry, delete_other_entries
						$owner = getEntryOwner($linkids[0], $fid);
						// check to see if we should draw in the delete checkbox or not
						if(($owner == $uid AND $gperm_handler->checkRight("delete_own_entry", $fid, $groups, $mid)) OR ($owner != $uid AND $gperm_handler->checkRight("delete_other_entries", $fid, $groups, $mid)) OR $useCheckboxes == 1) { // 1 means all
							$selectionCheckbox = "<input type=checkbox title='" . _formulize_DE_DELBOXDESC . "' class='Check' name='delete_" . $linkids[0] . "' id='delete_" . $linkids[0] . "' value='delete_" . $linkids[0] . "'>";
						} else {
							$selectionCheckbox = "";
						}
					} // end of IF NO LOCKCONTROLS

					$ids = internalRecordIds($entry, $mainFormHandle);
					foreach($inlineButtons as $caid=>$thisCustomAction) {
						list($caCode) = processCustomButton($caid, $thisCustomAction, $ids[0], $entry); // only bother with the code, since we already processed any clicked button above
						if($caCode) {
							${$thisCustomAction['handle']} = $caCode; // assign the button code that was returned
						}
					}
					
					// handle hidden elements for passing back to custom buttons
					foreach($hiddenColumns as $thisHiddenCol) {
						print "\n<input type=\"hidden\" name=\"hiddencolumn_".$linkids[0]."_$thisHiddenCol\" value=\"" . htmlspecialchars(display($entry, $thisHiddenCol)) . "\"></input>\n";
					}
					
					ob_start();
					$evalSuccess = eval(html_entity_decode($listTemplate));
					$evalResult = ob_get_clean();
					if($evalSuccess === false) { // eval returns false on a parse error
						print "<p>" . _AM_FORMULIZE_SCREEN_LOE_TEMPLATE_ERROR . "</p>";
						break;
					} else {
						print $evalResult;
					}
				}
			}
		}

	}// END OF MASTER HIDELIST CONDITIONAL
	if((!isset($data) OR count($data) == $blankentries) AND !$LOE_limit) { // if no data was returned, or the dataset was empty...
		print "<p><b>" . _formulize_DE_NODATAFOUND . "</b></p>\n";
	} elseif($LOE_limit) {
		print "<p>" . _formulize_DE_LOE_LIMIT_REACHED1 . " <b>" . $LOE_limit . "</b> " . _formulize_DE_LOE_LIMIT_REACHED2 . " <a href=\"\" onclick=\"javascript:forceQ();return false;\">" . _formulize_DE_LOE_LIMIT_REACHED3 . "</a></p>\n";
	}
	
	if($scrollBoxWasSet) {
		print "</div>";
	}
  formulize_benchmark("We're done");
}

// this function outputs the html to view an entry, based on the value the user wants clickable, and the pre-determined view link code for the entry
// overrideId is an alternative ID that we should construct the link to display instead of the active entry
// overrideScreen is the ID of the screen that the overrideID should be displayed in.  If not specified, then the current screen would be used.
// Note: for the $overrideId, pass in 'proxy' to start a new proxy entry, 'single' to start a new entry without the "add multiple entries" behaviour, 'addnew' to start adding multiple new entries
function viewEntryLink($linkContents, $overrideId="", $overrideScreen="") {
	if($overrideId) {
		$screenParam = $overrideScreen ? "', '".intval($overrideScreen) : "";
		return "<a href=\"\" onclick=\"javascript:goDetails('" . $overrideId . $screenParam ."');return false;\">".$linkContents."</a>";
	} else {
		return $GLOBALS['formulize_viewEntryLinkCode'] . $linkContents . "</a>";
	}
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



// this function draws in the search box row
// returnOnly is used to return the HTML code for the boxes, and that only happens when we are gathering the boxes because a custom list template is in use
// $filtersRequired can be 'true' which means include all valid filters, or it can be a list of fields (matching values in the cols array) which require filters
function drawSearches($searches, $cols, $useBoxes, $useLinks, $numberOfButtons, $returnOnly=false, $hiddenQuickSearches, $filtersRequired=array()) {
  
	$quickSearchBoxes = array();
	if(!$returnOnly) { print "<tr>"; }
	if($useBoxes != 2 OR $useLinks) {
		if(!$returnOnly) { print "<td class=head>&nbsp;</td>\n"; }
	}
	
	for($i=0;$i<count($cols);$i++) {
		
		
		$classToUse = "head column column".$i;
		if(!$returnOnly) {
			if($i==0) {
				print "<td class='head floating-column' id='floatingcelladdress_1'>\n";
			}
			print "<td class='$classToUse' id='celladdress_1_$i'><div class='main-cell-div' id='cellcontents_1_".$i."'>\n";
		}
    //formulize_benchmark("drawing one search");
		$search_text = isset($searches[$cols[$i]]) ? strip_tags(htmlspecialchars($searches[$cols[$i]]), ENT_QUOTES) : "";
		$search_text = get_magic_quotes_gpc() ? stripslashes($search_text) : $search_text;
		$boxid = "";
		$helpText = "";
		$clear_help_javascript = "";
		if(count($searches) == 0 AND !$returnOnly) {
			if($i==0) { 
				$search_text = _formulize_DE_SEARCH_HELP; 
				$boxid = "id=firstbox";
			}
			$clear_help_javascript = "onfocus=\"javascript:clearSearchHelp(this.form, '" . _formulize_DE_SEARCH_HELP . "');\"";
		}
		if($i==0) {
			if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/docs/search_help_"._LANGCODE.".html")) {
				$search_help_filepath = XOOPS_URL."/modules/formulize/docs/search_help_"._LANGCODE.".html";
			} elseif(file_exists(XOOPS_ROOT_PATH."/modules/formulize/docs/search_help_"._LANGCODE.".xhtml")) {
				$search_help_filepath = XOOPS_URL."/modules/formulize/docs/search_help_"._LANGCODE.".xhtml";
			} else {
				$search_help_filepath = XOOPS_URL."/modules/formulize/docs/search_help.xhtml";
			}
			$helpText = "\n&nbsp;<a href=\"\" onclick=\"javascript:showPop('".$search_help_filepath."'); return false;\" title=\""._formulize_DE_SEARCH_POP_HELP."\">[?]<a>\n";
		}
    //formulize_benchmark("finished prep of search box");
		$quickSearchBoxes[$cols[$i]]['search'] = "<input type=text $boxid name='search_" . $cols[$i] . "' value=\"$search_text\" $clear_help_javascript onchange=\"javascript:window.document.controls.ventry.value = '';\"></input>\n";
    //formulize_benchmark("made search box, starting filter");
    if(is_array($filtersRequired) OR $filtersRequired === true) {
      if($filtersRequired === true OR in_array($cols[$i], $filtersRequired)) {
        $quickSearchBoxes[$cols[$i]]['filter'] = formulize_buildQSFilter($cols[$i], $search_text);
	$quickSearchBoxes[$cols[$i]]['dateRange'] = formulize_buildDateRangeFilter($cols[$i], $search_text);
      }
    }
    //formulize_benchmark("done filter");
    
		// print out the boxes if we are supposed to (ie: if we're not just returning the arrays)
		if(!$returnOnly) {
      if(isset($quickSearchBoxes[$cols[$i]]['filter'])) {
        print $quickSearchBoxes[$cols[$i]]['filter'];
      } else {
        print "<nobr>".$quickSearchBoxes[$cols[$i]]['search']."$helpText</nobr>";
      }
		}
    
    // handle all the hidden quick searches if we are on the last run through...must be done here, last thing in the loop, after the last box has been drawn in!!  Order of columns and searches must be in synch...adding hidden ones in between columns can cause hard-to-find problems
		if($i == count($cols)-1) {
			foreach($hiddenQuickSearches as $thisHQS) {
				$search_text = isset($searches[$thisHQS]) ? htmlspecialchars(strip_tags($searches[$thisHQS]), ENT_QUOTES) : "";
				$search_text = get_magic_quotes_gpc() ? stripslashes($search_text) : $search_text;
				$quickSearchBoxes[$thisHQS]['search'] = "<input type=text name='search_$thisHQS' value=\"$search_text\" $clear_help_javascript onchange=\"javascript:window.document.controls.ventry.value = '';\"></input>\n";
        if(is_array($filtersRequired) OR $filtersRequired === true) {
          if($filtersRequired === true OR in_array($thisHQS, $filtersRequired)) {
            $quickSearchBoxes[$thisHQS]['filter'] = formulize_buildQSFilter($thisHQS, $search_text);
	    $quickSearchBoxes[$thisHQS]['dateRange'] = formulize_buildDateRangeFilter($thisHQS, $search_text);
          }
        }
				if(!$returnOnly) {
					print "<input type=hidden name='search_$thisHQS' value=\"$search_text\"></input>\n"; // note: this will cause a conflict if this particular column is included in the top or bottom templates and no custom list template is in effect...since this is only ! ! search terms, not sure why you'd ever include this as a box in the top/bottom templates...it's not type-in-able because of the ! !
				}
			}
		}
    
    if(!$returnOnly) {
      print "</div></td>\n";      
    }
	}
  
	if(!$returnOnly) {
		for($i=0;$i<$numberOfButtons;$i++) {
			print "<td class=head>&nbsp;</td>\n";
		}
		print "</tr>\n";
	}
  
	return $quickSearchBoxes;
}

// THIS FUNCTION CREATES THE QUICKFILTER BOXES
function formulize_buildQSFilter($handle, $search_text) {
  formulize_benchmark("start of building filter");
  $elementMetaData = formulize_getElementMetaData($handle, true); // true means this is a handle
  $id = $elementMetaData['ele_id'];
  if($elementMetaData['ele_type']=="select" OR $elementMetaData['ele_type']=="radio" OR $elementMetaData['ele_type']=="checkbox") {
    $qsfparts = explode("_", $search_text);
    $search_term = strstr($search_text, "_") ? $qsfparts[1] : $search_text;
    $filterHTML = buildFilter("search_".$handle, $id, _formulize_QSF_DefaultText, $name="{listofentries}", $search_term);
    return $filterHTML;
  }
  return "";
}

// THIS FUNCTION CREATES THE HTML FOR A DATE RANGE FILTER
function formulize_buildDateRangeFilter($handle, $search_text) {
   $elementMetaData = formulize_getElementMetaData($handle, true); // true means this is a handle
   if($elementMetaData['ele_type']=="date") {
	// split any search_text into start and end values
	if(strstr($search_text, "//")) {
		$startEnd = explode("//",$search_text);
		$startText = isset($startEnd[0]) ? parseUserAndToday(substr(htmlspecialchars_decode($startEnd[0]), 2)) : _formulize_QSdateRange_startText;
		$endText = isset($startEnd[1]) ? parseUserAndToday(substr(htmlspecialchars_decode($startEnd[1]), 2)) : _formulize_QSdateRange_endText;
	} else {
		$startText = "";
		$endText = "";
	}
	include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";
	$startDateElement = new XoopsFormTextDateSelect ('', 'formulize_daterange_sta_'.$handle, 15, strtotime($startText));
	$startDateElement->setExtra("class='formulize_daterange'");
	$endDateElement = new XoopsFormTextDateSelect ('', 'formulize_daterange_end_'.$handle, 15, strtotime($endText));
	$endDateElement->setExtra("class='formulize_daterange' target='$handle'");
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
		$('.formulize_daterange').change(function() {
			var id = new String($(this).attr('id'));
			var handle = id.substr(24);
			var start = $('#formulize_daterange_sta_'+handle).val();
			var end = $('#formulize_daterange_end_'+handle).val();
			$('#formulize_hidden_daterange_'+handle).val('>='+start+'//'+'<='+end);
		});
		</script>";
	}
	return $startDateElement->render() . " ". _formulize_QDR_to . " " . $endDateElement->render() . " <input type=button name=qdrGoButton value='" . _formulize_QDR_go . "' onclick=\"javascript:showLoading();\"></input>\n<input type='hidden' id='formulize_hidden_daterange_".$handle."' name='search_".$handle."' value='".$search_text."' ></input>\n$js";
   } else {
	return "";
   }
}

// this function writes in the headers for the columns in the results box
function drawHeaders($headers, $cols, $useBoxes=null, $useLinks=null, $numberOfButtons, $lockedColumns=array()) { //, $lockcontrols) {

	static $checkedHelpLink = false;
	static $headingHelpLink;
	if(!$checkedHelpLink) {
		$module_handler =& xoops_gethandler('module');
		$config_handler =& xoops_gethandler('config');
		$formulizeModule =& $module_handler->getByDirname("formulize");
		$formulizeConfig =& $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
		$headingHelpLink = $formulizeConfig['heading_help_link'];
		$checkedHelpLink = true;
	}

	print "<tr>";
	if($useBoxes != 2 OR $useLinks) {
		print "<td class=head>&nbsp;</td>\n";
	}
	for($i=0;$i<count($headers);$i++) {
	
		$classToUse = "head column column".$i;
		if($i==0) {
			print "<td class='head floating-column' id='floatingcelladdress_0'>\n";
		}
		print "<td class='$classToUse' id='celladdress_0_$i'><div class='main-cell-div' id='cellcontents_0_".$i."'>\n";

		if($headingHelpLink) {
			$lockedUI = in_array($i, $lockedColumns) ? "[X]" : "[ ]";
			print "<div style=\"float: right; margin-left: 3px;\"><a href=\"\" id=\"lockcolumn_$i\" class=\"lockcolumn\" title=\""._formulize_DE_FREEZECOLUMN."\">$lockedUI</a></div>\n";
			print "<div style=\"float: right;\"><a href=\"\" onclick=\"javascript:showPop('".XOOPS_URL."/modules/formulize/include/moreinfo.php?col=".$cols[$i]."');return false;\" title=\""._formulize_DE_MOREINFO."\">[?]</a></div>\n";
		}
		print clickableSortLink($cols[$i], printSmart(trans($headers[$i])));
		print "</div></td>\n";
	}
	for($i=0;$i<$numberOfButtons;$i++) {
		print "<td class=head>&nbsp;</td>\n";
	}
	print "</tr>\n";
}

// this function wraps whatever contents are passed in, in HTML that will make it a clickable sorting link, for the specified element
function clickableSortLink($handle, $contents) {
	$sort = strip_tags($_POST['sort']);
	$order = strip_tags($_POST['order']);
	$output = "";
	if($handle == $sort) {
		if($order == "SORT_DESC") {
			$imagename = "desc.gif";
		} else {
			$imagename = "asc.gif";
		}
		$output .= "<img src='" . XOOPS_URL . "/modules/formulize/images/$imagename' align=left>";
	}
	$output .= "<a href=\"\" alt=\"" . _formulize_DE_SORTTHISCOL . "\" title=\"" . _formulize_DE_SORTTHISCOL . "\" onclick=\"javascript:sort_data('" . $handle . "');return false;\">";
	$output .= $contents;
  	$output .= "</a>\n";
	return $output;
}


// this function returns the ele_ids of form elements to show, or the handles of the form elements to show for a framework
function getDefaultCols($fid, $frid="") {
	global $xoopsDB, $xoopsUser;

	if($frid) { // expand the headerlist to include the other forms
		$fids[0] = $fid;
		$check_results = checkForLinks($frid, $fids, $fid, "", "", "", "", "", "", "0");
		$fids = $check_results['fids'];
		$sub_fids = $check_results['sub_fids'];
		$gperm_handler = &xoops_gethandler('groupperm');
		$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
		$uid = $xoopsUser ? $xoopsUser->getVar('uid') : "0";
		$mid = getFormulizeModId();
		$ele_handles = array();
		$processedFids = array();
		foreach($fids as $this_fid) {
			if(security_check($this_fid, "", $uid, "", $groups, $mid, $gperm_handler) AND !isset($processedFids[$this_fid])) {
				$ele_handles = array_merge($ele_handles, getHeaderList($this_fid, true, true));
				$processedFids[$this_fid] = true;
			}
		}
		foreach($sub_fids as $this_fid) {
			if(security_check($this_fid, "", $uid, "", $groups, $mid, $gperm_handler) AND !isset($processedFids[$this_fid])) {
				$ele_handles = array_merge($ele_handles, getHeaderList($this_fid, true, true));
				$processedFids[$this_fid] = true;
			}
		}

		return $ele_handles;
		
	} else {
		$ele_handles = getHeaderList($fid, true, true); // third param causes element handles to be returned instead of IDs
		return $ele_handles;
	}

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
  
  for($i=0;$i<count($cols);$i++) {
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
      // if this calculation is being done on a field that is on the one side of a one to many relationship, then we need to use a special version of the baseQuery
      if($frid) {
        if($frameworkObject->whatSideIsHandleOn($cols[$i]) == "one") {
          $thisBaseQuery = $oneSideBaseQuery;
        } else {
          $thisBaseQuery = $baseQuery;
        }
      } else {
        $thisBaseQuery = $baseQuery;
      }
      
			// figure out if the field is encrypted, and setup the calcElement accordingly
			$calcElementMetaData = formulize_getElementMetaData($handle, true);
			if($calcElementMetaData['ele_encrypt']) {
				$calcElement = "AES_DECRYPT($fidAlias.`$handle`, '".getAESPassword()."')";
			} else {
				$calcElement = "$fidAlias.`$handle`";
			}
      
      // figure out what to ask for for this calculation      
      switch($calc) {
        case "sum":
          $select = "SELECT sum($calcElement) as $fidAlias$handle";
          break;
        case "min":
          $select = "SELECT min($calcElement) as $fidAlias$handle";
          break;
        case "max":
          $select = "SELECT max($calcElement) as $fidAlias$handle";
          break;
        case "count":
          $select = "SELECT count($calcElement) as count$fidAlias$handle, count(distinct($calcElement)) as distinct$fidAlias$handle";
          break;
        case "avg":
          $select = "SELECT avg($calcElement) as avg$fidAlias$handle, std($calcElement) as std$fidAlias$handle";
          $selectAvgCount = "SELECT $calcElement as $fidAlias$handle, count($calcElement) as avgcount$fidAlias$handle";
          break;
        case "per":
          $select = "SELECT $calcElement as $fidAlias$handle, count($calcElement) as percount$fidAlias$handle";
	  include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php"; // need a function here later on
          break;
        default:
          
          break;
      }
            
      // figure out the special where clause conditions that need to be added for this calculation
      list($allowedValues, $excludedValues) = calcParseBlanksSetting($excludes[$cid]);
        
      $allowedWhere = "";  
      if(count($allowedValues)>0) {
        $start = true;
        foreach($allowedValues as $value) {
          if($start) {
            $allowedWhere = " AND (";
            $start = false;
          } else {
            $allowedWhere .= " OR ";
          }
          if($value === "{BLANK}") {
            $allowedWhere .= "($calcElement='' OR $calcElement IS NULL)";
          } else {
            $value = parseUserAndToday($value); // translate {USER} and {TODAY} into literals
            $allowedWhere .= "$calcElement=";
            $allowedWhere .= (is_numeric($value) AND $value !=0) ? $value : "'$value'";
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
          $thisBaseQuery = str_replace("LEFT JOIN " . $replacementTable, "INNER JOIN " . $replacementTable, $thisBaseQuery);
        }
      }
      
      $excludedWhere = "";
      if(count($excludedValues)>0) {
        $start = true;
        foreach($excludedValues as $value) {
          if($start) {
            $excludedWhere = " AND (";
            $start = false;
          } else {
            $excludedWhere .= " AND ";
          }
          if($value === "{BLANK}") {
            $excludedWhere .= "($calcElement!='' AND $calcElement IS NOT NULL)";
          } else {
            $value = parseUserAndToday($value); // translate {USER} and {TODAY} into literals
            $excludedWhere .= "$calcElement!=";
            $excludedWhere .= (is_numeric($value) AND $value !=0) ? $value : "'$value'";
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
          $thisBaseQuery = str_replace("LEFT JOIN " . $replacementTable, "INNER JOIN " . $replacementTable, $thisBaseQuery);
        } 
      }
      
      // figure out the group by clause (grouping is expressed as element ids right now)
      //$groupings[$cid] .= "!@^%*17461!@^%*9402";
      $theseGroupings = explode("!@^%*", $groupings[$cid]);
      $groupByClause = "";
      $groupByClauseMode = "";
      $orderByClause = "";
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
		$select .= ", (SELECT CASE usertable.name WHEN '' THEN usertable.uname ELSE usertable.name END FROM ". DBPRE."users as usertable WHERE usertable.uid = ".$galias.".".$ghandle.") as $galias$ghandle";
		$selectAvgCount .= ", (SELECT CASE usertable.name WHEN '' THEN usertable.uname ELSE usertable.name END FROM ". DBPRE."users as usertable WHERE usertable.uid = ".$galias.".".$ghandle.") as $galias$ghandle";
	} else {
		$select .= ", $galias.`$ghandle` as $galias$ghandle";
	        $selectAvgCount .= ", $galias.`$ghandle` as $galias$ghandle";
	}
      }
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
        $groupByClause = " GROUP BY $fidAlias$handle";
        $orderByClause = " ORDER BY percount$fidAlias$handle DESC";
      }
    
      // do the query
      $calcResult = array();
      $calcResultSQL = "$select $thisBaseQuery $allowedWhere $excludedWhere $groupByClause $orderByClause";
      global $xoopsUser;
      //if($xoopsUser->getVar('uid') == 1) {
      //  print "$calcResultSQL<br><br>";
      //}*/
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
              break;
            case "max":
              foreach($theseGroupings as $gid=>$thisGrouping) {
                if($thisGrouping != "none" AND $thisGrouping != "") {
                  list($ghandle, $galias) = getCalcHandleAndFidAlias($thisGrouping, $fid);
                  $groupingValues[$cols[$i]][$calc][$calcId][] = convertRawValuesToRealValues($thisResult["$galias$ghandle"], $handle, true);
                }
              }
              $masterResults[$cols[$i]][$calc][$calcId] = _formulize_DE_CALC_MAX . ": ".formulize_numberFormat($thisResult["$fidAlias$handle"], $handle);
              break;
            case "count":
              foreach($theseGroupings as $gid=>$thisGrouping) {
                if($thisGrouping != "none" AND $thisGrouping != "") {
                  list($ghandle, $galias) = getCalcHandleAndFidAlias($thisGrouping, $fid);
                  $groupingValues[$cols[$i]][$calc][$calcId][] = convertRawValuesToRealValues($thisResult["$galias$ghandle"], $ghandle, true);
                }
              }
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
              if(count($groupingWhere)>0) {
                $groupingWhere = "AND (".implode(" AND ", $groupingWhere).")";
              } else {
                $groupingWhere = "";
              }
              if(!isset($groupCounts[$groupingWhere])) { // need to figure out the total count for this grouping setting
                $perindexer++;
                $groupingValues[$cols[$i]][$calc][$perindexer] = convertRawValuesToRealValues($groupingValuesFound, $ghandle, true);
                $countSQL = "SELECT count($fidAlias.`$handle`) as count$fidAlias$handle $thisBaseQuery $allowedWhere $excludedWhere $groupingWhere";
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
                // convert the pointers for the linked selectbox values, to their source values
                $sourceMeta = explode("#*=:*", $linkedMetaData[2]);
                $data_handler = new formulizeDataHandler($sourceMeta[0]);
                $rawIndivValues = $data_handler->findAllValuesForEntries($sourceMeta[1], explode(",",trim($thisResult["$fidAlias$handle"], ","))); // trip opening and closing commas and split by comma into an array
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
        $modeQuery = "$selectAvgCount $thisBaseQuery $allowedWhere $excludedWhere $groupByClauseMode ORDER BY ";
        if(count($allGroupings)>0) {
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
          $countSQL = "SELECT count($fidAlias.`$handle`) as count$fidAlias$handle $thisBaseQuery $allowedWhere $excludedWhere $groupingWhere";
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
          $per25SQL = "SELECT $fidAlias.`$handle` as $fidAlias$handle $thisBaseQuery $allowedWhere $excludedWhere $groupingWhere ORDER BY $fidAlias$handle LIMIT $per25Limit,$per25Size";
          //print "$per25SQL<Br><Br>";
          $per75SQL = "SELECT $fidAlias.`$handle` as $fidAlias$handle $thisBaseQuery $allowedWhere $excludedWhere $groupingWhere ORDER BY $fidAlias$handle LIMIT $per75Limit,$per75Size";
          //print "$per75SQL<Br><Br>";
          $per50SQL = "SELECT $fidAlias.`$handle` as $fidAlias$handle $thisBaseQuery $allowedWhere $excludedWhere $groupingWhere ORDER BY $fidAlias$handle LIMIT $per50Limit,$per50Size";
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
          if(count($perPair) < 2) {
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
          if(count($perPair) < 2) {
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
          if(count($perPair) < 2) {
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
            $typeout = "<table cellpadding=3>\n<tr><td style=\"vertical-align: top;\"><u>" . _formulize_DE_PER_ITEM . "</u></td><td style=\"vertical-align: top;\"><u>" . _formulize_DE_PER_COUNT . "</u></td><td style=\"vertical-align: top;\"><u>" . _formulize_DE_PER_PERCENT . "</u></td></tr>\n";
          } else {
            $typeout = "<table cellpadding=3>\n<tr><td style=\"vertical-align: top;\"><u>" . _formulize_DE_PER_ITEM . "</u></td><td style=\"vertical-align: top;\"><u>" . _formulize_DE_PER_COUNT . "</u></td><td style=\"vertical-align: top;\"><u>" . _formulize_DE_PER_PERCENTRESPONSES . "</u></td><td style=\"vertical-align: top;\"><u>" . _formulize_DE_PER_PERCENTENTRIES . "</u></td></tr>\n";
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
            if(count($nameReplacementMap)>0) { $indivText = $nameReplacementMap[$indivText]; } // swap in a name for this user, if applicable
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
	return $to_return;
}

// this function converts raw values from the database to their actual values users should see
// currently handles linked selectboxes and multiple values fields (listboxes and checkboxes)
// this could be made into a replacement for the prepvalues function in the extract.php file that does the same kind of thing when preparing a dataset
// returnFlat is a flag to cause multiple values to be returned as comma separated strings
// value can be an array, and if so, an array will be passed 
function convertRawValuestoRealValues($value, $handle, $returnFlat=false) {
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
			if(strstr($ele_value[2], "#*=:*")) {
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
			$realValues = $data_handler->findAllValuesForEntries($sourceMeta[1], explode(",",trim($thisValue, ","))); // trip opening and closing commas and split by comma into an array
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
	if(count($allRealValuesNames) > 0) {
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
				$allRealValues[] = $users[$thisUid]->getVar($isNamesList);	
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
			$allowed[] = "";
			$allowed[] = 0;
			break;
		case "noblanks";
			$excluded[] = "";
			$excluded[] = 0;
			break;
		case "justnoblanks";
			$excluded[] = "";
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
  				$allowed[] = mysql_real_escape_string(substr($thisSetting,1));
				} else {
					$excluded[] = mysql_real_escape_string($thisSetting);
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
	
  if($handle=="creation_date" OR $handle == "mod_date" OR $handle == "creation_datetime" OR $handle == "mod_datetime" OR $handle == "creator_email") {
    return $value;    
  }
  if($handle == "uid" OR $handle=="proxyid" OR $handle == "creation_uid" OR $handle == "mod_uid") {
    $member_handler = xoops_gethandler('member');
    $userObject = $member_handler->getUser(display($entry, $handle));
    $nameToDisplay = $userObject->getVar('name') ? $userObject->getVar('name') : $userObject->getVar('uname');
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
    if(key($ele_value[2]) === "{USERNAMES}" OR key($ele_value[2]) === "{FULLNAMES}") {
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
function printResults($masterResults, $blankSettings, $groupingSettings, $groupingValues, $filename="", $title="") {

	$output = "";
  foreach($masterResults as $handle=>$calcs) {
		$output .= "<tr><td class=head colspan=2>\n";
		$output .= printSmart(trans(getCalcHandleText($handle)), 100);
		$output .= "\n</td></tr>\n";
    foreach($calcs as $calc=>$groups) {
			$countGroups = count($groups);
     	$output .= "<tr><td class=even rowspan=$countGroups>\n";
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
			switch($blankSettings[$handle][$calc]) {
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
					$setting = explode(",",substr(str_replace("!@^%*", ",", $blankSettings[$handle][$calc]),6)); // replace back the commas and remove the word custom from the front, and explode it into an array
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
			$output .= "<p>$bsetting</p>\n</td>\n";
      $start = 1;
     	foreach($groups as $group=>$result) {
        //foreach($result as $resultID=>$thisResult) {
          if(!$start) { $output .= "<tr>\n"; }
          $start=0;
          $output .= "<td class=odd>\n";
          //if(count($groups)>1) { // OR count($groups)>1) { // output the heading section for this group of results
            $output .= "<p><b>";
            $start2 = true;
            foreach(explode("!@^%*", $groupingSettings[$handle][$calc]) as $id=>$thisGroupSetting) {
              if($thisGroupSetting === "none") { continue; }
              if(!$start2) {
                $output .= "<br>\n";
              }
              $start2 = false;
              $elementMetaData = formulize_getElementMetaData($thisGroupSetting, false);
              $groupText = formulize_swapUIText($groupingValues[$handle][$calc][$group][$id], unserialize($elementMetaData['ele_uitext']));
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
				include XOOPS_ROOT_PATH . "/modules/formulize/class/class.csstidy.php";
			}
			$css = new csstidy();
			$css->set_cfg('merge_selectors',0);
			$css->parse_from_url(XOOPS_ROOT_PATH . "/themes/" . $xoopsConfig['theme_set'] . "/style.css");
			$parsed_css = $css->css;
			// parsed_css seems to have only one key when looking at the default template...key is the number of styles?
			foreach($parsed_css as $thiscss) {
				$head = isset($thiscss['.head']['background-color']) ? $thiscss['.head']['background-color'] : isset($thiscss['.head']['background']) ? $thiscss['.head']['background'] : "";
				$even = isset($thiscss['.even']['background-color']) ? $thiscss['.even']['background-color'] : isset($thiscss['.even']['background']) ? $thiscss['.even']['background'] : "";
				$odd = isset($thiscss['.odd']['background-color']) ? $thiscss['.odd']['background-color'] : isset($thiscss['.odd']['background']) ? $thiscss['.odd']['background'] : "";
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
<meta name=\"generator\" content=\"Formulize -- form creation and data management for XOOPS\" />
<title>" . _formulize_DE_EXPORTCALC_TITLE . " '$title'</title>
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
<h1>" . _formulize_DE_EXPORTCALC_TITLE . " '$title'</h1>
<table class=outer>
$output
</table>
</body>
</html>";		
		// output the file
		$exfilename = strrchr($filename, "/");
		$wpath = XOOPS_ROOT_PATH . SPREADSHEET_EXPORT_FOLDER . "$exfilename";
		$exportfile = fopen($wpath, "w");
		fwrite ($exportfile, $outputfile);
		fclose ($exportfile);
	}
	formulize_benchmark("after creating file");
}

// this function converts a UID to a full name, or user name, if the handle is creation_uid or mod_uid
// also converts blanks to [blank]
function convertUids($value, $handle) {
	if(!is_numeric($value) AND $value == "") { $value = "[blank]"; }
	if($handle != "creation_uid" AND $handle != "mod_uid") { return $value; }
	global $xoopsDB;
	$name_q = q("SELECT name, uname FROM " . $xoopsDB->prefix("users") . " WHERE uid='$value'");
	$name = $name_q[0]['name'];
	if($name) {
		return $name;
	} else {
		return $name_q[0]['uname'];
	}
}

// this function returns the handle corresponding to a given column or grouping value in the requested calculations data, or advanced search query
function calcHandle($value, $fid) {
	$handle = convertElementIdsToElementHandles($value, $fid);
	return $handle[0];
}

// THIS FUNCTION PARSES OUT THE {USER} AND {TODAY} KEYWORDS INTO THEIR LITERAL VALUES
function parseUserAndToday($term) {
  if ($term === "{USER}") {
		global $xoopsUser;
		if($xoopsUser) {
			$term = $xoopsUser->getVar('name');
			if(!$term) { $term = $xoopsUser->getVar('uname'); }
		} else {
			$term = 0;
		}
	}
 	if (ereg_replace("[^A-Z{}]","", $term) === "{TODAY}") {
		$number = ereg_replace("[^0-9+-]","", $term);
		$term = date("Y-m-d",mktime(0, 0, 0, date("m") , date("d")+$number, date("Y")));
	}
  return $term;
}


// this function evaluates a basic part of an advanced search.
// accounts for all the values of a multiple value field, such as a checkbox
// operators: ==, !=, >, <, <=, >=, LIKE, NOT LIKE
function evalAdvSearch($entry, $handle, $op, $term) {
	$result = 0;
	$term = str_replace("\'", "'", $term); // seems that apostrophes are the only things that arrive at this point still escaped.
	$values = display($entry, $handle);
	if($handle == "creation_uid" OR $handle=="mod_uid") {
		$values = convertUids($values, $handle);
	} 
	if ($term == "{USER}") {
		global $xoopsUser;
		if($xoopsUser) {
			$term = $xoopsUser->getVar('name');
			if(!$term) { $term = $xoopsUser->getVar('uname'); }
		} else {
			$term = 0;
		}
	}
 	if (ereg_replace("[^A-Z{}]","", $term) == "{TODAY}") {
		$number = ereg_replace("[^0-9+-]","", $term);
		$term = date("Y-m-d",mktime(0, 0, 0, date("m") , date("d")+$number, date("Y")));
	}
//	code below replaced with the above check by dpicella which accounts for +/- number after {TODAY, ie: {TODAY+10}
//	if ($term == "{TODAY}") {
//		$term = date("Y-m-d");
//	}
	if ($term == "{BLANK}") {
		$term = "";
	}
	switch($op) {
		case "==":
			if(is_array($values)) {
				foreach($values as $value) {
					if($value == $term) { 
						$result = 1; 
						break;
					}
				}
			} else {
				if($values == $term) { $result = 1; }
			}
			break;
		case "!=":
			if(is_array($values)) {
				foreach($values as $value) {
					if($value != $term) { 
						$result = 1; 
						break;
					}
				}
			} else {
				if($values != $term) { $result = 1; }
			}
			break;
		case ">":
			if(is_array($values)) {
				foreach($values as $value) {
					if($value > $term) { 
						$result = 1; 
						break;
					}
				}
			} else {
				if($values > $term) { $result = 1; }
			}
			break;
		case "<":
			if(is_array($values)) {
				foreach($values as $value) {
					if($value < $term) { 
						$result = 1; 
						break;
					}
				}
			} else {
				if($values < $term) { $result = 1; }
			}
			break;
		case "<=":
			if(is_array($values)) {
				foreach($values as $value) {
					if($value <= $term) { 
						$result = 1; 
						break;
					}
				}
			} else {
				if($values <= $term) { $result = 1; }
			}
			break;
		case ">=":
			if(is_array($values)) {
				foreach($values as $value) {
					if($value >= $term) { 
						$result = 1; 
						break;
					}
				}
			} else {
				if($values >= $term) { $result = 1; }
			}
			break;
		case "LIKE":
			if(is_array($values)) {
				foreach($values as $value) {
					if(strstr($value, $term)) { 
						$result = 1; 
						break;
					}
				}
			} else {
				if(strstr($values, $term)) { $result = 1; }
			}
			break;
		case "NOT LIKE":
			if(is_array($values)) {
				foreach($values as $value) {
					if(!strstr($value, $term)) { 
						$result = 1; 
						break;
					}
				}
			} else {
				if(!strstr($values, $term)) { $result = 1; }
			}
			break;
	}
	return $result;
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

<?php
if($useXhr) {
	print " initialize_formulize_xhr();\n";
	drawXhrJavascript();
	print "</script>";
	print "<script type=\"text/javascript\" src=\"".XOOPS_URL."/modules/formulize/libraries/jquery/jquery-1.4.2.min.js\"></script>\n";
	print "<script type='text/javascript'>";
	print "var elementStates = new Array();";
	print "var savingNow = \"\";";
	print "var elementActive = \"\";";
?>
function renderElement(handle,element_id,entryId,fid,check) {
	if(elementStates[handle] == undefined) {
		elementStates[handle] = new Array();
	}
	if(elementStates[handle][entryId] == undefined) {
		if(elementActive) {
			// this is a bit cheap...we should be able to track multiple elements open at once.  But there seem to be race condition issues in the asynchronous requests that we have to track down.  This UI restriction isn't too bad though.
			alert("You need to close the form element that is open first, before you can edit this one.");
			return false;
		}
		elementActive = true;
		elementStates[handle][entryId] = jQuery("#deDiv_"+handle+"_"+entryId).html();
		var formulize_xhr_params = [];
		formulize_xhr_params[0] = handle;
		formulize_xhr_params[1] = element_id;
		formulize_xhr_params[2] = entryId;
		formulize_xhr_params[3] = fid;
		formulize_xhr_send('get_element_html',formulize_xhr_params);
	} else {
		if(check && savingNow == "") {
			savingNow = true;
			jQuery("#deDiv_"+handle+"_"+entryId).fadeTo("fast",0.33);
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
					formulize_xhr_send('get_element_value',formulize_xhr_params);
				}
			});
		} else if(check) {
			// do nothing...only allow one saving operation at a time
		} else {
			jQuery("#deDiv_"+handle+"_"+entryId).html(elementStates[handle][entryId]);
			elementStates[handle].splice(entryId, 1);
			elementActive = "";
		}
	}
}

function renderElementHtml(elementHtml,params) {
	handle = params[0];
	element_id = params[1];
	entryId = params[2];
	fid = params[3];
	jQuery("#deDiv_"+handle+"_"+entryId).html(elementHtml+"<br /><a href=\"\" onclick=\"javascript:renderElement('"+handle+"', "+element_id+", "+entryId+", "+fid+",1);return false;\"><img src=\"<?php print XOOPS_URL; ?>/modules/formulize/images/check.gif\" /></a>&nbsp;&nbsp;&nbsp;<a href=\"\" onclick=\"javascript:renderElement('"+handle+"', "+element_id+", "+entryId+", "+fid+");return false;\"><img src=\"<?php print XOOPS_URL; ?>/modules/formulize/images/x-wide.gif\" /></a>");
}

function renderElementNewValue(elementValue,params) {
	handle = params[0];
	element_id = params[1];
	entryId = params[2];
	fid = params[3];
	jQuery("#deDiv_"+handle+"_"+entryId).fadeTo("fast",1);
	jQuery("#deDiv_"+handle+"_"+entryId).html(elementValue);
	elementStates[handle].splice(entryId, 1);
	savingNow = "";
	elementActive = "";
}

<?php	
}
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
   $('.Check').each(function(){
      $('.Check').attr('checked', true);
   });
}

function unselectAll(uncheck) {
   $('.Check').each(function(){
      $('.Check').attr('checked', false);
   });
}
/* ---------------------------------------
   The selectall and clearall functions are based on a function by
   Vincent Puglia, GrassBlade Software
   site:   http://members.aol.com/grassblad
   
   NOTE: MUST RETROFIT THIS SO IN ADDITION TO CHECKING TYPE, WE ARE CHECKING FOR 'delete_' in the name, so we can have other checkbox elements in the screen templates!
------------------------------------------- */
/*
function selectAll(formObj) 
{
   for (var i=0;i < formObj.length;i++) 
   {
      fldObj = formObj.elements[i];
      if (fldObj.type == 'checkbox')
      { 
         fldObj.checked = true; 
      }
   }
}

function clearAll(formObj)
{
   for (var i=0;i < formObj.length;i++) 
   {
      fldObj = formObj.elements[i];
      if (fldObj.type == 'checkbox')
      { 
         fldObj.checked = false; 
      }
   }
}
*/
function delete_view(formObj, pubstart, endstandard) {

	for (var i=0; i < formObj.currentview.options.length; i++) {
		if (formObj.currentview.options[i].selected) {
			if( i > endstandard && i < pubstart && formObj.currentview.options[i].value != "") {
				var answer = confirm ('<?php print _formulize_DE_CONF_DELVIEW; ?>');
				if (answer) {
					window.document.controls.delview.value = 1;
					window.document.controls.ventry.value = '';
					showLoading();
				} else {
					return false;
				}
			} else {
				if(formObj.currentview.options[i].value != "") {
					alert('<?php print _formulize_DE_DELETE_ALERT; ?>');
				}
				return false;
			}
		}
	}

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
				}
			}
		}
	}
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

function customButtonProcess(caid, entries) {
	window.document.controls.caid.value = caid;
	window.document.controls.caentries.value = entries;
	showLoading();
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

function getPaddingNumber(element,paddingType) {
	var value = element.css(paddingType).replace(/[A-Za-z$-]/g, "");;
	return value;
}

var floatingContents = new Array();

function toggleColumnInFloat(column) {
	jQuery('.column'+column).map(function () {
		var columnAddress = jQuery(this).attr('id').split('_');
		var row = columnAddress[1];
		if(floatingContents[column] == true) {
			jQuery('#floatingcelladdress_'+row+' #cellcontents_'+row+'_'+column).remove();
			jQuery(this).removeClass('now-scrolling');
		} else {
			jQuery('#floatingcelladdress_'+row).append(jQuery(this).html());
			var paddingTop = getPaddingNumber(jQuery(this),'padding-top');
			var paddingBottom = getPaddingNumber(jQuery(this),'padding-bottom');
			var paddingLeft = getPaddingNumber(jQuery(this),'padding-left');
			var paddingRight = getPaddingNumber(jQuery(this),'padding-right');
			jQuery('#floatingcelladdress_'+row+' #cellcontents_'+row+'_'+column).css('width', (parseInt(jQuery(this).width())+parseInt(paddingLeft)+parseInt(paddingRight)));
			jQuery('#floatingcelladdress_'+row+' #cellcontents_'+row+'_'+column).css('height', (parseInt(jQuery(this).height())+parseInt(paddingTop)+parseInt(paddingBottom)));
			jQuery(this).addClass('now-scrolling');
		}
	});
	if(floatingContents[column] == true) {
		floatingContents[column] = false;
	} else {
		floatingContents[column] = true;
	}
}

function setScrollDisplay(element) {
	if(element.scrollLeft() > 0) {
		var maxWidth = 0;
		jQuery(".now-scrolling").css('display', 'none');
		jQuery(".floating-column").css('display', 'table-cell');
	} else {
		jQuery(".floating-column").css('display', 'none');
		jQuery(".now-scrolling").css('display', 'table-cell');
	}
}

jQuery(window).load(function() {
	
	jQuery('.lockcolumn').click(function() {
		var lockData = jQuery(this).attr('id').split('_');
		var column = lockData[1];
		if(floatingContents[column] == true) {
			jQuery(this).empty();
			jQuery(this).append('[ ]');
			var curColumnsArray = jQuery('#formulize_lockedColumns').val().split(',');
			var curColumnsHTML = '';
			for (var i=0; i < curColumnsArray.length; i++) {
				if(curColumnsArray[i] != column) {
					if(curColumnsHTML != '') {
						curColummsHTML = curColumnsHTML+',';
					}
					curColumnsHTML = curColumnsHTML+curColumnsArray[i];
				}
			}
			jQuery('#formulize_lockedColumns').val(curColumnsHTML);
		} else {
			jQuery(this).empty();
			jQuery(this).append('[X]');
			var curColumnsHTML = jQuery('#formulize_lockedColumns').val();
			jQuery('#formulize_lockedColumns').val(curColumnsHTML+','+column);
		}
		toggleColumnInFloat(column);
		return false;
	});
	
	jQuery(window).scrollTop(<?php print intval($_POST['formulize_scrollx']); ?>);
	jQuery(window).scrollLeft(<?php print intval($_POST['formulize_scrolly']); ?>);

<?php

foreach($lockedColumns as $thisColumn) {
	if(is_numeric($thisColumn)) {
		print "toggleColumnInFloat(".intval($thisColumn).");\n";
	}
}


?>
	
	jQuery('#resbox').scroll(function () {
		setScrollDisplay(jQuery('#resbox'));
	});
	jQuery(window).scroll(function () {
		setScrollDisplay(jQuery(window));
	});

	var saveButtonOffset = jQuery('#floating-list-of-entries-save-button').offset();
	saveButtonOffset.left = 15;
	floatSaveButton(saveButtonOffset);
	jQuery(window).scroll(function () {
		floatSaveButton(saveButtonOffset);
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

jQuery(window).scroll(function () {
        jQuery('.floating-column').css('margin-top', ((window.pageYOffset)*-1));
});

</script>
<?php
}

//THIS FUNCTION READS A LEGACY REPORT (ONE GENERATED IN 1.6rc OR PREVIOUS)
function loadOldReport($id, $fid, $view_groupscope) {
/*	need to create the following for passing back...
	$reportscope - ,1,31,45, list of group ids
	$_POST['oldcols'] - 234,56,781 list of ele_ids for visible columns (handles for a framework, but an old report will never be for a framework)
	$_POST['asearch'] - flat array of search elements, separator: --> /,%^&2 <--, possible elements:
		[field]ele_id[/field], ==, !=, <, >, <=, >=, LIKE, NOT, NOT LIKE, AND, OR, ( and )
	$_POST['calc_cols'] - 234/56/781 - list of ele_ids, or can include uid, proxyid, mod_date, creation_date
	$_POST['calc_calcs'] - sum,avg,min,max,count,per/...next column 
	$_POST['calc_blanks'] - all,noblanks,onlyblanks/...next column
	$_POST['calc_grouping'] - none,uid,proxyid,mod_date,creation_date,orlistofele_ids/...next column
	$_POST['sort'] - ele_id for form, handle for framework
	$_POST['order'] - SORT_ASC, SORT_DESC
*/
	global $xoopsDB;
	$s = "&*=%4#";
	// get all data from DB
	$data = q("SELECT report_ispublished, report_scope, report_fields, report_search_typeArray, report_search_textArray, report_andorArray, report_calc_typeArray, report_sort_orderArray, report_ascdscArray, report_globalandor FROM " . $xoopsDB->prefix("formulize_reports") . " WHERE report_id=$id AND report_id_form=$fid");

	// reportscope
	$scope = explode($s, $data[0]['report_scope']);
	if($scope[0] == "") { 
		if($view_groupscope) {
			$found_scope = "group";
		} else {
			$found_scope = "mine";
		}
	} else {
		foreach($scope as $thisscope) {
			if(substr($thisscope, 0, 1) == "g") {
				$found_scope .= "," . substr($thisscope, 1);
			} else { // the case of only userscopes, need to set the scope to the groups that the user is a member of
				$user_scope[] = $thisscope; // save and include as an advanced search property looking for the user id
				if(!$membership_handler) { $membership_handler =& xoops_gethandler('membership'); }
				unset($uidGroups);
				unset($groupString);
				$uidGroups = $membership_handler->getGroupsByUser($thisscope);
				$uidGroups = array_unique($uidGroups);
				// remove registered users from the $uidGroups -- registered users is equivalent to "all groups" since everyone is a member of it
				foreach($uidGroups as $key=>$thisgroup) {
					if($thisgroup == 2) { unset($uidGroups[$key]); }
				}								
				$groupString = implode(",", $uidGroups);				
				$found_scope .= "," . $groupString;
			}
		}
		$found_scope .= ",";
	}

	$to_return[0] = $found_scope;

	// oldcols
	$tempcols = explode($s, $data[0]['report_fields']);
// This conversion now performed as part of DB query
//	foreach($tempcols as $col) {
//		$cols[] = str_replace("`", "'", $col);
//	}
	$ids = convertHeadersToIds($cols, $fid);
	$to_return[1] = implode(",", $ids);

	// asearch - complicated!
	$s2 = "/,%^&2";
	$gao = $data[0]['report_globalandor'];
	if($gao == "and") { $gao = "AND"; }
	if($gao == "or") { $gao = "OR"; }
	$terms = explode($s, $data[0]['report_search_textArray']);
	$tempops = explode($s, $data[0]['report_search_typeArray']);
	foreach($tempops as $thisop) {
		switch($thisop) {
			case "equals":
				$ops[] = "==";
				break;
			case "not":
				$ops[] = "!=";
				break;
			case "like":
				$ops[] = "LIKE";
				break;
			case "notlike":
				$ops[] = "NOT LIKE";
				break;
			case "greaterthan":
				$ops[] = ">";
				break;
			case "greaterthanequal":
				$ops[] = ">=";
				break;
			case "lessthan":
				$ops[] = "<";
				break;
			case "lessthanequal":
				$ops[] = "<=";
				break;
		}
	}
	$laos = explode($s, $data[0]['report_andorArray']);
	$start = 1;

	// for each found column, we should create:
	// ($field $op $term1 $localandor $field $op $term2....)

	for($i=0;$i<count($ids);$i++) {
		if($terms[$i]) {
			if(!$start) {
				$asearch .= $s2 . $gao . $s2;
			}
			$start = 0; 
			$asearch .= "(";
			unset($allterms);
			$allterms = explode(",", $terms[$i]);
			$start2 = 1;
			foreach($allterms as $thisterm) {
				if(!$start2) {
					if($laos[$i] == "and") { $lao = "AND"; }
					if($laos[$i] == "or") { $lao = "OR"; }
					$asearch .= $s2 . $lao;
				}
				$start2 = 0;
				$asearch .= $s2 . "[field]" . $ids[$i] . "[/field]";
				$asearch .= $s2 . $ops[$i];
				$termtouse = str_replace("[,]", ",", $thisterm);
				$asearch .= $s2 . $termtouse;
			}
			$asearch .= $s2 . ")";
		}
	}
	// add in any user_scope found...
	if(count($user_scope)>0) {
		if($asearch) { 
			$asearch .= $s2 . "AND" . $s2 . "(" . $s2; 
			$needtoclose = 1;
		}
		$start = 1;
		foreach($user_scope as $user) {
			if(!$start) {
				$asearch .= $s2 . "OR" . $s2;
			}
			$start = 0;
			$name = convertUids($user, "uid");
			$asearch .= "[field]uid[/field]" . $s2 . "==" . $s2 . $name; 
		}
		if($needtoclose) { $asearch .= $s2 . ")"; }
	}

	$to_return[2] = $asearch;
		
	// calcs - special separator, and then the standard separator within each column (since multiple calcs can be requested)
	$oldcalcs = explode("!@+*+6-", $data[0]['report_calc_typeArray']);
	unset($cols);
	for($i=0;$i<count($ids);$i++) {
		if($oldcalcs[$i]) {
			$cols[] = $ids[$i];
			unset($localcalcs);
			$thesecalcs = explode($s, $oldcalcs[$i]);
			foreach($thesecalcs as $acalc) {
				if(strstr($acalc, "selected")) {
					if(strstr($acalc, "sum")) {
						$localcalcs[] = "sum";
					}
					if(strstr($acalc, "average")) {
						$localcalcs[] = "avg";
					}
					if(strstr($acalc, "min")) {
						$localcalcs[] = "min";
					}
					if(strstr($acalc, "max")) {
						$localcalcs[] = "max";
					}
					if(strstr($acalc, "count")) {
						$localcalcs[] = "count";
					}
					if(strstr($acalc, "percent")) {
						$localcalcs[] = "per";
					}
				}
			}
			$foundcalcs = implode(",", $localcalcs);
			$calcs[] = $foundcalcs;
			unset($theseblanks);
			unset($thesegrouping);
			for($x=0;$x<count($localcalcs);$x++) {
				$theseblanks[] = "all";
				$thesegrouping[] = "none";
			}
			$tempblanks = implode(",", $theseblanks);
			$blanks[] = $tempblanks;
			$tempgrouping = implode(",", $thesegrouping);
			$grouping[] = $tempgrouping;		
		}
	}
	$to_return[3] = implode("/", $cols);
	$to_return[4] = implode("/", $calcs);
	$to_return[5] = implode("/", $blanks);
	$to_return[6] = implode("/", $grouping);

	// sort and order
	$sorts = explode($s, $data[0]['report_sort_orderArray']);
	$orders = explode($s, $data[0]['report_ascdscArray']);
	for($i=0;$i<count($ids);$i++) {
		if($sorts[$i] == 1) {
			$to_return[7] = $ids[$i];
			if($orders[$i] == "ASC") { 
				$to_return[8] = "SORT_ASC"; 
			} else {
				$to_return[8] = "SORT_DESC";
			}
			break;
		}
	}
	if(!$to_return[7]) { $to_return[7] = ""; }
	if(!$to_return[8]) { $to_return[8] = ""; }

	// hide list, hide calcs
	// if ispub includes a 3 then hide list, show calcs
	if(strstr($data[0]['report_ispublished'], "3")) {
		$to_return[9] = 1;
		$to_return[10] = 0;
	} elseif($to_return[3]) {
		$to_return[9] = 1;
		$to_return[10] = 0;
	} else {
		$to_return[9] = 0;
		$to_return[10] = 1;
	}

	// lock controls
	// if ispub includes a 2 or a 3, then lock controls
	if(strstr($data[0]['report_ispublished'], "3") OR strstr($data[0]['report_ispublished'], "2")) {
		$to_return[11] = 1;
	} else {
		$to_return[11] = 0;
	}

	return $to_return;

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
    $thisview = q("SELECT * FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_name='".mysql_real_escape_string($id)."' AND sv_formframe = $formframe AND sv_mainform = $mainform");
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
	return $to_return;
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
	$all_allowed_cols[] = "creation_uid";
	$all_allowed_cols[] = "mod_uid";
	$all_allowed_cols[] = "creation_datetime";
	$all_allowed_cols[] = "mod_datetime";
	$all_allowed_cols[] = "creator_email";
	$all_allowed_cols_raw = getAllColList($fid, $frid, $groups);
	foreach($all_allowed_cols_raw as $form_id=>$values) {
		foreach($values as $id=>$value) {
			if(!in_array($value['ele_handle'], $all_allowed_cols)) {	$all_allowed_cols[] = $value['ele_handle']; }
		}
	}			
	$all_cols_from_view = $cols;
	
	$allowed_cols_in_view = array_intersect($all_allowed_cols, $all_cols_from_view);
	$allowed_cols_in_view = array_values($allowed_cols_in_view);

	return $allowed_cols_in_view;
}

// THIS FUNCTION HANDLES INTERPRETTING A LOE SCREEN TEMPLATE
// $type is the top/bottom setting
// $buttonCodeArray is the available buttons that have been pre-compiled by the drawInterface function
function formulize_screenLOETemplate($screen, $type, $buttonCodeArray, $settings, $messageText) {

	// include necessary files
	if(strstr($screen->getVar($type.'template'), 'buildFilter(')) {
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
			$view{'view'.$viewNumber} = false;
		}
		$viewNumber++;
	}

	// setup any custom buttons	
	$atLeastOneCustomButton = false;
	
	$caCode = array();
	foreach($screen->getVar('customactions') as $caid=>$thisCustomAction) {
		if($thisCustomAction['appearinline']) { continue; } // ignore buttons that are meant to appear inline
		$atLeastOneCustomButton = true;
		list($caCode) = processCustomButton($caid, $thisCustomAction);
		if($caCode) {
			${$thisCustomAction['handle']} = $caCode; // assign the button code that was returned
		}
	}

	// if there is no save button specified in either of the templates, but one is available, then put it in below the list
	if($type == "bottom" AND count($screen->getVar('decolumns')) > 0 AND !$screen->getVar('dedisplay') AND $GLOBALS['formulize_displayElement_LOE_Used'] AND !strstr($screen->getVar('toptemplate'), 'saveButton') AND !strstr($screen->getVar('bottomtemplate'), 'saveButton')) {
		print "<div id=\"floating-list-of-entries-save-button\" class=\"\"><p>$saveButton</p></div>\n";
	}
	
	$thisTemplate = html_entity_decode($screen->getVar($type.'template'));
	if($thisTemplate != "") {
    
    // process the template and output results
		ob_start();
		$evalSuccess = eval($thisTemplate);
		$evalResult = ob_get_clean();
		if($evalSuccess === false) { // eval returns false on a parse error
			print _AM_FORMULIZE_SCREEN_LOE_TEMPLATE_ERROR;
		} else {
			print $evalResult;
		}
		
		// if there are no page nav controls in either template the template, then 
		if($type == "top" AND !strstr($screen->getVar('toptemplate'), 'pageNavControls') AND (!strstr($screen->getVar('bottomtemplate'), 'pageNavControls'))) {
			print $pageNavControls;
		}
	}
	
	// output the message text to the screen if it's not used in the custom templates somewhere
	if($type == "top" AND $messageText AND !strstr($screen->getVar('toptemplate'), 'messageText') AND !strstr($screen->getVar('bottomtemplate'), 'messageText')) {
		print "<p><center><b>$messageText</b></center></p>\n";
	}
	
}

// THIS FUNCTION PROCESSES THE REQUESTED BUTTONS AND GENERATES HTML PLUS SENDS BACK INFO ABOUT THAT BUTTON
// $caid is the id of this button, $thisCustomAction is all the settings for this button, $entries is optional and is a comma separated list of entries that should be modified by this button (only takes effect on inline buttons, and possible future types)
// $entries is the entry ID that should be altered when this button is clicked.  Only sent for inline buttons.
// $entry is only sent from inline buttons, so that any PHP/HTML to be rendered inline has access to all the values of the current entry
function processCustomButton($caid, $thisCustomAction, $entries="", $entry) {

	global $xoopsUser;
	$userGroups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
	if(!is_array($thisCustomAction['groups'])) {
		$thisCustomAction['groups'] = unserialize($thisCustomAction['groups']);	// under some circumstances, this might be serialized?  I think it's being unserialized by the getVar method before it gets this far, but anyway....
	}
	if(is_array($thisCustomAction['groups'])) {
		$groupOverlap = array_intersect($thisCustomAction['groups'], $userGroups);
		if(count($groupOverlap) == 0) {
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
		$caHTML[] = isset($effectProperties['html']) ? $effectProperties['html'] : "";
		$isHTML = isset($effectProperties['html']) ? true : $isHTML;
	}
	if($isHTML) { // code to be rendered in place
		$allHTML = "";
		foreach($caHTML as $thisHTML) {
			ob_start();
			eval($thisHTML);
			$allHTML .= ob_get_clean();
		}
		$caCode = $allHTML;
	} else {
		$nameIdAddOn = $thisCustomAction['appearinline'] ? $nameIdAddOn+1 : "";
		$caCode = "<input type=button style=\"width: 140px;\" name=\"" . $thisCustomAction['handle'] . "$nameIdAddOn\" id=\"" . $thisCustomAction['handle'] . "$nameIdAddOn\" value=\"" . $thisCustomAction['buttontext'] . "\" onclick=\"javascript:customButtonProcess('$caid', '$entries');\">\n";
	}
	
	return array(0=>$caCode, 1=>$caElements, 2=>$caActions, 3=>$caValues, 4=>$thisCustomAction['messagetext'], 5=>$thisCustomAction['applyto'], 6=>$caPHP, 7=>$thisCustomAction['appearinline']);
}

// THIS FUNCTION PROCESSES CLICKED CUSTOM BUTTONS
function processClickedCustomButton($clickedElements, $clickedValues, $clickedActions, $clickedMessageText, $clickedApplyTo, $caPHP, $caInline) {

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
		if(count($clickedEntries) == 0 AND count($GLOBALS['formulize_selectedEntries']) == 0) {
			$clickedEntries[] = "";
		} elseif(count($clickedEntries) == 0) { // if this is not an inline button and there are selected entries, use them (inline buttons override selected checkboxes in this case for now)
			$clickedEntries = $GLOBALS['formulize_selectedEntries'];
		}
		foreach($caPHP as $thisCustomCode) {
			foreach($clickedEntries as $thisClickedEntry) {
				$GLOBALS['formulize_thisEntryId'] = $thisClickedEntry;
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
  		$GLOBALS['formulize_thisEntryId'] = $csEntries[$id]; // sent up to global scope so it can be accessed by the gatherHiddenValues function without the user having to type ", $id" in the function call
			$maxIdReq = 0;
			// don't use "i" in this loop, since it's a common variable name and would potentially conflict with names in the eval'd scope
			// same is true of "thisentry" and other variables here!
			for($ixz=0;$ixz<count($clickedElements);$ixz++) { // loop through all actions for this button
				if($thisEntry == "new" AND $maxIdReq > 0) { $thisEntry = $maxIdReq; } // for multiple effects on the same button, when the button applies to a new entry, reuse the initial id_req that was created during the first effect
				$formulize_lvoverride = false;
				if(strstr($clickedValues[$ixz], "\$value")) {
					eval($clickedValues[$ixz]);
					$valueToWrite = $value;
				} else {
					$valueToWrite = $clickedValues[$ixz];
				}
				$maxIdReq = writeElementValue("", $clickedElements[$ixz], $thisEntry, $valueToWrite, $clickedActions[$ixz], "", $formulize_lvoverride, $csEntries[$id]);
			}
			/*
			// if you pass in $screen, you could try to do something like this...but it would increase overhead, and really, a more unified way of handling writing custom button data and updating derived values, needs to be created.
			if($maxIdReq) {
				$form_handler = xoops_getmodulehandler('forms', 'formulize');
				$element_handler = xoops_getmodulehandler('elements', 'formulize');
				$elementObject = $element_handler->get($clickedElements[0]);
				$formObject = $form_handler->get($elementObject->getVar('id_form'));
				if(array_search("derived", $formObject->getVar('elementTypes'))) { // only bother if there is a derived value in the form
					// NOTE: if there are derived values involving something other than the fid of the updated form, and the frid of the screen, then they won't be updated when this custom button is clicked!!
					$frid = $screen ? $screen->getVar('frid') : 0;
					formulize_updateDerivedValues($maxIdReq, $elementObject->getVar('id_form'), $frid);
				}
			}*/
		}
	}
	return $clickedMessageText;
}

// THIS FUNCTION IS USED ONLY IN LIST OF ENTRIES SCREENS, IN THE VALUES OF CUSTOM BUTTONS
// Use this to gather a specified hidden value for the current entry being processed
// The key of $caEntries above MUST be set to the entry that was selected, or else this will not work
// This function is meant to be called from inside the eval call above where the custom buttons are evaluated
// This function is only meant to work with situations where someone has actually selected an entry (or clicked inline)
function gatherHiddenValue($handle) {
	global $formulize_thisEntryId;
	if($formulize_thisEntryId) {
		return htmlspecialchars(strip_tags($_POST["hiddencolumn_" . $formulize_thisEntryId . "_" . $handle]));
	} else {
		return false;
	}
}

// THIS FUNCTION GENERATES HTML FOR ANY BUTTONS THAT ARE REQUESTED
function formulize_screenLOEButton($button, $buttonText, $settings, $fid, $frid, $colhandles, $flatcols, $pubstart, $loadOnlyView, $calc_cols, $calc_calcs, $calc_blanks, $calc_grouping, $doNotForceSingle, $lastloaded, $currentview, $endstandard, $pickgroups, $viewoptions, $loadviewname, $advcalc_acid) {
  static $importExportCleanupDone = false;
	if($buttonText) {
		$buttonText = trans($buttonText);
		switch ($button) {
			case "changeColsButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=changecols value='" . $buttonText . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/changecols.php?fid=$fid&frid=$frid&cols=$colhandles');\"></input>";
				break;
			case "calcButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=calculations value='" . $buttonText . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/pickcalcs.php?fid=$fid&frid=$frid&calc_cols=".urlencode($calc_cols)."&calc_calcs=".urlencode($calc_calcs)."&calc_blanks=".urlencode($calc_blanks)."&calc_grouping=".urlencode($calc_grouping)."');\"></input>";
				break;
      case "advCalcButton":
				// only if any procedures (advanced calculations) are defined for this form
				$procedureHandler = xoops_getmodulehandler('advancedCalculation','formulize');
				$procList = $procedureHandler->getList($fid);
				if(is_array($procList) AND count($procList) > 0) {
				  return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=advcalculations value='" . $buttonText . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/pickadvcalcs.php?fid=$fid&frid=$frid&$advcalc_acid');\"></input>";
				} else {
					return false;
				}
	      break;
			case "advSearchButton":
				$buttonCode = "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=advsearch value='" . $buttonText . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/advsearch.php?fid=$fid&frid=$frid";
				foreach($settings as $k=>$v) {
					if(substr($k, 0, 3) == "as_") {
						$v = str_replace("'", "&#39;", $v);
						$v = stripslashes($v);
						$buttonCode .= "&$k=" . urlencode($v);
					}
				}
				$buttonCode .= "');\"></input>";
				return $buttonCode;
				break;
			case "exportCalcsButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=export value='" . $buttonText . "' onclick=\"javascript:showExport();\"></input>";
				break;
			case "exportButton":
			case "importButton":
				// need to write the query to the cache folder so it can be picked up when needed
				$exportTime = time();
				
				$queryForExportFile = fopen(XOOPS_ROOT_PATH . "/cache/exportQuery_".$exportTime.".formulize_cached_query_for_export", "w");
				fwrite($queryForExportFile, $fid."\n");
				global $xoopsUser;
				$exportUid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
				fwrite($queryForExportFile, $exportUid."\n");
				fwrite($queryForExportFile, $GLOBALS['formulize_queryForExport']);
				fclose($queryForExportFile);
				// cleanup old export files
        if(!$importExportCleanupDone) {
          formulize_benchmark("before scandir during export/import button creation");
          formulize_scandirAndClean(XOOPS_ROOT_PATH."/cache/", "exportQuery"); 
          formulize_benchmark("after scandir during export/import button creation. ".count($formulize_export_cache_files)." files found");
          $importExportCleanupDone = true;
        }
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
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=delete value='" . $buttonText . "' onclick=\"javascript:delete_view(this.form, '$pubstart', '$endstandard');\"></input>";
				break;
			case "currentViewList":
				$currentViewList = "<b>" . $buttonText . "</b><br><SELECT style=\"width: 350px;\" name=currentview id=currentview size=1 onchange=\"javascript:change_view(this.form, '$pickgroups', '$endstandard');\">\n";
				$currentViewList .= $viewoptions;
				$currentViewList .= "\n</SELECT>\n";
				if(!$loadviewname AND strstr($currentview, ",") AND !$loadOnlyView) { // if we're on a genuine pick-groups view (not a loaded view)...and the load-only-view override is not in place (which eliminates other viewing options besides the loaded view)
					$currentViewList .= "<br><input type=button name=pickdiffgroup value='" . _formulize_DE_PICKDIFFGROUP . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/changescope.php?fid=$fid&frid=$frid&scope=$currentview');\"></input>";		
				}
				return $currentViewList;
				break;
			case "saveButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=deSaveButton value='" . $buttonText . "' onclick=\"javascript:showLoading();\"></input>";
			case "goButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=deSubmitButton value='" . $buttonText . "' onclick=\"javascript:showLoading();\"></input>";
				break;
		}
	} elseif($button == "currentViewList") { // must always set a currentview value in POST even if the list is not visible
		return "<input type=hidden name=currentview value='$currentview'></input>\n";
	} else {
		return false;
	}
}

// THIS FUNCTION RUNS AN ADVANCED SEARCH FILTER ON A DATASET
function formulize_runAdvancedSearch($query_string, $data) {
	if($query_string) {
		$indexer = 0;
		$asearch_parse_error = 0;
		foreach($data as $entry) {
			ob_start();
			eval($query_string); // a constructed query based on the user's input.  $query_result = 1 if it succeeds and 0 if it fails.
			ob_end_clean();
			if($query_result) {
				$found_data[] = $entry;
			} elseif(!isset($query_result)) {
				$asearch_parse_error = 1;
				break;
			}
			unset($data[$indexer]);
			$indexer++;
		}
		unset($data);
		if(count($found_data)>0) { $data = $found_data; }
	}
	return $data;
}

// THIS FUNCTION HANDLES GATHERING A DATASET FOR DISPLAY IN THE LIST
function formulize_gatherDataSet($settings=array(), $searches, $sort="", $order="", $frid, $fid, $scope, $screen="", $currentURL="", $forcequery = 0) {

	// setup "flatscope" so we can compare arrays of groups that make up the scope, from page load to pageload
	if(is_array($scope)) {
		$flatscope = serialize($scope);
	} else {
		$flatscope = $scope;
	}

				 
	// Order of operations for the requested advanced search options
	// 1. unpack the settings necessary for the search
	// 2. loop through the data and store the results, unsetting $data as we go, and then reassigning the found array to $data at the end
	
	// example of as $settings:
/*	global $xoopsUser;
	if($xoopsUser->getVar('uid') == 'j') {
	$settings['as_1'] = "[field]545[/field]";
	$settings['as_2'] = "==";
	$settings['as_3'] = "Ontario";
	$settings['as_4'] = "AND";
	$settings['as_5'] = "(";
	$settings['as_6'] = "[field]557[/field]";
	$settings['as_7'] = "==";
	$settings['as_8'] = "visiting classrooms";
	$settings['as_9'] = "OR";
	$settings['as_10'] = "[field]557[/field]";
	$settings['as_11'] = "==";
	$settings['as_12'] = "advocacy";
	$settings['as_13'] = ")";
	} // end of xoopsuser check
*/
//	545 prov
//	556 are you still interested, yes/no
//	570 where vol with LTS (university name)
//	557 which of following areas... (multi)

	$query_string = "";
	if($settings['as_0']) {
		// build the query string
		// string looks like this:
		//if([query here]) {
		//	$query_result = 1;
		//}
		
		$query_string .= "if(";
		$firstTermNot = false;
		for($i=0;$settings['as_' . $i];$i++) {
			// save query for writing later
			$wq['as_' . $i] = $settings['as_' . $i];
			if(substr($settings['as_' . $i], 0, 7) == "[field]" AND substr($settings['as_' . $i], -8) == "[/field]") { // a field has been found, next two should be part of the query
				$fieldLen = strlen($settings['as_' . $i]);
				$field = substr($settings['as_' . $i], 7, $fieldLen-15); // 15 is the length of [field][/field]
				$field = calcHandle($field, $fid);
				$query_string .= "evalAdvSearch(\$entry, \"$field\", \"";
				$i++;
				$wq['as_' . $i] = $settings['as_' . $i];
				$query_string .= $settings['as_' . $i] . "\", \"";
				$i++;
				$wq['as_' . $i] = $settings['as_' . $i];
				$query_string .= $settings['as_' . $i] . "\")";
			} else {
				if($i==0 AND $settings['as_'.$i] == "NOT") {
					$firstTermNot = true; // must flag initial negations and handle differently
					continue;
				}
				if($firstTermNot == true AND $i==1 AND $settings['as_'.$i] != "(") {
					$firstTermNot = false; // only actually preserve the full negation if the second term is a parenthesis
					$query_string .= " NOT ";
				}
				$query_string .= " " . $settings['as_' . $i] . " ";
			}
		}

		if($firstTermNot) { // if we are looking for the negative of the entire query...
			$query_string .= ") { \$query_result=0; } else { \$query_result=1; }";
		} else {
			$query_string .= ") { \$query_result=1; } else { \$query_result=0; }";
		}
	}

	// build the filter out of the searches array
	$start = 1;
	$filter = "";
	$ORstart = 1;
	$ORfilter = "";
	$individualORSearches = array();
	global $xoopsUser;
	foreach($searches as $key=>$master_one_search) { // $key is handles for frameworks, and ele_handles for non-frameworks.

		// convert "between 2001-01-01 and 2002-02-02" to a normal date filter with two dates
		$count = preg_match("/^[bB][eE][tT][wW][eE][eE][nN] ([\d]{1,4}[-][\d]{1,2}[-][\d]{1,4}) [aA][nN][dD] ([\d]{1,4}[-][\d]{1,2}[-][\d]{1,4})\$/", $master_one_search, $matches);
		if ($count > 0) {
			$master_one_search = ">={$matches[1]}//<={$matches[2]}";
		}

		// split search based on new split string
		$searchArray = explode("//", $master_one_search);
		
		foreach($searchArray as $one_search) {
		
			$addToItsOwnORFilter = false; // used for trapping the {BLANK} keywords into their own space so they don't interfere with each other, or other filters
		
      // remove the qsf_ parts to make the quickfilter searches work
      if(substr($one_search, 0, 4)=="qsf_") {
        $qsfparts = explode("_", $one_search);
				// need to determine if the key is a multi selection element or not.  If it is, then this should not be a straight equals!
        $one_search = "=".$qsfparts[2];
      }
	
			// strip out any starting and ending ! that indicate that the column should not be stripped
			if(substr($one_search, 0, 1) == "!" AND substr($one_search, -1) == "!") {
				$one_search = substr($one_search, 1, -1);
			}
			
			// look for OR indicators...if all caps OR is at the front, then that means that this search is to put put into a separate set of OR filters that gets appended as a set to the main set of AND filters
      $addToORFilter = false; // flag to indicate if we need to apply the current search term to a set of "OR'd" terms			
			if(substr($one_search, 0, 2) == "OR" AND strlen($one_search) > 2) {
				$addToORFilter = true;
				$one_search = substr($one_search, 2);
			}
			
			
			// look for operators
			$operators = array(0=>"=", 1=>">", 2=>"<", 3=>"!");
			$operator = "";
			if(in_array(substr($one_search, 0, 1), $operators)) {
				// operator found, check to see if it's <= or >= and set start point for term accordingly
				$startpoint = (substr($one_search, 0, 2) == ">=" OR substr($one_search, 0, 2) == "<=" OR substr($one_search, 0, 2) == "!=" OR substr($one_search, 0, 2) == "<>") ? 2 : 1;
				$operator = substr($one_search, 0, $startpoint);
        if($operator == "!") { $operator = "NOT LIKE"; }
				$one_search = substr($one_search, $startpoint);
				
			}
			
			// look for blank search terms and convert them to {BLANK} so they are handled properly
			if($one_search === "") {
				$one_search = "{BLANK}";
			}
			
			// look for { } and transform special terms into what they should be for the filter
			if(substr($one_search, 0, 1) == "{" AND substr($one_search, -1) == "}") {
				$searchgetkey = substr($one_search, 1, -1);
        
				if (ereg_replace("[^A-Z]","", $searchgetkey) == "TODAY") {
					$number = ereg_replace("[^0-9+-]","", $searchgetkey);
					$one_search = date("Y-m-d",mktime(0, 0, 0, date("m") , date("d")+$number, date("Y")));
				} elseif($searchgetkey == "USER") {
					if($xoopsUser) {
						$one_search = $xoopsUser->getVar('name');
						if(!$one_search) { $one_search = $xoopsUser->getVar('uname'); }
					} else {
						$one_search = 0;
					}
				} elseif($searchgetkey == "USERNAME") {
					if($xoopsUser) {
						$one_search = $xoopsUser->getVar('uname');
					} else {
						$one_search = "";
					}
				} elseif($searchgetkey == "BLANK") { // special case, we need to construct a special OR here that will look for "" OR IS NULL
				  if($operator == "!=" OR $operator == "NOT LIKE") {
				    $blankOp1 = "!=";
				    $blankOp2 = " IS NOT NULL ";
				  } else {
				    $addToItsOwnORFilter = $addToORFilter ? false : true; // if this is not going into an OR filter already because the user asked for it to, then let's
				    $blankOp1 = "=";
				    $blankOp2 = " IS NULL ";
				  }
				  $one_search = "/**/$blankOp1][$key/**//**/$blankOp2";
				  $operator = ""; // don't use an operator, we've specially constructed the one_search string to have all the info we need
				} elseif($searchgetkey == "PERGROUPFILTER") {
					$one_search = $searchgetkey;
					$operator = "";
				} elseif(isset($_POST[$searchgetkey]) OR isset($_GET[$searchgetkey])) {
					$one_search = $_POST[$searchgetkey] ? htmlspecialchars(strip_tags($_POST[$searchgetkey])) : "";
					$one_search = (!$one_search AND $_GET[$searchgetkey]) ? htmlspecialchars(strip_tags($_GET[$searchgetkey])) : $one_search;
					if(!$one_search) {
						continue;
					}
				} elseif($searchgetkey) { // we were supposed to find something above, but did not, so there is a user defined search term, which has no value, ergo disregard this search term
					continue;
				} else {
					$one_search = "";
					$operator = "";
				}
			}
			
			// do additional search for {USERNAME} or {USER} in case they are embedded in another string
			if($xoopsUser) {
				$one_search = str_replace("{USER}", $xoopsUser->getVar('name'), $one_search);
				$one_search = str_replace("{USERNAME}", $xoopsUser->getVar('uname'), $one_search);
			}

			
			if($operator) {
				$one_search = $one_search . "/**/" . $operator;
			}
			if($addToItsOwnORFilter) {
				$individualORSearches[] = $key ."/**/$one_search";
			} elseif($addToORFilter) {
				if(!$ORstart) { $ORfilter .= "]["; }
				$ORfilter .= $key . "/**/$one_search"; // . mysql_real_escape_string($one_search); // mysql_real_escape_string no longer necessary here since the extraction layer does the necessary dirty work for us
				$ORstart = 0;
			} else {
				if(!$start) { $filter .= "]["; }
				$filter .= $key . "/**/$one_search"; // . mysql_real_escape_string($one_search); // mysql_real_escape_string no longer necessary here since the extraction layer does the necessary dirty work for us
				$start = 0;
			}
			
		}
	}
	//print $filter;
	// if there's a set of options that have been OR'd, then we need to construction a more complex filter
	
	if($ORfilter OR count($individualORSearches)>0) {
		$filterIndex = 0;
		$arrayFilter[$filterIndex][0] = "and";
		$arrayFilter[$filterIndex][1] = $filter;
		if($ORfilter) {
			$filterIndex++;
			$arrayFilter[$filterIndex][0] = "or";
			$arrayFilter[$filterIndex][1] = $ORfilter;
		}
		if(count($individualORSearches)>0) {
			foreach($individualORSearches as $thisORfilter) {
				$filterIndex++;
				$arrayFilter[$filterIndex][0] = "or";
				$arrayFilter[$filterIndex][1] = $thisORfilter;
			}
		}
		$filter = $arrayFilter;
		$filterToCompare = serialize($filter);
	} else {
		$filterToCompare = $filter;
	}
	
	$regeneratePageNumbers = false;
	// handle magic quotes if necessary
	if(get_magic_quotes_gpc()) {
		$_POST['formulize_previous_filter'] = stripslashes($_POST['formulize_previous_filter']);
		$_POST['formulize_previous_querystring'] = stripslashes($_POST['formulize_previous_querystring']);
		$_POST['formulize_previous_scope'] = stripslashes($_POST['formulize_previous_scope']);
	}
	
	
	if($frid) { // if there's a framework, figure out all the forms in the framework and check if any of them had data saved on this pageload
		$framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
		$frameworkObject = $framework_handler->get($frid);
		$readElementsWasRunOnAForm = false;
		foreach($frameworkObject->getVar('fids') as $thisFid) {
			if(isset($GLOBALS['formulize_allWrittenEntryIds'][$thisFid])) {
				$readElementsWasRunOnAForm = true;
				break;
			}
		}
	} else {
		$readElementsWasRunOnAForm = isset($GLOBALS['formulize_allWrittenEntryIds'][$fid]) ? true : false;
	}
	
  /*
	global $xoopsUser;
	if($xoopsUser) {
		if($xoopsUser->getVar('uid') == 1) {
			print "<br>formulize cacheddata: ". $settings['formulize_cacheddata'];
			print "<br>forcequery: $forcequery";
			print "<br>lastentry: ".$_POST['lastentry'];
			print "<br>deletion requests: ".$GLOBALS['formulize_deletionRequested'];
			print "<br>writeElementValue: ".$GLOBALS['formulize_writeElementValueWasRun'];
			print "<br>readElements: ".$readElementsWasRunOnAForm;
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
    if($formulize_LOEPageSize) {
      $limitStart = (isset($_POST['formulize_LOEPageStart']) AND !$regeneratePageNumbers) ? intval($_POST['formulize_LOEPageStart']) : 0;
      $limitSize = $formulize_LOEPageSize;
    } else {
      $limitStart = 0;
      $limitSize = 0;
    }
    //print "limitStart: $limitStart<br>limitSize: $limitSize<br>";
    
		$GLOBALS['formulize_getCountForPageNumbers'] = true; // flag used to trigger setting of the count of entries in the dataset
		$data = getData($frid, $fid, $filter, "AND", $scope, $limitStart, $limitSize, $sort, $order, $forcequery);
    
    if($currentURL=="") { return array(0=>"", 1=>"", 2=>""); } //current URL should only be "" if this is called directly by the special formulize_getCalcs function
    
		if($query_string AND is_array($data)) { $data = formulize_runAdvancedSearch($query_string, $data); } // must do advanced search after caching the data, so the advanced search results are not contained in the cached data.  Otherwise, we would have to rerun the base extraction every time we wanted to change just the advanced search query.  This way, advanced search changes can just hit the cache, and not the db.

	
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
		if($screen) { $currentviewResetForm = $screen->getVar('defaultview'); } // override the default set by $settings...must do this here and not above, since this should only apply to the resetview form
		print "<input type=hidden name=currentview value='$currentviewResetForm'>\n";
		print "<input type=hidden name=userClickedReset value=1>\n";
		print "</form>\n";
	}

	if($useWorking) {
		// working message
		global $xoopsConfig;
		print "<div id=workingmessage style=\"display: none; position: absolute; width: 100%; right: 0px; text-align: center; padding-top: 50px;\">\n";
		if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/images/working-".$xoopsConfig['language'].".gif") ) {
			print "<img src=\"" . XOOPS_URL . "/modules/formulize/images/working-" . $xoopsConfig['language'] . ".gif\">\n";
		} else {
			print "<img src=\"" . XOOPS_URL . "/modules/formulize/images/working-english.gif\">\n";
		}
		print "</div>\n";
	}

	print "<div id=listofentries>\n";

	print "<form name=controls id=controls action=$currentURL method=post onsubmit=\"javascript:showLoading();\">\n";
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
	$to_return[1] = $wq;
	$to_return[2] = $regeneratePageNumbers;
	return $to_return;
}

// THIS FUNCTION CALCULATES THE NUMBER OF PAGES AND DRAWS HTML FOR NAVIGATING THEM
function formulize_LOEbuildPageNav($data, $screen, $regeneratePageNumbers) {
	if(!is_array($data)) { $data = array(); } // $data can now be a flag that says "Limit Reached"
	$pageNav = "";
	//print "Passed pagestart: " . $_POST['formulize_LOEPageStart'] . "<br>";
	$pageStart = (isset($_POST['formulize_LOEPageStart']) AND !$regeneratePageNumbers) ? intval($_POST['formulize_LOEPageStart']) : 0; // regenerate essentially causes the user to jump back to page 0 because something about the dataset has fundamentally changed (like a new search term or something)
	//print "Actual pagestart: $pageStart<br>";
	print "\n<input type=hidden name=formulize_LOEPageStart id=formulize_LOEPageStart value=\"$pageStart\">\n"; // will receive via javascript the page number that was clicked, or will cause the current page to reload if anything else happens
	$numberPerPage = is_object($screen) ? $screen->getVar('entriesperpage') : 10;
	if($numberPerPage == 0 OR $_POST['hlist']) { return $pageNav; } // if all entries are supposed to be on one page for this screen, then return no navigation controls.  Also return nothing if the list is hidden.
	$allPageStarts = array();
	$pageNumbers = 0;
	for($i=0;$i<$GLOBALS['formulize_countMasterResultsForPageNumbers'];$i=$i+$numberPerPage) {
		$pageNumbers++;
		$allPageStarts[$pageNumbers] = $i;
	}
	$userPageNumber = $pageStart > 0 ? ($pageStart/$numberPerPage)+1 : 1;
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
		$pageNav .= "\n<p><nobr>";
		$pageNav .= "<b>" . _AM_FORMULIZE_LOE_ONPAGE . $userPageNumber . ".</b>&nbsp;&nbsp;[&nbsp;&nbsp;";
		if($firstDisplayPage > 1) {
			$pageNav .= "<a href=\"\" onclick=\"javascript:pageJump('0');return false;\">" . 1 . "</a>&nbsp;&nbsp;...&nbsp;&nbsp;";
		}
		//print "$firstDisplayPage<br>$lastDisplayPage<br>";
		for($i=$firstDisplayPage;$i<=$lastDisplayPage;$i++) {
			//print "$i<br>";
			$thisPageStart = ($i*$numberPerPage)-$numberPerPage;
			if($thisPageStart == $pageStart) {
				$pageNav .= "<b>$i</b>";
			} else {
				$pageNav .= "<a href=\"\" onclick=\"javascript:pageJump('$thisPageStart');return false;\">$i</a>";
			}
			$pageNav .= "&nbsp;&nbsp;";
		}
		if($lastDisplayPage < $pageNumbers) {
			$lastPageStart = ($pageNumbers*$numberPerPage)-$numberPerPage;
			$pageNav .= "...&nbsp;&nbsp;<a href=\"\" onclick=\"javascript:pageJump('$lastPageStart');return false;\">" . $pageNumbers . "</a>&nbsp;&nbsp;";
		}
		$pageNav .= "]</nobr></p>\n";
	}
	return $pageNav;	
}
