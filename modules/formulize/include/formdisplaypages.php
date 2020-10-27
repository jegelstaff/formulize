<?php

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions 					     ##
##  Project: Formulize                                                       ##
###############################################################################

//THIS FILE HANDLES THE DISPLAY OF FORMS AS MULTIPLE PAGES.  

global $xoopsConfig;
// load the formulize language constants if they haven't been loaded already
	if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
	} else {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
	}

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/elementdisplay.php";

function displayFormPages($formframe, $entry="", $mainform="", $pages, $conditions="", $introtext="", $thankstext="", $done_dest="", $button_text="", $settings=array(), $overrideValue="", $printall=0, $screen=null, $saveAndContinueButtonText=null) { // nmc 2007.03.24 - added 'printall'
	
	formulize_benchmark("Start of displayFormPages.");
	
    // instantiate multipage screen handler just because we might need some functions from that file (plain functions, not methods on the class, because they're not necessarily related to handling a screen, and we might not even have a screen in effect)
    $multiPageScreenHandler = xoops_getmodulehandler('multiPageScreen', 'formulize');
    
    // pickup a declared page that we're going back onto...will/might include screen id after a hyphen
    if(isset($_POST['parent_page'])) {
        $parent_page = strstr($_POST['parent_page'], ',') ? explode(',',$_POST['parent_page']) : array($_POST['parent_page']);
        $lastKey = count($parent_page)-1;
        $_POST['formulize_currentPage'] = $parent_page[$lastKey];
    }
	
    $currentPageScreen = 0;
    // reset $_POST['formulize_currentPage'] which is referred to many places to get the official page we're on
    if(isset($_POST['formulize_currentPage']) AND strstr($_POST['formulize_currentPage'],'-')) {
        $cpParts = explode('-',$_POST['formulize_currentPage']);
        $_POST['formulize_currentPage'] = $cpParts[0];
        $currentPageScreen = $cpParts[1];
    }
    // set prevPage, last page that the user was on, not necessarily the previous page numerically
    if(isset($_POST['formulize_prevPage']) AND strstr($_POST['formulize_prevPage'],'-')) {
        $cpParts = explode('-',$_POST['formulize_prevPage']);
        $prevPage = $cpParts[0];
        $prevScreen = $cpParts[1];
    } elseif(isset($_POST['formulize_prevPage'])) {
        $prevPage = intval($_POST['formulize_prevPage']);
    } else {
        $prevPage = 1;
    }
    
	// extract the optional page titles from the $pages array for use in the jump to box
	// NOTE: pageTitles array must start with key 1, not 0.  Page 1 is the first page of the form
	$pageTitles = array();
	if(isset($pages['titles'])) {
		$pageTitles = $pages['titles'];
		unset($pages['titles']);
	}
	
	if(!$saveAndContinueButtonText AND isset($_POST['formulize_saveAndContinueButtonText'])) { $saveAndContinueButtonText = unserialize($_POST['formulize_saveAndContinueButtonText']); }
	if(!$done_dest AND $_POST['formulize_doneDest']) { $done_dest = $_POST['formulize_doneDest']; }
	if(!$button_text AND $_POST['formulize_buttonText']) { $button_text = $_POST['formulize_buttonText']; }
	
	
	list($fid, $frid) = getFormFramework($formframe, $mainform);
	
	$thankstext = $thankstext ? $thankstext : _formulize_DMULTI_THANKS; 
	$introtext = $introtext ? $introtext : "";
	
	global $xoopsUser;
	
	$mid = getFormulizeModId();
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
	$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
	$gperm_handler =& xoops_gethandler('groupperm');
	$member_handler =& xoops_gethandler('member');
	$single_result = getSingle($fid, $uid, $groups, $member_handler, $gperm_handler, $mid);
	
	// if this function was called without an entry specified, then assume the identity of the entry we're editing (unless this is a new save, in which case no entry has been made yet)
	// no handling of cookies here, so anonymous multi-page surveys will not benefit from that feature
	// this emphasizes how we need to standardize a lot of these interfaces with a real class system
	if(!$entry AND $_POST['entry'.$fid]) {
		$entry = intval($_POST['entry'.$fid]);
    } elseif(!$entry AND $_POST['form_'.$fid.'_rendered_entry']) {
        $entry = intval($_POST['form_'.$fid.'_rendered_entry'][0]);
	} elseif(!$entry) { // or check getSingle to see what the real entry is
		$entry = $single_result['flag'] ? $single_result['entry'] : 0;
	}
	
	// formulize_newEntryIds is set when saving data
	if((!$entry OR $entry == 'new') AND isset($GLOBALS['formulize_newEntryIds'][$fid])) {
		$entry = $GLOBALS['formulize_newEntryIds'][$fid][0];
	} elseif(!$entry) {
        $entry = 'new';
	}
	
	$owner = getEntryOwner($entry, $fid);
	
    if($currentPageScreen) {
        if($screen AND $currentPageScreen == $screen->getVar('sid')) {
            $currentPage = $_POST['formulize_currentPage'];
        } else {
            $currentPage = 1;    
        }
    } else {
	$currentPage = isset($_POST['formulize_currentPage']) ? $_POST['formulize_currentPage'] : 1;
    }
	$thanksPage = count($pages) + 1;
	
	// debug control:
	$currentPage = (isset($_GET['debugpage']) AND is_numeric($_GET['debugpage'])) ? $_GET['debugpage'] : $currentPage;
	
    $usersCanSave = formulizePermHandler::user_can_edit_entry($fid, $uid, $entry);
	
	if($pages[$prevPage][0] !== "HTML" AND $pages[$prevPage][0] !== "PHP") { // remember prevPage is the last page the user was on, not the previous page numerically
		
		if(isset($_POST['form_submitted']) AND $usersCanSave) { // if something was maybe saved, we might need to assume the identify of the entry just saved, so see if we can figure that out
	
			include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
	
			$entries[$fid][0] = $entry;
	
			if($frid) { 
				$linkResults = checkForLinks($frid, array(0=>$fid), $fid, $entries); 
				unset($entries);
				$entries = $linkResults['entries'];
			} else {
                $entries = $GLOBALS['formulize_allSubmittedEntryIds']; // set in readelements.php
			}
	
			// if there has been no specific entry specified yet, then assume the identity of the entry that was just saved -- assumption is it will be a new save
			// from this point forward in time, this is the only entry that should be involved, since the 'entry'.$fid condition above will put this value into $entry even if this function was called with a blank entry value
			if(!$entry) {
				$entry = $entries[$fid][0];
			}
			
            unset($_POST['form_submitted']);
		}
	}

    // there are several points above where $entry is set, and now that we have a final value, store in ventry
    if ($entry > 0) {
        $settings['ventry'] = $entry;
    }

	// check to see if there are conditions on this page, and if so are they met
	// if the conditions are not met, move on to the next page and repeat the condition check
	// conditions only checked once there is an entry!
    
	$pagesSkipped = false;
	if(is_array($conditions) AND (!$currentPageScreen OR ($screen AND $currentPageScreen == $screen->getVar('sid')))) {
		$conditionsMet = false;
        $element_handler = xoops_getmodulehandler('elements','formulize');
		while(!$conditionsMet) {
			if(isset($conditions[$currentPage]) AND count($conditions[$currentPage][0])>0) { // conditions on the current page
				if(pageMeetsConditions($conditions, $currentPage, $entry, $fid, $frid) == false) { 
					if($prevPage <= $currentPage) {
						$currentPage++;
					} else {
						$currentPage--;
					}
					$pagesSkipped = true;
				} else {
					$conditionsMet = true;
				}
			} else {
				// no conditions on the current page
				$conditionsMet = true;
			}
		}
	}
	
	if($currentPage > 1) {
	  $previousPage = $currentPage-1; // previous page numerically
	} else {
	  $previousPage = "none";
	}
	
	$nextPage = $currentPage+1;
	
	$done_dest = $done_dest ? $done_dest : getCurrentURL();
	$done_dest = substr($done_dest,0,4) == "http" ? $done_dest : "http://".$done_dest;
	
	$GLOBALS['formulize_displayingMultipageScreen'] = $screen ? $screen->getVar('sid') : true;
	
	// display a form if that's what this page is...
	if($currentPage != $thanksPage AND $pages[$currentPage][0] !== "HTML" AND $pages[$currentPage][0] !== "PHP") {
	
		$buttonArray = array(0=>"{NOBUTTON}", 1=>"{NOBUTTON}", 2=>"{NOBUTTON}");
		foreach($pages[$currentPage] as $element) {
		  $elements_allowed[] = $element;
	  }
		$forminfo['elements'] = $elements_allowed;
		$forminfo['formframe'] = $formframe;
		$titleOverride = isset($pageTitles[$currentPage]) ? trans($pageTitles[$currentPage]) : "all"; // we can pass in any text value as the titleOverride, and it will have the same effect as "all", but the alternate text will be used as the title for the form
	
		$settings['formulize_currentPage'] = $currentPage;
		$settings['formulize_prevPage'] = $prevPage;
        $settings['formulize_prevScreen'] = $prevScreen;
	
		formulize_benchmark("Before drawing nav.");
	
        $standardText = ($screen AND ($screen->getVar('navstyle') != 2 OR $currentPage != 1)) ? _formulize_DMULTI_PREV : _formulize_SAVE_AND_LEAVE;
		$previousButtonText = (is_array($saveAndContinueButtonText) AND isset($saveAndContinueButtonText['previousButtonText'])) ? $saveAndContinueButtonText['previousButtonText'] : $standardText;
		if($usersCanSave AND $nextPage==$thanksPage) {
		    $nextButtonText = (is_array($saveAndContinueButtonText) AND $saveAndContinueButtonText['saveButtonText']) ? $saveAndContinueButtonText['saveButtonText'] :  _formulize_DMULTI_SAVE;
		} else {
		    $nextButtonText = (is_array($saveAndContinueButtonText) AND $saveAndContinueButtonText['nextButtonText']) ? $saveAndContinueButtonText['nextButtonText'] : _formulize_DMULTI_NEXT;
		}
		$previousPageButton = generatePrevNextButtonMarkup("prev", $previousButtonText, $usersCanSave, $nextPage, $previousPage, $thanksPage);
		$nextPageButton = generatePrevNextButtonMarkup("next", $nextButtonText, $usersCanSave, $nextPage, $previousPage, $thanksPage);
		$savePageButton = generatePrevNextButtonMarkup("save", _formulize_SAVE, $usersCanSave, $nextPage, $previousPage, $thanksPage);
		$totalPages = count($pages);
		$skippedPageMessage = $pagesSkipped ? _formulize_DMULTI_SKIP : "";
		$pageSelectionList = pageSelectionList($currentPage, $totalPages, $pageTitles, "above", $conditions, $entry, $fid, $frid);   // calling for the 'above' drawPageNav 

        // setting up the basic templateVars for all templates
        $templateVariables = array('previousPageButton' => $previousPageButton, 'nextPageButton' => $nextPageButton, 'savePageButton' => $savePageButton,
            'totalPages' => $totalPages, 'currentPage' => $currentPage, 'skippedPageMessage' => $skippedPageMessage,
            'pageSelectionList'=>$pageSelectionList, 'pageTitles' => $pageTitles, 'entry_id'=>$entry, 'form_id'=>$fid, 'owner'=>$owner);

        // cache the rendered header, in case this is the header we need for the page right now
        static $multipageHeader = array();
        static $multipageFooter = array();
        global $multipageInstances;
        $multipageInstances = !is_numeric($multipageInstances) ? -1 : $multipageInstances;
        $multipageInstances++;
        ob_start();
		print "<form name=\"pageNavOptions_above\" id=\"pageNavOptions_above\">\n";
		if($screen AND $toptemplate = $screen->getTemplate('toptemplate')) {
		    formulize_renderTemplate('toptemplate', $templateVariables, $screen->getVar('sid'));
		} else {
            drawPageNav($usersCanSave, "above", $screen, $templateVariables, $conditions, $entry, $fid, $frid);
		}
        print "</form>\n";
        $multipageHeader[$multipageInstances] = ob_get_clean();
		
		formulize_benchmark("After drawing nav/before displayForm.");
		
        // if this is our first time running through all this, then start buffering
		if(!isset($GLOBALS['formulize_completedFormRendering'])) {
            $GLOBALS['formulize_completedFormRendering'] = false;
            ob_start();
        }
        
	    // need to check for the existence of an elementtemplate property in the screen, like we did with the top and bottom templates
	    // if there's an eleemnt template, then do this loop, otherwise, do the displayForm call like normal
	    if ($screen AND $elementtemplate = $screen->getTemplate('elementtemplate')) {  // Code added by Julian 2012-09-04 and Gordon Woodmansey 2012-09-05 to render the elementtemplate
		    if(!security_check($fid, $entry)) {
			exit();
		    }
		    // start the form manually...
		    $formObjectForRequiredJS = new formulize_themeForm('form object for required js', 'formulize_mainform', getCurrentURL(), "post", true);
		    $element_handler = xoops_getmodulehandler('elements', 'formulize');
		    print "<div id='formulizeform'><form id='formulize_mainform' name='formulize_mainform' action='".getCurrentURL()."' method='post' onsubmit='return xoopsFormValidate_formulize_mainform('', window.document.formulize_mainform);' enctype='multipart/form-data'>";
		    foreach ($elements_allowed as $thisElement) {   // entry is a recordid, $thisElement is the element id
			    // to get the conditional logic to be captured, we should buffer the drawing of the displayElement, and then output that later, because when displayElement does NOT return an object, then we get conditional logic -- subform rendering does it this way
			    unset($form_ele); // previously set elements may linger when added to the form object, due to assignment of objects by reference or something odd like that...legacy of old code in the form class I think
			    $deReturnValue = displayElement("", $thisElement, $entry, false, $screen, null, false);
			    if (is_array($deReturnValue)) {
				    $form_ele = $deReturnValue[0];
				    $isDisabled = $deReturnValue[1];
				    if(isset($deReturnValue[2])) {
					$hiddenElements = $deReturnValue[2];
				    }
			    } else {
				    $form_ele = $deReturnValue;
				    $isDisabled = false;
			    }
			    if ($form_ele == "not_allowed") {
				continue;
			    } elseif($form_ele == "hidden") {
				$cueEntryValue = $entry ? $entry : "new";
				$cueElement = new xoopsFormHidden("decue_".$fid."_".$cueEntryValue."_".$thisElement, 1);
				print $cueElement->render();
				if(is_array($hiddenElements)) {
					foreach($hiddenElements as $thisHiddenElement) {
						if($is_object($thisHiddenElement)) {
							print $thisHiddenElement->render()."\n";
						}
					}
				} elseif(is_object($hiddenElements)) {
					print $hiddenElements->render()."\n";
				}
				continue;
			    } else {
				$thisElementObject = $element_handler->get($thisElement);
				$req = !$isDisabled ? intval($thisElementObject->getVar('ele_req')) : 0; 
				$formObjectForRequiredJS->addElement($form_ele, $req);
				$elementMarkup = $form_ele->render();
				$elementCaption = displayCaption("", $thisElement);
				$elementDescription = displayDescription("", $thisElement);
				$templateVariables['elementObjectForRendering'] = $form_ele;
				$templateVariables['elementCaption'] = $elementCaption;  // here we can assume that the $previousPageButton etc has not be changed before rendering 
			        $templateVariables['elementMarkup'] = $elementMarkup;
			        $templateVariables['elementDescription'] = $elementDescription;
			        $templateVariables['element_id'] = $thisElement;
				formulize_renderTemplate('elementtemplate', $templateVariables, $screen->getVar('sid'));
			        
			    }
		    }
		    // now we also need to add in some bits that are necessary for the form submission logic to work...borrowed from parts of formdisplay.php mostly...this should be put together into a more distinct rendering system for forms, so we can call the pieces as needed
            $currentPageToSend = $screen ? $settings['formulize_currentPage'].'-'.$screen->getVar('sid') : $settings['formulize_currentPage'];
            $prevPageToSend = $screen ? $settings['formulize_prevPage'].'-'.$screen->getVar('sid') : $settings['formulize_prevPage'];
		    print "<input type=hidden name=formulize_currentPage value='".$currentPageToSend."'>";
		    print "<input type=hidden name=formulize_prevPage value='".$prevPageToSend."'>";
		    print "<input type=hidden name=formulize_doneDest value='".$settings['formulize_doneDest']."'>";
		    print "<input type=hidden name=formulize_buttonText value='".$settings['formulize_buttonText']."'>";
            print "<input type=hidden name=deletesubsflag value=0>";
		    print "<input type=hidden name=ventry value='".$settings['ventry']."'>";
		    print $GLOBALS['xoopsSecurity']->getTokenHTML();
		    if($entry) {
			    print "<input type=hidden name=entry".$fid." value=".intval($entry).">"; // need this to persist the entry that the user is 
		    }
		    print "</form></div>";

		    drawJavascript(!$usersCanSave); // inverse of whether the user can save, will be the correct 'nosave' flag (we need to pass true if the user cannot save)
		    // need to create the form object, and add all the rendered elements to it, and then we'll have working required elements if we render the validation logic for the form
		    print $formObjectForRequiredJS->renderValidationJS(true, true); // with tags, true, skip the extra js that checks for the formulize theme form divs around the elements so that conditional animation works, true
		    // print "<script type=\"text/javascript\">function xoopsFormValidate_formulize_mainform(){return true;}</script>"; // shim for the validation javascript that is created by the xoopsThemeForms, and which our saving logic currently references...saving won't work without this...we should actually render the proper validation logic at some point, but not today.
            $GLOBALS['formulize_completedFormRendering'] = true;
	    } else {
            if(count($elements_allowed)==0) {
                print "Error: there are no form elements specified for page number $currentPage. Please contact the webmaster.";
            } else {
                displayForm($forminfo, $entry, $mainform, "", $buttonArray, $settings, $titleOverride, $overrideValue, "", "", 0, 0, $printall, $screen); // nmc 2007.03.24 - added empty params & '$printall'
            }
	    }
	    
		formulize_benchmark("After displayForm.");
    }

    if($currentPage != $thanksPage) {
	    // have to get the new value for $pageSelection list if the user requires it on the users view.
	    $pageSelectionList = pageSelectionList($currentPage, $totalPages, $pageTitles, "below", $conditions, $entry, $fid, $frid);
        ob_start();
	    print "<form name=\"pageNavOptions_below\" id=\"pageNavOptions_below\">\n";
        $templateVariables['pageSelectionList'] = $pageSelectionList; // assign the new pageSelectionList, since it was redone for the bottom section
	    if ($screen AND $bottomtemplate = $screen->getTemplate('bottomtemplate')) { 
		    $templateVariables['pageSelectionList'] = $pageSelectionList; // assign the new pageSelectionList, since it was redone for the bottom section
		    formulize_renderTemplate('bottomtemplate', $templateVariables, $screen->getVar('sid'));
	    } else {
		    drawPageNav($usersCanSave, "below", $screen, $templateVariables, $conditions, $entry, $fid, $frid);
	    }
	    print "</form>";
        $multipageFooter[$multipageInstances] = ob_get_clean();
    }
    
    // if we have actually completed rendering (and not simply going around and around in a recursive loop) then capture what has been rendered, and then output it with the header for the most recent run through (ie: the innermost nested multipage subform call, if that's the circumstance that caused the recursive looping)
    // since we're checking current page's screen against the declared screen we're rendering, this won't actually cache the top and bottom templates for the parent screen, but we could modify that to still get the top template for screens we're not rendering, so that we can do nested tabs later if we want
    if($currentPage == $thanksPage
       OR ($currentPage != $thanksPage AND ($pages[$currentPage][0] === "HTML" OR $pages[$currentPage][0] === "PHP"))
       OR (isset($GLOBALS['formulize_completedFormRendering']) AND $GLOBALS['formulize_completedFormRendering'])
       ) {
        $formRendering = ob_get_clean();

        include XOOPS_ROOT_PATH.'/modules/formulize/include/multipage_boilerplate.php';
        
        print $multipageHeader[$multipageInstances].$formRendering.$multipageFooter[$multipageInstances];
        unset($GLOBALS['formulize_completedFormRendering']);
    }

    
    
    formulize_benchmark("End of displayFormPages.");
} // end of the function!


