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
//============ start of sorting handling that sends info to template (and excludes multiple entry form elements (ie: checkboxes))
//Filter the reqFieldsJwe for the fields we allow sorting on.
$sortq = "SELECT ele_caption, ele_value FROM ". $xoopsDB->prefix("form") . " WHERE id_form = $id_form AND (ele_type = \"checkbox\" OR (ele_type = \"select\" AND ele_value REGEXP \"^.{19}1\")) ORDER BY ele_order";
$ressortq = mysql_query($sortq);
$sortarrayb = 0;
while ($rowsortq = mysql_fetch_row($ressortq))
{
	$sortcheckarray[$sortarrayb] =  $rowsortq[0];
	$sortarrayb++;
}

//print_r($sortcheckarray);

$sortarrayb = 0;
$allowedsort = -1;
foreach($reqFieldsJwe as $onefield)
{
	if(!in_array($onefield, $sortcheckarray))
	{
		$reqSortFields[$sortarrayb] = $onefield;
		$allowedsort++;
	}
	else
	{
		$reqSortFields[$sortarrayb] = "";
	}
	$sortarrayb++;
}
$xoopsTpl->assign('tempcaptionssort', $reqSortFields);

//set the array that has 1,2,3,4,5 etc for using in the sort priority drop downs
	
	$sortcreateindexer = 0;
	array($sort_indexes);
	for($sl=0;$sl<=$allowedsort;$sl++) // by controlling the '<=' you can control the number of sorting order options. 
	{
		$sort_indexes[$sortcreateindexer] = $sortcreateindexer+1;
		$sortcreateindexer++;
	}
	$xoopsTpl->assign('sort_index_array', $sort_indexes);
// ================= end of sorting handling for template


//7th array element after exploding ele_value on means multiple select box if it starts with 1.
//a:3:{i:0;i:1;i:1;i:0
?>