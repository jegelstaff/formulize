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

// Helpers for the admin UI of automations: the Automations tab of a form (admin/form.php) and the automation editor (admin/automation.php)

if (!defined("XOOPS_ROOT_PATH")) {
	die("XOOPS root path not defined");
}

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/automations.php";

/**
 * The elements with data in a form, for picking in the admin UI
 * @param int $fid The form
 * @param string $key 'handle' or 'id', what the keys of the returned array should be
 * @return array Key => label
 */
function formulize_automationElementOptions($fid, $key = 'handle') {
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$options = array();
	if (!$fid OR !$formObject = $form_handler->get($fid, true)) {
		return $options;
	}
	$captions = $formObject->getVar('elementCaptions');
	$colheads = $formObject->getVar('elementColheads');
	$handles = $formObject->getVar('elementHandles');
	foreach ((array) $formObject->getVar('elementsWithData') as $elementId) {
		$label = $colheads[$elementId] ? $colheads[$elementId] : $captions[$elementId];
		$label = printSmart(trans(strip_tags(strval($label))), 60);
		$options[$key == 'id' ? $elementId : $handles[$elementId]] = $label . " (" . $handles[$elementId] . ")";
	}
	return $options;
}

/**
 * The elements in a form of particular types, for the notification recipients options
 * @param int $fid The form
 * @param string $kind 'users' for elements that list users, 'linked' for linked elements
 * @return array Element id => label
 */
function formulize_automationElementOptionsOfKind($fid, $kind) {
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
	$options = array();
	if (!$fid OR !$formObject = $form_handler->get($fid, true)) {
		return $options;
	}
	$allOptions = formulize_automationElementOptions($fid, 'id');
	foreach ($formObject->getVar('elementTypes') as $elementId => $type) {
		if (!isset($allOptions[$elementId])) {
			continue;
		}
		if ($kind == 'users' AND in_array($type, array('selectUsers', 'listboxUsers', 'autocompleteUsers', 'checkboxUsers'))) {
			$options[$elementId] = $allOptions[$elementId];
		} elseif ($kind == 'linked' AND $elementObject = $element_handler->get($elementId) AND $elementObject->isLinked) {
			$options[$elementId] = $allOptions[$elementId];
		}
	}
	return $options;
}

/**
 * The custom buttons on a form's list screens, for picking as a trigger
 * @param int $fid The form
 * @return array "sid|handle" => label
 */
function formulize_automationButtonOptions($fid) {
	$screen_handler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
	$options = array();
	$screens = xoops_getmodulehandler('screen', 'formulize')->getObjects(new Criteria('type', 'listOfEntries'), $fid);
	foreach ($screens as $screen) {
		$listScreen = $screen_handler->get($screen->getVar('sid'));
		foreach ((array) $listScreen->getVar('customactions') as $button) {
			if (!is_array($button) OR empty($button['handle'])) {
				continue;
			}
			$options[$screen->getVar('sid') . '|' . $button['handle']] = trans($screen->getVar('title')) . ": " . trans(strval($button['buttontext'] ?? $button['handle'])) . " (" . $button['handle'] . ")";
		}
	}
	return $options;
}

/**
 * The label of an element, for summaries
 * @param mixed $elementIdOrHandle
 * @return string
 */
function formulize_automationElementLabel($elementIdOrHandle) {
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
	if ($elementObject = $element_handler->get($elementIdOrHandle)) {
		$label = $elementObject->getVar('ele_colhead') ? $elementObject->getVar('ele_colhead') : $elementObject->getVar('ele_caption');
		return printSmart(trans(strip_tags(strval($label))), 40);
	}
	return strval($elementIdOrHandle) . " (missing)";
}

/**
 * Describe conditions in words
 * @param array $conditions
 * @param int $fid The form the conditions' elements are in
 * @return string
 */
function formulize_automationConditionsSummary($conditions, $fid) {
	if (empty($conditions[0])) {
		return "All entries";
	}
	$all = array();
	$oom = array();
	foreach ((array) $conditions[0] as $i => $element) {
		$text = (isMetaDataField($element) ? $element : formulize_automationElementLabel($element)) . " " . strval($conditions[1][$i] ?? '=') . " " . strval($conditions[2][$i] ?? '');
		if (($conditions[3][$i] ?? 'all') == 'oom') {
			$oom[] = $text;
		} else {
			$all[] = $text;
		}
	}
	$parts = array();
	if ($all) {
		$parts[] = implode(" and ", $all);
	}
	if ($oom) {
		$parts[] = "at least one of: " . implode(", ", $oom);
	}
	return "Entries where " . implode("; and ", $parts);
}

