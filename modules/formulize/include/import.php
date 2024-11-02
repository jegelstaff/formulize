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
$confType = defined('XOOPS_CONF_USER') ? XOOPS_CONF_USER : ICMS_CONF_USER;
$xoopsConfigUser =& $config_handler->getConfigsByCat($confType);


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
	$validateOverride = $_POST['validatedata'] == 0 ? true : false;
	importCsv(array($_FILES['csv_name']['name'], $csv_name), $regfid, $validateOverride, $_POST['pkColumn']);
	print "</td></tr>\n";
} else {

	// provide a blank template, and a blank update template
	// store the id_reqs and the filename in the DB for later reference in the case of the update template

	// determine if this is the profile form and if so, send special flag to template creation

	$cols1 = getAllColList($fid, "", $groups);
	$cols = array('entry_id');
	foreach($cols1[$fid] as $col) {
		$cols[] = $col['ele_id'];
	}
	$headers = getHeaders($cols, false); // false means we're sending element ids
	$template = $regfid == $fid ? "blankprofile" : "blank";
	$blank_template = prepExport($headers, $cols, "", "comma", "", $template, $fid);

	$pkOptions = "<option value='"._formulize_ENTRY_ID."'>"._formulize_ENTRY_ID."</option>\n";
	foreach($headers as $header) {
		$pkOptions .= "<option value='".str_replace('"', "#&quot;", $header)."'>$header</option>\n";
	}

	print "<tr><td class=head><p>" . _formulize_DE_IMPORT_STEP1 . "</p></td><td class=even>";
	print "<a href=$blank_template target=_blank>" . _formulize_DE_IMPORT_BLANK2 . "</a> <b>" . _formulize_DE_IMPORT_OR . "</b> <a href=\"".XOOPS_URL."/makecsv.php?fid=$fid&includeMetadata=1\">" . _formulize_DE_IMPORT_DATATEMP2 . "</a></td></tr>\n";
	print "<tr><td class=head><p>" . _formulize_DE_IMPORT_STEP2 . "</p></td>".
	"<td class=even><p><form method=\"post\" ENCTYPE=\"multipart/form-data\" accept-charset=\"UTF-8\"><input type=\"file\" name=\"csv_name\" size=\"40\" /><br>
	<input type=\"checkbox\" name=\"validatedata\" value=\"1\" checked>&nbsp;"._formulize_DE_IMPORT_VALIDATEDATA."<br>
	<input type=\"checkbox\" name=\"updatederived\" value=\"1\" checked>&nbsp;"._formulize_DE_IMPORT_UPDATEDERIVED."<br>
	<input type=\"checkbox\" name=\"sendnotifications\" value=\"1\" checked>&nbsp;"._formulize_DE_IMPORT_SENDNOTIFICATIONS."<br><br>
	"._formulize_DE_IMPORT_IDENTIFIER_COLUMN."<select name='pkColumn' onchange=\"usePkValidate()\">".$pkOptions."</select><br>
	<input type=\"checkbox\" name=\"usePkColumnAsEntryId\" value=\"1\" disabled>&nbsp;"._formulize_DE_IMPORT_USEPKASID."<br></p>
	<p><input type=\"submit\" value=\"" . _formulize_DE_IMPORT_GO . "\"></p>
	</form></td></tr>\n";

	print "<tr><td class=head><p>" . _formulize_DE_IMPORT_STEP3 . "</p></td><td class=even>" . _formulize_DE_IMPORT_INSTRUCTIONS;

	if($regfid == $fid) {
		print _formulize_DE_IMPORT_INSTNEWPROFILE;
	} else {
		print _formulize_DE_IMPORT_INSTNEW;
	}

	print _formulize_DE_IMPORT_INSTUPDATE . "</td></tr>\n";


}

print "</table>";
print "</td><td width=5%></td></tr></table>";
print "</center>

<script>
function usePkValidate() {
	if(document.getElementsByName('pkColumn')[0].value == '"._formulize_ENTRY_ID."') {
		document.getElementsByName('usePkColumnAsEntryId')[0].checked = false;
		document.getElementsByName('usePkColumnAsEntryId')[0].disabled = true;
	} else {
		document.getElementsByName('usePkColumnAsEntryId')[0].disabled = false;
	}
}
</script>

<style>
b {
font-weight: bold;
}
</style>

</body>";
print "</HTML>";
