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
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php"; // you need to make sure the base element class has been read in first!
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/userAccountElement.php";
require_once XOOPS_ROOT_PATH . "/include/notification_constants.php";
require_once XOOPS_ROOT_PATH . "/language/english/notification.php";

class formulizeUserAccountNotificationMethodElement extends formulizeUserAccountElement {

    function __construct() {
			parent::__construct();
      $this->name = "User Account Notification Method";
			$this->userProperty = "notify_method";
		}

}

#[AllowDynamicProperties]
class formulizeUserAccountNotificationMethodElementHandler extends formulizeUserAccountElementHandler {

	function create() {
		return new formulizeUserAccountNotificationMethodElement();
	}

	protected function getOptions() {
		return array(
			XOOPS_NOTIFICATION_METHOD_EMAIL   => _NOT_METHOD_EMAIL,
			XOOPS_NOTIFICATION_METHOD_SMS     => _NOT_METHOD_SMS,
			XOOPS_NOTIFICATION_METHOD_PM      => _NOT_METHOD_PM,
			XOOPS_NOTIFICATION_METHOD_DISABLE => _NOT_METHOD_DISABLE,
		);
	}

	/**
	 * Convert the stored numeric notification method constant to its display label.
	 *
	 * @param mixed  $value    Notification method constant from the database
	 * @param string $handle   Element handle (unused)
	 * @param int    $entry_id Entry ID (unused)
	 * @return string Human-readable label, or the raw value if unrecognised
	 */
	function prepareDataForDataset($value, $handle, $entry_id) {
		$options = $this->getOptions();
		return isset($options[$value]) ? $options[$value] : $value;
	}

	/**
	 * Convert a human-readable notification method label to its numeric constant.
	 *
	 * @param mixed  $value        Label as typed by the user (e.g. "Email", "Disable")
	 * @param object $element      The element object (unused)
	 * @param bool   $partialMatch True for partial/LIKE matching, false for exact match
	 * @return int|array|mixed Matching constant(s), or the original value if no match found
	 */
	function prepareLiteralTextForDB($value, $element, $partialMatch = false) {
		$options = $this->getOptions();
		$matchingKeys = array();
		foreach ($options as $key => $label) {
			$matches = $partialMatch
				? (stripos($label, $value) !== false)
				: (strcasecmp($label, $value) === 0);
			if ($matches) {
				$matchingKeys[] = $key;
			}
		}
		if (empty($matchingKeys)) {
			return $value;
		}
		return count($matchingKeys) === 1 ? $matchingKeys[0] : $matchingKeys;
	}

	/**
	 * Build a WHERE clause fragment to search by notification method.
	 *
	 * @param string|array $term       Numeric constant(s) (resolved via prepareLiteralTextForDB)
	 * @param string       $operator   SQL operator; 'NOT LIKE'/'!=' produces a NOT IN clause
	 * @param string       $quotes     Ignored
	 * @param string       $likebits   Ignored
	 * @param int          $fid        Form ID (unused)
	 * @param string       $tableAlias Alias for the users table in the outer query
	 * @return string SQL WHERE clause fragment
	 */
	function buildSearchWhereClause($term, $operator, $quotes, $likebits, $fid, $tableAlias = 'main') {
		$isNegative = (trim($operator) === 'NOT LIKE' || trim($operator) === '!=');
		$options = $this->getOptions();

		$terms = is_array($term) ? $term : array($term);
		$matchingKeys = array();
		foreach ($terms as $t) {
			if (is_numeric($t)) {
				$k = intval($t);
				if (array_key_exists($k, $options)) {
					$matchingKeys[] = $k;
				}
			}
		}

		if (empty($matchingKeys)) {
			return $isNegative ? '1=1' : '1=0';
		}

		$safeKeys = implode(',', $matchingKeys);
		return $isNegative
			? "{$tableAlias}.`notify_method` NOT IN ($safeKeys)"
			: "{$tableAlias}.`notify_method` IN ($safeKeys)";
	}

	/**
	 * Render the notification method field as a radio group.
	 *
	 * Defaults to Email when no value is set.
	 *
	 * @param mixed  $ele_value  Current notification method constant
	 * @param string $caption    Field caption
	 * @param string $markupName HTML input name
	 * @param bool   $isDisabled Whether the field is read-only
	 * @param object $element    The element object (unused)
	 * @param mixed  $entry_id   Entry ID (unused)
	 * @param mixed  $screen     Screen object (unused)
	 * @param mixed  $owner      Owner context (unused)
	 * @return XoopsFormElement
	 */
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen, $owner) {
		if($ele_value === null OR $ele_value === false) {
			$ele_value = XOOPS_NOTIFICATION_METHOD_EMAIL;
		}
		return $this->renderUserAccountRadioButtons($this->getOptions(), $ele_value, $caption, $markupName, $isDisabled);
	}

}
