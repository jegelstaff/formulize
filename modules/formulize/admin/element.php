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

// this file gets all the data about elements, so we can display the tabs for elements

include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";

// this file contains objects to retrieve screen(s) information for elements

include_once XOOPS_ROOT_PATH."/modules/formulize/class/formScreen.php";

// need to listen for $_GET['aid'] later so we can limit this to just the application that is requested
$aid = intval($_GET['aid']);
$application_handler = xoops_getmodulehandler('applications','formulize');
$form_handler = xoops_getmodulehandler('forms','formulize');
$element_handler = xoops_getmodulehandler('elements', 'formulize');
$config_handler = $config_handler =& xoops_gethandler('config');
$formulizeConfig =& $config_handler->getConfigsByCat(0, getFormulizeModId());

if ($aid == 0) {
    $appName = "Forms with no app";
} else {
    $appObject = $application_handler->get($aid);
    $appName = $appObject->getVar('name');
}

$names = array();
$display = array();
$advanced = array();

$member_handler = xoops_gethandler('member');
$allGroups = $member_handler->getGroups();
$groups = array();
foreach($allGroups as $thisGroup) {
    $groups[$thisGroup->getVar('name')]['id'] = $thisGroup->getVar('groupid');
    $groups[$thisGroup->getVar('name')]['name'] = $thisGroup->getVar('name');
    $formlinkGroups[$thisGroup->getVar('groupid')] = $thisGroup->getVar('name');
}

