<?php
/**
* All functions for Password Expiry & Reset Password generator are going through here.
*
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package	core
* @since	ImpressCMS 1.1
* @author	Vaughan Montgomery <vaughan@impresscms.org>
* @author	The ImpressCMS Project
* @version	$Id: resetpass.php 9285 2009-08-30 17:35:45Z m0nty $
*/
/**
 * Form and process for resetting password and sending to user
 * @package kernel 
 * @subpackage users
 */
$xoopsOption['pagetype'] = 'user';
include 'mainfile.php';

if(!empty($_POST)) foreach($_POST as $k => $v) ${$k} = StopXSS($v);
if(!empty($_GET)) foreach($_GET as $k => $v) ${$k} = StopXSS($v);
$email = (isset($_GET['email']))?trim(StopXSS($_GET['email'])):((isset($_POST['email']))?trim(StopXSS($_POST['email'])):$email);
$username = (isset($_GET['username']))?trim(StopXSS($_GET['username'])):((isset($_POST['username']))?trim(StopXSS($_POST['username'])):$username);
$c_password = (isset($_GET['c_password']))?trim(StopXSS($_GET['c_password'])):((isset($_POST['c_password']))?trim(StopXSS($_POST['c_password'])):$c_password);
$password = (isset($_GET['password']))?trim(StopXSS($_GET['password'])):((isset($_POST['password']))?trim(StopXSS($_POST['password'])):$password);
$password2 = (isset($_GET['password2']))?trim(StopXSS($_GET['password2'])):((isset($_POST['password2']))?trim(StopXSS($_POST['password2'])):$password2);

global $icmsConfigUser;

if($email == '' || $username == '') {redirect_header('user.php',2,_US_SORRYNOTFOUND);}
elseif($password == '' || $password2 == '') {redirect_header('user.php',2,_US_SORRYMUSTENTERPASS);}
if((isset($password)) && ($password !== $password2)) {redirect_header('user.php',2,_US_PASSNOTSAME);}
elseif(($password !== '') && (strlen($password) < $icmsConfigUser['minpass'])) {redirect_header('user.php',2,sprintf(_US_PWDTOOSHORT,$icmsConfigUser['minpass']));}

$myts =& MyTextSanitizer::getInstance();
$member_handler =& xoops_gethandler('member');
$getuser =& $member_handler->getUsers(new Criteria('email', $myts->addSlashes($email)));

if(empty($getuser)) {redirect_header('user.php',2,_US_SORRYNOTFOUND);}
else
{
	if(strtolower($getuser[0]->getVar('uname')) !== strtolower($username)) {redirect_header('user.php',2,_US_SORRYUNAMENOTMATCHEMAIL);}
	else
	{
		$current_pass = $getuser[0]->getVar('pass');
		$current_salt = $getuser[0]->getVar('salt');
		$enc_type = $getuser[0]->getVar('enc_type');

          include_once ICMS_ROOT_PATH.'/class/icms_Password.php';
          $icmspass = new icms_Password();

		$c_pass = $icmspass->icms_encryptPass($c_password, $current_salt, $enc_type, 1);

		if($c_pass !== $current_pass) {redirect_header('user.php',2,_US_SORRYINCORRECTPASS);}

		$salt = $icmspass->icms_createSalt();
		$pass = $icmspass->icms_encryptPass($password, $salt);
		$xoopsMailer =& getMailer();
		$xoopsMailer->useMail();
		$xoopsMailer->setTemplate('resetpass2.tpl');
		$xoopsMailer->assign('SITENAME', $icmsConfig['sitename']);
		$xoopsMailer->assign('ADMINMAIL', $icmsConfig['adminmail']);
		$xoopsMailer->assign('SITEURL', ICMS_URL.'/');
		$xoopsMailer->assign('IP', $_SERVER['REMOTE_ADDR']);
		$xoopsMailer->setToUsers($getuser[0]);
		$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
		$xoopsMailer->setFromName($icmsConfig['sitename']);
		$xoopsMailer->setSubject(sprintf(_US_PWDRESET,ICMS_URL));
		if(!$xoopsMailer->send()) {echo $xoopsMailer->getErrors();}
	
		$sql = sprintf("UPDATE %s SET pass = '%s', salt = '%s', pass_expired = '%u', enc_type = '%u' WHERE uid = '%u'", $xoopsDB->prefix('users'), $pass, $salt, 0, intval($icmsConfigUser['enc_type']), intval($getuser[0]->getVar('uid')));
		if(!$xoopsDB->queryF($sql))
		{
			include 'header.php';
			echo _US_RESETPWDNG;
			include 'footer.php';
			exit();
		}
		unset($salt, $pass);
		redirect_header('user.php', 3, sprintf(_US_PWDRESET,$getuser[0]->getVar('uname')), false);
	}
}

?>