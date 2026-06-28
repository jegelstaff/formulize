<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2011 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considering copyrighted (c) material of the         ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Formulize Incorporated                              ##
##  Project: Formulize                                                       ##
###############################################################################

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php";
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/userAccountElement.php";

class formulizeUserAccountLastLoginElement extends formulizeUserAccountElement {

	function __construct() {
		parent::__construct();
		$this->name = "User Account Last Login";
		$this->userProperty = "last_login"; // Unix timestamp in users table
		$this->adminCanMakeRequired = false;
		$this->readOnly = true; // system-managed, never overwritten by Formulize
		$this->adminOnly = true; // webmaster-only display (informational)
	}

}

#[AllowDynamicProperties]
class formulizeUserAccountLastLoginElementHandler extends formulizeUserAccountElementHandler {

	function create() {
		return new formulizeUserAccountLastLoginElement();
	}

	/**
	 * Render the last login date as a read-only label (system-managed value).
	 *
	 * @param mixed  $ele_value  Unix timestamp of the last login
	 * @param string $caption    Field caption
	 * @param string $markupName HTML element name
	 * @param bool   $isDisabled Whether the field is disabled (always read-only)
	 * @param object $element    The element object (unused)
	 * @param mixed  $entry_id   Entry ID (unused)
	 * @param mixed  $screen     Screen object (unused)
	 * @param mixed  $owner      Owner context (unused)
	 * @return XoopsFormLabel
	 */
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen, $owner) {
		$displayValue = $ele_value ? date(_DATESTRING, intval($ele_value) + formulize_getUserUTCOffsetSecs(timestamp: intval($ele_value))) : '';
		return new XoopsFormLabel($caption, htmlspecialchars($displayValue, ENT_QUOTES), $markupName);
	}

	/**
	 * Format the raw Unix timestamp for list display.
	 *
	 * @param mixed  $value    Unix timestamp from the database
	 * @param string $handle   Element handle (unused)
	 * @param int    $entry_id Entry ID (unused)
	 * @return string Human-readable date string, or empty string if no value
	 */
	function prepareDataForDataset($value, $handle, $entry_id) {
		return ($value && is_numeric($value)) ? date(_DATESTRING, intval($value) + formulize_getUserUTCOffsetSecs(timestamp: intval($value))) : '';
	}

	/**
	 * Convert a human-readable date string to a MySQL datetime string for comparison.
	 *
	 * @param mixed  $value        User-supplied date string
	 * @param object $element      The element object (unused)
	 * @param bool   $partialMatch True for LIKE searches, false for range operators
	 * @return string MySQL-formatted date string
	 */
	function prepareLiteralTextForDB($value, $element, $partialMatch = false) {
		return self::prepareDateTimestampForDB($value, $partialMatch);
	}

	/**
	 * Build a WHERE clause fragment searching by last-login date.
	 *
	 * Uses FROM_UNIXTIME() so the stored Unix timestamp can be compared to a human-readable date.
	 *
	 * @param string $term       Search term (date string)
	 * @param string $operator   SQL operator
	 * @param string $quotes     Quote character(s) (unused; handled internally)
	 * @param string $likebits   LIKE wildcards (unused; handled internally)
	 * @param int    $fid        Form ID (unused)
	 * @param string $tableAlias Alias for the users table in the outer query
	 * @return string SQL WHERE clause fragment
	 */
	function buildSearchWhereClause($term, $operator, $quotes, $likebits, $fid, $tableAlias = 'main') {
		$op = trim($operator);
		$partialMatch = !in_array($op, ['>=', '<=', '>', '<']);
		return $this->buildUnixTimestampClause('last_login', $term, $operator, $partialMatch, $tableAlias);
	}

}
