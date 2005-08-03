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

// main function
function displayEntries($formframe, $mainform="", $loadview="") {

	global $xoopsDB, $xoopsUser;
	include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

	// Set some required variables
	$mid = getFormulizeModId();
	list($fid, $frid) = getFormFramework($formframe, $mainform);
	$gperm_handler = &xoops_gethandler('groupperm');
	$member_handler =& xoops_gethandler('member');
	$groups = $xoopsUser ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS;
	$uid = $xoopsUser->getVar('uid');

	if(!$scheck = security_check($fid, "", $uid, "", $groups, $mid, $gperm_handler, "")) {
		print "<p>" . _NO_PERM . "</p>";
		return;
	}

	// check for group and global permissions
	$view_globalscope = $gperm_handler->checkRight("view_globalscope", $fid, $groups, $mid);
	$view_groupscope = $gperm_handler->checkRight("view_groupscope", $fid, $groups, $mid);

	// Question:  do we need to add check here to make sure that $loadview is an available report (move function call from the generateViews function) and if it is not, then nullify
	// we may want to be able to pass in any old report, it's kind of like a way to override the publishing process.  Problem is unpublished reports or reports that aren't actually published to the user won't show up in the list of views.
	// [update: loaded views do not include the list of views, they have no interface at all except quick searches and quick sorts.  Since the intention is clearly for them to be accessed through pageworks, we will leave the permission control up to the application designer for now]

	$currentURL = getCurrentURL();

	// get title
	$displaytitle = getFormTitle($fid);

	// get default info and info passed to page....

	// check for deletion request and then delete entries
	if($_POST['delconfirmed']) { // only gets set by clicking on the delete selected button
		foreach($_POST as $k=>$v) {
			if(substr($k, 0, 7) == "delete_" AND $v != "") {
				$thisentry = substr($k, 7);
				deleteEntry($thisentry);
			}
		}
	}

	// handle deletion of view...reset currentView
	if($_POST['delview']) {
		if(substr($_POST['delviewid'], 1, 4) == "old_") {
			$sql = "DELETE FROM " . $xoopsDB->prefix("form_reports") . " WHERE report_id='" . substr($_POST['delviewid'], 5) . "'";
		} else {
			$sql = "DELETE FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_id='" . substr($_POST['delviewid'], 1) . "'";
		}
		if(!$res = $xoopsDB->query($sql)) {
			exit("Error deleting report: " . $_POST['delviewid']);
		}
		unset($_POST['currentview']);
		$_POST['resetview'] = 1;
	}
	// if resetview is set, then unset POST and then set currentview to resetview
	// intended for when a user switches from a locked view back to a basic view.  In that case we want all settings to be cleared and everything to work like the basic view, rather than remembering, for instance, that the previous view had a calculation or a search of something.
	// users who view reports (views) that aren't locked can switch back to a basic view and retain settings.  This is so they can make changes to a view and then save the updates.  It is also a little confusing to switch from a predefined view to a basic one but have the predefined view's settings still hanging around.
	// recommendation to users should be to lock the controls for all published views.
	// (this routine also invoked when a view has been deleted)
	if($_POST['resetview']) {
		$resetview = $_POST['currentview'];
		foreach($_POST as $k=>$v) {
			unset($_POST[$k]);
		}
		$_POST['currentview'] = $resetview;
	}

	// handle saving of the view if that has been requested
	if($_POST['saveid']) {
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
		$saveid = $_POST['saveid'];
		$_POST['lockcontrols'] = $_POST['savelock'];
		$savegroups = $_POST['savegroups'];

		// put name into loadview
		if($saveid != "new") {
			if(strstr($saveid, "old_")) { // legacy
				$sname = q("SELECT report_name FROM " . $xoopsDB->prefix("form_reports") . " WHERE report_id = \"" . substr($saveid, 5) . "\"");
				$savename = $sname[0]['report_name'];
			} else {
				$sname = q("SELECT sv_name, sv_owner_uid FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_id = \"" . substr($saveid, 1) . "\"");
				$savename = $sname[0]['sv_name'];
				if($sname[0]['sv_owner_uid'] == $uid) {
					$loadedView = $saveid;
				} else {
					$loadedView =  "p" . substr($saveid, 1);
				}
			}
		} else {
			$savename = $_POST['savename'];
		}

		// flatten quicksearches -- one value in the array for every column in the view
		$allcols = explode(",", $_POST['oldcols']);
		foreach($allcols as $thiscol) {
			$allquicksearches[] = $_POST['search_' . $thiscol];
		}
		$qsearches = implode("&*=%4#", $allquicksearches);

		$savename = mysql_real_escape_string($savename);
		$savesearches = mysql_real_escape_string($_POST['asearch']);
		$qsearches = mysql_real_escape_string($qsearches);

		if($frid) { 
			$saveformframe = $frid;
			$savemainform = $fid;
		} else {
			$saveformframe = $fid;
			$savemainform = "";
		}

		if($saveid == "new" OR strstr($saveid, "old_")) {
			if ($saveid == "new") {
				$owneruid = $uid;
				$moduid = $uid;
			} else {
				// get existing uid
				$olduid = q("SELECT report_uid FROM " . $xoopsDB->prefix("form_reports") . " WHERE report_id = '" . substr($saveid, 5) . "'");
				$owneruid = $olduid[0]['report_uid'];
				$moduid = $uid;
			}
			$savesql = "INSERT INTO " . $xoopsDB->prefix("formulize_saved_views") . " (sv_name, sv_pubgroups, sv_owner_uid, sv_mod_uid, sv_formframe, sv_mainform, sv_lockcontrols, sv_hidelist, sv_hidecalc, sv_asearch, sv_sort, sv_order, sv_oldcols, sv_currentview, sv_calc_cols, sv_calc_calcs, sv_calc_blanks, sv_calc_grouping, sv_quicksearches) VALUES (\"$savename\", \"$savegroups\", \"$owneruid\", \"$moduid\", \"$saveformframe\", \"$savemainform\", \"$savelock\", \"{$_POST['hlist']}\", \"{$_POST['hcalc']}\", \"$savesearches\", \"{$_POST['sort']}\", \"{$_POST['order']}\", \"{$_POST['oldcols']}\", \"{$_POST['savescope']}\", \"{$_POST['calc_cols']}\", \"{$_POST['calc_calcs']}\", \"{$_POST['calc_blanks']}\", \"{$_POST['calc_grouping']}\", \"$qsearches\")";
		} else {
			$savesql = "UPDATE " . $xoopsDB->prefix("formulize_saved_views") . " SET sv_pubgroups=\"$savegroups\", sv_mod_uid=\"$uid\", sv_lockcontrols=\"$savelock\", sv_hidelist=\"{$_POST['hlist']}\", sv_hidecalc=\"{$_POST['hcalc']}\", sv_asearch=\"$savesearches\", sv_sort=\"{$_POST['sort']}\", sv_order=\"{$_POST['order']}\", sv_oldcols=\"{$_POST['oldcols']}\", sv_currentview=\"{$_POST['savescope']}\", sv_calc_cols=\"{$_POST['calc_cols']}\", sv_calc_calcs=\"{$_POST['calc_calcs']}\", sv_calc_blanks=\"{$_POST['calc_blanks']}\", sv_calc_grouping=\"{$_POST['calc_grouping']}\", sv_quicksearches=\"$qsearches\" WHERE sv_id = \"" . substr($saveid, 1) . "\"";
		}

		// save the report
		if(!$result = $xoopsDB->query($savesql)) {
			exit("Error:  unable to save the current view settings.  SQL dump: $savesql");
		}
		if($saveid == "new" OR strstr($saveid, "old_")) {
			if($owneruid == $uid) {
				$loadedView = "s" . $xoopsDB->getInsertId();
			} else {
				$loadedView = "p" . $xoopsDB->getInsertId();
			}
		}
		$settings['loadedview'] = $loadedView;

		// delete legacy report if necessary
		if(strstr($saveid, "old_")) {
			$dellegacysql = "DELETE FROM " . $xoopsDB->prefix("form_reports") . " WHERE report_id=\"" . substr($saveid, 5) . "\"";
			if(!$result = $xoopsDB->query($dellegacysql)) {
				exit("Error:  unable to delete legacy report: " . substr($saveid, 5));
			}
		}

	}




	// set currentView to group if they have groupscope permission (overridden below by value sent from form)
	// override with loadview if that is specified
	if($loadview AND !$_POST['currentview'] AND $_POST['advscope'] == "") {
		if(substr($loadview, 0, 4) == "old_") { // this is a legacy view
			$loadview = "s" . $loadview;
		} elseif(is_numeric($loadview)) { // new view id
			$loadview = "s" . $loadview;
		} else { // new view name -- loading view by name -- note if two reports have the same name, then the first one created will be returned
			$viewnameq = q("SELECT sv_id FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_name='$loadview' ORDER BY sv_id");
			$loadview = "s" . $viewnameq[0]['sv_id'];
		}
		$_POST['currentview'] = $loadview;
		$_POST['loadreport'] = 1;
	} elseif($view_groupscope) {
		$currentView = "group";
	} else {
		$currentView = "mine";
	} 

	

	// debug block to show key settings being passed back to the page
/*	print "delview: " . $_POST['delview'] . "<br>";
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
*/

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
/*			print "<br>Currentview: " . $_POST['currentview'] . "<br>Oldcols: ";
			print $_POST['oldcols'] . "<br>asearch: ";
			print $_POST['asearch'] . "<br>calc_cols: ";
			print $_POST['calc_cols'] . "<br>calc_calcs: ";
			print $_POST['calc_calcs'] . "<br>calc_blanks: ";
			print $_POST['calc_blanks'] . "<br>calc_grouping: ";
			print $_POST['calc_grouping'] . "<br>sort: ";
			print $_POST['sort'] . "<br>order: ";
			print $_POST['order'] . "<br>"; 
			print $_POST['hlist'] . "<br>"; 
			print $_POST['hcalc'] . "<br>"; 
			print $_POST['lockcontrols'] . "<br>"; 
*/
		} elseif(is_numeric(substr($_POST['currentview'], 1))) { // saved or published view
			$loadedView = $_POST['currentview'];
			$settings['loadedview'] = $loadedView;
			// kill the quicksearches
			foreach($_POST as $k=>$v) {
				if(substr($k, 0, 7) == "search_" AND $v != "") {
					unset($_POST[$k]);
				}
			}
			list($_POST['currentview'], $_POST['oldcols'], $_POST['asearch'], $_POST['calc_cols'], $_POST['calc_calcs'], $_POST['calc_blanks'], $_POST['calc_grouping'], $_POST['sort'], $_POST['order'], $_POST['hlist'], $_POST['hcalc'], $_POST['lockcontrols'], $quicksearches) = loadReport(substr($_POST['currentview'], 1));
			// explode quicksearches into the search_ values
			$allqsearches = explode("&*=%4#", $quicksearches);
			$colsforsearches = explode(",", $_POST['oldcols']);
			for($i=0;$i<count($allqsearches);$i++) {
				if($allqsearches[$i] != "") {
					$_POST["search_" . $colsforsearches[$i]] = $allqsearches[$i];
				}
			}
		}
		$currentView = $_POST['currentview']; 
	} elseif($_POST['advscope']) {
		$currentView = $_POST['advscope'];
	} elseif($_POST['currentview']) { // could have been unset by deletion of a view or something else, so we must check to make sure it exists before we override the default that was determined above
		$currentView = $_POST['currentview'];
	} elseif($loadview) {
		$currentView = $loadview;
	}

	// get columns for this form/framework or use columns sent from interface
	// ele_ids for a form, handles for a framework, includes handles of all unified display forms
	if($_POST['oldcols']) {
		$showcols = explode(",", $_POST['oldcols']); 
	} else { // or use the defaults
		$showcols = getDefaultCols($fid, $frid);
	}


	if($_POST['newcols']) {
		$temp_showcols = $_POST['newcols'];
		$showcols = explode(",", $temp_showcols);
		if($frid) { // convert ids to form handles for a framework
			$temp_handles = convertIds($showcols, $frid);
			unset($showcols);
			$showcols = $temp_handles;
		}
	}
	

	// Create settings array to pass to form page or to other functions

	$settings['title'] = $displaytitle;

	// get export options
	if($_POST['xport']) {
		$settings['xport'] = $_POST['xport'];
		if($_POST['xport'] == "custom") {
			$settings['xport_cust'] = $_POST['xport_cust'];
		}
	}

	// generate the available views

	// pubstart used to indicate to the delete button where the list of published views begins in the current view drop down (since you cannot delete published views)
	list($settings['viewoptions'], $settings['pubstart'], $settings['endstandard'], $settings['pickgroups'], $settings['loadviewname'], $settings['curviewid']) = generateViews($fid, $uid, $groups, $frid, $currentView, $loadedView, $view_groupscope, $view_globalscope, $_POST['curviewid']);

	// this param only used in case of loading of reports via passing in the report id or name through $loadview
	if($_POST['loadviewname']) { $settings['loadviewname'] = $_POST['loadviewname']; }

	// if a view was loaded, then update the lastloaded value, otherwise preserve the previous value
	if($settings['curviewid']) { 
		$settings['lastloaded'] = $settings['curviewid']; 
	} else {
		$settings['lastloaded'] = $_POST['lastloaded'];
	}


	$settings['currentview'] = $currentView;

	$settings['currentURL'] = $currentURL; 

	$settings['columns'] = $showcols;	

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
				$diff = array_diff($viewgroups, $groups);
				if(!isset($diff[0]) AND $view_groupscope) { // if the scopegroups are completely included in the group membership of the user and they have groupscope (ie: they would be allowed to see all these entries anyway)
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

	if($_POST['newcols']) {
		$settings['oldcols'] = $_POST['newcols'];
	} else {
		$settings['oldcols'] = $_POST['oldcols'];
	}

	$settings['ventry'] = $_POST['ventry'];

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

	// get all requested calculations...assign to settings array.
	$settings['calc_cols'] = $_POST['calc_cols'];	
	$settings['calc_calcs'] = $_POST['calc_calcs'];
	$settings['calc_blanks'] = $_POST['calc_blanks'];
	$settings['calc_grouping'] = $_POST['calc_grouping'];

	if($_POST['ventry']) { // user clicked on a view this entry link
		include_once XOOPS_ROOT_PATH . '/modules/formulize/include/formdisplay.php';

		if($_POST['ventry'] == "addnew") {
			$this_ent = "";
		} elseif($_POST['ventry'] == "proxy") {
			$this_ent = "proxy";
		} else {
			$this_ent = $_POST['ventry'];
		}

		if($frid) {
			displayForm($frid, $this_ent, $fid, $currentURL, "", $settings); // "" is the done text
			return;
		} else {
			displayForm($fid, $this_ent, "", $currentURL, "", $settings); // "" is the done text
			return;
		}
	
	} 

	drawInterface($settings, $fid, $frid, $groups, $mid, $gperm_handler, $loadview); 


	// build filter for extraction layer

//	if($reportscope) {
//		$scope = buildScope($reportscope, $member_handler, $uid, $groups);
//	} else {
		$scope = buildScope($currentView, $member_handler, $uid, $groups);
//	}

	drawEntries($fid, $showcols, $_POST['sort'], $_POST['order'], $searches, $frid, $scope, "", $currentURL, $gperm_handler, $uid, $mid, $groups, $settings, $member_handler, $loadview);

	
}

