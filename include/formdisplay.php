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

//THIS FILE HANDLES THE DISPLAY OF FORMS.  FUNCTIONS CAN BE CALLED FROM ANYWHERE (INTENDED FOR PAGEWORKS MODULE)

global $xoopsConfig;
// load the formulize language constants if they haven't been loaded already
	if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
	} else {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
	}


// this function gets the element that is linked from a form to its parent form
// returns the ele_ids from form table
// note: no enforcement of only one link to a parent form.  You can screw up your framework structure and this function will dutifully return several links to the same parent form
function getParentLinks($fid, $frid) {

	global $xoopsDB;

	$check1 = q("SELECT fl_key1, fl_key2 FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form1_id='$fid' AND fl_frame_id = '$frid' AND fl_unified_display = '1' AND fl_relationship = '3'");
	$check2 = q("SELECT fl_key1, fl_key2 FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form2_id='$fid' AND fl_frame_id = '$frid' AND fl_unified_display = '1' AND fl_relationship = '2'");
	foreach($check1 as $c) {
		$source[] = $c['fl_key2'];
		$self[] = $c['fl_key1'];
	}
	foreach($check2 as $c) {
		$source[] = $c['fl_key1'];
		$self[] = $c['fl_key2'];
	}

	$to_return['source'] = $source;
	$to_return['self'] = $self;

	return $to_return;

}


// this function returns the captions and values that are in the DB for an existing entry
function getEntryValues($entry) {

	global $xoopsDB;

	$viewquery = q("SELECT ele_caption, ele_value FROM " . $xoopsDB->prefix("form_form") . " WHERE id_req=$entry");
	foreach($viewquery as $vq) {
		$prevEntry['captions'][] = $vq["ele_caption"];
		$prevEntry['values'][] = $vq["ele_value"];
	}
	return $prevEntry;
	
}



// this function returns the entry ids of entries in one form that are linked to another
// no scope control here...need to add that in, or add it in to the extraction layer
// IMPORTANT:  assume $startEntry is valid for the user(security check has already been executed by now)
// therefore just need to know the allowable uids (scope) in the $targetForm
function findLinkedEntries($startForm, $targetForm, $startEntry, $gperm_handler, $owner_groups, $mid, $member_handler, $owner) {

	// set scope filter -- may need to pass in some exceptions here in the case of viewing entries that are covered by reports?
	// scope based on the owner's scope within the subform, since that is the entries that the owner would see, the entries that belong to this entry, within the subform
	if($global_scope = $gperm_handler->checkRight("view_globalscope", $targetForm['fid'], $owner_groups, $mid)) {
		$scope_filter = "";
	} elseif($group_scope = $gperm_handler->checkRight("view_groupscope", $targetForm['fid'], $owner_groups, $mid)) {
		foreach($owner_groups as $grp) {
			if($grp != XOOPS_GROUP_USERS) { // exclude registered users group since that's everyone!
				$users = $member_handler->getUsersByGroup($grp);
				$all_users = array_merge($users, $all_users);
				unset($users);
			}
		}
		$uq = makeUidFilter($all_users);
		$scope_filter = "AND ($uq)";
	} else {
		$scope_filter = "AND uid=$owner";
	} 



	global $xoopsDB;
	//targetForm is a special array containing the keys as specified in the framework, and the target form
	//keys:  fid, keyself, keyother
	//keyself and other are the ele_id from the form table for the elements that need to be matched.  Must get captions and convert to form_form format in order to find the matching values

	//print_r($targetForm);
	//print "<br>$startForm<br>$startEntry<br>";
	

	if($targetForm['keyself'] == 0) { // linking based on uid, in the case of one to one forms, assumption is that these forms are both single_entry forms (otherwise linking one_to_one based on uid doesn't make any sense)
		// get uid of first entry
		// look for that uid in the target form
		$uid_q = q("SELECT uid FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form = $startForm AND id_req = $startEntry GROUP BY uid");
		// Question? is the error condition below valid?  Might you not have one to one linking in a multi form, in which case multiple uids returned is okay?
		if(count($uid_q)>1) { exit("Error: more than one user id found for a single entry while trying to display a form"); }		
		$entries_q = q("SELECT id_req FROM " . $xoopsDB->prefix("form_form") . " WHERE uid = " . $uid_q[0]['uid'] . " AND id_form = " . $targetForm['fid'] . " $scope_filter GROUP BY id_req"); 
		if($entries_q[0]['id_req']) {
			foreach($entries_q as $entry) {
				$entries_to_return[] = $entry['id_req'];
			}
		} else {
			$entries_to_return = "";
		}
		return $entries_to_return;
	} else { // linking based on a shared value.  in the case of one to one forms assumption is that the shared value does not appear more than once in either form's field (otherwise this will be a defacto one to many link)
		//get value at startEntry, for the keyother caption
		//look for that value in the target form's keyself

		$caption = q("SELECT ele_caption FROM " . $xoopsDB->prefix("form") . " WHERE ele_id = '" . $targetForm['keyother']."'"); 
		$ffcaption = eregi_replace ("&#039;", "`", $caption[0]['ele_caption']);
		$ffcaption = eregi_replace ("&quot;", "`", $ffcaption);
		$ffcaption = str_replace ("'", "`", $ffcaption);

		$sourceValue = q("SELECT ele_id, ele_value FROM " . $xoopsDB->prefix("form_form") . " WHERE id_req = '$startEntry' AND ele_caption = '$ffcaption' AND id_form = '$startForm'");				
		
		$caption2 = q("SELECT ele_caption FROM " . $xoopsDB->prefix("form") . " WHERE ele_id = '" . $targetForm['keyself']."'"); 
		$ffcaption = eregi_replace ("&#039;", "`", $caption2[0]['ele_caption']);
		$ffcaption = eregi_replace ("&quot;", "`", $ffcaption);
		$ffcaption = str_replace ("'", "`", $ffcaption);

		// check to see if we found a linked value
		// if so, then prepare to look for the ele_id of the other
		// if not, then get the ele_id and we'll look for that in the value of the other
		if(strstr($sourceValue[0]['ele_value'], "#*=:*")) {
			// get the ele_id from the link
			$parts = explode("#*=:*", $sourceValue[0]['ele_value']);
			if(strstr($parts[2], "[=*9*:")) { exit("Error: subform entry found with more than one value linked to parent form"); }
			//print "SELECT id_req FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form = '" . $targetForm['fid'] . "' AND ele_caption = '$ffcaption' AND ele_id = '" . $parts[2] . "' $scope_filter GROUP BY id_req";			
			$targetValue = q("SELECT id_req FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form = '" . $targetForm['fid'] . "' AND ele_caption = '$ffcaption' AND ele_id = '" . $parts[2] . "' $scope_filter GROUP BY id_req");				
			if($targetValue[0]['id_req']) {
				$entries_to_return[0] = $targetValue[0]['id_req'];
			} else {
				$entries_to_return[0] = "";
			}
			return $entries_to_return;						
		} else { // look for the ele_id in the value -- can't imagine this will ever be used, since it is a reverse direction for the linking, ie: the many form is being used as the main, and the subform is the "one form" in the one to many relationship.  This seems to only occur if the relationship of the forms is mis-specified.
			// what should happen here is that the behaviour of the add one/add another, etc UI is different
			// instead of add buttons, you simply have a link to the one entry that corresponds to what the user has selected in the linked select box. 
			// except the problem with that is we then have to change the link on the fly without reloading the page every time the selection in the linked selectbox is altered by the user
			// this will hopefully never have to happen!
			$targetValue = q("SELECT id_req FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form = '" . $targetForm['fid'] . "' AND ele_caption = '$ffcaption' AND ele_value LIKE '%#*=:*" . $sourceValue[0]['ele_id'] . "%' $scope_filter GROUP BY id_req");
			if($targetValue[0]['id_req']) {
				foreach($targetValue as $tv) {
					$entries_to_return[] = $tv['id_req']; 
				}
			} else {
				$entries_to_return[0] = "";
			}
			return $entries_to_return;
		}
	}
}


