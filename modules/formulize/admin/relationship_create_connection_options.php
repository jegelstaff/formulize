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

if($form1Object = $form_handler->get($form1Id)) {

	$currentPI = $form1Object->getVar('pi');

	// prompt for PI, and exit
	if(!$currentPI AND !$submittedPI) {
		$pioptions = array();
		$captions = $form1Object->getVar('elementCaptions');
		$headings = $form1Object->getVar('elementColheads');
		foreach($form1Object->getVar('elementsWithData') as $elementId) {
			$pioptions[$elementId] = $headings[$elementId] ? trans(strip_tags($headings[$elementId])) : trans(strip_tags($captions[$elementId]));
		}
		$content['formTitle'] = trans($form1Object->getVar('title'));
		$content['defaultpi'] = 0;
		$content['pioptions'] = $pioptions;
		$xoopsTpl->assign('content', $content);
		$xoopsTpl->display("db:admin/primary_identifier_selection.html");
		exit(); // nothing further to do at this moment

	// save PI if applicable
	} elseif(!$currentPI) {
		$form1Object->setVar('pi', $submittedPI);
		if($form_handler->insert($form1Object) == false) {
			print "Error: could not save the Primary Identifier for Form 1";
			exit();
		}
		$currentPI = $submittedPI;
	}

	// prepare the options for the new connection, based on form 1 PI and the elements in the forms already

	// Know PI form 1, and the existing connections, Options could then be:
	// - any linked element in form 2 that points to the same source as a linked element in form 1, as a common value (would be a pair)
	// - any linked element in form 2 that points to any element in form 1 (would be a pair)
	// -- also take vice versa for multiselect linked elements, if relationship is one to many
	// -- also take vice versa for non-multiselect linked elements, if relationship is one to one
	// -- also any linked elements in form 1 that don't point to form 2, with an option to create a matching element in form 2 (common value)
	// - any element in form 2 as a common value to PI form 1 (would be pair of PI form 1 plus an element selector for form 2)
	// - a new element option in selector for form 2 (above) to create a connection to any element in form 1 (defaults to PI):
	// -- textbox (common value), parallel element, or checkboxes or dropdown or autocomplete or multiselect autocomplete (linked element)
	// -- Offer the name of form 1 or the caption of PI/target for name of new element in form 2, or let them type in a name?

 	// IF ONE TO MANY, ADDITIONAL OPTION FOR SUBFORM ELEMENT CREATION
	// OPTION TO EMBED FORM 2 INSIDE FORM 1, SHOW FORM INSIDE - THE OLD "DISPLAY TOGETHER" IDEA
	// - ONLY AVAILABLE IF THERE ISN'T A SUBFORM ELEMENT ALREADY INSIDE FORM 1 THAT SHOWS FORM 2
	// - GIVE AN OPTION FOR WHICH PAGE OF THE DEFAULT SCREEN THE ELEMENT SHOULD SHOW UP ON, OR ADD A NEW PAGE FOR IT

	if($form2Object = $form_handler->get($form2Id)) {

		$form1PIObject = $element_handler->get($currentPI);
		$form1Elements = $form1Object->getVar('elementsWithData');
		$form2Elements = $form2Object->getVar('elementsWithData');
		$form1LinkedElementMetadata = getLinkedElementsAndSource($form1Elements);
		$form2LinkedElementMetadata = getLinkedElementsAndSource($form2Elements);

		$pairs = array();
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
				// linked multi select elements in form 1 if they point to form 2, only if rel is one to many (2)
				if($rel == 2 AND $form1Link['object']->canHaveMultipleValues AND $form1Link['sourceFid'] == $form2Id) {
					addPair($pairs, $form1Link['object'], $form1Link['sourceObject']);
				}
				// linked non multi select elements in form 1 if they point to form 2, only if rel is one to one (1)
				if($rel == 1 AND $form1Link['object']->canHaveMultipleValues == false AND $form1Link['sourceFid'] == $form2Id) {
					addPair($pairs, $form1Link['object'], $form1Link['sourceObject']);
				}
			}
		}

		// new common value pair option based on linked elements in form 1 that don't point to form 2, as long as there isn't already a linked element in form 2 that points to the same source
		// have to do this in separate second loop, so that common source linked elements would have already been catalogued
		foreach($form1LinkedElementMetadata as $form1Link) {
			if($form1Link['sourceFid'] != $form2Id AND !isset($form2LinkSources[$form1Link['sourceHandle']])) {
				addPair($pairs, $form1Link['object'], null, 'new-common-parallel');
			}
		}

		// lists of elements in both forms, and options for making new elements in form 2 referencing elements in form 1
		// if two existing elements are selected, link will need to be common value
		$candidateElementsForm1 = array(
			$currentPI => $form1PIObject->getUIName() . CREATE_CONNECTION_PI_LABEL
		);
		foreach($form1Elements as $elementId) {
			if($elementId == $currentPI) { continue; }
			$form1ElementObject = $element_handler->get($elementId);
			$candidateElementsForm1[$elementId] = $form1ElementObject->getUIName();
		}
		foreach($form2Elements as $elementId) {
			$form2ElementObject = $element_handler->get($elementId);
			$candidateElementsForm2[$elementId] = $form2ElementObject->getUIName();
		}
		$candidateElementsForm2 = array(
			'new-common-textbox' => CREATE_CONNECTION_COMMON_VALUE_TEXTBOX,
			'new-common-parallel' => CREATE_CONNECTION_COMMON_VALUE_PARALLEL,
			'new-linked-single' => CREATE_CONNECTION_LINKED_SINGLE
		);
		if($rel == 2) {
			$candidateElementsForm2['new-linked-multi'] = CREATE_CONNECTION_LINKED_MULTI;
		}
		foreach($form2Elements as $elementId) {
			$form2ElementObject = $element_handler->get($elementId);
			$candidateElementsForm2[$elementId] = $form2ElementObject->getUIName();
			if($elementId == $form2Object->getVar('pi')) {
				$candidateElementsForm2[$elementId] .= CREATE_CONNECTION_PI_LABEL;
			}
		}

		$content = array(
			'linkId'=>'new',
			'type'=>$rel,
			'pairs'=>$pairs,
			'candidates'=>array(
				'form1'=>$candidateElementsForm1,
				'form2'=>$candidateElementsForm2
			)
		);
		$xoopsTpl->assign('content', $content);
		$xoopsTpl->display("db:admin/relationship_create_connection_options.html");
	}

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
	$form2Indicator = is_object($form2ElementObject) ? $form2ElementObject->getVar('ele_id') : $type;
	$key = $form1ElementObject->getVar('ele_id').'-'.$form2Indicator;
	if(!isset($addedAlready[$key])) {
		$addedAlready[$key] = true;
		$pairs[] = array(
			'form1'=>array(
				'elementName'=>$form1ElementObject->getUIName(),
				'elementId'=>$form1ElementObject->getVar('ele_id')
			),
			'form2'=>array(
				'elementName'=>(is_object($form2ElementObject) ? $form2ElementObject->getUIName() : $type),
				'elementId'=>(is_object($form2ElementObject) ? $form2ElementObject->getVar('ele_id') : 'new')
			),
			'type'=>$type
		);
	}
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
