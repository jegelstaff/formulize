<?php
/**
 * @deprecated	Use icms_ipf_category_Object, instead
 * @todo		Remove in version 1.4
 *
 */
class IcmsPersistableCategory extends icms_ipf_category_Object {
	private $_deprecated;
	public function __construct() {
		parent::getInstance();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_category_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
/**
 * Provides data access mechanisms to the IcmsPersistableCategory object
 * @copyright 	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since 		1.1
 * @deprecated	Use icms_ipf_category_Handler, instead
 * @todo		Remove in version 1.4
 */
class IcmsPersistableCategoryHandler extends icms_ipf_category_Handler {
	private $_deprecated;
	public function __construct($db, $modulename) {
		parent::__construct($db, $modulename);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_category_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

?>