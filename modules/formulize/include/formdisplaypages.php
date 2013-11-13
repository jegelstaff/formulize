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

function displayFormPages($formframe, $entry="", $mainform="", $pages, $conditions="", $introtext="", $thankstext="", $done_dest="", $button_text="", $settings="", $overrideValue="", $printall=0, $screen=null, $saveAndContinueButtonText=null) { // nmc 2007.03.24 - added 'printall'
	
	formulize_benchmark("Start of displayFormPages.");
	
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
	$update_own_entry = $gperm_handler->checkRight("update_own_entry", $fid, $groups, $mid);
	$update_other_entries = $gperm_handler->checkRight("update_other_entries", $fid, $groups, $mid);
	$single_result = getSingle($fid, $uid, $groups, $member_handler, $gperm_handler, $mid);
	
	// if this function was called without an entry specified, then assume the identity of the entry we're editing (unless this is a new save, in which case no entry has been made yet)
	// no handling of cookies here, so anonymous multi-page surveys will not benefit from that feature
	// this emphasizes how we need to standardize a lot of these interfaces with a real class system
	if(!$entry AND $_POST['entry'.$fid]) {
		$entry = $_POST['entry'.$fid];
	} elseif(!$entry) { // or check getSingle to see what the real entry is
		$entry = $single_result['flag'] ? $single_result['entry'] : 0;
	}
	
	// formulize_newEntryIds is set when saving data
	if(!$entry AND isset($GLOBALS['formulize_newEntryIds'][$fid])) {
		$entry = $GLOBALS['formulize_newEntryIds'][$fid][0];
	}
	
	if($single_result['flag'] == "group" AND $update_own_entry AND $entry == $single_result['entry']) {
		$update_other_entries = true;
	}
	
	$owner = getEntryOwner($entry, $fid);
	
	
	$prevPage = isset($_POST['formulize_prevPage']) ? $_POST['formulize_prevPage'] : 1; // last page that the user was on, not necessarily the previous page numerically
	$currentPage = isset($_POST['formulize_currentPage']) ? $_POST['formulize_currentPage'] : 1;
	$thanksPage = count($pages) + 1;
	
	// debug control:
	$currentPage = (isset($_GET['debugpage']) AND is_numeric($_GET['debugpage'])) ? $_GET['debugpage'] : $currentPage;
	
	if($entry) {
		if(($owner == $uid AND $update_own_entry) OR ($owner != $uid AND $update_other_entries)) {
			$usersCanSave = true;
		} else {
			$usersCanSave = false;
		}
	} else {
		if($gperm_handler->checkRight("add_own_entry", $fid, $groups, $mid) OR $gperm_handler->checkRight("add_proxy_entries", $fid, $groups, $mid)) {
			$usersCanSave = true;
		} else {
			$usersCanSave = false;
		}
	}
	
	if($pages[$prevPage][0] !== "HTML" AND $pages[$prevPage][0] !== "PHP") { // remember prevPage is the last page the user was on, not the previous page numerically
		
		if(isset($_POST['form_submitted']) AND $usersCanSave) {
	
			include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formread.php";
			include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
			include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
	
			//$owner_groups =& $member_handler->getGroupsByUser($owner, FALSE);
			$data_handler = new formulizeDataHandler($fid);
			$owner_groups = $data_handler->getEntryOwnerGroups($entry);		
	
			$entries[$fid][0] = $entry;
	
			if($frid) { 
				$linkResults = checkForLinks($frid, array(0=>$fid), $fid, $entries, $gperm_handler, $owner_groups, $mid, $member_handler, $owner); 
				unset($entries);
				$entries = $linkResults['entries'];
			}
	
			$entries = $GLOBALS['formulize_allWrittenEntryIds']; // set in readelements.php
	
	
			// if there has been no specific entry specified yet, then assume the identity of the entry that was just saved -- assumption is it will be a new save
			// from this point forward in time, this is the only entry that should be involved, since the 'entry'.$fid condition above will put this value into $entry even if this function was called with a blank entry value
			if(!$entry) {
				$entry = $entries[$fid][0];
			}
			
			synchSubformBlankDefaults($fid, $entry);
		}
	}
	
	// check to see if there are conditions on this page, and if so are they met
	// if the conditions are not met, move on to the next page and repeat the condition check
	// conditions only checked once there is an entry!
	$pagesSkipped = false;
	if(is_array($conditions) AND $entry) {
		$conditionsMet = false;
		while(!$conditionsMet) {
			if(isset($conditions[$currentPage]) AND count($conditions[$currentPage][0])>0) { // conditions on the current page
				$thesecons = $conditions[$currentPage];
				$elements = $thesecons[0];
				$ops = $thesecons[1];
				$terms = $thesecons[2];
				$types = $thesecons[3]; // indicates if the term is part of a must or may set, ie: boolean and or or
				$filter = "";
				$oomfilter = "";
				$blankORSearch = "";
				foreach($elements as $i=>$thisElement) {
					if($ops[$i] == "NOT") { $ops[$i] = "!="; }
					if($terms[$i] == "{BLANK}") { // NOTE...USE OF BLANKS WON'T WORK CLEANLY IN ALL CASES DEPENDING WHAT OTHER TERMS HAVE BEEN SPECIFIED!!
						if($ops[$i] == "!=" OR $ops[$i] == "NOT LIKE") {
							if($types[$i] != "oom") {
								// add to the main filter, ie: entry id = 1 AND x=5 AND y IS NOT "" AND y IS NOT NULL
								if(!$filter) {
									$filter = $entry."][".$elements[$i]."/**//**/!=][".$elements[$i]."/**//**/IS NOT NULL";
								} else {
									$filter .= "][".$elements[$i]."/**//**/!=][".$elements[$i]."/**//**/IS NOT NULL";
								}
							} else {
								// Add to the OOM filter, ie: entry id = 1 AND (x=5 OR y IS NOT "" OR y IS NOT NULL)
								if(!$oomfilter) {
									$oomfilter = $elements[$i]."/**//**/=][".$elements[$i]."/**//**/IS NULL";
								} else {
									$oomfilter .= "][".$elements[$i]."/**//**/=][".$elements[$i]."/**//**/IS NULL";
								}
							}
						} else {
							if($types[$i] != "oom") {
								// add to its own OR filter, since we MUST match this condition, but we don't care if it's "" OR NULL
								// ie: entry id = 1 AND (x=5 OR y=10) AND (z = "" OR z IS NULL)
								if(!$blankORSearch) {
									$blankORSearch = $elements[$i]."/**//**/=][".$elements[$i]."/**//**/IS NULL";
								} else {
									$blankORSearch .= "][".$elements[$i]."/**//**/=][".$elements[$i]."/**//**/IS NULL";
								}
							} else {
								// it's part of the oom filters anyway, so we put it there, because we don't care if it's null or "" or neither
								if(!$oomfilter) {
									$oomfilter = $elements[$i]."/**//**/=][".$elements[$i]."/**//**/IS NULL";
								} else {
									$oomfilter .= "][".$elements[$i]."/**//**/=][".$elements[$i]."/**//**/IS NULL";
								}
							}
						}
					} elseif($types[$i] == "oom") {
						if(!$oomfilter) {
							$oomfilter = $elements[$i]."/**/".trans($terms[$i])."/**/".$ops[$i];
						} else {
							$oomfilter .= "][".$elements[$i]."/**/".trans($terms[$i])."/**/".$ops[$i];
						}
					} else {
						if(!$filter) {
							$filter = $entry."][".$elements[$i]."/**/".trans($terms[$i])."/**/".$ops[$i];
						} else {
							$filter .= "][".$elements[$i]."/**/".trans($terms[$i])."/**/".$ops[$i];
						}
					}
				}
				if($oomfilter AND $filter) {
					$finalFilter = array();
					$finalFilter[0][0] = "AND";
					$finalFilter[0][1] = $filter;
					$finalFilter[1][0] = "OR";
					$finalFilter[1][1] = $oomfilter;
					if($blankORSearch) {
						$finalFilter[2][0] = "OR";
						$finalFilter[2][1] = $blankORSearch;
					}
				} elseif($oomfilter) {
					// need to add the $entry as a separate filter from the oom, so the entry and oom get an AND in between them
					$finalFilter = array();
					$finalFilter[0][0] = "AND";
					$finalFilter[0][1] = $entry;
					$finalFilter[1][0] = "OR";
					$finalFilter[1][1] = $oomfilter;
					if($blankORSearch) {
						$finalFilter[2][0] = "OR";
						$finalFilter[2][1] = $blankORSearch;
					}
				} else {
					if($blankORSearch) {
						$finalFilter[0][0] = "AND";
						$finalFilter[0][1] = $filter ? $filter : $entry;
						$finalFilter[1][0] = "OR";
						$finalFilter[1][1] = $blankORSearch;
					} else {
						$finalFilter = $filter;
					}
				}
				$masterBoolean = "AND";

				include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
				$data = getData($frid, $fid, $finalFilter, $masterBoolean, "", "", "", "", "", false, 0, false, "", false, true);
				if(!$data) { 
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
	
	// Set up the javascript that we need for the form-submit functionality to work
	// note that validateAndSubmit calls the form validation function again, but obviously it will pass if it passed here.  The validation needs to be called prior to setting the pages, or else you can end up on the wrong page after clicking an ADD button in a subform when you've missed a required field.
	?>
	
	<script type='text/javascript'>
	
	function submitForm(page, prevpage) {
		var validate = xoopsFormValidate_formulize();
		if(validate) {<?php
			if(is_object($screen) AND $screen->getVar('finishisdone') AND $currentPage+1 == $thanksPage) {
				// neuter the ventry which is the key thing that keeps us on the form page, if in fact we just came from a list screen of some kind
				// need to use an unusual selector, because something about selecting by id wasn't working, apparently may be related to setting actions on forms with certain versions of jQuery??
				print "		if(page == $thanksPage) {
					window.document.formulize.ventry.value = '';
					jQuery('form[name=formulize]').attr('action', '$done_dest');
				}
				";
			}?>
			window.document.formulize.formulize_currentPage.value = page;
			window.document.formulize.formulize_prevPage.value = prevpage;
			window.document.formulize.formulize_doneDest.value = '<?php print $done_dest; ?>';
			window.document.formulize.formulize_buttonText.value = '<?php print $button_text; ?>';
			validateAndSubmit();
		}
	}
	
	function pageJump(options, prevpage) {
		for (var i=0; i < options.length; i++) {
			if (options[i].selected) {
				submitForm(options[i].value, prevpage);
				return false;
			}
		}
	}
	
	</script><noscript>
	<h1>You do not have javascript enabled in your web browser.  This form will not work with your web browser.  Please contact the webmaster for assistance.</h1>
	</noscript>
	<?php
		
	if($currentPage == $thanksPage) {
	
		if(is_array($thankstext)) { 
			if($thankstext[0] === "PHP") {
				eval($thankstext[1]);
			} else {
				print $thankstext[1];
			}
		} else { // HTML
			print html_entity_decode($thankstext);
		}
		print "<br><hr><br><div id=\"thankYouNavigation\"><p><center>\n";
		if($pagesSkipped) {
			print _formulize_DMULTI_SKIP . "</p><p>\n";
		}
		$button_text = $button_text ? $button_text : _formulize_DMULTI_ALLDONE;
		if($button_text != "{NOBUTTON}") {
			print "<a href='$done_dest'";
			if(is_array($settings)) {
				print " onclick=\"javascript:window.document.calreturnform.submit();return false;\"";
			}
			print ">" . $button_text . "</a>\n";
		}
		print "</center></p></div>";
	
		if(is_array($settings)) {
			print "<form name=calreturnform action=\"$done_dest\" method=post>\n";
			writeHiddenSettings($settings);
			print "</form>";
		}
	
	
	} 
	
	$submitJavascriptNext =  "onclick=\"javascript:submitForm($nextPage, $currentPage);return false;\"";
	$submitJavascriptPrev =  "onclick=\"javascript:submitForm($previousPage, $currentPage);return false;\"";
	
	if($currentPage == 1 AND $pages[1][0] !== "HTML" AND $pages[1][0] !== "PHP" AND !$_POST['goto_sfid']) { // only show intro text on first page if there's actually a form there
	  print html_entity_decode(html_entity_decode($introtext));
	}
	
	unset($_POST['form_submitted']);
	
	
	
	
	
	// display an HTML or PHP page if that's what this page is...
	if($currentPage != $thanksPage AND ($pages[$currentPage][0] === "HTML" OR $pages[$currentPage][0] === "PHP")) {
		// PHP
		if($pages[$currentPage][0] === "PHP") {
			eval($pages[$currentPage][1]);
		// HTML
		} else {
			print $pages[$currentPage][1];
		}
	
		// put in the form that passes the entry, page we're going to and page we were on
		include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
		?>
	
		
		<form name=formulize id=formulize action=<?php print getCurrentURL(); ?> method=post>
		<input type=hidden name=entry<?php print $fid; ?> id=entry<?php print $fid; ?> value=<?php print $entry ?>>
		<input type=hidden name=formulize_currentPage id=formulize_currentPage value="">
		<input type=hidden name=formulize_prevPage id=formulize_prevPage value="">
		</form>
	
		<script type="text/javascript">
			function validateAndSubmit() {
				window.document.formulize.submit();
			}
		</script>
	
		<?php
	
	}
	
	// display a form if that's what this page is...
	if($currentPage != $thanksPage AND $pages[$currentPage][0] !== "HTML" AND $pages[$currentPage][0] !== "PHP") {
	
		$buttonArray = array(0=>"{NOBUTTON}", 1=>"{NOBUTTON}");
		foreach($pages[$currentPage] as $element) {
		  $elements_allowed[] = $element;
	  }
		$forminfo['elements'] = $elements_allowed;
		$forminfo['formframe'] = $formframe;
		$titleOverride = isset($pageTitles[$currentPage]) ? trans($pageTitles[$currentPage]) : "all"; // we can pass in any text value as the titleOverride, and it will have the same effect as "all", but the alternate text will be used as the title for the form
	
		$GLOBALS['nosubforms'] = true; // subforms cannot have a view button on multipage forms, since moving to a sub causes total confusion of which entry and fid you are looking at
	
		$settings['formulize_currentPage'] = $currentPage;
		$settings['formulize_prevPage'] = $currentPage; // now that we're done everything else, we can send the current page as the previous page when initializing the form.  Javascript will set the true value prior to submission.
	
		formulize_benchmark("Before drawing nav.");
	
		$previousButtonText = (is_array($saveAndContinueButtonText) AND isset($saveAndContinueButtonText['previousButtonText'])) ? $saveAndContinueButtonText['previousButtonText'] : _formulize_DMULTI_PREV;
		if($usersCanSave AND $nextPage==$thanksPage) {
		    $nextButtonText = (is_array($saveAndContinueButtonText) AND $saveAndContinueButtonText['saveButtonText']) ? $saveAndContinueButtonText['saveButtonText'] :  _formulize_DMULTI_SAVE;
		} else {
		    $nextButtonText = (is_array($saveAndContinueButtonText) AND $saveAndContinueButtonText['nextButtonText']) ? $saveAndContinueButtonText['nextButtonText'] : _formulize_DMULTI_NEXT;
		}
		$previousPageButton = generatePrevNextButtonMarkup("prev", $previousButtonText, $submitJavascriptPrev, $usersCanSave, $nextPage, $previousPage, $thanksPage);
		$nextPageButton = generatePrevNextButtonMarkup("next", $nextButtonText, $submitJavascriptNext, $usersCanSave, $nextPage, $previousPage, $thanksPage);
		$totalPages = count($pages);
		$skippedPageMessage = $pagesSkipped ? _formulize_DMULTI_SKIP : "";
		$pageSelectionList = pageSelectionList($currentPage, $totalPages, $pageTitles, "above");   // calling for the 'above' drawPageNav 

        // setting up the basic templateVars for all templates
        // templatevariables must be called after all the variables are loaded otherwise they do not make it into renderTemplate
        $templateVariables = array('previousPageButton' => $previousPageButton, 'nextPageButton' => $nextPageButton,
            'totalPages' => $totalPages, 'currentPage' => $currentPage, 'skippedPageMessage' => $skippedPageMessage,
            'pageSelectionList'=>$pageSelectionList, 'pageTitles' => $pageTitles);

		print "<form name=\"pageNavOptions_above\" id=\"pageNavOptions_above\">\n";
		if($screen AND $toptemplate = $screen->getVar('toptemplate')) {
		    print renderTemplate($toptemplate, $templateVariables);
		} else {
		    drawPageNav($usersCanSave, $currentPage, $totalPages, "above", $nextPageButton, $previousPageButton, $skippedPageMessage, $pageSelectionList);
		}
		print "</form>";
		
		formulize_benchmark("After drawing nav/before displayForm.");
		
	    // need to check for the existence of an elementtemplate property in the screen, like we did with the top and bottom templates
	    // if there's an eleemnt template, then do this loop, otherwise, do the displayForm call like normal
	    if ($screen AND $elementtemplate = $screen->getVar('elementtemplate')) {  // Code added by Julian 2012-09-04 and Gordon Woodmansey 2012-09-05 to render the elementtemplate
		    if(!security_check($fid, $entry)) {
			exit();
		    }
		    // start the form manually...
		    $formObjectForRequiredJS = new formulize_themeForm('form object for required js', 'formulize', getCurrentURL(), "post", true);
		    $element_handler = xoops_getmodulehandler('elements', 'formulize');
		    print "<div id='formulizeform'><form id='formulize' name='formulize' action='".getCurrentURL()."' method='post' onsubmit='return xoopsFormValidate_formulize();' enctype='multipart/form-data'>";
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
			        print renderTemplate($elementtemplate, $templateVariables);
			    }
		    }
		    // now we also need to add in some bits that are necessary for the form submission logic to work...borrowed from parts of formdisplay.php mostly...this should be put together into a more distinct rendering system for forms, so we can call the pieces as needed
		    print "<input type=hidden name=formulize_currentPage value='".$settings['formulize_currentPage']."'>";
		    print "<input type=hidden name=formulize_prevPage value='".$settings['formulize_prevPage']."'>";
		    print "<input type=hidden name=formulize_doneDest value='".$settings['formulize_doneDest']."'>";
		    print "<input type=hidden name=formulize_buttonText value='".$settings['formulize_buttonText']."'>";
		    print "<input type=hidden name=ventry value='".$settings['ventry']."'>";
		    print $GLOBALS['xoopsSecurity']->getTokenHTML();
		    if($entry) {
			    print "<input type=hidden name=entry".$fid." value=".intval($entry).">"; // need this to persist the entry that the user is 
		    }
		    print "</form></div>";
		    print "<div id=savingmessage style=\"display: none; position: absolute; width: 100%; right: 0px; text-align: center; padding-top: 50px;\">\n";
		    if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/images/saving-".$xoopsConfig['language'].".gif") ) {
			    print "<img src=\"" . XOOPS_URL . "/modules/formulize/images/saving-" . $xoopsConfig['language'] . ".gif\">\n";
		    } else {
			    print "<img src=\"" . XOOPS_URL . "/modules/formulize/images/saving-english.gif\">\n";
		    }
		    print "</div>\n";
		    drawJavascript();
		    // need to create the form object, and add all the rendered elements to it, and then we'll have working required elements if we render the validation logic for the form
		    print $formObjectForRequiredJS->renderValidationJS(true, true); // with tags, true, skip the extra js that checks for the formulize theme form divs around the elements so that conditional animation works, true
		    // print "<script type=\"text/javascript\">function xoopsFormValidate_formulize(){return true;}</script>"; // shim for the validation javascript that is created by the xoopsThemeForms, and which our saving logic currently references...saving won't work without this...we should actually render the proper validation logic at some point, but not today.
	    } else {
		displayForm($forminfo, $entry, $mainform, "", $buttonArray, $settings, $titleOverride, $overrideValue, "", "", 0, 0, $printall, $screen); // nmc 2007.03.24 - added empty params & '$printall'
	    }
	    
		formulize_benchmark("After displayForm.");

}
    

    if($currentPage != $thanksPage AND !$_POST['goto_sfid']) {
	    // have to get the new value for $pageSelection list if the user requires it on the users view.
	    $pageSelectionList = pageSelectionList($currentPage, $totalPages, $pageTitles, "below");
	    print "<form name=\"pageNavOptions_below\" id=\"pageNavOptions_below\">\n";
	    if ($screen AND $bottomtemplate = $screen->getVar('bottomtemplate')) { 
		    $templateVariables['pageSelectionList'] = $pageSelectionList; // assign the new pageSelectionList, since it was redone for the bottom section
		    print renderTemplate($bottomtemplate, $templateVariables);
	    } else {
		    drawPageNav($usersCanSave, $currentPage, $totalPages, "below", $nextPageButton, $previousPageButton, $skippedPageMessage, $pageSelectionList);
	    }
	    print "</form>";
    }	


    formulize_benchmark("End of displayFormPages.");


} // end of the function!

