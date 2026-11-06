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
 * Summaries: digests of the data in a form, for AI assistants to read so they can orient themselves before looking at
 * the data itself. The definitions and text are stored by formulizeSummariesHandler (class/summaries.php).
 *
 * How it works:
 * - A summary covers the entries in a form that match its filter, optionally with the entries connected to them in the
 *   primary relationship. It is written at one of three levels: one piece of text per entry, one per group of entries
 *   (grouped by the value of an element), or one for all of them.
 * - Each piece of text records when the data it is based on was read (as_of) and which entries it covers. Whether it is
 *   still current is worked out when it is read, by comparing that with the entries as they are now. Nothing has to
 *   remember to mark a summary as out of date.
 * - Pieces are always rebuilt from the source data (or, for a summary built from another summary, from that summary's
 *   current text), never patched, so mistakes don't accumulate.
 * - Server-written summaries are written by the site's AI model (formulizeAI_complete). When entries in scope change, a
 *   refresh is queued in the rules outbox (channel 'summary') to run once things have been quiet for a while, so a bulk
 *   import causes one refresh, not hundreds. There is only ever one refresh waiting for each summary.
 * - Client-written summaries are written by an AI client through the MCP server's write_summary tool. They are not
 *   refreshed on the server; the next client to read one is told whether it is current.
 * - Who may read a summary is decided per summary: webmasters, the groups it is shared with, and (by default) anyone who
 *   can see everything it covers. A summary blends entries together, so it can't be filtered entry by entry the way a
 *   list of entries is.
 */

define('FORMULIZE_SUMMARY_MAX_ENTRIES', 2000); // the most main form entries one summary can cover
define('FORMULIZE_SUMMARY_MAX_INPUT_CHARS', 200000); // the most data sent to the AI model when writing one piece
define('FORMULIZE_SUMMARY_PIECES_PER_RUN', 20); // the most pieces written in one refresh, before the rest are queued
define('FORMULIZE_SUMMARY_MAX_IDS_IN_COVERS', 5000); // above this, a piece records how many entries it covers but not which

/**
 * Make sure the hidden, system-managed System Summaries form exists, over the table where the text of summaries is kept
 * @return int|false The form id
 */
function formulize_summariesEnsureForm() {
	static $fid = null;
	if ($fid !== null) {
		return $fid;
	}
	global $xoopsDB;
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$fid = $form_handler->ensureAdHocTableForm(
		$xoopsDB->prefix('formulize_summaries'),
		'__system_summaries',
		'System Summaries',
		array(
			'excludeColumns' => array('covers', 'written_uid'),
			'columnLabels' => array(
				'text_id' => 'Text ID',
				'summary_id' => 'Summary ID',
				'summary_name' => 'Summary',
				'form_id' => 'Form ID',
				'entry_id' => 'Entry ID',
				'group_value' => 'Group',
				'summary_text' => 'Text',
				'as_of' => 'Data As Of',
				'written_by' => 'Written By',
				'is_current' => 'Current',
				'created_at' => 'Written At',
			),
			'defaultColumns' => array('summary_name', 'entry_id', 'group_value', 'summary_text', 'as_of', 'is_current'),
		)
	);
	return $fid;
}

/**
 * The user whose permissions a summary is written with: the person who set it up
 * @param array $summary
 * @return object|null A user object, or null if they no longer exist
 */
function formulize_summaryOwner($summary) {
	static $cache = array();
	$uid = intval($summary['created_uid'] ?? 0);
	if (!isset($cache[$uid])) {
		$member_handler = xoops_gethandler('member');
		$cache[$uid] = $uid ? $member_handler->getUser($uid) : null;
	}
	return is_object($cache[$uid]) ? $cache[$uid] : null;
}

/**
 * The relationship a summary reads its entries through: -1 (the primary relationship) when it includes connected forms
 * and the form is in one, otherwise 0 (the form alone)
 * @param array $summary
 * @return int
 */
function formulize_summaryRelationship($summary) {
	if (empty($summary['include_connected_forms'])) {
		return 0;
	}
	include_once XOOPS_ROOT_PATH . '/modules/formulize/include/readentries.php';
	return formulize_apiValidateRelationshipId(-1, $summary['form_id']) ? -1 : 0;
}

/**
 * The forms a summary covers: its main form, and if it includes connected forms, the forms connected to it in the
 * primary relationship
 * @param array $summary
 * @return array Form ids
 */
function formulize_summaryScopeForms($summary) {
	static $cache = array();
	$key = intval($summary['form_id']) . '/' . (empty($summary['include_connected_forms']) ? 0 : 1);
	if (!isset($cache[$key])) {
		$fids = array(intval($summary['form_id']));
		if (formulize_summaryRelationship($summary) == -1) {
			$relationship = formulize_apiValidateRelationshipId(-1, $summary['form_id']);
			$relationship_handler = xoops_getmodulehandler('frameworks', 'formulize');
			foreach ($relationship_handler->getLinksGroupedByForm($relationship, $summary['form_id']) as $links) {
				foreach ($links as $link) {
					$fids[] = intval($link['form1']);
					$fids[] = intval($link['form2']);
				}
			}
		}
		$cache[$key] = array_values(array_unique(array_filter($fids)));
	}
	return $cache[$key];
}