$firstElementOrder = "";
$advanced['ele_index_show'] = false;
if ($_GET['ele_id'] != "new") {
    $ele_id = intval($_GET['ele_id']);
    $elementObject = $element_handler->get($ele_id);
    $fid = $elementObject->getVar('id_form');
    $defaultOrder = $element_handler->getPreviousElement($elementObject->getVar('ele_order'), $fid);
    if (!$defaultOrder) {
        $firstElementOrder = " selected";
    }
    $colhead = $elementObject->getVar('ele_colhead');
    $caption = $elementObject->getVar('ele_caption', "f"); // the f causes no stupid reformatting by the ICMS core to take place, like making clickable links, etc
    $ele_type = $elementObject->getVar('ele_type');
    $ele_value = $elementObject->getVar('ele_value');
    $ele_use_default_when_blank = intval($elementObject->getVar('ele_use_default_when_blank'));
    $ele_delim = $elementObject->getVar('ele_delim');
    if ($ele_delim != "br" AND $ele_delim != "space" AND $ele_delim != "") {
        $ele_delim_custom_value = $ele_delim;
        $ele_delim = "custom";
    }
    $elementName = $colhead ? printSmart($colhead,30) : printSmart($caption,30);
    $names['ele_caption'] = $caption;
    $names['ele_colhead'] = $colhead;
    $names['ele_handle'] = $elementObject->getVar('ele_handle');
    $names['ele_desc'] = $elementObject->getVar('ele_desc', "f"); // the f causes no stupid reformatting by the ICMS core to take place
    $ele_req = $elementObject->getVar('ele_req');
    $ele_req = removeNotApplicableRequireds($ele_type, $ele_req); // function returns false when the element cannot be required.
    $common['ele_req_on'] = $ele_req === false ? false : true;
    $names['ele_req_no_on'] = $ele_req ? "" : " checked";
    $names['ele_req_yes_on'] = $ele_req ? " checked" : "";
    $ele_display = $elementObject->getVar('ele_display');
    if (strstr($ele_display,",")) {
        foreach(explode(",",trim($ele_display,",")) as $displayGroup) {
            $display['ele_display'][$displayGroup] = " selected";
        }
    } elseif ($ele_display == 1) {
        $display['ele_display']['all'] = " selected";
    } elseif ($ele_display == 0) {
        $display['ele_display']['none'] = " selected";
    }
    $ele_disabled = $elementObject->getVar('ele_disabled');
    if (strstr($ele_disabled,",")) {
        foreach(explode(",",trim($ele_disabled,",")) as $displayGroup) {
            $display['ele_disabled'][$displayGroup] = " selected";
        }
    } elseif ($ele_disabled == 1) {
        $display['ele_disabled']['all'] = " selected";
    } elseif ($ele_disabled == 0) {
        $display['ele_disabled']['none'] = " selected";
    }

    $ele_filtersettings = $elementObject->getVar('ele_filtersettings');
    $filterSettingsToSend = (count((array) $ele_filtersettings) > 0) ? $ele_filtersettings : "";
    $display['filtersettings'] = formulize_createFilterUI($filterSettingsToSend, "elementfilter", $fid, "form-3");
    $display['ele_forcehidden'] = $elementObject->getVar('ele_forcehidden') ? " checked" : "";
    $display['ele_private'] = $elementObject->getVar('ele_private') ? " checked" : "";
    $ele_encrypt = $elementObject->getVar('ele_encrypt');
    if ($ele_type != "subform" AND $ele_type != "grid" AND $ele_type != "ib" AND $ele_type != "areamodif") {
        $advanced['ele_encrypt_no_on'] = $ele_encrypt ? "" : " checked";
        $advanced['ele_encrypt_yes_on'] = $ele_encrypt ? " checked" : "";
        $advanced['ele_encrypt_show'] = true;
        $ele_index = has_index($elementObject,$fid);
        $advanced['original_ele_index'] = strlen($ele_index) > 0;
        $advanced['original_index_name'] = $ele_index;
        $advanced['ele_index_no_on'] = strlen($ele_index) > 0 ? "" : " checked";
        $advanced['ele_index_yes_on'] = strlen($ele_index) > 0 ? " checked" : "";
        $advanced['ele_index_show'] = true;
        $advanced['original_handle'] = $elementObject->getVar('ele_handle');
    }

    if ($type == "text") { // set values for text number options, in case they haven't been set yet for this element
        if (!isset($ele_value[5])) {
            $ele_value[5] = isset($formulizeConfig['number_decimals']) ? $formulizeConfig['number_decimals'] : 0;
        }
        if (!isset($ele_value[6])) {
            $ele_value[6] = isset($formulizeConfig['number_prefix']) ? $formulizeConfig['number_prefix'] : '';
        }
        if (!isset($ele_value[10])) {
            $ele_value[10] = isset($formulizeConfig['number_suffix']) ? $formulizeConfig['number_suffix'] : '';
        }
        if (!isset($ele_value[7])) {
            $ele_value[7] = isset($formulizeConfig['number_decimalsep']) ? $formulizeConfig['number_decimalsep'] : '.';
        }
        if (!isset($ele_value[8])) {
            $ele_value[8] = isset($formulizeConfig['number_sep']) ? $formulizeConfig['number_sep'] : ',';
        }
    }
    if ($type=="derived") {
        if (!isset($ele_value[1])) {
            $ele_value[1] = isset($formulizeConfig['number_decimals']) ? $formulizeConfig['number_decimals'] : 0;
        }
        if (!isset($ele_value[2])) {
            $ele_value[2] = isset($formulizeConfig['number_prefix']) ? $formulizeConfig['number_prefix'] : '';
        }
        if (!isset($ele_value[3])) {
            $ele_value[3] = isset($formulizeConfig['number_decimalsep']) ? $formulizeConfig['number_decimalsep'] : '.';
        }
        if (!isset($ele_value[4])) {
            $ele_value[4] = isset($formulizeConfig['number_sep']) ? $formulizeConfig['number_sep'] : ',';
        }
    }

    $ele_uitext = $elementObject->getVar('ele_uitext');
    $ele_uitextshow = $elementObject->getVar('ele_uitextshow');
} else {
    $fid = intval($_GET['fid']);
    $elementName = "New element";
    $defaultOrder = "bottom";
    $elementObject = false;
    $names['ele_caption'] = $elementName;
    $ele_type = $_GET['type'];
    $ele_value = array();
    $ele_delim = "br";
    $ele_uitext = "";
    $ele_uitextshow = 0;
    $ele_use_default_when_blank = 0;
    global $xoopsModuleConfig;
    switch($ele_type) {
        case("text"):
            $ele_value[0] = $xoopsModuleConfig['t_width'];
            $ele_value[1] = $xoopsModuleConfig['t_max'];
            $ele_value[3] = 0;
            $ele_value[5] = isset($formulizeConfig['number_decimals']) ? $formulizeConfig['number_decimals'] : 0;
            $ele_value[6] = isset($formulizeConfig['number_prefix']) ? $formulizeConfig['number_prefix'] : '';
            $ele_value[7] = isset($formulizeConfig['number_decimalsep']) ? $formulizeConfig['number_decimalsep'] : '.';
            $ele_value[8] = isset($formulizeConfig['number_sep']) ? $formulizeConfig['number_sep'] : ',';
            break;
        case("textarea"):
            $ele_value[1] = $xoopsModuleConfig['ta_rows'];
            $ele_value[2] = $xoopsModuleConfig['ta_cols'];
            break;
        case "derived":
            $ele_value[1] = isset($formulizeConfig['number_decimals']) ? $formulizeConfig['number_decimals'] : 0;
            $ele_value[2] = isset($formulizeConfig['number_prefix']) ? $formulizeConfig['number_prefix'] : '';
            $ele_value[3] = isset($formulizeConfig['number_decimalsep']) ? $formulizeConfig['number_decimalsep'] : '.';
            $ele_value[4] = isset($formulizeConfig['number_sep']) ? $formulizeConfig['number_sep'] : ',';
            break;
        case "subform":
            $ele_value[2] = 1;
            $ele_value[3] = 1;
            $ele_value['simple_add_one_button'] = 1;
            $ele_value['show_delete_button'] = 1;
            $ele_value['show_clone_button'] = 1;
            break;
        case "grid":
            $ele_value[3] = "horizontal";
            $ele_value[5] = 1;
            $ele_value[0] = "caption";
            break;
    }

 $ele_req = removeNotApplicableRequireds($ele_type); // function returns false when the element cannot be required.
    $common['ele_req_on'] = $ele_req === false ? false : true;

    $names['ele_req_no_on'] = " checked";
    $display['ele_display']['all'] = " selected";
    $display['ele_disabled']['none'] = " selected";
    $display['filtersettings'] = formulize_createFilterUI("", "elementfilter", $fid, "form-3");
    $ele_encrypt = 0;
    if ($ele_type != "subform" AND $ele_type != "grid" AND $ele_type != "ib" AND $ele_type != "areamodif") {
        $advanced['ele_encrypt_no_on'] = " checked";
        $advanced['ele_encrypt_show'] = true;
        $ele_index = "";//has_index($elementObject,$fid);
        $advanced['original_ele_index'] = strlen($ele_index) > 0;
        $advanced['original_index_name'] = $ele_index;
        $advanced['ele_index_no_on'] = strlen($ele_index) > 0 ? "" : " checked";
        $advanced['ele_index_yes_on'] = strlen($ele_index) > 0 ? " checked" : "";
        $advanced['ele_index_show'] = true;
    }
    $ele_id = "new";
}

