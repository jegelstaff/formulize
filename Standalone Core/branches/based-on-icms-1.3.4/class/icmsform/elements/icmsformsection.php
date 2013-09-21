<?php
/**
 * Form control creating a section in a form for an object derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		icms_ipf_Object
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: icmsformsection.php 10848 2010-12-05 14:54:42Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * We cannot extend the refactored class since parameters for the constructor have changed.
 * However, this shouldn't be a problem because the class should never be instantiated directly.
 */
class IcmsFormSection extends icms_form_Element {
	private $_deprecated;

	public function __construct($sectionname, $value = FALSE) {
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_form_elements_Section', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}