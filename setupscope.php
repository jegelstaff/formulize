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

// create a masteruserlist for applying a specified report scope.

if($sentscope[0]) // if a scope was sent back from the user or a report then make it and let it override the groupsscope of the form
{

//1. turn groupids into a list of user ids
//2. add userids to the list

$mulindexer = 0;
foreach($sentscope as $ascope)
{
	//print "current scope setting: $ascope<br>";
	$hasgroupscope = "userspecified";
	if(strstr($ascope, "g")) // if the current entry is a group id then traverse the groups_users_link for user ids.
	{

		if($ascope == "g3") // if the selected scope is the anonymous group (group 3)
		{
			$masteruserlist[$mulindexer] = "anon";
			$mulindexer++;
		}
		else
		{
		//print "old gid: $ascope<br>";
		$ascope = substr($ascope, 1);
		//print "new gid: $ascope<br>";
		$getuidsq = "SELECT uid FROM " . $xoopsDB->prefix("groups_users_link") . " WHERE groupid=$ascope";
		$resgetuidsq = mysql_query($getuidsq);
		while($rowgetuidsq = mysql_fetch_row($resgetuidsq))
		{
			$masteruserlist[$mulindexer] = $rowgetuidsq[0];
			$mulindexer++;	
		}
		} // end of it it's an anon group or not.
	}
	else
	{
		$masteruserlist[$mulindexer] = $ascope;
		$mulindexer++;	
	}
} // end of foreach

if(!$masteruserlist[0]) // if the sentscope in fact returns no users... then set it to a null value which will result in no rows being returned
{
	$masteruserlist[0] = "null";
}

//print_r($masteruserlist); // debug code

} // end of it there's a user-sent scope
else // if there was no user-sent scope....check for a group scope on the form 
{

if($hasgroupscope)
{
	$gidindexer = 0;
	array ($groupid2);
	foreach($groupidadd as $gid)
	{
		// if the group is one the user is a member of...
		if( in_array($gid, $groupuser) )
		{
			$groupid2[$gidindexer] = $gid;
			$gidindexer++;
		}
	}
//	print "Add-permitted groups the user is a member of: ";
//	print_r($groupid2)
//	print "<br>";

	array($masteruserlist);
	foreach($groupid2 as $gid)
	{
		$userlistres = $xoopsDB->query("SELECT uid FROM ".$xoopsDB->prefix("groups_users_link")." WHERE groupid=".$gid);
		while ( $ulistrow = mysql_fetch_row ( $userlistres ) ) {
	  		$masteruserlist[] = $ulistrow[0];
  		}
	}

	$masteruserlist = array_unique($masteruserlist);
} // end of if the form hasgroupscope

} // end of if there's a user-sent scope or not

// now process the master user list, created either from the sent scope or based on the groupscope
if($masteruserlist[0])  // if there's an entry in the master userlist, then we need to make  the groupsscope parameter
{
	$beenthroughonce = 0;
	foreach($masteruserlist as $oneusersid)
	{
		if($oneusersid == "anon") {$oneusersid = "0";}
		if(!$beenthroughonce)
		{
			$gscopeparam = "uid=" . $oneusersid;
			$beenthroughonce = 1;
		}
		else
		{
			$gscopeparam = $gscopeparam . " OR uid=" . $oneusersid;
		}
	}

//	print_r($masteruserlist);
//	print "<br>$gscopeparam"; //debug lines
}

?>