$advanced['ele_use_default_when_blank'] = $ele_use_default_when_blank;
$advanced['datatypeui'] = createDataTypeUI($ele_type, $elementObject,$fid,$ele_encrypt);

$formObject = $form_handler->get($fid);
$formName = printSmart($formObject->getVar('title'), 30);
$formHandle=printSmart($formObject->getVar('form_handle'), 30);

// package up the elements into a list for ordering purposes
$orderOptions = array();
$ele_colheads = $formObject->getVar('elementColheads');
$ele_captions = $formObject->getVar('elementCaptions');
foreach($formObject->getVar('elements') as $elementId) {
    $elementTextToDisplay = $ele_colheads[$elementId] ? printSmart($ele_colheads[$elementId]) : printSmart($ele_captions[$elementId]);
    if ($ele_id != $elementId) {
        $orderOptions[$elementId] = "After: ".$elementTextToDisplay;
    }
}
$names['orderoptions'] = $orderOptions;
$names['defaultorder'] = $defaultOrder;
$names['firstelementorder'] = $firstElementOrder;

// common values should be assigned to all tabs
$common['name'] = '';
$common['ele_id'] = $ele_id;
$common['fid'] = $fid;
$common['formhandle']=$formHandle;
$common['aid'] = $aid;
$common['type'] = $ele_type;
$common['uid'] = $xoopsUser->getVar('uid');

$options = array();
$options['ele_delim'] = $ele_delim;
$options['ele_delim_custom_value'] = $ele_delim_custom_value;
$options['ele_uitext'] = $ele_uitext;
$options['ele_uitextshow'] = $ele_uitextshow;
$options['typetemplate'] = "db:admin/element_type_".$ele_type.".html";

