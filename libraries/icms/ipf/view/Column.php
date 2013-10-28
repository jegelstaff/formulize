<?php
/**
 * icms_ipf_Object Table Listing
 *
 * Contains the classes responsible for displaying a highly configurable and features rich listing of IcmseristableObject objects
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Ipf
 * @subpackage	View
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		SVN: $Id: Column.php 19775 2010-07-11 18:54:25Z malanciault $
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

/**
 * icms_ipf_view_Column class
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Ipf
 * @subpackage	View
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 */
class icms_ipf_view_Column {

	public $_keyname;
	public $_align;
	public $_width;
	public $_customMethodForValue;
	public $_extraParams;
	public $_sortable;
	public $_customCaption;

	/**
	 * Constructor
	 *
	 * @param unknown_type $keyname
	 * @param str $align
	 * @param unknown_type $width
	 * @param unknown_type $customMethodForValue
	 * @param unknown_type $param
	 * @param unknown_type $customCaption
	 * @param unknown_type $sortable
	 */
	public function __construct($keyname, $align = _GLOBAL_LEFT, $width = false, $customMethodForValue = false, $param = false, $customCaption = false, $sortable = true) {
		$this->_keyname = $keyname;
		$this->_align = $align;
		$this->_width = $width;
		$this->_customMethodForValue = $customMethodForValue;
		$this->_sortable = $sortable;
		$this->_param = $param;
		$this->_customCaption = $customCaption;
	}

	/**
	 * Accessor for keyname
	 */
	public function getKeyName() {
		return $this->_keyname;
	}

	/**
	 * Accessor for align
	 */
	public function getAlign() {
		return $this->_align;
	}

	/**
	 * Accessor
	 */
	public function isSortable() {
		return $this->_sortable;
	}

	/**
	 * Accessor for width
	 */
	public function getWidth() {
		if ($this->_width) {
			$ret = $this->_width;
		} else {
			$ret = '';
		}
		return $ret;
	}

	/**
	 * Accessor for custom caption
	 */
	public function getCustomCaption() {
		return $this->_customCaption;
	}

}