/**
 * Describe an automation in words, for the list on the Automations tab
 * @param array $automation
 * @return array 'trigger', 'conditions' and 'steps' (a list)
 */
function formulize_automationSummary($automation) {
	$triggerTypes = formulizeAutomationsHandler::triggerTypes();
	$trigger = $triggerTypes[$automation['trigger_type']] ?? $automation['trigger_type'];
	$watch = array_filter((array) ($automation['trigger_settings']['watch'] ?? array()));
	if ($watch AND in_array($automation['trigger_type'], array('update_entry', 'save_entry'))) {
		$trigger .= ", only when " . implode(" or ", array_map('formulize_automationElementLabel', $watch)) . " changes";
	}
	if ($automation['trigger_type'] == 'button_click') {
		$buttons = formulize_automationButtonOptions($automation['fid']);
		$key = intval($automation['trigger_settings']['sid'] ?? 0) . '|' . strval($automation['trigger_settings']['button'] ?? '');
		$trigger .= ": " . ($buttons[$key] ?? "no button chosen yet");
	}
	$steps = array();
	foreach ((array) $automation['steps'] as $step) {
		$outputName = formulize_automationOutputName($step);
		$steps[] = formulize_automationStepSummary($step, $automation['fid']) . ($outputName !== '' ? " → {" . $outputName . "}" : "");
	}
	if ($automation['trigger_type'] == 'before_save') {
		$automation['conditions'] = array(); // before save automations have no conditions
	}
	if (!$steps) {
		$steps[] = "Nothing yet";
	}
	return array(
		'trigger' => $trigger,
		'conditions' => formulize_automationConditionsSummary($automation['conditions'], $automation['fid']),
		'steps' => $steps,
	);
}

/**
 * Describe a step in words
 * @param array $step
 * @param int $fid The form of the automation
 * @return string
 */
function formulize_automationStepSummary($step, $fid) {
	switch ($step['type'] ?? '') {
		case 'notify':
			$recipients = (array) ($step['recipients'] ?? array());
			$who = array();
			if (!empty($recipients['creator'])) { $who[] = "the entry's owner"; }
			if (!empty($recipients['curuser'])) { $who[] = "the user who did it"; }
			if (!empty($recipients['groups'])) {
				$member_handler = xoops_gethandler('member');
				foreach ((array) $recipients['groups'] as $gid) {
					if ($group = $member_handler->getGroup(intval($gid))) {
						$who[] = "group '" . $group->getVar('name') . "'";
					}
				}
			}
			if (!empty($recipients['elementuids'])) { $who[] = "users in " . formulize_automationElementLabel($recipients['elementuids']); }
			if (!empty($recipients['linkcreator'])) { $who[] = "creators of entries selected in " . formulize_automationElementLabel($recipients['linkcreator']); }
			if (!empty($recipients['elementemail'])) { $who[] = "addresses in " . formulize_automationElementLabel($recipients['elementemail']); }
			if (!empty($recipients['emails'])) { $who[] = strval($recipients['emails']); }
			$subject = trim(strval($step['subject'] ?? ''));
			return "Notify " . ($who ? implode(", ", $who) : "nobody yet") . ($subject !== '' ? ": \"$subject\"" : "");
		case 'update_entries':
			$count = count((array) ($step['values'] ?? array()));
			if (($step['target'] ?? 'this') == 'this') {
				return "Update this entry ($count value" . ($count == 1 ? "" : "s") . ")";
			}
			return "Update entries in " . formulize_automationFormTitle(intval($step['target_fid'] ?? 0)) . " where " . lcfirst(substr(formulize_automationConditionsSummary($step['target_conditions'] ?? array(), $step['target_fid'] ?? 0), 15)) . " ($count value" . ($count == 1 ? "" : "s") . ")";
		case 'create_entry':
			return "Create an entry in " . formulize_automationFormTitle(intval($step['target_fid'] ?? 0));
		case 'webhook':
			return "Send a webhook to " . (trim(strval($step['url'] ?? '')) ?: "(no URL yet)") . (!empty($step['secret']) ? ", signed" : "");
		case 'ai':
			$prompt = trim(preg_replace('/\s+/', ' ', strval($step['prompt'] ?? '')));
			return "Ask AI: \"" . (mb_strlen($prompt) > 80 ? mb_substr($prompt, 0, 80) . "..." : $prompt) . "\"" . (!empty($step['json']) ? " (JSON reply)" : "");
		case 'code':
			$lines = count(array_filter(explode("\n", trim(removeOpeningPHPTag(strval($step['code'] ?? '')))), 'strlen'));
			return "Run PHP code ($lines line" . ($lines == 1 ? "" : "s") . (($step['values_as'] ?? 'stored') == 'readable' ? ", values as people see them" : "") . ")";
	}
	return "Unknown step";
}

