<?php
/**
 * Classes responsible for managing profile category objects
 *
 * @copyright	The ImpressCMS Project <http://www.impresscms.org>
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 * @package		profile
 * @version		$Id: Category.php 20113 2010-09-08 19:12:39Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_Category extends icms_ipf_Object {
	/**
	 * Constructor
	 *
	 * @param mod_profile_CategoryHandler $handler object handler
	 */
	public function __construct(&$handler) {
		parent::__construct($handler);

		$this->quickInitVar('catid', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('cat_title', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('cat_description', XOBJ_DTYPE_TXTAREA, false);
		$this->quickInitVar('cat_weight', XOBJ_DTYPE_TXTBOX, false, false, false, 0);
	}

	/**
	 * Overriding the icms_ipf_Object::getVar method to assign a custom method on some
	 * specific fields to handle the value before returning it
	 *
	 * @param str $key key of the field
	 * @param str $format format that is requested
	 * @return mixed value of the field that is requested
	 */
	public function getVar($key, $format = 's') {
		if ($format == 's' && in_array($key, array())) {
			return call_user_func(array($this,	$key));
		}
		return parent::getVar($key, $format);
	}

	/**
	 * return the category title
	 *
	 * @return string category title
	 */
	public function getCatTitle() {
		return $this->getVar('cat_title');
	}

	/**
	 * generate textbox control to edit weight on acp
	 *
	 * @return str textbox control
	 */
	public function getCat_weightControl() {
		$control = new icms_form_elements_Text('', 'cat_weight[]', 5, 4, $this->getVar('cat_weight'));
		return $control->render();
	}
}
?>