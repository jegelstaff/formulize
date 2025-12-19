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

$common = array();
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

$customTypeHandler = false;
if ($_GET['ele_id'] != "new") {
    $ele_id = intval($_GET['ele_id']);
    $elementObject = $element_handler->get($ele_id);
    $fid = $elementObject->getVar('id_form');
    $defaultOrder = $element_handler->getPreviousElement($elementObject->getVar('ele_order'), $fid);
    if (!$defaultOrder) {
        $firstElementOrder = " selected";
    }
    $defaultSort = $elementObject->getVar('ele_sort');
    $colhead = $elementObject->getVar('ele_colhead');
    $caption = $elementObject->getVar('ele_caption', "f"); // the f causes no stupid reformatting by the ICMS core to take place, like making clickable links, etc
    $ele_type = $elementObject->getVar('ele_type');
		if (file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$ele_type."Element.php")) {
  		$customTypeHandler = xoops_getmodulehandler($ele_type."Element", 'formulize');
		}
    $ele_value = $elementObject->getVar('ele_value');
    $ele_use_default_when_blank = intval($elementObject->getVar('ele_use_default_when_blank'));
    $ele_delim = $elementObject->getVar('ele_delim');
		$ele_delim_custom_value = '';
    if ($ele_delim != "br" AND $ele_delim != "space" AND $ele_delim != "") {
        $ele_delim_custom_value = $ele_delim;
        $ele_delim = "custom";
    }
    $elementName = $colhead ? printSmart($colhead,30) : printSmart($caption,30);
    $names['ele_caption'] = $caption;
    $names['ele_colhead'] = $colhead;
    $names['ele_handle'] = $elementObject->getVar('ele_handle');
    $names['ele_desc'] = $elementObject->getVar('ele_desc', "f"); // the f causes no stupid reformatting by the ICMS core to take place
    $ele_required = $elementObject->getVar('ele_required');
    $ele_required = removeNotApplicableRequireds($ele_type, $ele_required); // function returns false when the element cannot be required.
    $common['ele_req_on'] = $ele_required === false ? false : true;
    $names['ele_req_no_on'] = $ele_required ? "" : " checked";
    $names['ele_req_yes_on'] = $ele_required ? " checked" : "";
    $ele_display = $elementObject->getVar('ele_display');
    if (strstr($ele_display,",")) {
        foreach(explode(",",trim($ele_display,",")) as $displayGroup) {
            $display['ele_display'][$displayGroup] = " selected";
        }
				$display['ele_display']['all'] = "";
				$display['ele_display']['none'] = "";
    } elseif ($ele_display == 1) {
        $display['ele_display']['all'] = " selected";
				$display['ele_display']['none'] = "";
    } elseif ($ele_display == 0) {
        $display['ele_display']['none'] = " selected";
				$display['ele_display']['all'] = "";
    }
    $ele_disabled = $elementObject->getVar('ele_disabled');
    if (strstr($ele_disabled,",")) {
        foreach(explode(",",trim($ele_disabled,",")) as $displayGroup) {
            $display['ele_disabled'][$displayGroup] = " selected";
        }
				$display['ele_disabled']['all'] = "";
				$display['ele_disabled']['none'] = "";
    } elseif ($ele_disabled == 1) {
        $display['ele_disabled']['all'] = " selected";
				$display['ele_disabled']['none'] = "";
    } else {
        $display['ele_disabled']['none'] = " selected";
				$display['ele_disabled']['all'] = "";
    }

    $ele_filtersettings = $elementObject->getVar('ele_filtersettings');
    $filterSettingsToSend = (count((array) $ele_filtersettings) > 0) ? $ele_filtersettings : "";
    $display['filtersettings'] = formulize_createFilterUI($filterSettingsToSend, "elementfilter", $fid, "form-3");

    $ele_disabledconditions = $elementObject->getVar('ele_disabledconditions');
    $disabledConditionsToSend = (count((array) $ele_disabledconditions) > 0) ? $ele_disabledconditions : "";
    $display['disabledconditions'] = formulize_createFilterUI($ele_disabledconditions, "disabledconditions", $fid, "form-3");

    $display['ele_forcehidden'] = $elementObject->getVar('ele_forcehidden') ? " checked" : "";
    $display['ele_private'] = $elementObject->getVar('ele_private') ? " checked" : "";
    $ele_encrypt = $elementObject->getVar('ele_encrypt');
    if ($elementObject->hasData) {
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
				$advanced['ele_dynamicdefault_source'] = $elementObject->getVar('ele_dynamicdefault_source');
				$advanced['ele_dynamicdefault_conditions'] = $elementObject->getVar('ele_dynamicdefault_conditions');
    }
		if($elementObject->hasMultipleOptions AND !$elementObject->isLinked) {
			$advanced['hasMultipleOptions'] = true;
			$exportOptions = $elementObject->getVar('ele_exportoptions');
			$advanced['exportoptions_onoff'] = (is_array($exportOptions) AND count((array) $exportOptions) > 0) ? 1 : 0;
			$advanced['exportoptions_hasvalue'] = $exportOptions['indicators']['hasValue'];
			$advanced['exportoptions_doesnothavevalue'] = $exportOptions['indicators']['doesNotHaveValue'];
		} else {
			$advanced['exportoptions_onoff'] = 0;
		}
    $ele_uitext = $elementObject->getVar('ele_uitext');
    $ele_uitextshow = $elementObject->getVar('ele_uitextshow');
} else {
	$fid = intval($_GET['fid']);
	$elementName = "New element";
	$defaultOrder = "bottom";
	$defaultSort = "";
	$elementObject = false;
	$names['ele_caption'] = $elementName;
	$ele_type = $_GET['type'];
	if (file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$ele_type."Element.php")) {
		$customTypeHandler = xoops_getmodulehandler($ele_type."Element", 'formulize');
	}
	$ele_value = array();
	$ele_delim = "br";
	$ele_uitext = "";
	$ele_uitextshow = 0;
	$ele_use_default_when_blank = 0;
	global $xoopsModuleConfig;

	$ele_required = removeNotApplicableRequireds($ele_type); // function returns false when the element cannot be required.
	$common['ele_req_on'] = $ele_required === false ? false : true;

	$names['ele_req_no_on'] = " checked";
	$display['ele_display']['all'] = " selected";
	$display['ele_disabled']['none'] = " selected";
	$display['filtersettings'] = formulize_createFilterUI("", "elementfilter", $fid, "form-3");
	$ele_encrypt = 0;
	if ($customTypeHandler) {
		$customTypeObject = $customTypeHandler->create();
    if($customTypeObject->hasData) {
			$advanced['ele_encrypt_no_on'] = " checked";
			$advanced['ele_encrypt_show'] = true;
			$ele_index = "";
			$advanced['original_ele_index'] = strlen($ele_index) > 0;
			$advanced['original_index_name'] = $ele_index;
			$advanced['ele_index_no_on'] = strlen($ele_index) > 0 ? "" : " checked";
			$advanced['ele_index_yes_on'] = strlen($ele_index) > 0 ? " checked" : "";
			$advanced['ele_index_show'] = true;
			$advanced['ele_dynamicdefault_source'] = 0;
			$advanced['ele_dynamicdefault_conditions'] = "";
		}
	}
	$advanced['exportoptions_onoff'] = 0;
	$ele_id = "new";
}

