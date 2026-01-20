<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
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
##  Author of this file: Freeform Solutions and NS Tai (aka tuff) and others ##
##  URL: http://www.brandycoke.com/                                          ##
##  Project: Formulize                                                       ##
###############################################################################

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

class formulizeElementRenderer{
	var $_ele;

	function __construct(&$element){
		$this->_ele =& $element;
	}

	// function params modified to accept passing of $ele_value from index.php
	// $entry added June 1 2006 as part of 'Other' option for radio buttons and checkboxes (now $entry_id)
    // $ele_value is the prepared ele_value that has had the existing value of the element loaded into it as if that were a default!
    // ele_value from the element object is unchanged
	function constructElement($renderedElementMarkupName, $ele_value, $entry_id, $isDisabled=false, $screen=null, $validationOnly=false){
        global $xoopsDB;
        $wasDisabled = false; // yuck, see comment below when this is reassigned
		if (strstr(getCurrentURL(),"printview.php")) {
			$isDisabled = true; // disabled all elements if we're on the printable view
		}
		global $xoopsUser, $xoopsModuleConfig, $separ, $myts;
		$myts = MyTextSanitizer::getInstance();

		if(strstr($renderedElementMarkupName, "de_")) { // display element uses a slightly different element name so it can be distinguished on subsequent page load from regular elements...THIS IS NOT TRUE/NECESSARY ANYMORE SINCE FORMULIZE 3, WHERE ALL ELEMENTS ARE DISPLAY ELEMENTS
			$true_ele_id = str_replace("de_".$this->_ele->getVar('id_form')."_".$entry_id."_", "", $renderedElementMarkupName);
			$displayElementInEffect = true;
		} else {
			$true_ele_id = str_replace("ele_", "", $renderedElementMarkupName);
			$displayElementInEffect = false;
		}


		// added July 6 2005.
		if(!$xoopsModuleConfig['delimeter']) {
			// assume that we're accessing a form from outside the Formulize module, therefore the Formulize delimiter setting is not available, so we have to query for it directly.
			$delimq = q("SELECT conf_value FROM " . $xoopsDB->prefix("config") . ", " . $xoopsDB->prefix("modules") . " WHERE " . $xoopsDB->prefix("modules") . ".mid=" . $xoopsDB->prefix("config") . ".conf_modid AND " . $xoopsDB->prefix("modules") . ".dirname=\"formulize\" AND " . $xoopsDB->prefix("config") . ".conf_name=\"delimeter\"");
			$delimSetting = $delimq[0]['conf_value'];
		} else {
			$delimSetting = $xoopsModuleConfig['delimeter'];
		}

		$customElementHasData = false;
		$id_form = $this->_ele->getVar('id_form');
		$ele_caption = $this->_ele->getVar('ele_caption', 'e');
		$ele_caption = preg_replace('/\{SEPAR\}/', '', $ele_caption);
		// $ele_caption = stripslashes($ele_caption);
		// next line commented out to accomodate passing of ele_value from index.php
		// $ele_value = $this->_ele->getVar('ele_value');
		$ele_type = $this->_ele->getVar('ele_type');


		// call the text sanitizer, first try to convert HTML chars, and if there were no conversions, then do a textarea conversion to automatically make links clickable
		$ele_caption = trans($ele_caption);
		$htmlCaption = htmlspecialchars_decode($myts->undoHtmlSpecialChars($ele_caption)); // do twice, because we need to handle &amp;lt; and other stupid stuff...do first time through XOOPS myts just because it might be doing a couple extra things that are useful...can probably just use PHP's own filter twice, not too big a deal
		if($htmlCaption == $ele_caption) {
        	$ele_caption = $myts->displayTarea($ele_caption);
		} else {
			$ele_caption = $htmlCaption;
		}

		$ele_caption = $this->formulize_replaceReferencesAndVariables($ele_caption, $entry_id, $id_form, $renderedElementMarkupName, $screen);

		// ele_desc added June 6 2006 -- jwe
		$ele_desc = $this->_ele->getVar('ele_desc', "f"); // the f causes no stupid reformatting by the ICMS core to take place
		$helpText = $ele_desc != "" ? $this->formulize_replaceReferencesAndVariables($myts->makeClickable(html_entity_decode($ele_desc,ENT_QUOTES)), $entry_id, $id_form, $renderedElementMarkupName, $screen) : "";

		// determine the entry owner
		if($entry_id != "new") {
					$owner = getEntryOwner($entry_id, $id_form);
		} else {
					$owner = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
		}

		// setup the previous entry UI if necessary -- this is an option that can be specified for certain screens
		$previousEntryUI = "";
		if($screen AND $ele_type != "derived") {
			if($screen->getVar('paraentryform') > 0) {
				$previousEntryUI = $this->formulize_setupPreviousEntryUI($screen, $true_ele_id, $ele_type, $owner, $displayElementInEffect, $entry_id, $this->_ele->getVar('ele_handle'), $this->_ele->getVar('id_form'));
			}
		}

		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($id_form, true); // true includes all elements even if they're not displayed

		switch ($ele_type){

			case 'ib':
				if(trim($ele_value[0]) == "") { $ele_value[0] = $ele_caption; }
				$ele_value[0] = $this->formulize_replaceReferencesAndVariables($ele_value[0], $entry_id, $id_form, $renderedElementMarkupName, $screen, $screen);
				if(strstr($ele_value[0], "\$value=") OR strstr($ele_value[0], "\$value =")) {
					$form_id = $id_form;
					$entryData = gatherDataset($id_form, filter: $entry_id, frid: 0);
					$entry = $entryData[0];
					$creation_datetime = getValue($entry, "creation_datetime");
					$entryData = $entry; // alternate variable name for backwards compatibility
					$ele_value[0] = removeOpeningPHPTag($ele_value[0]);
					$value = ""; // will be set in eval
					$evalResult = eval($ele_value[0]);
					if($evalResult === false) {
						$ele_value[0] = _formulize_ERROR_IN_LEFTRIGHT;
					} else {
						$ele_value[0] = $value; // value is supposed to be the thing set in the eval'd code
						$ele_value[0] = $this->formulize_replaceReferencesAndVariables($ele_value[0], $entry_id, $id_form, $renderedElementMarkupName, $screen); // in case PHP code generated some { } references
					}
				}
				$form_ele = $ele_value; // an array, item 0 is the contents of the break, item 1 is the class of the table cell (for when the form is table rendered)
				break;

			case 'areamodif':
				$ele_value[0] = $this->formulize_replaceReferencesAndVariables($ele_value[0], $entry_id, $id_form, $renderedElementMarkupName, $screen, $screen);
				if(strstr($ele_value[0], "\$value=") OR strstr($ele_value[0], "\$value =")) {
					$form_id = $id_form;
					$entryData = gatherDataset($id_form, filter: $entry_id, frid: 0);
					$entry = $entryData[0];
					$creation_datetime = getValue($entry, "creation_datetime");
					$entryData = $entry; // alternate variable name for backwards compatibility
					$ele_value[0] = removeOpeningPHPTag($ele_value[0]);
					$value = ""; // will be set in eval
					$evalResult = eval($ele_value[0]);
					if($evalResult === false) {
						$ele_value[0] = _formulize_ERROR_IN_LEFTRIGHT;
					} else {
						$ele_value[0] = $value; // value is supposed to be the thing set in the eval'd code
						$ele_value[0] = $this->formulize_replaceReferencesAndVariables($ele_value[0], $entry_id, $id_form, $renderedElementMarkupName, $screen); // just in case PHP might have added { } refs
					}
				}
				$form_ele = new XoopsFormLabel(
					$ele_caption,
					$ele_value[0],
          $renderedElementMarkupName
				);
				$form_ele->setClass("formulize-text-for-display");
			break;

			default:
				if(!file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$ele_type."Element.php")) {
					return false; // element type not found
				}
				$elementTypeHandler = xoops_getmodulehandler($ele_type."Element", "formulize");
				$form_ele = $elementTypeHandler->render($ele_value, $ele_caption, $renderedElementMarkupName, $isDisabled, $this->_ele, $entry_id, $screen, $owner); // $ele_value as passed in here, $caption, name that we use for the element in the markup, flag for whether it's disabled or not, element object, entry id number that this element belongs to, $screen is the screen object that was passed in, if any
				// if form_ele is an array, then we want to treat it the same as an "insertbreak" element, ie: it's not a real form element object
				if(is_object($form_ele)) {
					if(!$isDisabled AND ($this->_ele->getVar('ele_required') OR $this->_ele->alwaysValidateInputs) AND $this->_ele->hasData) { // if it's not disabled, and either a declared required element according to the webmaster, or the element type itself always forces validation...
						$form_ele->customValidationCode = $elementTypeHandler->generateValidationCode($ele_caption, $renderedElementMarkupName, $this->_ele, $entry_id);
					}
					$form_ele->setDescription($helpText);
					$wasDisabled = $isDisabled; // Ack!! see spaghetti code comments with $wasDisabled elsewhere
					$isDisabled = false; // the render method must handle providing a disabled output, so as far as the rest of the logic here goes, the element is not disabled but should be rendered as is
					$baseCustomElementObject = $elementTypeHandler->create();
					if($baseCustomElementObject->hasData) {
						$customElementHasData = true;
					}
				}
				break;
		} // end element-type case

