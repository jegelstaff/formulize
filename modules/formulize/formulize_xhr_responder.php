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
icms::$logger->disableLogger();

while(ob_get_level()) {
    ob_end_clean();
}

// check that the user who sent this request is the same user we have a session for now, if not, bail
$sentUid = intval($_GET['uid']);

if(($xoopsUser AND $sentUid != $xoopsUser->getVar('uid')) OR (!$xoopsUser AND $sentUid !== 0)) {
  exit();
}

include_once "../../header.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/common.php";
include XOOPS_ROOT_PATH .'/modules/formulize/include/customCodeForApplications.php';

global $xoopsConfig;
// load the formulize language constants if they haven't been loaded already
if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
    include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
} else {
    include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
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
   AND $op != 'validate_php_code'
   AND $op != 'get_views_for_form'
	 AND $op != 'get_form_screens_for_form'
  ) {
  exit();
}

// unpack params based on op, and do whatever we're supposed to do
switch($op) {

	case 'get_form_screens_for_form':
		$screens = array();
		$screen_handler = xoops_getmodulehandler('screen', 'formulize');
		$criteria_object = new Criteria('type','multiPage');
		$multiPageFormScreens = $screen_handler->getObjects($criteria_object,intval($_GET['fid']));
		$screens = array();
		foreach($multiPageFormScreens as $screen) {
			$screens[] = '{ "sid" : '.$screen->getVar('sid').', "title" : "'.$screen->getVar('title').'"}';
		}
		$screens = '{ "screens" : ['.implode(",",$screens).']}';
		print $screens;
		break;

  case 'check_for_unique_value':
    $value = $_GET['param1'];
    $element = $_GET['param2'];
    $entry = $_GET['param3'];
    $leave = $_GET['param4'];

    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $elementObject = $element_handler->get($element);
    if(is_object($elementObject)) {
      include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
      $data_handler = new formulizeDataHandler($elementObject->getVar('id_form'));
      $entry_id = $data_handler->findFirstEntryWithValue($element, $value);
      if(is_numeric($entry_id) AND $entry_id != $entry) {
        print json_encode(array('val'=>'valuefound', 'key'=>'de_'.$elementObject->getVar('id_form').'_'.$entry.'_'.$elementObject->getVar('ele_id'), 'leave'=>$leave));
      } else {
        print json_encode(array('val'=>'valuenotfound', 'key'=>'de_'.$elementObject->getVar('id_form').'_'.$entry.'_'.$elementObject->getVar('ele_id'), 'leave'=>$leave));
      }
    } else {
      print json_encode(array('val'=>'invalidelement', 'key'=>'de_'.$elementObject->getVar('id_form').'_'.$entry.'_'.$elementObject->getVar('ele_id'), 'leave'=>$leave));
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
    if (!file_exists($filePath) or unlink($filePath)) {
				// erase any thumbnail associated with the file
				$dotPos = strrpos($filePath, '.');
				$fileExtension = strtolower(substr($filePath, $dotPos+1));
				$thumbFilePath = substr_replace($filePath, ".thumb.$fileExtension", $dotPos);
				if(file_exists($thumbFilePath)) {
					unlink($thumbFilePath);
				}
        // erase the recorded values for this file in the database, false is proxy user, true is force update (on a GET request)
        $data_handler->writeEntry($entry_id, array($elementObject->getVar('ele_handle')=>''), false, true);
        print json_encode(array("element_id"=>$element_id, "entry_id"=>$entry_id));
    }
    break;

  case 'get_element_html':
		/*
		Renders elements for display inside lists of entries, because the user clicked the icon
		the GET param0 through param4 are:
		1 - handle
		2 - element_id
		3 - entryId
		4 - fid
		5 - deInstanceCounter
		*/
    include_once XOOPS_ROOT_PATH."/modules/formulize/include/elementdisplay.php";
    displayElement("", formulize_db_escape($_GET['param2']), intval($_GET['param3']));
		print "<input type='hidden' name='detoken_".intval($_GET['param4']).'_'.intval($_GET['param3']).'_'.intval($_GET['param2'])."' value=".$GLOBALS['xoopsSecurity']->createToken(0, 'formulize_display_element_token').">";
    break;

  case 'get_element_value':
    $handle = $_GET['param1'];
    $entryId = intval($_GET['param3']);
    $fid = intval($_GET['param4']);
    $deInstanceCounter = intval($_GET['param5']);
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
    include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
    $element_handler = xoops_getmodulehandler('elements','formulize');
    $elementObject = $element_handler->get(formulize_db_escape($handle));
    $data_handler = new formulizeDataHandler($elementObject->getVar('id_form'));
    $dbValue = $data_handler->getElementValueInEntry($entryId,$handle);
    $preppedValue = prepvalues($dbValue,$handle,$entryId);
    print getHTMLForList($preppedValue,$handle,$entryId,1,0,array(),$fid,0,0,$deInstanceCounter);// 1 is a flag to include the icon for switching to an editable element,two zeros are row and column, which ought to be passed in but we would need them to roundtrip through the whole xhr process?!
    break;

  case 'get_element_row_html':
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/elementdisplay.php";
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
    $sendBackValue = array();
    $element_handler = xoops_getmodulehandler('elements','formulize');
    $elementsToProcess = array();
    foreach($_GET as $k=>$v) {
      if($k == 'elementId' OR $k == 'entryId' OR $k == 'fid' OR $k == 'frid' OR substr($k, 0, 8) == 'onetoone' OR $k == 'sid') { // serveral onetoone keys can be passed back too
        if($k == 'onetooneentries' OR $k == 'onetoonefids') {
            ${$k} = unserialize($v);
        } else {
            ${$k} = $v;
        }
      } elseif(substr($k, 0, 3) == 'de_') {
        $elementsToProcess[$k] = $v;
      }
    }
    foreach($elementsToProcess as $k => $v) {
      $keyParts = explode("_", $k); // ANY KEY PASSED THAT IS THE NAME OF A DE_ ELEMENT IN MARKUP, WILL GET UNPACKED AS A VALUE THAT CAN BE SUBBED IN WHEN DOING LOOKUPS LATER ON. This is because these elements are the elements that might determine how the conditionally rendered element behaves; it might be sensitive to these values.
      $passedEntryId = $keyParts[2];
      $passedElementId = $keyParts[3];
      $passedElementObject = $element_handler->get($passedElementId);
      $handle = $passedElementObject->getVar('ele_handle');
      if(is_string($v) && substr($v, 0, 9)=="newvalue:") {
        $databaseReadyValue = 'new';
      } else {
        $databaseReadyValue = prepDataForWrite($passedElementObject, $v, $entryId);
        $databaseReadyValue = $databaseReadyValue === "{WRITEASNULL}" ? NULL : $databaseReadyValue;
      }
      $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$passedEntryId][$handle] = $databaseReadyValue;
      $apiFormatValue = prepvalues($databaseReadyValue, $handle, $passedEntryId); // will be an array
      if(is_array($apiFormatValue) AND count((array) $apiFormatValue)==1) {
        $apiFormatValue = $apiFormatValue[0]; // take the single value if there's only one, same as display function does
      }
      $GLOBALS['formulize_asynchronousFormDataInAPIFormat'][$passedEntryId][$handle] = $apiFormatValue;
    }

		$screenObject = null;
		$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
		$gperm_handler = xoops_gethandler('groupperm');
		if($sid AND $viewFormPermission = $gperm_handler->checkRight("view_form", $fid, $groups, getFormulizeModId())) {
			$screen_handler = xoops_getmodulehandler('screen', 'formulize');
			if($candidateScreenObject = $screen_handler->get($sid)) {
				if($candidateScreenObject->getVar('fid') == $fid) {
					$screen_handler = xoops_getmodulehandler($candidateScreenObject->getVar('type').'Screen', 'formulize');
					$screenObject = $screen_handler->get($sid);
				}
			}
		}

		// render the elements and package them in JSON
    $jsonSep = '';
    $json = '{ "elements" : [';
    foreach(explode(',',$elementId) as $thisElementId) {
      $elementObject = $element_handler->get($thisElementId);
			if($elementObject->getVar('ele_type') == "derived") {
				// if it's a derived value, we need to do an update of the derived values based on this changed value... but not save it!!
				// When the global formulize_asynchronousFormDataInAPIFormat has values in it, the derived value computation will put
				// the values into asynch space to later be picked up when rendered. Does not write values to the database.
				// However, if derived value code is specifically writing anything anywhere because someone wrote it that way, those writing operations may happen now!
				// People should not be using derived values for that kind of thing. They should be using on_before_save and on_after_save.
				formulize_updateDerivedValues($entryId, $fid, $frid);
			}
      $entryIdToUse = $entryId;
      $originalEntryIdInMarkup = $_GET['entryId'];
      if($onetoonekey) {
        $originalEntryIdInMarkup = $onetooneentries[$elementObject->getVar('id_form')][0];
        $entryIdToUse = $onetooneentries[$elementObject->getVar('id_form')][0];
        if($elementObject->getVar('id_form') != intval($onetoonefid)) {
          // We only invoke this if the element is not in the onetoonefid, because that fid is the one that has the governing element (which determines the entryId normally), and so we need to swap the entryId when rendering the other form.
          // Normally, the entryId we're rendering is the one displayed in the form at load time, elements are dependent on conditions, but always rendered as in that entry.
          // In a one-to-one situation, if the relationship is based on a linked element, we need to render elements from the entry selected in the governing element
          // If the relationship is common value then we need to try to determine which entry is connected to it, if any.
          if(oneToOneRelationshipLinkBasedOnCommonValue($onetoonefrid, $onetoonefids)) {
            $onetooneentries = array($onetoonefid => array($onetooneentries[$onetoonefid][0]));
            $onetoonefids = array($onetoonefid);
            $checkForLinksResults = checkForLinks($onetoonefrid, $onetoonefids, $onetoonefid, $onetooneentries); // listens for asynchronous values in GLOBALS, set above
            $entryIdToUse = $checkForLinksResults['entries'][$fid][0];
            $entryIdToUse = $entryIdToUse ? $entryIdToUse : 'new';
          } else {
            $entryIdToUse = $databaseReadyValue; // use the DB value of the governing element that triggered the conditional check, since that will be the entry selected by the user, that we need to render in the other one to one form
          }
        }
      }
			$html = "";
      $json .= $jsonSep.'{ "handle" : '.json_encode('de_'.$elementObject->getVar('id_form').'_'.$originalEntryIdInMarkup.'_'.$thisElementId); // have to reference the element markup name that was used when originally rendering the page, ugh.
      if(security_check($fid, $entryIdToUse)) {
        $html = renderElement($elementObject, $entryIdToUse, $frid, $screenObject);
        $json .= ', "data" : '.json_encode($html);
      } else {
       	$json .= ', "data" : '.json_encode('{NOCHANGE}');
      }
      $json .= '}';
      $jsonSep = ', '; // set the separator now in case there are more elements to process
    }
    $json .= ']}';
    print $json;
    break;

  case "update_derived_value":
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
    $formID = $_GET['fid'];
    $formRelationID = $_GET['frid'];
    $entryID = isset($_GET['entryId']) ? intval($_GET['entryId']) : "";
    $limitStart = $_GET['limitstart'];
    $limitSize = $_GET['limitsize'] ? intval($_GET['limitsize']) : 50;
    $GLOBALS['formulize_forceDerivedValueUpdate'] = true;
    ob_start();
		$data = gatherDataset(
			$formID,
			filter: $entryID,
			limitStart: $limitStart,
			limitSize: $limitSize,
			frid: $formRelationID
		);
    ob_clean(); // this catches any errors or other output because it would stop the update from running
    $GLOBALS['formulize_forceDerivedValueUpdate'] = false;
    if(isset($_GET['returnElements'])) {
        // instead of returning the count of data that we found, generate the derived value elements of the main form and return those
        $derivedValueMarkup = array();
        $form_handler = xoops_getModuleHandler('forms', 'formulize');
        $element_handler = xoops_getModuleHandler('elements', 'formulize');
        $formObject = $form_handler->get($formID);
        foreach($formObject->getVar('elementTypes') as $elementId=>$elementType) {
            $elementObject = $element_handler->get($elementId);
            $ele_value = $elementObject->getVar('ele_value');
            // if it's derived, or it's text for display and the text for display has dynamic references, then render it and send it back
            if($elementType == 'derived' OR (
                (
                    $elementType == 'areamodif' OR $elementType == 'ib') AND (
                       strstr($ele_value[0], "\$value=") OR strstr($ele_value[0], "\$value =") OR (strstr($ele_value[0], "{") AND strstr($ele_value[0], "}"))
                    )
                )
            ) {
                if($html = renderElement($elementObject, $entryID, $frid, $screenObject)) {
                    $derivedValueMarkup[$elementId] = $html;
                }
            }
        }
        print json_encode($derivedValueMarkup);
    } else {
        print count((array) $data); // return the number of entries found. when this reaches 0, the client will know to stop calling, in the case where this is called by admin UI to update all entries after derived value formulas are changed
    }
    break;


  case "validate_php_code":
        echo formulize_validatePHPCode($_POST["the_code"]);
    break;


  case "get_views_for_form":
    //This is to respond to an Ajax request from the file screen_list_entries.html
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
    include_once XOOPS_ROOT_PATH ."/modules/formulize/class/forms.php";

    $formulizeForm = new formulizeForm();

    list($views, $viewNames, $viewFrids, $viewPublished) = $formulizeForm->getFormViews($_POST['form_id']);
    $sendNames = array();
    foreach($viewNames as $i=>$viewName) {
        if(!$viewPublished[$i]) {
            continue;
        }
        $sendNames[$views[$i]] = $viewName;
    }
    asort($sendNames);
    // make an array where each value is an array made up of the values from the passed arrays, ie: the key from each entry in sendNames, and the value
    // necessary because of how the iteration happens on the receiving end
    $array = array_map(null, array_keys($sendNames), $sendNames);

    echo json_encode($array);
    break;


}

