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
global $xoopsUser;
if(($xoopsUser AND $sentUid != $xoopsUser->getVar('uid')) OR (!$xoopsUser AND $sentUid !== 0)) {
  exit();
}


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
	 AND $op != 'group_member_search'
	 AND $op != 'entry_group_search'
	 AND $op != 'render_conditions_filter_ui'
  ) {
  exit();
}

// unpack params based on op, and do whatever we're supposed to do
switch($op) {

	case 'render_conditions_filter_ui':
		// Re-render a standard Formulize conditions filter UI from the currently submitted condition fields,
		// without a full page reload and without persisting anything. Reusable by any conditions filter UI:
		// the caller posts the existing condition fields plus the parameters below, and gets back fresh HTML.
		//   filter_ui_name       - the scope/name of the filter (same name passed to formulize_createFilterUI), letters/numbers only
		//   filter_ui_delete_key - the name of the hidden field carrying the "delete this condition" flag
		//   filter_ui_fid        - the id of the form whose elements populate the filter's field dropdown
		//   filter_ui_frid       - (optional) form relationship id, to include elements from linked forms
		include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
		$filterName = preg_replace('/[^a-zA-Z0-9]/', '', (string) $_POST['filter_ui_name']); // no underscores/special chars (underscores break delete-index parsing)
		$deleteKey = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $_POST['filter_ui_delete_key']);
		$filterFid = intval($_POST['filter_ui_fid']);
		$filterFrid = intval($_POST['filter_ui_frid']);
		if(!$filterName OR !$filterFid) {
			exit();
		}
		// the user must be allowed to edit this form in order to build filters against its data
		$gperm_handler = xoops_gethandler('groupperm');
		$module_handler = xoops_gethandler('module');
		$formulizeModule = $module_handler->getByDirname('formulize');
		$filterGroups = is_object($xoopsUser) ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
		if(!$gperm_handler->checkRight("edit_form", $filterFid, $filterGroups, $formulizeModule->getVar('mid'))) {
			exit();
		}
		$parsedFilterConditions = $deleteKey ? parseSubmittedConditions($filterName, $deleteKey) : parseSubmittedConditions($filterName);
		$filterConditions = is_array($parsedFilterConditions) ? $parsedFilterConditions[0] : "";
		print formulize_createFilterUI($filterConditions, $filterName, $filterFid, "form-".$filterFid, $filterFrid);
		break;

	case 'get_form_screens_for_form':

		$fid = intval($_GET['fid']);

		// the user must be allowed to edit this form in order to build filters against its data
		$gperm_handler = xoops_gethandler('groupperm');
		$module_handler = xoops_gethandler('module');
		$formulizeModule = $module_handler->getByDirname('formulize');
		$filterGroups = is_object($xoopsUser) ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
		if(!$gperm_handler->checkRight("edit_form", $fid, $filterGroups, $formulizeModule->getVar('mid'))) {
			exit();
		}

		$screen_handler = xoops_getmodulehandler('screen', 'formulize');
		$criteria_object = new Criteria('type','multiPage');
		$multiPageFormScreens = $screen_handler->getObjects($criteria_object, $fid);
		$screens = array();
		foreach($multiPageFormScreens as $screen) {
			$screens[] = array('sid' => intval($screen->getVar('sid')), 'title' => $screen->getVar('title'));
		}
		print json_encode(array('screens' => $screens));
		break;

  case 'check_for_unique_value':
    $value = $_GET['param1'];
    $element = $_GET['param2'];
    $entry = $_GET['param3'];
    $leave = $_GET['param4'];

		if($entry != 'new' AND !is_numeric($entry)) {
			throw new Exception('Invalid entry ID passed to check_for_unique_value');
		}

    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $elementObject = $element_handler->get($element);
    if(is_object($elementObject)) {

			if($elementObject->getVar('ele_type') == 'userAccountEmail'
			OR $elementObject->getVar('ele_type') == 'userAccountUsername'
			OR $elementObject->getVar('ele_type') == 'userAccountPhone') {

				if($elementObject->getVar('ele_type') == 'userAccountUsername') {
					// The Username element stores its value in the ImpressCMS login field
					// (login_name), not the display-name field (uname) — see
					// userAccountUsernameElement::$userProperty. Uniqueness must therefore
					// be checked against login_name, otherwise duplicate logins are allowed.
					$keyField = 'login_name';
					$table = "users";
				}elseif($elementObject->getVar('ele_type') == 'userAccountEmail') {
					$keyField = 'email';
					$table = "users";
				} elseif($elementObject->getVar('ele_type') == 'userAccountPhone') {
					$value = preg_replace('/[^0-9]/', '', $value);
					$keyField = '2faphone';
					$table = "profile_profile";
				}
				// For existing entries, exclude the current user's own record from the uniqueness check
				$excludeClause = '';
				global $xoopsDB;
				if($entry != 'new' AND is_numeric($entry)) {
					$fid = $elementObject->getVar('fid');
					$form_handler = xoops_getmodulehandler('forms', 'formulize');
					$isUserTableForm = ($formObject = $form_handler->get($fid) AND $formObject->isSystemUsersTableForm()) ? true : false;
					if ($isUserTableForm) {
						// The system users form uses uid as its primary key — entry IS the uid
						$entryUserId = intval($entry);
					} else {
						$data_handler = new formulizeDataHandler($fid);
						$entryUserId = intval($data_handler->getElementValueInEntry($entry, 'formulize_user_account_uid_'.$fid));
					}
					if($entryUserId > 0) {
						$idField = ($table == 'profile_profile') ? 'profileid' : 'uid';
						$excludeClause = " AND `$idField` != $entryUserId";
					}
				}
				$sql = "SELECT COUNT(*) AS count FROM ".$xoopsDB->prefix($table)." WHERE `$keyField` = '".formulize_db_escape($value)."'".$excludeClause;
				if($res = $xoopsDB->query($sql)) {
					$row = $xoopsDB->fetchArray($res);
					if($row['count'] > 0) {
						print json_encode(array('val'=>'valuefound', 'key'=>'de_'.$elementObject->getVar('id_form').'_'.$entry.'_'.$elementObject->getVar('ele_id'), 'leave'=>$leave));
					} else {
						print json_encode(array('val'=>'valuenotfound', 'key'=>'de_'.$elementObject->getVar('id_form').'_'.$entry.'_'.$elementObject->getVar('ele_id'), 'leave'=>$leave));
					}
				} else {
					print json_encode(array('val'=>'invalidsql', 'key'=>'de_'.$elementObject->getVar('id_form').'_'.$entry.'_'.$elementObject->getVar('ele_id'), 'leave'=>$leave));
				}

			} else {
				include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
				$data_handler = new formulizeDataHandler($elementObject->getVar('id_form'));
				$entry_id = $data_handler->findFirstEntryWithValue($element, $value);
				if(is_numeric($entry_id) AND $entry_id != $entry) {
					print json_encode(array('val'=>'valuefound', 'key'=>'de_'.$elementObject->getVar('id_form').'_'.$entry.'_'.$elementObject->getVar('ele_id'), 'leave'=>$leave));
				} else {
					print json_encode(array('val'=>'valuenotfound', 'key'=>'de_'.$elementObject->getVar('id_form').'_'.$entry.'_'.$elementObject->getVar('ele_id'), 'leave'=>$leave));
				}
			}

    } else {
      print json_encode(array('val'=>'invalidelement', 'key'=>'de_'.$elementObject->getVar('id_form').'_'.$entry.'_'.$elementObject->getVar('ele_id'), 'leave'=>$leave));
    }
    break;

  case 'get_element_option_list':
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
    $elementsq = q("SELECT ele_caption, ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form=" . intval($_GET['fid']) . " AND ele_type != \"ib\" AND ele_type != \"subformFullForm\" AND ele_type != \"subformEditableRow\" AND ele_type != \"subformListings\" ORDER BY ele_order");
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
    $folderName = basename((string) $_GET['param1']);
    $element_id = intval($_GET['param2']);
    $entry_id = intval($_GET['param3']);
    $element_handler = xoops_getmodulehandler('elements','formulize');
    $elementObject = $element_handler->get($element_id);
    if (!is_object($elementObject)) { break; } // unknown element id
    $fid = $elementObject->getVar('id_form');
    include_once XOOPS_ROOT_PATH . "/modules/formulize/class/usersGroupsPerms.php";
    $dufUid = $xoopsUser ? intval($xoopsUser->getVar('uid')) : 0;
    if (!formulizePermHandler::user_can_edit_entry($fid, $dufUid, $entry_id)) { break; }
    include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
    $data_handler = new formulizeDataHandler($fid);
    $fileInfo = $data_handler->getElementValueInEntry($entry_id, $elementObject);
    $fileInfo = unserialize($fileInfo);
    $filePath = XOOPS_ROOT_PATH."/uploads/$folderName/".basename((string) $fileInfo['name']);
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
    include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php"; // security_check()
    $gehElementId = formulize_db_escape($_GET['param2']);
    $gehEntryId = intval($_GET['param3']);
    $gehElement = xoops_getmodulehandler('elements','formulize')->get($gehElementId);
    if (!is_object($gehElement) OR !security_check(intval($gehElement->getVar('id_form')), $gehEntryId)) { break; }
    displayElement("", $gehElementId, $gehEntryId);
		print "<input type='hidden' name='detoken_".intval($_GET['param4']).'_'.intval($_GET['param3']).'_'.intval($_GET['param2'])."' value=".$GLOBALS['xoopsSecurity']->createToken(0, 'formulize_display_element_token').">";
    break;

  case 'get_element_value':
    $handle = $_GET['param1'];
    $entryId = intval($_GET['param3']);
    $fid = intval($_GET['param4']);
    $deInstanceCounter = intval($_GET['param5']);
		$textWidth = isset($_GET['param6']) ? intval($_GET['param6']) : 0;
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
    include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
    $element_handler = xoops_getmodulehandler('elements','formulize');
    $elementObject = $element_handler->get(formulize_db_escape($handle));
    if (!is_object($elementObject) OR !security_check(intval($elementObject->getVar('id_form')), $entryId)) { break; }
    $data_handler = new formulizeDataHandler($elementObject->getVar('id_form'));
    $dbValue = $data_handler->getElementValueInEntry($entryId,$handle);
    $preppedValue = prepvalues($dbValue,$handle,$entryId);
    print getHTMLForList($preppedValue,$handle,$entryId,1,$textWidth,array(),$fid,0,0,$deInstanceCounter);// 1 is a flag to include the icon for switching to an editable element,two zeros are row and column, which ought to be passed in but we would need them to roundtrip through the whole xhr process?!
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
            ${$k} = json_decode($v, true);
				} elseif($k == 'elementId') {
					if(strstr($v,",") !== false) {
						$elementId = array();
						foreach(explode(",",$v) as $thisV) {
							$elementId[] = trim($thisV, "[]");
						}
						$elementId = implode(",",$elementId);
					} else {
						$elementId = trim($v, "[]");
					}
        } else {
            ${$k} = $v;
        }
      } elseif(substr($k, 0, 3) == 'de_') {
        $elementsToProcess[$k] = $v;
      }
    }
    // normalize the request-supplied ids: fid is always numeric; the primary entry id is numeric
    // except for the special 'new'/'proxy' markers, which must be preserved for security_check and rendering
    $fid = intval($fid);
    if($entryId !== 'new' AND $entryId !== 'proxy') {
      $entryId = intval($entryId);
    }

    $entryAuthorized = security_check($fid, $entryId);
    foreach($entryAuthorized ? $elementsToProcess : array() as $k => $v) {
      $keyParts = explode("_", $k); // ANY KEY PASSED THAT IS THE NAME OF A DE_ ELEMENT IN MARKUP, WILL GET UNPACKED AS A VALUE THAT CAN BE SUBBED IN WHEN DOING LOOKUPS LATER ON. This is because these elements are the elements that might determine how the conditionally rendered element behaves; it might be sensitive to these values.
      $passedEntryId = $keyParts[2];
      $passedElementId = trim($keyParts[3], "[]"); // in case it's an array element, strip the brackets
      $passedElementObject = $element_handler->get($passedElementId);
      $handle = $passedElementObject->getVar('ele_handle');
      if(is_string($v) && substr($v, 0, 9)=="newvalue:") {
        $databaseReadyValue = 'new';
      } else {
        $databaseReadyValue = prepDataForWrite($passedElementObject, $v, $entryId);
        $databaseReadyValue = $databaseReadyValue === "{WRITEASNULL}" ? null : $databaseReadyValue;
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
			if($entryAuthorized AND $entryId AND $entryId != 'new' AND $elementObject->getVar('ele_type') == "derived") {
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
      if($entryAuthorized AND security_check($fid, $entryIdToUse)) {
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
            // re-render the element if it is derived, or if it is a display element whose content has
            // dynamic references (PHP code or {handle} references) that depend on other elements' values.
            // The element type decides whether its content is dynamic, via requiresDynamicRerendering().
            $elementTypeHandler = xoops_getmodulehandler($elementType.'Element', 'formulize');
            if($elementType == 'derived' OR (method_exists($elementTypeHandler, 'requiresDynamicRerendering') AND $elementTypeHandler->requiresDynamicRerendering($ele_value))) {
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
        $vpcGroups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
        if (!$xoopsUser OR !xoops_gethandler('groupperm')->checkRight('system_admin', XOOPS_SYSTEM_GROUP, $vpcGroups)) {
            break;
        }
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


	case 'group_member_search':
		// Search current members or non-members for the group member management widget.
		// System groups table form requires system_admin; EAG forms require view_form on the fid.
		global $xoopsDB;
		$gperm_handler = xoops_gethandler('groupperm');
		$userGroups    = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
		$gmsFid        = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
		$gmsCanSearch  = false;
		if ($gmsFid) {
			$gmsFormHandler = xoops_getmodulehandler('forms', 'formulize');
			$gmsFormObject  = $gmsFormHandler->get($gmsFid);
			if ($gmsFormObject && $gmsFormObject->getVar('entries_are_groups')) {
				$gmsMid       = getFormulizeModId();
				$gmsCanSearch = $xoopsUser && $gperm_handler->checkRight('view_form', $gmsFid, $userGroups, $gmsMid);
			}
		}
		if (!$gmsCanSearch && !$gperm_handler->checkRight('system_admin', XOOPS_SYSTEM_GROUP, $userGroups)) {
			print json_encode(array('error' => 'Permission denied'));
			break;
		}
		$gmsAction     = isset($_GET['action'])  ? trim($_GET['action'])    : '';
		$gmsGroupId    = isset($_GET['groupid']) ? intval($_GET['groupid']) : 0;
		$gmsTerm       = isset($_GET['term'])    ? trim($_GET['term'])      : '';
		$gmsUsersTable = $xoopsDB->prefix('users');
		$gmsGulTable   = $xoopsDB->prefix('groups_users_link');
		if ($gmsGroupId <= 0) {
			print json_encode(array('error' => 'Invalid group'));
			break;
		}
		if ($gmsAction === 'members') {
			require_once XOOPS_ROOT_PATH . '/modules/formulize/class/eagGroupMembersElement.php';
			$gmsLimit = $gmsTerm !== '' ? 200 : 10;
			print json_encode(formulizeEagGroupMembersElementHandler::queryMembers($gmsGroupId, $gmsTerm, $gmsLimit, true));
		} elseif ($gmsAction === 'nonmembers') {
			if (strlen($gmsTerm) < 2) {
				print json_encode(array());
				break;
			}
			$gmsSafe = formulize_db_escape($gmsTerm);
			$gmsRes  = $xoopsDB->query(
				"SELECT u.uid, u.uname, u.name FROM `$gmsUsersTable` u"
				. " WHERE (u.name LIKE '%$gmsSafe%' OR u.uname LIKE '%$gmsSafe%' OR u.email LIKE '%$gmsSafe%')"
				. " AND u.uid NOT IN (SELECT gul.uid FROM `$gmsGulTable` gul WHERE gul.groupid = $gmsGroupId)"
				. " ORDER BY u.name, u.uname LIMIT 50"
			);
			$gmsResults = array();
			while ($gmsRes && $gmsRow = $xoopsDB->fetchArray($gmsRes)) {
				$gmsDisplay   = ($gmsRow['name'] !== '') ? $gmsRow['name'] . ' (' . $gmsRow['uname'] . ')' : $gmsRow['uname'];
				$gmsResults[] = array('uid' => intval($gmsRow['uid']), 'display' => htmlspecialchars($gmsDisplay, ENT_QUOTES, 'UTF-8'));
			}
			print json_encode($gmsResults);
		} else {
			print json_encode(array('error' => 'Unknown action'));
		}
		break;


	case 'entry_group_search':
		// Search entry groups belonging to a template group. Requires system_admin.
		global $xoopsDB;
		$gperm_handler = xoops_gethandler('groupperm');
		$egsUserGroups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
		if (!$gperm_handler->checkRight('system_admin', XOOPS_SYSTEM_GROUP, $egsUserGroups)) {
			print json_encode(array('error' => 'Permission denied'));
			break;
		}
		$egsTemplateGroupId = isset($_GET['template_group_id']) ? intval($_GET['template_group_id']) : 0;
		$egsTerm            = isset($_GET['term']) ? trim($_GET['term']) : '';
		if (!$egsTemplateGroupId) {
			print json_encode(array('error' => 'Invalid template_group_id'));
			break;
		}
		$egsGroupsTable = $xoopsDB->prefix('groups');
		// Get the template group's form_id and name for category-scoped search
		$egsTmplRes = $xoopsDB->query(
			"SELECT form_id, name FROM `$egsGroupsTable` WHERE groupid = $egsTemplateGroupId AND is_group_template = 1 LIMIT 1"
		);
		if (!$egsTmplRes || !($egsTmplRow = $xoopsDB->fetchArray($egsTmplRes)) || !$egsTmplRow['form_id']) {
			print json_encode(array('error' => 'Template group not found or has no associated form'));
			break;
		}
		$egsFormId = intval($egsTmplRow['form_id']);
		// Template group names follow "{prefix} - {CategoryName}"; strip the prefix but keep " - CategoryName"
		$egsTmplName   = $egsTmplRow['name'];
		$egsDashPos    = strrpos($egsTmplName, ' - ');
		$egsCatSuffix  = ($egsDashPos !== false) ? substr($egsTmplName, $egsDashPos) : (' - ' . $egsTmplName);
		$egsCategorySafe = formulize_db_escape($egsCatSuffix);
		$egsResults = array();
		// Limit to entry groups of this category (name ends with " - {CategoryName}")
		$egsWhere = "form_id = $egsFormId AND is_group_template = 0 AND entry_id > 0"
		          . " AND name LIKE '%$egsCategorySafe'";
		if ($egsTerm !== '') {
			$egsSafe  = formulize_db_escape($egsTerm);
			$egsWhere .= " AND name LIKE '%$egsSafe%'";
		}
		$egsRes = $xoopsDB->query(
			"SELECT groupid, name FROM `$egsGroupsTable` WHERE $egsWhere ORDER BY name LIMIT 50"
		);
		while ($egsRes && $egsRow = $xoopsDB->fetchArray($egsRes)) {
			$egsResults[] = array('id' => intval($egsRow['groupid']), 'name' => htmlspecialchars($egsRow['name'], ENT_QUOTES, 'UTF-8'));
		}
		print json_encode($egsResults);
		break;

}

// CONDITIONAL SUBFORMS DON'T WORK WHEN SUBFORM IS SET TO ACCORDIONS! JS won't kick in if element is not visible at initial pageload (because of document ready around the instantiation of the accordion section?)
function renderElement($elementObject, $entryId, $frid, $screenObject) {

	$GLOBALS['formulize_asynchronousRendering'][$elementObject->getVar('ele_handle')] = true;
	$deReturnValue = displayElement("", $elementObject, $entryId, false, $screenObject, null, false); // false, means it's not a noSave element, null, false means no prevEntry data passed in, and do not render the element on screen
	unset($GLOBALS['formulize_asynchronousRendering']);

	// element is allowed, so prep some stuff for rendering...
	if(is_array($deReturnValue)) {
		$form_ele = $deReturnValue[0];
		if($elementObject->getVar('ele_required') AND is_object($form_ele)) {
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

		if(is_string($elementContents) OR is_array($elementContents)) {
			$breakClass = (is_array($elementContents) AND isset($elementContents[1]) AND $elementContents[1]) ? $elementContents[1] : "head";
			$elementContents = (is_array($elementContents) AND isset($elementContents[0]) AND $elementContents[0]) ? $elementContents[0] : $elementContents;
		}

		// render the element
		if(is_object($elementContents)) {
			$html = $form->_drawElementElementHTML($elementContents);
		} elseif($elementContents !== false) {
			$form->insertBreakFormulize(trans(stripslashes($elementContents)), $breakClass, $renderedElementMarkupName, $elementObject->getVar("ele_handle"));
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
