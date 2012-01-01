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
##  Author of this file: Freeform Solutions 					     ##
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

if(!isset($_POST['exportsubmit'])) {

	print "<HTML>";
	print "<head>";
	print "<title>" . _formulize_DE_EXPORT . "</title>\n";

	print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"" . XOOPS_URL . "/xoops.css\" />\n";
	$themecss = xoops_getcss();
	print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"$themecss\" />\n";
	
	print "</head>";
	print "<body style=\"background: white; margin-top:20px;\"><center>"; 
	print "<table width=100%><tr><td width=5%></td><td width=90%>";
	print "<form name=\"metachoiceform\" action=\"".getCurrentURL() . "\" method=\"post\">\n";
	print "<center><h1>"._formulize_DE_EXPORT_TITLE."</h1><br></center>\n";
	
	if(!isset($_GET['type'])) {
		print "<input type=\"radio\" name=\"metachoice\" value=\"1\">"._formulize_DB_EXPORT_METAYES."</input>\n<br>\n";
		print "<input type=\"radio\" name=\"metachoice\" value=\"0\" checked>"._formulize_DB_EXPORT_METANO."</input>\n<br><br>\n";
	}
	
	if($_GET['type']=="update") {
		print "<p>"._formulize_DE_IMPORT_DATATEMP4." <a href=\"\" onclick=\"javascript:window.opener.showPop('" . XOOPS_URL . "/modules/formulize/include/import.php?fid=$fid&eq=".intval($_GET['eq'])."');return false;\">"._formulize_DE_IMPORT_DATATEMP5."</a></p>\n";
		print "<p>"._formulize_DE_IMPORT_DATATEMP3."</p>\n";
	}
	
	$module_handler = xoops_gethandler('module');
	$config_handler = xoops_gethandler('config');
	$formulizeModule = $module_handler->getByDirname("formulize");
	$formulizeConfig = $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
	$excelChecked = $formulizeConfig['downloadDefaultToExcel'] == 1 ? "checked" : "";
	print "<label><input type=\"checkbox\" name=\"excel\" value=\"1\" $excelChecked>"._formulize_DB_EXPORT_TO_EXCEL."</input></label>\n<br><br>\n";

	print "<center>\n";
	print "<input type=\"submit\" name=\"exportsubmit\" value=\""._formulize_DE_EXPORT_MAKEFILE."\">\n";
	print "</center>\n";
	print "</form>";

	print "</td><td width=5%></td></tr></table>";
	print "</center></body>";
	print "</HTML>";

} else {

	if(!isset($_POST['metachoice'])) {
		$_POST['metachoice'] = 0; // just set this to zero in case it's not set, which should never matter, since if 'type' is set, and metachoice is therefore skipped above, and you're making a template for updating, the metachoice is ignored in the actual export file creation process when updating
	}
 
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
	if(trim($queryData[0]) == intval($_GET['fid']) AND trim($queryData[1]) == $exportUid) { // query fid must match passed fid in URL, and the current user id must match the userid at the time the export file was created
			
			$GLOBALS['formulize_doingExport'] = true;
			unset($queryData[0]); // get rid of the fid and userid lines
			unset($queryData[1]);
			$queryData = implode(" ",$queryData); // merge all remaining lines into one string to send to getData
			$data = getData($frid, $fid, $queryData);
			
			$cols = explode(",",$_GET['cols']);
			$headers = array();
			foreach($cols as $thiscol) {
				if($thiscol == "creator_email") {
					$headers[] = _formulize_DE_CALC_CREATOR_EMAIL;
				} else {
					$colMeta = formulize_getElementMetaData($thiscol, true);
					$headers[] = $colMeta['ele_colhead'] ? trans($colMeta['ele_colhead']) : trans($colMeta['ele_caption']);
				}
			}
			if($_GET['type'] == "update") {
				$fdchoice = "update";
			} else {
				$fdchoice = "comma";
			}
			
			$filename = prepExport($headers, $cols, $data, $fdchoice, "", "", false, $fid, $groups);
			
			$pathToFile = str_replace(XOOPS_URL,XOOPS_ROOT_PATH,$filename);
			if($_GET['type']=="update") {
				$fileForUser = str_replace(XOOPS_URL."/modules/formulize/export/","",$filename);
			} else {
				$form_handler = xoops_getmodulehandler('forms','formulize');
				$formObject = $form_handler->get($fid);
				if(is_object($formObject)) {
					$formTitle = "'".str_replace(array(" ", "-", "/", "'", "`", "\\", ".", "’", ",", ")", "(", "[", "]"), "_", trans($formObject->getVar('title')))."'";
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
			
			if(strstr(strtolower(_CHARSET),'utf') AND $_POST['excel']==1) {
				echo "\xef\xbb\xbf"; // necessary to trigger certain versions of Excel to recognize the file as unicode
			}
			if(strstr(strtolower(_CHARSET),'utf-8') AND $_POST['excel']!=1) {
				ob_start();
				readfile($pathToFile);
				$fileContents = ob_get_clean();
				header('Content-Length: '. filesize($pathToFile)*2);
				print iconv("UTF-8","UTF-16LE//TRANSLIT",$fileContents); // open office really wants it in UTF-16LE before it will actually trigger an automatic unicode opening?! -- this seems to cause problems on very large exports?  
			} else {
				header('Content-Length: '. filesize($pathToFile));
				readfile($pathToFile);	
			}
			
			exit();
			
			
			
	} else {
			print _formulize_DE_EXPORT_FILE_ERROR;
	}

} // end of "if the metachoice form has been submitted"



?>