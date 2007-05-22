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

// this file contains the logic for the change scope popup.

// 1. draw box of available groups
// 2. send selection back to parent window (after building string to do so, commas at beginning and end and in between group ids)
// 3. 

function scopeJavascript() {
?>
<script type='text/javascript'>
<!--

function updateScope(formObj) {

	var grps;
	var start=1;
	for (var i=0; i < formObj.elements[0].options.length; i++) {
		if (formObj.elements[0].options[i].selected) {
			if(start) {
				grps = "," + formObj.elements[0].options[i].value + ",";
				start = 0;
			} else {
				grps = grps + formObj.elements[0].options[i].value + ",";
			}
		}
	}
	if(grps) {
		window.opener.document.controls.advscope.value = grps;
		window.opener.document.controls.lockcontrols.value = 0;
		window.opener.showLoading();
		window.self.close();
	} else {
		alert("<?php print _formulize_DE_NOGROUPSPICKED; ?>");
	}

	
}
-->
</script>

<?php
}


require_once "../../../mainfile.php";

global $xoopsConfig;
// load the formulize language constants if they haven't been loaded already
	if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
	} else {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
	}


global $xoopsDB, $xoopsUser;
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

	// Set some required variables
	$mid = getFormulizeModId();
	$fid = ((isset( $_GET['fid'])) AND is_numeric( $_GET['fid'])) ? intval( $_GET['fid']) : "" ;
  $fid = ((isset($_POST['fid'])) AND is_numeric($_POST['fid'])) ? intval($_POST['fid']) : $fid ;

  $frid = ((isset( $_GET['frid'])) AND is_numeric( $_GET['frid'])) ? intval( $_GET['frid']) : "" ;
  $frid = ((isset($_POST['frid'])) AND is_numeric($_POST['frid'])) ? intval($_POST['frid']) : $frid ;

	$gperm_handler = &xoops_gethandler('groupperm');
	$member_handler =& xoops_gethandler('member');
	$groups = $xoopsUser ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS;
	$uid = $xoopsUser->getVar('uid');
	$curscope = $_GET['scope'];

	if(!$scheck = security_check($fid, "", $uid, "", $groups, $mid, $gperm_handler, "")) {
		print "<p>" . _NO_PERM . "</p>";
		exit;
	}

	//get the current scope, if any
	if(strstr($curscope, ",")) {
		$trimmed = trim($curscope, ",");
		if(strstr($trimmed, ",")) {
			$curgroups = explode(",", $trimmed);
		} else {
			$curgroups[0] = $trimmed;
		}
	}

// main body of page goes here...

// check for groupscope and globalscope
if($globalscope = $gperm_handler->checkRight("view_globalscope", $fid, $groups, $mid)) { // get all groups
	// need to make option array with values as gids and text as names of groups
	$allgroups =& $member_handler->getGroups();
	for($i=0;$i<count($allgroups);$i++) {
		if($can_add = $gperm_handler->checkRight("add_own_entry", $fid, $allgroups[$i]->getVar('groupid'), $mid)) {
			$availgroups[$allgroups[$i]->getVar('groupid')] = $allgroups[$i]->getVar('name');
		}
	}
} elseif($groupscope = $gperm_handler->checkRight("view_groupscope", $fid, $groups, $mid)) { // get all groups the groups the user is a member of (except registered users)
	for($i=0;$i<count($groups);$i++) {
		$thisgroup =& $member_handler->getGroup($groups[$i]);
		if($can_add = $gperm_handler->checkRight("add_own_entry", $fid, $groups[$i], $mid)) {
			$availgroups[$groups[$i]] = $thisgroup->getVar('name');
		}
	}
} else {
	exit("Error: no advanced scope permission detected.");
}

include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";

print "<HTML>";
print "<head>";
print "<title>" . _formulize_DE_PICKASCOPE . "</title>\n";

scopeJavascript();

print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"" . XOOPS_URL . "/xoops.css\" />\n";
$themecss = xoops_getcss();
//$themecss = substr($themecss, 0, -6);
//$themecss .= ".css";
print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"$themecss\" />\n";

print "</head>\n";
print "<body><center>"; 
print "<table style=\"width: 100%;\"><tr><td style=\"width: 5%;\"></td><td style=\"width: 90%;\">";
$advscope = new xoopsThemeForm(_formulize_DE_PICKASCOPE, 'advscope', XOOPS_URL."/modules/formulize/include/advscope.php?fid=$fid&frid=$frid");

$gcount = count($availgroups);
$size = ($gcount<10) ? $gcount : 10 ;
$grouplist = new xoopsFormSelect(_formulize_DE_AVAILGROUPS, 'newscope', $curgroups, $size, true);
$grouplist->addOptionArray($availgroups);

$doneButton = new xoopsFormButton('', 'done', _formulize_DE_USETHISSCOPE, 'button');
$doneButton->setExtra("onclick=\"javascript:updateScope(this.form);return false;\"");

$advscope->addElement($grouplist);
$advscope->addElement($doneButton);

print $advscope->render();

print "</td><td style=\"width: 5%;\"></td></tr></table>\n";
print "</center></body>\n";
print "</HTML>";


?>