<?php

/**
 * MCP tools for summaries: digests of the data in a form, which AI assistants read to orient themselves before looking at
 * the data itself. See modules/formulize/include/summaries.php for how summaries work.
 *
 * Everybody gets list_summaries and get_summary, limited to the summaries they can read. Webmasters also get the tools
 * that set summaries up and write them.
 */
trait summaryTools {

	/**
	 * Add the summary tools to $this->tools. Called at the end of registerTools().
	 * @return void
	 */
	private function registerSummaryTools() {

		$this->tools['list_summaries'] = [
			'name' => 'list_summaries',
			'description' => 'List the summaries in this system that you can read. A summary is a digest of the data in a form (optionally with the entries in connected forms), written by AI and kept up to date as the data changes. They exist so that you can understand an application quickly, without reading every entry. When you are orienting yourself in an application, check for summaries early.

Each summary is written at one of three levels: "each_entry" (one summary per entry), "per_group" (one per value of an element, such as one per department), or "whole_scope" (one summary of all the entries it covers).

Each summary reports its freshness, which is worked out from the data, not written by anyone: "current" means nothing it covers has changed since it was written. "stale" means entries were changed, added or deleted since; the counts say how many. For summaries with several pieces of text, the counts of pieces in each state are given. Use get_summary to read one.',
			'inputSchema' => [
				'type' => 'object',
				'properties' => [
					'form_id' => [
						'type' => 'integer',
						'description' => 'Optional. Only summaries about this form.'
					],
					'application_id' => [
						'type' => 'integer',
						'description' => 'Optional. Only summaries about forms in this application.'
					]
				]
			]
		];

		$this->tools['get_summary'] = [
			'name' => 'get_summary',
			'description' => 'Read a summary: its text, when the data it is based on was read (as_of), what it covers, and whether it is still current.

How to use what you get:
- freshness.status "current": nothing the summary covers has changed since it was written. Use it to orient yourself, and verify specific facts against the data before you rely on them for something important.
- "stale": entries were changed, added or deleted since it was written (freshness says how many, and when the newest change was). Use it as a map, but check what changed before you rely on it.
- "not_written": there is no text yet for this piece.
- "no_longer_applies": the entry or group this text was about is no longer covered by the summary.
Each piece includes verify_with: the arguments for get_entries_from_form that read the data the piece was written from. Add the elements you need.

For a summary with one piece per entry, give entry_id to read one entry\'s summary. Without it, you get the pieces in order of entry id, a page at a time. For a summary with one piece per group, give group_value to read one group\'s, or leave it out to get all of them.',
			'inputSchema' => [
				'type' => 'object',
				'properties' => [
					'summary_id' => [
						'type' => 'integer',
						'description' => 'Required. The summary to read. Use list_summaries to find summary ids.'
					],
					'entry_id' => [
						'type' => 'integer',
						'description' => 'Optional. For a summary with one piece per entry, the entry in the summary\'s form whose summary you want.'
					],
					'group_value' => [
						'type' => 'string',
						'description' => 'Optional. For a summary with one piece per group, the group whose summary you want, as list_summaries or get_summary shows it.'
					],
					'limitStart' => [
						'type' => 'integer',
						'description' => 'Optional. When reading many pieces, the piece to start from. Default 0.'
					],
					'limitSize' => [
						'type' => 'integer',
						'description' => 'Optional. When reading many pieces, how many to return. Default 20, at most 100.'
					]
				],
				'required' => ['summary_id']
			]
		];

		if (!$this->isUserAWebmaster()) {
			return;
		}

		$definitionProperties = [
			'name' => [
				'type' => 'string',
				'description' => 'A name that says what the summary is, ie: "Artifacts overview" or "Each donor at a glance".'
			],
			'form_id' => [
				'type' => 'integer',
				'description' => 'The form the summary is about. Use list_forms to find form ids.'
			],
			'include_connected_forms' => [
				'type' => 'boolean',
				'description' => 'Optional. True to include the entries connected to each entry in the primary relationship (the same data get_entries_from_form returns with relationship_id -1). Default false.'
			],
			'only_entries_where' => [
				'type' => 'array',
				'description' => 'Optional. Only cover the entries that match this filter. Same format as the filter in get_entries_from_form: a list of conditions, all of which must be true, each {"element": handle, "operator": "=", "value": "..."}, and {"any": [...]} or {"all": [...]} groups. Leave out to cover every entry.',
				'items' => ['type' => 'object']
			],
			'level' => [
				'type' => 'string',
				'enum' => ['each_entry', 'per_group', 'whole_scope'],
				'description' => 'One piece of text for each entry, one for each group of entries (see group_by), or one for all of them. Default whole_scope. Per-entry summaries are the most reliable, and the others can be built from them (see built_from_summary_id), which is the way to summarize a large form.'
			],
			'group_by' => [
				'type' => 'string',
				'description' => 'For per_group summaries: the handle of the element in the summary\'s form whose value groups the entries. One piece is written for each value.'
			],
			'built_from_summary_id' => [
				'type' => 'integer',
				'description' => 'Optional. For per_group and whole_scope summaries: an each_entry summary of the same form, whose texts this summary is written from, instead of from the entries themselves. Use this when there are too many entries, or too much in each, to send all of it to the AI model at once.'
			],
			'instructions' => [
				'type' => 'string',
				'description' => 'What the summary should cover, ie: "What the collection holds, notable items, condition concerns, and gaps in the records."'
			],
			'written_by' => [
				'type' => 'string',
				'enum' => ['server_ai', 'client'],
				'description' => '"server_ai" (the default): written by this site\'s AI model, which needs "Enable AI in rules and summaries" turned on in Settings, AI. "client": written by AI assistants like you, with write_summary. The server never rewrites a client-written summary; the next assistant to read it is told whether it is still current.'
			],
			'keep_current' => [
				'type' => 'string',
				'enum' => ['on_change', 'scheduled', 'manual'],
				'description' => 'For server_ai summaries. "on_change" (the default): rewrite the pieces that are out of date once the data has not changed for wait_minutes. "scheduled": every refresh_every_minutes. "manual": only when refresh_summary is used.'
			],
			'wait_minutes' => [
				'type' => 'integer',
				'description' => 'Optional. For on_change: how long the data must be unchanged before the summary is refreshed, so a burst of changes causes one refresh. Default 10.'
			],
			'refresh_every_minutes' => [
				'type' => 'integer',
				'description' => 'Optional. For scheduled: how often. Default 1440 (once a day).'
			],
			'readable_by_people_who_can_see_everything' => [
				'type' => 'boolean',
				'description' => 'Optional. True (the default) lets anyone who can see every entry, and every element, that the summary covers read it. Webmasters can always read it.'
			],
			'readable_by_groups' => [
				'type' => 'array',
				'items' => ['type' => 'integer'],
				'description' => 'Optional. Group ids whose members can read the summary even though they cannot see everything it covers. A summary blends entries together, so it can tell people things about entries they cannot see; only share it this way when that is intended. Use list_groups to find group ids.'
			],
			'max_length' => [
				'type' => 'integer',
				'description' => 'Optional. The longest each piece of text should be, in characters. Default 2000.'
			],
			'active' => [
				'type' => 'boolean',
				'description' => 'Optional. False to stop keeping the summary up to date, without deleting it. Default true.'
			]
		];

		$this->tools['create_summary'] = [
			'name' => 'create_summary',
			'description' => 'Set up a summary of the data in a form, for AI assistants to read with list_summaries and get_summary. The summary reads the data with your permissions, so it can cover anything you can see. A server_ai summary is written straight away (in the background), and kept up to date as set by keep_current. A client summary is written by you, or another AI assistant, with write_summary.

Choose the level carefully. For a form with more than a few dozen entries, or long entries, create an each_entry summary first, then a whole_scope or per_group summary built from it (built_from_summary_id).',
			'inputSchema' => [
				'type' => 'object',
				'properties' => $definitionProperties,
				'required' => ['name', 'form_id']
			]
		];

		$updateProperties = ['summary_id' => [
			'type' => 'integer',
			'description' => 'Required. The summary to change.'
		]] + $definitionProperties;
		unset($updateProperties['form_id']);
		$this->tools['update_summary'] = [
			'name' => 'update_summary',
			'description' => 'Change how a summary is set up. Only the settings you give are changed. If you change what it covers or the instructions, its text is not current any more: a server_ai summary is rewritten (in the background), and a client summary needs write_summary. The form a summary is about cannot be changed; create a new summary instead.',
			'inputSchema' => [
				'type' => 'object',
				'properties' => $updateProperties,
				'required' => ['summary_id']
			]
		];

		$this->tools['delete_summary'] = [
			'name' => 'delete_summary',
			'description' => 'Delete a summary, all of its text, and any refresh of it that is waiting.',
			'inputSchema' => [
				'type' => 'object',
				'properties' => [
					'summary_id' => [
						'type' => 'integer',
						'description' => 'Required. The summary to delete.'
					]
				],
				'required' => ['summary_id']
			]
		];

		$this->tools['refresh_summary'] = [
			'name' => 'refresh_summary',
			'description' => 'Have this site\'s AI model rewrite the pieces of a server_ai summary that are out of date (or all of them, with force). By default the refresh happens in the background and this returns straight away; use get_summary later to read the result. With wait true, it waits and reports what was written, which takes a while for anything but a single piece.',
			'inputSchema' => [
				'type' => 'object',
				'properties' => [
					'summary_id' => [
						'type' => 'integer',
						'description' => 'Required. The summary to refresh.'
					],
					'entry_id' => [
						'type' => 'integer',
						'description' => 'Optional. For a summary with one piece per entry, only this entry\'s piece.'
					],
					'group_value' => [
						'type' => 'string',
						'description' => 'Optional. For a summary with one piece per group, only this group\'s piece.'
					],
					'force' => [
						'type' => 'boolean',
						'description' => 'Optional. True to rewrite pieces even if they are current, ie: after changing the instructions. Default false.'
					],
					'wait' => [
						'type' => 'boolean',
						'description' => 'Optional. True to wait for the refresh to finish. Default false.'
					]
				],
				'required' => ['summary_id']
			]
		];

		$this->tools['write_summary'] = [
			'name' => 'write_summary',
			'description' => 'Write the text of a piece of a client summary (one with written_by "client"). Do this after reading the data the piece covers: call get_summary first and keep its server_time, read the data (verify_with gives you the get_entries_from_form arguments), write the text, then call this with the text and that server_time as as_of. The server records what the text covers, and works out later whether it is still current, so a change made while you were working is not missed. The previous text is kept as an earlier version.

Base the text only on the data. Follow the summary\'s instructions and max_length, which get_summary shows you.',
			'inputSchema' => [
				'type' => 'object',
				'properties' => [
					'summary_id' => [
						'type' => 'integer',
						'description' => 'Required. The summary to write.'
					],
					'entry_id' => [
						'type' => 'integer',
						'description' => 'For a summary with one piece per entry: the entry the text is about.'
					],
					'group_value' => [
						'type' => 'string',
						'description' => 'For a summary with one piece per group: the group the text is about.'
					],
					'text' => [
						'type' => 'string',
						'description' => 'Required. The summary.'
					],
					'as_of' => [
						'type' => 'string',
						'description' => 'Strongly recommended. The server_time from the get_summary call you made before reading the data, in the same format. Without it, the time now is used, and any change made while you were working will not show up as making the summary out of date.'
					]
				],
				'required' => ['summary_id', 'text']
			]
		];
	}

