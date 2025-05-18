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

// this file handles saving of submissions from the relationship_settings page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
    return;
}

$op = $_POST['formulize_admin_op'];
$relationship_id = $_POST['formulize_admin_key'];

if($relationship_id == "new") {
    // create the framework first
    $framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
    $frameworkObject = $framework_handler->create();
    $form_handler = xoops_getmodulehandler('forms','formulize');
    $form1Object = $form_handler->get($processedValues['relationships']['fid1']);
    $form2Object = $form_handler->get($processedValues['relationships']['fid2']);
    $frameworkObject->setVar('name',printSmart($form1Object->getVar('title'))." + ".printSmart($form2Object->getVar('title')));
    if(!$relationship_id = $framework_handler->insert($frameworkObject)) {
        print "Error: could not create the framework";
    }
    $redirectionURL = XOOPS_URL . "/modules/formulize/admin/ui.php?page=relationship&aid=".intval($_POST['aid'])."&frid=$relationship_id";
    if (isset($_POST['fid'])) {
        $redirectionURL .= "&fid=" . intval($_POST['fid']);
    }
    if (isset($_POST['sid'])) {
        $redirectionURL .= "&sid=" . intval($_POST['sid']);
    }
}

// save all changes, the user could have modified links and then clicked add or
// remove which causes a page reload, so preserve all user changes
updateframe($relationship_id);

switch ($op) {
    case "addlink":
        $form1_id = $processedValues['relationships']['fid1'];
        $form2_id = $processedValues['relationships']['fid2'];
        if (addlink($form1_id, $form2_id, $relationship_id)) {
            // send code to client that will to be evaluated
            if(isset($redirectionURL)) {
                print "/* eval */ reloadWithScrollPosition('$redirectionURL');";
            } else {
                print "/* eval */ reloadWithScrollPosition();";
            }
        }
        break;

    case "dellink":
        $lid = $_POST['formulize_admin_lid'];
        if(deletelink($lid, $relationship_id)) {
            // send code to client that will to be evaluated
            print "/* eval */ reloadWithScrollPosition();";
        }
        break;
}


function addlink($form1_id, $form2_id, $relationship_id) {
    global $xoopsDB;
    $success = true;

    // This is the function that makes a new form entry in the framework DB tables
    $forms = array();
    $forms[] = $form1_id;
    $forms[] = $form2_id;

    // write the link to the links table
    $writelink = "INSERT INTO " . $xoopsDB->prefix("formulize_framework_links") .
        " (fl_frame_id, fl_form1_id, fl_form2_id, fl_key1, fl_key2, fl_relationship, fl_unified_delete, fl_common_value)".
        " VALUES ('$relationship_id', '$form1_id', '$form2_id', 0, 0, 2, 0, 0)";
    if(!$res = $xoopsDB->query($writelink)) {
        print "Error: could not write link of $form1_id and $form2_id to the links table for framework $relationship_id";
        $success = false;
    }

    return $success;
}


function deletelink($fl_id, $relationship_id) {
    global $xoopsDB;
    $success = true;

    $sql = "SELECT fl_key1,fl_key2 FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_id='$fl_id'";
    if(!$res = $xoopsDB->query($sql)) {
        // do nothing
    }else{
        $elementIDs = $xoopsDB->fetchArray($res);
        deleteIndex($elementIDs['fl_key1']);
        deleteIndex($elementIDs['fl_key2']);
    }

    $deleteq = "DELETE FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_id='$fl_id'";
    if(!$res = $xoopsDB->queryF($deleteq)) {
        print "Error: link deletion unsuccessful";
        $success = false;
    }

    return $success;
}


function updateframe($relationship_id) {
    global $processedValues;

    // update the frame with the settings specified on the main modframe page
    $unified_delete = array();  // track the delete checkboxes that are saved on, so we know which ones to to save as off
		$one2one_conditional = array();
		$one2one_bookkeeping = array();
    foreach($processedValues['relationships'] as $key => $value) {
        if(substr($key, 0, 3) == "rel") {
            $fl_id = substr($key, 3);
            $unified_delete[$fl_id] = '';
						$one2one_conditional[$fl_id] = '';
						$one2one_bookkeeping[$fl_id] = '';
            updaterels($fl_id, $value);
        }

        if(substr($key, 0, 8) == "linkages") {
            $fl_id = substr($key, 8);
            updatelinks($fl_id, $value);
        }

        if(substr($key, 0, 6) == "delete") {
            $fl_id = substr($key, 6);
            $unified_delete[$fl_id] = '*';
            updatedeletes($fl_id, $value);
        }

				if(substr($key, 0, 11) == "conditional") {
					$fl_id = substr($key, 11);
					$one2one_conditional[$fl_id] = '*';
					updateconditionals($fl_id, $value);
				}

				if(substr($key, 0, 11) == "bookkeeping") {
					$fl_id = substr($key, 11);
					$one2one_bookkeeping[$fl_id] = '*';
					updatebookkeepings($fl_id, $value);
				}

        if(substr($key, 0, 4) == "name") {
            $relationship_handler = xoops_getmodulehandler('frameworks', 'formulize');
            $relationship = $relationship_handler->get($relationship_id);
            $relationship->setVar('name', $value);
            if(!$relationship_handler->insert($relationship)) {
                print "Error: could not update name of the relationship.";
            }
        }
    }

    // save the value of unchecked unified_delete checkboxes
    foreach ($unified_delete as $key => $value) {
        if (!$value == '*') {
            updatedeletes($key, 0);
        }
    }

    foreach ($one2one_conditional as $key => $value) {
			if (!$value == '*') {
					updateconditionals($key, 0);
			}
		}

		foreach ($one2one_bookkeeping as $key => $value) {
			if (!$value == '*') {
					updatebookkeepings($key, 0);
			}
		}
}


