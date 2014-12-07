<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
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
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

// this file listens for incoming formulize_xhr messages, and responds accordingly

require_once "../../mainfile.php"; // initialize the xoops stack so we have access to the user object, etc if necessary
ob_end_clean(); // stop all buffering of output (ie: related to the error logging, and/or xLangauge?)
include_once "../../header.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/common.php";

// check that the user who sent this request is the same user we have a session for now, if not, bail
$sentUid = $_GET['uid'];
if(($xoopsUser AND $sentUid != $xoopsUser->getVar('uid')) OR (!$xoopsUser AND $sentUid !== 0)) {
  exit(); 
}

$GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'] = array();
$GLOBALS['formulize_asynchronousFormDataInAPIFormat'] = array();

// unpack the op 
$op = $_GET['op'];

// validate op
if($op != "check_for_unique_value"
   AND $op != "get_element_option_list"
   AND $op != 'delete_uploaded_file'
   AND $op != 'get_element_html'
   AND $op != 'get_element_value'
   AND $op != 'get_element_row_html'
   AND $op != 'update_derived_value'
   AND $op != 'get_views_for_form'
   AND $op != 'get_form_columns'
  ) {
  exit();
}

// unpack params based on op, and do whatever we're supposed to do
switch($op) {
  case 'check_for_unique_value':
    $value = $_GET['param1'];
    $element = $_GET['param2'];
    $entry = $_GET['param3'];
    
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $elementObject = $element_handler->get($element);
    if(is_object($elementObject)) {
      include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
      $data_handler = new formulizeDataHandler($elementObject->getVar('id_form'));
      $entry_id = $data_handler->findFirstEntryWithValue($element, $value);
      if(is_numeric($entry_id) AND $entry_id != $entry) {
        print 'valuefound';
      } else {
        print 'valuenotfound';
      }
    } else {
      print 'invalidelement';
    }
    break;
  case 'get_element_option_list':
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
  	$elementsq = q("SELECT ele_caption, ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form=" . intval($_GET['fid']) . " AND ele_type != \"ib\" AND ele_type != \"subform\" ORDER BY ele_order");
    $json = "{ \"options\": [";
    $start = true;
  	foreach($elementsq as $oneele) {
      if(!$start) { $json .= ", "; }
      $json .= "{\"id\": \"".$oneele['ele_id']."\", \"value\": \"".printSmart($oneele['ele_caption'])."\"}";
      $start = false;
  	}
    $json .= "]}";
    print $json;
    break;
  case 'delete_uploaded_file':
    $folderName = $_GET['param1'];
    $element_id = $_GET['param2'];
    $entry_id = $_GET['param3'];
    $element_handler = xoops_getmodulehandler('elements','formulize');
    $elementObject = $element_handler->get($element_id);
    $fid = $elementObject->getVar('id_form');
    include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
    $data_handler = new formulizeDataHandler($fid);
    $fileInfo = $data_handler->getElementValueInEntry($entry_id, $elementObject);
    $fileInfo = unserialize($fileInfo);
    $filePath = XOOPS_ROOT_PATH."/uploads/$folderName/".$fileInfo['name'];
    $result = unlink($filePath);
    if($result) {
      $data_handler->writeEntry($entry_id, array($elementObject->getVar('ele_handle')=>''), false, true); // erase the recorded values for this file in the database, false is proxy user, true is force update (on a GET request)
      print "{ \"element_id\": \"$element_id\", \"entry_id\": \"$entry_id\" }";
    }
    break;
  case 'get_element_html':
    include_once XOOPS_ROOT_PATH."/modules/formulize/include/elementdisplay.php";
    displayElement("", formulize_db_escape($_GET['param2']), intval($_GET['param3']));
    break;
  case 'get_element_value':
    $handle = $_GET['param1'];
    $entryId = intval($_GET['param3']);
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
    include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
    $element_handler = xoops_getmodulehandler('elements','formulize');
    $elementObject = $element_handler->get(formulize_db_escape($handle));
    $data_handler = new formulizeDataHandler($elementObject->getVar('id_form'));
    $dbValue = $data_handler->getElementValueInEntry($entryId,$handle);
    $preppedValue = prepvalues($dbValue,$handle,$entryId);
    print getHTMLForList($preppedValue,$handle,$entryId,1);// 1 is a flag to include the icon for switching to an editable element
    break;
  case 'get_element_row_html':
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/elementdisplay.php";
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
    $element_handler = xoops_getmodulehandler('elements','formulize');
    foreach($_GET as $k=>$v) {
      if($k == 'elementId' OR $k == 'entryId' OR $k == 'fid' ) {
	${$k} = $v;
      } elseif($k != 'uid' AND $k != 'op') {
	$keyParts = explode("_", $k); // last one will be the element ID of the in-form value that is being passed back
	$passedEntryId = $keyParts[2];
	$passedElementId = $keyParts[3];
	$passedElementObject = $element_handler->get($passedElementId);
	$handle = $passedElementObject->getVar('ele_handle');
	$databaseReadyValue = prepDataForWrite($passedElementObject, $v);
	$databaseReadyValue = $databaseReadyValue === "{WRITEASNULL}" ? NULL : $databaseReadyValue;
	$GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$passedEntryId][$handle] = $databaseReadyValue;
	$apiFormatValue = prepvalues($databaseReadyValue, $handle, $passedEntryId); // will be an array
	if(is_array($apiFormatValue) AND count($apiFormatValue)==1) {
	  $apiFormatValue = $apiFormatValue[0]; // take the single value if there's only one, same as display function does
	}
	$GLOBALS['formulize_asynchronousFormDataInAPIFormat'][$passedEntryId][$handle] = $apiFormatValue;
      }
    }
    $elementObject = $element_handler->get($elementId);
    $html = "";
    if(security_check($fid, $entryId)) {
      // "" is framework, ie: not applicable
      $deReturnValue = displayElement("", $elementObject, $entryId, false, null, null, false); // false, null, null, false means it's not a noSave element, no screen, no prevEntry data passed in, and do not render the element on screen
      if(is_array($deReturnValue)) {
	$form_ele = $deReturnValue[0];
	$isDisabled = $deReturnValue[1];
	// rendered HTML code below is taken from the formulize classes at the top of include/formdisplay.php
	if($elementObject->getVar('ele_type') == "ib") {// if it's a break, handle it differently...
	  $class = ($form_ele[1] != '') ? " class='".$form_ele[1]."'" : '';
	  if ($form_ele[0]) {
	    $html = "<td colspan='2' $class><div style=\"font-weight: normal;\">" . trans(stripslashes($form_ele[0])) . "</div></td>"; 
	  } else {
	    $html = "<td colspan='2' $class>&nbsp;</td>";
	  }
	} else {
	  $req = !$isDisabled ? intval($elementObject->getVar('ele_req')) : 0;
	  $html = "<td class='head'>";
	  if (($caption = $form_ele->getCaption()) != '') {
	    $html .=
	    "<div class='xoops-form-element-caption" . ($req ? "-required" : "" ) . "'>"
		    . "<span class='caption-text'>{$caption}</span>"
		    . "<span class='caption-marker'>*</span>"
		    . "</div>";
	  }
	  if (($desc = $form_ele->getDescription()) != '') {
		  $html .= "<div class='xoops-form-element-help'>{$desc}</div>";
	  }
	  $html .= "</td><td class='even'>" . $form_ele->render() . "</td>";
	}
	print $html;
      } 
    }
    break;

    case "update_derived_value":
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
    $formID = $_GET['fid'];
    $formRelationID = $_GET['frid'];
    $limitStart = $_GET['limitstart'];
    $GLOBALS['formulize_forceDerivedValueUpdate'] = true;
    $data = getData($formRelationID, $formID,"","AND","",$limitStart,300);
    $GLOBALS['formulize_forceDerivedValueUpdate'] = false;
    print count($data); // return the number of entries found. when this reaches 0, the client will know to stop calling
    break;
  
    case "get_views_for_form":
      
      //This is to respond to an Ajax request from the file screen_list_entries.html
      $framework_handler =& xoops_getmodulehandler('frameworks', 'formulize');
      include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
      include_once XOOPS_ROOT_PATH ."/modules/formulize/class/forms.php";

      $formulizeForm = new formulizeForm();
      error_log(method_exists($formulizeForm, "getFormViews"));

      list($views, $viewNames, $viewFrids, $viewPublished) = $formulizeForm->getFormViews($_POST['form_id']);
      $frameworks = $framework_handler->getFrameworksByForm($_POST['form_id']);

      for ($i = 0; $i <= count($viewNames); $i++) {
	if(!$viewPublished[$i]) { continue; }
	if($viewFrids[$i]) {
          $viewNames[$i] .= " (" . _AM_FORMULIZE_SCREEN_LOE_VIEW_ONLY_IN_FRAME . $frameworks[$viewFrids[$i]]->getVar('name') . ")";
	} else {
          $viewNames[$i] .= " (" . _AM_FORMULIZE_SCREEN_LOE_VIEW_ONLY_NO_FRAME . ")";
	}
      }

      $array = array_map(null, $views, $viewNames);

      echo json_encode($array);
      break;
    
    case "get_form_columns":

      $temp_selectedCols = $_GET['cols'];
      $selectedCols = explode(",", $temp_selectedCols);
      $gperm_handler = &xoops_gethandler('groupperm');
      $member_handler =& xoops_gethandler('member');
      $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);

      $cols = getAllColList($_POST['form_id'], "", $groups);
      
      $columns = array();
      $columns[0] = "Select a column";
    
      foreach($cols as $id=>$arr) {
	foreach($arr as $innerId=>$value) {
	    $columns[$value["ele_id"]] = $value["ele_caption"];
        }
      }
      
      echo json_encode($columns);
      break;
}