$advanced['ele_use_default_when_blank'] = $ele_use_default_when_blank;
$advanced['datatypeui'] = createDataTypeUI($ele_type, $elementObject,$fid,$ele_encrypt);
$advanced['advancedTypeTemplate'] = file_exists(XOOPS_ROOT_PATH."/modules/formulize/templates/admin/element_type_".$ele_type."_advanced.html") ? "db:admin/element_type_".$ele_type."_advanced.html" : "";

list($dynamicDefaultElement, $dynamicDefaultSourceElementId) = createFieldList($advanced['ele_dynamicdefault_source'], false, false, "elements-ele_dynamicdefault_source", _NONE);
$advanced['dynamicDefaultSourceList'] = $dynamicDefaultElement->render();
$advanced['dynamicDefaultConditions'] = "";
if($dynamicDefaultSourceElementId) {
	$dynamicDefaultSourceElementObject = _getElementObject($dynamicDefaultSourceElementId);
	if($dynamicDefaultSourceElementObject) {
		$advanced['dynamicDefaultConditions'] = formulize_createFilterUI($advanced['ele_dynamicdefault_conditions'], "dynamicDefaultConditions", $dynamicDefaultSourceElementObject->getVar('fid'), "form-4");
	}
}

$formObject = $form_handler->get($fid);
$formName = printSmart($formObject->getVar('title'), 30);
$formHandle = printSmart($formObject->getVar('form_handle'), 30);

$common['formTitle'] = strip_tags($formName);

