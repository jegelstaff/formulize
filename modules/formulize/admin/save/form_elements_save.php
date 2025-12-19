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

// this file handles saving of submissions from the form_elements page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}
$fid = intval($_POST['formulize_admin_key']);
$form_handler = xoops_getmodulehandler('forms','formulize');
$formObject = $form_handler->get($fid);

// Check if the form is locked down
if(!$formObject OR $formObject->getVar('lockedform')) {
  return;
}

// check if the user has permission to edit the form
if(!$gperm_handler->checkRight("edit_form", $fid, $groups, $mid)) {
  return;
}

// invoke the necessary objects
$element_handler = xoops_getmodulehandler('elements','formulize');

// group elements by id
$processedElements = array();
foreach($processedValues['elements'] as $property=>$values) {
  foreach($values as $key=>$value) {
    $processedElements[$key][$property] = $value;
  }
}

// retrieve all the elements that belong to this form, except anonPasscodes because they are hidden and not shown on the form_elements admin page
$criteria = new Criteria('ele_type', 'anonPasscode', '!=');
$elements = $element_handler->getObjects($criteria,$fid);

// get the new order of the elements...
$newOrder = explode("drawer-2[]=", str_replace("&", "", $_POST['elementorder']));
unset($newOrder[0]);
// newOrder will have keys corresponding to the new order, and values corresponding to the old order

if(count((array) $elements) != count((array) $newOrder)) {
	print "Error: the number of elements being saved did not match the number of elements already in the database";
	return;
}

// modify elements
$oldOrderNumber = 1;
foreach($elements as $element) {
  $ele_id = $element->getVar('ele_id');

  // reset elements to deault
  $element->setVar('ele_required',0);
  $element->setVar('ele_private',0);
  $newOrderNumber = array_search($oldOrderNumber,$newOrder);
  $element->setVar('ele_order',$newOrderNumber);
  if($oldOrderNumber != $newOrderNumber) {
    $_POST['reload_elements'] = 1; // need to reload since the drawer numbers will be out of sequence now
  }
  $oldOrderNumber++;

  // apply settings submitted by user
  foreach($processedElements[$ele_id] as $property=>$value) {
    $element->setVar($property,$value);
  }

	// if there was no display property sent, and there was no custom flag sent, then blank the display settings
	if(!isset($processedElements[$ele_id]['ele_display']) AND !isset($_POST['customDisplayFlag'][$ele_id])) {
		$element->setVar('ele_display',0);
	}

  // presist changes
  if(!$element_handler->insert($element)) {
    print "Error: could not save the form elements properly: ".$xoopsDB->error();
  }
}

// handle any operations
if($_POST['convertelement']) {
  global $xoopsModuleConfig;
  $element =& $element_handler->get($_POST['convertelement']);
	$ele_type = $element->getVar('ele_type');
	$new_ele_value = array();
	if($ele_type == "text") { // converting to textarea
		$ele_value = $element->getVar('ele_value');
		$new_ele_value[0] = $ele_value[2]; // default value
		$new_ele_value[1] = $xoopsModuleConfig['ta_rows'];
		$new_ele_value[2] = $ele_value[0]; // width become cols
		$new_ele_value[3] = $ele_value[4]; // preserve any association that is going on
		$element->setVar('ele_value', $new_ele_value);
		$element->setVar('ele_type', "textarea");
    if( !$element_handler->insert($element)) {
			print "Error: could not complete conversion of the element";
		}
	} elseif($ele_type=="textarea") {
		$ele_value = $element->getVar('ele_value');
		$new_ele_value[0] = $ele_value[2]; // cols become width
		$new_ele_value[1] = $xoopsModuleConfig['t_max'];
		$new_ele_value[2] = $ele_value[0]; // default value
		$new_ele_value[3] = 0; // allow anything (do not restrict to just numbers)
		$new_ele_value[4] = $ele_value[3]; // preserve any association that is going on
		$element->setVar('ele_value', $new_ele_value);
		$element->setVar('ele_type', "text");
		if( !$element_handler->insert($element)) {
			print "Error: could not complete conversion of the element";
		}
	} elseif($ele_type=="radio") {
		$element->setVar('ele_type', "checkbox");
		$ele_value = array(
			5 => null,
			2 => $element->getVar('ele_value'),
			10 => null,
			11 => null,
			12 => null,
			15 => '1',
			17 => null,
			'checkbox_scopelimit' => '0',
			'checkbox_formlink_anyorall' => '0',
			'formlink_scope' => 'all'
		);
		$element->setVar('ele_value', $ele_value);
		if( !$element_handler->insert($element)) {
			print "Error: could not complete conversion of the element";
		} else {
			include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
			$data_handler = new formulizeDataHandler($element->getVar('id_form'));
			if(!$data_handler->convertRadioDataToCheckbox($element)) {
				print "Error: ". _AM_ELE_CHECKBOX_DATA_NOT_READY;
			}
		}
	} elseif($ele_type=="checkbox") {
		$element->setVar('ele_type', "radio");
		$ele_value = $element->getVar('ele_value');
		$element->setVar('ele_value', $ele_value[2]);
		if( !$element_handler->insert($element)) {
			print "Error: could not complete conversion of the element";
		} else {
			include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
			$data_handler = new formulizeDataHandler($element->getVar('id_form'));
			if(!$data_handler->convertCheckboxDataToRadio($element)) {
				print "Error: "._AM_ELE_RADIO_DATA_NOT_READY;
			}
		}
  } elseif($ele_type=="select") {
    $element->setVar('ele_type', 'checkbox');
    $old_ele_value = $element->getVar('ele_value');
    if($element->isLinked) {
      // get all the source values, and make an array of those...ignore filters and so on
      $boxproperties = explode("#*=:*", $old_ele_value[2]);
      $sourceFid = $boxproperties[0];
      $sourceHandle = $boxproperties[1];
      $data_handler = new formulizeDataHandler($sourceFid);
      $options = $data_handler->findAllValuesForField($sourceHandle, "ASC");
      foreach($options as $option) {
	$new_ele_value[$option] = 0;
      }
    } else {
      $new_ele_value = $old_ele_value[2];
    }
    $element->setVar('ele_value', $new_ele_value);
    $element->setVar('ele_delim', 'br');
    if( !$element_handler->insert($element)) {
      print "Error: could not complete conversion of the element";
    }
	}
}

