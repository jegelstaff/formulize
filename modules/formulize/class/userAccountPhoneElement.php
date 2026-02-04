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

	// this method would gather any data that we need to pass to the template, besides the ele_value and other properties that are already part of the basic element class
	// it receives the element object and returns an array of data that will go to the admin UI template
	// when dealing with new elements, $element might be FALSE
	// can organize template data into two top level keys, advanced-tab-values and options-tab-values, if there are some options for the element type that appear on the Advanced tab in the admin UI. This requires an additional template file with _advanced.html as the end of the name. Text elements have an example.
	function adminPrepare($element) {
		$ele_value = $element ? $element->getVar('ele_value') : array();
		$format = $ele_value['format'] ? $ele_value['format'] : 'XXX-XXX-XXXX';
		return array('format'=>$format);
	}

	// this method would read back any data from the user after they click save in the admin UI, and save the data to the database, if it were something beyond what is handled in the basic element class
	// this is called as part of saving the options tab.  It receives a copy of the element object immediately prior to it being saved, so the element object will have all its properties set as they would be based on the user's changes in the names & settings tab, and in the options tab (the tabs are saved in order from left to right).
	// the exception is the special ele_value array, which is passed separately from the object (this will contain the values the user set in the Options tab)
	// You can modify the element object in this function and since it is an object, and passed by reference by default, then your changes will be saved when the element is saved.
	// You should return a flag to indicate if any changes were made, so that the page can be reloaded for the user, and they can see the changes you've made here.
	// advancedTab is a flag to indicate if this is being called from the advanced tab (as opposed to the Options tab, normal behaviour). In this case, you have to go off first principals based on what is in $_POST to setup the advanced values inside ele_value (presumably).
	function adminSave($element, $ele_value = array(), $advancedTab = false) {
    $element->setVar('ele_value', $ele_value);
  }

	// this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
	// it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
	// $element is the element object
	// $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
	// $entry_id is the ID of the entry being loaded
	function loadValue($element, $value, $entry_id) {
		$value = parent::loadValue($element, $value, $entry_id);
		$ele_value = $element->getVar('ele_value');
		$ele_value['number'] = formatPhoneNumber($value, ((isset($ele_value['format']) AND $ele_value['format']) ? $ele_value['format'] : 'XXX-XXX-XXXX'));
		return $ele_value;
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
		if($isDisabled) {
			$formElement = new xoopsFormLabel($caption, $ele_value['number']);
		} else {
			$ele_value['format'] = (isset($ele_value['format']) AND $ele_value['format']) ? $ele_value['format'] : 'XXX-XXX-XXXX';
			$formElement = new xoopsFormText($caption, $markupName, 15, 255, $ele_value['number']); // caption, markup name, size, maxlength, default value, according to the xoops form class
			$formElement->setExtra('placeholder="'.$ele_value['format'].'"');
			$formElement->setExtra(" onchange=\"javascript:formulizechanged=1;\"");
		}
		return $formElement;
	}

	// this method returns any custom validation code (javascript) that should figure out how to validate this element
	// 'myform' is a name enforced by convention that refers to the form where this element resides
	// use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
	function generateValidationCode($caption, $markupName, $element, $entry_id) {
		$ele_value = $element->getVar('ele_value');
		$ele_value['format'] = $ele_value['format'] ? $ele_value['format'] : 'XXX-XXX-XXXX';
		$validationCode = array();
		// validate for length of numbers
		$numberOfXs = substr_count($ele_value['format'], 'X');
		$validationCode[] = "if(myform.{$markupName}.value =='') {\n alert('Please enter a phone number.'); \n myform.{$markupName}.focus();\n return false;\n }";
		$validationCode[] = "if(myform.{$markupName}.value.replace(/[^0-9]/g,\"\").length != $numberOfXs && myform.{$markupName}.value.replace(/[^0-9]/g,\"\").length > 0) {\n alert('Please enter a phone number with $numberOfXs digits, ie: ".$ele_value['format']."'); \n myform.{$markupName}.focus();\n return false;\n }";
		return $validationCode;
	}

}
