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

/**
 * Rules: "when this happens to an entry in this form, and the entry matches these conditions, then do these
 * things". Rules are configured on the Rules tab of a form, and stored by class/rules.php. See rules-plan.md.
 *
 * How rules get run:
 *
 * 1. Every place that used to call sendNotifications() for a new, updated or deleted entry now calls
 *    formulize_dispatchEntryEvent(), which sends the legacy notifications exactly as before and then runs the rules.
 *    Those call sites are the end of a "logical save", after derived values have been updated.
 *
 * 2. formulizeDataHandler::writeEntry() and deleteEntries() record each write and delete in a registry for the
 *    request (only for forms that have active rules). Anything in the registry that was not dispatched by step 1 is
 *    dispatched at the end of the request, so rules run no matter how an entry was saved (subform rows, inline list
 *    editing, procedures calling formulize_writeEntry, the API...). Deletes are recorded before the row is removed,
 *    so that conditions can be checked against it, and its values are available to the rule's steps.
 *
 * 3. Custom buttons dispatch a button_click event for the entries they applied to.
 *
 * Rules run as the system, not as the user who triggered them, the same as procedures. Each rule runs at most once per
 * entry per event per request, and rules triggering rules (by writing entries) stop after a depth of three.
 */

if (!defined("XOOPS_ROOT_PATH")) {
	die("XOOPS root path not defined");
}

include_once XOOPS_ROOT_PATH . "/modules/formulize/class/rules.php";

define('FORMULIZE_RULE_MAX_DEPTH', 3);
define('FORMULIZE_RULE_MAX_TARGET_ENTRIES', 1000);
define('FORMULIZE_RULE_WEBHOOK_MAX_ATTEMPTS', 6);

/**
 * Dispatch an entry event: send the legacy notifications for it, and run the rules for it.
 * Use this instead of calling sendNotifications() directly, once entries have been saved or deleted.
 *
 * @param int $fid The form the entries belong to
 * @param string $event new_entry, update_entry or delete_entry
 * @param array $entries The entry ids
 * @return void
 */
function formulize_dispatchEntryEvent($fid, $event, $entries) {
	$entries = array_filter(array_unique((array) $entries));
	if (empty($entries)) {
		return;
	}
	sendNotifications($fid, $event, $entries);
	formulize_runRules($fid, $event, $entries);
	$icmsEvents = array('new_entry' => 'formulizeEntriesCreated', 'update_entry' => 'formulizeEntriesUpdated', 'delete_entry' => 'formulizeEntriesDeleted');
	if (isset($icmsEvents[$event])) {
		formulize_rulesTriggerIcmsEvent($icmsEvents[$event], array('form_id' => intval($fid), 'entry_ids' => array_values($entries)));
	}
}

/**
 * Dispatch a custom button click, for the entries the button applied to.
 *
 * @param object $screen The list screen the button is on
 * @param string $buttonHandle The handle of the button
 * @param int $fid The form of the entries
 * @param array $entries The entry ids the button applied to. Can be empty, for a button that applies to no particular entry.
 * @return void
 */
function formulize_dispatchButtonEvent($screen, $buttonHandle, $fid, $entries) {
	if (!is_object($screen) OR !$fid) {
		return;
	}
	$entries = array_values(array_unique(array_filter(array_map('intval', (array) $entries))));
	if (empty($entries)) {
		$entries = array(0); // the button was not about any entry in particular, but the rule can still create entries, send webhooks, etc.
	}
	formulize_runRules($fid, 'button_click', $entries, array('sid' => intval($screen->getVar('sid')), 'button' => strval($buttonHandle)));
	formulize_rulesTriggerIcmsEvent('formulizeCustomButtonClicked', array('form_id' => intval($fid), 'entry_ids' => array_values(array_filter($entries)), 'screen_id' => intval($screen->getVar('sid')), 'button' => strval($buttonHandle)));
}

/**
 * Record that an entry was written, so the rules for it run at the end of the request if nothing dispatches it
 * sooner. Called from formulizeDataHandler::writeEntry() after a successful write.
 *
 * @param int $fid The form
 * @param int $entryId The entry that was written
 * @param bool $wasNew True if the entry was created by this write
 * @param array $existingValues The values in the entry before this write (empty for a new entry)
 * @return void
 */
function formulize_rulesRecordWrite($fid, $entryId, $wasNew, $existingValues) {
	$fid = intval($fid);
	$entryId = intval($entryId);
	if (!$fid OR !$entryId OR !empty($GLOBALS['formulize_suppressRules'])) {
		return;
	}
	$handler = xoops_getmodulehandler('rules', 'formulize');
	if (!array_intersect($handler->activeTriggerTypes($fid), array('new_entry', 'update_entry', 'save_entry'))) {
		return;
	}
	$registry =& formulize_rulesRegistry();
	if (isset($registry[$fid][$entryId]) AND !$registry[$fid][$entryId]['dispatched']) {
		return; // already waiting to be dispatched. A new entry stays new, and an update keeps the values from before the first write.
	}
	$registry[$fid][$entryId] = array(
		'event' => $wasNew ? 'new_entry' : 'update_entry',
		'before' => $wasNew ? array() : (array) $existingValues,
		'dispatched' => false,
	);
	formulize_rulesEnsureShutdown();
}

/**
 * Get ready for entries to be deleted: check the conditions of the delete rules against them, and keep a copy of
 * their values and ownership, since none of that can be read once the rows are gone.
 * Called from formulizeDataHandler::deleteEntries() before the rows are deleted.
 *
 * @param int $fid The form
 * @param array $ids The entries about to be deleted
 * @return void
 */
function formulize_rulesPrepareDelete($fid, $ids) {
	$fid = intval($fid);
	if (!$fid OR !empty($GLOBALS['formulize_suppressRules'])) {
		return;
	}
	$handler = xoops_getmodulehandler('rules', 'formulize');
	if (!in_array('delete_entry', $handler->activeTriggerTypes($fid))) {
		return;
	}
	$rules = $handler->getRules($fid, 'delete_entry', true);
	$data_handler = new formulizeDataHandler($fid);
	$registry =& formulize_rulesRegistry();
	foreach ((array) $ids as $id) {
		$id = intval($id);
		if (!$id OR !$row = formulize_ruleGetEntryRow($fid, $id)) {
			continue;
		}
		$matches = array();
		foreach ($rules as $rule) {
			$matches[$rule['rule_id']] = formulize_ruleEntryMatchesConditions($rule['conditions'], $fid, $id);
		}
		$registry[$fid][$id] = array(
			'event' => 'delete_entry',
			'snapshot' => $row,
			'owner' => intval($row['creation_uid']),
			'owner_groups' => $data_handler->getEntryOwnerGroups($id),
			'delete_matches' => $matches,
			'deleted' => false,
			'dispatched' => false,
		);
	}
	formulize_rulesEnsureShutdown();
}

/**
 * Record that entries prepared by formulize_rulesPrepareDelete() really were deleted.
 * Called from formulizeDataHandler::deleteEntries() after the rows are deleted.
 *
 * @param int $fid The form
 * @param array $ids The entries that were deleted
 * @return void
 */
function formulize_rulesRecordDelete($fid, $ids) {
	$registry =& formulize_rulesRegistry();
	foreach ((array) $ids as $id) {
		if (isset($registry[intval($fid)][intval($id)]) AND $registry[intval($fid)][intval($id)]['event'] == 'delete_entry') {
			$registry[intval($fid)][intval($id)]['deleted'] = true;
		}
	}
}

/**
 * The registry of entries written and deleted during this request
 * @return array A reference to the registry: [fid][entry id] => record
 */
function &formulize_rulesRegistry() {
	if (!isset($GLOBALS['formulize_ruleRegistry'])) {
		$GLOBALS['formulize_ruleRegistry'] = array();
	}
	return $GLOBALS['formulize_ruleRegistry'];
}

/**
 * Make sure the end-of-request processing will happen
 * @return void
 */
function formulize_rulesEnsureShutdown() {
	static $registered = false;
	if (!$registered) {
		register_shutdown_function('formulize_rulesShutdown');
		$registered = true;
	}
}

/**
 * At the end of the request, run the rules for anything written or deleted that was not dispatched already, then
 * send any webhooks that are waiting.
 * @return void
 */
