<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                       ##
###############################################################################
##  Author of this file: Formulize Incorporated                               ##
##  Project: Formulize                                                        ##
###############################################################################

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/virtualElement.php";

/**
 * Virtual element representing the "Type" column on the Users management page.
 *
 * Displays the title of the entries-are-users form the user belongs to, or "Regular"
 * if no EAU form claims the user. Has no backing database column — data is injected
 * post-query by injectUserEauTypeData() in usersAndGroups.php.
 * buildSearchWhereClause() fans out across dynamic EAU data tables and returns a
 * static uid IN (...) clause.
 */
class formulizeUserEauTypeElement extends formulizeVirtualElement {

	function __construct() {
		parent::__construct();
		$this->name = "Type";
	}

}

class formulizeUserEauTypeElementHandler extends formulizeVirtualElementHandler {

	function create() {
		return new formulizeUserEauTypeElement();
	}

	/**
	 * Return the available filter options for this element.
	 *
	 * Returns "Regular" plus one entry per EAU form (singular name, or form_title as fallback).
	 *
	 * @param object $element The element object (not needed - the options come from the EAU forms)
	 * @return array Associative array of option value => display label
	 */
	function getFilterOptions($element = null) {
		global $xoopsDB;
		$options    = array('Regular' => 'Regular');
		$formsTable = $xoopsDB->prefix('formulize_id');
		$res        = $xoopsDB->query(
			"SELECT form_title, singular FROM `$formsTable` WHERE entries_are_users = 1 ORDER BY form_title"
		);
		while ($res && ($row = $xoopsDB->fetchArray($res))) {
			$label           = (!empty($row['singular'])) ? $row['singular'] : $row['form_title'];
			$options[$label] = $label;
		}
		return $options;
	}

	/**
	 * Build a WHERE clause fragment matching users by their EAU type.
	 *
	 * "Regular" (case-insensitive) returns users NOT linked to any EAU form.
	 * Any other term fans out across EAU form data tables whose singular name matches,
	 * then returns a static uid IN (...) clause.
	 *
	 * @param string $term       The search term (e.g. "Regular" or an EAU form singular name)
	 * @param string $operator   SQL operator applied when matching form names
	 * @param string $quotes     Quote character(s) for the term
	 * @param string $likebits   LIKE wildcards
	 * @param int    $fid        Form ID (unused)
	 * @param string $tableAlias Alias for the users table in the outer query
	 * @return string SQL WHERE clause fragment
	 */
	function buildSearchWhereClause($term, $operator, $quotes, $likebits, $fid, $tableAlias = 'main') {
		global $xoopsDB;

		$formsTable = $xoopsDB->prefix('formulize_id');

		if (strtolower(trim($term)) === 'regular') {
			// Collect every UID linked to any EAU form.
			$allEauRes = $xoopsDB->query(
				"SELECT id_form, form_handle FROM `$formsTable` WHERE entries_are_users = 1"
			);
			$allEauUids = array();
			while ($allEauRes && ($formRow = $xoopsDB->fetchArray($allEauRes))) {
				$formHandle = $formRow['form_handle'];
				if (!preg_match('/^[a-z0-9_]+$/i', $formHandle)) {
					continue;
				}
				$uidCol    = 'formulize_user_account_uid_' . intval($formRow['id_form']);
				$dataTable = $xoopsDB->prefix('formulize_' . $formHandle);
				$innerRes  = $xoopsDB->query(
					"SELECT `$uidCol` FROM `$dataTable` WHERE `$uidCol` IS NOT NULL AND `$uidCol` > 0"
				);
				if ($innerRes) {
					while ($row = $xoopsDB->fetchArray($innerRes)) {
						$uid = intval($row[$uidCol]);
						if ($uid > 0) {
							$allEauUids[] = $uid;
						}
					}
				}
			}
			if (empty($allEauUids)) {
				return "1=1"; // no EAU forms exist, every user is regular
			}
			return "`{$tableAlias}`.uid NOT IN (" . implode(',', array_unique($allEauUids)) . ")";
		}

		// Find EAU forms whose displayed singular name matches the search term.
		// Mirrors getSingular(): use the singular field when set, fall back to form_title.
		// For IN / NOT IN, $term is already a fully escaped, parenthesized list from prepareValueForInOperator; escaping it again would corrupt the structural quotes. Escape only scalar terms.
		$escapedTerm = (trim($operator) === 'IN' || trim($operator) === 'NOT IN') ? $term : formulize_db_escape($term);
		$safeTermClause = ' ' . trim($operator) . ' ' . $quotes . $likebits . $escapedTerm . $likebits . $quotes;
		$res = $xoopsDB->query(
			"SELECT id_form, form_handle FROM `$formsTable`"
			. " WHERE entries_are_users = 1"
			. " AND ((singular IS NOT NULL AND singular != '' AND singular" . $safeTermClause . ")"
			. "   OR ((singular IS NULL OR singular = '') AND form_title" . $safeTermClause . "))"
		);

		if (!$res || $xoopsDB->getRowsNum($res) == 0) {
			return "1=0";
		}

		$matchingUids = array();
		while ($formRow = $xoopsDB->fetchArray($res)) {
			$formId     = intval($formRow['id_form']);
			$formHandle = $formRow['form_handle'];
			if (!preg_match('/^[a-z0-9_]+$/i', $formHandle)) {
				continue;
			}
			$uidCol    = 'formulize_user_account_uid_' . $formId;
			$dataTable = $xoopsDB->prefix('formulize_' . $formHandle);
			$innerRes  = $xoopsDB->query(
				"SELECT `$uidCol` FROM `$dataTable`"
				. " WHERE `$uidCol` IS NOT NULL AND `$uidCol` > 0"
			);
			if ($innerRes) {
				while ($row = $xoopsDB->fetchArray($innerRes)) {
					$uid = intval($row[$uidCol]);
					if ($uid > 0) {
						$matchingUids[] = $uid;
					}
				}
			}
		}

		if (empty($matchingUids)) {
			return "1=0";
		}

		return "`{$tableAlias}`.uid IN (" . implode(',', array_unique($matchingUids)) . ")";
	}

}
