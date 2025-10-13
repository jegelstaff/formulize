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

// this file gets all the data about applications, so we can display the Settings/forms/relationships tabs for applications

include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";
include_once XOOPS_ROOT_PATH."/modules/formulize/class/multiPageScreen.php";

$form_handler = xoops_getmodulehandler('forms', 'formulize');
$framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
$screen_id = $_GET['sid'];
// screen settings data
$settings = array();

$aid = isset($_GET['aid']) ? intval($_GET['aid']) : 0;
$form_id = intval($_GET['fid']);

// get all passcodes for other screens
// generate a potential new passcode for this screen
$passcode_handler = xoops_getmodulehandler('passcode', 'formulize');
$settings['existingPasscodes'] = $passcode_handler->getOtherScreenPasscodes($screen_id);
$settings['newPasscode'] = $passcode_handler->generatePasscode();
$settings['passcodes'] = $passcode_handler->getThisScreenPasscodes($screen_id);

$config_handler = $config_handler = xoops_gethandler('config');
$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
if ($screen_id == "new") {
    $settings['type'] = 'listOfEntries';
    $settings['frid'] = 0;
    $settings['useToken'] = $formulizeConfig['useToken'];
    $settings['anonNeedsPasscode'] = 1;
    $screenName = "New screen";
		$screen_handle = "";
} else {
    $screen_handler = xoops_getmodulehandler('screen', 'formulize');
    $screen = $screen_handler->get($screen_id);
    if (null == $screen) {
        // not a valid screen ID, so redirect to the Formulize main page
        header("location: ".XOOPS_URL."/modules/formulize/application.php?id=all");
        die;
    }

    $settings['type'] = $screen->getVar('type');
    $settings['frid'] = $screen->getVar('frid');
    $settings['useToken'] = $screen->getVar('useToken');
    $settings['anonNeedsPasscode'] = $screen->getVar('anonNeedsPasscode');
	$settings['alternateURLsOn'] = $formulizeConfig['formulizeRewriteRulesEnabled'];
    if($settings['alternateURLsOn']) {
        $settings['rewriteruleAddress'] = $screen->getVar('rewriteruleAddress');
        $settings['rewriteruleElement'] = $screen->getVar('rewriteruleElement');
        $formObject = $form_handler->get($form_id);
        $elementColheads = $formObject->getVar('elementColheads');
        $settings['rewriteruleElements'][0] = 'Entry ID';
        foreach($formObject->getVar('elementCaptions') as $elementId=>$elementLabel) {
            $elementLabel = $elementColheads[$elementId] ? $elementColheads[$elementId] : $elementLabel;
            $settings['rewriteruleElements'][$elementId] = $elementLabel;
        }
    }

    if ($settings['type'] == 'listOfEntries') {
        $screen_handler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
    } else if ($settings['type'] == 'form') {
        $screen_handler = xoops_getmodulehandler('formScreen', 'formulize');
    } else if ($settings['type'] == 'multiPage') {
        $screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
    } else if ($settings['type'] == 'template') {
        $screen_handler = xoops_getmodulehandler('templateScreen', 'formulize');
    } else if ($settings['type'] == 'calendar') {
        $screen_handler = xoops_getmodulehandler('calendarScreen', 'formulize');
    }
    $screen = $screen_handler->get($screen_id);

    $screenName = $screen->getVar('title');
		$screen_handle = $screen->getVar('screen_handle');
    $form_id = $screen->form_id();

    $adminPage["template"] = "ABC, mellonfarmers!";
}

if (!$aid) {
    $appName = "Forms with no app";
} else {
  $application_handler = xoops_getmodulehandler('applications','formulize');
    $appObject = $application_handler->get($aid);
    $appName = $appObject->getVar('name');
}

if ($form_id != "new") {
    $formObject = $form_handler->get($form_id);
    $formName = $formObject->getVar('title');
    $singleentry = $formObject->getVar('single');
}

$elements = array();

$includePrimaryRelationship = true;
$relationshipsIncludingThisForm = $framework_handler->getFrameworksByForm($form_id, $includePrimaryRelationship);
if(isset($relationshipsIncludingThisForm[-1])) {
    $frameworks = $relationshipsIncludingThisForm;
} else {
    // must include primary relationship regardless of whether the form is involved or not
    // and merging or unshifting into the array would screw things up ID wise. The + operator might work, since there wouldn't be duplicates in the array?
    $frameworks = array($framework_handler->get(-1)); // start with primary relationship
    foreach($relationshipsIncludingThisForm as $thisFrid=>$relationshipObject) {
        $frameworks[$thisFrid] = $relationshipObject;
    }
}
$relationships = $framework_handler->formatFrameworksAsRelationships($frameworks);
$relationshipsIncludingThisForm = $framework_handler->formatFrameworksAsRelationships($relationshipsIncludingThisForm);

