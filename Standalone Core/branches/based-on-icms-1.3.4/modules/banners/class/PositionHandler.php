<?php
/**
* Class responsible for managing banners position objects
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: PositionHandler.php 23919 2012-03-21 03:08:59Z qm-b $
*/
defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_banners_PositionHandler extends icms_ipf_Handler {
	private $_positions;

	/**
	 * Constructor
	 *
	 * @param object $db database object
	 */
	public function __construct(&$db) {
		parent::__construct($db, 'position', 'position_id', 'title', 'description', 'banners');
	}

	/**
	 * create and return banner positions array for value list
	 *
	 * @return array positions
	 */
	public function getPositionArray() {
		if (!count($this->_positions)) {
			$positions = $this->getObjects();
			foreach ($positions as $position) {
				$this->_positions[$position->getVar('position_id')] = $position->getVar('title') . ' ( ' . $position->getVar('width') . 'x' . $position->getVar('height') . ' )';
			}
		}
		return $this->_positions;
	}

	/**
	 * beforeSave event
	 *
	 * Event automatically triggered by IcmsPersistable Framework before the object is inserted
	 * or updated
	 *
	 * @param object $obj mod_banners_Banner object
	 * @return bool TRUE
	 */
	public function beforeSave(&$obj) {
		$obj->setVar('name', strtolower(str_replace(" ", "_", $obj->getVar('name'))));
		return TRUE;
	}
}