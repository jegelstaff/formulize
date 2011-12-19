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
 * @version		$Id: registerform.php 21083 2011-03-17 12:43:06Z m0nty_ $
 */
defined("ICMS_ROOT_PATH") || die("ImpressCMS root path not defined");

$email_tray = new icms_form_elements_Tray(_US_EMAIL, "<br />");
$email_text = new icms_form_elements_Text("", "email", 25, 60, icms_core_DataFilter::htmlSpecialChars($email));
$email_option = new icms_form_elements_Checkbox("", "user_viewemail", $user_viewemail);
$email_option->addOption(1, _US_ALLOWVIEWEMAIL);
$email_tray->addElement($email_text, true);
$email_tray->addElement($email_option);
$reg_form = new icms_form_Theme(_US_USERREG, "userinfo", "register.php", "post", true);
$uname_size = $icmsConfigUser['maxuname'] < 75 ? $icmsConfigUser['maxuname'] : 75;
$uname_size = $icmsConfigUser['maxuname'] > 3 ? $icmsConfigUser['maxuname'] : 3;
$reg_form->addElement(new icms_form_elements_Text(_US_NICKNAME, "uname", $uname_size, $uname_size, icms_core_DataFilter::htmlSpecialChars($uname)), true);
$login_name_size = $icmsConfigUser['maxuname'] < 75 ? $icmsConfigUser['maxuname'] : 75;
$reg_form->addElement(new icms_form_elements_Text(_US_LOGIN_NAME, "login_name", $login_name_size, $login_name_size, icms_core_DataFilter::htmlSpecialChars($login_name)), true);
$reg_form->addElement($email_tray);
if ($icmsConfigUser['pass_level']>20) {
	icms_PasswordMeter();
}
$reg_form->addElement(new icms_form_elements_Password(_US_PASSWORD, "pass", 10, 255, icms_core_DataFilter::htmlSpecialChars($pass), false, ($icmsConfigUser['pass_level']?'password_adv':'')), true);
$reg_form->addElement(new icms_form_elements_Password(_US_VERIFYPASS, "vpass", 10, 255, icms_core_DataFilter::htmlSpecialChars($vpass)), true);
$reg_form->addElement(new icms_form_elements_Text(_US_WEBSITE, "url", 25, 255, icms_core_DataFilter::htmlSpecialChars($url)));
$tzselected = ($timezone_offset != "") ? $timezone_offset : $icmsConfig['default_TZ'];
$reg_form->addElement(new icms_form_elements_select_Timezone(_US_TIMEZONE, "timezone_offset", $tzselected));
//$reg_form->addElement($avatar_tray);
$reg_form->addElement(new icms_form_elements_Radioyn(_US_MAILOK, 'user_mailok', $user_mailok));

if ($icmsConfigUser['reg_dispdsclmr'] != 0 && $icmsConfigUser['reg_disclaimer'] != '') {
	$disc_tray = new icms_form_elements_Tray(_US_DISCLAIMER, '<br />');
	$disclaimer_html = '<div id="disclaimer">'.nl2br($icmsConfigUser['reg_disclaimer']).'</div>';
	$disc_text = new icms_form_elements_Label('', $disclaimer_html, 'disclaimer');
	$disc_tray->addElement($disc_text);
	$agree_chk = new icms_form_elements_Checkbox('', 'agree_disc', $agree_disc);
	$agree_chk->addOption(1, _US_IAGREE);
	$eltname = $agree_chk->getName();
	$eltmsg = str_replace('"', '\"', stripslashes( sprintf( _FORM_ENTER, _US_IAGREE ) ) );
	$agree_chk->customValidationCode[] = "if (myform.{$eltname}.checked == false) { window.alert(\"{$eltmsg}\"); myform.{$eltname}.focus(); return false; }";
	$disc_tray->addElement($agree_chk, true);
	$reg_form->addElement($disc_tray);
}

$reg_form->addElement(new icms_form_elements_Hidden("salt", icms_core_DataFilter::htmlSpecialChars($salt)));
$reg_form->addElement(new icms_form_elements_Hidden("enc_type", (int) ($enc_type)));
$reg_form->addElement(new icms_form_elements_Hidden("actkey", icms_core_DataFilter::htmlSpecialChars($actkey)));

if ($icmsConfigUser['use_captcha'] == true) {
	$reg_form->addElement(new icms_form_elements_Captcha(_SECURITYIMAGE_GETCODE, "scode"), true);
	$reg_form->addElement(new icms_form_elements_Hidden("op", "finish"));
} else {
	$reg_form->addElement(new icms_form_elements_Hidden("op", "newuser"));
}

$reg_form->addElement(new icms_form_elements_Button("", "submit", _US_SUBMIT, "submit"));

