<?

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

	// read all the query/calc/sort arrays out of the database...


	$getreportq = "SELECT report_name, report_id_form, report_uid, report_ispublished, report_scope, report_groupids, report_fields, report_search_typeArray, report_search_textArray, report_andorArray, report_calc_typeArray, report_sort_orderArray, report_ascdscArray, report_globalandor FROM " . $xoopsDB->prefix("form_reports") . " WHERE report_id = $report";

	$resgetreportq = mysql_query($getreportq);
	$rowgetreportq = mysql_fetch_row($resgetreportq);
//	print_r($rowgetreportq);

	$report_name = $rowgetreportq[0];
	$id_form = $rowgetreportq[1];
	$report_uid = $rowgetreportq[2];
	$report_ispublished = $rowgetreportq[3];

	// check for the other publishing options, nove and calconly
	$report_nove = strstr($report_ispublished, "2");
	$report_calconly = strstr($report_ispublished, "3");

	if($rowgetreportq[4])
	{
		// need to set sentscope here and then process sentscope below to get an UP TO DATE LIST OF USERS for the scope param
		$sentscope = explode("&*=%4#", $rowgetreportq[4]);
		$showscope = 0;
	}
	else
	{
		array($sentscope);
		// need to grab the scope setting if the user specified one
		$sentscope = $_POST['scopeselector'];
	}
	$report_groupids = explode("&*=%4#", $rowgetreportq[5]);

	$apCorrectField = eregi_replace ("`", "'", $rowgetreportq[6]);

	$reqFieldsJwe = explode("&*=%4#", $apCorrectField);
//	print "reqFields: ";
//	print_r($reqFieldsJwe);
	$search_typeArray = explode("&*=%4#", $rowgetreportq[7]);
	$search_textArray = explode("&*=%4#", $rowgetreportq[8]);
	$andorArray = explode("&*=%4#", $rowgetreportq[9]);

	$tempcalc_typeArray = explode("!@+*+6-", $rowgetreportq[10]);
	$tcindexer=0;
	foreach($tempcalc_typeArray as $tc1)
	{
		$tcarray = explode("&*=%4#", $tc1);
		if ($tcarray[0] != "")
		{
			$calc_typeArray[$tcindexer] = $tcarray;
		}
		else
		{
			$calc_typeArray[$tcindexer] = "";
		}
		$tcindexer++;
	}
//	print "calc_typeArray: ";
//	print_r($calc_typeArray);
	$sort_orderArray = explode("&*=%4#", $rowgetreportq[11]);
	$ascdscArray = explode("&*=%4#", $rowgetreportq[12]);
	$globalandor = $rowgetreportq[13];


// verify that the user is a member of groups that can see the report, and if not, then report=0 -- also check to see if it is their own report and if so then candelete is set to on.

	$reportallowed = 0;
	$candeletereport = 0;
	if($report_uid == $uid OR $ismoduleadmin) // if it's their own report, or they're a module admin...
	{
		$reportallowed = 1;
		$candeletereport = 1;
	}
	else
	{
		foreach($report_groupids as $anothergid)
		{
			if(in_array($anothergid, $groupuser))
			{
				$reportallowed = 1;
				break;
			}
		}
	}


if($reportallowed)
{
		//$xoopsTpl->assign('ispublished', $report_ispublished); // used to be used by the _select.html template to control how View This Entry links are displayed, but superseded by other flags that indicate whether those links should appear at all.
		$xoopsTpl->assign('captions_sort_dir', $ascdscArray); // asc desc sort settings
		$xoopsTpl->assign('captions_search_type', $search_typeArray);
		$xoopsTpl->assign('captions_search_text', $search_textArray);
		$xoopsTpl->assign('captions_andor', $andorArray);
		$xoopsTpl->assign('captions_calc_type', $calc_typeArray);
		$xoopsTpl->assign('captions_sort_order', $sort_orderArray);
		$xoopsTpl->assign('globalandor', $globalandor);

		if($ismoduleadmin)
		{
			$candeletereport = 1;
		}

		$xoopsTpl->assign('candeletereport', $candeletereport);
		
		if($candeletereport)
		{
		$xoopsTpl->assign('reportdelete', _formulize_REPORTDELETE);
		$xoopsTpl->assign('delete', _formulize_DELETE);
		$xoopsTpl->assign('deleteconfirm', _formulize_DELETECONFIRM . "&nbsp;<b>" . $report_name . "</b>.");
		}
}
else
{
	$report=0;
	// blank all settings derived from the report, due to user not having perm on the report
	$reqFieldsJwe = "";
	$ascdscArray = "";
	$search_typeArray = "";
	$search_textArray = "";
	$andorArray = "";
	$calc_typeArray = "";
	$sort_orderArray = "";
	$globalandor = "";

}

?>