/**
 * Read the entries a summary covers, with the permissions of the person who set it up, and divide them into the
 * pieces the summary is written in
 *
 * @param array $summary
 * @param array $options Optional:
 *   - 'entry_id': only this main form entry
 *   - 'light': true to read only enough to know which entries are covered (for checking whether pieces are current)
 * @return array
 *   - 'pieces': piece key => array('entry_id', 'group_value', 'rows' (readable rows, see formulize_renderEntriesAsRows), 'ids' (fid => entry ids))
 *   - 'forms': fid => form handle, for the forms the entries came from
 *   - 'fields': the element handles that were read, by form id
 *   - 'truncated': true if there were more entries than one summary can cover
 * @throws FormulizeApiException
 */
function formulize_summaryReadScope($summary, $options = array()) {
	include_once XOOPS_ROOT_PATH . '/modules/formulize/include/readentries.php';
	$owner = formulize_summaryOwner($summary);
	if (!$owner) {
		throw new FormulizeApiException('The person who set up this summary no longer has an account, so the summary has no permissions to read the data with. Update the summary to give it a new owner.', 'permission_denied');
	}
	$relationship = formulize_summaryRelationship($summary);
	$dataHandler = new formulizeDataHandler(false);
	$metadataFields = $dataHandler->metadataFields;

	// every element the owner can see, in every form in scope, or when only checking which entries are covered, one per form
	$allowed = array_keys(formulize_apiAllowedFieldHandles($summary['form_id'], $relationship, $owner));
	$elementHandles = array_values(array_diff($allowed, $metadataFields));
	$fieldsByForm = $elementHandles ? formulize_apiValidateElementHandles($elementHandles, $summary['form_id']) : array();
	if (!empty($options['light'])) {
		foreach ($fieldsByForm as $thisFid => $handles) {
			$keep = array(reset($handles));
			if ($thisFid == $summary['form_id'] AND $summary['level'] == 'per_group' AND in_array($summary['group_by'], $handles)) {
				$keep[] = $summary['group_by'];
			}
			$fieldsByForm[$thisFid] = array_unique($keep);
		}
	}
	$fields = array();
	foreach ($fieldsByForm as $handles) {
		$fields = array_merge($fields, $handles);
	}
	if (!$fields) {
		$fields = array('entry_id');
	}

	$filter = array_values((array) $summary['only_entries_where']);
	if (!empty($options['entry_id'])) {
		$filter[] = array('element' => 'entry_id', 'operator' => '=', 'value' => intval($options['entry_id']));
	}
	$result = formulize_readEntries($summary['form_id'], array(
		'fields' => array_values(array_unique($fields)),
		'filter' => $filter,
		'andOr' => 'AND',
		'limitStart' => 0,
		'limitSize' => FORMULIZE_SUMMARY_MAX_ENTRIES + 1,
		'relationship' => $relationship,
	), $owner);

	$forms = array();
	foreach (array_keys($result['fieldsByForm']) as $thisFid) {
		$forms[$thisFid] = $thisFid == $result['fid'] ? $result['formHandle'] : getFormHandle($thisFid);
	}
	$fidsByHandle = array_flip($forms);
	$readFields = $result['fieldsByForm'];
	$rows = formulize_renderEntriesAsRows($result);
	$truncated = count($rows) > FORMULIZE_SUMMARY_MAX_ENTRIES;
	if ($truncated) {
		$rows = array_slice($rows, 0, FORMULIZE_SUMMARY_MAX_ENTRIES);
	}

	$pieces = array();
	foreach ($rows as $row) {
		switch ($summary['level']) {
			case 'each_entry':
				$key = intval($row['entry_id']);
				$entryId = $key;
				$groupValue = '';
				break;
			case 'per_group':
				$groupValue = formulize_summaryGroupValue($row[$summary['group_by']] ?? '');
				$key = $groupValue;
				$entryId = 0;
				break;
			default:
				$key = 0;
				$entryId = 0;
				$groupValue = '';
		}
		if (!isset($pieces[$key])) {
			$pieces[$key] = array('entry_id' => $entryId, 'group_value' => $groupValue, 'rows' => array(), 'ids' => array($summary['form_id'] => array()));
		}
		$pieces[$key]['rows'][] = $row;
		$pieces[$key]['ids'][$summary['form_id']][] = intval($row['entry_id']);
		foreach ((array) ($row['related'] ?? array()) as $formHandle => $children) {
			$childFid = $fidsByHandle[$formHandle] ?? 0;
			foreach ($children as $child) {
				if ($childFid) {
					$pieces[$key]['ids'][$childFid][] = intval($child['entry_id']);
				}
			}
		}
	}
	foreach ($pieces as $key => $piece) {
		foreach ($piece['ids'] as $thisFid => $ids) {
			$ids = array_values(array_unique($ids));
			sort($ids);
			$pieces[$key]['ids'][$thisFid] = $ids;
		}
	}
	if (is_array($pieces) AND $summary['level'] == 'per_group') {
		ksort($pieces, SORT_STRING);
	}
	return array('pieces' => $pieces, 'forms' => $forms, 'fields' => $readFields, 'truncated' => $truncated);
}

