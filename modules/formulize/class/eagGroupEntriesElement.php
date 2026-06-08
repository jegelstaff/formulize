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

// Virtual element type for the "Instances" column on the Groups management page.
// Represents the list of entry group instances (e.g. "HR", "Legal") for an
// entries-are-groups form. Entry group names follow the format
// "{PI value} - {Category name}", so the PI value (instance name) is the prefix.
// Has no real database column — data is injected post-query by
// injectGroupEntriesData() in usersAndGroups.php.
// This class exists solely to provide buildSearchWhereClause so that search
// terms typed into the Instances column produce a valid correlated subquery.

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/virtualElement.php";

class formulizeEagGroupEntriesElement extends formulizeVirtualElement {

	function __construct() {
		parent::__construct();
		$this->name = "EAG Group Entries";
	}

}

class formulizeEagGroupEntriesElementHandler extends formulizeVirtualElementHandler {

	function create() {
		return new formulizeEagGroupEntriesElement();
	}

	// Return a correlated EXISTS subquery that matches template group rows that
	// have at least one entry group whose name contains the search term.
	// Entry group names are stored as "{PI value} - {Category name}", so a search
	// for "HR" will match "HR - All Users", "HR - Staff", etc.
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
