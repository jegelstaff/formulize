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

// Shared base classes for the "static content" display element types: fullWidthContent and
// captionedContent. These are display-only (non-data) elements that show static text or HTML,
// optionally with {element_handle} references or (via the admin UI) PHP code. The two types differ
// only in how their resolved content is wrapped for display (full-width insert-break array vs.
// a captioned XoopsFormLabel) and in whether a blank content falls back to the caption.
//
// This file is named so it is NOT matched by the class/*Element.php discovery glob (see
// formulizeHandler::discoverElementTypes), the same convention used by baseClassForLists.php.

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php"; // you need to make sure the base element class has been read in first!
require_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

// the content to display is always stored in element 0 of the ele_value array
define('ELE_VALUE_STATICCONTENT_CONTENT', 0);

abstract class formulizeStaticContentElement extends formulizeElement {

	public static $category = "layout";

	function __construct() {
		// $this->name is set by the concrete subclass before calling parent::__construct()
		$this->hasData = false; // non-data element, no column in the form's data table
		$this->needsDataType = false;
		$this->adminCanMakeRequired = false;
		$this->alwaysValidateInputs = false;
		parent::__construct();
	}

	// write code to a file, when the content is PHP code authored via the admin UI (content containing $value).
	// The code file is named from the element's ele_type, so each concrete type gets its own naming (eg: fullWidthContent_handle.php).
	public function setVar($key, $value, $not_gpc = false) {
		if($key == 'ele_value') {
			$valueToWrite = is_array($value) ? $value : unserialize($value);
			$filename = $this->getVar('ele_type').'_'.$this->getVar('ele_handle').'.php';
			if(strstr((string)$valueToWrite[ELE_VALUE_STATICCONTENT_CONTENT], "\$value")) {
				formulize_writeCodeToFile($filename, $valueToWrite[ELE_VALUE_STATICCONTENT_CONTENT]);
				$valueToWrite[ELE_VALUE_STATICCONTENT_CONTENT] = '';
				$value = is_array($value) ? $valueToWrite : serialize($valueToWrite);
			// delete the code file if it exists but the value no longer contains code (these elements can hold code or plain text/HTML)
			} elseif(file_exists(XOOPS_ROOT_PATH.'/modules/formulize/code/'.$filename)) {
				unlink(XOOPS_ROOT_PATH.'/modules/formulize/code/'.$filename);
			}
		}
		parent::setVar($key, $value, $not_gpc);
	}

	// read code back from a file, if one exists for this element
	public function getVar($key, $format = 's') {
		$format = $key == "ele_value" ? "f" : $format;
		$value = parent::getVar($key, $format);
		if($key == 'ele_value' AND is_array($value)) {
			$filename = $this->getVar('ele_type').'_'.$this->getVar('ele_handle').'.php';
			$filePath = XOOPS_ROOT_PATH.'/modules/formulize/code/'.$filename;
			if(file_exists($filePath)) {
				$fileValue = strval(file_get_contents($filePath));
				$value[ELE_VALUE_STATICCONTENT_CONTENT] = $fileValue ? $fileValue : $value[ELE_VALUE_STATICCONTENT_CONTENT];
			}
		}
		return $value;
	}
}

#[AllowDynamicProperties]
abstract class formulizeStaticContentElementHandler extends formulizeElementsHandler {

	var $db;
	// set to true in a subclass if a blank content should fall back to displaying the element's caption
	protected $useCaptionAsContentFallback = false;

	function __construct($db) {
		$this->db =& $db;
	}

	// Prepare data for the admin Options tab. The content (ele_value[0]) is supplied to the template
	// generically by admin/element.php via $options['ele_value'], and the code-file contents (if any)
	// are read back by the element's getVar override, so no extra preparation is needed here.
	function adminPrepare($element) {
		return array();
	}

	// Save the content the admin entered in the Options tab. setVar handles writing PHP code to a file.
	function adminSave($element, $ele_value = array(), $advancedTab = false) {
		$element->setVar('ele_value', $ele_value);
		return false;
	}

	// Display-only element: there is no stored entry value, so the content (ele_value) is what renders.
	function loadValue($element, $value, $entry_id) {
		return $element->getVar('ele_value');
	}

	// Display-only element: nothing is saved to the form's data table.
	function prepareDataForSaving($value, $element, $entry_id=null, $subformBlankCounter=null) {
		return;
	}

	// Display-only element: it is excluded from datasets, but implement the interface as a passthrough for safety.
	function prepareDataForDataset($value, $handle, $entry_id) {
		return $value;
	}

	// Display-only element: no post-save processing required.
	function afterSavingLogic($value, $element_id, $entry_id) {
	}

	public function getDefaultEleValue() {
		return array(ELE_VALUE_STATICCONTENT_CONTENT => '');
	}

