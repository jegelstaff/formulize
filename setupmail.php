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


if( is_dir(formulize_ROOT_PATH."/language/".$xoopsConfig['language']."/mail_template") ){
	$template_dir = formulize_ROOT_PATH."/language/".$xoopsConfig['language']."/mail_template";
}else{
	$template_dir = formulize_ROOT_PATH."/language/english/mail_template";
}

	$xoopsMailer =&getMailer();
	$xoopsMailer->multimailer->isHTML(true);
	$xoopsMailer->setTemplateDir($template_dir);
	$xoopsMailer->setTemplate('formulize.tpl');
	$xoopsMailer->setSubject(_formulize_MSG_SUBJECT._formulize_MSG_FORM.$title);
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
	//$xoopsMailer->send();
?>