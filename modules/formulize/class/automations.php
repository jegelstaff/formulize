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

if (!defined("XOOPS_ROOT_PATH")) {
    die("XOOPS root path not defined");
}

/**
 * Storage for automations: "when this happens to an entry in this form, and the entry matches these conditions,
 * then do these things". See include/automations.php for how automations are run, and automations-plan.md for the design.
 *
 * Automations are plain arrays, with these keys:
 * - automation_id: int, or 'new' before the automation is saved
 * - fid: the form the automation belongs to
 * - name: the name shown in the admin UI
 * - trigger_type: one of the keys of formulizeAutomationsHandler::triggerTypes()
 * - trigger_settings: array. For update_entry and save_entry, 'watch' is a list of element handles, and the automation only
 *   runs on an update when one of them changed. For button_click, 'sid' and 'button' identify the custom button.
 * - conditions: a standard Formulize conditions array (elements, ops, terms, types), or an empty array for all entries
 * - steps: the steps, in order. Each is an array with a 'type' (notify, update_entries, create_entry, ai, webhook,
 *   code), a stable 'id', an optional 'output' name (later steps use the step's output as {name}), and the settings
 *   for that type (see formulize_automationRunStep in include/automations.php)
 * - active: 1 or 0
 * - weight: automations run in order of weight, then id
 */
#[AllowDynamicProperties]
class formulizeAutomationsHandler {

	var $db;
	var $table;
	var $activeTriggerTypesCache = array();

	function __construct(&$db) {
		$this->db =& $db;
		$this->table = $db->prefix('formulize_automations');
	}

	function &getInstance(&$db) {
		static $instance;
		if (!isset($instance)) {
			$instance = new formulizeAutomationsHandler($db);
		}
		return $instance;
	}

	/**
	 * The kinds of event that can trigger an automation, with the text the admin UI uses for them
	 * @return array
	 */
	static function triggerTypes() {
		return array(
			'new_entry' => 'A new entry is created',
			'update_entry' => 'An entry is updated',
			'save_entry' => 'An entry is created or updated',
			'delete_entry' => 'An entry is deleted',
			'button_click' => 'A custom button is clicked',
			'before_save' => 'Before an entry is saved (to check or change the values)',
		);
	}

	/**
	 * The kinds of step an automation can take, with the text the admin UI uses for them
	 * @return array
	 */
	static function stepTypes() {
		return array(
			'notify' => 'Send a notification',
			'update_entries' => 'Update entries',
			'create_entry' => 'Create an entry',
			'ai' => 'Ask AI',
			'webhook' => 'Send a webhook',
			'code' => 'Run PHP code',
		);
	}

	/**
	 * The default output name for a step that produces something, so later steps can use it as {name}
	 * @param string $type The step type
	 * @return string
	 */
	static function defaultOutputName($type) {
		$names = array('ai' => 'ai_reply', 'create_entry' => 'new_entry', 'code' => 'code_result');
		return $names[$type] ?? '';
	}

