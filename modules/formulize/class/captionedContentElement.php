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

// "Captioned content" is a display-only (non-data) element that shows static text or HTML in the
// right column of a two-column layout, with its caption shown to users in the left column (just like
// a normal form element). Formerly the 'areamodif' element type.
// Most behaviour is shared with fullWidthContent via the formulizeStaticContent* base classes.
// There is a corresponding admin template for this element type in the templates/admin folder.

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/baseClassForStaticContent.php";

class formulizeCaptionedContentElement extends formulizeStaticContentElement {

	function __construct() {
		$this->name = "Captioned Content";
		parent::__construct();
	}

	public static function mcpElementPropertiesDescriptionAndExamples($update = false) {
		return
"**Element:** Captioned Content (captionedContent).
**Description:** A block of static text or HTML shown in the right column of the form's two-column layout, with the caption shown to users as the label in the left column (just like a normal form element). Use it to present read-only information alongside a label. It does not collect any data.
**Note:** For this type, the caption IS shown to users as the left-column label.
**Properties:**
- content (string, the text or HTML to display in the right column. You can include values from other elements in the current entry by putting an element handle in curly brackets, ie: {element_handle}. Plain text and basic HTML are supported. PHP code is not supported via this tool.)
**Examples:**
- A labelled note: caption \"Status\" with { content: \"This application is awaiting review.\" }
- HTML with a reference to the value of another element: { content: \"Total owing: <strong>{amount_due}</strong>\" }";
	}
}

#[AllowDynamicProperties]
class formulizeCaptionedContentElementHandler extends formulizeStaticContentElementHandler {

	function create() {
		return new formulizeCaptionedContentElement();
	}

	// Captioned content renders as a XoopsFormLabel: the caption appears in the left column and the
	// content in the right column of the form's two-column layout.
	protected function wrapResolvedContent($ele_value, $caption, $markupName) {
		$form_ele = new XoopsFormLabel(
			$caption,
			$ele_value[ELE_VALUE_STATICCONTENT_CONTENT],
			$markupName
		);
		$form_ele->setClass("formulize-text-for-display");
		return $form_ele;
	}
}