// return the available current view settings based on the user's permissions
function generateViews($fid, $uid, $groups, $frid="0", $currentView, $loadedView="", $view_groupscope, $view_globalscope, $prevview="") {
	global $xoopsDB;

	$options = "<option value=\"\">" . _formulize_DE_STANDARD_VIEWS . "</option>\n";
	$vcounter = 0;
	
	if($currentView == "mine") {
		$options .= "<option value=mine selected>&nbsp;&nbsp;" . _formulize_DE_MINE . "</option>\n";
	} else {
		$options .= "<option value=mine>&nbsp;&nbsp;" . _formulize_DE_MINE . "</option>\n";
	}
	$vcounter++;

	if($currentView == "group" AND $view_groupscope) {
		$options .= "<option value=group selected>&nbsp;&nbsp;" . _formulize_DE_GROUP . "</option>\n";
		$vcounter++;
	} elseif($view_groupscope) {
		$vcounter++;
		$options .= "<option value=group>&nbsp;&nbsp;" . _formulize_DE_GROUP . "</option>\n";
	} 

	if($currentView == "all" AND $view_globalscope) {
		$options .= "<option value=all selected>&nbsp;&nbsp;" . _formulize_DE_ALL . "</option>\n";
		$vcounter++;
	} elseif($view_globalscope) {
		$vcounter++;
		$options .= "<option value=all>&nbsp;&nbsp;" . _formulize_DE_ALL . "</option>\n";
	} 

	// check for pressence of advanced scope
	if(strstr($currentView, ",") AND !$loadedView) {
		$vcounter++;
		$groupNames = groupNameList(trim($currentView, ","));
		$options .= "<option value=$currentView selected>&nbsp;&nbsp;" . _formulize_DE_AS_ENTRIESBY . printSmart($groupNames) . "</option>\n";
	} elseif($view_globalscope OR $view_groupscope) {
		$vcounter++;	
		$pickgroups = $vcounter;
		$options .= "<option value=\"\">&nbsp;&nbsp;" . _formulize_DE_AS_PICKGROUPS . "</option>\n";
	}


	// check for available reports/views
	list($s_reports, $p_reports, $ns_reports, $np_reports) = availReports($uid, $groups, $fid, $frid);
	$lastStandardView = $vcounter;

	if(count($s_reports)>0 OR count($ns_reports)>0) { // we have saved reports...
		$options .= "<option value=\"\">" . _formulize_DE_SAVED_VIEWS . "</option>\n";
		$vcounter++;
	}
	for($i=0;$i<count($s_reports);$i++) {
		if($loadedView == "sold_" . $s_reports[$i]['report_id'] OR $prevview == "sold_" . $s_reports[$i]['report_id']) {
			$vcounter++;
			$options .= "<option value=$currentView selected>&nbsp;&nbsp;" . $s_reports[$i]['report_name'] . "</option>\n"; // " (id: " . $s_reports[$i]['report_id'] . ")</option>\n";
			$loadviewname = $s_reports[$i]['report_name'];
			$curviewid = "sold_" . $s_reports[$i]['report_id'];
		} else {
			$vcounter++;
			$options .= "<option value=sold_" . $s_reports[$i]['report_id'] . ">&nbsp;&nbsp;" . $s_reports[$i]['report_name'] . "</option>\n"; // " (id: " . $s_reports[$i]['report_id'] . ")</option>\n";
		}
	}
	for($i=0;$i<count($ns_reports);$i++) {
		if($loadedView == "s" . $ns_reports[$i]['sv_id'] OR $prevview == "s" . $ns_reports[$i]['sv_id']) {
			$vcounter++;
			$options .= "<option value=$currentView selected>&nbsp;&nbsp;" . $ns_reports[$i]['sv_name'] . "</option>\n"; // " (id: " . $ns_reports[$i]['sv_id'] . ")</option>\n";
			$loadviewname = $ns_reports[$i]['sv_name'];
			$curviewid = "s" . $ns_reports[$i]['sv_id'];
		} else {
			$vcounter++;
			$options .= "<option value=s" . $ns_reports[$i]['sv_id'] . ">&nbsp;&nbsp;" . $ns_reports[$i]['sv_name'] . "</option>\n"; // " (id: " . $ns_reports[$i]['sv_id'] . ")</option>\n";
		}
	}
	

	if(count($p_reports)>0 OR count($np_reports)>0) { // we have saved reports...
		$options .= "<option value=\"\">" . _formulize_DE_PUB_VIEWS . "</option>\n";
		$vcounter++;
	}
	$firstPublishedView = $vcounter + 1;
	for($i=0;$i<count($p_reports);$i++) {
		if($loadedView == "pold_" . $p_reports[$i]['report_id'] OR $prevview == "pold_" . $p_reports[$i]['report_id']) {
			$vcounter++;
			$options .= "<option value=$currentView selected>&nbsp;&nbsp;" . $p_reports[$i]['report_name'] . "</option>\n"; // " (id: " . $p_reports[$i]['report_id'] . ")</option>\n";
			$loadviewname = $p_reports[$i]['report_name'];
			$curviewid = "pold_" . $p_reports[$i]['report_id'];
		} else {
			$vcounter++;
			$options .= "<option value=pold_" . $p_reports[$i]['report_id'] . ">&nbsp;&nbsp;" . $p_reports[$i]['report_name'] . "</option>\n"; // " (id: " . $p_reports[$i]['report_id'] . ")</option>\n";
		}
	}
	for($i=0;$i<count($np_reports);$i++) {
		if($loadedView == "p" . $np_reports[$i]['sv_id'] OR $prevview == "p" . $np_reports[$i]['sv_id']) {
			$vcounter++;
			$options .= "<option value=$currentView selected>&nbsp;&nbsp;" . $np_reports[$i]['sv_name'] . "</option>\n"; // " (id: " . $np_reports[$i]['sv_id'] . ")</option>\n";
			$loadviewname = $np_reports[$i]['sv_name'];
			$curviewid = "p" . $np_reports[$i]['sv_id'];
		} else {
			$vcounter++;
			$options .= "<option value=p" . $np_reports[$i]['sv_id'] . ">&nbsp;&nbsp;" . $np_reports[$i]['sv_name'] . "</option>\n"; // " (id: " . $np_reports[$i]['sv_id'] . ")</option>\n";
		}
	}
	$to_return[0] = $options;
	$to_return[1] = $firstPublishedView;
	$to_return[2] = $lastStandardView;
	$to_return[3] = $pickgroups;
	$to_return[4] = $loadviewname;
	$to_return[5] = $curviewid;
	return $to_return;

}

