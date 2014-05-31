<?php
/**
 * @copyright	The ImpressCMS Project <http://www.impresscms.org/>
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		svn: $Id: header.php 22269 2011-08-19 14:57:19Z phoenyx $
 */
defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

icms::$logger->stopTime('Module init');
icms::$logger->startTime('ICMS output init');

global $xoopsOption, $icmsConfig, $icmsModule;
$xoopsOption['theme_use_smarty'] = 1;

if (@$xoopsOption['template_main']) {
	if (FALSE === strpos($xoopsOption['template_main'], ':')) {
		$xoopsOption['template_main'] = 'db:' . $xoopsOption['template_main'];
	}
}
$xoopsThemeFactory = new icms_view_theme_Factory();
$xoopsThemeFactory->allowedThemes = $icmsConfig['theme_set_allowed'];
$xoopsThemeFactory->defaultTheme = $icmsConfig['theme_set'];

/**
 * @var icms_view_theme_Object
 */
$icmsTheme = $xoTheme =& $xoopsThemeFactory->createInstance(array('contentTemplate' => @$xoopsOption['template_main'],));
$GLOBALS['icmsTheme'] = $icmsTheme;
$GLOBALS['xoTheme'] = $xoTheme;
$xoopsTpl = $icmsTpl =& $xoTheme->template;
$GLOBALS['xoopsTpl'] = $xoopsTpl;
$GLOBALS['icmsTpl'] = $icmsTpl;
if ($icmsConfigMetaFooter['use_google_analytics'] === TRUE
	&& isset($icmsConfigMetaFooter['google_analytics']) && $icmsConfigMetaFooter['google_analytics'] != '') {
	/* Legacy GA urchin code */
	//$xoTheme->addScript('http://www.google-analytics.com/urchin.js',array('type' => 'text/javascript'),'_uacct = "UA-' . $icmsConfigMetaFooter['google_analytics'] . '";urchinTracker();');
	$scheme = parse_url(ICMS_URL, PHP_URL_SCHEME);
	if ($scheme == 'http') {
		/* New GA code, http protocol */
		$xoTheme->addScript('http://www.google-analytics.com/ga.js', array('type' => 'text/javascript'),'');
	} elseif ($scheme == 'https') {
		/* New GA code, https protocol */
		$xoTheme->addScript('https://ssl.google-analytics.com/ga.js', array('type' => 'text/javascript'),'');
	}
}
if (isset($icmsConfigMetaFooter['google_meta']) && $icmsConfigMetaFooter['google_meta'] != '') {
	$xoTheme->addMeta('meta', 'verify-v1', $icmsConfigMetaFooter['google_meta']);
	$xoTheme->addMeta('meta', 'google-site-verification', $icmsConfigMetaFooter['google_meta']);
}
// ################# Preload Trigger startOutputInit ##############
icms::$preload->triggerEvent('startOutputInit');

$xoTheme->addScript(ICMS_URL . '/include/xoops.js', array('type' => 'text/javascript'));
$xoTheme->addScript(ICMS_URL . '/include/linkexternal.js', array('type' => 'text/javascript'));
/**
 * Now system first checks for RTL, if it is enabled it'll just load it, otherwise it will load the normal (LTR) styles
 */
$xoTheme->addStylesheet(ICMS_URL . '/icms' . (@_ADM_USE_RTL === TRUE?'_rtl':'') . '.css', array('media' => 'screen'));

$style_info = '';
if (!empty($icmsConfigPlugins['sanitizer_plugins'])) {
	foreach ($icmsConfigPlugins['sanitizer_plugins'] as $key) {
		if (empty( $key ) )
		continue;
		if (file_exists(ICMS_PLUGINS_PATH . '/textsanitizer/' . $key . '/' . $key . '.css')) {
			$xoTheme->addStylesheet(ICMS_PLUGINS_URL . '/textsanitizer/' . $key . '/' . $key . '.css', array('media' => 'screen'));
		} else {
			$extension = include_once ICMS_PLUGINS_PATH . '/textsanitizer/' . $key . '/' . $key . '.php';
			$func = 'style_' . $key;
			if (function_exists($func)) {
				$style_info = $func();
				if (!empty($style_info)) {
					if (!file_exists(ICMS_ROOT_PATH . '/' . $style_info)) {
						$xoTheme->addStylesheet('', array('media' => 'screen'), $style_info);
					} else {
						$xoTheme->addStylesheet($style_info, array('media' => 'screen'));
					}
				}
			}
		}
	}
}

