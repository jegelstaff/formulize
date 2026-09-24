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

function displayFormPages($formframe, $entry_id, $mainform, $pages, $conditions="", $introtext="", $thankstext="", $done_dest="", $thankYouLinkText="", $settings=array(), $overrideValue="", $printall=0, $screen=null, $saveAndContinueButtonText=null, $elements_only = false) { // nmc 2007.03.24 - added 'printall'

    formulize_benchmark("Start of displayFormPages.");

    // Record whether a caller already forced elements disabled, so the per-page "disable all elements" handling below
    // never clobbers someone else's state and never leaks the flag out of this function to other forms/widgets/blocks
    // that may be rendered on the same site page. See the set/restore around the displayForm() call, and the guaranteed
    // cleanup at the end of this function.
    $formulize_forceDisabledWasSetOnEntry = isset($GLOBALS['formulize_forceElementsDisabled']);

    global $xoopsUser;
    if(!isset($_POST['parent_entry']) AND !isset($_POST['parent_form']) AND !isset($_POST['parent_page']) AND !isset($_POST['parent_subformElementId'])
       AND isset($_POST['go_back_form']) AND $_POST['go_back_form'] AND isset($_POST['go_back_entry']) AND $_POST['go_back_entry'] AND (!isset($_POST['ventry']) OR !$_POST['ventry'])) {
        $entry_id = setupParentFormValuesInPostAndReturnEntryId();
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
		$prevScreen = null;
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
		foreach($pages['titles'] as $key=>$value) {
			$pages['titles'][$key] = formulize_handleRandomAndDateText($value);
		}
		$pageTitles = $pages['titles'];
		unset($pages['titles']);
	}

	// extract the optional per-page "disable all elements" flags (parallel to titles, keyed from 1 by the same
	// compiled page number as $pages), stashed into $pages['disabled'] by the multipage screen's render() method
	$disabledPages = array();
	if(isset($pages['disabled'])) {
		$disabledPages = is_array($pages['disabled']) ? $pages['disabled'] : array();
		unset($pages['disabled']);
	}

    // $overrideMulti probably doesn't even need to be set, but for legacy compatibility, we'll keep this in for now
    // removing the entry value is the critical thing, so a new entry is displayed
    $overrideMulti = 0;
    $removeEntryValue = false;
		global $xoopsUser;
		$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);

    if(count((array) $pages) == 1 AND $screen) {
			$reloadblank = isset($_POST['originalReloadBlank']) ? $_POST['originalReloadBlank'] : $screen->getVar('reloadblank');
			// figure out the form's properties...
			// if it's more than one entry per user, and we have requested reload blank, then override multi is 0, otherwise 1
			// if it's one entry per user, and we have requested reload blank, then override multi is 1, otherwise 0
			$form_handler = xoops_getmodulehandler('forms', 'formulize');
			$formObject = $form_handler->get($screen->getVar('fid'));
			global $xoopsUser;
			$singleEntryMetadata = getSingle($formObject->getVar('fid'), ($xoopsUser ? $xoopsUser->getVar('uid') : 0)); // returns array with flag and entry as keys
			if(!$singleEntryMetadata['flag'] AND $reloadblank) {
				$removeEntryValue = true;
				$overrideMulti = 0;
			} elseif(!$singleEntryMetadata['flag'] AND !$reloadblank) {
				$overrideMulti = 1;
			} elseif($singleEntryMetadata['flag'] AND $reloadblank) {
				$overrideMulti = 1;
			} elseif($singleEntryMetadata['flag'] AND !$reloadblank) {
				$overrideMulti = 0;
			} else {
				$overrideMulti = 0;
			}
    }

	if(!$done_dest AND isset($_POST['formulize_doneDest']) AND $_POST['formulize_doneDest']) { $done_dest = $_POST['formulize_doneDest']; } // probably won't ever have these things in post if they're not defined, since the posted values are originally based on what is passed in to this function??
	if(!$thankYouLinkText AND isset($_POST['formulize_buttonText']) AND $_POST['formulize_buttonText']) { $thankYouLinkText = $_POST['formulize_buttonText']; }

    $thankYouLinkText = $thankYouLinkText ? $thankYouLinkText : _formulize_DMULTI_ALLDONE;

    $settings['formulize_doneDest'] = $done_dest;
    $settings['formulize_buttonText'] = $thankYouLinkText; // formulize_buttonText is some ancient name for the key in POST, and should be re-examined and refactored (or removed? does anything depend on this??)

	list($fid, $frid) = getFormFramework($formframe, $mainform);

	$thankstext = $thankstext ? $thankstext : _formulize_DMULTI_THANKS;
	$introtext = $introtext ? $introtext : "";
	$mid = getFormulizeModId();
	$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
	$gperm_handler =& xoops_gethandler('groupperm');
	$member_handler =& xoops_gethandler('member');
	$single_result = getSingle($fid, $uid, $groups, $member_handler, $gperm_handler, $mid);
	$view_globalscope = $gperm_handler->checkRight("view_globalscope", $fid, $groups, $mid);
	$view_groupscope = $gperm_handler->checkRight("view_groupscope", $fid, $groups, $mid);

	// if this function was called without an entry specified, then assume the identity of the entry we're editing (unless this is a new save, in which case no entry has been made yet)
	// no handling of cookies here, so anonymous multi-page surveys will not benefit from that feature
	// this emphasizes how we need to standardize a lot of these interfaces with a real class system
	if(!$entry_id AND $_POST['entry'.$fid]) {
		$entry_id = intval($_POST['entry'.$fid]);
  } elseif(!$entry_id AND $_POST['form_'.$fid.'_rendered_entry']) {
    $entry_id = intval($_POST['form_'.$fid.'_rendered_entry'][0]);
	} elseif(!$entry_id) { // or check getSingle to see what the real entry is
		$entry_id = $single_result['flag'] ? $single_result['entry'] : 0;
	}

	// formulize_newEntryIds is set when saving data
	if((!$entry_id OR $entry_id == 'new' OR $entry_id == 'proxy') AND isset($GLOBALS['formulize_newEntryIds'][$fid]) AND !$removeEntryValue) {
		$entry_id = $GLOBALS['formulize_newEntryIds'][$fid][0];
	} elseif(!$entry_id) {
    $entry_id = 'new';
	}

	$owner = getEntryOwner($entry_id, $fid);

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

  $usersCanSave = formulizePermHandler::user_can_edit_entry($fid, $uid, $entry_id);

	if($pages[$prevPage][0] !== "HTML" AND $pages[$prevPage][0] !== "PHP") { // remember prevPage is the last page the user was on, not the previous page numerically

		if(isset($_POST['form_submitted']) AND $usersCanSave) { // if something was maybe saved, we might need to assume the identify of the entry just saved, so see if we can figure that out

			include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

			$entries[$fid][0] = $entry_id;

			if($frid) {
				$linkResults = checkForLinks($frid, array(0=>$fid), $fid, $entries);
				unset($entries);
				$entries = $linkResults['entries'];
			} else {
                $entries = $GLOBALS['formulize_allSubmittedEntryIds']; // set in readelements.php
			}

			// if there has been no specific entry specified yet, then assume the identity of the entry that was just saved -- assumption is it will be a new save
			// from this point forward in time, this is the only entry that should be involved, since the 'entry'.$fid condition above will put this value into $entry_id even if this function was called with a blank entry value
			if(!$entry_id) {
				$entry_id = $entries[$fid][0];
			}

            unset($_POST['form_submitted']);
		}
	}

    // there are several points above where $entry_id is set, and now that we have a final value, store in ventry
    if ($entry_id > 0) {
        $settings['ventry'] = $entry_id;
    }

	// check if user does not have view private elements permission, and if all elements on the page are flagged as private, in which case, skip the page
	$userCanViewPrivateElements = $gperm_handler->checkRight("view_private_elements", $fid, $groups, $mid)
		|| formulizePermHandler::isUserOwnAccountEntry($fid, $uid, $entry_id);

	// check to see if there are conditions on this page, and if so are they met
	// if the conditions are not met, move on to the next page and repeat the condition check
	// conditions only checked once there is an entry!
 	$pagesSkipped = false;
	if(is_array($conditions) AND !empty($conditions) AND (!$currentPageScreen OR ($screen AND $currentPageScreen == $screen->getVar('sid')))) {
		$conditionsMet = false;
		while(!$conditionsMet AND $currentPage > 0) {
			$conditionsMet = ($userCanViewPrivateElements OR !isPageAllPrivateElements($pages[$currentPage], $fid)) ? true : false;
			if($conditionsMet AND isset($conditions[$currentPage][0]) AND count((array) $conditions[$currentPage][0])>0) { // conditions on the current page
				$conditionsMet = pageMeetsConditions($conditions, $currentPage, $entry_id, $fid, $frid);
			}
			if(!$conditionsMet) {
				if($prevPageThisScreen <= $currentPage) {
					$currentPage++;
				} else {
					$currentPage--;
				}
				$pagesSkipped = true;
			}
		}
	}
	$currentPage = !$currentPage ? $thanksPage : $currentPage;
	$previousPage = $currentPage > 1 ? $currentPage-1 : "none";
	$nextPage = $currentPage+1;

	// is the page we're about to render flagged to have all its elements disabled? (confirmation-page setting)
	$thisPageDisabled = !empty($disabledPages[$currentPage]);

	// done destination used in the multipage boilerplate included below
	$originalDoneDest = $done_dest;
	if(!$done_dest) {
			// check for a dd in get and use that as a screen id
			if(isset($_GET['dd']) AND is_numeric($_GET['dd'])) {
					$done_dest = XOOPS_URL.'/modules/formulize/index.php?sid='.$_GET['dd'];
			} else {
					$done_dest = determineDoneDestinationFromURL($screen);
			}
	}
	$done_dest = stripEntryFromDoneDestination($done_dest);
	$done_dest = substr($done_dest,0,4) == "http" ? $done_dest : "http://".$done_dest;

	// setup elements for the page...
	if($currentPage != $thanksPage) {
		if($pages[$currentPage][0] !== "HTML" AND $pages[$currentPage][0] !== "PHP") {

			if($currentPage == 1 AND $pages[1][0] !== "HTML" AND $pages[1][0] !== "PHP" AND (!isset($_POST['goto_sfid']) OR !$_POST['goto_sfid'])) { // only show intro text on first page if there's actually a form there
					print undoAllHTMLChars($introtext);
			}

			$forminfo['elements'] = $pages[$currentPage];
			if(!$usersCanSave) {
				// check perm for add perm on forms of any subform elements
				foreach($forminfo['elements'] as $elementId) {
					if($candidateSubformElementObject = $element_handler->get($elementId)) {
						if($candidateSubformElementObject->getVar('ele_type') == 'subformFullForm'
							OR $candidateSubformElementObject->getVar('ele_type') == 'subformEditableRow'
							OR $candidateSubformElementObject->getVar('ele_type') == 'subformListings') {
								$candidateSubformElementEleValue = $candidateSubformElementObject->getVar('ele_value');
								// could be made smarter if we went and figured out exactly which entries are connected through an active relationship, etc
								// but all that metadata is not available here, so we simply go off of whether people can add entries
								if($usersCanSave = formulizePermHandler::user_can_edit_entry($candidateSubformElementEleValue[0], $uid, 'new')) {
									break;
								}
						}
					}
				}
			}

		} else {
			$customPageContents = "";
			$thisCustomCode = $pages[$currentPage][1];
			ob_start();
			// PHP
			if($pages[$currentPage][0] === "PHP") {
					eval(removeOpeningPHPTag($thisCustomCode));
			// HTML
			} else {
					print undoAllHTMLChars($thisCustomCode);
			}
			$customPageContents = ob_get_clean();
			$forminfo['elements'] = array($customPageContents);
		}
	}

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

			$saveAndContinueButtonText = formulizeMultipageButtonText($saveAndContinueButtonText);

			if(!$usersCanSave AND $saveAndContinueButtonText['leaveButtonText'] == trans(_formulize_SAVE_AND_LEAVE)) {
				$saveAndContinueButtonText['leaveButtonText'] = trans(_formulize_DONE);
			}

			// Which buttons this page of this screen has is decided in one place, by
			// formulize_multipageButtonSet(), and the elements-only rendering below
			// publishes the answer from that very same call so the drawer's footer and
			// this bar can never disagree about the set (PR #127 review). All this does
			// with it is turn each slot into markup.
			$multipageButtonSet = formulize_multipageButtonSet($saveAndContinueButtonText, $usersCanSave, $currentPage,
				pageIsThanksPageOrEquivalent($nextPage, $currentPage, $thanksPage, $pages, $conditions, $entry_id, $fid, $frid));
			$previousButtonText = isset($multipageButtonSet['prev']) ? $multipageButtonSet['prev'] : '';
			$saveButtonText = isset($multipageButtonSet['save']) ? $multipageButtonSet['save'] : '';
			$nextButtonText = isset($multipageButtonSet['next']) ? $multipageButtonSet['next'] : '';
			$closeButtonText = isset($multipageButtonSet['close']) ? $multipageButtonSet['close'] : '';
			$previousPageButton = generatePrevNextButtonMarkup("prev", $previousButtonText, $usersCanSave, $nextPage, $previousPage, $thanksPage);
			$nextPageButton = generatePrevNextButtonMarkup("next", $nextButtonText, $usersCanSave, $nextPage, $previousPage, $thanksPage);
			$savePageButton = generatePrevNextButtonMarkup("save", $saveButtonText, $usersCanSave, $nextPage, $previousPage, $thanksPage);
			$closePageButton = generatePrevNextButtonMarkup("close", $closeButtonText, $usersCanSave, $nextPage, $previousPage, $thanksPage);
			$totalPages = count((array) $pages);
			$skippedPageMessage = $pagesSkipped ? _formulize_DMULTI_SKIP : "";
			$pageSelectionList = pageSelectionList($currentPage, $totalPages, $pageTitles, "below", $conditions, $entry_id, $fid, $frid); // pageSelector can only show up once on the page, and we draw it with 'below' as the designation, since by default it shows up in the bottom templates. Used to be two versions, above and below, which allowed two copies of this to be functional in the page. Different names were required by the JS, which could be refactored to not need that. But expecting only one per page is valid and simpler for now.

			$pageIndicator = $screen->getUIOption("showpageindicator") ? "<div id='page-indicator'>"._formulize_DMULTI_PAGE." $currentPage "._formulize_DMULTI_OF." $totalPages</div>" : "";
			// NB: the closing tag here was `<div>` rather than `</div>`, leaving
			// #page-selector open. That was invisible while the selector was the last
			// thing in the action bar (it only swallowed the trailing script/noscript),
			// but any markup emitted after it ended up nested inside it. Lyris now emits
			// the page meta before the buttons (issue #121 item 4), which turned the four
			// form buttons into children of #page-selector, so the tag is closed properly.
			$pageSelector = $screen->getUIOption("showpageselector") ? "<div id='page-selector'>"._formulize_DMULTI_JUMPTO."&nbsp;&nbsp;$pageSelectionList</div>" : "";

			// setting up the basic templateVars for all templates
			$templateVariables = array(
					// navstyle - 0 is buttons, 1 is tabs, 2 is tabs and buttons, 3 is nothing at all
					'previousPageButton' => (($screen->getVar('navstyle') == 1 OR $screen->getVar('navstyle') == 3) ? "" : $previousPageButton),
					'nextPageButton' => (($screen->getVar('navstyle') == 1 OR $screen->getVar('navstyle') == 3) ? "" : $nextPageButton),
					'savePageButton' => $savePageButton,
					'closePageButton' => $closePageButton,
					'totalPages' => $totalPages,
					'currentPage' => $currentPage,
					'skippedPageMessage' => $skippedPageMessage,
					'pageSelectionList' => $pageSelectionList,
					'pageTitles' => $pageTitles,
					'entry_id' => $entry_id,
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

			// the tab strip shows the reachable pages, decided by the same function the
			// drawer's tab strip uses, so the two surfaces cannot drift apart
			$templateVariables['pageTitles'] = formulize_visibleMultipagePages($pages, $templateVariables['pageTitles'], $conditions, $entry_id, $fid, $frid, $userCanViewPrivateElements);
			$templateVariables['aboveBelow'] = 'above';

			$templateVariables['saveAndLeave'] = formulizeMultipageSaveAndLeaveText($saveAndContinueButtonText, $originalDoneDest, $single_result, $view_globalscope, $view_groupscope, $done_dest);
			$printableViewButonText = $saveAndContinueButtonText['printableViewButtonText'] ? $saveAndContinueButtonText['printableViewButtonText'] : "{NOBUTTON}";
			$buttonArray = array(0=>"{NOBUTTON}", 1=>"{NOBUTTON}", 2=>"{NOBUTTON}", 3=>$printableViewButonText);
			$GLOBALS['formulize_displayingMultipageScreen']['templateVariables'] = $templateVariables;
	}

	// In elements-only mode (e.g. the list drawer) all the navigation chrome above is
	// skipped, but the client still needs the paging state to build its own controls.
	// Emit it as JSON here, where conditional page-skipping has already been resolved,
	// so the client doesn't have to re-derive which page is next/last.
	if($elements_only) {
		$nextIsThanks = pageIsThanksPageOrEquivalent($nextPage, $currentPage, $thanksPage, $pages, $conditions, $entry_id, $fid, $frid);
		// Resolve the navigation button labels with the same precedence the full-page
		// rendering uses (the screen's configured button text, falling back to the
		// standard language constants) so the client-side controls read identically.
		// An empty label means "no button", exactly as generatePrevNextButtonMarkup treats it.
		$navButtonText = formulizeMultipageButtonText($saveAndContinueButtonText);
		// Full screen turns the leave button into "Done" for a user who cannot save
		// (see the !$elements_only branch above). Applied here too, so the drawer reads
		// the same word on the same button.
		if(!$usersCanSave AND isset($navButtonText['leaveButtonText']) AND $navButtonText['leaveButtonText'] == trans(_formulize_SAVE_AND_LEAVE)) {
			$navButtonText['leaveButtonText'] = trans(_formulize_DONE);
		}
		// The page's button set, from the same function that decides the full page bar's
		// (see the !$elements_only branch above). Published in action bar order as
		// {slot, text} pairs -- slot being the `name` generatePrevNextButtonMarkup puts
		// on the full screen button -- so the drawer renders full screen's set rather
		// than assembling its own from the individual labels. navstyle is NOT applied
		// here: it is published separately as showNavButtons, and the drawer's one
		// documented deviation from it (navstyle 3 would strand the user on page one in
		// a drawer) is applied client side.
		$navButtonSet = formulize_multipageButtonSet($navButtonText, $usersCanSave, $currentPage, $nextIsThanks);
		$navButtons = array();
		foreach($navButtonSet as $navSlot=>$navSlotText) {
			$navButtons[] = array('slot'=>$navSlot, 'text'=>trans($navSlotText));
		}
		$navPreviousButtonText = isset($navButtonSet['prev']) ? trans($navButtonSet['prev']) : '';
		$navNextButtonText = isset($navButtonSet['next']) ? trans($navButtonSet['next']) : '';
		// Which navigation affordances the screen is configured to offer. navstyle is a
		// single setting with four states: 0 buttons, 1 tabs, 2 tabs and buttons, 3 nothing
		// at all. Full screen reads it at the top of this function to decide what the
		// templates draw; publishing it here lets the drawer honour the same configuration
		// instead of inventing its own navigation.
		$navStyle = is_object($screen) ? intval($screen->getVar('navstyle')) : 0;
		$navVisiblePages = formulize_visibleMultipagePages($pages, $pageTitles, $conditions, $entry_id, $fid, $frid, $userCanViewPrivateElements);
		$navPages = array();
		foreach($navVisiblePages as $navPageNumber=>$navPageTitle) {
			$navPages[] = array('page'=>intval($navPageNumber), 'title'=>trans($navPageTitle));
		}
		$navMeta = array(
			'showTabs'        => ($navStyle == 1 OR $navStyle == 2),
			'showNavButtons'  => ($navStyle == 0 OR $navStyle == 2),
			'pages'           => $navPages,
			'showPageIndicator' => (bool) (is_object($screen) ? $screen->getUIOption("showpageindicator") : false),
			'showPageSelector'  => (bool) (is_object($screen) ? $screen->getUIOption("showpageselector") : false),
			'currentPage'  => intval($currentPage),
			'totalPages'   => count((array) $pages),
			'previousPage' => ($previousPage === "none" ? null : intval($previousPage)),
			'nextPage'     => intval($nextPage),
			'isThanksPage' => ($currentPage == $thanksPage),
			'nextIsThanks' => $nextIsThanks,
			'usersCanSave' => (bool) $usersCanSave,
			'screenId'     => (is_object($screen) ? intval($screen->getVar('sid')) : 0),
			'entryId'      => (is_numeric($entry_id) ? intval($entry_id) : 0),
			'pageTitle'    => (isset($pageTitles[$currentPage]) ? trans($pageTitles[$currentPage]) : ''),
			'buttons'            => $navButtons,
			'previousButtonText' => $navPreviousButtonText,
			'nextButtonText'     => $navNextButtonText,
			'pageWord'           => _formulize_DMULTI_PAGE,
			'ofWord'             => _formulize_DMULTI_OF,
		);
		print "\n<script type=\"application/json\" class=\"formulize-multipage-nav\">".json_encode($navMeta)."</script>\n";

		// The form-level buttons the full page rendering shows alongside the paging
		// controls: the screen's save and close buttons (savePageButton/closePageButton,
		// on the same terms generatePrevNextButtonMarkup applies to them) and the
		// printable view button, which the full page gets from displayForm's button tray
		// and which is resolved here through the same shared function that tray uses.
		// Recorded rather than printed so the endpoint can publish the entry belonging to
		// the form it was actually asked to render. Recording it before displayForm runs
		// is what makes this the version the host sees for a multipage screen.
		$multipageSaveButtonText = (isset($navButtonText['saveButtonText']) AND $navButtonText['saveButtonText'] AND $usersCanSave) ? trans($navButtonText['saveButtonText']) : null;
		$multipageCloseButtonText = (isset($navButtonText['closeButtonText']) AND $navButtonText['closeButtonText']) ? trans($navButtonText['closeButtonText']) : null;
		$multipagePrintableViewText = (isset($navButtonText['printableViewButtonText']) AND $navButtonText['printableViewButtonText']) ? $navButtonText['printableViewButtonText'] : "{NOBUTTON}";
		$multipagePrintableViewButtons = formulize_resolveFormButtons(
			array(0=>"{NOBUTTON}", 1=>"{NOBUTTON}", 2=>"{NOBUTTON}", 3=>$multipagePrintableViewText),
			array(), $fid, $uid, $entry_id, false, $printall, '');
		$multipageButtonMeta = array(
			'printableView' => isset($multipagePrintableViewButtons['printableview']) ? trans($multipagePrintableViewButtons['printableview']) : null,
			'save'          => $multipageSaveButtonText,
			// Full screen, a tabbed multipage screen offers "save and leave" as the leading
			// tab of its tab strip. The drawer's strip carries pages only, so the control
			// becomes a footer button here - resolved by the same function that names the
			// full screen tab. When the screen is not tabbed its previous/finish control
			// plays that role instead, and the host already has it from the paging
			// metadata above, so there is nothing extra to offer.
			'saveAndLeave'  => ($navStyle == 1 OR $navStyle == 2) ? (formulizeMultipageSaveAndLeaveText($saveAndContinueButtonText, $originalDoneDest, $single_result, $view_globalscope, $view_groupscope, $done_dest) ?: null) : null,
			'done'          => $multipageCloseButtonText,
			'printAction'   => null,
			'printFields'   => null,
		);
		if($multipageButtonMeta['printableView']) {
			$multipageButtonMeta['printAction'] = XOOPS_URL . "/modules/formulize/printview.php";
			$multipageButtonMeta['printFields'] = formulize_printViewFields(array(), array(0=>$fid), $formframe, $mainform, $entry_id, $forminfo['elements'], $screen, $settings);
		}
		formulize_registerElementsOnlyButtonMeta(formulize_elementsOnlyButtonMetaKey($screen, $fid), $multipageButtonMeta);
	}

	writeToFormulizeLog(array(
		'formulize_event'=>'rendering-form-screen-page',
		'user_id'=>($xoopsUser ? $xoopsUser->getVar('uid') : 0),
		'form_id'=>$fid,
		'screen_id'=>(is_object($screen) ? $screen->getVar('sid') : 0),
		'entry_id'=>$entry_id,
		'form_screen_page_number'=>$currentPage
	));

	// Embedded, ask the page hosting this screen to come back to the top of the frame, since the
	// reader is being shown a different page of the form and whatever the host page is looking at is
	// now the middle of something else. Staying on the same page asks for nothing, so an ordinary
	// save leaves the reader exactly where they were.
	//
	// Emitted here rather than with the form's own javascript because the thanks page draws no form
	// at all - the displayForm() below is skipped for it - and arriving at the thanks page is the
	// most common moment this is wanted.
	if(!$elements_only AND intval($currentPage) != intval($prevPage)) {
		print formulize_embedScrollToTopScript();
	}

	// display the form if applicable...
	if($currentPage != $thanksPage) {
		if(count((array) $forminfo['elements'])==0) {
			print "Error: there are no form elements specified for page number $currentPage. Please contact the webmaster.";
		} else {
			// on a page flagged "disable all elements", force every element on this page to render read-only, tightly
			// scoped to just this displayForm() call so the nav/boilerplate rendered afterward is unaffected
			if($thisPageDisabled) { $GLOBALS['formulize_forceElementsDisabled'] = true; }
			displayForm($forminfo, $entry_id, $mainform, "", $buttonArray, $settings, $titleOverride, $overrideValue, $overrideMulti, "", 0, $printall, $screen); // nmc 2007.03.24 - added empty params & '$printall'
			if($thisPageDisabled AND !$formulize_forceDisabledWasSetOnEntry) { unset($GLOBALS['formulize_forceElementsDisabled']); }
		}
	}

	// put in boilerplate code etc, and handle thanks page if applicable...
	if(!$elements_only AND !isset($GLOBALS['formulize_inlineSubformFrid'])) {
		include_once XOOPS_ROOT_PATH.'/modules/formulize/include/multipage_boilerplate.php';
	}

	// guaranteed cleanup: never let the "disable all elements" flag leak out of this function to anything else on the
	// page. A no-op in the normal path (already restored right after displayForm above), but protects against any path
	// within this function that set it. Only unset if this function did not inherit it from a caller.
	if(!$formulize_forceDisabledWasSetOnEntry) { unset($GLOBALS['formulize_forceElementsDisabled']); }

    formulize_benchmark("End of displayFormPages.");
} // end of the function!

