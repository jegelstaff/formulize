<?php
/**
* Footer page included at the end of each page on user side of the mdoule
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
* @package		content
* @version		$Id$
*/

if (!defined("ICMS_ROOT_PATH")) die("ICMS root path not defined");

$xoopsTpl->assign("content_adminpage", content_getModuleAdminLink());
$xoopsTpl->assign("content_is_admin", $content_isAdmin);
$xoopsTpl->assign('content_url', CONTENT_URL);
$xoopsTpl->assign('content_images_url', CONTENT_IMAGES_URL);

$xoTheme->addStylesheet(CONTENT_URL . 'module'.(( defined("_ADM_USE_RTL") && _ADM_USE_RTL )?'_rtl':'').'.css');

include_once(ICMS_ROOT_PATH . '/footer.php');

?>