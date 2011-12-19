<?php
/**
 * Classes responsible for managing profile category objects
 *
 * @copyright	The ImpressCMS Project <http://www.impresscms.org>
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 * @package		profile
 * @version		$Id$
 */

if (!defined("ICMS_ROOT_PATH")) die("ICMS root path not defined");

// including the IcmsPersistabelSeoObject
include_once ICMS_ROOT_PATH . '/kernel/icmspersistableobject.php';

class ProfileCategory extends IcmsPersistableObject {

	/**
	 * Constructor
	 *
	 * @param object $handler ProfileCategoryHandler object
	 */
	public function __construct(& $handler) {
		global $icmsConfig;

		$this->IcmsPersistableObject($handler);

		$this->quickInitVar('catid', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('cat_title', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('cat_description', XOBJ_DTYPE_TXTAREA, false);
		$this->quickInitVar('cat_weight', XOBJ_DTYPE_TXTBOX, false, false, false, 0);

	}

	/**
	 * Overriding the IcmsPersistableObject::getVar method to assign a custom method on some
	 * specific fields to handle the value before returning it
	 *
	 * @param str $key key of the field
	 * @param str $format format that is requested
	 * @return mixed value of the field that is requested
	 */
	function getVar($key, $format = 's') {
		if ($format == 's' && in_array($key, array ())) {
			return call_user_func(array ($this,	$key));
		}
		return parent :: getVar($key, $format);
	}

	/**
	 * return the category title
	 *
	 * @return string category title
	 */
	function getCatTitle() {
		return $this->getVar('cat_title');
	}
}

class ProfileCategoryHandler extends IcmsPersistableObjectHandler {

	/**
	 * Constructor
	 */
	public function __construct(& $db) {
		$this->IcmsPersistableObjectHandler($db, 'category', 'catid', 'cat_title', 'cat_description', 'profile');
	}
}
?>