/**
 * Determine if the specified page, or the next page that passes conditions for the entry, is the thanks page
 *
 * @param int $pageNumber - the page number we are checking
 * @param int $activePageNumber - the page number that the user is currently on, used to determine if conditions on the pageNumber page could be met by values to be saved by the user on the active page
 * @param int $thanksPageNumber - the page number of the thanks page
 * @param array $pages - the elements for each page of the form
 * @param array $conditions - the conditions for the pages, used to determine if we need to skip any pages
 * @param int $entry_id - the entry id, used to determine if conditions are met
 * @param int $fid - the form id, used to determine if conditions are met
 * @param int $frid - the form framework id, used to determine if conditions are met
 * @return bool - true if the specified page or the next page that meets conditions is the thanks page, false otherwise
 */
function pageIsThanksPageOrEquivalent($pageNumber, $activePageNumber, $thanksPageNumber, $pages, $conditions, $entry_id, $fid, $frid) {
	while($pageNumber < $thanksPageNumber) {
		if(isset($conditions[$pageNumber][0]) AND count((array) $conditions[$pageNumber][0])>0) { // conditions on the current page
			if(pageMeetsConditions($conditions, $pageNumber, $entry_id, $fid, $frid) == false) {
				// page didn't meet the conditions
				// check if it could possibly still meet the conditions
				// because one of the conditions is based on a derived value
				// or is based on an element on the active page
				// DOES NOT TAKE INTO ACCOUNT VALUES SET IN ON BEFORE SAVE OR ON AFTER SAVE!
				foreach($conditions[$pageNumber][0] as $elementIdentifier) {
					if($elementObject = _getElementObject($elementIdentifier)
						AND (
							$elementObject->getVar('ele_type') == 'derived'
							OR (is_array($pages[$activePageNumber]) AND in_array($elementObject->getVar('ele_id'), $pages[$activePageNumber]))
						)) {
						break 2; // do not increment and check next page, let's go with this one as the potential next page
					}
				}
				// this page will not be available to the user, so let's carry on to the next page
				$pageNumber++;
				continue;
			}
		}
		// the page has no conditions
		// or the conditions are met
		// or the conditions are not met but could still be met due to a derived value being referenced in conditions, or an element in the active page being referenced in conditions
		break;
	}
	// whatever page we've landed on, check if it's the thanks page
	return $pageNumber == $thanksPageNumber ? true : false;
}