$relationshipSettings = array(
  'relationships' => $relationships,
  'relationshipsIncludingThisForm' => $relationshipsIncludingThisForm,
  'type' => $settings['type']
);

if ($screen_id != 'new') {
  $relationshipSettings['frid'] = $screen->getVar('frid');
  $templates = array();
  $templates['selectedTheme'] =  $screen->getVar('theme');
}

// prepare data for sub-page
if ($screen_id != "new" && $settings['type'] == 'listOfEntries') {
  // display data
  $templates['toptemplate'] = str_replace("&", "&amp;", $screen->getTemplate('toptemplate', $screen->getVar('theme')));
  $templates['bottomtemplate'] = str_replace("&", "&amp;", $screen->getTemplate('bottomtemplate', $screen->getVar('theme')));
  $templates['listtemplate'] = str_replace("&", "&amp;", $screen->getTemplate('listtemplate', $screen->getVar('theme')));
  $templates['openlisttemplate'] = str_replace("&", "&amp;", $screen->getTemplate('openlisttemplate', $screen->getVar('theme')));
  $templates['closelisttemplate'] = str_replace("&", "&amp;", $screen->getTemplate('closelisttemplate', $screen->getVar('theme')));
  $templates['usingTemplates'] = ($templates['toptemplate'] OR $templates['bottomtemplate'] OR $templates['listtemplate'] OR $templates['openlisttemplate'] OR $templates['closelisttemplate']);

  // view data
  // gather all the available views
  // setup an option list of all views, as well as one just for the currently selected Framework setting
  $formObj = $form_handler->get($form_id, true); // true causes all elements to be included even if they're not visible.
  $selectedFramework = $settings['frid'];
  $views = $formObj->getVar('views');
  $viewNames = $formObj->getVar('viewNames');
  $viewFrids = $formObj->getVar('viewFrids');
  $viewPublished = $formObj->getVar('viewPublished');
  $viewOptions = array();
  $limitViewOptions = array();
  $viewOptions['blank'] = _AM_FORMULIZE_SCREEN_LOE_BLANK_DEFAULTVIEW;
  $viewOptions['mine'] = _AM_FORMULIZE_SCREEN_LOE_DVMINE;
  $viewOptions['group'] = _AM_FORMULIZE_SCREEN_LOE_DVGROUP;
  $viewOptions['all'] = _AM_FORMULIZE_SCREEN_LOE_DVALL;
  $viewOptionsC = array();
  for($i=0;$i<count((array) $views);$i++) {
      if (!$viewPublished[$i]) { continue; }
      $viewOptionsC[$views[$i]] = $viewNames[$i];
  }
  $limitViewOptions['allviews'] = _AM_FORMULIZE_SCREEN_LOE_DEFAULTVIEWLIMIT;
  asort($viewOptionsC);
  $limitViewOptions += $viewOptionsC;
  $viewOptions += $viewOptionsC;
  unset($limitViewOptions['blank']);
  // get the available screens
  $viewentryscreenOptions = generateViewEntryScreenOptions($selectedFramework, $form_id);

    $screen_handler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
    $screen = $screen_handler->get($screen_id);
    $adv = $screen->getVar('advanceview');

    $advanceViewSelected = array();
    $index = 0;
    foreach($adv as $id=>$arr) {
        $advanceViewSelected[$index]["column"] = $arr[0];
        $advanceViewSelected[$index]["text"] = $arr[1];
        $advanceViewSelected[$index]["sort"] = $arr[2];
        $advanceViewSelected[$index]["searchtype"] = $arr[3];
        $index++;
    }

  // included file sets up $elementOptions and other variables relied upon below
  include XOOPS_ROOT_PATH.'/modules/formulize/admin/generateTemplateElementHandleHelp.php';
  $templates['variabletemplatehelp'] = $listTemplateHelp;

  $entries = array();
  $entries['advanceviewoptions'] = array(0=>_AM_ELE_SELECT_NONE)+array(
        'creation_uid'=>_formulize_DE_CREATED,
        'mod_uid'=>_formulize_DE_LASTMOD,
        'creation_datetime'=>_formulize_DE_CALC_CREATEDATE,
        'mod_datetime'=>_formulize_DE_CALC_MODDATE,
				'creator_email'=>_formulize_DE_CALC_CREATOR_EMAIL,
				'owner_groups'=>_formulize_DE_CALC_OWNERGROUPS)+$elementOptions; // add a 0 value default to the element list
  $entries['advanceviewsearchtypeoptions'] = array('Box'=>'Search Box','Filter'=>'Dropdown List - select value','NegativeFilter'=>'Dropdown List - exclude value','MultiFilter'=>'Checkboxes', 'DateRange'=>'Date Range');
  $entries['advanceview'] = $advanceViewSelected;
  $entries['defaultview'] = $screen->getVar('defaultview');
  // Convert to arrays if a legacy value
  if(!is_array($entries['defaultview'])) {
    $entries['defaultview'] = array(XOOPS_GROUP_USERS => FORMULIZE_QUERY_SCOPE_GLOBAL);
  }
  $entries['viewoptions'] = $viewOptions;
  $entries['usecurrentviewlist'] = $screen->getVar('usecurrentviewlist');
  $entries['limitviewoptions'] = $limitViewOptions;
  $entries['limitviews'] = $screen->getVar('limitviews');
  $entries['useworkingmsg'] = $screen->getVar('useworkingmsg');
  $entries['usescrollbox'] = $screen->getVar('usescrollbox');
  $entries['entriesperpage'] = $screen->getVar('entriesperpage');
  $entries['viewentryscreenoptions'] = $viewentryscreenOptions;
  $entries['viewentryscreen'] = $screen->getVar('viewentryscreen');
  $entries['frid'] = $settings['frid'];

  // add fundamental filter conditions... cannot have _ in the DOM element name (second param)
  $entries['fundamentalfilters'] = formulize_createFilterUI($screen->getVar('fundamental_filters'), "fundamentalfilters", $screen->getVar('fid'), "form-3", $screen->getVar('frid'));

  // headings data
  $headings = array();
  $headings['useheadings'] = $screen->getVar('useheadings');
  $headings['repeatheaders'] = $screen->getVar('repeatheaders');
  $headings['usesearchcalcmsgs'] = $screen->getVar('usesearchcalcmsgs');
  $headings['usesearch'] = $screen->getVar('usesearch');
  $headings['columnwidth'] = $screen->getVar('columnwidth');
  $headings['textwidth'] = $screen->getVar('textwidth');
  $headings['usecheckboxes'] = $screen->getVar('usecheckboxes');
  $headings['useviewentrylinks'] = $screen->getVar('useviewentrylinks');
  $headings['elementoptions'] = $elementOptions;
  $headings['hiddencolumns'] = $screen->getVar('hiddencolumns');
  $headings['decolumns'] = $screen->getVar('decolumns');
  $headings['dedisplay'] = $screen->getVar('dedisplay');
  $headings['desavetext'] = $screen->getVar('desavetext');

  // buttons data
  $buttons = array();
  $buttons['useaddupdate'] = $screen->getVar('useaddupdate');
  $buttons['useaddmultiple'] = $screen->getVar('useaddmultiple');
  $buttons['useaddproxy'] = $screen->getVar('useaddproxy');
  $buttons['useexport'] = $screen->getVar('useexport');
  $buttons['useimport'] = $screen->getVar('useimport');
  $buttons['usenotifications'] = $screen->getVar('usenotifications');
  $buttons['usechangecols'] = $screen->getVar('usechangecols');
  $buttons['usecalcs'] = $screen->getVar('usecalcs');
  $buttons['useadvcalcs'] = $screen->getVar('useadvcalcs');
  $buttons['useexportcalcs'] = $screen->getVar('useexportcalcs');
  $buttons['useadvsearch'] = $screen->getVar('useadvsearch');
  $buttons['useclone'] = $screen->getVar('useclone');
  $buttons['usedelete'] = $screen->getVar('usedelete');
  $buttons['useselectall'] = $screen->getVar('useselectall');
  $buttons['useclearall'] = $screen->getVar('useclearall');
  $buttons['usereset'] = $screen->getVar('usereset');
  $buttons['usesave'] = $screen->getVar('usesave');
  $buttons['usedeleteview'] = $screen->getVar('usedeleteview');

  // custom button data
  $custom = array();
  $applyToOptions = array('inline'=>_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_INLINE, 'selected'=>_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_SELECTED, 'all'=>_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_ALL, 'new'=>_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEW, 'new_per_selected'=>_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEWPERSELECTED);
	// $allFids set in generateTemplateElementHandleHelp.php included above. Yuck.
  if (count((array) $allFids) > 1) {
    foreach ($allFids as $i=>$thisFid) {
      if ($thisFid == $form_id) { continue; } // don't treat the current form as if it's an 'other' form
      $applyToOptions['new_'.$thisFid] = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEW_OTHER . printSmart($allFidObjs[$thisFid]->getVar('title'), 20) . "'";
      $applyToOptions['new_per_selected_'.$thisFid] = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEW_OTHER . printSmart($allFidObjs[$thisFid]->getVar('title'), 20) . _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEWPERSELECTED_OTHER;
    }
  }
  $applyToOptions['custom_code'] = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_CUSTOM_CODE;
	$applyToOptions['custom_code_once'] = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_CUSTOM_CODE_ONCE;
  $applyToOptions['custom_html'] = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_CUSTOM_HTML;
  $custom['applytoOptions'] = $applyToOptions;
  if (is_array($screen->getVar('customactions'))) {
    foreach($screen->getVar('customactions') as $buttonId=>$buttonData) {
      $custom['custombuttons'][$buttonId]['content'] = $buttonData;
      $custom['custombuttons'][$buttonId]['content']['id'] = $buttonId; // add id to the date for the template
      $custom['custombuttons'][$buttonId]['name'] = $buttonData['handle'];
      $custom['custombuttons'][$buttonId]['groups'] = unserialize((string) $buttonData['groups']);
      foreach($buttonData as $key=>$value) {
        if (is_numeric($key)) { // effects have numeric keys
          if ($buttonData['applyto'] == 'custom_code') {
						$filename = "custom_code_".$key."_".$buttonData['handle']."_".$screen_handle.".php";
						$custom['custombuttons'][$buttonId]['content'][$key]['code'] = file_get_contents(XOOPS_ROOT_PATH.'/modules/formulize/code/'.$filename);
            $custom['custombuttons'][$buttonId]['content'][$key]['description'] = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_CUSTOM_CODE_DESC;
					} elseif($buttonData['applyto'] == 'custom_code_once') {
						$filename = "custom_code_".$key."_".$buttonData['handle']."_".$screen_handle.".php";
						$custom['custombuttons'][$buttonId]['content'][$key]['code'] = file_get_contents(XOOPS_ROOT_PATH.'/modules/formulize/code/'.$filename);
            $custom['custombuttons'][$buttonId]['content'][$key]['description'] = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_CUSTOM_CODE_ONCE_DESC;
          } elseif ($buttonData['applyto'] == 'custom_html') {
						$filename = "custom_html_".$key."_".$buttonData['handle']."_".$screen_handle.".php";
						$custom['custombuttons'][$buttonId]['content'][$key]['html'] = file_get_contents(XOOPS_ROOT_PATH.'/modules/formulize/code/'.$filename);
            $custom['custombuttons'][$buttonId]['content'][$key]['description'] = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_CUSTOM_HTML_DESC;
          } else {
            if (strstr($buttonData['applyto'], "new_per_selected_")) {
              $thisEffectFid = substr($buttonData['applyto'], 17);
            } elseif (strstr($buttonData['applyto'], "new_")) {
              $thisEffectFid = substr($buttonData['applyto'], 4);
            } else {
              $thisEffectFid = $form_id;
            }
            $custom['custombuttons'][$buttonId]['content'][$key]['elementOptions'] = $elementOptionsFid[$thisEffectFid]; // add element options from the appropriate form to each effect, for passing to the template
            $custom['custombuttons'][$buttonId]['content'][$key]['actionOptions'] = array('replace'=>_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ACTION_REPLACE, 'remove'=>_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ACTION_REMOVE, 'append'=>_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ACTION_APPEND); // add the apply to options to each effect for sending to the template
            $custom['custombuttons'][$buttonId]['content'][$key]['description'] = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_DESC;
          }
        }
      }
    }
  }
}

