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

function displayFormPages($formframe, $entry="", $mainform="", $pages, $conditions="", $introtext="", $thankstext="", $done_dest="", $button_text="", $settings="", $overrideValue="", $printall=0, $screen=null) { // nmc 2007.03.24 - added 'printall'

formulize_benchmark("Start of displayFormPages.");

// extract the optional page titles from the $pages array for use in the jump to box
// NOTE: pageTitles array must start with key 1, not 0.  Page 1 is the first page of the form
$pageTitles = array();
if(isset($pages['titles'])) {
	$pageTitles = $pages['titles'];
	unset($pages['titles']);
}

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

// this is probably not necessary any more, due to architecture changes in Formulize 3
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
	foreach($pages[$prevPage] as $element) {
	  $save_elements[] = $element;
	}

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

		$formulize_mgr =& xoops_getmodulehandler('elements', 'formulize');
		//$entries = handleSubmission($formulize_mgr, $entries, $uid, $owner, $fid, $owner_groups, $groups, "", $save_elements, $mid, $screen);
		$entries = $GLOBALS['formulize_allWrittenEntryIds']; // set in readelements.php


		// if there has been no specific entry specified yet, then assume the identity of the entry that was just saved -- assumption is it will be a new save
		// from this point forward in time, this is the only entry that should be involved, since the 'entry'.$fid condition above will put this value into $entry even if this function was called with a blank entry value
		if(!$entry) {
			$entry = $entries[$fid][0];
		}
		
		synchSubformBlankDefaults($fid, $entry);
	}
}

// Set up the javascript that we need for the form-submit functionality to work
// note that validateAndSubmit calls the form validation function again, but obviously it will pass if it passed here.  The validation needs to be called prior to setting the pages, or else you can end up on the wrong page after clicking an ADD button in a subform when you've missed a required field.
?>

<script type='text/javascript'>

