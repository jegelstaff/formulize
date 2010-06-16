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

// this file gets all the data about elements, so we can display the tabs for elements

include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";

// need to listen for $_GET['aid'] later so we can limit this to just the application that is requested
$aid = intval($_GET['aid']);
$application_handler = xoops_getmodulehandler('applications','formulize');
$form_handler = xoops_getmodulehandler('forms','formulize');
$element_handler = xoops_getmodulehandler('elements', 'formulize');

if($aid == 0) {
	$appName = "Forms with no app"; 
} else {
	$appObject = $application_handler->get($aid);
	$appName = $appObject->getVar('name');
}

$names = array();
$display = array();
$advanced = array();

if($_GET['ele_id'] != "new") {
  $ele_id = intval($_GET['ele_id']);
  $elementObject = $element_handler->get($ele_id);
  $fid = $elementObject->getVar('id_form');
  $colhead = $elementObject->getVar('ele_colhead');
  $caption = $elementObject->getVar('ele_caption');
  $ele_type = $elementObject->getVar('ele_type');
  $elementName = $colhead ? printSmart($colhead,30) : printSmart($caption,30);
  $names['ele_caption'] = $caption;
  $names['ele_colhead'] = $colhead;
  $names['ele_handle'] = $elementObject->getVar('ele_handle');
  $names['ele_desc'] = $elementObject->getVar('ele_desc');
  $ele_req = $elementObject->getVar('ele_req');
  $names['ele_req_on'] = removeNotApplicableRequireds($ele_type, $ele_req);
  $names['ele_req_no_on'] = $ele_req ? "" : " selected";
  $names['ele_req_yes_on'] = $ele_req ? " selected" : "";
  $ele_display = $elementObject->getVar('ele_display');
  if(strstr($ele_display,",")) {
    foreach(explode(",",trim($ele_display,",")) as $displayGroup) {
      $display['ele_display'][$displayGroup] = " selected"; 
    }
  } elseif($ele_display == 1) {
    $display['ele_display']['all'] = " selected"; 
  }
  $ele_disabled = $elementObject->getVar('ele_disabled');
  if(strstr($ele_disabled,",")) {
    foreach(explode(",",trim($ele_disabled,",")) as $displayGroup) {
      $display['ele_disabled'][$displayGroup] = " selected"; 
    }
  } elseif($ele_disabled == 1) {
    $display['ele_disabled']['all'] = " selected"; 
  }
  $ele_filtersettings = $elementObject->getVar('ele_filtersettings');
  $filterSettingsToSend = count($ele_filtersettings > 0) ? $ele_filtersettings : "";
  $display['filtersettings'] = formulize_createFilterUI($filterSettingsToSend, "elementfilter", $fid, "form-3");
  $display['ele_forcehidden'] = $elementObject->getVar('ele_forcehidden') ? " checked" : "";
  $display['ele_private'] = $elementObject->getVar('ele_private') ? " checked" : "";
  $ele_encrypt = $elementObject->getVar('ele_encrypt');
  if($ele_type != "subform" AND $ele_type != "grid" AND $ele_type != "ib" AND $ele_type != "areamodif") {
    $advanced['ele_encrypt_no_on'] = $ele_encrypt ? "" : " selected";
    $advanced['ele_encrypt_yes_on'] = $ele_encrypt ? " selected" : "";
    $advanced['ele_encrypt_show'] = true;
  }
  
} else {
  $fid = intval($_GET['fid']);
  $elementName = "New element";
  $elementObject = false;
  $names['ele_caption'] = $elementName;
  $ele_type = $_GET['type'];
  $names['ele_req_on'] = removeNotApplicableRequireds($ele_type, true);
  $names['ele_req_no_on'] = " selected";
  $display['ele_display']['all'] = " selected";
  $display['ele_disabled']['all'] = " selected";
  $display['filtersettings'] = formulize_createFilterUI("", "elementfilter", $fid, "form-3");
  $ele_encrypt = 0;
  if ($ele_type != "subform" AND $ele_type != "grid" AND $ele_type != "ib" AND $ele_type != "areamodif") {
    $advanced['ele_encrypt_no_on'] = " selected";
    $advanced['ele_encrypt_show'] = true;
  }
  
}

$advanced['datatypeui'] = createDataTypeUI($ele_type, $elementObject,$fid,$ele_encrypt);

$formObject = $form_handler->get($fid);
$formName = printSmart($formObject->getVar('title'), 30);


// common values should be assigned to all tabs
$common['name'] = '';
$common['ele_id'] = $ele_id;

$names['hello'] = 'hello';

$options = array();
$options['typetemplate'] = "db:admin/element_type_".$ele_type.".html";