function drawPageNav($usersCanSave="", $aboveBelow, $screen, $templateVariables, $conditions, $entry_id, $fid, $frid)
{
    global $xoopsTpl;
    $xoopsTpl->assign("usersCanSave", $usersCanSave);
    $xoopsTpl->assign("currentPage", $templateVariables['currentPage']);
    $xoopsTpl->assign("totalPages", $templateVariables['totalPages']);
    unset($templateVariables['pageTitles'][0]);
    $templatePageTitles = array();
    foreach($templateVariables['pageTitles'] as $i=>$title) {
        if(pageMeetsConditions($conditions, $i, $entry_id, $fid, $frid)) {
            $templatePageTitles[$i] = $title;
        }
    }
    $xoopsTpl->assign("pageTitles", $templatePageTitles);
    $xoopsTpl->assign("aboveBelow", $aboveBelow);
    if($aboveBelow == 'below') {
        $xoopsTpl->assign("bottom", 'Bottom');
    } else {
        $xoopsTpl->assign("bottom", '');
    }
    $xoopsTpl->assign("nextPageButton", $templateVariables['nextPageButton']);
    $xoopsTpl->assign("previousPageButton", $templateVariables['previousPageButton']);
    $xoopsTpl->assign("skippedPageMessage", $templateVariables['skippedPageMessage']);
    $xoopsTpl->assign("pageSelectionList", $templateVariables['pageSelectionList']);
    $xoopsTpl->assign("savePageButton", $templateVariables['savePageButton']);
    global $formulize_displayingSubform;
    if($formulize_displayingSubform) {
        $xoopsTpl->assign("saveAndLeave", trans(_formulize_SAVE_AND_GOBACK));
    } else {
        $xoopsTpl->assign("saveAndLeave", trans(_formulize_SAVE_AND_LEAVE));
    }
    $xoopsTpl->assign("_formulize_DMULTI_PAGE", _formulize_DMULTI_PAGE);
    $xoopsTpl->assign("_formulize_DMULTI_OF", _formulize_DMULTI_OF);
    $xoopsTpl->assign("_formulize_DMULTI_JUMPTO", _formulize_DMULTI_JUMPTO);
    if($screen->getVar('navstyle')==1) {
        if($aboveBelow!='below') {
            $xoopsTpl->display("file:".XOOPS_ROOT_PATH."/modules/formulize/templates/multipage-navigation2-above.html");
        } else {
            $xoopsTpl->display("file:".XOOPS_ROOT_PATH."/modules/formulize/templates/multipage-navigation2-below.html");
        }
    } elseif($screen->getVar('navstyle')==2) {
        if($aboveBelow!='below') {
            $xoopsTpl->display("file:".XOOPS_ROOT_PATH."/modules/formulize/templates/multipage-navigation3-above.html");
        } else {
            $xoopsTpl->display("file:".XOOPS_ROOT_PATH."/modules/formulize/templates/multipage-navigation3-below.html");
        }
    } else {
        $xoopsTpl->display("file:".XOOPS_ROOT_PATH."/modules/formulize/templates/multipage-navigation.html");
    }

    
}

