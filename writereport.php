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
// WRITE A REPORT TO THE DB IF THE USER SAVED ONE...
// note that the form configuration means that if the user has typed in the formname box, then if they *do a query with the ENTER key* the form will be saved.
if($_POST['reportnametouse'] AND isset($_POST['save']))
{
//	print "WRITING REPORT<br>";
	$report_name = $_POST['reportnametouse'];
	$report_id_form = $id_form;
	$report_uid = $uid;
	$report_ispublished = $_POST['publishreport'];
	$report_globalandor = $globalandor;

	if($report_ispublished) // if the report is published then append the other options to the is published flag
	{
		$report_nove = $_POST['publishnove'];
		$report_calconly = $_POST['publishcalconly'];
		
		$report_ispublished .= $report_nove . $report_calconly;
	}

	function flatarray ($ain) // used to flatten arrays into a format that can be saved as text fields in the _reports table and then exploded into arrays again upon reading the report
	{
		$startsave = 1;
		foreach($ain as $fieldtosave)
		{
			$fieldtosave = eregi_replace ("&#039;", "`", $fieldtosave);
			$fieldtosave = eregi_replace ("&quot;", "`", $fieldtosave);
			$fieldtosave = eregi_replace ("'", "`", $fieldtosave);
			if($startsave)
			{
	  			$returnstring = $fieldtosave;
				$startsave=0;
			}
			else
			{
				$returnstring .= "&*=%4#" . $fieldtosave;
			}
		}
		return $returnstring;
	}

	if($_POST['lockscope'] == 1) // get the current scope into a form where we can save it.
	{
		if($sentscope)
		{
			$report_scope = flatarray($sentscope);
		}
		else 
		{
			// default scope (not a user selected scope) is in use, so we must turn the group list that the gscopeparam is based on into a sentscope style formatted array and then save it
			if($hasgroupscope)
			{
				// turn groupid2 array into an array of gids with "g" appended in front, so that they will be processed for userids when read back from the report
				foreach($groupid2 as $thisgid)
				{
					$groupid2towrite[] = "g" . $thisgid;
				}
				$report_scope = flatarray($groupid2towrite);
			}
			else
			{
				$report_scope = $uid;
			}
		}
		$showscope = 0;
	}
	
	$report_groupids = flatarray($_POST['reportpubgroups']);
	$report_fields = flatarray($reqFieldsJwe);
	$report_search_typeArray = flatarray($search_typeArray);
	$report_search_textArray = flatarray($search_textArray);
	$report_andorArray = flatarray($andorArray);
	$report_sort_orderArray = flatarray($sort_orderArray);
	$report_ascdscArray = flatarray($ascdscArray);

	//extract all the calc_type arrays and flatten them all and put them together with a unique delimiter
	$startcalcsave = 1;
	foreach($calc_typeArray as $onecalcarray)
	{
		if($startcalcsave)
		{
			$report_calc_typeArray = flatarray($onecalcarray);
			$startcalcsave = 0;
		}
		else
		{
			$report_calc_typeArray .= "!@+*+6-" . flatarray($onecalcarray);
		}
	}

	$reportwriteq = "INSERT INTO " . $xoopsDB->prefix("form_reports") . " (report_name, report_id_form, report_uid, report_ispublished, report_groupids, report_scope, report_fields, report_search_typeArray, report_search_textArray, report_andorArray, report_calc_typeArray, report_sort_orderArray, report_ascdscArray, report_globalandor) VALUES ('$report_name', '$report_id_form', '$report_uid', '$report_ispublished', '$report_groupids', '$report_scope', '$report_fields', '$report_search_typeArray', '$report_search_textArray', '$report_andorArray', '$report_calc_typeArray', '$report_sort_orderArray', '$report_ascdscArray', '$report_globalandor')";

//	print "$reportwriteq<br>";
	$resultReportWriteq = $xoopsDB->query($reportwriteq);
//	print "$resultReportWriteq<br>";
	// have to set view perm for groups that the form has been published to, if those groups don't have view perm right now.
	// 1. find out what groups published to don't have view perm on the form
	// 2. give those groups view perm

	if(isset($_POST['reportpubgroups']) AND isset($_POST['publishreport'])) // if the report was published to a group or more...
	{

// is grpview array a duplicate of groupuser? -- jwe 12/30/04
	$ghavevpq ="SELECT gperm_groupid FROM " . $xoopsDB->prefix("group_permission") . " WHERE gperm_modid=$module_id AND gperm_itemid=$id_form AND gperm_name=\"view\"";
//	print "<br>Groups that can view query: $ghavevpq<br>";
	$resghavevpq = mysql_query($ghavevpq);
	$grpviewindexer = 0;
	while($rowghavevpq = mysql_fetch_row($resghavevpq))
	{
		$grpview[$grpviewindexer] = $rowghavevpq[0];
		$grpviewindexer++;
	}
//	print "Groups that can view: ";
//	print_r($grpview);
	foreach($_POST['reportpubgroups'] as $pubdgrp)
	{
		if(!in_array($pubdgrp, $grpview))
		{
//			print "<br>not found in groups that can view: $pubdgrp";
			// give this group access to the module
			// give this group access to the form

			// add view perm for this form to that group
		
			// first check to see if they have access and if not, then give it

			$haveaccessq = "SELECT * FROM " . $xoopsDB->prefix("group_permission") . " WHERE gperm_groupid=$pubdgrp AND gperm_itemid=$module_id AND gperm_modid=1 AND gperm_name=\"module_read\""; 
			$reshaveaccessq = mysql_query($haveaccessq);
			$rowhaveaccessq = mysql_fetch_row($reshaveaccessq);
			if(!$rowhaveaccessq[0]) // if that permisison wasn't found, then give it...
			{
				$setaccessq = "INSERT INTO " . $xoopsDB->prefix("group_permission") . " (gperm_groupid, gperm_itemid, gperm_modid, gperm_name) VALUES ($pubdgrp, $module_id, 1, \"module_read\")";
				$resultSetAccessq = $xoopsDB->query($setaccessq);
			}
		
			// find block id
			$findblockq = "SELECT bid FROM " . $xoopsDB->prefix("newblocks") . " WHERE mid=$module_id";
			$resfindblockq = mysql_query($findblockq);
			$rowfindblockq = mysql_fetch_row($resfindblockq);
			$block_id = $rowfindblockq[0];
		
			// check about block access...

			$havebaccessq = "SELECT * FROM " . $xoopsDB->prefix("group_permission") . " WHERE gperm_groupid=$pubdgrp AND gperm_itemid=$block_id AND gperm_modid=1 AND gperm_name=\"block_read\""; 
			$reshavebaccessq = mysql_query($havebaccessq);
			$rowhavebaccessq = mysql_fetch_row($reshavebaccessq);
			if(!$rowhavebaccessq[0]) // if no block access then set it.
			{
				$setbaccessq = "INSERT INTO " . $xoopsDB->prefix("group_permission") . " (gperm_groupid, gperm_itemid, gperm_modid, gperm_name) VALUES ($pubdgrp, $block_id, 1, \"block_read\")";
				$resultSetBAccess = $xoopsDB->query($setbaccessq);
			}
	
			// set their view permission on this form...
			$setviewq = "INSERT INTO " . $xoopsDB->prefix("group_permission") . " (gperm_groupid, gperm_itemid, gperm_modid, gperm_name) VALUES ($pubdgrp, $id_form, $module_id, \"view\")";
			$resultSetViewq = $xoopsDB->query($setviewq);
		}
	}

	} // END IF publish group list is set

	// get the id of the just-written report so we can select it in the dropdown list...
	$jwrq = "SELECT report_id FROM " . $xoopsDB->prefix("form_reports") . " WHERE report_name='$report_name' AND report_id_form='$report_id_form' AND report_uid='$report_uid' AND report_ispublished='$report_ispublished' AND report_groupids='$report_groupids' AND report_scope='$report_scope' AND report_fields='$report_fields' AND report_search_typeArray='$report_search_typeArray' AND report_search_textArray='$report_search_textArray' AND report_andorArray='$report_andorArray' AND report_calc_typeArray='$report_calc_typeArray' AND report_sort_orderArray='$report_sort_orderArray' AND report_ascdscArray='$report_ascdscArray'";
	$resjwrq = mysql_query($jwrq);
	$rowjwrq = mysql_fetch_row($resjwrq);
	$report = $rowjwrq[0];

	//add just written report to the finalreportlist and make it selected...(unselect any others)
	$frpindexer = 0;
	foreach($finalreportlist as $afreport)
	{
//		print "current frep entry: $afreport<br>";
		$finalreportlist[$frpindexer] = str_replace(" selected", "", $afreport);
//		print "mod'd entry: $finalreportlist[$frpindexer]<br>";
		$frpindexer++;
	}
	$finalreportlist[$frpindexer] = $report . " selected>" . stripslashes($report_name);
//	print_r($finalreportlist);
	$candeletereport = 1;  // set this and send it so they can delete the report they just made.
	$xoopsTpl->assign('candeletereport', $candeletereport);
	$xoopsTpl->assign('reportdelete', _formulize_REPORTDELETE);
	$xoopsTpl->assign('delete', _formulize_DELETE);
	$xoopsTpl->assign('deleteconfirm', _formulize_DELETECONFIRM . "&nbsp;<b>" . $report_name . "</b>.");
		



} // end of writing report to DB

?>