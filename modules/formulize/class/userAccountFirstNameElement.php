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

	// this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
	// it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
	// $element is the element object
	// $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
	// $entry_id is the ID of the entry being loaded
	function loadValue($element, $value, $entry_id) {
		$value = parent::loadValue($element, $value, $entry_id);
		$nameParts = explode(" ", trim($value));
		$elementTypeName = strtolower(str_ireplace(['formulizeUserAccount', 'ElementHandler'], "", static::class));
		if($elementTypeName != 'lastname') {
			$value = $nameParts[0];
		} elseif(count($nameParts) > 1) {
			unset($nameParts[0]);
			$value = implode(" ", $nameParts);
		} else {
			$value = $nameParts[0];
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
		if(is_array($ele_value)) {
			$ele_value = "";
		}
		if($isDisabled) {
			$form_ele = new XoopsFormLabel(
				$caption,
				$ele_value
			);
		} else {
			$config_handler = xoops_gethandler('config');
			$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
			$form_ele = new XoopsFormText(
				$caption,
				$markupName,
				(isset($formulizeConfig['t_width']) ? $formulizeConfig['t_width'] : 30),	//	box width
				(isset($formulizeConfig['t_max']) ? $formulizeConfig['t_max'] : 255),	//	max width
				$ele_value,	//	value
				false,		// autocomplete in browser
				'text'		// numbers only
			);
			$form_ele->setExtra(" onchange=\"javascript:formulizechanged=1;\"");
		}
		return $form_ele;
	}

	// this method returns any custom validation code (javascript) that should figure out how to validate this element
	// 'myform' is a name enforced by convention that refers to the form where this element resides
	// use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
	function generateValidationCode($caption, $markupName, $element, $entry_id) {
		$validationCode = array();
		// Todo - add error message to language files
		$validationCode[] = "if(myform.{$markupName}.value =='') {\n alert('Please enter \"'.$caption.'\".'); \n myform.{$markupName}.focus();\n return false;\n }";
	}

}