function renderElement($elementObject, $entryId, $frid, $screenObject) {

	$GLOBALS['formulize_asynchronousRendering'][$elementObject->getVar('ele_handle')] = true;
	$deReturnValue = displayElement("", $elementObject, $entryId, false, $screenObject, null, false); // false, means it's not a noSave element, null, false means no prevEntry data passed in, and do not render the element on screen
	unset($GLOBALS['formulize_asynchronousRendering']);

	// element is allowed, so prep some stuff for rendering...
	if(is_array($deReturnValue)) {
		$form_ele = $deReturnValue[0];
		if($elementObject->getVar('ele_req') AND is_object($form_ele)) {
				$form_ele->setRequired();
		}
		$isDisabled = $deReturnValue[1];
		$elementContents = $form_ele;

		// prepare empty form object just for rendering element
		$form = new formulize_themeForm('formulizeAsynchElementRender','','','post', false, $frid, $screenObject);

		// figure out what we've got on our hands to render
		$breakClass = 'head';
		$entryForDEElements = (is_numeric($entryId) AND $entryId) ? $entryId : 'new';
		$renderedElementMarkupName = 'de_'.$elementObject->getVar('id_form').'_'.$entryForDEElements.'_'.$elementObject->getVar('ele_id');
		$elementType = $elementObject->getVar('ele_type');

		if($elementType == "ib") {
			$elementContents = "<div class=\"formulize-text-for-display\">" . trans(stripslashes($form_ele[0])) . "</div>";
			$breakClass = $form_ele[1];

		} elseif($elementType == "grid") {
			$elementContents = renderGrid($elementObject, $entryForDEElements); // won't take into account the existing entry's saved values or the screen config when rendering the consituent elements, but probably doesn't matter.


		// CONDITIONAL SUBFORMS DON'T WORK WHEN SUBFORM IS SET TO ACCORDIONS! JS won't kick in if element is not visible at initial pageload (because of document ready around the instantiation of the accordion section?
		} elseif($elementType == "subform") {
			$elementContents = "";
			if(elementIsAllowedForUserInEntry($elementObject, $entryId)) {
				global $xoopsUser;
				$ele_value = $elementObject->getVar('ele_value');
				$uid = is_object($xoopsUser) ? $xoopsUser->getVar('uid') : 0;
				$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
				$owner = getEntryOwner($entryId, $elementObject->getVar('id_form'));
				$customCaption = $elementObject->getVar('ele_caption');
				$customElements = $ele_value[1] ? explode(",", $ele_value[1]) : "";
				$subUICols = drawSubLinks($ele_value[0], array($ele_value[0] => array()), $uid, $groups, $_GET['frid'], getFormulizeModId(), $elementObject->getVar('id_form'), $entryId, $customCaption, $customElements, $ele_value[2], $ele_value[3], $ele_value[4], $ele_value[5], $owner, $ele_value[6], $ele_value[7], $elementObject->getVar('ele_id'), $ele_value[8], $ele_value[9], $elementObject);
				if(isset($subUICols['single'])) {
					$elementContents = $subUICols['single'];
					$breakClass = "even";
				} else {
					$elementContents = new XoopsFormLabel($subUICols['c1'], $subUICols['c2'], $renderedElementMarkupName);
				}
			}
		}

		// render the element
		if(is_object($elementContents)) {
			$html = $form->_drawElementElementHTML($elementContents);
		} else {
			$form->insertBreakFormulize($elementContents, $breakClass, $renderedElementMarkupName, $elementObject->getVar("ele_handle"));
			$hidden = '';
			$html = '';
			list($html, $hidden) = $form->_drawElements($form->getElements(), $html, $hidden);
		}

		// return the html, or nothing
		if($html) {
			$html = trans($html);
			return $html;
		}
	}
	return false;
}