$xoTheme->addScript(ICMS_LIBRARIES_URL . '/jquery/jquery.js', array('type' => 'text/javascript'));
$xoTheme->addScript(ICMS_LIBRARIES_URL . '/jquery/jquery-migrate-1.2.1.min.js', array('type' => 'text/javascript'));
$xoTheme->addScript(ICMS_LIBRARIES_URL . '/jquery/ui/ui.min.js', array('type' => 'text/javascript'));
$xoTheme->addScript(ICMS_URL . '/libraries/jquery/helptip.js', array( 'type' => 'text/javascript'));
$xoTheme->addStylesheet(ICMS_LIBRARIES_URL . '/jquery/ui/css/ui-smoothness/ui.css', array('media' => 'screen'));
$xoTheme->addStylesheet(ICMS_LIBRARIES_URL . '/jquery/jgrowl'
	. (( defined('_ADM_USE_RTL') && _ADM_USE_RTL )?'_rtl':'') . '.css', array('media' => 'screen'));
if (! empty( $_SESSION['redirect_message'])) {
	$xoTheme->addScript(ICMS_LIBRARIES_URL . '/jquery/jgrowl.js', array('type' => 'text/javascript'));
	$xoTheme->addScript('', array('type' => 'text/javascript'), '
	if (!window.console || !console.firebug) {
		var names = ["log", "debug", "info", "warn", "error", "assert", "dir", "dirxml", "group", "groupEnd",
					"time", "timeEnd", "count", "trace", "profile", "profileEnd"];
		window.console = {};

		for (var i = 0; i < names.length; ++i) window.console[names[i]] = function() {};
	}

	(function($) {
		$(document).ready(function() {
			$.jGrowl("' . $_SESSION['redirect_message'] . '", {  life:5000 , position: "center", speed: "slow" });
		});
	})(jQuery);
	');
	unset( $_SESSION['redirect_message'] ) ;
}

$xoTheme->addStylesheet(ICMS_LIBRARIES_URL . '/jquery/colorbox/colorbox.css');
$xoTheme->addScript(ICMS_LIBRARIES_URL . '/jquery/colorbox/jquery.colorbox-min.js');

if (@is_object($xoTheme->plugins['icms_view_PageBuilder'])) {
	$aggreg =& $xoTheme->plugins['icms_view_PageBuilder'];
	$xoopsTpl->assign_by_ref('xoBlocks', $aggreg->blocks);

	// Backward compatibility code for pre 2.0.14 themes
	$xoopsTpl->assign_by_ref('xoops_lblocks', $aggreg->blocks['canvas_left']);
	$xoopsTpl->assign_by_ref('xoops_rblocks', $aggreg->blocks['canvas_right']);
	$xoopsTpl->assign_by_ref('xoops_ccblocks', $aggreg->blocks['page_topcenter']);
	$xoopsTpl->assign_by_ref('xoops_clblocks', $aggreg->blocks['page_topleft']);
	$xoopsTpl->assign_by_ref('xoops_crblocks', $aggreg->blocks['page_topright']);

	$xoopsTpl->assign('xoops_showlblock', !empty($aggreg->blocks['canvas_left']));
	$xoopsTpl->assign('xoops_showrblock', !empty($aggreg->blocks['canvas_right']));
	$xoopsTpl->assign('xoops_showcblock', !empty($aggreg->blocks['page_topcenter'])
												|| !empty($aggreg->blocks['page_topleft'])
												|| !empty($aggreg->blocks['page_topright'])
											);
}

if ($icmsModule )
$xoTheme->contentCacheLifetime = @$icmsConfig['module_cache'][$icmsModule->getVar('mid', 'n')];

if ($xoTheme->checkCache()) exit();

if (!isset($xoopsOption['template_main']) && $icmsModule) {
	// new themes using Smarty does not have old functions that are required in old modules, so include them now
	include ICMS_INCLUDE_PATH . '/old_theme_functions.php';
	// Need this also
	$xoopsTheme['thename'] = $icmsConfig['theme_set'];
	ob_start();
}

// Assigning the selected language as a smarty var
$xoopsTpl->assign('icmsLang', $icmsConfig['language']);

icms::$logger->stopTime('ICMS output init');
icms::$logger->startTime('Module display');