// this function checks for singleentry status and returns the appropriate entry in the form if there is one
function getSingle($fid, $uid, $groups, $member_handler) {
	global $xoopsDB;
	// determine single/multi status
	$smq = q("SELECT singleentry FROM " . $xoopsDB->prefix("form_id") . " WHERE id_form=$fid");
	if($smq[0]['singleentry'] != "") {
		// find the entry that applies
		$single['flag'] = 1;
		if($smq[0]['singleentry'] == "on") { // if we're looking for a regular single, find first entry for this user
			$entry_q = q("SELECT id_req FROM " . $xoopsDB->prefix("form_form") . " WHERE uid=$uid AND id_form=$fid ORDER BY id_req LIMIT 0,1");
			if($entry_q[0]['id_req']) {
				$single['entry'] = $entry_q[0]['id_req'];
			} else {
				$single['entry'] = "";	
			}
		} elseif($smq[0]['singleentry'] == "group") { // get the first entry belonging to anyone in their groups
			foreach($groups as $grp) {
				if($grp != XOOPS_GROUP_USERS) { // exclude registered users group since that's everyone!
					$users = $member_handler->getUsersByGroup($grp);
					$all_users = array_merge($users, $all_users);
					unset($users);
				}
			}
			$uq = makeUidFilter($all_users);
			$entry_q = q("SELECT id_req FROM " . $xoopsDB->prefix("form_form") . " WHERE ($uq) AND id_form=$fid ORDER BY id_req LIMIT 0,1");
			if($entry_q[0]['id_req']) {
				$single['entry'] = $entry_q[0]['id_req'];
			} else {
				$single['entry'] = "";	
			}
		} else {
			exit("Error: invalid value found for singleentry for form $fid");
		}
	} else {
		$single['flag'] = 0;
	}
	return $single;

}




