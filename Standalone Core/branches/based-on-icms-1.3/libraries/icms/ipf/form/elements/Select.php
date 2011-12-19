<?php
/**
 * Form control creating a selectbox for an object derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		ipf
 * @subpackage	form
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: Select.php 21230 2011-03-24 23:43:48Z m0nty_ $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

class icms_ipf_form_elements_Select extends icms_form_elements_Select {
	protected $_multiple = false;

	/**
	 * Constructor
	 * @param	object    $object   reference to targetobject (@link icms_ipf_Object)
	 * @param	string    $key      the form name
	 */
	public function __construct($object, $key) {
		$var = $object->vars[$key];
		$size = isset($var['size']) ? $var['size'] : ($this->_multiple ? 5 : 1);

		// Adding the options inside this SelectBox
		// If the custom method is not from a module, than it's from the core
		$control = $object->getControl($key);

		$value = isset($control['value']) ? $control['value'] : $object->getVar($key, 'e');

		parent::__construct($var['form_caption'], $key, $value, $size, $this->_multiple);

		if (isset($control['options'])) {
			$this->addOptionArray($control['options']);
		} else {
			// let's find if the method we need to call comes from an already defined object
			if (isset($control['object'])) {
				if (method_exists($control['object'], $control['method'])) {
					if ($option_array = $control['object']->$control['method']()) {
						// Adding the options array to the select element
						$this->addOptionArray($option_array);
					}
				}
			} else {
				// finding the itemHandler; if none, let's take the itemHandler of the $object
				if (isset($control['itemHandler'])) {
					if (!isset($control['module'])) {
						// Creating the specified core object handler
						$control_handler = icms::handler($control['itemHandler']);
					} else {
						$control_handler =& icms_getModuleHandler($control['itemHandler'], $control['module']);
					}
				} else {
					$control_handler =& $object->handler;
				}

				// Checking if the specified method exists
				if (method_exists($control_handler, $control['method'])) {
					$option_array = call_user_func_array(array($control_handler, $control['method']),
						isset($control['params']) ? $control['params'] : array());
					if (is_array($option_array) && count($option_array) > 0) {
						// Adding the options array to the select element
						$this->addOptionArray($option_array);
					}
				}
			}
		}
	}
}