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
//	Version du portable marchand bien, sauf quand aucun nom n'est ajouté au fichier joint
include 'header.php';
include_once XOOPS_ROOT_PATH.'/class/mail/phpmailer/class.phpmailer.php';

global $xoopsDB, $myts, $xoopsUser, $xoopsModule, $xoopsTpl, $xoopsConfig;
$block = array();
$groupuser = array();

if(!isset($HTTP_POST_VARS['title'])){
	$title = isset ($HTTP_GET_VARS['title']) ? $HTTP_GET_VARS['title'] : '';
}else {
	$title = $HTTP_POST_VARS['title'];
}
/*
if ($title=="") {

}
		
*/

$sql=sprintf("SELECT id_form,admin,groupe,email,expe FROM ".$xoopsDB->prefix("form_id")." WHERE desc_form='%s'",$title);
$res = mysql_query ( $sql ) or die('Erreur SQL !<br>'.$sql.'<br>'.mysql_error());
//global $nb_fichier;
 
      	$myts =& MyTextSanitizer::getInstance();
        $title = $myts->displayTarea($title);

if ( $res ) {
  while ( $row = mysql_fetch_array ( $res ) ) {
    $id_form = $row['id_form'];
    $admin = $row['admin'];
    $groupe = $row['groupe'];
    $email = $row['email'];
    $expe = $row['expe'];
  }
}


$result_form = $xoopsDB->query("SELECT margintop, marginbottom, itemurl, status FROM ".$xoopsDB->prefix("form_menu")." WHERE menuid='".$id_form);
       
$res_mod = $xoopsDB->query("SELECT mid FROM ".$xoopsDB->prefix("modules")." WHERE dirname='formulaire'");
if ($res_mod) {
	while ($row = mysql_fetch_row($res_mod))
		$module_id = $row[0];
}

$perm_name = 'Permission des catégories';
if ($xoopsUser) {$uid = $xoopsUser->getVar("uid");} else { $groupuser[0] = 3; }
$res_gp = $xoopsDB->query("SELECT groupid FROM ".$xoopsDB->prefix("groups_users_link")." WHERE uid= ".$uid);
if ( $res_gp ) {
  while ( $row = mysql_fetch_row ( $res_gp ) ) {
	$groupuser[] = $row[0];
  }
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
               	else redirect_header(XOOPS_URL."/modules/formulaire/index.php", 1, "pas la permission !!!");
        }
        $block['content'] .= "</ul>";
}



$form2 = "<center><font size=4>"._AM_FORM.$title."</font></center>";
     	//include_once(XOOPS_ROOT_PATH . "/class/uploader.php");
include_once(XOOPS_ROOT_PATH . "/modules/formulaire/upload_FA.php");

