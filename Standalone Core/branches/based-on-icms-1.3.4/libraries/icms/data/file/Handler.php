<?php
/**
 * Richfile Handler
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	icms
 * @package		data
 * @subpackage	richfile
 * @since		1.3
 * @author		Phoenyx
 * @version		$Id: Handler.php 10851 2010-12-05 19:15:30Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

class icms_data_file_Handler extends icms_ipf_Handler {
	/**
	 * constrcutor
	 *
	 * @param object $db database connection
	 */
	public function __construct(&$db) {
		parent::__construct($db, "data_file", "fileid", "caption", "desc", "icms");
	}

	/*
	 * afterDelete event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is deleted
	 *
	 * @param icms_data_file_Object $obj object
	 * @return bool TRUE
	 */
	protected function afterDelete(&$obj) {
		$imgUrl = $obj->getVar("url");
		if (strstr($imgUrl, ICMS_URL) !== FALSE) {
			$imgPath = str_replace(ICMS_URL, ICMS_ROOT_PATH, $imgUrl);
			if (is_file($imgPath)) unlink($imgPath);
		}
		return TRUE;
	}
}