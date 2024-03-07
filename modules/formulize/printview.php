<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
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
##  Author of this file: Freeform Solutions and NS Tai (aka tuff) and others ##
##  URL: http://www.brandycoke.com/                                          ##
##  Project: Formulize                                                       ##
###############################################################################

if(isset($_POST['makepdf'])) {
    include "printviewpdf.php";
    return;
}

require_once "../../mainfile.php";
include XOOPS_ROOT_PATH.'/header.php';

include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

global $icmsConfig, $icmsTheme, $xoopsUser;

$module_handler =& xoops_gethandler('module');
$config_handler =& xoops_gethandler('config');
$formulizeModule =& $module_handler->getByDirname("formulize");
$formulizeConfig =& $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
/*$modulePrefUseToken = $formulizeConfig['useToken'];
// screen type for regular forms doesn't yet exist, but when it does, this check will be relevant
$useToken = $screen ? $screen->getVar('useToken') : $modulePrefUseToken;
// avoid security check for versions of XOOPS that don't have that feature, or for when it's turned off
if (isset($GLOBALS['xoopsSecurity']) AND $useToken) {
    if (!$GLOBALS['xoopsSecurity']->check()) {
      print "<b>Error: it appears you should not be viewing this page.  Please contact the webmaster for assistance.</b>";
        return false;
    }
}*/

if(!$makingPDF) {

print "<HTML>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset="._CHARSET."\" />";
print "<HEAD>";

if (!$formulizeConfig['printviewStylesheets']) {
    print "<link rel='stylesheet' type='text/css' media='all' href='".getcss($xoopsConfig['theme_set'])."'>\n";

    // figure out if this is XOOPS or ICMS
    if (file_exists(XOOPS_ROOT_PATH."/class/icmsform/index.html")) {
        print "<link rel=\"stylesheet\" media=\"screen\" href=\"".XOOPS_URL."/icms.css\" type=\"text/css\" />\n";
    }
    print <<<EOF
<style>
body {
    font-size: 9pt;
}
h2, th {
    font-weight: normal;
    color: #333;
}
.subform-caption b {
    font-weight: normal;
}
p.subform-caption {
    display: none;
}
.formulize-subform-title {
    display: none;
}
.formulize-subform-heading h2 {
    font-weight: normal;
    margin-bottom: 0;
}
.caption-text, #formulize-printpreview-pagetitle {
    font-weight: normal;
    color: #333;
}
td.head div.xoops-form-element-help {
    color: #ddd;
    font-size: 8pt;
    font-weight: normal;
}
td.head {
    width: 30%;
    background-color: white;
}
td.even, td.odd {
    width: 70%;
    border-left: 1px solid #bbb;
}
table.outer table.outer {
    width: 100%;
}
td div.xo-theme-form {
    padding: 0;
}
div.xo-theme-form > table.outer > tbody > tr > td {
    border-bottom: 1px solid #bbb;
}
table.outer {
    border: 1px solid #bbb;
}
table.outer table.outer {
    border-left: none;
    border-right: none;
    border-bottom: none;
}
.formulize-element-edit-link {
    display: none;
}
</style>
EOF;
} else {
    foreach(explode(',', $formulizeConfig['printviewStylesheets']) as $styleSheet) {
        $styleSheet = substr(trim($styleSheet),0,4) == 'http' ? trim($styleSheet) : XOOPS_URL.trim($styleSheet);
        print "<link rel='stylesheet' type='text/css' media='all' href='$styleSheet' />\n";
    }
}
print "</HEAD>";

} // end of if we're making a PDF - leave out the head and stuff

$formframe      = $_POST['formframe'];
$ventry         = $_POST['lastentry'];
$mainform       = $_POST['mainform'];
$ele_allowed    = $_POST['elements_allowed'];
$screenid       = $_POST['screenid'];
$currentPage    = $_POST['currentpage'];

$fid = $mainform ? $mainform : $formframe;
$frid = $mainform ? $formframe : '';
if(security_check($fid, $ventry) == false){
    exit("Error: you do not have permission to view this entry or form");
}

