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

class formulizeUserAccountEmailElement extends formulizeUserAccountElement {

    function __construct() {
			parent::__construct();
			$this->name = "User Account Email Address";
			$this->userProperty = "email";
    }

}

#[AllowDynamicProperties]
class formulizeUserAccountEmailElementHandler extends formulizeUserAccountElementHandler {

	function create() {
		return new formulizeUserAccountEmailElement();
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
			$formElement = new xoopsFormLabel($caption, $ele_value);
		} else {
			$formElement = new xoopsFormText($caption, $markupName, 20, 255, $ele_value); // caption, markup name, size, maxlength, default value, according to the xoops form class
			$formElement->setExtra(" onchange=\"javascript:formulizechanged=1;\"");
		}
		return $formElement;
	}

	// this method returns any custom validation code (javascript) that should figure out how to validate this element
	// 'myform' is a name enforced by convention that refers to the form where this element resides
	// use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
	function generateValidationCode($caption, $markupName, $element, $entry_id) {
		$validationCode = array();
		// Todo - add error message to language files
		$validationCode[] = "if(myform.{$markupName}.value =='') {\n alert('Please enter an email address.'); \n myform.{$markupName}.focus();\n return false;\n }";
		$validationCode[] =
		"if(myform.{$markupName}.value != '' && /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,63})+$/.test(myform.{$markupName}.value) == false){
			alert('The email address you have entered is not valid.');
			myform.{$markupName}.focus();
			return false;
		}";
		$eltmsgUnique = empty($caption) ? sprintf( _formulize_REQUIRED_UNIQUE, $markupName ) : sprintf( _formulize_REQUIRED_UNIQUE, $caption );
		$validationCode[] = "if ( myform.{$markupName}.value != '' ) {\n";
		$validationCode[] = "if(\"{$markupName}\" in formulize_xhr_returned_check_for_unique_value && formulize_xhr_returned_check_for_unique_value[\"{$markupName}\"] != 'notreturned') {\n"; // a value has already been returned from xhr, so let's check that out...
		$validationCode[] = "if(\"{$markupName}\" in formulize_xhr_returned_check_for_unique_value && formulize_xhr_returned_check_for_unique_value[\"{$markupName}\"] != 'valuenotfound') {\n"; // request has come back, form has been resubmitted, but the check turned up postive, ie: value is not unique, so we have to halt submission, and reset the check for unique flag so we can check again when the user has typed again and is ready to submit
		$validationCode[] = "window.alert(\"{$eltmsgUnique}\");\n";
		$validationCode[] = "hideSavingGraphic();\n";
		$validationCode[] = "delete formulize_xhr_returned_check_for_unique_value.{$markupName};\n"; // unset this key
		$validationCode[] = "myform.{$markupName}.focus();\n return false;\n";
		$validationCode[] = "}\n";
		$validationCode[] = "} else {\n";	 // do not submit the form, just send off the request, which will trigger a resubmission after setting the returned flag above to true so that we won't send again on resubmission
		$validationCode[] = "\nvar formulize_xhr_params = []\n";
		$validationCode[] = "formulize_xhr_params[0] = myform.{$markupName}.value;\n";
		$validationCode[] = "formulize_xhr_params[1] = ".$element->getVar('ele_id').";\n";
		$xhr_entry_to_send = is_numeric($entry_id) ? $entry_id : "'".$entry_id."'";
		$validationCode[] = "formulize_xhr_params[2] = ".$xhr_entry_to_send.";\n";
		$validationCode[] = "formulize_xhr_params[4] = leave;\n"; // will have been passed in to the main function and we need to preserve it after xhr is done
		$validationCode[] = "formulize_xhr_send('check_for_unique_value', formulize_xhr_params);\n";
		$validationCode[] = "return false;\n";
		$validationCode[] = "}\n";
		$validationCode[] = "}\n";
		return $validationCode;
	}

}
