<?php
/**
 * Site index aka home page.
 * redirects to installation, if ImpressCMS is not installed yet
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License(GPL)
 * @package		core
 * @author	    Sina Asghari(aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		AVN: $Id: index.php 21047 2011-03-14 15:52:14Z m0nty_ $
 **/

/** Need the mainfile */
if (!defined("XOOPS_MAINFILE_INCLUDED")) {
    include_once "mainfile.php";
}

$member_handler = icms::handler('icms_member');
$group = $member_handler->getUserBestGroup((@is_object(icms::$user) ? icms::$user->getVar('uid') : 0));

if(isset($_SESSION['google_xoops_redirect'])) {
    header('Location: ' . $_SESSION['google_xoops_redirect']);
    unset($_SESSION['google_xoops_redirect']);
    exit();
}

// added failover to default startpage for the registered users group -- JULIAN EGELSTAFF Apr 3 2017
$groups = @is_object(icms::$user) ? icms::$user->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
if(($icmsConfig['startpage'][$group] == "" OR $icmsConfig['startpage'][$group] == "--")
AND in_array(XOOPS_GROUP_USERS, $groups)
AND $icmsConfig['startpage'][XOOPS_GROUP_USERS] != ""
AND $icmsConfig['startpage'][XOOPS_GROUP_USERS] != "--") {
    $icmsConfig['startpage'] = $icmsConfig['startpage'][XOOPS_GROUP_USERS];
} else {
    $icmsConfig['startpage'] = $icmsConfig['startpage'][$group];
}

// See if they actually have a Formulize start page declared. If not, and they're anon, nullify the startpage since we have no where to take them and this way they can login.
if($icmsConfig['startpage'] == 'formulize') {
	include_once XOOPS_ROOT_PATH."/modules/formulize/class/applications.php";
  $includeMenuURLs = true;
	$followMenuURLs = false;
	list($startFid,$startSid,$startURL) = formulizeApplicationMenuLinksHandler::getDefaultScreenForUser();
	if(!$xoopsUser AND !$startFid AND !$startSid AND !$startURL) {
		$icmsConfig['startpage'] = '--';
	} elseif($startSid) {
		// start pages with rewrite URLs only work when the Formulize start page system points directly to a screen, not a fid. We're not going to do all the interpretation here to figure out the valid screen for the fid and user, that's what half of modules/formulize/initialize.php is for.
		$screen_handler = xoops_getmodulehandler('screen', 'formulize');
		if($screenObject = $screen_handler->get($startSid)) {
			if($screenObject->getVar('rewriteruleAddress')) {
				header('Location: '.ICMS_URL.'/'.urlencode(strip_tags($screenObject->getVar('rewriteruleAddress'))));
				exit();
			}
		}
	}
}

if (isset($icmsConfig['startpage']) && $icmsConfig['startpage'] != "" && $icmsConfig['startpage'] != "--") {
	$arr = explode('-', $icmsConfig['startpage']);
	if (count($arr) > 1) {
		$page_handler = icms::handler('icms_data_page');
		$page = $page_handler->get($arr[1]);
		if (is_object($page)) {
			$url =(substr($page->getVar('page_url'), 0, 7) == 'http://')
				? $page->getVar('page_url') : ICMS_URL . '/' . $page->getVar('page_url');
			header('Location: ' . $url);
		} else {
			$icmsConfig['startpage'] = '--';
			$xoopsOption['show_cblock'] = 1;
			/** Included to start page rendering */
			include "header.php";
			global $xoopsTpl;
			$xoopsTpl->assign('openMenuClass', 'site-layout__sidebar--open');
			/** Included to complete page rendering */
			include "footer.php";
		}
	} else {
		header('Location: ' . ICMS_MODULES_URL . '/' . $icmsConfig['startpage'] . '/');
	}
	exit();
} else {
	$xoopsOption['show_cblock'] = 1;
	/** Included to start page rendering */
	include "header.php";
    global $xoopsTpl;
    $xoopsTpl->assign('openMenuClass', 'site-layout__sidebar--open');
	/** Included to complete page rendering */
	include "footer.php";
}