// this function draws in the interface parts of a display entries widget
function drawInterface($settings, $fid, $frid, $groups, $mid, $gperm_handler, $loadview="") {

	global $xoopsDB;
	// unpack the $settings
	foreach($settings as $k=>$v) {
		${$k} = $v;
	}

	// get single/multi entry status of this form...
	$singleMulti = q("SELECT singleentry FROM " . $xoopsDB->prefix("form_id") . " WHERE id_form = $fid");
	
	// flatten columns array and convert handles to ids so that we can send them to the change columns popup
	if($frid) {
		$ids = convertHandles($columns, $frid);
	} else {
		$ids = $columns;
	}
	$colids = implode(",", $ids);
	$flatcols = implode(",", $columns);

	print "<form name=resetviewform id=resetviewform action=$currentURL method=post>\n";
	print "<input type=hidden name=currentview value='$currentview'>";
	print "</form>";


	print "<form name=controls id=controls action=$currentURL method=post>\n";

	print "<table cellpadding=10><tr><td style=\"vertical-align: top;\">";
	print "<h1>$title</h1>";

	if(strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
		$submitButton = "<input type=submit name=submitx style=\"width:0px; height:0px;\" value='' ></input>\n";
	} else {
		$submitButton =  "<input type=submit name=submitx style=\"visibility: hidden;\" value='' ></input>\n";
	}

	if($loadview) {
		print "<h3>" . $loadviewname . "</h3></td><td>";
		print "<input type=hidden name=currentview id=currentview value=\"$currentview\">\n<input type=hidden name=loadviewname id=loadviewname value=\"$loadviewname\">$submitButton";
	} else {
		print "</td>";
		print "<td rowspan=2 style=\"vertical-align: bottom;\">";	      
      	if(!$settings['lockcontrols']) {
      		print "<table><tr><td style=\"vertical-align: bottom;\">";

            	print "<center><p>$submitButton";

            	$add_own_entry = $gperm_handler->checkRight("add_own_entry", $fid, $groups, $mid);
            	if($add_own_entry AND $singleMulti[0]['singleentry'] == "") {
            		print "<br><input type=button style=\"width: 140px;\" name=addentry value='" . _formulize_DE_ADDENTRY . "' onclick=\"javascript:addNew();\"></input>";
            	} elseif($add_own_entry AND $proxy = $gperm_handler->checkRight("add_proxy_entries", $fid, $groups, $mid)) { // this is a single entry form, so add in the update and proxy buttons if they have proxy, otherwise, just add in update button
            		print "<br><input type=button style=\"width: 140px;\" name=addentry value='" . _formulize_DE_UPDATEENTRY . "' onclick=\"javascript:addNew();\"></input>";
            		print "<br><input type=button style=\"width: 140px;\" name=addentry value='" . _formulize_DE_PROXYENTRY . "' onclick=\"javascript:addNew('proxy');\"></input>";
            	} elseif($add_own_entry) {
            		print "<br><input type=button style=\"width: 140px;\" name=addentry value='" . _formulize_DE_UPDATEENTRY . "' onclick=\"javascript:addNew();\"></input>";
            	}
      		print "<br><input type=button style=\"width: 140px;\" name=changecols value='" . _formulize_DE_CHANGECOLS . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/changecols.php?fid=$fid&frid=$frid&cols=$colids');\"></input>";
      		print "<br><input type=button style=\"width: 140px;\" name=calculations value='" . _formulize_DE_CALCS . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/pickcalcs.php?fid=$fid&frid=$frid&calc_cols=$calc_cols&calc_calcs=$calc_calcs&calc_blanks=$calc_blanks&calc_grouping=$calc_grouping');\"></input>";
      		print "<br><input type=button style=\"width: 140px;\" name=advsearch value='" . _formulize_DE_ADVSEARCH . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/advsearch.php?fid=$fid&frid=$frid";
      		foreach($settings as $k=>$v) {
      			if(substr($k, 0, 3) == "as_") {
      				$v = str_replace("'", "&#39;", $v);
      				$v = stripslashes($v);
      				print "&$k=" . urlencode($v);
      			}
      		}
      		print "');\"></input>";
      		print "<br><input type=button style=\"width: 140px;\" name=export value='" . _formulize_DE_EXPORT . "' onclick=\"javascript:runExport();\"></input>";
            	print "</p></center></td><td style=\"vertical-align: bottom;\"><center><p>";

            	if(($del_own = $gperm_handler->checkRight("delete_own_entry", $fid, $groups, $mid) OR $del_others = $gperm_handler->checkRight("delete_other_entries", $fid, $groups, $mid)) AND !$settings['lockcontrols']) {
            		print "<input type=button style=\"width: 140px;\" name=deletesel value='" . _formulize_DE_DELETESEL . "' onclick=\"javascript:confirmDel();\"></input>";
            		print "<br><input type=button style=\"width: 110px;\" name=sellall value='" . _formulize_DE_SELALL . "' onclick=\"javascript:selectAll(this.form);\"></input>";
            		print "<br><input type=button style=\"width: 110px;\" name=clearall value='" . _formulize_DE_CLEARALL . "' onclick=\"javascript:clearAll(this.form);\"></input><br>";
            	}
            	print "<input type=button style=\"width: 140px;\" name=resetviewbutton value='" . _formulize_DE_RESETVIEW . "' onclick=\"javascript:window.document.resetviewform.submit();\"></input>";

            	// there is a create reports permission, but we are currently allowing everyone to save their own views regardless of that permission.  The publishing permissions do kick in on the save popup.
            	print "<br><input type=button style=\"width: 140px;\" name=save value='" . _formulize_DE_SAVE . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/save.php?fid=$fid&frid=$frid&lastloaded=$lastloaded&cols=$flatcols&currentview=$currentview');\"></input>";

      		// you can always create and delete your own reports right now (delete_own_reports perm has no effect).  If can delete other reports, then set $pubstart to 10000 (ie: can delete published as well as your own, because the javascript will consider everything beyond the start of 'your saved views' to be saved instead of published (published be thought to never begin))
      		if($delete_other_reports = $gperm_handler->checkRight("delete_other_reports", $fid, $groups, $mid)) { $pubstart = 10000; }
            	print "<br><input type=button style=\"width: 140px;\" name=delete value='" . _formulize_DE_DELETE . "' onclick=\"javascript:delete_view(this.form, '$pubstart', '$endstandard');\"></input>";
            	print "</p></center>";

      	} else { // if lockcontrols set, then write in explanation...
      		print "<table><tr><td style=\"vertical-align: bottom; width: 290px;\">";
			print "<input type=hidden name=curviewid id=curviewid value=$curviewid>";
			print "<p>$submitButton<br>" . _formulize_DE_WARNLOCK . "</p>";;
      	} // end of if controls are locked

      	print "</td></tr></table></center></td></tr><tr><td style=\"vertical-align: bottom;\">";

      	print "<p><b>" . _formulize_DE_CURRENT_VIEW . "</b>&nbsp;&nbsp;<SELECT name=currentview id=currentview size=1 onchange=\"javascript:change_view(this.form, '$pickgroups', '$endstandard');\">\n";
      	print $viewoptions;
      	print "</SELECT>";

		if(!$loadviewname AND strstr($currentview, ",")) { // if we're on a genuine pick-groups view (not a loaded view)...
			print "&nbsp&nbsp;<input type=button style=\"width: 140px;\" name=pickdiffgroup value='" . _formulize_DE_PICKDIFFGROUP . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/changescope.php?fid=$fid&frid=$frid&scope=$currentview');\"></input>";		
		}
		print "</p>";

	} // end of if there's a loadview or not
     	print "</td></tr></table>";

	print "<input type=hidden name=newcols id=newcols value=\"\">\n";
	print "<input type=hidden name=oldcols id=oldcols value='$flatcols'>\n";
	print "<input type=hidden name=ventry id=ventry value=\"\">\n";
	print "<input type=hidden name=delconfirmed id=delconfirmed value=\"\">\n";
	print "<input type=hidden name=xport id=xport value=\"\">\n";
	print "<input type=hidden name=xport_cust id=xport_cust value=\"\">\n";
	print "<input type=hidden name=loadreport id=loadreport value=\"\">\n";
	print "<input type=hidden name=lastloaded id=lastloaded value=\"$lastloaded\">\n";
	print "<input type=hidden name=saveviewname id=saveviewname value=\"\">\n";
	print "<input type=hidden name=saveviewoptions id=saveviewoptions value=\"\">\n";


	// hidden fields used by UI in the Entries section
	print "<input type=hidden name=sort id=sort value=\"$sort\">\n";
	print "<input type=hidden name=order id=order value=\"$order\">\n";

	print "<input type=hidden name=hlist id=hlist value=\"$hlist\">\n";
	print "<input type=hidden name=hcalc id=hcalc value=\"$hcalc\">\n";
	print "<input type=hidden name=lockcontrols id=lockcontrols value=\"$lockcontrols\">\n";
	print "<input type=hidden name=resetview id=resetview value=\"\">\n";

	// hidden fields used by calculations
	print "<input type=hidden name=calc_cols id=calc_cols value=\"$calc_cols\">\n";
	print "<input type=hidden name=calc_calcs id=calc_calcs value=\"$calc_calcs\">\n";
	print "<input type=hidden name=calc_blanks id=calc_blanks value=\"$calc_blanks\">\n";
	print "<input type=hidden name=calc_grouping id=calc_grouping value=\"$calc_grouping\">\n";

	// advanced search
	$asearch = str_replace("\"", "&quot;", $asearch);
	print "<input type=hidden name=asearch id=asearch value=\"" . stripslashes($asearch) . "\">\n";

	// advanced scope
	print "<input type=hidden name=advscope id=advscope value=\"\">\n";

	// delete view
	print "<input type=hidden name=delview id=delview value=\"\">\n";
	print "<input type=hidden name=delviewid id=delviewid value=\"$loadedview\">\n";

	// related to saving a new view
	print "<input type=hidden name=saveid id=saveid value=\"\">\n";
	print "<input type=hidden name=savename id=savename value=\"\">\n";
	print "<input type=hidden name=savegroups id=savegroups value=\"\">\n";
	print "<input type=hidden name=savelock id=savelock value=\"\">\n";
	print "<input type=hidden name=savescope id=savescope value=\"\">\n";


//	print "</form>"; // form used to end here with the idea that drawEntries would be able to stand alone

	interfaceJavascript($fid, $frid, $currentview); // must be called after form is drawn, so that the javascript which clears ventry can operate correctly (clearing is necessary to avoid displaying the form after clicking the Back button on the form and then clicking a button or doing an operation that causes a posting of the controls form).

}

