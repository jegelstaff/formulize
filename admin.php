<?php
/**
 * Admin control panel entry page
 *
 * This page is responsible for
 * - displaying the home of the Control Panel
 * - checking for cache/adminmenu.php
 * - displaying RSS feed of the ImpressCMS Project
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		XOOPS
 * @author		http://www.xoops.org The XOOPS Project
 * @author		modified by marcan <marcan@impresscms.org>
 * @version		$Id: admin.php 20456 2010-12-02 17:57:52Z skenow $
 */

define('ICMS_IN_ADMIN', 1); 

$xoopsOption['pagetype'] = 'admin';
include 'mainfile.php';
include ICMS_ROOT_PATH . '/include/cp_functions.php';

// test to see if the system module should be updated, added in 1.2
if (icms_getModuleInfo('system')->getDBVersion() < ICMS_SYSTEM_DBVERSION) {
	redirect_header('modules/system/admin.php?fct=modulesadmin&amp;op=update&amp;module=system', 1, _CO_ICMS_UPDATE_NEEDED);
}

$op = isset($_GET['rssnews']) ? (int) ($_GET['rssnews']) : 0;
if (!empty($_GET['op'])) {$op = (int) ($_GET['op']);}
if (!empty($_POST['op'])) {$op = (int) ($_POST['op']);}

if (!file_exists(ICMS_CACHE_PATH . '/adminmenu_' . $icmsConfig['language'] . '.php')) {
	xoops_module_write_admin_menu(impresscms_get_adminmenu());
}

switch ($op) {
	case 1:
		icms_cp_header();
		showRSS();
		break;
		/*	case 2:
		 xoops_module_write_admin_menu(impresscms_get_adminmenu());
		 redirect_header('javascript:history.go(-1)', 1, _AD_LOGINADMIN);
		 break;*/

	default:
		icms_cp_header();
		break;
}

function showRSS() {
	global $icmsAdminTpl, $icmsConfigPersona;

	$rssurl = $icmsConfigPersona['rss_local'];
	$rssfile = ICMS_CACHE_PATH . '/adminnews_' . _LANGCODE . '.xml';

	// Create a new instance of the SimplePie object
	$feed = new icms_feeds_Simplerss();
	$feed->set_feed_url($rssurl);
	$feed->set_cache_duration(3600);
	$feed->set_autodiscovery_level(SIMPLEPIE_LOCATOR_NONE);
	$feed->init();
	$feed->handle_content_type();

	if (!$feed->error) {
		$icmsAdminTpl->assign('admin_rss_feed_link', $feed->get_link());
		$icmsAdminTpl->assign('admin_rss_feed_title', $feed->get_title());
		$icmsAdminTpl->assign('admin_rss_feed_dsc', $feed->get_description());
		$feeditems = array();
		foreach ($feed->get_items() as $item) {
			$feeditem = array();
			$feeditem['link'] = $item->get_permalink();
			$feeditem['title'] = $item->get_title();
			$feeditem['description'] = $item->get_description();
			$feeditem['date'] = $item->get_date();
			$feeditem['guid'] = $item->get_id();
			$feeditems[] = $feeditem;
		}
		$icmsAdminTpl->assign('admin_rss_feeditems', $feeditems);
	}

	$icmsAdminTpl->display('db:admin/system_adm_rss.html');
}
icms_cp_footer();
