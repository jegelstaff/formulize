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

// GET A LIST OF AVAILABLE REPORTS
// NOTE:  this code should be moved after the check for whether a report is allowed for the user (or that check should happen first) otherwise a report can be selected in the box which the user doesn't have perm for.  OR... the check below for isallowed could be done prior to reading the report and the check that is currently after could be deleted.  Code has to be checked closely before that change is made.
//$userreportq = "SELECT report_id, report_name, report_groupids FROM " . $xoopsDB->prefix("form_reports") . " WHERE report_id_form=$id_form AND (report_uid=$uid OR report_ispublished=1) GROUP BY report_id ORDER BY report_id";
$userreportq = "SELECT report_id, report_name FROM " . $xoopsDB->prefix("form_reports") . " WHERE report_id_form=$id_form AND (report_uid=$uid OR report_ispublished>0) GROUP BY report_id ORDER BY report_id";
$resuserreportq = mysql_query($userreportq);
$userreportsindexer = 0;
array($userreportslist);
array($userreportsnames);
array($userreportsgroupids);
while ($rowuserreportq = mysql_fetch_row($resuserreportq))
{
	$userreportslist[$userreportsindexer] = $rowuserreportq[0];
	$userreportsnames[$userreportsindexer] = $rowuserreportq[1];
//	$userreportsgroupids[$userreportsindexer] = $rowuserreportq[2];
	$userreportsindexer++;
}

if($userreportsindexer) // if at least one report was found...
{
	// cull reports based on groupid matches with user id and membership
	// make the current report selected.
	$frlindexer = 0;
	$urpnameindexer = 0;
	foreach($userreportslist as $arep)
	{

		$isallowedforuser = 0; //assume no reports are allowed...
		$isallowedforuserq = "SELECT report_uid, report_groupids, report_scope, report_ispublished FROM " . $xoopsDB->prefix("form_reports") . " WHERE report_id=$arep";
		$resisallowedforuserq = mysql_query($isallowedforuserq);
		$rowisallowedforuserq = mysql_fetch_row($resisallowedforuserq);

		if($rowisallowedforuserq[0] == $uid OR $ismoduleadmin) // module admins see all reports for all forms
		{
			$isallowedforuser = 1;
		} 

		$rgids = explode("&*=%4#", $rowisallowedforuserq[1]);
		foreach($rgids as $argid)
		{
			//print "allowed group: $argid<br>";
			if(in_array($argid, $groupuser))
			{
				$isallowedforuser = 1;
				break;
			}
		}
 
		
		if($isallowedforuser)
		{

		// check to see if this report (allowed for this user) permits the viewing of an entry that we need to check

		if($vereportcheck)
		{

		// first check to see that this report allows entry viewing...
		if(strstr($rowisallowedforuserq[3], "2") OR strstr($rowisallowedforuserq[3], "3"))
		{
		}
		else
		{

		//print "in report check";
		// check to see if the groups or users in this report's scope match the match user or groups of the viewentry requested
		// 1. check to see if uid=$enuid is in the scope string
		$needlestr = "uid=" . $enuid;
		$vesaver = strstr($rowisallowedforuserq[2], $needlestr);
		if($vesaver)
		{
			// if there is a match, then restore the view entry setting and the selectjwe setting (to zero)
			$viewentry = $vereportcheck;
			$selectjwe = 0;
		}// end of if the vesaver found something


		}// end of if report allows viewing of entries

		}// end of if vereportcheck

		$finalreportlist[$frlindexer] = $arep;
//		print "currently allowing report number: $arep<br>currently selected report: $report<br>";
		if($arep == $report)
		{
			$finalreportlist[$frlindexer] .= " selected";
		}
		$finalreportlist[$frlindexer] .= ">" . $userreportsnames[$urpnameindexer];
		$frlindexer++;
		}
		$urpnameindexer++;
	}

} 

// override showviewentries iff the user can view reports for this form (they would have to have been published) or they are an admin

if($finalreportlist[0] OR $isadmin)
{
	$showviewentries = 1;
}
?>