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
	for (var i=0; i < formObj.popnewcols.options.length; i++) {
		if (formObj.popnewcols.options[i].selected) {
			if(start) {
				cols = formObj.popnewcols.options[i].value;
				start = 0;
			} else {
				cols = cols + "," + formObj.popnewcols.options[i].value;
			}
		}
	}
	if(cols) {
		window.opener.document.controls.newcols.value = cols;
		window.opener.document.controls.submit();
		window.self.close();
	}

	
}

-->
</script>


<?

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
	$fid = $_GET['fid'];
	$frid = $_GET['frid'];
	$temp_selectedCols = $_GET['cols'];
	$selectedCols = explode(",", $temp_selectedCols);
	$gperm_handler = &xoops_gethandler('groupperm');
	$member_handler =& xoops_gethandler('member');
	$groups = $xoopsUser ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS;
	$uid = $xoopsUser->getVar('uid');


	if(!$scheck = security_check($fid, "", $uid, "", $groups, $mid, $gperm_handler, "")) {
		print "<p>" . _NO_PERM . "</p>";
		exit;
	}

	// generate the $allcols list
/*	if($frid) {
		$fids[0] = $fid;
		$check_results = checkForLinks($frid, $fids, $fid, "", "", "", "", "", "", "0");
		$fids = $check_results['fids'];
		$sub_fids = $check_results['sub_fids'];
//		$all_fids = array_merge($sub_fids, $fids);
//		array_unique($all_fids);
//		foreach($all_fids as $this_fid) {
		foreach($fids as $this_fid) {
			$c = q("SELECT ele_id, ele_caption FROM " . $xoopsDB->prefix("form") . " WHERE id_form='$this_fid' ORDER BY ele_order");
			$cols[$this_fid] = $c;
		}
		foreach($sub_fids as $this_fid) {
			$c = q("SELECT ele_id, ele_caption FROM " . $xoopsDB->prefix("form") . " WHERE id_form='$this_fid' ORDER BY ele_order");
			$cols[$this_fid] = $c;
		}
	} else {
		$cols[$fid] = q("SELECT ele_id, ele_caption FROM " . $xoopsDB->prefix("form") . " WHERE id_form='$fid' ORDER BY ele_order");
	}
*/
	$cols = getAllColList($fid, $frid, $groups); // $groups indicates that we only want columns which are visible to the current user

	$numcols = 0;
	foreach($cols as $f=>$vs) {
		foreach($vs as $row=>$values) {
			if(!in_array($values['ele_id'], $usedvals)) { // exclude duplicates...the array is not uniqued above because we don't want to merge it an unique it since that throws things out of order.
				$usedvals[] = $values['ele_id'];
				$options[$numcols] = "<option value=" . $values['ele_id'];
				if(in_array($values['ele_id'], $selectedCols)) {
					$options[$numcols] .= " selected";
				}
				$options[$numcols] .= ">" . printSmart(trans($values['ele_caption']), 75) . "</option>";
				$numcols++;
			}
		}		
	}

	// set list size variable
	$size = $numcols;
	if($numcols>20) { $size = 20; }

	print "<HTML>";
	print "<head>";
	print "<title>" . _formulize_DE_PICKNEWCOLS . "</title>";

	print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"" . XOOPS_URL . "/xoops.css\" />\n";
	$themecss = xoops_getcss();
	$themecss = substr($themecss, 0, -6);
	$themecss .= ".css";
	print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"$themecss\" />\n";

	changeColJavascript();

print "</head>";
print "<body><center>"; 
print "<table width=100%><tr><td width=5%></td><td width=90%>";
	print "<form name=newcolform action=\"" . XOOPS_URL . "\" method=post>\n";

	print "<table class=outer><tr><th colspan=2>" . _formulize_DE_PICKNEWCOLS . "</th></tr>";
	print "<tr><td class=head>" . _formulize_DE_AVAILCOLS . "</td><td class=odd>";
	print "<SELECT name=popnewcols[] id=popnewcols size=$size multiple>";
	foreach($options as $option) {
		print $option . "\n";
	}
	print "</SELECT>\n</td></tr>\n";
	
	print "<tr><td class=head></td><td class=odd><input type=button name=newcolbutton value=\"" . _formulize_DE_CHANGECOLS . "\" onclick=\"javascript:updateCols(this.form);\"></input></td></tr>\n";

	print "</table>\n</form>";
print "</td><td width=5%></td></tr></table>";
print "</center></body>";
print "</HTML>";


?>