if( empty($_POST['submit']) ){
	$xoopsOption['template_main'] = 'formulaire.html';
	include_once XOOPS_ROOT_PATH.'/header.php';
	$criteria = new Criteria('ele_display', 1);
	$criteria->setSort('ele_order');
	$criteria->setOrder('ASC');
	$elements =& $formulaire_mgr->getObjects2($criteria,$id_form);
	$form = new XoopsThemeForm($form2, 'formulaire', XOOPS_URL.'/modules/formulaire/index.php?title='.$title.'');
	$form->setExtra("enctype='multipart/form-data'") ; // impératif !
     	include_once(XOOPS_ROOT_PATH . "/class/uploader.php");

	$count = 0;

	foreach( $elements as $i ){
		$ele_value = $i->getVar('ele_value');
		if (isset ($ele_value[0])) {
			$ele_value[0] = eregi_replace("'", "`", $ele_value[0]);
			$ele_value[0] = stripslashes($ele_value[0]); }
		$renderer =& new FormulaireElementRenderer($i);
		$form_ele =& $renderer->constructElement('ele_'.$i->getVar('ele_id'));
		if ($i->getVar('ele_type') == 'sep'){
			$ele_value = split ('<*>', $ele_value[0]);		
			foreach ($ele_value as $t){
				if (strpos($t, '<')!=false) {
					$ele_value[0] = $t;
			}	}
			$ele_value = split ('</', $ele_value[0]);			
			$hid = new XoopsFormHidden('ele_'.$i->getVar('ele_id'), $ele_value[0]);
			$form->addElement ($hid);
		}
		if ($i->getVar('ele_type') == 'areamodif'){
			$hid2 = new XoopsFormHidden('ele_'.$i->getVar('ele_id'), $ele_value[0]);
			$form->addElement ($hid2);
		}
		if ($i->getVar('ele_type') == 'upload'){
			$hid3 = new XoopsFormHidden($ele_value[1], $ele_value[1]);
			$form->addElement ($hid3);
		}
		$req = intval($i->getVar('ele_req'));
		$form->addElement($form_ele, $req);
		$count++;
		unset($hidden);
	}
	$form->addElement (new XoopsFormHidden ('counter', $count));
	$form->addElement(new XoopsFormButton('', 'submit', _SUBMIT, 'submit'));
	$form->assign($xoopsTpl);
	include_once XOOPS_ROOT_PATH.'/footer.php';
}else{
	$myts =& MyTextSanitizer::getInstance();
	$msg = '';
	$i=0;
	unset($_POST['submit']);
	foreach( $_POST as $k => $v ){
		if( preg_match('/ele_/', $k)){
			$n = explode("_", $k);
			$ele[$n[1]] = $v;
			$id[$n[1]] = $n[1];
		}
		if($k == 'xoops_upload_file'){
			$tmp = $k;
			$k = $v[0];			
			$v = $tmp;
			$n = explode("_", $k);
			$ele[$n[1]] = $v;
			$id[$n[1]] = $n[1];
		}
	}
	
	$sql = $xoopsDB->query("SELECT id_req from " . $xoopsDB->prefix("form_form")." order by id_req DESC");
	list($id_req) = $xoopsDB->fetchRow($sql);
	if ($id_req == 0) { $num_id = 1; }
	else if ($num_id <= $id_req) $num_id = $id_req + 1;

	$up = array();
	$desc_form = array();
	$value = null;
	foreach( $id as $i ){
		$element =& $formulaire_mgr->get($i);
		if( !empty($ele[$i]) ){
			//$pds = $element->getVar('pds');
			$id_form = $element->getVar('id_form');
			$ele_id = $element->getVar('ele_id');
			$ele_type = $element->getVar('ele_type');
			$ele_value = $element->getVar('ele_value');
			$ele_caption = $element->getVar('ele_caption');
			$ele_caption = stripslashes($ele_caption);
			$ele_caption = eregi_replace ("&#039;", "`", $ele_caption);
			$ele_caption = eregi_replace ("&quot;", "`", $ele_caption);
			$sql = $xoopsDB->query("SELECT desc_form from ".$xoopsDB->prefix("form_id")." WHERE id_form= ".$id_form.'');
			while ($row = mysql_fetch_array ($sql)) 
			{	$desc_form[] = $row['desc_form']; }
			
			switch($ele_type){
				case 'text':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$msg.= $myts->stripSlashesGPC($ele[$i])."<br></td></table><br>";
					$value = $ele[$i];
				break;
				case 'textarea':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$msg.= $myts->stripSlashesGPC($ele[$i])."<br></td></table><br>";
					$value = $ele[$i];
				break;
				case 'areamodif':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$msg.= $myts->stripSlashesGPC($ele[$i])."<br></td></table><br>";
					$value = $myts->stripSlashesGPC($ele[$i]);
				break;
				case 'radio':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$value = '';
					$opt_count = 1;
					while( $v = each($ele_value) ){
						if( $opt_count == $ele[$i] ){
							$msg.= $myts->stripSlashesGPC($v['key']).'<br>';
							$value = $v['key'];
						}
						$opt_count++;
					}
					$msg.= $myts->stripSlashesGPC("</td></table><br>");
				break;
				case 'yn':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$v = ($ele[$i]==2) ? _NO : _YES;
					$msg.= $myts->stripSlashesGPC($v)."<br></td></table><br>";
					$value = $ele[$i];
				break;
				case 'checkbox':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$value = '';
					$opt_count = 1;
					while( $v = each($ele_value) ){
						if( is_array($ele[$i]) ){
							if( in_array($opt_count, $ele[$i]) ){
								$msg.= $myts->stripSlashesGPC($v['key']).'<br>';
								$value = $value.':'.$v['key'];
							}
							$opt_count++;
						}else{
							if( !empty($ele[$i]) ){
								$msg.= $myts->stripSlashesGPC($v['key']).'<br>';
								$value = $value.':'.$v['key'];
							}
						}						
					}
					$msg.= $myts->stripSlashesGPC("</td></table><br>");
				break;
				case 'select':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$value = '';
					$opt_count = 1;
					while( $v = each($ele_value[2]) ){
						if( is_array($ele[$i]) ){
							if( in_array($opt_count, $ele[$i]) ){
								$msg.= $myts->stripSlashesGPC($v['key']).'<br>';
								$value = $v['key'];
							}
							$opt_count++;
						}else{
							while( $j = each($ele_value[2]) ){
								if( $opt_count == $ele[$i] ){
									$msg.= $myts->stripSlashesGPC($j['key']).'<br>';
									$value = $j['key'];
								}
								$opt_count++;
							}
						}
					}
					$msg.= $myts->stripSlashesGPC("</td></table><br>");
				break;
				//Marie le 20/04/04
				case 'areamodif':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$msg.= $myts->stripSlashesGPC($ele[$i])."<br></td></table><br>";
					$value = $ele[$i];
				break;
				case 'date':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$msg.= $myts->stripSlashesGPC($ele[$i])."<br></td></table><br>";
					$value = ''.$ele[$i];
				break;
				case 'sep':
			/*if ($ele_caption != '{SEPAR}') {
				$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
				$msg.= $myts->stripSlashesGPC($ele[$i])."<br></td></table><br>"; }
			else {
				$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>";
				$msg.= $myts->stripSlashesGPC($ele[$i])."</b></td></table><br>"; }*/
					$value = $myts->stripSlashesGPC($ele[$i]);
				break;
				case 'upload':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
							/************* UPLOAD *************/
				$img_dir = XOOPS_ROOT_PATH . "/modules/formulaire/upload" ;
				$allowed_mimetypes = array();
				foreach ($ele_value[2] as $v){ $allowed_mimetypes[] = 'image/'.$v[1];
				}
				// types proposés : pdf, doc, txt, gif, mpeg, jpeg
				$max_imgsize = $ele_value[1];
				$max_imgwidth = 12000;
				$max_imgheight = 12000;
				
				$fichier = $_POST["xoops_upload_file"][0] ; 
// teste si le champ a été rempli :
			if( !empty( $fichier ) || $fichier != "") {
// test si aucun fichier n'a été joint
				if($_FILES[$fichier]['error'] == '2' || $_FILES[$fichier]['error'] == '1') {	
					$error = sprintf(_FORMULAIRE_MSG_BIG, $xoopsConfig['sitename'])._FORMULAIRE_MSG_THANK;
					redirect_header(XOOPS_URL."/modules/formulaire/index.php?title=".$desc_form[0], 3, $error);
				}
				if(filesize($_FILES[$fichier]['tmp_name']) ==null) {	
					$value = $path = '';
					$filename = '';
					$msg.= $filename.'</TD></table><br>';
					break;
				}
				if($_FILES[$fichier]['size'] > $max_imgsize) {	
					$error = sprintf(_FORMULAIRE_MSG_UNSENT.$max_imgsize.' octets', $xoopsConfig['sitename'])._FORMULAIRE_MSG_THANK;
					redirect_header(XOOPS_URL."/modules/formulaire/index.php?title=".$desc_form[0], 3, $error);
				}
// teste si le fichier a été uploadé dans le répertoire temporaire:
				if( ! is_readable( $_FILES[$fichier]['tmp_name'])  || $_FILES[$fichier]['tmp_name'] == "" ) 
				{
				//redirect_header( XOOPS_URL.'/modules/formulaire/index.php?title='.$title , 2, _MD_FILEERROR ) ; 
					$path = '';
					$filename = '';
					$error = sprintf(_FORMULAIRE_MSG_UNSENT.$max_imgsize.' octets', $xoopsConfig['sitename'])._FORMULAIRE_MSG_THANK;
					redirect_header(XOOPS_URL."/modules/formulaire/index.php?title=".$desc_form[0], 3, $error);
				//	exit ;				
				}
// création de l'objet uploader
				$uploader = new XoopsMediaUploader_FA($img_dir, $allowed_mimetypes, $max_imgsize, $max_imgwidth, $max_imgheight);
// fichier uploadé conforme en dimension et taille, bien copié du répertoire temporaire au répertoire indiqué ??
				if( $uploader->fetchMedia( $fichier ) && $uploader->upload() ) { 
					$pos = strrpos($uploader->getSavedFileName(), '.');
					$type = 'image/'.substr($uploader->getSavedFileName(), $pos+1);
					if (!in_array ($type, $allowed_mimetypes)) {	//si ce type est autorisé
						$path = '';
						$filename = '';
						$error = sprintf(_FORMULAIRE_MSG_UNTYPE.implode(', ',$allowed_mimetypes))._FORMULAIRE_MSG_THANK;
						redirect_header(XOOPS_URL."/modules/formulaire/index.php?title=".$desc_form[0], 3, $error);
					}
// L'upload a réussi 
					$path = $uploader->getSavedDestination();
					$filename = $uploader->getSavedFileName();
					$up[$path] = $filename;
					$value = $path;
					$msg.= $filename.'</TD></table><br>';
// sinon l''upload a échoué : message d'erreur 
				} 
			}
			else {
				$value = $path = '';
				$filename = '';
				$msg.= $filename.'</TD></table><br>';
			}
				break;
				default:
				break;
			}

	if( is_object($xoopsUser) )
	{
  $uid = $xoopsUser->getVar("uid");
	}
	else {
				$uid =0;
				}

$date = date ("Y-m-d");
$value = addslashes ($value);
$sql="INSERT INTO ".$xoopsDB->prefix("form_form")." (id_form, id_req, ele_id, ele_type, ele_caption, ele_value, uid, date) VALUES (\"$id_form\", \"$num_id\", \"\", \"$ele_type\", \"$ele_caption\", \"$value\", \"$uid\", \"$date\")";
$result = $xoopsDB->queryF($sql);
//    if ($result == false) {
//        die('Erreur insertion : <br>' . $sql . '<br>');
//    } 
    		}
	}
	$msg = nl2br($msg);			
	

if( is_dir(FORMULAIRE_ROOT_PATH."/language/".$xoopsConfig['language']."/mail_template") ){
	$template_dir = FORMULAIRE_ROOT_PATH."/language/".$xoopsConfig['language']."/mail_template";
}else{
	$template_dir = FORMULAIRE_ROOT_PATH."/language/english/mail_template";
}

	$xoopsMailer =&getMailer();
	$xoopsMailer->multimailer->isHTML(true);
	$xoopsMailer->setTemplateDir($template_dir);
	$xoopsMailer->setTemplate('formulaire.tpl');
	$xoopsMailer->setSubject(_FORMULAIRE_MSG_SUBJECT._FORMULAIRE_MSG_FORM.$title);
	if( is_object($xoopsUser) ){
		$xoopsMailer->assign("UNAME", $xoopsUser->getVar("uname"));
		$xoopsMailer->assign("UID", $xoopsUser->getVar("uid"));
	}else{
		$xoopsMailer->assign("UNAME", $xoopsConfig['anonymous']);
		$xoopsMailer->assign("UID", '-');
	}
	$xoopsMailer->assign("IP", xoops_getenv('REMOTE_ADDR'));
	$xoopsMailer->assign("AGENT", xoops_getenv('HTTP_USER_AGENT'));
	$xoopsMailer->assign("MSG", $msg);
	$xoopsMailer->assign("TITLE", $title);

	foreach ($up as $k => $v ) {
		$path = $k;
		$filename = $v;
		if ($xoopsMailer->multimailer->AddAttachment($path,$filename,"base64","application/octet-stream"))
			{ }
		else { echo $xoopsMailer->getErrors();}
	}
	
	if( $xoopsModuleConfig['method'] == 'pm' && is_object($xoopsUser) ){
		$xoopsMailer->usePM();

	  	$sqlstr = "SELECT $xoopsDB->prefix" . "_users.uname AS UserName, $xoopsDB->prefix" . "_users.email AS UserEmail, $xoopsDB->prefix" . "_users.uid AS UserID FROM
	            ".$xoopsDB->prefix("groups").", ".$xoopsDB->prefix("groups_users_link").", ".$xoopsDB->prefix("users")." WHERE $xoopsDB->prefix" . "_users.uid = $xoopsDB->prefix" . "_groups_users_link.uid
	            AND $xoopsDB->prefix" . "_groups_users_link.groupid = $xoopsDB->prefix" . "_groups.groupid AND $xoopsDB->prefix" . "_groups.groupid = $groupe";

	$res = $xoopsDB->query($sqlstr);
        while (list($UserName,$UserEmail,$UserID) = $xoopsDB->fetchRow($res))
		{
			$xoopsMailer->setToEmails($UserEmail);
		}

	}else{
		$xoopsMailer->useMail();

		if( $expe == "on" ){
		  $email_expe   = $xoopsUser->getVar("email");
			$xoopsMailer->setToEmails($email_expe);
			$xoopsMailer->assign("EMAIL_EXPE", $email_expe);
		} else {$xoopsMailer->assign("EMAIL_EXPE", "");}


		if( $admin == "on" ){
			$xoopsMailer->setToEmails($xoopsConfig['adminmail']);
			$xoopsMailer->assign("ADMINEMAIL", $xoopsConfig['adminmail']);
			$xoopsMailer->assign("EMAIL", "");
			$xoopsMailer->assign("GROUPE", "");
		}else{

			$xoopsMailer->assign("ADMINEMAIL", " ");
			$xoopsMailer->setToEmails($email);
		  $xoopsMailer->assign("EMAIL", $email);

if (!empty($groupe) && ($groupe != "0")){
	$sql=sprintf("SELECT name FROM ".$xoopsDB->prefix("groups")." WHERE groupid='%s'",$groupe);
	$res = mysql_query ( $sql ) or die('Erreur SQL !<br>'.$sql.'<br>'.mysql_error());
	if ( $res ) {
  	while ( $row = mysql_fetch_row ( $res ) ) {
    	$gr = $row[0];
  		}
	}

			$xoopsMailer->assign("GROUPE", $gr);}

	  	$sqlstr = "SELECT $xoopsDB->prefix" . "_users.uname AS UserName, $xoopsDB->prefix" . "_users.email AS UserEmail, $xoopsDB->prefix" . "_users.uid AS UserID FROM
	            ".$xoopsDB->prefix("groups").", ".$xoopsDB->prefix("groups_users_link").", ".$xoopsDB->prefix("users")." WHERE $xoopsDB->prefix" . "_users.uid = $xoopsDB->prefix" . "_groups_users_link.uid
	            AND $xoopsDB->prefix" . "_groups_users_link.groupid = $xoopsDB->prefix" . "_groups.groupid AND $xoopsDB->prefix" . "_groups.groupid = $groupe";

	$res = $xoopsDB->query($sqlstr);
        while (list($UserName,$UserEmail,$UserID) = $xoopsDB->fetchRow($res))
		{
			$xoopsMailer->setToEmails($UserEmail);
		}
	}
}	
	$xoopsMailer->send(); 
	$sent = sprintf(_FORMULAIRE_MSG_SENT, $xoopsConfig['sitename'])._FORMULAIRE_MSG_THANK;
	unlink($path);
	unset ($up);
	redirect_header(XOOPS_URL."/index.php", 0, $sent);
}
?>