if ($screen_id != "new" && $settings['type'] == 'multiPage') {
    // parallel entry options for showing previous entries in another form (or entries of some other defined type, but previous entries are what this was made for)
    // Previous entries are meant to be in another form.  To begin with, that form must have the same captions as this form does, but later on there will be some broader capabilities to specify the parallel elements.
    $fe_paraentryform = new xoopsFormSelect(_AM_FORMULIZE_SCREEN_PARAENTRYFORM, 'paraentryform', $screen->getVar('paraentryform'), 1, false);
    $formHandler =& xoops_getmodulehandler('forms');
    $allFormObjects = $formHandler->getAllForms();
    $allFormOptions = array();
    foreach($allFormObjects as $thisFormObject) {
        $allFormOptions[$thisFormObject->getVar('id_form')] = printSmart($thisFormObject->getVar('title'));
    }

    // setup all the elements in this form for use in the listboxes
    include_once XOOPS_ROOT_PATH . "/modules/formulize/class/forms.php";
		$frid = $screen->getVar("frid");


    // get page titles
    $pageTitles = $screen->getVar("pagetitles");
    $elements = $screen->getVar("pages");
    $conditions = $screen->getVar("conditions");

    // get details of each page
    $pages = array();
    for($i=0;$i<(count((array) $pageTitles)+$pageCounterOffset);$i++) {
        $pages[$i]['name'] = $pageTitles[$i];
        $pages[$i]['content']['index'] = $i;
        $pages[$i]['content']['number'] = $i+1;
        $pages[$i]['content']['title'] = $pageTitles[$i];
        list($pages[$i]['content']['pageItemTypeTitle'], $pages[$i]['content']['elements']) = generateElementInfoForScreenPage($elements[$i], $form_id, $frid);
    }

    // options data
    $multipageOptions = array();
    $multipageOptions['reloadblank'] = $screen->getVar('reloadblank') ? "blank" : "entry";
    $multipageOptions['allformoptions'] = $allFormOptions;
    $multipageOptions['paraentryform'] = $screen->getVar('paraentryform');
    $multipageOptions['paraentryrelationship'] = $screen->getVar('paraentryrelationship');
    $multipageOptions['donedest'] = $screen->getVar('donedest');
    $multipageOptions['finishisdone'] = $screen->getVar('finishisdone');
    $navstyle = $screen->getVar('navstyle') ? $screen->getVar('navstyle') : 0;
    $multipageOptions['navstyletabs'] = ($navstyle == 1 OR $navstyle == 2) ? 'checked' : '';
    $multipageOptions['navstylebuttons'] = ($navstyle == 0 OR $navstyle == 2) ? 'checked' : '';
    $multipageOptions['showpageselector'] = $screen->getUIOption('showpageselector') ? 'checked' : '';
    $multipageOptions['showpageindicator'] = $screen->getUIOption('showpageindicator') ? 'checked' : '';
    $multipageOptions['showpagetitles'] = $screen->getUIOption('showpagetitles') ? 'checked' : '';
    $buttonText = $screen->getVar('buttontext');
    $multipageOptions['thankyoulinktext'] = is_array($buttonText) ? $buttonText['thankyoulinktext'] : $buttonText;
    $multipageOptions['thankyoulinktext'] = $multipageOptions['thankyoulinktext'] ? $multipageOptions['thankyoulinktext'] : '';
    $multipageOptions['prevButtonText'] = (is_array($buttonText) AND $buttonText['prevButtonText']) ? $buttonText['prevButtonText'] : '';
    $multipageOptions['leaveButtonText'] = (is_array($buttonText) AND $buttonText['leaveButtonText']) ? $buttonText['leaveButtonText'] : '';
    $multipageOptions['saveButtonText'] = (is_array($buttonText) AND $buttonText['saveButtonText']) ? $buttonText['saveButtonText'] :  '';
    $multipageOptions['finishButtonText'] = (is_array($buttonText) AND $buttonText['finishButtonText']) ? $buttonText['finishButtonText'] : '';
    $multipageOptions['nextButtonText'] = (is_array($buttonText) AND $buttonText['nextButtonText']) ? $buttonText['nextButtonText'] : '';
		$multipageOptions['closeButtonText'] = (is_array($buttonText) AND $buttonText['closeButtonText']) ? $buttonText['closeButtonText'] : '';
    $multipageOptions['printableViewButtonText'] = (is_array($buttonText)) ? $buttonText['printableViewButtonText'] : '';
    $multipageOptions['printall'] = $screen->getVar('printall');
    $multipageOptions['displaycolumns'] = $screen->getVar('displaycolumns') == 1 ? "onecolumn" : "twocolumns";
    $multipageOptions['column1width'] = $screen->getVar('column1width') ? $screen->getVar('column1width') : '20%';
    $multipageOptions['column2width'] = $screen->getVar('column2width') ? $screen->getVar('column2width') : 'auto';


    // text data
    $multipageText = array();
    $multipageText['introtext'] = undoAllHTMLChars($screen->getVar('introtext', "e"));
    $multipageText['thankstext'] = undoAllHTMLChars($screen->getVar('thankstext', "e")); // need the e to make sure it doesn't convert links to clickable HTML!

    // template data
    updateMultipageTemplates($screen);
    $templates['toptemplate'] =  str_replace("&", "&amp;", $screen->getTemplate('toptemplate', $screen->getVar('theme')));
    $templates['bottomtemplate'] = str_replace("&", "&amp;", $screen->getTemplate('bottomtemplate', $screen->getVar('theme')));
    $templates['elementtemplate1'] = str_replace("&", "&amp;", $screen->getTemplate('elementtemplate1', $screen->getVar('theme')));
    $templates['elementtemplate2'] = str_replace("&", "&amp;", $screen->getTemplate('elementtemplate2', $screen->getVar('theme')));
    $templates['elementcontainero'] = str_replace("&", "&amp;", $screen->getTemplate('elementcontainero', $screen->getVar('theme')));
    $templates['elementcontainerc'] = str_replace("&", "&amp;", $screen->getTemplate('elementcontainerc', $screen->getVar('theme')));
    $templates['usingTemplates'] = ($templates['toptemplate'] OR $templates['bottomtemplate'] OR $templates['elementtemplate1'] OR $templates['elementtemplate2'] OR $templates['elementcontainero'] OR $templates['elementcontainerc']);

    // pages data
    $multipagePages = array();
    $multipagePages['pages'] = $pages;
		$frid = $screen->getVar("frid");
    $element_list = multiPageScreen_addToOptionsList($form_id, $frid);
    $multipageOptions['element_list'] = $element_list;
    $multipageOptions['elementdefaults'] = $screen->getVar('elementdefaults');


}

