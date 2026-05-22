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
	}

}

#[AllowDynamicProperties]
class formulizeUserAccountStatusElementHandler extends formulizeUserAccountElementHandler {

	function create() {
		return new formulizeUserAccountStatusElement();
	}

	function buildSearchWhereClause($term, $operator, $quotes, $likebits, $fid, $tableAlias = 'main') {
		$isNegative = (trim($operator) === 'NOT LIKE' || trim($operator) === '!=');
		$options = array(1 => _formulize_UA_STATUS_ACTIVE, -1 => _formulize_UA_STATUS_DISABLED);
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

	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen, $owner) {
		$options = array(
			1  => _formulize_UA_STATUS_ACTIVE,
			-1 => _formulize_UA_STATUS_DISABLED,
		);
		if (!$ele_value && $ele_value !== -1) {
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