	/**
	 * Starting points for new automations: the common things people set up, with the trigger and steps already chosen, so
	 * the webmaster only fills in the details. An automation made from a starting point is an automation like any other.
	 * @param int $fid The form the automation will be for
	 * @return array Key => 'group', 'title', 'description', 'needs' ('ai' when AI automations must be on), 'automation' (the parts of the automation to start with)
	 */
	static function startingPoints($fid) {
		$emptyValues = array(array('element' => '', 'value' => '', 'mode' => 'replace'));
		return array(
			'notify_new' => array('group' => 'Notifications', 'title' => 'Notify people when an entry is created',
				'description' => 'Send a notification, with a message you write, to the people you choose.',
				'automation' => array('name' => 'Notify about new entries', 'trigger_type' => 'new_entry', 'steps' => array(
					array('type' => 'notify', 'recipients' => array('creator' => 1), 'subject' => 'New {FORMSINGULAR}', 'message' => "{ENTRYNAME} added a new entry.\n\n{VIEWURL}")))),
			'notify_change' => array('group' => 'Notifications', 'title' => 'Notify people when something changes',
				'description' => 'Choose which elements to watch. A notification goes out when one of them changes.',
				'automation' => array('name' => 'Notify about changes', 'trigger_type' => 'update_entry', 'steps' => array(
					array('type' => 'notify', 'recipients' => array('creator' => 1), 'subject' => '{FORMSINGULAR} changed', 'message' => "{ENTRYNAME} changed an entry.\n\n{VIEWURL}")))),
			'create_related' => array('group' => 'Changing entries', 'title' => 'Create an entry in another form',
				'description' => 'When an entry is created, create a connected entry somewhere else, with values copied from it.',
				'automation' => array('name' => 'Create a connected entry', 'trigger_type' => 'new_entry', 'steps' => array(
					array('type' => 'create_entry', 'target_fid' => $fid, 'owner' => 'current', 'output' => 'new_entry', 'values' => $emptyValues)))),
			'update_related' => array('group' => 'Changing entries', 'title' => 'Update connected entries when an entry changes',
				'description' => 'Keep other entries in step: when this entry is updated, change the entries connected to it.',
				'automation' => array('name' => 'Update connected entries', 'trigger_type' => 'update_entry', 'steps' => array(
					array('type' => 'update_entries', 'target' => 'matching', 'target_fid' => $fid, 'values' => $emptyValues)))),
			'set_values_before_save' => array('group' => 'Changing entries', 'title' => 'Fill in values when an entry is saved',
				'description' => 'Set elements to values you choose, every time an entry is saved, before it goes into the database.',
				'automation' => array('name' => 'Fill in values', 'trigger_type' => 'before_save', 'steps' => array(
					array('type' => 'update_entries', 'target' => 'this', 'target_fid' => $fid, 'values' => $emptyValues)))),
			'ai_summary' => array('group' => 'AI', 'title' => 'Ask AI about each new entry, and save the answer', 'needs' => 'ai',
				'description' => 'Send each new entry to AI with your instructions (summarize it, sort it into a category, draft a reply), and write the answer into an element.',
				'automation' => array('name' => 'Ask AI about new entries', 'trigger_type' => 'new_entry', 'steps' => array(
					array('type' => 'ai', 'prompt' => 'Summarize this entry in one or two sentences.', 'include_entry' => 1, 'json' => 0, 'output' => 'ai_reply'),
					array('type' => 'update_entries', 'target' => 'this', 'target_fid' => $fid, 'values' => array(array('element' => '', 'value' => '{ai_reply}', 'mode' => 'replace')))))),
			'webhook_save' => array('group' => 'Other systems', 'title' => 'Send a webhook when an entry is saved',
				'description' => 'Tell another system (Zapier, Make, n8n, your own app) whenever an entry is created or updated.',
				'automation' => array('name' => 'Webhook when saved', 'trigger_type' => 'save_entry', 'steps' => array(
					array('type' => 'webhook', 'url' => '', 'secret' => '', 'include_data' => 1)))),
			'webhook_delete' => array('group' => 'Other systems', 'title' => 'Send a webhook when an entry is deleted',
				'description' => 'Tell another system when an entry is deleted, with the values it had.',
				'automation' => array('name' => 'Webhook when deleted', 'trigger_type' => 'delete_entry', 'steps' => array(
					array('type' => 'webhook', 'url' => '', 'secret' => '', 'include_data' => 1)))),
			'button' => array('group' => 'Buttons', 'title' => 'Do something when a button is clicked',
				'description' => 'Pick a custom button from one of the list screens, and choose what happens to the entries it is clicked for.',
				'automation' => array('name' => 'When the button is clicked', 'trigger_type' => 'button_click', 'steps' => array(
					array('type' => 'update_entries', 'target' => 'this', 'target_fid' => $fid, 'values' => $emptyValues)))),
			'code_before_save' => array('group' => 'Code', 'title' => 'Run PHP code before an entry is saved',
				'description' => 'Check or change the values before they are saved, or stop the save by returning false. The same as the Before Saving procedure.',
				'automation' => array('name' => 'Code before saving', 'trigger_type' => 'before_save', 'steps' => array(
					array('type' => 'code', 'code' => "<?php\n")))),
			'code_after_save' => array('group' => 'Code', 'title' => 'Run PHP code after an entry is saved',
				'description' => 'The same as the After Saving procedure, with conditions and other steps if you want them.',
				'automation' => array('name' => 'Code after saving', 'trigger_type' => 'save_entry', 'steps' => array(
					array('type' => 'code', 'code' => "<?php\n")))),
			'code_delete' => array('group' => 'Code', 'title' => 'Run PHP code when an entry is deleted',
				'description' => "The same as the On Delete procedure. The deleted entry's values are available.",
				'automation' => array('name' => 'Code when deleted', 'trigger_type' => 'delete_entry', 'steps' => array(
					array('type' => 'code', 'code' => "<?php\n")))),
		);
	}

