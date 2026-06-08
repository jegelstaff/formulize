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

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php";

/**
 * Base class for virtual element types — elements that have no backing database
 * column and are populated post-query by injection functions.
 *
 * Virtual elements are always system-managed; they cannot be created via the admin
 * element picker. Subclass this to define a typed virtual column. Implement
 * buildSearchWhereClause() on the handler to enable search delegation from
 * parseTableFormFilter().
 */
class formulizeVirtualElement extends formulizeElement {

	var $isVirtualElement = true;

	function __construct() {
		parent::__construct();
		$this->name           = "Virtual Element";
		$this->isSystemElement = true;
		$this->hasData        = false;
		$this->needsDataType  = false;
	}

}

/**
 * Base handler for virtual element types.
 *
 * Provides no-op implementations of the standard element handler methods.
 * Subclasses should override buildSearchWhereClause() to support filtering.
 */
class formulizeVirtualElementHandler extends formulizeElementsHandler {

	function create() {
		return new formulizeVirtualElement();
	}

	function prepareDataForDataset($value, $handle, $entry_id) {
		return $value;
	}

	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen = false, $owner = null) {
		return null;
	}

	/**
	 * Format a virtual element value for list display.
	 *
	 * Virtual elements store arrays of pre-formatted strings. HTML escaping and
	 * printSmart truncation are both skipped — injection functions apply their own
	 * display limits before this is called, and values may contain HTML links.
	 *
	 * @param mixed $value Pre-formatted value from the injection function
	 * @param string $handle Element handle (unused)
	 * @param int $entry_id Entry ID (unused)
	 * @param int $textWidth Column width hint (unused)
	 * @return string Formatted display value
	 */
	function formatDataForList($value, $handle = "", $entry_id = 0, $textWidth = 100) {
		if (is_array($value)) {
			//$value = implode('<br>', $value);
		}
		$this->striphtml = false;
		$this->length    = 0;
		$this->clickable = false;
		return parent::formatDataForList($value, $handle, $entry_id, $textWidth);
	}

}
