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
 * Virtual element representing the "Type" column on the Groups management page.
 *
 * Displays "Regular" for plain groups and "Form-based" for template groups.
 * Has no real database column — data is injected post-query by injectGroupTypeData().
 */
class formulizeEagGroupTypeElement extends formulizeVirtualElement {

	function __construct() {
		parent::__construct();
		$this->name = "Type";
	}

}

/** @see formulizeEagGroupTypeElement */
class formulizeEagGroupTypeElementHandler extends formulizeVirtualElementHandler {

	function create() {
		return new formulizeEagGroupTypeElement();
	}

	/**
	 * Return the available filter options for this element type.
	 *
	 * @param object $element The element object (not needed - the options are fixed)
	 * @return array Associative array of option value => display label
	 */
	function getFilterOptions($element = null) {
		return array('Regular' => 'Regular', 'Form-based' => 'Form-based');
	}

	/**
	 * Map the search term to an is_group_template predicate.
	 *
	 * "Form-based" (case-insensitive) maps to template groups only (is_group_template = 1);
	 * anything else maps to regular groups only (is_group_template = 0).
	 *
	 * @param string $term       The search term
	 * @param string $operator   Ignored; predicate is always equality
	 * @param string $quotes     Ignored
	 * @param string $likebits   Ignored
	 * @param int    $fid        Form ID (unused)
	 * @param string $tableAlias Alias for the main table in the outer query
	 * @return string SQL WHERE clause fragment
	 */
	function buildSearchWhereClause($term, $operator, $quotes, $likebits, $fid, $tableAlias = 'main') {
		$isTemplate = (strtolower(trim($term)) === 'form-based') ? 1 : 0;
		return "`{$tableAlias}`.is_group_template = $isTemplate";
	}

}
