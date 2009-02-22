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

include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/class/elementrenderer.php";
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

// $groups is optional and can be passed in to override getting the user's groups.  This is necessary for the registration form to work with custom displayed elements
function displayElement($formframe="", $ele, $entry="new", $noSave = false, $screen=null, $prevEntry=null, $renderElement=true, $profileForm, $groups="") {

	static $cachedPrevEntries = array();

	$subformCreateEntry = strstr($entry, "subformCreateEntry") ? true : false; // check for this special flag, which is mostly like a "new" situation, except for the deh hidden flag that gets passed back, since we don't want the standard readelements logic to pickup these elements!
	if($subformCreateEntry) { $subformEntryIndex = substr($entry, -1); } // index value will only ever be one character at the end (it will be between 0 and 4
	if($entry == "" OR $subformCreateEntry) { $entry = "new"; }

	$element = "";
	if(is_object($ele)) {	
		if(get_class($ele) == "formulizeformulize") {
			$element = $ele;
		} else {
			return "invalid_element";
		}
	}

	if(!$element) {
		
		if(!isset($formulize_mgr)) {
			$formulize_mgr =& xoops_getmodulehandler('elements', 'formulize');
		}
		
		if(is_numeric($ele)) {
  		$element =& $formulize_mgr->get($ele);
		} else {
      $framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
      $frameworkObject = $framework_handler->get($formframe);
      $frameworkElementIds = $frameworkObject->getVar('element_ids');
      $element_id = $frameworkElementIds[$ele];
  		$element =& $formulize_mgr->get($element_id);
		}
	
		if(!is_object($element)) {
			return "invalid_element";
		}
	}

	global $xoopsUser;
  if(!$groups) { // groups might be passed in, which covers the case of the registration form and getting the groups from the registration code
    $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
  }
	$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
	static $cachedEntryOwners = array();
	if(!isset($cachedEntryOwners[$element->getVar('id_form')][$entry])) {
		$cachedEntryOwners[$element->getVar('id_form')][$entry] = getEntryOwner($entry, $element->getVar('id_form'));
	}
	$owner = $cachedEntryOwners[$element->getVar('id_form')][$entry];
	$mid = getFormulizeModId();

	

	if($prevEntry==null) { // preferable to pass in prevEntry!
		$prevEntry = getEntryValues($entry, "", $groups, $element->getVar('id_form'), "", $mid, $uid, $owner);			
	}

	static $cachedViewPrivate = array();
	if(!isset($cachedViewPrivate[$element->getVar('id_form')])) {
		$gperm_handler =& xoops_gethandler('groupperm');
		$cachedViewPrivate[$element->getVar('id_form')] = $gperm_handler->checkRight("view_private_elements", $element->getVar('id_form'), $groups, $mid);	
	}
	$view_private_elements = $cachedViewPrivate[$element->getVar('id_form')];
	
	// check if the user is normally able to view this element or not, by checking their groups against the display groups -- added Nov 7 2005
	// messed up.  Private should not override the display settings.  And the $entry should be checked against the security check first to determine whether the user should even see this entry in the first place.
	$display = $element->getVar('ele_display');
	$private = $element->getVar('ele_private');
	if($private AND ($uid != $owner AND $entry != "new")) {
		$allowed = $view_private_elements ? 1 : 0;
	} elseif(strstr($display, ",")) {
		$display_groups = explode(",", $display);
		$allowed = array_intersect($groups, $display_groups) ? 1 : 0;
	} elseif($display == 1) {
		$allowed = 1;	
	} else {
		$allowed = 0;
	}
	
	if($allowed) {

		$ele_disabled = $element->getVar('ele_disabled');
		$isDisabled = false;
		if($ele_disabled == 1) {
			$isDisabled = true;
		} elseif(!is_numeric($disabled)) {
			$disabled_groups = explode(",", $ele_disabled);
			if(array_intersect($groups, $disabled_groups) AND !array_diff($groups, $disabled_groups)) {
				$isDisabled = true;
			}
		}

	  $renderer =& new formulizeElementRenderer($element);
  	$ele_value = $element->getVar('ele_value');
		$ele_type = $element->getVar('ele_type');
		$deprefix = $noSave ? "denosave_" : "de_";
		$deprefix = $subformCreateEntry ? "desubform".$subformEntryIndex."_" : $deprefix; // need to pass in an entry index so that all fields in the same element can be collected
		if(($prevEntry OR $profileForm === "new") AND $ele_type != 'subform' AND $ele_type != 'grid' AND $ele_type != 'derived') {
			$data_handler = new formulizeDataHandler($element->getVar('id_form'));
			$ele_value = loadValue($prevEntry, $element, $ele_value, $data_handler->getEntryOwnerGroups($entry), $groups, $entry, $profileForm); // get the value of this element for this entry as stored in the DB -- and unset any defaults if we are looking at an existing entry
		}
		
		formulize_benchmark("About to render element ".$element->getVar('ele_caption').".");
		
		
  	$form_ele =& $renderer->constructElement($deprefix . $element->getVar('id_form').'_'.$entry.'_'.$element->getVar('ele_id'), $ele_value, $entry, $isDisabled, $screen); 
		
		formulize_benchmark("Done rendering element.");
		
		if(!$renderElement) {
			return $form_ele;			
		} else {
			if($element->getVar('ele_type') == "ib") {
				print $form_ele[0];
				return "rendered";
			} elseif(is_object($form_ele)) {
					print $form_ele->render();
          if(!empty($form_ele->customValidationCode)) {
            $GLOBALS['formulize_renderedElementsValidationJS'][] = $form_ele->renderValidationJS();
          } elseif($element->getVar('ele_req') AND ($element->getVar('ele_type') == "text" OR $element->getVar('ele_type') == "textarea")) {
            $eltname    = $form_ele->getName();
            $eltcaption = $form_ele->getCaption();
            $eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, $eltcaption );
            $eltmsg = str_replace('"', '\"', stripslashes( $eltmsg ) );
            $GLOBALS['formulize_renderedElementsValidationJS'][] = "if ( myform.".$eltname.".value == \"\" ) { window.alert(\"".$eltmsg."\"); myform.".$eltname.".focus(); return false; }";
          }
					return "rendered";
			}
		}
  		

	// or, even if the user is not supposed to see the element, put in a hidden element with its default value (only on new entries)
	// NOTE: YOU CANNOT HAVE DEFAULT VALUES ON A LINKED FIELD CURRENTLY
	// So, no handling of linked values is included here.
	} elseif($forcehidden = $element->getVar('ele_forcehidden') AND $entry=="new" AND !$noSave) {
		// get the default value for the element, different kinds of elements have their defaults in different locations in ele_value
		$ele_value = $element->getVar('ele_value');
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

	if($prevValueThisElement = getElementValue($entry, $element_id, $element->getVar('id_form'))) {
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
