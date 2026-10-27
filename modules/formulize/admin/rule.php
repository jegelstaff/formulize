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

// the editor for one rule, reached from the Rules tab of a form

include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";
include_once XOOPS_ROOT_PATH."/modules/formulize/include/rules_admin.php";

$aid = intval($_GET['aid']);
$fid = intval($_GET['fid']);
$application_handler = xoops_getmodulehandler('applications', 'formulize');
$appName = $aid ? $application_handler->get($aid)->getVar('name') : "Forms with no app";
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($fid);
$formName = $formObject ? $formObject->getVar('title') : '';

$rules_handler = xoops_getmodulehandler('rules', 'formulize');
$ruleId = (isset($_GET['rule_id']) AND $_GET['rule_id'] !== 'new') ? intval($_GET['rule_id']) : 'new';
$rule = $ruleId === 'new' ? false : $rules_handler->get($ruleId);
if(!$rule OR $rule['fid'] != $fid) {
	$startingPoint = isset($_GET['starting_point']) ? preg_replace('/[^a-z_]/', '', $_GET['starting_point']) : '';
	$rule = $startingPoint ? $rules_handler->createFromStartingPoint($fid, $startingPoint) : $rules_handler->create($fid);
	$ruleId = 'new';
}

$member_handler = xoops_gethandler('member');
$groupOptions = array();
foreach($member_handler->getGroups() as $groupObject) {
	$groupOptions[$groupObject->getVar('groupid')] = $groupObject->getVar('name');
}
natcasesort($groupOptions);

$formOptions = array();
foreach($form_handler->getAllFormTitles() as $formId => $formTitle) {
	$formOptions[$formId] = trans($formTitle);
}

$content = array();
$content['aid'] = $aid;
$content['fid'] = $fid;
$content['rule_id'] = $ruleId;
$content['form_title'] = trans($formName);
$content['rule'] = array(
	'name' => $rule['name'],
	'active' => $rule['active'],
	'trigger_type' => $rule['trigger_type'],
	'watch' => array_values((array) ($rule['trigger_settings']['watch'] ?? array())),
	'button_key' => intval($rule['trigger_settings']['sid'] ?? 0).'|'.strval($rule['trigger_settings']['button'] ?? ''),
);
$content['triggerTypes'] = formulizeRulesHandler::triggerTypes();
$content['stepTypes'] = formulizeRulesHandler::stepTypes();
$content['elementOptions'] = formulize_ruleElementOptions($fid);
$content['buttonOptions'] = formulize_ruleButtonOptions($fid);
$content['conditions_ui'] = formulize_createFilterUI($rule['conditions'], 'ruleconditions', $fid, 'form-1');
$content['groupOptions'] = $groupOptions;
$content['formOptions'] = $formOptions;
$content['userElementOptions'] = array(0 => '(none)') + formulize_ruleElementOptionsOfKind($fid, 'users');
$content['linkedElementOptions'] = array(0 => '(none)') + formulize_ruleElementOptionsOfKind($fid, 'linked');
$content['emailElementOptions'] = array(0 => '(none)') + formulize_ruleElementOptions($fid, 'id');
$content['modeOptions'] = array('replace' => 'Set to', 'append' => 'Add to the end of what is there');
$content['ownerOptions'] = array('current' => 'The user who triggered the event', 'entry_owner' => 'The owner of the entry the event is about');

$content['ai_rules_enabled'] = formulize_aiInRulesEnabled();
$content['is_new'] = ($ruleId === 'new');
// steps that can be used before an entry is saved, when nothing else exists yet to do things to
$content['beforeSaveStepTypes'] = array('code', 'update_entries');

