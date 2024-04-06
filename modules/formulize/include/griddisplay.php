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

//THIS FILE HANDLES THE DISPLAY OF FORMS AS GRIDS.  FUNCTIONS CAN BE CALLED FROM ANYWHERE (INTENDED FOR PAGEWORKS MODULE)
//Form must be setup as follows:
//question 1
//question 2
//question 3
//question 4
//question 5
//question 6
//question 7
//etc
//
//To result in display like this (captions passed to function):
//...................| caption for col 1 | caption for col 2 | caption for col 3
//Caption for row 1  | element for q 1   | element for q 2   | element for q 3
//Caption for row 2  | element for q 4   | element for q 5   | element for q 6
//Caption for row 3  | element for q 7   | etc....
//
//Number of rows and columns determined by the captions that are passed.
//Intent is for use with textboxes, to mimic a spreadsheet like display, but could be used with any elements
//
//params:
//$fid -- the form id of the form to use
//$entry_id -- Optional.  An existing entry to display (will self-populate if the form is a single form)
//$rowcaps -- array of row captions
//$colcaps -- array of the column captions
//$orientation -- "horizontal" or "vertical" which controls whether the grid background colours emphasize rows or columns.  Default is horizontal.
//$title -- Optional.  Text to use as title instead of title of form.
//$startID -- Optional. ele_id of the first element in the form that you want to include in the grid, or the caption of that element.    This is the element that will be used in the q1 position and all subsequent elements will be drawn in their respective places after that.
//$finalCell -- Optional. Array containing HTML to use as the last cell in each row (for totalling, etc).  Each key is for each row in order.  Expectation is the developer would have calculated some values and prepared this HTML in advance.  Must have numeric keys! Should NOT include <td> and </td>
//$finalRow -- Optional. HTML to use as a last row in the table, to show totals, etc.  Expectation is the developer would have calculated some values and prepared this HTML in advance.  Should NOT include <tr> and </tr>
// $calledInternal -- boolean used to indicate whether we need the xoops security token or not.  When called from inside a form using the grid element collection, there will already be a security token associated with the form.

include_once XOOPS_ROOT_PATH.'/modules/formulize/include/common.php';

