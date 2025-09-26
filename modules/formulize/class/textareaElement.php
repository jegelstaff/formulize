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

// There is a corresponding admin template for this element type in the templates/admin folder

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php"; // you need to make sure the base element class has been read in first!
require_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/textElement.php";

// constants for the keys in ele_value
define('ELE_VALUE_TEXTAREA_DEFAULTVALUE', 0);
define('ELE_VALUE_TEXTAREA_ROWS', 1);
define('ELE_VALUE_TEXTAREA_COLS', 2);
define('ELE_VALUE_TEXTAREA_ASSOCIATED_ELEMENT_ID', 3);
define('ELE_VALUE_TEXTAREA_RICHTEXT', 'use_rich_text');

class formulizeTextareaElement extends formulizeTextElement {

	function __construct() {
		parent::__construct();
		// set different properties last when extending other elements
		$this->name = "Multi-line Textbox";
		$this->alwaysValidateInputs = false; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
		$this->defaultValueKey = ELE_VALUE_TEXTAREA_DEFAULTVALUE; // text and textarea do not share the same default value key :(
	}

	/**
	 * Static function to provide the mcp server with the schema for the properties that can be used with the create_form_element and update_form_element tools
	 * Concerned with the properties for the ele_value property of the element object
	 * Follows the convention of properties used publically (MCP, Public API, etc).
	 * @param bool|int $update True if this is being called as part of building the properties for Updating, as opposed to properties for Creating. Default is false (Creating).
	 * @return string The schema for the properties that can be used with the create_form_element and update_form_element tools
	 */
	public static function mcpElementPropertiesDescriptionAndExamples($update = false) {
		return
"**Element:** Multi-line Textbox (textarea).
**Description:** A multi-line text input field. Useful for addresses, notes, and other longer text inputs. Can be set to provide a rich text editor to the user.
**Properties:**
- defaultValue (optional, string, default value for new entries)
- useRichTextEditor (optional, a 1/0 indicating whether to provide a rich text editor for this field. Default is 0 (no editor). Set to 1 to provide an editor.)
**Examples:**
- A rich text editor box: { useRichTextEditor: 1 }
- A multi-line text box for addresses in Toronto, ON: { defaultValue: 'Toronto, ON' }";
	}

}

#[AllowDynamicProperties]
class formulizeTextareaElementHandler extends formulizeTextElementHandler {

	function __construct($db) {
		$this->db =& $db;
		$this->defaultValueKey = ELE_VALUE_TEXTAREA_DEFAULTVALUE;
		$this->associatedElementKey = ELE_VALUE_TEXTAREA_ASSOCIATED_ELEMENT_ID;
	}

	function create() {
		return new formulizeTextareaElement();
	}

	public function getDefaultEleValue() {
		$config_handler = xoops_gethandler('config');
		$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
		$ele_value = array();
		$ele_value[ELE_VALUE_TEXTAREA_DEFAULTVALUE] = "";
		$ele_value[ELE_VALUE_TEXTAREA_ROWS] = $formulizeConfig['ta_rows'];
		$ele_value[ELE_VALUE_TEXTAREA_COLS] = $formulizeConfig['ta_cols'];
		$ele_value[ELE_VALUE_TEXTAREA_ASSOCIATED_ELEMENT_ID] = 0;
		$ele_value[ELE_VALUE_TEXTAREA_RICHTEXT] = 0;
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
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen=false, $owner=null) {

		$ele_value[ELE_VALUE_TEXTAREA_DEFAULTVALUE] = (isset($ele_value[ELE_VALUE_TEXTAREA_DEFAULTVALUE]) AND $ele_value[ELE_VALUE_TEXTAREA_DEFAULTVALUE]) ? stripslashes($ele_value[ELE_VALUE_TEXTAREA_DEFAULTVALUE]) : "";
		$ele_value[ELE_VALUE_TEXTAREA_DEFAULTVALUE] = interpretTextboxValue($element, $entry_id, $ele_value[ELE_VALUE_TEXTAREA_DEFAULTVALUE]);
		if (!strstr(getCurrentURL(),"printview.php") AND !$isDisabled) {
			if(isset($ele_value[ELE_VALUE_TEXTAREA_RICHTEXT]) AND $ele_value[ELE_VALUE_TEXTAREA_RICHTEXT]) {
				include_once XOOPS_ROOT_PATH."/class/xoopsform/formeditor.php";
				$form_ele = new XoopsFormEditor(
					$caption,
					'CKEditor',
					array("name"=>$markupName, "value"=>$ele_value[ELE_VALUE_TEXTAREA_DEFAULTVALUE])
				);
				$GLOBALS['formulize_CKEditors'][] = $markupName.'_tarea';
			} else {
				$form_ele = new XoopsFormTextArea(
					$caption,
					$markupName,
					$ele_value[ELE_VALUE_TEXTAREA_DEFAULTVALUE],
					$ele_value[ELE_VALUE_TEXTAREA_ROWS],
					$ele_value[ELE_VALUE_TEXTAREA_COLS]
				);
			}
		} else {
			$form_ele = new XoopsFormLabel ($caption, str_replace("\n", "<br>", undoAllHTMLChars($ele_value[ELE_VALUE_TEXTAREA_DEFAULTVALUE], ENT_QUOTES)), $markupName);	// nmc 2007.03.24 - added
		}
		return $form_ele;
	}

	// this method returns any custom validation code (javascript) that should figure out how to validate this element
	// 'myform' is a name enforced by convention that refers to the form where this element resides
	// use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
	function generateValidationCode($caption, $markupName, $element, $entry_id=false) {
		$validationCode = array();
		if (!strstr(getCurrentURL(),"printview.php") AND isset($ele_value[ELE_VALUE_TEXTAREA_RICHTEXT]) AND $ele_value[ELE_VALUE_TEXTAREA_RICHTEXT] AND $element->getVar('ele_required')) {
			$eltname = $markupName;
			$eltmsg = empty($caption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, strip_tags(htmlspecialchars_decode($caption, ENT_QUOTES)));
			$eltmsg = str_replace('"', '\"', stripslashes($eltmsg));
			$validationCode[] = "var getText = CKEditors['".$eltname."_tarea'].getData();\n";
			$validationCode[] = "var StripTag = getText.replace(/(<([^>]+)>)/ig,''); \n";
			$validationCode[] = "if(StripTag=='' || StripTag=='&nbsp;') {\n";
			$validationCode[] = "window.alert(\"{$eltmsg}\");\n CKEditors['".$eltname."_tarea'].focus();\n return false;\n";
			$validationCode[] = "}\n";
		}
		return $validationCode;
	}

	// this method will format a dataset value for display on screen when a list of entries is prepared
	// for standard elements, this step is where linked selectboxes potentially become clickable or not, among other things
	// Set certain properties in this function, to control whether the output will be sent through a "make clickable" function afterwards, sent through an HTML character filter (a security precaution), and trimmed to a certain length with ... appended.
	function formatDataForList($value, $handle="", $entry_id=0, $textWidth=100) {
		$elementObject = $this->get($handle);
		$ele_value = $elementObject->getVar('ele_value');
		if(isset($ele_value[ELE_VALUE_TEXTAREA_RICHTEXT]) AND $ele_value[ELE_VALUE_TEXTAREA_RICHTEXT]) {
			return printSmart(strip_tags(trans($value)), 100); // handle this separately from non-rich text areas
		} else {
			return parent::formatDataForList($value, $handle, $entry_id, $textWidth);
		}
	}

}
