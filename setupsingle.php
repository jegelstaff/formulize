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



if($issingle) // if it's a single entry form...
{
	if(!$hasgroupscope AND !$viewentry)
	{
		$issquery = "SELECT id_req FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form=$id_form AND uid=$uid ORDER BY id_req";
		$issquery2 = mysql_query($issquery);
//		print "$issquery";
		$firstrowissquery = mysql_fetch_row($issquery2);
		/*print "**";
		print_r($firstrowissquery);
		print "**<br>";*/
		$viewentry = $firstrowissquery[0];
//		print "*$viewentry*<br>";


	}
	elseif(!$viewentry) // as long as no view entry has been specified (and the validity of the view entry has already been checked above) then if it's a group scope form, then we display the first entry in the DB that belongs to a group the user is a member of
	{

		//*******code duplicated from above in the if-we're-showing-the-select-page -- used to generate the groupscope:

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
	$beenthroughonce = 0;
	foreach($masteruserlist as $oneusersid)
	{
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
//====================== end of duplicated lines

		$gsquery = "SELECT id_req FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form=$id_form AND ($gscopeparam) ORDER BY id_req";
		$gsquery2 = mysql_query($gsquery);
		//print "$gsquery";
		$firstrowgsquery = mysql_fetch_row($gsquery2);
		/*print "**";
		print_r($firstrowgsquery);
		print "**<br>";*/
		$viewentry = $firstrowgsquery[0];
		//print "*$viewentry*<br>";
	}
}

?>