/**
 * The group a per_group summary puts an entry in, from the readable value of the element it groups by
 * @param mixed $value
 * @return string
 */
function formulize_summaryGroupValue($value) {
	if (is_array($value)) {
		$value = implode(', ', array_map('strval', $value));
	}
	$value = trim(strval($value));
	return $value === '' ? '(blank)' : substr($value, 0, 255);
}

/**
 * When each of a set of entries was last changed
 * @param array $idsByForm fid => entry ids
 * @return array fid => array(entry id => mod_datetime)
 */
function formulize_summaryChangeTimes($idsByForm) {
	global $xoopsDB;
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$times = array();
	foreach ($idsByForm as $fid => $ids) {
		$times[$fid] = array();
		$ids = array_filter(array_map('intval', (array) $ids));
		if (!$ids OR !$formObject = $form_handler->get($fid)) {
			continue;
		}
		$table = $xoopsDB->prefix('formulize_' . $formObject->getVar('form_handle'));
		foreach (array_chunk($ids, 1000) as $chunk) {
			if ($res = $xoopsDB->query("SELECT entry_id, mod_datetime FROM $table WHERE entry_id IN (" . implode(',', $chunk) . ")")) {
				while ($row = $xoopsDB->fetchRow($res)) {
					$times[$fid][intval($row[0])] = strval($row[1]);
				}
			}
		}
	}
	return $times;
}

/**
 * What a piece of a summary covers, recorded with its text so whether it is still current can be worked out later
 * @param array $summary
 * @param array $piece From formulize_summaryReadScope
 * @param array $scope The rest of the result of formulize_summaryReadScope
 * @param array $sourceTexts For a summary built from another one, the source texts used: entry id => content row
 * @return array
 */
function formulize_summaryCovers($summary, $piece, $scope, $sourceTexts = array()) {
	$times = formulize_summaryChangeTimes($piece['ids']);
	$forms = array();
	foreach ($piece['ids'] as $fid => $ids) {
		$forms[$fid] = array(
			'form_handle' => $scope['forms'][$fid] ?? getFormHandle($fid),
			'entry_count' => count($ids),
			'latest_change' => $times[$fid] ? max($times[$fid]) : null,
		);
		if (count($ids) <= FORMULIZE_SUMMARY_MAX_IDS_IN_COVERS) {
			$forms[$fid]['entry_ids'] = $ids;
		}
	}
	$covers = array(
		'main_form_id' => intval($summary['form_id']),
		'main_entry_count' => count($piece['ids'][$summary['form_id']] ?? array()),
		'forms' => $forms,
		'elements' => $scope['fields'],
		'truncated' => !empty($scope['truncated']),
	);
	if ($sourceTexts) {
		$covers['built_from'] = array(
			'summary_id' => intval($summary['built_from_summary_id']),
			'text_ids' => array_values(array_map(function ($text) { return intval($text['text_id']); }, $sourceTexts)),
		);
	}
	return $covers;
}

/**
 * Whether a piece of a summary is still current: compare what it covered with the entries as they are now
 *
 * @param array|false $text The current content row of the piece, from formulizeSummariesHandler::currentText(), or false if it has never been written
 * @param array|null $piece The piece as it is now, from formulize_summaryReadScope, or null if no entries are in it any more
 * @param array $sourceTexts For a summary built from another one, that summary's current texts for the entries in the piece
 * @return array status ('current', 'stale', 'not_written' or 'no_longer_applies'), changed_since, added_since, deleted_since, newest_change
 */