// Resolve the multipage screen's button text settings, falling back to the standard
// language constants when the screen has no configured button text at all.
// Note the all-or-nothing fallback is deliberate and matches the historical behaviour:
// once a screen has a button text array, an empty value in it means "no button".
function formulizeMultipageButtonText($saveAndContinueButtonText) {
    if(is_array($saveAndContinueButtonText)) {
        return $saveAndContinueButtonText;
    }
    return array(
        'prevButtonText' => trans(_formulize_DMULTI_PREV),
        'leaveButtonText' => trans(_formulize_SAVE_AND_LEAVE),
        'saveButtonText' => trans(_formulize_SAVE),
        'finishButtonText' => trans(_formulize_DMULTI_SAVE),
        'nextButtonText' => trans(_formulize_DMULTI_NEXT),
        'printableViewButtonText' => trans(_formulize_PRINTVIEW),
        'closeButtonText' => trans(_formulize_DONE),
    );
}

// The text for the "previous" control on a multipage screen. On the first page the
// control is the leave button instead of a page-back button.
/**
 * The label for a multipage screen's "save and leave" control, or "" when the user has
 * nowhere to go. Full screen this is the leading tab of the tab strip; in the drawer it is
 * a footer button, because the drawer deliberately gives the strip no non-page tabs. Both
 * resolve it here so the two surfaces offer the same control under the same name.
 *
 * Inside a subform it becomes "save and go back", since leaving a sub entry returns to its
 * parent. Otherwise it appears when there is an originally specified done destination, or
 * the user can see a list of entries - unless the screen has no leave button and the done
 * destination would land back on exactly the same place. (Blank done destinations resolve
 * to the applicable list screen in determineDoneDestinationFromURL, which covers arbitrary
 * pages such as edituser.php where the current URL is not resolvable to a list screen.)
 */
