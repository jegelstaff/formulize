<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2011 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                       ##
###############################################################################
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                        ##
###############################################################################

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php";
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/userAccountElement.php";

class formulizeUserAccountFullNameElement extends formulizeUserAccountElement {

	function __construct() {
		parent::__construct();
		$this->name = "User Account Full Name";
		$this->userProperty = "uname";
	}

}

#[AllowDynamicProperties]
class formulizeUserAccountFullNameElementHandler extends formulizeUserAccountElementHandler {

	function create() {
		return new formulizeUserAccountFullNameElement();
	}

	/**
	 * Render the full name as a read-only label (this is a list-only / display element).
	 *
	 * @param mixed  $ele_value  Current uname value
	 * @param string $caption    Field caption
	 * @param string $markupName HTML element name (unused)
	 * @param bool   $isDisabled Whether the field is disabled (always treated as read-only)
	 * @param object $element    The element object (unused)
	 * @param mixed  $entry_id   Entry ID (unused)
	 * @param mixed  $screen     Screen object (unused)
	 * @param mixed  $owner      Owner context (unused)
	 * @return XoopsFormLabel
	 */
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen, $owner) {
		if (is_array($ele_value)) {
			$ele_value = "";
		}
		return new XoopsFormLabel($caption, $ele_value);
	}

	/**
	 * Sort Full Name by last name (everything after the last space in uname).
	 *
	 * @param string $tableAlias SQL alias for the row in the users table (e.g. 'main' or 'u')
	 * @return string SQL expression
	 */
	public function buildSortExpression($tableAlias) {
		return "SUBSTRING_INDEX(`{$tableAlias}`.uname, ' ', -1)";
	}

}
