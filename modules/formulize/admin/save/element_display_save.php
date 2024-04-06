<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2010 Freeform Solutions                  ##
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
##  URL: http://www.formulize.org                           ##
##  Project: Formulize                                                       ##
###############################################################################

// this file contains objects to retrieve screen(s) information for elements

include_once XOOPS_ROOT_PATH."/modules/formulize/class/formScreen.php";

// this file handles saving of submissions from the element display page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}

// invoke the necessary objects
$element_handler = xoops_getmodulehandler('elements','formulize');
if(!$ele_id = intval($_GET['ele_id'])) { // on new element saves, new ele_id can be passed through the URL of this ajax save
  if(!$ele_id = intval($_POST['formulize_admin_key'])) {
    print "Error: could not determine element id when saving display settings";
    return;
  }
}
$element = $element_handler->get($ele_id);
$fid = $element->getVar('id_form');

$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($fid);
if($formObject->getVar('lockedform')) {
  return;
}
// check if the user has permission to edit the form
if(!$gperm_handler->checkRight("edit_form", $fid, $groups, $mid)) {
  return;
}

// do not need to serialize this when assigning, since the elements class calls cleanvars from the xoopsobject on all properties prior to insertion, and that intelligently serializes properties that have been declared as arrays
list($parsedFilterSettings, $filterSettingsChanged) = parseSubmittedConditions('elementfilter', 'display-conditionsdelete');
list($parsedDisabledConditions, $disabledConditionsChanged) = parseSubmittedConditions('disabledconditions', 'disabled-conditionsdelete');
$_POST['reload_element_pages'] = ($filterSettingsChanged OR $disabledConditionsChanged) ? true : false;
$element->setVar('ele_filtersettings', $parsedFilterSettings);
$element->setVar('ele_disabledconditions', $parsedDisabledConditions);

// check that the checkboxes have no values, and if so, set them to "" in the processedValues array
if(!isset($_POST['elements-ele_private'])) {
    $processedValues['elements']['ele_private'] = "";
}
foreach($processedValues['elements'] as $property=>$value) {
  $element->setVar($property, $value);
}

if($_POST['elements_ele_display'][0] == "all") {
	$display = 1;
} else if($_POST['elements_ele_display'][0] == "none") {
	$display = 0;
} else {
	$display = "," . implode(",", $_POST['elements_ele_display']) . ",";
}
$element->setVar('ele_display', $display);

if($_POST['elements_ele_disabled'][0] == "none") {
	$disabled = 0;
} else if($_POST['elements_ele_disabled'][0] == "all"){
  $disabled = 1;
} else {
  $disabled = "," . implode(",", $_POST['elements_ele_disabled']) . ",";
}
$element->setVar('ele_disabled', $disabled);


// Saving element existence in multi-paged screens
$screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
$raw_pages = $_POST['multi_page_screens'];

if (is_array($raw_pages)) {
    foreach($raw_pages as $key => $page_value) {
        // can ignore this top tree node (the template must hand back some placeholder value for it).
        // Since the script below will loop through each child node regardless
        if ($page_value == "all") {
            unset($raw_pages[$key]);
        }
    }
}

$all_multi_screens = $form_handler->getMultiScreens($fid);

