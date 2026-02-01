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

class formulizeUserAccountPasswordElement extends formulizeUserAccountElement {

    function __construct() {
			parent::__construct();
			$this->name = "User Account Password";
		}

}

#[AllowDynamicProperties]
class formulizeUserAccountPasswordElementHandler extends formulizeUserAccountElementHandler {

	function create() {
		return new formulizeUserAccountPasswordElement();
	}

	/**
	 * Set up and validate a set of element properties
	 * Focuses on the non ele_value properties that are common to all element types
	 * The ele_value options are handled in the child class, since they are element-type specific
	 * @param array $properties The properties to set on the element object
	 * @return array The processed properties that are ready to set on the element object
	 */
	public function setupAndValidateElementProperties($properties) {

		$properties = parent::setupAndValidateElementProperties($properties);
		$properties['ele_desc']	= _formulize_USERACCOUNT_PWREPEATDESC;
		return $properties;
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
			$form_ele = new XoopsFormLabel(
				$caption,
				""
			);
		} else {
			$config_handler = xoops_gethandler('config');
			$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
			$form_ele = new XoopsFormPassword(
				'',
				$markupName,
				(isset($formulizeConfig['t_width']) ? $formulizeConfig['t_width'] : 30),	//	box width
				(isset($formulizeConfig['t_max']) ? $formulizeConfig['t_max'] : 255),	//	max width
				$ele_value
			);
			$form_ele->setExtra(" onchange=\"javascript:formulizechanged=1;\"");
			$form_ele2 = new XoopsFormPassword(
				'',
				'pw_two',
				(isset($formulizeConfig['t_width']) ? $formulizeConfig['t_width'] : 30),	//	box width
				(isset($formulizeConfig['t_max']) ? $formulizeConfig['t_max'] : 255),	//	max width
				$ele_value
			);
			$form_ele2->setExtra(" onchange=\"javascript:formulizechanged=1;\"");
			$tray = new XoopsFormElementTray('', '<br>');
			$tray->addElement($form_ele);
			$tray->addElement($form_ele2);
			$form_ele = new XoopsFormLabel(
				($entry_id == 'new' ? _formulize_USERACCOUNTPASSWORD_CREATE : _formulize_USERACCOUNTPASSWORD_UPDATE). strtolower(" $caption"),
				trans($tray->render()),
				$markupName
			);
		}
		return $form_ele;
	}

	// this method returns any custom validation code (javascript) that should figure out how to validate this element
	// 'myform' is a name enforced by convention that refers to the form where this element resides
	// use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
	function generateValidationCode($caption, $markupName, $element, $entry_id) {
		$validationCode = array();
		$validationCode[] = "if((myform.{$markupName}.value !='' || myform.pw_two.value !='') && myform.{$markupName}.value != myform.pw_two.value) {\n alert('Your passwords do not match. Please try again.'); \n myform.{$markupName}.focus();\n return false;\n }";
		return $validationCode;
	}

}
