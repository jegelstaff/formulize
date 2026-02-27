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

class formulizeUserAccountTimezoneElement extends formulizeUserAccountElement {

    function __construct() {
			parent::__construct();
      $this->name = "User Account Timezone";
			$this->userProperty = "profile:timezone";
		}

}

#[AllowDynamicProperties]
class formulizeUserAccountTimezoneElementHandler extends formulizeUserAccountElementHandler {

	function create() {
		return new formulizeUserAccountTimezoneElement();
	}

	// this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
	// it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
	// $element is the element object
	// $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
	// $entry_id is the ID of the entry being loaded
	function loadValue($element, $value, $entry_id) {
		$value = parent::loadValue($element, $value, $entry_id);
		if(!$value) {
			// get the uid for the current entry, if there is one
			// get that user's timezone_default, if there is one
			$dataHandler = new formulizeDataHandler($element->getVar('fid'));
			if($entryUserId = intval($dataHandler->getElementValueInEntry($entry_id, 'formulize_user_account_uid_'.$element->getVar('fid')))) {
				$member_handler = xoops_gethandler('member');
				if($userObject = $member_handler->getUser($entryUserId)) {
					$value = formulize_getIANATimezone($userObject->getVar('timezone_offset'));
				}
			}
		}
		return $value;
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
		$timezones = formulize_getTimezoneList();
		if(!$ele_value) {
			global $xoopsConfig;
			$ele_value = formulize_getIANATimezone($xoopsConfig['default_TZ']);
		}
		$disabled = $isDisabled ? ' disabled="disabled"' : '';
		$html = '<select name="'.$markupName.'" id="'.$markupName.'" onchange="javascript:formulizechanged=1;"'.$disabled.'>';
		foreach($timezones as $tz) {
			$selected = ($tz == $ele_value) ? ' selected="selected"' : '';
			$html .= '<option value="'.htmlspecialchars($tz, ENT_QUOTES).'"'.$selected.'>'.htmlspecialchars($tz, ENT_QUOTES).'</option>';
		}
		$html .= '</select>';
		$form_ele = new XoopsFormLabel($caption, $html, $markupName);
		return $form_ele;
	}

}