function formulizeMultipageSaveAndLeaveText($saveAndContinueButtonText, $originalDoneDest, $single_result, $view_globalscope, $view_groupscope, $done_dest) {
	global $xoopsUser, $formulize_displayingSubform;
	$leaveButtonText = (is_array($saveAndContinueButtonText) AND isset($saveAndContinueButtonText['leaveButtonText'])) ? $saveAndContinueButtonText['leaveButtonText'] : '';
	$prevButtonText = (is_array($saveAndContinueButtonText) AND isset($saveAndContinueButtonText['prevButtonText'])) ? $saveAndContinueButtonText['prevButtonText'] : '';
	// A genuine subform render carries the parent it has to go back to, as an array of
	// originalFid/originalEntry. The elements-only endpoint also sets this flag to a bare
	// true, but only to switch on the conditional-element Javascript, so testing for the
	// array is what distinguishes actually being in a sub entry from that reuse.
	if(is_array($formulize_displayingSubform)) {
		return $prevButtonText ? trans($prevButtonText) : trans(_formulize_SAVE_AND_GOBACK);
	}
	if(($originalDoneDest
		OR ($single_result['flag'] == 0 AND $xoopsUser)
		OR $view_globalscope
		OR ($view_groupscope AND $single_result['flag'] != "group")
		)
		AND ($leaveButtonText !== '' OR $done_dest !== getCurrentUrl())) {
		return $leaveButtonText ? trans($leaveButtonText) : trans(_formulize_SAVE_AND_LEAVE);
	}
	return "";
}

