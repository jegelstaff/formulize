<?php
/**
 * Temporary solution for "site closed" status
 *
 * @copyright	The Xoops project http://www.xoops.org/
 * @license		http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author		phppp (infomax@gmail.com)
 * @since		Xoops 2.0.17
 * @package core
 * @version		$Id: site-closed.php 9520 2009-11-11 14:32:52Z pesianstranger $
 */

if (! defined ( "ICMS_ROOT_PATH" )) {
	die ( "ImpressCMS root path not defined" );
}

global $icmsConfig, $icmsUser, $xoopsOption;

$allowed = false;
if (isset($xoopsOption['ignore_closed_site']) && $xoopsOption['ignore_closed_site']) {
	$allowed = true;
} elseif (is_object ( $icmsUser )) {
	foreach ( $icmsUser->getGroups () as $group ) {
		if (in_array ( $group, $icmsConfig ['closesite_okgrp'] ) || ICMS_GROUP_ADMIN == $group) {
			$allowed = true;
			break;
		}
	}
} elseif (! empty ( $_POST ['xoops_login'] )) {
	include_once ICMS_ROOT_PATH . '/include/checklogin.php';
	exit ();
}

if (! $allowed) {
	include_once ICMS_ROOT_PATH . "/include/customtag.php";
	require_once ICMS_ROOT_PATH . '/class/template.php';
	require_once ICMS_ROOT_PATH . '/class/theme.php';

	$xoopsThemeFactory = & new xos_opal_ThemeFactory ( );
	$xoopsThemeFactory->allowedThemes = $icmsConfig ['theme_set_allowed'];
	$xoopsThemeFactory->defaultTheme = $icmsConfig ['theme_set'];
	$xoTheme = & $xoopsThemeFactory->createInstance ( array ("plugins" => array ( ) ) );
	$xoTheme->addScript ( '/include/xoops.js', array ('type' => 'text/javascript' ) );
	$xoTheme->addStylesheet(ICMS_URL."/icms".(( defined('_ADM_USE_RTL') && _ADM_USE_RTL )?"_rtl":"").".css", array("media" => "screen"));
	$xoopsTpl = & $xoTheme->template;

	$xoopsTpl->assign ( array ('xoops_theme' => $icmsConfig ['theme_set'], 'xoops_imageurl' => ICMS_THEME_URL . '/' . $icmsConfig ['theme_set'] . '/', 'xoops_themecss' => xoops_getcss ( $icmsConfig ['theme_set'] ), 'xoops_requesturi' => htmlspecialchars ( $_SERVER ['REQUEST_URI'], ENT_QUOTES ), 'xoops_sitename' => htmlspecialchars ( $icmsConfig ['sitename'], ENT_QUOTES ), 'xoops_slogan' => htmlspecialchars ( $icmsConfig ['slogan'], ENT_QUOTES ), 'xoops_dirname' => @$icmsModule ? $icmsModule->getVar ( 'dirname' ) : 'system', 'xoops_banner' => $icmsConfig ['banners'] ? xoops_getbanner () : '&nbsp;', 'xoops_pagetitle' => isset ( $icmsModule ) && is_object ( $icmsModule ) ? $icmsModule->getVar ( 'name' ) : htmlspecialchars ( $icmsConfig ['slogan'], ENT_QUOTES ), 'lang_login' => _LOGIN, 'lang_username' => _USERNAME, 'lang_password' => _PASSWORD, 'lang_siteclosemsg' => $icmsConfig ['closesite_text'] )
	 );

	$config_handler = & xoops_gethandler ( 'config' );
	$criteria = new CriteriaCompo ( new Criteria ( 'conf_modid', 0 ) );
	$criteria->add ( new Criteria ( 'conf_catid', XOOPS_CONF_METAFOOTER ) );
	$config = $config_handler->getConfigs ( $criteria, true );
	foreach ( array_keys ( $config ) as $i ) {
		$name = $config [$i]->getVar ( 'conf_name', 'n' );
		$value = $config [$i]->getVar ( 'conf_value', 'n' );
		if (substr ( $name, 0, 5 ) == 'meta_') {
			$xoopsTpl->assign ( "xoops_$name", htmlspecialchars ( $value, ENT_QUOTES ) );
		} else {
			// prefix each tag with 'xoops_'
			$xoopsTpl->assign ( "xoops_$name", $value );
		}
	}
	$xoopsTpl->debugging = false;
	$xoopsTpl->debugging_ctrl = 'NONE';

	$xoopsTpl->caching = 0;

	global $icms_customtag_handler;
	$customtags_array = array ( );
	if (is_object ( $xoopsTpl )) {
		foreach ( $icms_customtag_handler->objects as $k => $v ) {
			$customtags_array [$k] = $v->render ();
		}
		$xoopsTpl->assign ( 'icmsCustomtags', $customtags_array );
	}
	
	$xoopsTpl->display ( 'db:system_siteclosed.html' );
	exit ();
}
unset ( $allowed, $group );

return true;

?>