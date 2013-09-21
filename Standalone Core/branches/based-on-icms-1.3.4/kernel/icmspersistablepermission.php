<?php
if (!defined("ICMS_ROOT_PATH")) die("ImpressCMS root path not defined");
/**
 * @deprecated	Use icms_ipf_permission_Handler, instead
 * @todo		Remove in version 1.4
 *
 */
class IcmsPersistablePermissionHandler extends icms_ipf_permission_Handler {
	private $_deprecated;
	public function __construct(&$handler) {
		parent::__construct($handler);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_permission_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
?>