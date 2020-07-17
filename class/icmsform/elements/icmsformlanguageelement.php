<?php
/**
 * Form control creating an image upload element for an object derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: icmsformlanguageelement.php 20285 2010-10-11 18:59:29Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

class IcmsFormLanguageElement extends icms_ipf_form_elements_Language {
	private $_deprecated;

	public function __construct($object, $key) {
		parent::__construct($object, $key);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_form_elements_Language', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}