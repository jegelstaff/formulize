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

require_once "../../../mainfile.php";

icms::$logger->disableLogger();
while(ob_get_level()) {
		ob_end_clean();
}

include_once("admin_header.php");

include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";
include_once XOOPS_ROOT_PATH."/class/xoopsformloader.php";
$form_handler = xoops_getmodulehandler('forms', 'formulize');

// setup a smarty object that we can use for templating our own pages

global $icmsConfig, $xoopsDB;
require_once XOOPS_ROOT_PATH.'/class/template.php';
require_once XOOPS_ROOT_PATH.'/class/theme.php';
require_once XOOPS_ROOT_PATH.'/class/theme_blocks.php';
$xoopsThemeFactory = new icms_view_theme_Factory();
$xoopsThemeFactory->allowedThemes = $icmsConfig['theme_set_allowed'];
$xoopsThemeFactory->defaultTheme = $icmsConfig['theme_set'];
$xoTheme = $xoopsThemeFactory->createInstance();
$xoopsTpl = $xoTheme->template;

$oneFormNames = array();
$manyFormNames = array();
$existingOneConnections = array();
$existingManyConnections = array();
$form1Id = intval($_GET['form1Id']);
$form2Ids = (isset($_GET['form2Ids']) AND is_array($_GET['form2Ids']) AND count($_GET['form2Ids']) > 0) ? $_GET['form2Ids'] : array();

$getAllElementsEvenUnDisplayedOnes = true;
$includeTableForms = false;
$targetFormObjects = $form_handler->getAllForms($getAllElementsEvenUnDisplayedOnes, $form2Ids, $includeTableForms);

$formObject = $form_handler->get($form1Id);

if($formObject->getVar('tableform') == false) {
	foreach($targetFormObjects as $thisFormObject) {
		$thisFid = $thisFormObject->getVar('fid');
		$oneFormNames[$thisFid] = $thisFormObject->getSingular();
		$manyFormNames[$thisFid] = $thisFormObject->getPlural();
		// figure out what existing connections this form has to any other form in the set
		$sql = array();
		$sql[] = "SELECT fl_form1_id, fl_relationship FROM ". $xoopsDB->prefix("formulize_framework_links") ." WHERE fl_frame_id = -1 AND fl_form2_id = $thisFid AND fl_form1_id IN (".implode(', ', array_keys($targetFormObjects)).")";
		$sql[] = "SELECT fl_form2_id, fl_relationship FROM ". $xoopsDB->prefix("formulize_framework_links") ." WHERE fl_frame_id = -1 AND fl_form1_id = $thisFid AND fl_form2_id IN (".implode(', ', array_keys($targetFormObjects)).")";
		foreach($sql as $i=>$thisSQL) {
			if($res = $xoopsDB->query($thisSQL)) {
				while($row = $xoopsDB->fetchRow($res)) {
					// always record from the one-to-many perspective, so flip number 3 (many to one) -- although in the primary relationship there should never be type 3 because things are standardized when being recorded
					// one to one connections are necessarily reciprocal
					// one to many.... they often behave fine no matter which direction, but it's probably context dependent
					switch($i) {
						case 0:
							$mainForm = $row[0];
							$otherForm = $thisFid;
							break;
						case 1:
							$mainForm = $thisFid;
							$otherForm = $row[0];
							break;
					}
					if($row[1] == 1) {
						addIfNotInArray($mainForm, $existingOneConnections, $otherForm);
						addIfNotInArray($otherForm, $existingOneConnections, $mainForm);
					} elseif($row[1] == 2) {
						addIfNotInArray($otherForm, $existingManyConnections, $mainForm);
					} else {
						addIfNotInArray($mainForm, $existingManyConnections, $otherForm);
					}
				}
			}
		}
	}

	// possibilities...
	// 1. user is requesting to create a connection through form settings
	// - form 1 is known, form 2 could be any form including form 1, with which form 1 does not aleady have both a one and a many connection
	// 2. user is dragging forms together
	// - we have to let the user put form 1 and 2 together in the order they want
	// - we pass the existing connections, so that the UI can only offer valid choices
}

$content = array();
if(empty($form2Ids)) { // manually creating connection from form settings

	foreach($existingOneConnections[$form1Id] as $oneForm) {
		unset($oneFormNames[$oneForm]);
	}
	foreach($existingManyConnections[$form1Id] as $manyForm) {
		unset($manyFormNames[$manyForm]);
	}

	if(count($oneFormNames) > 0 OR count($manyFormNames) > 0) {
		$content = array(
			'form1Id'=>$form1Id,
			'formTitle'=>$formObject->getVar('title'),
			'formSingular'=>$formObject->getSingular(),
			'oneFormNames'=>array('0'=>'Choose a form') + $oneFormNames,
			'manyFormNames'=>array('0'=>'Choose a form') + $manyFormNames,
			'isSaveLocked'=>sendSaveLockPrefToTemplate()
		);
		$template = "db:admin/relationship_create_connection.html";
	}

} elseif(!empty($form2Ids)) { // click and dragging forms together
	$content = array(
		'isSaveLocked'=>sendSaveLockPrefToTemplate(),
		'existingOneConnections'=>$existingOneConnections,
		'existingManyConnections'=>$existingManyConnections
	);
	foreach($targetFormObjects as $thisFormObject) {
		$content['forms'][] = array(
			'formId'=>$thisFormObject->getVar('fid'),
			'formTitle'=>trans($thisFormObject->getVar('title')),
			'singular'=>trans($thisFormObject->getSingular()),
			'plural'=>trans($thisFormObject->getPlural())
		);
	}
	$template = "db:admin/relationship_create_connection_multi.html";
}

// prepare the contents if there are any
if(!empty($content)) {
	$xoopsTpl->assign("content",$content);
	$xoopsTpl->display($template);
}

function addIfNotInArray($value, &$array, $arrayKey) {
	if(!isset($array[$arrayKey]) OR !in_array($value, $array[$arrayKey])) {
		$array[$arrayKey][] = $value;
	}
}
