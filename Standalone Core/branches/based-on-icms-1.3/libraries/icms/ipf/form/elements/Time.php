<?php
/**
 * Form control creating a textbox to enter time for an object derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		ipf
 * @subpackage	form
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: Time.php 20279 2010-10-10 17:11:29Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

class icms_ipf_form_elements_Time extends icms_form_elements_Select {
	/**
	 * Constructor
	 * @param	object    $object   reference to targetobject (@link icms_ipf_Object)
	 * @param	string    $key      the form name
	 */
	public function __construct($object, $key) {
		$var = $object->vars[$key];
		$timearray = array();
		for ($i = 0; $i < 24; $i++) {
			for ($j = 0; $j < 60; $j = $j + 10) {
				$key_t = ($i * 3600) + ($j * 60);
				$timearray[$key_t] = ($j != 0) ? $i.':'.$j : $i.':0'.$j;
			}
		}
		ksort($timearray);
		parent::__construct($var['form_caption'], $key, $object->getVar($key, 'e'));
		$this->addOptionArray($timearray);
	}
}