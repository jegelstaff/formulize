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

// This file contains the logic for the import popup

// The other popups like this are handled by the following files:
// changecols.php
// advsearch.php
// changescope.php
// pickcalcs.php
// save.php

// all of those use javascript to communicate options back to the parent window that opened them.
// The import process probably does not need to communicate back to the parent window, but if so,
// consult those files to see how that has been done in the past.

require_once "../../../mainfile.php";

require_once "import_functions.php";

global $xoopsConfig;
// load the formulize language constants if they haven't been loaded already -- also other language constants for user registration
	if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
	} else {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
	}
	if ( file_exists(XOOPS_ROOT_PATH."/language/".$xoopsConfig['language']."/user.php") ) {
		include_once XOOPS_ROOT_PATH."/language/".$xoopsConfig['language']."/user.php";
	} else {
		include_once XOOPS_ROOT_PATH."/language/english/user.php";
	}



global $xoopsDB, $xoopsUser;

$config_handler =& xoops_gethandler('config');
$xoopsConfigUser =& $config_handler->getConfigsByCat(XOOPS_CONF_USER);


include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

	// Set some required variables
	$mid = getFormulizeModId();
	$fid="";
	if(!$fid = $_GET['fid']) {
		$fid = intval($_POST['fid']);
	}
	$frid = "";
	$frid = isset($_GET['frid']) ? intval($_GET['frid']) : "";
	$frid = isset($_POST['frid']) ? intval($_POST['frid']) : $frid;
	/*if(!$frid = $_GET['frid']) {
		$frid = $_POST['frid'];	
	}*/
	
	$gperm_handler = &xoops_gethandler('groupperm');
	$member_handler =& xoops_gethandler('member');
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
	$uid = $xoopsUser->getVar('uid');

	// additional check to see if the user has import_data permission for this form
	if(!$scheck = security_check($fid, "", $uid, "", $groups, $mid, $gperm_handler) OR !$import_data = $gperm_handler->checkRight("import_data", $fid, $groups, $mid)) {
		print "<p>" . _NO_PERM . "</p>";
		exit;
	}


// main body of page and logic goes here...

// basic premise is that we have the $fid, and that is the form that we are importing data into.
// We need a browse box that the user can use to select the .csv they have prepared, and then when 
// they click the submit button to upload that file, presto, the import process begins.  If there
// are parse errors on the file, the import process communicates them.  If the parse is successful, 
// the import begins and the user gets a message, maybe a report of the number of records entered into
// the DB, or whatever seems appropriate.  Then there is a button to close the window.

// This popup window can be reloaded and receive form submissions in it just like any other window, of 
// course.  It's essentially a compartmentalized extension of the main "list of entries" UI.


print "<HTML>";
print "<head>";
print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset="._CHARSET."\" />";
print "<title>" . _formulize_DE_IMPORTDATA . "</title>\n";

print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"" . XOOPS_URL . "/xoops.css\" />\n";
$themecss = xoops_getcss();
//$themecss = substr($themecss, 0, -6);
//$themecss .= ".css";
print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"$themecss\" />\n";

print "</head>";
print "<body style=\"background: white; margin-top:20px;\"><center>"; 
print "<table width=100%><tr><td width=5%></td><td width=90%>";

print "<table id='import-instructions' class='outer popup'><tr><th colspan=2>" . _formulize_DE_IMPORT . "</th></tr>";

define("IMPORT_WRITE", true);
define("IMPORT_DEBUG", false);

//define("IMPORT_WRITE", false);
//define("IMPORT_DEBUG", true);

$errors = array();

// get id of profile form
$module_handler =& xoops_gethandler('module');
$formulizeModule =& $module_handler->getByDirname("formulize");
$formulizeConfig =& $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
$regfid = $formulizeConfig['profileForm'];

