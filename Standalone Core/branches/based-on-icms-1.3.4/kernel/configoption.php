<?php
if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");
/**
 * @deprecated	Use icms_config_option_Object, instead
 * @todo		Remove in version 1.4
 */
class XoopsConfigOption extends icms_config_option_Object {
	private $_deprecated;
	public function __construct() {
		parent::getInstance();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_config_option_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
/**
 * @deprecated	use icms_config_option_Handler
 * @todo		Remove in version 1.4
 */
class XoopsConfigOptionHandler extends icms_config_option_Handler {
	private $_deprecated;
	public function __construct(&$db) {
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_config_option_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}