if ($screen_id != "new" && $settings['type'] == 'form') {
		$frid = $screen->getVar("frid");
		$element_list = multiPageScreen_addToOptionsList($form_id, $frid);
    $options = array();
    $options['donedest'] = $screen->getVar('donedest');
    $options['savebuttontext'] = $screen->getVar('savebuttontext');
    $options['saveandleavebuttontext'] = $screen->getVar('saveandleavebuttontext');
    $options['alldonebuttontext'] = $screen->getVar('alldonebuttontext');
    $options['printableviewbuttontext'] = $screen->getVar('printableviewbuttontext');
    $options['displayheading'] = $screen->getVar('displayheading');
    $options['reloadblank'] = $screen->getVar('reloadblank') ? "blank" : "entry";
    $options['leavebehaviour'] = $screen->getVar('donedest') ? "url" : "default";
    $options['displaycolumns'] = $screen->getVar('displaycolumns') == 1 ? "onecolumn" : "twocolumns";
    $options['column1width'] = $screen->getVar('column1width') ? $screen->getVar('column1width') : '20%';
    $options['column2width'] = $screen->getVar('column2width') ? $screen->getVar('column2width') : 'auto';
    $options['formelements'] = $screen->getVar('formelements');
    $options['elementdefaults'] = $screen->getVar('elementdefaults');
    $options['element_list'] = $element_list;

    $containerOptions = array(
        'inline',
        'block',
        'contents',
        'flex',
        'grid',
        'inline-block',
        'inline-flex',
        'inline-grid',
        'inline-table',
        'list-item',
        'run-in',
        'table',
        'table-caption',
        'table-column-group',
        'table-header-group',
        'table-footer-group',
        'table-row-group',
        'table-cell',
        'table-column',
        'table-row',
        'none',
        'initial',
        'inherit'
    );
    $templates['displayOptions'] = array_combine(array_values($containerOptions), $containerOptions);

    $templates['displayType'] = $screen->getVar('displayType');
    $templates['displayType'] = $templates['displayType'] ? $templates['displayType'] : 'block';

    $templates['toptemplate'] =  str_replace("&", "&amp;", $screen->getTemplate('toptemplate', $screen->getVar('theme')));
    $templates['bottomtemplate'] = str_replace("&", "&amp;", $screen->getTemplate('bottomtemplate', $screen->getVar('theme')));
    $templates['elementtemplate1'] = str_replace("&", "&amp;", $screen->getTemplate('elementtemplate1', $screen->getVar('theme')));
    $templates['elementtemplate2'] = str_replace("&", "&amp;", $screen->getTemplate('elementtemplate2', $screen->getVar('theme')));
    $templates['elementcontainero'] = str_replace("&", "&amp;", $screen->getTemplate('elementcontainero', $screen->getVar('theme')));
    $templates['elementcontainerc'] = str_replace("&", "&amp;", $screen->getTemplate('elementcontainerc', $screen->getVar('theme')));
    $templates['usingTemplates'] = ($templates['toptemplate'] OR $templates['bottomtemplate'] OR $templates['elementtemplate1'] OR $templates['elementtemplate2'] OR $templates['elementcontainero'] OR $templates['elementcontainerc']);
}


