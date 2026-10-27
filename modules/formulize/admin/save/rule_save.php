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

// this file saves the editor for one rule (admin/rule.php, templates/admin/rule.html)

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
	return;
}

$fid = intval($_POST['formulize_admin_fid']);
$aid = intval($_POST['formulize_admin_aid']);
$ruleId = $_POST['formulize_admin_key'] === 'new' ? 'new' : intval($_POST['formulize_admin_key']);

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
if($ruleId === 'new') {
	$rule = $rules_handler->create($fid);
} elseif(!($rule = $rules_handler->get($ruleId)) OR $rule['fid'] != $fid) {
	print "Error: this rule doesn't exist, or belongs to another form.";
	return;
}
$previousSteps = array_values((array) $rule['steps']);
$reload = !empty($_POST['rule_reload']);

// the rule itself
$rule['name'] = trim(strval($_POST['rule_name'] ?? '')) !== '' ? trim(strval($_POST['rule_name'])) : 'New rule';
$rule['active'] = !empty($_POST['rule_active']) ? 1 : 0;
$triggerTypes = formulizeRulesHandler::triggerTypes();
$rule['trigger_type'] = isset($triggerTypes[$_POST['rule_trigger_type'] ?? '']) ? $_POST['rule_trigger_type'] : $rule['trigger_type'];
$elementOptions = formulize_ruleElementOptions($fid);
$rule['trigger_settings'] = array();
if(in_array($rule['trigger_type'], array('update_entry', 'save_entry'))) {
	$rule['trigger_settings']['watch'] = array_values(array_intersect((array) ($_POST['rule_watch'] ?? array()), array_keys($elementOptions)));
}
if($rule['trigger_type'] == 'button_click' AND !empty($_POST['rule_button'])) {
	list($buttonSid, $buttonHandle) = array_pad(explode('|', strval($_POST['rule_button']), 2), 2, '');
	$rule['trigger_settings']['sid'] = intval($buttonSid);
	$rule['trigger_settings']['button'] = $buttonHandle;
}

// which entries
$parsedConditions = parseSubmittedConditions('ruleconditions', 'ruleconditionsdelete');
if(is_array($parsedConditions)) {
	list($rule['conditions'], $conditionsReload) = $parsedConditions;
	$reload = ($reload || $conditionsReload);
}

// then
$stepTypes = formulizeRulesHandler::stepTypes();
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
if(($_POST['rule_remove_value'] ?? '') !== '') {
	list($stepIndex, $valueIndex) = array_map('intval', array_pad(explode('_', $_POST['rule_remove_value']), 2, 0));
	if(isset($steps[$stepIndex]['values'][$valueIndex])) {
		unset($steps[$stepIndex]['values'][$valueIndex]);
		$steps[$stepIndex]['values'] = array_values($steps[$stepIndex]['values']);
	}
}
if(($_POST['rule_add_value'] ?? '') !== '' AND isset($steps[intval($_POST['rule_add_value'])])) {
	$steps[intval($_POST['rule_add_value'])]['values'][] = array('element' => '', 'value' => '', 'mode' => 'replace');
}
if(($_POST['rule_remove_step'] ?? '') !== '' AND isset($steps[intval($_POST['rule_remove_step'])])) {
	unset($steps[intval($_POST['rule_remove_step'])]);
	$steps = array_values($steps);
}
if(($_POST['rule_move_step'] ?? '') !== '') {
	$moveIndex = intval($_POST['rule_move_step']);
	if($moveIndex > 0 AND isset($steps[$moveIndex])) {
		$moving = $steps[$moveIndex];
		$steps[$moveIndex] = $steps[$moveIndex - 1];
		$steps[$moveIndex - 1] = $moving;
	}
}
if(isset($stepTypes[$_POST['rule_add_step'] ?? ''])) {
	$newType = $_POST['rule_add_step'];
	$newStep = array('type' => $newType);
	if($defaultOutputName = formulizeRulesHandler::defaultOutputName($newType)) {
		// don't reuse a name an earlier step already has
		$usedNames = array_filter(array_map('formulize_ruleOutputName', $steps));
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
$rule['steps'] = $steps;

if(!$savedRuleId = $rules_handler->insert($rule)) {
	print "Error: could not save the rule: ".$xoopsDB->error();
	return;
}

if($ruleId === 'new') {
	print "/* eval */ window.location = '".XOOPS_URL."/modules/formulize/admin/ui.php?page=rule&aid=$aid&fid=$fid&rule_id=$savedRuleId';";
} elseif(intval($_POST['rule_test'] ?? 0) > 0) {
	$testOutcome = formulize_testRule($rules_handler->get($savedRuleId), intval($_POST['rule_test']));
	print "/* eval */ alert(".json_encode($testOutcome)."); reloadWithScrollPosition();";
} elseif($reload) {
	print "/* eval */ reloadWithScrollPosition();";
}
