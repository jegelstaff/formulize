<?

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
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
##  Author of this file: Freeform Solutions and NS Tai (aka tuff) and others ##
##  URL: http://www.brandycoke.com/                                          ##
##  Project: Formulize                                                       ##
###############################################################################

// -------------------------------------- JWE MODS BELOW... end of july, early august 2004

// SECURITY!

// 1. find the user id (done above $uid)
// 2. find the form id (done above $id_form)
// 3. find the group membership of the user
// 4. find the groups that have access to the form
// 5. find out if there's overlap
// 6. halt execution with error message if there's no overlap

// code below borrowed from mymenu.php

      // SET THE MODULE id 
	$res4 = $xoopsDB->query("SELECT mid FROM ".$xoopsDB->prefix("modules")." WHERE dirname='formulize'");
	if ($res4) {
		while ($row = mysql_fetch_row($res4)) {
			$module_id = $row[0];
		}
	}
	
	// SET THE GROUPUSER ARRAY -- user is a member of these groups
	if (!$uid) {
		$groupuser[0] = XOOPS_GROUP_ANONYMOUS; 
		// print "ANON USER!!!"; // debug code
	}
	$res = $xoopsDB->query("SELECT groupid FROM ".$xoopsDB->prefix("groups_users_link")." WHERE uid= ".$uid);
	if ( $res ) {
  		while ( $row = mysql_fetch_row ( $res ) ) {
	  		$groupuser[] = $row[0];
			//print "GROUPS: $row[0]"; // DEBUG CODE
  		}
	}

	// SET THE GROUPIDADD ARRAY, AND THEN DO THE CHECK FOR **ADD** PERMISSION - groups that can add to this form
     	$groupidadd = array();
      $res3 = $xoopsDB->query("SELECT gperm_groupid FROM ".$xoopsDB->prefix("group_permission")." WHERE gperm_itemid= ".$id_form." AND gperm_modid=".$module_id . " AND gperm_name=\"add\"");
	if ( $res3 ) {
  		while ( $row = mysql_fetch_row ( $res3 ) ) {
  			$groupidadd[] = $row[0];
  	  	}
	}
/*	print "groupidadd: ";
	print_r($groupidadd);
	print "<br>";*/

	// SET THE GROUPID ARRAY, AND THEN DO THE CHECK FOR **VIEW** PERMISSION - groups this form is permitted for
     	$groupid = array();
      $res2 = $xoopsDB->query("SELECT gperm_groupid FROM ".$xoopsDB->prefix("group_permission")." WHERE gperm_itemid= ".$id_form." AND gperm_modid=".$module_id . " AND gperm_name=\"view\"");
	if ( $res2 ) {
  		while ( $row = mysql_fetch_row ( $res2 ) ) {
  			$groupid[] = $row[0];
  	  	}
	}
	
	// this code checks to see if the user is a member of a group that has been given permission to access the form
	$lettheminjwe = 0;
	foreach ($groupid as $gr){
           	if ( in_array ($gr, $groupuser)) {
			$lettheminjwe = 1;
			break;
		}
	}

	// this code checks to see if the user is a member of a group that has been given permission to ADD TO the form
	$theycanadd = 0;
	foreach ($groupidadd as $gr){
           	if ( in_array ($gr, $groupuser)) {
			$theycanadd = 1;
			break;
		}
	}

	// this code kicks the user out...
	if (!$lettheminjwe)
	{
		//	print "$gr: user is NOT allowed!!!"; // DEBUG CODE
		//redirect to main page if they don't have permission
		$kickmsgjwe = _formulize_NO_PERMISSION;			
		redirect_header(XOOPS_URL."/index.php", 3, $kickmsgjwe);
	}

?>