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
* @version		$Id$
*/

include_once 'header.php';

$xoopsOption['template_main'] = 'content_index.html';
include_once ICMS_ROOT_PATH . '/header.php';

// At which record shall we start display
$clean_start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$clean_content_uid = isset($_GET['uid']) ? intval($_GET['uid']) : false;
$clean_content_tags = isset($_GET['tag']) ? htmlentities($_GET['tag']) : false;
$clean_content_pid = isset($_GET['pid']) ? intval($_GET['pid']) : (($clean_content_uid || $clean_content_tags)?false:0);

$content_content_handler = xoops_getModuleHandler('content');

$icmsTpl->assign('content_contents', $content_content_handler->getContents($clean_start, $contentConfig['contents_limit'], $clean_content_uid, $clean_content_tags, false, $clean_content_pid));
$icmsTpl->assign('showInfo',$contentConfig['show_contentinfo']);

/**
 * Create Navbar
 */
include_once ICMS_ROOT_PATH . '/class/pagenav.php';
$contents_count = $content_content_handler->getContentsCount($clean_content_uid,true);
if ($clean_content_uid) {
	$extr_arg = 'uid=' . $clean_content_uid;
} else {
	$extr_arg = '';
}
$pagenav = new XoopsPageNav($contents_count, $contentConfig['contents_limit'], $clean_start, 'start', $extr_arg);
$icmsTpl->assign('navbar', $pagenav->renderNav());

$icmsTpl->assign('content_module_home', content_getModuleName(true, true));
if ($clean_content_uid) {
	$icmsTpl->assign('content_category_path', sprintf(_CO_CONTENT_CONTENT_FROM_USER, icms_getLinkedUnameFromId($clean_content_uid)));
}
$xoTheme->addStylesheet(ICMS_URL.'/modules/content/include/content.css');
include_once 'footer.php';
?>