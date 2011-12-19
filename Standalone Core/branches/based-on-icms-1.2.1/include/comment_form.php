<?php
/**
* The comment form extra include file
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: comment_form.php 9520 2009-11-11 14:32:52Z pesianstranger $
*/

if (!defined('ICMS_ROOT_PATH') || !is_object($icmsModule)) {
	exit();
}
$com_modid = $icmsModule->getVar('mid');
include_once ICMS_ROOT_PATH."/class/xoopslists.php";
include_once ICMS_ROOT_PATH."/class/xoopsformloader.php";
$cform = new XoopsThemeForm(_CM_POSTCOMMENT, "commentform", 'comment_post.php', 'post', true);
if (isset($icmsModuleConfig['com_rule'])) {
	include_once ICMS_ROOT_PATH.'/include/comment_constants.php';
	switch ($icmsModuleConfig['com_rule']) {
	case XOOPS_COMMENT_APPROVEALL:
		$rule_text = _CM_COMAPPROVEALL;
		break;
	case XOOPS_COMMENT_APPROVEUSER:
		$rule_text = _CM_COMAPPROVEUSER;
		break;
	case XOOPS_COMMENT_APPROVEADMIN:
		default:
		$rule_text = _CM_COMAPPROVEADMIN;
		break;
	}
	$cform->addElement(new XoopsFormLabel(_CM_COMRULES, $rule_text));
}

$cform->addElement(new XoopsFormText(_CM_TITLE, 'com_title', 50, 255, $com_title), true);
$icons_radio = new XoopsFormRadio(_MESSAGEICON, 'com_icon', $com_icon);
$subject_icons = XoopsLists::getSubjectsList();
foreach ($subject_icons as $iconfile) {
	$icons_radio->addOption($iconfile, '<img src="'.ICMS_URL.'/images/subject/'.$iconfile.'" alt="" />');
}
$cform->addElement($icons_radio);
$cform->addElement(new XoopsFormDhtmlTextArea(_CM_MESSAGE, 'com_text', $com_text, 10, 50), true);
$option_tray = new XoopsFormElementTray(_OPTIONS,'<br />');

$button_tray = new XoopsFormElementTray('' ,'&nbsp;');


if (is_object($icmsUser)) {
  if ($icmsModuleConfig['com_anonpost'] == 1) {
	$noname = !empty($noname) ? 1 : 0;
	$noname_checkbox = new XoopsFormCheckBox('', 'noname', $noname);
	$noname_checkbox->addOption(1, _POSTANON);
	$option_tray->addElement($noname_checkbox);
  }
  if (false != $icmsUser->isAdmin($com_modid)) {
	// show status change box when editing (comment id is not empty)
	if (!empty($com_id)) {
	  include_once ICMS_ROOT_PATH.'/include/comment_constants.php';
	  $status_select = new XoopsFormSelect(_CM_STATUS, 'com_status', $com_status);
	  $status_select->addOptionArray(array(XOOPS_COMMENT_PENDING => _CM_PENDING, XOOPS_COMMENT_ACTIVE => _CM_ACTIVE, XOOPS_COMMENT_HIDDEN => _CM_HIDDEN));
	  $cform->addElement($status_select);
	  $button_tray->addElement(new XoopsFormButton('', 'com_dodelete', _DELETE, 'submit'));
	}
	$html_checkbox = new XoopsFormCheckBox('', 'dohtml', $dohtml);
	$html_checkbox->addOption(1, _CM_DOHTML);
	$option_tray->addElement($html_checkbox);
  }else{
	$cform->addElement(new XoopsFormHidden('dohtml', $dohtml));
  }
}
$smiley_checkbox = new XoopsFormCheckBox('', 'dosmiley', $dosmiley);
$smiley_checkbox->addOption(1, _CM_DOSMILEY);
$option_tray->addElement($smiley_checkbox);
$xcode_checkbox = new XoopsFormCheckBox('', 'doxcode', $doxcode);
$xcode_checkbox->addOption(1, _CM_DOXCODE);
$option_tray->addElement($xcode_checkbox);
$br_checkbox = new XoopsFormCheckBox('', 'dobr', $dobr);
$br_checkbox->addOption(1, _CM_DOAUTOWRAP);
$option_tray->addElement($br_checkbox);

$cform->addElement($option_tray);
$cform->addElement(new XoopsFormHidden('com_pid', intval($com_pid)));
$cform->addElement(new XoopsFormHidden('com_rootid', intval($com_rootid)));
$cform->addElement(new XoopsFormHidden('com_id', $com_id));
$cform->addElement(new XoopsFormHidden('com_itemid', $com_itemid));
$cform->addElement(new XoopsFormHidden('com_order', $com_order));
$cform->addElement(new XoopsFormHidden('com_mode', $com_mode));

// add module specific extra params

if ('system' != $icmsModule->getVar('dirname')) {
	$comment_config = $icmsModule->getInfo('comments');
 	if (isset($comment_config['extraParams']) && is_array($comment_config['extraParams'])) {
	$myts =& MyTextSanitizer::getInstance();
	foreach ($comment_config['extraParams'] as $extra_param) {
	  // This routine is included from forms accessed via both GET and POST
	  if (isset($_POST[$extra_param])) {
		  $hidden_value = $myts->stripSlashesGPC($_POST[$extra_param]);
	  } elseif (isset($_GET[$extra_param])) {
		  $hidden_value = $myts->stripSlashesGPC($_GET[$extra_param]);
			} else {
				$hidden_value = '';
			}
 			$cform->addElement(new XoopsFormHidden($extra_param, $hidden_value));
 		}
 	}
}
// Captcha Hack
if ( $icmsConfig['use_captchaf'] == true ) {
  $cform->addElement(new IcmsFormCaptcha());
}
// Captcha Hack
$button_tray->addElement(new XoopsFormButton('', 'com_dopreview', _PREVIEW, 'submit'));
$button_tray->addElement(new XoopsFormButton('', 'com_dopost', _CM_POSTCOMMENT, 'submit'));
$cform->addElement($button_tray);
$cform->display();
?>