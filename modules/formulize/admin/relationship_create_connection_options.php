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
##  URL: http://www.formulize.org                           								 ##
##  Project: Formulize                                                       ##
###############################################################################

include "../../../mainfile.php";
icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}

include_once XOOPS_ROOT_PATH.'/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH.'/header.php';
global $xoopsTpl;

$form1Id = intval($_GET['form1']);
$form2Id = intval($_GET['form2']);
$rel = intval($_GET['rel']);
$submittedPI = intval($_GET['pi']);

$element_handler = xoops_getmodulehandler('elements', 'formulize');
$form_handler = xoops_getmodulehandler('forms', 'formulize');
list($form1Object, $form1PI) = confirmPIAndReturnFormObject($form1Id, $submittedPI); // will prompt user for PI if necessary
list($form2Object, $form2PI) = confirmPIAndReturnFormObject($form2Id, $submittedPI); // will prompt user for PI if necessary

if($form1Object AND $form2Object AND $form1PI AND $form2PI) {

	// prepare the options for the new connection, based on form 1 PI and the elements in the forms already
	// Know PI form 1, and the existing connections, Options could then be:
	// - any linked element in form 2 that points to the same source as a linked element in form 1, as a common value (would be a pair)
	// - any linked element in form 2 that points to any element in form 1 (would be a pair)
	// -- also take vice versa for multiselect linked elements, if relationship is one to many
	// -- also take vice versa for non-multiselect linked elements, if relationship is one to one
	// -- also any linked elements in form 1 that don't point to form 2, with an option to create a matching element in form 2 (common value)
	// -- also, vice versa of above (form 2 links that don't point to form 1, create new matching element)
	// - any element in form 2 as a common value to PI form 1 (would be pair of PI form 1 plus an element selector for form 2)
	// - a new element option in selector for form 2 (above) to create a connection to any element in form 1 (defaults to PI):
	// -- textbox (common value), parallel element, or checkboxes or dropdown or autocomplete or multiselect autocomplete (linked element)

	$form1PIObject = $element_handler->get($form1PI);
	$form1Elements = $form1Object->getVar('elementsWithData');
	$form2Elements = $form2Object->getVar('elementsWithData');
	$form1LinkedElementMetadata = getLinkedElementsAndSource($form1Elements);
	$form2LinkedElementMetadata = getLinkedElementsAndSource($form2Elements);

	$pairs = array();
	$form1LinkSources = array();
	$form2LinkSources = array();
	foreach($form2LinkedElementMetadata as $form2Link) {

		// catalogue the sources of linked elements in form 2
		$form2LinkSources[$form2Link['sourceHandle']] = true;

		// linked elements in form 2 that point to form 1
		if($form2Link['sourceFid'] == $form1Id) {
			addPair($pairs, $form2Link['sourceObject'], $form2Link['object']);
		}

		foreach($form1LinkedElementMetadata as $form1Link) {
			// linked elements in form 2 that point to the same source as linked elements in form 1
			if($form2Link['sourceHandle'] == $form1Link['sourceHandle']) {
				addPair($pairs, $form1Link['object'], $form2Link['object'], 'common');
			}
		}
	}

	foreach($form1LinkedElementMetadata as $form1Link) {

		// catalogue the sources of linked elements in form 1
		$form1LinkSources[$form1Link['sourceHandle']] = true;

		// new common value pair option based on linked elements in form 1 that don't point to form 2, as long as there isn't already a linked element in form 2 that points to the same source
		if($form1Link['sourceFid'] != $form2Id AND !isset($form2LinkSources[$form1Link['sourceHandle']])) {
			addPair($pairs, $form1Link['object'], null, 'new-common-parallel');
		}
		// linked multi select elements in form 1 if they point to form 2, only if rel is one to many (2)
		if($rel == 2 AND $form1Link['object']->canHaveMultipleValues AND $form1Link['sourceFid'] == $form2Id) {
			addPair($pairs, $form1Link['object'], $form1Link['sourceObject']);
		}
		// linked non multi select elements in form 1 if they point to form 2, only if rel is one to one (1)
		if($rel == 1 AND $form1Link['object']->canHaveMultipleValues == false AND $form1Link['sourceFid'] == $form2Id) {
			addPair($pairs, $form1Link['object'], $form1Link['sourceObject']);
		}
	}

	foreach($form2LinkedElementMetadata as $form2Link) {
		// new common value pair option based on linked elements in form 2 that don't point to form 1, as long as there isn't already a linked element in form 1 that points to the same source
		if($form2Link['sourceFid'] != $form1Id AND !isset($form1LinkSources[$form2Link['sourceHandle']])) {
			addPair($pairs, null, $form2Link['object'], 'new-common-parallel');
		}
	}

	// lists of elements in both forms, and options for making new elements in form 2 referencing elements in form 1
	// if two existing elements are selected, link will need to be common value
	$candidateElementsForm1 = array(
		$form1PI => printSmart($form1PIObject->getUIName()) . CREATE_CONNECTION_PI_LABEL
	);
	foreach($form1Elements as $elementId) {
		if($elementId == $form1PI) { continue; }
		$form1ElementObject = $element_handler->get($elementId);
		if($form1ElementObject->hasData) {
			$candidateElementsForm1[$elementId] = printSmart($form1ElementObject->getUIName());
		}
	}
	$candidateElementsForm1['new-common-parallel'] = CREATE_CONNECTION_COMMON_VALUE_PARALLEL;
	$candidateElementsForm2 = array(
		'new-linked-dropdown' => CREATE_CONNECTION_LINKED_DROPDOWN,
		'new-linked-autocomplete' => CREATE_CONNECTION_LINKED_AUTOCOMPLETE,
		'new-linked-multiselect-autocomplete' => CREATE_CONNECTION_LINKED_MULTI_AUTOCOMPLETE,
		'new-linked-checkboxes' => CREATE_CONNECTION_LINKED_CHECKBOXES,
		'new-common-parallel' => CREATE_CONNECTION_COMMON_VALUE_PARALLEL,
		'new-common-textbox' => CREATE_CONNECTION_COMMON_VALUE_TEXTBOX
	);
	if($rel == 1) {
		unset($candidateElementsForm2['new-linked-multiselect-autocomplete']);
		unset($candidateElementsForm2['new-linked-checkboxes']);
	}
	foreach($form2Elements as $elementId) {
		$form2ElementObject = $element_handler->get($elementId);
		if($form2ElementObject->hasData) {
			$candidateElementsForm2[$elementId] = printSmart($form2ElementObject->getUIName());
			if($elementId == $form2Object->getVar('pi')) {
				$candidateElementsForm2[$elementId] .= CREATE_CONNECTION_PI_LABEL;
			}
		}
	}
	$content = array(
		'linkId'=>0,
		'type'=>$rel,
		'form1Title'=>trans($form1Object->getVar('title')),
		'form2Title'=>trans($form2Object->getVar('title')),
		'pairs'=>$pairs,
		'candidates'=>array(
			'form1'=>$candidateElementsForm1,
			'form2'=>$candidateElementsForm2
		)
	);
	$xoopsTpl->assign('content', $content);
	$xoopsTpl->display("db:admin/relationship_create_connection_options.html");
}

