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

	function prepareDataForDataset($value, $handle, $entry_id) {
		$options = $this->getOptions();
		return isset($options[$value]) ? $options[$value] : $value;
	}

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

	// this method renders the element for display in a form
	// the caption has been pre-prepared and passed in separately from the element object
	// if the element is disabled, then the method must take that into account and return a non-interactable label with some version of the element's value in it
	// $ele_value is the options for this element - which will either be the admin values set by the admin user, or will be the value created in the loadValue method
	// $caption is the prepared caption for the element
	// $markupName is what we have to call the rendered element in HTML
	// $isDisabled flags whether the element is disabled or not so we know how to render it
	// $element is the element object
	// $entry_id is the ID number of the entry where this particular element comes from
	// $screen is the screen object that is in effect, if any (may be null)
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen, $owner) {
		if($ele_value === null OR $ele_value === false) {
			$ele_value = XOOPS_NOTIFICATION_METHOD_EMAIL;
		}
		return $this->renderUserAccountRadioButtons($this->getOptions(), $ele_value, $caption, $markupName, $isDisabled);
	}

}
