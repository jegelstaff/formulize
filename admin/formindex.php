<?php
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

include("admin_header.php");

if ( file_exists("../language/".$xoopsConfig['language']."/main.php") ) {
	include "../language/".$xoopsConfig['language']."/main.php";
} else {
	include "../language/english/main.php";
}

if(!isset($HTTP_POST_VARS['op'])){
	$op = isset ($HTTP_GET_VARS['op']) ? $HTTP_GET_VARS['op'] : 'main';
}else {
	$op = $HTTP_POST_VARS['op'];
}
if(!isset($HTTP_POST_VARS['title'])){
	$title = isset ($HTTP_GET_VARS['title']) ? $HTTP_GET_VARS['title'] : '0';
}else {
	$title = $HTTP_POST_VARS['title'];
}

// Classe permissions
include_once XOOPS_ROOT_PATH.'/class/xoopsform/grouppermform.php';
$module_id = $xoopsModule->getVar('mid'); // recupere le numero id du module

$n = 0;
$m = 0;
//include "../include/functions.php";
include_once XOOPS_ROOT_PATH."/class/xoopstree.php";
include_once XOOPS_ROOT_PATH."/class/xoopslists.php";
include_once XOOPS_ROOT_PATH."/include/xoopscodes.php";
include_once XOOPS_ROOT_PATH."/class/module.errorhandler.php";
$myts =& MyTextSanitizer::getInstance();
$eh = new ErrorHandler;

global $title2, $op, $data;

	xoops_cp_header();


//Sélection des formulizes
	$sql="SELECT distinct desc_form, id_form FROM ".$xoopsDB->prefix("form_id");
	$res = mysql_query ( $sql );

if ( $res ) {
  while ( $row = mysql_fetch_row ( $res ) ) {
    $data[$row[1]] = $row[0];
  }
}

if( $op != 'addform' && $op != 'modform' && $op != 'renform' && $op != 'delform' && $op != 'showform' && $op != 'permform' && $op != 'permlinks'){ // permlinks condition added jwe 08/29/04
	echo '
	<table class="outer" width="100%">
	<th><center>'._FORM_OPT.'</center></th>
	
	<tr class="head"><td><A HREF="menu_index.php">'._FORM_MENU.'</a></td></tr>
	<tr class="head"><td><A HREF="../../system/admin.php?fct=preferences&amp;op=showmod&amp;mod='.$module_id.'">'._FORM_PREF.'</a></td></tr>
	</table><br>';
}