function displayGrid($fid, $entry_id="", $rowcaps, $colcaps, $title="", $orientation="horizontal", $startID="first", $finalCell="", $finalRow="", $calledInternal=false, $screen=null, $headingAtSide="", $elementId=0, $prevEntry = array()) {

	global $xoopsUser;
	$numcols = count((array) $colcaps);
	if(is_array($finalCell)) {
		$numcols = $numcols+2;
	} else {
		$numcols = $numcols+1;
	}
	$numrows = count((array) array_filter($rowcaps, 'nonNullGridRowCaps'));	# count non-null row captions
	if($title == "{FORMTITLE}") {
		$title = trans(getFormTitle($fid));
	} else {
		$title = trans($title);
	}
	$currentURL = getCurrentURL();
	$uid = $xoopsUser ? $xoopsUser->getVar('uid') : '0';
	$mid = getFormulizeModId();
	$gperm_handler =& xoops_gethandler('groupperm');
	$owner = $entry_id == 'new' ? ($xoopsUser ? $xoopsUser->getVar('uid') : 0) : getEntryOwner($entry_id, $fid);
	$member_handler =& xoops_gethandler('member');
	//$owner_groups = $owner ? $member_handler->getGroupsByUser($owner, FALSE) : array(0=>XOOPS_GROUP_ANONYMOUS);
	$data_handler = new formulizeDataHandler($fid);
	$owner_groups = $owner ? $data_handler->getEntryOwnerGroups($entry_id) : array(0=>XOOPS_GROUP_ANONYMOUS);
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);

	if(!$calledInternal) {
		if(!$scheck = security_check($fid, $entry_id, $uid, $owner, $groups, $mid, $gperm_handler)) {
			print "<p>" . _NO_PERM . "</p>";
			return;
		}
	}

	// determine if the form is a single entry form and so whether an entry already exists for this form...
	$single_result = getSingle($fid, $uid, $groups, $member_handler, $gperm_handler, $mid);
	$single = $single_result['flag'];
	if($single AND !$entry_id) { $entry_id = $single_result['entry']; }
	if(!$entry_id) { $entry_id = "new"; }
	$element_ids_query = elementsInGrid($startID, $fid); // returns all possible candidate elements, actual elements determined in loop below

	// initialize form
	if(!$calledInternal) {
		print $GLOBALS['xoopsSecurity']->getTokenHTML();
	}

	// start buffering the output
	ob_start();

	// set the title row
	if($headingAtSide) {
		$gridContents[0] = $title;
		$class = "even";
		print "<table class='formulize-grid'>\n<tr>";
		if ($numrows > 1 OR preg_replace('/[\s]+/mu', '', $rowcaps[0]) != '') {
			echo "<td class='head'></td>";
		}
	} else {
		print "<table class='outer formulize-grid'>\n";
		$class = "head";
		if($title) { print "<tr><th colspan='$numcols'>$title</th></tr>\n"; }
		print "<tr>\n<td class=\"head\">&nbsp;</td>\n";
	}

	// draw top row
	$needToDrawCellsWhenHeadingAtSide = false;
	$cellsWhenHeadingAtSide = '';
	foreach($colcaps as $thiscap) {
		if($headingAtSide) {
			$needToDrawCellsWhenHeadingAtSide = preg_replace('/[\s]+/mu', '', $thiscap) != '' ? true : $needToDrawCellsWhenHeadingAtSide;
			$cellsWhenHeadingAtSide .= "<td class=head>$thiscap</td>\n";
		} else {
		  if($orientation == "vertical" AND $class=="even" AND !$headingAtSide) { // only alternate rows
				$class = "odd";
			} elseif($orientation == "vertical") {
				$class = "even";
			}
			print "<td class=$class>$thiscap</td>\n";
		}
	}

	if($needToDrawCellsWhenHeadingAtSide) {
		print $cellsWhenHeadingAtSide;
	}

	if(is_array($finalCell)) { // draw blank header for last column if there is such a thing
		print "<td class=head>&nbsp;</td>\n";
	}
	print "</tr>\n";

	// draw regular rows
	$class = "head";
	$row_index = 0;
	$ele_index = 0;
	foreach($rowcaps as $thiscap) {
		if($orientation == "horizontal" AND $class=="even") {
			$class = "odd";
		} elseif($orientation == "horizontal") {
			$class = "even";
		} else {
			$class = "head";
		}
		print "<tr>\n";
		if($headingAtSide) {
			if ($numrows > 1 OR preg_replace('/[\s]+/mu', '', $thiscap) != '') {
				print "<td class=\"head\">$thiscap</td>\n";
			}
		} else {
			print "<td class=$class>$thiscap</td>\n";
		}
		foreach($colcaps as $thiscolcap) {
			if($orientation == "vertical" AND $class=="even") {
				$class = "odd";
			} elseif($orientation == "vertical") {
				$class = "even";
			}
			print "<td class=$class>\n";
			// display the element starting with the initial one.  Keep trying to display something until we're successful (displaying the element might fail if the user does not have permission to view (based on which groups are allowed to view this element)
			$renderSuccess = false;
			while(!$renderSuccess AND isset($element_ids_query[$ele_index])) {
				$elementInGridId = $element_ids_query[$ele_index];
				$deReturnValue = displayElement("", $elementInGridId, $entry_id, false, $screen, $prevEntry, false);
				if(is_array($deReturnValue)) {
					$form_ele = $deReturnValue[0];
					$isDisabled = $deReturnValue[1];
				} else {
					$form_ele = $deReturnValue;
					$isDisabled = false;
				}
				if(is_object($form_ele)) {
					$renderSuccess = true;
					print $form_ele->render();
					catalogueGridElement($elementInGridId, $entry_id, $elementId, $prevEntry, $screen);
				}
				$ele_index++;
			}
			if(!$renderSuccess) { print "&nbsp;"; }
			print "</td>\n";
		}
		if(is_array($finalCell)) { // draw final cell values if they exist
			if($orientation == "vertical") {
				$class = "head";
			}
			if($finalCell[$row_index]) {
				print "<td class=$class>" . $finalCell[$row_index] . "</td>\n";
			} else {
				print "<td class=$class>&nbsp;</td>\n";
			}
		}
		print "</tr>\n";
		$row_index++;
	}

	// draw final row if necessary
	if($finalRow) {
		print "<tr>$finalRow</tr>\n";
	}
	print "</table>";
	$gridContents[1] = trans(ob_get_clean());
	if($headingAtSide === "") { // if $headingAtSide is "" (not false) then we print out the grid contents here.  Only pass back contents if $headingAtSide is specified as true or false (presumably by the formdisplay.php file), since otherwise for backwards compatibility we need to printout contents here because that's what the behaviour used to be.
		print $gridContents[1];
	} elseif($headingAtSide) {
		return $gridContents;
	} else {
		return $gridContents[1];
	}


}

