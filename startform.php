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


$result_form = $xoopsDB->query("SELECT margintop, marginbottom, itemurl, status FROM ".$xoopsDB->prefix("form_menu")." WHERE menuid='".$id_form);
       
$res_mod = $xoopsDB->query("SELECT mid FROM ".$xoopsDB->prefix("modules")." WHERE dirname='formulize'");
if ($res_mod) {
	while ($row = mysql_fetch_row($res_mod))
		$module_id = $row[0];
}

$gperm_handler =& xoops_gethandler('groupperm');

if ($result_form) {
	while ($row = mysql_fetch_row ($result_form)) {
		$margintop = $row[0];
		$marginbottom = $row[1];
		$itemurl = $row[2];
		$status = $row[3];
	}
}
else $status = 0;

if ( $status == 1 ) {
	$groupid = array();
        $res2 = $xoopsDB->query("SELECT gperm_groupid,gperm_itemid FROM ".$xoopsDB->prefix("group_permission")." WHERE gperm_itemid= ".$menuid." AND gperm_modid=".$module_id);
	if ( $res2 ) {
	  while ( $row = mysql_fetch_row ( $res2 ) ) {
		$groupid[] = $row[0];
	  }
	}

	$block['content'] .= "<ul>";
        $display = 0;
	$perm_itemid = $menuid; //intval($_GET['category_id']);
        foreach ($groupid as $gr){
               	if ( in_array ($gr, $groupuser) && $display != 1) {
               		$block['content'] .= "<table cellspacing='0' border='0'><tr><td><li><div style='margin-left: $indent px; margin-right: 0; margin-top: $margintop px; margin-bottom: $marginbottom px;'>
               		<a style='font-weight: normal' href='$itemurl'>$title</a></li></td></tr></table>";
               		$display = 1;
               	}
               	else redirect_header(XOOPS_URL."/modules/formulize/index.php", 1, "pas la permission !!!");
        }
        $block['content'] .= "</ul>";
}



// following line modified to remove the name of the module from before the form's own name - jwe 07/23/04
$form2 = "<center><h3>$title</h3></center>";
     	//include_once(XOOPS_ROOT_PATH . "/class/uploader.php");
include_once(XOOPS_ROOT_PATH . "/modules/formulize/upload_FA.php");

?>