// THIS FUNCTION DRAWS IN THE RESULTS OF THE QUERY
function drawEntries($fid, $cols, $sort="", $order="", $searches="", $frid="", $scope, $standalone="", $currentURL, $gperm_handler, $uid, $mid, $groups, $settings, $member_handler, $loadview="") {

	global $xoopsDB;
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";

	if($standalone) { // write in the top of the form if we're doing this on our own without the interface
	// [code goes here]
	// this feature not enabled yet
	// the search and sort features in this part of the page are dependant on the logic above
	// seems unlikely this block could realistically be used as a standalone block
	// but a simpler function could be created that would just draw a scrollbox with results in it
	}


	// build the filter out of the searches array
	$start = 1;
	$filter = "";
	foreach($searches as $key=>$one_search) {
		// if frid, searches contains handles, so use them.  if no frid then get ff captions with ids
		if(!$start) { $filter .= "]["; }
		if($frid) {	
			$filter .= $key . "/**/" . mysql_real_escape_string($one_search);
		} else {
			$caption = go("SELECT ele_caption FROM " . $xoopsDB->prefix("form") . " WHERE ele_id = '$key'"); 
			$ffcaption = eregi_replace ("&#039;", "`", $caption[0]['ele_caption']);
			$ffcaption = eregi_replace ("&quot;", "`", $ffcaption);
			$ffcaption = str_replace ("'", "`", $ffcaption);
			$filter .= $ffcaption . "/**/" . mysql_real_escape_string($one_search);
		}
		$start = 0;
	}
	
	// extraction could be optimized by passing the current columns and limiting returned values to those columns only
	$data = getData($frid, $fid, $filter, "AND", $scope); // 
	if($sort AND $order) {
		$data = resultSort($data, $sort, $order); // sort is ele_id for form, handle for framework
	} 


	// perform the requested advanced search options
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



	if($settings['as_0']) {
		// build the query string
		// string looks like this:
		//if([query here]) {
		//	$query_result = 1;
		//}
		
		$query_string = "if(";

		for($i=0;$settings['as_' . $i];$i++) {
			// save query for writing later
			$wq['as_' . $i] = $settings['as_' . $i];
			if(substr($settings['as_' . $i], 0, 7) == "[field]" AND substr($settings['as_' . $i], -8) == "[/field]") { // a field has been found, next two should be part of the query
				$fieldLen = strlen($settings['as_' . $i]);
				$field = substr($settings['as_' . $i], 7, $fieldLen-15); // 15 is the length of [field][/field]
				$field = calcHandle($field, $frid);
				$query_string .= "evalAdvSearch(\$entry, \"$field\", \"";
				$i++;
				$wq['as_' . $i] = $settings['as_' . $i];
				$query_string .= $settings['as_' . $i] . "\", \"";
				$i++;
				$wq['as_' . $i] = $settings['as_' . $i];
				$query_string .= $settings['as_' . $i] . "\")";
			} else {
				$query_string .= " " . $settings['as_' . $i] . " ";
			}
		}

		$query_string .= ") { \$query_result=1; }";

		$indexer = 0;
		foreach($data as $entry) {
			$query_result = 0;
			eval($query_string); // a constructed query based on the user's input.  $query_result = 1 if it succeeds and 0 if it fails.
			if($query_result) {
				$found_data[] = $entry;
			}
			unset($data[$indexer]);
			$indexer++;
		}
		unset($data);
		if(count($found_data)>0) { $data = $found_data; }
	} 
	

	// get the headers

	foreach($cols as $col) {
       	if($frid) {
       		$headers[] = getCaption($frid, $col);
       	} else {
       		$temp_cap = go("SELECT ele_caption FROM " . DBPRE . "form WHERE ele_id = '$col'"); 
       		$headers[] = $temp_cap[0]['ele_caption'];
       	}
	}
	
	print "<style>\n";

	print ".scrollbox {\n";
	print "	height: 550px;\n";
	print "	width: 820px;\n";
	print "	overflow: scroll;\n";
	print "}\n";

	print ".entrymeta {\n";
	print "	font-size: 8pt;\n";
	print "}\n";

	print "</style>\n";

	if($settings['xport']) {
		$filename = prepExport($headers, $cols, $data, $settings['xport'], $settings['xport_cust'], $settings['title']);
		print "<center><p><a href='$filename' target=\"_blank\">" . _formulize_DE_CLICKSAVE . "</a></p></center>";
		print "<br>";
	}


	print "<div class=scrollbox id=resbox>\n";


	// perform calculations...
	// calc_cols is the columns requested (separated by / -- ele_id for each, so needs conversion to handle if framework in effect, also metadata is indicated with uid, proxyid, creation_date, mod_date)
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
       	$cresults = performCalcs($ccols, $ccalcs, $cblanks, $cgrouping, $data, $frid);
//		print "<p><input type=button style=\"width: 140px;\" name=cancelcalcs1 value='" . _formulize_DE_CANCELCALCS . "' onclick=\"javascript:cancelCalcs();\"></input></p>\n";
//		print "<div";
//		if($totalcalcs>4) { print " class=scrollbox"; }
//		print " id=calculations>
		$calc_cols = $settings['calc_cols'];
		$calc_calcs = $settings['calc_calcs'];
		$calc_blanks = $settings['calc_blanks'];
		$calc_grouping = $settings['calc_grouping'];

		print "<table class=outer><tr><th colspan=2>" . _formulize_DE_CALCHEAD . "</th></tr>\n";
		if(!$settings['lockcontrols'] AND !$loadview) {
			print "<tr><td class=head colspan=2><input type=button style=\"width: 140px;\" name=mod_calculations value='" . _formulize_DE_MODCALCS . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/pickcalcs.php?fid=$fid&frid=$frid&calc_cols=$calc_cols&calc_calcs=$calc_calcs&calc_blanks=$calc_blanks&calc_grouping=$calc_grouping');\"></input>&nbsp;&nbsp;<input type=button style=\"width: 140px;\" name=cancelcalcs value='" . _formulize_DE_CANCELCALCS . "' onclick=\"javascript:cancelCalcs();\"></input>&nbsp;&nbsp<input type=button style=\"width: 140px;\" name=showlist value='" . _formulize_DE_SHOWLIST . "' onclick=\"javascript:showList();\"></input></td></tr>";
		}
		printResults($cresults[0], $cresults[1], $cresults[2], $frid); // 0 is the masterresults, 1 is the blanksettings, 2 is grouping settings
		print "</table>\n";

//		print "</div>\n";
		// put in hide list/cancel calcs buttons here
//		print "<p><input type=button style=\"width: 140px;\" name=cancelcalcs value='" . _formulize_DE_CANCELCALCS . "' onclick=\"javascript:cancelCalcs();\"></input>&nbsp;&nbsp;";
//		if($settings['hlist']) {
//			print "<input type=button style=\"width: 140px;\" name=showlist value='" . _formulize_DE_SHOWLIST . "' onclick=\"javascript:showList();\"></input></p>";
//		} else {
//			print "<input type=button style=\"width: 140px;\" name=hidelist value='" . _formulize_DE_HIDELIST . "' onclick=\"javascript:hideList();\"></input></p>";
//		}

	}

	// MASTER HIDELIST CONDITIONAL...
	if(!$settings['hlist']) {

	print "<table class=outer>";

	$count_colspan = count($cols)+1;
	print "<tr><th colspan=$count_colspan>" . _formulize_DE_DATAHEADING . "</th></tr>\n";

	if($settings['calc_cols'] AND !$settings['lockcontrols'] AND !$loadview) {
		$calc_cols = $settings['calc_cols'];
		$calc_calcs = $settings['calc_calcs'];
		$calc_blanks = $settings['calc_blanks'];
		$calc_grouping = $settings['calc_grouping'];
		print "<tr><td class=head colspan=$count_colspan><input type=button style=\"width: 140px;\" name=mod_calculations value='" . _formulize_DE_MODCALCS . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/pickcalcs.php?fid=$fid&frid=$frid&calc_cols=$calc_cols&calc_calcs=$calc_calcs&calc_blanks=$calc_blanks&calc_grouping=$calc_grouping');\"></input>&nbsp;&nbsp;<input type=button style=\"width: 140px;\" name=cancelcalcs value='" . _formulize_DE_CANCELCALCS . "' onclick=\"javascript:cancelCalcs();\"></input>&nbsp;&nbsp;<input type=button style=\"width: 140px;\" name=hidelist value='" . _formulize_DE_HIDELIST . "' onclick=\"javascript:hideList();\"></input></td></tr>";
	}

	// draw advanced search notification
	if($query_string) {
		$writable_q = writableQuery($wq);
		$minus1colspan = $count_colspan-1;
		print "<tr><td class=head></td><td colspan=$minus1colspan class=head>" . _formulize_DE_ADVSEARCH . ": $writable_q";
		if(!$settings['lockcontrols'] AND !$loadview) {
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
	drawHeaders($headers, $cols, $sort, $order, $settings['lockcontrols']);
	drawSearches($searches, $cols);

	// get form handles in use
	$mainFormHandle = key($data[0]);

	if(count($data) == 0) { // kill an empty dataset so there's no rows drawn
		unset($data);
	}
	$headcounter = 0;
	foreach($data as $id=>$entry) {

		if($entry != "") { // check to make sure this isn't an unset entry (ie: one that was blanked by the extraction layer just prior to sending back results

		if($headcounter == 5) { 
			drawHeaders($headers, $cols, $sort, $order, $settings['lockcontrols']); 
			$headcounter = 0;
		}
		$headcounter++;		

		print "<tr>\n";
		if($class=="even") {
			$class="odd";
		} else {
			$class="even";
		}

		$linkids = internalRecordIds($entry, $mainFormHandle);
		// commented below is an attempt to make metadata appear in tooltip boxes, but formatting is not available and the box is of a fixed width and "dotdotdots" itself -- member_handler not currently used by drawEntries so long as this is commented (and it is not added elsewhere)
		//$metaData = getMetaData($linkids[0], $member_handler);
		//$metaToPrint = "<br>" . _formulize_FD_CREATED . $metaData['created_by'] . " " . _formulize_TEMP_ON . " " . $metaData['created'] . "<br>" . _formulize_FD_MODIFIED . $metaData['last_update_by'] . " " . _formulize_TEMP_ON . " " . $metaData['last_update'];

		// draw in the margin column where the links and metadata goes
		print "<td class=head>\n";

		if(!$settings['lockcontrols'] AND !$loadview) {
      		print "<p><center><a href='" . $currentURL;
      		if(strstr($currentURL, "?")) { // if params are already part of the URL...
      			print "&";
      		} else {
      			print "?";
      		}
      		print "ve=" . $linkids[0] . "' onclick=\"javascript:goDetails('" . $linkids[0] . "');return false;\"><img src='" . XOOPS_URL . "/modules/formulize/images/detail.gif' border=0 alt=\"" . _formulize_DE_VIEWDETAILS . "$metaToPrint\" title=\"" . _formulize_DE_VIEWDETAILS . "$metaToPrint\"></a>";

      		// metadata not drawn in currently.  Takes up too much space.  Available on form page.
      		/*<br>\n";
      		$c_uid = display($entry, 'uid');
      		$c_name_q = q("SELECT name, uname FROM " . $xoopsDB->prefix("users") . " WHERE uid='$c_uid'");
      		$c_name = $c_name_q[0]['name'];
      		if(!$c_name) { $c_name = $c_name_q[0]['uname']; }
      		$c_date = display($entry, 'creation_date');
      		$m_uid = display($entry, 'proxyid');
      		if($m_uid) {
      			$m_name_q = q("SELECT name, uname FROM " . $xoopsDB->prefix("users") . " WHERE uid='$m_uid'");
      			$m_name = $m_name_q[0]['name'];
      			if(!$m_name) { $m_name = $m_name_q[0]['uname']; }
      		} else {
      			$m_name = $c_name;
      		}
      		$m_date = display($entry, 'mod_date');
      		print "<p class=entrymeta>" . _formulize_DE_LASTMOD . " " . $m_name . " " . _formulize_DE_ON . " " . $m_date . "<br>\n";
      		print _formulize_DE_CREATED . " " . $c_name . " " . _formulize_DE_ON . " " . $c_date; */

      		// put in the delete checkboxes -- check for perms delete_own_entry, delete_other_entries
      		$owner = getEntryOwner($linkids[0]);
      		// check to see if we should draw in the delete checkbox or not
      		if(($owner == $uid AND $gperm_handler->checkRight("delete_own_entry", $fid, $groups, $mid)) OR ($owner != $uid AND $gperm_handler->checkRight("delete_other_entries", $fid, $groups, $mid))) {		
      			print "<br><input type=checkbox title='" . _formulize_DE_DELBOXDESC . "' name='delete_" . $linkids[0] . "' id='delete_" . $linkids[0] . "' value='delete_" . $linkids[0] . "'>";
      		}

      		print "</center></p>\n";	
		} // end of IF NO LOCKCONTROLS
		print "</td>\n";

		foreach($cols as $col) {
			print "<td class=$class>\n";
			$value = display($entry, $col);
			if(is_array($value)) {
				$start = 1;
				foreach($value as $v) {
					if($start) {
						print printSmart($v);
						$start = 0;
					} else {
						print ",<br>\n";
						print printSmart($v);
					}
				}
			} else {
				print printSmart($value);
			}
			print "</td>\n";
		}
		print "</tr>\n";
		
		} // end of not "" check
	
	}

	if(count($data)>20) { drawHeaders($headers, $cols, $sort, $order, $settings['lockcontrols']); }

	print "</table>";

	print "</div>";

	}// END OF MASTER HIDELIST CONDITIONAL
	
	if($settings['calc_cols'] AND !$settings['hcalc']) { // if calculations are going on above, then draw in the search boxes, but hidden, just to preserve the values of the quicksearches.
		print "<div style=\"visibility: hidden;\">";
		drawSearches($searches, $cols);
		print "</div>";
	}
	print "</form>\n"; 

}



// this function draws in the search box row
function drawSearches($searches, $cols) {
	print "<tr><td class=head>&nbsp;</td>\n";
	for($i=0;$i<count($cols);$i++) {
		print "<td class=head>\n";
		$search_text = str_replace("\"", "&quot;", $searches[$cols[$i]]);
		print "<input type=text name='search_" . $cols[$i] . "' value=\"" . stripslashes($search_text) . "\"></input>\n";
		print "</td>\n";
	}
	print "</tr>\n";

}

// this function writes in the headers for the columns in the results box
function drawHeaders($headers, $cols, $sort, $order) { //, $lockcontrols) {

	print "<tr><td class=head>&nbsp;</td>\n";
	for($i=0;$i<count($headers);$i++) {
       	print "<td class=head>\n";
		if($cols[$i] == $sort) {
			if($order == "SORT_DESC") {
				$imagename = "desc.gif";
			} else {
				$imagename = "asc.gif";
			}
			print "<img src='" . XOOPS_URL . "/modules/formulize/images/$imagename' align=left>";
		}
//		if(!$lockcontrols) {
			print "<a href=\"\" alt=\"" . _formulize_DE_SORTTHISCOL . "\" title=\"" . _formulize_DE_SORTTHISCOL . "\" onclick=\"javascript:sort_data('" . $cols[$i] . "');return false;\">";
//		}
       	print printSmart($headers[$i]);
//		if(!$lockcontrols) {
			print "</a>\n";
//		}
	     	print "</td>\n";
	}
	print "</tr>\n";
}


// this function takes handles and returns the ids formatted for sending to change columns
// assume handles are unique within a framework (which they are supposed to be!)
function convertHandles($handles, $frid) {
	global $xoopsDB;
	foreach($handles as $handle) {
		$id = q("SELECT fe_element_id FROM " . $xoopsDB->prefix("formulize_framework_elements") . " WHERE fe_frame_id='$frid' AND fe_handle='$handle'");
		$ids[] = $id[0]['fe_element_id'];
	}
	return $ids;
}

// this function takes ids and converts them to handles
function convertIds($ids, $frid) {
	global $xoopsDB;
	if(!is_array($ids)) { 
		$temp = $ids;
		unset($ids);
		$ids[0] = $temp;
	}
	foreach($ids as $id) {
		$handle = q("SELECT fe_handle FROM " . $xoopsDB->prefix("formulize_framework_elements") . " WHERE fe_frame_id='$frid' AND fe_element_id='$id'");
		$handles[] = $handle[0]['fe_handle'];
	}
	return $handles;
}
 


// this function returns the ele_ids of form elements to show, or the handles of the form elements to show for a framework
function getDefaultCols($fid, $frid="") {
	global $xoopsDB;

	if($frid) { // expand the headerlist to include the other forms
		$fids[0] = $fid;
		$check_results = checkForLinks($frid, $fids, $fid, "", "", "", "", "", "", "0");
		$fids = $check_results['fids'];
		$sub_fids = $check_results['sub_fids'];
		foreach($fids as $this_fid) {
			$headers = getHeaderList($this_fid);
			$ele_ids[$this_fid] = convertHeadersToIds($headers, $this_fid);
		}
		foreach($sub_fids as $this_fid) {
			$headers = getHeaderList($this_fid);
			$ele_ids[$this_fid] = convertHeadersToIds($headers, $this_fid);
		}

		array_unique($ele_ids);
		if($frid) { // get the handles
	  		include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
			foreach($ele_ids as $this_fid=>$ids) {
				foreach($ids as $id) {
					$handles[] = handleFromId($id, $this_fid, $frid);
				}
			}
			return $handles;
		}
	} else {
		$headers = getHeaderList($fid);
		$ele_ids = convertHeadersToIds($headers, $fid);
		return $ele_ids;
	}



} 

// gets the ele_ids of the headerlist for a form
function convertHeadersToIds($headers, $fid) {
	global $xoopsDB;
	foreach($headers as $cap) {
		$cap = addslashes($cap);
		$ele_id = q("SELECT ele_id FROM " . $xoopsDB->prefix("form") . " WHERE id_form='$fid' AND ele_caption='$cap'");
		$ele_ids[] = $ele_id[0]['ele_id'];
	}
	return $ele_ids;
}


//THIS FUNCTION PERFORMS THE REQUESTED CALCULATIONS, AND RETURNS AN html FORMATTED CHUNK FOR DISPLAY ON THE SCREEN
function performCalcs($cols, $calcs, $blanks, $grouping, $data, $frid)  {
	
	// figure out all the handles that we need to grab for calculating
	// plus the calcs requested for each, plus the blank options plus the grouping handle
	for($i=0;$i<count($cols);$i++) {
		$handles[$i] = calcHandle($cols[$i], $frid);
		unset($ex_calcs);
		unset($ex_blanks);
		unset($ex_grouping);
		if(strstr($calcs[$i], ",")) {
			$ex_calcs = explode(",", $calcs[$i]);
		} else {
			$ex_calcs[0] = $calcs[$i];
		}
		if(strstr($blanks[$i], ",")) {
			$ex_blanks = explode(",", $blanks[$i]);
		} else {
			$ex_blanks[0] = $blanks[$i];
		}
		if(strstr($grouping[$i], ",")) {
			$ex_grouping = explode(",", $grouping[$i]);
		} else {
			$ex_grouping[0] = $grouping[$i];
		}
		for($z=0;$z<count($ex_calcs);$z++) {
			$c[$i][$z] = $ex_calcs[$z];
			$b[$i][$z] = $ex_blanks[$z];
			$g[$i][$z] = calcHandle($ex_grouping[$z], $frid);
		}
	}

/*	print_r($handles);
	print "<br>";
	print_r($c);
	print "<br>";
	print_r($b);
	print "<br>";
	print_r($g);
*/
	// loop through all the data.  For each entry, store it as necessary for every calculation that needs to happen.

	foreach($data as $entry) {
		for($i=0;$i<count($handles);$i++)  {
			$tempvalue = display($entry, $handles[$i]);
			$thisvalue = convertUids($tempvalue, $handles[$i]); // also converts blanks to [blank]
			for($z=0;$z<count($c[$i]);$z++) {
				$blankSettings[$handles[$i]][$c[$i][$z]] = $b[$i][$z];				
				$groupingSettings[$handles[$i]][$c[$i][$z]] = $g[$i][$z];
				if(($b[$i][$z] == "onlyblanks" AND $tempvalue == "") OR ($b[$i][$z] == "noblanks" AND $tempvalue != "") OR $b[$i][$z] == "all") {
					if($g[$i][$z] == "none" OR $g[$i][$z] == "") { 
						if(is_array($thisvalue)) {
							foreach($thisvalue as $onevalue) {

								$masterCalcs[$handles[$i]][$c[$i][$z]][0][] = $onevalue;
							}
						} else {
							$masterCalcs[$handles[$i]][$c[$i][$z]][0][] = $thisvalue;
						}
					} else {
						$thisgroup = display($entry, $g[$i][$z]);
						$thisgroup = convertUids($thisgroup, $g[$i][$z]);
						if(is_array($thisgroup)) {
							foreach($thisgroup as $onegroup) {
								if(is_array($thisvalue)) {
									foreach($thisvalue as $onevalue) {
										$masterCalcs[$handles[$i]][$c[$i][$z]][$onegroup][] = $onevalue;
									}
								} else {
									$masterCalcs[$handles[$i]][$c[$i][$z]][$onegroup][] = $thisvalue;
								}
							}	
						} else {
							if(is_array($thisvalue)) {
								foreach($thisvalue as $onevalue) {
									$masterCalcs[$handles[$i]][$c[$i][$z]][$thisgroup][] = $onevalue;
								}
							} else {
								$masterCalcs[$handles[$i]][$c[$i][$z]][$thisgroup][] = $thisvalue;
							}
						}
					}
				}
			}
		}
	}

	unset($data); // clears memory?
	unset($cols);
	unset($calcs);
	unset($blanks);
	unset($grouping);
	// loop through the masterCalc array and perform each required calculation

	foreach($masterCalcs as $handle=>$thesecalcs) {
		foreach($thesecalcs as $thiscalc=>$thesegroups) {
			foreach($thesegroups as $thisgroup=>$values) {
				switch($thiscalc) {
					case "sum":
						$total = array_sum($values);
						$masterResults[$handle][$thiscalc][$thisgroup] = _formulize_DE_CALC_SUM . ": $total";
						break;
					case "avg":
						$total = array_sum($values);
						$count = count($values);
						$mean = $total/$count;
						sort($values, SORT_NUMERIC);
						if($count%2 == 0 AND $count>1) {
							$median = $values[($count/2)] . ", " . $values[($count/2)-1];						
						} elseif($count>2) {
							$median = $values[($count/2)-0.5];						
						} else {
							$median = $values[($count)-1];						
						}
						$breakdown = array_count_values($values);
						arsort($breakdown);
						$mode_keys = array_keys($breakdown);
						$mode = "" . $mode_keys[0] . "";
						$index = 0;
						foreach($breakdown as $val) {
							if(!$index) { 
								$index++;
								$prevval = $val;
							} else {
								if($prevval == $val) {
									$mode .= ", " . $mode_keys[$index];
									$index++;
									$prevval = $val;
								} else {
									break;
								}
							}
						}
						$masterResults[$handle][$thiscalc][$thisgroup] = _formulize_DE_CALC_MEAN . ": $mean<br>" . _formulize_DE_CALC_MEDIAN . ": $median<br>" . _formulize_DE_CALC_MODE . ": $mode";
						break;
					case "min":
						sort($values, SORT_NUMERIC);
						$min = $values[0];						
						$masterResults[$handle][$thiscalc][$thisgroup] = _formulize_DE_CALC_MIN . ": $min";
						break;
					case "max":
						$count = count($values);
						sort($values, SORT_NUMERIC);
						$max = $values[$count-1];						
						$masterResults[$handle][$thiscalc][$thisgroup] = _formulize_DE_CALC_MAX . ": $max";										
						break;
					case "count":
						$count = count($values);
						$breakdown = array_count_values($values);
						$count_unique = count(array_keys($breakdown));
						$masterResults[$handle][$thiscalc][$thisgroup] = _formulize_DE_CALC_NUMENTRIES . ": $count<br>" . _formulize_DE_CALC_NUMUNIQUE . ": $count_unique";
						break;
					case "per":
						$count = count($values);
						$breakdown = array_count_values($values);
						arsort($breakdown);
						$typeout = "<table cellpadding=3>\n<tr><td style=\"vertical-align: top;\"><u>" . _formulize_DE_PER_ITEM . "</u></td><td style=\"vertical-align: top;\"><u>" . _formulize_DE_PER_COUNT . "</u></td><td style=\"vertical-align: top;\"><u>" . _formulize_DE_PER_PERCENT . "</u></td></tr>\n";
						foreach($breakdown as $item=>$icount) {
							$percentage = round(($icount/$count)*100, 2);
							$typeout .= "<tr><td style=\"vertical-align: top;\">$item</td><td style=\"vertical-align: top;\">$icount</td><td style=\"vertical-align: top;\">$percentage%</td></tr>\n";		
						}
						$typeout .= "</table>";
						$masterResults[$handle][$thiscalc][$thisgroup] = $typeout;
						break;
				}
			}
		}
	}
	$to_return[0] = $masterResults;
	$to_return[1] = $blankSettings;
	$to_return[2] = $groupingSettings;
	return $to_return;
}


//THIS FUNCTION TAKES A MASTER RESULT SET AND DRAWS IT ON THE SCREEN
function printResults($masterResults, $blankSettings, $groupingSettings, $frid) {


     	foreach($masterResults as $handle=>$calcs) {
		print "<tr><td class=head colspan=2>\n";
		print printSmart(getCalcHandleText($handle, $frid));
		print "\n</td></tr>\n";
     		foreach($calcs as $calc=>$groups) {
			$countGroups = count($groups);
     			print "<tr><td class=even rowspan=$countGroups>\n";
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
			print "<p><b>$calc_name</b></p>\n";
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
			}
			print "<p>$bsetting</p>\n</td>\n";
			$start = 1;
     			foreach($groups as $group=>$result) {
				if(!$start) { print "<tr>\n"; }
				$start=0;
				print "<td class=odd>\n";
				if(count($groups)>1) {
					print "<p><b>" . printSmart(getCalcHandleText($groupingSettings[$handle][$calc], $frid)) . ": " . printSmart($group) . "</b></p>\n";
				} 
     				print "<p>$result</p>\n</td></tr>\n";
     			}
     		}
     	}			
}



