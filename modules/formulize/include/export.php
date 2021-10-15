<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2008 Freeform Solutions                  ##
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

// this file generates the export popup

include_once "../../../mainfile.php";
global $xoopsConfig;
// load the formulize language constants if they haven't been loaded already
if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
    include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
} else {
    include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
}
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";

$fid = intval($_GET['fid']);
$frid = intval($_GET['frid']);

if (!isset($_POST['metachoice']) AND !isset($formulize_doingManualExport)) {
    print "<HTML>";
    print "<head>";
    print "<meta http-equiv='Content-Type' content='text/html;charset=utf-8' />\n";
    print "<title>" . _formulize_DE_EXPORT . "</title>\n";
    print "<script type='text/javascript' src='".XOOPS_URL."/modules/formulize/libraries/jquery/jquery-1.4.2.min.js'></script>\n";
    ?>
    <script type='text/javascript'>
        jQuery(window).load(function() {
            jQuery('#show_hide_advanced').click(function() {
                if (jQuery('#advanced_options').css('display') == 'none') {
                    jQuery('#advanced_options').animate({height:'toggle'}, 400);
                    jQuery(this).text('<?php print _formulize_DE_EXPORT_HIDE_ADVANCED; ?>');
                } else {
                    jQuery('#advanced_options').animate({height:'toggle'}, 400);
                    jQuery(this).text('<?php print _formulize_DE_EXPORT_SHOW_ADVANCED; ?>');
                }
                return false;
            });
            jQuery('[name=exportsubmit]').click(function() {
                jQuery(this).attr('disabled', 'disabled');
                jQuery('body').fadeTo(1, 0.25);
                jQuery('#metachoiceform').submit(); // some browsers won't complete the submit action natively after the button is disabled
                return false;
            })
        });
    </script>
    <?php
    print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"" . XOOPS_URL . "/xoops.css\" />\n";
    $themecss = xoops_getcss();
    print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"$themecss\" />\n";

    print "</head>";
    print "<body style=\"background: white; margin-top:20px;\"><center>";
    print "<table width=100%><tr><td width=5%></td><td width=90%>";
    print "<form id=\"metachoiceform\" name=\"metachoiceform\" action=\"".getCurrentURL() . "\" method=\"post\">\n";
    
    print "<table class='outer popup'><tr><th>"._formulize_DE_EXPORT_TITLE."</th></tr><tr><td>\n";

    if ($_GET['type'] == "update") {
        print "<p>"._formulize_DE_IMPORT_DATATEMP4." <a href=\"\" onclick=\"javascript:window.opener.showPop('" . XOOPS_URL . "/modules/formulize/include/import.php?fid=$fid&eq=".intval($_GET['eq'])."');return false;\">"._formulize_DE_IMPORT_DATATEMP5."</a></p>\n";
        print "<p>"._formulize_DE_IMPORT_DATATEMP3."</p>\n";
    }

    print "<center>\n";
    print "<p><input type=\"submit\" name=\"exportsubmit\" value=\""._formulize_DE_EXPORT_MAKEFILE."\"></p>\n";

    print "<p><a href='' id='show_hide_advanced'>"._formulize_DE_EXPORT_SHOW_ADVANCED."</a></p>";
    print "</center>\n";
    print "<div id='advanced_options' style='display: none; border:1px solid #aaa;border-radius:5px;padding:0 1em;margin-bottom:1em;'>";

    if (!isset($_GET['type'])) {
        print "<p><input type=\"radio\" name=\"metachoice\" value=\"1\">"._formulize_DB_EXPORT_METAYES."</input>\n<br>\n";
        print "<input type=\"radio\" name=\"metachoice\" value=\"0\" checked>"._formulize_DB_EXPORT_METANO."</input>\n</p>\n";
    } else {
        print "<input type=\"hidden\" name=\"metachoice\" value=\"0\">\n";
    }

    $module_handler = xoops_gethandler('module');
    $config_handler = xoops_gethandler('config');
    $formulizeModule = $module_handler->getByDirname("formulize");
    $formulizeConfig = $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
    $excelChecked = $formulizeConfig['downloadDefaultToExcel'] == 1 ? "checked" : "";
    print "<p><label>"._formulize_DB_EXPORT_TO_EXCEL." <input type=\"checkbox\" name=\"excel\" value=\"1\" $excelChecked></input></label></p>\n";
    
    print "<p>"._formulize_DB_EXPORT_NULL_OPTION." <input type=\"text\" name=\"nullOption\" value=\"\"></input></p>\n";
    
    
    print "</div>\n</td></tr></table>";
    print "</form>";

    print "</td><td width=5%></td></tr></table>";
    print "</center></body>";
    print "</HTML>";
} elseif(!isset($formulize_doingManualExport)) {

    // 1. need to pickup the full query that was used for the dataset on the page where the button was clicked
    // 2. need to run that query and make a complete dataset
    // 3. need to send that dataset to the prepexport function to make the spreadsheet
    // 4. need to provide a link to the finished file
    // 5. need to make sure import templates are created appropriately

    // read the query data from the cached file
    $queryData = file(XOOPS_ROOT_PATH."/cache/exportQuery_".intval($_GET['eq']).".formulize_cached_query_for_export");
    global $xoopsUser;
    $exportUid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
    $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
    // query fid must match passed fid in URL, and the current user id must match the userid at the time the export file was created
    if (trim($queryData[0]) == intval($_GET['fid']) AND trim($queryData[1]) == $exportUid) {
        if ($_GET['type'] == "update") {
            do_update_export($queryData, $frid, $fid, $groups);
        } else {
            $columns = explode(",", $_GET['cols']);
            $include_metadata = (1 == $_POST['metachoice']);
            $output_filename = isset($GLOBALS['formulize_export_output_filename']) ? $GLOBALS['formulize_export_output_filename'] : "";
            export_data($queryData, $frid, $fid, $groups, $columns, $include_metadata, $output_filename);
        }
    } else {
        print _formulize_DE_EXPORT_FILE_ERROR;
    }
} // end of "if the metachoice form has been submitted"