if ($screen_id != "new" && $settings['type'] == 'template') {
    $screen = $screen_handler->get($screen_id);
    $templates['custom_code'] = str_replace("&", "&amp;", $screen_handler->getCustomCode($screen, $screen->getVar('theme'))); // admin UI so load based on current theme set in the admin UI for this screen
    $templates['template'] = str_replace("&", "&amp;", $screen_handler->getTemplateHtml($screen, $screen->getVar('theme'))); // admin UI so load based on current theme set in the admin UI for this screen
    $templates['donedest'] = $screen->getVar('donedest');
    $templates['savebuttontext'] = $screen->getVar('savebuttontext');
    $templates['donebuttontext'] = $screen->getVar('donebuttontext');
    $templates['viewentryscreen'] = $screen->getVar('viewentryscreen');
    $templates['viewentryscreenoptions'] = generateViewEntryScreenOptions($screen->getVar('frid'), $screen->getVar('fid'));

		$framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
		$includePrimaryRelationship = true;
		$frameworks = $framework_handler->getFrameworksByForm($form_id, $includePrimaryRelationship);
		$selectedFramework = $settings['frid'];

		// included file sets up $elementOptions and other variables relied upon below
		include XOOPS_ROOT_PATH.'/modules/formulize/admin/generateTemplateElementHandleHelp.php';
		$templates['variabletemplatehelp'] = $listTemplateHelp;

}