if($_POST['deleteelement']) {
  $element = $element_handler->get($_POST['deleteelement']);
	if($element && $element->getVar('fid') == $fid) { // validate that it's part of this form which they have edit_form perm on!
		$element_handler->delete($element);
		$_POST['reload_elements'] = 1;
	}
}

if($_POST['cloneelement']) {

	// validate that the element exists, the target form exists, and the element belongs to this form that they have edit_form perm on!
	if($originalElementObject = $element_handler->get($_POST['cloneelement'])
		AND $targetFormObject = $form_handler->get(intval($_POST['clonefid']))
		AND $originalElementObject->getVar('fid') == $fid
	) {
		$elementObjectProperties = array();
		foreach($originalElementObject->vars as $key => $value) {

			// fid will always be the target form selected - defaults to current form
			if($key == 'fid' OR $key == 'id_form') {
				$elementObjectProperties['fid'] = intval($_POST['clonefid']);

			// if cloning to the same form, append " - copied" to the caption
			} elseif($key == 'ele_caption' AND $originalElementObject->getVar('fid') == intval($_POST['clonefid'])) {
				$elementObjectProperties['ele_caption'] = sprintf(_AM_COPIED, $originalElementObject->getVar('ele_caption'));

			// if there's a colhead and cloning to the same form, append " - copied" to the colhead
			} elseif($key == 'ele_colhead' AND $originalElementObject->getVar('ele_colhead') AND $originalElementObject->getVar('fid') == intval($_POST['clonefid'])) {
				$elementObjectProperties['ele_colhead'] = sprintf(_AM_COPIED, $originalElementObject->getVar('ele_colhead'));

			// if cloning to the same form, set the ele_order to be after the original element
			} elseif($key == 'ele_order') {
				// position after the element we're cloning, if they're in the same form. Otherwise, element will default to bottom of form.
				if($originalElementObject->getVar('fid') == intval($_POST['clonefid'])) {
					$oldOrder = $originalElementObject->getVar('ele_order')+1.1;
					$elementObjectProperties['ele_order'] = figureOutOrder($originalElementObject->getVar('ele_id'), $oldOrder, intval($_POST['clonefid']));
				}

			// append _copied to the ele_handle if cloning to the same form, otherwise leave empty so handle can be generated on creation
			} elseif($key == 'ele_handle' AND $originalElementObject->getVar('fid') == intval($_POST['clonefid'])) {
				$elementObjectProperties['ele_handle'] = $originalElementObject->getVar('ele_handle').'_'.str_replace(['-',' ','%s'], '', _AM_COPIED);

			} elseif($key != 'ele_id' AND $key != 'ele_handle') { // skip ele_id so a new element will be created, assign all other vars as is (except ele_handle which we leave blank unless set specifically above)
				$elementObjectProperties[$key] = $originalElementObject->getVar($key);
			}
		}
		$dataTypeInformation = $originalElementObject->getDataTypeInformation();
		$dataType = $dataTypeInformation['dataTypeCompleteString'];
		// if moving forms, add to default screens (pages with all elements, triggered by empty array for screenIdsAndPagesForAdding)
		// if staying in same form, add to same screens as original element
		$screenIdsAndPagesForAdding = array();
		if($_POST['clonefid'] == $originalElementObject->getVar('fid')) {
			$screenIdsAndPagesForAdding = $originalElementObject->getScreenIdsAndPages();
		}
		if($clonedElementObject = FormulizeHandler::upsertElementSchemaAndResources($elementObjectProperties, screenIdsAndPagesForAdding: $screenIdsAndPagesForAdding, dataType: $dataType)) {
			$appForRedirect = intval($_POST['aid']);
			if($originalElementObject->getVar('fid') != intval($_POST['clonefid'])) {
				$appForRedirect = intval(formulize_getFirstApplicationForForm(intval($_POST['clonefid']))); // could be no app
			}
  		print "/* eval */ window.location = '".XOOPS_URL."/modules/formulize/admin/ui.php?page=element&ele_id=".$clonedElementObject->getVar('ele_id')."&aid=".$appForRedirect."';";
		}
	}
}

if($_POST['reload_elements']) {
  print "/* eval */ reloadWithScrollPosition();";
}
