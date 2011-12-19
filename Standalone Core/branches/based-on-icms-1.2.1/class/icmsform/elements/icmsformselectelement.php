<?php
/**
* Form control creating a selectbox for an object derived from IcmsPersistableObject
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		  1.1
* @author		  marcan <marcan@impresscms.org>
* @version		$Id: icmsformselectelement.php 8559 2009-04-11 11:34:22Z icmsunderdog $
*/

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

class IcmsFormSelectElement extends XoopsFormSelect {
    var $multiple = false;

	/**
	 * Constructor
	 * @param	object    $object   reference to targetobject (@link IcmsPersistableObject)
	 * @param	string    $key      the form name
	 */
  function IcmsFormSelectElement($object, $key) {
    $var = $object->vars[$key];
    $size = isset($var['size']) ? $var['size'] : ($this->multiple ? 5 : 1);

    // Adding the options inside this SelectBox
    // If the custom method is not from a module, than it's from the core
    $control = $object->getControl($key);

    $value = isset($control['value']) ? $control['value'] : $object->getVar($key, 'e');

    $this->XoopsFormSelect($var['form_caption'], $key, $value, $size, $this->multiple);

    if (isset($control['options'])) {
    	$this->addOptionArray($control['options']);
    } else {
    	// let's find if the method we need to call comes from an already defined object
    	if (isset($control['object'])) {
    		if (method_exists($control['object'], $control['method'])) {
    			if ($option_array = $control['object']->$control['method']()) {
    				// Adding the options array to the XoopsFormSelect
    				$this->addOptionArray($option_array);
    			}
    		}
    	} else {
    		// finding the itemHandler; if none, let's take the itemHandler of the $object
    		if (isset($control['itemHandler'])) {
    			if (!$control['module']) {
    				// Creating the specified core object handler
    				$control_handler =& xoops_gethandler($control['itemHandler']);
    			} else {
    				$control_handler =& xoops_getmodulehandler($control['itemHandler'], $control['module']);
    			}
    		} else {
    			$control_handler =& $object->handler;
    		}

    		// Checking if the specified method exists
    		if (method_exists($control_handler, $control['method'])) {
    			// TODO : How could I pass the parameters in the following call ...
    			if ($option_array = $control_handler->$control['method']()) {
    				// Adding the options array to the XoopsFormSelect
    				$this->addOptionArray($option_array);
    			}
    		}
    	}
    }
  }
}

?>