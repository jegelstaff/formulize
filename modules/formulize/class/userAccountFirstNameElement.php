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

class formulizeUserAccountFirstNameElement extends formulizeUserAccountElement {

    function __construct() {
			parent::__construct();
      $this->name = "User Account First Name";
			$this->userProperty = "uname";
    }

}

#[AllowDynamicProperties]
class formulizeUserAccountFirstNameElementHandler extends formulizeUserAccountElementHandler {

	function create() {
		return new formulizeUserAccountFirstNameElement();
	}

	private function extractNamePart($value) {
		$value = trim($value);
		$elementTypeName = strtolower(str_ireplace(['formulizeUserAccount', 'ElementHandler'], "", static::class));
		$lastSpace = strrpos($value, ' ');
		if ($lastSpace === false) {
			return $value;
		}
		if ($elementTypeName != 'lastname') {
			return substr($value, 0, $lastSpace);  // first name = everything before the last space
		} else {
			return substr($value, $lastSpace + 1); // last name = everything after the last space
		}
	}


	/**
	 * Load the first-name portion of the uname field for this entry.
	 *
	 * Delegates to the parent to read the full uname from the users table, then
	 * extracts the appropriate name part via extractNamePart().
	 *
	 * @param object    $element  The element object
	 * @param mixed     $value    Ignored; value is read from the user record
	 * @param int|mixed $entry_id Entry ID
	 * @return string The first-name portion of the uname value
	 */
	function loadValue($element, $value, $entry_id) {
		$value = parent::loadValue($element, $value, $entry_id);
		return $this->extractNamePart($value);
	}

	/**
	 * Format a dataset value for list display.
	 *
	 * Splits the full uname value into the appropriate name part (first or last)
	 * before delegating to the parent formatter.
	 *
	 * @param mixed  $value     Full uname value from the dataset
	 * @param string $handle    Element handle
	 * @param int    $entry_id  Entry ID
	 * @param int    $textWidth Column display width hint
	 * @return string Formatted name portion
	 */
	function formatDataForList($value, $handle="", $entry_id=0, $textWidth=100) {
		return parent::formatDataForList($this->extractNamePart($value), $handle, $entry_id, $textWidth);
	}

	/**
	 * Render the first name field as a plain text input (or a label if disabled).
	 *
	 * @param mixed  $ele_value  Current value (already extracted to the name part by loadValue)
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
		return $this->renderSimpleTextInput($ele_value, $caption, $markupName, $isDisabled);
	}

	/**
	 * Generate JS validation code requiring a non-empty first name.
	 *
	 * @param string    $caption    Field caption (used in the alert message)
	 * @param string    $markupName HTML input name
	 * @param object    $element    The element object (unused)
	 * @param int|mixed $entry_id   Entry ID (unused)
	 * @return void
	 */
	function generateValidationCode($caption, $markupName, $element, $entry_id) {
		$validationCode = array();
		// Todo - add error message to language files
		$validationCode[] = "if(myform.{$markupName}.value =='') {\n alert('Please enter \"'.$caption.'\".'); \n myform.{$markupName}.focus();\n return false;\n }";
	}

}
