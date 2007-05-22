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

	$element = "";
	if(is_object($ele)) {	
		if(get_class($ele) == "formulizeformulize") {
			$element = $ele;
			$element_id = $ele->getVar('ele_id');
		}
	}

	if(!$element) {
		if(is_numeric($ele))
		  {
			$element_id = $ele;
		  }
		else
		  {
			$element_id = getFrameworkElementId($formframe, $ele);
		  }
	
		if(!isset($formulize_mgr)) {
			$formulize_mgr =& xoops_getmodulehandler('elements', 'formulize');
		}
	
		$element =& $formulize_mgr->get($element_id);
		if(!is_object($element)) {
			return "invalid_element";
		}
	}

	// check if the user is normally able to view this element or not, by checking their groups against the display groups -- added Nov 7 2005
	// messed up.  Private should not override the display settings.  And the $entry should be checked against the security check first to determine whether the user should even see this entry in the first place.
	$display = $element->getVar('ele_display');
	$private = $element->getVar('ele_private');
	$mid = getFormulizeModId();
	$uid = $xoopsUser->getVar('uid');
	$owner = getEntryOwner($entry);
	if($private AND ($uid != $owner AND $entry != "new")) {
		$gperm_handler =& xoops_gethandler('groupperm');
		$view_private_elements = $gperm_handler->checkRight("view_private_elements", $element->getVar('id_form'), $groups, $mid);
		$allowed = $view_private_elements ? 1 : 0;
	} elseif(strstr($display, ",")) {
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
  			$prevEntry = getEntryValues($entry, $formulize_mgr, $groups, "", "", $mid, $uid, $owner);
	  	}
  		if($prevEntry) { 
			$loadValueEntry = $entry == "new" ? "" : $entry; // loadValue expects a blank value for $entry if we are looking at a new entry.
			$member_handler =& xoops_gethandler('member');
			$ownerObj = $member_handler->getUser($owner);
			$owner_groups = is_object($ownerObj) ? $ownerObj->getGroups() : XOOPS_GROUP_ANONYMOUS;  
	  		$ele_value = loadValue($prevEntry, $element, $ele_value, $owner_groups, "", $loadValueEntry); // get the value of this element for this entry as stored in the DB -- "" is groups which is deprecated in that function.
  			// query to see if this particular element has a value saved in this entry
			if($prevValueThisElement = getElementValue($entry, getRealCaption($element_id))) {
  					print "<input type=hidden name=deh_" . $entry . "_" . $element->getVar('ele_id') . " value=set>\n"; // indicates a previous value for this element
	  		} else {
  					print "<input type=hidden name=deh_" . $entry . "_" . $element->getVar('ele_id') . " value=empty>\n";
  			}
	  	} else {
  				print "<input type=hidden name=deh_" . $entry . "_" . $element->getVar('ele_id') . " value=empty>\n"; // note, $entry may be "new" in this case
	  	}
  		$form_ele =& $renderer->constructElement('de_' . $entry . '_'.$element->getVar('ele_id'), $ele_value, $entry);
		$form_ele->setExtra("onchange=\"javascript:formulizechanged=1;\"");
		if($element->getVar('ele_type') == "ib") {
			print $form_ele[0];
		} else {
		  	print $form_ele->render();
		}
  		return "rendered";

  	// or, even if the user is not supposed to see the element, put in a hidden element with its default value (only on new entries)
  	// NOTE: YOU CANNOT HAVE DEFAULT VALUES ON A LINKED FIELD CURRENTLY
  	// So, no handling of linked values is included here.
  	} elseif($forcehidden = $element->getVar('ele_forcehidden') AND $entry=="new") {
  		// get the default value for the element, different kinds of elements have their defaults in different locations in ele_value
  		$ele_value = $element->getVar('ele_value');
  		print "<input type=hidden name=deh_" . $entry . "_" . $element->getVar('ele_id') . " value=empty>\n";
  		// handle only radio buttons and textboxes for now.
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
			case "text":
				$myts =& MyTextSanitizer::getInstance();
				print "<input type=hidden name=de_". $entry . "_" . $element->getVar('ele_id') . " id=de_" . $entry . "_" . $element->getVar('ele_id') . " value='" . $myts->htmlSpecialChars(getTextboxDefault($ele_value[2])) . "'>\n";
				break;
			case "textarea":
				$myts =& MyTextSanitizer::getInstance();
				print "<input type=hidden name=de_". $entry . "_" . $element->getVar('ele_id') . " id=de_" . $entry . "_" . $element->getVar('ele_id') . " value='" . $myts->htmlSpecialChars(getTextboxDefault($ele_value[0])) . "'>\n";
				break;

  		}
		return "hidden";
		
	} else {
		return "not_allowed";
	}
}

// THIS FUNCTION RETURNS THE CAPTION FOR AN ELEMENT 
// added June 25 2006 -- jwe
function displayCaption($formframe="", $ele) {
	$element = "";
	if(is_object($ele)) {	
		if(get_class($ele) == "formulizeformulize") {
			$element = $ele;
		}
	}

	if(!$element) {
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
      	if(!is_object($element)) {
      		return "invalid_element";
      	}
	}

	return $element->getVar('ele_caption');

}

// THIS FUNCTION DRAWS IN A SAVE BUTTON AT THE POINT REQUESTED BY THE USER
// The displayElementRedirect is passed back to the page and is used to override the currently specified page, so the user can go to different content upon submitting the form
// Redirect must be a valid pageworks page number!
// Note that the URL does not change, even though the page contents do!
function displayElementSave($text="", $style="", $redirect_page="") {
	if($text == "") { $text = _pageworks_SAVE_BUTTON; }
	print "<input type=hidden name=displayElementRedirect value=$redirect_page>\n";
	print "<input type=submit name=submitelementdisplayform value=\"$text\" style=\"$style\">\n";
}

// FUNCTION FOR DISPLAYING A TEXT LINK OR BUTTON THAT APPENDS OR OVERWRITES VALUES FOR AN ELEMENT
// DO NOT USE WITH LINKED FIELDS!!!
//function displayButton($text, $ele, $value, $entry="new", $append="replace", $buttonOrLink="button") {
function displayButton($text, $ele, $value, $entry="new", $append="replace", $buttonOrLink="button", $formframe = "") {
	//echo "text: $text, ele: $ele, value: $value, entry: $entry, append: $append, buttonOrLink: $buttonOrLink, formframe: $formframe<br>";

	// 1. check for button or link
	// 2. write out the element

	$element = "";
	if(is_object($ele)) {	
		if(get_class($ele) == "formulizeformulize") {
			$element = $ele;
			$element_id = $element->getVar('ele_id');
		}
	}

	if(!$element) {
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
      	if(!is_object($element)) {
      		return "invalid_element";
      	}

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
