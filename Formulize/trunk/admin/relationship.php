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
##  URL: http://www.freeformsolutions.ca/formulize                           ##
##  Project: Formulize                                                       ##
###############################################################################

// this file gets all the data about applications, so we can display the Settings/forms/relationships tabs for applications

include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";

// need to listen for $_GET['aid'] later so we can limit this to just the application that is requested
$aid = intval($_GET['aid']);
$appName = "All forms"; // needs to be set based on aid in future
$elements = array();
if($_GET['frid'] != "new") {
  $frid = intval($_GET['frid']);
  $framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
  $relationshipObject = $framework_handler->get($frid);
  $relationshipName = $relationshipObject->getVar('name');

	// retrieve the names and ids of all forms, and create the form options for the Add Form section
	$formsq = "SELECT id_form, desc_form FROM " . $xoopsDB->prefix("formulize_id") . " ORDER BY desc_form";
	$res = $xoopsDB->query($formsq);
  $i = 0;
	while($array = $xoopsDB->fetchArray($res)) {
		$common['formoptions'][$i]['value'] = $array['id_form'];
		$common['formoptions'][$i]['name'] = $array['desc_form'];
    $i++;
	}


	// **************
	// GET THE MASTER LIST OF LINKS
	// **************

	// initialize the class that can read the ele_value field
	$formulize_mgr =& xoops_getmodulehandler('elements');

	// get a list of all the linked select boxes since we need to know if any fields in these two forms are the source for any links
	$getlinksq = "SELECT id_form, ele_caption, ele_id, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_type=\"select\" AND ele_value LIKE '%#*=:*%' ORDER BY id_form";
	// print "$getlinksq<br>";
	$resgetlinksq = $xoopsDB->query($getlinksq);
	while ($rowlinksq = $xoopsDB->fetchRow($resgetlinksq))
	{
		//print_r($rowlinksq);
		//print "<br>";
		$target_form_ids[] = $rowlinksq[0];
		$target_captions[] = $rowlinksq[1];
		$target_ele_ids[] = $rowlinksq[2];

		// returns an object containing all the details about the form
		$elements =& $formulize_mgr->getObjects2($criteria,$rowlinksq[0]);
	
		// search for the elements where the link exists
		foreach ($elements as $e) {
			$ele_id = $e->getVar('ele_id');
			// if this is the right element, then proceed and get the source of the link
			if($ele_id == $rowlinksq[2]) {
				$ele_value = $e->getVar('ele_value');
				$details = explode("#*=:*", $ele_value[2]);
				$source_form_ids[] = $details[0];

				//get the element ID for the source we've just found
				$sourceq = "SELECT ele_id, ele_caption FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_handle = '" . mysql_real_escape_string($details[1]) . "' AND id_form = '$details[0]'";
				if($ressourceq = $xoopsDB->query($sourceq)) {
					$rowsourceq = $xoopsDB->fetchRow($ressourceq);
					$source_ele_ids[] = $rowsourceq[0];
					$source_captions[] = $rowsourceq[1];
				} else {
					print "Error:  Query failed.  Searching for element ID for the caption $details[1] in form $details[0]";
				}
			}
		}
	}

	// Arrays now set as follows:
	// target_form_ids == the ID of the form where the current linked selectbox resides
	// target_captions == the caption of the current linked selectbox
	// target_ele_ids == the element ID of the current linked selectbox
	// source_form_ids == the ID of the form where the source for the current linked selectbox resides
	// source_captions == the caption of the source for the current linked selectbox
	// source_ele_ids == the element ID of the source for the current linked selectbox

	// each index in those arrays denotes a distinct linked selectbox

	// example:
	// target_form_ids == 11
	// target_captions == Link to Name
	// target_ele_ids == 22
	// source_form_ids == 10
	// source_captions == Name
	// source_ele_ids == 20


  $relationshipLinks = $relationshipObject->getVar('links');
  $links = array();
  $li = 1;
  foreach($relationshipLinks as $relationshipLink) {
    $links[$li]['form1_id'] = printSmart($relationshipLink->getVar('form1'));
    $links[$li]['form2_id'] = printSmart($relationshipLink->getVar('form2'));
    // get names of forms in the link
    $name1q = "SELECT desc_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form = '" . $links[$li]['form1_id'] . "'";
    $res = $xoopsDB->query($name1q);
    $row = $xoopsDB->fetchRow($res);
    $form1 = $row[0];
    $name2q = "SELECT desc_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form = '" . $links[$li]['form2_id'] . "'";
    $res = $xoopsDB->query($name2q);
    $row = $xoopsDB->fetchRow($res);
    $form2 = $row[0];
    $links[$li]['form1'] = printSmart($form1);
    $links[$li]['form2'] = printSmart($form2);
    $links[$li]['key1'] = printSmart($relationshipLink->getVar('key1'));
    $links[$li]['key2'] = printSmart($relationshipLink->getVar('key2'));
    $links[$li]['relationship'] = printSmart($relationshipLink->getVar('relationship'));
    $links[$li]['unifiedDisplay'] = printSmart($relationshipLink->getVar('unifiedDisplay'));
    $links[$li]['common'] = printSmart($relationshipLink->getVar('common'));
    $links[$li]['lid'] = printSmart($relationshipLink->getVar('lid'));


		//determine the contents of the linkage box
		//find all links between these forms, but add User ID as the top value in the box
		// 1. Find all target links for form 1
		// 2. Check if the source is form 2
		// 3. If yes, add to the stack
		// 4. Repeat for form 2, looking for form 1
		// 5. Draw entries in box as follows:
		// form 1 field name/form 2 field name
		// 6. Account for the current link if one is specified, and make that the default selection

		$hits12 = findlink($links[$li]['form1_id'], $links[$li]['form2_id'], $target_form_ids, $source_form_ids);
		$hits21 = findlink($links[$li]['form2_id'], $links[$li]['form1_id'], $target_form_ids, $source_form_ids);
		//print_r($hits12); print_r($hits21);

		// common value option added July 19 2006 -- jwe
    $links[$li]['linkoptions'] = array( );
    $loi = 1;
		if($links[$li]['common'] == 1) {
			// must retrieve the names of the fields, since they won't be in the target and source caps arrays, since those are focused only on the linked fields
			$element_handler =& xoops_getmodulehandler('elements', 'formulize');
			$ele1 = $element_handler->get($links[$li]['key1']);
			$ele2 = $element_handler->get($links[$li]['key2']);
			$name1 = $ele1->getVar('ele_colhead') ? printSmart($ele1->getVar('ele_colhead')) : printSmart($ele1->getVar('ele_caption'));
			$name2 = $ele2->getVar('ele_colhead') ? printSmart($ele2->getVar('ele_colhead')) : printSmart($ele2->getVar('ele_caption'));
      $links[$li]['linkoptions'][$loi]['value'] = $links[$li]['key1'] . "+" . $links[$li]['key2'];
      $links[$li]['linkoptions'][$loi]['name'] = _AM_FRAME_COMMON_VALUES . $name1 . " & " . $name2;
      $loi++;
		}
		buildlinkoptions($hits12, 0, $links[$li]['key1'], $links[$li]['key2'], $target_ele_ids, $source_ele_ids, $target_captions, $source_captions, $links[$li]['linkoptions'], $loi);
		buildlinkoptions($hits21, 1, $links[$li]['key1'], $links[$li]['key2'], $target_ele_ids, $source_ele_ids, $target_captions, $source_captions, $links[$li]['linkoptions'], $loi);			


    $li++;
  }
	$common['links'] = $links;
}


