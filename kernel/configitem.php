<?php
if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");
/**
 * @deprecated	Use icms_config_item_Object, instead
 * @todo		Remove in version 1.4
 */
class XoopsConfigItem extends icms_config_item_Object {
	private $_deprecated;
	public function __construct() {
		parent::getInstance();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_config_item_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
/**
 * @deprecated	Use icms_config_item_Handler, instead
 * @todo		Remove in version 1.4
 */
class XoopsConfigItemHandler extends icms_config_item_Handler {
	private $_deprecated;
	public function __construct(&$db) {
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_config_item_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}