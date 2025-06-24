<?php
/**
 * Form control creating a file upload element for an object derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		IcmsForm
 * @subpackage	Elements
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: icmsformfileuploadelement.php 20288 2010-10-11 19:36:35Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

class IcmsFormFileUploadElement extends icms_ipf_form_elements_Fileupload {
	private $_deprecated;

	public function __construct($object, $key) {
		parent::__construct($object, $key);
		//$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_form_elements_Fileupload', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