// setup various special things per element, including ele_value
if ($ele_type=='text') {
    $formlink = createFieldList($ele_value[4], true);
    $options['formlink'] = $formlink->render();
} else if ($ele_type=='textarea') {
    $formlink = createFieldList($ele_value[3], true);
    $options['formlink'] = $formlink->render();
} else if ($ele_type=='derived') {
    $derivedOptions = array();
    $allColList = getAllColList($fid);
    foreach($allColList[$fid] as $thisCol) {
        if ($thisCol['ele_colhead'] != "") {
            $derivedOptions[trans($thisCol['ele_colhead'])] = printSmart(trans($thisCol['ele_colhead']));
        } else {
            $derivedOptions[trans(strip_tags($thisCol['ele_caption']))] = printSmart(trans(strip_tags($thisCol['ele_caption'])));
        }
    }
    $listOfElements = new XoopsFormSelect("", 'listofelementsoptions');
    $listOfElements->addOptionArray($derivedOptions);
    $options['listofelementsoptions'] = $listOfElements->render();

    //new relationship dropdown
    $framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
    $allRelationships = $framework_handler->getFrameworksByForm($fid);
    $relationships = array();
    $relationshipIndex = array();
    $relationships[""] = "this form only, no relationship.";
    foreach ($allRelationships as $thisRelationship) {
        $frid = $thisRelationship->getVar('frid');
        if (!isset($relationships[$frid])) {
            $relationships[$frid] = $thisRelationship->getVar('name');
        }
    }
    $listOfRelationships = new XoopsFormSelect("", 'listofrelationshipoptions');
    $listOfRelationships->addOptionArray($relationships);
    $options['listofrelationshipoptions'] = $listOfRelationships->render();
    //end of new relationship
} elseif ($ele_type == "yn") {
    $options['ele_value_yes'] = $ele_value['_YES'];
    $options['ele_value_no'] = $ele_value['_NO'];
} elseif ($ele_type == "subform") {
    
    if(!isset($ele_value['show_delete_button'])) {
        $ele_value['show_delete_button'] = 1;
    }
    if(!isset($ele_value['show_clone_button'])) {
        $ele_value['show_clone_button'] = 1;
    }
    
    $ele_value['enforceFilterChanges'] = isset($ele_value['enforceFilterChanges']) ? $ele_value['enforceFilterChanges'] : 1;
    
    $ele_value[1] = explode(",",$ele_value[1]);
    if (is_string($ele_value['disabledelements'])) {
        $ele_value['disabledelements'] = explode(",",$ele_value['disabledelements']);
    }
    global $xoopsDB;
    $validForms1 = q("SELECT t1.fl_form1_id, t2.desc_form FROM " . $xoopsDB->prefix("formulize_framework_links") . " AS t1, " . $xoopsDB->prefix("formulize_id") . " AS t2 WHERE t1.fl_form2_id=" . intval($fid) . " AND t1.fl_unified_display=1 AND t1.fl_relationship != 1 AND t1.fl_form1_id=t2.id_form");
    $validForms2 = q("SELECT t1.fl_form2_id, t2.desc_form FROM " . $xoopsDB->prefix("formulize_framework_links") . " AS t1, " . $xoopsDB->prefix("formulize_id") . " AS t2 WHERE t1.fl_form1_id=" . intval($fid) . " AND t1.fl_unified_display=1 AND t1.fl_relationship != 1 AND t1.fl_form2_id=t2.id_form");
    $caughtfirst = false;
    foreach($validForms1 as $vf1) {
        $validForms[$vf1['fl_form1_id']] = $vf1['desc_form'];
        if (!$caughtfirst) {
            $firstform = $vf1['fl_form1_id'];
            $caughtfirst = true;
        }
    }
    foreach($validForms2 as $vf2) {
        if (!isset($validForms[$vf2['fl_form2_id']])) {
            $validForms[$vf2['fl_form2_id']] = $vf2['desc_form'];
            if (!$caughtfirst) {
                $firstform = $vf2['fl_form2_id'];
                $caughtfirst = true;
            }
        }
    }
    if (count((array) $validForms) == 0) {
        $validForms['none'] = _AM_ELE_SUBFORM_NONE;
    }
    $options['subforms'] = $validForms;
    if ($caughtfirst) {
        $formtouse = $ele_value[0] ? $ele_value[0] : $firstform; // use the user's selection, unless there isn't one, then use the first form found
        $elementsq = q("SELECT ele_caption, ele_colhead, ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form=" . intval($formtouse) . " AND ele_type != \"grid\" AND ele_type != \"ib\" AND ele_type != \"subform\" ORDER BY ele_order");
        $options['subformUserFilterElements'][0] = _formulize_NONE;
        foreach($elementsq as $oneele) {
            $options['subformelements'][$oneele['ele_id']] = $oneele['ele_colhead'] ? $oneele['ele_colhead'] : printSmart($oneele['ele_caption']);
            $options['subformUserFilterElements'][$oneele['ele_id']] = $oneele['ele_colhead'] ? $oneele['ele_colhead'] : printSmart($oneele['ele_caption']);
        }
    } else {
        $options['subformelements'][0] = "";
        $options['subformUserFilterElements'][0] = "";
    }

    // compile a list of data-entry screens for this form
    $options['subform_screens'] = array();
    $screen_options = q("SELECT sid, title, type FROM ".$xoopsDB->prefix("formulize_screen")." WHERE fid=".intval($formtouse)." and (type='form' OR type='multiPage')");
    $options['subform_screens'][0] = "(Use Default Screen)";
    foreach($screen_options as $screen_option) {
        $options['subform_screens'][$screen_option["sid"]] = $screen_option["title"];
    }

    // setup the UI for the subform conditions filter
    $options['subformfilter'] = formulize_createFilterUI($ele_value[7], "subformfilter", $ele_value[0], "form-2");
} elseif ($ele_type == "grid") {
    $options['background'] = $ele_value[3];
    $options['heading'] = $ele_value[0];
    $options['sideortop'] = $ele_value[5] == 1 ? "side" : "above";
    $grid_elements_criteria = new Criteria('');
    $grid_elements_criteria->setSort('ele_order');
    $grid_elements_criteria->setOrder('ASC');
    $grid_elements = $element_handler->getObjects($grid_elements_criteria, $fid);
    foreach($grid_elements as $this_element) {
        $grid_start_options[$this_element->getVar('ele_id')] = $this_element->getVar('ele_colhead') ? printSmart(trans($this_element->getVar('ele_colhead'))) : printSmart(trans($this_element->getVar('ele_caption')));
    }
    $options['grid_start_options'] = $grid_start_options;

} elseif ($ele_type=="radio") {
    $ele_value = formulize_mergeUIText($ele_value, $ele_uitext);
    $options['useroptions'] = $ele_value;

} elseif ($ele_type=="select") {
    if ($ele_id == "new") {
        $options['listordd'] = 0;
        $options['multiple'] = 0;
        $options['multiple_auto'] = 0;
        $ele_value[0] = 6;
        $options['islinked'] = 0;
        $options['formlink_scope'] = array(0=>'all');
    } else {
        $options['listordd'] = $ele_value[0] == 1 ? 0 : 1;
        $options['listordd'] = $ele_value[8] == 1 ? 2 : $options['listordd'];
        $options['multiple'] = $ele_value[1];
        $options['multiple_auto'] = $ele_value[1];
        if (!is_array($ele_value[2])) {
            $options['islinked'] = 1;
        } else {
            $options['islinked'] = 0;
            if (is_array($ele_uitext) AND count((array) $ele_uitext) > 0) {
                $ele_value[2] = formulize_mergeUIText($ele_value[2], $ele_uitext);
            }
            $options['useroptions'] = $ele_value[2];
        }

        $options['formlink_scope'] = explode(",",$ele_value[3]);
    }

    list($formlink, $selectedLinkElementId) = createFieldList($ele_value[2]);
    $options['linkedoptions'] = $formlink->render();
    
    list($optionsLimitByElement, $limitByElementElementId) = createFieldList($ele_value['optionsLimitByElement'], false, false, "elements-ele_value[optionsLimitByElement]", _NONE);
    $options['optionsLimitByElement'] = $optionsLimitByElement->render();
    if($limitByElementElementId) {
        $limitByElementElementObject = $element_handler->get($limitByElementElementId);
        if($limitByElementElementObject) {
            $options['optionsLimitByElementFilter'] = formulize_createFilterUI($ele_value['optionsLimitByElementFilter'], "optionsLimitByElementFilter", $limitByElementElementObject->getVar('id_form'), "form-2");
        }
    }
    

    // setup the list value and export value option lists, and the default sort order list, and the list of possible default values
    if ($options['islinked']) {
        $linkedMetaDataParts = explode("#*=:*", $ele_value[2]);
        $linkedSourceFid = $linkedMetaDataParts[0];
        if ($linkedSourceFid) {
            // list of elements to display when showing this element in a list
            list($listValue, $selectedListValue) = createFieldList($ele_value[EV_MULTIPLE_LIST_COLUMNS], false, $linkedSourceFid,
                "elements-ele_value[".EV_MULTIPLE_LIST_COLUMNS."]", _AM_ELE_LINKSELECTEDABOVE, true);
            $listValue->setValue($ele_value[EV_MULTIPLE_LIST_COLUMNS]); // mark the current selections in the form element
            $options['listValue'] = $listValue->render();

            // list of elements to display when showing this element as an html form element (in form or list screens)
            list($displayElements, $selectedListValue) = createFieldList($ele_value[EV_MULTIPLE_FORM_COLUMNS], false, $linkedSourceFid,
                "elements-ele_value[".EV_MULTIPLE_FORM_COLUMNS."]", _AM_ELE_LINKSELECTEDABOVE, true);
            $displayElements->setValue($ele_value[EV_MULTIPLE_FORM_COLUMNS]); // mark the current selections in the form element
            $options['displayElements'] = $displayElements->render();

            // list of elements to export to spreadsheet
            list($exportValue, $selectedExportValue) = createFieldList($ele_value[EV_MULTIPLE_SPREADSHEET_COLUMNS], false, $linkedSourceFid,
                "elements-ele_value[".EV_MULTIPLE_SPREADSHEET_COLUMNS."]", _AM_ELE_VALUEINLIST, true);
            $exportValue->setValue($ele_value[EV_MULTIPLE_SPREADSHEET_COLUMNS]); // mark the current selections in the form element
            $options['exportValue'] = $exportValue->render();

            // sort order
            list($optionSortOrder, $selectedOptionsSortOrder) = createFieldList($ele_value[12], false, $linkedSourceFid,
                "elements-ele_value[12]", _AM_ELE_LINKFIELD_ITSELF);
            $options['optionSortOrder'] = $optionSortOrder->render();
            include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
            $linkedDataHandler = new formulizeDataHandler($linkedSourceFid);
            $allLinkedValues = $linkedDataHandler->findAllValuesForField($linkedMetaDataParts[1], "ASC");
            if (!is_array($ele_value[13])) {
                $ele_value[13] = array($ele_value[13]);
            }
            $options['optionDefaultSelectionDefaults'] = $ele_value[13];
            $options['optionDefaultSelection'] = $allLinkedValues; // array with keys as entry ids and values as text
            
            // handle additional linked source mapping options...
            list($thisFormFieldList, $thisFormFieldListSelected) = createFieldList('throwawayvalue', false, $fid, 'throwawayname', 'This Form');
            $options['mappingthisformoptions'] = $thisFormFieldList->getOptions();
            list($sourceFormFieldList, $sourceFormFieldListSelected) = createFieldList('throwawayvalue', false, $linkedSourceFid, 'throwawayname', 'Source Form');
            $options['mappingsourceformoptions'] = $sourceFormFieldList->getOptions();
            $options['linkedSourceMappings'] = $ele_value['linkedSourceMappings'];

        }
    }
    if (!$options['islinked'] OR !$linkedSourceFid) {
        $options['exportValue'] = "";
        $options['listValue'] = "";
        $options['optionSortOrder'] = "";
        $options['optionDefaultSelectionDefaults'] = array();
        $options['optionDefaultSelection'] = "";
    }

    // setup group list:
    $options['formlink_scope_options'] = array('all'=>_AM_ELE_FORMLINK_SCOPE_ALL) + $formlinkGroups;

    // setup conditions:
    $selectedLinkFormId = "";
    if (isset($ele_value[2]['{FULLNAMES}']) OR isset($ele_value[2]['{USERNAMES}'])) {
        if ($formulizeConfig['profileForm']) {
            $selectedLinkFormId = $formulizeConfig['profileForm'];
        }
    }

    if ($selectedLinkElementId) {
        $selectedElementObject = $element_handler->get($selectedLinkElementId);
        if ($selectedElementObject) {
            $options['formlinkfilter'] = formulize_createFilterUI($ele_value[5], "formlinkfilter", $selectedElementObject->getVar('id_form'), "form-2");
        }
    } elseif ($selectedLinkFormId) { // if usernames or fullnames is in effect, we'll have the profile form fid instead
        $options['formlinkfilter'] = formulize_createFilterUI($ele_value[5], "formlinkfilter", $selectedLinkFormId, "form-2");
    }
    if (!$options['formlinkfilter']) {
        $options['formlinkfilter'] = "<p>The options are not linked.</p>";
    }

} elseif ($ele_type=="ib") {
    $options['ib_style_options']['head'] = "head";
    $options['ib_style_options']['form-heading'] = "form-heading";
}