/******************* Affichage des formulizes *******************/
if( $op != 'addform' && $op != 'modform' && $op != 'renform' && $op != 'delform' && $op != 'showform' && $op != 'permform' && $op != 'permlinks'){ // permlinks condition added jwe 08/29/04

	// javascript to confirm deletion added by jwe 8/30/04

	print "<script type='text/javascript'>\n";
	print "	function confirmdel() {\n";
	print "		var answer = confirm ('" . _AM_CONFIRM_DEL . "')\n";
	print "		if (answer)\n";
	print "		{\n";
	print "			return true;\n";
	print "		}\n";
	print "		else\n";
	print "		{\n";
	print "			return false;\n";
	print "		}\n";
	print "	}\n";
	print "</script>\n";




	echo '
	<table class="outer" width="100%">

	<th><center>'._AM_FORMUL.'</center></th>
	<th><center>'._FORM_ACT.'</center></th>';

	$renom = new XoopsFormButton('', 'renom', _FORM_RENOM_IMG, 'submit');
	$hidden_renom = new XoopsFormHidden('op', 'renform');
	$sup = new XoopsFormButton('', 'sup', _FORM_SUP, 'submit');
	$hidden_sup = new XoopsFormHidden('op', 'delform');
	$mod = new XoopsFormButton('', 'modif', _FORM_MODIF, 'submit');
	$hidden_mod = new XoopsFormHidden('op', 'modform');
	$show = new XoopsFormButton('', 'show', _FORM_SHOW, 'submit');
	$hidden_show = new XoopsFormHidden('op', 'showform');
	
	echo '<tr><td class="head" ALIGN=center>'._FORM_CREAT.'</td>
	      <td class="odd"><A HREF="mailindex.php">
	      <center><img src="../images/filenew2.png" alt='._FORM_NEW.'>  </center></a></td></tr>';
	//old export section, not used any more
	//echo '<tr><td class="head" ALIGN=center>'._FORM_EXPORT.'</td>
	//      <td class="odd"><A HREF="export.php">
	//      <center><img src="../images/xls.png" alt='._FORM_ALT_EXPORT.'>  </center></a></td></tr>';
	echo '<tr><td class="head" ALIGN=center>'._FORM_MODPERM.'</td>
	      <td class="odd"><A HREF="formindex.php?op=permform">
	      <center><img src="../images/perm.png" alt='._FORM_PERM.'> </center></a></td></tr>';

	echo '<tr><td class="head" ALIGN=center>'._FORM_MODPERMLINKS.'</td>
	      <td class="odd"><A HREF="formindex.php?op=permlinks">
	      <center><img src="../images/perm.png" alt='._FORM_PERMLINKS.'> </center></a></td></tr>';
	
	foreach($data as $id => $titre) {
	   //echo '<form action="formindex.php?title='.$id.'" method="post">';	
	   echo '<tr><td class="head" ALIGN=center>'.$titre.'</td>';

	   echo '<td class="odd" align="center">  
	         <A HREF="renom.php?title='.$id.'">  <img src="../images/signature.png" alt='._FORM_RENOM.'>  </a>';

	   echo '<A HREF="formindex.php?title='.$id.'&op=delform" onclick="return confirmdel();">  <img src="../images/editdelete.png" alt='._FORM_SUP.'>  </a>';
	   
	   echo '<A HREF="formindex.php?title='.$id.'&op=modform">  <img src="../images/kedit.png" alt='._FORM_MODIF.'>  </a>';
	   
	   //old display entries section, not used anymore 
	   //echo '<A HREF="formindex.php?title='.$id.'&op=showform">  <img src="../images/kfind.png" alt='._FORM_SHOW.'>  </a>';	   

	   echo '<A HREF="mailindex.php?title='.$titre.'">  <img src="../images/xfmail.png" alt='._FORM_ADD.'>  </a></td>';	   
	}
	echo '</tr></table>';

}


function addform()
{
	global $xoopsDB, $HTTP_POST_VARS, $myts, $eh;
	$data[$title] = $myts->makeTboxData4Save($HTTP_POST_VARS["desc_form"]);
	$admin = $myts->makeTboxData4Save($HTTP_POST_VARS["admin"]);
	$groupe = $myts->makeTboxData4Save($HTTP_POST_VARS["groupe"]);
	$email = $myts->makeTboxData4Save($HTTP_POST_VARS["email"]);
	$expe = $myts->makeTboxData4Save($HTTP_POST_VARS["expe"]);
	if (empty($data[$title])) {
		redirect_header("formindex.php", 2, _MD_ERRORTITLE);
	}
	if((!empty($email)) && (!eregi("^[_a-z0-9.-]+@[a-z0-9.-]{2,}[.][a-z]{2,3}$",$email))){

		redirect_header("formindex.php", 2, _MD_ERROREMAIL);
	}
	if (empty($email) && empty($admin) && $groupe=="0" && empty($expe)) {
		redirect_header("formindex.php", 2, _MD_ERRORMAIL);
	}
	$data[$title] = stripslashes($data[$title]);
	$data[$title] = eregi_replace ("'", "`", $data[$title]);
	$data[$title] = eregi_replace ('"', "`", $data[$title]);
	$data[$title] = eregi_replace ('&', "_", $data[$title]);

	$sql = sprintf("INSERT INTO %s (desc_form, admin, groupe, email, expe) VALUES ('%s', '%s', '%s', '%s', '%s')", $xoopsDB->prefix("form_id"), $data[$title], $admin, $groupe, $email, $expe);
	$xoopsDB->queryF($sql) or $eh->show("error insertion 1 dans addform");

//	$sql2 = sprintf("INSERT INTO %s (itemname,itemurl) VALUES ('%s', '%s')", $xoopsDB->prefix("form_menu"), $title, XOOPS_URL.'/modules/formulize/index.php?title='.$data[$title].'');
	$sql2 = sprintf("INSERT INTO %s (itemname,itemurl) VALUES ('%s', '%s')", $xoopsDB->prefix("form_menu"), $title, XOOPS_URL.'/modules/formulize/index.php?title='.$data[$title].'');
	$xoopsDB->queryF($sql2) or $eh->show("error insertion 2 in addform");

	redirect_header("index.php?title=$data[$title]",1,_formulize_NEWFORMADDED);
}

