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

// this file saves the editor for one automation (admin/automation.php, templates/admin/automation.html)

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
	return;
}

$fid = intval($_POST['formulize_admin_fid']);
$aid = intval($_POST['formulize_admin_aid']);
$automationId = $_POST['formulize_admin_key'] === 'new' ? 'new' : intval($_POST['formulize_admin_key']);

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
if($automationId === 'new') {
	$automation = $automations_handler->create($fid);
} elseif(!($automation = $automations_handler->get($automationId)) OR $automation['fid'] != $fid) {
	print "Error: this automation doesn't exist, or belongs to another form.";
	return;
}
$previousSteps = array_values((array) $automation['steps']);
$reload = !empty($_POST['automation_reload']);

// the automation itself
$automation['name'] = trim(strval($_POST['automation_name'] ?? '')) !== '' ? trim(strval($_POST['automation_name'])) : 'New automation';
$automation['active'] = !empty($_POST['automation_active']) ? 1 : 0;
$triggerTypes = formulizeAutomationsHandler::triggerTypes();
$automation['trigger_type'] = isset($triggerTypes[$_POST['automation_trigger_type'] ?? '']) ? $_POST['automation_trigger_type'] : $automation['trigger_type'];
$elementOptions = formulize_automationElementOptions($fid);
$automation['trigger_settings'] = array();
if(in_array($automation['trigger_type'], array('update_entry', 'save_entry'))) {
	$automation['trigger_settings']['watch'] = array_values(array_intersect((array) ($_POST['automation_watch'] ?? array()), array_keys($elementOptions)));
}
if($automation['trigger_type'] == 'button_click' AND !empty($_POST['automation_button'])) {
	list($buttonSid, $buttonHandle) = array_pad(explode('|', strval($_POST['automation_button']), 2), 2, '');
	$automation['trigger_settings']['sid'] = intval($buttonSid);
	$automation['trigger_settings']['button'] = $buttonHandle;
}

// which entries
$parsedConditions = parseSubmittedConditions('automationconditions', 'automationconditionsdelete');
if(is_array($parsedConditions)) {
	list($automation['conditions'], $conditionsReload) = $parsedConditions;
	$reload = ($reload || $conditionsReload);
}

// then
$stepTypes = formulizeAutomationsHandler::stepTypes();
$steps = array();
$postedSteps = (array) ($_POST['steps'] ?? array());
ksort($postedSteps, SORT_NUMERIC);
foreach($postedSteps as $index => $posted) {
	$type = strval($posted['type'] ?? '');
	if(!isset($stepTypes[$type])) {
		continue;
	}
	$previous = $previousSteps[$index] ?? array();
	$step = array('type' => $type, 'id' => preg_replace('/[^a-z0-9]/', '', strval($posted['id'] ?? ($previous['id'] ?? ''))));
	// the name later steps use for this step's output: lowercase letters, numbers and underscores, and not the same as
	// an element handle in this form, since {name} would then be ambiguous
	if(isset($posted['output'])) {
		$outputName = preg_replace('/[^a-z0-9_]/', '', strtolower(str_replace(array(' ', '-'), '_', trim(strval($posted['output']), " {}"))));
		if($outputName !== '' AND (isset($elementOptions[$outputName]) OR in_array($outputName, array('id', 'user', 'today', 'blank')))) {
			$outputName .= '_result';
		}
		$step['output'] = $outputName;
	}
	switch($type) {
		case 'ai':
			$step['prompt'] = strval($posted['prompt'] ?? '');
			$step['include_entry'] = !empty($posted['include_entry']) ? 1 : 0;
			$step['json'] = !empty($posted['json']) ? 1 : 0;
			$step['max_tokens'] = max(16, min(64000, intval($posted['max_tokens'] ?? 1024) ?: 1024));
			break;
		case 'code':
			$step['code'] = strval($posted['code'] ?? '');
			$step['values_as'] = ($posted['values_as'] ?? 'stored') == 'readable' ? 'readable' : 'stored';
			break;
		case 'notify':
			$recipients = (array) ($posted['recipients'] ?? array());
			$step['recipients'] = array(
				'creator' => !empty($recipients['creator']) ? 1 : 0,
				'curuser' => !empty($recipients['curuser']) ? 1 : 0,
				'groups' => array_values(array_filter(array_map('intval', (array) ($recipients['groups'] ?? array())))),
				'elementuids' => intval($recipients['elementuids'] ?? 0),
				'linkcreator' => intval($recipients['linkcreator'] ?? 0),
				'elementemail' => intval($recipients['elementemail'] ?? 0),
				'emails' => trim(strval($recipients['emails'] ?? '')),
			);
			$step['subject'] = trim(strval($posted['subject'] ?? ''));
			$step['message'] = strval($posted['message'] ?? '');
			$step['template'] = basename(str_replace('\\', '/', trim(strval($posted['template'] ?? ''))));
			break;
		case 'update_entries':
		case 'create_entry':
			if($type == 'update_entries') {
				$step['target'] = ($posted['target'] ?? 'this') == 'matching' ? 'matching' : 'this';
			}
			$step['target_fid'] = intval($posted['target_fid'] ?? ($previous['target_fid'] ?? $fid));
			if(!$step['target_fid']) {
				$step['target_fid'] = $fid;
			}
			if($type == 'create_entry') {
				$step['owner'] = ($posted['owner'] ?? 'current') == 'entry_owner' ? 'entry_owner' : 'current';
			}
			$step['values'] = array();
			$postedValues = (array) ($posted['values'] ?? array());
			ksort($postedValues, SORT_NUMERIC);
			foreach($postedValues as $valueSetting) {
				$step['values'][] = array(
					'element' => strval($valueSetting['element'] ?? ''),
					'value' => strval($valueSetting['value'] ?? ''),
					'mode' => ($valueSetting['mode'] ?? 'replace') == 'append' ? 'append' : 'replace',
				);
			}
			$previousFid = intval($previous['target_fid'] ?? $fid);
			$previousTarget = $previous['target'] ?? ($type == 'create_entry' ? 'other' : 'this');
			$valuesFormChanged = (($step['target_fid'] != $previousFid AND ($type == 'create_entry' OR $step['target'] == 'matching'))
				OR ($type == 'update_entries' AND $step['target'] != $previousTarget AND $step['target_fid'] != $fid));
			if($valuesFormChanged) {
				// the elements belong to another form now, so the old ones don't apply
				$step['values'] = array(array('element' => '', 'value' => '', 'mode' => 'replace'));
			}
			if($type == 'update_entries') {
				$step['target_conditions'] = (array) ($previous['target_conditions'] ?? array());
				$parsedTargetConditions = parseSubmittedConditions('stepcond'.intval($index), 'stepcond'.intval($index).'delete');
				if(is_array($parsedTargetConditions)) {
					list($step['target_conditions'], $targetConditionsReload) = $parsedTargetConditions;
					$reload = ($reload || $targetConditionsReload);
				}
				if($step['target_fid'] != $previousFid) {
					$step['target_conditions'] = array();
				}
			}
			break;
		case 'webhook':
			$step['url'] = trim(strval($posted['url'] ?? ''));
			$step['secret'] = trim(strval($posted['secret'] ?? ''));
			$step['include_data'] = !empty($posted['include_data']) ? 1 : 0;
			break;
	}
	$steps[] = $step;
}

