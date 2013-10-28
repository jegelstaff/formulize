<?php
/**
 * ImpressCMS Custom Tag features
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		libraries
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: adsense.php 22210 2011-08-10 02:34:28Z skenow $
 */

/**
 *
 * Preload items and events for AdSense
 * @since 1.2
 *
 */
class IcmsPreloadAdsense extends icms_preload_Item {
	/**
	 * Function to be triggered at the end of the core boot process
	 */
	function eventFinishCoreBoot() {
		icms_loadLanguageFile('system', 'adsense', TRUE);
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
	public function eventAfterPreviewTarea($array) {
		$array[0] = preg_replace_callback(array("/\[adsense](.*)\[\/adsense\]/sU"),
			'icms_sanitizeAdsenses_callback', $array[0]);
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
	public function eventAfterDisplayTarea($array) {
		$array[0] = preg_replace_callback(array("/\[adsense](.*)\[\/adsense\]/sU"),
			'icms_sanitizeAdsenses_callback', $array[0]);
	}

	/**
	 * Function to be triggered at the end of the output init process
	 *
	 * @return	void
	 */
	public function eventStartOutputInit() {
		global $icmsTpl;
		$icms_adsense_handler = icms_getModuleHandler("adsense", "system");
		$icms_adsensesObj = $icms_adsense_handler->getAdsensesByTag();
		$adsenses_array = array();
		if (is_object($icmsTpl)) {
			foreach ($icms_adsensesObj as $k => $v) {
				$adsenses_array[$k] = $v->render();
			}
			$icmsTpl->assign('icmsAdsenses', $adsenses_array);
		}
	}
}