	/**
	 * Make an automation from a starting point
	 * @param int $fid The form
	 * @param string $startingPointKey A key from startingPoints()
	 * @return array The automation, not saved yet
	 */
	function createFromStartingPoint($fid, $startingPointKey) {
		$automation = $this->create($fid);
		$startingPoints = self::startingPoints($fid);
		if (isset($startingPoints[$startingPointKey])) {
			$automation = array_merge($automation, $startingPoints[$startingPointKey]['automation']);
		}
		return $automation;
	}

	/**
	 * An automation with default settings, not saved yet
	 * @param int $fid The form the automation is for
	 * @return array
	 */
	function create($fid) {
		return array(
			'automation_id' => 'new',
			'fid' => intval($fid),
			'name' => 'New automation',
			'trigger_type' => 'new_entry',
			'trigger_settings' => array(),
			'conditions' => array(),
			'steps' => array(),
			'active' => 1,
			'weight' => 0,
		);
	}

	/**
	 * Get one automation
	 * @param int $automation_id
	 * @return array|false The automation, or false if there is no automation with that id
	 */
	function get($automation_id) {
		$sql = "SELECT * FROM {$this->table} WHERE automation_id = " . intval($automation_id);
		if ($res = $this->db->query($sql)) {
			if ($row = $this->db->fetchArray($res)) {
				return $this->unpack($row);
			}
		}
		return false;
	}

	/**
	 * Get the automations for a form
	 * @param int $fid The form
	 * @param string|array $triggerTypes Optional. Only automations with this trigger type, or one of these trigger types
	 * @param bool $activeOnly Optional. Only automations that are turned on. Default false.
	 * @return array The automations, keyed by automation id, in the order they run
	 */
	function getAutomations($fid, $triggerTypes = null, $activeOnly = false) {
		$where = "fid = " . intval($fid);
		if ($triggerTypes) {
			$triggerTypes = array_map('formulize_db_escape', (array) $triggerTypes);
			$where .= " AND trigger_type IN ('" . implode("','", $triggerTypes) . "')";
		}
		if ($activeOnly) {
			$where .= " AND active = 1";
		}
		$automations = array();
		if ($res = $this->db->query("SELECT * FROM {$this->table} WHERE $where ORDER BY weight, automation_id")) {
			while ($row = $this->db->fetchArray($res)) {
				$automations[$row['automation_id']] = $this->unpack($row);
			}
		}
		return $automations;
	}

	/**
	 * Find out which trigger types have active automations on a form. Cached for the request, since this is asked on every
	 * write to the form, so it has to be cheap. The cache is cleared when automations are saved or deleted.
	 * @param int $fid The form
	 * @return array The trigger types that have at least one active automation on the form
	 */
	function activeTriggerTypes($fid) {
		$fid = intval($fid);
		if (!isset($this->activeTriggerTypesCache[$fid])) {
			$this->activeTriggerTypesCache[$fid] = array();
			if ($res = $this->db->query("SELECT DISTINCT trigger_type FROM {$this->table} WHERE fid = $fid AND active = 1")) {
				while ($row = $this->db->fetchArray($res)) {
					$this->activeTriggerTypesCache[$fid][] = $row['trigger_type'];
				}
			}
		}
		return $this->activeTriggerTypesCache[$fid];
	}