	/**
	 * Get a summary definition that the current user may read
	 * @param mixed $summaryId
	 * @return array The definition
	 * @throws FormulizeMCPException if there is no such summary, or the user can't read it
	 */
	private function summaryForUser($summaryId) {
		$handler = xoops_getmodulehandler('summaries', 'formulize');
		$summary = intval($summaryId) ? $handler->get(intval($summaryId)) : false;
		if (!$summary OR !formulize_summaryUserCanRead($summary, $this->authenticatedUid)) {
			throw new FormulizeMCPException(
				"There is no summary with id ".intval($summaryId)." that you can read.",
				'invalid_data',
				context: ['hint' => 'Use list_summaries to see the summaries you can read.']
			);
		}
		return $summary;
	}

	/**
	 * The piece key a request refers to: the entry id for each_entry summaries, the group value for per_group, 0 for whole_scope
	 * @param array $summary
	 * @param array $arguments
	 * @param bool $required True if a piece must be named for each_entry and per_group summaries
	 * @return mixed The key, or null if none was given
	 * @throws FormulizeMCPException
	 */
	private function summaryPieceKey($summary, $arguments, $required = false) {
		if ($summary['level'] == 'each_entry') {
			if (!empty($arguments['entry_id'])) {
				return intval($arguments['entry_id']);
			}
			if ($required) {
				throw new FormulizeMCPException('This summary has one piece for each entry, so entry_id is required.', 'invalid_data');
			}
			return null;
		}
		if ($summary['level'] == 'per_group') {
			if (isset($arguments['group_value']) AND strval($arguments['group_value']) !== '') {
				return strval($arguments['group_value']);
			}
			if ($required) {
				throw new FormulizeMCPException('This summary has one piece for each value of '.$summary['group_by'].', so group_value is required.', 'invalid_data');
			}
			return null;
		}
		return 0;
	}

