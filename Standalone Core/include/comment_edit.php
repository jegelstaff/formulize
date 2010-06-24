<?php
/**
* The edit comment include file
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: comment_edit.php 9520 2009-11-11 14:32:52Z pesianstranger $
*/

if (!defined('ICMS_ROOT_PATH')) {
	die("ImpressCMS root path not defined");
}
include_once ICMS_ROOT_PATH.'/include/comment_constants.php';
if ( ('system' != $icmsModule->getVar('dirname') && XOOPS_COMMENT_APPROVENONE == $icmsModuleConfig['com_rule']) || (!is_object($icmsUser) && !$icmsModuleConfig['com_anonpost']) || !is_object($icmsModule) ) {
	redirect_header(ICMS_URL . '/user.php', 1, _NOPERM);
}

icms_loadLanguageFile('core', 'comment');
$com_id = isset($_GET['com_id']) ? intval($_GET['com_id']) : 0;
$com_mode = isset($_GET['com_mode']) ? htmlspecialchars(trim($_GET['com_mode']), ENT_QUOTES) : '';
if ($com_mode == '') {
	if (is_object($icmsUser)) {
		$com_mode = $icmsUser->getVar('umode');
	} else {
		$com_mode = $icmsConfig['com_mode'];
	}
}
if (!isset($_GET['com_order'])) {
	if (is_object($icmsUser)) {
		$com_order = $icmsUser->getVar('uorder');
	} else {
		$com_order = $icmsConfig['com_order'];
	}
} else {
	$com_order = intval($_GET['com_order']);
}
$comment_handler =& xoops_gethandler('comment');
$comment =& $comment_handler->get($com_id);
$dohtml = $comment->getVar('dohtml');
$dosmiley = $comment->getVar('dosmiley');
$dobr = $comment->getVar('dobr');
$doxcode = $comment->getVar('doxcode');
$com_icon = $comment->getVar('com_icon');
$com_itemid = $comment->getVar('com_itemid');
$com_title = $comment->getVar('com_title', 'E');
$com_text = $comment->getVar('com_text', 'E');
$com_pid = $comment->getVar('com_pid');
$com_status = $comment->getVar('com_status');
$com_rootid = $comment->getVar('com_rootid');
if ($icmsModule->getVar('dirname') != 'system') {
	include ICMS_ROOT_PATH.'/header.php';
	include ICMS_ROOT_PATH.'/include/comment_form.php';
	include ICMS_ROOT_PATH.'/footer.php';
} else {
	xoops_cp_header();
	include ICMS_ROOT_PATH.'/include/comment_form.php';
	xoops_cp_footer();
}
?>