function displayForm($formframe, $entry="", $mainform="", $done_dest="", $done_text="", $settings="", $onetooneTitles="", $overrideValue="", $overrideMulti="") {
//syntax:
//displayform($formframe, $entry, $mainform)
//$formframe is the id of the form OR title of the form OR name of the framework
//$entry is the numeric entry to display in the form -- if $entry is the word 'proxy' then it is meant to force a new form entry when the form is a single-entry form that the user already may have an entry in
//$mainform is the starting form to use, if this is a framework (can be specified by form id or by handle)
//$done_dest is the URL to go to after the form has been submitted
//Steps:
//1. identify form or framework
//2. if framework, check for unified display options
//3. if entry specified, then get data for that entry
//4. drawform with data if necessary

	global $xoopsDB, $xoopsUser, $myts;
	include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

	$original_entry = $entry;

	$mid = getFormulizeModId();

	$currentURL = getCurrentURL();

	// identify form or framework

	list($fid, $frid) = getFormFramework($formframe, $mainform);

	if($_POST['deletesubs']) { // if deletion of sub entries requested
		foreach($_POST as $k=>$v) {
			if(strstr($k, "delbox")) {
				$subs_to_del[] = $v;
			}
		}
		deleteFormEntries($subs_to_del);
	}

	if($_POST['parent_form']) { // if we're coming back from a subform
		$entry = $_POST['parent_entry'];
		$fid = $_POST['parent_form'];
	}

	if($_POST['go_back_form']) { // we just received a subform submission
		$entry = $_POST['sub_submitted'];
		$fid = $_POST['sub_fid'];
		$go_back['form'] = $_POST['go_back_form'];
		$go_back['entry'] = $_POST['go_back_entry'];
	}

	// set $entry in the case of a form_submission where we were editing an entry (just in case that entry is not what is used to call this function in the first place -- ie: we're on a subform and the mainform has no entry specified, or we're clicking submit over again on a single-entry form where we started with no entry)
	$entrykey = "entry" . $fid;
	if(!$entry AND $_POST[$entrykey]) { // $entrykey will only be set when *editing* an entry, not on new saves
		$entry = $_POST[$entrykey];
	}

	$gperm_handler = &xoops_gethandler('groupperm');
	$groups = $xoopsUser ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS;
	$uid = $xoopsUser ? $xoopsUser->getVar('uid') : '0';
	$owner = getEntryOwner($entry); 
	$member_handler =& xoops_gethandler('member');
	$owner_groups =& $member_handler->getGroupsByUser($owner, FALSE);

	if(!$scheck = security_check($fid, $entry, $uid, $owner, $groups, $mid, $gperm_handler, $owner_groups)) {
		print "<p>" . _NO_PERM . "</p>";
		return;
	}

	// main security check passed, so let's initialize flags	
	$go_back['url'] = $done_dest;
	$single_result = getSingle($fid, $uid, $groups, $member_handler);
	$single = $single_result['flag'];
	if($single AND !$entry) { // only adjust the active entry if we're not already looking at an entry
		$entry = $single_result['entry'];
		$owner = getEntryOwner($entry);
		unset($owner_groups);
		$owner_groups =& $member_handler->getGroupsByUser($owner, FALSE);
	}
	if($entry == "proxy") { $entry = ""; } // convert the proxy flag to the actual null value expected for new entry situations (do this after the single check!)
	$editing = is_numeric($entry); // will be true if there is an entry we're looking at already

	// set these arrays for the one form, and they are added to by the framework if it is in effect
	$fids[0] = $fid;
	if($entry) {
		$entries[$fid][0] = $entry;
	} else {
		$entries[$fid][0] = "";
	}
	
	if($frid) { 
		$linkResults = checkForLinks($frid, $fids, $fid, $entries, $gperm_handler, $owner_groups, $mid, $member_handler, $owner); 
		unset($entries);
		unset($fids);

		$fids = $linkResults['fids'];
		$entries = $linkResults['entries'];
		$sub_fids = $linkResults['sub_fids'];
		$sub_entries = $linkResults['sub_entries'];
	}

	// need to handle submission of entries 
	$formulize_mgr =& xoops_getmodulehandler('elements', 'formulize');

	$info_received_msg = 0;
	$info_continue = 0;
	if($entries[$fid][0]) { $info_continue = 1; }
	if($_POST['form_submitted']) {
		$info_received_msg = "1"; // flag for display of info received message
		include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formread.php";
		$temp_entries = handleSubmission($formulize_mgr, $entries, $uid, $owner, $fid, $owner_groups, $groups);
		if($single OR ($entries[$fid][0] AND ($original_entry OR $_POST[$entrykey])) OR $overrideMulti) { // if we just did a submission on a single form, or we just edited a multi, then assume the identity of the new entry
			$entry = $temp_entries[$fid][0];
			$entries = $temp_entries;
			$owner = getEntryOwner($entry);
			unset($owner_groups);
			$owner_groups =& $member_handler->getGroupsByUser($owner, FALSE);
			$info_continue = 1;
		} elseif(!$_POST['target_sub']) { // as long as the form was submitted and we're not going to a sub form, then display the info received message and carry on with a blank form
			if(!$original_entry) { // if we're on a multi-form where the display form function was called without an entry, then clear the entries and behave as if we're doing a new add
				unset($entries);
				$entries[$fid][0] = "";
			}
			$info_continue = 2;
		}
		if(count($fids)>1) { // we have a unified one-to-one situation, so write the entries just created to the list of links
			writeLinks($temp_entries, $fids);
		}
	}


      // need to add code here to switch some things around if we're on a subform for the first time (add)
	// note: double nested sub forms will not work currently, since on the way back to the intermediate level, the go_back values will not be set correctly
      if($_POST['target_sub'] OR $_POST['goto_sfid']) {
		$info_continue = 0;
		if($_POST['goto_sfid']) {
			$new_fid = $_POST['goto_sfid'];
		} else {
			$new_fid = $_POST['target_sub'];
		}
		$go_back['form'] = $fid;
		$go_back['entry'] = $temp_entries[$fid][0];
		unset($entries);
		unset($fids);
		unset($sub_fids);
		unset($sub_entries);
		$fid = $new_fid;
		$fids[0] = $new_fid;
		if($_POST['target_sub']) { // if we're adding a new entry
			$entries[$new_fid][0] = "";
		} else { // if we're going to an existing entry
			$entries[$new_fid][0] = $_POST['goto_sub'];
		}
		$entry = $entries[$new_fid][0];
		$single_result = getSingle($fid, $uid, $groups, $member_handler);
		$single = $single_result['flag'];
		if($single AND !$entry) {
			$entry = $single_result['entry'];
			unset($entries);
			$entries[$fid][0] = $entry;
		}
		unset($owner);
		$owner = getEntryOwner($entries[$new_fid][0]); 
		$editing = is_numeric($entry); 
		unset($owner_groups);
		$owner_groups =& $member_handler->getGroupsByUser($owner, FALSE);
// DON'T UNDERSTAND WHY WE'RE CHECKING FOR LINKS WHEN A SUBFORM IS LOADED (NO SUPPORT INTENDED FOR DOUBLE NESTED SUBFORMS)
/*
		$linkResults = checkForLinks($frid, $fids, $fid, $entries, $gperm_handler, $owner_groups, $mid, $member_handler, $owner); 
		unset($entries);
		unset($fids);
		$fids = $linkResults['fids'];
		$entries = $linkResults['entries'];
		$sub_fids = $linkResults['sub_fids'];
		$sub_entries = $linkResults['sub_entries'];
*/
		$info_received_msg = 0;// never display this message when a subform is displayed the first time.	
		if($entry) { $info_continue = 1; }
		if(!$scheck = security_check($fid, $entries[$fid][0], $uid, $owner, $groups, $mid, $gperm_handler, $owner_groups)) {
			print "<p>" . _NO_PERM . "</p>";
			return;
		}
      }


	include_once XOOPS_ROOT_PATH."/class/xoopsformloader.php";
	include_once XOOPS_ROOT_PATH . "/include/functions.php";

/*	if($uid==1) {
	print "Forms: ";
	print_r($fids);
	print "<br>Entries: ";
	print_r($entries);
	print "<br>Subforms: ";
	print_r($sub_fids);
	print "<br>Subentries: ";
	print_r($sub_entries); // debug block - ONLY VISIBLE TO USER 1 RIGHT NOW
	} */
	$title = "";
	foreach($fids as $this_fid) {
  
// NOTE: SERIOUSLY NEED TO CHECK WHY THIS CHECK IS FAILING FOR HOURS LOG!    
//		if(!$scheck = security_check($this_fid, $entries[$this_fid], $uid, $owner, $groups, $mid, $gperm_handler, $owner_groups)) {
//			continue;
//		}


      	unset($prevEntry);
      	if($entries[$this_fid]) { 	// if there is an entry, then get the data for that entry
      		$prevEntry = getEntryValues($entries[$this_fid][0]); 
      	}

      	// display the form

      	//get the form title: (do only once)
		$firstform = 0;
		if(!$form) {
			drawJavascript();
			$firstform = 1; 	      	
			$title = getFormTitle($this_fid);
			if(method_exists($myts, 'formatForML')) {
				$title = $myts->formatForML($title);
			} 
	      	$form = new XoopsThemeForm($title, 'formulize', "$currentURL");
			$form->setExtra("enctype='multipart/form-data'"); // impératif!

			if(is_array($settings)) { $form = writeHiddenSettings($settings, $form); }
			$form->addElement (new XoopsFormHidden ('ventry', $settings['ventry'])); // necessary to trigger the proper reloading of the form page, until Done is called and that form does not have this flag.

			// include who the entry belongs to and the date
			// include acknowledgement that information has been updated if we have just done a submit
			// form_meta includes: last_update, created, last_update_by, created_by

			// build the break HTML and then add the break to the form
			$breakHTML = "<center><p><b>";
			if($info_received_msg) { $breakHTML .= _formulize_INFO_SAVED . "&nbsp;"; }
			if($info_continue == 1) {
				$breakHTML .= _formulize_INFO_CONTINUE1 . "</b></p>";
			} elseif($info_continue == 2) {
				$breakHTML .=  _formulize_INFO_CONTINUE2 . "</b></p>";
			} else {
				$breakHTML .=  _formulize_INFO_MAKENEW . "</b></p>";
			}

			$breakHTML .= "</center><table cellpadding=5 width=100%><tr><td width=50% style=\"vertical-align: bottom;\">";

			$breakHTML .= "<p><b>" . _formulize_FD_ABOUT . "</b><br>";
			
			if($entries[$this_fid][0]) {
				if(!$member_handler) { $member_handler =& xoops_gethandler('member'); }
				$form_meta = getMetaData($entries[$this_fid][0], $member_handler);
				$breakHTML .= _formulize_FD_CREATED . $form_meta['created_by'] . " " . _formulize_TEMP_ON . " " . $form_meta['created'] . "<br>" . _formulize_FD_MODIFIED . $form_meta['last_update_by'] . " " . _formulize_TEMP_ON . " " . $form_meta['last_update'] . "</p>";
			} else {
				$breakHTML .= _formulize_FD_NEWENTRY . "</p>";
			}

			$breakHTML .= "</td><td width=50% style=\"vertical-align: bottom;\">"; //<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p></td><td>";

			$breakHTML .= "<p>" . _formulize_INFO_SAVEBUTTON;
			if(!$done_text) { 
				$temptext = _formulize_DONE; 
			} else {
				$temptext = $done_text;
			}
			if($temptext != "{NOBUTTON}") {
 				$breakHTML .= "<br>" . _formulize_INFO_DONE1 . $temptext . _formulize_INFO_DONE2;
			}

			$breakHTML .= "</p></td></tr></table>";
			$form->insertBreak($breakHTML, "even");

// OLD Message system commented out...
/*			if($entries[$this_fid][0]) {
				if(!$member_handler) { $member_handler =& xoops_gethandler('member'); }
				$form_meta = getMetaData($entries[$this_fid][0], $member_handler);
				$form->insertBreak("<center><p>". _formulize_FD_ABOUT . "<br>" . _formulize_FD_CREATED . $form_meta['created_by'] . " " . _formulize_TEMP_ON . " " . $form_meta['created'] . "<br>" . _formulize_FD_MODIFIED . $form_meta['last_update_by'] . " " . _formulize_TEMP_ON . " " . $form_meta['last_update'] . "</p></center>", "head");
			} elseif($info_received_msg) {
				if(!$done_text) { 
					$temptext = _formulize_DONE; 
				} else {
					$temptext = $done_text;
				}
				$form->insertBreak("<center><p>" . _formulize_INFO_SAVED . $temptext . _formulize_INFO_SAVED2 . "</p></center>", "head");
			}
*/
		}

		if($onetooneTitles=="1" AND !$firstform) { // set onetooneTitle flag to 1 when function invoked to force drawing of the form title over again
			$title = getFormTitle($this_fid);
			$form->insertBreak("<table><th>$title</th></table>","");
		}

		// if this form has a parent, then determine the $parentLinks
		if($go_back['form'] AND !$parentLinks[$this_fid]) {
			$parentLinks[$this_fid] = getParentLinks($this_fid, $frid);
		}

      	$form = compileElements($this_fid, $form, $formulize_mgr, $prevEntry, $entries[$this_fid][0], $go_back, $parentLinks[$this_fid], $owner_groups, $groups, $overrideValue);

      	// DRAW IN THE SPECIAL UI FOR A SUBFORM LINK (ONE TO MANY)		
     		if(count($sub_fids) > 0) { // if there are subforms, then draw them in...only once we have a bonafide entry in place already

      		// draw in special params for this form
			$form->addElement (new XoopsFormHidden ('target_sub', ''));
			$form->addElement (new XoopsFormHidden ('del_subs', ''));
			$form->addElement (new XoopsFormHidden ('goto_sub', ''));
			$form->addElement (new XoopsFormHidden ('goto_sfid', ''));

			foreach($sub_fids as $sfid) {

				$subUICols = drawSubLinks($sfid, $sub_entries, $uid, $groups, $member_handler, $frid, $gperm_handler, $mid);

				unset($subLinkUI);
      			$subLinkUI = new XoopsFormLabel($subUICols['c1'], $subUICols['c2']);
      			$form->addElement($subLinkUI);
      		}
      	} 

	} // end of for each fids

	// draw in proxy box if necessary (only if they have permission and only on new entries, not on edits)
	if($gperm_handler->checkRight("add_proxy_entries", $fid, $groups, $mid) AND !$entries[$fid][0]) {
		if(!$member_handler) { $member_handler =& xoops_gethandler('member'); }
		$form = addProxyList($form, $groups, $member_handler, $gperm_handler, $fid, $mid);
	}

	// add flag to indicate that the form has been submitted
	$form->addElement (new XoopsFormHidden ('form_submitted', "1"));
	if($go_back['form']) { // if this is set, then we're doing a subform, so put in a flag to prevent the parent from being drawn again on submission
		$form->addElement (new XoopsFormHidden ('sub_fid', $fid));
		$form->addElement (new XoopsFormHidden ('sub_submitted', $entries[$fid][0]));
		$form->addElement (new XoopsFormHidden ('go_back_form', $go_back['form']));
		$form->addElement (new XoopsFormHidden ('go_back_entry', $go_back['entry']));
	}
	
	// draw in the submitbutton if necessary
	if($entry) { // existing entry, if it's their own and they can update their own, or someone else's and they can update someone else's
		if(($owner == $uid AND $gperm_handler->checkRight("update_own_entry", $fid, $groups, $mid)) OR ($owner != $uid AND $gperm_handler->checkRight("update_other_entries", $fid, $groups, $mid))) {
			$form = addSubmitButton($form, _formulize_SAVE, $go_back, $currentURL, $done_text, $settings, $temp_entries[$this_fid][0]);
		} else {
			$form = addSubmitButton($form, '', $go_back, $currentURL, $done_text, $settings, $temp_entries[$this_fid][0]); // draw in just the done button
		}
	} else { // new entry
		if($gperm_handler->checkRight("add_own_entry", $fid, $groups, $mid)) {
			$form = addSubmitButton($form, _formulize_SAVE, $go_back, $currentURL, $done_text, $settings, $temp_entries[$this_fid][0]);
		} else {
			$form = addSubmitButton($form, '', $go_back, $currentURL, $done_text, $settings, $temp_entries[$this_fid][0]); // draw in just the done button
		}
	}

	print $form->render();
	
}