	/**
	 * The definition of a summary as the tools present it
	 * @param array $summary
	 * @return array
	 */
	private function summaryDescription($summary) {
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($summary['form_id']);
		$description = [
			'summary_id' => $summary['summary_id'],
			'name' => $summary['name'],
			'form_id' => $summary['form_id'],
			'form_title' => $formObject ? trans($formObject->getVar('title')) : '',
			'covers' => formulize_summaryDescribeScope($summary),
			'level' => $summary['level'],
			'written_by' => $summary['written_by'],
			'active' => (bool) $summary['active'],
		];
		if ($summary['level'] == 'per_group') {
			$description['group_by'] = $summary['group_by'];
		}
		if ($summary['built_from_summary_id']) {
			$description['built_from_summary_id'] = $summary['built_from_summary_id'];
		}
		if ($summary['written_by'] == 'server_ai') {
			$description['keep_current'] = $summary['keep_current'];
		}
		return $description;
	}

	/**
	 * The full settings of a summary, for webmasters
	 * @param array $summary
	 * @return array
	 */
	private function summarySettings($summary) {
		return [
			'include_connected_forms' => (bool) $summary['include_connected_forms'],
			'only_entries_where' => $summary['only_entries_where'],
			'instructions' => $summary['instructions'],
			'wait_minutes' => $summary['wait_minutes'],
			'refresh_every_minutes' => $summary['refresh_every_minutes'],
			'readable_by_people_who_can_see_everything' => (bool) $summary['all_scope_viewers'],
			'readable_by_groups' => $summary['readable_by_groups'],
			'max_length' => $summary['max_length'],
			'set_up_by_user_id' => $summary['created_uid'],
		];
	}