// Test if the filename of the temporary uploaded csv is empty
//$csv_name = @$_POST["csv_name"];
$csv_name = @$_FILES['csv_name']['tmp_name'];
if($csv_name != "")
{
	//$csv_name = "../import/$csv_name.csv";
	print "<tr><td class=head><p>" . _formulize_DE_IMPORT_RESULTS . "</p></td><td class=odd>\n";
	
	// check for this file in the list of valid imports, and if necessary, pass the valid id_reqs along with it
	$filenameparts = explode("_", $_FILES['csv_name']['name']);
	if(isset($filenameparts[1])) {
		$fileid = substr($filenameparts[1], 0, -4);
		$filesql = "SELECT id_reqs FROM " . $xoopsDB->prefix("formulize_valid_imports") . " WHERE file='" . floatval($fileid)."'";
		$fileres = $xoopsDB->query($filesql);
		$filerow = $xoopsDB->fetchRow($fileres);
		if($filerow[0]) {
			$id_reqs = unserialize($filerow[0]);
		} else {
			$id_reqs = false;
		}
	} else {
		$id_reqs = false;
	}
	$validateOverride = $_POST['validatedata'] == 0 ? true : false;
	importCsv(array($_FILES['csv_name']['name'], $csv_name), $id_reqs, $regfid, $validateOverride);
	print "</td></tr>\n";
}
elseif(!strstr(getCurrentURL(), 'dara.daniels')) 
{

print "<tr><td class=head><p>" . _formulize_DE_IMPORT_STEP1 . "</p></td><td class=even>";

// provide a blank template, and a blank update template
// store the id_reqs and the filename in the DB for later reference in the case of the update template

// determine if this is the profile form and if so, send special flag to template creation

$cols1 = getAllColList($fid, "", $groups);
$cols = array();
foreach($cols1[$fid] as $col) {
	$cols[] = $col['ele_id'];
}
$headers = getHeaders($cols, false); // false means we're sending element ids
$template = $regfid == $fid ? "blankprofile" : "blank";
$blank_template = prepExport($headers, $cols, "", "comma", "", "", $template, $fid);

print "<p><b>" . _formulize_DE_IMPORT_EITHEROR . "</b><p>";

print "<ul><li>" . _formulize_DE_IMPORT_BLANK . "<br><a href=$blank_template target=_blank>" . _formulize_DE_IMPORT_BLANK2 . "</a></li></ul>\n";
print "<Center><p><b>" . _formulize_DE_IMPORT_OR . "</b></p></center>";
print "<ul><li>" . _formulize_DE_IMPORT_DATATEMP . "<br><a href=\"\" onclick=\"javascript:window.opener.showPop('" . XOOPS_URL . "/modules/formulize/include/export.php?fid=$fid&frid=&cols=".strip_tags(htmlspecialchars($_GET['cols']))."&eq=".intval($_GET['eq'])."&type=update');return false;\">" . _formulize_DE_IMPORT_DATATEMP2 . "</a>";
print "</li></ul></td></tr>\n";
print "<tr><td class=head><p>" . _formulize_DE_IMPORT_STEP2 . "</p></td><td class=even>" . _formulize_DE_IMPORT_INSTRUCTIONS;

if($regfid == $fid) { 
	print _formulize_DE_IMPORT_INSTNEWPROFILE;
} else {
	print _formulize_DE_IMPORT_INSTNEW;
}

print _formulize_DE_IMPORT_INSTUPDATE . "</td></tr>\n";
print "<tr><td class=head><p>" . _formulize_DE_IMPORT_STEP3 . "</p></td>".
"<td class=even><p>" . _formulize_DE_IMPORT_FILE . ": <form method=\"post\" ENCTYPE=\"multipart/form-data\"><input type=\"file\" name=\"csv_name\" size=\"40\" /><br>
<input type=\"checkbox\" name=\"validatedata\" value=\"1\" checked>&nbsp;"._formulize_DE_IMPORT_VALIDATEDATA."<br>
<input type=\"checkbox\" name=\"updatederived\" value=\"1\" checked>&nbsp;"._formulize_DE_IMPORT_UPDATEDERIVED."<br>
<input type=\"checkbox\" name=\"sendnotifications\" value=\"1\" checked>&nbsp;"._formulize_DE_IMPORT_SENDNOTIFICATIONS."</p><p><input type=\"submit\" value=\"" . _formulize_DE_IMPORT_GO . "\"></p>
</form></td></tr>\n";

} else {
    print "<tr><td class=even><p>" . _formulize_DE_IMPORT_FILE . ": <form method=\"post\" ENCTYPE=\"multipart/form-data\"><input type=\"file\" name=\"csv_name\" size=\"40\" /><br><input type=\"hidden\" name=\"validatedata\" value=\"1\">&nbsp;</p><p><input type=\"submit\" value=\"" . _formulize_DE_IMPORT_GO . "\"></form></p></td></tr>\n";
}

print "</table>";
print "</td><td width=5%></td></tr></table>";
print "</center></body>";
print "</HTML>";