function formulize_summaryFreshness($text, $piece, $sourceTexts = array()) {
	$freshness = array('status' => 'current', 'changed_since' => 0, 'added_since' => 0, 'deleted_since' => 0, 'newest_change' => null);
	if (!$text) {
		$freshness['status'] = $piece ? 'not_written' : 'no_longer_applies';
		return $freshness;
	}
	if (!$piece) {
		$freshness['status'] = 'no_longer_applies';
		return $freshness;
	}
	$asOf = strval($text['as_of']);
	$covered = (array) ($text['covers']['forms'] ?? array());
	$times = formulize_summaryChangeTimes($piece['ids']);
	foreach ($piece['ids'] as $fid => $ids) {
		$before = $covered[$fid] ?? array('entry_count' => 0, 'entry_ids' => array());
		if (isset($before['entry_ids'])) {
			$freshness['added_since'] += count(array_diff($ids, $before['entry_ids']));
			$freshness['deleted_since'] += count(array_diff($before['entry_ids'], $ids));
		} elseif (count($ids) != intval($before['entry_count'])) {
			// too many to have recorded which ones, so only the difference in the count is known
			$difference = count($ids) - intval($before['entry_count']);
			$freshness[$difference > 0 ? 'added_since' : 'deleted_since'] += abs($difference);
		}
		foreach ($times[$fid] ?? array() as $entryId => $modified) {
			if ($modified > $asOf AND (!isset($before['entry_ids']) OR in_array($entryId, $before['entry_ids']))) {
				$freshness['changed_since']++;
			}
			if ($freshness['newest_change'] === null OR $modified > $freshness['newest_change']) {
				$freshness['newest_change'] = $modified;
			}
		}
	}
	foreach ($covered as $fid => $before) {
		if (!isset($piece['ids'][$fid]) AND isset($before['entry_ids'])) {
			$freshness['deleted_since'] += count($before['entry_ids']);
		}
	}
	if ($sourceTexts OR isset($text['covers']['built_from'])) {
		$sourceIds = array_values(array_map(function ($source) { return intval($source['text_id']); }, $sourceTexts));
		sort($sourceIds);
		$usedIds = array_map('intval', (array) ($text['covers']['built_from']['text_ids'] ?? array()));
		sort($usedIds);
		if ($sourceIds != $usedIds) {
			$freshness['source_summary_changed'] = true;
		}
	}
	if ($freshness['changed_since'] OR $freshness['added_since'] OR $freshness['deleted_since'] OR !empty($freshness['source_summary_changed'])) {
		$freshness['status'] = 'stale';
	}
	return $freshness;
}

/**
 * For a summary built from another summary, the source summary's current texts for the entries in a piece
 * @param array $summary
 * @param array $piece
 * @return array entry id => content row
 */
function formulize_summarySourceTexts($summary, $piece) {
	if (empty($summary['built_from_summary_id']) OR !$piece) {
		return array();
	}
	$handler = xoops_getmodulehandler('summaries', 'formulize');
	return $handler->currentTexts($summary['built_from_summary_id'], $piece['ids'][$summary['form_id']] ?? array());
}

/**
 * The state of every piece of a summary
 * @param array $summary
 * @param mixed $onlyKey Optional. Only this piece: the entry id for each_entry summaries, the group value for per_group, 0 for whole_scope.
 * @return array
 *   - 'pieces': key => array('entry_id', 'group_value', 'text' (content row or false), 'freshness')
 *   - 'scope': the result of formulize_summaryReadScope (light)
 *   - 'counts': how many pieces are current, stale, not_written and no_longer_applies
 * @throws FormulizeApiException
 */
function formulize_summaryState($summary, $onlyKey = null) {
	$handler = xoops_getmodulehandler('summaries', 'formulize');
	$readOptions = array('light' => true);
	if ($onlyKey !== null AND $summary['level'] == 'each_entry') {
		$readOptions['entry_id'] = intval($onlyKey);
	}
	$scope = formulize_summaryReadScope($summary, $readOptions);
	$texts = $handler->currentTexts($summary['summary_id'], ($onlyKey !== null AND $summary['level'] == 'each_entry') ? array(intval($onlyKey)) : null);
	$keys = array_unique(array_merge(array_map('strval', array_keys($scope['pieces'])), array_map('strval', array_keys($texts))));
	if ($onlyKey !== null) {
		$keys = array(strval($onlyKey));
	}
	$state = array('pieces' => array(), 'scope' => $scope, 'counts' => array('current' => 0, 'stale' => 0, 'not_written' => 0, 'no_longer_applies' => 0));
	foreach ($keys as $key) {
		$lookup = $summary['level'] == 'per_group' ? $key : intval($key);
		$piece = $scope['pieces'][$lookup] ?? null;
		$text = $texts[$lookup] ?? false;
		if (!$piece AND !$text) {
			if ($onlyKey === null) {
				continue;
			}
		}
		$freshness = formulize_summaryFreshness($text, $piece, formulize_summarySourceTexts($summary, $piece));
		$state['counts'][$freshness['status']]++;
		$state['pieces'][$key] = array(
			'entry_id' => $piece ? $piece['entry_id'] : ($text ? $text['entry_id'] : ($summary['level'] == 'each_entry' ? intval($key) : 0)),
			'group_value' => $piece ? $piece['group_value'] : ($text ? $text['group_value'] : ($summary['level'] == 'per_group' ? strval($key) : '')),
			'text' => $text,
			'freshness' => $freshness,
		);
	}
	return $state;
}

/**
 * Write one piece of a summary with the site's AI model
 * @param array $summary
 * @param array $piece From formulize_summaryReadScope (not light)
 * @param array $scope The rest of the result of formulize_summaryReadScope
 * @return int The new text id
 * @throws FormulizeAIException|Exception
 */