/**
 * Put together all the data necessary in a possible pair of elements that could be used in the connection
 * @param array pairs - the array of pairs that we will add to
 * @param object form1ElementObject - the formulize element object for the candidate element in form 1
 * @param mixed form2ElementObject - either the formulize element object for the candidate element in form 2, or null if this pair indicates a new element possibility
 * @param string type - a string flag indicating the type of connection, empty for regular linked pair, 'common' for a common value pair, 'new-common-parallel' for making a new common value element in form 2 that is the same as the element in form 1
 * @return nothing. pairs are added to in this function only if we haven't included the pair already
 */
function addPair(&$pairs, $form1ElementObject, $form2ElementObject, $type='regular') {
	static $addedAlready = array();
	$form1Indicator = is_object($form1ElementObject) ? $form1ElementObject->getVar('ele_id') : $type;
	$form2Indicator = is_object($form2ElementObject) ? $form2ElementObject->getVar('ele_id') : $type;
	$key = $form1Indicator.'-'.$form2Indicator;
	if(!isset($addedAlready[$key])) {
		$addedAlready[$key] = true;
		$pairs[] = array(
			'form1'=>array(
				'elementName'=>(is_object($form1ElementObject) ? $form1ElementObject->getUIName() : generateNewElementPairNameFromType($type, $form2ElementObject)),
				'elementId'=>(is_object($form1ElementObject) ? $form1ElementObject->getVar('ele_id') : 'new')
			),
			'form2'=>array(
				'elementName'=>(is_object($form2ElementObject) ? $form2ElementObject->getUIName() : generateNewElementPairNameFromType($type, $form1ElementObject)),
				'elementId'=>(is_object($form2ElementObject) ? $form2ElementObject->getVar('ele_id') : 'new')
			),
			'type'=>$type
		);
	}
}

