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
require_once "../../../mainfile.php";
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

if (!isset($_POST['metachoice'])) {
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
    print "<center><h1>"._formulize_DE_EXPORT_TITLE."</h1><br></center>\n";

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
    print "<p><label><input type=\"checkbox\" name=\"excel\" value=\"1\" $excelChecked>"._formulize_DB_EXPORT_TO_EXCEL."</input></label></p></div>\n";
    print "</form>";

    print "</td><td width=5%></td></tr></table>";
    print "</center></body>";
    print "</HTML>";
} else {

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
            export_data($queryData, $frid, $fid, $groups, $columns, $include_metadata);
        }
    } else {
        print _formulize_DE_EXPORT_FILE_ERROR;
    }
} // end of "if the metachoice form has been submitted"


function do_update_export($queryData, $frid, $fid, $groups) {
    // this is the old export code, which is used for 'update' mode
    $fdchoice = "update";

    $GLOBALS['formulize_doingExport'] = true;
    unset($queryData[0]); // get rid of the fid and userid lines
    unset($queryData[1]);
    $queryData = implode(" ", $queryData); // merge all remaining lines into one string to send to getData
    $data = getData($frid, $fid, $queryData);

    $cols = explode(",", $_GET['cols']);
    $headers = array();
    foreach($cols as $thiscol) {
        if ($thiscol == "creator_email") {
            $headers[] = _formulize_DE_CALC_CREATOR_EMAIL;
        } else {
            $colMeta = formulize_getElementMetaData($thiscol, true);
            $headers[] = $colMeta['ele_colhead'] ? trans($colMeta['ele_colhead']) : trans($colMeta['ele_caption']);
        }
    }

    $filename = prepExport($headers, $cols, $data, $fdchoice, "", "", false, $fid, $groups);

    $pathToFile = str_replace(XOOPS_URL,XOOPS_ROOT_PATH, $filename);

    if ($_GET['type'] == "update") {
        $fileForUser = str_replace(XOOPS_URL. SPREADSHEET_EXPORT_FOLDER, "", $filename);
    } else {
        $form_handler = xoops_getmodulehandler('forms','formulize');
        $formObject = $form_handler->get($fid);
        if (is_object($formObject)) {
            $formTitle = "'".str_replace(array(" ", "-", "/", "'", "`", "\\", ".", "?", ",", ")", "(", "[", "]"), "_", trans($formObject->getVar('title')))."'";
        } else {
            $formTitle = "a_form";
        }
        $fileForUser = _formulize_EXPORT_FILENAME_TEXT."_".$formTitle."_".date("M_j_Y_Hi").".csv";
    }

    header('Content-Description: File Transfer');
    header('Content-Type: text/csv; charset='._CHARSET);
    header('Content-Disposition: attachment; filename='.$fileForUser);
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    if (strstr(strtolower(_CHARSET),'utf') AND $_POST['excel'] == 1) {
        echo "\xef\xbb\xbf"; // necessary to trigger certain versions of Excel to recognize the file as unicode
    }
    if (strstr(strtolower(_CHARSET),'utf-8') AND $_POST['excel'] != 1) {
        ob_start();
        readfile($pathToFile);
        $fileContents = ob_get_clean();
        header('Content-Length: '. filesize($pathToFile) * 2);
        // open office really wants it in UTF-16LE before it will actually trigger an automatic unicode opening?! -- this seems to cause problems on very large exports?
        print iconv("UTF-8","UTF-16LE//TRANSLIT", $fileContents);
    } else {
        header('Content-Length: '. filesize($pathToFile));
        readfile($pathToFile);
    }
}


