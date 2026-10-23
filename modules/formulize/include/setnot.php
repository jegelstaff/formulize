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
##  Author of this file: Freeform Solutions 				     ##
##  Project: Formulize                                                       ##
###############################################################################

// this file generates the set notifications popup

// delete notifications
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
			// check if the current user has any items left for a this event on this form, and if not, then unsub from that event
			$anyleft = q("SELECT * FROM " . $xoopsDB->prefix("formulize_notification_conditions") . " WHERE not_cons_uid=".intval($uid)." AND not_cons_fid = ".intval($fid)." AND not_cons_event=\"".$event[0]['not_cons_event']."\"");
			if(count((array) $anyleft) == 0) {
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
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/popup_functions.php';

	// Set some required variables
	$mid = getFormulizeModId();
	$fid="";
	if(!$fid = intval($_GET['fid'] ?? 0)) {
		$fid = intval($_POST['fid']);
	}

	$gperm_handler = &xoops_gethandler('groupperm');
	$member_handler =& xoops_gethandler('member');
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
	$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;

	if(!$scheck = security_check($fid, "", $uid, "", $groups, $mid, $gperm_handler)) {
		print formulize_noPermissionMessage();
		exit;
	}

// main body of page goes here...

// cleanup notifications -- remove entries from our table for specific uid notifications, where the user has unsubscribed
// not in use now, see note below with the function
//cleanupNots($fid, $uid, $mid);

$cols = getAllColList($fid, "", $groups); // notifications can only be set on a single form at a time

$canSetNots = $gperm_handler->checkRight("set_notifications_for_others", $fid, $groups, $mid);

// add latest element condition to existing conditions

if($_POST['new_term']) {
	$_POST['elements'][] = $_POST['new_element'];
	$_POST['ops'][] = $_POST['new_op'];
	$_POST['terms'][] = $_POST['new_term'];
}

// Whitelist the condition operators before they are stored (and before they are re-rendered into the
// hidden fields that get resubmitted).
if(isset($_POST['ops']) AND is_array($_POST['ops'])) {
	foreach($_POST['ops'] as $opKey => $opValue) {
		$cleanOp = is_string($opValue) ? formulize_conditionsCleanOps($opValue) : "";
		$_POST['ops'][$opKey] = ($cleanOp !== "") ? $cleanOp : "=";
	}
}

if(substr($_POST['template'], -4) == ".tpl") {
	$_POST['template'] = substr($_POST['template'], 0, -4);
}

if($_POST['save']) {
	// save notification options that were selected

	$not_cons_curuser = 0;
	$not_cons_groupid = 0;
	$not_cons_uid = 0;
	$not_cons_creator = 0;
	$not_cons_elementuids = 0;
	$not_cons_linkcreator = 0;
	$not_cons_elementemail = 0;
    $not_cons_arbitrary = '';

	if($_POST['setwho'] === "curuser") {
		$not_cons_curuser = 1;
	} elseif($_POST['setwho'] === "groupid" AND intval($_POST['gid']) > 0) {
		$not_cons_groupid = intval($_POST['gid']);
	} elseif($_POST['setwho'] === "creator") {
		$not_cons_creator = 1;
	} elseif($_POST['setwho'] === "elementuids" AND intval($_POST['ele_id']) > 0) {
		$not_cons_elementuids = intval($_POST['ele_id']);
	} elseif($_POST['setwho'] === "linkcreator" AND intval($_POST['lc_ele_id']) > 0) {
		$not_cons_linkcreator = intval($_POST['lc_ele_id']);
	} elseif($_POST['setwho'] === "elementemail" AND intval($_POST['email_ele_id']) > 0) {
		$not_cons_elementemail = intval($_POST['email_ele_id']);
    } elseif($_POST['setwho'] === "arbitrary" AND $_POST['arbitrary']) {
        $not_cons_arbitrary = strip_tags(htmlspecialchars($_POST['arbitrary'], ENT_QUOTES));
	} else {
		$not_cons_uid = $uid;
		// since this is a user specific notification, set a subscription for it
		$notification_handler =& xoops_gethandler('notification');
		$notification_handler->subscribe('form', $fid, $_POST['setwhen'], '', $mid, $not_cons_uid);
		$thisnot = $notification_handler->getNotification($mid, 'form', $fid, $_POST['setwhen'], $not_cons_uid);
	}
	$not_cons_con = ($_POST['setfor'] == "all" OR count((array) $_POST['terms']) == 0) ? "all" : serialize(array(serialize($_POST['elements']), serialize($_POST['ops']), serialize($_POST['terms'])));

	$template_filename = strstr($_POST['template'], ".tpl") ? str_replace(".tpl", "", $_POST['template']) : $_POST['template']; // strip .tpl out of the template name if it's present
	$sql = "INSERT INTO " . $xoopsDB->prefix("formulize_notification_conditions") . " (not_cons_fid, not_cons_event, not_cons_uid, not_cons_curuser, not_cons_groupid, not_cons_creator, not_cons_elementuids, not_cons_linkcreator, not_cons_elementemail, not_cons_arbitrary, not_cons_con, not_cons_template, not_cons_subject) VALUES (\"$fid\", '".formulize_db_escape($_POST['setwhen'])."', \"$not_cons_uid\", \"$not_cons_curuser\", \"$not_cons_groupid\", \"$not_cons_creator\", \"$not_cons_elementuids\", \"$not_cons_linkcreator\", \"$not_cons_elementemail\", '".formulize_db_escape($not_cons_arbitrary)."', '".formulize_db_escape($not_cons_con)."', '".formulize_db_escape($template_filename)."', '".formulize_db_escape($_POST['subject'])."')";
	if(!$result = $xoopsDB->query($sql)) {
		exit("Error:  notification could not be saved.  SQL:<br>$sql<br>");
	}
	unset($_POST);
}

$deleted = handleDelete($uid, $fid, $mid); // returns 1 if a deletion was made, 0 if not.

// Get all existing notifications
// $nots will be an array sent back by the q function
$nots = getCurNots($fid, $canSetNots, $xoopsUser->getVar('uid'));

$noNots = count((array) $nots) == 0 ? true : false;

// get the groups and group names, and the elements with fullnames or usernames in them
if($canSetNots) {
	$set_groups1 = $gperm_handler->getGroupIds("view_groupscope", $fid, $mid);
	$set_groups2 = $gperm_handler->getGroupIds("view_globalscope", $fid, $mid);
	$set_groups = array_merge((array)$set_groups1, (array)$set_groups2, array(XOOPS_GROUP_ADMIN)); // type casting required for php 5. Webmasters always have global scope via the checkRight override, but getGroupIds only reports explicitly granted groups.
	$group_names = $member_handler->getGroups("", true);
	$group_options = array();
	foreach($set_groups as $thisgroup) {
		$group_options[$thisgroup] = $group_names[$thisgroup]->getVar('name');
	}
	natcasesort($group_options);

	// gather the list of elements that have user names
	$sql = "SELECT ele_id, ele_caption, ele_colhead FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form = " . intval($fid) . " AND (ele_type = \"selectUsers\" OR ele_type = \"listboxUsers\" OR ele_type = \"autocompleteUsers\") AND (ele_value LIKE \"%{FULLNAMES}%\" OR ele_value LIKE \"%{USERNAMES}%\")";
	$element_options = buildNotOptionList($sql, "elementuid");

	// gather the list of elements that are linked selectboxes
	$sql = "SELECT ele_id, ele_caption, ele_colhead FROM " . $xoopsDB->prefix("formulize")  . " WHERE id_form = " . intval($fid) . " AND (ele_type = \"selectLinked\" OR ele_type = \"listboxLinked\" OR ele_type = \"autocompleteLinked\" OR ele_type = \"checkboxLinked\") AND ele_value LIKE \"%#*=:*%\"";
	$linkcreator_options = buildNotOptionList($sql, "linkcreator");

	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$formObject = $form_handler->get($fid);
	$formColheads = $formObject->getVar('elementColheads');
	$formCaptions = $formObject->getVar('elementCaptions');
	$elementemail_options = array();
	foreach($formObject->getVar('elementsWithData') as $elementWithDataId) {
		$elementemail_options[$elementWithDataId] = $formColheads[$elementWithDataId] ? $formColheads[$elementWithDataId] : printSmart($formCaptions[$elementWithDataId]);
	}
} else {
	$set_groups = array();
}

// setup the options array for form elements
// UID
$options['creation_uid'] = _formulize_DE_CALC_CREATOR;
$options['mod_uid'] = _formulize_DE_CALC_MODIFIER;
foreach($cols as $f=>$vs) {
	foreach($vs as $row=>$values) {
		if($values['ele_colhead'] != "") {
			$options[$values['ele_id']] = printSmart(trans($values['ele_colhead']), 20);
		} else {
			$options[$values['ele_id']] = printSmart(trans(strip_tags($values['ele_caption'])), 20);
		}
	}
}

formulize_popupHeader(_formulize_DE_SETNOT);

print "<form id='setnot' name='setnot' class='fz-card' method='post' action='" . XOOPS_URL."/modules/formulize/include/setnot.php?fid=$fid'>\n";
print "<h1 class='fz-card__title'>" . _formulize_DE_SETNOT . "</h1>\n";

if(!isset($_POST['setwhen'])) {
	$_POST['setwhen'] = 'new_entry';
}

$notblurb = $canSetNots ? _formulize_DE_SETNOT_WHEN : _formulize_DE_SETNOT_TOME_WHEN;
print formulize_popupField($notblurb, "<div class='fz-choice-group'>\n"
	. formulize_popupChoice('radio', 'setwhen', 'new_entry', _formulize_DE_SETNOT_WHEN_NEW, $_POST['setwhen'] == 'new_entry')
	. formulize_popupChoice('radio', 'setwhen', 'update_entry', _formulize_DE_SETNOT_WHEN_UPDATE, $_POST['setwhen'] == 'update_entry')
	. formulize_popupChoice('radio', 'setwhen', 'delete_entry', _formulize_DE_SETNOT_WHEN_DELETE, $_POST['setwhen'] == 'delete_entry')
	. "</div>\n");

if($canSetNots) {

	if(!isset($_POST['setwho'])) {
		$_POST['setwho'] = $xoopsUser->getVar('uid');
	}

	// each choice that needs more detail has the control for it beside it, and using that control picks the choice.
	// The script finds the choice by its position among the setwho radio buttons, so their order here matters.
	// creator and users identified in an element, these options added Feb 7 2008 by jwe
	$setwho = "<div class='fz-choice-group'>\n";
	$setwho .= formulize_popupChoice('radio', 'setwho', $uid, _formulize_DE_SETNOT_WHO_ME, $_POST['setwho'] == $uid);
	$setwho .= formulize_popupChoice('radio', 'setwho', 'curuser', _formulize_DE_SETNOT_WHO_CURUSER, $_POST['setwho'] == 'curuser');
	$setwho .= formulize_popupChoice('radio', 'setwho', 'creator', _formulize_DE_SETNOT_WHO_CREATOR, $_POST['setwho'] == 'creator');
	$setwho .= "<div class='fz-cluster'>" . formulize_popupChoice('radio', 'setwho', 'elementuids', _formulize_DE_SETNOT_WHO_ELEMENTUIDS, $_POST['setwho'] == 'elementuids')
		. formulize_popupSelect('ele_id', $element_options, $_POST['ele_id'], "aria-label='" . htmlspecialchars(trim(_formulize_DE_SETNOT_WHO_ELEMENTUIDS)) . "' onfocus=\"javascript:window.document.setnot.setwho[3].checked=true\"", 'fz-w-auto') . "</div>\n";
	$setwho .= "<div class='fz-cluster'>" . formulize_popupChoice('radio', 'setwho', 'linkcreator', _formulize_DE_SETNOT_WHO_LINKCREATOR, $_POST['setwho'] == 'linkcreator')
		. formulize_popupSelect('lc_ele_id', $linkcreator_options, $_POST['lc_ele_id'], "aria-label='" . htmlspecialchars(trim(_formulize_DE_SETNOT_WHO_LINKCREATOR)) . "' onfocus=\"javascript:window.document.setnot.setwho[4].checked=true\"", 'fz-w-auto') . "</div>\n";
	$setwho .= "<div class='fz-cluster'>" . formulize_popupChoice('radio', 'setwho', 'elementemail', _formulize_DE_SETNOT_WHO_ELEMENTEMAIL, $_POST['setwho'] == 'elementemail')
		. formulize_popupSelect('email_ele_id', $elementemail_options, $_POST['email_ele_id'], "aria-label='" . htmlspecialchars(trim(_formulize_DE_SETNOT_WHO_ELEMENTEMAIL)) . "' onfocus=\"javascript:window.document.setnot.setwho[5].checked=true\"", 'fz-w-auto') . "</div>\n";
	$setwho .= "<div class='fz-cluster'>" . formulize_popupChoice('radio', 'setwho', 'groupid', _formulize_DE_SETNOT_WHO_GROUP, $_POST['setwho'] == 'groupid')
		. formulize_popupSelect('gid', $group_options, $_POST['gid'], "aria-label='" . htmlspecialchars(trim(_formulize_DE_SETNOT_WHO_GROUP)) . "' onfocus=\"javascript:window.document.setnot.setwho[6].checked=true\"", 'fz-w-auto') . "</div>\n";
	$setwho .= "<div class='fz-cluster'>" . formulize_popupChoice('radio', 'setwho', 'arbitrary', _formulize_DE_SETNOT_WHO_ARBITRARY, $_POST['setwho'] == 'arbitrary')
		. "<input type='text' class='fz-input fz-w-auto' name='arbitrary' id='arbitrary' size='50' maxlength='255' value='" . htmlspecialchars($_POST['arbitrary']) . "' aria-label='" . htmlspecialchars(trim(_formulize_DE_SETNOT_WHO_ARBITRARY)) . "' onfocus=\"javascript:window.document.setnot.setwho[7].checked=true\" /></div>\n";
	$setwho .= "</div>\n";
	print formulize_popupField(_formulize_DE_SETNOT_WHO, $setwho);
}

if(!isset($_POST['setfor'])) {
	$_POST['setfor'] = 'all';
}

// process existing conditions...

$conditionlist = "";
if($_POST['addcon']) {
	for($i=0;$i<count((array) $_POST['elements']);$i++) {
		print "<input type='hidden' name='elements[]' value='" . htmlspecialchars($_POST['elements'][$i]) . "' />\n";
		print "<input type='hidden' name='ops[]' value='" . htmlspecialchars($_POST['ops'][$i]) . "' />\n";
		print "<input type='hidden' name='terms[]' value='" . htmlspecialchars($_POST['terms'][$i]) . "' />\n";
		$conditionlist .= "<p>".$options[$_POST['elements'][$i]] . " " . htmlspecialchars($_POST['ops'][$i]) . " " . htmlspecialchars($_POST['terms'][$i]) . "</p>";
	}
}

// setup the operator boxes...
$ops['='] = "=";
$ops['NOT'] = "NOT";
$ops['>'] = ">";
$ops['<'] = "<";
$ops['>='] = ">=";
$ops['<='] = "<=";
$ops['LIKE'] = "LIKE";
$ops['NOT LIKE'] = "NOT LIKE";
$ops = array_map('htmlspecialchars', $ops);
// using any of the condition controls picks the choice that they belong to
$pickConditions = "onfocus=\"javascript:window.document.setnot.setfor[1].checked=true\"";
$conditionui = "<div class='fz-stack fz-gap-1'>" . $conditionlist . "</div><div class='fz-cluster'>"
	. formulize_popupSelect('new_element', $options, array(), "aria-label='" . htmlspecialchars(_formulize_DE_SETNOT_FOR_CON) . "' $pickConditions", 'fz-w-auto')
	. formulize_popupSelect('new_op', $ops, array(), "aria-label='" . htmlspecialchars(_formulize_DE_SETNOT_FOR_CON) . "' $pickConditions", 'fz-w-auto')
	. "<input type='text' class='fz-input fz-w-auto' name='new_term' id='new_term' size='10' maxlength='255' value='' aria-label='" . htmlspecialchars(_formulize_DE_SETNOT_FOR_CON) . "' $pickConditions />"
	. "<input type='submit' class='fz-btn' name='addcon' id='addcon' value='" . _formulize_DE_SETNOT_ADDCON . "' $pickConditions />"
	. "</div>\n";

print formulize_popupField(_formulize_DE_SETNOT_FOR, "<div class='fz-choice-group'>\n"
	. formulize_popupChoice('radio', 'setfor', 'all', _formulize_DE_SETNOT_FOR_ALL, $_POST['setfor'] == 'all')
	. formulize_popupChoice('radio', 'setfor', 'con', _formulize_DE_SETNOT_FOR_CON, $_POST['setfor'] == 'con')
	. "</div>\n"
	. $conditionui);

if($canSetNots) {
	print formulize_popupField(_formulize_DE_SETNOT_TEMP, "<input type='text' class='fz-input' name='template' id='template' size='50' maxlength='255' value='" . htmlspecialchars($_POST['template']) . "' />", _formulize_DE_SETNOT_TEMP_DESC, 'template');
	print formulize_popupField(_formulize_DE_SETNOT_SUBJ, "<input type='text' class='fz-input' name='subject' id='subject' size='50' maxlength='255' value='" . htmlspecialchars($_POST['subject']) . "' />", '', 'subject');
}

print "<div class='fz-cluster'><input type='submit' class='fz-btn fz-btn--primary' name='save' id='save' value='" . _formulize_DE_SETNOT_SAVE . "' /></div>\n";

print "</form>\n";

// if there are notifications to show
if(!$noNots) {

	print "<form id='notlist' name='notlist' class='fz-card' method='post' action='" . XOOPS_URL."/modules/formulize/include/setnot.php?fid=$fid'>\n";
	print "<h2 class='fz-card__title'>" . _formulize_DE_NOTLIST . "</h2>\n";
	$text = "";
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
		} elseif($thisnot['not_cons_creator'] > 0) {
			$text .= _formulize_DE_SETNOT_WHO_CREATOR;
		} elseif($thisnot['not_cons_elementuids'] > 0) {
			$text .= _formulize_DE_SETNOT_WHO_ELEMENTUIDS;
			// figure out the element name
			if(!$element_handler) { $element_handler = xoops_getmodulehandler('elements', 'formulize'); }
			$elementObject = $element_handler->get($thisnot['not_cons_elementuids']);
			$text .= $elementObject->getVar('ele_colhead') ? printSmart(trans($elementObject->getVar('ele_colhead'))) : printSmart(trans($elementObject->getVar('ele_caption')));
		} elseif($thisnot['not_cons_linkcreator'] > 0) {
			$text .= _formulize_DE_SETNOT_WHO_LINKCREATOR;
			// figure out the element name
			if(!$element_handler) { $element_handler = xoops_getmodulehandler('elements', 'formulize'); }
			$elementObject = $element_handler->get($thisnot['not_cons_linkcreator']);
			$text .= $elementObject->getVar('ele_colhead') ? printSmart(trans($elementObject->getVar('ele_colhead'))) : printSmart(trans($elementObject->getVar('ele_caption')));
		} elseif($thisnot['not_cons_elementemail'] > 0) {
			$text .= _formulize_DE_SETNOT_WHO_ELEMENTEMAIL;
			// figure out the element name
			if(!$element_handler) { $element_handler = xoops_getmodulehandler('elements', 'formulize'); }
			$elementObject = $element_handler->get($thisnot['not_cons_elementemail']);
			$text .= $elementObject->getVar('ele_colhead') ? printSmart(trans($elementObject->getVar('ele_colhead'))) : printSmart(trans($elementObject->getVar('ele_caption')));
		} elseif($thisnot['not_cons_arbitrary']) {
            $text .= _formulize_DE_SETNOT_WHO_ARBITRARY.$thisnot['not_cons_arbitrary'];
        }

		if($thisnot['not_cons_con'] !== "all") {
			$cons = unserialize($thisnot['not_cons_con']);
			$elements = unserialize($cons[0]);
			$ops = unserialize($cons[1]);
			$terms = unserialize($cons[2]);
			$text .=", ". _formulize_DE_NOT_CONTEXTIF;
			$start = 1;
			for($i=0;$i<count((array) $elements);$i++) {
				if(!$start) {
					$text .= _formluize_DE_NOT_CONTEXTAND;
				}
				$text .= $options[$elements[$i]] . " " . htmlspecialchars($ops[$i]) . " " . htmlspecialchars($terms[$i]);
				$start =0;
			}
		}
		$text .= ".";

		if($thisnot['not_cons_template']) {
			$text .= "<br />" . _formulize_DE_NOT_TEMPTEXT . $thisnot['not_cons_template'] . ".";
		}
		if($thisnot['not_cons_subject']) {
			$text .= "<br />" . _formulize_DE_NOT_SUBJTEXT . "'" . str_replace(array("[","]"), " ", $thisnot['not_cons_subject']) . "'.";
		}

		// each notification: what it is, and the button that deletes it
		print "<div class='fz-flex fz-items-start fz-gap-4 fz-border-t fz-pt-3'>
		<p class='fz-flex-1 fz-min-w-0 fz-m-0'>" . $text . "</p>
		<input type='submit' class='fz-btn fz-btn--danger fz-btn--sm fz-flex-none' name='delete_" . intval($thisnot['not_cons_id']) . "' id='delete_" . intval($thisnot['not_cons_id']) . "' value='" . _formulize_DELETE . "' />
		</div>\n";
		$text = "";
	}
	print "</form>\n";

}