if(!isset($ele_value['snapshot'])) {
    $ele_value['snapshot'] = 0;
}

$options['ele_value'] = $ele_value;

if($elementObject->hasMultipleOptions AND !$elementObject->isLinked) {
    $advanced['hasMultipleOptions'] = true;
    $exportOptions = $elementObject->getVar('ele_exportoptions');
    $advanced['exportoptions_onoff'] = (is_array($exportOptions) AND count((array) $exportOptions) > 0) ? 1 : 0;
    $advanced['exportoptions_hasvalue'] = $exportOptions['indicators']['hasValue'];
    $advanced['exportoptions_doesnothavevalue'] = $exportOptions['indicators']['doesNotHaveValue'];
} else {
    $advanced['exportoptions_onoff'] = 0;
}

// if this is a custom element, then get any additional values that we need to send to the template
$customValues = array();
if (file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$ele_type."Element.php")) {
    $customTypeHandler = xoops_getmodulehandler($ele_type."Element", 'formulize');
    $customValues = $customTypeHandler->adminPrepare($elementObject);
}

$display['groups'] = $groups;


$tabindex = 1;
$adminPage['tabs'][$tabindex]['name'] = _AM_ELE_NAMEANDSETTINGS;
$adminPage['tabs'][$tabindex]['template'] = "db:admin/element_names.html";
$adminPage['tabs'][$tabindex]['content'] = $names+$common;