	/**
	 * Validate the properties passed via the public API (MCP) and convert them into the ele_value array.
	 * @param array $properties The properties to validate (the public API representation)
	 * @param array $ele_value The current ele_value settings for this element (or the default for a new element)
	 * @param int|string|object $elementIdentifier The element id, handle or object being validated, if any
	 * @return array An array of properties ready for the object, keyed by object property name
	 */
	public function validateEleValuePublicAPIProperties($properties, $ele_value = [], $elementIdentifier = null) {
		if(isset($properties['content'])) {
			$properties['content'] = is_array($properties['content']) ? $properties['content'][array_key_first($properties['content'])] : $properties['content'];
			$content = (string)$properties['content'];
			if(strstr($content, '<?php') !== false OR strstr($content, '$value=') !== false OR strstr($content, '$value =') !== false) {
				throw new FormulizeMCPException('PHP code is not supported in the content of a static content element via this tool. Use plain text or HTML, optionally with {element_handle} references to include values from the current entry.', 'invalid_data');
			}
			$ele_value[ELE_VALUE_STATICCONTENT_CONTENT] = $content;
		}
		return ['ele_value' => $ele_value];
	}

	/**
	 * Render the element for display. The content is resolved here (references replaced, any PHP code
	 * evaluated) and then handed to the subclass's wrapResolvedContent() to produce the final form
	 * element representation (an insert-break array, or a captioned label, etc).
	 */
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen=false, $owner=null) {
		$id_form = $element->getVar('id_form');
		$renderer = new formulizeElementRenderer($element);
		if($this->useCaptionAsContentFallback AND trim($ele_value[ELE_VALUE_STATICCONTENT_CONTENT]) == "") {
			$ele_value[ELE_VALUE_STATICCONTENT_CONTENT] = $caption; // use the caption as the contents if no contents are specified
		}
		$ele_value[ELE_VALUE_STATICCONTENT_CONTENT] = $renderer->formulize_replaceReferencesAndVariables($ele_value[ELE_VALUE_STATICCONTENT_CONTENT], $entry_id, $id_form, $markupName, $screen);
		if(strstr($ele_value[ELE_VALUE_STATICCONTENT_CONTENT], "\$value=") OR strstr($ele_value[ELE_VALUE_STATICCONTENT_CONTENT], "\$value =")) {
			$form_id = $id_form;
			$entryData = gatherDataset($id_form, filter: $entry_id, frid: 0);
			$entry = $entryData[0];
			$creation_datetime = getValue($entry, "creation_datetime");
			$entryData = $entry; // alternate variable name for backwards compatibility
			$ele_value[ELE_VALUE_STATICCONTENT_CONTENT] = removeOpeningPHPTag($ele_value[ELE_VALUE_STATICCONTENT_CONTENT]);
			$value = ""; // will be set in the eval'd code
			$evalResult = eval($ele_value[ELE_VALUE_STATICCONTENT_CONTENT]);
			if($evalResult === false) {
				$ele_value[ELE_VALUE_STATICCONTENT_CONTENT] = _formulize_ERROR_IN_LEFTRIGHT;
			} else {
				$ele_value[ELE_VALUE_STATICCONTENT_CONTENT] = $value; // value is supposed to be the thing set in the eval'd code
				$ele_value[ELE_VALUE_STATICCONTENT_CONTENT] = $renderer->formulize_replaceReferencesAndVariables($ele_value[ELE_VALUE_STATICCONTENT_CONTENT], $entry_id, $id_form, $markupName, $screen); // in case the PHP code generated some { } references
			}
		}
		return $this->wrapResolvedContent($ele_value, $caption, $markupName);
	}

	/**
	 * Wrap the resolved content (in $ele_value[ELE_VALUE_STATICCONTENT_CONTENT]) into the final form
	 * element representation for this element type.
	 * @param array $ele_value The ele_value array, with the resolved content in element 0
	 * @param string $caption The prepared caption
	 * @param string $markupName The element's markup name
	 * @return mixed An array (treated as an insert-break) or a form element object
	 */
	abstract protected function wrapResolvedContent($ele_value, $caption, $markupName);

	/**
	 * Whether this element's content is dynamic (contains code or {handle} references) and therefore must be
	 * re-rendered via AJAX when other element values change on screen. Used by formulize_xhr_responder.php.
	 * @param array $ele_value The ele_value array for the element
	 * @return bool
	 */
	public function requiresDynamicRerendering($ele_value) {
		return (strstr($ele_value[ELE_VALUE_STATICCONTENT_CONTENT], "\$value=") OR strstr($ele_value[ELE_VALUE_STATICCONTENT_CONTENT], "\$value =") OR (strstr($ele_value[ELE_VALUE_STATICCONTENT_CONTENT], "{") AND strstr($ele_value[ELE_VALUE_STATICCONTENT_CONTENT], "}")));
	}
}
