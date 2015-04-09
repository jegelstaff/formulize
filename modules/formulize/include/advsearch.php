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

// This file contains the logic for the advanced search options popup

//stuff that needs to be recorded for sending back to the main controls interface...
//1.
//2.

function searchJavascript($items) {
?>

<script type='text/javascript'>
<!--

function sendSearch(formObj) {

<?php
// process the $items array

$flatItems = implode("/,%^&2", $items);
print "window.opener.document.controls.asearch.value = '$flatItems';\n";
print "window.opener.showLoading();\n";
print "window.self.close();\n";
?>
}

-->
</script>


<?php

}

//this function reads the query items passed from previous page
function readQueryItems() {
    foreach($_REQUEST as $k=>$v) { // look through get and post
        if(substr($k, 0, 3) == "as_") {
            $items[$k] = $v;
            $v = str_replace("'", "&#39;", $v);
            $hidden[] = new xoopsFormHidden($k, stripslashes($v));
        }
    }
    $count = count($items);
    // read what was just sent back...
    if($_POST['addq']) {
        $columnsProcessed = false;
        foreach($_POST['column'] AS $selectedColumn) {
            if($columnsProcessed) { // handle AND/OR setting
                switch($_POST['multi_andor']) {
                    case "1": // AND
                        $items['as_' . $count] = "AND";
                        $hidden[] = new xoopsFormHidden('as_' . $count, "AND");
                        $count++;
                        break;
                    case "2": // OR
                        $items['as_' . $count] = "OR";
                        $hidden[] = new xoopsFormHidden('as_' . $count, "OR");
                        $count++;
                        break;
                }
            }
                $items['as_' . $count] = "[field]" . $selectedColumn . "[/field]";
            $hidden[] = new xoopsFormHidden('as_' . $count, "[field]" . $selectedColumn . "[/field]");
            $count++;
            $items['as_' . $count] = $_POST['op'];
            $hidden[] = new xoopsFormHidden('as_' . $count, $_POST['op']);
            $count++;
            $items['as_' . $count] = $_POST['term'];
            $thisterm = str_replace("'", "&#39;", $_POST['term']);
            $hidden[] = new xoopsFormHidden('as_' . $count, stripslashes($thisterm));
            $count++;
            $columnsProcessed = true;
        }
    }
    if($_POST['openb']) {
        $items['as_' . $count] = "(";
        $hidden[] = new xoopsFormHidden('as_' . $count, "(");
    }
    if($_POST['closeb']) {
        $items['as_' . $count] = ")";
        $hidden[] = new xoopsFormHidden('as_' . $count, ")");
    }
    if($_POST['and']) {
        $items['as_' . $count] = "AND";
        $hidden[] = new xoopsFormHidden('as_' . $count, "AND");
    }
    if($_POST['or']) {
        $items['as_' . $count] = "OR";
        $hidden[] = new xoopsFormHidden('as_' . $count, "OR");
    }
    if($_POST['not']) {
        $items['as_' . $count] = "NOT";
        $hidden[] = new xoopsFormHidden('as_' . $count, "NOT");
    }

    $to_return[0] = $items;
    $to_return[1] = $hidden;
    return $to_return;
}

