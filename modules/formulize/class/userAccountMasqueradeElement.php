<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2011 Freeform Solutions                  ##
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

class formulizeUserAccountMasqueradeElement extends formulizeUserAccountElement {

	function __construct() {
		parent::__construct();
		$this->name = "User Account Masquerade";
		$this->userProperty = ''; // no DB column; uid derived from entry_id
		$this->adminCanMakeRequired = false;
		$this->readOnly = true;
		$this->adminOnly = true; // only webmasters may see/use masquerade
	}

}

#[AllowDynamicProperties]
class formulizeUserAccountMasqueradeElementHandler extends formulizeUserAccountElementHandler {

	function create() {
		return new formulizeUserAccountMasqueradeElement();
	}

	/**
	 * Override the element help text to the masquerade-specific description.
	 *
	 * @param array $properties Element property array
	 * @return array Updated property array
	 */
	public function setupAndValidateElementProperties($properties, $existingElement = null) {
		$properties = parent::setupAndValidateElementProperties($properties, $existingElement);
		$properties['ele_desc'] = _formulize_UA_MASQUERADE_HELP;
		return $properties;
	}

	/**
	 * Return the uid of the user represented by this entry.
	 *
	 * @param object    $element  The element object
	 * @param mixed     $value    Ignored
	 * @param int|mixed $entry_id Entry ID
	 * @return int|null The uid, or null if the entry has no associated user
	 */
	function loadValue($element, $value, $entry_id) {
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($element->getVar('fid'));
		$uid = $formObject ? $formObject->getSystemUserIdFromEntry($entry_id) : 0;
		return $uid ?: null;
	}

	/**
	 * Render a Masquerade button that links to the masquerade endpoint.
	 *
	 * Returns an empty label when already masquerading, when there is no target user,
	 * or when the current user would be masquerading as themselves.
	 *
	 * @param mixed  $ele_value  The target uid (from loadValue)
	 * @param string $caption    Field caption
	 * @param string $markupName HTML element name
	 * @param bool   $isDisabled Whether the element is disabled (unused; always read-only)
	 * @param object $element    The element object (unused)
	 * @param mixed  $entry_id   Entry ID (unused)
	 * @param mixed  $screen     Screen object (unused)
	 * @param mixed  $owner      Owner context (unused)
	 * @return XoopsFormLabel
	 */
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen, $owner) {
		global $xoopsUser;
		if (isset($_SESSION['masquerade_xoopsUserId'])) {
			return new XoopsFormLabel($caption, '', $markupName);
		}
		$currentUid = $xoopsUser ? intval($xoopsUser->getVar('uid')) : 0;
		$targetUid = intval($ele_value);
		if (!$targetUid || $targetUid === $currentUid) {
			return new XoopsFormLabel($caption, '', $markupName);
		}
		$url = htmlspecialchars(XOOPS_URL . '/modules/formulize/masquerade.php?uid=' . $targetUid, ENT_QUOTES);
		$label = htmlspecialchars(_formulize_UA_MASQUERADE_BUTTON, ENT_QUOTES);
		$button = '<a href="' . $url . '" class="btn btn--primary">' . $label . '</a>';
		return new XoopsFormLabel($caption, $button, $markupName);
	}

	function prepareDataForDataset($value, $handle, $entry_id) {
		return '';
	}

	function prepareLiteralTextForDB($value, $element, $partialMatch = false) {
		return null;
	}

	function buildSearchWhereClause($term, $operator, $quotes, $likebits, $fid, $tableAlias = 'main') {
		return null;
	}

}