// Added $pageSelectionList by Gordon Woodmansey 2012-09-04
function drawPageNav($usersCanSave="", $currentPage="", $totalPages, $aboveBelow, $nextPageButton, $previousPageButton, $skippedPageMessage, $pageSelectionList) {

	if($aboveBelow == "above") {
		//navigation options above the form print like this
		print "<br /><div id=\"pageNavTable\"><table><tr>\n";
		print "<td style=\"vertical-align: middle; padding-right: 5px;\"><nobr><b>" . _formulize_DMULTI_YOUAREON . "</b></nobr><br /><nobr>" . _formulize_DMULTI_PAGE . " $currentPage " . _formulize_DMULTI_OF . " " . $totalPages . "</nobr></td>";
		print "<td style=\"vertical-align: middle; padding-right: 5px;\">";
		print $previousPageButton;
		print "</td>";
		print "<td style=\"vertical-align: middle; padding-right: 5px;\">";
                print $nextPageButton;
	
		print "</td>";
		print "<td style=\"vertical-align: middle;\">";
		print _formulize_DMULTI_JUMPTO . "&nbsp;&nbsp;" . $pageSelectionList;
		print "</td></tr></table></div></form><br />";
        } else { 
                //navigation options below the form print like this
                print "<div id=\"bottomPageNumber\">" . _formulize_DMULTI_PAGE . " $currentPage " . _formulize_DMULTI_OF . " " . $totalPages;
                if(!$usersCanSave) {print "<br>" . _formulize_INFO_NOSAVE;}
                if($skippedPageMessage) {
                    print "<br>". $skippedPageMessage;
                }
                print "<div id=\"bottomJumpList\"><p>". _formulize_DMULTI_JUMPTO . "&nbsp;&nbsp;" . $pageSelectionList . "</p></div>";
                print "</p></div>";
                print "<div id=\"bottom-save-block\"><form name=\"pageNavOptions_$aboveBelow\" id==\"pageNavOptions_$aboveBelow\">";
                print $previousPageButton;
                print "&nbsp;&nbsp;&nbsp;&nbsp;";
                print $nextPageButton;
                print "</div>";
        }
}

