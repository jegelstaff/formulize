<?php
###############################################################################
##             Formulaire - Information submitting module for XOOPS          ##
##                    Copyright (c) 2003 NS Tai (aka tuff)                   ##
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
##  Author of this file: NS Tai (aka tuff)                                   ##
##  URL: http://www.brandycoke.com/                                          ##
##  Project: Formulaire                                                      ##
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


//Sélection des formulaires
	$sql="SELECT distinct desc_form, id_form FROM ".$xoopsDB->prefix("form_id");
	$res = mysql_query ( $sql );

if ( $res ) {
  while ( $row = mysql_fetch_row ( $res ) ) {
    $data[$row[1]] = $row[0];
  }
}

if( $op != 'addform' && $op != 'modform' && $op != 'renform' && $op != 'delform' && $op != 'showform' && $op != 'permform'){
	echo '
	<table class="outer" width="100%">
	<th><center>'._FORM_OPT.'</center></th>
	
	<tr class="head"><td><A HREF="menu_index.php">'._FORM_MENU.'</a></td></tr>
	<tr class="head"><td><A HREF="../../system/admin.php?fct=preferences&amp;op=showmod&amp;mod='.$module_id.'">'._FORM_PREF.'</a></td></tr>
	</table><br>';
}

/******************* Affichage des formulaires *******************/
if( $op != 'addform' && $op != 'modform' && $op != 'renform' && $op != 'delform' && $op != 'showform' && $op != 'permform'){
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
	echo '<tr><td class="head" ALIGN=center>'._FORM_EXPORT.'</td>
	      <td class="odd"><A HREF="export.php">
	      <center><img src="../images/xls.png" alt='._FORM_ALT_EXPORT.'>  </center></a></td></tr>';
	echo '<tr><td class="head" ALIGN=center>'._FORM_MODPERM.'</td>
	      <td class="odd"><A HREF="formindex.php?op=permform">
	      <center><img src="../images/perm.png" alt='._FORM_PERM.'> </center></a></td></tr>';
	
	foreach($data as $id => $titre) {
	   //echo '<form action="formindex.php?title='.$id.'" method="post">';	
	   echo '<tr><td class="head" ALIGN=center>'.$titre.'</td>';

	   echo '<td class="odd" align="center">  
	         <A HREF="renom.php?title='.$id.'">  <img src="../images/signature.png" alt='._FORM_RENOM.'>  </a>';

	   echo '<A HREF="formindex.php?title='.$id.'&op=delform">  <img src="../images/editdelete.png" alt='._FORM_SUP.'>  </a>';
	   
	   echo '<A HREF="formindex.php?title='.$id.'&op=modform">  <img src="../images/kedit.png" alt='._FORM_MODIF.'>  </a>';
	   
	   echo '<A HREF="formindex.php?title='.$id.'&op=showform">  <img src="../images/kfind.png" alt='._FORM_SHOW.'>  </a>';	   

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

//	$sql2 = sprintf("INSERT INTO %s (itemname,itemurl) VALUES ('%s', '%s')", $xoopsDB->prefix("form_menu"), $title, XOOPS_URL.'/modules/formulaire/index.php?title='.$data[$title].'');
	$sql2 = sprintf("INSERT INTO %s (itemname,itemurl) VALUES ('%s', '%s')", $xoopsDB->prefix("form_menu"), $title, XOOPS_URL.'/modules/formulaire/index.php?title='.$data[$title].'');
	$xoopsDB->queryF($sql2) or $eh->show("error insertion 2 in addform");

	redirect_header("index.php?title=$data[$title]",1,_FORMULAIRE_NEWFORMADDED);
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

	$sql2 = sprintf("UPDATE %s SET itemname='%s',itemurl='%s' WHERE itemname='%s'", $xoopsDB->prefix("form_menu"), $title2, XOOPS_URL.'/modules/formulaire/index.php?title='.$title2, $data[$title]);
	$xoopsDB->query($sql2) or $eh->show("error insertion 2 dans renform");
	redirect_header("formindex.php",1,_FORMULAIRE_FORMMOD);
}

function modform()
{
	global $xoopsDB, $HTTP_POST_VARS, $myts, $eh, $title, $data;
	//$title5 = $myts->makeTboxData4Save($HTTP_POST_VARS["desc_form5"]);
	if (empty($data[$title])) {
		redirect_header("formindex.php", 2, _MD_ERRORTITLE);
	}
	redirect_header("index.php?title=$data[$title]",2,_FORMULAIRE_FORMCHARG);
}

function delform()
{
	global $xoopsDB, $HTTP_POST_VARS, $myts, $eh, $title, $data;
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

	redirect_header("formindex.php",3,_FORMULAIRE_FORMDEL._FORMULAIRE_MSG_SUP);
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
		redirect_header("formindex.php",2,_FORMULAIRE_NOTSHOW.$data[$title]._FORMULAIRE_NOTSHOW2);
		else redirect_header("consult.php?form=".$title,2,_FORMULAIRE_FORMSHOW.$data[$title]);
	}
		

}

function permform()
{
	global $xoopsDB, $xoopsModule;
	$module_id = $xoopsModule->getVar('mid');

	$sql="SELECT id_form,desc_form FROM ".$xoopsDB->prefix("form_id");
	$res = mysql_query ( $sql );
	if ( $res ) {
		$tab = array();
		while ( $row = mysql_fetch_array ( $res ) ) {
		    $tab[$row['id_form']] = $row['desc_form']." (".$row['id_form'].")";
		  }
	}
		
	$title_of_form = 'Permissions d\'accès aux formulaires';
	$perm_name = 'Permission des catégories';
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
}

include 'footer.php';
    xoops_cp_footer();

?>




