<?php
// ------------------------------------------------------------------------- 
//	pageworks
//		Copyright 2004, Freeform Solutions
// 		
//	Template
//		Copyright 2004 Thomas Hill
//		<a href="http://www.worldware.com">worldware.com</a>
// ------------------------------------------------------------------------- 
// ------------------------------------------------------------------------- //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //

//if(!function_exists("microtime_float2")) {
//function microtime_float2()
//{
//   list($usec, $sec) = explode(" ", microtime());
//   return ((float)$usec + (float)$sec);
//}
//}

//$time_start = microtime_float2();

// nullify the $page variable if a URL param is being passed and the $page variable has not been set previously
// Normal order in which pages get processed is the main body first, in which case $page should not have been set
// and then blocks get read in by header.php second, and for each block, $page should have been set, and so for
// the blocks, $page will be valid and that will control the drawing of the contents

// if the $page variable equals the page requested in the URL, then kill the page variable
// this will screw up a pageworks block that tries to display on a pageworks page when the block and page have the same contents
// but why would that ever happen?!
// This fix primarily addresses a bug that happens when register_globals turns $_GET['page'] into $page and the mainfile would therefore not be loaded!
if(isset($_GET['page']) AND isset($page)) {
	if($page == $_GET['page']) {
		unset($page);
	}
}

if(!isset($page) AND isset($_GET['page'])) { $page=""; }
if(!isset($_GET['block'])) { $_GET['block'] =""; }

if(!$page) {
	$pageworksOverride = "";
	if($_GET['block']) {
		$pageworksOverride = "password goes here";
	} 

	if(defined("XOOPS_ROOT_PATH")) {
		require_once XOOPS_ROOT_PATH . "/mainfile.php";
	} else {
	    	require_once "../../mainfile.php";
	}
}

if ( file_exists(XOOPS_ROOT_PATH ."/modules/pageworks/language/".$xoopsConfig['language']."/templates.php") ) 
    require_once XOOPS_ROOT_PATH ."/modules/pageworks/language/".$xoopsConfig['language']."/templates.php";
else 
	include_once XOOPS_ROOT_PATH ."/modules/pageworks/language/english/templates.php";
// Include any common code for this module.
include_once(XOOPS_ROOT_PATH ."/modules/pageworks/include/pageworks_includes.php");
include_once XOOPS_ROOT_PATH . "/modules/pageworks/include/functions.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";

global $xoopsDB, $xoopsUser;

// GET THE MODULE ID
	$res4 = $xoopsDB->query("SELECT mid FROM ".$xoopsDB->prefix("modules")." WHERE dirname='pageworks'");
	if ($res4) {
		while ($row = mysql_fetch_row($res4))
			$module_id = $row[0];
	}

if(!$page AND !$_GET['block']) {
	include XOOPS_ROOT_PATH.'/header.php';
}

// RUN THE LOGIC FOR THIS PAGE
// 1. determine what page has been requested
// 2. check to see if the user has permission for that page
// 3. if no, redirect to front page of site
// 4. if yes, continue
// 5. determine what id, filters and andor were sent from the prev page
// 6. gather framework, mainform, filter, andor, sort and output name from database (andor missing from database right now) 
// 7. if id passed, id overrides filters
// 8. if no id, merge filters, etc, other options in effect
// 9. run extraction, put results in output name
// 10. run PHP code from template

// 1. determine what page has been requested
if(!$page_id = $page) { // page can alread be set if this is a block (or is being invoked from somewhere else)
	if(!$page_id = $_POST['displayElementRedirect']) { 
		if(!$page_id = $_POST['page']) {
			if(!$page_id = $_GET['page']) {
				$page_id = "none"; 
			}
		}
	}
}


if($page_id != "none") { // show blank page if no page is specified (all code below is omitted)

// get the page_title and page_template
$pagedata = q("SELECT page_title, page_template, page_html_from_db FROM " . $xoopsDB->prefix("pageworks_pages") . " WHERE page_id='$page_id'");

// 2. check perms, unless we're in a block in which case block permissions are in effect
if(!$page AND !$_GET['block']) {

  $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS); 
	$gperm_handler = &xoops_gethandler('groupperm');
	$allowedPages = $gperm_handler->getItemIds("view", $groups, $module_id);

	if($_GET['debug']==1) {
		print_r($groups);
		print "<br>" . $page_id . "<br>";
		print_r($allowedPages);
	}

	if(!in_array($page_id, $allowedPages)) {
		redirect_header(XOOPS_URL . "/user.php", 2, _NO_PERM);
	}

}

