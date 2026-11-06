<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) Formulize Project                        ##
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
##  Author of this file: Formulize Project                                   ##
##  Project: Formulize                                                       ##
###############################################################################

// this file handles the buttons on the Rules tab of a form: turning rules on and off, cloning, deleting, converting
// notifications from the notifications popup, and sending webhooks

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
	return;
}

$fid = intval($_POST['formulize_admin_key']);
$aid = intval($_POST['formulize_admin_aid']);

$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($fid);
if(!$formObject OR $formObject->getVar('lockedform')) {
	return;
}
if(!$gperm_handler->checkRight("edit_form", $fid, $groups, $mid)) {
	return;
}

include_once XOOPS_ROOT_PATH.'/modules/formulize/include/rules_admin.php';
$rules_handler = xoops_getmodulehandler('rules', 'formulize');
$reload = false;

// only act on rules that belong to this form
$ruleOfThisForm = function($ruleId) use ($rules_handler, $fid) {
	$rule = $rules_handler->get(intval($ruleId));
	return ($rule AND $rule['fid'] == $fid) ? $rule : false;
};

if($_POST['rules_toggle'] AND $rule = $ruleOfThisForm($_POST['rules_toggle'])) {
	$rule['active'] = $rule['active'] ? 0 : 1;
	$rules_handler->insert($rule);
	$reload = true;
}

if($_POST['rules_clone'] AND $rule = $ruleOfThisForm($_POST['rules_clone'])) {
	if(!$rules_handler->cloneRule($rule['rule_id'])) {
		print "Error: could not clone the rule.";
	}
	$reload = true;
}

if($_POST['rules_delete'] AND $rule = $ruleOfThisForm($_POST['rules_delete'])) {
	if(!$rules_handler->delete($rule['rule_id'])) {
		print "Error: could not delete the rule.";
	}
	$reload = true;
}

if($_POST['rules_convert_notification']) {
	if(!formulize_convertPopupNotificationToRule(intval($_POST['rules_convert_notification']), $fid)) {
		print "Error: could not convert the notification into a rule.";
	}
	$reload = true;
}

if($_POST['rules_send_pending'] OR $_POST['rules_retry_item']) {
	if($_POST['rules_retry_item']) {
		$outboxIds = array(intval($_POST['rules_retry_item']));
	} else {
		$outboxIds = array();
		if($res = $xoopsDB->query("SELECT outbox_id FROM ".$xoopsDB->prefix('formulize_rule_outbox')." WHERE fid = $fid AND status IN ('pending', 'sending') ORDER BY outbox_id LIMIT 50")) {
			while($row = $xoopsDB->fetchRow($res)) {
				$outboxIds[] = intval($row[0]);
			}
		}
	}
	// only deliveries for this form
	$outboxIds = array_filter($outboxIds, function($outboxId) use ($xoopsDB, $fid) {
		$res = $xoopsDB->query("SELECT fid FROM ".$xoopsDB->prefix('formulize_rule_outbox')." WHERE outbox_id = ".intval($outboxId));
		$row = $res ? $xoopsDB->fetchRow($res) : false;
		return $row AND intval($row[0]) == $fid;
	});
	if($outboxIds) {
		formulize_ruleProcessOutbox(count($outboxIds), $outboxIds);
	}
	$reload = true;
}

if($_POST['rules_move_procedure']) {
	// this runs after the Procedures tab's own save in the same round, so its textarea can't put the code back
	if(!$movedRuleId = formulize_moveProcedureIntoRule($formObject, strval($_POST['rules_move_procedure']))) {
		print "Error: could not move the procedure's code into a rule.";
	} else {
		print "/* eval */ window.location = '".XOOPS_URL."/modules/formulize/admin/ui.php?page=rule&aid=$aid&fid=$fid&rule_id=$movedRuleId';";
		return;
	}
}

if($_POST['rules_goto']) {
	$ruleId = $_POST['rules_goto'] === 'new' ? 'new' : intval($_POST['rules_goto']);
	$startingPoint = preg_replace('/[^a-z_]/', '', strval($_POST['rules_starting_point'] ?? ''));
	print "/* eval */ window.location = '".XOOPS_URL."/modules/formulize/admin/ui.php?page=rule&aid=$aid&fid=$fid&rule_id=$ruleId".($startingPoint ? "&starting_point=$startingPoint" : "")."';";
} elseif($reload) {
	print "/* eval */ reloadWithScrollPosition();";
}
