<?php
/**
 * Hidden Content TextSanitizer plugin
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @since		1.2
 * @package		plugins
 * @subpackage	textsanitizer
 * @version		$Id: hiddencontent.php 10326 2010-07-11 18:54:25Z malanciault $
 */
/**
 *
 * Replaces text delimited with [hide][/hide] with appropriate values
 *
 * @param object $ts textsanitizer instance
 * @param string $text text to be marked as hidden
 */
function textsanitizer_hiddencontent(&$ts, $text) {
	$patterns[] = "/\[hide](.*)\[\/hide\]/sU";
	if (!empty($_SESSION['xoopsUserId']) && $_SESSION['xoopsUserId']) {
		$replacements[] = _HIDDENC . '<div class="icmsHidden">\\1</div>';
	} else {
		$replacements[] = _HIDDENC . '<div class="icmsHidden">' . _HIDDENTEXT . '</div>';
	}
	return preg_replace($patterns, $replacements, $text);
}

/**
 * Adds javascript and icon to editor
 *
 * @param $ele_name
 */
function render_hiddencontent($ele_name) {
	global $xoTheme;
	$javascript='';
	$dirname = basename(dirname(__FILE__));
	if (isset($xoTheme)) {
		$xoTheme->addScript(
			ICMS_URL . '/plugins/textsanitizer/' . $dirname . '/' . $dirname . '.js',
			array('type' => 'text/javascript'));
	}
	$code = "<img
		onclick='javascript:icmsCodeHidden(\"" . $ele_name . "\", \"" . htmlspecialchars(_ENTERHIDDEN, ENT_QUOTES) . "\");'
		onmouseover='style.cursor=\"pointer\"' src='" . ICMS_URL . "/images/hide.gif'
		alt='hide'
		title='" . $dirname . "' />&nbsp;";
	//$javascript = 'plugins/textsanitizer/'.basename(dirname(__FILE__)).'/hiddencontent.js';;
	return array($code, $javascript);
}
/**
 * You can use a function like this to add css information
 */

/*
 function style_hiddencontent() {
 $style_info = '.icmsHidden { background-color: #FAFAFA; color: #444; font-size: .9em; line-height: 1.2em; text-align: justify; border: #c2cdd6 1px dashed;}';
 return $style_info;
 }
 */
