<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                Copyright (c) 2026 Formulize Incorporated                  ##
###############################################################################
##  Released under the GNU General Public License. See license elsewhere in  ##
##  this distribution for details.                                           ##
###############################################################################
/**
 * Shared core for reading entries out of a form.
 *
 * This is the single implementation behind both the Public API read endpoint
 * (modules/formulize/public_api/v1/form.php) and the MCP get_entries_from_form
 * tool (mcp/tools.php). It validates the request, applies permissions, and runs
 * gatherDataset. It lives here rather than in the MCP trait so that the module
 * does not depend on /mcp.
 *
 * The two transports need different output, so reading and rendering are split:
 *
 *   formulize_readEntries()          validate + permissions + gatherDataset,
 *                                    returning the raw nested dataset
 *   formulize_renderEntriesAsRows()  flatten that into readable JSON rows
 *
 * MCP calls only the first, because its callers, and its companion tool
 * prepare_database_values_for_human_readability, expect the raw dataset. The
 * Public API calls both.
 *
 * Errors are reported by throwing FormulizeApiException, which each transport
 * maps to its own error shape.
 */

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}

include_once XOOPS_ROOT_PATH.'/modules/formulize/class/apiexception.php';
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/extract.php';

/**
 * The operators a caller may use in a filter condition. formulize_parseFilter understands
 * more than these, but these are the ones that behave predictably across element types.
 * @return array
 */
