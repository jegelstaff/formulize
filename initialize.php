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

//include 'header.php'; //redundant as all the code that this calls is now called from within each function (since the functions operate on a standalone basis)

include_once XOOPS_ROOT_PATH.'/class/mail/phpmailer/class.phpmailer.php';

global $xoopsDB, $myts, $xoopsUser, $xoopsModule, $xoopsTpl, $xoopsConfig;
// code commented as part of switch over to new interface - July 28, 2005
/*$block = array();
$groupuser = array();

//userobject variable gathering moved up here by jwe 7/23/04

if( is_object($xoopsUser) )
{
	$uid = $xoopsUser->getVar("uid");
	$realuid = $uid; // used in the case of proxy submissions
	$usernamejwe = $xoopsUser->getVar("uname");
	$realnamejwe = $xoopsUser->getVar("name");
}
else {
	$uid =0;
}

// print "*$realnamejwe*"; //JWE DEBUG CODE
*/
// altered sept 8 to use fid instead of title
if(!isset($_POST['fid'])){
	$fid = isset($_GET['fid']) ? $_GET['fid'] : '';
}else {
	$fid = $_POST['fid'];
}
/*
if ($title=="") {
}
		
*/

// query modified to include singleentry - July 28, 2005 -- part of switch to new intnerface
$sql=sprintf("SELECT admin,groupe,email,expe,singleentry,desc_form FROM ".$xoopsDB->prefix("formulize_id")." WHERE id_form='$fid'");
$res = mysql_query ( $sql ) or die('Erreur SQL !<br>'.$sql.'<br>'.mysql_error());
//global $nb_fichier;
 
if ( $res ) {
  while ( $row = mysql_fetch_array ( $res ) ) {
    $id_form = $fid;
    $admin = $row['admin'];
    $groupe = $row['groupe'];
    $email = $row['email'];
    $expe = $row['expe'];
    $singleentry = $row['singleentry'];
    $desc_form = $row['desc_form'];
  }
}

$myts =& MyTextSanitizer::getInstance();
$title = $myts->displayTarea($desc_form);
$xoopsTpl->assign('xoops_pagetitle', $title);

// new logic to handle invoking new interface
// 1. determine if the form is a single or multi
// 1.5 if multi->displayEntries, if single...
// 2. if single, determine if the user has group or global scope
// 2.5 if yes->displayEntries, if no...
// 3 displayForm

// get the global or group permission
$groups = $xoopsUser ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS;
$mid = $xoopsModule->getVar('mid');
$gperm_handler = &xoops_gethandler('groupperm');
$view_globalscope = $gperm_handler->checkRight("view_globalscope", $id_form, $groups, $mid);
$view_groupscope = $gperm_handler->checkRight("view_groupscope", $id_form, $groups, $mid);

// THIS CHECK TURNED OFF
// THEORY IS THAT VIEW_FORM PERMISSION IS THE MASTER CONTROL
// IF SOMEONE HAS VIEW_FORM, THEN NO MATTER HOW THEY GET TO THE FORM, THEY SHOULD BE ALLOWED IN
// ADDED VIEW_FORM CHECK BELOW -- Mar 15 2006, jwe
/*
// check whether user can bypass the form menu for this form.  If not, then check whether they have access to the form menu.  If not, deny access to the form
if(!$bypass_form_menu = $gperm_handler->checkRight("bypass_form_menu", $id_form, $groups, $mid)) {

	// check to see if the user has access to the Form Menu.  If they do not, then redirect to home
	// get the block ID for the Form Menu
	$block_sql = "SELECT bid FROM " . $xoopsDB->prefix("newblocks") . " WHERE mid=$mid AND name='Form Menu'";
	$block_res = $xoopsDB->query($block_sql);
	$block_array = $xoopsDB->fetchArray($block_res);
	$bid = $block_array['bid']; // assumption is there will only be one record returned

	if(!$view_formmenu = $gperm_handler->checkRight("block_read", $bid, $groups, 1)) {
		redirect_header(XOOPS_URL, 3, _formulize_NO_PERMISSION);
	}
}
*/

if(!$view_form = $gperm_handler->checkRight("view_form", $id_form, $groups, $mid)) {
	redirect_header(XOOPS_URL, 3, _formulize_NO_PERMISSION);
}

// check for $xoopsUser added by skenow.  Forces anons to only see the form itself and never the list of entries view.
// Really, we should build in better permission/configuration control so that more precise 
// control over anon behaviour is possible
if((!$singleentry OR ($view_globalscope OR $view_groupscope)) AND $xoopsUser) {
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/entriesdisplay.php";
	displayEntries($id_form); // if it's a multi, or if a single and they have group or global scope
} else {
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
	displayForm($id_form, "", "", "", "{NOBUTTON}"); // if it's a single and they don't have group or global scope
}



?>