function export_data($queryData, $frid, $fid, $groups, $columns, $include_metadata) {
    global $xoopsDB;

    // generate the export filename, which the user will see
    $form_handler = xoops_getmodulehandler('forms','formulize');
    $formObject = $form_handler->get($fid);
    if (is_object($formObject)) {
        $formTitle = "'".str_replace(array(" ", "-", "/", "'", "`", "\\", ".", "?", ",", ")", "(", "[", "]"), "_", trans($formObject->getVar('title')))."'";
    } else {
        $formTitle = "a_form";
    }
    $export_filename = _formulize_EXPORT_FILENAME_TEXT."_".$formTitle."_".date("M_j_Y_Hi").".csv";

    // output http headers
    header('Content-Description: File Transfer');
    header('Content-Type: text/csv; charset='._CHARSET);
    header('Content-Disposition: attachment; filename='.$export_filename);
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    // get a list of columns for export
    $headers = array();
    if ($include_metadata) {
        // include metadata columns if the user requested them
        $headers = array(_formulize_ENTRY_ID, _formulize_DE_CALC_CREATOR, _formulize_DE_CALC_MODIFIER,
            _formulize_DE_CALC_CREATEDATE, _formulize_DE_CALC_MODDATE);
    } else {
        if (in_array("entry_id", $columns)) {
            $headers[] = _formulize_ENTRY_ID;
        }
        if (in_array("uid", $columns) OR in_array("creation_uid", $columns)) {
            $headers[] = _formulize_DE_CALC_CREATOR;
        }
        if (in_array("proxyid", $columns) OR in_array("mod_uid", $columns)) {
            $headers[] = _formulize_DE_CALC_MODIFIER;
        }
        if (in_array("creation_date", $columns) OR in_array("creation_datetime", $columns)) {
            $headers[] = _formulize_DE_CALC_CREATEDATE;
        }
        if (in_array("mod_date", $columns) OR in_array("mod_datetime", $columns)) {
            $headers[] = _formulize_DE_CALC_MODDATE;
        }
    }
    foreach ($columns as $thiscol) {
        if ("creator_email" == $thiscol) {
            $headers[] = _formulize_DE_CALC_CREATOR_EMAIL;
        } else {
            $colMeta = formulize_getElementMetaData($thiscol, true);
            $headers[] = $colMeta['ele_colhead'] ? trans($colMeta['ele_colhead']) : trans($colMeta['ele_caption']);
        }
    }
    if ($include_metadata) {
        // include metadata columns if the user requested them
        $columns = array_merge(array("entry_id", "uid", "proxyid", "creation_date", "mod_date"), $columns);
    }

    if (strstr(strtolower(_CHARSET),'utf') AND $_POST['excel'] == 1) {
        echo "\xef\xbb\xbf"; // necessary to trigger certain versions of Excel to recognize the file as unicode
    }

    // output export header
    $output_handle = fopen('php://output', 'w');    // open a file handle to stdout because fputcsv() needs it
    fputcsv($output_handle, $headers);

    // output export data
    $GLOBALS['formulize_doingExport'] = true;
    unset($queryData[0]); // get rid of the fid and userid lines
    unset($queryData[1]);
       
    $data_sql = implode(" ", $queryData); // merge all remaining lines into one string to send to getData
    if(substr($data_sql, 0, 12)=="USETABLEFORM") {
        $params = explode(" -- ", $data_sql);
        $data = dataExtractionTableForm($params[1], $params[2], $params[3], $params[4], $params[5], FALSE, FALSE, $params[8], $params[9]);
        foreach($data as $entry) {
            $row = array();
            foreach($columns as $column) {
                $row[] = trans(html_entity_decode(displayTogether($entry, $column, ", "), ENT_QUOTES));    
            }
            // output this row to the browser
            fputcsv($output_handle, $row);
        }
        
    } else {

        $limitStart = 0;
        $limitSize = 50;    // export in batches of 50 records at a time
    
        do {
            // load part of the data, since a very large dataset could exceed the PHP memory limit
            $data = getData($frid, $fid, $data_sql, "AND", null, $limitStart, $limitSize);
            if (is_array($data)) {
                foreach ($data as $entry) {
                    $row = array();
                    foreach ($columns as $column) {
                        switch ($column) {
                            case "entry_id":
                            $formhandle = getFormHandlesFromEntry($entry);
                            $ids = internalRecordIds($entry, $formhandle[0]);
                            $row[] = $ids[0];
                            break;
    
                            case "uid":
                            $c_uid = display($entry, 'creation_uid');
                            $c_name_q = q("SELECT name, uname FROM " . $xoopsDB->prefix("users") . " WHERE uid='$c_uid'");
                            $row[] = (isset($c_name_q[0]['name']) ? $c_name_q[0]['name'] : $c_name_q[0]['uname']);
                            break;
    
                            case "proxyid":
                            $m_uid = display($entry, 'mod_uid');
                            if ($m_uid) {
                                $m_name_q = q("SELECT name, uname FROM " . $xoopsDB->prefix("users") . " WHERE uid='$m_uid'");
                                $row[] = (isset($m_name_q[0]['name']) ? $m_name_q[0]['name'] : $m_name_q[0]['uname']);
                            } else {
                                $row[] = "";
                            }
                            break;
    
                            case "creation_date":
                            $row[] = display($entry, 'creation_datetime');
                            break;
    
                            case "mod_date":
                            $row[] = display($entry, 'mod_datetime');
                            break;
    
                            default:
                            $row[] = trans(html_entity_decode(displayTogether($entry, $column, ", "), ENT_QUOTES));
                        }
                    }
                    // output this row to the browser
                    fputcsv($output_handle, $row);
                }
    
                // get the next set of data
                set_time_limit(90);
                $limitStart += $limitSize;
            }
        } while (is_array($data) and count($data) > 0);

    }
        
    fclose($output_handle);
}
