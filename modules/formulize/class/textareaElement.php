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
define('ELE_VALUE_TEXTAREA_LIMIT_TYPE',   'limit_type');
define('ELE_VALUE_TEXTAREA_LIMIT_NUMBER', 'limit_number');

require_once XOOPS_ROOT_PATH . "/class/xoopsform/formtextarea.php";

class formulizeTextAreaWithCounter extends XoopsFormTextArea {

	private $_limitType;
	private $_limitNumber;
	private $_markupName;

	function __construct($caption, $markupName, $value, $rows, $cols, $limitType, $limitNumber) {
		parent::__construct($caption, $markupName, $value, $rows, $cols);
		$this->_limitType   = $limitType;
		$this->_limitNumber = intval($limitNumber);
		$this->_markupName  = $markupName;
	}

	function render() {
		$html      = parent::render();
		$limit     = $this->_limitNumber;
		$typeLabel = ($this->_limitType === 'words') ? 'words' : 'characters';
		$counterId = 'fz-counter-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $this->_markupName);
		$nameJs    = str_replace(["'", "\\"], ["\\'", "\\\\"], $this->_markupName);
		$ctrIdJs   = str_replace(["'", "\\"], ["\\'", "\\\\"], $counterId);
		$countFn   = $this->_limitType === 'words'
			? "function(t){return t.trim()===''?0:t.trim().split(/\\s+/).length;}"
			: "function(t){return t.length;}";
		$html .= '<div id="' . htmlspecialchars($counterId, ENT_QUOTES) . '" class="fz-limit-counter fz-limit-green">'
		       . $limit . ' ' . $typeLabel . ' remaining</div>';
		$html .= "<script type='text/javascript'>"
		       . "(function(){"
		       . "var ta=document.getElementsByName('" . $nameJs . "')[0];"
		       . "var ctr=document.getElementById('" . $ctrIdJs . "');"
		       . "var limit=" . $limit . ";"
		       . "var count=" . $countFn . ";"
		       . "var red=Math.max(1,Math.floor(limit*0.10));"
		       . "var orange=Math.max(2,Math.floor(limit*0.20));"
		       . "function update(){if(!ta||!ctr)return;"
		       . "var used=count(ta.value),rem=limit-used;"
		       . "ctr.textContent=rem<0?('" . $typeLabel . " limit: '+(-rem)+' over the limit'):(rem+' " . $typeLabel . " remaining');"
		       . "ctr.className='fz-limit-counter '+(rem<=red?'fz-limit-red':rem<=orange?'fz-limit-orange':'fz-limit-green');"
		       . "}"
		       . "if(ta){ta.addEventListener('input',update);update();}"
		       . "})();</script>";
		return $html;
	}
}

class formulizeTextareaElement extends formulizeTextElement {

	public static $category = "textboxes";