function updaterels($fl_id, $value) {
    global $xoopsDB;

    $sql = "UPDATE " . $xoopsDB->prefix("formulize_framework_links") . " SET fl_relationship='$value' WHERE fl_id='$fl_id'";
    if(!$res = $xoopsDB->query($sql)) {
        print "Error: could not update relationship for framework link $fl_id";
    }
}


function updatelinks($fl_id, $value) {
    global $xoopsDB, $processedValues;

    $keys = explode("+", $value);
    if(isset($keys[2]) AND $keys[2] == "common") {
        $common = 1;
    } else {
        $common = $processedValues['relationships']['preservecommon'.$fl_id] == $value ? 1 : 0;
    }

    // if the elements are actually linked together, discard the common flag!
    // also set indexes if necessary
    $element_handler = xoops_getmodulehandler('elements','formulize');
    $elementKeyZero = null;
    $elementKeyOne = null;
    if($keys[0] > 0){
        updateIndex($keys[0]);
        $elementKeyZero = $element_handler->get($keys[0]);
    }
    if($keys[1] > 0){
        updateIndex($keys[1]);
        $elementKeyOne = $element_handler->get($keys[1]);
    }
    if(elementsAreLinked($elementKeyZero, $elementKeyOne)) {
        if($common) {
            print "/* eval */ reloadWithScrollPosition();"; // reload since we're changing what the user has chosen so need to update display
        }
        $common = 0;
    }

    $sql = "UPDATE " . $xoopsDB->prefix("formulize_framework_links") . " SET fl_key1='" . intval($keys[0]) . "', fl_key2='" . intval($keys[1]) . "', fl_common_value='".intval($common)."' WHERE fl_id='".intval($fl_id)."'";
    if(!$res = $xoopsDB->query($sql)) {
        print "Error: could not update key fields for framework link $fl_id";
    }
		$rel = isset($_POST['relationships-rel'.$fl_id]) ? $_POST['relationships-rel'.$fl_id] : 2;
		if($keys[0] AND $keys[1] AND !linkExistsInPrimaryRelationship($common, $rel, $keys[0], $keys[1])) { // must have actual element keys, don't do "user who created" links in primary relationship yet
			$e1Object = _getElementObject($keys[0]);
			$e2Object = _getElementObject($keys[1]);
			$del = (isset($_POST['relationships-delete'.$fl_id]) AND $_POST['relationships-delete'.$fl_id]) ? 1 : 0;
			$con = (isset($_POST['relationships-conditional'.$fl_id]) AND $_POST['relationships-conditional'.$fl_id]) ? 1 : 0;
			$book = (isset($_POST['relationships-bookkeeping'.$fl_id]) AND $_POST['relationships-bookkeeping'.$fl_id]) ? 1 : 0;
			$result = insertLinkIntoPrimaryRelationship($common, $rel, $e1Object->getVar('fid'), $e2Object->getVar('fid'), $keys[0], $keys[1], $del, $con, $book);
			if($result !== true) { // result will be text error message on failure
				print $result;
			}
		}
}

function updatedeletes($fl_id, $value) {
    global $xoopsDB;
    $sql = "UPDATE " . $xoopsDB->prefix("formulize_framework_links") . " SET fl_unified_delete='$value' WHERE fl_id='$fl_id'";
    if(!$res = $xoopsDB->query($sql)) {
        print "Error: could not update unified delete setting for framework link $fl_id";
    }
}

function updateconditionals($fl_id, $value) {
	global $xoopsDB;
	$sql = "UPDATE " . $xoopsDB->prefix("formulize_framework_links") . " SET fl_one2one_conditional='$value' WHERE fl_id='$fl_id'";
	if(!$res = $xoopsDB->query($sql)) {
			print "Error: could not update conditional flag for framework link $fl_id";
	}
}

function updatebookkeepings($fl_id, $value) {
	global $xoopsDB;
	$sql = "UPDATE " . $xoopsDB->prefix("formulize_framework_links") . " SET fl_one2one_bookkeeping='$value' WHERE fl_id='$fl_id'";
	if(!$res = $xoopsDB->query($sql)) {
			print "Error: could not update bookkeeping flag for framework link $fl_id";
	}
}


function updateIndex($elementID){
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $elementObject = $element_handler->get(intval($elementID));

    if(is_object($elementObject)){
        if(strlen($elementObject->has_index()) == 0){
            $elementObject->createIndex();
        }
    }
}


function deleteIndex($elementID){
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $elementObject = $element_handler->get(intval($elementID));
    if(is_object($elementObject)){
        $originalName = $elementObject->has_index();
        if(strlen($originalName) > 0){
            $elementObject->deleteIndex($originalName);
        }
    }
}