function renform()
{
	global $xoopsDB, $HTTP_POST_VARS, $myts, $eh, $title, $title2, $data;
	$title2 = $myts->makeTboxData4Save($HTTP_POST_VARS["title2"]);
	//$title3 = $myts->makeTboxData4Save($HTTP_POST_VARS["desc_form3"]);
	if (empty($data[$title])) {
		redirect_header("formindex.php", 2, _MD_ERRORTITLE);
	}
	$title2 = stripslashes($title2);
	$title2 = eregi_replace ("'", "`", $title2);
	$title2 = eregi_replace ('"', "`", $title2);
	$title2 = eregi_replace ('&', "_", $title2);

	$sql = sprintf("UPDATE %s SET desc_form='%s' WHERE desc_form='%s'", $xoopsDB->prefix("form_id"), $title2, $data[$title]);
	$xoopsDB->queryF($sql) or $eh->show("error insertion 1 dans renform");

	$sql2 = sprintf("UPDATE %s SET itemname='%s',itemurl='%s' WHERE itemname='%s'", $xoopsDB->prefix("form_menu"), $title2, XOOPS_URL.'/modules/formulize/index.php?title='.$title2, $data[$title]);
	$xoopsDB->query($sql2) or $eh->show("error insertion 2 dans renform");
	redirect_header("formindex.php",1,_formulize_FORMMOD);
}

function modform()
{
	global $xoopsDB, $HTTP_POST_VARS, $myts, $eh, $title, $data;
	//$title5 = $myts->makeTboxData4Save($HTTP_POST_VARS["desc_form5"]);
	if (empty($data[$title])) {
		redirect_header("formindex.php", 2, _MD_ERRORTITLE);
	}
	redirect_header("index.php?title=$data[$title]",2,_formulize_FORMCHARG);
}

function delform()
{



	global $xoopsDB, $HTTP_POST_VARS, $myts, $eh, $title, $data, $xoopsModule; // module ID added by JWE 10/11/04
	$module_id = $xoopsModule->getVar('mid');

	//$title4 = $myts->makeTboxData4Save($HTTP_POST_VARS["desc_form4"]);
	$sql=sprintf("SELECT id_form FROM ".$xoopsDB->prefix("form_id")." WHERE desc_form='%s'",$data[$title]);
	$res = mysql_query ( $sql ) or die('Erreur SQL !<br>'.$requete.'<br>'.mysql_error());
	if ( $res ) {
  	while ( $row = mysql_fetch_row ( $res ) ) {
    	$id_form = $row[0];
  		}
	}
	$sql = sprintf("DELETE FROM %s WHERE desc_form = '%s'", $xoopsDB->prefix("form_id"), $data[$title]);
	$xoopsDB->queryF($sql) or $eh->show("error supression 1 dans delform");

	$sql = sprintf("DELETE FROM %s WHERE id_form = '%u'", $xoopsDB->prefix("form"), $title);
	$xoopsDB->queryF($sql) or $eh->show("error supression 2 dans delform");

	$sql = sprintf("DELETE FROM %s WHERE itemname = '%s'", $xoopsDB->prefix("form_menu"), $data[$title]);
	$xoopsDB->queryF($sql) or $eh->show("error supression 3 dans delform");

	$sql = sprintf("DELETE FROM %s WHERE id_form = '%u'", $xoopsDB->prefix("form_form"), $title);
	$xoopsDB->queryF($sql) or $eh->show("error supression 4 dans delform");
	
	$perm_name = 'Permission des catégories';
	//xoops_groupperm_deletebymoditem ($module_id>$perm_name>$id_form) ;

	xoops_notification_deletebyitem ($module_id, "form", $id_form); // added by jwe-10/10/04 to handle removing notifications for a form once it's gone

	redirect_header("formindex.php",3,_formulize_FORMDEL._formulize_MSG_SUP);
}

