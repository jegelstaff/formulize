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

$framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
$screen_id = $_GET['sid'];
// screen settings data
$settings = array();

$aid = isset($_GET['aid']) ? intval($_GET['aid']) : 0;
$form_id = intval($_GET['fid']);

if($screen_id == "new") {
    $settings['type'] = 'listOfEntries';
    $settings['frid'] = 0;
    $config_handler = $config_handler =& xoops_gethandler('config');
    $formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
    $settings['useToken'] = $formulizeConfig['useToken'];
    $screenName = "New screen";
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

    if($settings['type'] == 'listOfEntries') {
        $screen_handler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
    } else if($settings['type'] == 'form') {
        $screen_handler = xoops_getmodulehandler('formScreen', 'formulize');
    } else if($settings['type'] == 'multiPage') {
        $screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
    }
    $screen = $screen_handler->get($screen_id);

    $screenName = $screen->getVar('title');
    $form_id = $screen->form_id;

    $adminPage["template"] = "ABC, mellonfarmers!";
}

if($aid == 0) {
    $appName = "Forms with no app";
} else {
  $application_handler = xoops_getmodulehandler('applications','formulize');
    $appObject = $application_handler->get($aid);
    $appName = $appObject->getVar('name');
}

if ($form_id != "new") {
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($form_id);
    $formName = $formObject->getVar('title');
    $singleentry = $formObject->getVar('single');
}

$elements = array();

$frameworks = $framework_handler->getFrameworksByForm($form_id);
$relationships = $framework_handler->formatFrameworksAsRelationships($frameworks);

$relationshipSettings = array(
  'relationships' => $relationships, 
  'type' => $settings['type']
);

if($screen_id != 'new') {
  $relationshipSettings['frid'] = $screen->getVar('frid');
}

