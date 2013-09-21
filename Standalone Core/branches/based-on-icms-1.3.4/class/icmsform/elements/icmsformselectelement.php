<?php
/**
 * Form control creating a selectbox for an object derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		icms_ipf_Object
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: icmsformselectelement.php 10712 2010-10-11 16:59:46Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

class IcmsFormSelectElement extends icms_ipf_form_elements_Select {
	private $_deprecated;

	public function __construct($object, $key) {
		parent::__construct($object, $key);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_form_elements_Select', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}