if ($ele_type!='colorpick') {
    $adminPage['tabs'][++$tabindex]['name'] = "Options";
    $adminPage['tabs'][$tabindex]['template'] = "db:admin/element_options.html";
    if (count((array) $customValues)>0) {
    $adminPage['tabs'][$tabindex]['content'] = $customValues + $options + $common;
    } else {
    $adminPage['tabs'][$tabindex]['content'] = $options + $common;
    }
}

$adminPage['tabs'][++$tabindex]['name'] = _AM_ELE_DISPLAYSETTINGS;
$adminPage['tabs'][$tabindex]['template'] = "db:admin/element_display.html";
$adminPage['tabs'][$tabindex]['content'] = $display + $common;
$formScreenHandler = xoops_getmodulehandler('formScreen', 'formulize');
$adminPage['tabs'][$tabindex]['content']['form_screens'] = $formScreenHandler->getScreensForElement($common['fid']);
$adminPage['tabs'][$tabindex]['content']['multi_form_screens'] = $form_handler->getMultiScreens($common['fid']);
// for new elements, pre-select all of the "filled up" screens
if ($ele_id == "new") {
    $adminPage['tabs'][$tabindex]['content']['ele_form_screens'] = $formScreenHandler->getSelectedScreensForNewElement();
} else {
    // get all default selected form screens in an array
    $adminPage['tabs'][$tabindex]['content']['ele_form_screens'] = $formScreenHandler->getSelectedScreens($common['fid']);
}


