<?php
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
function block_FORMULAIREMENU_show() {
        global $xoopsDB, $xoopsUser, $xoopsModule, $myts;
		    $myts =& MyTextSanitizer::getInstance();

        $block = array();
        $groups = array();
        $block['title'] = _MB_FORMULAIREMENU_TITLE;
        $block['content'] = "";

        $result = $xoopsDB->query("SELECT menuid, position, indent, itemname, margintop, marginbottom, itemurl, bold, membersonly, mainmenu, status FROM ".$xoopsDB->prefix("form_menu")." ORDER BY position");
       
        // Gestion des permissions 
	include_once XOOPS_ROOT_PATH.'/class/xoopsform/grouppermform.php';

	$res4 = $xoopsDB->query("SELECT mid FROM ".$xoopsDB->prefix("modules")." WHERE dirname='formulaire'");
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

        while (list($menuid, $position, $indent, $itemname, $margintop, $marginbottom, $itemurl, $bold, $membersonly, $mainmenu, $status) = $xoopsDB->fetchRow($result)) {
  		//echo $itemname." <br>".$status."<br>".$menuid;
  		                if ( $status == 1 ) {
	                $groupid = array();
	                $res2 = $xoopsDB->query("SELECT gperm_groupid,gperm_itemid FROM ".$xoopsDB->prefix("group_permission")." WHERE gperm_itemid= ".$menuid." AND gperm_modid=".$module_id);
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
	                		if ($bold == 1) 
	                		$block['content'] .= "<table cellspacing='0' border='0'><li><div style='margin-left: $indent px; margin-right: 0; margin-top: $margintop px; margin-bottom: $marginbottom px;'><a style='font-weight: bold' href='$itemurl'>$itemname</a></li></table>";
	                		else
	                		$block['content'] .= "<table cellspacing='0' border='0'><li><div style='margin-left: $indent px; margin-right: 0; margin-top: $margintop px; margin-bottom: $marginbottom px;'><a style='font-weight: normal' href='$itemurl'>$itemname</a></li></table>";	                		
	                		$display = 1;
	                	}
	                
	        	}
        	}
        }
        return $block;
}
?>