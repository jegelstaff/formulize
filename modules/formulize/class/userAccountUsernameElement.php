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
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/userAccountFirstNameElement.php";

class formulizeUserAccountUsernameElement extends formulizeUserAccountFirstNameElement {

    function __construct() {
			parent::__construct();
			$this->name = "User Account Username";
			$this->userProperty = "login_name";
		}

}

#[AllowDynamicProperties]
class formulizeUserAccountUsernameElementHandler extends formulizeUserAccountFirstNameElementHandler {

		function create() {
			return new formulizeUserAccountUsernameElement();
		}

		/**
		 * Render the username field as a plain text input (or a label if disabled).
		 *
		 * @param mixed  $ele_value  Current username value
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
	 * Generate JS validation code requiring a non-empty unique username.
	 *
	 * Uses an XHR uniqueness check; halts submission until the check returns.
	 *
	 * @param string    $caption    Field caption (used in the uniqueness alert message)
	 * @param string    $markupName HTML input name
	 * @param object    $element    The element object
	 * @param int|mixed $entry_id   Entry ID
	 * @return array Array of JavaScript statement strings
	 */
	function generateValidationCode($caption, $markupName, $element, $entry_id) {
		$validationCode = array();
		// Todo - add error message to language files
		$validationCode[] = "if(myform.{$markupName}.value =='') {\n alert('Please enter a username.'); \n myform.{$markupName}.focus();\n return false;\n }";
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
