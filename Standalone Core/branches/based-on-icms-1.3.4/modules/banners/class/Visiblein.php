<?php
/**
* Class representing visible positions for banner objects
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: Visiblein.php 20209 2010-09-26 13:41:19Z phoenyx $
*/
defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_banners_Visiblein extends icms_ipf_Object {
	/**
	 * Constructor
	 *
	 * @param object $handler BannersPostHandler object
	 */
	public function __construct(&$handler) {
		parent::__construct($handler);

		$this->quickInitVar('visiblein_id', XOBJ_DTYPE_INT, TRUE);
		$this->quickInitVar('banner_id', XOBJ_DTYPE_INT, TRUE);
		$this->quickInitVar('module', XOBJ_DTYPE_INT, TRUE);
		$this->quickInitVar('page', XOBJ_DTYPE_INT, TRUE);
	}
}