function showform()
{
	global $xoopsDB, $HTTP_POST_VARS, $myts, $eh, $title, $data;
	//$title5 = $myts->makeTboxData4Save($HTTP_POST_VARS["desc_form5"]);
	if (empty($data[$title])) {
		redirect_header("formindex.php", 2, _MD_ERRORTITLE);
	}
	
	$sql = "SELECT count(*) FROM ".$xoopsDB->prefix("form_form")." WHERE id_form= ".$title;	
	$res = mysql_query($sql);		
	if ($res){
       		list($count) = mysql_fetch_row($res);
		if ($count == 0)
		redirect_header("formindex.php",2,_formulize_NOTSHOW.$data[$title]._formulize_NOTSHOW2);
		else redirect_header("consult.php?form=".$title,2,_formulize_FORMSHOW.$data[$title]);
	}
		

}

//
// ADDED BY JWE 8/29/04 TO CREATE A PERMISSION PAGE USED TO ASSIGN SCOPE TO LINKS WITH OTHER FORMS
//

function permlinks()
{

	global $xoopsDB, $xoopsModule;
	$module_id = $xoopsModule->getVar('mid');

	$currentperm = $_GET['currentperm'];
	if(!$currentperm)
	{
		$currentperm = "none";
	}

	// NEED TO GATHER ALL LINKS INTO ARRAYS FOR DRAWING TO THE SELECTION BOX
	$getlinksq = "SELECT id_form, ele_caption, ele_id FROM " . $xoopsDB->prefix("form") . " WHERE ele_type=\"select\" AND ele_value LIKE '%#*=:*%'";
	// print "$getlinksq<br>";
	$resgetlinksq = $xoopsDB->query($getlinksq);
	while ($rowlinksq = $xoopsDB->fetchRow($resgetlinksq))
	{
		//print_r($rowlinksq);
		//print "<br>";
		$linkformids[] = $rowlinksq[0];
		$linkcaptions[] = $rowlinksq[1];
		$linkeleids[] = $rowlinksq[2];
	}
	// now get all the form names for each form that has a link

	if($linkformids) // if a link was found...
	{

	foreach($linkformids as $aform)
	{
	$getlformnamesq = "SELECT desc_form FROM " . $xoopsDB->prefix("form_id") . " WHERE id_form = $aform";
	$resq = $xoopsDB->query($getlformnamesq);
	$rowq = $xoopsDB->fetchRow($resq);
	$linkformnames[] = $rowq[0];
	}
	// now the formid array is the ids
	// cations array is the linked select box names
	// names is the form names, 
	// eleids is the element ids, all indexed together
	array_multisort($linkformnames, $linkformids, $linkcaptions, $linkeleids);

	// set the item array...
	// 1. get a list of all the groups
	// 2. put the list into the array

	array($grouplist_id);
	array($grouplist_name);
	$grouplistq = "SELECT groupid, name FROM " . $xoopsDB->prefix("groups");
	$resgrouplistq = $xoopsDB->query($grouplistq); 
	while ($rowgrouplistq = $xoopsDB->fetchRow($resgrouplistq))
	{
		$grouplist_id[] = $rowgrouplistq[0];
		$grouplist_name[] = $rowgrouplistq[1];
	}

	// javascript function to reload page everytime someone selects a new linkbox...
		print "<script type='text/javascript'>\n";
		print "<!--//\n";
		print "	function redrawpage(perm) {\n";
//		print "	alert(perm)\n";
		print "		window.location = 'formindex.php?op=permlinks&currentperm=' + perm;\n";
		print "	}\n";
		print "//--></script>\n\n";

	print "<table class='outer' cellspacing='1'><tr><td class=head><center><form name=permselect action=formindex.php?op=permlinks method=post>\n";
	print "<p><b>" . _AM_FORM_CURPERMLINKS . "</b><br>";
	print "<SELECT name=currentperm size=1 onChange='redrawpage(document.permselect.currentperm.value)'>\n";
	for($i=0;$i<count($linkformnames);$i++)
	{
		if($currentperm == "none")
		{
			$currentperm = $linkeleids[$i];
		}
		print "<option value=\"" . $linkeleids[$i]. "\"";
		if ($currentperm == $linkeleids[$i])
		{
			print " selected";
		}
		print ">" . $linkformnames[$i] . ": " . $linkcaptions[$i] . "</option>\n";
	}

	print "</SELECT></p></form></center></td></tr></table>\n";

	$title_of_form = "";
	$perm_name = $currentperm;
	$perm_desc = '';
	$form = new XoopsGroupPermForm($title_of_form, $module_id, $perm_name, $perm_desc); 
	for($i=0;$i<count($grouplist_id);$i++)
	{
		$form->addItem($grouplist_id[$i], $grouplist_name[$i]);
	}

	echo $form->render();
	
	}
	else // of if no link was found
	{
	
		print "NO LINKS!";
		
	} // end of if a link was found
}