function formulizeMultipagePreviousButtonText($buttonText, $currentPage) {
    if(!is_array($buttonText)) { return ''; }
    if($currentPage == 1) {
        return isset($buttonText['leaveButtonText']) ? $buttonText['leaveButtonText'] : '';
    }
    // previousButtonText used to be valid... backwards compatibility.
    // Kept verbatim from the original inline logic so the full page rendering is unchanged.
    $previousButtonText = isset($buttonText['previousButtonText']) ? $buttonText['previousButtonText'] : '';
    $previousButtonText = (!$previousButtonText AND isset($buttonText['prevButtonText'])) ? $buttonText['prevButtonText'] : '';
    return $previousButtonText;
}

// The text for the "next" control on a multipage screen. It becomes the finish button
// when the page after this one is the thanks page (or resolves to it).
function formulizeMultipageNextButtonText($buttonText, $usersCanSave, $nextIsThanksPage) {
    if(!is_array($buttonText)) { return ''; }
    $key = ($usersCanSave AND $nextIsThanksPage) ? 'finishButtonText' : 'nextButtonText';
    return (isset($buttonText[$key]) AND $buttonText[$key]) ? $buttonText[$key] : '';
}

/**
 * Which action bar buttons a multipage screen offers on the page being rendered, and
 * what each one is called.
 *
 * This is the single answer to "what buttons does this page of this screen have". The
 * full page rendering builds its `#multipage-controls` bar out of it, and the
 * elements-only rendering publishes it to the client so the right drawer's footer can
 * draw the same set instead of assembling an approximation of it from separate pieces
 * of metadata (which is what left the drawer showing both a previous-page control and
 * a save-and-leave control past page one, where full screen shows only the first --
 * PR #127 review).
 *
 * The slot names are the `name` attributes generatePrevNextButtonMarkup puts on the
 * markup, and the order is the order the multiPage bottomtemplate emits them in:
 *
 *   prev  - the screen's leave button on page one (so page one reads "Save and Close"
 *           and the control saves and leaves), the previous-page button after that
 *   save  - save in place; suppressed outright when the user cannot save
 *   close - leave without saving
 *   next  - the next-page button, becoming the finish button on the last page
 *
 * A slot with no text is not a button, exactly as generatePrevNextButtonMarkup treats
 * it, and is left out of the returned set.
 *
 * @param array $buttonText The screen's resolved button text, i.e. the return of
 *                          formulizeMultipageButtonText()
 * @param bool $usersCanSave Whether this user can save the entry
 * @param int $currentPage The page being rendered
 * @param bool $nextIsThanksPage Whether the page after this one is (or resolves to)
 *                               the thanks page
 * @return array slot name => untranslated button text, in action bar order
 */