// prepare data for sub-page
if($screen_id != "new" && $settings['type'] == 'listOfEntries') {
  // display data
  $templates = array();
  $templates['toptemplate'] = $screen->getTemplate('toptemplate');
  $templates['bottomtemplate'] = $screen->getTemplate('bottomtemplate');
  $templates['listtemplate'] = $screen->getTemplate('listtemplate');

  // view data
  // gather all the available views
  // setup an option list of all views, as well as one just for the currently selected Framework setting
  $framework_handler =& xoops_getmodulehandler('frameworks', 'formulize');
  $form_handler =& xoops_getmodulehandler('forms', 'formulize');
  $formObj = $form_handler->get($form_id, true); // true causes all elements to be included even if they're not visible.
  $frameworks = $framework_handler->getFrameworksByForm($form_id);
  $selectedFramework = $settings['frid'];
  $views = $formObj->getVar('views');
  $viewNames = $formObj->getVar('viewNames');
  $viewFrids = $formObj->getVar('viewFrids');
  $viewPublished = $formObj->getVar('viewPublished');
  $defaultViewOptions = array();
  $limitViewOptions = array();
  $defaultViewOptions['blank'] = _AM_FORMULIZE_SCREEN_LOE_BLANK_DEFAULTVIEW;
  $defaultViewOptions['mine'] = _AM_FORMULIZE_SCREEN_LOE_DVMINE;
  $defaultViewOptions['group'] = _AM_FORMULIZE_SCREEN_LOE_DVGROUP;
  $defaultViewOptions['all'] = _AM_FORMULIZE_SCREEN_LOE_DVALL;
  for($i=0;$i<count($views);$i++) {

      if(!$viewPublished[$i]) { continue; }
      $defaultViewOptions[$views[$i]] = $viewNames[$i];
      if($viewFrids[$i]) {
          $defaultViewOptions[$views[$i]] .= " (" . _AM_FORMULIZE_SCREEN_LOE_VIEW_ONLY_IN_FRAME . $frameworks[$viewFrids[$i]]->getVar('name') . ")";
      } else {
          $defaultViewOptions[$views[$i]] .= " (" . _AM_FORMULIZE_SCREEN_LOE_VIEW_ONLY_NO_FRAME . ")";
      }
  }
  $limitViewOptions['allviews'] = _AM_FORMULIZE_SCREEN_LOE_DEFAULTVIEWLIMIT;
  $limitViewOptions += $defaultViewOptions;
  unset($limitViewOptions['blank']);
  // get the available screens
  $screen_handler = xoops_getmodulehandler('screen', 'formulize');
  $criteria_object = new CriteriaCompo(new Criteria('type','multiPage'));
  $criteria_object->add(new Criteria('type','form'), 'OR');
  $viewentryscreenOptionsDB = $screen_handler->getObjects($criteria_object, $form_id);
  $viewentryscreenOptions["none"] = _AM_FORMULIZE_SCREEN_LOE_VIEWENTRYSCREEN_DEFAULT;
  foreach($viewentryscreenOptionsDB as $thisViewEntryScreenOption) {
      $viewentryscreenOptions[$thisViewEntryScreenOption->getVar('sid')] = printSmart(trans($thisViewEntryScreenOption->getVar('title')), 100);
  }
	// get all the pageworks page IDs and include them too with a special prefix that will be picked up when this screen is rendered, so we don't confuse "view entry screens" and "view entry pageworks pages" -- added by jwe April 16 2009
	if(file_exists(XOOPS_ROOT_PATH."/modules/pageworks/index.php")) {
			global $xoopsDB;
			$pageworksSQL = "SELECT page_id, page_name, page_title FROM ".$xoopsDB->prefix("pageworks_pages")." ORDER BY page_name, page_title, page_id";
			$pageworksResult = $xoopsDB->query($pageworksSQL);
			while($pageworksArray = $xoopsDB->fetchArray($pageworksResult)) {
					$pageworksName = $pageworksArray['page_name'] ? $pageworksArray['page_name'] : $pageworksArray['page_title'];
					$viewentryscreenOptions["p".$pageworksArray['page_id']] = _AM_FORMULIZE_SCREEN_LOE_VIEWENTRYPAGEWORKS . " -- " . printSmart(trans($pageworksName), 85);
			}
	}

  // create the template information
  $entries = array();
  $entries['defaultviewoptions'] = $defaultViewOptions;
  $entries['defaultview'] = $screen->getVar('defaultview');
  $entries['usecurrentviewlist'] = $screen->getVar('usecurrentviewlist');
  $entries['limitviewoptions'] = $limitViewOptions;
  $entries['limitviews'] = $screen->getVar('limitviews');
  $entries['useworkingmsg'] = $screen->getVar('useworkingmsg');
  $entries['usescrollbox'] = $screen->getVar('usescrollbox');
  $entries['entriesperpage'] = $screen->getVar('entriesperpage');
  $entries['viewentryscreenoptions'] = $viewentryscreenOptions;
  $entries['viewentryscreen'] = $screen->getVar('viewentryscreen');
  $entries['frid'] = $settings['frid'];

  // headings data
  //set options for all elements in entire framework
  //also, collect the handles from a framework if any, and prep the list of possible handles/ids for the list template
  if($selectedFramework and isset($frameworks[$selectedFramework])) {
      $allFids = $frameworks[$selectedFramework]->getVar('fids');
  } else {
      $allFids = array(0=>$form_id);
  }
  $thisFidObj = "";
  $allFidObjs = array();
  $elementOptionsFid = array();
  $listTemplateHelp = array();
  $class = "odd";

  foreach($allFids as $thisFid) {
      unset($thisFidObj);
      if($form_id == $thisFid) {
          $thisFidObj = $formObj;
      } else {
          $thisFidObj = $form_handler->get($thisFid, true); // true causes all elements to be included, even if they're not visible
      }
      $allFidObjs[$thisFid] = $thisFidObj; // for use later on
      $thisFidElements = $thisFidObj->getVar('elementsWithData');
      $thisFidCaptions = $thisFidObj->getVar('elementCaptions');
      $thisFidColheads = $thisFidObj->getVar('elementColheads');
      $thisFidHandles = $thisFidObj->getVar('elementHandles');

      foreach($thisFidElements as $i => $thisFidElement) {
        $elementHeading = $thisFidColheads[$i] ? $thisFidColheads[$i] : $thisFidCaptions[$i];
        $elementOptions[$thisFidHandles[$i]] = printSmart(trans(strip_tags($elementHeading)), 75);
        // for passing to custom button logic, so we know all the element options for each form in framework
        $elementOptionsFid[$thisFid][$thisFidElement] = printSmart(trans(strip_tags($elementHeading)), 75);
        $class = $class == "even" ? "odd" : "even";
        $listTemplateHelp[] = "<tr><td class=$class><nobr><b>" . printSmart(trans(strip_tags($elementHeading)), 75) . "</b></nobr></td><td class=$class><nobr>".$thisFidHandles[$i]."</nobr></td></tr>";
      }
  }
  $templates['listtemplatehelp'] = $listTemplateHelp;

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
  if(count($allFids) > 1) {
    foreach ($allFids as $i=>$thisFid) {
      if($thisFid == $form_id) { continue; } // don't treat the current form as if it's an 'other' form
      $applyToOptions['new_'.$thisFid] = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEW_OTHER . printSmart($allFidObjs[$thisFid]->getVar('title'), 20) . "'";
      $applyToOptions['new_per_selected_'.$thisFid] = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEW_OTHER . printSmart($allFidObjs[$thisFid]->getVar('title'), 20) . _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEWPERSELECTED_OTHER;
    }
  }
  $applyToOptions['custom_code'] = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_CUSTOM_CODE;
  $applyToOptions['custom_html'] = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_CUSTOM_HTML;
  $custom['applytoOptions'] = $applyToOptions;
  if (is_array($screen->getVar('customactions'))) {
    foreach($screen->getVar('customactions') as $buttonId=>$buttonData) {
      $custom['custombuttons'][$buttonId]['content'] = $buttonData;
      $custom['custombuttons'][$buttonId]['content']['id'] = $buttonId; // add id to the date for the template
      $custom['custombuttons'][$buttonId]['name'] = $buttonData['handle'];
      $custom['custombuttons'][$buttonId]['groups'] = unserialize($buttonData['groups']);
      foreach($buttonData as $key=>$value) {
        if(is_numeric($key)) { // effects have numeric keys
          if($buttonData['applyto'] == 'custom_code') {
            $custom['custombuttons'][$buttonId]['content'][$key]['description'] = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_CUSTOM_CODE_DESC;
          } elseif($buttonData['applyto'] == 'custom_html') {
            $custom['custombuttons'][$buttonId]['content'][$key]['description'] = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_CUSTOM_HTML_DESC;
          } else {
            if(strstr($buttonData['applyto'], "new_per_selected_")) {
              $thisEffectFid = substr($buttonData['applyto'], 17);
            } elseif(strstr($buttonData['applyto'], "new_")) {
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

if($screen_id != "new" && $settings['type'] == 'multiPage') {
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
  $options = getElementCaptions($form_id);

	// add in elements from other forms in the framework, by looping through each link in the framework and checking if it is a display as one, one-to-one link
	// added March 20 2008, by jwe
	$frid = $screen->getVar("frid");
	if($frid) {
			$framework_handler =& xoops_getModuleHandler('frameworks');
			$frameworkObject = $framework_handler->get($frid);
			foreach($frameworkObject->getVar("links") as $thisLinkObject) {
					if($thisLinkObject->getVar("unifiedDisplay") AND $thisLinkObject->getVar("relationship") == 1) {
							$thisFid = $thisLinkObject->getVar("form1") == $form_id ? $thisLinkObject->getVar("form2") : $thisLinkObject->getVar("form1");
            $options = getElementCaptions($thisFid, $options);
					}
			}
	}

    // get page titles
    $pageTitles = $screen->getVar("pagetitles");
    $elements = $screen->getVar("pages");
    $conditions = $screen->getVar("conditions");

  // group entries
  $pages = array();
	for($i=0;$i<(count($pageTitles)+$pageCounterOffset);$i++) {
    $pages[$i]['name'] = $pageTitles[$i];
    $pages[$i]['content']['index'] = $i;
    $pages[$i]['content']['number'] = $i+1;
    $pages[$i]['content']['title'] = $pageTitles[$i];
		foreach($elements[$i] as $thisElement) {
			$pages[$i]['content']['elements'][] = $options[$thisElement];
		}
  }

  // options data
  $multipageOptions = array();
  $multipageOptions['allformoptions'] = $allFormOptions;
  $multipageOptions['paraentryform'] = $screen->getVar('paraentryform');
  $multipageOptions['paraentryrelationship'] = $screen->getVar('paraentryrelationship');
  $multipageOptions['donedest'] = $screen->getVar('donedest');
  $multipageOptions['finishisdone'] = $screen->getVar('finishisdone');
  $multipageOptions['buttontext'] = $screen->getVar('buttontext');
  $multipageOptions['printall'] = $screen->getVar('printall');

  // text data
  $multipageText = array();
  $multipageText['introtext'] = undoAllHTMLChars($screen->getVar('introtext', "e"));
  $multipageText['thankstext'] = undoAllHTMLChars($screen->getVar('thankstext', "e")); // need the e to make sure it doesn't convert links to clickable HTML!

  // template data
  $multipageTemplates = array();   // Added by Gordon Woodmansey, 29-08-2012
  $multipageTemplates['toptemplate'] = $screen->getTemplate('toptemplate');
  $multipageTemplates['elementtemplate'] = $screen->getTemplate('elementtemplate'); 
  $multipageTemplates['bottomtemplate'] = $screen->getTemplate('bottomtemplate'); 

  // pages data
  $multipagePages = array();
  $multipagePages['pages'] = $pages;
}

if($screen_id != "new" && $settings['type'] == 'form') {
  $element_list = getElementCaptions($form_id);
  $frid = $screen->getVar("frid");
  if($frid) {
      $framework_handler =& xoops_getModuleHandler('frameworks');
      $frameworkObject = $framework_handler->get($frid);
      foreach($frameworkObject->getVar("links") as $thisLinkObject) {
          if($thisLinkObject->getVar("unifiedDisplay") AND $thisLinkObject->getVar("relationship") == 1) {
              $thisFid = $thisLinkObject->getVar("form1") == $form_id ? $thisLinkObject->getVar("form2") : $thisLinkObject->getVar("form1");
              $element_list = getElementCaptions($thisFid, $element_list);
          }
      }
  }

  $options = array();
  $options['donedest'] = $screen->getVar('donedest');
  $options['savebuttontext'] = $screen->getVar('savebuttontext');
  $options['alldonebuttontext'] = $screen->getVar('alldonebuttontext');
  $options['displayheading'] = $screen->getVar('displayheading');
  $options['reloadblank'] = $screen->getVar('reloadblank') ? "blank" : "entry";
  $options['formelements'] = $screen->getVar('formelements');
  $options['element_list'] = $element_list;
}

// common values should be assigned to all tabs
$common['name'] = $screenName;
$common['title'] = $screenName; // oops, we've got two copies of this data floating around...standardize sometime
$common['sid'] = $screen_id;
$common['fid'] = $form_id;
$common['aid'] = $aid;

// generate a group list for use with the custom buttons
$sql = "SELECT name, groupid FROM ".$xoopsDB->prefix("groups")." ORDER BY groupid";
if($res = $xoopsDB->query($sql)) {
	while($array = $xoopsDB->fetchArray($res)) {
		$common['grouplist'][$array['groupid']] = $array['name'];
	}
}

// define tabs for screen sub-page
$adminPage['tabs'][1] = array(
	'name'		=> _AM_APP_SETTINGS,
	'template'	=> "db:admin/screen_settings.html",
	'content'	=> $settings + $common
);

$adminPage['tabs'][] = array(
	'name'		=> _AM_APP_RELATIONSHIPS,
	'template'	=> "db:admin/screen_relationships.html",
	'content'	=> $common + $relationshipSettings
); 

if($screen_id != "new" && $settings['type'] == 'form') {
	$adminPage['tabs'][] = array(
		'name' 		=> _AM_ELE_OPT,
		'template' 	=> "db:admin/screen_form_options.html",
		'content' 	=> $options + $common
	);
}

if($screen_id != "new" && $settings['type'] == 'multiPage') {
	$adminPage['tabs'][] = array(
		'name' 		=> _AM_ELE_OPT,
		'template' 	=> "db:admin/screen_multipage_options.html",
		'content' 	=> $multipageOptions + $common
	);

	$adminPage['tabs'][] = array(
		'name' 		=> _AM_FORM_SCREEN_TEXT,
		'template' 	=> "db:admin/screen_multipage_text.html",
		'content' 	=> $multipageText + $common
	);

	$adminPage['tabs'][] = array(
		'name' 		=> _AM_FORM_SCREEN_PAGES,
		'template' 	=> "db:admin/screen_multipage_pages.html",
		'content' 	=> $multipagePages + $common
	);

	$adminPage['tabs'][] = array(
		'name' 		=> _AM_FORM_SCREEN_TEMPLATES,
		'template' 	=> "db:admin/screen_multipage_templates.html",
		'content' 	=> $multipageTemplates + $common
	);
}

if($screen_id != "new" && $settings['type'] == 'listOfEntries') {
	$adminPage['tabs'][] = array(
		'name' 		=> _AM_FORM_SCREEN_ENTRIES_DISPLAY,
		'template' 	=> "db:admin/screen_list_entries.html",
		'content' 	=> $entries + $common
	);

	$adminPage['tabs'][] = array(
		'name'		=> _AM_FORM_SCREEN_HEADINGS_INTERFACE,
		'template'	=> "db:admin/screen_list_headings.html",
		'content'	=> $headings + $common
	);

	$adminPage['tabs'][] = array(
		'name'		=> _AM_FORM_SCREEN_ACTION_BUTTONS,
		'template'	=> "db:admin/screen_list_buttons.html",
		'content'	=> $buttons + $common
	);

	$adminPage['tabs'][] = array(
		'name'		=> _AM_FORM_SCREEN_CUSTOM_BUTTONS,
		'template'	=> "db:admin/screen_list_custom.html",
		'content'	=> $custom + $common
	);

	$adminPage['tabs'][] = array(
		'name'		=> _AM_FORM_SCREEN_TEMPLATES,
		'template'	=> "db:admin/screen_list_templates.html",
		'content'	=> $templates + $common
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