// package up the elements into a list for ordering purposes
// also, the sort options
$orderOptions = array();
$ele_colheads = $formObject->getVar('elementColheads');
$ele_captions = $formObject->getVar('elementCaptions');
$defaultpi = $formObject->getVar('pi');
foreach($formObject->getVar('elements') as $elementId) {
    $elementTextToDisplay = $ele_colheads[$elementId] ? printSmart($ele_colheads[$elementId]) : printSmart($ele_captions[$elementId]);
    if ($ele_id != $elementId) {
        $orderOptions[$elementId] = "After: ".$elementTextToDisplay;
        $sortOptions[$elementId] = "Sort by value of: ".$elementTextToDisplay;
    }
}
$names['orderoptions'] = $orderOptions;
$names['defaultorder'] = $defaultOrder;
$names['firstelementorder'] = $firstElementOrder;
$names['sortoptions'] = $sortOptions;
$names['defaultsort'] = $defaultSort;
$names['principalidentifier_off'] = ($defaultpi != $ele_id AND ($ele_id != 'new' OR count($formObject->getVar('elements')) > 0)) ? " checked='checked' " : "";
$names['principalidentifier_on'] = ($defaultpi == $ele_id OR ($ele_id == 'new' AND count($formObject->getVar('elements')) == 0)) ? " checked='checked' " : "";

// common values should be assigned to all tabs
$common['name'] = '';
$common['ele_id'] = $ele_id;
$common['fid'] = $fid;
$common['formhandle']=$formHandle;
$common['aid'] = $aid;
$common['type'] = $ele_type;
$common['typeIsSelect'] = anySelectElementType($ele_type);
$common['uid'] = $xoopsUser->getVar('uid');
$common['isSystemElement'] = $elementObject ? $elementObject->isSystemElement : false;
$common['isUserAccountElement'] = ($elementObject AND substr($elementObject->getVar('ele_type'), 0, 11) == 'userAccount' AND $elementObject->isSystemElement) ? true : false;

$options = array();
$options['ele_delim'] = $ele_delim;
$options['ele_delim_custom_value'] = $ele_delim_custom_value;
$options['ele_uitext'] = $ele_uitext;
$options['ele_uitextshow'] = $ele_uitextshow;
$options['typetemplate'] = "db:admin/element_type_".$ele_type.".html";

// setup various special things per element, including ele_value
if ($ele_type=="ib") {
  $options['ib_style_options']['head'] = "head";
  $options['ib_style_options']['form-heading'] = "form-heading";
}


if(!isset($ele_value['snapshot'])) {
    $ele_value['snapshot'] = 0;
}

$options['ele_value'] = $ele_value;

// if this is a custom element, then get any additional values that we need to send to the template
$customValues = array();
$advancedCustomValues = array();
if($customTypeHandler) {
    $customValues = $customTypeHandler->adminPrepare($elementObject);
		if (is_array($customValues) AND count($customValues) == 2 AND isset($customValues['options-tab-values']) AND isset($customValues['advanced-tab-values'])) {
			$advancedCustomValues = $customValues['advanced-tab-values'];
			$customValues = $customValues['options-tab-values'];
		}
}

$display['groups'] = $groups;

// cannot be in the adminPrepare because new elements do not have fid available in an object. :(
if($ele_type == 'derived') {
	$form_id = $fid; // needs to be declared for generateTemplateElementHandleHelp.php
	$selectedFramework = 0; // needs to be declared for generateTemplateElementHandleHelp.php
	include XOOPS_ROOT_PATH.'/modules/formulize/admin/generateTemplateElementHandleHelp.php';
	$common['variabletemplatehelp'] = $listTemplateHelp;
}

$tabindex = 1;
$adminPage['tabs'][$tabindex]['name'] = _AM_ELE_NAMEANDSETTINGS;
$adminPage['tabs'][$tabindex]['template'] = "db:admin/element_names.html";
$adminPage['tabs'][$tabindex]['content'] = $names+$common;

if(!$elementObject OR $elementObject->isSystemElement == false) {
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
    $adminPage['tabs'][$tabindex]['content'] = $advanced + $common + $advancedCustomValues;
}

$adminPage['pagetitle'] = "Element: ".$elementName;
$adminPage['pagesubtitle'] = "(".convertTypeToText($ele_type, $ele_value).")";
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

    if ($customTypeNeedsUI AND !$ele_encrypt) {
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
        $textDataTypeLabel = (!$element AND ($ele_type == 'text' OR $ele_type == 'number')) ? _AM_FORM_DATATYPE_TEXT_NEWTEXT : _AM_FORM_DATATYPE_TEXT;
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
        $dateTimeType = new XoopsFormRadio('', 'element_datatype', $defaultType);
        $dateTimeType->addOption('datetime', _AM_FORM_DATATYPE_DATETIME);
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
        $dataTypeTray->addElement($dateTimeType);
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
