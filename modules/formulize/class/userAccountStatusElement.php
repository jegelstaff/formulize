<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
###############################################################################
##  Author of this file: Formulize Incorporated                              ##
##  Project: Formulize                                                       ##
###############################################################################

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php";
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/userAccountElement.php";

class formulizeUserAccountStatusElement extends formulizeUserAccountElement {

	function __construct() {
		parent::__construct();
		$this->name = "Account Status";
		$this->userProperty = "level";
		$this->adminOnly = true; // only webmasters may see/change account status
	}

}

#[AllowDynamicProperties]
class formulizeUserAccountStatusElementHandler extends formulizeUserAccountElementHandler {

	function create() {
		return new formulizeUserAccountStatusElement();
	}

	/**
	 * The statuses an account can have, by the users table's level, with the words for each. Pending (0) is a
	 * self-registered account that hasn't confirmed its email or phone yet (see signup.php). Like Disabled (-1), it
	 * can't log in, since checklogin.php refuses a level of 0 or less, but it's waiting on the person, not blocked by
	 * an admin.
	 *
	 * @return array Level => words
	 */
	private function statusLabels() {
		return array(
			1  => _formulize_UA_STATUS_ACTIVE,
			0  => _formulize_UA_STATUS_PENDING,
			-1 => _formulize_UA_STATUS_DISABLED,
		);
	}

	/**
	 * The status of a level. Only 1, 0 and -1 have words, but real accounts have other levels: the installer gives
	 * the first admin 5, and Formulize::createUser() in integration_api.php stores whatever level it's sent. Nothing
	 * gives those levels a meaning of their own. What counts is whether the account can log in, which
	 * checklogin.php allows above 0, so any level above 0 is Active and any level below 0 is Disabled.
	 *
	 * @param mixed $level The users table's level
	 * @return int 1, 0 or -1, a key of statusLabels()
	 */
	public static function statusOfLevel($level) {
		$level = intval($level);
		return $level > 0 ? 1 : ($level < 0 ? -1 : 0);
	}

	/**
	 * The status in words, for a list and anything else that reads the dataset, the way an option's words replace
	 * its stored value. The stored value is the users table's level, and any level has a status, see statusOfLevel().
	 *
	 * @param mixed  $value    The level
	 * @param string $handle   Element handle
	 * @param int    $entry_id Entry ID
	 * @return mixed The status's words, or the value as it was if it isn't a number
	 */
	function prepareDataForDataset($value, $handle, $entry_id) {
		$labels = $this->statusLabels();
		return is_numeric($value) ? $labels[self::statusOfLevel($value)] : $value;
	}

	/**
	 * Build a WHERE clause fragment to search by account status.
	 *
	 * A term matches a status by its words, or by a level, which stands for that level's status. Each status covers
	 * a range of levels, see statusOfLevel(), so searching for Active finds the first admin's level of 5 too.
	 *
	 * @param string|array $term       Search term(s) (part of a status's words, or a level)
	 * @param string       $operator   SQL operator; 'NOT LIKE'/'!=' excludes the matching statuses
	 * @param string       $quotes     Ignored
	 * @param string       $likebits   Ignored
	 * @param int          $fid        Form ID (unused)
	 * @param string       $tableAlias Alias for the users table in the outer query
	 * @return string SQL WHERE clause fragment
	 */
	function buildSearchWhereClause($term, $operator, $quotes, $likebits, $fid, $tableAlias = 'main') {
		$isNegative = (trim($operator) === 'NOT LIKE' || trim($operator) === '!=');
		$options = $this->statusLabels();
		$terms = is_array($term) ? $term : array($term);
		$matchingStatuses = array();
		foreach ($terms as $t) {
			$t = trim($t);
			foreach ($options as $status => $label) {
				if (strlen($t) > 0 && stripos($label, $t) !== false) {
					$matchingStatuses[] = $status;
				}
			}
			if (is_numeric($t)) {
				$matchingStatuses[] = self::statusOfLevel($t);
			}
		}
		$matchingStatuses = array_unique($matchingStatuses);
		if (empty($matchingStatuses)) {
			return $isNegative ? '1=1' : '1=0';
		}
		// level is a varchar column, so compare it as a number
		$levelRanges = array(1 => '> 0', 0 => '= 0', -1 => '< 0');
		$comparisons = array();
		foreach ($matchingStatuses as $status) {
			$comparisons[] = "CAST({$tableAlias}.`level` AS SIGNED) " . $levelRanges[$status];
		}
		$clause = '(' . implode(' OR ', $comparisons) . ')';
		return $isNegative ? "NOT $clause" : $clause;
	}

	/**
	 * Render the account status field as a dropdown (Active / Pending / Disabled).
	 *
	 * Defaults to Active when no value is set. When disabled, renders the label as read-only. The status shown is the
	 * level's status (see statusOfLevel()), and saving the same status keeps the level as it was, see
	 * collectPendingUserVars() in userAccountElement.php.
	 *
	 * @param mixed  $ele_value  Current level value (above 0 = Active, 0 = Pending, below 0 = Disabled)
	 * @param string $caption    Field caption
	 * @param string $markupName HTML element name
	 * @param bool   $isDisabled Whether the field is read-only
	 * @param object $element    The element object (unused)
	 * @param mixed  $entry_id   Entry ID (unused)
	 * @param mixed  $screen     Screen object (unused)
	 * @param mixed  $owner      Owner context (unused)
	 * @return XoopsFormElement
	 */
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen, $owner) {
		$options = $this->statusLabels();
		// Distinguish a genuinely unset value (new account, nothing loaded yet → default Active) from
		// an explicit 0 (a real Pending account), which must keep showing Pending.
		if ($ele_value === null || $ele_value === '') {
			$ele_value = 1; // new accounts default to Active
		}
		$status = self::statusOfLevel($ele_value);
		if ($isDisabled) {
			$form_ele = new XoopsFormLabel($caption, $options[$status], $markupName);
			return $form_ele;
		}
		$form_ele = new XoopsFormSelect($caption, $markupName, $status);
		$form_ele->addOptionArray($options);
		$form_ele->setExtra('onchange="javascript:formulizechanged=1;"');
		return $form_ele;
	}

}