		// if element is object, with data, not disabled, let's get it ready for rendering
		// by rendering everything now, and sticking it in a clean "label" element
		if(is_object($form_ele) AND !$isDisabled AND $this->_ele->hasData) {

			// put in cue if element has data we should be handling on save
			$elementCue = "";
			if(substr($renderedElementMarkupName, 0, 9) != "desubform"
				AND $this->_ele->getVar('ele_type') != "derived"
				AND !$isDisabled
				AND !$wasDisabled
				AND $customElementHasData) {
				$elementCue = "\n<input type=\"hidden\" id=\"decue_".trim($renderedElementMarkupName,"de_")."\" name=\"decue_".trim($renderedElementMarkupName,"de_")."\" value=1>\n";
			}

			// put in special validation logic, if the element has special validation logic
			// hard coded for dara to start with
			$specialValidationLogicDisplay = "";
			if(strstr(getCurrentURL(), 'dara.daniels') AND $true_ele_id == 88) {
					$GLOBALS['formulize_specialValidationLogicHook'][$renderedElementMarkupName] = $true_ele_id;
					$specialValidationLogicDisplay = "&nbsp;&nbsp;<span id='va_".trim($renderedElementMarkupName,"de_")."'></span>";
			}

			$previousEntryUIRendered = $previousEntryUI ? "&nbsp;&nbsp;" . $previousEntryUI->render() : "";

			$form_ele->setExtra(" onchange=\"javascript:formulizechanged=1;\"");
			$form_ele_new = new xoopsFormLabel($form_ele->getCaption(), $form_ele->render().$previousEntryUIRendered.$specialValidationLogicDisplay.$elementCue, $renderedElementMarkupName);

			$form_ele_new->setName($renderedElementMarkupName); // need to set this as the name, in case it is required and then the name will be picked up by any "required" checks that get done and used in the required validation javascript for textboxes
			if(!empty($form_ele->customValidationCode)) {
				$form_ele_new->customValidationCode = $form_ele->customValidationCode;
			}
			if($form_ele->isRequired()) {
				$form_ele_new->setRequired();
			}

		// else if element is an old "classic" element (no custom class) and is disabled (elements with custom class are not disabled at this point because their render method must handle disabling and so the disabled flag is off on those by now)
		} elseif(is_object($form_ele) AND $isDisabled AND $this->_ele->hasData) {
			$form_ele_new = $this->formulize_disableElement($form_ele, $this->_ele->getVar('ele_type'));

		// else form_ele is not an object...and/or has no data.  Happens for IBs and for non-interactive elements, like grids.
		} else {
			if(is_object($form_ele)) {
					$form_ele->formulize_element = $this->_ele;
			}
			return $form_ele;
		}

