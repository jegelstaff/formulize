<?php
/**
 * Wiki TextSanitizer plugin
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @since		1.2
 * @package		plugins
 * @subpackage	textsanitizer
 * @version		$Id: wiki.php 19775 2010-07-11 18:54:25Z malanciault $
 */
/**
 * Link to associated page on Wikipedia for the enclosed text
 *
 * @var unknown_type
 */
define('WIKI_LINK',	'http://'._LANGCODE.'.wikipedia.org/wiki/%s');
/**
 *
 * Locates and replaced marked text with a link to the wiki
 * @param unknown_type $ts
 * @param unknown_type $text
 */
function textsanitizer_wiki(&$ts, $text) {
	$patterns[] = "/\[\[([^\]]*)\]\]/esU";
	$replacements[] = "wikiLink( '\\1' )";
	return preg_replace($patterns, $replacements, $text);
}

/**
 *
 * Creates the link to the wiki page
 * @param $text
 */
function wikiLink($text) {
	if (empty($text) ) return $text;
	$ret = "<a
		href='" . sprintf(WIKI_LINK, $text) . "'
		target='_blank'
		title=''>".$text."</a>";
	return $ret;
}

/**
 *
 * Adds button and script to the editor
 * @param $ele_name
 */
function render_wiki($ele_name) {
	global $xoTheme;
	$dirname = basename(dirname(__FILE__));
	if (isset($xoTheme)) {
		$xoTheme->addScript(
			ICMS_URL . '/plugins/textsanitizer/' . $dirname . '/' . $dirname . '.js',
			array('type' => 'text/javascript'));
	}
	$code = "<img
		onclick='javascript:icmsCodeWIKI(\"" . $ele_name . "\", \"" . htmlspecialchars(_ENTERWIKICODE, ENT_QUOTES)."\");'
		onmouseover='style.cursor=\"pointer\"'
		src='" . ICMS_URL . "/plugins/textsanitizer/" . $dirname . "/wiki.png'
		alt='wiki'
		title='Wiki' />&nbsp;";
	/**
	 * Using this method You can add a file to load your java script informations
	 */
	$javascript = 'plugins/textsanitizer/' . $dirname . '/wiki.js';;
	return array($code, $javascript);
}

/**
 *
 * Enter specific styling for this plugin
 */
function style_wiki() {
	$style_info = '';
	return $style_info;
}