// add the submit button to a form
function addSubmitButton($form, $subButtonText, $go_back="", $currentURL, $done_text, $settings, $entry) {
	if($go_back['url'] == "" AND !isset($go_back['form'])) { // there are no back instructions at all, then make the done button go to the front page of whatever is going on in pageworks
		print "<form name=go_parent action=\"$currentURL\" method=post>"; //onsubmit=\"javascript:verifyDone();\" method=post>";
		if(is_array($settings)) { writeHiddenSettings($settings); }
		print "<input type=hidden name=lastentry value=$entry>";
		print "</form>";
	}
	if($go_back['form']) { // parent form overrides specified back URL
		print "<form name=go_parent action=\"$currentURL\" method=post>"; //onsubmit=\"javascript:verifyDone();\" method=post>";
		print "<input type=hidden name=parent_form value=" . $go_back['form'] . ">";
		print "<input type=hidden name=parent_entry value=" . $go_back['entry'] . ">";
		print "<input type=hidden name=ventry value=" . $settings['ventry'] . ">";
		if(is_array($settings)) { writeHiddenSettings($settings); }
		print "<input type=hidden name=lastentry value=$entry>";
		print "</form>";
	} elseif($go_back['url']) {
		print "<form name=go_parent action=\"" . $go_back['url'] . "\" method=post>"; //onsubmit=\"javascript:verifyDone();\" method=post>";
		if(is_array($settings)) { writeHiddenSettings($settings); }		
		print "<input type=hidden name=lastentry value=$entry>";
		print "</form>";
	} 
	if(!$done_text) { $done_text = _formulize_DONE; }
	$buttontray = new XoopsFormElementTray("", "&nbsp;");
	if($subButtonText == _formulize_SAVE) {
		$saveButton = new XoopsFormButton('', 'submitx', $subButtonText, 'button'); // doesn't use name submit since that conflicts with the submit javascript function
		$saveButton->setExtra("onclick=javascript:window.document.formulize.submit();");
		$buttontray->addElement($saveButton);
	}
	if($done_text != "{NOBUTTON}") {
		$donebutton = new XoopsFormButton('', 'donebutton', $done_text, 'button');
		$donebutton->setExtra("onclick=javascript:verifyDone();");
		$buttontray->addElement($donebutton); 
	}
	$form->addElement($buttontray);
	return $form;
}

