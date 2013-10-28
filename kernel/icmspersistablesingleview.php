<?php
if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");
/**
 * @deprecated	Use icms_ipf_view_Row, instead
 * @todo		Remove in version 1.4
 *
 */
class IcmsPersistableRow extends icms_ipf_view_Row {
	private $_deprecated;
	public function __construct($keyname, $customMethodForValue = false, $header = false, $class = false) {
		parent::__construct($keyname, $customMethodForValue, $header, $class);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_view_Row', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
/**
 * @deprecated	Use icms_ipf_view_Single, instead
 * @todo		Remove in version 1.4
 *
 */
class IcmsPersistableSingleView extends icms_ipf_view_Single{

	private $_deprecated;
	public function __construct(&$object, $userSide = false, $actions = array(), $headerAsRow = true) {
		parent::__construct(&$object, $userSide, $actions, $headerAsRow);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_view_Single', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
?>