// delete calculations from request list
function handleDelete($items, $hidden) {
    if($_POST['remove']) {
        $count = count($items);
        $checkField = $count-3;
        if(substr($items['as_' . $checkField], 0, 7) == "[field]" AND substr($items['as_' . $checkField], -8) == "[/field]") {
            for($i=0;$i<3;$i++) {
                array_pop($items);
                array_pop($hidden);
            }
        } else {
            array_pop($items);
            array_pop($hidden);
        }
    }
    $to_return[0] = $items;
    $to_return[1] = $hidden;
    return $to_return;
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
$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;

if(!$scheck = security_check($fid, "", $uid, "", $groups, $mid, $gperm_handler)) {
    print "<p>" . _NO_PERM . "</p>";
    exit;
}

// main body of page goes here...
include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";

$returned = readQueryItems();

$cols = getAllColList($fid, $frid, $groups);

$returned = handleDelete($returned[0], $returned[1]); // returns 1 if a deletion was made, 0 if not.
$items = $returned[0];
$hidden = $returned[1];

foreach($cols as $f=>$vs) {
    foreach($vs as $row=>$values) {
        $reqdcol = 'reqdcalc_column_' . $values['ele_id'];
        if(!in_array($values['ele_id'], $usedvals)) { // exclude duplicates...the array is not uniqued above because we don't want to merge it an unique it since that throws things out of order.
            $usedvals[] = $values['ele_id'];
            if($values['ele_colhead'] != "") {
                $options[$values['ele_id']] = printSmart(trans($values['ele_colhead']), 60);
            } else {
                $options[$values['ele_id']] = printSmart(trans(strip_tags($values['ele_caption'])), 60);
            }
        }
    }
}

print "<HTML>";
print "<head>";
print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset="._CHARSET."\" />";
print "<title>" . _formulize_DE_BUILDQUERY . "</title>\n";

searchJavascript($items);

print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"" . XOOPS_URL . "/xoops.css\" />\n";
$themecss = xoops_getcss();
//$themecss = substr($themecss, 0, -6);
//$themecss .= ".css";
print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"$themecss\" />\n";

print "</head>";
print "<body style=\"background: white; margin-top:20px;\"><center>";
print "<table style=\"width: 100%;\"><tr><td style=\"width: 5%;\"></td><td style=\"width: 90%;\">";
$advsearch = new xoopsThemeForm(_formulize_DE_BUILDQUERY, 'buildq', XOOPS_URL."/modules/formulize/include/advsearch.php?fid=$fid&frid=$frid");

//$returned = addReqdCalcs($pickcalc);
//$pickcalc = $returned['form'];

$columns_andor = new xoopsFormElementTray('', "<br />");
$columns_and = new xoopsFormRadio('', 'multi_andor', '1');
$columns_and->addOption(1, _formulize_DE_AS_MULTI_AND);
$columns_andor->addElement($columns_and);
$columns_or = new xoopsFormRadio('', 'multi_andor', '1');
$columns_or->addOption(2, _formulize_DE_AS_MULTI_OR);
$columns_andor->addElement($columns_or);

$columns = new xoopsFormSelect(_formulize_DE_AS_FIELD . "<br /><br />" . $columns_andor->render(), 'column', '', 5, true);
$columns->addOption("creation_uid", _formulize_DE_CALC_CREATOR);
$columns->addOption("mod_uid", _formulize_DE_CALC_MODIFIER);
$columns->addOption("creation_datetime", _formulize_DE_CALC_CREATEDATE . ' (YYYY-mm-dd)');
$columns->addOption("mod_datetime", _formulize_DE_CALC_MODDATE . ' (YYYY-mm-dd)');
$columns->addOption("creator_email", _formulize_DE_CALC_CREATOR_EMAIL);
$columns->addOptionArray($options);

$opterm = new xoopsFormElementTray(_formulize_DE_AS_OPTERM, "&nbsp;&nbsp;");
$op = new xoopsFormSelect('', 'op');
$ops['=='] = "=";
$ops['!='] = "NOT";
$ops['>'] = ">";
$ops['<'] = "<";
$ops['>='] = ">=";
$ops['<='] = "<=";
$ops['LIKE'] = "LIKE";
$ops['NOT LIKE'] = "NOT LIKE";
$op->addOptionArray($ops);
$term = new xoopsFormText('', 'term', 20, 255);
$opterm->addElement($op);
$opterm->addElement($term);

$addButton = new xoopsFormButton('', 'addq', _formulize_DE_AS_ADD, 'submit');

$addOtherTray = new xoopsFormElementTray(_formulize_DE_AS_ADDOTHER, "&nbsp;&nbsp;");
$openBracketButton = new xoopsFormButton('', 'openb', "(", 'submit');
$closeBracketButton = new xoopsFormButton('', 'closeb', ")", 'submit');
$andButton = new xoopsFormButton('', 'and', "AND", 'submit');
$orButton = new xoopsFormButton('', 'or', "OR", 'submit');
$notButton = new xoopsFormButton('', 'not', "NOT", 'submit');
$addOtherTray->addElement($andButton);
$addOtherTray->addElement($orButton);
$addOtherTray->addElement($notButton);
$addOtherTray->addElement($openBracketButton);
$addOtherTray->addElement($closeBracketButton);

// add hidden items...
foreach($hidden as $oneHidden) {
    $advsearch->addElement($oneHidden);
    unset($oneHidden);
}

$advsearch->insertBreak("<div style=\"font-weight: normal;\">" . _formulize_DE_AS_DEPRECATED . "</div>", "head"); // advanced search officially deprecated with work on version 3.1
$advsearch->addElement($opterm);
$advsearch->addElement($columns); // order change April 4 2007
$advsearch->addElement($addButton);
$advsearch->addElement($addOtherTray);

if($items['as_0']) {
    $removeButton = new xoopsFormButton('', 'remove', _formulize_DE_AS_REMOVE, 'submit');
    $doneButton = new xoopsFormButton('', 'done', _formulize_DE_SEARCHGO, 'button');
    $doneButton->setExtra("onclick=\"javascript:sendSearch(this.form);return false;\"");
    $advsearch->insertBreak("</td></tr></table><table class=outer><tr><th colspan=2>" . _formulize_DE_AS_QUERYSOFAR . "</th></tr><tr><td class=even colspan=2><center>" . $doneButton->render() . "</center>", "");
    $qstring = writableQuery($items, 1); // 1 flag indicates to not translate special terms
    $advsearch->insertBreak("<p>$qstring" . "<br />" . $removeButton->render() . "</p>", 'head');
}

print $advsearch->render();

print "</td><td style=\"width: 5%;\"></td></tr></table>";
print "</center></body>";
print "</HTML>";