	/**
	 * Read a summary's state, turning problems reading the data into errors the model can act on
	 * @param array $summary
	 * @param mixed $onlyKey
	 * @return array From formulize_summaryState
	 * @throws FormulizeMCPException
	 */
	private function summaryState($summary, $onlyKey = null) {
		try {
			return formulize_summaryState($summary, $onlyKey);
		} catch (FormulizeApiException $e) {
			throw new FormulizeMCPException(
				'Could not read the data this summary covers: '.$e->getMessage(),
				'invalid_data',
				context: $e->getContext()
			);
		}
	}

	/**
	 * The current time on the database server, which as_of and the times entries were changed are measured in
	 * @return string
	 */
	private function summaryServerTime() {
		$res = $this->db->query("SELECT NOW()");
		$row = $res ? $this->db->fetchRow($res) : [date('Y-m-d H:i:s')];
		return $row[0];
	}

	/**
	 * List the summaries the user can read
	 * @param array $arguments 'form_id', 'application_id' (both optional)
	 * @return array
	 */
	private function list_summaries($arguments) {
		$handler = xoops_getmodulehandler('summaries', 'formulize');
		$fids = null;
		if (!empty($arguments['application_id'])) {
			$fids = [];
			$sql = "SELECT fid FROM ".$this->db->prefix('formulize_application_form_link')." WHERE appid = ".intval($arguments['application_id']);
			if ($res = $this->db->query($sql)) {
				while ($row = $this->db->fetchRow($res)) {
					$fids[] = intval($row[0]);
				}
			}
		}
		$summaries = [];
		foreach ($handler->getSummaries(intval($arguments['form_id'] ?? 0)) as $summary) {
			if ($fids !== null AND !in_array($summary['form_id'], $fids)) {
				continue;
			}
			if (!formulize_summaryUserCanRead($summary, $this->authenticatedUid)) {
				continue;
			}
			$item = $this->summaryDescription($summary);
			try {
				$state = formulize_summaryState($summary);
				if ($summary['level'] == 'whole_scope') {
					$piece = reset($state['pieces']);
					$item['freshness'] = $piece ? $piece['freshness'] : ['status' => 'not_written'];
					$item['as_of'] = ($piece AND $piece['text']) ? $piece['text']['as_of'] : null;
					$item['length'] = ($piece AND $piece['text']) ? strlen($piece['text']['summary_text']) : 0;
				} else {
					$item['pieces'] = $state['counts'];
					if ($summary['level'] == 'per_group') {
						$item['groups'] = array_values(array_map('strval', array_keys($state['pieces'])));
					}
				}
			} catch (FormulizeApiException $e) {
				$item['freshness'] = ['status' => 'unknown', 'problem' => $e->getMessage()];
			}
			$summaries[] = $item;
		}
		$response = [
			'summary_count' => count($summaries),
			'summaries' => $summaries,
		];
		if (!$summaries) {
			$response['note'] = 'There are no summaries that you can read'.((!empty($arguments['form_id']) OR !empty($arguments['application_id'])) ? ' for that form or application' : '').'.'
				.($this->isUserAWebmaster() ? ' You can set one up with create_summary.' : '');
		}
		return $response;
	}