function formulize_apiFilterOperators() {
	return array('=', '!=', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE');
}

/**
 * Read entries from a form, honouring the permissions of the given user.
 *
 * @param int|string formIdOrHandle The form to read. The forms handler accepts either.
 * @param array options Request options. 'fields' is required, everything else optional:
 *        - fields (array) element handles and/or metadata field names to gather. Required.
 *        - filter (int|string|array) see formulize_apiValidateFilter
 *        - andOr (string) AND or OR, joining the top level filter items. Default AND.
 *        - sortField (string) element handle or metadata field name. Default entry_id.
 *        - sortOrder (string) ASC or DESC. Default ASC.
 *        - limitStart (int) default 0
 *        - limitSize (int|null) default 100, capped at 10000, null for no limit
 *        - relationship (int) 0 for the main form alone, -1 for the primary relationship
 * @param int|object|null user The user to act as: a user object, a user id, or nothing at all for the current $xoopsUser.
 * @return array Keys: fid, formHandle, dataset, fieldsByForm, scope, and the validated
 *         filter/andOr/limitStart/limitSize/sortField/sortOrder/relationship values.
 * @throws FormulizeApiException
 */
function formulize_readEntries($formIdOrHandle, $options = array(), $user = null) {

	$user = formulize_resolveUserObject($user);
	$groups = formulize_userGroups($user);

	// ---- the form -------------------------------------------------------
	// The forms handler resolves either an id or a handle, so there is nothing to look up first.
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	if ($formIdOrHandle === '' or $formIdOrHandle === null) {
		throw new FormulizeApiException('No form was specified', 'form_not_found');
	}
	if (!$formObject = $form_handler->get($formIdOrHandle)) {
		throw new FormulizeApiException('No form exists with the id or handle: '.$formIdOrHandle, 'form_not_found');
	}
	$fid = intval($formObject->getVar('fid'));
	$formHandle = $formObject->getVar('form_handle');

	// ---- may this user see the form at all? -----------------------------
	// gatherDataset alone would not refuse: buildScope quietly downgrades to "mine" and
	// hands back an empty dataset. That is not good enough here, because anonymous callers
	// are supported and deserve an explicit answer.
	$gperm_handler = xoops_gethandler('groupperm');
	if (!$gperm_handler->checkRight('view_form', $fid, $groups, getFormulizeModId())) {
		throw new FormulizeApiException('You do not have permission to view this form', 'permission_denied');
	}

	// ---- relationship ---------------------------------------------------
	$relationship = intval($options['relationship'] ?? 0);
	// The validator gives back the relationship object, or false when this form is not part
	// of the requested relationship. 0 means "no relationship" and needs no lookup.
	$validRelationship = $relationship !== 0 ? formulize_apiValidateRelationshipId($relationship, $fid) : true;
	if (!$validRelationship) {
		if ($relationship > 0) {
			throw new FormulizeApiException(
				'This form is not part of relationship '.$relationship,
				'invalid_data',
				array('valid_relationship_ids_for_form' => formulize_apiGetValidRelationshipIds($fid))
			);
		}
		// The primary relationship was requested but this form is in no relationship at all,
		// so fall back to the main form rather than failing.
		$relationship = 0;
	}

	// ---- fields ---------------------------------------------------------
	$fields = $options['fields'] ?? array();
	if (!is_array($fields)) {
		throw new FormulizeApiException('The fields parameter must be an array of element handles', 'invalid_arguments');
	}
	if (count($fields) == 0) {
		throw new FormulizeApiException('At least one field must be requested in the fields parameter', 'invalid_arguments');
	}
	// Refuse anything this user may not see, before we go anywhere near the data. The same
	// list gates the sort field and the filter below, so a field that cannot be returned
	// cannot be used to order or select entries either.
	$allowedFields = formulize_apiAllowedFieldHandles($fid, $relationship, $user);
	formulize_apiCheckFieldPermissions($fields, $allowedFields);
	$fieldsByForm = formulize_apiValidateElementHandles($fields, $fid);
	if (empty($fieldsByForm)) {
		throw new FormulizeApiException('At least one field must be requested in the fields parameter', 'invalid_arguments');
	}

	// ---- sorting --------------------------------------------------------
	$sortField = $options['sortField'] ?? 'entry_id';
	$sortOrder = ($options['sortOrder'] ?? 'ASC') == 'DESC' ? 'DESC' : 'ASC';
	if (!empty($sortField)) {
		// Sorting by a field the caller cannot see would leak its ordering, so the sort field
		// goes through the same permission gate as the requested fields.
		formulize_apiCheckFieldPermissions(array($sortField), $allowedFields);
	}

	// ---- limits ---------------------------------------------------------
	list($limitStart, $limitSize) = formulize_apiValidateLimitParameters(
		$options['limitStart'] ?? 0,
		array_key_exists('limitSize', $options) ? $options['limitSize'] : 100
	);

	// ---- filter ---------------------------------------------------------
	// Which forms may the filter reference? Everything reachable through the relationship.
	if (is_object($validRelationship)) {
		$relationship_handler = xoops_getmodulehandler('frameworks', 'formulize');
		$linksByForm = $relationship_handler->getLinksGroupedByForm($validRelationship, $fid);
		$form_ids = array();
		foreach ($linksByForm as $links) {
			foreach ($links as $thisLink) {
				if (!in_array($thisLink['form1'], $form_ids)) {
					$form_ids[] = $thisLink['form1'];
				}
				if (!in_array($thisLink['form2'], $form_ids)) {
					$form_ids[] = $thisLink['form2'];
				}
			}
		}
	} else {
		$form_ids = array($fid);
	}
	$andOr = strtoupper($options['andOr'] ?? 'AND') == 'OR' ? 'OR' : 'AND';
	// An array filter is a list of expressions, each carrying the boolean that goes between its
	// own terms. formulize_parseFilter puts $andOr between the expressions themselves, so it is
	// still the caller's operator that joins the top level items, exactly as for a flat string.
	$filter = formulize_apiValidateFilter($options['filter'] ?? '', $form_ids, $andOr, $allowedFields, $fid);

	// ---- scope and query ------------------------------------------------
	$scope = buildScope('all', $user, $fid);
	$actualScope = $scope[0];

	// bypassCache, because this dataset will not be asked for again. A request reads once and
	// ends, so keeping the result in formulize_cachedGetDataResults for the rest of the request
	// buys nothing and holds every entry in memory until the request finishes, along with the
	// entry-to-cache-key index that is built alongside it. At the limits this endpoint allows,
	// that is the difference between holding one dataset and being unable to let go of it.
	$dataset = gatherDataset(
		$fid,
		$fieldsByForm,
		$filter,
		$andOr,
		$actualScope,
		$limitStart,
		$limitSize,
		$sortField,
		$sortOrder,
		$relationship,
		bypassCache: true
	);

	return array(
		'fid' => $fid,
		'formHandle' => $formHandle,
		'dataset' => $dataset,
		'fieldsByForm' => $fieldsByForm,
		'scope' => $actualScope,
		'filter' => $filter,
		'andOr' => $andOr,
		'limitStart' => $limitStart,
		'limitSize' => $limitSize,
		'sortField' => $sortField,
		'sortOrder' => $sortOrder,
		'relationship' => $relationship,
	);
}

/**
 * Flatten the nested dataset from formulize_readEntries into readable JSON rows.
 *
 * Main form fields sit at the top level of each row. Fields from connected forms are
 * grouped per child entry under 'related', keyed by form handle, so a one to many
 * connection survives the trip intact. 'related' is omitted when there is none.
 *
 * A flat row carrying every field from every form would be closer to what getValue does
 * in PHP, but it does not survive as a wire format: getValue returns a scalar for one
 * value and an array for several, its arrays are not index aligned across fields once a
 * multi-select is involved, and blank values drop out entirely, shifting the rest. That
 * is harmless in PHP, where you ask for one field at a time and never line them up.
 * Grouping per child entry keeps every field a predictable shape.
 *
 * The dataset is consumed as it is read. Each item is released once its row has been built,
 * so the two representations of an entry are never both in memory for the whole set, only for
 * the one entry being worked on. That is why $result is taken by reference: the rows are the
 * output and the dataset is the raw material, and at the limits this endpoint allows, holding
 * both in full is the largest avoidable cost in the request. A caller that still needs the
 * dataset afterwards should render from a copy.
 *
 * @param array result The return value of formulize_readEntries. Its 'dataset' is emptied.
 * @param bool|array raw true for raw database values throughout, or a list of the field
 *        handles that should be raw while everything else stays readable
 * @return array A list of rows
 */
function formulize_renderEntriesAsRows(&$result, $raw = false) {

	$rawAll = ($raw === true or $raw === 'true' or $raw === 1 or $raw === '1');
	$rawFields = is_array($raw) ? $raw : array();

	$mainFid = $result['fid'];
	$mainFormHandle = $result['formHandle'];
	$fieldsByForm = $result['fieldsByForm'];

	$dataHandler = new formulizeDataHandler(false);
	$metadataFields = $dataHandler->metadataFields;

	// form id => form handle, for the connected forms
	$formHandles = array();
	foreach (array_keys($fieldsByForm) as $thisFid) {
		$formHandles[$thisFid] = ($thisFid == $mainFid) ? $mainFormHandle : getFormHandle($thisFid);
	}

	// Every value here is read exactly once, so there is nothing for prepvalues' cache to be
	// reused for and no reason to let it grow to the size of the whole result. Left alone by an
	// outer caller that had already turned it off, so that whoever set it decides when it ends.
	$releasePreppedValueCacheFlag = false;
	if (!isset($GLOBALS['formulize_doNotCachePreppedValues'])) {
		$GLOBALS['formulize_doNotCachePreppedValues'] = true;
		$releasePreppedValueCacheFlag = true;
	}

	try {

		$rows = array();
		// Taking the items off the front rather than iterating: a foreach would be working on its own
		// copy of the dataset the moment we removed anything from it, which is the opposite of the
		// point. array_key_first is O(1), so this walks the dataset in order just as a foreach would.
		while (($datasetKey = array_key_first($result['dataset'])) !== null) {

			$item = $result['dataset'][$datasetKey];
			unset($result['dataset'][$datasetKey]); // this entry's raw form is finished with once its row is built

			// The dataset has one item per main form entry, so there is exactly one local id here.
			$mainEntryId = null;
			if (isset($item[$mainFormHandle]) and is_array($item[$mainFormHandle])) {
				foreach ($item[$mainFormHandle] as $lid => $ignored) {
					$mainEntryId = $lid;
					break;
				}
			}

			$row = array('entry_id' => is_numeric($mainEntryId) ? intval($mainEntryId) : $mainEntryId);
			if (isset($fieldsByForm[$mainFid])) {
				foreach ($fieldsByForm[$mainFid] as $handle) {
					if ($handle == 'entry_id') {
						continue;
					}
					$row[$handle] = formulize_apiReadFieldValue(
						$item, $mainFormHandle, $mainEntryId, $handle,
						formulize_apiFieldIsRaw($handle, $rawAll, $rawFields), $metadataFields
					);
				}
			}

			// Connected forms, grouped by the child entry each value belongs to.
			$related = array();
			foreach ($fieldsByForm as $thisFid => $handles) {
				if ($thisFid == $mainFid) {
					continue;
				}
				$thisFormHandle = $formHandles[$thisFid];
				if (!isset($item[$thisFormHandle]) or !is_array($item[$thisFormHandle])) {
					continue;
				}
				foreach ($item[$thisFormHandle] as $lid => $ignored) {
					// A main form entry with no connected entry shows up as an empty local id.
					if (!$lid or $lid === 'NULL') {
						continue;
					}
					$child = array('entry_id' => intval($lid));
					foreach ($handles as $handle) {
						if ($handle == 'entry_id') {
							continue;
						}
						$child[$handle] = formulize_apiReadFieldValue(
							$item, $thisFormHandle, $lid, $handle,
							formulize_apiFieldIsRaw($handle, $rawAll, $rawFields), $metadataFields
						);
					}
					$related[$thisFormHandle][] = $child;
				}
			}
			if (count($related)) {
				$row['related'] = $related;
			}

			$rows[] = $row;
		}

	} finally {
		if ($releasePreppedValueCacheFlag) {
			unset($GLOBALS['formulize_doNotCachePreppedValues']);
		}
	}

	return $rows;
}

/**
 * Should this field be returned as the raw database value?
 * @return bool
 */
function formulize_apiFieldIsRaw($handle, $rawAll, $rawFields) {
	return $rawAll ? true : in_array($handle, $rawFields);
}

/**
 * Read one field of one entry out of a dataset item.
 *
 * Ordinary elements go through getValue, which resolves foreign keys, splits multi-value
 * fields into arrays and decodes html entities. The form handle is passed to it, because we
 * already know which form the value is coming from and getValue would otherwise search the
 * item for it, once per field of every entry.
 *
 * Metadata fields cannot go through getValue at all, because the extraction layer writes
 * metadata into the records of the main form only -- it reads the aliased metadata columns of
 * the connected forms to work out which entry each row belongs to, and then skips them. So the
 * form a metadata handle resolves to is always the main form, and asking for it against a
 * connected entry's local id finds nothing. A connected entry's own entry_id survives as the
 * key its record is stored under, which is where the rows built above take it from. Reading
 * metadata straight out of the structure keeps it attached to the entry it belongs to.
 *
 * @param array item One item from the dataset
 * @param string formHandle The handle of the form the value should come from
 * @param int|string localEntryId The entry id within that form
 * @param string handle The field to read
 * @param bool isRaw Whether to skip the readability preparation
 * @param array metadataFields The metadata field names, from formulizeDataHandler
 * @return mixed
 */
function formulize_apiReadFieldValue($item, $formHandle, $localEntryId, $handle, $isRaw, $metadataFields) {
	if (in_array($handle, $metadataFields)) {
		return isset($item[$formHandle][$localEntryId][$handle]) ? $item[$formHandle][$localEntryId][$handle] : null;
	}
	return getValue($item, $handle, null, $localEntryId, $isRaw, $formHandle);
}

/**
 * Every field handle this user is allowed to read in this query.
 *
 * getAllAllowedColHandles is the authority, shared with the list screens and the column
 * picker so that a field means the same thing in the API as it does in the product: the
 * metadata fields, the elements whose ele_display and ele_private settings admit this
 * user, and the user account elements, which hold no column of their own and so have to
 * be added back on top of what getAllColList reports. Forms in the relationship this user
 * cannot see contribute nothing.
 *
 * Worked out once per request, because every part of the request is measured against
 * the same list: the requested fields, the sort field, and every element named in the
 * filter. A field the caller may not read must not be usable in any of those roles,
 * since a filter or a sort reveals the values of a field just as surely as returning it.
 *
 * @param int fid The main form id
 * @param int frid The relationship being queried
 * @param object user The user being acted as
 * @return array Keys are the permitted handles
 */
function formulize_apiAllowedFieldHandles($fid, $frid, $user = null) {
	static $cached = array();
	$cacheKey = intval($fid).'/'.intval($frid).'/'.intval(is_object($user) ? $user->getVar('uid') : 0);
	if (!isset($cached[$cacheKey])) {
		$cached[$cacheKey] = getAllAllowedColHandles($fid, $frid, $user);
	}
	return $cached[$cacheKey];
}

/**
 * Refuse any field handle this user is not allowed to read.
 *
 * @param array fields The handles to check
 * @param array allowedFields From formulize_apiAllowedFieldHandles
 * @return void
 * @throws FormulizeApiException if any handle is not permitted
 */
function formulize_apiCheckFieldPermissions($fields, $allowedFields) {
	foreach ($fields as $handle) {
		if (!is_string($handle)) {
			throw new FormulizeApiException('Field names must be strings', 'invalid_arguments');
		}
		if ($handle === '') {
			continue;
		}
		if (!isset($allowedFields[$handle])) {
			// Deliberately the same message whether the field does not exist or is simply not
			// visible to this user, so the endpoint cannot be used to probe for field names.
			throw new FormulizeApiException('Unknown or unavailable field: '.$handle, 'unknown_element');
		}
	}
}

/**
 * Turn a filter into something gatherDataset understands.
 *
 * The preferred form is a list of condition arrays, each with an element and a value, and
 * optionally an operator which defaults to LIKE:
 *
 *   array('element'=>'amount', 'value'=>'100', 'operator'=>'>')
 *
 * Top level items are joined by $andOr. To use a different operator within part of the
 * expression, wrap conditions in a group: 'any' puts OR between them, 'all' puts AND.
 *
 * Groups cannot contain groups. That is formulize_parseFilter's limit rather than a
 * shortcut here: it consumes exactly two levels, an outer list joined by one operator,
 * with each inner expression carrying its own.
 *
 * A third kind of group, 'none', selects main form entries that have NO connected entry
 * matching all of its conditions. See formulize_apiBuildEmptySetExpression for the rules
 * it has to follow.
 *
 * An integer entry id is also accepted, meaning that one entry of the main form.
 *
 * A filter string in gatherDataset's own format is NOT accepted, even though gatherDataset
 * would take one. getData() treats a string beginning with "SELECT " as a complete query to
 * run as it stands, which is how the export feature reuses a query it built itself, and the
 * terms of a filter string are not measured against $allowedFields either. Neither is
 * anything a caller of this API should be able to reach, so only a list of conditions
 * described here gets through, and a JSON string carrying one is decoded first.
 *
 * @param mixed filter
 * @param array form_ids The forms whose elements the filter may reference
 * @param string andOr The operator joining the top level items
 * @param array allowedFields From formulize_apiAllowedFieldHandles
 * @param int mainFormId The form being read, which a 'none' group may not reference
 * @return mixed A string or array suitable for gatherDataset
 * @throws FormulizeApiException
 */
function formulize_apiValidateFilter($filter, $form_ids, $andOr = 'AND', $allowedFields = array(), $mainFormId = 0) {

	if (is_numeric($filter)) {
		return intval($filter);
	}
	if (empty($filter)) {
		return '';
	}
	if (is_string($filter)) {
		$trimmed = ltrim($filter);
		if (substr($trimmed, 0, 1) !== '[' and substr($trimmed, 0, 1) !== '{') {
			throw new FormulizeApiException(
				'The filter parameter must be an entry id, or a list of conditions, or that list encoded as JSON',
				'invalid_arguments'
			);
		}
		$decoded = json_decode($filter, true);
		if ($decoded === null) {
			throw new FormulizeApiException('Invalid JSON in the filter parameter: '.json_last_error_msg(), 'invalid_arguments');
		}
		$filter = $decoded;
	}
	if (!is_array($filter)) {
		throw new FormulizeApiException('The filter parameter must be an entry id or a list of conditions', 'invalid_arguments');
	}

	$bareTerms = array();
	$expressions = array();

	foreach ($filter as $item) {

		if (!is_array($item)) {
			throw new FormulizeApiException('Each filter item must be a condition or a group', 'invalid_arguments');
		}

		// ---- an empty set group ------------------------------------------
		if (isset($item['none'])) {
			$expressions[] = formulize_apiBuildEmptySetExpression($item['none'], $form_ids, $andOr, $allowedFields, $mainFormId);
			continue;
		}

		// ---- a group -----------------------------------------------------
		if (isset($item['any']) or isset($item['all'])) {
			$groupOperator = isset($item['any']) ? 'OR' : 'AND';
			$groupItems = isset($item['any']) ? $item['any'] : $item['all'];
			if (!is_array($groupItems) or count($groupItems) == 0) {
				throw new FormulizeApiException('A filter group must contain at least one condition', 'invalid_arguments');
			}
			$groupTerms = array();
			foreach ($groupItems as $condition) {
				if (formulize_apiIsFilterGroup($condition)) {
					throw new FormulizeApiException(
						'Filter groups cannot be nested inside other groups. Formulize filters support one level of grouping.',
						'invalid_arguments'
					);
				}
				$groupTerms = array_merge($groupTerms, formulize_apiBuildFilterTerms($condition, $form_ids, $groupOperator, $allowedFields));
			}
			$expressions[] = array($groupOperator, implode('][', $groupTerms));
			continue;
		}

		// ---- a bare condition --------------------------------------------
		list($element, $value, $operator) = formulize_apiReadFilterCondition($item, $form_ids, $allowedFields);
		if ($value === '{BLANK}') {
			// A blank test is two terms with a boolean of its own, so it cannot simply join the
			// other bare terms. It gets an expression to itself, one per test rather than one
			// shared by every blank test that needs the same boolean: two "is blank" conditions
			// joined by AND are (a='' OR a IS NULL) AND (b='' OR b IS NULL), and merging them
			// into a single OR expression would ask for either one instead of both.
			list($blankBoolean, $blankTerms) = formulize_apiBuildBlankTerms($element, $operator);
			$expressions[] = array($blankBoolean, implode('][', $blankTerms));
		} else {
			$bareTerms[] = $element.'/**/'.$value.'/**/'.$operator;
		}
	}

	// Nothing but plain terms: use the flat string form, which is what gatherDataset likes best.
	if (count($expressions) == 0) {
		return implode('][', $bareTerms);
	}

	$returnFilter = array();
	if (count($bareTerms)) {
		$returnFilter[] = array($andOr, implode('][', $bareTerms));
	}
	foreach ($expressions as $expression) {
		$returnFilter[] = $expression;
	}
	return $returnFilter;
}

/**
 * Build the filter terms for one condition inside a group.
 *
 * A blank test expands to two terms joined by a boolean of its own: OR for "is blank" and
 * AND for "is not blank". Inside a group that only gives the right answer when the group's
 * operator already matches, so a mismatch is refused rather than silently producing a
 * condition that can never be true.
 *
 * @return array The terms to add to the group
 * @throws FormulizeApiException
 */
function formulize_apiBuildFilterTerms($condition, $form_ids, $groupOperator, $allowedFields = array()) {
	list($element, $value, $operator) = formulize_apiReadFilterCondition($condition, $form_ids, $allowedFields);
	if ($value === '{BLANK}') {
		list($blankBoolean, $blankTerms) = formulize_apiBuildBlankTerms($element, $operator);
		if ($blankBoolean !== $groupOperator) {
			throw new FormulizeApiException(
				'A blank test on '.$element.' needs '.$blankBoolean.' between its parts, so it cannot go in an '
				.($groupOperator == 'OR' ? 'any' : 'all').' group. Put it at the top level of the filter instead.',
				'invalid_arguments'
			);
		}
		return $blankTerms;
	}
	return array($element.'/**/'.$value.'/**/'.$operator);
}

/**
 * The two terms that make up a blank or not blank test, and the boolean they need between them.
 * @return array array(boolean, array(term, term))
 */
function formulize_apiBuildBlankTerms($element, $operator) {
	if ($operator == '!=' or $operator == 'NOT LIKE') {
		return array('AND', array($element.'/**//**/!=', $element.'/**//**/ IS NOT NULL '));
	}
	return array('OR', array($element.'/**//**/=', $element.'/**//**/ IS NULL '));
}

/**
 * Is this filter item a group of conditions rather than a single condition?
 * @return bool
 */
function formulize_apiIsFilterGroup($item) {
	return is_array($item) and (isset($item['any']) or isset($item['all']) or isset($item['none']));
}

/**
 * Build the expression for a 'none' group: main form entries qualify only when no connected
 * entry matches every condition in the group.
 *
 * formulize_parseFilter turns an expression carrying 'none' in its third slot into a NOT
 * EXISTS against the connected form. It is lenient about what it will not handle, and drops
 * it without a word, which for a NOT EXISTS means the caller gets more entries back than
 * they asked for and has no way to tell. So everything it would drop is refused here instead:
 *
 * - There must be a connected form to quantify over, so a relationship has to be in effect
 *   and no condition may be on the main form. Metadata fields such as creation_uid always
 *   belong to the main form in a filter, so they are refused too.
 * - Every condition must be on the same connected form. Conditions on two forms become two
 *   separate NOT EXISTS clauses, "no A matches x and no B matches y", which is not what a
 *   single group appears to say.
 * - The top level andOr must be AND. The NOT EXISTS clauses are ANDed onto the query
 *   whatever andOr says, so an OR between this group and the rest cannot be honoured.
 * - An "is blank" test needs OR between its two parts, while the group needs AND between
 *   its conditions, and one clause only has one boolean. So an "is blank" test must be the
 *   only condition in its group. "Is not blank" is two ANDed parts and mixes freely.
 *
 * @param mixed conditions The contents of the 'none' key
 * @param array form_ids The forms whose elements the filter may reference
 * @param string andOr The operator joining the top level items
 * @param array allowedFields From formulize_apiAllowedFieldHandles
 * @param int mainFormId The form being read
 * @return array array(boolean, terms, 'none'), one row of an array filter for gatherDataset
 * @throws FormulizeApiException
 */
function formulize_apiBuildEmptySetExpression($conditions, $form_ids, $andOr, $allowedFields, $mainFormId) {

	if (!is_array($conditions) or count($conditions) == 0) {
		throw new FormulizeApiException('A filter group must contain at least one condition', 'invalid_arguments');
	}
	$connectedFormIds = array_diff($form_ids, array($mainFormId));
	if (count($connectedFormIds) == 0) {
		throw new FormulizeApiException(
			'A none group finds entries with no matching connected entry, so it needs a relationship that connects this form to other forms',
			'invalid_arguments'
		);
	}
	if ($andOr == 'OR') {
		throw new FormulizeApiException(
			'A none group cannot be used when andOr is OR. Put the other conditions in an any group instead.',
			'invalid_arguments'
		);
	}

	$terms = array();
	$groupFormId = null;
	$blankTestCount = 0;
	foreach ($conditions as $condition) {
		if (formulize_apiIsFilterGroup($condition)) {
			throw new FormulizeApiException(
				'Filter groups cannot be nested inside other groups. Formulize filters support one level of grouping.',
				'invalid_arguments'
			);
		}
		list($element, $value, $operator, $elementFormId) = formulize_apiReadFilterCondition($condition, $form_ids, $allowedFields);
		if (!$elementFormId) {
			throw new FormulizeApiException(
				'A none group can only use elements from connected forms, and '.$element.' is a metadata field of the main form',
				'invalid_arguments'
			);
		}
		if ($elementFormId == $mainFormId) {
			throw new FormulizeApiException(
				'A none group can only use elements from connected forms, and '.$element.' is on the main form',
				'invalid_arguments'
			);
		}
		if ($groupFormId !== null and $elementFormId != $groupFormId) {
			throw new FormulizeApiException(
				'All the conditions in a none group must be on the same connected form. Use a separate none group for each form.',
				'invalid_arguments'
			);
		}
		$groupFormId = $elementFormId;

		if ($value === '{BLANK}') {
			list($blankBoolean, $blankTerms) = formulize_apiBuildBlankTerms($element, $operator);
			if ($blankBoolean == 'OR') {
				$blankTestCount++;
			}
			$terms = array_merge($terms, $blankTerms);
		} else {
			$terms[] = $element.'/**/'.$value.'/**/'.$operator;
		}
	}

	if ($blankTestCount > 0 and count($conditions) > 1) {
		throw new FormulizeApiException(
			'A blank test must be the only condition in its none group. Use a separate none group for it.',
			'invalid_arguments'
		);
	}

	return array($blankTestCount ? 'OR' : 'AND', implode('][', $terms), 'none');
}

/**
 * Pull element, value and operator out of one condition, and check the element is usable.
 *
 * The element goes through the same permission gate as a requested field. Filtering on a
 * field reveals its contents just as returning it does: repeat a request narrowing the
 * value each time and you have read it, one comparison at a time. So a caller may only
 * filter on what they could have asked to see.
 *
 * @param array condition
 * @param array form_ids The forms whose elements the filter may reference
 * @param array allowedFields From formulize_apiAllowedFieldHandles
 * @return array array(element, value, operator, form id of the element, or 0 for a metadata field)
 * @throws FormulizeApiException
 */
function formulize_apiReadFilterCondition($condition, $form_ids, $allowedFields = array()) {
	if (!is_array($condition) or !isset($condition['element'])) {
		throw new FormulizeApiException('Each filter condition needs an element and a value', 'invalid_arguments');
	}
	if (!array_key_exists('value', $condition)) {
		throw new FormulizeApiException('The filter condition for '.$condition['element'].' has no value', 'invalid_arguments');
	}
	$element = $condition['element'];
	$value = $condition['value'];
	if (is_array($value) or is_object($value)) {
		throw new FormulizeApiException('The filter value for '.$element.' must be a single value', 'invalid_arguments');
	}
	$operator = (isset($condition['operator']) and $condition['operator'] !== '') ? strtoupper(trim($condition['operator'])) : 'LIKE';
	if (!in_array($operator, formulize_apiFilterOperators())) {
		throw new FormulizeApiException('Unsupported filter operator: '.$condition['operator'], 'invalid_arguments');
	}

	// A condition becomes one term of a filter string, with /**/ between its three parts and
	// ][ between terms. A value carrying either sequence would not be searched for, it would
	// be read back as more terms, on elements the caller never named and never passed through
	// the permission check below. There is no escape for them in that format, so they are
	// refused rather than quietly mangled.
	if (strstr((string) $value, '][') or strstr((string) $value, '/**/')) {
		throw new FormulizeApiException(
			'The filter value for '.$element.' cannot contain ][ or /**/',
			'invalid_arguments'
		);
	}

	formulize_apiCheckFieldPermissions(array($element), $allowedFields);

	$elementFormId = 0;
	$dataHandler = new formulizeDataHandler(false);
	if (!in_array($element, $dataHandler->metadataFields)) {
		if (!$elementObject = _getElementObject($element)) {
			throw new FormulizeApiException('Unknown element in filter: '.$element, 'unknown_element');
		} elseif (!in_array($elementObject->getVar('fid'), $form_ids)) {
			throw new FormulizeApiException('Element is not part of this dataset: '.$element, 'invalid_data');
		}
		$elementFormId = intval($elementObject->getVar('fid'));
	}

	return array($element, $value, $operator, $elementFormId);
}

/**
 * Check element handles and group them the way gatherDataset wants them.
 * @param array elementHandles Candidate element handles
 * @param int form_id The form id to file any metadata field names under
 * @return array Outer keys are form ids, values are the handles belonging to that form
 * @throws FormulizeApiException
 */
function formulize_apiValidateElementHandles($elementHandles, $form_id) {
	if (!is_array($elementHandles)) {
		return array();
	}
	$dataHandler = new formulizeDataHandler(false);
	$validatedHandles = array();
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
	foreach ($elementHandles as $handle) {
		if (!is_string($handle)) {
			throw new FormulizeApiException('Element handle must be a string', 'invalid_arguments');
		}
		if ($handle === '') {
			continue;
		}
		$validatedFormId = $form_id;
		if(!in_array($handle, $dataHandler->metadataFields)) {
			if(!$elementObject = $element_handler->get($handle)) {
				throw new FormulizeApiException('Invalid element handle: '.$handle, 'unknown_element');
			}
			$validatedFormId = $elementObject->getVar('fid');
		}
		$validatedHandles[$validatedFormId][] = $handle;
	}
	return $validatedHandles;
}

/**
 * Is this relationship real, and is this form part of it?
 * @return mixed The relationship object if valid, false if not
 */
function formulize_apiValidateRelationshipId($relationshipId, $formId) {
	$relationship_handler = xoops_getmodulehandler('frameworks', 'formulize');
	$validRelationships = $relationship_handler->getFrameworksByForm($formId, includePrimaryRelationship: true);
	return isset($validRelationships[$relationshipId]) ? $validRelationships[$relationshipId] : false;
}

/**
 * The relationship ids a given form can be queried through.
 * @return array
 */
function formulize_apiGetValidRelationshipIds($formId) {
	$relationship_handler = xoops_getmodulehandler('frameworks', 'formulize');
	$validRelationships = $relationship_handler->getFrameworksByForm($formId, includePrimaryRelationship: true);
	ksort($validRelationships);
	return array_keys($validRelationships);
}

/**
 * Check the paging parameters.
 * @return array array(limitStart, limitSize) where limitSize may be null for no limit
 * @throws FormulizeApiException
 */
function formulize_apiValidateLimitParameters($limitStart, $limitSize) {

	$validatedLimitStart = 0;

	if ($limitStart !== null) {
		if (!is_numeric($limitStart) or $limitStart < 0) {
			throw new FormulizeApiException('limitStart must be a non-negative integer', 'invalid_arguments');
		}
		$validatedLimitStart = intval($limitStart);
	}

	if ($limitSize === null) {
		return array($validatedLimitStart, null); // no limit
	}

	if (!is_numeric($limitSize)) {
		throw new FormulizeApiException('limitSize must be an integer or null', 'invalid_arguments');
	}
	$limitSizeInt = intval($limitSize);
	if ($limitSizeInt < 0) {
		throw new FormulizeApiException('limitSize must be non-negative', 'invalid_arguments');
	}
	// Guard against a single request trying to pull the whole database into memory.
	if ($limitSizeInt > 10000) {
		throw new FormulizeApiException('limitSize cannot exceed 10000 records', 'invalid_arguments');
	}

	return array($validatedLimitStart, $limitSizeInt);
}