function formulize_rulesShutdown() {
	try {
		ob_start(); // nothing done here should end up in the page
		formulize_rulesFlush();
		ob_end_clean();
		if (!empty($GLOBALS['formulize_ruleJobsThisRequest'])) {
			// rules queued to run in the background can take a while (ie: asking AI), so let the page go first
			formulize_rulesReleaseClient();
		}
		ob_start();
		formulize_ruleProcessOutbox(10);
		ob_end_clean();
	} catch (Throwable $e) {
		while (ob_get_level() > 0) { ob_end_clean(); }
		error_log("Formulize rules: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
	}
}

/**
 * Run the rules for every write and delete in the registry that has not been dispatched yet. Running rules can write
 * more entries, so go around again until nothing is left, a few times at most.
 * @return void
 */
function formulize_rulesFlush() {
	for ($pass = 0; $pass < 5; $pass++) {
		$registry =& formulize_rulesRegistry();
		$toDispatch = array();
		foreach ($registry as $fid => $records) {
			foreach ($records as $entryId => $record) {
				if ($record['dispatched'] OR ($record['event'] == 'delete_entry' AND empty($record['deleted']))) {
					continue;
				}
				$toDispatch[$fid][$record['event']][] = $entryId;
			}
		}
		if (empty($toDispatch)) {
			return;
		}
		foreach ($toDispatch as $fid => $events) {
			foreach ($events as $event => $entryIds) {
				formulize_runRules($fid, $event, $entryIds);
			}
		}
	}
}

/**
 * The trigger types that a kind of event sets off
 * @param string $event new_entry, update_entry, delete_entry or button_click
 * @return array
 */
function formulize_ruleTriggerTypesForEvent($event) {
	switch ($event) {
		case 'new_entry':
			return array('new_entry', 'save_entry');
		case 'update_entry':
			return array('update_entry', 'save_entry');
		case 'delete_entry':
			return array('delete_entry');
		case 'button_click':
			return array('button_click');
	}
	return array();
}

/**
 * Run the rules on a form, for an event that happened to some entries.
 *
 * @param int $fid The form
 * @param string $event new_entry, update_entry, delete_entry or button_click
 * @param array $entries The entry ids
 * @param array $buttonFilter For button_click, the 'sid' and 'button' handle of the button that was clicked
 * @return array The outcome of each rule that ran, keyed by rule id and then entry id (mostly for testing)
 */
function formulize_runRules($fid, $event, $entries, $buttonFilter = null) {
	static $ran = array();
	$fid = intval($fid);
	$results = array();
	if (!$fid OR !empty($GLOBALS['formulize_suppressRules'])) {
		return $results;
	}
	$handler = xoops_getmodulehandler('rules', 'formulize');
	$activeTypes = $handler->activeTriggerTypes($fid);
	$registry =& formulize_rulesRegistry();
	$rulesByEvent = array();

	foreach ((array) $entries as $entryId) {
		$entryId = intval($entryId);
		$record = isset($registry[$fid][$entryId]) ? $registry[$fid][$entryId] : null;
		// whatever happens below, this entry's event has now been dispatched, so the end of the request won't do it again
		if ($record AND $event != 'button_click') {
			$registry[$fid][$entryId]['dispatched'] = true;
		}
		// an entry created earlier in this request is still a new entry, even if it was saved again since
		$entryEvent = ($event == 'update_entry' AND $record AND $record['event'] == 'new_entry') ? 'new_entry' : $event;
		$triggerTypes = formulize_ruleTriggerTypesForEvent($entryEvent);
		if (!array_intersect($triggerTypes, $activeTypes)) {
			continue;
		}
		if (!isset($rulesByEvent[$entryEvent])) {
			$rulesByEvent[$entryEvent] = $handler->getRules($fid, $triggerTypes, true);
		}
		$context = null;
		foreach ($rulesByEvent[$entryEvent] as $rule) {
			if ($entryEvent == 'button_click') {
				if (!$buttonFilter
					OR intval($rule['trigger_settings']['sid'] ?? 0) != $buttonFilter['sid']
					OR strval($rule['trigger_settings']['button'] ?? '') !== $buttonFilter['button']) {
					continue;
				}
			}
			$ranKey = $rule['rule_id'] . '-' . $entryId . '-' . $entryEvent;
			if (isset($ran[$ranKey]) AND $entryEvent != 'button_click') {
				continue; // each rule runs once per entry per event per request, which also stops rules looping on themselves
			}
			if ($context === null) {
				$context = formulize_ruleBuildContext($fid, $entryId, $entryEvent, $record);
				if ($context === false) {
					break; // the entry doesn't exist (anymore), so there's nothing to do for it
				}
			}
			if (!formulize_ruleApplies($rule, $context, $record)) {
				continue;
			}
			$ran[$ranKey] = true;
			$results[$rule['rule_id']][$entryId] = formulize_runRule($rule, $context);
		}
	}
	return $results;
}

/**
 * Check the trigger settings and conditions of a rule against the entry
 * @param array $rule The rule
 * @param array $context The context of the event (see formulize_ruleBuildContext)
 * @param array $record The registry record for the entry, if any
 * @return bool True if the rule should run for this entry
 */
function formulize_ruleApplies($rule, $context, $record) {
	$event = $context['event'];
	// updates: only when something actually changed, and when one of the watched elements changed, if there are any
	if ($event == 'update_entry' AND is_array($context['changed'])) {
		if (empty($context['changed'])) {
			return false;
		}
		$watch = array_filter((array) ($rule['trigger_settings']['watch'] ?? array()));
		if ($watch AND !array_intersect($watch, $context['changed'])) {
			return false;
		}
	}
	if ($event == 'delete_entry') {
		return !empty($record['delete_matches'][$rule['rule_id']]); // conditions were checked before the entry was deleted
	}
	if (!$context['entry_id']) {
		return empty($rule['conditions'][0]); // nothing to check conditions against, so only rules with no conditions apply
	}
	return formulize_ruleEntryMatchesConditions($rule['conditions'], $context['fid'], $context['entry_id']);
}

/**
 * Gather what rules and their steps need to know about the event and the entry
 *
 * @param int $fid The form
 * @param int $entryId The entry, or 0 for a button that applied to no entry
 * @param string $event The event
 * @param array $record The registry record for the entry, if any
 * @return array|false The context, or false if the entry does not exist
 */
function formulize_ruleBuildContext($fid, $entryId, $event, $record) {
	global $xoopsUser;
	$context = array(
		'fid' => $fid,
		'entry_id' => $entryId,
		'event' => $event,
		'uid' => $xoopsUser ? intval($xoopsUser->getVar('uid')) : 0,
		'values' => array(),
		'before' => array(),
		'changed' => null, // null means we don't know what changed
		'owner' => 0,
		'owner_groups' => null,
		'deleted' => false,
		'outputs' => array(), // what earlier steps produced, by output name
	);
	if (!$entryId) {
		return $context;
	}
	if ($event == 'delete_entry') {
		if (!$record OR !isset($record['snapshot'])) {
			return false;
		}
		$context['values'] = $record['snapshot'];
		$context['owner'] = $record['owner'];
		$context['owner_groups'] = $record['owner_groups'];
		$context['deleted'] = true;
		return $context;
	}
	if (!$context['values'] = formulize_ruleGetEntryRow($fid, $entryId)) {
		return false;
	}
	$context['owner'] = intval($context['values']['creation_uid']);
	if ($event == 'update_entry') {
		$before = null;
		if ($record AND isset($record['before']) AND $record['event'] == 'update_entry') {
			$before = $record['before'];
		} elseif (isset($GLOBALS['formulize_existingValues'][$fid][$entryId]['before_save'])) {
			$before = $GLOBALS['formulize_existingValues'][$fid][$entryId]['before_save'];
		}
		if (is_array($before) AND !empty($before)) {
			$context['before'] = $before;
			$context['changed'] = array();
			foreach ($context['values'] as $handle => $value) {
				if (isMetaDataField($handle) OR $handle == 'entry_id' OR !array_key_exists($handle, $before)) {
					continue;
				}
				if (strval($before[$handle]) !== strval($value)) {
					$context['changed'][] = $handle;
				}
			}
		}
	}
	return $context;
}

/**
 * Run the steps of a rule for one entry, and record that it ran. A rule with a step that can take a long time (Ask AI)
 * is queued to run in the background instead, unless this is already the background run.
 * @param array $rule The rule
 * @param array $context The context of the event
 * @return array The outcome of each step, as text
 */
function formulize_runRule($rule, $context) {
	global $formulize_ruleDepth;
	$formulize_ruleDepth = intval($formulize_ruleDepth);
	$context['rule'] = $rule;
	if ($formulize_ruleDepth >= FORMULIZE_RULE_MAX_DEPTH) {
		$outcomes = array("Not run: rules were setting off other rules more than " . FORMULIZE_RULE_MAX_DEPTH . " levels deep, which usually means rules are setting each other off in a loop.");
	} elseif (formulize_ruleRunsInBackground($rule) AND empty($GLOBALS['formulize_ruleRunNow'])) {
		$jobId = formulize_ruleQueueJob($rule, $context);
		$outcomes = array($jobId ? "Queued to run in the background (job $jobId), because it asks AI, which can take a while." : "Could not be queued to run in the background.");
	} else {
		$outcomes = array();
		$result = formulize_ruleRunSteps($rule, $context, 0, $outcomes);
		if ($result['status'] == 'retry') {
			// only happens outside the background, ie: when trying a rule from its editor
			$outcomes[] = "Stopped at step " . ($result['step'] + 1) . ": " . $result['error'];
		}
	}
	formulize_ruleLog($rule, $context, $outcomes);
	return $outcomes;
}

/**
 * Whether a rule should run in the background, after the request that triggered it, rather than straight away
 * @param array $rule
 * @return bool
 */
function formulize_ruleRunsInBackground($rule) {
	if ($rule['trigger_type'] == 'before_save') {
		return false; // can't be: the save is waiting for it
	}
	foreach ((array) $rule['steps'] as $step) {
		if (($step['type'] ?? '') == 'ai') {
			return true;
		}
	}
	return false;
}

/**
 * Run the steps of a rule, in order, starting from a step. Each step can produce an output, saved under the step's
 * output name, which later steps can use as {name}.
 *
 * @param array $rule The rule
 * @param array $context The context of the event. Outputs are added to $context['outputs'].
 * @param int $startStep The step to start from (steps before it ran already, ie: in an earlier attempt)
 * @param array $outcomes The outcome of each step is added to this
 * @return array 'status' => 'done', or 'retry' when a step failed in a way that might work later ('step' and 'error' say which and why)
 */
function formulize_ruleRunSteps($rule, &$context, $startStep, &$outcomes) {
	global $formulize_ruleDepth;
	$stepTypes = formulizeRulesHandler::stepTypes();
	$steps = array_values((array) $rule['steps']);
	if (!isset($context['outputs']) OR !is_array($context['outputs'])) {
		$context['outputs'] = array();
	}
	if (empty($steps)) {
		$outcomes[] = "No steps to run.";
		return array('status' => 'done');
	}
	$formulize_ruleDepth = intval($formulize_ruleDepth) + 1;
	$result = array('status' => 'done');
	for ($i = $startStep; $i < count($steps); $i++) {
		$step = $steps[$i];
		$label = ($i + 1) . ". " . ($stepTypes[$step['type'] ?? ''] ?? 'Step');
		try {
			$stepResult = formulize_ruleRunStep($step, $context);
			if (is_array($stepResult)) {
				$outcomes[] = $label . ": " . $stepResult['outcome'];
				$outputName = formulize_ruleOutputName($step);
				if ($outputName !== '' AND array_key_exists('output', $stepResult)) {
					$context['outputs'][$outputName] = $stepResult['output'];
				}
			} else {
				$outcomes[] = $label . ": " . $stepResult;
			}
		} catch (FormulizeRuleStopException $e) {
			$outcomes[] = $label . ": failed: " . $e->getMessage() . ($i + 1 < count($steps) ? " The steps after it were not run." : "");
			break;
		} catch (FormulizeRuleRetryableException $e) {
			$outcomes[] = $label . ": failed, will try again: " . $e->getMessage();
			$result = array('status' => 'retry', 'step' => $i, 'error' => $e->getMessage());
			break;
		} catch (Throwable $e) {
			$outcomes[] = $label . ": failed: " . $e->getMessage();
		}
		// later steps see the entry as it is now, including any changes the steps so far made to it
		if (in_array($step['type'] ?? '', array('update_entries', 'code')) AND $context['entry_id'] AND empty($context['deleted'])
			AND $currentValues = formulize_ruleGetEntryRow($context['fid'], $context['entry_id'])) {
			$context['values'] = $currentValues;
		}
	}
	$formulize_ruleDepth--;
	return $result;
}

/**
 * A step failed in a way that might work if it's tried again later (ie: the AI provider was busy)
 */
class FormulizeRuleRetryableException extends Exception {}

/**
 * The name a step's output is saved under, so later steps can use it as {name}
 * @param array $step The step
 * @return string The name, or '' if the step doesn't save its output
 */
function formulize_ruleOutputName($step) {
	return preg_replace('/[^a-z0-9_]/', '', strtolower(strval($step['output'] ?? '')));
}

/**
 * Write a record of a rule running, for the rule's Activity tab, and the Formulize log
 * @param array $rule
 * @param array $context
 * @param array $outcomes
 * @return void
 */
function formulize_ruleLog($rule, $context, $outcomes) {
	global $xoopsDB;
	$sql = "INSERT INTO " . $xoopsDB->prefix('formulize_rule_log') . " (rule_id, fid, entry_id, event, uid, outcome, created_at) VALUES ("
		. intval($rule['rule_id']) . ", " . intval($context['fid']) . ", " . intval($context['entry_id']) . ", '" . formulize_db_escape($context['event']) . "', "
		. intval($context['uid']) . ", '" . formulize_db_escape(implode("\n", $outcomes)) . "', NOW())";
	$xoopsDB->queryF($sql);
	if (function_exists('writeToFormulizeLog')) {
		writeToFormulizeLog(array(
			'formulize_event' => 'rule-ran',
			'user_id' => $context['uid'],
			'form_id' => $context['fid'],
			'entry_id' => $context['entry_id'],
			'rule_id' => $rule['rule_id'],
			'additional_info' => implode(' | ', $outcomes),
		));
	}
}

/**
 * Run one step of a rule
 * @param array $step The step settings, with a 'type'
 * @param array $context The context of the event, with the 'rule' added
 * @return string A description of what happened
 */
function formulize_ruleRunStep($step, $context) {
	if ($context['event'] == 'before_save' AND !in_array($step['type'] ?? '', array('code', 'update_entries'))) {
		return "skipped: before an entry is saved, only Run PHP code and Update entries (this entry) can be used.";
	}
	switch ($step['type'] ?? '') {
		case 'notify':
			return formulize_ruleStepNotify($step, $context);
		case 'update_entries':
			return formulize_ruleStepUpdateEntries($step, $context);
		case 'create_entry':
			return formulize_ruleStepCreateEntry($step, $context);
		case 'webhook':
			return formulize_ruleStepWebhook($step, $context);
		case 'ai':
			return formulize_ruleStepAI($step, $context);
		case 'code':
			return formulize_ruleStepCode($step, $context);
	}
	return "Unknown step type '" . strval($step['type'] ?? '') . "', skipped.";
}

/**
 * Get the stored values of an entry, straight from its form's table
 * @param int $fid
 * @param int $entryId
 * @return array|false The values keyed by element handle, plus the metadata fields, or false if there's no such entry
 */
function formulize_ruleGetEntryRow($fid, $entryId) {
	global $xoopsDB;
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	if (!$entryId OR !$formObject = $form_handler->get($fid)) {
		return false;
	}
	$sql = "SELECT * FROM " . $xoopsDB->prefix("formulize_" . $formObject->getVar('form_handle')) . " WHERE entry_id = " . intval($entryId);
	if ($res = $xoopsDB->query($sql)) {
		if ($row = $xoopsDB->fetchArray($res)) {
			return $row;
		}
	}
	return false;
}

/**
 * Check if an entry matches a set of conditions from the standard conditions UI
 * @param array $conditions The conditions: elements, ops, terms, types
 * @param int $fid The form of the entry
 * @param int $entryId The entry
 * @return bool True if the entry matches, or there are no conditions
 */
function formulize_ruleEntryMatchesConditions($conditions, $fid, $entryId) {
	global $xoopsDB, $xoopsUser;
	if (!is_array($conditions) OR empty($conditions[0])) {
		return true;
	}
	if (!formulize_ruleConditionsAreValid($conditions)) {
		return false; // an element in the conditions doesn't exist anymore, so don't guess
	}
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$formObject = $form_handler->get($fid);
	$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
	list($conditionsfilter, $conditionsfilter_oom, $parentFormFrom) = buildConditionsFilterSQL($conditions, $fid, $entryId, $uid, $formObject, 't1');
	if (trim((string) $conditionsfilter) === '' AND trim((string) $conditionsfilter_oom) === '') {
		return false; // conditions were given but none could be used, so don't assume they all pass
	}
	$table = $xoopsDB->prefix("formulize_" . $formObject->getVar('form_handle'));
	$sql = "SELECT t1.entry_id FROM $table AS t1 $parentFormFrom WHERE t1.entry_id = " . intval($entryId) . " $conditionsfilter $conditionsfilter_oom LIMIT 1";
	if ($res = $xoopsDB->query($sql)) {
		return $xoopsDB->getRowsNum($res) > 0;
	}
	return false;
}

/**
 * Check that every element in a set of conditions still exists. buildConditionsFilterSQL() stops the whole request
 * when it meets an element that doesn't exist, which would make saving entries impossible, so check first.
 * @param array $conditions
 * @return bool
 */
function formulize_ruleConditionsAreValid($conditions) {
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
	foreach ((array) $conditions[0] as $elementIdOrHandle) {
		if (!isMetaDataField($elementIdOrHandle) AND !$element_handler->get($elementIdOrHandle)) {
			return false;
		}
	}
	return true;
}

/**
 * Get the readable values of the entry the event is about, keyed by element handle
 * @param array $context The context of the event
 * @return array
 */
function formulize_ruleReadableValues($context) {
	static $cache = array();
	$fid = $context['fid'];
	$entryId = $context['entry_id'];
	if (!$entryId) {
		return array();
	}
	$cacheKey = $fid . '-' . $entryId . '-' . md5(serialize($context['values']));
	if (isset($cache[$cacheKey])) {
		return $cache[$cacheKey];
	}
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$formObject = $form_handler->get($fid, true);
	$readable = array();
	$handles = (array) $formObject->getVar('elementHandles');
	if (!$context['deleted']) {
		$data = gatherDataset($fid, filter: $entryId, frid: 0);
		if (isset($data[0])) {
			foreach ($handles as $handle) {
				$value = displayTogether($data[0], $handle, ", ");
				if ($value !== null AND $value !== '' AND !is_array($value)) {
					$readable[$handle] = trans(html_entity_decode(strval($value), ENT_QUOTES));
				} elseif (array_key_exists($handle, $context['values'])) {
					$readable[$handle] = '';
				}
			}
		}
	} else {
		// the entry is gone, so make its stored values readable one at a time
		foreach ($handles as $handle) {
			if (array_key_exists($handle, $context['values'])) {
				$readable[$handle] = formulize_ruleReadableValue($context['values'][$handle], $handle, $entryId);
			}
		}
	}
	return $cache[$cacheKey] = $readable;
}

/**
 * Make one stored value readable
 * @param mixed $value The stored value
 * @param string $handle The element handle
 * @param int $entryId The entry
 * @return string
 */
function formulize_ruleReadableValue($value, $handle, $entryId) {
	if ($value === null OR $value === '') {
		return '';
	}
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
	$prepped = prepvalues($value, $handle, $entryId);
	if (is_array($prepped)) {
		$prepped = implode(", ", $prepped);
	}
	return trans(html_entity_decode(strval($prepped), ENT_QUOTES));
}

/**
 * The tags that can be used in notification subjects and messages, and their values for this event
 * @param array $context The context of the event
 * @return array Tag => value
 */
function formulize_ruleNotificationTags($context) {
	global $xoopsUser;
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$formObject = $form_handler->get($context['fid']);
	$tags = array();
	if ($xoopsUser) {
		$tags['ENTRYUSERNAME'] = $xoopsUser->getVar('uname');
		$tags['ENTRYNAME'] = $xoopsUser->getVar('name') ? $xoopsUser->getVar('name') : $xoopsUser->getVar('uname');
	} else {
		$tags['ENTRYUSERNAME'] = _FORM_ANON_USER;
		$tags['ENTRYNAME'] = _FORM_ANON_USER;
	}
	$tags['FORMNAME'] = trans($formObject->getVar('title'));
	$tags['FORMPLURAL'] = trans($formObject->getPlural());
	$tags['FORMSINGULAR'] = trans($formObject->getSingular());
	$tags['VIEWURL'] = $context['entry_id'] ? XOOPS_URL . "/modules/formulize/index.php?fid=" . $context['fid'] . "&ve=" . $context['entry_id'] : XOOPS_URL . "/modules/formulize/index.php?fid=" . $context['fid'];
	$tags['ENTRYID'] = $context['entry_id'];
	$tags['SITEURL'] = XOOPS_URL;
	$tags['RULENAME'] = isset($context['rule']['name']) ? $context['rule']['name'] : '';
	foreach (formulize_ruleReadableValues($context) as $handle => $value) {
		$tags['ELEMENT_' . strtoupper($handle)] = $value;
		$tags['ELEMENT' . strtoupper($handle)] = $value; // legacy form, same as sendNotifications provides
		if (is_array($context['changed']) AND in_array($handle, $context['changed'])) {
			$tags['PREVIOUS_ELEMENT_' . strtoupper($handle)] = formulize_ruleReadableValue($context['before'][$handle], $handle, $context['entry_id']);
		} else {
			$tags['PREVIOUS_ELEMENT_' . strtoupper($handle)] = $value;
		}
	}
	return $tags;
}

/**
 * Swap tags into text: {TAG} for any of the notification tags, and {element_handle} for the readable value of an element
 * @param string $text
 * @param array $tags From formulize_ruleNotificationTags
 * @param array $readable From formulize_ruleReadableValues
 * @return string
 */
function formulize_ruleInterpolate($text, $tags, $readable, $outputs = array()) {
	$text = strval($text);
	if (strpos($text, '{') === false) {
		return $text;
	}
	return preg_replace_callback('/\{([A-Za-z0-9_]+)(?:\.([A-Za-z0-9_]+))?\}/', function ($m) use ($tags, $readable, $outputs) {
		$found = formulize_ruleOutputValue($outputs, $m[1], $m[2] ?? '');
		if ($found !== null) {
			return formulize_ruleOutputAsText($found);
		}
		if (!empty($m[2])) {
			return $m[0];
		}
		if (array_key_exists($m[1], $tags)) {
			return strval($tags[$m[1]]);
		}
		if (array_key_exists($m[1], $readable)) {
			return strval($readable[$m[1]]);
		}
		return $m[0];
	}, $text);
}

/**
 * Look up the output of an earlier step: {name}, or {name.key} for one part of an output that has parts (ie: an AI
 * reply in JSON)
 * @param array $outputs The outputs so far
 * @param string $name
 * @param string $key Optional
 * @return mixed The value, or null if there's no such output
 */
function formulize_ruleOutputValue($outputs, $name, $key = '') {
	if (!is_array($outputs) OR !array_key_exists($name, $outputs)) {
		return null;
	}
	$value = $outputs[$name];
	if ($key !== '') {
		if (is_array($value) AND isset($value['parts']) AND is_array($value['parts']) AND array_key_exists($key, $value['parts'])) {
			return $value['parts'][$key];
		}
		return null;
	}
	return $value;
}

/**
 * Turn an output into text, to put in a message or write to an element
 * @param mixed $value
 * @return string
 */
function formulize_ruleOutputAsText($value) {
	if (is_array($value)) {
		if (array_key_exists('text', $value)) {
			return strval($value['text']);
		}
		return implode(", ", array_map(function ($part) { return is_array($part) ? json_encode($part) : strval($part); }, $value));
	}
	return strval($value);
}

/**
 * Step: send a notification
 *
 * Settings:
 * - recipients: creator (1/0), curuser (1/0), groups (array of group ids), elementuids (element id of a user list),
 *   linkcreator (element id of a linked element: notify the creators of the selected entries), elementemail (element id
 *   holding email addresses), emails (text, comma separated addresses)
 * - subject: text, with tags
 * - message: text, with tags. If there's no message, then template is used, or the standard message for the event.
 * - template: a template file in the language's mail_template folder
 *
 * @param array $step
 * @param array $context
 * @return string
 */
function formulize_ruleStepNotify($step, $context) {
	global $xoopsDB;
	$fid = $context['fid'];
	$entryId = $context['entry_id'];
	$recipients = (array) ($step['recipients'] ?? array());
	$member_handler = xoops_gethandler('member');
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
	$uids = array();
	$emails = array();

	if (!empty($recipients['creator']) AND $context['owner']) {
		$uids[] = $context['owner'];
	}
	if (!empty($recipients['curuser']) AND $context['uid']) {
		$uids[] = $context['uid'];
	}
	foreach ((array) ($recipients['groups'] ?? array()) as $groupId) {
		if (intval($groupId) > 0) {
			$uids = array_merge($uids, (array) $member_handler->getUsersByGroup(intval($groupId)));
		}
	}
	if ($entryId AND !empty($recipients['elementuids']) AND $elementObject = $element_handler->get(intval($recipients['elementuids']))) {
		$value = $context['values'][$elementObject->getVar('ele_handle')] ?? '';
		if ($value) {
			$uids = array_merge($uids, array_filter(array_map('intval', explode("*=+*:", trim($value, "*=+*:")))));
		}
	}
	if ($entryId AND !empty($recipients['linkcreator']) AND $elementObject = $element_handler->get(intval($recipients['linkcreator']))) {
		$value = $context['values'][$elementObject->getVar('ele_handle')] ?? '';
		$linkedEntryIds = array_filter(array_map('intval', explode(",", trim(strval($value), ","))));
		if ($linkedEntryIds AND $elementObject->isLinked) {
			$ele_value = $elementObject->getVar('ele_value');
			$linkProperties = explode("#*=:*", $ele_value[2]);
			$sourceDataHandler = new formulizeDataHandler($linkProperties[0]);
			$uids = array_merge($uids, (array) $sourceDataHandler->getAllUsersForEntries($linkedEntryIds));
		}
	}
	if ($entryId AND !empty($recipients['elementemail']) AND $elementObject = $element_handler->get(intval($recipients['elementemail']))) {
		$value = $context['values'][$elementObject->getVar('ele_handle')] ?? '';
		foreach (explode(",", strval($value)) as $email) {
			$emails[] = $email;
		}
	}
	foreach (preg_split('/[\s,;]+/', strval($recipients['emails'] ?? '')) as $email) {
		$emails[] = $email;
	}
	$emails = array_values(array_unique(array_filter(array_map('trim', $emails), function ($email) {
		return $email AND substr($email, -9) != ".archived" AND filter_var($email, FILTER_VALIDATE_EMAIL);
	})));

	// only notify users who are allowed to see the entry, the same as all other notifications
	$uids = array_unique(array_filter(array_map('intval', $uids)));
	$outOfScope = 0;
	if ($uids AND $entryId) {
		$scopeUids = formulize_getUidsInNotificationScope($fid, $entryId, null, $context['owner_groups'], $context['deleted'] ? $context['owner'] : null);
		$inScope = array_values(array_intersect($uids, $scopeUids));
		$outOfScope = count($uids) - count($inScope);
		$uids = $inScope;
	}
	if (empty($uids) AND empty($emails)) {
		return "nobody to notify" . ($outOfScope ? " ($outOfScope recipient(s) can't see this entry)" : "") . ".";
	}

	$tags = formulize_ruleNotificationTags($context);
	$readable = formulize_ruleReadableValues($context);
	$subject = formulize_ruleInterpolate(trim(strval($step['subject'] ?? '')), $tags, $readable, $context['outputs'] ?? array());
	$message = trim(strval($step['message'] ?? ''));
	$template = trim(str_replace('.tpl', '', strval($step['template'] ?? '')));
	if ($message !== '') {
		$tags['EVENTMESSAGE'] = formulize_ruleInterpolate($message, $tags, $readable, $context['outputs'] ?? array());
		$template = 'form_rule';
	}
	if ($subject === '' AND $template === 'form_rule') {
		$subject = $tags['FORMSINGULAR'] . ": " . ($context['rule']['name'] ?? '');
	}

	// the notification system needs one of the module's notification events. Button clicks use the update event, with the rule's own subject and message.
	$notEvent = in_array($context['event'], array('new_entry', 'update_entry', 'delete_entry')) ? $context['event'] : 'update_entry';
	$mid = getFormulizeModId();
	if ($uids) {
		subscribeUidsToEvent($uids, $fid, $notEvent);
	}
	$uidsToNotify = $uids;
	if ($emails) {
		$GLOBALS['formulize_notification_email'] = implode(",", $emails);
		$uidsToNotify[] = -1; // means: also send to the addresses in $GLOBALS['formulize_notification_email']
	}
	formulize_processNotification($notEvent, $tags, $fid, $uidsToNotify, $mid, 0, $subject, $template);
	unset($GLOBALS['formulize_notification_email']);

	$who = array();
	if ($uids) {
		$names = array();
		foreach (array_slice($uids, 0, 10) as $uid) {
			if ($user = $member_handler->getUser($uid)) {
				$names[] = $user->getVar('uname');
			}
		}
		$who[] = count($uids) . " user(s) (" . implode(", ", $names) . (count($uids) > 10 ? ", ..." : "") . ")";
	}
	if ($emails) {
		$who[] = implode(", ", $emails);
	}
	return "sent to " . implode(" and ", $who) . ($outOfScope ? "; skipped $outOfScope recipient(s) who can't see this entry" : "") . ".";
}

/**
 * Work out the value to write to an element, from a value in a rule
 *
 * - {handle} on its own copies the stored value from the entry the event is about (useful between elements of the same type)
 * - {ID} is the id of the entry the event is about
 * - {handle} inside other text is swapped for the stored value
 * - {USER}, {TODAY} etc. work as they do in other places
 * - Text that matches an option of the element is converted to the stored value for that option (ie: for linked elements)
 *
 * @param string $value The value from the rule
 * @param object $elementObject The element the value will be written to
 * @param array $context The context of the event
 * @return mixed The value to write
 */
function formulize_ruleResolveValue($value, $elementObject, $context) {
	$value = strval($value);
	$outputs = $context['outputs'] ?? array();
	if (preg_match('/^\{([A-Za-z0-9_]+)(?:\.([A-Za-z0-9_]+))?\}$/', $value, $m)) {
		$found = formulize_ruleOutputValue($outputs, $m[1], $m[2] ?? '');
		if ($found !== null) {
			$value = formulize_ruleOutputAsText($found);
			// an output is text (ie: an AI reply), so it still goes through the option matching below
			$prepared = $value === '' ? $value : prepareLiteralTextForDB($elementObject, $value);
			if (is_array($prepared)) {
				$prepared = reset($prepared);
			}
			return ($prepared === false OR $prepared === null) ? $value : $prepared;
		}
		if (empty($m[2]) AND $m[1] == 'ID') {
			return $context['entry_id'];
		}
		if (empty($m[2]) AND array_key_exists($m[1], $context['values']) AND !isMetaDataField($m[1])) {
			return $context['values'][$m[1]];
		}
	}
	$value = preg_replace_callback('/\{([A-Za-z0-9_]+)(?:\.([A-Za-z0-9_]+))?\}/', function ($m) use ($context, $outputs) {
		$found = formulize_ruleOutputValue($outputs, $m[1], $m[2] ?? '');
		if ($found !== null) {
			return formulize_ruleOutputAsText($found);
		}
		if (!empty($m[2])) {
			return $m[0];
		}
		if ($m[1] == 'ID') {
			return strval($context['entry_id']);
		}
		if (array_key_exists($m[1], $context['values'])) {
			return strval($context['values'][$m[1]]);
		}
		return $m[0];
	}, $value);
	if ($value === '') {
		return $value;
	}
	$prepared = prepareLiteralTextForDB($elementObject, $value);
	if (is_array($prepared)) {
		$prepared = reset($prepared);
	}
	return ($prepared === false OR $prepared === null) ? $value : $prepared;
}

/**
 * Work out the values to write, for update and create steps
 * @param array $valueSettings A list of ['element' => handle, 'value' => text, 'mode' => replace|append]
 * @param int $targetFid The form the values are for
 * @param array $context The context of the event
 * @param int $targetEntryId The entry being updated, for appending, or 'new'
 * @return array Handle => value, ready to write
 */
function formulize_ruleValuesToWrite($valueSettings, $targetFid, $context, $targetEntryId = 'new') {
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
	$values = array();
	$currentRow = null;
	foreach ((array) $valueSettings as $setting) {
		if (empty($setting['element']) OR !$elementObject = $element_handler->get($setting['element'])) {
			continue;
		}
		if ($elementObject->getVar('id_form') != $targetFid) {
			continue;
		}
		$handle = $elementObject->getVar('ele_handle');
		$value = formulize_ruleResolveValue($setting['value'] ?? '', $elementObject, $context);
		if (($setting['mode'] ?? 'replace') == 'append' AND is_numeric($targetEntryId)) {
			if ($currentRow === null) {
				$currentRow = formulize_ruleGetEntryRow($targetFid, $targetEntryId);
			}
			$value = strval($currentRow[$handle] ?? '') . strval($value);
		}
		$values[$handle] = ($value === '') ? null : $value;
	}
	return $values;
}

/**
 * Step: update entries
 *
 * Settings:
 * - target: 'this' for the entry the event is about, or 'matching' for entries in target_fid that match target_conditions
 * - target_fid: the form to update, when the target is 'matching'
 * - target_conditions: conditions for which entries to update. {handle} terms refer to the entry the event is about, and {ID} to its id.
 * - values: a list of ['element' => handle, 'value' => text, 'mode' => replace|append]
 *
 * @param array $step
 * @param array $context
 * @return string
 */
function formulize_ruleStepUpdateEntries($step, $context) {
	global $xoopsDB;
	$target = $step['target'] ?? 'this';
	if ($target == 'this') {
		if (!$context['entry_id'] OR $context['deleted']) {
			return "there is no entry to update" . ($context['deleted'] ? ", since it was deleted." : ".");
		}
		$targetFid = $context['fid'];
		$targetEntries = array($context['entry_id']);
	} else {
		$targetFid = intval($step['target_fid'] ?? 0);
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		if (!$targetFid OR !$targetFormObject = $form_handler->get($targetFid)) {
			return "the form to update doesn't exist.";
		}
		$targetEntries = formulize_ruleFindEntries($targetFormObject, (array) ($step['target_conditions'] ?? array()), $context);
		if ($targetEntries === false) {
			return "the conditions for which entries to update could not be used (check that their elements still exist), so nothing was updated.";
		}
	}
	if (empty($targetEntries)) {
		return "no entries matched.";
	}
	$capped = false;
	if (count($targetEntries) > FORMULIZE_RULE_MAX_TARGET_ENTRIES) {
		$targetEntries = array_slice($targetEntries, 0, FORMULIZE_RULE_MAX_TARGET_ENTRIES);
		$capped = true;
	}
	$changed = array();
	foreach ($targetEntries as $targetEntryId) {
		$values = formulize_ruleValuesToWrite($step['values'] ?? array(), $targetFid, $context, $targetEntryId);
		if (empty($values)) {
			return "no values are set up to write.";
		}
		if (formulize_writeEntry($values, $targetEntryId, 'replace', false, true)) {
			formulize_updateDerivedValues($targetEntryId, $targetFid);
			$changed[] = $targetEntryId;
		}
	}
	if ($changed) {
		formulize_dispatchEntryEvent($targetFid, 'update_entry', $changed);
	}
	$formTitle = formulize_ruleFormTitle($targetFid);
	$outcome = "updated " . count($changed) . " of " . count($targetEntries) . " matching entr" . (count($targetEntries) == 1 ? "y" : "ies") . " in $formTitle"
		. ($changed ? " (" . implode(", ", array_slice($changed, 0, 20)) . (count($changed) > 20 ? ", ..." : "") . ")" : "")
		. ($capped ? "; stopped at " . FORMULIZE_RULE_MAX_TARGET_ENTRIES . " entries" : "") . ".";
	return array('outcome' => $outcome, 'output' => implode(",", $changed));
}

/**
 * Find the entries in a form that match conditions, where {handle} terms in the conditions refer to the entry the
 * event is about. The terms are swapped for that entry's values first, so this works even when it has been deleted.
 * @param object $targetFormObject The form to look in
 * @param array $conditions The conditions
 * @param array $context The context of the event
 * @return array|false The entry ids, or false if the conditions can't be used
 */
function formulize_ruleFindEntries($targetFormObject, $conditions, $context) {
	global $xoopsDB, $xoopsUser;
	$targetFid = $targetFormObject->getVar('id_form');
	$table = $xoopsDB->prefix("formulize_" . $targetFormObject->getVar('form_handle'));
	$where = "";
	$from = "";
	if (!empty($conditions[0])) {
		if (!formulize_ruleConditionsAreValid($conditions)) {
			return false;
		}
		foreach ((array) $conditions[2] as $i => $term) {
			if (preg_match('/^\{([A-Za-z0-9_]+)(?:\.([A-Za-z0-9_]+))?\}$/', strval($term), $m)) {
				$found = formulize_ruleOutputValue($context['outputs'] ?? array(), $m[1], $m[2] ?? '');
				if ($found !== null) {
					$conditions[2][$i] = formulize_ruleOutputAsText($found);
				} elseif (!empty($m[2])) {
					continue;
				} elseif ($m[1] == 'ID') {
					$conditions[2][$i] = strval($context['entry_id']);
				} elseif (array_key_exists($m[1], $context['values']) AND !isMetaDataField($m[1])) {
					$conditions[2][$i] = strval($context['values'][$m[1]]);
				}
			}
		}
		$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
		list($conditionsfilter, $conditionsfilter_oom, $from) = buildConditionsFilterSQL($conditions, $targetFid, 'new', $uid, $targetFormObject, 't1');
		if (trim((string) $conditionsfilter) === '' AND trim((string) $conditionsfilter_oom) === '') {
			return false;
		}
		$where = "$conditionsfilter $conditionsfilter_oom";
	}
	$entries = array();
	$sql = "SELECT t1.entry_id FROM $table AS t1 $from WHERE 1 $where ORDER BY t1.entry_id LIMIT " . (FORMULIZE_RULE_MAX_TARGET_ENTRIES + 1);
	if ($res = $xoopsDB->query($sql)) {
		while ($row = $xoopsDB->fetchRow($res)) {
			$entries[] = intval($row[0]);
		}
	}
	return $entries;
}

/**
 * Step: create an entry
 *
 * Settings:
 * - target_fid: the form to create the entry in
 * - values: a list of ['element' => handle, 'value' => text]. {ID} in a linked element makes the new entry point to the entry the event is about.
 * - owner: 'current' for the user who triggered the event (the default), or 'entry_owner' for the owner of the entry the event is about
 *
 * @param array $step
 * @param array $context
 * @return string
 */
function formulize_ruleStepCreateEntry($step, $context) {
	$targetFid = intval($step['target_fid'] ?? 0);
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	if (!$targetFid OR !$form_handler->get($targetFid)) {
		return "the form to create the entry in doesn't exist.";
	}
	$values = formulize_ruleValuesToWrite($step['values'] ?? array(), $targetFid, $context);
	if (empty($values)) {
		return "no values are set up for the new entry, so it was not created.";
	}
	$owner = (($step['owner'] ?? 'current') == 'entry_owner' AND $context['owner']) ? $context['owner'] : false;
	if (!$newEntryId = formulize_writeEntry($values, 'new', 'replace', $owner, true)) {
		return "the entry could not be created.";
	}
	formulize_updateDerivedValues($newEntryId, $targetFid);
	formulize_dispatchEntryEvent($targetFid, 'new_entry', array($newEntryId));
	return array('outcome' => "created entry $newEntryId in " . formulize_ruleFormTitle($targetFid) . ".", 'output' => $newEntryId);
}

/**
 * The title of a form, for outcome messages
 * @param int $fid
 * @return string
 */
function formulize_ruleFormTitle($fid) {
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$formObject = $form_handler->get($fid);
	return $formObject ? trans($formObject->getVar('title')) : "form $fid";
}

/**
 * The name of an event, as sent to webhooks
 * @param string $event
 * @return string
 */
function formulize_ruleWebhookEventName($event) {
	$names = array(
		'new_entry' => 'entry.created',
		'update_entry' => 'entry.updated',
		'delete_entry' => 'entry.deleted',
		'button_click' => 'button.clicked',
	);
	return $names[$event] ?? $event;
}

/**
 * Build the description of an event that webhooks receive
 * @param array $context The context of the event
 * @param bool $includeData Whether to include the entry's values
 * @return array
 */
function formulize_ruleWebhookPayload($context, $includeData = true) {
	global $xoopsUser;
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$formObject = $form_handler->get($context['fid']);
	$payload = array(
		'event' => formulize_ruleWebhookEventName($context['event']),
		'occurred_at' => gmdate('Y-m-d\TH:i:s\Z'),
		'site' => XOOPS_URL,
		'form' => array(
			'id' => intval($context['fid']),
			'handle' => $formObject->getVar('form_handle'),
			'title' => trans($formObject->getVar('title')),
		),
		'entry_id' => intval($context['entry_id']),
		'rule' => array(
			'id' => intval($context['rule']['rule_id']),
			'name' => $context['rule']['name'],
		),
		'triggered_by' => array(
			'uid' => intval($context['uid']),
			'name' => $xoopsUser ? ($xoopsUser->getVar('name') ? $xoopsUser->getVar('name') : $xoopsUser->getVar('uname')) : '',
		),
	);
	if ($context['event'] == 'button_click' AND isset($context['rule']['trigger_settings']['button'])) {
		$payload['button'] = $context['rule']['trigger_settings']['button'];
	}
	if ($includeData AND $context['entry_id']) {
		$payload['entry'] = formulize_ruleReadableValues($context);
		$payload['entry_raw'] = $context['values'];
		if ($context['event'] == 'update_entry' AND is_array($context['changed'])) {
			$payload['changed'] = $context['changed'];
			$payload['previous'] = array();
			foreach ($context['changed'] as $handle) {
				$payload['previous'][$handle] = $context['before'][$handle] ?? null;
			}
		}
	}
	return $payload;
}

/**
 * Step: send a webhook. The delivery goes in the outbox, and is sent at the end of the request, and retried later if it fails.
 *
 * Settings:
 * - url: where to POST
 * - secret: optional, used to sign the request so the receiver can check it came from this site
 * - include_data: 1 to include the entry's values (the default), 0 to only say what happened
 *
 * @param array $step
 * @param array $context
 * @return string
 */
function formulize_ruleStepWebhook($step, $context) {
	global $xoopsDB;
	$url = trim(strval($step['url'] ?? ''));
	$scheme = strtolower(strval(parse_url($url, PHP_URL_SCHEME)));
	if (!$url OR !in_array($scheme, array('http', 'https')) OR !parse_url($url, PHP_URL_HOST)) {
		return "the URL '" . $url . "' is not a valid http or https address, so nothing was sent.";
	}
	$payload = formulize_ruleWebhookPayload($context, !isset($step['include_data']) OR $step['include_data']);
	$outboxId = formulize_ruleQueueWebhook($context, $url, $payload, strval($step['secret'] ?? ''));
	if (!$outboxId) {
		return "could not be queued: " . $xoopsDB->error();
	}
	$GLOBALS['formulize_ruleWebhooksThisRequest'][] = $outboxId;
	formulize_rulesEnsureShutdown();
	return array('outcome' => "queued delivery $outboxId to $url.", 'output' => $outboxId);
}

/**
 * Put a webhook delivery in the outbox. The body and headers are worked out now, so retries send exactly the same thing.
 * @param array $context The context of the event
 * @param string $url Where to send it
 * @param array $payload The description of the event
 * @param string $secret Optional. The secret to sign it with.
 * @return int|false The delivery id
 */
function formulize_ruleQueueWebhook($context, $url, $payload, $secret = '') {
	global $xoopsDB;
	$table = $xoopsDB->prefix('formulize_rule_outbox');
	$sql = "INSERT INTO $table (rule_id, fid, entry_id, event, channel, target, payload, status, attempts, created_at, next_attempt_at) VALUES ("
		. intval($context['rule']['rule_id']) . ", " . intval($context['fid']) . ", " . intval($context['entry_id']) . ", '" . formulize_db_escape($payload['event']) . "', 'webhook', '"
		. formulize_db_escape($url) . "', '', 'pending', 0, NOW(), NOW())";
	if (!$xoopsDB->queryF($sql)) {
		return false;
	}
	$outboxId = intval($xoopsDB->getInsertId());
	$payload['delivery_id'] = $outboxId;
	$body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
	$timestamp = time();
	$headers = array(
		'Content-Type' => 'application/json',
		'User-Agent' => 'Formulize-Webhooks/1.0',
		'X-Formulize-Event' => $payload['event'],
		'X-Formulize-Delivery' => strval($outboxId),
		'X-Formulize-Timestamp' => strval($timestamp),
	);
	if ($secret !== '') {
		$headers['X-Formulize-Signature'] = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);
	}
	$stored = json_encode(array('headers' => $headers, 'body' => $body), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	$xoopsDB->queryF("UPDATE $table SET payload = '" . formulize_db_escape($stored) . "' WHERE outbox_id = $outboxId");
	return $outboxId;
}

/**
 * Send webhooks waiting in the outbox: ones created during this request, and ones due for a retry.
 * @param int $limit The most to send in one go
 * @param array $onlyIds Optional. Only these outbox items, whether they are due or not (ie: "send now" in the admin UI)
 * @return array Outbox id => status after trying
 */
function formulize_ruleProcessOutbox($limit = 20, $onlyIds = null) {
	global $xoopsDB;
	$table = $xoopsDB->prefix('formulize_rule_outbox');
	$results = array();
	if ($onlyIds !== null) {
		$onlyIds = array_filter(array_map('intval', (array) $onlyIds));
		if (!$onlyIds) {
			return $results;
		}
		$where = "outbox_id IN (" . implode(",", $onlyIds) . ") AND status IN ('pending', 'sending', 'failed')";
	} else {
		$where = "status IN ('pending', 'sending') AND next_attempt_at <= NOW()";
	}
	$ids = array();
	if ($res = $xoopsDB->query("SELECT outbox_id FROM $table WHERE $where ORDER BY outbox_id LIMIT " . intval($limit))) {
		while ($row = $xoopsDB->fetchRow($res)) {
			$ids[] = intval($row[0]);
		}
	}
	foreach ($ids as $outboxId) {
		// claim it, so another request processing the outbox at the same time doesn't send it too
		$xoopsDB->queryF("UPDATE $table SET status = 'sending', next_attempt_at = DATE_ADD(NOW(), INTERVAL 2 MINUTE) WHERE outbox_id = $outboxId AND (" . ($onlyIds !== null ? "1" : "next_attempt_at <= NOW()") . ") AND status IN ('pending', 'sending', 'failed')");
		if ($xoopsDB->getAffectedRows() < 1) {
			continue;
		}
		$results[$outboxId] = formulize_ruleOutboxChannel($outboxId) == 'rule' ? formulize_ruleRunJob($outboxId) : formulize_ruleSendWebhook($outboxId);
	}
	// keep the outbox and the activity log from growing forever
	if (mt_rand(1, 20) == 1) {
		$xoopsDB->queryF("DELETE FROM $table WHERE status = 'delivered' AND delivered_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
		$xoopsDB->queryF("DELETE FROM " . $xoopsDB->prefix('formulize_rule_log') . " WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
	}
	return $results;
}

/**
 * Which kind of item in the outbox this is: 'webhook' or 'rule' (a rule queued to run in the background)
 * @param int $outboxId
 * @return string
 */
function formulize_ruleOutboxChannel($outboxId) {
	global $xoopsDB;
	$res = $xoopsDB->query("SELECT channel FROM " . $xoopsDB->prefix('formulize_rule_outbox') . " WHERE outbox_id = " . intval($outboxId));
	$row = $res ? $xoopsDB->fetchRow($res) : false;
	return $row ? $row[0] : '';
}

/**
 * Send one delivery from the outbox, and record what happened
 * @param int $outboxId
 * @return string The status afterwards: delivered, pending (will retry) or failed
 */
function formulize_ruleSendWebhook($outboxId) {
	global $xoopsDB;
	$table = $xoopsDB->prefix('formulize_rule_outbox');
	$res = $xoopsDB->query("SELECT * FROM $table WHERE outbox_id = " . intval($outboxId));
	if (!$res OR !$row = $xoopsDB->fetchArray($res)) {
		return 'missing';
	}
	$stored = json_decode(strval($row['payload']), true);
	$headerLines = array();
	foreach ((array) ($stored['headers'] ?? array()) as $name => $value) {
		$headerLines[] = "$name: $value";
	}
	$code = 0;
	$error = '';
	if (!function_exists('curl_init')) {
		$error = 'The curl extension is not available in PHP on this server.';
	} else {
		$ch = curl_init($row['target']);
		curl_setopt_array($ch, array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => strval($stored['body'] ?? ''),
			CURLOPT_HTTPHEADER => $headerLines,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 3,
			CURLOPT_TIMEOUT => 5,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
		));
		$response = curl_exec($ch);
		$code = intval(curl_getinfo($ch, CURLINFO_RESPONSE_CODE));
		if ($response === false) {
			$error = curl_error($ch);
		} elseif ($code < 200 OR $code >= 300) {
			$error = "HTTP $code: " . substr(strval($response), 0, 500);
		}
		curl_close($ch);
	}
	$attempts = intval($row['attempts']) + 1;
	if ($error === '') {
		$status = 'delivered';
		$sql = "UPDATE $table SET status = 'delivered', attempts = $attempts, response_code = $code, last_error = NULL, delivered_at = NOW(), next_attempt_at = NULL WHERE outbox_id = " . intval($outboxId);
	} else {
		// back off: 1 minute, 5 minutes, 30 minutes, 2 hours, 12 hours, then give up
		$backoffMinutes = array(1 => 1, 2 => 5, 3 => 30, 4 => 120, 5 => 720);
		$status = ($attempts >= FORMULIZE_RULE_WEBHOOK_MAX_ATTEMPTS) ? 'failed' : 'pending';
		$next = $status == 'pending' ? "DATE_ADD(NOW(), INTERVAL " . intval($backoffMinutes[$attempts] ?? 720) . " MINUTE)" : "NULL";
		$sql = "UPDATE $table SET status = '$status', attempts = $attempts, response_code = $code, last_error = '" . formulize_db_escape($error) . "', next_attempt_at = $next WHERE outbox_id = " . intval($outboxId);
	}
	$xoopsDB->queryF($sql);
	return $status;
}

/**
 * Turn a notification set up in the notifications popup (a row in formulize_notification_conditions) into an event
 * rule, and remove the popup's version so it isn't sent twice.
 * @param int $notConsId The id of the row in formulize_notification_conditions
 * @param int $fid The form, as a check that the row belongs to it
 * @return int|false The new rule id
 */
function formulize_convertPopupNotificationToRule($notConsId, $fid) {
	global $xoopsDB;
	$table = $xoopsDB->prefix('formulize_notification_conditions');
	$res = $xoopsDB->query("SELECT * FROM $table WHERE not_cons_id = " . intval($notConsId) . " AND not_cons_fid = " . intval($fid) . " AND not_cons_uid = 0");
	if (!$res OR !$row = $xoopsDB->fetchArray($res)) {
		return false;
	}
	$handler = xoops_getmodulehandler('rules', 'formulize');
	$rule = $handler->create($fid);
	$rule['trigger_type'] = in_array($row['not_cons_event'], array('new_entry', 'update_entry', 'delete_entry')) ? $row['not_cons_event'] : 'new_entry';
	$recipients = array();
	if ($row['not_cons_curuser']) { $recipients['curuser'] = 1; }
	if ($row['not_cons_creator']) { $recipients['creator'] = 1; }
	if ($row['not_cons_groupid']) { $recipients['groups'] = array(intval($row['not_cons_groupid'])); }
	if ($row['not_cons_elementuids']) { $recipients['elementuids'] = intval($row['not_cons_elementuids']); }
	if ($row['not_cons_linkcreator']) { $recipients['linkcreator'] = intval($row['not_cons_linkcreator']); }
	if ($row['not_cons_elementemail']) { $recipients['elementemail'] = intval($row['not_cons_elementemail']); }
	if ($row['not_cons_arbitrary']) { $recipients['emails'] = htmlspecialchars_decode($row['not_cons_arbitrary'], ENT_QUOTES); }
	if ($row['not_cons_con'] !== 'all' AND $cons = unserialize($row['not_cons_con'])) {
		$elements = (array) unserialize($cons[0]);
		$ops = (array) unserialize($cons[1]);
		$terms = (array) unserialize($cons[2]);
		$conditions = array(array(), array(), array(), array());
		foreach (array_values($elements) as $i => $element) {
			$conditions[0][] = $element;
			$conditions[1][] = formulize_conditionsCleanOps($ops[$i] ?? '=') ?: '=';
			$conditions[2][] = $terms[$i] ?? '';
			$conditions[3][] = 'all';
		}
		$rule['conditions'] = $conditions;
		if ($rule['trigger_type'] == 'update_entry') {
			// the popup's update notifications only go out when one of the elements in their conditions changed
			$element_handler = xoops_getmodulehandler('elements', 'formulize');
			$watch = array();
			foreach ($conditions[0] as $element) {
				if (isMetaDataField($element)) { continue; }
				if ($elementObject = $element_handler->get($element)) {
					$watch[] = $elementObject->getVar('ele_handle');
				}
			}
			$rule['trigger_settings']['watch'] = array_values(array_unique($watch));
		}
	}
	$rule['steps'] = array(array(
		'type' => 'notify',
		'recipients' => $recipients,
		'subject' => $row['not_cons_subject'],
		'message' => '',
		'template' => $row['not_cons_template'],
	));
	$triggerTypes = formulizeRulesHandler::triggerTypes();
	$rule['name'] = 'Notification: ' . strtolower($triggerTypes[$rule['trigger_type']]);
	if (!$ruleId = $handler->insert($rule)) {
		return false;
	}
	$xoopsDB->queryF("DELETE FROM $table WHERE not_cons_id = " . intval($notConsId));
	return $ruleId;
}

/**
 * Run a rule for an entry, as if its event had just happened, for trying out a rule from its editor. The conditions are
 * checked, and the steps really happen. For delete rules, the entry is not deleted, but the rule sees it as deleted.
 * @param array $rule The rule
 * @param int $entryId The entry
 * @return string What happened
 */
function formulize_testRule($rule, $entryId) {
	if (!$rule) {
		return "The rule doesn't exist.";
	}
	$fid = $rule['fid'];
	$entryId = intval($entryId);
	$events = array('new_entry' => 'new_entry', 'update_entry' => 'update_entry', 'save_entry' => 'update_entry', 'delete_entry' => 'delete_entry', 'button_click' => 'button_click');
	$event = $events[$rule['trigger_type']] ?? 'update_entry';
	if (!$row = formulize_ruleGetEntryRow($fid, $entryId)) {
		return "There is no entry $entryId in " . formulize_ruleFormTitle($fid) . ".";
	}
	if (!formulize_ruleEntryMatchesConditions($rule['conditions'], $fid, $entryId)) {
		return "Entry $entryId doesn't match the rule's conditions, so nothing happened.";
	}
	$record = null;
	if ($event == 'delete_entry') {
		$data_handler = new formulizeDataHandler($fid);
		$record = array('event' => 'delete_entry', 'snapshot' => $row, 'owner' => intval($row['creation_uid']), 'owner_groups' => $data_handler->getEntryOwnerGroups($entryId));
	}
	if ($rule['trigger_type'] == 'before_save') {
		return "Before Save rules run while an entry is being saved, so they can't be tried this way. Save an entry to try it, and look at the Activity tab.";
	}
	$context = formulize_ruleBuildContext($fid, $entryId, $event, $record);
	$GLOBALS['formulize_ruleRunNow'] = true; // run steps that would normally run in the background (Ask AI) now, so the result can be seen
	$outcomes = formulize_runRule($rule, $context);
	unset($GLOBALS['formulize_ruleRunNow']);
	formulize_ruleProcessOutbox(10, $GLOBALS['formulize_ruleWebhooksThisRequest'] ?? array());
	unset($GLOBALS['formulize_ruleWebhooksThisRequest']);
	return "Ran the rule for entry $entryId:\n\n" . implode("\n", $outcomes);
}

/**
 * A step failed in a way that means the rest of the rule shouldn't run (ie: the AI step failed, so there's no reply
 * for the later steps to write anywhere)
 */
class FormulizeRuleStopException extends Exception {}

/**
 * Whether AI rules are turned on in Settings, AI
 * @return bool
 */
function formulize_aiInRulesEnabled() {
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/aiadminconfig.php";
	return formulizeAI_preference('formulizeAIRulesEnabled', 0) == 1;
}

/**
 * Step: ask AI. Sends a prompt (and the entry, if asked) to the site's AI model, and saves the reply as the step's
 * output, for later steps to use as {name} (or {name.key} for one part of a JSON reply).
 *
 * Settings:
 * - prompt: what to ask, with tags ({element_handle}, outputs of earlier steps, etc.)
 * - include_entry: 1 to add the entry's values after the prompt (the default)
 * - json: 1 to ask for a JSON object, so its parts can be used separately as {name.key}
 * - max_tokens: the longest reply allowed (default 1024)
 * - output: the name to save the reply under
 *
 * @param array $step
 * @param array $context
 * @return array
 */
function formulize_ruleStepAI($step, $context) {
	if (!formulize_aiInRulesEnabled()) {
		throw new FormulizeRuleStopException("AI in rules is turned off. Turn it on in Settings, AI.");
	}
	include_once XOOPS_ROOT_PATH . "/ai/ai_providers.php";
	$tags = formulize_ruleNotificationTags($context);
	$readable = formulize_ruleReadableValues($context);
	$prompt = formulize_ruleInterpolate(trim(strval($step['prompt'] ?? '')), $tags, $readable, $context['outputs'] ?? array());
	if ($prompt === '') {
		throw new FormulizeRuleStopException("there is no prompt.");
	}
	if (!isset($step['include_entry']) OR $step['include_entry']) {
		$lines = array();
		$labels = formulize_ruleElementLabels($context['fid']);
		foreach ($readable as $handle => $value) {
			if ($value !== '') {
				$lines[] = ($labels[$handle] ?? $handle) . " ($handle): " . $value;
			}
		}
		$prompt .= "\n\nThe " . strtolower($tags['FORMSINGULAR']) . ($context['entry_id'] ? " (entry " . $context['entry_id'] . ")" : "") . ":\n" . ($lines ? implode("\n", $lines) : "(no values)");
	}
	$system = "You are working with data in Formulize, a database application. The form is called " . $tags['FORMNAME'] . ". "
		. "Your reply is used directly by an automated process, ie: written into a field, so reply with only what is asked for, with no introduction, explanation or sign-off.";
	$json = !empty($step['json']);
	try {
		$reply = formulizeAI_complete($system, $prompt, array('json' => $json, 'max_tokens' => intval($step['max_tokens'] ?? 0) ?: 1024));
	} catch (FormulizeAIException $e) {
		if ($e->isRetryable()) {
			throw new FormulizeRuleRetryableException($e->getMessage());
		}
		throw new FormulizeRuleStopException($e->getMessage());
	}
	$text = trim($reply['text']);
	$output = $text;
	$note = '';
	if ($json) {
		// models sometimes wrap JSON in a code fence anyway
		$decoded = json_decode(preg_replace('/^`{3}(?:json)?\s*|\s*`{3}$/', '', $text), true);
		if (is_array($decoded)) {
			$output = array('text' => $text, 'parts' => $decoded);
			$note = " The reply is JSON with these parts: " . implode(", ", array_keys($decoded)) . ".";
		} else {
			$note = " The reply was supposed to be JSON but isn't, so it is saved as plain text.";
		}
	}
	$preview = mb_substr(preg_replace('/\s+/', ' ', $text), 0, 160);
	$cutOff = in_array($reply['stop_reason'], array('max_tokens', 'length', 'MAX_TOKENS'));
	return array(
		'outcome' => "asked " . $reply['provider'] . " (" . $reply['model'] . "), got " . mb_strlen($text) . " characters: \"" . $preview . (mb_strlen($text) > 160 ? "..." : "") . "\"" . ($cutOff ? " (cut off at the length limit)" : "") . "." . $note,
		'output' => $output,
	);
}

/**
 * The captions of the elements in a form, by handle
 * @param int $fid
 * @return array
 */
function formulize_ruleElementLabels($fid) {
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$labels = array();
	if ($formObject = $form_handler->get($fid, true)) {
		$captions = $formObject->getVar('elementCaptions');
		$colheads = $formObject->getVar('elementColheads');
		foreach ((array) $formObject->getVar('elementHandles') as $elementId => $handle) {
			$labels[$handle] = trans(strip_tags(strval($colheads[$elementId] ? $colheads[$elementId] : $captions[$elementId])));
		}
	}
	return $labels;
}

/**
 * The variables a Run PHP code step's code has, the same as the procedures on the Procedures tab have:
 * each element's value as a variable named after its handle, $entry_id, $form_id, $currentValues (the values before an
 * update), $newEntry, and $formulize_element_values (all the values in an array). Also $outputs, with the outputs of
 * earlier steps, which are also variables of their own, $event and $user_id.
 *
 * The element variables hold the values either as stored in the database (the default, the same as procedures: a
 * linked element gives the entry id it points to), or as people see them (the same as derived values: a linked element
 * gives the text shown, and an element with several values gives an array). $entry is the entry's record from
 * gatherDataset, for getValue() and display(), the same as in derived values. The record comes from the same cache the
 * derived value update just used, when it used the same relationship (none), so it usually costs nothing.
 *
 * @param array $context
 * @param string $valuesAs 'stored' or 'readable'
 * @param bool $includeEntryRecord Whether to provide $entry (always provided for 'readable')
 * @return array Name => value
 */
function formulize_ruleCodeVariables($context, $valuesAs = 'stored', $includeEntryRecord = false) {
	$variables = array();
	$record = array();
	$readable = ($valuesAs == 'readable' AND $context['event'] != 'before_save' AND $context['entry_id']);
	if (($readable OR $includeEntryRecord) AND empty($context['deleted']) AND is_numeric($context['entry_id']) AND $context['entry_id']) {
		include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
		$data = gatherDataset($context['fid'], filter: intval($context['entry_id']), frid: 0);
		$record = isset($data[0]) ? $data[0] : array();
	}
	foreach ((array) $context['values'] as $handle => $value) {
		if ($readable AND !isMetaDataField($handle) AND $handle != 'entry_id') {
			$value = $record ? getValue($record, $handle) : formulize_ruleReadableValue($value, $handle, $context['entry_id']);
		}
		$variables[is_numeric($handle) ? 'elementId' . $handle : $handle] = $value;
	}
	$variables['entry'] = $record;
	foreach ((array) ($context['outputs'] ?? array()) as $name => $value) {
		if (!array_key_exists($name, $variables)) {
			$variables[$name] = $value;
		}
	}
	$variables['entry_id'] = $context['entry_id'];
	$variables['form_id'] = $context['fid'];
	$variables['currentValues'] = !empty($context['before']) ? $context['before'] : $context['values'];
	// which elements changed in this save, since the element variables hold the whole entry, not only what was submitted
	$variables['changedElements'] = is_array($context['changed'] ?? null) ? $context['changed'] : array();
	$variables['newEntry'] = ($context['event'] == 'new_entry' OR ($context['event'] == 'before_save' AND !is_numeric($context['entry_id'])));
	$variables['formulize_element_values'] = $context['values'];
	$variables['outputs'] = $context['outputs'] ?? array();
	$variables['event'] = $context['event'];
	$variables['user_id'] = $context['uid'];
	return $variables;
}

/**
 * Run a piece of PHP code from a rule. The code is written to a file in the cache folder, named after a hash of the
 * code, wrapped in a function, so errors report a file and line, and a changed rule can never run stale code.
 * @param string $code The code
 * @param array $variables The variables the code gets
 * @return mixed false if the code returned false; the code's own return value if it returned something else; otherwise
 *         array('formulize_rule_defined_vars' => the variables as they were at the end of the code)
 */
function formulize_ruleRunCode($code, $variables) {
	$code = removeOpeningPHPTag(strval($code));
	$functionName = 'formulize_rule_code_' . md5($code);
	if (!function_exists($functionName)) {
		$filename = ICMS_CACHE_PATH . '/' . $functionName . '.php';
		if (!file_exists($filename)) {
			$wrapped = "<?php\n\nfunction $functionName(\$formulize_rule_variables) {\n"
				. "\tforeach(\$formulize_rule_variables as \$formulize_rule_key => \$formulize_rule_value) { \${\$formulize_rule_key} = \$formulize_rule_value; }\n"
				. "\tunset(\$formulize_rule_variables, \$formulize_rule_key, \$formulize_rule_value);\n\n"
				. $code
				. "\n\n\treturn array('formulize_rule_defined_vars' => get_defined_vars());\n}\n";
			$temporaryFile = $filename . '.' . getmypid() . '.tmp';
			if (false === file_put_contents($temporaryFile, $wrapped) OR !rename($temporaryFile, $filename)) {
				@unlink($temporaryFile);
				throw new Exception("could not write the code to the cache folder.");
			}
		}
		include_once $filename;
	}
	return call_user_func($functionName, $variables);
}

/**
 * Step: run PHP code. Whatever the code returns (or puts in $output) is the step's output.
 *
 * Settings:
 * - code: the PHP code
 * - output: the name to save the output under
 *
 * @param array $step
 * @param array $context
 * @return array|string
 */
function formulize_ruleStepCode($step, $context) {
	$code = strval($step['code'] ?? '');
	if (trim(removeOpeningPHPTag($code)) === '') {
		return "there is no code to run.";
	}
	ob_start();
	try {
		$result = formulize_ruleRunCode($code, formulize_ruleCodeVariables($context, ($step['values_as'] ?? 'stored') == 'readable' ? 'readable' : 'stored', strpos($code, '$entry') !== false));
	} finally {
		$printed = trim(ob_get_clean());
	}
	if (is_array($result) AND array_key_exists('formulize_rule_defined_vars', $result)) {
		$output = $result['formulize_rule_defined_vars']['output'] ?? null;
	} else {
		$output = $result;
	}
	$outcome = "ran the code" . ($output !== null ? ", which produced: " . mb_substr(formulize_ruleOutputAsText($output), 0, 160) : "") . ($printed !== '' ? ". It printed: " . mb_substr(strip_tags($printed), 0, 300) : "") . ".";
	return $output !== null ? array('outcome' => $outcome, 'output' => $output) : $outcome;
}

/**
 * Before an entry is saved: tell ICMS (so preloads can change the values or stop the save), then run the form's
 * Before Save rules. Called from formulizeForm::onBeforeSave(), after the form's own Before Saving procedure, on every
 * write, the same as that procedure.
 *
 * Before Save rules can only run code and set values on the entry being saved, and they have no conditions (the code
 * can check whatever it needs to): the entry isn't saved yet, so there is nothing else to do things to.
 *
 * @param int $fid The form
 * @param int|string $entryId The entry, or 'new'
 * @param array $values The values about to be saved, by element handle
 * @param array $existingValues The values in the database now
 * @return array|false The values to save, or false to stop the save
 */
function formulize_rulesBeforeSave($fid, $entryId, $values, $existingValues) {
	if (class_exists('icms_Event')) {
		$parameters = array('form_id' => $fid, 'entry_id' => $entryId, 'values' => &$values, 'current_values' => $existingValues);
		$event = icms_Event::trigger('icms', '*formulizebeforesave', null, $parameters);
		if ($event AND $event->canceled) {
			return false;
		}
	}
	if (!empty($GLOBALS['formulize_suppressRules'])) {
		return $values;
	}
	$handler = xoops_getmodulehandler('rules', 'formulize');
	if (!in_array('before_save', $handler->activeTriggerTypes($fid))) {
		return $values;
	}
	global $xoopsUser;
	static $logged = array();
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$validHandles = array_flip((array) $form_handler->get($fid)->getVar('elementHandles'));
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
	$stepTypes = formulizeRulesHandler::stepTypes();
	foreach ($handler->getRules($fid, 'before_save', true) as $rule) {
		$context = array(
			'fid' => $fid, 'entry_id' => $entryId, 'event' => 'before_save', 'uid' => $xoopsUser ? intval($xoopsUser->getVar('uid')) : 0,
			'values' => $values, 'before' => $existingValues, 'changed' => null, 'owner' => intval($existingValues['creation_uid'] ?? 0),
			'owner_groups' => null, 'deleted' => false, 'outputs' => array(), 'rule' => $rule,
		);
		$outcomes = array();
		$stopped = false;
		foreach (array_values((array) $rule['steps']) as $i => $step) {
			$label = ($i + 1) . ". " . ($stepTypes[$step['type'] ?? ''] ?? 'Step');
			try {
				if (($step['type'] ?? '') == 'code') {
					ob_start();
					try {
						$result = formulize_ruleRunCode(strval($step['code'] ?? ''), formulize_ruleCodeVariables($context));
					} finally {
						ob_end_clean();
					}
					if ($result === false) {
						$outcomes[] = "$label: stopped the save.";
						$stopped = true;
						break;
					}
					if (is_array($result) AND array_key_exists('formulize_rule_defined_vars', $result)) {
						foreach ($result['formulize_rule_defined_vars'] as $name => $value) {
							$handle = substr($name, 0, 9) == 'elementId' ? substr($name, 9) : $name;
							if (isset($validHandles[$handle])) {
								$values[$handle] = $value;
							}
						}
						$outputName = formulize_ruleOutputName($step);
						if ($outputName !== '' AND isset($result['formulize_rule_defined_vars']['output'])) {
							$context['outputs'][$outputName] = $result['formulize_rule_defined_vars']['output'];
						}
					}
					$outcomes[] = "$label: ran the code.";
				} elseif (($step['type'] ?? '') == 'update_entries') {
					$set = array();
					foreach ((array) ($step['values'] ?? array()) as $setting) {
						if (empty($setting['element']) OR !$elementObject = $element_handler->get($setting['element']) OR $elementObject->getVar('id_form') != $fid) {
							continue;
						}
						$handle = $elementObject->getVar('ele_handle');
						$value = formulize_ruleResolveValue($setting['value'] ?? '', $elementObject, $context);
						if (($setting['mode'] ?? 'replace') == 'append') {
							$value = strval($values[$handle] ?? '') . strval($value);
						}
						$values[$handle] = ($value === '') ? null : $value;
						$set[] = $handle;
					}
					$outcomes[] = "$label: set " . ($set ? implode(", ", $set) : "nothing") . ".";
				} else {
					$outcomes[] = "$label: skipped, before an entry is saved only Run PHP code and Update entries (this entry) can be used.";
				}
			} catch (Throwable $e) {
				$outcomes[] = "$label: failed: " . $e->getMessage();
			}
			$context['values'] = $values;
		}
		// before save runs on every write, so only record the first time per entry in a request, and whenever a save is stopped
		$logKey = $rule['rule_id'] . '-' . $entryId;
		if ($stopped OR !isset($logged[$logKey])) {
			$logged[$logKey] = true;
			$context['entry_id'] = is_numeric($entryId) ? $entryId : 0;
			formulize_ruleLog($rule, $context, $outcomes);
		}
		if ($stopped) {
			return false;
		}
	}
	return $values;
}

/**
 * Tell ICMS that something happened to entries, so code in preloads can respond (see rules-plan.md). A preload is a
 * class in plugins/preloads/ (or a module's preload folder) with a method named after the event, ie:
 * eventFormulizeEntriesCreated($parameters).
 * @param string $eventName formulizeEntriesCreated, formulizeEntriesUpdated, formulizeEntriesDeleted or formulizeCustomButtonClicked
 * @param array $parameters
 * @return void
 */
function formulize_rulesTriggerIcmsEvent($eventName, $parameters) {
	if (class_exists('icms') AND isset(icms::$preload) AND is_object(icms::$preload)) {
		icms::$preload->triggerEvent($eventName, $parameters);
	}
}

/**
 * Queue a rule to run in the background, after the request that triggered it
 * @param array $rule
 * @param array $context
 * @return int|false The job id
 */
function formulize_ruleQueueJob($rule, $context) {
	global $xoopsDB;
	unset($context['rule']);
	$payload = json_encode(array('rule_id' => $rule['rule_id'], 'context' => $context, 'next_step' => 0, 'outcomes' => array()), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
	$sql = "INSERT INTO " . $xoopsDB->prefix('formulize_rule_outbox') . " (rule_id, fid, entry_id, event, channel, target, payload, status, attempts, created_at, next_attempt_at) VALUES ("
		. intval($rule['rule_id']) . ", " . intval($context['fid']) . ", " . intval($context['entry_id']) . ", '" . formulize_db_escape($context['event']) . "', 'rule', '', '"
		. formulize_db_escape($payload) . "', 'pending', 0, NOW(), NOW())";
	if (!$xoopsDB->queryF($sql)) {
		return false;
	}
	$jobId = intval($xoopsDB->getInsertId());
	$GLOBALS['formulize_ruleJobsThisRequest'][] = $jobId;
	formulize_rulesEnsureShutdown();
	return $jobId;
}

/**
 * Run a rule that was queued to run in the background. It runs as the user who triggered it, with the entry's values as
 * they are now. If a step fails in a way that might work later, the job is retried from that step, so the steps before
 * it don't happen twice.
 * @param int $jobId
 * @return string The status afterwards: delivered (done), pending (will retry) or failed
 */
function formulize_ruleRunJob($jobId) {
	global $xoopsDB, $xoopsUser;
	$table = $xoopsDB->prefix('formulize_rule_outbox');
	$res = $xoopsDB->query("SELECT * FROM $table WHERE outbox_id = " . intval($jobId));
	if (!$res OR !$row = $xoopsDB->fetchArray($res)) {
		return 'missing';
	}
	$payload = json_decode(strval($row['payload']), true);
	$handler = xoops_getmodulehandler('rules', 'formulize');
	$rule = $handler->get(intval($payload['rule_id'] ?? 0));
	$attempts = intval($row['attempts']) + 1;
	if (!$rule OR !$rule['active']) {
		$xoopsDB->queryF("UPDATE $table SET status = 'failed', attempts = $attempts, last_error = '" . formulize_db_escape($rule ? "The rule was turned off before it could run." : "The rule was deleted before it could run.") . "', next_attempt_at = NULL WHERE outbox_id = " . intval($jobId));
		return 'failed';
	}
	$context = (array) $payload['context'];
	$context['rule'] = $rule;
	if (empty($context['deleted']) AND $context['entry_id'] AND $currentValues = formulize_ruleGetEntryRow($context['fid'], $context['entry_id'])) {
		$context['values'] = $currentValues;
	}
	$outcomes = (array) ($payload['outcomes'] ?? array());
	$previousUser = $xoopsUser;
	$member_handler = xoops_gethandler('member');
	$xoopsUser = $context['uid'] ? $member_handler->getUser($context['uid']) : null;
	$GLOBALS['xoopsUser'] = $xoopsUser;
	$previousRunNow = $GLOBALS['formulize_ruleRunNow'] ?? null;
	$GLOBALS['formulize_ruleRunNow'] = true;
	try {
		$result = formulize_ruleRunSteps($rule, $context, intval($payload['next_step'] ?? 0), $outcomes);
	} catch (Throwable $e) {
		$result = array('status' => 'done');
		$outcomes[] = "Failed: " . $e->getMessage();
	}
	$GLOBALS['formulize_ruleRunNow'] = $previousRunNow;
	$xoopsUser = $previousUser;
	$GLOBALS['xoopsUser'] = $previousUser;

	if ($result['status'] == 'retry' AND $attempts < FORMULIZE_RULE_WEBHOOK_MAX_ATTEMPTS) {
		$backoffMinutes = array(1 => 1, 2 => 5, 3 => 30, 4 => 120, 5 => 720);
		unset($context['rule']);
		$payload['context'] = $context;
		$payload['next_step'] = $result['step'];
		$payload['outcomes'] = $outcomes;
		$xoopsDB->queryF("UPDATE $table SET status = 'pending', attempts = $attempts, payload = '" . formulize_db_escape(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR)) . "', last_error = '" . formulize_db_escape($result['error']) . "', next_attempt_at = DATE_ADD(NOW(), INTERVAL " . intval($backoffMinutes[$attempts] ?? 720) . " MINUTE) WHERE outbox_id = " . intval($jobId));
		return 'pending';
	}
	$status = $result['status'] == 'retry' ? 'failed' : 'delivered';
	formulize_ruleLog($rule, $context, array_merge(array("Ran in the background (job $jobId" . ($attempts > 1 ? ", attempt $attempts" : "") . "):"), $outcomes));
	$xoopsDB->queryF("UPDATE $table SET status = '$status', attempts = $attempts, last_error = '" . formulize_db_escape(implode("\n", $outcomes)) . "', " . ($status == 'delivered' ? "delivered_at = NOW(), " : "") . "next_attempt_at = NULL WHERE outbox_id = " . intval($jobId));
	return $status;
}

/**
 * Let the browser have the page before doing slow background work at the end of a request: close the session, send
 * what's buffered, and where the server allows it (PHP-FPM, LiteSpeed), finish the response so the person isn't
 * kept waiting. Under mod_php the page arrives, but the browser may show it still loading until the work is done.
 * @return void
 */
function formulize_rulesReleaseClient() {
	ignore_user_abort(true);
	@set_time_limit(300);
	if (session_id()) {
		session_write_close();
	}
	while (ob_get_level() > 0) {
		ob_end_flush();
	}
	flush();
	if (function_exists('fastcgi_finish_request')) {
		fastcgi_finish_request();
	} elseif (function_exists('litespeed_finish_request')) {
		litespeed_finish_request();
	}
}
