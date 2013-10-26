<?php
/**
 * ImpressCMS Custom Tag features
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		libraries
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: customtag.php 20558 2010-12-19 18:17:29Z phoenyx $
 */
/**
 *
 * Event triggers for Custom Tags
 * @since	1.2
 *
 */
class IcmsPreloadCustomtag extends icms_preload_Item {
	/**
	 * Function to be triggered at the end of the core boot process
	 */
	function eventFinishCoreBoot() {
		icms_loadLanguageFile("system", "customtag", TRUE);
	}

	/**
	 * Function to be triggered when entering in icms_core_Textsanitizer::displayTarea() function
	 *
	 * The $array var is structured like this:
	 * $array[0] = $text
	 * $array[1] = $html
	 * $array[2] = $smiley
	 * $array[3] = $xcode
	 * $array[4] = $image
	 * $array[5] = $br
	 *
	 * @param array array containing parameters passed by icms_core_Textsanitizer::displayTarea()
	 *
	 * @return	void
	 */
	function eventBeforePreviewTarea($array) {
		$array[0] = preg_replace_callback(array('/\[customtag](.*)\[\/customtag\]/sU'),
			"icms_sanitizeCustomtags_callback", $array[0]);
	}

	/**
	 * Function to be triggered when entering in icms_core_Textsanitizer::displayTarea() function
	 *
	 * The $array var is structured like this:
	 * $array[0] = $text
	 * $array[1] = $html
	 * $array[2] = $smiley
	 * $array[3] = $xcode
	 * $array[4] = $image
	 * $array[5] = $br
	 *
	 * @param array array containing parameters passed by icms_core_Textsanitizer::displayTarea()
	 *
	 * @return	void
	 */
	function eventBeforeDisplayTarea($array) {
		$array[0] = preg_replace_callback(array('/\[customtag](.*)\[\/customtag\]/sU'),
			"icms_sanitizeCustomtags_callback", $array[0]);
	}

	/**
	 * Function to be triggered at the end of the output init process
	 *
	 * @return	void
	 */
	function eventStartOutputInit() {
		global $icmsTpl;
		$icms_customtag_handler = icms_getModuleHandler("customtag", "system");
		$icms_customTagsObj = $icms_customtag_handler->getCustomtagsByName();
		$customtags_array = array();
		if (is_object($icmsTpl)) {
			foreach ($icms_customTagsObj as $k => $v) {
				$customtags_array[$k] = $v->render();
			}
			$icmsTpl->assign("icmsCustomtags", $customtags_array);
		}
	}
}