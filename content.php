<?php
/**
 * Content page
 *
 * @copyright	The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
 * @package		content
 * @version		$Id: content.php 19866 2010-07-17 20:00:30Z phoenyx $
 */

include_once 'mainfile.php';

define ( "CONTENT_DIRNAME", 'content' );
define ( "CONTENT_URL", ICMS_URL . '/modules/' . CONTENT_DIRNAME . '/' );
define ( "CONTENT_ROOT_PATH", ICMS_ROOT_PATH . '/modules/' . CONTENT_DIRNAME . '/' );
define ( "CONTENT_IMAGES_URL", CONTENT_URL . 'images/' );
define ( "CONTENT_ADMIN_URL", CONTENT_URL . 'admin/' );

$mhandler = icms::handler('icms_module');
$xoopsModule = $mhandler->getByDirname(CONTENT_DIRNAME);
include_once ICMS_ROOT_PATH . '/modules/content/include/common.php';
$icmsModule = $xoopsModule;
icms_loadLanguageFile('content', 'common');
icms_loadLanguageFile('content', 'main');
$icmsModuleConfig = $contentConfig;

$content_content_handler = icms_getModuleHandler ( 'content', 'content' );

/** Again, use a naming convention that indicates the source of the content of the variable */
$clean_content_id = isset ( $_GET ['content_id'] ) ? (int) ( $_GET ['content_id'] ) : 0;
$page = (isset ( $_GET ['page'] )) ? trim ( StopXSS ( $_GET ['page'] ) ) : ((isset ( $_POST ['page'] )) ? trim ( StopXSS ( $_POST ['page'] ) ) : $clean_content_id);

if (! $page) {
	$path = (isset ( $_SERVER ['PATH_INFO'] ) && substr ( $_SERVER ['PATH_INFO'], 0, 1 ) == '/') ? substr ( $_SERVER ['PATH_INFO'], 1, strlen ( $_SERVER ['PATH_INFO'] ) ) : ((isset ( $_SERVER ['PATH_INFO'] )) ? $_SERVER ['PATH_INFO'] : '');
	$path = trim ( StopXSS ( $path ) );
	$params = explode ( '/', $path );
	if (count ( $params ) > 0) {
		if ($params [0] == 'page') {
			$page = (isset ( $params [1] )) ? $params [1] : 0;
		} else {
			$page = $params [0];
		}
	}
}

if (!empty($page)) {
	$page = (is_int($page)) ? (int) ($page) : urlencode($page);
	$page = str_replace('-',' ',$page);
	$criteria = $content_content_handler->getContentsCriteria ( 0, 1, false, false, $page, false, 'content_id', 'DESC' );
	$content = $content_content_handler->getObjects ( $criteria );
	$contentObj = false;
	foreach ( $content as $content) {
		$contentObj = $content;
		break;
	}
	$clean_content_id = $contentObj->getVar ( 'content_id' );
}

$xoopsOption['template_main'] = 'content_content.html';
include_once ICMS_ROOT_PATH . '/header.php';

if (is_object ( $contentObj ) && $contentObj->accessGranted ()) {
	$content_content_handler->updateCounter ( $clean_content_id );
	$content = $contentObj->toArray ();
	$icmsTpl->assign ( 'content_content', $content );
	$icmsTpl->assign ( 'showInfo', $contentConfig ['show_contentinfo'] );
	$showSubs = ($contentConfig ['show_relateds'] && $content ['content_showsubs']) ? true : false;
	$icmsTpl->assign ( 'showSubs', $showSubs );
	if ($contentConfig ['show_breadcrumb']) {
		$icmsTpl->assign ( 'content_category_path', $content_content_handler->getBreadcrumbForPid ( $contentObj->getVar ( 'content_id', 'e' ), 1 ) );
	} else {
		$icmsTpl->assign ( 'content_category_path', false );
	}
} else {
	redirect_header ( CONTENT_URL, 3, _NOPERM );
}

if ($contentConfig ['com_rule'] && $contentObj->getVar ( 'content_cancomment' )) {
	$icmsTpl->assign ( 'content_content_comment', true );
	include_once ICMS_ROOT_PATH . '/include/comment_view.php';
}

/**
 * Generating meta information for this page
 */
$icms_metagen = new icms_ipf_Metagen ( $contentObj->getVar ( 'content_title' ), $contentObj->getVar ( 'meta_keywords', 'n' ), $contentObj->getVar ( 'meta_description', 'n' ) );
$icms_metagen->createMetaTags ();

$xoTheme->addStylesheet ( ICMS_URL . '/modules/content/include/content.css' );
$icmsTpl->assign ( 'content_module_home', content_getModuleName ( true, true ) );

include_once CONTENT_ROOT_PATH.'footer.php';
?>