// 5. determine params from previous page

if(!$page AND !$_GET['block']) { // only set ID if 
	if(!$id = $_POST['id']) {
		$id = $_GET['id'];
	}
} else {
	$id = 0; // don't pay attention to the id in the URL if we're not on the body of the page
}
if(!$filters = $_POST['filters']) {
	$filters = $_GET['filters'];
}
if(!$andor = $_POST['andor']) {
	$andor = $_GET['andor'];
}
if(!$sort = $_POST['sort']) {
	$sort = $_GET['sort'];
}

// 5.5 handle displayElements data that has been passed back from the page -- added Oct 2 2005

// one time only, the code to read the displayElement elements will be executed
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/readelements.php";

// 5.6 handle displayButtons...
// look for passed back:
// $_POST['displayButtonProcessEle'] -- element that is being handled
// $_POST['displayButtonProcessEntry'] -- entry that is being handled (can be "new" to indicate a new entry)
// $_POST['displayButtonProcessValue'] -- the value to put in this element in this entry
// $_POST['displayButtonProcessAppend'] -- flag indicating whether the value replaces the current value or gets appended to it
// $_POST['displayButtonProcessPrevValue'] -- a flag indicating whether there is an existing value for this element in this entry
// $_POST['displayButtonProcessFormFrame'] -- a flag indicating whether there is a form framework

if($_POST['displayButtonProcessEle']) { // assume that all five are present if this is present -- since they are set with a javascript function all at the same time, this should be a valid assumption
	//writeElementValue($_POST['displayButtonProcessEle'], $_POST['displayButtonProcessEntry'], $_POST['displayButtonProcessValue'], $_POST['displayButtonProcessAppend'], $_POST['displayButtonProcessPrevValue']);
	/*writeElementValue($_POST['displayButtonProcessFormFrame'], $_POST['displayButtonProcessEle'], $_POST['displayButtonProcessEntry'], $_POST['displayButtonProcessValue'], $_POST['displayButtonProcessAppend'], $_POST['displayButtonProcessPrevValue']);

	global $post_displayButtonProcessEle;
	$post_displayButtonProcessEle = $_POST['displayButtonProcessEle'];    
    unset($_POST['displayButtonProcessEle']); // prevents multiple operations with this data if multiple pageworks blocks are visible*/
	include_once "display_buttons.php";    
}



// 6. get stored framework info for this page

$frameworks = getFrameworks($page_id);
//keys:
//framework
//mainform
//filters
//sort
//output
//sortable 0 no, 1 yes
//sortdir 0 asc, 1 desc

//print_r($frameworks);

// 7.8. ID overrides filters, otherwise, merge filters
// assume that passed params are only meant for first framework returned

if($id>0 AND $frameworks[0]['filters'] != "" AND !is_numeric($frameworks[0]['filters'])) {
	$frameworks[0]['filters'] .= "][$id"; // add id to existing non-numeric filters
} elseif($id>0) {
	$frameworks[0]['filters'] = $id; // id takes precedence over filters in URL
} elseif($frameworks[0]['filters'] != "" AND $filters) {
	$frameworks[0]['filters'] .= "][$filters"; // then we append filters if ones exist from db
} elseif($frameworks[0]['filters'] == "") {
	$frameworks[0]['filters'] = $filters; // or last of all, we just use the passed ones when nothing is in db
}

// 9. run extraction, do the sort