/**
 * Take a type for a pairing as passed to the addPair function, and return a readable text for what the type means
 * Only new-common-parallel is used initially
 * @param string type - a string flag indicating the type of connection, empty for regular linked pair, 'common' for a common value pair, 'new-common-parallel' for making a new common value element in form 2 that is the same as the element in form 1
 * @param object otherElementObject - the element object of the element that any new element should connect to or be based on
 * @return string A readable string indicating what the type flag means. To be used in the UI
 */
function generateNewElementPairNameFromType($type, $otherElementObject) {
	$text = "";
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$otherFormObject = $form_handler->get($otherElementObject->getVar('fid'));
	switch($type) {
		case 'common':
			$text = sprintf(_AM_SETTINGS_NEW_CONNECTION_COMMON, "<span class='italic'>".trans($otherFormObject->getVar('title')) . ": ". $otherElementObject->getUIName()."</span>");
			break;
		case 'new-common-parallel':
			$text = sprintf(_AM_SETTINGS_NEW_CONNECTION_COMMON_PARALLEL, "<span class='italic'>".trans($otherFormObject->getVar('title')) . ": ". $otherElementObject->getUIName()."</span>");
			break;
		case 'regular':
		default:
			$text = sprintf(_AM_SETTINGS_NEW_CONNECTION_REGULAR, "<span class='italic'>".trans($otherFormObject->getVar('title')) . ": ". $otherElementObject->getUIName()."</span>");
			break;
	}
	return $text;
}

/**
 * Given an array of element Ids, returns a structured array with the element id, element colhead/caption, and link target form and target element, if and only if the element is linked
 * @param array elementIdsArray - an array of element id numbers
 * @return array An array of metadata about the linked elements
 */
function getLinkedElementsAndSource($elementIdsArray) {
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
	$linkedElementMetadata = array();
	foreach($elementIdsArray as $elementId) {
		if($elementObject = $element_handler->get($elementId)) {
			if($elementObject->isLinked) {
				$ele_value = $elementObject->getVar('ele_value');
				$boxproperties = explode("#*=:*", $ele_value[2]);
      	$sourceFid = $boxproperties[0];
      	$sourceHandle = $boxproperties[1];
      	$linkedElementMetadata[$elementId] = array(
					'object'=>$elementObject,
					'sourceFid'=>$sourceFid,
					'sourceHandle'=>$sourceHandle,
					'sourceObject'=>$element_handler->get($sourceHandle)
				);
			}
		}
	}
	return $linkedElementMetadata;
}

/**
 * Gather element options from a form object for the PI, put into template and exit so user can choose
 * Alternatively, if there are no elements in the form, make a textbox called Name and use that as the PI
 * @param object formObject - The form object we're dealing with to set the PI
 * @return mixed Either returns nothing because PHP has exited by the end if we need to prompt, or returns the element ID of the newly created element
 */