function formulize_summaryWritePiece($summary, $piece, $scope) {
	global $xoopsDB;
	include_once XOOPS_ROOT_PATH . "/ai/ai_providers.php";
	if (!formulize_aiInRulesEnabled()) {
		throw new FormulizeAIException("AI in rules and summaries is turned off. Turn it on in Settings, AI, or have AI clients write this summary instead.");
	}
	$handler = xoops_getmodulehandler('summaries', 'formulize');
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$formObject = $form_handler->get($summary['form_id']);
	$formTitle = $formObject ? trans($formObject->getVar('title')) : 'form ' . $summary['form_id'];

	$sourceTexts = formulize_summarySourceTexts($summary, $piece);
	if ($summary['built_from_summary_id']) {
		$lines = array();
		foreach ($sourceTexts as $entryId => $source) {
			$lines[] = "Entry $entryId: " . $source['summary_text'];
		}
		$data = implode("\n\n", $lines);
		$dataDescription = "Below are the summaries of " . count($sourceTexts) . " entries in the form \"$formTitle\", one per entry.";
	} else {
		$lines = array();
		foreach ($piece['rows'] as $row) {
			$lines[] = json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
		}
		$data = implode("\n", $lines);
		$dataDescription = "Below are " . count($piece['rows']) . " entries from the form \"$formTitle\", one JSON object per line."
			. (count($scope['forms']) > 1 ? " Entries in connected forms are listed under \"related\" in each one, by form." : "");
	}
	$truncatedInput = false;
	if (strlen($data) > FORMULIZE_SUMMARY_MAX_INPUT_CHARS) {
		$data = substr($data, 0, FORMULIZE_SUMMARY_MAX_INPUT_CHARS);
		$truncatedInput = true;
	}
	if ($summary['level'] == 'each_entry') {
		$aboutPiece = "This summary is about entry " . intval($piece['entry_id']) . ".";
	} elseif ($summary['level'] == 'per_group') {
		$aboutPiece = "This summary is about the entries where \"" . $summary['group_by'] . "\" is \"" . $piece['group_value'] . "\".";
	} else {
		$aboutPiece = "This summary is about all the entries below.";
	}

	$system = "You write summaries of data from a database, for AI assistants to read so they can understand the data quickly before they look at it in detail."
		. " State only what the data shows; do not guess or fill in gaps. Include counts where they help, notable or unusual items, patterns, and anything missing or inconsistent in the records."
		. " Refer to entries by their entry_id where that helps someone find them."
		. " Write plain text, with no heading or preamble, in no more than " . intval($summary['max_length']) . " characters.";
	$prompt = "Summary: " . $summary['name'] . "\n"
		. ($summary['instructions'] !== '' ? "What it should cover: " . $summary['instructions'] . "\n" : "")
		. $aboutPiece . "\n"
		. ($truncatedInput || !empty($scope['truncated']) ? "Note: there was too much data to include all of it, so this is only part of it. Say so in the summary.\n" : "")
		. "\n$dataDescription\n\n$data";

	$asOf = $piece['as_of'] ?? null;
	$reply = formulizeAI_complete($system, $prompt, array('max_tokens' => intval(ceil($summary['max_length'] / 3)) + 256));
	$text = trim(strval($reply['text']));
	if ($text === '') {
		throw new FormulizeAIException('The AI model replied with no text.', true);
	}
	$covers = formulize_summaryCovers($summary, $piece, $scope, $sourceTexts);
	$textId = $handler->writeText($summary, $piece['entry_id'], $piece['group_value'], $text, $asOf, $covers, 'server_ai: ' . $reply['provider'] . ' (' . $reply['model'] . ')');
	if (!$textId) {
		throw new Exception('Could not save the summary text: ' . $xoopsDB->error());
	}
	return $textId;
}

/**
 * Bring a server-written summary up to date: write the pieces that are stale or have never been written, and retire the
 * ones whose entries have gone
 *
 * @param array $summary
 * @param mixed $onlyKey Optional. Only this piece: the entry id for each_entry summaries, the group value for per_group.
 * @param bool|string $force Optional. True to rewrite the pieces even if they are current, or a database datetime to
 *   rewrite the ones written before then (so a rewrite done over several runs doesn't start again each time).
 * @param int $limit Optional. The most pieces to write.
 * @return array 'written' => keys, 'retired' => keys, 'remaining' => how many still need writing, 'errors' => key => message, 'retry' => true if an error might go away later
 */