function submitForm(page, prevpage) {
	var validate = xoopsFormValidate_formulize();
	if(validate) {
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
			$start = 1;
			foreach($elements as $i=>$thisElement) {
			//for($i=0;$i<count($elements);$i++) {
				if($ops[$i] == "NOT") { $ops[$i] = "!="; }
				if($start) {
					$filter = $entry."][".$elements[$i]."/**/".trans($terms[$i])."/**/".$ops[$i];
					$start = 0;
				} else {
					$filter .= "][".$elements[$i]."/**/".trans($terms[$i])."/**/".$ops[$i];
				}
			}
			include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
			$data = getData($frid, $fid, $filter, "AND");
			if($data[0] == "") { 
				if($prevPage < $currentPage) {
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



$thanksPage = count($pages) + 1;

if($currentPage > 1) {
  $previousPage = $currentPage-1; // previous page numerically
} else {
  $previousPage = "none";
}

$nextPage = $currentPage+1;

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
	print "<br><hr><br><p><center>\n";
	if($pagesSkipped) {
		print _formulize_DMULTI_SKIP . "</p><p>\n";
	}
	$done_dest = $done_dest ? $done_dest : getCurrentURL();
	$button_text = $button_text ? $button_text : _formulize_DMULTI_ALLDONE;
	if($button_text != "{NOBUTTON}") {
		print "<a href='$done_dest'";
		if(is_array($settings)) {
			print " onclick=\"javascript:window.document.calreturnform.submit();return false;\"";
		}
		print ">" . $button_text . "</a>\n";
	}
	print "</center></p>";

	if(is_array($settings)) {
		print "<form name=calreturnform action=\"$done_dest\" method=post>\n";
		writeHiddenSettings($settings);
		print "</form>";
	}


} 

$submitTextNext =  "onclick=\"javascript:submitForm($nextPage, $currentPage);return false;\"";
$submitTextPrev =  "onclick=\"javascript:submitForm($previousPage, $currentPage);return false;\"";

if($currentPage == 1 AND $pages[1][0] !== "HTML" AND $pages[1][0] !== "PHP" AND !$_POST['goto_sfid']) { // only show intro text on first page if there's actually a form there
  print html_entity_decode($introtext);
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

	drawPageNav($usersCanSave, $pagesSkipped, $currentPage, $previousPage, $nextPage,$submitTextPrev, $submitTextNext, $pages, $thanksPage, $pageTitles, "above");
	
	formulize_benchmark("After drawing nav/before displayForm.");
	
	displayForm($forminfo, $entry, $mainform, "", $buttonArray, $settings, $titleOverride, $overrideValue, "", "", 0, 0, $printall, $screen); // nmc 2007.03.24 - added empty params & '$printall'

	formulize_benchmark("After displayForm.");

}

if($currentPage != $thanksPage AND !$_POST['goto_sfid']) {

	drawPageNav($usersCanSave, $pagesSkipped, $currentPage, $previousPage, $nextPage,$submitTextPrev, $submitTextNext, $pages, $thanksPage, $pageTitles, "below");

	print "</center>";

}

formulize_benchmark("End of displayFormPages.");


} // end of the function!


function drawPageNav($usersCanSave="", $pagesSkipped="", $currentPage="", $previousPage="", $nextPage="", $submitTextPrev="", $submitTextNext="", $pages="", $thanksPage, $pageTitles, $aboveBelow) {

	if($aboveBelow == "above") {
		//navigation options above the form print like this
		print "<br /><form name=\"pageNavOptions_$aboveBelow\" id==\"pageNavOptions_$aboveBelow\"><table><tr>\n";
		print "<td style=\"vertical-align: middle; padding-right: 5px;\"><nobr><b>" . _formulize_DMULTI_YOUAREON . "</b></nobr><br /><nobr>" . _formulize_DMULTI_PAGE . " $currentPage " . _formulize_DMULTI_OF . " " . count($pages) . "</nobr></td>";
		print "<td style=\"vertical-align: middle; padding-right: 5px;\">";
	if($previousPage != "none") {
	  print "<input type=button name=prev id=prev value='" . _formulize_DMULTI_PREV . "' $submitTextPrev>\n";
	} else {
	  print "<input type=button name=prev id=prev value='" . _formulize_DMULTI_PREV . "' disabled=true>\n";
	}
		print "</td>";
		print "<td style=\"vertical-align: middle; padding-right: 5px;\">";

	if($usersCanSave AND $nextPage==$thanksPage) {
	  print "<input type=button name=next id=next value='" . _formulize_DMULTI_SAVE . "' $submitTextNext>\n";
	} elseif($nextPage==$thanksPage) {
	  print "<input type=button name=next id=next value='" . _formulize_DMULTI_NEXT . "' disabled=true>\n";
	} else {
	  print "<input type=button name=next id=next value='" . _formulize_DMULTI_NEXT . "' $submitTextNext>\n";
	}
		print "</td>";
		print "<td style=\"vertical-align: middle;\">";
		print _formulize_DMULTI_JUMPTO . "&nbsp;&nbsp;" . pageSelectionList($currentPage, count($pages), $pageTitles, $aboveBelow);
		print "</td></tr></table></form><br />";
} else { 
	//navigation options below the form print like this
	print "<br><center><p>" . _formulize_DMULTI_PAGE . " $currentPage " . _formulize_DMULTI_OF . " " . count($pages);
	if(!$usersCanSave) {print "<br>" . _formulize_INFO_NOSAVE;}
	if($pagesSkipped) {print "<br>". _formulize_DMULTI_SKIP;}
	print "</p><br><form name=\"pageNavOptions_$aboveBelow\" id==\"pageNavOptions_$aboveBelow\">";
	if($previousPage != "none") {print "<input type=button name=prev id=prev value='" . _formulize_DMULTI_PREV . "' $submitTextPrev>\n";} 
	else {print "<input type=button name=prev id=prev value='" . _formulize_DMULTI_PREV . "' disabled=true>\n";}
	print "&nbsp;&nbsp;&nbsp;&nbsp;";
	if($usersCanSave AND $nextPage==$thanksPage) {print "<input type=button name=next id=next value='" . _formulize_DMULTI_SAVE . "' $submitTextNext>\n";} 
	elseif($nextPage==$thanksPage) {print "<input type=button name=next id=next value='" . _formulize_DMULTI_NEXT . "' disabled=true>\n";} 
	else {print "<input type=button name=next id=next value='" . _formulize_DMULTI_NEXT . "' $submitTextNext>\n";}
	print "<br><p>". _formulize_DMULTI_JUMPTO . "&nbsp;&nbsp;" . pageSelectionList($currentPage, count($pages), $pageTitles, $aboveBelow) . "</p></form>";
}
}

function pageSelectionList($currentPage, $countPages, $pageTitles, $aboveBelow) {

	$pageSelectionList = "";

	$pageSelectionList .= "<select name=\"pageselectionlist_$aboveBelow\" id=\"pageselectionlist_$aboveBelow\" size=\"1\" onchange=\"javascript:pageJump(this.form.pageselectionlist_$aboveBelow.options, $currentPage);\">\n";
	for($page=1;$page<=$countPages;$page++) {
		$title = isset($pageTitles[$page]) ? " &mdash; " . trans($pageTitles[$page]) : "";
		$pageSelectionList .= "<option value=$page";
		$pageSelectionList .= $page == $currentPage ? " selected=true>" : ">";
		$pageSelectionList .= $page . $title . "</option>\n";
	}
	$pageSelectionList .= "</select>";
	return $pageSelectionList;
}

?>