if($frameworks[0]['output']) { // if there's at least one framework to generate...then do extractions...

//$framework = $frameworks[0]['framework']; // set the global variable $framework to be equal to the first framework specified for the page.  To use subsequent frameworks, you must change $framework to be equal to their name at the appropriate place inside the template code.

foreach($frameworks as $afw) {
	if($_GET['debug']==1) {	print_r($afw); }
	${$afw['output']} = start($afw['framework'], $afw['mainform'], $afw['filters']);
	if($afw['sortable']) { 
		switch($afw['sortdir']) {
			case "0":
				$sortdir = "SORT_ASC";
				break;
			case "1":
				$sortdir = "SORT_DESC";
				break;
		}
		${$afw['output']} = resultSort(${$afw['output']}, $afw['sort'], $sortdir); 
	}
	if($_GET['debug']==1) {
		print ${$afw['output']} . ": "; // DEBUG code
		print_r(${$afw['output']});
		print "<br><br>";
	}
}
} // end of if there's a framework...

// PARSE TEMPLATE AND OTHER PAGE DATA...

if(!$page AND !$_GET['block']) { // assign the page title unless we're in a block
	$xoopsTpl->assign('xoops_pagetitle', $pagedata[0]['page_title']);
}

//print "<h1>" . $pagedata[0]['page_title'] . "</h1>";

if(strstr($pagedata[0]['page_template'], "displayFormPages(")) {
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplaypages.php";
}
if(strstr($pagedata[0]['page_template'], "displayForm(")) {
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
}
if(strstr($pagedata[0]['page_template'], "displayEntries(")) {
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/entriesdisplay.php";
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
}
if(strstr($pagedata[0]['page_template'], "displayCalendar(")) {
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/calendardisplay.php";
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
}
// Note:  using displayElement is probably incompatible with using displayForm on a page, or displayElements, or displayCalendar, since element display wraps the entire pageworks output in a form.
if(strstr($pagedata[0]['page_template'], "displayElement(") OR strstr($pagedata[0]['page_template'], "displayButton(") OR strstr($pagedata[0]['page_template'], "displayGrid(") OR strstr($pagedata[0]['page_template'], "displayCaption(")) {
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/elementdisplay.php";
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
	if(strstr($pagedata[0]['page_template'], "displayButton(")) {
		include_once XOOPS_ROOT_PATH . "/modules/pageworks/include/displayButton_HTML.php";
	}
	if(strstr($pagedata[0]['page_template'], "displayElement(") OR strstr($pagedata[0]['page_template'], "displayGrid(")) {
		$thistime = microtime();
		print "<form name=\"elementdisplayform_$thistime\" action=" . getCurrentUrl() . " method=post>\n";
	}
	if(strstr($pagedata[0]['page_template'], "displayGrid(")) {
		include_once XOOPS_ROOT_PATH . "/modules/formulize/include/griddisplay.php";
	}
}


// 10. run PHP code from template 

ob_start();
$result = eval(htmlspecialchars_decode($pagedata[0]['page_template'], ENT_QUOTES));
$page_contents = ob_get_clean();
if($pagedata[0]['page_html_from_db']) { // if HTML chars from the DB are allowed for this page, then convert the output of this page so that HTML will display right on the screen
	$page_contents = htmlspecialchars_decode($page_contents); // does not decode &039; so textboxes don't break.
}
print $page_contents;


// close the elementdisplayform if necessary, including all the handling for displayButtons
if(strstr($pagedata[0]['page_template'], "displayElement(") OR strstr($pagedata[0]['page_template'], "displayGrid(")) {
	if(!strstr($pagedata[0]['page_template'], "displayElementSave(")) {
		print "<p style=\"text-align: right\"><input type=submit name=submitelementdisplayform value=\"" . _pageworks_SAVE_BUTTON . "\"></p>\n";
	}
	print "</form>";
}
if(strstr($pagedata[0]['page_template'], "displayButton(")) {
	include_once XOOPS_ROOT_PATH . "/modules/pageworks/include/displayButton_Javascript.php";
}

// log the viewing of this page... added July 8 2005
$udt = getUserDateTime();
$page_id = ltrim($page_id, "0");
writeLogEntry($page_id, $udt['uid'], $udt['date'], $udt['time']);

} // end of blank page condition...show footer

if(!$page AND !$_GET['block']) {
	include XOOPS_ROOT_PATH."/footer.php";
}

//$time_end = microtime_float2();
//$time = $time_end - $time_start;
//if($xoopsUser->getVar('uid') == 1) {
//echo "Execution time is <b>$time</b> seconds\n";
//}



?>