function formulize_summaryRefresh($summary, $onlyKey = null, $force = false, $limit = FORMULIZE_SUMMARY_PIECES_PER_RUN) {
	global $xoopsDB;
	$handler = xoops_getmodulehandler('summaries', 'formulize');
	$report = array('written' => array(), 'retired' => array(), 'remaining' => 0, 'errors' => array(), 'retry' => false);
	$state = formulize_summaryState($summary, $onlyKey);
	$toWrite = array();
	foreach ($state['pieces'] as $key => $pieceState) {
		$status = $pieceState['freshness']['status'];
		if ($status == 'no_longer_applies') {
			if ($pieceState['text']) {
				$handler->retireText($summary['summary_id'], $pieceState['entry_id'], $pieceState['group_value']);
				$report['retired'][] = $key;
			}
		} elseif ($force === true OR $status != 'current' OR (is_string($force) AND $pieceState['text'] AND $pieceState['text']['created_at'] < $force)) {
			$toWrite[] = $key;
		}
	}
	$report['remaining'] = max(0, count($toWrite) - $limit);
	$toWrite = array_slice($toWrite, 0, $limit);
	$scope = null;
	foreach ($toWrite as $key) {
		try {
			// read the time before the data, so anything that changes while the AI is working shows up as a change after as_of
			if ($summary['level'] == 'each_entry' OR $scope === null) {
				$res = $xoopsDB->query("SELECT NOW()");
				$now = $res ? $xoopsDB->fetchRow($res) : array(null);
				// each entry is read just before it is written; the other levels read all the entries once
				$scope = formulize_summaryReadScope($summary, $summary['level'] == 'each_entry' ? array('entry_id' => intval($key)) : array());
			}
			$lookup = $summary['level'] == 'per_group' ? strval($key) : intval($key);
			if (!isset($scope['pieces'][$lookup])) {
				continue;
			}
			$piece = $scope['pieces'][$lookup];
			$piece['as_of'] = $now[0];
			formulize_summaryWritePiece($summary, $piece, $scope);
			$report['written'][] = $key;
		} catch (FormulizeAIException $e) {
			$report['errors'][$key] = $e->getMessage();
			if ($e->isRetryable()) {
				$report['retry'] = true;
				$report['remaining'] += count($toWrite) - count($report['written']) - count($report['errors']);
				break; // the AI provider is not answering, so don't try the rest now
			}
		} catch (Throwable $e) {
			$report['errors'][$key] = $e->getMessage();
		}
	}
	if ($report['written']) {
		// summaries built from this one are now out of date
		foreach ($handler->activeDefinitions() as $other) {
			if ($other['built_from_summary_id'] == $summary['summary_id'] AND $other['written_by'] == 'server_ai' AND $other['keep_current'] == 'on_change') {
				formulize_summaryQueueRefresh($other, $other['wait_minutes']);
			}
		}
	}
	return $report;
}

/**
 * Note that entries in a form have been written or deleted, so the server-written summaries that cover the form are
 * refreshed once things are quiet. Called from formulizeDataHandler::writeEntry() and deleteEntries().
 * @param int $fid
 * @return void
 */
function formulize_summariesRecordChange($fid) {
	$fid = intval($fid);
	static $queuedThisRequest = array();
	if (!$fid OR isset($queuedThisRequest[$fid])) {
		return;
	}
	$queuedThisRequest[$fid] = true;
	$handler = xoops_getmodulehandler('summaries', 'formulize');
	$definitions = $handler->activeDefinitions();
	if (!$definitions) {
		return;
	}
	foreach ($definitions as $summary) {
		if ($summary['written_by'] != 'server_ai' OR $summary['keep_current'] != 'on_change' OR $summary['built_from_summary_id']) {
			continue; // built-from summaries are queued when the summary they are built from is refreshed
		}
		if (in_array($fid, formulize_summaryScopeForms($summary))) {
			formulize_summaryQueueRefresh($summary, $summary['wait_minutes']);
		}
	}
}

/**
 * Queue a refresh of a summary in the rules outbox. If one is already waiting, it is put off until things have been
 * quiet for the wait time, but never by more than an hour past when it was first queued, so a summary of a form that
 * changes all day long is still refreshed.
 * @param array $summary
 * @param int $waitMinutes
 * @param string $rewriteBefore Optional. A database datetime: rewrite every piece written before then, even if it is current.
 * @return int|false The outbox id
 */
function formulize_summaryQueueRefresh($summary, $waitMinutes, $rewriteBefore = '') {
	global $xoopsDB;
	$table = $xoopsDB->prefix('formulize_rule_outbox');
	$target = 'summary:' . intval($summary['summary_id']);
	$waitMinutes = max(0, intval($waitMinutes));
	$payload = array('summary_id' => intval($summary['summary_id']));
	if ($rewriteBefore) {
		$payload['rewrite_before'] = strval($rewriteBefore);
	}
	$res = $xoopsDB->query("SELECT outbox_id, payload FROM $table WHERE channel = 'summary' AND target = '$target' AND status = 'pending' ORDER BY outbox_id LIMIT 1");
	if ($res AND $row = $xoopsDB->fetchRow($res)) {
		$set = array();
		if ($waitMinutes == 0) {
			$set[] = "next_attempt_at = NOW()";
		} else {
			$set[] = "next_attempt_at = GREATEST(next_attempt_at, LEAST(DATE_ADD(NOW(), INTERVAL $waitMinutes MINUTE), DATE_ADD(created_at, INTERVAL " . ($waitMinutes + 60) . " MINUTE)))";
		}
		$existing = json_decode(strval($row[1]), true);
		if ($rewriteBefore AND strval($existing['rewrite_before'] ?? '') < $rewriteBefore) {
			$existing['rewrite_before'] = strval($rewriteBefore);
			$set[] = "payload = '" . formulize_db_escape(json_encode($existing)) . "'";
		}
		$xoopsDB->queryF("UPDATE $table SET " . implode(', ', $set) . " WHERE outbox_id = " . intval($row[0]) . " AND attempts = 0");
		if ($waitMinutes == 0) {
			formulize_rulesEnsureShutdown();
		}
		return intval($row[0]);
	}
	$sql = "INSERT INTO $table (rule_id, fid, entry_id, event, channel, target, payload, status, attempts, created_at, next_attempt_at) VALUES (0, "
		. intval($summary['form_id']) . ", 0, 'summary_refresh', 'summary', '$target', '" . formulize_db_escape(json_encode($payload)) . "', 'pending', 0, NOW(), DATE_ADD(NOW(), INTERVAL $waitMinutes MINUTE))";
	if (!$xoopsDB->queryF($sql)) {
		return false;
	}
	if ($waitMinutes == 0) {
		formulize_rulesEnsureShutdown();
	}
	return intval($xoopsDB->getInsertId());
}

