<?php
/**
 * Form control creating an autocomplete select box powered by Scriptaculous for an object derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		icms_ipf_Object
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: icmsformautocompleteelement.php 20461 2010-12-04 15:22:46Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

class IcmsFormAutocompleteElement extends icms_ipf_form_elements_Autocomplete {
	private $_deprecated;

	public function __construct($object, $key) {
		parent::__construct($object, $key);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_form_elements_Autocomplete', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}