// Go through each possible multi-paged screen's pages, and make changes accordingly
foreach ($all_multi_screens as $i => $screen_array) {
  $screen_id = $screen_array['sid'];
  $screen = $screen_handler->get($screen_id);
  $existing_pages = $screen->getVar('pages');

  if (empty($raw_pages)) {
    // Check if this element exists in each page, if it exists, then unset them
    foreach ($existing_pages as $page_index => $page_elements) {
      // Since PHP does not know how to differentiate between false and 0 that well, have a double checking for element existence
      $element_exists = in_array($ele_id, $page_elements);
      $element_index = array_search($ele_id, $page_elements);
      if ($element_exists) {
        unset($existing_pages[$page_index][$element_index]);
      }
    }
  } else {
     foreach ($existing_pages as $page_index => $page_elements) {
      if (empty($page_elements)) {
        // Necessary to loop through each treeview checkbox value, since every value is concatenated with its page index
        $element_exists_in_treeview = false;
        foreach ($raw_pages as $k => $raw_pair) {
          $screen_page = explode("-", $raw_pair);

          if ($screen_id == $screen_page[0] && $page_index == $screen_page[1]) {
            $element_exists_in_treeview = true;
          }
        }

        if ($element_exists_in_treeview) {
          if (!in_array($ele_id, $page_elements)) {
            // If this element does not exist yet under the page's element array, then add it
            array_push($existing_pages[$page_index], $ele_id);
          }
        }
      } else {
        foreach ($page_elements as $ele_index => $page_element) {
          // Necessary to loop through each treeview checkbox value, since every value is concatenated with its page index
          $element_exists_in_treeview = false;
          foreach ($raw_pages as $k => $raw_pair) {
            $screen_page = explode("-", $raw_pair);

            if ($screen_id == $screen_page[0] && $page_index == $screen_page[1]) {
              $element_exists_in_treeview = true;
            }
          }

          if ($element_exists_in_treeview) {
            if (!in_array($ele_id, $existing_pages[$page_index])) {
              // If this element does not exist yet under the page's element array, then add it
              array_push($existing_pages[$page_index], $ele_id);
            }
          } else {
            if (in_array($ele_id, $existing_pages[$page_index])) {
              // If this element exists under the page's element array, then remove it
              unset($existing_pages[$page_index][$ele_index]);
            }
          }
        }
      }
    }
  }
  $screen->setVar('pages',serialize($existing_pages));
  if(!$screen_handler->insert($screen)) {
      print "Error: could not save the screen properly: ".$xoopsDB->error();
  }
}

// Saving element existence in screen(s)
$screens_save = $_POST['elements_form_screens'];
if (!is_array($screens_save)) {
    $screens_save = array();
}
// go through each possible screen, and save whether the element in the UI accordingly by appending to existing screen's elements
// If the screen is not highlighted in the UI, then we must unset it manually by going through each screen's saved array
$formScreenHandler = xoops_getmodulehandler('formScreen', 'formulize');
$all_screens = $formScreenHandler->getScreensForElement($fid);

// Due to security, not possible to retrieve formelements from using getmodulehandler, hence used abstract method.
// For saving, use getmodulehandler directly
$screen_handler = xoops_getmodulehandler('formScreen', 'formulize');
foreach ($all_screens as $key => $screen) {
  $screen_elements = $formScreenHandler->getSelectedElementsForScreen($screen['sid']);
  $screen_stream = $screen_handler->get($screen['sid']);
  if (in_array($screen['sid'], $screens_save)) {
    // avoid adding duplicate element within the list if the screen already has it
    if (!in_array($ele_id, $screen_elements)) {
      array_push($screen_elements, strval($ele_id));
    }
    $save_element = serialize($screen_elements);
    $screen_stream->setVar('formelements', $save_element);
  } else {
    if (in_array($ele_id, $screen_elements)) {
      // If the element exists in the screen's element, array, then unset it
      if(($index = array_search($ele_id, $screen_elements)) !== false) {
          unset($screen_elements[$index]);
      }

      // if resulting array is empty, then send an empty quotation as data to setVar
      if (empty($screen_elements)){
        $screen_stream->setVar('formelements', "");
      } else {
        $save_element = serialize($screen_elements);
        $screen_stream->setVar('formelements', $save_element);
      }
    }
  }

  if(!$screen_handler->insert($screen_stream)) {
    print "Error: could not save the screen properly: ".$xoopsDB->error();
  }
}

if(!$ele_id = $element_handler->insert($element)) {
  print "Error: could not save the display settings for element: ".$xoopsDB->error();
}

if($_POST['reload_element_pages']) {
  print "/* evalnow */ if(redirect=='') { redirect = 'reloadWithScrollPosition();'; }";
}

