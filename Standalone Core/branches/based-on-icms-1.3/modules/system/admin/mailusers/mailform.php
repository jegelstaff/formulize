<?php
/**
 * Administration of mailusers, form file
 *
 * Longer description about this page
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		Administration
 * @subpackage	Users
 * @version		SVN: $Id: mailform.php 22529 2011-09-02 19:55:40Z phoenyx $
 */

$form = new icms_form_Theme(_AM_SENDMTOUSERS, "mailusers", "admin.php?fct=mailusers", 'post', TRUE);
/*
 * GET variables
 * 
 * POST variables
 * memberslist_id
 * memberslist_uname
 */
// from finduser section
if (!empty($_POST['memberslist_id'])) {
	$user_count = count($_POST['memberslist_id']);
	$display_names = "";
	for ($i = 0; $i < $user_count; $i++) {
		$uid_hidden = new icms_form_elements_Hidden("mail_to_user[]", $_POST['memberslist_id'][$i]);
		$form->addElement($uid_hidden);
		$display_names .= "<a href='" . ICMS_URL . "/userinfo.php?uid=" 
			. $_POST['memberslist_id'][$i] . "' target='_blank'>" 
			. $_POST['memberslist_uname'][$_POST['memberslist_id'][$i]] . "</a>, ";
		unset($uid_hidden);
	}
	$users_label = new icms_form_elements_Label(_AM_SENDTOUSERS2, substr($display_names, 0, -2));
	$form->addElement($users_label);
	$display_criteria = 0;
}

if (!empty($display_criteria)) {
	$selected_groups = array();
	$group_select = new icms_form_elements_select_Group(_AM_GROUPIS . "<br />", "mail_to_group", FALSE, $selected_groups, 5, TRUE);
	$lastlog_min = new icms_form_elements_Text(_AM_LASTLOGMIN . "<br />" . _AM_TIMEFORMAT . "<br />", "mail_lastlog_min", 20, 10);
	$lastlog_max = new icms_form_elements_Text(_AM_LASTLOGMAX . "<br />" . _AM_TIMEFORMAT . "<br />", "mail_lastlog_max", 20, 10);
	$regd_min = new icms_form_elements_Text(_AM_REGDMIN . "<br />" . _AM_TIMEFORMAT . "<br />", "mail_regd_min", 20, 10);
	$regd_max = new icms_form_elements_Text(_AM_REGDMAX . "<br />" . _AM_TIMEFORMAT . "<br />", "mail_regd_max", 20, 10);
	$idle_more = new icms_form_elements_Text(_AM_IDLEMORE . "<br />", "mail_idle_more", 10, 5);
	$idle_less = new icms_form_elements_Text(_AM_IDLELESS . "<br />", "mail_idle_less", 10, 5);
	$mailok_cbox = new icms_form_elements_Checkbox('', 'mail_mailok');
	$mailok_cbox->addOption(1, _AM_MAILOK);
	$inactive_cbox = new icms_form_elements_Checkbox('', 'mail_inactive');
	$inactive_cbox->addOption(1, _AM_INACTIVE . '. ' . _AMIFCHECKD);
	$inactive_cbox->setExtra("onclick='javascript:disableElement(\"mail_lastlog_min\");disableElement(\"mail_lastlog_max\");disableElement(\"mail_idle_more\");disableElement(\"mail_idle_less\");disableElement(\"mail_to_group[]\");'");
	$criteria_tray = new icms_form_elements_Tray(_AM_SENDTOUSERS, "<br /><br />");
	$criteria_tray->addElement($group_select);
	$criteria_tray->addElement($lastlog_min);
	$criteria_tray->addElement($lastlog_max);
	$criteria_tray->addElement($idle_more);
	$criteria_tray->addElement($idle_less);
	$criteria_tray->addElement($mailok_cbox);
	$criteria_tray->addElement($inactive_cbox);
	$criteria_tray->addElement($regd_min);
	$criteria_tray->addElement($regd_max);
	$form->addElement($criteria_tray);
}

$fname_text = new icms_form_elements_Text(_AM_MAILFNAME, "mail_fromname", 30, 255, htmlspecialchars($icmsConfig['sitename'], ENT_QUOTES));
$fromemail = !empty($icmsConfig['adminmail']) ? $icmsConfig['adminmail'] : icms::$user->getVar("email", "E");
$femail_text = new icms_form_elements_Text(_AM_MAILFMAIL, "mail_fromemail", 30, 255, $fromemail);
//$subject_caption = _AM_MAILSUBJECT . "<br /><br /><span style='font-size:x-small;font-weight:bold;'>" . _AM_MAILTAGS . "</span><br /><span style='font-size:x-small;font-weight:normal;'>" . _AM_MAILTAGS1 . "<br />" . _AM_MAILTAGS2 . "<br />" . _AM_MAILTAGS3 . "</span>";
$subject_caption = _AM_MAILSUBJECT . "<br /><br /><span style='font-size:x-small;font-weight:bold;'>" . _AM_MAILTAGS . "</span><br /><span style='font-size:x-small;font-weight:normal;'>" . _AM_MAILTAGS2 . "</span>";
$subject_text = new icms_form_elements_Text($subject_caption, "mail_subject", 50, 255);
$body_caption = _AM_MAILBODY . "<br /><br /><span style='font-size:x-small;font-weight:bold;'>" . _AM_MAILTAGS . "</span><br /><span style='font-size:x-small;font-weight:normal;'>" . _AM_MAILTAGS1 . "<br />" . _AM_MAILTAGS2 . "<br />" . _AM_MAILTAGS3 . "<br />" . _AM_MAILTAGS4 . "</span>";
$body_text = new icms_form_elements_Textarea($body_caption, "mail_body", "", 10);
$to_checkbox = new icms_form_elements_Checkbox(_AM_SENDTO, "mail_send_to", "mail");
$to_checkbox->addOption("mail", _AM_EMAIL);
$to_checkbox->addOption("pm", _AM_PM);
$start_hidden = new icms_form_elements_Hidden("mail_start", 0);
$op_hidden = new icms_form_elements_Hidden("op", "send");
$submit_button = new icms_form_elements_Button("", "mail_submit", _SEND, "submit");

$form->addElement($fname_text);
$form->addElement($femail_text);
$form->addElement($subject_text);
$form->addElement($body_text);
$form->addElement($to_checkbox);
$form->addElement($op_hidden);
$form->addElement($start_hidden);
$form->addElement($submit_button);
$form->setRequired($subject_text);
$form->setRequired($body_text);
//$form->setRequired($to_checkbox);

