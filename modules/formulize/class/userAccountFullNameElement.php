<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2011 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                       ##
###############################################################################
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                        ##
###############################################################################

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php";
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/userAccountElement.php";

class formulizeUserAccountFullNameElement extends formulizeUserAccountElement {

	function __construct() {
		parent::__construct();
		$this->name = "User Account Full Name";
		$this->userProperty = "uname";
	}

}

#[AllowDynamicProperties]
class formulizeUserAccountFullNameElementHandler extends formulizeUserAccountElementHandler {

	function create() {
		return new formulizeUserAccountFullNameElement();
	}

	// List-only element — render as a read-only label if it appears in a form.
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen, $owner) {
		if (is_array($ele_value)) {
			$ele_value = "";
		}
		return new XoopsFormLabel($caption, $ele_value);
	}

}
