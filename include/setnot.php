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

// this file generates the set notifications popup

// delete calculations from request list
function handleDelete($uid, $fid, $mid) {
	global $xoopsDB;
	$delete = 0;
	foreach($_POST as $k=>$v) {
		if(strstr($k, "delete_")) {
			$delete = 1;
			$id = substr($k, 7);
			$event = q("SELECT not_cons_event FROM " . $xoopsDB->prefix("formulize_notification_conditions") . " WHERE not_cons_id=" .intval($id));
			$sql = "DELETE FROM " . $xoopsDB->prefix("formulize_notification_conditions") . " WHERE not_cons_id=" .intval($id);
			if(!$result = $xoopsDB->query($sql)) {
				exit("Error:  could not remove notification info.  SQL:<br>$sql</br>");
			}
			// check if the current user has any items left for a this event, and if not, then unsub from that event
			$anyleft = q("SELECT * FROM " . $xoopsDB->prefix("formulize_notification_conditions") . " WHERE not_cons_uid=$uid AND not_cons_event=\"".$event[0]['not_cons_event']."\"");
			if(count($anyleft) == 0) {
				$notification_handler =& xoops_gethandler('notification');
				$notification_handler->unsubscribe('form', $fid, $event[0]['not_cons_event'], $mid, $uid);
			}

			break; // only one item can be deleted at a time
		}
	}
	return $delete;
}


require_once "../../../mainfile.php";

global $xoopsConfig;
// load the formulize language constants if they haven't been loaded already
	if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
	} else {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
	}


global $xoopsDB, $xoopsUser;
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

	// Set some required variables
	$mid = getFormulizeModId();
	$fid="";
	if(!$fid = $_GET['fid']) {
		$fid = $_POST['fid'];
	}

	$gperm_handler = &xoops_gethandler('groupperm');
	$member_handler =& xoops_gethandler('member');
	$groups = $xoopsUser ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS;
	$uid = $xoopsUser->getVar('uid');

	if(!$scheck = security_check($fid, "", $uid, "", $groups, $mid, $gperm_handler, "")) {
		print "<p>" . _NO_PERM . "</p>";
		exit;
	}

// main body of page goes here...

// cleanup notifications -- remove entries from our table for specific uid notifications, where the user has unsubscribed
// not in use now, see note below with the function
//cleanupNots($fid, $uid, $mid);

$cols = getAllColList($fid, "", $groups); // notifications can only be set on a single form at a time

$canSetNots = $gperm_handler->checkRight("set_notifications_for_others", $fid, $groups, $mid);

// add latest element condition to existing conditions
if(get_magic_quotes_gpc()) {
	$_POST['new_term'] = stripslashes($_POST['new_term']);
	$_POST['template'] = stripslashes($_POST['template']);
	$_POST['subject'] = stripslashes($_POST['subject']);
}

if($_POST['new_term']) {
	$_POST['elements'][] = $_POST['new_element'];
	$_POST['ops'][] = $_POST['new_op'];
	$_POST['terms'][] = $_POST['new_term'];
}

if(substr($_POST['template'], -4) == ".tpl") {
	$_POST['template'] = substr($_POST['template'], 0, -4);
}

if($_POST['save']) {
	// save notification options that were selected

	if($_POST['setwho'] === "curuser") {
		$not_cons_curuser = 1;
		$not_cons_groupid = 0;
		$not_cons_uid = 0;
	} elseif($_POST['setwho'] === "groupid") {
		$not_cons_uid = 0;
		$not_cons_groupid = intval($_POST['gid']);
		$not_cons_curuser = 0;
	} else {
		$not_cons_uid = $uid;
		$not_cons_curuser = 0;
		$not_cons_groupid = 0;

		// since this is a user specific notification, set a subscription for it
		$notification_handler =& xoops_gethandler('notification');
		$notification_handler->subscribe('form', $fid, $_POST['setwhen'], '', $mid, $not_cons_uid);
		$thisnot = $notification_handler->getNotification($mid, 'form', $fid, $_POST['setwhen'], $not_cons_uid);

	}
	$not_cons_con = ($_POST['setfor'] == "all" OR count($_POST['terms']) == 0) ? "all" : serialize(array(serialize($_POST['elements']), serialize($_POST['ops']), serialize($_POST['terms'])));
	$sql = "INSERT INTO " . $xoopsDB->prefix("formulize_notification_conditions") . " (not_cons_fid, not_cons_event, not_cons_uid, not_cons_curuser, not_cons_groupid, not_cons_con, not_cons_template, not_cons_subject) VALUES (\"$fid\", \"".mysql_real_escape_string($_POST['setwhen'])."\", \"$not_cons_uid\", \"$not_cons_curuser\", \"$not_cons_groupid\", \"".mysql_real_escape_string($not_cons_con)."\", \"".mysql_real_escape_string($_POST['template'])."\", \"".mysql_real_escape_string($_POST['subject'])."\")";
	if(!$result = $xoopsDB->query($sql)) {
		exit("Error:  notification could not be saved.  SQL:<br>$sql<br>");
	}
	unset($_POST);
}

