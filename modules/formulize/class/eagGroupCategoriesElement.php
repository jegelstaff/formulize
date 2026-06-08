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
 * Virtual element representing the "Categories" column on the Groups management page.
 *
 * Displays the list of template group categories (e.g. "All Users", "Staff") for an
 * entries-are-groups form. Has no real database column — data is injected post-query
 * by injectGroupCategoriesData() in usersAndGroups.php.
 */
class formulizeEagGroupCategoriesElement extends formulizeVirtualElement {

	function __construct() {
		parent::__construct();
		$this->name = "EAG Group Categories";
	}

}

/** @see formulizeEagGroupCategoriesElement */
class formulizeEagGroupCategoriesElementHandler extends formulizeVirtualElementHandler {

	function create() {
		return new formulizeEagGroupCategoriesElement();
	}

	/**
	 * Return a correlated EXISTS subquery for searching by category name.
	 *
	 * Matches template group rows whose category name (the part after the
	 * "{FormTitle} - " prefix) contains the search term.
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
		return "EXISTS (SELECT 1 FROM `$groupsTable` AS g_cat"
			. " WHERE g_cat.form_id = `{$tableAlias}`.form_id"
			. " AND g_cat.is_group_template = 1"
			. " AND g_cat.name" . $safeTermClause . ")";
	}

}