/**
 * Describe a notification set up in the notifications popup, in words
 * @param array $row The row from formulize_notification_conditions
 * @return string
 */
function formulize_automationPopupNotificationSummary($row) {
	$events = array('new_entry' => 'a new entry is created', 'update_entry' => 'an entry is updated', 'delete_entry' => 'an entry is deleted');
	$who = '';
	if ($row['not_cons_curuser']) {
		$who = "the user who did it";
	} elseif ($row['not_cons_groupid']) {
		$group = xoops_gethandler('member')->getGroup(intval($row['not_cons_groupid']));
		$who = "group '" . ($group ? $group->getVar('name') : $row['not_cons_groupid']) . "'";
	} elseif ($row['not_cons_creator']) {
		$who = "the entry's owner";
	} elseif ($row['not_cons_elementuids']) {
		$who = "users in " . formulize_automationElementLabel($row['not_cons_elementuids']);
	} elseif ($row['not_cons_linkcreator']) {
		$who = "creators of entries selected in " . formulize_automationElementLabel($row['not_cons_linkcreator']);
	} elseif ($row['not_cons_elementemail']) {
		$who = "addresses in " . formulize_automationElementLabel($row['not_cons_elementemail']);
	} elseif ($row['not_cons_arbitrary']) {
		$who = htmlspecialchars_decode($row['not_cons_arbitrary'], ENT_QUOTES);
	}
	$text = "When " . ($events[$row['not_cons_event']] ?? $row['not_cons_event']) . ", notify " . $who;
	if ($row['not_cons_con'] !== 'all' AND $cons = unserialize($row['not_cons_con'])) {
		$elements = (array) unserialize($cons[0]);
		$ops = (array) unserialize($cons[1]);
		$terms = (array) unserialize($cons[2]);
		$parts = array();
		foreach (array_values($elements) as $i => $element) {
			$parts[] = (isMetaDataField($element) ? $element : formulize_automationElementLabel($element)) . " " . ($ops[$i] ?? '=') . " " . ($terms[$i] ?? '');
		}
		$text .= ", if " . implode(" and ", $parts);
	}
	if ($row['not_cons_subject']) {
		$text .= ", subject \"" . $row['not_cons_subject'] . "\"";
	}
	if ($row['not_cons_template']) {
		$text .= ", template " . $row['not_cons_template'];
	}
	return $text;
}

/**
 * Gather everything the Automations tab of a form shows
 * @param int $fid The form
 * @return array
 */
function formulize_automationsTabContent($fid) {
	global $xoopsDB;
	$handler = xoops_getmodulehandler('automations', 'formulize');
	$content = array('automations' => array(), 'popup_notifications' => array(), 'personal_subscriptions' => 0, 'outbox_items' => array(), 'pending_outbox_items' => 0);
	foreach ($handler->getAutomations($fid) as $automation) {
		$summary = formulize_automationSummary($automation);
		$lastRun = '';
		if ($res = $xoopsDB->query("SELECT MAX(created_at) FROM " . $xoopsDB->prefix('formulize_automation_log') . " WHERE automation_id = " . intval($automation['automation_id']))) {
			$row = $xoopsDB->fetchRow($res);
			$lastRun = $row[0] ? $row[0] : '';
		}
		$content['automations'][] = array(
			'automation_id' => $automation['automation_id'],
			'name' => $automation['name'],
			'active' => $automation['active'],
			'trigger' => $summary['trigger'],
			'conditions' => $summary['conditions'],
			'steps' => $summary['steps'],
			'last_run' => $lastRun,
		);
	}
	$table = $xoopsDB->prefix('formulize_notification_conditions');
	if ($res = $xoopsDB->query("SELECT * FROM $table WHERE not_cons_fid = " . intval($fid) . " ORDER BY not_cons_id")) {
		while ($row = $xoopsDB->fetchArray($res)) {
			if ($row['not_cons_uid'] > 0) {
				$content['personal_subscriptions']++;
			} else {
				$content['popup_notifications'][] = array('id' => $row['not_cons_id'], 'summary' => formulize_automationPopupNotificationSummary($row));
			}
		}
	}
	$outboxTable = $xoopsDB->prefix('formulize_automation_outbox');
	if ($res = $xoopsDB->query("SELECT o.*, a.name AS automation_name FROM $outboxTable AS o LEFT JOIN " . $xoopsDB->prefix('formulize_automations') . " AS a ON a.automation_id = o.automation_id WHERE o.fid = " . intval($fid) . " ORDER BY o.outbox_id DESC LIMIT 25")) {
		while ($row = $xoopsDB->fetchArray($res)) {
			unset($row['payload']);
			$content['outbox_items'][] = $row;
		}
	}
	if ($res = $xoopsDB->query("SELECT COUNT(*) FROM $outboxTable WHERE fid = " . intval($fid) . " AND status IN ('pending', 'sending')")) {
		$row = $xoopsDB->fetchRow($res);
		$content['pending_outbox_items'] = intval($row[0]);
	}

	// starting points for new automations, grouped
	$content['ai_automation_enabled'] = formulize_aiInAutomationsEnabled();
	$content['starting_point_groups'] = array();
	foreach (formulizeAutomationsHandler::startingPoints($fid) as $key => $startingPoint) {
		$startingPoint['key'] = $key;
		$startingPoint['unavailable'] = (($startingPoint['needs'] ?? '') == 'ai' AND !$content['ai_automation_enabled']);
		$content['starting_point_groups'][$startingPoint['group']][] = $startingPoint;
	}

	// code in the form's procedures, which can be moved into automations
	$content['procedures'] = array();
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	if ($formObject = $form_handler->get($fid)) {
		foreach (formulize_automationProcedureTriggers() as $procedure => $details) {
			$code = trim(removeOpeningPHPTag(strval($formObject->getVar($procedure))));
			if ($code !== '') {
				$content['procedures'][] = array('key' => $procedure, 'label' => $details['label'], 'lines' => count(array_filter(explode("\n", $code), 'strlen')));
			}
		}
	}
	return $content;
}

