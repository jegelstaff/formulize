<?php
/**
 * UrlLink Handler
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	icms
 * @package		data
 * @subpackage	urllink
 * @since		1.3
 * @author		Phoenyx
 * @version		$Id: Handler.php 10849 2010-12-05 18:46:02Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

class icms_data_urllink_Handler extends icms_ipf_Handler {
	/**
	 * constrcutor
	 *
	 * @param object $db database connection
	 */
	public function __construct(&$db) {
		parent::__construct($db, "data_urllink", "urllinkid", "caption", "desc", "icms");
	}
}