	function __construct() {
		parent::__construct();
		// set different properties last when extending other elements
		$this->name = "Multi-line Textbox";
		$this->alwaysValidateInputs = true; // true so the word/character limit check in generateValidationCode() always runs; the method returns [] when no limit is set, which is falsy and causes elementrenderer to skip setting customValidationCode.
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
- limitType (optional, one of 'none', 'characters', or 'words'. Sets whether there is a character or word limit on the field. Default is 'none'. Not applicable when useRichTextEditor is 1.)
- limitNumber (optional, positive integer. The maximum number of characters or words allowed. Required when limitType is 'characters' or 'words'. Set to 0 or omit for no limit.)
**Examples:**
- A rich text editor box: { useRichTextEditor: 1 }
- A multi-line text box for addresses in Toronto, ON: { defaultValue: 'Toronto, ON' }
- A notes field limited to 200 characters: { limitType: 'characters', limitNumber: 200 }
- A short essay field limited to 500 words: { limitType: 'words', limitNumber: 500 }";
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
		$ele_value[ELE_VALUE_TEXTAREA_LIMIT_TYPE] = 'none';
		$ele_value[ELE_VALUE_TEXTAREA_LIMIT_NUMBER] = 0;
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
				$limitType   = isset($ele_value[ELE_VALUE_TEXTAREA_LIMIT_TYPE])   ? $ele_value[ELE_VALUE_TEXTAREA_LIMIT_TYPE]   : 'none';
				$limitNumber = isset($ele_value[ELE_VALUE_TEXTAREA_LIMIT_NUMBER]) ? intval($ele_value[ELE_VALUE_TEXTAREA_LIMIT_NUMBER]) : 0;
				if ($limitType && $limitType !== 'none' && $limitNumber > 0) {
					$form_ele = new formulizeTextAreaWithCounter(
						$caption,
						$markupName,
						$ele_value[ELE_VALUE_TEXTAREA_DEFAULTVALUE],
						$ele_value[ELE_VALUE_TEXTAREA_ROWS],
						$ele_value[ELE_VALUE_TEXTAREA_COLS],
						$limitType,
						$limitNumber
					);
				} else {
					$form_ele = new XoopsFormTextArea(
						$caption,
						$markupName,
						$ele_value[ELE_VALUE_TEXTAREA_DEFAULTVALUE],
						$ele_value[ELE_VALUE_TEXTAREA_ROWS],
						$ele_value[ELE_VALUE_TEXTAREA_COLS]
					);
				}
			}
		} else {
			$form_ele = new XoopsFormLabel ($caption, formulize_text_to_hyperlink(str_replace("\n", "<br>", undoAllHTMLChars($ele_value[ELE_VALUE_TEXTAREA_DEFAULTVALUE], ENT_QUOTES))), $markupName);	// nmc 2007.03.24 - added
		}
		return $form_ele;
	}

	// this method returns any custom validation code (javascript) that should figure out how to validate this element
	// 'myform' is a name enforced by convention that refers to the form where this element resides
	// use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
	function generateValidationCode($caption, $markupName, $element, $entry_id=false) {
		$validationCode = array();
		$ele_value = $element->getVar('ele_value');
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
		$limitType   = isset($ele_value[ELE_VALUE_TEXTAREA_LIMIT_TYPE])   ? $ele_value[ELE_VALUE_TEXTAREA_LIMIT_TYPE]   : 'none';
		$limitNumber = isset($ele_value[ELE_VALUE_TEXTAREA_LIMIT_NUMBER]) ? intval($ele_value[ELE_VALUE_TEXTAREA_LIMIT_NUMBER]) : 0;
		$isRichText  = isset($ele_value[ELE_VALUE_TEXTAREA_RICHTEXT]) && $ele_value[ELE_VALUE_TEXTAREA_RICHTEXT];
		if (!strstr(getCurrentURL(), "printview.php") && $limitType && $limitType !== 'none' && $limitNumber > 0 && !$isRichText) {
			$typeLabel = ($limitType === 'words') ? 'words' : 'characters';
			$alertMsg  = addslashes('Please reduce your ' . $typeLabel . ' to ' . $limitNumber . ' or fewer before submitting.');
			$nameJs    = str_replace(["'", "\\"], ["\\'", "\\\\"], $markupName);
			$safeVar   = preg_replace('/[^a-zA-Z0-9]/', '_', $markupName);
			$validationCode[] = "var fzTa_{$safeVar}=document.getElementsByName('" . $nameJs . "')[0];";
			$validationCode[] = "if(fzTa_{$safeVar}){";
			if ($limitType === 'words') {
				$validationCode[] = "var fzC_{$safeVar}=fzTa_{$safeVar}.value.trim()===''?0:fzTa_{$safeVar}.value.trim().split(/\\s+/).length;";
			} else {
				$validationCode[] = "var fzC_{$safeVar}=fzTa_{$safeVar}.value.length;";
			}
			$validationCode[] = "if(fzC_{$safeVar}>" . $limitNumber . "){window.alert('" . $alertMsg . "');fzTa_{$safeVar}.focus();return false;}";
			$validationCode[] = "}";
		}
		return $validationCode;
	}

	// this method will format a dataset value for display on screen when a list of entries is prepared
	// for standard elements, this step is where linked selectboxes potentially become clickable or not, among other things
	// Set certain properties in this function, to control whether the output will be sent through a "make clickable" function afterwards, sent through an HTML character filter (a security precaution), and trimmed to a certain length with ... appended.
	function formatDataForList($value, $handle="", $entry_id=0, $textWidth=100) {
		$this->clickable = true;
		$this->striphtml = false;
		$this->length = $textWidth;
		$elementObject = $this->get($handle);
		$ele_value = $elementObject->getVar('ele_value');
		// for rich text with that we're going to cut down, simply remove the tags and return the shortened version
		if($textWidth AND isset($ele_value[ELE_VALUE_TEXTAREA_RICHTEXT]) AND $ele_value[ELE_VALUE_TEXTAREA_RICHTEXT]) {
			return printSmart(strip_tags(trans($value)), $textWidth); // handle this separately from non-rich text areas
		// otherwise, go direct to plain element handler method
		} else {
			return formulizeElementsHandler::formatDataForList($value);
		}
	}

	function prepareDataForSaving($value, $element, $entry_id=null, $subformBlankCounter=null) {
		$ele_value   = $element->getVar('ele_value');
		$limitType   = isset($ele_value[ELE_VALUE_TEXTAREA_LIMIT_TYPE])   ? $ele_value[ELE_VALUE_TEXTAREA_LIMIT_TYPE]   : 'none';
		$limitNumber = isset($ele_value[ELE_VALUE_TEXTAREA_LIMIT_NUMBER]) ? intval($ele_value[ELE_VALUE_TEXTAREA_LIMIT_NUMBER]) : 0;
		$isRichText  = isset($ele_value[ELE_VALUE_TEXTAREA_RICHTEXT]) && $ele_value[ELE_VALUE_TEXTAREA_RICHTEXT];
		if (!$isRichText && $limitType && $limitType !== 'none' && $limitNumber > 0 && $value !== '' && $value !== '{WRITEASNULL}') {
			if ($limitType === 'characters') {
				if (mb_strlen($value) > $limitNumber) {
					$value = mb_substr($value, 0, $limitNumber);
				}
			} elseif ($limitType === 'words') {
				$words = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
				if (count($words) > $limitNumber) {
					$value = implode(' ', array_slice($words, 0, $limitNumber));
				}
			}
		}
		return parent::prepareDataForSaving($value, $element, $entry_id, $subformBlankCounter);
	}

	public function validateEleValuePublicAPIProperties($properties, $ele_value = [], $elementIdentifier = null) {
		$result    = parent::validateEleValuePublicAPIProperties($properties, $ele_value, $elementIdentifier);
		$ele_value = $result['ele_value'];
		$allowed   = ['none', 'characters', 'words'];
		if (isset($properties['limitType'])) {
			$limitType = trim($properties['limitType']);
			$ele_value[ELE_VALUE_TEXTAREA_LIMIT_TYPE] = in_array($limitType, $allowed) ? $limitType : 'none';
		}
		if (isset($properties['limitNumber'])) {
			$limitNumber = intval($properties['limitNumber']);
			$ele_value[ELE_VALUE_TEXTAREA_LIMIT_NUMBER] = $limitNumber > 0 ? $limitNumber : 0;
		}
		return ['ele_value' => $ele_value];
	}

}