/**
 * The procedures (on the Procedures tab) that can be moved into automations, and the trigger each becomes
 * @return array Procedure => 'label', 'trigger_type'
 */
function formulize_automationProcedureTriggers() {
	return array(
		'on_before_save' => array('label' => 'Before Saving', 'trigger_type' => 'before_save'),
		'on_after_save' => array('label' => 'After Saving', 'trigger_type' => 'save_entry'),
		'on_delete' => array('label' => 'On Delete', 'trigger_type' => 'delete_entry'),
	);
}

/**
 * Move the code in one of a form's procedures into a new automation, and empty the procedure, so the code runs once
 * @param object $formObject The form
 * @param string $procedure on_before_save, on_after_save or on_delete
 * @return int|false The new automation's id
 */
function formulize_moveProcedureIntoAutomation($formObject, $procedure) {
	$procedures = formulize_automationProcedureTriggers();
	if (!isset($procedures[$procedure])) {
		return false;
	}
	$code = strval($formObject->getVar($procedure));
	if (trim(removeOpeningPHPTag($code)) === '') {
		return false;
	}
	$handler = xoops_getmodulehandler('automations', 'formulize');
	$automation = $handler->create($formObject->getVar('id_form'));
	$automation['name'] = $procedures[$procedure]['label'] . ' code (moved from the Procedures tab)';
	$automation['trigger_type'] = $procedures[$procedure]['trigger_type'];
	$automation['steps'] = array(array('type' => 'code', 'code' => ensureOpeningPHPTag($code)));
	if (!$automationId = $handler->insert($automation)) {
		return false;
	}
	$formObject->setVar($procedure, '');
	xoops_getmodulehandler('forms', 'formulize')->insert($formObject);
	return $automationId;
}

/**
 * The tags that can go in a step's text, for the "Insert" menu beside each step: values from the entry, the outputs of
 * earlier steps, and the general tags
 * @param int $fid The form of the automation
 * @param array $earlierOutputs Output names of the steps before this one
 * @return array Group label => array(token => label)
 */
function formulize_automationTokenOptions($fid, $earlierOutputs) {
	$groups = array();
	if ($earlierOutputs) {
		foreach ($earlierOutputs as $name => $stepLabel) {
			$groups['From earlier steps']['{' . $name . '}'] = $stepLabel;
		}
	}
	foreach (formulize_automationElementOptions($fid) as $handle => $label) {
		$groups['From the entry']['{' . $handle . '}'] = $label;
	}
	$groups['General'] = array(
		'{ID}' => 'The entry id',
		'{ENTRYNAME}' => 'Who did it',
		'{VIEWURL}' => 'A link to the entry',
		'{FORMSINGULAR}' => 'The form, singular',
		'{FORMNAME}' => 'The form name',
		'{SITEURL}' => 'The site address',
		'{USER}' => 'The current user',
		'{TODAY}' => "Today's date",
	);
	return $groups;
}