// this function draws in the UI for sub links
function drawSubLinks($sfid, $sub_entries, $uid, $groups, $member_handler, $frid, $gperm_handler, $mid) {

	global $xoopsDB;
	$GLOBALS['framework'] = $frid;
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
	// need to do a number of checks here, including looking for single status on subform, and not drawing in add another if there is an entry for a single
			
	$sub_single_result = getSingle($sfid, $uid, $groups, $member_handler);
	$sub_single = $sub_single_result['flag'];
	if($sub_single) {
		unset($sub_entries);
		$sub_entries[$sfid][0] = $sub_single_result['entry'];
	}
	// get the title of this subform
	$subtitle = q("SELECT desc_form FROM " . $xoopsDB->prefix("form_id") . " WHERE id_form = $sfid");
	$col_one = "<p>" . $subtitle[0]['desc_form'] . "</p>";
// add button moved to right side
/*	if(count($sub_entries[$sfid]) == 1 AND $sub_entries[$sfid][0] == "") {
		$col_one .= "<p><input type=button name=addsub value='". _formulize_ADD_ONE . "' onclick=\"javascript:add_sub('$sfid');\"></p>";
	} elseif(!$sub_single) {
		$col_one .=  "<p><input type=button name=addsub value='". _formulize_ADD_ANOTHER . "' onclick=\"javascript:add_sub('$sfid');\"></p>";
	} else {
		$col_one .= "<p>&nbsp;</p>"; // place holder so delete button remains in the same place when add button not drawn
	} */
	if(count($sub_entries[$sfid])>0 AND $sub_entries[$sfid][0] != "") {
		$col_one .= "<p><input type=submit name=deletesubs value='" . _formulize_DELETE_CHECKED . "' onclick=\"javascript:sub_del();\"></p>";
	}
		// list the entries, including links to them and delete checkboxes
	
	// get the headerlist for the subform and convert it into handles
	// note big assumption/restriction that we are only using the first header found (ie: only specify one header for a sub form!)
	$subHeaderList = getHeaderList($sfid);
	$subHandle = handle($subHeaderList[0], $sfid, $frid);
	foreach($sub_entries[$sfid] as $sub_ent) {
		if($sub_ent != "") {
			$data = getData($frid, $sfid, $sub_ent);
			$col_two .= "<p>";
			// check to see if we draw a delete box or not
			$deleteSelf = $gperm_handler->checkRight("delete_own_entry", $sfid, $groups, $mid);
			$deleteOther = $gperm_handler->checkRight("delete_other_entries", $sfid, $groups, $mid);
			$sub_owner = getEntryOwner($sub_ent);
			//print "sub_owner: $sub_owner<br>uid: $uid<br>deleteself: $deleteSelf<br>";
			if(($sub_owner == $uid AND $deleteSelf) OR ($sub_owner != $uid AND $deleteOther)) {
				$col_two .= "<input type=checkbox name=delbox$sub_ent value=$sub_ent></input>&nbsp;&nbsp;";
			}
			if(!$sub_name = display($data, $subHandle, 0)) { $sub_name = _formulize_NOSUBNAME . $sub_ent; }
			$col_two .= "<a href=\"\" onclick=\"javascript:goSub('$sub_ent', '$sfid');return false;\">$sub_name</a></p>";
		}
	}
	if(count($sub_entries[$sfid]) == 1 AND $sub_entries[$sfid][0] == "") {
		$col_two .= "<p><input type=button name=addsub value='". _formulize_ADD_ONE . "' onclick=\"javascript:add_sub('$sfid');\"></p>";
	} elseif(!$sub_single) {
		$col_two .=  "<p><input type=button name=addsub value='". _formulize_ADD_ANOTHER . "' onclick=\"javascript:add_sub('$sfid');\"></p>";
	} 
	//$col_two .= "</form>";

	$to_return['c1'] = $col_one;
	$to_return['c2'] = $col_two;
	return $to_return;

}