if ($screen_id != "new" && $settings['type'] == 'calendar') {
    $screen = $screen_handler->get($screen_id);
    $form_id = $screen->getVar('fid');
    $templates['toptemplate'] = str_replace("&", "&amp;", $screen->getTemplate('toptemplate', $screen->getVar('theme')));
    $templates['bottomtemplate'] = str_replace("&", "&amp;", $screen->getTemplate('bottomtemplate', $screen->getVar('theme')));
    $templates['usingTemplates'] = ($templates['toptemplate'] OR $templates['bottomtemplate']);
    $templates['caltype'] = $screen->getVar('caltype');
    $data = array();
    foreach($screen->getVar('datasets') as $i=>$dataset) {
        $data['data'][$i]['content']['index'] = $i;
        $data['data'][$i]['name'] = 'Dataset '.($i+1);
        $data['data'][$i]['content']['fid'] = $dataset->getVar('fid');
        $data['data'][$i]['content']['frid'] = $dataset->getVar('frid');
        $data['data'][$i]['content']['scope'] = $dataset->getVar('scope');
        $data['data'][$i]['content']['useaddicons'] = $dataset->getVar('useaddicons');
        $data['data'][$i]['content']['usedeleteicons'] = $dataset->getVar('usedeleteicons');
        $data['data'][$i]['content']['textcolor'] = $dataset->getVar('textcolor');
        $data['data'][$i]['content']['viewentryscreen'] = $dataset->getVar('viewentryscreen');
        $data['data'][$i]['content']['clicktemplate'] = $dataset->getVar('clicktemplate');
        $data['data'][$i]['content']['datehandle'] = $dataset->getVar('datehandle');
    }
		$includePrimaryRelationship = true;
    $frameworks = $framework_handler->getFrameworksByForm($form_id, $includePrimaryRelationship);
    if($selectedFramework = $screen->getVar('frid')) {
        $frameworkObject = $frameworks[$selectedFramework];
    } else {
        $frameworkObject = false;
    }
    include XOOPS_ROOT_PATH.'/modules/formulize/admin/generateTemplateElementHandleHelp.php';
    $templates['variabletemplatehelp'] = $listTemplateHelp;
}

