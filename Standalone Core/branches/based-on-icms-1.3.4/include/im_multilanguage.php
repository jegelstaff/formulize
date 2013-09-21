<?php
/**
 * This is the file handling multilanguage on the site
 *
 * @license GNU
 * @author GIJOE
 * @version $Id: im_multilanguage.php 11194 2011-04-20 23:49:33Z skenow $
 * @package core
 */

// CONFIGURATIONS BEGIN

// list the language tags separated with comma
//define('EASIESTML_LANGS','xlang:en,xlang:ja'); // This is a sample of long pattern against tag misunderstanding [xlang:en]english[/xlang:en]
define('EASIESTML_LANGS', $icmsConfigMultilang['ml_tags']); // [en]english[/en]  [ja]japananese[/ja] common

// list the language images separated with comma
define('EASIESTML_LANGIMAGES', 'images/flags/english.gif,images/flags/french.gif');

// list the language names separated with comma (these will be alt of <img>)
define('EASIESTML_LANGNAMES', $icmsConfigMultilang['ml_captions']);

// list language - accept_chaset patterns (perl regex) separated with comma
define('EASIESTML_ACCEPT_CHARSET_REGEXES', ',/shift_jis/i');

// list language - accept_language patterns (perl regex) separated with comma
define('EASIESTML_ACCEPT_LANGUAGE_REGEXES', '/^en/,/^ja/');

// charset in Content-Type separated with comma (only for fastestcache)
define('EASIESTML_CHARSETS', $icmsConfigMultilang['ml_charset']);

// tag name for language image  (default [mlimg]. don't include specialchars)
define('EASIESTML_IMAGETAG', 'mlimg');

// make regular expression which disallows language tags to cross it
define('EASIESTML_NEVERCROSSREGEX', '');

// the life time of language selection stored in cookie
define('EASIESTML_COOKIELIFETIME', 365*86400);

// default language
define('EASIESTML_DEFAULT_LANG', 0);

// CONFIGURATIONS END

// Patch check
//if (! defined( 'ICMS_ROOT_PATH') || ! defined( 'ICMS_URL') || defined( 'XOOPS_SIDEBLOCK_LEFT')) die( "You should patch just after define('ICMS_URL', ...) in mainfile.php") ;
// moving the inclusing of easiest ml just after the xoopsDB creation because we need the db...
if (! defined('ICMS_ROOT_PATH') || ! defined('ICMS_URL')) die("You should patch just after define('ICMS_URL', ...) in mainfile.php");