$deleted = handleDelete($uid, $fid, $mid); // returns 1 if a deletion was made, 0 if not.  

// Get all existing notifications 
// $nots will be an array sent back by the q function
$nots = getCurNots($fid, $canSetNots, $xoopsUser->getVar('uid'));

$noNots = count($nots) == 0 ? true : false;

// get the groups and group names
if($canSetNots) {
	$set_groups1 = $gperm_handler->getGroupIds("view_groupscope", $fid, $mid);
	$set_groups2 = $gperm_handler->getGroupIds("view_globalscope", $fid, $mid);
	$set_groups = array_merge($set_groups1, $set_groups2);
	$group_names = $member_handler->getGroups("", true);
	foreach($set_groups as $thisgroup) {
		$group_options[$thisgroup] = $group_names[$thisgroup]->getVar('name');
	}
	natsort($group_options);
} else {
	$set_groups = array();
}

// setup the options array for form elements
foreach($cols as $f=>$vs) {
	foreach($vs as $row=>$values) {
		if($values['ele_colhead'] != "") {
			$options[$values['ele_id']] = printSmart(trans($values['ele_colhead']), 20);
		} else {
			$options[$values['ele_id']] = printSmart(trans($values['ele_caption']), 20);
		}
	}
}

include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";

print "<HTML>";
print "<head>";
print "<title>" . _formulize_DE_SETNOT . "</title>\n";
print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"" . XOOPS_URL . "/xoops.css\" />\n";
$themecss = xoops_getcss();
//$themecss = substr($themecss, 0, -6);
//$themecss .= ".css";
print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"$themecss\" />\n";

print "</head>";
print "<body><center>"; 
print "<table width=100%><tr><td width=5%></td><td width=90%>";
$setnot = new xoopsThemeForm(_formulize_DE_SETNOT, 'setnot', XOOPS_URL."/modules/formulize/include/setnot.php?fid=$fid");

if(!isset($_POST['setwhen'])) {
	$_POST['setwhen'] = 'new_entry';
}

$notblurb = $canSetNots ? _formulize_DE_SETNOT_WHEN : _formulize_DE_SETNOT_TOME_WHEN;
$setwhen = new xoopsFormElementTray($notblurb, "<br />");
$setwhen_created = new xoopsFormRadio('', 'setwhen', $_POST['setwhen']);
$setwhen_created->addOption('new_entry', _formulize_DE_SETNOT_WHEN_NEW);
$setwhen_updated = new xoopsFormRadio('', 'setwhen', $_POST['setwhen']);
$setwhen_updated->addOption('update_entry', _formulize_DE_SETNOT_WHEN_UPDATE);
$setwhen_deleted = new xoopsFormRadio('', 'setwhen', $_POST['setwhen']);
$setwhen_deleted->addOption('delete_entry', _formulize_DE_SETNOT_WHEN_DELETE);
$setwhen->addElement($setwhen_created);
$setwhen->addElement($setwhen_updated);
$setwhen->addElement($setwhen_deleted);
$setnot->addElement($setwhen);

if($canSetNots) {

	if(!isset($_POST['setwho'])) {
		$_POST['setwho'] = $xoopsUser->getVar('uid');
	}

	$setwho = new xoopsFormElementTray(_formulize_DE_SETNOT_WHO, "<br />");
	$setwho_me = new xoopsFormRadio('', 'setwho', $_POST['setwho']);
	$setwho_me->addOption($uid, _formulize_DE_SETNOT_WHO_ME);
	$setwho_curuser = new xoopsFormRadio('', 'setwho', $_POST['setwho']);
	$setwho_curuser->addOption('curuser', _formulize_DE_SETNOT_WHO_CURUSER);
	$setwho_grouplist = new xoopsFormSelect('', 'gid', $_POST['gid'], 1);
	$setwho_grouplist->setExtra("onfocus=\"javascript:window.document.setnot.setwho[2].checked=true\"");
	$setwho_grouplist->addOptionArray($group_options);
	$grouplist = $setwho_grouplist->render();
	$setwho_group = new xoopsFormRadio('', 'setwho', $_POST['setwho']);
	$setwho_group->addOption('groupid', _formulize_DE_SETNOT_WHO_GROUP.$grouplist);
	$setwho->addElement($setwho_me);
	$setwho->addElement($setwho_curuser);
	$setwho->addElement($setwho_group);
	$setnot->addElement($setwho);
}

