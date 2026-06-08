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
 * Element type mapping to the 'name' column in the system groups table.
 */
class formulizeGroupNameElement extends formulizeGroupTableElement {

	function __construct() {
		parent::__construct();
		$this->name          = "Group Name";
		$this->groupProperty = "name";
	}

}

/** @see formulizeGroupNameElement */
#[AllowDynamicProperties]
class formulizeGroupNameElementHandler extends formulizeGroupTableElementHandler {

	function create() {
		return new formulizeGroupNameElement();
	}

	/**
	 * Render the group name field as a text input (or a read-only label if disabled).
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
			$config_handler    = xoops_gethandler('config');
			$formulizeConfig   = $config_handler->getConfigsByCat(0, getFormulizeModId());
			$form_ele = new XoopsFormText(
				$caption,
				$markupName,
				isset($formulizeConfig['t_width']) ? $formulizeConfig['t_width'] : 30,
				isset($formulizeConfig['t_max'])   ? $formulizeConfig['t_max']   : 255,
				$ele_value,
				false,
				'text'
			);
			$form_ele->setExtra(" onchange=\"javascript:formulizechanged=1;\"");
		}
		return $form_ele;
	}

}