		// if we haven't returned yet, element was an object with data, so do final prep of the element and return it
		$form_ele_new->formulize_element = $this->_ele;
		if($helpText) {
			$form_ele_new->setDescription($helpText);
		}
		$form_ele_new->setClass($form_ele->getClass());
		return $form_ele_new;
	}

	/**
	 * Replace curly bracket variable references, and also random: and date: sets
	 * @param string $text Text to perform replacements on
	 * @param int $entry_id Entry ID to get values from
	 * @param int $id_form Form ID to get values from
	 * @param string $renderedElementMarkupName Name of the rendered element markup, if any
	 * @return string Text with replacements made
	 */
	function formulize_replaceReferencesAndVariables($text, $entry_id, $id_form, $renderedElementMarkupName='', $screen=null) {
		$text = $this->formulize_replaceCurlyBracketVariables($text, $entry_id, $id_form, $renderedElementMarkupName, $screen);
		$text = formulize_handleRandomAndDateText($text);
		return $text;
	}

  // replace { } terms with element handle values from the current entry, if any exist
	function formulize_replaceCurlyBracketVariables($text, $entry_id, $id_form, $renderedElementMarkupName='', $screen=null) {
		if(strstr($text, "}") AND strstr($text, "{")) {
			$entryData = gatherDataset($id_form, filter: $entry_id, frid: 0);
			$entry = $entryData[0];
      $element_handler = xoops_getmodulehandler('elements', 'formulize');
			$bracketPos = 0;
			$start = true; // flag used to force the loop to execute, even if the 0th position has the {
			while($bracketPos <= strlen($text) AND $bracketPos = strpos($text, "{", $bracketPos) OR $start == true) {
				$start = false;
        $endBracketPos = strpos($text, "}", $bracketPos+1);
				$term = substr($text, $bracketPos+1, $endBracketPos-$bracketPos-1);
				$elementObject = $element_handler->get($term);
				if($elementObject) {
					if(isset($GLOBALS['formulize_asynchronousFormDataInAPIFormat'][$entry_id][$term])) {
						$replacementTerm = $GLOBALS['formulize_asynchronousFormDataInAPIFormat'][$entry_id][$term];
					} else {
           	$replacementTerm = getValue($entry, $term, localEntryId: $entry_id);
					}
					// get the uitext value if necessary
					$replacementTerm = formulize_swapUIText($replacementTerm, $elementObject->getVar('ele_uitext'));
					$replacementTerm = formulize_numberFormat($replacementTerm, $term);
					$text = str_replace("{".$term."}",$replacementTerm,$text);
					$lookAhead = strlen($replacementTerm); // move ahead the length of what we replaced
					if($renderedElementMarkupName) {
						catalogConditionalElement($renderedElementMarkupName,array($elementObject->getVar('ele_handle')), $screen);
					}
				} else {
					$lookAhead = 1;
				}
				$bracketPos = $bracketPos + $lookAhead;
			}
		}
		return $text;
	}

	function formulize_disableElement($element, $type) {
		if($type == "date" OR $type == "colorpick") {
			switch($type) {
				case 'date':
					if($timeval = $element->getValue()) {
						if($timeval == _DATE_DEFAULT OR $timeval == '0000-00-00' OR !$timeval) {
							$hiddenValue = "";
						} else {
							$timeval = is_numeric($timeval) ? $timeval : strtotime($timeval);
							$hiddenValue = date(_SHORTDATESTRING, $timeval);
						}
					} else {
						$hiddenValue = "";
					}
					break;
				default:
					// should work for all elements, since non-textbox type elements where the value would not be passed straight back, are handled differently at the time they are constructed
          $hiddenValue = $element->getValue();
			}
			if(is_array($hiddenValue)) { // not sure when/if this would ever happen
				$newElement = new xoopsFormLabel($element->getCaption(), implode(", ", $hiddenValue));
			} else {
				$newElement = new xoopsFormLabel($element->getCaption(), $hiddenValue);
			}
      $newElement->setName($element->getName());
			return $newElement;
		} else {
			return $element;
		}
	}


	// this function creates the previous values drop down that people can use to set the value of an element
	// screen is the screen object with the data we need (form id with previous entries and rule for lining them up with current form)
	// element_id is the ID of the element we're drawing (add ele_ to the front to make the javascript ID we need to know in order to set the value of the element to the one the user selects)
	// type is the type of element, which affects how the javascript is written (textboxes aren't set the same as radio buttons, etc)
	function formulize_setupPreviousEntryUI($screen, $element_id, $type, $owner, $de, $entry_id, $ele_handle, $fid) {

		// 1. need to get and cache the values of the entry for this screen
		// 2. need to put the values into a dropdown list with an onchange event that populates the actual form element
		// this should be cached in some other way, since every instance of the renderer will need to cache this.  If it were a GLOBAL or this whole thing were in some other function, that would work.
		static $cachedEntries = array();
		if(!isset($cachedEntries[$screen->getVar('sid')])) {
			// identify the entry belonging to this user's group(s) in the other form.  Currently only group correspondence is supported.
			global $xoopsUser;
			$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
			$member_handler =& xoops_gethandler('member');
			$gperm_handler =& xoops_gethandler('groupperm');
			$mid = getFormulizeModId();
			$owner_groups =& $member_handler->getGroupsByUser($owner, FALSE); // in this particular case, it's okay to make the owner_groups based on the users's memberships, since we want to present the single entry that belongs to whichever groups the user is a member of...I think.  :-)
			$singleData = getSingle($screen->getVar('paraentryform'), $owner, $owner_groups, $member_handler, $gperm_handler, $mid);
			if($singleData['flag'] == "group" AND $singleData['entry'] > 0) { // only proceed if there is a one-entry-per-group situation in the target form
				formulize_benchmark("Ready to do previous entry query.");
				$cachedEntries[$screen->getVar('sid')] = gatherDataset($screen->getVar('paraentryform'), filter: $singleData['entry'], frid: 0);
				formulize_benchmark("Done query.");
			} else {
				return "";
			}
		}
		$entries = $cachedEntries[$screen->getVar('sid')];

		// big assumption below is corresponding captions.  In future there will be more ad hoc ways of describing which elements align to which other ones.
		// 1. figure out the corresponding element ID based on matching captions
		// 2. grab the previous value from the $entry/entries
		// 3. create the dropdown list with these values, including javascript

		$formHandler =& xoops_getmodulehandler('forms', 'formulize');
		$currentForm = $formHandler->get($screen->getVar('fid'));
		$previousForm = $formHandler->get($screen->getVar('paraentryform'));
		$currentCaptions = $currentForm->getVar('elementCaptions');
		$captionToMatch = $currentCaptions[$ele_handle];
		$previousCaptions = $previousForm->getVar('elementCaptions');
		$previousElementHandle = array_search($captionToMatch, $previousCaptions);
		if(!$previousElementHandle) { return ""; }
		$elementName = $de ? "de_".$fid."_".$entry_id."_".$element_id : "ele_".$element_id;
		$previousElementId = formulize_getIdFromElementHandle($previousElementHandle); // function is in extract.php
		// setup the javascript based on the type of question, and setup other data that is required
		switch($type) {
			case "text":
			case "date":
				$javascript = "onchange='javascript:this.form.".$elementName.".value=this.form.prev_".$element_id.".value;'";
				break;
			case "radio":
				// need to get the options of the question so we know what to match
				$prevElementMetaData = formulize_getElementMetaData($previousElementId); // use this function in extract instead of the get element method in handler, since this is guaranteed to be already be cached in memory
				$prevElement_ele_value = unserialize($prevElementMetaData['ele_value']);
				$prevElementOptions = array_keys($prevElement_ele_value);
				$javascript = "onchange='javascript:if(this.form.prev_".$element_id.".value !== \"\") { this.form.".$elementName."[this.form.prev_".$element_id.".value].checked=true; }'";
				break;
			case "yn":
				$javascript = "onchange='javascript:if(this.form.prev_".$element_id.".value !== \"\") { this.form.".$elementName."[this.form.prev_".$element_id.".value].checked=true; }'";
				break;
		}
		$previousOptions = array();
		$prevOptionsExist = false;
		foreach($entries as $id=>$entry) {
			$value = htmlspecialchars(strip_tags(getValue($entry, $previousElementHandle)));
			if(is_array($value)) {
				$value = printSmart(implode(", ", $value));
			}
			if(trim($value) === "" OR trim($value) == "0000-00-00") { continue; }
			$prevOptionsExist = true;
			switch($type) {
				case "text":
				case "date":
					$previousOptions[$value] = $value;
					break;
				case "radio":
					$prevElementPosition = array_search($value, $prevElementOptions); // need to figure out which option matches the text of the value
					if($prevElementPosition !== false) {
						$previousOptions[$prevElementPosition] = $value; // for radio buttons, we need to pass the position of the option
					}
					break;
				case "yn":
					if($value == _formulize_TEMP_QYES) {
						$previousOptions[0] = $value;
					} elseif($value == _formulize_TEMP_QNO) {
						$previousOptions[1] = $value;
					}
					break;

			}
		}
		if(!$prevOptionsExist) { return ""; }
		$prevUI = new xoopsFormSelect('', 'prev_'.$element_id, '123qweasdzxc', 1, false); // 123qweasdzxc is meant to be a unique value that will never be selected, since we don't ever want a previous selection showing by default
		$prevUI->addOption('', _AM_FORMULIZE_PREVIOUS_OPTION);
		$prevUI->addOptionArray($previousOptions);
		$prevUI->setExtra($javascript);
		return $prevUI;
	}

}

