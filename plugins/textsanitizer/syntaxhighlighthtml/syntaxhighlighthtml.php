<?php
/**
 * HTML Highlighter TextSanitizer plugin
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @since		1.2
 * @package		plugins
 * @subpackage	textsanitizer
 * @version		$Id: syntaxhighlighthtml.php 21537 2011-04-15 17:55:34Z m0nty_ $
 */

/**
 * Locates and replaces marked text with highlighted HTML text
 *
 * @param object $ts textsanitizer instance
 * @param string $text the search terms
 */
function textsanitizer_syntaxhighlighthtml(&$ts, $text) {
	$patterns[] = "/\[code_html](.*)\[\/code_html\]/esU";
	$replacements[] = "textsanitizer_geshi_html_highlight( '\\1' )";
	return preg_replace($patterns, $replacements, $text);
}

/**
 * Returns passed code snippet as HTML
 *
 * @param $source
 */
function textsanitizer_geshi_html_highlight($source) {
	if (!@include_once ICMS_LIBRARIES_PATH . '/geshi/geshi.php' ) return false;
	$source = icms_core_DataFilter::undoHtmlSpecialChars($source);

	// Create the new GeSHi object, passing relevant stuff
	$geshi = new GeSHi($source, 'html4strict');
	// Enclose the code in a <div>
	$geshi->set_header_type(GESHI_HEADER_NONE);

	// Sets the proper encoding charset other than "ISO-8859-1"
	$geshi->set_encoding(_CHARSET);

	$geshi->set_link_target ( "_blank" );

	// Parse the code
	$code = $geshi->parse_code();
	$code = "<div class=\"icmsCodeHtml\"><pre><code>".$code."</code></pre></div>";
	return $code;
}

/**
 * Adds button and script to the editor
 *
 * @param $ele_name
 */
function render_syntaxhighlighthtml($ele_name) {
	global $xoTheme;
	$javascript='';
	$dirname = basename(dirname(__FILE__));
	if (isset($xoTheme)) {
		$xoTheme->addScript(
			ICMS_URL.'/plugins/textsanitizer/' . $dirname . '/' . $dirname . '.js',
			array('type' => 'text/javascript'));
	}
	$code = "<img
		onclick='javascript:icmsCodeHTML(\"" . $ele_name . "\", \"" . htmlspecialchars(_ENTERHTMLCODE, ENT_QUOTES) . "\");'
		onmouseover='style.cursor=\"pointer\"'
		src='" . ICMS_URL . "/plugins/textsanitizer/" . $dirname . "/html.png'
		alt='html'
		title='HTML' />&nbsp;";
	return array($code, $javascript);
}

/**
 *
 * Enter specific styling for this plugin
 */
function style_syntaxhighlighthtml() {
	$style_info = '';
	return $style_info;
}