function formulize_multipageButtonSet($buttonText, $usersCanSave, $currentPage, $nextIsThanksPage) {
    $slots = array(
        'prev'  => formulizeMultipagePreviousButtonText($buttonText, $currentPage),
        'save'  => (is_array($buttonText) AND isset($buttonText['saveButtonText'])) ? $buttonText['saveButtonText'] : '',
        'close' => (is_array($buttonText) AND isset($buttonText['closeButtonText'])) ? $buttonText['closeButtonText'] : '',
        'next'  => formulizeMultipageNextButtonText($buttonText, $usersCanSave, $nextIsThanksPage),
    );
    $set = array();
    foreach($slots as $slot=>$text) {
        // the same two tests generatePrevNextButtonMarkup applies before it emits anything
        if(!$text OR ($slot == 'save' AND !$usersCanSave)) { continue; }
        $set[$slot] = $text;
    }
    return $set;
}

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
						break;
				case 'close':
						$buttonJavascriptAndExtraCode = "onclick=\"javascript:verifyDone();return false;\"";
    }

    if($buttonType == "next" OR $buttonType == "save") {
        $buttonMarkup = "<input type=button name='$buttonType' id='$buttonType' class='formulize-form-submit-button' value='" . $buttonText . "' $buttonJavascriptAndExtraCode>\n";
    } elseif($buttonType == "prev") {
        if($previousPage == "none") {
            $buttonJavascriptAndExtraCode = "onclick=\"javascript:submitForm($thanksPage, 1);return false;\"";
        }
        $buttonMarkup = "<input type=button name='prev' id='prev' class='formulize-form-submit-button' value='" . $buttonText . "' $buttonJavascriptAndExtraCode>\n";
    } elseif($buttonType == "close") {
				$buttonMarkup = "<input type=button name='close' id='close' class='formulize-form-submit-button' value='" . $buttonText . "' $buttonJavascriptAndExtraCode>\n";
		}
    return $buttonMarkup;
}


