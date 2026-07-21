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
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/phoneElement.php";

class formulizeUserAccountPhoneElement extends formulizeUserAccountElement {

    function __construct() {
			parent::__construct();
      $this->name = "User Account Phone Number";
			$this->userProperty = "profile:2faphone"; // 2FA phone is stored in user profile, not base user object :/
		}

}

#[AllowDynamicProperties]
class formulizeUserAccountPhoneElementHandler extends formulizeUserAccountElementHandler {

	function create() {
		return new formulizeUserAccountPhoneElement();
	}

	/**
	 * Gather data for the admin UI template, including the phone format string.
	 *
	 * @param object|false $element The element object, or false for new elements
	 * @return array Template data array merged with the format value
	 */
	function adminPrepare($element) {
		$parentValues = parent::adminPrepare($element);
		$ele_value = $element ? $element->getVar('ele_value') : array();
		$format = $ele_value['format'] ? $ele_value['format'] : 'XXX-XXX-XXXX';
		return array_merge($parentValues, array('format'=>$format));
	}

	/**
	 * Save admin UI option-tab data (phone format string) back to the element object.
	 *
	 * @param object $element    The element object (modified in place)
	 * @param array  $ele_value  Values from the Options tab (includes 'format' key)
	 * @param bool   $advancedTab True when called from the Advanced tab
	 * @return void
	 */
	function adminSave($element, $ele_value = array(), $advancedTab = false) {
    $element->setVar('ele_value', $ele_value);
  }

	/**
	 * Load the phone number from the user's profile and format it for display.
	 *
	 * Reads the raw digits from the profile_profile table via the parent, then
	 * formats them according to the configured format string (default 'XXX-XXX-XXXX').
	 *
	 * @param object    $element  The element object
	 * @param mixed     $value    Ignored; value is read from the user profile
	 * @param int|mixed $entry_id Entry ID
	 * @return array ele_value array with 'number' and 'format' keys
	 */
	function loadValue($element, $value, $entry_id) {
		$value = parent::loadValue($element, $value, $entry_id);
		$ele_value = $element->getVar('ele_value');
		$ele_value['number'] = $value ? formatPhoneNumber($value, ((isset($ele_value['format']) AND $ele_value['format']) ? $ele_value['format'] : 'XXX-XXX-XXXX')) : '';
		return $ele_value;
	}

	/**
	 * Render the phone number field as a text input with a format placeholder.
	 *
	 * When disabled, renders the formatted number as a read-only label.
	 *
	 * @param array  $ele_value  Array with 'number' (formatted) and 'format' keys
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
		if($isDisabled) {
			$formElement = new xoopsFormLabel($caption, $this->makeValueSafeForReadOnlyDisplay($ele_value['number'], $element->getVar('ele_handle'), $entry_id));
		} else {
			$ele_value['format'] = (isset($ele_value['format']) AND $ele_value['format']) ? $ele_value['format'] : 'XXX-XXX-XXXX';
			$formElement = new xoopsFormText($caption, $markupName, 15, 255, $ele_value['number']); // caption, markup name, size, maxlength, default value, according to the xoops form class
			$formElement->setExtra('placeholder="'.$ele_value['format'].'"');
			$formElement->setExtra(" onchange=\"javascript:formulizechanged=1;\"");
		}
		return $formElement;
	}

	/**
	 * Generate the shared email/phone JS validation code.
	 *
	 * @param string    $caption    Field caption (unused)
	 * @param string    $markupName HTML input name (unused; resolved from element)
	 * @param object    $element    The element object
	 * @param int|mixed $entry_id   Entry ID
	 * @return array Array of JavaScript statement strings
	 */
	function generateValidationCode($caption, $markupName, $element, $entry_id) {
		return formulizeGenerateUserAccountEmailPhoneValidation($element, $entry_id);
	}

	/**
	 * Format the raw stored phone digits for list display using the configured format.
	 *
	 * @param mixed  $value     Raw phone digits from the database
	 * @param string $handle    Element handle (used to look up the format string)
	 * @param int    $entry_id  Entry ID (unused)
	 * @param int    $textWidth Column width hint (unused)
	 * @return string Formatted phone number
	 */
	function formatDataForList($value, $handle="", $entry_id=0, $textWidth=100) {
		$format = 'XXX-XXX-XXXX';
		if ($handle) {
			static $formatCache = array();
			if (!isset($formatCache[$handle])) {
				$meta = formulize_getElementMetaData($handle, true);
				$eleValue = isset($meta['ele_value']) ? $meta['ele_value'] : array();
				$formatCache[$handle] = (isset($eleValue['format']) && $eleValue['format']) ? $eleValue['format'] : 'XXX-XXX-XXXX';
			}
			$format = $formatCache[$handle];
		}
		$this->clickable = false;
		$this->striphtml = true;
		$this->length = 255;
		return parent::formatDataForList($value ? formatPhoneNumber($value, $format) : $value);
	}

	/**
	 * Build a WHERE clause fragment to search by phone number.
	 *
	 * Delegates to an EXISTS subquery against the profile_profile.2faphone column.
	 *
	 * @param string $term       Search term
	 * @param string $operator   SQL operator
	 * @param string $quotes     Quote character(s) for the term
	 * @param string $likebits   LIKE wildcards
	 * @param int    $fid        Form ID (unused)
	 * @param string $tableAlias Alias for the users table in the outer query
	 * @return string SQL WHERE clause fragment
	 */
	function buildSearchWhereClause($term, $operator, $quotes, $likebits, $fid, $tableAlias = 'main') {
		return $this->buildProfileExistsClause('2faphone', $term, $operator, $quotes, $likebits, $tableAlias);
	}

}