// this function converts a UID to a full name, or user name, if the handle is uid or proxyid
// also converts blanks to [blank]
function convertUids($value, $handle) {
	if(!is_numeric($value) AND $value == "") { $value = "[blank]"; }
	if($handle != "uid" AND $handle != "proxyid") { return $value; }
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
function calcHandle($value, $frid) {
	if(!$frid OR ($value == "uid" OR $value == "proxyid" OR $value == "creation_date" OR $value == "mod_date")) {
		$handle = $value;
	} else {
		$thandle = convertIds($value, $frid); // convert id to handle if this is a framework (unless it's a metadata value)
		$handle = $thandle[0];	
	}
	return $handle;				
}



// this function evaluates a basic part of an advanced search.
// accounts for all the values of a multiple value field, such as a checkbox
// operators: ==, !=, >, <, <=, >=, LIKE, NOT LIKE
function evalAdvSearch($entry, $handle, $op, $term) {
	$result = 0;
	$term = str_replace("\'", "'", $term); // seems that apostrophes are the only things that arrive at this point still escaped.
	$values = display($entry, $handle);
	if($handle == "uid" OR $handle=="proxyid") {
		$values = convertUids($values, $handle);
	} 
	if ($term == "{USER}") {
		global $xoopsUser;
		$term = $xoopsUser->getVar('name');
		if(!$term) { $term = $xoopsUser->getVar('uname'); }
	}
	if ($term == "{TODAY}") {
		$term = date("Y-m-d");
	}
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
function interfaceJavascript($fid, $frid, $currentview) {
?>
<script type='text/javascript'>

window.document.controls.ventry.value = '';
window.document.controls.loadreport.value = '';

function warnLock() {
	alert('<? print _formulize_DE_WARNLOCK; ?>');
	return false;
}

function showPop(url) {

	if (window.popup == null) {
		popup = window.open(url,'popup','toolbar=no,scrollbars=yes,resizable=yes,width=800,height=450,screenX=0,screenY=0,top=0,left=0');
      } else {
		if (window.popup.closed) {
			popup = window.open(url,'popup','toolbar=no,scrollbars=yes,resizable=yes,width=800,height=450,screenX=0,screenY=0,top=0,left=0');
            } else {
			window.popup.location = url;              
		}
	}
	window.popup.focus();

}


function confirmDel() {
	var answer = confirm ('<?php print _formulize_DE_CONFIRMDEL; ?>');
	if (answer) {
		window.document.controls.delconfirmed.value = 1;
		window.document.controls.submit();
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
	window.document.controls.submit();
}


function runExport() {
	window.document.controls.xport.value = "comma";
	window.document.controls.submit();

}

/* ---------------------------------------
   The selectall and clearall functions are based on a function by
   Vincent Puglia, GrassBlade Software
   site:   http://members.aol.com/grassblad
------------------------------------------- */

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

function delete_view(formObj, pubstart, endstandard) {

	for (var i=0; i < formObj.currentview.options.length; i++) {
		if (formObj.currentview.options[i].selected) {
			if( i > endstandard && i < pubstart && formObj.currentview.options[i].value != "") {
				var answer = confirm ('<?php print _formulize_DE_CONF_DELVIEW; ?>');
				if (answer) {
					window.document.controls.delview.value = 1;
					window.document.controls.submit();
				} else {
					return false;
				}
			} else {
				if(formObj.currentview.options[i].value != "") {
					alert('<? print _formulize_DE_DELETE_ALERT; ?>');
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
				<? print "showPop('" . XOOPS_URL . "/modules/formulize/include/changescope.php?fid=$fid&frid=$frid&scope=$currentview');"; ?>				
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
					window.document.controls.submit();
				}
			}
		}
	}
}

function addNew(proxy) {
	if(proxy) {
		window.document.controls.ventry.value = 'proxy';
	} else {
		window.document.controls.ventry.value = 'addnew';
	}
	window.document.controls.submit();
}

function goDetails(viewentry) {
	window.document.controls.ventry.value = viewentry;
	window.document.controls.submit();
}

function cancelCalcs() {
	window.document.controls.calc_cols.value = '';
	window.document.controls.calc_calcs.value = '';
	window.document.controls.calc_blanks.value = '';
	window.document.controls.calc_grouping.value = '';
	window.document.controls.hlist.value = 0;
	window.document.controls.hcalc.value = 1;
	window.document.controls.submit();
}

function hideList() {
	window.document.controls.hlist.value = 1;
	window.document.controls.hcalc.value = 0;
	window.document.controls.submit();
}

function showList() {
	window.document.controls.hlist.value = 0;
	window.document.controls.hcalc.value = 1;
	window.document.controls.submit();
}

function killSearch() {
	window.document.controls.asearch.value = '';
	window.document.controls.submit();
}


</script>
<?
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
	$data = q("SELECT report_ispublished, report_scope, report_fields, report_search_typeArray, report_search_textArray, report_andorArray, report_calc_typeArray, report_sort_orderArray, report_ascdscArray, report_globalandor FROM " . $xoopsDB->prefix("form_reports") . " WHERE report_id=$id AND report_id_form=$fid");

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
	foreach($tempcols as $col) {
		$cols[] = str_replace("`", "'", $col);
	}
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
function loadReport($id) {
	global $xoopsDB;
	$thisview = q("SELECT * FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_id='$id'");
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



?>