// THIS FUNCTION COPIED FROM LIASE 1.26, onchange control added
// JWE -- JUNE 1 2006
function optOther($s, $id, $entry_id, $counter, $checkbox=false, $isDisabled=false){
    static $blankSubformCounters = array();
    global $xoopsModuleConfig, $xoopsDB;
    if( !is_string($s) OR !preg_match('/\{OTHER\|+[0-9]+\}/', $s) ){
        return false;
    }
    // deal with displayElement elements...
    $id_parts = explode("_", $id);
    /* // displayElement elements will be in the format de_{id_req}_{ele_id} (deh?)
    // regular elements will be in the format ele_{ele_id}
    if(count((array) $id_parts) == 3) {
        $ele_id = $id_parts[2];
    } else {
        $ele_id = $id_parts[1];
    }*/
    // NOW, in Formulize 3, id_parts[3] will always be the element id. :-)
    $ele_id = $id_parts[3];

    // gather the current value if there is one
    $other_text = "";
    if(is_numeric($entry_id)) {
        $otherq = q("SELECT other_text FROM " . $xoopsDB->prefix("formulize_other") . " WHERE id_req='$entry_id' AND ele_id='$ele_id' LIMIT 0,1");
        $other_text = $otherq[0]['other_text'];
    }
    if(strstr($_SERVER['PHP_SELF'], "formulize/printview.php") OR $isDisabled) {
        return $other_text;
    }
    $s = explode('|', preg_replace('/[\{\}]/', '', $s));
    $len = !empty($s[1]) ? $s[1] : $xoopsModuleConfig['t_width'];
    if($entry_id == "new") {
        $blankSubformCounters[$ele_id] = isset($blankSubformCounters[$ele_id]) ? $blankSubformCounters[$ele_id] + 1 : 0;
        $blankSubformCounter = $blankSubformCounters[$ele_id];
        $otherKey = 'ele_'.$ele_id.'_'.$entry_id.'_'.$blankSubformCounter;
    } else {
        $otherKey = 'ele_'.$ele_id.'_'.$entry_id;
    }
    $box = new XoopsFormText('', 'other['.$otherKey.']', $len, 255, $other_text);
    if($checkbox) {
        $box->setExtra("onchange=\"javascript:formulizechanged=1;\" onkeydown=\"javascript:if(this.value != ''){this.form.elements['" . $id . "[]'][$counter].checked = true;}\"");
    } else {
        $box->setExtra("onchange=\"javascript:formulizechanged=1;\" onkeydown=\"javascript:if(this.value != ''){this.form." . $id . "[$counter].checked = true;}\"");
    }
    return $box->render();
}

/**
 * Convert some raw value from a user entered record in the database, into an array of entry ids
 *
 * @param string $dbValue The raw value being converted
 * @return array An array of the entry ids represented in the string
 */
function convertEntryIdsFromDBToArray($dbValue) {
	// isolate the values selected in this entry, and then use those as an entry id filter
	if(strstr($dbValue, '*=+*:')) {
		$directLimit = explode("*=+*:",trim($dbValue, "*=+*:"));
		foreach($directLimit as $v) {
			$directLimit[] = intval($v);
		}
	} elseif(strstr($dbValue, ',') AND is_numeric(str_replace(',','',$dbValue))) { // if it has commas, but if you remove them then it's numbers...
		$directLimit = explode(",",trim($dbValue, ","));
	} else {
		$directLimit = array(intval($dbValue));
	}
	return $directLimit;
}