/**
 * Catalogue an element as belonging to a certain grid, and generate any necessary validation javascript for picking up later
 * @param $elementId The id number of the element that we're cataloguing, that appears inside a grid
 * @param $entry_id The id number of the entry the grid is being rendered in, or 'new' for entries that haven't been saved yet.
 * @param $containerElementMarkupName The identifier as used in the DOM for the grid that contains this element
 * @param array $prevEntry Optional. The canonical values in this entry which should be taken into account when rendering grid elements. Generated in formdisplay.php or elementdisplay.php.
 * @param object $screen Optional. The screen that the grid is being rendered in, if any
 * @return Nothing
 */
function catalogueGridElement($elementId, $entry_id, $containingGridElementIdOrObject, $prevEntry = array(), $screen = null) {
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
	$gridElementObject = is_a($containingGridElementIdOrObject, 'formulizeformulize') ? $containingGridElementIdOrObject : $element_handler->get($containingGridElementIdOrObject);
	$elementInGridObject = $element_handler->get($elementId);
	$fid = $elementInGridObject->getVar('id_form');
	$elementInGridMarkupName = "de_{$fid}_{$entry_id}_{$elementId}";
	$containerElementMarkupName = "de_{$fid}_{$entry_id}_{$gridElementObject->getVar('ele_id')}";
	$GLOBALS['elementsInGridsAndTheirContainers'][$elementId] = $containerElementMarkupName;
	$gridDisplayConditions = $gridElementObject->getVar('ele_filtersettings');
	if(is_array($gridDisplayConditions[0]) AND count($gridDisplayConditions[0]) > 0 ) {
		catalogConditionalElement($elementInGridMarkupName, array_unique($gridDisplayConditions[0]));
		makePlaceholderForConditionalElement($elementInGridObject, $entry_id, $elementInGridMarkupName, $prevEntry, $screen); // don't actually put the placeholder into the form being compiled, because the containing grid is all we need. Just need to do book keeping for JS.
		removeFromConditionalCatalogue($elementInGridMarkupName); // only need to be in for generating the JS, otherwise, we don't want them here so that they don't add unnecessary complexity to the conditional event processing JS, since it's their container that matters for events.
	}
}

// THIS FUNCTION TAKES THE ELE_VALUE SETTINGS FOR A GRID AND RETURNS ALL THE NECESSARY PARAMS READY FOR PASSING TO THE DISPLAYGRID FUNCTION
// ALSO WORKS OUT THE NUMBER OF ELEMENTS THAT CAN BE ENTERED INTO THIS GRID
function compileGrid($element) {

	$ele_value = $element->getVar('ele_value');

	// 0 is heading
	// 1 is row captions
	// 2 is col captions
	// 3 is shading
	// 4 is first element

	switch($ele_value[0]) {
		case "caption":
			global $myts;
			if(!$myts){
				$myts =& MyTextSanitizer::getInstance();
			}
			// call the text sanitizer, first try to convert HTML chars, and if there were no conversions, then do a textarea conversion to automatically make links clickable
			$ele_caption = trans($element->getVar('ele_caption'));
			$htmlCaption = $myts->undoHtmlSpecialChars($ele_caption);
			if($htmlCaption == $ele_caption) {
				$ele_caption = $myts->displayTarea($ele_caption);
			} else {
				$ele_caption = $htmlCaption;
			}
			$toreturn[] = $ele_caption;
			break;
		case "form":
			$form_handler = xoops_getmodulehandler('forms', 'formulize');
			$formObject = $form_handler->get($element->getVar('id_form'));
			$toreturn[] = $formObject->getVar('title');
			break;
		case "none":
			$toreturn[] = "";
			break;
	}

	$toreturn[] = explode(",", $ele_value[1]);
	$toreturn[] = explode(",", $ele_value[2]);

	$toreturn[] = $ele_value[3];

	$toreturn[] = $ele_value[4];

	// number of cells in this grid
	$toreturn[] = count((array) $toreturn[1]) * count((array) $toreturn[2]);

	return $toreturn;
}

/**
 * Takes a grid element and renders it, returning something ready to go into a xoops form object
 * @param object $elementObject The grid element we are adding to the form
 * @param int|string $entry_id The id number of the entry the grid is being rendered in, or 'new' for entries that haven't been saved yet.
 * @param array $prevEntry Optional. The canonical values in this entry which should be taken into account when rendering grid elements. Generated in formdisplay.php or elementdisplay.php.
 * @param object $screen Optional. The screen that the grid is being rendered in, if any
 * @return mixed Returns the element ready to be added to the form, either an object suitable for the addElement method, or a string suitable for the insertBreakFormulize method.
 */
