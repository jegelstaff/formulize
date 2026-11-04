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
 * Storage for summaries: AI-readable digests of the data in a form, kept up to date as the data changes, so an AI
 * assistant can orient itself without reading every entry. See include/summaries.php for how they are written and
 * kept current, and rules-plan.md for the design.
 *
 * A summary has a definition (what it covers and how it is kept current) and content (the text). Definitions are in
 * formulize_summary_definitions. Content is in formulize_summaries, one row per piece of text: a whole-scope summary
 * has one piece, a per-entry summary has one per entry, a per-group summary has one per group value. Every write adds
 * a row and marks the previous one as no longer current, so earlier versions are kept (the last few, see
 * $revisionsToKeep). The content table is also registered as the hidden, system-managed "System Summaries" form.
 *
 * Definitions are plain arrays, with these keys:
 * - summary_id: int, or 'new' before it is saved
 * - name
 * - form_id: the form the summary is about (its "main" form)
 * - include_connected_forms: 1 to include the entries connected to each main entry in the primary relationship
 * - only_entries_where: a filter in the format get_entries_from_form and the Public API take (a list of
 *   {element, operator, value} conditions, and {any: [...]} / {all: [...]} groups) limiting which main form entries are
 *   covered, or an empty array for all of them
 * - level: 'each_entry' (one summary per main entry), 'per_group' (one per value of group_by) or 'whole_scope'
 * - group_by: for per_group, the handle of the element in the main form whose value groups the entries
 * - built_from_summary_id: for per_group and whole_scope, optionally an each_entry summary whose texts the summary is
 *   built from, instead of from the entries themselves
 * - instructions: what the summary should cover
 * - written_by: 'server_ai' (written by the site's AI model) or 'client' (written by an AI client with write_summary)
 * - keep_current: 'on_change', 'scheduled' or 'manual'
 * - wait_minutes: for on_change, how long things must be quiet before the summary is refreshed
 * - refresh_every_minutes: for scheduled, how often
 * - readable_by_groups: groups who may read the summary, besides the people who can see everything it covers
 * - all_scope_viewers: 1 if people who can see everything the summary covers may read it
 * - max_length: the longest a piece of summary text should be, in characters
 * - active: 1 or 0
 */
#[AllowDynamicProperties]
class formulizeSummariesHandler {

	var $db;
	var $definitionsTable;
	var $contentTable;
	var $revisionsToKeep = 10;
	var $definitionsByFormCache = null;

	function __construct(&$db) {
		$this->db =& $db;
		$this->definitionsTable = $db->prefix('formulize_summary_definitions');
		$this->contentTable = $db->prefix('formulize_summaries');
	}

	function &getInstance(&$db) {
		static $instance;
		if (!isset($instance)) {
			$instance = new formulizeSummariesHandler($db);
		}
		return $instance;
	}

	/**
	 * The levels a summary can be written at, with the text used to describe them
	 * @return array
	 */
	static function levels() {
		return array(
			'each_entry' => 'One summary for each entry',
			'per_group' => 'One summary for each group of entries',
			'whole_scope' => 'One summary of all the entries',
		);
	}

	/**
	 * A definition with default settings, not saved yet
	 * @param int $fid The form the summary is about
	 * @return array
	 */
	function create($fid) {
		return array(
			'summary_id' => 'new',
			'name' => 'New summary',
			'form_id' => intval($fid),
			'include_connected_forms' => 0,
			'only_entries_where' => array(),
			'level' => 'whole_scope',
			'group_by' => '',
			'built_from_summary_id' => 0,
			'instructions' => '',
			'written_by' => 'server_ai',
			'keep_current' => 'on_change',
			'wait_minutes' => 10,
			'refresh_every_minutes' => 1440,
			'readable_by_groups' => array(),
			'all_scope_viewers' => 1,
			'max_length' => 2000,
			'active' => 1,
		);
	}

	/**
	 * Get one definition
	 * @param int $summaryId
	 * @return array|false
	 */
	function get($summaryId) {
		$res = $this->db->query("SELECT * FROM {$this->definitionsTable} WHERE summary_id = " . intval($summaryId));
		if ($res AND $row = $this->db->fetchArray($res)) {
			return $this->unpack($row);
		}
		return false;
	}

	/**
	 * Get definitions, optionally only those about one form
	 * @param int $fid Optional. Only summaries whose main form is this one.
	 * @param bool $activeOnly Optional.
	 * @return array Keyed by summary id
	 */
	function getSummaries($fid = 0, $activeOnly = false) {
		$where = array('1');
		if ($fid) {
			$where[] = 'form_id = ' . intval($fid);
		}
		if ($activeOnly) {
			$where[] = 'active = 1';
		}
		$summaries = array();
		if ($res = $this->db->query("SELECT * FROM {$this->definitionsTable} WHERE " . implode(' AND ', $where) . " ORDER BY name, summary_id")) {
			while ($row = $this->db->fetchArray($res)) {
				$summaries[$row['summary_id']] = $this->unpack($row);
			}
		}
		return $summaries;
	}

	/**
	 * Whether there are any active summaries at all, so changes to entries can be ignored cheaply when there are none
	 * @return array The active definitions, cached for the request
	 */
	function activeDefinitions() {
		if ($this->definitionsByFormCache === null) {
			$this->definitionsByFormCache = $this->getSummaries(0, true);
		}
		return $this->definitionsByFormCache;
	}

	/**
	 * Save a definition
	 * @param array $summary
	 * @return int|false The summary id
	 */
	function insert($summary) {
		$fields = array(
			'name' => "'" . formulize_db_escape(strval($summary['name'])) . "'",
			'form_id' => intval($summary['form_id']),
			'include_connected_forms' => $summary['include_connected_forms'] ? 1 : 0,
			'only_entries_where' => "'" . formulize_db_escape(json_encode((array) $summary['only_entries_where'])) . "'",
			'level' => "'" . formulize_db_escape(strval($summary['level'])) . "'",
			'group_by' => "'" . formulize_db_escape(strval($summary['group_by'])) . "'",
			'built_from_summary_id' => intval($summary['built_from_summary_id']),
			'instructions' => "'" . formulize_db_escape(strval($summary['instructions'])) . "'",
			'written_by' => "'" . formulize_db_escape(strval($summary['written_by'])) . "'",
			'keep_current' => "'" . formulize_db_escape(strval($summary['keep_current'])) . "'",
			'wait_minutes' => max(0, intval($summary['wait_minutes'])),
			'refresh_every_minutes' => max(5, intval($summary['refresh_every_minutes'])),
			'readable_by_groups' => "'" . formulize_db_escape(json_encode(array_values(array_map('intval', (array) $summary['readable_by_groups'])))) . "'",
			'all_scope_viewers' => $summary['all_scope_viewers'] ? 1 : 0,
			'max_length' => max(100, intval($summary['max_length'])),
			'active' => $summary['active'] ? 1 : 0,
		);
		if (is_numeric($summary['summary_id']) AND $summary['summary_id'] > 0) {
			$sets = array();
			foreach ($fields as $field => $value) {
				$sets[] = "`$field` = $value";
			}
			$result = $this->db->queryF("UPDATE {$this->definitionsTable} SET " . implode(', ', $sets) . ", updated_at = NOW() WHERE summary_id = " . intval($summary['summary_id'])) ? intval($summary['summary_id']) : false;
			if ($result) {
				// the name is copied onto the content, so the System Summaries form shows which summary each piece belongs to
				$this->db->queryF("UPDATE {$this->contentTable} SET summary_name = " . $fields['name'] . " WHERE summary_id = " . intval($summary['summary_id']));
			}
		} else {
			global $xoopsUser;
			$fields['created_uid'] = $xoopsUser ? intval($xoopsUser->getVar('uid')) : 0;
			$result = $this->db->queryF("INSERT INTO {$this->definitionsTable} (`" . implode('`, `', array_keys($fields)) . "`, created_at, updated_at) VALUES (" . implode(', ', $fields) . ", NOW(), NOW())") ? intval($this->db->getInsertId()) : false;
		}
		$this->definitionsByFormCache = null;
		return $result;
	}

	/**
	 * Delete a definition, all its content, and any refreshes waiting for it
	 * @param int $summaryId
	 * @return bool
	 */
	function delete($summaryId) {
		$summaryId = intval($summaryId);
		$this->definitionsByFormCache = null;
		$this->db->queryF("DELETE FROM {$this->contentTable} WHERE summary_id = $summaryId");
		$this->db->queryF("DELETE FROM " . $this->db->prefix('formulize_rule_outbox') . " WHERE channel = 'summary' AND target = 'summary:$summaryId' AND status IN ('pending', 'sending')");
		return $this->db->queryF("DELETE FROM {$this->definitionsTable} WHERE summary_id = $summaryId") ? true : false;
	}

	/**
	 * The current text of one piece of a summary
	 * @param int $summaryId
	 * @param int $entryId For each_entry summaries, the main form entry. 0 otherwise.
	 * @param string $groupValue For per_group summaries, the group. '' otherwise.
	 * @return array|false The content row (summary_text, as_of, covers (decoded), written_by, ...), or false if there is none
	 */
	function currentText($summaryId, $entryId = 0, $groupValue = '') {
		$sql = "SELECT * FROM {$this->contentTable} WHERE summary_id = " . intval($summaryId) . " AND entry_id = " . intval($entryId)
			. " AND group_value = '" . formulize_db_escape(strval($groupValue)) . "' AND is_current = 1 ORDER BY text_id DESC LIMIT 1";
		if ($res = $this->db->query($sql) AND $row = $this->db->fetchArray($res)) {
			return $this->unpackText($row);
		}
		return false;
	}

	/**
	 * All the current pieces of a summary
	 * @param int $summaryId
	 * @param array $entryIds Optional. Only these entries (for each_entry summaries).
	 * @return array Content rows, keyed by entry id for each_entry summaries, by group value for per_group, by 0 for whole_scope
	 */
	function currentTexts($summaryId, $entryIds = null) {
		$where = "summary_id = " . intval($summaryId) . " AND is_current = 1";
		if (is_array($entryIds)) {
			$entryIds = array_filter(array_map('intval', $entryIds));
			if (!$entryIds) {
				return array();
			}
			$where .= " AND entry_id IN (" . implode(',', $entryIds) . ")";
		}
		$texts = array();
		if ($res = $this->db->query("SELECT * FROM {$this->contentTable} WHERE $where ORDER BY text_id")) {
			while ($row = $this->db->fetchArray($res)) {
				$key = $row['entry_id'] ? intval($row['entry_id']) : ($row['group_value'] !== '' ? $row['group_value'] : 0);
				$texts[$key] = $this->unpackText($row);
			}
		}
		return $texts;
	}

	/**
	 * Write a new version of one piece of a summary. The previous version is kept, but is no longer current.
	 * @param array $summary The definition
	 * @param int $entryId For each_entry summaries, the main form entry. 0 otherwise.
	 * @param string $groupValue For per_group summaries, the group. '' otherwise.
	 * @param string $text The summary
	 * @param string $asOf When the data it is based on was read, as a database datetime
	 * @param array $covers What it covers
	 * @param string $writtenBy Who or what wrote it, ie: 'server_ai: claude (claude-...)' or 'client: username'
	 * @return int|false The new text id
	 */
	function writeText($summary, $entryId, $groupValue, $text, $asOf, $covers, $writtenBy) {
		global $xoopsUser;
		$summaryId = intval($summary['summary_id']);
		$match = "summary_id = $summaryId AND entry_id = " . intval($entryId) . " AND group_value = '" . formulize_db_escape(strval($groupValue)) . "'";
		$this->db->queryF("UPDATE {$this->contentTable} SET is_current = 0 WHERE $match");
		$sql = "INSERT INTO {$this->contentTable} (summary_id, summary_name, form_id, entry_id, group_value, summary_text, as_of, covers, written_by, written_uid, is_current, created_at) VALUES ("
			. "$summaryId, '" . formulize_db_escape(strval($summary['name'])) . "', " . intval($summary['form_id']) . ", " . intval($entryId) . ", '" . formulize_db_escape(strval($groupValue)) . "', '"
			. formulize_db_escape(strval($text)) . "', " . ($asOf ? "'" . formulize_db_escape($asOf) . "'" : "NOW()") . ", '" . formulize_db_escape(json_encode($covers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . "', '"
			. formulize_db_escape(strval($writtenBy)) . "', " . ($xoopsUser ? intval($xoopsUser->getVar('uid')) : 0) . ", 1, NOW())";
		if (!$this->db->queryF($sql)) {
			return false;
		}
		$textId = intval($this->db->getInsertId());
		// keep the last few versions of each piece
		$old = array();
		if ($res = $this->db->query("SELECT text_id FROM {$this->contentTable} WHERE $match AND is_current = 0 ORDER BY text_id DESC")) {
			while ($row = $this->db->fetchRow($res)) {
				$old[] = intval($row[0]);
			}
		}
		$extra = array_slice($old, $this->revisionsToKeep - 1);
		if ($extra) {
			$this->db->queryF("DELETE FROM {$this->contentTable} WHERE text_id IN (" . implode(',', $extra) . ")");
		}
		return $textId;
	}

	/**
	 * Retire the current text of a piece that no longer applies, ie: its entry was deleted, or no longer matches the filter
	 * @param int $summaryId
	 * @param int $entryId For each_entry summaries, the main form entry. 0 otherwise.
	 * @param string $groupValue For per_group summaries, the group. '' otherwise.
	 * @return void
	 */
	function retireText($summaryId, $entryId, $groupValue = '') {
		$this->db->queryF("UPDATE {$this->contentTable} SET is_current = 0 WHERE summary_id = " . intval($summaryId) . " AND entry_id = " . intval($entryId) . " AND group_value = '" . formulize_db_escape(strval($groupValue)) . "'");
	}

	private function unpack($row) {
		$summary = $row;
		foreach (array('summary_id', 'form_id', 'include_connected_forms', 'built_from_summary_id', 'wait_minutes', 'refresh_every_minutes', 'all_scope_viewers', 'max_length', 'active', 'created_uid') as $field) {
			$summary[$field] = intval($row[$field]);
		}
		foreach (array('only_entries_where', 'readable_by_groups') as $field) {
			$decoded = json_decode(strval($row[$field]), true);
			$summary[$field] = is_array($decoded) ? $decoded : array();
		}
		return $summary;
	}

	private function unpackText($row) {
		$decoded = json_decode(strval($row['covers']), true);
		$row['covers'] = is_array($decoded) ? $decoded : array();
		foreach (array('text_id', 'summary_id', 'form_id', 'entry_id', 'written_uid', 'is_current') as $field) {
			$row[$field] = intval($row[$field]);
		}
		return $row;
	}
}
