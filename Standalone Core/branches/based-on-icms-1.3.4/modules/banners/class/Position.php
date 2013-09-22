<?php
/**
* Class representing the banners position objects
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: Position.php 20209 2010-09-26 13:41:19Z phoenyx $
*/
defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_banners_Position extends icms_ipf_Object {
	/**
	 * Constructor
	 *
	 * @param object $handler BannersPostHandler object
	 */
	public function __construct(&$handler) {
		parent::__construct($handler);

		$this->quickInitVar('position_id', XOBJ_DTYPE_INT, TRUE);
		$this->quickInitVar('name', XOBJ_DTYPE_TXTBOX, TRUE);
		$this->quickInitVar('title', XOBJ_DTYPE_TXTBOX, TRUE);
		$this->quickInitVar('description', XOBJ_DTYPE_TXTBOX, FALSE);
		$this->quickInitVar('width', XOBJ_DTYPE_INT, TRUE);
		$this->quickInitVar('height', XOBJ_DTYPE_INT, TRUE);
		$this->initNonPersistableVar('dimension', XOBJ_DTYPE_OTHER);

		$this->hideFieldFromForm('dimension');
	}

	/**
	 * Overriding the IcmsPersistableObject::getVar method to assign a custom method on some
	 * specific fields to handle the value before returning it
	 *
	 * @param str $key key of the field
	 * @param str $format format that is requested
	 * @return mixed value of the field that is requested
	 */
	public function getVar($key, $format = 's') {
		if ($format == 's' && in_array($key, array('dimension'))) {
			return call_user_func(array($this,	$key));
		}
		return parent::getVar($key, $format);
	}

	/**
	 * custom getVar function for dimension
	 *
	 * @return str dimension
	 */
	private function dimension() {
		return $this->getVar('width') . "x" . $this->getVar('height') . "px";
	}

	/**
	 * get position title for display on table
	 *
	 * @return str position title
	 */
	public function getTitleForTableDisplay() {
		return $this->getVar('title');
	}
}