if ($advanced['datatypeui'] OR $advanced['ele_encrypt_show']) {
    $adminPage['tabs'][++$tabindex]['name'] = "Advanced";
    $adminPage['tabs'][$tabindex]['template'] = "db:admin/element_advanced.html";
    $adminPage['tabs'][$tabindex]['content'] = $advanced + $common;
}

$adminPage['pagetitle'] = "Element: ".$elementName;
if ($ele_id == "new" AND $ele_type == "select") {
    $adminPage['pagesubtitle'] = _AM_ELE_DROPDORLIST;
} else {
    $adminPage['pagesubtitle'] = "(".convertTypeToText($ele_type, $ele_value).")";
}
$adminPage['needsave'] = true;

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['url'] = "page=application&aid=$aid&tab=forms";
$breadcrumbtrail[2]['text'] = $appName;
$breadcrumbtrail[3]['url'] = "page=form&aid=$aid&fid=$fid&tab=elements";
$breadcrumbtrail[3]['text'] = $formName;
$breadcrumbtrail[4]['text'] = $elementName;

function createDataTypeUI($ele_type, $element,$id_form,$ele_encrypt) {
    // data type controls ... added May 31 2009, jwe
    // only do it for existing elements where the datatype choice is relevant
    // do not do it for encrypted elements
    $renderedUI = "";

    // check if there's a special class file for this element type, and if so, instantiate an element object of that kind, so we can check if it needs a datatype UI or not
    $customTypeNeedsUI = false;
    if (file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$ele_type."Element.php")) {
        $customTypeHandler = xoops_getmodulehandler($ele_type."Element", 'formulize');
        $customTypeObject = $customTypeHandler->create();
        $customTypeNeedsUI = $customTypeObject->needsDataType;
    }

    if (($ele_type == "text" OR $ele_type == "textarea" OR $ele_type == "select" OR $ele_type == "radio" OR $ele_type == "derived" OR $customTypeNeedsUI) AND !$ele_encrypt) {
        if ($element) {
            $defaultTypeInformation = $element->getDataTypeInformation();
            $defaultType = $defaultTypeInformation['dataType'];
            $defaultTypeSize = $defaultTypeInformation['dataTypeSize'];
            //print "defaultType: $defaultType<br>";
            //print "defaultTypeSize: $defaultTypeSize<br>";
            $renderedUI .= "<input type='hidden' name='element_default_datatype' value='$defaultType'>\n";
            $renderedUI .= "<input type='hidden' name='element_default_datatypesize' value='$defaultTypeSize'>\n";
        } else {
            $defaultType = 'text';
            $defaultTypeSize = '';
        }
        // setup the UI for the options...
        $dataTypeTray = new XoopsFormElementTray(_AM_FORM_DATATYPE_CONTROLS, '<br>');
        $dataTypeTray->setDescription(_AM_FORM_DATATYPE_CONTROLS_DESC);
        $textType = new XoopsFormRadio('', 'element_datatype', $defaultType);
        $textDataTypeLabel = (!$element AND ($ele_type == 'text')) ? _AM_FORM_DATATYPE_TEXT_NEWTEXT : _AM_FORM_DATATYPE_TEXT;
        $textType->addOption('text', $textDataTypeLabel);
        $intType = new XoopsFormRadio('', 'element_datatype', $defaultType);
        $intType->addOption('int', _AM_FORM_DATATYPE_INT);
        $decimalType = new XoopsFormRadio('', 'element_datatype', $defaultType);
        $decimalTypeSizeDefault = ($defaultTypeSize AND $defaultType == "decimal") ? $defaultTypeSize : 2;
        $decimalTypeSize = new XoopsFormText('', 'element_datatype_decimalsize', 2, 2, $decimalTypeSizeDefault);
        $decimalTypeSize->setExtra(" style=\"width: 2em;\" "); // style to force width necessary to compensate for silly forced 60% textbox widths in ICMS admin side
        $decimalType->addOption('decimal', _AM_FORM_DATATYPE_DECIMAL1.$decimalTypeSize->render()._AM_FORM_DATATYPE_DECIMAL2);
        $varcharType = new XoopsFormRadio('', 'element_datatype', $defaultType);
        $varcharTypeSizeDefault = ($defaultTypeSize AND $defaultType == 'varchar') ? $defaultTypeSize : 255;
        $varcharTypeSize = new XoopsFormText('', 'element_datatype_varcharsize', 3, 3, $varcharTypeSizeDefault);
        $varcharTypeSize->setExtra(" style=\"width: 3em;\" ");
        $varcharType->addOption('varchar', _AM_FORM_DATATYPE_VARCHAR1.$varcharTypeSize->render()._AM_FORM_DATATYPE_VARCHAR2);
        $charType = new XoopsFormRadio('', 'element_datatype', $defaultType);
        $charTypeSizeDefault = ($defaultTypeSize AND $defaultType == 'char') ? $defaultTypeSize : 255;
        $charTypeSize = new XoopsFormText('', 'element_datatype_charsize', 3, 3, $charTypeSizeDefault);
        $charTypeSize->setExtra(" style=\"width: 3em;\" ");
        $charType->addOption('char', _AM_FORM_DATATYPE_CHAR1.$charTypeSize->render()._AM_FORM_DATATYPE_CHAR2);
        $dateType = new XoopsFormRadio('', 'element_datatype', $defaultType);
        $dateType->addOption('date', _AM_FORM_DATATYPE_DATE);
        if ($defaultType != "text" AND $defaultType != "int" AND $defaultType != "decimal" AND $defaultType != "varchar" AND $defaultType != "char" AND $defaultType != "date") {
            $otherType = new XoopsFormRadio('', 'element_datatype', $defaultType);
            $otherType->addOption($defaultType, _AM_FORM_DATATYPE_OTHER.$defaultType);
            $dataTypeTray->addElement($otherType);
        }
        $dataTypeTray->addElement($textType);
        $dataTypeTray->addElement($intType);
        $dataTypeTray->addElement($decimalType);
        $dataTypeTray->addElement($varcharType);
        $dataTypeTray->addElement($charType);
        $dataTypeTray->addElement($dateType);
        $renderedUI .= $dataTypeTray->render();
    }
    return $renderedUI;
}

