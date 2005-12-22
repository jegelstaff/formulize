<?php

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions 					     ##
##  Project: Formulize                                                       ##
###############################################################################

//THIS FILE HANDLES THE DISPLAY OF INDIVIDUAL FORM ELEMENTS.  FUNCTIONS CAN BE CALLED FROM ANYWHERE (INTENDED FOR PAGEWORKS MODULE)

function displayElement($formframe="", $ele, $entry="new") {

	if($entry == "") { $entry = "new"; }

	global $xoopsUser;
	$groups = $xoopsUser ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS;

	include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
	include_once XOOPS_ROOT_PATH . "/modules/formulize/class/elementrenderer.php";
	include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';


	if(is_numeric($ele))
    {
		$element_id = $ele;
    }
	else
    {
		$element_id = getFrameworkElementId($formframe, $ele);
    }

	if(!$formulize_mgr) {
		$formulize_mgr =& xoops_getmodulehandler('elements', 'formulize');
	}

	$element =& $formulize_mgr->get($element_id);

	// check if the user is normally able to view this element or not, by checking their groups against the display groups -- added Nov 7 2005
	$display = $element->getVar('ele_display');
	if(strstr($display, ",")) {
		$display_groups = explode(",", $display);
		if(array_intersect($groups, $display_groups)) {
			$allowed = 1;
		} else {
			$allowed = 0;
		}
	} elseif($display == 1) {
		$allowed = 1;	
	} else {
		$allowed = 0;
	}
	
	if($allowed) {

	$renderer =& new formulizeElementRenderer($element);

	$ele_value = $element->getVar('ele_value');
	if($entry != "new") {
		$prevEntry = getEntryValues($entry, $formulize_mgr, $groups);
	}

	if($prevEntry) { 
		$ele_value = loadValue($prevEntry, $element, $ele_value); // get the value of this element for this entry as stored in the DB 
		// query to see if this particular element has a value saved in this entry
			if($prevValueThisElement = getElementValue($entry, getRealCaption($element_id))) {
				print "<input type=hidden name=deh_" . $entry . "_" . $element->getVar('ele_id') . " value=set>\n"; // indicates a previous value for this element
		} else {
				print "<input type=hidden name=deh_" . $entry . "_" . $element->getVar('ele_id') . " value=empty>\n";
		}
	} else {
			print "<input type=hidden name=deh_" . $entry . "_" . $element->getVar('ele_id') . " value=empty>\n"; // note, $entry may be "new" in this case
	}
	$form_ele =& $renderer->constructElement('de_' . $entry . '_'.$element->getVar('ele_id'), $ele_value);
	print $form_ele->render();

	// or, even if the user is not supposed to see the element, put in a hidden element with its default value (only on new entries)
	// NOTE: YOU CANNOT HAVE DEFAULT VALUES ON A LINKED FIELD CURRENTLY
	// So, no handling of linked values is included here.
	} elseif($forcehidden = $element->getVar('ele_forcehidden') AND $entry=="new") {
		// get the default value for the element, different kinds of elements have their defaults in different locations in ele_value
		$ele_value = $element->getVar('ele_value');
		print "<input type=hidden name=deh_" . $entry . "_" . $element->getVar('ele_id') . " value=empty>\n";
		// handle only radio buttons for now.
		switch($element->getVar('ele_type')) {
			case "radio":
				$indexer = 1;
				foreach($ele_value as $k=>$v) {
					if($v == 1) {
						print "<input type=hidden name=de_" . $entry . "_" . $element->getVar('ele_id') . " id=de_" . $entry . "_" . $element->getVar('ele_id') . " value=\"$indexer\">\n";
					}
					$indexer++;
				}
				break;
		}
	}
}

// THIS FUNCTION DRAWS IN A SAVE BUTTON AT THE POINT REQUESTED BY THE USER
// The displayElementRedirect is passed back to the page and is used to override the currently specified page, so the user can go to different content upon submitting the form
// Note that the URL does not change, even though the page contents do!
function displayElementSave($text="", $redirect_page="") {
	if($text == "") { $text = _pageworks_SAVE_BUTTON; }
	print "<input type=hidden name=displayElementRedirect value=$redirect_page>\n";
	print "<input type=submit name=submitelementdisplayform value=\"$text\">\n";
}

// FUNCTION FOR DISPLAYING A TEXT LINK OR BUTTON THAT APPENDS OR OVERWRITES VALUES FOR AN ELEMENT
// DO NOT USE WITH LINKED FIELDS!!!
//function displayButton($text, $ele, $value, $entry="new", $append=0, $buttonOrLink="button") {
function displayButton($text, $ele, $value, $entry="new", $append=0, $buttonOrLink="button", $formframe = "") {
	//echo "text: $text, ele: $ele, value: $value, entry: $entry, append: $append, buttonOrLink: $buttonOrLink, formframe: $formframe<br>";

	// 1. check for button or link
	// 2. write out the element

	if(is_numeric($ele))
    {
		$element_id = $ele;
    }
	else
    {
		$element_id = getFrameworkElementId($formframe, $ele);
    }

	if($prevValueThisElement = getElementValue($entry, getRealCaption($element_id))) {
		$prevValue = 1;
	} else {
		$prevValue = 0;
	}

	if($buttonOrLink == "button") {
		$curtime = time();
		print "<input type=button name=displayButton_$curtime id=displayButton_$curtime value=\"$text\" onclick=\"javascript:displayButtonProcess('$formframe', '$ele', '$entry', '$value', '$append', '$prevValue');return false;\">\n";
	} elseif($buttonOrLink == "link") {
		print "<a href=\"\" onclick=\"javascript:displayButtonProcess('$formframe', '$ele', '$entry', '$value', '$append', '$prevValue');return false;\">$text</a>\n";
	} else {
		exit("Error: invalid button or link option specified in a call to displayButton");
	}
}



?>
