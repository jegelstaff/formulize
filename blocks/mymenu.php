<?php
// Copyright (c) 2004 Freeform Solutions and Marcel Widmer (for the original
// mymenu module).
// ------------------------------------------------------------------------- //
//                XOOPS - PHP Content Management System                      //
//                       <http://www.xoops.org/>                             //
// ------------------------------------------------------------------------- //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
// ------------------------------------------------------------------------- //

function block_formulizeMENU_show() {
        global $xoopsDB, $xoopsUser, $xoopsModule, $myts;
		    $myts =& MyTextSanitizer::getInstance();

        $block = array();
        $groups = array();
        $block['title'] = ""; //_MB_formulizeMENU_TITLE;
	  // following line added jwe 7/23/04 -- part of the form menu redrawing to look like main menu
        $block['content'] = "<table cellspacing='0' border='0'><tr><td id=mainmenu>";

        $result = $xoopsDB->query("SELECT menuid, position, indent, itemname, margintop, marginbottom, itemurl, bold, membersonly, mainmenu, status FROM ".$xoopsDB->prefix("form_menu")." ORDER BY position");
       
        // Gestion des permissions 
	include_once XOOPS_ROOT_PATH.'/class/xoopsform/grouppermform.php';

	$res4 = $xoopsDB->query("SELECT mid FROM ".$xoopsDB->prefix("modules")." WHERE dirname='formulize'");
	if ($res4) {
		while ($row = mysql_fetch_row($res4))
			$module_id = $row[0];
	}
	
	//$module_id = $xoopsModule->getVar('mid'); // récupère le numéro id du module

	$perm_name = 'Permission des catégories';
	//if ($xoopsUser) { $groups = $xoopsUser->getGroups(); } else { $groups = XOOPS_GROUP_ANONYMOUS; }
	if ($xoopsUser) {$uid = $xoopsUser->getVar("uid");} else { $groupuser[0] = XOOPS_GROUP_ANONYMOUS; }
	$res = $xoopsDB->query("SELECT groupid FROM ".$xoopsDB->prefix("groups_users_link")." WHERE uid= ".$uid);
	if ( $res ) {
  	  while ( $row = mysql_fetch_row ( $res ) ) {
  		$groupuser[] = $row[0];

  	  }
	}
	$gperm_handler =& xoops_gethandler('groupperm');

// SQL updated to look at view permission specifically -- jwe 7/28/04
        while (list($menuid, $position, $indent, $itemname, $margintop, $marginbottom, $itemurl, $bold, $membersonly, $mainmenu, $status) = $xoopsDB->fetchRow($result)) {
  		//echo $itemname." <br>".$status."<br>".$menuid;
  		                if ( $status == 1 ) {
	                $groupid = array();
//	                $res2 = $xoopsDB->query("SELECT gperm_groupid, gperm_itemid FROM ".$xoopsDB->prefix("group_permission")." WHERE gperm_itemid= ".$menuid." AND gperm_modid=".$module_id " AND gperm_name=\"view\"");

			$res2q = "SELECT gperm_groupid, gperm_itemid FROM ".$xoopsDB->prefix("group_permission")." WHERE gperm_itemid=$menuid AND gperm_modid=$module_id AND gperm_name=\"view\"";
			$res2 = mysql_query($res2q);

			if ( $res2 ) {
		  	  while ( $row = mysql_fetch_row ( $res2 ) ) {
		  		$groupid[] = $row[0];
		  	  }
			}
	
	   	$display = 0;
			$perm_itemid = $menuid; //intval($_GET['category_id']);
			$itemname = $myts->displayTarea($itemname);
		      foreach ($groupid as $gr){
	                	if ( in_array ($gr, $groupuser) && $display != 1) {

//does not seem to be used so commented out... jwe 7/23/04
//	                		if ($bold == 1) 
//	                		$block['content'] .= "<a class=menuMain href='$itemurl'>$itemname</a>";
//	                		else

//if check added to put menuTop class on first element... jwe 7/23/04
					if ($topwritten != 1) {
						$block['content'] .= "<a class=menuTop href='$itemurl'>$itemname</a>";
						$topwritten = 1;
					}
					else
					{
	                		$block['content'] .= "<a class=menuMain href='$itemurl'>$itemname</a>";
					}
	                		$display = 1;
	                	}
	                
	        	}
        	}
        }
	  // following line added jwe 7/23/04
	  $block['content'] .= "</td></tr></table>";
        return $block;
}
?>