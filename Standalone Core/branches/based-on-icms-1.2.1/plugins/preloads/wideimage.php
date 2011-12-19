<?php
/**
* Initiating Wideimage library
*
* This file is responsible for initiating the Wideimage library
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		libraries
* @since		1.2
* @author		TheRplima <therplima@impresscms.org>
* @version		$Id: wideimage.php 1742 2008-04-20 14:46:20Z real_therplima $
*/

class IcmsPreloadWideimage extends IcmsPreloadItem
{
	function eventStartOutputInit() {
		// just including the file... more to come
		include ( ICMS_LIBRARIES_PATH."/wideimage/lib/WideImage.php" );
	}
	
	function eventAdminHeader() {
		include ( ICMS_LIBRARIES_PATH."/wideimage/lib/WideImage.php" );
	}
}
?>