	/**
	 * Read a summary
	 * @param array $arguments 'summary_id', and optionally 'entry_id' or 'group_value', 'limitStart', 'limitSize'
	 * @return array
	 */
	private function get_summary($arguments) {
		$summary = $this->summaryForUser($arguments['summary_id'] ?? 0);
		$serverTime = $this->summaryServerTime();
		$key = $this->summaryPieceKey($summary, $arguments);
		$state = $this->summaryState($summary, $key);
		$pieces = $state['pieces'];
		$response = $this->summaryDescription($summary) + [
			'instructions' => $summary['instructions'],
			'max_length' => $summary['max_length'],
			'server_time' => $serverTime,
		];
		if ($this->isUserAWebmaster()) {
			$response['settings'] = $this->summarySettings($summary);
		}
		if ($key === null) {
			// several pieces: page through them
			$limitStart = max(0, intval($arguments['limitStart'] ?? 0));
			$limitSize = min(100, max(1, intval($arguments['limitSize'] ?? 20)));
			$response['pieces_in_total'] = count($pieces);
			$response['piece_counts'] = $state['counts'];
			$pieces = array_slice($pieces, $limitStart, $limitSize, true);
			if ($limitStart + count($pieces) < $response['pieces_in_total']) {
				$response['note'] = 'These are pieces '.($limitStart + 1).' to '.($limitStart + count($pieces)).' of '.$response['pieces_in_total'].'. To get the next ones, call again with limitStart '.($limitStart + count($pieces)).'.';
			}
		}
		$response['pieces'] = [];
		foreach ($pieces as $pieceState) {
			$response['pieces'][] = $this->summaryPieceForResponse($summary, $pieceState);
		}
		if (!empty($state['scope']['truncated'])) {
			$response['warning'] = 'This summary covers more entries than one summary can, so only the first '.FORMULIZE_SUMMARY_MAX_ENTRIES.' are included. Narrow it with only_entries_where, or split it up.';
		}
		return $response;
	}

	/**
	 * One piece of a summary, as get_summary returns it
	 * @param array $summary
	 * @param array $pieceState From formulize_summaryState
	 * @return array
	 */
	private function summaryPieceForResponse($summary, $pieceState) {
		$piece = [];
		if ($summary['level'] == 'each_entry') {
			$piece['entry_id'] = intval($pieceState['entry_id']);
		} elseif ($summary['level'] == 'per_group') {
			$piece['group_value'] = strval($pieceState['group_value']);
		}
		$text = $pieceState['text'];
		$piece['text'] = $text ? $text['summary_text'] : null;
		$piece['as_of'] = $text ? $text['as_of'] : null;
		$piece['written_by'] = $text ? $text['written_by'] : null;
		$piece['freshness'] = $pieceState['freshness'];
		if ($text) {
			$covers = ['forms' => []];
			foreach ((array) ($text['covers']['forms'] ?? []) as $fid => $form) {
				$covers['forms'][] = ['form_id' => intval($fid), 'form_handle' => $form['form_handle'] ?? '', 'entry_count' => intval($form['entry_count'] ?? 0)];
			}
			if (!empty($text['covers']['truncated'])) {
				$covers['truncated'] = true;
			}
			if (!empty($text['covers']['built_from'])) {
				$covers['built_from_summary_id'] = intval($text['covers']['built_from']['summary_id']);
			}
			$piece['covers'] = $covers;
		}
		if ($pieceState['freshness']['status'] != 'no_longer_applies') {
			$piece['verify_with'] = formulize_summaryVerifyWith($summary, $pieceState);
		}
		return $piece;
	}