if(!isset($_POST['setfor'])) {
	$_POST['setfor'] = 'all';
}

$setfor = new xoopsFormElementTray(_formulize_DE_SETNOT_FOR, "<br />");
$setfor_all = new xoopsFormRadio('', 'setfor', $_POST['setfor']);
$setfor_all->addOption('all', _formulize_DE_SETNOT_FOR_ALL);

// process existing conditions...

if($_POST['addcon']) {
	for($i=0;$i<count($_POST['elements']);$i++) {
		$setnot->addElement(new xoopsFormHidden('elements[]', $_POST['elements'][$i]));
		$setnot->addElement(new xoopsFormHidden('ops[]', $_POST['ops'][$i]));
		$setnot->addElement(new xoopsFormHidden('terms[]', $_POST['terms'][$i]));
		$conditionlist .= $options[$_POST['elements'][$i]] . " " . $_POST['ops'][$i] . " " . $_POST['terms'][$i] . "<br />";
	} 
}

// setup the operator boxes...
$opterm = new xoopsFormElementTray('', "&nbsp;&nbsp;");
$element = new xoopsFormSelect('', 'new_element');
$element->setExtra("onfocus=\"javascript:window.document.setnot.setfor[1].checked=true\"");
$element->addOptionArray($options);
$op = new xoopsFormSelect('', 'new_op');
$ops['='] = "=";
$ops['NOT'] = "NOT";
$ops['>'] = ">";
$ops['<'] = "<";
$ops['>='] = ">=";
$ops['<='] = "<=";
$ops['LIKE'] = "LIKE";
$ops['NOT LIKE'] = "NOT LIKE";
$op->addOptionArray($ops);
$op->setExtra("onfocus=\"javascript:window.document.setnot.setfor[1].checked=true\"");
$term = new xoopsFormText('', 'new_term', 10, 255);
$term->setExtra("onfocus=\"javascript:window.document.setnot.setfor[1].checked=true\"");
$opterm->addElement($element);
$opterm->addElement($op);
$opterm->addElement($term);

$addcon = new xoopsFormButton('', 'addcon', _formulize_DE_SETNOT_ADDCON, 'submit');
$addcon->setExtra("onfocus=\"javascript:window.document.setnot.setfor[1].checked=true\"");

$conditionui = "<br />$conditionlist" . $opterm->render() . "<br />" . $addcon->render();

$setfor_con = new xoopsFormRadio('' , 'setfor', $_POST['setfor']);
$setfor_con->addOption('con', _formulize_DE_SETNOT_FOR_CON.$conditionui);
$setfor->addElement($setfor_all);
$setfor->addElement($setfor_con);
$setnot->addElement($setfor);

if($canSetNots) {
	$setnot->addElement(new xoopsFormText(_formulize_DE_SETNOT_TEMP, 'template', 50, 255, $_POST['template']));
	$setnot->addElement(new xoopsFormText(_formulize_DE_SETNOT_SUBJ, 'subject', 50, 255, $_POST['subject']));
}

$setnot->addElement(new xoopsFormButton('', 'save', _formulize_DE_SETNOT_SAVE, 'submit'));

print $setnot->render();

