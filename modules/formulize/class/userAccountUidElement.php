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

class formulizeUserAccountUidElement extends formulizeUserAccountElement {

    function __construct() {
			parent::__construct();
      $this->name = "User Account UID";
			$this->overrideDataType = "MEDIUMINT(8) UNSIGNED";
			$this->hasData = true;
			$this->userProperty = "uid";
			$this->readOnly = true; // uid is determined from first principles on submission, never written via the user property loop
			$this->adminOnly = true; // webmaster-only display (informational)
		}

}

#[AllowDynamicProperties]
class formulizeUserAccountUidElementHandler extends formulizeUserAccountElementHandler {

	function create() {
		return new formulizeUserAccountUidElement();
	}

	/**
	 * Render the UID as a read-only label (system-managed; never editable by users).
	 *
	 * @param mixed  $ele_value  The uid value
	 * @param string $caption    Field caption
	 * @param string $markupName HTML element name (unused)
	 * @param bool   $isDisabled Whether the field is disabled (always read-only)
	 * @param object $element    The element object (unused)
	 * @param mixed  $entry_id   Entry ID (unused)
	 * @param mixed  $screen     Screen object (unused)
	 * @param mixed  $owner      Owner context (unused)
	 * @return XoopsFormLabel
	 */
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen, $owner) {
		$formElement = new xoopsFormLabel($caption, $this->makeValueSafeForReadOnlyDisplay($ele_value, $element->getVar('ele_handle'), $entry_id));
		return $formElement;
	}

}
