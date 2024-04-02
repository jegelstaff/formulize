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

function displayFormPages($formframe, $entry, $mainform, $pages, $conditions="", $introtext="", $thankstext="", $done_dest="", $button_text=array(), $settings=array(), $overrideValue="", $printall=0, $screen=null, $saveAndContinueButtonText=null, $elements_only = false) { // nmc 2007.03.24 - added 'printall'

    formulize_benchmark("Start of displayFormPages.");

    global $xoopsUser;
    if(!isset($_POST['parent_entry']) AND !isset($_POST['parent_form']) AND !isset($_POST['parent_page']) AND !isset($_POST['parent_subformElementId'])
       AND isset($_POST['go_back_form']) AND $_POST['go_back_form'] AND isset($_POST['go_back_entry']) AND $_POST['go_back_entry'] AND (!isset($_POST['ventry']) OR !$_POST['ventry'])) {
        $entry = setupParentFormValuesInPostAndReturnEntryId();
    }

    // instantiate multipage screen handler just because we might need some functions from that file (plain functions, not methods on the class, because they're not necessarily related to handling a screen, and we might not even have a screen in effect)
    $multiPageScreenHandler = xoops_getmodulehandler('multiPageScreen', 'formulize');
    $element_handler = xoops_getmodulehandler('elements','formulize');

    // pickup a declared page that we're going back onto...will/might include screen id after a hyphen
    if(isset($_POST['parent_page'])) {
        $parent_page = strstr($_POST['parent_page'], ',') ? explode(',',$_POST['parent_page']) : array($_POST['parent_page']);
        $lastKey = count((array) $parent_page)-1;
        $_POST['formulize_currentPage'] = $parent_page[$lastKey];
    }

    // attach the target screen id to the currentPage value, if we have been directed to a sub from the previous page request
    // currentPage will have been reset to 1 in javascript prior to this submission. Now we need to add the screen ID, based on the subform element id, which we will have from the submission
    if(isset($_POST['goto_subformElementId']) AND $_POST['goto_subformElementId'] AND isset($_POST['formulize_currentPage']) AND $_POST['formulize_currentPage'] == 1) {
        if($gotoSubformElementObject = $element_handler->get($_POST['goto_subformElementId'])) {
            if($subformScreenIdToAppend = get_display_screen_for_subform($gotoSubformElementObject)) {
                $_POST['formulize_currentPage'] .= '-'.$subformScreenIdToAppend;
            }
        }
    }

    $currentPageScreen = 0;
    // reset $_POST['formulize_currentPage'] which is referred to many places to get the official page we're on
    if(isset($_POST['formulize_currentPage']) AND strstr($_POST['formulize_currentPage'],'-')) {
        $cpParts = explode('-',$_POST['formulize_currentPage']);
        $_POST['formulize_currentPage'] = $cpParts[0];
        $currentPageScreen = $cpParts[1];
    }
    // set prevPage, last page that the user was on, not necessarily the previous page numerically
    $prevPage = 1;
    if(isset($_POST['formulize_prevPage']) AND strstr($_POST['formulize_prevPage'],'-')) {
        $cpParts = explode('-',$_POST['formulize_prevPage']);
        $prevPage = $cpParts[0];
        $prevScreen = $cpParts[1];
    } elseif(isset($_POST['formulize_prevPage'])) {
        $prevPage = intval($_POST['formulize_prevPage']);
    }
    if($screen AND isset($prevScreen) AND $screen->getVar('sid') != $prevScreen) {
        $prevPageThisScreen = 1;
    } else {
        $prevPageThisScreen = $prevPage;
    }

	// extract the optional page titles from the $pages array for use in the jump to box
	// NOTE: pageTitles array must start with key 1, not 0.  Page 1 is the first page of the form
	$pageTitles = array();
	if(isset($pages['titles'])) {
		$pageTitles = $pages['titles'];
		unset($pages['titles']);
	}

    // $overrideMulti probably doesn't even need to be set, but for legacy compatibility, we'll keep this in for now
    // removing the entry value is the critical thing, so a new entry is displayed
    $overrideMulti = 0;
    $removeEntryValue = false;

    if(count((array) $pages) == 1 AND $screen) {
        $reloadblank = isset($_POST['originalReloadBlank']) ? $_POST['originalReloadBlank'] : $screen->getVar('reloadblank');
        // figure out the form's properties...
        // if it's more than one entry per user, and we have requested reload blank, then override multi is 0, otherwise 1
        // if it's one entry per user, and we have requested reload blank, then override multi is 1, otherwise 0
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        $formObject = $form_handler->get($screen->getVar('fid'));
        if($formObject->getVar('single')=="off" AND $reloadblank) {
            $removeEntryValue = true;
            $overrideMulti = 0;
        } elseif($formObject->getVar('single')=="off" AND !$reloadblank) {
            $overrideMulti = 1;
        } elseif(($formObject->getVar('single')=="group" OR $formObject->getVar('single')=="user") AND $reloadblank) {
            $overrideMulti = 1;
        } elseif(($formObject->getVar('single')=="group" OR $formObject->getVar('single')=="user") AND !$reloadblank) {
            $overrideMulti = 0;
        } else {
            $overrideMulti = 0;
        }
    }

	if(!$saveAndContinueButtonText AND isset($_POST['formulize_saveAndContinueButtonText'])) { $saveAndContinueButtonText = unserialize($_POST['formulize_saveAndContinueButtonText']); }
	if(!$done_dest AND $_POST['formulize_doneDest']) { $done_dest = $_POST['formulize_doneDest']; } // probably won't ever have these things in post if they're not defined, since the posted values are originally based on what is passed in to this function??
	if(!$button_text AND $_POST['formulize_buttonText']) { $button_text = $_POST['formulize_buttonText']; }

    $button_text = $button_text ? $button_text : _formulize_DMULTI_ALLDONE;

    $settings['formulize_doneDest'] = $done_dest;
    $settings['formulize_buttonText'] = $button_text;

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
	if((!$entry OR $entry == 'new') AND isset($GLOBALS['formulize_newEntryIds'][$fid]) AND !$removeEntryValue) {
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
        $currentPage = (!$elements_only AND isset($_POST['formulize_currentPage'])) ? $_POST['formulize_currentPage'] : 1;
    }
	$thanksPage = count((array) $pages) + 1;

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
	if(is_array($conditions) AND !empty($conditions) AND (!$currentPageScreen OR ($screen AND $currentPageScreen == $screen->getVar('sid')))) {
		$conditionsMet = false;
		while(!$conditionsMet AND $currentPage > 0) {
			if(isset($conditions[$currentPage][0]) AND count((array) $conditions[$currentPage][0])>0) { // conditions on the current page
				if(pageMeetsConditions($conditions, $currentPage, $entry, $fid, $frid) == false) {
					if($prevPageThisScreen <= $currentPage) {
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

    if(!$currentPage) {
        $currentPage = $thanksPage;
    }

	if($currentPage > 1) {
	  $previousPage = $currentPage-1; // previous page numerically
	} else {
	  $previousPage = "none";
	}

	$nextPage = $currentPage+1;

    if(!$done_dest) {
        // check for a dd in get and use that as a screen id
        if(isset($_GET['dd']) AND is_numeric($_GET['dd'])) {
            $done_dest = XOOPS_URL.'/modules/formulize/index.php?sid='.$_GET['dd'];
        } else {
            $done_dest = getCurrentURL();
            // check if the done destination is for this specific form screen that we're rendering, if so, switch done destination to the default list for the form if any
            if($screen AND strstr($done_dest, 'sid='.$screen->getVar('sid'))) {
                $form_handler = xoops_getmodulehandler('forms', 'formulize');
                $formObject = $form_handler->get($screen->getVar('fid'));
                if($defaultListScreenId = $formObject->getVar('defaultlist')) {
                    $done_dest = XOOPS_URL.'/modules/formulize/index.php?sid='.$defaultListScreenId;
                }
            }
        }
    } else {
        // strip out any ve portion of a done destination, so we don't end up forcing the user back to this entry after they're done
        if($vepos = strpos($done_dest,'&ve=')) {
            if(is_numeric(substr($done_dest, $vepos+4))) {
                $done_dest = substr($done_dest, 0, $vepos);
            }
        }
    }
	$done_dest = substr($done_dest,0,4) == "http" ? $done_dest : "http://".$done_dest;

	// display a form if that's what this page is...
	if($currentPage != $thanksPage AND $pages[$currentPage][0] !== "HTML" AND $pages[$currentPage][0] !== "PHP") {

        if($currentPage == 1 AND $pages[1][0] !== "HTML" AND $pages[1][0] !== "PHP" AND !$_POST['goto_sfid']) { // only show intro text on first page if there's actually a form there
            print undoAllHTMLChars($introtext);
        }

		foreach($pages[$currentPage] as $element) {
            $elements_allowed[] = $element;
	    }
		$forminfo['elements'] = $elements_allowed;
		$forminfo['formframe'] = $formframe;
        $settings['formulize_currentPage'] = $currentPage;
		$settings['formulize_prevPage'] = $prevPage;
        $settings['formulize_prevScreen'] = $prevScreen;

        $titleOverride = $elements_only ? 'formElementsOnly' : 'all';

		formulize_benchmark("Before drawing nav.");

        if(!$elements_only) {

            global $formulize_displayingMultipageScreen;
            $formulize_displayingMultipageScreen = $screen ? array('sid'=>$screen->getVar('sid')) : array('sid'=>false);

            $showPageTitles = ($screen AND $screen->getUIOption('showpagetitles')) ? true : false;
            $titleOverride = (isset($pageTitles[$currentPage]) AND $showPageTitles) ? trans($pageTitles[$currentPage]) : "all"; // we can pass in any text value as the titleOverride, and it will have the same effect as "all", but the alternate text will be used as the title for the form

            if(!is_array($saveAndContinueButtonText)) {
                $saveAndContinueButtonText = array();
                $saveAndContinueButtonText['prevButtonText'] = trans(_formulize_DMULTI_PREV);
                $saveAndContinueButtonText['leaveButtonText'] = trans(_formulize_SAVE_AND_LEAVE);
                $saveAndContinueButtonText['saveButtonText'] = trans(_formulize_SAVE);
                $saveAndContinueButtonText['finishButtonText'] =  trans(_formulize_DMULTI_SAVE);
                $saveAndContinueButtonText['nextButtonText'] = trans(_formulize_DMULTI_NEXT);
                $saveAndContinueButtonText['printableViewButtonText'] = trans(_formulize_PRINTVIEW);
            }

            if($currentPage != 1) {
                // previousButtonText used to be valid... backwards compatibility
                $previousButtonText = (is_array($saveAndContinueButtonText) AND isset($saveAndContinueButtonText['previousButtonText'])) ? $saveAndContinueButtonText['previousButtonText'] : '';
                $previousButtonText = (!$previousButtonText AND is_array($saveAndContinueButtonText) AND isset($saveAndContinueButtonText['prevButtonText'])) ? $saveAndContinueButtonText['prevButtonText'] : '';
            } else {
                $previousButtonText = (is_array($saveAndContinueButtonText) AND isset($saveAndContinueButtonText['leaveButtonText'])) ? $saveAndContinueButtonText['leaveButtonText'] : '';
            }
            $saveButtonText = (is_array($saveAndContinueButtonText) AND isset($saveAndContinueButtonText['saveButtonText'])) ? $saveAndContinueButtonText['saveButtonText'] : '';
            if($usersCanSave AND $nextPage==$thanksPage) {
                $nextButtonText = (is_array($saveAndContinueButtonText) AND $saveAndContinueButtonText['finishButtonText']) ? $saveAndContinueButtonText['finishButtonText'] :  '';
            } else {
                $nextButtonText = (is_array($saveAndContinueButtonText) AND $saveAndContinueButtonText['nextButtonText']) ? $saveAndContinueButtonText['nextButtonText'] : '';
            }
            $previousPageButton = generatePrevNextButtonMarkup("prev", $previousButtonText, $usersCanSave, $nextPage, $previousPage, $thanksPage);
            $nextPageButton = generatePrevNextButtonMarkup("next", $nextButtonText, $usersCanSave, $nextPage, $previousPage, $thanksPage);
            $savePageButton = generatePrevNextButtonMarkup("save", $saveButtonText, $usersCanSave, $nextPage, $previousPage, $thanksPage);
            $totalPages = count((array) $pages);
            $skippedPageMessage = $pagesSkipped ? _formulize_DMULTI_SKIP : "";
            $pageSelectionList = pageSelectionList($currentPage, $totalPages, $pageTitles, "below", $conditions, $entry, $fid, $frid); // pageSelector can only show up once on the page, and we draw it with 'below' as the designation, since by default it shows up in the bottom templates. Used to be two versions, above and below, which allowed two copies of this to be functional in the page. Different names were required by the JS, which could be refactored to not need that. But expecting only one per page is valid and simpler for now.

            $pageIndicator = $screen->getUIOption("showpageindicator") ? "<div id='page-indicator'>"._formulize_DMULTI_PAGE." $currentPage "._formulize_DMULTI_OF." $totalPages</div>" : "";
            $pageSelector = $screen->getUIOption("showpageselector") ? "<div id='page-selector'>"._formulize_DMULTI_JUMPTO."&nbsp;&nbsp;$pageSelectionList<div>" : "";

            // setting up the basic templateVars for all templates
            $templateVariables = array(
                // navstyle - 0 is buttons, 1 is tabs, 2 is tabs and buttons, 3 is nothing at all
                'previousPageButton' => (($screen->getVar('navstyle') == 1 OR $screen->getVar('navstyle') == 3) ? "" : $previousPageButton),
                'nextPageButton' => (($screen->getVar('navstyle') == 1 OR $screen->getVar('navstyle') == 3) ? "" : $nextPageButton),
                'savePageButton' => $savePageButton,
                'totalPages' => $totalPages,
                'currentPage' => $currentPage,
                'skippedPageMessage' => $skippedPageMessage,
                'pageSelectionList' => $pageSelectionList,
                'pageTitles' => $pageTitles,
                'entry_id' => $entry,
                'form_id' => $fid,
                'owner' => $owner,
                'saveAndLeaveText' => $saveAndContinueButtonText['leaveButtonText'],
                'saveAndGoBackText' => $saveAndContinueButtonText['prevButtonText'],
                'pageIndicator' => $pageIndicator,
                'pageSelector' => $pageSelector,
                'usersCanSave' => $usersCanSave,
                'showpageindicator' => $screen->getUIOption("showpageindicator"),
                'showpageselector' => $screen->getUIOption("showpageselector"),
                'showTabs' => (($screen->getVar('navstyle') == 1 OR $screen->getVar('navstyle') == 2) ? true : false)
                );

            $templatePageTitles = array();
            unset($templateVariables['pageTitles'][0]);
            foreach($templateVariables['pageTitles'] as $i=>$title) {
                if(pageMeetsConditions($conditions, $i, $entry, $fid, $frid)) {
                    $templatePageTitles[$i] = $title;
                }
            }
            $templateVariables['pageTitles'] = $templatePageTitles;
            $templateVariables['aboveBelow'] = 'above';

            global $formulize_displayingSubform;
            if($formulize_displayingSubform) {
                $templateVariables['saveAndLeave'] = $templateVariables['saveAndGoBackText'] ? trans($templateVariables['saveAndGoBackText']) : trans(_formulize_SAVE_AND_GOBACK);
            } else {
                $templateVariables['saveAndLeave'] = $templateVariables['saveAndLeaveText'] ? trans($templateVariables['saveAndLeaveText']) : trans(_formulize_SAVE_AND_LEAVE);
            }

            $printableViewButonText = $saveAndContinueButtonText['printableViewButtonText'] ? $saveAndContinueButtonText['printableViewButtonText'] : "{NOBUTTON}";
            $buttonArray = array(0=>"{NOBUTTON}", 1=>"{NOBUTTON}", 2=>"{NOBUTTON}", 3=>$printableViewButonText);
            $GLOBALS['formulize_displayingMultipageScreen']['templateVariables'] = $templateVariables;
        }

        if(count((array) $elements_allowed)==0) {
            print "Error: there are no form elements specified for page number $currentPage. Please contact the webmaster.";
        } else {
            displayForm($forminfo, $entry, $mainform, "", $buttonArray, $settings, $titleOverride, $overrideValue, $overrideMulti, "", 0, 0, $printall, $screen); // nmc 2007.03.24 - added empty params & '$printall'
        }

    }

	if(!$elements_only AND !isset($GLOBALS['formulize_inlineSubformFrid']) AND !strstr(getCurrentURL(), 'subformdisplay-elementsonly.php')) {
		include_once XOOPS_ROOT_PATH.'/modules/formulize/include/multipage_boilerplate.php';
	}

    formulize_benchmark("End of displayFormPages.");
} // end of the function!

// THIS FUNCTION GENERATES THE MARKUP FOR THE PREVIOUS AND NEXT BUTTONS
function generatePrevNextButtonMarkup($buttonType, $buttonText, $usersCanSave, $nextPage, $previousPage, $thanksPage) {

    if(!$buttonText OR ($buttonType == 'save' AND !$usersCanSave)) { return ''; }

    $buttonText = trans($buttonText);
    $buttonMarkup = "";

    switch($buttonType) {
        case 'next':
            $buttonJavascriptAndExtraCode = "onclick=\"javascript:submitForm($nextPage, ".(intval($previousPage)+1).");return false;\"";
            break;
        case 'prev':
            $buttonJavascriptAndExtraCode = "onclick=\"javascript:submitForm($previousPage, ".(intval($previousPage)+1).");return false;\"";
            break;
        case 'save':
            $buttonJavascriptAndExtraCode = "onclick=\"javascript:submitForm(".(intval($previousPage)+1).", ".(intval($previousPage)+1).");return false;\"";
    }

    if($buttonType == "next" OR $buttonType == "save") {
        $buttonMarkup = "<input type=button name='$buttonType' id='$buttonType' class='formulize-form-submit-button' value='" . $buttonText . "' $buttonJavascriptAndExtraCode>\n";
    } elseif($buttonType == "prev") {
        if($previousPage == "none") {
            $buttonJavascriptAndExtraCode = "onclick=\"javascript:submitForm($thanksPage, 1);return false;\"";
        }
        $buttonMarkup = "<input type=button name='prev' id='prev' class='formulize-form-submit-button' value='" . $buttonText . "' $buttonJavascriptAndExtraCode>\n";
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
