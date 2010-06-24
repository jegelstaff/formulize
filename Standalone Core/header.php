<?php
/**
* @copyright	The XOOPS Project <http://www.xoops.org/> 
* @copyright	XOOPS_copyrights.txt
* @copyright	The ImpressCMS Project <http://www.impresscms.org/>
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		core
* @since		XOOPS
* @author		The XOOPS Project Community <http://www.xoops.org> 
* @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id: header.php 9549 2009-11-14 14:13:02Z pesianstranger $
*/
defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

if( !isset( $xoopsLogger ) )
	$xoopsLogger =& $GLOBALS['xoopsLogger'];
if( !isset($icmsPreloadHandler) )
	$icmsPreloadHandler =& $GLOBALS['icmsPreloadHandler'];

$xoopsLogger->stopTime('Module init');
$xoopsLogger->startTime('ICMS output init');

if($icmsConfig['theme_set'] != 'default' && file_exists(ICMS_THEME_PATH.'/'.$icmsConfig['theme_set'].'/theme.php'))
{
	/** For backwards compatibility with XOOPS 1.3.x */
  require_once ICMS_ROOT_PATH.'/include/xoops13_header.php';
}
else
{
	global $xoopsOption, $icmsConfig, $icmsModule;
	$xoopsOption['theme_use_smarty'] = 1;
	
	/**  include Smarty template engine and initialize it*/
	require_once ICMS_ROOT_PATH.'/class/template.php';
	require_once ICMS_ROOT_PATH.'/class/theme.php';
	require_once ICMS_ROOT_PATH.'/class/theme_blocks.php';

	if(@$xoopsOption['template_main'])
	{
		if(false === strpos($xoopsOption['template_main'], ':')) {$xoopsOption['template_main'] = 'db:' . $xoopsOption['template_main'];}
	}
	$xoopsThemeFactory = new xos_opal_ThemeFactory();
	$xoopsThemeFactory->allowedThemes = $icmsConfig['theme_set_allowed'];
	$xoopsThemeFactory->defaultTheme = $icmsConfig['theme_set'];

	/**
	 * @var xos_opal_Theme
	 */
	$icmsTheme = $xoTheme =& $xoopsThemeFactory->createInstance(array('contentTemplate' => @$xoopsOption['template_main'],));
	$xoopsTpl = $icmsTpl =& $xoTheme->template;
	if ($icmsConfigMetaFooter['use_google_analytics'] == true && isset($icmsConfigMetaFooter['google_analytics']) && $icmsConfigMetaFooter['google_analytics'] != ''){

		/* Legacy GA urchin code */
		//$xoTheme->addScript('http://www.google-analytics.com/urchin.js',array('type' => 'text/javascript'),'_uacct = "UA-'.$icmsConfigMetaFooter['google_analytics'].'";urchinTracker();');
		$scheme = parse_url(ICMS_URL, PHP_URL_SCHEME);
		if ($scheme == 'http'){
			/* New GA code, http protocol */
			$xoTheme->addScript('http://www.google-analytics.com/ga.js',array('type' => 'text/javascript'),'');
		} elseif ($scheme == 'https') {
			/* New GA code, https protocol */
			$xoTheme->addScript('https://ssl.google-analytics.com/ga.js',array('type' => 'text/javascript'),'');
		}
	}
	if (isset($icmsConfigMetaFooter['google_meta']) && $icmsConfigMetaFooter['google_meta'] != ''){
		$xoTheme->addMeta('meta','verify-v1',$icmsConfigMetaFooter['google_meta']);
		$xoTheme->addMeta('meta','google-site-verification',$icmsConfigMetaFooter['google_meta']);
	}
	// ################# Preload Trigger startOutputInit ##############
	$icmsPreloadHandler->triggerEvent('startOutputInit');

	$xoTheme->addScript(ICMS_URL.'/include/xoops.js', array('type' => 'text/javascript'));
	$xoTheme->addScript(ICMS_URL.'/include/linkexternal.js', array('type' => 'text/javascript'));
	/** 
	 * Now system first checks for RTL, if it is enabled it'll just load it, otherwise it will load the normal (LTR) styles
	 */
	$xoTheme->addStylesheet(ICMS_URL.'/icms'.(@_ADM_USE_RTL == true?'_rtl':'').'.css', array('media' => 'screen'));

	/**
	 * Weird, but need extra <script> tags for 2.0.x themes
	 *
	 * $xoopsTpl->assign('xoops_js', '//--></script><script type="text/javascript" src="'.ICMS_URL.'/include/xoops.js"></script><script type="text/javascript"><!--');
	 * $xoopsTpl->assign('linkexternal_js', '//--></script><script type="text/javascript" src="'.ICMS_URL.'/include/linkexternal.js"></script><script type="text/javascript"><!--');
	 * 
	 * Weird, but we need to bring plugins java information in header because doing it form elsewhere will drop system required Java Script files!! 
	 */

/*	$jscript = '';
	if(class_exists('XoopsFormDhtmlTextArea')){
		foreach ($icmsConfigPlugins['sanitizer_plugins'] as $key) {
			if(empty($key)) continue;
			if(file_exists(ICMS_ROOT_PATH.'/plugins/textsanitizer/'.$key.'/'.$key.'.js')){
				$xoTheme->addScript(ICMS_URL.'/plugins/textsanitizer/'.$key.'/'.$key.'.js', array('type' => 'text/javascript'));
			}else{
				$extension = include_once ICMS_ROOT_PATH.'/plugins/textsanitizer/'.$key.'/'.$key.'.php';
				$func = 'render_'.$key;
				if ( function_exists($func) ) {
					@list($encode, $jscript) = $func($ele_name);
					if (!empty($jscript)) {
						if(!file_exists(ICMS_ROOT_PATH.'/'.$jscript)){
							$xoTheme->addScript('', array('type' => 'text/javascript'), $jscript);
						}else{
							$xoTheme->addScript($jscript, array('type' => 'text/javascript'));
						}
					}
				}
			}
		}
	}
*/
	$style_info = '';
	if(!empty($icmsConfigPlugins['sanitizer_plugins'])){
		foreach ($icmsConfigPlugins['sanitizer_plugins'] as $key) {
			if( empty( $key ) )
				continue;
			if(file_exists(ICMS_ROOT_PATH.'/plugins/textsanitizer/'.$key.'/'.$key.'.css')){
				$xoTheme->addStylesheet(ICMS_URL.'/plugins/textsanitizer/'.$key.'/'.$key.'.css', array('media' => 'screen'));
			}else{
				$extension = include_once ICMS_ROOT_PATH.'/plugins/textsanitizer/'.$key.'/'.$key.'.php';
				$func = 'style_'.$key;
				if ( function_exists($func) ) {
					$style_info = $func();
				 	if (!empty($style_info)) {
			 			if(!file_exists(ICMS_ROOT_PATH.'/'.$style_info)){
							$xoTheme->addStylesheet('', array('media' => 'screen'), $style_info);
						}else{
							$xoTheme->addStylesheet($style_info, array('media' => 'screen'));
						}
					}
				}
			}
		}
	}

	$xoTheme->addScript(ICMS_URL.'/libraries/jquery/jquery.js', array('type' => 'text/javascript'));
	if(! empty( $_SESSION['redirect_message'] )){
		$xoTheme->addScript(ICMS_URL.'/libraries/jquery/jgrowl.js', array('type' => 'text/javascript'));
		$xoTheme->addStylesheet(ICMS_URL.'/libraries/jquery/jgrowl'.(( defined('_ADM_USE_RTL') && _ADM_USE_RTL )?'_rtl':'').'.css', array('media' => 'screen'));
		$xoTheme->addScript('', array('type' => 'text/javascript'), '
		if (!window.console || !console.firebug) {
			var names = ["log", "debug", "info", "warn", "error", "assert", "dir", "dirxml", "group", "groupEnd", "time", "timeEnd", "count", "trace", "profile", "profileEnd"];
			window.console = {};
			
			for (var i = 0; i < names.length; ++i) window.console[names[i]] = function() {};
		}

		(function($){
			$(document).ready(function(){
				$.jGrowl("'.$_SESSION['redirect_message'].'", {  life:5000 , position: "center", speed: "slow" });
			});
		})(jQuery);
		');
		unset( $_SESSION['redirect_message'] ) ;
	}

	$xoTheme->addScript(ICMS_URL.'/libraries/jquery/ui/ui.core.js', array('type' => 'text/javascript'));
	$xoTheme->addScript(ICMS_URL.'/libraries/jquery/ui/ui.dialog.js', array('type' => 'text/javascript'));
	$xoTheme->addStylesheet(ICMS_URL.'/libraries/jquery/ui/themes/base/ui.all.css', array('media' => 'screen'));

	$xoTheme->addStylesheet(ICMS_LIBRARIES_URL.'/jquery/colorbox/colorbox.css');
	$xoTheme->addStylesheet(ICMS_LIBRARIES_URL.'/jquery/colorbox/colorbox-custom.css');
	if(ereg('msie', strtolower($_SERVER['HTTP_USER_AGENT']))) {$xoTheme->addStylesheet(ICMS_LIBRARIES_URL.'/jquery/colorbox/colorbox-custom-ie.css');}
	$xoTheme->addScript(ICMS_LIBRARIES_URL.'/jquery/colorbox/colorbox.js');
	$xoTheme->addScript(ICMS_LIBRARIES_URL.'/jquery/colorbox/lightbox.js');
	
	if(@is_object($xoTheme->plugins['xos_logos_PageBuilder'])) {
		$aggreg =& $xoTheme->plugins['xos_logos_PageBuilder'];
		$xoopsTpl->assign_by_ref('xoBlocks', $aggreg->blocks);

		// Backward compatibility code for pre 2.0.14 themes
		$xoopsTpl->assign_by_ref('xoops_lblocks', $aggreg->blocks['canvas_left']);
		$xoopsTpl->assign_by_ref('xoops_rblocks', $aggreg->blocks['canvas_right']);
		$xoopsTpl->assign_by_ref('xoops_ccblocks', $aggreg->blocks['page_topcenter']);
		$xoopsTpl->assign_by_ref('xoops_clblocks', $aggreg->blocks['page_topleft']);
		$xoopsTpl->assign_by_ref('xoops_crblocks', $aggreg->blocks['page_topright']);
		
		$xoopsTpl->assign('xoops_showlblock', !empty($aggreg->blocks['canvas_left']));
		$xoopsTpl->assign('xoops_showrblock', !empty($aggreg->blocks['canvas_right']));
		$xoopsTpl->assign('xoops_showcblock', !empty($aggreg->blocks['page_topcenter']) || !empty($aggreg->blocks['page_topleft']) || !empty($aggreg->blocks['page_topright']));
	}

	if( $icmsModule ) 
		$xoTheme->contentCacheLifetime = @$icmsConfig['module_cache'][$icmsModule->getVar('mid', 'n')];

	if( $xoTheme->checkCache() )
		exit();

	if(!isset($xoopsOption['template_main']) && $icmsModule) {
		// new themes using Smarty does not have old functions that are required in old modules, so include them now
		include ICMS_ROOT_PATH.'/include/old_theme_functions.php';
		// Need this also
		$xoopsTheme['thename'] = $icmsConfig['theme_set'];
		ob_start();
	}

	// Assigning the selected language as a smarty var
	$xoopsTpl->assign('icmsLang', $icmsConfig['language']);

	$xoopsLogger->stopTime('ICMS output init');
	$xoopsLogger->startTime('Module display');
}

?>