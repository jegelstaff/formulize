<?php
/**
 * Form control creating a Date Picker element for an object derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		ipf
 * @subpackage	form
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: Date.php 10733 2010-10-16 17:31:12Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

class icms_ipf_form_elements_Date extends icms_form_elements_Date {
	/**
	 * Constructor
	 * @param	object    $object   reference to targetobject (@link icms_ipf_Object)
	 * @param	string    $key      the form name
	 */
	public function __construct($object, $key) {
		parent::__construct($object->vars[$key]['form_caption'], $key, 15, $object->getVar($key, 'e'));
	}
}