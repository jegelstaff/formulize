<?php
/**
* ImpressCMS Custom Tag features
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		libraries
* @since		1.1
* @author		marcan <marcan@impresscms.org>
* @version		$Id$
*/

class IcmsPreloadAdsense extends IcmsPreloadItem
{
	/**
	 * Function to be triggered at the end of the core boot process
	 *
	 * @return	void
	 */
	function eventFinishCoreBoot() {
		include_once(ICMS_ROOT_PATH . "/include/adsense.php");
	}

	/**
	 * Function to be triggered when entering in MyTextSanitizer::displayTarea() function
	 *
	 * The $array var is structured like this:
	 * $array[0] = $text
	 * $array[1] = $html
	 * $array[2] = $smiley
	 * $array[3] = $xcode
	 * $array[4] = $image
	 * $array[5] = $br
	 *
	 * @param array array containing parameters passed by MyTextSanitizer::displayTarea()
	 *
	 * @return	void
	 */
	function eventBeforePreviewTarea($array) {
		$array[0] = icms_sanitizeAdsenses($array[0]);
	}

	/**
	 * Function to be triggered when entering in MyTextSanitizer::displayTarea() function
	 *
	 * The $array var is structured like this:
	 * $array[0] = $text
	 * $array[1] = $html
	 * $array[2] = $smiley
	 * $array[3] = $xcode
	 * $array[4] = $image
	 * $array[5] = $br
	 *
	 * @param array array containing parameters passed by MyTextSanitizer::displayTarea()
	 *
	 * @return	void
	 */
	function eventBeforeDisplayTarea($array) {
		$array[0] = icms_sanitizeAdsenses($array[0]);
	}


	/**
	 * Function to be triggered at the end of the output init process
	 *
	 * @return	void
	 */
	function eventStartOutputInit() {
		global $xoopsTpl, $icms_adsense_handler;
		$adsenses_array = array();
		if (is_object($xoopsTpl)) {
			foreach($icms_adsense_handler->objects as $k=>$v) {
				$adsenses_array[$k] = $v->render();
			}
			$xoopsTpl->assign('icmsAdsenses', $adsenses_array);
		}
	}
}
?>