// Target check
if (! preg_match('?' . preg_quote(ICMS_ROOT_PATH, '?') . '(/common/)?', $_SERVER['SCRIPT_FILENAME'])) {
	global $easiestml_lang;

	// get cookie path
	$xoops_cookie_path = defined('XOOPS_COOKIE_PATH') ? XOOPS_COOKIE_PATH : preg_replace('?http://[^/]+(/.*)$?', "$1", ICMS_URL);
	if ($xoops_cookie_path == ICMS_URL) $xoops_cookie_path = '/';

	// deciding the current language (the priority is important)
	$easiestml_langs = explode(',', EASIESTML_LANGS);
	$easiestml_charsets = explode(',', EASIESTML_CHARSETS);
	if (! empty($_GET['lang']) && $_GET['lang'] == 'all') {
		// set by GET (all)
		$easiestml_lang = 'all';
	} else if (! empty($_GET['lang']) && ($offset = array_search($_GET['lang'], $easiestml_langs)) !== false) {
		// set by GET (other than all)
		$easiestml_lang = $_GET['lang'];
		$easiestml_charset = $easiestml_charsets[$offset];
		setcookie('lang', $easiestml_lang, time() + EASIESTML_COOKIELIFETIME, $xoops_cookie_path, '', 0);
	} else if (! empty($_COOKIE['lang']) && ($offset = array_search($_COOKIE['lang'], $easiestml_langs)) !== false) {
		// set by COOKIE (other than all)
		$easiestml_lang = $_COOKIE['lang'];
		$easiestml_charset = $easiestml_charsets[$offset];
	} else if (! empty($_SERVER['HTTP_ACCEPT_CHARSET'])) {
		// set by HTTP_ACCEPT_CHARSET pattern
		$offset = 0;
		foreach (explode(',', EASIESTML_ACCEPT_CHARSET_REGEXES) as $pattern) {
			if ($pattern && preg_match($pattern, $_SERVER['HTTP_ACCEPT_CHARSET'])) {
				$easiestml_lang = $easiestml_langs[$offset];
				$easiestml_charset = $easiestml_charsets[$offset];
				break;
			}
			$offset ++;
		}
	} else if (! empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		// set by HTTP_ACCEPT_LANGUAGE pattern
		$offset = 0;
		foreach (explode(',', EASIESTML_ACCEPT_LANGUAGE_REGEXES) as $pattern) {
			if ($pattern && preg_match($pattern, $_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
				$easiestml_lang = $easiestml_langs[$offset];
				$easiestml_charset = $easiestml_charsets[$offset];
				break;
			}
			$offset++;
		}
	}

	if (empty($easiestml_lang)) {
		$easiestml_lang = $easiestml_langs[EASIESTML_DEFAULT_LANG];
		$easiestml_charset = $easiestml_charsets[EASIESTML_DEFAULT_LANG];
	}

	// charset for Content-Type

	ob_start('easiestml');
}

/**
 * The multilanguage function
 *
 * @param string $s The passed string
 * @return string $s	The (translated?) string
 */
function easiestml($s) {
	global $easiestml_lang, $icmsConfigMultilang;

	// all mode for debug (allowed to system admin only)
	if (is_object(icms::$user) && icms::$user->isAdmin(1) && ! empty($_GET['lang']) && $_GET['lang'] == 'all') {
		return $s;
	}

	$easiestml_langs = explode(',', EASIESTML_LANGS);
	// protection against some injection
	if (! in_array($easiestml_lang, $easiestml_langs)) {
		$easiestml_lang = $easiestml_langs[0];
	}

	// escape brackets inside of <input type="text" value="...">
	// $s = preg_replace_callback( '/(\<input)(?=.*type\=[\'\"]?text[\'\"]?)([^>]*)(\>)/isU' , 'easiestml_escape_bracket' , $s) ;

	// Fix for bug #1905485 in tracker
	$s = preg_replace_callback('/(\<input\b(?![^\>]*\btype=([\'"]?)(submit|image))[^\>]*\>)/isU', 'easiestml_escape_bracket_input', $s);
	$s = preg_replace_callback('/(\<input)([^>]*)(\>)/isU', 'easiestml_escape_bracket_textbox', $s);

	// escape brackets inside of <textarea></textarea>
	$s = preg_replace_callback('/(\<textarea[^>]*\>)(.*)(<\/textarea\>)/isU', 'easiestml_escape_bracket_textarea', $s);

	// multilanguage image tag
	$langnames = explode(',', $icmsConfigMultilang['ml_names']);
	foreach ($langnames as $v) {
		$langimages[] = "images/flags/$v.gif";
	}
	$langnames = explode(',', EASIESTML_LANGNAMES);
	if (empty($_SERVER['QUERY_STRING'])) {
		$link_base = basename($_SERVER['SCRIPT_NAME']) . '?lang=';
	} else if (($pos = strpos($_SERVER['QUERY_STRING'], 'lang=')) === false) {
		$link_base = basename($_SERVER['SCRIPT_NAME']) . '?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES) . '&amp;lang=';
	} else if ($pos < 2) {
		$link_base = basename($_SERVER['SCRIPT_NAME']) . '?lang=';
	} else {
		$link_base = basename($_SERVER['SCRIPT_NAME']) . '?' . htmlspecialchars(substr($_SERVER['QUERY_STRING'], 0, $pos-1), ENT_QUOTES) . '&amp;lang=';
	}
	$langimage_html = '';
	foreach ($easiestml_langs as $l => $lang) {
		$langimage_html .= '<a href="' . $link_base . urlencode($lang) . '"><img src="' . ICMS_URL . '/' . $langimages[$l] . '" title="' . $langnames[$l] . '" alt="' . $langnames[$l] . '" /></a>&nbsp;';
		$s = preg_replace('/\[change_lang_' . $lang . '\]/', $link_base . urlencode($lang), $s);
	}
	$s = preg_replace('/\[' . EASIESTML_IMAGETAG . '\]/', $langimage_html, $s);

	// create the pattern between language tags
	//$pqhtmltags = explode( ',' , preg_quote( EASIESTML_NEVERCROSSTAGS , '/')) ;
	//$mid_pattern = '(?:(?!(' . implode( '|' , $pqhtmltags) . ')).)*' ;

	// eliminate description between the other language tags.
	foreach ($easiestml_langs as $lang) {
		if ($easiestml_lang == $lang) continue;
		$s = preg_replace_callback('/\[' . preg_quote($lang) . '\].*\[\/' . preg_quote($lang) . '(?:\]\<br \/\>|\])/isU', 'easiestml_check_nevercross', $s);
	}

	// simple pattern to strip selected lang_tags (remove all tags)
	$s = preg_replace('/\[\/?' . preg_quote($easiestml_lang) . '\](\<br \/\>)?/i', '', $s);

	// much complex pattern to strip valid pair of selected lag_tags (BUGGY?)
	// $s = str_replace( '['.$easiestml_lang.']<br />' , '['.$easiestml_lang.']' , $s) ;
	// $s = str_replace( '[/'.$easiestml_lang.']<br />' , '[/'.$easiestml_lang.']' , $s) ;
	// $s = preg_replace( '/(\['.preg_quote($easiestml_lang).'\])('.$mid_pattern.')(\[\/'.preg_quote($easiestml_lang).'\])/isU' , '$2' , $s) ;

	/* list($usec, $sec) = explode(" ",microtime());
	 $GIJ_end_time = ((float)$sec + (float)$usec);
	 error_log( ($GIJ_end_time - $GLOBALS['GIJ_start_time']) . "(sec)\n" , 3 , "/tmp/error_log") ; */

	return $s;
}

/**
 * Escape textbox function for MultiLanguage
 *
 * @param array $matches Matches array to escape
 * @return array
 */
function easiestml_escape_bracket_textbox($matches) {
	if (preg_match('/type=["\']?text["\']?/i', $matches[2])) {
		return $matches[1] . str_replace('[', '&#91;', $matches[2]) . $matches[3];
	} else {
		return $matches[1] . $matches[2] . $matches[3];
	}
}

/**
 * Escape textarea function for MultiLanguage
 *
 * @param array $matches Matches array to escape
 * @return array
 */
function easiestml_escape_bracket_textarea($matches) {
	return $matches[1] . str_replace('[', '&#91;', $matches[2]) . $matches[3];
}

/**
 * Escape regex function for MultiLanguage
 *
 * @param array $matches Matches array to escape
 * @return array
 */
function easiestml_check_nevercross($matches) {
	$answer = '';
	if (EASIESTML_NEVERCROSSREGEX != '') {
		$answer = preg_match(EASIESTML_NEVERCROSSREGEX, $matches[0]) ? $matches[0] : '';
	}
	return $answer;
}

/**
 * Fix for bug #1905485 in tracker
 *
 * @param array $matches Matches array to escape
 * @return array
 */
function easiestml_escape_bracket_input($matches) {
	return str_replace('[', '&#91;', $matches[1]);
}