/**
 * Run a summary refresh that was waiting in the outbox. Called from formulize_ruleProcessOutbox().
 * @param int $outboxId
 * @return string The status afterwards: delivered (done), pending (will retry, or more to do) or failed
 */
function formulize_summaryRunJob($outboxId) {
	global $xoopsDB;
	$table = $xoopsDB->prefix('formulize_rule_outbox');
	$res = $xoopsDB->query("SELECT * FROM $table WHERE outbox_id = " . intval($outboxId));
	if (!$res OR !$row = $xoopsDB->fetchArray($res)) {
		return 'missing';
	}
	$payload = json_decode(strval($row['payload']), true);
	$handler = xoops_getmodulehandler('summaries', 'formulize');
	$summary = $handler->get(intval($payload['summary_id'] ?? 0));
	$attempts = intval($row['attempts']) + 1;
	if (!$summary OR !$summary['active'] OR $summary['written_by'] != 'server_ai') {
		$xoopsDB->queryF("UPDATE $table SET status = 'failed', attempts = $attempts, last_error = 'The summary was deleted, turned off, or is written by AI clients, before it could be refreshed.', next_attempt_at = NULL WHERE outbox_id = " . intval($outboxId));
		return 'failed';
	}
	if (!empty($GLOBALS['formulize_inRulesShutdown'])) {
		// asking AI takes a while, so let the page go first. Not when run from the autotask, which may run in the middle of a page.
		formulize_rulesReleaseClient();
	}
	@set_time_limit(300);
	try {
		$report = formulize_summaryRefresh($summary, null, strval($payload['rewrite_before'] ?? '') ?: false);
	} catch (Throwable $e) {
		$report = array('written' => array(), 'retired' => array(), 'remaining' => 0, 'errors' => array('all' => $e->getMessage()), 'retry' => false);
	}
	$outcome = formulize_summaryDescribeReport($report);
	if ($report['retry'] AND $attempts < FORMULIZE_RULE_WEBHOOK_MAX_ATTEMPTS) {
		$backoffMinutes = array(1 => 1, 2 => 5, 3 => 30, 4 => 120, 5 => 720);
		$xoopsDB->queryF("UPDATE $table SET status = 'pending', attempts = $attempts, last_error = '" . formulize_db_escape($outcome) . "', next_attempt_at = DATE_ADD(NOW(), INTERVAL " . intval($backoffMinutes[$attempts] ?? 720) . " MINUTE) WHERE outbox_id = " . intval($outboxId));
		return 'pending';
	}
	if ($report['remaining'] > 0 AND count($report['written']) > 0) {
		// more to do: carry on in a minute, so one run doesn't take too long
		$xoopsDB->queryF("UPDATE $table SET status = 'pending', last_error = '" . formulize_db_escape($outcome) . "', next_attempt_at = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE outbox_id = " . intval($outboxId));
		return 'pending';
	}
	$status = ($report['errors'] AND !$report['written']) ? 'failed' : 'delivered';
	$xoopsDB->queryF("UPDATE $table SET status = '$status', attempts = $attempts, last_error = '" . formulize_db_escape($outcome) . "', " . ($status == 'delivered' ? "delivered_at = NOW(), " : "") . "next_attempt_at = NULL WHERE outbox_id = " . intval($outboxId));
	return $status;
}

/**
 * A short description of what a refresh did
 * @param array $report From formulize_summaryRefresh
 * @return string
 */
function formulize_summaryDescribeReport($report) {
	$parts = array(count($report['written']) . " written");
	if ($report['retired']) {
		$parts[] = count($report['retired']) . " no longer applying, retired";
	}
	if ($report['remaining']) {
		$parts[] = $report['remaining'] . " still to write";
	}
	foreach ($report['errors'] as $key => $message) {
		$parts[] = "error" . ($key === 'all' ? "" : " ($key)") . ": $message";
	}
	return implode("; ", $parts);
}

/**
 * Queue refreshes of scheduled summaries that are due. Called from the rules autotask.
 * @return void
 */