// common values should be assigned to all tabs
$common['name'] = $relationshipName;
$common['frid'] = $frid;

$adminPage['tabs'][1]['name'] = "Settings";
$adminPage['tabs'][1]['template'] = "db:admin/relationship_settings.html";
//$adminPage['tabs'][1]['content'] = $settings + $common;
$adminPage['tabs'][1]['content'] = $common;

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['url'] = "page=application&aid=$aid";
$breadcrumbtrail[2]['text'] = $appName;
$breadcrumbtrail[3]['text'] = $relationshipName;


function findlink($targetform, $sourceform, $target_form_ids, $source_form_ids) {
	//array_splice($hits, 0);
	//array_splice($truehits, 0);
  $hits = array( );
  $truehits = array( );

  /*print "<br>target: " . $targetform . "<br>";
	print "source: " . $sourceform . "<br>targetarray: ";
	print_r($target_form_ids);
	print "<br>sourcearray: ";
	print_r($source_form_ids);*/

	$hits = array_keys($target_form_ids, $targetform);
	foreach($hits as $hit) {
		if($source_form_ids[$hit] == $sourceform) {
			$truehits[] = $hit;
		}
	}
	return $truehits;
}

function buildlinkoptions($links, $invert, $key1, $key2, $target_ele_ids, $source_ele_ids, $target_captions, $source_captions, $linkoptions, $loi) {
	foreach($links as $link) {
		if($invert) {
			$linkoptions[$loi]['value'] = $source_ele_ids[$link] . "+" . $target_ele_ids[$link];
      $linkoptions[$loi]['name'] = printSmart($source_captions[$link],20) . "/" . printSmart($target_captions[$link],20);
		} else { 
			$linkoptions[$loi]['value'] = $target_ele_ids[$link] . "+" . $source_ele_ids[$link];
      $linkoptions[$loi]['name'] = printSmart($source_captions[$link],20) . "/" . printSmart($target_captions[$link],20);
		}
    $loi++;
	}
}