// add the proxy list to a form
function addProxyList($form, $groups, $member_handler, $gperm_handler, $fid, $mid) {

	global $xoopsDB;

			$proxylist = new XoopsFormSelect(_AM_SELECT_PROXY, 'proxyuser', 0, 5, TRUE); // made multi May 3 05
			$proxylist->addOption('noproxy', _formulize_PICKAPROXY);
			
			$add_groups = $gperm_handler->getGroupIds("add_own_entry", $fid, $mid);
			foreach($add_groups as $grp) {
				$add_users = $member_handler->getUsersByGroup($grp);
				$all_add_users = array_merge($add_users, $all_add_users);
				unset($add_users);
			}
		
			$unique_users = array_unique($all_add_users);

			foreach($unique_users as $uid) {
				$uqueryforrealnames = "SELECT name FROM " . $xoopsDB->prefix("users") . " WHERE uid=$uid";
				$uresqforrealnames = $xoopsDB->query($uqueryforrealnames);
				$urowqforrealnames = $xoopsDB->fetchRow($uresqforrealnames);
				$punames[] = $urowqforrealnames[0];
				//print "username: $urowqforrealnames[0]<br>"; // debug code
			}

			// alphabetize the proxy list added 11/2/04
			array_multisort($punames, $unique_users);

			for($i=0;$i<count($unique_users);$i++)
			{
				$proxylist->addOption($unique_users[$i], $punames[$i]);
			}

			$proxylist->setValue('noproxy');
						
			$form->addElement($proxylist);
			return $form;
}


