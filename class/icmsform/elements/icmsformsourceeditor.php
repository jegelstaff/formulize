<?php
/**
 * Form control creating a textbox for an object derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		icms_ipf_Object
 * @since		1.2
 * @author		MekDrop <mekdrop@gmail.com>
 * @version		$Id: icmsformsourceeditorelement.php 01 2009-06-09 11:34:22Z mekdrop $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

class IcmsFormSourceEditor extends icms_ipf_form_elements_Source {
	private $_deprecated;

	public function __construct($object, $key) {
		parent::__construct($object, $key);
		//$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_form_elements_Source', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
