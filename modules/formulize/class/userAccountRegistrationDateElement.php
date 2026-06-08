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
##  source code which is considered copyrighted (c) material of the          ##
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

class formulizeUserAccountRegistrationDateElement extends formulizeUserAccountElement {

	function __construct() {
		parent::__construct();
		$this->name = "User Account Registration Date";
		$this->userProperty = "user_regdate"; // Unix timestamp in users table
		$this->adminCanMakeRequired = false;
		$this->readOnly = true; // system-managed, never overwritten by Formulize
	}

}

#[AllowDynamicProperties]
class formulizeUserAccountRegistrationDateElementHandler extends formulizeUserAccountElementHandler {

	function create() {
		return new formulizeUserAccountRegistrationDateElement();
	}

	// Always renders as a read-only label; registration date is system-managed.
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen, $owner) {
		$displayValue = $ele_value ? date(_MEDIUMDATESTRING, intval($ele_value) + formulize_getUserUTCOffsetSecs(timestamp: intval($ele_value))) : '';
		return new XoopsFormLabel($caption, htmlspecialchars($displayValue, ENT_QUOTES), $markupName);
	}

	function prepareDataForDataset($value, $handle, $entry_id) {
		return ($value && is_numeric($value)) ? date(_MEDIUMDATESTRING, intval($value) + formulize_getUserUTCOffsetSecs(timestamp: intval($value))) : '';
	}

	function prepareLiteralTextForDB($value, $element, $partialMatch = false) {
		return self::prepareDateTimestampForDB($value, $partialMatch);
	}

	function buildSearchWhereClause($term, $operator, $quotes, $likebits, $fid, $tableAlias = 'main') {
		$op = trim($operator);
		$partialMatch = !in_array($op, ['>=', '<=', '>', '<']);
		return $this->buildUnixTimestampClause('user_regdate', $term, $operator, $partialMatch, $tableAlias);
	}

}