//this function takes a formid and compiles all the elements for that form
function compileElements($fid, $form, $formulize_mgr, $prevEntry, $entry, $go_back, $parentLinks, $owner_groups, $groups, $overrideValue="") {
	
	global $xoopsDB;

	$criteria = new Criteria('ele_display', 1);
	$criteria->setSort('ele_order');
	$criteria->setOrder('ASC');
	$elements =& $formulize_mgr->getObjects2($criteria,$fid);
	$count = 0;
	foreach( $elements as $i ){
		$ele_value = $i->getVar('ele_value');
	
		if($prevEntry) { 
			$ele_value = loadValue($prevEntry, $i, $ele_value, $owner_groups, $groups); // get the value of this element for this entry as stored in the DB 
		} elseif($go_back['form']) { // if there's a parent form...
			$this_ele_id = $i->getVar('ele_id');
			// check here to see if we need to initialize the value of a linked selectbox when it is the key field for a subform
			// although this is setup as a loop through all found parentLinks, only the last one will be used, since ele_value[2] is overwritten each time.
			// assumption is there will only be one parent link for this form
			for($z=0;$z<count($parentLinks['source']);$z++) {					
				if($this_ele_id == $parentLinks['self'][$z]) { // this is the element
					// get the caption of the parent's field
					$pcq = q("SELECT ele_caption FROM " . $xoopsDB->prefix("form") . " WHERE id_form='" . $go_back['form'] . "' AND ele_id='" . $parentLinks['source'][$z] . "'");				
					$parentCap = str_replace ("'", "`", $pcq[0]['ele_caption']);
					$pvq = q("SELECT ele_id FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form='" . $go_back['form'] . "' AND id_req='" . $go_back['entry'] . "' AND ele_caption='$parentCap'");
					$pid = $pvq[0]['ele_id'];

					// NOTE: assuming that there will only be one value in the match, ie: the link field is not a multiple select box!
					// format of value should be $formid#*=:*$formcaption#*=:*$ele_id
					$ele_value[2] = $go_back['form'] . "#*=:*" . $parentCap . "#*=:*" . $pid; 
				}
			}
		} elseif($overrideValue){ // used to force a default setting in a form element, other than the normal default
			if(!is_array($overrideValue)) { //convert a string to an array so that strings don't screw up logic below (which is designed for arrays)
				$temp = $overrideValue;
				unset($overrideValue);
				$overrideValue[0] = $temp;
			}
			// currently only operative for select boxes
			switch($i->getVar('ele_type')) {
				case "select":
					foreach($overrideValue as $ov) {
						if(array_key_exists($ov, $ele_value[2])) {
							$ele_value[2][$ov] = 1;
						}	
					}
					break;
			}
		}

		include_once XOOPS_ROOT_PATH . "/modules/formulize/class/elementrenderer.php";

		$renderer =& new formulizeElementRenderer($i);
		$form_ele =& $renderer->constructElement('ele_'.$i->getVar('ele_id'), $ele_value);

		if (isset ($ele_value[0])) {
			$ele_value[0] = eregi_replace("'", "`", $ele_value[0]);
			$ele_value[0] = stripslashes($ele_value[0]); 
		} 

		if ($i->getVar('ele_type') == 'sep'){
			$ele_value = split ('<*>', $ele_value[0]);		
			foreach ($ele_value as $t){
				if (strpos($t, '<')!=false) {
					$ele_value[0] = $t;
			}	}
			$ele_value = split ('</', $ele_value[0]);			
			$hid = new XoopsFormHidden('ele_'.$i->getVar('ele_id'), $ele_value[0]);
			$form->addElement ($hid);
		}
		if ($i->getVar('ele_type') == 'areamodif'){
			$hid2 = new XoopsFormHidden('ele_'.$i->getVar('ele_id'), $ele_value[0]);
			$form->addElement ($hid2);
		}
		if ($i->getVar('ele_type') == 'upload'){
			$hid3 = new XoopsFormHidden($ele_value[1], $ele_value[1]);
			$form->addElement ($hid3);
		}
		$req = intval($i->getVar('ele_req'));
		if($i->getVar('ele_type') != "ib") { // if it's a break, handle it differently...
			$form_ele->setExtra("onchange=\"javascript:formulizechanged=1;\"");
			$form->addElement($form_ele, $req);
		} else {
			$form->insertBreak(stripslashes($form_ele[0]), $form_ele[1]);
		}
		$count++;
		unset($hidden);
	}
	$form->addElement (new XoopsFormHidden ('counter', $count)); // not used by reading logic?
	if($entry) {
		$form->addElement (new XoopsFormHidden ('entry'.$fid, $entry));
	}
	return $form;

}

function loadValue($prevEntry, $i, $ele_value, $owner_groups, $groups) {
//global $xoopsUser;
//if($xoopsUser->getVar('uid') == 1) {
//print_r($prevEntry);

//}
			$type = $i->getVar('ele_type');
			// going direct from the DB since if multi-language is active, getVar will translate the caption
			//$caption = $i->getVar('ele_caption');
			$ele_id = $i->getVar('ele_id');
			global $xoopsDB;
			$ecq = q("SELECT ele_caption FROM " . $xoopsDB->prefix("form") . " WHERE ele_id = '$ele_id'");
			$caption = $ecq[0]['ele_caption'];
	
			// two lines to mimic how captions are written to the DB...
			$caption = eregi_replace ("&#039;", "`", $caption);
			$caption = eregi_replace ("&quot;", "`", $caption);
			$caption = eregi_replace ("'", "`", $caption);

			$key = array_search($caption, $prevEntry['captions']);

			if(!is_numeric($key) AND $key=="") { return $ele_value; } // do nothing if the caption was not found
			$value = $prevEntry['values'][$key];

			/*print_r($ele_value);
			print "<br>After: "; //debug block
			*/
			switch ($type)
			{
				case "text":
					$ele_value[2] = $value;				
					$ele_value[2] = eregi_replace("'", "&#039;", $ele_value[2]);				
					break;
				case "textarea":
					$ele_value[0] = $value;								
					break;
				case "select":
				case "radio":
				case "checkbox":

					// NEED TO ADD IN INITIALIZATION OF LINKED SELECT BOXES FOR SUBFORMS

					// NOTE:  unique delimiter used to identify LINKED select boxes, so they can be handled differently.
					if(strstr($value, "#*=:*")) // if we've got a linked select box, then do everything differently
					{
						$ele_value[2] = $value;
					}
					else
					{

					// put the array into another array (clearing all default values)
					// then we modify our place holder array and then reassign

					if ($type != "select")
					{
						$temparray = $ele_value;
					}
					else
					{
						$temparray = $ele_value[2];
					}					
					$temparraykeys = array_keys($temparray);

					if($temparraykeys[0] == "{FULLNAMES}" OR $temparraykeys[0] == "{USERNAMES}") { // ADDED June 18 2005 to handle pulling in usernames for the user's group(s)
						if($temparraykeys[0] == "{FULLNAMES}") { $nametype = "name"; }
						if($temparraykeys[0] == "{USERNAMES}") { $nametype = "uname"; }
						unset($temparraykeys);
						unset($temparray); // necessary to get rid of the fullnames/usernames flag
						if(count($owner_groups)>0) {
							$temparraykeys = gatherNames($owner_groups, $nametype);
						} else {
							$temparraykeys = gatherNames($groups, $nametype);
						}
					}


					$selvalarray = explode("*=+*:", $value);
					
					foreach($temparraykeys as $k)
					{
						if($k == $value) // if there's a straight match (not a multiple selection)
						{
							$temparray[$k] = 1;
						}
						elseif( in_array($k, $selvalarray) ) // or if there's a match within a multiple selection array)
						{
							$temparray[$k] = 1;
						}
						else // otherwise set to zero.
						{
							$temparray[$k] = 0;
						}
					}
					
					if ($type != "select")
					{
						$ele_value = $temparray;
					}
					else
					{
						$ele_value[2] = $temparray;
					}
					} // end of IF we have a linked select box
					break;
				case "yn":

					if($value == 1)
					{
						$ele_value = array("_YES"=>1, "_NO"=>0);
					}
					elseif($value == 2)
					{
						$ele_value = array("_YES"=>0, "_NO"=>1);
					}
					else
					{
						$ele_value = array("_YES"=>0, "_NO"=>0);

					}
					break;
				case "date":

					$ele_value[0] = $value;

					break;
			} // end switch

			/*print_r($ele_value);
			print "<br>"; //debug block
			*/
			return $ele_value;
}