	/**
	 * Check the settings for a summary and apply them to a definition
	 * @param array $summary The definition to change
	 * @param array $arguments The settings given
	 * @return array The changed definition
	 * @throws FormulizeMCPException
	 */
	private function applySummaryArguments($summary, $arguments) {
		$simple = ['name' => 'strval', 'instructions' => 'strval', 'group_by' => 'strval', 'wait_minutes' => 'intval', 'refresh_every_minutes' => 'intval', 'max_length' => 'intval', 'built_from_summary_id' => 'intval'];
		foreach ($simple as $key => $cast) {
			if (array_key_exists($key, $arguments)) {
				$summary[$key] = $cast($arguments[$key] ?? '');
			}
		}
		foreach (['include_connected_forms' => 'include_connected_forms', 'readable_by_people_who_can_see_everything' => 'all_scope_viewers', 'active' => 'active'] as $key => $field) {
			if (array_key_exists($key, $arguments)) {
				$summary[$field] = $arguments[$key] ? 1 : 0;
			}
		}
		foreach (['level' => ['each_entry', 'per_group', 'whole_scope'], 'written_by' => ['server_ai', 'client'], 'keep_current' => ['on_change', 'scheduled', 'manual']] as $key => $valid) {
			if (array_key_exists($key, $arguments)) {
				if (!in_array($arguments[$key], $valid, true)) {
					throw new FormulizeMCPException("$key must be one of: ".implode(', ', $valid), 'invalid_data');
				}
				$summary[$key] = $arguments[$key];
			}
		}
		if (array_key_exists('only_entries_where', $arguments)) {
			$filter = $arguments['only_entries_where'];
			if (is_string($filter) AND trim($filter) !== '') {
				$filter = json_decode($filter, true);
			}
			if ($filter !== null AND $filter !== '' AND !is_array($filter)) {
				throw new FormulizeMCPException('only_entries_where must be a list of conditions, in the same format as the filter in get_entries_from_form.', 'invalid_data');
			}
			$summary['only_entries_where'] = $filter ? array_values($filter) : [];
		}
		if (array_key_exists('readable_by_groups', $arguments)) {
			$groups = array_values(array_filter(array_map('intval', (array) $arguments['readable_by_groups'])));
			$member_handler = xoops_gethandler('member');
			foreach ($groups as $groupId) {
				if (!$member_handler->getGroup($groupId)) {
					throw new FormulizeMCPException("There is no group with the id $groupId.", 'invalid_data', context: ['hint' => 'Use list_groups to find group ids.']);
				}
			}
			$summary['readable_by_groups'] = $groups;
		}

		if (trim($summary['name']) === '') {
			throw new FormulizeMCPException('A summary needs a name.', 'invalid_data');
		}
		if ($summary['level'] == 'per_group') {
			$element_handler = xoops_getmodulehandler('elements', 'formulize');
			$elementObject = $summary['group_by'] ? $element_handler->get($summary['group_by']) : false;
			if (!$elementObject OR $elementObject->getVar('id_form') != $summary['form_id']) {
				throw new FormulizeMCPException('A per_group summary needs group_by: the handle of an element in form '.$summary['form_id'].'.', 'invalid_data', context: ['hint' => 'Use get_form_details to find element handles.']);
			}
		} else {
			$summary['group_by'] = '';
		}
		if ($summary['built_from_summary_id']) {
			$handler = xoops_getmodulehandler('summaries', 'formulize');
			$source = $handler->get($summary['built_from_summary_id']);
			if ($summary['level'] == 'each_entry') {
				throw new FormulizeMCPException('An each_entry summary is written from its entries, so it cannot be built from another summary.', 'invalid_data');
			}
			if (!$source OR $source['level'] != 'each_entry' OR $source['form_id'] != $summary['form_id'] OR $source['summary_id'] == $summary['summary_id']) {
				throw new FormulizeMCPException('built_from_summary_id must be an each_entry summary of the same form.', 'invalid_data');
			}
		}
		return $summary;
	}

	/**
	 * Check that a summary can read its data, before it is saved
	 * @param array $summary
	 * @return array From formulize_summaryReadScope (light)
	 * @throws FormulizeMCPException
	 */
	private function checkSummaryScope($summary) {
		try {
			return formulize_summaryReadScope($summary, ['light' => true]);
		} catch (FormulizeApiException $e) {
			throw new FormulizeMCPException(
				'The summary cannot read the data it would cover: '.$e->getMessage(),
				'invalid_data',
				context: $e->getContext()
			);
		}
	}

	/**
	 * What a summary will do next, after it is created or changed
	 * @param array $summary
	 * @return string
	 */
	private function summaryNextStep($summary) {
		if (!$summary['active']) {
			return 'The summary is turned off, so it will not be written or kept up to date until it is turned on.';
		}
		if ($summary['written_by'] == 'client') {
			return 'Write its text with write_summary. Use get_summary to see which pieces need writing.';
		}
		include_once XOOPS_ROOT_PATH.'/modules/formulize/include/aiadminconfig.php';
		if (!formulize_aiInRulesEnabled()) {
			return 'This site\'s AI model will not write it until "Enable AI in rules and summaries" is turned on in Settings, AI. Until then, you could make it a client summary and write it yourself.';
		}
		return 'This site\'s AI model is writing it in the background. Use get_summary in a minute or two to read it.';
	}

	/**
	 * Set up a summary
	 * @param array $arguments The definition
	 * @return array
	 */
	private function create_summary($arguments) {
		if (!$this->isUserAWebmaster()) {
			throw new FormulizeMCPException('Permission denied: Only webmasters can set up summaries.', 'authentication_error');
		}
		$formObject = $this->assertFormExists($arguments['form_id'] ?? 0);
		if ($formObject->isSystemManagedForm()) {
			throw new FormulizeMCPException('Summaries cannot be made of system forms.', 'invalid_data');
		}
		formulize_summariesEnsureForm();
		$handler = xoops_getmodulehandler('summaries', 'formulize');
		$summary = $handler->create($formObject->getVar('id_form'));
		$summary['created_uid'] = $this->authenticatedUid;
		$summary = $this->applySummaryArguments($summary, $arguments);
		$scope = $this->checkSummaryScope($summary);
		if (!$summaryId = $handler->insert($summary)) {
			throw new FormulizeMCPException('Could not save the summary: '.$this->db->error(), 'database_error');
		}
		$summary = $handler->get($summaryId);
		if ($summary['active'] AND $summary['written_by'] == 'server_ai') {
			formulize_summaryQueueRefresh($summary, 0);
		}
		return $this->summaryDescription($summary) + [
			'settings' => $this->summarySettings($summary),
			'pieces_to_write' => count($scope['pieces']),
			'next' => $this->summaryNextStep($summary),
		];
	}

