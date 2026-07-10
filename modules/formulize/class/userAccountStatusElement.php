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
	 * Build a WHERE clause fragment to search by account status label or numeric level.
	 *
	 * Matches "Active" (level=1) and "Disabled" (level=-1) by label substring or numeric key.
	 *
	 * @param string|array $term       Search term(s) (label substring or numeric level)
	 * @param string       $operator   SQL operator; 'NOT LIKE'/'!=' produces a NOT IN clause
	 * @param string       $quotes     Ignored
	 * @param string       $likebits   Ignored
	 * @param int          $fid        Form ID (unused)
	 * @param string       $tableAlias Alias for the users table in the outer query
	 * @return string SQL WHERE clause fragment
	 */
	function buildSearchWhereClause($term, $operator, $quotes, $likebits, $fid, $tableAlias = 'main') {
		$isNegative = (trim($operator) === 'NOT LIKE' || trim($operator) === '!=');
		$options = array(1 => _formulize_UA_STATUS_ACTIVE, 0 => _formulize_UA_STATUS_PENDING, -1 => _formulize_UA_STATUS_DISABLED);
		$terms = is_array($term) ? $term : array($term);
		$matchingKeys = array();
		foreach ($terms as $t) {
			$t = trim($t);
			foreach ($options as $k => $label) {
				if (strlen($t) > 0 && stripos($label, $t) !== false) {
					$matchingKeys[] = $k;
				}
			}
			if (is_numeric($t) && array_key_exists(intval($t), $options)) {
				$matchingKeys[] = intval($t);
			}
		}
		$matchingKeys = array_unique($matchingKeys);
		if (empty($matchingKeys)) {
			return $isNegative ? '1=1' : '1=0';
		}
		$safeKeys = implode(',', $matchingKeys);
		return $isNegative
			? "{$tableAlias}.`level` NOT IN ($safeKeys)"
			: "{$tableAlias}.`level` IN ($safeKeys)";
	}

	/**
	 * Render the account status field as a dropdown (Active / Disabled).
	 *
	 * Defaults to Active when no value is set. When disabled, renders the label as read-only.
	 *
	 * @param mixed  $ele_value  Current level value (1 = Active, -1 = Disabled)
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
		// Pending (0) is the status of a self-registered account that has not yet confirmed its
		// email/phone (see signup.php). Like Disabled (-1) it cannot log in (checklogin.php refuses
		// level <= 0), but it is semantically distinct: awaiting the user, not blocked by an admin.
		$options = array(
			1  => _formulize_UA_STATUS_ACTIVE,
			0  => _formulize_UA_STATUS_PENDING,
			-1 => _formulize_UA_STATUS_DISABLED,
		);
		// Distinguish a genuinely unset value (new account, nothing loaded yet → default Active) from
		// an explicit 0 (a real Pending account), which must keep showing Pending.
		if ($ele_value === null || $ele_value === '') {
			$ele_value = 1; // new accounts default to Active
		}
		if ($isDisabled) {
			$label = isset($options[$ele_value]) ? $options[$ele_value] : htmlspecialchars($ele_value, ENT_QUOTES);
			$form_ele = new XoopsFormLabel($caption, $label, $markupName);
			return $form_ele;
		}
		$form_ele = new XoopsFormSelect($caption, $markupName, (int)$ele_value);
		$form_ele->addOptionArray($options);
		$form_ele->setExtra('onchange="javascript:formulizechanged=1;"');
		return $form_ele;
	}

}
