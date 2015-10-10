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
##  Author of this file: Freeform Solutions                                  ##
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
    var colboxes = document.getElementsByClassName('colbox');
    for(var i=0; colboxes[i]; ++i) {
        if (colboxes[i].checked) {
            if (start) {
                cols = colboxes[i].value;
                start = 0;
            } else {
                cols = cols +','+colboxes[i].value;
            }
        }
    }

    if (cols) {
        window.opener.document.controls.newcols.value = cols;
        window.opener.showLoading();
        window.self.close();
    }
}

function toggleAllCols() {
    currentStyle = document.getElementById('allcols').style.display;
    if (currentStyle == 'none') { 
        document.getElementById('allcols').style.display = 'block';
    } else {
        document.getElementById('allcols').style.display = 'none';
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

if (!$scheck = security_check($fid, "", $uid, "", $groups, $mid, $gperm_handler)) {
    print "<p>" . _NO_PERM . "</p>";
    exit;
}

$defaultCols = getDefaultCols($fid, $frid); // returns ele_handles
$cols = getAllColList($fid, $frid, $groups); // $groups indicates that we only want columns which are visible to the current user

// handle metadata columns


$options['entry_id'] = _formulize_ENTRY_ID;
$options['creation_uid'] = _formulize_DE_CALC_CREATOR;
$options['mod_uid'] = _formulize_DE_CALC_MODIFIER;
$options['creation_datetime'] = _formulize_DE_CALC_CREATEDATE;
$options['mod_datetime'] = _formulize_DE_CALC_MODDATE;
$options['creator_email'] = _formulize_DE_CALC_CREATOR_EMAIL;

$usedvals = array();
foreach($cols as $f=>$vs) {
    foreach($vs as $row=>$values) {
        if (!in_array($values['ele_id'], $usedvals)) {
            $usedvals[] = $values['ele_handle'];
            $options[$values['ele_handle']] = $values['ele_coldhead'] != "" ? printSmart(trans($values['ele_colhead']), 75) : printSmart(trans(strip_tags($values['ele_caption'])), 75);
        }
    }
}

print "<HTML>";
print "<head>";
print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset="._CHARSET."\" />";
print "<title>" . _formulize_DE_PICKNEWCOLS . "</title>";

print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"" . XOOPS_URL . "/xoops.css\" />\n";
$themecss = xoops_getcss();
print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"$themecss\" />\n";

changeColJavascript();

print "</head>";
print "<body style=\"background: white; margin-top:20px;\"><center>";
print "<table style=\"width: 100%;\"><tr><td style=\"width: 5%;\"></td><td style=\"width: 90%;\">";
print "<form name=newcolform action=\"" . XOOPS_URL . "\" method=post>\n";

print "<table class=outer><tr><th colspan=2>" . _formulize_DE_PICKNEWCOLS . "</th></tr>";
print "<tr><td class=head>" . _formulize_DE_AVAILCOLS . "</td><td class=even>";

$counter = 1;
// add in the default columns first and they'll be visible
foreach($options as $value=>$option) {
    if(in_array($value, $defaultCols)) {
        $selected = in_array($value, $selectedCols) ? "checked='checked'" : "";
        print "<label><input type='checkbox' name='popnewcols[]' id='popnewcols".$counter."' class='colbox' value=\"$value\" $selected />&nbsp;&nbsp;&nbsp;$option</label><br />\n";
        $counter++;
        unset($options[$value]);
    }
}
// add in the rest of the columns second, they'll be invisible
if(count($options) > 0) {
    print "<p><a onclick='javascript:toggleAllCols();return false;'>"._formulize_DE_SHOWALLCOLS."</a></p>";
    print "<div id='allcols' style='display:none;'>";
    foreach($options as $value=>$option) {
        $selected = in_array($value, $selectedCols) ? "checked='checked'" : "";
        print "<label><input type='checkbox' name='popnewcols[]' id='popnewcols".$counter."' class='colbox' value=\"$value\" $selected />&nbsp;&nbsp;&nbsp;$option</label><br />\n";
        $counter++;
    }
    print "</div>";
}

print "</td></tr>\n";

print "<tr><td class=head></td><td class=even><input type=button name=newcolbutton value=\"" . _formulize_DE_CHANGECOLS . "\" onclick=\"javascript:updateCols(this.form);\"></input></td></tr>\n";
print "</table>\n</form>";
print "</td><td style=\"width: 5%;\"></td></tr></table>\n";
print "</center></body>\n";
print "</HTML>\n";