	/**
	 * Change how a summary is set up
	 * @param array $arguments 'summary_id' and the settings to change
	 * @return array
	 */
	private function update_summary($arguments) {
		if (!$this->isUserAWebmaster()) {
			throw new FormulizeMCPException('Permission denied: Only webmasters can change summaries.', 'authentication_error');
		}
		$summary = $this->summaryForUser($arguments['summary_id'] ?? 0);
		$before = $summary;
		unset($arguments['form_id']);
		$summary = $this->applySummaryArguments($summary, $arguments);
		$this->checkSummaryScope($summary);
		if (!$this->getSummariesHandler()->insert($summary)) {
			throw new FormulizeMCPException('Could not save the summary: '.$this->db->error(), 'database_error');
		}
		$summary = $this->getSummariesHandler()->get($summary['summary_id']);
		$contentChanged = false;
		foreach (['include_connected_forms', 'only_entries_where', 'level', 'group_by', 'built_from_summary_id', 'instructions', 'max_length'] as $field) {
			if ($before[$field] != $summary[$field]) {
				$contentChanged = true;
			}
		}
		$response = $this->summaryDescription($summary) + ['settings' => $this->summarySettings($summary)];
		if ($contentChanged AND $summary['active'] AND $summary['written_by'] == 'server_ai') {
			// what it covers or how it is written has changed, so rewrite every piece
			formulize_summaryQueueRefresh($summary, 0, $this->summaryServerTime());
			$response['next'] = $this->summaryNextStep($summary);
		} elseif ($contentChanged) {
			$response['next'] = $summary['written_by'] == 'client' ? 'What the summary covers or how it is written has changed, so its text should be rewritten with write_summary.' : $this->summaryNextStep($summary);
		}
		return $response;
	}

	/**
	 * @return formulizeSummariesHandler
	 */
	private function getSummariesHandler() {
		return xoops_getmodulehandler('summaries', 'formulize');
	}

	/**
	 * Delete a summary
	 * @param array $arguments 'summary_id'
	 * @return array
	 */
	private function delete_summary($arguments) {
		if (!$this->isUserAWebmaster()) {
			throw new FormulizeMCPException('Permission denied: Only webmasters can delete summaries.', 'authentication_error');
		}
		$summary = $this->summaryForUser($arguments['summary_id'] ?? 0);
		foreach ($this->getSummariesHandler()->getSummaries() as $other) {
			if ($other['built_from_summary_id'] == $summary['summary_id']) {
				throw new FormulizeMCPException('Summary '.$other['summary_id'].' ("'.$other['name'].'") is built from this one. Delete it, or change what it is built from, first.', 'invalid_data');
			}
		}
		if (!$this->getSummariesHandler()->delete($summary['summary_id'])) {
			throw new FormulizeMCPException('Could not delete the summary: '.$this->db->error(), 'database_error');
		}
		return ['deleted' => true, 'summary_id' => $summary['summary_id'], 'name' => $summary['name']];
	}

	/**
	 * Rewrite a server_ai summary now
	 * @param array $arguments 'summary_id', and optionally 'entry_id' or 'group_value', 'force', 'wait'
	 * @return array
	 */
	private function refresh_summary($arguments) {
		if (!$this->isUserAWebmaster()) {
			throw new FormulizeMCPException('Permission denied: Only webmasters can refresh summaries.', 'authentication_error');
		}
		$summary = $this->summaryForUser($arguments['summary_id'] ?? 0);
		if ($summary['written_by'] != 'server_ai') {
			throw new FormulizeMCPException('This summary is written by AI clients, not by this site\'s AI model. Write it with write_summary.', 'invalid_data');
		}
		$key = $this->summaryPieceKey($summary, $arguments);
		// forcing rewrites the pieces written before now, so the ones written in this refresh aren't written again if it carries on in the background
		$force = !empty($arguments['force']) ? $this->summaryServerTime() : false;
		if (empty($arguments['wait'])) {
			$jobId = formulize_summaryQueueRefresh($summary, 0, $force ?: '');
			return ['queued' => (bool) $jobId, 'summary_id' => $summary['summary_id'], 'next' => 'The refresh is running in the background. Use get_summary in a minute or two to read the result.'];
		}
		try {
			$report = formulize_summaryRefresh($summary, $key, $force);
		} catch (FormulizeApiException $e) {
			throw new FormulizeMCPException('Could not read the data this summary covers: '.$e->getMessage(), 'invalid_data', context: $e->getContext());
		}
		$response = [
			'summary_id' => $summary['summary_id'],
			'pieces_written' => count($report['written']),
			'pieces_retired' => count($report['retired']),
			'pieces_still_to_write' => $report['remaining'],
		];
		if ($report['errors']) {
			$response['errors'] = $report['errors'];
		}
		if ($report['remaining']) {
			formulize_summaryQueueRefresh($summary, 0, $force ?: '');
			$response['next'] = 'The rest are being written in the background.';
		}
		return $response;
	}