// THIS FUNCTION GENERATES THE MARKUP FOR THE PREVIOUS AND NEXT BUTTONS
function generatePrevNextButtonMarkup($buttonType, $buttonText, $usersCanSave, $nextPage, $previousPage, $thanksPage) {
    $buttonMarkup = "";
    
    switch($buttonType) {
	case 'next':
		$buttonJavascriptAndExtraCode = "onclick=\"javascript:submitForm($nextPage, ".($previousPage+1).");return false;\"";
		break;
	case 'prev':
		$buttonJavascriptAndExtraCode = "onclick=\"javascript:submitForm($previousPage, ".($previousPage+1).");return false;\"";	
		break;
	case 'save':
		$buttonJavascriptAndExtraCode = "onclick=\"javascript:submitForm(".($previousPage+1).", ".($previousPage+1).");return false;\"";
    }
    
    if($buttonType == "next" OR $buttonType == "save") {
        $buttonMarkup = "<input type=button name='$buttonType' id='$buttonType' class='formulize-form-submit-button' value='" . $buttonText . "' $buttonJavascriptAndExtraCode>\n";
    } elseif($buttonType == "prev") {
        if($previousPage == "none") {
            $buttonJavascriptAndExtraCode = "onclick=\"javascript:submitForm($thanksPage, 1);return false;\"";
        }
        $buttonMarkup = "<input type=button name=prev id=prev class='formulize-form-submit-button' value='" . $buttonText . "' $buttonJavascriptAndExtraCode>\n";
    }
    return $buttonMarkup;
}