$templates['themes'] = icms_view_theme_Factory::getThemesList();
global $xoopsConfig;
$themeFolder = $screen ? $screen->getVar('theme') : $xoopsConfig['theme_set'];
$themeDefaultPath = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/".$themeFolder."/default/".$settings['type']."/";
$templates['seedtemplates'] = $themeDefaultPath;
if(!file_exists($themeDefaultPath)) {
    $templates['seedtemplates'] = str_replace($themeFolder.'/default', 'default', $themeDefaultPath);
}

// common values should be assigned to all tabs
$common['name'] = $screenName;
$common['title'] = $screenName; // oops, we've got two copies of this data floating around...standardize sometime
$common['sid'] = $screen_id;
$common['fid'] = $form_id;
$common['aid'] = $aid;
$common['uid'] = $xoopsUser->getVar('uid');
$common['screen_handle'] = $screen_handle;

// generate a group list for use with the custom buttons
$sql = "SELECT name, groupid FROM ".$xoopsDB->prefix("groups")." ORDER BY groupid";
if ($res = $xoopsDB->query($sql)) {
    while($array = $xoopsDB->fetchArray($res)) {
        $common['grouplist'][$array['groupid']] = $array['name'];
    }
}

// define tabs for screen sub-page
$adminPage['tabs'][1] = array(
    'name'      => _AM_APP_SETTINGS,
    'template'  => "db:admin/screen_settings.html",
    'content'   => $settings + $common
);


if ($screen_id != "new") {
    $adminPage['tabs'][] = array(
        'name' => _AM_APP_RELATIONSHIPS,
        'template' => "db:admin/screen_relationships.html",
        'content' => $common + $relationshipSettings
    );
}

if ($screen_id != "new" && $settings['type'] == 'form') {
    $adminPage['tabs'][] = array(
        'name'      => _AM_ELE_OPT,
        'template'  => "db:admin/screen_form_options.html",
        'content'   => $options + $common
    );
     $adminPage['tabs'][] = array(
        'name'      => _AM_FORM_SCREEN_TEMPLATES,
        'template'  => "db:admin/screen_form_templates.html",
        'content'   => $templates + $common
    );

}

if ($screen_id != "new" && $settings['type'] == 'multiPage') {
    $adminPage['tabs'][] = array(
        'name'      => _AM_ELE_OPT,
        'template'  => "db:admin/screen_multipage_options.html",
        'content'   => $multipageOptions + $common
    );

    $adminPage['tabs'][] = array(
        'name'      => _AM_FORM_SCREEN_TEXT,
        'template'  => "db:admin/screen_multipage_text.html",
        'content'   => $multipageText + $common
    );

    $adminPage['tabs'][] = array(
        'name'      => _AM_FORM_SCREEN_PAGES,
        'template'  => "db:admin/screen_multipage_pages.html",
        'content'   => $multipagePages + $common
    );

    $adminPage['tabs'][] = array(
        'name'      => _AM_FORM_SCREEN_TEMPLATES,
        'template'  => "db:admin/screen_multipage_templates.html",
        'content'   => $templates + $common
    );
}

