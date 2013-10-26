<?php
/**
 * Temporary solution for "site closed" status
 *
 * @copyright	The Xoops project http://www.xoops.org/
 * @license		http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author		phppp (infomax@gmail.com)
 * @since		Xoops 2.0.17
 * @package 	core
 * @version		SVN: $Id: site-closed.php 21837 2011-06-23 13:16:54Z phoenyx $
 */

defined("ICMS_ROOT_PATH") || die("ImpressCMS root path not defined");

$allowed = FALSE;
if (isset($xoopsOption['ignore_closed_site']) && $xoopsOption['ignore_closed_site']) {
	$allowed = TRUE;
} elseif (is_object(icms::$user)) {
	foreach (icms::$user->getGroups() as $group) {
		if (in_array($group, $icmsConfig['closesite_okgrp']) || ICMS_GROUP_ADMIN == $group) {
			$allowed = TRUE;
			break;
		}
	}
} elseif (!empty($_POST['xoops_login'])) {
	include_once ICMS_INCLUDE_PATH . '/checklogin.php';
	exit();
}

if (!$allowed) {
	$themeFactory = new icms_view_theme_Factory();
	$themeFactory->allowedThemes = $icmsConfig['theme_set_allowed'];
	$themeFactory->defaultTheme = $icmsConfig['theme_set'];
	$icmsTheme =& $themeFactory->createInstance(array("plugins" => array()));
	$icmsTheme->addScript('/include/xoops.js', array('type' => 'text/javascript'));
	$icmsTheme->addStylesheet(ICMS_URL . "/icms" 
		. ((defined('_ADM_USE_RTL') && _ADM_USE_RTL) ? "_rtl" : "") . ".css", array("media" => "screen"));
	$icmsTpl =& $icmsTheme->template;

	$icmsTpl->assign(array(
		'icms_theme' => $icmsConfig['theme_set'],
		'icms_imageurl' => ICMS_THEME_URL . '/' . $icmsConfig['theme_set'] . '/',
		'icms_themecss' => xoops_getcss($icmsConfig['theme_set']),
		'icms_requesturi' => htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES),
		'icms_sitename' => htmlspecialchars($icmsConfig['sitename'], ENT_QUOTES),
		'icms_slogan' => htmlspecialchars($icmsConfig['slogan'], ENT_QUOTES),
		'icms_dirname' => @$icmsModule ? $icmsModule->getVar('dirname') : 'system',
		'icms_banner' => $icmsConfig['banners'] ? xoops_getbanner() : '&nbsp;',
		'icms_pagetitle' => isset($icmsModule) && is_object($icmsModule) 
			? $icmsModule->getVar('name') 
			: htmlspecialchars($icmsConfig['slogan'], ENT_QUOTES),
		'lang_login' => _LOGIN,
		'lang_username' => _USERNAME,
		'lang_password' => _PASSWORD,
		'lang_siteclosemsg' => $icmsConfig['closesite_text'])
	);

	foreach ($icmsConfigMetaFooter as $name => $value) {
		if (substr($name, 0, 5) == 'meta_') {
			$icmsTpl->assign("xoops_$name", htmlspecialchars($value, ENT_QUOTES));
		} else {
			$icmsTpl->assign("xoops_$name", $value);
		}
	}
	$icmsTpl->debugging = FALSE;
	$icmsTpl->debugging_ctrl = 'NONE';
	$icmsTpl->caching = 0;

	icms_loadLanguageFile("system", "customtag", TRUE);
	$icms_customtag_handler = icms_getModuleHandler("customtag", "system");
	$customtags_array = array();
	if (is_object($icmsTpl)) {
		foreach ($icms_customtag_handler->getCustomtagsByName() as $k => $v) {
			$customtags_array[$k] = $v->render();
		}
		$icmsTpl->assign('icmsCustomtags', $customtags_array);
	}

	$icmsTpl->display('db:system_siteclosed.html');
	exit();
}
unset($allowed, $group);

return TRUE;