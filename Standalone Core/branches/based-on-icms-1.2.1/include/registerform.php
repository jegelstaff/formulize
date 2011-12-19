<?php
/**
* Registration form
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		LICENSE.txt
* @package	core
* @since		XOOPS
* @author		http://www.xoops.org The XOOPS Project
* @version		$Id: registerform.php 8768 2009-05-16 22:48:26Z pesianstranger $
*/
if (!defined("ICMS_ROOT_PATH")) {
	die("ImpressCMS root path not defined");
}
include_once ICMS_ROOT_PATH."/class/xoopslists.php";
include_once ICMS_ROOT_PATH."/class/xoopsformloader.php";


$email_tray = new XoopsFormElementTray(_US_EMAIL, "<br />");
$email_text = new XoopsFormText("", "email", 25, 60, $myts->htmlSpecialChars($email));
$email_option = new XoopsFormCheckBox("", "user_viewemail", $user_viewemail);
$email_option->addOption(1, _US_ALLOWVIEWEMAIL);
$email_tray->addElement($email_text, true);
$email_tray->addElement($email_option);
$reg_form = new XoopsThemeForm(_US_USERREG, "userinfo", "register.php", "post", true);
$uname_size = $icmsConfigUser['maxuname'] < 75 ? $icmsConfigUser['maxuname'] : 75;
$uname_size = $icmsConfigUser['maxuname'] > 3 ? $icmsConfigUser['maxuname'] : 3;
$reg_form->addElement(new XoopsFormText(_US_NICKNAME, "uname", $uname_size, $uname_size, $myts->htmlSpecialChars($uname)), true);
$login_name_size = $icmsConfigUser['maxuname'] < 75 ? $icmsConfigUser['maxuname'] : 75;
$reg_form->addElement(new XoopsFormText(_US_LOGIN_NAME, "login_name", $login_name_size, $login_name_size, $myts->htmlSpecialChars($login_name)), true);
$reg_form->addElement($email_tray);
if($icmsConfigUser['pass_level']>20){
icms_PasswordMeter();
}
$reg_form->addElement(new XoopsFormPassword(_US_PASSWORD, "pass", 10, 255, $myts->htmlSpecialChars($pass), false, ($icmsConfigUser['pass_level']?'password_adv':'')), true);
$reg_form->addElement(new XoopsFormPassword(_US_VERIFYPASS, "vpass", 10, 255, $myts->htmlSpecialChars($vpass)), true);
$reg_form->addElement(new XoopsFormText(_US_WEBSITE, "url", 25, 255, $myts->htmlSpecialChars($url)));
$tzselected = ($timezone_offset != "") ? $timezone_offset : $icmsConfig['default_TZ'];
$reg_form->addElement(new XoopsFormSelectTimezone(_US_TIMEZONE, "timezone_offset", $tzselected));
//$reg_form->addElement($avatar_tray);
$reg_form->addElement(new XoopsFormRadioYN(_US_MAILOK, 'user_mailok', $user_mailok));

if ($icmsConfigUser['reg_dispdsclmr'] != 0 && $icmsConfigUser['reg_disclaimer'] != '') {
	$disc_tray = new XoopsFormElementTray(_US_DISCLAIMER, '<br />');
	$disclaimer_html = '<div id="disclaimer">'.nl2br($icmsConfigUser['reg_disclaimer']).'</div>';
	$disc_text = new XoopsFormLabel('', $disclaimer_html, 'disclaimer');
	$disc_tray->addElement($disc_text);
	$agree_chk = new XoopsFormCheckBox('', 'agree_disc', $agree_disc);
	$agree_chk->addOption(1, _US_IAGREE);
	$eltname = $agree_chk->getName();
	$eltmsg = str_replace('"', '\"', stripslashes( sprintf( _FORM_ENTER, _US_IAGREE ) ) );
	$agree_chk->customValidationCode[] = "if ( myform.{$eltname}.checked == false ) { window.alert(\"{$eltmsg}\"); myform.{$eltname}.focus(); return false; }";
	$disc_tray->addElement($agree_chk, true);
	$reg_form->addElement($disc_tray);
}

$reg_form->addElement(new XoopsFormHidden("salt", $myts->htmlSpecialChars($salt)));
$reg_form->addElement(new XoopsFormHidden("enc_type", intval($enc_type)));
$reg_form->addElement(new XoopsFormHidden("actkey", $myts->htmlSpecialChars($actkey)));

if ($icmsConfigUser['use_captcha'] == true) {
	$reg_form->addElement(new IcmsFormCaptcha(_SECURITYIMAGE_GETCODE, "scode"), true);
	$reg_form->addElement(new XoopsFormHidden("op", "finish"));
} else {
	$reg_form->addElement(new XoopsFormHidden("op", "newuser"));
}

$reg_form->addElement(new XoopsFormButton("", "submit", _US_SUBMIT, "submit"));

?>