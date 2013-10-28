<?php
if (!defined("ICMS_ROOT_PATH")) die("ImpressCMS root path not defined");
/**
 *
 * @deprecated	Use icms_ipf_Handler, instead
 * @todo		Remove in version 1.4
 *
 */
class IcmsPersistableObjectHandler extends icms_ipf_Handler {
	private $_deprecated;
	public function __construct(&$db, $itemname, $keyname, $idenfierName, $summaryName, $modulename) {
		parent::__construct($db, $itemname, $keyname, $idenfierName, $summaryName, $modulename);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}

	public function IcmsPersistableObjectHandler(&$db, $itemname, $keyname, $idenfierName, $summaryName, $modulename) {
		parent::__construct($db, $itemname, $keyname, $idenfierName, $summaryName, $modulename);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
?>