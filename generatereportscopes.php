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
// GENERATE AVAILABLE REPORT SCOPES....

if($showscope) // only generate if the report allows it.
{
// 1. get all groups that have add permission for the form (done above)
// 2. if user is not admin, find the user's groups in the groupidadd array (done through the group_user_link table
// 3. get all users in the final set of groups
// 4. make an array of all the groups plus all the users and send it to the template

array($scopegroups); // the list of groupids that are valid scopes
if(!$ismoduleadmin) // note that only module admins see all groups.  All other users see only the groups they are members of (regular admins will see scope selections even for nongroupscope forms).
{
	$sgindexer = 0;
	foreach($groupuser as $ugp)
	{
		if(in_array($ugp, $groupidadd))
		{
			$scopegroups[$sgindexer] = $ugp;
			$sgindexer++;
		}
	}
}
else
{
	$scopegroups = $groupidadd;
}

	//get the names of the scopegroups, and the users in the scope groups

   array($masteruserscopelist);
   $scopenamesindexer = 0;
   $userlistindexer = 0;
	foreach($scopegroups as $sgid)
	{
		$scopeuserlistres = $xoopsDB->query("SELECT uid FROM ".$xoopsDB->prefix("groups_users_link")." WHERE groupid=".$sgid);
		

		while ( $scopeulistrow = mysql_fetch_row ( $scopeuserlistres ) ) {
//			print "group: $sgid<br>";
//			print "uid found: $scopeulistrow[0]<br>";
	  		$masteruserscopelist[$userlistindexer] = $scopeulistrow[0];
			if(in_array($scopeulistrow[0], $sentscope))
			{
				$masteruserscopelist[$userlistindexer] .= " selected";
			}
			$queryforrealnames = "SELECT name FROM " . $xoopsDB->prefix("users") . " WHERE uid=$scopeulistrow[0]";
			$resqforrealnames = mysql_query($queryforrealnames);
			$rowqforrealnames = mysql_fetch_row($resqforrealnames);
//			print "username: $rowqforrealnames[0]<br>";
			$masteruserscopelist[$userlistindexer] .= ">" .  _formulize_USERSCOPE . $rowqforrealnames[0];
			$userlistindexer++;
  		}

		$sgnameq = "SELECT name FROM " . $xoopsDB->prefix("groups") . " WHERE groupid=$sgid";
		$ressgname = mysql_query($sgnameq);
		$rowsgname = mysql_fetch_row($ressgname);
		$scopegroups[$scopenamesindexer] = "g" . $sgid; // adds a g to the beginning of the  current groupid, so that it can be identified as a group id later on when reading scope selections the user makes.  
		// checks to see if this scope was a selected scope and adds on other text as needed to make the selection box work in the template.
		if(in_array($scopegroups[$scopenamesindexer], $sentscope))
		{
			$scopegroups[$scopenamesindexer] .= " selected";	
		}
		$scopegroups[$scopenamesindexer] .= ">" . _formulize_GROUPSCOPE . $rowsgname[0];
		$scopenamesindexer++;
	}

	// this will now be an array of all the unique user ids in groups that have add permission in the form (culled by groups the user is a member of if they are not an admin)
	$masteruserscopelist = array_unique($masteruserscopelist);

	function cmp($a, $b) // a function that will do a sort on an array by comparing the text after the last > in the values.
	{
		$a = substr(strrchr($a, ">"), 1);
		$b = substr(strrchr($b, ">"), 1);

		if ($a == $b) {
	      return 0;
   		}
		return ($a < $b) ? -1 : 1;
	}

	usort($masteruserscopelist, "cmp");

array($tempscopenames);

$masteruserscopelist = array_reverse($masteruserscopelist);
//print_r($masteruserscopelist);
//$scopegroups = array_reverse($scopegroups);
//print_r($scopegroups);
$tempscopenames = array_merge($masteruserscopelist, $scopegroups);
$tempscopenames = array_reverse($tempscopenames);

$xoopsTpl->assign('reportscope', _formulize_REPORTSCOPE);
$xoopsTpl->assign('scopenames', $tempscopenames);

}
// END OF GENERATING REPORT SCOPES...
?>