// write the settings passed to this page from the view entries page, so the view can be restored when they go back
function writeHiddenSettings($settings, $form) {
	//unpack settings
	$sort = $settings['sort'];
	$order = $settings['order'];
	$oldcols = $settings['oldcols'];
	$currentview = $settings['currentview'];
	foreach($settings as $k=>$v) {
		if(substr($k, 0, 7) == "search_" AND $v != "") {
			$thiscol = substr($k, 7);
			$searches[$thiscol] = $v;
		}
	}
	//calculations:
	$calc_cols = $settings['calc_cols'];
	$calc_calcs = $settings['calc_calcs'];
	$calc_blanks = $settings['calc_blanks'];
	$calc_grouping = $settings['calc_grouping'];

	$hlist = $settings['hlist'];
	$hcalc = $settings['hcalc'];
	$lockcontrols = $settings['lockcontrols'];
	$asearch = $settings['asearch'];
	$lastloaded = $settings['lastloaded'];	

	// used for calendars...
	$calview = $settings['calview'];
	$calfrid = $settings['calfrid'];
	$calfid = $settings['calfid'];

	// write hidden fields
	if($form) { // write as form objects and return form
		$form->addElement (new XoopsFormHidden ('sort', $sort));
		$form->addElement (new XoopsFormHidden ('order', $order));
		$form->addElement (new XoopsFormHidden ('currentview', $currentview));
		$form->addElement (new XoopsFormHidden ('oldcols', $oldcols));
		foreach($searches as $key=>$search) {
			$search_key = "search_" . $key;
			$search = str_replace("'", "&#39;", $search);
			$form->addElement (new XoopsFormHidden ($search_key, stripslashes($search)));
		}
		$form->addElement (new XoopsFormHidden ('calc_cols', $calc_cols));
		$form->addElement (new XoopsFormHidden ('calc_calcs', $calc_calcs));
		$form->addElement (new XoopsFormHidden ('calc_blanks', $calc_blanks));
		$form->addElement (new XoopsFormHidden ('calc_grouping', $calc_grouping));
		$form->addElement (new XoopsFormHidden ('hlist', $hlist));
		$form->addElement (new XoopsFormHidden ('hcalc', $hcalc));
		$form->addElement (new XoopsFormHidden ('lockcontrols', $lockcontrols));
		$form->addElement (new XoopsFormHidden ('lastloaded', $lastloaded));
		$asearch = str_replace("'", "&#39;", $asearch);
		$form->addElement (new XoopsFormHidden ('asearch', stripslashes($asearch)));
		$form->addElement (new XoopsFormHidden ('calview', $calview));
		$form->addElement (new XoopsFormHidden ('calfrid', $calfrid));
		$form->addElement (new XoopsFormHidden ('calfid', $calfid));
		return $form;
	} else { // write as HTML
		print "<input type=hidden name=sort value='" . $sort . "'>";
		print "<input type=hidden name=order value='" . $order . "'>";
		print "<input type=hidden name=currentview value='" . $currentview . "'>";
		print "<input type=hidden name=oldcols value='" . $oldcols . "'>";
		foreach($searches as $key=>$search) {
			$search_key = "search_" . $key;
			$search = str_replace("\"", "&quot;", $search);
			print "<input type=hidden name=$search_key value=\"" . stripslashes($search) . "\">";
		}
		print "<input type=hidden name=calc_cols value='" . $calc_cols . "'>";
		print "<input type=hidden name=calc_calcs value='" . $calc_calcs . "'>";
		print "<input type=hidden name=calc_blanks value='" . $calc_blanks . "'>";
		print "<input type=hidden name=calc_grouping value='" . $calc_grouping . "'>";
		print "<input type=hidden name=hlist value='" . $hlist . "'>";
		print "<input type=hidden name=hcalc value='" . $hcalc . "'>";
		print "<input type=hidden name=lockcontrols value='" . $lockcontrols . "'>";
		print "<input type=hidden name=lastloaded value='" . $lastloaded . "'>";
		$asearch = str_replace("\"", "&quot;", $asearch);
		print "<input type=hidden name=asearch value=\"" . stripslashes($asearch) . "\">";
		print "<input type=hidden name=calview value='" . $calview . "'>";
		print "<input type=hidden name=calfrid value='" . $calfrid . "'>";
		print "<input type=hidden name=calfid value='" . $calfid . "'>";
	}
}


// draw in javascript for this form that is relevant to subforms
function drawJavascript() {
print "\n<script type='text/javascript'>\n";
print "<!--\n";

print " var formulizechanged=0;\n";

print "	function verifyDone() {\n";
//print "		alert(formulizechanged);\n";
print "		if(formulizechanged==0) {\n";
print "			window.document.go_parent.submit();\n";
print "		} else {\n";
print "			var answer = confirm (\"" . _formulize_CONFIRMNOSAVE . "\");\n";
print "			if (answer) {\n";
print "				window.document.go_parent.submit();\n";
print "			} else {\n";
print "				return false;\n";
print "			}\n";
print "		}\n";
print "	}\n";
	
print "	function add_sub(sfid) {\n";
print "		document.formulize.target_sub.value=sfid;\n";
print "		document.formulize.submit();\n";
print "	}\n";

print "	function sub_del() {\n";
print "		var answer = confirm ('" . _formulize_DEL_ENTRIES . "')\n";
print "		if (answer) {\n";
print "			return true;\n";
print "		} else {\n";
print "			return false;\n";
print "		}\n";
print "	}\n";

print "	function goSub(ent, fid) {\n";
print "		document.formulize.goto_sub.value = ent;\n";
print "		document.formulize.goto_sfid.value = fid;\n";
print "		document.formulize.submit();\n";
print "	}\n";
			
print "-->\n";
print "</script>\n";
}

?>