// THIS FUNCTION GENERATES THE MARKUP FOR THE PREVIOUS AND NEXT BUTTONS
function generatePrevNextButtonMarkup($buttonType, $buttonText, $buttonJavascriptAndExtraCode, $usersCanSave, $nextPage, $previousPage, $thanksPage) {
    $buttonMarkup = "";
    if($buttonType == "next") {
        if(!$usersCanSave AND $nextPage==$thanksPage) {
            $buttonJavascriptAndExtraCode = "disabled=true";
        }
        $buttonMarkup = "<input type=button name=next id=next class='formulize-form-submit-button' value='" . $buttonText . "' $buttonJavascriptAndExtraCode>\n";
    } elseif($buttonType == "prev") {
        if($previousPage == "none") {
            $buttonJavascriptAndExtraCode = "disabled=true";
        }
        $buttonMarkup = "<input type=button name=prev id=prev class='formulize-form-submit-button' value='" . $buttonText . "' $buttonJavascriptAndExtraCode>\n";
    }
    return $buttonMarkup;
}


function pageSelectionList($currentPage, $countPages, $pageTitles, $aboveBelow) {

	static $pageSelectionList = "";
	
	if($pageSelectionList) {
		return $pageSelectionList;
	}

	$pageSelectionList .= "<select name=\"pageselectionlist_$aboveBelow\" id=\"pageselectionlist_$aboveBelow\" size=\"1\" onchange=\"javascript:pageJump(this.form.pageselectionlist_$aboveBelow.options, $currentPage);\">\n";
	for($page=1;$page<=$countPages;$page++) {
		if(isset($pageTitle[$page]) AND strstr($pageTitles[$page], "[")) {
			$title = " &mdash; " . trans($pageTitles[$page]); // translation can be expensive, so only do it if we have to (regular expression matching is not pretty)
		} elseif(isset($pageTitles[$page])) {
			$title = " &mdash; " . $pageTitles[$page];
		} else {
			$title = "";
		}
		$pageSelectionList .= "<option value=$page";
		$pageSelectionList .= $page == $currentPage ? " selected=true>" : ">";
		$pageSelectionList .= $page . $title . "</option>\n";
	}
	$pageSelectionList .= "</select>";
	return $pageSelectionList;
}
