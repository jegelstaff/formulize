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
		if($this->_ele->readOnly) {
			$isDisabled = true; // code-level read-only override, treated the same as disabled throughout the pipeline
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
		$processedHelpText = $ele_desc != "" ? $myts->makeClickable(html_entity_decode($ele_desc,ENT_QUOTES)) : "";
		// decide whether this help text is admin-authored PHP code BEFORE any {ref} handling happens
		// (using the raw, not-yet-substituted text), so a referenced field's stored value can never
		// trigger evalPHPStrings() on its own just by happening to contain literal PHP open/close tags
		$helpTextIsPHPCode = (strstr($processedHelpText, "<?php") !== false AND strstr($processedHelpText, "?>") !== false);
		if($helpTextIsPHPCode) {
			// Only the embedded PHP region is code - the text around it is ordinary display text.
			// evalPHPStrings binds the references inside the code region, and the substitution afterwards
			// handles the references in the surrounding display text as well as any the code itself produced.
			$helpText = $this->evalPHPStrings($processedHelpText, $entry_id, $id_form, $renderedElementMarkupName, $screen);
			$helpText = $this->formulize_replaceReferencesAndVariables($helpText, $entry_id, $id_form, $renderedElementMarkupName, $screen);
		} else {
			$helpText = $processedHelpText != "" ? $this->formulize_replaceReferencesAndVariables($processedHelpText, $entry_id, $id_form, $renderedElementMarkupName, $screen) : "";
		}

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

			// NB: the former 'ib' and 'areamodif' display element types are now the class-based
			// 'fullWidthContent' and 'captionedContent' element types, handled by the default case below.

			default:
				if(!file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$ele_type."Element.php")) {
					return false; // element type not found
				}
				$elementTypeHandler = xoops_getmodulehandler($ele_type."Element", "formulize");
				$form_ele = $elementTypeHandler->render($ele_value, $ele_caption, $renderedElementMarkupName, $isDisabled, $this->_ele, $entry_id, $screen, $owner); // $ele_value as passed in here, $caption, name that we use for the element in the markup, flag for whether it's disabled or not, element object, entry id number that this element belongs to, $screen is the screen object that was passed in, if any
				// if form_ele is an array, then we want to treat it the same as an "insertbreak" element, ie: it's not a real form element object
				if(is_object($form_ele)) {
					if(!$isDisabled
						AND (
							$this->_ele->getVar('ele_required')
							OR $this->_ele->alwaysValidateInputs
						)
						AND method_exists($elementTypeHandler, 'generateValidationCode')
						AND $customValidationCode = $elementTypeHandler->generateValidationCode($ele_caption, $renderedElementMarkupName, $this->_ele, $entry_id)
					) { // if it's not disabled, and either a declared required element according to the webmaster, or the element type itself always forces validation...
						$form_ele->customValidationCode = $customValidationCode;
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
		if(is_object($form_ele) AND !$isDisabled AND ($this->_ele->hasData OR $this->_ele->isUserAccountElement OR $this->_ele->isGroupTableElement)) {

			// put in cue if element has data we should be handling on save
			$elementCue = "";
			if(substr($renderedElementMarkupName, 0, 9) != "desubform"
				AND $this->_ele->getVar('ele_type') != "derived"
				AND !$isDisabled
				AND !$wasDisabled
				AND ($customElementHasData OR $this->_ele->isUserAccountElement OR $this->_ele->isGroupTableElement)) {
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
	 * @param mixed $screen The screen object, if any
	 * @return string Text with replacements made
	 */
	function formulize_replaceReferencesAndVariables($text, $entry_id, $id_form, $renderedElementMarkupName='', $screen=null) {
		$text = $this->formulize_replaceCurlyBracketVariables($text, $entry_id, $id_form, $renderedElementMarkupName, $screen);
		$text = formulize_handleRandomAndDateText($text);
		return $text;
	}

	/**
	 * Resolve a single {element_handle} reference to the value that should stand in for it.
	 *
	 * Shared by the plain display substitution (formulize_replaceCurlyBracketVariables) and the
	 * PHP binding path (bindReferencesForPHPEval), so a reference resolves to exactly the same value
	 * whether it is being substituted into display text or bound to a variable for eval'd code.
	 *
	 * @param string $term The handle inside the curly brackets
	 * @param array $entry The entry data to read the value from
	 * @param int|string $entry_id The entry id in effect
	 * @param string $renderedElementMarkupName Markup name, when this is a live render (drives cataloguing)
	 * @param mixed $screen The screen object, if any
	 * @param object $element_handler The elements handler
	 * @return array array($found, $value) - $found is false when the term is not a real element
	 */
	protected function resolveReferenceValue($term, $entry, $entry_id, $renderedElementMarkupName, $screen, $element_handler) {
		$elementObject = $element_handler->get($term);
		if(!$elementObject) {
			return array(false, null);
		}
		if(isset($GLOBALS['formulize_asynchronousFormDataInAPIFormat'][$entry_id][$term])) {
			$replacementTerm = $GLOBALS['formulize_asynchronousFormDataInAPIFormat'][$entry_id][$term];
		} else {
			$replacementTerm = getValue($entry, $term, localEntryId: $entry_id);
		}
		// get the uitext value if necessary
		$replacementTerm = formulize_swapUIText($replacementTerm, $elementObject->getVar('ele_uitext'));
		$replacementTerm = formulize_numberFormat($replacementTerm, $term);
		if($renderedElementMarkupName) {
			catalogConditionalElement($renderedElementMarkupName, array($elementObject->getVar('ele_handle')), $screen);
		}
		return array(true, $replacementTerm);
	}

  // replace { } terms with element handle values from the current entry, if any exist
  // NB: this is the plain display substitution. Code that is about to be eval()'d must NOT use this
  // to put values into itself - use bindReferencesForPHPEval() instead, which supplies the values as
  // runtime variables rather than as text spliced into the code.
	function formulize_replaceCurlyBracketVariables($text, $entry_id, $id_form, $renderedElementMarkupName='', $screen=null) {
		if(strstr($text, "}") AND strstr($text, "{")) {
			// a new entry has no saved data to gather - its references resolve to '' (except async
			// submitted values, which resolveReferenceValue reads from the global before the entry)
			$entry = array();
			if($entry_id AND $entry_id !== 'new') {
				$entryData = gatherDataset($id_form, filter: $entry_id, frid: 0);
				$entry = isset($entryData[0]) ? $entryData[0] : array();
			}
      $element_handler = xoops_getmodulehandler('elements', 'formulize');
			$bracketPos = 0;
			$start = true; // flag used to force the loop to execute, even if the 0th position has the {
			while($bracketPos <= strlen($text) AND $bracketPos = strpos($text, "{", $bracketPos) OR $start == true) {
				$start = false;
        $endBracketPos = strpos($text, "}", $bracketPos+1);
				$term = substr($text, $bracketPos+1, $endBracketPos-$bracketPos-1);
				list($found, $replacementTerm) = $this->resolveReferenceValue($term, $entry, $entry_id, $renderedElementMarkupName, $screen, $element_handler);
				if($found) {
					// getValue returns an array when the element has more than one value in the entry (see
					// getValue in extract.php). This is text being assembled for display, so the values are
					// rendered as a list. The PHP binding path deliberately does not do this - code that
					// references a multi-value element receives the actual array and can work with it.
					// Highly unlikely there would be an array, but anyway...
					$replacementTerm = is_array($replacementTerm) ? implode(", ", $replacementTerm) : (string)$replacementTerm;
					$text = str_replace("{".$term."}",$replacementTerm,$text);
					$lookAhead = strlen($replacementTerm); // move ahead the length of what we replaced
				} else {
					$lookAhead = 1;
				}
				$bracketPos = $bracketPos + $lookAhead;
			}
		}
		return $text;
	}

	/**
	 * Prepare admin-authored PHP code for eval() by binding its {element_handle} references to
	 * variables, instead of splicing the referenced values into the code as text.
	 *
	 * The returned code opens with a preamble that assigns each referenced element's value to a
	 * uniquely named variable, read out of the values array that is returned alongside it. The
	 * caller must make that array available to the eval'd scope under the name $__formulizeRefValues.
	 * Because the values travel as data and are never rendered into the code string, a value entered
	 * by a non-admin user cannot alter the structure of the admin's code - the same property that
	 * makes derived value formulas safe.
	 *
	 * @param string $code The admin-authored PHP code, with any opening PHP tag already removed
	 * @param int|string $entry_id The entry id in effect
	 * @param int $form_id The form id the references belong to
	 * @param string $renderedElementMarkupName Markup name, when this is a live render (drives cataloguing)
	 * @param mixed $screen The screen object, if any
	 * @param array $entry Optional. The already gathered entry data, to save gathering it again
	 * @return array array($codeToEval, $refValues) - pass $refValues into the eval'd scope as
	 *   $__formulizeRefValues
	 */
	function bindReferencesForPHPEval($code, $entry_id, $form_id, $renderedElementMarkupName='', $screen=null, $entry=null) {
		if(strpos($code, '{') === false OR strpos($code, '}') === false) {
			return array($code, array());
		}
		if($entry === null) {
			$entry = array();
			if($entry_id AND $entry_id !== 'new') { // a new entry has no saved data to gather
				$entryData = gatherDataset($form_id, filter: $entry_id, frid: 0);
				$entry = isset($entryData[0]) ? $entryData[0] : array();
			}
		}
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		// resolve each reference once, the first time the transform asks whether it is a real element,
		// so the values are ready to hand out and each element is only looked up and catalogued once
		$resolved = array();
		$isKnownHandle = function($handle) use (&$resolved, $entry, $entry_id, $renderedElementMarkupName, $screen, $element_handler) {
			if(!array_key_exists($handle, $resolved)) {
				list($found, $value) = $this->resolveReferenceValue($handle, $entry, $entry_id, $renderedElementMarkupName, $screen, $element_handler);
				$resolved[$handle] = $found ? array($value) : false;
			}
			return $resolved[$handle] !== false;
		};
		list($codeToEval, $bindings) = formulize_bindCurlyReferencesInPHPCode($code, $isKnownHandle);
		if(empty($bindings)) {
			return array($codeToEval, array());
		}
		// build the preamble and the values array. Variable names are generated from [a-zA-Z0-9_] only
		// (see formulize_bindCurlyReferencesInPHPCode), so using them as array keys in the generated
		// code introduces no text that could alter the code's structure.
		$refValues = array();
		$preamble = '';
		foreach($bindings as $handle => $variableName) {
			$refValues[$variableName] = $resolved[$handle][0];
			$preamble .= '$'.$variableName.' = $__formulizeRefValues[\''.$variableName.'\'];'."\n";
		}
		return array($preamble.$codeToEval, $refValues);
	}

	/**
	 * Evaluate an admin-authored PHP snippet that may contain {element_handle} references.
	 *
	 * This is the canonical way to run admin-authored PHP - textbox default values, static content
	 * elements, help text, and anything added later should all go through here rather than assembling
	 * the sequence themselves. The steps have to happen in a particular order to be both correct and
	 * safe, and every caller that builds its own sequence is somewhere for them to drift apart:
	 *   - strip any opening PHP tag
	 *   - resolve [random:...] and [date:...] tags in the admin's code. These produce dates and choose
	 *     between admin-authored options, so unlike entry data they are not user input.
	 *   - bind {element_handle} references to variables instead of splicing their values into the code
	 *     as text, so entry data can never alter the code's structure (see bindReferencesForPHPEval)
	 *   - make any extra context variables available to the code
	 *   - evaluate it, then resolve any { } references the code itself produced
	 *
	 * The code is given the same entry context the previous hand-rolled versions of this made available:
	 * $form_id, $entry_id, $entry, and the legacy aliases $id_form, $entryData and $creation_datetime.
	 * Anything beyond that is passed in by the caller via $extraScope.
	 *
	 * @param string $code The admin-authored PHP code
	 * @param int|string $entry_id The entry id in effect
	 * @param int $form_id The form id the references belong to
	 * @param string $markupName Markup name, when this is a live render (drives cataloguing)
	 * @param mixed $screen The screen object, if any
	 * @param string $resultVariable The name of the variable the code is expected to set (eg: 'default'
	 *   or 'value'). Pass null to use the code's return value as the result instead.
	 * @param array $extraScope Additional variables to make available to the code, keyed by variable
	 *   name. Names must not begin with __formulize, which is reserved for this method's internals.
	 * @param bool $substituteResultReferences Whether to resolve { } references the code itself produced.
	 *   Pass false when the caller runs the result through a substitution pass of its own anyway, so the
	 *   result is not processed twice.
	 * @return array array($result, $succeeded) - $succeeded is false if the code could not be run, or
	 *   signalled an error by returning false. $result is null when $succeeded is false, so callers
	 *   can substitute whatever error text is appropriate for where the content appears.
	 */
	function evalAdminPHPWithReferences($code, $entry_id, $form_id, $markupName='', $screen=null, $resultVariable=null, $extraScope=array(), $substituteResultReferences=true) {
		// everything this method needs after the eval is held in __formulize prefixed variables, so that
		// a caller's extra scope cannot clobber it on its way into the eval'd code
		$__formulizeEntryId = $entry_id;
		$__formulizeFormId = $form_id;
		$__formulizeMarkupName = $markupName;
		$__formulizeScreen = $screen;
		$__formulizeResultVariable = $resultVariable;
		$__formulizeSubstituteResult = $substituteResultReferences;
		$__formulizeCode = formulize_handleRandomAndDateText(removeOpeningPHPTag($code));
		// gathered once here and handed to the binding, so the entry is only read from the database once.
		// A new entry has no saved data, so nothing is gathered and its references resolve to ''
		$entryData = array();
		if($entry_id AND $entry_id !== 'new') {
			$__formulizeGatheredData = gatherDataset($form_id, filter: $entry_id, frid: 0);
			$entryData = isset($__formulizeGatheredData[0]) ? $__formulizeGatheredData[0] : array();
		}
		list($__formulizeCode, $__formulizeRefValues) = $this->bindReferencesForPHPEval($__formulizeCode, $entry_id, $form_id, $markupName, $screen, $entryData);
		// the entry context the admin's code is entitled to expect
		$creation_datetime = getValue($entryData, "creation_datetime");
		$entry = $entryData; // legacy alias
		$id_form = $form_id; // legacy alias
		// textbox default code could always see $xoopsUser, because the eval used to happen inline in a
		// function that had declared it global. Taken as a copy rather than with a global declaration, so
		// that code reassigning $xoopsUser cannot write back over the real global.
		$xoopsUser = isset($GLOBALS['xoopsUser']) ? $GLOBALS['xoopsUser'] : null;
		if($__formulizeResultVariable !== null) {
			$$__formulizeResultVariable = '';
		}
		foreach($extraScope as $__formulizeScopeName => $__formulizeScopeValue) {
			$$__formulizeScopeName = $__formulizeScopeValue;
		}
		try {
			// $__formulizeRefValues supplies the referenced values to the preamble the binding produced
			$__formulizeEvalReturn = eval($__formulizeCode);
		} catch (\Throwable $__formulizeError) {
			// A parse or runtime error in the admin's code is logged and reported as a failure, rather than
			// being left to take down the whole page with no record of what went wrong. The keys match the
			// module's exception handler (formulize_exception_handler in include/common.php) so these sit
			// alongside other PHP errors in the log, with the element, form and entry added so it is
			// possible to tell which piece of admin code was responsible.
			writeToFormulizeLog(array(
				'formulize_event' => 'PHP-error-recorded',
				'PHP_error_number' => $__formulizeError->getCode(),
				'PHP_error_string' => 'Error in admin authored PHP code: '.$__formulizeError->getMessage(),
				'PHP_error_file' => $__formulizeError->getFile(),
				'PHP_error_errline' => $__formulizeError->getLine(),
				'element_id' => $this->_ele ? $this->_ele->getVar('ele_id') : '',
				'form_id' => $__formulizeFormId,
				'entry_id' => $__formulizeEntryId,
				'screen_id' => $__formulizeScreen ? $__formulizeScreen->getVar('sid') : '',
			));
			return array(null, false);
		}
		if($__formulizeEvalReturn === false) { // long standing convention: returning false signals an error
			return array(null, false);
		}
		$__formulizeResult = $__formulizeResultVariable !== null ? $$__formulizeResultVariable : $__formulizeEvalReturn;
		if($__formulizeSubstituteResult) { // the code may itself have produced { } references
			$__formulizeResult = $this->formulize_replaceReferencesAndVariables($__formulizeResult, $__formulizeEntryId, $__formulizeFormId, $__formulizeMarkupName, $__formulizeScreen);
		}
		return array($__formulizeResult, true);
	}

	function formulize_disableElement($element, $type) {
		// resolve custom/derived element types to date or colorpick if they're based on those, so subclasses are disabled the same way
		$type = formulize_resolveEleType($type, array("date", "colorpick"));
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
		// Radio buttons and everything that extends them (yn, and any custom radio-based type)
		// are all set by checking the option at a given position, so they are handled together.
		// Which position a given value corresponds to differs between them though - a plain
		// radio matches on the option text, while a yes/no element stores codes - so that part
		// is delegated to the element type, via previousEntryOptionKey().
		$isRadioType = anyRadioElementType($type);
		$typeHandler = $isRadioType ? xoops_getmodulehandler($type."Element", "formulize") : null;
		// types that are simply given the previous value. Resolving means subclasses of the text
		// element (textarea, number) are prefilled the same way the text element is.
		$simpleValueType = formulize_resolveEleType($type, array("text", "date"));
		$prevElement_ele_value = array();
		$javascript = ""; // stays empty for element types that the previous entry UI does not support
		// setup the javascript based on the type of question, and setup other data that is required
		if($isRadioType) {
			// need to get the options of the question so we know what to match
			$prevElementMetaData = formulize_getElementMetaData($previousElementId); // use this function in extract instead of the get element method in handler, since this is guaranteed to be already be cached in memory
			$prevElement_ele_value = unserialize($prevElementMetaData['ele_value']);
			$javascript = "onchange='javascript:if(this.form.prev_".$element_id.".value !== \"\") { this.form.".$elementName."[this.form.prev_".$element_id.".value].checked=true; }'";
		} else {
			switch($simpleValueType) {
				case "text":
				case "date":
					$javascript = "onchange='javascript:this.form.".$elementName.".value=this.form.prev_".$element_id.".value;'";
					break;
			}
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
			if($isRadioType) {
				// for radio buttons, we need to pass the position of the option, not the value
				$prevElementPosition = $typeHandler->previousEntryOptionKey($value, $prevElement_ele_value);
				if($prevElementPosition !== false) {
					$previousOptions[$prevElementPosition] = $value;
				}
			} else {
				switch($simpleValueType) {
					case "text":
					case "date":
						$previousOptions[$value] = $value;
						break;
				}
			}
		}
		if(!$prevOptionsExist) { return ""; }
		$prevUI = new xoopsFormSelect('', 'prev_'.$element_id, '123qweasdzxc', 1, false); // 123qweasdzxc is meant to be a unique value that will never be selected, since we don't ever want a previous selection showing by default
		$prevUI->addOption('', _AM_FORMULIZE_PREVIOUS_OPTION);
		$prevUI->addOptionArray($previousOptions);
		$prevUI->setExtra($javascript);
		return $prevUI;
	}

	/**
	 * Look for <?php ?> in a string and eval the PHP code within it
	 * @param string $string The text containing an embedded PHP region
	 * @param int|string $entry_id The entry id in effect
	 * @param int $form_id The form id the references belong to
	 * @param string $renderedElementMarkupName Markup name, when this is a live render
	 * @param mixed $screen The screen object, if any
	 * @return string The text with the PHP region replaced by its result
	 */
	function evalPHPStrings($string, $entry_id, $form_id, $renderedElementMarkupName='', $screen=null) {
		if(strstr($string, "<?php") !== false AND strstr($string, "?>")) {
			// $phpCode is kept untouched, it is what locates the region to replace further below
			$phpCode = substr($string, strpos($string, "<?php")+5, strpos($string, "?>") - strpos($string, "<?php") - 5);
			// substituteResultReferences is off because the caller substitutes across the whole string,
			// surrounding text included, once this returns - so the result would otherwise be done twice
			list($evalResult, $evalSucceeded) = $this->evalAdminPHPWithReferences($phpCode, $entry_id, $form_id,
				$renderedElementMarkupName, $screen, null, array(), false);
			if(!$evalSucceeded) {
				$evalResult = _formulize_ERROR_IN_PHP_CODE;
			}
			$string = str_replace("<?php".$phpCode."?>", $evalResult, $string);
		} else {
			return $string;
		}
		return $string;
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