function permform()
{
	global $xoopsDB, $xoopsModule;
	$module_id = $xoopsModule->getVar('mid');

	$currentperm = $_GET['currentperm'];
	if(!$currentperm)
	{
		$currentperm = "view";
	}

	$sql="SELECT id_form,desc_form FROM ".$xoopsDB->prefix("form_id");
	$res = mysql_query ( $sql );
	if ( $res ) {
		$tab = array();
		while ( $row = mysql_fetch_array ( $res ) ) {
		    $tab[$row['id_form']] = $row['desc_form']." (".$row['id_form'].")";
		  }
	}
	
	// javascript function to reload page everytime someone selects a new perm...
		print "<script type='text/javascript'>\n";
		print "<!--//\n";
		print "	function redrawpage(perm) {\n";
//		print "	alert(perm)\n";
		print "		window.location = 'formindex.php?op=permform&currentperm=' + perm;\n";
		print "	}\n";
		print "//--></script>\n\n";

	print "<table><tr><td valign=top><table class='outer' cellspacing='1'><tr><td class=head><center><form name=permselect action=formindex.php?op=permform method=post>\n";
	print "<p><b>" . _AM_FORM_CURPERM . "</b><br>";
	print "<SELECT name=currentperm size=1 onChange='redrawpage(document.permselect.currentperm.value)'>\n";
	print "<option value=view";
	if($currentperm == "view")
	{
		print " selected";
	}
	print ">" . _AM_FORM_PERMVIEW . "</option>\n";
	print "<option value=add";
	if($currentperm == "add")
	{
		print " selected";
	}
	print ">" . _AM_FORM_PERMADD . "</option>\n";
	print "<option value=admin";
	if($currentperm == "admin")
	{
		print " selected";
	}
	print ">" . _AM_FORM_PERMADMIN . "</option>\n";
	print "</SELECT></p></form></center></td></tr></table>\n";

	print '</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td valign=top>';
	print '<center><a href="../admin/formindex.php">' . _AM_GOTO_MAIN . ' <br><img src="../images/formulize.gif" height=35></a></center></td></tr></table>';


	$title_of_form = "";
	$perm_name = $currentperm;
	$perm_desc = '';
	$form = new XoopsGroupPermForm($title_of_form, $module_id, $perm_name, $perm_desc); 
	foreach($tab as $item_id => $item_name) {
		if($item_name != "") $form->addItem($item_id, $item_name);
	} 
	echo $form->render();

}


switch ($op) {
case "addform":
	addform();
	break;
case "renform":
	renform();
	break;
case "modform":
	modform();
	break;
case "delform":
	delform();
	break;
case "showform":
	showform();
	break;
case "permform":
	permform();
	break;

// added jwe 8/29/04
case "permlinks":
	permlinks();
	break;

}

include 'footer.php';
    xoops_cp_footer();

?>