if ($screen_id != "new" && $settings['type'] == 'listOfEntries') {
    $adminPage['tabs'][] = array(
        'name'      => _AM_FORM_SCREEN_ENTRIES_DISPLAY,
        'template'  => "db:admin/screen_list_entries.html",
        'content'   => $entries + $common
    );

    $adminPage['tabs'][] = array(
        'name'      => _AM_FORM_SCREEN_HEADINGS_INTERFACE,
        'template'  => "db:admin/screen_list_headings.html",
        'content'   => $headings + $common
    );

    $adminPage['tabs'][] = array(
        'name'      => _AM_FORM_SCREEN_ACTION_BUTTONS,
        'template'  => "db:admin/screen_list_buttons.html",
        'content'   => $buttons + $common
    );

    $adminPage['tabs'][] = array(
        'name'      => _AM_FORM_SCREEN_CUSTOM_BUTTONS,
        'template'  => "db:admin/screen_list_custom.html",
        'content'   => $custom + $common
    );

    $adminPage['tabs'][] = array(
        'name'      => _AM_FORM_SCREEN_TEMPLATES,
        'template'  => "db:admin/screen_list_templates.html",
        'content'   => $templates + $common
    );
}


if ($screen_id != "new" && $settings['type'] == 'template') {
    $adminPage['tabs'][] = array(
        'name'      => _AM_FORM_SCREEN_TEMPLATES_OPTIONS,
        'template'  => "db:admin/screen_template_options.html",
        'content'   => $templates + $common
    );
    $adminPage['tabs'][] = array(
        'name'      => _AM_FORM_SCREEN_TEMPLATES,
        'template'  => "db:admin/screen_template_templates.html",
        'content'   => $templates + $common
    );
}

if ($screen_id != "new" && $settings['type'] == 'calendar') {
    $adminPage['tabs'][] = array(
        'name'      => _AM_CAL_SCREEN_DATA,
        'template'  => "db:admin/screen_calendar_data.html",
        'content'   => $data + $common
    );

    $adminPage['tabs'][] = array(
        'name'      => _AM_CAL_SCREEN_TEMPLATES,
        'template'  => "db:admin/screen_calendar_templates.html",
        'content'   => $templates + $common
    );
}



$adminPage['pagetitle'] = _AM_FORM_SCREEN.$screenName;
$adminPage['needsave'] = true;
$adminPage['show_user_view'] = array("View Screen", XOOPS_URL."/modules/formulize/index.php?sid=$screen_id");

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['url'] = "page=application&aid=$aid&tab=forms";
$breadcrumbtrail[2]['text'] = $appName;
$breadcrumbtrail[3]['url'] = "page=form&aid=$aid&fid=$form_id&tab=screens";
$breadcrumbtrail[3]['text'] = $formName;
$breadcrumbtrail[4]['text'] = $screenName;

// gather the available screens for the specified form and all the others in the relationship
// for use when selecting the view entry screen for list and template screens
function generateViewEntryScreenOptions($relationship_id, $form_id) {

    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObj = $form_handler->get($form_id);

    $screen_handler = xoops_getmodulehandler('screen', 'formulize');
    $criteria_object = new CriteriaCompo(new Criteria('type','multiPage'));
    $criteria_object->add(new Criteria('type','form'), 'OR');
    $criteria_object->add(new Criteria('type','template'), 'OR');
    $viewentryscreenOptionsDB = $screen_handler->getObjects($criteria_object, $form_id);
    $viewentryscreenOptions["none"] = _AM_FORMULIZE_SCREEN_LOE_VIEWENTRYSCREEN_DEFAULT;
    foreach($viewentryscreenOptionsDB as $thisViewEntryScreenOption) {
        $viewentryscreenOptions[$thisViewEntryScreenOption->getVar('sid')] = trans($formObj->getVar('title'))." &mdash; ".printSmart(trans($thisViewEntryScreenOption->getVar('title')), 100);
    }

    $relationshipHandler = xoops_getmodulehandler('frameworks', 'formulize');
		$includePrimaryRelationship = true;
    $relationships = $relationshipHandler->getFrameworksByForm($form_id, $includePrimaryRelationship);

    // if a relationship is in effect, get the screens on the other forms
    if($relationship_id) {
        $parsedFids = array($form_id=>$form_id);
        if($frameworkObject = $relationships[$relationship_id]) {
            if($links = $frameworkObject->getVar('links')) {
                foreach($frameworkObject->getVar('links') as $linkObject) {
                    foreach(array($linkObject->getVar('form1'), $linkObject->getVar('form2')) as $candidateFid) {
                        if(!isset($parsedFids[$candidateFid])) {
                            $candidateFormObj = $form_handler->get($candidateFid);
                            $viewentryscreenOptionsDB = $screen_handler->getObjects($criteria_object, $candidateFid);
                            foreach($viewentryscreenOptionsDB as $thisViewEntryScreenOption) {
                                $viewentryscreenOptions[$thisViewEntryScreenOption->getVar('sid')] = trans($candidateFormObj->getVar('title'))." &mdash; ".printSmart(trans($thisViewEntryScreenOption->getVar('title')), 100);
                            }
                            $parsedFids[$candidateFid] = $candidateFid;
                        }
                    }
                }
            }
        }
    }

    return $viewentryscreenOptions;
}
