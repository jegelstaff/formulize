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

// Virtual element type for the "Type" column on the Groups management page.
// Displays "Regular" for plain groups and "Form-based" for template groups.
// Has no real database column — data is injected post-query by
// injectGroupTypeData() in usersAndGroups.php.
// buildSearchWhereClause maps "Form-based" → is_group_template = 1,
// anything else → is_group_template = 0.

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/virtualElement.php";

class formulizeEagGroupTypeElement extends formulizeVirtualElement {

	function __construct() {
		parent::__construct();
		$this->name = "Type";
	}

}

class formulizeEagGroupTypeElementHandler extends formulizeVirtualElementHandler {

	function create() {
		return new formulizeEagGroupTypeElement();
	}

	function getFilterOptions() {
		return array('Regular' => 'Regular', 'Form-based' => 'Form-based');
	}

	// Map the search term to an is_group_template predicate.
	// "Form-based" → template groups only; anything else → regular groups only.
	function buildSearchWhereClause($term, $operator, $quotes, $likebits, $fid, $tableAlias = 'main') {
		$isTemplate = (strtolower(trim($term)) === 'form-based') ? 1 : 0;
		return "`{$tableAlias}`.is_group_template = $isTemplate";
	}

}