function pageSelectionList($currentPage, $countPages, $pageTitles, $aboveBelow, $conditions, $entry_id, $fid, $frid) {

	static $pageSelectionList = array();
	
    $cacheKey = md5(serialize(func_get_args()));
    
	if(isset($pageSelectionList[$cacheKey])) {
		return $pageSelectionList[$cacheKey];
	}

	$pageSelectionList[$cacheKey] .= "<select name=\"pageselectionlist_$aboveBelow\" id=\"pageselectionlist_$aboveBelow\" size=\"1\" onchange=\"javascript:pageJump(this.form.pageselectionlist_$aboveBelow.options, $currentPage);\">\n";
	for($page=1;$page<=$countPages;$page++) {
        if(pageMeetsConditions($conditions, $page, $entry_id, $fid, $frid)) {
		if(isset($pageTitle[$page]) AND strstr($pageTitles[$page], "[")) {
			$title = " &mdash; " . trans($pageTitles[$page]); // translation can be expensive, so only do it if we have to (regular expression matching is not pretty)
		} elseif(isset($pageTitles[$page])) {
			$title = " &mdash; " . $pageTitles[$page];
		} else {
			$title = "";
		}
		$pageSelectionList[$cacheKey] .= "<option value=$page";
		$pageSelectionList[$cacheKey] .= $page == $currentPage ? " selected=true>" : ">";
		$pageSelectionList[$cacheKey] .= $page . $title . "</option>\n";
	}
	}
	$pageSelectionList[$cacheKey] .= "</select>";
	return $pageSelectionList[$cacheKey];
}
