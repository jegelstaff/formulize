<?php
/**
 * Form control creating the options of a block
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		icms_ipf_Object
 * @since		1.2
 * @author		marcan <marcan@impresscms.org>
 * @author		phoenyx
 * @version		$Id:$
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

class IcmsFormBlockoptionsElement extends icms_ipf_form_elements_Blockoptions {
	private $_deprecated;

	public function __construct($object, $key) {
		parent::__construct($object, $key);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_form_elements_Blockoptions', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}