function pageSelectionList($currentPage, $countPages, $pageTitles, $aboveBelow, $conditions, $entry_id, $fid, $frid) {

	static $pageSelectionList = array();

    $cacheKey = md5(serialize(func_get_args()));

	if(isset($pageSelectionList[$cacheKey])) {
		return $pageSelectionList[$cacheKey];
	}

	$pageSelectionList[$cacheKey] = "<select name=\"pageselectionlist_$aboveBelow\" id=\"pageselectionlist_$aboveBelow\" size=\"1\" onchange=\"javascript:pageJump(this.form.pageselectionlist_$aboveBelow.options, $currentPage);\">\n";
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

/**
 * Which pages of a multipage screen the user can actually reach, and what each one is called.
 *
 * The full screen form's tab strip and the drawer's tab strip both have to answer that
 * question, and they have to answer it the same way or the two surfaces disagree about what
 * the form contains. A page is reachable when it meets its display conditions and is not made
 * up entirely of elements this user is not allowed to see. Page 0 is not a page of the form,
 * so it is never included.
 *
 * @return array page number => page title, in the compiled page order
 */
function formulize_visibleMultipagePages($pages, $pageTitles, $conditions, $entry_id, $fid, $frid, $userCanViewPrivateElements) {
	$visiblePages = array();
	unset($pageTitles[0]);
	foreach((array) $pageTitles as $pageNumber=>$title) {
		// an absent page compiles to no elements, which isPageAllPrivateElements reports as
		// not-all-private, so the page is kept - the behaviour this filter has always had
		$pageElements = isset($pages[$pageNumber]) ? $pages[$pageNumber] : array();
		if(pageMeetsConditions($conditions, $pageNumber, $entry_id, $fid, $frid)
			AND ($userCanViewPrivateElements OR !isPageAllPrivateElements($pageElements, $fid))) {
			$visiblePages[$pageNumber] = $title;
		}
	}
	return $visiblePages;
}

function isPageAllPrivateElements($pageElements, $fid) {
	if(is_array($pageElements) AND !empty($pageElements)) {
		global $xoopsDB;
		$sql = "SELECT ele_id FROM ".$xoopsDB->prefix("formulize")." WHERE id_form=".intval($fid)." AND ele_private=1 AND ele_id IN (".implode(",",array_filter($pageElements, 'is_numeric')).")";
		$result = $xoopsDB->query($sql);
		if($xoopsDB->getRowsNum($result) == count($pageElements)) {
			return true;
		}
	}
	return false;
}
