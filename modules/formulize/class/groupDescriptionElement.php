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
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/groupTableElement.php";

/**
 * Element type mapping to the 'description' column in the system groups table.
 */
class formulizeGroupDescriptionElement extends formulizeGroupTableElement {

	function __construct() {
		parent::__construct();
		$this->name          = "Group Description";
		$this->groupProperty = "description";
	}

}

/** @see formulizeGroupDescriptionElement */
#[AllowDynamicProperties]
class formulizeGroupDescriptionElementHandler extends formulizeGroupTableElementHandler {

	function create() {
		return new formulizeGroupDescriptionElement();
	}

	/**
	 * Render the group description field as a textarea (or a read-only label if disabled).
	 *
	 * @param mixed  $ele_value  Current field value
	 * @param string $caption    Field caption
	 * @param string $markupName HTML form element name
	 * @param bool   $isDisabled Whether the field is read-only
	 * @param object $element    The element object
	 * @param mixed  $entry_id   Entry ID (groupid for system groups form)
	 * @param mixed  $screen     Screen object (unused)
	 * @param mixed  $owner      Owner context (unused)
	 * @return XoopsFormElement
	 */
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen = false, $owner = null) {
		if (is_array($ele_value)) {
			$ele_value = "";
		}
		if ($isDisabled) {
			$form_ele = new XoopsFormLabel($caption, htmlspecialchars($ele_value, ENT_QUOTES));
		} else {
			$form_ele = new XoopsFormTextArea(
				$caption,
				$markupName,
				$ele_value,
				5,   // rows
				40   // cols
			);
			$form_ele->setExtra(" onchange=\"javascript:formulizechanged=1;\"");
		}
		return $form_ele;
	}

}