// THIS FUNCTION TAKES THE VALUES USED IN THE DB, PLUS THE UITEXT FOR THOSE VALUES, AND CONSTRUCTS AN ARRAY SUITABLE FOR USE WHEN EDITING ELEMENTS, SO THE UITEXT IS VISIBLE INLINE WITH THE VALUES, SEPARATED BY A PIPE (|)
function formulize_mergeUIText($values, $uitext) {
    if (is_string($values) and strstr($values, "#*=:*")) {
        // don't alter linked selectbox properties
        return $values;
    }
    
    if (is_array($values)) {
        $newvalues = array();
        foreach($values as $key=>$value) {
            if (isset($uitext[$key])) {
                $newvalues[$key . "|" . $uitext[$key]] = $value;
            } else {
                $newvalues[$key] = $value;
            }
        }
        return $newvalues;
    }
    return $values;
}

function has_index($element,$id_form) {
    // get the current index...
    global $xoopsDB;
    $indexType = "";

    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($id_form);

    //Complex check if
    $elementDataSQL = "SELECT stats.index_name FROM information_schema.statistics AS stats INNER JOIN (SELECT count( 1 ) AS amountCols, index_name FROM information_schema.statistics WHERE table_schema = '".SDATA_DB_NAME."' AND table_name = '".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))."' GROUP BY index_name) AS amount ON amount.index_name = stats.index_name WHERE stats.table_name = '".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))."' AND stats.column_name = '".$element->getVar('ele_handle')."' AND amount.amountCols =1";

    // Simple sql with no check that it is not a multi column index
    //$elementDataSQL = "SHOW  INDEX  FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." WHERE Column_Name = '".$element->getVar('ele_handle')."'";
    $elementDataRes = $xoopsDB->queryF($elementDataSQL);
    $elementDataArray = $xoopsDB->fetchArray($elementDataRes);
    $indexType = $elementDataArray['index_name'];

    return $indexType;
}
