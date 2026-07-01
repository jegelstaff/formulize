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

// "Full width content" is a display-only (non-data) element that shows static text or HTML
// spanning the full width of the form. Formerly the 'ib' element type.
// Most behaviour is shared with captionedContent via the formulizeStaticContent* base classes.
// There is a corresponding admin template for this element type in the templates/admin folder.

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/baseClassForStaticContent.php";

// the css class applied when the form is rendered in a table layout (full width content only)
define('ELE_VALUE_FULLWIDTHCONTENT_CSSCLASS', 1);

class formulizeFullWidthContentElement extends formulizeStaticContentElement {

	function __construct() {
		$this->name = "Full Width Content";
		parent::__construct();
	}

	public static function mcpElementPropertiesDescriptionAndExamples($update = false) {
		return
"**Element:** Full Width Content (fullWidthContent).
**Description:** A block of static text or HTML that spans the entire width of the form. Use it for instructions, headings, or contextual information shown to the people filling out the form. It does not collect any data.
**Note:** For this type, the caption is an internal label only and is NOT shown to users. If you leave the content empty, the caption text will be displayed instead.
**Properties:**
- content (string, the text or HTML to display. You can include values from other elements in the current entry by putting an element handle in curly brackets, ie: {element_handle}. Plain text and basic HTML are supported. PHP code is not supported via this tool.)
**Examples:**
- A simple instruction spanning the form: { content: \"Please complete all of the fields below.\" }
- HTML with a reference to the value of another element: { content: \"<strong>Welcome, {first_name}!</strong>\" }";
	}
}

#[AllowDynamicProperties]
class formulizeFullWidthContentElementHandler extends formulizeStaticContentElementHandler {

	// a blank content falls back to displaying the element's caption
	protected $useCaptionAsContentFallback = true;

	function create() {
		return new formulizeFullWidthContentElement();
	}

	public function getDefaultEleValue() {
		$ele_value = parent::getDefaultEleValue();
		$ele_value[ELE_VALUE_FULLWIDTHCONTENT_CSSCLASS] = 'head';
		return $ele_value;
	}

	public function validateEleValuePublicAPIProperties($properties, $ele_value = [], $elementIdentifier = null) {
		$result = parent::validateEleValuePublicAPIProperties($properties, $ele_value, $elementIdentifier);
		// the visual style is not exposed via the public API - always use the default
		$result['ele_value'][ELE_VALUE_FULLWIDTHCONTENT_CSSCLASS] = 'head';
		return $result;
	}

	// Full width content renders as an array (item 0 is the contents, item 1 is the css class of the
	// table cell when the form is table-rendered); the rendering pipeline treats it as an insert-break.
	protected function wrapResolvedContent($ele_value, $caption, $markupName) {
		return $ele_value;
	}
}
