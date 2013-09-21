<?php
/**
 * Form control creating an hidden field for an object derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		icms_ipf_Object
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: icmsformhidden.php 10828 2010-12-04 15:37:39Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

class IcmsFormHidden extends icms_form_elements_Hidden {
	private $_deprecated;

	public function __construct($name, $value) {
		parent::__construct($name, $value);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_Hidden', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}