// changes to the list of steps and values
if(($_POST['automation_remove_value'] ?? '') !== '') {
	list($stepIndex, $valueIndex) = array_map('intval', array_pad(explode('_', $_POST['automation_remove_value']), 2, 0));
	if(isset($steps[$stepIndex]['values'][$valueIndex])) {
		unset($steps[$stepIndex]['values'][$valueIndex]);
		$steps[$stepIndex]['values'] = array_values($steps[$stepIndex]['values']);
	}
}
if(($_POST['automation_add_value'] ?? '') !== '' AND isset($steps[intval($_POST['automation_add_value'])])) {
	$steps[intval($_POST['automation_add_value'])]['values'][] = array('element' => '', 'value' => '', 'mode' => 'replace');
}
if(($_POST['automation_remove_step'] ?? '') !== '' AND isset($steps[intval($_POST['automation_remove_step'])])) {
	unset($steps[intval($_POST['automation_remove_step'])]);
	$steps = array_values($steps);
}
if(($_POST['automation_move_step'] ?? '') !== '') {
	$moveIndex = intval($_POST['automation_move_step']);
	if($moveIndex > 0 AND isset($steps[$moveIndex])) {
		$moving = $steps[$moveIndex];
		$steps[$moveIndex] = $steps[$moveIndex - 1];
		$steps[$moveIndex - 1] = $moving;
	}
}
if(isset($stepTypes[$_POST['automation_add_step'] ?? ''])) {
	$newType = $_POST['automation_add_step'];
	$newStep = array('type' => $newType);
	if($defaultOutputName = formulizeAutomationsHandler::defaultOutputName($newType)) {
		// don't reuse a name an earlier step already has
		$usedNames = array_filter(array_map('formulize_automationOutputName', $steps));
		$newStep['output'] = $defaultOutputName;
		for($n = 2; in_array($newStep['output'], $usedNames); $n++) {
			$newStep['output'] = $defaultOutputName.'_'.$n;
		}
	}
	switch($newType) {
		case 'ai':
			$newStep['prompt'] = '';
			$newStep['include_entry'] = 1;
			$newStep['json'] = 0;
			break;
		case 'code':
			$newStep['code'] = "<?php\n";
			break;
		case 'notify':
			$newStep['recipients'] = array('creator' => 1);
			$newStep['subject'] = '';
			$newStep['message'] = '';
			break;
		case 'update_entries':
			$newStep['target'] = 'this';
			$newStep['target_fid'] = $fid;
			$newStep['values'] = array(array('element' => '', 'value' => '', 'mode' => 'replace'));
			break;
		case 'create_entry':
			$newStep['target_fid'] = $fid;
			$newStep['owner'] = 'current';
			$newStep['values'] = array(array('element' => '', 'value' => '', 'mode' => 'replace'));
			break;
		case 'webhook':
			$newStep['url'] = '';
			$newStep['secret'] = '';
			$newStep['include_data'] = 1;
			break;
	}
	$steps[] = $newStep;
}
$automation['steps'] = $steps;

if(!$savedAutomationId = $automations_handler->insert($automation)) {
	print "Error: could not save the automation: ".$xoopsDB->error();
	return;
}

if($automationId === 'new') {
	print "/* eval */ window.location = '".XOOPS_URL."/modules/formulize/admin/ui.php?page=automation&aid=$aid&fid=$fid&automation_id=$savedAutomationId';";
} elseif(intval($_POST['automation_test'] ?? 0) > 0) {
	$testOutcome = formulize_testAutomation($automations_handler->get($savedAutomationId), intval($_POST['automation_test']));
	print "/* eval */ alert(".json_encode($testOutcome)."); reloadWithScrollPosition();";
} elseif($reload) {
	print "/* eval */ reloadWithScrollPosition();";
}