$content['steps'] = array();
$earlierOutputs = array();
foreach(array_values((array) $rule['steps']) as $index => $step) {
	$type = $step['type'] ?? '';
	$item = array(
		'index' => $index,
		'number' => $index + 1,
		'type' => $type,
		'type_label' => $content['stepTypes'][$type] ?? $type,
		'id' => strval($step['id'] ?? ''),
		'output' => formulize_ruleOutputName($step),
		'makes_output' => in_array($type, array('ai', 'create_entry', 'code', 'update_entries')),
		'token_groups' => formulize_ruleTokenOptions($fid, $earlierOutputs),
	);
	switch($type) {
		case 'ai':
			$item['prompt'] = strval($step['prompt'] ?? '');
			$item['include_entry'] = (!isset($step['include_entry']) || $step['include_entry']);
			$item['json'] = !empty($step['json']);
			$item['max_tokens'] = intval($step['max_tokens'] ?? 0) ?: 1024;
			break;
		case 'code':
			$item['code'] = strval($step['code'] ?? '') !== '' ? strval($step['code']) : "<?php\n";
			$item['values_as'] = ($step['values_as'] ?? 'stored') == 'readable' ? 'readable' : 'stored';
			break;
		case 'notify':
			$recipients = (array) ($step['recipients'] ?? array());
			$item['creator'] = !empty($recipients['creator']);
			$item['curuser'] = !empty($recipients['curuser']);
			$item['groups'] = array_map('intval', (array) ($recipients['groups'] ?? array()));
			$item['elementuids'] = intval($recipients['elementuids'] ?? 0);
			$item['linkcreator'] = intval($recipients['linkcreator'] ?? 0);
			$item['elementemail'] = intval($recipients['elementemail'] ?? 0);
			$item['emails'] = strval($recipients['emails'] ?? '');
			$item['subject'] = strval($step['subject'] ?? '');
			$item['message'] = strval($step['message'] ?? '');
			$item['template'] = strval($step['template'] ?? '');
			break;
		case 'update_entries':
		case 'create_entry':
			$item['target'] = $type == 'create_entry' ? 'other' : strval($step['target'] ?? 'this');
			$item['target_fid'] = intval($step['target_fid'] ?? 0) ? intval($step['target_fid']) : $fid;
			$item['owner'] = strval($step['owner'] ?? 'current');
			$valuesFid = ($type == 'update_entries' AND $item['target'] == 'this') ? $fid : $item['target_fid'];
			$item['target_element_options'] = array('' => '(choose an element)') + formulize_ruleElementOptions($valuesFid);
			$item['values'] = array();
			foreach(array_values((array) ($step['values'] ?? array())) as $valueIndex => $valueSetting) {
				$item['values'][] = array(
					'index' => $valueIndex,
					'element' => strval($valueSetting['element'] ?? ''),
					'value' => strval($valueSetting['value'] ?? ''),
					'mode' => strval($valueSetting['mode'] ?? 'replace'),
				);
			}
			if($type == 'update_entries') {
				$item['target_conditions_ui'] = formulize_createFilterUI((array) ($step['target_conditions'] ?? array()), 'stepcond'.$index, $item['target_fid'], 'form-1');
			}
			break;
		case 'webhook':
			$item['url'] = strval($step['url'] ?? '');
			$item['secret'] = strval($step['secret'] ?? '');
			$item['include_data'] = (!isset($step['include_data']) || $step['include_data']);
			break;
	}
	$content['steps'][] = $item;
	if($item['output'] !== '') {
		$earlierOutputs[$item['output']] = 'Step '.($index + 1).': '.$item['type_label'];
	}
}
$content['next_token_groups'] = formulize_ruleTokenOptions($fid, $earlierOutputs);

// activity: when the rule ran, and its webhook deliveries
$content['log'] = array();
$content['outbox_items'] = array();
if($ruleId !== 'new') {
	global $xoopsDB;
	if($res = $xoopsDB->query("SELECT * FROM ".$xoopsDB->prefix('formulize_rule_log')." WHERE rule_id = ".intval($ruleId)." ORDER BY log_id DESC LIMIT 50")) {
		while($row = $xoopsDB->fetchArray($res)) {
			$user = $row['uid'] ? $member_handler->getUser($row['uid']) : false;
			$row['user'] = $user ? $user->getVar('uname') : ($row['uid'] ? $row['uid'] : 'anonymous');
			$row['outcome_lines'] = explode("\n", strval($row['outcome']));
			$content['log'][] = $row;
		}
	}
	if($res = $xoopsDB->query("SELECT outbox_id, entry_id, event, target, status, attempts, response_code, last_error, created_at, delivered_at, next_attempt_at FROM ".$xoopsDB->prefix('formulize_rule_outbox')." WHERE rule_id = ".intval($ruleId)." ORDER BY outbox_id DESC LIMIT 25")) {
		while($row = $xoopsDB->fetchArray($res)) {
			$content['outbox_items'][] = $row;
		}
	}
}

$adminPage['tabs'][1]['name'] = "Settings";
$adminPage['tabs'][1]['template'] = "db:admin/rule.html";
$adminPage['tabs'][1]['content'] = $content;
if($ruleId !== 'new') {
	$adminPage['tabs'][2]['name'] = "Activity";
	$adminPage['tabs'][2]['template'] = "db:admin/rule_activity.html";
	$adminPage['tabs'][2]['content'] = $content;
}

$adminPage['pagetitle'] = "Rule: ".$rule['name'];
$adminPage['needsave'] = true;

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['url'] = "page=application&aid=$aid&tab=forms";
$breadcrumbtrail[2]['text'] = $appName;
$breadcrumbtrail[3]['url'] = "page=form&aid=$aid&fid=$fid&tab=rules";
$breadcrumbtrail[3]['text'] = $formName;
$breadcrumbtrail[4]['text'] = $rule['name'];
