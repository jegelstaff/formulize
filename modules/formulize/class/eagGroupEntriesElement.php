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
 * Virtual element representing the "Instances" column on the Groups management page.
 *
 * Shows the entry group instances (e.g. "HR", "Legal") for an entries-are-groups form.
 * Entry group names follow the format "{PI value} - {Category name}", so the PI value
 * is the instance name. Data is injected post-query by injectGroupEntriesData().
 */
class formulizeEagGroupEntriesElement extends formulizeVirtualElement {

	function __construct() {
		parent::__construct();
		$this->name = "EAG Group Entries";
	}

}

/** @see formulizeEagGroupEntriesElement */
class formulizeEagGroupEntriesElementHandler extends formulizeVirtualElementHandler {

	function create() {
		return new formulizeEagGroupEntriesElement();
	}

	/**
	 * Return a correlated EXISTS subquery for searching by entry group instance name.
	 *
	 * Matches template group rows that have at least one entry group whose name
	 * contains the search term. Entry groups are stored as "{PI value} - {Category name}",
	 * so searching "HR" will match "HR - All Users", "HR - Staff", etc.
	 *
	 * @param string $term       The search term
	 * @param string $operator   SQL comparison operator
	 * @param string $quotes     Quote characters around the value
	 * @param string $likebits   LIKE wildcard characters
	 * @param int    $fid        Form ID (unused for this element type)
	 * @param string $tableAlias Alias for the main table in the outer query
	 * @return string SQL WHERE clause fragment
	 */
	function buildSearchWhereClause($term, $operator, $quotes, $likebits, $fid, $tableAlias = 'main') {
		global $xoopsDB;
		$groupsTable    = $xoopsDB->prefix('groups');
		// For IN / NOT IN, $term is already a fully escaped, parenthesized list from prepareValueForInOperator; escaping it again would corrupt the structural quotes. Escape only scalar terms.
		$escapedTerm = (trim($operator) === 'IN' || trim($operator) === 'NOT IN') ? $term : formulize_db_escape($term);
		$safeTermClause = ' ' . trim($operator) . ' ' . $quotes . $likebits . $escapedTerm . $likebits . $quotes;
		return "EXISTS (SELECT 1 FROM `$groupsTable` AS g_ent"
			. " WHERE g_ent.form_id = `{$tableAlias}`.form_id"
			. " AND g_ent.is_group_template = 0"
			. " AND g_ent.entry_id > 0"
			. " AND g_ent.name" . $safeTermClause . ")";
	}

}