formulize_popupFooter();


// this function returns all the current notifications for a form, including notifications for other groups if the user has permission to set such things
// does not return other user's own personal notifications
function getCurNots($fid, $setNots, $uid) {
	global $xoopsDB;
	if($setNots) {
		$getNoGroups = " OR not_cons_uid=0";
	} else {
		$getNoGroups = "";
	}
	$notsq = q("SELECT * FROM " . $xoopsDB->prefix("formulize_notification_conditions") . " WHERE not_cons_fid=" . intval($fid) . " AND (not_cons_uid=" . intval($uid) . " $getNoGroups) ORDER BY not_cons_id");
	return $notsq;
}

// THIS FUNCTION TAKES AN SQL STATEMENT THAT GETS ELE_ID, ELE_CAPTION AND ELE_COLHEAD FROM THE FORMULIZE TABLE, AND TURNS IT INTO AN ARRAY OF OPTIONS SUITABLE FOR DISPLAYING THE SELECTED ELEMENTS IN A DROPDOWN LIST
function buildNotOptionList($sql, $type) {
	global $xoopsDB;
	$res = $xoopsDB->query($sql);
	$options = array();
	$noOptionText = $type == "elementuid" ? _formulize_DE_SETNOT_NOELEMENTOPTIONS : _formulize_DE_SETNOT_NOLINKCREATOROPTIONS;
	if($res) {
		while($array = $xoopsDB->fetchArray($res)) {
			$options[$array['ele_id']] = $array['ele_colhead'] ? printSmart(trans($array['ele_colhead'])) : printSmart(trans($array['ele_caption']));
		}
		if(count((array) $options) == 0) {
			$options[0] = $noOptionText;
		}
	} else {
		$options[0] = $noOptionText;
	}
	return $options;
}

