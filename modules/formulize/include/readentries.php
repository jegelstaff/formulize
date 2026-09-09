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
 * @param object user The user to act as. Defaults to the current $xoopsUser. Null is anonymous.
 * @return array Keys: fid, formHandle, dataset, fieldsByForm, scope, and the validated
 *         filter/andOr/limitStart/limitSize/sortField/sortOrder/relationship values.
 * @throws FormulizeApiException
 */
function formulize_readEntries($formIdOrHandle, $options = array(), $user = null) {

    if ($user === null) {
        global $xoopsUser;
        $user = $xoopsUser;
    }
    $groups = $user ? $user->getGroups() : array(XOOPS_GROUP_ANONYMOUS);

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
    // Refuse anything this user may not see, before we go anywhere near the data.
    formulize_apiCheckFieldPermissions($fields, $fid, $relationship, $groups);
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
        formulize_apiCheckFieldPermissions(array($sortField), $fid, $relationship, $groups);
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
    $filter = formulize_apiValidateFilter($options['filter'] ?? '', $form_ids, $andOr);
    // An array filter is already a set of expressions carrying their own booleans, so the
    // operator between them is fixed at AND by formulize_parseFilter's contract.
    $andOr = is_array($filter) ? 'AND' : $andOr;

    // ---- scope and query ------------------------------------------------
    $scope = buildScope('all', $user, $fid);
    $actualScope = $scope[0];

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
        $relationship
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
 * @param array result The return value of formulize_readEntries
 * @param bool|array raw true for raw database values throughout, or a list of the field
 *        handles that should be raw while everything else stays readable
 * @return array A list of rows
 */
function formulize_renderEntriesAsRows($result, $raw = false) {

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

    $rows = array();
    foreach ($result['dataset'] as $item) {

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
 * fields into arrays and decodes html entities.
 *
 * Metadata fields cannot, because getValue locates a field by asking which form contains
 * that handle, and every form's records carry creation_datetime, entry_id and the rest.
 * getFormHandleFromEntry returns the first form it finds them in, which is not necessarily
 * the one being read, so once a relationship pulls connected forms into the item the answer
 * can come from the wrong form, or be filtered away entirely by a local entry id that only
 * means something in another form. Reading metadata straight out of the structure keeps it
 * attached to the entry it actually belongs to.
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
    return getValue($item, $handle, null, $localEntryId, $isRaw);
}

/**
 * Refuse any requested field handle this user is not allowed to read.
 *
 * getAllColList does the real work: it filters on the ele_display group lists and on
 * ele_private versus the view_private_elements permission, and it only reports elements
 * that actually hold data. Metadata field names are not elements, so they bypass it;
 * creator_email has its own masking inside the extraction.
 *
 * @param array fields The requested handles
 * @param int fid The main form id
 * @param int frid The relationship being queried
 * @param array groups The user's group ids
 * @return void
 * @throws FormulizeApiException if any requested handle is not permitted
 */
function formulize_apiCheckFieldPermissions($fields, $fid, $frid, $groups) {
    $dataHandler = new formulizeDataHandler(false);
    $allowed = array();
    $cols = getAllColList($fid, $frid, $groups);
    foreach ($cols as $thisFormCols) {
        if (!is_array($thisFormCols)) {
            continue;
        }
        foreach ($thisFormCols as $col) {
            $allowed[$col['ele_handle']] = true;
        }
    }
    foreach ($fields as $handle) {
        if (!is_string($handle)) {
            throw new FormulizeApiException('Field names must be strings', 'invalid_arguments');
        }
        if ($handle === '' or in_array($handle, $dataHandler->metadataFields)) {
            continue;
        }
        if (!isset($allowed[$handle])) {
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
 * Also accepted, as legacy input: an integer entry id, or an old style filter string of
 * terms joined with the bracket separator.
 *
 * @param mixed filter
 * @param array form_ids The forms whose elements the filter may reference
 * @param string andOr The operator joining the top level items
 * @return mixed A string or array suitable for gatherDataset
 * @throws FormulizeApiException
 */
function formulize_apiValidateFilter($filter, $form_ids, $andOr = 'AND') {

    if (is_numeric($filter)) {
        return intval($filter);
    }
    if (empty($filter)) {
        return '';
    }
    if (is_string($filter)) {
        $trimmed = ltrim($filter);
        if (substr($trimmed, 0, 1) === '[' or substr($trimmed, 0, 1) === '{') {
            $decoded = json_decode($filter, true);
            if ($decoded === null) {
                throw new FormulizeApiException('Invalid JSON in the filter parameter: '.json_last_error_msg(), 'invalid_arguments');
            }
            $filter = $decoded;
        } else {
            // An old style filter string, passed straight through to gatherDataset.
            return $filter;
        }
    }
    if (!is_array($filter)) {
        throw new FormulizeApiException('The filter parameter must be a number, a string, or an array', 'invalid_arguments');
    }

    $bareTerms = array();
    $expressions = array();
    $blankSearches = array();

    foreach ($filter as $item) {

        if (!is_array($item)) {
            throw new FormulizeApiException('Each filter item must be a condition or a group', 'invalid_arguments');
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
                if (is_array($condition) and (isset($condition['any']) or isset($condition['all']))) {
                    throw new FormulizeApiException(
                        'Filter groups cannot be nested inside other groups. Formulize filters support one level of grouping.',
                        'invalid_arguments'
                    );
                }
                $groupTerms = array_merge($groupTerms, formulize_apiBuildFilterTerms($condition, $form_ids, $groupOperator));
            }
            $expressions[] = array($groupOperator, implode('][', $groupTerms));
            continue;
        }

        // ---- a bare condition --------------------------------------------
        list($element, $value, $operator) = formulize_apiReadFilterCondition($item, $form_ids);
        if ($value === '{BLANK}') {
            // A blank test is two terms with a boolean of its own, so it cannot simply join the
            // other bare terms. Lift it into its own expression, as the MCP tool has always done.
            list($blankBoolean, $blankTerms) = formulize_apiBuildBlankTerms($element, $operator);
            $blankSearches[$blankBoolean][] = implode('][', $blankTerms);
        } else {
            $bareTerms[] = $element.'/**/'.$value.'/**/'.$operator;
        }
    }

    // Nothing but plain terms: use the flat string form, which is what gatherDataset likes best.
    if (count($expressions) == 0 and count($blankSearches) == 0) {
        return implode('][', $bareTerms);
    }

    $returnFilter = array();
    if (count($bareTerms)) {
        $returnFilter[] = array($andOr, implode('][', $bareTerms));
    }
    foreach ($expressions as $expression) {
        $returnFilter[] = $expression;
    }
    foreach (array('AND', 'OR') as $blankBoolean) {
        if (isset($blankSearches[$blankBoolean])) {
            $returnFilter[] = array($blankBoolean, implode('][', $blankSearches[$blankBoolean]));
        }
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
function formulize_apiBuildFilterTerms($condition, $form_ids, $groupOperator) {
    list($element, $value, $operator) = formulize_apiReadFilterCondition($condition, $form_ids);
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
 * Pull element, value and operator out of one condition, and check the element is usable.
 * @return array array(element, value, operator)
 * @throws FormulizeApiException
 */
function formulize_apiReadFilterCondition($condition, $form_ids) {
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

    $dataHandler = new formulizeDataHandler(false);
    if (!in_array($element, $dataHandler->metadataFields)) {
        if (!$elementObject = _getElementObject($element)) {
            throw new FormulizeApiException('Unknown element in filter: '.$element, 'unknown_element');
        } elseif (!in_array($elementObject->getVar('fid'), $form_ids)) {
            throw new FormulizeApiException('Element is not part of this dataset: '.$element, 'invalid_data');
        }
    }

    return array($element, $value, $operator);
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
        if (!$elementObject = $element_handler->get($handle) and !in_array($handle, $dataHandler->metadataFields)) {
            throw new FormulizeApiException('Invalid element handle: '.$handle, 'unknown_element');
        }
        $validatedHandles[($elementObject ? $elementObject->getVar('fid') : $form_id)][] = $handle;
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
