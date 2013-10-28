<?php
/**
 * User index page of the module
 *
 * Including the content page
 *
 * @copyright	The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
 * @package		content
 * @version		$Id: index.php 21842 2011-06-23 14:46:08Z phoenyx $
 */

include_once 'header.php';

$xoopsOption['template_main'] = 'content_index.html';
include_once ICMS_ROOT_PATH . '/header.php';

$content_content_handler = icms_getModuleHandler('content', basename(dirname(__FILE__)), 'content');

if (icms::$module->config['default_page'] == 0) {
	// At which record shall we start display
	$clean_start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
	$clean_content_uid = isset($_GET['uid']) ? (int)$_GET['uid'] : false;
	$clean_content_tags = isset($_GET['tag']) ? filter_input(INPUT_GET, 'tag', FILTER_SANITIZE_MAGIC_QUOTES) : false;
	$clean_content_pid = isset($_GET['pid']) ? (int)$_GET['pid'] : (($clean_content_uid || $clean_content_tags) ? false : 0);

	$content = $content_content_handler->getContents($clean_start, icms::$module->config['contents_limit'], $clean_content_uid, $clean_content_tags, false, $clean_content_pid);
	$icmsTpl->assign('content_contents', $content);

	if ($clean_content_uid !== false) {
		$contents_count = $content_content_handler->getContentsCount($clean_content_uid);
		$pagenav = new icms_view_PageNav($contents_count, icms::$module->config['contents_limit'], $clean_start, 'start', 'uid=' . $clean_content_uid);
	} else {
		/**
		 * @todo this is a bug because it's not taking into concideration view permissions, ...
		 */
		$contents_count = $content_content_handler->getCount();
		$pagenav = new icms_view_PageNav($contents_count, icms::$module->config['contents_limit'], $clean_start, 'start');
	}
	$icmsTpl->assign('navbar', $pagenav->renderNav());
	if ($clean_content_uid) {
		$icmsTpl->assign('content_category_path', sprintf(_CO_CONTENT_CONTENT_FROM_USER, icms_member_user_Handler::getUserLink($clean_content_uid)));
	}
} else {
	$content = $content_content_handler->getContents(0, 1, false, false, icms::$module->config['default_page']);
	$icmsTpl->assign('content_contents', $content);
}

$icmsTpl->assign('showInfo', icms::$module->config['show_contentinfo']);
$icmsTpl->assign('content_module_home', '<a href="' . ICMS_URL . '/modules/' . icms::$module->getVar('dirname') . '">' . icms::$module->getVar('name') . '</a>');

$xoTheme->addStylesheet(ICMS_URL . '/modules/content/include/content.css');
include_once 'footer.php';