	/**
	 * Save an automation
	 * @param array $automation An automation, as returned by get() or create(), with changes
	 * @return int|false The id of the automation, or false if it could not be saved
	 */
	function insert($automation) {
		// every step gets an id that stays the same when steps are added, removed or reordered
		foreach ((array) $automation['steps'] as $i => $step) {
			if (empty($step['id'])) {
				$automation['steps'][$i]['id'] = 's' . substr(md5(uniqid('', true) . $i), 0, 8);
			}
		}
		$fields = array(
			'fid' => intval($automation['fid']),
			'name' => "'" . formulize_db_escape(strval($automation['name'])) . "'",
			'trigger_type' => "'" . formulize_db_escape(strval($automation['trigger_type'])) . "'",
			'trigger_settings' => "'" . formulize_db_escape(json_encode((array) $automation['trigger_settings'])) . "'",
			'conditions' => "'" . formulize_db_escape(json_encode((array) $automation['conditions'])) . "'",
			'steps' => "'" . formulize_db_escape(json_encode(array_values((array) $automation['steps']))) . "'",
			'active' => $automation['active'] ? 1 : 0,
			'weight' => intval($automation['weight']),
		);
		if (is_numeric($automation['automation_id']) AND $automation['automation_id'] > 0) {
			$sets = array();
			foreach ($fields as $field => $value) {
				$sets[] = "`$field` = $value";
			}
			$sql = "UPDATE {$this->table} SET " . implode(", ", $sets) . " WHERE automation_id = " . intval($automation['automation_id']);
			$result = $this->db->queryF($sql) ? intval($automation['automation_id']) : false;
		} else {
			$sql = "INSERT INTO {$this->table} (`" . implode("`, `", array_keys($fields)) . "`) VALUES (" . implode(", ", $fields) . ")";
			$result = $this->db->queryF($sql) ? intval($this->db->getInsertId()) : false;
		}
		$this->activeTriggerTypesCache = array();
		return $result;
	}

	/**
	 * Delete an automation, and the record of when it ran
	 * @param int $automation_id
	 * @return bool
	 */
	function delete($automation_id) {
		$automation_id = intval($automation_id);
		$this->activeTriggerTypesCache = array();
		$this->db->queryF("DELETE FROM " . $this->db->prefix('formulize_automation_log') . " WHERE automation_id = $automation_id");
		return $this->db->queryF("DELETE FROM {$this->table} WHERE automation_id = $automation_id") ? true : false;
	}

	/**
	 * Make a copy of an automation, turned off, so it does not start running until the webmaster has looked at it
	 * @param int $automation_id
	 * @return int|false The id of the copy
	 */
	function cloneAutomation($automation_id) {
		if (!$automation = $this->get($automation_id)) {
			return false;
		}
		$automation['automation_id'] = 'new';
		$automation['name'] = $automation['name'] . ' (copy)';
		$automation['active'] = 0;
		return $this->insert($automation);
	}

	/**
	 * Turn the stored row into an automation array, decoding the JSON fields
	 * @param array $row
	 * @return array
	 */
	private function unpack($row) {
		$automation = $row;
		$automation['automation_id'] = intval($row['automation_id']);
		$automation['fid'] = intval($row['fid']);
		$automation['active'] = intval($row['active']);
		$automation['weight'] = intval($row['weight']);
		foreach (array('trigger_settings', 'conditions', 'steps') as $field) {
			$decoded = json_decode(strval($row[$field]), true);
			$automation[$field] = is_array($decoded) ? $decoded : array();
		}
		return $automation;
	}
}