	/**
	 * Write the text of a piece of a client summary
	 * @param array $arguments 'summary_id', 'text', 'as_of', and 'entry_id' or 'group_value'
	 * @return array
	 */
	private function write_summary($arguments) {
		if (!$this->isUserAWebmaster()) {
			throw new FormulizeMCPException('Permission denied: Only webmasters can write summaries.', 'authentication_error');
		}
		$summary = $this->summaryForUser($arguments['summary_id'] ?? 0);
		if ($summary['written_by'] != 'client') {
			throw new FormulizeMCPException('This summary is written by this site\'s AI model, not by AI clients. To have it rewritten, use refresh_summary, or change it to a client summary with update_summary.', 'invalid_data');
		}
		$text = trim(strval($arguments['text'] ?? ''));
		if ($text === '') {
			throw new FormulizeMCPException('text is required.', 'invalid_data');
		}
		$key = $this->summaryPieceKey($summary, $arguments, true);
		$serverTime = $this->summaryServerTime();
		$asOf = trim(strval($arguments['as_of'] ?? ''));
		if ($asOf !== '') {
			$parsed = DateTime::createFromFormat('Y-m-d H:i:s', $asOf);
			if (!$parsed OR $parsed->format('Y-m-d H:i:s') !== $asOf) {
				throw new FormulizeMCPException('as_of must be in the format of server_time from get_summary, ie: '.$serverTime, 'invalid_data');
			}
			if ($asOf > $serverTime) {
				throw new FormulizeMCPException('as_of cannot be in the future. Use the server_time from get_summary.', 'invalid_data');
			}
		} else {
			$asOf = $serverTime;
		}
		try {
			$scope = formulize_summaryReadScope($summary, $summary['level'] == 'each_entry' ? ['entry_id' => intval($key)] : []);
		} catch (FormulizeApiException $e) {
			throw new FormulizeMCPException('Could not read the data this summary covers: '.$e->getMessage(), 'invalid_data', context: $e->getContext());
		}
		$lookup = $summary['level'] == 'per_group' ? strval($key) : intval($key);
		if (!isset($scope['pieces'][$lookup])) {
			$what = $summary['level'] == 'each_entry' ? "entry $key" : ($summary['level'] == 'per_group' ? "the group \"$key\"" : 'any entries');
			throw new FormulizeMCPException("This summary does not cover $what.", 'invalid_data', context: ['hint' => 'Use get_summary to see the pieces this summary has.']);
		}
		$piece = $scope['pieces'][$lookup];
		$covers = formulize_summaryCovers($summary, $piece, $scope, formulize_summarySourceTexts($summary, $piece));
		$member_handler = xoops_gethandler('member');
		$user = $member_handler->getUser($this->authenticatedUid);
		$writtenBy = 'client: '.($user ? $user->getVar('uname') : 'user '.$this->authenticatedUid);
		if (!$textId = $this->getSummariesHandler()->writeText($summary, $piece['entry_id'], $piece['group_value'], $text, $asOf, $covers, $writtenBy)) {
			throw new FormulizeMCPException('Could not save the summary text: '.$this->db->error(), 'database_error');
		}
		$state = $this->summaryState($summary, $key);
		$pieceState = reset($state['pieces']);
		$response = [
			'written' => true,
			'summary_id' => $summary['summary_id'],
			'piece' => $pieceState ? $this->summaryPieceForResponse($summary, $pieceState) : null,
		];
		if (strlen($text) > $summary['max_length']) {
			$response['note'] = 'The text is longer than the summary\'s max_length of '.$summary['max_length'].' characters. It was saved, but keep to the limit next time.';
		}
		if ($pieceState AND $pieceState['freshness']['status'] == 'stale') {
			$response['note'] = trim(($response['note'] ?? '').' Entries changed after as_of, so the text is already out of date. Read them again and rewrite it.');
		}
		return $response;
	}
}
