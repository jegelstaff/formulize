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
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/popup_functions.php";
    ob_start();
    ?>
    <script type='text/javascript'>
        window.addEventListener('load', function() {
            var advancedOptions = document.getElementById('advanced_options');
            advancedOptions.addEventListener('toggle', function() {
                advancedOptions.querySelector('summary').textContent = advancedOptions.open ? '<?php print _formulize_DE_EXPORT_HIDE_ADVANCED; ?>' : '<?php print _formulize_DE_EXPORT_SHOW_ADVANCED; ?>';
            });
            var exportForm = document.getElementById('metachoiceform');
            exportForm.addEventListener('submit', function() {
                exportForm.elements['exportsubmit'].disabled = true;
                document.body.style.opacity = 0.25;
            });
        });
    </script>
    <?php
    formulize_popupHeader(_formulize_DE_EXPORT, ob_get_clean());

    print "<form id=\"metachoiceform\" name=\"metachoiceform\" action=\"".getCurrentURL() . "\" method=\"post\" class=\"fz-card\">\n";
    print "<h1 class='fz-card__title'>"._formulize_DE_EXPORT_TITLE."</h1>\n";

    if ($_GET['type'] == "update") {
        print "<p>"._formulize_DE_IMPORT_DATATEMP4." <a href=\"\" onclick=\"javascript:window.opener.showPop('" . XOOPS_URL . "/modules/formulize/include/import.php?fid=$fid&eq=".intval($_GET['eq'])."');return false;\">"._formulize_DE_IMPORT_DATATEMP5."</a></p>\n";
        print "<p>"._formulize_DE_IMPORT_DATATEMP3."</p>\n";
    }

    print "<div class='fz-cluster'><input type=\"submit\" class=\"fz-btn fz-btn--primary\" name=\"exportsubmit\" value=\""._formulize_DE_EXPORT_MAKEFILE."\"></div>\n";

    print "<details class='fz-disclosure' id='advanced_options'>\n";
    print "<summary>"._formulize_DE_EXPORT_SHOW_ADVANCED."</summary>\n";
    print "<div class='fz-stack'>\n";

    if (!isset($_GET['type'])) {
        print "<div class='fz-choice-group'>\n";
        print formulize_popupChoice('radio', 'metachoice', 1, _formulize_DB_EXPORT_METAYES);
        print formulize_popupChoice('radio', 'metachoice', 0, _formulize_DB_EXPORT_METANO, true);
        print "</div>\n";
    } else {
        print "<input type=\"hidden\" name=\"metachoice\" value=\"0\">\n";
    }

    $module_handler = xoops_gethandler('module');
    $config_handler = xoops_gethandler('config');
    $formulizeModule = $module_handler->getByDirname("formulize");
    $formulizeConfig = $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
    print "<div class='fz-choice-group'>\n";
    print formulize_popupChoice('checkbox', 'excel', 1, _formulize_DB_EXPORT_TO_EXCEL, $formulizeConfig['downloadDefaultToExcel'] == 1);
    print "</div>\n";

    print formulize_popupField(_formulize_DB_EXPORT_NULL_OPTION, "<input type=\"text\" class=\"fz-input\" name=\"nullOption\" id=\"nullOption\" value=\"\">", '', 'nullOption');

    print "</div>\n</details>\n";
    print "</form>\n";

    formulize_popupFooter();
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
            do_update_export($queryData, $frid, $fid);
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