$titleOverride = "";

// only present when a specific page in a multipage is requested (or in future, a list of elements from a form screen)
if ($ele_allowed) {
    $elements_allowed = explode(",",$ele_allowed);
    $formframetemp = $formframe;
    unset($formframe);
    $formframe['formframe'] = $formframetemp;
    $formframe['elements'] = $elements_allowed;
    // if there's a currentPage, then use that page title from the screen (currentpage is only present if there's a screen id)
    if ($currentPage) {
        $screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
        $multiPageScreen = $screen_handler->get($screenid);
        $pageTitles = $multiPageScreen->getVar('pagetitles');
        $titleOverride = $pageTitles[$currentPage-1];
    }
}

// no element list passed in, but there is a screen id, so assume a multipage form
if (! is_array($formframe) && $screenid && !$ele_allowed) {
    $screen_handler =& xoops_getmodulehandler('screen', 'formulize');
    $screen = $screen_handler->get($screenid);
    $screen_type = $screen->getVar('type');

    if ($screen_type == 'multiPage') {
        $screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
        $multiPageScreen = $screen_handler->get($screenid);
        $conditions = $multiPageScreen->getConditions();
        $pages = $multiPageScreen->getVar('pages');

        $elements = array();
        $printed_elements = array();
        foreach ($pages as $currentPage=>$page) {
            if(pageMeetsConditions($conditions, $currentPage+1, $ventry, $fid, $frid)) {
                foreach ($page as $element) {
                    // on a multipage form, some elements such a persons name may repeat on each page. print only once
                    if (!isset($printed_elements[$element])) {
                        $elements[] = $element;
                        $printed_elements[$element] = true;
                    }
                }
            }
        }

        $formframetemp = $formframe;
        unset($formframe);
        $formframe['elements'] = $elements;
        $formframe['formframe'] = $formframetemp;
        $pages = $multiPageScreen->getVar('pages');
        $pagetitles = $multiPageScreen->getVar('pagetitles');
        ksort($pages); // make sure the arrays are sorted by key, ie: page number
        ksort($pagetitles);
        // convention dictates that the page arrays start with [1] and not [0],
        //  for readability when manually using the API, so we bump up all the numbers by one by adding
        //  something to the front of the array
        array_unshift($pages, "");
        array_unshift($pagetitles, "");
        unset($pages[0]); // get rid of the part we just unshifted, so the page count is correct
        unset($pagetitles[0]);
        $formframe['pagetitles'] = $pagetitles;
        $formframe['pages'] = $pages;

        // use the screen title
        $titleOverride = $multiPageScreen->getVar('title');
    }
}


if(!$makingPDF) {

print "<center>";
print "<table width=100%><tr><td width=5%></td><td width=90%>";
print "<div id=\"formulize-printpreview\">";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php"; // needed to get the benchmark function available
// if it's a single and they don't have group or global scope
displayForm($formframe, $ventry, $mainform, "", "{NOBUTTON}", "", $titleOverride);
print "</div>";
print "</td><td width=5%></td></tr></table>";
    print "</center>";

    print "</body>";
print "</HTML>";

                    } else {

    include_once XOOPS_ROOT_PATH.'/modules/formulize/include/elementdisplay.php';
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    // simpler layout for PDF
    if($formframe['elements']) {
        print "<table cellpadding=\"10\"><tbody>";
        foreach($elements as $element) {
            if($elementObject = $element_handler->get($element)) {
                ob_start();
                $result = displayElement('', $element, $ventry);
                $value = ob_get_clean();
                if($result != 'not_allowed' AND $result != 'hidden') {
                    $caption = $element->getVar('ele_caption');
                    if($elementObject->getVar('ele_type') == 'ib') {
                        $caption = $value;
                        $value = "";
            }
                    print "<tr><td style width=\"400\">$caption</td><td>$value</td></tr>";
            }
            }
        }
        print "</tbody></table>";
    }


}
