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

// this file generates the change columns popup

// this function writes in the Javascript for changing columns
function changeColJavascript() {

?>

<script type='text/javascript'>
<!--

function updateCols(formObj) {

	var cols;
	var start=1;
	for (var i=0; i < formObj.elements[0].options.length; i++) {
		if (formObj.elements[0].options[i].selected) {
			if(start) {
				cols = formObj.elements[0].options[i].value;
				start = 0;
			} else {
				cols = cols + "," + formObj.elements[0].options[i].value;
			}
		}
	}
	
	if(cols) {
		window.opener.document.controls.newcols.value = cols;
		window.opener.showLoading();
		window.self.close();
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

	$temp_selectedCols = $_GET['cols'];
	$selectedCols = explode(",", $temp_selectedCols);
	$gperm_handler = &xoops_gethandler('groupperm');
	$member_handler =& xoops_gethandler('member');
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
	$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;


	if(!$scheck = security_check($fid, "", $uid, "", $groups, $mid, $gperm_handler)) {
		print "<p>" . _NO_PERM . "</p>";
		exit;
	}

	$cols = getAllColList($fid, $frid, $groups); // $groups indicates that we only want columns which are visible to the current user

	// handle metadata columns
	// UID
	$options[0] = "<option value=\"creation_uid\"";
	if(in_array("creation_uid", $selectedCols)) {
		$options[0] .= " selected";
	}
	$options[0] .= ">" . _formulize_DE_CALC_CREATOR . "</option>";
	// PROXYID
	$options[1] = "<option value=\"mod_uid\"";
	if(in_array("mod_uid", $selectedCols)) {
		$options[1] .= " selected";
	}
	$options[1] .= ">" . _formulize_DE_CALC_MODIFIER . "</option>";
	// CREATION_DATE
	$options[2] = "<option value=\"creation_datetime\"";
	if(in_array("creation_datetime", $selectedCols)) {
		$options[2] .= " selected";
	}
	$options[2] .= ">" . _formulize_DE_CALC_CREATEDATE . "</option>";
	// MOD_DATE
	$options[3] = "<option value=\"mod_datetime\"";
	if(in_array("mod_datetime", $selectedCols)) {
		$options[3] .= " selected";
	}
	$options[3] .= ">" . _formulize_DE_CALC_MODDATE . "</option>";
	// CREATOR EMAIL -- added September 24 2006
	$options[4] = "<option value=\"creator_email\"";
	if(in_array("creator_email", $selectedCols)) {
		$options[4] .= " selected";
	}
	$options[4] .= ">" . _formulize_DE_CALC_CREATOR_EMAIL . "</option>";

	$numcols = 5;
	foreach($cols as $f=>$vs) {
		foreach($vs as $row=>$values) {
			if(!in_array($values['ele_id'], $usedvals)) { // exclude duplicates...the array is not uniqued above because we don't want to merge it an unique it since that throws things out of order.
				$usedvals[] = $values['ele_handle'];
				$options[$numcols] = "<option value=" . $values['ele_handle'];
				if(in_array($values['ele_handle'], $selectedCols)) {
					$options[$numcols] .= " selected";
				}
				if($values['ele_colhead'] != "") {
					$options[$numcols] .= ">" . printSmart(trans($values['ele_colhead']), 75) . "</option>";
				} else {
					$options[$numcols] .= ">" . printSmart(trans(strip_tags($values['ele_caption'])), 75) . "</option>";
				}
				$numcols++;
			}
		}		
	}

	// set list size variable
	$size = $numcols;
	if($numcols>20) { $size = 20; }

	print "<HTML>";
	print "<head>";
	print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset="._CHARSET."\" />";
	print "<title>" . _formulize_DE_PICKNEWCOLS . "</title>";

	print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"" . XOOPS_URL . "/xoops.css\" />\n";
	$themecss = xoops_getcss();
	print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"$themecss\" />\n";

	changeColJavascript();

  print "</head>";
  print "<body><center>"; 
  print "<table style=\"width: 100%;\"><tr><td style=\"width: 5%;\"></td><td style=\"width: 90%;\">";
	print "<form name=newcolform action=\"" . XOOPS_URL . "\" method=post>\n";

	print "<table class=outer><tr><th colspan=2>" . _formulize_DE_PICKNEWCOLS . "</th></tr>";
	print "<tr><td class=head>" . _formulize_DE_AVAILCOLS . "</td><td class=even>";
	print "<SELECT name=popnewcols[] id=popnewcols size=$size multiple>";
	foreach($options as $option) {
		print $option . "\n";
	}
	print "</SELECT>\n</td></tr>\n";
	
	print "<tr><td class=head></td><td class=even><input type=button name=newcolbutton value=\"" . _formulize_DE_CHANGECOLS . "\" onclick=\"javascript:updateCols(this.form);\"></input></td></tr>\n";

	print "</table>\n</form>";
  print "</td><td style=\"width: 5%;\"></td></tr></table>\n";
  print "</center></body>\n";
  print "</HTML>\n";

?>