if($ele_type=='text'||$ele_type=='textarea'||$ele_type=='select') {
  $formlink = createFieldList($val, true);
  $options['formlink'] = $formlink->render();
} else if($ele_type=='sep') {
  $options['options'] = array('centre'=>_AM_ELE_CTRE, 'souligné'=>_AM_ELE_SOUL, 'italique'=>_AM_ELE_ITALIQ);
  //$options['colors'] = array ("Noir"=>"#000000", "Marron"=>"#97694F", "Bleu"=>"#7093DB", "Rouge"=>"#e00000", "Vert"=>"#4A766E", "Rose"=>"#9F5F9F", "Jaune"=>"#ffff00", "Blanc"=>"#ffffff");
  $options['colors'] = array ('#000000'=>'Noir','#97694F'=>'Marron','#7093DB'=>'Bleu','#e00000'=>'Rouge','#4A766E'=>'Vert','#9F5F9F'=>'Rose','#ffff00'=>'Jaune','#ffffff'=>'Blanc');
} else if($ele_type=='derived') {
  $derivedOptions = array();
  $allColList = getAllColList($fid);
  foreach($allColList[$fid] as $thisCol) {
    if($thisCol['ele_colhead'] != "") {
      $derivedOptions[trans($thisCol['ele_colhead'])] = printSmart(trans($thisCol['ele_colhead']));
    } else {
      $derivedOptions[trans(strip_tags($thisCol['ele_caption']))] = printSmart(trans(strip_tags($thisCol['ele_caption'])));
    }
  }

  $listOfElements = new XoopsFormSelect("", 'listofelementsoptions');
  $listOfElements->addOptionArray($derivedOptions);
  $options['listofelementsoptions'] = $listOfElements->render();
}


$member_handler = xoops_gethandler('member');
$allGroups = $member_handler->getGroups();
$groups = array();
foreach($allGroups as $thisGroup) {
  $groups[$thisGroup->getVar('name')]['id'] = $thisGroup->getVar('groupid');
  $groups[$thisGroup->getVar('name')]['name'] = $thisGroup->getVar('name');
}
$display['groups'] = $groups;


$tabindex = 1;
$adminPage['tabs'][$tabindex]['name'] = "Names & Settings";
$adminPage['tabs'][$tabindex]['template'] = "db:admin/element_names.html";
$adminPage['tabs'][$tabindex]['content'] = $names + $common;

if($ele_type!='colorpick') {
  $adminPage['tabs'][++$tabindex]['name'] = "Options";
  $adminPage['tabs'][$tabindex]['template'] = "db:admin/element_options.html";
  $adminPage['tabs'][$tabindex]['content'] = $options + $common;
}

$adminPage['tabs'][++$tabindex]['name'] = "Display Settings";
$adminPage['tabs'][$tabindex]['template'] = "db:admin/element_display.html";
$adminPage['tabs'][$tabindex]['content'] = $display + $common;

if($advanced['datatypeui'] OR $advanced['ele_encrypt_show']) {
  $adminPage['tabs'][++$tabindex]['name'] = "Advanced";
  $adminPage['tabs'][$tabindex]['template'] = "db:admin/element_advanced.html";
  $adminPage['tabs'][$tabindex]['content'] = $advanced + $common;
}

$adminPage['pagetitle'] = "Element: ".$elementName;
$adminPage['needsave'] = true;

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['url'] = "page=application&aid=$aid";
$breadcrumbtrail[2]['text'] = $appName;
$breadcrumbtrail[3]['url'] = "page=form&aid=$aid&fid=$fid";
$breadcrumbtrail[3]['text'] = $formName;
$breadcrumbtrail[4]['text'] = $elementName;

function removeNotApplicableRequireds($type, $req) {
  switch($type) {
    case "text":
    case "textarea":
    case "select":
    case "radio":
    case "date":
      return $req;
  }
  return false;
}

function createDataTypeUI($ele_type, $element,$id_form,$ele_encrypt) {
  // data type controls ... added May 31 2009, jwe 
    // only do it for existing elements where the datatype choice is relevant
		// do not do it for encrypted elements
    $renderedUI = "";
		if(($ele_type == "text" OR $ele_type == "textarea" OR $ele_type == "select" OR $ele_type == "radio" OR $ele_type == "checkbox" OR $ele_type == "derived") AND !$ele_encrypt) {
      if($element) {
              // get the current type...
              global $xoopsDB;
              $elementDataSQL = "SHOW COLUMNS FROM ".$xoopsDB->prefix("formulize_".$id_form)." LIKE '".$element->getVar('ele_handle')."'";
              $elementDataRes = $xoopsDB->queryF($elementDataSQL);
              $elementDataArray = $xoopsDB->fetchArray($elementDataRes);
              $defaultTypeComplete = $elementDataArray['Type'];
              $parenLoc = strpos($defaultTypeComplete, "(");
              if($parenLoc) {
                      $defaultType = substr($defaultTypeComplete,0,$parenLoc);
                      $lengthOfSizeValues = strlen($defaultTypeComplete)-($parenLoc+2);
                      $defaultTypeSize = substr($defaultTypeComplete,($parenLoc+1),$lengthOfSizeValues);
                      if($defaultType == "decimal") {
                              $sizeParts = explode(",", $defaultTypeSize);
                              $defaultTypeSize = $sizeParts[1]; // second part of the comma separated value is the number of decimal places declaration
                      }
              } else {
                      $defaultType = $defaultTypeComplete;
                      $defaultTypeSize = '';
              }
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
      if($defaultType != "text" AND $defaultType != "int" AND $defaultType != "decimal" AND $defaultType != "varchar" AND $defaultType != "char") {
              $otherType = new XoopsFormRadio('', 'element_datatype', $defaultType);
              $otherType->addOption($defaultType, _AM_FORM_DATATYPE_OTHER.$defaultType);
              $dataTypeTray->addElement($otherType);
      }
      $dataTypeTray->addElement($textType);
      $dataTypeTray->addElement($intType);
      $dataTypeTray->addElement($decimalType);
      $dataTypeTray->addElement($varcharType);
      $dataTypeTray->addElement($charType);
      $renderedUI .= $dataTypeTray->render();
  }
  return $renderedUI;
}
