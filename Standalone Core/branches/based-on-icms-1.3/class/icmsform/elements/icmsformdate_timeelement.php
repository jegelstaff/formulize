<?php
/**
 * Form control creating a DateTime Picker element for an object derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: icmsformdate_timeelement.php 20295 2010-10-16 09:09:44Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

class IcmsFormDate_timeElement extends icms_ipf_form_elements_Datetime {
	private $_deprecated;

	public function __construct($object, $key) {
		parent::__construct($object, $key);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_form_elements_Datetime', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}