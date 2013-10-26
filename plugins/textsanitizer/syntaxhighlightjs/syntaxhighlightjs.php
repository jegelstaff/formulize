<?php
/**
 * JavaScript Highlighter TextSanitizer plugin
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @since		1.2
 * @package		plugins
 * @subpackage	textsanitizer
 * @version		$Id: syntaxhighlightjs.php 21537 2011-04-15 17:55:34Z m0nty_ $
 */

/**
 * Locates and replaces passed text with JS highlighted code block
 *
 * @param object $ts textsanitizer instance
 * @param string $text the search terms
 */
function textsanitizer_syntaxhighlightjs(&$ts, $text) {
	$patterns[] = "/\[code_js](.*)\[\/code_js\]/esU";
	$replacements[] = "textsanitizer_geshi_js_highlight( '\\1' )";
	return preg_replace($patterns, $replacements, $text);
}

/**
 * Parses passed code into highlighted JS
 *
 * @param $source
 */
function textsanitizer_geshi_js_highlight( $source) {
	if (!@include_once ICMS_LIBRARIES_PATH . '/geshi/geshi.php' ) return false;
	$source = icms_core_DataFilter::undoHtmlSpecialChars($source);

	// Create the new GeSHi object, passing relevant stuff
	$geshi = new GeSHi($source, 'javascript');
	// Enclose the code in a <div>
	$geshi->set_header_type(GESHI_HEADER_NONE);

	// Sets the proper encoding charset other than "ISO-8859-1"
	$geshi->set_encoding(_CHARSET);

	$geshi->set_link_target ( "_blank" );

	// Parse the code
	$code = $geshi->parse_code();
	$code = "<div class=\"icmsCodeJs\"><code>" . $code . "</code></div>";
	return $code;
}

/**
 *
 *  Adds button and script to the editor
 * @param $ele_name
 */
function render_syntaxhighlightjs($ele_name) {
	global $xoTheme;
	$javascript='';
	$dirname = basename(dirname(__FILE__));
	if (isset($xoTheme)) {
		$xoTheme->addScript(
			ICMS_URL . '/plugins/textsanitizer/' . $dirname . '/' . $dirname . '.js',
			array('type' => 'text/javascript'));
	}
	$code = "<img
        	onclick='javascript:icmsCodeJS(\"" . $ele_name . "\", \"" . htmlspecialchars(_ENTERJSCODE, ENT_QUOTES) . "\");'
        	onmouseover='style.cursor=\"pointer\"'
        	src='" . ICMS_URL."/plugins/textsanitizer/" . $dirname . "/js.png'
        	alt='js'
        	title='Javascript' />&nbsp;";
	return array($code, $javascript);
}
/*function style_syntaxhighlightjs() {
 $style_info = '.icmsCodeJs { background-color: #FAFAFA; color: #444; font-size: .9em; line-height: 1.2em; text-align: justify; border: #c2cdd6 1px dashed;}';
 return $style_info;
 }*/
