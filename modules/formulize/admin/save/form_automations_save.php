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

// this file handles the buttons on the Automations tab of a form: turning automations on and off, cloning, deleting, converting
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

include_once XOOPS_ROOT_PATH.'/modules/formulize/include/automations_admin.php';
$automations_handler = xoops_getmodulehandler('automations', 'formulize');
$reload = false;

// only act on automations that belong to this form
$automationOfThisForm = function($automationId) use ($automations_handler, $fid) {
	$automation = $automations_handler->get(intval($automationId));
	return ($automation AND $automation['fid'] == $fid) ? $automation : false;
};

if($_POST['automations_toggle'] AND $automation = $automationOfThisForm($_POST['automations_toggle'])) {
	$automation['active'] = $automation['active'] ? 0 : 1;
	$automations_handler->insert($automation);
	$reload = true;
}

if($_POST['automations_clone'] AND $automation = $automationOfThisForm($_POST['automations_clone'])) {
	if(!$automations_handler->cloneAutomation($automation['automation_id'])) {
		print "Error: could not clone the automation.";
	}
	$reload = true;
}

if($_POST['automations_delete'] AND $automation = $automationOfThisForm($_POST['automations_delete'])) {
	if(!$automations_handler->delete($automation['automation_id'])) {
		print "Error: could not delete the automation.";
	}
	$reload = true;
}

if($_POST['automations_convert_notification']) {
	if(!formulize_convertPopupNotificationToAutomation(intval($_POST['automations_convert_notification']), $fid)) {
		print "Error: could not convert the notification into an automation.";
	}
	$reload = true;
}

if($_POST['automations_send_pending'] OR $_POST['automations_retry_item']) {
	if($_POST['automations_retry_item']) {
		$outboxIds = array(intval($_POST['automations_retry_item']));
	} else {
		$outboxIds = array();
		if($res = $xoopsDB->query("SELECT outbox_id FROM ".$xoopsDB->prefix('formulize_automation_outbox')." WHERE fid = $fid AND status IN ('pending', 'sending') ORDER BY outbox_id LIMIT 50")) {
			while($row = $xoopsDB->fetchRow($res)) {
				$outboxIds[] = intval($row[0]);
			}
		}
	}
	// only deliveries for this form
	$outboxIds = array_filter($outboxIds, function($outboxId) use ($xoopsDB, $fid) {
		$res = $xoopsDB->query("SELECT fid FROM ".$xoopsDB->prefix('formulize_automation_outbox')." WHERE outbox_id = ".intval($outboxId));
		$row = $res ? $xoopsDB->fetchRow($res) : false;
		return $row AND intval($row[0]) == $fid;
	});
	if($outboxIds) {
		formulize_automationProcessOutbox(count($outboxIds), $outboxIds);
	}
	$reload = true;
}

if($_POST['automations_move_procedure']) {
	// this runs after the Procedures tab's own save in the same round, so its textarea can't put the code back
	if(!$movedAutomationId = formulize_moveProcedureIntoAutomation($formObject, strval($_POST['automations_move_procedure']))) {
		print "Error: could not move the procedure's code into an automation.";
	} else {
		print "/* eval */ window.location = '".XOOPS_URL."/modules/formulize/admin/ui.php?page=automation&aid=$aid&fid=$fid&automation_id=$movedAutomationId';";
		return;
	}
}

if($_POST['automations_goto']) {
	$automationId = $_POST['automations_goto'] === 'new' ? 'new' : intval($_POST['automations_goto']);
	$startingPoint = preg_replace('/[^a-z_]/', '', strval($_POST['automations_starting_point'] ?? ''));
	print "/* eval */ window.location = '".XOOPS_URL."/modules/formulize/admin/ui.php?page=automation&aid=$aid&fid=$fid&automation_id=$automationId".($startingPoint ? "&starting_point=$startingPoint" : "")."';";
} elseif($reload) {
	print "/* eval */ reloadWithScrollPosition();";
}
