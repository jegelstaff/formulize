<?php
/**
 * The commentform include file
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package		Administration
 * @subpackage	Comments
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version		SVN: $Id: commentform.inc.php 20768 2011-02-06 00:02:25Z skenow $
 */

defined("ICMS_ROOT_PATH") || die("ImpressCMS root path not defined");

$cform = new icms_form_Theme(_CM_POSTCOMMENT, "commentform", "postcomment.php", "post", true);
if (!preg_match("/^re:/i", $subject)) {
	$subject = "Re: " . icms_core_DataFilter::icms_substr($subject,0,56);
}
$cform->addElement(new icms_form_elements_Text(_CM_TITLE, 'subject', 50, 255, $subject), true);
$icons_radio = new icms_form_elements_Radio(_MESSAGEICON, 'icon', $icon);
$subject_icons = icms_core_Filesystem::getFileList(ICMS_ROOT_PATH . "/images/subject/", '', array('gif', 'jpg', 'png'));
foreach ($subject_icons as $iconfile) {
	$icons_radio->addOption($iconfile, '<img src="' . ICMS_IMAGES_URL . '/subject/' . $iconfile . '" alt="" />');
}
$cform->addElement($icons_radio);
$cform->addElement(new icms_form_elements_Dhtmltextarea(_CM_MESSAGE, 'message', $message, 10, 50), true);
$option_tray = new icms_form_elements_Tray(_OPTIONS,'<br />');
if (icms::$user) {
	if ($icmsConfig['anonpost'] == true) {
		$noname_checkbox = new icms_form_elements_Checkbox('', 'noname', $noname);
		$noname_checkbox->addOption(1, _POSTANON);
		$option_tray->addElement($noname_checkbox);
	}
	if (icms::$user->isAdmin($icmsModule->getVar('mid'))) {
		$nohtml_checkbox = new icms_form_elements_Checkbox('', 'nohtml', $nohtml);
		$nohtml_checkbox->addOption(1, _DISABLEHTML);
		$option_tray->addElement($nohtml_checkbox);
	}
}
$smiley_checkbox = new icms_form_elements_Checkbox('', 'nosmiley', $nosmiley);
$smiley_checkbox->addOption(1, _DISABLESMILEY);
$option_tray->addElement($smiley_checkbox);

$cform->addElement($option_tray);
$cform->addElement(new icms_form_elements_Hidden('pid', (int) $pid));
$cform->addElement(new icms_form_elements_Hidden('comment_id', (int) $comment_id));
$cform->addElement(new icms_form_elements_Hidden('item_id', (int) $item_id));
$cform->addElement(new icms_form_elements_Hidden('order', (int) $order));
$button_tray = new icms_form_elements_Tray('' ,'&nbsp;');
$button_tray->addElement(new icms_form_elements_Button('', 'preview', _PREVIEW, 'submit'));
$button_tray->addElement(new icms_form_elements_Button('', 'post', _CM_POSTCOMMENT, 'submit'));
$cform->addElement($button_tray);
$cform->display();
