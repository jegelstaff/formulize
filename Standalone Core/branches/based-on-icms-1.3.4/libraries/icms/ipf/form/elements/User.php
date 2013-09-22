<?php
/**
 * Form control creating a simple users selectbox for an object derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		ipf
 * @subpackage	form
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: User.php 10756 2010-11-13 17:18:27Z malanciault $
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

class icms_ipf_form_elements_User extends icms_form_elements_Select {
	private $_multiple = false;

	/**
	 * Constructor
	 * @param	object    $object   reference to targetobject (@link icms_ipf_Object)
	 * @param	string    $key      the form name
	 */
	public function __construct($object, $key) {
		$var = $object->vars[$key];
		$size = isset($var['size']) ? $var['size'] : ($this->_multiple ? 5 : 1);

		parent::__construct($var['form_caption'], $key, $object->getVar($key, 'e'), $size, $this->_multiple);

		$sql = "SELECT uid, uname FROM " . icms::$xoopsDB->prefix("users"). " ORDER BY uname ASC";
		$result = icms::$xoopsDB->query($sql);
		if ($result) {
			while ($myrow = icms::$xoopsDB->fetchArray($result)) {
				$uArray[$myrow["uid"]] = $myrow["uname"];
			}
		}
		$this->addOptionArray($uArray);

	}
}