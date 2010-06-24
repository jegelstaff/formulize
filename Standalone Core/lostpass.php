<?php
/**
* All functions for lost password generator are going through here.
*
* Form and process for sending a new password to a user
* 
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		XOOPS
* @author		http://www.xoops.org The XOOPS Project
* @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id: lostpass.php 9285 2009-08-30 17:35:45Z m0nty $
* 
* @package	kernel 
* @subpackage	users
**/

$xoopsOption['pagetype'] = 'user';
/** Include mainfile.php - required */
include 'mainfile.php';

if(!empty($_POST)) foreach($_POST as $k => $v) ${$k} = StopXSS($v);
if(!empty($_GET)) foreach($_GET as $k => $v) ${$k} = StopXSS($v);
$email = (isset($_GET['email']))?trim(StopXSS($_GET['email'])):((isset($_POST['email']))?trim(StopXSS($_POST['email'])):$email);

if($email == '') {redirect_header('user.php',2,_US_SORRYNOTFOUND);}

$myts =& MyTextSanitizer::getInstance();
$member_handler =& xoops_gethandler('member');
$criteria = new CriteriaCompo();
$criteria->add(new Criteria('email', $myts->addSlashes($email)));
$criteria->add(new Criteria('level', '-1', '!='));
$getuser =& $member_handler->getUsers($criteria);

if(empty($getuser))
{
	$msg = _US_SORRYNOTFOUND;
	redirect_header('user.php',2,$msg);
}
else
{
	include_once ICMS_ROOT_PATH.'/class/icms_Password.php';
        $icmspass = new icms_Password();

	$code = isset($_GET['code']) ? trim(StopXSS($_GET['code'])) : '';
	$areyou = substr($getuser[0]->getVar('pass'), 0, 5);
	$enc_type = intval($icmsConfigUser['enc_type']);
	if($code != '' && $areyou == $code)
	{
		$newpass = $icmspass->icms_createSalt(8);
		$salt = $icmspass->icms_createSalt();
		$pass = $icmspass->icms_encryptPass($newpass, $salt);
		$xoopsMailer =& getMailer();
		$xoopsMailer->useMail();
		$xoopsMailer->setTemplate('lostpass2.tpl');
		$xoopsMailer->assign('SITENAME', $icmsConfig['sitename']);
		$xoopsMailer->assign('ADMINMAIL', $icmsConfig['adminmail']);
		$xoopsMailer->assign('SITEURL', ICMS_URL.'/');
		$xoopsMailer->assign('IP', $_SERVER['REMOTE_ADDR']);
		$xoopsMailer->assign('NEWPWD', $newpass);
		$xoopsMailer->setToUsers($getuser[0]);
		$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
		$xoopsMailer->setFromName($icmsConfig['sitename']);
		$xoopsMailer->setSubject(sprintf(_US_NEWPWDREQ,ICMS_URL));
		if(!$xoopsMailer->send()) {echo $xoopsMailer->getErrors();}

		// Next step: add the new password to the database
		$sql = sprintf("UPDATE %s SET pass = '%s', salt = '%s', enc_type = '%u', pass_expired = '%u' WHERE uid = '%u'", $xoopsDB->prefix('users'), $pass, $salt, $enc_type, 0, intval($getuser[0]->getVar('uid')));
		if(!$xoopsDB->queryF($sql))
		{
			/** Include header.php to start page rendering */
      include 'header.php';
			echo _US_MAILPWDNG;
			/** Include footer.php to complete page rendering */
			include 'footer.php';
			exit();
		}
		redirect_header('user.php', 3, sprintf(_US_PWDMAILED,$getuser[0]->getVar('uname')), false);
		// If no Code, send it
	}
	else
	{
		$xoopsMailer =& getMailer();
		$xoopsMailer->useMail();
		$xoopsMailer->setTemplate('lostpass1.tpl');
		$xoopsMailer->assign('SITENAME', $icmsConfig['sitename']);
		$xoopsMailer->assign('ADMINMAIL', $icmsConfig['adminmail']);
		$xoopsMailer->assign('SITEURL', ICMS_URL.'/');
		$xoopsMailer->assign('IP', $_SERVER['REMOTE_ADDR']);
		$xoopsMailer->assign('NEWPWD_LINK', ICMS_URL.'/lostpass.php?email='.$email.'&code='.$areyou);
		$xoopsMailer->setToUsers($getuser[0]);
		$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
		$xoopsMailer->setFromName($icmsConfig['sitename']);
		$xoopsMailer->setSubject(sprintf(_US_NEWPWDREQ,$icmsConfig['sitename']));
		/** Include header.php to start page rendering */
    include 'header.php';
		if(!$xoopsMailer->send()) {echo $xoopsMailer->getErrors();}
		echo '<h4>';
		printf(_US_CONFMAIL,$getuser[0]->getVar('uname'));
		echo '</h4>';
		/** Include footer.php to complete page rendering */
		include 'footer.php';
	}
}

?>