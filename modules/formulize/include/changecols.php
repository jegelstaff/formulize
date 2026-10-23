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
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/popup_functions.php';

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
    print formulize_noPermissionMessage();
    exit;
}

$cols = getAllColList($fid, $frid, $groups); // $groups indicates that we only want columns which are visible to the current user

ob_start();
changeColJavascript();
formulize_popupHeader(_formulize_DE_PICKNEWCOLS, ob_get_clean());

print "<form name='newcolform' action=\"" . XOOPS_URL . "\" method='post' class='fz-card'>\n";
print "<h1 class='fz-card__title'>" . _formulize_DE_PICKNEWCOLS . "</h1>\n";
// the buttons stay in view while the list of columns, which can be long, scrolls under them
print "<div class='fz-toolbar fz-bg-surface fz-py-3 fz-border-b' style='position: sticky; top: 0; z-index: 1;'>
<div class='fz-toolbar__start'>
<input type='button' class='fz-btn fz-btn--primary' name='newcolbutton' value=\"" . _formulize_DE_CHANGECOLS . "\" onclick=\"javascript:updateCols(this.form);\" />
</div>
<div class='fz-toolbar__end'>
<input type='button' class='fz-btn' name='clearall' value='". _formulize_DE_CLEAR_ALL."'
onclick=\"var boxes = document.getElementsByClassName('colbox');for(var i=0;i<boxes.length;i++){boxes[i].checked = false;}\" />
<input type='reset' class='fz-btn' name='reset' value='". _formulize_DE_RESET_COLS."' />
</div>
</div>\n";

print generateTidyElementList($fid, $cols, $selectedCols);

print "</form>\n";

formulize_popupFooter();