// if there are notifications to show
if(!$noNots) {

	print "<br />\n";

	$notlist = new xoopsThemeForm(_formulize_DE_NOTLIST, 'notlist', XOOPS_URL."/modules/formulize/include/setnot.php?fid=$fid");
	foreach($nots as $thisnot) {
		$text .= _formulize_DE_NOT_WHENTEXT;
		switch($thisnot['not_cons_event']) {
			case "new_entry":
				$text .= _formulize_DE_SETNOT_WHEN_NEW;
				break;
			case "update_entry":
				$text .= _formulize_DE_SETNOT_WHEN_UPDATE;
				break;
			case "delete_entry":
				$text .= _formulize_DE_SETNOT_WHEN_DELETE;
				break;
		}
		$text .= _formulize_DE_NOT_SENDTEXT;
		if($thisnot['not_cons_uid'] > 0) {
			$text .= _formulize_DE_SETNOT_WHO_ME;
		} elseif($thisnot['not_cons_curuser'] > 0) {
			$text .= _formulize_DE_SETNOT_WHO_CURUSER;
		} elseif($thisnot['not_cons_groupid'] > 0) {
			$text .= $group_names[$thisnot['not_cons_groupid']]->getVar('name');
		}
		if($thisnot['not_cons_con'] !== "all") {
			$cons = unserialize($thisnot['not_cons_con']);
			$elements = unserialize($cons[0]);
			$ops = unserialize($cons[1]);
			$terms = unserialize($cons[2]);
			$text .= _formulize_DE_NOT_CONTEXTIF;
			$start = 1;
			for($i=0;$i<count($elements);$i++) {
				if(!$start) {
					$text .= _formluize_DE_NOT_CONTEXTAND;
				}
				$text .= $options[$elements[$i]] . " " . $ops[$i] . " " . $terms[$i];
				$start =0;
			}
		}
		$text .= ".";

		if($thisnot['not_cons_template']) {
			$text .= "<br />" . _formulize_DE_NOT_TEMPTEXT . $thisnot['not_cons_template'] . "."; 
		}
		if($thisnot['not_cons_subject']) {
			$text .= "<br />" . _formulize_DE_NOT_SUBJTEXT . "'" . $thisnot['not_cons_subject'] . "'.";
		}
		
		$delbutton = new xoopsFormButton('', 'delete_'.$thisnot['not_cons_id'], _formulize_DELETE, 'submit');
		$anot = new xoopsFormLabel($delbutton->render(), $text);
		$notlist->addElement($anot);
		unset($anot);
		unset($delbutton);
		$text = "";
	}
	print $notlist->render();
	
}


print "</td><td width=5%></td></tr></table>";
print "</center></body>";
print "</HTML>";


// this function returns all the current notifications for a form, including notifications for other groups if the user has permission to set such things
// does not return other user's own personal notifications
function getCurNots($fid, $setNots=false, $uid) {
	global $xoopsDB;
	if($setNots) {
		$getNoGroups = " OR not_cons_uid=0";
	} else {
		$getNoGroups = "";
	}
	$notsq = q("SELECT not_cons_id, not_cons_event, not_cons_groupid, not_cons_uid, not_cons_curuser, not_cons_con, not_cons_template, not_cons_subject FROM " . $xoopsDB->prefix("formulize_notification_conditions") . " WHERE not_cons_fid=" . intval($fid) . " AND (not_cons_uid=" . intval($uid) . " $getNoGroups) ORDER BY not_cons_id");
	return $notsq;
}

// this function removes entries from the table where there is no corresponding notification subscription by a user
// not fully tested, and likely not to be implemented
// formulize can keep track of multiple notifications for each "event", since you can specify conditions on the events.  So if a user unsubs from new_entry, are we supposed to delete every single new entry condition that they created in Formulize?  Better to keep stuff active here, and if they unsub, they will get no nots, but as soon as they create a new not of that event here, then they will get all their old nots back.  They can delete nots from here if they don't want them any longer.
/*
function cleanupNots($fid, $uid, $mid) {
	global $xoopsDB;
	$notification_handler =& xoops_gethandler('notification');
	$criteria = new criteriaCompo();
	$criteria->add(new criteria('not_modid', $mid));
	$criteria->add(new criteria('not_itemid', $fid));
	$criteria->add(new criteria('not_uid', $uid));
	print $criteria->renderWhere();
	$user_nots = $notification_handler->getObjects($criteria, true); // true is use IDs as keys
	$sub_ids = array_keys($user_nots);
print_r($sub_ids);
print "<br>SELECT * FROM " . $xoopsDB->prefix("formulize_notification_conditions") . " WHERE uid=" . intval($uid);
	$user_fnots = q("SELECT * FROM " . $xoopsDB->prefix("formulize_notification_conditions") . " WHERE not_cons_uid=" . intval($uid));
	$start = 1;
print_r($user_fnots);
	foreach($user_fnots as $thisnot) {
print "<br>".$thisnot['not_cons_sub_id']."<br>";
		if(!in_array($thisnot['not_cons_sub_id'], $sub_ids)) {
			if($start) {
				$delq = " not_cons_id=" . $thisnot['not_cons_id'];
				$start =0;
			} else {
				$delq .= " OR not_cons_id=" . $thisnot['not_cons_id'];
			}
		}
	}
	if(!$start) { // something was missing
		$sql = "DELETE FROM " . $xoopsDB->prefix("formulize_notification_conditions") . " WHERE $delq";
		if(!$result = $xoopsDB->queryF($sql)) {
			exit("Error:  could not remove unsubscribed notification info.  SQL:<br>$sql</br>");
		}
	}	
}
*/
?>