function renderGrid($elementObject, $entry_id = 'new', $prevEntry = null, $screen = null) {
	$ele_value = $elementObject->getVar('ele_value');
	$fid = $elementObject->getVar('id_form');
	$renderedElementName = "de_".$fid."_".$entry_id."_".$elementObject->getVar('ele_id');
	list($grid_title, $grid_row_caps, $grid_col_caps, $grid_background, $grid_start, $grid_count) = compileGrid($elementObject);
	$headingAtSide = ($ele_value[5] AND $grid_title) ? true : false; // if there is a value for ele_value[5], then the heading should be at the side, otherwise, grid spans form width as it's own chunk of HTML
	$gridContents = displayGrid($fid, $entry_id, $grid_row_caps, $grid_col_caps, $grid_title, $grid_background, $grid_start, "", "", true, $screen, $headingAtSide, $elementObject->getVar('ele_id'), $prevEntry); // also sets the $GLOBALS['elementsInGridsAndTheirContainers'] which references the elements that were rendered into the grid
	if($headingAtSide) { // grid contents is the two bits for the xoopsformlabel when heading is at side, otherwise, it's just the contents for the break
		$gridElement = new XoopsFormLabel($gridContents[0], $gridContents[1], $renderedElementName);
		$helpText = $elementObject->getVar('ele_desc');
		if(trim($helpText)) {
			$gridElement->setDescription($helpText);
		}
		// if any of the elements in the grid are required, mark as required so we get the asterisk
		if(gridHasRequiredElements($grid_start, $fid, $grid_count)) {
			$gridElement->setRequired();
		}
		$gridElement->formulize_element = $elementObject;
		return $gridElement; // object
	} else {
		return $gridContents; // string
	}
}

/**
 * This function gets the elements that are included in a grid
 * @param int $startID The starting element ID (could be some other legacy format too, yuck)
 * @param int $fid The form id number
 * @param int $gridCount Optional. Recommended! The number of elements that will be included in the grid. If left out, returns a list of all possible elements after the start element.
 * @param boolean $requiredOnly Optional. A boolean to indicate whether we should limit the search to required elements (elements marked as required for users)
 * @return array Returns an array of the element ids of the elements in the grid
 */
function elementsInGrid($startID, $fid, $gridCount = 0, $requiredOnly = false) {
	static $cachedGridElements = array();
	$requiredOnly = $requiredOnly ? 1 : 0; // make a number, nicer for the array
	if(!isset($cachedGridElements[$fid][$startID][$gridCount][$requiredOnly])) {
		global $xoopsDB;
		// figure out where we are supposed to start in the form
		if(!is_numeric($startID) AND $startID !== "first") {
			$order_query = q("SELECT ele_order FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_caption = \"$startID\" AND id_form=\"$fid\"");
		} elseif($startID === "first") { // get the ele_id of the element with the lowest weight
			$order_query = q("SELECT ele_order FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form=\"$fid\" ORDER BY ele_order LIMIT 0,1");
		} else {
			$order_query = q("SELECT ele_order FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form=\"$fid\" AND ele_id =\"$startID\"");
		}
		$starting_order = $order_query[0]['ele_order'];
		$required = $requiredOnly ? "ele_req = 1 AND" : "";
		$limit = $gridCount ? " LIMIT 0,".intval($gridCount) : "";
		$element_ids_result = q("SELECT ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE $required ele_order >= '$starting_order' AND id_form='$fid' AND ele_type != 'subform' ORDER BY ele_order $limit", 'ele_id', true);
		$cachedGridElements[$fid][$startID][$gridCount][$requiredOnly] = $element_ids_result;
	}
	return $cachedGridElements[$fid][$startID][$gridCount][$requiredOnly];
}

/**
 * This function checks elements from the start through the count, and returns true if any are required
 * @param int $startID The starting element ID (could be some other legacy format too, yuck)
 * @param int $grid_count The number of elements that will be included in the grid
 * @param int $fid The form id number
 * @return boolean Returns true if any of the elements to be included are required. Otherwise, false.
 */
function gridHasRequiredElements($startID, $fid, $grid_count) {
	$requiredElements = true;
	return (count(elementsInGrid($startID, $fid, $grid_count, $requiredElements)) > 0);
}

function nonNullGridRowCaps($var) {
    if(trim($var) != "") {
        return true;
    } else {
        return false;
    }
}