function formulize_summariesQueueScheduled() {
	global $xoopsDB;
	$handler = xoops_getmodulehandler('summaries', 'formulize');
	foreach ($handler->activeDefinitions() as $summary) {
		if ($summary['written_by'] != 'server_ai' OR $summary['keep_current'] != 'scheduled') {
			continue;
		}
		$sql = "SELECT MAX(created_at) FROM " . $xoopsDB->prefix('formulize_summaries') . " WHERE summary_id = " . intval($summary['summary_id']) . " AND is_current = 1 HAVING MAX(created_at) > DATE_SUB(NOW(), INTERVAL " . intval($summary['refresh_every_minutes']) . " MINUTE)";
		$res = $xoopsDB->query($sql);
		if ($res AND $xoopsDB->getRowsNum($res) > 0) {
			continue; // written recently enough
		}
		formulize_summaryQueueRefresh($summary, 0);
	}
}

/**
 * Can this person read a summary? Webmasters can, the groups it is shared with can, and if it is set up that way, so
 * can anyone who can see everything it covers: every entry, and every element the summary was written from, in every
 * form in scope.
 * @param array $summary
 * @param object|int|null $user Optional. A user object or id. Default is the current user.
 * @return bool
 */
function formulize_summaryUserCanRead($summary, $user = null) {
	include_once XOOPS_ROOT_PATH . '/modules/formulize/include/readentries.php';
	$user = formulize_resolveUserObject($user);
	$groups = formulize_userGroups($user);
	if (in_array(XOOPS_GROUP_ADMIN, $groups)) {
		return true;
	}
	if (array_intersect($groups, array_map('intval', (array) $summary['readable_by_groups']))) {
		return true;
	}
	if (empty($summary['all_scope_viewers']) OR !$user) {
		return false;
	}
	$gperm_handler = xoops_gethandler('groupperm');
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$mid = getFormulizeModId();
	foreach (formulize_summaryScopeForms($summary) as $fid) {
		if (!$gperm_handler->checkRight('view_form', $fid, $groups, $mid) OR !$gperm_handler->checkRight('view_globalscope', $fid, $groups, $mid)) {
			return false;
		}
		if (trim(strval($form_handler->getPerGroupFilterWhereClause($fid, '', $groups))) !== '') {
			return false;
		}
	}
	$owner = formulize_summaryOwner($summary);
	if ($owner) {
		$relationship = formulize_summaryRelationship($summary);
		$ownerFields = array_keys(formulize_apiAllowedFieldHandles($summary['form_id'], $relationship, $owner));
		$userFields = array_keys(formulize_apiAllowedFieldHandles($summary['form_id'], $relationship, $user));
		if (array_diff($ownerFields, $userFields)) {
			return false;
		}
	}
	return true;
}

/**
 * Describe what a summary covers, in a sentence
 * @param array $summary
 * @return string
 */
function formulize_summaryDescribeScope($summary) {
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$formObject = $form_handler->get($summary['form_id']);
	$title = $formObject ? trans($formObject->getVar('title')) : 'form ' . $summary['form_id'];
	$text = ($summary['only_entries_where'] ? "Entries in \"$title\" that match a filter" : "All entries in \"$title\"");
	if (count(formulize_summaryScopeForms($summary)) > 1) {
		$titles = array();
		foreach (formulize_summaryScopeForms($summary) as $fid) {
			if ($fid != $summary['form_id'] AND $connected = $form_handler->get($fid)) {
				$titles[] = '"' . trans($connected->getVar('title')) . '"';
			}
		}
		$text .= ", with their connected entries in " . implode(', ', $titles);
	}
	switch ($summary['level']) {
		case 'each_entry':
			$text .= "; one summary for each entry";
			break;
		case 'per_group':
			$text .= "; one summary for each value of " . $summary['group_by'];
			break;
		default:
			$text .= "; one summary of all of them";
	}
	if ($summary['built_from_summary_id']) {
		$text .= ", written from the per-entry summaries in summary " . intval($summary['built_from_summary_id']);
	}
	return $text . '.';
}

/**
 * The get_entries_from_form arguments that would read the data a piece of a summary was written from
 * @param array $summary
 * @param array $pieceState From formulize_summaryState
 * @return array
 */
function formulize_summaryVerifyWith($summary, $pieceState) {
	$filter = array_values((array) $summary['only_entries_where']);
	if ($summary['level'] == 'each_entry') {
		$filter[] = array('element' => 'entry_id', 'operator' => '=', 'value' => intval($pieceState['entry_id']));
	} elseif ($summary['level'] == 'per_group') {
		$filter[] = array('element' => $summary['group_by'], 'operator' => '=', 'value' => $pieceState['group_value'] === '(blank)' ? '{BLANK}' : strval($pieceState['group_value']));
	}
	$arguments = array('form_id' => intval($summary['form_id']));
	if ($filter) {
		$arguments['filter'] = $filter;
	}
	if (formulize_summaryRelationship($summary) == -1) {
		$arguments['relationship_id'] = -1;
	}
	return $arguments;
}