function promptForPIAndExit($formObject) {
	global $xoopsTpl;
	$elementId = false;
	$pioptions = array();
	$captions = $formObject->getVar('elementCaptions');
	$headings = $formObject->getVar('elementColheads');
	foreach($formObject->getVar('elementsWithData') as $elementId) {
		$pioptions[$elementId] = $headings[$elementId] ? trans(strip_tags($headings[$elementId])) : trans(strip_tags($captions[$elementId]));
	}

	// prompt user to pick from existing elements
	if(!empty($pioptions)) {
		$content['formTitle'] = trans($formObject->getVar('title'));
		$content['defaultpi'] = 0;
		$content['pioptions'] = $pioptions;
		$xoopsTpl->assign('content', $content);
		$xoopsTpl->display("db:admin/primary_identifier_selection.html");
		exit(); // nothing further to do at this moment

	// No elements, so make a Name textbox and use that
	} else {
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		$element = $element_handler->create();
		$ele_handle = substr($formObject::sanitize_handle_name(strtolower($formObject->getVar('form_handle')."_"._AM_ITEMNAME)), 0, 40);
		$firstUniqueCheck = true;
		while (!$uniqueCheck = $form_handler->isElementHandleUnique($ele_handle)) {
			if ($firstUniqueCheck) {
				$ele_handle = $ele_handle . "_".$formObject->getVar('fid');
				$firstUniqueCheck = false;
			} else {
					$ele_handle = $ele_handle . "_copy";
			}
		}
		$config_handler = xoops_gethandler('config');
    $formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
		$element->hasData = true;
    $element->isSystemElement = false;
		$element->isLinked = false;
		$element->setVar('id_form', $formObject->getVar('fid'));
		$element->setVar('ele_caption', _AM_ITEMNAME);
		$element->setVar('ele_handle', $ele_handle);
		$element->setVar('ele_order', 1);
		$element->setVar('ele_display', 1);
		$ele_value = array();
		$ele_value[0] = $formulizeConfig['t_width'];
		$ele_value[1] = $formulizeConfig['t_max'];
		$ele_value[3] = 0;
		$ele_value[5] = isset($formulizeConfig['number_decimals']) ? $formulizeConfig['number_decimals'] : 0;
		$ele_value[6] = isset($formulizeConfig['number_prefix']) ? $formulizeConfig['number_prefix'] : '';
		$ele_value[7] = isset($formulizeConfig['number_decimalsep']) ? $formulizeConfig['number_decimalsep'] : '.';
		$ele_value[8] = isset($formulizeConfig['number_sep']) ? $formulizeConfig['number_sep'] : ',';
		$ele_value[10] = isset($formulizeConfig['number_suffix']) ? $formulizeConfig['number_suffix'] : '';
		$ele_value[12] = 1; // Default trim option to enabled
		$element->setVar('ele_value', $ele_value); // does not need to be serialized, because element handler insert method applies cleanVars to everything, which will serialize it for us, and we don't want double serialization!
		$element->setVar('ele_type', 'text');
		$fieldDataType = 'text';
		if($elementId = $element_handler->insert($element)) { // false on failure, element id on success
			if($form_handler->insertElementField($element, $fieldDataType)) {
				$formObject->setVar('pi', $elementId);
				if($form_handler->insert($formObject)) {
					if(addElementToMultipageScreens($formObject->getVar('fid'), $elementId) == false) {
						print "Error: could add Name element to one or more screens on form ".$formObject->getVar('fid').". Please contact info@formulize.org for assistance.";
					}
				} else {
					print "Error: could not update the PI on form ".$formObject->getVar('fid')." with the new Name element. Please contact info@formulize.org for assistance.";
				}
			} else {
				print "Error: could not create the field in the database for the Name element (PI) on form ".$formObject->getVar('fid').". Please contact info@formulize.org for assistance.";
			}
		} else {
			print "Error: could not save the Name element (PI) on form ".$formObject->getVar('fid').". Please contact info@formulize.org for assistance.";
		}
	}
	return $elementId;
}

/**
 * Make sure a form has a PI, and if not, prompt the user for one. Otherwise, return the form object and the PI
 * May cause an early exit if we need to prompt for the PI
 * Called in order, first for form 1, second for form 2, which is important
 * @param int formId - the id number of the form we're concerned with
 * @param int submittedId - the ID number of an element that was submitted to us as the PI for the form. Passed by reference and cleared to zero if saved successfully.
 * @return array Returns an array of form object and PI. Returns false, false if form id was invalid.
 */
function confirmPIAndReturnFormObject($formId, &$submittedPI) {
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	if($formObject = $form_handler->get($formId)) {
		$formPI = $formObject->getVar('pi');
		if(!$formPI) {
			if($submittedPI) {
				$formObject->setVar('pi', $submittedPI);
				if(!$result = $form_handler->insert($formObject)) {
					print "Error: could not save the Primary Identifier for Form 1";
				} else {
					$formPI = $submittedPI;
				}
				$submittedPI = 0; // kill it because we can only use the submitted PI once, on the appropriate form
			} else {
				$formPI = promptForPIAndExit($formObject);
			}
		}
		return array($formObject, $formPI);
	}
	return array(false, false);
}
