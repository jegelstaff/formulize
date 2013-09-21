<?php
/**
 * Youtube TextSanitizer plugin
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @since		1.2
 * @package		plugins
 * @subpackage	textsanitizer
 * @version		$Id: youtube.php 10326 2010-07-11 18:54:25Z malanciault $
 */

/**
 *
 * Locates and replaces enclosed text with an embedded YouTube video
 * @param $ts
 * @param $text
 */
function textsanitizer_youtube(&$ts, $text) {
	$patterns[] = "/\[youtube=(['\"]?)([^\"']*),([^\"']*)\\1]([^\"]*)\[\/youtube\]/esU";
	$replacements[] = "textsanitizer_youtube_decode( '\\4', '\\2', '\\3' )";
	return preg_replace($patterns, $replacements, $text);
}

/**
 *
 * Adds button and script to the editor
 * @param $ele_name
 */
function render_youtube($ele_name) {
	global $xoTheme;
	$javascript='';
	$dirname = basename(dirname(__FILE__));
	if (isset($xoTheme)) {
		$xoTheme->addScript(ICMS_URL.'/plugins/textsanitizer/'.$dirname.'/'.$dirname.'.js',
		array('type' => 'text/javascript'));
	}
	$code = "<img
		onclick='javascript:icmsCodeYoutube(\"" . $ele_name . "\", \"" . htmlspecialchars(_ENTERYOUTUBEURL, ENT_QUOTES) . "\", \"" . htmlspecialchars(_ENTERHEIGHT, ENT_QUOTES) . "\", \"" . htmlspecialchars(_ENTERWIDTH, ENT_QUOTES)."\");'
		onmouseover='style.cursor=\"pointer\"'
		src='" . ICMS_URL . "/plugins/textsanitizer/" . $dirname . "/youtube.gif'
		alt='YouTube'
		title='YouTube' />&nbsp;";

	return array($code, $javascript);
}

/**
 *
 * Parses the enclosed text into a YouTube video
 * @param $url
 * @param $width
 * @param $height
 */
function textsanitizer_youtube_decode($url, $width, $height)
{
	if (!preg_match("/^http:\/\/(www\.)?youtube\.com\/watch\?v=(.*)/i", $url, $matches)) {
		trigger_error("Not matched: {$url} {$width} {$height}", E_USER_WARNING);
		return "";
	}
	$src = "http://www.youtube.com/v/" . $matches[2];
	if (empty($width) || empty($height)) {
		if (!$dimension = @getimagesize($src)) {
			return "";
		}
		if (!empty($width)) {
			$height = $dimension[1] * $width /  $dimension[0];
		} elseif (!empty($height)) {
			$width = $dimension[0] * $height /  $dimension[1];
		} else {
			list($width, $height) = array($dimension[0], $dimension[1]);
		}
	}
	$code = "<object width='{$width}' height='{$height}'><param name='movie' value='{$src}'></param>" .
                "<param name='wmode' value='transparent'></param>" .
                "<embed src='{$src}' type='application/x-shockwave-flash' wmode='transparent' width='425' height='350'></embed>" .
                "</object>";
	return $code;
}